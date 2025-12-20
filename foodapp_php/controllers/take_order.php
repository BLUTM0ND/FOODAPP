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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);

    try {
        // Check if order is still available
        $check = $conn->prepare("SELECT id FROM pedidos WHERE id = ? AND repartidor_id IS NULL AND estado = 'PENDIENTE'");
        $check->bind_param('i', $order_id);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            // Take the order
            $update = $conn->prepare("UPDATE pedidos SET repartidor_id = ?, estado = 'EN_CAMINO' WHERE id = ? AND repartidor_id IS NULL AND estado = 'PENDIENTE'");
            $update->bind_param('ii', $user_id, $order_id);

            if ($update->execute() && $update->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Pedido tomado exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se pudo tomar el pedido. Puede que ya haya sido asignado.']);
            }
            $update->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'El pedido ya no está disponible']);
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
