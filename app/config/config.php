<?php
/**
 * Configuración General de la Aplicación
 */

// Cargar autoload de Composer
require_once __DIR__ . '/../../vendor/autoload.php';

// Cargar variables de entorno desde .env
if (file_exists(__DIR__ . '/../../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
    $dotenv->load();
}

// Configuración de la aplicación
define('APP_NAME', 'BIENIESTAR');
define('APP_VERSION', '2.1.0');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');
define('APP_DEBUG', ($_ENV['APP_DEBUG'] ?? 'true') === 'true');

// URLs
define('BASE_URL', $_ENV['BASE_URL'] ?? 'http://localhost/a/Bienestar/public');
define('ASSETS_URL', BASE_URL . '/assets');

// Rutas del sistema
define('ROOT_PATH', dirname(dirname(__DIR__)));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UPLOAD_PATH', PUBLIC_PATH . '/assets/uploads');

// Configuración de sesiones
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Zona horaria
date_default_timezone_set('America/Mexico_City');

// Manejo de errores
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Configuración de Google OAuth
define('GOOGLE_CLIENT_ID', $_ENV['GOOGLE_CLIENT_ID'] ?? '');
define('GOOGLE_CLIENT_SECRET', $_ENV['GOOGLE_CLIENT_SECRET'] ?? '');
define('GOOGLE_REDIRECT_URI', $_ENV['GOOGLE_REDIRECT_URI'] ?? '');

// Headers de seguridad
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// Cargar helpers
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/validation_helper.php';
require_once __DIR__ . '/../helpers/file_helper.php';

/**
 * Función helper para generar URLs
 */
function url($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Función helper para generar URLs de assets
 */
function asset($path = '') {
    $url = ASSETS_URL . '/' . ltrim($path, '/');
    if (preg_match('/\.(css|js)$/i', $path)) {
        $url .= '?v=' . APP_VERSION;
    }
    return $url;
}

// Clave de encriptación para mensajes de chat (AES-256-CBC, 32 bytes)
define('CHAT_ENCRYPTION_KEY', hash('sha256', 'bieniestar_chat_secret_k3y_2026', true));

// FIN DEL ARCHIVO - NO AGREGAR MÁS FUNCIONES AQUÍ