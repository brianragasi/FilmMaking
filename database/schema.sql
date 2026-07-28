CREATE DATABASE IF NOT EXISTS ecocart_demo
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE ecocart_demo;

CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  category VARCHAR(60) NOT NULL,
  description TEXT NOT NULL,
  price DECIMAL(10, 2) NOT NULL,
  stock INT UNSIGNED NOT NULL DEFAULT 0,
  image_url VARCHAR(500) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY products_name_unique (name)
);

CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL,
  phone VARCHAR(40) NOT NULL,
  address TEXT NOT NULL,
  cart_json JSON NOT NULL,
  subtotal DECIMAL(10, 2) NOT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'Pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO products (name, category, description, price, stock, image_url, is_active) VALUES
('School Supplies Set', 'Students', 'Notebook set, pens, folders, and study essentials for the classmates waiting in the classroom.', 349.00, 38, 'https://images.unsplash.com/photo-1517842645767-c639042777db?auto=format&fit=crop&w=900&q=80', 1),
('Sale Sneakers', 'Students', 'Discounted shoes for the classmates racing to checkout when the sale opens.', 899.00, 31, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=900&q=80', 1),
('Wireless Headset', 'Students', 'Comfortable wireless headset for classes, calls, and entertainment.', 1199.00, 28, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=900&q=80', 1),
('Work Safety Boots', 'Construction', 'Heavy-duty boots for the construction workers checking the sale during lunch break.', 1299.00, 16, 'https://images.unsplash.com/photo-1605812860427-4024433a70fd?auto=format&fit=crop&w=900&q=80', 1),
('Work Safety Helmet', 'Construction', 'Durable protective helmet for job-site safety and field work.', 499.00, 22, 'https://images.unsplash.com/photo-1504917595217-d4dc5ebe6122?auto=format&fit=crop&w=900&q=80', 1),
('Basic Tool Set', 'Construction', 'Everyday work tools requested by the construction workers in the community sale scene.', 649.00, 19, 'https://images.unsplash.com/photo-1530124566582-a618bc2615dc?auto=format&fit=crop&w=900&q=80', 1),
('Motorcycle Phone Holder', 'Rider', 'A handlebar phone holder for the Pick Me rider checking directions while working.', 249.00, 44, 'https://images.unsplash.com/photo-1558980394-4c7c9299fe96?auto=format&fit=crop&w=900&q=80', 1),
('Motorcycle Rain Gear', 'Rider', 'Lightweight rain jacket and waterproof pouch for daily delivery rides.', 799.00, 17, 'https://images.unsplash.com/photo-1558981806-ec527fa84c39?auto=format&fit=crop&w=900&q=80', 1),
('Kalha Cooking Pot', 'Barangay', 'A sturdy cooking pot for the nanay who switches from barangay chika to the EcoCart sale.', 399.00, 26, 'https://images.unsplash.com/photo-1556911220-bff31c812dba?auto=format&fit=crop&w=900&q=80', 1),
('Home Curtain Set', 'Barangay', 'A clean curtain set for homes, waiting-shed shoppers, and family spaces.', 299.00, 54, 'https://images.unsplash.com/photo-1583847268964-b28dc8f51f92?auto=format&fit=crop&w=900&q=80', 1),
('Baby Formula Pack', 'Family', 'Essential baby formula from the mother and family scene.', 699.00, 13, 'https://images.unsplash.com/photo-1522771930-78848d9293e8?auto=format&fit=crop&w=900&q=80', 1),
('Diaper Bundle', 'Family', 'Discounted diapers for the mother trying to save money for the baby checkup.', 599.00, 24, 'https://images.unsplash.com/photo-1586015555751-63bb77f4322a?auto=format&fit=crop&w=900&q=80', 1),
('Baby Clothes Set', 'Family', 'Soft baby clothes added to the family cart before the checkout error scene.', 449.00, 20, 'https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?auto=format&fit=crop&w=900&q=80', 1),
('Feeding Bottles', 'Family', 'Baby feeding bottles for the mother and family essential-needs cart.', 249.00, 30, 'https://images.unsplash.com/photo-1522771930-78848d9293e8?auto=format&fit=crop&w=900&q=80', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);
