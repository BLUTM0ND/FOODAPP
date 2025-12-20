<?php
session_start();
include_once '../includes/config.php';

header('Content-Type: application/json');

// Check if user is logged in as delivery person
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'repartidor') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Get available orders (LISTO status, no delivery person assigned)
    $available_query = $conn->query("
        SELECT p.id, p.direccion_entrega, p.total, p.fecha_pedido, p.delivery_lat, p.delivery_lng,
               r.nombre as restaurante_nombre, r.direccion as restaurante_direccion, r.ubicacion_gps as restaurante_gps,
               COALESCE(c.nombre, 'Cliente') as cliente_nombre
        FROM pedidos p
        JOIN restaurantes r ON p.restaurante_id = r.id
        LEFT JOIN clientes cl ON p.cliente_id = cl.id
        LEFT JOIN usuarios c ON cl.id = c.id
        WHERE p.estado = 'LISTO'
        AND p.repartidor_id IS NULL
        ORDER BY p.fecha_pedido ASC
        LIMIT 20
    ");

    $available_orders = [];
    while ($order = $available_query->fetch_assoc()) {
        // Use real delivery coordinates if available, otherwise generate based on address
        $lat = floatval($order['delivery_lat']);
        $lng = floatval($order['delivery_lng']);

        if ($lat == 0 && $lng == 0) {
            // Fallback: Generate consistent coordinates based on address hash
            $address_hash = crc32($order['direccion_entrega'] ?: 'default_address');
            $lat = -16.3989 + (($address_hash % 600 - 300) / 10000); // Consistent variation around Arequipa center
            $lng = -71.5350 + ((($address_hash >> 16) % 600 - 300) / 10000);
        }

        // Get restaurant coordinates
        $restaurant_lat = -16.3989; // Default Arequipa center
        $restaurant_lng = -71.5350;

        if (!empty($order['restaurante_gps'])) {
            $coords = explode(',', $order['restaurante_gps']);
            if (count($coords) == 2) {
                $restaurant_lat = floatval(trim($coords[0]));
                $restaurant_lng = floatval(trim($coords[1]));
            }
        }

        $available_orders[] = [
            'id' => $order['id'],
            'lat' => $lat,
            'lng' => $lng,
            'restaurante_ubicacion_gps' => $restaurant_lat . ',' . $restaurant_lng,
            'address' => $order['direccion_entrega'] ?: 'Dirección no especificada',
            'restaurant' => $order['restaurante_nombre'],
            'cliente' => $order['cliente_nombre'],
            'total' => floatval($order['total']),
            'hora' => date('H:i', strtotime($order['fecha_pedido']))
        ];
    }

    // Get delivery person's orders
    $my_orders_query = $conn->prepare("
        SELECT p.id, p.direccion_entrega, p.total, p.fecha_pedido, p.estado, p.updated_at, p.delivery_lat, p.delivery_lng,
               r.nombre as restaurante_nombre, r.direccion as restaurante_direccion, r.ubicacion_gps as restaurante_gps,
               COALESCE(c.nombre, 'Cliente') as cliente_nombre
        FROM pedidos p
        JOIN restaurantes r ON p.restaurante_id = r.id
        LEFT JOIN clientes cl ON p.cliente_id = cl.id
        LEFT JOIN usuarios c ON cl.id = c.id
        WHERE p.repartidor_id = ?
        AND p.estado IN ('EN_CAMINO', 'ENTREGADO')
        ORDER BY p.fecha_pedido DESC
        LIMIT 20
    ");
    $my_orders_query->bind_param('i', $user_id);
    $my_orders_query->execute();
    $my_orders_result = $my_orders_query->get_result();

    $my_orders = [];
    while ($order = $my_orders_result->fetch_assoc()) {
        // Use real delivery coordinates if available, otherwise generate based on address
        $lat = floatval($order['delivery_lat']);
        $lng = floatval($order['delivery_lng']);

        if ($lat == 0 && $lng == 0) {
            // Fallback: Generate consistent coordinates based on address hash
            $address_hash = crc32($order['direccion_entrega'] ?: 'default_address');
            $lat = -16.3989 + (($address_hash % 600 - 300) / 10000); // Consistent variation around Arequipa center
            $lng = -71.5350 + ((($address_hash >> 16) % 600 - 300) / 10000);
        }

        // Get restaurant coordinates
        $restaurant_lat = -16.3989; // Default Arequipa center
        $restaurant_lng = -71.5350;

        if (!empty($order['restaurante_gps'])) {
            $coords = explode(',', $order['restaurante_gps']);
            if (count($coords) == 2) {
                $restaurant_lat = floatval(trim($coords[0]));
                $restaurant_lng = floatval(trim($coords[1]));
            }
        }

        $my_orders[] = [
            'id' => $order['id'],
            'lat' => $lat,
            'lng' => $lng,
            'restaurante_ubicacion_gps' => $restaurant_lat . ',' . $restaurant_lng,
            'address' => $order['direccion_entrega'] ?: 'Dirección no especificada',
            'restaurant' => $order['restaurante_nombre'],
            'cliente' => $order['cliente_nombre'],
            'total' => floatval($order['total']),
            'status' => $order['estado'],
            'hora' => date('H:i', strtotime($order['fecha_pedido'])),
            'updated_at' => $order['updated_at']
        ];
    }

    $my_orders_query->close();

    echo json_encode([
        'available_orders' => $available_orders,
        'my_orders' => $my_orders
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
