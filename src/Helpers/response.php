<?php

if (!class_exists('response')) {
    class response
    {
        /* Hàm chuẩn hoá thông tin trả về chú ý có exit
        nên khi sử dụng các code ở sau sẽ không chạy phải để thông tin trả về ở cuối */
        public static function json(array $payload, int $statusCode = 200): void
        {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        // Hàm trả về thành công, trả về data và message
        public static function success($data = null, string $message = null, int $statusCode = null): void
        {
            self::json([
                'success' => true,
                'status'  => true,
                'data'    => $data,
                'user'    => $data,
                'message' => $message,
            ], $statusCode ?? 200);
        }
        //Hàm trả về lỗi kèm message có thể gắn status, errers nếu cần
        public static function error(string $message = null, int $statusCode = null, $errors = null): void
        {
            $payload = [
                'success' => false,
                'status'  => false,
                'message' => $message,
            ];

            if ($errors !== null) {
                $payload['errors'] = $errors;
            }
            self::json($payload, $statusCode ?? 400);
        }
    }
}
