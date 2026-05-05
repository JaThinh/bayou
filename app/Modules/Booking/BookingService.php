<?php
/**
 * Module: Booking
 * Xử lý toàn bộ nghiệp vụ PNR, đặt vé, xuất vé
 */
namespace App\Modules\Booking;

use App\Core\Database;

class BookingService
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll(array $filters = []): array
    {
        $sql = "SELECT b.*, u.fullname AS agent_name, a.name AS airline_name, a.logo_url
                FROM bookings b
                LEFT JOIN users u ON b.user_id = u.id
                LEFT JOIN airlines a ON b.airline_code = a.code";
        
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "(b.pnr_code LIKE :q OR b.order_code LIKE :q)";
            $params[':q'] = "%{$filters['search']}%";
        }

        if (!empty($filters['status'])) {
            $where[] = "b.payment_status = :status";
            $params[':status'] = $filters['status'];
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY b.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findByPnr(string $pnr): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM bookings WHERE pnr_code = :pnr");
        $stmt->execute([':pnr' => $pnr]);
        return $stmt->fetch() ?: null;
    }

    public function updateStatus(int $id, string $paymentStatus, string $ticketStatus): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE bookings SET payment_status = :ps, ticket_status = :ts WHERE id = :id"
        );
        return $stmt->execute([':ps' => $paymentStatus, ':ts' => $ticketStatus, ':id' => $id]);
    }
}
