<?php
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../config/STMPConfig.php';

class AuthController
{
    public static function login(): void
    {
        // Hỗ trợ cả Form Data ($_POST) và JSON Raw Body (php://input)
        $input = $_POST;
        if (empty($input)) {
            $raw = file_get_contents('php://input');
            $input = json_decode($raw, true) ?? [];
        }

        // 1. Lấy dữ liệu
        $identifier = trim($input['username'] ?? $input['email'] ?? '');
        $password = $input['password'] ?? '';

        // 2. Tìm kiếm user từ Model
        $user = User::findByUsernameOrEmail($identifier);
        /*
        logic kiểm tra
        kiểm tra xem có user thật không nếu không thì báo không có tài khoản
        nếu có user thật kiểm tra mật khẩu
        nếu đúng mật khẩu kiểm tra tk có bị khoá không
        nếu đúng mk và không khoá thì đăng nhập thành công
        */
        if ($user) {
            if (password_verify($password, $user['password_hash'])) {
                if ($user['status'] === 'locked') {
                    response::error("tài khoản của bạn đã bị khoá");
                } else {
                    // Bắt đầu session nếu chưa có
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
                    unset($user['password_hash']);
                    session_regenerate_id(true);
                    $_SESSION['user'] = $user;
                    response::success($user, "đăng nhập thành công", 200);
                }
            } else {
                response::error("sai mật khẩu");
            }

        } else {
            response::error("không có tài khoản trong hệ thống");
        }
    }

    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        session_destroy();
        response::success(null, "Đăng xuất thành công", 200);
    }

    public static function register(): void
    {
        // Hỗ trợ cả Form Data ($_POST) và JSON Raw Body (php://input)
        // lấy dữ liệu
        $input = $_POST;
        if (empty($input)) {
            $raw = file_get_contents('php://input');
            $input = json_decode($raw, true) ?? [];
        }
                // 1. Xác thực Cloudflare Turnstile thật
        $turnstileToken = $input['cf-turnstile-response'] ?? $input['turnstile_token'] ?? '';
        $remoteIp = $_SERVER['REMOTE_ADDR'] ?? null;
        if (!self::verifyTurnstile($turnstileToken, $remoteIp)) {
            response::error("Xác minh bảo mật Cloudflare không hợp lệ hoặc đã hết hạn. Vui lòng thử lại.");
        }

        // làm sạch dữ liệu
        $username = trim($input['username'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = trim($input['password'] ?? '');
        $ingame_name = trim($input['ingame_name'] ?? '');
        //Kiểm tra xem có đủ thông tin không ingame là optinal
        if (empty($username) || empty($email) || empty($password)) {
            response::error("Hãy nhập đầy đủ thông tin");
        }
        //kiểm tra email có đúng không
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            response::error("Email không hợp lệ");
        }
        //Kiểm tra có trùng username hay email không
        if (User::findByUsernameOrEmail($email)) {
            response::error("email đã tồn tại");
        }
        if (User::findByUsernameOrEmail($username)) {
            response::error("username đã tồn tại");
        }
        //nén dữ liệu vào data
        $data = ([
            'username' => $username,
            'email' => $email,
            'ingame_name' => $ingame_name,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT)
        ]);
        //Tạo người dùng
        User::createUser($data);
        response::success(null, "tạo tài khoản thành công");
    }

    public static function reCreatePassword(): void
    {

        //Kiểm tra session
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($_SESSION)) {
            response::error("Session bị trống");
        }
        // Hỗ trợ cả Form Data ($_POST) và JSON Raw Body (php://input)
        //Lấy dữ liệu
        $input = $_POST;
        if (empty($input)) {
            $raw = file_get_contents('php://input');
            $input = json_decode($raw, true) ?? [];
        }
        //Kiểm tra user có nhập đủ thông tin không
        if (empty($input['new_password']) || empty($input['new_password_confirm'])) {
            response::error("vui lòng nhập đầy đủ thông tin");
        }
        //Kiểm tra password và passsword_confirm có giống nhau không
        if ($input['new_password'] !== $input['new_password_confirm']) {
            response::error("mật khẩu xác nhận không khớp");
        } else {
            //nếu giống nhau thì đổi mật khẩu
            $data = ([
                'password_hash' => password_hash($input['new_password'], PASSWORD_BCRYPT),
                'id' => $_SESSION['user']['id'],
            ]);
            User::updateUserPassword($data);
            response::success(null, "đổi mật khẩu thành công");
        }
    }

    public static function ForgotPassword(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Hỗ trợ cả Form Data ($_POST) và JSON Raw Body (php://input)
        //Lấy dữ liệu
        $input = $_POST;
        if (empty($input)) {
            $raw = file_get_contents('php://input');
            $input = json_decode($raw, true) ?? [];
        }
        //Lấy dữ liệu từ username người dùng nhập
        $userdata = User::findByUsernameOrEmail($input['username']);
        //nếu user có thật
        if ($userdata) {
            //tạo token
            $token = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = date('Y-m-d H:i:s', time() + 900); // Hết hạn sau 15 phút
            //nén token và token expires vào chung userdata
            $userdata['token'] = $token;
            $userdata['token_expires'] = $expiresAt;
            //gắn token và token_expires vào csdl
            User::updateUsertoken($userdata);
            STMPConfig::sendResetEmail($userdata['email'], $token);
            $_SESSION['reset_username'] = $userdata['username'];
            response::success(null, "đã gửi mã đến email của bạn");
        } else {
            //nếu user không có thật
            response::error("không có người dùng này");
        }
    }

    public static function resetPassword(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['reset_username'])) {
            response::error("Không tìm thấy phiên xác thực, vui lòng thực hiện lại quên mật khẩu", 400);
        }
        //Lấy dữ liệu
        $input = $_POST;
        if (empty($input)) {
            $raw = file_get_contents('php://input');
            $input = json_decode($raw, true) ?? [];
        }
        //Kiểm tra có nhập token không
        $token = trim($input['token'] ?? '');
        if (empty($token)) {
            response::error("vui lòng nhập token");
        }

        $userdata = User::findByUsernameOrEmail($_SESSION['reset_username']);
        if (!$userdata) {
            response::error("không tìm thấy người dùng", 404);
        }
        if ($userdata['reset_token'] === $input['token']) {
            $current_time = date('Y-m-d H:i:s');
            if ($current_time > $userdata['reset_token_expires']) {
                response::error("token hết hạn");
            } else {
                //xác thực thành công lưu user vào session để tiến hành bước tạo mật khẩu mới
                unset($userdata['password_hash']);
                $_SESSION['user'] = $userdata;
                unset($_SESSION['reset_username'], $_SESSION['reset_attempts']);
                response::success(null , "xác thực thành công");
            }
        } else {
            // Nhập sai token: trừ số lần thử
            $_SESSION['reset_attempts'] = ($_SESSION['reset_attempts'] ?? 6) - 1;

            if ($_SESSION['reset_attempts'] <= 0) {
                User::updateUserStatus([
                    'id' => $userdata['id'],
                    'status' => 'locked'
                ]);
                unset($_SESSION['reset_username'], $_SESSION['reset_attempts']);
                response::error("Bạn đã nhập sai quá 6 lần. Tài khoản của bạn đã bị khoá");
            }

            response::error("Mã token không đúng. Bạn còn " . $_SESSION['reset_attempts'] . " lần thử");
        }
    }

    /**
     * Xác thực Cloudflare Turnstile Token với máy chủ Cloudflare
     */
    private static function verifyTurnstile(?string $token, ?string $remoteIp = null): bool
    {
        if (empty($token)) {
            return false;
        }

        $secretKey = '0x4AAAAAAF2aRpJPMrBv7tPTtFu62KjhzMg';
        $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

        $data = [
            'secret' => $secretKey,
            'response' => $token,
        ];
        if (!empty($remoteIp)) {
            $data['remoteip'] = $remoteIp;
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            $result = curl_exec($ch);
            curl_close($ch);
        } else {
            $options = [
                'http' => [
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => http_build_query($data),
                    'timeout' => 10,
                ],
            ];
            $context = stream_context_create($options);
            $result = @file_get_contents($url, false, $context);
        }

        if (!$result) {
            return false;
        }

        $json = json_decode($result, true);
        return isset($json['success']) && $json['success'] === true;
    }
}
