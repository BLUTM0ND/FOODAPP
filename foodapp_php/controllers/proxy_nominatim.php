<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$type = $_GET['type'] ?? 'search';
$query = $_GET['q'] ?? '';
$lat = $_GET['lat'] ?? '';
$lon = $_GET['lon'] ?? '';

try {
    if ($type === 'reverse') {
        // Reverse geocoding
        $url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=" . urlencode($lat) . "&lon=" . urlencode($lon);
    } else {
        // Forward geocoding/search
        $url = "https://nominatim.openstreetmap.org/search?format=jsonv2&q=" . urlencode($query) . "&limit=5&countrycodes=PE";
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: FoodApp/1.0 (contact@foodapp.com)',
                'Accept: application/json'
            ],
            'timeout' => 10
        ]
    ]);

    $response = file_get_contents($url, false, $context);

    if ($response === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch data from Nominatim']);
        exit;
    }

    // Forward the response
    echo $response;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
