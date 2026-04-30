@extends('includes.header-admin')

@section('title', 'Dashboard')

@section('content')
@php use App\Enums\PedidoStatus; @endphp
<div class="space-y-4 lg:space-y-6">

    {{-- Header --}}
    <div class="border border-[var(--color-lab-border)] bg-white overflow-hidden">
        <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)] gap-0">
            <div class="px-5 py-6 sm:px-6 sm:py-7 border-b xl:border-b-0 xl:border-r border-[var(--color-lab-border)] bg-[linear-gradient(135deg,#ffffff_0%,#f8fafc_100%)]">
                <p class="font-mono text-[10px] uppercase tracking-[0.22em] text-[var(--color-lab-muted)] mb-2">Admin Overview</p>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-bold text-black tracking-tight">Dashboard</h1>
                        <p class="mt-2 max-w-2xl text-sm text-[var(--color-lab-muted)]">
                            Monitore vendas, operação e fulfillment. Os analytics detalhados agora ficam em uma tela própria.
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
                    <span class="font-mono text-[10px] uppercase tracking-[0.18em] text-[var(--color-lab-muted)]">Mobile ready</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-2 gap-2.5">
                    <button onclick="abrirModalProduto()"
                            class="inline-flex min-h-12 items-center justify-center gap-2 px-4 py-3 bg-black text-white font-mono text-[10px] uppercase tracking-widest hover:bg-gray-800 transition-colors text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                        Novo Produto
                    </button>

                    <a href="{{ route('admin.analytics') }}"
                       class="inline-flex min-h-12 items-center justify-center gap-2 px-4 py-3 border border-[var(--color-lab-border)] bg-white font-mono text-[10px] uppercase tracking-widest text-black hover:border-black transition-colors text-center">
                        Analytics
                    </a>

                    <a href="{{ route('admin.pedidos') }}"
                       class="inline-flex min-h-12 items-center justify-center gap-2 px-4 py-3 border font-mono text-[10px] uppercase tracking-widest hover:border-black transition-colors text-center {{ $pedidos_nao_finalizados > 0 ? 'border-yellow-400 bg-yellow-50 text-yellow-700' : 'border-[var(--color-lab-border)] bg-white text-black' }}">
                        Não finalizados: {{ $pedidos_nao_finalizados }}
                    </a>

                    <a href="{{ route('admin.pedidos') }}"
                       class="inline-flex min-h-12 items-center justify-center gap-2 px-4 py-3 border font-mono text-[10px] uppercase tracking-widest hover:border-black transition-colors text-center {{ $pedidos_pagos > 0 ? 'border-green-300 bg-green-50 text-green-700' : 'border-[var(--color-lab-border)] bg-white text-black' }}">
                        Pagos: {{ $pedidos_pagos }}
                    </a>

                    <a href="{{ route('admin.pedidos') }}"
                       class="inline-flex min-h-12 items-center justify-center gap-2 px-4 py-3 border border-[var(--color-lab-border)] bg-white font-mono text-[10px] uppercase tracking-widest text-black hover:border-black transition-colors text-center">
                        Processando: {{ $pedidos_processando }}
                    </a>

                    <a href="{{ route('admin.pedidos') }}"
                       class="inline-flex min-h-12 items-center justify-center gap-2 px-4 py-3 border border-[var(--color-lab-border)] bg-white font-mono text-[10px] uppercase tracking-widest text-black hover:border-black transition-colors text-center sm:col-span-2">
                        Ver Pedidos
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI CARDS --}}
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
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Pedidos Pagos</p>
            <p class="text-2xl sm:text-3xl font-bold text-black font-mono">{{ $pedidos_pagos }}</p>
            <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-1">confirmados no checkout</p>
        </div>
    </div>

    {{-- SECTION: STATUS + AÇÕES --}}
    <div data-section="status" data-default-open="false" class="border border-[var(--color-lab-border)] bg-white">
        <div data-section-toggle class="flex items-center justify-between gap-3 px-4 sm:px-6 py-4 cursor-pointer hover:bg-gray-50 border-b border-[var(--color-lab-border)] select-none">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">
                <span data-section-arrow>&#9660;</span>&nbsp; Status e Ações
            </p>
        </div>
        <div data-section-content class="p-4 sm:p-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-4">Status dos Pedidos</p>
                    <div class="space-y-3">
                        @foreach($pedidos_por_status as $s)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 {{ $s['color'] }} shrink-0"></div>
                                <span class="font-mono text-sm text-black">{{ $s['label'] }}</span>
                            </div>
                            <span class="font-mono text-sm font-bold text-black">{{ $s['count'] }}</span>
                        </div>
                        @endforeach
                        <div class="pt-3 border-t border-[var(--color-lab-border)]">
                            <div class="flex items-center justify-between">
                                <span class="font-mono text-xs text-[var(--color-lab-muted)]">Total pedidos</span>
                                <span class="font-mono text-xs font-bold text-black">{{ $total_pedidos }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-4">Resumo Operacional</p>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-sm text-black">Pedidos pagos</span>
                            <span class="font-mono text-sm font-bold text-black">{{ $pedidos_pagos }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-sm text-black">Não finalizados</span>
                            <span class="font-mono text-sm font-bold text-black">{{ $pedidos_nao_finalizados }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-sm text-black">Itens sem custo</span>
                            <span class="font-mono text-sm font-bold {{ $itens_sem_custo > 0 ? 'text-yellow-600' : 'text-black' }}">{{ $itens_sem_custo }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-sm text-black">Receita total</span>
                            <span class="font-mono text-sm font-bold text-black">R$ {{ number_format($receita_total, 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <div>
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-4">Ações Rápidas</p>
                    <div class="space-y-2">
                        <a href="{{ route('admin.analytics') }}" class="flex items-center gap-3 px-4 py-3 border border-[var(--color-lab-border)] hover:border-black hover:bg-gray-50 transition-colors">
                            <span class="font-mono text-xs uppercase tracking-widest text-black">Abrir Analytics</span>
                        </a>
                        <a href="{{ route('admin.produtos') }}" class="flex items-center gap-3 px-4 py-3 border border-[var(--color-lab-border)] hover:border-black hover:bg-gray-50 transition-colors">
                            <span class="font-mono text-xs uppercase tracking-widest text-black">Gerenciar Produtos</span>
                        </a>
                        <a href="{{ route('admin.pedidos') }}" class="flex items-center gap-3 px-4 py-3 border border-[var(--color-lab-border)] hover:border-black hover:bg-gray-50 transition-colors">
                            <span class="font-mono text-xs uppercase tracking-widest text-black">Ver Todos os Pedidos</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SECTION: PIPELINE DE FULFILLMENT --}}
    {{-- SLA ALERTS --}}
    @if($sla_pago_sem_processar > 0 || $sla_processando_sem_enviar > 0 || $sla_enviado_sem_entregar > 0)
    <div class="border border-yellow-400 bg-yellow-50 px-4 sm:px-6 py-4 space-y-1">
        <p class="font-mono text-[10px] uppercase tracking-widest text-yellow-700 font-bold mb-2">Alertas de Prazo</p>
        @if($sla_pago_sem_processar > 0)
        <p class="font-mono text-xs text-yellow-800">&#9888; {{ $sla_pago_sem_processar }} {{ $sla_pago_sem_processar === 1 ? 'pedido pago há mais de 24h sem processar' : 'pedidos pagos há mais de 24h sem processar' }}</p>
        @endif
        @if($sla_processando_sem_enviar > 0)
        <p class="font-mono text-xs text-yellow-800">&#9888; {{ $sla_processando_sem_enviar }} {{ $sla_processando_sem_enviar === 1 ? 'pedido em processamento há mais de 3 dias sem enviar' : 'pedidos em processamento há mais de 3 dias sem enviar' }}</p>
        @endif
        @if($sla_enviado_sem_entregar > 0)
        <p class="font-mono text-xs text-yellow-800">&#9888; {{ $sla_enviado_sem_entregar }} {{ $sla_enviado_sem_entregar === 1 ? 'pedido enviado há mais de 15 dias sem confirmação de entrega' : 'pedidos enviados há mais de 15 dias sem confirmação de entrega' }}</p>
        @endif
    </div>
    @endif

    {{-- PIPELINE VISUAL --}}
    <div class="border border-[var(--color-lab-border)] bg-white">
        <div class="flex items-center justify-between gap-3 px-4 sm:px-6 py-4 border-b border-[var(--color-lab-border)]">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Pipeline de Fulfillment</p>
        </div>
        <div class="grid grid-cols-4 divide-x divide-[var(--color-lab-border)]">
            <a href="{{ route('admin.pedidos') }}?status=pago" class="flex flex-col items-center py-4 px-2 hover:bg-gray-50 transition-colors text-center">
                <span class="font-mono text-2xl font-bold text-green-700">{{ $pedidos_pagos }}</span>
                <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mt-1">Pago</span>
                @if($pedidos_pagos > 0)
                <span class="mt-2 font-mono text-[9px] uppercase tracking-widest text-green-700 border border-green-400 px-1.5 py-0.5">&#9889; ação</span>
                @else
                <span class="mt-2 font-mono text-[9px] uppercase tracking-widest text-gray-400">&#10003; ok</span>
                @endif
            </a>
            <a href="{{ route('admin.pedidos') }}?status=processando" class="flex flex-col items-center py-4 px-2 hover:bg-gray-50 transition-colors text-center">
                <span class="font-mono text-2xl font-bold text-blue-700">{{ $pedidos_processando }}</span>
                <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mt-1">Processando</span>
                @if($pedidos_processando > 0)
                <span class="mt-2 font-mono text-[9px] uppercase tracking-widest text-blue-700 border border-blue-400 px-1.5 py-0.5">&#9889; ação</span>
                @else
                <span class="mt-2 font-mono text-[9px] uppercase tracking-widest text-gray-400">&#10003; ok</span>
                @endif
            </a>
            <a href="{{ route('admin.pedidos') }}?status=enviado" class="flex flex-col items-center py-4 px-2 hover:bg-gray-50 transition-colors text-center">
                <span class="font-mono text-2xl font-bold text-purple-700">{{ $pedidos_enviados }}</span>
                <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mt-1">Enviado</span>
                <span class="mt-2 font-mono text-[9px] uppercase tracking-widest text-gray-400">aguardando</span>
            </a>
            <a href="{{ route('admin.pedidos') }}?status=entregue" class="flex flex-col items-center py-4 px-2 hover:bg-gray-50 transition-colors text-center">
                <span class="font-mono text-2xl font-bold text-black">{{ $pedidos_entregues }}</span>
                <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mt-1">Entregue</span>
                <span class="mt-2 font-mono text-[9px] uppercase tracking-widest text-gray-400">&#10003; concluído</span>
            </a>
        </div>
    </div>

    {{-- ACTION LIST: pago + processando orders --}}
    @if($pedidos_acao->isNotEmpty())
    <div data-section="pedidos-acao" data-default-open="true" class="border border-[var(--color-lab-border)] bg-white">
        <div data-section-toggle class="flex items-center justify-between gap-3 px-4 sm:px-6 py-4 cursor-pointer hover:bg-gray-50 border-b border-[var(--color-lab-border)] select-none">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">
                <span data-section-arrow>&#9660;</span>&nbsp; Pedidos que Precisam de A&ccedil;&atilde;o
                <span class="ml-2 px-2 py-0.5 bg-yellow-100 text-yellow-700 text-[10px] font-bold">{{ $pedidos_acao->count() }}</span>
            </p>
        </div>
        <div data-section-content id="lista-pedidos-acao">
            @foreach($pedidos_acao as $pedido)
            <div id="pedido-acao-{{ $pedido->id }}" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between px-4 sm:px-6 py-4 border-b border-[var(--color-lab-border)] last:border-0">
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 sm:gap-3 mb-0.5">
                        <span class="font-mono text-sm font-bold text-black">#{{ $pedido->id }}</span>
                        <span class="font-mono text-[10px] text-[var(--color-lab-muted)]">{{ $pedido->created_at->format('d/m H:i') }}</span>
                        @php $badgeColors = PedidoStatus::badgeClasses(); @endphp
                        <span data-status-badge class="inline-block px-2 py-0.5 font-mono text-[10px] border {{ $badgeColors[$pedido->status] ?? 'border-gray-400 text-gray-600' }}">
                            {{ PedidoStatus::label($pedido->status) }}
                        </span>
                    </div>
                    <div class="font-mono text-sm text-black">{{ $pedido->user?->name ?? $pedido->customer_name ?? 'Guest' }}</div>
                    <div class="font-mono text-xs text-[var(--color-lab-muted)]">R$&nbsp;{{ number_format($pedido->valor_total, 2, ',', '.') }}</div>
                </div>
                <div class="sm:mt-0">
                    <div class="flex flex-col sm:flex-row gap-2">
                        <button onclick="verDetalhes({{ $pedido->id }})"
                                class="w-full sm:w-auto font-mono text-[10px] uppercase tracking-widest px-3 py-2 border border-[var(--color-lab-border)] text-black hover:border-black hover:bg-gray-50 transition-colors">
                            Ver detalhes
                        </button>
                        @if($pedido->status === PedidoStatus::PAGO)
                        <button onclick="avancarStatusPedido(this, {{ $pedido->id }}, '{{ PedidoStatus::PROCESSANDO }}')"
                                class="w-full sm:w-auto font-mono text-[10px] uppercase tracking-widest px-3 py-2 border border-black text-black hover:bg-black hover:text-white transition-colors">
                            &rarr; {{ PedidoStatus::label(PedidoStatus::PROCESSANDO) }}
                        </button>
                        @elseif($pedido->status === PedidoStatus::PROCESSANDO)
                        <button onclick="avancarStatusPedido(this, {{ $pedido->id }}, '{{ PedidoStatus::ENVIADO }}')"
                                class="w-full sm:w-auto font-mono text-[10px] uppercase tracking-widest px-3 py-2 border border-black text-black hover:bg-black hover:text-white transition-colors">
                            &rarr; {{ PedidoStatus::label(PedidoStatus::ENVIADO) }}
                        </button>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- SECTION: PEDIDOS RECENTES --}}
    <div data-section="pedidos-recentes" data-default-open="false" class="border border-[var(--color-lab-border)] bg-white">
        <div data-section-toggle class="flex items-center justify-between gap-3 px-4 sm:px-6 py-4 cursor-pointer hover:bg-gray-50 select-none">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">
                <span data-section-arrow>&#9654;</span>&nbsp; Pedidos Recentes
            </p>
        </div>
        <div data-section-content class="hidden p-4 sm:p-6">
            @forelse($pedidos_recentes as $pedido)
            <div class="border border-[var(--color-lab-border)] p-4 mb-3 last:mb-0">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 sm:gap-3 mb-1">
                            <span class="font-mono text-sm font-bold text-black">#{{ $pedido->id }}</span>
                            <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">{{ $pedido->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="font-mono text-sm text-black break-words sm:truncate">{{ $pedido->user?->name ?? $pedido->customer_name ?? 'Guest' }}</div>
                        <div class="font-mono text-xs text-[var(--color-lab-muted)] break-all sm:truncate">{{ $pedido->user?->email ?? $pedido->customer_email ?? 'E-mail não informado' }}</div>
                    </div>
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 mt-2 sm:mt-0">
                    <div class="font-mono text-sm font-bold text-black text-right">R$&nbsp;{{ number_format($pedido->valor_total, 2, ',', '.') }}</div>
                    @php $badgeColors = PedidoStatus::badgeClasses(); @endphp
                    <span class="inline-block px-3 py-1 font-mono text-[10px] uppercase tracking-widest border {{ $badgeColors[$pedido->status] ?? 'border-gray-300 text-gray-500' }}">
                        {{ PedidoStatus::label($pedido->status) }}
                    </span>
                    <button onclick="verDetalhes({{ $pedido->id }})"
                            class="font-mono text-[10px] uppercase tracking-widest px-3 py-2 border border-[var(--color-lab-border)] text-black hover:border-black hover:bg-gray-50 transition-colors">
                        Ver detalhes
                    </button>
                </div>
            </div>
            </div>
            @empty
            <p class="font-mono text-xs uppercase tracking-widest text-[var(--color-lab-muted)] text-center py-8">Nenhum pedido</p>
            @endforelse
        </div>
    </div>

</div>

<div id="modalDetalhes" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-2 sm:p-4">
        <div class="bg-white border border-[var(--color-lab-border)] w-full max-w-6xl max-h-[95vh] sm:max-h-[92vh] overflow-y-auto shadow-[0_24px_80px_rgba(0,0,0,0.16)]">
            <div class="p-6 border-b border-[var(--color-lab-border)]">
                <div class="flex justify-between items-center">
                    <h3 class="font-mono text-sm font-bold uppercase tracking-widest text-black">Detalhes do Pedido</h3>
                    <button onclick="fecharModalDetalhes()" class="text-[var(--color-lab-muted)] hover:text-black p-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
            </div>

            <div id="conteudoDetalhes" class="p-6">
                <!-- Conteudo sera carregado via JavaScript -->
            </div>
        </div>
    </div>
</div>

{{-- QUICK-FIX MODAL --}}
<div id="modal-quickfix" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden z-50" onclick="fecharQuickFix()">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white border border-[var(--color-lab-border)] w-full max-w-sm max-h-[92vh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="p-4 border-b border-[var(--color-lab-border)] flex justify-between items-center">
                <div class="min-w-0">
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Edi&ccedil;&atilde;o R&aacute;pida</p>
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
                    <input id="qf-custo" type="text" placeholder="ex: 599,90"
                           class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono text-sm focus:outline-none focus:border-black">
                    <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-1">Use v&iacute;rgula como decimal: 1.234,56</p>
                </div>
                <div>
                    <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-1">Estoque</label>
                    <input id="qf-estoque" type="number" min="0"
                           class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono text-sm focus:outline-none focus:border-black">
                </div>
                <div class="flex flex-col sm:flex-row gap-2 pt-2">
                    <button type="submit" id="qf-btn-salvar"
                            class="flex-1 bg-black text-white font-mono text-[10px] uppercase tracking-widest py-2 hover:bg-gray-800 transition-colors">
                        Salvar
                    </button>
                    <button type="button" onclick="fecharQuickFix()"
                            class="px-4 py-2 border border-[var(--color-lab-border)] font-mono text-[10px] uppercase tracking-widest text-black hover:border-black transition-colors">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Full product modal (for Novo Produto action bar button) --}}
@include('admin.includes.modal-produto')

<script>
(function () {
    var CSRF = document.querySelector('meta[name="csrf-token"]').content;
    var exportMenuRoot = document.querySelector('[data-export-menu]');
    var exportMenuButton = document.getElementById('export-menu-button');
    var exportMenuPanel = document.getElementById('export-menu-panel');
    var exportMenuChevron = document.getElementById('export-menu-chevron');

    function setExportMenuState(open) {
        if (!exportMenuRoot || !exportMenuButton || !exportMenuPanel) return;

        exportMenuPanel.classList.toggle('hidden', !open);
        exportMenuButton.setAttribute('aria-expanded', open ? 'true' : 'false');

        if (exportMenuChevron) {
            exportMenuChevron.classList.toggle('rotate-180', open);
        }
    }

    function initExportMenu() {
        if (!exportMenuRoot || !exportMenuButton || !exportMenuPanel) return;

        exportMenuButton.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var isOpen = !exportMenuPanel.classList.contains('hidden');
            setExportMenuState(!isOpen);
        });

        exportMenuPanel.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                setExportMenuState(false);
            });
        });

        document.addEventListener('click', function (e) {
            if (!exportMenuRoot.contains(e.target)) {
                setExportMenuState(false);
            }
        });
    }

    function initCollapsibles() {
        document.querySelectorAll('[data-section]').forEach(function(section) {
            var id      = section.dataset.section;
            var content = section.querySelector('[data-section-content]');
            var arrow   = section.querySelector('[data-section-arrow]');
            var toggle  = section.querySelector('[data-section-toggle]');
            if (!content || !toggle) return;

            var saved   = localStorage.getItem('dash-section-' + id);
            var defOpen = section.dataset.defaultOpen !== 'false';
            var isOpen  = saved === null ? defOpen : (saved === 'open');

            if (!isOpen) { content.classList.add('hidden'); if (arrow) arrow.textContent = '\u25B6'; }
            else          { content.classList.remove('hidden'); if (arrow) arrow.textContent = '\u25BC'; }

            toggle.addEventListener('click', function() {
                var open = !content.classList.contains('hidden');
                content.classList.toggle('hidden', open);
                if (arrow) arrow.textContent = open ? '\u25B6' : '\u25BC';
                localStorage.setItem('dash-section-' + id, open ? 'closed' : 'open');
            });
        });
    }

    function initExpandableAlerts() {
        document.querySelectorAll('[data-expandable]').forEach(function(row) {
            row.addEventListener('click', function() {
                var target = document.getElementById(row.dataset.expandable);
                var arrowEl = row.querySelector('[data-expand-arrow]');
                if (!target) return;
                var open = !target.classList.contains('hidden');
                target.classList.toggle('hidden', open);
                if (arrowEl) arrowEl.textContent = open ? '\u25B6' : '\u25BC';
            });
        });
    }

    var quickFixId = null;

    window.abrirQuickFix = function(id, nome, custo, estoque) {
        quickFixId = id;
        document.getElementById('qf-nome').textContent    = nome;
        document.getElementById('qf-custo').value         = custo ? String(custo).replace('.', ',') : '';
        document.getElementById('qf-estoque').value       = estoque;
        document.getElementById('qf-btn-salvar').disabled = false;
        document.getElementById('qf-btn-salvar').textContent = 'Salvar';
        document.getElementById('modal-quickfix').classList.remove('hidden');
        document.getElementById('qf-custo').focus();
    };

    window.fecharQuickFix = function() {
        document.getElementById('modal-quickfix').classList.add('hidden');
        quickFixId = null;
    };

    document.getElementById('form-quickfix').addEventListener('submit', async function(e) {
        e.preventDefault();
        if (!quickFixId) return;

        var btn = document.getElementById('qf-btn-salvar');
        btn.disabled    = true;
        btn.textContent = 'Salvando...';

        var custo   = document.getElementById('qf-custo').value.trim();
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
                row.dataset.custo   = data.custo_compra != null ? data.custo_compra : '';
                row.dataset.margem  = data.margem != null ? data.margem : '';
                row.dataset.lucro   = data.lucro != null ? data.lucro : '';
                row.dataset.estoque = data.estoque;

                var cellCusto = row.querySelector('[data-cell="custo"]');
                if (cellCusto) cellCusto.textContent = data.custo_efetivo
                    ? 'R$ ' + fmtMoney(data.custo_efetivo) : '\u2014';

                var cellLucro = row.querySelector('[data-cell="lucro"]');
                if (cellLucro) cellLucro.textContent = data.lucro !== null
                    ? 'R$ ' + fmtMoney(data.lucro) : '\u2014';

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
            btn.disabled    = false;
            btn.textContent = 'Salvar';
        }
    });

    function fmtMoney(val) {
        return parseFloat(val).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function atualizarBadgeMargem(cell, margem) {
        if (margem === null || margem === undefined || margem === '') {
            cell.innerHTML = '<span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-blue-50 text-blue-500 border border-blue-200">Sem custo</span>';
        } else {
            var m = parseFloat(margem);
            var fmt = m.toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%';
            if (m < 0)      cell.innerHTML = '<span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-red-100 text-red-700 font-bold">' + fmt + '</span>';
            else if (m < 20) cell.innerHTML = '<span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-yellow-100 text-yellow-700 font-bold">' + fmt + '</span>';
            else             cell.innerHTML = '<span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-green-100 text-green-700 font-bold">' + fmt + '</span>';
        }
    }

    window.avancarStatusPedido = async function(btn, pedidoId, novoStatus) {
        btn.disabled    = true;
        var original    = btn.textContent;
        btn.textContent = '...';

        try {
            var payload = { status: novoStatus };

            if (novoStatus === 'enviado') {
                var trackingCode = window.prompt('Informe o código de rastreio para marcar o pedido como enviado:', '');

                if (trackingCode === null) {
                    btn.disabled = false;
                    btn.textContent = original;
                    return;
                }

                trackingCode = trackingCode.trim().toUpperCase();

                if (!trackingCode) {
                    throw new Error('codigo_rastreio_obrigatorio');
                }

                payload.codigo_rastreio = trackingCode;
            }

            var url = (window.routes.adminPedidosQuickStatus || '').replace(':id', pedidoId);
            var res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify(payload),
            });
            var data = await res.json();
            if (!data.success) throw new Error('Falha');

            var rowEl = document.getElementById('pedido-acao-' + pedidoId);
            if (!rowEl) return;

            if (novoStatus === 'enviado') {
                rowEl.style.transition = 'opacity 0.4s';
                rowEl.style.opacity    = '0';
                setTimeout(function() { rowEl.remove(); }, 400);
            } else {
                var badge = rowEl.querySelector('[data-status-badge]');
                if (badge) {
                    badge.textContent = 'Processando';
                    badge.className = badge.className
                        .replace('border-green-500 text-green-700', '')
                        .replace('border-yellow-500 text-yellow-700', '')
                        .replace('border-gray-400 text-gray-600', '')
                        .trim() + ' border-blue-500 text-blue-700';
                }
                btn.textContent = '\u2192 Enviado';
                btn.onclick     = function() { window.avancarStatusPedido(btn, pedidoId, 'enviado'); };
                btn.disabled    = false;
            }

            mostrarToast('Status atualizado');
        } catch (err) {
            btn.disabled    = false;
            btn.textContent = original;
            if (err && err.message === 'codigo_rastreio_obrigatorio') {
                alert('O código de rastreio é obrigatório para marcar o pedido como enviado.');
            } else {
                alert('Erro ao atualizar status.');
            }
        }
    };

    function mostrarToast(msg) {
        var t = document.createElement('div');
        t.className   = 'fixed bottom-4 right-4 bg-black text-white font-mono text-xs px-4 py-2 z-[100]';
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(function() { t.style.transition = 'opacity 0.3s'; t.style.opacity = '0'; setTimeout(function() { t.remove(); }, 300); }, 2000);
    }

    var tableEl  = document.getElementById('tabela-analytics');
    var numCols  = ['preco','custo','lucro','margem','estoque','status'];
    var currentCol = 'margem', currentDir = 'asc';

    function sortTable(col, dir) {
        var tbody = tableEl.querySelector('tbody');
        var rows  = Array.from(tbody.querySelectorAll('tr'));
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
                currentCol = col; currentDir = dir;
                sortTable(col, dir);
                updateArrows(col, dir);
            });
        });
        sortTable('margem', 'asc');
        updateArrows('margem', 'asc');
    }


    initCollapsibles();
    initExpandableAlerts();
    initExportMenu();

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') setExportMenuState(false);
        if (e.key === 'Escape') fecharQuickFix();
    });
})();
</script>
@endsection
