CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
       slug VARCHAR(64) NOT NULL UNIQUE,
       name VARCHAR(100) NOT NULL,
       description VARCHAR(400) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
       category_id INT NOT NULL,
       name VARCHAR(140) NOT NULL,
       description TEXT NOT NULL,
    FOREIGN KEY(category_id) REFERENCES categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS variants (
    id INT PRIMARY KEY AUTO_INCREMENT,
       product_id INT NOT NULL,
       sku VARCHAR(48) NOT NULL UNIQUE,
       slug VARCHAR(150) NOT NULL UNIQUE,
    option_name VARCHAR(80) NOT NULL,
       format_name VARCHAR(80) NOT NULL,
       unit VARCHAR(32) NOT NULL,
       coverage DECIMAL(8,3) NULL,
    price INT UNSIGNED NOT NULL,
       stock INT UNSIGNED NOT NULL,
       image VARCHAR(80) NOT NULL,
       visible TINYINT NOT NULL DEFAULT 1,
    FOREIGN KEY(product_id) REFERENCES products(id), INDEX(visible,price)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
       email VARCHAR(190) NOT NULL UNIQUE,
       name VARCHAR(100) NOT NULL,
       password_hash VARCHAR(255) NOT NULL,
       role VARCHAR(12) NOT NULL DEFAULT 'customer'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
       number VARCHAR(24) NOT NULL UNIQUE,
       request_token CHAR(48) NOT NULL UNIQUE,
    user_id INT NULL,
       name VARCHAR(100) NOT NULL,
       phone VARCHAR(32) NOT NULL,
       total BIGINT UNSIGNED NOT NULL,
       status VARCHAR(30) NOT NULL DEFAULT 'Новый',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
       order_id INT NOT NULL,
       variant_id INT NOT NULL,
       sku VARCHAR(48) NOT NULL,
    name VARCHAR(240) NOT NULL,
       unit VARCHAR(32) NOT NULL,
       price INT UNSIGNED NOT NULL,
       quantity INT UNSIGNED NOT NULL,
    FOREIGN KEY(order_id) REFERENCES orders(id), FOREIGN KEY(variant_id) REFERENCES variants(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
