<?php 
require_once 'session_auth.php'; // Include session authentication
// Database connection constants
const DB_HOST = 'localhost';
const DB_USER = 'root'; // Change to your database username
const DB_PASS = ''; // Change to your database password
const DB_NAME = 'aitp';

// Error and response messages
$errors = [];
$success = "";

// Create database connection
function connectDB() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // Check connection
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        return $conn;
    } catch (Exception $e) {
        die("Database connection error: " . $e->getMessage());
    }
}

// Sanitize user input
function sanitizeInput($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input);
    return $input;
}

// Check if email exists in the database
function emailExists($email) {
    $conn = connectDB();
    $email = $conn->real_escape_string($email);
    
    $query = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $exists = $result->num_rows > 0;
    
    $stmt->close();
    $conn->close();
    
    return $exists;
}

// Get user data by ID
function getUserById($id) {
    $conn = connectDB();
    $id = $conn->real_escape_string($id);
    
    $query = "SELECT id, name, email, balance FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $user = null;
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    }
    
    $stmt->close();
    $conn->close();
    
    return $user;
}

// Check if user is logged in and get user data
$users = null;
if (isset($_SESSION['user_id']) && $_SESSION['authenticated'] === true) {
    $users = getUserById($_SESSION['user_id']);
}

// Handle sign in request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'signin') {
    $email = sanitizeInput($_POST['email']);
    $password = sanitizeInput($_POST['password']);
    
    // Validate inputs
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    // If no validation errors, proceed with login
    if (empty($errors)) {
        $conn = connectDB();
        
        // Use prepared statement to prevent SQL injection
        $query = "SELECT id, name, email, balance FROM users WHERE email = ? AND password = MD5(?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $email, $password);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            // Login successful
            $user = $result->fetch_assoc();
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_balance'] = $user['balance'];
            $_SESSION['authenticated'] = true;
            $_SESSION['last_activity'] = time();
            
            // Update the last login time (optional)
            $update_query = "UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("i", $user['id']);
            $update_stmt->execute();
            $update_stmt->close();
            
            // Redirect to user profile page
            header("Location: ../User_page/user.php");
            exit();
        } else {
            // Login failed
            $errors[] = "Invalid email or password";
        }
        
        $stmt->close();
        $conn->close();
    }
}

// Handle password reset request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'reset') {
    $email = sanitizeInput($_POST['email']);
    
    // Validate email
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } else {
        // Check if email exists in the database
        if (emailExists($email)) {
            // Generate a random token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $conn = connectDB();
            
            // Store the token in the database
            // Note: You may need to create a password_resets table for this
            // For now, we'll just simulate success
            
            // Send the password reset link (in a real application, you would send an email)
            // For now, we'll just set a success message
            $success = "Password reset instructions have been sent to your email.";
            
            $conn->close();
        } else {
            // Don't reveal that email doesn't exist for security reasons
            $success = "If your email exists in our system, you will receive password reset instructions.";
        }
    }
}

// Handle logout request
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    // Destroy the session
    session_unset();
    session_destroy();
    
    // Redirect to login page
    header("Location: login.php");
    exit();
}

// JSON response for AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    if (!empty($errors)) {
        echo json_encode(['status' => 'error', 'errors' => $errors]);
    } elseif (!empty($success)) {
        echo json_encode(['status' => 'success', 'message' => $success]);
    }
    
    exit();
}

?>
