<?php
// backend/models/Table.php
require_once __DIR__ . '/../config/database.php';

class Table {
    private $db;
    
   // Tüm model dosyalarında:
public function __construct() {
    $this->db = Database::getInstance(); // new Database() yerine
}
    
    // Tüm masaları getir
    public function getAllTables($restaurantId) {
        $sql = "SELECT * FROM tables WHERE restaurant_id = :restaurant_id ORDER BY table_number";
        return $this->db->fetchAll($sql, ['restaurant_id' => $restaurantId]);
    }
    
    // Masa getir
    public function getTable($id) {
        return $this->db->fetch("SELECT * FROM tables WHERE id = :id", ['id' => $id]);
    }
    
    // QR kod ile masa getir
    public function getTableByQR($qrCode) {
        return $this->db->fetch("SELECT * FROM tables WHERE qr_code = :qr_code", ['qr_code' => $qrCode]);
    }
    
    // Yeni masa ekle
    public function createTable($data) {
        $required = ['restaurant_id', 'table_number'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Alan gerekli: {$field}");
            }
        }
        
        // QR kod oluştur
        if (!isset($data['qr_code'])) {
            $data['qr_code'] = 'QR_TABLE_' . strtoupper(uniqid());
        }
        
        return $this->db->insert('tables', $data);
    }
    
    // Masa güncelle
    public function updateTable($id, $data) {
        return $this->db->update('tables', $data, 'id = :id', ['id' => $id]);
    }
    
    // Masa sil
    public function deleteTable($id) {
        return $this->db->delete('tables', 'id = :id', ['id' => $id]);
    }
}
?>