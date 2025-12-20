<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'repartidor') {
    header('Location: login.php');
    exit;
}
include_once '../includes/config.php';

$user_id = $_SESSION['user_id'];

// Get delivery person info
$repartidor_query = $conn->prepare("SELECT r.*, u.nombre, u.email FROM repartidores r JOIN usuarios u ON r.id = u.id WHERE u.id = ?");
$repartidor_query->bind_param('i', $user_id);
$repartidor_query->execute();
$repartidor_result = $repartidor_query->get_result();
$repartidor = $repartidor_result->fetch_assoc();
$repartidor_query->close();

// Handle taking an order
if (isset($_POST['take_order']) && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
    $update = $conn->prepare("UPDATE pedidos SET repartidor_id = ?, estado = 'EN_CAMINO' WHERE id = ? AND repartidor_id IS NULL AND estado = 'PENDIENTE'");
    $update->bind_param('ii', $user_id, $order_id);
    if ($update->execute() && $update->affected_rows > 0) {
        $success_message = "Pedido tomado exitosamente.";
    } else {
        $error_message = "No se pudo tomar el pedido. Puede que ya haya sido asignado.";
    }
    $update->close();
}

// Handle updating order status
if (isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];

    // Verify the order belongs to this delivery person
    $check = $conn->prepare("SELECT id FROM pedidos WHERE id = ? AND repartidor_id = ?");
    $check->bind_param('ii', $order_id, $user_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $update = $conn->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
        $update->bind_param('si', $status, $order_id);
        $update->execute();
        $update->close();

        if ($status == 'ENTREGADO') {
            $success_message = "Pedido marcado como entregado.";
        }
    }
    $check->close();
}

// Get available orders (PENDIENTE status, no delivery person assigned)
$available_orders = $conn->query("
    SELECT p.*, r.nombre as restaurante_nombre, r.direccion as restaurante_direccion,
           u.nombre as cliente_nombre
    FROM pedidos p
    JOIN restaurantes r ON p.restaurante_id = r.id
    LEFT JOIN clientes cl ON p.cliente_id = cl.id
    LEFT JOIN usuarios u ON cl.id = u.id
    WHERE p.estado = 'PENDIENTE' AND p.repartidor_id IS NULL
    ORDER BY p.fecha_pedido ASC
");

// Get orders assigned to this delivery person
$my_orders = $conn->prepare("
    SELECT p.*, r.nombre as restaurante_nombre, r.direccion as restaurante_direccion,
           u.nombre as cliente_nombre
    FROM pedidos p
    JOIN restaurantes r ON p.restaurante_id = r.id
    LEFT JOIN clientes cl ON p.cliente_id = cl.id
    LEFT JOIN usuarios u ON cl.id = u.id
    WHERE p.repartidor_id = ? AND p.estado IN ('EN_CAMINO', 'ENTREGADO')
    ORDER BY p.fecha_pedido DESC
");
$my_orders->bind_param('i', $user_id);
$my_orders->execute();
$my_orders_result = $my_orders->get_result();
$my_orders->close();

// Get earnings statistics
$earnings_query = $conn->prepare("
    SELECT
        COUNT(*) as total_deliveries,
        SUM(CASE WHEN estado = 'ENTREGADO' THEN 1 ELSE 0 END) as completed_deliveries,
        SUM(CASE WHEN estado = 'ENTREGADO' THEN total * 0.1 ELSE 0 END) as total_earnings,
        AVG(CASE WHEN estado = 'ENTREGADO' THEN total * 0.1 ELSE NULL END) as avg_earning_per_delivery
    FROM pedidos
    WHERE repartidor_id = ? AND estado = 'ENTREGADO'
");
$earnings_query->bind_param('i', $user_id);
$earnings_query->execute();
$earnings_result = $earnings_query->get_result();
$earnings = $earnings_result->fetch_assoc();
$earnings_query->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Repartidor - <?php echo htmlspecialchars($repartidor['nombre']); ?> - FoodApp</title>
    <link rel="icon" href="../assets/FOODAPP.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet.locatecontrol@0.79.0/dist/L.Control.Locate.min.css" />
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        body { font-family: 'Nunito', sans-serif; background: #f8f9fa; }
        .delivery-container { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: linear-gradient(135deg, #ff441f 0%, #ff7d00 100%); color: white; padding: 1rem; position: fixed; height: 100vh; overflow-y: auto; box-shadow: 2px 0 10px rgba(0,0,0,0.1); }
        .sidebar h2 { margin-bottom: 1rem; text-align: center; font-size: 1.2rem; }
        .sidebar ul { list-style: none; padding: 0; }
        .sidebar li { margin: 0.5rem 0; }
        .sidebar a { color: white; text-decoration: none; display: block; padding: 0.75rem; border-radius: 10px; transition: all 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.2); transform: translateX(5px); }
        .main-content { margin-left: 250px; padding: 2rem; width: calc(100% - 250px); }
        .card { background: white; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); transition: transform 0.3s, box-shadow 0.3s; }
        .card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
        .btn { background: linear-gradient(135deg, #ff441f 0%, #ff7d00 100%); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 25px; cursor: pointer; transition: all 0.3s; font-weight: 600; }
        .btn:hover { background: linear-gradient(135deg, #e63946 0%, #ff6b35 100%); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(255,68,31,0.3); }
        .btn-success { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
        .btn-success:hover { background: linear-gradient(135deg, #218838 0%, #17a2b8 100%); }
        .btn-warning { background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); color: #212529; }
        .btn-warning:hover { background: linear-gradient(135deg, #e0a800 0%, #e8680d 100%); }
        .btn-info { background: linear-gradient(135deg, #0dcaf0 0%, #3cc2e0 100%); color: white; }
        .btn-info:hover { background: linear-gradient(135deg, #0aa2c0 0%, #2da8c0 100%); }
        .order-card { margin-bottom: 1rem; border-left: 4px solid #ff441f; }
        .order-card.available { border-left-color: #28a745; }
        .order-card.mine { border-left-color: #007bff; }
        .stats-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .stats-card .card-body { padding: 2rem; }
        .stats-card h3 { margin-bottom: 1rem; font-size: 2rem; }
        .stats-card p { margin-bottom: 0.5rem; font-size: 1.1rem; }
        .section { display: none; }
        .section.active { display: block; }
        .suggestion-item:hover { background-color: #f8f9fa !important; }
        .leaflet-control-locate { background-color: #ff441f !important; border-color: #ff441f !important; }
        .leaflet-control-locate:hover { background-color: #e63946 !important; }
        .map-controls { background: rgba(255, 255, 255, 0.9); border-radius: 10px; padding: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        #map { height: 500px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); max-width: 100%; }
        .card-body.p-0 { overflow: hidden; border-radius: 15px; }
        .map-section-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; border-radius: 15px; margin-bottom: 2rem; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .search-container { background: white; border-radius: 15px; padding: 1.5rem; box-shadow: 0 3px 10px rgba(0,0,0,0.1); margin-bottom: 1rem; }
        .map-container {
            position: relative;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .map-legend {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            min-width: 200px;
            margin: 0 auto;
        }

        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .legend-color {
            width: 20px;
            height: 4px;
            margin-right: 10px;
            border-radius: 2px;
        }

        .route-popup {
            max-width: 250px;
        }

        .route-popup h6 {
            margin-bottom: 10px;
            font-size: 1rem;
        }

        .route-popup .badge {
            font-size: 0.75rem;
        }

        .order-marker {
            background: none !important;
            border: none !important;
        }

        .restaurant-marker-icon {
            background: linear-gradient(135deg, #ff441f, #ff7d00);
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(255,68,31,0.3);
            border: 2px solid white;
        }

        .delivery-marker-icon {
            background: linear-gradient(135deg, #28a745, #20c997);
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(40,167,69,0.3);
            border: 2px solid white;
        }

        .available-marker-icon {
            background: linear-gradient(135deg, #fd7e14, #ffc107);
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            box-shadow: 0 2px 8px rgba(253,126,20,0.3);
            border: 2px solid white;
        }

        .route-line-active {
            filter: drop-shadow(0 0 6px rgba(255, 68, 31, 0.6));
        }

        .route-info-panel {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(248, 249, 250, 0.95));
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }

        .route-info-panel .card-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            border: none;
        }

        .pulse-animation {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <div class="delivery-container">
        <aside class="sidebar">
            <h2>Panel Repartidor</h2>
            <p class="text-center small"><?php echo htmlspecialchars($repartidor['nombre']); ?></p>
            <ul>
                <li><a href="#" class="nav-link active" data-section="available">📦 Pedidos Disponibles</a></li>
                <li><a href="#" class="nav-link" data-section="my-orders">🚚 Mis Entregas</a></li>
                <li><a href="#" class="nav-link" data-section="earnings">💰 Ganancias</a></li>
                <li><a href="#" class="nav-link" data-section="map-section">🗺️ Mapa</a></li>
                <li><a href="#" class="nav-link" data-section="profile">👤 Mi Perfil</a></li>
                <li><a href="../controllers/logout.php">🚪 Cerrar Sesión</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <!-- Success/Error Messages -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Available Orders Section -->
            <section id="available" class="section active">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-clipboard-list"></i> Pedidos Disponibles</h1>
                    <span class="badge bg-success fs-6"><?php echo $available_orders->num_rows; ?> disponibles</span>
                </div>

                <?php if ($available_orders->num_rows == 0): ?>
                    <div class="card p-5 text-center">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h3>No hay pedidos disponibles</h3>
                        <p class="text-muted">Los pedidos disponibles aparecerán aquí cuando los clientes realicen nuevos pedidos.</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php while ($order = $available_orders->fetch_assoc()): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card order-card available">
                                    <div class="card-body">
                                        <h5 class="card-title">Pedido #<?php echo $order['id']; ?></h5>
                                        <div class="mb-2">
                                            <span class="status-badge status-listo">LISTO PARA ENTREGA</span>
                                        </div>
                                        <p class="card-text">
                                            <strong>Restaurante:</strong> <?php echo htmlspecialchars($order['restaurante_nombre']); ?><br>
                                            <strong>Cliente:</strong> <?php echo htmlspecialchars($order['cliente_nombre'] ?? 'No especificado'); ?><br>
                                            <strong>Dirección:</strong> <?php echo htmlspecialchars($order['direccion_entrega'] ?? 'Recoger en local'); ?><br>
                                            <strong>Total:</strong> S/ <?php echo number_format($order['total'], 2); ?><br>
                                            <strong>Ganancia estimada:</strong> S/ <?php echo number_format($order['total'] * 0.1, 2); ?><br>
                                            <strong>Hora:</strong> <?php echo date('H:i', strtotime($order['fecha_pedido'])); ?>
                                        </p>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <button type="submit" name="take_order" class="btn btn-success btn-sm">
                                                <i class="fas fa-hand-paper"></i> Tomar Pedido
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- My Orders Section -->
            <section id="my-orders" class="section">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-truck"></i> Mis Entregas</h1>
                    <span class="badge bg-primary fs-6"><?php echo $my_orders_result->num_rows; ?> entregas</span>
                </div>

                <!-- Map visualization button -->
                <div class="mb-3 text-end">
                    <button class="btn btn-success" id="showRoutesBtn" onclick="showDeliveryRoutesOnMap()">
                        <i class="fas fa-route"></i> Ver Todas las Rutas en Mapa
                    </button>
                </div>

                <?php if ($my_orders_result->num_rows == 0): ?>
                    <div class="card p-5 text-center">
                        <i class="fas fa-truck fa-3x text-muted mb-3"></i>
                        <h3>No tienes entregas asignadas</h3>
                        <p class="text-muted">Toma un pedido disponible para comenzar a entregar.</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php while ($order = $my_orders_result->fetch_assoc()): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card order-card mine">
                                    <div class="card-body">
                                        <h5 class="card-title">Pedido #<?php echo $order['id']; ?></h5>
                                        <div class="mb-2">
                                            <span class="status-badge status-<?php echo strtolower(str_replace('_', '_', $order['estado'])); ?>">
                                                <?php echo $order['estado']; ?>
                                            </span>
                                        </div>
                                        <p class="card-text">
                                            <strong>Restaurante:</strong> <?php echo htmlspecialchars($order['restaurante_nombre']); ?><br>
                                            <strong>Cliente:</strong> <?php echo htmlspecialchars($order['cliente_nombre'] ?? 'No especificado'); ?><br>
                                            <strong>Dirección:</strong> <?php echo htmlspecialchars($order['direccion_entrega'] ?? 'Recoger en local'); ?><br>
                                            <strong>Total:</strong> S/ <?php echo number_format($order['total'], 2); ?><br>
                                            <strong>Ganancia:</strong> S/ <?php echo number_format($order['total'] * 0.1, 2); ?><br>
                                            <strong>Hora:</strong> <?php echo date('H:i', strtotime($order['fecha_pedido'])); ?>
                                        </p>

                                        <!-- Order Details -->
                                        <div class="order-details">
                                            <strong><i class="fas fa-utensils"></i> Artículos:</strong>
                                            <?php
                                            $items_query = $conn->prepare("SELECT dp.cantidad, dp.precio, p.nombre FROM detalle_pedido dp LEFT JOIN productos p ON dp.producto_id = p.id WHERE dp.pedido_id = ?");
                                            $items_query->bind_param('i', $order['id']);
                                            $items_query->execute();
                                            $items_result = $items_query->get_result();
                                            ?>
                                            <ul class="mb-0 mt-1">
                                                <?php while ($item = $items_result->fetch_assoc()): ?>
                                                    <li><?php echo htmlspecialchars($item['nombre'] ?? 'Producto'); ?> — Cant: <?php echo intval($item['cantidad']); ?> — S/ <?php echo number_format($item['precio'], 2); ?></li>
                                                <?php endwhile; ?>
                                            </ul>
                                            <?php $items_query->close(); ?>
                                        </div>

                                        <div class="mt-3 d-flex gap-2">
                                            <?php if ($order['estado'] == 'EN_CAMINO'): ?>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                    <input type="hidden" name="status" value="ENTREGADO">
                                                    <button type="submit" name="update_status" class="btn btn-success btn-sm">
                                                        <i class="fas fa-check"></i> Marcar Entregado
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <button class="btn btn-info btn-sm" onclick="showOrderRoute(<?php echo $order['id']; ?>)">
                                                <i class="fas fa-map-marker-alt"></i> Ver en Mapa
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Earnings Section -->
            <section id="earnings" class="section">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-chart-line"></i> Mis Ganancias</h1>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card stats-card">
                            <div class="card-body text-center">
                                <h3>S/ <?php echo number_format($earnings['total_earnings'] ?? 0, 2); ?></h3>
                                <p><i class="fas fa-money-bill-wave"></i> Ganancias Totales</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card stats-card">
                            <div class="card-body text-center">
                                <h3><?php echo $earnings['completed_deliveries'] ?? 0; ?></h3>
                                <p><i class="fas fa-check-circle"></i> Entregas Completadas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3><?php echo $earnings['total_deliveries'] ?? 0; ?></h3>
                                <p class="text-muted"><i class="fas fa-truck"></i> Total de Entregas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3>S/ <?php echo number_format($earnings['avg_earning_per_delivery'] ?? 0, 2); ?></h3>
                                <p class="text-muted"><i class="fas fa-calculator"></i> Promedio por Entrega</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Earnings Chart -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-chart-bar"></i> Historial de Ganancias</h5>
                        <canvas id="earningsChart" style="max-height: 300px;"></canvas>
                    </div>
                </div>
            </section>

            <!-- Map Section -->
            <section id="map-section" class="section">
                <div class="map-section-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="mb-0"><i class="fas fa-map-marked-alt"></i> Mapa de Entregas - Arequipa</h1>
                            <p class="mb-0 opacity-75">Visualiza pedidos disponibles, restaurantes y tus entregas activas</p>
                        </div>
                        <div class="control-buttons d-flex">
                            <button class="btn btn-light" onclick="centerMap()">
                                <i class="fas fa-crosshairs"></i> Centrar en Arequipa
                            </button>
                            <button class="btn btn-success" onclick="refreshData()">
                                <i class="fas fa-sync"></i> Actualizar Datos
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Search and Controls -->
                <div class="search-container">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" id="mapSearch" class="form-control form-control-lg"
                                       placeholder="Buscar dirección en Arequipa..."
                                       autocomplete="off">
                                <button class="btn btn-primary" type="button" onclick="searchLocation()">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                            </div>
                            <div id="searchSuggestions" class="mt-2" style="display: none; max-height: 200px; overflow-y: auto; border: 1px solid #ddd; border-radius: 5px; background: white; position: absolute; z-index: 1000; width: calc(100% - 30px);"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex gap-2 align-items-center flex-wrap">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="showRestaurants" checked>
                                    <label class="form-check-label" for="showRestaurants">
                                        <i class="fas fa-utensils text-success"></i> Restaurantes
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="showAvailableOrders" checked>
                                    <label class="form-check-label" for="showAvailableOrders">
                                        <i class="fas fa-clock text-warning"></i> Pedidos Disponibles
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="showMyOrders" checked>
                                    <label class="form-check-label" for="showMyOrders">
                                        <i class="fas fa-truck text-primary"></i> Mis Entregas
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="showRoutes" checked>
                                    <label class="form-check-label" for="showRoutes">
                                        <i class="fas fa-route text-info"></i> Rutas
                                    </label>
                                </div>
                                <div class="mt-3">
                                    <button class="btn btn-success btn-sm" id="historyBtn" onclick="showDeliveryRoutesOnMap()">
                                        <i class="fas fa-history"></i> Ver Historial de Entregas
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Map Container -->
                <div class="map-container">
                    <div class="card-body p-0">
                        <div id="map" style="height: 500px; border-radius: 15px;"></div>
                    </div>
                </div>

                <!-- Map Legend -->
                <div class="map-legend mt-3">
                    <div class="row text-center">
                        <div class="col-3">
                            <i class="fas fa-utensils fa-2x text-success"></i>
                            <br><small class="text-muted fw-bold">Restaurantes</small>
                        </div>
                        <div class="col-3">
                            <i class="fas fa-clock fa-2x text-warning"></i>
                            <br><small class="text-muted fw-bold">Pedidos Disponibles</small>
                        </div>
                        <div class="col-3">
                            <i class="fas fa-truck fa-2x text-primary"></i>
                            <br><small class="text-muted fw-bold">En Camino</small>
                        </div>
                        <div class="col-3">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                            <br><small class="text-muted fw-bold">Entregados</small>
                        </div>
                    </div>
                    <div class="row text-center mt-2">
                        <div class="col-4">
                            <div style="width: 30px; height: 4px; background: #fd7e14; margin: 0 auto; border-radius: 2px;"></div>
                            <br><small class="text-muted">Ruta Disponible</small>
                        </div>
                        <div class="col-4">
                            <div style="width: 30px; height: 4px; background: #007bff; margin: 0 auto; border-radius: 2px;"></div>
                            <br><small class="text-muted">Ruta Activa</small>
                        </div>
                        <div class="col-4">
                            <div style="width: 30px; height: 4px; background: #28a745; margin: 0 auto; border-radius: 2px;"></div>
                            <br><small class="text-muted">Ruta Completada</small>
                        </div>
                    </div>
                </div>

                <!-- Route Information -->
                <div id="routeInfo" class="card mt-3" style="display: none;">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-route"></i> Información de Ruta</h5>
                        <div id="routeDetails"></div>
                    </div>
                </div>
            </section>

            <!-- Profile Section -->
            <section id="profile" class="section">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-user"></i> Mi Perfil</h1>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card p-4">
                            <h3><i class="fas fa-id-card"></i> Información Personal</h3>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Nombre</label>
                                        <p class="h5"><?php echo htmlspecialchars($repartidor['nombre']); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Email</label>
                                        <p class="h5"><?php echo htmlspecialchars($repartidor['email']); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Vehículo</label>
                                        <p class="h5"><?php echo htmlspecialchars($repartidor['vehiculo'] ?? 'No especificado'); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Zona</label>
                                        <p class="h5"><?php echo htmlspecialchars($repartidor['zona'] ?? 'No especificada'); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Estado</label>
                                        <p class="h5">
                                            <span class="badge bg-<?php echo ($repartidor['disponible'] ?? 1) ? 'success' : 'secondary'; ?>">
                                                <?php echo ($repartidor['disponible'] ?? 1) ? 'Disponible' : 'No Disponible'; ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card p-4 text-center">
                            <h3><i class="fas fa-trophy"></i> Estadísticas</h3>
                            <div class="mt-3">
                                <div class="h2 text-primary"><?php echo $earnings['completed_deliveries'] ?? 0; ?></div>
                                <p class="text-muted">Entregas</p>
                                <hr>
                                <div class="h2 text-success">S/ <?php echo number_format($earnings['total_earnings'] ?? 0, 2); ?></div>
                                <p class="text-muted">Ganancias</p>
                                <hr>
                                <div class="h2 text-warning"><?php echo $earnings['total_deliveries'] ?? 0; ?></div>
                                <p class="text-muted">Total Pedidos</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/leaflet.locatecontrol@0.79.0/dist/L.Control.Locate.min.js"></script>
    <script>
        // Navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const sectionId = this.getAttribute('data-section');
                if (sectionId) {
                    // Hide all sections
                    document.querySelectorAll('.section').forEach(section => {
                        section.classList.remove('active');
                    });
                    // Remove active class from all nav links
                    document.querySelectorAll('.nav-link').forEach(nav => {
                        nav.classList.remove('active');
                    });
                    // Show selected section
                    document.getElementById(sectionId).classList.add('active');
                    // Add active class to clicked link
                    this.classList.add('active');

                    // Initialize map when map section is shown
                    if (sectionId === 'map-section') {
                        setTimeout(() => {
                            if (map) map.invalidateSize();
                        }, 100);
                    }

                    // When "Mis Entregas" is selected, show the section and prepare map visualization
                    if (sectionId === 'my-orders') {
                        // Just show the section normally, don't force map switch
                        console.log('📋 Mostrando sección Mis Entregas');
                        // After showing the section, prepare map routes in background
                        setTimeout(() => {
                            console.log('🗺️ Preparando visualización de rutas en mapa...');
                            // Load routes in background for when user clicks "Ver en Mapa"
                            loadNearbyOrders().then(() => {
                                console.log('✅ Rutas preparadas para visualización');
                            });
                        }, 500);
                    }
                }
            });
        });

        // Initialize map
        let map;
        let markers = [];
        let restaurantMarkers = [];
        let availableOrderMarkers = [];
        let myOrderMarkers = [];
        let orderRoutes = []; // Store route polylines
        let searchMarker = null;
        let searchTimeout;
        let currentRoute = null;

        // Arequipa coordinates
        const AREQUIPA_CENTER = [-16.3989, -71.5350];
        const AREQUIPA_BOUNDS = [[-16.5, -71.7], [-16.3, -71.4]];

        function initMap() {
            if (!map) {
                // Initialize map centered on Arequipa with appropriate zoom
                map = L.map('map', {
                    center: AREQUIPA_CENTER,
                    zoom: 15, // Increased zoom for better initial view
                    zoomControl: true,
                    scrollWheelZoom: true,
                    doubleClickZoom: true,
                    boxZoom: true,
                    keyboard: true,
                    dragging: true,
                    touchZoom: true
                });

                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19
                }).addTo(map);

                // Add scale control
                L.control.scale().addTo(map);

                // Add geolocation control
                L.control.locate({
                    position: 'topright',
                    strings: {
                        title: "Mostrar mi ubicación"
                    },
                    locateOptions: {
                        enableHighAccuracy: true
                    }
                }).addTo(map);

                // Load initial data
                loadRestaurants();
                loadNearbyOrders();

                // Add zoom event listener to ensure routes remain visible
                map.on('zoomend', function() {
                    // Force redraw of all polylines after zoom
                    orderRoutes.forEach(route => {
                        if (route && map.hasLayer(route)) {
                            route.redraw();
                        }
                    });
                });

                // Setup search functionality
                setupSearch();
                setupLayerControls();
            }
        }

        function setupSearch() {
            const searchInput = document.getElementById('mapSearch');
            const suggestionsDiv = document.getElementById('searchSuggestions');

            searchInput.addEventListener('input', function() {
                const query = this.value.trim();
                if (query.length < 3) {
                    suggestionsDiv.style.display = 'none';
                    return;
                }

                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    searchArequipaLocations(query);
                }, 300);
            });

            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchLocation();
                }
            });

            // Hide suggestions when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
                    suggestionsDiv.style.display = 'none';
                }
            });
        }

        function searchArequipaLocations(query) {
            const suggestionsDiv = document.getElementById('searchSuggestions');

            // Common Arequipa locations for autocomplete
            const arequipaLocations = [
                "Plaza de Armas Arequipa",
                "Catedral de Arequipa",
                "Monasterio de San Francisco",
                "Mirador de Yanahuara",
                "Museo Santuarios Andinos",
                "Barrio de San Lázaro",
                "Puente Grau",
                "Aeropuerto Rodríguez Ballón",
                "Universidad Nacional de San Agustín",
                "Hospital Honorio Delgado",
                "Mercado San Camilo",
                "Centro Histórico de Arequipa",
                "Distrito de Cayma",
                "Distrito de Yanahuara",
                "Distrito de Sachaca",
                "Distrito de Socabaya",
                "Distrito de Characato",
                "Distrito de Chiguata",
                "Distrito de La Joya",
                "Distrito de Mariano Melgar"
            ];

            const filtered = arequipaLocations.filter(location =>
                location.toLowerCase().includes(query.toLowerCase())
            ).slice(0, 8);

            if (filtered.length > 0) {
                suggestionsDiv.innerHTML = filtered.map(location =>
                    `<div class="p-2 border-bottom suggestion-item" style="cursor: pointer; hover: background-color: #f8f9fa;" onclick="selectLocation('${location}')">${location}</div>`
                ).join('');
                suggestionsDiv.style.display = 'block';
            } else {
                suggestionsDiv.style.display = 'none';
            }
        }

        function selectLocation(location) {
            document.getElementById('mapSearch').value = location;
            document.getElementById('searchSuggestions').style.display = 'none';
            searchLocation();
        }

        function searchLocation() {
            const query = document.getElementById('mapSearch').value.trim();
            if (!query) return;

            // Use Nominatim API for geocoding with proper headers
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}, Arequipa, Peru&limit=1`, {
                headers: {
                    'User-Agent': 'FoodApp Delivery Panel/1.0'
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data && data.length > 0) {
                        const result = data[0];
                        const lat = parseFloat(result.lat);
                        const lon = parseFloat(result.lon);

                        // Check if coordinates are within Arequipa bounds
                        if (lat >= -16.5 && lat <= -16.3 && lon >= -71.7 && lon <= -71.4) {
                            map.setView([lat, lon], 16);

                            // Remove previous search marker
                            if (searchMarker) {
                                map.removeLayer(searchMarker);
                            }

                            // Add marker for search result
                            searchMarker = L.marker([lat, lon], {
                                icon: L.icon({
                                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                                    iconSize: [25, 41],
                                    iconAnchor: [12, 41],
                                    popupAnchor: [1, -34],
                                    shadowSize: [41, 41]
                                })
                            })
                            .addTo(map)
                            .bindPopup(`<b>${query}</b><br>Lat: ${lat.toFixed(6)}<br>Lng: ${lon.toFixed(6)}`)
                            .openPopup();
                        } else {
                            alert('La ubicación encontrada no está en Arequipa. Intente con una dirección más específica dentro de Arequipa.');
                        }
                    } else {
                        alert('Ubicación no encontrada en Arequipa. Intente con una dirección más específica.');
                    }
                })
                .catch(error => {
                    console.error('Error en búsqueda:', error);
                    alert('Error al buscar la ubicación. Verifique su conexión a internet e intente nuevamente.');
                });
        }

        function loadRestaurants() {
            // Clear existing restaurant markers
            restaurantMarkers.forEach(marker => map.removeLayer(marker));
            restaurantMarkers = [];

            // Fetch real restaurants from database
            fetch('../controllers/get_restaurants.php', {
                credentials: 'same-origin'
            })
                .then(response => response.json())
                .then(data => {
                    // Check if response is an error
                    if (data.error) {
                        console.warn('Error loading restaurants:', data.error);
                        return;
                    }
                    
                    // Ensure data is an array
                    if (!Array.isArray(data)) {
                        console.error('Invalid restaurants data format');
                        return;
                    }
                    
                    data.forEach(restaurant => {
                        if (restaurant.lat && restaurant.lng) {
                            const marker = L.marker([restaurant.lat, restaurant.lng], {
                                icon: L.icon({
                                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
                                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                                    iconSize: [25, 41],
                                    iconAnchor: [12, 41],
                                    popupAnchor: [1, -34],
                                    shadowSize: [41, 41]
                                })
                            })
                            .addTo(map)
                            .bindPopup(`
                                <div class="text-center">
                                    <h6><i class="fas fa-utensils text-success"></i> ${restaurant.nombre}</h6>
                                    <p class="mb-1"><i class="fas fa-map-marker-alt"></i> ${restaurant.direccion}</p>
                                    <p class="mb-1"><i class="fas fa-phone"></i> ${restaurant.telefono || 'No disponible'}</p>
                                    <p class="mb-0"><i class="fas fa-star text-warning"></i> ${restaurant.calificacion || 'Sin calificación'}</p>
                                </div>
                            `);
                            restaurantMarkers.push(marker);
                        }
                    });
                })
                .catch(error => {
                    console.error('Error loading restaurants:', error);
                    // Fallback to sample data if API fails
                    const sampleRestaurants = [
                        { nombre: "La Nueva Palomino", lat: -16.3989, lng: -71.5350, direccion: "Plaza de Armas", telefono: "054-123456", calificacion: 4.5 },
                        { nombre: "Sol de Mayo", lat: -16.4021, lng: -71.5338, direccion: "Calle Mercaderes 123", telefono: "054-234567", calificacion: 4.2 },
                        { nombre: "Hatunpa", lat: -16.3956, lng: -71.5372, direccion: "Av. Ejército 456", telefono: "054-345678", calificacion: 4.7 },
                        { nombre: "El Verde", lat: -16.4012, lng: -71.5298, direccion: "Calle San Francisco 789", telefono: "054-456789", calificacion: 4.0 },
                        { nombre: "Pachamama", lat: -16.3998, lng: -71.5412, direccion: "Av. Parra 321", telefono: "054-567890", calificacion: 4.3 }
                    ];

                    sampleRestaurants.forEach(restaurant => {
                        const marker = L.marker([restaurant.lat, restaurant.lng], {
                            icon: L.icon({
                                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
                                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                                iconSize: [25, 41],
                                iconAnchor: [12, 41],
                                popupAnchor: [1, -34],
                                shadowSize: [41, 41]
                            })
                        })
                        .addTo(map)
                        .bindPopup(`
                            <div class="text-center">
                                <h6><i class="fas fa-utensils text-success"></i> ${restaurant.nombre}</h6>
                                <p class="mb-1"><i class="fas fa-map-marker-alt"></i> ${restaurant.direccion}</p>
                                <p class="mb-1"><i class="fas fa-phone"></i> ${restaurant.telefono}</p>
                                <p class="mb-0"><i class="fas fa-star text-warning"></i> ${restaurant.calificacion}</p>
                            </div>
                        `);
                        restaurantMarkers.push(marker);
                    });
                });
        }

        function loadNearbyOrders() {
            return new Promise((resolve) => {
                // Clear existing order markers and routes
                availableOrderMarkers.forEach(marker => map.removeLayer(marker));
                myOrderMarkers.forEach(marker => map.removeLayer(marker));
                orderRoutes.forEach(route => map.removeLayer(route));
                availableOrderMarkers = [];
                myOrderMarkers = [];
                orderRoutes = [];

                // Reset highlighting state
                completedRoutesHighlighted = false;
                highlightedRoutes = [];
                updateHistoryButtons(false);

                // Fetch real orders from database
                fetch('../controllers/get_orders.php', {
                credentials: 'same-origin'
            })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            console.error('Error loading orders:', data.error);
                            resolve();
                            return;
                        }

                        // Add available order markers
                        data.available_orders.forEach(order => {
                            const marker = L.marker([order.lat, order.lng], {
                                icon: L.divIcon({
                                    className: 'order-marker',
                                    html: '<div class="available-marker-icon"><i class="fas fa-shopping-cart"></i></div>',
                                    iconSize: [30, 30],
                                    iconAnchor: [15, 30]
                                })
                            })
                            .addTo(map)
                            .bindPopup(`
                                <div class="route-popup">
                                    <h6><i class="fas fa-shopping-cart text-warning"></i> Pedido Disponible</h6>
                                    <p class="mb-1"><strong>#${order.id}</strong></p>
                                    <p class="mb-1"><i class="fas fa-utensils"></i> ${order.restaurant}</p>
                                    <p class="mb-1"><i class="fas fa-user"></i> ${order.cliente}</p>
                                    <p class="mb-1"><i class="fas fa-map-marker-alt"></i> ${order.address}</p>
                                    <p class="mb-1"><i class="fas fa-money-bill-wave"></i> <strong>S/ ${order.total.toFixed(2)}</strong></p>
                                    <p class="mb-1"><i class="fas fa-clock"></i> ${order.hora}</p>
                                    <button class="btn btn-warning btn-sm" onclick="takeOrderFromMap(${order.id})">
                                        <i class="fas fa-hand-paper"></i> Tomar Pedido
                                    </button>
                                </div>
                            `);

                            availableOrderMarkers.push(marker);
                        });

                        // Add my order markers
                        data.my_orders.forEach(order => {
                            // Choose marker color based on status
                            const markerColor = order.status === 'ENTREGADO' ? 'success' : 'primary';
                            const markerIcon = order.status === 'ENTREGADO' ? 'check-circle' : 'truck';

                            const marker = L.marker([order.lat, order.lng], {
                                icon: L.divIcon({
                                    className: 'order-marker',
                                    html: `<div class="delivery-marker-icon"><i class="fas fa-${markerIcon}"></i></div>`,
                                    iconSize: [30, 30],
                                    iconAnchor: [15, 30]
                                })
                            })
                            .addTo(map)
                            .bindPopup(`
                                <div class="route-popup">
                                    <h6><i class="fas fa-truck text-${markerColor}"></i> ${order.status === 'ENTREGADO' ? 'Entrega Completada' : 'Mi Entrega'}</h6>
                                    <p class="mb-1"><strong>#${order.id}</strong></p>
                                    <p class="mb-1"><i class="fas fa-utensils"></i> ${order.restaurant}</p>
                                    <p class="mb-1"><i class="fas fa-user"></i> ${order.cliente}</p>
                                    <p class="mb-1"><i class="fas fa-map-marker-alt"></i> ${order.address}</p>
                                    <p class="mb-1"><i class="fas fa-money-bill-wave"></i> <strong>S/ ${order.total.toFixed(2)}</strong></p>
                                    <p class="mb-1"><span class="badge bg-${order.status === 'ENTREGADO' ? 'success' : 'info'}">${order.status}</span></p>
                                    <p class="mb-1"><i class="fas fa-clock"></i> ${order.hora}</p>
                                    ${order.status === 'ENTREGADO' ?
                                        `<p class="mb-1"><i class="fas fa-check-circle text-success"></i> Entregado: ${new Date(order.updated_at).toLocaleString('es-PE')}</p>` :
                                        `<button class="btn btn-success btn-sm" onclick="completeOrderFromMap(${order.id})">
                                            <i class="fas fa-check"></i> Marcar Entregado
                                        </button>`
                                    }
                                </div>
                            `);

                            myOrderMarkers.push(marker);
                        });

                        // Draw routes from restaurant to customer for my orders
                        data.my_orders.forEach(order => {
                            console.log('🎨 Creando ruta para pedido:', order.id, 'Estado:', order.status);

                            // Parse restaurant coordinates from string
                            const restaurantCoords = order.restaurante_ubicacion_gps.split(',').map(coord => parseFloat(coord.trim()));
                            const deliveryCoords = [order.lat, order.lng];

                            // Validate coordinates thoroughly
                            if (isNaN(restaurantCoords[0]) || isNaN(restaurantCoords[1]) || isNaN(deliveryCoords[0]) || isNaN(deliveryCoords[1]) ||
                                !isFinite(restaurantCoords[0]) || !isFinite(restaurantCoords[1]) || !isFinite(deliveryCoords[0]) || !isFinite(deliveryCoords[1])) {
                                console.warn('⚠️ Coordenadas inválidas para pedido:', order.id, {restaurantCoords, deliveryCoords});
                                return; // Skip this order
                            }

                            // Additional bounds check for Arequipa
                            const allCoords = [restaurantCoords[0], restaurantCoords[1], deliveryCoords[0], deliveryCoords[1]];
                            const inBounds = allCoords.every(coord =>
                                coord >= -90 && coord <= 90 && coord !== 0 // Also check for default 0 values
                            );

                            if (!inBounds) {
                                console.warn('⚠️ Coordenadas fuera de límites para pedido:', order.id, {restaurantCoords, deliveryCoords});
                                return; // Skip this order
                            }

                            const routeColor = order.status === 'ENTREGADO' ? '#28a745' : '#007bff'; // Green for delivered, blue for in progress
                            const route = L.polyline([
                                restaurantCoords, // Restaurant [lat, lng]
                                deliveryCoords // Customer [lat, lng]
                            ], {
                                color: routeColor,
                                weight: 4,
                                opacity: 0.8,
                                dashArray: order.status === 'ENTREGADO' ? null : '8, 12' // Dashed for in progress
                            }).addTo(map);

                            // Add popup to route showing order info
                            route.bindPopup(`
                                <div class="text-center">
                                    <h6><i class="fas fa-route text-${order.status === 'ENTREGADO' ? 'success' : 'primary'}"></i> Ruta de Entrega</h6>
                                    <p class="mb-1"><strong>Pedido #${order.id}</strong></p>
                                    <p class="mb-1">De: ${order.restaurant}</p>
                                    <p class="mb-1">A: ${order.cliente}</p>
                                    <p class="mb-1"><span class="badge bg-${order.status === 'ENTREGADO' ? 'success' : 'info'}">${order.status}</span></p>
                                </div>
                            `);

                            orderRoutes.push(route);
                            console.log('✅ Ruta guardada en orderRoutes. Total rutas:', orderRoutes.length);
                        });

                        // Draw routes for available orders (dashed orange lines)
                        data.available_orders.forEach(order => {
                            // Parse restaurant coordinates from string
                            const restaurantCoords = order.restaurante_ubicacion_gps.split(',').map(coord => parseFloat(coord.trim()));
                            const deliveryCoords = [order.lat, order.lng];

                            // Validate coordinates thoroughly
                            if (isNaN(restaurantCoords[0]) || isNaN(restaurantCoords[1]) || isNaN(deliveryCoords[0]) || isNaN(deliveryCoords[1]) ||
                                !isFinite(restaurantCoords[0]) || !isFinite(restaurantCoords[1]) || !isFinite(deliveryCoords[0]) || !isFinite(deliveryCoords[1])) {
                                console.warn('⚠️ Coordenadas inválidas para pedido disponible:', order.id, {restaurantCoords, deliveryCoords});
                                return; // Skip this order
                            }

                            // Additional bounds check for Arequipa
                            const allCoords = [restaurantCoords[0], restaurantCoords[1], deliveryCoords[0], deliveryCoords[1]];
                            const inBounds = allCoords.every(coord =>
                                coord >= -90 && coord <= 90 && coord !== 0 // Also check for default 0 values
                            );

                            if (!inBounds) {
                                console.warn('⚠️ Coordenadas fuera de límites para pedido disponible:', order.id, {restaurantCoords, deliveryCoords});
                                return; // Skip this order
                            }

                            const route = L.polyline([
                                restaurantCoords, // Restaurant [lat, lng]
                                deliveryCoords // Customer [lat, lng]
                            ], {
                                color: '#fd7e14',
                                weight: 3,
                                opacity: 0.7,
                                dashArray: '8, 12'
                            }).addTo(map);

                            // Add popup to route showing order info
                            route.bindPopup(`
                                <div class="text-center">
                                    <h6><i class="fas fa-route text-warning"></i> Ruta Disponible</h6>
                                    <p class="mb-1"><strong>Pedido #${order.id}</strong></p>
                                    <p class="mb-1">De: ${order.restaurant}</p>
                                    <p class="mb-1">A: ${order.cliente}</p>
                                    <p class="mb-1"><span class="badge bg-warning">DISPONIBLE</span></p>
                                </div>
                            `);

                            orderRoutes.push(route);
                        });

                        resolve();
                        console.log('🏁 loadNearbyOrders completado. Total rutas:', orderRoutes.length);
                    })
                    .catch(error => {
                        console.error('Error loading orders:', error);
                        resolve();
                    });
            });
        }

        function setupLayerControls() {
            // Setup checkbox controls for showing/hiding markers
            document.getElementById('showRestaurants').addEventListener('change', function() {
                restaurantMarkers.forEach(marker => {
                    if (this.checked) {
                        map.addLayer(marker);
                    } else {
                        map.removeLayer(marker);
                    }
                });
            });

            document.getElementById('showAvailableOrders').addEventListener('change', function() {
                availableOrderMarkers.forEach(marker => {
                    if (this.checked) {
                        map.addLayer(marker);
                    } else {
                        map.removeLayer(marker);
                    }
                });
            });

            document.getElementById('showMyOrders').addEventListener('change', function() {
                myOrderMarkers.forEach(marker => {
                    if (this.checked) {
                        map.addLayer(marker);
                    } else {
                        map.removeLayer(marker);
                    }
                });
            });

            document.getElementById('showRoutes').addEventListener('change', function() {
                orderRoutes.forEach(route => {
                    if (this.checked) {
                        map.addLayer(route);
                    } else {
                        map.removeLayer(route);
                    }
                });
            });
        }

        function takeOrderFromMap(orderId) {
            if (confirm('¿Deseas tomar este pedido?')) {
                // Make AJAX call to take the order
                fetch('/FOODAPP/foodapp_php/controllers/take_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `order_id=${orderId}`,
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`Pedido #${orderId} tomado exitosamente!`);
                        refreshData(); // Refresh all data
                        
                        // Switch to map view and show route for this order
                        setTimeout(() => {
                            // Switch to map section
                            document.querySelectorAll('.section').forEach(section => {
                                section.classList.remove('active');
                            });
                            document.querySelectorAll('.nav-link').forEach(nav => {
                                nav.classList.remove('active');
                            });
                            document.getElementById('map-section').classList.add('active');
                            document.querySelector('[data-section="map-section"]').classList.add('active');
                            
                            // Initialize map
                            setTimeout(() => {
                                if (map) map.invalidateSize();
                                showOrderRoute(orderId);
                            }, 100);
                        }, 500);
                    } else {
                        alert(data.message || 'Error al tomar el pedido');
                    }
                })
                .catch(error => {
                    console.error('Error taking order:', error);
                    alert('Error al tomar el pedido. Intente nuevamente.');
                });
            }
        }

        // Function to create a more realistic route with intermediate waypoints
        function createRealisticRoute(startCoords, endCoords, map) {
            return new Promise((resolve) => {
                // Calculate intermediate waypoints to simulate road following
                const waypoints = [startCoords];
                
                // Calculate vector between start and end
                const latDiff = endCoords[0] - startCoords[0];
                const lngDiff = endCoords[1] - startCoords[1];
                
                // Add intermediate points (simulate following roads)
                const numWaypoints = Math.max(3, Math.floor(Math.abs(latDiff + lngDiff) * 100)); // More points for longer distances
                
                for (let i = 1; i < numWaypoints; i++) {
                    const ratio = i / numWaypoints;
                    
                    // Add some randomness to simulate road curves
                    const randomOffset = 0.001; // Small offset for realism
                    const lat = startCoords[0] + (latDiff * ratio) + (Math.random() - 0.5) * randomOffset;
                    const lng = startCoords[1] + (lngDiff * ratio) + (Math.random() - 0.5) * randomOffset;
                    
                    waypoints.push([lat, lng]);
                }
                
                waypoints.push(endCoords);
                
                // Create curved route line
                const routeLine = L.polyline(waypoints, {
                    color: '#ff441f',
                    weight: 4,
                    opacity: 0.8,
                    smoothFactor: 1
                }).addTo(map);
                
                resolve(routeLine);
            });
        }

        function showOrderRoute(orderId) {
            // Switch to map section if not already active
            const mapSection = document.getElementById('map-section');
            if (!mapSection.classList.contains('active')) {
                document.querySelectorAll('.section').forEach(section => {
                    section.classList.remove('active');
                });
                document.querySelectorAll('.nav-link').forEach(nav => {
                    nav.classList.remove('active');
                });
                mapSection.classList.add('active');
                document.querySelector('[data-section="map-section"]').classList.add('active');

                // Initialize map if needed
                setTimeout(() => {
                    if (map) map.invalidateSize();
                    showOrderRoute(orderId);
                }, 200);
                return;
            }

            // Ensure map is initialized
            if (!map) {
                initMap();
                setTimeout(() => showOrderRoute(orderId), 500);
                return;
            }

            // Get order details including restaurant and delivery coordinates
            fetch(`../controllers/get_order_details.php?order_id=${orderId}`, {
                credentials: 'same-origin'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const order = data.order;

                        // Parse restaurant coordinates
                        const restaurantCoords = order.restaurante_ubicacion_gps.split(',').map(coord => parseFloat(coord.trim()));

                        // Delivery coordinates
                        const deliveryCoords = [parseFloat(order.delivery_lat), parseFloat(order.delivery_lng)];

                        console.log('📍 Coordenadas del pedido:', {
                            restaurant: restaurantCoords,
                            delivery: deliveryCoords
                        });

                        // Validate coordinates
                        if (isNaN(restaurantCoords[0]) || isNaN(restaurantCoords[1]) || isNaN(deliveryCoords[0]) || isNaN(deliveryCoords[1])) {
                            console.error('❌ Coordenadas inválidas:', {restaurantCoords, deliveryCoords});
                            alert('Error: Coordenadas inválidas para mostrar la ruta');
                            return;
                        }

                        // Clear existing routes and markers
                        orderRoutes.forEach(route => {
                            try {
                                map.removeLayer(route);
                            } catch (e) {
                                console.warn('⚠️ Error removing route:', e);
                            }
                        });
                        orderRoutes = [];

                        markers.forEach(marker => map.removeLayer(marker));
                        markers = [];
                        availableOrderMarkers.forEach(marker => map.removeLayer(marker));
                        myOrderMarkers.forEach(marker => map.removeLayer(marker));
                        markers = [];
                        availableOrderMarkers = [];
                        myOrderMarkers = [];

                        // Add markers for restaurant and delivery
                        const restaurantMarker = L.marker(restaurantCoords, {
                            icon: L.divIcon({
                                className: 'order-marker',
                                html: '<div class="restaurant-marker-icon"><i class="fas fa-utensils"></i></div>',
                                iconSize: [30, 30],
                                iconAnchor: [15, 30]
                            })
                        }).addTo(map).bindPopup(`
                            <div class="route-popup">
                                <h6><i class="fas fa-utensils text-danger"></i> Restaurante</h6>
                                <p class="mb-1"><strong>${order.restaurante_nombre}</strong></p>
                                <p class="mb-1"><i class="fas fa-map-marker-alt"></i> Punto de recogida</p>
                            </div>
                        `);

                        const deliveryMarker = L.marker(deliveryCoords, {
                            icon: L.divIcon({
                                className: 'order-marker',
                                html: '<div class="delivery-marker-icon"><i class="fas fa-map-marker-alt"></i></div>',
                                iconSize: [30, 30],
                                iconAnchor: [15, 30]
                            })
                        }).addTo(map).bindPopup(`
                            <div class="route-popup">
                                <h6><i class="fas fa-map-marker-alt text-success"></i> Entrega</h6>
                                <p class="mb-1"><strong>Destino</strong></p>
                                <p class="mb-1"><i class="fas fa-user"></i> ${order.cliente || 'Cliente'}</p>
                                <p class="mb-1"><i class="fas fa-map-marker-alt"></i> ${order.direccion_entrega}</p>
                            </div>
                        `);

                        markers.push(restaurantMarker, deliveryMarker);

                        // Create route using OpenRouteService API
                        const startCoords = [restaurantCoords[1], restaurantCoords[0]]; // [lng, lat] for ORS
                        const endCoords = [deliveryCoords[1], deliveryCoords[0]]; // [lng, lat] for ORS

                        console.log('🚗 Solicitando ruta:', {
                            startCoords: startCoords,
                            endCoords: endCoords
                        });

                        // Show loading indicator
                        const loadingInfo = L.control({position: 'topright'});
                        loadingInfo.onAdd = function(map) {
                            const div = L.DomUtil.create('div', 'route-info-panel');
                            div.innerHTML = `
                                <div class="card" style="min-width: 200px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                                    <div class="card-body text-center">
                                        <div class="pulse-animation mb-2">
                                            <i class="fas fa-route fa-2x text-primary"></i>
                                        </div>
                                        <p class="mb-0"><strong>Calculando ruta...</strong></p>
                                        <small class="text-muted">Obteniendo ruta óptima</small>
                                    </div>
                                </div>
                            `;
                            return div;
                        };
                        loadingInfo.addTo(map);

                        // Use OpenRouteService via proxy
                        fetch(`/FOODAPP/foodapp_php/controllers/proxy_ors.php?start=${startCoords.join(',')}&end=${endCoords.join(',')}`)
                            .then(response => {
                                // Remove loading indicator
                                map.removeControl(loadingInfo);
                                if (!response.ok) {
                                    throw new Error(`HTTP error! status: ${response.status}`);
                                }
                                return response.json();
                            })
                            .then(routeData => {
                                console.log('✅ Respuesta del proxy:', routeData);

                                if (!routeData || !routeData.features || !routeData.features[0] || !routeData.features[0].geometry || !routeData.features[0].geometry.coordinates) {
                                    throw new Error('Respuesta de ruta inválida - faltan coordenadas');
                                }

                                const coordinates = routeData.features[0].geometry.coordinates;

                                // Convert coordinates from [lng, lat] to [lat, lng] for Leaflet
                                const routeCoords = coordinates.map(coord => [coord[1], coord[0]]);

                                // Create polyline
                                const routeLine = L.polyline(routeCoords, {
                                    color: '#ff441f',
                                    weight: 5,
                                    opacity: 0.9,
                                    className: 'route-line-active'
                                }).addTo(map);

                                orderRoutes.push(routeLine);

                                // Fit map to show the route
                                const group = new L.featureGroup([restaurantMarker, deliveryMarker, routeLine]);
                                const bounds = group.getBounds();
                                if (bounds.isValid()) {
                                    map.fitBounds(bounds.pad(0.3));
                                }

                                // Add route info with distance calculation
                                let distanceKm = 'N/A';
                                try {
                                    // Handle different response formats from ors_proxy.php
                                    if (routeData.features[0].properties) {
                                        const props = routeData.features[0].properties;
                                        // Check if distance is directly in properties (OSRM format)
                                        if (props.distance) {
                                            const distance = props.distance / 1000;
                                            distanceKm = distance.toFixed(1);
                                        }
                                        // Or if it's in segments (ORS format)
                                        else if (props.segments && props.segments[0] && props.segments[0].distance) {
                                            const distance = props.segments[0].distance / 1000;
                                            distanceKm = distance.toFixed(1);
                                        }
                                    }
                                } catch (e) {
                                    console.warn('⚠️ No se pudo calcular la distancia:', e);
                                }

                                const routeInfo = L.control({position: 'topright'});
                                routeInfo.onAdd = function(map) {
                                    const div = L.DomUtil.create('div', 'route-info-panel');
                                    div.innerHTML = `
                                        <div class="card" style="min-width: 280px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                                            <div class="card-header">
                                                <h6 class="mb-0"><i class="fas fa-route me-2"></i>Detalles de Ruta</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-hashtag text-primary me-2"></i>
                                                    <strong>Pedido #${orderId}</strong>
                                                </div>
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-utensils text-danger me-2"></i>
                                                    <span>${order.restaurante_nombre}</span>
                                                </div>
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-map-marker-alt text-success me-2"></i>
                                                    <span>${order.direccion_entrega}</span>
                                                </div>
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-road text-info me-2"></i>
                                                    <span><strong>Distancia: ~${distanceKm} km</strong></span>
                                                </div>
                                                <div class="d-flex align-items-center mb-3">
                                                    <i class="fas fa-clock text-warning me-2"></i>
                                                    <span>Estado: <span class="badge bg-info">EN CAMINO</span></span>
                                                </div>
                                                <button class="btn btn-success btn-sm w-100" onclick="completeOrderFromMap(${orderId})">
                                                    <i class="fas fa-check-circle me-1"></i>Confirmar Llegada
                                                </button>
                                                <small class="text-muted d-block mt-2 text-center">
                                                    <i class="fas fa-info-circle me-1"></i>Ruta por vías reales
                                                </small>
                                            </div>
                                        </div>
                                    `;
                                    return div;
                                };

                                // Remove existing route info
                                if (currentRoute) {
                                    map.removeControl(currentRoute);
                                }
                                routeInfo.addTo(map);
                                currentRoute = routeInfo;

                            })
                            .catch(error => {
                                console.error('❌ Error al obtener ruta:', error);

                                // Remove loading indicator if it exists
                                if (loadingInfo) {
                                    map.removeControl(loadingInfo);
                                }

                                // Fallback: create straight line route
                                const routeLine = L.polyline([restaurantCoords, deliveryCoords], {
                                    color: '#ff441f',
                                    weight: 5,
                                    opacity: 0.9,
                                    dashArray: '10, 10',
                                    className: 'route-line-active'
                                }).addTo(map);

                                orderRoutes.push(routeLine);

                                // Fit map to show the route
                                const group = new L.featureGroup([restaurantMarker, deliveryMarker, routeLine]);
                                const bounds = group.getBounds();
                                if (bounds.isValid()) {
                                    map.fitBounds(bounds.pad(0.3));
                                }

                                // Add route info with estimated distance
                                const distance = map.distance(restaurantCoords, deliveryCoords);
                                const distanceKm = (distance / 1000).toFixed(1);

                                const routeInfo = L.control({position: 'topright'});
                                routeInfo.onAdd = function(map) {
                                    const div = L.DomUtil.create('div', 'route-info-panel');
                                    div.innerHTML = `
                                        <div class="card" style="min-width: 250px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                                            <div class="card-header">
                                                <h6 class="mb-0"><i class="fas fa-route me-2"></i>Detalles de Ruta</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-hashtag text-primary me-2"></i>
                                                    <strong>Pedido #${orderId}</strong>
                                                </div>
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-utensils text-danger me-2"></i>
                                                    <span>${order.restaurante_nombre}</span>
                                                </div>
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-map-marker-alt text-success me-2"></i>
                                                    <span>${order.direccion_entrega}</span>
                                                </div>
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-road text-info me-2"></i>
                                                    <span><strong>Distancia: ~${distanceKm} km</strong></span>
                                                </div>
                                                <small class="text-muted d-block">
                                                    <i class="fas fa-exclamation-triangle text-warning me-1"></i>Ruta estimada (API no disponible)
                                                </small>
                                            </div>
                                        </div>
                                    `;
                                    return div;
                                };

                                // Remove existing route info
                                if (currentRoute) {
                                    map.removeControl(currentRoute);
                                }
                                routeInfo.addTo(map);
                                currentRoute = routeInfo;

                                alert('Se muestra una ruta estimada ya que el servicio de rutas no está disponible.');
                            });
                    } else {
                        alert('Error al obtener detalles del pedido');
                    }
                })
                .catch(error => {
                    console.error('Error getting order details:', error);
                    alert('Error al obtener detalles del pedido');
                });
        }

        function completeOrderFromMap(orderId) {
            // Create a more detailed confirmation dialog
            const confirmDialog = document.createElement('div');
            confirmDialog.className = 'modal fade';
            confirmDialog.innerHTML = `
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title"><i class="fas fa-check-circle me-2"></i>Confirmar Entrega</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center mb-3">
                                <i class="fas fa-map-marker-alt fa-3x text-success mb-3"></i>
                                <h6>¿Has llegado al destino?</h6>
                                <p class="text-muted">Confirma que has entregado el pedido #${orderId}</p>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">
                                        <i class="fas fa-times me-1"></i>Cancelar
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button type="button" class="btn btn-success w-100" id="confirmDeliveryBtn">
                                        <i class="fas fa-check me-1"></i>Confirmar Entrega
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(confirmDialog);
            const modal = new bootstrap.Modal(confirmDialog);
            modal.show();

            // Handle confirmation
            document.getElementById('confirmDeliveryBtn').addEventListener('click', function() {
                modal.hide();
                confirmDialog.remove();

                // Show loading
                showToast('Procesando entrega...', 'info');

                // Make AJAX call to complete the order
                fetch('/FOODAPP/foodapp_php/controllers/update_order_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `order_id=${orderId}&status=ENTREGADO`,
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(`¡Pedido #${orderId} entregado exitosamente!`, 'success');
                        refreshData(); // Refresh all data
                    } else {
                        showToast(data.message || 'Error al completar el pedido', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error completing order:', error);
                    showToast('Error al completar el pedido. Intente nuevamente.', 'error');
                });
            });

            // Clean up modal when hidden
            confirmDialog.addEventListener('hidden.bs.modal', function() {
                confirmDialog.remove();
            });
        }

        function showOnMap(orderId) {
            // Switch to map section
            document.querySelector('[data-section="map-section"]').click();

            // Fetch order details and show on map
            setTimeout(() => {
                if (map) {
                    // If routes are not loaded, load them first
                    if (orderRoutes.length === 0) {
                        loadNearbyOrders();
                        setTimeout(() => showOnMap(orderId), 1000); // Retry after loading
                        return;
                    }

                    // Find the order marker and show its popup
                    let foundMarker = null;
                    let foundRoute = null;

                    // Check in available orders
                    availableOrderMarkers.forEach(marker => {
                        const popupContent = marker.getPopup().getContent();
                        if (popupContent.includes(`Pedido #${orderId}`)) {
                            foundMarker = marker;
                        }
                    });

                    // Check in my orders
                    if (!foundMarker) {
                        myOrderMarkers.forEach(marker => {
                            const popupContent = marker.getPopup().getContent();
                            if (popupContent.includes(`Mi Entrega #${orderId}`) || popupContent.includes(`Pedido #${orderId}`) || popupContent.includes(`Entrega Completada #${orderId}`)) {
                                foundMarker = marker;
                            }
                        });
                    }

                    // Find corresponding route
                    orderRoutes.forEach(route => {
                        const popupContent = route.getPopup().getContent();
                        if (popupContent.includes(`Pedido #${orderId}`)) {
                            foundRoute = route;
                        }
                    });

                    if (foundMarker) {
                        map.setView(foundMarker.getLatLng(), 16);
                        foundMarker.openPopup();

                        // Highlight the route if found
                        if (foundRoute) {
                            // Temporarily increase route opacity and weight
                            const originalOptions = foundRoute.options;
                            foundRoute.setStyle({
                                weight: originalOptions.weight + 3,
                                opacity: 1.0
                            });

                            // Reset after 5 seconds
                            setTimeout(() => {
                                foundRoute.setStyle(originalOptions);
                            }, 5000);
                        }
                    } else {
                        // If marker not found, center on Arequipa
                        map.setView(AREQUIPA_CENTER, 15);
                    }
                }
            }, 500); // Increased timeout to ensure map is loaded
        }

        let completedRoutesHighlighted = false;
        let highlightedRoutes = [];

        function highlightCompletedRoutes() {
            console.log('🔍 Buscando rutas completadas...');
            console.log('Total de rutas en orderRoutes:', orderRoutes.length);

            let completedRoutes = [];

            // Find all completed delivery routes (green routes)
            orderRoutes.forEach((route, index) => {
                const popupContent = route.getPopup().getContent();
                console.log(`Ruta ${index} popup content:`, popupContent.substring(0, 100) + '...');
                if (popupContent.includes('ENTREGADO')) {
                    console.log('✅ Ruta completada encontrada!');
                    completedRoutes.push(route);
                }
            });

            console.log('Rutas completadas encontradas:', completedRoutes.length);

            if (completedRoutes.length > 0) {
                if (!completedRoutesHighlighted) {
                    // Highlight completed routes
                    showNotification(`Mostrando ${completedRoutes.length} entregas completadas en el mapa`, 'success');

                    completedRoutes.forEach(route => {
                        // Store original style
                        route.originalOptions = route.options;
                        route.setStyle({
                            weight: 6,
                            opacity: 1.0,
                            color: '#28a745' // Keep green color
                        });
                        // Bring to front
                        route.bringToFront();
                    });

                    highlightedRoutes = completedRoutes;
                    completedRoutesHighlighted = true;

                    // Update button texts
                    updateHistoryButtons(true);

                    // Fit map to show all completed routes
                    const group = new L.featureGroup(completedRoutes);
                    map.fitBounds(group.getBounds().pad(0.1));
                } else {
                    // Unhighlight completed routes
                    showNotification('Ocultando rutas de entregas completadas', 'info');

                    highlightedRoutes.forEach(route => {
                        if (route.originalOptions) {
                            route.setStyle(route.originalOptions);
                        }
                    });

                    highlightedRoutes = [];
                    completedRoutesHighlighted = false;

                    // Update button texts
                    updateHistoryButtons(false);

                    // Center on Arequipa
                    map.setView(AREQUIPA_CENTER, 13);
                }
            } else {
                console.log('❌ No se encontraron rutas completadas');
                showNotification('No hay entregas completadas para mostrar', 'info');
                // If no completed routes, center on Arequipa
                map.setView(AREQUIPA_CENTER, 13);
            }
        }

        function showToast(message, type = 'info') {
            // Create toast container if it doesn't exist
            let toastContainer = document.getElementById('toastContainer');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.id = 'toastContainer';
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
                    <div class="toast-body">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;

            toastContainer.appendChild(toast);

            // Initialize and show toast
            const bsToast = new bootstrap.Toast(toast, {
                autohide: true,
                delay: type === 'error' ? 5000 : 3000
            });
            bsToast.show();

            // Remove toast from DOM after it's hidden
            toast.addEventListener('hidden.bs.toast', function() {
                toast.remove();
            });

            return toast;
        }

        function updateHistoryButtons(showing) {
            const showRoutesBtn = document.getElementById('showRoutesBtn');
            const historyBtn = document.getElementById('historyBtn');

            if (showing) {
                if (showRoutesBtn) {
                    showRoutesBtn.innerHTML = '<i class="fas fa-eye-slash"></i> Ocultar Rutas en Mapa';
                    showRoutesBtn.className = 'btn btn-warning';
                }
                if (historyBtn) {
                    historyBtn.innerHTML = '<i class="fas fa-eye-slash"></i> Ocultar Historial';
                    historyBtn.className = 'btn btn-warning btn-sm';
                }
            } else {
                if (showRoutesBtn) {
                    showRoutesBtn.innerHTML = '<i class="fas fa-route"></i> Ver Todas las Rutas en Mapa';
                    showRoutesBtn.className = 'btn btn-success';
                }
                if (historyBtn) {
                    historyBtn.innerHTML = '<i class="fas fa-history"></i> Ver Historial de Entregas';
                    historyBtn.className = 'btn btn-success btn-sm';
                }
            }
        }

        function refreshData() {
            // Refresh map markers
            loadNearbyOrders();

            // Show loading indicator
            const refreshBtn = document.querySelector('button[onclick="refreshData()"]');
            if (refreshBtn) {
                const originalText = refreshBtn.innerHTML;
                refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
                refreshBtn.disabled = true;

                setTimeout(() => {
                    refreshBtn.innerHTML = originalText;
                    refreshBtn.disabled = false;
                    // Refresh the page to update order lists and statistics
                    location.reload();
                }, 2000);
            }
        }

        // Initialize map when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
            updateHistoryButtons(false); // Initialize button states
        });

        // Earnings Chart
        <?php
        // Get earnings data for the last 7 days
        $chart_data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $query = $conn->prepare("
                SELECT SUM(total * 0.1) as daily_earnings
                FROM pedidos
                WHERE repartidor_id = ? AND DATE(fecha_pedido) = ? AND estado = 'ENTREGADO'
            ");
            $query->bind_param('is', $user_id, $date);
            $query->execute();
            $result = $query->get_result();
            $data = $result->fetch_assoc();
            $chart_data[] = $data['daily_earnings'] ?? 0;
            $query->close();
        }
        ?>

        const ctx = document.getElementById('earningsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: [
                    <?php
                    for ($i = 6; $i >= 0; $i--) {
                        echo "'" . date('d/m', strtotime("-$i days")) . "',";
                    }
                    ?>
                ],
                datasets: [{
                    label: 'Ganancias Diarias (S/)',
                    data: [<?php echo implode(',', $chart_data); ?>],
                    borderColor: '#ff441f',
                    backgroundColor: 'rgba(255, 68, 31, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'S/ ' + value;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
