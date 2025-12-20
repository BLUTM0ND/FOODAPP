<?php
session_start();
include_once '../includes/config.php';

header('Content-Type: application/json');

// Check if user is logged in as delivery person
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'repartidor') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    // Get restaurants with GPS coordinates
    $query = $conn->prepare("
        SELECT id, nombre, direccion, ubicacion_gps, calificacion
        FROM restaurantes
        WHERE estado = 'ABIERTO'
        ORDER BY calificacion DESC, nombre ASC
    ");

    $query->execute();
    $result = $query->get_result();

    $restaurants = [];
    while ($row = $result->fetch_assoc()) {
        // Parse GPS coordinates if available
        $lat = null;
        $lng = null;

        if (!empty($row['ubicacion_gps'])) {
            // Assuming ubicacion_gps is stored as "lat,lng" or similar
            $coords = explode(',', $row['ubicacion_gps']);
            if (count($coords) == 2) {
                $lat = floatval(trim($coords[0]));
                $lng = floatval(trim($coords[1]));
            }
        }

        // If no GPS coordinates, assign random coordinates within Arequipa bounds
        if ($lat === null || $lng === null) {
            // Arequipa city center bounds
            $lat = -16.3989 + (mt_rand(-500, 500) / 10000); // Random variation around center
            $lng = -71.5350 + (mt_rand(-500, 500) / 10000);
        }

        $restaurants[] = [
            'id' => $row['id'],
            'nombre' => $row['nombre'],
            'direccion' => $row['direccion'],
            'calificacion' => $row['calificacion'] ?: 0,
            'lat' => $lat,
            'lng' => $lng
        ];
    }

    echo json_encode($restaurants);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
