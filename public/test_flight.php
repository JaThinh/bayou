<?php
declare(strict_types=1);

$origin = $_GET['origin'] ?? 'SGN';
$destination = $_GET['destination'] ?? 'HAN';
$originEntityId = $_GET['originEntityId'] ?? '95673379';
$destinationEntityId = $_GET['destinationEntityId'] ?? '128668079';
$date = $_GET['date'] ?? '2026-05-10';
$adults = $_GET['adults'] ?? '1';

$query = http_build_query([
    'originSkyId' => $origin,
    'originEntityId' => $originEntityId,
    'destinationSkyId' => $destination,
    'destinationEntityId' => $destinationEntityId,
    'date' => $date,
    'adults' => $adults,
]);

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => 'https://skyscanner-flights-travel-api.p.rapidapi.com/flights/searchFlights?' . $query,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => [
        'X-RapidAPI-Key: 38ab0610a4msh9ca0345cabfdc62p189f00jsn011fbc7dfea2',
        'X-RapidAPI-Host: skyscanner-flights-travel-api.p.rapidapi.com',
    ],
]);

$response = curl_exec($curl);
$error = curl_error($curl);
$httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);

curl_close($curl);

header('Content-Type: text/html; charset=utf-8');

if ($response === false) {
    echo '<pre>';
    print_r(['error' => $error]);
    echo '</pre>';
    exit;
}

$json = json_decode($response, true);

if (!is_array($json)) {
    echo '<pre>';
    print_r([
        'http_code' => $httpCode,
        'error' => 'Cannot decode JSON response',
        'raw_response' => $response,
    ]);
    echo '</pre>';
    exit;
}

$itineraries = $json['data']['itineraries']
    ?? $json['itineraries']
    ?? $json['data']['results']['itineraries']
    ?? [];

$flights = [];

foreach ($itineraries as $itinerary) {
    $leg = $itinerary['legs'][0] ?? $itinerary['leg'] ?? [];
    $carrier = $leg['carriers']['marketing'][0]
        ?? $leg['carriers'][0]
        ?? $itinerary['pricingOptions'][0]['agents'][0]
        ?? [];

    $price = $itinerary['price']['formatted']
        ?? $itinerary['price']['raw']
        ?? $itinerary['pricingOptions'][0]['price']['formatted']
        ?? $itinerary['pricingOptions'][0]['price']['raw']
        ?? null;

    $flights[] = [
        'Airline' => $carrier['name'] ?? $carrier['alternateId'] ?? 'Unknown Airline',
        'Departure' => $leg['departure'] ?? $leg['departureTime'] ?? null,
        'Arrival' => $leg['arrival'] ?? $leg['arrivalTime'] ?? null,
        'Price' => $price,
        'Logo' => $carrier['logoUrl'] ?? $carrier['logo'] ?? null,
    ];
}

echo '<h2>RapidAPI Skyscanner Flight Test</h2>';
echo '<p><strong>Route:</strong> ' . htmlspecialchars($origin) . ' - ' . htmlspecialchars($destination) . ' | <strong>Date:</strong> ' . htmlspecialchars($date) . '</p>';
echo '<pre>';
print_r([
    'http_code' => $httpCode,
    'total' => count($flights),
    'flights' => $flights,
]);
echo '</pre>';
