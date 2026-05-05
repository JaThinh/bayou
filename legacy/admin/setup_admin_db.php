<?php
/**
 * BAYOU OTA - Setup dữ liệu mẫu
 * Chạy 1 lần: http://localhost/admin/setup_admin_db.php
 */
require_once __DIR__ . '/db.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h2>BAYOU - Setup Admin Database</h2><pre>";

try {
    // 1. Thêm cột nếu thiếu
    $cols = $pdo->query("SHOW COLUMNS FROM bookings")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('pnr_code', $cols)) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN pnr_code VARCHAR(10) AFTER id");
        echo "[+] Thêm cột pnr_code\n";
    }
    if (!in_array('order_code', $cols)) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN order_code VARCHAR(20) AFTER pnr_code");
        echo "[+] Thêm cột order_code\n";
    }
    if (!in_array('airline_code', $cols)) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN airline_code VARCHAR(3) AFTER user_id");
        echo "[+] Thêm cột airline_code\n";
    }
    if (!in_array('total_price', $cols)) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN total_price DECIMAL(15,2) DEFAULT 0");
        echo "[+] Thêm cột total_price\n";
    }
    if (!in_array('payment_status', $cols)) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN payment_status ENUM('pending','paid','failed') DEFAULT 'pending'");
        echo "[+] Thêm cột payment_status\n";
    }
    if (!in_array('ticket_status', $cols)) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN ticket_status ENUM('processing','issued','cancelled') DEFAULT 'processing'");
        echo "[+] Thêm cột ticket_status\n";
    }
    if (!in_array('contact_email', $cols)) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN contact_email VARCHAR(255)");
        echo "[+] Thêm cột contact_email\n";
    }
    if (!in_array('contact_phone', $cols)) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN contact_phone VARCHAR(20)");
        echo "[+] Thêm cột contact_phone\n";
    }
    
    // 2. Kiểm tra bảng users có cột fullname không
    $userCols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('fullname', $userCols)) {
        // Có thể dùng full_name
        if (in_array('full_name', $userCols)) {
            echo "[~] users dùng cột 'full_name' (OK)\n";
        } else {
            $pdo->exec("ALTER TABLE users ADD COLUMN fullname VARCHAR(255)");
            echo "[+] Thêm cột fullname vào users\n";
        }
    }
    
    // 3. Kiểm tra bảng airlines có cột logo_url không
    $airlineCols = $pdo->query("SHOW COLUMNS FROM airlines")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('logo_url', $airlineCols)) {
        $pdo->exec("ALTER TABLE airlines ADD COLUMN logo_url VARCHAR(500)");
        echo "[+] Thêm cột logo_url vào airlines\n";
    }

    // 4. Seed airlines
    $pdo->exec("INSERT IGNORE INTO airlines (code, name, logo_url) VALUES
        ('VN', 'Vietnam Airlines', 'https://upload.wikimedia.org/wikipedia/vi/thumb/4/40/Logo_Vietnam_Airlines_new.svg/200px-Logo_Vietnam_Airlines_new.svg.png'),
        ('VJ', 'VietJet Air', 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/02/VietJet_Air_logo.svg/200px-VietJet_Air_logo.svg.png'),
        ('QH', 'Bamboo Airways', 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Bamboo_Airways_logo.svg/200px-Bamboo_Airways_logo.svg.png')
    ");
    echo "[OK] Airlines seeded\n";
    
    // 5. Seed users (nếu trống)
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($userCount == 0) {
        $pdo->exec("INSERT INTO users (username, email, fullname, phone) VALUES
            ('admin', 'admin@bayou.vn', 'Admin Bayou', '0933115768'),
            ('truong', 'truong@bayou.vn', 'Nguyễn Minh Trường', '0966899767'),
            ('thuy', 'thuy@bayou.vn', 'Lê Thị Thúy', '0919093293')
        ");
        echo "[OK] Users seeded (3 users)\n";
    } else {
        echo "[~] Users đã có {$userCount} records\n";
    }

    // 6. Seed bookings (nếu trống)
    $bookingCount = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
    if ($bookingCount == 0) {
        $firstUserId = $pdo->query("SELECT id FROM users LIMIT 1")->fetchColumn();
        $pdo->exec("INSERT INTO bookings (pnr_code, order_code, user_id, airline_code, total_price, payment_status, ticket_status, contact_email, contact_phone) VALUES
            ('ABC123', 'BYO-240501-001', {$firstUserId}, 'VN', 7682000, 'paid', 'issued', 'truong@bayou.vn', '0966899767'),
            ('DEF456', 'BYO-240501-002', {$firstUserId}, 'VJ', 1650000, 'paid', 'processing', 'thuy@bayou.vn', '0919093293'),
            ('GHI789', 'BYO-240502-003', {$firstUserId}, 'VN', 3213000, 'pending', 'processing', 'khach@gmail.com', '0909123456'),
            ('JKL012', 'BYO-240502-004', {$firstUserId}, 'QH', 5700000, 'failed', 'cancelled', 'truong@bayou.vn', '0966899767'),
            ('MNO345', 'BYO-240503-005', {$firstUserId}, 'VN', 9200000, 'paid', 'issued', 'thuy@bayou.vn', '0919093293')
        ");
        echo "[OK] Bookings seeded (5 bookings)\n";
    } else {
        // Cập nhật các booking có sẵn nếu thiếu pnr_code
        $empty = $pdo->query("SELECT COUNT(*) FROM bookings WHERE pnr_code IS NULL OR pnr_code = ''")->fetchColumn();
        if ($empty > 0) {
            $pdo->exec("UPDATE bookings SET 
                pnr_code = CONCAT('PNR', LPAD(id, 3, '0')),
                order_code = CONCAT('BYO-', DATE_FORMAT(created_at, '%y%m%d'), '-', LPAD(id, 3, '0'))
                WHERE pnr_code IS NULL OR pnr_code = ''");
            echo "[OK] Đã cập nhật PNR cho {$empty} bookings cũ\n";
        }
        echo "[~] Bookings đã có {$bookingCount} records\n";
    }

    echo "\n========================================\n";
    echo "✅ HOÀN TẤT! Truy cập: <a href='bookings.php'>bookings.php</a>\n";
    echo "========================================\n";

} catch (PDOException $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
}
echo "</pre>";
