<?php

declare(strict_types=1);

namespace App\Repository;

use App\Config\Database;
use App\DTO\ProductDTO;
use PDO;

class ProductRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function findAll(): array
    {
        return $this->db
            ->query('SELECT * FROM products ORDER BY created_at DESC')
            ->fetchAll();
    }

    public function findBySku(string $sku): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM products WHERE sku = :sku');
        $stmt->execute([':sku' => $sku]);

        $result = $stmt->fetch();

        return $result !== false ? $result : null;
    }

    public function insert(ProductDTO $dto): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO products (sku, name, description, category, price, stock, images)
             VALUES (:sku, :name, :description, :category, :price, :stock, :images)'
        );

        $stmt->execute([
            ':sku'         => $dto->sku,
            ':name'        => $dto->name,
            ':description' => $dto->description,
            ':category'    => $dto->category,
            ':price'       => $dto->price,
            ':stock'       => $dto->stock,
            ':images'      => json_encode($dto->images),
        ]);
    }

    public function updateMarketplaceStatus(string $sku, string $status, ?string $error = null): void
    {
        $stmt = $this->db->prepare(
            'UPDATE products
             SET marketplace_status = :status, marketplace_error = :error
             WHERE sku = :sku'
        );

        $stmt->execute([':status' => $status, ':error' => $error, ':sku' => $sku]);
    }

    public function updatePrice(string $sku, float $price): void
    {
        $stmt = $this->db->prepare('UPDATE products SET price = :price WHERE sku = :sku');
        $stmt->execute([':price' => $price, ':sku' => $sku]);
    }

    public function updateStock(string $sku, int $stock): void
    {
        $stmt = $this->db->prepare('UPDATE products SET stock = :stock WHERE sku = :sku');
        $stmt->execute([':stock' => $stock, ':sku' => $sku]);
    }
}
