<?php
/**
 * =============================================================================
 * NAGA MC STORE — AUTH ROUTER & CONTROLLER CONNECTOR
 * Tiếp nhận yêu cầu từ Frontend và gọi hàm tương ứng trong AuthController
 * =============================================================================
 */

require_once __DIR__ . '/bootstrap.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = getRequestData();
if (empty($action) && isset($input['action'])) {
    $action = $input['action'];
}

// Đồng bộ dữ liệu vào $_POST để AuthController luôn đọc được
foreach ($input as $k => $v) {
    if (!isset($_POST[$k])) {
        $_POST[$k] = $v;
    }
}

// Chuẩn hoá các trường đầu vào cho phù hợp với AuthController
// 1. Trường identifier (tên tài khoản hoặc email)
if (empty($_POST['username'])) {
    if (!empty($input['login'])) {
        $_POST['username'] = $input['login'];
    } elseif (!empty($input['email'])) {
        $_POST['username'] = $input['email'];
    }
}

// 2. Trường token OTP
if (empty($_POST['token'])) {
    if (!empty($input['otp'])) {
        $_POST['token'] = $input['otp'];
    } elseif (!empty($input['otp_code'])) {
        $_POST['token'] = $input['otp_code'];
    }
}

// 3. Trường mật khẩu mới khi tạo lại mật khẩu
if (empty($_POST['new_password']) && !empty($input['password'])) {
    $_POST['new_password'] = $input['password'];
}
if (empty($_POST['new_password_confirm'])) {
    if (!empty($input['confirm_password'])) {
        $_POST['new_password_confirm'] = $input['confirm_password'];
    } elseif (!empty($input['password_confirm'])) {
        $_POST['new_password_confirm'] = $input['password_confirm'];
    }
}

switch (strtolower($action)) {
    case 'login':
        AuthController::login();
        break;

    case 'register':
        AuthController::register();
        break;

    case 'logout':
        AuthController::logout();
        break;

    case 'forgot_password':
    case 'forgot-password':
    case 'forgot':
        AuthController::ForgotPassword();
        break;

    case 'verify_otp':
    case 'verify-otp':
    case 'otp':
        AuthController::resetPassword();
        break;

    case 'recreate_password':
    case 'reset_password':
    case 'reset-password':
    case 'recreate':
        AuthController::reCreatePassword();
        break;

    case 'me':
    case 'user':
    case 'session':
        if (!empty($_SESSION['user'])) {
            $user = $_SESSION['user'];
            unset($user['password_hash']);
            response::success($user, 'Đã xác thực phiên đăng nhập');
        } else {
            response::error('Chưa đăng nhập', 401);
        }
        break;

    default:
        response::error('Hành động không hợp lệ: ' . htmlspecialchars($action), 400);
        break;
}
