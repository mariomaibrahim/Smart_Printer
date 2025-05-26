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
            $notification_stmt = $dbname->prepare("INSERT INTO notifications (user_id, message, type, created_at) VALUES (:user_id, :message, 'success', NOW())");
            
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

// دالة لتحديث حالة المهمة - محسنة
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
        
        // بدء معاملة قاعدة البيانات
        $dbname->beginTransaction();
        
        try {
            // تحديث حالة المهمة
            $update_stmt = $dbname->prepare("UPDATE print_jobs SET status = :status, updated_at = NOW() WHERE id = :job_id");
            $update_stmt->bindParam(':status', $new_status);  
            $update_stmt->bindParam(':job_id', $job_id);
            $update_stmt->execute();
            
            // إذا كانت الحالة "done" - إرسال إشعار فقط (لا نحذف الملف هنا)
            if ($new_status === 'done') {
                // إرسال إشعار للمستخدم
                $notification_stmt = $dbname->prepare("INSERT INTO notifications (user_id, message, type, created_at) VALUES (:user_id, :message, 'success', NOW())");
                $message = "تم الانتهاء من طباعة ملف: " . $job['file_name'] . ". المهمة جاهزة للاستلام.";
                $notification_stmt->bindParam(':user_id', $job['user_id']);
                $notification_stmt->bindParam(':message', $message);
                $notification_stmt->execute();
                
                error_log("تم إرسال إشعار اكتمال الطباعة للمستخدم: " . $job['user_id'] . " للملف: " . $job['file_name']);
            }
            
            // تأكيد المعاملة
            $dbname->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'تم تحديث حالة المهمة بنجاح',
                'notification_sent' => ($new_status === 'done')
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

// دالة للحصول على الإشعارات للمستخدم الحالي
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_notifications') {
    header('Content-Type: application/json');
    try {
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
        
        $stmt = $dbname->prepare("SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // عدد الإشعارات غير المقروءة
        $unread_stmt = $dbname->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND seen = 0");
        $unread_stmt->bindParam(':user_id', $user_id);
        $unread_stmt->execute();
        $unread_count = $unread_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo json_encode([
            'success' => true, 
            'notifications' => $notifications,
            'unread_count' => $unread_count
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// دالة لتحديد الإشعارات كمقروءة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_notifications_read') {
    header('Content-Type: application/json');
    try {
        $notification_ids = isset($_POST['notification_ids']) ? $_POST['notification_ids'] : [];
        
        if (empty($notification_ids)) {
            // تحديد جميع الإشعارات كمقروءة
            $stmt = $dbname->prepare("UPDATE notifications SET seen = 1 WHERE user_id = :user_id AND seen = 0");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
        } else {
            // تحديد إشعارات محددة كمقروءة
            $placeholders = str_repeat('?,', count($notification_ids) - 1) . '?';
            $stmt = $dbname->prepare("UPDATE notifications SET seen = 1 WHERE user_id = ? AND id IN ($placeholders)");
            $params = array_merge([$user_id], $notification_ids);
            $stmt->execute($params);
        }
        
        echo json_encode(['success' => true, 'message' => 'تم تحديث الإشعارات']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// باقي الكود يبقى كما هو...
// دالة للحصول على إعدادات الأسعار
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

// دالة لحساب تكلفة الطباعة
function calculateCost($numPages, $numCopies, $colorMode, $printSides, $dbname, $user_id)
{
    // الحصول على إعدادات الأسعار من قاعدة البيانات
    $settings = getPriceSettings($dbname);
    
    // تحويل قيم print_sides لتتناسب مع قاعدة البيانات
    $dbPrintSides = ($printSides === 'two-sided') ? 'double' : 'single';
    
    // تحديد سعر الصفحة بناءً على إعدادات اللون والطباعة
    if ($colorMode == 'color') {
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
    // حساب التكلفة الإجمالية
    $totalCost = $numPages * $numCopies * $pagePrice;
    return $totalCost;
}

// معالجة طلب حساب التكلفة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'calculate_cost') {
    $colorMode = $_POST['color'] ?? 'bw';
    $printSides = $_POST['sides'] ?? 'one-sided';
    $numPages = intval($_POST['page_count'] ?? 1);
    $numCopies = intval($_POST['copies'] ?? 1);
    
    $cost = calculateCost($numPages, $numCopies, $colorMode, $printSides, $dbname, $user_id);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'cost' => $cost]);
    exit;
}

// معالجة طلب معالجة الطباعة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_print') {
    $tempFilePath = $_POST['temp_file_path'] ?? '';
    $originalFileName = $_POST['original_file_name'] ?? '';
    $numPages = intval($_POST['page_count'] ?? 1);
    $numCopies = intval($_POST['copies'] ?? 1);
    $colorMode = $_POST['color'] ?? 'bw';
    $printSides = $_POST['sides'] ?? 'one-sided';
    $orientation = $_POST['layout'] ?? 'portrait';
    $pageOption = $_POST['pages'] ?? 'all';
    $pageRange = $_POST['page_range'] ?? '';
    
    // تحويل قيم print_sides لتتناسب مع قاعدة البيانات
    $dbPrintSides = ($printSides === 'two-sided') ? 'double' : 'single';
    
    // التحقق من وجود الملف
    if (!file_exists($tempFilePath)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'الملف غير موجود']);
        exit;
    }
    
    // حساب التكلفة
    $cost = calculateCost($numPages, $numCopies, $colorMode, $printSides, $dbname, $user_id);
    
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
    
    try {
        // بدء معاملة قاعدة البيانات
        $dbname->beginTransaction();
        
        // إنشاء مهمة طباعة بحالة pending
        $stmt = $dbname->prepare("INSERT INTO print_jobs (user_id, file_name, file_path, num_pages, num_copies, 
                                color_mode, print_sides, orientation, page_range, cost, status, created_at) 
                                VALUES (:user_id, :file_name, :file_path, :num_pages, :num_copies, 
                                :color_mode, :print_sides, :orientation, :page_range, :cost, 'pending', NOW())");

        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':file_name', $originalFileName);
        $stmt->bindParam(':file_path', $target_path);
        $stmt->bindParam(':num_pages', $numPages);
        $stmt->bindParam(':num_copies', $numCopies);
        $stmt->bindParam(':color_mode', $colorMode);
        $stmt->bindParam(':print_sides', $dbPrintSides); // استخدام القيمة المحولة
        $stmt->bindParam(':orientation', $orientation);
        $stmt->bindParam(':page_range', $pageRange);
        $stmt->bindParam(':cost', $cost);
        $stmt->execute();
        
        $job_id = $dbname->lastInsertId();
        
        // خصم التكلفة من رصيد المستخدم
        $new_balance = $balance - $cost;
        $stmt = $dbname->prepare("UPDATE users SET balance = :new_balance WHERE id = :user_id");
        $stmt->bindParam(':new_balance', $new_balance);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        // تسجيل المعاملة
        $stmt = $dbname->prepare("INSERT INTO transactions (user_id, amount, type, description, created_at) 
                                VALUES (:user_id, :amount, 'debit', :description, NOW())");
        $description = "طباعة " . $numPages . " صفحة، " . $numCopies . " نسخة، " . ($colorMode == 'color' ? 'ملون' : 'أبيض وأسود');
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
            'message' => 'تمت معالجة مهمة الطباعة بنجاح وهي في انتظار المعالجة',
            'job_id' => $job_id,
            'cost' => $cost,
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