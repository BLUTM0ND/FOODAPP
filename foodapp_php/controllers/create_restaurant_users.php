<?php
// Script to create user accounts for restaurants that don't have one
include_once 'C:\xampp\htdocs\FOODAPP\foodapp_php\includes\config.php';

echo "<h1>Creating/Updating Restaurant User Accounts</h1>";

// Function to generate email from restaurant name
function generateRestaurantEmail($name, $id) {
    $clean = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
    return $clean . $id . '@foodapp.com';
}

// First, fix restaurants that share usuario_id - create unique users for them
$shared_users = $conn->query("
    SELECT r.usuario_id, COUNT(*) as count 
    FROM restaurantes r 
    WHERE r.usuario_id IS NOT NULL AND r.usuario_id != 0 
    GROUP BY r.usuario_id 
    HAVING count > 1
");

while ($shared = $shared_users->fetch_assoc()) {
    $shared_user_id = $shared['usuario_id'];
    
    // Get restaurants with this shared user
    $restaurants_with_shared = $conn->query("SELECT id, nombre FROM restaurantes WHERE usuario_id = $shared_user_id");
    
    $first = true;
    while ($rest = $restaurants_with_shared->fetch_assoc()) {
        if ($first) {
            // Keep the first one with the original user
            $first = false;
            continue;
        }
        
        // Create new user for the rest
        $restaurant_id = $rest['id'];
        $restaurant_name = $rest['nombre'];
        $email = generateRestaurantEmail($restaurant_name, $restaurant_id);
        $password = password_hash('password123', PASSWORD_DEFAULT);
        $user_type = 'restaurante';
        
        $insert_user = $conn->prepare("INSERT INTO usuarios (email, contrasena, tipo) VALUES (?, ?, ?)");
        $insert_user->bind_param('sss', $email, $password, $user_type);
        if ($insert_user->execute()) {
            $new_user_id = $conn->insert_id;
            $update_rest = $conn->prepare("UPDATE restaurantes SET usuario_id = ? WHERE id = ?");
            $update_rest->bind_param('ii', $new_user_id, $restaurant_id);
            $update_rest->execute();
            $update_rest->close();
            echo "<p>✅ Created unique user for shared restaurant: {$restaurant_name} (ID: {$restaurant_id}) - {$email}</p>";
        }
        $insert_user->close();
    }
}

// Then, update existing restaurant users with proper emails
$existing = $conn->query("SELECT r.id, r.nombre, r.usuario_id, u.email FROM restaurantes r JOIN usuarios u ON r.usuario_id = u.id WHERE r.usuario_id IS NOT NULL AND r.usuario_id != 0");
while ($row = $existing->fetch_assoc()) {
    $restaurant_id = $row['id'];
    $restaurant_name = $row['nombre'];
    $user_id = $row['usuario_id'];
    $current_email = $row['email'];
    
    $new_email = generateRestaurantEmail($restaurant_name, $restaurant_id);
    
    if ($current_email !== $new_email) {
        $update_email = $conn->prepare("UPDATE usuarios SET email = ? WHERE id = ?");
        $update_email->bind_param('si', $new_email, $user_id);
        if ($update_email->execute()) {
            echo "<p>✅ Updated email for {$restaurant_name}: {$current_email} → {$new_email}</p>";
        } else {
            echo "<p>❌ Failed to update email for {$restaurant_name}</p>";
        }
        $update_email->close();
    }
}

// Then, create accounts for restaurants without users
$query = "SELECT id, nombre FROM restaurantes WHERE usuario_id IS NULL OR usuario_id = 0";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    while ($restaurant = $result->fetch_assoc()) {
        $restaurant_id = $restaurant['id'];
        $restaurant_name = $restaurant['nombre'];

        $email = generateRestaurantEmail($restaurant_name, $restaurant_id);
        $password = password_hash('password123', PASSWORD_DEFAULT);
        $user_type = 'restaurante';

        // Insert user
        $insert_user = $conn->prepare("INSERT INTO usuarios (email, contrasena, tipo) VALUES (?, ?, ?)");
        $insert_user->bind_param('sss', $email, $password, $user_type);
        if ($insert_user->execute()) {
            $user_id = $conn->insert_id;

            // Update restaurant with usuario_id
            $update_rest = $conn->prepare("UPDATE restaurantes SET usuario_id = ? WHERE id = ?");
            $update_rest->bind_param('ii', $user_id, $restaurant_id);
            if ($update_rest->execute()) {
                echo "<p>✅ Created user for restaurant: {$restaurant_name} (ID: {$restaurant_id})</p>";
                echo "<p>   Email: {$email}</p>";
                echo "<p>   Password: password123</p>";
                echo "<p>   User ID: {$user_id}</p><br>";
            } else {
                echo "<p>❌ Failed to update restaurant {$restaurant_name}</p>";
            }
            $update_rest->close();
        } else {
            echo "<p>❌ Failed to create user for restaurant {$restaurant_name}</p>";
        }
        $insert_user->close();
    }
} else {
    echo "<p>No restaurants found without user accounts.</p>";
}

echo "<h2>Current Restaurant Users:</h2>";
$existing = $conn->query("SELECT r.nombre, r.id, u.email FROM restaurantes r JOIN usuarios u ON r.usuario_id = u.id WHERE r.usuario_id IS NOT NULL AND r.usuario_id != 0 ORDER BY r.nombre");
while ($row = $existing->fetch_assoc()) {
    echo "<p><strong>{$row['nombre']}</strong> (ID: {$row['id']}) - {$row['email']}</p>";
}

$conn->close();
?>
