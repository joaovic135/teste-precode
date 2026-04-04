<?php

declare(strict_types=1);

namespace App\Http;

use App\Marketplace\OrderService;
use App\Marketplace\PriceStockService;
use App\Marketplace\ProductService;
use RuntimeException;

class Router
{
    public function __construct(
        private ProductService    $productService,
        private PriceStockService $priceStockService,
        private OrderService      $orderService,
    ) {}

    public function dispatch(string $method, string $uri): mixed
    {
        $body     = $this->parseBody($method);
        $segments = $this->parseSegments($uri);

        return match (true) {
            $method === 'GET'  && $segments === ['api', 'products']
                => $this->productService->listProducts(),

            $method === 'POST' && $segments === ['api', 'products']
                => $this->productService->registerProduct($body),

            $method === 'PUT'  && $this->matches($segments, ['api', 'products', '*', 'price'])
                => $this->priceStockService->updatePrice(
                    urldecode($segments[2]),
                    $this->requireFloat($body, 'price'),
                ),

            $method === 'PUT'  && $this->matches($segments, ['api', 'products', '*', 'stock'])
                => $this->priceStockService->updateStock(
                    urldecode($segments[2]),
                    $this->requireInt($body, 'stock'),
                ),

            $method === 'GET'  && $segments === ['api', 'updates']
                => $this->priceStockService->listUpdateHistory(),

            $method === 'GET'  && $segments === ['api', 'orders']
                => $this->orderService->listOrders(),

            $method === 'POST' && $segments === ['api', 'orders', 'sync']
                => $this->orderService->syncOrders(),

            $method === 'POST' && $this->matches($segments, ['api', 'orders', '*', 'process'])
                => $this->orderService->processOrder(urldecode($segments[2])),

            default => throw new RuntimeException(
                "Rota não encontrada: {$method} {$uri}",
                404,
            ),
        };
    }

    private function parseSegments(string $uri): array
    {
        return array_values(array_filter(explode('/', trim($uri, '/'))));
    }

    private function parseBody(string $method): array
    {
        if (!in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            return [];
        }

        $raw     = file_get_contents('php://input');
        $decoded = json_decode($raw ?: '{}', true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException(
                'O corpo da requisição não é um JSON válido: ' . json_last_error_msg()
            );
        }

        return $decoded ?? [];
    }

    private function matches(array $segments, array $pattern): bool
    {
        if (count($segments) !== count($pattern)) {
            return false;
        }

        foreach ($pattern as $i => $part) {
            if ($part !== '*' && $segments[$i] !== $part) {
                return false;
            }
        }

        return true;
    }

    private function requireFloat(array $body, string $field): float
    {
        if (!isset($body[$field])) {
            throw new \InvalidArgumentException(
                "O campo '{$field}' é obrigatório no corpo da requisição"
            );
        }

        return (float) $body[$field];
    }

    private function requireInt(array $body, string $field): int
    {
        if (!isset($body[$field])) {
            throw new \InvalidArgumentException(
                "O campo '{$field}' é obrigatório no corpo da requisição"
            );
        }

        return (int) $body[$field];
    }
}
