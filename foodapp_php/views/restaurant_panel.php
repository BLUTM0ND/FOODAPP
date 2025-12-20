<!DOCTYPE html>
<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'restaurante') {
    header('Location: login.php');
    exit;
}
include_once '../includes/config.php';

// Get restaurant ID for this user
$user_id = intval($_SESSION['user_id']);
$restaurant_query = $conn->prepare("SELECT r.id, r.nombre, r.direccion, r.ubicacion_gps, r.calificacion, u.telefono FROM restaurantes r LEFT JOIN usuarios u ON r.usuario_id = u.id WHERE r.usuario_id = ? LIMIT 1");
$restaurant_query->bind_param('i', $user_id);
$restaurant_query->execute();
$restaurant_result = $restaurant_query->get_result();
if ($restaurant_result->num_rows == 0) {
    echo "No restaurant found for this user.";
    exit;
}
$restaurant = $restaurant_result->fetch_assoc();
$restaurant_id = $restaurant['id'];
$restaurant_name = $restaurant['nombre'];
$restaurant_address = $restaurant['direccion'] ?? 'No especificada';
$restaurant_phone = $restaurant['telefono'] ?? 'No especificado';
$restaurant_coords = $restaurant['ubicacion_gps'] ?? '';
$restaurant_rating = $restaurant['calificacion'] ?? 0;
$restaurant_query->close();

// Get real ratings data (if table exists, otherwise use default)
try {
    $ratings_query = $conn->prepare("SELECT COUNT(*) as total_reviews, AVG(calificacion_restaurante) as avg_rating FROM valoraciones WHERE restaurante_id = ?");
    $ratings_query->bind_param('i', $restaurant_id);
    $ratings_query->execute();
    $ratings_result = $ratings_query->get_result();
    $ratings_data = $ratings_result->fetch_assoc();
    $total_reviews = $ratings_data['total_reviews'] ?? 0;
    $avg_rating = $ratings_data['avg_rating'] ?? 0;
    $ratings_query->close();
} catch (Exception $e) {
    // Table doesn't exist yet, use default values
    $total_reviews = 0;
    $avg_rating = 0;
}
?>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Restaurante - <?php echo htmlspecialchars($restaurant_name); ?> - FoodApp</title>
    <link rel="icon" href="../assets/FOODAPP.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Nunito', sans-serif; background: #f8f9fa; }
        .restaurant-container { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: linear-gradient(135deg, #ff441f 0%, #ff7d00 100%); color: white; padding: 1rem; position: fixed; height: 100vh; overflow-y: auto; box-shadow: 2px 0 10px rgba(0,0,0,0.1); }
        .sidebar h2 { margin-bottom: 1rem; text-align: center; font-size: 1.2rem; }
        .sidebar ul { list-style: none; padding: 0; }
        .sidebar li { margin: 0.5rem 0; }
        .sidebar a { color: white; text-decoration: none; display: block; padding: 0.75rem; border-radius: 10px; transition: all 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.2); transform: translateX(5px); }
        .main-content { 
            margin-left: 250px; 
            padding: 2rem; 
            width: calc(100% - 250px);
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
        }
        .card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }

        .btn {
            background: linear-gradient(135deg, #ff441f 0%, #ff7d00 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: linear-gradient(135deg, #e63946 0%, #ff6b35 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,68,31,0.3);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        .btn-success:hover {
            background: linear-gradient(135deg, #218838 0%, #17a2b8 100%);
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: #212529;
        }
        .btn-warning:hover {
            background: linear-gradient(135deg, #e0a800 0%, #e8680d 100%);
        }

        .btn-info {
            background: linear-gradient(135deg, #0dcaf0 0%, #3cc2e0 100%);
            color: white;
        }
        .btn-info:hover {
            background: linear-gradient(135deg, #0aa2c0 0%, #2da8c0 100%);
        }

        .btn-outline-danger {
            background: transparent;
            color: #dc3545;
            border: 2px solid #dc3545;
        }
        .btn-outline-danger:hover {
            background: #dc3545;
            color: white;
        }

        .section { 
            display: none; 
            opacity: 0;
            transform: translateX(-50px);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            padding: 2.5rem;
            border: 1px solid rgba(255,255,255,0.8);
        }
        
        .section:target, .section.active { 
            display: block; 
            opacity: 1;
            transform: translateX(0);
        }

        /* Section Indicators */
        .section-indicators {
            position: fixed;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 100;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 1rem;
            display: none;
        }

        .indicator-item {
            display: block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #ddd;
            margin: 0.5rem auto;
            transition: all 0.3s;
            cursor: pointer;
        }

        .indicator-item.active {
            background: #ff441f;
            transform: scale(1.3);
        }

        /* Stats Cards */
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 1rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .stats-card h3 {
            margin-bottom: 1rem;
            font-size: 2rem;
        }

        .stats-card p {
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        /* Dashboard Cards */
        .dashboard-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 1rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .dashboard-card h3 {
            color: #ff441f;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fefefe; margin: 2% auto; padding: 0; border-radius: 20px; width: 95%; max-width: 800px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: modalFadeIn 0.4s; }
        @keyframes modalFadeIn { from { opacity: 0; transform: translateY(-50px); } to { opacity: 1; transform: translateY(0); } }
        .modal-header { background: linear-gradient(135deg, #ff441f 0%, #ff7d00 100%); color: white; padding: 2rem; border-radius: 20px 20px 0 0; position: relative; }
        .modal-header h2 { margin: 0; font-size: 1.5rem; }
        .modal-body { padding: 2rem; }
        .modal-footer { padding: 1rem 2rem 2rem; text-align: right; border-top: 1px solid #dee2e6; }

        /* Form Styles */
        .form-group { margin-bottom: 1.5rem; }
        .form-label { font-weight: 600; color: #333; margin-bottom: 0.5rem; display: block; }
        .form-control { width: 100%; padding: 0.75rem; border: 2px solid #e9ecef; border-radius: 10px; transition: border-color 0.3s, box-shadow 0.3s; }
        .form-control:focus { border-color: #ff441f; box-shadow: 0 0 0 3px rgba(255,68,31,0.1); outline: none; }
        .form-textarea { resize: vertical; min-height: 100px; }

        /* Action Buttons */
        .action-btn { padding: 0.5rem; border: none; border-radius: 8px; cursor: pointer; transition: all 0.3s; margin: 0 0.25rem; }
        .edit-btn { background: #ffc107; color: #212529; }
        .edit-btn:hover { background: #e0a800; transform: scale(1.1); }
        .delete-btn { background: #dc3545; color: white; }
        .delete-btn:hover { background: #c82333; transform: scale(1.1); }

        /* Status Badges */
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .status-pendiente { background: #fff3cd; color: #856404; }
        .status-confirmado { background: #d1ecf1; color: #0c5460; }
        .status-preparando { background: #d4edda; color: #155724; }
        .status-listo { background: #cce5ff; color: #004085; }
        .status-entregado { background: #d1ecf1; color: #0c5460; }
        .status-cancelado { background: #f8d7da; color: #721c24; }

        /* Loading Spinner */
        .spinner { display: inline-block; width: 20px; height: 20px; border: 3px solid #f3f3f3; border-top: 3px solid #ff441f; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        /* Table Styles */
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #dee2e6; }
        th { background: linear-gradient(135deg, #ff441f 0%, #ff7d00 100%); color: white; font-weight: 600; }
        tr:hover { background: #f8f9fa; }

        /* Chart Containers */
        .chart-container { position: relative; height: 200px; width: 100%; }

        /* Rating Stars */
        .rating-stars { margin-top: 0.5rem; }
        .rating-stars .fas { margin-right: 0.25rem; }

        /* Section Separators */
        .section-separator {
            height: 1px;
            background: linear-gradient(90deg, transparent, #dee2e6, transparent);
            margin: 3rem 0;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; width: 100%; }
            .section-indicators { display: none; }
            .modal-content { width: 98%; max-width: none; margin: 1rem; }
            .modal-header { padding: 1.5rem; }
            .modal-header h2 { font-size: 1.3rem; }
            .modal-body { padding: 1.5rem; }
            .modal-footer { padding: 1rem 1.5rem 2rem; flex-direction: column; }
            .modal-footer .btn { width: 100%; margin-bottom: 0.5rem; }
            .modal-footer .btn:last-child { margin-bottom: 0; }
            .btn { padding: 0.5rem 1rem; font-size: 0.9rem; }
            .card { margin-bottom: 1rem; }
            .dashboard .col-md-6 { margin-bottom: 1rem; }
        }

        /* Image Management Styles */
        .image-upload-container { border: 2px dashed #dee2e6; border-radius: 10px; padding: 1rem; text-align: center; background: #f8f9fa; }
        .image-preview { position: relative; display: inline-block; width: 100%; max-width: 300px; margin: 0 auto; }
        .image-placeholder { padding: 2rem; }
        .gallery-container { border: 2px solid #dee2e6; border-radius: 10px; padding: 1rem; background: #f8f9fa; }
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
        .gallery-item { position: relative; border-radius: 10px; overflow: hidden; background: white; border: 1px solid #dee2e6; }
        .gallery-item img { width: 100%; height: 120px; object-fit: cover; }
        .gallery-item .gallery-actions { position: absolute; top: 0; right: 0; background: rgba(0,0,0,0.7); padding: 0.25rem; border-radius: 0 0 0 10px; }
        .gallery-item .gallery-actions button { background: none; border: none; color: white; padding: 0.25rem; cursor: pointer; }
        .gallery-item .gallery-actions button:hover { color: #ff441f; }

        /* Product Image Styles */
        .product-image-upload-container { border: 2px dashed #dee2e6; border-radius: 10px; padding: 1rem; text-align: center; background: #f8f9fa; }
        .product-image-preview { position: relative; display: inline-block; width: 100%; max-width: 250px; margin: 0 auto; }
        .product-image-placeholder { padding: 1.5rem; }
    </style>

    <style>
        /* Restaurant Images Display */
        .restaurant-image-container { text-align: center; margin-bottom: 1rem; }
        .restaurant-main-image { max-width: 100%; max-height: 200px; border-radius: 10px; object-fit: cover; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .no-image-placeholder { padding: 2rem; border: 2px dashed #dee2e6; border-radius: 10px; background: #f8f9fa; }
        .restaurant-gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 0.5rem; }
        .gallery-thumbnail { width: 100%; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid #dee2e6; }
        .no-gallery-placeholder { padding: 2rem; border: 2px dashed #dee2e6; border-radius: 10px; background: #f8f9fa; text-align: center; grid-column: 1 / -1; }
    </style>
</head>
<body>
    <!-- Modal para agregar/editar productos -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeModal()">&times;</span>
                <h2 id="modalTitle">Agregar Producto</h2>
            </div>
            <div class="modal-body">
                <form id="productModalForm" onsubmit="saveProduct(event)">
                    <input type="hidden" id="productId" name="productId">
                    <div class="form-group">
                        <label for="modalNombre" class="form-label">Nombre del Producto</label>
                        <input type="text" class="form-control" id="modalNombre" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label for="modalDescripcion" class="form-label">Descripción</label>
                        <textarea class="form-control form-textarea" id="modalDescripcion" name="descripcion" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="modalPrecio" class="form-label">Precio (S/)</label>
                        <input type="number" class="form-control" id="modalPrecio" name="precio" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="modalCategoria" class="form-label">Categoría</label>
                        <select class="form-control" id="modalCategoria" name="categoria" required>
                            <option value="">Seleccionar categoría</option>
                            <option value="Platos principales">Platos principales</option>
                            <option value="Entradas">Entradas</option>
                            <option value="Postres">Postres</option>
                            <option value="Bebidas">Bebidas</option>
                            <option value="Otros">Otros</option>
                        </select>
                    </div>

                    <!-- Campo de imagen para productos -->
                    <div class="form-group">
                        <label class="form-label">Imagen del Producto</label>
                        <div class="product-image-upload-container">
                            <div id="productImagePreview" class="product-image-preview">
                                <img id="productImageDisplay" src="" alt="Imagen del producto" style="display: none; max-width: 100%; max-height: 200px; border-radius: 10px; object-fit: cover;">
                                <div id="productImagePlaceholder" class="product-image-placeholder">
                                    <i class="fas fa-camera fa-3x text-muted"></i>
                                    <p class="text-muted mt-2">No hay imagen del producto</p>
                                </div>
                            </div>
                            <div class="product-image-actions mt-2">
                                <input type="file" id="productImageInput" accept="image/*" style="display: none;">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('productImageInput').click()">
                                    <i class="fas fa-upload"></i> Subir Imagen
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm ms-2" id="removeProductImageBtn" style="display: none;" onclick="removeProductImage()">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                <button type="submit" form="productModalForm" class="btn" id="modalSubmitBtn">
                    <span class="spinner" id="modalSpinner" style="display: none;"></span>
                    Guardar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal para editar perfil -->
    <div id="profileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeProfileModal()">&times;</span>
                <h2>Editar Información del Restaurante</h2>
            </div>
            <div class="modal-body">
                <form id="profileForm" onsubmit="saveProfile(event)">
                    <div class="row">
                        <div class="col-md-6">
                            <h4 class="mb-4"><i class="fas fa-info-circle"></i> Información General</h4>
                            <div class="form-group">
                                <label for="profileNombre" class="form-label">Nombre del Restaurante</label>
                                <input type="text" class="form-control" id="profileNombre" name="nombre" required>
                            </div>
                            <div class="form-group">
                                <label for="profileDireccion" class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="profileDireccion" name="direccion" required>
                            </div>
                            <div class="form-group">
                                <label for="profileTelefono" class="form-label">Teléfono</label>
                                <input type="tel" class="form-control" id="profileTelefono" name="telefono" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h4 class="mb-4"><i class="fas fa-map-marked-alt"></i> Ubicación en el Mapa</h4>
                            <div class="form-group">
                                <div id="profileMap" style="height: 350px; border-radius: 15px; margin-bottom: 1rem; border: 2px solid #dee2e6;"></div>
                                <small class="text-muted">
                                    <i class="fas fa-mouse-pointer"></i> Arrastra el marcador o haz click para cambiar la ubicación
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Sección de Imágenes -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h4 class="mb-4"><i class="fas fa-images"></i> Imágenes del Restaurante</h4>

                            <!-- Imagen Principal -->
                            <div class="form-group mb-4">
                                <label class="form-label">Imagen Principal del Restaurante</label>
                                <div class="image-upload-container">
                                    <div id="mainImagePreview" class="image-preview">
                                        <img id="mainImageDisplay" src="" alt="Imagen principal" style="display: none; max-width: 100%; max-height: 200px; border-radius: 10px; object-fit: cover;">
                                        <div id="mainImagePlaceholder" class="image-placeholder">
                                            <i class="fas fa-camera fa-3x text-muted"></i>
                                            <p class="text-muted mt-2">No hay imagen principal</p>
                                        </div>
                                    </div>
                                    <div class="image-actions mt-2">
                                        <input type="file" id="mainImageInput" accept="image/*" style="display: none;">
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('mainImageInput').click()">
                                            <i class="fas fa-upload"></i> Subir Imagen Principal
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm ms-2" id="removeMainImageBtn" style="display: none;" onclick="removeMainImage()">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Galería de Imágenes -->
                            <div class="form-group">
                                <label class="form-label">Galería de Imágenes</label>
                                <div class="gallery-container">
                                    <div id="galleryImages" class="gallery-grid">
                                        <!-- Las imágenes se cargarán aquí dinámicamente -->
                                    </div>
                                    <div class="gallery-actions mt-3">
                                        <input type="file" id="galleryImageInput" accept="image/*" multiple style="display: none;">
                                        <button type="button" class="btn btn-outline-success btn-sm" onclick="document.getElementById('galleryImageInput').click()">
                                            <i class="fas fa-plus"></i> Agregar Imágenes a la Galería
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary me-3" onclick="closeProfileModal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" form="profileForm" class="btn" id="profileSubmitBtn">
                    <span class="spinner" id="profileSpinner" style="display: none;"></span>
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
        <aside class="sidebar">
            <h2>Panel Restaurante</h2>
            <p class="text-center small"><?php echo htmlspecialchars($restaurant_name); ?></p>
            <ul>
                <li><a href="#dashboard" class="nav-link active" data-section="dashboard">📊 Dashboard</a></li>
                <li><a href="#products" class="nav-link" data-section="products">🍽️ Mis Productos</a></li>
                <li><a href="#orders" class="nav-link" data-section="orders">📦 Pedidos</a></li>
                <li><a href="#profile" class="nav-link" data-section="profile">🏪 Mi Restaurante</a></li>
                <li><a href="../controllers/logout.php">🚪 Cerrar Sesión</a></li>
            </ul>
        </aside>
        <main class="main-content">
            <section id="dashboard" class="section active">
                <h1>Dashboard - <?php echo htmlspecialchars($restaurant_name); ?></h1>
                <div class="dashboard row">
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card p-3">
                            <h3>📊 Estadísticas</h3>
                            <?php
                            $total_products = $conn->query("SELECT COUNT(*) as count FROM productos WHERE restaurante_id = $restaurant_id")->fetch_assoc()['count'];
                            $total_orders = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE restaurante_id = $restaurant_id")->fetch_assoc()['count'];
                            $pending_orders = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE restaurante_id = $restaurant_id AND estado IN ('PENDIENTE','CONFIRMADO')")->fetch_assoc()['count'];
                            $total_revenue = $conn->query("SELECT SUM(total) as sum FROM pedidos WHERE restaurante_id = $restaurant_id AND estado != 'CANCELADO'")->fetch_assoc()['sum'] ?? 0;
                            echo "<p>Productos: $total_products</p>";
                            echo "<p>Pedidos Totales: $total_orders</p>";
                            echo "<p>Pedidos Pendientes: $pending_orders</p>";
                            echo "<p>Ingresos Totales: S/ " . number_format($total_revenue, 2) . "</p>";
                            ?>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card p-3">
                            <h3>📈 Pedidos Recientes</h3>
                            <div class="chart-container">
                                <canvas id="ordersChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card p-3">
                            <h3>💰 Ingresos Mensuales</h3>
                            <div class="chart-container">
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card p-3">
                            <h3>⭐ Calificaciones</h3>
                            <p>Promedio: <?php echo number_format($restaurant_rating, 1); ?>/5</p>
                            <p>Total reseñas: <?php echo $total_reviews; ?></p>
                            <?php if ($restaurant_rating > 0): ?>
                                <div class="rating-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= round($restaurant_rating) ? 'text-warning' : 'text-muted'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <div class="section-separator"></div>

            <section id="products" class="section">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-utensils"></i> Mis Productos</h1>
                    <button class="btn" onclick="openProductModal()">
                        <i class="fas fa-plus"></i> Agregar Producto
                    </button>
                </div>
                <div class="card p-4">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Imagen</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Precio</th>
                                    <th>Categoría</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="productsTableBody">
                                <?php
                                $products = $conn->query("SELECT * FROM productos WHERE restaurante_id = $restaurant_id ORDER BY nombre");
                                while ($product = $products->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>{$product['id']}</td>";
                                    echo "<td>";
                                    if (!empty($product['imagen'])) {
                                        $image_url = $product['imagen'];
                                        if (filter_var($image_url, FILTER_VALIDATE_URL)) {
                                            // Es una URL externa
                                        } elseif (strpos($image_url, 'uploads/') === 0) {
                                            // Es una ruta local
                                            $image_url = '/FOODAPP/foodapp_php/' . $image_url;
                                        }
                                        echo "<img src='" . htmlspecialchars($image_url) . "' alt='Producto' style='width: 50px; height: 50px; object-fit: cover; border-radius: 5px;'>";
                                    } else {
                                        echo "<div style='width: 50px; height: 50px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; display: flex; align-items: center; justify-content: center;'><i class='fas fa-image text-muted'></i></div>";
                                    }
                                    echo "</td>";
                                    echo "<td>" . htmlspecialchars($product['nombre']) . "</td>";
                                    echo "<td>" . htmlspecialchars(substr($product['descripcion'], 0, 50)) . (strlen($product['descripcion']) > 50 ? '...' : '') . "</td>";
                                    echo "<td>S/ " . number_format($product['precio'], 2) . "</td>";
                                    echo "<td><span class='badge bg-secondary'>" . htmlspecialchars($product['categoria']) . "</span></td>";
                                    echo "<td>
                                        <button class='action-btn edit-btn' onclick='editProduct({$product['id']})' title='Editar'>
                                            <i class='fas fa-edit'></i>
                                        </button>
                                        <button class='action-btn delete-btn' onclick='deleteProduct({$product['id']})' title='Eliminar'>
                                            <i class='fas fa-trash'></i>
                                        </button>
                                    </td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <div class="section-separator"></div>

            <section id="orders" class="section">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-clipboard-list"></i> Pedidos de Mi Restaurante</h1>
                    <div class="d-flex gap-2">
                        <button class="btn btn-success btn-sm" onclick="filterOrders('PENDIENTE')">Pendientes</button>
                        <button class="btn btn-warning btn-sm" onclick="filterOrders('PREPARANDO')">Preparando</button>
                        <button class="btn btn-info btn-sm" onclick="filterOrders('LISTO')">Listos</button>
                    </div>
                </div>
                <div class="card p-4">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Fecha</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="ordersTableBody">
                                <?php
                                $orders = $conn->query("SELECT p.id, p.fecha_pedido, p.total, p.estado, u.nombre as cliente FROM pedidos p LEFT JOIN clientes c ON p.cliente_id = c.id LEFT JOIN usuarios u ON c.id = u.id WHERE p.restaurante_id = $restaurant_id ORDER BY p.fecha_pedido DESC");
                                while ($order = $orders->fetch_assoc()) {
                                    $statusClass = 'status-' . strtolower($order['estado']);
                                    echo "<tr>";
                                    echo "<td>#{$order['id']}</td>";
                                    echo "<td><i class='fas fa-user'></i> " . htmlspecialchars($order['cliente']) . "</td>";
                                    echo "<td><i class='fas fa-calendar'></i> " . date('d/m/Y H:i', strtotime($order['fecha_pedido'])) . "</td>";
                                    echo "<td><strong>S/ " . number_format($order['total'], 2) . "</strong></td>";
                                    echo "<td><span class='status-badge {$statusClass}'>" . htmlspecialchars($order['estado']) . "</span></td>";
                                    echo "<td class='action-buttons'>";
                                    if ($order['estado'] === 'PENDIENTE' || $order['estado'] === 'CONFIRMADO') {
                                        echo "<button class='btn btn-success btn-sm me-1' onclick='updateOrderStatus({$order['id']}, \"PREPARANDO\")'><i class='fas fa-play'></i> Preparar</button>";
                                    } elseif ($order['estado'] === 'PREPARANDO') {
                                        echo "<button class='btn btn-warning btn-sm me-1' onclick='updateOrderStatus({$order['id']}, \"LISTO\")'><i class='fas fa-check'></i> Listo</button>";
                                    } elseif ($order['estado'] === 'LISTO') {
                                        echo "<button class='btn btn-info btn-sm me-1' onclick='updateOrderStatus({$order['id']}, \"EN_CAMINO\")'><i class='fas fa-truck'></i> En Camino</button>";
                                    }
                                    echo "<button class='btn btn-outline-danger btn-sm' onclick='cancelOrder({$order['id']})'><i class='fas fa-times'></i> Cancelar</button>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <div class="section-separator"></div>

            <section id="profile" class="section">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-store"></i> Mi Restaurante</h1>
                    <button class="btn" onclick="openProfileModal()">
                        <i class="fas fa-edit"></i> Editar Información
                    </button>
                </div>
                <div class="row">
                    <div class="col-md-8">
                        <div class="card p-4">
                            <h3><i class="fas fa-info-circle"></i> Información del Restaurante</h3>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Nombre</label>
                                        <p class="h5"><?php echo htmlspecialchars($restaurant_name); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Dirección</label>
                                        <p class="h5"><i class="fas fa-map-marker-alt text-danger"></i> <?php echo htmlspecialchars($restaurant_address); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Teléfono</label>
                                        <p class="h5"><i class="fas fa-phone text-success"></i> <?php echo htmlspecialchars($restaurant_phone); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Calificación</label>
                                        <p class="h5">
                                            <i class="fas fa-star text-warning"></i> <?php echo number_format($restaurant_rating, 1); ?>/5
                                            <small class="text-muted">(<?php echo $total_reviews; ?> reseñas)</small>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($restaurant_coords)): ?>
                            <div class="mt-4">
                                <h5><i class="fas fa-map-marked-alt"></i> Ubicación en el Mapa</h5>
                                <div id="restaurantMap" style="height: 300px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);"></div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Restaurant Images Section -->
                        <div class="card p-4 mt-4">
                            <h3><i class="fas fa-images"></i> Imágenes del Restaurante</h3>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <h5>Imagen Principal</h5>
                                    <div class="restaurant-image-container">
                                        <?php
                                        $restaurant_image = $restaurant['imagen'] ?? null;
                                        if ($restaurant_image):
                                            $image_url = $restaurant_image;
                                            if (filter_var($image_url, FILTER_VALIDATE_URL)) {
                                                // Es una URL externa
                                            } elseif (strpos($image_url, 'uploads/') === 0) {
                                                // Es una ruta local
                                                $image_url = '/FOODAPP/foodapp_php/' . $image_url;
                                            }
                                            ?>
                                            <img src="<?php echo htmlspecialchars($image_url); ?>" alt="Imagen principal del restaurante" class="restaurant-main-image">
                                        <?php else: ?>
                                            <div class="no-image-placeholder">
                                                <i class="fas fa-camera fa-3x text-muted"></i>
                                                <p class="text-muted mt-2">No hay imagen principal</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5>Galería de Imágenes</h5>
                                    <div class="restaurant-gallery">
                                        <?php
                                        // Get gallery images
                                        $gallery_query = $conn->prepare("SELECT url_imagen FROM imagenes_restaurante WHERE restaurante_id = ? ORDER BY orden ASC, fecha_subida DESC LIMIT 6");
                                        $gallery_query->bind_param('i', $restaurant_id);
                                        $gallery_query->execute();
                                        $gallery_result = $gallery_query->get_result();

                                        if ($gallery_result->num_rows > 0):
                                            while ($image = $gallery_result->fetch_assoc()):
                                                $gallery_image_url = $image['url_imagen'];
                                                if (filter_var($gallery_image_url, FILTER_VALIDATE_URL)) {
                                                    // Es una URL externa
                                                } elseif (strpos($gallery_image_url, 'uploads/') === 0) {
                                                    // Es una ruta local
                                                    $gallery_image_url = '/FOODAPP/foodapp_php/' . $gallery_image_url;
                                                }
                                                ?>
                                                <img src="<?php echo htmlspecialchars($gallery_image_url); ?>" alt="Imagen de galería" class="gallery-thumbnail">
                                            <?php endwhile;
                                        else: ?>
                                            <div class="no-gallery-placeholder">
                                                <i class="fas fa-images fa-2x text-muted"></i>
                                                <p class="text-muted mt-2">No hay imágenes en la galería</p>
                                            </div>
                                        <?php endif;
                                        $gallery_query->close();
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card p-4 text-center">
                            <h3><i class="fas fa-chart-line"></i> Estadísticas Rápidas</h3>
                            <div class="mt-3">
                                <div class="h2 text-primary"><?php echo $total_products; ?></div>
                                <p class="text-muted">Productos</p>
                                <hr>
                                <div class="h2 text-success"><?php echo $total_orders; ?></div>
                                <p class="text-muted">Pedidos Totales</p>
                                <hr>
                                <div class="h2 text-warning"><?php echo $pending_orders; ?></div>
                                <p class="text-muted">Pedidos Activos</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Section Indicator -->
            <div class="section-indicator" id="sectionIndicator">
                <div class="indicator-item active" data-section="dashboard" title="Dashboard"></div>
                <div class="indicator-item" data-section="products" title="Productos"></div>
                <div class="indicator-item" data-section="orders" title="Pedidos"></div>
                <div class="indicator-item" data-section="profile" title="Perfil"></div>
            </div>
        </main>
    </div>

    <script>
        // Toast notification system
        function showToast(message, type = 'success') {
            const toastContainer = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `
                <div class="toast-icon">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                </div>
                <div class="toast-message">${message}</div>
                <div class="toast-close" onclick="this.parentElement.remove()">×</div>
            `;
            toastContainer.appendChild(toast);

            // Auto remove after 5 seconds
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 5000);
        }

        // Modal functions
        function openProductModal(productId = null) {
            const modal = document.getElementById('productModal');
            const form = document.getElementById('productModalForm');
            const title = document.getElementById('modalTitle');

            if (productId) {
                title.textContent = 'Editar Producto';
                // Load product data
                fetch(`../controllers/restaurant_actions.php?action=get_product&product_id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('productId').value = data.product.id;
                        document.getElementById('modalNombre').value = data.product.nombre;
                        document.getElementById('modalDescripcion').value = data.product.descripcion;
                        document.getElementById('modalPrecio').value = data.product.precio;
                        document.getElementById('modalCategoria').value = data.product.categoria;
                        
                        // Set product image
                        if (data.product.imagen) {
                            let imageUrl = data.product.imagen;
                            if (!imageUrl.startsWith('http')) {
                                imageUrl = '../' + imageUrl;
                            }
                            document.getElementById('productImageDisplay').src = imageUrl;
                            document.getElementById('productImageDisplay').style.display = 'block';
                            document.getElementById('productImagePlaceholder').style.display = 'none';
                            document.getElementById('removeProductImageBtn').style.display = 'inline-block';
                        } else {
                            resetProductImage();
                        }
                    }
                });
            } else {
                title.textContent = 'Agregar Producto';
                form.reset();
                document.getElementById('productId').value = '';
                // Reset product image
                resetProductImage();
            }

            modal.style.display = 'block';
        }

        function closeModal() {
            document.getElementById('productModal').style.display = 'none';
        }

        function openProfileModal() {
            const modal = document.getElementById('profileModal');
            // Load current profile data
            document.getElementById('profileNombre').value = '<?php echo addslashes($restaurant_name); ?>';
            document.getElementById('profileDireccion').value = '<?php echo addslashes($restaurant['direccion'] ?? ''); ?>';
            document.getElementById('profileTelefono').value = '<?php echo addslashes($restaurant['telefono'] ?? ''); ?>';
            modal.style.display = 'block';
        }

        function closeProfileModal() {
            document.getElementById('profileModal').style.display = 'none';

            // Remove resize listener when modal closes
            if (profileMap && profileMap._resizeHandler) {
                window.removeEventListener('resize', profileMap._resizeHandler);
                profileMap._resizeHandler = null;
            }
        }

        // Image management functions
        let mainImageUrl = '';
        let galleryImages = [];

        function loadRestaurantImages() {
            fetch('../controllers/restaurant_actions.php?action=get_restaurant_images')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Load main image
                        if (data.main_image) {
                            mainImageUrl = data.main_image;
                            document.getElementById('mainImageDisplay').src = data.main_image;
                            document.getElementById('mainImageDisplay').style.display = 'block';
                            document.getElementById('mainImagePlaceholder').style.display = 'none';
                            document.getElementById('removeMainImageBtn').style.display = 'inline-block';
                        } else {
                            document.getElementById('mainImageDisplay').style.display = 'none';
                            document.getElementById('mainImagePlaceholder').style.display = 'block';
                            document.getElementById('removeMainImageBtn').style.display = 'none';
                        }

                        // Load gallery images
                        galleryImages = data.gallery_images || [];
                        renderGallery();
                    }
                })
                .catch(error => console.error('Error loading images:', error));
        }

        function renderGallery() {
            const galleryContainer = document.getElementById('galleryImages');
            galleryContainer.innerHTML = '';

            galleryImages.forEach((image, index) => {
                const galleryItem = document.createElement('div');
                galleryItem.className = 'gallery-item';
                galleryItem.innerHTML = `
                    <img src="${image.url_imagen}" alt="Imagen ${index + 1}">
                    <div class="gallery-actions">
                        <button onclick="deleteGalleryImage(${image.id})" title="Eliminar imagen">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
                galleryContainer.appendChild(galleryItem);
            });
        }

        // Main image upload
        document.getElementById('mainImageInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                uploadMainImage(file);
            }
        });

        function uploadMainImage(file) {
            const formData = new FormData();
            formData.append('image', file);
            formData.append('type', 'main');

            fetch('../controllers/restaurant_actions.php?action=upload_restaurant_image', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mainImageUrl = data.image_url;
                    document.getElementById('mainImageDisplay').src = data.image_url;
                    document.getElementById('mainImageDisplay').style.display = 'block';
                    document.getElementById('mainImagePlaceholder').style.display = 'none';
                    document.getElementById('removeMainImageBtn').style.display = 'inline-block';
                    showToast('Imagen principal subida correctamente', 'success');
                } else {
                    showToast(data.message || 'Error al subir imagen', 'error');
                }
            })
            .catch(error => {
                showToast('Error al subir imagen', 'error');
                console.error('Error:', error);
            });
        }

        function removeMainImage() {
            if (confirm('¿Estás seguro de que deseas eliminar la imagen principal?')) {
                fetch('../controllers/restaurant_actions.php?action=delete_restaurant_image', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'type=main'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mainImageUrl = '';
                        document.getElementById('mainImageDisplay').style.display = 'none';
                        document.getElementById('mainImagePlaceholder').style.display = 'block';
                        document.getElementById('removeMainImageBtn').style.display = 'none';
                        showToast('Imagen principal eliminada', 'success');
                    } else {
                        showToast(data.message || 'Error al eliminar imagen', 'error');
                    }
                })
                .catch(error => {
                    showToast('Error al eliminar imagen', 'error');
                    console.error('Error:', error);
                });
            }
        }

        // Gallery image upload
        document.getElementById('galleryImageInput').addEventListener('change', function(e) {
            const files = Array.from(e.target.files);
            if (files.length > 0) {
                uploadGalleryImages(files);
            }
        });

        function uploadGalleryImages(files) {
            const uploadPromises = files.map(file => {
                const formData = new FormData();
                formData.append('image', file);
                formData.append('type', 'gallery');

                return fetch('../controllers/restaurant_actions.php?action=upload_restaurant_image', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json());
            });

            Promise.all(uploadPromises)
                .then(results => {
                    const successCount = results.filter(r => r.success).length;
                    const failCount = results.length - successCount;

                    if (successCount > 0) {
                        showToast(`${successCount} imagen(es) subida(s) correctamente`, 'success');
                        loadRestaurantImages(); // Reload gallery
                    }
                    if (failCount > 0) {
                        showToast(`${failCount} imagen(es) no se pudieron subir`, 'error');
                    }
                })
                .catch(error => {
                    showToast('Error al subir imágenes', 'error');
                    console.error('Error:', error);
                });
        }

        function deleteGalleryImage(imageId) {
            if (confirm('¿Estás seguro de que deseas eliminar esta imagen de la galería?')) {
                fetch('../controllers/restaurant_actions.php?action=delete_restaurant_image', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'image_id=' + encodeURIComponent(imageId)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Imagen eliminada de la galería', 'success');
                        loadRestaurantImages(); // Reload gallery
                    } else {
                        showToast(data.message || 'Error al eliminar imagen', 'error');
                    }
                })
                .catch(error => {
                    showToast('Error al eliminar imagen', 'error');
                    console.error('Error:', error);
                });
            }
        }

        // Update openProfileModal to load images
        const originalOpenProfileModal = openProfileModal;
        openProfileModal = function() {
            originalOpenProfileModal();
            loadRestaurantImages();
        };

        // Product image management functions
        let productImageUrl = '';

        function loadProductImage(productId) {
            if (productId) {
                fetch(`../controllers/restaurant_actions.php?action=get_product&product_id=${productId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.product.imagen) {
                            productImageUrl = data.product.imagen;
                            document.getElementById('productImageDisplay').src = data.product.imagen;
                            document.getElementById('productImageDisplay').style.display = 'block';
                            document.getElementById('productImagePlaceholder').style.display = 'none';
                            document.getElementById('removeProductImageBtn').style.display = 'inline-block';
                        } else {
                            resetProductImage();
                        }
                    })
                    .catch(error => console.error('Error loading product image:', error));
            } else {
                resetProductImage();
            }
        }

        function resetProductImage() {
            productImageUrl = '';
            document.getElementById('productImageDisplay').style.display = 'none';
            document.getElementById('productImagePlaceholder').style.display = 'block';
            document.getElementById('removeProductImageBtn').style.display = 'none';
        }

        // Product image upload
        document.getElementById('productImageInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                uploadProductImage(file);
            }
        });

        function uploadProductImage(file) {
            const formData = new FormData();
            formData.append('image', file);
            formData.append('type', 'product');

            fetch('../controllers/restaurant_actions.php?action=upload_product_image', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    productImageUrl = data.image_url;
                    document.getElementById('productImageDisplay').src = data.image_url;
                    document.getElementById('productImageDisplay').style.display = 'block';
                    document.getElementById('productImagePlaceholder').style.display = 'none';
                    document.getElementById('removeProductImageBtn').style.display = 'inline-block';
                    showToast('Imagen del producto subida correctamente', 'success');
                } else {
                    showToast(data.message || 'Error al subir imagen', 'error');
                }
            })
            .catch(error => {
                showToast('Error al subir imagen', 'error');
                console.error('Error:', error);
            });
        }

        function removeProductImage() {
            if (confirm('¿Estás seguro de que deseas eliminar la imagen del producto?')) {
                productImageUrl = '';
                document.getElementById('productImageDisplay').style.display = 'none';
                document.getElementById('productImagePlaceholder').style.display = 'block';
                document.getElementById('removeProductImageBtn').style.display = 'none';
                showToast('Imagen del producto eliminada', 'success');
            }
        }

        // Update saveProduct to include image
        const originalSaveProduct = saveProduct;
        saveProduct = function(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            const data = new URLSearchParams();

            for (let [key, value] of formData.entries()) {
                data.append(key, value);
            }

            // Add product image
            if (productImageUrl) {
                data.append('imagen', productImageUrl);
            }

            const isEdit = data.get('productId') ? true : false;
            const action = isEdit ? 'edit_product' : 'add_product';

            const submitBtn = document.getElementById('modalSubmitBtn');
            const spinner = document.getElementById('modalSpinner');

            submitBtn.disabled = true;
            spinner.style.display = 'inline-block';

            fetch(`../controllers/restaurant_actions.php?action=${action}`, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: data.toString()
            })
            .then(response => response.json())
            .then(result => {
                submitBtn.disabled = false;
                spinner.style.display = 'none';

                if (result.success) {
                    showToast(result.message, 'success');
                    closeModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(result.message, 'error');
                }
            })
            .catch(error => {
                submitBtn.disabled = false;
                spinner.style.display = 'none';
                showToast('Error al procesar la solicitud', 'error');
                console.error('Error:', error);
            });
        };

        // Update openProductModal to load product image
        const originalOpenProductModal = openProductModal;
        openProductModal = function(productId = null) {
            originalOpenProductModal(productId);
            loadProductImage(productId);
        };

        // Product functions
        function saveProduct(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            const data = new URLSearchParams();
            const submitBtn = document.getElementById('modalSubmitBtn');
            const spinner = document.getElementById('modalSpinner');

            for (let [key, value] of formData.entries()) {
                data.append(key, value);
            }

            const isEdit = data.get('productId') ? true : false;
            const action = isEdit ? 'edit_product' : 'add_product';

            submitBtn.disabled = true;
            spinner.style.display = 'inline-block';

            fetch(`../controllers/restaurant_actions.php?action=${action}`, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: data.toString()
            })
            .then(response => response.json())
            .then(result => {
                submitBtn.disabled = false;
                spinner.style.display = 'none';

                if (result.success) {
                    showToast(result.message, 'success');
                    closeModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(result.message, 'error');
                }
            })
            .catch(error => {
                submitBtn.disabled = false;
                spinner.style.display = 'none';
                showToast('Error al procesar la solicitud', 'error');
                console.error('Error:', error);
            });
        }

        function editProduct(id) {
            openProductModal(id);
        }

        function deleteProduct(id) {
            if (confirm('¿Estás seguro de que deseas eliminar este producto? Esta acción no se puede deshacer.')) {
                fetch('../controllers/restaurant_actions.php?action=delete_product', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'product_id=' + encodeURIComponent(id)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    showToast('Error al eliminar producto', 'error');
                    console.error('Error:', error);
                });
            }
        }

        // Order functions
        function updateOrderStatus(orderId, status) {
            const statusText = {
                'PREPARANDO': 'marcar como en preparación',
                'LISTO': 'marcar como listo',
                'EN_CAMINO': 'marcar como en camino'
            };

            if (confirm(`¿Confirmas que deseas ${statusText[status]} este pedido?`)) {
                fetch('../controllers/restaurant_actions.php?action=update_order_status', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'order_id=' + encodeURIComponent(orderId) + '&status=' + encodeURIComponent(status)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    showToast('Error al actualizar estado del pedido', 'error');
                    console.error('Error:', error);
                });
            }
        }

        function cancelOrder(orderId) {
            const reason = prompt('¿Cuál es el motivo de la cancelación?');
            if (reason && reason.trim()) {
                fetch('../controllers/restaurant_actions.php?action=update_order_status', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'order_id=' + encodeURIComponent(orderId) + '&status=CANCELADO'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Pedido cancelado exitosamente', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    showToast('Error al cancelar pedido', 'error');
                    console.error('Error:', error);
                });
            }
        }

        function filterOrders(status) {
            const rows = document.querySelectorAll('#ordersTableBody tr');
            rows.forEach(row => {
                const statusCell = row.querySelector('.status-badge');
                if (status === 'ALL' || statusCell.textContent.trim() === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Profile functions
        function saveProfile(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            const data = new URLSearchParams();
            const submitBtn = document.getElementById('profileSubmitBtn');
            const spinner = document.getElementById('profileSpinner');

            for (let [key, value] of formData.entries()) {
                data.append(key, value);
            }

            // Add coordinates
            data.append('ubicacion_gps', currentCoords);

            submitBtn.disabled = true;
            spinner.style.display = 'inline-block';

            fetch('../controllers/restaurant_actions.php?action=update_restaurant', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: data.toString()
            })
            .then(response => response.json())
            .then(result => {
                submitBtn.disabled = false;
                spinner.style.display = 'none';

                if (result.success) {
                    showToast(result.message, 'success');
                    closeProfileModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(result.message, 'error');
                }
            })
            .catch(error => {
                submitBtn.disabled = false;
                spinner.style.display = 'none';
                showToast('Error al actualizar información', 'error');
                console.error('Error:', error);
            });
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const productModal = document.getElementById('productModal');
            const profileModal = document.getElementById('profileModal');
            if (event.target == productModal) {
                closeModal();
            }
            if (event.target == profileModal) {
                closeProfileModal();
            }
        }

        // Initialize maps when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Show section indicator on large screens
            if (window.innerWidth > 1200) {
                document.getElementById('sectionIndicator').classList.add('show');
            }
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 1200) {
                    document.getElementById('sectionIndicator').classList.add('show');
                } else {
                    document.getElementById('sectionIndicator').classList.remove('show');
                }
            });
            
            // Small delay to ensure DOM is fully ready
            setTimeout(() => {
                // Navigation - just handle active link styling
                document.querySelectorAll('.nav-link').forEach(link => {
                    link.addEventListener('click', function(e) {
                        // Remove active class from all nav links
                        document.querySelectorAll('.nav-link').forEach(nav => {
                            nav.classList.remove('active');
                        });
                        
                        // Remove active class from all indicator items
                        document.querySelectorAll('.indicator-item').forEach(indicator => {
                            indicator.classList.remove('active');
                        });
                        
                        // Add active class to clicked link
                        this.classList.add('active');
                        
                        // Add active class to corresponding indicator
                        const sectionId = this.getAttribute('data-section');
                        const indicator = document.querySelector(`.indicator-item[data-section="${sectionId}"]`);
                        if (indicator) {
                            indicator.classList.add('active');
                        }
                        
                        // Handle map resizing when profile section becomes active
                        const href = this.getAttribute('href');
                        if (href === '#profile') {
                            setTimeout(() => {
                                if (restaurantMap) {
                                    restaurantMap.invalidateSize();
                                } else {
                                    initializeRestaurantMap();
                                }
                            }, 100);
                        }
                    });
                });
                
                // Handle indicator clicks
                document.querySelectorAll('.indicator-item').forEach(indicator => {
                    indicator.addEventListener('click', function() {
                        const sectionId = this.getAttribute('data-section');
                        const link = document.querySelector(`a[data-section="${sectionId}"]`);
                        if (link) {
                            link.click();
                        }
                    });
                });
                
                // Set initial active state based on URL hash or default to dashboard
                const hash = window.location.hash || '#dashboard';
                const activeLink = document.querySelector(`a[href="${hash}"]`);
                if (activeLink) {
                    activeLink.classList.add('active');
                    const sectionId = activeLink.getAttribute('data-section');
                    const indicator = document.querySelector(`.indicator-item[data-section="${sectionId}"]`);
                    if (indicator) {
                        indicator.classList.add('active');
                    }
                }
            }, 100);
        });

        // Restaurant profile map
        let restaurantMap;
        let restaurantMarker;
        let restaurantMapInitialized = false;

        function initializeRestaurantMap() {
            if (restaurantMapInitialized) return; // Prevent multiple initializations

            <?php if (!empty($restaurant_coords)): ?>
                const coords = '<?php echo $restaurant_coords; ?>'.split(',');
                if (coords.length === 2) {
                    const lat = parseFloat(coords[0]);
                    const lng = parseFloat(coords[1]);

                    restaurantMap = L.map('restaurantMap').setView([lat, lng], 15);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(restaurantMap);

                    restaurantMarker = L.marker([lat, lng]).addTo(restaurantMap)
                        .bindPopup('<b><?php echo addslashes($restaurant_name); ?></b><br><?php echo addslashes($restaurant_address); ?>')
                        .openPopup();

                    restaurantMapInitialized = true;

                    // Force map to recalculate its size after initialization
                    setTimeout(() => {
                        if (restaurantMap) {
                            restaurantMap.invalidateSize();
                        }
                    }, 100);

                    // Handle window resize
                    window.addEventListener('resize', () => {
                        if (restaurantMap) {
                            setTimeout(() => {
                                restaurantMap.invalidateSize();
                            }, 100);
                        }
                    });
                }
            <?php endif; ?>
        }

        // Profile modal map
        let profileMap;
        let profileMarker;
        let currentCoords = '<?php echo $restaurant_coords; ?>';

        function openProfileModal() {
            const modal = document.getElementById('profileModal');
            // Load current profile data
            document.getElementById('profileNombre').value = '<?php echo addslashes($restaurant_name); ?>';
            document.getElementById('profileDireccion').value = '<?php echo addslashes($restaurant_address); ?>';
            document.getElementById('profileTelefono').value = '<?php echo addslashes($restaurant_phone); ?>';
            modal.style.display = 'block';

            // Initialize map after modal is shown
            setTimeout(() => {
                initializeProfileMap();
                loadRestaurantImages();
            }, 150); // Increased delay to ensure modal is fully rendered
        }

        function initializeProfileMap() {
            // Clean up existing map if it exists
            if (profileMap) {
                profileMap.remove();
                profileMap = null;
            }

            const coords = currentCoords.split(',');
            let lat = -16.3989; // Default Arequipa coordinates
            let lng = -71.5350;

            if (coords.length === 2) {
                lat = parseFloat(coords[0]);
                lng = parseFloat(coords[1]);
            }

            profileMap = L.map('profileMap').setView([lat, lng], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(profileMap);

            profileMarker = L.marker([lat, lng], {draggable: true}).addTo(profileMap);

            // Update coordinates when marker is dragged
            profileMarker.on('dragend', function(event) {
                const marker = event.target;
                const position = marker.getLatLng();
                currentCoords = position.lat + ',' + position.lng;
            });

            // Update coordinates when map is clicked
            profileMap.on('click', function(event) {
                const latlng = event.latlng;
                profileMarker.setLatLng(latlng);
                currentCoords = latlng.lat + ',' + latlng.lng;
            });

            // Force map to recalculate its size after initialization
            setTimeout(() => {
                if (profileMap) {
                    profileMap.invalidateSize();
                }
            }, 200);

            // Handle window resize for profile map
            const handleResize = () => {
                if (profileMap && document.getElementById('profileModal').style.display === 'block') {
                    setTimeout(() => {
                        profileMap.invalidateSize();
                    }, 100);
                }
            };

            window.addEventListener('resize', handleResize);

            // Store the resize handler to remove it when modal closes
            profileMap._resizeHandler = handleResize;
        }

        // Charts with real data
        const ctxOrders = document.getElementById('ordersChart').getContext('2d');
        new Chart(ctxOrders, {
            type: 'line',
            data: {
                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
                datasets: [{
                    label: 'Pedidos',
                    data: [
                        <?php
                        // Get real orders data for last 6 months
                        $orders_data = [];
                        for ($i = 5; $i >= 0; $i--) {
                            $month = date('m', strtotime("-$i months"));
                            $year = date('Y', strtotime("-$i months"));
                            $query = $conn->prepare("SELECT COUNT(*) as count FROM pedidos WHERE restaurante_id = ? AND MONTH(fecha_pedido) = ? AND YEAR(fecha_pedido) = ?");
                            $query->bind_param('iii', $restaurant_id, $month, $year);
                            $query->execute();
                            $result = $query->get_result();
                            $count = $result->fetch_assoc()['count'] ?? 0;
                            $orders_data[] = $count;
                            $query->close();
                        }
                        echo implode(', ', $orders_data);
                        ?>
                    ],
                    borderColor: '#ff441f',
                    backgroundColor: 'rgba(255, 68, 31, 0.1)',
                }]
            }
        });

        const ctxRevenue = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctxRevenue, {
            type: 'bar',
            data: {
                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
                datasets: [{
                    label: 'Ingresos (S/)',
                    data: [
                        <?php
                        // Get real revenue data for last 6 months
                        $revenue_data = [];
                        for ($i = 5; $i >= 0; $i--) {
                            $month = date('m', strtotime("-$i months"));
                            $year = date('Y', strtotime("-$i months"));
                            $query = $conn->prepare("SELECT SUM(total) as sum FROM pedidos WHERE restaurante_id = ? AND MONTH(fecha_pedido) = ? AND YEAR(fecha_pedido) = ? AND estado != 'CANCELADO'");
                            $query->bind_param('iii', $restaurant_id, $month, $year);
                            $query->execute();
                            $result = $query->get_result();
                            $sum = $result->fetch_assoc()['sum'] ?? 0;
                            $revenue_data[] = $sum;
                            $query->close();
                        }
                        echo implode(', ', $revenue_data);
                        ?>
                    ],
                    backgroundColor: '#ff441f',
                }]
            }
        });
    </script>

    <div class="toast-container" id="toastContainer"></div>
</body>
</html>
