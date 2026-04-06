<?php

declare(strict_types=1);

namespace App\Marketplace;

use App\DTO\OrderDTO;
use App\Http\ApiClient;
use App\Repository\OrderRepository;
use InvalidArgumentException;
use RuntimeException;

class OrderService
{
    private const MAX_QUEUE_PULL     = 50;
    private const FORMA_PAGAMENTO    = '4';
    private const QTD_PARCELAS       = 1;

    public function __construct(
        private OrderRepository $repository,
        private ApiClient       $apiClient,
    ) {}

    // ─── Criação de pedido (POST v1/pedido/pedido) ──────────────────────────────

    /**
     * Cria um pedido no marketplace a partir dos dados enviados pelo formulário.
     *
     * Payload v1:
     * { pedido: { idPedidoParceiro, valorFrete, formaPagamento, dadosCliente,
     *             pagamento: [{ valor, quantidadeParcelas }],
     *             itens: [{ sku, valorUnitario, quantidade }] } }
     */
    public function createOrder(array $data): array
    {
        $this->validateOrderData($data);

        $partnerOrderId = 'HUB-' . date('YmdHis') . '-' . rand(1000, 9999);
        $items          = $data['items'] ?? [];
        $totalItems     = array_sum(array_map(
            fn($i) => (float) ($i['valorUnitario'] ?? 0) * (int) ($i['quantidade'] ?? 1),
            $items,
        ));
        $valorFrete     = (float) ($data['valorFrete'] ?? 0);
        $totalCompra    = round($totalItems + $valorFrete, 2);

        $payload = [
            'pedido' => [
                'idPedidoParceiro' => $partnerOrderId,
                'valorFrete'       => $valorFrete,
                'valorTotalCompra' => $totalCompra,
                'formaPagamento'   => self::FORMA_PAGAMENTO,
                'dadosCliente'     => [
                    'cpfCnpj'    => $data['cpfCnpj'],
                    'nomeRazao'  => $data['nomeRazao'],
                    'fantasia'   => $data['fantasia'] ?? $data['nomeRazao'],
                    'email'      => $data['email'],
                    'dadosEntrega' => [
                        'cep'        => preg_replace('/\D/', '', $data['cep'] ?? ''),
                        'endereco'   => $data['endereco']  ?? '',
                        'numero'     => $data['numero']    ?? 's/n',
                        'bairro'     => $data['bairro']    ?? '',
                        'cidade'     => $data['cidade']    ?? '',
                        'uf'         => strtoupper(substr($data['uf'] ?? '', 0, 2)),
                        'complemento' => $data['complemento'] ?? '',
                    ],
                    'telefones' => [
                        'residencial' => preg_replace('/\D/', '', $data['telefone'] ?? '11900000000'),
                        'celular'     => preg_replace('/\D/', '', $data['celular']   ?? $data['telefone'] ?? '11900000000'),
                    ],
                ],
                'pagamento' => [[
                    'valor'             => $totalCompra,
                    'quantidadeParcelas' => self::QTD_PARCELAS,
                    'meioPagamento'     => 'pix',
                ]],
                'itens' => array_map(fn($i) => [
                    'sku'          => (int) ($i['sku']          ?? 0),
                    'valorUnitario' => (float) ($i['valorUnitario'] ?? 0),
                    'quantidade'   => (int)   ($i['quantidade']   ?? 1),
                ], $items),
            ],
        ];

        $customerName = $data['nomeRazao'];
        $requestData  = ['customer_name' => $customerName, 'total' => $totalCompra, 'items' => $items];

        $dto = new OrderDTO(
            marketplaceOrderId: $partnerOrderId,
            partnerOrderId:     $partnerOrderId,
            status:             OrderDTO::STATUS_NEW,
            customerName:       $customerName,
            total:              $totalCompra,
            origin:             OrderDTO::ORIGIN_LOCAL,
            marketplaceStatus:  'pending',
            items:              $items,
            rawData:            $payload,
        );

        $this->repository->insert($dto);

        try {
            $response     = $this->apiClient->post('v1/pedido/pedido', $payload);
            $pedidoNode   = $response['pedido'] ?? $response;
            $codigoPedido = (int) ($pedidoNode['numeroPedido']
                ?? $pedidoNode['codigoPedido']
                ?? $response['numeroPedido']
                ?? $response['codigoPedido']
                ?? 0);

            if ($codigoPedido > 0) {
                $this->repository->updateCodigoPedido($partnerOrderId, $codigoPedido);
                $finalOrderId = (string) $codigoPedido;
            } else {
                $finalOrderId = $partnerOrderId;
            }

            $this->repository->updateMarketplaceStatus($finalOrderId, 'sent');

            return [
                'partner_order_id'        => $partnerOrderId,
                'marketplace_codigo_pedido' => $codigoPedido > 0 ? $codigoPedido : null,
                'marketplace_status'      => 'sent',
                'api_response'            => $response,
            ];
        } catch (RuntimeException $e) {
            $this->repository->updateMarketplaceStatus($partnerOrderId, 'error', $e->getMessage());

            return [
                'partner_order_id'   => $partnerOrderId,
                'marketplace_status' => 'error',
                'error'              => $e->getMessage(),
            ];
        }
    }

    // ─── Aprovação (PUT v1/pedido/pedido) ──────────────────────────────────────

    public function approveOrder(string $marketplaceOrderId): array
    {
        $order = $this->getOrderOrFail($marketplaceOrderId);

        if ($order['status'] === 'aprovado') {
            throw new InvalidArgumentException("Pedido '{$marketplaceOrderId}' já foi aprovado");
        }

        if ($order['status'] === 'cancelado') {
            throw new InvalidArgumentException("Pedido '{$marketplaceOrderId}' está cancelado e não pode ser aprovado");
        }

        $codigoPedido = (int) ($order['marketplace_codigo_pedido'] ?? 0);

        if ($codigoPedido <= 0) {
            return [
                'marketplace_order_id' => $marketplaceOrderId,
                'approved'             => false,
                'error'                => 'Pedido sem código Precode — não é possível aprovar via API. Recrie o pedido para obter o código.',
            ];
        }

        try {
            $this->apiClient->put('v1/pedido/pedido', [
                'pedido' => [
                    'codigoPedido'     => $codigoPedido,
                    'idPedidoParceiro' => $order['partner_order_id'] ?? '',
                ],
            ]);
        } catch (RuntimeException $e) {
            return [
                'marketplace_order_id' => $marketplaceOrderId,
                'approved'             => false,
                'error'                => $e->getMessage(),
            ];
        }

        $this->repository->approve($marketplaceOrderId);

        return ['marketplace_order_id' => $marketplaceOrderId, 'approved' => true];
    }

    // ─── Cancelamento (DELETE v1/pedido/pedido) ────────────────────────────────

    public function cancelOrder(string $marketplaceOrderId): array
    {
        $order = $this->getOrderOrFail($marketplaceOrderId);

        if ($order['status'] === 'cancelado') {
            throw new InvalidArgumentException("Pedido '{$marketplaceOrderId}' já foi cancelado");
        }

        $codigoPedido = (int) ($order['marketplace_codigo_pedido'] ?? 0);

        if ($codigoPedido <= 0) {
            return [
                'marketplace_order_id' => $marketplaceOrderId,
                'cancelled'            => false,
                'error'                => 'Pedido sem código Precode — não é possível cancelar via API. Recrie o pedido para obter o código.',
            ];
        }

        try {
            $this->apiClient->delete('v1/pedido/pedido', [
                'pedido' => [
                    'codigoPedido'     => $codigoPedido,
                    'idPedidoParceiro' => $order['partner_order_id'] ?? '',
                ],
            ]);
        } catch (RuntimeException $e) {
            return [
                'marketplace_order_id' => $marketplaceOrderId,
                'cancelled'            => false,
                'error'                => $e->getMessage(),
            ];
        }

        $this->repository->cancel($marketplaceOrderId);

        return ['marketplace_order_id' => $marketplaceOrderId, 'cancelled' => true];
    }

    // ─── Sincronização fila v3 (GET v3/orders) ─────────────────────────────────

    public function syncOrders(): array
    {
        $synced = 0;
        $failed = 0;
        $errors = [];
        $pulled = 0;

        while ($pulled < self::MAX_QUEUE_PULL) {
            try {
                $response = $this->apiClient->get('v3/orders');
            } catch (RuntimeException $e) {
                return [
                    'synced'         => $synced,
                    'total_received' => $pulled,
                    'failed'         => $failed,
                    'error'          => $e->getMessage(),
                ];
            }

            if (empty($response)) {
                break;
            }

            $pulled++;

            try {
                $dto      = OrderDTO::fromMarketplaceResponse($response);
                $existing = $this->repository->findByMarketplaceId($dto->marketplaceOrderId);

                if ($existing === null) {
                    $this->repository->insert($dto);
                    $synced++;
                } else {
                    $this->repository->updateStatus($dto->marketplaceOrderId, $dto->status);
                }
            } catch (InvalidArgumentException $e) {
                $failed++;
                $errors[] = $e->getMessage();
            }
        }

        $result = ['synced' => $synced, 'total_received' => $pulled, 'failed' => $failed];

        if (!empty($errors)) {
            $result['parse_errors'] = $errors;
        }

        return $result;
    }

    public function listOrders(): array
    {
        return $this->repository->findAll();
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    private function getOrderOrFail(string $marketplaceOrderId): array
    {
        $order = $this->repository->findByMarketplaceId($marketplaceOrderId);

        if ($order === null) {
            throw new InvalidArgumentException(
                "Pedido não encontrado com ID '{$marketplaceOrderId}'"
            );
        }

        return $order;
    }

    private function validateOrderData(array $data): void
    {
        $required = ['cpfCnpj', 'nomeRazao', 'email', 'cep', 'bairro', 'cidade', 'uf'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("Campo '{$field}' é obrigatório para criação do pedido");
            }
        }

        if (empty($data['items'])) {
            throw new InvalidArgumentException('O pedido deve ter pelo menos um item');
        }

        foreach ($data['items'] as $i => $item) {
            if (empty($item['sku']) || empty($item['valorUnitario']) || empty($item['quantidade'])) {
                throw new InvalidArgumentException("Item #{$i}: sku, valorUnitario e quantidade são obrigatórios");
            }
        }
    }
}
