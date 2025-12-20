<?php
session_start();
include_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pedido_id = intval($_POST['pedido_id'] ?? 0);
    $cal_rest = intval($_POST['cal_rest'] ?? 0);
    $cal_repartidor = intval($_POST['cal_repartidor'] ?? 0);
    $cal_ped = intval($_POST['cal_ped'] ?? 0);
    $comentario = trim($_POST['comentario'] ?? '');
    $cliente_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

    if ($cliente_id <= 0 || $pedido_id <= 0) {
        header('Location: ../views/profile.php?error=invalid');
        exit;
    }

    // Verificar si ya existe una valoración para este pedido y cliente
    $check_existing = $conn->prepare("SELECT id FROM valoraciones WHERE pedido_id = ? AND cliente_id = ? LIMIT 1");
    $check_existing->bind_param('ii', $pedido_id, $cliente_id);
    $check_existing->execute();
    $existing_result = $check_existing->get_result();
    if ($existing_result->num_rows > 0) {
        $check_existing->close();
        header('Location: ../views/ratings.php?id=' . $pedido_id . '&error=already_rated');
        exit;
    }
    $check_existing->close();

    // Get restaurante_id and repartidor_id safely
    $stmt = $conn->prepare("SELECT restaurante_id, repartidor_id FROM pedidos WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $pedido_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $rest_id = intval($row['restaurante_id']);
            $repartidor_id = intval($row['repartidor_id'] ?? 0);
        } else {
            $stmt->close();
            header('Location: ../views/profile.php?error=notfound');
            exit;
        }
        $stmt->close();
    } else {
        header('Location: ../views/profile.php?error=stmt');
        exit;
    }

    // Ensure restaurante actually exists (avoid FK violation)
    $check = $conn->prepare("SELECT id FROM restaurantes WHERE id = ? LIMIT 1");
    $valid_rest = false;
    if ($check) {
        $check->bind_param('i', $rest_id);
        $check->execute();
        $cres = $check->get_result();
        if ($cres && $cres->num_rows > 0) {
            $valid_rest = true;
        }
        $check->close();
    }

    // Fallback: try to find restaurante via detalle_pedido -> productos
    if (! $valid_rest) {
        $fb = $conn->prepare("SELECT p.restaurante_id FROM detalle_pedido dp JOIN productos p ON dp.producto_id = p.id WHERE dp.pedido_id = ? LIMIT 1");
        if ($fb) {
            $fb->bind_param('i', $pedido_id);
            $fb->execute();
            $fres = $fb->get_result();
            if ($fres && $fres->num_rows > 0) {
                $frow = $fres->fetch_assoc();
                $try_rest = intval($frow['restaurante_id']);
                if ($try_rest > 0) {
                    $chk2 = $conn->prepare("SELECT id FROM restaurantes WHERE id = ? LIMIT 1");
                    if ($chk2) {
                        $chk2->bind_param('i', $try_rest);
                        $chk2->execute();
                        $cres2 = $chk2->get_result();
                        if ($cres2 && $cres2->num_rows > 0) {
                            $rest_id = $try_rest;
                            $valid_rest = true;
                        }
                        $chk2->close();
                    }
                }
            }
            $fb->close();
        }
    }

    if (! $valid_rest) {
        header('Location: ../views/profile.php?error=invalid_restaurant');
        exit;
    }

    // Insert valoración using prepared statement
    $ins = $conn->prepare("INSERT INTO valoraciones (cliente_id, pedido_id, restaurante_id, calificacion_restaurante, calificacion_repartidor, calificacion_pedido, comentario) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($ins) {
        $ins->bind_param('iiiiiss', $cliente_id, $pedido_id, $rest_id, $cal_rest, $cal_repartidor, $cal_ped, $comentario);
        if ($ins->execute()) {
            $ins->close();

            // Update restaurant average rating
            $avg_query = $conn->prepare("SELECT AVG(calificacion_restaurante) as avg_rating FROM valoraciones WHERE restaurante_id = ?");
            $avg_query->bind_param('i', $rest_id);
            $avg_query->execute();
            $avg_result = $avg_query->get_result();
            $avg_data = $avg_result->fetch_assoc();
            $new_avg_rating = round($avg_data['avg_rating'], 1);
            $avg_query->close();

            // Update restaurant rating
            $update_rest = $conn->prepare("UPDATE restaurantes SET calificacion = ? WHERE id = ?");
            $update_rest->bind_param('di', $new_avg_rating, $rest_id);
            $update_rest->execute();
            $update_rest->close();

            // Update delivery person average rating if repartidor_id exists
            if ($repartidor_id > 0) {
                $avg_delivery_query = $conn->prepare("SELECT AVG(calificacion_repartidor) as avg_rating FROM valoraciones WHERE calificacion_repartidor > 0");
                $avg_delivery_query->execute();
                $avg_delivery_result = $avg_delivery_query->get_result();
                $avg_delivery_data = $avg_delivery_result->fetch_assoc();
                $new_avg_delivery_rating = round($avg_delivery_data['avg_rating'], 1);
                $avg_delivery_query->close();

                // Update repartidor rating (assuming there's a calificacion column in repartidores or usuarios table)
                // For now, we'll update the usuarios table if it has a calificacion column
                $update_delivery = $conn->prepare("UPDATE usuarios SET calificacion = ? WHERE id = ? AND tipo = 'repartidor'");
                $update_delivery->bind_param('di', $new_avg_delivery_rating, $repartidor_id);
                $update_delivery->execute();
                $update_delivery->close();
            }

            header('Location: ../views/profile.php?success=rating');
            exit;
        } else {
            $err = $ins->error;
            $ins->close();
            header('Location: ../views/profile.php?error=' . urlencode($err));
            exit;
        }
    } else {
        header('Location: ../views/profile.php?error=prepare');
        exit;
    }
}
?>
