<?php
declare(strict_types=1);

namespace App\Services\TravelAPI;

use App\Core\Database;
use PDO;

class FlightSearchService
{
    private const CACHE_TTL_MINUTES = 15;

    private PDO $db;
    /** @var FlightProviderInterface[] */
    private array $providers;

    public function __construct(?PDO $db = null, ?array $providers = null)
    {
        $this->db = $db ?? Database::getInstance()->getConnection();
        $this->providers = $providers ?? [
            new VietjetApiService(),
            new AmadeusApiService(),
        ];
    }

    public function searchFlights(string $origin, string $destination, string $departureDate): array
    {
        $searchKey = md5($origin . $destination . $departureDate);
        $cachedResponse = $this->getCachedResponse($searchKey);

        if ($cachedResponse !== null) {
            return $cachedResponse;
        }

        $apiResponse = $this->aggregateProviderResults($origin, $destination, $departureDate);
        $this->storeCachedResponse($searchKey, $apiResponse);

        return $apiResponse;
    }

    public function verifyPrice(string $flightId): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->verifyPrice($flightId)) {
                return true;
            }
        }

        return false;
    }

    private function getCachedResponse(string $searchKey): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT api_response
             FROM flight_cache
             WHERE search_key = :search_key AND expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute([':search_key' => $searchKey]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        try {
            return json_decode((string) $row['api_response'], true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }
    }

    private function storeCachedResponse(string $searchKey, array $apiResponse): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO flight_cache (search_key, api_response, expires_at)
             VALUES (:search_key, :api_response, DATE_ADD(NOW(), INTERVAL 15 MINUTE))
             ON DUPLICATE KEY UPDATE
                api_response = VALUES(api_response),
                expires_at = VALUES(expires_at)'
        );

        $stmt->execute([
            ':search_key' => $searchKey,
            ':api_response' => json_encode($apiResponse, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ]);
    }

    private function aggregateProviderResults(
        string $origin,
        string $destination,
        string $departureDate
    ): array {
        $flights = [];

        foreach ($this->providers as $provider) {
            try {
                $flights = array_merge(
                    $flights,
                    $provider->searchFlights($origin, $destination, $departureDate)
                );
            } catch (\Throwable) {
                continue;
            }
        }

        usort(
            $flights,
            fn (array $first, array $second): int => ($first['Price'] ?? 0) <=> ($second['Price'] ?? 0)
        );

        return [
            'source' => 'Bayou Aggregator',
            'cache_ttl_minutes' => self::CACHE_TTL_MINUTES,
            'search' => [
                'origin' => $origin,
                'destination' => $destination,
                'departure_date' => $departureDate,
            ],
            'flights' => $flights,
        ];
    }
}
