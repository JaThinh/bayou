<?php
/**
 * Airline Price Verifier
 * Kiểm tra tổng tiền thực tế trực tiếp từ web hãng bay
 * Bao gồm: Vé + Hành lý ký gửi + Chọn ghế + Phí thanh toán
 *
 * Các hãng hỗ trợ: Vietnam Airlines, VietJet Air, Bamboo Airways
 */

namespace App\Services;

class AirlineVerifier
{
    /**
     * Lấy thông tin chi phí thực tế từ hãng bay
     *
     * @param string $airlineCode VN / VJ / QH / VU
     * @param string $flightNumber VD: VN123, VJ456
     * @param string $origin  SGN
     * @param string $dest    HAN
     * @param string $date    YYYY-MM-DD
     */
    public function verifyPrice(
        string $airlineCode,
        string $flightNumber,
        string $origin,
        string $dest,
        string $date
    ): array {
        $code = strtoupper($airlineCode);

        return match ($code) {
            'VN'    => $this->checkVietnamAirlines($flightNumber, $origin, $dest, $date),
            'VJ'    => $this->checkVietJetAir($origin, $dest, $date),
            'QH'    => $this->checkBambooAirways($origin, $dest, $date),
            'VU'    => $this->checkVietravelAirlines($origin, $dest, $date),
            default => $this->fallbackDeepLink($code, $origin, $dest, $date),
        };
    }

    // ================================================================
    // VIETNAM AIRLINES (VN)
    // API chính thức: dùng endpoint deeplink booking, không cần key
    // Dùng Availability API từ F12: /api/avail
    // ================================================================
    private function checkVietnamAirlines(string $flightNum, string $origin, string $dest, string $date): array
    {
        // Vietnam Airlines dùng Salesforce-based booking engine
        // Endpoint lấy từ F12 DevTools trên https://www.vietnamairlines.com
        $url = 'https://www.vietnamairlines.com/vn/vi/search-booking/book-flight';
        $apiUrl = 'https://www.vietnamairlines.com/api/avail?' . http_build_query([
            'ADT'     => 1,
            'CHD'     => 0,
            'INF'     => 0,
            'CabinCl' => 'Y',       // Y=Economy
            'Route'   => "{$origin}{$dest}",
            'DepDate' => str_replace('-', '', $date), // YYYYMMDD
            'RetDate' => '',
        ]);

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'Accept: application/json, text/plain, */*',
                'Referer: https://www.vietnamairlines.com/',
                'Accept-Language: vi-VN,vi;q=0.9',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $basePrice = 0;
        $parsed    = false;

        if ($httpCode === 200 && $response) {
            $json = json_decode($response, true);
            // Parse giá từ Vietnam Airlines response
            $prices = $json['AO']['Itin'][0]['Seg'][0]['Fare'] ?? [];
            if (!empty($prices)) {
                $basePrice = (int)($prices[0]['Total'] ?? 0);
                $parsed = true;
            }
        }

        // Phí cộng thêm ước tính (lấy từ bảng phụ phí công khai của VN Airlines)
        $fees = $this->getVNAirlinesFees($basePrice);

        return [
            'airline'   => 'Vietnam Airlines',
            'code'      => 'VN',
            'success'   => $parsed,
            'source_url'=> $url,
            'base_price'=> $basePrice,
            'fees'      => $fees,
            'total'     => $basePrice + $fees['baggage'] + $fees['payment'],
            'total_formatted' => number_format($basePrice + $fees['baggage'] + $fees['payment'], 0, ',', '.') . ' ₫',
            'note'      => $parsed ? 'Giá thực tế từ Vietnam Airlines' : 'Không lấy được giá trực tiếp — nhấn nút để xem trên web hãng bay',
            'book_url'  => "https://www.vietnamairlines.com/vn/vi/search-booking/book-flight?originCode={$origin}&destinationCode={$dest}&departureDate={$date}&adult=1&children=0&infant=0&cabinClass=Y",
        ];
    }

    private function getVNAirlinesFees(int $basePrice): array
    {
        // Phí công khai của Vietnam Airlines (2025-2026)
        return [
            'baggage'   => 0,        // Hạng Economy đã bao gồm 23kg
            'seat'      => 0,        // Chọn ghế miễn phí 24h trước
            'payment'   => $basePrice > 0 ? (int)($basePrice * 0.015) : 15000, // 1.5% phí thẻ (tối thiểu 15k)
            'note'      => 'VN Airlines: 23kg hành lý ký gửi miễn phí, chọn ghế thường miễn phí',
        ];
    }

    // ================================================================
    // VIETJET AIR (VJ)
    // API lấy từ F12 DevTools trên https://www.vietjetair.com
    // ================================================================
    private function checkVietJetAir(string $origin, string $dest, string $date): array
    {
        // VietJet dùng SkySpeed booking engine
        // Endpoint lấy từ F12 DevTools
        $apiUrl = 'https://api.vietjetair.com/Sites/VietjetAir/VietjetAirApi/api/Common/GetAvailableFlights';
        $payload = json_encode([
            'departure'  => $origin,
            'arrival'    => $dest,
            'depDate'    => $date,
            'adult'      => 1,
            'child'      => 0,
            'infant'     => 0,
            'currency'   => 'VND',
            'fareType'   => 'ECO',
        ]);

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'Accept: application/json',
                'Referer: https://www.vietjetair.com/',
                'Origin: https://www.vietjetair.com',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $basePrice = 0;
        $parsed    = false;

        if ($httpCode === 200 && $response) {
            $json = json_decode($response, true);
            $flights = $json['ListFlight'] ?? $json['data'] ?? [];
            if (!empty($flights)) {
                $first = $flights[0];
                $basePrice = (int)($first['Fare']['Total'] ?? $first['totalPrice'] ?? 0);
                $parsed = true;
            }
        }

        $fees = $this->getVJFees($basePrice);

        return [
            'airline'   => 'VietJet Air',
            'code'      => 'VJ',
            'success'   => $parsed,
            'source_url'=> 'https://www.vietjetair.com',
            'base_price'=> $basePrice,
            'fees'      => $fees,
            'total'     => $basePrice + $fees['baggage'] + $fees['seat'] + $fees['payment'],
            'total_formatted' => number_format($basePrice + $fees['baggage'] + $fees['seat'] + $fees['payment'], 0, ',', '.') . ' ₫',
            'note'      => $parsed ? 'Giá thực tế từ VietJet Air' : 'Không lấy được giá — nhấn nút để xem trực tiếp',
            'book_url'  => "https://www.vietjetair.com/vi/flight?departureStation={$origin}&arrivalStation={$dest}&departureDate={$date}&adult=1&child=0&infant=0&tripType=1",
        ];
    }

    private function getVJFees(int $basePrice): array
    {
        // Phí phụ trội VietJet (2025-2026) — SkyBoss Lite không bao gồm hành lý
        return [
            'baggage' => 245000,   // Gói 20kg hành lý ký gửi thêm ≈ 245.000₫ (SGN-HAN)
            'seat'    => 55000,    // Chọn ghế ≈ 55.000₫ (ghế tiêu chuẩn)
            'payment' => $basePrice > 0 ? (int)($basePrice * 0.02) : 20000,  // 2% phí thẻ
            'note'    => 'VJ: Giá cơ bản KHÔNG bao gồm hành lý ký gửi (tính thêm 245k/20kg SGN-HAN)',
        ];
    }

    // ================================================================
    // BAMBOO AIRWAYS (QH)
    // ================================================================
    private function checkBambooAirways(string $origin, string $dest, string $date): array
    {
        // Bamboo dùng Radixx booking engine
        $apiUrl = 'https://www.bambooairways.com/api/v2/flight-search/search-flight';
        $payload = json_encode([
            'departureAirport'  => $origin,
            'arrivalAirport'    => $dest,
            'departureDate'     => $date,
            'paxType'           => [['type' => 'ADT', 'count' => 1]],
            'cabinClass'        => 'Y',
            'tripType'          => 'OW',
            'currency'          => 'VND',
        ]);

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'Accept: application/json',
                'Referer: https://www.bambooairways.com/',
                'Origin: https://www.bambooairways.com',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $basePrice = 0;
        $parsed    = false;

        if ($httpCode === 200 && $response) {
            $json = json_decode($response, true);
            $offers = $json['data']['fareGroups'][0]['fares'] ?? $json['offers'] ?? [];
            if (!empty($offers)) {
                $basePrice = (int)($offers[0]['totalFare'] ?? $offers[0]['price']['total'] ?? 0);
                $parsed = true;
            }
        }

        $fees = $this->getBambooFees($basePrice);

        return [
            'airline'   => 'Bamboo Airways',
            'code'      => 'QH',
            'success'   => $parsed,
            'source_url'=> 'https://www.bambooairways.com',
            'base_price'=> $basePrice,
            'fees'      => $fees,
            'total'     => $basePrice + $fees['baggage'] + $fees['payment'],
            'total_formatted' => number_format($basePrice + $fees['baggage'] + $fees['payment'], 0, ',', '.') . ' ₫',
            'note'      => $parsed ? 'Giá thực tế từ Bamboo Airways' : 'Không lấy được giá — nhấn nút để xem trực tiếp',
            'book_url'  => "https://www.bambooairways.com/flight?departure={$origin}&arrival={$dest}&departureDate={$date}&passenger=1&tripType=OW",
        ];
    }

    private function getBambooFees(int $basePrice): array
    {
        return [
            'baggage' => 0,         // Bamboo Economy bao gồm 20kg hành lý
            'seat'    => 0,         // Chọn ghế tiêu chuẩn miễn phí
            'payment' => $basePrice > 0 ? (int)($basePrice * 0.015) : 15000,
            'note'    => 'Bamboo: 20kg hành lý ký gửi miễn phí, phí chọn ghế đặc biệt từ 50.000₫',
        ];
    }

    // ================================================================
    // VIETRAVEL AIRLINES (VU)
    // ================================================================
    private function checkVietravelAirlines(string $origin, string $dest, string $date): array
    {
        return $this->fallbackDeepLink('VU', $origin, $dest, $date, 'Vietravel Airlines',
            "https://booking.vietravelairlines.vn/vn/vi/?type=one-way&from={$origin}&to={$dest}&date={$date}&adults=1");
    }

    private function fallbackDeepLink(
        string $code,
        string $origin,
        string $dest,
        string $date,
        string $name = '',
        string $url = ''
    ): array {
        $names = ['VN' => 'Vietnam Airlines', 'VJ' => 'VietJet Air', 'QH' => 'Bamboo Airways', 'VU' => 'Vietravel Airlines'];
        $urls  = [
            'VN' => "https://www.vietnamairlines.com/vn/vi/search-booking/book-flight?originCode={$origin}&destinationCode={$dest}&departureDate={$date}&adult=1",
            'VJ' => "https://www.vietjetair.com/vi/flight?departureStation={$origin}&arrivalStation={$dest}&departureDate={$date}&adult=1",
            'QH' => "https://www.bambooairways.com/flight?departure={$origin}&arrival={$dest}&departureDate={$date}&passenger=1",
            'VU' => "https://booking.vietravelairlines.vn/vn/vi/?type=one-way&from={$origin}&to={$dest}&date={$date}&adults=1",
        ];

        return [
            'airline'   => $name ?: ($names[$code] ?? $code),
            'code'      => $code,
            'success'   => false,
            'source_url'=> $url ?: ($urls[$code] ?? '#'),
            'base_price'=> 0,
            'fees'      => ['baggage' => 0, 'seat' => 0, 'payment' => 0, 'note' => ''],
            'total'     => 0,
            'total_formatted' => 'Xem trực tiếp',
            'note'      => 'Nhấn nút để kiểm tra giá và phụ phí trực tiếp trên web hãng bay',
            'book_url'  => $url ?: ($urls[$code] ?? '#'),
        ];
    }
}
