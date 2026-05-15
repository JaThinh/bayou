<?php
/**
 * BAYOU OTA - Trang kết quả tìm kiếm chuyến bay
 *
 * Đây là entry point public/. Chỉ làm nhiệm vụ load view tương ứng.
 * Toàn bộ logic render giao diện loading + AJAX fetch nằm trong view.
 */
session_start();

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../resources/views/frontend/flight-search.php';
