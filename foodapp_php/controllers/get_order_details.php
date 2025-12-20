<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in as delivery driver
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'repartidor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de pedido inválido']);
    exit;
}

$orderId = (int)$_GET['order_id'];

try {
    // Get order details with restaurant and delivery information
    $query = $conn->prepare("
        SELECT
            p.id,
            p.estado,
            p.direccion_entrega,
            p.delivery_lat,
            p.delivery_lng,
            r.nombre as restaurante_nombre,
            r.ubicacion_gps as restaurante_ubicacion_gps,
            r.direccion as restaurante_direccion,
            u.nombre as cliente_nombre,
            u.telefono as cliente_telefono
        FROM pedidos p
        JOIN restaurantes r ON p.restaurante_id = r.id
        JOIN usuarios u ON p.cliente_id = u.id
        WHERE p.id = ? AND p.estado IN ('LISTO', 'EN_CAMINO')
    ");
    
    $query->bind_param('i', $orderId);
    $query->execute();
    $result = $query->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Pedido no encontrado o no disponible']);
        exit;
    }
    
    $order = $result->fetch_assoc();
    $query->close();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Pedido no encontrado o no disponible']);
        exit;
    }

    // Validate coordinates
    if (empty($order['restaurante_ubicacion_gps']) ||
        empty($order['delivery_lat']) ||
        empty($order['delivery_lng'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Coordenadas faltantes para el pedido']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'order' => $order
    ]);

} catch (Exception $e) {
    error_log("Error getting order details: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>
