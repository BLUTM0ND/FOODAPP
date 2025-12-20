<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Recuperar contraseña - FoodApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <main class="container d-flex justify-content-center align-items-center" style="min-height:60vh;">
        <div class="card p-4" style="max-width:420px; width:100%;">
            <h2 class="text-center mb-4">Recuperar contraseña</h2>
            <p class="text-muted">Introduce tu correo y te enviaremos un enlace para restablecer tu contraseña.</p>
            <form action="../controllers/forgot_password_process.php" method="post">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="tu@email.com" required>
                </div>
                <button class="btn btn-custom w-100" type="submit">Enviar enlace de recuperación</button>
            </form>
            <p class="text-center mt-3"><a href="login.php">Volver al inicio de sesión</a></p>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
