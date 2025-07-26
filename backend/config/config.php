<?php
// Restaurant Menu System - Auto-generated Configuration
// Generated on 2025-07-26 12:11:15

// Güvenlik: Direct access engelle
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'restaurant_menu');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Site ayarları
define('SITE_URL', 'https://localhost');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/backend/uploads/');

// Güvenlik ayarları
define('JWT_SECRET', 'cc229cd6407c7db25f626e92413bd647d0510b060fe6e1ffd0740a66a5112135');
define('CSRF_TOKEN_EXPIRE', 3600);
define('SESSION_EXPIRE', 86400);

// Dosya yükleme ayarları
define('MAX_FILE_SIZE', 5 * 1024 * 1024);
define('ALLOWED_IMAGE_TYPES', array('jpg', 'jpeg', 'png', 'webp'));

// Timezone
date_default_timezone_set('Europe/Istanbul');

// Include other config files
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/session_manager.php';
