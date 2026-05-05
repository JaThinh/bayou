<?php
namespace App\Middlewares;

use App\Core\Database;

class SecurityMiddleware
{
    private Database $db;

    // Giới hạn chống Bot: Tối đa 60 requests / phút
    const MAX_REQUESTS_PER_MINUTE = 60;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Hook bảo mật chính chặn Bot và Blacklist IP
     */
    public function handle(string $ipAddress, string $endpoint): bool
    {
        if ($this->isBlacklisted($ipAddress)) {
            error_log("Security Blocked: IP bị cấm ($ipAddress)");
            throw new \Exception("Truy cập bị từ chối: Địa chỉ IP của bạn đã bị đưa vào danh sách đen.");
        }

        if ($this->isRateLimited($ipAddress, $endpoint)) {
            error_log("Security Blocked: Phát hiện Spam/Bot ($ipAddress) tại $endpoint");
            throw new \Exception("Quá nhiều yêu cầu hệ thống. Vui lòng thử lại sau.");
        }

        return true;
    }

    private function isBlacklisted(string $ip): bool
    {
        // Kiểm tra xem IP có trong bảng `ip_blacklist` và chưa hết hạn không
        $sql = "SELECT id FROM ip_blacklist WHERE ip_address = ? AND (expires_at IS NULL OR expires_at > NOW())";
        $result = $this->db->queryOne($sql, [$ip]);
        return !empty($result);
    }

    private function isRateLimited(string $ip, string $endpoint): bool
    {
        // Xóa các record cũ hơn 1 phút
        $this->db->execute("DELETE FROM rate_limits WHERE window_start < (NOW() - INTERVAL 1 MINUTE)");

        // Kiểm tra record hiện tại
        $sql = "SELECT id, hit_count FROM rate_limits WHERE ip_address = ? AND endpoint = ?";
        $record = $this->db->queryOne($sql, [$ip, $endpoint]);

        if (!$record) {
            $this->db->execute(
                "INSERT INTO rate_limits (ip_address, endpoint, hit_count, window_start) VALUES (?, ?, 1, NOW())",
                [$ip, $endpoint]
            );
            return false;
        }

        if ($record['hit_count'] >= self::MAX_REQUESTS_PER_MINUTE) {
            return true; // Quá giới hạn (Rate Limited)
        }

        // Cập nhật số lần hit
        $this->db->execute("UPDATE rate_limits SET hit_count = hit_count + 1 WHERE id = ?", [$record['id']]);
        return false;
    }
}
