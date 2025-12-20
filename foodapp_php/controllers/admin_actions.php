<?php
session_start();
// Accept either `user_type` or `tipo` session keys for admin role
$notAdmin = true;
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') $notAdmin = false;
if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'admin') $notAdmin = false;
if (!isset($_SESSION['user_id']) || $notAdmin) {
    header('Location: ../views/login.php');
    exit;
}
include_once '../includes/config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Función para subir imágenes
function uploadImage($file, $folder) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 2 * 1024 * 1024; // 2MB

    if (!in_array($file['type'], $allowed_types)) {
        return false;
    }

    if ($file['size'] > $max_size) {
        return false;
    }

    $upload_dir = '../uploads/' . $folder . '/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $file_name = uniqid() . '_' . time() . '.' . $file_extension;
    $file_path = $upload_dir . $file_name;

    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        return 'uploads/' . $folder . '/' . $file_name;
    }

    return false;
}

if ($action == 'delete_user') {
    $id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header('Location: admin.php?section=users');
} elseif ($action == 'delete_product') {
    $id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header('Location: admin.php?section=products');
} elseif ($action == 'delete_restaurant') {
    $id = $_GET['id'];

    // Verificar si el restaurante tiene pedidos asociados
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM pedidos WHERE restaurante_id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    $check_stmt->close();

    if ($count > 0) {
        // No se puede eliminar porque tiene pedidos asociados
        header('Location: ../views/admin_restaurants.php?error=No se puede eliminar el restaurante porque tiene pedidos asociados');
        exit;
    }

    // Obtener el usuario_id del restaurante para eliminar también el usuario
    $user_stmt = $conn->prepare("SELECT usuario_id FROM restaurantes WHERE id = ?");
    $user_stmt->bind_param("i", $id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_row = $user_result->fetch_assoc();
    $usuario_id = $user_row['usuario_id'];
    $user_stmt->close();

    // Eliminar el restaurante
    $stmt = $conn->prepare("DELETE FROM restaurantes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Eliminar el usuario asociado si existe
    if ($usuario_id) {
        $user_delete = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
        $user_delete->bind_param("i", $usuario_id);
        $user_delete->execute();
        $user_delete->close();
    }

    header('Location: ../views/admin_restaurants.php?success=Restaurante eliminado correctamente');
} elseif ($action == 'add_product') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $nombre = $_POST['nombre'];
        $precio = $_POST['precio'];
        $categoria = $_POST['categoria'];
        $restaurante_id = $_POST['restaurante_id'];
        $descripcion = $_POST['descripcion'];
        $imagen = $_POST['imagen'];
        $disponible = isset($_POST['disponible']) ? 1 : 0;
        $stmt = $conn->prepare("INSERT INTO productos (nombre, precio, categoria, restaurante_id, descripcion, imagen, disponible) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sdsiisi", $nombre, $precio, $categoria, $restaurante_id, $descripcion, $imagen, $disponible);
        $stmt->execute();
        header('Location: ../views/admin_products.php');
    }
} elseif ($action == 'edit_product') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $id = $_POST['id'];
        $nombre = $_POST['nombre'];
        $precio = $_POST['precio'];
        $categoria = $_POST['categoria'];
        $restaurante_id = $_POST['restaurante_id'];
        $descripcion = $_POST['descripcion'];
        $disponible = isset($_POST['disponible']) ? 1 : 0;

        // Manejar subida de imagen
        $imagen_path = '';
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
            $imagen_path = uploadImage($_FILES['imagen'], 'product_images');
            if ($imagen_path === false) {
                header('Location: ../views/admin_products.php?error=Error al subir la imagen. Verifique que sea una imagen válida y no exceda 2MB');
                exit;
            }
        }

        // Actualizar producto
        if (!empty($imagen_path)) {
            $stmt = $conn->prepare("UPDATE productos SET nombre = ?, precio = ?, categoria = ?, restaurante_id = ?, descripcion = ?, imagen = ?, disponible = ? WHERE id = ?");
            $stmt->bind_param("sdsiisii", $nombre, $precio, $categoria, $restaurante_id, $descripcion, $imagen_path, $disponible, $id);
        } else {
            $stmt = $conn->prepare("UPDATE productos SET nombre = ?, precio = ?, categoria = ?, restaurante_id = ?, descripcion = ?, disponible = ? WHERE id = ?");
            $stmt->bind_param("sdsiisi", $nombre, $precio, $categoria, $restaurante_id, $descripcion, $disponible, $id);
        }
        $stmt->execute();
        header('Location: ../views/admin_products.php');
    }
} elseif ($action == 'add_restaurant') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $nombre = $_POST['nombre'];
        $direccion = $_POST['direccion'];
        $telefono = $_POST['telefono'];
        $password = $_POST['password'];
        $estado = $_POST['estado'];

        // Generar email automáticamente basado en el nombre del restaurante
        $email_base = $nombre;
        // Quitar "Restaurante " o "Restaurant " del inicio si existe
        if (stripos($email_base, 'Restaurante ') === 0) {
            $email_base = substr($email_base, strlen('Restaurante '));
        } elseif (stripos($email_base, 'Restaurant ') === 0) {
            $email_base = substr($email_base, strlen('Restaurant '));
        }
        $email_base = strtolower(str_replace(' ', '', $email_base)); // Quitar espacios y convertir a minúsculas
        $email_base = preg_replace('/[^a-z0-9]/', '', $email_base); // Quitar caracteres especiales
        $email = $email_base . '@foodapp.com';

        // Verificar si el email ya existe y agregar sufijo si es necesario
        $email_check = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $email_check->bind_param("s", $email);
        $email_check->execute();
        $email_check->store_result();
        $counter = 1;
        while ($email_check->num_rows > 0) {
            $email = $email_base . $counter . '@foodapp.com';
            $email_check->bind_param("s", $email);
            $email_check->execute();
            $email_check->store_result();
            $counter++;
        }
        $email_check->close();

        // Crear usuario primero
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt_user = $conn->prepare("INSERT INTO usuarios (nombre, email, contrasena, telefono, tipo) VALUES (?, ?, ?, ?, 'restaurante')");
        $stmt_user->bind_param("ssss", $nombre, $email, $hash, $telefono);
        $stmt_user->execute();
        $usuario_id = $stmt_user->insert_id;
        $stmt_user->close();

        // Crear restaurante con usuario_id (solo campos que existen en restaurantes)
        $stmt = $conn->prepare("INSERT INTO restaurantes (nombre, direccion, estado, usuario_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $nombre, $direccion, $estado, $usuario_id);
        $stmt->execute();
        $stmt->close();

        header('Location: ../views/admin_restaurants.php');
    }
} elseif ($action == 'edit_restaurant') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $id = $_POST['id'];
        $nombre = $_POST['nombre'];
        $direccion = $_POST['direccion'];
        $telefono = $_POST['telefono'];
        $email = $_POST['email'];
        $estado = $_POST['estado'];
        $password = $_POST['password'] ?? '';

        // Manejar subida de imagen
        $imagen_path = '';
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
            $imagen_path = uploadImage($_FILES['imagen'], 'restaurant_images');
            if ($imagen_path === false) {
                header('Location: ../views/admin_restaurants.php?error=Error al subir la imagen. Verifique que sea una imagen válida y no exceda 2MB');
                exit;
            }
        }

        // Actualizar información del restaurante
        if (!empty($imagen_path)) {
            $stmt = $conn->prepare("UPDATE restaurantes SET nombre = ?, direccion = ?, estado = ?, imagen = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $nombre, $direccion, $estado, $imagen_path, $id);
        } else {
            $stmt = $conn->prepare("UPDATE restaurantes SET nombre = ?, direccion = ?, estado = ? WHERE id = ?");
            $stmt->bind_param("sssi", $nombre, $direccion, $estado, $id);
        }
        $stmt->execute();
        $stmt->close();

        // Obtener el usuario_id del restaurante para actualizar la información del usuario
        $user_stmt = $conn->prepare("SELECT usuario_id FROM restaurantes WHERE id = ?");
        $user_stmt->bind_param("i", $id);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        $user_row = $user_result->fetch_assoc();
        $usuario_id = $user_row['usuario_id'];
        $user_stmt->close();

        if ($usuario_id) {
            // Construir la consulta dinámicamente basada en qué campos se van a actualizar
            $update_fields = ["nombre = ?"];
            $bind_types = "s";
            $bind_values = [$nombre];

            if (!empty($telefono)) {
                $update_fields[] = "telefono = ?";
                $bind_types .= "s";
                $bind_values[] = $telefono;
            }

            if (!empty($email)) {
                $update_fields[] = "email = ?";
                $bind_types .= "s";
                $bind_values[] = $email;
            }

            if (!empty($password)) {
                $update_fields[] = "contrasena = ?";
                $bind_types .= "s";
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $bind_values[] = $hashed_password;
            }

            $bind_types .= "i";
            $bind_values[] = $usuario_id;

            $update_query = "UPDATE usuarios SET " . implode(", ", $update_fields) . " WHERE id = ?";
            $user_update = $conn->prepare($update_query);

            // Crear array de referencias para bind_param
            $bind_refs = [];
            foreach ($bind_values as $key => $value) {
                $bind_refs[$key] = &$bind_values[$key];
            }
            array_unshift($bind_refs, $bind_types);

            call_user_func_array([$user_update, 'bind_param'], $bind_refs);
            $user_update->execute();
            $user_update->close();
        } else {
            // Si no hay usuario_id, crear nuevo usuario si se proporciona al menos email
            if (!empty($email)) {
                $hashed_password = password_hash($password ?: 'password123', PASSWORD_DEFAULT);
                $user_insert = $conn->prepare("INSERT INTO usuarios (nombre, email, contrasena, telefono, tipo) VALUES (?, ?, ?, ?, 'restaurante')");
                $user_insert->bind_param("ssss", $nombre, $email, $hashed_password, $telefono);
                $user_insert->execute();
                $new_usuario_id = $user_insert->insert_id;
                $user_insert->close();

                // Actualizar restaurante con el nuevo usuario_id
                $update_rest = $conn->prepare("UPDATE restaurantes SET usuario_id = ? WHERE id = ?");
                $update_rest->bind_param("ii", $new_usuario_id, $id);
                $update_rest->execute();
                $update_rest->close();
            }
        }

        header('Location: ../views/admin_restaurants.php?success=updated');
    }
} elseif ($action == 'change_status') {
    $id = $_GET['id'];
    $status = $_GET['status'];
    $stmt = $conn->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    header('Location: ../views/admin_orders.php');
}
elseif ($action == 'update_user') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $user_id = $_POST['user_id'] ?? '';
        $nombre = $_POST['nombre'] ?? '';
        $email = $_POST['email'] ?? '';
        $telefono = $_POST['telefono'] ?? '';
        $tipo = $_POST['tipo'] ?? '';
        $password = $_POST['password'] ?? '';
        $calificacion = !empty($_POST['calificacion']) ? floatval($_POST['calificacion']) : null;

        // Debug logging
        error_log("Update User Debug: ID=$user_id, Nombre=$nombre, Email=$email, Tipo=$tipo, Password=" . (!empty($password) ? 'provided' : 'empty') . ", Calificacion=" . ($calificacion ?? 'null'));

        if (!empty($user_id) && !empty($nombre) && !empty($email) && !empty($tipo)) {
            // First, update basic user information in usuarios table
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, email = ?, telefono = ?, contrasena = ?, tipo = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $nombre, $email, $telefono, $hash, $tipo, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, email = ?, telefono = ?, tipo = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $nombre, $email, $telefono, $tipo, $user_id);
            }

            if ($stmt->execute()) {
                // If user is a restaurant and calificacion is provided, update restaurant rating
                if ($tipo === 'restaurante' && $calificacion !== null) {
                    $stmt_rest = $conn->prepare("UPDATE restaurantes SET calificacion = ? WHERE usuario_id = ?");
                    $stmt_rest->bind_param("di", $calificacion, $user_id);
                    $stmt_rest->execute();
                    $stmt_rest->close();
                }

                error_log("Update User Success: User $user_id updated successfully");
                header('Location: ../views/admin_users.php?success=1');
            } else {
                error_log("Update User Error: " . $stmt->error);
                header('Location: ../views/admin_users.php?error=1');
            }
            $stmt->close();
        } else {
            error_log("Update User Error: Missing required fields");
            header('Location: ../views/admin_users.php?error=1');
        }
    }
}
elseif ($action == 'add_repartidor') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $nombre = $_POST['nombre'] ?? '';
        $email = $_POST['email'] ?? '';
        $telefono = $_POST['telefono'] ?? '';
        $vehiculo = $_POST['vehiculo'] ?? '';
        $licencia = $_POST['licencia'] ?? '';
        $zona = $_POST['zona'] ?? '';
        $password = $_POST['password'] ?? '';

        // Insert into usuarios
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, contrasena, telefono, tipo) VALUES (?, ?, ?, ?, 'repartidor')");
        if ($stmt) {
            $stmt->bind_param('ssss', $nombre, $email, $hash, $telefono);
            $stmt->execute();
            $new_id = $stmt->insert_id;
            $stmt->close();

            // Insert into repartidores table
            $stmt2 = $conn->prepare("INSERT INTO repartidores (id, vehiculo, licencia, zona, disponible) VALUES (?, ?, ?, ?, 1)");
            if ($stmt2) {
                $stmt2->bind_param('isss', $new_id, $vehiculo, $licencia, $zona);
                $stmt2->execute();
                $stmt2->close();
            }
        }
        header('Location: ../views/admin_repartidores.php');
        exit;
    }
}
?>
