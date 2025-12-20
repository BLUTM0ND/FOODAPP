<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrarse - FoodApp</title>
    <link rel="icon" href="../assets/FOODAPP.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
            --secondary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --shadow-light: 0 4px 20px rgba(255, 107, 53, 0.1);
            --shadow-medium: 0 8px 32px rgba(255, 107, 53, 0.15);
            --shadow-heavy: 0 16px 48px rgba(255, 107, 53, 0.2);
            --border-radius: 20px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .navbar-brand {
            color: #ff6b35 !important;
            font-weight: 800;
            font-size: 1.5rem;
        }

        .main-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            position: relative;
        }

        .main-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="rgba(255,107,53,0.1)"/><circle cx="80" cy="80" r="2" fill="rgba(255,107,53,0.1)"/><circle cx="40" cy="70" r="1" fill="rgba(255,107,53,0.1)"/><circle cx="60" cy="30" r="1.5" fill="rgba(255,107,53,0.1)"/></svg>');
            opacity: 0.5;
            pointer-events: none;
        }

        .register-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-heavy);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            max-width: 480px;
            width: 100%;
            transition: var(--transition);
        }

        .register-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 60px rgba(255, 107, 53, 0.25);
        }

        .register-header {
            background: var(--primary-gradient);
            color: white;
            padding: 2rem 2rem 1.5rem;
            text-align: center;
            position: relative;
        }

        .register-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path d="M0,0 L100,0 L100,100 L0,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
        }

        .register-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .register-header p {
            font-size: 0.95rem;
            opacity: 0.9;
            margin-bottom: 0;
            position: relative;
            z-index: 1;
        }

        .register-body {
            padding: 2rem;
        }

        .form-floating {
            margin-bottom: 1.5rem;
        }

        .form-floating > label {
            color: #6c757d;
            font-weight: 500;
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: var(--transition);
            background: rgba(255, 255, 255, 0.8);
        }

        .form-control:focus, .form-select:focus {
            border-color: #ff6b35;
            box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.15);
            background: white;
            transform: translateY(-1px);
        }

        .input-group-text {
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 12px 0 0 12px;
        }

        .btn-register {
            background: var(--primary-gradient);
            border: none;
            border-radius: 12px;
            padding: 0.875rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            color: white;
            width: 100%;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .btn-register::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn-register:hover::before {
            left: 100%;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e9ecef;
        }

        .divider::before { margin-right: 1rem; }
        .divider::after { margin-left: 1rem; }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #6c757d;
        }

        .login-link a {
            color: #ff6b35;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .login-link a:hover {
            color: #e63946;
            text-decoration: underline;
        }

        .benefits-section {
            margin-top: 2rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, rgba(255,107,53,0.05) 0%, rgba(247,147,30,0.05) 100%);
            border-radius: 16px;
            border: 1px solid rgba(255,107,53,0.1);
        }

        .benefit-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
            color: #495057;
        }

        .benefit-item i {
            color: #ff6b35;
            margin-right: 0.75rem;
            font-size: 1rem;
        }

        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.8rem;
        }

        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }

        @media (max-width: 576px) {
            .register-card {
                margin: 1rem;
                max-width: none;
            }

            .register-header {
                padding: 1.5rem 1.5rem 1rem;
            }

            .register-header h1 {
                font-size: 1.75rem;
            }

            .register-body {
                padding: 1.5rem;
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .floating-shapes {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            overflow: hidden;
            z-index: -1;
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            background: var(--primary-gradient);
            opacity: 0.05;
            animation: float 6s ease-in-out infinite;
        }

        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 60px;
            height: 60px;
            top: 20%;
            right: 10%;
            animation-delay: 2s;
        }

        .shape:nth-child(3) {
            width: 100px;
            height: 100px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <img src="../assets/FOODAPP.png" alt="FoodApp Logo" height="40" class="me-2">
                <span>FoodApp</span>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../index.php">Inicio</a>
                <a class="nav-link" href="login.php">Iniciar Sesión</a>
                <a class="nav-link" href="search.php">Buscar</a>
            </div>
        </div>
    </nav>

    <main class="main-container">
        <div class="floating-shapes">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div>

        <div class="register-card animate-fade-in">
            <div class="register-header">
                <h1><i class="fas fa-utensils me-2"></i>Únete a FoodApp</h1>
                <p>Crea tu cuenta y disfruta de la mejor comida a domicilio</p>
            </div>

            <div class="register-body">
                <form action="../controllers/register_process.php" method="post" id="registerForm">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Nombre Completo" required>
                        <label for="nombre"><i class="fas fa-user me-2"></i>Nombre Completo</label>
                    </div>

                    <div class="form-floating">
                        <input type="email" class="form-control" id="email" name="email" placeholder="tu@email.com" required>
                        <label for="email"><i class="fas fa-envelope me-2"></i>Email</label>
                    </div>

                    <div class="form-floating">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" minlength="6" required>
                        <label for="password"><i class="fas fa-lock me-2"></i>Contraseña</label>
                        <div id="passwordStrength" class="password-strength" style="display: none;"></div>
                    </div>

                    <div class="form-floating">
                        <input type="text" class="form-control" id="telefono" name="telefono" placeholder="+51 999 999 999">
                        <label for="telefono"><i class="fas fa-phone me-2"></i>Teléfono (opcional)</label>
                    </div>

                    <div class="form-floating">
                        <select class="form-select" id="tipo" name="tipo" required>
                            <option value="cliente">🍽️ Cliente - Pide comida deliciosa</option>
                            <option value="repartidor">🚴 Repartidor - Gana dinero entregando</option>
                            <option value="restaurante">🏪 Restaurante - Vende tus platos</option>
                        </select>
                        <label for="tipo"><i class="fas fa-users me-2"></i>Tipo de Cuenta</label>
                    </div>

                    <button type="submit" class="btn btn-register">
                        <i class="fas fa-rocket me-2"></i>Crear Mi Cuenta
                    </button>
                </form>

                <div class="divider">o</div>

                <p class="login-link">
                    ¿Ya tienes cuenta?
                    <a href="login.php">
                        <i class="fas fa-sign-in-alt me-1"></i>Inicia Sesión
                    </a>
                </p>

                <div class="benefits-section">
                    <h6 class="text-center mb-3" style="color: #ff6b35; font-weight: 600;">
                        <i class="fas fa-star me-1"></i>¿Por qué registrarte?
                    </h6>
                    <div class="benefit-item">
                        <i class="fas fa-clock"></i>
                        <span>Entregas rápidas en menos de 30 minutos</span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-utensils"></i>
                        <span>Miles de restaurantes con comida deliciosa</span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-map-marked-alt"></i>
                        <span>Seguimiento GPS en tiempo real</span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>Pagos seguros y datos protegidos</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-white py-4 text-center border-top mt-5">
        <div class="container">
            <p class="text-muted mb-0">
                <i class="fas fa-heart text-danger me-1"></i>
                © 2025 FoodApp. Todos los derechos reservados.
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthIndicator = document.getElementById('passwordStrength');
            let strength = 0;
            let feedback = [];

            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/\d/)) strength++;
            if (password.match(/[^a-zA-Z\d]/)) strength++;

            if (password.length > 0) {
                strengthIndicator.style.display = 'block';
                if (strength <= 1) {
                    strengthIndicator.className = 'password-strength strength-weak';
                    strengthIndicator.textContent = 'Contraseña débil';
                } else if (strength <= 3) {
                    strengthIndicator.className = 'password-strength strength-medium';
                    strengthIndicator.textContent = 'Contraseña media';
                } else {
                    strengthIndicator.className = 'password-strength strength-strong';
                    strengthIndicator.textContent = 'Contraseña fuerte';
                }
            } else {
                strengthIndicator.style.display = 'none';
            }
        });

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            if (password.length < 6) {
                e.preventDefault();
                alert('La contraseña debe tener al menos 6 caracteres.');
                return false;
            }
        });

        // Animate elements on load
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.animate-fade-in');
            elements.forEach((element, index) => {
                element.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>
