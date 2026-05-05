<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

try {
    // 1. Tạo bảng flights nếu chưa có
    $pdo->exec("CREATE TABLE IF NOT EXISTS flights (
        id INT AUTO_INCREMENT PRIMARY KEY,
        airline_code VARCHAR(10),
        airline_name VARCHAR(100),
        flight_number VARCHAR(20),
        origin VARCHAR(10),
        destination VARCHAR(10),
        departure_time DATETIME,
        arrival_time DATETIME,
        price DECIMAL(15, 2),
        seat_class VARCHAR(50),
        logo VARCHAR(255),
        available_seats INT DEFAULT 9,
        stops INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. Xóa dữ liệu cũ để cập nhật dữ liệu thật mới nhất
    $pdo->exec("TRUNCATE TABLE flights;");

    // 3. Dữ liệu thật từ Vietnam Airlines (SGN -> HAN) cho các ngày tới
    $flights = [
        // Ngày 29/04
        ['VN', 'Vietnam Airlines', 'VN206', 'SGN', 'HAN', '2026-04-29 06:00:00', '2026-04-29 08:10:00', 3117500, 'Phổ thông', 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/44/Vietnam_Airlines_logo.svg/1200px-Vietnam_Airlines_logo.svg.png', 9, 0],
        ['VN', 'Vietnam Airlines', 'VN240', 'SGN', 'HAN', '2026-04-29 07:00:00', '2026-04-29 09:10:00', 2552500, 'Phổ thông', 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/44/Vietnam_Airlines_logo.svg/1200px-Vietnam_Airlines_logo.svg.png', 5, 0],
        ['VN', 'Vietnam Airlines', 'VN208', 'SGN', 'HAN', '2026-04-29 08:00:00', '2026-04-29 10:10:00', 3117500, 'Phổ thông', 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/44/Vietnam_Airlines_logo.svg/1200px-Vietnam_Airlines_logo.svg.png', 7, 0],
        
        // Ngày 01/05 (Lễ)
        ['VN', 'Vietnam Airlines', 'VN206', 'SGN', 'HAN', '2026-05-01 06:00:00', '2026-05-01 08:10:00', 2552500, 'Phổ thông', 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/44/Vietnam_Airlines_logo.svg/1200px-Vietnam_Airlines_logo.svg.png', 9, 0],
        ['VN', 'Vietnam Airlines', 'VN7210', 'SGN', 'HAN', '2026-05-01 06:25:00', '2026-05-01 08:35:00', 2552500, 'Phổ thông', 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/44/Vietnam_Airlines_logo.svg/1200px-Vietnam_Airlines_logo.svg.png', 4, 0],
        ['VN', 'Vietnam Airlines', 'VN240', 'SGN', 'HAN', '2026-05-01 07:00:00', '2026-05-01 09:10:00', 3117500, 'Phổ thông', 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/44/Vietnam_Airlines_logo.svg/1200px-Vietnam_Airlines_logo.svg.png', 9, 0],
        
        // Ngày 02/05
        ['VN', 'Vietnam Airlines', 'VN206', 'SGN', 'HAN', '2026-05-02 06:00:00', '2026-05-02 08:10:00', 2552500, 'Phổ thông', 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/44/Vietnam_Airlines_logo.svg/1200px-Vietnam_Airlines_logo.svg.png', 9, 0],
        ['VN', 'Vietnam Airlines', 'VN7204', 'SGN', 'HAN', '2026-05-02 06:30:00', '2026-05-02 08:40:00', 2552500, 'Phổ thông', 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/44/Vietnam_Airlines_logo.svg/1200px-Vietnam_Airlines_logo.svg.png', 2, 0],
        ['VN', 'Vietnam Airlines', 'VN240', 'SGN', 'HAN', '2026-05-02 07:00:00', '2026-05-02 09:10:00', 2912500, 'Phổ thông', 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/44/Vietnam_Airlines_logo.svg/1200px-Vietnam_Airlines_logo.svg.png', 9, 0]
    ];

    $sql = "INSERT INTO flights (airline_code, airline_name, flight_number, origin, destination, departure_time, arrival_time, price, seat_class, logo, available_seats, stops) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    foreach ($flights as $f) {
        $stmt->execute($f);
    }

    echo json_encode([
        "status" => "success",
        "message" => "Đã cập nhật dữ liệu thật từ Vietnam Airlines vào Database thành công!",
        "count" => count($flights)
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Lỗi Database: " . $e->getMessage()
    ]);
}
?>
