<?php
declare(strict_types=1);

namespace App\Services\TravelAPI;

class AmadeusApiService implements FlightProviderInterface
{
    private const SEARCH_ENDPOINT = 'https://api.amadeus.com/v2/shopping/flight-offers';
    private const PRICE_ENDPOINT = 'https://api.amadeus.com/v1/shopping/flight-offers/pricing';
    private const BOOKING_ENDPOINT = 'https://api.amadeus.com/v1/booking/flight-orders';

    public function searchFlights(string $origin, string $destination, string $date): array
    {
        $query = [
            'originLocationCode' => $origin,
            'destinationLocationCode' => $destination,
            'departureDate' => $date,
            'adults' => 1,
            'currencyCode' => 'VND',
            'carrier' => 'VN',
            'includedAirlineCodes' => 'VN',
        ];

        $response = $this->sendMockRequest(self::SEARCH_ENDPOINT, $query, 'GET');

        return array_map(
            fn (array $flight): array => $this->mapFlightToBayouFormat($flight, $origin, $destination, $date),
            $response['data']
        );
    }

    public function verifyPrice(string $flightId): bool
    {
        $this->sendMockRequest(self::PRICE_ENDPOINT, ['flightOfferId' => $flightId], 'POST');

        return (bool) random_int(0, 1);
    }

    public function bookFlight(array $flightData): array
    {
        $this->sendMockRequest(self::BOOKING_ENDPOINT, $flightData, 'POST');

        return [
            'success' => true,
            'provider' => 'Vietnam Airlines via Amadeus',
            'pnr' => 'VN' . random_int(100000, 999999),
        ];
    }

    private function sendMockRequest(string $endpoint, array $payload, string $method): array
    {
        // TODO: Dien Amadeus Client ID/Client Secret that tai day.
        // TODO: Lay OAuth token that va thay mock nay bang REST client production.
        if (function_exists('curl_init')) {
            $url = $method === 'GET'
                ? $endpoint . '?' . http_build_query($payload)
                : $endpoint;

            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer mock-amadeus-access-token',
                    'Content-Type: application/json',
                ],
            ]);

            if ($method !== 'GET') {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload, JSON_THROW_ON_ERROR));
            }

            curl_close($curl);
        }

        return [
            'data' => [
                [
                    'id' => 'AMADEUS-VN-' . random_int(1000, 9999),
                    'carrierCode' => 'VN',
                    'number' => (string) random_int(200, 899),
                    'total' => random_int(1600000, 4300000),
                    'departureTime' => '09:20',
                    'arrivalTime' => '11:30',
                ],
                [
                    'id' => 'AMADEUS-VN-' . random_int(1000, 9999),
                    'carrierCode' => 'VN',
                    'number' => (string) random_int(900, 1299),
                    'total' => random_int(1900000, 5200000),
                    'departureTime' => '15:45',
                    'arrivalTime' => '17:55',
                ],
            ],
        ];
    }

    private function mapFlightToBayouFormat(array $flight, string $origin, string $destination, string $date): array
    {
        return [
            'Provider' => 'AMADEUS',
            'Airline' => 'Vietnam Airlines',
            'AirlineCode' => 'VN',
            'FlightCode' => $flight['carrierCode'] . $flight['number'],
            'FlightId' => $flight['id'],
            'Origin' => $origin,
            'Destination' => $destination,
            'DepartureTime' => $date . ' ' . $flight['departureTime'] . ':00',
            'ArrivalTime' => $date . ' ' . $flight['arrivalTime'] . ':00',
            'Price' => (float) $flight['total'],
            'Currency' => 'VND',
        ];
    }
}
