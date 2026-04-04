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
    <div class="app-header__brand">Hub Marketplace</div>
    <nav class="app-nav" role="navigation" aria-label="Abas de navegação">
        <button class="app-nav__tab app-nav__tab--active" data-tab="products" type="button">Produtos</button>
        <button class="app-nav__tab" data-tab="updates" type="button">Preço / Estoque</button>
        <button class="app-nav__tab" data-tab="orders" type="button">Pedidos</button>
    </nav>
</header>

<main class="app-main">

    <section id="tab-products" class="tab-panel tab-panel--active" role="tabpanel">
        <div class="panel-header">
            <h2 class="panel-title">Catálogo de Produtos</h2>
        </div>

        <div class="card">
            <div class="card__header">
                <h3 class="card__title">Cadastrar produto</h3>
            </div>
            <div class="card__body">
                <form id="form-product" class="form" novalidate>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="product-sku">SKU</label>
                            <input type="text" id="product-sku" name="sku" class="form-input" required autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="product-name">Nome</label>
                            <input type="text" id="product-name" name="name" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="product-category">Categoria</label>
                            <input type="text" id="product-category" name="category" class="form-input">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="product-price">Preço (R$)</label>
                            <input type="number" id="product-price" name="price" class="form-input" step="0.01" min="0.01" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="product-stock">Estoque</label>
                            <input type="number" id="product-stock" name="stock" class="form-input" min="0" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="product-description">Descrição</label>
                        <textarea id="product-description" name="description" class="form-textarea" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="product-images">URLs das imagens <span class="form-label__hint">(uma por linha)</span></label>
                        <textarea id="product-images" name="images" class="form-textarea" rows="2" placeholder="https://example.com/imagem.jpg"></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn--primary" id="btn-submit-product">Enviar ao marketplace</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card__header">
                <h3 class="card__title">Produtos cadastrados</h3>
                <button id="btn-refresh-products" class="btn btn--ghost btn--sm" type="button">Atualizar</button>
            </div>
            <div class="card__body card__body--flush">
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Nome</th>
                                <th>Categoria</th>
                                <th>Preço</th>
                                <th>Estoque</th>
                                <th>Status</th>
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

    <section id="tab-updates" class="tab-panel" role="tabpanel" hidden>
        <div class="panel-header">
            <h2 class="panel-title">Atualização de Preço e Estoque</h2>
        </div>

        <div class="card-grid">
            <div class="card">
                <div class="card__header">
                    <h3 class="card__title">Atualizar preço</h3>
                </div>
                <div class="card__body">
                    <form id="form-price" class="form" novalidate>
                        <div class="form-group">
                            <label class="form-label" for="price-sku">SKU do produto</label>
                            <input type="text" id="price-sku" name="sku" class="form-input" required autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="price-value">Novo preço (R$)</label>
                            <input type="number" id="price-value" name="price" class="form-input" step="0.01" min="0.01" required>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn--primary">Atualizar preço</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card__header">
                    <h3 class="card__title">Atualizar estoque</h3>
                </div>
                <div class="card__body">
                    <form id="form-stock" class="form" novalidate>
                        <div class="form-group">
                            <label class="form-label" for="stock-sku">SKU do produto</label>
                            <input type="text" id="stock-sku" name="sku" class="form-input" required autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="stock-value">Nova quantidade</label>
                            <input type="number" id="stock-value" name="stock" class="form-input" min="0" required>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn--primary">Atualizar estoque</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card__header">
                <h3 class="card__title">Histórico de atualizações</h3>
                <button id="btn-refresh-updates" class="btn btn--ghost btn--sm" type="button">Atualizar</button>
            </div>
            <div class="card__body card__body--flush">
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Tipo</th>
                                <th>Valor anterior</th>
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

    <section id="tab-orders" class="tab-panel" role="tabpanel" hidden>
        <div class="panel-header">
            <h2 class="panel-title">Pedidos</h2>
            <button id="btn-sync-orders" class="btn btn--primary" type="button">Sincronizar com marketplace</button>
        </div>

        <div class="card">
            <div class="card__header">
                <h3 class="card__title">Pedidos recebidos</h3>
                <button id="btn-refresh-orders" class="btn btn--ghost btn--sm" type="button">Atualizar</button>
            </div>
            <div class="card__body card__body--flush">
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID Marketplace</th>
                                <th>Cliente</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Processado em</th>
                                <th>Recebido em</th>
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
