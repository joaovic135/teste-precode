<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

class UpdateLogRepository
{
    public function __construct(private PDO $db) {}

    public function findAll(): array
    {
        return $this->db
            ->query('SELECT * FROM price_stock_updates ORDER BY created_at DESC LIMIT 200')
            ->fetchAll();
    }

    public function insert(string $sku, string $type, float $oldValue, float $newValue): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO price_stock_updates (product_sku, update_type, old_value, new_value)
             VALUES (:sku, :type, :old_value, :new_value)
             RETURNING id'
        );

        $stmt->execute([
            ':sku'       => $sku,
            ':type'      => $type,
            ':old_value' => $oldValue,
            ':new_value' => $newValue,
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function markAsSent(int $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE price_stock_updates SET status = 'sent' WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
    }

    public function markAsError(int $id, string $errorMessage): void
    {
        $stmt = $this->db->prepare(
            "UPDATE price_stock_updates SET status = 'error', error_message = :msg WHERE id = :id"
        );
        $stmt->execute([':msg' => $errorMessage, ':id' => $id]);
    }
}
