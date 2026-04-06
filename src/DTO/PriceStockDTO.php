<?php

declare(strict_types=1);

namespace App\DTO;

use InvalidArgumentException;

/**
 * DTO para atualização de inventário via PUT v3/products/inventory.
 *
 * A API v3 unificou preço e estoque em um único endpoint — é necessário
 * enviar todos os campos mesmo quando apenas um deles é alterado.
 */
class PriceStockDTO
{
    public const TYPE_PRICE = 'price';
    public const TYPE_STOCK = 'stock';

    private const STORES_DEFAULT = 1;

    public function __construct(
        public readonly string $ref,
        public readonly string $type,
        public readonly float  $price,
        public readonly float  $promotionalPrice,
        public readonly float  $cost,
        public readonly int    $stock,
        public readonly string $status       = 'enabled',
        public readonly int    $shippingTime = 0,
    ) {}

    /**
     * Cria DTO para atualizar preço, mantendo estoque/custo atuais do produto.
     */
    public static function withNewPrice(array $product, float $newPrice): self
    {
        if ($newPrice <= 0) {
            throw new InvalidArgumentException(
                "Preço deve ser maior que zero, recebido: {$newPrice}"
            );
        }

        $currentPromo = (float) $product['promotional_price'];
        $promotionalPrice = $currentPromo >= $newPrice ? $currentPromo : $newPrice;

        return new self(
            ref:              (string) $product['sku'],
            type:             self::TYPE_PRICE,
            price:            $newPrice,
            promotionalPrice: $promotionalPrice,
            cost:             (float) $product['cost'],
            stock:            (int)   $product['stock'],
        );
    }

    /**
     * Cria DTO para atualizar estoque, mantendo preços/custo atuais do produto.
     */
    public static function withNewStock(array $product, int $newStock): self
    {
        if ($newStock < 0) {
            throw new InvalidArgumentException(
                "Estoque não pode ser negativo, recebido: {$newStock}"
            );
        }

        return new self(
            ref:              (string) $product['sku'],
            type:             self::TYPE_STOCK,
            price:            (float) $product['price'],
            promotionalPrice: (float) $product['promotional_price'],
            cost:             (float) $product['cost'],
            stock:            $newStock,
        );
    }

    public function toMarketplacePayload(): array
    {
        return [
            'products' => [[
                'ref'               => $this->ref,
                'price'             => $this->price,
                'promotional_price' => $this->promotionalPrice,
                'cost'              => $this->cost,
                'shippingTime'      => $this->shippingTime,
                'status'            => $this->status,
                'stock'             => [[
                    'stores'         => self::STORES_DEFAULT,
                    'availableStock' => $this->stock,
                    'realStock'      => $this->stock,
                ]],
            ]],
        ];
    }

    public function marketplaceEndpoint(): string
    {
        return 'v3/products/inventory';
    }
}
