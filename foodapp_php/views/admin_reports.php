<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header('Location: login.php');
    exit;
}
include_once '../includes/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Admin FoodApp</title>
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
            --danger-color: #dc3545;
            --warning-color: #ffc107;
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

        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

        .financial-summary {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .financial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .financial-item {
            text-align: center;
            padding: 1.5rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .financial-item:hover {
            transform: scale(1.05);
        }

        .financial-item.income {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .financial-item.expenses {
            background: linear-gradient(135deg, #dc3545, #fd7e14);
            color: white;
        }

        .financial-item.profit {
            background: linear-gradient(135deg, #ffc107, #ff6b35);
            color: white;
        }

        .financial-value {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .financial-label {
            font-size: 0.9rem;
            font-weight: 600;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
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

            .stats-overview {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }

            .financial-grid {
                grid-template-columns: 1fr;
            }
        }

        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 350px;
            color: var(--primary-color);
        }

        .loading i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-chart-line me-2"></i>
                FoodApp - Reportes
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php">
                            <i class="fas fa-home me-1"></i>Dashboard
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
            <h1><i class="fas fa-chart-bar me-3"></i>Reportes y Estadísticas</h1>
            <p>Información detallada del rendimiento de FoodApp</p>
        </div>

        <!-- Estadísticas Generales -->
        <div class="stats-overview">
            <?php
            $total_orders = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE estado != 'CANCELADO'")->fetch_assoc()['count'];
            $total_products = $conn->query("SELECT COUNT(*) as count FROM productos WHERE disponible = 1")->fetch_assoc()['count'];
            $total_restaurants = $conn->query("SELECT COUNT(*) as count FROM restaurantes")->fetch_assoc()['count'];
            $total_users = $conn->query("SELECT COUNT(*) as count FROM usuarios WHERE tipo != 'admin'")->fetch_assoc()['count'];
            ?>
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
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?php echo number_format($total_users); ?></div>
                <div class="stat-label">Usuarios Activos</div>
            </div>
        </div>

        <!-- Resumen Financiero -->
        <div class="financial-summary">
            <h3 class="text-center mb-4" style="color: var(--dark-color); font-weight: 700;">
                <i class="fas fa-dollar-sign me-2" style="color: var(--primary-color);"></i>
                Resumen Financiero
            </h3>
            <div class="financial-grid">
                <?php
                $total_revenue = $conn->query("SELECT SUM(total) as sum FROM pedidos WHERE estado != 'CANCELADO'")->fetch_assoc()['sum'] ?? 0;
                $estimated_expenses = $total_revenue * 0.3;
                $profit = $total_revenue * 0.7;
                ?>
                <div class="financial-item income">
                    <div class="financial-value">S/ <?php echo number_format($total_revenue, 2); ?></div>
                    <div class="financial-label">Ingresos Totales</div>
                </div>
                <div class="financial-item expenses">
                    <div class="financial-value">S/ <?php echo number_format($estimated_expenses, 2); ?></div>
                    <div class="financial-label">Gastos Estimados (30%)</div>
                </div>
                <div class="financial-item profit">
                    <div class="financial-value">S/ <?php echo number_format($profit, 2); ?></div>
                    <div class="financial-label">Beneficio (70%)</div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-header">
                    <i class="fas fa-trophy chart-icon"></i>
                    <h4 class="chart-title">Productos Más Vendidos</h4>
                </div>
                <div class="chart-container">
                    <canvas id="topProductsChart"></canvas>
                </div>
            </div>

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
        </div>
    </div>
    <script>
        <?php
        // Productos más vendidos
        $top_products_query = $conn->query("
            SELECT p.nombre, SUM(dp.cantidad) as total_vendido
            FROM productos p
            JOIN detalle_pedido dp ON p.id = dp.producto_id
            JOIN pedidos ped ON dp.pedido_id = ped.id
            WHERE ped.estado != 'CANCELADO'
            GROUP BY p.id, p.nombre
            ORDER BY total_vendido DESC
            LIMIT 5
        ");
        $top_products_labels = [];
        $top_products_data = [];
        while ($row = $top_products_query->fetch_assoc()) {
            $top_products_labels[] = $row['nombre'];
            $top_products_data[] = $row['total_vendido'];
        }

        // Pedidos por mes
        $orders_by_month_query = $conn->query("
            SELECT MONTH(fecha_pedido) as mes, COUNT(*) as total_pedidos
            FROM pedidos
            WHERE YEAR(fecha_pedido) = YEAR(CURDATE()) AND estado != 'CANCELADO'
            GROUP BY MONTH(fecha_pedido)
            ORDER BY MONTH(fecha_pedido)
        ");
        $orders_labels = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $orders_data = array_fill(0, 12, 0);
        while ($row = $orders_by_month_query->fetch_assoc()) {
            $orders_data[$row['mes'] - 1] = $row['total_pedidos'];
        }

        // Ingresos por mes
        $revenue_by_month_query = $conn->query("
            SELECT MONTH(fecha_pedido) as mes, SUM(total) as total_ingresos
            FROM pedidos
            WHERE YEAR(fecha_pedido) = YEAR(CURDATE()) AND estado != 'CANCELADO'
            GROUP BY MONTH(fecha_pedido)
            ORDER BY MONTH(fecha_pedido)
        ");
        $revenue_data = array_fill(0, 12, 0);
        while ($row = $revenue_by_month_query->fetch_assoc()) {
            $revenue_data[$row['mes'] - 1] = $row['total_ingresos'];
        }
        ?>

        const ctxTopProducts = document.getElementById('topProductsChart').getContext('2d');
        new Chart(ctxTopProducts, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($top_products_labels); ?>,
                datasets: [{
                    label: 'Ventas',
                    data: <?php echo json_encode($top_products_data); ?>,
                    backgroundColor: 'rgba(255, 107, 53, 0.8)',
                    borderColor: 'rgba(255, 107, 53, 1)',
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                    hoverBackgroundColor: 'rgba(255, 107, 53, 1)',
                }]
            },
            options: {
                indexAxis: 'y',
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
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            color: '#343a40'
                        }
                    },
                    y: {
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

        const ctxOrders = document.getElementById('ordersChart').getContext('2d');
        new Chart(ctxOrders, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($orders_labels); ?>,
                datasets: [{
                    label: 'Pedidos',
                    data: <?php echo json_encode($orders_data); ?>,
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
                labels: <?php echo json_encode($orders_labels); ?>,
                datasets: [{
                    label: 'Ingresos (S/)',
                    data: <?php echo json_encode($revenue_data); ?>,
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
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
