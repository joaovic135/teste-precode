'use strict';

const Api = {
    async request(method, url, body = null) {
        const options = {
            method,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        };

        if (body !== null) {
            options.body = JSON.stringify(body);
        }

        const response = await fetch(url, options);
        const json     = await response.json();

        if (!json.success) {
            throw new Error(json.error ?? 'Erro desconhecido na requisição');
        }

        return json.data;
    },

    get:  (url)         => Api.request('GET',  url),
    post: (url, body)   => Api.request('POST', url, body),
    put:  (url, body)   => Api.request('PUT',  url, body),
};

const UI = {
    formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
    },

    formatDate(dateStr) {
        if (!dateStr) return '—';
        return new Intl.DateTimeFormat('pt-BR', {
            day: '2-digit', month: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit',
        }).format(new Date(dateStr));
    },

    productStatusBadge(status) {
        const map = {
            sent:    { cls: 'success', label: 'Enviado' },
            error:   { cls: 'error',   label: 'Erro' },
            pending: { cls: 'pending', label: 'Pendente' },
        };
        const { cls, label } = map[status] ?? { cls: 'pending', label: status };
        return `<span class="badge badge--${cls}">${label}</span>`;
    },

    updateStatusBadge(status) {
        const map = {
            sent:    { cls: 'success', label: 'Enviado' },
            error:   { cls: 'error',   label: 'Erro' },
            pending: { cls: 'warning', label: 'Pendente' },
        };
        const { cls, label } = map[status] ?? { cls: 'pending', label: status };
        return `<span class="badge badge--${cls}">${label}</span>`;
    },

    orderStatusBadge(status) {
        const map = {
            novo:         { cls: 'info',    label: 'Novo' },
            aprovado:     { cls: 'success', label: 'Aprovado' },
            faturado:     { cls: 'success', label: 'Faturado' },
            cancelado:    { cls: 'error',   label: 'Cancelado' },
            entregue:     { cls: 'success', label: 'Entregue' },
        };
        const { cls, label } = map[status] ?? { cls: 'pending', label: status };
        return `<span class="badge badge--${cls}">${label}</span>`;
    },

    showToast(message, type = 'info') {
        const icons = { success: '✓', error: '✕', info: 'ℹ' };
        const toast = document.createElement('div');
        toast.className = `toast toast--${type}`;
        toast.innerHTML = `
            <span class="toast__icon">${icons[type] ?? icons.info}</span>
            <span class="toast__message">${message}</span>
        `;

        const container = document.getElementById('toast-container');
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity .3s';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    },

    setLoading(buttonEl, loading) {
        if (loading) {
            buttonEl.dataset.originalText = buttonEl.textContent;
            buttonEl.textContent = 'Aguarde...';
            buttonEl.disabled = true;
        } else {
            buttonEl.textContent = buttonEl.dataset.originalText ?? buttonEl.textContent;
            buttonEl.disabled = false;
        }
    },

    renderEmpty(tbodyId, colspan, message = 'Nenhum registro encontrado') {
        document.getElementById(tbodyId).innerHTML =
            `<tr><td colspan="${colspan}" class="table-empty">${message}</td></tr>`;
    },
};

const Products = {
    async load() {
        try {
            const products = await Api.get('/api/products');
            Products.render(products);
        } catch (err) {
            UI.renderEmpty('tbody-products', 7, 'Erro ao carregar produtos');
            UI.showToast(err.message, 'error');
        }
    },

    render(products) {
        const tbody = document.getElementById('tbody-products');

        if (!products.length) {
            UI.renderEmpty('tbody-products', 7);
            return;
        }

        tbody.innerHTML = products.map(p => `
            <tr>
                <td class="table-mono">${p.sku}</td>
                <td>${p.name}</td>
                <td>${p.category || '—'}</td>
                <td>${UI.formatCurrency(p.price)}</td>
                <td>${p.stock}</td>
                <td>${UI.productStatusBadge(p.marketplace_status)}</td>
                <td>${UI.formatDate(p.created_at)}</td>
            </tr>
        `).join('');
    },

    async handleSubmit(e) {
        e.preventDefault();

        const form   = e.target;
        const btn    = form.querySelector('[type="submit"]');
        const images = document.getElementById('product-images').value
            .split('\n')
            .map(u => u.trim())
            .filter(u => u.length > 0);

        const payload = {
            sku:         form.sku.value.trim(),
            name:        form.name.value.trim(),
            category:    form.category.value.trim(),
            description: form.description.value.trim(),
            price:       parseFloat(form.price.value),
            stock:       parseInt(form.stock.value, 10),
            images,
        };

        UI.setLoading(btn, true);

        try {
            const result = await Api.post('/api/products', payload);
            const status = result.marketplace_status === 'sent' ? 'success' : 'error';
            const label  = result.marketplace_status === 'sent'
                ? `Produto '${result.sku}' cadastrado e enviado ao marketplace`
                : `Produto salvo, mas houve erro no marketplace: ${result.error}`;

            UI.showToast(label, status);
            form.reset();
            await Products.load();
        } catch (err) {
            UI.showToast(err.message, 'error');
        } finally {
            UI.setLoading(btn, false);
        }
    },
};

const Updates = {
    async load() {
        try {
            const updates = await Api.get('/api/updates');
            Updates.render(updates);
        } catch (err) {
            UI.renderEmpty('tbody-updates', 6, 'Erro ao carregar histórico');
            UI.showToast(err.message, 'error');
        }
    },

    render(updates) {
        const tbody = document.getElementById('tbody-updates');

        if (!updates.length) {
            UI.renderEmpty('tbody-updates', 6);
            return;
        }

        tbody.innerHTML = updates.map(u => {
            const isPrice = u.update_type === 'price';
            const fmt     = v => isPrice ? UI.formatCurrency(v) : v;
            const label   = isPrice ? 'Preço' : 'Estoque';

            return `
                <tr>
                    <td class="table-mono">${u.product_sku}</td>
                    <td>${label}</td>
                    <td>${fmt(u.old_value ?? '—')}</td>
                    <td>${fmt(u.new_value)}</td>
                    <td>${UI.updateStatusBadge(u.status)}</td>
                    <td>${UI.formatDate(u.created_at)}</td>
                </tr>
            `;
        }).join('');
    },

    async handlePriceSubmit(e) {
        e.preventDefault();

        const form = e.target;
        const btn  = form.querySelector('[type="submit"]');
        const sku  = form.sku.value.trim();

        if (!sku) {
            UI.showToast('Informe o SKU do produto', 'error');
            return;
        }

        UI.setLoading(btn, true);

        try {
            const result = await Api.put(`/api/products/${encodeURIComponent(sku)}/price`, {
                price: parseFloat(form.price.value),
            });

            const type  = result.status === 'sent' ? 'success' : 'error';
            const label = result.status === 'sent'
                ? `Preço do SKU '${sku}' atualizado para ${UI.formatCurrency(result.new_price)}`
                : `Erro ao atualizar preço: ${result.error}`;

            UI.showToast(label, type);
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

        const form  = e.target;
        const btn   = form.querySelector('[type="submit"]');
        const sku   = form.sku.value.trim();
        const stock = parseInt(form.stock.value, 10);

        if (!sku) {
            UI.showToast('Informe o SKU do produto', 'error');
            return;
        }

        if (isNaN(stock) || stock < 0) {
            UI.showToast('Estoque deve ser um número inteiro não negativo', 'error');
            return;
        }

        UI.setLoading(btn, true);

        try {
            const result = await Api.put(`/api/products/${encodeURIComponent(sku)}/stock`, {
                stock,
            });

            const type  = result.status === 'sent' ? 'success' : 'error';
            const label = result.status === 'sent'
                ? `Estoque do SKU '${sku}' atualizado para ${result.new_stock} unidades`
                : `Erro ao atualizar estoque: ${result.error}`;

            UI.showToast(label, type);
            form.reset();
            await Updates.load();
        } catch (err) {
            UI.showToast(err.message, 'error');
        } finally {
            UI.setLoading(btn, false);
        }
    },
};

const Orders = {
    async load() {
        try {
            const orders = await Api.get('/api/orders');
            Orders.render(orders);
        } catch (err) {
            UI.renderEmpty('tbody-orders', 7, 'Erro ao carregar pedidos');
            UI.showToast(err.message, 'error');
        }
    },

    render(orders) {
        const tbody = document.getElementById('tbody-orders');

        if (!orders.length) {
            UI.renderEmpty('tbody-orders', 7);
            return;
        }

        tbody.innerHTML = orders.map(o => {
            const processBtn = o.processed_at
                ? '<span class="badge badge--success">Processado</span>'
                : `<button class="btn btn--danger btn--sm" onclick="Orders.handleProcess('${o.marketplace_order_id}')">Processar</button>`;

            return `
                <tr>
                    <td class="table-mono">${o.marketplace_order_id}</td>
                    <td>${o.customer_name || '—'}</td>
                    <td>${UI.formatCurrency(o.total)}</td>
                    <td>${UI.orderStatusBadge(o.status)}</td>
                    <td>${UI.formatDate(o.processed_at)}</td>
                    <td>${UI.formatDate(o.created_at)}</td>
                    <td>${processBtn}</td>
                </tr>
            `;
        }).join('');
    },

    async handleSync() {
        const btn = document.getElementById('btn-sync-orders');
        UI.setLoading(btn, true);

        try {
            const result = await Api.post('/api/orders/sync');
            const label  = result.synced > 0
                ? `${result.synced} pedido(s) novo(s) importado(s) de ${result.total_received} recebidos`
                : `Nenhum pedido novo. ${result.total_received} recebidos do marketplace.`;

            UI.showToast(label, 'success');
            await Orders.load();
        } catch (err) {
            UI.showToast(err.message, 'error');
        } finally {
            UI.setLoading(btn, false);
        }
    },

    async handleProcess(marketplaceOrderId) {
        try {
            await Api.post(`/api/orders/${encodeURIComponent(marketplaceOrderId)}/process`);
            UI.showToast(`Pedido '${marketplaceOrderId}' marcado como processado`, 'success');
            await Orders.load();
        } catch (err) {
            UI.showToast(err.message, 'error');
        }
    },
};

function initTabs() {
    const tabs    = document.querySelectorAll('.app-nav__tab');
    const loaders = { products: Products.load, updates: Updates.load, orders: Orders.load };

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.tab;

            tabs.forEach(t => t.classList.remove('app-nav__tab--active'));
            tab.classList.add('app-nav__tab--active');

            document.querySelectorAll('.tab-panel').forEach(panel => {
                panel.hidden = panel.id !== `tab-${target}`;
            });

            loaders[target]?.();
        });
    });
}

function initForms() {
    document.getElementById('form-product').addEventListener('submit', Products.handleSubmit);
    document.getElementById('form-price').addEventListener('submit', Updates.handlePriceSubmit);
    document.getElementById('form-stock').addEventListener('submit', Updates.handleStockSubmit);
    document.getElementById('btn-sync-orders').addEventListener('click', Orders.handleSync);
    document.getElementById('btn-refresh-products').addEventListener('click', Products.load);
    document.getElementById('btn-refresh-updates').addEventListener('click', Updates.load);
    document.getElementById('btn-refresh-orders').addEventListener('click', Orders.load);
}

document.addEventListener('DOMContentLoaded', () => {
    initTabs();
    initForms();
    Products.load();
});
