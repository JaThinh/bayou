DROP TABLE IF EXISTS `flight_cache`;

CREATE TABLE `flight_cache` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `search_key` CHAR(32) NOT NULL,
    `api_response` JSON NOT NULL,
    `expires_at` TIMESTAMP NOT NULL,
    UNIQUE INDEX `idx_flight_cache_search_key` (`search_key`),
    INDEX `idx_flight_cache_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
