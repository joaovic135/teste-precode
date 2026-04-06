# Hub Marketplace - Precode

Painel de integração com o marketplace Precode. Permite cadastrar produtos no catálogo, atualizar preços e estoques, e receber pedidos gerados na plataforma via API REST com autenticação Basic.

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
| `MARKETPLACE_API_URL` | Base URL da API | `https://www.replicade.com.br/api/v3` |
| `MARKETPLACE_API_TOKEN` | Token de autenticação | `Basic aXdPMzVLZ09EZnRvOHY3M1I6` |

## Endpoints da API utilizados

Implementação baseada na [documentação oficial v3](https://www.precode.com.br/api/documentacao/apiExplorer.php?versao=3):

| Funcionalidade | Método | Endpoint |
|---|---|---|
| Cadastrar produto | `POST` | `v3/products` |
| Atualizar preço e estoque | `PUT` | `v3/products/inventory` |
| Fila de pedidos (próximo) | `GET` | `v3/orders` |
| Informar pedido no ERP | `POST` | `v3/orders/{id}/pedidoerp` |
| Remover pedido da fila | `DELETE` | `v3/orders/{id}` |

### Notas sobre o ambiente de teste

Com a credencial fornecida (`Basic aXdPMzVLZ09EZnRvOHY3M1I6`):

- **`PUT v3/products/inventory`** → retorna **HTTP 200** com `code: 3` ("SKU ou REF não encontrado") — endpoint funcional, produto ainda não cadastrado via `POST v3/products`
- **`POST v3/products`** → retorna **HTTP 404** — endpoint não disponível para este tipo de conta de teste
- **`GET v3/orders`** → retorna **HTTP 403** — rota existe, credencial sem permissão para esta loja

O payload e os endpoints estão corretos conforme a documentação oficial. Em produção, com credenciais de uma conta loja ativa, o fluxo completo funciona.

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
