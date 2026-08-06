<?php
// router.php - Local Development Router for PHP CLI Server

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$filePath = __DIR__ . $uri;

// 1. Direct file match (CSS, JS, images, uploaded files, or explicit .php)
if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    return false;
}

// 2. Directory root handling (e.g. /dashboard or /dashboard/ -> /dashboard/index.php)
if (is_dir($filePath)) {
    $indexFile = rtrim($filePath, '/') . '/index.php';
    if (file_exists($indexFile)) {
        require $indexFile;
        exit;
    }
}

// 3. Hide .php extensions in URLs (e.g. /auth/login -> /auth/login or /dashboard/orders -> /dashboard/orders)
if (file_exists($filePath . '.php') && !is_dir($filePath . '.php')) {
    require $filePath . '.php';
    exit;
}

// 4. Route /boutique/{slug} -> /boutique/index.php?store_slug=$1
if (preg_match('#^/boutique/([a-zA-Z0-9_-]+)#', $uri, $matches)) {
    $_GET['store_slug'] = $matches[1];
    require __DIR__ . '/boutique/index.php';
    exit;
}

// 5. Direct Store Slug URL e.g. /boutique-ci -> /boutique/index.php?store_slug=boutique-ci
$segments = array_values(array_filter(explode('/', $uri)));
if (!empty($segments[0])) {
    $firstDir = $segments[0];
    $reserved = ['assets', 'auth', 'dashboard', 'admin', 'uploads', 'includes', 'database', 'api', 'terms', 'privacy', 'download'];
    if (!in_array($firstDir, $reserved) && !file_exists(__DIR__ . '/' . $firstDir . '.php')) {
        $_GET['store_slug'] = $firstDir;
        require __DIR__ . '/boutique/index.php';
        exit;
    }
}

// Default fallback
return false;

