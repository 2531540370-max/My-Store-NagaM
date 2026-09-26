<?php
// File: app/Middleware/AdminMiddleware.php

class AdminMiddleware {
    // Chặn mọi người dùng chưa đăng nhập hoặc không phải Admin
    public static function checkAdmin(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 1. Chưa đăng nhập -> Đẩy về trang login
        if (empty($_SESSION['user_id'])) {
            header("Location: ../auth/login.php");
            exit();
        }

        // 2. Không phải quyền admin -> Báo lỗi 403 chặn đứng
        if (($_SESSION['role'] ?? '') !== 'admin') {
            http_response_code(403);
            die("<div style='font-family:sans-serif; text-align:center; margin-top:80px;'>
                    <h1 style='color:#dc3545;'>🛑 403 FORBIDDEN - TRUY CẬP BỊ TỪ CHỐI!</h1>
                    <p>Tài khoản <b>" . htmlspecialchars($_SESSION['username'] ?? '') . "</b> chỉ là Người chơi, không có quyền vào khu vực Quản trị (Admin).</p>
                    <a href='../auth/logout.php'>Đăng xuất để đổi tài khoản Admin</a>
                 </div>");
        }

        // 3. Tạo mã bảo mật CSRF Token chống giả mạo thao tác Admin
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    // Kiểm tra mã CSRF khi Admin bấm nút Thêm / Sửa / Xóa
    public static function verifyCsrf(): void {
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            die("Cảnh báo bảo mật: Phát hiện yêu cầu giả mạo (CSRF Token không hợp lệ)!");
        }
    }
}
?>