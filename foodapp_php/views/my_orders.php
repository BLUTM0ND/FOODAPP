<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'cliente') {
    header('Location: login.php');
    exit;
}
include_once '../includes/config.php';

$user_id = $_SESSION['user_id'];

// Get user info
$user_query = $conn->prepare("SELECT u.nombre FROM usuarios u JOIN clientes c ON u.id = c.id WHERE u.id = ?");
$user_query->bind_param('i', $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
$user = $user_result->fetch_assoc();
$user_query->close();

// Get user's orders
$orders_query = $conn->prepare("
    SELECT p.*, r.nombre as restaurante_nombre, r.direccion as restaurante_direccion,
           u.nombre as repartidor_nombre
    FROM pedidos p
    LEFT JOIN restaurantes r ON p.restaurante_id = r.id
    LEFT JOIN usuarios u ON p.repartidor_id = u.id
    WHERE p.cliente_id = ?
    ORDER BY p.fecha_pedido DESC
");
$orders_query->bind_param('i', $user_id);
$orders_query->execute();
$orders_result = $orders_query->get_result();
$orders_query->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - FoodApp</title>
    <link rel="icon" href="../assets/FOODAPP.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($user['nombre'] ?? 'Usuario'); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit me-2"></i>Mi Perfil</a></li>
                        <li><a class="dropdown-item active" href="my_orders.php"><i class="fas fa-shopping-bag me-2"></i>Mis Pedidos</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../controllers/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <main class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-shopping-bag me-2 text-custom"></i>Mis Pedidos</h2>
                </div>

                <?php if ($orders_result->num_rows > 0): ?>
                    <div class="row">
                        <?php while ($order = $orders_result->fetch_assoc()):
                            // Get status badge class
                            $status_class = 'status-pendiente';
                            switch ($order['estado']) {
                                case 'ENTREGADO': $status_class = 'status-entregado'; break;
                                case 'EN_CAMINO': $status_class = 'status-en_camino'; break;
                                case 'PREPARANDO': $status_class = 'status-preparando'; break;
                                case 'CANCELADO': $status_class = 'status-cancelado'; break;
                            }
                        ?>
                            <div class="col-12 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <h5 class="card-title">
                                                    <i class="fas fa-receipt me-2"></i>
                                                    Pedido #<?php echo $order['id']; ?>
                                                    <span class="status-badge <?php echo $status_class; ?> ms-2">
                                                        <?php echo $order['estado']; ?>
                                                    </span>
                                                </h5>
                                                <p class="card-text mb-1">
                                                    <i class="fas fa-store me-2"></i>
                                                    <strong><?php echo htmlspecialchars($order['restaurante_nombre'] ?? 'N/A'); ?></strong>
                                                </p>
                                                <p class="card-text mb-1">
                                                    <i class="fas fa-calendar me-2"></i>
                                                    <?php echo date('d/m/Y H:i', strtotime($order['fecha_pedido'])); ?>
                                                </p>
                                                <p class="card-text mb-1">
                                                    <i class="fas fa-map-marker-alt me-2"></i>
                                                    <?php echo htmlspecialchars($order['direccion_entrega']); ?>
                                                </p>
                                                <?php if (!empty($order['repartidor_nombre'])): ?>
                                                    <p class="card-text mb-1">
                                                        <i class="fas fa-motorcycle me-2"></i>
                                                        Repartidor: <?php echo htmlspecialchars($order['repartidor_nombre']); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <h4 class="text-custom mb-3">S/ <?php echo number_format($order['total'], 2); ?></h4>
                                                <div class="btn-group-vertical" role="group">
                                                    <a href="tracking.php?id=<?php echo $order['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-eye me-1"></i>Ver Detalles
                                                    </a>
                                                    <?php if ($order['estado'] == 'ENTREGADO'): ?>
                                                        <a href="ratings.php?id=<?php echo $order['id']; ?>" class="btn btn-custom btn-sm">
                                                            <i class="fas fa-star me-1"></i>Valorar
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-bag fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">No tienes pedidos aún</h4>
                        <p class="text-muted">¡Explora nuestros restaurantes y haz tu primer pedido!</p>
                        <a href="../index.php" class="btn btn-custom">
                            <i class="fas fa-utensils me-2"></i>Ver Restaurantes
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="bg-light py-4 text-center border-top mt-5">
        <div class="container">
            <p class="text-muted mb-0">© 2023 FoodApp. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$conn->close();
?>
