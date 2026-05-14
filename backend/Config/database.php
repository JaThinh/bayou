<?php
/**
 * backend/Config/database.php
 * Kết nối PostgreSQL qua PDO — đọc thông số từ .env
 */

namespace Backend\Config;

class Database
{
    private static ?\PDO $instance = null;

    public static function connect(): \PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        // Load .env nếu chưa có
        self::loadEnv();

        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? '5432';
        $name = $_ENV['DB_NAME'] ?? 'bayou_db';
        $user = $_ENV['DB_USER'] ?? 'postgres';
        $pass = $_ENV['DB_PASS'] ?? '';

        $dsn = "pgsql:host={$host};port={$port};dbname={$name};options='--client_encoding=UTF8'";

        try {
            self::$instance = new \PDO($dsn, $user, $pass, [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error'   => 'Database connection failed: ' . $e->getMessage(),
            ]);
            exit;
        }

        return self::$instance;
    }

    /** Đọc file .env từ root project */
    private static function loadEnv(): void
    {
        $envFile = dirname(__DIR__, 2) . '/.env';
        if (!file_exists($envFile)) return;

        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            if (!str_contains($line, '=')) continue;

            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");

            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key]    = $value;
                $_SERVER[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}
