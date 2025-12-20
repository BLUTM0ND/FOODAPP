<?php
session_start();
include_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/forgot_password.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
if ($email === '') {
    header('Location: ../views/forgot_password.php?error=1');
    exit;
}

// Find user
$stmt = $conn->prepare("SELECT id, nombre FROM usuarios WHERE email = ? LIMIT 1");
if (!$stmt) {
    die('Error en consulta.');
}
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    // Do not reveal whether email exists — show generic message
    echo "<p>Si existe una cuenta asociada a ese correo, recibirás un enlace de recuperación (simulado).</p>";
    echo "<p><a href='../views/login.php'>Volver al login</a></p>";
    exit;
}
$user = $res->fetch_assoc();
$user_id = intval($user['id']);

// Ensure password_resets table exists (simple migration)
$create = "CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(128) NOT NULL,
    expires TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(token(64)),
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($create);

// Generate token
$token = bin2hex(random_bytes(16));
$expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

// Store token
$ins = $conn->prepare("INSERT INTO password_resets (user_id, token, expires) VALUES (?, ?, ?)");
$ins->bind_param('iss', $user_id, $token, $expires);
$ins->execute();
$ins->close();

// In production: send email to user with reset link. For now, show a simulated link for development.
$reset_link = sprintf('http://localhost/FOODAPP/foodapp_php/views/reset_password.php?token=%s', $token);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Enlace enviado - FoodApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php include '../views/header.php'; ?>
    <main class="container d-flex justify-content-center align-items-center" style="min-height:60vh;">
        <div class="card p-4" style="max-width:720px; width:100%;">
            <h3>Enlace de recuperación creado (simulado)</h3>
            <p>Se ha generado un enlace de restablecimiento de contraseña. En un entorno real se enviaría por correo al usuario.</p>
            <p><strong>Enlace (solo desarrollo):</strong></p>
            <pre><a href="<?php echo htmlspecialchars($reset_link); ?>"><?php echo htmlspecialchars($reset_link); ?></a></pre>
            <p class="mt-3"><a href="../views/login.php" class="btn btn-secondary">Volver al login</a></p>
        </div>
    </main>
</body>
</html>
