<?php
// 0. TẮT CẢNH BÁO PHP
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
ini_set('display_errors', 0);

// 1. BỔ SUNG HEADERS (CORS & JSON)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// 2. NHẬN MÃ ĐẶT CHỖ (PNR)
$pnr = isset($_GET['pnr']) ? strtoupper($_GET['pnr']) : '';

if (empty($pnr)) {
    echo json_encode([
        "status" => "error",
        "message" => "Vui lòng cung cấp mã đặt chỗ (PNR)"
    ]);
    exit();
}

// =========================================================================
// 3. TRA CỨU TRẠNG THÁI CHUYẾN BAY (Flight Status API)
// =========================================================================
// Giả lập dữ liệu trạng thái
$statuses = ["Đúng giờ (On-time)", "Chậm chuyến (Delayed)", "Đã khởi hành (Departed)", "Hủy chuyến (Cancelled)"];
$randomStatus = $statuses[array_rand($statuses)];

$response = [
    "status" => "success",
    "pnr" => $pnr,
    "flight_info" => [
        "flight_number" => "VN245",
        "route" => "SGN -> HAN",
        "scheduled_time" => "08:00",
        "estimated_time" => ($randomStatus == "Delayed") ? "09:30" : "08:00",
        "current_status" => $randomStatus
    ],
    "last_updated" => date('Y-m-d H:i:s')
];

// Logic gọi NuSOAP 'GetBookingDetail' sẽ được triển khai tại đây
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
