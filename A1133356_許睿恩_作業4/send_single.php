<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// 修改這裡的路徑，指向正確的 PHPMailer-master 資料夾
require_once __DIR__ . '/PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer-master/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $body = $_POST['body'] ?? '';

    if (!$email) die("缺少收件者");

    $mail = new PHPMailer(true);
    try {
        // SMTP 伺服器設定 (以 Gmail 為例)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'a1133356@mail.nuk.edu.tw'; // 你的信箱
        $mail->Password   = 'osiy ndci vrpl hstm';    // 你的 Gmail 應用程式密碼
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // 收件者與寄件者
        $mail->setFrom('your_email@gmail.com', '郵件發送系統');
        $mail->addAddress($email);

        // 內容
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = nl2br(htmlspecialchars($body));

        $mail->send();
        echo "success";
    } catch (Exception $e) {
        echo "Error: {$mail->ErrorInfo}";
    }
}
?>