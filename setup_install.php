<?php
// setup_install.php - Setup Installation Backend

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Prevent direct access if already installed
if (file_exists(__DIR__ . '/backend/config/installed.flag')) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Sistem zaten kurulmuş.'
    ]);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Geçersiz JSON verisi'
    ]);
    exit;
}

$action = $input['action'] ?? '';
$setupData = $input['setup_data'] ?? [];

try {
    switch ($action) {
        case 'create_database':
            echo json_encode(createDatabase($setupData));
            break;
            
        case 'create_tables':
            echo json_encode(createTables($setupData));
            break;
            
        case 'insert_sample_data':
            echo json_encode(insertSampleData($setupData));
            break;
            
        case 'create_admin':
            echo json_encode(createAdminUser($setupData));
            break;
            
        case 'write_config':
            echo json_encode(writeConfiguration($setupData));
            break;
            
        default:
            throw new Exception('Geçersiz action');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function getDatabaseConnection($config) {
    $host = $config['database']['host'] ?? 'localhost';
    $port = $config['database']['port'] ?? '3306';
    $dbname = $config['database']['name'];
    $username = $config['database']['user'];
    $password = $config['database']['pass'];
    
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    
    return new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
}

function createDatabase($setupData) {
    $config = $setupData['database'];
    $host = $config['host'] ?? 'localhost';
    $port = $config['port'] ?? '3306';
    $dbname = $config['name'];
    $username = $config['user'];
    $password = $config['pass'];
    
    // Connect without database first
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    return [
        'success' => true,
        'message' => "Veritabanı '{$dbname}' hazırlandı"
    ];
}

function createTables($setupData) {
    $pdo = getDatabaseConnection($setupData);
    
    // Read SQL file
    $sqlFile = __DIR__ . '/database.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception('Database SQL dosyası bulunamadı');
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split SQL into individual statements
    $statements = preg_split('/;\s*$/m', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement) || substr($statement, 0, 2) === '--') {
            continue;
        }
        
        try {
            $pdo->exec($statement);
        } catch (PDOException $e) {
            // Log error but continue with other statements
            error_log("SQL Error: " . $e->getMessage() . " - Statement: " . substr($statement, 0, 100));
        }
    }
    
    return [
        'success' => true,
        'message' => 'Veritabanı tabloları oluşturuldu'
    ];
}

function insertSampleData($setupData) {
    $pdo = getDatabaseConnection($setupData);
    
    // Insert restaurant data
    $restaurant = $setupData['restaurant'];
    $slug = createSlug($restaurant['name']);
    
    $stmt = $pdo->prepare("
        INSERT INTO restaurants (name, slug, phone, email, address, description, is_active, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
    ");
    
    $stmt->execute([
        $restaurant['name'],
        $slug,
        $restaurant['phone'] ?? null,
        $restaurant['email'] ?? null,
        $restaurant['address'] ?? null,
        $restaurant['description'] ?? null
    ]);
    
    $restaurantId = $pdo->lastInsertId();
    
    // Set default theme
    if (!empty($restaurant['theme_id'])) {
        $stmt = $pdo->prepare("
            INSERT INTO restaurant_themes (restaurant_id, theme_id, is_default, created_at) 
            VALUES (?, ?, 1, NOW())
        ");
        $stmt->execute([$restaurantId, $restaurant['theme_id']]);
    }
    
    // Insert basic settings
    $settings = [
        ['default_currency', $restaurant['currency'] ?? 'TL'],
        ['default_language', $restaurant['language'] ?? 'tr'],
        ['restaurant_open_hours', json_encode([
            'monday' => '09:00-23:00',
            'tuesday' => '09:00-23:00', 
            'wednesday' => '09:00-23:00',
            'thursday' => '09:00-23:00',
            'friday' => '09:00-23:00',
            'saturday' => '09:00-23:00',
            'sunday' => '09:00-23:00'
        ])],
        ['enable_waiter_call', 'true'],
        ['max_waiter_calls_per_hour', '10'],
        ['featured_items_limit', '6']
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO settings (restaurant_id, setting_key, setting_value, setting_type, is_public, created_at) 
        VALUES (?, ?, ?, 'string', 1, NOW())
    ");
    
    foreach ($settings as $setting) {
        $stmt->execute([$restaurantId, $setting[0], $setting[1]]);
    }
    
    // Store restaurant ID for admin creation
    $setupData['restaurant_id'] = $restaurantId;
    
    return [
        'success' => true,
        'message' => 'Restoran verileri eklendi',
        'restaurant_id' => $restaurantId
    ];
}

function createAdminUser($setupData) {
    $pdo = getDatabaseConnection($setupData);
    
    $admin = $setupData['admin'];
    $restaurantId = $setupData['restaurant_id'] ?? 1;
    
    // Validate admin data
    if (empty($admin['username']) || empty($admin['password']) || empty($admin['email'])) {
        throw new Exception('Admin bilgileri eksik');
    }
    
    // Validate email
    if (!filter_var($admin['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Geçersiz email adresi');
    }
    
    // Validate password strength
    if (!isStrongPassword($admin['password'])) {
        throw new Exception('Şifre yeterince güçlü değil');
    }
    
    // Hash password
    $hashedPassword = password_hash($admin['password'], PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3,
    ]);
    
    // Insert admin user
    $stmt = $pdo->prepare("
        INSERT INTO admin_users (
            restaurant_id, username, password, email, full_name, role, 
            is_active, created_at
        ) VALUES (?, ?, ?, ?, ?, 'super_admin', 1, NOW())
    ");
    
    $stmt->execute([
        $restaurantId,
        $admin['username'],
        $hashedPassword,
        $admin['email'],
        $admin['fullname']
    ]);
    
    return [
        'success' => true,
        'message' => 'Admin hesabı oluşturuldu'
    ];
}

function writeConfiguration($setupData) {
    $config = $setupData['database'];
    
    // Generate secure keys
    $jwtSecret = bin2hex(random_bytes(32));
    $csrfSecret = bin2hex(random_bytes(32));
    
    // Determine site URL
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $siteUrl = $protocol . $host;
    
    // Create .env file content
    $envContent = "# Restaurant Menu System - Environment Configuration\n";
    $envContent .= "# Generated on " . date('Y-m-d H:i:s') . "\n\n";
    
    $envContent .= "# Environment\n";
    $envContent .= "ENVIRONMENT=production\n\n";
    
    $envContent .= "# Database Configuration\n";
    $envContent .= "DB_HOST={$config['host']}\n";
    $envContent .= "DB_NAME={$config['name']}\n";
    $envContent .= "DB_USER={$config['user']}\n";
    $envContent .= "DB_PASS={$config['pass']}\n\n";
    
    $envContent .= "# Site Configuration\n";
    $envContent .= "SITE_URL={$siteUrl}\n\n";
    
    $envContent .= "# Security\n";
    $envContent .= "JWT_SECRET={$jwtSecret}\n";
    $envContent .= "CSRF_SECRET={$csrfSecret}\n\n";
    
    $envContent .= "# File Upload Limits\n";
    $envContent .= "MAX_FILE_SIZE=5242880\n";
    $envContent .= "UPLOAD_PATH=/var/www/html/restaurant-menu/backend/uploads/\n\n";
    
    $envContent .= "# Rate Limiting\n";
    $envContent .= "RATE_LIMIT_REQUESTS=100\n";
    $envContent .= "RATE_LIMIT_WINDOW=3600\n\n";
    
    $envContent .= "# Session Configuration\n";
    $envContent .= "SESSION_LIFETIME=86400\n\n";
    
    $envContent .= "# Feature Flags\n";
    $envContent .= "ENABLE_WAITER_CALLS=true\n";
    $envContent .= "ENABLE_NOTIFICATIONS=true\n";
    $envContent .= "ENABLE_THEMES=true\n";
    $envContent .= "ENABLE_ANALYTICS=true\n";
    $envContent .= "ENABLE_PWA=true\n";
    $envContent .= "ENABLE_OFFLINE_MODE=true\n";
    
    // Write .env file
    $envFile = __DIR__ . '/.env';
    if (file_put_contents($envFile, $envContent) === false) {
        throw new Exception('.env dosyası yazılamadı');
    }
    
    // Create .htaccess for security
    $htaccessContent = "# Restaurant Menu System Security\n";
    $htaccessContent .= "RewriteEngine On\n\n";
    $htaccessContent .= "# Hide sensitive files\n";
    $htaccessContent .= "<Files \".env\">\n";
    $htaccessContent .= "    Require all denied\n";
    $htaccessContent .= "</Files>\n\n";
    $htaccessContent .= "<Files \"setup*.php\">\n";
    $htaccessContent .= "    Require all denied\n";
    $htaccessContent .= "</Files>\n\n";
    $htaccessContent .= "<Files \"database.sql\">\n";
    $htaccessContent .= "    Require all denied\n";
    $htaccessContent .= "</Files>\n";
    
    file_put_contents(__DIR__ . '/.htaccess', $htaccessContent);
    
    // Create installed flag
    $flagContent = json_encode([
        'installed_at' => date('c'),
        'version' => '1.0.0',
        'installer_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    if (!is_dir(__DIR__ . '/backend/config')) {
        mkdir(__DIR__ . '/backend/config', 0755, true);
    }
    
    file_put_contents(__DIR__ . '/backend/config/installed.flag', $flagContent);
    
    // Create basic config.php if it doesn't exist
    $configPath = __DIR__ . '/backend/config/config.php';
    if (!file_exists($configPath)) {
        $configContent = "<?php\n";
        $configContent .= "// Auto-generated configuration\n";
        $configContent .= "define('SECURE_ACCESS', true);\n";
        $configContent .= "require_once __DIR__ . '/../../vendor/autoload.php';\n";
        $configContent .= "// Load environment variables\n";
        $configContent .= "if (file_exists(__DIR__ . '/../../.env')) {\n";
        $configContent .= "    \$lines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);\n";
        $configContent .= "    foreach (\$lines as \$line) {\n";
        $configContent .= "        if (strpos(trim(\$line), '#') === 0) continue;\n";
        $configContent .= "        list(\$name, \$value) = explode('=', \$line, 2);\n";
        $configContent .= "        \$_ENV[trim(\$name)] = trim(\$value);\n";
        $configContent .= "    }\n";
        $configContent .= "}\n";
        
        file_put_contents($configPath, $configContent);
    }
    
    return [
        'success' => true,
        'message' => 'Konfigürasyon dosyaları oluşturuldu'
    ];
}

// Utility functions
function createSlug($text) {
    $turkish = ['ç', 'ğ', 'ı', 'ö', 'ş', 'ü', 'Ç', 'Ğ', 'I', 'İ', 'Ö', 'Ş', 'Ü'];
    $english = ['c', 'g', 'i', 'o', 's', 'u', 'c', 'g', 'i', 'i', 'o', 's', 'u'];
    
    $text = str_replace($turkish, $english, $text);
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    $text = trim($text, '-');
    
    return substr($text, 0, 100);
}

function isStrongPassword($password) {
    return strlen($password) >= 8 &&
           preg_match('/[a-z]/', $password) &&
           preg_match('/[A-Z]/', $password) &&
           preg_match('/[0-9]/', $password) &&
           preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password);
}
?>