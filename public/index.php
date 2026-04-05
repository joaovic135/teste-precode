<?php

declare(strict_types=1);

use App\Config\Environment;
use App\Http\ApiClient;
use App\Http\Router;
use App\Marketplace\OrderService;
use App\Marketplace\PriceStockService;
use App\Marketplace\ProductService;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UpdateLogRepository;

require_once __DIR__ . '/../vendor/autoload.php';

Environment::load(__DIR__ . '/../.env');

$requestUri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$requestMethod = $_SERVER['REQUEST_METHOD'];

if (in_array($requestUri, ['/', '/index.php', ''], true)) {
    require __DIR__ . '/views/dashboard.php';
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$apiClient = new ApiClient(
    $_ENV['MARKETPLACE_API_URL']   ?? 'https://www.replicade.com.br/api/v1',
    $_ENV['MARKETPLACE_API_TOKEN'] ?? 'Basic aXdPMzVLZ09EZnRvOHY3M1I6',
);

$productRepository = new ProductRepository();

$router = new Router(
    new ProductService($productRepository, $apiClient),
    new PriceStockService($productRepository, new UpdateLogRepository(), $apiClient),
    new OrderService(new OrderRepository(), $apiClient),
);

try {
    $result = $router->dispatch($requestMethod, $requestUri);
    echo json_encode(['success' => true, 'data' => $result]);
} catch (\InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (\RuntimeException $e) {
    $statusCode = $e->getCode() === 404 ? 404 : 500;
    http_response_code($statusCode);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
