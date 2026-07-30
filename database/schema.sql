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

CREATE TABLE IF NOT EXISTS ecocart_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'customer',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_login_at TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY users_email_unique (email)
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
('School Supplies Set', 'Students', 'A complete study bundle with notebooks, pens, folders, and daily classroom essentials.', 349.00, 38, 'https://images.unsplash.com/photo-1517842645767-c639042777db?auto=format&fit=crop&w=900&q=80', 1),
('Sale Sneakers', 'Students', 'Lightweight everyday sneakers with cushioned support for school, errands, and weekends.', 899.00, 31, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=900&q=80', 1),
('Wireless Headset', 'Students', 'Comfortable wireless headset for classes, calls, and entertainment.', 1199.00, 28, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=900&q=80', 1),
('Work Safety Boots', 'Construction', 'Heavy-duty boots for the construction workers checking the sale during lunch break.', 1299.00, 16, 'https://images.unsplash.com/photo-1605812860427-4024433a70fd?auto=format&fit=crop&w=900&q=80', 1),
('Work Safety Helmet', 'Construction', 'Durable protective helmet for job-site safety and field work.', 499.00, 22, 'https://images.unsplash.com/photo-1504917595217-d4dc5ebe6122?auto=format&fit=crop&w=900&q=80', 1),
('Basic Tool Set', 'Construction', 'A compact everyday tool kit with the essentials for repairs, assembly, and site work.', 649.00, 19, 'https://images.unsplash.com/photo-1530124566582-a618bc2615dc?auto=format&fit=crop&w=900&q=80', 1),
('Motorcycle Phone Holder', 'Rider', 'A secure handlebar phone mount that keeps directions visible while riding.', 249.00, 44, 'https://images.unsplash.com/photo-1558980394-4c7c9299fe96?auto=format&fit=crop&w=900&q=80', 1),
('Motorcycle Rain Gear', 'Rider', 'Lightweight rain jacket and waterproof pouch for daily delivery rides.', 799.00, 17, 'https://images.unsplash.com/photo-1558981806-ec527fa84c39?auto=format&fit=crop&w=900&q=80', 1),
('Kalha Cooking Pot', 'Barangay', 'A sturdy stainless cooking pot for soups, stews, rice dishes, and family meals.', 399.00, 26, 'https://images.unsplash.com/photo-1556911220-bff31c812dba?auto=format&fit=crop&w=900&q=80', 1),
('Home Curtain Set', 'Barangay', 'A clean curtain set for homes, waiting-shed shoppers, and family spaces.', 299.00, 54, 'https://images.unsplash.com/photo-1583847268964-b28dc8f51f92?auto=format&fit=crop&w=900&q=80', 1),
('Baby Formula Pack', 'Family', 'A convenient formula multipack prepared for everyday feeding routines.', 699.00, 13, 'https://images.unsplash.com/photo-1522771930-78848d9293e8?auto=format&fit=crop&w=900&q=80', 1),
('Diaper Bundle', 'Family', 'Soft, absorbent diapers bundled for dependable everyday comfort and care.', 599.00, 24, 'https://images.unsplash.com/photo-1586015555751-63bb77f4322a?auto=format&fit=crop&w=900&q=80', 1),
('Baby Clothes Set', 'Family', 'Soft, breathable baby basics made for comfortable all-day wear.', 449.00, 20, 'https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?auto=format&fit=crop&w=900&q=80', 1),
('Feeding Bottles', 'Family', 'Baby feeding bottles for the mother and family essential-needs cart.', 249.00, 30, 'https://images.unsplash.com/photo-1522771930-78848d9293e8?auto=format&fit=crop&w=900&q=80', 1)
ON DUPLICATE KEY UPDATE
  category = VALUES(category),
  description = VALUES(description),
  price = VALUES(price),
  stock = VALUES(stock),
  image_url = VALUES(image_url),
  is_active = VALUES(is_active);
