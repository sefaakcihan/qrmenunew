<?php
// setup_install.php - Setup Installation Backend (FINAL FIXED)

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Prevent direct access if already installed
if (file_exists(__DIR__ . '/backend/config/installed.flag')) {
    http_response_code(403);
    echo json_encode(array(
        'success' => false,
        'message' => 'Sistem zaten kurulmuş.'
    ));
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(array(
        'success' => false,
        'message' => 'Geçersiz JSON verisi'
    ));
    exit;
}

$action = isset($input['action']) ? $input['action'] : '';
$setupData = isset($input['setup_data']) ? $input['setup_data'] : array();

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
    echo json_encode(array(
        'success' => false,
        'message' => $e->getMessage()
    ));
}

function getDatabaseConnection($config) {
    $host = isset($config['database']['host']) ? $config['database']['host'] : 'localhost';
    $port = isset($config['database']['port']) ? $config['database']['port'] : '3306';
    $dbname = $config['database']['name'];
    $username = $config['database']['user'];
    $password = $config['database']['pass'];
    
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    
    $options = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    );
    
    return new PDO($dsn, $username, $password, $options);
}

function createDatabase($setupData) {
    $config = $setupData['database'];
    $host = isset($config['host']) ? $config['host'] : 'localhost';
    $port = isset($config['port']) ? $config['port'] : '3306';
    $dbname = $config['name'];
    $username = $config['user'];
    $password = $config['pass'];
    
    // Connect without database first
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $options = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    );
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    return array(
        'success' => true,
        'message' => "Veritabanı '{$dbname}' hazırlandı"
    );
}

function createTables($setupData) {
    $pdo = getDatabaseConnection($setupData);
    
    // Enable strict mode and error handling
    $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
    $pdo->exec("SET SESSION foreign_key_checks = 0");
    
    try {
        // Define table creation SQL inline to avoid file dependency issues
        $tables = array(
            'restaurants' => "
                CREATE TABLE IF NOT EXISTS restaurants (
                    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                    name VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) UNIQUE NOT NULL,
                    logo VARCHAR(500),
                    address TEXT,
                    phone VARCHAR(50),
                    email VARCHAR(255),
                    website VARCHAR(255),
                    description TEXT,
                    business_hours JSON,
                    social_media JSON,
                    settings JSON,
                    is_active BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    
                    INDEX idx_slug (slug),
                    INDEX idx_active (is_active)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            'themes' => "
                CREATE TABLE IF NOT EXISTS themes (
                    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                    name VARCHAR(100) NOT NULL,
                    slug VARCHAR(50) UNIQUE NOT NULL,
                    description TEXT,
                    primary_color VARCHAR(7) DEFAULT '#C8102E',
                    secondary_color VARCHAR(7) DEFAULT '#FFD700',
                    accent_color VARCHAR(7) DEFAULT '#2E8B57',
                    bg_color VARCHAR(7) DEFAULT '#F8FAFC',
                    text_color VARCHAR(7) DEFAULT '#1E293B',
                    card_bg VARCHAR(7) DEFAULT '#FFFFFF',
                    css_variables JSON,
                    custom_css TEXT,
                    thumbnail VARCHAR(500),
                    preview_images JSON,
                    is_active BOOLEAN DEFAULT TRUE,
                    is_premium BOOLEAN DEFAULT FALSE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    
                    INDEX idx_slug (slug),
                    INDEX idx_active (is_active)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            'restaurant_themes' => "
                CREATE TABLE IF NOT EXISTS restaurant_themes (
                    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                    restaurant_id INT UNSIGNED NOT NULL,
                    theme_id INT UNSIGNED NOT NULL,
                    custom_css TEXT,
                    custom_variables JSON,
                    is_default BOOLEAN DEFAULT FALSE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    
                    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
                    FOREIGN KEY (theme_id) REFERENCES themes(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_restaurant_theme (restaurant_id, theme_id),
                    INDEX idx_default (restaurant_id, is_default)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            'categories' => "
                CREATE TABLE IF NOT EXISTS categories (
                    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                    restaurant_id INT UNSIGNED NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) NOT NULL,
                    description TEXT,
                    icon VARCHAR(100),
                    image VARCHAR(500),
                    sort_order INT DEFAULT 0,
                    status ENUM('active', 'inactive') DEFAULT 'active',
                    meta_title VARCHAR(255),
                    meta_description TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    
                    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_restaurant_slug (restaurant_id, slug),
                    INDEX idx_restaurant_status (restaurant_id, status),
                    INDEX idx_sort_order (sort_order)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            'menu_items' => "
                CREATE TABLE IF NOT EXISTS menu_items (
                    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                    category_id INT UNSIGNED NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) NOT NULL,
                    description TEXT,
                    short_description VARCHAR(500),
                    price DECIMAL(10,2) NOT NULL,
                    old_price DECIMAL(10,2) NULL,
                    image VARCHAR(500),
                    gallery JSON,
                    ingredients TEXT,
                    allergens VARCHAR(500),
                    nutritional_info JSON,
                    calories INT UNSIGNED,
                    prep_time INT UNSIGNED COMMENT 'Preparation time in minutes',
                    spice_level ENUM('none', 'mild', 'medium', 'hot', 'very_hot') DEFAULT 'none',
                    dietary_info SET('vegetarian', 'vegan', 'gluten_free', 'dairy_free', 'halal', 'kosher') DEFAULT '',
                    is_available BOOLEAN DEFAULT TRUE,
                    is_featured BOOLEAN DEFAULT FALSE,
                    is_popular BOOLEAN DEFAULT FALSE,
                    is_new BOOLEAN DEFAULT FALSE,
                    sort_order INT DEFAULT 0,
                    view_count INT UNSIGNED DEFAULT 0,
                    meta_title VARCHAR(255),
                    meta_description TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    
                    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
                    INDEX idx_category_available (category_id, is_available),
                    INDEX idx_featured (is_featured),
                    INDEX idx_popular (is_popular),
                    INDEX idx_sort_order (sort_order)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            'tables' => "
                CREATE TABLE IF NOT EXISTS tables (
                    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                    restaurant_id INT UNSIGNED NOT NULL,
                    table_number VARCHAR(50) NOT NULL,
                    table_name VARCHAR(100),
                    qr_code VARCHAR(500) UNIQUE NOT NULL,
                    capacity INT UNSIGNED DEFAULT 4,
                    location VARCHAR(100) COMMENT 'Indoor, Outdoor, VIP, etc.',
                    status ENUM('active', 'inactive', 'maintenance', 'reserved') DEFAULT 'active',
                    notes TEXT,
                    last_used TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    
                    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_restaurant_table (restaurant_id, table_number),
                    UNIQUE KEY unique_qr_code (qr_code),
                    INDEX idx_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            'waiter_calls' => "
                CREATE TABLE IF NOT EXISTS waiter_calls (
                    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                    table_id INT UNSIGNED NOT NULL,
                    message TEXT,
                    call_type ENUM('service', 'bill', 'complaint', 'assistance', 'other') DEFAULT 'service',
                    status ENUM('pending', 'acknowledged', 'completed', 'cancelled') DEFAULT 'pending',
                    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
                    assigned_to INT UNSIGNED NULL,
                    response_message TEXT,
                    customer_rating TINYINT UNSIGNED NULL,
                    customer_feedback TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    acknowledged_at TIMESTAMP NULL,
                    completed_at TIMESTAMP NULL,
                    
                    FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE CASCADE,
                    INDEX idx_status (status),
                    INDEX idx_priority (priority),
                    INDEX idx_created_at (created_at),
                    INDEX idx_table_status (table_id, status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            'admin_users' => "
                CREATE TABLE IF NOT EXISTS admin_users (
                    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                    restaurant_id INT UNSIGNED,
                    username VARCHAR(50) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    full_name VARCHAR(255) NOT NULL,
                    avatar VARCHAR(500),
                    role ENUM('super_admin', 'admin', 'manager', 'staff') DEFAULT 'admin',
                    permissions JSON,
                    phone VARCHAR(50),
                    last_login TIMESTAMP NULL,
                    last_ip VARCHAR(45),
                    login_attempts TINYINT UNSIGNED DEFAULT 0,
                    locked_until TIMESTAMP NULL,
                    email_verified_at TIMESTAMP NULL,
                    two_factor_secret VARCHAR(32),
                    two_factor_enabled BOOLEAN DEFAULT FALSE,
                    is_active BOOLEAN DEFAULT TRUE,
                    notes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    deleted_at TIMESTAMP NULL,
                    
                    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE SET NULL,
                    INDEX idx_username (username),
                    INDEX idx_email (email),
                    INDEX idx_restaurant_active (restaurant_id, is_active),
                    INDEX idx_role (role)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            'settings' => "
                CREATE TABLE IF NOT EXISTS settings (
                    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                    restaurant_id INT UNSIGNED,
                    setting_key VARCHAR(100) NOT NULL,
                    setting_value TEXT,
                    setting_type ENUM('string', 'number', 'boolean', 'json', 'text', 'email', 'url') DEFAULT 'string',
                    is_global BOOLEAN DEFAULT FALSE,
                    is_public BOOLEAN DEFAULT FALSE,
                    description TEXT,
                    validation_rules JSON,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    
                    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_setting (restaurant_id, setting_key),
                    INDEX idx_global (is_global),
                    INDEX idx_public (is_public)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        
        // Create tables in dependency order
        foreach ($tables as $tableName => $sql) {
            $pdo->exec($sql);
            error_log("Created table: $tableName");
        }
        
        // Insert default themes
        insertDefaultThemes($pdo);
        
        // Re-enable foreign key checks
        $pdo->exec("SET SESSION foreign_key_checks = 1");
        
        return array(
            'success' => true,
            'message' => 'Veritabanı tabloları oluşturuldu'
        );
        
    } catch (PDOException $e) {
        $pdo->exec("SET SESSION foreign_key_checks = 1");
        throw new Exception('Tablo oluşturma hatası: ' . $e->getMessage());
    }
}

function insertDefaultThemes($pdo) {
    // Check if themes already exist
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM themes");
    $result = $stmt->fetch();
    if ($result['count'] > 0) {
        return; // Themes already exist
    }
    
    $themes = array(
        array(
            'name' => 'Classic Restaurant',
            'slug' => 'classic',
            'description' => 'Geleneksel ve sıcak restoran teması',
            'primary_color' => '#C8102E',
            'secondary_color' => '#FFD700',
            'accent_color' => '#2E8B57',
            'css_variables' => json_encode(array(
                'shadow' => '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
                'border_radius' => '0.5rem',
                'font_family' => 'Inter, sans-serif'
            ))
        ),
        array(
            'name' => 'Modern Minimalist',
            'slug' => 'modern',
            'description' => 'Modern ve minimalist tasarım',
            'primary_color' => '#667EEA',
            'secondary_color' => '#764BA2',
            'accent_color' => '#F093FB',
            'css_variables' => json_encode(array(
                'shadow' => '0 10px 25px -3px rgba(0, 0, 0, 0.1)',
                'border_radius' => '1rem',
                'font_family' => 'Inter, sans-serif'
            ))
        ),
        array(
            'name' => 'Dark Elegance',
            'slug' => 'dark-elegance',
            'description' => 'Lüks ve koyu tema',
            'primary_color' => '#D4AF37',
            'secondary_color' => '#C0392B',
            'accent_color' => '#2ECC71',
            'bg_color' => '#0D1117',
            'text_color' => '#F8F9FA',
            'card_bg' => '#21262D',
            'css_variables' => json_encode(array(
                'shadow' => '0 4px 6px -1px rgba(255, 255, 255, 0.1)',
                'border_radius' => '0.75rem',
                'font_family' => 'Inter, sans-serif'
            ))
        ),
        array(
            'name' => 'Colorful Cafe',
            'slug' => 'colorful',
            'description' => 'Renkli ve enerjik kafe teması',
            'primary_color' => '#FF6B6B',
            'secondary_color' => '#4ECDC4',
            'accent_color' => '#45B7D1',
            'bg_color' => '#FFF8E1',
            'css_variables' => json_encode(array(
                'shadow' => '0 8px 32px rgba(31, 38, 135, 0.37)',
                'border_radius' => '1.25rem',
                'font_family' => 'Inter, sans-serif'
            ))
        )
    );
    
    $stmt = $pdo->prepare("
        INSERT INTO themes (name, slug, description, primary_color, secondary_color, accent_color, bg_color, text_color, card_bg, css_variables, is_active) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");
    
    foreach ($themes as $theme) {
        $bg_color = isset($theme['bg_color']) ? $theme['bg_color'] : '#F8FAFC';
        $text_color = isset($theme['text_color']) ? $theme['text_color'] : '#1E293B';
        $card_bg = isset($theme['card_bg']) ? $theme['card_bg'] : '#FFFFFF';
        
        $stmt->execute(array(
            $theme['name'],
            $theme['slug'],
            $theme['description'],
            $theme['primary_color'],
            $theme['secondary_color'],
            $theme['accent_color'],
            $bg_color,
            $text_color,
            $card_bg,
            $theme['css_variables']
        ));
    }
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
    
    $phone = isset($restaurant['phone']) ? $restaurant['phone'] : null;
    $email = isset($restaurant['email']) ? $restaurant['email'] : null;
    $address = isset($restaurant['address']) ? $restaurant['address'] : null;
    $description = isset($restaurant['description']) ? $restaurant['description'] : null;
    
    $stmt->execute(array(
        $restaurant['name'],
        $slug,
        $phone,
        $email,
        $address,
        $description
    ));
    
    $restaurantId = $pdo->lastInsertId();
    
    // Set default theme
    if (!empty($restaurant['theme_id'])) {
        $stmt = $pdo->prepare("
            INSERT INTO restaurant_themes (restaurant_id, theme_id, is_default, created_at) 
            VALUES (?, ?, 1, NOW())
        ");
        $stmt->execute(array($restaurantId, $restaurant['theme_id']));
    }
    
    // Insert basic categories
    $categories = array(
        array('name' => 'Başlangıçlar', 'slug' => 'baslangiclar', 'icon' => '🥗', 'sort_order' => 1),
        array('name' => 'Ana Yemekler', 'slug' => 'ana-yemekler', 'icon' => '🍖', 'sort_order' => 2),
        array('name' => 'Tatlılar', 'slug' => 'tatlilar', 'icon' => '🍰', 'sort_order' => 3),
        array('name' => 'İçecekler', 'slug' => 'icecekler', 'icon' => '🥤', 'sort_order' => 4)
    );
    
    $categoryStmt = $pdo->prepare("
        INSERT INTO categories (restaurant_id, name, slug, icon, sort_order, status, created_at)
        VALUES (?, ?, ?, ?, ?, 'active', NOW())
    ");
    
    foreach ($categories as $category) {
        $categoryStmt->execute(array(
            $restaurantId,
            $category['name'],
            $category['slug'],
            $category['icon'],
            $category['sort_order']
        ));
    }
    
    // Insert sample tables
    $tables = array(
        array('number' => '1', 'capacity' => 4, 'location' => 'Indoor'),
        array('number' => '2', 'capacity' => 2, 'location' => 'Indoor'),
        array('number' => '3', 'capacity' => 6, 'location' => 'Outdoor'),
        array('number' => '4', 'capacity' => 4, 'location' => 'VIP')
    );
    
    $tableStmt = $pdo->prepare("
        INSERT INTO tables (restaurant_id, table_number, table_name, qr_code, capacity, location, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
    ");
    
    foreach ($tables as $table) {
        $qrCode = 'QR_TABLE_' . strtoupper(uniqid()) . '_' . time();
        $tableStmt->execute(array(
            $restaurantId,
            $table['number'],
            'Masa ' . $table['number'],
            $qrCode,
            $table['capacity'],
            $table['location']
        ));
    }
    
    // Insert basic settings
    $settings = array(
        array('default_currency', isset($restaurant['currency']) ? $restaurant['currency'] : 'TL', 'string'),
        array('default_language', isset($restaurant['language']) ? $restaurant['language'] : 'tr', 'string'),
        array('enable_waiter_call', 'true', 'boolean'),
        array('max_waiter_calls_per_hour', '10', 'number'),
        array('featured_items_limit', '6', 'number')
    );
    
    $settingStmt = $pdo->prepare("
        INSERT INTO settings (restaurant_id, setting_key, setting_value, setting_type, is_public, created_at) 
        VALUES (?, ?, ?, ?, 1, NOW())
    ");
    
    foreach ($settings as $setting) {
        $settingStmt->execute(array($restaurantId, $setting[0], $setting[1], $setting[2]));
    }
    
    // Store restaurant ID for admin creation
    $setupData['restaurant_id'] = $restaurantId;
    
    return array(
        'success' => true,
        'message' => 'Restoran verileri eklendi',
        'restaurant_id' => $restaurantId
    );
}

function createAdminUser($setupData) {
    $pdo = getDatabaseConnection($setupData);
    
    $admin = $setupData['admin'];
    $restaurantId = isset($setupData['restaurant_id']) ? $setupData['restaurant_id'] : 1;
    
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
    $hashedPassword = password_hash($admin['password'], PASSWORD_DEFAULT);
    
    // Insert admin user
    $stmt = $pdo->prepare("
        INSERT INTO admin_users (
            restaurant_id, username, password, email, full_name, role, 
            is_active, created_at
        ) VALUES (?, ?, ?, ?, ?, 'super_admin', 1, NOW())
    ");
    
    $stmt->execute(array(
        $restaurantId,
        $admin['username'],
        $hashedPassword,
        $admin['email'],
        $admin['fullname']
    ));
    
    return array(
        'success' => true,
        'message' => 'Admin hesabı oluşturuldu'
    );
}

function writeConfiguration($setupData) {
    $config = $setupData['database'];
    
    // Generate secure keys
    $jwtSecret = bin2hex(random_bytes(32));
    $csrfSecret = bin2hex(random_bytes(32));
    
    // Determine site URL
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $siteUrl = $protocol . $host;
    
    // Create backend/config directory if not exists
    $configDir = __DIR__ . '/backend/config';
    if (!is_dir($configDir)) {
        mkdir($configDir, 0755, true);
    }
    
    // Write basic config.php
    $configContent = "<?php\n";
    $configContent .= "// Restaurant Menu System - Auto-generated Configuration\n";
    $configContent .= "// Generated on " . date('Y-m-d H:i:s') . "\n\n";
    
    $configContent .= "// Güvenlik: Direct access engelle\n";
    $configContent .= "if (!defined('SECURE_ACCESS')) {\n";
    $configContent .= "    define('SECURE_ACCESS', true);\n";
    $configContent .= "}\n\n";
    
    $configContent .= "// Database Configuration\n";
    $configContent .= "define('DB_HOST', '{$config['host']}');\n";
    $configContent .= "define('DB_NAME', '{$config['name']}');\n";
    $configContent .= "define('DB_USER', '{$config['user']}');\n";
    $configContent .= "define('DB_PASS', '{$config['pass']}');\n";
    $configContent .= "define('DB_CHARSET', 'utf8mb4');\n\n";
    
    $configContent .= "// Site ayarları\n";
    $configContent .= "define('SITE_URL', '{$siteUrl}');\n";
    $configContent .= "define('UPLOAD_PATH', __DIR__ . '/../uploads/');\n";
    $configContent .= "define('UPLOAD_URL', SITE_URL . '/backend/uploads/');\n\n";
    
    $configContent .= "// Güvenlik ayarları\n";
    $configContent .= "define('JWT_SECRET', '{$jwtSecret}');\n";
    $configContent .= "define('CSRF_TOKEN_EXPIRE', 3600);\n";
    $configContent .= "define('SESSION_EXPIRE', 86400);\n\n";
    
    $configContent .= "// Dosya yükleme ayarları\n";
    $configContent .= "define('MAX_FILE_SIZE', 5 * 1024 * 1024);\n";
    $configContent .= "define('ALLOWED_IMAGE_TYPES', array('jpg', 'jpeg', 'png', 'webp'));\n\n";
    
    $configContent .= "// Timezone\n";
    $configContent .= "date_default_timezone_set('Europe/Istanbul');\n\n";
    
    $configContent .= "// Include other config files\n";
    $configContent .= "require_once __DIR__ . '/database.php';\n";
    $configContent .= "require_once __DIR__ . '/utils.php';\n";
    $configContent .= "require_once __DIR__ . '/session_manager.php';\n";
    
    // Write config file
    $configFile = $configDir . '/config.php';
    if (file_put_contents($configFile, $configContent) === false) {
        throw new Exception('Config dosyası yazılamadı');
    }
    
    // Create .htaccess for security
    $htaccessContent = "# Restaurant Menu System Security\n";
    $htaccessContent .= "RewriteEngine On\n\n";
    $htaccessContent .= "# Hide sensitive files\n";
    $htaccessContent .= "<Files \"*.flag\">\n";
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
    $flagContent = json_encode(array(
        'installed_at' => date('c'),
        'version' => '1.0.0',
        'installer_ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown'
    ));
    
    file_put_contents($configDir . '/installed.flag', $flagContent);
    
    return array(
        'success' => true,
        'message' => 'Konfigürasyon dosyaları oluşturuldu'
    );
}

// Utility functions
function createSlug($text) {
    $turkish = array('ç', 'ğ', 'ı', 'ö', 'ş', 'ü', 'Ç', 'Ğ', 'I', 'İ', 'Ö', 'Ş', 'Ü');
    $english = array('c', 'g', 'i', 'o', 's', 'u', 'c', 'g', 'i', 'i', 'o', 's', 'u');
    
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