<?php
// File: app/Helpers/IpHelper.php

class IpHelper {
    // 1. Lấy địa chỉ IP của người dùng
    public static function getUserIp(): string {
        $ip = $_SERVER['HTTP_CLIENT_IP'] 
           ?? $_SERVER['HTTP_X_FORWARDED_FOR'] 
           ?? $_SERVER['REMOTE_ADDR'] 
           ?? '0.0.0.0';

        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }

        // Nếu đang chạy trên máy cá nhân (Localhost), tự lấy IP mạng thật để tra được vị trí
        if ($ip === '127.0.0.1' || $ip === '::1') {
            $ctx = stream_context_create(['http' => ['timeout' => 2]]);
            $publicIp = @file_get_contents('https://api.ipify.org', false, $ctx);
            if ($publicIp !== false) {
                return trim($publicIp);
            }
        }
        return $ip;
    }

    // 2. Tra cứu Thành phố, Quốc gia và Nhà mạng từ IP
    public static function getLocation(string $ip): string {
        if ($ip === '127.0.0.1' || $ip === '::1' || $ip === '0.0.0.0') {
            return 'Máy cục bộ (Localhost)';
        }

        $url = "http://ip-api.com/json/" . urlencode($ip) . "?fields=status,country,city,isp";
        $ctx = stream_context_create(['http' => ['timeout' => 2]]);
        $res = @file_get_contents($url, false, $ctx);

        if ($res !== false) {
            $data = json_decode($res, true);
            if (($data['status'] ?? '') === 'success') {
                return $data['city'] . ', ' . $data['country'] . ' (' . $data['isp'] . ')';
            }
        }
        return 'Không xác định';
    }
}
?>