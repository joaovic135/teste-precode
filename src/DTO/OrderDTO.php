<?php

declare(strict_types=1);

namespace App\DTO;

use InvalidArgumentException;

class OrderDTO
{
    public const STATUS_NEW = 'novo';

    public function __construct(
        public readonly string $marketplaceOrderId,
        public readonly string $status,
        public readonly string $customerName,
        public readonly string $customerEmail,
        public readonly float  $total,
        public readonly array  $items,
        public readonly array  $rawData,
    ) {}

    public static function fromMarketplaceResponse(array $data): self
    {
        $orderId = (string) ($data['id'] ?? $data['pedido_id'] ?? '');

        if ($orderId === '') {
            throw new InvalidArgumentException(
                'Resposta do marketplace não contém um ID de pedido válido'
            );
        }

        return new self(
            marketplaceOrderId: $orderId,
            status:             (string) ($data['status'] ?? self::STATUS_NEW),
            customerName:       (string) ($data['cliente']['nome']  ?? $data['cliente']['name'] ?? ''),
            customerEmail:      (string) ($data['cliente']['email'] ?? ''),
            total:              (float)  ($data['total'] ?? $data['valor_total'] ?? 0),
            items:              (array)  ($data['itens'] ?? $data['items'] ?? []),
            rawData:            $data,
        );
    }
}
