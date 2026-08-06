<?php
/**
 * PhoenixKA Shop - Configuration principale
 */

// Output buffering pour éviter les erreurs "headers already sent"
if (ob_get_level() === 0) {
    ob_start();
}

// Session configuration & auto-start at top
define('SESSION_LIFETIME', 86400 * 7); // 7 jours
define('SESSION_NAME', 'PHOENIXKA_SESSION');
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_name(SESSION_NAME);
    session_start();
}

// Mode debug (Passer à false en production sur votre hébergeur web)
$hostName = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('DEBUG_MODE', $hostName === 'localhost' || $hostName === '127.0.0.1');

// Configuration base de données
$envDbHost = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? null);
$envDbName = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? null);
$envDbUser = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? null);
$envDbPass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : ($_ENV['DB_PASS'] ?? null);

if ($envDbHost && $envDbName && $envDbUser) {
    // Variables d'environnement Render / Vercel / Cloud
    define('DB_HOST', $envDbHost);
    define('DB_NAME', $envDbName);
    define('DB_USER', $envDbUser);
    define('DB_PASS', $envDbPass ?? '');
} elseif (in_array($hostName, ['localhost', '127.0.0.1']) || str_contains($hostName, '127.0.0.1:8000')) {
    // Configuration LOCALHOST (XAMPP / WAMP / Dev)
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'phoenixka_shop');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    // Configuration HÉBERGEUR DISTANT (Render / cPanel / LWS / Hostinger)
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'votre_nom_bdd');
    define('DB_USER', 'votre_utilisateur_bdd');
    define('DB_PASS', 'votre_mot_de_passe_bdd');
}
define('DB_CHARSET', 'utf8mb4');

// Configuration du site
define('SITE_NAME', 'PhoenixKA Shop');

// Détection dynamique propre de l'URL du site (HTTPS Reverse Proxy / Cloud Compatible)
$isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
           (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
$protocol = $isHttps ? 'https://' : 'http://';

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$baseDir = ($scriptDir === '/' || $scriptDir === '.' || $scriptDir === '\\') ? '' : rtrim($scriptDir, '/');

if (php_sapi_name() === 'cli-server') {
    define('SITE_URL', rtrim($protocol . $hostName, '/'));
} else {
    define('SITE_URL', rtrim($protocol . $hostName . $baseDir, '/'));
}
define('SITE_DESCRIPTION', 'Créez votre boutique en ligne en quelques minutes. Des solutions intelligentes pour booster votre réussite.');

// Chemins
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('INCLUDES_PATH', ROOT_PATH . 'includes' . DIRECTORY_SEPARATOR);
define('UPLOADS_PATH', ROOT_PATH . 'uploads' . DIRECTORY_SEPARATOR);
define('ASSETS_URL', SITE_URL . '/assets');
define('UPLOADS_URL', SITE_URL . '/uploads');

// Devise par défaut
define('DEFAULT_CURRENCY', 'FCFA');

// Limites uploads
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);

// Couleurs du thème (extraites du logo)
define('THEME_PRIMARY', '#D4A520');      // Or doré
define('THEME_PRIMARY_DARK', '#B8860B'); // Or foncé
define('THEME_PRIMARY_LIGHT', '#FFD700'); // Or clair
define('THEME_BG_DARK', '#0A0A0A');      // Noir profond
define('THEME_BG_SURFACE', '#1A1A1A');   // Surface sombre

// Gestion des erreurs
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('Africa/Douala');

// Connexion PDO
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die('Erreur de connexion BDD: ' . $e->getMessage());
            }
            die("<div style='font-family:sans-serif;padding:50px 20px;text-align:center;background:#0F172A;color:#FFF;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center'>
                <div style='background:#1E293B;border:1px solid #334155;padding:30px;border-radius:18px;max-width:550px'>
                    <h2 style='color:#EAB308;margin-top:0'>⚠️ Connexion Base de Données requise</h2>
                    <p style='color:#94A3B8;line-height:1.6'>Votre application PHP sur Render fonctionne, mais les identifiants de la base de données MySQL doivent être configurés dans les variables d'environnement (DB_HOST, DB_NAME, DB_USER, DB_PASS).</p>
                    <p style='font-size:0.85rem;color:#64748B'>Détail technique : " . htmlspecialchars($e->getMessage()) . "</p>
                </div>
            </div>");
        }
    }
    return $pdo;
}
