@extends('includes.header-admin')

@section('title', 'Analytics')

@section('content')
<div class="space-y-4 lg:space-y-6">

    <div class="border border-[var(--color-lab-border)] bg-white overflow-hidden">
        <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)] gap-0">
            <div class="px-5 py-6 sm:px-6 sm:py-7 border-b xl:border-b-0 xl:border-r border-[var(--color-lab-border)] bg-[linear-gradient(135deg,#ffffff_0%,#f8fafc_100%)]">
                <p class="font-mono text-[10px] uppercase tracking-[0.22em] text-[var(--color-lab-muted)] mb-2">Analytics Overview</p>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-bold text-black tracking-tight">Analytics</h1>
                        <p class="mt-2 max-w-2xl text-sm text-[var(--color-lab-muted)]">
                            Centralize margens, lucro unitário, gargalos do catálogo e exportações em uma única visão.
                        </p>
                    </div>
                    <div class="inline-flex w-fit flex-col border border-[var(--color-lab-border)] bg-white px-4 py-3">
                        <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-[var(--color-lab-muted)]">Atualizado em</span>
                        <span class="mt-1 font-mono text-sm font-bold text-black">{{ now()->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>

            <div class="px-5 py-6 sm:px-6 sm:py-7 bg-[var(--color-lab-bg)]">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <p class="font-mono text-[10px] uppercase tracking-[0.22em] text-[var(--color-lab-muted)]">Ações rápidas</p>
                    <span class="font-mono text-[10px] uppercase tracking-[0.18em] text-[var(--color-lab-muted)]">Relatórios</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <a href="{{ route('admin.dashboard.exportar.pdf') }}"
                       class="inline-flex min-h-12 items-center justify-center gap-2 px-4 py-3 bg-black text-white font-mono text-[10px] uppercase tracking-widest hover:bg-gray-800 transition-colors text-center">
                        Exportar PDF
                    </a>
                    <a href="{{ route('admin.dashboard.exportar.csv') }}"
                       class="inline-flex min-h-12 items-center justify-center gap-2 px-4 py-3 border border-[var(--color-lab-border)] bg-white font-mono text-[10px] uppercase tracking-widest text-black hover:border-black transition-colors text-center">
                        Exportar CSV
                    </a>
                    <a href="{{ route('admin.dashboard') }}"
                       class="inline-flex min-h-12 items-center justify-center gap-2 px-4 py-3 border border-[var(--color-lab-border)] bg-white font-mono text-[10px] uppercase tracking-widest text-black hover:border-black transition-colors text-center sm:col-span-2">
                        Voltar ao Dashboard
                    </a>
                </div>

                <div class="mt-5 border border-[var(--color-lab-border)] bg-white p-4">
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <p class="font-mono text-[10px] uppercase tracking-[0.18em] text-[var(--color-lab-muted)]">Buscar analytics de produto</p>
                        <span class="font-mono text-[10px] text-[var(--color-lab-muted)]">nome, marca ou slug</span>
                    </div>
                    <div class="relative">
                        <input
                            type="search"
                            id="analytics-product-search"
                            placeholder="Ex: Wooting 60HE"
                            autocomplete="off"
                            class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black bg-white"
                        >
                        <div id="analytics-product-search-results" class="absolute left-0 right-0 top-full mt-2 hidden border border-[var(--color-lab-border)] bg-white shadow-sm z-20"></div>
                    </div>
                    <p class="mt-2 font-mono text-[10px] text-[var(--color-lab-muted)]">Selecione um produto para abrir a visão detalhada com período e métricas próprias.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="border border-[var(--color-lab-border)] bg-white p-5 sm:p-6">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Receita Total</p>
            <p class="text-lg sm:text-2xl font-bold text-black font-mono">R$&nbsp;{{ number_format($receita_total, 2, ',', '.') }}</p>
            <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-1">vendas confirmadas e em andamento</p>
        </div>
        <div class="border border-[var(--color-lab-border)] bg-white p-5 sm:p-6">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Lucro Líquido</p>
            <p class="text-lg sm:text-2xl font-bold font-mono {{ $lucro_bruto_total >= 0 ? 'text-black' : 'text-red-600' }}">R$&nbsp;{{ number_format($lucro_bruto_total, 2, ',', '.') }}</p>
            <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-1">receita de produtos &minus; custo</p>
        </div>
        <div class="border border-[var(--color-lab-border)] bg-white p-5 sm:p-6">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Margem Bruta</p>
            <p class="text-2xl sm:text-3xl font-bold font-mono @if($margem_bruta_percentual >= 20) text-black @elseif($margem_bruta_percentual >= 0) text-yellow-600 @else text-red-600 @endif">{{ number_format($margem_bruta_percentual, 1, ',', '.') }}%</p>
            <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-1">sobre receita</p>
        </div>
        <div class="border border-[var(--color-lab-border)] bg-white p-5 sm:p-6">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Itens Sem Custo</p>
            <p class="text-2xl sm:text-3xl font-bold text-black font-mono">{{ $itens_sem_custo }}</p>
            <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-1">itens vendidos sem custo base</p>
        </div>
    </div>

    <div data-section="bad-analytics" data-default-open="true" class="border border-[var(--color-lab-border)] bg-white">
        <div data-section-toggle class="flex items-center justify-between gap-3 px-4 sm:px-6 py-4 cursor-pointer hover:bg-gray-50 border-b border-[var(--color-lab-border)] select-none">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">
                <span data-section-arrow>&#9660;</span>&nbsp; Analytics Ruins
            </p>
            <span class="font-mono text-xs text-[var(--color-lab-muted)]">Top gargalos de margem, custo e estoque</span>
        </div>
        <div data-section-content class="p-4 sm:p-6">
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                @php
                    $margemMinima = 20;
                    $badGroups = [
                        ['key' => 'margem_negativa', 'title' => 'Margem negativa', 'items' => $alertas['produtos_margem_negativa']->take(10), 'count' => $alertas['margem_negativa'], 'tone' => 'red', 'button' => 'Editar'],
                        ['key' => 'margem_zero', 'title' => 'Margem zero', 'items' => $alertas['produtos_margem_zero']->take(10), 'count' => $alertas['margem_zero'], 'tone' => 'red', 'button' => 'Editar'],
                        ['key' => 'margem_baixa', 'title' => 'Margem baixa (< 20%)', 'items' => $alertas['produtos_margem_baixa']->sortBy('margem_bruta_percentual')->take(10), 'count' => $alertas['margem_baixa'], 'tone' => 'yellow', 'button' => 'Ajustar preço'],
                        ['key' => 'sem_custo', 'title' => 'Sem custo cadastrado', 'items' => $alertas['produtos_sem_custo']->take(10), 'count' => $alertas['sem_custo'], 'tone' => 'blue', 'button' => 'Editar custo'],
                        ['key' => 'estoque_zerado', 'title' => 'Estoque zerado', 'items' => $alertas['produtos_estoque_zerado']->take(10), 'count' => $alertas['estoque_zerado'], 'tone' => 'yellow', 'button' => 'Estoque'],
                    ];
                @endphp

                @foreach($badGroups as $group)
                <div class="border {{ $group['key'] === 'margem_baixa' ? 'border-yellow-300 bg-gradient-to-br from-yellow-50 via-white to-white' : 'border-[var(--color-lab-border)] bg-white' }}">
                    <div class="flex items-center justify-between gap-3 px-4 py-4 border-b {{ $group['key'] === 'margem_baixa' ? 'border-yellow-200' : 'border-[var(--color-lab-border)]' }}">
                        <div>
                            <p class="font-mono text-[10px] uppercase tracking-widest {{ $group['key'] === 'margem_baixa' ? 'text-yellow-800' : 'text-[var(--color-lab-muted)]' }}">{{ $group['title'] }}</p>
                            @if($group['key'] === 'margem_baixa')
                                <p class="font-mono text-xs text-yellow-800 mt-1">Priorize ajustes de preço nos itens mais distantes da meta de {{ $margemMinima }}%.</p>
                            @else
                                <p class="font-mono text-xs text-[var(--color-lab-muted)] mt-1">{{ $group['count'] }} produto(s) sinalizados</p>
                            @endif
                        </div>
                        <div class="text-right">
                            <span class="font-mono text-xs font-bold {{ $group['tone'] === 'red' ? 'text-red-600' : ($group['tone'] === 'yellow' ? 'text-yellow-700' : 'text-blue-600') }}">{{ $group['count'] }}</span>
                            @if($group['key'] === 'margem_baixa')
                                <p class="font-mono text-[10px] text-yellow-700 mt-1">ordenado pela menor margem</p>
                            @endif
                        </div>
                    </div>
                    <div class="p-4 space-y-2">
                        @forelse($group['items'] as $item)
                        @if($group['key'] === 'margem_baixa')
                        @php
                            $margemAtual = (float) $item->margem_bruta_percentual;
                            $pontosFaltantes = max(0, $margemMinima - $margemAtual);
                        @endphp
                        <div class="border border-yellow-200 bg-white px-3 py-3" data-alert-produto="{{ $item->id }}">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-mono text-xs font-bold text-black break-words">{{ $item->nome }}</p>
                                        <span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-yellow-100 text-yellow-800 font-bold">
                                            {{ number_format($margemAtual, 1, ',', '.') }}% margem
                                        </span>
                                    </div>
                                    <div class="mt-3 space-y-2">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2">
                                            <div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] px-3 py-2">
                                                <p class="font-mono text-[9px] uppercase tracking-widest text-[var(--color-lab-muted)]">Lucro unit.</p>
                                                <p class="mt-1 font-mono text-xs text-black">R$ {{ number_format($item->lucro_bruto_unitario, 2, ',', '.') }}</p>
                                            </div>
                                            <div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] px-3 py-2">
                                                <p class="font-mono text-[9px] uppercase tracking-widest text-[var(--color-lab-muted)]">Venda</p>
                                                <p class="mt-1 font-mono text-xs text-black">R$ {{ number_format($item->preco_com_desconto, 2, ',', '.') }}</p>
                                            </div>
                                        </div>
                                        <div class="border border-yellow-200 bg-yellow-50 px-3 py-2">
                                            <p class="font-mono text-[9px] uppercase tracking-widest text-yellow-700">Meta de margem</p>
                                            <p class="mt-1 font-mono text-[10px] text-yellow-900">Faltam {{ number_format($pontosFaltantes, 1, ',', '.') }} p.p. para {{ $margemMinima }}%</p>
                                        </div>
                                    </div>
                                </div>
                                <button onclick="editarProduto({{ $item->id }})"
                                        class="shrink-0 font-mono text-[10px] uppercase tracking-widest px-3 py-2 border border-yellow-400 bg-yellow-50 text-yellow-900 hover:border-black hover:bg-white transition-colors">
                                    {{ $group['button'] }}
                                </button>
                            </div>
                        </div>
                        @else
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border border-[var(--color-lab-border)] px-3 py-3" data-alert-produto="{{ $item->id }}">
                            <div class="min-w-0">
                                <p class="font-mono text-xs text-black break-words">{{ $item->nome }}</p>
                                <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-1">
                                    @if($item->custo_compra === null)
                                        Venda R$ {{ number_format($item->preco_com_desconto, 2, ',', '.') }}
                                    @elseif($item->margem_bruta_percentual !== null)
                                        {{ number_format($item->margem_bruta_percentual, 1, ',', '.') }}% margem &middot; R$ {{ number_format($item->lucro_bruto_unitario, 2, ',', '.') }}
                                    @else
                                        Estoque {{ $item->estoque }}
                                    @endif
                                </p>
                            </div>
                            <button onclick="abrirQuickFix({{ $item->id }}, '{{ addslashes($item->nome) }}', '{{ $item->custo_compra ?? '' }}', {{ $item->estoque }})"
                                    class="shrink-0 font-mono text-[10px] uppercase tracking-widest px-3 py-2 border border-[var(--color-lab-border)] text-black hover:border-black transition-colors">
                                {{ $group['button'] }}
                            </button>
                        </div>
                        @endif
                        @empty
                        <p class="font-mono text-xs {{ $group['key'] === 'margem_baixa' ? 'text-yellow-800' : 'text-[var(--color-lab-muted)]' }}">
                            {{ $group['key'] === 'margem_baixa' ? 'Nenhum produto abaixo da meta de margem no momento.' : 'Nenhum produto neste grupo.' }}
                        </p>
                        @endforelse
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div data-section="performance" data-default-open="true" class="border border-[var(--color-lab-border)] bg-white">
        <div data-section-toggle class="flex items-center justify-between gap-3 px-4 sm:px-6 py-4 cursor-pointer hover:bg-gray-50 border-b border-[var(--color-lab-border)] select-none">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">
                <span data-section-arrow>&#9660;</span>&nbsp; Performance
            </p>
        </div>
        <div data-section-content class="p-4 sm:p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div>
                <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-4">Top 5 Mais Vendidos</p>
                @forelse($top_produtos as $i => $item)
                <div class="flex items-start gap-3 mb-3">
                    <span class="font-mono text-xs text-[var(--color-lab-muted)] w-4 shrink-0">{{ $i + 1 }}</span>
                    <div class="flex-1 min-w-0">
                        <p class="font-mono text-xs text-black truncate">{{ $item->produto?->nome ?? '—' }}</p>
                        <p class="font-mono text-[10px] text-[var(--color-lab-muted)]">{{ $item->total_vendido }} un. &middot; R$&nbsp;{{ number_format($item->receita_gerada, 2, ',', '.') }}</p>
                    </div>
                </div>
                @empty
                <p class="font-mono text-xs text-[var(--color-lab-muted)]">Nenhuma venda entregue ainda.</p>
                @endforelse
            </div>

            <div>
                <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-4">Receita por Categoria</p>
                @php $maxReceita = $receita_categoria->max('receita') ?: 1; @endphp
                @forelse($receita_categoria as $cat)
                <div class="mb-3">
                    <div class="flex justify-between mb-1">
                        <span class="font-mono text-xs text-black">{{ $cat->nome }}</span>
                        <span class="font-mono text-xs text-[var(--color-lab-muted)]">R$&nbsp;{{ number_format($cat->receita, 0, ',', '.') }}</span>
                    </div>
                    <div class="h-1 bg-gray-100">
                        <div class="h-1 bg-black" style="width: {{ round(($cat->receita / $maxReceita) * 100) }}%"></div>
                    </div>
                </div>
                @empty
                <p class="font-mono text-xs text-[var(--color-lab-muted)]">Nenhuma venda entregue ainda.</p>
                @endforelse
            </div>

            <div>
                <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-4">Métricas Gerais</p>
                <div class="space-y-4">
                    <div>
                        <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mb-1">Ticket Médio</p>
                        <p class="font-mono text-xl font-bold text-black">R$&nbsp;{{ number_format($ticket_medio, 2, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mb-1">Unidades Vendidas</p>
                        <p class="font-mono text-xl font-bold text-black">{{ $total_unidades }}</p>
                    </div>
                    <div>
                        <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mb-1">Produtos Ativos</p>
                        <p class="font-mono text-xl font-bold text-black">{{ $total_ativos }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div data-section="rankings" data-default-open="true" class="border border-[var(--color-lab-border)] bg-white">
        <div data-section-toggle class="flex items-center justify-between gap-3 px-4 sm:px-6 py-4 cursor-pointer hover:bg-gray-50 border-b border-[var(--color-lab-border)] select-none">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">
                <span data-section-arrow>&#9660;</span>&nbsp; Produtos com Mais Margem e Exclusividade
            </p>
            <span class="font-mono text-xs text-[var(--color-lab-muted)]">Melhores e piores recortes do catálogo</span>
        </div>
        <div data-section-content class="p-4 sm:p-6">
            @php
                $topLucroInsights = collect($profit_exclusivity_insights['top_lucro_unitario'] ?? []);
                $topMargemInsights = collect($profit_exclusivity_insights['top_margem_percentual'] ?? []);
                $topWorstMarginInsights = collect($profit_exclusivity_insights['top_menor_margem'] ?? []);
                $topExclusiveInsights = collect($profit_exclusivity_insights['top_exclusivos_lucrativos'] ?? []);
                $maxLucroInsight = max((float) ($topLucroInsights->max('lucro_bruto_unitario') ?? 0), 1);
                $maxExclusiveLucroInsight = max((float) ($topExclusiveInsights->max('lucro_bruto_unitario') ?? 0), 1);
            @endphp

            <div class="grid grid-cols-1 xl:grid-cols-2 2xl:grid-cols-4 gap-4">
                @foreach([
                    ['title' => 'Top 10 por lucro unitário', 'items' => $topLucroInsights, 'max' => $maxLucroInsight, 'metric' => 'lucro'],
                    ['title' => 'Top 10 por margem', 'items' => $topMargemInsights, 'max' => 100, 'metric' => 'margem'],
                    ['title' => 'Top 10 menores margens', 'items' => $topWorstMarginInsights, 'max' => 100, 'metric' => 'margem_ruim'],
                    ['title' => 'Exclusivos / premium', 'items' => $topExclusiveInsights, 'max' => $maxExclusiveLucroInsight, 'metric' => 'exclusive'],
                ] as $card)
                <div class="border border-[var(--color-lab-border)] bg-white">
                    <div class="px-4 py-4 border-b border-[var(--color-lab-border)]">
                        <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">{{ $card['title'] }}</p>
                    </div>
                    <div class="p-4 space-y-4">
                        @forelse($card['items'] as $index => $item)
                        @php
                            $barWidth = $card['metric'] === 'margem_ruim'
                                ? min(100, round(max(0, 100 - $item['margem_bruta_percentual'])))
                                : ($card['metric'] === 'margem'
                                    ? min(100, round($item['margem_bruta_percentual']))
                                    : round((($card['metric'] === 'exclusive' ? $item['lucro_bruto_unitario'] : $item['lucro_bruto_unitario']) / max($card['max'], 1)) * 100));
                        @endphp
                        <div class="border-b border-dashed border-[var(--color-lab-border)] pb-4 last:border-b-0 last:pb-0">
                            <div class="flex items-start gap-3">
                                <span class="font-mono text-xs text-[var(--color-lab-muted)] w-5 shrink-0">{{ $index + 1 }}</span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="font-mono text-xs font-bold text-black break-words">{{ $item['nome'] }}</p>
                                            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mt-1">{{ $item['categoria'] ?? 'Sem categoria' }} &middot; {{ $item['marca'] ?: 'Sem marca' }}</p>
                                        </div>
                                        @if($item['estoque'] <= 5)
                                        <span class="inline-block px-2 py-0.5 font-mono text-[10px] border border-yellow-300 bg-yellow-50 text-yellow-700 shrink-0">Estoque baixo</span>
                                        @endif
                                    </div>
                                    <div class="mt-3 h-1.5 bg-gray-100">
                                        <div class="h-1.5 {{ $card['metric'] === 'margem_ruim' ? 'bg-red-600' : 'bg-black' }}" style="width: {{ max(4, min(100, $barWidth)) }}%"></div>
                                    </div>
                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        @if(in_array($card['metric'], ['lucro', 'exclusive'], true))
                                        <span class="font-mono text-xs font-bold text-black">R$ {{ number_format($item['lucro_bruto_unitario'], 2, ',', '.') }}</span>
                                        <span class="font-mono text-[10px] text-[var(--color-lab-muted)]">{{ number_format($item['margem_bruta_percentual'], 1, ',', '.') }}% margem</span>
                                        @else
                                        <span class="font-mono text-xs font-bold {{ $card['metric'] === 'margem_ruim' && $item['margem_bruta_percentual'] < 20 ? 'text-red-600' : 'text-black' }}">{{ number_format($item['margem_bruta_percentual'], 1, ',', '.') }}%</span>
                                        <span class="font-mono text-[10px] text-[var(--color-lab-muted)]">R$ {{ number_format($item['lucro_bruto_unitario'], 2, ',', '.') }} por unidade</span>
                                        @endif
                                        @if(!empty($item['exclusive_reason']))
                                        <span class="inline-block px-2 py-0.5 font-mono text-[10px] border border-black text-black">{{ $item['exclusive_reason'] }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <p class="font-mono text-xs text-[var(--color-lab-muted)]">Nenhum produto elegível neste ranking.</p>
                        @endforelse
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div data-section="analytics" data-default-open="true" class="border border-[var(--color-lab-border)] bg-white">
        <div data-section-toggle class="flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:justify-between px-4 sm:px-6 py-4 cursor-pointer hover:bg-gray-50 border-b border-[var(--color-lab-border)] select-none">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">
                <span data-section-arrow>&#9660;</span>&nbsp; Analytics de Produtos
            </p>
            <span class="font-mono text-xs text-[var(--color-lab-muted)]">{{ $produtos_analytics->count() }} produtos &middot; clique nos cabeçalhos para ordenar</span>
        </div>
        <div data-section-content>
            <div class="md:hidden p-4 space-y-3">
                @foreach($produtos_analytics as $p)
                @php $margem = $p->margem_bruta_percentual; $lucro = $p->lucro_bruto_unitario; @endphp
                <div class="border border-[var(--color-lab-border)] p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-mono text-sm font-bold text-black break-words">{{ $p->nome }}</p>
                            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mt-1">{{ $p->marca ?: 'Sem marca' }}</p>
                        </div>
                        <button onclick="abrirQuickFix({{ $p->id }}, '{{ addslashes($p->nome) }}', '{{ $p->custo_compra ?? '' }}', {{ $p->estoque }})"
                                class="shrink-0 font-mono text-[10px] px-2 py-1 border border-[var(--color-lab-border)] text-[var(--color-lab-muted)] hover:border-black hover:text-black transition-colors"
                                title="Editar custo e estoque">
                            &#9998;
                        </button>
                    </div>
                    <div class="grid grid-cols-2 gap-3 mt-4">
                        <div>
                            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Venda</p>
                            <p class="font-mono text-xs text-black mt-1">R$ {{ number_format($p->preco_com_desconto, 2, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Custo</p>
                            <p class="font-mono text-xs mt-1 {{ $p->custo_compra ? 'text-black' : 'text-gray-300' }}">
                                {!! $p->custo_compra ? 'R$ ' . number_format($p->custo_compra, 2, ',', '.') : '&mdash;' !!}
                            </p>
                        </div>
                        <div>
                            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Lucro unit.</p>
                            <p class="font-mono text-xs mt-1 {{ $lucro === null ? 'text-gray-300' : ($lucro >= 0 ? 'text-black' : 'text-red-600') }}">
                                {!! $lucro !== null ? 'R$ ' . number_format($lucro, 2, ',', '.') : '&mdash;' !!}
                            </p>
                        </div>
                        <div>
                            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Estoque</p>
                            <p class="font-mono text-xs mt-1 {{ $p->estoque == 0 && $p->ativo ? 'text-yellow-600 font-bold' : 'text-black' }}">{{ $p->estoque }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 mt-4">
                        @if($margem === null)
                            <span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-blue-50 text-blue-500 border border-blue-200">Sem custo</span>
                        @elseif($margem < 0)
                            <span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-red-100 text-red-700 font-bold">{{ number_format($margem, 1, ',', '.') }}%</span>
                        @elseif($margem < 20)
                            <span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-yellow-100 text-yellow-700 font-bold">{{ number_format($margem, 1, ',', '.') }}%</span>
                        @else
                            <span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-green-100 text-green-700 font-bold">{{ number_format($margem, 1, ',', '.') }}%</span>
                        @endif
                        <span class="inline-block px-2 py-0.5 font-mono text-[10px] border {{ $p->ativo ? 'border-black text-black' : 'border-gray-300 text-gray-400' }}">
                            {{ $p->ativo ? 'Ativo' : 'Inativo' }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="hidden md:block overflow-x-auto admin-mobile-scroll">
                <table id="tabela-analytics" class="w-full min-w-[920px] text-sm">
                    <thead>
                        <tr class="border-b border-[var(--color-lab-border)]">
                            <th data-col="nome" class="analytics-th text-left px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Produto <span class="sort-arrow ml-1"></span></th>
                            <th data-col="marca" class="analytics-th text-left px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Marca <span class="sort-arrow ml-1"></span></th>
                            <th data-col="preco" class="analytics-th text-right px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Preço Venda <span class="sort-arrow ml-1"></span></th>
                            <th data-col="custo" class="analytics-th text-right px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Custo <span class="sort-arrow ml-1"></span></th>
                            <th data-col="lucro" class="analytics-th text-right px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Lucro Unit. <span class="sort-arrow ml-1"></span></th>
                            <th data-col="margem" class="analytics-th text-right px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Margem % <span class="sort-arrow ml-1"></span></th>
                            <th data-col="estoque" class="analytics-th text-right px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Estoque <span class="sort-arrow ml-1"></span></th>
                            <th data-col="status" class="analytics-th text-center px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Status <span class="sort-arrow ml-1"></span></th>
                            <th class="px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] whitespace-nowrap">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($produtos_analytics as $p)
                        @php $margem = $p->margem_bruta_percentual; $lucro = $p->lucro_bruto_unitario; @endphp
                        <tr class="border-b border-[var(--color-lab-border)] hover:bg-gray-50 transition-colors"
                            data-id="{{ $p->id }}"
                            data-nome="{{ strtolower($p->nome) }}"
                            data-marca="{{ strtolower($p->marca ?? '') }}"
                            data-preco="{{ $p->preco_com_desconto }}"
                            data-custo="{{ $p->custo_compra ?? '' }}"
                            data-lucro="{{ $lucro ?? '' }}"
                            data-margem="{{ $margem ?? '' }}"
                            data-estoque="{{ $p->estoque }}"
                            data-status="{{ $p->ativo ? '1' : '0' }}">
                            <td class="px-4 py-3 font-mono text-xs text-black">{{ $p->nome }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-[var(--color-lab-muted)]">{!! $p->marca ?? '&mdash;' !!}</td>
                            <td class="px-4 py-3 font-mono text-xs text-black text-right">R$&nbsp;{{ number_format($p->preco_com_desconto, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-right {{ $p->custo_compra ? 'text-black' : 'text-gray-300' }}" data-cell="custo">
                                {!! $p->custo_compra ? 'R$ ' . number_format($p->custo_compra, 2, ',', '.') : '&mdash;' !!}
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-right {{ $lucro === null ? 'text-gray-300' : ($lucro >= 0 ? 'text-black' : 'text-red-600') }}" data-cell="lucro">
                                {!! $lucro !== null ? 'R$ ' . number_format($lucro, 2, ',', '.') : '&mdash;' !!}
                            </td>
                            <td class="px-4 py-3 text-right" data-cell="margem">
                                @if($margem === null)
                                    <span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-blue-50 text-blue-500 border border-blue-200">Sem custo</span>
                                @elseif($margem < 0)
                                    <span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-red-100 text-red-700 font-bold">{{ number_format($margem, 1, ',', '.') }}%</span>
                                @elseif($margem < 20)
                                    <span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-yellow-100 text-yellow-700 font-bold">{{ number_format($margem, 1, ',', '.') }}%</span>
                                @else
                                    <span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-green-100 text-green-700 font-bold">{{ number_format($margem, 1, ',', '.') }}%</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-right {{ $p->estoque == 0 && $p->ativo ? 'text-yellow-600 font-bold' : 'text-black' }}" data-cell="estoque">{{ $p->estoque }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-block px-2 py-0.5 font-mono text-[10px] border {{ $p->ativo ? 'border-black text-black' : 'border-gray-300 text-gray-400' }}">
                                    {{ $p->ativo ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button onclick="abrirQuickFix({{ $p->id }}, '{{ addslashes($p->nome) }}', '{{ $p->custo_compra ?? '' }}', {{ $p->estoque }})"
                                        class="font-mono text-[10px] px-2 py-1 border border-[var(--color-lab-border)] text-[var(--color-lab-muted)] hover:border-black hover:text-black transition-colors"
                                        title="Editar custo e estoque">
                                    &#9998;
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

@include('admin.includes.modal-produto')

<div id="modal-quickfix" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden z-50" onclick="fecharQuickFix()">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white border border-[var(--color-lab-border)] w-full max-w-sm max-h-[92vh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="p-4 border-b border-[var(--color-lab-border)] flex justify-between items-center">
                <div class="min-w-0">
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Edição Rápida</p>
                    <p id="qf-nome" class="font-mono text-sm font-bold text-black mt-0.5 break-words"></p>
                </div>
                <button onclick="fecharQuickFix()" class="text-[var(--color-lab-muted)] hover:text-black p-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            <form id="form-quickfix" class="p-4 space-y-4">
                @csrf
                <div>
                    <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-1">Custo de Compra (R$)</label>
                    <input id="qf-custo" type="text" placeholder="ex: 599,90" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono text-sm focus:outline-none focus:border-black">
                </div>
                <div>
                    <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-1">Estoque</label>
                    <input id="qf-estoque" type="number" min="0" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono text-sm focus:outline-none focus:border-black">
                </div>
                <div class="flex flex-col sm:flex-row gap-2 pt-2">
                    <button type="submit" id="qf-btn-salvar" class="flex-1 bg-black text-white font-mono text-[10px] uppercase tracking-widest py-2 hover:bg-gray-800 transition-colors">Salvar</button>
                    <button type="button" onclick="fecharQuickFix()" class="px-4 py-2 border border-[var(--color-lab-border)] font-mono text-[10px] uppercase tracking-widest text-black hover:border-black transition-colors">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var CSRF = document.querySelector('meta[name="csrf-token"]').content;
    var analyticsSearchInput = document.getElementById('analytics-product-search');
    var analyticsSearchResults = document.getElementById('analytics-product-search-results');
    var analyticsSearchUrl = @json(route('admin.analytics.products.search'));
    var searchRequestId = 0;

    function initCollapsibles() {
        document.querySelectorAll('[data-section]').forEach(function(section) {
            var id = section.dataset.section;
            var content = section.querySelector('[data-section-content]');
            var arrow = section.querySelector('[data-section-arrow]');
            var toggle = section.querySelector('[data-section-toggle]');
            if (!content || !toggle) return;

            var saved = localStorage.getItem('dash-section-' + id);
            var defOpen = section.dataset.defaultOpen !== 'false';
            var isOpen = saved === null ? defOpen : (saved === 'open');

            if (!isOpen) { content.classList.add('hidden'); if (arrow) arrow.textContent = '\u25B6'; }
            else { content.classList.remove('hidden'); if (arrow) arrow.textContent = '\u25BC'; }

            toggle.addEventListener('click', function() {
                var open = !content.classList.contains('hidden');
                content.classList.toggle('hidden', open);
                if (arrow) arrow.textContent = open ? '\u25B6' : '\u25BC';
                localStorage.setItem('dash-section-' + id, open ? 'closed' : 'open');
            });
        });
    }

    function hideAnalyticsSearchResults() {
        if (!analyticsSearchResults) return;
        analyticsSearchResults.innerHTML = '';
        analyticsSearchResults.classList.add('hidden');
    }

    function renderAnalyticsSearchResults(products) {
        if (!analyticsSearchResults) return;

        analyticsSearchResults.innerHTML = '';

        if (!products.length) {
            var emptyState = document.createElement('div');
            emptyState.className = 'px-4 py-3 font-mono text-xs text-[var(--color-lab-muted)]';
            emptyState.textContent = 'Nenhum produto encontrado.';
            analyticsSearchResults.appendChild(emptyState);
            analyticsSearchResults.classList.remove('hidden');
            return;
        }

        products.forEach(function(product) {
            var link = document.createElement('a');
            link.href = product.url;
            link.className = 'block border-b border-[var(--color-lab-border)] last:border-b-0 px-4 py-3 hover:bg-gray-50 transition-colors';

            var name = document.createElement('p');
            name.className = 'font-mono text-xs font-bold text-black break-words';
            name.textContent = product.name;

            var meta = document.createElement('p');
            meta.className = 'mt-1 font-mono text-[10px] text-[var(--color-lab-muted)] break-words';
            meta.textContent = [product.brand || 'Sem marca', product.slug, product.active ? 'Ativo' : 'Inativo']
                .filter(Boolean)
                .join(' · ');

            link.appendChild(name);
            link.appendChild(meta);
            analyticsSearchResults.appendChild(link);
        });

        analyticsSearchResults.classList.remove('hidden');
    }

    if (analyticsSearchInput && analyticsSearchResults) {
        var searchTimer = null;

        analyticsSearchInput.addEventListener('input', function() {
            var term = analyticsSearchInput.value.trim();
            clearTimeout(searchTimer);

            if (term.length < 2) {
                hideAnalyticsSearchResults();
                return;
            }

            searchTimer = setTimeout(async function() {
                var currentRequestId = ++searchRequestId;

                try {
                    var url = new URL(analyticsSearchUrl, window.location.origin);
                    url.searchParams.set('q', term);
                    var response = await fetch(url.toString(), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    var payload = await response.json();

                    if (currentRequestId !== searchRequestId) return;
                    renderAnalyticsSearchResults(payload.products || []);
                } catch (error) {
                    if (currentRequestId !== searchRequestId) return;
                    hideAnalyticsSearchResults();
                }
            }, 180);
        });

        analyticsSearchInput.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                hideAnalyticsSearchResults();
            }
        });

        document.addEventListener('click', function(event) {
            if (!analyticsSearchResults.contains(event.target) && event.target !== analyticsSearchInput) {
                hideAnalyticsSearchResults();
            }
        });
    }

    var quickFixId = null;
    window.abrirQuickFix = function(id, nome, custo, estoque) {
        quickFixId = id;
        document.getElementById('qf-nome').textContent = nome;
        document.getElementById('qf-custo').value = custo ? String(custo).replace('.', ',') : '';
        document.getElementById('qf-estoque').value = estoque;
        document.getElementById('qf-btn-salvar').disabled = false;
        document.getElementById('qf-btn-salvar').textContent = 'Salvar';
        document.getElementById('modal-quickfix').classList.remove('hidden');
    };

    window.fecharQuickFix = function() {
        document.getElementById('modal-quickfix').classList.add('hidden');
        quickFixId = null;
    };

    var formQuickFix = document.getElementById('form-quickfix');
    if (formQuickFix) {
        formQuickFix.addEventListener('submit', async function(e) {
            e.preventDefault();
            if (!quickFixId) return;

            var btn = document.getElementById('qf-btn-salvar');
            btn.disabled = true;
            btn.textContent = 'Salvando...';

            var custo = document.getElementById('qf-custo').value.trim();
            var estoque = document.getElementById('qf-estoque').value.trim();

            try {
                var url = (window.routes.adminProdutosQuickEdit || '').replace(':id', quickFixId);
                var res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ custo_compra: custo, estoque: estoque }),
                });
                var data = await res.json();
                if (!data.success) throw new Error('Falha ao salvar');

                var row = document.querySelector('#tabela-analytics tr[data-id="' + quickFixId + '"]');
                if (row) {
                    row.dataset.custo = data.custo_compra != null ? data.custo_compra : '';
                    row.dataset.margem = data.margem != null ? data.margem : '';
                    row.dataset.lucro = data.lucro != null ? data.lucro : '';
                    row.dataset.estoque = data.estoque;

                    var cellCusto = row.querySelector('[data-cell="custo"]');
                    if (cellCusto) cellCusto.textContent = data.custo_compra ? 'R$ ' + fmtMoney(data.custo_compra) : '\u2014';

                    var cellLucro = row.querySelector('[data-cell="lucro"]');
                    if (cellLucro) cellLucro.textContent = data.lucro !== null ? 'R$ ' + fmtMoney(data.lucro) : '\u2014';

                    var cellMargem = row.querySelector('[data-cell="margem"]');
                    if (cellMargem) atualizarBadgeMargem(cellMargem, data.margem);

                    var cellEstoque = row.querySelector('[data-cell="estoque"]');
                    if (cellEstoque) cellEstoque.textContent = data.estoque;
                }

                document.querySelectorAll('[data-alert-produto="' + quickFixId + '"]').forEach(function(el) { el.remove(); });
                fecharQuickFix();
                mostrarToast('Salvo com sucesso');
            } catch (err) {
                alert('Erro ao salvar. Tente novamente.');
                btn.disabled = false;
                btn.textContent = 'Salvar';
            }
        });
    }

    function fmtMoney(val) {
        return parseFloat(val).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function atualizarBadgeMargem(cell, margem) {
        if (margem === null || margem === undefined || margem === '') {
            cell.innerHTML = '<span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-blue-50 text-blue-500 border border-blue-200">Sem custo</span>';
        } else {
            var m = parseFloat(margem);
            var fmt = m.toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%';
            if (m < 0) cell.innerHTML = '<span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-red-100 text-red-700 font-bold">' + fmt + '</span>';
            else if (m < 20) cell.innerHTML = '<span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-yellow-100 text-yellow-700 font-bold">' + fmt + '</span>';
            else cell.innerHTML = '<span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-green-100 text-green-700 font-bold">' + fmt + '</span>';
        }
    }

    function mostrarToast(msg) {
        var t = document.createElement('div');
        t.className = 'fixed bottom-4 right-4 bg-black text-white font-mono text-xs px-4 py-2 z-[100]';
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(function() { t.style.transition = 'opacity 0.3s'; t.style.opacity = '0'; setTimeout(function() { t.remove(); }, 300); }, 2000);
    }

    var tableEl = document.getElementById('tabela-analytics');
    var numCols = ['preco','custo','lucro','margem','estoque','status'];
    var currentCol = 'margem', currentDir = 'asc';

    function sortTable(col, dir) {
        if (!tableEl) return;
        var tbody = tableEl.querySelector('tbody');
        var rows = Array.from(tbody.querySelectorAll('tr'));
        rows.sort(function(a, b) {
            var av = a.dataset[col] !== undefined ? a.dataset[col] : '';
            var bv = b.dataset[col] !== undefined ? b.dataset[col] : '';
            if (numCols.indexOf(col) !== -1) {
                var an = av === '' ? (dir === 'asc' ? Infinity : -Infinity) : parseFloat(av);
                var bn = bv === '' ? (dir === 'asc' ? Infinity : -Infinity) : parseFloat(bv);
                return dir === 'asc' ? an - bn : bn - an;
            }
            if (av === '' && bv !== '') return 1;
            if (bv === '' && av !== '') return -1;
            return dir === 'asc' ? av.localeCompare(bv) : bv.localeCompare(av);
        });
        rows.forEach(function(r) { tbody.appendChild(r); });
    }

    function updateArrows(activeCol, dir) {
        if (!tableEl) return;
        tableEl.querySelectorAll('th.analytics-th').forEach(function(th) {
            var arrowEl = th.querySelector('.sort-arrow');
            if (!arrowEl) return;
            if (th.dataset.col === activeCol) {
                arrowEl.textContent = dir === 'asc' ? '\u2191' : '\u2193';
                th.classList.add('text-black');
                th.classList.remove('text-[var(--color-lab-muted)]');
            } else {
                arrowEl.textContent = '';
                th.classList.remove('text-black');
                th.classList.add('text-[var(--color-lab-muted)]');
            }
        });
    }

    if (tableEl) {
        tableEl.querySelectorAll('th.analytics-th[data-col]').forEach(function(th) {
            th.addEventListener('click', function() {
                var col = th.dataset.col;
                var dir = (col === currentCol && currentDir === 'asc') ? 'desc' : 'asc';
                currentCol = col;
                currentDir = dir;
                sortTable(col, dir);
                updateArrows(col, dir);
            });
        });
        sortTable('margem', 'asc');
        updateArrows('margem', 'asc');
    }

    initCollapsibles();
})();
</script>
@endsection
