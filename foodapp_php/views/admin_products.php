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
    <title>Gestión de Productos - Admin FoodApp</title>
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

        .products-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .products-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(255, 107, 53, 0.1);
        }

        .products-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin: 0;
        }

        .products-stats {
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

        .add-product-btn {
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

        .add-product-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 53, 0.3);
            color: white;
            text-decoration: none;
        }

        .products-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 1rem;
        }

        .products-table thead th {
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

        .products-table tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .products-table tbody tr:hover {
            background: rgba(255, 107, 53, 0.05);
            transform: scale(1.01);
        }

        .products-table tbody td {
            padding: 1rem;
            border: none;
            vertical-align: middle;
        }

        .product-id {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.1rem;
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

        .product-category {
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

        .availability-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            min-width: 80px;
            display: inline-block;
        }

        .available {
            background: rgba(40, 167, 69, 0.2);
            color: #155724;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .unavailable {
            background: rgba(220, 53, 69, 0.2);
            color: #721c24;
            border: 1px solid rgba(220, 53, 69, 0.3);
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

        .product-thumbnail {
            transition: transform 0.3s ease;
            cursor: pointer;
        }

        .product-thumbnail:hover {
            transform: scale(1.1);
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

            .products-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .products-stats {
                width: 100%;
                justify-content: space-between;
            }

            .products-table {
                font-size: 0.85rem;
            }

            .products-table thead th,
            .products-table tbody td {
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
                <i class="fas fa-utensils me-2"></i>
                FoodApp - Productos
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
                        <a class="nav-link" href="admin_products.php" style="color: var(--primary-color) !important;">
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
            <h1><i class="fas fa-utensils me-3"></i>Gestión de Productos</h1>
            <p>Administra todos los productos de la plataforma FoodApp</p>
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

        <div class="products-container fade-in">
            <div class="products-header">
                <h2 class="products-title">
                    <i class="fas fa-list me-2"></i>Lista de Productos
                </h2>
                <div class="products-stats">
                    <?php
                    $total_products = $conn->query("SELECT COUNT(*) as count FROM productos")->fetch_assoc()['count'];
                    $available_products = $conn->query("SELECT COUNT(*) as count FROM productos WHERE disponible = 1")->fetch_assoc()['count'];
                    $categories_count = $conn->query("SELECT COUNT(DISTINCT categoria) as count FROM productos")->fetch_assoc()['count'];
                    ?>
                    <span class="stat-badge">
                        <i class="fas fa-utensils me-1"></i><?php echo $total_products; ?> Total
                    </span>
                    <span class="stat-badge">
                        <i class="fas fa-check-circle me-1"></i><?php echo $available_products; ?> Disponibles
                    </span>
                    <span class="stat-badge">
                        <i class="fas fa-tags me-1"></i><?php echo $categories_count; ?> Categorías
                    </span>
                </div>
            </div>

            <button class="add-product-btn" onclick="openModal()">
                <i class="fas fa-plus"></i>Agregar Producto
            </button>

            <div class="table-responsive">
                <table class="products-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag me-1"></i>ID</th>
                            <th><i class="fas fa-image me-1"></i>Imagen</th>
                            <th><i class="fas fa-utensils me-1"></i>Nombre</th>
                            <th><i class="fas fa-dollar-sign me-1"></i>Precio</th>
                            <th><i class="fas fa-tag me-1"></i>Categoría</th>
                            <th><i class="fas fa-store me-1"></i>Restaurante</th>
                            <th><i class="fas fa-toggle-on me-1"></i>Disponible</th>
                            <th><i class="fas fa-cogs me-1"></i>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $conn->query("SELECT p.id, p.nombre, p.precio, p.categoria, p.imagen, r.nombre as restaurante, p.disponible FROM productos p JOIN restaurantes r ON p.restaurante_id = r.id ORDER BY p.id");
                        if ($result->num_rows === 0) {
                            echo '<tr><td colspan="8">
                                <div class="empty-state">
                                    <i class="fas fa-utensils"></i>
                                    <h3>No hay productos registrados</h3>
                                    <p>Agrega el primer producto para comenzar.</p>
                                </div>
                            </td></tr>';
                        } else {
                            while ($row = $result->fetch_assoc()) {
                                $id = intval($row['id']);
                                $nombre = htmlspecialchars($row['nombre']);
                                $precio = 'S/ ' . number_format($row['precio'], 2);
                                $categoria = htmlspecialchars($row['categoria']);
                                $imagen = $row['imagen']; // No aplicar htmlspecialchars a URLs
                                $restaurante = htmlspecialchars($row['restaurante']);
                                $disponible = $row['disponible'] ? 'available' : 'unavailable';
                                $disponible_text = $row['disponible'] ? 'Sí' : 'No';

                                // Determinar la imagen a mostrar
                                $imagen_src = '';
                                $imagen_alt = 'Sin imagen';
                                if (!empty($imagen)) {
                                    if (filter_var($imagen, FILTER_VALIDATE_URL)) {
                                        // Es una URL externa (Unsplash, etc.)
                                        $imagen_src = $imagen;
                                    } elseif (strpos($imagen, 'uploads/') === 0) {
                                        // Es una ruta local que comienza con uploads/
                                        $imagen_src = '../' . $imagen;
                                    } elseif (file_exists('../' . $imagen)) {
                                        // Verificar si existe como archivo local
                                        $imagen_src = '../' . $imagen;
                                    }
                                }
                                if (empty($imagen_src)) {
                                    $imagen_src = 'https://via.placeholder.com/60x60/cccccc/666666?text=Sin+Imagen';
                                }

                                echo "<tr>
                                        <td><span class='product-id'>#{$id}</span></td>
                                        <td><img src='{$imagen_src}' alt='{$imagen_alt}' class='product-thumbnail' style='width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 2px solid #e9ecef;'></td>
                                        <td><span class='product-name'>{$nombre}</span></td>
                                        <td><span class='product-price'>{$precio}</span></td>
                                        <td><span class='product-category'>{$categoria}</span></td>
                                        <td><span class='restaurant-name'>{$restaurante}</span></td>
                                        <td><span class='availability-badge {$disponible}'>{$disponible_text}</span></td>
                                        <td>
                                            <div class='action-buttons'>
                                                <a href='?edit={$id}' class='action-btn edit-btn'>
                                                    <i class='fas fa-edit'></i>Editar
                                                </a>
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
                    <form action="../controllers/admin_actions.php?action=add_product" method="post">
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
                                <label for="categoria" class="form-label">Categoría</label>
                                <input type="text" class="form-control" id="categoria" name="categoria" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="restaurante_id" class="form-label">Restaurante</label>
                                <select class="form-select" id="restaurante_id" name="restaurante_id" required>
                                    <?php
                                    $rests = $conn->query("SELECT id, nombre FROM restaurantes ORDER BY nombre");
                                    while ($r = $rests->fetch_assoc()) {
                                        echo "<option value='{$r['id']}'>{$r['nombre']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="imagen" class="form-label">URL de la Imagen</label>
                            <input type="url" class="form-control" id="imagen" name="imagen" placeholder="https://ejemplo.com/imagen.jpg">
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="disponible" name="disponible" checked>
                                <label class="form-check-label" for="disponible">
                                    Producto disponible para pedidos
                                </label>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-custom flex-fill">
                                <i class="fas fa-plus me-2"></i>Agregar Producto
                            </button>
                            <button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php
    // Modal para editar producto
    if (isset($_GET['edit'])) {
        $edit_id = intval($_GET['edit']);
        $edit_result = $conn->query("SELECT * FROM productos WHERE id = $edit_id");
        if ($edit_result->num_rows > 0) {
            $edit_row = $edit_result->fetch_assoc();
            ?>
            <div id='editProductModal' class='modal fade show' style='display: block;' tabindex='-1'>
                <div class='modal-dialog modal-lg'>
                    <div class='modal-content'>
                        <div class='modal-header'>
                            <h5 class='modal-title'>
                                <i class='fas fa-edit me-2'></i>Editar Producto
                            </h5>
                            <button type='button' class='btn-close' onclick="window.location.href='admin_products.php'"></button>
                        </div>
                        <div class='modal-body'>
                            <form action='../controllers/admin_actions.php?action=edit_product' method='post' enctype='multipart/form-data'>
                                <input type='hidden' name='id' value='<?php echo $edit_row['id']; ?>'>
                                <div class='row'>
                                    <div class='col-md-6 mb-3'>
                                        <label for='edit_nombre' class='form-label'>Nombre del Producto</label>
                                        <input type='text' id='edit_nombre' name='nombre' class='form-control' value='<?php echo htmlspecialchars($edit_row['nombre']); ?>' required>
                                    </div>
                                    <div class='col-md-6 mb-3'>
                                        <label for='edit_precio' class='form-label'>Precio (S/)</label>
                                        <input type='number' step='0.01' id='edit_precio' name='precio' class='form-control' value='<?php echo $edit_row['precio']; ?>' required>
                                    </div>
                                </div>
                                <div class='row'>
                                    <div class='col-md-6 mb-3'>
                                        <label for='edit_categoria' class='form-label'>Categoría</label>
                                        <input type='text' id='edit_categoria' name='categoria' class='form-control' value='<?php echo htmlspecialchars($edit_row['categoria']); ?>' required>
                                    </div>
                                    <div class='col-md-6 mb-3'>
                                        <label for='edit_restaurante_id' class='form-label'>Restaurante</label>
                                        <select id='edit_restaurante_id' name='restaurante_id' class='form-select' required>
                                            <?php
                                            $rests = $conn->query("SELECT id, nombre FROM restaurantes ORDER BY nombre");
                                            while ($r = $rests->fetch_assoc()) {
                                                $selected = ($edit_row['restaurante_id'] == $r['id']) ? ' selected' : '';
                                                echo "<option value='{$r['id']}'{$selected}>{$r['nombre']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class='mb-3'>
                                    <label for='edit_descripcion' class='form-label'>Descripción</label>
                                    <textarea id='edit_descripcion' name='descripcion' class='form-control' rows='3'><?php echo htmlspecialchars($edit_row['descripcion']); ?></textarea>
                                </div>
                                <div class='mb-3'>
                                    <label for='edit_imagen' class='form-label'>Imagen del Producto</label>
                                    <input type='file' id='edit_imagen' name='imagen' class='form-control' accept='image/*'>
                                    <small class='text-muted'>Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 2MB</small>
                                    <?php if (!empty($edit_row['imagen'])): ?>
                                        <div class='mt-2'>
                                            <small class='text-muted'>Imagen actual:</small><br>
                                            <img src='<?php echo htmlspecialchars($edit_row['imagen']); ?>' alt='Imagen actual' style='max-width: 100px; max-height: 100px; border: 1px solid #ddd; border-radius: 5px;'>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class='mb-3'>
                                    <div class='form-check'>
                                        <input class='form-check-input' type='checkbox' id='edit_disponible' name='disponible' <?php echo $edit_row['disponible'] ? 'checked' : ''; ?>>
                                        <label class='form-check-label' for='edit_disponible'>
                                            Producto disponible para pedidos
                                        </label>
                                    </div>
                                </div>
                                <div class='d-flex gap-2'>
                                    <button type='submit' class='btn btn-custom flex-fill'>
                                        <i class='fas fa-save me-2'></i>Actualizar Producto
                                    </button>
                                    <button type='button' onclick="window.location.href='admin_products.php'" class='btn btn-secondary flex-fill'>
                                        <i class='fas fa-times me-2'></i>Cancelar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
    }
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openModal() {
            const modal = new bootstrap.Modal(document.getElementById('addProductModal'));
            modal.show();
        }

        function confirmDelete(type, id) {
            if (confirm('¿Estás seguro de que quieres eliminar este producto? Esta acción no se puede deshacer.')) {
                window.location.href = '../controllers/admin_actions.php?action=delete_' + type + '&id=' + id;
            }
        }

        // Animación de fade-in para las filas de la tabla
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('.products-table tbody tr');
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
