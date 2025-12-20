<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguimiento - FoodApp</title>
    <link rel="icon" href="../assets/FOODAPP.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        body { font-family: 'Nunito', sans-serif; background: linear-gradient(180deg,#fbfbfd 0%, #f3f6fb 100%); }
        .navbar-brand { color: #ff441f !important; font-weight: 800; }
        .btn-custom { background: #ff441f; border-color: #ff441f; border-radius: 999px; padding: .6rem .9rem; }
        .btn-custom:hover { background: #e63946; border-color: #e63946; }
        .text-custom { color: #ff441f; }
        .card { border-radius: 14px; box-shadow: 0 6px 20px rgba(16,24,40,0.08); }
        .status-badge { font-size: 0.9rem; padding: 0.375rem 0.75rem; border-radius: 20px; }
        .status-entregado { background: #d4edda; color: #155724; }
        .status-en_camino { background: #fff3cd; color: #856404; }
        .status-preparando { background: #cce5ff; color: #004085; }
        .status-listo { background: #d1ecf1; color: #0c5460; }
        .status-pendiente { background: #f8d7da; color: #721c24; }
        .status-cancelado { background: #e2e3e5; color: #383d41; }
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
                <a class="nav-link" href="search.php">Buscar</a>
            </div>
        </div>
    </nav>
    <main class="d-flex justify-content-center align-items-center min-vh-100">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-6">
                    <div class="card p-4">
                        <div class="text-center mb-4">
                            <img src="../assets/FOODAPP.png" alt="Logo" class="login-logo mb-2" style="width: 60px; height: 60px;">
                            <h3 class="mb-0">Seguimiento de Pedido</h3>
                        </div>

                        <?php
                        include_once '../includes/config.php';
                        $pedido_id = intval($_GET['id'] ?? 0);
                        if ($pedido_id <= 0) {
                            echo '<div class="alert alert-danger">ID de pedido inválido.</div>';
                        } else {
                            // Get order details with restaurant and delivery info
                            $sql = "SELECT p.*, r.nombre as restaurante_nombre, r.direccion as restaurante_direccion, 
                                           u.nombre as repartidor_nombre, c.nombre as cliente_nombre
                                    FROM pedidos p
                                    LEFT JOIN restaurantes r ON p.restaurante_id = r.id
                                    LEFT JOIN usuarios u ON p.repartidor_id = u.id
                                    LEFT JOIN clientes cl ON p.cliente_id = cl.id
                                    LEFT JOIN usuarios c ON cl.id = c.id
                                    WHERE p.id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param('i', $pedido_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result->num_rows > 0) {
                                $pedido = $result->fetch_assoc();
                                
                                // Get status badge class
                                $status_class = 'status-pendiente';
                                switch (trim($pedido['estado'])) {
                                    case 'ENTREGADO': $status_class = 'status-entregado'; break;
                                    case 'EN_CAMINO': $status_class = 'status-en_camino'; break;
                                    case 'PREPARANDO': $status_class = 'status-preparando'; break;
                                    case 'LISTO': $status_class = 'status-listo'; break;
                                    case 'CANCELADO': $status_class = 'status-cancelado'; break;
                                }
                                
                                echo '<div class="mb-3">';
                                echo '<h5 class="mb-3"><i class="fas fa-receipt me-2"></i>Pedido #' . $pedido['id'] . '</h5>';
                                echo '<div class="row mb-2">';
                                echo '<div class="col-6"><strong>Estado:</strong></div>';
                                echo '<div class="col-6"><span class="status-badge ' . $status_class . '">' . $pedido['estado'] . '</span></div>';
                                echo '</div>';
                                echo '<div class="row mb-2">';
                                echo '<div class="col-6"><strong>Fecha:</strong></div>';
                                echo '<div class="col-6">' . date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])) . '</div>';
                                echo '</div>';
                                echo '<div class="row mb-2">';
                                echo '<div class="col-6"><strong>Total:</strong></div>';
                                echo '<div class="col-6">S/ ' . number_format($pedido['total'], 2) . '</div>';
                                echo '</div>';
                                echo '</div>';
                                
                                // Cancel order button if still pending
                                if ($pedido['estado'] == 'PENDIENTE') {
                                    echo '<div class="text-center mb-3">';
                                    echo '<button type="button" class="btn btn-outline-danger" onclick="cancelOrder(' . $pedido['id'] . ')">';
                                    echo '<i class="fas fa-times me-2"></i>Cancelar Pedido';
                                    echo '</button>';
                                    echo '</div>';
                                } elseif ($pedido['estado'] == 'CANCELADO') {
                                    echo '<div class="text-center mb-3">';
                                    echo '<div class="alert alert-secondary py-2">';
                                    echo '<i class="fas fa-info-circle me-2"></i>Este pedido ha sido cancelado.';
                                    echo '</div>';
                                    echo '</div>';
                                }
                                
                                // Restaurant info
                                echo '<div class="mb-3">';
                                echo '<h6 class="text-custom"><i class="fas fa-store me-2"></i>Restaurante</h6>';
                                echo '<p class="mb-1"><strong>' . htmlspecialchars($pedido['restaurante_nombre'] ?? 'N/A') . '</strong></p>';
                                if (!empty($pedido['restaurante_direccion'])) {
                                    echo '<p class="mb-0 small text-muted">' . htmlspecialchars($pedido['restaurante_direccion']) . '</p>';
                                }
                                echo '</div>';
                                
                                // Delivery person info
                                if (!empty($pedido['repartidor_nombre'])) {
                                    echo '<div class="mb-3">';
                                    echo '<h6 class="text-custom"><i class="fas fa-motorcycle me-2"></i>Repartidor</h6>';
                                    echo '<p class="mb-0">' . htmlspecialchars($pedido['repartidor_nombre']) . '</p>';
                                    echo '</div>';
                                }
                                
                                // Client info
                                echo '<div class="mb-3">';
                                echo '<h6 class="text-custom"><i class="fas fa-user me-2"></i>Cliente</h6>';
                                echo '<p class="mb-0">' . htmlspecialchars($pedido['cliente_nombre'] ?? 'N/A') . '</p>';
                                echo '<p class="mb-0 small text-muted">' . htmlspecialchars($pedido['direccion_entrega']) . '</p>';
                                echo '</div>';
                                
                                // Products
                                echo '<div class="mb-3">';
                                echo '<h6 class="text-custom"><i class="fas fa-utensils me-2"></i>Productos</h6>';
                                $products_sql = "SELECT dp.cantidad, dp.precio, p.nombre 
                                                FROM detalle_pedido dp 
                                                LEFT JOIN productos p ON dp.producto_id = p.id 
                                                WHERE dp.pedido_id = ?";
                                $products_stmt = $conn->prepare($products_sql);
                                $products_stmt->bind_param('i', $pedido_id);
                                $products_stmt->execute();
                                $products_result = $products_stmt->get_result();
                                
                                if ($products_result->num_rows > 0) {
                                    echo '<ul class="list-group list-group-flush">';
                                    while ($product = $products_result->fetch_assoc()) {
                                        echo '<li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">';
                                        echo '<div>';
                                        echo '<strong>' . htmlspecialchars($product['nombre'] ?? 'Producto') . '</strong>';
                                        echo '<span class="badge bg-secondary ms-2">x' . intval($product['cantidad']) . '</span>';
                                        echo '</div>';
                                        echo '<span>S/ ' . number_format($product['precio'], 2) . '</span>';
                                        echo '</li>';
                                    }
                                    echo '</ul>';
                                } else {
                                    echo '<p class="text-muted">No hay productos registrados.</p>';
                                }
                                $products_stmt->close();
                                echo '</div>';
                                
                                // Map section
                                echo '<div class="mb-3">';
                                echo '<h6 class="text-custom"><i class="fas fa-map-marked-alt me-2"></i>Seguimiento en Mapa</h6>';
                                echo '<div id="tracking-map" style="height: 300px; border-radius: 10px;"></div>';
                                echo '<div id="delivery-info" class="mt-3 p-3 bg-light rounded">';
                                echo '<h6 class="text-custom mb-3"><i class="fas fa-clock me-2"></i>Información de Entrega</h6>';
                                echo '<div id="eta-info">Calculando ruta y tiempo estimado...</div>';
                                echo '</div>';
                                echo '</div>';
                                
                                // Rating button if delivered
                                if ($pedido['estado'] == 'ENTREGADO') {
                                    echo '<div class="text-center mt-4">';
                                    echo '<a href="ratings.php?id=' . $pedido['id'] . '" class="btn btn-custom">';
                                    echo '<i class="fas fa-star me-2"></i>Valorar Pedido';
                                    echo '</a>';
                                    echo '</div>';
                                }
                                
                            } else {
                                echo '<div class="alert alert-danger">Pedido no encontrado.</div>';
                            }
                            $stmt->close();
                        }
                        $conn->close();
                        ?>
                        
                        <script>
                        const restaurantAddress = "<?php echo isset($pedido) ? addslashes($pedido['restaurante_direccion'] ?? '') : ''; ?>";
                        const clientAddress = "<?php echo isset($pedido) ? addslashes($pedido['direccion_entrega'] ?? '') : ''; ?>";
                        </script>
                        
                        <!-- Cancel Order Modal -->
                        <div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title text-danger" id="cancelOrderModalLabel">
                                            <i class="fas fa-exclamation-triangle me-2"></i>Cancelar Pedido
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="text-center mb-4">
                                            <div class="mb-3">
                                                <i class="fas fa-times-circle fa-4x text-danger"></i>
                                            </div>
                                            <h5 class="mb-3">¿Estás seguro?</h5>
                                            <p class="text-muted mb-0">
                                                Estás a punto de <strong>cancelar el pedido #<span id="cancelOrderId"></span></strong>.<br>
                                                Esta acción <strong class="text-danger">no se puede deshacer</strong> y el pedido será eliminado permanentemente.
                                            </p>
                                        </div>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <strong>Nota:</strong> Si el pedido ya fue tomado por un repartidor, contacta directamente con el restaurante.
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                            <i class="fas fa-arrow-left me-2"></i>Mantener Pedido
                                        </button>
                                        <button type="button" class="btn btn-danger" id="confirmCancelBtn">
                                            <i class="fas fa-times me-2"></i>Sí, Cancelar Pedido
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <footer class="bg-light py-4 text-center border-top">
        <div class="container">
            <p class="text-muted mb-0">© 2025 FoodApp. Todos los derechos reservados.</p>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        let currentOrderId = null;
        
        function cancelOrder(orderId) {
            currentOrderId = orderId;
            document.getElementById('cancelOrderId').textContent = orderId;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('cancelOrderModal'));
            modal.show();
        }
        
        // Handle confirm cancel button
        document.getElementById('confirmCancelBtn').addEventListener('click', function() {
            if (!currentOrderId) return;
            
            // Hide modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('cancelOrderModal'));
            modal.hide();
            
            // Show loading state on the cancel button
            const cancelBtn = document.querySelector('button[onclick*="cancelOrder(' + currentOrderId + ')"]');
            if (cancelBtn) {
                const originalText = cancelBtn.innerHTML;
                cancelBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Cancelando...';
                cancelBtn.disabled = true;
                
                // Send cancel request
                fetch('../controllers/cancel_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'order_id=' + currentOrderId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message and reload page
                        showAlert('Pedido cancelado exitosamente.', 'success');
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showAlert('Error al cancelar el pedido: ' + (data.message || 'Error desconocido'), 'danger');
                        // Restore button
                        cancelBtn.innerHTML = originalText;
                        cancelBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Error de conexión. Inténtalo de nuevo.', 'danger');
                    // Restore button
                    cancelBtn.innerHTML = originalText;
                    cancelBtn.disabled = false;
                });
            }
        });
        
        function showAlert(message, type = 'info') {
            // Create alert element
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        // Map functions
        async function geocode(address) {
            if (!address) return null;
            try {
                const response = await fetch('../controllers/proxy_nominatim.php?q=' + encodeURIComponent(address));
                const data = await response.json();
                if (data && data.length > 0) {
                    return [parseFloat(data[0].lat), parseFloat(data[0].lon)];
                }
            } catch (error) {
                console.error('Geocoding error:', error);
            }
            return null;
        }

        async function initMap() {
            if (!restaurantAddress || !clientAddress) return;

            const restCoords = await geocode(restaurantAddress);
            const clientCoords = await geocode(clientAddress);

            if (!restCoords || !clientCoords) {
                console.log('Could not geocode addresses');
                return;
            }

            const map = L.map('tracking-map').setView(restCoords, 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            L.marker(restCoords).addTo(map).bindPopup('Restaurante: ' + restaurantAddress);
            L.marker(clientCoords).addTo(map).bindPopup('Cliente: ' + clientAddress);

            // Draw route
            try {
                const routeResponse = await fetch('../controllers/proxy_ors.php?start=' + restCoords[1] + ',' + restCoords[0] + '&end=' + clientCoords[1] + ',' + clientCoords[0]);
                const routeData = await routeResponse.json();
                if (routeData && routeData.features && routeData.features[0]) {
                    const coordinates = routeData.features[0].geometry.coordinates.map(coord => [coord[1], coord[0]]);
                    L.polyline(coordinates, {color: 'blue', weight: 4}).addTo(map);
                    map.fitBounds([restCoords, clientCoords]);

                    // Calculate ETA and display info
                    let summary;
                    if (routeData.features[0].properties.summary) {
                        summary = routeData.features[0].properties.summary;
                    } else if (routeData.features[0].properties.segments && routeData.features[0].properties.segments[0]) {
                        summary = routeData.features[0].properties.segments[0];
                    } else if (routeData.features[0].properties.distance !== undefined) {
                        summary = routeData.features[0].properties;
                    } else {
                        throw new Error('No route summary available');
                    }

                    const distance = summary.distance; // in meters
                    const duration = summary.duration; // in seconds

                    const eta = new Date(Date.now() + duration * 1000);
                    const etaString = eta.toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'});
                    const distanceKm = (distance / 1000).toFixed(1);
                    const durationMin = Math.round(duration / 60);

                    document.getElementById('eta-info').innerHTML = `
                        <div class="row">
                            <div class="col-4">
                                <i class="fas fa-route text-primary"></i>
                                <strong>Distancia:</strong><br>
                                ${distanceKm} km
                            </div>
                            <div class="col-4">
                                <i class="fas fa-clock text-warning"></i>
                                <strong>Tiempo:</strong><br>
                                ${durationMin} min
                            </div>
                            <div class="col-4">
                                <i class="fas fa-calendar-check text-success"></i>
                                <strong>Llegada:</strong><br>
                                ${etaString}
                            </div>
                        </div>
                    `;
                } else {
                    document.getElementById('eta-info').innerHTML = '<p class="text-muted">No se pudo calcular la ruta.</p>';
                }
            } catch (error) {
                console.error('Route error:', error);
                document.getElementById('eta-info').innerHTML = '<p class="text-muted">Error al calcular la ruta.</p>';
            }
        }

        // Initialize map when page loads
        if (restaurantAddress && clientAddress) {
            initMap();
        }
    </script>
</body>
</html>
