<?php
session_start();
include_once '../includes/config.php';

if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$order_id = intval($_GET['id']);

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = intval($_SESSION['user_id']);
$is_admin = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin');

// Fetch order ensuring owner or admin
$sql = $conn->prepare("SELECT id, fecha_pedido, estado, total, metodo_pago, propina, descuento, direccion_entrega, delivery_lat, delivery_lng, cliente_id, restaurante_id FROM pedidos WHERE id = ? LIMIT 1");
$sql->bind_param('i', $order_id);
$sql->execute();
$res = $sql->get_result();
if ($res->num_rows === 0) {
    echo "<p>Pedido no encontrado.</p>";
    exit;
}
$order = $res->fetch_assoc();
if (!$is_admin && $order['cliente_id'] != $user_id) {
    echo "<p>No tienes permiso para ver este pedido.</p>";
    exit;
}

// Determine whether order has coordinates (helpful for repartidor)
$has_coords = (!empty($order['delivery_lat']) && !empty($order['delivery_lng']));

// Fetch order items with product names
$stmt = $conn->prepare("SELECT dp.producto_id, dp.cantidad, dp.precio, p.nombre, p.imagen FROM detalle_pedido dp LEFT JOIN productos p ON dp.producto_id = p.id WHERE dp.pedido_id = ?");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$items_res = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Detalle Pedido #<?php echo $order['id']; ?> - FoodApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <main class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Pedido #<?php echo $order['id']; ?></h2>
            <div>
                <span class="badge bg-info text-dark"><?php echo htmlspecialchars($order['estado']); ?></span>
            </div>
        </div>

        <?php
        // Detect inconsistent state: pickup (no direccion) but status indicates it's en camino.
        // 'ENTREGADO' is allowed for both pickup and delivery (order finished).
        $is_pickup = empty($order['direccion_entrega']);
        $estado_upper = strtoupper($order['estado'] ?? '');
        if ($is_pickup && $estado_upper === 'EN_CAMINO'):
        ?>
            <div class="alert alert-warning">Este pedido está marcado como "Recoger en local" pero su estado es "<?php echo htmlspecialchars($order['estado']); ?>" — esto parece inconsistente (un pedido de recogida no debería estar "EN_CAMINO").</div>
            <?php if ($is_admin): ?>
                <a href="../controllers/admin_actions.php?action=change_status&id=<?php echo $order['id']; ?>&status=PREPARANDO" class="btn btn-sm btn-primary">Corregir a PREPARANDO</a>
            <?php endif; ?>
        <?php endif; ?>

        <div class="card mb-3">
            <div class="card-body">
                <p><strong>Fecha:</strong> <?php echo $order['fecha_pedido']; ?></p>
                <p><strong>Método de pago:</strong> <?php echo htmlspecialchars($order['metodo_pago']); ?></p>
                <p><strong>Dirección:</strong>
                    <?php
                        if (!empty($order['direccion_entrega'])) {
                            echo htmlspecialchars($order['direccion_entrega']);
                        } else {
                            // If it's not pickup but there's no textual address, inform about default plaza
                            if (!$is_pickup) {
                                echo 'Plaza de Armas, Arequipa (ubicación por defecto)';
                            } else {
                                echo 'Recoger en local';
                            }
                        }
                    ?>
                </p>
                <?php if (!$is_pickup): ?>
                    <p><strong>Coordenadas:</strong>
                        <?php
                            if ($has_coords) {
                                echo htmlspecialchars($order['delivery_lat']) . ', ' . htmlspecialchars($order['delivery_lng']);
                            } else {
                                echo '-16.3989, -71.5350 (Plaza de Armas — por defecto)';
                            }
                        ?>
                    </p>
                <?php endif; ?>
                <p><strong>Propina:</strong> S/ <?php echo number_format($order['propina'],2); ?></p>
                <p><strong>Descuento:</strong> S/ <?php echo number_format($order['descuento'],2); ?></p>
                <p class="h5">Total: S/ <?php echo number_format($order['total'],2); ?></p>
            </div>
        </div>

        <h4>Artículos</h4>
        <div class="list-group mb-4">
            <?php while ($it = $items_res->fetch_assoc()):
                $subtotal = $it['precio'] * $it['cantidad'];
            ?>
                <div class="list-group-item d-flex align-items-center">
                    <img src="<?php echo $it['imagen'] ? $it['imagen'] : 'https://via.placeholder.com/80x80?text=Img'; ?>" alt="" style="width:80px;height:80px;object-fit:cover;border-radius:6px;margin-right:12px;">
                    <div class="flex-grow-1">
                        <h6 class="mb-1"><?php echo htmlspecialchars($it['nombre'] ?? 'Producto'); ?></h6>
                        <small class="text-muted">Cantidad: <?php echo intval($it['cantidad']); ?> × S/ <?php echo number_format($it['precio'],2); ?></small>
                    </div>
                    <div class="text-end">
                        <strong>S/ <?php echo number_format($subtotal,2); ?></strong>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <a href="orders.php" class="btn btn-secondary">Volver a Mis Pedidos</a>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
