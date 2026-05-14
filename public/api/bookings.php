<?php
/**
 * public/api/bookings.php
 * REST endpoint: POST = tạo booking | GET = tra cứu
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once dirname(__DIR__, 2) . '/backend/Config/database.php';
require_once dirname(__DIR__, 2) . '/backend/Database/DB.php';
require_once dirname(__DIR__, 2) . '/backend/Models/Booking.php';

use Backend\Models\Booking;

$method = $_SERVER['REQUEST_METHOD'];
$body   = json_decode(file_get_contents('php://input'), true) ?: [];
$params = array_merge($_GET, $_POST, $body);

try {
    if ($method === 'POST') {
        // Tạo booking mới
        $required = ['flight_number','airline','origin','destination',
                     'depart_at','price_amount','passenger_name','contact_phone'];
        foreach ($required as $f) {
            if (empty($params[$f])) {
                echo json_encode(['success'=>false,'error'=>"Thiếu trường: $f"]);
                exit;
            }
        }
        $booking = Booking::create($params);
        echo json_encode(['success'=>true,'data'=>$booking,'booking_ref'=>$booking['booking_ref']??''],
                         JSON_UNESCAPED_UNICODE);

    } elseif ($method === 'GET') {
        // Tra cứu
        $ref   = trim($params['ref']   ?? '');
        $phone = trim($params['phone'] ?? '');

        if (!$ref || !$phone) {
            echo json_encode(['success'=>false,'error'=>'Cần nhập booking_ref và phone']);
            exit;
        }
        $booking = Booking::lookup($ref, $phone);
        if (!$booking) {
            echo json_encode(['success'=>false,'error'=>'Không tìm thấy đặt chỗ']);
            exit;
        }
        echo json_encode(['success'=>true,'data'=>$booking], JSON_UNESCAPED_UNICODE);

    } else {
        http_response_code(405);
        echo json_encode(['success'=>false,'error'=>'Method not allowed']);
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
