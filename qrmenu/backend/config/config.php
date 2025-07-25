<?php
// backend/config/config.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'qrmenu');
define('DB_USER', 'root'); // Değiştirilmeli
define('DB_PASS', ''); // Değiştirilmeli
//define('DB_CHARSET', 'utf8mb4');

// Site ayarları
define('SITE_URL', 'https://localhost/qrmenunew/qrmenu');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

// Güvenlik ayarları
define('JWT_SECRET', 'your-secret-key-change-this'); // Değiştirilmeli
define('CSRF_TOKEN_EXPIRE', 3600); // 1 saat
define('SESSION_EXPIRE', 86400); // 24 saat

// Dosya yükleme ayarları
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'webp']);

// Hata raporlama
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Europe/Istanbul');

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}
?>