<?php
header('Content-Type: application/json');
include_once 'includes/config.php';

$term = isset($_GET['term']) ? trim($_GET['term']) : '';

if (empty($term)) {
    echo json_encode(['restaurantes' => [], 'productos' => []]);
    exit;
}

$term = $conn->real_escape_string($term);
$results = ['restaurantes' => [], 'productos' => []];

// Buscar restaurantes por nombre o dirección
$sql_restaurantes = "SELECT id, nombre, direccion FROM restaurantes
                    WHERE (nombre LIKE '%$term%' OR direccion LIKE '%$term%')
                    AND estado = 'ABIERTO'";

$result_restaurantes = $conn->query($sql_restaurantes);
if ($result_restaurantes) {
    while ($row = $result_restaurantes->fetch_assoc()) {
        $results['restaurantes'][] = $row['id'];
    }
}

// Buscar productos por nombre o descripción
$sql_productos = "SELECT p.nombre, p.descripcion, p.restaurante_id, r.nombre as restaurante_nombre
                 FROM productos p
                 JOIN restaurantes r ON p.restaurante_id = r.id
                 WHERE (p.nombre LIKE '%$term%' OR p.descripcion LIKE '%$term%' OR p.categoria LIKE '%$term%')
                 AND r.estado = 'ABIERTO'";

$result_productos = $conn->query($sql_productos);
if ($result_productos) {
    while ($row = $result_productos->fetch_assoc()) {
        $results['productos'][] = [
            'id' => $row['restaurante_id'],
            'restaurante_id' => $row['restaurante_id'],
            'restaurante_nombre' => $row['restaurante_nombre'],
            'producto_nombre' => $row['nombre'],
            'producto_descripcion' => $row['descripcion']
        ];
    }
}

$conn->close();
echo json_encode($results);
?>
