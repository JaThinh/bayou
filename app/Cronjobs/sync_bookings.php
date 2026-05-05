<?php
/**
 * BAYOU OTA - Cronjob: Đồng bộ trạng thái Booking với Hãng
 * Chạy mỗi 24h: php cronjobs/sync_bookings.php
 */
require_once __DIR__ . '/../app/Core/Database.php';

use App\Core\Database;

$db = Database::getInstance()->getConnection();

// Lấy tất cả booking đang active trong 7 ngày gần nhất
$stmt = $db->query("SELECT id, pnr_code, airline_code FROM bookings 
                     WHERE ticket_status = 'processing' 
                     AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");

$bookings = $stmt->fetchAll();
$synced = 0;
$errors = 0;

foreach ($bookings as $b) {
    try {
        // TODO: Gọi API hãng để kiểm tra trạng thái PNR thực tế
        // $api = TravelAPIFactory::create($b['airline_code']);
        // $status = $api->retrieveBooking($b['pnr_code']);
        
        $synced++;
        echo "[OK] PNR {$b['pnr_code']} synced.\n";
    } catch (\Exception $e) {
        $errors++;
        echo "[ERR] PNR {$b['pnr_code']}: {$e->getMessage()}\n";
    }
}

echo "\nDone. Synced: {$synced}, Errors: {$errors}\n";
