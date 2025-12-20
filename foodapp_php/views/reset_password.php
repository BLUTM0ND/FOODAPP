<?php
session_start();
include_once '../includes/config.php';
$token = $_GET['token'] ?? '';
$token = trim($token);
$valid = false;
$user_id = null;
if ($token !== '') {
    $stmt = $conn->prepare("SELECT user_id, expires FROM password_resets WHERE token = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $expires = strtotime($row['expires']);
            if ($expires > time()) {
                $valid = true;
                $user_id = intval($row['user_id']);
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Restablecer contraseña - FoodApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <main class="container d-flex justify-content-center align-items-center" style="min-height:60vh;">
        <div class="card p-4" style="max-width:420px; width:100%;">
            <h2 class="text-center mb-4">Restablecer contraseña</h2>
            <?php if (!$valid): ?>
                <div class="alert alert-danger">El enlace de restablecimiento no es válido o ha expirado.</div>
                <p class="text-center"><a href="forgot_password.php">Solicitar otro enlace</a></p>
            <?php else: ?>
                <form action="../controllers/reset_password_process.php" method="post">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <div class="mb-3">
                        <label for="password" class="form-label">Nueva contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label for="password2" class="form-label">Repetir contraseña</label>
                        <input type="password" class="form-control" id="password2" name="password2" required minlength="6">
                    </div>
                    <button class="btn btn-custom w-100" type="submit">Restablecer contraseña</button>
                </form>
            <?php endif; ?>
            <p class="text-center mt-3"><a href="login.php">Volver al inicio de sesión</a></p>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
