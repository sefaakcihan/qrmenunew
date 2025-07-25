<?php
// backend/api/auth.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/utils.php';
require_once __DIR__ . '/../config/session_manager.php';
require_once __DIR__ . '/../models/Admin.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            // JSON parse hatası kontrolü
            if (json_last_error() !== JSON_ERROR_NONE) {
                Utils::errorResponse('Geçersiz JSON formatı');
            }
            
            $action = $input['action'] ?? 'login';
            
            if ($action === 'login') {
                $username = Database::sanitize($input['username'] ?? '');
                $password = $input['password'] ?? '';
                
                if (empty($username) || empty($password)) {
                    Utils::errorResponse('Kullanıcı adı ve şifre gerekli');
                }
                
                // Rate limiting
                $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                if (!Utils::rateLimit("login_{$clientIp}", 5, 900)) { // 5 deneme / 15 dakika
                    Utils::errorResponse('Çok fazla giriş denemesi. 15 dakika bekleyin.', 429);
                }
                
                $adminModel = new Admin();
                $admin = $adminModel->authenticate($username, $password);
                
                if ($admin) {
                    SessionManager::regenerate();
                    SessionManager::set('admin_id', $admin['id']);
                    SessionManager::set('admin_logged_in', true);
                    SessionManager::set('admin_username', $admin['username']);
                    SessionManager::set('admin_role', $admin['role']);
                    SessionManager::set('restaurant_id', $admin['restaurant_id']);
                    
                    // Last login güncelle
                    $adminModel->updateLastLogin($admin['id']);
                    
                    Utils::successResponse([
                        'user' => [
                            'id' => $admin['id'],
                            'username' => $admin['username'],
                            'full_name' => $admin['full_name'],
                            'role' => $admin['role'],
                            'restaurant_id' => $admin['restaurant_id']
                        ]
                    ], 'Giriş başarılı');
                } else {
                    Utils::errorResponse('Geçersiz kullanıcı adı veya şifre', 401);
                }
                
            } elseif ($action === 'logout') {
                SessionManager::destroy();
                Utils::successResponse([], 'Çıkış başarılı');
                
            } elseif ($action === 'check') {
                if (SessionManager::isAuthenticated()) {
                    Utils::successResponse([
                        'authenticated' => true,
                        'user' => [
                            'id' => SessionManager::get('admin_id'),
                            'username' => SessionManager::get('admin_username'),
                            'role' => SessionManager::get('admin_role'),
                            'restaurant_id' => SessionManager::get('restaurant_id')
                        ]
                    ]);
                } else {
                    Utils::successResponse(['authenticated' => false]);
                }
            } else {
                Utils::errorResponse('Geçersiz action parametresi');
            }
            break;
            
        default:
            Utils::errorResponse('Desteklenmeyen method', 405);
    }
    
} catch (Exception $e) {
    Utils::errorResponse($e->getMessage());
}
?>