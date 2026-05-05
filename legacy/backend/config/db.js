const mysql = require('mysql2/promise');
require('dotenv').config();

// Tạo connection pool thay vì connection đơn lẻ để tối ưu hiệu suất
const pool = mysql.createPool({
    host: process.env.DB_HOST,
    user: process.env.DB_USER,
    password: process.env.DB_PASS,
    database: process.env.DB_NAME,
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
});

// Test connection ngay khi module được load
pool.getConnection()
    .then(connection => {
        console.log(`✅ Kết nối thành công đến database MySQL "${process.env.DB_NAME}" qua Laragon`);
        connection.release();
    })
    .catch(err => {
        console.error('❌ Lỗi kết nối database:', err.message);
    });

module.exports = pool;
