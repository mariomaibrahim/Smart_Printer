<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && $_SESSION['authenticated'] === true;
}

// Function to redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../login_pages/login.php");
        exit();
    }
}

// Function to redirect if already logged in
function redirectIfLoggedIn($destination = "../Home_page/options_page.php") {
    if (isLoggedIn()) {
        header("Location: " . $destination);
        exit();
    }
}

// Refresh last activity timestamp to maintain session
function updateLastActivity() {
    if (isLoggedIn()) {
        $_SESSION['last_activity'] = time();
    }
}

// Session timeout (30 minutes = 1800 seconds)
function checkSessionTimeout($timeout = 1800) {
    if (isLoggedIn() && isset($_SESSION['last_activity'])) {
        $inactive = time() - $_SESSION['last_activity'];
        
        if ($inactive >= $timeout) {
            // Destroy session if timeout
            session_unset();
            session_destroy();
            header("Location: ../login_pages/login.php?timeout=1");
            exit();
        }
    }
    
    // Update last activity time
    updateLastActivity();
}

// Call this function to check for session timeout
checkSessionTimeout();
?>