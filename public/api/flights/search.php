<?php
declare(strict_types=1);

/**
 * Bayou OTA - Flight Search JSON API
 *
 * Đầu vào (GET hoặc POST):
 *   - from / origin / originSkyId        (bắt buộc, ví dụ: SGN)
 *   - to / destination / destinationSkyId (bắt buộc, ví dụ: HAN)
 *   - date                                (bắt buộc, định dạng YYYY-MM-DD)
 *   - originEntityId, destinationEntityId (tùy chọn)
 *   - adults, children, infants           (tùy chọn)
 *   - currency, market                    (tùy chọn, mặc định VND/VN)
 *
 * Đầu ra: JSON với cấu trúc
 *   { success, source, request: {...}, flights: [ ... ] }
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');

// Cho phép gọi cross-origin trong môi trường dev (frontend chạy port khác).
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$payload = $_GET;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $rawBody = file_get_contents('php://input');
        $jsonBody = json_decode((string) $rawBody, true);
        if (is_array($jsonBody)) {
            $payload = array_merge($payload, $jsonBody);
        }
    } else {
        $payload = array_merge($payload, $_POST);
    }
}

$origin = strtoupper(trim((string) (
    $payload['from']
    ?? $payload['origin']
    ?? $payload['originSkyId']
    ?? ''
)));
$destination = strtoupper(trim((string) (
    $payload['to']
    ?? $payload['destination']
    ?? $payload['destinationSkyId']
    ?? ''
)));
$date = trim((string) ($payload['date'] ?? $payload['departDate'] ?? ''));

$adults = max(1, (int) ($payload['adults'] ?? 1));
$children = max(0, (int) ($payload['children'] ?? 0));
$infants = max(0, (int) ($payload['infants'] ?? 0));
$currency = strtoupper((string) ($payload['currency'] ?? 'VND'));
$market = strtoupper((string) ($payload['market'] ?? 'VN'));

if ($origin === '' || $destination === '' || $date === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng cung cấp đầy đủ điểm đi (from), điểm đến (to) và ngày bay (date).',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Chuẩn hoá ngày về định dạng YYYY-MM-DD (chấp nhận DD/MM/YYYY).
$normalizedDate = normalizeDate($date);
if ($normalizedDate === null) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Ngày bay không hợp lệ. Sử dụng định dạng YYYY-MM-DD.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
$date = $normalizedDate;

// Map sân bay phổ biến -> entityId của Skyscanner.
$entityMap = [
    'SGN' => '95673379',  // TP HCM
    'HAN' => '128668079', // Hà Nội
    'DAD' => '129049050', // Đà Nẵng
    'CXR' => '95673380',  // Nha Trang
    'PQC' => '128668080', // Phú Quốc
    'VCA' => '95673381',  // Cần Thơ
    'DLI' => '128668081', // Đà Lạt
    'HPH' => '95673382',  // Hải Phòng
    'BKK' => '95565051',  // Bangkok
    'ICN' => '128668077', // Seoul
    'NRT' => '128668082', // Tokyo Narita
    'HND' => '128668083', // Tokyo Haneda
    'SIN' => '95565064',  // Singapore
    'CDG' => '128668078', // Paris CDG
    'LAX' => '95565062',  // Los Angeles
    'JFK' => '128668076', // New York JFK
];

$originEntityId = (string) ($payload['originEntityId'] ?? $entityMap[$origin] ?? '');
$destinationEntityId = (string) ($payload['destinationEntityId'] ?? $entityMap[$destination] ?? '');

$request = [
    'origin' => $origin,
    'destination' => $destination,
    'date' => $date,
    'adults' => $adults,
    'children' => $children,
    'infants' => $infants,
    'currency' => $currency,
    'market' => $market,
];

$rapidApiKey = getRapidApiKey();
$apiResult = null;
$apiError = null;
$attempts = 0;

// Skyscanner RapidAPI thường gặp lỗi 502 thoáng qua. Thử lại tối đa 2 lần
// với độ trễ ngắn để hấp thụ những cú nhỡ tạm thời trước khi fallback.
if ($rapidApiKey !== '' && $originEntityId !== '' && $destinationEntityId !== '') {
    $maxAttempts = 3;
    while ($attempts < $maxAttempts && $apiResult === null) {
        $attempts++;
        try {
            $apiResult = callSkyscanner(
                $rapidApiKey,
                $origin,
                $originEntityId,
                $destination,
                $destinationEntityId,
                $date,
                $adults,
                $currency,
                $market
            );
            $apiError = null;
        } catch (\Throwable $e) {
            $apiError = $e->getMessage();
            $isTransient = stripos($apiError, '502') !== false
                || stripos($apiError, '503') !== false
                || stripos($apiError, '504') !== false
                || stripos($apiError, 'timeout') !== false
                || stripos($apiError, 'cURL error') !== false;
            if (!$isTransient || $attempts >= $maxAttempts) {
                break;
            }
            usleep(800000); // 0.8s rồi thử lại
        }
    }
}

// Tính dải 7 ngày liền kề (±3 ngày) để hiển thị date carousel với giá ước tính.
$dateRange = buildDateRange($date, $origin, $destination);

if (is_array($apiResult) && !empty($apiResult)) {
    echo json_encode([
        'success' => true,
        'source' => 'Skyscanner via RapidAPI',
        'attempts' => $attempts,
        'request' => $request,
        'dateRange' => $dateRange,
        'flights' => $apiResult,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Fallback: trả về dữ liệu mẫu để giao diện vẫn hiển thị được khi không có API key
// hoặc khi nhà cung cấp gặp sự cố. Phân loại lý do để frontend hiển thị banner phù hợp.
$fallbackReason = $apiError ?? 'No upstream provider available';
$isUpstreamFailure = $apiError !== null;

echo json_encode([
    'success' => true,
    'source' => 'Bayou Mock Data',
    'fallback_reason' => $fallbackReason,
    'fallback_kind' => $isUpstreamFailure ? 'upstream_error' : 'no_provider',
    'fallback_message' => $isUpstreamFailure
        ? 'Hệ thống đặt vé đang gặp sự cố tạm thời. Đây là kết quả tham khảo, giá có thể thay đổi.'
        : 'Đang hiển thị dữ liệu mẫu do chưa cấu hình API thực tế.',
    'attempts' => $attempts,
    'request' => $request,
    'dateRange' => $dateRange,
    'flights' => buildMockFlights($origin, $destination, $date),
], JSON_UNESCAPED_UNICODE);

// =============================================================================
// Helper functions
// =============================================================================

function normalizeDate(string $value): ?string
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
        return $value;
    }

    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $matches) === 1) {
        return sprintf('%s-%s-%s', $matches[3], $matches[2], $matches[1]);
    }

    $timestamp = strtotime($value);
    return $timestamp !== false ? date('Y-m-d', $timestamp) : null;
}

function getRapidApiKey(): string
{
    $candidates = [
        getenv('RAPIDAPI_KEY'),
        getenv('SKYSCANNER_RAPIDAPI_KEY'),
        $_ENV['RAPIDAPI_KEY'] ?? null,
        $_SERVER['RAPIDAPI_KEY'] ?? null,
    ];

    foreach ($candidates as $candidate) {
        if (is_string($candidate) && $candidate !== '') {
            return $candidate;
        }
    }

    $envFile = __DIR__ . '/../../../.env';
    if (is_readable($envFile)) {
        $contents = file_get_contents($envFile);
        if (is_string($contents)
            && preg_match('/^RAPIDAPI_KEY\s*=\s*(.+)$/mi', $contents, $matches) === 1
        ) {
            return trim($matches[1], " \t\"'");
        }
    }

    return '';
}

function callSkyscanner(
    string $apiKey,
    string $originSkyId,
    string $originEntityId,
    string $destinationSkyId,
    string $destinationEntityId,
    string $date,
    int $adults,
    string $currency,
    string $market
): array {
    $query = http_build_query([
        'originSkyId' => $originSkyId,
        'originEntityId' => $originEntityId,
        'destinationSkyId' => $destinationSkyId,
        'destinationEntityId' => $destinationEntityId,
        'date' => $date,
        'adults' => $adults,
        'currency' => $currency,
        'market' => $market,
    ]);

    $url = 'https://skyscanner-flights-travel-api.p.rapidapi.com/flights/searchFlights?' . $query;

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => [
            'X-RapidAPI-Host: skyscanner-flights-travel-api.p.rapidapi.com',
            'X-RapidAPI-Key: ' . $apiKey,
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($err !== '') {
        throw new \RuntimeException('cURL error: ' . $err);
    }

    if ($httpCode >= 400) {
        throw new \RuntimeException('Upstream HTTP ' . $httpCode);
    }

    $data = json_decode((string) $response, true);
    if (!is_array($data)) {
        throw new \RuntimeException('Upstream returned non-JSON payload');
    }

    $itineraries = $data['data']['itineraries']
        ?? $data['itineraries']
        ?? $data['data']['results']['itineraries']
        ?? [];

    if (!is_array($itineraries)) {
        return [];
    }

    return array_map(
        static fn (array $itinerary): array => mapItinerary($itinerary, $originSkyId, $destinationSkyId),
        array_values($itineraries)
    );
}

function mapItinerary(array $itinerary, string $originSkyId, string $destinationSkyId): array
{
    $leg = $itinerary['legs'][0] ?? $itinerary['leg'] ?? [];
    $carrier = [];
    if (isset($leg['carriers']) && is_array($leg['carriers'])) {
        $carrier = $leg['carriers']['marketing'][0] ?? $leg['carriers'][0] ?? [];
    }

    $itineraryText = json_encode($itinerary) ?: '';
    $airline = (string) ($carrier['name'] ?? '');
    $airlineCode = (string) ($carrier['alternateId'] ?? $carrier['iata'] ?? '');
    $logo = (string) ($carrier['logoUrl'] ?? $carrier['logo'] ?? '');

    if ($airline === '' || $airline === 'Unknown Airline') {
        if (str_contains($itineraryText, '-31703') || str_contains($itineraryText, '/viet/')) {
            $airline = 'Vietnam Airlines';
            $airlineCode = $airlineCode !== '' ? $airlineCode : 'VN';
            $logo = $logo !== '' ? $logo : 'https://logos.skyscnr.com/images/airlines/favicon/VN.png';
        } elseif (str_contains($itineraryText, '-31705') || str_contains($itineraryText, '/jtuk/')) {
            $airline = 'VietJet Air';
            $airlineCode = $airlineCode !== '' ? $airlineCode : 'VJ';
            $logo = $logo !== '' ? $logo : 'https://logos.skyscnr.com/images/airlines/favicon/4V.png';
        } else {
            $airline = $airline !== '' ? $airline : 'Unknown Airline';
        }
    }

    $departure = (string) ($leg['departure'] ?? $leg['departureTime'] ?? '');
    $arrival = (string) ($leg['arrival'] ?? $leg['arrivalTime'] ?? '');
    $durationMinutes = (int) ($leg['durationInMinutes'] ?? $leg['duration'] ?? 0);
    $stopCount = (int) ($leg['stopCount'] ?? count($leg['stops'] ?? []));
    $flightNumber = (string) (
        $leg['segments'][0]['flightNumber']
        ?? $leg['flightNumber']
        ?? ''
    );

    $priceRaw = $itinerary['price']['raw']
        ?? $itinerary['pricingOptions'][0]['price']['raw']
        ?? null;
    $priceFormatted = $itinerary['price']['formatted']
        ?? $itinerary['pricingOptions'][0]['price']['formatted']
        ?? null;

    return [
        'AirlineName' => $airline,
        'AirlineCode' => $airlineCode,
        'FlightNumber' => $flightNumber !== '' ? $flightNumber : ($airlineCode . '–'),
        'Origin' => (string) ($leg['origin']['displayCode'] ?? $originSkyId),
        'Destination' => (string) ($leg['destination']['displayCode'] ?? $destinationSkyId),
        'DepartTime' => $departure,
        'ArriveTime' => $arrival,
        'DurationMinutes' => $durationMinutes,
        'Stops' => $stopCount,
        'Price' => is_numeric($priceRaw) ? (float) $priceRaw : null,
        'PriceDisplay' => $priceFormatted ?? (is_numeric($priceRaw) ? number_format((float) $priceRaw, 0, '.', ',') : null),
        'Logo' => $logo,
    ];
}

function buildMockFlights(string $origin, string $destination, string $date): array
{
    $base = [
        [
            'AirlineName' => 'Vietnam Airlines',
            'AirlineCode' => 'VN',
            'FlightNumber' => 'VN217',
            'DepartHour' => '06:00',
            'ArriveHour' => '08:15',
            'Price' => 1850000,
            'Logo' => '/assets/logos/airline-vn.png',
            'Aircraft' => 'Airbus A321',
            'SeatsLeft' => 9,
            'Amenities' => ['wifi', 'meal', 'usb'],
        ],
        [
            'AirlineName' => 'VietJet Air',
            'AirlineCode' => 'VJ',
            'FlightNumber' => 'VJ124',
            'DepartHour' => '08:30',
            'ArriveHour' => '10:45',
            'Price' => 1290000,
            'Logo' => '/assets/logos/airline-vj.png',
            'Aircraft' => 'Airbus A320',
            'SeatsLeft' => 22,
            'Amenities' => ['usb'],
        ],
        [
            'AirlineName' => 'Bamboo Airways',
            'AirlineCode' => 'QH',
            'FlightNumber' => 'QH202',
            'DepartHour' => '11:15',
            'ArriveHour' => '13:30',
            'Price' => 1620000,
            'Logo' => '/assets/logos/airline-qh.png',
            'Aircraft' => 'Airbus A321neo',
            'SeatsLeft' => 5,
            'Amenities' => ['wifi', 'meal', 'usb', 'entertainment'],
        ],
        [
            'AirlineName' => 'Vietravel Airlines',
            'AirlineCode' => 'VU',
            'FlightNumber' => 'VU810',
            'DepartHour' => '14:40',
            'ArriveHour' => '16:55',
            'Price' => 1490000,
            'Logo' => '/assets/logos/airline-vu.png',
            'Aircraft' => 'Airbus A321',
            'SeatsLeft' => 14,
            'Amenities' => ['meal', 'usb'],
        ],
        [
            'AirlineName' => 'Vietnam Airlines',
            'AirlineCode' => 'VN',
            'FlightNumber' => 'VN271',
            'DepartHour' => '17:30',
            'ArriveHour' => '19:45',
            'Price' => 2120000,
            'Logo' => '/assets/logos/airline-vn.png',
            'Aircraft' => 'Boeing 787-9',
            'SeatsLeft' => 3,
            'Amenities' => ['wifi', 'meal', 'usb', 'entertainment'],
        ],
        [
            'AirlineName' => 'VietJet Air',
            'AirlineCode' => 'VJ',
            'FlightNumber' => 'VJ186',
            'DepartHour' => '20:15',
            'ArriveHour' => '22:30',
            'Price' => 1390000,
            'Logo' => '/assets/logos/airline-vj.png',
            'Aircraft' => 'Airbus A321',
            'SeatsLeft' => 17,
            'Amenities' => ['usb'],
        ],
    ];

    $flights = [];
    foreach ($base as $row) {
        $flights[] = [
            'AirlineName' => $row['AirlineName'],
            'AirlineCode' => $row['AirlineCode'],
            'FlightNumber' => $row['FlightNumber'],
            'Origin' => $origin,
            'Destination' => $destination,
            'DepartTime' => $date . 'T' . $row['DepartHour'] . ':00',
            'ArriveTime' => $date . 'T' . $row['ArriveHour'] . ':00',
            'DurationMinutes' => 135,
            'Stops' => 0,
            'Price' => $row['Price'],
            'PriceDisplay' => number_format($row['Price'], 0, ',', '.') . ' VND',
            'Logo' => $row['Logo'],
            'Aircraft' => $row['Aircraft'],
            'SeatsLeft' => $row['SeatsLeft'],
            'Amenities' => $row['Amenities'],
        ];
    }

    return $flights;
}

/**
 * Sinh dải 7 ngày (mặc định ±3 ngày quanh ngày tìm) kèm "giá thấp nhất ước tính"
 * để frontend hiển thị date carousel — cho phép user nhanh chóng so sánh giá
 * giữa các ngày liền kề và bấm chọn ngày khác để tìm lại.
 *
 * @return array<int, array{date: string, lowestPrice: int, displayPrice: string, dayLabel: string}>
 */
function buildDateRange(string $centerDate, string $origin, string $destination): array
{
    $center = DateTimeImmutable::createFromFormat('Y-m-d', $centerDate)
        ?: new DateTimeImmutable($centerDate);

    // Hash deterministic theo route+date để giá ổn định giữa các lần load.
    $seed = crc32($origin . $destination . $centerDate);
    mt_srand($seed);

    $vietnameseWeekday = [
        0 => 'CN',
        1 => 'T2',
        2 => 'T3',
        3 => 'T4',
        4 => 'T5',
        5 => 'T6',
        6 => 'T7',
    ];

    $range = [];
    for ($offset = -3; $offset <= 3; $offset++) {
        $day = $center->modify(($offset >= 0 ? '+' : '') . $offset . ' day');
        // Giá biến thiên trong khoảng 1.05Tr ~ 2.5Tr, ngày tìm chính lấy giá thấp.
        $base = $offset === 0 ? 1190000 : (1190000 + mt_rand(0, 1310000));
        $base = (int) (round($base / 10000) * 10000);

        $range[] = [
            'date' => $day->format('Y-m-d'),
            'dayLabel' => $vietnameseWeekday[(int) $day->format('w')],
            'dayShort' => $day->format('d/m'),
            'lowestPrice' => $base,
            'displayPrice' => 'Từ ' . formatVndShort($base),
            'isCenter' => $offset === 0,
        ];
    }

    mt_srand(); // reset seed
    return $range;
}

function formatVndShort(int $vnd): string
{
    if ($vnd >= 1000000) {
        $value = $vnd / 1000000;
        return rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',') . 'Tr';
    }
    if ($vnd >= 1000) {
        return number_format(round($vnd / 1000), 0, ',', '.') . 'K';
    }
    return number_format($vnd, 0, ',', '.');
}
