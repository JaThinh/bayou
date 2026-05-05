<?php
declare(strict_types=1);

namespace App\Services\TravelAPI;

class VietjetApiService implements FlightProviderInterface
{
    private const SEARCH_ENDPOINT = 'https://b2b-api.vietjetair.com/flight/search';
    private const PRICE_ENDPOINT = 'https://b2b-api.vietjetair.com/flight/price';
    private const BOOKING_ENDPOINT = 'https://b2b-api.vietjetair.com/booking/create';

    public function searchFlights(string $origin, string $destination, string $date): array
    {
        $payload = [
            'origin' => $origin,
            'destination' => $destination,
            'departureDate' => $date,
            'currency' => 'VND',
        ];

        $response = $this->sendMockRequest(self::SEARCH_ENDPOINT, $payload, 'REST');

        return array_map(
            fn (array $flight): array => $this->mapFlightToBayouFormat($flight, $origin, $destination, $date),
            $response['flights']
        );
    }

    public function verifyPrice(string $flightId): bool
    {
        $this->sendMockRequest(self::PRICE_ENDPOINT, ['flightId' => $flightId], 'SOAP');

        return (bool) random_int(0, 1);
    }

    public function bookFlight(array $flightData): array
    {
        $this->sendMockRequest(self::BOOKING_ENDPOINT, $flightData, 'SOAP');

        return [
            'success' => true,
            'provider' => 'Vietjet Air',
            'pnr' => 'VJ' . random_int(100000, 999999),
        ];
    }

    private function sendMockRequest(string $endpoint, array $payload, string $protocol): array
    {
        // TODO: Dien API Key/Agent ID/Secret that cua Vietjet B2B tai day.
        // TODO: Thay mock nay bang HTTP client SOAP/REST that khi co credential production.
        if (function_exists('curl_init')) {
            $curl = curl_init($endpoint);
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $protocol === 'SOAP'
                    ? $this->buildSoapEnvelope($payload)
                    : json_encode($payload, JSON_THROW_ON_ERROR),
                CURLOPT_HTTPHEADER => $protocol === 'SOAP'
                    ? ['Content-Type: text/xml; charset=utf-8', 'X-API-Key: mock-vietjet-api-key']
                    : ['Content-Type: application/json', 'X-API-Key: mock-vietjet-api-key'],
            ]);
            curl_close($curl);
        }

        return [
            'flights' => [
                [
                    'flightNumber' => 'VJ' . random_int(100, 399),
                    'fareAmount' => random_int(950000, 2800000),
                    'departTime' => '07:35',
                    'arriveTime' => '09:45',
                ],
                [
                    'flightNumber' => 'VJ' . random_int(400, 799),
                    'fareAmount' => random_int(1200000, 3200000),
                    'departTime' => '18:10',
                    'arriveTime' => '20:20',
                ],
            ],
        ];
    }

    private function mapFlightToBayouFormat(array $flight, string $origin, string $destination, string $date): array
    {
        return [
            'Provider' => 'VIETJET_B2B',
            'Airline' => 'Vietjet Air',
            'AirlineCode' => 'VJ',
            'FlightCode' => $flight['flightNumber'],
            'FlightId' => 'VJ-' . $flight['flightNumber'] . '-' . $date,
            'Origin' => $origin,
            'Destination' => $destination,
            'DepartureTime' => $date . ' ' . $flight['departTime'] . ':00',
            'ArrivalTime' => $date . ' ' . $flight['arriveTime'] . ':00',
            'Price' => (float) $flight['fareAmount'],
            'Currency' => 'VND',
        ];
    }

    private function buildSoapEnvelope(array $payload): string
    {
        $body = htmlspecialchars(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), ENT_XML1);

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <VietjetRequest>{$body}</VietjetRequest>
    </soapenv:Body>
</soapenv:Envelope>
XML;
    }
}
