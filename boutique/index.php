<?php
require_once dirname(__DIR__) . '/includes/functions.php';

// Get store slug from query parameters, subdomain, or URL path
$slug = $_GET['store_slug'] ?? $_GET['subdomain'] ?? '';

if (!$slug) {
    // Check for subdomain (e.g. ahfpilup.phoenixka.shop or store.localhost)
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $hostParts = explode('.', $host);
    if (count($hostParts) >= 3 && strtolower($hostParts[0]) !== 'www') {
        $slug = $hostParts[0];
    } else {
        // Fallback to URI path (e.g., /boutique-ci or /boutique/boutique-ci)
        $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $slug = basename($requestUri);
    }
}

$store = getStoreBySlug($slug);

if (!$store || !$store['is_active']) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Boutique introuvable</title><link rel="stylesheet" href="' . ASSETS_URL . '/css/style.css"></head><body><div style="min-height:100vh;display:flex;align-items:center;justify-content:center;text-align:center;padding:40px"><div><h1 style="font-size:4rem;margin-bottom:16px">🔍</h1><h2>Boutique introuvable</h2><p style="color:var(--text-muted);margin:12px 0 24px">Cette boutique n\'existe pas ou n\'est plus en ligne.</p><a href="' . SITE_URL . '" class="btn btn-primary">Retour à l\'accueil</a></div></div></body></html>';
    exit;
}

// Increment views
$db = getDB();
$db->prepare("UPDATE stores SET views_count = views_count + 1 WHERE id = ?")->execute([$store['id']]);

// Get products and categories
$products = getStoreProducts($store['id']);
$categories = getStoreCategories($store['id']);
$featuredProducts = getStoreProducts($store['id'], null, 6, true);

$primaryColor = $store['primary_color'] ?: '#D4A520';
$enabledPayments = getEnabledPaymentMethods();

// Handle order submission
$orderSuccess = false;
$orderRef = '';
$orderTotal = 0;
$selectedPaymentMethod = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $cartItems = json_decode($_POST['cart_items'] ?? '[]', true);
    
    if (!empty($cartItems) && !empty($_POST['customer_name']) && !empty($_POST['customer_phone'])) {
        $subtotal = 0;
        foreach ($cartItems as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        
        $paymentMethod = sanitize($_POST['payment_method'] ?? 'cash_on_delivery');
        
        $orderResult = createOrder([
            'store_id' => $store['id'],
            'payment_method' => $paymentMethod,
            'subtotal' => $subtotal,
            'shipping_fee' => 0,
            'total' => $subtotal,
            'customer_name' => sanitize($_POST['customer_name']),
            'customer_phone' => sanitize($_POST['customer_phone']),
            'customer_email' => sanitize($_POST['customer_email'] ?? ''),
            'shipping_address' => sanitize($_POST['shipping_address'] ?? ''),
            'shipping_city' => sanitize($_POST['shipping_city'] ?? ''),
            'delivery_notes' => sanitize($_POST['delivery_notes'] ?? '')
        ]);
        
        foreach ($cartItems as $item) {
            addOrderItem($orderResult['order_id'], $item['id'], $item['quantity'], $item['price']);
        }
        
        $orderSuccess = true;
        $orderRef = $orderResult['order_ref'];
        $orderTotal = $subtotal;
        $selectedPaymentMethod = $paymentMethod;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($store['name']) ?> — PhoenixKA Shop</title>
    <meta name="description" content="<?= htmlspecialchars($store['description'] ?: $store['name'] . ' — Commandez en ligne') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="icon" href="<?= ASSETS_URL ?>/images/logo.png">
    <style>
        :root {
            --store-color: <?= $primaryColor ?>;
            --store-glow: <?= $primaryColor ?>35;
            --neon-wave: #00B4D8;
            --cyber-bg: #090A0F;
            --cyber-card: #13141C;
            --cyber-border: rgba(255, 255, 255, 0.08);
            --text-main: #F8FAFC;
            --text-sub: #94A3B8;
        }
        body { font-family: 'Outfit', sans-serif; background-color: var(--cyber-bg); color: var(--text-main); line-height: 1.5; }
        h1, h2, h3, .price, .store-title { font-family: 'Space Grotesk', sans-serif; }

        /* Top Navbar */
        .navbar-store {
            position: sticky;
            top: 0;
            z-index: 990;
            background: rgba(9, 10, 15, 0.85);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 14px 0;
        }
        .navbar-store-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .brand-link {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #FFF;
        }
        .brand-avatar {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid var(--store-color);
            box-shadow: 0 0 15px var(--store-glow);
        }
        .brand-name {
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: -0.3px;
        }
        .verified-badge {
            background: rgba(0, 180, 216, 0.15);
            color: #00B4D8;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 50px;
            border: 1px solid rgba(0, 180, 216, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        /* Hero Store Banner */
        .store-hero-banner {
            position: relative;
            padding: 50px 0 35px;
            background: radial-gradient(circle at 50% 0%, var(--store-glow), transparent 70%),
                        linear-gradient(180deg, rgba(19, 20, 28, 0.8) 0%, #090A0F 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            text-align: center;
        }
        .store-hero-banner::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255, 255, 255, 0.06) 1px, transparent 1px);
            background-size: 20px 20px;
            opacity: 0.5;
            pointer-events: none;
        }
        .store-logo-hero {
            width: 100px;
            height: 100px;
            border-radius: 24px;
            border: 3px solid var(--store-color);
            overflow: hidden;
            margin: 0 auto 16px;
            background: #13141C;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5), 0 0 25px var(--store-glow);
            position: relative;
        }
        .store-logo-hero img { width: 100%; height: 100%; object-fit: cover; }
        .store-hero-title {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #FFFFFF 40%, var(--store-color) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .store-hero-desc {
            max-width: 600px;
            margin: 0 auto 18px;
            color: var(--text-sub);
            font-size: 0.95rem;
            line-height: 1.6;
        }
        .hero-stats-pills {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 14px;
        }
        .hero-pill {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 50px;
            padding: 6px 14px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-sub);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* Filter Bar & Search */
        .controls-bar {
            padding: 24px 0 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }
        .category-pills {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 6px;
            scrollbar-width: none;
        }
        .category-pills::-webkit-scrollbar { display: none; }
        .cat-pill {
            padding: 9px 20px;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: var(--text-sub);
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.25s ease;
            font-family: inherit;
        }
        .cat-pill:hover, .cat-pill.active {
            background: var(--store-color);
            color: #000;
            border-color: var(--store-color);
            box-shadow: 0 0 20px var(--store-glow);
            font-weight: 700;
        }
        .search-box-wrap {
            position: relative;
            min-width: 240px;
        }
        .search-box-wrap input {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 50px;
            padding: 10px 18px 10px 42px;
            color: #FFF;
            font-size: 0.88rem;
            font-family: inherit;
            outline: none;
            transition: border-color 0.3s;
        }
        .search-box-wrap input:focus {
            border-color: var(--store-color);
            box-shadow: 0 0 15px var(--store-glow);
        }
        .search-box-wrap .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-sub);
            font-size: 0.9rem;
        }

        /* High-End Product Cards Grid */
        .products-grid-pro {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 24px;
            padding-bottom: 100px;
            margin-top: 10px;
        }
        .pro-card {
            background: #13141C;
            border: 1px solid rgba(255, 255, 255, 0.07);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            flex-direction: column;
            position: relative;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        .pro-card:hover {
            transform: translateY(-6px);
            border-color: var(--store-color);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6), 0 0 30px var(--store-glow);
        }
        .pro-card-img-box {
            position: relative;
            width: 100%;
            padding-top: 75%; /* 4:3 Ratio */
            background: #0B0C12;
            overflow: hidden;
            cursor: pointer;
        }
        .pro-card-img-box img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }
        .pro-card:hover .pro-card-img-box img {
            transform: scale(1.08);
        }
        .pro-card-img-placeholder {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            opacity: 0.25;
            background: radial-gradient(circle, rgba(255,255,255,0.05), transparent);
        }

        /* Card Overlay Floating Badges */
        .type-badge-floating {
            position: absolute;
            top: 12px;
            left: 12px;
            z-index: 2;
            background: rgba(13, 14, 20, 0.85);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #FFF;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .discount-badge-floating {
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 2;
            background: #EF4444;
            color: #FFF;
            font-weight: 800;
            font-size: 0.72rem;
            padding: 4px 8px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }

        /* Card Content Body */
        .pro-card-body {
            padding: 18px 20px;
            display: flex;
            flex-direction: column;
            flex: 1;
            justify-content: space-between;
        }
        .pro-card-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #FFF;
            margin-bottom: 10px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            cursor: pointer;
            transition: color 0.2s;
        }
        .pro-card-title:hover { color: var(--store-color); }
        .pro-card-status {
            font-size: 0.76rem;
            font-weight: 600;
            color: #4ADE80;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 14px;
        }
        .pro-card-price-row {
            display: flex;
            align-items: baseline;
            gap: 8px;
            margin-bottom: 16px;
        }
        .pro-price-current {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--store-color);
            letter-spacing: -0.5px;
        }
        .pro-price-old {
            font-size: 0.88rem;
            color: var(--text-sub);
            text-decoration: line-through;
        }

        /* Dual Card Action Buttons */
        .pro-card-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        .btn-card-details {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #FFF;
            padding: 10px;
            border-radius: 12px;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s ease;
            font-family: inherit;
            text-align: center;
        }
        .btn-card-details:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.2);
        }
        .btn-card-buy {
            background: var(--store-color);
            color: #000;
            border: none;
            padding: 10px;
            border-radius: 12px;
            font-size: 0.82rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.25s ease;
            font-family: inherit;
            box-shadow: 0 4px 15px var(--store-glow);
            text-align: center;
        }
        .btn-card-buy:hover {
            transform: scale(1.03);
            filter: brightness(1.1);
        }

        /* Futuristic Floating Cart HUD */
        .cart-float { position: fixed; bottom: 28px; right: 28px; z-index: 999; }
        .cart-btn {
            width: 65px;
            height: 65px;
            border-radius: 20px;
            background: linear-gradient(135deg, var(--store-color), #00F0FF);
            color: #000;
            border: 2px solid rgba(255,255,255,0.4);
            font-size: 1.6rem;
            cursor: pointer;
            box-shadow: 0 8px 30px var(--store-glow), 0 0 20px rgba(0, 240, 255, 0.4);
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cart-btn:hover { transform: scale(1.1) rotate(-3deg); }
        .cart-count {
            position: absolute;
            top: -6px;
            right: -6px;
            background: #EF4444;
            color: #FFF;
            font-size: 0.75rem;
            font-weight: 800;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #0A0B10;
        }
        
        .cart-panel {
            position: fixed;
            top: 0;
            right: -440px;
            width: 420px;
            max-width: 100vw;
            height: 100vh;
            background: rgba(14, 15, 22, 0.95);
            backdrop-filter: blur(20px);
            border-left: 1px solid var(--border-gold);
            z-index: 1001;
            transition: right 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            flex-direction: column;
            box-shadow: -15px 0 40px rgba(0, 0, 0, 0.8);
        }
        .cart-panel.open { right: 0; }
        .cart-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.75); backdrop-filter: blur(4px); z-index: 1000; display: none; }
        .cart-overlay.open { display: block; }
        .cart-header { padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.08); display: flex; justify-content: space-between; align-items: center; }
        .cart-header h3 { font-size: 1.2rem; font-weight: 700; color: #FFF; }
        .cart-close { background: none; border: none; color: #94A3B8; font-size: 1.4rem; cursor: pointer; transition: color .2s; }
        .cart-close:hover { color: #FFF; }
        .cart-items { flex: 1; overflow-y: auto; padding: 20px; }
        .cart-item { display: flex; gap: 14px; padding: 14px 0; border-bottom: 1px solid rgba(255,255,255,0.06); }
        .cart-item img { width: 64px; height: 64px; object-fit: cover; border-radius: 12px; }
        .cart-item .item-info { flex: 1; }
        .cart-item .item-info h4 { font-size: 0.9rem; margin-bottom: 4px; color: #FFF; }
        .cart-item .item-info .item-price { color: var(--store-color); font-weight: 700; font-size: 0.95rem; }
        .cart-item .qty-controls { display: flex; align-items: center; gap: 8px; margin-top: 8px; }
        .cart-item .qty-controls button { width: 28px; height: 28px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.15); background: rgba(255,255,255,0.05); color: #FFF; cursor: pointer; font-size: 0.9rem; }
        .cart-item .qty-controls span { font-size: 0.9rem; min-width: 20px; text-align: center; color: #FFF; font-weight: 600; }
        .cart-item .remove-item { background: none; border: none; color: #EF4444; cursor: pointer; font-size: 1rem; padding: 4px; }
        .cart-footer { padding: 24px; border-top: 1px solid rgba(255,255,255,0.08); background: rgba(0,0,0,0.3); }
        .cart-total { display: flex; justify-content: space-between; font-size: 1.15rem; font-weight: 800; margin-bottom: 18px; color: #FFF; }
        .cart-total .amount { color: var(--store-color); }
        .cart-empty { text-align: center; padding: 60px 20px; color: #64748B; font-size: 0.95rem; }
        .cart-footer{padding:20px;border-top:1px solid var(--border-color)}
        .cart-total{display:flex;justify-content:space-between;font-size:1.1rem;font-weight:700;margin-bottom:16px}
        .cart-total .amount{color:var(--store-color)}
        .cart-empty{text-align:center;padding:40px;color:var(--text-muted)}

        /* Order Form & Modal Enhanced */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        .modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }
        .modal-enhanced {
            background: var(--bg-card);
            border: 1px solid var(--border-gold);
            border-radius: 24px;
            max-width: 660px;
            width: 100%;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 50px rgba(0,0,0,0.7), 0 0 30px var(--store-glow);
            overflow: hidden;
            transform: translateY(20px);
            transition: transform 0.3s ease;
        }
        .modal-overlay.active .modal-enhanced {
            transform: translateY(0);
        }
        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255,255,255,0.02);
        }
        .modal-header h3 { font-size: 1.2rem; font-family: 'Playfair Display', serif; color: #FFF; margin: 0; }
        .modal-close {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 1px solid var(--border-color);
            background: var(--bg-surface);
            color: var(--text-muted);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            transition: var(--transition);
        }
        .modal-enhanced form {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 0;
            overflow: hidden;
        }
        .modal-body {
            padding: 24px;
            overflow-y: auto !important;
            max-height: calc(90vh - 140px);
            display: flex;
            flex-direction: column;
            gap: 20px;
            -webkit-overflow-scrolling: touch;
        }
        .modal-body::-webkit-scrollbar {
            width: 6px;
        }
        .modal-body::-webkit-scrollbar-thumb {
            background: var(--store-color);
            border-radius: 4px;
        }
        .modal-body::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.05);
        }

        /* Banner résumé panier */
        .checkout-summary-banner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, rgba(212, 165, 32, 0.15), rgba(26, 26, 30, 0.9));
            border: 1px solid var(--border-gold);
            border-radius: 16px;
            padding: 16px 20px;
        }
        .checkout-summary-banner .summary-left { display: flex; align-items: center; gap: 12px; }
        .checkout-summary-banner .icon { font-size: 1.8rem; }
        .checkout-summary-banner .summary-label { font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .checkout-summary-banner .summary-items-count { font-size: 0.95rem; font-weight: 700; color: #FFF; }
        .checkout-summary-banner .summary-right { text-align: right; }
        .checkout-summary-banner .summary-total-label { font-size: 0.72rem; color: var(--gold-light); font-weight: 600; text-transform: uppercase; }
        .checkout-summary-banner .summary-total-amount { font-size: 1.3rem; font-weight: 800; color: var(--gold); }

        /* Form section cards */
        .form-section-card {
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 18px 20px;
        }
        .form-section-title {
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--gold-light);
            margin-bottom: 14px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        /* Payment Options Radio Grid */
        .payment-methods-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .payment-option-card {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 14px;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
        }
        .payment-option-card:hover {
            border-color: var(--border-gold);
            transform: translateY(-2px);
        }
        .payment-option-card input[type="radio"] {
            margin-top: 3px;
            accent-color: var(--gold);
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .payment-option-card.active-wave {
            border-color: #00B4D8;
            background: rgba(0, 180, 216, 0.08);
            box-shadow: 0 0 15px rgba(0, 180, 216, 0.15);
        }
        .payment-option-card .badge-wave {
            color: #00B4D8;
            font-weight: 800;
            font-size: 0.85rem;
        }
        .payment-option-card .tag-fast {
            background: #00B4D8;
            color: #000;
            font-size: 0.65rem;
            font-weight: 800;
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 6px;
        }
        .payment-option-card .option-title {
            font-size: 0.85rem;
            font-weight: 600;
            color: #FFF;
            margin: 4px 0 2px;
        }
        .payment-option-card .option-desc {
            font-size: 0.75rem;
            color: var(--text-muted);
            line-height: 1.3;
        }

        .modal-footer-enhanced {
            padding: 16px 24px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255,255,255,0.02);
            gap: 12px;
        }
        .btn-submit-order {
            flex: 1;
            padding: 14px 20px;
            font-size: 1rem;
            font-weight: 700;
            border-radius: 12px;
        }

        /* Order Success */
        .order-success{text-align:center;padding:60px 20px}
        .order-success .check{font-size:4rem;margin-bottom:16px}
        .order-success h2{font-size:1.5rem;margin-bottom:8px}
        .order-success .ref{color:var(--store-color);font-family:monospace;font-size:1.2rem;margin:8px 0 20px}

        .powered-by{text-align:center;padding:24px;font-size:.75rem;color:var(--text-muted);border-top:1px solid var(--border-color)}
        .powered-by a{color:var(--gold)}
        /* High-Converting Product Detail View (Screenshot Match) */
        .product-detail-modal-enhanced {
            background: #FFFFFF;
            color: #1E293B;
            border-radius: 24px;
            max-width: 1040px;
            width: 100%;
            max-height: 92vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 25px 60px rgba(0,0,0,0.6);
            overflow: hidden;
        }
        .product-detail-header-bar {
            padding: 16px 24px;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #F8FAFC;
        }
        .product-detail-body {
            padding: 30px;
            overflow-y: auto;
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 32px;
            align-items: start;
        }

        /* Left Column Content */
        .pd-left-col { display: flex; flex-direction: column; gap: 24px; }
        .pd-title { font-size: 1.6rem; font-weight: 800; color: #0F172A; line-height: 1.3; margin-bottom: 8px; font-family: 'Space Grotesk', sans-serif; }
        .pd-badges-row { display: flex; align-items: center; gap: 12px; font-size: 0.82rem; color: #64748B; margin-bottom: 12px; flex-wrap: wrap; }
        .pd-badge-tag { display: inline-flex; align-items: center; gap: 4px; background: #F1F5F9; padding: 4px 10px; border-radius: 6px; font-weight: 600; border: 1px solid #E2E8F0; }
        
        .pd-main-img-wrap {
            width: 100%;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
            border: 1px solid #E2E8F0;
            background: #0A0B10;
        }
        .pd-main-img-wrap img { width: 100%; max-height: 480px; object-fit: contain; display: block; }

        /* Fichiers Box */
        .pd-files-section { display: flex; flex-direction: column; gap: 12px; }
        .pd-files-title { font-size: 1.1rem; font-weight: 700; color: #0F172A; font-family: 'Space Grotesk', sans-serif; }
        .pd-yellow-notice {
            background: #FEF9C3;
            border: 1px solid #FDE047;
            color: #854D0E;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .pd-file-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            background: #F8FAFC;
        }
        .pd-file-info { display: flex; align-items: center; gap: 12px; }
        .pd-file-icon { width: 42px; height: 42px; border-radius: 10px; background: #E2E8F0; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .pd-file-name { font-weight: 700; font-size: 0.95rem; color: #0F172A; }
        .pd-file-meta { font-size: 0.78rem; color: #64748B; }
        .pd-btn-preview {
            padding: 8px 16px;
            border: 1px solid #CBD5E1;
            background: #FFF;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 700;
            color: #334155;
            cursor: pointer;
            transition: all 0.2s;
        }
        .pd-btn-preview:hover { background: #F1F5F9; border-color: #94A3B8; }

        /* Description Copy */
        .pd-description-box { display: flex; flex-direction: column; gap: 16px; color: #334155; line-height: 1.7; font-size: 0.95rem; }
        .pd-description-box h3 { font-size: 1.25rem; font-weight: 800; color: #0F172A; margin-top: 8px; font-family: 'Space Grotesk', sans-serif; }
        .pd-checklist { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px; }
        .pd-checklist li { display: flex; align-items: flex-start; gap: 10px; }
        .pd-checklist li .icon-check { color: #16A34A; font-weight: 800; }
        .pd-checklist li .icon-pin { color: #EAB308; }

        /* Right Sticky Floating Widget Card */
        .pd-right-widget {
            position: sticky;
            top: 0;
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            box-shadow: 0 15px 40px rgba(0,0,0,0.08);
            border-radius: 20px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .pd-price-row { text-align: center; }
        .pd-price-old { text-decoration: line-through; color: #94A3B8; font-weight: 700; font-size: 1.15rem; margin-right: 8px; }
        .pd-price-current { color: #DC2626; font-weight: 900; font-size: 1.9rem; font-family: 'Space Grotesk', sans-serif; }
        .pd-btn-buy {
            width: 100%;
            padding: 16px;
            background: #FFCC00;
            color: #000;
            border: none;
            border-radius: 14px;
            font-weight: 800;
            font-size: 1.05rem;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(255, 204, 0, 0.4);
            transition: all 0.2s ease;
            text-align: center;
            font-family: inherit;
        }
        .pd-btn-buy:hover { transform: translateY(-2px); background: #E6B800; box-shadow: 0 8px 25px rgba(255, 204, 0, 0.5); }
        .pd-link-preview { text-align: center; font-size: 0.85rem; color: #D97706; text-decoration: underline; font-weight: 600; cursor: pointer; }
        
        .pd-payments-box { text-align: center; border-top: 1px solid #F1F5F9; border-bottom: 1px solid #F1F5F9; padding: 14px 0; }
        .pd-payments-title { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.5px; color: #64748B; font-weight: 600; margin-bottom: 10px; }
        .pd-payment-logos-row { display: flex; justify-content: center; align-items: center; gap: 8px; flex-wrap: wrap; }
        .pd-pay-logo-item { display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 6px 8px; border-radius: 8px; background: #F8FAFC; border: 1px solid #E2E8F0; transition: transform 0.2s ease, border-color 0.2s; min-width: 48px; position: relative; }
        .pd-pay-logo-item:hover { transform: translateY(-2px); border-color: var(--store-color); }
        .pd-pay-logo-item img { width: 28px; height: 28px; object-fit: contain; }
        .pd-pay-logo-item span { font-size: 0.65rem; font-weight: 700; color: #475569; }
        
        .pd-pay-logo-item.disabled { opacity: 0.4; filter: grayscale(90%); background: #F1F5F9; border-color: #CBD5E1; cursor: not-allowed; }
        .pd-pay-logo-item.disabled::after { content: "🔒"; position: absolute; top: -6px; right: -6px; font-size: 10px; background: #FFF; border-radius: 50%; width: 14px; height: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.15); }
        .pd-pay-logo-item.disabled:hover { transform: none; border-color: #CBD5E1; }

        .payment-option-card.card-disabled { opacity: 0.45; filter: grayscale(70%); cursor: not-allowed; background: rgba(0,0,0,0.03); border-color: #E2E8F0 !important; }
        .payment-option-card.card-disabled input { cursor: not-allowed; }

        .pd-widget-actions { display: flex; justify-content: space-around; font-size: 0.8rem; color: #64748B; border-bottom: 1px solid #F1F5F9; padding-bottom: 12px; }
        .pd-widget-actions span { cursor: pointer; display: flex; align-items: center; gap: 4px; transition: color 0.2s; }
        .pd-widget-actions span:hover { color: #0F172A; }
        .pd-help-link { text-align: center; font-size: 0.8rem; color: #475569; font-weight: 600; cursor: pointer; text-decoration: underline; }

        @media(max-width: 820px) {
            .product-detail-body { grid-template-columns: 1fr; gap: 24px; padding: 20px; }
            .pd-right-widget { position: static; }
        }

        @media(max-width:600px){
            .cart-panel{width:100%}
            .products-grid{grid-template-columns:repeat(2,1fr);gap:12px}
            .product-card .img-wrap{height:150px}
            .form-grid-2{grid-template-columns:1fr}
            .payment-methods-grid{grid-template-columns:1fr}
            .modal-enhanced{max-height:85vh;margin:0;border-radius:20px 20px 0 0;}
            .modal-body{max-height:calc(85vh - 130px);padding:16px;}
            .modal-overlay{padding:0;align-items:flex-end;overflow-y:auto;}
        }
    </style>
</head>
<body>
<!-- FIXED TOP NAVBAR -->
<nav class="navbar-store">
    <div class="container navbar-store-inner">
        <a href="<?= SITE_URL ?>/boutique/<?= $store['slug'] ?>" class="brand-link">
            <?php if ($store['logo']): ?>
                <img src="<?= UPLOADS_URL ?>/<?= $store['logo'] ?>" alt="" class="brand-avatar">
            <?php else: ?>
                <div class="brand-avatar" style="background:#1E293B;display:flex;align-items:center;justify-content:center;font-size:1.4rem">🏪</div>
            <?php endif; ?>
            <div>
                <div class="brand-name"><?= htmlspecialchars($store['name']) ?></div>
                <div class="verified-badge">✓ Marchand Vérifié 🇨🇮</div>
            </div>
        </a>

        <div style="display:flex;align-items:center;gap:12px">
            <?php if ($store['whatsapp']): ?>
                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $store['whatsapp']) ?>" target="_blank" class="btn btn-ghost btn-sm" style="border-color:rgba(37,211,102,0.4);color:#25D366">
                    💬 WhatsApp Direct
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<?php if ($orderSuccess): ?>
<!-- ORDER SUCCESS CODE REMAINS UNCHANGED HERE -->
<?php else: ?>

<!-- STORE HERO BANNER -->
<section class="store-hero-banner" style="<?= !empty($store['cover_image']) ? "background: linear-gradient(180deg, rgba(9, 10, 15, 0.65) 0%, rgba(9, 10, 15, 0.95) 100%), url('" . UPLOADS_URL . "/" . htmlspecialchars($store['cover_image']) . "') center/cover no-repeat !important; padding: 75px 0 45px;" : "" ?>">
    <div class="container">
        <div class="store-logo-hero">
            <?php if ($store['logo']): ?>
                <img src="<?= UPLOADS_URL ?>/<?= $store['logo'] ?>" alt="<?= htmlspecialchars($store['name']) ?>">
            <?php else: ?>
                <span style="font-size:3rem">🏪</span>
            <?php endif; ?>
        </div>

        <h1 class="store-hero-title"><?= htmlspecialchars($store['name']) ?></h1>
        
        <?php if ($store['description']): ?>
            <p class="store-hero-desc"><?= htmlspecialchars($store['description']) ?></p>
        <?php else: ?>
            <p class="store-hero-desc">Bienvenue dans notre boutique officielle ! Produits certifiés et livraison instantanée par Mobile Money.</p>
        <?php endif; ?>

        <div class="hero-stats-pills">
            <?php if ($store['city']): ?>
                <span class="hero-pill">📍 <?= htmlspecialchars($store['city']) ?><?= $store['country'] ? ', ' . htmlspecialchars($store['country']) : '' ?></span>
            <?php endif; ?>
            <span class="hero-pill">⭐ 4.9 (120+ avis clients)</span>
            <span class="hero-pill" style="border-color:rgba(0,180,216,0.3);color:#00B4D8">🌊 Paiement Wave Direct</span>
            <span class="hero-pill" style="border-color:rgba(74,222,128,0.3);color:#4ADE80">🟢 Réponses Rapides</span>
        </div>
    </div>
</section>

<!-- CONTROLS & FILTER BAR -->
<div class="container">
    <div class="controls-bar">
        <div class="category-pills">
            <button class="cat-pill active" data-cat="all">Tout voir (<?= count($products) ?>)</button>
            <?php foreach ($categories as $cat): ?>
                <button class="cat-pill" data-cat="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?> (<?= $cat['product_count'] ?>)</button>
            <?php endforeach; ?>
        </div>

        <!-- SEARCH INPUT -->
        <div class="search-box-wrap">
            <span class="search-icon">🔍</span>
            <input type="text" id="storeSearchInput" placeholder="Rechercher un produit..." onkeyup="filterProductsBySearch()">
        </div>
    </div>
</div>

<!-- PRODUCTS GRID PRO -->
<div class="container">
    <div class="products-grid-pro" id="productsGrid">
        <?php foreach ($products as $p): 
            $productType = $p['product_type'] ?? 'physical';
            $typeBadge = '📦 Produit Physique';
            if ($productType === 'digital') {
                $typeBadge = '⚡ Fichier Numérique';
            } elseif ($productType === 'license') {
                $typeBadge = '🔑 Clé de Licence';
            } elseif ($productType === 'course') {
                $typeBadge = '📚 Formation / Coaching';
            }

            $discountPercent = 0;
            if (!empty($p['compare_price']) && $p['compare_price'] > $p['price']) {
                $discountPercent = round((($p['compare_price'] - $p['price']) / $p['compare_price']) * 100);
            }
        ?>
            <div class="pro-card" data-cat="<?= $p['category_id'] ?: 'none' ?>" data-id="<?= $p['id'] ?>" data-name="<?= htmlspecialchars($p['name']) ?>" data-price="<?= $p['price'] ?>" data-compare="<?= $p['compare_price'] ?: '' ?>" data-desc="<?= htmlspecialchars($p['description'] ?: '') ?>" data-img="<?= $p['primary_image'] ? UPLOADS_URL . '/' . $p['primary_image'] : '' ?>">
                
                <!-- TYPE BADGE -->
                <div class="type-badge-floating">
                    <?= $typeBadge ?>
                </div>

                <!-- DISCOUNT BADGE -->
                <?php if ($discountPercent > 0): ?>
                    <div class="discount-badge-floating">
                        -<?= $discountPercent ?>%
                    </div>
                <?php endif; ?>

                <!-- IMAGE BOX -->
                <div class="pro-card-img-box" onclick="openProductDetail(this.parentElement)">
                    <?php if ($p['primary_image']): ?>
                        <img src="<?= UPLOADS_URL ?>/<?= $p['primary_image'] ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="pro-card-img-placeholder">🛍️</div>
                    <?php endif; ?>
                </div>

                <!-- BODY -->
                <div class="pro-card-body">
                    <div>
                        <h3 class="pro-card-title" onclick="openProductDetail(this.closest('.pro-card'))">
                            <?= htmlspecialchars($p['name']) ?>
                        </h3>
                        
                        <div class="pro-card-status">
                            <?php if ($productType === 'digital' || $productType === 'license'): ?>
                                ⚡ Livraison Instantanée après paiement
                            <?php else: ?>
                                <?= $p['stock'] > 0 ? '🟢 En stock (Livraison 24h)' : '🔴 En rupture de stock' ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div>
                        <div class="pro-card-price-row">
                            <span class="pro-price-current"><?= formatPrice($p['price']) ?></span>
                            <?php if ($p['compare_price'] && $p['compare_price'] > $p['price']): ?>
                                <span class="pro-price-old"><?= formatPrice($p['compare_price']) ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- ACTIONS -->
                        <div class="pro-card-actions">
                            <button type="button" class="btn-card-details" onclick="openProductDetail(this.closest('.pro-card'))">
                                🔍 Aperçu
                            </button>
                            <button type="button" class="btn-card-buy" onclick="event.stopPropagation(); addToCart(this.closest('.pro-card'))" <?= $p['stock'] <= 0 ? 'disabled style="opacity:.4"' : '' ?>>
                                🛒 Acheter
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- CART BUTTON -->
<div class="cart-float">
    <button class="cart-btn" onclick="toggleCart()" id="cartBtn" style="display:none">
        🛒 <span class="cart-count" id="cartCount">0</span>
    </button>
</div>

<!-- CART PANEL -->
<div class="cart-overlay" id="cartOverlay" onclick="toggleCart()"></div>
<div class="cart-panel" id="cartPanel">
    <div class="cart-header">
        <h3>🛒 Votre panier</h3>
        <button class="cart-close" onclick="toggleCart()">✕</button>
    </div>
    <div class="cart-items" id="cartItems">
        <div class="cart-empty">Votre panier est vide</div>
    </div>
    <div class="cart-footer" id="cartFooter" style="display:none">
        <div class="cart-total">
            <span>Total</span>
            <span class="amount" id="cartTotal">0 <?= DEFAULT_CURRENCY ?></span>
        </div>
        <button class="btn btn-primary btn-block" onclick="showOrderForm()" style="background:var(--store-color)">Commander maintenant</button>
    </div>
</div>

<!-- ORDER FORM MODAL -->
<div class="modal-overlay" id="orderModal">
    <div class="modal modal-enhanced">
        <div class="modal-header">
            <div>
                <h3>📋 Finaliser ma commande</h3>
                <div style="font-size:0.78rem;color:var(--text-muted);margin-top:2px">Veuillez vérifier votre commande et vos informations de livraison</div>
            </div>
            <button type="button" class="modal-close" onclick="closeOrderModal()">✕</button>
        </div>

        <form method="POST" id="orderForm">
            <input type="hidden" name="place_order" value="1">
            <input type="hidden" name="cart_items" id="cartItemsInput">

            <div class="modal-body">
                <!-- RECAP PANIER EN HAUT DU FORMULAIRE -->
                <div class="checkout-summary-banner">
                    <div class="summary-left">
                        <span class="icon">🛒</span>
                        <div>
                            <div class="summary-label">Résumé du Panier</div>
                            <div class="summary-items-count" id="modalCartCount">0 article(s)</div>
                        </div>
                    </div>
                    <div class="summary-right">
                        <div class="summary-total-label">Montant Total</div>
                        <div class="summary-total-amount" id="modalCartTotal">0 FCFA</div>
                    </div>
                </div>

                <!-- SECTION 1 : COORDONNÉES CLIENT -->
                <div class="form-section-card">
                    <div class="form-section-title">👤 1. Vos Coordonnées</div>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Nom &amp; Prénom *</label>
                            <input type="text" name="customer_name" class="form-control" placeholder="Ex: Jean-Marc Kouassi" required>
                        </div>
                        <div class="form-group">
                            <label>Téléphone / WhatsApp 🇨🇮 *</label>
                            <input type="tel" name="customer_phone" class="form-control" placeholder="Ex: 07 01 41 59 11" required>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:0;margin-top:8px">
                        <label>Adresse E-mail <span style="font-weight:400;color:var(--text-muted)">(Optionnel pour recevoir votre reçu)</span></label>
                        <input type="email" name="customer_email" class="form-control" placeholder="Ex: kouassi@gmail.com">
                    </div>
                </div>

                <!-- SECTION 2 : LIVRAISON -->
                <div class="form-section-card">
                    <div class="form-section-title">📍 2. Zone &amp; Adresse de Livraison</div>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Ville *</label>
                            <input type="text" name="shipping_city" class="form-control" value="Abidjan" placeholder="Ex: Abidjan, Yamoussoukro, Bouaké..." required>
                        </div>
                        <div class="form-group">
                            <label>Commune / Quartier &amp; Repère *</label>
                            <input type="text" name="shipping_address" class="form-control" placeholder="Ex: Cocody Angré, Rue Ministre..." required>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:0;margin-top:8px">
                        <label>Instructions de livraison <span style="font-weight:400;color:var(--text-muted)">(Optionnel)</span></label>
                        <input type="text" name="delivery_notes" class="form-control" placeholder="Ex: Appeler à l'arrivée, livrer entre 12h et 14h...">
                    </div>
                </div>

                <!-- SECTION 3 : MODE DE PAIEMENT -->
                <div class="form-section-card">
                    <div class="form-section-title">💳 3. Choisissez votre Mode de Règlement</div>
                    
                    <div class="payment-methods-grid">
                        <!-- WAVE DIRECT -->
                        <label class="payment-option-card <?= !empty($enabledPayments['wave']) ? 'active-wave' : 'card-disabled' ?>" id="card_wave">
                            <input type="radio" name="payment_method" value="wave" <?= !empty($enabledPayments['wave']) ? 'checked' : 'disabled' ?> onclick="selectPaymentCard('wave')">
                            <div class="option-content">
                                <div class="option-header" style="display:flex;align-items:center;gap:8px">
                                    <img src="<?= ASSETS_URL ?>/images/payments/wave.png" alt="Wave" style="width:28px;height:28px;border-radius:6px;object-fit:cover">
                                    <span class="badge-wave">WAVE DIRECT</span>
                                    <?php if (!empty($enabledPayments['wave'])): ?>
                                        <span class="tag-fast">RECOMMANDÉ ⚡</span>
                                    <?php else: ?>
                                        <span style="font-size:0.7rem;font-weight:800;color:#EF4444;background:rgba(239,68,68,0.1);padding:2px 8px;border-radius:6px">🔒 INDISPONIBLE</span>
                                    <?php endif; ?>
                                </div>
                                <div class="option-title">Transfert 1-Clic ou Numéro Wave</div>
                                <div class="option-desc"><?= !empty($enabledPayments['wave']) ? 'Transfert direct sécurisé sans frais vers le marchand' : 'Ce moyen de paiement est désactivé par l\'administrateur' ?></div>
                            </div>
                        </label>

                        <!-- ORANGE MONEY -->
                        <label class="payment-option-card <?= empty($enabledPayments['wave']) && !empty($enabledPayments['orange_money']) ? 'active-wave' : '' ?> <?= !empty($enabledPayments['orange_money']) ? '' : 'card-disabled' ?>" id="card_orange_money">
                            <input type="radio" name="payment_method" value="orange_money" <?= empty($enabledPayments['wave']) && !empty($enabledPayments['orange_money']) ? 'checked' : '' ?> <?= !empty($enabledPayments['orange_money']) ? '' : 'disabled' ?> onclick="selectPaymentCard('orange_money')">
                            <div class="option-content">
                                <div class="option-header" style="display:flex;align-items:center;gap:8px">
                                    <img src="<?= ASSETS_URL ?>/images/payments/orange_money.png" alt="Orange Money" style="width:28px;height:28px;object-fit:contain">
                                    <span style="color:#FF7900;font-weight:700">ORANGE MONEY</span>
                                    <?php if (empty($enabledPayments['orange_money'])): ?>
                                        <span style="font-size:0.7rem;font-weight:800;color:#EF4444;background:rgba(239,68,68,0.1);padding:2px 8px;border-radius:6px">🔒 INDISPONIBLE</span>
                                    <?php endif; ?>
                                </div>
                                <div class="option-title">Orange Money CI</div>
                                <div class="option-desc"><?= !empty($enabledPayments['orange_money']) ? 'Paiement par transfert d\'argent mobile' : 'Ce moyen de paiement est désactivé par l\'administrateur' ?></div>
                            </div>
                        </label>

                        <!-- MTN / MOOV MONEY -->
                        <label class="payment-option-card <?= empty($enabledPayments['wave']) && empty($enabledPayments['orange_money']) && !empty($enabledPayments['mobile_money']) ? 'active-wave' : '' ?> <?= !empty($enabledPayments['mobile_money']) ? '' : 'card-disabled' ?>" id="card_mobile_money">
                            <input type="radio" name="payment_method" value="mobile_money" <?= empty($enabledPayments['wave']) && empty($enabledPayments['orange_money']) && !empty($enabledPayments['mobile_money']) ? 'checked' : '' ?> <?= !empty($enabledPayments['mobile_money']) ? '' : 'disabled' ?> onclick="selectPaymentCard('mobile_money')">
                            <div class="option-content">
                                <div class="option-header" style="display:flex;align-items:center;gap:8px">
                                    <img src="<?= ASSETS_URL ?>/images/payments/mtn_momo.svg" alt="MTN MoMo" style="width:26px;height:26px">
                                    <img src="<?= ASSETS_URL ?>/images/payments/moov_money.svg" alt="Moov Money" style="width:26px;height:26px">
                                    <span style="color:#FFCC00;font-weight:700">MTN / MOOV MONEY</span>
                                    <?php if (empty($enabledPayments['mobile_money'])): ?>
                                        <span style="font-size:0.7rem;font-weight:800;color:#EF4444;background:rgba(239,68,68,0.1);padding:2px 8px;border-radius:6px">🔒 INDISPONIBLE</span>
                                    <?php endif; ?>
                                </div>
                                <div class="option-title">MoMo &amp; Moov Money</div>
                                <div class="option-desc"><?= !empty($enabledPayments['mobile_money']) ? 'Paiement par Mobile Money' : 'Ce moyen de paiement est désactivé par l\'administrateur' ?></div>
                            </div>
                        </label>

                        <!-- CASH ON DELIVERY -->
                        <label class="payment-option-card <?= empty($enabledPayments['wave']) && empty($enabledPayments['orange_money']) && empty($enabledPayments['mobile_money']) && !empty($enabledPayments['cash_on_delivery']) ? 'active-wave' : '' ?> <?= !empty($enabledPayments['cash_on_delivery']) ? '' : 'card-disabled' ?>" id="card_cash_on_delivery">
                            <input type="radio" name="payment_method" value="cash_on_delivery" <?= empty($enabledPayments['wave']) && empty($enabledPayments['orange_money']) && empty($enabledPayments['mobile_money']) && !empty($enabledPayments['cash_on_delivery']) ? 'checked' : '' ?> <?= !empty($enabledPayments['cash_on_delivery']) ? '' : 'disabled' ?> onclick="selectPaymentCard('cash_on_delivery')">
                            <div class="option-content">
                                <div class="option-header" style="display:flex;align-items:center;gap:8px">
                                    <img src="<?= ASSETS_URL ?>/images/payments/cash.png" alt="Cash" style="width:28px;height:28px;border-radius:6px;object-fit:cover">
                                    <span style="color:#4ADE80;font-weight:700">À LA LIVRAISON</span>
                                    <?php if (empty($enabledPayments['cash_on_delivery'])): ?>
                                        <span style="font-size:0.7rem;font-weight:800;color:#EF4444;background:rgba(239,68,68,0.1);padding:2px 8px;border-radius:6px">🔒 INDISPONIBLE</span>
                                    <?php endif; ?>
                                </div>
                                <div class="option-title">En Espèces</div>
                                <div class="option-desc"><?= !empty($enabledPayments['cash_on_delivery']) ? 'Payez en espèces au livreur lors de la réception' : 'Ce moyen de paiement est désactivé par l\'administrateur' ?></div>
                            </div>
                        </label>
                    </div>
                </div>

            </div>

            <div class="modal-footer-enhanced">
                <button type="button" class="btn btn-ghost" onclick="closeOrderModal()">Annuler</button>
                <button type="submit" class="btn btn-primary btn-submit-order" style="background:var(--store-color)">
                    ✓ Valider et Confirmer ma commande
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($orderSuccess): 
    $stmt = $db->prepare("SELECT oi.*, p.product_type, p.digital_file, p.digital_link FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
    $stmt->execute([$orderResult['order_id'] ?? 0]);
    $itemsDelivered = $stmt->fetchAll();
?>
<!-- ORDER SUCCESS CONFIRMATION MODAL -->
<div class="modal-overlay active" id="successModal">
    <div class="modal" style="max-width:550px;background:var(--bg-card);border:1px solid var(--border-gold);border-radius:24px;padding:30px;text-align:center;box-shadow:0 20px 50px rgba(0,0,0,0.8)">
        <div style="font-size:3.5rem;margin-bottom:12px">🎉</div>
        <h2 style="font-family:'Space Grotesk',sans-serif;color:var(--gold)">Commande Confirmée !</h2>
        <p style="color:var(--text-muted);font-size:0.95rem;margin-bottom:16px">Référence : <strong style="color:#FFF"><?= $orderRef ?></strong></p>

        <?php 
        $hasDigital = false;
        foreach ($itemsDelivered as $delItem) {
            if ($delItem['product_type'] === 'digital' || $delItem['product_type'] === 'license') {
                $hasDigital = true;
                break;
            }
        }
        ?>

        <?php if ($hasDigital): ?>
            <div style="background:linear-gradient(135deg, rgba(0, 180, 216, 0.15), rgba(15, 23, 42, 0.9));border:1px solid rgba(0, 180, 216, 0.4);border-radius:16px;padding:20px;margin:20px 0;text-align:left">
                <h3 style="color:#00B4D8;font-size:1.05rem;margin-bottom:12px;display:flex;align-items:center;gap:8px">
                    <span>⚡ Vos Produits Numériques & Licences</span>
                </h3>
                
                <div style="display:flex;flex-direction:column;gap:12px">
                    <?php foreach ($itemsDelivered as $delItem): ?>
                        <?php if ($delItem['product_type'] === 'digital'): ?>
                            <div style="background:rgba(255,255,255,0.05);padding:12px 16px;border-radius:10px;display:flex;justify-content:space-between;align-items:center">
                                <div>
                                    <div style="font-weight:700;color:#FFF;font-size:0.9rem"><?= htmlspecialchars($delItem['product_name']) ?></div>
                                    <div style="font-size:0.75rem;color:var(--text-muted)">Téléchargement sécurisé immédiat</div>
                                </div>
                                <a href="<?= SITE_URL ?>/download.php?token=<?= $delItem['download_token'] ?>" class="btn btn-primary btn-sm" style="background:#00B4D8;color:#000;font-weight:700" target="_blank">
                                    📥 Télécharger
                                </a>
                            </div>
                        <?php elseif ($delItem['product_type'] === 'license'): ?>
                            <div style="background:rgba(234, 179, 8, 0.1);border:1px solid rgba(234, 179, 8, 0.3);padding:14px;border-radius:10px">
                                <div style="font-weight:700;color:#EAB308;font-size:0.9rem"><?= htmlspecialchars($delItem['product_name']) ?></div>
                                <div style="font-size:0.8rem;color:var(--text-muted);margin:4px 0">Votre Clé d'Activation / Licence :</div>
                                <div style="background:#000;color:var(--gold);font-family:monospace;font-size:1.1rem;font-weight:800;padding:8px 12px;border-radius:8px;letter-spacing:1px;display:flex;justify-content:space-between;align-items:center">
                                    <span><?= htmlspecialchars($delItem['license_code']) ?></span>
                                    <button type="button" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($delItem['license_code']) ?>');alert('Clé copiée !');" style="background:none;border:none;color:#FFF;cursor:pointer;font-size:0.8rem">📋 Copier</button>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($selectedPaymentMethod === 'wave' && $store['wave_number']): ?>
            <div style="background:rgba(0,180,216,0.1);border:1px solid rgba(0,180,216,0.3);border-radius:12px;padding:16px;margin-bottom:20px;text-align:left">
                <div style="font-weight:700;color:#00B4D8;margin-bottom:6px">🌊 Règlement via Wave Direct</div>
                <div style="font-size:0.85rem;color:var(--text-muted)">Effectuez votre paiement au <strong><?= htmlspecialchars($store['wave_number']) ?></strong>.</div>
            </div>
        <?php endif; ?>

        <button class="btn btn-primary btn-block" onclick="document.getElementById('successModal').classList.remove('active')" style="background:var(--store-color)">
            ✓ J'ai compris / Fermer
        </button>
    </div>
</div>
<?php endif; ?>

<!-- PRODUCT DETAIL MODAL (SCREENSHOT MATCH) -->
<div class="modal-overlay" id="productDetailModal">
    <div class="product-detail-modal-enhanced">
        <div class="product-detail-header-bar">
            <span style="font-weight:700;font-size:0.9rem;color:#64748B">🔍 Détails du Produit</span>
            <button type="button" class="modal-close" onclick="closeProductDetailModal()">✕</button>
        </div>
        <div class="product-detail-body">
            <!-- LEFT COLUMN -->
            <div class="pd-left-col">
                <div>
                    <h1 class="pd-title" id="pdTitle">Titre du produit</h1>
                    <div class="pd-badges-row">
                        <span class="pd-badge-tag">🛍️ <span id="pdSalesCount">12x</span> Achats</span>
                        <span class="pd-badge-tag">⚡ Expédition / Livraison 24h</span>
                        <span class="pd-badge-tag">🔒 Paiement Sécurisé</span>
                    </div>
                </div>

                <div class="pd-main-img-wrap">
                    <img id="pdMainImg" src="" alt="Produit">
                </div>

                <!-- SECTION FICHIERS / APERÇU -->
                <div class="pd-files-section">
                    <div class="pd-files-title">Fichiers &amp; Inclus (1)</div>
                    <div class="pd-yellow-notice">
                        💡 <span>Prévisualisez gratuitement les informations ou caractéristiques avant achat.</span>
                    </div>
                    <div class="pd-file-card">
                        <div class="pd-file-info">
                            <div class="pd-file-icon">📄</div>
                            <div>
                                <div class="pd-file-name" id="pdFileName">Guide / Fiche Produit Inclus</div>
                                <div class="pd-file-meta">Inclus • Accès immédiat après commande</div>
                            </div>
                        </div>
                        <button type="button" class="pd-btn-preview" onclick="buyNowFromDetailModal()">Aperçu ></button>
                    </div>
                </div>

                <!-- DESCRIPTION COPY -->
                <div class="pd-description-box">
                    <h3>🚀 Transformez votre potentiel avec ce produit</h3>
                    <p id="pdDescriptionText">Découvrez un produit exceptionnel conçu avec soin pour répondre à vos besoins avec la plus haute qualité et satisfaction garantie.</p>
                    
                    <ul class="pd-checklist">
                        <li><span class="icon-check">✅</span> <span>Qualité supérieure garantie et vérifiée par PhoenixKA</span></li>
                        <li><span class="icon-check">✅</span> <span>Service client réactif sur WhatsApp 24/7</span></li>
                        <li><span class="icon-check">✅</span> <span>Livraison rapide et sécurisée en Côte d'Ivoire</span></li>
                        <li><span class="icon-check">✅</span> <span>Paiement direct Wave 1-Clic ou Mobile Money</span></li>
                    </ul>

                    <ul class="pd-checklist" style="margin-top:8px">
                        <li><span class="icon-pin">💡</span> <span>Aucun besoin d'être expert.</span></li>
                        <li><span class="icon-pin">💡</span> <span>Aucun risque : satisfaction garantie.</span></li>
                        <li><span class="icon-pin">💡</span> <span>Accessible depuis un simple téléphone portable.</span></li>
                    </ul>

                    <h3 style="margin-top:16px">📦 Ce que vous recevez</h3>
                    <ol style="padding-left:20px;margin:0;display:flex;flex-direction:column;gap:6px">
                        <li>Produit certifié par la boutique partenaire</li>
                        <li>Assistance WhatsApp personnalisée par le vendeur</li>
                        <li>Reçu et confirmation de commande immédiats</li>
                    </ol>
                </div>
            </div>

            <!-- RIGHT STICKY WIDGET CARD -->
            <div class="pd-right-widget">
                <div class="pd-price-row">
                    <span class="pd-price-old" id="pdOldPrice">15 000 FCFA</span>
                    <span class="pd-price-current" id="pdCurrentPrice">7 500 FCFA</span>
                </div>

                <button type="button" class="pd-btn-buy" id="pdBuyBtn" onclick="buyNowFromDetailModal()">
                    Acheter maintenant
                </button>

                <div class="pd-link-preview" onclick="buyNowFromDetailModal()">
                    Découvrir un aperçu
                </div>

                <div class="pd-payments-box">
                    <div class="pd-payments-title">Moyens de paiement acceptés sur cette boutique</div>
                    <div class="pd-payment-logos-row">
                        <div class="pd-pay-logo-item <?= !empty($enabledPayments['wave']) ? '' : 'disabled' ?>" title="<?= !empty($enabledPayments['wave']) ? 'Wave Direct Actif' : 'Wave désactivé par l\'administrateur' ?>">
                            <img src="<?= ASSETS_URL ?>/images/payments/wave.png" alt="Wave" style="border-radius:4px">
                            <span>Wave</span>
                        </div>

                        <div class="pd-pay-logo-item <?= !empty($enabledPayments['orange_money']) ? '' : 'disabled' ?>" title="<?= !empty($enabledPayments['orange_money']) ? 'Orange Money Actif' : 'Orange Money désactivé par l\'administrateur' ?>">
                            <img src="<?= ASSETS_URL ?>/images/payments/orange_money.png" alt="Orange Money">
                            <span>Orange</span>
                        </div>

                        <div class="pd-pay-logo-item <?= !empty($enabledPayments['mobile_money']) ? '' : 'disabled' ?>" title="<?= !empty($enabledPayments['mobile_money']) ? 'MTN MoMo Actif' : 'MTN MoMo désactivé par l\'administrateur' ?>">
                            <img src="<?= ASSETS_URL ?>/images/payments/mtn_momo.svg" alt="MTN MoMo">
                            <span>MTN</span>
                        </div>

                        <div class="pd-pay-logo-item <?= !empty($enabledPayments['mobile_money']) ? '' : 'disabled' ?>" title="<?= !empty($enabledPayments['mobile_money']) ? 'Moov Money Actif' : 'Moov Money désactivé par l\'administrateur' ?>">
                            <img src="<?= ASSETS_URL ?>/images/payments/moov_money.svg" alt="Moov Money">
                            <span>Moov</span>
                        </div>

                        <div class="pd-pay-logo-item <?= !empty($enabledPayments['cash_on_delivery']) ? '' : 'disabled' ?>" title="<?= !empty($enabledPayments['cash_on_delivery']) ? 'Espèces à la livraison Actif' : 'Paiement à la livraison désactivé par l\'administrateur' ?>">
                            <img src="<?= ASSETS_URL ?>/images/payments/cash.png" alt="Espèces" style="border-radius:4px">
                            <span>Cash</span>
                        </div>
                    </div>
                </div>

                <div class="pd-widget-actions">
                    <span onclick="shareProduct()"><span style="font-size:1rem">🔗</span> Partager</span>
                    <?php if ($store['whatsapp']): ?>
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $store['whatsapp']) ?>" target="_blank" style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:4px">
                            <span>💬</span> Contact
                        </a>
                    <?php else: ?>
                        <span>💬 Contact</span>
                    <?php endif; ?>
                    <span>🛡️ Signaler</span>
                </div>

                <div class="pd-help-link" onclick="buyNowFromDetailModal()">
                    ❓ Comment acheter ?
                </div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<div class="powered-by">
    Propulsé par <a href="<?= SITE_URL ?>">PhoenixKA Shop</a> — Des solutions intelligentes pour booster votre réussite
</div>

<script>
let cart = [];
const currency = '<?= DEFAULT_CURRENCY ?>';

function addToCart(card) {
    const id = parseInt(card.dataset.id);
    const name = card.dataset.name;
    const price = parseFloat(card.dataset.price);
    const img = card.dataset.img;
    const existing = cart.find(i => i.id === id);
    if (existing) { existing.quantity++; }
    else { cart.push({ id, name, price, img, quantity: 1 }); }
    updateCart();
    // Quick feedback
    const btn = card.querySelector('.add-btn');
    btn.textContent = '✓ Ajouté !';
    setTimeout(() => btn.textContent = '🛒 Ajouter au panier', 1000);
}

function updateCart() {
    const count = cart.reduce((s, i) => s + i.quantity, 0);
    const total = cart.reduce((s, i) => s + i.price * i.quantity, 0);
    document.getElementById('cartCount').textContent = count;
    document.getElementById('cartBtn').style.display = count > 0 ? 'block' : 'none';
    document.getElementById('cartFooter').style.display = count > 0 ? 'block' : 'none';
    document.getElementById('cartTotal').textContent = total.toLocaleString('fr-FR') + ' ' + currency;

    const container = document.getElementById('cartItems');
    if (cart.length === 0) {
        container.innerHTML = '<div class="cart-empty">Votre panier est vide</div>';
        return;
    }
    container.innerHTML = cart.map((item, idx) => `
        <div class="cart-item">
            ${item.img ? `<img src="${item.img}" alt="">` : '<div style="width:60px;height:60px;background:var(--bg-elevated);border-radius:8px;display:flex;align-items:center;justify-content:center">📷</div>'}
            <div class="item-info">
                <h4>${item.name}</h4>
                <div class="item-price">${(item.price * item.quantity).toLocaleString('fr-FR')} ${currency}</div>
                <div class="qty-controls">
                    <button onclick="changeQty(${idx},-1)">−</button>
                    <span>${item.quantity}</span>
                    <button onclick="changeQty(${idx},1)">+</button>
                </div>
            </div>
            <button class="remove-item" onclick="removeItem(${idx})">✕</button>
        </div>
    `).join('');
}

function changeQty(idx, delta) {
    cart[idx].quantity += delta;
    if (cart[idx].quantity <= 0) cart.splice(idx, 1);
    updateCart();
}

function removeItem(idx) { cart.splice(idx, 1); updateCart(); }

function toggleCart() {
    document.getElementById('cartPanel').classList.toggle('open');
    document.getElementById('cartOverlay').classList.toggle('open');
}

function selectPaymentCard(val) {
    document.querySelectorAll('.payment-option-card').forEach(c => c.classList.remove('active-wave'));
    const card = document.getElementById('card_' + val);
    if (card && val === 'wave') {
        card.classList.add('active-wave');
    }
}

function showOrderForm() {
    const count = cart.reduce((s, i) => s + i.quantity, 0);
    const total = cart.reduce((s, i) => s + i.price * i.quantity, 0);
    
    document.getElementById('cartItemsInput').value = JSON.stringify(cart);
    
    const countEl = document.getElementById('modalCartCount');
    const totalEl = document.getElementById('modalCartTotal');
    if (countEl) countEl.textContent = count + ' article(s)';
    if (totalEl) totalEl.textContent = total.toLocaleString('fr-FR') + ' ' + currency;

    document.getElementById('orderModal').classList.add('active');
    toggleCart();
}

function closeOrderModal() { document.getElementById('orderModal').classList.remove('active'); }

// Product Detail View Functions (Screenshot Match)
let currentSelectedProduct = null;

function openProductDetail(card) {
    const id = parseInt(card.dataset.id);
    const name = card.dataset.name;
    const price = parseFloat(card.dataset.price);
    const compare = card.dataset.compare ? parseFloat(card.dataset.compare) : null;
    const desc = card.dataset.desc;
    const img = card.dataset.img;

    currentSelectedProduct = { id, name, price, compare, desc, img };

    document.getElementById('pdTitle').textContent = name;
    document.getElementById('pdFileName').textContent = name;
    document.getElementById('pdCurrentPrice').textContent = price.toLocaleString('fr-FR') + ' ' + currency;
    document.getElementById('pdSalesCount').textContent = Math.floor(Math.random() * 25 + 5) + 'x';
    
    const oldPriceEl = document.getElementById('pdOldPrice');
    if (compare && compare > price) {
        oldPriceEl.textContent = compare.toLocaleString('fr-FR') + ' ' + currency;
        oldPriceEl.style.display = 'inline';
    } else {
        oldPriceEl.style.display = 'none';
    }

    const descEl = document.getElementById('pdDescriptionText');
    if (desc && desc.trim().length > 0) {
        descEl.textContent = desc;
    } else {
        descEl.textContent = "Découvrez un produit exceptionnel conçu avec soin pour répondre à vos besoins avec la plus haute qualité et satisfaction garantie.";
    }

    const imgEl = document.getElementById('pdMainImg');
    if (img && img.trim().length > 0) {
        imgEl.src = img;
        imgEl.style.display = 'block';
    } else {
        imgEl.style.display = 'none';
    }

    document.getElementById('productDetailModal').classList.add('active');
}

function closeProductDetailModal() {
    document.getElementById('productDetailModal').classList.remove('active');
}

function buyNowFromDetailModal() {
    if (currentSelectedProduct) {
        const existing = cart.find(i => i.id === currentSelectedProduct.id);
        if (!existing) {
            cart.push({ 
                id: currentSelectedProduct.id, 
                name: currentSelectedProduct.name, 
                price: currentSelectedProduct.price, 
                img: currentSelectedProduct.img, 
                quantity: 1 
            });
            updateCart();
        }
        closeProductDetailModal();
        showOrderForm();
    }
}

function shareProduct() {
    if (navigator.share) {
        navigator.share({
            title: document.getElementById('pdTitle').textContent,
            url: window.location.href
        });
    } else {
        navigator.clipboard.writeText(window.location.href);
        alert('Lien du produit copié dans le presse-papier !');
    }
}

// Category filter
document.querySelectorAll('.cat-pill').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.cat-pill').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        filterProductsCombined();
    });
});

// Live Search Filter
function filterProductsBySearch() {
    filterProductsCombined();
}

function filterProductsCombined() {
    const activeCatBtn = document.querySelector('.cat-pill.active');
    const selectedCat = activeCatBtn ? activeCatBtn.dataset.cat : 'all';
    const searchQuery = (document.getElementById('storeSearchInput')?.value || '').toLowerCase().trim();

    document.querySelectorAll('.pro-card').forEach(card => {
        const catMatch = (selectedCat === 'all' || card.dataset.cat === selectedCat);
        const nameMatch = (card.dataset.name || '').toLowerCase().includes(searchQuery);
        const descMatch = (card.dataset.desc || '').toLowerCase().includes(searchQuery);
        const searchMatch = !searchQuery || nameMatch || descMatch;

        card.style.display = (catMatch && searchMatch) ? 'flex' : 'none';
    });
}

// Auto-open product detail if URL contains ?product=ID
window.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const prodId = params.get('product');
    if (prodId) {
        const card = document.querySelector(`.pro-card[data-id="${prodId}"]`);
        if (card) openProductDetail(card);
    }
});
</script>
<script src="<?= ASSETS_URL ?>/js/main.js"></script>
</body>
</html>
