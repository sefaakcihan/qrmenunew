<?php
// backend/api/waiter-call.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/utils.php';
require_once __DIR__ . '/../config/session_manager.php';
require_once __DIR__ . '/../models/WaiterCall.php';
require_once __DIR__ . '/../models/Table.php';

$method = $_SERVER['REQUEST_METHOD'];
$waiterCallModel = new WaiterCall();
$tableModel = new Table();

try {
    switch ($method) {
        case 'GET':
            SessionManager::requireAuth(); // Admin only
            
            $restaurantId = $_GET['restaurant_id'] ?? 1;
            $action = $_GET['action'] ?? 'pending';
            
            if ($action === 'pending') {
                $calls = $waiterCallModel->getPendingCalls($restaurantId);
            } else {
                $calls = $waiterCallModel->getCallHistory($restaurantId);
            }
            
            Utils::successResponse($calls);
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            // JSON parse hatası kontrolü
            if (json_last_error() !== JSON_ERROR_NONE) {
                Utils::errorResponse('Geçersiz JSON formatı');
            }
            
            $action = $input['action'] ?? 'create';
            
            if ($action === 'create') {
                $tableId = $input['table_id'] ?? null;
                $qrCode = $input['qr_code'] ?? null;
                $message = $input['message'] ?? 'Garson çağrısı';
                $priority = $input['priority'] ?? 'medium';
                
                // QR kod ile masa bulma
                if ($qrCode && !$tableId) {
                    $table = $tableModel->getTableByQR($qrCode);
                    if ($table) {
                        $tableId = $table['id'];
                    }
                }
                
                if (!$tableId) {
                    Utils::errorResponse('Masa bilgisi gerekli');
                }
                
                $id = $waiterCallModel->createCall($tableId, $message, $priority);
                Utils::successResponse(['id' => $id], 'Garson çağrısı gönderildi');
                
            } elseif ($action === 'update_status') {
                SessionManager::requireAuth(); // Admin only
                
                $id = $input['id'] ?? null;
                $status = $input['status'] ?? null;
                
                if (!$id || !$status) {
                    Utils::errorResponse('ID ve durum gerekli');
                }
                
                $waiterCallModel->updateCallStatus($id, $status);
                Utils::successResponse([], 'Çağrı durumu güncellendi');
            } else {
                Utils::errorResponse('Geçersiz action parametresi');
            }
            break;
            
        default:
            Utils::errorResponse('Desteklenmeyen method', 405);
    }
    
} catch (Exception $e) {
    Utils::errorResponse($e->getMessage());
}
?>