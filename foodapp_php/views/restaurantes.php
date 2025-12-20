<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurantes - FoodApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <main class="container mt-4">
        <h2 class="mb-4"><i class="fas fa-utensils"></i> Lista de Restaurantes</h2>
        <div class="row" id="restaurantes-container">
            <?php
            include_once '../includes/config.php';
            $sql = "SELECT * FROM restaurantes";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $coords = explode(',', $row['ubicacion_gps']);
                    $lat = trim($coords[0]);
                    $lng = trim($coords[1]);
                    $estadoClass = $row['estado'] == 'ABIERTO' ? 'success' : ($row['estado'] == 'CERRADO' ? 'danger' : 'warning');
                    echo "<div class='col-md-6 col-lg-4 mb-4'>
                            <div class='card h-100'>
                                <div id='map-{$row['id']}' style='height: 200px; border-radius: 10px 10px 0 0;'></div>
                                <div class='card-body'>
                                    <h5 class='card-title'>{$row['nombre']}</h5>
                                    <p class='card-text'><i class='fas fa-map-marker-alt'></i> {$row['direccion']}</p>
                                    <p class='card-text'><span class='badge bg-{$estadoClass}'>{$row['estado']}</span></p>
                                    <a href='menu.php?id={$row['id']}' class='btn btn-primary'>Ver Menú</a>
                                </div>
                            </div>
                          </div>";
                    echo "<script>
                            var map{$row['id']} = L.map('map-{$row['id']}').setView([{$lat}, {$lng}], 13);
                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                attribution: '© OpenStreetMap contributors'
                            }).addTo(map{$row['id']});
                            L.marker([{$lat}, {$lng}]).addTo(map{$row['id']});
                          </script>";
                }
            } else {
                echo "<div class='col-12'><div class='alert alert-info text-center'>No hay restaurantes disponibles.</div></div>";
            }
            ?>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
