# Hub Marketplace — Precode

Painel de integração com o marketplace Precode desenvolvido em PHP 8.3, PostgreSQL e JavaScript vanilla — sem frameworks. A aplicação cobre os três fluxos do teste técnico:

1. **Cadastro de produtos** — formulário que envia os dados ao marketplace via `POST v3/products` e exibe o retorno da API na tela
2. **Atualização de preço e estoque** — painel com listagem dos produtos cadastrados e atualização via `PUT v3/products/inventory`
3. **Gestão de pedidos** — criação de pedidos (`POST v1/pedido/pedido`), aprovação (`PUT`) e cancelamento (`DELETE`) com exibição do retorno da API

## Como rodar

### Docker (recomendado)

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

### Sem Docker

```bash
cp .env.example .env
# preencha .env com as credenciais do banco e da API

composer install
psql -U postgres -d hub_marketplace -f database/migrations.sql

php -S localhost:8000 -t public
```

## Variáveis de ambiente

Copie `.env.example` para `.env` e preencha:

| Variável                | Descrição                          |
|-------------------------|------------------------------------|
| `DB_HOST`               | Host do PostgreSQL                 |
| `DB_PORT`               | Porta do PostgreSQL                |
| `DB_NAME`               | Nome do banco                      |
| `DB_USER`               | Usuário do banco                   |
| `DB_PASS`               | Senha do banco                     |
| `MARKETPLACE_API_URL`   | URL base da API **(sem versão)**   |
| `MARKETPLACE_API_TOKEN` | Token de autenticação Basic        |

## Endpoints da API

| Funcionalidade          | Método   | Endpoint                | Versão |
|-------------------------|----------|-------------------------|--------|
| Cadastrar produto       | `POST`   | `v3/products`           | v3     |
| Atualizar preço/estoque | `PUT`    | `v3/products/inventory` | v3     |
| Fila de pedidos         | `GET`    | `v3/orders`             | v3     |
| Criar pedido            | `POST`   | `v1/pedido/pedido`      | v1     |
| Aprovar pedido          | `PUT`    | `v1/pedido/pedido`      | v1     |
| Cancelar pedido         | `DELETE` | `v1/pedido/pedido`      | v1     |

## Estrutura de pastas

```
├── database/
│   ├── migrations.sql          ← schema completo
│   └── migrations_v3.sql       ← migrations incrementais
├── public/                     ← document root
│   ├── index.php               ← front controller
│   ├── assets/
│   │   ├── css/app.css
│   │   └── js/app.js
│   └── views/dashboard.php
├── src/
│   ├── Config/                 ← Database, Environment
│   ├── DTO/                    ← OrderDTO, PriceStockDTO, ProductDTO
│   ├── Http/                   ← ApiClient, Router
│   ├── Marketplace/            ← OrderService, PriceStockService, ProductService
│   └── Repository/             ← OrderRepository, ProductRepository, UpdateLogRepository
├── .env.example
├── Dockerfile
└── docker-compose.yml
```
