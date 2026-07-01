-- Crear la base de datos si no existe
CREATE DATABASE IF NOT EXISTS `frutas_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `frutas_db`;

-- Tabla de Usuarios
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Frutas
CREATE TABLE IF NOT EXISTS `fruits` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `price` DECIMAL(10, 2) NOT NULL,
  `stock` INT NOT NULL DEFAULT 0,
  `image_path` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar algunas frutas de ejemplo para que la tienda no aparezca vacía al inicio
INSERT INTO `fruits` (`name`, `description`, `price`, `stock`, `image_path`) VALUES
('Manzana Roja', 'Manzanas crujientes, dulces y muy jugosas. Ricas en fibra y perfectas para consumir a cualquier hora del día.', 1.50, 45, 'uploads/manzana_roja.jpg'),
('Plátano de Canarias', 'Plátanos maduros con el dulzor perfecto. Aportan una gran cantidad de potasio y energía natural.', 0.90, 80, 'uploads/platano.jpg'),
('Fresa Silvestre', 'Fresas frescas de temporada, ácidas y dulces a la vez. Ideales para postres, ensaladas o solas.', 3.20, 20, 'uploads/fresas.jpg'),
('Naranja de Valencia', 'Naranjas súper jugosas de calidad premium, ideales tanto para comer enteras como para preparar un exquisito zumo recién exprimido.', 1.20, 100, 'uploads/naranja.jpg'),
('Aguacate Hass', 'Aguacates cremosos y suaves con el punto justo de madurez. Ideales para preparar un delicioso guacamole.', 2.80, 15, 'uploads/aguacate.jpg'),
('Piña Gold', 'Piña tropical de gran dulzura y aroma intenso. Rica en vitamina C y perfecta para refrescar tus tardes.', 3.50, 12, 'uploads/pina.jpg');

-- Tabla de Pedidos (Compras)
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `customer_name` VARCHAR(100) NOT NULL DEFAULT 'Cliente General',
  `total` DECIMAL(10, 2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Detalles de Pedido
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `fruit_id` INT NOT NULL,
  `quantity` INT NOT NULL,
  `price` DECIMAL(10, 2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`fruit_id`) REFERENCES `fruits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

