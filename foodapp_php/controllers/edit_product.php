<?php
session_start();
include '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $precio = $_POST['precio'];
    $stock = $_POST['stock'];
    $restaurante_id = $_POST['restaurante_id'];

    $sql = "UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, stock = ?, restaurante_id = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdiii", $nombre, $descripcion, $precio, $stock, $restaurante_id, $id);

    if ($stmt->execute()) {
        header("Location: admin_data.php?success=Producto actualizado");
    } else {
        header("Location: admin_data.php?error=Error al actualizar producto");
    }
    $stmt->close();
}
$conn->close();
?>
