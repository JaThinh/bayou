<?php
/**
 * Service: Travel API Interface
 * Interface chuẩn để tích hợp mọi GDS/Hãng bay
 */
namespace App\Services\TravelAPI;

interface TravelAPIInterface
{
    /** Tìm kiếm chuyến bay */
    public function searchFlights(string $origin, string $dest, string $date, int $adults, int $children): array;
    
    /** Giữ chỗ (Hold Booking) */
    public function holdBooking(array $flightData, array $passengers): array;
    
    /** Xuất vé */
    public function issueTicket(string $pnr): array;
    
    /** Hủy booking */
    public function cancelBooking(string $pnr): bool;
    
    /** Lấy trạng thái booking từ hãng */
    public function retrieveBooking(string $pnr): array;
}
