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
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'فشل الاتصال بقاعدة البيانات: ' . $e->getMessage()
    ]);
    exit;
}

// التحقق من تسجيل دخول المستخدم
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
if (!$user_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'المستخدم غير مسجل الدخول']);
    exit;
}

// تعيين نوع المحتوى المناسب
header('Content-Type: application/json');

// وظيفة إرسال الإشعارات
function sendNotification($dbname, $user_id, $message) {
    try {
        $stmt = $dbname->prepare("INSERT INTO notifications (user_id, message, created_at, seen) VALUES (:user_id, :message, NOW(), 0)");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':message', $message);
        $stmt->execute();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// حالة تحديث مهمة الطباعة إلى "تمت"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_as_done') {
    if (!isset($_POST['job_id'])) {
        echo json_encode(['success' => false, 'message' => 'معرف المهمة مطلوب']);
        exit;
    }
    
    $job_id = $_POST['job_id'];
    
    try {
        // بدء المعاملة
        $dbname->beginTransaction();
        
        // الحصول على معلومات المهمة
        $stmt = $dbname->prepare("SELECT * FROM print_jobs WHERE id = :job_id");
        $stmt->bindParam(':job_id', $job_id);
        $stmt->execute();
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$job) {
            echo json_encode(['success' => false, 'message' => 'مهمة الطباعة غير موجودة']);
            exit;
        }
        
        // تحديث حالة المهمة إلى "تمت"
        $stmt = $dbname->prepare("UPDATE print_jobs SET status = 'done', completed_at = NOW() WHERE id = :job_id");
        $stmt->bindParam(':job_id', $job_id);
        $stmt->execute();
        
        // حذف الملف من المجلد
        $file_path = $job['file_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // إرسال إشعار للمستخدم
        $user_stmt = $dbname->prepare("SELECT name FROM users WHERE id = :user_id");
        $user_stmt->bindParam(':user_id', $job['user_id']);
        $user_stmt->execute();
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        $notification_message = "تم طباعة المستند \"{$job['file_name']}\" بنجاح وهو جاهز للاستلام.";
        sendNotification($dbname, $job['user_id'], $notification_message);
        
        // تأكيد المعاملة
        $dbname->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'تم تعيين مهمة الطباعة على أنها مكتملة وإرسال إشعار للمستخدم',
            'job_id' => $job_id,
            'user_name' => $user['name'] ?? 'غير معروف'
        ]);
        
    } catch (Exception $e) {
        // إلغاء المعاملة في حالة حدوث خطأ
        $dbname->rollBack();
        
        echo json_encode([
            'success' => false,
            'message' => 'حدث خطأ: ' . $e->getMessage()
        ]);
    }
    exit;
}

// حذف مهمة طباعة وملف
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_job') {
    if (!isset($_POST['job_id'])) {
        echo json_encode(['success' => false, 'message' => 'معرف المهمة مطلوب']);
        exit;
    }
    
    $job_id = $_POST['job_id'];
    
    try {
        // بدء المعاملة
        $dbname->beginTransaction();
        
        // الحصول على معلومات المهمة
        $stmt = $dbname->prepare("SELECT * FROM print_jobs WHERE id = :job_id");
        $stmt->bindParam(':job_id', $job_id);
        $stmt->execute();
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$job) {
            echo json_encode(['success' => false, 'message' => 'مهمة الطباعة غير موجودة']);
            exit;
        }
        
        // حذف الملف من المجلد
        $file_path = $job['file_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // حذف المهمة من قاعدة البيانات
        $stmt = $dbname->prepare("DELETE FROM print_jobs WHERE id = :job_id");
        $stmt->bindParam(':job_id', $job_id);
        $stmt->execute();
        
        // تأكيد المعاملة
        $dbname->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'تم حذف مهمة الطباعة والملف المرتبط بها بنجاح',
            'job_id' => $job_id
        ]);
        
    } catch (Exception $e) {
        // إلغاء المعاملة في حالة حدوث خطأ
        $dbname->rollBack();
        
        echo json_encode([
            'success' => false,
            'message' => 'حدث خطأ: ' . $e->getMessage()
        ]);
    }
    exit;
}

// حذف ملف مؤقت فقط
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // الحصول على بيانات JSON من الطلب
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);
    
    // التحقق من صحة البيانات
    if (!$data || !isset($data['file_path'])) {
        echo json_encode([
            'success' => false,
            'message' => 'بيانات غير صالحة'
        ]);
        exit;
    }
    
    // استخراج معلومات الملف
    $filePath = $data['file_path'];
    
    // التحقق من أمان المسار - السماح فقط بحذف الملفات من مجلدات محددة
    $allowedDirs = [
        '../uploads/',
        '../temp_uploads/'
    ];
    
    $validPath = false;
    foreach ($allowedDirs as $dir) {
        if (strpos($filePath, $dir) === 0) {
            $validPath = true;
            break;
        }
    }
    
    if (!$validPath) {
        echo json_encode([
            'success' => false,
            'message' => 'مسار ملف غير صالح'
        ]);
        exit;
    }
    
    // التأكد من وجود الملف
    if (!file_exists($filePath)) {
        echo json_encode([
            'success' => false,
            'message' => 'الملف غير موجود'
        ]);
        exit;
    }
    
    // حذف الملف
    if (unlink($filePath)) {
        echo json_encode([
            'success' => true,
            'message' => 'تم حذف الملف بنجاح'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'فشل في حذف الملف'
        ]);
    }
    exit;
}

// في أي حالة أخرى، إعادة خطأ
echo json_encode([
    'success' => false,
    'message' => 'طلب غير صالح'
]);
exit;
?>