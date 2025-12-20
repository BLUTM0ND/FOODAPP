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
    $direccion = $_POST['direccion'];
    $telefono = $_POST['telefono'];

    $sql = "UPDATE restaurantes SET nombre = ?, direccion = ?, telefono = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $nombre, $direccion, $telefono, $id);

    if ($stmt->execute()) {
        header("Location: ../views/admin_data.php?success=Restaurante actualizado");
    } else {
        header("Location: ../views/admin_data.php?error=Error al actualizar restaurante");
    }
    $stmt->close();
}
$conn->close();
?>
