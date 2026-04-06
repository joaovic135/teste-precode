<?php

declare(strict_types=1);

namespace App\Repository;

use App\DTO\OrderDTO;
use PDO;

class OrderRepository
{
    public function __construct(private PDO $db) {}

    public function findAll(): array
    {
        return $this->db
            ->query('SELECT * FROM orders ORDER BY created_at DESC')
            ->fetchAll();
    }

    public function findByMarketplaceId(string $marketplaceOrderId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM orders WHERE marketplace_order_id = :id'
        );
        $stmt->execute([':id' => $marketplaceOrderId]);

        $result = $stmt->fetch();

        return $result !== false ? $result : null;
    }

    public function insert(OrderDTO $dto): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO orders
                (marketplace_order_id, status, customer_name, customer_email, total, items, raw_data)
             VALUES
                (:marketplace_order_id, :status, :customer_name, :customer_email, :total, :items, :raw_data)'
        );

        $stmt->execute([
            ':marketplace_order_id' => $dto->marketplaceOrderId,
            ':status'               => $dto->status,
            ':customer_name'        => $dto->customerName,
            ':customer_email'       => '',
            ':total'                => $dto->total,
            ':items'                => json_encode($dto->items),
            ':raw_data'             => json_encode($dto->rawData),
        ]);
    }

    public function markAsProcessed(string $marketplaceOrderId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE orders SET processed_at = NOW() WHERE marketplace_order_id = :id'
        );
        $stmt->execute([':id' => $marketplaceOrderId]);
    }

    public function updateStatus(string $marketplaceOrderId, string $status): void
    {
        $stmt = $this->db->prepare(
            'UPDATE orders SET status = :status WHERE marketplace_order_id = :id'
        );
        $stmt->execute([':status' => $status, ':id' => $marketplaceOrderId]);
    }
}
