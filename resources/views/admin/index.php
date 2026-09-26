<?php
// File: resources/views/admin/index.php
require_once __DIR__ . '/../../../app/Middleware/AdminMiddleware.php';
require_once __DIR__ . '/../../../config/database.php';

// 1. Kích hoạt "Trạm gác" bảo mật: Chặn đứng mọi tài khoản không phải Admin
AdminMiddleware::checkAdmin();

$pdo = Database::connect();
$msg = '';
$tab = $_GET['tab'] ?? 'dashboard'; // Mặc định mở mục 1: Bảng điều khiển

// 2. XỬ LÝ MỌI THAO TÁC THÊM / SỬA / XÓA CỦA ADMIN (CÓ KIỂM TRA BẢO MẬT CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    AdminMiddleware::verifyCsrf(); // Chống tấn công giả mạo CSRF
    $action = $_POST['action'] ?? '';

    // --- MỤC 2: THÊM DANH MỤC & SẢN PHẨM ---
    if ($action === 'add_category') {
        $name = trim($_POST['name']);
        $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $name));
        $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)")->execute([$name, $slug]);
        $msg = "Đã thêm danh mục mới thành công!";
        $tab = 'products';
    }
    elseif ($action === 'add_product') {
        $stmt = $pdo->prepare("INSERT INTO products (category_id, name, minecraft_command, price, stock) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['category_id'], trim($_POST['name']), trim($_POST['minecraft_command']), (float)$_POST['price'], (int)$_POST['stock']]);
        $msg = "Đã thêm vật phẩm Minecraft thành công!";
        $tab = 'products';
    }
    elseif ($action === 'delete_product') {
        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([(int)$_POST['id']]);
        $msg = "Đã xóa vật phẩm!";
        $tab = 'products';
    }

    // --- MỤC 3: THÊM MÃ GIẢM GIÁ (COUPON) ---
    elseif ($action === 'add_coupon') {
        $code = strtoupper(trim($_POST['code']));
        $stmt = $pdo->prepare("INSERT INTO coupons (code, discount_percent, max_uses, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$code, (int)$_POST['discount_percent'], (int)$_POST['max_uses'], $_POST['expires_at']]);
        $msg = "Đã tạo mã giảm giá $code thành công!";
        $tab = 'coupons';
    }
    elseif ($action === 'delete_coupon') {
        $pdo->prepare("DELETE FROM coupons WHERE id = ?")->execute([(int)$_POST['id']]);
        $msg = "Đã xóa mã giảm giá!";
        $tab = 'coupons';
    }

    // --- MỤC 4: QUẢN LÝ & DUYỆT ĐƠN HÀNG ---
    elseif ($action === 'update_order_status') {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], (int)$_POST['order_id']]);
        $msg = "Đã cập nhật trạng thái đơn hàng #" . (int)$_POST['order_id'];
        $tab = 'orders';
    }
    elseif ($action === 'create_demo_order') {
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, minecraft_username, total_amount, status) VALUES (?, ?, ?, 'PENDING')");
        $stmt->execute([$_SESSION['user_id'], 'Steve_VN', 150000]);
        $msg = "Đã tạo 1 đơn hàng mẫu để test!";
        $tab = 'orders';
    }

    // --- MỤC 5: QUẢN LÝ TÀI KHOẢN (KHÓA / MỞ KHÓA / ĐỔI QUYỀN) ---
    elseif ($action === 'toggle_ban_user') {
        $uid = (int)$_POST['user_id'];
        $newStatus = $_POST['current_status'] === 'active' ? 'banned' : 'active';
        $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$newStatus, $uid]);
        $msg = "Đã cập nhật trạng thái khóa tài khoản #$uid!";
        $tab = 'users';
    }
    elseif ($action === 'toggle_role_user') {
        $uid = (int)$_POST['user_id'];
        $newRole = $_POST['current_role'] === 'admin' ? 'user' : 'admin';
        $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$newRole, $uid]);
        $msg = "Đã đổi quyền tài khoản #$uid thành " . strtoupper($newRole) . "!";
        $tab = 'users';
    }

    // --- MỤC 6: QUẢN LÝ TIN TỨC GAME & TRẠNG THÁI MÁY CHỦ ---
    elseif ($action === 'add_news') {
        $stmt = $pdo->prepare("INSERT INTO news (title, content, server_status) VALUES (?, ?, ?)");
        $stmt->execute([trim($_POST['title']), trim($_POST['content']), $_POST['server_status']]);
        $msg = "Đã đăng thông báo máy chủ mới!";
        $tab = 'news';
    }
    elseif ($action === 'delete_news') {
        $pdo->prepare("DELETE FROM news WHERE id = ?")->execute([(int)$_POST['id']]);
        $msg = "Đã xóa bài viết tin tức!";
        $tab = 'news';
    }
}

// 3. LẤY DỮ LIỆU TỪ DATABASE ĐỂ HIỂN THỊ LÊN 6 MỤC
$totalUsers    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalOrders   = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalRevenue  = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status IN ('PAID', 'COMPLETED')")->fetchColumn();

$categories = $pdo->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll();
$products   = $pdo->query("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC")->fetchAll();
$coupons    = $pdo->query("SELECT * FROM coupons ORDER BY id DESC")->fetchAll();
$orders     = $pdo->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll();
$users      = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
$newsList   = $pdo->query("SELECT * FROM news ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản Trị Hệ Thống - Store NagaMC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .sidebar { min-height: 100vh; background: #1e293b; color: #fff; }
        .sidebar a { color: #cbd5e1; text-decoration: none; display: block; padding: 12px 16px; border-radius: 8px; margin-bottom: 6px; font-weight: 500; }
        .sidebar a:hover, .sidebar a.active { background: #f59e0b; color: #000; font-weight: 700; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- THANH MENU BÊN TRÁI (6 CHỨC NĂNG ADMIN) -->
        <div class="col-md-3 col-lg-2 sidebar p-3">
            <h5 class="text-warning fw-bold text-center py-2 border-bottom border-secondary">⚔️ NAGAMC ADMIN</h5>
            <div class="small text-center text-light mb-3">
                Xin chào, <b><?= htmlspecialchars($_SESSION['username']) ?></b>
            </div>

            <a href="?tab=dashboard" class="<?= $tab === 'dashboard' ? 'active' : '' ?>">📊 1. Bảng Điều Khiển & Thống Kê</a>
            <a href="?tab=products" class="<?= $tab === 'products' ? 'active' : '' ?>">🗡️ 2. Sản Phẩm & Danh Mục</a>
            <a href="?tab=coupons" class="<?= $tab === 'coupons' ? 'active' : '' ?>">🎟️ 3. Mã Giảm Giá & Khuyến Mãi</a>
            <a href="?tab=orders" class="<?= $tab === 'orders' ? 'active' : '' ?>">📦 4. Quản Lý Đơn Hàng</a>
            <a href="?tab=users" class="<?= $tab === 'users' ? 'active' : '' ?>">👥 5. Tài Khoản (IP & Vị Trí)</a>
            <a href="?tab=news" class="<?= $tab === 'news' ? 'active' : '' ?>">📰 6. Tin Tức & Máy Chủ</a>

            <hr class="border-secondary">
            <a href="../auth/logout.php" class="bg-danger text-white text-center">🚪 Đăng Xuất</a>
        </div>

        <!-- NỘI DUNG CHÍNH BÊN PHẢI -->
        <div class="col-md-9 col-lg-10 p-4">
            <?php if ($msg): ?>
                <div class="alert alert-success fw-bold"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <!-- ================= MỤC 1: BẢNG ĐIỀU KHIỂN & THỐNG KÊ ================= -->
            <?php if ($tab === 'dashboard'): ?>
                <h3 class="fw-bold mb-4">📊 1. Bảng Điều Khiển & Thống Kê Hệ Thống</h3>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white p-3 shadow-sm">
                            <h6>TỔNG NGƯỜI CHƠI</h6>
                            <h2 class="fw-bold"><?= $totalUsers ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white p-3 shadow-sm">
                            <h6>VẬT PHẨM ĐANG BÁN</h6>
                            <h2 class="fw-bold"><?= $totalProducts ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-dark p-3 shadow-sm">
                            <h6>TỔNG ĐƠN HÀNG</h6>
                            <h2 class="fw-bold"><?= $totalOrders ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white p-3 shadow-sm">
                            <h6>DOANH THU (ĐÃ THANH TOÁN)</h6>
                            <h2 class="fw-bold"><?= number_format($totalRevenue) ?> đ</h2>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white fw-bold">🌍 Giám Sát Đăng Nhập Gần Đây (IP & Vị Trí Thực Tế)</div>
                    <div class="card-body p-0">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr><th>Tài khoản</th><th>Tên Minecraft</th><th>Quyền</th><th>Địa chỉ IP</th><th>Vị trí & Nhà mạng</th><th>Đăng nhập lúc</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($u['username']) ?></td>
                                    <td><?= htmlspecialchars($u['minecraft_username'] ?? '') ?></td>
                                    <td><span class="badge <?= $u['role']==='admin'?'bg-danger':'bg-secondary' ?>"><?= strtoupper($u['role']) ?></span></td>
                                    <td><code><?= htmlspecialchars($u['last_ip'] ?? 'Chưa rõ') ?></code></td>
                                    <td class="text-primary fw-semibold">📍 <?= htmlspecialchars($u['last_location'] ?? 'Chưa xác định') ?></td>
                                    <td><?= htmlspecialchars($u['last_login'] ?? '') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ================= MỤC 2: SẢN PHẨM & DANH MỤC ================= -->
            <?php if ($tab === 'products'): ?>
                <h3 class="fw-bold mb-4">🗡️ 2. Quản Lý Sản Phẩm & Danh Mục</h3>
                <div class="row">
                    <div class="col-md-4">
                        <!-- Form thêm Danh mục -->
                        <div class="card shadow-sm mb-3">
                            <div class="card-header bg-secondary text-white fw-bold">Thêm Danh Mục Mới</div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="action" value="add_category">
                                    <div class="input-group">
                                        <input type="text" name="name" class="form-control" required placeholder="VD: Vũ khí Thần Thoại">
                                        <button class="btn btn-dark">Thêm</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Form thêm Vật phẩm Minecraft -->
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white fw-bold">Thêm Vật Phẩm Minecraft</div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="action" value="add_product">
                                    <div class="mb-2">
                                        <label>Danh mục</label>
                                        <select name="category_id" class="form-select">
                                            <?php foreach ($categories as $c): ?>
                                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label>Tên vật phẩm</label>
                                        <input type="text" name="name" class="form-control" required placeholder="VD: Cúp Netherite">
                                    </div>
                                    <div class="mb-2">
                                        <label>Lệnh cấp đồ trong Server</label>
                                        <input type="text" name="minecraft_command" class="form-control" required placeholder="give {player} diamond 64">
                                    </div>
                                    <div class="mb-2">
                                        <label>Giá bán (VNĐ)</label>
                                        <input type="number" name="price" class="form-control" required min="0">
                                    </div>
                                    <div class="mb-3">
                                        <label>Tồn kho</label>
                                        <input type="number" name="stock" class="form-control" required value="100">
                                    </div>
                                    <button class="btn btn-primary w-100 fw-bold">Lưu Vật Phẩm</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="card shadow-sm">
                            <div class="card-header bg-dark text-white fw-bold">Danh Sách Vật Phẩm Hiện Có</div>
                            <div class="card-body p-0">
                                <table class="table table-bordered table-hover mb-0">
                                    <thead>
                                        <tr><th>ID</th><th>Danh mục</th><th>Tên vật phẩm</th><th>Lệnh Server</th><th>Giá</th><th>Kho</th><th>Xóa</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $p): ?>
                                        <tr>
                                            <td><?= $p['id'] ?></td>
                                            <td><span class="badge bg-info text-dark"><?= htmlspecialchars($p['category_name'] ?? 'Khác') ?></span></td>
                                            <td class="fw-bold"><?= htmlspecialchars($p['name']) ?></td>
                                            <td><code><?= htmlspecialchars($p['minecraft_command']) ?></code></td>
                                            <td class="text-danger fw-bold"><?= number_format($p['price']) ?> đ</td>
                                            <td><?= $p['stock'] ?></td>
                                            <td>
                                                <form method="POST" onsubmit="return confirm('Xóa vật phẩm này?');">
                                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                    <input type="hidden" name="action" value="delete_product">
                                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                    <button class="btn btn-sm btn-outline-danger">Xóa</button>
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

            <!-- ================= MỤC 3: MÃ GIẢM GIÁ & KHUYẾN MÃI ================= -->
            <?php if ($tab === 'coupons'): ?>
                <h3 class="fw-bold mb-4">🎟️ 3. Quản Lý Mã Giảm Giá & Chương Trình Khuyến Mãi</h3>
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
                                        <tr><th>Mã Code</th><th>Giảm giá</th><th>Lượt dùng</th><th>Hết hạn</th><th>Thao tác</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($coupons as $cp): ?>
                                        <tr>
                                            <td><span class="badge bg-warning text-dark fs-6"><?= htmlspecialchars($cp['code']) ?></span></td>
                                            <td class="fw-bold text-success">-<?= $cp['discount_percent'] ?>%</td>
                                            <td><?= $cp['used_count'] ?> / <?= $cp['max_uses'] ?></td>
                                            <td><?= htmlspecialchars($cp['expires_at']) ?></td>
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

            <!-- ================= MỤC 4: QUẢN LÝ ĐƠN HÀNG ================= -->
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

            <!-- ================= MỤC 5: QUẢN LÝ TÀI KHOẢN + XEM IP & VỊ TRÍ ================= -->
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

            <!-- ================= MỤC 6: TIN TỨC GAME & MÁY CHỦ ================= -->
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

        </div>
    </div>
</div>
</body>
</html>