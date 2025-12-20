<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header('Location: ../views/login.php');
    exit;
}
include_once '../includes/config.php';
$repartidores = $conn->query("SELECT u.id, u.nombre, u.email, r.vehiculo, r.licencia, r.zona, r.disponible FROM usuarios u JOIN repartidores r ON u.id = r.id ORDER BY u.id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin - Repartidores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <main class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Repartidores</h2>
            <a href="admin.php" class="btn btn-secondary">Volver al Dashboard</a>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="card p-3 mb-3">
                    <h5>Agregar Repartidor</h5>
                    <form action="../controllers/admin_actions.php?action=add_repartidor" method="post">
                        <div class="mb-2">
                            <label class="form-label">Nombre</label>
                            <input class="form-control" name="nombre" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Email</label>
                            <input class="form-control" type="email" name="email" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Teléfono</label>
                            <input class="form-control" name="telefono">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Vehículo</label>
                            <input class="form-control" name="vehiculo">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Licencia</label>
                            <input class="form-control" name="licencia">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Zona</label>
                            <input class="form-control" name="zona">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Contraseña temporal</label>
                            <input class="form-control" type="password" name="password" required>
                        </div>
                        <button class="btn btn-primary">Crear Repartidor</button>
                    </form>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card p-3 mb-3">
                    <h5>Lista de Repartidores</h5>
                    <table class="table">
                        <thead>
                            <tr><th>ID</th><th>Nombre</th><th>Email</th><th>Vehículo</th><th>Zona</th><th>Disponible</th></tr>
                        </thead>
                        <tbody>
                            <?php if ($repartidores && $repartidores->num_rows>0) {
                                while($r = $repartidores->fetch_assoc()) {
                                    echo '<tr>';
                                    echo '<td>'.intval($r['id']).'</td>';
                                    echo '<td>'.htmlspecialchars($r['nombre']).'</td>';
                                    echo '<td>'.htmlspecialchars($r['email']).'</td>';
                                    echo '<td>'.htmlspecialchars($r['vehiculo']).'</td>';
                                    echo '<td>'.htmlspecialchars($r['zona']).'</td>';
                                    echo '<td>'.($r['disponible'] ? 'Sí' : 'No').'</td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="6">No hay repartidores.</td></tr>';
                            } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
