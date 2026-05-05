-- ============================================================
-- BAYOU OTA - CLEAN DATABASE SCHEMA
-- Copy toàn bộ SQL này vào phpMyAdmin → Tab SQL → Go
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `booking_queue`;
DROP TABLE IF EXISTS `action_logs`;
DROP TABLE IF EXISTS `rate_limits`;
DROP TABLE IF EXISTS `ip_blacklist`;
DROP TABLE IF EXISTS `daily_reports`;
DROP TABLE IF EXISTS `search_logs`;
DROP TABLE IF EXISTS `exchange_rates`;
DROP TABLE IF EXISTS `pricing_policies`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `news`;
DROP TABLE IF EXISTS `email_templates`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `refunds`;
DROP TABLE IF EXISTS `tickets`;
DROP TABLE IF EXISTS `ticket_templates`;
DROP TABLE IF EXISTS `passengers`;
DROP TABLE IF EXISTS `booking_segments`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `flight_cache`;
DROP TABLE IF EXISTS `airports`;
DROP TABLE IF EXISTS `airlines`;
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `flights`;

-- -----------------------------------------------------------
-- A. PHÂN QUYỀN (RBAC)
-- -----------------------------------------------------------
CREATE TABLE `roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `description` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `permissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `module` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `role_permissions` (
    `role_id` INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`role_id`, `permission_id`),
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- B. USERS
-- -----------------------------------------------------------
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT UNSIGNED NOT NULL DEFAULT 5,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL DEFAULT '',
    `full_name` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20),
    `company_name` VARCHAR(255) NULL,
    `agent_code` VARCHAR(20) NULL UNIQUE,
    `balance` DECIMAL(15,2) DEFAULT 0,
    `status` ENUM('active','suspended','pending') DEFAULT 'active',
    `language` VARCHAR(5) DEFAULT 'vi',
    `last_login_at` TIMESTAMP NULL,
    `last_login_ip` VARCHAR(45),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`),
    INDEX `idx_users_status` (`status`),
    INDEX `idx_users_agent` (`agent_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- C. HÃNG BAY & SÂN BAY
-- -----------------------------------------------------------
CREATE TABLE `airlines` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(3) NOT NULL UNIQUE,
    `name` VARCHAR(255) NOT NULL,
    `name_en` VARCHAR(255),
    `logo_url` VARCHAR(500),
    `gds_provider` VARCHAR(50),
    `status` ENUM('active','inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `airports` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `iata_code` VARCHAR(3) NOT NULL UNIQUE,
    `name` VARCHAR(255) NOT NULL,
    `name_en` VARCHAR(255),
    `city` VARCHAR(100),
    `country_code` VARCHAR(2) NOT NULL,
    `region` VARCHAR(50),
    `timezone` VARCHAR(50),
    `status` ENUM('active','inactive') DEFAULT 'active',
    INDEX `idx_airport_country` (`country_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- D. BOOKINGS
-- -----------------------------------------------------------
CREATE TABLE `bookings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `pnr_code` VARCHAR(10) NOT NULL,
    `order_code` VARCHAR(20) NOT NULL,
    `user_id` INT UNSIGNED NULL,
    `airline_code` VARCHAR(3),
    `trip_type` ENUM('oneway','roundtrip','multicity') NOT NULL DEFAULT 'oneway',
    `booking_type` ENUM('domestic','international') NOT NULL DEFAULT 'domestic',
    `adult_count` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `child_count` TINYINT UNSIGNED DEFAULT 0,
    `infant_count` TINYINT UNSIGNED DEFAULT 0,
    `seat_class` VARCHAR(20) DEFAULT 'economy',
    `base_fare` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `tax_fee` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `service_fee` DECIMAL(15,2) DEFAULT 0,
    `commission` DECIMAL(15,2) DEFAULT 0,
    `total_price` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `currency` VARCHAR(3) DEFAULT 'VND',
    `payment_status` ENUM('pending','paid','failed') DEFAULT 'pending',
    `ticket_status` ENUM('processing','issued','cancelled') DEFAULT 'processing',
    `contact_name` VARCHAR(255),
    `contact_email` VARCHAR(100),
    `contact_phone` VARCHAR(20),
    `hold_deadline` TIMESTAMP NULL,
    `notes` TEXT,
    `raw_response` JSON,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    UNIQUE INDEX `idx_booking_pnr` (`pnr_code`),
    UNIQUE INDEX `idx_booking_order` (`order_code`),
    INDEX `idx_booking_status` (`payment_status`, `ticket_status`),
    INDEX `idx_booking_user` (`user_id`),
    INDEX `idx_booking_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `booking_segments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `booking_id` INT UNSIGNED NOT NULL,
    `segment_order` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `flight_number` VARCHAR(10) NOT NULL,
    `airline_code` VARCHAR(3) NOT NULL,
    `origin` VARCHAR(3) NOT NULL,
    `destination` VARCHAR(3) NOT NULL,
    `departure_at` DATETIME NOT NULL,
    `arrival_at` DATETIME NOT NULL,
    `duration_minutes` INT UNSIGNED,
    `cabin_class` VARCHAR(20),
    `fare_basis` VARCHAR(20),
    `booking_class` VARCHAR(2),
    `baggage_allowance` VARCHAR(50),
    `status` ENUM('confirmed','waitlist','cancelled') DEFAULT 'confirmed',
    FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
    INDEX `idx_segment_booking` (`booking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `passengers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `booking_id` INT UNSIGNED NOT NULL,
    `pax_type` ENUM('adult','child','infant') NOT NULL DEFAULT 'adult',
    `title` VARCHAR(10),
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `dob` DATE,
    `gender` ENUM('M','F'),
    `nationality` VARCHAR(2),
    `passport_number` VARCHAR(20),
    `passport_expiry` DATE,
    `ticket_number` VARCHAR(20),
    `fare_amount` DECIMAL(15,2) DEFAULT 0,
    `tax_amount` DECIMAL(15,2) DEFAULT 0,
    FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
    INDEX `idx_pax_booking` (`booking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- E. VÉ & MẶT VÉ
-- -----------------------------------------------------------
CREATE TABLE `tickets` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `booking_id` INT UNSIGNED NOT NULL,
    `passenger_id` INT UNSIGNED NOT NULL,
    `ticket_number` VARCHAR(20) NOT NULL UNIQUE,
    `airline_code` VARCHAR(3) NOT NULL,
    `issue_date` DATE NOT NULL,
    `status` ENUM('active','void','refunded','exchanged') DEFAULT 'active',
    `fare` DECIMAL(15,2) NOT NULL,
    `tax` DECIMAL(15,2) NOT NULL,
    `total` DECIMAL(15,2) NOT NULL,
    `issued_by` INT UNSIGNED,
    `voided_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`),
    FOREIGN KEY (`passenger_id`) REFERENCES `passengers`(`id`),
    FOREIGN KEY (`issued_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `ticket_templates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `airline_code` VARCHAR(3),
    `html_template` LONGTEXT NOT NULL,
    `conditions` TEXT,
    `is_default` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- F. THANH TOÁN
-- -----------------------------------------------------------
CREATE TABLE `payments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `booking_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED,
    `method` ENUM('vnpay','momo','bank_transfer','cash','agent_balance') NOT NULL,
    `transaction_id` VARCHAR(100),
    `amount` DECIMAL(15,2) NOT NULL,
    `currency` VARCHAR(3) DEFAULT 'VND',
    `status` ENUM('pending','success','failed','refunded') DEFAULT 'pending',
    `gateway_response` JSON,
    `paid_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
    INDEX `idx_payment_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- G. CHÍNH SÁCH GIÁ
-- -----------------------------------------------------------
CREATE TABLE `pricing_policies` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `type` ENUM('commission','service_fee','advanced') NOT NULL,
    `airline_code` VARCHAR(3),
    `origin` VARCHAR(3),
    `destination` VARCHAR(3),
    `route_type` ENUM('domestic','international','all') DEFAULT 'all',
    `cabin_class` VARCHAR(20) DEFAULT 'all',
    `commission_pct` DECIMAL(5,2) DEFAULT 0,
    `commission_fixed` DECIMAL(15,2) DEFAULT 0,
    `service_fee_pct` DECIMAL(5,2) DEFAULT 0,
    `service_fee_fixed` DECIMAL(15,2) DEFAULT 0,
    `priority` INT DEFAULT 0,
    `valid_from` DATE,
    `valid_to` DATE,
    `status` ENUM('active','inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `exchange_rates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `from_currency` VARCHAR(3) NOT NULL,
    `to_currency` VARCHAR(3) NOT NULL,
    `rate` DECIMAL(15,6) NOT NULL,
    `fetched_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX `idx_rate_pair` (`from_currency`, `to_currency`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- H. BẢO MẬT & LOG
-- -----------------------------------------------------------
CREATE TABLE `ip_blacklist` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL UNIQUE,
    `reason` VARCHAR(255),
    `blocked_by` INT UNSIGNED,
    `expires_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`blocked_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `rate_limits` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL,
    `endpoint` VARCHAR(100) NOT NULL,
    `hit_count` INT DEFAULT 1,
    `window_start` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ratelimit` (`ip_address`, `endpoint`, `window_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `action_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED,
    `action` VARCHAR(100) NOT NULL,
    `module` VARCHAR(50),
    `entity_type` VARCHAR(50),
    `entity_id` INT UNSIGNED,
    `description` TEXT,
    `ip_address` VARCHAR(45),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
    INDEX `idx_log_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- I. QUEUE & BÁO CÁO
-- -----------------------------------------------------------
CREATE TABLE `booking_queue` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `booking_id` INT UNSIGNED NOT NULL,
    `queue_type` ENUM('international_review','auto_ticket','sync','email') NOT NULL,
    `status` ENUM('pending','processing','done','failed') DEFAULT 'pending',
    `attempts` TINYINT DEFAULT 0,
    `error_log` TEXT,
    `processed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `search_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED,
    `origin` VARCHAR(3),
    `destination` VARCHAR(3),
    `depart_date` DATE,
    `return_date` DATE,
    `trip_type` VARCHAR(10),
    `results_count` INT DEFAULT 0,
    `ip_address` VARCHAR(45),
    `response_time_ms` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_search_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `daily_reports` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `report_date` DATE NOT NULL,
    `total_searches` INT DEFAULT 0,
    `total_bookings` INT DEFAULT 0,
    `total_tickets` INT DEFAULT 0,
    `total_revenue` DECIMAL(15,2) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX `idx_report_date` (`report_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- J. TIN TỨC & CÀI ĐẶT
-- -----------------------------------------------------------
CREATE TABLE `news` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(500) NOT NULL,
    `slug` VARCHAR(500) NOT NULL UNIQUE,
    `content` LONGTEXT NOT NULL,
    `thumbnail` VARCHAR(500),
    `category` ENUM('news','promotion','guide') DEFAULT 'news',
    `language` VARCHAR(5) DEFAULT 'vi',
    `is_published` TINYINT(1) DEFAULT 0,
    `author_id` INT UNSIGNED,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`author_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED,
    `title` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL,
    `type` ENUM('info','warning','booking','system') DEFAULT 'info',
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `email_templates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `subject` VARCHAR(255) NOT NULL,
    `html_body` LONGTEXT NOT NULL,
    `language` VARCHAR(5) DEFAULT 'vi',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `settings` (
    `key` VARCHAR(100) PRIMARY KEY,
    `value` TEXT NOT NULL,
    `description` VARCHAR(255),
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `flight_cache` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `search_key` CHAR(32) NOT NULL,
    `api_response` JSON NOT NULL,
    `expires_at` TIMESTAMP NOT NULL,
    UNIQUE INDEX `idx_flight_cache_search_key` (`search_key`),
    INDEX `idx_flight_cache_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SEED DATA
-- ============================================================
INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'superadmin', 'Quản trị viên cao nhất'),
(2, 'admin', 'Quản trị viên'),
(3, 'agent', 'Đại lý cấp 1'),
(4, 'staff', 'Nhân viên'),
(5, 'customer', 'Khách hàng');

INSERT INTO `users` (`id`, `role_id`, `email`, `password_hash`, `full_name`, `phone`, `agent_code`) VALUES
(1, 1, 'admin@bayou.vn', '$2y$10$dummy', 'Admin Bayou', '0933115768', NULL),
(2, 3, 'truong@bayou.vn', '$2y$10$dummy', 'Nguyễn Minh Trường', '0966899767', 'AGT001'),
(3, 3, 'thuy@bayou.vn', '$2y$10$dummy', 'Lê Thị Thúy', '0919093293', 'AGT002');

INSERT INTO `airlines` (`code`, `name`, `name_en`, `logo_url`) VALUES
('VN', 'Vietnam Airlines', 'Vietnam Airlines', 'https://www.gstatic.com/flights/airline_logos/70px/VN.png'),
('VJ', 'VietJet Air', 'VietJet Air', 'https://www.gstatic.com/flights/airline_logos/70px/VJ.png'),
('QH', 'Bamboo Airways', 'Bamboo Airways', 'https://www.gstatic.com/flights/airline_logos/70px/QH.png'),
('BL', 'Pacific Airlines', 'Pacific Airlines', 'https://www.gstatic.com/flights/airline_logos/70px/BL.png');

INSERT INTO `bookings` (`pnr_code`,`order_code`,`user_id`,`airline_code`,`trip_type`,`booking_type`,`adult_count`,`base_fare`,`tax_fee`,`service_fee`,`total_price`,`payment_status`,`ticket_status`,`contact_name`,`contact_email`,`contact_phone`) VALUES
('ABC123','BYO-260501-001',2,'VN','roundtrip','domestic',2,6235000,1247000,200000,7682000,'paid','issued','Nguyễn Minh Trường','truong@bayou.vn','0966899767'),
('DEF456','BYO-260501-002',3,'VJ','oneway','domestic',1,1200000,350000,100000,1650000,'paid','processing','Lê Thị Thúy','thuy@bayou.vn','0919093293'),
('GHI789','BYO-260502-003',2,'VN','oneway','domestic',1,2552500,510500,150000,3213000,'pending','processing','Trần Văn Khách','khach@gmail.com','0909123456'),
('JKL012','BYO-260502-004',2,'QH','roundtrip','domestic',3,4500000,900000,300000,5700000,'failed','cancelled','Nguyễn Minh Trường','truong@bayou.vn','0966899767'),
('MNO345','BYO-260503-005',3,'VN','roundtrip','international',2,6235000,1247000,200000,9200000,'paid','issued','Lê Thị Thúy','thuy@bayou.vn','0919093293');

INSERT INTO `booking_segments` (`booking_id`,`segment_order`,`flight_number`,`airline_code`,`origin`,`destination`,`departure_at`,`arrival_at`,`duration_minutes`,`cabin_class`) VALUES
(1,1,'VN206','VN','SGN','HAN','2026-05-10 06:00:00','2026-05-10 08:10:00',130,'economy'),
(1,2,'VN207','VN','HAN','SGN','2026-05-15 18:00:00','2026-05-15 20:10:00',130,'economy'),
(2,1,'VJ123','VJ','HAN','DAD','2026-05-12 14:30:00','2026-05-12 15:50:00',80,'economy'),
(3,1,'VN240','VN','SGN','HAN','2026-05-15 07:00:00','2026-05-15 09:10:00',130,'economy'),
(4,1,'QH202','QH','DAD','SGN','2026-05-20 10:00:00','2026-05-20 11:30:00',90,'economy'),
(5,1,'VN300','VN','SGN','NRT','2026-05-25 23:00:00','2026-05-26 07:00:00',300,'economy');

INSERT INTO `passengers` (`booking_id`,`pax_type`,`title`,`first_name`,`last_name`,`ticket_number`) VALUES
(1,'adult','Mr','MINH TRUONG','NGUYEN','738-1234567890'),
(1,'adult','Mrs','THI MAI','TRAN','738-1234567891'),
(2,'adult','Ms','THI THUY','LE',NULL),
(3,'adult','Mr','VAN KHACH','TRAN',NULL),
(5,'adult','Ms','THI THUY','LE','738-9876543210'),
(5,'adult','Mr','VAN HUNG','TRAN','738-9876543211');

INSERT INTO `settings` (`key`, `value`, `description`) VALUES
('site_name', 'Bayou', 'Tên hệ thống'),
('default_currency', 'VND', 'Tiền tệ mặc định'),
('default_language', 'vi', 'Ngôn ngữ mặc định'),
('hold_time_domestic', '30', 'Giữ chỗ nội địa (phút)'),
('hold_time_international', '1440', 'Giữ chỗ quốc tế (phút)'),
('bot_rate_limit', '30', 'Request tối đa/phút/IP');
