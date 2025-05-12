<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
  header("Location: login.html");
  exit;
}

$id = $_SESSION['user'];
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<h2>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h2>
<p>Your email: <?php echo $user['email']; ?></p>
<a href="logout.php">Logout</a>