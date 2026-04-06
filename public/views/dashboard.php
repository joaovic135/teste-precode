<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hub Marketplace — Precode</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<header class="app-header">
    <div class="app-header__inner">
        <div class="app-header__brand">
            <span class="app-header__logo">⬡</span>
            Hub Marketplace
            <span class="app-header__sub">Precode</span>
        </div>
        <nav class="app-nav" role="navigation">
            <button class="app-nav__tab app-nav__tab--active" data-tab="products" type="button">
                <span class="tab-icon">📦</span> Produtos
            </button>
            <button class="app-nav__tab" data-tab="updates" type="button">
                <span class="tab-icon">🏷</span> Preço / Estoque
            </button>
            <button class="app-nav__tab" data-tab="orders" type="button">
                <span class="tab-icon">🛒</span> Pedidos
            </button>
        </nav>
    </div>
</header>

<main class="app-main">

    <!-- ════════════════════════ ABA 1 — PRODUTOS ════════════════════════ -->
    <section id="tab-products" class="tab-panel tab-panel--active" role="tabpanel">
        <div class="panel-header">
            <div>
                <h2 class="panel-title">Cadastro de Produtos</h2>
                <p class="panel-desc">Envie produtos ao catálogo do marketplace via <code>POST v3/products</code></p>
            </div>
        </div>

        <div class="card">
            <div class="card__header">
                <h3 class="card__title">Novo produto</h3>
            </div>
            <div class="card__body">
                <form id="form-product" class="form" novalidate>
                    <div class="api-info">
                        <span class="api-info__method api-info__method--post">POST</span>
                        <span class="api-info__endpoint">v3/products</span>
                        <span class="api-info__note">Nome ≥ 20 chars · Descrição ≥ 100 chars · Todos os campos marcados com * são obrigatórios</span>
                    </div>

                    <fieldset class="form-fieldset">
                        <legend class="form-legend">Identificação</legend>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="product-sku">SKU *</label>
                                <input type="text" id="product-sku" name="sku" class="form-input" required autocomplete="off" placeholder="PROD-001">
                            </div>
                            <div class="form-group form-group--wide">
                                <label class="form-label" for="product-name">Nome * <span class="form-label__hint">(mín. 20 char)</span></label>
                                <input type="text" id="product-name" name="name" class="form-input" required minlength="20" placeholder="Nome completo do produto">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="product-brand">Marca *</label>
                                <input type="text" id="product-brand" name="brand" class="form-input" required placeholder="Ex: Samsung">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="product-category">Categoria</label>
                                <input type="text" id="product-category" name="category" class="form-input" placeholder="Ex: Eletrônicos">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="product-ean">EAN / Cód. de barras</label>
                                <input type="text" id="product-ean" name="ean" class="form-input" autocomplete="off" placeholder="7891234567890">
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="form-fieldset">
                        <legend class="form-legend">Preços e Estoque</legend>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="product-price">Preço de venda (R$) *</label>
                                <input type="number" id="product-price" name="price" class="form-input" step="0.01" min="0.01" required placeholder="99.90">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="product-promotional-price">Preço "De" (R$) <span class="form-label__hint">(≥ venda)</span></label>
                                <input type="number" id="product-promotional-price" name="promotional_price" class="form-input" step="0.01" min="0.01" placeholder="129.90">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="product-cost">Custo (R$) *</label>
                                <input type="number" id="product-cost" name="cost" class="form-input" step="0.01" min="0.01" required placeholder="50.00">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="product-stock">Estoque *</label>
                                <input type="number" id="product-stock" name="stock" class="form-input" min="0" required placeholder="10">
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="form-fieldset">
                        <legend class="form-legend">Dimensões e Peso</legend>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="product-weight">Peso (kg) *</label>
                                <input type="number" id="product-weight" name="weight" class="form-input" step="0.001" min="0.001" required placeholder="1.500">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="product-width">Largura (cm) *</label>
                                <input type="number" id="product-width" name="width" class="form-input" step="0.1" min="0.1" required placeholder="30.0">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="product-height">Altura (cm) *</label>
                                <input type="number" id="product-height" name="height" class="form-input" step="0.1" min="0.1" required placeholder="20.0">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="product-length">Profundidade (cm) *</label>
                                <input type="number" id="product-length" name="length" class="form-input" step="0.1" min="0.1" required placeholder="10.0">
                            </div>
                        </div>
                    </fieldset>

                    <div class="form-group">
                        <label class="form-label" for="product-description">Descrição * <span class="form-label__hint">(mín. 100 char)</span></label>
                        <textarea id="product-description" name="description" class="form-textarea" rows="4" required minlength="100" placeholder="Descrição detalhada do produto com pelo menos 100 caracteres..."></textarea>
                        <span class="char-counter" id="desc-counter">0 / 100</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="product-images">URLs das imagens <span class="form-label__hint">(uma por linha, JPG, 500–1200px)</span></label>
                        <textarea id="product-images" name="images" class="form-textarea" rows="2" placeholder="https://example.com/imagem.jpg"></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn--primary" id="btn-submit-product">
                            Enviar ao marketplace
                        </button>
                    </div>
                </form>

                <div id="product-api-result" class="api-result" hidden></div>
            </div>
        </div>

        <div class="card">
            <div class="card__header">
                <h3 class="card__title">Produtos cadastrados</h3>
                <button id="btn-refresh-products" class="btn btn--ghost btn--sm" type="button">↻ Atualizar</button>
            </div>
            <div class="card__body card__body--flush">
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Nome</th>
                                <th>Marca</th>
                                <th>Preço</th>
                                <th>Estoque</th>
                                <th>Status API</th>
                                <th>Cadastrado em</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-products">
                            <tr><td colspan="7" class="table-empty">Carregando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- ════════════════════════ ABA 2 — PREÇO / ESTOQUE ════════════════════════ -->
    <section id="tab-updates" class="tab-panel" role="tabpanel" hidden>
        <div class="panel-header">
            <div>
                <h2 class="panel-title">Atualização de Preço e Estoque</h2>
                <p class="panel-desc">Envie atualizações ao marketplace via <code>PUT v3/products/inventory</code></p>
            </div>
        </div>

        <div class="card">
            <div class="card__header">
                <h3 class="card__title">Selecionar produto e atualizar</h3>
                <button id="btn-refresh-updates-products" class="btn btn--ghost btn--sm" type="button">↻ Recarregar produtos</button>
            </div>
            <div class="card__body">
                <div class="api-info">
                    <span class="api-info__method api-info__method--put">PUT</span>
                    <span class="api-info__endpoint">v3/products/inventory</span>
                    <span class="api-info__note">Atualiza preço, custo e estoque em um único request</span>
                </div>

                <div class="form-group" style="max-width:460px">
                    <label class="form-label" for="update-product-select">Produto</label>
                    <select id="update-product-select" class="form-input">
                        <option value="">— selecione um produto —</option>
                    </select>
                </div>

                <div class="card-grid" id="update-forms-wrapper">
                    <div class="card card--inner">
                        <div class="card__header"><h4 class="card__title">Novo preço</h4></div>
                        <div class="card__body">
                            <form id="form-price" class="form" novalidate>
                                <input type="hidden" id="price-sku" name="sku">
                                <div class="form-group">
                                    <label class="form-label" for="price-current">Preço atual</label>
                                    <input type="text" id="price-current" class="form-input" disabled placeholder="—">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="price-value">Novo preço (R$) *</label>
                                    <input type="number" id="price-value" name="price" class="form-input" step="0.01" min="0.01" required placeholder="0.00">
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn btn--primary">Atualizar preço</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card card--inner">
                        <div class="card__header"><h4 class="card__title">Novo estoque</h4></div>
                        <div class="card__body">
                            <form id="form-stock" class="form" novalidate>
                                <input type="hidden" id="stock-sku" name="sku">
                                <div class="form-group">
                                    <label class="form-label" for="stock-current">Estoque atual</label>
                                    <input type="text" id="stock-current" class="form-input" disabled placeholder="—">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="stock-value">Nova quantidade *</label>
                                    <input type="number" id="stock-value" name="stock" class="form-input" min="0" required placeholder="0">
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn btn--primary">Atualizar estoque</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card__header">
                <h3 class="card__title">Histórico de atualizações</h3>
                <button id="btn-refresh-updates" class="btn btn--ghost btn--sm" type="button">↻ Atualizar</button>
            </div>
            <div class="card__body card__body--flush">
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Tipo</th>
                                <th>Anterior</th>
                                <th>Novo valor</th>
                                <th>Status</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-updates">
                            <tr><td colspan="6" class="table-empty">Carregando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- ════════════════════════ ABA 3 — PEDIDOS ════════════════════════ -->
    <section id="tab-orders" class="tab-panel" role="tabpanel" hidden>
        <div class="panel-header">
            <div>
                <h2 class="panel-title">Pedidos</h2>
                <p class="panel-desc">Crie e gerencie pedidos · Aprovação/cancelamento via <code>PUT/DELETE v1/pedido/pedido</code></p>
            </div>
            <button id="btn-sync-orders" class="btn btn--secondary" type="button">↻ Sincronizar fila marketplace</button>
        </div>

        <!-- Form de criação de pedido -->
        <div class="card">
            <div class="card__header">
                <h3 class="card__title">Criar pedido</h3>
                <button type="button" class="btn btn--ghost btn--sm" id="btn-toggle-order-form">+ Expandir</button>
            </div>
            <div id="order-form-body" class="card__body" hidden>
                <div class="api-info">
                    <span class="api-info__method api-info__method--post">POST</span>
                    <span class="api-info__endpoint">v1/pedido/pedido</span>
                    <span class="api-info__note">formaPagamento fixo em "4" · itens.sku = SKU inteiro Precode</span>
                </div>

                <form id="form-order" class="form" novalidate>
                    <fieldset class="form-fieldset">
                        <legend class="form-legend">Dados do cliente</legend>
                        <div class="form-row">
                            <div class="form-group form-group--wide">
                                <label class="form-label" for="order-nome">Nome / Razão social *</label>
                                <input type="text" id="order-nome" name="nomeRazao" class="form-input" required placeholder="João da Silva">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="order-cpf">CPF / CNPJ *</label>
                                <input type="text" id="order-cpf" name="cpfCnpj" class="form-input" required placeholder="000.000.000-00">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group form-group--wide">
                                <label class="form-label" for="order-email">E-mail *</label>
                                <input type="email" id="order-email" name="email" class="form-input" required placeholder="cliente@email.com">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="order-celular">Celular *</label>
                                <input type="text" id="order-celular" name="celular" class="form-input" required placeholder="(11) 99999-9999">
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="form-fieldset">
                        <legend class="form-legend">Endereço de entrega</legend>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="order-cep">CEP *</label>
                                <input type="text" id="order-cep" name="cep" class="form-input" required placeholder="00000-000" maxlength="9">
                            </div>
                            <div class="form-group form-group--wide">
                                <label class="form-label" for="order-endereco">Endereço *</label>
                                <input type="text" id="order-endereco" name="endereco" class="form-input" required placeholder="Rua das Flores">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="order-numero">Número *</label>
                                <input type="text" id="order-numero" name="numero" class="form-input" required placeholder="123">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="order-bairro">Bairro *</label>
                                <input type="text" id="order-bairro" name="bairro" class="form-input" required placeholder="Centro">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="order-cidade">Cidade *</label>
                                <input type="text" id="order-cidade" name="cidade" class="form-input" required placeholder="São Paulo">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="order-uf">UF *</label>
                                <select id="order-uf" name="uf" class="form-input" required>
                                    <option value="">—</option>
                                    <option>AC</option><option>AL</option><option>AM</option><option>AP</option>
                                    <option>BA</option><option>CE</option><option>DF</option><option>ES</option>
                                    <option>GO</option><option>MA</option><option>MG</option><option>MS</option>
                                    <option>MT</option><option>PA</option><option>PB</option><option>PE</option>
                                    <option>PI</option><option>PR</option><option>RJ</option><option>RN</option>
                                    <option>RO</option><option>RR</option><option>RS</option><option>SC</option>
                                    <option selected>SP</option><option>SE</option><option>TO</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="order-complemento">Complemento</label>
                                <input type="text" id="order-complemento" name="complemento" class="form-input" placeholder="Apto 42">
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="form-fieldset">
                        <legend class="form-legend">Itens do pedido</legend>
                        <div class="order-items-header">
                            <div class="order-items-hint">
                                SKU inteiro = código Precode do produto (<code>itens[].sku</code>)
                            </div>
                            <button type="button" class="btn btn--ghost btn--sm" id="btn-add-item">+ Adicionar item</button>
                        </div>
                        <div id="order-items-list">
                            <!-- itens dinâmicos inseridos via JS -->
                        </div>
                        <div class="order-total-row">
                            <span>Total calculado:</span>
                            <strong id="order-total-display">R$ 0,00</strong>
                        </div>
                    </fieldset>

                    <div class="form-actions">
                        <button type="submit" class="btn btn--primary" id="btn-submit-order">Enviar pedido ao marketplace</button>
                        <button type="reset" class="btn btn--ghost">Limpar</button>
                    </div>
                </form>

                <div id="order-api-result" class="api-result" hidden></div>
            </div>
        </div>

        <!-- Lista de pedidos -->
        <div class="card">
            <div class="card__header">
                <h3 class="card__title">Pedidos enviados</h3>
                <button id="btn-refresh-orders" class="btn btn--ghost btn--sm" type="button">↻ Atualizar</button>
            </div>
            <div class="card__body card__body--flush">
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Ref / Cód. Precode</th>
                                <th>Origem</th>
                                <th>Cliente</th>
                                <th>Total</th>
                                <th>Status pedido</th>
                                <th>Status API</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-orders">
                            <tr><td colspan="7" class="table-empty">Carregando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

</main>

<div id="toast-container" class="toast-container" aria-live="polite"></div>

<script src="/assets/js/app.js"></script>
</body>
</html>
