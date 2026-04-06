<?php

declare(strict_types=1);

namespace App\Marketplace;

use App\DTO\PriceStockDTO;
use App\Http\ApiClient;
use App\Repository\ProductRepository;
use App\Repository\UpdateLogRepository;
use InvalidArgumentException;
use RuntimeException;

class PriceStockService
{
    public function __construct(
        private ProductRepository   $productRepository,
        private UpdateLogRepository $updateLogRepository,
        private ApiClient           $apiClient,
    ) {}

    public function updatePrice(string $sku, float $newPrice): array
    {
        $product = $this->productRepository->findBySku($sku);

        if ($product === null) {
            throw new InvalidArgumentException(
                "Nenhum produto encontrado com o SKU '{$sku}'"
            );
        }

        $dto   = PriceStockDTO::forPrice($sku, $newPrice);
        $logId = $this->updateLogRepository->insert(
            $sku,
            PriceStockDTO::TYPE_PRICE,
            (float) $product['price'],
            $newPrice,
        );

        try {
            $response = $this->apiClient->put($dto->marketplaceEndpoint(), $dto->toMarketplacePayload());

            $this->assertApiProductSuccess($response, 'preço');

            $this->productRepository->updatePrice($sku, $newPrice);
            $this->updateLogRepository->markAsSent($logId);

            return ['sku' => $sku, 'status' => 'sent', 'new_price' => $newPrice];
        } catch (RuntimeException $e) {
            $this->updateLogRepository->markAsError($logId, $e->getMessage());

            return ['sku' => $sku, 'status' => 'error', 'error' => $e->getMessage()];
        }
    }

    public function updateStock(string $sku, int $newStock): array
    {
        $product = $this->productRepository->findBySku($sku);

        if ($product === null) {
            throw new InvalidArgumentException(
                "Nenhum produto encontrado com o SKU '{$sku}'"
            );
        }

        $dto   = PriceStockDTO::forStock($sku, $newStock);
        $logId = $this->updateLogRepository->insert(
            $sku,
            PriceStockDTO::TYPE_STOCK,
            (float) $product['stock'],
            (float) $newStock,
        );

        try {
            $response = $this->apiClient->put($dto->marketplaceEndpoint(), $dto->toMarketplacePayload());

            $this->assertApiProductSuccess($response, 'estoque');

            $this->productRepository->updateStock($sku, $newStock);
            $this->updateLogRepository->markAsSent($logId);

            return ['sku' => $sku, 'status' => 'sent', 'new_stock' => $newStock];
        } catch (RuntimeException $e) {
            $this->updateLogRepository->markAsError($logId, $e->getMessage());

            return ['sku' => $sku, 'status' => 'error', 'error' => $e->getMessage()];
        }
    }

    public function listUpdateHistory(): array
    {
        return $this->updateLogRepository->findAll();
    }

    /**
     * A Precode retorna HTTP 200 mesmo em falha de negócio, indicando o erro via
     * idMensagem != 0 dentro de produto[0]. Lança RuntimeException se o campo indicar falha.
     */
    private function assertApiProductSuccess(array $response, string $context): void
    {
        $first = $response['produto'][0] ?? null;

        if ($first === null) {
            return;
        }

        $idMensagem = (int) ($first['idMensagem'] ?? 0);

        if ($idMensagem !== 0) {
            $mensagem = (string) ($first['mensagem'] ?? 'erro desconhecido da API');
            throw new RuntimeException(
                "Falha ao atualizar {$context} no marketplace: {$mensagem} (código {$idMensagem})"
            );
        }
    }
}
