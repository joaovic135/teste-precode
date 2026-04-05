# Hub Marketplace — Precode

Painel de integração com o marketplace Precode. Permite cadastrar produtos no catálogo, atualizar preços e estoques, e receber pedidos gerados na plataforma — tudo via API REST com autenticação Basic.

## Pré-requisitos

- PHP 8.1+
- Composer
- PostgreSQL 13+
- Extensões PHP: `pdo_pgsql`, `curl`, `json`
- Servidor web com suporte a `mod_rewrite` (Apache) ou equivalente

## Como executar localmente

```bash
git clone <repositório>
cd hub-marketplace

cp .env.example .env
# edite .env com as credenciais do banco

composer install

psql -U postgres -d hub_marketplace -f database/migrations.sql
```

Configure o document root do servidor para apontar para `public/`. Com PHP built-in:

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

## Premissas adotadas

A documentação da API (`/api/documentacao/apiExplorer.php`) retornou 404 durante o desenvolvimento. As premissas abaixo foram adotadas com base na descrição do teste e nos padrões comuns de APIs de marketplace brasileiras:

| Ponto indefinido | Decisão adotada |
|---|---|
| Endpoints da API não documentados | `POST /produto`, `PUT /produto/{sku}/preco`, `PUT /produto/{sku}/estoque`, `GET /pedido` |
| Estrutura da resposta de pedidos | Aceita `pedidos[]`, `orders[]`, `data[]` ou array raiz — normalizado via `extractOrdersList` |
| Modo de recebimento de pedidos | Pull por polling manual; webhook não é viável sem URL pública configurável |
| "Processar pedido" não está definido | Marcação de `processed_at` no banco local — ponto de extensão para lógica de negócio futura |
| Autenticação do painel | Nenhuma — o teste não menciona login de usuário, o foco é a integração com o marketplace |
| Schema do banco | Definido com base nos campos descritos no enunciado e no payload típico de marketplace |

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
