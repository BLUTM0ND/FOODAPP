<!DOCTYPE html>
<?php
session_start();
// Allow either `user_type` or `tipo` session keys for admin role
$notAdmin = true;
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') $notAdmin = false;
if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'admin') $notAdmin = false;
if (!isset($_SESSION['user_id']) || $notAdmin) {
    header('Location: login.php');
    exit;
}

// Include database configuration
$config_path = __DIR__ . '/../includes/config.php';
if (!file_exists($config_path)) {
    die('Error: Archivo de configuración no encontrado: ' . $config_path);
}

include_once $config_path;

// Verify database connection
if (!isset($conn) || $conn->connect_error) {
    die('Error de conexión a la base de datos: ' . ($conn->connect_error ?? 'Conexión no establecida'));
}
?>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FoodApp</title>
    <link rel="icon" href="../assets/FOODAPP.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #ff6b35;
            --secondary-color: #f7931e;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --dark-color: #343a40;
            --light-color: #f8f9fa;
            --shadow: 0 4px 20px rgba(0,0,0,0.1);
            --border-radius: 15px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 1rem 0;
            box-shadow: var(--shadow);
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
        }

        .navbar-nav .nav-link {
            color: var(--dark-color) !important;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .navbar-nav .nav-link:hover {
            color: var(--primary-color) !important;
            transform: translateY(-2px);
        }

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            color: white;
        }

        .page-header h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .page-header p {
            font-size: 1.2rem;
            opacity: 0.9;
            font-weight: 400;
        }

        .dashboard-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 2rem;
            text-align: center;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1rem;
            font-weight: 600;
            color: var(--dark-color);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
        }

        .chart-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .chart-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(0,0,0,0.12);
        }

        .chart-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(255, 107, 53, 0.1);
        }

        .chart-icon {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-right: 0.75rem;
        }

        .chart-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark-color);
            margin: 0;
        }

        .chart-container {
            position: relative;
            height: 350px;
            margin: 0 auto;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .charts-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-tachometer-alt me-2"></i>
                FoodApp - Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php" style="color: var(--primary-color) !important;">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_data.php">
                            <i class="fas fa-database me-1"></i>Datos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_users.php">
                            <i class="fas fa-users me-1"></i>Usuarios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_orders.php">
                            <i class="fas fa-shopping-cart me-1"></i>Pedidos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_products.php">
                            <i class="fas fa-utensils me-1"></i>Productos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_restaurants.php">
                            <i class="fas fa-store me-1"></i>Restaurantes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_reports.php">
                            <i class="fas fa-chart-bar me-1"></i>Reportes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../controllers/logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Cerrar Sesión
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-container">
        <div class="page-header">
            <h1><i class="fas fa-tachometer-alt me-3"></i>Dashboard Administrativo</h1>
            <p>Información general del rendimiento de FoodApp</p>
        </div>

        <div class="dashboard-container fade-in">
            <!-- Estadísticas Generales -->
            <div class="stats-grid">
                <?php
                $total_users = $conn->query("SELECT COUNT(*) as count FROM usuarios WHERE tipo != 'admin'")->fetch_assoc()['count'];
                $total_orders = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE estado != 'CANCELADO'")->fetch_assoc()['count'];
                $total_products = $conn->query("SELECT COUNT(*) as count FROM productos WHERE disponible = 1")->fetch_assoc()['count'];
                $total_restaurants = $conn->query("SELECT COUNT(*) as count FROM restaurantes")->fetch_assoc()['count'];
                $total_revenue = $conn->query("SELECT SUM(total) as sum FROM pedidos WHERE estado != 'CANCELADO'")->fetch_assoc()['sum'] ?? 0;
                ?>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-value"><?php echo number_format($total_users); ?></div>
                    <div class="stat-label">Total Usuarios</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-value"><?php echo number_format($total_orders); ?></div>
                    <div class="stat-label">Total Pedidos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🍽️</div>
                    <div class="stat-value"><?php echo number_format($total_products); ?></div>
                    <div class="stat-label">Productos Activos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🏪</div>
                    <div class="stat-value"><?php echo number_format($total_restaurants); ?></div>
                    <div class="stat-label">Restaurantes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-value">S/ <?php echo number_format($total_revenue, 0); ?></div>
                    <div class="stat-label">Ingresos Totales</div>
                </div>
            </div>

            <!-- Gráficos -->
            <div class="charts-grid">
                <div class="chart-card">
                    <div class="chart-header">
                        <i class="fas fa-calendar-alt chart-icon"></i>
                        <h4 class="chart-title">Pedidos por Mes</h4>
                    </div>
                    <div class="chart-container">
                        <canvas id="ordersChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <i class="fas fa-money-bill-wave chart-icon"></i>
                        <h4 class="chart-title">Ingresos por Mes</h4>
                    </div>
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <i class="fas fa-users chart-icon"></i>
                        <h4 class="chart-title">Distribución de Usuarios</h4>
                    </div>
                    <div class="chart-container">
                        <canvas id="usersChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Datos dinámicos para los gráficos
        <?php
        // Pedidos por mes (últimos 6 meses)
        $orders_by_month = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $count = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE DATE_FORMAT(fecha_pedido, '%Y-%m') = '$month' AND estado != 'CANCELADO'")->fetch_assoc()['count'];
            $orders_by_month[] = $count;
        }

        // Ingresos por mes (últimos 6 meses)
        $revenue_by_month = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $sum = $conn->query("SELECT SUM(total) as sum FROM pedidos WHERE DATE_FORMAT(fecha_pedido, '%Y-%m') = '$month' AND estado != 'CANCELADO'")->fetch_assoc()['sum'] ?? 0;
            $revenue_by_month[] = $sum;
        }

        // Conteo de usuarios por tipo
        $clientes_count = $conn->query("SELECT COUNT(*) FROM usuarios WHERE tipo='cliente'")->fetch_assoc()['COUNT(*)'];
        $restaurantes_count = $conn->query("SELECT COUNT(*) FROM usuarios WHERE tipo='restaurante'")->fetch_assoc()['COUNT(*)'];
        $repartidores_count = $conn->query("SELECT COUNT(*) FROM usuarios WHERE tipo='repartidor'")->fetch_assoc()['COUNT(*)'];
        $admins_count = $conn->query("SELECT COUNT(*) FROM usuarios WHERE tipo='admin'")->fetch_assoc()['COUNT(*)'];
        ?>

        const ctxOrders = document.getElementById('ordersChart').getContext('2d');
        new Chart(ctxOrders, {
            type: 'line',
            data: {
                labels: [
                    '<?php echo date('M Y', strtotime('-5 months')); ?>',
                    '<?php echo date('M Y', strtotime('-4 months')); ?>',
                    '<?php echo date('M Y', strtotime('-3 months')); ?>',
                    '<?php echo date('M Y', strtotime('-2 months')); ?>',
                    '<?php echo date('M Y', strtotime('-1 months')); ?>',
                    '<?php echo date('M Y'); ?>'
                ],
                datasets: [{
                    label: 'Pedidos',
                    data: <?php echo json_encode($orders_by_month); ?>,
                    borderColor: 'rgba(255, 107, 53, 1)',
                    backgroundColor: 'rgba(255, 107, 53, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: 'rgba(255, 107, 53, 1)',
                    pointBorderColor: 'white',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    pointHoverBackgroundColor: 'rgba(255, 107, 53, 1)',
                    pointHoverBorderColor: 'white',
                    pointHoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: 'white',
                        bodyColor: 'white',
                        cornerRadius: 8,
                        displayColors: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            color: '#343a40'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            color: '#343a40'
                        }
                    }
                },
                animation: {
                    duration: 2000,
                    easing: 'easeInOutQuart'
                }
            }
        });

        const ctxRevenue = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctxRevenue, {
            type: 'bar',
            data: {
                labels: [
                    '<?php echo date('M Y', strtotime('-5 months')); ?>',
                    '<?php echo date('M Y', strtotime('-4 months')); ?>',
                    '<?php echo date('M Y', strtotime('-3 months')); ?>',
                    '<?php echo date('M Y', strtotime('-2 months')); ?>',
                    '<?php echo date('M Y', strtotime('-1 months')); ?>',
                    '<?php echo date('M Y'); ?>'
                ],
                datasets: [{
                    label: 'Ingresos (S/)',
                    data: <?php echo json_encode($revenue_by_month); ?>,
                    backgroundColor: 'rgba(247, 147, 30, 0.8)',
                    borderColor: 'rgba(247, 147, 30, 1)',
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                    hoverBackgroundColor: 'rgba(247, 147, 30, 1)',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: 'white',
                        bodyColor: 'white',
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return 'S/ ' + context.parsed.y.toLocaleString('es-PE', {minimumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            color: '#343a40'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            color: '#343a40',
                            callback: function(value) {
                                return 'S/ ' + value.toLocaleString('es-PE');
                            }
                        }
                    }
                },
                animation: {
                    duration: 2000,
                    easing: 'easeInOutQuart'
                }
            }
        });

        const ctxUsers = document.getElementById('usersChart').getContext('2d');
        new Chart(ctxUsers, {
            type: 'doughnut',
            data: {
                labels: ['Clientes', 'Restaurantes', 'Repartidores', 'Administradores'],
                datasets: [{
                    data: [<?php echo $clientes_count; ?>, <?php echo $restaurantes_count; ?>, <?php echo $repartidores_count; ?>, <?php echo $admins_count; ?>],
                    backgroundColor: [
                        'rgba(255, 107, 53, 0.8)',
                        'rgba(247, 147, 30, 0.8)',
                        'rgba(118, 75, 162, 0.8)',
                        'rgba(52, 58, 64, 0.8)'
                    ],
                    borderColor: [
                        'rgba(255, 107, 53, 1)',
                        'rgba(247, 147, 30, 1)',
                        'rgba(118, 75, 162, 1)',
                        'rgba(52, 58, 64, 1)'
                    ],
                    borderWidth: 2,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#343a40',
                            font: {
                                size: 12,
                                weight: '600'
                            },
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: 'white',
                        bodyColor: 'white',
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                },
                animation: {
                    duration: 2000,
                    easing: 'easeInOutQuart'
                }
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
