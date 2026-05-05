<?php
namespace App\Middlewares;

use App\Modules\Pricing\SaaSBillingService;

class UsageLimitMiddleware
{
    private SaaSBillingService $billingService;

    public function __construct()
    {
        $this->billingService = new SaaSBillingService();
    }

    /**
     * Hook vào trước khi Controller xử lý Request Tìm kiếm
     * Trả về true nếu hợp lệ, ngược lại throw Exception hoặc redirect
     */
    public function handleSearchRequest(int $userId): bool
    {
        // 1. Kiểm tra IP Blacklist hoặc Bot Prevention (Rate limit)
        if ($this->isBotDetected($_SERVER['REMOTE_ADDR'])) {
            throw new \Exception("Access Denied: Bị chặn bởi hệ thống Bot Prevention.");
        }

        // 2. Ghi nhận lượt tìm kiếm (Sẽ tự động trừ tiền nếu vượt mốc 400,000)
        try {
            $this->billingService->logSearch($userId);
            return true;
        } catch (\Exception $e) {
            // Có thể xảy ra lỗi nếu số dư không đủ để trừ phí vượt trội
            error_log("Billing Error (Search): " . $e->getMessage());
            throw new \Exception("Tài khoản của bạn không đủ số dư để thanh toán phí tìm kiếm vượt trội (100 VND/lượt).");
        }
    }

    /**
     * Hook vào quá trình tạo Booking
     */
    public function handleBookingRequest(int $userId): bool
    {
        try {
            $this->billingService->logBooking($userId);
            return true;
        } catch (\Exception $e) {
            throw new \Exception("Tài khoản không đủ số dư thanh toán phí đặt chỗ vượt trội (2,000 VND/lượt).");
        }
    }

    /**
     * Hook vào quá trình xuất vé (Ticketing)
     */
    public function handleTicketingRequest(int $userId): bool
    {
        try {
            $this->billingService->logTicketing($userId);
            return true;
        } catch (\Exception $e) {
            throw new \Exception("Tài khoản không đủ số dư thanh toán phí xuất vé vượt trội (5,000 VND/lượt).");
        }
    }

    /**
     * Mô phỏng kiểm tra Bot (Dựa vào RateLimit hoặc IP Blacklist)
     */
    private function isBotDetected(string $ip): bool
    {
        // TODO: Kết nối bảng `ip_blacklist` và `rate_limits` để kiểm tra
        return false;
    }
}
