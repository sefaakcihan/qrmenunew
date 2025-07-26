<?php
// backend/api/menu.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/utils.php';
require_once __DIR__ . '/../config/session_manager.php';
require_once __DIR__ . '/../models/MenuItem.php';
require_once __DIR__ . '/../models/Category.php';

$method = $_SERVER['REQUEST_METHOD'];
$menuModel = new MenuItem();
$categoryModel = new Category();

try {
    switch ($method) {
        case 'GET':
            $restaurantId = $_GET['restaurant_id'] ?? 1;
            $categoryId = $_GET['category_id'] ?? null;
            $search = $_GET['search'] ?? null;
            $featured = isset($_GET['featured']);
            
            if ($search) {
                $items = $menuModel->searchItems($restaurantId, $search);
            } elseif ($featured) {
                $items = $menuModel->getFeaturedItems($restaurantId);
            } else {
                $items = $menuModel->getAllItems($restaurantId, $categoryId, true);
            }
            
            // Görsel URL'lerini tam path'e çevir
            foreach ($items as &$item) {
                if ($item['image']) {
                    $item['image_url'] = UPLOAD_URL . $item['image'];
                }
            }
            
            Utils::successResponse($items);
            break;
            
        case 'POST':
            SessionManager::requireAuth();
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            // JSON parse hatası kontrolü
            if (json_last_error() !== JSON_ERROR_NONE) {
                Utils::errorResponse('Geçersiz JSON formatı');
            }
            
            // Görsel yükleme kontrolü iyileştirmesi
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $input['image'] = Utils::uploadImage($_FILES['image']);
            }
            
            // Input validasyonu
            $input = Database::sanitize($input);
            
            $id = $menuModel->createItem($input);
            $item = $menuModel->getItem($id);
            
            if ($item && $item['image']) {
                $item['image_url'] = UPLOAD_URL . $item['image'];
            }
            
            Utils::successResponse($item, 'Ürün başarıyla eklendi');
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
            
            $menuModel->updateItem($id, $input);
            $item = $menuModel->getItem($id);
            
            if ($item && $item['image']) {
                $item['image_url'] = UPLOAD_URL . $item['image'];
            }
            
            Utils::successResponse($item, 'Ürün başarıyla güncellendi');
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
            
            $menuModel->deleteItem($id);
            Utils::successResponse([], 'Ürün başarıyla silindi');
            break;
            
        default:
            Utils::errorResponse('Desteklenmeyen method', 405);
    }
    
} catch (Exception $e) {
    Utils::errorResponse($e->getMessage());
}
?>