@extends('includes.header-admin')

@section('title', 'Simulador de Promoção')

@section('content')
<div class="space-y-4 lg:space-y-6">

    {{-- Page Header --}}
    <div class="border border-[var(--color-lab-border)] bg-white overflow-hidden">
        <div class="px-5 py-6 sm:px-6 sm:py-7 bg-[linear-gradient(135deg,#ffffff_0%,#f8fafc_100%)]">
            <p class="font-mono text-[10px] uppercase tracking-[0.22em] text-[var(--color-lab-muted)] mb-2">Ferramentas</p>
            <h1 class="text-3xl sm:text-4xl font-bold text-black tracking-tight">Simulador de Promoção</h1>
            <p class="mt-2 max-w-2xl text-sm text-[var(--color-lab-muted)]">
                Selecione produtos, aplique desconto e compare margem antes de publicar.
            </p>
        </div>
    </div>

    {{-- Simulator --}}
    <div class="border border-[var(--color-lab-border)] bg-white">
        <div class="p-4 sm:p-6 space-y-6">
            <form id="promotion-simulator-form" class="space-y-5">
                @csrf
                <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)] gap-5">
                    <div class="border border-[var(--color-lab-border)] p-4 space-y-4">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Produtos</p>
                                <p class="font-mono text-xs text-[var(--color-lab-muted)] mt-1">Use a busca para filtrar e combine SKUs específicos ou o catálogo inteiro.</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" id="simulator-select-all" class="px-3 py-2 border border-[var(--color-lab-border)] font-mono text-[10px] uppercase tracking-widest text-black hover:border-black transition-colors">Selecionar todos</button>
                                <button type="button" id="simulator-clear-all" class="px-3 py-2 border border-[var(--color-lab-border)] font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] hover:border-black hover:text-black transition-colors">Limpar</button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_auto] gap-3 lg:items-center">
                            <input id="simulator-product-search" type="search" placeholder="Buscar produto ou marca"
                                   class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono text-sm focus:outline-none focus:border-black">
                            <div class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">
                                Selecionados: <span id="simulator-selected-count" class="text-black font-bold">0</span>
                            </div>
                        </div>

                        <div id="simulator-product-list" class="h-[24rem] lg:h-[26rem] overflow-y-auto border border-[var(--color-lab-border)] admin-mobile-scroll divide-y divide-[var(--color-lab-border)]">
                            @foreach($produtos_analytics as $produto)
                            @php
                                $lucro = $produto->lucro_bruto_unitario;
                                $margem = $produto->margem_bruta_percentual;
                            @endphp
                            <label class="flex items-start gap-3 px-4 py-4 hover:bg-[var(--color-lab-bg)] cursor-pointer transition-colors" data-product-option data-filter-text="{{ strtolower($produto->nome . ' ' . ($produto->marca ?? '')) }}">
                                <input type="checkbox"
                                       name="product_ids[]"
                                       value="{{ $produto->id }}"
                                       class="simulator-product-checkbox mt-1 h-4 w-4 border-[var(--color-lab-border)] text-black focus:ring-black">
                                <div class="min-w-0 flex-1 space-y-3">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="font-mono text-sm font-bold text-black break-words">{{ $produto->nome }}</span>
                                                @if(!$produto->ativo)
                                                    <span class="inline-block px-2 py-0.5 font-mono text-[10px] border border-gray-300 text-gray-500 bg-gray-50">Inativo</span>
                                                @endif
                                                @if($produto->custo_compra === null)
                                                    <span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-blue-50 text-blue-600 border border-blue-200">Sem custo</span>
                                                @endif
                                            </div>
                                            <div class="mt-1 font-mono text-[11px] uppercase tracking-widest text-gray-500">{{ $produto->marca ?: 'Sem marca' }}</div>
                                        </div>
                                        <div class="shrink-0 text-right">
                                            <div class="font-mono text-[10px] uppercase tracking-widest text-gray-500">Preço de venda</div>
                                            <div class="font-mono text-sm font-bold text-black">R$ {{ number_format($produto->preco_com_desconto, 2, ',', '.') }}</div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                        <div class="border border-[var(--color-lab-border)] bg-white px-3 py-2">
                                            <div class="font-mono text-[10px] uppercase tracking-widest text-gray-500">Custo</div>
                                            <div class="mt-1 font-mono text-sm font-bold {{ $produto->custo_efetivo !== null ? 'text-black' : 'text-blue-600' }}">
                                                {{ $produto->custo_efetivo !== null ? 'R$ ' . number_format($produto->custo_efetivo, 2, ',', '.') : 'Pendente' }}
                                            </div>
                                        </div>
                                        <div class="border border-[var(--color-lab-border)] bg-white px-3 py-2">
                                            <div class="font-mono text-[10px] uppercase tracking-widest text-gray-500">Lucro unit.</div>
                                            <div class="mt-1 font-mono text-sm font-bold {{ $lucro === null ? 'text-blue-600' : ($lucro < 0 ? 'text-red-600' : 'text-black') }}">
                                                {{ $lucro !== null ? 'R$ ' . number_format($lucro, 2, ',', '.') : 'Sem custo' }}
                                            </div>
                                        </div>
                                        <div class="border border-[var(--color-lab-border)] bg-white px-3 py-2">
                                            <div class="font-mono text-[10px] uppercase tracking-widest text-gray-500">Margem</div>
                                            <div class="mt-1 font-mono text-sm font-bold {{ $margem === null ? 'text-blue-600' : ($margem < 0 ? 'text-red-600' : ($margem < 20 ? 'text-yellow-700' : 'text-green-700')) }}">
                                                {{ $margem !== null ? number_format($margem, 1, ',', '.') . '%' : 'Sem custo' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="border border-[var(--color-lab-border)] p-4 space-y-4">
                        <div>
                            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Parâmetros da simulação</p>
                            <p class="font-mono text-xs text-[var(--color-lab-muted)] mt-1">O volume base vem do histórico real de pedidos em status de performance.</p>
                        </div>

                        <div>
                            <label for="simulator-period-days" class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-1">Período base</label>
                            <select id="simulator-period-days" name="period_days" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono text-sm focus:outline-none focus:border-black">
                                @foreach($promotion_simulator_periods as $period)
                                <option value="{{ $period }}" @selected($period === $promotion_simulator_default_period)>Últimos {{ $period }} dias</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="simulator-discount-percent" class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-1">Desconto percentual</label>
                            <input id="simulator-discount-percent" name="discount_percent" type="number" min="0" max="100" step="0.01" value="0"
                                   class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono text-sm focus:outline-none focus:border-black">
                        </div>

                        <div>
                            <label for="simulator-extra-unit-cost" class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-1">Custo extra por unidade</label>
                            <input id="simulator-extra-unit-cost" name="extra_unit_cost" type="number" min="0" step="0.01" value="0"
                                   class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono text-sm focus:outline-none focus:border-black">
                        </div>

                        <div>
                            <label for="simulator-extra-order-cost" class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-1">Custo extra por pedido</label>
                            <input id="simulator-extra-order-cost" name="extra_order_cost" type="number" min="0" step="0.01" value="0"
                                   class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono text-sm focus:outline-none focus:border-black">
                            <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-1">Rateado automaticamente entre as unidades do recorte selecionado.</p>
                        </div>

                        <button type="submit" id="promotion-simulator-submit" class="w-full bg-black text-white font-mono text-[10px] uppercase tracking-widest py-3 hover:bg-gray-800 transition-colors">
                            Rodar simulação
                        </button>
                    </div>
                </div>
            </form>

            <div id="promotion-simulator-empty" class="border border-dashed border-[var(--color-lab-border)] px-4 py-8 text-center">
                <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Aguardando cenário</p>
                <p class="font-mono text-xs text-[var(--color-lab-muted)] mt-2">Selecione ao menos um produto e rode a simulação para ver receita, lucro e margem comparados.</p>
            </div>

            <div id="promotion-simulator-results" class="hidden space-y-4">
                <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] px-4 py-3">
                    <div>
                        <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Base analisada</p>
                        <p id="promotion-simulator-meta" class="font-mono text-xs text-black mt-1"></p>
                    </div>
                    <div class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">
                        Pedidos base: <span id="promotion-simulator-orders" class="text-black font-bold">0</span>
                    </div>
                </div>

                <div id="promotion-simulator-alerts" class="space-y-2"></div>

                <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
                    <div class="border border-[var(--color-lab-border)] p-4">
                        <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Cenário Atual</p>
                        <div class="mt-4 space-y-2 font-mono text-xs">
                            <div class="flex items-center justify-between gap-3"><span class="text-[var(--color-lab-muted)]">Receita</span><span id="sim-current-revenue" class="text-black font-bold">R$ 0,00</span></div>
                            <div class="flex items-center justify-between gap-3"><span class="text-[var(--color-lab-muted)]">Custo</span><span id="sim-current-cost" class="text-black">R$ 0,00</span></div>
                            <div class="flex items-center justify-between gap-3"><span class="text-[var(--color-lab-muted)]">Lucro</span><span id="sim-current-profit" class="text-black">R$ 0,00</span></div>
                            <div class="flex items-center justify-between gap-3"><span class="text-[var(--color-lab-muted)]">Margem</span><span id="sim-current-margin" class="text-black">0,0%</span></div>
                            <div class="flex items-center justify-between gap-3"><span class="text-[var(--color-lab-muted)]">Unidades</span><span id="sim-current-units" class="text-black">0</span></div>
                        </div>
                    </div>

                    <div class="border border-[var(--color-lab-border)] p-4 bg-[linear-gradient(135deg,#ffffff_0%,#f8fafc_100%)]">
                        <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Cenário Simulado</p>
                        <div class="mt-4 space-y-2 font-mono text-xs">
                            <div class="flex items-center justify-between gap-3"><span class="text-[var(--color-lab-muted)]">Receita</span><span id="sim-simulated-revenue" class="text-black font-bold">R$ 0,00</span></div>
                            <div class="flex items-center justify-between gap-3"><span class="text-[var(--color-lab-muted)]">Custo</span><span id="sim-simulated-cost" class="text-black">R$ 0,00</span></div>
                            <div class="flex items-center justify-between gap-3"><span class="text-[var(--color-lab-muted)]">Lucro</span><span id="sim-simulated-profit" class="text-black">R$ 0,00</span></div>
                            <div class="flex items-center justify-between gap-3"><span class="text-[var(--color-lab-muted)]">Margem</span><span id="sim-simulated-margin" class="text-black">0,0%</span></div>
                            <div class="flex items-center justify-between gap-3"><span class="text-[var(--color-lab-muted)]">Unidades</span><span id="sim-simulated-units" class="text-black">0</span></div>
                        </div>
                    </div>

                    <div class="border border-[var(--color-lab-border)] p-4">
                        <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Delta</p>
                        <div class="mt-4 space-y-2 font-mono text-xs">
                            <div class="flex items-center justify-between gap-3"><span class="text-[var(--color-lab-muted)]">Receita</span><span id="sim-delta-revenue" class="text-black">R$ 0,00</span></div>
                            <div class="flex items-center justify-between gap-3"><span class="text-[var(--color-lab-muted)]">Custo</span><span id="sim-delta-cost" class="text-black">R$ 0,00</span></div>
                            <div class="flex items-center justify-between gap-3"><span class="text-[var(--color-lab-muted)]">Lucro</span><span id="sim-delta-profit" class="text-black font-bold">R$ 0,00</span></div>
                            <div class="flex items-center justify-between gap-3"><span class="text-[var(--color-lab-muted)]">Margem</span><span id="sim-delta-margin" class="text-black">0,0 p.p.</span></div>
                            <div class="flex items-center justify-between gap-3"><span class="text-[var(--color-lab-muted)]">Produtos</span><span id="sim-products-count" class="text-black">0</span></div>
                        </div>
                    </div>
                </div>

                <div class="border border-[var(--color-lab-border)] overflow-x-auto admin-mobile-scroll">
                    <table class="w-full min-w-[1120px] text-sm">
                        <thead>
                            <tr class="border-b border-[var(--color-lab-border)] bg-[var(--color-lab-bg)]">
                                <th class="text-left px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Produto</th>
                                <th class="text-right px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Unidades</th>
                                <th class="text-right px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Preço atual</th>
                                <th class="text-right px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Preço simulado</th>
                                <th class="text-right px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Custo médio</th>
                                <th class="text-right px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Lucro unit. atual</th>
                                <th class="text-right px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Lucro unit. simulado</th>
                                <th class="text-right px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Margem atual</th>
                                <th class="text-right px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Margem simulada</th>
                                <th class="text-right px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Delta lucro</th>
                            </tr>
                        </thead>
                        <tbody id="promotion-simulator-products"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
(function () {
    var CSRF = document.querySelector('meta[name="csrf-token"]').content;

    function initPromotionSimulator() {
        var form = document.getElementById('promotion-simulator-form');
        if (!form) return;

        var submitBtn = document.getElementById('promotion-simulator-submit');
        var searchInput = document.getElementById('simulator-product-search');
        var list = document.getElementById('simulator-product-list');
        var countEl = document.getElementById('simulator-selected-count');
        var emptyEl = document.getElementById('promotion-simulator-empty');
        var resultsEl = document.getElementById('promotion-simulator-results');
        var alertsEl = document.getElementById('promotion-simulator-alerts');
        var productsEl = document.getElementById('promotion-simulator-products');
        var metaEl = document.getElementById('promotion-simulator-meta');
        var ordersEl = document.getElementById('promotion-simulator-orders');
        var selectAllBtn = document.getElementById('simulator-select-all');
        var clearAllBtn = document.getElementById('simulator-clear-all');

        function formatMoney(value) {
            return 'R$ ' + Number(value || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function formatPercent(value) {
            return Number(value || 0).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%';
        }

        function formatDeltaPercent(value) {
            var num = Number(value || 0);
            var prefix = num > 0 ? '+' : '';
            return prefix + num.toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + ' p.p.';
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function getCheckedBoxes() {
            return Array.from(form.querySelectorAll('.simulator-product-checkbox:checked'));
        }

        function updateSelectedCount() {
            countEl.textContent = String(getCheckedBoxes().length);
        }

        function applyFilter() {
            var term = (searchInput.value || '').trim().toLowerCase();
            list.querySelectorAll('[data-product-option]').forEach(function (row) {
                var haystack = row.dataset.filterText || '';
                row.classList.toggle('hidden', term !== '' && haystack.indexOf(term) === -1);
            });
        }

        function badgeForMargin(margin, missingCost) {
            if (missingCost) {
                return '<span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-blue-50 text-blue-500 border border-blue-200">Sem custo</span>';
            }
            if (margin < 0) {
                return '<span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-red-100 text-red-700 font-bold">' + formatPercent(margin) + '</span>';
            }
            if (margin < 20) {
                return '<span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-yellow-100 text-yellow-700 font-bold">' + formatPercent(margin) + '</span>';
            }
            return '<span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-green-100 text-green-700 font-bold">' + formatPercent(margin) + '</span>';
        }

        function toneClass(value, positiveClass, negativeClass) {
            var num = Number(value || 0);
            if (num < 0) return negativeClass;
            if (num > 0) return positiveClass;
            return 'text-black';
        }

        function renderAlerts(alerts) {
            alertsEl.innerHTML = '';
            if (!alerts || !alerts.length) return;

            alerts.forEach(function (alertItem) {
                var tone = 'border-[var(--color-lab-border)] bg-white text-black';
                if (alertItem.level === 'critical') tone = 'border-red-300 bg-red-50 text-red-700';
                else if (alertItem.level === 'warning') tone = 'border-yellow-300 bg-yellow-50 text-yellow-700';
                else if (alertItem.level === 'info') tone = 'border-blue-300 bg-blue-50 text-blue-700';

                var alertEl = document.createElement('div');
                alertEl.className = 'border px-4 py-3 font-mono text-xs ' + tone;
                alertEl.textContent = alertItem.message;
                alertsEl.appendChild(alertEl);
            });
        }

        function renderProducts(products) {
            productsEl.innerHTML = '';

            products.forEach(function (product) {
                var row = document.createElement('tr');
                row.className = 'border-b border-[var(--color-lab-border)]';
                row.innerHTML = [
                    '<td class="px-4 py-3">',
                        '<div class="min-w-0">',
                            '<div class="font-mono text-xs text-black">' + escapeHtml(product.nome) + '</div>',
                            '<div class="mt-1 flex flex-wrap items-center gap-2">',
                                '<span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">' + escapeHtml(product.marca || 'Sem marca') + '</span>',
                                (product.ativo ? '' : '<span class="inline-block px-2 py-0.5 font-mono text-[10px] border border-gray-300 text-gray-400">Inativo</span>'),
                                (product.missing_cost ? '<span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-blue-50 text-blue-500 border border-blue-200">Sem custo</span>' : ''),
                            '</div>',
                        '</div>',
                    '</td>',
                    '<td class="px-4 py-3 text-right font-mono text-xs text-black">' + product.units + '</td>',
                    '<td class="px-4 py-3 text-right font-mono text-xs text-black">' + formatMoney(product.current_price) + '</td>',
                    '<td class="px-4 py-3 text-right font-mono text-xs text-black">' + formatMoney(product.simulated_price) + '</td>',
                    '<td class="px-4 py-3 text-right font-mono text-xs ' + (product.missing_cost ? 'text-blue-500' : 'text-black') + '">' + (product.missing_cost ? 'Sem custo' : formatMoney(product.average_base_cost_per_unit)) + '</td>',
                    '<td class="px-4 py-3 text-right font-mono text-xs ' + toneClass(product.current_unit_profit, 'text-black', 'text-red-600') + '">' + formatMoney(product.current_unit_profit) + '</td>',
                    '<td class="px-4 py-3 text-right font-mono text-xs ' + toneClass(product.simulated_unit_profit, 'text-black', 'text-red-600') + '">' + formatMoney(product.simulated_unit_profit) + '</td>',
                    '<td class="px-4 py-3 text-right">' + badgeForMargin(product.current_margin_percent, product.missing_cost) + '</td>',
                    '<td class="px-4 py-3 text-right">' + badgeForMargin(product.simulated_margin_percent, product.missing_cost) + '</td>',
                    '<td class="px-4 py-3 text-right font-mono text-xs ' + toneClass(product.delta_profit_total, 'text-green-700', 'text-red-600') + '">' + formatMoney(product.delta_profit_total) + '</td>'
                ].join('');
                productsEl.appendChild(row);
            });
        }

        function updateSummary(simulation) {
            var current = simulation.summary.current;
            var simulated = simulation.summary.simulated;
            var delta = simulation.summary.delta;
            var meta = simulation.meta;

            metaEl.textContent = meta.products_count + ' produto(s), ' + meta.selected_units + ' unidade(s), ' + meta.period_label + '.';
            ordersEl.textContent = String(meta.selected_orders);

            document.getElementById('sim-current-revenue').textContent = formatMoney(current.revenue_total);
            document.getElementById('sim-current-cost').textContent = formatMoney(current.cost_total);
            document.getElementById('sim-current-profit').textContent = formatMoney(current.profit_total);
            document.getElementById('sim-current-profit').className = 'text-black ' + toneClass(current.profit_total, 'text-black', 'text-red-600');
            document.getElementById('sim-current-margin').textContent = formatPercent(current.margin_percent);
            document.getElementById('sim-current-margin').className = toneClass(current.margin_percent - 20, 'text-black', 'text-yellow-600');
            document.getElementById('sim-current-units').textContent = String(current.units);

            document.getElementById('sim-simulated-revenue').textContent = formatMoney(simulated.revenue_total);
            document.getElementById('sim-simulated-cost').textContent = formatMoney(simulated.cost_total);
            document.getElementById('sim-simulated-profit').textContent = formatMoney(simulated.profit_total);
            document.getElementById('sim-simulated-profit').className = toneClass(simulated.profit_total, 'text-black font-bold', 'text-red-600 font-bold');
            document.getElementById('sim-simulated-margin').textContent = formatPercent(simulated.margin_percent);
            document.getElementById('sim-simulated-margin').className = toneClass(simulated.margin_percent - 20, 'text-black', 'text-yellow-600');
            document.getElementById('sim-simulated-units').textContent = String(simulated.units);

            document.getElementById('sim-delta-revenue').textContent = formatMoney(delta.revenue_total);
            document.getElementById('sim-delta-revenue').className = toneClass(delta.revenue_total, 'text-green-700', 'text-red-600');
            document.getElementById('sim-delta-cost').textContent = formatMoney(delta.cost_total);
            document.getElementById('sim-delta-cost').className = toneClass(-delta.cost_total, 'text-green-700', 'text-red-600');
            document.getElementById('sim-delta-profit').textContent = formatMoney(delta.profit_total);
            document.getElementById('sim-delta-profit').className = toneClass(delta.profit_total, 'text-green-700 font-bold', 'text-red-600 font-bold');
            document.getElementById('sim-delta-margin').textContent = formatDeltaPercent(delta.margin_percent);
            document.getElementById('sim-delta-margin').className = toneClass(delta.margin_percent, 'text-green-700', 'text-red-600');
            document.getElementById('sim-products-count').textContent = String(meta.products_count);
        }

        async function handleSubmit(event) {
            event.preventDefault();

            var checked = getCheckedBoxes();
            if (!checked.length) {
                alert('Selecione ao menos um produto para simular.');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Simulando...';

            try {
                var response = await fetch(window.routes.adminDashboardSimulator, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                    },
                    body: JSON.stringify({
                        product_ids: checked.map(function (checkbox) { return Number(checkbox.value); }),
                        period_days: Number(document.getElementById('simulator-period-days').value),
                        discount_percent: Number(document.getElementById('simulator-discount-percent').value || 0),
                        extra_unit_cost: Number(document.getElementById('simulator-extra-unit-cost').value || 0),
                        extra_order_cost: Number(document.getElementById('simulator-extra-order-cost').value || 0),
                    }),
                });

                var data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Falha ao gerar simulação.');
                }

                updateSummary(data);
                renderAlerts(data.alerts || []);
                renderProducts(data.products || []);

                emptyEl.classList.add('hidden');
                resultsEl.classList.remove('hidden');
            } catch (error) {
                alert('Não foi possível gerar a simulação agora.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Rodar simulação';
            }
        }

        selectAllBtn.addEventListener('click', function () {
            form.querySelectorAll('.simulator-product-checkbox').forEach(function (checkbox) {
                if (!checkbox.closest('[data-product-option]').classList.contains('hidden')) {
                    checkbox.checked = true;
                }
            });
            updateSelectedCount();
        });

        clearAllBtn.addEventListener('click', function () {
            form.querySelectorAll('.simulator-product-checkbox').forEach(function (checkbox) {
                checkbox.checked = false;
            });
            updateSelectedCount();
        });

        searchInput.addEventListener('input', applyFilter);
        form.querySelectorAll('.simulator-product-checkbox').forEach(function (checkbox) {
            checkbox.addEventListener('change', updateSelectedCount);
        });
        form.addEventListener('submit', handleSubmit);

        updateSelectedCount();
        applyFilter();
    }

    initPromotionSimulator();
})();
</script>
@endsection
