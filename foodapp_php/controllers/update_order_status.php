<?php
session_start();
include_once '../includes/config.php';

header('Content-Type: application/json');

// Check if user is logged in as delivery person
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'repartidor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];

    // Validate status
    $valid_statuses = ['EN_CAMINO', 'ENTREGADO'];
    if (!in_array($status, $valid_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Estado inválido']);
        exit;
    }

    try {
        // Verify the order belongs to this delivery person
        $check = $conn->prepare("SELECT id FROM pedidos WHERE id = ? AND repartidor_id = ?");
        $check->bind_param('ii', $order_id, $user_id);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            // Update order status
            $update = $conn->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
            $update->bind_param('si', $status, $order_id);

            if ($update->execute()) {
                $message = $status === 'ENTREGADO' ? 'Pedido marcado como entregado' : 'Estado del pedido actualizado';
                echo json_encode(['success' => true, 'message' => $message]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado del pedido']);
            }
            $update->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Pedido no encontrado o no autorizado']);
        }
        $check->close();

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

$conn->close();
?>
