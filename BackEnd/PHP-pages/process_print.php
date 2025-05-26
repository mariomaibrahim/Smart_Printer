<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'db_connect.php';

// Function to calculate printing cost based on options
function calculatePrintCost($color, $sides, $pageCount, $copies) {
    // Base costs (in AITP currency)
    $bwCostPerPage = 0.50; // Black & white cost per page
    $colorCostPerPage = 1.50; // Color cost per page
    
    // Calculate base cost per page based on color selection
    $costPerPage = ($color === 'color') ? $colorCostPerPage : $bwCostPerPage;
    
    // Apply discount for double-sided printing (25% discount per page)
    if ($sides === 'two-sided') {
        $costPerPage *= 0.75; // 25% discount
    }
    
    // Calculate total cost
    $totalCost = $costPerPage * $pageCount * $copies;
    
    // Round to 2 decimal places
    return round($totalCost, 2);
}

// Function to check user balance
function checkUserBalance($userId, $conn) {
    $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['balance'];
    }
    
    return 0; // Return 0 if user not found
}

// Function to update user balance
function updateUserBalance($userId, $amount, $conn) {
    $stmt = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?");
    $stmt->bind_param("did", $amount, $userId, $amount);
    $stmt->execute();
    
    // If rows affected is 1, the update was successful
    return $stmt->affected_rows === 1;
}

// Handle AJAX request to calculate cost
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'calculate_cost') {
    // Get parameters
    $color = $_POST['color'] ?? 'bw';
    $sides = $_POST['sides'] ?? 'one-sided';
    $pageCount = intval($_POST['page_count'] ?? 0);
    $copies = intval($_POST['copies'] ?? 1);
    
    // Calculate cost
    $cost = calculatePrintCost($color, $sides, $pageCount, $copies);
    
    // Return cost as JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'cost' => $cost]);
    exit;
}

// Handle print job submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_print') {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    
    // Get form data
    $color = $_POST['color'] ?? 'bw';
    $sides = $_POST['sides'] ?? 'one-sided';
    $layout = $_POST['layout'] ?? 'portrait';
    $pageOption = $_POST['pages'] ?? 'all';
    $pageRange = $_POST['page_range'] ?? '';
    $copies = intval($_POST['copies'] ?? 1);
    $tempFilePath = $_POST['temp_file_path'] ?? '';
    $originalFileName = $_POST['original_file_name'] ?? '';
    $pageCount = intval($_POST['page_count'] ?? 0);
    
    // Validate required data
    if (empty($tempFilePath) || empty($originalFileName) || $pageCount <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Missing required data']);
        exit;
    }
    
    // Calculate cost
    $cost = calculatePrintCost($color, $sides, $pageCount, $copies);
    
    // Check user balance
    $userBalance = checkUserBalance($userId, $conn);
    
    if ($userBalance < $cost) {
        // Insufficient balance
        // Delete temporary file
        if (file_exists($tempFilePath)) {
            unlink($tempFilePath);
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Insufficient balance. Your balance: ' . $userBalance . ' AITP, Required: ' . $cost . ' AITP'
        ]);
        exit;
    }
    
    // Process payment (deduct balance)
    $paymentSuccess = updateUserBalance($userId, $cost, $conn);
    
    if (!$paymentSuccess) {
        // Payment failed
        if (file_exists($tempFilePath)) {
            unlink($tempFilePath);
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Payment failed. Please try again.']);
        exit;
    }
    
    // Payment successful, proceed with saving file permanently
    $uploadsDir = '../uploads/'; // Adjust path as needed
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }
    
    // Generate a unique filename
    $fileExt = pathinfo($originalFileName, PATHINFO_EXTENSION);
    $newFileName = uniqid('print_') . '.' . $fileExt;
    $finalPath = $uploadsDir . $newFileName;
    
    // Move file from temp to permanent location
    if (!rename($tempFilePath, $finalPath)) {
        // Failed to move file, refund the user
        updateUserBalance($userId, -$cost, $conn); // Add back the amount (negative subtraction)
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'File processing failed. Your account has not been charged.']);
        exit;
    }
    
    // Save print job in database
    $stmt = $conn->prepare("INSERT INTO print_jobs (user_id, file_name, original_file_name, file_path, num_pages, 
                           num_copies, color_mode, print_sides, orientation, page_range, cost, status) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    
    $stmt->bind_param("isssiissssds", 
        $userId, 
        $newFileName, 
        $originalFileName, 
        $finalPath, 
        $pageCount, 
        $copies, 
        $color, 
        $sides, 
        $layout, 
        $pageOption === 'custom' ? $pageRange : $pageOption,
        $cost
    );
    
    $result = $stmt->execute();
    $printJobId = $conn->insert_id;
    
    if (!$result) {
        // Failed to save to database, refund the user
        updateUserBalance($userId, -$cost, $conn); // Add back the amount
        
        // Try to remove the file
        if (file_exists($finalPath)) {
            unlink($finalPath);
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error. Your account has not been charged.']);
        exit;
    }
    
    // Add transaction record
    $stmt = $conn->prepare("INSERT INTO transactions (user_id, amount, type, description, job_id) 
                           VALUES (?, ?, 'debit', ?, ?)");
    
    $description = "Print job: " . $originalFileName . " (" . $pageCount . " pages, " . $copies . " copies)";
    $stmt->bind_param("idsi", $userId, $cost, $description, $printJobId);
    $stmt->execute();
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Print job submitted successfully!',
        'job_id' => $printJobId,
        'cost' => $cost,
        'remaining_balance' => $userBalance - $cost
    ]);
    exit;
}

// For direct access to this script, return error
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}
?>