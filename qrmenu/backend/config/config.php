<?php
// backend/config/config.php

// Güvenlik: Direct access engelle
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}

// Error reporting - production'da kapatılmalı
if (isset($_ENV['ENVIRONMENT']) && $_ENV['ENVIRONMENT'] === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Database Configuration
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'qrmenu');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', 'utf8mb4');

// Site ayarları
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('SITE_URL', $_ENV['SITE_URL'] ?? $protocol . $host);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/backend/uploads/');

// Güvenlik ayarları
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'change-this-secret-key-in-production-' . hash('sha256', __FILE__));
define('CSRF_TOKEN_EXPIRE', 3600); // 1 saat
define('SESSION_EXPIRE', 86400); // 24 saat
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 dakika

// Dosya yükleme ayarları
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'webp']);
define('ALLOWED_MIME_TYPES', [
    'image/jpeg',
    'image/png', 
    'image/webp',
    'image/gif'
]);

// Rate limiting
define('RATE_LIMIT_REQUESTS', 100); // per window
define('RATE_LIMIT_WINDOW', 3600); // 1 hour in seconds

// Timezone
date_default_timezone_set('Europe/Istanbul');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
ini_set('session.name', 'RESTAURANT_ADMIN_SESSION');

// Upload directory oluştur
if (!is_dir(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}

$subDirs = ['images', 'logos', 'themes'];
foreach ($subDirs as $dir) {
    $fullPath = UPLOAD_PATH . $dir . '/';
    if (!is_dir($fullPath)) {
        mkdir($fullPath, 0755, true);
    }
}

// CORS headers - güvenlik için restrict edilmeli
$allowedOrigins = [
    SITE_URL,
    'http://localhost',
    'http://localhost:3000',
    'http://127.0.0.1'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: ' . SITE_URL);
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');
header('Access-Control-Allow-Credentials: true');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Content Security Policy
$csp = "default-src 'self'; " .
       "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com; " .
       "style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; " .
       "img-src 'self' data: blob:; " .
       "font-src 'self' https://fonts.gstatic.com; " .
       "connect-src 'self'; " .
       "frame-ancestors 'self'";

header('Content-Security-Policy: ' . $csp);

// OPTIONS request için early return
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// IP Address helper
function getClientIP() {
    $headers = [
        'HTTP_CF_CONNECTING_IP',     // Cloudflare
        'HTTP_CLIENT_IP',            // Proxy
        'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
        'HTTP_X_FORWARDED',          // Proxy
        'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
        'HTTP_FORWARDED_FOR',        // Proxy
        'HTTP_FORWARDED',            // Proxy
        'REMOTE_ADDR'                // Standard
    ];
    
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ips = explode(',', $_SERVER[$header]);
            $ip = trim($ips[0]);
            
            // Validate IP
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

// Log function
function logActivity($level, $message, $context = []) {
    $logDir = __DIR__ . '/../logs/';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . 'app-' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = getClientIP();
    $contextStr = !empty($context) ? json_encode($context) : '';
    
    $logEntry = "[{$timestamp}] [{$level}] [{$ip}] {$message} {$contextStr}" . PHP_EOL;
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Global exception handler
set_exception_handler(function($exception) {
    logActivity('ERROR', 'Uncaught exception: ' . $exception->getMessage(), [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ]);
    
    http_response_code(500);
    
    if (ini_get('display_errors')) {
        echo json_encode([
            'success' => false,
            'message' => 'Internal server error: ' . $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Internal server error occurred'
        ]);
    }
    exit;
});

// Global error handler
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    
    $levels = [
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING', 
        E_NOTICE => 'NOTICE',
        E_USER_ERROR => 'ERROR',
        E_USER_WARNING => 'WARNING',
        E_USER_NOTICE => 'NOTICE'
    ];
    
    $level = $levels[$severity] ?? 'ERROR';
    
    logActivity($level, $message, [
        'file' => $file,
        'line' => $line
    ]);
    
    if ($severity === E_ERROR || $severity === E_USER_ERROR) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Server error occurred'
        ]);
        exit;
    }
});

// Register shutdown function
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        logActivity('FATAL', 'Fatal error: ' . $error['message'], [
            'file' => $error['file'],
            'line' => $error['line']
        ]);
        
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Fatal server error occurred'
            ]);
        }
    }
});

// Auto-include required files
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/session_manager.php';

// Environment-specific settings
if (isset($_ENV['ENVIRONMENT'])) {
    switch ($_ENV['ENVIRONMENT']) {
        case 'development':
            ini_set('display_errors', 1);
            error_reporting(E_ALL);
            break;
            
        case 'staging':
            ini_set('display_errors', 0);
            error_reporting(E_ALL);
            break;
            
        case 'production':
            ini_set('display_errors', 0);
            error_reporting(0);
            break;
    }
}

// Maintenance mode check
if (file_exists(__DIR__ . '/../maintenance.flag')) {
    http_response_code(503);
    header('Retry-After: 3600'); // 1 hour
    echo json_encode([
        'success' => false,
        'message' => 'Sistem bakımda. Lütfen daha sonra tekrar deneyin.',
        'maintenance' => true
    ]);
    exit;
}

// Log request
logActivity('INFO', 'Request: ' . $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'], [
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    'referer' => $_SERVER['HTTP_REFERER'] ?? ''
]);
?>