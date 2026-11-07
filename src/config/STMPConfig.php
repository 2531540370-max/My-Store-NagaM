<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

class STMPConfig
{
    public static function sendResetEmail($recipientEmail, $token)
    {
        $mail = new PHPMailer(true);

        try {
// Cấu hình máy chủ SMTP của Gmail
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'letuonglnkt9@gmail.com';
            $mail->Password = 'kxxi cwiu nnhi lfam'; // Mật khẩu ứng dụng gồm 16 ký tự
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

// Thông tin người gửi và người nhận
            $mail->setFrom('letuonglnkt9@gmail.com', 'Hệ thống website');
            $mail->addAddress($recipientEmail);

// Nội dung email
            $mail->isHTML(true);
            $mail->Subject = 'Yêu cầu đặt lại mật khẩu';
            $mail->Body = "token để reset mật khẩu (hết hạn trong 15 phút) : <br><a> $token </a>";

            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>