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
    private const SYNC_WINDOW_DAYS = 30;

    public function __construct(
        private OrderRepository $repository,
        private ApiClient       $apiClient,
    ) {}

    public function syncOrders(): array
    {
        $today     = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime('-' . self::SYNC_WINDOW_DAYS . ' days'));

        try {
            $response = $this->apiClient->get("pedido/pedidoStatus/{$startDate}/{$today}");
        } catch (RuntimeException $e) {
            return ['synced' => 0, 'total_received' => 0, 'failed' => 0, 'error' => $e->getMessage()];
        }

        $orders = $this->extractOrdersList($response);
        $synced = 0;
        $failed = 0;
        $errors = [];

        foreach ($orders as $orderData) {
            try {
                $dto      = OrderDTO::fromMarketplaceResponse($orderData);
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

        $result = ['synced' => $synced, 'total_received' => count($orders), 'failed' => $failed];

        if ($failed > 0) {
            $result['parse_errors'] = $errors;
        }

        return $result;
    }

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

        $this->apiClient->put('pedido/pedido', [
            'pedido' => [
                'codigoPedido'    => (int) $marketplaceOrderId,
                'idPedidoParceiro' => '',
            ],
        ]);

        $this->repository->markAsProcessed($marketplaceOrderId);

        return ['marketplace_order_id' => $marketplaceOrderId, 'processed' => true];
    }

    public function listOrders(): array
    {
        return $this->repository->findAll();
    }

    private function extractOrdersList(array $response): array
    {
        if (isset($response['pedido']) && is_array($response['pedido'])) {
            return $response['pedido'];
        }

        if (isset($response['pedidos']) && is_array($response['pedidos'])) {
            return $response['pedidos'];
        }

        if (array_is_list($response)) {
            return $response;
        }

        return [];
    }
}
