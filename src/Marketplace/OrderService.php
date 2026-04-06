<?php

declare(strict_types=1);

namespace App\Marketplace;

use App\DTO\OrderDTO;
use App\Http\ApiClient;
use App\Repository\OrderRepository;
use InvalidArgumentException;
use RuntimeException;

class OrderService
{
    private const MAX_QUEUE_PULL = 50;

    public function __construct(
        private OrderRepository $repository,
        private ApiClient       $apiClient,
    ) {}

    /**
     * Drena a fila da API v3 (GET v3/orders) importando cada pedido localmente.
     *
     * A fila retorna HTTP 204 (corpo vazio → array vazio) quando não há mais pedidos.
     * Os pedidos NÃO são removidos da fila aqui — isso ocorre em processOrder().
     */
    public function syncOrders(): array
    {
        $synced = 0;
        $failed = 0;
        $errors = [];
        $pulled = 0;

        while ($pulled < self::MAX_QUEUE_PULL) {
            try {
                $response = $this->apiClient->get('orders');
            } catch (RuntimeException $e) {
                return [
                    'synced'         => $synced,
                    'total_received' => $pulled,
                    'failed'         => $failed,
                    'error'          => $e->getMessage(),
                ];
            }

            if (empty($response)) {
                break;
            }

            $pulled++;

            try {
                $dto      = OrderDTO::fromMarketplaceResponse($response);
                $existing = $this->repository->findByMarketplaceId($dto->marketplaceOrderId);

                if ($existing === null) {
                    $this->repository->insert($dto);
                    $synced++;
                } else {
                    $this->repository->updateStatus($dto->marketplaceOrderId, $dto->status);
                }
            } catch (InvalidArgumentException $e) {
                $failed++;
                $errors[] = $e->getMessage();
            }
        }

        $result = ['synced' => $synced, 'total_received' => $pulled, 'failed' => $failed];

        if (!empty($errors)) {
            $result['parse_errors'] = $errors;
        }

        return $result;
    }

    /**
     * Processa um pedido: informa o número ERP ao marketplace e remove da fila.
     *
     * Fluxo v3:
     *  1. POST v3/orders/{id}/pedidoerp  → informa que o pedido foi gerado no ERP
     *  2. DELETE v3/orders/{id}          → remove da fila de processamento
     */
    public function processOrder(string $marketplaceOrderId): array
    {
        $order = $this->repository->findByMarketplaceId($marketplaceOrderId);

        if ($order === null) {
            throw new InvalidArgumentException(
                "Pedido não encontrado com ID de marketplace '{$marketplaceOrderId}'"
            );
        }

        if ($order['processed_at'] !== null) {
            throw new InvalidArgumentException(
                "O pedido '{$marketplaceOrderId}' já foi processado"
            );
        }

        try {
            $erpResponse = $this->apiClient->post(
                "orders/{$marketplaceOrderId}/pedidoerp",
                ['pedidoerp' => (int) $order['id'], 'fp' => 1, 'flf' => 1],
            );

            $this->assertOrderApiSuccess($erpResponse, 'pedidoerp');

            $this->apiClient->delete("orders/{$marketplaceOrderId}");
        } catch (RuntimeException $e) {
            return [
                'marketplace_order_id' => $marketplaceOrderId,
                'processed'            => false,
                'error'                => $e->getMessage(),
            ];
        }

        $this->repository->markAsProcessed($marketplaceOrderId);

        return ['marketplace_order_id' => $marketplaceOrderId, 'processed' => true];
    }

    public function listOrders(): array
    {
        return $this->repository->findAll();
    }

    /**
     * Verifica resposta de endpoints de pedido v3:
     * { "Code": "complete"|"order_error", "Type": "SUCCESS"|"ERROR", "Label": "..." }
     */
    private function assertOrderApiSuccess(array $response, string $context): void
    {
        $type = strtoupper((string) ($response['Type'] ?? $response['type'] ?? 'SUCCESS'));

        if ($type === 'ERROR') {
            $label = (string) ($response['Label'] ?? $response['label'] ?? 'erro desconhecido');
            throw new RuntimeException(
                "Falha ao executar {$context} no marketplace: {$label}"
            );
        }
    }
}
