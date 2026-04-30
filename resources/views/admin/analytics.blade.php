@extends('includes.header-admin')

@section('title', 'Analytics')

@section('content')
<div class="space-y-4 lg:space-y-6">

    <div class="border border-[var(--color-lab-border)] bg-white">
        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-[var(--color-lab-border)]">
            <div>
                <p class="font-mono text-[10px] uppercase tracking-[0.22em] text-[var(--color-lab-muted)] mb-1">Analytics Overview</p>
                <h1 class="text-xl font-bold text-black tracking-tight">Analytics</h1>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex flex-col border border-[var(--color-lab-border)] px-3 py-2">
                    <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-[var(--color-lab-muted)]">Atualizado</span>
                    <span class="font-mono text-xs font-bold text-black">{{ now()->format('d/m/Y H:i') }}</span>
                </div>
                <a href="{{ route('admin.dashboard.exportar.pdf') }}"
                   class="px-4 py-2 bg-black text-white font-mono text-[10px] uppercase tracking-widest hover:bg-gray-800 transition-colors">PDF</a>
                <a href="{{ route('admin.dashboard.exportar.csv') }}"
                   class="px-4 py-2 border border-[var(--color-lab-border)] font-mono text-[10px] uppercase tracking-widest text-black hover:border-black transition-colors">CSV</a>
                <a href="{{ route('admin.dashboard') }}"
                   class="px-4 py-2 border border-[var(--color-lab-border)] font-mono text-[10px] uppercase tracking-widest text-black hover:border-black transition-colors">Dashboard</a>
            </div>
        </div>
        <div class="px-5 py-3 relative">
            <input
                type="search"
                id="analytics-product-search"
                placeholder="Buscar produto por analytics — nome, marca ou slug"
                autocomplete="off"
                class="w-full border border-[var(--color-lab-border)] px-4 py-2.5 text-sm font-mono focus:outline-none focus:border-black bg-white"
            >
            <div id="analytics-product-search-results" class="absolute left-5 right-5 top-full hidden border border-[var(--color-lab-border)] bg-white shadow-sm z-20"></div>
        </div>
    </div>

    {{-- KPI Strip + Tabs + Content --}}
    <div class="border border-[var(--color-lab-border)] bg-white">

        {{-- KPI Strip --}}
        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 px-5 py-3 border-b border-[var(--color-lab-border)]">
            <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Receita <strong class="text-black text-sm font-bold ml-1 normal-case tracking-normal">R$&nbsp;{{ number_format($receita_total, 2, ',', '.') }}</strong></span>
            <span class="font-mono text-[10px] text-[var(--color-lab-muted)] hidden sm:inline select-none">│</span>
            <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Lucro <strong class="text-sm font-bold ml-1 normal-case tracking-normal {{ $lucro_bruto_total >= 0 ? 'text-black' : 'text-red-600' }}">R$&nbsp;{{ number_format($lucro_bruto_total, 2, ',', '.') }}</strong></span>
            <span class="font-mono text-[10px] text-[var(--color-lab-muted)] hidden sm:inline select-none">│</span>
            <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Margem <strong class="text-sm font-bold ml-1 normal-case tracking-normal @if($margem_bruta_percentual >= 20) text-black @elseif($margem_bruta_percentual >= 0) text-yellow-600 @else text-red-600 @endif">{{ number_format($margem_bruta_percentual, 1, ',', '.') }}%</strong></span>
            <span class="font-mono text-[10px] text-[var(--color-lab-muted)] hidden sm:inline select-none">│</span>
            <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Sem custo <strong class="text-black text-sm font-bold ml-1 normal-case tracking-normal">{{ $itens_sem_custo }}</strong></span>
        </div>

        {{-- Tab Bar --}}
        <div class="flex border-b border-[var(--color-lab-border)]">
            <button data-tab="alertas" class="tab-btn px-5 py-3 font-mono text-[10px] uppercase tracking-widest border-b-2 -mb-px transition-colors">Alertas</button>
            <button data-tab="rankings" class="tab-btn px-5 py-3 font-mono text-[10px] uppercase tracking-widest border-b-2 -mb-px transition-colors">Rankings</button>
            <button data-tab="tabela" class="tab-btn px-5 py-3 font-mono text-[10px] uppercase tracking-widest border-b-2 -mb-px transition-colors">Tabela</button>
            <button data-tab="visao-geral" class="tab-btn px-5 py-3 font-mono text-[10px] uppercase tracking-widest border-b-2 -mb-px transition-colors">Visão Geral</button>
        </div>

        {{-- Tab: Alertas --}}
        <div data-tab-content="alertas" class="p-4 sm:p-6 hidden">
            @php
                $margemMinima = 20;
                $badGroups = [
                    ['key' => 'margem_negativa', 'title' => 'Margem negativa', 'items' => $alertas['produtos_margem_negativa']->take(10), 'count' => $alertas['margem_negativa'], 'dot' => 'bg-red-500', 'button' => 'Editar', 'fn' => 'quickfix'],
                    ['key' => 'margem_zero',     'title' => 'Margem zero',     'items' => $alertas['produtos_margem_zero']->take(10),     'count' => $alertas['margem_zero'],     'dot' => 'bg-red-300', 'button' => 'Editar', 'fn' => 'quickfix'],
                    ['key' => 'margem_baixa',    'title' => 'Margem baixa (< 20%)', 'items' => $alertas['produtos_margem_baixa']->sortBy('margem_bruta_percentual')->take(10), 'count' => $alertas['margem_baixa'], 'dot' => 'bg-yellow-400', 'button' => 'Ajustar preço', 'fn' => 'editar'],
                    ['key' => 'sem_custo',       'title' => 'Sem custo cadastrado', 'items' => $alertas['produtos_sem_custo']->take(10),       'count' => $alertas['sem_custo'],       'dot' => 'bg-gray-400', 'button' => 'Editar custo', 'fn' => 'quickfix'],
                    ['key' => 'estoque_zerado',  'title' => 'Estoque zerado',  'items' => $alertas['produtos_estoque_zerado']->take(10),  'count' => $alertas['estoque_zerado'],  'dot' => 'bg-yellow-500', 'button' => 'Estoque', 'fn' => 'quickfix'],
                ];
                $totalAlertas = array_sum(array_column($badGroups, 'count'));
            @endphp

            @if($totalAlertas === 0)
            <p class="font-mono text-xs text-[var(--color-lab-muted)]">Nenhum alerta no momento. Catálogo saudável.</p>
            @else
            <div class="space-y-8">
                @foreach($badGroups as $group)
                @if($group['count'] > 0)
                <div>
                    <div class="flex items-center gap-2 mb-3 pb-2 border-b border-[var(--color-lab-border)]">
                        <span class="w-2 h-2 rounded-full {{ $group['dot'] }} shrink-0"></span>
                        <span class="font-mono text-[10px] uppercase tracking-widest text-black">{{ $group['title'] }}</span>
                        <span class="font-mono text-[10px] text-[var(--color-lab-muted)]">({{ $group['count'] }})</span>
                    </div>
                    <div>
                        @foreach($group['items'] as $item)
                        @php
                            if ($group['key'] === 'sem_custo') {
                                $infoText = 'Venda R$ ' . number_format($item->preco_com_desconto, 2, ',', '.');
                            } elseif ($group['key'] === 'estoque_zerado') {
                                $infoText = 'Estoque ' . $item->estoque;
                            } elseif ($group['key'] === 'margem_baixa') {
                                $margemAtual = (float) $item->margem_bruta_percentual;
                                $pontosFaltantes = max(0, $margemMinima - $margemAtual);
                                $infoText = number_format($margemAtual, 1, ',', '.') . '% margem · faltam ' . number_format($pontosFaltantes, 1, ',', '.') . 'p.p.';
                            } else {
                                $infoText = number_format($item->margem_bruta_percentual, 1, ',', '.') . '% · R$ ' . number_format($item->lucro_bruto_unitario, 2, ',', '.');
                            }
                        @endphp
                        <div class="flex items-center justify-between gap-4 py-2.5 border-b border-[var(--color-lab-border)] last:border-b-0" data-alert-produto="{{ $item->id }}">
                            <div class="min-w-0 flex-1">
                                <span class="font-mono text-xs text-black">{{ $item->nome }}</span>
                                <span class="font-mono text-[10px] text-[var(--color-lab-muted)] ml-3">{{ $infoText }}</span>
                            </div>
                            @if($group['fn'] === 'editar')
                            <button onclick="editarProduto({{ $item->id }})"
                                    class="shrink-0 font-mono text-[10px] uppercase tracking-widest px-3 py-1.5 border border-[var(--color-lab-border)] text-black hover:border-black transition-colors">
                                {{ $group['button'] }}
                            </button>
                            @else
                            <button onclick="abrirQuickFix({{ $item->id }}, '{{ addslashes($item->nome) }}', '{{ $item->custo_compra ?? '' }}', {{ $item->estoque }})"
                                    class="shrink-0 font-mono text-[10px] uppercase tracking-widest px-3 py-1.5 border border-[var(--color-lab-border)] text-black hover:border-black transition-colors">
                                {{ $group['button'] }}
                            </button>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
                @endforeach
            </div>
            @endif
        </div>

        {{-- Tab: Rankings --}}
        <div data-tab-content="rankings" class="p-4 sm:p-6 hidden">
            @php
                $topLucroInsights    = collect($profit_exclusivity_insights['top_lucro_unitario'] ?? []);
                $topMargemInsights   = collect($profit_exclusivity_insights['top_margem_percentual'] ?? []);
                $topWorstInsights    = collect($profit_exclusivity_insights['top_menor_margem'] ?? []);
                $topExclusiveInsights = collect($profit_exclusivity_insights['top_exclusivos_lucrativos'] ?? []);
                $maxLucro    = max((float) ($topLucroInsights->max('lucro_bruto_unitario') ?? 0), 1);
                $maxExclusivo = max((float) ($topExclusiveInsights->max('lucro_bruto_unitario') ?? 0), 1);
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8">
                @foreach([
                    ['title' => 'Top lucro unitário',   'items' => $topLucroInsights,    'max' => $maxLucro,    'metric' => 'lucro'],
                    ['title' => 'Top margem %',          'items' => $topMargemInsights,   'max' => 100,          'metric' => 'margem'],
                    ['title' => 'Piores margens',        'items' => $topWorstInsights,    'max' => 100,          'metric' => 'margem_ruim'],
                    ['title' => 'Exclusivos / premium',  'items' => $topExclusiveInsights,'max' => $maxExclusivo,'metric' => 'exclusive'],
                ] as $card)
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-4 pb-2 border-b border-[var(--color-lab-border)]">{{ $card['title'] }}</p>
                    <ol class="space-y-3">
                        @forelse($card['items'] as $index => $item)
                        @php
                            $barWidth = $card['metric'] === 'margem_ruim'
                                ? min(100, round(max(0, 100 - $item['margem_bruta_percentual'])))
                                : ($card['metric'] === 'margem'
                                    ? min(100, round($item['margem_bruta_percentual']))
                                    : round(($item['lucro_bruto_unitario'] / max($card['max'], 1)) * 100));
                        @endphp
                        <li class="flex items-start gap-3">
                            <span class="font-mono text-[10px] text-[var(--color-lab-muted)] w-4 shrink-0 pt-0.5">{{ $index + 1 }}</span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-baseline justify-between gap-2">
                                    <p class="font-mono text-xs text-black truncate">{{ $item['nome'] }}</p>
                                    <span class="font-mono text-xs font-bold shrink-0 {{ ($card['metric'] === 'margem_ruim' && $item['margem_bruta_percentual'] < 0) ? 'text-red-600' : 'text-black' }}">
                                        @if(in_array($card['metric'], ['lucro', 'exclusive'], true))
                                            R$ {{ number_format($item['lucro_bruto_unitario'], 2, ',', '.') }}
                                        @else
                                            {{ number_format($item['margem_bruta_percentual'], 1, ',', '.') }}%
                                        @endif
                                    </span>
                                </div>
                                <div class="mt-1.5 h-0.5 bg-gray-100">
                                    <div class="h-0.5 {{ $card['metric'] === 'margem_ruim' ? 'bg-red-500' : 'bg-black' }}" style="width: {{ max(2, min(100, $barWidth)) }}%"></div>
                                </div>
                            </div>
                        </li>
                        @empty
                        <li class="font-mono text-xs text-[var(--color-lab-muted)]">Nenhum produto elegível.</li>
                        @endforelse
                    </ol>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Tab: Tabela --}}
        <div data-tab-content="tabela" class="hidden">
            <div class="px-4 sm:px-6 py-3 border-b border-[var(--color-lab-border)]">
                <span class="font-mono text-[10px] text-[var(--color-lab-muted)]">{{ $produtos_analytics->count() }} produtos &middot; clique nos cabeçalhos para ordenar</span>
            </div>
            <div class="overflow-x-auto">
                <table id="tabela-analytics" class="w-full min-w-[920px] text-sm">
                    <thead>
                        <tr class="border-b border-[var(--color-lab-border)]">
                            <th data-col="nome"    class="analytics-th text-left  px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Produto <span class="sort-arrow ml-1"></span></th>
                            <th data-col="marca"   class="analytics-th text-left  px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Marca <span class="sort-arrow ml-1"></span></th>
                            <th data-col="preco"   class="analytics-th text-right px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Preço Venda <span class="sort-arrow ml-1"></span></th>
                            <th data-col="custo"   class="analytics-th text-right px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Custo <span class="sort-arrow ml-1"></span></th>
                            <th data-col="lucro"   class="analytics-th text-right px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Lucro Unit. <span class="sort-arrow ml-1"></span></th>
                            <th data-col="margem"  class="analytics-th text-right px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Margem % <span class="sort-arrow ml-1"></span></th>
                            <th data-col="estoque" class="analytics-th text-right px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Estoque <span class="sort-arrow ml-1"></span></th>
                            <th data-col="status"  class="analytics-th text-center px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Status <span class="sort-arrow ml-1"></span></th>
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
                            <td class="px-4 py-3 font-mono text-xs text-right {{ $p->custo_efetivo ? 'text-black' : 'text-gray-300' }}" data-cell="custo">
                                {!! $p->custo_efetivo ? 'R$ ' . number_format($p->custo_efetivo, 2, ',', '.') : '&mdash;' !!}
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-right {{ $lucro === null ? 'text-gray-300' : ($lucro >= 0 ? 'text-black' : 'text-red-600') }}" data-cell="lucro">
                                {!! $lucro !== null ? 'R$ ' . number_format($lucro, 2, ',', '.') : '&mdash;' !!}
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-right" data-cell="margem">
                                @if($margem === null)
                                    <span class="text-gray-300">&mdash;</span>
                                @elseif($margem < 0)
                                    <span class="text-red-600 font-bold">{{ number_format($margem, 1, ',', '.') }}%</span>
                                @elseif($margem < 20)
                                    <span class="text-yellow-600">{{ number_format($margem, 1, ',', '.') }}%</span>
                                @else
                                    <span class="text-black">{{ number_format($margem, 1, ',', '.') }}%</span>
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

        {{-- Tab: Visão Geral --}}
        <div data-tab-content="visao-geral" class="p-4 sm:p-6 hidden">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-4 pb-2 border-b border-[var(--color-lab-border)]">Top 5 Mais Vendidos</p>
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
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-4 pb-2 border-b border-[var(--color-lab-border)]">Receita por Categoria</p>
                    @php $maxReceita = $receita_categoria->max('receita') ?: 1; @endphp
                    @forelse($receita_categoria as $cat)
                    <div class="mb-3">
                        <div class="flex justify-between mb-1">
                            <span class="font-mono text-xs text-black">{{ $cat->nome }}</span>
                            <span class="font-mono text-xs text-[var(--color-lab-muted)]">R$&nbsp;{{ number_format($cat->receita, 0, ',', '.') }}</span>
                        </div>
                        <div class="h-0.5 bg-gray-100">
                            <div class="h-0.5 bg-black" style="width: {{ round(($cat->receita / $maxReceita) * 100) }}%"></div>
                        </div>
                    </div>
                    @empty
                    <p class="font-mono text-xs text-[var(--color-lab-muted)]">Nenhuma venda entregue ainda.</p>
                    @endforelse
                </div>

                <div>
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-4 pb-2 border-b border-[var(--color-lab-border)]">Métricas Gerais</p>
                    <div class="flex flex-wrap gap-x-8 gap-y-4">
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

    // Tab switching
    var tabBtns = document.querySelectorAll('[data-tab]');
    var tabContents = document.querySelectorAll('[data-tab-content]');

    function switchTab(tabName) {
        tabContents.forEach(function(el) { el.classList.add('hidden'); });
        tabBtns.forEach(function(btn) {
            btn.style.borderBottomColor = 'transparent';
            btn.style.color = 'var(--color-lab-muted)';
        });
        var activeContent = document.querySelector('[data-tab-content="' + tabName + '"]');
        if (activeContent) activeContent.classList.remove('hidden');
        var activeBtn = document.querySelector('[data-tab="' + tabName + '"]');
        if (activeBtn) {
            activeBtn.style.borderBottomColor = 'black';
            activeBtn.style.color = 'black';
        }
        localStorage.setItem('analytics-tab', tabName);
    }

    tabBtns.forEach(function(btn) {
        btn.addEventListener('click', function() { switchTab(btn.dataset.tab); });
    });

    switchTab(localStorage.getItem('analytics-tab') || 'alertas');

    var focusedResultIdx = -1;

    function hideAnalyticsSearchResults() {
        if (!analyticsSearchResults) return;
        analyticsSearchResults.innerHTML = '';
        analyticsSearchResults.classList.add('hidden');
        focusedResultIdx = -1;
    }

    function highlightTerm(text, term) {
        var frag = document.createDocumentFragment();
        if (!term) { frag.appendChild(document.createTextNode(text)); return frag; }
        var lower = text.toLowerCase();
        var idx = lower.indexOf(term.toLowerCase());
        if (idx === -1) { frag.appendChild(document.createTextNode(text)); return frag; }
        frag.appendChild(document.createTextNode(text.slice(0, idx)));
        var mark = document.createElement('strong');
        mark.textContent = text.slice(idx, idx + term.length);
        frag.appendChild(mark);
        frag.appendChild(document.createTextNode(text.slice(idx + term.length)));
        return frag;
    }

    function renderAnalyticsSearchResults(products, term) {
        if (!analyticsSearchResults) return;
        analyticsSearchResults.innerHTML = '';
        focusedResultIdx = -1;
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
            link.className = 'flex items-center justify-between gap-3 border-b border-[var(--color-lab-border)] last:border-b-0 px-4 py-2.5 hover:bg-gray-50 focus:bg-gray-50 focus:outline-none transition-colors';

            var left = document.createElement('div');
            left.className = 'min-w-0';

            var name = document.createElement('p');
            name.className = 'font-mono text-xs text-black';
            name.appendChild(highlightTerm(product.name, term));
            left.appendChild(name);

            if (product.brand) {
                var brand = document.createElement('p');
                brand.className = 'font-mono text-[10px] text-[var(--color-lab-muted)] mt-0.5';
                brand.appendChild(highlightTerm(product.brand, term));
                left.appendChild(brand);
            }

            var badge = document.createElement('span');
            badge.className = 'shrink-0 font-mono text-[10px] px-1.5 py-0.5 border ' + (product.active ? 'border-black text-black' : 'border-gray-300 text-gray-400');
            badge.textContent = product.active ? 'Ativo' : 'Inativo';

            link.appendChild(left);
            link.appendChild(badge);
            analyticsSearchResults.appendChild(link);
        });
        analyticsSearchResults.classList.remove('hidden');
    }

    if (analyticsSearchInput && analyticsSearchResults) {
        var searchTimer = null;

        analyticsSearchInput.addEventListener('input', function() {
            var term = analyticsSearchInput.value.trim();
            clearTimeout(searchTimer);
            if (term.length < 2) { hideAnalyticsSearchResults(); return; }
            searchTimer = setTimeout(async function() {
                var currentRequestId = ++searchRequestId;
                try {
                    var url = new URL(analyticsSearchUrl, window.location.origin);
                    url.searchParams.set('q', term);
                    var response = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    var payload = await response.json();
                    if (currentRequestId !== searchRequestId) return;
                    renderAnalyticsSearchResults(payload.products || [], term);
                } catch (error) {
                    if (currentRequestId !== searchRequestId) return;
                    hideAnalyticsSearchResults();
                }
            }, 180);
        });

        analyticsSearchInput.addEventListener('keydown', function(event) {
            var links = Array.from(analyticsSearchResults.querySelectorAll('a'));
            if (event.key === 'Escape') {
                hideAnalyticsSearchResults();
            } else if (event.key === 'ArrowDown' && links.length) {
                event.preventDefault();
                focusedResultIdx = Math.min(focusedResultIdx + 1, links.length - 1);
                links[focusedResultIdx].focus();
            }
        });

        analyticsSearchResults.addEventListener('keydown', function(event) {
            var links = Array.from(analyticsSearchResults.querySelectorAll('a'));
            if (event.key === 'Escape') {
                hideAnalyticsSearchResults();
                analyticsSearchInput.focus();
            } else if (event.key === 'ArrowDown') {
                event.preventDefault();
                focusedResultIdx = Math.min(focusedResultIdx + 1, links.length - 1);
                links[focusedResultIdx]?.focus();
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                focusedResultIdx--;
                if (focusedResultIdx < 0) { focusedResultIdx = -1; analyticsSearchInput.focus(); }
                else links[focusedResultIdx]?.focus();
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
                    if (cellCusto) cellCusto.textContent = data.custo_efetivo ? 'R$ ' + fmtMoney(data.custo_efetivo) : '—';
                    var cellLucro = row.querySelector('[data-cell="lucro"]');
                    if (cellLucro) cellLucro.textContent = data.lucro !== null ? 'R$ ' + fmtMoney(data.lucro) : '—';
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
            cell.innerHTML = '<span class="text-gray-300">—</span>';
        } else {
            var m = parseFloat(margem);
            var fmt = m.toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%';
            if (m < 0)       cell.innerHTML = '<span class="font-mono text-xs text-red-600 font-bold">' + fmt + '</span>';
            else if (m < 20) cell.innerHTML = '<span class="font-mono text-xs text-yellow-600">' + fmt + '</span>';
            else             cell.innerHTML = '<span class="font-mono text-xs text-black">' + fmt + '</span>';
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
                arrowEl.textContent = dir === 'asc' ? '↑' : '↓';
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
})();
</script>
@endsection
