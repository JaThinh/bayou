<?php
/**
 * public/api/search_flights.php
 * REST endpoint: POST /api/search_flights.php
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// Autoload backend
require_once dirname(__DIR__, 2) . '/backend/Api/FlightController.php';

// Parse input
$body   = json_decode(file_get_contents('php://input'), true) ?: [];
$params = array_merge($_GET, $_POST, $body);

echo json_encode(
    \Backend\Api\FlightController::search($params),
    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
);
