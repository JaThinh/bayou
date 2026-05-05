<?php
declare(strict_types=1);

use App\Utils\CurrencyConverter;

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../app/Core/Database.php';
require_once __DIR__ . '/../../../app/Utils/CurrencyConverter.php';

try {
    $rate = CurrencyConverter::getLatestUsdRate();

    echo json_encode([
        'success' => true,
        'data' => [
            'from' => 'USD',
            'to' => 'VND',
            'rate' => $rate,
            'source' => 'Vietcombank',
            'rate_type' => 'sell',
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Không thể cập nhật tỷ giá USD/VND.',
    ], JSON_UNESCAPED_UNICODE);
}
