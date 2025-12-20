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
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos - Admin FoodApp</title>
    <link rel="icon" href="../assets/FOODAPP.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .orders-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .orders-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(255, 107, 53, 0.1);
        }

        .orders-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin: 0;
        }

        .orders-stats {
            display: flex;
            gap: 1rem;
        }

        .stat-badge {
            background: rgba(255, 107, 53, 0.1);
            color: var(--primary-color);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .orders-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 1rem;
        }

        .orders-table thead th {
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 700;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: none;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .orders-table tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .orders-table tbody tr:hover {
            background: rgba(255, 107, 53, 0.05);
            transform: scale(1.01);
        }

        .orders-table tbody td {
            padding: 1rem;
            border: none;
            vertical-align: middle;
        }

        .order-id {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .client-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .client-avatar {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .client-details {
            display: flex;
            flex-direction: column;
        }

        .client-email {
            font-weight: 600;
            color: var(--dark-color);
            font-size: 0.9rem;
        }

        .restaurant-name {
            font-weight: 600;
            color: var(--dark-color);
        }

        .order-total {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--success-color);
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            min-width: 100px;
            display: inline-block;
        }

        .status-pendiente {
            background: rgba(255, 193, 7, 0.2);
            color: #856404;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }

        .status-preparando {
            background: rgba(23, 162, 184, 0.2);
            color: #0c5460;
            border: 1px solid rgba(23, 162, 184, 0.3);
        }

        .status-en_camino {
            background: rgba(255, 107, 53, 0.2);
            color: #a94442;
            border: 1px solid rgba(255, 107, 53, 0.3);
        }

        .status-entregado {
            background: rgba(40, 167, 69, 0.2);
            color: #155724;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .status-cancelado {
            background: rgba(220, 53, 69, 0.2);
            color: #721c24;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .status-select {
            padding: 0.4rem 0.8rem;
            border: 2px solid rgba(255, 107, 53, 0.3);
            border-radius: 8px;
            background: white;
            color: var(--dark-color);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }

        .order-date {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .action-btn {
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 53, 0.3);
            color: white;
            text-decoration: none;
        }

        .action-btn i {
            font-size: 0.9rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .orders-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .orders-stats {
                width: 100%;
                justify-content: space-between;
            }

            .orders-table {
                font-size: 0.85rem;
            }

            .orders-table thead th,
            .orders-table tbody td {
                padding: 0.5rem;
            }

            .client-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.25rem;
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
                <i class="fas fa-shopping-cart me-2"></i>
                FoodApp - Pedidos
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
            <h1><i class="fas fa-clipboard-list me-3"></i>Gestión de Pedidos</h1>
            <p>Administra todos los pedidos de la plataforma FoodApp</p>
        </div>

        <div class="orders-container fade-in">
            <div class="orders-header">
                <h2 class="orders-title">
                    <i class="fas fa-list me-2"></i>Lista de Pedidos
                </h2>
                <div class="orders-stats">
                    <?php
                    $total_orders = $conn->query("SELECT COUNT(*) as count FROM pedidos")->fetch_assoc()['count'];
                    $pending_orders = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE estado = 'PENDIENTE'")->fetch_assoc()['count'];
                    $delivered_orders = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE estado = 'ENTREGADO'")->fetch_assoc()['count'];
                    ?>
                    <span class="stat-badge">
                        <i class="fas fa-shopping-cart me-1"></i><?php echo $total_orders; ?> Total
                    </span>
                    <span class="stat-badge">
                        <i class="fas fa-clock me-1"></i><?php echo $pending_orders; ?> Pendientes
                    </span>
                    <span class="stat-badge">
                        <i class="fas fa-check-circle me-1"></i><?php echo $delivered_orders; ?> Entregados
                    </span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag me-1"></i>ID</th>
                            <th><i class="fas fa-user me-1"></i>Cliente</th>
                            <th><i class="fas fa-utensils me-1"></i>Restaurante</th>
                            <th><i class="fas fa-dollar-sign me-1"></i>Total</th>
                            <th><i class="fas fa-info-circle me-1"></i>Estado</th>
                            <th><i class="fas fa-calendar me-1"></i>Fecha</th>
                            <th><i class="fas fa-cogs me-1"></i>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT p.id, u.email as cliente, r.nombre as restaurante, p.total, p.estado, p.fecha_pedido FROM pedidos p LEFT JOIN clientes c ON p.cliente_id = c.id LEFT JOIN usuarios u ON c.id = u.id LEFT JOIN restaurantes r ON p.restaurante_id = r.id ORDER BY p.fecha_pedido DESC";
                        $result = $conn->query($sql);
                        if (!$result) {
                            echo '<tr><td colspan="7" class="text-center text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error al consultar pedidos: ' . htmlspecialchars($conn->error) . '</td></tr>';
                        } else {
                            if ($result->num_rows === 0) {
                                echo '<tr><td colspan="7">
                                    <div class="empty-state">
                                        <i class="fas fa-shopping-cart"></i>
                                        <h3>No hay pedidos registrados</h3>
                                        <p>Los pedidos aparecerán aquí cuando los clientes realicen sus compras.</p>
                                    </div>
                                </td></tr>';
                            } else {
                                while ($row = $result->fetch_assoc()) {
                                    $id = intval($row['id']);
                                    $cliente = htmlspecialchars($row['cliente'] ?? '—');
                                    $rest = htmlspecialchars($row['restaurante'] ?? '—');
                                    $total = 'S/ ' . number_format($row['total'] ?? 0, 2);
                                    $fecha = htmlspecialchars($row['fecha_pedido']);
                                    $estado = htmlspecialchars($row['estado']);

                                    // Obtener iniciales del cliente para el avatar
                                    $cliente_iniciales = strtoupper(substr(explode('@', $cliente)[0], 0, 2));

                                    // Determinar clase CSS para el estado
                                    $status_class = 'status-' . strtolower(str_replace('_', '', $estado));

                                    echo "<tr>
                                            <td><span class='order-id'>#{$id}</span></td>
                                            <td>
                                                <div class='client-info'>
                                                    <div class='client-avatar'>{$cliente_iniciales}</div>
                                                    <div class='client-details'>
                                                        <span class='client-email'>{$cliente}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class='restaurant-name'>{$rest}</span></td>
                                            <td><span class='order-total'>{$total}</span></td>
                                            <td>
                                                <select class='status-select' onchange=\"changeStatus({$id}, this.value)\">
                                                    <option value='PENDIENTE'" . ($estado == 'PENDIENTE' ? ' selected' : '') . ">PENDIENTE</option>
                                                    <option value='PREPARANDO'" . ($estado == 'PREPARANDO' ? ' selected' : '') . ">PREPARANDO</option>
                                                    <option value='EN_CAMINO'" . ($estado == 'EN_CAMINO' ? ' selected' : '') . ">EN_CAMINO</option>
                                                    <option value='ENTREGADO'" . ($estado == 'ENTREGADO' ? ' selected' : '') . ">ENTREGADO</option>
                                                    <option value='CANCELADO'" . ($estado == 'CANCELADO' ? ' selected' : '') . ">CANCELADO</option>
                                                </select>
                                            </td>
                                            <td><span class='order-date'>{$fecha}</span></td>
                                            <td>
                                                <a href=\"order_detail.php?id={$id}\" class=\"action-btn\">
                                                    <i class=\"fas fa-eye\"></i>Ver Detalles
                                                </a>
                                            </td>
                                        </tr>";
                                }
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function changeStatus(orderId, newStatus) {
            if (confirm('¿Cambiar estado del pedido #' + orderId + ' a ' + newStatus + '?')) {
                // Mostrar indicador de carga
                const button = event.target;
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Cambiando...';
                button.disabled = true;

                // Simular procesamiento (puedes quitar esto si es muy rápido)
                setTimeout(() => {
                    window.location.href = '../controllers/admin_actions.php?action=change_status&id=' + orderId + '&status=' + newStatus;
                }, 500);
            }
        }

        // Animación de fade-in para las filas de la tabla
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('.orders-table tbody tr');
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    row.style.transition = 'all 0.5s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, index * 50);
            });
        });
    </script>
</body>
</html>
