<?php
// backend/models/WaiterCall.php
require_once __DIR__ . '/../config/database.php';

class WaiterCall {
    private $db;
    
   // Tüm model dosyalarında:
public function __construct() {
    $this->db = Database::getInstance(); // new Database() yerine
}
    
    // Rate limiting kontrolü iyileştirmesi
    public function createCall($tableId, $message = null, $priority = 'medium') {
        // Masa ID'si doğrulama
        if (!$tableId || !is_numeric($tableId)) {
            throw new Exception("Geçersiz masa bilgisi");
        }
        
        // Masa varlığını kontrol et
        $table = $this->db->fetch("SELECT id FROM tables WHERE id = :id", ['id' => $tableId]);
        if (!$table) {
            throw new Exception("Masa bulunamadı");
        }
        
        // Rate limiting kontrolü
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!Utils::rateLimit("waiter_call_{$clientIp}", 10, 3600)) {
            throw new Exception("Çok fazla çağrı yapıyorsunuz. Lütfen bekleyin.");
        }
        
        // Aynı masadan son 5 dakikada çağrı var mı kontrol et
        $recentCall = $this->db->fetch(
            "SELECT id FROM waiter_calls 
             WHERE table_id = :table_id 
             AND status IN ('pending', 'acknowledged') 
             AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)",
            ['table_id' => $tableId]
        );
        
        if ($recentCall) {
            throw new Exception("Bu masa için zaten bekleyen bir çağrı bulunuyor.");
        }
        
        // Mesajı temizle ve doğrula
        $message = $message ? Database::sanitize($message) : 'Garson çağrısı';
        if (strlen($message) > 500) {
            throw new Exception("Mesaj çok uzun. Maksimum 500 karakter.");
        }
        
        // Priority kontrolü
        if (!in_array($priority, ['low', 'medium', 'high'])) {
            $priority = 'medium';
        }
        
        $data = [
            'table_id' => $tableId,
            'message' => $message,
            'priority' => $priority,
            'status' => 'pending'
        ];
        
        return $this->db->insert('waiter_calls', $data);
    }
    
    // Bekleyen çağrıları getir
    public function getPendingCalls($restaurantId) {
        $sql = "SELECT wc.*, t.table_number, t.restaurant_id
                FROM waiter_calls wc
                JOIN tables t ON wc.table_id = t.id
                WHERE t.restaurant_id = :restaurant_id 
                AND wc.status = 'pending'
                ORDER BY wc.priority DESC, wc.created_at ASC";
        
        return $this->db->fetchAll($sql, ['restaurant_id' => $restaurantId]);
    }
    
    // Çağrı durumunu güncelle
    public function updateCallStatus($id, $status) {
        $validStatuses = ['pending', 'acknowledged', 'completed', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            throw new Exception("Geçersiz durum");
        }
        
        $data = ['status' => $status];
        
        if ($status === 'acknowledged') {
            $data['acknowledged_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'completed') {
            $data['completed_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->db->update('waiter_calls', $data, 'id = :id', ['id' => $id]);
    }
    
    // Çağrı geçmişini getir
    public function getCallHistory($restaurantId, $limit = 50) {
        $sql = "SELECT wc.*, t.table_number
                FROM waiter_calls wc
                JOIN tables t ON wc.table_id = t.id
                WHERE t.restaurant_id = :restaurant_id
                ORDER BY wc.created_at DESC
                LIMIT :limit";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(':restaurant_id', $restaurantId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
?>