<?php
/**
 * Skyscanner Scraper Service
 * Lấy giá vé từ Skyscanner thông qua Reverse Engineering API
 *
 * Skyscanner cung cấp Partner API chính thức (cần đăng ký),
 * nhưng cũng có thể dùng endpoint nội bộ phát hiện qua F12 DevTools:
 *   https://www.skyscanner.net/g/conductor/v1/fps3/search/
 *
 * Endpoint này dùng session-based token, flow gồm 2 bước:
 *   1. POST /create  → lấy sessionToken
 *   2. GET  /poll/{sessionToken} → lấy kết quả vé
 */

namespace App\Services;

class SkyscannerScraper
{
    // ----------------------------------------------------------------
    // Skyscanner RapidAPI — https://rapidapi.com/skyscanner/api/skyscanner-api
    // API key thực tế lấy từ project (test_flight.php)
    // Host: skyscanner-flights-travel-api.p.rapidapi.com
    // ----------------------------------------------------------------
    private string $rapidApiKey  = '38ab0610a4msh9ca0345cabfdc62p189f00jsn011fbc7dfea2';
    private string $rapidApiHost = 'skyscanner-flights-travel-api.p.rapidapi.com';

    // Endpoint nội bộ (reverse-engineered từ F12)
    private string $internalBase = 'https://www.skyscanner.net/g/conductor/v1/fps3/search/';

    /**
     * Tìm chuyến bay — tự động chọn phương thức: RapidAPI hoặc internal scrape
     */
    public function searchFlights(string $origin, string $dest, string $date, int $adults = 1): array
    {
        if (!empty($this->rapidApiKey)) {
            return $this->searchViaRapidAPI($origin, $dest, $date, $adults);
        }
        return $this->searchViaInternal($origin, $dest, $date, $adults);
    }

    // ================================================================
    // PHƯƠNG THỨC 1: RapidAPI (chính thức, ổn định)
    // ================================================================
    private function searchViaRapidAPI(string $origin, string $dest, string $date, int $adults): array
    {
        // Endpoint chính xác theo test_flights.php đã test thực tế
        $query = http_build_query([
            'originSkyId'          => $origin,            // SGN
            'originEntityId'       => $this->getEntityId($origin),
            'destinationSkyId'     => $dest,              // HAN
            'destinationEntityId'  => $this->getEntityId($dest),
            'date'                 => $date,              // YYYY-MM-DD
            'adults'               => $adults,
            'currency'             => 'VND',
            'market'               => 'VN',
        ]);

        $url = "https://{$this->rapidApiHost}/flights/searchFlights?{$query}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER     => [
                'X-RapidAPI-Host: ' . $this->rapidApiHost,
                'X-RapidAPI-Key: '  . $this->rapidApiKey,
            ],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return ['success' => false, 'source' => 'skyscanner', 'error' => "RapidAPI HTTP $httpCode", 'data' => []];
        }

        $json = json_decode($response, true);
        return $this->parseRapidAPIResponse($json, $origin, $dest, $date);
    }

    private function parseRapidAPIResponse(?array $json, string $origin, string $dest, string $date): array
    {
        // Hỗ trợ cả 2 cấu trúc response của skyscanner-flights-travel-api
        $itineraries = $json['data']['itineraries']
                    ?? $json['itineraries']
                    ?? $json['data']['results']['itineraries']
                    ?? [];

        if (empty($itineraries)) {
            return ['success' => false, 'source' => 'skyscanner', 'error' => 'No results', 'data' => []];
        }

        $flights = [];
        foreach ($itineraries as $it) {
            $leg     = $it['legs'][0] ?? $it['leg'] ?? [];
            $carrier = $leg['carriers']['marketing'][0] ?? $leg['carriers'][0] ?? [];
            $seg     = $leg['segments'][0] ?? [];

            // Giá: thử nhiều path
            $priceRaw = $it['price']['raw']
                     ?? $it['price']['formatted']
                     ?? $it['pricingOptions'][0]['price']['raw']
                     ?? null;

            if (!$priceRaw) continue;

            // Nếu giá là string (vd "1,234,000") thì parse số
            $priceAmt = is_numeric($priceRaw) ? (int)$priceRaw : (int)preg_replace('/[^0-9]/', '', $priceRaw);
            if ($priceAmt === 0) continue;

            // Tên hãng — fallback sang detect bằng text
            $airline = $carrier['name'] ?? $carrier['alternateId'] ?? '';
            $logo    = $carrier['logoUrl'] ?? $carrier['logo'] ?? '';

            if (empty($airline) || $airline === 'Unknown Airline') {
                $text = json_encode($it);
                if (str_contains($text, 'vietnam') || str_contains($text, '/viet/')) {
                    $airline = 'Vietnam Airlines';
                    $logo    = 'https://logos.skyscnr.com/images/airlines/favicon/VN.png';
                } elseif (str_contains($text, 'vietjet') || str_contains($text, 'VJ')) {
                    $airline = 'VietJet Air';
                    $logo    = 'https://logos.skyscnr.com/images/airlines/favicon/VJ.png';
                } elseif (str_contains($text, 'bamboo') || str_contains($text, 'QH')) {
                    $airline = 'Bamboo Airways';
                    $logo    = 'https://logos.skyscnr.com/images/airlines/favicon/QH.png';
                }
            }

            $flights[] = [
                'airline'       => $airline ?: 'Unknown',
                'logo'          => $logo,
                'flightNumber'  => ($seg['marketingCarrier']['alternateId'] ?? '') . ($seg['flightNumber'] ?? ''),
                'departureTime' => substr($leg['departure'] ?? '', 11, 5),
                'arrivalTime'   => substr($leg['arrival']   ?? '', 11, 5),
                'duration'      => $this->formatDuration($leg['durationInMinutes'] ?? 0),
                'stops'         => ($leg['stopCount'] ?? 0) === 0 ? 'Bay thẳng' : ($leg['stopCount'] . ' điểm dừng'),
                'price'         => [
                    'amount'    => $priceAmt,
                    'formatted' => number_format($priceAmt, 0, ',', '.') . ' ₫',
                    'currency'  => 'VND',
                ],
                'source'        => 'Skyscanner',
                'book_url'      => $it['deeplinks']['appUriDesktop']
                                ?? "https://www.skyscanner.net/transport/flights/{$origin}/{$dest}/{$this->formatDateForSky($date)}/",
            ];
        }

        usort($flights, fn($a, $b) => $a['price']['amount'] <=> $b['price']['amount']);

        return [
            'success' => true,
            'source'  => 'skyscanner',
            'route'   => "$origin → $dest",
            'date'    => $date,
            'count'   => count($flights),
            'data'    => $flights,
        ];
    }


    // ================================================================
    // PHƯƠNG THỨC 2: Internal scrape (reverse-engineered từ F12 DevTools)
    // Skyscanner dùng 2-phase polling: CREATE → POLL
    // ================================================================
    private function searchViaInternal(string $origin, string $dest, string $date, int $adults): array
    {
        $dateYYMMDD = $this->formatDateForSky($date); // 260615

        // Bước 1: Tạo session tìm kiếm
        $createUrl = "https://www.skyscanner.net/transport/flights/{$origin}/{$dest}/{$dateYYMMDD}/";

        $ch = curl_init($createUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: vi-VN,vi;q=0.9,en-US;q=0.8',
            ],
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_COOKIEFILE     => '',
            CURLOPT_COOKIEJAR      => '',
        ]);
        curl_exec($ch);
        curl_close($ch);

        // Bước 2: Gọi conductor API (endpoint lấy từ F12)
        $apiUrl = "https://www.skyscanner.net/g/conductor/v1/fps3/search/?"
                . http_build_query([
                    'adults'       => $adults,
                    'cabinclass'   => 'economy',
                    'rtn'          => 0,
                    'preferdirects'=> 'false',
                    'outboundDate' => $date,
                    'originPlace'  => $origin . '-sky',
                    'destinationPlace' => $dest . '-sky',
                    'currency'     => 'VND',
                    'locale'       => 'vi-VN',
                    'market'       => 'VN',
                ]);

        $ch2 = curl_init($apiUrl);
        curl_setopt_array($ch2, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'Accept: application/json',
                'Accept-Language: vi-VN,vi;q=0.9',
                'x-skyscanner-deviceid: ' . $this->generateDeviceId(),
                'x-skyscanner-channelid: website',
                'Referer: https://www.skyscanner.net/',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch2);
        $httpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        curl_close($ch2);

        if ($httpCode !== 200 || !$response) {
            return [
                'success' => false,
                'source'  => 'skyscanner',
                'error'   => "Internal scrape failed (HTTP $httpCode). Hãy thêm RapidAPI key để dùng API chính thức.",
                'data'    => [],
                'note'    => 'Đăng ký API key miễn phí tại: https://rapidapi.com/skyscanner/api/skyscanner-api',
            ];
        }

        return $this->parseInternalResponse($response, $origin, $dest, $date);
    }

    private function parseInternalResponse(string $raw, string $origin, string $dest, string $date): array
    {
        $json = json_decode($raw, true);
        if (!$json) {
            return ['success' => false, 'source' => 'skyscanner', 'error' => 'JSON parse failed', 'data' => []];
        }

        $flights = [];
        $itineraries = $json['itineraries']   // conductor v1
                    ?? $json['data']['itineraries']  // fps3
                    ?? [];

        $places = $json['places'] ?? [];
        $carriers = $json['carriers'] ?? [];
        $segments = $json['segments'] ?? [];
        $legs     = $json['legs'] ?? [];

        foreach ($itineraries as $it) {
            $legId     = $it['legIds'][0] ?? null;
            $leg       = $this->findById($legs, $legId);
            $pricingOptions = $it['pricingOptions'] ?? [];
            $cheapest  = $pricingOptions[0] ?? null;

            if (!$leg || !$cheapest) continue;

            $priceAmt  = (int)(($cheapest['price']['amount'] ?? 0) * 1000); // convert from k
            if ($priceAmt === 0) continue;

            $segId    = $leg['segmentIds'][0] ?? null;
            $seg      = $this->findById($segments, $segId);
            $carrierId = $seg['marketingCarrierId'] ?? $leg['carrierIds'][0] ?? null;
            $carrier  = $this->findById($carriers, $carrierId);

            $departureTime = substr($leg['departure'] ?? '', 11, 5);
            $arrivalTime   = substr($leg['arrival'] ?? '', 11, 5);

            $flights[] = [
                'airline'       => $carrier['name'] ?? 'Unknown',
                'logo'          => $carrier['imageUrl'] ?? '',
                'flightNumber'  => ($carrier['iata'] ?? '') . ($seg['flightNumber'] ?? ''),
                'departureTime' => $departureTime,
                'arrivalTime'   => $arrivalTime,
                'duration'      => $this->formatDuration($leg['durationInMinutes'] ?? 0),
                'stops'         => ($leg['stopCount'] ?? 0) === 0 ? 'Bay thẳng' : ($leg['stopCount'] . ' điểm dừng'),
                'price'         => [
                    'amount'    => $priceAmt,
                    'formatted' => number_format($priceAmt, 0, ',', '.') . ' ₫',
                    'currency'  => 'VND',
                ],
                'source'        => 'Skyscanner',
                'book_url'      => $cheapest['deepLink'] ?? "https://www.skyscanner.net/transport/flights/{$origin}/{$dest}/{$this->formatDateForSky($date)}/",
            ];
        }

        usort($flights, fn($a, $b) => $a['price']['amount'] <=> $b['price']['amount']);

        return [
            'success' => true,
            'source'  => 'skyscanner',
            'route'   => "$origin → $dest",
            'date'    => $date,
            'count'   => count($flights),
            'data'    => $flights,
        ];
    }

    // ================================================================
    // HELPERS
    // ================================================================
    private function findById(array $list, $id): ?array
    {
        foreach ($list as $item) {
            if (($item['id'] ?? null) === $id || ($item['legId'] ?? null) === $id) return $item;
        }
        return null;
    }

    private function formatDateForSky(string $date): string
    {
        // YYYY-MM-DD → YYMMDD (VD: 2026-06-15 → 260615)
        return date('ymd', strtotime($date));
    }

    private function formatDuration(int $minutes): string
    {
        if ($minutes <= 0) return '';
        return intdiv($minutes, 60) . 'g ' . ($minutes % 60) . 'p';
    }

    private function generateDeviceId(): string
    {
        // Giả lập deviceId ngẫu nhiên dạng UUID v4
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * EntityId cho các sân bay Việt Nam thường dùng
     * (Bắt buộc theo API skyscanner-flights-travel-api)
     */
    private function getEntityId(string $skyId): string
    {
        return match (strtoupper($skyId)) {
            'SGN'  => '95673379',   // Tân Sơn Nhất - TP HCM
            'HAN'  => '128668079',  // Nội Bài - Hà Nội
            'DAD'  => '95674529',   // Đà Nẵng
            'CXR'  => '95674536',   // Cam Ranh - Nha Trang
            'PQC'  => '95674565',   // Phú Quốc
            'VCA'  => '95674560',   // Cần Thơ
            'VII'  => '95674561',   // Vinh
            'HUI'  => '95674527',   // Huế (Phú Bài)
            'BMV'  => '95674521',   // Buôn Mê Thuột
            'DLI'  => '95674526',   // Đà Lạt (Liên Khương)
            'TBB'  => '95674556',   // Tuy Hòa (Đông Tác)
            'VDO'  => '128668397',  // Vân Đồn
            // Sân bay quốc tế phổ biến
            'BKK'  => '95565050',   // Bangkok Suvarnabhumi
            'DMK'  => '128668416',  // Bangkok Don Mueang
            'SIN'  => '128668174',  // Singapore Changi
            'KUL'  => '95565044',   // Kuala Lumpur
            'NRT'  => '95673581',   // Tokyo Narita
            'ICN'  => '95673492',   // Seoul Incheon
            'PEK'  => '95673529',   // Beijing
            'HKG'  => '95673496',   // Hong Kong
            default => '',           // Entity không xác định — API vẫn có thể hoạt động
        };
    }
}
