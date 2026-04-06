'use strict';

// ─── HTTP client ──────────────────────────────────────────────────────────────

const Api = {
    async request(method, url, body = null) {
        const options = {
            method,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        };
        if (body !== null) options.body = JSON.stringify(body);

        const response = await fetch(url, options);
        const json     = await response.json();

        if (!json.success) throw new Error(json.error ?? 'Erro desconhecido na requisição');
        return json.data;
    },
    get:    (url)       => Api.request('GET',  url),
    post:   (url, body) => Api.request('POST', url, body),
    put:    (url, body) => Api.request('PUT',  url, body),
    delete: (url, body) => Api.request('DELETE', url, body),
};

// ─── UI helpers ───────────────────────────────────────────────────────────────

const UI = {
    fmt: v => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v),

    fmtDate(dateStr) {
        if (!dateStr) return '—';
        return new Intl.DateTimeFormat('pt-BR', {
            day: '2-digit', month: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit',
        }).format(new Date(dateStr));
    },

    badge(cls, label, title = '') {
        const t = title ? ` title="${title.replace(/"/g, '&quot;')}"` : '';
        return `<span class="badge badge--${cls}"${t}>${label}</span>`;
    },

    productStatusBadge(status, error = '') {
        const map = { sent: ['success', 'Enviado'], error: ['error', 'Erro'], pending: ['pending', 'Pendente'] };
        const [cls, label] = map[status] ?? ['pending', status];
        return UI.badge(cls, label, status === 'error' ? error : '');
    },

    orderStatusBadge(status) {
        const map = {
            novo: ['info', 'Novo'], aprovado: ['success', 'Aprovado'],
            cancelado: ['error', 'Cancelado'], faturando: ['success', 'Faturando'],
            'em viagem': ['info', 'Em viagem'], entregue: ['success', 'Entregue'],
        };
        const [cls, label] = map[status] ?? ['pending', status];
        return UI.badge(cls, label);
    },

    marketplaceStatusBadge(status, error = '') {
        const map = { sent: ['success', 'Enviado'], error: ['error', 'Erro API'], pending: ['warning', 'Pendente'] };
        const [cls, label] = map[status] ?? ['pending', status];
        return UI.badge(cls, label, status === 'error' ? error : '');
    },

    updateStatusBadge(status) {
        const map = { sent: ['success', 'Enviado'], error: ['error', 'Erro'], pending: ['warning', 'Pendente'] };
        const [cls, label] = map[status] ?? ['pending', status];
        return UI.badge(cls, label);
    },

    showToast(message, type = 'info') {
        const icons = { success: '✓', error: '✕', info: 'ℹ', warning: '⚠' };
        const toast = document.createElement('div');
        toast.className = `toast toast--${type}`;
        toast.innerHTML = `
            <span class="toast__icon">${icons[type] ?? '•'}</span>
            <span class="toast__message">${message}</span>
        `;
        document.getElementById('toast-container').appendChild(toast);
        setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 5000);
    },

    setLoading(btn, on) {
        if (on) { btn.dataset.orig = btn.textContent; btn.textContent = 'Aguarde…'; btn.disabled = true; }
        else    { btn.textContent = btn.dataset.orig ?? btn.textContent; btn.disabled = false; }
    },

    renderEmpty(tbodyId, cols, msg = 'Nenhum registro encontrado') {
        document.getElementById(tbodyId).innerHTML =
            `<tr><td colspan="${cols}" class="table-empty">${msg}</td></tr>`;
    },

    showApiResult(containerId, data, isError = false) {
        const el = document.getElementById(containerId);
        el.hidden   = false;
        el.className = `api-result api-result--${isError ? 'error' : 'success'}`;
        el.innerHTML = `
            <div class="api-result__header">
                <strong>${isError ? '✕ Erro da API' : '✓ Resposta da API'}</strong>
                <button class="api-result__close" onclick="this.parentElement.parentElement.hidden=true">×</button>
            </div>
            <pre class="api-result__body">${JSON.stringify(data, null, 2)}</pre>
        `;
    },
};

// ─── Produtos ─────────────────────────────────────────────────────────────────

const Products = {
    list: [],

    async load() {
        try {
            Products.list = await Api.get('/api/products');
            Products.render(Products.list);
        } catch (err) {
            UI.renderEmpty('tbody-products', 7, 'Erro ao carregar produtos');
        }
    },

    render(products) {
        if (!products.length) { UI.renderEmpty('tbody-products', 7); return; }

        document.getElementById('tbody-products').innerHTML = products.map(p => `
            <tr>
                <td class="table-mono">${p.sku}</td>
                <td>${p.name}</td>
                <td>${p.brand || '—'}</td>
                <td>${UI.fmt(p.price)}</td>
                <td>${p.stock}</td>
                <td>${UI.productStatusBadge(p.marketplace_status, p.marketplace_error ?? '')}</td>
                <td>${UI.fmtDate(p.created_at)}</td>
            </tr>
        `).join('');
    },

    async handleSubmit(e) {
        e.preventDefault();
        const form   = e.target;
        const btn    = form.querySelector('[type="submit"]');
        const images = document.getElementById('product-images').value
            .split('\n').map(u => u.trim()).filter(Boolean);

        const promo = form.promotional_price.value.trim();
        const payload = {
            sku:               form.sku.value.trim(),
            name:              form.name.value.trim(),
            brand:             form.brand.value.trim(),
            category:          form.category.value.trim(),
            ean:               form.ean.value.trim(),
            description:       form.description.value.trim(),
            price:             parseFloat(form.price.value),
            promotional_price: promo ? parseFloat(promo) : parseFloat(form.price.value),
            cost:              parseFloat(form.cost.value),
            weight:            parseFloat(form.weight.value),
            width:             parseFloat(form.width.value),
            height:            parseFloat(form.height.value),
            length:            parseFloat(form.length.value),
            stock:             parseInt(form.stock.value, 10),
            images,
        };

        UI.setLoading(btn, true);
        try {
            const result = await Api.post('/api/products', payload);
            const ok     = result.marketplace_status === 'sent';
            UI.showApiResult('product-api-result', result, !ok);
            UI.showToast(
                ok ? `Produto '${result.sku}' cadastrado e enviado com sucesso`
                   : `Produto salvo localmente — erro no marketplace: ${result.error}`,
                ok ? 'success' : 'error',
            );
            if (ok) form.reset();
            await Products.load();
        } catch (err) {
            UI.showApiResult('product-api-result', { error: err.message }, true);
            UI.showToast(err.message, 'error');
        } finally {
            UI.setLoading(btn, false);
        }
    },
};

document.getElementById('product-description')?.addEventListener('input', function () {
    const cnt = this.value.length;
    const el  = document.getElementById('desc-counter');
    if (el) { el.textContent = `${cnt} / 100`; el.style.color = cnt >= 100 ? 'var(--color-success)' : ''; }
});

// ─── Preço / Estoque ──────────────────────────────────────────────────────────

const Updates = {
    async loadProducts() {
        const sel = document.getElementById('update-product-select');
        if (!sel) return;
        try {
            const products = Products.list.length ? Products.list : await Api.get('/api/products');
            Products.list  = products;
            sel.innerHTML  = '<option value="">— selecione um produto —</option>' +
                products.map(p => `<option value="${p.sku}" data-price="${p.price}" data-stock="${p.stock}">${p.sku} — ${p.name}</option>`).join('');
        } catch { /* ignore */ }
    },

    async load() {
        try {
            Updates.render(await Api.get('/api/updates'));
        } catch (err) {
            UI.renderEmpty('tbody-updates', 6, 'Erro ao carregar histórico');
        }
    },

    render(updates) {
        if (!updates.length) { UI.renderEmpty('tbody-updates', 6); return; }
        document.getElementById('tbody-updates').innerHTML = updates.map(u => {
            const isPrice = u.update_type === 'price';
            const fmt     = v => isPrice ? UI.fmt(v) : v;
            return `
                <tr>
                    <td class="table-mono">${u.product_sku}</td>
                    <td>${isPrice ? 'Preço' : 'Estoque'}</td>
                    <td>${fmt(u.old_value ?? '—')}</td>
                    <td>${fmt(u.new_value)}</td>
                    <td>${UI.updateStatusBadge(u.status)}</td>
                    <td>${UI.fmtDate(u.created_at)}</td>
                </tr>`;
        }).join('');
    },

    async handlePriceSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const btn  = form.querySelector('[type="submit"]');
        const sku  = document.getElementById('price-sku').value.trim();
        if (!sku) { UI.showToast('Selecione um produto primeiro', 'error'); return; }

        UI.setLoading(btn, true);
        try {
            const result = await Api.put(`/api/products/${encodeURIComponent(sku)}/price`, { price: parseFloat(form.price.value) });
            const ok     = result.status === 'sent';
            UI.showToast(ok ? `Preço de '${sku}' atualizado para ${UI.fmt(result.new_price)}` : `Erro: ${result.error}`, ok ? 'success' : 'error');
            form.reset();
            await Updates.load();
        } catch (err) {
            UI.showToast(err.message, 'error');
        } finally {
            UI.setLoading(btn, false);
        }
    },

    async handleStockSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const btn  = form.querySelector('[type="submit"]');
        const sku  = document.getElementById('stock-sku').value.trim();
        if (!sku) { UI.showToast('Selecione um produto primeiro', 'error'); return; }

        UI.setLoading(btn, true);
        try {
            const result = await Api.put(`/api/products/${encodeURIComponent(sku)}/stock`, { stock: parseInt(form.stock.value, 10) });
            const ok     = result.status === 'sent';
            UI.showToast(ok ? `Estoque de '${sku}' atualizado para ${result.new_stock} unidades` : `Erro: ${result.error}`, ok ? 'success' : 'error');
            form.reset();
            await Updates.load();
        } catch (err) {
            UI.showToast(err.message, 'error');
        } finally {
            UI.setLoading(btn, false);
        }
    },
};

document.getElementById('update-product-select')?.addEventListener('change', function () {
    const opt   = this.selectedOptions[0];
    const price = opt?.dataset.price ?? '';
    const stock = opt?.dataset.stock ?? '';
    const sku   = this.value;

    document.getElementById('price-sku').value     = sku;
    document.getElementById('stock-sku').value     = sku;
    document.getElementById('price-current').value = price ? UI.fmt(price) : '';
    document.getElementById('stock-current').value = stock || '';
});

// ─── Pedidos ──────────────────────────────────────────────────────────────────

const Orders = {
    items: [],

    async load() {
        try {
            Orders.render(await Api.get('/api/orders'));
        } catch (err) {
            UI.renderEmpty('tbody-orders', 7, 'Erro ao carregar pedidos');
        }
    },

    render(orders) {
        if (!orders.length) { UI.renderEmpty('tbody-orders', 7); return; }

        document.getElementById('tbody-orders').innerHTML = orders.map(o => {
            const id      = o.marketplace_order_id;
            const codigo  = o.marketplace_codigo_pedido ? `#${o.marketplace_codigo_pedido}` : '';
            const ref     = codigo ? `${id} ${codigo}` : id;
            const origin  = o.origin === 'local'
                ? UI.badge('info', 'Enviado por nós')
                : UI.badge('pending', 'Marketplace');

            const canApprove = ['novo', 'analisando'].includes(o.status);
            const canCancel  = !['cancelado', 'entregue'].includes(o.status);

            const actions = `
                ${canApprove ? `<button class="btn btn--success btn--sm" onclick="Orders.handleApprove('${id}')">Aprovar</button>` : ''}
                ${canCancel  ? `<button class="btn btn--danger btn--sm" onclick="Orders.handleCancel('${id}')">Cancelar</button>` : ''}
                ${!canApprove && !canCancel ? '<span class="text-muted">—</span>' : ''}
            `.trim();

            return `
                <tr>
                    <td class="table-mono" title="${id}">${ref}</td>
                    <td>${origin}</td>
                    <td>${o.customer_name || '—'}</td>
                    <td>${UI.fmt(o.total)}</td>
                    <td>${UI.orderStatusBadge(o.status)}</td>
                    <td>${UI.marketplaceStatusBadge(o.marketplace_status ?? 'pending', o.marketplace_error ?? '')}</td>
                    <td class="table-actions">${actions}</td>
                </tr>`;
        }).join('');
    },

    async handleSync() {
        const btn = document.getElementById('btn-sync-orders');
        UI.setLoading(btn, true);
        try {
            const result = await Api.post('/api/orders/sync');
            if (result.error) {
                UI.showToast(`Erro ao sincronizar: ${result.error}`, 'error');
            } else {
                UI.showToast(
                    result.synced > 0
                        ? `${result.synced} novo(s) pedido(s) importado(s)`
                        : 'Nenhum pedido novo na fila',
                    'success',
                );
            }
            await Orders.load();
        } catch (err) {
            UI.showToast(err.message, 'error');
        } finally {
            UI.setLoading(btn, false);
        }
    },

    async handleApprove(id) {
        try {
            const result = await Api.post(`/api/orders/${encodeURIComponent(id)}/approve`);
            if (result.approved === false) {
                UI.showToast(`Erro ao aprovar: ${result.error}`, 'error');
            } else {
                UI.showToast(`Pedido '${id}' aprovado com sucesso`, 'success');
            }
            await Orders.load();
        } catch (err) { UI.showToast(err.message, 'error'); }
    },

    async handleCancel(id) {
        if (!confirm(`Cancelar o pedido '${id}'?`)) return;
        try {
            const result = await Api.post(`/api/orders/${encodeURIComponent(id)}/cancel`);
            if (result.cancelled === false) {
                UI.showToast(`Erro ao cancelar: ${result.error}`, 'error');
            } else {
                UI.showToast(`Pedido '${id}' cancelado`, 'success');
            }
            await Orders.load();
        } catch (err) { UI.showToast(err.message, 'error'); }
    },

    // ── Criação de pedido ─────────────────────────────────────────────────────

    addItem() {
        const idx = Orders.items.length;
        Orders.items.push({ sku: 0, valorUnitario: 0, quantidade: 1 });
        const list = document.getElementById('order-items-list');
        const row  = document.createElement('div');
        row.className = 'order-item-row';
        row.dataset.idx = idx;

        // Product options from our catalog (for reference)
        const productOpts = Products.list.length
            ? `<option value="">— ou selecione da lista —</option>` +
              Products.list.map(p => `<option value="${p.sku}" data-price="${p.price}">${p.sku} — ${p.name} (R$ ${parseFloat(p.price).toFixed(2)})</option>`).join('')
            : '';

        row.innerHTML = `
            <div class="order-item-fields">
                <div class="form-group">
                    <label class="form-label">SKU Precode (int) *</label>
                    <input type="number" class="form-input item-sku" min="0" placeholder="0" required>
                </div>
                ${productOpts ? `<div class="form-group">
                    <label class="form-label">Ref. catálogo local</label>
                    <select class="form-input item-ref">${productOpts}</select>
                </div>` : ''}
                <div class="form-group">
                    <label class="form-label">Valor unit. (R$) *</label>
                    <input type="number" class="form-input item-price" step="0.01" min="0.01" placeholder="0.00" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Qtd *</label>
                    <input type="number" class="form-input item-qty" min="1" value="1" required>
                </div>
                <button type="button" class="btn btn--danger btn--sm item-remove" style="align-self:flex-end">✕</button>
            </div>
        `;

        // Auto-fill price from catalog ref
        row.querySelector('.item-ref')?.addEventListener('change', function () {
            const opt   = this.selectedOptions[0];
            const price = opt?.dataset.price;
            if (price) row.querySelector('.item-price').value = parseFloat(price).toFixed(2);
            Orders.recalcTotal();
        });

        row.querySelector('.item-price')?.addEventListener('input', Orders.recalcTotal);
        row.querySelector('.item-qty')?.addEventListener('input', Orders.recalcTotal);
        row.querySelector('.item-remove').addEventListener('click', () => { row.remove(); Orders.recalcTotal(); });

        list.appendChild(row);
        Orders.recalcTotal();
    },

    recalcTotal() {
        let total = 0;
        document.querySelectorAll('.order-item-row').forEach(row => {
            const price = parseFloat(row.querySelector('.item-price')?.value) || 0;
            const qty   = parseInt(row.querySelector('.item-qty')?.value, 10) || 0;
            total += price * qty;
        });
        const el = document.getElementById('order-total-display');
        if (el) el.textContent = UI.fmt(total);
    },

    async handleSubmit(e) {
        e.preventDefault();
        const form  = e.target;
        const btn   = form.querySelector('[type="submit"]');
        const rows  = [...document.querySelectorAll('.order-item-row')];

        if (rows.length === 0) {
            UI.showToast('Adicione ao menos um item ao pedido', 'error');
            return;
        }

        const items = rows.map(row => ({
            sku:          parseInt(row.querySelector('.item-sku')?.value, 10) || 0,
            valorUnitario: parseFloat(row.querySelector('.item-price')?.value) || 0,
            quantidade:   parseInt(row.querySelector('.item-qty')?.value, 10)  || 1,
        }));

        const totalItems = items.reduce((s, i) => s + i.valorUnitario * i.quantidade, 0);

        const payload = {
            nomeRazao:    form.nomeRazao.value.trim(),
            fantasia:     form.nomeRazao.value.trim(),
            cpfCnpj:      form.cpfCnpj.value.trim(),
            email:        form.email.value.trim(),
            celular:      form.celular.value.trim(),
            telefone:     form.celular.value.trim(),
            cep:          form.cep.value.trim(),
            endereco:     form.endereco.value.trim(),
            numero:       form.numero.value.trim(),
            bairro:       form.bairro.value.trim(),
            cidade:       form.cidade.value.trim(),
            uf:           form.uf.value,
            complemento:  form.complemento.value.trim(),
            valorFrete:   0,
            total:        round2(totalItems),
            items,
        };

        UI.setLoading(btn, true);
        try {
            const result = await Api.post('/api/orders', payload);
            const ok     = result.marketplace_status === 'sent';
            UI.showApiResult('order-api-result', result, !ok);
            UI.showToast(
                ok ? `Pedido enviado com sucesso (cód. Precode: ${result.marketplace_codigo_pedido ?? 'N/A'})`
                   : `Pedido salvo localmente — erro: ${result.error}`,
                ok ? 'success' : 'error',
            );
            if (ok) { form.reset(); document.getElementById('order-items-list').innerHTML = ''; Orders.recalcTotal(); }
            await Orders.load();
        } catch (err) {
            UI.showApiResult('order-api-result', { error: err.message }, true);
            UI.showToast(err.message, 'error');
        } finally {
            UI.setLoading(btn, false);
        }
    },
};

function round2(v) { return Math.round(v * 100) / 100; }

// ─── Bootstrap ────────────────────────────────────────────────────────────────

function initTabs() {
    const tabs    = document.querySelectorAll('.app-nav__tab');
    const loaders = {
        products: async () => { await Products.load(); },
        updates:  async () => { await Updates.loadProducts(); await Updates.load(); },
        orders:   async () => { await Orders.load(); },
    };

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('app-nav__tab--active'));
            tab.classList.add('app-nav__tab--active');

            document.querySelectorAll('.tab-panel').forEach(p => {
                p.hidden = p.id !== `tab-${tab.dataset.tab}`;
                if (!p.hidden) p.classList.add('tab-panel--active');
                else p.classList.remove('tab-panel--active');
            });

            loaders[tab.dataset.tab]?.();
        });
    });
}

function initForms() {
    document.getElementById('form-product')?.addEventListener('submit', Products.handleSubmit);
    document.getElementById('form-price')?.addEventListener('submit', Updates.handlePriceSubmit);
    document.getElementById('form-stock')?.addEventListener('submit', Updates.handleStockSubmit);
    document.getElementById('form-order')?.addEventListener('submit', Orders.handleSubmit);

    document.getElementById('btn-add-item')?.addEventListener('click', Orders.addItem);
    document.getElementById('btn-refresh-products')?.addEventListener('click', Products.load);
    document.getElementById('btn-refresh-updates-products')?.addEventListener('click', Updates.loadProducts);
    document.getElementById('btn-refresh-updates')?.addEventListener('click', Updates.load);
    document.getElementById('btn-refresh-orders')?.addEventListener('click', Orders.load);
    document.getElementById('btn-sync-orders')?.addEventListener('click', Orders.handleSync);

    // Toggle order form
    const toggleBtn  = document.getElementById('btn-toggle-order-form');
    const orderBody  = document.getElementById('order-form-body');
    toggleBtn?.addEventListener('click', () => {
        const hidden = orderBody.hidden;
        orderBody.hidden       = !hidden;
        toggleBtn.textContent  = hidden ? '− Recolher' : '+ Expandir';
        if (!hidden) return;
        if (Products.list.length === 0) Products.load().then(() => {
            if (document.querySelectorAll('.order-item-row').length === 0) Orders.addItem();
        });
        else if (document.querySelectorAll('.order-item-row').length === 0) Orders.addItem();
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initTabs();
    initForms();
    Products.load();
});
