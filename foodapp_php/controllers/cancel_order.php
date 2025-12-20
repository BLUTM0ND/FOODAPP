<?php
session_start();
header('Content-Type: application/json');
include_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'] ?? 'cliente';

// Get order ID
$order_id = intval($_POST['order_id'] ?? 0);

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de pedido inválido']);
    exit;
}

try {
    // Check if order exists and belongs to current user (if client) or can be cancelled
    $check_sql = "SELECT p.id, p.estado, p.cliente_id FROM pedidos p WHERE p.id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('i', $order_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Pedido no encontrado']);
        exit;
    }

    $order = $check_result->fetch_assoc();
    $check_stmt->close();

    // Check permissions: clients can only cancel their own orders, admins/restaurants can cancel any
    if ($user_type === 'cliente' && $order['cliente_id'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para cancelar este pedido']);
        exit;
    }

    // Check if order can be cancelled (only PENDIENTE status)
    if ($order['estado'] !== 'PENDIENTE') {
        echo json_encode(['success' => false, 'message' => 'Solo se pueden cancelar pedidos en estado PENDIENTE']);
        exit;
    }

    // Update order status to CANCELADO
    $update_sql = "UPDATE pedidos SET estado = 'CANCELADO', updated_at = NOW() WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('i', $order_id);

    if ($update_stmt->execute()) {
        // Log the cancellation (optional)
        error_log("Pedido #$order_id cancelado por usuario #$user_id (tipo: $user_type)");

        echo json_encode(['success' => true, 'message' => 'Pedido cancelado exitosamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado del pedido']);
    }

    $update_stmt->close();

} catch (Exception $e) {
    error_log("Error al cancelar pedido #$order_id: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}

$conn->close();
?>
