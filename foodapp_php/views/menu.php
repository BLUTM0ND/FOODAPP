<?php
session_start();
include '../includes/config.php';
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // If no restaurant ID provided, redirect to index
    header('Location: ../index.php');
    exit;
}
$rest_id = intval($_GET['id']);
$sql = "SELECT * FROM restaurantes WHERE id = $rest_id";
$result = $conn->query($sql);
if (!$result || $result->num_rows == 0) {
    // Restaurant not found, redirect to index
    header('Location: ../index.php');
    exit;
}
$rest = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($rest['nombre']); ?> - FoodApp</title>
    <link rel="icon" href="../assets/FOODAPP.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        :root {
            --primary-color: #ff1744;
            --secondary-color: #ff6d00;
            --success-color: #00c853;
            --warning-color: #ff9800;
            --danger-color: #f44336;
            --dark-color: #212121;
            --light-color: #fafafa;
            --gray-color: #757575;
            --border-color: #e0e0e0;
            --shadow: 0 2px 8px rgba(0,0,0,0.1);
            --shadow-lg: 0 4px 20px rgba(0,0,0,0.15);
            --border-radius: 12px;
            --border-radius-lg: 16px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-color);
            color: var(--dark-color);
            line-height: 1.6;
        }

        /* Header Style */
        .app-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px 20px;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow);
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1200px;
            margin: 0 auto;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .back-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .brand {
            font-size: 24px;
            font-weight: 700;
            color: white;
            text-decoration: none;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .cart-btn {
            position: relative;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .cart-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
        }

        /* Restaurant Info */
        .restaurant-hero {
            background: white;
            padding: 24px 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .restaurant-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .restaurant-image {
            width: 80px;
            height: 80px;
            border-radius: var(--border-radius);
            object-fit: cover;
            flex-shrink: 0;
        }

        .restaurant-details h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--dark-color);
        }

        .restaurant-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--gray-color);
            font-size: 14px;
        }

        .rating {
            background: var(--success-color);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
        }

        .delivery-time {
            color: var(--primary-color);
            font-weight: 600;
        }

        .restaurant-description {
            color: var(--gray-color);
            font-size: 16px;
            line-height: 1.5;
        }

        .map-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .map-btn:hover {
            background: var(--secondary-color);
            transform: scale(1.1);
        }

        /* Categories */
        .categories-section {
            background: white;
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .categories-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            gap: 12px;
            padding: 8px 0;
        }

        .category-btn {
            background: white;
            border: 2px solid var(--border-color);
            color: var(--dark-color);
            padding: 12px 20px;
            border-radius: 25px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .category-btn:hover,
        .category-btn.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        /* Products Grid */
        .products-section {
            padding: 24px 20px;
        }

        .products-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
        }

        .product-card {
            background: white;
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        .product-content {
            padding: 20px;
        }

        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .product-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-color);
            margin: 0;
            flex: 1;
        }

        .product-price {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }

        .product-description {
            color: var(--gray-color);
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 16px;
        }

        .product-category {
            display: inline-block;
            background: #f5f5f5;
            color: var(--gray-color);
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
        }

        .add-to-cart-btn {
            width: 100%;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 14px 20px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .add-to-cart-btn:hover {
            background: #d50000;
            transform: translateY(-2px);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray-color);
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--dark-color);
        }

        /* Map Modal */
        .map-modal .modal-content {
            border-radius: var(--border-radius-lg);
            border: none;
            overflow: hidden;
        }

        .map-modal .modal-header {
            background: var(--primary-color);
            color: white;
            border: none;
        }

        #restaurant-map {
            height: 400px;
            border-radius: 0;
        }

        /* Toast */
        .toast {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--shadow-lg);
        }

        .toast-success {
            background: var(--success-color);
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .restaurant-content {
                flex-direction: column;
                text-align: center;
                gap: 16px;
            }

            .restaurant-hero {
                padding: 20px 16px;
            }

            .restaurant-details h1 {
                font-size: 24px;
            }

            .products-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .product-content {
                padding: 16px;
            }

            .categories-section {
                padding: 12px 16px;
            }

            .products-section {
                padding: 20px 16px;
            }
        }

        /* Animations */
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .product-card {
            animation: fadeIn 0.6s ease-out;
        }

        .product-card:nth-child(1) { animation-delay: 0.1s; }
        .product-card:nth-child(2) { animation-delay: 0.2s; }
        .product-card:nth-child(3) { animation-delay: 0.3s; }
        .product-card:nth-child(4) { animation-delay: 0.4s; }
        .product-card:nth-child(5) { animation-delay: 0.5s; }
        .product-card:nth-child(6) { animation-delay: 0.6s; }

        /* CART STYLES */
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
            color: var(--primary-color);
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

        .cart-footer {
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid #dee2e6;
        }

        .cart-total {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <!-- App Header -->
    <header class="app-header">
        <div class="header-content">
            <div class="header-left">
                <button class="back-btn" onclick="history.back()">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <a href="../index.php" class="brand">FoodApp</a>
            </div>
            <div class="header-right">
                <button class="cart-btn" data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count" id="cart-count">0</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Restaurant Hero -->
    <section class="restaurant-hero">
        <div class="restaurant-content">
            <?php
            // Determinar imagen del restaurante
            $restaurant_image_url = 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?ixlib=rb-4.0.3&auto=format&fit=crop&w=200&q=80'; // Imagen por defecto
            if (!empty($rest['imagen'])) {
                if (filter_var($rest['imagen'], FILTER_VALIDATE_URL)) {
                    // Es una URL externa
                    $restaurant_image_url = $rest['imagen'];
                } elseif (strpos($rest['imagen'], 'uploads/') === 0) {
                    // Es una ruta local
                    $restaurant_image_url = '../' . $rest['imagen'];
                }
            }
            ?>
            <img src="<?php echo htmlspecialchars($restaurant_image_url); ?>"
                 alt="<?php echo htmlspecialchars($rest['nombre']); ?>"
                 class="restaurant-image">
            <div class="restaurant-details">
                <h1><?php echo htmlspecialchars($rest['nombre']); ?></h1>
                <div class="restaurant-meta">
                    <span class="meta-item rating">
                        <i class="fas fa-star"></i>
                        <?php echo number_format($rest['calificacion'], 1); ?>
                    </span>
                    <span class="meta-item delivery-time">
                        <i class="fas fa-clock"></i>
                        <?php echo $rest['tiempo_entrega']; ?> min
                    </span>
                    <span class="meta-item location">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($rest['direccion']); ?>
                        <button class="map-btn ms-2" data-bs-toggle="modal" data-bs-target="#mapModal">
                            <i class="fas fa-external-link-alt"></i>
                        </button>
                    </span>
                </div>
                <p class="restaurant-description">
                    Disfruta de nuestros deliciosos platillos preparados con los mejores ingredientes frescos.
                    ¡Haz tu pedido ahora!
                </p>
            </div>
        </div>
    </section>

    <!-- Categories -->
    <section class="categories-section">
        <div class="categories-container">
            <button class="category-btn active" data-category="all">
                <i class="fas fa-th-large me-2"></i>Todo
            </button>
            <?php
            $categories = $conn->query("SELECT DISTINCT categoria FROM productos WHERE restaurante_id = $rest_id ORDER BY categoria");
            while ($cat = $categories->fetch_assoc()) {
                $emoji = '';
                switch ($cat['categoria']) {
                    case 'Pizza': $emoji = '🍕'; break;
                    case 'Pasta': $emoji = '🍝'; break;
                    case 'Hamburguesas': $emoji = '🍔'; break;
                    case 'Criolla': $emoji = '🇵🇪'; break;
                    case 'Mariscos': $emoji = '🦞'; break;
                    case 'Bebidas': $emoji = '🥤'; break;
                    case 'Postres': $emoji = '🍰'; break;
                    case 'Sushi': $emoji = '🍣'; break;
                    case 'Pollo': $emoji = '🍗'; break;
                    case 'Snacks': $emoji = '🍟'; break;
                    default: $emoji = '🍽️';
                }
                echo "<button class='category-btn' data-category='" . htmlspecialchars($cat['categoria']) . "'>
                        <span class='me-2'>{$emoji}</span>" . htmlspecialchars($cat['categoria']) . "
                      </button>";
            }
            ?>
        </div>
    </section>

    <!-- Products -->
    <section class="products-section">
        <div class="products-container">
            <div class="products-grid">
                <?php
                $sql = "SELECT * FROM productos WHERE restaurante_id = $rest_id ORDER BY categoria, nombre";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        // Determinar imagen del producto
                        $image_url = 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'; // Imagen por defecto
                        if (!empty($row['imagen'])) {
                            if (filter_var($row['imagen'], FILTER_VALIDATE_URL)) {
                                // Es una URL externa
                                $image_url = $row['imagen'];
                            } elseif (strpos($row['imagen'], 'uploads/') === 0) {
                                // Es una ruta local
                                $image_url = '/FOODAPP/foodapp_php/' . $row['imagen'];
                            }
                        }

                        echo "<div class='product-card' data-category='" . htmlspecialchars($row['categoria']) . "'>
                                <img src='" . htmlspecialchars($image_url) . "' alt='" . htmlspecialchars($row['nombre']) . "' class='product-image'>
                                <div class='product-content'>
                                    <div class='product-header'>
                                        <h3 class='product-title'>" . htmlspecialchars($row['nombre']) . "</h3>
                                        <p class='product-price'>S/ " . number_format($row['precio'], 2) . "</p>
                                    </div>
                                    <p class='product-description'>" . htmlspecialchars($row['descripcion']) . "</p>
                                    <span class='product-category'>" . htmlspecialchars($row['categoria']) . "</span>
                                    <button onclick='addToCart(" . intval($row['id']) . ", \"" . addslashes($row['nombre']) . "\", " . floatval($row['precio']) . ", \"" . addslashes($image_url) . "\", " . intval($rest_id) . ")' class='add-to-cart-btn'>
                                        <i class='fas fa-plus'></i>
                                        Agregar
                                    </button>
                                </div>
                              </div>";
                    }
                } else {
                    echo "<div class='empty-state'>
                            <i class='fas fa-utensils'></i>
                            <h3>No hay productos disponibles</h3>
                            <p>Este restaurante aún no ha agregado productos a su menú.</p>
                          </div>";
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Map Modal -->
    <div class="modal fade map-modal" id="mapModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-map-marked-alt me-2"></i>Ubicación de <?php echo htmlspecialchars($rest['nombre']); ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <?php
                    $coords = explode(',', $rest['ubicacion_gps']);
                    if (count($coords) >= 2) {
                        $lat = trim($coords[0]);
                        $lng = trim($coords[1]);
                    } else {
                        // Valores por defecto para Arequipa si la ubicación GPS no está bien formateada
                        $lat = '-16.3989';
                        $lng = '-71.5350';
                    }
                    ?>
                    <div id="restaurant-map"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="cart-toast" class="toast toast-success" role="alert">
            <div class="toast-header">
                <i class="fas fa-check-circle me-2"></i>
                <strong class="me-auto">¡Agregado!</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                Producto agregado al carrito correctamente.
            </div>
        </div>
    </div>

    <!-- Cart Offcanvas -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="cartOffcanvas" aria-labelledby="cartOffcanvasLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="cartOffcanvasLabel">
                <i class="fas fa-shopping-cart me-2"></i>Mi Carrito
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <div id="cart-items">
                <!-- Cart items will be loaded here -->
                <div class="text-center text-muted py-5">
                    <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                    <p>Tu carrito está vacío</p>
                    <p class="small">Agrega productos de los restaurantes</p>
                </div>
            </div>
            <div class="cart-footer mt-auto">
                <div class="d-grid gap-2">
                    <button class="btn btn-primary" id="checkout-btn" disabled>
                        <i class="fas fa-credit-card me-2"></i>Proceder al Pago
                    </button>
                    <button class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">
                        <i class="fas fa-arrow-left me-2"></i>Continuar Comprando
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let restaurantMap = null;

        // Initialize map when modal is shown
        document.getElementById('mapModal').addEventListener('shown.bs.modal', function () {
            setTimeout(() => {
                // Remove existing map if it exists
                if (restaurantMap) {
                    restaurantMap.remove();
                }

                const mapContainer = document.getElementById('restaurant-map');
                mapContainer.innerHTML = ''; // Clear any existing content

                restaurantMap = L.map('restaurant-map').setView([<?php echo $lat; ?>, <?php echo $lng; ?>], 15);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(restaurantMap);

                L.marker([<?php echo $lat; ?>, <?php echo $lng; ?>]).addTo(restaurantMap)
                    .bindPopup('<b><?php echo addslashes($rest['nombre']); ?></b><br><?php echo addslashes($rest['direccion']); ?>')
                    .openPopup();

                // Force map to resize after modal is shown
                setTimeout(() => {
                    restaurantMap.invalidateSize();
                }, 100);
            }, 100);
        });

        // Clean up map when modal is hidden
        document.getElementById('mapModal').addEventListener('hidden.bs.modal', function () {
            if (restaurantMap) {
                restaurantMap.remove();
                restaurantMap = null;
            }
        });

        // Category filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            const categoryButtons = document.querySelectorAll('.category-btn');
            const productCards = document.querySelectorAll('.product-card');

            categoryButtons.forEach(button => {
                button.addEventListener('click', () => {
                    // Remove active class from all buttons
                    categoryButtons.forEach(btn => btn.classList.remove('active'));
                    // Add active class to clicked button
                    button.classList.add('active');

                    const selectedCategory = button.getAttribute('data-category');

                    productCards.forEach(card => {
                        if (selectedCategory === 'all' || card.getAttribute('data-category') === selectedCategory) {
                            card.style.display = 'block';
                            card.style.animation = 'fadeIn 0.5s ease-out';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });
        });

        // CART FUNCTIONALITY
        let cart = [];

        // Load cart from localStorage
        function loadCart() {
            const savedCart = localStorage.getItem('cart');
            console.log('Menu: Loading cart from localStorage:', savedCart);
            if (savedCart) {
                cart = JSON.parse(savedCart);
                console.log('Menu: Parsed cart:', cart);
            }
            updateCartCount();
            updateMiniCart();
        }

        // Save cart to localStorage
        function saveCart() {
            console.log('Saving cart to localStorage:', cart);
            localStorage.setItem('cart', JSON.stringify(cart));
        }

        function updateCartCount() {
            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            const cartCountEl = document.getElementById('cart-count');
            if (cartCountEl) {
                cartCountEl.textContent = totalItems;
                cartCountEl.style.display = totalItems > 0 ? 'flex' : 'none';
            }
        }

        function updateMiniCart() {
            const cartItemsEl = document.getElementById('cart-items');
            const checkoutBtn = document.getElementById('checkout-btn');
            if (!cartItemsEl) return;

            if (cart.length === 0) {
                cartItemsEl.innerHTML = `
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                        <p>Tu carrito está vacío</p>
                        <p class="small">Agrega productos de los restaurantes</p>
                    </div>
                `;
                if (checkoutBtn) checkoutBtn.disabled = true;
                return;
            }

            let html = '';
            let total = 0;
            cart.forEach((item, index) => {
                const subtotal = item.price * item.quantity;
                total += subtotal;
                let imageSrc = item.image;
                if (imageSrc.startsWith('../')) {
                    imageSrc = '/FOODAPP/foodapp_php/' + imageSrc.substring(3);
                }
                html += `
                    <div class="cart-item">
                        <img src="${imageSrc}" alt="${item.name}" class="cart-item-image">
                        <div class="cart-item-details">
                            <div class="cart-item-title">${item.name}</div>
                            <div class="cart-item-price">S/ ${subtotal.toFixed(2)}</div>
                            <small class="text-muted">Cantidad: ${item.quantity}</small>
                        </div>
                        <button class="cart-item-remove" onclick="removeFromCart(${index})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
            });

            html += `
                <div class="cart-total mt-3 p-3 bg-light rounded">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong>Total:</strong>
                        <strong class="text-primary">S/ ${total.toFixed(2)}</strong>
                    </div>
                </div>
            `;
            cartItemsEl.innerHTML = html;
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
            updateMiniCart();
            showToast('Producto agregado al carrito', 'success');
        }

        // Remove item from cart
        function removeFromCart(index) {
            cart.splice(index, 1);
            saveCart();
            updateCartCount();
            updateMiniCart();
            showToast('Producto eliminado del carrito', 'info');
        }

        // Checkout function
        function checkout() {
            if (cart.length === 0) {
                showToast('El carrito está vacío', 'warning');
                return;
            }

            // Store cart data for checkout page
            sessionStorage.setItem('checkout_cart', JSON.stringify(cart));
            window.location.href = 'checkout.php';
        }

        // Toast notification
        function showToast(message, type = 'info') {
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

            // Add to page
            const container = document.querySelector('.container') || document.body;
            container.appendChild(toast);

            // Initialize and show
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();

            // Remove after shown
            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        }

        // Initialize cart count and mini cart on page load
        loadCart();

        // Add event listener for checkout button
        const checkoutBtn = document.getElementById('checkout-btn');
        if (checkoutBtn) {
            checkoutBtn.addEventListener('click', checkout);
        }
    </script>
</body>
</html>
