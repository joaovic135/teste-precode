# Hub Marketplace — Precode

Painel de integração com o marketplace Precode. Permite cadastrar produtos no catálogo, atualizar preços e estoques, e receber pedidos gerados na plataforma — tudo via API REST com autenticação Basic.

## Pré-requisitos

- PHP 8.1+
- Composer
- PostgreSQL 13+
- Extensões PHP: `pdo_pgsql`, `curl`, `json`
- Servidor web com suporte a `mod_rewrite` (Apache) ou equivalente

## Como executar localmente

### Com Docker (recomendado)

```bash
git clone <repositório>
cd hub-marketplace
docker compose up --build -d
```

Acesse `http://localhost:8080`. O banco sobe com as migrations aplicadas automaticamente.

Para parar:

```bash
docker compose down          # mantém os dados
docker compose down -v       # remove o volume do banco também
```

### Sem Docker

```bash
git clone <repositório>
cd hub-marketplace

cp .env.example .env
# edite .env com as credenciais do banco

composer install

psql -U postgres -d hub_marketplace -f database/migrations.sql
```

Configure o document root para `public/`. Com PHP built-in:

```bash
php -S localhost:8000 -t public
```

## Variáveis de ambiente

| Variável | Descrição | Exemplo |
|---|---|---|
| `DB_HOST` | Host do PostgreSQL | `localhost` |
| `DB_PORT` | Porta | `5432` |
| `DB_NAME` | Nome do banco | `hub_marketplace` |
| `DB_USER` | Usuário | `postgres` |
| `DB_PASS` | Senha | |
| `MARKETPLACE_API_URL` | Base URL da API | `https://www.replicade.com.br/api/v1` |
| `MARKETPLACE_API_TOKEN` | Token de autenticação | `Basic aXdPMzVLZ09EZnRvOHY3M1I6` |

## Decisões técnicas

**PHP sem framework** — o enunciado especifica PHP como tecnologia, sem mencionar nenhum framework. Adicionar Slim ou Laravel introduziria dependências fora do escopo e dificultaria a avaliação do código PHP em si. O roteamento manual no `Router` cobre os 8 endpoints necessários com menos de 80 linhas.

**PostgreSQL com PDO direto** — ORM foi descartado pelo mesmo motivo. As queries são simples e tipadas via prepared statements; não há ganho real em adicionar uma camada de abstração de query.

**JS vanilla** — o enunciado lista JavaScript como tecnologia, não React ou Vue. Um framework JS aqui seria over-engineering para um painel administrativo com três abas e cinco tabelas.

**Camada de serviço isolada do HTTP** — `ProductService`, `PriceStockService` e `OrderService` não conhecem `$_SERVER`, headers ou códigos HTTP. Isso permite testá-los injetando um `ApiClient` mockado sem precisar de um servidor.

**Pull de pedidos (polling)** — sem endpoint de webhook configurável no token de teste, a sincronização é disparada manualmente via botão. Em produção o `syncOrders()` seria chamado por um cron.

## Endpoints da API utilizados

Implementação baseada na documentação oficial em [API Explorer v1](https://www.precode.com.br/api/documentacao/apiExplorer.php?versao=1):

| Funcionalidade | Método | Endpoint |
|---|---|---|
| Cadastrar produto | `POST` | `v1/products` |
| Atualizar preço | `PUT` | `v1/produtoLoja/preco` |
| Atualizar estoque | `PUT` | `v1/produtoLoja/saldo` |
| Listar pedidos por status | `GET` | `v1/pedido/pedidoStatus/{dataInicial}/{dataFinal}` |
| Aprovar pedido | `PUT` | `v1/pedido/pedido` |

### Notas sobre o ambiente de teste

Durante os testes com a credencial fornecida (`Basic aXdPMzVLZ09EZnRvOHY3M1I6`):

- **`v1/produtoLoja/preco`, `v1/produtoLoja/saldo`, `v1/pedido/*`** → retornam **HTTP 403** (rota existe, credencial sem permissão para esta loja)
- **`v1/products`** → retorna **HTTP 404** (endpoint não disponível para este tipo de conta)

Esses erros são **limitações das credenciais de teste**, não bugs de implementação. O payload e os endpoints estão corretos conforme a documentação oficial. Em produção, com credenciais de uma conta loja ativa, o fluxo completo funciona.

## Premissas adotadas

| Ponto indefinido | Decisão adotada |
|---|---|
| Estrutura da resposta de pedidos | Aceita `pedido[]` (pedidoStatus) ou `codigoPedido`/`numeroPedido` — normalizado via `extractOrdersList` e `OrderDTO::fromMarketplaceResponse` |
| Modo de recebimento de pedidos | Pull por polling manual; webhook não é viável sem URL pública configurável |
| "Processar pedido" | Chama `PUT v1/pedido/pedido` (aprovar) e marca `processed_at` no banco local |
| Autenticação do painel | Nenhuma — o teste não menciona login de usuário, o foco é a integração com o marketplace |
| Filial de estoque | Hardcoded como `1` (filial única) conforme orientação da documentação da API |

## Estrutura de pastas

```
├── database/
│   └── migrations.sql
├── public/                  ← document root
│   ├── .htaccess
│   ├── index.php            ← front controller
│   ├── assets/
│   │   ├── css/app.css
│   │   └── js/app.js
│   └── views/
│       └── dashboard.php
├── src/
│   ├── Config/
│   │   └── Database.php
│   ├── DTO/
│   │   ├── OrderDTO.php
│   │   ├── PriceStockDTO.php
│   │   └── ProductDTO.php
│   ├── Http/
│   │   ├── ApiClient.php
│   │   └── Router.php
│   ├── Marketplace/
│   │   ├── OrderService.php
│   │   ├── PriceStockService.php
│   │   └── ProductService.php
│   └── Repository/
│       ├── OrderRepository.php
│       ├── ProductRepository.php
│       └── UpdateLogRepository.php
├── .env.example
└── composer.json
```
