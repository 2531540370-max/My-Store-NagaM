<?php
// File: public/test_db.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::connect();

    // 1. Kiểm tra xem default đã có những bảng nào
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    // 2. Nếu đã có bảng products thì lấy thử 5 vật phẩm đầu tiên
    $products = [];
    if (in_array('products', $tables)) {
        $stmt = $db->query("SELECT id, name, price,stock FROM products LIMIT 5");
        $products = $stmt->fetchAll();
    }

    echo json_encode([
        'status'       => 'success',
        'message'      => 'Kết nối Cloud MySQL thành công!',
        'tables_count' => count($tables),
        'tables_list'  => $tables,
        'sample_items' => $products
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} 