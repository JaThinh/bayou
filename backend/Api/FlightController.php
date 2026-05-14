<?php
/**
 * backend/Api/FlightController.php
 * Xử lý tìm kiếm vé — tích hợp Cache + DB Log
 */

namespace Backend\Api;

require_once dirname(__DIR__, 2) . '/backend/Config/database.php';
require_once dirname(__DIR__, 2) . '/backend/Database/DB.php';
require_once dirname(__DIR__, 2) . '/backend/Models/PriceCache.php';
require_once dirname(__DIR__, 2) . '/backend/Models/SearchLog.php';
require_once dirname(__DIR__, 2) . '/backend/Services/FlightAggregator.php';

use Backend\Models\PriceCache;
use Backend\Models\SearchLog;
use App\Services\FlightAggregator;

class FlightController
{
    public static function search(array $params): array
    {
        $origin = trim(strtoupper($params['origin']      ?? ''));
        $dest   = trim(strtoupper($params['destination'] ?? ''));
        $date   = trim($params['date'] ?? date('Y-m-d', strtotime('+7 days')));
        $verify = filter_var($params['verify'] ?? true, FILTER_VALIDATE_BOOLEAN);

        // Validate
        if (!$origin || !$dest) {
            return ['success' => false, 'error' => 'Thiếu origin hoặc destination'];
        }

        // Normalize date DD/MM/YYYY → YYYY-MM-DD
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $date, $m)) {
            $date = "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        if (strtotime($date) < strtotime('today')) {
            return ['success' => false, 'error' => 'Ngày bay không thể trong quá khứ'];
        }

        $startTime = microtime(true);

        // ── 1. Kiểm tra Cache PostgreSQL ─────────────────────
        $cacheKey = PriceCache::key($origin, $dest, $date);
        try {
            $cached = PriceCache::get($cacheKey);
            if ($cached) {
                return $cached;
            }
        } catch (\Throwable) {
            // DB chưa sẵn sàng → bỏ qua cache
        }

        // ── 2. Gọi Aggregator (Google + Skyscanner + Airline) ─
        $aggregator = new FlightAggregator();
        $result     = $aggregator->searchAll($origin, $dest, $date, $verify);

        $elapsed = (int)((microtime(true) - $startTime) * 1000);
        $result['elapsed_ms'] = $elapsed;

        // ── 3. Lưu Cache vào PostgreSQL ───────────────────────
        if ($result['success'] && !empty($result['data'])) {
            try {
                PriceCache::set($cacheKey, $result, $result['sources'] ? 'both' : 'google');
            } catch (\Throwable) {}
        }

        // ── 4. Ghi Search Log ─────────────────────────────────
        try {
            SearchLog::log(
                ['origin' => $origin, 'destination' => $dest, 'date' => $date],
                $result['count'] ?? 0,
                implode('+', array_keys(array_filter($result['sources'] ?? [], fn($s) => $s['ok'] ?? false))),
                $elapsed
            );
        } catch (\Throwable) {}

        return $result;
    }
}
