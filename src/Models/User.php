<?php
require_once __DIR__ . '/../config/database.php';
class User
{
    // Hàm dùng để lấy dữ liệu từ bảng user từ database
    public static function findByUsernameOrEmail(string $data): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = :username OR email = :email LIMIT 1");
        $stmt->execute([
            ':username' => $data,
            ':email' => $data
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function createUser(array $data)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO users (username, password_hash, email, ingame_name) VALUES (:username, :password_hash, :email, :ingame_name)");
        $stmt->execute([
            ':username' => $data['username'],
            ':password_hash' => $data['password_hash'],
            ':email' => $data['email'],
            ':ingame_name' => $data['ingame_name']
        ]);;
    }

    public static function updateUserPassword(array $data) {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE users SET password_hash = :password_hash WHERE id = :id ");
        $stmt->execute([
            ':password_hash' => $data['password_hash'],
            ':id' => $data['id']
        ]);
    }

    public static function updateUsertoken(array $data) {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE users SET reset_token = :token, reset_token_expires = :token_expires WHERE id = :id ");
        $stmt->execute([
            ':token' => $data['token'],
            ':token_expires' => $data['token_expires'],
            ':id' => $data['id']
        ]);
    }

    public static function updateUserStatus(array $data) {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE users SET status = :status WHERE id = :id ");
        $stmt->execute([
            ':status' => $data['status'],
            ':id' => $data['id']
        ]);
    }
}