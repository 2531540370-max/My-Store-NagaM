<?php
// File: resources/views/admin/index.php
require_once __DIR__ . '/../../../app/Middleware/AdminMiddleware.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/Services/SystemLogger.php';

// 1. Kích hoạt "Trạm gác" bảo mật: Chặn đứng mọi tài khoản không phải Admin
AdminMiddleware::checkAdmin();

$pdo = Database::connect();

// Endpoint JSON riêng chỉ cho Admin đã đăng nhập, dùng để cập nhật panel giám sát.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['monitor'] ?? '') === '1') {
    $loginEvents = $pdo->query(
        "SELECT event_type, severity, username, ip_address, message, created_at
         FROM system_logs
         WHERE event_type IN ('login_success', 'login_failed', 'login_blocked')
         ORDER BY id DESC
         LIMIT 20"
    )->fetchAll();
    $systemLogs = $pdo->query(
        'SELECT event_type, severity, username, ip_address, message, created_at
         FROM system_logs
         ORDER BY id DESC
         LIMIT 20'
    )->fetchAll();

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');
    echo json_encode(
        ['loginEvents' => $loginEvents, 'systemLogs' => $systemLogs],
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit();
}

$msg = '';
$tab = $_GET['tab'] ?? 'dashboard'; // Mặc định mở mục 1: Bảng điều khiển
$validTabs = ['dashboard', 'coupons', 'orders', 'users', 'news', 'help'];
if (!in_array($tab, $validTabs, true)) {
    $tab = 'dashboard';
}

// 2. XỬ LÝ MỌI THAO TÁC THÊM / SỬA / XÓA CỦA ADMIN (CÓ KIỂM TRA BẢO MẬT CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    AdminMiddleware::verifyCsrf(); // Chống tấn công giả mạo CSRF
    $action = $_POST['action'] ?? '';

    // --- MỤC 2: THÊM MÃ GIẢM GIÁ (COUPON) ---
    if ($action === 'add_coupon') {
        $code = strtoupper(trim($_POST['code']));
        $stmt = $pdo->prepare("INSERT INTO coupons (code, discount_percent, max_uses, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$code, (int)$_POST['discount_percent'], (int)$_POST['max_uses'], $_POST['expires_at']]);
        SystemLogger::log($pdo, 'admin_action', 'info', (int)$_SESSION['user_id'], $_SESSION['username'], "Admin đã tạo mã giảm giá {$code}.");
        $msg = "Đã tạo mã giảm giá $code thành công!";
        $tab = 'coupons';
    }
    elseif ($action === 'delete_coupon') {
        $couponId = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM coupons WHERE id = ?")->execute([$couponId]);
        SystemLogger::log($pdo, 'admin_action', 'info', (int)$_SESSION['user_id'], $_SESSION['username'], "Admin đã xóa mã giảm giá #{$couponId}.");
        $msg = "Đã xóa mã giảm giá!";
        $tab = 'coupons';
    }

    // --- MỤC 3: QUẢN LÝ & DUYỆT ĐƠN HÀNG ---
    elseif ($action === 'update_order_status') {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], (int)$_POST['order_id']]);
        SystemLogger::log($pdo, 'admin_action', 'info', (int)$_SESSION['user_id'], $_SESSION['username'], "Admin cập nhật trạng thái đơn hàng #" . (int)$_POST['order_id'] . " thành " . $_POST['status'] . ".");
        $msg = "Đã cập nhật trạng thái đơn hàng #" . (int)$_POST['order_id'];
        $tab = 'orders';
    }
    elseif ($action === 'create_demo_order') {
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, minecraft_username, total_amount, status) VALUES (?, ?, ?, 'PENDING')");
        $stmt->execute([$_SESSION['user_id'], 'Steve_VN', 150000]);
        SystemLogger::log($pdo, 'admin_action', 'info', (int)$_SESSION['user_id'], $_SESSION['username'], 'Admin đã tạo đơn hàng mẫu.');
        $msg = "Đã tạo 1 đơn hàng mẫu để test!";
        $tab = 'orders';
    }

    // --- MỤC 4: QUẢN LÝ TÀI KHOẢN (KHÓA / MỞ KHÓA / ĐỔI QUYỀN) ---
    elseif ($action === 'toggle_ban_user') {
        $uid = (int)$_POST['user_id'];
        $newStatus = $_POST['current_status'] === 'active' ? 'banned' : 'active';
        $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$newStatus, $uid]);
        SystemLogger::log($pdo, 'admin_action', 'warning', (int)$_SESSION['user_id'], $_SESSION['username'], "Admin đổi trạng thái tài khoản #{$uid} thành {$newStatus}.");
        $msg = "Đã cập nhật trạng thái khóa tài khoản #$uid!";
        $tab = 'users';
    }
    elseif ($action === 'toggle_role_user') {
        $uid = (int)$_POST['user_id'];
        $newRole = $_POST['current_role'] === 'admin' ? 'user' : 'admin';
        $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$newRole, $uid]);
        SystemLogger::log($pdo, 'admin_action', 'warning', (int)$_SESSION['user_id'], $_SESSION['username'], "Admin đổi quyền tài khoản #{$uid} thành {$newRole}.");
        $msg = "Đã đổi quyền tài khoản #$uid thành " . strtoupper($newRole) . "!";
        $tab = 'users';
    }

    // --- MỤC 5: QUẢN LÝ TIN TỨC GAME & TRẠNG THÁI MÁY CHỦ ---
    elseif ($action === 'add_news') {
        $stmt = $pdo->prepare("INSERT INTO news (title, content, server_status) VALUES (?, ?, ?)");
        $stmt->execute([trim($_POST['title']), trim($_POST['content']), $_POST['server_status']]);
        SystemLogger::log($pdo, 'admin_action', 'info', (int)$_SESSION['user_id'], $_SESSION['username'], 'Admin đã đăng thông báo máy chủ.');
        $msg = "Đã đăng thông báo máy chủ mới!";
        $tab = 'news';
    }
    elseif ($action === 'delete_news') {
        $newsId = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM news WHERE id = ?")->execute([$newsId]);
        SystemLogger::log($pdo, 'admin_action', 'info', (int)$_SESSION['user_id'], $_SESSION['username'], "Admin đã xóa bài tin #{$newsId}.");
        $msg = "Đã xóa bài viết tin tức!";
        $tab = 'news';
    }
}

// 3. LẤY DỮ LIỆU TỪ DATABASE ĐỂ HIỂN THỊ LÊN 6 MỤC
$totalUsers    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalOrders   = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalRevenue  = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status IN ('PAID', 'COMPLETED')")->fetchColumn();

// Tự chuyển mã sang hết hạn khi ngày hết hạn đã qua.
$pdo->exec("UPDATE coupons SET status = 'expired' WHERE status = 'active' AND expires_at IS NOT NULL AND expires_at < CURRENT_DATE");
$coupons    = $pdo->query("SELECT * FROM coupons ORDER BY id DESC")->fetchAll();
$orders     = $pdo->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll();
$users      = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
$newsList   = $pdo->query("SELECT * FROM news ORDER BY id DESC")->fetchAll();
$loginEvents = [];
$systemLogs = [];
if ($tab === 'dashboard') {
    $loginEvents = $pdo->query(
        "SELECT event_type, severity, username, ip_address, message, created_at
         FROM system_logs
         WHERE event_type IN ('login_success', 'login_failed', 'login_blocked')
         ORDER BY id DESC
         LIMIT 20"
    )->fetchAll();
    $systemLogs = $pdo->query(
        'SELECT event_type, severity, username, ip_address, message, created_at
         FROM system_logs
         ORDER BY id DESC
         LIMIT 20'
    )->fetchAll();
}
$loginEventLabels = [
    'login_success' => 'Đăng nhập thành công',
    'login_failed' => 'Đăng nhập thất bại',
    'login_blocked' => 'Tài khoản bị khóa',
];
$severityLabels = [
    'info' => 'Thông tin',
    'warning' => 'Cảnh báo',
    'error' => 'Lỗi',
];
$pageTitles = [
    'dashboard' => 'Bảng điều khiển',
    'coupons' => 'Mã giảm giá',
    'orders' => 'Đơn hàng',
    'users' => 'Tài khoản người dùng',
    'news' => 'Tin tức & máy chủ',
    'help' => 'Hướng dẫn sử dụng',
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản Trị Hệ Thống - Store NagaMC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --naga-ink: #17231f;
            --naga-muted: #748078;
            --naga-green: #236b4b;
            --naga-gold: #f3bd55;
            --naga-paper: #f4f6f1;
        }
        body { background: var(--naga-paper); color: var(--naga-ink); font-family: Inter, "Segoe UI", sans-serif; }
        .sidebar {
            min-height: 100vh;
            color: #f4f7f2;
            background:
                radial-gradient(ellipse at 10% 0%, rgba(243,189,85,.16), transparent 38%),
                linear-gradient(160deg, #172b24 0%, #14201d 65%, #101a18 100%);
            border-right: 1px solid rgba(255,255,255,.07);
        }
        .admin-brand { display: flex; align-items: center; gap: 12px; padding: 9px 5px 18px; border-bottom: 1px solid rgba(255,255,255,.12); }
        .brand-cube { display: grid; width: 42px; height: 42px; place-items: center; border-radius: 13px; background: linear-gradient(145deg, #f8d77e, #d99a32); color: #263126; font-size: 22px; box-shadow: 0 7px 20px rgba(243,189,85,.2); }
        .admin-brand strong { display: block; color: #fff; letter-spacing: .08em; }
        .admin-brand small { color: #a7b6aa; font-size: 11px; letter-spacing: .12em; }
        .sidebar .account-chip { padding: 12px; margin: 16px 0 20px; border: 1px solid rgba(255,255,255,.09); border-radius: 13px; background: rgba(255,255,255,.055); color: #dce5dc; }
        .sidebar .account-chip strong { color: #fff; }
        .sidebar .nav-caption { margin: 0 0 8px 10px; color: #81958a; font-size: 10px; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
        .sidebar a { display: flex; align-items: center; gap: 10px; margin-bottom: 5px; padding: 11px 12px; border: 1px solid transparent; border-radius: 11px; color: #c1cec5; text-decoration: none; font-size: 13px; font-weight: 600; transition: .18s ease; }
        .sidebar a:hover { background: rgba(255,255,255,.07); color: #fff; transform: translateX(2px); }
        .sidebar a.active { border-color: rgba(243,189,85,.23); background: linear-gradient(100deg, rgba(243,189,85,.2), rgba(243,189,85,.08)); color: #ffda83; }
        .sidebar a.logout-link { margin-top: 15px; background: rgba(220,53,69,.13); color: #ffb8b8; }
        .admin-main { min-width: 0; padding: 26px 30px !important; }
        .topbar { display: flex; align-items: center; justify-content: space-between; gap: 18px; margin-bottom: 25px; padding: 17px 20px; border: 1px solid #e6e9e1; border-radius: 17px; background: rgba(255,255,255,.88); box-shadow: 0 5px 22px rgba(25,41,31,.035); }
        .topbar .eyebrow { margin-bottom: 3px; color: var(--naga-green); font-size: 10px; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
        .topbar h1 { margin: 0; font-size: clamp(20px, 2.2vw, 27px); font-weight: 800; letter-spacing: -.04em; }
        .server-pill { display: inline-flex; align-items: center; gap: 8px; padding: 9px 12px; border: 1px solid #dcebe1; border-radius: 999px; background: #f4faf5; color: #286744; font-size: 12px; font-weight: 700; white-space: nowrap; }
        .server-dot { width: 8px; height: 8px; border-radius: 50%; background: #38a968; box-shadow: 0 0 0 4px rgba(56,169,104,.12); }
        .admin-main h3 { color: var(--naga-ink); font-size: 21px; letter-spacing: -.025em; }
        .admin-main .card { overflow: hidden; border: 1px solid #e6e9e1; border-radius: 15px; box-shadow: 0 7px 24px rgba(25,41,31,.045) !important; }
        .admin-main .card-header { padding: 13px 16px; border-bottom: 1px solid rgba(24,42,32,.08); }
        .admin-main .card-body { overflow-x: auto; }
        .admin-main .table { --bs-table-bg: transparent; margin: 0; font-size: 13px; }
        .admin-main .table thead th { padding: 12px; background: #f3f5f0; color: #59665d; font-size: 10px; letter-spacing: .07em; text-transform: uppercase; white-space: nowrap; }
        .admin-main .table td { padding: 12px; vertical-align: middle; }
        .admin-main .form-control, .admin-main .form-select { border-color: #dfe5dc; border-radius: 9px; font-size: 13px; }
        .admin-main .form-control:focus, .admin-main .form-select:focus { border-color: #70a887; box-shadow: 0 0 0 .2rem rgba(35,107,75,.12); }
        .admin-main .btn { border-radius: 9px; font-weight: 650; }
        .admin-main .alert { border: 0; border-radius: 12px; }
        .metric-card { position: relative; min-height: 145px; overflow: hidden; border: 0 !important; color: #fff; }
        .metric-card::after { position: absolute; right: -14px; bottom: -34px; width: 120px; height: 120px; border: 1px solid rgba(255,255,255,.16); border-radius: 28px; content: ""; transform: rotate(28deg); }
        .metric-card .metric-icon { display: grid; width: 34px; height: 34px; place-items: center; margin-bottom: 13px; border: 1px solid rgba(255,255,255,.22); border-radius: 10px; background: rgba(255,255,255,.12); font-size: 17px; }
        .metric-card h6 { color: rgba(255,255,255,.76); font-size: 10px; font-weight: 800; letter-spacing: .1em; }
        .metric-card h2 { margin: 0; font-size: clamp(23px, 2.4vw, 30px); letter-spacing: -.04em; }
        .metric-users { background: linear-gradient(135deg, #276b80, #1d536a) !important; }
        .metric-products { background: linear-gradient(135deg, #39855b, #246a49) !important; }
        .metric-orders { background: linear-gradient(135deg, #c18a32, #9b6722) !important; color: #fff !important; }
        .metric-revenue { background: linear-gradient(135deg, #78558d, #59416e) !important; }
        @media (max-width: 767.98px) {
            .sidebar { min-height: auto; }
            .sidebar .nav-caption { margin-top: 15px; }
            .admin-main { padding: 17px 13px !important; }
            .topbar { align-items: flex-start; padding: 15px; }
            .server-pill { padding: 8px 10px; font-size: 10px; }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- THANH MENU BÊN TRÁI -->
        <div class="col-md-3 col-lg-2 sidebar p-3">
            <div class="admin-brand">
                <span class="brand-cube" aria-hidden="true">⚒</span>
                <span><strong>NAGAMC</strong><small>STORE CONTROL</small></span>
            </div>
            <div class="account-chip small">Xin chào, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></div>

            <div class="nav-caption">Khu vực quản trị</div>
            <a href="?tab=dashboard" class="<?= $tab === 'dashboard' ? 'active' : '' ?>">📊 Bảng điều khiển</a>
            <a href="?tab=coupons" class="<?= $tab === 'coupons' ? 'active' : '' ?>">🎟️ Mã giảm giá</a>
            <a href="?tab=orders" class="<?= $tab === 'orders' ? 'active' : '' ?>">📦 Đơn hàng</a>
            <a href="?tab=users" class="<?= $tab === 'users' ? 'active' : '' ?>">👥 Tài khoản người dùng</a>
            <a href="?tab=news" class="<?= $tab === 'news' ? 'active' : '' ?>">📰 Tin tức & máy chủ</a>
            <a href="?tab=help" class="<?= $tab === 'help' ? 'active' : '' ?>">❔ Hướng dẫn sử dụng</a>

            <a href="../auth/logout.php" class="logout-link">🚪 Đăng xuất</a>
        </div>

        <!-- NỘI DUNG CHÍNH BÊN PHẢI -->
        <main class="col-md-9 col-lg-10 admin-main">
            <header class="topbar">
                <div>
                    <div class="eyebrow">Store NagaMC / Admin</div>
                    <h1><?= htmlspecialchars($pageTitles[$tab]) ?></h1>
                </div>
                <span class="server-pill"><span class="server-dot"></span> Bảng điều khiển sẵn sàng</span>
            </header>
            <?php if ($msg): ?>
                <div class="alert alert-success fw-bold"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <!-- ================= MỤC 1: BẢNG ĐIỀU KHIỂN & THỐNG KÊ ================= -->
            <?php if ($tab === 'dashboard'): ?>
                <h3 class="fw-bold mb-4">📊 1. Bảng Điều Khiển & Thống Kê Hệ Thống</h3>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card metric-card metric-users p-3 shadow-sm">
                            <span class="metric-icon">👥</span>
                            <h6>TỔNG NGƯỜI CHƠI</h6>
                            <h2 class="fw-bold"><?= $totalUsers ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card metric-card metric-products p-3 shadow-sm">
                            <span class="metric-icon">🗡️</span>
                            <h6>VẬT PHẨM ĐANG BÁN</h6>
                            <h2 class="fw-bold"><?= $totalProducts ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card metric-card metric-orders p-3 shadow-sm">
                            <span class="metric-icon">📦</span>
                            <h6>TỔNG ĐƠN HÀNG</h6>
                            <h2 class="fw-bold"><?= $totalOrders ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card metric-card metric-revenue p-3 shadow-sm">
                            <span class="metric-icon">✦</span>
                            <h6>DOANH THU (ĐÃ THANH TOÁN)</h6>
                            <h2 class="fw-bold"><?= number_format($totalRevenue) ?> đ</h2>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="fw-bold mb-0">🛡️ Giám sát an ninh ứng dụng</h3>
                    <small id="monitor-status" class="text-muted" role="status">Đang cập nhật mỗi 10 giây</small>
                </div>
                <div class="row g-3">
                    <div class="col-xl-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-dark text-white fw-bold">🔐 Đăng nhập và IP gần đây</div>
                            <div class="card-body p-0">
                                <table class="table table-striped mb-0">
                                    <thead>
                                        <tr><th>Kết quả</th><th>Tài khoản</th><th>IP kết nối</th><th>Thời gian</th></tr>
                                    </thead>
                                    <tbody id="login-events">
                                        <?php if (!$loginEvents): ?>
                                            <tr><td colspan="4" class="text-center text-muted py-4">Chưa có sự kiện đăng nhập.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($loginEvents as $event): ?>
                                                <?php
                                                $eventLabel = $loginEventLabels[$event['event_type']] ?? $event['event_type'];
                                                $eventBadge = $event['event_type'] === 'login_success' ? 'bg-success' : 'bg-warning text-dark';
                                                ?>
                                                <tr>
                                                    <td><span class="badge <?= $eventBadge ?>"><?= htmlspecialchars($eventLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                                                    <td><?= htmlspecialchars($event['username'] ?? 'Không xác định', ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td><code><?= htmlspecialchars($event['ip_address'] ?? 'Không xác định', ENT_QUOTES, 'UTF-8') ?></code></td>
                                                    <td><?= htmlspecialchars($event['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-dark text-white fw-bold">📋 Nhật ký hệ thống ứng dụng</div>
                            <div class="card-body p-0">
                                <table class="table table-striped mb-0">
                                    <thead>
                                        <tr><th>Mức độ</th><th>Sự kiện</th><th>Tài khoản / IP</th><th>Thời gian</th></tr>
                                    </thead>
                                    <tbody id="system-logs">
                                        <?php if (!$systemLogs): ?>
                                            <tr><td colspan="4" class="text-center text-muted py-4">Chưa có nhật ký hệ thống.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($systemLogs as $log): ?>
                                                <?php
                                                $severityLabel = $severityLabels[$log['severity']] ?? $log['severity'];
                                                $severityBadge = $log['severity'] === 'error'
                                                    ? 'bg-danger'
                                                    : ($log['severity'] === 'warning' ? 'bg-warning text-dark' : 'bg-info text-dark');
                                                ?>
                                                <tr>
                                                    <td><span class="badge <?= $severityBadge ?>"><?= htmlspecialchars($severityLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                                                    <td>
                                                        <strong><?= htmlspecialchars($log['event_type'], ENT_QUOTES, 'UTF-8') ?></strong>
                                                        <div class="small text-muted"><?= htmlspecialchars($log['message'], ENT_QUOTES, 'UTF-8') ?></div>
                                                    </td>
                                                    <td>
                                                        <?= htmlspecialchars($log['username'] ?? 'Hệ thống', ENT_QUOTES, 'UTF-8') ?>
                                                        <div><code><?= htmlspecialchars($log['ip_address'] ?? 'Không xác định', ENT_QUOTES, 'UTF-8') ?></code></div>
                                                    </td>
                                                    <td><?= htmlspecialchars($log['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($tab === 'help'): ?>
                <h3 class="fw-bold mb-2">❔ Hướng dẫn sử dụng trang quản trị</h3>
                <p class="text-muted mb-4">Cách truy cập, công dụng từng mục và cách thay đổi dữ liệu hoặc mã nguồn của bản demo.</p>

                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header bg-dark text-white fw-bold">1. Cách truy cập</div>
                            <div class="card-body">
                                <ol class="mb-0">
                                    <li>Mở XAMPP Control Panel và khởi động <strong>Apache</strong> cùng <strong>MySQL</strong>.</li>
                                    <li>Mở <code>http://localhost/Store-NagaMC%20b%E1%BA%A3n%20c%E1%BA%ADp%20nh%E1%BA%ADt%202/Store-NagaMC/resources/views/auth/login.php</code> với thư mục hiện tại. Nếu bạn chuyển dự án, thay phần đường dẫn sau <code>localhost/</code> bằng vị trí mới trong <code>htdocs</code>.</li>
                                    <li>Đăng nhập bằng tài khoản có quyền <code>admin</code>. Trong database demo mới, tài khoản mẫu <code>admin</code> / <code>123456</code> chỉ được tạo tự động khi bảng <code>users</code> chưa có tài khoản nào.</li>
                                    <li>Sau khi đăng nhập, chọn <strong>Hướng dẫn sử dụng</strong> hoặc các mục quản lý ở thanh bên trái. Chọn <strong>Đăng xuất</strong> khi hoàn tất.</li>
                                </ol>
                                <div class="alert alert-warning mt-3 mb-0">Không dùng tài khoản mẫu trên website công khai. Tài khoản thường không được vào khu vực Admin.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header bg-success text-white fw-bold">2. Chức năng các mục</div>
                            <div class="card-body">
                                <ul class="mb-0">
                                    <li><strong>Bảng điều khiển:</strong> xem tổng người chơi, vật phẩm, đơn hàng, doanh thu đã thanh toán; xem sự kiện đăng nhập và nhật ký ứng dụng. Hai bảng giám sát tự làm mới mỗi 10 giây khi đang mở mục này.</li>
                                    <li><strong>Mã giảm giá:</strong> tạo coupon với mã, phần trăm giảm, số lượt tối đa và ngày hết hạn; xem trạng thái hoặc xóa coupon. Mã hết hạn được đánh dấu khi trang Admin được tải sau ngày hết hạn, không chạy bằng tác vụ nền.</li>
                                    <li><strong>Đơn hàng:</strong> xem đơn và thay đổi trạng thái bằng danh sách chọn rồi bấm <strong>Lưu</strong>. Nút tạo đơn hàng mẫu chỉ dùng để thử nghiệm.</li>
                                    <li><strong>Tài khoản người dùng:</strong> xem thông tin và IP/vị trí đăng nhập gần nhất; dùng <strong>Khóa/Mở khóa</strong> hoặc <strong>Đổi quyền</strong>. Chỉ cấp quyền Admin cho người đáng tin cậy.</li>
                                    <li><strong>Tin tức & máy chủ:</strong> đăng thông báo mới và chọn trạng thái ONLINE, MAINTENANCE hoặc OFFLINE; xóa bài cũ bằng <strong>Xóa bài</strong>.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white fw-bold">3. Cách thay đổi dữ liệu hiện có</div>
                            <div class="card-body">
                                <ul>
                                    <li><strong>Đơn hàng:</strong> chọn trạng thái mới trong hàng cần cập nhật, sau đó bấm <strong>Lưu</strong>.</li>
                                    <li><strong>Tài khoản:</strong> bấm <strong>Khóa/Mở khóa</strong> để đổi trạng thái; bấm <strong>Đổi quyền</strong> để chuyển giữa User và Admin.</li>
                                    <li><strong>Coupon:</strong> bản hiện tại không có nút sửa coupon. Để đổi thông tin, xóa coupon cũ rồi tạo lại coupon với giá trị mới.</li>
                                    <li><strong>Tin tức:</strong> bản hiện tại không có nút sửa bài. Sao chép nội dung cần giữ, xóa bài cũ rồi đăng lại bản đã chỉnh sửa.</li>
                                </ul>
                                <p class="mb-0">Các thao tác ghi dữ liệu yêu cầu Admin và mã CSRF hợp lệ. Không chỉnh trực tiếp bảng database nếu có thể dùng nút trên giao diện; luôn sao lưu trước khi sửa database.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header bg-info text-dark fw-bold">4. Cách chỉnh sửa giao diện, chức năng và cấu hình</div>
                            <div class="card-body">
                                <ol>
                                    <li>Mở toàn bộ thư mục dự án trong VS Code và sao lưu file trước khi sửa.</li>
                                    <li>Sửa giao diện, nội dung, các tab và thao tác Admin trong <code>resources/views/admin/index.php</code>.</li>
                                    <li>Sửa kết nối XAMPP trong <code>config/database.php</code> (host, cổng, tên database, user và mật khẩu).</li>
                                    <li>Thay đổi cấu trúc bảng bằng migration SQL trong <code>database/migrations/</code>; sao lưu database trước khi chạy SQL trong phpMyAdmin.</li>
                                    <li>Lưu file, tải lại trang và kiểm tra trên database thử nghiệm. Không xóa/tạo lại bảng đang có dữ liệu.</li>
                                </ol>
                                <div class="alert alert-info mb-0">Quản lý sản phẩm/danh mục, thanh toán thực tế và tích hợp cấp vật phẩm Minecraft chưa được triển khai trong bản demo này.</div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ================= MỤC 2: MÃ GIẢM GIÁ & KHUYẾN MÃI ================= -->
            <?php if ($tab === 'coupons'): ?>
                <h3 class="fw-bold mb-4">🎟️ 2. Quản Lý Mã Giảm Giá & Chương Trình Khuyến Mãi</h3>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-success text-white fw-bold">Tạo Mã Giảm Giá Mới</div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="action" value="add_coupon">
                                    <div class="mb-2">
                                        <label>Mã Code (VD: HE2026)</label>
                                        <input type="text" name="code" class="form-control" required>
                                    </div>
                                    <div class="mb-2">
                                        <label>Phần trăm giảm (%)</label>
                                        <input type="number" name="discount_percent" class="form-control" required min="1" max="100" value="20">
                                    </div>
                                    <div class="mb-2">
                                        <label>Số lượt dùng tối đa</label>
                                        <input type="number" name="max_uses" class="form-control" required value="50">
                                    </div>
                                    <div class="mb-3">
                                        <label>Ngày hết hạn</label>
                                        <input type="date" name="expires_at" class="form-control" required>
                                    </div>
                                    <button class="btn btn-success w-100 fw-bold">Phát Hành Mã</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card shadow-sm">
                            <div class="card-header bg-dark text-white fw-bold">Danh Sách Mã Khuyến Mãi</div>
                            <div class="card-body p-0">
                                <table class="table table-striped mb-0">
                                    <thead>
                                        <tr><th>Mã Code</th><th>Giảm giá</th><th>Lượt dùng</th><th>Hết hạn</th><th>Trạng thái</th><th>Thao tác</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($coupons as $cp): ?>
                                        <tr>
                                            <td><span class="badge bg-warning text-dark fs-6"><?= htmlspecialchars($cp['code']) ?></span></td>
                                            <td class="fw-bold text-success">-<?= $cp['discount_percent'] ?>%</td>
                                            <td><?= $cp['used_count'] ?> / <?= $cp['max_uses'] ?></td>
                                            <td><?= htmlspecialchars($cp['expires_at']) ?></td>
                                            <td>
                                                <?php if ($cp['status'] === 'expired'): ?>
                                                    <span class="badge bg-secondary">Đã hết hạn</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Đang hoạt động</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                    <input type="hidden" name="action" value="delete_coupon">
                                                    <input type="hidden" name="id" value="<?= $cp['id'] ?>">
                                                    <button class="btn btn-sm btn-danger">Xóa</button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ================= MỤC 3: QUẢN LÝ ĐƠN HÀNG ================= -->
            <?php if ($tab === 'orders'): ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold mb-0">📦 4. Quản Lý Đơn Hàng & Duyệt Giao Dịch</h3>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="create_demo_order">
                        <button class="btn btn-outline-primary fw-bold">+ Tạo Đơn Hàng Mẫu Để Test</button>
                    </form>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-dark">
                                <tr><th>Mã Đơn</th><th>Tên Nhân Vật Minecraft</th><th>Tổng Tiền</th><th>Trạng Thái Hiện Tại</th><th>Thời Gian</th><th>Đổi Trạng Thái (Chuẩn README)</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $o): ?>
                                <tr>
                                    <td class="fw-bold">#<?= $o['id'] ?></td>
                                    <td><?= htmlspecialchars($o['minecraft_username']) ?></td>
                                    <td class="fw-bold text-danger"><?= number_format($o['total_amount']) ?> đ</td>
                                    <td><span class="badge bg-info text-dark"><?= $o['status'] ?></span></td>
                                    <td><?= $o['created_at'] ?></td>
                                    <td>
                                        <form method="POST" class="d-flex gap-2">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="action" value="update_order_status">
                                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                            <select name="status" class="form-select form-select-sm">
                                                <?php foreach (['PENDING','PAID','PROCESSING','COMPLETED','FAILED','CANCELLED'] as $st): ?>
                                                    <option value="<?= $st ?>" <?= $o['status']===$st?'selected':'' ?>><?= $st ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="btn btn-sm btn-primary">Lưu</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ================= MỤC 4: QUẢN LÝ TÀI KHOẢN + XEM IP & VỊ TRÍ ================= -->
            <?php if ($tab === 'users'): ?>
                <h3 class="fw-bold mb-4">👥 5. Quản Lý Tài Khoản Người Dùng (Kèm Giám Sát IP & Vị Trí)</h3>
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <table class="table table-striped table-bordered align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Username / Email</th>
                                    <th>Nhân vật MC</th>
                                    <th>Địa chỉ IP</th>
                                    <th>Vị trí (Thành phố, Quốc gia, Mạng)</th>
                                    <th>Quyền</th>
                                    <th>Trạng thái</th>
                                    <th>Hành động bảo mật</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?= $u['id'] ?></td>
                                    <td>
                                        <b><?= htmlspecialchars($u['username']) ?></b><br>
                                        <small class="text-muted"><?= htmlspecialchars($u['email']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($u['minecraft_username'] ?? '') ?></td>
                                    <td><code class="fw-bold text-danger"><?= htmlspecialchars($u['last_ip'] ?? 'Chưa có') ?></code></td>
                                    <td class="text-primary fw-semibold">📍 <?= htmlspecialchars($u['last_location'] ?? 'Chưa xác định') ?></td>
                                    <td><span class="badge <?= $u['role']==='admin'?'bg-danger':'bg-secondary' ?>"><?= strtoupper($u['role']) ?></span></td>
                                    <td>
                                        <span class="badge <?= $u['status']==='active'?'bg-success':'bg-dark' ?>">
                                            <?= $u['status']==='active' ? 'Hoạt động' : 'Đã khóa (Banned)' ?>
                                        </span>
                                    </td>
                                    <td class="d-flex gap-1">
                                        <!-- Nút Khóa / Mở khóa -->
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="action" value="toggle_ban_user">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <input type="hidden" name="current_status" value="<?= $u['status'] ?>">
                                            <button class="btn btn-sm <?= $u['status']==='active'?'btn-warning':'btn-success' ?>">
                                                <?= $u['status']==='active' ? '🔒 Khóa' : '🔓 Mở khóa' ?>
                                            </button>
                                        </form>
                                        <!-- Nút Đổi quyền Admin / User -->
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="action" value="toggle_role_user">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <input type="hidden" name="current_role" value="<?= $u['role'] ?>">
                                            <button class="btn btn-sm btn-outline-dark">Đổi Quyền</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ================= MỤC 5: TIN TỨC GAME & MÁY CHỦ ================= -->
            <?php if ($tab === 'news'): ?>
                <h3 class="fw-bold mb-4">📰 6. Quản Lý Tin Tức Game & Trạng Thái Máy Chủ Minecraft</h3>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-info text-dark fw-bold">Đăng Thông Báo & Trạng Thái Server</div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="action" value="add_news">
                                    <div class="mb-2">
                                        <label>Tiêu đề bản tin</label>
                                        <input type="text" name="title" class="form-control" required placeholder="VD: Sự kiện Săn Rồng Ender">
                                    </div>
                                    <div class="mb-2">
                                        <label>Trạng thái Máy chủ hiện tại</label>
                                        <select name="server_status" class="form-select">
                                            <option value="ONLINE">🟢 ONLINE (Đang hoạt động)</option>
                                            <option value="MAINTENANCE">🟡 MAINTENANCE (Đang bảo trì)</option>
                                            <option value="OFFLINE">🔴 OFFLINE (Tạm ngắt)</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label>Nội dung thông báo</label>
                                        <textarea name="content" rows="4" class="form-control" required></textarea>
                                    </div>
                                    <button class="btn btn-info w-100 fw-bold">Đăng Bản Tin</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <?php foreach ($newsList as $n): ?>
                            <div class="card shadow-sm mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <h5 class="fw-bold"><?= htmlspecialchars($n['title']) ?></h5>
                                        <span class="badge <?= $n['server_status']==='ONLINE'?'bg-success':($n['server_status']==='MAINTENANCE'?'bg-warning text-dark':'bg-danger') ?>">
                                            Server: <?= $n['server_status'] ?>
                                        </span>
                                    </div>
                                    <p class="mb-2"><?= nl2br(htmlspecialchars($n['content'])) ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">Đăng lúc: <?= $n['created_at'] ?></small>
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="action" value="delete_news">
                                            <input type="hidden" name="id" value="<?= $n['id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger">Xóa bài</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </main>
    </div>
</div>
<?php if ($tab === 'dashboard'): ?>
<script>
(() => {
    const loginBody = document.getElementById('login-events');
    const logsBody = document.getElementById('system-logs');
    const monitorStatus = document.getElementById('monitor-status');
    const loginLabels = {
        login_success: 'Đăng nhập thành công',
        login_failed: 'Đăng nhập thất bại',
        login_blocked: 'Tài khoản bị khóa'
    };
    const severityLabels = { info: 'Thông tin', warning: 'Cảnh báo', error: 'Lỗi' };
    let isUpdating = false;

    function addCell(row, value) {
        const cell = document.createElement('td');
        cell.textContent = value ?? '';
        row.appendChild(cell);
        return cell;
    }

    function addBadge(cell, label, className) {
        const badge = document.createElement('span');
        badge.className = `badge ${className}`;
        badge.textContent = label;
        cell.appendChild(badge);
    }

    function renderEmpty(body, columnCount, message) {
        const row = document.createElement('tr');
        const cell = addCell(row, message);
        cell.colSpan = columnCount;
        cell.className = 'text-center text-muted py-4';
        body.replaceChildren(row);
    }

    function renderLoginEvents(events) {
        if (events.length === 0) {
            renderEmpty(loginBody, 4, 'Chưa có sự kiện đăng nhập.');
            return;
        }

        const rows = events.map((event) => {
            const row = document.createElement('tr');
            const resultCell = addCell(row, '');
            const successful = event.event_type === 'login_success';
            addBadge(
                resultCell,
                loginLabels[event.event_type] || event.event_type,
                successful ? 'bg-success' : 'bg-warning text-dark'
            );
            addCell(row, event.username || 'Không xác định');
            const ipCell = addCell(row, '');
            const ip = document.createElement('code');
            ip.textContent = event.ip_address || 'Không xác định';
            ipCell.appendChild(ip);
            addCell(row, event.created_at || 'Không xác định');
            return row;
        });
        loginBody.replaceChildren(...rows);
    }

    function renderSystemLogs(logs) {
        if (logs.length === 0) {
            renderEmpty(logsBody, 4, 'Chưa có nhật ký hệ thống.');
            return;
        }

        const rows = logs.map((log) => {
            const row = document.createElement('tr');
            const severityCell = addCell(row, '');
            const severityClass = log.severity === 'error'
                ? 'bg-danger'
                : (log.severity === 'warning' ? 'bg-warning text-dark' : 'bg-info text-dark');
            addBadge(severityCell, severityLabels[log.severity] || log.severity, severityClass);

            const eventCell = addCell(row, '');
            const eventName = document.createElement('strong');
            eventName.textContent = log.event_type || 'Sự kiện';
            const message = document.createElement('div');
            message.className = 'small text-muted';
            message.textContent = log.message || '';
            eventCell.append(eventName, message);

            const accountCell = addCell(row, '');
            accountCell.append(document.createTextNode(log.username || 'Hệ thống'));
            const ip = document.createElement('code');
            ip.textContent = log.ip_address || 'Không xác định';
            const ipLine = document.createElement('div');
            ipLine.appendChild(ip);
            accountCell.appendChild(ipLine);
            addCell(row, log.created_at || 'Không xác định');
            return row;
        });
        logsBody.replaceChildren(...rows);
    }

    async function refreshMonitor() {
        if (isUpdating || document.hidden) {
            return;
        }

        isUpdating = true;
        try {
            const response = await fetch(`${window.location.pathname}?tab=dashboard&monitor=1`, {
                headers: { Accept: 'application/json' },
                cache: 'no-store'
            });
            if (!response.ok) {
                throw new Error(`Lỗi HTTP ${response.status}`);
            }

            const data = await response.json();
            if (!Array.isArray(data.loginEvents) || !Array.isArray(data.systemLogs)) {
                throw new Error('Dữ liệu giám sát không đúng định dạng.');
            }

            renderLoginEvents(data.loginEvents);
            renderSystemLogs(data.systemLogs);
            monitorStatus.textContent = `Đã cập nhật lúc ${new Date().toLocaleTimeString()}`;
        } catch (error) {
            monitorStatus.textContent = 'Không thể cập nhật giám sát. Kiểm tra kết nối hoặc nhật ký máy chủ.';
        } finally {
            isUpdating = false;
        }
    }

    window.setInterval(refreshMonitor, 10000);
    document.addEventListener('visibilitychange', refreshMonitor);
})();
</script>
<?php endif; ?>
</body>
</html>