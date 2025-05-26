<?php
// Example database connection file
// Replace with your actual database credentials

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'aitp';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set
$conn->set_charset("utf8mb4");
?>