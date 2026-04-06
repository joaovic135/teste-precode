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

        $dto   = PriceStockDTO::withNewPrice($product, $newPrice);
        $logId = $this->updateLogRepository->insert(
            $sku,
            PriceStockDTO::TYPE_PRICE,
            (float) $product['price'],
            $newPrice,
        );

        try {
            $response = $this->apiClient->put($dto->marketplaceEndpoint(), $dto->toMarketplacePayload());

            $this->assertInventorySuccess($response);

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

        $dto   = PriceStockDTO::withNewStock($product, $newStock);
        $logId = $this->updateLogRepository->insert(
            $sku,
            PriceStockDTO::TYPE_STOCK,
            (float) $product['stock'],
            (float) $newStock,
        );

        try {
            $response = $this->apiClient->put($dto->marketplaceEndpoint(), $dto->toMarketplacePayload());

            $this->assertInventorySuccess($response);

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
     * A API v3 retorna HTTP 200 mesmo em falha de negócio via products[0].return[0].code.
     * code = 0 → sucesso; qualquer outro valor → mensagem de erro.
     */
    private function assertInventorySuccess(array $response): void
    {
        $first  = $response['products'][0] ?? null;

        if ($first === null) {
            return;
        }

        $ret  = $first['return'][0] ?? null;

        if ($ret === null) {
            return;
        }

        $code = (int) ($ret['code'] ?? 0);

        if ($code !== 0) {
            $message = (string) ($ret['message'] ?? 'erro desconhecido da API');
            throw new RuntimeException(
                "Falha ao atualizar inventário no marketplace: {$message} (código {$code})"
            );
        }
    }
}
