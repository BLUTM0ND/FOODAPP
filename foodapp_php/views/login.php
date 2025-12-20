<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - FoodApp</title>
    <link rel="icon" href="../assets/FOODAPP.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f9fa;
            overflow-x: hidden;
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            position: relative;
        }

        .login-left {
            flex: 1;
            background: linear-gradient(135deg, #ff441f 0%, #ff7d00 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .login-left::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.1)"/><circle cx="90" cy="40" r="0.5" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .welcome-content {
            text-align: center;
            color: white;
            position: relative;
            z-index: 2;
            max-width: 400px;
        }

        .welcome-content img {
            width: 80px;
            height: 80px;
            margin-bottom: 2rem;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
        }

        .welcome-content h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .welcome-content p {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.6;
        }

        .login-right {
            flex: 1;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            min-width: 400px;
        }

        .login-form-container {
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h2 {
            color: #333;
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
        }

        .login-header p {
            color: #666;
            font-size: 0.95rem;
        }

        .form-floating {
            margin-bottom: 1rem;
        }

        .form-floating > label {
            padding: 1rem 0.75rem;
            color: #666;
            font-weight: 500;
        }

        .form-control {
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            padding: 1rem 0.75rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #ff441f;
            box-shadow: 0 0 0 0.2rem rgba(255, 68, 31, 0.1);
        }

        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e1e5e9;
            border-right: none;
            color: #666;
            border-radius: 12px 0 0 12px;
        }

        .form-control:focus + .input-group-text,
        .input-group .form-control:focus ~ .input-group-text {
            border-color: #ff441f;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 1.5rem 0;
        }

        .form-check {
            margin-bottom: 0;
        }

        .form-check-input {
            width: 18px;
            height: 18px;
            border: 2px solid #e1e5e9;
            border-radius: 4px;
            margin-right: 0.5rem;
        }

        .form-check-input:checked {
            background-color: #ff441f;
            border-color: #ff441f;
        }

        .form-check-label {
            color: #666;
            font-size: 0.9rem;
            cursor: pointer;
        }

        .forgot-link {
            color: #ff441f;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .forgot-link:hover {
            color: #e63946;
            text-decoration: underline;
        }

        .alert {
            border-radius: 12px;
            border: none;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee, #fdd);
            color: #c53030;
        }

        .alert-success {
            background: linear-gradient(135deg, #efe, #dfd);
            color: #22543d;
        }

        .btn-login {
            background: linear-gradient(135deg, #ff441f 0%, #ff7d00 100%);
            border: none;
            border-radius: 12px;
            padding: 0.875rem 1.5rem;
            font-weight: 600;
            font-size: 1rem;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 68, 31, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 68, 31, 0.4);
            background: linear-gradient(135deg, #e63946 0%, #ff6600 100%);
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 2rem 0;
            color: #666;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e1e5e9;
        }

        .divider::before {
            margin-right: 1rem;
        }

        .divider::after {
            margin-left: 1rem;
        }

        .register-link {
            text-align: center;
            margin-top: 2rem;
            color: #666;
            font-size: 0.95rem;
        }

        .register-link a {
            color: #ff441f;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .register-link a:hover {
            color: #e63946;
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }

            .login-left {
                min-height: 300px;
                padding: 3rem 1rem;
            }

            .welcome-content h1 {
                font-size: 2.5rem;
            }

            .login-right {
                padding: 2rem 1rem;
            }
        }

        @media (max-width: 480px) {
            .welcome-content h1 {
                font-size: 2rem;
            }

            .welcome-content p {
                font-size: 1rem;
            }

            .login-form-container {
                max-width: 100%;
            }
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-form-container {
            animation: fadeInUp 0.6s ease-out;
        }

        .welcome-content {
            animation: fadeInUp 0.8s ease-out 0.2s both;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left Side - Welcome -->
        <div class="login-left">
            <div class="welcome-content">
                <img src="../assets/FOODAPP.png" alt="FoodApp Logo">
                <h1>¡Bienvenido!</h1>
                <p>Descubre los mejores restaurantes cerca de ti y disfruta de una experiencia culinaria excepcional.</p>
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="login-right">
            <div class="login-form-container">
                <div class="login-header">
                    <h2>Iniciar Sesión</h2>
                    <p>Ingresa tus credenciales para continuar</p>
                </div>

                <?php
                // Display error messages
                if (isset($_GET['error'])) {
                    $error_message = '';
                    $error_icon = 'exclamation-triangle';
                    $error_class = 'danger';

                    switch ($_GET['error']) {
                        case 'invalid_credentials':
                            $error_message = 'Credenciales incorrectas. Por favor verifica tu email y contraseña.';
                            break;
                        case 'empty_fields':
                            $error_message = 'Por favor ingresa tu email y contraseña.';
                            $error_icon = 'exclamation-circle';
                            break;
                        case 'account_disabled':
                            $error_message = 'Tu cuenta ha sido desactivada. Contacta al soporte.';
                            break;
                        case 'server_error':
                            $error_message = 'Error del servidor. Inténtalo de nuevo más tarde.';
                            $error_icon = 'server';
                            break;
                        default:
                            $error_message = 'Ha ocurrido un error. Inténtalo de nuevo.';
                    }

                    echo '<div class="alert alert-' . $error_class . ' alert-dismissible fade show" role="alert">
                        <i class="fas fa-' . $error_icon . ' me-2"></i>' . htmlspecialchars($error_message) . '
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
                }

                // Display success messages
                if (isset($_GET['success'])) {
                    $success_message = '';
                    switch ($_GET['success']) {
                        case 'registered':
                            $success_message = '¡Cuenta creada exitosamente! Ahora puedes iniciar sesión.';
                            break;
                        case 'password_reset':
                            $success_message = 'Contraseña restablecida exitosamente.';
                            break;
                        default:
                            $success_message = 'Operación completada exitosamente.';
                    }

                    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>' . htmlspecialchars($success_message) . '
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
                }
                ?>

                <form action="../controllers/login_process.php" method="post">
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="email" name="email" placeholder="tu@email.com" required>
                        <label for="email">
                            <i class="fas fa-envelope me-2"></i>Email
                        </label>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Tu contraseña" required>
                        <label for="password">
                            <i class="fas fa-lock me-2"></i>Contraseña
                        </label>
                    </div>

                    <div class="form-options">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember" name="remember">
                            <label class="form-check-label" for="remember">
                                Recordarme
                            </label>
                        </div>
                        <a href="forgot_password.php" class="forgot-link">¿Olvidaste tu contraseña?</a>
                    </div>

                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                    </button>
                </form>

                <div class="register-link">
                    <p>¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Focus on email field when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const emailField = document.getElementById('email');
            if (emailField) {
                emailField.focus();
            }

            // If there's an error, don't clear the email field
            <?php if (!isset($_GET['error'])): ?>
                // Clear password field on page load (but keep email if there was an error)
                const passwordField = document.getElementById('password');
                if (passwordField) {
                    passwordField.value = '';
                }
            <?php endif; ?>
        });

        // Add loading state to button on form submit
        const loginForm = document.querySelector('form');
        const loginButton = document.querySelector('.btn-login');

        if (loginForm && loginButton) {
            loginForm.addEventListener('submit', function() {
                loginButton.disabled = true;
                loginButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Iniciando sesión...';
            });
        }

        // Auto-hide alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    </script>
</body>
</html>
