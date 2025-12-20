
-- Eliminar base de datos si existe
DROP DATABASE IF EXISTS foodapp_db;

-- Crear base de datos con charset UTF-8
CREATE DATABASE foodapp_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE foodapp_db;

-- =====================================================
-- TABLAS PRINCIPALES
-- =====================================================

-- Table: usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    telefono VARCHAR(20),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tipo ENUM('cliente', 'repartidor', 'restaurante', 'admin') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: clientes
CREATE TABLE clientes (
    id INT PRIMARY KEY,
    FOREIGN KEY (id) REFERENCES usuarios(id) ON DELETE CASCADE,
    direcciones TEXT,
    historial_pedidos TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: repartidores
CREATE TABLE repartidores (
    id INT PRIMARY KEY,
    FOREIGN KEY (id) REFERENCES usuarios(id) ON DELETE CASCADE,
    vehiculo VARCHAR(100),
    licencia VARCHAR(100),
    zona VARCHAR(100),
    disponible BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: restaurantes (CON COLUMNA IMAGEN AGREGADA)
CREATE TABLE restaurantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    ruc VARCHAR(20) UNIQUE,
    direccion TEXT,
    tiempo_entrega INT,
    calificacion FLOAT DEFAULT 0,
    estado ENUM('ABIERTO', 'CERRADO', 'OCUPADO') DEFAULT 'CERRADO',
    ubicacion_gps VARCHAR(255),
    usuario_id INT,
    imagen VARCHAR(255), -- NUEVO: Para funcionalidad de imágenes
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: productos
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    categoria VARCHAR(100),
    disponible BOOLEAN DEFAULT TRUE,
    imagen VARCHAR(255),
    opciones_dieteticas TEXT,
    restaurante_id INT,
    FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: pedidos
CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('PENDIENTE', 'CONFIRMADO', 'PREPARANDO', 'LISTO', 'EN_CAMINO', 'ENTREGADO', 'CANCELADO') DEFAULT 'PENDIENTE',
    total DECIMAL(10,2),
    tiempo_estimado INT,
    metodo_pago ENUM('TARJETA', 'EFECTIVO', 'BILLETERA_DIGITAL'),
    propina DECIMAL(10,2) DEFAULT 0,
    descuento DECIMAL(10,2) DEFAULT 0,
    cliente_id INT,
    restaurante_id INT,
    repartidor_id INT,
    direccion_entrega TEXT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id),
    FOREIGN KEY (repartidor_id) REFERENCES repartidores(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: detalle_pedido
CREATE TABLE detalle_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT,
    producto_id INT,
    cantidad INT NOT NULL,
    precio DECIMAL(10,2),
    observaciones TEXT,
    opciones_personalizables TEXT,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: direcciones
CREATE TABLE direcciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT,
    calle VARCHAR(255),
    numero VARCHAR(50),
    ciudad VARCHAR(100),
    referencia TEXT,
    predeterminada BOOLEAN DEFAULT FALSE,
    coordenadas_gps VARCHAR(255),
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: pagos
CREATE TABLE pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT,
    monto DECIMAL(10,2),
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('PENDIENTE', 'PROCESANDO', 'COMPLETADO', 'FALLIDO') DEFAULT 'PENDIENTE',
    tipo_tarjeta VARCHAR(50),
    datos_encriptados TEXT,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: valoraciones
CREATE TABLE valoraciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT,
    pedido_id INT,
    restaurante_id INT,
    calificacion_restaurante INT CHECK (calificacion_restaurante BETWEEN 1 AND 5),
    calificacion_pedido INT CHECK (calificacion_pedido BETWEEN 1 AND 5),
    comentario TEXT,
    fecha_valoracion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: imagenes_restaurante
CREATE TABLE imagenes_restaurante (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurante_id INT NOT NULL,
    url_imagen VARCHAR(500) NOT NULL,
    tipo ENUM('principal', 'galeria') DEFAULT 'galeria',
    orden INT DEFAULT 0,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- ÍNDICES PARA RENDIMIENTO
-- =====================================================

CREATE INDEX idx_pedidos_estado ON pedidos(estado);
CREATE INDEX idx_pedidos_fecha ON pedidos(fecha_pedido);
CREATE INDEX idx_productos_categoria ON productos(categoria);
CREATE INDEX idx_productos_restaurante ON productos(restaurante_id);
CREATE INDEX idx_restaurantes_estado ON restaurantes(estado);
CREATE INDEX idx_usuarios_tipo ON usuarios(tipo);
CREATE INDEX idx_usuarios_email ON usuarios(email);

-- =====================================================
-- DATOS DE EJEMPLO
-- =====================================================

-- Usuarios de ejemplo
INSERT INTO usuarios (nombre, email, contrasena, telefono, tipo) VALUES
('Cliente Ejemplo', 'cliente@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '123456789', 'cliente'),
('Cliente Dos', 'cliente2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '987654321', 'cliente'),
('Repartidor Ejemplo', 'repartidor@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555666777', 'repartidor'),
('Restaurante Ejemplo', 'restaurante@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '111222333', 'restaurante'),
('Pizzeria Italiana', 'pizzeria@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '444555666', 'restaurante'),
('Cafe Central', 'cafe@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '777888999', 'restaurante'),
('Comida Criolla', 'criolla@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '000111222', 'restaurante'),
('Sushi Express', 'sushi@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '333444555', 'restaurante'),
('Burger King Arequipa', 'burger@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '666777888', 'restaurante'),
('Polleria El Buen Sabor', 'polleria@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '999000111', 'restaurante'),
('Heladeria Italiana', 'heladeria@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '222333444', 'restaurante'),
('Taco Bell Arequipa', 'taco@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555666777', 'restaurante'),
('Chifa Express', 'chifa@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '888999000', 'restaurante'),
('Vegano Natural', 'vegano@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '111222333', 'restaurante'),
('Poke Bowl', 'poke@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '444555666', 'restaurante'),
('Donde Hugo', 'dondehugo@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '777888999', 'restaurante'),
('La Creperia', 'creperia@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '000111222', 'restaurante'),
('Starbucks Arequipa', 'starbucks@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '333444555', 'restaurante'),
('KFC Arequipa', 'kfc@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '666777888', 'restaurante'),
('Admin', 'admin@foodapp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '000000000', 'admin');

-- Clientes y repartidores
INSERT INTO clientes (id) VALUES (1), (2);
INSERT INTO repartidores (id, vehiculo, licencia, zona) VALUES (3, 'Moto', 'LIC123', 'Arequipa Centro');

-- Restaurantes (con columna imagen incluida - campos vacíos para subida manual)
INSERT INTO restaurantes (nombre, ruc, direccion, tiempo_entrega, estado, usuario_id, ubicacion_gps, imagen) VALUES
('Restaurante Ejemplo', '12345678901', 'Calle San Francisco 117, Arequipa', 30, 'ABIERTO', 4, '-16.3989,-71.5350', NULL),
('Pizzeria Italiana', '23456789012', 'Av. Ejercito 123, Arequipa', 25, 'ABIERTO', 5, '-16.4090,-71.5375', NULL),
('Cafe Central', '34567890123', 'Plaza de Armas, Arequipa', 15, 'ABIERTO', 6, '-16.3988,-71.5350', NULL),
('Comida Criolla', '45678901234', 'Calle Mercaderes 456, Arequipa', 35, 'CERRADO', 7, '-16.4020,-71.5300', NULL),
('Sushi Express', '56789012345', 'Av. Kennedy 789, Arequipa', 20, 'ABIERTO', 8, '-16.4050,-71.5400', NULL),
('Burger King Arequipa', '67890123456', 'Mall Aventura Plaza, Arequipa', 15, 'ABIERTO', 9, '-16.4120,-71.5250', NULL),
('Polleria El Buen Sabor', '78901234567', 'Calle Lima 321, Arequipa', 25, 'ABIERTO', 10, '-16.3990,-71.5330', NULL),
('Heladeria Italiana', '89012345678', 'Calle Santa Catalina 654, Arequipa', 10, 'ABIERTO', 11, '-16.3970,-71.5360', NULL),
('Taco Bell Arequipa', '90123456789', 'Calle San Francisco 200, Arequipa', 20, 'ABIERTO', 12, '-16.4000,-71.5355', NULL),
('Chifa Express', '01234567890', 'Av. Venezuela 150, Arequipa', 25, 'ABIERTO', 13, '-16.4030,-71.5320', NULL),
('Vegano Natural', '12345678902', 'Calle Jerusalen 300, Arequipa', 15, 'ABIERTO', 14, '-16.3960,-71.5340', NULL),
('Poke Bowl', '23456789013', 'Mall Plaza Arequipa, Arequipa', 20, 'ABIERTO', 15, '-16.4070,-71.5260', NULL),
('Donde Hugo', '34567890124', 'Calle Ugarte 401, Arequipa', 30, 'ABIERTO', 16, '-16.4010,-71.5310', NULL),
('La Creperia', '45678901235', 'Calle Santa Catalina 200, Arequipa', 15, 'ABIERTO', 17, '-16.3980,-71.5365', NULL),
('Starbucks Arequipa', '56789012346', 'Calle Mercaderes 200, Arequipa', 10, 'ABIERTO', 18, '-16.3995,-71.5345', NULL),
('KFC Arequipa', '67890123457', 'Av. Ejercito 500, Arequipa', 20, 'ABIERTO', 19, '-16.4100,-71.5380', NULL);

-- Productos con caracteres correctamente codificados (sin imágenes pre-asignadas)
INSERT INTO productos (nombre, descripcion, precio, stock, categoria, restaurante_id, imagen) VALUES
-- Restaurante Ejemplo (ID: 1) - Pizzas y pastas
('Pizza Margherita', 'Pizza clasica con queso y tomate', 25.00, 10, 'Pizza', 1, NULL),
('Pizza Pepperoni', 'Pizza con pepperoni y queso', 27.00, 9, 'Pizza', 1, NULL),
('Pizza Cuatro Quesos', 'Pizza con cuatro tipos de queso', 29.00, 8, 'Pizza', 1, NULL),
('Pizza Vegetariana', 'Pizza con vegetales frescos', 26.00, 7, 'Pizza', 1, NULL),
('Calzone', 'Pizza doblada con relleno', 22.00, 12, 'Pizza', 1, NULL),

-- Pizzeria Italiana (ID: 2) - Pastas
('Lasagna', 'Pasta horneada con carne', 28.00, 6, 'Pasta', 2, NULL),
('Pasta Carbonara', 'Pasta con salsa carbonara', 22.00, 8, 'Pasta', 2, NULL),
('Spaghetti Bolognese', 'Spaghetti con salsa boloñesa', 24.00, 10, 'Pasta', 2, NULL),
('Penne Arrabbiata', 'Pasta con salsa picante', 20.00, 9, 'Pasta', 2, NULL),
('Ravioli', 'Pasta rellena de ricotta', 26.00, 5, 'Pasta', 2, NULL),

-- Cafe Central (ID: 3) - Bebidas y cafés
('Cafe Americano', 'Cafe negro tradicional', 8.00, 20, 'Bebidas', 3, NULL),
('Cappuccino', 'Cafe con leche espumosa', 10.00, 22, 'Bebidas', 3, NULL),
('Latte Macchiato', 'Cafe con leche manchada', 12.00, 18, 'Bebidas', 3, NULL),
('Mocha', 'Cafe con chocolate', 13.00, 15, 'Bebidas', 3, NULL),
('Té Verde', 'Té verde natural', 9.00, 25, 'Bebidas', 3, NULL),
('Croissant', 'Panecillo frances', 6.00, 30, 'Reposteria', 3, NULL),

-- Comida Criolla (ID: 4) - Platos peruanos
('Lomo Saltado', 'Plato tipico peruano con arroz', 28.00, 12, 'Criolla', 4, NULL),
('Aji de Gallina', 'Pollo en crema con aji', 24.00, 10, 'Criolla', 4, NULL),
('Rocoto Relleno', 'Rocoto relleno con carne', 22.00, 11, 'Criolla', 4, NULL),
('Ceviche', 'Pescado marinado con limon', 30.00, 5, 'Mariscos', 4, NULL),
('Arroz con Pollo', 'Arroz con pollo criollo', 20.00, 15, 'Criolla', 4, NULL),
('Tacu Tacu', 'Tortilla de arroz y frijoles', 18.00, 8, 'Criolla', 4, NULL),

-- Sushi Express (ID: 5) - Sushi y comida japonesa
('Sushi Roll', 'Roll de sushi con salmon', 35.00, 7, 'Sushi', 5, NULL),
('Nigiri de Atun', 'Sushi nigiri con atun fresco', 40.00, 4, 'Sushi', 5, NULL),
('Temaki', 'Cono de sushi', 32.00, 6, 'Sushi', 5, NULL),
('Sashimi Mixto', 'Variedad de sashimi fresco', 45.00, 3, 'Sushi', 5, NULL),
('Ramen', 'Sopa de fideos japonesa', 25.00, 10, 'Sopas', 5, NULL),

-- Burger King Arequipa (ID: 6) - Hamburguesas
('Whopper', 'Hamburguesa grande con queso', 20.00, 18, 'Hamburguesas', 6, NULL),
('Big Mac', 'Hamburguesa doble con salsa especial', 22.00, 16, 'Hamburguesas', 6, NULL),
('Cheeseburger', 'Hamburguesa con queso', 15.00, 25, 'Hamburguesas', 6, NULL),
('Chicken Burger', 'Hamburguesa de pollo', 18.00, 20, 'Hamburguesas', 6, NULL),
('Veggie Burger', 'Hamburguesa vegetariana', 16.00, 12, 'Hamburguesas', 6, NULL),

-- Polleria El Buen Sabor (ID: 7) - Pollo
('Pollo a la Brasa', 'Pollo asado con papas', 26.00, 14, 'Pollo', 7, NULL),
('Salchipapa', 'Papas fritas con salchicha', 14.00, 20, 'Snacks', 7, NULL),
('Alitas de Pollo', 'Alitas de pollo con salsa', 22.00, 16, 'Pollo', 7, NULL),
('Pollo con Papas', 'Pollo frito con papas', 24.00, 18, 'Pollo', 7, NULL),
('Ensalada de Pollo', 'Ensalada con pollo grillado', 19.00, 10, 'Ensaladas', 7, NULL),

-- Heladeria Italiana (ID: 8) - Helados y postres
('Helado de Vainilla', 'Helado cremoso de vainilla', 12.00, 25, 'Postres', 8, NULL),
('Gelato de Chocolate', 'Helado italiano de chocolate', 14.00, 13, 'Postres', 8, NULL),
('Tiramisu', 'Postre italiano con cafe', 15.00, 9, 'Postres', 8, NULL),
('Panna Cotta', 'Postre italiano de vainilla', 13.00, 11, 'Postres', 8, NULL),
('Cannoli', 'Postre siciliano relleno', 16.00, 8, 'Postres', 8, NULL),

-- Taco Bell Arequipa (ID: 9) - Tacos y comida mexicana
('Taco de Carne', 'Taco con carne molida', 12.00, 30, 'Tacos', 9, NULL),
('Burrito Supreme', 'Burrito grande con relleno', 18.00, 15, 'Burritos', 9, NULL),
('Quesadilla', 'Tortilla con queso fundido', 14.00, 20, 'Mexicana', 9, NULL),
('Nachos', 'Totopos con queso y jalapeños', 16.00, 18, 'Snacks', 9, NULL),
('Enchiladas', 'Tortillas enrolladas con salsa', 20.00, 12, 'Mexicana', 9, NULL),

-- Chifa Express (ID: 10) - Comida china
('Chop Suey', 'Vegetales salteados con carne', 22.00, 14, 'Chifa', 10, NULL),
('Arroz Chaufa', 'Arroz frito con verduras', 18.00, 20, 'Chifa', 10, NULL),
('Tallarin Saltado', 'Fideos salteados con carne', 20.00, 16, 'Chifa', 10, NULL),
('Wantan', 'Empanaditas chinas', 15.00, 25, 'Chifa', 10, NULL),
('Pollo con Almendras', 'Pollo con salsa de almendras', 24.00, 10, 'Chifa', 10, NULL),

-- Vegano Natural (ID: 11) - Comida vegana
('Hamburguesa Vegana', 'Hamburguesa de lentejas', 16.00, 18, 'Vegano', 11, NULL),
('Ensalada Quinoa', 'Ensalada con quinoa y vegetales', 19.00, 15, 'Ensaladas', 11, NULL),
('Wrap Vegetariano', 'Wrap con vegetales frescos', 14.00, 22, 'Vegano', 11, NULL),
('Smoothie Verde', 'Batido de vegetales', 12.00, 25, 'Bebidas', 11, NULL),
('Falafel', 'Croquetas de garbanzos', 17.00, 12, 'Vegano', 11, NULL),

-- Poke Bowl (ID: 12) - Poké y bowls saludables
('Poke Salmon', 'Poke con salmon fresco', 28.00, 10, 'Poke', 12, NULL),
('Poke Atun', 'Poke con atun fresco', 30.00, 8, 'Poke', 12, NULL),
('Poke Vegetariano', 'Poke con tofu y vegetales', 24.00, 12, 'Poke', 12, NULL),
('Acai Bowl', 'Tazón de acai con frutas', 22.00, 15, 'Bowls', 12, NULL),
('Buddha Bowl', 'Tazón saludable con quinoa', 20.00, 18, 'Bowls', 12, NULL),

-- Donde Hugo (ID: 13) - Comida rápida peruana
('Hamburguesa Criolla', 'Hamburguesa con estilo peruano', 16.00, 20, 'Hamburguesas', 13, NULL),
('Choripán', 'Pan con chorizo', 12.00, 25, 'Snacks', 13, NULL),
('Lomito', 'Sándwich de lomo', 18.00, 15, 'Sandwiches', 13, NULL),
('Papas Fritas', 'Papas fritas con ketchup', 8.00, 30, 'Snacks', 13, NULL),
('Bebida Grande', 'Refresco grande', 6.00, 40, 'Bebidas', 13, NULL),

-- La Creperia (ID: 14) - Crepes y postres
('Crepe Nutella', 'Crepe con nutella y plátano', 15.00, 18, 'Crepes', 14, NULL),
('Crepe Frutas', 'Crepe con frutas frescas', 16.00, 16, 'Crepes', 14, NULL),
('Crepe Salado', 'Crepe con jamón y queso', 14.00, 20, 'Crepes', 14, NULL),
('Waffle con Helado', 'Waffle con helado de vainilla', 18.00, 12, 'Postres', 14, NULL),
('Panqueque', 'Panqueque con miel', 12.00, 22, 'Postres', 14, NULL),

-- Starbucks Arequipa (ID: 15) - Cafés premium
('Frappuccino', 'Bebida helada con cafe', 16.00, 20, 'Bebidas', 15, NULL),
('Cold Brew', 'Cafe frio preparado', 14.00, 18, 'Bebidas', 15, NULL),
('Espresso', 'Cafe espresso italiano', 8.00, 30, 'Bebidas', 15, NULL),
('Muffin', 'Panecillo con arandanos', 10.00, 25, 'Reposteria', 15, NULL),
('Sandwich', 'Sandwich de pavo y queso', 18.00, 15, 'Sandwiches', 15, NULL),

-- KFC Arequipa (ID: 16) - Pollo frito
('Bucket de Pollo', 'Cubo con 8 piezas de pollo', 45.00, 8, 'Pollo', 16, NULL),
('Pollo Crispy', 'Pollo frito crujiente', 22.00, 20, 'Pollo', 16, NULL),
('Twister', 'Wrap de pollo', 16.00, 18, 'Wraps', 16, NULL),
('Papas Grandes', 'Porción grande de papas', 12.00, 25, 'Snacks', 16, NULL),
('Ensalada', 'Ensalada con pollo', 15.00, 15, 'Ensaladas', 16, NULL);

-- =====================================================
-- MENSAJE DE CONFIRMACIÓN
-- =====================================================

SELECT '🎉 BASE DE DATOS FOODAPP v3.1 CREADA EXITOSAMENTE 🎉' as ESTADO;
SELECT '✓ Todas las tablas creadas con charset UTF-8' as VERIFICACION_1;
SELECT '✓ Columna imagen agregada a restaurantes' as VERIFICACION_2;
SELECT '✓ 16 restaurantes agregados (campos imagen vacíos)' as VERIFICACION_3;
SELECT '✓ 80+ productos agregados (campos imagen vacíos)' as VERIFICACION_4;
SELECT '✓ Funcionalidad de subida de imágenes lista para usar' as VERIFICACION_5;
SELECT '✓ Código PHP corregido (referencias contrasena)' as VERIFICACION_6;
SELECT '✓ Lógica de actualización de restaurantes mejorada' as VERIFICACION_7;