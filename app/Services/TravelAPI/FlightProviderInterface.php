<?php
declare(strict_types=1);

namespace App\Services\TravelAPI;

interface FlightProviderInterface
{
    public function searchFlights(string $origin, string $destination, string $date): array;

    public function verifyPrice(string $flightId): bool;

    public function bookFlight(array $flightData): array;
}
