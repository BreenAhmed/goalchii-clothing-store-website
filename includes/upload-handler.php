<?php
/**
 * File Upload Handler for Product Images
 */

function uploadProductImage($file, $productId = null) {
    // Allowed image types
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    
    // Check if file was uploaded
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'No file uploaded or upload error occurred.'];
    }
    
    // Check file size
    if ($file['size'] > $maxFileSize) {
        return ['success' => false, 'error' => 'File size exceeds 5MB limit.'];
    }
    
    // Check file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.'];
    }
    
    // Create upload directory if it doesn't exist
    $uploadDir = 'assets/images/products/';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            return ['success' => false, 'error' => 'Failed to create upload directory.'];
        }
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'product_' . ($productId ? $productId . '_' : '') . time() . '_' . uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Return relative path for database storage
        return [
            'success' => true, 
            'path' => $filepath,
            'url' => '/' . $filepath // For use in img src
        ];
    } else {
        return ['success' => false, 'error' => 'Failed to move uploaded file.'];
    }
}

function deleteProductImage($filepath) {
    // Remove leading slash if present
    $filepath = ltrim($filepath, '/');
    
    // Security check - only allow deletion of files in products or blog directories
    if (strpos($filepath, 'assets/images/products/') !== 0 && strpos($filepath, 'assets/images/blog/') !== 0) {
        return false;
    }
    
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

function uploadBlogImage($file) {
    // Allowed image types
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    
    // Check if file was uploaded
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'No file uploaded or upload error occurred.'];
    }
    
    // Check file size
    if ($file['size'] > $maxFileSize) {
        return ['success' => false, 'error' => 'File size exceeds 5MB limit.'];
    }
    
    // Check file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.'];
    }
    
    // Create upload directory if it doesn't exist
    $uploadDir = 'assets/images/blog/';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            return ['success' => false, 'error' => 'Failed to create upload directory.'];
        }
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'blog_' . time() . '_' . uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Return relative path for database storage
        return [
            'success' => true, 
            'path' => $filepath,
            'url' => '/' . $filepath // For use in img src
        ];
    } else {
        return ['success' => false, 'error' => 'Failed to move uploaded file.'];
    }
}

function getImageUrl($path) {
    // If it's already a full URL, return as is
    if (preg_match('/^https?:\/\//', $path)) {
        return $path;
    }
    
    // If it's a relative path, return with leading slash
    return '/' . ltrim($path, '/');
}

