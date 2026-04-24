@extends('includes.header-admin')

@section('title', 'Analytics do Produto')

@section('content')
@php
    $receitaTotal = $product_metrics['receita_total'];
    $lucroBrutoTotal = $product_metrics['lucro_bruto_total'];
    $margemBrutaPercentual = $product_metrics['margem_bruta_percentual'];
    $unidadesVendidas = $product_metrics['unidades_vendidas'];
    $pedidosCount = $product_metrics['pedidos_count'];
    $ticketMedioProduto = $product_metrics['ticket_medio_produto'];
    $itensSemCusto = $product_metrics['itens_sem_custo'];
    $ultimoPedidoEm = $product_metrics['ultimo_pedido_em'];
@endphp

<div class="space-y-4 lg:space-y-6">

    {{-- HEADER --}}
    <div class="border border-[var(--color-lab-border)] bg-white overflow-hidden">
        <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)] gap-0">
            <div class="px-5 py-6 sm:px-6 sm:py-7 border-b xl:border-b-0 xl:border-r border-[var(--color-lab-border)] bg-[linear-gradient(135deg,#ffffff_0%,#f8fafc_100%)]">
                <p class="font-mono text-[10px] uppercase tracking-[0.22em] text-[var(--color-lab-muted)] mb-2">Analytics do Produto</p>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <h1 class="text-3xl sm:text-4xl font-bold text-black tracking-tight break-words">{{ $produto->nome }}</h1>
                        <p class="mt-2 max-w-2xl text-sm text-[var(--color-lab-muted)]">
                            {{ $produto->marca ?: 'Sem marca' }}
                            @if($produto->categoria)
                                &middot; {{ $produto->categoria->nome }}
                            @endif
                            &middot; {{ $produto->ativo ? 'Ativo' : 'Inativo' }}
                        </p>
                        <p class="mt-2 font-mono text-[10px] uppercase tracking-[0.18em] text-[var(--color-lab-muted)]">Slug: {{ $produto->slug }}</p>
                    </div>
                    <div class="inline-flex w-fit flex-col border border-[var(--color-lab-border)] bg-white px-4 py-3">
                        <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-[var(--color-lab-muted)]">Período</span>
                        <span class="mt-1 font-mono text-sm font-bold text-black">{{ $period_label }}</span>
                    </div>
                </div>
            </div>

            <div class="px-5 py-6 sm:px-6 sm:py-7 bg-[var(--color-lab-bg)]">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <a href="{{ route('admin.analytics') }}"
                       class="inline-flex min-h-12 items-center justify-center gap-2 px-4 py-3 border border-[var(--color-lab-border)] bg-white font-mono text-[10px] uppercase tracking-widest text-black hover:border-black transition-colors text-center">
                        Voltar ao Analytics
                    </a>
                    <button type="button"
                            onclick="editarProduto({{ $produto->id }})"
                            class="inline-flex min-h-12 items-center justify-center gap-2 px-4 py-3 bg-black text-white font-mono text-[10px] uppercase tracking-widest hover:bg-gray-800 transition-colors text-center">
                        Editar produto
                    </button>
                    <a href="{{ route('site.produto.detalhes', $produto->slug) }}"
                       target="_blank"
                       class="inline-flex min-h-12 items-center justify-center gap-2 px-4 py-3 border border-[var(--color-lab-border)] bg-white font-mono text-[10px] uppercase tracking-widest text-black hover:border-black transition-colors text-center sm:col-span-2">
                        Abrir página pública
                    </a>
                </div>

                <form method="GET" action="{{ route('admin.analytics.products.show', $produto) }}" class="mt-5 border border-[var(--color-lab-border)] bg-white p-4">
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <p class="font-mono text-[10px] uppercase tracking-[0.18em] text-[var(--color-lab-muted)]">Recorte temporal</p>
                        <span class="font-mono text-[10px] text-[var(--color-lab-muted)]">{{ $period_start ? 'Desde ' . $period_start->format('d/m/Y') : 'Acumulado total' }}</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        @foreach($period_options as $option)
                            <a href="{{ route('admin.analytics.products.show', ['produto' => $produto->id, 'period' => $option['value']]) }}"
                               class="inline-flex min-h-11 items-center justify-center border px-3 py-2 font-mono text-[10px] uppercase tracking-widest transition-colors {{ $selected_period === $option['value'] ? 'border-black bg-black text-white' : 'border-[var(--color-lab-border)] bg-white text-black hover:border-black' }}">
                                {{ $option['label'] }}
                            </a>
                        @endforeach
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- KPI GRID 3×2 --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">

        {{-- Receita Total --}}
        <div class="border border-[var(--color-lab-border)] bg-white p-5 sm:p-6">
            <div class="flex items-start justify-between mb-3">
                <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Receita Total</p>
                <span class="inline-block w-2 h-2 rounded-full mt-0.5 {{ $receitaTotal > 0 ? 'bg-black' : 'bg-gray-300' }}"></span>
            </div>
            <p class="text-2xl sm:text-3xl font-bold text-black font-mono">R$ {{ number_format($receitaTotal, 2, ',', '.') }}</p>
            <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-2">{{ $pedidosCount }} pedido(s) no recorte</p>
        </div>

        {{-- Lucro Bruto --}}
        <div class="border border-[var(--color-lab-border)] bg-white p-5 sm:p-6">
            <div class="flex items-start justify-between mb-3">
                <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Lucro Bruto</p>
                <span class="inline-block w-2 h-2 rounded-full mt-0.5 {{ $lucroBrutoTotal >= 0 ? 'bg-black' : 'bg-red-500' }}"></span>
            </div>
            <p class="text-2xl sm:text-3xl font-bold font-mono {{ $lucroBrutoTotal >= 0 ? 'text-black' : 'text-red-600' }}">R$ {{ number_format($lucroBrutoTotal, 2, ',', '.') }}</p>
            <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-2">custo: R$ {{ number_format($product_metrics['custo_total'], 2, ',', '.') }}</p>
        </div>

        {{-- Margem Bruta --}}
        <div class="border border-[var(--color-lab-border)] bg-white p-5 sm:p-6">
            <div class="flex items-start justify-between mb-3">
                <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Margem Bruta</p>
                <span class="inline-block w-2 h-2 rounded-full mt-0.5 {{ $margemBrutaPercentual >= 20 ? 'bg-black' : ($margemBrutaPercentual >= 0 ? 'bg-yellow-400' : 'bg-red-500') }}"></span>
            </div>
            <p class="text-2xl sm:text-3xl font-bold font-mono {{ $margemBrutaPercentual >= 20 ? 'text-black' : ($margemBrutaPercentual >= 0 ? 'text-yellow-600' : 'text-red-600') }}">{{ number_format($margemBrutaPercentual, 1, ',', '.') }}%</p>
            <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-2">sobre a receita do produto</p>
        </div>

        {{-- Unidades Vendidas --}}
        <div class="border border-[var(--color-lab-border)] bg-white p-5 sm:p-6">
            <div class="flex items-start justify-between mb-3">
                <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Unidades Vendidas</p>
                <span class="inline-block w-2 h-2 rounded-full mt-0.5 {{ $unidadesVendidas > 0 ? 'bg-black' : 'bg-gray-300' }}"></span>
            </div>
            <p class="text-2xl sm:text-3xl font-bold text-black font-mono">{{ $unidadesVendidas }}</p>
            <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-2">itens do produto vendidos</p>
        </div>

        {{-- Ticket Médio --}}
        <div class="border border-[var(--color-lab-border)] bg-white p-5 sm:p-6">
            <div class="flex items-start justify-between mb-3">
                <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Ticket Médio</p>
                <span class="inline-block w-2 h-2 rounded-full mt-0.5 {{ $ticketMedioProduto > 0 ? 'bg-black' : 'bg-gray-300' }}"></span>
            </div>
            <p class="text-2xl sm:text-3xl font-bold text-black font-mono">R$ {{ number_format($ticketMedioProduto, 2, ',', '.') }}</p>
            <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-2">receita do produto por pedido</p>
        </div>

        {{-- Itens Sem Custo --}}
        <div class="border border-[var(--color-lab-border)] bg-white p-5 sm:p-6">
            <div class="flex items-start justify-between mb-3">
                <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Itens Sem Custo</p>
                <span class="inline-block w-2 h-2 rounded-full mt-0.5 {{ $itensSemCusto > 0 ? 'bg-yellow-400' : 'bg-gray-300' }}"></span>
            </div>
            <p class="text-2xl sm:text-3xl font-bold text-black font-mono">{{ $itensSemCusto }}</p>
            <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-2">{{ $ultimoPedidoEm ? 'último pedido ' . \Illuminate\Support\Carbon::parse($ultimoPedidoEm)->format('d/m/Y H:i') : 'sem pedidos neste recorte' }}</p>
        </div>

    </div>

    {{-- CHARTS SECTION --}}
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <div class="space-y-4">

        {{-- Chart 1: Receita (área) --}}
        <div class="border border-[var(--color-lab-border)] bg-white p-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Receita ao longo do tempo</p>
                <span class="font-mono text-[10px] text-[var(--color-lab-muted)]">{{ $period_label }}</span>
            </div>
            <div id="chart-receita" class="w-full"></div>
        </div>

        {{-- Chart 2: Unidades (barras) --}}
        <div class="border border-[var(--color-lab-border)] bg-white p-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Unidades vendidas</p>
                <span class="font-mono text-[10px] text-[var(--color-lab-muted)]">{{ $period_label }}</span>
            </div>
            <div id="chart-unidades" class="w-full"></div>
        </div>

        {{-- Chart 3: Margem % (linha) --}}
        <div class="border border-[var(--color-lab-border)] bg-white p-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Margem bruta %</p>
                <span class="font-mono text-[10px] text-[var(--color-lab-muted)]">{{ $period_label }}</span>
            </div>
            <div id="chart-margem" class="w-full"></div>
        </div>

    </div>

    <script>
    (function () {
        var ts = @json($time_series);

        if (!ts || ts.length === 0) {
            ['chart-receita', 'chart-unidades', 'chart-margem'].forEach(function (id) {
                var el = document.getElementById(id);
                if (el) {
                    el.style.minHeight = '60px';
                    el.innerHTML = '<p style="font-family:monospace;font-size:10px;color:#9ca3af;text-transform:uppercase;letter-spacing:.15em;padding:16px 0;">Sem dados no período selecionado</p>';
                }
            });
            return;
        }

        var dates    = ts.map(function (d) { return d.date; });
        var receitas = ts.map(function (d) { return d.receita; });
        var unidades = ts.map(function (d) { return d.unidades; });
        var margens  = ts.map(function (d) { return d.margem; });

        var fontMono = "'Courier New', Courier, monospace";

        var baseChart = {
            toolbar: { show: false },
            fontFamily: fontMono,
            background: '#ffffff',
            animations: { enabled: true, easing: 'easeinout', speed: 500 },
        };

        var baseXaxis = {
            categories: dates,
            labels: {
                style: { fontSize: '9px', colors: '#9ca3af' },
                rotate: -30,
            },
            axisBorder: { color: '#e5e7eb' },
            axisTicks: { color: '#e5e7eb' },
        };

        var baseYaxis = {
            labels: { style: { fontSize: '9px', colors: '#9ca3af' } },
        };

        var baseGrid = { borderColor: '#f3f4f6', strokeDashArray: 3 };
        var baseTooltip = { style: { fontFamily: fontMono, fontSize: '11px' } };

        // Chart 1 — Receita (área com gradiente)
        new ApexCharts(document.querySelector('#chart-receita'), {
            chart: Object.assign({}, baseChart, { type: 'area', height: 230 }),
            series: [{ name: 'Receita', data: receitas }],
            fill: {
                type: 'gradient',
                gradient: { shadeIntensity: 1, opacityFrom: 0.2, opacityTo: 0.02, stops: [0, 100] },
            },
            stroke: { curve: 'smooth', width: 2, colors: ['#000000'] },
            colors: ['#000000'],
            xaxis: baseXaxis,
            yaxis: Object.assign({}, baseYaxis, {
                labels: Object.assign({}, baseYaxis.labels, {
                    formatter: function (v) { return 'R$ ' + v.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, '.'); },
                }),
            }),
            grid: baseGrid,
            tooltip: Object.assign({}, baseTooltip, {
                y: { formatter: function (v) { return 'R$ ' + v.toFixed(2).replace('.', ','); } },
            }),
            markers: { size: ts.length <= 10 ? 4 : 0, colors: ['#000'], strokeColors: '#fff', strokeWidth: 2 },
        }).render();

        // Chart 2 — Unidades (barras)
        new ApexCharts(document.querySelector('#chart-unidades'), {
            chart: Object.assign({}, baseChart, { type: 'bar', height: 210 }),
            series: [{ name: 'Unidades', data: unidades }],
            colors: ['#1f2937'],
            plotOptions: { bar: { borderRadius: 2, columnWidth: '55%' } },
            xaxis: baseXaxis,
            yaxis: Object.assign({}, baseYaxis, {
                labels: Object.assign({}, baseYaxis.labels, {
                    formatter: function (v) { return Math.round(v); },
                }),
            }),
            grid: baseGrid,
            tooltip: Object.assign({}, baseTooltip, {
                y: { formatter: function (v) { return v + ' un.'; } },
            }),
        }).render();

        // Chart 3 — Margem % (linha com anotações)
        new ApexCharts(document.querySelector('#chart-margem'), {
            chart: Object.assign({}, baseChart, { type: 'line', height: 210 }),
            series: [{ name: 'Margem %', data: margens }],
            stroke: { curve: 'smooth', width: 2, colors: ['#000000'] },
            colors: ['#000000'],
            xaxis: baseXaxis,
            yaxis: Object.assign({}, baseYaxis, {
                labels: Object.assign({}, baseYaxis.labels, {
                    formatter: function (v) { return v.toFixed(1) + '%'; },
                }),
            }),
            grid: baseGrid,
            tooltip: Object.assign({}, baseTooltip, {
                y: { formatter: function (v) { return v.toFixed(1) + '%'; } },
            }),
            annotations: {
                yaxis: [
                    {
                        y: 20,
                        borderColor: '#16a34a',
                        borderWidth: 1,
                        strokeDashArray: 4,
                        label: {
                            text: 'Meta 20%',
                            style: { fontFamily: fontMono, fontSize: '9px', color: '#16a34a', background: '#f0fdf4', padding: { left: 4, right: 4, top: 2, bottom: 2 } },
                            position: 'right',
                        },
                    },
                    {
                        y: 0,
                        borderColor: '#dc2626',
                        borderWidth: 1,
                        strokeDashArray: 4,
                    },
                ],
            },
            markers: { size: ts.length <= 10 ? 4 : 0, colors: ['#000'], strokeColors: '#fff', strokeWidth: 2 },
        }).render();
    })();
    </script>

    {{-- POSIÇÃO COMERCIAL + DADOS DE CATÁLOGO --}}
    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)] gap-4">

        {{-- Posição Comercial --}}
        <div class="border border-[var(--color-lab-border)] bg-white p-5 sm:p-6">
            <div class="flex items-center justify-between gap-3 mb-4">
                <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Posição Comercial</p>
                <span class="font-mono text-xs text-[var(--color-lab-muted)]">{{ $period_label }}</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] p-4">
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Pedidos com o produto</p>
                    <p class="mt-2 font-mono text-2xl font-bold text-black">{{ $pedidosCount }}</p>
                </div>
                <div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] p-4">
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Receita por unidade</p>
                    <p class="mt-2 font-mono text-2xl font-bold text-black">
                        R$ {{ number_format($unidadesVendidas > 0 ? ($receitaTotal / $unidadesVendidas) : 0, 2, ',', '.') }}
                    </p>
                </div>

                {{-- Banner de diagnóstico --}}
                <div class="sm:col-span-2 border p-4
                    {{ $pedidosCount === 0 ? 'border-gray-200 bg-gray-50' : ($margemBrutaPercentual >= 20 ? 'border-green-200 bg-green-50' : ($margemBrutaPercentual >= 0 ? 'border-yellow-200 bg-yellow-50' : 'border-red-200 bg-red-50')) }}">
                    <p class="font-mono text-[10px] uppercase tracking-widest mb-2
                        {{ $pedidosCount === 0 ? 'text-gray-500' : ($margemBrutaPercentual >= 20 ? 'text-green-700' : ($margemBrutaPercentual >= 0 ? 'text-yellow-700' : 'text-red-700')) }}">
                        Diagnóstico
                    </p>
                    <p class="text-sm text-black">
                        @if($pedidosCount === 0)
                            O produto não teve pedidos com status de performance neste período.
                        @elseif($margemBrutaPercentual < 0)
                            O produto está vendendo com margem negativa neste recorte e precisa de ajuste imediato.
                        @elseif($margemBrutaPercentual < 20)
                            O produto vendeu no período, mas ainda está abaixo da meta de margem de 20%.
                        @else
                            O produto está com margem saudável no recorte selecionado.
                        @endif
                    </p>
                </div>
            </div>
        </div>

        {{-- Dados de Catálogo --}}
        <div class="border border-[var(--color-lab-border)] bg-white p-5 sm:p-6">
            <div class="flex items-center justify-between gap-3 mb-4">
                <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Dados de Catálogo</p>
                <span class="font-mono text-xs text-[var(--color-lab-muted)]">estado atual</span>
            </div>
            <div class="space-y-3">
                <div class="flex items-center justify-between gap-4 border-b border-[var(--color-lab-border)] pb-3">
                    <span class="font-mono text-xs uppercase tracking-widest text-[var(--color-lab-muted)]">Preço venda</span>
                    <span class="font-mono text-sm font-bold text-black">R$ {{ number_format($produto->preco_com_desconto, 2, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between gap-4 border-b border-[var(--color-lab-border)] pb-3">
                    <span class="font-mono text-xs uppercase tracking-widest text-[var(--color-lab-muted)]">Custo</span>
                    <span class="font-mono text-sm font-bold {{ $produto->custo_compra !== null ? 'text-black' : 'text-gray-400' }}">
                        {{ $produto->custo_compra !== null ? 'R$ ' . number_format($produto->custo_compra, 2, ',', '.') : 'Não cadastrado' }}
                    </span>
                </div>
                <div class="flex items-center justify-between gap-4 border-b border-[var(--color-lab-border)] pb-3">
                    <span class="font-mono text-xs uppercase tracking-widest text-[var(--color-lab-muted)]">Lucro unitário</span>
                    <span class="font-mono text-sm font-bold {{ $produto->lucro_bruto_unitario !== null && $produto->lucro_bruto_unitario < 0 ? 'text-red-600' : 'text-black' }}">
                        {{ $produto->lucro_bruto_unitario !== null ? 'R$ ' . number_format($produto->lucro_bruto_unitario, 2, ',', '.') : '—' }}
                    </span>
                </div>
                <div class="border-b border-[var(--color-lab-border)] pb-3">
                    <div class="flex items-center justify-between gap-4">
                        <span class="font-mono text-xs uppercase tracking-widest text-[var(--color-lab-muted)]">Margem unitária</span>
                        <span class="font-mono text-sm font-bold {{ $produto->margem_bruta_percentual !== null && $produto->margem_bruta_percentual < 20 ? 'text-yellow-700' : 'text-black' }}">
                            {{ $produto->margem_bruta_percentual !== null ? number_format($produto->margem_bruta_percentual, 1, ',', '.') . '%' : '—' }}
                        </span>
                    </div>
                    @if($produto->margem_bruta_percentual !== null)
                    <div class="w-full bg-gray-100 h-1 mt-2">
                        <div class="h-1 {{ $produto->margem_bruta_percentual >= 20 ? 'bg-black' : ($produto->margem_bruta_percentual >= 0 ? 'bg-yellow-500' : 'bg-red-500') }}"
                             style="width: {{ min(100, max(0, (float)$produto->margem_bruta_percentual)) }}%"></div>
                    </div>
                    @endif
                </div>
                <div class="flex items-center justify-between gap-4 border-b border-[var(--color-lab-border)] pb-3">
                    <span class="font-mono text-xs uppercase tracking-widest text-[var(--color-lab-muted)]">Estoque</span>
                    <span class="font-mono text-sm font-bold {{ $produto->ativo && $produto->estoque == 0 ? 'text-yellow-700' : 'text-black' }}">{{ $produto->estoque }}</span>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <span class="font-mono text-xs uppercase tracking-widest text-[var(--color-lab-muted)]">Status</span>
                    <span class="inline-flex items-center border px-2 py-1 font-mono text-[10px] uppercase tracking-widest {{ $produto->ativo ? 'border-black text-black' : 'border-gray-300 text-gray-400' }}">
                        {{ $produto->ativo ? 'Ativo' : 'Inativo' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

</div>

@include('admin.includes.modal-produto')
@endsection
