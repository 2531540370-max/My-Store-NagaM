-- Tạo database chuẩn theo tên trong tài liệu của nhóm
CREATE DATABASE IF NOT EXISTS store_nagamc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE store_nagamc;

-- 1. Bảng Tài khoản người dùng (Có cột lưu IP và Vị trí theo yêu cầu của bạn)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    minecraft_username VARCHAR(50) DEFAULT NULL,
    balance DECIMAL(15,2) DEFAULT 0,
    role ENUM('user', 'admin') DEFAULT 'user',
    status ENUM('active', 'banned') DEFAULT 'active',
    last_ip VARCHAR(45) DEFAULT NULL,
    last_location VARCHAR(150) DEFAULT NULL,
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Nhật ký sự kiện đăng nhập và thao tác trong ứng dụng để Admin giám sát.
CREATE TABLE IF NOT EXISTS system_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(40) NOT NULL,
    severity ENUM('info', 'warning', 'error') NOT NULL DEFAULT 'info',
    user_id INT DEFAULT NULL,
    username VARCHAR(100) DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    message VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_system_logs_created_at (created_at),
    KEY idx_system_logs_event_created (event_type, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Bảng Danh mục vật phẩm (Mục 2)
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Bảng Sản phẩm / Vật phẩm Minecraft (Mục 2)
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT DEFAULT NULL,
    name VARCHAR(150) NOT NULL,
    minecraft_command VARCHAR(255) NOT NULL,
    price DECIMAL(15,2) NOT NULL DEFAULT 0,
    stock INT NOT NULL DEFAULT 100,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Bảng Mã giảm giá & Khuyến mãi (Mục 3)
CREATE TABLE coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    discount_percent INT NOT NULL,
    max_uses INT DEFAULT 100,
    used_count INT DEFAULT 0,
    expires_at DATE DEFAULT NULL,
    status ENUM('active', 'expired') DEFAULT 'active'
);

-- 5. Bảng Đơn hàng (Mục 4 - Theo đúng 6 trạng thái trong README của nhóm)
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    minecraft_username VARCHAR(50) NOT NULL,
    total_amount DECIMAL(15,2) NOT NULL,
    status ENUM('PENDING', 'PAID', 'PROCESSING', 'COMPLETED', 'FAILED', 'CANCELLED') DEFAULT 'PENDING',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. Bảng Tin tức game & Thông báo máy chủ (Mục 6)
CREATE TABLE news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    server_status ENUM('ONLINE', 'MAINTENANCE', 'OFFLINE') DEFAULT 'ONLINE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Thêm sẵn dữ liệu mẫu để trang Thống kê (Mục 1) có số liệu hiển thị ngay
INSERT INTO categories (name, slug) VALUES ('Vũ khí Netherite', 'vu-khi-netherite'), ('Rank VIP', 'rank-vip');
INSERT INTO products (category_id, name, minecraft_command, price, stock) VALUES 
(1, 'Kiếm Netherite Thần Thánh', 'give {player} netherite_sword 1', 50000, 20),
(2, 'Gói VIP Vĩnh Viễn', 'lp user {player} parent set vip', 150000, 999);
INSERT INTO coupons (code, discount_percent, max_uses, expires_at) VALUES ('NAGAMC50', 50, 50, '2026-12-31');
INSERT INTO news (title, content, server_status) VALUES ('Khai trương máy chủ NagaMC!', 'Chào mừng các chiến binh đến với cụm máy chủ Sinh tồn NagaMC.', 'ONLINE');