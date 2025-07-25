<?php
// backend/models/Theme.php
require_once __DIR__ . '/../config/database.php';

class Theme {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Tüm temaları getir
    public function getAllThemes($activeOnly = true) {
        $sql = "SELECT * FROM themes";
        
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        
        $sql .= " ORDER BY name";
        
        return $this->db->fetchAll($sql);
    }
    
    // Restoran temalarını getir
    public function getRestaurantThemes($restaurantId) {
        $sql = "SELECT t.*, rt.custom_css, rt.is_default
                FROM themes t
                JOIN restaurant_themes rt ON t.id = rt.theme_id
                WHERE rt.restaurant_id = :restaurant_id
                ORDER BY rt.is_default DESC, t.name";
        
        return $this->db->fetchAll($sql, ['restaurant_id' => $restaurantId]);
    }
    
    // Aktif tema getir
    public function getActiveTheme($restaurantId) {
        $sql = "SELECT t.*, rt.custom_css
                FROM themes t
                JOIN restaurant_themes rt ON t.id = rt.theme_id
                WHERE rt.restaurant_id = :restaurant_id AND rt.is_default = 1";
        
        $theme = $this->db->fetch($sql, ['restaurant_id' => $restaurantId]);
        
        // Eğer tema bulunamazsa varsayılan tema kullan
        if (!$theme) {
            $theme = $this->db->fetch("SELECT * FROM themes WHERE slug = 'classic'");
        }
        
        return $theme;
    }
    
    // Tek tema getir
    public function getTheme($id) {
        return $this->db->fetch("SELECT * FROM themes WHERE id = :id", ['id' => $id]);
    }
    
    // Tema slug ile getir
    public function getThemeBySlug($slug) {
        return $this->db->fetch("SELECT * FROM themes WHERE slug = :slug", ['slug' => $slug]);
    }
    
    // Yeni tema ekle
    public function createTheme($data) {
        $required = ['name', 'slug'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Alan gerekli: {$field}");
            }
        }
        
        // Slug benzersizliği kontrolü
        $existing = $this->getThemeBySlug($data['slug']);
        if ($existing) {
            throw new Exception("Bu slug zaten kullanılıyor");
        }
        
        return $this->db->insert('themes', $data);
    }
    
    // Tema güncelle
    public function updateTheme($id, $data) {
        // Slug güncelleniyorsa benzersizlik kontrolü
        if (isset($data['slug'])) {
            $existing = $this->db->fetch(
                "SELECT id FROM themes WHERE slug = :slug AND id != :id",
                ['slug' => $data['slug'], 'id' => $id]
            );
            if ($existing) {
                throw new Exception("Bu slug zaten kullanılıyor");
            }
        }
        
        return $this->db->update('themes', $data, 'id = :id', ['id' => $id]);
    }
    
    // Tema sil
    public function deleteTheme($id) {
        // Varsayılan temaları silme koruması
        $theme = $this->getTheme($id);
        if ($theme && in_array($theme['slug'], ['classic', 'modern', 'dark-elegance'])) {
            throw new Exception("Varsayılan temalar silinemez");
        }
        
        return $this->db->delete('themes', 'id = :id', ['id' => $id]);
    }
    
    // Restoran teması ayarla
    public function setRestaurantTheme($restaurantId, $themeId, $customCss = null, $isDefault = false) {
        $this->db->beginTransaction();
        
        try {
            // Eğer varsayılan tema yapılıyorsa, diğerlerini varsayılan olmaktan çıkar
            if ($isDefault) {
                $this->db->update(
                    'restaurant_themes',
                    ['is_default' => 0],
                    'restaurant_id = :restaurant_id',
                    ['restaurant_id' => $restaurantId]
                );
            }
            
            // Mevcut kaydı kontrol et
            $existing = $this->db->fetch(
                "SELECT id FROM restaurant_themes WHERE restaurant_id = :restaurant_id AND theme_id = :theme_id",
                ['restaurant_id' => $restaurantId, 'theme_id' => $themeId]
            );
            
            if ($existing) {
                // Güncelle
                $this->db->update(
                    'restaurant_themes',
                    [
                        'custom_css' => $customCss,
                        'is_default' => $isDefault ? 1 : 0
                    ],
                    'id = :id',
                    ['id' => $existing['id']]
                );
                $id = $existing['id'];
            } else {
                // Yeni kayıt oluştur
                $id = $this->db->insert('restaurant_themes', [
                    'restaurant_id' => $restaurantId,
                    'theme_id' => $themeId,
                    'custom_css' => $customCss,
                    'is_default' => $isDefault ? 1 : 0
                ]);
            }
            
            $this->db->commit();
            return $id;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    // CSS oluştur
    public function generateCSS($theme, $customCss = null) {
        $cssVariables = json_decode($theme['css_variables'] ?? '{}', true);
        
        $css = ":root {\n";
        $css .= "  --primary-color: {$theme['primary_color']};\n";
        $css .= "  --secondary-color: {$theme['secondary_color']};\n";
        $css .= "  --accent-color: {$theme['accent_color']};\n";
        $css .= "  --bg-color: {$theme['bg_color']};\n";
        $css .= "  --text-color: {$theme['text_color']};\n";
        $css .= "  --card-bg: {$theme['card_bg']};\n";
        
        // Ek CSS değişkenleri
        foreach ($cssVariables as $key => $value) {
            $css .= "  --{$key}: {$value};\n";
        }
        
        $css .= "}\n\n";
        
        // Özel CSS ekle
        if ($customCss) {
            $css .= $customCss;
        }
        
        return $css;
    }
}
?>