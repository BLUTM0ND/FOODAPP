<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $lat = isset($_POST['lat']) ? floatval($_POST['lat']) : null;
    $lng = isset($_POST['lng']) ? floatval($_POST['lng']) : null;
    
    if ($lat !== null && $lng !== null) {
        $_SESSION['user_location'] = ['lat' => $lat, 'lng' => $lng];
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid coordinates']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
}
?>