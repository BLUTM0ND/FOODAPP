<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'cliente') {
    header('Location: login.php');
    exit;
}
include_once '../includes/config.php';

$pedido_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$cliente_id = $_SESSION['user_id'];

// Verificar si ya existe una valoración para este pedido
$existing_rating = null;
if ($pedido_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM valoraciones WHERE pedido_id = ? AND cliente_id = ? LIMIT 1");
    $stmt->bind_param('ii', $pedido_id, $cliente_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $existing_rating = $result->fetch_assoc();
    }
    $stmt->close();
}

// Obtener información del pedido para mostrar contexto
$pedido_info = null;
if ($pedido_id > 0) {
    $stmt = $conn->prepare("
        SELECT p.*, r.nombre as restaurante_nombre, u.nombre as repartidor_nombre
        FROM pedidos p
        LEFT JOIN restaurantes r ON p.restaurante_id = r.id
        LEFT JOIN usuarios u ON p.repartidor_id = u.id
        WHERE p.id = ? AND p.cliente_id = ?
        LIMIT 1
    ");
    $stmt->bind_param('ii', $pedido_id, $cliente_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $pedido_info = $result->fetch_assoc();
    }
    $stmt->close();
}

$page_title = 'Valorar Pedido - FoodApp';

// Manejar mensajes de error/éxito
$message = '';
$message_type = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'already_rated':
            $message = 'Ya has valorado este pedido anteriormente.';
            $message_type = 'warning';
            break;
        default:
            $message = 'Ha ocurrido un error.';
            $message_type = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .rating-stars {
            font-size: 2rem;
            color: #ddd;
            cursor: pointer;
        }
        .rating-stars.active {
            color: #ffc107;
        }
        .rating-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .rating-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .rating-header i {
            font-size: 3rem;
            color: #ff441f;
            margin-bottom: 1rem;
        }
        .star-rating {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin: 1rem 0;
        }
        .star-rating i {
            font-size: 2.5rem;
            color: #ddd;
            transition: color 0.2s;
        }
        .star-rating i.active {
            color: #ffc107;
        }
        .rating-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .rating-description {
            text-align: center;
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        .submit-btn {
            background: linear-gradient(135deg, #ff441f, #ff7d00);
            border: none;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 10px;
            color: white;
            transition: transform 0.2s;
        }
        .submit-btn:hover {
            transform: translateY(-2px);
            color: white;
        }
        .existing-rating {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        .rating-display {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin: 0.5rem 0;
            border: 2px solid #e9ecef;
        }
        .rating-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            margin: 0.25rem;
        }
        .rating-badge.restaurant {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
        }
        .rating-badge.delivery {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: white;
        }
        .rating-badge.order {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: black;
        }
        .already-rated {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
            border: none;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <img src="../assets/FOODAPP.png" alt="FoodApp Logo" height="40" class="me-2">
                <span class="fw-bold">FoodApp</span>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../index.php">
                    <i class="fas fa-home me-1"></i>Inicio
                </a>
                <a class="nav-link" href="search.php">
                    <i class="fas fa-search me-1"></i>Buscar
                </a>
            </div>
        </div>
    </nav>

    <?php if (!empty($message)): ?>
    <div class="container mt-3">
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : ($message_type == 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); ?> me-2"></i>
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="rating-section">
                    <?php if ($existing_rating): ?>
                        <!-- Mostrar valoraciones existentes -->
                        <div class="rating-header existing-rating">
                            <i class="fas fa-check-circle"></i>
                            <h2 class="h3 mb-1">Valoración Enviada</h2>
                            <p class="text-white-50 mb-0">Gracias por tu feedback</p>
                        </div>

                        <?php if ($pedido_info): ?>
                        <div class="mb-4">
                            <h5 class="text-center mb-3">
                                <i class="fas fa-receipt me-2"></i>
                                Pedido #<?php echo $pedido_info['id']; ?>
                            </h5>
                            <div class="row text-center">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Restaurante:</strong></p>
                                    <p class="text-muted"><?php echo htmlspecialchars($pedido_info['restaurante_nombre'] ?? 'N/A'); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Repartidor:</strong></p>
                                    <p class="text-muted"><?php echo htmlspecialchars($pedido_info['repartidor_nombre'] ?? 'Pendiente'); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="rating-display">
                            <h6 class="mb-3 text-center">
                                <i class="fas fa-star me-2"></i>
                                Tus Calificaciones
                            </h6>
                            <div class="text-center mb-3">
                                <span class="rating-badge restaurant">
                                    <i class="fas fa-utensils me-1"></i>
                                    Restaurante: <?php echo $existing_rating['calificacion_restaurante']; ?>/5
                                </span>
                                <span class="rating-badge delivery">
                                    <i class="fas fa-truck me-1"></i>
                                    Repartidor: <?php echo $existing_rating['calificacion_repartidor']; ?>/5
                                </span>
                                <span class="rating-badge order">
                                    <i class="fas fa-box me-1"></i>
                                    Pedido: <?php echo $existing_rating['calificacion_pedido']; ?>/5
                                </span>
                            </div>
                            <?php if (!empty($existing_rating['comentario'])): ?>
                            <div class="mt-3">
                                <strong><i class="fas fa-comment me-1"></i>Comentario:</strong>
                                <p class="mt-2 text-muted fst-italic">"<?php echo htmlspecialchars($existing_rating['comentario']); ?>"</p>
                            </div>
                            <?php endif; ?>
                            <div class="text-center mt-3 text-muted small">
                                <i class="fas fa-calendar me-1"></i>
                                Valorado el <?php echo date('d/m/Y H:i', strtotime($existing_rating['fecha_valoracion'])); ?>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <a href="profile.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left me-2"></i>Volver a Mi Perfil
                            </a>
                        </div>

                    <?php else: ?>
                        <!-- Mostrar formulario de valoración -->
                        <div class="rating-header">
                            <i class="fas fa-star-half-alt"></i>
                            <h2 class="h3 mb-1">Valora tu Experiencia</h2>
                            <p class="text-muted">Tu opinión nos ayuda a mejorar</p>
                        </div>

                        <?php if ($pedido_info): ?>
                        <div class="mb-4">
                            <h5 class="text-center mb-3">
                                <i class="fas fa-receipt me-2"></i>
                                Pedido #<?php echo $pedido_info['id']; ?>
                            </h5>
                            <div class="row text-center">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Restaurante:</strong></p>
                                    <p class="text-muted"><?php echo htmlspecialchars($pedido_info['restaurante_nombre'] ?? 'N/A'); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Repartidor:</strong></p>
                                    <p class="text-muted"><?php echo htmlspecialchars($pedido_info['repartidor_nombre'] ?? 'Pendiente'); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <form action="../controllers/submit_rating.php" method="post" id="ratingForm">
                        <input type="hidden" name="pedido_id" value="<?php echo htmlspecialchars($_GET['id'] ?? ''); ?>">

                        <!-- Calificación Restaurante -->
                        <div class="mb-4">
                            <label class="rating-label">
                                <i class="fas fa-utensils me-2 text-success"></i>
                                Calificación del Restaurante
                            </label>
                            <div class="star-rating" id="restaurantStars">
                                <i class="fas fa-star" data-rating="1"></i>
                                <i class="fas fa-star" data-rating="2"></i>
                                <i class="fas fa-star" data-rating="3"></i>
                                <i class="fas fa-star" data-rating="4"></i>
                                <i class="fas fa-star" data-rating="5"></i>
                            </div>
                            <input type="hidden" name="cal_rest" id="cal_rest" required>
                            <p class="rating-description" id="restaurantDesc">Selecciona una calificación</p>
                        </div>

                        <!-- Calificación Repartidor -->
                        <div class="mb-4">
                            <label class="rating-label">
                                <i class="fas fa-truck me-2 text-primary"></i>
                                Calificación del Repartidor
                            </label>
                            <div class="star-rating" id="deliveryStars">
                                <i class="fas fa-star" data-rating="1"></i>
                                <i class="fas fa-star" data-rating="2"></i>
                                <i class="fas fa-star" data-rating="3"></i>
                                <i class="fas fa-star" data-rating="4"></i>
                                <i class="fas fa-star" data-rating="5"></i>
                            </div>
                            <input type="hidden" name="cal_repartidor" id="cal_repartidor" required>
                            <p class="rating-description" id="deliveryDesc">Selecciona una calificación</p>
                        </div>

                        <!-- Calificación Pedido -->
                        <div class="mb-4">
                            <label class="rating-label">
                                <i class="fas fa-box me-2 text-warning"></i>
                                Calificación del Pedido
                            </label>
                            <div class="star-rating" id="orderStars">
                                <i class="fas fa-star" data-rating="1"></i>
                                <i class="fas fa-star" data-rating="2"></i>
                                <i class="fas fa-star" data-rating="3"></i>
                                <i class="fas fa-star" data-rating="4"></i>
                                <i class="fas fa-star" data-rating="5"></i>
                            </div>
                            <input type="hidden" name="cal_ped" id="cal_ped" required>
                            <p class="rating-description" id="orderDesc">Selecciona una calificación</p>
                        </div>

                        <!-- Comentario -->
                        <div class="mb-4">
                            <label for="comentario" class="rating-label">
                                <i class="fas fa-comment me-2 text-info"></i>
                                Comentarios (Opcional)
                            </label>
                            <textarea
                                class="form-control"
                                id="comentario"
                                name="comentario"
                                rows="4"
                                placeholder="Comparte tu experiencia... ¿Qué te gustó? ¿Qué podríamos mejorar?"
                                maxlength="500"
                            ></textarea>
                            <div class="form-text text-end">
                                <span id="charCount">0</span>/500 caracteres
                            </div>
                        </div>

                        <!-- Botón Enviar -->
                        <div class="text-center">
                            <button type="submit" class="btn submit-btn">
                                <i class="fas fa-paper-plane me-2"></i>
                                Enviar Valoración
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (!$existing_rating): ?>
    <script>
        // Función para manejar calificaciones con estrellas
        function setupStarRating(starsContainer, hiddenInput, descriptionElement, descriptions) {
            const stars = starsContainer.querySelectorAll('i');
            let currentRating = 0;

            stars.forEach(star => {
                star.addEventListener('click', function() {
                    const rating = parseInt(this.getAttribute('data-rating'));
                    currentRating = rating;
                    hiddenInput.value = rating;

                    // Actualizar estrellas visuales
                    stars.forEach((s, index) => {
                        if (index < rating) {
                            s.classList.add('active');
                        } else {
                            s.classList.remove('active');
                        }
                    });

                    // Actualizar descripción
                    descriptionElement.textContent = descriptions[rating - 1];
                    descriptionElement.style.color = getRatingColor(rating);
                });

                star.addEventListener('mouseover', function() {
                    const rating = parseInt(this.getAttribute('data-rating'));
                    stars.forEach((s, index) => {
                        if (index < rating) {
                            s.classList.add('active');
                        } else {
                            s.classList.remove('active');
                        }
                    });
                });

                star.addEventListener('mouseout', function() {
                    stars.forEach((s, index) => {
                        if (index < currentRating) {
                            s.classList.add('active');
                        } else {
                            s.classList.remove('active');
                        }
                    });
                });
            });
        }

        function getRatingColor(rating) {
            if (rating >= 4) return '#28a745';
            if (rating >= 3) return '#ffc107';
            return '#dc3545';
        }

        // Configurar calificaciones
        const restaurantDescriptions = [
            'Muy malo', 'Malo', 'Regular', 'Bueno', 'Excelente'
        ];
        const deliveryDescriptions = [
            'Muy lento/poco amable', 'Lento/poco amable', 'Normal', 'Rápido/amable', 'Excelente servicio'
        ];
        const orderDescriptions = [
            'Muy malo', 'Malo', 'Regular', 'Bueno', 'Excelente'
        ];

        setupStarRating(
            document.getElementById('restaurantStars'),
            document.getElementById('cal_rest'),
            document.getElementById('restaurantDesc'),
            restaurantDescriptions
        );

        setupStarRating(
            document.getElementById('deliveryStars'),
            document.getElementById('cal_repartidor'),
            document.getElementById('deliveryDesc'),
            deliveryDescriptions
        );

        setupStarRating(
            document.getElementById('orderStars'),
            document.getElementById('cal_ped'),
            document.getElementById('orderDesc'),
            orderDescriptions
        );

        // Contador de caracteres para el comentario
        const comentarioTextarea = document.getElementById('comentario');
        const charCount = document.getElementById('charCount');

        comentarioTextarea.addEventListener('input', function() {
            const count = this.value.length;
            charCount.textContent = count;
            charCount.style.color = count > 450 ? '#dc3545' : count > 400 ? '#ffc107' : '#6c757d';
        });

        // Validación del formulario
        document.getElementById('ratingForm').addEventListener('submit', function(e) {
            const calRest = document.getElementById('cal_rest').value;
            const calRepartidor = document.getElementById('cal_repartidor').value;
            const calPed = document.getElementById('cal_ped').value;

            if (!calRest || !calRepartidor || !calPed) {
                e.preventDefault();
                alert('Por favor, califica todos los aspectos antes de enviar.');
                return false;
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
