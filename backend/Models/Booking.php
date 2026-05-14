<?php
/**
 * backend/Models/Booking.php
 * Model đặt chỗ — CRUD trên bảng bookings
 */

namespace Backend\Models;

use Backend\Database\DB;

class Booking
{
    /** Tạo booking mới — trả về booking_ref */
    public static function create(array $data): array
    {
        $id = DB::insert(
            'INSERT INTO bookings
                (user_id, flight_number, airline, airline_code, origin, destination,
                 depart_at, arrive_at, cabin_class, adults,
                 price_amount, currency,
                 passenger_name, passenger_email, contact_phone,
                 baggage_fee, seat_fee, payment_fee, status, source)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $data['user_id']        ?? null,
                $data['flight_number'],
                $data['airline'],
                $data['airline_code']   ?? substr($data['flight_number'], 0, 2),
                strtoupper($data['origin']),
                strtoupper($data['destination']),
                $data['depart_at'],
                $data['arrive_at']      ?? null,
                $data['cabin_class']    ?? 'economy',
                (int)($data['adults']   ?? 1),
                (int)$data['price_amount'],
                $data['currency']       ?? 'VND',
                $data['passenger_name'],
                $data['passenger_email'] ?? null,
                $data['contact_phone'],
                (int)($data['baggage_fee'] ?? 0),
                (int)($data['seat_fee']    ?? 0),
                (int)($data['payment_fee'] ?? 0),
                'pending',
                $data['source']         ?? 'bayou',
            ]
        );

        $booking = self::findById($id);
        return $booking ?? [];
    }

    /** Tìm theo ID */
    public static function findById(int $id): ?array
    {
        return DB::selectOne('SELECT * FROM bookings WHERE id = ?', [$id]);
    }

    /** Tìm theo booking_ref */
    public static function findByRef(string $ref): ?array
    {
        return DB::selectOne(
            'SELECT * FROM bookings WHERE booking_ref = ?',
            [strtoupper(trim($ref))]
        );
    }

    /** Tìm theo số điện thoại + ref (dùng cho tra cứu) */
    public static function lookup(string $ref, string $phone): ?array
    {
        return DB::selectOne(
            'SELECT b.*, 
                    a1.name_vi AS origin_name,
                    a2.name_vi AS destination_name
             FROM bookings b
             LEFT JOIN airports a1 ON a1.iata_code = b.origin
             LEFT JOIN airports a2 ON a2.iata_code = b.destination
             WHERE b.booking_ref = ? AND b.contact_phone = ?',
            [strtoupper(trim($ref)), trim($phone)]
        );
    }

    /** Cập nhật trạng thái */
    public static function updateStatus(int $id, string $status, ?string $pnr = null): int
    {
        return DB::statement(
            'UPDATE bookings SET status = ?, pnr = ? WHERE id = ?',
            [$status, $pnr, $id]
        );
    }

    /** Danh sách booking theo user */
    public static function byUser(int $userId, int $limit = 20): array
    {
        return DB::select(
            'SELECT * FROM bookings WHERE user_id = ? ORDER BY created_at DESC LIMIT ?',
            [$userId, $limit]
        );
    }

    /** Thống kê doanh thu (dùng cho admin) */
    public static function revenueStats(): array
    {
        return DB::selectOne(
            'SELECT
                COUNT(*)                               AS total_bookings,
                COUNT(*) FILTER (WHERE status=\'confirmed\') AS confirmed,
                SUM(total_amount) FILTER (WHERE status=\'confirmed\') AS revenue_vnd
             FROM bookings
             WHERE created_at > NOW() - INTERVAL \'30 days\''
        ) ?? [];
    }
}
