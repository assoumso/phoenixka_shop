<?php
/**
 * PhoenixKA Shop - Configuration principale
 */

// Mode debug (Passer à false en production sur votre hébergeur web)
define('DEBUG_MODE', $_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1');

// Configuration base de données
// Si vous êtes sur votre hébergeur web distant, ajustez ces 4 constantes :
if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1' || str_contains($_SERVER['HTTP_HOST'], '127.0.0.1:8000')) {
    // Configuration LOCALHOST (XAMPP / WAMP / Dev)
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'phoenixka_shop');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    // Configuration HÉBERGEUR DISTANT (cPanel / LWS / Hostinger / O2Switch)
    define('DB_HOST', 'localhost'); // ou le nom d'hôte fourni par votre hébergeur (ex: sql.hebergeur.com)
    define('DB_NAME', 'votre_nom_bdd'); // Nom de la base créée chez l'hébergeur
    define('DB_USER', 'votre_utilisateur_bdd'); // Utilisateur BDD distant
    define('DB_PASS', 'votre_mot_de_passe_bdd'); // Mot de passe BDD distant
}
define('DB_CHARSET', 'utf8mb4');

// Configuration du site
define('SITE_NAME', 'PhoenixKA Shop');
// Détection dynamique de l'URL du site
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$baseDir = $scriptName === '/' ? '' : $scriptName;
// Dans le cas de php -S (serveur de dev local), dirname($_SERVER['SCRIPT_NAME']) peut ne pas donner le bon dossier de base si on appelle directement à la racine
if (php_sapi_name() == 'cli-server') {
    define('SITE_URL', $protocol . $host);
} else {
    define('SITE_URL', $protocol . $host . $baseDir);
}
define('SITE_DESCRIPTION', 'Créez votre boutique en ligne en quelques minutes. Des solutions intelligentes pour booster votre réussite.');

// Chemins
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('INCLUDES_PATH', ROOT_PATH . 'includes' . DIRECTORY_SEPARATOR);
define('UPLOADS_PATH', ROOT_PATH . 'uploads' . DIRECTORY_SEPARATOR);
define('ASSETS_URL', SITE_URL . '/assets');
define('UPLOADS_URL', SITE_URL . '/uploads');

// Session
define('SESSION_LIFETIME', 86400 * 7); // 7 jours
define('SESSION_NAME', 'PHOENIXKA_SESSION');

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

// Démarrer session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

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
                die('Erreur de connexion: ' . $e->getMessage());
            }
            die('Erreur de connexion à la base de données.');
        }
    }
    return $pdo;
}
