<?php
/**
 * Database Configuration
 */
return [
    'host'    => 'localhost',
    'name'    => 'bayou_web',
    'user'    => 'root',
    'pass'    => '',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
