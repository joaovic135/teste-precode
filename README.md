# Hub Marketplace — Precode

Painel de integração com o marketplace Precode. Permite **cadastrar produtos** no catálogo, **atualizar preços e estoques**, **criar pedidos** e **aprovar ou cancelar** pedidos diretamente pela API REST com autenticação Basic.

## Pré-requisitos

- PHP 8.3+
- Composer
- PostgreSQL 13+
- Extensões PHP: `pdo_pgsql`, `curl`, `json`
- Servidor web com `mod_rewrite` habilitado (Apache)

## Como executar localmente

### Com Docker (recomendado)

```bash
git clone <repositório>
cd hub-marketplace
docker compose up --build -d
```

Acesse `http://localhost:8080`. O banco sobe com as migrations aplicadas automaticamente.

```bash
docker compose down       # mantém os dados
docker compose down -v    # remove o volume do banco também
```

> **Atualização de schema em instâncias existentes:** se o volume já existia antes da última versão, rode manualmente:
> ```bash
> docker compose exec db psql -U postgres -d hub_marketplace -f /docker-entrypoint-initdb.d/02_migrations_v3.sql
> ```

### Sem Docker

```bash
git clone <repositório>
cd hub-marketplace

cp .env.example .env
# edite .env com as credenciais do banco e da API

composer install

psql -U postgres -d hub_marketplace -f database/migrations.sql
```

Configure o document root para `public/`. Com PHP built-in:

```bash
php -S localhost:8000 -t public
```

## Variáveis de ambiente

| Variável                | Descrição                                     | Exemplo                                    |
|-------------------------|-----------------------------------------------|--------------------------------------------|
| `DB_HOST`               | Host do PostgreSQL                            | `localhost`                                |
| `DB_PORT`               | Porta do PostgreSQL                           | `5432`                                     |
| `DB_NAME`               | Nome do banco                                 | `hub_marketplace`                          |
| `DB_USER`               | Usuário do banco                              | `postgres`                                 |
| `DB_PASS`               | Senha do banco                                | `postgres`                                 |
| `MARKETPLACE_API_URL`   | URL base da API (sem versão)                  | `https://api.seumarketplace.com.br/api`    |
| `MARKETPLACE_API_TOKEN` | Token de autenticação Basic                   | `Basic <seu_token_aqui>`                   |

## Endpoints da API utilizados

Implementação baseada na [documentação oficial](https://www.precode.com.br/api/documentacao/apiExplorer.php?versao=3):

| Funcionalidade           | Método     | Endpoint                         | Versão |
|--------------------------|------------|----------------------------------|--------|
| Cadastrar produto        | `POST`     | `v3/products`                    | v3     |
| Atualizar preço/estoque  | `PUT`      | `v3/products/inventory`          | v3     |
| Fila de pedidos          | `GET`      | `v3/orders`                      | v3     |
| Criar pedido             | `POST`     | `v1/pedido/pedido`               | v1     |
| Aprovar pedido           | `PUT`      | `v1/pedido/pedido`               | v1     |
| Cancelar pedido          | `DELETE`   | `v1/pedido/pedido`               | v1     |

### Notas sobre o ambiente de teste

Com as credenciais de teste fornecidas:

| Endpoint                    | Resultado observado                                                              |
|-----------------------------|----------------------------------------------------------------------------------|
| `POST v1/pedido/pedido`     | **HTTP 200** — pedido criado com sucesso, retorna `numeroPedido`                 |
| `PUT v1/pedido/pedido`      | **HTTP 200** — aprovação processada com sucesso                                  |
| `DELETE v1/pedido/pedido`   | **HTTP 200** — cancelamento processado com sucesso                               |
| `PUT v3/products/inventory` | **HTTP 200** com `code: 3` ("SKU ou REF não encontrado") — endpoint funcional   |
| `POST v3/products`          | **HTTP 404** — endpoint não disponível para este tipo de conta de teste          |
| `GET v3/orders`             | **HTTP 403** — rota existe, credencial sem permissão para esta loja              |

O fluxo completo de pedidos (`v1`) funciona com as credenciais de teste. As operações de produto (`v3/products`) requerem uma conta loja ativa para funcionar em produção.

## Estrutura de pastas

```
├── database/
│   ├── migrations.sql          ← schema completo (fresh install)
│   └── migrations_v3.sql       ← migrations incrementais (atualização)
├── public/                     ← document root
│   ├── .htaccess
│   ├── index.php               ← front controller
│   ├── assets/
│   │   ├── css/app.css
│   │   └── js/app.js
│   └── views/
│       └── dashboard.php
├── src/
│   ├── Config/
│   │   ├── Database.php
│   │   └── Environment.php
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
├── composer.json
├── Dockerfile
└── docker-compose.yml
```
