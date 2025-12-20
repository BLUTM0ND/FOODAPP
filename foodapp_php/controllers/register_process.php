<?php
include '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validación básica de entrada
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $telefono = trim($_POST['telefono']);
    $tipo = $_POST['tipo'];

    // Validar que los campos requeridos no estén vacíos
    if (empty($nombre) || empty($email) || empty($password) || empty($tipo)) {
        echo "Error: Todos los campos son requeridos.";
        exit;
    }

    // Validar formato de email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Error: Formato de email inválido.";
        exit;
    }

    // Validar tipo de usuario
    $tipos_validos = ['cliente', 'repartidor', 'restaurante', 'admin'];
    if (!in_array($tipo, $tipos_validos)) {
        echo "Error: Tipo de usuario inválido.";
        exit;
    }

    // Hash de la contraseña
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Usar prepared statements para prevenir SQL injection
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, contrasena, telefono, tipo) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $nombre, $email, $hashed_password, $telefono, $tipo);

    if ($stmt->execute()) {
        $user_id = $conn->insert_id;

        // Insertar en tabla específica según el tipo
        if ($tipo == 'cliente') {
            $conn->query("INSERT INTO clientes (id) VALUES ($user_id)");
        } elseif ($tipo == 'repartidor') {
            $conn->query("INSERT INTO repartidores (id) VALUES ($user_id)");
        }

        echo "Registro exitoso. <a href='../views/login.php'>Iniciar Sesión</a>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
