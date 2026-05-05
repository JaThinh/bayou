<?php
/**
 * BAYOU OTA - Front Controller
 */
session_start();

// Thiết lập Error Reporting cho môi trường Development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load Database config (optional cho sau này)
require_once __DIR__ . '/../app/Core/Database.php';

// Route đơn giản: Load trang chủ
require_once __DIR__ . '/../resources/views/frontend/home.php';
