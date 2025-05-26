<?php
require_once '../PHP-pages/session_auth.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
  // إذا لم يكن المستخدم مسجل الدخول، قم بتوجيهه إلى صفحة تسجيل الدخول
  header("Location: login.php");
  exit();
}

// قائمة المستخدمين المسموح لهم بالوصول إلى لوحة التحكم
$allowedAdminUsers = [2320603, 2320598, 2320241];

// التحقق مما إذا كان المستخدم الحالي لديه صلاحيات الإدارة
if (!in_array($_SESSION['user_id'], $allowedAdminUsers)) {
  // إذا لم يكن المستخدم مسموحًا له، قم بتوجيهه إلى صفحة المستخدم العادي
  header("Location: ../User_page/user.php");
  exit();
}
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "aitp";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Get statistics from database
function getStatistics($conn)
{
  $stats = array();

  // Get total users
  $sql = "SELECT COUNT(*) as total_users FROM users";
  $result = $conn->query($sql);
  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $stats['total_users'] = $row['total_users'];
  } else {
    $stats['total_users'] = 0;
  }

  // Get total print requests - changed table name from print_orders to print_jobs
  $sql = "SELECT COUNT(*) as total_print_requests FROM print_jobs";
  $result = $conn->query($sql);
  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $stats['total_print_requests'] = $row['total_print_requests'];
  } else {
    $stats['total_print_requests'] = 0;
  }

  // Get total revenue - changed table name from print_orders to print_jobs
  $sql = "SELECT SUM(cost) as total_revenue FROM print_jobs";
  $result = $conn->query($sql);
  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $stats['total_revenue'] = $row['total_revenue'] ? $row['total_revenue'] : 0;
  } else {
    $stats['total_revenue'] = 0;
  }

  // Get completed orders - changed table name and status value based on print_jobs schema
  $sql = "SELECT COUNT(*) as completed_orders FROM print_jobs WHERE status = 'done'";
  $result = $conn->query($sql);
  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $stats['completed_orders'] = $row['completed_orders'];
  } else {
    $stats['completed_orders'] = 0;
  }

  return $stats;
}

// Create price settings table if it doesn't exist
function createPriceSettingsTable($conn)
{
  $sql = "CREATE TABLE IF NOT EXISTS price_settings (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      bw_single DECIMAL(10, 2) NOT NULL DEFAULT 0.50,
      color_single DECIMAL(10, 2) NOT NULL DEFAULT 1.50,
      bw_double DECIMAL(10, 2) NOT NULL DEFAULT 0.80,
      color_double DECIMAL(10, 2) NOT NULL DEFAULT 2.50,
      student_discount INT NOT NULL DEFAULT 10,
      professor_discount INT NOT NULL DEFAULT 15,
      staff_discount INT NOT NULL DEFAULT 5,
      bulk_discount INT NOT NULL DEFAULT 20,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  )";

  if ($conn->query($sql) === TRUE) {
    // Insert default record if it doesn't exist
    $checkSql = "SELECT COUNT(*) as count FROM price_settings";
    $result = $conn->query($checkSql);
    $row = $result->fetch_assoc();

    if ($row['count'] == 0) {
      $insertSql = "INSERT INTO price_settings (id, bw_single, color_single, bw_double, color_double) 
                    VALUES (1, 0.50, 1.50, 0.80, 2.50)";
      $conn->query($insertSql);
    }
  }
}

// Create printer status table if it doesn't exist
function createPrinterStatusTable($conn)
{
  $sql = "CREATE TABLE IF NOT EXISTS printer_status (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(100) NOT NULL DEFAULT 'HP LaserJet Pro M404dn',
      status VARCHAR(50) NOT NULL DEFAULT 'Connected',
      black_ink INT NOT NULL DEFAULT 35,
      cyan_ink INT NOT NULL DEFAULT 65,
      magenta_ink INT NOT NULL DEFAULT 15,
      yellow_ink INT NOT NULL DEFAULT 80,
      remaining_paper INT NOT NULL DEFAULT 250,
      pending_jobs INT NOT NULL DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  )";

  if ($conn->query($sql) === TRUE) {
    // Insert default record if it doesn't exist
    $checkSql = "SELECT COUNT(*) as count FROM printer_status";
    $result = $conn->query($checkSql);
    $row = $result->fetch_assoc();

    if ($row['count'] == 0) {
      $insertSql = "INSERT INTO printer_status (id) VALUES (1)";
      $conn->query($insertSql);
    }
  }
}

// Create printer notifications table if it doesn't exist
function createPrinterNotificationsTable($conn)
{
  $sql = "CREATE TABLE IF NOT EXISTS printer_notifications (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      date_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      message TEXT NOT NULL,
      level ENUM('info', 'warning', 'error') NOT NULL DEFAULT 'info'
  )";

  $conn->query($sql);
}

// Ensure necessary tables exist
createPriceSettingsTable($conn);
createPrinterStatusTable($conn);
createPrinterNotificationsTable($conn);

// Get price settings from database
function getPriceSettings($conn)
{
  $prices = array();

  $sql = "SELECT * FROM price_settings WHERE id = 1";
  $result = $conn->query($sql);

  if ($result && $result->num_rows > 0) {
    $prices = $result->fetch_assoc();
  } else {
    // Default values if no record exists
    $prices = array(
      'bw_single' => 0.50,
      'color_single' => 1.50,
      'bw_double' => 0.80,
      'color_double' => 2.50,
      'student_discount' => 10,
      'professor_discount' => 15,
      'staff_discount' => 5,
      'bulk_discount' => 20
    );
  }

  return $prices;
}

// Get recent print orders - adjusted for print_jobs schema
function getRecentPrintOrders($conn, $limit = 10)
{
  $orders = array();

  $sql = "SELECT pj.id, u.name as user_name, pj.file_name as document_type, 
            pj.num_pages as pages, pj.cost, pj.created_at as order_date, pj.status 
            FROM print_jobs pj 
            JOIN users u ON pj.user_id = u.id 
            ORDER BY pj.created_at DESC 
            LIMIT $limit";

  $result = $conn->query($sql);

  if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $orders[] = $row;
    }
  }

  return $orders;
}

// Get printer notifications
function getPrinterNotifications($conn, $limit = 5)
{
  $notifications = array();

  $sql = "SELECT date_time, message, level FROM printer_notifications ORDER BY date_time DESC LIMIT $limit";
  $result = $conn->query($sql);

  if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $notifications[] = $row;
    }
  }

  return $notifications;
}

// Get printer status
function getPrinterStatus($conn)
{
  $status = array();

  $sql = "SELECT * FROM printer_status WHERE id = 1";
  $result = $conn->query($sql);

  if ($result && $result->num_rows > 0) {
    $status = $result->fetch_assoc();
  } else {
    // Default values if no record exists
    $status = array(
      'name' => 'HP LaserJet Pro M404dn',
      'status' => 'Connected',
      'black_ink' => 35,
      'cyan_ink' => 65,
      'magenta_ink' => 15,
      'yellow_ink' => 80,
      'remaining_paper' => 250,
      'pending_jobs' => 5
    );
  }

  return $status;
}

// Update printer status with count of pending jobs
function updatePrinterStatus($conn)
{
  $sql = "SELECT COUNT(*) as pending_count FROM print_jobs WHERE status = 'pending'";
  $result = $conn->query($sql);
  $row = $result->fetch_assoc();
  $pending_count = $row['pending_count'];

  $update_sql = "UPDATE printer_status SET pending_jobs = ? WHERE id = 1";
  $stmt = $conn->prepare($update_sql);
  $stmt->bind_param("i", $pending_count);
  $stmt->execute();
  $stmt->close();
}

// Update printer status
updatePrinterStatus($conn);

// Save price settings
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_prices'])) {
  $bw_single = $_POST['bw_single'];
  $color_single = $_POST['color_single'];
  $bw_double = $_POST['bw_double'];
  $color_double = $_POST['color_double'];

  $sql = "UPDATE price_settings SET 
            bw_single = ?, 
            color_single = ?, 
            bw_double = ?, 
            color_double = ?, 
            student_discount = ?, 
            professor_discount = ?, 
            staff_discount = ?, 
            bulk_discount = ? 
            WHERE id = 1";

  $stmt = $conn->prepare($sql);
  $stmt->bind_param("dddddddd", $bw_single, $color_single, $bw_double, $color_double, $student_discount, $professor_discount, $staff_discount, $bulk_discount);

  if ($stmt->execute()) {
    $priceUpdateMessage = "Price settings updated successfully!";
  } else {
    $priceUpdateMessage = "Error updating price settings: " . $conn->error;
  }

  $stmt->close();
}

// Add coins to user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_coin'])) {
  $email = $_POST['email'];
  $amount = $_POST['amount'];

  // Find user by email
  $sql = "SELECT id, balance FROM users WHERE email = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $new_balance = $user['balance'] + $amount;

    // Update user balance using stored procedure instead of direct update
    $admin_name = "Admin"; // Assuming admin name
    $reason = "Manual balance addition"; // Reason for addition

    $sp_sql = "CALL add_funds_to_user(?, ?, ?, ?)";
    $sp_stmt = $conn->prepare($sp_sql);
    $sp_stmt->bind_param("sids", $admin_name, $user['id'], $amount, $reason);

    if ($sp_stmt->execute()) {
      $coinUpdateMessage = "Successfully added " . $amount . " AITP to user account!";
    } else {
      $coinUpdateMessage = "Error adding coins: " . $conn->error;
    }

    $sp_stmt->close();
  } else {
    $coinUpdateMessage = "User with this email not found!";
  }

  $stmt->close();
}

// Get all data for the dashboard
$statistics = getStatistics($conn);
$prices = getPriceSettings($conn);
$recentOrders = getRecentPrintOrders($conn);
$printerStatus = getPrinterStatus($conn);
$printerNotifications = getPrinterNotifications($conn);

// Add test notification for testing
if (!empty($_GET['test_notification'])) {
  $message = "This is a test printer notification.";
  $level = "info";
  $sql = "INSERT INTO printer_notifications (message, level) VALUES (?, ?)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss", $message, $level);
  $stmt->execute();
  header("Location: " . $_SERVER['PHP_SELF']);
  exit;
}

?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>AITP Dashboard | Print Management System</title>
  <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
  <link rel="icon" href="../../logo_printer/aitpn.ico" type="image/x-icon" />

  <!-- Fonts and icons -->
  <script src="assets/js/plugin/webfont/webfont.min.js"></script>
  <script>
    WebFont.load({
      google: { families: ["Public Sans:300,400,500,600,700"] },
      custom: {
        families: [
          "Font Awesome 5 Solid",
          "Font Awesome 5 Regular",
          "Font Awesome 5 Brands",
          "simple-line-icons",
        ],
        urls: ["assets/css/fonts.min.css"],
      },
      active: function () {
        sessionStorage.fonts = true;
      },
    });
  </script>

  <!-- CSS Files -->
  <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
  <link rel="stylesheet" href="assets/css/plugins.min.css" />
  <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />

  <!-- CSS Just for demo purpose, don't include it in your project -->
  <link rel="stylesheet" href="assets/css/demo.css" />

  <style>
    .ink-level {
      background-color: #f1f1f1;
      border-radius: 5px;
      height: 20px;
      margin-bottom: 15px;
      position: relative;
    }

    .ink-level-fill {
      border-radius: 5px;
      height: 20px;
      position: relative;
      text-align: center;
    }

    .ink-level-text {
      color: #fff;
      font-size: 12px;
      line-height: 20px;
      position: absolute;
      text-align: center;
      width: 100%;
    }

    .ink-level-good {
      background-color: #28a745;
    }

    .ink-level-medium {
      background-color: #ffc107;
    }

    .ink-level-low {
      background-color: #dc3545;
    }

    .status-indicator {
      display: inline-block;
      height: 10px;
      width: 10px;
      border-radius: 50%;
      margin-right: 5px;
    }

    .status-active {
      background-color: #28a745;
    }

    .status-inactive {
      background-color: #dc3545;
    }

    .alert-success {
      position: fixed;
      top: 60px;
      right: 20px;
      z-index: 9999;
      animation: fadeOut 5s forwards;
      padding: 15px;
    }

    @keyframes fadeOut {
      0% {
        opacity: 1;
      }

      80% {
        opacity: 1;
      }

      100% {
        opacity: 0;
        display: none;
      }
    }
  </style>
</head>

<body>
  <?php if (isset($priceUpdateMessage)): ?>
    <div class="alert alert-success">
      <?php echo $priceUpdateMessage; ?>
    </div>
  <?php endif; ?>

  <?php if (isset($coinUpdateMessage)): ?>
    <div class="alert alert-success">
      <?php echo $coinUpdateMessage; ?>
    </div>
  <?php endif; ?>

  <div class="wrapper">
    <!-- Sidebar -->
    <div class="sidebar" data-background-color="dark">
      <div class="sidebar-logo">
        <!-- Logo Header -->
        <div class="logo-header" data-background-color="dark">
          <a href="index.php" class="logo">
            <img src="../../logo_printer/aitpn.png" alt="navbar brand" class="navbar-brand" height="190" />
          </a>
          <div class="nav-toggle">
            <button class="btn btn-toggle toggle-sidebar">
              <i class="gg-menu-right"></i>
            </button>
            <button class="btn btn-toggle sidenav-toggler">
              <i class="gg-menu-left"></i>
            </button>
          </div>
          <button class="topbar-toggler more">
            <i class="gg-more-vertical-alt"></i>
          </button>
        </div>
        <!-- End Logo Header -->
      </div>
      <div class="sidebar-wrapper scrollbar scrollbar-inner">
        <div class="sidebar-content">
          <ul class="nav nav-secondary">
            <li class="nav-item active">
              <a href="index.php">
                <i class="fas fa-tachometer-alt"></i>
                <p>Dashboard</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="./datatables.php">
                <i class="fas fa-users"></i>
                <p>User Management</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="#printer">
                <i class="fas fa-print"></i>
                <p>Printer Status</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="#pricing">
                <i class="fa-solid fa-money-bill-trend-up"></i>
                <p>Price Settings</p>
              </a>
            </li>
            <li class="nav-item">
              <a data-bs-toggle="collapse" href="#settings">
                <i class="fa-regular fa-window-restore"></i>
                <p>Pages</p>
                <span class="caret"></span>
              </a>
              <div class="collapse" id="settings">
                <ul class="nav nav-collapse">
                  <li style="margin-left: 20px;">
                    <a href="../../Home_page/home_page.php">
                      <i class="fa-solid fa-laptop-file"></i>
                      <span>Home</span>
                    </a>
                  </li>
                </ul>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>
    <!-- End Sidebar -->

    <div class="main-panel">
      <div class="main-header">
        <div class="main-header-logo d-block d-lg-none">
          <!-- Logo Header -->
          <div class="logo-header" data-background-color="dark">
            <a href="index.php" class="logo">
              <img src="../../logo_printer/aitpn.png" alt="navbar brand" class="navbar-brand" height="190" />
            </a>
            <div class="nav-toggle">
              <button class="btn btn-toggle toggle-sidebar">
                <i class="gg-menu-right"></i>
              </button>
              <button class="btn btn-toggle sidenav-toggler">
                <i class="gg-menu-left"></i>
              </button>
            </div>
            <button class="topbar-toggler more">
              <i class="gg-more-vertical-alt"></i>
            </button>
          </div>
          <!-- End Logo Header -->
        </div>
        <!-- Navbar Header -->
        <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
          <div class="container-fluid">
            <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
              <li class="nav-item topbar-icon dropdown hidden-caret">
                <a class="nav-link dropdown-toggle" href="#" id="notifDropdown" role="button" data-bs-toggle="dropdown"
                  aria-haspopup="true" aria-expanded="false">
                  <i class="fa fa-bell"></i>
                  <span class="notification">4</span>
                </a>
                <ul class="dropdown-menu notif-box animated fadeIn" aria-labelledby="notifDropdown">
                  <li>
                    <div class="dropdown-title">
                      You have 4 new notifications
                    </div>
                  </li>
                  <li>
                    <div class="notif-scroll scrollbar-outer">
                      <div class="notif-center">
                        <a href="#">
                          <div class="notif-icon notif-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                          </div>
                          <div class="notif-content">
                            <span class="block">Low ink level</span>
                            <span class="time">5 minutes ago</span>
                          </div>
                        </a>
                        <a href="#">
                          <div class="notif-icon notif-primary">
                            <i class="fa fa-user-plus"></i>
                          </div>
                          <div class="notif-content">
                            <span class="block">New user registered</span>
                            <span class="time">12 minutes ago</span>
                          </div>
                        </a>
                        <a href="#">
                          <div class="notif-icon notif-success">
                            <i class="fa fa-print"></i>
                          </div>
                          <div class="notif-content">
                            <span class="block">Print job #4321 completed</span>
                            <span class="time">15 minutes ago</span>
                          </div>
                        </a>
                        <a href="#">
                          <div class="notif-icon notif-danger">
                            <i class="fa fa-exclamation-circle"></i>
                          </div>
                          <div class="notif-content">
                            <span class="block">Printer disconnected</span>
                            <span class="time">30 minutes ago</span>
                          </div>
                        </a>
                      </div>
                    </div>
                  </li>
                  <li>
                    <a class="see-all" href="javascript:void(0);">See all notifications<i class="fa fa-angle-right"></i>
                    </a>
                  </li>
                </ul>
              </li>

              <li class="nav-item topbar-user dropdown hidden-caret">
                <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                  <div class="avatar-sm">
                    <img src="assets/img/profile.jpg" alt="..." class="avatar-img rounded-circle" />
                  </div>
                  <span class="profile-username">
                    <span class="op-7">Hello,</span>
                    <span class="fw-bold"><?php echo $users['name'] ?? $_SESSION['user_name']; ?></span>
                  </span>
                </a>
                <ul class="dropdown-menu dropdown-user animated fadeIn">
                  <div class="dropdown-user-scroll scrollbar-outer">
                    <li>
                      <div class="user-box">
                        <div class="avatar-lg">
                          <img src="assets/img/profile.jpg" alt="image profile" class="avatar-img rounded" />
                        </div>
                        <div class="u-text">
                          <h4><?php echo $users['name'] ?? $_SESSION['user_name']; ?></h4>
                          <p class="text-muted"><?php echo $users['email'] ?? $_SESSION['user_email']; ?></p>
                          <a href="../../User_page/user.php" class="btn btn-xs btn-secondary btn-sm">View Profile</a>
                        </div>
                      </div>
                    </li>
                    <li>
                      <div class="dropdown-divider"></div>
                      <a class="btn btn-logout" href="../PHP-pages/logout.php">
                        <i class="fas fa-sign-out-alt"></i> Log out
                      </a>
                    </li>
                  </div>
                </ul>
              </li>
            </ul>
          </div>
        </nav>
        <!-- End Navbar -->
      </div>

      <div class="container">
        <div class="page-inner">
          <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-3 pb-4">
            <div>
              <h3 class="fw-bold mb-3">Dashboard</h3>
              <h6 class="op-7 mb-2">Print Management System</h6>
            </div>
            <div class="ms-md-auto py-2 py-md-0">
              <a href="#" class="btn btn-label-info btn-round me-2">
                <i class="fas fa-sync-alt me-2"></i>
                Refresh
              </a>
              <a href="#" class="btn btn-primary btn-round" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fa-solid fa-coins" style="color: #ffffff;"></i>
                Add Coin
              </a>
            </div>
          </div>

          <!-- Dashboard Stats -->
          <div class="row">
            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-primary bubble-shadow-small">
                        <i class="fas fa-users"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Users</p>
                        <h4 class="card-title"><?php echo number_format($statistics['total_users']); ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-info bubble-shadow-small">
                        <i class="fas fa-print"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Print Requests</p>
                        <h4 class="card-title"><?php echo number_format($statistics['total_print_requests']); ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-success bubble-shadow-small">
                        <i class="fas fa-money-bill-wave"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Revenue</p>
                        <h4 class="card-title">‎<?php echo number_format($statistics['total_revenue'], 2); ?> AITP</h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-secondary bubble-shadow-small">
                        <i class="far fa-check-circle"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Completed Orders</p>
                        <h4 class="card-title"><?php echo number_format($statistics['completed_orders']); ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Printer Status Section -->
          <div class="row" id="printer">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-head-row">
                    <div class="card-title">Printer Status</div>
                    <div class="card-tools">
                      <a href="#" class="btn btn-sm btn-label-info">
                        <i class="fas fa-sync-alt me-2"></i>
                        Refresh Status
                      </a>
                    </div>
                  </div>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6">
                      <div class="card card-round bg-light-gray">
                        <div class="card-body">
                          <div class="d-flex align-items-center mb-3">
                            <div class="avatar avatar-lg">
                              <i class="fas fa-print fa-2x text-primary"></i>
                            </div>
                            <div class="ms-3">
                              <h4 class="mb-0"><?php echo $printerStatus['name']; ?></h4>
                              <p class="mb-0">
                                <span
                                  class="status-indicator <?php echo ($printerStatus['status'] == 'Connected') ? 'status-active' : 'status-inactive'; ?>"></span>
                                <?php echo $printerStatus['status']; ?>
                              </p>
                            </div>
                          </div>

                          <div class="row">
                            <div class="col-md-6">
                              <h6>Black Ink</h6>
                              <div class="ink-level">
                                <div
                                  class="ink-level-fill <?php echo ($printerStatus['black_ink'] > 60) ? 'ink-level-good' : (($printerStatus['black_ink'] > 20) ? 'ink-level-medium' : 'ink-level-low'); ?>"
                                  style="width: <?php echo $printerStatus['black_ink']; ?>%">
                                  <span class="ink-level-text"><?php echo $printerStatus['black_ink']; ?>%</span>
                                </div>
                              </div>

                              <h6>Cyan Ink</h6>
                              <div class="ink-level">
                                <div
                                  class="ink-level-fill <?php echo ($printerStatus['cyan_ink'] > 60) ? 'ink-level-good' : (($printerStatus['cyan_ink'] > 20) ? 'ink-level-medium' : 'ink-level-low'); ?>"
                                  style="width: <?php echo $printerStatus['cyan_ink']; ?>%">
                                  <span class="ink-level-text"><?php echo $printerStatus['cyan_ink']; ?>%</span>
                                </div>
                              </div>

                              <h6>Magenta Ink</h6>
                              <div class="ink-level">
                                <div
                                  class="ink-level-fill <?php echo ($printerStatus['magenta_ink'] > 60) ? 'ink-level-good' : (($printerStatus['magenta_ink'] > 20) ? 'ink-level-medium' : 'ink-level-low'); ?>"
                                  style="width: <?php echo $printerStatus['magenta_ink']; ?>%">
                                  <span class="ink-level-text"><?php echo $printerStatus['magenta_ink']; ?>%</span>
                                </div>
                              </div>

                              <h6>Yellow Ink</h6>
                              <div class="ink-level">
                                <div
                                  class="ink-level-fill <?php echo ($printerStatus['yellow_ink'] > 60) ? 'ink-level-good' : (($printerStatus['yellow_ink'] > 20) ? 'ink-level-medium' : 'ink-level-low'); ?>"
                                  style="width: <?php echo $printerStatus['yellow_ink']; ?>%">
                                  <span class="ink-level-text"><?php echo $printerStatus['yellow_ink']; ?>%</span>
                                </div>
                              </div>
                            </div>

                            <div class="col-md-6">
                              <div class="card card-stats">
                                <div class="card-body text-center p-3">
                                  <div class="numbers">
                                    <p class="card-category">Remaining Paper</p>
                                    <h3 class="card-title"><?php echo $printerStatus['remaining_paper']; ?></h3>
                                  </div>
                                </div>
                              </div>

                              <div class="card card-stats">
                                <div class="card-body text-center p-3">
                                  <div class="numbers">
                                    <p class="card-category">Pending Jobs</p>
                                    <h3 class="card-title"><?php echo $printerStatus['pending_jobs']; ?></h3>
                                  </div>
                                </div>
                              </div>

                              <div class="mt-3">
                                <button class="btn btn-primary btn-sm w-100 mb-2">
                                  <i class="fas fa-cog me-2"></i>
                                  Printer Settings
                                </button>
                                <button class="btn btn-danger btn-sm w-100">
                                  <i class="fas fa-times-circle me-2"></i>
                                  Restart Printer
                                </button>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="col-md-6">
                      <div class="card card-round">
                        <div class="card-header">
                          <h4 class="card-title">Recent Printer Notifications</h4>
                        </div>
                        <div class="card-body p-0">
                          <div class="table-responsive">
                            <table class="table table-hover">
                              <thead>
                                <tr>
                                  <th>Date & Time</th>
                                  <th>Message</th>
                                  <th>Level</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php foreach ($printerNotifications as $notification): ?>
                                  <tr>
                                    <td><?php echo $notification['date_time']; ?></td>
                                    <td><?php echo $notification['message']; ?></td>
                                    <td>
                                      <span class="badge bg-<?php
                                      echo ($notification['level'] == 'Warning') ? 'warning' :
                                        (($notification['level'] == 'Error') ? 'danger' :
                                          (($notification['level'] == 'Info') ? 'info' : 'success'));
                                      ?>">
                                        <?php echo $notification['level']; ?>
                                      </span>
                                    </td>
                                  </tr>
                                <?php endforeach; ?>
                              </tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Recent Orders Section -->
          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-head-row">
                    <div class="card-title">Recent Print Orders</div>
                    <div class="card-tools">
                      <a href="#" class="btn btn-sm btn-label-primary">
                        View All
                      </a>
                    </div>
                  </div>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-hover">
                      <thead>
                        <tr>
                          <th>Order #</th>
                          <th>User</th>
                          <th>Document Type</th>
                          <th>Pages</th>
                          <th>Cost</th>
                          <th>Order Date</th>
                          <th>Status</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                          <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo $order['user_name']; ?></td>
                            <td><?php echo $order['document_type']; ?></td>
                            <td><?php echo $order['pages']; ?></td>
                            <td>AITP <?php echo number_format($order['cost'], 2); ?></td>
                            <td><?php echo $order['order_date']; ?></td>
                            <td>
                              <span class="badge bg-<?php
                              echo ($order['status'] == 'completed') ? 'success' :
                                (($order['status'] == 'pending') ? 'warning' :
                                  (($order['status'] == 'canceled') ? 'danger' : 'info'));
                              ?>">
                                <?php echo ucfirst($order['status']); ?>
                              </span>
                            </td>
                            <td>
                              <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-link">
                                  <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-link">
                                  <i class="fas fa-print"></i>
                                </button>
                              </div>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Pricing Settings Section -->
          <div class="row" id="pricing">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-head-row">
                    <div class="card-title">Price Settings</div>
                    <div class="card-tools">
                      <button type="submit" form="priceSettingsForm" class="btn btn-sm btn-label-success">
                        <i class="fas fa-save me-2"></i>
                        Save Changes
                      </button>
                    </div>
                  </div>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6">
                      <div class="card card-round">
                        <div class="card-header">
                          <h4 class="card-title">Standard Printing Prices</h4>
                        </div>
                        <div class="card-body">
                          <form id="priceSettingsForm" method="post" action="">
                            <input type="hidden" name="save_prices" value="1">
                            <div class="mb-3">
                              <label class="form-label">Price per page (Black and White)</label>
                              <div class="input-group">
                                <span class="input-group-text">AITP</span>
                                <input type="number" name="bw_single" class="form-control"
                                  value="<?php echo $prices['bw_single']; ?>" step="0.10">
                              </div>
                            </div>
                            <div class="mb-3">
                              <label class="form-label">Price per page (Color)</label>
                              <div class="input-group">
                                <span class="input-group-text">AITP</span>
                                <input type="number" name="color_single" class="form-control"
                                  value="<?php echo $prices['color_single']; ?>" step="0.10">
                              </div>
                            </div>
                            <div class="mb-3">
                              <label class="form-label">Price per double-sided page (Black and White)</label>
                              <div class="input-group">
                                <span class="input-group-text">AITP</span>
                                <input type="number" name="bw_double" class="form-control"
                                  value="<?php echo $prices['bw_double']; ?>" step="0.10">
                              </div>
                            </div>
                            <div class="mb-3">
                              <label class="form-label">Price per double-sided page (Color)</label>
                              <div class="input-group">
                                <span class="input-group-text">AITP</span>
                                <input type="number" name="color_double" class="form-control"
                                  value="<?php echo $prices['color_double']; ?>" step="0.10">
                              </div>
                            </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Add Coin Modal -->
          <div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="addUserModalLabel">Add More Coin</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="">
                  <div class="modal-body">
                    <input type="hidden" name="add_coin" value="1">
                    <div class="mb-3">
                      <label for="email" class="form-label">Email</label>
                      <input type="email" name="email" class="form-control" id="email" placeholder="Enter email"
                        required>
                    </div>
                    <div class="mb-3">
                      <label for="initialBalance" class="form-label">Top-Up Balance</label>
                      <div class="input-group">
                        <span class="input-group-text">AITP</span>
                        <input type="number" name="amount" class="form-control" id="initialBalance" placeholder="0.00"
                          required>
                      </div>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Coin</button>
                  </div>
                </form>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
  <!--   Core JS Files   -->
  <script src="assets/js/core/jquery-3.7.1.min.js"></script>
  <script src="assets/js/core/popper.min.js"></script>
  <script src="assets/js/core/bootstrap.min.js"></script>

  <!-- jQuery Scrollbar -->
  <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
  <!-- Kaiadmin JS -->
  <script src="assets/js/kaiadmin.min.js"></script>
  <!-- Kaiadmin DEMO methods, don't include it in your project! -->
  <script src="assets/js/setting-demo2.js"></script>
  <script>
    $("#displayNotif").on("click", function () {
      var placementFrom = $("#notify_placement_from option:selected").val();
      var placementAlign = $("#notify_placement_align option:selected").val();
      var state = $("#notify_state option:selected").val();
      var style = $("#notify_style option:selected").val();
      var content = {};

      content.message =
        'Turning standard Bootstrap alerts into "notify" like notifications';
      content.title = "Bootstrap notify";
      if (style == "withicon") {
        content.icon = "fa fa-bell";
      } else {
        content.icon = "none";
      }
      content.url = "index.php";
      content.target = "_blank";

      $.notify(content, {
        type: state,
        placement: {
          from: placementFrom,
          align: placementAlign,
        },
        time: 1000,
      });
    });
  </script>
</body>

</html>