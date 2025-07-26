<?php
// setup_check.php - Setup System Check Backend

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Prevent direct access if already installed
if (file_exists(__DIR__ . '/backend/config/installed.flag')) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Sistem zaten kurulmuş. Bu dosyayı silin.'
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

try {
    switch ($action) {
        case 'system_check':
            echo json_encode(performSystemCheck($input['check']));
            break;
            
        case 'test_database':
            echo json_encode(testDatabaseConnection($input));
            break;
            
        case 'get_themes':
            echo json_encode(getAvailableThemes());
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

function performSystemCheck($check) {
    switch ($check) {
        case 'php_version':
            $version = PHP_VERSION;
            $required = '8.0';
            $success = version_compare($version, $required, '>=');
            return [
                'success' => $success,
                'message' => $success ? "PHP {$version}" : "PHP {$version} (Gerekli: {$required}+)"
            ];
            
        case 'pdo_extension':
            $success = extension_loaded('pdo') && extension_loaded('pdo_mysql');
            return [
                'success' => $success,
                'message' => $success ? 'PDO MySQL yüklü' : 'PDO MySQL extension gerekli'
            ];
            
        case 'gd_extension':
            $success = extension_loaded('gd');
            return [
                'success' => $success,
                'message' => $success ? 'GD extension yüklü' : 'GD extension gerekli (resim işleme için)'
            ];
            
        case 'uploads_writable':
            $uploadDir = __DIR__ . '/backend/uploads';
            
            // Create directory if not exists
            if (!is_dir($uploadDir)) {
                $created = mkdir($uploadDir, 0755, true);
                if (!$created) {
                    return [
                        'success' => false,
                        'message' => 'Uploads dizini oluşturulamadı'
                    ];
                }
            }
            
            $success = is_writable($uploadDir);
            return [
                'success' => $success,
                'message' => $success ? 'Uploads dizini yazılabilir' : 'Uploads dizini yazılabilir değil (chmod 755)'
            ];
            
        case 'logs_writable':
            $logsDir = __DIR__ . '/backend/logs';
            
            // Create directory if not exists
            if (!is_dir($logsDir)) {
                $created = mkdir($logsDir, 0755, true);
                if (!$created) {
                    return [
                        'success' => false,
                        'message' => 'Logs dizini oluşturulamadı'
                    ];
                }
            }
            
            $success = is_writable($logsDir);
            return [
                'success' => $success,
                'message' => $success ? 'Logs dizini yazılabilir' : 'Logs dizini yazılabilir değil (chmod 755)'
            ];
            
        default:
            throw new Exception('Bilinmeyen sistem kontrolü');
    }
}

function testDatabaseConnection($config) {
    $host = $config['db_host'] ?? 'localhost';
    $port = $config['db_port'] ?? '3306';
    $dbname = $config['db_name'] ?? '';
    $username = $config['db_user'] ?? '';
    $password = $config['db_pass'] ?? '';
    
    if (empty($dbname) || empty($username)) {
        throw new Exception('Veritabanı adı ve kullanıcı adı gerekli');
    }
    
    try {
        // First try to connect without database to check credentials
        $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10
        ]);
        
        // Check if database exists
        $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
        $stmt->execute([$dbname]);
        $dbExists = $stmt->fetch() !== false;
        
        if (!$dbExists) {
            // Try to create database
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $message = "Veritabanı '{$dbname}' başarıyla oluşturuldu";
        } else {
            $message = "Veritabanı '{$dbname}' mevcut ve erişilebilir";
        }
        
        // Test connection to the specific database
        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
        $testPdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Test a simple query
        $testPdo->query("SELECT 1");
        
        return [
            'success' => true,
            'message' => $message,
            'database_exists' => $dbExists
        ];
        
    } catch (PDOException $e) {
        $errorCode = $e->getCode();
        
        switch ($errorCode) {
            case 1044:
            case 1045:
                $message = 'Veritabanı kullanıcı adı veya şifresi yanlış';
                break;
            case 2002:
                $message = 'Veritabanı sunucusuna bağlanılamıyor. Sunucu adresi kontrol edin.';
                break;
            case 1049:
                $message = "Veritabanı '{$dbname}' bulunamadı ve oluşturulamadı";
                break;
            default:
                $message = 'Veritabanı bağlantı hatası: ' . $e->getMessage();
        }
        
        throw new Exception($message);
    }
}

function getAvailableThemes() {
    // Default themes data
    $themes = [
        [
            'id' => 1,
            'name' => 'Classic Restaurant',
            'slug' => 'classic',
            'description' => 'Geleneksel ve sıcak restoran teması',
            'primary_color' => '#C8102E',
            'secondary_color' => '#FFD700',
            'accent_color' => '#2E8B57'
        ],
        [
            'id' => 2,
            'name' => 'Modern Minimalist',
            'slug' => 'modern',
            'description' => 'Modern ve minimalist tasarım',
            'primary_color' => '#667EEA',
            'secondary_color' => '#764BA2',
            'accent_color' => '#F093FB'
        ],
        [
            'id' => 3,
            'name' => 'Dark Elegance',
            'slug' => 'dark-elegance',
            'description' => 'Lüks ve koyu tema',
            'primary_color' => '#D4AF37',
            'secondary_color' => '#C0392B',
            'accent_color' => '#2ECC71'
        ],
        [
            'id' => 4,
            'name' => 'Colorful Cafe',
            'slug' => 'colorful',
            'description' => 'Renkli ve enerjik kafe teması',
            'primary_color' => '#FF6B6B',
            'secondary_color' => '#4ECDC4',
            'accent_color' => '#45B7D1'
        ],
        [
            'id' => 5,
            'name' => 'Fine Dining',
            'slug' => 'fine-dining',
            'description' => 'Şık ve premium restoran teması',
            'primary_color' => '#8B4513',
            'secondary_color' => '#D2691E',
            'accent_color' => '#DAA520'
        ],
        [
            'id' => 6,
            'name' => 'Seafood Fresh',
            'slug' => 'seafood',
            'description' => 'Deniz ürünleri restoranı teması',
            'primary_color' => '#0077BE',
            'secondary_color' => '#20B2AA',
            'accent_color' => '#FF7F50'
        ],
        [
            'id' => 7,
            'name' => 'Italian Rustic',
            'slug' => 'italian',
            'description' => 'İtalyan mutfağı teması',
            'primary_color' => '#228B22',
            'secondary_color' => '#DC143C',
            'accent_color' => '#FFD700'
        ],
        [
            'id' => 8,
            'name' => 'Asian Zen',
            'slug' => 'asian',
            'description' => 'Asya mutfağı zen teması',
            'primary_color' => '#8B0000',
            'secondary_color' => '#FFD700',
            'accent_color' => '#32CD32'
        ]
    ];
    
    return [
        'success' => true,
        'data' => $themes
    ];
}

// Utility functions
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function isStrongPassword($password) {
    // At least 8 characters
    if (strlen($password) < 8) return false;
    
    // Contains lowercase letter
    if (!preg_match('/[a-z]/', $password)) return false;
    
    // Contains uppercase letter
    if (!preg_match('/[A-Z]/', $password)) return false;
    
    // Contains number
    if (!preg_match('/[0-9]/', $password)) return false;
    
    // Contains special character
    if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) return false;
    
    return true;
}
?>