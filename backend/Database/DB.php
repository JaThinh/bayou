<?php
/**
 * backend/Database/DB.php
 * Query helper đơn giản dùng PostgreSQL PDO
 */

namespace Backend\Database;

use Backend\Config\Database;

class DB
{
    /** SELECT nhiều hàng */
    public static function select(string $sql, array $bindings = []): array
    {
        $stmt = Database::connect()->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchAll();
    }

    /** SELECT 1 hàng */
    public static function selectOne(string $sql, array $bindings = []): ?array
    {
        $stmt = Database::connect()->prepare($sql);
        $stmt->execute($bindings);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** INSERT — trả về ID vừa tạo */
    public static function insert(string $sql, array $bindings = []): int
    {
        $stmt = Database::connect()->prepare($sql . ' RETURNING id');
        $stmt->execute($bindings);
        $row = $stmt->fetch();
        return (int)($row['id'] ?? 0);
    }

    /** UPDATE / DELETE — trả về số hàng ảnh hưởng */
    public static function statement(string $sql, array $bindings = []): int
    {
        $stmt = Database::connect()->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->rowCount();
    }

    /** Bắt đầu transaction */
    public static function beginTransaction(): void
    {
        Database::connect()->beginTransaction();
    }

    /** Commit transaction */
    public static function commit(): void
    {
        Database::connect()->commit();
    }

    /** Rollback transaction */
    public static function rollback(): void
    {
        Database::connect()->rollBack();
    }

    /** Escape tên bảng/cột để tránh SQL injection */
    public static function quoteIdentifier(string $name): string
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }
}
