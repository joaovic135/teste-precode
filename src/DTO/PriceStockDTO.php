<?php

declare(strict_types=1);

namespace App\DTO;

use InvalidArgumentException;

class PriceStockDTO
{
    public const TYPE_PRICE = 'price';
    public const TYPE_STOCK = 'stock';

    private const FILIAL_PADRAO = 1;

    public function __construct(
        public readonly string $sku,
        public readonly string $type,
        public readonly float  $value,
    ) {}

    public static function forPrice(string $sku, float $price): self
    {
        if ($price <= 0) {
            throw new InvalidArgumentException(
                "Preço deve ser maior que zero, recebido: {$price}"
            );
        }

        return new self(sku: $sku, type: self::TYPE_PRICE, value: $price);
    }

    public static function forStock(string $sku, int $stock): self
    {
        if ($stock < 0) {
            throw new InvalidArgumentException(
                "Estoque não pode ser negativo, recebido: {$stock}"
            );
        }

        return new self(sku: $sku, type: self::TYPE_STOCK, value: (float) $stock);
    }

    public function toMarketplacePayload(): array
    {
        if ($this->type === self::TYPE_PRICE) {
            return [
                'produto' => [[
                    'IdReferencia' => $this->sku,
                    'precoDe'      => $this->value,
                    'precoVenda'   => $this->value,
                ]],
            ];
        }

        $qty = (int) $this->value;

        return [
            'produto' => [[
                'IdReferencia' => $this->sku,
                'estoque'      => [[
                    'filialSaldo'      => self::FILIAL_PADRAO,
                    'saldoReal'        => $qty,
                    'saldoDisponivel'  => $qty,
                    'prazoAdicional'   => 0,
                    'tipoSaldo'        => 'A',
                ]],
            ]],
        ];
    }

    public function marketplaceEndpoint(): string
    {
        if ($this->type === self::TYPE_PRICE) {
            return 'produtoLoja/preco';
        }

        return 'produtoLoja/saldo';
    }
}
