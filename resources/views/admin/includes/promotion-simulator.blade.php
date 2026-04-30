<div id="promotion-simulator" data-section="promotion-simulator" data-default-open="true" class="border border-[var(--color-lab-border)] bg-white scroll-mt-28">
    <div data-section-toggle class="flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:justify-between px-4 sm:px-6 py-4 cursor-pointer hover:bg-gray-50 border-b border-[var(--color-lab-border)] select-none">
        <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">
            <span data-section-arrow>&#9660;</span>&nbsp; Simulador de Promoção
        </p>
        <span class="font-mono text-xs text-[var(--color-lab-muted)]">Selecione produtos, aplique desconto e compare margem antes de publicar</span>
    </div>

    <div data-section-content class="p-4 sm:p-6 space-y-6">
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
