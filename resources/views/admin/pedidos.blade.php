@extends('includes.header-admin')

@section('title', 'Gerenciar Pedidos')

@section('content')
@php
    use App\Enums\PedidoStatus;

    $costComparison = $cost_comparison ?? ['summary' => [], 'recent' => collect(), 'top_overruns' => collect(), 'top_savings' => collect()];
    $costComparisonSummary = $costComparison['summary'] ?? [];
    $couponFilter = $couponFilter ?? null;
    $cupomOptions = $cupomOptions ?? collect();
@endphp
<div class="space-y-6 lg:space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-end gap-4">
        <div>
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-1">Gerenciamento</p>
            <h1 class="text-2xl sm:text-3xl font-bold text-black tracking-tight">Pedidos</h1>
        </div>
    </div>

    <div class="border border-[var(--color-lab-border)] bg-white">
        <div class="flex border-b border-[var(--color-lab-border)]">
            <button type="button" data-pedidos-tab="lista" class="pedidos-tab-btn px-5 py-3 font-mono text-[10px] uppercase tracking-widest border-b-2 -mb-px transition-colors">Lista</button>
            <button type="button" data-pedidos-tab="custo-real" class="pedidos-tab-btn px-5 py-3 font-mono text-[10px] uppercase tracking-widest border-b-2 -mb-px transition-colors">Custo Real</button>
        </div>
        <div class="px-5 py-3 flex flex-wrap items-center gap-x-5 gap-y-2">
            @php $deltaPedidos = $costComparisonSummary['delta_total'] ?? 0; @endphp
            <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Confirmados <strong class="text-black text-sm font-bold ml-1 normal-case tracking-normal">{{ $costComparisonSummary['items_count'] ?? 0 }}</strong></span>
            <span class="font-mono text-[10px] text-[var(--color-lab-muted)] hidden sm:inline select-none">│</span>
            <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Custo real <strong class="text-black text-sm font-bold ml-1 normal-case tracking-normal">R$&nbsp;{{ number_format($costComparisonSummary['real_total'] ?? 0, 2, ',', '.') }}</strong></span>
            <span class="font-mono text-[10px] text-[var(--color-lab-muted)] hidden sm:inline select-none">│</span>
            <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Diferença <strong class="text-sm font-bold ml-1 normal-case tracking-normal {{ $deltaPedidos > 0 ? 'text-red-600' : ($deltaPedidos < 0 ? 'text-emerald-700' : 'text-black') }}">{{ $deltaPedidos > 0 ? '+ ' : ($deltaPedidos < 0 ? '- ' : '') }}R$&nbsp;{{ number_format(abs($deltaPedidos), 2, ',', '.') }}</strong></span>
        </div>
    </div>

    <div data-pedidos-tab-content="lista" class="space-y-6 lg:space-y-8">
    <!-- Filtros -->
    <div class="border border-[var(--color-lab-border)] bg-white p-4 sm:p-6">
        <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-4">Filtros</p>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_auto] gap-4">
                <select id="filtroStatus" class="flex-1 px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black bg-white">
                    <option value="">Todos os status</option>
                    @foreach(PedidoStatus::adminValues() as $status)
                        <option value="{{ $status }}" @selected(request('status') == $status)>{{ PedidoStatus::label($status) }}</option>
                    @endforeach
                </select>

            <input type="date" id="filtroData" value="{{ request('data') }}" class="flex-1 px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black">

            <div>
                <input type="text"
                       id="filtroCupom"
                       value="{{ $couponFilter }}"
                       list="filtroCupomOptions"
                       placeholder="Cupom"
                       class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono uppercase focus:outline-none focus:border-black bg-white">
                <datalist id="filtroCupomOptions">
                    @foreach($cupomOptions as $cupom)
                        <option value="{{ $cupom }}"></option>
                    @endforeach
                </datalist>
            </div>

            <div class="flex flex-col sm:flex-row gap-2">
                <button onclick="aplicarFiltros()" class="bg-black text-white px-5 py-2.5 text-xs font-bold tracking-widest uppercase hover:bg-gray-800 transition-colors w-full sm:w-auto flex items-center justify-center space-x-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                    <span>Filtrar</span>
                </button>
                @if(request('status') || request('data') || request('cupom'))
                    <a href="{{ route('admin.pedidos') }}"
                       class="inline-flex items-center justify-center border border-[var(--color-lab-border)] bg-white px-4 py-2.5 font-mono text-[10px] uppercase tracking-widest text-black hover:border-black transition-colors">
                        Limpar
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Lista de Pedidos -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4 lg:gap-6">
        @forelse($pedidos as $pedido)
        <div class="border border-[var(--color-lab-border)] bg-white p-4 sm:p-5">
            <div class="flex justify-between items-start mb-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center space-x-2 mb-2">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-black flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-mono text-sm font-bold text-black">#{{ $pedido->id }}</h3>
                            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">{{ $pedido->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                    <div class="space-y-0.5">
                        <div class="font-mono text-sm text-black break-words sm:truncate">{{ $pedido->user?->name ?? $pedido->customer_name ?? 'Guest' }}</div>
                        <div class="font-mono text-[10px] text-[var(--color-lab-muted)] break-all sm:truncate">{{ $pedido->user?->email ?? $pedido->customer_email ?? 'E-mail não informado' }}</div>
                        <div class="font-mono text-[10px] text-[var(--color-lab-muted)] break-words sm:truncate">{{ $pedido->user?->phone ?? $pedido->customer_phone ?? 'Telefone não informado' }}</div>
                    </div>
                </div>
                <div class="flex space-x-1 ml-2 flex-shrink-0">
                    <button onclick="verDetalhes({{ $pedido->id }})" class="text-[var(--color-lab-muted)] hover:text-black p-1.5 border border-transparent hover:border-[var(--color-lab-border)] transition-colors" title="Ver detalhes">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                    <button onclick="alterarStatus({{ $pedido->id }}, '{{ $pedido->status }}')" class="text-[var(--color-lab-muted)] hover:text-black p-1.5 border border-transparent hover:border-[var(--color-lab-border)] transition-colors" title="Alterar status">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                    </button>
                </div>
            </div>

            <div class="space-y-2 border-t border-[var(--color-lab-border)] pt-3">
                <!-- Valor -->
                <div class="flex items-center justify-between gap-3">
                    <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Valor:</span>
                    <span class="font-mono text-sm font-bold text-black">R$ {{ number_format($pedido->valor_total, 2, ',', '.') }}</span>
                </div>

                @if($pedido->cupom_codigo)
                <div class="flex items-center justify-between gap-3">
                    <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Cupom:</span>
                    <span class="font-mono text-[10px] font-bold uppercase tracking-widest text-emerald-700">{{ $pedido->cupom_codigo }}</span>
                </div>
                @endif

                <!-- Status -->
                <div class="flex items-center justify-between gap-3">
                    <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Status:</span>
                    @php $badgeColors = PedidoStatus::badgeClasses(); @endphp
                    <span class="inline-block px-3 py-1 font-mono text-[10px] uppercase tracking-widest border {{ $badgeColors[$pedido->status] ?? 'border-gray-300 text-gray-500' }}">
                        {{ PedidoStatus::label($pedido->status) }}
                    </span>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full border border-[var(--color-lab-border)] bg-white p-12 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="mx-auto text-gray-300 mb-4"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            <h3 class="font-mono text-sm font-bold uppercase tracking-widest text-black mb-2">Nenhum pedido encontrado</h3>
            <p class="font-mono text-xs text-[var(--color-lab-muted)]">Os pedidos aparecerao aqui quando os clientes fizerem compras</p>
        </div>
        @endforelse
    </div>

    <!-- Paginacao -->
    @if($pedidos->hasPages())
    <div class="mt-6">
        {{ $pedidos->links() }}
    </div>
    @endif
    </div>

    <div data-pedidos-tab-content="custo-real" class="hidden">
        <div class="border border-[var(--color-lab-border)] bg-white">
            <div class="px-5 py-4 sm:px-6 border-b border-[var(--color-lab-border)]">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="font-mono text-[10px] uppercase tracking-[0.22em] text-[var(--color-lab-muted)]">Pedidos em separação</p>
                        <h2 class="mt-1 text-lg font-bold tracking-tight text-black">Custo real vs custo do sistema</h2>
                    </div>
                    <p class="font-mono text-[10px] uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Itens cancelados entram como estorno</p>
                </div>
            </div>

            <div class="p-4 sm:p-6 space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
                    <div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] px-4 py-4">
                        <p class="font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Custo real</p>
                        <p class="mt-2 font-mono text-xl font-bold text-black">R$ {{ number_format($costComparisonSummary['real_total'] ?? 0, 2, ',', '.') }}</p>
                    </div>
                    <div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] px-4 py-4">
                        <p class="font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Custo sistema</p>
                        <p class="mt-2 font-mono text-xl font-bold text-black">R$ {{ number_format($costComparisonSummary['catalog_total'] ?? 0, 2, ',', '.') }}</p>
                    </div>
                    <div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] px-4 py-4">
                        @php $deltaPedidosTab = $costComparisonSummary['delta_total'] ?? 0; @endphp
                        <p class="font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Diferença</p>
                        <p class="mt-2 font-mono text-xl font-bold {{ $deltaPedidosTab > 0 ? 'text-red-600' : ($deltaPedidosTab < 0 ? 'text-emerald-700' : 'text-black') }}">
                            {{ $deltaPedidosTab > 0 ? '+ ' : ($deltaPedidosTab < 0 ? '- ' : '') }}R$ {{ number_format(abs($deltaPedidosTab), 2, ',', '.') }}
                        </p>
                    </div>
                    <div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] px-4 py-4">
                        <p class="font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Sem custo sistema</p>
                        <p class="mt-2 font-mono text-xl font-bold text-black">{{ $costComparisonSummary['missing_catalog_count'] ?? 0 }}</p>
                    </div>
                </div>

                <div class="overflow-x-auto border border-[var(--color-lab-border)]">
                    <table class="w-full min-w-[980px] text-sm">
                        <thead>
                            <tr class="border-b border-[var(--color-lab-border)] bg-[var(--color-lab-bg)]">
                                <th class="px-4 py-3 text-left font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Pedido</th>
                                <th class="px-4 py-3 text-left font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Produto</th>
                                <th class="px-4 py-3 text-right font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Real un.</th>
                                <th class="px-4 py-3 text-right font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Sistema un.</th>
                                <th class="px-4 py-3 text-right font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Diferença</th>
                                <th class="px-4 py-3 text-right font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Confirmado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($costComparison['recent'] ?? collect() as $row)
                                <tr class="border-b border-[var(--color-lab-border)] last:border-b-0">
                                    <td class="px-4 py-3">
                                        <a href="{{ $row['pedido_url'] }}" class="font-mono text-xs font-bold text-black hover:underline">#{{ $row['pedido_id'] }}</a>
                                        <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)]">{{ $row['pedido_status_label'] }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <a href="{{ $row['produto_url'] }}" class="font-mono text-xs font-bold text-black hover:underline break-words">{{ $row['produto_nome'] }}</a>
                                        <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)]">{{ $row['variant_label'] ?: $row['status_preparacao_label'] }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-xs text-black">R$ {{ number_format($row['real_unit_cost'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-xs {{ $row['catalog_unit_cost'] === null ? 'text-gray-400' : 'text-black' }}">{{ $row['catalog_unit_cost'] !== null ? 'R$ ' . number_format($row['catalog_unit_cost'], 2, ',', '.') : 'Sem custo' }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-xs font-bold {{ $row['delta_total'] > 0 ? 'text-red-600' : ($row['delta_total'] < 0 ? 'text-emerald-700' : 'text-black') }}">
                                        @if($row['delta_total'] === null)
                                            N/A
                                        @else
                                            {{ $row['delta_total'] > 0 ? '+ ' : ($row['delta_total'] < 0 ? '- ' : '') }}R$ {{ number_format(abs($row['delta_total']), 2, ',', '.') }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-xs text-[var(--color-lab-muted)]">{{ $row['confirmed_at'] ? $row['confirmed_at']->format('d/m/Y H:i') : 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 font-mono text-xs text-[var(--color-lab-muted)]">Nenhum custo real confirmado ainda.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Detalhes do Pedido -->
<div id="modalDetalhes" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden z-50" data-pedido-id="" data-pedido-status="">
    <div class="flex items-center justify-center min-h-screen p-2 sm:p-4" onclick="if(event.target===this)fecharModalDetalhes()">
        <div class="bg-white border border-[var(--color-lab-border)] w-full max-w-6xl max-h-[95vh] sm:max-h-[92vh] overflow-y-auto shadow-[0_24px_80px_rgba(0,0,0,0.16)]">
            <div class="p-6 border-b border-[var(--color-lab-border)]">
                <div class="flex justify-between items-center gap-3">
                    <div>
                        <p id="modalDetalhesSub" class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Detalhes do Pedido</p>
                        <h3 id="modalDetalhesTitle" class="font-mono text-sm font-bold uppercase tracking-widest text-black">Carregando...</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <button id="btnAlterarStatusModal"
                                onclick="alterarStatusDoModal()"
                                class="hidden font-mono text-[10px] uppercase tracking-widest px-3 py-2 border border-black text-black hover:bg-black hover:text-white transition-colors whitespace-nowrap">
                            Alterar Status
                        </button>
                        <button onclick="fecharModalDetalhes()" class="text-[var(--color-lab-muted)] hover:text-black p-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <div id="conteudoDetalhes" class="p-6">
                <!-- Conteudo sera carregado via JavaScript -->
            </div>
        </div>
    </div>
</div>

<!-- Modal de Alterar Status -->
<div id="modalStatus" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-2 sm:p-4">
        <div class="bg-white border border-[var(--color-lab-border)] w-full max-w-md max-h-[92vh] overflow-y-auto">
            <div class="p-6 border-b border-[var(--color-lab-border)]">
                <div class="flex justify-between items-center">
                    <h3 class="font-mono text-sm font-bold uppercase tracking-widest text-black">Alterar Status</h3>
                    <button onclick="fecharModalStatus()" class="text-[var(--color-lab-muted)] hover:text-black p-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
            </div>

            <form id="formStatus" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="p-6">
                    <div>
                        <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Novo Status</label>
                        <select name="status" id="novoStatus" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black bg-white" required>
                            @foreach(PedidoStatus::adminValues() as $status)
                                <option value="{{ $status }}">{{ PedidoStatus::label($status) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="codigoRastreioWrapper" class="mt-4 hidden">
                        <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Código de Rastreio</label>
                        <input type="text" name="codigo_rastreio" id="codigoRastreioStatus" maxlength="50"
                            class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono uppercase focus:outline-none focus:border-black bg-white"
                            placeholder="Ex: BR123456789BR">
                        <p class="mt-2 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Obrigatório para marcar como enviado.</p>
                    </div>

                    <div id="preparacaoCustosWrapper" class="mt-4 hidden">
                        <div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] p-4">
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <div>
                                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Preparação</p>
                                    <p class="mt-1 text-sm font-semibold text-black">Custo real de compra</p>
                                </div>
                                <button type="button" id="btnUsarCustosCatalogo" class="font-mono text-[10px] uppercase tracking-widest px-3 py-2 border border-black text-black hover:bg-black hover:text-white transition-colors whitespace-nowrap">
                                    Usar cadastro
                                </button>
                            </div>

                            <div class="mb-4">
                                <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Imagem da nota fiscal</label>
                                <input type="file" name="nota_fiscal_imagem" accept="image/jpeg,image/png,image/webp"
                                    class="w-full px-3 py-2 border border-[var(--color-lab-border)] text-xs font-mono bg-white focus:outline-none focus:border-black">
                                <p id="notaFiscalAtual" class="mt-2 font-mono text-[10px] text-[var(--color-lab-muted)] hidden"></p>
                            </div>

                            <div id="preparacaoCustosConteudo" class="space-y-3">
                                <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Carregando itens...</p>
                            </div>
                            <p class="mt-3 font-mono text-[10px] text-[var(--color-lab-muted)]">Obrigatório ao marcar como Em preparação. O valor é unitário, item cancelado entra como estorno e os analytics passam a usar este custo declarado.</p>
                        </div>
                    </div>
                </div>

                <div class="p-6 border-t border-[var(--color-lab-border)] flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
                    <button type="button" onclick="fecharModalStatus()" class="w-full sm:w-auto px-5 py-2.5 text-xs font-bold tracking-widest uppercase border border-[var(--color-lab-border)] text-black hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="w-full sm:w-auto bg-black text-white px-5 py-2.5 text-xs font-bold tracking-widest uppercase hover:bg-gray-800 transition-colors">
                        Atualizar Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var buttons = document.querySelectorAll('[data-pedidos-tab]');
    var contents = document.querySelectorAll('[data-pedidos-tab-content]');

    function switchPedidosTab(tabName) {
        contents.forEach(function (content) { content.classList.add('hidden'); });
        buttons.forEach(function (button) {
            button.style.borderBottomColor = 'transparent';
            button.style.color = 'var(--color-lab-muted)';
        });

        var activeContent = document.querySelector('[data-pedidos-tab-content="' + tabName + '"]');
        if (activeContent) activeContent.classList.remove('hidden');

        var activeButton = document.querySelector('[data-pedidos-tab="' + tabName + '"]');
        if (activeButton) {
            activeButton.style.borderBottomColor = 'black';
            activeButton.style.color = 'black';
        }

        localStorage.setItem('pedidos-tab', tabName);
    }

    buttons.forEach(function (button) {
        button.addEventListener('click', function () { switchPedidosTab(button.dataset.pedidosTab); });
    });

    switchPedidosTab(localStorage.getItem('pedidos-tab') || 'lista');
})();
</script>

@endsection
