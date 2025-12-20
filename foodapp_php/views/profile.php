<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'cliente') {
    header('Location: login.php');
    exit;
}
include_once '../includes/config.php';

$user_id = $_SESSION['user_id'];
$page_title = 'Mi Perfil - FoodApp';

// Get user info
$user_query = $conn->prepare("SELECT u.*, c.direcciones FROM usuarios u JOIN clientes c ON u.id = c.id WHERE u.id = ?");
$user_query->bind_param('i', $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
$user = $user_result->fetch_assoc();
$user_query->close();

// Get user's orders with restaurant info
$orders_query = $conn->prepare("
    SELECT p.*, r.nombre as restaurante_nombre, r.direccion as restaurante_direccion
    FROM pedidos p
    LEFT JOIN restaurantes r ON p.restaurante_id = r.id
    WHERE p.cliente_id = ?
    ORDER BY p.fecha_pedido DESC
    LIMIT 10
");
$orders_query->bind_param('i', $user_id);
$orders_query->execute();
$orders_result = $orders_query->get_result();
$orders_query->close();

// Handle profile update
$message = '';
$message_type = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $nombre = trim($_POST['nombre']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);

    if (!empty($nombre) && !empty($telefono)) {
        $update_query = $conn->prepare("UPDATE usuarios SET nombre = ?, telefono = ? WHERE id = ?");
        $update_query->bind_param('ssi', $nombre, $telefono, $user_id);
        if ($update_query->execute()) {
            // Update address in clientes table
            $address_query = $conn->prepare("UPDATE clientes SET direcciones = ? WHERE id = ?");
            $address_query->bind_param('si', $direccion, $user_id);
            $address_query->execute();
            $address_query->close();

            $message = 'Perfil actualizado exitosamente';
            $message_type = 'success';
            $_SESSION['user_name'] = $nombre;

            // Refresh user data
            $user_query = $conn->prepare("SELECT u.*, c.direcciones FROM usuarios u JOIN clientes c ON u.id = c.id WHERE u.id = ?");
            $user_query->bind_param('i', $user_id);
            $user_query->execute();
            $user_result = $user_query->get_result();
            $user = $user_result->fetch_assoc();
            $user_query->close();
        } else {
            $message = 'Error al actualizar el perfil';
            $message_type = 'danger';
        }
        $update_query->close();
    } else {
        $message = 'Por favor complete todos los campos requeridos';
        $message_type = 'warning';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
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
        .profile-avatar { width: 80px; height: 80px; background: linear-gradient(135deg, #ff441f, #ff7d00); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; margin: 0 auto 1rem; }
        .status-badge { font-size: 0.9rem; padding: 0.375rem 0.75rem; border-radius: 20px; }
        .status-entregado { background: #d4edda; color: #155724; }
        .status-en_camino { background: #fff3cd; color: #856404; }
        .status-preparando { background: #cce5ff; color: #004085; }
        .status-listo { background: #d1ecf1; color: #0c5460; }
        .status-pendiente { background: #f8d7da; color: #721c24; }
        .status-cancelado { background: #e2e3e5; color: #383d41; }
        .form-control:focus { border-color: #ff441f; box-shadow: 0 0 0 0.2rem rgba(255, 68, 31, 0.25); }
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
                        <li><a class="dropdown-item active" href="profile.php"><i class="fas fa-user-edit me-2"></i>Mi Perfil</a></li>
                        <li><a class="dropdown-item" href="my_orders.php"><i class="fas fa-shopping-bag me-2"></i>Mis Pedidos</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../controllers/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <main class="container mt-4">
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : ($message_type == 'danger' ? 'exclamation-triangle' : 'info-circle'); ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Profile Information -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="profile-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <h5 class="card-title"><?php echo htmlspecialchars($user['nombre']); ?></h5>
                        <p class="text-muted mb-1"><?php echo htmlspecialchars($user['email']); ?></p>
                        <p class="text-muted mb-3">
                            <i class="fas fa-calendar me-1"></i>
                            Miembro desde <?php echo date('M Y', strtotime($user['fecha_registro'])); ?>
                        </p>
                        <button class="btn btn-custom w-100" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                            <i class="fas fa-edit me-2"></i>Editar Perfil
                        </button>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card mt-3">
                    <div class="card-body">
                        <h6 class="card-title"><i class="fas fa-chart-bar me-2 text-custom"></i>Estadísticas</h6>
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="h4 text-custom"><?php echo $orders_result->num_rows; ?></div>
                                <small class="text-muted">Pedidos Totales</small>
                            </div>
                            <div class="col-6">
                                <div class="h4 text-success">
                                    <?php
                                    $completed_orders = 0;
                                    $orders_result->data_seek(0); // Reset pointer
                                    while ($order = $orders_result->fetch_assoc()) {
                                        if ($order['estado'] == 'ENTREGADO') $completed_orders++;
                                    }
                                    $orders_result->data_seek(0); // Reset pointer again
                                    echo $completed_orders;
                                    ?>
                                </div>
                                <small class="text-muted">Completados</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order History -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-shopping-bag me-2 text-custom"></i>Historial de Pedidos</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($orders_result->num_rows > 0): ?>
                            <div class="row">
                                <?php while ($order = $orders_result->fetch_assoc()):
                                    // Get status badge class and user-friendly status text
                                    $status_class = 'status-pendiente';
                                    $status_text = 'Pendiente';
                                    switch (trim($order['estado'])) {
                                        case 'ENTREGADO': 
                                            $status_class = 'status-entregado'; 
                                            $status_text = 'Entregado';
                                            break;
                                        case 'EN_CAMINO': 
                                            $status_class = 'status-en_camino'; 
                                            $status_text = 'En Camino';
                                            break;
                                        case 'PREPARANDO': 
                                            $status_class = 'status-preparando'; 
                                            $status_text = 'Preparando';
                                            break;
                                        case 'LISTO': 
                                            $status_class = 'status-listo'; 
                                            $status_text = 'Listo para Entrega';
                                            break;
                                        case 'CANCELADO': 
                                            $status_class = 'status-cancelado'; 
                                            $status_text = 'Cancelado';
                                            break;
                                        case 'PENDIENTE':
                                        default:
                                            $status_class = 'status-pendiente'; 
                                            $status_text = 'Pendiente';
                                            break;
                                    }
                                    // Debug: mostrar el estado y la clase asignada
                                    // echo "<!-- Debug: Estado: '" . $order['estado'] . "', Clase: '" . $status_class . "', Texto: '" . $status_text . "' -->";
                                ?>
                                    <div class="col-12 mb-3">
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-body">
                                                <div class="row align-items-center">
                                                    <div class="col-md-8">
                                                        <h6 class="card-title mb-1">
                                                            <i class="fas fa-receipt me-2"></i>
                                                            Pedido #<?php echo $order['id']; ?>
                                                            <span class="status-badge <?php echo $status_class; ?> ms-2">
                                                                <?php echo $status_text; ?>
                                                            </span>
                                                        </h6>
                                                        <p class="card-text mb-1 small text-muted">
                                                            <i class="fas fa-store me-2"></i>
                                                            <?php echo htmlspecialchars($order['restaurante_nombre'] ?? 'N/A'); ?>
                                                        </p>
                                                        <p class="card-text mb-1 small text-muted">
                                                            <i class="fas fa-calendar me-2"></i>
                                                            <?php echo date('d/m/Y H:i', strtotime($order['fecha_pedido'])); ?>
                                                        </p>
                                                        <p class="card-text mb-1 small text-muted">
                                                            <i class="fas fa-map-marker-alt me-2"></i>
                                                            <?php echo htmlspecialchars($order['direccion_entrega']); ?>
                                                        </p>
                                                    </div>
                                                    <div class="col-md-4 text-end">
                                                        <h5 class="text-custom mb-3">S/ <?php echo number_format($order['total'], 2); ?></h5>
                                                        <a href="tracking.php?id=<?php echo $order['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                            <i class="fas fa-eye me-1"></i>Ver Detalles
                                                        </a>
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
                                <h5 class="text-muted">No tienes pedidos aún</h5>
                                <p class="text-muted">¡Explora nuestros restaurantes y haz tu primer pedido!</p>
                                <a href="../index.php" class="btn btn-custom">
                                    <i class="fas fa-utensils me-2"></i>Ver Restaurantes
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileModalLabel"><i class="fas fa-user-edit me-2"></i>Editar Perfil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nombre" class="form-label"><i class="fas fa-user me-2"></i>Nombre Completo</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($user['nombre']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="telefono" class="form-label"><i class="fas fa-phone me-2"></i>Teléfono</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono" value="<?php echo htmlspecialchars($user['telefono']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="direccion" class="form-label"><i class="fas fa-map-marker-alt me-2"></i>Dirección Principal</label>
                            <textarea class="form-control" id="direccion" name="direccion" rows="3"><?php echo htmlspecialchars($user['direcciones']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-envelope me-2"></i>Email</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                            <small class="form-text text-muted">El email no se puede modificar</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="update_profile" class="btn btn-custom">
                            <i class="fas fa-save me-2"></i>Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
