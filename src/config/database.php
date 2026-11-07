<?php

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host = 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com';
            $port = '4000';
            $dbname = 'defaultdb';
            $username = 'o8i93riPE2Px6mZ.root';
            $password = trim('KidusAPn4fZZ0WLu   '); // kiểm tra lại nếu có dấu cách thừa

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                // TiDB Cloud yêu cầu SSL:
                PDO::MYSQL_ATTR_SSL_CA       => true,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            ];

            try {
                self::$instance = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                die("Lỗi kết nối cơ sở dữ liệu: " . $e->getMessage());
            }
        }

        return self::$instance;
    }
}