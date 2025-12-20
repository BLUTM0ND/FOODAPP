<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../includes/config.php';

$term = isset($_GET['term']) ? trim($_GET['term']) : '';

if (empty($term) || strlen($term) < 2) {
    echo json_encode(['restaurantes' => [], 'productos' => []]);
    exit;
}

try {
    // Usar la conexión mysqli existente
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Buscar restaurantes
    $stmtRestaurantes = $conn->prepare("
        SELECT id, nombre, direccion, categoria
        FROM restaurantes
        WHERE estado = 'ABIERTO'
        AND (nombre LIKE ? OR categoria LIKE ? OR direccion LIKE ?)
        ORDER BY nombre
        LIMIT 5
    ");
    $searchTerm = '%' . $term . '%';
    $stmtRestaurantes->bind_param('sss', $searchTerm, $searchTerm, $searchTerm);
    $stmtRestaurantes->execute();
    $resultRestaurantes = $stmtRestaurantes->get_result();
    $restaurantes = $resultRestaurantes->fetch_all(MYSQLI_ASSOC);
    $stmtRestaurantes->close();

    // Buscar productos
    $stmtProductos = $conn->prepare("
        SELECT p.id, p.nombre, p.precio, p.restaurante_id, r.nombre as restaurante_nombre
        FROM productos p
        JOIN restaurantes r ON p.restaurante_id = r.id
        WHERE r.estado = 'ABIERTO'
        AND (p.nombre LIKE ? OR p.descripcion LIKE ? OR p.categoria LIKE ?)
        ORDER BY p.nombre
        LIMIT 5
    ");
    $stmtProductos->bind_param('sss', $searchTerm, $searchTerm, $searchTerm);
    $stmtProductos->execute();
    $resultProductos = $stmtProductos->get_result();
    $productos = $resultProductos->fetch_all(MYSQLI_ASSOC);
    $stmtProductos->close();

    echo json_encode([
        'restaurantes' => $restaurantes,
        'productos' => $productos
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
