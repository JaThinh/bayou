<?php
/**
 * backend/Models/SearchLog.php
 * Ghi log và quản lý cache tìm kiếm vé
 */

namespace Backend\Models;

use Backend\Database\DB;

class SearchLog
{
    /** Ghi log 1 lần tìm kiếm */
    public static function log(array $params, int $resultCount, string $source, int $elapsedMs): int
    {
        return DB::insert(
            'INSERT INTO search_logs
                (origin, destination, date_depart, date_return, adults, children, infants,
                 cabin_class, result_count, source, elapsed_ms, user_ip)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                strtoupper($params['origin'] ?? ''),
                strtoupper($params['destination'] ?? ''),
                $params['date']         ?? date('Y-m-d'),
                $params['date_return']  ?? null,
                (int)($params['adults']   ?? 1),
                (int)($params['children'] ?? 0),
                (int)($params['infants']  ?? 0),
                $params['cabin_class']  ?? 'economy',
                $resultCount,
                $source,
                $elapsedMs,
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]
        );
    }

    /** Thống kê top tuyến tìm nhiều nhất */
    public static function topRoutes(int $limit = 10): array
    {
        return DB::select(
            'SELECT origin, destination,
                    COUNT(*)          AS total_searches,
                    AVG(result_count) AS avg_results
             FROM search_logs
             WHERE created_at > NOW() - INTERVAL \'30 days\'
             GROUP BY origin, destination
             ORDER BY total_searches DESC
             LIMIT ?',
            [$limit]
        );
    }
}
