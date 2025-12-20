<?php if (session_status() == PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'FoodApp'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <header class="bg-primary text-white p-3">
        <div class="container d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">FoodApp</h1>
            <nav>
                <a href="../index.php" class="text-white me-3">Inicio</a>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="login.php" class="text-white me-3">Iniciar Sesión</a>
                    <a href="register.php" class="text-white me-3">Registrarse</a>
                <?php else: ?>
                    <span class="me-3">Hola, <?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['nombre'] ?? 'Usuario'); ?></span>
                    <a href="../controllers/logout.php" class="text-white me-3">Cerrar Sesión</a>
                <?php endif; ?>
                <a href="restaurantes.php" class="text-white me-3">Restaurantes</a>
                <a href="search.php" class="text-white me-3">Buscar</a>
                <?php if ((isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'repartidor') || (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'repartidor')): ?>
                    <a href="delivery_panel.php" class="text-white me-3">Pedidos Asignados</a>
                <?php endif; ?>
                <a href="cart.php" class="text-white position-relative" data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas" aria-controls="#cartOffcanvas">
                    <i class="fas fa-shopping-cart"></i>
                    <span id="cart-count" class="badge bg-danger position-absolute top-0 start-100 translate-middle">0</span>
                </a>
            </nav>
        </div>
    </header>
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="cart-toast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="fas fa-shopping-cart me-2"></i>
                <strong class="me-auto">Carrito</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Producto agregado al carrito
            </div>
        </div>
    </div>

    <!-- Cart Offcanvas -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="cartOffcanvas" aria-labelledby="cartOffcanvasLabel" data-bs-backdrop="false" data-bs-scroll="true">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="cartOffcanvasLabel"><i class="fas fa-shopping-cart"></i> Tu Carrito</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div id="mini-cart-items"></div>
            <div id="mini-cart-summary" class="mt-3 border-top pt-3" style="display: none;">
                <h6>Total: S/ <span id="mini-total" class="text-primary">0.00</span></h6>
                <a href="cart.php" class="btn btn-primary w-100">Ver Carrito Completo</a>
                <a href="checkout.php" class="btn btn-success w-100 mt-2">Proceder al Pago</a>
            </div>
            <div id="empty-mini-cart" class="text-center text-muted">
                <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                <p>Tu carrito está vacío</p>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateCartCount() {
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            let count = cart.reduce((sum, item) => sum + item.quantity, 0);
            const cartCountEl = document.getElementById('cart-count');
            if (cartCountEl) cartCountEl.textContent = count;
        }

        function updateMiniCart() {
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            let cartDiv = document.getElementById('mini-cart-items');
            let total = 0;
            cartDiv.innerHTML = '';
            if (cart.length === 0) {
                document.getElementById('mini-cart-summary').style.display = 'none';
                document.getElementById('empty-mini-cart').style.display = 'block';
                return;
            }
            document.getElementById('empty-mini-cart').style.display = 'none';
            for (let i = 0; i < cart.length; i++) {
                let item = cart[i];
                let subtotal = item.price * item.quantity;
                total += subtotal;
                let image = item.image || 'https://via.placeholder.com/50x50?text=P';
                cartDiv.innerHTML += `
                    <div class="d-flex align-items-center mb-3 border-bottom pb-2">
                        <img src="${image}" alt="${item.name}" class="me-3" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">${item.name}</h6>
                            <small class="text-muted">S/ ${item.price.toFixed(2)} x ${item.quantity}</small>
                            <div class="d-flex align-items-center mt-1">
                                <button onclick="updateMiniQuantity(${i}, ${item.quantity - 1})" class="btn btn-sm btn-outline-secondary me-1" ${item.quantity <= 1 ? 'disabled' : ''}>-</button>
                                <span class="mx-2">${item.quantity}</span>
                                <button onclick="updateMiniQuantity(${i}, ${item.quantity + 1})" class="btn btn-sm btn-outline-secondary">+</button>
                            </div>
                        </div>
                        <div class="text-end">
                            <strong>S/ ${subtotal.toFixed(2)}</strong>
                            <button onclick="removeMiniItem(${i})" class="btn btn-sm btn-outline-danger ms-2">×</button>
                        </div>
                    </div>
                `;
            }
            document.getElementById('mini-total').textContent = total.toFixed(2);
            document.getElementById('mini-cart-summary').style.display = 'block';
        }

        function updateMiniQuantity(index, quantity) {
            quantity = parseInt(quantity);
            if (quantity < 1 || isNaN(quantity)) return;
            let cart = JSON.parse(localStorage.getItem('cart'));
            cart[index].quantity = quantity;
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartCount();
            updateMiniCart();
        }

        function removeMiniItem(index) {
            let cart = JSON.parse(localStorage.getItem('cart'));
            cart.splice(index, 1);
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartCount();
            updateMiniCart();
        }

        updateCartCount();
    </script>
