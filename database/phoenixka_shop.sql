-- =====================================================
-- PhoenixKA Shop - Base de données Multi-Boutique
-- =====================================================

CREATE DATABASE IF NOT EXISTS phoenixka_shop
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE phoenixka_shop;

-- =====================================================
-- Table des plans d'abonnement
-- =====================================================
CREATE TABLE plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    currency VARCHAR(10) DEFAULT 'FCFA',
    max_products INT DEFAULT 10,
    commission_rate DECIMAL(5,2) DEFAULT 3.00,
    features JSON,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- Table des utilisateurs (propriétaires de boutiques)
-- =====================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    role ENUM('merchant','admin','super_admin') DEFAULT 'merchant',
    is_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(255) DEFAULT NULL,
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_expires DATETIME DEFAULT NULL,
    last_login DATETIME DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- Table des boutiques
-- =====================================================
CREATE TABLE stores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    logo VARCHAR(255) DEFAULT NULL,
    cover_image VARCHAR(255) DEFAULT NULL,
    primary_color VARCHAR(7) DEFAULT '#D4A520',
    secondary_color VARCHAR(7) DEFAULT '#1A1A1A',
    phone VARCHAR(20),
    whatsapp VARCHAR(20),
    wave_number VARCHAR(30) DEFAULT NULL,
    wave_link VARCHAR(255) DEFAULT NULL,
    email VARCHAR(255),
    address TEXT,
    city VARCHAR(100),
    country VARCHAR(100),
    currency VARCHAR(10) DEFAULT 'FCFA',
    is_active TINYINT(1) DEFAULT 0,
    is_featured TINYINT(1) DEFAULT 0,
    views_count INT DEFAULT 0,
    plan_id INT DEFAULT NULL,
    subscription_start DATE DEFAULT NULL,
    subscription_end DATE DEFAULT NULL,
    settings JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- Table des catégories de produits
-- =====================================================
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    store_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT,
    image VARCHAR(255) DEFAULT NULL,
    parent_id INT DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    UNIQUE KEY unique_category_slug (store_id, slug)
) ENGINE=InnoDB;

-- =====================================================
-- Table des produits
-- =====================================================
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    store_id INT NOT NULL,
    category_id INT DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    compare_price DECIMAL(10,2) DEFAULT NULL,
    cost_price DECIMAL(10,2) DEFAULT NULL,
    sku VARCHAR(100) DEFAULT NULL,
    stock INT DEFAULT 0,
    track_stock TINYINT(1) DEFAULT 1,
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    weight DECIMAL(8,2) DEFAULT NULL,
    variants JSON DEFAULT NULL,
    tags VARCHAR(500) DEFAULT NULL,
    views_count INT DEFAULT 0,
    sales_count INT DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    UNIQUE KEY unique_product_slug (store_id, slug)
) ENGINE=InnoDB;

-- =====================================================
-- Table des images de produits
-- =====================================================
CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) DEFAULT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- Table des clients (acheteurs)
-- =====================================================
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    store_id INT NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(20) NOT NULL,
    whatsapp VARCHAR(20) DEFAULT NULL,
    address TEXT,
    city VARCHAR(100),
    country VARCHAR(100),
    notes TEXT,
    orders_count INT DEFAULT 0,
    total_spent DECIMAL(12,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- Table des commandes
-- =====================================================
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    store_id INT NOT NULL,
    customer_id INT DEFAULT NULL,
    order_ref VARCHAR(20) UNIQUE NOT NULL,
    status ENUM('pending','confirmed','processing','shipped','delivered','cancelled','refunded') DEFAULT 'pending',
    payment_status ENUM('pending','paid','partial','refunded','failed') DEFAULT 'pending',
    payment_method ENUM('cash_on_delivery','mobile_money','wave','orange_money','card','bank_transfer') DEFAULT 'cash_on_delivery',
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    shipping_fee DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    customer_name VARCHAR(200),
    customer_phone VARCHAR(20),
    customer_email VARCHAR(255),
    shipping_address TEXT,
    shipping_city VARCHAR(100),
    shipping_country VARCHAR(100),
    delivery_date DATE DEFAULT NULL,
    delivery_notes TEXT,
    livreur_phone VARCHAR(20) DEFAULT NULL,
    livreur_name VARCHAR(100) DEFAULT NULL,
    promo_code_id INT DEFAULT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- Table des articles de commande
-- =====================================================
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT DEFAULT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_image VARCHAR(255) DEFAULT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    variant_info JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- Table des paiements
-- =====================================================
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    store_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'FCFA',
    method ENUM('wave','orange_money','mtn_money','moov_money','cash','card','bank_transfer') NOT NULL,
    status ENUM('pending','completed','failed','refunded') DEFAULT 'pending',
    transaction_id VARCHAR(255) DEFAULT NULL,
    payment_data JSON DEFAULT NULL,
    paid_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- Table des codes promo
-- =====================================================
CREATE TABLE promo_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    store_id INT NOT NULL,
    code VARCHAR(50) NOT NULL,
    type ENUM('percentage','fixed') DEFAULT 'percentage',
    value DECIMAL(10,2) NOT NULL,
    min_order DECIMAL(10,2) DEFAULT 0,
    max_uses INT DEFAULT NULL,
    used_count INT DEFAULT 0,
    starts_at DATETIME DEFAULT NULL,
    expires_at DATETIME DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    UNIQUE KEY unique_promo (store_id, code)
) ENGINE=InnoDB;

-- =====================================================
-- Table des avis clients
-- =====================================================
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    store_id INT NOT NULL,
    product_id INT DEFAULT NULL,
    customer_id INT DEFAULT NULL,
    order_id INT DEFAULT NULL,
    customer_name VARCHAR(200),
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    is_approved TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- Table des notifications
-- =====================================================
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    store_id INT DEFAULT NULL,
    type ENUM('order','payment','review','system','promo') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSON DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- Table des paramètres de la plateforme
-- =====================================================
CREATE TABLE platform_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string','number','boolean','json') DEFAULT 'string',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- Table de suivi des visites
-- =====================================================
CREATE TABLE store_visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    store_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    referrer VARCHAR(500),
    page_visited VARCHAR(255),
    visited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- Données initiales - Plans d'abonnement
-- =====================================================
INSERT INTO plans (name, slug, price, currency, max_products, commission_rate, features) VALUES
('Découverte', 'decouverte', 2900, 'FCFA', 10, 3.00, '{"whatsapp_notifications": false, "promo_codes": false, "ai_messages": 20, "custom_domain": false, "cover_image": false, "export_excel": false, "hide_branding": false, "priority_support": false}'),
('Business', 'business', 4900, 'FCFA', -1, 3.00, '{"whatsapp_notifications": true, "promo_codes": true, "ai_messages": 50, "custom_domain": false, "cover_image": true, "export_excel": false, "hide_branding": false, "priority_support": false}'),
('Pro', 'pro', 9900, 'FCFA', -1, 0.00, '{"whatsapp_notifications": true, "promo_codes": true, "ai_messages": -1, "custom_domain": true, "cover_image": true, "export_excel": true, "hide_branding": true, "priority_support": true}');

-- =====================================================
-- Données initiales - Paramètres plateforme
-- =====================================================
INSERT INTO platform_settings (setting_key, setting_value, setting_type) VALUES
('site_name', 'PhoenixKA Shop', 'string'),
('site_description', 'Créez votre boutique en ligne en quelques minutes', 'string'),
('contact_email', 'contact@phoenixka.shop', 'string'),
('contact_whatsapp', '+237600000000', 'string'),
('default_currency', 'FCFA', 'string'),
('maintenance_mode', '0', 'boolean');

-- =====================================================
-- Utilisateur admin par défaut
-- =====================================================
INSERT INTO users (first_name, last_name, email, phone, password_hash, role, is_verified, is_active) VALUES
('Admin', 'PhoenixKA', 'admin@phoenixka.shop', '+237600000000', '$2y$10$placeholder_hash_change_me', 'super_admin', 1, 1);

-- =====================================================
-- Index pour les performances
-- =====================================================
CREATE INDEX idx_stores_user ON stores(user_id);
CREATE INDEX idx_stores_slug ON stores(slug);
CREATE INDEX idx_stores_active ON stores(is_active);
CREATE INDEX idx_products_store ON products(store_id);
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_active ON products(is_active, store_id);
CREATE INDEX idx_orders_store ON orders(store_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_date ON orders(created_at);
CREATE INDEX idx_payments_order ON payments(order_id);
CREATE INDEX idx_notifications_user ON notifications(user_id, is_read);
CREATE INDEX idx_visits_store ON store_visits(store_id, visited_at);
