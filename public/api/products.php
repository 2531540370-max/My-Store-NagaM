<?php
/**
 * =============================================================================
 * NAGA MC STORE — PRODUCTS API
 * Trả về danh sách sản phẩm hoặc chi tiết theo id, danh mục, tìm kiếm
 * =============================================================================
 */

require_once __DIR__ . '/bootstrap.php';

$action = $_GET['action'] ?? 'all';
$id = $_GET['id'] ?? null;
$categoryId = $_GET['category_id'] ?? null;
$search = $_GET['name'] ?? $_GET['q'] ?? null;

try {
    if (!empty($id)) {
        $product = Product::getProductById((int)$id);
        if ($product) {
            response::success($product, 'Lấy sản phẩm thành công');
        } else {
            response::error('Không tìm thấy sản phẩm', 404);
        }
    } elseif (!empty($categoryId)) {
        $products = Product::getProductByCategoryId((int)$categoryId);
        response::success($products, 'Lấy sản phẩm theo danh mục thành công');
    } elseif (!empty($search)) {
        $products = Product::getProductByName('%' . $search . '%');
        response::success($products, 'Tìm kiếm sản phẩm thành công');
    } else {
        $products = Product::getAllProducts();
        response::success($products, 'Lấy danh sách sản phẩm thành công');
    }
} catch (Throwable $e) {
    response::error('Lỗi khi tải dữ liệu sản phẩm: ' . $e->getMessage(), 500);
}
