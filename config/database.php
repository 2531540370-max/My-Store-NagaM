<?php
// File: config/database.php

class Database {
    private static ?PDO $pdo = null;

    public static function connect(): PDO {
        if (self::$pdo === null) {
            // Cấu hình XAMPP mặc định: MySQL/MariaDB trên máy hiện tại, cổng 3306.
            // Nếu đã đổi cổng trong XAMPP Control Panel, cập nhật $port tại đây.
            $host = '127.0.0.1';
            $port = 3306;
            $dbname = 'store_nagamc';
            $username = 'root';
            $password = '';

            try {
                self::$pdo = new PDO(
                    "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
                    $username,
                    $password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch (PDOException $e) {
                die("Lỗi kết nối MySQL: " . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}
?>