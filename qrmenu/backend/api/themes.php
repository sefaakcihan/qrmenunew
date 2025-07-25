<?php
// backend/api/themes.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/utils.php';
require_once __DIR__ . '/../config/session_manager.php';
require_once __DIR__ . '/../models/Theme.php';

$method = $_SERVER['REQUEST_METHOD'];
$themeModel = new Theme();

try {
    switch ($method) {
        case 'GET':
            $restaurantId = $_GET['restaurant_id'] ?? 1;
            $action = $_GET['action'] ?? 'list';
            
            if ($action === 'active') {
                $theme = $themeModel->getActiveTheme($restaurantId);
                Utils::successResponse($theme);
            } elseif ($action === 'css') {
                $theme = $themeModel->getActiveTheme($restaurantId);
                $css = $themeModel->generateCSS($theme, $theme['custom_css'] ?? null);
                
                header('Content-Type: text/css; charset=utf-8');
                echo $css;
                exit;
            } else {
                $themes = $themeModel->getAllThemes();
                Utils::successResponse($themes);
            }
            break;
            
        case 'POST':
            SessionManager::requireAuth();
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            // JSON parse hatası kontrolü
            if (json_last_error() !== JSON_ERROR_NONE) {
                Utils::errorResponse('Geçersiz JSON formatı');
            }
            
            $action = $input['action'] ?? 'create';
            
            if ($action === 'set_restaurant_theme') {
                $restaurantId = $input['restaurant_id'] ?? 1;
                $themeId = $input['theme_id'];
                $customCss = $input['custom_css'] ?? null;
                $isDefault = $input['is_default'] ?? true;
                
                $id = $themeModel->setRestaurantTheme($restaurantId, $themeId, $customCss, $isDefault);
                Utils::successResponse(['id' => $id], 'Tema başarıyla ayarlandı');
            } else {
                $input = Database::sanitize($input);
                $id = $themeModel->createTheme($input);
                $theme = $themeModel->getTheme($id);
                Utils::successResponse($theme, 'Tema başarıyla oluşturuldu');
            }
            break;
            
        case 'PUT':
            SessionManager::requireAuth();
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            // JSON parse hatası kontrolü
            if (json_last_error() !== JSON_ERROR_NONE) {
                Utils::errorResponse('Geçersiz JSON formatı');
            }
            
            $id = $input['id'] ?? null;
            
            if (!$id) {
                Utils::errorResponse('ID gerekli');
            }
            
            unset($input['id']);
            $input = Database::sanitize($input);
            
            $themeModel->updateTheme($id, $input);
            $theme = $themeModel->getTheme($id);
            
            Utils::successResponse($theme, 'Tema başarıyla güncellendi');
            break;
            
        case 'DELETE':
            SessionManager::requireAuth();
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            // JSON parse hatası kontrolü
            if (json_last_error() !== JSON_ERROR_NONE) {
                Utils::errorResponse('Geçersiz JSON formatı');
            }
            
            $id = $input['id'] ?? null;
            
            if (!$id) {
                Utils::errorResponse('ID gerekli');
            }
            
            $themeModel->deleteTheme($id);
            Utils::successResponse([], 'Tema başarıyla silindi');
            break;
            
        default:
            Utils::errorResponse('Desteklenmeyen method', 405);
    }
    
} catch (Exception $e) {
    Utils::errorResponse($e->getMessage());
}
?>