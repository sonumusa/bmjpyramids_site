-- BMJ Pyramids LLP production-ready MySQL schema
-- Import this file in phpMyAdmin after creating the database.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+05:00";
SET NAMES utf8mb4;

DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS product_images;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS seo_settings;
DROP TABLE IF EXISTS settings;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('super_admin','manager','viewer') NOT NULL DEFAULT 'viewer',
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  last_login_at DATETIME NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(140) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  phone VARCHAR(60) NULL,
  company_name VARCHAR(190) NULL,
  address TEXT NULL,
  city VARCHAR(120) NULL,
  gst_no VARCHAR(120) NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_id INT UNSIGNED NULL,
  name VARCHAR(160) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  description TEXT NULL,
  image VARCHAR(255) NULL,
  meta_title VARCHAR(190) NULL,
  meta_description VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  INDEX idx_parent (parent_id),
  CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NULL,
  sku VARCHAR(120) NULL,
  name VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  short_description VARCHAR(500) NULL,
  description MEDIUMTEXT NULL,
  price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  gst_rate DECIMAL(5,2) NOT NULL DEFAULT 18.00,
  stock_quantity INT NOT NULL DEFAULT 0,
  low_stock_threshold INT NOT NULL DEFAULT 5,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  featured TINYINT(1) NOT NULL DEFAULT 0,
  meta_title VARCHAR(190) NULL,
  meta_description VARCHAR(255) NULL,
  keywords VARCHAR(255) NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  INDEX idx_category (category_id),
  INDEX idx_sku (sku),
  INDEX idx_status_featured (status, featured),
  CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  CONSTRAINT fk_products_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  alt_text VARCHAR(190) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_product (product_id),
  CONSTRAINT fk_product_images_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_number VARCHAR(40) NOT NULL UNIQUE,
  customer_id INT UNSIGNED NULL,
  customer_name VARCHAR(160) NOT NULL,
  company_name VARCHAR(190) NULL,
  customer_email VARCHAR(190) NOT NULL,
  customer_phone VARCHAR(80) NOT NULL,
  shipping_address TEXT NOT NULL,
  city VARCHAR(120) NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  gst_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  grand_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  status ENUM('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending',
  notes TEXT NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  INDEX idx_customer (customer_id),
  INDEX idx_status (status),
  CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NULL,
  product_name VARCHAR(190) NOT NULL,
  sku VARCHAR(120) NULL,
  unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  qty INT NOT NULL DEFAULT 1,
  gst_rate DECIMAL(5,2) NOT NULL DEFAULT 18.00,
  line_subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  gst_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  line_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  INDEX idx_order (order_id),
  INDEX idx_product (product_id),
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE seo_settings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  page_key VARCHAR(80) NOT NULL UNIQUE,
  title VARCHAR(190) NULL,
  meta_description VARCHAR(255) NULL,
  keywords VARCHAR(255) NULL,
  og_image VARCHAR(255) NULL,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(120) NOT NULL UNIQUE,
  setting_value TEXT NULL,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contact_messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(140) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(80) NULL,
  company VARCHAR(190) NULL,
  subject VARCHAR(190) NULL,
  message TEXT NOT NULL,
  created_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demo admin users. Change or delete these before going live.
-- Super Admin: admin@bmj.com / admin123
-- Manager:     manager@bmj.com / manager123
-- Viewer:      viewer@bmj.com / viewer123
INSERT INTO users (name, email, password_hash, role, status, created_at, updated_at) VALUES
('Super Admin', 'admin@bmj.com', '$2y$10$mwgCrDBV7BrK6sByc4t6QuuAuE7VpnBXiXirRRjgTUdUQj0CQeXoO', 'super_admin', 'active', NOW(), NOW()),
('Demo Manager', 'manager@bmj.com', '$2y$10$GKQeNVFX/dVn15TJiudBRedAolx0nOpf.wdp/.vrVf9ZI0kAv4xPu', 'manager', 'active', NOW(), NOW()),
('Demo Viewer', 'viewer@bmj.com', '$2y$10$CgVddSo7ihdDTHyinoZ9ou.FvP2mwiY1B.WRz65R/aJ617.kcA5gG', 'viewer', 'active', NOW(), NOW());

INSERT INTO categories (id, parent_id, name, slug, description, meta_title, meta_description, sort_order, status, created_at, updated_at) VALUES
(1, NULL, 'Industrial Tools', 'industrial-tools', 'Professional industrial tools and workshop supplies.', 'Industrial Tools Supplier | BMJ Pyramids LLP', 'Browse industrial tools and workshop supplies from BMJ Pyramids LLP.', 1, 'active', NOW(), NOW()),
(2, 1, 'Bearing', 'bearing', 'Bearing products for industrial machinery and maintenance.', 'Industrial Bearings | BMJ Pyramids LLP', 'Request quotes for industrial bearing products.', 1, 'active', NOW(), NOW()),
(3, 2, 'Ball Bearing', 'ball-bearing', 'Deep groove and standard ball bearing products.', 'Ball Bearing Supplier | BMJ Pyramids LLP', 'Browse ball bearings with live stock availability.', 1, 'active', NOW(), NOW()),
(4, 2, 'Roller Bearing', 'roller-bearing', 'Roller bearing products for heavy-duty applications.', 'Roller Bearing Supplier | BMJ Pyramids LLP', 'Browse roller bearings and request quotes.', 2, 'active', NOW(), NOW()),
(5, 1, 'Abrasives', 'abrasives', 'Grinding, cutting and finishing abrasives.', 'Abrasives Supplier | BMJ Pyramids LLP', 'Industrial abrasive products for cutting and grinding.', 2, 'active', NOW(), NOW()),
(6, 5, 'Grinding Discs', 'grinding-discs', 'Grinding discs for metal and industrial use.', 'Grinding Discs | BMJ Pyramids LLP', 'Request quotes for grinding discs.', 1, 'active', NOW(), NOW()),
(7, 5, 'Cutting Discs', 'cutting-discs', 'Cutting discs for steel, metal and workshop use.', 'Cutting Discs | BMJ Pyramids LLP', 'Browse cutting discs and request B2B quotes.', 2, 'active', NOW(), NOW()),
(8, NULL, 'Hand Tools', 'hand-tools', 'Manual tools for workshop and maintenance teams.', 'Hand Tools Supplier | BMJ Pyramids LLP', 'Browse hand tools for industrial procurement.', 2, 'active', NOW(), NOW()),
(9, NULL, 'Safety Equipment', 'safety-equipment', 'Safety gear and PPE for industrial environments.', 'Safety Equipment Supplier | BMJ Pyramids LLP', 'Browse safety equipment and PPE.', 3, 'active', NOW(), NOW());

INSERT INTO products (category_id, sku, name, slug, short_description, description, price, gst_rate, stock_quantity, low_stock_threshold, status, featured, meta_title, meta_description, keywords, created_by, created_at, updated_at) VALUES
(3, 'BRG-6204', 'Deep Groove Ball Bearing 6204', 'deep-groove-ball-bearing-6204', 'Standard 6204 deep groove ball bearing for machinery maintenance.', '<p>Reliable deep groove ball bearing suitable for common industrial machinery applications.</p><ul><li>Standard size: 6204</li><li>Quote available for bulk quantity</li><li>Check compatibility before ordering</li></ul>', 850.00, 18.00, 50, 8, 'active', 1, 'Deep Groove Ball Bearing 6204 | BMJ Pyramids LLP', 'Request quote for 6204 deep groove ball bearing with live stock availability.', '6204 bearing, ball bearing, industrial bearing', 1, NOW(), NOW()),
(3, 'BRG-6205', 'Deep Groove Ball Bearing 6205', 'deep-groove-ball-bearing-6205', 'Industrial 6205 ball bearing for maintenance and replacement needs.', '<p>Deep groove ball bearing 6205 for industrial replacement and maintenance.</p>', 920.00, 18.00, 35, 8, 'active', 1, 'Deep Groove Ball Bearing 6205 | BMJ Pyramids LLP', 'Request quote for 6205 ball bearing from BMJ Pyramids LLP.', '6205 bearing, ball bearing', 1, NOW(), NOW()),
(4, 'BRG-TR-30205', 'Taper Roller Bearing 30205', 'taper-roller-bearing-30205', 'Taper roller bearing for heavy load applications.', '<p>Taper roller bearing 30205 designed for radial and axial load applications.</p>', 1450.00, 18.00, 22, 5, 'active', 1, 'Taper Roller Bearing 30205 | BMJ Pyramids LLP', 'Request quote for taper roller bearing 30205.', 'taper roller bearing, 30205', 1, NOW(), NOW()),
(6, 'ABR-GD-4', 'Grinding Disc 4 Inch Metal', 'grinding-disc-4-inch-metal', '4 inch grinding disc for metal fabrication and workshop use.', '<p>Industrial grinding disc for metal grinding, finishing and maintenance tasks.</p>', 180.00, 18.00, 120, 20, 'active', 1, 'Grinding Disc 4 Inch Metal | BMJ Pyramids LLP', 'Browse 4 inch metal grinding discs and request bulk quote.', 'grinding disc, abrasive disc', 1, NOW(), NOW()),
(6, 'ABR-GD-7', 'Grinding Disc 7 Inch Heavy Duty', 'grinding-disc-7-inch-heavy-duty', 'Heavy-duty 7 inch grinding disc for industrial users.', '<p>Suitable for demanding grinding work. Contact for brand and specification options.</p>', 420.00, 18.00, 80, 15, 'active', 1, '7 Inch Grinding Disc Heavy Duty | BMJ Pyramids LLP', 'Request quote for heavy-duty 7 inch grinding discs.', '7 inch grinding disc, heavy duty abrasive', 1, NOW(), NOW()),
(7, 'ABR-CD-14', 'Cutting Disc 14 Inch Steel', 'cutting-disc-14-inch-steel', '14 inch steel cutting disc for fabrication and industrial jobs.', '<p>Cutting disc for steel and metal fabrication. Bulk quote available.</p>', 650.00, 18.00, 60, 10, 'active', 1, '14 Inch Steel Cutting Disc | BMJ Pyramids LLP', 'Request B2B quote for 14 inch steel cutting discs.', 'cutting disc, steel cutting', 1, NOW(), NOW()),
(7, 'ABR-CD-4', 'Cutting Disc 4 Inch Metal', 'cutting-disc-4-inch-metal', 'Compact 4 inch cutting disc for workshop cutting tasks.', '<p>Metal cutting disc for small grinders and workshop maintenance.</p>', 160.00, 18.00, 200, 25, 'active', 0, '4 Inch Metal Cutting Disc | BMJ Pyramids LLP', 'Request quote for 4 inch metal cutting discs.', '4 inch cutting disc, metal cutting', 1, NOW(), NOW()),
(8, 'HT-SP-SET', 'Combination Spanner Set', 'combination-spanner-set', 'Combination spanner set for workshop maintenance teams.', '<p>Durable hand tool set suitable for maintenance and mechanical work.</p>', 3200.00, 18.00, 15, 4, 'active', 1, 'Combination Spanner Set | BMJ Pyramids LLP', 'Request quote for combination spanner set.', 'spanner set, hand tools', 1, NOW(), NOW()),
(8, 'HT-PL-CMB', 'Combination Pliers', 'combination-pliers', 'General-purpose combination pliers for workshop use.', '<p>Combination pliers for gripping, bending and cutting tasks.</p>', 750.00, 18.00, 40, 8, 'active', 0, 'Combination Pliers | BMJ Pyramids LLP', 'Request quote for combination pliers.', 'pliers, hand tools', 1, NOW(), NOW()),
(9, 'PPE-GLV-NIT', 'Nitrile Coated Safety Gloves', 'nitrile-coated-safety-gloves', 'Safety gloves for industrial handling and maintenance.', '<p>Nitrile coated gloves for better grip and hand protection.</p>', 280.00, 18.00, 300, 50, 'active', 1, 'Nitrile Safety Gloves | BMJ Pyramids LLP', 'Request quote for nitrile coated industrial safety gloves.', 'safety gloves, PPE', 1, NOW(), NOW()),
(9, 'PPE-SG-CLR', 'Clear Safety Goggles', 'clear-safety-goggles', 'Protective clear safety goggles for industrial work.', '<p>Clear protective goggles suitable for workshop and site use.</p>', 520.00, 18.00, 90, 15, 'active', 0, 'Clear Safety Goggles | BMJ Pyramids LLP', 'Request quote for industrial clear safety goggles.', 'safety goggles, PPE', 1, NOW(), NOW()),
(1, 'IND-LUB-1L', 'Industrial Lubricant 1L', 'industrial-lubricant-1l', 'General industrial lubricant for maintenance applications.', '<p>Industrial lubricant for routine maintenance and machine care.</p>', 1100.00, 18.00, 25, 6, 'active', 0, 'Industrial Lubricant 1L | BMJ Pyramids LLP', 'Request quote for industrial lubricant 1L.', 'industrial lubricant, maintenance', 1, NOW(), NOW());

-- Demo product images for visual understanding. These SVG files are included in uploads/products/.
INSERT INTO product_images (product_id, image_path, alt_text, sort_order, is_primary) VALUES
(1, 'products/demo-bearing.svg', 'Deep Groove Ball Bearing 6204 demo image', 1, 1),
(1, 'products/demo-bearing-alt.svg', 'Deep Groove Ball Bearing 6204 alternate demo image', 2, 0),
(2, 'products/demo-bearing.svg', 'Deep Groove Ball Bearing 6205 demo image', 1, 1),
(3, 'products/demo-bearing.svg', 'Taper Roller Bearing 30205 demo image', 1, 1),
(4, 'products/demo-grinding-disc.svg', 'Grinding Disc 4 Inch Metal demo image', 1, 1),
(5, 'products/demo-grinding-disc.svg', 'Grinding Disc 7 Inch Heavy Duty demo image', 1, 1),
(6, 'products/demo-cutting-disc.svg', 'Cutting Disc 14 Inch Steel demo image', 1, 1),
(7, 'products/demo-cutting-disc.svg', 'Cutting Disc 4 Inch Metal demo image', 1, 1),
(8, 'products/demo-hand-tool.svg', 'Combination Spanner Set demo image', 1, 1),
(9, 'products/demo-hand-tool.svg', 'Combination Pliers demo image', 1, 1),
(10, 'products/demo-safety.svg', 'Nitrile Coated Safety Gloves demo image', 1, 1),
(11, 'products/demo-safety.svg', 'Clear Safety Goggles demo image', 1, 1),
(12, 'products/demo-lubricant.svg', 'Industrial Lubricant 1L demo image', 1, 1);

-- Demo customer account. Login: customer@bmj.com / customer123
INSERT INTO customers (id, name, email, phone, company_name, address, city, gst_no, password_hash, created_at, updated_at) VALUES
(1, 'Ali Khan', 'customer@bmj.com', '+92 300 1111111', 'Demo Engineering Works', 'Plot 12, Industrial Area, Demo Road', 'Karachi', 'GST-DEMO-001', '$2y$10$I7p7hqtaoP30up2NApINw.itKcULx.o.JSkmT8WbGio52bI7Y18Om', NOW(), NOW());

-- Demo quote orders so the admin dashboard and order screens are populated immediately.
INSERT INTO orders (id, order_number, customer_id, customer_name, company_name, customer_email, customer_phone, shipping_address, city, subtotal, gst_amount, grand_total, status, notes, created_at, updated_at) VALUES
(1, 'BMJ-DEMO-1001', 1, 'Ali Khan', 'Demo Engineering Works', 'customer@bmj.com', '+92 300 1111111', 'Plot 12, Industrial Area, Demo Road', 'Karachi', 7900.00, 1422.00, 9322.00, 'pending', 'Demo pending quote request: customer needs urgent delivery confirmation.', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 'BMJ-DEMO-1002', 1, 'Ali Khan', 'Demo Engineering Works', 'customer@bmj.com', '+92 300 1111111', 'Plot 12, Industrial Area, Demo Road', 'Karachi', 17200.00, 3096.00, 20296.00, 'completed', 'Demo completed order for understanding order history and status workflow.', DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY));

INSERT INTO order_items (order_id, product_id, product_name, sku, unit_price, qty, gst_rate, line_subtotal, gst_amount, line_total) VALUES
(1, 1, 'Deep Groove Ball Bearing 6204', 'BRG-6204', 850.00, 4, 18.00, 3400.00, 612.00, 4012.00),
(1, 4, 'Grinding Disc 4 Inch Metal', 'ABR-GD-4', 180.00, 25, 18.00, 4500.00, 810.00, 5310.00),
(2, 8, 'Combination Spanner Set', 'HT-SP-SET', 3200.00, 1, 18.00, 3200.00, 576.00, 3776.00),
(2, 10, 'Nitrile Coated Safety Gloves', 'PPE-GLV-NIT', 280.00, 50, 18.00, 14000.00, 2520.00, 16520.00);

-- Demonstrate live stock deduction caused by the demo orders above.
UPDATE products SET stock_quantity = stock_quantity - 4 WHERE id = 1;
UPDATE products SET stock_quantity = stock_quantity - 25 WHERE id = 4;
UPDATE products SET stock_quantity = stock_quantity - 1 WHERE id = 8;
UPDATE products SET stock_quantity = stock_quantity - 50 WHERE id = 10;

INSERT INTO contact_messages (name, email, phone, company, subject, message, created_at) VALUES
('Sara Procurement', 'procurement@example.com', '+92 300 2222222', 'Sample Factory Pvt Ltd', 'Bulk quote for bearings and abrasives', 'This is a demo contact inquiry. Please quote 6204 bearings and grinding discs in bulk quantity.', DATE_SUB(NOW(), INTERVAL 1 DAY));

INSERT INTO seo_settings (page_key, title, meta_description, keywords, og_image, updated_at) VALUES
('home', 'BMJ Pyramids LLP | Industrial Tools, Bearings & B2B Supplier', 'Professional industrial tools, bearings and B2B supply solutions with quote request workflow and live stock tracking.', 'industrial tools, bearings, B2B supplier, BMJ Pyramids', '', NOW()),
('shop', 'Shop Industrial Products | BMJ Pyramids LLP', 'Browse industrial tools, bearings, abrasives and safety products with live stock and GST quote requests.', 'industrial products, bearings, grinding discs, cutting discs', '', NOW()),
('about', 'About BMJ Pyramids LLP | Industrial B2B Supplier', 'Learn about BMJ Pyramids LLP and our industrial tools and bearings supply capabilities.', 'about BMJ Pyramids, industrial supplier', '', NOW()),
('contact', 'Contact BMJ Pyramids LLP | Request Industrial Quote', 'Contact BMJ Pyramids LLP for product availability, bulk pricing and industrial procurement quotes.', 'contact BMJ Pyramids, quote request', '', NOW()),
('product_default', 'Industrial Product | BMJ Pyramids LLP', 'Request a quote for industrial products from BMJ Pyramids LLP.', 'industrial product, quote', '', NOW()),
('category_default', 'Industrial Category | BMJ Pyramids LLP', 'Browse industrial products by category from BMJ Pyramids LLP.', 'industrial category', '', NOW());

INSERT INTO settings (setting_key, setting_value) VALUES
('company_phone', '+92 300 0000000'),
('company_email', 'info@bmjpyramids.com'),
('company_address', 'Pakistan'),
('whatsapp', '+92 300 0000000'),
('years_in_business', '10+');
