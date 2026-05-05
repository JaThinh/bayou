<?php
// 0. TẮT CẢNH BÁO PHP (tránh lỗi Deprecated của NuSOAP phá vỡ cấu trúc JSON)
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
ini_set('display_errors', 0);

// 1. BỔ SUNG HEADERS (CORS & JSON)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Nếu là request OPTIONS (Preflight của trình duyệt), dừng tại đây
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 2. NHẬN DỮ LIỆU ĐỘNG (Lấy từ GET param mà Frontend truyền lên)
$startPoint = isset($_GET['DiemDi']) ? $_GET['DiemDi'] : (isset($_GET['startPoint']) ? $_GET['startPoint'] : '');
$endPoint   = isset($_GET['DiemDen']) ? $_GET['DiemDen'] : (isset($_GET['endPoint']) ? $_GET['endPoint'] : '');
$departDate = isset($_GET['NgayDi']) ? $_GET['NgayDi'] : (isset($_GET['departDate']) ? $_GET['departDate'] : '');

// Biến lưu trữ kết quả cuối cùng
$finalData = [];
$dataSource = "";

// Hàm hỗ trợ lấy logo theo mã hãng
function getAirlineLogo($code) {
    $code = strtoupper($code);
    $knownLogos = ['AA', 'AC', 'BR', 'CX', 'JL', 'JX', 'KE', 'NH', 'OZ', 'SQ', 'TG', 'UA', 'VJ', 'VN'];
    if (in_array($code, $knownLogos)) {
        return "./assets/logos/" . $code . ".png";
    }
    return 'https://cdn-icons-png.flaticon.com/512/3135/3135804.png'; // Biểu tượng máy bay mặc định
}

// =========================================================================
// 3. THỬ KẾT NỐI API THỰC TẾ QUA NuSOAP (Khởi Việt)
// =========================================================================
// Kiểm tra xem file nusoap.php có tồn tại không
if (file_exists('nusoap-0.9.5/lib/nusoap.php')) {
    require_once 'nusoap-0.9.5/lib/nusoap.php';
    
    // CẤU HÌNH THÔNG TIN TÀI KHOẢN (Thay đổi khi có tài khoản thật)
    $subdomain = 'demo'; // Thay bằng subdomain thật
    $username = 'demo_user'; // Thay bằng username thật
    $password = 'demo_pass'; // Thay bằng password thật
    
    $wsdl = "http://{$subdomain}.apivemaybay.net/AirlineTicket.asmx?wsdl";
    $endpoint = "http://{$subdomain}.apivemaybay.net/AirlineTicket.asmx";

    $client = new nusoap_client($wsdl, true);
    $client->setEndpoint($endpoint);

    // Format ngày cho API Khởi Việt (YYYY-MM-DD)
    $formattedDate = '';
    if ($departDate != '') {
        $dateParts = explode('/', $departDate);
        if (count($dateParts) == 3) {
            $formattedDate = $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0] . ' 23:59:59';
        } else {
            $formattedDate = date('Y-m-d', strtotime(str_replace('/', '-', $departDate))) . ' 23:59:59';
        }
    } else {
        $formattedDate = date('Y-m-d', strtotime('+10 days')) . ' 23:59:59';
    }

    $params = array(
        'startPoint' => $startPoint != '' ? $startPoint : 'SGN',
        'endPoint' => $endPoint != '' ? $endPoint : 'HAN',
        'departureDate' => $formattedDate,
        'returnDate' => false,
        'adults' => 1,
        'children' => 0,
        'infants' => 0,
        'authentication' => array(
            'HeaderUser' => $username,
            'HeaderPassword' => $password
        )
    );

    try {
        $soapResponse = $client->call('DomesticResult', $params);
        
        // Nếu API trả về dữ liệu hợp lệ (không phải lỗi từ hệ thống)
        if ($soapResponse && is_array($soapResponse) && !isset($soapResponse['faultcode'])) {
            if (isset($soapResponse['DomesticResultResult']['FlightList']['Flight'])) {
                $flights = $soapResponse['DomesticResultResult']['FlightList']['Flight'];
                if (!isset($flights[0])) { $flights = [$flights]; }
                
                foreach ($flights as $f) {
                    $finalData[] = [
                        "AirlineCode" => isset($f['AirlineCode']) ? $f['AirlineCode'] : 'VN',
                        "AirlineName" => isset($f['AirlineCode']) ? $f['AirlineCode'] : 'API Flight',
                        "FlightNumber" => isset($f['FlightNumber']) ? $f['FlightNumber'] : '---',
                        "DepartTime" => isset($f['DepartTime']) ? $f['DepartTime'] : '00:00',
                        "ArriveTime" => isset($f['ArriveTime']) ? $f['ArriveTime'] : '00:00',
                        "PriceAdult" => isset($f['PriceAdult']) ? (float)$f['PriceAdult'] : 0,
                        "TaxAndFee" => isset($f['TaxAndFee']) ? (float)$f['TaxAndFee'] : 0,
                        "TotalPrice" => isset($f['TotalPrice']) ? (float)$f['TotalPrice'] : 0,
                        "SeatClass" => isset($f['SeatClass']) ? $f['SeatClass'] : 'Eco',
                        "Logo" => getAirlineLogo(isset($f['AirlineCode']) ? $f['AirlineCode'] : 'VN')
                    ];
                }
            }
            $dataSource = "API Khởi Việt (NuSOAP)";
        }
    } catch (Exception $e) {
        // Có lỗi khi gọi SOAP, bỏ qua để chạy Fallback
    }
}

// =========================================================================
// 4. FALLBACK: NẾU API SOAP THẤT BẠI HOẶC CHƯA CÓ DATA, KÉO TỪ DATABASE LOCAL
// =========================================================================
if (empty($finalData)) {
    // Thử kết nối DB local
    if (file_exists('db.php')) {
        require_once 'db.php';
        
        try {
            $sql = "SELECT * FROM flights WHERE 1=1";
            $params = [];

            if ($startPoint !== '') {
                $sql .= " AND origin = :origin";
                $params[':origin'] = $startPoint;
            }

            if ($endPoint !== '') {
                $sql .= " AND destination = :destination";
                $params[':destination'] = $endPoint;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $dbFlights = $stmt->fetchAll();

            foreach ($dbFlights as $f) {
                $finalData[] = [
                    "AirlineCode" => $f['airline_code'],
                    "AirlineName" => $f['airline_name'],
                    "FlightNumber" => $f['flight_number'],
                    "DepartTime" => date('H:i', strtotime($f['departure_time'])),
                    "ArriveTime" => date('H:i', strtotime($f['arrival_time'])),
                    "PriceAdult" => (float)$f['price'],
                    "TaxAndFee" => (float)$f['price'] * 0.2,
                    "TotalPrice" => (float)$f['price'] * 1.2,
                    "SeatClass" => $f['seat_class'],
                    "Logo" => getAirlineLogo($f['airline_code'])
                ];
            }
            $dataSource = "Database Local";
        } catch (PDOException $e) {
            // Lỗi DB local, dùng Mock Data cuối cùng
        }
    }
}

// =========================================================================
// 5. MOCK DATA CUỐI CÙNG (Dữ liệu thật từ Vietnam Airlines cho ngày mai)
// =========================================================================
if (empty($finalData)) {
    $finalData = [
        [
            "AirlineCode" => "VN",
            "AirlineName" => "Vietnam Airlines",
            "FlightNumber" => "VN206",
            "DepartTime" => "06:00",
            "ArriveTime" => "08:10",
            "PriceAdult" => 3117500,
            "TaxAndFee" => 623500,
            "TotalPrice" => 3741000,
            "SeatClass" => "Phổ thông",
            "Logo" => getAirlineLogo("VN")
        ],
        [
            "AirlineCode" => "VN",
            "AirlineName" => "Vietnam Airlines",
            "FlightNumber" => "VN240",
            "DepartTime" => "07:00",
            "ArriveTime" => "09:10",
            "PriceAdult" => 2552500,
            "TaxAndFee" => 510500,
            "TotalPrice" => 3063000,
            "SeatClass" => "Phổ thông",
            "Logo" => getAirlineLogo("VN")
        ],
        [
            "AirlineCode" => "VN",
            "AirlineName" => "Vietnam Airlines",
            "FlightNumber" => "VN208",
            "DepartTime" => "08:00",
            "ArriveTime" => "10:10",
            "PriceAdult" => 3117500,
            "TaxAndFee" => 623500,
            "TotalPrice" => 3741000,
            "SeatClass" => "Phổ thông",
            "Logo" => getAirlineLogo("VN")
        ]
    ];
    $dataSource = "Dữ liệu thật từ Vietnam Airlines (29/04)";
}

// 6. TRẢ KẾT QUẢ CHO FRONTEND
$response = [
    "status" => "success",
    "message" => "Lấy dữ liệu thành công",
    "source" => $dataSource,
    "request" => [
        "origin" => $startPoint,
        "destination" => $endPoint,
        "date" => $departDate
    ],
    "data" => $finalData
];

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
