<?php

declare(strict_types=1);

namespace App\DTO;

use InvalidArgumentException;

class OrderDTO
{
    public const STATUS_NEW      = 'novo';
    public const ORIGIN_LOCAL    = 'local';
    public const ORIGIN_INCOMING = 'incoming';

    public function __construct(
        public readonly string  $marketplaceOrderId,
        public readonly string  $partnerOrderId,
        public readonly string  $status,
        public readonly string  $customerName,
        public readonly float   $total,
        public readonly string  $origin              = self::ORIGIN_INCOMING,
        public readonly ?int    $codigoPedido        = null,
        public readonly string  $marketplaceStatus   = 'pending',
        public readonly ?string $marketplaceError    = null,
        public readonly array   $items               = [],
        public readonly array   $rawData             = [],
    ) {}

    /**
     * Constrói a partir da estrutura de fila da API v3 (GET v3/orders).
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
        $statusRaw      = $data['status'] ?? [];
        $statusLabel    = is_array($statusRaw)
            ? (string) ($statusRaw['type'] ?? $statusRaw['label'] ?? self::STATUS_NEW)
            : (string) $statusRaw;

        $customer     = $data['customer'] ?? $data['dadosCliente'] ?? [];
        $customerName = (string) ($customer['name'] ?? $customer['nomeRazao'] ?? $partnerOrderId);
        $total        = (float) ($data['total_ordered'] ?? $data['valorTotalCompra'] ?? 0.0);
        $items        = (array) ($data['items'] ?? $data['itens'] ?? []);

        return new self(
            marketplaceOrderId: $orderId,
            partnerOrderId:     $partnerOrderId,
            status:             $statusLabel,
            customerName:       $customerName,
            total:              $total,
            origin:             self::ORIGIN_INCOMING,
            marketplaceStatus:  'sent',
            items:              $items,
            rawData:            $data,
        );
    }

    /**
     * Constrói a partir da resposta de POST v1/pedido/pedido (criação pelo nosso app).
     */
    public static function fromCreateResponse(string $partnerOrderId, array $apiResponse, array $requestData): self
    {
        $pedidoNode   = $apiResponse['pedido'] ?? $apiResponse;
        $codigoPedido = (int) ($pedidoNode['numeroPedido'] ?? $pedidoNode['codigoPedido']
            ?? $apiResponse['numeroPedido']    ?? $apiResponse['codigoPedido']    ?? 0);
        $status       = (string) ($apiResponse['statusAtual'] ?? self::STATUS_NEW);
        $customerName = (string) ($requestData['customer_name'] ?? '');
        $total        = (float) ($requestData['total'] ?? 0.0);

        return new self(
            marketplaceOrderId: $codigoPedido > 0 ? (string) $codigoPedido : $partnerOrderId,
            partnerOrderId:     $partnerOrderId,
            status:             $status,
            customerName:       $customerName,
            total:              $total,
            origin:             self::ORIGIN_LOCAL,
            codigoPedido:       $codigoPedido > 0 ? $codigoPedido : null,
            marketplaceStatus:  'sent',
            items:              $requestData['items'] ?? [],
            rawData:            $apiResponse,
        );
    }
}
