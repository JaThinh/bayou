<?php
/**
 * Flight Aggregator Service
 * Gộp dữ liệu từ nhiều nguồn: Google Flights, Skyscanner, hãng bay trực tiếp
 * Trả về kết quả duy nhất đã được sắp xếp và loại bỏ trùng lặp
 */

namespace App\Services;

require_once __DIR__ . '/GoogleFlightsScraper.php';
require_once __DIR__ . '/SkyscannerScraper.php';
require_once __DIR__ . '/AirlineVerifier.php';

class FlightAggregator
{
    private GoogleFlightsScraper $googleScraper;
    private SkyscannerScraper    $skyscanner;
    private AirlineVerifier      $verifier;

    public function __construct()
    {
        $this->googleScraper = new GoogleFlightsScraper();
        $this->skyscanner    = new SkyscannerScraper();
        $this->verifier      = new AirlineVerifier();
    }

    /**
     * Tìm kiếm từ TẤT CẢ nguồn — song song (nếu có cURL multi), gộp lại
     *
     * @param string $origin   IATA điểm đi
     * @param string $dest     IATA điểm đến
     * @param string $date     YYYY-MM-DD
     * @param bool   $verify   Có kiểm tra trực tiếp web hãng bay không
     */
    public function searchAll(string $origin, string $dest, string $date, bool $verify = true): array
    {
        $startTime = microtime(true);

        // ----------------------------------------------------------------
        // Bước 1: Lấy dữ liệu từ Google Flights & Skyscanner
        // ----------------------------------------------------------------
        $googleResult = $this->googleScraper->searchFlights($origin, $dest, $date);
        $skyResult    = $this->skyscanner->searchFlights($origin, $dest, $date);

        // ----------------------------------------------------------------
        // Bước 2: Gộp + Normalize tất cả vào một danh sách
        // ----------------------------------------------------------------
        $allFlights = [];

        foreach ($googleResult['data'] ?? [] as $f) {
            $f['source_badge'] = 'google';
            $allFlights[] = $f;
        }

        foreach ($skyResult['data'] ?? [] as $f) {
            $f['source_badge'] = 'skyscanner';
            // Không thêm trùng (cùng hãng + giờ bay gần nhau)
            if (!$this->isDuplicate($allFlights, $f)) {
                $allFlights[] = $f;
            }
        }

        // ----------------------------------------------------------------
        // Bước 3: Xác minh giá từ web hãng bay (tối đa 3 chuyến rẻ nhất)
        // ----------------------------------------------------------------
        $verifications = [];
        if ($verify && !empty($allFlights)) {
            $topFlights = array_slice($allFlights, 0, 3); // chỉ xác minh top 3
            foreach ($topFlights as $f) {
                $code = $this->extractAirlineCode($f['airline'] ?? '', $f['flightNumber'] ?? '');
                if ($code && !isset($verifications[$code])) {
                    $verifications[$code] = $this->verifier->verifyPrice(
                        $code,
                        $f['flightNumber'] ?? '',
                        $origin,
                        $dest,
                        $date
                    );
                }
            }
        }

        // ----------------------------------------------------------------
        // Bước 4: Sắp xếp theo giá tăng dần
        // ----------------------------------------------------------------
        usort($allFlights, fn($a, $b) => ($a['price']['amount'] ?? PHP_INT_MAX) <=> ($b['price']['amount'] ?? PHP_INT_MAX));

        $elapsed = round((microtime(true) - $startTime) * 1000); // ms

        return [
            'success'       => !empty($allFlights),
            'route'         => "$origin → $dest",
            'date'          => $date,
            'count'         => count($allFlights),
            'elapsed_ms'    => $elapsed,
            'data'          => $allFlights,
            'verifications' => $verifications,
            'sources'       => [
                'google'     => ['ok' => $googleResult['success'] ?? false, 'count' => $googleResult['count'] ?? 0],
                'skyscanner' => ['ok' => $skyResult['success']    ?? false, 'count' => $skyResult['count']    ?? 0],
            ],
            'errors' => array_filter([
                isset($googleResult['error'])  ? 'Google: ' . $googleResult['error']  : null,
                isset($skyResult['error'])      ? 'Skyscanner: ' . $skyResult['error'] : null,
            ]),
        ];
    }

    /**
     * Kiểm tra trùng lặp dựa vào hãng bay + thời gian khởi hành
     */
    private function isDuplicate(array $existing, array $flight): bool
    {
        $newAirline = strtolower($flight['airline'] ?? '');
        $newTime    = $flight['departureTime'] ?? '';
        foreach ($existing as $e) {
            if (strtolower($e['airline'] ?? '') === $newAirline && $e['departureTime'] === $newTime) {
                return true;
            }
        }
        return false;
    }

    /**
     * Trích xuất mã hãng từ tên hoặc số hiệu chuyến bay
     */
    private function extractAirlineCode(string $name, string $flightNum): string
    {
        $name = strtolower($name);
        if (str_contains($name, 'vietnam') || str_starts_with($flightNum, 'VN')) return 'VN';
        if (str_contains($name, 'vietjet') || str_starts_with($flightNum, 'VJ')) return 'VJ';
        if (str_contains($name, 'bamboo')  || str_starts_with($flightNum, 'QH')) return 'QH';
        if (str_contains($name, 'vietravel') || str_starts_with($flightNum, 'VU')) return 'VU';
        // Lấy 2 ký tự đầu của số hiệu bay làm mã hãng
        return substr(preg_replace('/[^A-Z]/', '', strtoupper($flightNum)), 0, 2);
    }
}
