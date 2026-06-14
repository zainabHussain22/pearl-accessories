

CREATE DATABASE IF NOT EXISTS pearl_accessories;
USE pearl_accessories;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- 1. ADMINS TABLE
CREATE TABLE IF NOT EXISTS admins (
  admin_id int NOT NULL AUTO_INCREMENT,
  username varchar(100) NOT NULL UNIQUE,
  password varchar(255) NOT NULL,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (admin_id),
  KEY idx_username (username)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password is hashed via password_hash() — original plain text: 'admin123'
INSERT INTO admins (admin_id, username, password) VALUES
(1, 'admin', '$2y$10$5CXh9VWvlxWyU8x0EKhXXuUnk5RQgHeGEf5H8cZ9uer2H7vP/Uk0e');

-- 2. USERS TABLE
CREATE TABLE IF NOT EXISTS users (
  user_id int NOT NULL AUTO_INCREMENT,
  username varchar(100) NOT NULL UNIQUE,
  password varchar(255) NOT NULL,
  email varchar(150) NOT NULL UNIQUE,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  first_name varchar(100),
  last_name varchar(100),
  phone varchar(20),
  address longtext,
  city varchar(100),
  country varchar(100),
  PRIMARY KEY (user_id),
  KEY idx_username (username),
  KEY idx_email (email)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Passwords are hashed via password_hash() — original plain text shown in comments
INSERT INTO users (user_id, username, password, email, created_at, first_name, last_name, phone, address, city, country) VALUES
(1, 'ayah', '$2y$10$Sc0u1Ucnhhm8l3Igsgxo8..QXTlo7F1fxGgjS43E.cok6UMlkiJg2', 'ayah@gmail.com', '2026-04-17 06:32:27', 'ayah', 'khudear', '0549063437', '25 a', 'Qatif', 'Saudi Arabia'), -- pwd: 1234
(2, 'zainab', '$2y$10$gc.pJQZiN7nwWrngUCUD2.AfmXe4.icOPgvah1ZDXWDxhuSfrYUSy', 'zainab@example.com', '2026-04-17 10:00:00', 'Zainab', 'Hussain', '0555555555', 'Main Street 1', 'Riyadh', 'Saudi Arabia'); -- pwd: password123

-- 3. CATEGORIES TABLE
CREATE TABLE IF NOT EXISTS categories (
  category_id int NOT NULL AUTO_INCREMENT,
  name varchar(100) NOT NULL UNIQUE,
  description longtext,
  PRIMARY KEY (category_id),
  KEY idx_name (name)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categories (category_id, name, description) VALUES
(1, 'Necklaces', 'Beautiful necklaces made from high-quality pearls'),
(2, 'Earrings', 'Elegant earrings to complete your look'),
(3, 'Bracelets', 'Stylish bracelets for all occasions'),
(4, 'Rings', 'Exquisite rings for special moments'),
(5, 'Anklets', 'Sophisticated anklets with pearl details'),
(6, 'Pendants', 'Beautiful pendants with refined elegance');

-- 4. PRODUCTS TABLE (مع added_by)
CREATE TABLE IF NOT EXISTS products (
  product_id int NOT NULL AUTO_INCREMENT,
  name varchar(150) NOT NULL,
  description longtext,
  price decimal(10,2) NOT NULL,
  image varchar(255),
  category varchar(100) DEFAULT 'Pearl Jewelry',
  stock int DEFAULT 0,
  material varchar(100) DEFAULT 'Genuine Freshwater Pearl',
  metal varchar(100) DEFAULT 'Sterling Silver 925',
  authenticity varchar(200) DEFAULT 'Certified Genuine • 100% Authentic',
  dimensions varchar(100),
  weight varchar(50),
  pearl_size varchar(50) DEFAULT '8-10mm',
  care_instructions longtext,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  added_by int DEFAULT NULL,
  PRIMARY KEY (product_id),
  KEY idx_name (name),
  KEY idx_category (category),
  KEY idx_price (price),
  KEY added_by (added_by),
  FOREIGN KEY (added_by) REFERENCES admins (admin_id) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO products (product_id, name, description, price, image, category, stock, material, metal, authenticity, dimensions, weight, pearl_size, care_instructions, added_by) VALUES
(1, 'Classic White Pearl Necklace', 'Elegant white pearl necklace with delicate design.', 149.99, 'pearl-necklace.jpg', 'Necklaces', 10, 'Genuine Freshwater Pearl', 'Sterling Silver 925', 'Certified Genuine • 100% Authentic', '40cm', '15g', '8-10mm', 'Avoid water and chemicals. Store in a soft pouch. Clean gently with a dry cloth.', 1),
(2, 'Golden South Sea Pearl Necklace', 'Luxurious golden south sea pearls with premium craftsmanship.', 299.99, 'golden-necklace.jpg', 'Necklaces', 5, 'South Sea Pearl', 'Gold Plated Sterling Silver', 'Certified Genuine • 100% Authentic', '45cm', '20g', '10-12mm', 'Avoid water and chemicals. Store in a soft pouch. Clean gently with a dry cloth.', 1),
(3, 'Pink Pearl Drop Earrings', 'Delicate pink pearl drop earrings.', 79.99, 'pink-earrings.jpg,pink-earrings-model.jpeg', 'Earrings', 15, 'Freshwater Pearl', 'Sterling Silver 925', 'Certified Genuine • 100% Authentic', '3cm', '5g', '8mm', 'Avoid water and chemicals. Store in a soft pouch. Clean gently with a dry cloth.', 1),
(4, 'Pearl Bracelet', 'Classic pearl bracelet with smooth pearl beads.', 99.99, 'pearl-bracelet.jpg,pearl-bracelet-model.png', 'Bracelets', 100, 'Genuine Freshwater Pearl', 'Sterling Silver 925', 'Certified Genuine • 100% Authentic', '20cm', '25g', '8mm', 'Avoid water and chemicals. Store in a soft pouch. Clean gently with a dry cloth.', 1),
(5, 'Pearl Ring', 'Elegant pearl ring with contemporary design.', 59.99, 'pearl-ring.jpg,pearl-ring-model.jpeg', 'Rings', 20, 'Freshwater Pearl', 'Sterling Silver 925', 'Certified Genuine • 100% Authentic', 'Adjustable', '3g', '8-9mm', 'Avoid water and chemicals. Store in a soft pouch. Clean gently with a dry cloth.', 1),
(6, 'Pearl Pendant', 'Beautiful pearl pendant with refined elegance.', 89.99, 'pearl-pendant.jpg,necklace-model.jpeg', 'Pendants', 12, 'Freshwater Pearl', 'Sterling Silver 925', 'Certified Genuine • 100% Authentic', '2cm', '8g', '10mm', 'Avoid water and chemicals. Store in a soft pouch. Clean gently with a dry cloth.', 1);

-- 5. ORDERS TABLE
CREATE TABLE IF NOT EXISTS orders (
  order_id int NOT NULL AUTO_INCREMENT,
  user_id int,
  customer_name varchar(150) NOT NULL,
  customer_email varchar(150) NOT NULL,
  customer_address longtext,
  total_amount decimal(10,2) NOT NULL,
  order_date datetime DEFAULT CURRENT_TIMESTAMP,
  status varchar(50) DEFAULT 'Pending',
  PRIMARY KEY (order_id),
  KEY user_id (user_id),
  KEY idx_status (status),
  KEY idx_order_date (order_date),
  FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO orders (order_id, user_id, customer_name, customer_email, customer_address, total_amount, order_date, status) VALUES
(1, 1, 'ayah', 'ayah@gmail.com', '25 a, Qatif', 299.99, '2026-04-17 09:36:36', 'Pending'),
(2, 1, 'ayah', 'ayah@gmail.com', '25 a, Qatif', 299.98, '2026-04-17 09:40:56', 'Shipped'),
(38, 1, 'ayah', 'ayah@gmail.com', '25 a, Qatif', 59.99, '2026-04-17 13:58:35', 'Delivered'),
(51, 1, 'ayah', 'ayah@gmail.com', '25 a, Qatif', 59.99, '2026-04-17 20:57:40', 'Pending');

-- 6. ORDER_ITEMS TABLE
CREATE TABLE IF NOT EXISTS order_items (
  item_id int NOT NULL AUTO_INCREMENT,
  order_id int NOT NULL,
  product_id int NOT NULL,
  quantity int NOT NULL,
  price decimal(10,2) NOT NULL,
  PRIMARY KEY (item_id),
  KEY order_id (order_id),
  KEY product_id (product_id),
  FOREIGN KEY (order_id) REFERENCES orders (order_id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products (product_id) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO order_items (item_id, order_id, product_id, quantity, price) VALUES
(1, 1, 2, 1, 299.99),
(2, 2, 1, 2, 149.99),
(38, 38, 5, 1, 59.99),
(51, 51, 5, 1, 59.99);

-- 7. CART TABLE
CREATE TABLE IF NOT EXISTS cart (
  cart_id int NOT NULL AUTO_INCREMENT,
  user_id int NOT NULL,
  product_id int NOT NULL,
  quantity int NOT NULL DEFAULT 1,
  added_at timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (cart_id),
  KEY user_id (user_id),
  KEY product_id (product_id),
  FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products (product_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. RATINGS TABLE
CREATE TABLE IF NOT EXISTS ratings (
  rating_id int NOT NULL AUTO_INCREMENT,
  order_id int NOT NULL,
  user_id int NOT NULL,
  product_quality int NOT NULL DEFAULT 50,
  customer_service int NOT NULL DEFAULT 50,
  comment longtext,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (rating_id),
  KEY order_id (order_id),
  KEY user_id (user_id),
  KEY idx_created_at (created_at),
  FOREIGN KEY (order_id) REFERENCES orders (order_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO ratings (rating_id, order_id, user_id, product_quality, customer_service, comment, created_at) VALUES
(15, 1, 1, 85, 88, 'Great quality product! Excellent packaging and fast delivery.', '2026-04-17 09:19:41'),
(16, 2, 1, 90, 92, 'Very satisfied with my purchase. Beautiful pearls and great customer service.', '2026-04-17 09:35:36'),
(43, 2, 1, 85, 90, 'Excellent service and product quality. Highly recommend!', '2026-04-17 17:58:08');

-- 9. ORDER_STATUS_LOG TABLE 
CREATE TABLE IF NOT EXISTS order_status_log (
  log_id int NOT NULL AUTO_INCREMENT,
  order_id int NOT NULL,
  admin_id int NOT NULL,
  old_status varchar(50) NOT NULL,
  new_status varchar(50) NOT NULL,
  changed_at timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (log_id),
  KEY order_id (order_id),
  KEY admin_id (admin_id),
  FOREIGN KEY (order_id) REFERENCES orders (order_id) ON DELETE CASCADE,
  FOREIGN KEY (admin_id) REFERENCES admins (admin_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
