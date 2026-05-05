<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$originSkyId = $_GET['originSkyId'] ?? 'SGN';
$destinationSkyId = $_GET['destinationSkyId'] ?? 'HAN';
$originEntityId = $_GET['originEntityId'] ?? '95673379';
$destinationEntityId = $_GET['destinationEntityId'] ?? '128668079';
$date = $_GET['date'] ?? '2026-05-15';
$adults = $_GET['adults'] ?? '1';
$currency = $_GET['currency'] ?? 'VND';
$market = $_GET['market'] ?? 'VN';

echo "<h2>Du lieu chuyen bay thuc te {$originSkyId} di {$destinationSkyId} ngay {$date}</h2>";

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

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "https://skyscanner-flights-travel-api.p.rapidapi.com/flights/searchFlights?{$query}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => [
        "X-RapidAPI-Host: skyscanner-flights-travel-api.p.rapidapi.com",
        "X-RapidAPI-Key: 38ab0610a4msh9ca0345cabfdc62p189f00jsn011fbc7dfea2",
    ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($err) {
    echo "Loi cURL: " . htmlspecialchars($err);
    exit;
}

$data = json_decode($response, true);

if (!is_array($data)) {
    echo "<pre>Khong decode duoc JSON. Raw response:\n" . htmlspecialchars($response) . "</pre>";
    exit;
}

$itineraries = $data['data']['itineraries'] ?? $data['itineraries'] ?? [];
$flights = [];

foreach ($itineraries as $itinerary) {
    $leg = $itinerary['legs'][0] ?? [];
    $carrier = [];
    if (isset($leg['carriers']) && is_array($leg['carriers'])) {
        $carrier = $leg['carriers']['marketing'][0] ?? $leg['carriers'][0] ?? [];
    }

    $itineraryText = json_encode($itinerary);
    $airline = $carrier['name'] ?? 'Unknown Airline';
    $logo = $carrier['logoUrl'] ?? '';

    if ($airline === 'Unknown Airline') {
        if (str_contains($itineraryText, '-31703') || str_contains($itineraryText, '/viet/')) {
            $airline = 'Vietnam Airlines';
            $logo = 'https://logos.skyscnr.com/images/airlines/favicon/VN.png';
        } elseif (str_contains($itineraryText, '-31705') || str_contains($itineraryText, '/jtuk/')) {
            $airline = 'VietJet Air';
            $logo = 'https://logos.skyscnr.com/images/airlines/favicon/4V.png';
        }
    }

    $flights[] = [
        'Airline' => $airline,
        'Departure' => $leg['departure'] ?? '',
        'Arrival' => $leg['arrival'] ?? '',
        'Price' => $itinerary['price']['formatted'] ?? ($itinerary['price']['raw'] ?? ''),
        'Logo' => $logo,
    ];
}

echo "<p><strong>HTTP Code:</strong> {$httpCode} | <strong>So ket qua:</strong> " . count($flights) . "</p>";

if (empty($flights)) {
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    exit;
}
?>

<table border="1" cellpadding="10" cellspacing="0" style="border-collapse: collapse; width: 100%; font-family: Arial, sans-serif;">
    <thead>
        <tr style="background: #f2f6ff;">
            <th>Logo</th>
            <th>Hang bay</th>
            <th>Gio cat canh</th>
            <th>Gio ha canh</th>
            <th>Gia ve</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($flights as $flight): ?>
            <tr>
                <td style="text-align: center;">
                    <?php if (!empty($flight['Logo'])): ?>
                        <img src="<?= htmlspecialchars($flight['Logo']) ?>" alt="<?= htmlspecialchars($flight['Airline']) ?>" style="width: 42px; height: 42px; object-fit: contain;">
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($flight['Airline']) ?></td>
                <td><?= htmlspecialchars($flight['Departure']) ?></td>
                <td><?= htmlspecialchars($flight['Arrival']) ?></td>
                <td><strong><?= htmlspecialchars((string) $flight['Price']) ?></strong></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h3>Array rut gon</h3>
<pre><?php print_r($flights); ?></pre>
