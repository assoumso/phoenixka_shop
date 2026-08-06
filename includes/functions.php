<?php
/**
 * PhoenixKA Shop - Fonctions utilitaires
 */

require_once __DIR__ . '/config.php';

// =====================================================
// Fonctions de sécurité
// =====================================================

function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function generateToken($length = 64) {
    return bin2hex(random_bytes($length / 2));
}

function generateOrderRef($prefix = 'PK') {
    return $prefix . '-' . strtoupper(substr(uniqid(), -4)) . rand(10, 99);
}

function generateSlug($string) {
    $slug = strtolower(trim($string));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

function uniqueSlug($table, $slug, $excludeId = null) {
    $db = getDB();
    $originalSlug = $slug;
    $counter = 1;
    
    while (true) {
        $sql = "SELECT id FROM {$table} WHERE slug = ?";
        $params = [$slug];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        if (!$stmt->fetch()) {
            return $slug;
        }
        
        $slug = $originalSlug . '-' . $counter;
        $counter++;
    }
}

function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken(32);
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// =====================================================
// Fonctions d'authentification
// =====================================================

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/auth/login');
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function getCurrentStore() {
    if (!isLoggedIn()) return null;
    
    $db = getDB();
    $stmt = $db->prepare("SELECT s.*, p.name as plan_name, p.max_products, p.commission_rate FROM stores s LEFT JOIN plans p ON s.plan_id = p.id WHERE s.user_id = ? ORDER BY s.created_at DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function getUserStores($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT s.*, p.name as plan_name FROM stores s LEFT JOIN plans p ON s.plan_id = p.id WHERE s.user_id = ? ORDER BY s.created_at DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function loginUser($email, $password) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        
        // Mise à jour last login
        $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        return $user;
    }
    
    return false;
}

function registerUser($data) {
    $db = getDB();
    
    // Vérifier si email existe
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Cet email est déjà utilisé.'];
    }
    
    $hash = password_hash($data['password'], PASSWORD_DEFAULT);
    $token = generateToken();
    
    $stmt = $db->prepare("INSERT INTO users (first_name, last_name, email, phone, password_hash, verification_token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        sanitize($data['first_name']),
        sanitize($data['last_name']),
        sanitize($data['email']),
        sanitize($data['phone'] ?? ''),
        $hash,
        $token
    ]);
    
    $userId = $db->lastInsertId();
    
    // Auto-login
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_role'] = 'merchant';
    $_SESSION['user_name'] = $data['first_name'] . ' ' . $data['last_name'];
    
    return ['success' => true, 'user_id' => $userId];
}

function logoutUser() {
    session_destroy();
    header('Location: ' . SITE_URL);
    exit;
}

// =====================================================
// Fonctions boutique
// =====================================================

function createStore($data) {
    $db = getDB();
    
    $slug = uniqueSlug('stores', generateSlug($data['name']));
    
    $stmt = $db->prepare("INSERT INTO stores (user_id, name, slug, description, phone, whatsapp, wave_number, wave_link, city, country, primary_color, plan_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['user_id'],
        sanitize($data['name']),
        $slug,
        sanitize($data['description'] ?? ''),
        sanitize($data['phone'] ?? ''),
        sanitize($data['whatsapp'] ?? ''),
        sanitize($data['wave_number'] ?? '+225 0141591150'),
        sanitize($data['wave_link'] ?? ''),
        sanitize($data['city'] ?? ''),
        sanitize($data['country'] ?? ''),
        sanitize($data['primary_color'] ?? '#D4A520'),
        $data['plan_id'] ?? 1
    ]);
    
    return $db->lastInsertId();
}

function getStoreBySlug($slug) {
    $db = getDB();
    $stmt = $db->prepare("SELECT s.*, u.first_name as owner_name, p.name as plan_name FROM stores s JOIN users u ON s.user_id = u.id LEFT JOIN plans p ON s.plan_id = p.id WHERE s.slug = ? OR s.id = ?");
    $stmt->execute([$slug, is_numeric($slug) ? $slug : 0]);
    return $stmt->fetch();
}

function getStoreUrl($storeOrSlug, $subdomainMode = false) {
    $slug = is_array($storeOrSlug) ? ($storeOrSlug['slug'] ?? '') : $storeOrSlug;
    if ($subdomainMode) {
        return "https://{$slug}.phoenixka.shop";
    }
    return SITE_URL . '/' . $slug;
}

function getStoreProducts($storeId, $categoryId = null, $limit = null, $featured = false) {
    $db = getDB();
    $sql = "SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image FROM products p WHERE p.store_id = ? AND p.is_active = 1";
    $params = [$storeId];
    
    if ($categoryId) {
        $sql .= " AND p.category_id = ?";
        $params[] = $categoryId;
    }
    
    if ($featured) {
        $sql .= " AND p.is_featured = 1";
    }
    
    $sql .= " ORDER BY p.sort_order ASC, p.created_at DESC";
    
    if ($limit) {
    $sql .= " LIMIT " . (int)$limit;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function checkDatabaseMigrations() {
    static $migrated = false;
    if ($migrated) return;
    $migrated = true;
    
    $db = getDB();
    
    // Add product_type, digital_file, digital_link, license_keys to products
    try {
        $cols = $db->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('product_type', $cols)) {
            $db->exec("ALTER TABLE products ADD COLUMN product_type ENUM('physical', 'digital', 'license') DEFAULT 'physical' AFTER category_id");
        }
        if (!in_array('digital_file', $cols)) {
            $db->exec("ALTER TABLE products ADD COLUMN digital_file VARCHAR(255) NULL AFTER product_type");
        }
        if (!in_array('digital_link', $cols)) {
            $db->exec("ALTER TABLE products ADD COLUMN digital_link VARCHAR(255) NULL AFTER digital_file");
        }
        if (!in_array('license_keys', $cols)) {
            $db->exec("ALTER TABLE products ADD COLUMN license_keys TEXT NULL AFTER digital_link");
        }
    } catch (Exception $e) {}

    // Add download_token, license_code, download_count to order_items
    try {
        $itemCols = $db->query("SHOW COLUMNS FROM order_items")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('download_token', $itemCols)) {
            $db->exec("ALTER TABLE order_items ADD COLUMN download_token VARCHAR(64) NULL AFTER total_price");
        }
        if (!in_array('license_code', $itemCols)) {
            $db->exec("ALTER TABLE order_items ADD COLUMN license_code VARCHAR(255) NULL AFTER download_token");
        }
        if (!in_array('download_count', $itemCols)) {
            $db->exec("ALTER TABLE order_items ADD COLUMN download_count INT DEFAULT 0 AFTER license_code");
        }
    } catch (Exception $e) {}

    // Add payment numbers & payout mode columns to stores
    try {
        $storeCols = $db->query("SHOW COLUMNS FROM stores")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('wave_number_sandbox', $storeCols)) {
            $db->exec("ALTER TABLE stores ADD COLUMN wave_number_sandbox VARCHAR(50) NULL AFTER wave_number");
        }
        if (!in_array('wave_number_live', $storeCols)) {
            $db->exec("ALTER TABLE stores ADD COLUMN wave_number_live VARCHAR(50) NULL AFTER wave_number_sandbox");
        }
        if (!in_array('payment_environment', $storeCols)) {
            $db->exec("ALTER TABLE stores ADD COLUMN payment_environment ENUM('sandbox', 'live') DEFAULT 'sandbox' AFTER wave_number_live");
        }
        if (!in_array('payout_mode', $storeCols)) {
            $db->exec("ALTER TABLE stores ADD COLUMN payout_mode ENUM('auto', 'on_demand') DEFAULT 'auto' AFTER payment_environment");
        }
        if (!in_array('virtual_wallet', $storeCols)) {
            $db->exec("ALTER TABLE stores ADD COLUMN virtual_wallet DECIMAL(12,2) DEFAULT 0.00 AFTER payout_mode");
        }
    } catch (Exception $e) {}

    // Create payout_requests table
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS payout_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            store_id INT NOT NULL,
            order_id INT NULL,
            amount_gross DECIMAL(12,2) NOT NULL,
            fee_amount DECIMAL(12,2) NOT NULL,
            amount_net DECIMAL(12,2) NOT NULL,
            payment_method VARCHAR(50) DEFAULT 'wave',
            payment_number VARCHAR(50) NOT NULL,
            payout_type ENUM('auto', 'on_demand') DEFAULT 'auto',
            status ENUM('pending', 'approved', 'rejected', 'paid') DEFAULT 'pending',
            admin_notes TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_payout_store (store_id),
            INDEX idx_payout_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) {}

    // Create notifications table
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            store_id INT NULL,
            user_id INT NULL,
            target ENUM('merchant', 'admin', 'all') DEFAULT 'merchant',
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_notif_target (target),
            INDEX idx_notif_store (store_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) {}
}

// =====================================================
// Helper Payment Numbers & Payout Workflow Functions
// =====================================================

function getActivePaymentNumber($store) {
    $env = $store['payment_environment'] ?? 'sandbox';
    if ($env === 'live') {
        return !empty($store['wave_number_live']) ? $store['wave_number_live'] : ($store['wave_number'] ?? '');
    }
    return !empty($store['wave_number_sandbox']) ? $store['wave_number_sandbox'] : ($store['wave_number'] ?? '');
}

function addNotification($target, $storeId, $userId, $type, $title, $message) {
    checkDatabaseMigrations();
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO notifications (target, store_id, user_id, type, title, message) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$target, $storeId ?: null, $userId ?: null, $type, $title, $message]);
}

function getStoreNotifications($storeId, $limit = 10) {
    checkDatabaseMigrations();
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM notifications WHERE target IN ('merchant', 'all') AND (store_id = ? OR store_id IS NULL) ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$storeId, (int)$limit]);
    return $stmt->fetchAll();
}

function getAdminNotifications($limit = 10) {
    checkDatabaseMigrations();
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM notifications WHERE target IN ('admin', 'all') ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([(int)$limit]);
    return $stmt->fetchAll();
}

function processOrderPayoutWorkflow($orderId) {
    checkDatabaseMigrations();
    $db = getDB();
    
    $stmt = $db->prepare("SELECT o.*, s.name as store_name, s.user_id, s.payout_mode, s.virtual_wallet, s.wave_number, s.wave_number_sandbox, s.wave_number_live, s.payment_environment FROM orders o JOIN stores s ON o.store_id = s.id WHERE o.id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order) return;

    $gross = floatval($order['total']);
    // Commission PHENIXKA (2.5%) + Frais opérateur (1%) = 3.5%
    $commissionRate = 0.035; 
    $fee = round($gross * $commissionRate, 2);
    $net = round($gross - $fee, 2);
    $paymentNumber = getActivePaymentNumber($order);
    
    $mode = $order['payout_mode'] ?? 'auto';

    if ($mode === 'auto') {
        // Option 1 : Paiement Automatique
        $stmtPayout = $db->prepare("INSERT INTO payout_requests (store_id, order_id, amount_gross, fee_amount, amount_net, payment_method, payment_number, payout_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'auto', 'pending')");
        $stmtPayout->execute([$order['store_id'], $order['id'], $gross, $fee, $net, $order['payment_method'], $paymentNumber]);
        
        addNotification('merchant', $order['store_id'], $order['user_id'], 'payout_auto_created', 
            '⚡ Décaissement automatique en cours', 
            "La commande {$order['order_ref']} de " . formatPrice($gross) . " a déclenché un décaissement automatique de " . formatPrice($net) . " (frais déduits: " . formatPrice($fee) . "). Transfert vers votre numéro $paymentNumber."
        );

        addNotification('admin', $order['store_id'], null, 'payout_auto_pending', 
            '🔔 Décaissement automatique à valider', 
            "Commande {$order['order_ref']} de la boutique '{$order['store_name']}'. Décaissement net à envoyer: " . formatPrice($net) . " vers $paymentNumber."
        );
    } else {
        // Option 2 : Paiement Sur Demande (Solde Virtuel)
        $db->prepare("UPDATE stores SET virtual_wallet = virtual_wallet + ? WHERE id = ?")->execute([$net, $order['store_id']]);
        
        addNotification('merchant', $order['store_id'], $order['user_id'], 'wallet_credited', 
            '💰 Solde virtuel crédité (' . formatPrice($net) . ')', 
            "La commande {$order['order_ref']} a été encaissée par PHENIXKA. Solde net ajouté à votre portefeuille : " . formatPrice($net) . "."
        );

        addNotification('admin', $order['store_id'], null, 'payment_received', 
            '💳 Paiement récepteur encaissé (' . $order['store_name'] . ')', 
            "Commande {$order['order_ref']} de " . formatPrice($gross) . " encaissée par PHENIXKA. Montant crédité sur le portefeuille virtuel du marchand."
        );
    }
}


function getStoreCategories($storeId) {
    checkDatabaseMigrations();
    $db = getDB();
    $stmt = $db->prepare("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id AND is_active = 1) as product_count FROM categories c WHERE c.store_id = ? AND c.is_active = 1 ORDER BY c.sort_order ASC, c.name ASC");
    $stmt->execute([$storeId]);
    return $stmt->fetchAll();
}

// =====================================================
// Fonctions produits
// =====================================================

function createProduct($data) {
    checkDatabaseMigrations();
    $db = getDB();
    $slug = uniqueSlug('products', generateSlug($data['name']));
    
    $stmt = $db->prepare("INSERT INTO products (store_id, category_id, product_type, digital_file, digital_link, license_keys, name, slug, description, price, compare_price, stock, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['store_id'],
        $data['category_id'] ?: null,
        $data['product_type'] ?? 'physical',
        $data['digital_file'] ?? null,
        $data['digital_link'] ?? null,
        $data['license_keys'] ?? null,
        sanitize($data['name']),
        $slug,
        $data['description'] ?? '',
        $data['price'],
        $data['compare_price'] ?: null,
        $data['stock'] ?? 0,
        $data['is_featured'] ?? 0
    ]);
    
    return $db->lastInsertId();
}

function addProductImage($productId, $imagePath, $isPrimary = false) {
    $db = getDB();
    
    if ($isPrimary) {
        $db->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?")->execute([$productId]);
    }
    
    $stmt = $db->prepare("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, ?)");
    $stmt->execute([$productId, $imagePath, $isPrimary ? 1 : 0]);
    return $db->lastInsertId();
}

// =====================================================
// Fonctions commandes
// =====================================================

function createOrder($data) {
    checkDatabaseMigrations();
    $db = getDB();
    $ref = generateOrderRef();
    
    $stmt = $db->prepare("INSERT INTO orders (store_id, customer_id, order_ref, payment_method, subtotal, shipping_fee, discount_amount, total, customer_name, customer_phone, customer_email, shipping_address, shipping_city, delivery_date, delivery_notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['store_id'],
        $data['customer_id'] ?? null,
        $ref,
        $data['payment_method'] ?? 'cash_on_delivery',
        $data['subtotal'],
        $data['shipping_fee'] ?? 0,
        $data['discount_amount'] ?? 0,
        $data['total'],
        sanitize($data['customer_name']),
        sanitize($data['customer_phone']),
        sanitize($data['customer_email'] ?? ''),
        sanitize($data['shipping_address'] ?? ''),
        sanitize($data['shipping_city'] ?? ''),
        $data['delivery_date'] ?? null,
        sanitize($data['delivery_notes'] ?? '')
    ]);
    
    $orderId = $db->lastInsertId();
    processOrderPayoutWorkflow($orderId);
    
    return ['order_id' => $orderId, 'order_ref' => $ref];
}

function addOrderItem($orderId, $productId, $quantity, $unitPrice) {
    checkDatabaseMigrations();
    $db = getDB();
    
    // Récupérer les infos du produit
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    $stmt = $db->prepare("SELECT image_path FROM product_images WHERE product_id = ? AND is_primary = 1 LIMIT 1");
    $stmt->execute([$productId]);
    $img = $stmt->fetch();
    
    $total = $quantity * $unitPrice;

    $downloadToken = null;
    $licenseCode = null;

    if ($product) {
        if ($product['product_type'] === 'digital') {
            $downloadToken = generateToken(32);
        } else if ($product['product_type'] === 'license') {
            $downloadToken = generateToken(32);
            // Attribution d'une clé de licence si disponible
            if (!empty($product['license_keys'])) {
                $keys = array_filter(array_map('trim', explode("\n", $product['license_keys'])));
                if (!empty($keys)) {
                    $licenseCode = array_shift($keys); // Prend la première clé disponible
                    // Met à jour la liste restante
                    $remaining = implode("\n", $keys);
                    $db->prepare("UPDATE products SET license_keys = ? WHERE id = ?")->execute([$remaining, $productId]);
                }
            }
            if (!$licenseCode) {
                $licenseCode = 'PHX-' . strtoupper(substr(md5(uniqid()), 0, 4)) . '-' . strtoupper(substr(md5(uniqid()), 4, 4)) . '-' . rand(1000, 9999);
            }
        }
    }
    
    $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, product_name, product_image, quantity, unit_price, total_price, download_token, license_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $orderId, 
        $productId, 
        $product['name'] ?? 'Produit', 
        $img['image_path'] ?? null, 
        $quantity, 
        $unitPrice, 
        $total,
        $downloadToken,
        $licenseCode
    ]);
    
    // Mettre à jour le stock si produit physique
    if (!$product || $product['product_type'] === 'physical') {
        $db->prepare("UPDATE products SET stock = stock - ?, sales_count = sales_count + ? WHERE id = ? AND track_stock = 1")->execute([$quantity, $quantity, $productId]);
    } else {
        $db->prepare("UPDATE products SET sales_count = sales_count + ? WHERE id = ?")->execute([$quantity, $productId]);
    }
    
    return $db->lastInsertId();
}

function getStoreOrders($storeId, $status = null, $limit = 20, $offset = 0) {
    $db = getDB();
    $sql = "SELECT o.*, (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as items_count FROM orders o WHERE o.store_id = ?";
    $params = [$storeId];
    
    if ($status) {
        $sql .= " AND o.status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// =====================================================
// Fonctions statistiques
// =====================================================

function getStoreDashboardStats($storeId) {
    $db = getDB();
    
    // Ventes aujourd'hui
    $stmt = $db->prepare("SELECT COALESCE(SUM(total), 0) as today_sales, COUNT(*) as today_orders FROM orders WHERE store_id = ? AND DATE(created_at) = CURDATE() AND status != 'cancelled'");
    $stmt->execute([$storeId]);
    $today = $stmt->fetch();
    
    // Ventes ce mois
    $stmt = $db->prepare("SELECT COALESCE(SUM(total), 0) as month_sales, COUNT(*) as month_orders FROM orders WHERE store_id = ? AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND status != 'cancelled'");
    $stmt->execute([$storeId]);
    $month = $stmt->fetch();
    
    // Produits actifs
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM products WHERE store_id = ? AND is_active = 1");
    $stmt->execute([$storeId]);
    $products = $stmt->fetch();
    
    // Commandes en attente
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM orders WHERE store_id = ? AND status IN ('pending', 'confirmed')");
    $stmt->execute([$storeId]);
    $pending = $stmt->fetch();
    
    // Clients
    $stmt = $db->prepare("SELECT COUNT(DISTINCT customer_phone) as total FROM orders WHERE store_id = ?");
    $stmt->execute([$storeId]);
    $customers = $stmt->fetch();
    
    return [
        'today_sales' => $today['today_sales'],
        'today_orders' => $today['today_orders'],
        'month_sales' => $month['month_sales'],
        'month_orders' => $month['month_orders'],
        'total_products' => $products['total'],
        'pending_orders' => $pending['total'],
        'total_customers' => $customers['total']
    ];
}

// =====================================================
// Fonctions d'upload
// =====================================================

function uploadImage($file, $directory = 'products') {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'message' => 'Aucun fichier envoyé.'];
    }
    
    $fileType = mime_content_type($file['tmp_name']);
    if (!in_array($fileType, ALLOWED_IMAGE_TYPES)) {
        return ['success' => false, 'message' => 'Type de fichier non autorisé.'];
    }
    
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'message' => 'Fichier trop volumineux (max 5MB).'];
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid('img_') . '_' . time() . '.' . $ext;
    $uploadDir = UPLOADS_PATH . $directory . DIRECTORY_SEPARATOR;
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filePath = $uploadDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => true, 'path' => $directory . '/' . $fileName, 'full_path' => $filePath];
    }
    
    return ['success' => false, 'message' => 'Erreur lors de l\'upload.'];
}

// =====================================================
// Fonctions utilitaires
// =====================================================

function formatPrice($price, $currency = null) {
    $currency = $currency ?? DEFAULT_CURRENCY;
    return number_format($price, 0, ',', ' ') . ' ' . $currency;
}

function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) return 'il y a ' . $diff->y . ' an' . ($diff->y > 1 ? 's' : '');
    if ($diff->m > 0) return 'il y a ' . $diff->m . ' mois';
    if ($diff->d > 0) return 'il y a ' . $diff->d . ' jour' . ($diff->d > 1 ? 's' : '');
    if ($diff->h > 0) return 'il y a ' . $diff->h . 'h';
    if ($diff->i > 0) return 'il y a ' . $diff->i . ' min';
    return 'à l\'instant';
}

function getStatusBadge($status) {
    $badges = [
        'pending' => ['En attente', 'warning'],
        'confirmed' => ['Confirmée', 'info'],
        'processing' => ['En préparation', 'primary'],
        'shipped' => ['Expédiée', 'info'],
        'delivered' => ['Livrée', 'success'],
        'cancelled' => ['Annulée', 'danger'],
        'refunded' => ['Remboursée', 'secondary'],
        'paid' => ['Payé', 'success'],
        'partial' => ['Partiel', 'warning'],
        'failed' => ['Échoué', 'danger']
    ];
    
    $badge = $badges[$status] ?? ['Inconnu', 'secondary'];
    return '<span class="badge badge-' . $badge[1] . '">' . $badge[0] . '</span>';
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function getFeaturedStores($limit = 8) {
    $db = getDB();
    $stmt = $db->prepare("SELECT s.*, (SELECT COUNT(*) FROM products WHERE store_id = s.id AND is_active = 1) as product_count, (SELECT COUNT(*) FROM orders WHERE store_id = s.id) as order_count FROM stores s WHERE s.is_active = 1 AND s.is_featured = 1 ORDER BY s.created_at DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function getPlatformStats() {
    $db = getDB();
    $stats = [];
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM stores WHERE is_active = 1");
    $stats['total_stores'] = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM orders");
    $stats['total_orders'] = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COUNT(DISTINCT country) as total FROM stores WHERE country IS NOT NULL AND country != ''");
    $stats['total_countries'] = $stmt->fetch()['total'];
    
    return $stats;
}

function getPlans() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM plans WHERE is_active = 1 ORDER BY price ASC");
    return $stmt->fetchAll();
}

/**
 * Récupère la valeur d'un paramètre système global
 */
function getPlatformSetting($key, $default = null) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM platform_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        if (!$row) return $default;
        $decoded = json_decode($row['setting_value'], true);
        return $decoded !== null ? $decoded : $row['setting_value'];
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Enregistre un paramètre système global
 */
function setPlatformSetting($key, $value, $type = 'string') {
    try {
        $db = getDB();
        $encodedValue = is_array($value) ? json_encode($value) : (string)$value;
        $stmt = $db->prepare("INSERT INTO platform_settings (setting_key, setting_value, setting_type) 
                              VALUES (?, ?, ?) 
                              ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type)");
        return $stmt->execute([$key, $encodedValue, $type]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Récupère la liste des moyens de paiement activés par l'administrateur
 */
function getEnabledPaymentMethods() {
    $default = [
        'wave' => 1,
        'orange_money' => 1,
        'mobile_money' => 1,
        'cash_on_delivery' => 1
    ];
    $methods = getPlatformSetting('available_payment_methods', $default);
    if (!is_array($methods)) {
        return $default;
    }
    return array_merge($default, $methods);
}

/**
 * Verse la prime de parrainage de 250 FCFA au parrain dès l'activation d'une boutique filleule.
 */
function triggerReferralReward($storeId) {
    $db = getDB();
    try {
        $stmt = $db->prepare("SELECT * FROM referrals WHERE referred_store_id = ? AND status = 'pending'");
        $stmt->execute([$storeId]);
        $ref = $stmt->fetch();

        if ($ref) {
            $bonusAmount = floatval($ref['bonus_amount'] ?? 250.00);
            
            // Mark referral as rewarded
            $db->prepare("UPDATE referrals SET status = 'rewarded' WHERE id = ?")->execute([$ref['id']]);

            // Credit 250 FCFA to referrer store virtual wallet
            $db->prepare("UPDATE stores SET virtual_wallet = virtual_wallet + ? WHERE user_id = ?")->execute([$bonusAmount, $ref['referrer_id']]);

            // Get referrer store for notification
            $stmtRefStore = $db->prepare("SELECT s.id, s.name as store_name, st.name as referred_store_name FROM stores s JOIN stores st ON st.id = ? WHERE s.user_id = ?");
            $stmtRefStore->execute([$storeId, $ref['referrer_id']]);
            $info = $stmtRefStore->fetch();

            if ($info) {
                $refStoreName = $info['referred_store_name'] ?? 'filleule';
                addNotification('merchant', $info['id'], $ref['referrer_id'], 'referral_rewarded',
                    '🎉 Bonus Parrainage Crédité (+250 FCFA)',
                    "La boutique filleule ($refStoreName) a été activée avec succès ! Un bonus de 250 FCFA a été crédité sur votre solde."
                );
            }
            return true;
        }
    } catch (Exception $e) {
        return false;
    }
    return false;
}

