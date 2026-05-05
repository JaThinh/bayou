<?php
// 0. TẮT CẢNH BÁO PHP
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
ini_set('display_errors', 0);

// 1. BỔ SUNG HEADERS (CORS & JSON)
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

// =========================================================================
// 2. CUNG CẤP THÔNG TIN HÃNG HÀNG KHÔNG (Airline API)
// =========================================================================
$airlines = [
    [
        "code" => "VN",
        "name" => "Vietnam Airlines",
        "country" => "Việt Nam",
        "type" => "Full Service",
        "description" => "Hãng hàng không quốc gia Việt Nam."
    ],
    [
        "code" => "VJ",
        "name" => "VietJet Air",
        "country" => "Việt Nam",
        "type" => "Low Cost",
        "description" => "Hãng hàng không giá rẻ hàng đầu Việt Nam."
    ],
    [
        "code" => "QH",
        "name" => "Bamboo Airways",
        "country" => "Việt Nam",
        "type" => "Hybrid",
        "description" => "Hãng hàng không của sự hiếu khách."
    ]
];

echo json_encode([
    "status" => "success",
    "data" => $airlines
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
