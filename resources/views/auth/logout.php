<?php
// File: resources/views/auth/logout.php
session_start();

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/Services/SystemLogger.php';

if (!empty($_SESSION['user_id'])) {
    SystemLogger::log(
        Database::connect(),
        'logout',
        'info',
        (int)$_SESSION['user_id'],
        $_SESSION['username'] ?? null,
        'Người dùng đã đăng xuất.'
    );
}

session_unset();
session_destroy();
header("Location: login.php");
exit();
?>