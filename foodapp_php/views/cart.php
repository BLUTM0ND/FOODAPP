<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito - FoodApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <main class="container mt-4">
        <h2 class="mb-4"><i class="fas fa-shopping-cart"></i> Tu Carrito de Compras</h2>
        <div id="cart-items" class="row"></div>
        <div id="cart-summary" class="cart-summary card mt-4 p-3" style="display: none;">
            <h3>Total: S/ <span id="total" class="text-primary">0.00</span></h3>
            <a href="checkout.php" id="checkout-btn" class="btn btn-success btn-lg">Proceder al Pago</a>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function loadCart() {
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            let cartDiv = document.getElementById('cart-items');
            let total = 0;
            cartDiv.innerHTML = '';
            if (cart.length === 0) {
                cartDiv.innerHTML = '<div class="col-12"><div class="alert alert-info text-center"><i class="fas fa-shopping-cart fa-3x mb-3"></i><h4>Tu carrito está vacío</h4><p>Agrega algunos productos deliciosos</p><a href="restaurantes.php" class="btn btn-primary">Ver Restaurantes</a></div></div>';
                document.getElementById('cart-summary').style.display = 'none';
                return;
            }
            for (let i = 0; i < cart.length; i++) {
                let item = cart[i];
                let subtotal = item.price * item.quantity;
                total += subtotal;
                let image = item.image || 'https://via.placeholder.com/150x150?text=Producto';
                if (image.startsWith('../')) {
                    image = '/FOODAPP/foodapp_php/' + image.substring(3);
                }
                cartDiv.innerHTML += `
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <img src="${image}" class="card-img-top" alt="${item.name}" style="height: 150px; object-fit: cover;">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">${item.name}</h5>
                                <p class="card-text text-primary fw-bold">S/ ${item.price.toFixed(2)}</p>
                                <div class="quantity-controls d-flex align-items-center justify-content-center mb-3">
                                    <button onclick="updateQuantity(${i}, ${item.quantity - 1})" class="btn btn-outline-secondary btn-sm" ${item.quantity <= 1 ? 'disabled' : ''}>-</button>
                                    <input type="number" value="${item.quantity}" min="1" onchange="updateQuantity(${i}, parseInt(this.value))" class="form-control form-control-sm mx-2" style="width: 60px;">
                                    <button onclick="updateQuantity(${i}, ${item.quantity + 1})" class="btn btn-outline-secondary btn-sm">+</button>
                                </div>
                                <p class="card-text">Subtotal: <strong>S/ ${subtotal.toFixed(2)}</strong></p>
                                <button onclick="removeItem(${i})" class="btn btn-danger mt-auto">Eliminar</button>
                            </div>
                        </div>
                    </div>
                `;
            }
            document.getElementById('total').textContent = total.toFixed(2);
            document.getElementById('cart-summary').style.display = 'block';
        }

        function updateQuantity(index, quantity) {
            quantity = parseInt(quantity);
            if (quantity < 1 || isNaN(quantity)) return;
            let cart = JSON.parse(localStorage.getItem('cart'));
            cart[index].quantity = quantity;
            localStorage.setItem('cart', JSON.stringify(cart));
            loadCart();
            updateCartCount();
        }

        function removeItem(index) {
            let cart = JSON.parse(localStorage.getItem('cart'));
            cart.splice(index, 1);
            localStorage.setItem('cart', JSON.stringify(cart));
            loadCart();
            updateCartCount();
            showToast('Producto eliminado del carrito');
        }

        function updateCartCount() {
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            let count = cart.reduce((sum, item) => sum + item.quantity, 0);
            const cartCountEl = document.getElementById('cart-count');
            if (cartCountEl) cartCountEl.textContent = count;
        }

        function showToast(message) {
            const toastEl = document.getElementById('cart-toast');
            if (toastEl) {
                toastEl.querySelector('.toast-body').textContent = message;
                const toast = new bootstrap.Toast(toastEl);
                toast.show();
            }
        }

        loadCart();
    </script>
</body>
</html>
