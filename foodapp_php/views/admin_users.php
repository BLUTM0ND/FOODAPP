<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header('Location: login.php');
    exit;
}
include_once '../includes/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Admin FoodApp</title>
    <link rel="icon" href="../assets/FOODAPP.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #ff1744;
            --secondary-color: #ff6d00;
            --success-color: #00c853;
            --warning-color: #ff9800;
            --danger-color: #f44336;
            --dark-color: #212121;
            --light-color: #fafafa;
            --gray-color: #757575;
            --border-color: #e0e0e0;
            --shadow: 0 2px 8px rgba(0,0,0,0.1);
            --shadow-lg: 0 4px 20px rgba(0,0,0,0.15);
            --border-radius: 12px;
            --border-radius-lg: 16px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--dark-color);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Header */
        .page-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: var(--gray-color);
            font-size: 1.1rem;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .btn:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
        }

        .btn-danger {
            background: var(--danger-color);
        }

        .btn-danger:hover {
            background: #d32f2f;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--gray-color);
            font-weight: 500;
            text-transform: capitalize;
        }

        /* Search Section */
        .search-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .search-input {
            width: 100%;
            padding: 1rem 1.5rem;
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 1.1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 23, 68, 0.1);
        }

        /* Table */
        .table-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }

        .table-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem;
            font-weight: 600;
            font-size: 1.1rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem 1.5rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-weight: 600;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tbody tr {
            transition: all 0.2s ease;
        }

        tbody tr:hover {
            background: rgba(255, 23, 68, 0.05);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 1rem;
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-details h4 {
            margin: 0;
            font-weight: 600;
            color: var(--dark-color);
        }

        .user-details p {
            margin: 0.25rem 0 0 0;
            color: var(--gray-color);
            font-size: 0.9rem;
        }

        .user-type {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .user-type.cliente {
            background: rgba(0, 200, 83, 0.1);
            color: var(--success-color);
        }

        .user-type.restaurante {
            background: rgba(255, 109, 0, 0.1);
            color: var(--secondary-color);
        }

        .user-type.repartidor {
            background: rgba(255, 152, 0, 0.1);
            color: var(--warning-color);
        }

        .user-type.admin {
            background: rgba(244, 67, 54, 0.1);
            color: var(--danger-color);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            border-radius: 6px;
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            display: none;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: var(--border-radius-lg);
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            animation: slideUp 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translate(-50%, -40%); }
            to { opacity: 1; transform: translate(-50%, -50%); }
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius-lg) var(--border-radius-lg) 0 0;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }

        .modal-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark-color);
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 23, 68, 0.1);
        }

        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }

        .modal-footer {
            padding: 1.5rem 2rem;
            background: var(--light-color);
            border-radius: 0 0 var(--border-radius-lg) var(--border-radius-lg);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        .close-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: background 0.3s ease;
        }

        .close-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }

        .alert-success {
            background: rgba(0, 200, 83, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(0, 200, 83, 0.2);
        }

        .alert-error {
            background: rgba(244, 67, 54, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(244, 67, 54, 0.2);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .page-header {
                flex-direction: column;
                text-align: center;
                padding: 1.5rem;
            }

            .page-title {
                font-size: 2rem;
            }

            .header-actions {
                width: 100%;
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .table-container {
                overflow-x: auto;
            }

            table {
                min-width: 800px;
            }

            .user-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .action-buttons {
                flex-direction: column;
                gap: 0.25rem;
            }

            .modal-content {
                width: 95%;
                margin: 1rem;
            }
        }

        /* Loading animation */
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .loading {
            animation: pulse 1.5s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1 class="page-title">
                    <i class="fas fa-users me-3"></i>Gestión de Usuarios
                </h1>
                <p class="page-subtitle">Administra todos los usuarios de la plataforma FoodApp</p>
            </div>
            <div class="header-actions">
                <a href="admin.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
                <a href="../controllers/logout.php" class="btn">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php
        if (isset($_GET['success']) && $_GET['success'] == 'updated') {
            echo '<div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    Usuario actualizado correctamente.
                  </div>';
        }
        if (isset($_GET['error'])) {
            echo '<div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    Error: ' . htmlspecialchars($_GET['error']) . '
                  </div>';
        }
        ?>

        <!-- Stats Cards -->
        <?php
        $total_users = $conn->query("SELECT COUNT(*) as total FROM usuarios")->fetch_assoc()['total'];
        $user_types = $conn->query("SELECT tipo, COUNT(*) as count FROM usuarios GROUP BY tipo");
        $stats = [];
        while ($stat = $user_types->fetch_assoc()) {
            $stats[$stat['tipo']] = $stat['count'];
        }
        ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_users; ?></div>
                <div class="stat-label">Total Usuarios</div>
            </div>
            <?php foreach ($stats as $type => $count): ?>
            <div class="stat-card">
                <div class="stat-number"><?php echo $count; ?></div>
                <div class="stat-label"><?php echo ucfirst($type); ?>s</div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Search Section -->
        <div class="search-section">
            <input type="text" id="searchInput" class="search-input" placeholder="🔍 Buscar por nombre, email, teléfono o tipo de usuario...">
        </div>

        <!-- Users Table -->
        <div class="table-container">
            <div class="table-header">
                <i class="fas fa-list me-2"></i>Lista de Usuarios
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Tipo</th>
                        <th>Teléfono</th>
                        <th>Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("SELECT u.id, u.nombre, u.email, u.telefono, u.tipo, r.calificacion, u.fecha_registro FROM usuarios u LEFT JOIN restaurantes r ON u.id = r.usuario_id ORDER BY u.fecha_registro DESC");
                    while ($row = $result->fetch_assoc()) {
                        $initials = strtoupper(substr($row['nombre'], 0, 2));
                        $type_class = $row['tipo'];
                        echo "<tr>
                                <td>
                                    <div class='user-info'>
                                        <div class='user-avatar'>{$initials}</div>
                                        <div class='user-details'>
                                            <h4>{$row['nombre']}</h4>
                                            <p>ID: {$row['id']}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>{$row['email']}</td>
                                <td><span class='user-type {$type_class}'>{$row['tipo']}</span></td>
                                <td>" . ($row['telefono'] ?: '<em>No registrado</em>') . "</td>
                                <td>" . date('d/m/Y H:i', strtotime($row['fecha_registro'])) . "</td>
                                <td>
                                    <div class='action-buttons'>
                                        <button class='btn btn-small' onclick=\"editUser({$row['id']}, '{$row['nombre']}', '{$row['email']}', '{$row['telefono']}', '{$row['tipo']}', '" . ($row['calificacion'] ?: '0') . "')\">
                                            <i class='fas fa-edit'></i> Editar
                                        </button>
                                        <button class='btn btn-danger btn-small' onclick=\"confirmDelete('user', {$row['id']})\">
                                            <i class='fas fa-trash'></i> Eliminar
                                        </button>
                                    </div>
                                </td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editModal" class="modal-overlay">
        <div class="modal-content">
            <button class="close-btn" onclick="closeEditModal()">
                <i class="fas fa-times"></i>
            </button>
            <div class="modal-header">
                <h2 class="modal-title">
                    <i class="fas fa-user-edit me-2"></i>Editar Usuario Completo
                </h2>
            </div>
            <form id="editForm" method="POST" action="../controllers/admin_actions.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="user_id" id="editUserId">
                    
                    <div class="form-group">
                        <label class="form-label" for="editNombre">
                            <i class="fas fa-user me-2"></i>Nombre Completo
                        </label>
                        <input type="text" id="editNombre" name="nombre" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="editEmail">
                            <i class="fas fa-envelope me-2"></i>Email
                        </label>
                        <input type="email" id="editEmail" name="email" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="editTelefono">
                            <i class="fas fa-phone me-2"></i>Teléfono
                        </label>
                        <input type="tel" id="editTelefono" name="telefono" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="editPassword">
                            <i class="fas fa-lock me-2"></i>Nueva Contraseña
                        </label>
                        <input type="password" id="editPassword" name="password" class="form-input" 
                               placeholder="Dejar vacío para mantener la actual">
                        <small style="color: var(--gray-color); font-size: 0.85rem;">
                            Solo ingresa una contraseña si deseas cambiarla
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="editType">
                            <i class="fas fa-user-tag me-2"></i>Tipo de Usuario
                        </label>
                        <select id="editType" name="tipo" class="form-input form-select" required>
                            <option value="cliente">👤 Cliente</option>
                            <option value="restaurante">🍽️ Restaurante</option>
                            <option value="repartidor">🚴‍♂️ Repartidor</option>
                            <option value="admin">⚡ Admin</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="editCalificacion">
                            <i class="fas fa-star me-2"></i>Calificación (0.0 - 5.0)
                        </label>
                        <input type="number" id="editCalificacion" name="calificacion" class="form-input" 
                               min="0" max="5" step="0.1" placeholder="Ej: 4.5">
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" onclick="closeEditModal()" class="btn btn-outline">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editUser(id, nombre, email, telefono, tipo, calificacion) {
            document.getElementById('editUserId').value = id;
            document.getElementById('editNombre').value = nombre;
            document.getElementById('editEmail').value = email;
            document.getElementById('editTelefono').value = telefono || '';
            document.getElementById('editType').value = tipo;
            document.getElementById('editCalificacion').value = calificacion || '';
            document.getElementById('editPassword').value = ''; // Always empty for security
            document.getElementById('editModal').style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevent background scroll
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
            document.body.style.overflow = 'auto'; // Restore scroll
        }

        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('editModal').style.display === 'block') {
                closeEditModal();
            }
        });

        function confirmDelete(type, id) {
            if (confirm('¿Estás seguro de que quieres eliminar este ' + type + '? Esta acción no se puede deshacer.')) {
                // Show loading state
                const button = event.target;
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Eliminando...';
                button.disabled = true;

                window.location.href = '../controllers/admin_actions.php?action=delete_' + type + '&id=' + id;
            }
        }

        // Enhanced search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('tbody tr');
            let visibleCount = 0;

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const isVisible = text.includes(searchTerm);
                row.style.display = isVisible ? '' : 'none';
                if (isVisible) visibleCount++;
            });

            // Update stats if needed
            updateSearchStats(visibleCount, rows.length);
        });

        function updateSearchStats(visible, total) {
            // Could add a small indicator showing "X of Y results"
            console.log(`Mostrando ${visible} de ${total} usuarios`);
        }

        // Add loading animation to forms
        document.getElementById('editForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
            submitBtn.disabled = true;

            // Re-enable after 10 seconds as fallback
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 10000);
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'all 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>
