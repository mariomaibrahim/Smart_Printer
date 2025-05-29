<?php
// بدء الجلسة إذا لم تكن قد بدأت بالفعل
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// الاتصال بقاعدة البيانات
try {
    $dbname = new PDO('mysql:host=localhost;dbname=aitp', 'root', '');
    $dbname->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("فشل الاتصال: " . $e->getMessage());
}

// التحقق من تسجيل دخول المستخدم
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
if (!$user_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'المستخدم غير مسجل الدخول']);
    exit;
}

// دالة للتحقق من صحة نطاق الصفحات وحساب عدد الصفحات الفعلي
function validateAndCountPageRange($pageOption, $pageRange, $totalPages) {
    if ($pageOption === 'all') {
        return ['valid' => true, 'count' => $totalPages, 'message' => '', 'pages' => range(1, $totalPages)];
    }
    
    if ($pageOption === 'custom' || $pageOption === 'range') {
        if (empty($pageRange)) {
            return ['valid' => false, 'count' => 0, 'message' => 'يجب تحديد نطاق الصفحات', 'pages' => []];
        }
        
        // تنظيف النطاق من المسافات والرموز غير المرغوب فيها
        $pageRange = preg_replace('/\s+/', '', $pageRange);
        $pageRange = trim($pageRange, ',');
        
        if (empty($pageRange)) {
            return ['valid' => false, 'count' => 0, 'message' => 'نطاق الصفحات فارغ', 'pages' => []];
        }
        
        // دعم الفواصل والشرطات
        $ranges = explode(',', $pageRange);
        $pageNumbers = [];
        
        foreach ($ranges as $range) {
            $range = trim($range);
            if (empty($range)) continue;
            
            if (strpos($range, '-') !== false) {
                // نطاق من صفحة إلى أخرى (مثل 1-5)
                $parts = explode('-', $range);
                if (count($parts) !== 2) {
                    return ['valid' => false, 'count' => 0, 'message' => 'نطاق الصفحات غير صحيح: ' . $range, 'pages' => []];
                }
                
                $start = intval(trim($parts[0]));
                $end = intval(trim($parts[1]));
                
                if ($start <= 0 || $end <= 0) {
                    return ['valid' => false, 'count' => 0, 'message' => 'أرقام الصفحات يجب أن تكون أكبر من صفر', 'pages' => []];
                }
                
                if ($start > $totalPages || $end > $totalPages) {
                    return ['valid' => false, 'count' => 0, 'message' => "رقم الصفحة يتجاوز عدد صفحات الملف ($totalPages)", 'pages' => []];
                }
                
                if ($start > $end) {
                    return ['valid' => false, 'count' => 0, 'message' => 'رقم الصفحة الأولى يجب أن يكون أقل من أو يساوي رقم الصفحة الأخيرة', 'pages' => []];
                }
                
                for ($i = $start; $i <= $end; $i++) {
                    $pageNumbers[] = $i;
                }
            } else {
                // صفحة واحدة
                $pageNum = intval($range);
                if ($pageNum <= 0) {
                    return ['valid' => false, 'count' => 0, 'message' => 'رقم الصفحة يجب أن يكون أكبر من صفر', 'pages' => []];
                }
                
                if ($pageNum > $totalPages) {
                    return ['valid' => false, 'count' => 0, 'message' => "رقم الصفحة $pageNum يتجاوز عدد صفحات الملف ($totalPages)", 'pages' => []];
                }
                
                $pageNumbers[] = $pageNum;
            }
        }
        
        if (empty($pageNumbers)) {
            return ['valid' => false, 'count' => 0, 'message' => 'لم يتم تحديد صفحات صالحة', 'pages' => []];
        }
        
        // إزالة التكرارات وترتيب الصفحات
        $pageNumbers = array_unique($pageNumbers);
        sort($pageNumbers);
        $actualPageCount = count($pageNumbers);
        
        return ['valid' => true, 'count' => $actualPageCount, 'message' => '', 'pages' => $pageNumbers];
    }
    
    return ['valid' => false, 'count' => 0, 'message' => 'خيار الصفحات غير صحيح', 'pages' => []];
}

// دالة لحذف الملفات المكتملة - محسنة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_completed_jobs') {
    header('Content-Type: application/json');
    try {
        // البحث عن المهام المكتملة
        $stmt = $dbname->prepare("SELECT id, file_path, file_name, user_id FROM print_jobs WHERE status = 'done'");
        $stmt->execute();
        $completed_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $deleted_count = 0;
        $user_notifications = [];
        
        foreach ($completed_jobs as $job) {
            // حذف الملف من النظام إذا كان موجوداً
            if (!empty($job['file_path']) && file_exists($job['file_path'])) {
                if (unlink($job['file_path'])) {
                    error_log("تم حذف الملف: " . $job['file_path']);
                } else {
                    error_log("فشل في حذف الملف: " . $job['file_path']);
                }
            } else {
                error_log("الملف غير موجود أو المسار فارغ: " . ($job['file_path'] ?? 'مسار فارغ'));
            }
            
            // حذف السجل من قاعدة البيانات
            $delete_stmt = $dbname->prepare("DELETE FROM print_jobs WHERE id = :job_id");
            $delete_stmt->bindParam(':job_id', $job['id']);
            
            if ($delete_stmt->execute()) {
                $deleted_count++;
                error_log("تم حذف سجل المهمة: " . $job['id']);
                
                // إضافة إشعار للمستخدم
                if (!isset($user_notifications[$job['user_id']])) {
                    $user_notifications[$job['user_id']] = [];
                }
                $user_notifications[$job['user_id']][] = $job['file_name'];
            } else {
                error_log("فشل في حذف سجل المهمة: " . $job['id']);
            }
        }
        
        // إرسال إشعارات للمستخدمين
        foreach ($user_notifications as $user_id => $file_names) {
            $job_count = count($file_names);
            $notification_stmt = $dbname->prepare("INSERT INTO notifications (user_id, message, type, seen, created_at) VALUES (:user_id, :message, 'success', 0, NOW())");
            
            if ($job_count == 1) {
                $message = "تم الانتهاء من طباعة الملف: " . $file_names[0] . " وتم حذفه من النظام.";
            } else {
                $message = "تم الانتهاء من طباعة {$job_count} ملفات وتم حذفها من النظام.";
            }
            
            $notification_stmt->bindParam(':user_id', $user_id);
            $notification_stmt->bindParam(':message', $message);
            
            if ($notification_stmt->execute()) {
                error_log("تم إرسال إشعار للمستخدم: " . $user_id);
            } else {
                error_log("فشل في إرسال إشعار للمستخدم: " . $user_id);
            }
        }
        
        echo json_encode([
            'success' => true, 
            'deleted_count' => $deleted_count, 
            'message' => "تم حذف {$deleted_count} مهمة مكتملة وإرسال الإشعارات"
        ]);
    } catch (Exception $e) {
        error_log("خطأ في حذف المهام المكتملة: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()]);
    }
    exit;
}

// دالة لتحديث حالة المهمة - محسنة مع إرسال الإشعارات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_job_status') {
    header('Content-Type: application/json');
    try {
        $job_id = $_POST['job_id'] ?? 0;
        $new_status = $_POST['status'] ?? '';
        
        // التحقق من صحة الحالة
        $valid_statuses = ['pending', 'in_progress', 'done', 'canceled'];
        if (!in_array($new_status, $valid_statuses)) {
            echo json_encode(['success' => false, 'message' => 'حالة غير صحيحة']);
            exit;
        }
        
        // الحصول على تفاصيل المهمة
        $stmt = $dbname->prepare("SELECT * FROM print_jobs WHERE id = :job_id");
        $stmt->bindParam(':job_id', $job_id);
        $stmt->execute();
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$job) {
            echo json_encode(['success' => false, 'message' => 'المهمة غير موجودة']);
            exit;
        }
        
        // التحقق من وجود تغيير في الحالة لتجنب الإشعارات المكررة
        if ($job['status'] === $new_status) {
            echo json_encode(['success' => true, 'message' => 'الحالة محدثة بالفعل']);
            exit;
        }
        
        // بدء معاملة قاعدة البيانات
        $dbname->beginTransaction();
        
        try {
            // تحديث حالة المهمة
            $update_stmt = $dbname->prepare("UPDATE print_jobs SET status = :status, updated_at = NOW() WHERE id = :job_id");
            $update_stmt->bindParam(':status', $new_status);  
            $update_stmt->bindParam(':job_id', $job_id);
            $update_stmt->execute();
            
            // إرسال إشعار للمستخدم بناءً على الحالة الجديدة
            $notification_message = '';
            $notification_type = 'info';
            
            switch ($new_status) {
                case 'in_progress':
                    $notification_message = "🔄 بدأت طباعة ملف: " . $job['file_name'] . ". المهمة قيد التنفيذ الآن.";
                    $notification_type = 'info';
                    break;
                case 'done':
                    $notification_message = "✅ تم الانتهاء من طباعة ملف: " . $job['file_name'] . ". المهمة جاهزة للاستلام!";
                    $notification_type = 'success';
                    break;
                case 'canceled':
                    $notification_message = "❌ تم إلغاء طباعة ملف: " . $job['file_name'] . ".";
                    $notification_type = 'error';
                    break;
                case 'pending':
                    $notification_message = "⏳ تم إعادة تعيين المهمة: " . $job['file_name'] . " إلى قائمة الانتظار.";
                    $notification_type = 'warning';
                    break;
            }
            
            // إدراج الإشعار في قاعدة البيانات مع تعيين seen = 0 بوضوح
            if (!empty($notification_message)) {
                $notification_stmt = $dbname->prepare("INSERT INTO notifications (user_id, message, type, seen, created_at) VALUES (:user_id, :message, :type, 0, NOW())");
                $notification_stmt->bindParam(':user_id', $job['user_id']);
                $notification_stmt->bindParam(':message', $notification_message);
                $notification_stmt->bindParam(':type', $notification_type);
                
                if ($notification_stmt->execute()) {
                    error_log("تم إرسال إشعار تحديث الحالة للمستخدم: " . $job['user_id'] . " للملف: " . $job['file_name'] . " بالحالة: " . $new_status);
                } else {
                    error_log("فشل في إرسال الإشعار");
                }
            }
            
            // تأكيد المعاملة
            $dbname->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'تم تحديث حالة المهمة بنجاح وإرسال الإشعار',
                'notification_sent' => !empty($notification_message),
                'old_status' => $job['status'],
                'new_status' => $new_status
            ]);
            
        } catch (Exception $e) {
            // إلغاء المعاملة في حالة حدوث خطأ
            $dbname->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log("خطأ في تحديث حالة المهمة: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()]);
    }
    exit;
}

// دالة منفصلة لحذف الملفات المكتملة بعد فترة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cleanup_old_done_jobs') {
    header('Content-Type: application/json');
    try {
        // حذف المهام المكتملة التي مضى عليها أكثر من ساعة (يمكن تعديل المدة)
        $cleanup_time = isset($_POST['cleanup_hours']) ? intval($_POST['cleanup_hours']) : 1;
        
        $stmt = $dbname->prepare("SELECT id, file_path, file_name, user_id FROM print_jobs WHERE status = 'done' AND updated_at < DATE_SUB(NOW(), INTERVAL :hours HOUR)");
        $stmt->bindParam(':hours', $cleanup_time);
        $stmt->execute();
        $old_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $cleaned_count = 0;
        
        foreach ($old_jobs as $job) {
            // حذف الملف من النظام
            if (!empty($job['file_path']) && file_exists($job['file_path'])) {
                unlink($job['file_path']);
            }
            
            // حذف السجل من قاعدة البيانات
            $delete_stmt = $dbname->prepare("DELETE FROM print_jobs WHERE id = :job_id");
            $delete_stmt->bindParam(':job_id', $job['id']);
            $delete_stmt->execute();
            
            $cleaned_count++;
        }
        
        echo json_encode([
            'success' => true, 
            'cleaned_count' => $cleaned_count, 
            'message' => "تم تنظيف {$cleaned_count} مهمة قديمة"
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_pending_jobs') {
    header('Content-Type: application/json');
    try {
        $order_by = isset($_GET['order_by']) ? $_GET['order_by'] : 'id';
        $order_dir = isset($_GET['order_dir']) && in_array(strtoupper($_GET['order_dir']), ['ASC', 'DESC']) ? strtoupper($_GET['order_dir']) : 'ASC';
        $stmt = $dbname->prepare("SELECT * FROM print_jobs WHERE status = 'pending' ORDER BY $order_by $order_dir");
        $stmt->execute();
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'jobs' => $jobs]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// دالة للحصول على الإشعارات للمستخدم الحالي - محسنة بشكل كامل
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_notifications') {
    header('Content-Type: application/json');
    try {
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
        $show_all = isset($_GET['show_all']) && $_GET['show_all'] === 'true';
        
        // إنشاء الاستعلام بناءً على طلب عرض جميع الإشعارات أو الغير مقروءة فقط
        if ($show_all) {
            $sql = "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        } else {
            $sql = "SELECT * FROM notifications WHERE user_id = :user_id AND (seen = 0 OR seen IS NULL) ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        }
        
        $stmt = $dbname->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // عدد الإشعارات غير المقروءة
        $unread_stmt = $dbname->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND (seen = 0 OR seen IS NULL)");
        $unread_stmt->bindParam(':user_id', $user_id);
        $unread_stmt->execute();
        $unread_count = $unread_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // عدد جميع الإشعارات
        $total_stmt = $dbname->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id");
        $total_stmt->bindParam(':user_id', $user_id);
        $total_stmt->execute();
        $total_count = $total_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo json_encode([
            'success' => true, 
            'notifications' => $notifications,
            'unread_count' => intval($unread_count),
            'total_count' => intval($total_count)
        ]);
    } catch (Exception $e) {
        error_log("خطأ في جلب الإشعارات: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// دالة منفصلة للحصول على عدد الإشعارات غير المقروءة فقط
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_unread_notifications_count') {
    header('Content-Type: application/json');
    try {
        $stmt = $dbname->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND (seen = 0 OR seen IS NULL)");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $unread_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo json_encode([
            'success' => true, 
            'unread_count' => intval($unread_count)
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// دالة لتحديد الإشعارات كمقروءة - محسنة بشكل كامل
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_notifications_read') {
    header('Content-Type: application/json');
    try {
        // استقبال بيانات JSON إذا كانت موجودة
        $input = file_get_contents('php://input');
        $json_data = json_decode($input, true);
        
        $notification_ids = [];
        
        // التحقق من مصدر البيانات
        if (isset($_POST['notification_ids'])) {
            $notification_ids = is_string($_POST['notification_ids']) ? 
                json_decode($_POST['notification_ids'], true) : 
                $_POST['notification_ids'];
        } elseif (isset($json_data['notification_ids'])) {
            $notification_ids = $json_data['notification_ids'];
        }
        
        if (empty($notification_ids)) {
            // تحديد جميع الإشعارات غير المقروءة كمقروءة للمستخدم الحالي
            $stmt = $dbname->prepare("UPDATE notifications SET seen = 1 WHERE user_id = :user_id AND (seen = 0 OR seen IS NULL)");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $affected_rows = $stmt->rowCount();
            
            error_log("تم تحديد جميع الإشعارات كمقروءة للمستخدم: $user_id، عدد الإشعارات المحدثة: $affected_rows");
        } else {
            // تحديد إشعارات محددة كمقروءة للمستخدم الحالي فقط
            $placeholders = str_repeat('?,', count($notification_ids) - 1) . '?';
            $stmt = $dbname->prepare("UPDATE notifications SET seen = 1 WHERE user_id = ? AND id IN ($placeholders) AND (seen = 0 OR seen IS NULL)");
            $params = array_merge([$user_id], $notification_ids);
            $stmt->execute($params);
            $affected_rows = $stmt->rowCount();
            
            error_log("تم تحديد إشعارات محددة كمقروءة للمستخدم: $user_id، معرفات الإشعارات: " . implode(',', $notification_ids) . "، عدد الإشعارات المحدثة: $affected_rows");
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'تم تحديث الإشعارات بنجاح',
            'affected_rows' => $affected_rows
        ]);
    } catch (Exception $e) {
        error_log("خطأ في تحديث الإشعارات: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// دالة لحذف الإشعارات المقروءة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_read_notifications') {
    header('Content-Type: application/json');
    try {
        $stmt = $dbname->prepare("DELETE FROM notifications WHERE user_id = :user_id AND seen = 1");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $deleted_count = $stmt->rowCount();
        
        echo json_encode([
            'success' => true, 
            'message' => "تم حذف {$deleted_count} إشعار مقروء",
            'deleted_count' => $deleted_count
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// دالة لحذف إشعار واحد محدد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_notification') {
    header('Content-Type: application/json');
    try {
        $notification_id = $_POST['notification_id'] ?? 0;
        
        if ($notification_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'معرف الإشعار غير صحيح']);
            exit;
        }
        
        $stmt = $dbname->prepare("DELETE FROM notifications WHERE id = :notification_id AND user_id = :user_id");
        $stmt->bindParam(':notification_id', $notification_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'تم حذف الإشعار']);
        } else {
            echo json_encode(['success' => false, 'message' => 'الإشعار غير موجود أو لا يمكن حذفه']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

function getPriceSettings($dbname) {
    try {
        $stmt = $dbname->query("SELECT * FROM price_settings ORDER BY id DESC LIMIT 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // إذا لم تكن هناك إعدادات، قم بإنشاء إعدادات افتراضية
        if (!$settings) {
            $dbname->exec("INSERT INTO price_settings (bw_single, color_single, bw_double, color_double, student_discount, professor_discount, staff_discount, bulk_discount) 
                VALUES (0.50, 1.50, 0.80, 2.50, 10, 15, 5, 20)");
            
            $stmt = $dbname->query("SELECT * FROM price_settings ORDER BY id DESC LIMIT 1");
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return $settings;
    } catch (PDOException $e) {
        // إذا كان الجدول غير موجود، قم بإنشائه
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
        
        $dbname->exec($sql);
        $dbname->exec("INSERT INTO price_settings (bw_single, color_single, bw_double, color_double, student_discount, professor_discount, staff_discount, bulk_discount) 
            VALUES (0.50, 1.50, 0.80, 2.50, 10, 15, 5, 20)");
        
        $stmt = $dbname->query("SELECT * FROM price_settings ORDER BY id DESC LIMIT 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $settings;
    }
}

// دالة لتنظيف اسم الملف
function sanitizeFileName($fileName) {
    // إزالة الأحرف التي قد تكون خطرة
    $sanitized = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $fileName);
    return $sanitized;
}

// دالة لتوحيد قيم وضع اللون
function normalizeColorMode($colorMode) {
    // تحويل جميع القيم إلى نظام موحد
    $colorMode = strtolower(trim($colorMode));
    
    switch ($colorMode) {
        case 'bw':
        case 'black_white':
        case 'blackwhite':
        case 'grayscale':
            return 'black_white';
        case 'color':
        case 'colored':
            return 'color';
        default:
            return 'black_white'; // القيمة الافتراضية
    }
}

// دالة لحساب تكلفة الطباعة - محسنة
function calculateCost($numPages, $numCopies, $colorMode, $printSides, $dbname, $user_id, $pageOption = 'all', $pageRange = '', $totalPages = null)
{
    // الحصول على إعدادات الأسعار من قاعدة البيانات
    $settings = getPriceSettings($dbname);
    
    // توحيد قيمة وضع اللون
    $normalizedColorMode = normalizeColorMode($colorMode);
    
    // تحويل قيم print_sides لتتناسب مع قاعدة البيانات
    $dbPrintSides = ($printSides === 'two-sided') ? 'double' : 'single';
    
    // حساب عدد الصفحات الفعلي المراد طباعتها
    $actualPageCount = $numPages;
    
    if ($pageOption === 'range' && !empty($pageRange) && $totalPages !== null) {
        // التحقق من صحة النطاق وحساب عدد الصفحات الفعلي
        $validation = validateAndCountPageRange($pageOption, $pageRange, $totalPages);
        
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['message']];
        }
        
        $actualPageCount = $validation['count'];
    }
    
    // تحديد سعر الصفحة بناءً على إعدادات اللون والطباعة
    if ($normalizedColorMode == 'color') {
        if ($dbPrintSides == 'double') {
            $pagePrice = $settings['color_double'];
        } else {
            $pagePrice = $settings['color_single'];
        }
    } else {
        if ($dbPrintSides == 'double') {
            $pagePrice = $settings['bw_double'];
        } else {
            $pagePrice = $settings['bw_single'];
        }
    }
    
    // حساب التكلفة الإجمالية بناء على عدد الصفحات الفعلي
    $totalCost = $actualPageCount * $numCopies * $pagePrice;
    
    return [
        'success' => true, 
        'cost' => $totalCost, 
        'actual_page_count' => $actualPageCount,
        'page_price' => $pagePrice
    ];
}

// معالجة طلب حساب التكلفة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'calculate_cost') {
    $colorMode = $_POST['color'] ?? 'black_white';
    $printSides = $_POST['sides'] ?? 'one-sided';
    $numPages = intval($_POST['page_count'] ?? 1);
    $numCopies = intval($_POST['copies'] ?? 1);
    $pageOption = $_POST['pages'] ?? 'all';
    $pageRange = $_POST['page_range'] ?? '';
    $totalPages = isset($_POST['total_pages']) ? intval($_POST['total_pages']) : $numPages;
    
    $result = calculateCost($numPages, $numCopies, $colorMode, $printSides, $dbname, $user_id, $pageOption, $pageRange, $totalPages);
    
    header('Content-Type: application/json');
    
    if ($result['success']) {
        echo json_encode([
            'success' => true, 
            'cost' => $result['cost'],
            'actual_page_count' => $result['actual_page_count'],
            'page_price' => $result['page_price'],
            'message' => "سيتم طباعة {$result['actual_page_count']} صفحة"
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => $result['error']
        ]);
    }
    exit;
}

// معالجة طلب معالجة الطباعة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_print') {
    $tempFilePath = $_POST['temp_file_path'] ?? '';
    $originalFileName = $_POST['original_file_name'] ?? '';
    $numPages = intval($_POST['page_count'] ?? 1);
    $numCopies = intval($_POST['copies'] ?? 1);
    $colorMode = $_POST['color'] ?? 'black_white';
    $printSides = $_POST['sides'] ?? 'one-sided';
    $orientation = $_POST['layout'] ?? 'portrait';
    $pageOption = $_POST['pages'] ?? 'all';
    $pageRange = $_POST['page_range'] ?? '';
    $totalPages = isset($_POST['total_pages']) ? intval($_POST['total_pages']) : $numPages;
    
    // توحيد قيمة وضع اللون
    $normalizedColorMode = normalizeColorMode($colorMode);
    
    // تحويل قيم print_sides لتتناسب مع قاعدة البيانات
    $dbPrintSides = ($printSides === 'two-sided') ? 'double' : 'single';
    
    // التحقق من وجود الملف
    if (!file_exists($tempFilePath)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'الملف غير موجود']);
        exit;
    }
    
    // حساب التكلفة مع التحقق من نطاق الصفحات
    $costResult = calculateCost($numPages, $numCopies, $normalizedColorMode, $printSides, $dbname, $user_id, $pageOption, $pageRange, $totalPages);
    
    if (!$costResult['success']) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $costResult['error']]);
        exit;
    }
    
    $cost = $costResult['cost'];
    $actualPageCount = $costResult['actual_page_count'];
    
    // التحقق من رصيد المستخدم
    $stmt = $dbname->prepare("SELECT balance FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'لم يتم العثور على المستخدم']);
        exit;
    }
    
    $balance = $user['balance'];
    
    if ($balance < $cost) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'رصيد غير كافٍ. الرصيد الحالي: ' . $balance . ' AITP، التكلفة المطلوبة: ' . $cost . ' AITP']);
        exit;
    }
    
    // إنشاء مجلد للملفات المرفوعة إذا لم يكن موجوداً
    $upload_dir = '../uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // إنشاء اسم فريد للملف لتجنب تكرار الأسماء
    $unique_file_name = time() . '_' . sanitizeFileName($originalFileName);
    $target_path = $upload_dir . $unique_file_name;
    
    // نقل الملف من المجلد المؤقت إلى المجلد النهائي
    if (!copy($tempFilePath, $target_path)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'فشل في نقل الملف من المجلد المؤقت']);
        exit;
    }
    
    // تحديد نطاق الصفحات لحفظه في قاعدة البيانات
    $finalPageRange = ($pageOption === 'all') ? 'all' : $pageRange;
    
    try {
        // بدء معاملة قاعدة البيانات
        $dbname->beginTransaction();
        
        // إنشاء مهمة طباعة بحالة pending مع حفظ عدد الصفحات الفعلي
        $stmt = $dbname->prepare("INSERT INTO print_jobs (user_id, file_name, file_path, num_pages, num_copies, 
                                color_mode, print_sides, orientation, page_range, cost, status, created_at) 
                                VALUES (:user_id, :file_name, :file_path, :num_pages, :num_copies, 
                                :color_mode, :print_sides, :orientation, :page_range, :cost, 'pending', NOW())");

        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':file_name', $originalFileName);
        $stmt->bindParam(':file_path', $target_path);
        $stmt->bindParam(':num_pages', $actualPageCount); // حفظ عدد الصفحات الفعلي
        $stmt->bindParam(':num_copies', $numCopies);
        $stmt->bindParam(':color_mode', $normalizedColorMode);
        $stmt->bindParam(':print_sides', $dbPrintSides);
        $stmt->bindParam(':orientation', $orientation);
        $stmt->bindParam(':page_range', $finalPageRange);
        $stmt->bindParam(':cost', $cost);
        $stmt->execute();
        
        $job_id = $dbname->lastInsertId();
        
        // خصم التكلفة من رصيد المستخدم
        $new_balance = $balance - $cost;
        $stmt = $dbname->prepare("UPDATE users SET balance = :new_balance WHERE id = :user_id");
        $stmt->bindParam(':new_balance', $new_balance);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        // تسجيل المعاملة مع تفاصيل أكثر دقة
        $stmt = $dbname->prepare("INSERT INTO transactions (user_id, amount, type, description, created_at) 
                                VALUES (:user_id, :amount, 'debit', :description, NOW())");
        
        $pageDescription = ($pageOption === 'all') ? "جميع الصفحات ({$actualPageCount})" : "صفحات محددة ({$actualPageCount})";
        $description = "طباعة {$pageDescription}، {$numCopies} نسخة، " . ($normalizedColorMode == 'color' ? 'ملون' : 'أبيض وأسود');
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':amount', $cost);
        $stmt->bindParam(':description', $description);
        $stmt->execute();
        
        // تأكيد المعاملة
        $dbname->commit();
        
        // حذف الملف المؤقت
        @unlink($tempFilePath);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => "تمت معالجة مهمة الطباعة بنجاح. سيتم طباعة {$actualPageCount} صفحة",
            'job_id' => $job_id,
            'cost' => $cost,
            'actual_page_count' => $actualPageCount,
            'remaining_balance' => $new_balance
        ]);
        exit;
        
    } catch (Exception $e) {
        // إلغاء المعاملة في حالة حدوث خطأ
        $dbname->rollBack();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'فشل في معالجة مهمة الطباعة: ' . $e->getMessage()]);
        exit;
    }
}
// معالجة رفع الملف
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    
    // التحقق من الأخطاء
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'الملف المرفوع يتجاوز الحد الأقصى المسموح به في php.ini',
            UPLOAD_ERR_FORM_SIZE => 'الملف المرفوع يتجاوز الحد الأقصى المسموح به في نموذج HTML',
            UPLOAD_ERR_PARTIAL => 'تم رفع الملف بشكل جزئي فقط',
            UPLOAD_ERR_NO_FILE => 'لم يتم رفع أي ملف',
            UPLOAD_ERR_NO_TMP_DIR => 'مجلد مؤقت مفقود',
            UPLOAD_ERR_CANT_WRITE => 'فشل في كتابة الملف على القرص',
            UPLOAD_ERR_EXTENSION => 'أوقف امتداد PHP تحميل الملف'
        ];
        
        $errorMessage = $errorMessages[$file['error']] ?? 'خطأ غير معروف في التحميل';
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $errorMessage]);
        exit;
    }
    
    // التحقق من حجم الملف (بحد أقصى 10 ميجابايت)
    $maxFileSize = 10 * 1024 * 1024; // 10 ميجابايت بالبايت
    if ($file['size'] > $maxFileSize) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'حجم الملف يتجاوز الحد الأقصى البالغ 10 ميجابايت']);
        exit;
    }
    
    // التحقق من نوع الملف
    $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 
                     'image/jpeg', 'image/jpg', 'image/png'];
    
   if (!in_array($file['type'], $allowedTypes)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'نوع ملف غير صالح. الأنواع المسموح بها: PDF، DOC، DOCX، JPG، PNG']);
        exit;
    }
    
    // إنشاء مجلد مؤقت إذا لم يكن موجودًا
    $tempDir = '../temp_uploads/';
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }
    
    // إنشاء اسم ملف فريد للتخزين المؤقت
    $tempFileName = 'temp_' . time() . '_' . sanitizeFileName($file['name']);
    $tempFilePath = $tempDir . $tempFileName;
    
    // نقل الملف المرفوع إلى الموقع المؤقت
    if (move_uploaded_file($file['tmp_name'], $tempFilePath)) {
        // تم رفع الملف بنجاح إلى موقع مؤقت
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'تم تحميل الملف إلى التخزين المؤقت',
            'file_path' => $tempFilePath,
            'file_name' => $tempFileName
        ]);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'فشل في نقل الملف المرفوع']);
        exit;
    }
}

// معالجة طلب JSON لإنشاء مهمة الطباعة
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
    
    if (strpos($contentType, 'application/json') !== false) {
        // استقبال بيانات JSON
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (!empty($data) && !isset($data['action'])) {
            // التحقق من وجود الملف المرفوع مسبقًا
            if (isset($data['file_path']) && file_exists($data['file_path'])) {
                // استخراج بيانات الطباعة من JSON
                $file_name = $data['file_name'];
                $file_path = $data['file_path'];
                $num_pages = $data['num_pages'];
                $num_copies = $data['num_copies'];
                $color_mode = $data['color_mode'];
                $print_sides = $data['print_sides'];
                $orientation = $data['orientation'];
                $page_range = isset($data['page_range']) ? $data['page_range'] : 'all';
                
                // تحويل قيم print_sides لتتناسب مع قاعدة البيانات
                $dbPrintSides = ($print_sides === 'two-sided') ? 'double' : 'single';
                
                // حساب التكلفة باستخدام الأسعار من قاعدة البيانات
                $cost = calculateCost($num_pages, $num_copies, $color_mode, $print_sides, $dbname, $user_id);
                
                // إدخال مهمة الطباعة في قاعدة البيانات بحالة pending
                $stmt = $dbname->prepare("INSERT INTO print_jobs (user_id, file_name, file_path, num_pages, num_copies, 
                                        color_mode, print_sides, orientation, page_range, cost, status, created_at) 
                                        VALUES (:user_id, :file_name, :file_path, :num_pages, :num_copies, 
                                        :color_mode, :print_sides, :orientation, :page_range, :cost, 'pending', NOW())");
                
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':file_name', $file_name);
                $stmt->bindParam(':file_path', $file_path);
                $stmt->bindParam(':num_pages', $num_pages);
                $stmt->bindParam(':num_copies', $num_copies);
                $stmt->bindParam(':color_mode', $color_mode);
                $stmt->bindParam(':print_sides', $dbPrintSides); // استخدام القيمة المحولة
                $stmt->bindParam(':orientation', $orientation);
                $stmt->bindParam(':page_range', $page_range);
                $stmt->bindParam(':cost', $cost);
                
                if ($stmt->execute()) {
                    $job_id = $dbname->lastInsertId();
                    echo json_encode(['success' => true, 'message' => 'تم إنشاء مهمة الطباعة بنجاح', 'job_id' => $job_id, 'cost' => $cost]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'فشل في إنشاء مهمة الطباعة']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'الملف غير موجود']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'لم يتم استلام بيانات صالحة']);
        }
    }
}

// معالجة طلب تأكيد مهمة الطباعة
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // استقبال بيانات JSON
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (isset($data['job_id'])) {
        $job_id = $data['job_id'];
        
        // تحديث حالة مهمة الطباعة إلى "in_progress"
        $stmt = $dbname->prepare("UPDATE print_jobs SET status = 'in_progress', updated_at = NOW() WHERE id = :job_id AND user_id = :user_id");
        $stmt->bindParam(':job_id', $job_id);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'تم تأكيد مهمة الطباعة بنجاح']);
        } else {
            echo json_encode(['success' => false, 'message' => 'فشل في تأكيد مهمة الطباعة']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'معرف مهمة الطباعة غير موجود']);
    }
}

// جلب تفاصيل مهمة طباعة معينة (يمكن استخدامها للتحقق من الحالة أو عرض التفاصيل)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['job_id'])) {
    $job_id = $_GET['job_id'];
    
    $stmt = $dbname->prepare("SELECT * FROM print_jobs WHERE id = :job_id AND user_id = :user_id");
    $stmt->bindParam(':job_id', $job_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($job) {
        echo json_encode(['success' => true, 'job' => $job]);
    } else {
        echo json_encode(['success' => false, 'message' => 'مهمة الطباعة غير موجودة']);
    }
}

// جلب إعدادات الأسعار الحالية (مفيد للواجهة الأمامية)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['get_price_settings'])) {
    $settings = getPriceSettings($dbname);
    
    // الحصول على معلومات المستخدم
    $stmt = $dbname->prepare("SELECT name, email, balance FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'settings' => $settings, 
        'user_info' => $userInfo
    ]);
    exit;
}

// في أي حالة أخرى، إعادة خطأ
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'طلب غير صالح']);
exit;
?>