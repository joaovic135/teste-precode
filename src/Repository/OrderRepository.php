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

    public function insert(OrderDTO $dto): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO orders
                (marketplace_order_id, marketplace_codigo_pedido, partner_order_id,
                 origin, status, customer_name, total, items, raw_data,
                 marketplace_status, marketplace_error)
             VALUES
                (:marketplace_order_id, :marketplace_codigo_pedido, :partner_order_id,
                 :origin, :status, :customer_name, :total, :items, :raw_data,
                 :marketplace_status, :marketplace_error)
             RETURNING id'
        );

        $stmt->execute([
            ':marketplace_order_id'      => $dto->marketplaceOrderId,
            ':marketplace_codigo_pedido' => $dto->codigoPedido,
            ':partner_order_id'          => $dto->partnerOrderId,
            ':origin'                    => $dto->origin,
            ':status'                    => $dto->status,
            ':customer_name'             => $dto->customerName,
            ':total'                     => $dto->total,
            ':items'                     => json_encode($dto->items),
            ':raw_data'                  => json_encode($dto->rawData),
            ':marketplace_status'        => $dto->marketplaceStatus,
            ':marketplace_error'         => $dto->marketplaceError,
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function updateMarketplaceStatus(string $marketplaceOrderId, string $status, ?string $error = null): void
    {
        $stmt = $this->db->prepare(
            'UPDATE orders
             SET marketplace_status = :status, marketplace_error = :error
             WHERE marketplace_order_id = :id'
        );
        $stmt->execute([':status' => $status, ':error' => $error, ':id' => $marketplaceOrderId]);
    }

    public function updateCodigoPedido(string $marketplaceOrderId, int $codigoPedido): void
    {
        $stmt = $this->db->prepare(
            'UPDATE orders
             SET marketplace_codigo_pedido = :codigo, marketplace_order_id = :new_id
             WHERE marketplace_order_id = :old_id'
        );
        $stmt->execute([
            ':codigo'  => $codigoPedido,
            ':new_id'  => (string) $codigoPedido,
            ':old_id'  => $marketplaceOrderId,
        ]);
    }

    public function approve(string $marketplaceOrderId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE orders
             SET status = 'aprovado', approved_at = NOW()
             WHERE marketplace_order_id = :id"
        );
        $stmt->execute([':id' => $marketplaceOrderId]);
    }

    public function cancel(string $marketplaceOrderId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE orders
             SET status = 'cancelado', cancelled_at = NOW()
             WHERE marketplace_order_id = :id"
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
