<?php
/**
 * backend/Models/PriceCache.php
 * Cache kết quả tìm kiếm trong PostgreSQL (thay Redis)
 */

namespace Backend\Models;

use Backend\Database\DB;

class PriceCache
{
    private static int $ttl = 600; // 10 phút

    public static function init(): void
    {
        self::$ttl = (int)($_ENV['CACHE_TTL_SECONDS'] ?? 600);
    }

    /** Tạo cache key: SGN-HAN-2026-06-15 */
    public static function key(string $origin, string $dest, string $date): string
    {
        return strtoupper($origin) . '-' . strtoupper($dest) . '-' . $date;
    }

    /** Lấy cache nếu còn hạn */
    public static function get(string $key): ?array
    {
        $row = DB::selectOne(
            'SELECT data_json FROM price_cache
             WHERE route_key = ? AND expires_at > NOW()',
            [$key]
        );
        if (!$row) return null;
        $data = json_decode($row['data_json'], true);
        $data['_from_cache'] = true;
        return $data;
    }

    /** Lưu cache */
    public static function set(string $key, array $data, string $source = ''): void
    {
        self::init();
        $json    = json_encode($data, JSON_UNESCAPED_UNICODE);
        $expires = date('Y-m-d H:i:s', time() + self::$ttl);
        $count   = count($data['data'] ?? []);

        DB::statement(
            'INSERT INTO price_cache (route_key, data_json, result_count, source, expires_at)
             VALUES (?, ?, ?, ?, ?)
             ON CONFLICT (route_key) DO UPDATE
               SET data_json    = EXCLUDED.data_json,
                   result_count = EXCLUDED.result_count,
                   source       = EXCLUDED.source,
                   expires_at   = EXCLUDED.expires_at,
                   created_at   = NOW()',
            [$key, $json, $count, $source, $expires]
        );
    }

    /** Xóa cache hết hạn */
    public static function cleanup(): void
    {
        DB::statement('DELETE FROM price_cache WHERE expires_at < NOW()');
    }
}
