<?php
session_start();
include_once '../includes/config.php';

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../views/login.php');
    exit;
}

$cliente_id = intval($_SESSION['user_id']);
$cart_json = $_POST['cart'] ?? '[]';
$tipo_entrega = $_POST['tipo_entrega'] ?? ($_POST['entrega'] ?? 'DELIVERY');
$direccion = trim($_POST['direccion'] ?? '');
$metodo_pago = $_POST['metodo_pago'] ?? 'EFECTIVO';
$propina = floatval($_POST['propina'] ?? 0);

$cart = json_decode($cart_json, true);
if (!is_array($cart) || count($cart) === 0) {
    header('Location: ../views/cart.php?error=empty');
    exit;
}

// Calculate total
$total = 0.0;
foreach ($cart as $it) {
    $price = floatval($it['price'] ?? 0);
    $qty = intval($it['quantity'] ?? 0);
    $total += $price * $qty;
}
$total += $propina;

// Try to get restaurant id from cart (if provided)
$restaurante_id = null;
if (isset($cart[0]['restaurante_id'])) {
    $restaurante_id = intval($cart[0]['restaurante_id']);
}

// Read delivery coordinates early so we can include them in the INSERT if available
$delivery_lat = isset($_POST['delivery_lat']) ? trim($_POST['delivery_lat']) : '';
$delivery_lng = isset($_POST['delivery_lng']) ? trim($_POST['delivery_lng']) : '';

// Default plaza coords
$plaza_lat = -16.3989;
$plaza_lng = -71.5350;

// Normalize tipo
$tipo_upper = strtoupper(trim($tipo_entrega));
if ($tipo_upper === 'DELIVERY') {
    // if client didn't provide coords, leave empty for now; we'll default to plaza later
    if ($delivery_lat === '' || $delivery_lng === '') {
        // we'll set defaults later if DB supports columns
    }
}

// Determine if pedidos table has delivery columns so we can persist coords in the INSERT
$has_delivery_cols = false;
$colChk = $conn->prepare("SHOW COLUMNS FROM pedidos LIKE 'delivery_lat'");
if ($colChk) {
    $colChk->execute();
    $cres = $colChk->get_result();
    if ($cres && $cres->num_rows > 0) $has_delivery_cols = true;
    $colChk->close();
}
// If columns are missing, try to add them (best-effort). This requires DB ALTER privileges.
if (!$has_delivery_cols) {
    $tryAlter = $conn->query("ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS delivery_lat DECIMAL(10,8) NULL, ADD COLUMN IF NOT EXISTS delivery_lng DECIMAL(11,8) NULL");
    if ($tryAlter !== false) {
        // re-check
        $colChk2 = $conn->prepare("SHOW COLUMNS FROM pedidos LIKE 'delivery_lat'");
        if ($colChk2) {
            $colChk2->execute();
            $cres2 = $colChk2->get_result();
            if ($cres2 && $cres2->num_rows > 0) $has_delivery_cols = true;
            $colChk2->close();
        }
    }
}

// Insert pedido using prepared statement; include delivery coords if present and supported
$estado = 'PENDIENTE'; // Orders start as pending and admin changes status as needed
$descuento = 0.0;
$direccion_entrega = $direccion === '' ? null : $direccion;

if ($has_delivery_cols && $tipo_upper === 'DELIVERY') {
    // if client didn't provide coords, default to plaza
    if ($delivery_lat === '' || $delivery_lng === '') {
        $delivery_lat = (string)$plaza_lat;
        $delivery_lng = (string)$plaza_lng;
        if ($direccion_entrega === null) $direccion_entrega = 'Plaza de Armas, Arequipa (ubicación por defecto)';
    }
    $sql = "INSERT INTO pedidos (estado, total, metodo_pago, propina, descuento, cliente_id, restaurante_id, direccion_entrega, delivery_lat, delivery_lng) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die('Prepare failed: ' . $conn->error);
    }
    $bind_rest_id = $restaurante_id === null ? null : $restaurante_id;
    // types: s d s d d i i s d d => 'sdsddiisdd'
    $stmt->bind_param('sdsddiisdd', $estado, $total, $metodo_pago, $propina, $descuento, $cliente_id, $bind_rest_id, $direccion_entrega, $delivery_lat, $delivery_lng);
    if (!$stmt->execute()) {
        die('Error inserting pedido: ' . $stmt->error);
    }
    $pedido_id = $stmt->insert_id;
    $stmt->close();
} else {
    $sql = "INSERT INTO pedidos (estado, total, metodo_pago, propina, descuento, cliente_id, restaurante_id, direccion_entrega) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die('Prepare failed: ' . $conn->error);
    }
    $bind_rest_id = $restaurante_id === null ? null : $restaurante_id;
    // types: s d s d d i i s -> 'sdsddiis'
    $stmt->bind_param('sdsddiis', $estado, $total, $metodo_pago, $propina, $descuento, $cliente_id, $bind_rest_id, $direccion_entrega);
    if (!$stmt->execute()) {
        die('Error inserting pedido: ' . $stmt->error);
    }
    $pedido_id = $stmt->insert_id;
    $stmt->close();
}

// If delivery coordinates provided and table has columns, save them (UPDATE)
$delivery_lat = isset($_POST['delivery_lat']) ? trim($_POST['delivery_lat']) : '';
$delivery_lng = isset($_POST['delivery_lng']) ? trim($_POST['delivery_lng']) : '';
// If delivery coordinates provided and table has columns, save them (UPDATE)
$plaza_lat = -16.3989;
$plaza_lng = -71.5350;
$delivery_provided = ($delivery_lat !== '' && $delivery_lng !== '');
if ($tipo_upper === 'DELIVERY') {
    // if no coordinates were provided by client, persist Plaza de Armas as default so repartidores can take the pedido
    if (!$delivery_provided) {
        $delivery_lat = (string)$plaza_lat;
        $delivery_lng = (string)$plaza_lng;
        // also set direccion to a friendly default if empty
        if ($direccion_entrega === null) {
            $direccion_entrega = 'Plaza de Armas, Arequipa (ubicación por defecto)';
        }
    }

    // check if delivery columns exist
    $colChk = $conn->prepare("SHOW COLUMNS FROM pedidos LIKE 'delivery_lat'");
    if ($colChk) {
        $colChk->execute();
        $cres = $colChk->get_result();
        if ($cres && $cres->num_rows > 0) {
            $colChk->close();
            $upd = $conn->prepare("UPDATE pedidos SET delivery_lat = ?, delivery_lng = ?, direccion_entrega = ? WHERE id = ?");
            if ($upd) {
                $upd->bind_param('ddsi', $delivery_lat, $delivery_lng, $direccion_entrega, $pedido_id);
                $upd->execute();
                $upd->close();
            }
        } else {
            $colChk->close();
        }
    }
}

// Insert detalle_pedido
$stmt_det = $conn->prepare("INSERT INTO detalle_pedido (pedido_id, producto_id, cantidad, precio) VALUES (?, ?, ?, ?)");
if ($stmt_det === false) {
    die('Prepare detalle failed: ' . $conn->error);
}

foreach ($cart as $it) {
    $prod_id = intval($it['id'] ?? 0);
    $cantidad = intval($it['quantity'] ?? 0);
    $precio = floatval($it['price'] ?? 0);
    $stmt_det->bind_param('iiid', $pedido_id, $prod_id, $cantidad, $precio);
    if (!$stmt_det->execute()) {
        // log error but continue
        error_log('detalle insert error: ' . $stmt_det->error);
    }
    // Optional: decrement stock (not done automatically here)
}
$stmt_det->close();

// Optional: record a payment (simulated)
$stmt_pay = $conn->prepare("INSERT INTO pagos (pedido_id, monto, estado) VALUES (?, ?, 'COMPLETADO')");
if ($stmt_pay) {
    $stmt_pay->bind_param('id', $pedido_id, $total);
    $stmt_pay->execute();
    $stmt_pay->close();
}

// Send a small HTML that clears client cart and redirects to orders page
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Procesando Pedido...</title>
    <script>
        // clear cart and redirect
        try { localStorage.removeItem('cart'); } catch(e) {}
        window.location.href = '../index.php?success=1&pedido_id=<?php echo $pedido_id; ?>';
    </script>
    </head>
    <body>Procesando pedido...</body>
</html>
