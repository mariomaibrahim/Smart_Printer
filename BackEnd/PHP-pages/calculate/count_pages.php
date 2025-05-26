
<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../vendor/autoload.php';

use Smalot\PdfParser\Parser;

if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] == 0) {
    $tmpPath = $_FILES['pdf_file']['tmp_name'];
    $fileType = mime_content_type($tmpPath);

    if ($fileType !== 'application/pdf') {
        echo json_encode(['success' => false, 'message' => 'الملف ليس PDF.']);
        exit;
    }

    try {
        $parser = new Parser();
        $pdf = $parser->parseFile($tmpPath);
        $pages = count($pdf->getPages());
        echo json_encode(['success' => true, 'pages' => $pages]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'فشل في قراءة الملف.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'لم يتم رفع الملف.']);
}
?>

