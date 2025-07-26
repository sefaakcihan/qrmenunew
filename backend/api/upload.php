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
    // Rate limiting
    $clientIp = getClientIP();
    if (!Utils::rateLimit("upload_{$clientIp}", 10, 3600)) {
        Utils::errorResponse('Çok fazla dosya yükleme denemesi. Lütfen bekleyin.', 429);
    }
    
    if (!isset($_FILES['file'])) {
        Utils::errorResponse('Dosya seçilmedi');
    }
    
    $file = $_FILES['file'];
    $directory = Database::sanitize($_POST['directory'] ?? 'images');
    $resize = isset($_POST['resize']) && $_POST['resize'] === 'true';
    $maxWidth = max(100, min(2000, intval($_POST['max_width'] ?? 800)));
    $maxHeight = max(100, min(2000, intval($_POST['max_height'] ?? 600)));
    
    // Directory validation
    $allowedDirectories = ['images', 'logos', 'themes'];
    if (!in_array($directory, $allowedDirectories)) {
        $directory = 'images';
    }
    
    // Comprehensive file validation
    Utils::validateUploadedFile($file);
    
    // Generate safe filename
    $safeFileName = Utils::generateSafeFileName($file['name']);
    $targetDirectory = $directory . '/';
    $relativePath = $targetDirectory . $safeFileName;
    $fullPath = UPLOAD_PATH . $relativePath;
    
    // Ensure target directory exists
    $targetDir = UPLOAD_PATH . $targetDirectory;
    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0755, true)) {
            throw new Exception('Hedef dizin oluşturulamadı');
        }
    }
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        throw new Exception('Dosya taşınamadı');
    }
    
    // Strip metadata for security
    Utils::stripImageMetadata($fullPath);
    
    // Resize if requested
    if ($resize) {
        try {
            $tempResizedPath = $fullPath . '.resized';
            Utils::resizeImage($fullPath, $tempResizedPath, $maxWidth, $maxHeight);
            
            // Replace original with resized
            if (file_exists($tempResizedPath)) {
                unlink($fullPath);
                rename($tempResizedPath, $fullPath);
            }
        } catch (Exception $resizeError) {
            // Log resize error but don't fail the upload
            logActivity('WARNING', 'Resize failed: ' . $resizeError->getMessage());
        }
    }
    
    // Verify final file
    if (!file_exists($fullPath) || filesize($fullPath) === 0) {
        throw new Exception('Dosya kaydedilemedi');
    }
    
    // Generate thumbnail for images (optional)
    $thumbnailPath = null;
    if (in_array($directory, ['images', 'logos'])) {
        try {
            $thumbDir = UPLOAD_PATH . $targetDirectory . 'thumbs/';
            if (!is_dir($thumbDir)) {
                mkdir($thumbDir, 0755, true);
            }
            
            $thumbnailPath = $targetDirectory . 'thumbs/' . $safeFileName;
            $fullThumbPath = UPLOAD_PATH . $thumbnailPath;
            
            Utils::resizeImage($fullPath, $fullThumbPath, 150, 150);
        } catch (Exception $thumbError) {
            // Thumbnail creation is optional
            logActivity('INFO', 'Thumbnail creation failed: ' . $thumbError->getMessage());
        }
    }
    
    // Success response
    $response = [
        'filename' => $relativePath,
        'original_name' => $file['name'],
        'url' => UPLOAD_URL . $relativePath,
        'size' => filesize($fullPath),
        'size_formatted' => Utils::formatFileSize(filesize($fullPath)),
        'directory' => $directory,
        'mime_type' => mime_content_type($fullPath),
        'dimensions' => getimagesize($fullPath)
    ];
    
    if ($thumbnailPath && file_exists(UPLOAD_PATH . $thumbnailPath)) {
        $response['thumbnail_url'] = UPLOAD_URL . $thumbnailPath;
    }
    
    Utils::successResponse($response, 'Dosya başarıyla yüklendi');
    
} catch (Exception $e) {
    // Clean up failed upload
    if (isset($fullPath) && file_exists($fullPath)) {
        unlink($fullPath);
    }
    
    logActivity('ERROR', 'Upload failed: ' . $e->getMessage(), [
        'file' => $file['name'] ?? 'unknown',
        'size' => $file['size'] ?? 0,
        'error' => $file['error'] ?? 0
    ]);
    
    Utils::errorResponse($e->getMessage());
}
?>