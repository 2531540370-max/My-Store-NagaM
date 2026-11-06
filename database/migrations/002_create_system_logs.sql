-- Import file này bằng phpMyAdmin trên XAMPP.
-- CREATE DATABASE IF NOT EXISTS an toàn khi database đã tồn tại.
CREATE DATABASE IF NOT EXISTS store_nagamc
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE store_nagamc;

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
