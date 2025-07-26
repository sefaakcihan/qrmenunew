<?php
// backend/config/utils.php

// Yardımcı fonksiyonlar
class Utils {

public static function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

public static function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / pow(1024, $pow), 2) . ' ' . $units[$pow];
}

public static function validateUploadedFile($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Dosya yükleme hatası');
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        throw new Exception('Dosya boyutu çok büyük');
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ALLOWED_MIME_TYPES)) {
        throw new Exception('Geçersiz dosya tipi');
    }
}

public static function generateSafeFileName($originalName) {
    $info = pathinfo($originalName);
    $ext = strtolower($info['extension']);
    return uniqid('file_') . '_' . time() . '.' . $ext;
}

public static function stripImageMetadata($imagePath) {
    if (!extension_loaded('exif')) return;
    
    try {
        $img = imagecreatefromstring(file_get_contents($imagePath));
        if ($img) {
            imagejpeg($img, $imagePath, 100);
            imagedestroy($img);
        }
    } catch (Exception $e) {
        // Log error
    }
}
    
    // JSON response
    public static function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Error response
    public static function errorResponse($message, $statusCode = 400) {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('c')
        ];
        self::jsonResponse($response, $statusCode);
    }
    
    // Success response
    public static function successResponse($data = [], $message = 'Success') {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c')
        ];
        self::jsonResponse($response);
    }
    
    // Dosya yükleme
    public static function uploadImage($file, $directory = 'images') {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Dosya yükleme hatası');
        }
        
        // Dosya boyutu kontrolü
        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception('Dosya boyutu çok büyük (Max: ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB)');
        }
        
        // Dosya tipi kontrolü
        $fileInfo = pathinfo($file['name']);
        $extension = strtolower($fileInfo['extension']);
        
        if (!in_array($extension, ALLOWED_IMAGE_TYPES)) {
            throw new Exception('Geçersiz dosya tipi. İzin verilen: ' . implode(', ', ALLOWED_IMAGE_TYPES));
        }
        
        // Benzersiz dosya adı oluşturma
        $fileName = uniqid('img_') . '.' . $extension;
        $uploadDir = UPLOAD_PATH . $directory . '/';
        $uploadPath = $uploadDir . $fileName;
        
        // Dizin oluşturma
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Dosyayı taşıma
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Dosya yükleme başarısız');
        }
        
        return $directory . '/' . $fileName;
    }
    
    // Görsel boyutlandırma
    // Görsel boyutlandırma - Bug düzeltmesi
    public static function resizeImage($sourcePath, $targetPath, $maxWidth = 800, $maxHeight = 600, $quality = 85) {
        if (!file_exists($sourcePath)) {
            throw new Exception('Kaynak dosya bulunamadı: ' . $sourcePath);
        }
        
        // GD extension kontrolü
        if (!extension_loaded('gd')) {
            throw new Exception('GD extension yüklü değil');
        }
        
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            throw new Exception('Geçersiz resim dosyası');
        }
        
        $sourceWidth = $imageInfo[0];
        $sourceHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];
        
        // Desteklenen format kontrolü
        $supportedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($mimeType, $supportedTypes)) {
            throw new Exception('Desteklenmeyen resim formatı: ' . $mimeType);
        }
        
        // Yeni boyutları hesapla
        $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight);
        if ($ratio >= 1) {
            // Resim zaten istenen boyuttan küçük, kopyala
            return copy($sourcePath, $targetPath);
        }
        
        $newWidth = intval($sourceWidth * $ratio);
        $newHeight = intval($sourceHeight * $ratio);
        
        // Kaynak resmi yükle
        $sourceImage = false;
        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $sourceImage = imagecreatefromwebp($sourcePath);
                }
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
        }
        
        if (!$sourceImage) {
            throw new Exception('Resim yüklenemedi: ' . $mimeType);
        }
        
        // Yeni resim oluştur
        $targetImage = imagecreatetruecolor($newWidth, $newHeight);
        if (!$targetImage) {
            imagedestroy($sourceImage);
            throw new Exception('Hedef resim oluşturulamadı');
        }
        
        // PNG ve GIF transparanlığını koru
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($targetImage, false);
            imagesavealpha($targetImage, true);
            $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
            imagefilledrectangle($targetImage, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Resmi boyutlandır
        $resizeResult = imagecopyresampled(
            $targetImage, $sourceImage, 
            0, 0, 0, 0, 
            $newWidth, $newHeight, $sourceWidth, $sourceHeight
        );
        
        if (!$resizeResult) {
            imagedestroy($sourceImage);
            imagedestroy($targetImage);
            throw new Exception('Resim boyutlandırılamadı');
        }
        
        // Hedef dizini oluştur
        $targetDir = dirname($targetPath);
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        // Hedef formatında kaydet
        $result = false;
        switch ($mimeType) {
            case 'image/jpeg':
                $result = imagejpeg($targetImage, $targetPath, $quality);
                break;
            case 'image/png':
                // PNG için quality'yi 0-9 arasına çevir
                $pngQuality = intval(9 * (100 - $quality) / 100);
                $result = imagepng($targetImage, $targetPath, $pngQuality);
                break;
            case 'image/webp':
                if (function_exists('imagewebp')) {
                    $result = imagewebp($targetImage, $targetPath, $quality);
                }
                break;
            case 'image/gif':
                $result = imagegif($targetImage, $targetPath);
                break;
        }
        
        // Belleği temizle
        imagedestroy($sourceImage);
        imagedestroy($targetImage);
        
        if (!$result) {
            throw new Exception('Resim kaydedilemedi');
        }
        
        return $result;
    }
    
    // Slug oluşturma (Türkçe karakter desteği)
    public static function createSlug($text) {
        $turkish = ['ç', 'ğ', 'ı', 'ö', 'ş', 'ü', 'Ç', 'Ğ', 'I', 'İ', 'Ö', 'Ş', 'Ü'];
        $english = ['c', 'g', 'i', 'o', 's', 'u', 'c', 'g', 'i', 'i', 'o', 's', 'u'];
        
        $text = str_replace($turkish, $english, $text);
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9-]/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        $text = trim($text, '-');
        
        return $text;
    }
    
    // Password hash
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    // Password verify
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    // Rate limiting
    private static $rateLimits = [];
    
    public static function rateLimit($key, $maxRequests = 60, $timeWindow = 3600) {
        $now = time();
        
        if (!isset(self::$rateLimits[$key])) {
            self::$rateLimits[$key] = [];
        }
        
        // Eski kayıtları temizle
        self::$rateLimits[$key] = array_filter(self::$rateLimits[$key], function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });
        
        // Limit kontrolü
        if (count(self::$rateLimits[$key]) >= $maxRequests) {
            return false;
        }
        
        // Yeni request'i kaydet
        self::$rateLimits[$key][] = $now;
        return true;
    }
}
?>