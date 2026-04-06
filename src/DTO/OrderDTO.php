<?php

declare(strict_types=1);

namespace App\DTO;

use InvalidArgumentException;

class OrderDTO
{
    public const STATUS_NEW = 'novo';

    public function __construct(
        public readonly string $marketplaceOrderId,
        public readonly string $partnerOrderId,
        public readonly string $status,
        public readonly string $customerName,
        public readonly float  $total,
        public readonly array  $items   = [],
        public readonly array  $rawData = [],
    ) {}

    /**
     * Constrói a partir da estrutura de fila da API v3 (GET v3/orders):
     *
     * {
     *   "code": "123456",
     *   "cod_partner": "parceiro-001",
     *   "status": { "type": "novo", "label": "Novo" },
     *   "customer": { "name": "João", "email": "..." },
     *   "total_ordered": 199.90,
     *   "items": [{ "product_id": "", "name": "", "qty": "", ... }]
     * }
     */
    public static function fromMarketplaceResponse(array $data): self
    {
        $orderId = (string) ($data['code'] ?? $data['numeroPedido'] ?? $data['codigoPedido'] ?? '');

        if ($orderId === '') {
            throw new InvalidArgumentException(
                'Resposta do marketplace não contém um ID de pedido válido'
            );
        }

        $partnerOrderId = (string) ($data['cod_partner'] ?? $data['idPedidoParceiro'] ?? $data['pedidoParceiro'] ?? '');

        $statusRaw  = $data['status'] ?? [];
        $statusLabel = is_array($statusRaw)
            ? (string) ($statusRaw['type'] ?? $statusRaw['label'] ?? self::STATUS_NEW)
            : (string) $statusRaw;

        $customer     = $data['customer'] ?? $data['dadosCliente'] ?? [];
        $customerName = (string) ($customer['name'] ?? $customer['nomeRazao'] ?? $partnerOrderId);

        $total = (float) ($data['total_ordered'] ?? $data['valorTotalCompra'] ?? 0.0);
        $items = (array) ($data['items'] ?? $data['itens'] ?? []);

        return new self(
            marketplaceOrderId: $orderId,
            partnerOrderId:     $partnerOrderId,
            status:             $statusLabel,
            customerName:       $customerName,
            total:              $total,
            items:              $items,
            rawData:            $data,
        );
    }
}
