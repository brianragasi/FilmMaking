CREATE TABLE IF NOT EXISTS ecocart_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'customer',
  avatar_style VARCHAR(20) NOT NULL DEFAULT 'rose',
  bio VARCHAR(180) NOT NULL DEFAULT '',
  avatar_path VARCHAR(255) NULL DEFAULT NULL,
  is_banned TINYINT(1) NOT NULL DEFAULT 0,
  ban_reason VARCHAR(180) NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_login_at TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY users_email_unique (email)
);

CREATE TABLE IF NOT EXISTS product_discussions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
  body VARCHAR(1000) NOT NULL,
  is_deleted TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP NULL DEFAULT NULL,
  deleted_by VARCHAR(160) NULL DEFAULT NULL,
  KEY discussions_product_active (product_id, is_deleted, id),
  KEY discussions_user (user_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_discussion_reactions (
  discussion_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  reaction VARCHAR(16) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (discussion_id, user_id, reaction),
  KEY reactions_discussion (discussion_id, reaction),
  KEY reactions_user (user_id, discussion_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
