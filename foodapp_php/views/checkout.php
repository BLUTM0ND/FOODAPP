<?php
session_start();
include_once '../includes/config.php';
include 'header.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - FoodApp</title>
    <link rel="icon" href="../assets/FOODAPP.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(135deg, #FFF5F5 0%, #FFE8E8 50%, #FFF0F0 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
            color: #2D3748;
        }

        .checkout-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .checkout-header {
            background: linear-gradient(135deg, #FF7E8B 0%, #FF6B6B 100%);
            color: white;
            padding: 2rem 0 4rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .checkout-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="80" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="60" cy="30" r="1" fill="rgba(255,255,255,0.05)"/><circle cx="30" cy="70" r="1" fill="rgba(255,255,255,0.05)"/></svg>');
            opacity: 0.3;
        }

        .checkout-header h1 {
            font-size: clamp(2rem, 4vw, 3.5rem);
            font-weight: 800;
            margin: 0 0 0.5rem;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
            position: relative;
            z-index: 1;
        }

        .checkout-header p {
            font-size: 1.1rem;
            margin: 0;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .checkout-content {
            flex: 1;
            padding: 2rem 1rem 4rem;
            max-width: 1200px;
            margin: -2rem auto 0;
            position: relative;
            z-index: 10;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        @media (min-width: 768px) {
            .checkout-grid {
                grid-template-columns: 1fr 1fr;
                gap: 3rem;
            }
        }

        .checkout-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(255, 126, 139, 0.1), 0 4px 20px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            border: 1px solid rgba(255, 126, 139, 0.1);
            transition: all 0.3s ease;
        }

        .checkout-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 60px rgba(255, 126, 139, 0.15), 0 8px 30px rgba(0, 0, 0, 0.1);
        }

        .card-header-modern {
            background: linear-gradient(135deg, #FF7E8B 0%, #FF6B6B 100%);
            color: white;
            padding: 1.5rem 2rem;
            border: none;
            position: relative;
        }

        .card-header-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path d="M0,50 Q25,30 50,50 T100,50 L100,100 L0,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
            opacity: 0.3;
        }

        .card-header-modern h2 {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            position: relative;
            z-index: 1;
        }

        .card-header-modern i {
            font-size: 1.5rem;
            opacity: 0.9;
        }

        .card-body-modern {
            padding: 2rem;
        }

        /* Order Summary Styles */
        .order-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: linear-gradient(135deg, #FFF8F8 0%, #FFF0F0 100%);
            border-radius: 16px;
            margin-bottom: 0.75rem;
            border: 1px solid rgba(255, 126, 139, 0.1);
            transition: all 0.3s ease;
        }

        .order-item:hover {
            background: linear-gradient(135deg, #FFE8E8 0%, #FFD6D6 100%);
            transform: translateX(4px);
        }

        .order-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 12px;
            border: 2px solid rgba(255, 126, 139, 0.2);
        }

        .order-item-details {
            flex: 1;
        }

        .order-item-name {
            font-weight: 600;
            color: #2D3748;
            margin-bottom: 0.25rem;
            font-size: 1rem;
        }

        .order-item-meta {
            font-size: 0.85rem;
            color: #718096;
            margin-bottom: 0.25rem;
        }

        .order-item-price {
            font-weight: 700;
            color: #FF7E8B;
            font-size: 1rem;
        }

        .order-total {
            background: linear-gradient(135deg, #FF7E8B 0%, #FF6B6B 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 16px;
            text-align: center;
            margin-top: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .order-total::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: shimmer 3s infinite;
        }

        .order-total h3 {
            font-size: 1.5rem;
            font-weight: 800;
            margin: 0;
            text-shadow: 0 1px 3px rgba(0,0,0,0.2);
            position: relative;
            z-index: 1;
        }

        /* Form Styles */
        .form-section {
            margin-bottom: 2rem;
        }

        .form-section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #2D3748;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-section-title i {
            color: #FF7E8B;
            font-size: 1.2rem;
        }

        .delivery-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .delivery-option {
            position: relative;
        }

        .delivery-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .delivery-label {
            display: block;
            padding: 1.25rem 1rem;
            background: white;
            border: 2px solid rgba(255, 126, 139, 0.2);
            border-radius: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            color: #4A5568;
        }

        .delivery-label:hover {
            border-color: #FF7E8B;
            background: rgba(255, 126, 139, 0.05);
        }

        .delivery-option input:checked + .delivery-label {
            background: linear-gradient(135deg, #FF7E8B 0%, #FF6B6B 100%);
            color: white;
            border-color: #FF7E8B;
            transform: scale(1.02);
            box-shadow: 0 8px 25px rgba(255, 126, 139, 0.3);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #2D3748;
            margin-bottom: 0.5rem;
            display: block;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid rgba(255, 126, 139, 0.2);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control:focus {
            border-color: #FF7E8B;
            box-shadow: 0 0 0 3px rgba(255, 126, 139, 0.1);
            outline: none;
        }

        .form-control::placeholder {
            color: #A0AEC0;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .payment-method {
            position: relative;
        }

        .payment-method input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .payment-label {
            display: block;
            padding: 1rem;
            background: white;
            border: 2px solid rgba(255, 126, 139, 0.2);
            border-radius: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            color: #4A5568;
        }

        .payment-label:hover {
            border-color: #FF7E8B;
            background: rgba(255, 126, 139, 0.05);
        }

        .payment-method input:checked + .payment-label {
            background: linear-gradient(135deg, #FF7E8B 0%, #FF6B6B 100%);
            color: white;
            border-color: #FF7E8B;
            transform: scale(1.02);
            box-shadow: 0 8px 25px rgba(255, 126, 139, 0.3);
        }

        .map-section {
            margin: 1.5rem 0;
        }

        .map-container {
            border-radius: 16px;
            overflow: hidden;
            border: 2px solid rgba(255, 126, 139, 0.2);
            box-shadow: 0 4px 20px rgba(255, 126, 139, 0.1);
            height: 300px;
        }

        .autocomplete-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid rgba(255, 126, 139, 0.2);
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(255, 126, 139, 0.15);
            z-index: 1000;
            max-height: 250px;
            overflow-y: auto;
        }

        .autocomplete-item {
            padding: 0.875rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid rgba(255, 126, 139, 0.1);
            transition: all 0.2s ease;
            color: #2D3748;
        }

        .autocomplete-item:hover,
        .autocomplete-item.active {
            background: linear-gradient(135deg, #FF7E8B 0%, #FF6B6B 100%);
            color: white;
        }

        .autocomplete-item:last-child {
            border-bottom: none;
        }

        .confirm-order-section {
            text-align: center;
            margin-top: 2rem;
        }

        .confirm-btn {
            background: linear-gradient(135deg, #FF7E8B 0%, #FF6B6B 100%);
            color: white;
            border: none;
            border-radius: 16px;
            padding: 1.25rem 3rem;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(255, 126, 139, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            min-width: 200px;
        }

        .confirm-btn:hover {
            background: linear-gradient(135deg, #FF5722 0%, #FF7E8B 100%);
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(255, 126, 139, 0.4);
        }

        .confirm-btn:active {
            transform: translateY(0);
        }

        .help-text {
            font-size: 0.85rem;
            color: #718096;
            margin-top: 0.5rem;
            font-style: italic;
        }

        .empty-cart {
            text-align: center;
            padding: 3rem 2rem;
            color: #A0AEC0;
        }

        .empty-cart i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Animations */
        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .checkout-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .checkout-card:nth-child(2) {
            animation-delay: 0.2s;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .checkout-header {
                padding: 2rem 0 3rem;
            }

            .checkout-header h1 {
                font-size: 2.2rem;
            }

            .checkout-content {
                padding: 1rem 0.5rem 3rem;
                margin-top: -1rem;
            }

            .checkout-grid {
                gap: 1.5rem;
            }

            .card-body-modern {
                padding: 1.5rem;
            }

            .delivery-options {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }

            .payment-methods {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }

            .map-container {
                height: 250px;
            }

            .confirm-btn {
                width: 100%;
                min-width: unset;
            }
        }

        @media (max-width: 480px) {
            .checkout-header h1 {
                font-size: 1.8rem;
            }

            .card-body-modern {
                padding: 1rem;
            }

            .order-item {
                padding: 0.75rem;
                gap: 0.75rem;
            }

            .order-item img {
                width: 50px;
                height: 50px;
            }
        }

        /* Leaflet custom styles */
        .leaflet-control-container .leaflet-control {
            border-radius: 8px !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1) !important;
        }

        .leaflet-control-zoom-in,
        .leaflet-control-zoom-out {
            background: rgba(255,126,139,0.9) !important;
            color: white !important;
            border: none !important;
            font-weight: bold !important;
        }

        .leaflet-control-zoom-in:hover,
        .leaflet-control-zoom-out:hover {
            background: rgba(255,107,107,0.95) !important;
        }
    </style>
</head>
<body>
    <div class="checkout-wrapper">
        <!-- Hero Section -->
        <section class="checkout-header">
            <h1>🛒 Finalizar Pedido</h1>
            <p>Completa tu orden y recibe tu comida deliciosa</p>
        </section>

        <main class="checkout-content">
            <div class="checkout-grid">
                <!-- Order Summary -->
                <div class="checkout-card">
                    <div class="card-header-modern">
                        <h2><i class="fas fa-shopping-cart"></i> Resumen del Pedido</h2>
                    </div>
                    <div class="card-body-modern">
                        <div id="order-items"></div>
                        <div class="order-total">
                            <h3>Total: S/ <span id="total">0.00</span></h3>
                        </div>
                    </div>
                </div>

                <!-- Payment Form -->
                <div class="checkout-card">
                    <div class="card-header-modern">
                        <h2><i class="fas fa-credit-card"></i> Detalles de Entrega y Pago</h2>
                    </div>
                    <div class="card-body-modern">
                        <form action="../controllers/process_order.php" method="post" onsubmit="return setCartData(event)">
                            <input type="hidden" id="cart_data" name="cart">
                            <input type="hidden" id="tipo_entrega" name="tipo_entrega" value="DELIVERY">
                            <input type="hidden" id="delivery_lat" name="delivery_lat" value="">
                            <input type="hidden" id="delivery_lng" name="delivery_lng" value="">

                            <div class="form-section">
                                <h3 class="form-section-title"><i class="fas fa-truck"></i>Tipo de Entrega</h3>
                                <div class="delivery-options">
                                    <div class="delivery-option">
                                        <input type="radio" id="pickup" name="entrega" value="PICKUP" onchange="onEntregaChange(this.value)">
                                        <label for="pickup" class="delivery-label">
                                            <i class="fas fa-store me-2"></i><br>
                                            <strong>Recoger en Local</strong>
                                        </label>
                                    </div>
                                    <div class="delivery-option">
                                        <input type="radio" id="delivery" name="entrega" value="DELIVERY" checked onchange="onEntregaChange(this.value)">
                                        <label for="delivery" class="delivery-label">
                                            <i class="fas fa-motorcycle me-2"></i><br>
                                            <strong>Delivery a Domicilio</strong>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section" id="direccion-group">
                                <h3 class="form-section-title"><i class="fas fa-map-marker-alt"></i>Dirección de Entrega</h3>
                                <div style="position: relative;">
                                    <input type="text" id="direccion" name="direccion" class="form-control" placeholder="Calle, Número, Ciudad, Arequipa" autocomplete="off" required>
                                    <div id="autocomplete-results" class="autocomplete-results" style="display: none;"></div>
                                </div>
                                <div class="map-section">
                                    <div id="map-picker" class="map-container"></div>
                                    <div id="map-info" class="help-text" style="display: block; background: #f8f9fa; padding: 0.5rem; border-radius: 0.375rem; border: 1px solid #e2e8f0; margin-bottom: 0.5rem;"><i class="fas fa-info-circle me-1"></i>Arrastra el marcador o busca una dirección para ver la ubicación aquí.</div>
                                    <button id="get-location-btn-checkout" class="btn btn-primary mt-2 mb-3">
                                        <i class="fas fa-map-marker-alt me-2"></i>Usar mi ubicación actual
                                    </button>
                                    <p class="help-text"><i class="fas fa-info-circle me-1"></i>Arrastra el marcador para ajustar la ubicación exacta. También puedes buscar con autocomplete.</p>
                                </div>
                            </div>

                            <div class="form-section">
                                <h3 class="form-section-title"><i class="fas fa-money-bill-wave"></i>Método de Pago</h3>
                                <div class="payment-methods">
                                    <div class="payment-method">
                                        <input type="radio" id="tarjeta" name="metodo_pago" value="TARJETA" checked>
                                        <label for="tarjeta" class="payment-label">
                                            <i class="fas fa-credit-card me-2"></i><br>
                                            <strong>Tarjeta</strong>
                                        </label>
                                    </div>
                                    <div class="payment-method">
                                        <input type="radio" id="efectivo" name="metodo_pago" value="EFECTIVO">
                                        <label for="efectivo" class="payment-label">
                                            <i class="fas fa-money-bill me-2"></i><br>
                                            <strong>Efectivo</strong>
                                        </label>
                                    </div>
                                    <div class="payment-method">
                                        <input type="radio" id="digital" name="metodo_pago" value="BILLETERA_DIGITAL">
                                        <label for="digital" class="payment-label">
                                            <i class="fas fa-mobile-alt me-2"></i><br>
                                            <strong>Digital</strong>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h3 class="form-section-title"><i class="fas fa-gift"></i>Propina Opcional</h3>
                                <input type="number" id="propina" name="propina" class="form-control" step="0.01" value="0" min="0" placeholder="0.00">
                            </div>

                            <div class="confirm-order-section">
                                <button type="submit" class="confirm-btn">
                                    <i class="fas fa-check-circle me-2"></i>Confirmar Pedido
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        function loadOrder() {
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            let itemsDiv = document.getElementById('order-items');
            let total = 0;
            itemsDiv.innerHTML = '';

            if (cart.length === 0) {
                itemsDiv.innerHTML = `
                    <div class="empty-cart">
                        <i class="fas fa-shopping-cart"></i>
                        <h4>Tu carrito está vacío</h4>
                        <p>Agrega algunos productos deliciosos</p>
                    </div>
                `;
                document.getElementById('total').textContent = '0.00';
                return;
            }

            cart.forEach(item => {
                let subtotal = item.price * item.quantity;
                total += subtotal;
                let image = item.image || 'https://via.placeholder.com/100x100?text=Producto';
                if (image.startsWith('../')) {
                    image = '/FOODAPP/foodapp_php/' + image.substring(3);
                }

                itemsDiv.innerHTML += `
                    <div class="order-item">
                        <img src="${image}" alt="${item.name}" loading="lazy">
                        <div class="order-item-details">
                            <div class="order-item-name">${item.name}</div>
                            <div class="order-item-meta">Cantidad: ${item.quantity}</div>
                            <div class="order-item-price">S/ ${(item.price * item.quantity).toFixed(2)}</div>
                        </div>
                    </div>
                `;
            });
            document.getElementById('total').textContent = total.toFixed(2);
        }

        function onEntregaChange(value) {
            const dirGroup = document.getElementById('direccion-group');
            const tipoInput = document.getElementById('tipo_entrega');
            const direccionInput = document.getElementById('direccion');

            if (value === 'PICKUP') {
                dirGroup.style.display = 'none';
                direccionInput.required = false;
                tipoInput.value = 'PICKUP';
            } else {
                dirGroup.style.display = 'block';
                direccionInput.required = true;
                tipoInput.value = 'DELIVERY';
            }
        }

        function setCartData(e) {
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            // Validate: if delivery selected, ensure address present
            const tipo = document.getElementById('tipo_entrega').value;
            const direccion = document.getElementById('direccion').value.trim();
            if (tipo === 'DELIVERY' && direccion.length === 0) {
                alert('Por favor ingresa la dirección de entrega o selecciona "Recoger en local".');
                if (e && typeof e.preventDefault === 'function') e.preventDefault();
                return false;
            }
            // ensure we have lat/lng when delivery
            if (tipo === 'DELIVERY') {
                const lat = document.getElementById('delivery_lat').value;
                const lng = document.getElementById('delivery_lng').value;
                if (!lat || !lng) {
                    if (!confirm('No se detectó la ubicación exacta en el mapa. Deseas continuar sin coordenadas?')) {
                        if (e && typeof e.preventDefault === 'function') e.preventDefault();
                        return false;
                    }
                }
            }
            document.getElementById('cart_data').value = JSON.stringify(cart);
            return true;
        }

        // Primary map picker using Leaflet + Nominatim (OpenStreetMap)
        let map, marker;
        const PLAZA_COORDS = { lat: -16.3989, lng: -71.5350 };

        function loadLeafletAssets(cb) {
            if (window.L) { cb(); return; }
            const css = document.createElement('link');
            css.rel = 'stylesheet';
            css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            document.head.appendChild(css);
            const s = document.createElement('script');
            s.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            s.onload = cb;
            document.head.appendChild(s);
        }

        function initLeafletPrimary() {
            loadLeafletAssets(() => {
                // Ubicación guardada del usuario
                const savedLocation = <?php echo isset($_SESSION['user_location']) ? json_encode($_SESSION['user_location']) : 'null'; ?>;
                const mapDiv = document.getElementById('map-picker');
                mapDiv.innerHTML = '';
                const defaultCenter = savedLocation ? [savedLocation.lat, savedLocation.lng] : [PLAZA_COORDS.lat, PLAZA_COORDS.lng];
                map = L.map(mapDiv).setView(defaultCenter, 14);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
                marker = L.marker(defaultCenter, {draggable: true, title: savedLocation ? 'Tu ubicación guardada' : 'Plaza de Armas (ubicación por defecto)'}).addTo(map);

                // Si hay ubicación guardada, actualizar inputs y hacer reverse geocoding
                if (savedLocation) {
                    updateLatLngInputs(savedLocation.lat, savedLocation.lng);
                    reverseGeocode(savedLocation.lat, savedLocation.lng);
                }

                // Try to use browser geolocation to move marker
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(pos => {
                        const p = [pos.coords.latitude, pos.coords.longitude];
                        map.setView(p, 15);
                        marker.setLatLng(p);
                        updateLatLngInputs(p[0], p[1]);
                        reverseGeocode(p[0], p[1]);
                    }, () => {
                        // ignore errors, keep default
                    });
                }

                marker.on('dragend', function() {
                    const p = marker.getLatLng();
                    updateLatLngInputs(p.lat, p.lng);
                    reverseGeocode(p.lat, p.lng);
                });

                map.on('click', function(e) {
                    marker.setLatLng(e.latlng);
                    updateLatLngInputs(e.latlng.lat, e.latlng.lng);
                    reverseGeocode(e.latlng.lat, e.latlng.lng);
                });

                // Autocomplete functionality
                const addressInput = document.getElementById('direccion');
                const autocompleteResults = document.getElementById('autocomplete-results');
                let autocompleteTimeout;
                let currentResults = [];
                let lastAutocompleteSelection = 0;

                addressInput.addEventListener('input', function() {
                    const query = this.value.trim();
                    console.log('Input event, query:', query); // Debug
                    clearTimeout(autocompleteTimeout);

                    if (query.length < 2) { // Reducido a 2 caracteres para testing
                        autocompleteResults.style.display = 'none';
                        return;
                    }

                    autocompleteTimeout = setTimeout(() => {
                        console.log('Calling searchAutocomplete with:', query); // Debug
                        searchAutocomplete(query);
                    }, 300);
                });

                addressInput.addEventListener('blur', function() {
                    // Delay hiding to allow clicks on results
                    setTimeout(() => {
                        autocompleteResults.style.display = 'none';
                    }, 200);
                });

                addressInput.addEventListener('keydown', function(e) {
                    const items = autocompleteResults.querySelectorAll('.autocomplete-item');
                    let activeItem = autocompleteResults.querySelector('.autocomplete-item.active');

                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        if (!activeItem && items.length > 0) {
                            items[0].classList.add('active');
                        } else if (activeItem && activeItem.nextElementSibling) {
                            activeItem.classList.remove('active');
                            activeItem.nextElementSibling.classList.add('active');
                        }
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        if (activeItem && activeItem.previousElementSibling) {
                            activeItem.classList.remove('active');
                            activeItem.previousElementSibling.classList.add('active');
                        } else if (activeItem) {
                            activeItem.classList.remove('active');
                        }
                    } else if (e.key === 'Enter') {
                        e.preventDefault();
                        if (activeItem) {
                            const index = Array.from(items).indexOf(activeItem);
                            if (currentResults && currentResults[index]) {
                                selectAutocompleteResult(currentResults[index]);
                            }
                        }
                    } else if (e.key === 'Escape') {
                        autocompleteResults.style.display = 'none';
                    }
                });

                // Close autocomplete when clicking outside
                document.addEventListener('click', function(e) {
                    if (!addressInput.contains(e.target) && !autocompleteResults.contains(e.target)) {
                        autocompleteResults.style.display = 'none';
                    }
                });

                // Geocode on address input blur (fallback for manual entry)
                addressInput.addEventListener('blur', function() {
                    const q = addressInput.value.trim();
                    if (!q) return;
                    
                    // Only geocode if no autocomplete selection was made in the last 500ms
                    if (Date.now() - lastAutocompleteSelection > 500) {
                        geocodeAddress(q);
                    }
                });
            });
        }

        function updateLatLngInputs(lat, lng) {
            document.getElementById('delivery_lat').value = parseFloat(lat).toFixed(8);
            document.getElementById('delivery_lng').value = parseFloat(lng).toFixed(8);
        }

        function reverseGeocode(lat, lng) {
            // Use local proxy to avoid CORS issues
            fetch('../controllers/proxy_nominatim.php?type=reverse&lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng))
                .then(r => r.json()).then(data => {
                    if (data && data.display_name) {
                        document.getElementById('direccion').value = data.display_name;
                        // Mostrar dirección automática en el div map-info
                        const mapInfo = document.getElementById('map-info');
                        mapInfo.innerHTML = '<i class="fas fa-map-marker-alt me-1"></i><strong>Dirección detectada:</strong> ' + data.display_name;
                        mapInfo.style.display = 'block';
                    }
                }).catch(() => {
                    // Ignore errors silently
                });
        }

        function searchAutocomplete(query) {
            // Search for addresses using local proxy to avoid CORS
            const searchQuery = query;
            console.log('Searching for:', searchQuery); // Debug
            fetch('../controllers/proxy_nominatim.php?type=search&q=' + encodeURIComponent(searchQuery))
                .then(r => r.json()).then(results => {
                    console.log('Raw results:', results); // Debug
                    // Filter results to Peru if possible
                    const peruResults = Array.isArray(results) ? results.filter(r => r.display_name && (r.display_name.includes('Peru') || r.display_name.includes('Perú'))) : [];
                    console.log('Filtered Peru results:', peruResults); // Debug
                    currentResults = peruResults.length > 0 ? peruResults : (Array.isArray(results) ? results : []);
                    showAutocompleteResults(currentResults);
                }).catch(error => {
                    console.error('Autocomplete error:', error); // Debug
                    autocompleteResults.style.display = 'none';
                    currentResults = [];
                });
        }

        function showAutocompleteResults(results) {
            console.log('showAutocompleteResults called with:', results); // Debug
            const autocompleteResults = document.getElementById('autocomplete-results');
            autocompleteResults.innerHTML = '';

            if (results && results.length > 0) {
                console.log('Showing', results.length, 'results'); // Debug
                results.forEach(result => {
                    const item = document.createElement('div');
                    item.className = 'autocomplete-item';
                    // Show shorter address for better UX
                    const shortAddress = result.display_name.split(',')[0] + ', ' + (result.display_name.split(',')[1] || '');
                    item.textContent = shortAddress.trim();
                    item.title = result.display_name; // Full address on hover
                    item.addEventListener('mousedown', (e) => {
                        e.preventDefault(); // Prevent blur
                        console.log('Selected result:', result); // Debug
                        selectAutocompleteResult(result);
                    });
                    autocompleteResults.appendChild(item);
                });
                autocompleteResults.style.display = 'block';
            } else {
                console.log('No results to show'); // Debug
                autocompleteResults.style.display = 'none';
            }
        }

        function selectAutocompleteResult(result) {
            const addressInput = document.getElementById('direccion');
            const autocompleteResults = document.getElementById('autocomplete-results');

            addressInput.value = result.display_name;
            autocompleteResults.style.display = 'none';
            lastAutocompleteSelection = Date.now();

            // Mostrar dirección seleccionada en el div map-info
            const mapInfo = document.getElementById('map-info');
            mapInfo.innerHTML = '<i class="fas fa-map-marker-alt me-1"></i><strong>Dirección seleccionada:</strong> ' + result.display_name;
            mapInfo.style.display = 'block';

            // Move map to selected location
            const lat = parseFloat(result.lat);
            const lon = parseFloat(result.lon);
            map.setView([lat, lon], 16);
            marker.setLatLng([lat, lon]);
            updateLatLngInputs(lat, lon);
        }

        // Add event listener for get location button
        const getLocationBtn = document.getElementById('get-location-btn-checkout');
        if (getLocationBtn) {
            getLocationBtn.addEventListener('click', function() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        map.setView([lat, lng], 16);
                        marker.setLatLng([lat, lng]);
                        updateLatLngInputs(lat, lng);
                        reverseGeocode(lat, lng);
                    }, function(error) {
                        alert('Error obteniendo ubicación: ' + error.message);
                    });
                } else {
                    alert('Geolocalización no soportada por este navegador.');
                }
            });
        }

        function geocodeAddress(q) {
            fetch('../controllers/proxy_nominatim.php?type=search&q=' + encodeURIComponent(q))
                .then(r => r.json()).then(results => {
                    if (Array.isArray(results) && results.length > 0) {
                        const first = results[0];
                        const lat = parseFloat(first.lat);
                        const lon = parseFloat(first.lon);
                        map.setView([lat, lon], 16);
                        marker.setLatLng([lat, lon]);
                        updateLatLngInputs(lat, lon);
                    }
                }).catch(() => {});
        }

        // initialize primary Leaflet map
        onEntregaChange('DELIVERY');
        loadOrder();
        initLeafletPrimary();
    </script>
</body>
</html>
