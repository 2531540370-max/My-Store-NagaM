<?php
// File: resources/views/auth/login.php
session_start();
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/Helpers/IpHelper.php';

$pdo = Database::connect();

// Tự động tạo 2 tài khoản mẫu (admin và steve) nếu bảng users đang trống
$checkCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($checkCount == 0) {
    $hash = password_hash('123456', PASSWORD_BCRYPT);
    $ip   = IpHelper::getUserIp();
    $loc  = IpHelper::getLocation($ip);

    $stmtSeed = $pdo->prepare("INSERT INTO users (username, email, password_hash, minecraft_username, balance, role, last_ip, last_location, last_login) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmtSeed->execute(['admin', 'admin@nagamc.com', $hash, 'Naga_Admin', 999999, 'admin', $ip, $loc]);
    $stmtSeed->execute(['steve', 'steve@nagamc.com', $hash, 'Steve_VN', 50000, 'user', $ip, $loc]);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account  = trim($_POST['account'] ?? '');
    $password = $_POST['password'] ?? '';

    // ĐÃ SỬA LỖI HY093: Dùng 2 dấu ? riêng biệt cho username và email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
    $stmt->execute([$account, $account]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['status'] === 'banned') {
            $error = "Tài khoản của bạn đã bị Quản trị viên khóa!";
        } else {
            // Bắt Địa chỉ IP và Vị trí ngay lúc đăng nhập để lưu vào Database
            $currentIp  = IpHelper::getUserIp();
            $currentLoc = IpHelper::getLocation($currentIp);

            $updateStmt = $pdo->prepare("UPDATE users SET last_ip = ?, last_location = ?, last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$currentIp, $currentLoc, $user['id']]);

            // Làm mới Session ID chống đánh cắp phiên
            session_regenerate_id(true);
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            // Chuyển hướng sang trang Quản trị
            header("Location: ../admin/index.php");
            exit();
        }
    } else {
        $error = "Tài khoản hoặc mật khẩu không chính xác!";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập - Store NagaMC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="card bg-secondary text-white shadow-lg p-4" style="width: 400px; border-radius: 12px;">
        <h4 class="text-center fw-bold mb-3">⚔️ STORE NAGAMC - LOGIN</h4>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Tên tài khoản hoặc Email</label>
                <input type="text" name="account" class="form-control" required placeholder="Nhập: admin hoặc steve">
            </div>
            <div class="mb-3">
                <label class="form-label">Mật khẩu</label>
                <input type="password" name="password" class="form-control" required placeholder="Nhập: 123456">
            </div>
            <button type="submit" class="btn btn-warning w-100 fw-bold">ĐĂNG NHẬP HỆ THỐNG</button>
        </form>

        <div class="mt-3 p-2 bg-dark rounded small text-info">
            <div>🛡️ <b>Tài khoản Admin:</b> <code>admin</code> / MK: <code>123456</code></div>
            <div>🎮 <b>Tài khoản User:</b> <code>steve</code> / MK: <code>123456</code></div>
        </div>
    </div>
</body>
</html>