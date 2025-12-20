<?php
session_start();
include '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $direccion = $_POST['direccion'];
    $telefono = $_POST['telefono'];
    // Optionally create a user for the restaurante if email and password provided
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $usuario_id = null;

    if ($email !== '' && $password !== '') {
        // create usuario with tipo 'restaurante'
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmtu = $conn->prepare("INSERT INTO usuarios (nombre, email, contrasena, telefono, tipo) VALUES (?, ?, ?, ?, 'restaurante')");
        if ($stmtu) {
            $stmtu->bind_param('ssss', $nombre, $email, $hash, $telefono);
            if ($stmtu->execute()) {
                $usuario_id = $stmtu->insert_id;
            }
            $stmtu->close();
        }
    }

    if ($usuario_id === null) {
        $sql = "INSERT INTO restaurantes (nombre, direccion) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            header("Location: ../views/admin_data.php?error=Error en preparación");
            exit;
        }
        $stmt->bind_param("ss", $nombre, $direccion);
    } else {
        $sql = "INSERT INTO restaurantes (nombre, direccion, usuario_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            header("Location: ../views/admin_data.php?error=Error en preparación");
            exit;
        }
        $stmt->bind_param("ssi", $nombre, $direccion, $usuario_id);
    }

    if ($stmt->execute()) {
        header("Location: ../views/admin_data.php?success=Restaurante agregado");
    } else {
        header("Location: ../views/admin_data.php?error=Error al agregar restaurante");
    }
    $stmt->close();
}
$conn->close();
?>
