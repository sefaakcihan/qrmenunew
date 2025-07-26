<?php
// backend/api/categories.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/utils.php';
require_once __DIR__ . '/../config/session_manager.php';
require_once __DIR__ . '/../models/Category.php';

$method = $_SERVER['REQUEST_METHOD'];
$categoryModel = new Category();

try {
    switch ($method) {
        case 'GET':
            $restaurantId = $_GET['restaurant_id'] ?? 1;
            $withCount = isset($_GET['with_count']);
            
            if ($withCount) {
                $categories = $categoryModel->getCategoriesWithItemCount($restaurantId);
            } else {
                $categories = $categoryModel->getAllCategories($restaurantId);
            }
            
            Utils::successResponse($categories);
            break;
            
        case 'POST':
            SessionManager::requireAuth();
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            // JSON parse hatası kontrolü
            if (json_last_error() !== JSON_ERROR_NONE) {
                Utils::errorResponse('Geçersiz JSON formatı');
            }
            
            $input = Database::sanitize($input);
            $id = $categoryModel->createCategory($input);
            $category = $categoryModel->getCategory($id);
            
            Utils::successResponse($category, 'Kategori başarıyla eklendi');
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
            
            $categoryModel->updateCategory($id, $input);
            $category = $categoryModel->getCategory($id);
            
            Utils::successResponse($category, 'Kategori başarıyla güncellendi');
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
            
            $categoryModel->deleteCategory($id);
            Utils::successResponse([], 'Kategori başarıyla silindi');
            break;
            
        default:
            Utils::errorResponse('Desteklenmeyen method', 405);
    }
    
} catch (Exception $e) {
    Utils::errorResponse($e->getMessage());
}
?>