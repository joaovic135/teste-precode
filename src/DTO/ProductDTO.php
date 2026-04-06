<?php

declare(strict_types=1);

namespace App\DTO;

use InvalidArgumentException;

class ProductDTO
{
    public function __construct(
        public readonly string $sku,
        public readonly string $name,
        public readonly string $description,
        public readonly string $category,
        public readonly string $brand,
        public readonly float  $price,
        public readonly float  $promotionalPrice,
        public readonly float  $cost,
        public readonly float  $weight,
        public readonly float  $width,
        public readonly float  $height,
        public readonly float  $length,
        public readonly int    $stock,
        public readonly string $ean    = '',
        public readonly array  $images = [],
    ) {}

    public static function fromArray(array $data): self
    {
        $sku  = trim((string) ($data['sku']  ?? ''));
        $name = trim((string) ($data['name'] ?? ''));

        if ($sku === '') {
            throw new InvalidArgumentException('SKU é obrigatório e não pode ser vazio');
        }

        if (strlen($name) < 20) {
            throw new InvalidArgumentException(
                "Nome do produto deve ter no mínimo 20 caracteres, recebido: " . strlen($name)
            );
        }

        $description = trim((string) ($data['description'] ?? ''));

        if (strlen($description) < 100) {
            throw new InvalidArgumentException(
                "Descrição deve ter no mínimo 100 caracteres, recebido: " . strlen($description)
            );
        }

        $brand = trim((string) ($data['brand'] ?? ''));

        if ($brand === '') {
            throw new InvalidArgumentException('Marca (brand) é obrigatória');
        }

        $price = (float) ($data['price'] ?? 0);

        if ($price <= 0) {
            throw new InvalidArgumentException(
                "Preço deve ser maior que zero, recebido: {$price}"
            );
        }

        $cost = (float) ($data['cost'] ?? 0);

        if ($cost <= 0) {
            throw new InvalidArgumentException(
                "Custo deve ser maior que zero, recebido: {$cost}"
            );
        }

        $weight = (float) ($data['weight'] ?? 0);

        if ($weight <= 0) {
            throw new InvalidArgumentException(
                "Peso deve ser maior que zero, recebido: {$weight}"
            );
        }

        $width  = (float) ($data['width']  ?? 0);
        $height = (float) ($data['height'] ?? 0);
        $length = (float) ($data['length'] ?? $data['depth'] ?? 0);

        if ($width <= 0 || $height <= 0 || $length <= 0) {
            throw new InvalidArgumentException(
                'Largura, altura e profundidade devem ser maiores que zero'
            );
        }

        $stock = (int) ($data['stock'] ?? 0);

        if ($stock < 0) {
            throw new InvalidArgumentException(
                "Estoque não pode ser negativo, recebido: {$stock}"
            );
        }

        $promotionalPrice = (float) ($data['promotional_price'] ?? $price);

        if ($promotionalPrice < $price) {
            throw new InvalidArgumentException(
                "Preço promocional ({$promotionalPrice}) não pode ser menor que o preço de venda ({$price})"
            );
        }

        return new self(
            sku:              $sku,
            name:             $name,
            description:      $description,
            category:         trim((string) ($data['category'] ?? '')),
            brand:            $brand,
            price:            $price,
            promotionalPrice: $promotionalPrice,
            cost:             $cost,
            weight:           $weight,
            width:            $width,
            height:           $height,
            length:           $length,
            stock:            $stock,
            ean:              trim((string) ($data['ean'] ?? '')),
            images:           (array) ($data['images'] ?? []),
        );
    }

    public function toMarketplacePayload(): array
    {
        $variation = [
            'ref' => $this->sku,
            'qty' => (string) $this->stock,
        ];

        if ($this->ean !== '') {
            $variation['ean'] = $this->ean;
        }

        if (!empty($this->images)) {
            $variation['images'] = $this->images;
        }

        return [
            'product' => [
                'name'              => $this->name,
                'description'       => $this->description,
                'status'            => 'enabled',
                'price'             => $this->price,
                'promotional_price' => $this->promotionalPrice,
                'cost'              => $this->cost,
                'weight'            => $this->weight,
                'width'             => $this->width,
                'height'            => $this->height,
                'length'            => $this->length,
                'brand'             => $this->brand,
                'category'          => $this->category,
                'variations'        => [$variation],
            ],
        ];
    }
}
