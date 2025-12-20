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
           c.calle, c.numero, c.ciudad, c.referencia
    FROM pedidos p
    JOIN restaurantes r ON p.restaurante_id = r.id
    LEFT JOIN direcciones c ON p.direccion_entrega = CONCAT(c.calle, ' ', c.numero, ', ', c.ciudad)
    WHERE p.estado = 'PENDIENTE' AND p.repartidor_id IS NULL
    ORDER BY p.fecha_pedido ASC
");

// Get orders assigned to this delivery person
$my_orders = $conn->prepare("
    SELECT p.*, r.nombre as restaurante_nombre, r.direccion as restaurante_direccion,
           c.calle, c.numero, c.ciudad, c.referencia
    FROM pedidos p
    JOIN restaurantes r ON p.restaurante_id = r.id
    LEFT JOIN direcciones c ON p.direccion_entrega = CONCAT(c.calle, ' ', c.numero, ', ', c.ciudad)
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
        .order-card { margin-bottom: 1rem; border-left: 4px solid #ff441f; }
        .order-card.available { border-left-color: #28a745; }
        .order-card.mine { border-left-color: #007bff; }
        .stats-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .stats-card .card-body { padding: 2rem; }
        .stats-card h3 { margin-bottom: 1rem; font-size: 2rem; }
        .stats-card p { margin-bottom: 0.5rem; font-size: 1.1rem; }
        .section { display: none; }
        .section.active { display: block; }
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
                        <p class="text-muted">Los pedidos disponibles aparecerán aquí cuando los restaurantes terminen de prepararlos.</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php while ($order = $available_orders->fetch_assoc()): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card order-card available">
                                    <div class="card-body">
                                        <h5 class="card-title">Pedido #<?php echo $order['id']; ?></h5>
                                        <p class="card-text">
                                            <strong>Restaurante:</strong> <?php echo htmlspecialchars($order['restaurante_nombre']); ?><br>
                                            <strong>Dirección:</strong> <?php echo htmlspecialchars($order['direccion_entrega'] ?? 'Recoger en local'); ?><br>
                                            <strong>Total:</strong> S/ <?php echo number_format($order['total'], 2); ?><br>
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
                                            <span class="badge bg-<?php echo $order['estado'] == 'ENTREGADO' ? 'success' : 'warning'; ?>">
                                                <?php echo $order['estado']; ?>
                                            </span>
                                        </div>
                                        <p class="card-text">
                                            <strong>Restaurante:</strong> <?php echo htmlspecialchars($order['restaurante_nombre']); ?><br>
                                            <strong>Dirección:</strong> <?php echo htmlspecialchars($order['direccion_entrega'] ?? 'Recoger en local'); ?><br>
                                            <strong>Total:</strong> S/ <?php echo number_format($order['total'], 2); ?><br>
                                            <strong>Ganancia:</strong> S/ <?php echo number_format($order['total'] * 0.1, 2); ?><br>
                                            <strong>Hora:</strong> <?php echo date('H:i', strtotime($order['fecha_pedido'])); ?>
                                        </p>
                                        <?php if ($order['estado'] == 'EN_CAMINO'): ?>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <input type="hidden" name="status" value="ENTREGADO">
                                                <button type="submit" name="update_status" class="btn btn-success btn-sm">
                                                    <i class="fas fa-check"></i> Marcar Entregado
                                                </button>
                                            </form>
                                        <?php endif; ?>
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
                }
            });
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
</html>
