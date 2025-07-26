<?php
// backend/models/Admin.php - FIXED VERSION
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utils.php';

class Admin {
    private $db;
    private $tableName = 'admin_users'; // Fixed table name
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    // Tüm model dosyalarında
    
    // Admin kimlik doğrulama - Enhanced security
    public function authenticate($username, $password) {
        // Input validation
        if (empty($username) || empty($password)) {
            logActivity('WARNING', 'Empty credentials provided', ['username' => $username]);
            return false;
        }
        
        // Sanitize username
        $username = Database::sanitize($username);
        
        // Username format validation
        if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
            logActivity('WARNING', 'Invalid username format', ['username' => $username]);
            return false;
        }
        
        try {
            $sql = "SELECT * FROM {$this->tableName} WHERE username = :username AND is_active = 1";
            $admin = $this->db->fetch($sql, ['username' => $username]);
            
            if (!$admin) {
                logActivity('WARNING', 'Admin not found', ['username' => $username]);
                return false;
            }
            
            // Check password
            if (!Utils::verifyPassword($password, $admin['password'])) {
                logActivity('WARNING', 'Invalid password attempt', [
                    'username' => $username,
                    'admin_id' => $admin['id']
                ]);
                return false;
            }
            
            // Check account status
            if (!$admin['is_active']) {
                logActivity('WARNING', 'Inactive account login attempt', [
                    'username' => $username,
                    'admin_id' => $admin['id']
                ]);
                return false;
            }
            
            // Log successful login
            logActivity('INFO', 'Successful admin login', [
                'username' => $username,
                'admin_id' => $admin['id']
            ]);
            
            // Remove sensitive data
            unset($admin['password']);
            return $admin;
            
        } catch (Exception $e) {
            logActivity('ERROR', 'Authentication error: ' . $e->getMessage(), [
                'username' => $username
            ]);
            return false;
        }
    }
    
    // Admin bilgilerini getir - Enhanced
    public function getAdmin($id) {
        if (!is_numeric($id) || $id <= 0) {
            throw new Exception("Geçersiz admin ID");
        }
        
        $sql = "SELECT id, restaurant_id, username, full_name, email, role, last_login, created_at, is_active 
                FROM {$this->tableName} WHERE id = :id";
        
        $admin = $this->db->fetch($sql, ['id' => $id]);
        
        if (!$admin) {
            throw new Exception("Admin bulunamadı");
        }
        
        return $admin;
    }
    
    // Kullanıcı adı ile admin getir - Enhanced
    public function getAdminByUsername($username) {
        $username = Database::sanitize($username);
        
        if (empty($username)) {
            return null;
        }
        
        $sql = "SELECT id, restaurant_id, username, full_name, email, role, last_login, created_at, is_active 
                FROM {$this->tableName} WHERE username = :username";
        
        return $this->db->fetch($sql, ['username' => $username]);
    }
    
    // Email ile admin getir
    public function getAdminByEmail($email) {
        if (!Utils::validateEmail($email)) {
            return null;
        }
        
        $sql = "SELECT id, restaurant_id, username, full_name, email, role, last_login, created_at, is_active 
                FROM {$this->tableName} WHERE email = :email";
        
        return $this->db->fetch($sql, ['email' => $email]);
    }
    
    // Yeni admin oluştur - Enhanced validation
    public function createAdmin($data) {
        // Required fields validation
        $required = ['restaurant_id', 'username', 'password', 'full_name', 'email'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                throw new Exception("Alan gerekli: {$field}");
            }
        }
        
        // Sanitize data
        $data = Database::sanitize($data);
        
        // Validate restaurant_id
        if (!is_numeric($data['restaurant_id']) || $data['restaurant_id'] <= 0) {
            throw new Exception("Geçersiz restaurant ID");
        }
        
        // Username validation
        if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $data['username'])) {
            throw new Exception("Kullanıcı adı 3-50 karakter arasında olmalı ve sadece harf, rakam, alt çizgi içermelidir");
        }
        
        // Username uniqueness check
        if ($this->getAdminByUsername($data['username'])) {
            throw new Exception("Bu kullanıcı adı zaten kullanılıyor");
        }
        
        // Email validation
        if (!Utils::validateEmail($data['email'])) {
            throw new Exception("Geçersiz email adresi");
        }
        
        // Email uniqueness check
        if ($this->getAdminByEmail($data['email'])) {
            throw new Exception("Bu email adresi zaten kullanılıyor");
        }
        
        // Full name validation
        if (strlen($data['full_name']) < 2 || strlen($data['full_name']) > 100) {
            throw new Exception("Ad soyad 2-100 karakter arasında olmalı");
        }
        
        // Password validation and hashing
        if (strlen($data['password']) < 8) {
            throw new Exception("Şifre en az 8 karakter olmalı");
        }
        
        // Check password strength
        if (!$this->isStrongPassword($data['password'])) {
            throw new Exception("Şifre en az bir büyük harf, bir küçük harf, bir rakam ve bir özel karakter içermelidir");
        }
        
        $data['password'] = Utils::hashPassword($data['password']);
        
        // Role validation
        $validRoles = ['super_admin', 'admin', 'manager', 'staff'];
        if (!isset($data['role']) || !in_array($data['role'], $validRoles)) {
            $data['role'] = 'admin';
        }
        
        // Set defaults
        $data['is_active'] = $data['is_active'] ?? 1;
        $data['created_at'] = date('Y-m-d H:i:s');
        
        try {
            $id = $this->db->insert($this->tableName, $data);
            
            logActivity('INFO', 'New admin created', [
                'admin_id' => $id,
                'username' => $data['username'],
                'email' => $data['email']
            ]);
            
            return $id;
            
        } catch (Exception $e) {
            logActivity('ERROR', 'Admin creation failed: ' . $e->getMessage(), $data);
            throw new Exception("Admin oluşturulamadı: " . $e->getMessage());
        }
    }
    
    // Admin güncelle - Enhanced
    public function updateAdmin($id, $data) {
        if (!is_numeric($id) || $id <= 0) {
            throw new Exception("Geçersiz admin ID");
        }
        
        // Check if admin exists
        $existingAdmin = $this->getAdmin($id);
        if (!$existingAdmin) {
            throw new Exception("Admin bulunamadı");
        }
        
        // Sanitize data
        $data = Database::sanitize($data);
        
        // Password handling
        if (isset($data['password'])) {
            if (empty($data['password'])) {
                unset($data['password']);
            } else {
                if (strlen($data['password']) < 8) {
                    throw new Exception("Şifre en az 8 karakter olmalı");
                }
                if (!$this->isStrongPassword($data['password'])) {
                    throw new Exception("Şifre yeterince güçlü değil");
                }
                $data['password'] = Utils::hashPassword($data['password']);
            }
        }
        
        // Email validation
        if (isset($data['email'])) {
            if (!Utils::validateEmail($data['email'])) {
                throw new Exception("Geçersiz email adresi");
            }
            
            // Email uniqueness check
            $existing = $this->db->fetch(
                "SELECT id FROM {$this->tableName} WHERE email = :email AND id != :id",
                ['email' => $data['email'], 'id' => $id]
            );
            if ($existing) {
                throw new Exception("Bu email adresi zaten kullanılıyor");
            }
        }
        
        // Username validation
        if (isset($data['username'])) {
            if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $data['username'])) {
                throw new Exception("Geçersiz kullanıcı adı formatı");
            }
            
            // Username uniqueness check
            $existing = $this->db->fetch(
                "SELECT id FROM {$this->tableName} WHERE username = :username AND id != :id",
                ['username' => $data['username'], 'id' => $id]
            );
            if ($existing) {
                throw new Exception("Bu kullanıcı adı zaten kullanılıyor");
            }
        }
        
        // Role validation
        if (isset($data['role'])) {
            $validRoles = ['super_admin', 'admin', 'manager', 'staff'];
            if (!in_array($data['role'], $validRoles)) {
                throw new Exception("Geçersiz rol");
            }
        }
        
        // Full name validation
        if (isset($data['full_name'])) {
            if (strlen($data['full_name']) < 2 || strlen($data['full_name']) > 100) {
                throw new Exception("Ad soyad 2-100 karakter arasında olmalı");
            }
        }
        
        try {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $result = $this->db->update($this->tableName, $data, 'id = :id', ['id' => $id]);
            
            logActivity('INFO', 'Admin updated', [
                'admin_id' => $id,
                'updated_fields' => array_keys($data)
            ]);
            
            return $result;
            
        } catch (Exception $e) {
            logActivity('ERROR', 'Admin update failed: ' . $e->getMessage(), [
                'admin_id' => $id,
                'data' => $data
            ]);
            throw new Exception("Admin güncellenemedi: " . $e->getMessage());
        }
    }
    
    // Admin sil (soft delete) - Enhanced
    public function deleteAdmin($id) {
        if (!is_numeric($id) || $id <= 0) {
            throw new Exception("Geçersiz admin ID");
        }
        
        // Check if admin exists
        $admin = $this->getAdmin($id);
        if (!$admin) {
            throw new Exception("Admin bulunamadı");
        }
        
        // Prevent deleting super admin
        if ($admin['role'] === 'super_admin') {
            $superAdminCount = $this->db->fetch(
                "SELECT COUNT(*) as count FROM {$this->tableName} WHERE role = 'super_admin' AND is_active = 1"
            );
            
            if ($superAdminCount['count'] <= 1) {
                throw new Exception("Son süper admin silinemez");
            }
        }
        
        try {
            $result = $this->db->update(
                $this->tableName, 
                ['is_active' => 0, 'deleted_at' => date('Y-m-d H:i:s')], 
                'id = :id', 
                ['id' => $id]
            );
            
            logActivity('INFO', 'Admin deleted (soft delete)', [
                'admin_id' => $id,
                'username' => $admin['username']
            ]);
            
            return $result;
            
        } catch (Exception $e) {
            logActivity('ERROR', 'Admin deletion failed: ' . $e->getMessage(), ['admin_id' => $id]);
            throw new Exception("Admin silinemedi: " . $e->getMessage());
        }
    }
    
    // Son giriş zamanını güncelle - Enhanced
    public function updateLastLogin($id, $ipAddress = null) {
        if (!is_numeric($id) || $id <= 0) {
            return false;
        }
        
        $updateData = ['last_login' => date('Y-m-d H:i:s')];
        
        if ($ipAddress) {
            $updateData['last_ip'] = $ipAddress;
        }
        
        try {
            return $this->db->update(
                $this->tableName, 
                $updateData,
                'id = :id', 
                ['id' => $id]
            );
        } catch (Exception $e) {
            logActivity('WARNING', 'Failed to update last login: ' . $e->getMessage(), ['admin_id' => $id]);
            return false;
        }
    }
    
    // Şifre değiştir - Enhanced
    public function changePassword($id, $currentPassword, $newPassword) {
        if (!is_numeric($id) || $id <= 0) {
            throw new Exception("Geçersiz admin ID");
        }
        
        // Get admin with password
        $admin = $this->db->fetch(
            "SELECT password FROM {$this->tableName} WHERE id = :id AND is_active = 1", 
            ['id' => $id]
        );
        
        if (!$admin) {
            throw new Exception("Admin bulunamadı");
        }
        
        // Verify current password
        if (!Utils::verifyPassword($currentPassword, $admin['password'])) {
            logActivity('WARNING', 'Invalid current password in change attempt', ['admin_id' => $id]);
            throw new Exception("Mevcut şifre yanlış");
        }
        
        // Validate new password
        if (strlen($newPassword) < 8) {
            throw new Exception("Yeni şifre en az 8 karakter olmalı");
        }
        
        if (!$this->isStrongPassword($newPassword)) {
            throw new Exception("Yeni şifre yeterince güçlü değil");
        }
        
        // Check if new password is different from current
        if (Utils::verifyPassword($newPassword, $admin['password'])) {
            throw new Exception("Yeni şifre mevcut şifre ile aynı olamaz");
        }
        
        try {
            $result = $this->updateAdmin($id, ['password' => $newPassword]);
            
            logActivity('INFO', 'Password changed successfully', ['admin_id' => $id]);
            
            return $result;
            
        } catch (Exception $e) {
            logActivity('ERROR', 'Password change failed: ' . $e->getMessage(), ['admin_id' => $id]);
            throw $e;
        }
    }
    
    // Check password strength
    private function isStrongPassword($password) {
        // At least 8 characters
        if (strlen($password) < 8) {
            return false;
        }
        
        // Contains lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }
        
        // Contains uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }
        
        // Contains number
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }
        
        // Contains special character
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
            return false;
        }
        
        return true;
    }
    
    // Restaurant'a göre adminleri getir - Enhanced
    public function getAdminsByRestaurant($restaurantId, $includeInactive = false) {
        if (!is_numeric($restaurantId) || $restaurantId <= 0) {
            throw new Exception("Geçersiz restaurant ID");
        }
        
        $sql = "SELECT id, username, full_name, email, role, last_login, created_at, is_active 
                FROM {$this->tableName} 
                WHERE restaurant_id = :restaurant_id";
        
        if (!$includeInactive) {
            $sql .= " AND is_active = 1";
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        return $this->db->fetchAll($sql, ['restaurant_id' => $restaurantId]);
    }
    
    // Admin istatistikleri - Enhanced
    public function getAdminStats($restaurantId) {
        if (!is_numeric($restaurantId) || $restaurantId <= 0) {
            throw new Exception("Geçersiz restaurant ID");
        }
        
        $stats = [];
        
        // Total active admins
        $stats['total'] = $this->db->fetch(
            "SELECT COUNT(*) as count FROM {$this->tableName} 
             WHERE restaurant_id = :restaurant_id AND is_active = 1",
            ['restaurant_id' => $restaurantId]
        )['count'];
        
        // Total inactive admins
        $stats['inactive'] = $this->db->fetch(
            "SELECT COUNT(*) as count FROM {$this->tableName} 
             WHERE restaurant_id = :restaurant_id AND is_active = 0",
            ['restaurant_id' => $restaurantId]
        )['count'];
        
        // Role distribution
        $stats['by_role'] = $this->db->fetchAll(
            "SELECT role, COUNT(*) as count FROM {$this->tableName} 
             WHERE restaurant_id = :restaurant_id AND is_active = 1 
             GROUP BY role ORDER BY count DESC",
            ['restaurant_id' => $restaurantId]
        );
        
        // Recently active (last 30 days)
        $stats['recent_active'] = $this->db->fetch(
            "SELECT COUNT(*) as count FROM {$this->tableName} 
             WHERE restaurant_id = :restaurant_id AND is_active = 1 
             AND last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            ['restaurant_id' => $restaurantId]
        )['count'];
        
        // New admins this month
        $stats['new_this_month'] = $this->db->fetch(
            "SELECT COUNT(*) as count FROM {$this->tableName} 
             WHERE restaurant_id = :restaurant_id 
             AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)",
            ['restaurant_id' => $restaurantId]
        )['count'];
        
        return $stats;
    }
    
    // Check admin permissions
    public function hasPermission($adminId, $permission) {
        $admin = $this->getAdmin($adminId);
        if (!$admin) {
            return false;
        }
        
        $rolePermissions = [
            'super_admin' => ['*'], // All permissions
            'admin' => [
                'menu.create', 'menu.update', 'menu.delete', 'menu.view',
                'category.create', 'category.update', 'category.delete', 'category.view',
                'waiter_calls.view', 'waiter_calls.update',
                'theme.update', 'theme.view',
                'stats.view'
            ],
            'manager' => [
                'menu.create', 'menu.update', 'menu.view',
                'category.create', 'category.update', 'category.view',
                'waiter_calls.view', 'waiter_calls.update',
                'stats.view'
            ],
            'staff' => [
                'menu.view', 'category.view',
                'waiter_calls.view', 'waiter_calls.update'
            ]
        ];
        
        $userPermissions = $rolePermissions[$admin['role']] ?? [];
        
        return in_array('*', $userPermissions) || in_array($permission, $userPermissions);
    }
    
    // Reset password (for forgot password functionality)
    public function resetPassword($email, $newPassword) {
        if (!Utils::validateEmail($email)) {
            throw new Exception("Geçersiz email adresi");
        }
        
        $admin = $this->getAdminByEmail($email);
        if (!$admin) {
            // Don't reveal if email exists
            throw new Exception("İşlem tamamlandı");
        }
        
        if (!$admin['is_active']) {
            throw new Exception("Hesap aktif değil");
        }
        
        if (!$this->isStrongPassword($newPassword)) {
            throw new Exception("Şifre yeterince güçlü değil");
        }
        
        try {
            $result = $this->updateAdmin($admin['id'], ['password' => $newPassword]);
            
            logActivity('INFO', 'Password reset completed', [
                'admin_id' => $admin['id'],
                'email' => $email
            ]);
            
            return $result;
            
        } catch (Exception $e) {
            logActivity('ERROR', 'Password reset failed: ' . $e->getMessage(), ['email' => $email]);
            throw new Exception("Şifre sıfırlama başarısız");
        }
    }
    
    // Get admin activity log
    public function getAdminActivity($adminId, $limit = 50) {
        if (!is_numeric($adminId) || $adminId <= 0) {
            throw new Exception("Geçersiz admin ID");
        }
        
        // This would typically query a separate activity log table
        // For now, return basic info from admin table
        $admin = $this->getAdmin($adminId);
        if (!$admin) {
            return [];
        }
        
        return [
            [
                'action' => 'last_login',
                'timestamp' => $admin['last_login'],
                'ip_address' => $admin['last_ip'] ?? null
            ]
        ];
    }
    
    // Bulk operations
    public function bulkUpdateStatus($adminIds, $status) {
        if (!is_array($adminIds) || empty($adminIds)) {
            throw new Exception("Geçersiz admin ID listesi");
        }
        
        if (!in_array($status, [0, 1])) {
            throw new Exception("Geçersiz durum");
        }
        
        $placeholders = str_repeat('?,', count($adminIds) - 1) . '?';
        $sql = "UPDATE {$this->tableName} SET is_active = ?, updated_at = NOW() 
                WHERE id IN ({$placeholders})";
        
        $params = array_merge([$status], $adminIds);
        
        try {
            $stmt = $this->db->query($sql, $params);
            $affectedRows = $stmt->rowCount();
            
            logActivity('INFO', 'Bulk admin status update', [
                'admin_ids' => $adminIds,
                'status' => $status,
                'affected_rows' => $affectedRows
            ]);
            
            return $affectedRows;
            
        } catch (Exception $e) {
            logActivity('ERROR', 'Bulk status update failed: ' . $e->getMessage(), [
                'admin_ids' => $adminIds,
                'status' => $status
            ]);
            throw new Exception("Toplu güncelleme başarısız");
        }
    }
}