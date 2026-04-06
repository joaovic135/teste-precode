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
     * Suporta duas estruturas da API:
     *  - pedidoStatus: { numeroPedido, idPedidoParceiro, statusAtual, idStatusAtual }
     *  - pedido completo: { codigoPedido, pedidoParceiro, statusAtual, dadosCliente, ... }
     */
    public static function fromMarketplaceResponse(array $data): self
    {
        $orderId = (string) ($data['numeroPedido'] ?? $data['codigoPedido'] ?? '');

        if ($orderId === '') {
            throw new InvalidArgumentException(
                'Resposta do marketplace não contém um ID de pedido válido'
            );
        }

        $partnerOrderId = (string) ($data['idPedidoParceiro'] ?? $data['pedidoParceiro'] ?? '');
        $customerData   = $data['dadosCliente'] ?? [];
        $customerName   = (string) ($customerData['nomeRazao'] ?? $partnerOrderId);
        $total          = (float) ($data['valorTotalCompra'] ?? 0.0);
        $items          = (array) ($data['itens'] ?? []);

        return new self(
            marketplaceOrderId: $orderId,
            partnerOrderId:     $partnerOrderId,
            status:             (string) ($data['statusAtual'] ?? self::STATUS_NEW),
            customerName:       $customerName,
            total:              $total,
            items:              $items,
            rawData:            $data,
        );
    }
}
