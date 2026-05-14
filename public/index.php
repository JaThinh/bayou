<?php
/**
 * BAYOU — Front Controller
 * Phục vụ index.html tĩnh. PHP chỉ dùng cho API (/api/search_flights.php).
 */
$htmlFile = __DIR__ . '/index.html';
if (file_exists($htmlFile)) {
    header('Content-Type: text/html; charset=UTF-8');
    readfile($htmlFile);
} else {
    http_response_code(404);
    echo 'index.html not found';
}
