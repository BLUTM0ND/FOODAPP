<?php
session_start();
include_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate input
    if (empty($email) || empty($password)) {
        header("Location: ../views/login.php?error=empty_fields");
        exit;
    }

    // Quick debug/admin shortcut (keep for dev; remove in production)
    if ($email === 'admin@foodapp.com' && $password === 'password') {
        $_SESSION['user_id'] = 6; // admin id in sample data
        $_SESSION['user_type'] = 'admin';
        $_SESSION['tipo'] = 'admin';
        $_SESSION['user_name'] = 'Admin';
        header("Location: /FOODAPP/foodapp_php/views/admin_data.php");
        exit;
    }

    // Use prepared statement to avoid SQL injection
    $stmt = $conn->prepare("SELECT id, nombre, contrasena, tipo FROM usuarios WHERE email = ? LIMIT 1");
    if ($stmt === false) {
        header("Location: ../views/login.php?error=server_error");
        exit;
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['contrasena'])) {
            // Normalize session keys for the whole app
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['tipo'];
            $_SESSION['tipo'] = $user['tipo'];
            $_SESSION['user_name'] = $user['nombre'];

            // Redirect based on role
            switch ($user['tipo']) {
                case 'admin':
                    header("Location: /FOODAPP/foodapp_php/views/admin_data.php");
                    break;
                case 'cliente':
                    header("Location: /FOODAPP/foodapp_php/views/profile.php");
                    break;
                case 'restaurante':
                    header("Location: /FOODAPP/foodapp_php/views/restaurant_panel.php");
                    break;
                case 'repartidor':
                    header("Location: /FOODAPP/foodapp_php/views/delivery_panel.php");
                    break;
                default:
                    header("Location: /FOODAPP/foodapp_php/index.php");
            }
            exit;
        } else {
            header("Location: ../views/login.php?error=invalid_credentials");
            exit;
        }
    } else {
        header("Location: ../views/login.php?error=invalid_credentials");
        exit;
    }
}
?>
