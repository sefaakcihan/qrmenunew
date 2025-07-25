<?php
// backend/api/upload.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/utils.php';
require_once __DIR__ . '/../config/session_manager.php';

SessionManager::requireAuth();

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    Utils::errorResponse('Sadece POST method desteklenir', 405);
}

try {
    if (!isset($_FILES['file'])) {
        Utils::errorResponse('Dosya seçilmedi');
    }
    
    $file = $_FILES['file'];
    $directory = $_POST['directory'] ?? 'images';
    $resize = isset($_POST['resize']) && $_POST['resize'] === 'true';
    $maxWidth = intval($_POST['max_width'] ?? 800);
    $maxHeight = intval($_POST['max_height'] ?? 600);
    
    // Directory validation
    $allowedDirectories = ['images', 'logos', 'themes'];
    if (!in_array($directory, $allowedDirectories)) {
        $directory = 'images';
    }
    
    // Size validation
    if ($maxWidth > 2000) $maxWidth = 2000;
    if ($maxHeight > 2000) $maxHeight = 2000;
    
    // Dosyayı yükle
    $fileName = Utils::uploadImage($file, $directory);
    $fullPath = UPLOAD_PATH . $fileName;
    
    // Boyutlandırma
    if ($resize && file_exists($fullPath)) {
        $resizedPath = UPLOAD_PATH . $directory . '/resized_' . basename($fileName);
        if (Utils::resizeImage($fullPath, $resizedPath, $maxWidth, $maxHeight)) {
            // Orijinal dosyayı sil ve boyutlandırılmış olanı kullan
            unlink($fullPath);
            rename($resizedPath, $fullPath);
        }
    }
    
    Utils::successResponse([
        'filename' => $fileName,
        'url' => UPLOAD_URL . $fileName,
        'size' => filesize($fullPath),
        'directory' => $directory
    ], 'Dosya başarıyla yüklendi');
    
} catch (Exception $e) {
    Utils::errorResponse($e->getMessage());
}
?>