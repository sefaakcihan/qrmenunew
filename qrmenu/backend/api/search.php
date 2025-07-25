<?php
// backend/api/search.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/utils.php';
require_once __DIR__ . '/../models/MenuItem.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    Utils::errorResponse('Sadece GET method desteklenir', 405);
}

try {
    $restaurantId = $_GET['restaurant_id'] ?? 1;
    $query = $_GET['q'] ?? '';
    $category = $_GET['category'] ?? null;
    
    if (strlen($query) < 2) {
        Utils::errorResponse('En az 2 karakter giriniz');
    }
    
    // XSS ve güvenlik kontrolü
    $query = Database::sanitize($query);
    
    $menuModel = new MenuItem();
    
    if ($category) {
        $items = $menuModel->getAllItems($restaurantId, $category, true);
        // Arama filtreleme
        $items = array_filter($items, function($item) use ($query) {
            return stripos($item['name'], $query) !== false ||
                   stripos($item['description'], $query) !== false ||
                   stripos($item['ingredients'], $query) !== false;
        });
    } else {
        $items = $menuModel->searchItems($restaurantId, $query);
    }
    
    // Görsel URL'lerini tam path'e çevir
    foreach ($items as &$item) {
        if ($item['image']) {
            $item['image_url'] = UPLOAD_URL . $item['image'];
        }
        
        // Arama terimini highlight et (XSS korumalı)
        $safeQuery = htmlspecialchars($query, ENT_QUOTES, 'UTF-8');
        $item['name_highlighted'] = str_ireplace($query, '<mark>' . $safeQuery . '</mark>', $item['name']);
        $item['description_highlighted'] = str_ireplace($query, '<mark>' . $safeQuery . '</mark>', $item['description']);
    }
    
    Utils::successResponse([
        'query' => htmlspecialchars($query, ENT_QUOTES, 'UTF-8'),
        'count' => count($items),
        'items' => array_values($items)
    ]);
    
} catch (Exception $e) {
    Utils::errorResponse($e->getMessage());
}
?>