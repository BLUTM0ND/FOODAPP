<?php
session_start();
include_once 'includes/config.php';
$user_logged_in = isset($_SESSION['user_id']);
$user_name = '';
$user_type = '';
if ($user_logged_in) {
    // Si es admin, redirigir al dashboard de admin
    if ($_SESSION['user_type'] == 'admin') {
        header("Location: /FOODAPP/foodapp_php/views/admin_data.php");
        exit;
    }
    // Obtener nombre del usuario si no está en sesión
    if (!isset($_SESSION['user_name'])) {
        $user_id = $_SESSION['user_id'];
        $sql = "SELECT nombre FROM usuarios WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $_SESSION['user_name'] = $user['nombre'];
        }
        $stmt->close();
    }
    $user_name = $_SESSION['user_name'];
    $user_type = $_SESSION['user_type'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoodApp - Pedidos de Comida</title>
    <link rel="icon" href="assets/FOODAPP.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .navbar-brand {
            color: #ff441f !important;
            font-weight: 800;
            transition: all 0.3s ease;
        }

        .navbar-brand:hover {
            transform: scale(1.05);
        }

        .btn-custom {
            background: linear-gradient(135deg, #ff441f 0%, #ff7d00 100%);
            border: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 68, 31, 0.3);
        }

        .btn-custom:hover {
            background: linear-gradient(135deg, #e63946 0%, #ff6b35 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 68, 31, 0.4);
        }

        /* HERO SECTION - MEJORADO AL MÁXIMO */
        .hero-section {
            min-height: 100vh;
            position: relative;
            display: flex;
            align-items: center;
            overflow: visible;
        }

        .hero-bg {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #FF7E8B 0%, #FF6B6B 25%, #FFA500 50%, #FF7E8B 75%, #FF5722 100%);
            background-size: 400% 400%;
            animation: gradientShift 8s ease-in-out infinite;
            z-index: -1;
        }

        .hero-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.05)"/><circle cx="10" cy="50" r="0.5" fill="rgba(255,255,255,0.05)"/><circle cx="90" cy="30" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .hero-bg::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float 20s ease-in-out infinite;
        }

        .hero-content {
            text-align: center;
            color: white;
            position: relative;
            z-index: 2;
            animation: fadeInUp 1s ease-out;
        }

        .hero-title {
            font-size: clamp(2.5rem, 5vw, 4.5rem);
            font-weight: 900;
            margin-bottom: 1.5rem;
            line-height: 1.1;
            text-shadow: 0 4px 20px rgba(0,0,0,0.3);
            animation: slideInFromLeft 1.2s ease-out 0.2s both;
            background: linear-gradient(135deg, #ffffff 0%, #ffeaa7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: clamp(1rem, 2.5vw, 1.4rem);
            margin-bottom: 3rem;
            opacity: 0.95;
            font-weight: 400;
            line-height: 1.6;
            animation: slideInFromRight 1.2s ease-out 0.4s both;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .search-box {
            max-width: 700px;
            margin: 0 auto 3rem;
            position: relative;
            z-index: 10000;
            animation: fadeInScale 1s ease-out 0.6s both;
        }

        .search-input-group {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 50px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255,255,255,0.1);
            backdrop-filter: blur(20px);
            transition: all 0.3s ease;
            display: flex;
            align-items: stretch;
        }

        .search-input-group:hover {
            transform: translateY(-2px);
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255,255,255,0.2);
        }

        .search-input {
            border: none;
            padding: 1.2rem 1.8rem;
            font-size: 1.2rem;
            background: transparent;
            flex: 1;
            font-weight: 500;
            border-radius: 50px 0 0 50px;
        }

        .search-input:focus {
            box-shadow: none;
            outline: none;
        }

        .search-input::placeholder {
            color: #666;
            font-weight: 400;
        }

        .btn-search {
            background: linear-gradient(135deg, #FF7E8B 0%, #FF6B6B 100%);
            border: none;
            padding: 1.2rem 2.5rem;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            border-radius: 0 50px 50px 0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
        }

        .btn-search::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn-search:hover::before {
            left: 100%;
        }

        .btn-search:hover {
            background: linear-gradient(135deg, #FF5722 0%, #FF7E8B 100%);
            transform: scale(1.05);
            box-shadow: 0 10px 30px rgba(255, 126, 139, 0.4);
        }

        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            z-index: 100000;
            max-height: 300px;
            overflow-y: auto;
            margin-top: 0.5rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 4rem;
            animation: fadeInUp 1s ease-out 0.8s both;
            position: relative;
            z-index: 1;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.6s;
        }

        .feature-card:hover::before {
            left: 100%;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #ffffff 0%, rgba(255,255,255,0.8) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: #FF7E8B;
            box-shadow: 0 10px 30px rgba(255,126,139,0.3);
            transition: all 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.1);
            box-shadow: 0 15px 40px rgba(255,126,139,0.4);
        }

        .feature-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: white;
        }

        .feature-description {
            font-size: 1rem;
            opacity: 0.9;
            color: rgba(255,255,255,0.9);
            line-height: 1.5;
        }

        /* Floating Elements */
        .floating-element {
            position: absolute;
            animation: float 6s ease-in-out infinite;
            z-index: 1;
        }

        .floating-element:nth-child(1) {
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .floating-element:nth-child(2) {
            top: 20%;
            right: 15%;
            animation-delay: 2s;
        }

        .floating-element:nth-child(3) {
            bottom: 20%;
            left: 15%;
            animation-delay: 4s;
        }

        .floating-element:nth-child(4) {
            bottom: 10%;
            right: 10%;
            animation-delay: 1s;
        }

        .floating-shape {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            backdrop-filter: blur(10px);
        }

        .floating-shape.triangle {
            width: 0;
            height: 0;
            border-left: 30px solid transparent;
            border-right: 30px solid transparent;
            border-bottom: 52px solid rgba(255,255,255,0.1);
            background: none;
        }

        .floating-shape.square {
            border-radius: 10px;
        }

        /* Animations */
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-20px) rotate(120deg); }
            66% { transform: translateY(10px) rotate(240deg); }
        }

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

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes slideInFromLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInFromRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1.1rem;
            }

            .search-input {
                padding: 1rem 1.5rem;
                font-size: 1rem;
            }

            .btn-search {
                padding: 1rem 2rem;
            }

            .features-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
                margin-top: 3rem;
            }

            .feature-card {
                padding: 1.5rem;
            }

            .floating-element {
                display: none;
            }
        }

        /* CART STYLES */
        .cart-btn {
            background: none;
            border: none;
            color: #ff441f;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.3s ease;
            position: relative;
        }

        .cart-btn:hover {
            background-color: rgba(255, 68, 31, 0.1);
            transform: scale(1.1);
        }

        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff441f;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* OFFCANVAS CART STYLES */
        .offcanvas-body {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .cart-footer {
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid #dee2e6;
        }

        .cart-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #f8f9fa;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 1rem;
        }

        .cart-item-details {
            flex: 1;
        }

        .cart-item-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .cart-item-price {
            color: #ff441f;
            font-weight: 600;
        }

        .cart-item-remove {
            background: none;
            border: none;
            color: #dc3545;
            padding: 0.25rem;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .cart-item-remove:hover {
            background-color: rgba(220, 53, 69, 0.1);
        }

        /* RESTAURANT CARDS */
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            overflow: hidden;
            background: white;
        }

        .card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }

        .card-img-top {
            transition: transform 0.3s ease;
        }

        .card:hover .card-img-top {
            transform: scale(1.1);
        }

        .rating-stars {
            color: #ffd700;
            font-size: 1.1rem;
        }

        .delivery-time {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        footer {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 50%, #1a1a1a 100%);
            color: white;
            margin-top: 3rem;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .restaurant-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .restaurant-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .restaurant-item {
            display: block;
        }

        .restaurant-item.hidden {
            display: none;
        }

        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            max-height: 300px;
            overflow-y: auto;
            z-index: 100000;
            display: none;
        }

        .suggestion-item {
            padding: 12px 16px;
            cursor: pointer;
            transition: background-color 0.2s ease;
            border-bottom: 1px solid #f8f9fa;
            color: black;
        }

        .suggestion-item small {
            color: #6c757d !important; /* Keep small text slightly muted but visible */
        }

        .suggestion-item:hover {
            background-color: #f8f9fa;
        }

        .suggestion-item:last-child {
            border-bottom: none;
        }
    </style>
    <script>
        // Datos de restaurantes para mapa
        const restaurants = [
            <?php
            $sql = "SELECT id, nombre, ubicacion_gps, imagen, calificacion, tiempo_entrega FROM restaurantes WHERE estado = 'ABIERTO' AND ubicacion_gps IS NOT NULL AND ubicacion_gps != ''";
            $result = $conn->query($sql);
            $restaurants_data = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $coords = explode(',', $row['ubicacion_gps']);
                    if (count($coords) == 2) {
                        $lat = trim($coords[0]);
                        $lng = trim($coords[1]);
                        $restaurants_data[] = [
                            'id' => $row['id'],
                            'name' => addslashes($row['nombre']),
                            'lat' => $lat,
                            'lng' => $lng,
                            'image' => $row['imagen'] ?: 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=100&h=100&fit=crop',
                            'rating' => (float)($row['calificacion'] ?: 4.0),
                            'delivery_time' => (int)($row['tiempo_entrega'] ?: 30)
                        ];
                    }
                }
            }
            echo implode(',', array_map(function($r) {
                return json_encode($r);
            }, $restaurants_data));
            ?>
        ];
    </script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="assets/FOODAPP.png" alt="FoodApp" width="40" height="40" class="me-2">
                FoodApp
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#hero">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#restaurantes">Restaurantes</a>
                    </li>
                    <?php if ($user_logged_in): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="#restaurantes-cercanos">Cercanos</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <div class="d-flex align-items-center">
                    <?php if ($user_logged_in): ?>
                        <div class="dropdown">
                            <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user me-1"></i>Hola, <?php echo htmlspecialchars($user_name); ?>!
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="views/profile.php"><i class="fas fa-user-circle me-2"></i>Mi Perfil</a></li>
                                <li><a class="dropdown-item" href="views/my_orders.php"><i class="fas fa-shopping-bag me-2"></i>Mis Pedidos</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="controllers/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="views/login.php" class="btn btn-outline-primary btn-sm me-2">Iniciar Sesión</a>
                        <a href="views/register.php" class="btn btn-custom btn-sm">Registrarse</a>
                    <?php endif; ?>
                    <button class="cart-btn ms-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas">
                        <i class="fas fa-shopping-cart fa-lg"></i>
                        <span class="cart-count" id="cart-count">0</span>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Cart Offcanvas -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="cartOffcanvas" aria-labelledby="cartOffcanvasLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="cartOffcanvasLabel">
                <i class="fas fa-shopping-cart me-2"></i>Tu Carrito
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div id="cart-items">
                <div class="text-center text-muted py-5">
                    <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                    <p>Tu carrito está vacío</p>
                    <p class="small">Agrega productos de los restaurantes</p>
                </div>
            </div>
            <div class="cart-footer mt-auto">
                <button class="btn btn-custom w-100" id="checkout-btn" onclick="checkout()" disabled>
                    <i class="fas fa-credit-card me-2"></i>Proceder al Pago
                </button>
            </div>
        </div>
    </div>

    <main>
        <?php
        // Show success message if redirected from order processing
        if (isset($_GET['success']) && $_GET['success'] == '1') {
            $pedido_id = isset($_GET['pedido_id']) ? intval($_GET['pedido_id']) : 0;
            echo '<div class="container mt-3">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    ¡Pedido realizado exitosamente! Tu número de pedido es: <strong>#' . $pedido_id . '</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>';
        }
        ?>

        <section id="hero" class="hero-section">
            <!-- Floating Elements -->
            <div class="floating-element">
                <div class="floating-shape"></div>
            </div>
            <div class="floating-element">
                <div class="floating-shape triangle"></div>
            </div>
            <div class="floating-element">
                <div class="floating-shape square"></div>
            </div>
            <div class="floating-element">
                <div class="floating-shape"></div>
            </div>

            <div class="hero-bg"></div>
            <div class="container">
                <div class="hero-content">
                    <h1 class="hero-title">
                        <span class="d-block">🍽️ Deliciosa Comida</span>
                        <span class="d-block">a Tu Puerta</span>
                    </h1>
                    <p class="hero-subtitle">
                        Descubre los mejores restaurantes de tu ciudad y disfruta de una experiencia culinaria única con entrega rápida y segura
                    </p>

                    <div class="search-box">
                        <div class="search-input-group">
                            <input type="text" class="search-input" placeholder="Buscar restaurantes o comidas..." id="main-search">
                            <button class="btn btn-search" id="search-btn">
                                <i class="fas fa-search"></i>
                                <span class="ms-2 d-none d-sm-inline">Buscar</span>
                            </button>
                        </div>
                        <div id="search-suggestions" class="search-suggestions" style="display: none;"></div>
                    </div>

                    <div class="features-grid">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-bolt"></i>
                            </div>
                            <h5 class="feature-title">Entrega Rápida</h5>
                            <p class="feature-description">Recibe tu pedido en menos de 30 minutos con nuestros repartidores express</p>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-utensils"></i>
                            </div>
                            <h5 class="feature-title">+100 Restaurantes</h5>
                            <p class="feature-description">Descubre una amplia variedad de cocinas y sabores de los mejores lugares</p>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h5 class="feature-title">Pago Seguro</h5>
                            <p class="feature-description">Transacciones 100% seguras con múltiples métodos de pago disponibles</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <?php if ($user_logged_in): ?>
        <section id="restaurantes-cercanos" class="py-5 bg-light">
            <div class="container">
                <div class="text-center mb-5">
                    <h2 class="display-4 fw-bold">📍 Restaurantes Cercanos</h2>
                    <p class="lead">Encuentra los restaurantes más cerca de ti</p>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Selecciona tu ubicación</h5>
                                <p class="card-text">Haz clic en el mapa para marcar tu ubicación actual.</p>
                                <div id="location-map" style="height: 300px; border-radius: 10px;"></div>
                                <button id="get-location-btn" class="btn btn-primary mt-3">
                                    <i class="fas fa-map-marker-alt me-2"></i>Usar mi ubicación actual
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Restaurantes Cercanos</h5>
                                <div id="nearby-restaurants" style="max-height: 400px; overflow-y: auto;">
                                    <p class="text-muted">Selecciona tu ubicación para ver los restaurantes cercanos.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <section id="restaurantes" class="py-5 bg-white">
            <div class="container">
                <div class="text-center mb-5">
                    <h2 class="display-4 fw-bold">🏪 Restaurantes Destacados</h2>
                    <p class="lead">Descubre los mejores lugares para comer en Arequipa</p>
                </div>

                <!-- Categorías -->
                <div class="d-flex flex-wrap justify-content-center mb-4 gap-2" id="categories">
                    <button class="btn category-btn active" data-category="all">
                        <span class="me-2">🍽️</span>Todos
                    </button>
                    <button class="btn category-btn" data-category="pizza">
                        <span class="me-2">🍕</span>Pizza
                    </button>
                    <button class="btn category-btn" data-category="pollo">
                        <span class="me-2">🍗</span>Pollo
                    </button>
                    <button class="btn category-btn" data-category="burger">
                        <span class="me-2">🍔</span>Burger
                    </button>
                    <button class="btn category-btn" data-category="sushi">
                        <span class="me-2">🍣</span>Sushi
                    </button>
                    <button class="btn category-btn" data-category="cafe">
                        <span class="me-2">☕</span>Café
                    </button>
                    <button class="btn category-btn" data-category="criolla">
                        <span class="me-2">🇵🇪</span>Criolla
                    </button>
                    <button class="btn category-btn" data-category="vegano">
                        <span class="me-2">🥗</span>Vegano
                    </button>
                    <button class="btn category-btn" data-category="mexicana">
                        <span class="me-2">🌮</span>Mexicana
                    </button>
                    <button class="btn category-btn" data-category="china">
                        <span class="me-2">🥢</span>China
                    </button>
                </div>

                <!-- Barra de búsqueda adicional -->
                <div class="row justify-content-center mb-4">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0 ps-0" id="restaurant-search" placeholder="Buscar restaurantes específicos...">
                            <button class="btn btn-outline-secondary" type="button" onclick="clearRestaurantSearch()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row" id="restaurant-list">
                    <?php
                    // Obtener restaurantes de la base de datos
                    $sql = "SELECT * FROM restaurantes WHERE estado = 'ABIERTO' ORDER BY calificacion DESC, nombre ASC";
                    $result = $conn->query($sql);

                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            // Determinar imagen por defecto basada en el tipo de restaurante
                            $image_url = 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=400&h=250&fit=crop';
                            if (!empty($row['imagen'])) {
                                $image_url = $row['imagen'];
                            } else {
                                // Imágenes por defecto basadas en el nombre
                                $name_lower = strtolower($row['nombre']);
                                if (strpos($name_lower, 'pizza') !== false || strpos($name_lower, 'pizzeria') !== false) {
                                    $image_url = 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=400&h=250&fit=crop';
                                } elseif (strpos($name_lower, 'pollo') !== false || strpos($name_lower, 'kfc') !== false) {
                                    $image_url = 'https://images.unsplash.com/photo-1598103442097-8b74394b95c6?w=400&h=250&fit=crop';
                                } elseif (strpos($name_lower, 'burger') !== false || strpos($name_lower, 'mcdonald') !== false) {
                                    $image_url = 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400&h=250&fit=crop';
                                } elseif (strpos($name_lower, 'sushi') !== false) {
                                    $image_url = 'https://images.unsplash.com/photo-1579871494447-9811cf80d66c?w=400&h=250&fit=crop';
                                } elseif (strpos($name_lower, 'café') !== false || strpos($name_lower, 'cafe') !== false || strpos($name_lower, 'starbucks') !== false) {
                                    $image_url = 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=400&h=250&fit=crop';
                                } elseif (strpos($name_lower, 'criolla') !== false || strpos($name_lower, 'peruana') !== false) {
                                    $image_url = 'https://images.unsplash.com/photo-1551782450-17144efb5723?w=400&h=250&fit=crop';
                                } elseif (strpos($name_lower, 'vegano') !== false || strpos($name_lower, 'vegetariano') !== false) {
                                    $image_url = 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=400&h=250&fit=crop';
                                } elseif (strpos($name_lower, 'chino') !== false || strpos($name_lower, 'chifa') !== false) {
                                    $image_url = 'https://images.unsplash.com/photo-1563379091339-03246963d96c?w=400&h=250&fit=crop';
                                } elseif (strpos($name_lower, 'mexicana') !== false || strpos($name_lower, 'taco') !== false) {
                                    $image_url = 'https://images.unsplash.com/photo-1565299624946-b28f40a0ca4b?w=400&h=250&fit=crop';
                                } elseif (strpos($name_lower, 'heladería') !== false || strpos($name_lower, 'heladeria') !== false) {
                                    $image_url = 'https://images.unsplash.com/photo-1567206563064-6f60f40a2b57?w=400&h=250&fit=crop';
                                } elseif (strpos($name_lower, 'poke') !== false) {
                                    $image_url = 'https://images.unsplash.com/photo-1546833999-b9f581a1996d?w=400&h=250&fit=crop';
                                }
                            }

                            // Determinar categoría para filtrado
                            $categoria = 'otros';
                            $name_lower = strtolower($row['nombre']);
                            if (strpos($name_lower, 'pizza') !== false || strpos($name_lower, 'pizzeria') !== false || strpos($name_lower, 'papa john') !== false) {
                                $categoria = 'pizza';
                            } elseif (strpos($name_lower, 'pollo') !== false || strpos($name_lower, 'kfc') !== false || strpos($name_lower, 'verde') !== false) {
                                $categoria = 'pollo';
                            } elseif (strpos($name_lower, 'burger') !== false || strpos($name_lower, 'mcdonald') !== false || strpos($name_lower, 'king') !== false) {
                                $categoria = 'burger';
                            } elseif (strpos($name_lower, 'sushi') !== false) {
                                $categoria = 'sushi';
                            } elseif (strpos($name_lower, 'café') !== false || strpos($name_lower, 'cafe') !== false || strpos($name_lower, 'starbucks') !== false || strpos($name_lower, 'juan valdez') !== false || strpos($name_lower, 'creperia') !== false) {
                                $categoria = 'cafe';
                            } elseif (strpos($name_lower, 'criolla') !== false || strpos($name_lower, 'peruana') !== false || strpos($name_lower, 'hugo') !== false) {
                                $categoria = 'criolla';
                            } elseif (strpos($name_lower, 'vegano') !== false || strpos($name_lower, 'vegetariano') !== false || strpos($name_lower, 'natural') !== false) {
                                $categoria = 'vegano';
                            } elseif (strpos($name_lower, 'mexicana') !== false || strpos($name_lower, 'taco') !== false || strpos($name_lower, 'chipotle') !== false) {
                                $categoria = 'mexicana';
                            } elseif (strpos($name_lower, 'chino') !== false || strpos($name_lower, 'china') !== false || strpos($name_lower, 'panda') !== false || strpos($name_lower, 'chifa') !== false) {
                                $categoria = 'china';
                            }

                            // Mostrar calificación real o generar una si es 0
                            $rating = $row['calificacion'] > 0 ? $row['calificacion'] : rand(35, 50) / 10;
                            $delivery_time = $row['tiempo_entrega'] ?: rand(15, 45);

                            // Emoji basado en categoría
                            $emoji = '🍽️';
                            switch($categoria) {
                                case 'pizza': $emoji = '🍕'; break;
                                case 'pollo': $emoji = '🍗'; break;
                                case 'burger': $emoji = '🍔'; break;
                                case 'sushi': $emoji = '🍣'; break;
                                case 'cafe': $emoji = '☕'; break;
                                case 'criolla': $emoji = '🇵🇪'; break;
                                case 'vegano': $emoji = '🥗'; break;
                                case 'mexicana': $emoji = '🌮'; break;
                                case 'china': $emoji = '🥢'; break;
                            }
                            ?>
                            <div class="col-lg-4 col-md-6 mb-4 restaurant-item" data-category="<?php echo $categoria; ?>" data-name="<?php echo htmlspecialchars(strtolower($row['nombre'])); ?>">
                                <div class="card h-100 restaurant-card">
                                    <div class="position-relative overflow-hidden">
                                        <img src="<?php echo $image_url; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['nombre']); ?>" style="height: 200px; object-fit: cover;">
                                        <div class="position-absolute top-0 end-0 m-2">
                                            <span class="badge bg-success"><?php echo $row['estado']; ?></span>
                                        </div>
                                        <div class="position-absolute top-0 start-0 m-2">
                                            <span class="badge bg-primary"><?php echo $emoji; ?></span>
                                        </div>
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title"><?php echo htmlspecialchars($row['nombre']); ?></h5>
                                        <p class="card-text text-muted small mb-2"><?php echo htmlspecialchars($row['direccion']); ?></p>
                                        <div class="mt-auto">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <div class="rating-stars">
                                                    <?php
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        if ($i <= floor($rating)) {
                                                            echo '<i class="fas fa-star"></i>';
                                                        } elseif ($i - 0.5 <= $rating) {
                                                            echo '<i class="fas fa-star-half-alt"></i>';
                                                        } else {
                                                            echo '<i class="far fa-star"></i>';
                                                        }
                                                    }
                                                    ?>
                                                    <span class="ms-1"><?php echo number_format($rating, 1); ?></span>
                                                </div>
                                                <span class="delivery-time"><?php echo $delivery_time; ?> min</span>
                                            </div>
                                            <a href="views/menu.php?id=<?php echo $row['id']; ?>" class="btn btn-custom w-100">
                                                <i class="fas fa-utensils me-2"></i>Ver Menú
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<div class="col-12 text-center">
                            <p class="text-muted">No hay restaurantes disponibles en este momento.</p>
                        </div>';
                    }
                    ?>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-gradient-primary text-white py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h5 class="mb-3">FoodApp</h5>
                    <p>La mejor manera de pedir comida deliciosa desde la comodidad de tu hogar.</p>
                    <div class="d-flex">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram fa-lg"></i></a>
                    </div>
                </div>
                <div class="col-lg-4">
                    <h5 class="mb-3">Enlaces Rápidos</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white text-decoration-none">Sobre Nosotros</a></li>
                        <li><a href="#" class="text-white text-decoration-none">Contacto</a></li>
                        <li><a href="#" class="text-white text-decoration-none">Política de Privacidad</a></li>
                        <li><a href="#" class="text-white text-decoration-none">Términos de Servicio</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h5 class="mb-3">Contacto</h5>
                    <p><i class="fas fa-phone me-2"></i>+1 234 567 890</p>
                    <p><i class="fas fa-envelope me-2"></i>info@foodapp.com</p>
                    <p><i class="fas fa-map-marker-alt me-2"></i>Arequipa Peru</p>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p class="mb-0">&copy; 2024 FoodApp. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // CART FUNCTIONALITY
        let cart = [];

        // Load cart from localStorage
        function loadCart() {
            const savedCart = localStorage.getItem('cart');
            if (savedCart) {
                try {
                    cart = JSON.parse(savedCart);
                } catch (e) {
                    cart = [];
                }
            }
            updateCartCount();
            updateCartDisplay();
        }

        // Save cart to localStorage
        function saveCart() {
            localStorage.setItem('cart', JSON.stringify(cart));
        }

        // Update cart count badge
        function updateCartCount() {
            const cartCount = document.getElementById('cart-count');
            if (cartCount) {
                const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
                cartCount.textContent = totalItems;
            }
        }

        // Update cart display in offcanvas
        function updateCartDisplay() {
            const cartItems = document.getElementById('cart-items');
            const checkoutBtn = document.getElementById('checkout-btn');

            if (!cartItems) return;

            if (cart.length === 0) {
                cartItems.innerHTML = `
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                        <p>Tu carrito está vacío</p>
                        <p class="small">Agrega productos de los restaurantes</p>
                    </div>
                `;
                if (checkoutBtn) checkoutBtn.disabled = true;
                return;
            }

            let total = 0;
            const itemsHtml = cart.map((item, index) => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                let imageSrc = item.image;
                if (imageSrc.startsWith('../')) {
                    imageSrc = '/FOODAPP/foodapp_php/' + imageSrc.substring(3);
                }
                return `
                    <div class="cart-item">
                        <img src="${imageSrc}" alt="${item.name}" class="cart-item-image">
                        <div class="cart-item-details">
                            <div class="cart-item-title">${item.name}</div>
                            <div class="cart-item-price">S/ ${(item.price * item.quantity).toFixed(2)} (${item.quantity}x)</div>
                        </div>
                        <button class="cart-item-remove" onclick="removeFromCart(${index})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
            }).join('');

            cartItems.innerHTML = `
                ${itemsHtml}
                <div class="mt-3 p-3 bg-light rounded">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong>Total:</strong>
                        <strong class="text-primary">S/ ${total.toFixed(2)}</strong>
                    </div>
                </div>
            `;
            if (checkoutBtn) checkoutBtn.disabled = false;
        }

        // Add item to cart
        function addToCart(id, name, price, image, restaurante_id) {
            const existingItem = cart.find(item => item.id === id);

            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push({
                    id: id,
                    name: name,
                    price: parseFloat(price),
                    image: image,
                    restaurante_id: restaurante_id,
                    quantity: 1
                });
            }

            saveCart();
            updateCartCount();
            updateCartDisplay();
            showToast('Producto agregado al carrito', 'success');
        }

        // Remove item from cart
        function removeFromCart(index) {
            cart.splice(index, 1);
            saveCart();
            updateCartCount();
            updateCartDisplay();
            showToast('Producto eliminado del carrito', 'info');
        }

        // Clear cart
        function clearCart() {
            cart = [];
            saveCart();
            updateCartCount();
            updateCartDisplay();
        }

        // Checkout function
        function checkout() {
            if (cart.length === 0) {
                showToast('Tu carrito está vacío', 'warning');
                return;
            }

            // Store cart data for checkout page
            sessionStorage.setItem('checkout_cart', JSON.stringify(cart));
            window.location.href = 'views/checkout.php';
        }

        // Toast notification
        function showToast(message, type = 'info') {
            // Create toast container if it doesn't exist
            let toastContainer = document.querySelector('.toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
                toastContainer.style.zIndex = '9999';
                document.body.appendChild(toastContainer);
            }

            // Create toast element
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;

            toastContainer.appendChild(toast);

            // Initialize and show toast
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();

            // Remove toast from DOM after it's hidden
            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        }

        // Smooth scroll for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Load cart on page load
        loadCart();

        // Category filtering
        const categoryButtons = document.querySelectorAll('.category-btn');
        const restaurantItems = document.querySelectorAll('.restaurant-item');
        const restaurantSearch = document.getElementById('restaurant-search');

        categoryButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Remove active class from all buttons
                categoryButtons.forEach(btn => btn.classList.remove('active'));
                // Add active class to clicked button
                button.classList.add('active');

                const category = button.dataset.category;
                filterRestaurants(category);
            });
        });

        function filterRestaurants(category) {
            restaurantItems.forEach(item => {
                const itemCategory = item.dataset.category;
                if (category === 'all' || itemCategory === category) {
                    item.classList.remove('hidden');
                    item.style.display = 'block';
                } else {
                    item.classList.add('hidden');
                    item.style.display = 'none';
                }
            });
        }

        // Restaurant search functionality
        if (restaurantSearch) {
            restaurantSearch.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                filterRestaurantsBySearch(searchTerm);
            });
        }

        function filterRestaurantsBySearch(searchTerm) {
            restaurantItems.forEach(item => {
                const restaurantName = item.dataset.name;
                const isVisible = restaurantName.includes(searchTerm);
                if (isVisible) {
                    item.classList.remove('hidden');
                    item.style.display = 'block';
                } else {
                    item.classList.add('hidden');
                    item.style.display = 'none';
                }
            });
        }

        function clearRestaurantSearch() {
            if (restaurantSearch) {
                restaurantSearch.value = '';
                filterRestaurantsBySearch('');
            }
        }

        // Main search functionality with suggestions
        const mainSearchInput = document.getElementById('main-search');
        const searchSuggestions = document.getElementById('search-suggestions');
        const searchBtn = document.getElementById('search-btn');

        let searchTimeout;

        function performMainSearch() {
            const query = mainSearchInput.value.trim();
            if (query) {
                // Redirect to search page with query
                window.location.href = `views/search.php?q=${encodeURIComponent(query)}`;
            }
        }

        if (searchBtn) {
            searchBtn.addEventListener('click', performMainSearch);
        }

        if (mainSearchInput) {
            mainSearchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performMainSearch();
                }
            });

            // Live search suggestions
            mainSearchInput.addEventListener('input', function() {
                const query = this.value.trim();
                clearTimeout(searchTimeout);

                if (query.length >= 2) {
                    searchTimeout = setTimeout(() => showSearchSuggestions(query), 300);
                } else {
                    searchSuggestions.style.display = 'none';
                }
            });
        }

        async function showSearchSuggestions(query) {
            try {
                const response = await fetch(`views/search_api.php?term=${encodeURIComponent(query)}`);
                const data = await response.json();

                if (data.restaurantes && data.restaurantes.length > 0) {
                    let html = '<div class="p-2 border-bottom"><small class="text-muted fw-bold">🏪 Restaurantes</small></div>';

                    data.restaurantes.slice(0, 5).forEach(restaurante => {
                        html += `
                            <div class="suggestion-item" onclick="window.location.href='views/menu.php?id=${restaurante.id}'">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">🏪</div>
                                    <div>
                                        <div class="fw-bold">${restaurante.nombre}</div>
                                        <small class="text-muted">${restaurante.direccion || 'Sin dirección'}</small>
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    if (data.productos && data.productos.length > 0) {
                        html += '<div class="p-2 border-bottom"><small class="text-muted fw-bold">🍽️ Productos</small></div>';
                        data.productos.slice(0, 5).forEach(producto => {
                            html += `
                                <div class="suggestion-item" onclick="window.location.href='views/menu.php?id=${producto.restaurante_id}'">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">🍽️</div>
                                        <div>
                                            <div class="fw-bold">${producto.nombre}</div>
                                            <small class="text-muted">${producto.restaurante_nombre} - S/ ${producto.precio}</small>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                    }

                    searchSuggestions.innerHTML = html;
                    searchSuggestions.style.display = 'block';
                } else {
                    searchSuggestions.style.display = 'none';
                }
            } catch (error) {
                console.error('Error fetching search suggestions:', error);
                searchSuggestions.style.display = 'none';
            }
        }

        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (mainSearchInput && searchSuggestions && !mainSearchInput.contains(e.target) && !searchSuggestions.contains(e.target)) {
                searchSuggestions.style.display = 'none';
            }
        });
    </script>
    <script>
        // Función para guardar ubicación del usuario
        function saveUserLocation(lat, lng) {
            fetch('controllers/save_location.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'lat=' + lat + '&lng=' + lng
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Ubicación guardada:', lat, lng);
                } else {
                    console.error('Error guardando ubicación:', data.error);
                }
            })
            .catch(error => {
                console.error('Error en petición:', error);
            });
        }

        // Mapa para restaurantes cercanos
        <?php if ($user_logged_in): ?>
        let map;
        let userMarker;
        let userLatLng;
        let restaurantMarkers = [];
        // Ubicación guardada del usuario
        const savedLocation = <?php echo isset($_SESSION['user_location']) ? json_encode($_SESSION['user_location']) : 'null'; ?>;

        function initMap() {
            // Usar ubicación guardada o centro en Arequipa por defecto
            const defaultLat = savedLocation ? savedLocation.lat : -16.4090;
            const defaultLng = savedLocation ? savedLocation.lng : -71.5375;

            map = L.map('location-map').setView([defaultLat, defaultLng], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Marcador inicial
            userMarker = L.marker([defaultLat, defaultLng], {draggable: true}).addTo(map);
            userLatLng = [defaultLat, defaultLng];

            userMarker.on('dragend', function(e) {
                userLatLng = [e.target.getLatLng().lat, e.target.getLatLng().lng];
                saveUserLocation(userLatLng[0], userLatLng[1]);
                updateNearbyRestaurants();
            });

            map.on('click', function(e) {
                userMarker.setLatLng(e.latlng);
                userLatLng = [e.latlng.lat, e.latlng.lng];
                saveUserLocation(userLatLng[0], userLatLng[1]);
                updateNearbyRestaurants();
            });
        }

        function getUserLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    userMarker.setLatLng([lat, lng]);
                    map.setView([lat, lng], 15);
                    userLatLng = [lat, lng];
                    saveUserLocation(lat, lng);
                    updateNearbyRestaurants();
                }, function(error) {
                    alert('Error obteniendo ubicación: ' + error.message);
                });
            } else {
                alert('Geolocalización no soportada por este navegador.');
            }
        }

        function calculateDistance(lat1, lng1, lat2, lng2) {
            const R = 6371; // Radio de la Tierra en km
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLng = (lng2 - lng1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLng/2) * Math.sin(dLng/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }

        function updateNearbyRestaurants() {
            const nearbyContainer = document.getElementById('nearby-restaurants');
            
            // Calcular distancias
            const restaurantsWithDistance = restaurants.map(r => ({
                ...r,
                distance: calculateDistance(userLatLng[0], userLatLng[1], parseFloat(r.lat), parseFloat(r.lng))
            })).sort((a, b) => a.distance - b.distance);

            // Limpiar marcadores anteriores
            restaurantMarkers.forEach(marker => map.removeLayer(marker));
            restaurantMarkers = [];

            // Agregar marcadores de restaurantes cercanos
            restaurantsWithDistance.slice(0, 10).forEach(r => {
                const marker = L.marker([parseFloat(r.lat), parseFloat(r.lng)])
                    .addTo(map)
                    .bindPopup(`<b>${r.name}</b><br>Distancia: ${r.distance.toFixed(1)} km<br>Calificación: ${r.rating.toFixed(1)} ⭐`);
                restaurantMarkers.push(marker);
            });

            let html = '<div class="list-group">';
            restaurantsWithDistance.slice(0, 10).forEach(r => {
                const stars = '★'.repeat(Math.floor(r.rating)) + '☆'.repeat(5 - Math.floor(r.rating));
                html += `
                    <a href="views/menu.php?id=${r.id}" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <div class="d-flex">
                                <img src="${r.image}" class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                <div>
                                    <h6 class="mb-1">${r.name}</h6>
                                    <p class="mb-1 small text-muted">${stars} ${r.rating.toFixed(1)} • ${r.delivery_time} min</p>
                                </div>
                            </div>
                            <small class="text-muted">${r.distance.toFixed(1)} km</small>
                        </div>
                    </a>
                `;
            });
            html += '</div>';

            nearbyContainer.innerHTML = html;
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('location-map')) {
                initMap();
                document.getElementById('get-location-btn').addEventListener('click', getUserLocation);
            }
        });
        <?php endif; ?>
    </script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</body>
</html>
