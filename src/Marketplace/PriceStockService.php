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
            $this->apiClient->put($dto->marketplaceEndpoint(), $dto->toMarketplacePayload());
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
            $this->apiClient->put($dto->marketplaceEndpoint(), $dto->toMarketplacePayload());
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
}
