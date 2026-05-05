const mysql = require('mysql2/promise');
require('dotenv').config();

async function setupDatabase() {
    try {
        console.log('Đang kết nối đến MySQL...');
        // Kết nối đến MySQL nhưng không chọn database (vì database có thể chưa tồn tại)
        const connection = await mysql.createConnection({
            host: process.env.DB_HOST,
            user: process.env.DB_USER,
            password: process.env.DB_PASS || ""
        });

        console.log('⏳ Tạo database "bayou" (nếu chưa có)...');
        await connection.query('CREATE DATABASE IF NOT EXISTS bayou CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
        
        console.log('📂 Chuyển sang sử dụng database "bayou"...');
        await connection.query('USE bayou;');

        console.log('⏳ Tạo bảng "flights"...');
        const createTableQuery = `
            CREATE TABLE IF NOT EXISTS flights (
                id INT AUTO_INCREMENT PRIMARY KEY,
                flight_number VARCHAR(20) NOT NULL,
                airline VARCHAR(100) NOT NULL,
                departure_city VARCHAR(100) NOT NULL,
                arrival_city VARCHAR(100) NOT NULL,
                departure_time DATETIME NOT NULL,
                arrival_time DATETIME NOT NULL,
                price DECIMAL(10, 2) NOT NULL,
                status VARCHAR(50) DEFAULT 'Scheduled',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        `;
        await connection.query(createTableQuery);

        console.log('🔍 Kiểm tra dữ liệu mẫu...');
        const [rows] = await connection.query('SELECT COUNT(*) as count FROM flights');
        if (rows[0].count === 0) {
            console.log('💾 Đang chèn dữ liệu mẫu (mock data) vào bảng "flights"...');
            const insertQuery = `
                INSERT INTO flights (flight_number, airline, departure_city, arrival_city, departure_time, arrival_time, price) VALUES
                ('VN-123', 'Vietnam Airlines', 'Hà Nội', 'TP. Hồ Chí Minh', '2026-10-15 08:00:00', '2026-10-15 10:15:00', 1500000),
                ('VJ-456', 'Vietjet Air', 'TP. Hồ Chí Minh', 'Đà Nẵng', '2026-10-16 14:30:00', '2026-10-16 15:45:00', 850000),
                ('QH-789', 'Bamboo Airways', 'Hà Nội', 'Đà Nẵng', '2026-10-17 09:00:00', '2026-10-17 10:20:00', 1100000),
                ('VN-234', 'Vietnam Airlines', 'Đà Nẵng', 'TP. Hồ Chí Minh', '2026-10-18 16:00:00', '2026-10-18 17:20:00', 1250000),
                ('QA-999', 'Qatar Airways', 'Hà Nội', 'Paris', '2026-11-01 23:00:00', '2026-11-02 06:30:00', 15000000)
            `;
            await connection.query(insertQuery);
        }

        console.log('✅ Khởi tạo toàn bộ Database thành công! Server của bạn sẽ tự động chạy lại.');
        await connection.end();
    } catch (error) {
        console.error('❌ Lỗi khi khởi tạo database:', error);
    }
}

setupDatabase();
