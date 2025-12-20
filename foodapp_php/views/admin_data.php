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

// Obtener datos para el dashboard
// Total productos
$sql_productos = "SELECT COUNT(*) as total FROM productos";
$result_productos = $conn->query($sql_productos);
$total_productos = $result_productos->fetch_assoc()['total'];

// Total restaurantes
$sql_restaurantes = "SELECT COUNT(*) as total FROM restaurantes";
$result_restaurantes = $conn->query($sql_restaurantes);
$total_restaurantes = $result_restaurantes->fetch_assoc()['total'];

// Total stock (suma de stock de productos)
$sql_stock = "SELECT SUM(stock) as total FROM productos";
$result_stock = $conn->query($sql_stock);
$total_stock = $result_stock->fetch_assoc()['total'];

// Costos totales (suma de precios de productos)
$sql_costos = "SELECT SUM(precio) as total FROM productos";
$result_costos = $conn->query($sql_costos);
$total_costos = $result_costos->fetch_assoc()['total'];

// Obtener lista de productos
$sql_lista_productos = "SELECT p.id, p.nombre, p.descripcion, p.precio, p.stock, r.id as restaurante_id, r.nombre as restaurante FROM productos p JOIN restaurantes r ON p.restaurante_id = r.id";
$result_lista_productos = $conn->query($sql_lista_productos);

// Obtener lista de restaurantes
$sql_lista_restaurantes = "SELECT * FROM restaurantes";
$result_lista_restaurantes = $conn->query($sql_lista_restaurantes);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Datos - Admin FoodApp</title>
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

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1rem;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .data-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 2rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(255, 107, 53, 0.1);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .add-btn {
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .add-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 53, 0.3);
            color: white;
            text-decoration: none;
        }

        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 1rem;
        }

        .data-table thead th {
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

        .data-table tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .data-table tbody tr:hover {
            background: rgba(255, 107, 53, 0.05);
            transform: scale(1.01);
        }

        .data-table tbody td {
            padding: 1rem;
            border: none;
            vertical-align: middle;
        }

        .product-name {
            font-weight: 700;
            color: var(--dark-color);
            font-size: 1rem;
        }

        .product-price {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--success-color);
        }

        .product-stock {
            background: rgba(23, 162, 184, 0.1);
            color: #0c5460;
            padding: 0.3rem 0.6rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .restaurant-name {
            font-weight: 600;
            color: var(--dark-color);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            padding: 0.5rem 0.8rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .edit-btn {
            background: rgba(23, 162, 184, 0.2);
            color: #0c5460;
            border: 1px solid rgba(23, 162, 184, 0.3);
        }

        .edit-btn:hover {
            background: rgba(23, 162, 184, 0.8);
            color: white;
            transform: translateY(-1px);
        }

        .delete-btn {
            background: rgba(220, 53, 69, 0.2);
            color: #721c24;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .delete-btn:hover {
            background: rgba(220, 53, 69, 0.8);
            color: white;
            transform: translateY(-1px);
        }

        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            color: #155724;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            color: #721c24;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        /* Modal Styles */
        .modal-content {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .modal-header {
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            border-bottom: none;
        }

        .modal-title {
            font-weight: 700;
        }

        .btn-close {
            filter: invert(1);
        }

        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select, .form-check-input {
            border: 2px solid rgba(255, 107, 53, 0.2);
            border-radius: 8px;
            padding: 0.75rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }

        .btn-custom {
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            border: none;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-custom:hover {
            background: linear-gradient(135deg, #e55a2b, #e8850d);
            color: white;
            transform: translateY(-1px);
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

            .stats-container {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .section-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .data-table {
                font-size: 0.85rem;
            }

            .data-table thead th,
            .data-table tbody td {
                padding: 0.5rem;
            }

            .action-buttons {
                flex-direction: column;
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
                <i class="fas fa-database me-2"></i>
                FoodApp - Datos
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
                        <a class="nav-link" href="admin_data.php" style="color: var(--primary-color) !important;">
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
            <h1><i class="fas fa-database me-3"></i>Gestión de Datos</h1>
            <p>Administra productos y restaurantes de la plataforma FoodApp</p>
        </div>

        <!-- Alertas de éxito/error -->
        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success fade-in">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger fade-in">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Error: <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="stats-container fade-in">
            <div class="stat-card">
                <i class="fas fa-box stat-icon"></i>
                <div class="stat-value"><?php echo $total_productos; ?></div>
                <div class="stat-label">Total Productos</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-utensils stat-icon"></i>
                <div class="stat-value"><?php echo $total_restaurantes; ?></div>
                <div class="stat-label">Total Restaurantes</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-warehouse stat-icon"></i>
                <div class="stat-value"><?php echo $total_stock; ?></div>
                <div class="stat-label">Stock Total</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-dollar-sign stat-icon"></i>
                <div class="stat-value">S/ <?php echo number_format($total_costos, 2); ?></div>
                <div class="stat-label">Costos Totales</div>
            </div>
        </div>

        <!-- Gestión de Productos -->
        <div class="data-section fade-in">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-box"></i>Productos
                </h2>
                <button class="add-btn" onclick="openModal('addProductModal')">
                    <i class="fas fa-plus"></i>Agregar Producto
                </button>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag me-1"></i>ID</th>
                            <th><i class="fas fa-utensils me-1"></i>Nombre</th>
                            <th><i class="fas fa-align-left me-1"></i>Descripción</th>
                            <th><i class="fas fa-dollar-sign me-1"></i>Precio</th>
                            <th><i class="fas fa-warehouse me-1"></i>Stock</th>
                            <th><i class="fas fa-store me-1"></i>Restaurante</th>
                            <th><i class="fas fa-cogs me-1"></i>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result_lista_productos->num_rows === 0) {
                            echo '<tr><td colspan="7">
                                <div class="empty-state">
                                    <i class="fas fa-box"></i>
                                    <h3>No hay productos registrados</h3>
                                    <p>Agrega el primer producto para comenzar.</p>
                                </div>
                            </td></tr>';
                        } else {
                            while ($producto = $result_lista_productos->fetch_assoc()) {
                                $id = intval($producto['id']);
                                $nombre = htmlspecialchars($producto['nombre']);
                                $descripcion = htmlspecialchars($producto['descripcion']);
                                $precio = 'S/ ' . number_format($producto['precio'], 2);
                                $stock = intval($producto['stock']);
                                $restaurante = htmlspecialchars($producto['restaurante']);
                                $restaurante_id = intval($producto['restaurante_id']);

                                echo "<tr>
                                        <td><span class='product-id'>#{$id}</span></td>
                                        <td><span class='product-name'>{$nombre}</span></td>
                                        <td>{$descripcion}</td>
                                        <td><span class='product-price'>{$precio}</span></td>
                                        <td><span class='product-stock'>{$stock} unidades</span></td>
                                        <td><span class='restaurant-name'>{$restaurante}</span></td>
                                        <td>
                                            <div class='action-buttons'>
                                                <button class='action-btn edit-btn' onclick=\"editProduct({$id}, '{$nombre}', '{$descripcion}', {$producto['precio']}, {$stock}, {$restaurante_id})\">
                                                    <i class='fas fa-edit'></i>Editar
                                                </button>
                                                <button class='action-btn delete-btn' onclick=\"confirmDelete('product', {$id})\">
                                                    <i class='fas fa-trash'></i>Eliminar
                                                </button>
                                            </div>
                                        </td>
                                    </tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Gestión de Restaurantes -->
        <div class="data-section fade-in">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-store"></i>Restaurantes
                </h2>
                <button class="add-btn" onclick="openModal('addRestaurantModal')">
                    <i class="fas fa-plus"></i>Agregar Restaurante
                </button>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag me-1"></i>ID</th>
                            <th><i class="fas fa-store me-1"></i>Nombre</th>
                            <th><i class="fas fa-map-marker-alt me-1"></i>Dirección</th>
                            <th><i class="fas fa-phone me-1"></i>Teléfono</th>
                            <th><i class="fas fa-cogs me-1"></i>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result_lista_restaurantes->num_rows === 0) {
                            echo '<tr><td colspan="5">
                                <div class="empty-state">
                                    <i class="fas fa-store"></i>
                                    <h3>No hay restaurantes registrados</h3>
                                    <p>Agrega el primer restaurante para comenzar.</p>
                                </div>
                            </td></tr>';
                        } else {
                            while ($restaurante = $result_lista_restaurantes->fetch_assoc()) {
                                $id = intval($restaurante['id']);
                                $nombre = htmlspecialchars($restaurante['nombre']);
                                $direccion = htmlspecialchars($restaurante['direccion']);
                                $telefono = htmlspecialchars($restaurante['telefono']);

                                echo "<tr>
                                        <td><span class='product-id'>#{$id}</span></td>
                                        <td><span class='restaurant-name'>{$nombre}</span></td>
                                        <td>{$direccion}</td>
                                        <td>{$telefono}</td>
                                        <td>
                                            <div class='action-buttons'>
                                                <button class='action-btn edit-btn' onclick=\"editRestaurant({$id}, '{$nombre}', '{$direccion}', '{$telefono}')\">
                                                    <i class='fas fa-edit'></i>Editar
                                                </button>
                                                <button class='action-btn delete-btn' onclick=\"confirmDelete('restaurant', {$id})\">
                                                    <i class='fas fa-trash'></i>Eliminar
                                                </button>
                                            </div>
                                        </td>
                                    </tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal para agregar producto -->
    <div id="addProductModal" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Agregar Producto
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="../controllers/add_product.php" method="post">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nombre" class="form-label">Nombre del Producto</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="precio" class="form-label">Precio (S/)</label>
                                <input type="number" step="0.01" class="form-control" id="precio" name="precio" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="stock" class="form-label">Stock</label>
                                <input type="number" class="form-control" id="stock" name="stock" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="restaurante_id" class="form-label">Restaurante</label>
                                <select class="form-select" id="restaurante_id" name="restaurante_id" required>
                                    <?php
                                    $result_rest = $conn->query("SELECT id, nombre FROM restaurantes ORDER BY nombre");
                                    while ($rest = $result_rest->fetch_assoc()) {
                                        echo "<option value='{$rest['id']}'>{$rest['nombre']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-custom flex-fill">
                                <i class="fas fa-plus me-2"></i>Agregar Producto
                            </button>
                            <button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Cerrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para editar producto -->
    <div id="editProductModal" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Editar Producto
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="../controllers/edit_product.php" method="post">
                        <input type="hidden" id="edit_product_id" name="id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_nombre" class="form-label">Nombre del Producto</label>
                                <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_precio" class="form-label">Precio (S/)</label>
                                <input type="number" step="0.01" class="form-control" id="edit_precio" name="precio" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_stock" class="form-label">Stock</label>
                                <input type="number" class="form-control" id="edit_stock" name="stock" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_restaurante_id" class="form-label">Restaurante</label>
                                <select class="form-select" id="edit_restaurante_id" name="restaurante_id" required>
                                    <?php
                                    $result_rest = $conn->query("SELECT id, nombre FROM restaurantes ORDER BY nombre");
                                    while ($rest = $result_rest->fetch_assoc()) {
                                        echo "<option value='{$rest['id']}'>{$rest['nombre']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="edit_descripcion" name="descripcion" rows="3"></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-custom flex-fill">
                                <i class="fas fa-save me-2"></i>Actualizar Producto
                            </button>
                            <button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Cerrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para agregar restaurante -->
    <div id="addRestaurantModal" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Agregar Restaurante
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="../controllers/add_restaurant.php" method="post">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="rest_nombre" class="form-label">Nombre del Restaurante</label>
                                <input type="text" class="form-control" id="rest_nombre" name="nombre" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="telefono" name="telefono" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="direccion" class="form-label">Dirección</label>
                            <input type="text" class="form-control" id="direccion" name="direccion" required>
                        </div>
                        <hr>
                        <h6 class="mb-3">Crear cuenta asociada (opcional)</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="rest_email" class="form-label">Email de la cuenta</label>
                                <input type="email" class="form-control" id="rest_email" name="email" placeholder="correo@restaurante.com">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="rest_password" class="form-label">Contraseña inicial</label>
                                <input type="password" class="form-control" id="rest_password" name="password" placeholder="Contraseña temporal">
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-custom flex-fill">
                                <i class="fas fa-plus me-2"></i>Agregar Restaurante
                            </button>
                            <button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Cerrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para editar restaurante -->
    <div id="editRestaurantModal" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Editar Restaurante
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="../controllers/edit_restaurant.php" method="post">
                        <input type="hidden" id="edit_restaurant_id" name="id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_rest_nombre" class="form-label">Nombre del Restaurante</label>
                                <input type="text" class="form-control" id="edit_rest_nombre" name="nombre" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_telefono" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="edit_telefono" name="telefono" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_direccion" class="form-label">Dirección</label>
                            <input type="text" class="form-control" id="edit_direccion" name="direccion" required>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-custom flex-fill">
                                <i class="fas fa-save me-2"></i>Actualizar Restaurante
                            </button>
                            <button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Cerrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openModal(modalId) {
            const modal = new bootstrap.Modal(document.getElementById(modalId));
            modal.show();
        }

        function editProduct(id, nombre, descripcion, precio, stock, restaurante_id) {
            document.getElementById('edit_product_id').value = id;
            document.getElementById('edit_nombre').value = nombre;
            document.getElementById('edit_descripcion').value = descripcion;
            document.getElementById('edit_precio').value = precio;
            document.getElementById('edit_stock').value = stock;
            document.getElementById('edit_restaurante_id').value = restaurante_id;
            const modal = new bootstrap.Modal(document.getElementById('editProductModal'));
            modal.show();
        }

        function editRestaurant(id, nombre, direccion, telefono) {
            document.getElementById('edit_restaurant_id').value = id;
            document.getElementById('edit_rest_nombre').value = nombre;
            document.getElementById('edit_direccion').value = direccion;
            document.getElementById('edit_telefono').value = telefono;
            const modal = new bootstrap.Modal(document.getElementById('editRestaurantModal'));
            modal.show();
        }

        function confirmDelete(type, id) {
            if (confirm('¿Estás seguro de que quieres eliminar este ' + (type === 'product' ? 'producto' : 'restaurante') + '? Esta acción no se puede deshacer.')) {
                window.location.href = '../controllers/delete_' + type + '.php?id=' + id;
            }
        }

        // Animación de fade-in para las filas de la tabla
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('.data-table tbody tr');
            rows.forEach((row, index) => {
                if (!row.querySelector('.empty-state')) {
                    row.style.opacity = '0';
                    row.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        row.style.transition = 'all 0.5s ease';
                        row.style.opacity = '1';
                        row.style.transform = 'translateY(0)';
                    }, index * 50);
                }
            });
        });
    </script>
</body>
</html>
