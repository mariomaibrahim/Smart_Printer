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
function getInitials($fullName)
{
    if (empty($fullName))
        return 'U'; // افتراضي للمستخدم غير المعروف

    $names = explode(' ', trim($fullName));
    $initials = '';

    // الحرف الأول من الاسم الأول
    if (!empty($names[0])) {
        $initials .= mb_substr($names[0], 0, 1, 'UTF-8');
    }

    // الحرف الأول من الاسم الأخير (إذا وُجد)
    if (count($names) > 1 && !empty($names[count($names) - 1])) {
        $initials .= mb_substr($names[count($names) - 1], 0, 1, 'UTF-8');
    }

    return mb_strtoupper($initials, 'UTF-8');
}

// دالة للحصول على لون الـ Avatar بناءً على الاسم
function getAvatarColor($name)
{
    $colors = [
        'avatar-blue',
        'avatar-green',
        'avatar-orange',
        'avatar-purple',
        'avatar-red'
    ];

    // Hash function بسيط للحصول على لون ثابت لنفس الاسم
    $hash = 0;
    for ($i = 0; $i < mb_strlen($name, 'UTF-8'); $i++) {
        $char = mb_substr($name, $i, 1, 'UTF-8');
        $hash = ord($char) + (($hash << 5) - $hash);
    }

    return $colors[abs($hash) % count($colors)];
}

// الحصول على بيانات المستخدم
$userName = $_SESSION['user_name'] ?? 'مستخدم';
$userEmail = $_SESSION['user_email'] ?? 'user@example.com';
$userInitials = getInitials($userName);
$avatarColor = getAvatarColor($userName);


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


// معالجة طلبات AJAX لتحديث حالة قراءة الإشعارات
if (isset($_GET['action']) && $_GET['action'] === 'mark_notifications_read') {
    // إضافة عمود read_status إذا لم يكن موجوداً
    $alter_sql = "ALTER TABLE printer_notifications ADD COLUMN IF NOT EXISTS read_status BOOLEAN DEFAULT FALSE";
    $conn->query($alter_sql);

    // تحديث جميع الإشعارات لتصبح مقروءة
    $update_sql = "UPDATE printer_notifications SET read_status = TRUE WHERE read_status = FALSE OR read_status IS NULL";

    if ($conn->query($update_sql) === TRUE) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Failed to update notifications']);
    }
    $conn->close();
    exit();
}

// معالجة طلب تحديد إشعار واحد كمقروء
if (isset($_GET['action']) && $_GET['action'] === 'mark_single_read' && isset($_GET['notification_id'])) {
    $notification_id = intval($_GET['notification_id']);

    $update_sql = "UPDATE printer_notifications SET read_status = TRUE WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $notification_id);

    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Failed to update notification']);
    }

    $stmt->close();
    $conn->close();
    exit();
}

// معالجة طلب POST لتحديث الإشعارات
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mark_notifications_read'])) {
    // إضافة عمود read_status إذا لم يكن موجوداً
    $alter_sql = "ALTER TABLE printer_notifications ADD COLUMN IF NOT EXISTS read_status BOOLEAN DEFAULT FALSE";
    $conn->query($alter_sql);

    // تحديث جميع الإشعارات لتصبح مقروءة
    $update_sql = "UPDATE printer_notifications SET read_status = TRUE WHERE read_status = FALSE OR read_status IS NULL";

    if ($conn->query($update_sql) === TRUE) {
        $notificationUpdateMessage = "تم تحديد جميع الإشعارات كمقروءة!";
    } else {
        $notificationUpdateMessage = "خطأ في تحديث الإشعارات!";
    }
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
      level ENUM('info', 'warning', 'error') NOT NULL DEFAULT 'info',
      read_status BOOLEAN DEFAULT FALSE
  )";

    $conn->query($sql);
}

// دالة لإضافة إشعارات تجريبية للاختبار
function addTestNotifications($conn)
{
    $notifications = [
        ['message' => 'Low ink level detected', 'level' => 'warning'],
        ['message' => 'Print job completed successfully', 'level' => 'info'],
        ['message' => 'Printer connection restored', 'level' => 'info'],
        ['message' => 'Paper jam detected', 'level' => 'error'],
        ['message' => 'Maintenance required', 'level' => 'warning']
    ];

    foreach ($notifications as $notif) {
        $sql = "INSERT INTO printer_notifications (message, level, read_status) VALUES (?, ?, FALSE)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $notif['message'], $notif['level']);
        $stmt->execute();
        $stmt->close();
    }
}

// إضافة إشعارات تجريبية إذا كانت الطاولة فارغة (للاختبار فقط)
function initializeNotifications($conn)
{
    $check_sql = "SELECT COUNT(*) as count FROM printer_notifications";
    $result = $conn->query($check_sql);
    $row = $result->fetch_assoc();

    if ($row['count'] == 0) {
        addTestNotifications($conn);
    }
}

// Ensure necessary tables exist
createPriceSettingsTable($conn);
createPrinterStatusTable($conn);
createPrinterNotificationsTable($conn);

// Initialize notifications if table is empty
initializeNotifications($conn);

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

// دالة محدثة للحصول على إشعارات الطابعة مع معرف الإشعار
function getPrinterNotifications($conn, $limit = 5, $unread_only = true)
{
    $notifications = array();

    // إضافة عمود read_status إذا لم يكن موجوداً
    $alter_sql = "ALTER TABLE printer_notifications ADD COLUMN IF NOT EXISTS read_status BOOLEAN DEFAULT FALSE";
    $conn->query($alter_sql);

    if ($unread_only) {
        $sql = "SELECT id, date_time, message, level, read_status FROM printer_notifications 
            WHERE read_status = FALSE OR read_status IS NULL 
            ORDER BY date_time DESC LIMIT $limit";
    } else {
        $sql = "SELECT id, date_time, message, level, read_status FROM printer_notifications 
            ORDER BY date_time DESC LIMIT $limit";
    }

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
    }

    return $notifications;
}

// دالة للحصول على عدد الإشعارات غير المقروءة
function getUnreadNotificationsCount($conn)
{
    // إضافة عمود read_status إذا لم يكن موجوداً
    $alter_sql = "ALTER TABLE printer_notifications ADD COLUMN IF NOT EXISTS read_status BOOLEAN DEFAULT FALSE";
    $conn->query($alter_sql);

    $sql = "SELECT COUNT(*) as unread_count FROM printer_notifications 
          WHERE read_status = FALSE OR read_status IS NULL";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['unread_count'];
    }

    return 0;
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
    $bw_single = floatval($_POST['bw_single']);
    $color_single = floatval($_POST['color_single']);
    $bw_double = floatval($_POST['bw_double']);
    $color_double = floatval($_POST['color_double']);

    // التحقق من صحة البيانات
    if ($bw_single >= 0 && $color_single >= 0 && $bw_double >= 0 && $color_double >= 0) {
        $sql = "UPDATE price_settings SET 
              bw_single = ?, 
              color_single = ?, 
              bw_double = ?, 
              color_double = ?
              WHERE id = 1";

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            // إصلاح عدد المعاملات - 4 معاملات من نوع double
            $stmt->bind_param("dddd", $bw_single, $color_single, $bw_double, $color_double);

            if ($stmt->execute()) {
                $priceUpdateMessage = "تم تحديث أسعار الطباعة بنجاح!";
                // إعادة تحميل الأسعار المحدثة
                $prices = getPriceSettings($conn);
            } else {
                $priceUpdateMessage = "خطأ في تحديث الأسعار: " . $stmt->error;
            }

            $stmt->close();
        } else {
            $priceUpdateMessage = "خطأ في إعداد الاستعلام: " . $conn->error;
        }
    } else {
        $priceUpdateMessage = "خطأ: جميع الأسعار يجب أن تكون أرقام موجبة!";
    }
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
$unreadNotificationsCount = getUnreadNotificationsCount($conn);

// Add test notification for testing
if (!empty($_GET['test_notification'])) {
    $message = "This is a test printer notification.";
    $level = "info";
    $sql = "INSERT INTO printer_notifications (message, level, read_status) VALUES (?, ?, FALSE)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $message, $level);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

?>
<script>
    document.querySelector('form[name="priceForm"]')?.addEventListener('submit', function (e) {
        const inputs = this.querySelectorAll('input[type="number"]');
        let isValid = true;

        inputs.forEach(input => {
            const value = parseFloat(input.value);
            if (isNaN(value) || value < 0) {
                isValid = false;
                input.classList.add('is-invalid');
            } else {
                input.classList.remove('is-invalid');
            }
        });

        if (!isValid) {
            e.preventDefault();
            alert('يرجى التأكد من أن جميع الأسعار أرقام صحيحة وموجبة');
        }
    });

    // إضافة تأثيرات بصرية عند تغيير القيم
    document.querySelectorAll('input[type="number"]').forEach(input => {
        input.addEventListener('input', function () {
            this.classList.add('border-warning');
            setTimeout(() => {
                this.classList.remove('border-warning');
            }, 1000);
        });
    });
    function markAllNotificationsAsRead() {
        fetch('?action=mark_notifications_read')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // إعادة تحميل الصفحة أو تحديث قسم الإشعارات
                    location.reload();
                } else {
                    alert('خطأ في تحديث الإشعارات: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ في الاتصال');
            });
    }

    // تحديد إشعار واحد كمقروء
    function markSingleNotificationAsRead(notificationId) {
        fetch(`?action=mark_single_read&notification_id=${notificationId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // إخفاء الإشعار أو تحديث الواجهة
                    const notificationElement = document.getElementById(`notification_${notificationId}`);
                    if (notificationElement) {
                        notificationElement.style.display = 'none';
                    }

                    // تحديث عداد الإشعارات غير المقروءة
                    updateNotificationCount();
                } else {
                    alert('خطأ في تحديث الإشعار: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }

    // تحديث عداد الإشعارات
    function updateNotificationCount() {
        const unreadCount = document.querySelectorAll('.notification-item:not([style*="display: none"])').length;
        const counterElement = document.getElementById('notifications-counter');
        if (counterElement) {
            counterElement.textContent = unreadCount;
            if (unreadCount === 0) {
                counterElement.style.display = 'none';
            }
        }
    }

    // إضافة أحداث النقر للإشعارات عند تحميل الصفحة
    document.addEventListener('DOMContentLoaded', function () {
        // إضافة حدث للنقر على أي إشعار لتحديده كمقروء
        const notifications = document.querySelectorAll('.notification-item');
        notifications.forEach(function (notification) {
            notification.addEventListener('click', function () {
                const notificationId = this.getAttribute('data-notification-id');
                if (notificationId) {
                    markSingleNotificationAsRead(notificationId);
                }
            });
        });

        // إضافة حدث لزر "تحديد الكل كمقروء"
        const markAllButton = document.getElementById('mark-all-read-btn');
        if (markAllButton) {
            markAllButton.addEventListener('click', function (e) {
                e.preventDefault();
                markAllNotificationsAsRead();
            });
        }
    });
</script>
<style>
    .avatar-initials {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 16px;
        text-transform: uppercase;
        border: 2px solid #fff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    .avatar-initials:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .avatar-blue {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .avatar-green {
        background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
    }

    .avatar-orange {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .avatar-purple {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .avatar-red {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    }
</style>