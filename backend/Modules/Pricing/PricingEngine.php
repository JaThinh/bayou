<?php
/**
 * Module: Pricing Rules Engine
 * Cấu hình Hoa hồng + Phí dịch vụ theo Hãng/Chặng/Hạng vé
 */
namespace App\Modules\Pricing;

use App\Core\Database;

class PricingEngine
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Tính giá cuối cùng cho booking dựa trên policy
     */
    public function calculate(string $airlineCode, string $origin, string $dest, string $cabin, float $baseFare, int $userId): array
    {
        // Tìm policy phù hợp nhất (ưu tiên: user > route > airline > default)
        $policy = $this->findBestPolicy($airlineCode, $origin, $dest, $cabin, $userId);

        $commission = 0;
        $serviceFee = 0;

        if ($policy) {
            $commission = ($baseFare * $policy['commission_pct'] / 100) + $policy['commission_fixed'];
            $serviceFee = ($baseFare * $policy['service_fee_pct'] / 100) + $policy['service_fee_fixed'];
        }

        return [
            'base_fare'    => $baseFare,
            'commission'   => round($commission),
            'service_fee'  => round($serviceFee),
            'selling_price'=> round($baseFare + $serviceFee),
            'policy_id'    => $policy['id'] ?? null,
            'policy_name'  => $policy['name'] ?? 'Mặc định',
        ];
    }

    private function findBestPolicy(string $airline, string $origin, string $dest, string $cabin, int $userId): ?array
    {
        $sql = "SELECT * FROM pricing_policies 
                WHERE status = 'active' 
                  AND (airline_code = :airline OR airline_code IS NULL)
                  AND (origin = :origin OR origin IS NULL)
                  AND (destination = :dest OR destination IS NULL)
                  AND (CURDATE() BETWEEN COALESCE(valid_from, '2000-01-01') AND COALESCE(valid_to, '2099-12-31'))
                ORDER BY priority DESC
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':airline' => $airline, ':origin' => $origin, ':dest' => $dest]);
        return $stmt->fetch() ?: null;
    }
}
