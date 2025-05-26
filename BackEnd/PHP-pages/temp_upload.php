<?php
// Include this file at the beginning of upload.php to handle temporary file uploads

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Include database connection
require_once 'db_connect.php';

// Function to sanitize file name
function sanitizeFileName($fileName) {
    // Remove potentially dangerous characters
    $sanitized = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $fileName);
    return $sanitized;
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    
    // Check for errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        ];
        
        $errorMessage = $errorMessages[$file['error']] ?? 'Unknown upload error';
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $errorMessage]);
        exit;
    }
    
    // Validate file size (max 10MB)
    $maxFileSize = 10 * 1024 * 1024; // 10MB in bytes
    if ($file['size'] > $maxFileSize) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'File size exceeds maximum limit of 10MB']);
        exit;
    }
    
    // Validate file type
    $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 
                     'image/jpeg', 'image/jpg', 'image/png'];
    
    if (!in_array($file['type'], $allowedTypes)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed types: PDF, DOC, DOCX, JPG, PNG']);
        exit;
    }
    
    // Create temp directory if it doesn't exist
    $tempDir = '../temp_uploads/';
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }
    
    // Generate unique filename for temporary storage
    $tempFileName = 'temp_' . time() . '_' . sanitizeFileName($file['name']);
    $tempFilePath = $tempDir . $tempFileName;
    
    // Move uploaded file to temporary location
    if (move_uploaded_file($file['tmp_name'], $tempFilePath)) {
        // File uploaded successfully to temporary location
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'File uploaded to temporary storage',
            'file_path' => $tempFilePath,
            'file_name' => $tempFileName
        ]);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
        exit;
    }
}

// For any other HTTP method or missing file
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit;
?>