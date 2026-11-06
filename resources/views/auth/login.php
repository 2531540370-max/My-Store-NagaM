<?php
// Trang đăng nhập: xử lý xác thực ở phía máy chủ rồi hiển thị form HTML.

// Session lưu trạng thái đăng nhập giữa các request. Trang admin dùng các giá trị
// user_id, username và role được gán bên dưới để nhận diện và phân quyền người dùng.
session_start();

// Nạp lớp kết nối database và helper dùng để lấy IP/tra cứu vị trí.
// __DIR__ giúp đường dẫn include vẫn đúng dù trang được gọi từ URL khác.
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/Helpers/IpHelper.php';
require_once __DIR__ . '/../../../app/Services/SystemLogger.php';

// Database::connect() trả về kết nối PDO dùng cho các truy vấn trong trang này.
$pdo = Database::connect();

// Khởi tạo dữ liệu demo khi chưa có bất kỳ tài khoản nào.
// Tài khoản mẫu chỉ phục vụ chạy thử; không dùng thông tin mặc định trên website
// công khai. Mật khẩu được lưu dưới dạng hash, không lưu nguyên văn trong database.
$checkCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($checkCount == 0) {
    // Dùng cùng mật khẩu demo cho các tài khoản mẫu nhưng chỉ lưu kết quả hash.
    $hash = password_hash('123456', PASSWORD_BCRYPT);

    // Ghi IP và vị trí ban đầu để các cột thông tin đăng nhập gần nhất có dữ liệu.
    // Việc tra cứu vị trí phụ thuộc kết nối Internet và dịch vụ bên ngoài.
    $ip   = IpHelper::getUserIp();
    $loc  = IpHelper::getLocation($ip);

    // Prepared statement giúp truyền giá trị riêng khỏi câu lệnh SQL.
    // NOW() ghi thời điểm tạo tài khoản demo trong database.
    $stmtSeed = $pdo->prepare("INSERT INTO users (username, email, password_hash, minecraft_username, balance, role, last_ip, last_location, last_login) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    // Tạo một tài khoản quản trị và một tài khoản người chơi mẫu.
    $stmtSeed->execute(['admin', 'admin@nagamc.com', $hash, 'Naga_Admin', 999999, 'admin', $ip, $loc]);
    $stmtSeed->execute(['steve', 'steve@nagamc.com', $hash, 'Steve_VN', 50000, 'user', $ip, $loc]);
}

// Chuỗi này được đặt khi thông tin đăng nhập sai hoặc tài khoản bị khóa,
// sau đó hiển thị thành thông báo trên form.
$error = '';

// Chỉ xử lý xác thực khi người dùng gửi form bằng phương thức POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Xóa khoảng trắng thừa ở đầu/cuối tên tài khoản hoặc email.
    // Dùng giá trị mặc định rỗng nếu request không gửi trường tương ứng.
    $account  = trim($_POST['account'] ?? '');
    $password = $_POST['password'] ?? '';

    // Tìm một tài khoản khớp tên đăng nhập hoặc email.
    // Mỗi dấu ? là một tham số riêng nên cần truyền $account hai lần.
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
    $stmt->execute([$account, $account]);
    $user = $stmt->fetch();

    // Chỉ tiếp tục nếu tìm thấy tài khoản và mật khẩu nhập vào khớp password_hash.
    if ($user && password_verify($password, $user['password_hash'])) {
        // Người dùng bị khóa không được tạo session đăng nhập.
        if ($user['status'] === 'banned') {
            SystemLogger::log(
                $pdo,
                'login_blocked',
                'warning',
                (int)$user['id'],
                $user['username'],
                'Đăng nhập bị từ chối vì tài khoản đã bị khóa.'
            );
            $error = "Tài khoản của bạn đã bị Quản trị viên khóa!";
        } else {
            // Cập nhật thông tin lần đăng nhập gần nhất để trang quản trị có thể
            // hiển thị IP, vị trí và thời điểm người dùng đăng nhập.
            $currentIp  = IpHelper::getUserIp();
            $currentLoc = IpHelper::getLocation($currentIp);

            $updateStmt = $pdo->prepare("UPDATE users SET last_ip = ?, last_location = ?, last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$currentIp, $currentLoc, $user['id']]);

            SystemLogger::log(
                $pdo,
                'login_success',
                'info',
                (int)$user['id'],
                $user['username'],
                'Đăng nhập thành công.'
            );

            // Đổi session ID sau khi xác thực để không tiếp tục dùng ID phiên
            // được tạo trước đăng nhập.
            session_regenerate_id(true);

            // Lưu thông tin tối thiểu cần thiết cho các trang được bảo vệ.
            // Middleware admin dùng user_id để xác nhận đăng nhập và role để phân quyền.
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            // Chuyển người dùng đã đăng nhập tới trang quản trị.
            // exit() ngăn PHP tiếp tục xuất nội dung của trang login sau redirect.
            header("Location: ../admin/index.php");
            exit();
        }
    } else {
        // Dùng thông báo chung để không tiết lộ tài khoản hay mật khẩu nào bị sai.
        SystemLogger::log(
            $pdo,
            'login_failed',
            'warning',
            null,
            $account,
            'Đăng nhập thất bại: tài khoản không tồn tại hoặc mật khẩu không khớp.'
        );
        $error = "Tài khoản hoặc mật khẩu không chính xác!";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập - Store NagaMC</title>
    <!-- Bootstrap cung cấp các lớp bố cục và thành phần giao diện dùng bên dưới. -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Nền toàn màn hình; các lớp gradient tạo chiều sâu cho trang đăng nhập. */
        body {
            min-height: 100vh;
            color: #f4f5ef;
            background:
                radial-gradient(ellipse at 50% 12%, rgba(69,130,91,.25), transparent 38%),
                linear-gradient(145deg, #101b18, #1c3027 58%, #17221f);
            font-family: Inter, "Segoe UI", sans-serif;
        }

        /* Thẻ chính chứa form, co theo màn hình nhưng không vượt quá 420px. */
        .login-card {
            width: min(420px, calc(100vw - 32px));
            padding: 32px !important;
            border: 1px solid rgba(255,255,255,.11);
            border-radius: 20px !important;
            background: rgba(30,43,37,.94) !important;
            box-shadow: 0 25px 80px rgba(0,0,0,.4) !important;
        }

        /* Khung biểu tượng trang trí phía trên tiêu đề. */
        .login-emblem {
            display: grid;
            width: 58px;
            height: 58px;
            place-items: center;
            margin: 0 auto 15px;
            border: 1px solid rgba(255,218,131,.4);
            border-radius: 17px;
            background: linear-gradient(145deg, #f8d77e, #d99a32);
            color: #263126;
            font-size: 29px;
            box-shadow: 0 8px 25px rgba(243,189,85,.2);
        }

        /* Màu chữ và khoảng cách cho tiêu đề, nhãn và các trường nhập liệu. */
        .login-card h4 { color: #fff; letter-spacing: .06em; }
        .login-card .form-label { color: #d5ddd6; font-size: 13px; font-weight: 600; }
        .login-card .form-control { padding: 11px 13px; border-color: rgba(255,255,255,.12); border-radius: 10px; background: #17221e; color: #fff; }
        .login-card .form-control::placeholder { color: #7f9085; }
        .login-card .form-control:focus { border-color: #76a889; box-shadow: 0 0 0 .2rem rgba(67,139,92,.2); }

        /* Nút gửi form và hộp thông tin tài khoản demo. */
        .login-card .btn-warning { padding: 11px; border: 0; border-radius: 10px; background: linear-gradient(100deg, #f2c460, #e8a93f); color: #2a2a1e; }
        .demo-hint { border: 1px solid rgba(255,255,255,.08); border-radius: 11px; background: rgba(12,20,17,.6); }
        .demo-hint code { color: #ffda83; }

        /* Thu nhỏ khoảng đệm trên màn hình điện thoại để form vẫn vừa khung. */
        @media (max-width: 480px) { .login-card { padding: 25px !important; } }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center py-4">
    <div class="card login-card text-white">
        <!-- aria-hidden loại biểu tượng trang trí khỏi cây trợ năng. -->
        <div class="login-emblem" aria-hidden="true">⚒</div>
        <h4 class="text-center fw-bold mb-3">⚔️ STORE NAGAMC - LOGIN</h4>

        <!-- Chỉ render khung lỗi khi quá trình đăng nhập đặt nội dung cho $error.
             htmlspecialchars() mã hóa ký tự đặc biệt trước khi đưa thông báo ra HTML. -->
        <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Form gửi username/email và mật khẩu về chính URL này bằng POST. -->
        <form method="POST">
            <div class="mb-3">
                <!-- name là khóa PHP đọc từ $_POST ở phần xử lý phía trên. -->
                <label class="form-label">Tên tài khoản hoặc Email</label>
                <input type="text" name="account" class="form-control" required placeholder="Nhập: admin hoặc steve">
            </div>
            <div class="mb-3">
                <label class="form-label">Mật khẩu</label>
                <input type="password" name="password" class="form-control" required placeholder="Nhập: 123456">
            </div>
            <!-- Nút submit gửi form để PHP thực hiện truy vấn và xác thực. -->
            <button type="submit" class="btn btn-warning w-100 fw-bold">ĐĂNG NHẬP HỆ THỐNG</button>
        </form>

        <!-- Thông tin tiện dụng cho môi trường demo; cần gỡ hoặc thay đổi trước
             khi đưa website lên Internet để tránh công khai thông tin đăng nhập. -->
        <div class="demo-hint mt-3 p-3 small text-info">
            <div>🛡️ <b>Tài khoản Admin:</b> <code>admin</code> / MK: <code>123456</code></div>
            <div>🎮 <b>Tài khoản User:</b> <code>steve</code> / MK: <code>123456</code></div>
        </div>
    </div>
</body>
</html>