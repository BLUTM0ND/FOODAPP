<?php
// Handle AJAX search requests and prepare categories before any HTML output
include '../includes/config.php';

// Helper: resolve image URLs so relative paths stored in DB work from any view
function resolve_image_url($img) {
    if (empty($img)) return '';
    $img = trim($img);
    // absolute URL or protocol-relative or data URIs
    if (preg_match('#^(https?:|//|data:)#i', $img) || strpos($img, '/') === 0) return $img;
    // build base path like '/FOODAPP/foodapp_php'
    $base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
    return $base . '/' . ltrim($img, '/');
}

// Simple AJAX handler: if ajax=1 in query, return JSON results
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $q = trim($_GET['q'] ?? '');
    $categoria = trim($_GET['categoria'] ?? '');
    $precio_max = $_GET['precio_max'] ?? '';
    $out = ['restaurants'=>[], 'products'=>[]];
    // If any filter or query provided, do filtered search; otherwise return a limited "all" set
    if ($q !== '' || $categoria !== '' || ($precio_max !== '' && is_numeric($precio_max))) {
        $like = '%' . $q . '%';
        // only search restaurants when a text query exists (category filter is product-scoped)
        if ($q !== '') {
            $stmt = $conn->prepare("SELECT id,nombre,direccion,ubicacion_gps FROM restaurantes WHERE nombre LIKE ? LIMIT 100");
            if ($stmt) {
                $stmt->bind_param('s', $like);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($r = $res->fetch_assoc()) $out['restaurants'][] = $r;
                $stmt->close();
            }
        }
        // products
        $sql = "SELECT p.id,p.nombre,p.precio,p.imagen,p.categoria,r.id as restaurante_id, r.nombre as restaurante_nombre FROM productos p LEFT JOIN restaurantes r ON p.restaurante_id = r.id WHERE p.nombre LIKE ?";
        if ($categoria !== '') $sql .= " AND p.categoria = ?";
        if ($precio_max !== '' && is_numeric($precio_max)) $sql .= " AND p.precio <= ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            // bind params dynamically
            if ($categoria !== '' && $precio_max !== '' && is_numeric($precio_max)) {
                $stmt->bind_param('ssd', $like, $categoria, $precio_max);
            } else if ($categoria !== '') {
                $stmt->bind_param('ss', $like, $categoria);
            } else if ($precio_max !== '' && is_numeric($precio_max)) {
                $stmt->bind_param('sd', $like, $precio_max);
            } else {
                $stmt->bind_param('s', $like);
            }
            $stmt->execute();
            $res = $stmt->get_result();
            while ($p = $res->fetch_assoc()) $out['products'][] = $p;
            $stmt->close();
        }
    } else {
        // No filters: return a reasonable amount of restaurants and products so the page can show "everything"
        $rres = $conn->query("SELECT id,nombre,direccion,ubicacion_gps FROM restaurantes ORDER BY nombre LIMIT 100");
        if ($rres) {
            while ($r = $rres->fetch_assoc()) $out['restaurants'][] = $r;
        }
        $pres = $conn->query("SELECT p.id,p.nombre,p.precio,p.imagen,p.categoria,r.id as restaurante_id, r.nombre as restaurante_nombre FROM productos p LEFT JOIN restaurantes r ON p.restaurante_id = r.id ORDER BY p.nombre LIMIT 200");
        if ($pres) {
            while ($p = $pres->fetch_assoc()) $out['products'][] = $p;
        }
    }
    header('Content-Type: application/json');
    echo json_encode($out);
    exit;
}

// For rendering the page: get categories for the select
$cats = [];
$cres = $conn->query("SELECT DISTINCT categoria FROM productos WHERE categoria IS NOT NULL AND categoria <> '' ORDER BY categoria");
if ($cres) {
    while ($c = $cres->fetch_assoc()) $cats[] = $c['categoria'];
}
?>

    <?php include 'header.php'; ?>

    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Nunito', sans-serif;
        }

        .search-hero {
            background: linear-gradient(135deg, #FF7E8B 0%, #FF6B6B 50%, #FFA500 100%);
            padding: 4rem 0;
            margin-top: -1rem;
            margin-bottom: 2rem;
            border-radius: 0 0 50px 50px;
            box-shadow: 0 10px 30px rgba(255, 126, 139, 0.3);
        }

        .search-hero h1 {
            color: white;
            font-weight: 800;
            font-size: 3rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            margin-bottom: 1rem;
        }

        .search-hero p {
            color: rgba(255,255,255,0.9);
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }

        .search-form-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15), 0 0 0 1px rgba(255,255,255,0.2);
            padding: 2.5rem;
            margin-top: -4rem;
            position: relative;
            z-index: 10;
            border: 1px solid rgba(255,255,255,0.3);
            transition: all 0.3s ease;
        }

        .search-form-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 80px rgba(0,0,0,0.2), 0 0 0 1px rgba(255,255,255,0.3);
        }

        .form-floating {
            position: relative;
        }

        .form-floating > label {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            padding: 1rem 1.25rem;
            pointer-events: none;
            border: none;
            transform-origin: 0 0;
            transition: opacity 0.1s ease-in-out, transform 0.1s ease-in-out;
            color: #FF7E8B;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-floating > .form-control {
            height: calc(3.5rem + 2px);
            line-height: 1.25;
            padding: 1rem 1.25rem;
            border: 2px solid rgba(255,126,139,0.2);
            border-radius: 15px;
            background: rgba(255,255,255,0.9);
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .form-floating > .form-control:focus {
            border-color: #FF7E8B;
            box-shadow: 0 0 0 0.3rem rgba(255, 126, 139, 0.15);
            background: white;
            transform: translateY(-2px);
        }

        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            opacity: 0.8;
            transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
        }

        .form-floating > .form-select {
            height: calc(3.5rem + 2px);
            line-height: 1.25;
            padding: 1rem 1.25rem;
            border: 2px solid rgba(255,126,139,0.2);
            border-radius: 15px;
            background: rgba(255,255,255,0.9);
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .form-floating > .form-select:focus {
            border-color: #FF7E8B;
            box-shadow: 0 0 0 0.3rem rgba(255, 126, 139, 0.15);
            background: white;
            transform: translateY(-2px);
        }

        .btn-search-form {
            background: linear-gradient(135deg, #FF7E8B 0%, #FF6B6B 100%);
            border: none;
            border-radius: 15px;
            padding: 1rem 2.5rem;
            font-weight: 700;
            font-size: 1.1rem;
            color: white;
            height: calc(3.5rem + 2px);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(255, 126, 139, 0.3);
        }

        .btn-search-form::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn-search-form:hover::before {
            left: 100%;
        }

        .btn-search-form:hover {
            background: linear-gradient(135deg, #FF5722 0%, #FF7E8B 100%);
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(255, 126, 139, 0.4);
        }

        .search-hint {
            background: linear-gradient(135deg, rgba(255,126,139,0.1) 0%, rgba(255,107,107,0.1) 100%);
            border: 1px solid rgba(255,126,139,0.2);
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-top: 1.5rem;
            text-align: center;
            color: #FF7E8B;
            font-weight: 500;
        }

        .search-hint i {
            color: #FF6B6B;
            margin-right: 0.5rem;
        
            font-weight: 600;
            font-size: 1rem;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 126, 139, 0.3);
        }

        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 126, 139, 0.4);
        }

        .results-section {
            margin-top: 3rem;
        }

        .section-title {
            color: #333;
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 2rem;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(135deg, #FF7E8B 0%, #FF6B6B 100%);
            border-radius: 2px;
        }

        .restaurant-card, .product-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
            border: none;
        }

        .restaurant-card:hover, .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }

        .card-img-top {
            height: 180px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .restaurant-card:hover .card-img-top,
        .product-card:hover .card-img-top {
            transform: scale(1.05);
        }

        .card-body {
            padding: 1.5rem;
        }

        .card-title {
            color: #333;
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }

        .card-text {
            color: #666;
            font-size: 0.95rem;
        }

        .price-badge {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-block;
            margin-top: 0.5rem;
        }

        .category-badge {
            background: linear-gradient(135deg, #FF7E8B 0%, #FF6B6B 100%);
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-left: 0.5rem;
        }

        .loading-spinner {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 200px;
        }

        .spinner-custom {
            width: 3rem;
            height: 3rem;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #FF7E8B;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            color: #666;
        }

        .no-results i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }

        .no-results h4 {
            color: #333;
            margin-bottom: 1rem;
        }

        .filter-summary {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 2rem;
            border-left: 4px solid #667eea;
        }

        .filter-summary strong {
            color: #667eea;
        }

        @media (max-width: 768px) {
            .search-hero h1 {
                font-size: 2rem;
            }

            .search-form-card {
                padding: 2rem 1.5rem;
                margin-top: -3rem;
                border-radius: 20px;
            }

            .form-floating > .form-control,
            .form-floating > .form-select,
            .btn-search-form {
                height: calc(3rem + 2px);
                font-size: 0.95rem;
            }

            .form-floating > label {
                font-size: 0.8rem;
                padding: 0.75rem 1rem;
            }

            .search-hint {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
            }

            .section-title {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 576px) {
            .search-form-card {
                padding: 1.5rem 1rem;
                margin-top: -2rem;
            }

            .form-floating > .form-control,
            .form-floating > .form-select,
            .btn-search-form {
                height: calc(2.75rem + 2px);
                font-size: 0.9rem;
            }

            .form-floating > label {
                font-size: 0.75rem;
                padding: 0.6rem 0.8rem;
            }
        }
    </style>

    <!-- Hero Section -->
    <section class="search-hero">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-8 text-center">
                    <h1>🍽️ Descubre tu Sabor Perfecto</h1>
                    <p>Encuentra los mejores restaurantes y productos cerca de ti</p>
                </div>
            </div>
        </div>
    </section>

    <main class="container">
        <!-- Results Section -->
        <div class="results-section">
            <div id="results" class="row">
                <?php
                // Server-side render when this is not an AJAX call
                if (!isset($_GET['ajax'])) {
                    $q = trim($_GET['q'] ?? '');
                    $categoria = trim($_GET['categoria'] ?? '');
                    $precio_max = $_GET['precio_max'] ?? '';
                    $outHtml = [];

                    // Show filter summary if filters are applied
                    if ($q !== '' || $categoria !== '' || ($precio_max !== '' && is_numeric($precio_max))) {
                        $filters = [];
                        if ($q !== '') $filters[] = "Búsqueda: \"$q\"";
                        if ($categoria !== '') $filters[] = "Categoría: $categoria";
                        if ($precio_max !== '' && is_numeric($precio_max)) $filters[] = "Precio máximo: S/ " . number_format($precio_max, 2);
                        $outHtml[] = '<div class="col-12"><div class="filter-summary"><strong>Filtros aplicados:</strong> ' . implode(' • ', $filters) . '</div></div>';
                    }

                    // Search logic
                    if ($q !== '' || $categoria !== '' || ($precio_max !== '' && is_numeric($precio_max))) {
                        $like = '%' . $q . '%';

                        // Restaurants
                        if ($q !== '') {
                            $stmt = $conn->prepare("SELECT id,nombre,direccion,ubicacion_gps,imagen FROM restaurantes WHERE nombre LIKE ? ORDER BY nombre LIMIT 200");
                            if ($stmt) {
                                $stmt->bind_param('s', $like);
                                $stmt->execute();
                                $res = $stmt->get_result();
                                if ($res && $res->num_rows > 0) {
                                    $outHtml[] = '<div class="col-12"><h2 class="section-title"><i class="fas fa-utensils me-2"></i>Restaurantes</h2></div>';
                                    while ($r = $res->fetch_assoc()) {
                                        $img_src = resolve_image_url($r['imagen']) ?: 'https://via.placeholder.com/600x300?text=' . urlencode($r['nombre']);
                                        $outHtml[] = '<div class="col-12 col-md-6 col-lg-4 mb-4">';
                                        $outHtml[] = '<div class="restaurant-card card h-100">';
                                        $outHtml[] = '<img src="' . htmlspecialchars($img_src) . '" class="card-img-top" alt="' . htmlspecialchars($r['nombre']) . '">';
                                        $outHtml[] = '<div class="card-body d-flex flex-column">';
                                        $outHtml[] = '<h5 class="card-title">' . htmlspecialchars($r['nombre']) . '</h5>';
                                        $outHtml[] = '<p class="card-text flex-grow-1"><i class="fas fa-map-marker-alt me-1"></i>' . htmlspecialchars($r['direccion'] ?? 'Sin dirección') . '</p>';
                                        $outHtml[] = '<a href="menu.php?id=' . intval($r['id']) . '" class="btn btn-search mt-auto"><i class="fas fa-eye me-1"></i>Ver Menú</a>';
                                        $outHtml[] = '</div></div></div>';
                                    }
                                }
                                $stmt->close();
                            }
                        }

                        // Products
                        $sql = "SELECT p.id,p.nombre,p.precio,p.imagen,p.categoria,r.id as restaurante_id, r.nombre as restaurante_nombre FROM productos p LEFT JOIN restaurantes r ON p.restaurante_id = r.id WHERE p.nombre LIKE ?";
                        if ($categoria !== '') $sql .= " AND p.categoria = ?";
                        if ($precio_max !== '' && is_numeric($precio_max)) $sql .= " AND p.precio <= ?";
                        $stmt = $conn->prepare($sql);
                        if ($stmt) {
                            if ($categoria !== '' && $precio_max !== '' && is_numeric($precio_max)) {
                                $stmt->bind_param('ssd', $like, $categoria, $precio_max);
                            } else if ($categoria !== '') {
                                $stmt->bind_param('ss', $like, $categoria);
                            } else if ($precio_max !== '' && is_numeric($precio_max)) {
                                $stmt->bind_param('sd', $like, $precio_max);
                            } else {
                                $stmt->bind_param('s', $like);
                            }
                            $stmt->execute();
                            $res = $stmt->get_result();
                            if ($res->num_rows > 0) {
                                $outHtml[] = '<div class="col-12 mt-4"><h2 class="section-title"><i class="fas fa-shopping-cart me-2"></i>Productos</h2></div>';
                                while ($p = $res->fetch_assoc()) {
                                    $outHtml[] = '<div class="col-12 col-md-6 col-lg-4 mb-4">';
                                    $outHtml[] = '<div class="product-card card h-100">';
                                    if (!empty($p['imagen'])) {
                                        $outHtml[] = '<img src="' . htmlspecialchars(resolve_image_url($p['imagen'])) . '" class="card-img-top" alt="' . htmlspecialchars($p['nombre']) . '">';
                                    } else {
                                        $outHtml[] = '<div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 180px;"><i class="fas fa-image fa-3x text-muted"></i></div>';
                                    }
                                    $outHtml[] = '<div class="card-body d-flex flex-column">';
                                    $outHtml[] = '<h5 class="card-title">' . htmlspecialchars($p['nombre']) . '</h5>';
                                    $outHtml[] = '<p class="card-text flex-grow-1"><i class="fas fa-store me-1"></i>' . htmlspecialchars($p['restaurante_nombre'] ?? 'Sin restaurante') . '</p>';
                                    $outHtml[] = '<div class="d-flex justify-content-between align-items-center">';
                                    $outHtml[] = '<span class="price-badge">S/ ' . number_format($p['precio'], 2) . '</span>';
                                    if (!empty($p['categoria'])) {
                                        $outHtml[] = '<span class="category-badge">' . htmlspecialchars($p['categoria']) . '</span>';
                                    }
                                    $outHtml[] = '</div>';
                                    $outHtml[] = '<a href="menu.php?id=' . intval($p['restaurante_id']) . '" class="btn btn-outline-primary mt-3"><i class="fas fa-eye me-1"></i>Ver en Menú</a>';
                                    $outHtml[] = '</div></div></div>';
                                }
                            }
                            $stmt->close();
                        }
                    } else {
                        // No filters: show featured content
                        $outHtml[] = '<div class="col-12 text-center mb-4"><div class="alert alert-info" style="border-radius: 15px; border: none; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;"><i class="fas fa-star me-2"></i><strong>¡Descubre todo lo que tenemos para ti!</strong> Explora nuestros restaurantes y productos destacados.</div></div>';

                        // Featured restaurants
                        $rres = $conn->query("SELECT id,nombre,direccion,ubicacion_gps,imagen FROM restaurantes ORDER BY nombre LIMIT 200");
                        if ($rres && $rres->num_rows > 0) {
                            $outHtml[] = '<div class="col-12"><h2 class="section-title"><i class="fas fa-utensils me-2"></i>Restaurantes Disponibles</h2></div>';
                            while ($r = $rres->fetch_assoc()) {
                                $img_src = resolve_image_url($r['imagen']) ?: 'https://via.placeholder.com/600x300?text=' . urlencode($r['nombre']);
                                $outHtml[] = '<div class="col-12 col-md-6 col-lg-4 mb-4">';
                                $outHtml[] = '<div class="restaurant-card card h-100">';
                                $outHtml[] = '<img src="' . htmlspecialchars($img_src) . '" class="card-img-top" alt="' . htmlspecialchars($r['nombre']) . '">';
                                $outHtml[] = '<div class="card-body d-flex flex-column">';
                                $outHtml[] = '<h5 class="card-title">' . htmlspecialchars($r['nombre']) . '</h5>';
                                $outHtml[] = '<p class="card-text flex-grow-1"><i class="fas fa-map-marker-alt me-1"></i>' . htmlspecialchars($r['direccion'] ?? 'Sin dirección') . '</p>';
                                $outHtml[] = '<a href="menu.php?id=' . intval($r['id']) . '" class="btn btn-search mt-auto"><i class="fas fa-eye me-1"></i>Ver Menú</a>';
                                $outHtml[] = '</div></div></div>';
                            }
                        }

                        // Featured products
                        $pres = $conn->query("SELECT p.id,p.nombre,p.precio,p.imagen,p.categoria,r.id as restaurante_id, r.nombre as restaurante_nombre FROM productos p LEFT JOIN restaurantes r ON p.restaurante_id = r.id ORDER BY p.nombre LIMIT 400");
                        if ($pres && $pres->num_rows > 0) {
                            $outHtml[] = '<div class="col-12 mt-4"><h2 class="section-title"><i class="fas fa-shopping-cart me-2"></i>Productos Destacados</h2></div>';
                            while ($p = $pres->fetch_assoc()) {
                                $outHtml[] = '<div class="col-12 col-md-6 col-lg-4 mb-4">';
                                $outHtml[] = '<div class="product-card card h-100">';
                                if (!empty($p['imagen'])) {
                                    $outHtml[] = '<img src="' . htmlspecialchars(resolve_image_url($p['imagen'])) . '" class="card-img-top" alt="' . htmlspecialchars($p['nombre']) . '">';
                                } else {
                                    $outHtml[] = '<div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 180px;"><i class="fas fa-image fa-3x text-muted"></i></div>';
                                }
                                $outHtml[] = '<div class="card-body d-flex flex-column">';
                                $outHtml[] = '<h5 class="card-title">' . htmlspecialchars($p['nombre']) . '</h5>';
                                $outHtml[] = '<p class="card-text flex-grow-1"><i class="fas fa-store me-1"></i>' . htmlspecialchars($p['restaurante_nombre'] ?? 'Sin restaurante') . '</p>';
                                $outHtml[] = '<div class="d-flex justify-content-between align-items-center">';
                                $outHtml[] = '<span class="price-badge">S/ ' . number_format($p['precio'], 2) . '</span>';
                                if (!empty($p['categoria'])) {
                                    $outHtml[] = '<span class="category-badge">' . htmlspecialchars($p['categoria']) . '</span>';
                                }
                                $outHtml[] = '</div>';
                                $outHtml[] = '<a href="menu.php?id=' . intval($p['restaurante_id']) . '" class="btn btn-outline-primary mt-3"><i class="fas fa-eye me-1"></i>Ver en Menú</a>';
                                $outHtml[] = '</div></div></div>';
                            }
                        }
                    }

                    if (empty($outHtml)) {
                        echo '<div class="col-12"><div class="no-results"><i class="fas fa-search"></i><h4>No se encontraron resultados</h4><p>Intenta con otros términos de búsqueda o ajusta tus filtros.</p></div></div>';
                    } else {
                        echo implode("\n", $outHtml);
                    }
                }
                ?>
            </div>
        </div>

        <script>
        // Enhanced live search with better UX
        const qInput = document.getElementById('q');
        let debounce = null;

        qInput && qInput.addEventListener('input', (e) => {
            clearTimeout(debounce);
            debounce = setTimeout(() => doSearch(), 400);
        });

        // Also trigger search on filter changes
        document.getElementById('categoria').addEventListener('change', () => doSearch());
        document.getElementById('precio_max').addEventListener('input', () => doSearch());

        async function doSearch() {
            const q = document.getElementById('q').value.trim();
            const categoria = document.getElementById('categoria').value;
            const precio_max = document.getElementById('precio_max').value;
            const resultsEl = document.getElementById('results');

            // Show loading state
            resultsEl.innerHTML = `
                <div class="col-12">
                    <div class="loading-spinner">
                        <div class="spinner-custom"></div>
                        <div class="ms-3">
                            <h5 class="text-muted mb-1">Buscando...</h5>
                            <small class="text-muted">Encontrando los mejores resultados para ti</small>
                        </div>
                    </div>
                </div>
            `;

            try {
                const params = new URLSearchParams({ajax: '1', q, categoria, precio_max});
                const resp = await fetch(window.location.pathname + '?' + params.toString());
                const text = await resp.text();

                try {
                    const data = JSON.parse(text);
                    renderResults(data, q, categoria, precio_max);
                } catch (parseErr) {
                    console.error('Failed to parse JSON, server returned:', text);
                    resultsEl.innerHTML = '<div class="col-12"><div class="alert alert-danger" style="border-radius: 15px;"><i class="fas fa-exclamation-triangle me-2"></i><strong>Error al buscar</strong><br><small>Por favor intenta nuevamente.</small></div></div>';
                }
            } catch (err) {
                console.error('Fetch error:', err);
                resultsEl.innerHTML = '<div class="col-12"><div class="alert alert-danger" style="border-radius: 15px;"><i class="fas fa-wifi-slash me-2"></i><strong>Error de conexión</strong><br><small>Verifica tu conexión a internet e intenta nuevamente.</small></div></div>';
            }
        }

        function renderResults(data, q, categoria, precio_max) {
            const resultsEl = document.getElementById('results');
            const parts = [];

            // Filter summary
            const filters = [];
            if (q) filters.push(`Búsqueda: "${q}"`);
            if (categoria) filters.push(`Categoría: ${categoria}`);
            if (precio_max) filters.push(`Precio máximo: S/ ${parseFloat(precio_max).toFixed(2)}`);

            if (filters.length > 0) {
                parts.push(`<div class="col-12"><div class="filter-summary"><i class="fas fa-filter me-2"></i><strong>Filtros aplicados:</strong> ${filters.join(' • ')}</div></div>`);
            }

            // Restaurants
            if (data.restaurants && data.restaurants.length > 0) {
                parts.push('<div class="col-12"><h2 class="section-title"><i class="fas fa-utensils me-2"></i>Restaurantes</h2></div>');
                data.restaurants.forEach(r => {
                    parts.push(`<div class="col-12 col-md-6 col-lg-4 mb-4">`);
                    parts.push(`<div class="restaurant-card card h-100">`);
                    parts.push(`<img src="https://via.placeholder.com/600x300?text=${encodeURIComponent(r.nombre)}" class="card-img-top" alt="${escapeHtml(r.nombre)}">`);
                    parts.push(`<div class="card-body d-flex flex-column">`);
                    parts.push(`<h5 class="card-title">${escapeHtml(r.nombre)}</h5>`);
                    parts.push(`<p class="card-text flex-grow-1"><i class="fas fa-map-marker-alt me-1"></i>${escapeHtml(r.direccion||'Sin dirección')}</p>`);
                    parts.push(`<a href="menu.php?id=${r.id}" class="btn btn-search mt-auto"><i class="fas fa-eye me-1"></i>Ver Menú</a>`);
                    parts.push(`</div></div></div>`);
                });
            }

            // Products
            if (data.products && data.products.length > 0) {
                parts.push('<div class="col-12 mt-4"><h2 class="section-title"><i class="fas fa-shopping-cart me-2"></i>Productos</h2></div>');
                data.products.forEach(p => {
                    parts.push(`<div class="col-12 col-md-6 col-lg-4 mb-4">`);
                    parts.push(`<div class="product-card card h-100">`);
                    if (p.imagen) {
                        let imgSrc = p.imagen;
                        if (!/^https?:|^\/\//i.test(imgSrc) && imgSrc.indexOf('data:') !== 0 && imgSrc.indexOf('/') !== 0) {
                            const basePath = '<?php echo rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), "/"); ?>';
                            imgSrc = basePath + '/' + imgSrc.replace(/^\/+/, '');
                        }
                        parts.push(`<img src="${escapeHtml(imgSrc)}" class="card-img-top" alt="${escapeHtml(p.nombre)}">`);
                    } else {
                        parts.push(`<div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 180px;"><i class="fas fa-image fa-3x text-muted"></i></div>`);
                    }
                    parts.push(`<div class="card-body d-flex flex-column">`);
                    parts.push(`<h5 class="card-title">${escapeHtml(p.nombre)}</h5>`);
                    parts.push(`<p class="card-text flex-grow-1"><i class="fas fa-store me-1"></i>${escapeHtml(p.restaurante_nombre||'Sin restaurante')}</p>`);
                    parts.push(`<div class="d-flex justify-content-between align-items-center">`);
                    parts.push(`<span class="price-badge">S/ ${Number(p.precio).toFixed(2)}</span>`);
                    if (p.categoria) {
                        parts.push(`<span class="category-badge">${escapeHtml(p.categoria)}</span>`);
                    }
                    parts.push(`</div>`);
                    parts.push(`<a href="menu.php?id=${p.restaurante_id}" class="btn btn-outline-primary mt-3"><i class="fas fa-eye me-1"></i>Ver en Menú</a>`);
                    parts.push(`</div></div></div>`);
                });
            }

            // No results
            if ((!data.restaurants || data.restaurants.length === 0) && (!data.products || data.products.length === 0)) {
                parts.push('<div class="col-12"><div class="no-results"><i class="fas fa-search"></i><h4>No se encontraron resultados</h4><p>Intenta con otros términos de búsqueda o ajusta tus filtros.</p></div></div>');
            }

            resultsEl.innerHTML = parts.join('');
        }

        function escapeHtml(s) {
            if (!s) return '';
            return String(s).replace(/[&<>\"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":"&#39;"})[c]);
        }

        // Initial search on load
        (function(){ doSearch(); })();
        </script>

    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
