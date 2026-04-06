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
                    <p class="form-hint">Campos obrigatórios pela API Precode: nome (mín. 20 caracteres), descrição (mín. 100 caracteres), marca, dimensões e custos.</p>

                    <fieldset class="form-fieldset">
                        <legend class="form-legend">Identificação</legend>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="product-sku">SKU *</label>
                                <input type="text" id="product-sku" name="sku" class="form-input" required autocomplete="off">
                            </div>
                            <div class="form-group form-group--wide">
                                <label class="form-label" for="product-name">Nome * <span class="form-label__hint">(mín. 20 caracteres)</span></label>
                                <input type="text" id="product-name" name="name" class="form-input" required minlength="20">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="product-brand">Marca *</label>
                                <input type="text" id="product-brand" name="brand" class="form-input" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="product-category">Categoria</label>
                                <input type="text" id="product-category" name="category" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="product-ean">EAN / Código de barras</label>
                                <input type="text" id="product-ean" name="ean" class="form-input" autocomplete="off">
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="form-fieldset">
                        <legend class="form-legend">Preços e Estoque</legend>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="product-price">Preço de venda (R$) *</label>
                                <input type="number" id="product-price" name="price" class="form-input" step="0.01" min="0.01" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="product-promotional-price">Preço "De" (R$) <span class="form-label__hint">(≥ preço de venda)</span></label>
                                <input type="number" id="product-promotional-price" name="promotional_price" class="form-input" step="0.01" min="0.01">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="product-cost">Custo (R$) *</label>
                                <input type="number" id="product-cost" name="cost" class="form-input" step="0.01" min="0.01" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="product-stock">Estoque *</label>
                                <input type="number" id="product-stock" name="stock" class="form-input" min="0" required>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="form-fieldset">
                        <legend class="form-legend">Dimensões e Peso</legend>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="product-weight">Peso bruto (kg) *</label>
                                <input type="number" id="product-weight" name="weight" class="form-input" step="0.001" min="0.001" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="product-width">Largura (cm) *</label>
                                <input type="number" id="product-width" name="width" class="form-input" step="0.1" min="0.1" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="product-height">Altura (cm) *</label>
                                <input type="number" id="product-height" name="height" class="form-input" step="0.1" min="0.1" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="product-length">Profundidade (cm) *</label>
                                <input type="number" id="product-length" name="length" class="form-input" step="0.1" min="0.1" required>
                            </div>
                        </div>
                    </fieldset>

                    <div class="form-group">
                        <label class="form-label" for="product-description">Descrição * <span class="form-label__hint">(mín. 100 caracteres)</span></label>
                        <textarea id="product-description" name="description" class="form-textarea" rows="4" required minlength="100"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="product-images">URLs das imagens <span class="form-label__hint">(uma por linha, JPG, entre 500px e 1200px)</span></label>
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
