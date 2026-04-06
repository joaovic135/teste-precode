<?php

declare(strict_types=1);

namespace App\Marketplace;

use App\DTO\ProductDTO;
use App\Http\ApiClient;
use App\Repository\ProductRepository;
use InvalidArgumentException;
use RuntimeException;

class ProductService
{
    public function __construct(
        private ProductRepository $repository,
        private ApiClient         $apiClient,
    ) {}

    public function registerProduct(array $data): array
    {
        $dto = ProductDTO::fromArray($data);

        if ($this->repository->findBySku($dto->sku) !== null) {
            throw new InvalidArgumentException(
                "Já existe um produto com o SKU '{$dto->sku}'"
            );
        }

        $this->repository->insert($dto);

        try {
            $this->apiClient->post('products', $dto->toMarketplacePayload());
            $this->repository->updateMarketplaceStatus($dto->sku, 'sent');

            return ['sku' => $dto->sku, 'marketplace_status' => 'sent'];
        } catch (RuntimeException $e) {
            $this->repository->updateMarketplaceStatus($dto->sku, 'error', $e->getMessage());

            return ['sku' => $dto->sku, 'marketplace_status' => 'error', 'error' => $e->getMessage()];
        }
    }

    public function listProducts(): array
    {
        return $this->repository->findAll();
    }
}
