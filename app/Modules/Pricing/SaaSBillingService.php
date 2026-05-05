<?php
namespace App\Modules\Pricing;

use App\Core\Database;

class SaaSBillingService
{
    private Database $db;

    // Giới hạn mặc định theo yêu cầu (Có thể mở rộng lấy từ DB Settings)
    const LIMIT_SEARCHES = 400000;
    const LIMIT_BOOKINGS = 2000;
    const LIMIT_TICKETS = 1000;

    // Phí phụ trội
    const FEE_OVERAGE_SEARCH = 100;
    const FEE_OVERAGE_BOOKING = 2000;
    const FEE_OVERAGE_TICKET = 5000;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Lấy hoặc tạo mới bản ghi usage cho tháng hiện tại
     */
    private function getCurrentUsage(int $userId): array
    {
        $currentMonth = date('Y-m');
        $sql = "SELECT * FROM usage_tracking WHERE user_id = ? AND billing_month = ?";
        $usage = $this->db->queryOne($sql, [$userId, $currentMonth]);

        if (!$usage) {
            $this->db->execute(
                "INSERT INTO usage_tracking (user_id, billing_month, searches_count, bookings_count, tickets_count) VALUES (?, ?, 0, 0, 0)",
                [$userId, $currentMonth]
            );
            $usage = $this->db->queryOne($sql, [$userId, $currentMonth]);
        }

        return $usage;
    }

    /**
     * Xử lý khi có 1 request tìm kiếm mới
     */
    public function logSearch(int $userId): void
    {
        $usage = $this->getCurrentUsage($userId);
        $newCount = $usage['searches_count'] + 1;

        $this->db->execute(
            "UPDATE usage_tracking SET searches_count = ? WHERE id = ?",
            [$newCount, $usage['id']]
        );

        if ($newCount > self::LIMIT_SEARCHES) {
            $this->chargeOverage($userId, self::FEE_OVERAGE_SEARCH, 'search', "Phí vượt trội 1 lượt tìm kiếm (Lượt thứ $newCount)");
        }
    }

    /**
     * Xử lý khi tạo booking mới
     */
    public function logBooking(int $userId): void
    {
        $usage = $this->getCurrentUsage($userId);
        $newCount = $usage['bookings_count'] + 1;

        $this->db->execute(
            "UPDATE usage_tracking SET bookings_count = ? WHERE id = ?",
            [$newCount, $usage['id']]
        );

        if ($newCount > self::LIMIT_BOOKINGS) {
            $this->chargeOverage($userId, self::FEE_OVERAGE_BOOKING, 'booking', "Phí vượt trội 1 đặt chỗ (Lượt thứ $newCount)");
        }
    }

    /**
     * Xử lý khi xuất vé thành công
     */
    public function logTicketing(int $userId): void
    {
        $usage = $this->getCurrentUsage($userId);
        $newCount = $usage['tickets_count'] + 1;

        $this->db->execute(
            "UPDATE usage_tracking SET tickets_count = ? WHERE id = ?",
            [$newCount, $usage['id']]
        );

        if ($newCount > self::LIMIT_TICKETS) {
            $this->chargeOverage($userId, self::FEE_OVERAGE_TICKET, 'ticket', "Phí vượt trội 1 lần xuất vé (Lượt thứ $newCount)");
        }
    }

    /**
     * Trừ tiền tự động
     */
    private function chargeOverage(int $userId, float $amount, string $refType, string $description): void
    {
        $this->db->transaction(function(Database $db) use ($userId, $amount, $refType, $description) {
            // Trừ balance user
            $db->execute("UPDATE users SET balance = balance - ? WHERE id = ?", [$amount, $userId]);
            
            // Ghi nhận transaction
            $db->execute(
                "INSERT INTO transactions (user_id, amount, transaction_type, reference_type, description) VALUES (?, ?, 'overage_fee', ?, ?)",
                [$userId, $amount, $refType, $description]
            );
        });
    }
}
