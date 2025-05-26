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

// إذا وصل التنفيذ إلى هنا، فهذا يعني أن المستخدم مسجل الدخول ولديه صلاحيات الإدارة
// يمكن متابعة عرض محتوى لوحة التحكم...

// إنشاء اتصال بقاعدة البيانات
$servername = "localhost";
$username = "root";  // قم بتغيير هذا إلى اسم المستخدم الخاص بك
$password = "";      // قم بتغيير هذا إلى كلمة المرور الخاصة بك
$dbname = "aitp";

// إنشاء الاتصال بقاعدة البيانات
$conn = new mysqli($servername, $username, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
  die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// معالجة طلب تحديث المعلومات
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_user'])) {
  $id = $_POST['user_id'];
  $name = $_POST['name'];
  $email = $_POST['email'];
  $password = $_POST['password'];
  $balance = $_POST['balance'];

  $sql = "UPDATE users SET name='$name', email='$email', password='$password', balance='$balance' WHERE id=$id";

  if ($conn->query($sql) === TRUE) {
    $success_message = "تم تحديث بيانات المستخدم بنجاح";
  } else {
    $error_message = "خطأ في تحديث البيانات: " . $conn->error;
  }
}

// استعلام لاسترجاع بيانات المستخدمين
$sql = "SELECT * FROM users";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>AITP Dashboard | Users</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
  <link rel="icon" href="../../logo_printer/aitpn.ico" type="image/x-icon"/>

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
</head>

<body>
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
            <li class="nav-item">
              <a href="index.php">
                <i class="fas fa-tachometer-alt"></i>
                <p>Dashboard</p>
              </a>
            </li>
            <li class="nav-item active">
              <a href="#users">
                <i class="fas fa-users"></i>
                <p>User Management</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="./index.php#printer">
                <i class="fas fa-print"></i>
                <p>Printer Status</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="./index.php#pricing">
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
          <div class="page-header">
            <h3 class="fw-bold mb-3">USERS</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home">
                <a href="#">
                  <i class="icon-home"></i>
                </a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Users Table</a>
              </li>
            </ul>
          </div>

          <!-- رسالة نجاح أو خطأ -->
          <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
          <?php endif; ?>

          <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
          <?php endif; ?>

          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <h4 class="card-title">Users Management</h4>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table id="basic-datatables" class="display table table-striped table-hover">
                      <thead>
                        <tr>
                          <th>ID</th>
                          <th>Name</th>
                          <th>Email</th>
                          <th>Password</th>
                          <th>Balance</th>
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tfoot>
                        <tr>
                          <th>ID</th>
                          <th>Name</th>
                          <th>Email</th>
                          <th>Password</th>
                          <th>Balance</th>
                          <th>Action</th>
                        </tr>
                      </tfoot>
                      <tbody>
                        <?php
                        // عرض بيانات المستخدمين من قاعدة البيانات
                        if ($result->num_rows > 0) {
                          while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row["id"] . "</td>";
                            echo "<td>" . $row["name"] . "</td>";
                            echo "<td>" . $row["email"] . "</td>";
                            echo "<td>" . $row["password"] . "</td>";
                            echo "<td>$" . $row["balance"] . "</td>";
                            echo "<td>
                                        <div class='btn-group'>
                                          <button type='button' class='btn btn-sm btn-link edit-user' data-bs-toggle='modal' data-bs-target='#editModal' 
                                            data-id='" . $row["id"] . "' 
                                            data-name='" . $row["name"] . "' 
                                            data-email='" . $row["email"] . "' 
                                            data-password='" . $row["password"] . "' 
                                            data-balance='" . $row["balance"] . "'>
                                            <i class='fas fa-edit'></i>
                                          </button>
                                          <button type='button' class='btn btn-sm btn-link text-danger'>
                                            <i class='fas fa-ban'></i>
                                          </button>
                                        </div>
                                      </td>";
                            echo "</tr>";
                          }
                        } else {
                          echo "<tr><td colspan='6' class='text-center'>لا توجد بيانات متاحة</td></tr>";
                        }
                        ?>
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
    <!-- End Custom template -->
  </div>

  <!-- Modal تعديل المستخدم -->
  <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editModalLabel">تعديل بيانات المستخدم</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="post" action="">
          <div class="modal-body">
            <input type="hidden" name="user_id" id="edit_user_id">
            <div class="mb-3">
              <label for="edit_name" class="form-label">Name</label>
              <input type="text" class="form-control" id="edit_name" name="name" required>
            </div>
            <div class="mb-3">
              <label for="edit_email" class="form-label">Email</label>
              <input type="email" class="form-control" id="edit_email" name="email" required>
            </div>
            <div class="mb-3">
              <label for="edit_password" class="form-label">Password</label>
              <input type="text" class="form-control" id="edit_password" name="password" required>
            </div>
            <div class="mb-3">
              <label for="edit_balance" class="form-label">Balance</label>
              <input type="number" step="0.01" class="form-control" id="edit_balance" name="balance" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
            <button type="submit" name="update_user" class="btn btn-primary">حفظ التغييرات</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!--   Core JS Files   -->
  <script src="assets/js/core/jquery-3.7.1.min.js"></script>
  <script src="assets/js/core/popper.min.js"></script>
  <script src="assets/js/core/bootstrap.min.js"></script>

  <!-- jQuery Scrollbar -->
  <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
  <!-- Datatables -->
  <script src="assets/js/plugin/datatables/datatables.min.js"></script>
  <!-- Kaiadmin JS -->
  <script src="assets/js/kaiadmin.min.js"></script>
  <!-- Kaiadmin DEMO methods, don't include it in your project! -->
  <script src="assets/js/setting-demo2.js"></script>
  <script>
    $(document).ready(function () {
      // تهيئة جدول البيانات
      $("#basic-datatables").DataTable({});

      // عند النقر على زر التعديل، ملء بيانات النموذج
      $('.edit-user').on('click', function () {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var email = $(this).data('email');
        var password = $(this).data('password');
        var balance = $(this).data('balance');

        $('#edit_user_id').val(id);
        $('#edit_name').val(name);
        $('#edit_email').val(email);
        $('#edit_password').val(password);
        $('#edit_balance').val(balance);
      });
    });
  </script>
</body>

</html>

<?php
// إغلاق الاتصال بقاعدة البيانات
$conn->close();
?>