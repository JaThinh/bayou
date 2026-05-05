<?php
// Bắt đầu đệm đầu ra để tránh lỗi BOM/khoảng trắng làm hỏng headers
ob_start();

// 0. TẮT CẢNH BÁO PHP
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
ini_set('display_errors', 0);

// 1. BỔ SUNG HEADERS (CORS & JSON)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    ob_end_clean();
    exit();
}

// 2. NHẬN DỮ LIỆU ĐẶT VÉ (Thường là POST)
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    ob_end_clean();
    echo json_encode([
        "status" => "error",
        "message" => "Dữ liệu yêu cầu không hợp lệ"
    ]);
    exit();
}

/* 
Dữ liệu mong đợi từ Frontend:
{
    "flight_id": "VN245",
    "passengers": [
        {"name": "NGUYEN VAN A", "type": "ADT", "gender": true}
    ],
    "contact": {"phone": "0912345678", "email": "test@gmail.com"}
}
*/

// =========================================================================
// 3. XỬ LÝ ĐẶT VÉ (Booking API)
// =========================================================================
$response = [
    "status" => "success",
    "message" => "Yêu cầu đặt vé đã được ghi nhận thành công!",
    "pnr" => strtoupper(substr(md5(time()), 0, 6)), // Tạo mã PNR giả định
    "booking_id" => rand(100000, 999999),
    "details" => [
        "flight" => $data['flight_id'] ?? "VN245",
        "total_price" => $data['total_price'] ?? 1950000,
        "status" => "Pending (Chờ thanh toán)"
    ]
];

// Nếu có NuSOAP, ta có thể gọi method 'Book' tại đây
if (file_exists('nusoap-0.9.5/lib/nusoap.php')) {
    // Logic gọi $client->call('Book', $params) sẽ được triển khai khi có tài khoản thật
}

ob_end_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
