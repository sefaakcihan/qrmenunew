<?php
// backend/models/Admin.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';

class Admin {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Admin kimlik doğrulama
    public function authenticate($username, $password) {
        // Güvenlik: input sanitization
        $username = Database::sanitize($username);
        
        if (empty($username) || empty($password)) {
            return false;
        }
        
        $sql = "SELECT * FROM admins WHERE username = :username AND is_active = 1";
        $admin = $this->db->fetch($sql, ['username' => $username]);
        
        if ($admin && Utils::verifyPassword($password, $admin['password'])) {
            // Hassas bilgileri kaldır
            unset($admin['password']);
            return $admin;
        }
        
        return false;
    }
    
    // Admin bilgilerini getir
    public function getAdmin($id) {
        $sql = "SELECT id, restaurant_id, username, full_name, email, role, last_login, created_at 
                FROM admins WHERE id = :id AND is_active = 1";
        return $this->db->fetch($sql, ['id' => $id]);
    }
    
    // Kullanıcı adı ile admin getir
    public function getAdminByUsername($username) {
        $sql = "SELECT id, restaurant_id, username, full_name, email, role, last_login, created_at 
                FROM admins WHERE username = :username AND is_active = 1";
        return $this->db->fetch($sql, ['username' => $username]);
    }
    
    // Yeni admin oluştur
    public function createAdmin($data) {
        $required = ['restaurant_id', 'username', 'password', 'full_name', 'email'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Alan gerekli: {$field}");
            }
        }
        
        // Username benzersizliği kontrolü
        if ($this->getAdminByUsername($data['username'])) {
            throw new Exception("Bu kullanıcı adı zaten kullanılıyor");
        }
        
        // Email validasyonu
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Geçersiz email adresi");
        }
        
        // Şifre hash'le
        $data['password'] = Utils::hashPassword($data['password']);
        
        // Varsayılan değerler
        if (!isset($data['role'])) {
            $data['role'] = 'admin';
        }
        
        return $this->db->insert('admins', $data);
    }
    
    // Admin güncelle
    public function updateAdmin($id, $data) {
        // Şifre güncellenmiyorsa kaldır
        if (isset($data['password'])) {
            if (empty($data['password'])) {
                unset($data['password']);
            } else {
                $data['password'] = Utils::hashPassword($data['password']);
            }
        }
        
        // Email validasyonu
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Geçersiz email adresi");
        }
        
        // Username benzersizliği kontrolü
        if (isset($data['username'])) {
            $existing = $this->db->fetch(
                "SELECT id FROM admins WHERE username = :username AND id != :id",
                ['username' => $data['username'], 'id' => $id]
            );
            if ($existing) {
                throw new Exception("Bu kullanıcı adı zaten kullanılıyor");
            }
        }
        
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->db->update('admins', $data, 'id = :id', ['id' => $id]);
    }
    
    // Admin sil (soft delete)
    public function deleteAdmin($id) {
        return $this->db->update('admins', ['is_active' => 0], 'id = :id', ['id' => $id]);
    }
    
    // Son giriş zamanını güncelle
    public function updateLastLogin($id) {
        return $this->db->update(
            'admins', 
            ['last_login' => date('Y-m-d H:i:s')], 
            'id = :id', 
            ['id' => $id]
        );
    }
    
    // Şifre değiştir
    public function changePassword($id, $currentPassword, $newPassword) {
        // Mevcut şifreyi kontrol et
        $admin = $this->db->fetch("SELECT password FROM admins WHERE id = :id", ['id' => $id]);
        
        if (!$admin || !Utils::verifyPassword($currentPassword, $admin['password'])) {
            throw new Exception("Mevcut şifre yanlış");
        }
        
        // Yeni şifre validasyonu
        if (strlen($newPassword) < 6) {
            throw new Exception("Yeni şifre en az 6 karakter olmalı");
        }
        
        return $this->updateAdmin($id, ['password' => $newPassword]);
    }
    
    // Restaurant'a göre adminleri getir
    public function getAdminsByRestaurant($restaurantId) {
        $sql = "SELECT id, username, full_name, email, role, last_login, created_at 
                FROM admins 
                WHERE restaurant_id = :restaurant_id AND is_active = 1 
                ORDER BY created_at DESC";
        
        return $this->db->fetchAll($sql, ['restaurant_id' => $restaurantId]);
    }
    
    // Admin istatistikleri
    public function getAdminStats($restaurantId) {
        $stats = [];
        
        // Toplam admin sayısı
        $stats['total'] = $this->db->fetch(
            "SELECT COUNT(*) as count FROM admins WHERE restaurant_id = :restaurant_id AND is_active = 1",
            ['restaurant_id' => $restaurantId]
        )['count'];
        
        // Role göre dağılım
        $stats['by_role'] = $this->db->fetchAll(
            "SELECT role, COUNT(*) as count FROM admins 
             WHERE restaurant_id = :restaurant_id AND is_active = 1 
             GROUP BY role",
            ['restaurant_id' => $restaurantId]
        );
        
        // Son 30 günde aktif olanlar
        $stats['recent_active'] = $this->db->fetch(
            "SELECT COUNT(*) as count FROM admins 
             WHERE restaurant_id = :restaurant_id AND is_active = 1 
             AND last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            ['restaurant_id' => $restaurantId]
        )['count'];
        
        return $stats;
    }
}
?>