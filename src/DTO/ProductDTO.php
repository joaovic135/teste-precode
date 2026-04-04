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
        public readonly float  $price,
        public readonly int    $stock,
        public readonly array  $images = [],
    ) {}

    public static function fromArray(array $data): self
    {
        $sku  = trim((string) ($data['sku']  ?? ''));
        $name = trim((string) ($data['name'] ?? ''));

        if ($sku === '') {
            throw new InvalidArgumentException('SKU é obrigatório e não pode ser vazio');
        }

        if ($name === '') {
            throw new InvalidArgumentException('Nome do produto é obrigatório e não pode ser vazio');
        }

        $price = (float) ($data['price'] ?? 0);

        if ($price <= 0) {
            throw new InvalidArgumentException(
                "Preço deve ser maior que zero, recebido: {$price}"
            );
        }

        $stock = (int) ($data['stock'] ?? 0);

        if ($stock < 0) {
            throw new InvalidArgumentException(
                "Estoque não pode ser negativo, recebido: {$stock}"
            );
        }

        return new self(
            sku:         $sku,
            name:        $name,
            description: trim((string) ($data['description'] ?? '')),
            category:    trim((string) ($data['category']    ?? '')),
            price:       $price,
            stock:       $stock,
            images:      (array) ($data['images'] ?? []),
        );
    }

    public function toMarketplacePayload(): array
    {
        return [
            'sku'      => $this->sku,
            'nome'     => $this->name,
            'descricao' => $this->description,
            'categoria' => $this->category,
            'preco'    => $this->price,
            'estoque'  => $this->stock,
            'imagens'  => $this->images,
        ];
    }
}
