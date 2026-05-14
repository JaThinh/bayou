<?php
/**
 * Google Flights Scraper Service v2
 * Lấy dữ liệu vé thực tế từ Google Flights qua Reverse Engineering endpoint batchexecute
 * Cập nhật: Dựa trên phân tích F12 thực tế với cấu trúc f.req chính xác hơn
 */

namespace App\Services;

class GoogleFlightsScraper
{
    private string $baseUrl = 'https://www.google.com/_/TravelFrontendUi/data/batchexecute';

    /**
     * Tìm kiếm chuyến bay một chiều
     *
     * @param string $origin  IATA code điểm đi  (VD: SGN)
     * @param string $dest    IATA code điểm đến (VD: HAN)
     * @param string $date    Ngày bay YYYY-MM-DD
     * @param int    $adults  Số người lớn
     */
    public function searchFlights(string $origin, string $dest, string $date, int $adults = 1): array
    {
        // ================================================================
        // BUILD f.req PAYLOAD — cấu trúc chính xác theo F12 DevTools
        // RPC Method: jQ1olc — đây là method Google dùng để tìm kiếm vé
        // ================================================================
        $flightPayload = [
            null,           // [0]  không dùng
            null,           // [1]  không dùng
            2,              // [2]  tripType: 1=khứ hồi, 2=một chiều
            null,           // [3]
            [],             // [4]  filters
            [               // [5]  legs — danh sách hành trình
                [
                    [
                        [[$origin], 0],      // điểm đi
                        [[$dest],   0],      // điểm đến
                        [
                            (int)substr($date, 0, 4),  // năm
                            (int)substr($date, 5, 2),  // tháng
                            (int)substr($date, 8, 2),  // ngày
                        ]
                    ]
                ]
            ],
            null,           // [6]
            null,           // [7]
            1,              // [8]  cabin: 1=economy, 2=premium economy, 3=business, 4=first
            [               // [9]  passengers
                $adults,    // adults
                0,          // children
                0,          // infants on seat
                0           // infants on lap
            ],
            null,           // [10]
            null,           // [11]
            null,           // [12]
            1,              // [13] sort: 1=best, 2=price asc, 3=duration
            0,              // [14] max stops: 0=any, 1=nonstop, 2=1 stop
        ];

        $rpcPayload = json_encode([
            [["jQ1olc", json_encode($flightPayload), null, "generic"]]
        ]);

        $postBody = 'f.req=' . rawurlencode($rpcPayload) . '&at=&';

        // ================================================================
        // GỬI REQUEST — Giả lập Chrome 124 (Windows)
        // ================================================================
        $ch = curl_init($this->baseUrl . '?rpcids=jQ1olc&source-path=/travel/flights&hl=vi&gl=VN&soc-app=162&soc-platform=1&soc-device=1&rt=c');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postBody,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'Accept: */*',
                'Accept-Language: vi-VN,vi;q=0.9,en-US;q=0.8',
                'Origin: https://www.google.com',
                'Referer: https://www.google.com/travel/flights',
                'X-Same-Domain: 1',
            ],
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $rawResponse = curl_exec($ch);
        $httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError   = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'source' => 'google', 'error' => 'cURL error: ' . $curlError, 'data' => []];
        }

        if ($httpCode !== 200) {
            return ['success' => false, 'source' => 'google', 'error' => "HTTP $httpCode", 'data' => []];
        }

        return $this->parseResponse($rawResponse, $origin, $dest, $date);
    }

    /**
     * Bóc tách dữ liệu mảng lồng nhau từ response của Google
     */
    private function parseResponse(string $rawResponse, string $origin, string $dest, string $date): array
    {
        // Google trả về dạng )]}' theo sau là JSON array
        // Ví dụ: )]}'\n\n[["wrb.fr","jQ1olc","[...]",null,null,null,"generic"]...]
        $lines   = explode("\n", $rawResponse);
        $jsonStr = '';
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (str_starts_with($trimmed, '[')) {
                $jsonStr = $trimmed;
                break;
            }
        }

        if (!$jsonStr) {
            // fallback: strip prefix )]}' and get first JSON array
            $jsonStr = preg_replace("/^[^\\[]+/", '', $rawResponse);
        }

        $outerArray = json_decode($jsonStr, true);

        if (!$outerArray || !is_array($outerArray)) {
            return ['success' => false, 'source' => 'google', 'error' => 'JSON parse failed', 'data' => [], 'raw' => substr($rawResponse, 0, 300)];
        }

        // Tìm phần tử wrb.fr chứa data của jQ1olc
        $innerJsonStr = null;
        foreach ($outerArray as $item) {
            if (is_array($item) && isset($item[0]) && $item[0] === 'wrb.fr' && isset($item[2])) {
                $innerJsonStr = $item[2];
                break;
            }
        }

        if (!$innerJsonStr) {
            return ['success' => false, 'source' => 'google', 'error' => 'No wrb.fr data found', 'data' => []];
        }

        $flightData = json_decode($innerJsonStr, true);
        if (!$flightData) {
            return ['success' => false, 'source' => 'google', 'error' => 'Inner JSON parse failed', 'data' => []];
        }

        // ================================================================
        // PARSE CÁC CHUYẾN BAY
        // Cấu trúc: $flightData[3][0][$i] = mỗi kết quả chuyến bay
        //           hoặc      $flightData[2][0][$i] tùy phiên bản
        // ================================================================
        $flights = [];

        // Thử nhiều đường dẫn khác nhau vì Google hay thay đổi index
        $candidates = [
            $flightData[3][0]  ?? [],
            $flightData[2][0]  ?? [],
            $flightData[1][0]  ?? [],
        ];

        foreach ($candidates as $resultList) {
            if (!empty($resultList)) {
                foreach ($resultList as $r) {
                    $parsed = $this->parseFlightItem($r);
                    if ($parsed) $flights[] = $parsed;
                }
                if (!empty($flights)) break;
            }
        }

        // Sắp xếp tăng dần theo giá
        usort($flights, fn($a, $b) => $a['price']['amount'] <=> $b['price']['amount']);

        return [
            'success' => true,
            'source'  => 'google_flights',
            'route'   => "$origin → $dest",
            'date'    => $date,
            'count'   => count($flights),
            'data'    => $flights,
        ];
    }

    /**
     * Parse một kết quả chuyến bay cụ thể
     */
    private function parseFlightItem(?array $r): ?array
    {
        if (!$r) return null;

        try {
            // Giá — thường nằm tại [1][0][6][1] hoặc [5][0][1]
            $priceStr = $r[1][0][6][1] ?? $r[5][0][1] ?? $r[1][2] ?? null;
            if (!$priceStr) return null;
            $priceNum = (int)preg_replace('/[^0-9]/', '', $priceStr);
            if ($priceNum === 0) return null;

            // Hành trình (itinerary)
            $leg         = $r[1][0][2][0] ?? $r[0][2][0] ?? [];
            $airline     = $leg[0][14] ?? $leg[14] ?? 'Unknown';
            $logo        = $leg[0][16] ?? $leg[16] ?? '';
            $flightNum   = $leg[0][12] ?? $leg[12] ?? '';
            $departTime  = $this->formatTime($leg[0][1] ?? $leg[1] ?? '');
            $arrivalTime = $this->formatTime($leg[0][2] ?? $leg[2] ?? '');
            $duration    = (int)($leg[0][4] ?? $leg[4] ?? 0); // phút
            $stops       = (int)($r[1][0][5] ?? 0);

            return [
                'airline'       => $airline,
                'logo'          => $logo,
                'flightNumber'  => $flightNum,
                'departureTime' => $departTime,
                'arrivalTime'   => $arrivalTime,
                'duration'      => $this->formatDuration($duration),
                'stops'         => $stops === 0 ? 'Bay thẳng' : "$stops điểm dừng",
                'price'         => [
                    'amount'    => $priceNum,
                    'formatted' => number_format($priceNum, 0, ',', '.') . ' ₫',
                    'currency'  => 'VND',
                ],
                'source'        => 'Google Flights',
                'book_url'      => 'https://www.google.com/travel/flights',
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function formatTime(string|array $rawTime): string
    {
        if (is_array($rawTime)) {
            // [hour, minute]
            return sprintf('%02d:%02d', $rawTime[0] ?? 0, $rawTime[1] ?? 0);
        }
        // ISO: 2026-06-15T07:30
        if (strlen($rawTime) >= 16) {
            return substr($rawTime, 11, 5);
        }
        return $rawTime;
    }

    private function formatDuration(int $minutes): string
    {
        if ($minutes <= 0) return '';
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return "{$h}g {$m}p";
    }
}
