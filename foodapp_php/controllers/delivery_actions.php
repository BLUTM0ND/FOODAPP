<?php
session_start();
include_once '../includes/config.php';

// allow either session key for repartidor
$notRepartidor = true;
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'repartidor') $notRepartidor = false;
if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'repartidor') $notRepartidor = false;
if (!isset($_SESSION['user_id']) || $notRepartidor) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success'=>false,'message'=>'No autorizado']);
    exit;
}

$action = $_GET['action'] ?? '';
$user_id = intval($_SESSION['user_id']);

if ($action === 'start' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pedido_id = intval($_POST['pedido_id'] ?? 0);
    if ($pedido_id <= 0) {
        echo json_encode(['success'=>false,'message'=>'pedido_id inválido']);
        exit;
    }

    $stmt = $conn->prepare("SELECT p.id, p.direccion_entrega, p.estado, u.nombre AS cliente_nombre, r.id AS restaurant_id, r.nombre AS restaurant_name, r.ubicacion_gps, r.direccion AS restaurant_direccion, d.coordenadas_gps AS dest_gps
        FROM pedidos p
        LEFT JOIN restaurantes r ON p.restaurante_id = r.id
        LEFT JOIN clientes c ON p.cliente_id = c.id
        LEFT JOIN usuarios u ON c.id = u.id
        LEFT JOIN direcciones d ON d.cliente_id = c.id AND d.predeterminada = 1
        WHERE p.id = ? AND p.repartidor_id = ? LIMIT 1");
    $stmt->bind_param('ii', $pedido_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if (! $res || $res->num_rows == 0) {
        echo json_encode(['success'=>false,'message'=>'Pedido no encontrado o no asignado a este repartidor']);
        exit;
    }
    $row = $res->fetch_assoc();
    $restaurant_gps = $row['ubicacion_gps'] ?? null;
    $dest_gps = $row['dest_gps'] ?? null;

    // If restaurant data is missing (pedido may have been created without restaurante_id),
    // try to infer the restaurant from detalle_pedido -> productos -> restaurantes
    if (empty($row['restaurant_name'])) {
        $tryRest = $conn->prepare("SELECT r.id, r.nombre, r.ubicacion_gps, r.direccion FROM detalle_pedido dp JOIN productos p ON dp.producto_id = p.id JOIN restaurantes r ON p.restaurante_id = r.id WHERE dp.pedido_id = ? LIMIT 1");
        if ($tryRest) {
            $tryRest->bind_param('i', $pedido_id);
            $tryRest->execute();
            $rr = $tryRest->get_result();
            if ($rr && $rr->num_rows > 0) {
                $rdata = $rr->fetch_assoc();
                // populate missing fields
                $row['restaurant_id'] = $rdata['id'];
                $row['restaurant_name'] = $rdata['nombre'];
                $row['ubicacion_gps'] = $rdata['ubicacion_gps'];
                $row['restaurant_direccion'] = $rdata['direccion'];
                $restaurant_gps = $row['ubicacion_gps'] ?? $restaurant_gps;
            }
            $tryRest->close();
        }
    }

    // If restaurant GPS is missing, try to geocode the restaurant address via Nominatim
    if (empty($restaurant_gps) && !empty($row['restaurant_direccion'])) {
        $addr = urlencode($row['restaurant_direccion']);
        $url = "https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q={$addr}";
        // Nominatim requires a sensible User-Agent
        $opts = [
            'http' => [
                'method' => "GET",
                'header' => "User-Agent: FoodApp/1.0 (admin@foodapp.example)\r\n"
            ]
        ];
        $context = stream_context_create($opts);
        $res = @file_get_contents($url, false, $context);
        if ($res !== false) {
            $json = json_decode($res, true);
            if (is_array($json) && count($json) > 0 && !empty($json[0]['lat']) && !empty($json[0]['lon'])) {
                $restaurant_gps = $json[0]['lat'] . ',' . $json[0]['lon'];
                // Persist the discovered coordinates back to restaurantes.ubicacion_gps so future calls don't need geocoding
                if (!empty($row['restaurant_id'])) {
                    $updR = $conn->prepare("UPDATE restaurantes SET ubicacion_gps = ? WHERE id = ?");
                    if ($updR) {
                        $updR->bind_param('si', $restaurant_gps, $row['restaurant_id']);
                        $updR->execute();
                        $updR->close();
                    }
                }
            }
        }
    }

    // Consolidated lookup for destination GPS:
    // 1) If there is a saved 'dest_gps' from customer default address, use it.
    // 2) Else, try to read delivery_lat/delivery_lng from pedidos table (if columns exist and values present).
    // 3) Else, fallback to Plaza de Armas coordinates.
    if (empty($dest_gps)) {
        // check if pedidos has delivery_lat column
        $try = $conn->prepare("SHOW COLUMNS FROM pedidos LIKE 'delivery_lat'");
        if ($try) {
            $try->execute();
            $tres = $try->get_result();
            if ($tres && $tres->num_rows > 0) {
                $q = $conn->prepare("SELECT delivery_lat, delivery_lng FROM pedidos WHERE id = ? LIMIT 1");
                if ($q) {
                    $q->bind_param('i', $pedido_id);
                    $q->execute();
                    $qr = $q->get_result();
                    if ($qr && $qr->num_rows > 0) {
                        $rrow = $qr->fetch_assoc();
                        if (!empty($rrow['delivery_lat']) && !empty($rrow['delivery_lng'])) {
                            $dest_gps = $rrow['delivery_lat'] . ',' . $rrow['delivery_lng'];
                        }
                    }
                    $q->close();
                }
            }
            $try->close();
        }
    }

    // Final fallback: Plaza de Armas de Arequipa (shared point)
    if (empty($dest_gps)) {
        $dest_gps = '-16.3989,-71.5350';
        if (empty($row['cliente_nombre'])) $row['cliente_nombre'] = 'Plaza de Armas';
    }

    // Prepare restaurant lat/lng separately for convenience
    $restaurant_lat = null;
    $restaurant_lng = null;
    if (!empty($restaurant_gps)) {
        $parts = preg_split('/[, ]+/', $restaurant_gps);
        if (count($parts) >= 2) {
            $restaurant_lat = floatval($parts[0]);
            $restaurant_lng = floatval($parts[1]);
        }
    }

    echo json_encode([
        'success' => true,
        'pedido_id' => $row['id'],
        'restaurant_name' => $row['restaurant_name'],
        'client_name' => $row['cliente_nombre'],
        'restaurant_gps' => $restaurant_gps,
        'restaurant_lat' => $restaurant_lat,
        'restaurant_lng' => $restaurant_lng,
        'restaurant_address' => $row['restaurant_direccion'] ?? null,
        'direccion_entrega' => $row['direccion_entrega'] ?? null,
        'dest_gps' => $dest_gps
    ]);
    exit;

} elseif ($action === 'claim' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Allow a repartidor to claim an unassigned pedido. This uses an atomic UPDATE
    // so only one repartidor can claim it (WHERE repartidor_id IS NULL).
    $pedido_id = intval($_POST['pedido_id'] ?? 0);
    if ($pedido_id <= 0) {
        echo json_encode(['success'=>false,'message'=>'pedido_id inválido']);
        exit;
    }

    // Only allow claiming orders that are pending
    $allowed_states = ['PENDIENTE','CONFIRMADO'];

    // Perform atomic update: set repartidor_id and estado to EN_CAMINO only if currently unassigned
    $up = $conn->prepare("UPDATE pedidos SET repartidor_id = ?, estado = 'EN_CAMINO' WHERE id = ? AND (repartidor_id IS NULL OR repartidor_id = 0) AND estado = ? LIMIT 1");
    if (! $up) {
        echo json_encode(['success'=>false,'message'=>'Error preparando claim']);
        exit;
    }
    // We will try allowed states in sequence; execute once with PENDIENTE then fallback to CONFIRMADO
    $repartidor_id = $user_id;
    $claimed = false;
    foreach ($allowed_states as $st) {
        $up->bind_param('iis', $repartidor_id, $pedido_id, $st);
        if ($up->execute() && $up->affected_rows > 0) {
            $claimed = true;
            break;
        }
    }
    $up->close();

    if ($claimed) {
        echo json_encode(['success'=>true,'message'=>'Pedido tomado']);
    } else {
        echo json_encode(['success'=>false,'message'=>'No se pudo tomar el pedido. Puede que ya esté asignado.']);
    }
    exit;

} elseif ($action === 'update_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pedido_id = intval($_POST['pedido_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $allowed = ['EN_CAMINO','ENTREGADO','PREPARANDO','CONFIRMADO','CANCELADO','PENDIENTE'];
    if ($pedido_id <= 0 || !in_array($status, $allowed)) {
        echo json_encode(['success'=>false,'message'=>'Parámetros inválidos']);
        exit;
    }
    // ensure this repartidor is assigned to the pedido
    $chk = $conn->prepare("SELECT id FROM pedidos WHERE id = ? AND repartidor_id = ? LIMIT 1");
    $chk->bind_param('ii', $pedido_id, $user_id);
    $chk->execute();
    $cres = $chk->get_result();
    if (! $cres || $cres->num_rows == 0) {
        echo json_encode(['success'=>false,'message'=>'Pedido no asignado a este repartidor']);
        exit;
    }
    $chk->close();

    $up = $conn->prepare("UPDATE pedidos SET estado = ? WHERE id = ? AND repartidor_id = ?");
    if (! $up) {
        echo json_encode(['success'=>false,'message'=>'Error preparing statement']);
        exit;
    }
    $up->bind_param('sis', $status, $pedido_id, $user_id);
    if ($up->execute()) {
        echo json_encode(['success'=>true,'message'=>'Estado actualizado']);
    } else {
        echo json_encode(['success'=>false,'message'=>$up->error]);
    }
    $up->close();
    exit;

} else {
    echo json_encode(['success'=>false,'message'=>'Acción no válida']);
    exit;
}
