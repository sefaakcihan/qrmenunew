<?php
// backend/models/Category.php
require_once __DIR__ . '/../config/database.php';

class Category {
    private $db;
    
   // Tüm model dosyalarında:
public function __construct() {
    $this->db = Database::getInstance(); // new Database() yerine
}
    
    // Tüm kategorileri getir
    public function getAllCategories($restaurantId, $activeOnly = true) {
        $sql = "SELECT * FROM categories WHERE restaurant_id = :restaurant_id";
        $params = ['restaurant_id' => $restaurantId];
        
        if ($activeOnly) {
            $sql .= " AND status = 'active'";
        }
        
        $sql .= " ORDER BY sort_order";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    // Kategori ile birlikte ürün sayılarını getir
    public function getCategoriesWithItemCount($restaurantId) {
        $sql = "SELECT c.*, COUNT(mi.id) as item_count,
                COUNT(CASE WHEN mi.is_available = 1 THEN mi.id END) as available_count
                FROM categories c 
                LEFT JOIN menu_items mi ON c.id = mi.category_id 
                WHERE c.restaurant_id = :restaurant_id AND c.status = 'active'
                GROUP BY c.id 
                ORDER BY c.sort_order";
        
        return $this->db->fetchAll($sql, ['restaurant_id' => $restaurantId]);
    }
    
    // Tek kategori getir
    public function getCategory($id) {
        return $this->db->fetch("SELECT * FROM categories WHERE id = :id", ['id' => $id]);
    }
    
    // Yeni kategori ekle
    public function createCategory($data) {
        $required = ['restaurant_id', 'name'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Alan gerekli: {$field}");
            }
        }
        
        // Sort order ayarla
        if (!isset($data['sort_order'])) {
            $maxSort = $this->db->fetch(
                "SELECT MAX(sort_order) as max_sort FROM categories WHERE restaurant_id = :restaurant_id",
                ['restaurant_id' => $data['restaurant_id']]
            );
            $data['sort_order'] = ($maxSort['max_sort'] ?? 0) + 1;
        }
        
        if (!isset($data['status'])) {
            $data['status'] = 'active';
        }
        
        return $this->db->insert('categories', $data);
    }
    
    // Kategori güncelle
    public function updateCategory($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update('categories', $data, 'id = :id', ['id' => $id]);
    }
    
    // Kategori sil
    public function deleteCategory($id) {
        // Önce bu kategoriye ait ürünleri kontrol et
        $itemCount = $this->db->fetch(
            "SELECT COUNT(*) as count FROM menu_items WHERE category_id = :id",
            ['id' => $id]
        );
        
        if ($itemCount['count'] > 0) {
            throw new Exception("Bu kategoriye ait ürünler bulunuyor. Önce ürünleri silin.");
        }
        
        return $this->db->delete('categories', 'id = :id', ['id' => $id]);
    }
    
    // Kategori sırasını güncelle
    public function updateSortOrder($categories) {
        $this->db->beginTransaction();
        
        try {
            foreach ($categories as $category) {
                $this->db->update(
                    'categories',
                    ['sort_order' => $category['sort_order']],
                    'id = :id',
                    ['id' => $category['id']]
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