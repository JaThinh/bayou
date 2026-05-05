-- Bảng theo dõi lưu lượng sử dụng của Agent
CREATE TABLE IF NOT EXISTS `usage_tracking` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `billing_month` VARCHAR(7) NOT NULL COMMENT 'Định dạng: YYYY-MM',
    `searches_count` INT UNSIGNED DEFAULT 0,
    `bookings_count` INT UNSIGNED DEFAULT 0,
    `tickets_count` INT UNSIGNED DEFAULT 0,
    `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `idx_user_month` (`user_id`, `billing_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bảng ghi nhận giao dịch trừ tiền khi vượt quá gói (Overage Billing)
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `transaction_type` ENUM('deposit', 'overage_fee', 'booking_payment', 'refund') NOT NULL,
    `reference_type` ENUM('search', 'booking', 'ticket', 'manual') DEFAULT 'manual',
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
