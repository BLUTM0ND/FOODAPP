<?php
session_start();
include '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../views/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$nombre = $_POST['nombre'];
$descripcion = $_POST['descripcion'];
$precio = $_POST['precio'];
$stock = $_POST['stock'];

if ($user_type == 'admin') {
    $restaurante_id = $_POST['restaurante_id'];
} else {
    $sql = "SELECT id FROM restaurantes WHERE usuario_id = $user_id";
    $result = $conn->query($sql);
    $rest = $result->fetch_assoc();
    $restaurante_id = $rest['id'];
}

$sql = "INSERT INTO productos (nombre, descripcion, precio, stock, restaurante_id) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssdii", $nombre, $descripcion, $precio, $stock, $restaurante_id);

if ($stmt->execute()) {
    if ($user_type == 'admin') {
        header("Location: ../views/admin_data.php?success=Producto agregado");
    } else {
        header("Location: ../views/restaurant_panel.php");
    }
} else {
    if ($user_type == 'admin') {
        header("Location: ../views/admin_data.php?error=Error al agregar producto");
    } else {
        echo "Error: " . $conn->error;
    }
}
$stmt->close();
$conn->close();
?>
