<?php
session_start();
include_once '../includes/config.php';

// Check if restaurant owner is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'restaurante') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Get restaurant ID for this user
$user_id = intval($_SESSION['user_id']);
$restaurant_query = $conn->prepare("SELECT id FROM restaurantes WHERE usuario_id = ? LIMIT 1");
$restaurant_query->bind_param('i', $user_id);
$restaurant_query->execute();
$restaurant_result = $restaurant_query->get_result();
if ($restaurant_result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Restaurante no encontrado']);
    exit;
}
$restaurant = $restaurant_result->fetch_assoc();
$restaurant_id = $restaurant['id'];
$restaurant_query->close();

$action = $_GET['action'] ?? '';

if ($action === 'delete_product' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id'] ?? 0);

    // Verify product belongs to this restaurant
    $check = $conn->prepare("SELECT id FROM productos WHERE id = ? AND restaurante_id = ?");
    $check->bind_param('ii', $product_id, $restaurant_id);
    $check->execute();
    if ($check->get_result()->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
        exit;
    }
    $check->close();

    // Delete product
    $delete = $conn->prepare("DELETE FROM productos WHERE id = ? AND restaurante_id = ?");
    $delete->bind_param('ii', $product_id, $restaurant_id);
    if ($delete->execute()) {
        echo json_encode(['success' => true, 'message' => 'Producto eliminado']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar producto']);
    }
    $delete->close();

} elseif ($action === 'update_order_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = intval($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    $valid_statuses = ['PENDIENTE', 'CONFIRMADO', 'PREPARANDO', 'LISTO', 'EN_CAMINO', 'ENTREGADO', 'CANCELADO'];
    if (!in_array($status, $valid_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Estado inválido']);
        exit;
    }

    // Verify order belongs to this restaurant
    $check = $conn->prepare("SELECT id FROM pedidos WHERE id = ? AND restaurante_id = ?");
    $check->bind_param('ii', $order_id, $restaurant_id);
    $check->execute();
    if ($check->get_result()->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Pedido no encontrado']);
        exit;
    }
    $check->close();

    // Update order status
    $update = $conn->prepare("UPDATE pedidos SET estado = ? WHERE id = ? AND restaurante_id = ?");
    $update->bind_param('sii', $status, $order_id, $restaurant_id);
    if ($update->execute()) {
        echo json_encode(['success' => true, 'message' => 'Estado actualizado']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar estado']);
    }
    $update->close();

} elseif ($action === 'update_restaurant' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $ubicacion_gps = trim($_POST['ubicacion_gps'] ?? '');

    if (empty($nombre)) {
        echo json_encode(['success' => false, 'message' => 'Nombre es requerido']);
        exit;
    }

    // Update restaurant info
    $update = $conn->prepare("UPDATE restaurantes SET nombre = ?, direccion = ?, telefono = ?, ubicacion_gps = ? WHERE id = ?");
    $update->bind_param('ssssi', $nombre, $direccion, $telefono, $ubicacion_gps, $restaurant_id);
    if ($update->execute()) {
        echo json_encode(['success' => true, 'message' => 'Información actualizada']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar información']);
    }
    $update->close();

} elseif ($action === 'add_product' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = floatval($_POST['precio'] ?? 0);
    $categoria = trim($_POST['categoria'] ?? '');
    $imagen = trim($_POST['imagen'] ?? '');

    if (empty($nombre) || empty($descripcion) || $precio <= 0 || empty($categoria)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
        exit;
    }

    // Add product
    $insert = $conn->prepare("INSERT INTO productos (restaurante_id, nombre, descripcion, precio, categoria, imagen) VALUES (?, ?, ?, ?, ?, ?)");
    $insert->bind_param('issdss', $restaurant_id, $nombre, $descripcion, $precio, $categoria, $imagen);
    if ($insert->execute()) {
        echo json_encode(['success' => true, 'message' => 'Producto agregado']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al agregar producto']);
    }
    $insert->close();

} elseif ($action === 'edit_product' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = floatval($_POST['precio'] ?? 0);
    $categoria = trim($_POST['categoria'] ?? '');
    $imagen = trim($_POST['imagen'] ?? '');

    if (empty($nombre) || empty($descripcion) || $precio <= 0 || empty($categoria)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
        exit;
    }

    // Verify product belongs to this restaurant
    $check = $conn->prepare("SELECT id FROM productos WHERE id = ? AND restaurante_id = ?");
    $check->bind_param('ii', $product_id, $restaurant_id);
    $check->execute();
    if ($check->get_result()->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
        exit;
    }
    $check->close();

    // Update product
    $update = $conn->prepare("UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, categoria = ?, imagen = ? WHERE id = ? AND restaurante_id = ?");
    $update->bind_param('ssdssii', $nombre, $descripcion, $precio, $categoria, $imagen, $product_id, $restaurant_id);
    if ($update->execute()) {
        echo json_encode(['success' => true, 'message' => 'Producto actualizado']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar producto']);
    }
    $update->close();

} elseif ($action === 'get_product' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $product_id = intval($_GET['product_id'] ?? 0);

    // Get product data
    $query = $conn->prepare("SELECT * FROM productos WHERE id = ? AND restaurante_id = ?");
    $query->bind_param('ii', $product_id, $restaurant_id);
    $query->execute();
    $result = $query->get_result();
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        echo json_encode(['success' => true, 'product' => $product]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
    }
    $query->close();

} elseif ($action === 'get_restaurant_images' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get restaurant images
    $main_image = null;
    $gallery_images = [];

    // Get main image
    $query = $conn->prepare("SELECT imagen FROM restaurantes WHERE id = ?");
    $query->bind_param('i', $restaurant_id);
    $query->execute();
    $result = $query->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $main_image = $row['imagen'];
    }
    $query->close();

    // Get gallery images
    $query = $conn->prepare("SELECT id, url_imagen, orden FROM imagenes_restaurante WHERE restaurante_id = ? ORDER BY orden ASC, fecha_subida DESC");
    $query->bind_param('i', $restaurant_id);
    $query->execute();
    $result = $query->get_result();
    while ($row = $result->fetch_assoc()) {
        $gallery_images[] = $row;
    }
    $query->close();

    echo json_encode([
        'success' => true,
        'main_image' => $main_image,
        'gallery_images' => $gallery_images
    ]);

} elseif ($action === 'upload_restaurant_image' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $image_url = $_POST['image_url'] ?? '';

    if (empty($image_url) && !isset($_FILES['image'])) {
        echo json_encode(['success' => false, 'message' => 'No se proporcionó imagen']);
        exit;
    }

    // Handle file upload
    if (isset($_FILES['image'])) {
        $file = $_FILES['image'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Error al subir archivo']);
            exit;
        }

        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido']);
            exit;
        }

        // Validate file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Archivo demasiado grande (máximo 5MB)']);
            exit;
        }

        // Create uploads directory if it doesn't exist
        $upload_dir = '../uploads/restaurant_images/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('restaurant_' . $restaurant_id . '_') . '.' . $extension;
        $filepath = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $image_url = 'uploads/restaurant_images/' . $filename;
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar archivo']);
            exit;
        }
    }

    if ($type === 'main') {
        // Update main image
        $query = $conn->prepare("UPDATE restaurantes SET imagen = ? WHERE id = ?");
        $query->bind_param('si', $image_url, $restaurant_id);
        if ($query->execute()) {
            echo json_encode(['success' => true, 'message' => 'Imagen principal actualizada', 'image_url' => $image_url]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar imagen principal']);
        }
        $query->close();
    } elseif ($type === 'gallery') {
        // Add to gallery
        $query = $conn->prepare("INSERT INTO imagenes_restaurante (restaurante_id, url_imagen, tipo) VALUES (?, ?, 'galeria')");
        $query->bind_param('is', $restaurant_id, $image_url);
        if ($query->execute()) {
            echo json_encode(['success' => true, 'message' => 'Imagen agregada a la galería', 'image_url' => $image_url]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al agregar imagen a la galería']);
        }
        $query->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Tipo de imagen no válido']);
    }

} elseif ($action === 'delete_restaurant_image' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $image_id = intval($_POST['image_id'] ?? 0);

    if ($type === 'main') {
        // Delete main image
        $query = $conn->prepare("UPDATE restaurantes SET imagen = NULL WHERE id = ?");
        $query->bind_param('i', $restaurant_id);
        if ($query->execute()) {
            echo json_encode(['success' => true, 'message' => 'Imagen principal eliminada']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar imagen principal']);
        }
        $query->close();
    } elseif ($type === 'gallery' && $image_id > 0) {
        // Delete gallery image
        $query = $conn->prepare("SELECT url_imagen FROM imagenes_restaurante WHERE id = ? AND restaurante_id = ?");
        $query->bind_param('ii', $image_id, $restaurant_id);
        $query->execute();
        $result = $query->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $image_path = '../' . $row['url_imagen'];

            // Delete from database
            $delete = $conn->prepare("DELETE FROM imagenes_restaurante WHERE id = ? AND restaurante_id = ?");
            $delete->bind_param('ii', $image_id, $restaurant_id);
            if ($delete->execute()) {
                // Delete physical file if it exists
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
                echo json_encode(['success' => true, 'message' => 'Imagen eliminada de la galería']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar imagen de la galería']);
            }
            $delete->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Imagen no encontrada']);
        }
        $query->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
    }

} elseif ($action === 'upload_product_image' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $image_url = $_POST['image_url'] ?? '';

    if (empty($image_url) && !isset($_FILES['image'])) {
        echo json_encode(['success' => false, 'message' => 'No se proporcionó imagen']);
        exit;
    }

    // Handle file upload
    if (isset($_FILES['image'])) {
        $file = $_FILES['image'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Error al subir archivo']);
            exit;
        }

        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido']);
            exit;
        }

        // Validate file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Archivo demasiado grande (máximo 5MB)']);
            exit;
        }

        // Create uploads directory if it doesn't exist
        $upload_dir = '../uploads/product_images/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('product_' . $restaurant_id . '_') . '.' . $extension;
        $filepath = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $image_url = 'uploads/product_images/' . $filename;
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar archivo']);
            exit;
        }
    }

    echo json_encode(['success' => true, 'message' => 'Imagen subida correctamente', 'image_url' => $image_url]);

} else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}

$conn->close();
?>
