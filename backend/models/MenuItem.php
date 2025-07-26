<?php
// backend/models/MenuItem.php
require_once __DIR__ . '/../config/database.php';

class MenuItem {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Tüm menü öğelerini getir
    public function getAllItems($restaurantId, $categoryId = null, $isAvailable = null) {
        $sql = "SELECT mi.*, c.name as category_name, c.icon as category_icon 
                FROM menu_items mi 
                JOIN categories c ON mi.category_id = c.id 
                WHERE c.restaurant_id = :restaurant_id";
        
        $params = ['restaurant_id' => $restaurantId];
        
        if ($categoryId) {
            $sql .= " AND mi.category_id = :category_id";
            $params['category_id'] = $categoryId;
        }
        
        if ($isAvailable !== null) {
            $sql .= " AND mi.is_available = :is_available";
            $params['is_available'] = $isAvailable ? 1 : 0;
        }
        
        $sql .= " AND c.status = 'active' ORDER BY c.sort_order, mi.sort_order";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    // Öne çıkan ürünleri getir
    public function getFeaturedItems($restaurantId, $limit = 6) {
        $sql = "SELECT mi.*, c.name as category_name 
                FROM menu_items mi 
                JOIN categories c ON mi.category_id = c.id 
                WHERE c.restaurant_id = :restaurant_id 
                AND mi.is_featured = 1 
                AND mi.is_available = 1 
                AND c.status = 'active'
                ORDER BY mi.sort_order 
                LIMIT :limit";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(':restaurant_id', $restaurantId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    // Arama sistemi güvenlik iyileştirmesi
    public function searchItems($restaurantId, $searchTerm) {
        // Minimum arama terimi uzunluğu kontrolü
        if (strlen(trim($searchTerm)) < 2) {
            return [];
        }
        
        // Arama terimini temizle
        $searchTerm = Database::sanitize($searchTerm);
        
        $sql = "SELECT mi.*, c.name as category_name 
                FROM menu_items mi 
                JOIN categories c ON mi.category_id = c.id 
                WHERE c.restaurant_id = :restaurant_id 
                AND (mi.name LIKE :search OR mi.description LIKE :search OR mi.ingredients LIKE :search)
                AND mi.is_available = 1 
                AND c.status = 'active'
                ORDER BY 
                    CASE 
                        WHEN mi.name LIKE :exact_search THEN 1
                        WHEN mi.name LIKE :start_search THEN 2
                        ELSE 3
                    END,
                    c.sort_order, mi.sort_order
                LIMIT 50";
        
        $searchParam = '%' . $searchTerm . '%';
        $exactSearchParam = $searchTerm;
        $startSearchParam = $searchTerm . '%';
        
        return $this->db->fetchAll($sql, [
            'restaurant_id' => $restaurantId,
            'search' => $searchParam,
            'exact_search' => $exactSearchParam,
            'start_search' => $startSearchParam
        ]);
    }
    
    // Tek ürün getir
    public function getItem($id) {
        $sql = "SELECT mi.*, c.name as category_name, c.restaurant_id 
                FROM menu_items mi 
                JOIN categories c ON mi.category_id = c.id 
                WHERE mi.id = :id";
        
        return $this->db->fetch($sql, ['id' => $id]);
    }
    
    // Yeni ürün ekle
    public function createItem($data) {
        // Gerekli alanları kontrol et
        $required = ['category_id', 'name', 'price'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Alan gerekli: {$field}");
            }
        }
        
        // Fiyat kontrolü
        if (!is_numeric($data['price']) || $data['price'] < 0) {
            throw new Exception("Geçersiz fiyat");
        }
        
        // Sort order ayarla
        if (!isset($data['sort_order'])) {
            $maxSort = $this->db->fetch(
                "SELECT MAX(sort_order) as max_sort FROM menu_items WHERE category_id = :category_id",
                ['category_id' => $data['category_id']]
            );
            $data['sort_order'] = ($maxSort['max_sort'] ?? 0) + 1;
        }
        
        return $this->db->insert('menu_items', $data);
    }
    
    // Ürün güncelle
    public function updateItem($id, $data) {
        // Fiyat kontrolü
        if (isset($data['price']) && (!is_numeric($data['price']) || $data['price'] < 0)) {
            throw new Exception("Geçersiz fiyat");
        }
        
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->db->update('menu_items', $data, 'id = :id', ['id' => $id]);
    }
    
    // Ürün sil
    public function deleteItem($id) {
        return $this->db->delete('menu_items', 'id = :id', ['id' => $id]);
    }
    
    // Ürün durumunu değiştir
    public function toggleAvailability($id) {
        $item = $this->getItem($id);
        if (!$item) {
            throw new Exception("Ürün bulunamadı");
        }
        
        $newStatus = $item['is_available'] ? 0 : 1;
        return $this->updateItem($id, ['is_available' => $newStatus]);
    }
    
    // Ürün sırasını güncelle
    public function updateSortOrder($items) {
        $this->db->beginTransaction();
        
        try {
            foreach ($items as $item) {
                $this->db->update(
                    'menu_items',
                    ['sort_order' => $item['sort_order']],
                    'id = :id',
                    ['id' => $item['id']]
                );
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
?>