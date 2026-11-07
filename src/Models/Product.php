<?php
require_once __DIR__ . '/../config/database.php';
class Product
{
    //Hàm lấy tất cả thông tin từ tất cả sản phẩm của products
    public static function getAllProducts(): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM products");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getProductById($id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->execute([
            ':id' => $id
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getProductByCategoryId($category_id): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM products WHERE category_id = :category_id");
        $stmt->execute([
            ':category_id' => $category_id
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getProductByName($product_name): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM products WHERE product_name LIKE :product_name");
        $stmt->execute([
            ':product_name' => $product_name
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function Add(array $data)
    {
        //Kiểm tra các trường bắt buộc không được để trống
        if (empty($data['name']) || empty($data['minecraft_command'])) {
            return false;
        }

        $db = Database::getConnection();

        $sql = "INSERT INTO products (category_id, name, minecraft_command, price, stock, image) 
            VALUES (:category_id, :name, :minecraft_command, :price, :stock, :image)";

        $stmt = $db->prepare($sql);

        //Xử lý dữ liệu và giá trị
        $success = $stmt->execute([
            ':category_id'        => !empty($data['category_id']) ? (int)$data['category_id'] : null,
            ':name'               => trim($data['name']),
            ':minecraft_command'  => trim($data['minecraft_command']),
            ':price'              => isset($data['price']) && $data['price'] !== '' ? (float)$data['price'] : 0,
            ':stock'              => isset($data['stock']) && $data['stock'] !== '' ? (int)$data['stock'] : 100,
            ':image'              => !empty($data['image']) ? trim($data['image']) : null,
        ]);

        //Nếu thành công trả về kết quả
        if ($success) {
            response::success(null,"đã thêm sản phẩm mới");
        }
    }

    public static function Update($data)
    {
        //Lưu các thông tin được đổi
        $allowedUpdate = [
            'category_id',
            'name',
            'description',
            'price',
            'image_url',
            'stock',
            'is_active'
        ];

        $Clause = [];
        $params = [':id' => $data['id']];

        //Lọc các trường có trong whitelist và không bị rỗng
        foreach ($data as $key => $value) {
            // Chỉ xử lý nếu cột nằm trong danh sách cho phép
            if (in_array($key, $allowedUpdate, true)) {
                // Kiểm tra: Không phải null và nếu là chuỗi thì không được rỗng sau khi trim
                if ($value !== null && trim((string)$value) !== '') {
                    $setClause[] = "`{$key}` = :{$key}";
                    $params[":{$key}"] = is_string($value) ? trim($value) : $value;
                }
            }
        }

        //Nếu không có trường nào hợp lệ để cập nhật, dừng lại
        if (empty($setClause)) {
            return false;
        }

        //Tạo câu truy vấn động và thực thi
        $sql = "UPDATE products SET " . implode(', ', $setClause) . " WHERE id = :id";

        $db = Database::getConnection();
        $stmt = $db->prepare($sql);

        return $stmt->execute($params);

    }

    public static function Delete($id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM products WHERE id = :id");
        $stmt->execute([
            ':id' => $id
        ]);
    }

    public static function DeleteAll()
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM products");
        $stmt->execute();
    }
}