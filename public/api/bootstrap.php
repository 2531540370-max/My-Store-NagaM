<?php
/**
 * =============================================================================
 * NAGA MC STORE — API BOOTSTRAPPER
 * Nạp môi trường, kết nối Database, xử lý session, CORS và nạp Controller an toàn
 * =============================================================================
 */

// Bắt đầu Session an toàn với cookie tương thích
if (session_status() === PHP_SESSION_NONE) {
    // Thiết lập cookie session có thời hạn 7 ngày
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Thiết lập header phản hồi JSON
header('Content-Type: application/json; charset=utf-8');

// Định nghĩa lớp response chuẩn hoá (khắc phục lỗi strict type null của PHP 8)
if (!class_exists('response', false)) {
    class response
    {
        public static function json(array $payload, ?int $statusCode = 200): void
        {
            $code = $statusCode ?? 200;
            if ($code < 100 || $code > 599) {
                $code = 200;
            }
            if (!headers_sent()) {
                http_response_code($code);
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        public static function success($data = null, ?string $message = null, ?int $statusCode = 200): void
        {
            self::json([
                'success' => true,
                'status'  => true,
                'data'    => $data,
                'user'    => $data,
                'message' => $message,
            ], $statusCode ?? 200);
        }

        public static function error(?string $message = null, ?int $statusCode = 400, $errors = null): void
        {
            $payload = [
                'success' => false,
                'status'  => false,
                'message' => $message,
            ];

            if ($errors !== null) {
                $payload['errors'] = $errors;
            }

            self::json($payload, $statusCode ?? 400);
        }
    }
}

// Nạp Database và User Model
$rootPath = dirname(__DIR__, 2);
require_once $rootPath . '/src/config/database.php';
require_once $rootPath . '/src/Models/User.php';
require_once $rootPath . '/src/Models/Product.php';

// Xử lý nạp STMPConfig: Nếu có vendor autoload thì dùng PHPMailer, nếu chưa có thì dùng fallback
$vendorAutoload = $rootPath . '/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $rootPath . '/src/config/STMPConfig.php';
} else {
    if (!class_exists('STMPConfig', false)) {
        class STMPConfig
        {
            public static function sendResetEmail($recipientEmail, $token)
            {
                // Lưu token vào session cho môi trường dev/local nếu thiếu cấu hình SMTP
                $_SESSION['last_reset_token'] = $token;
                $_SESSION['last_reset_email'] = $recipientEmail;

                // Thử gửi bằng hàm mail() mặc định của PHP nếu máy chủ hỗ trợ
                $subject = "Mã OTP đặt lại mật khẩu NagaMC: " . $token;
                $message = "Xin chào,\n\nMã OTP để đặt lại mật khẩu của bạn là: " . $token . "\nMã có hiệu lực trong 15 phút.\n\nNagaMC Store";
                $headers = "From: noreply@nagamc.live\r\nReply-To: support@nagamc.live\r\nX-Mailer: PHP/" . phpversion();
                @mail($recipientEmail, $subject, $message, $headers);

                return true;
            }
        }
    }
}

// Nạp AuthController an toàn
if (!class_exists('AuthController', false)) {
    $authPath = $rootPath . '/src/Controllers/AuthController.php';
    if (file_exists($authPath)) {
        $authCode = file_get_contents($authPath);
        $srcControllersDir = realpath($rootPath . '/src/Controllers');
        // Thay thế __DIR__ bằng đường dẫn thực của thư mục src/Controllers
        $authCode = str_replace('__DIR__', var_export($srcControllersDir, true), $authCode);
        // Loại bỏ require_once response.php và STMPConfig.php vì đã nạp ở trên
        $authCode = preg_replace('/require_once[^\n]+response\.php[^\n]+;/', '// response already loaded', $authCode);
        $authCode = preg_replace('/require_once[^\n]+STMPConfig\.php[^\n]+;/', '// STMPConfig already loaded', $authCode);
        $authCode = preg_replace('/^<\?php/', '', $authCode);
        eval($authCode);
    }
}

/**
 * Lấy dữ liệu gửi lên từ Client (hỗ trợ cả JSON body lẫn Form Data)
 */
function getRequestData(): array
{
    $input = $_POST;
    if (empty($input)) {
        $raw = file_get_contents('php://input');
        if (!empty($raw)) {
            $jsonData = json_decode($raw, true);
            if (is_array($jsonData)) {
                $input = $jsonData;
            }
        }
    }
    return $input ?: [];
}
