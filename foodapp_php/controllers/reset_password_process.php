<?php
session_start();
include_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/login.php');
    exit;
}

$token = trim($_POST['token'] ?? '');
$pass = $_POST['password'] ?? '';
$pass2 = $_POST['password2'] ?? '';

if ($token === '' || $pass === '' || $pass !== $pass2) {
    echo "<p>Datos inválidos. Asegúrate de que las contraseñas coincidan.</p>";
    echo "<p><a href='../views/login.php'>Volver al login</a></p>";
    exit;
}

// Find token
$stmt = $conn->prepare("SELECT user_id, expires FROM password_resets WHERE token = ? LIMIT 1");
if (!$stmt) { die('Error en consulta.'); }
$stmt->bind_param('s', $token);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    echo "<p>Token inválido o expirado.</p>";
    echo "<p><a href='../views/forgot_password.php'>Solicitar otro enlace</a></p>";
    exit;
}
$row = $res->fetch_assoc();
if (strtotime($row['expires']) < time()) {
    echo "<p>Token expirado.</p>";
    echo "<p><a href='../views/forgot_password.php'>Solicitar otro enlace</a></p>";
    exit;
}
$user_id = intval($row['user_id']);
$stmt->close();

// Update password
$hash = password_hash($pass, PASSWORD_DEFAULT);
$upd = $conn->prepare("UPDATE usuarios SET contrasena = ? WHERE id = ?");
$upd->bind_param('si', $hash, $user_id);
if (!$upd->execute()) {
    echo "<p>Error al actualizar la contraseña.</p>";
    exit;
}
$upd->close();

// Delete used token
$del = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
$del->bind_param('s', $token);
$del->execute();
$del->close();

// Success
header('Location: ../views/login.php?reset=1');
exit;
