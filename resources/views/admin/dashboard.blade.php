@extends('includes.header-admin')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-4 lg:space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-end gap-4">
        <div>
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-1">Overview</p>
            <h1 class="text-2xl sm:text-3xl font-bold text-black tracking-tight">Dashboard</h1>
        </div>
        <div class="font-mono text-xs text-[var(--color-lab-muted)] uppercase tracking-widest">
            {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    {{-- ACTION BAR --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-2">
        <button onclick="abrirModalProduto()"
                class="inline-flex items-center justify-center gap-2 px-4 py-3 bg-black text-white font-mono text-[10px] uppercase tracking-widest hover:bg-gray-800 transition-colors text-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            Novo Produto
        </button>
        <a href="{{ route('admin.pedidos') }}"
           class="inline-flex items-center justify-center gap-2 px-4 py-3 border font-mono text-[10px] uppercase tracking-widest hover:border-black transition-colors text-center {{ $pedidos_pendentes > 0 ? 'border-yellow-400 text-yellow-700' : 'border-[var(--color-lab-border)] text-black' }}">
            Pendentes: {{ $pedidos_pendentes }}
        </a>
        <a href="{{ route('admin.pedidos') }}"
           class="inline-flex items-center justify-center gap-2 px-4 py-3 border border-[var(--color-lab-border)] font-mono text-[10px] uppercase tracking-widest text-black hover:border-black transition-colors text-center">
            Processando: {{ $pedidos_processando }}
        </a>
        <a href="{{ route('admin.pedidos') }}"
           class="inline-flex items-center justify-center gap-2 px-4 py-3 border border-[var(--color-lab-border)] font-mono text-[10px] uppercase tracking-widest text-black hover:border-black transition-colors text-center">
            Ver Pedidos
        </a>
        <a href="{{ route('admin.dashboard.exportar.csv') }}"
           class="inline-flex items-center justify-center gap-2 px-4 py-3 border border-[var(--color-lab-border)] font-mono text-[10px] uppercase tracking-widest text-black hover:border-black transition-colors text-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
            Exportar Analytics CSV
        </a>
        <a href="{{ route('admin.dashboard.exportar.pdf') }}"
           class="inline-flex items-center justify-center gap-2 px-4 py-3 border border-[var(--color-lab-border)] font-mono text-[10px] uppercase tracking-widest text-black hover:border-black transition-colors text-center sm:col-span-2 xl:col-span-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
            Exportar Analytics PDF
        </a>
    </div>

    {{-- KPI CARDS --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="border border-[var(--color-lab-border)] bg-white p-5 sm:p-6">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Receita Total</p>
            <p class="text-lg sm:text-2xl font-bold text-black font-mono">R$&nbsp;{{ number_format($receita_total, 2, ',', '.') }}</p>
            <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-1">pedidos entregues</p>
        </div>
        <div class="border border-[var(--color-lab-border)] bg-white p-5 sm:p-6">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Lucro Bruto</p>
            <p class="text-lg sm:text-2xl font-bold font-mono {{ $lucro_bruto_total >= 0 ? 'text-black' : 'text-red-600' }}">R$&nbsp;{{ number_format($lucro_bruto_total, 2, ',', '.') }}</p>
            <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-1">receita &minus; custo</p>
        </div>
        <div class="border border-[var(--color-lab-border)] bg-white p-5 sm:p-6">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Margem Bruta</p>
            <p class="text-2xl sm:text-3xl font-bold font-mono @if($margem_bruta_percentual >= 20) text-black @elseif($margem_bruta_percentual >= 0) text-yellow-600 @else text-red-600 @endif">{{ number_format($margem_bruta_percentual, 1, ',', '.') }}%</p>
            <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-1">sobre receita</p>
        </div>
        <div class="border border-[var(--color-lab-border)] bg-white p-5 sm:p-6">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Pendentes</p>
            <p class="text-2xl sm:text-3xl font-bold text-black font-mono">{{ $pedidos_pendentes }}</p>
            <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-1">aguardando</p>
        </div>
    </div>

    {{-- SECTION: ALERTAS + STATUS --}}
    <div data-section="alertas" data-default-open="true" class="border border-[var(--color-lab-border)] bg-white">
        <div data-section-toggle class="flex items-center justify-between gap-3 px-4 sm:px-6 py-4 cursor-pointer hover:bg-gray-50 border-b border-[var(--color-lab-border)] select-none">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">
                <span data-section-arrow>&#9660;</span>&nbsp; Alertas &amp; Status
            </p>
        </div>
        <div data-section-content class="p-4 sm:p-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Alertas --}}
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-4">Alertas</p>
                    @php
                        $temCritico  = $alertas['margem_negativa'] > 0 || $alertas['margem_zero'] > 0;
                        $temAtencao  = $alertas['margem_baixa'] > 0 || $alertas['estoque_zerado'] > 0;
                        $temSemCusto = $alertas['sem_custo'] > 0;
                        $temInfo     = $alertas['sem_imagem'] > 0 || $alertas['inativos'] > 0;
                    @endphp

                    @if(!$temCritico && !$temAtencao && !$temSemCusto)
                    <div class="flex items-center gap-2 px-3 py-2 bg-green-50 border border-green-200 mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-600 shrink-0"><path d="M20 6 9 17l-5-5"/></svg>
                        <span class="font-mono text-[10px] uppercase tracking-widest text-green-700">Tudo OK</span>
                    </div>
                    @endif

                    <div class="space-y-1">
                        @if($temCritico)
                        <p class="font-mono text-[10px] uppercase tracking-widest text-red-500 mt-3 mb-1">Cr&iacute;tico</p>
                        @endif

                        @if($alertas['margem_negativa'] > 0)
                        <div>
                            <div data-expandable="alert-margem-neg" class="flex items-center justify-between py-2 border-l-2 border-red-500 pl-3 cursor-pointer hover:bg-red-50 select-none">
                                <div class="flex items-center gap-2">
                                    <span data-expand-arrow class="font-mono text-[10px] text-red-400">&#9654;</span>
                                    <span class="font-mono text-xs text-black">Margem negativa</span>
                                </div>
                                <span class="font-mono text-xs font-bold text-red-600">{{ $alertas['margem_negativa'] }}</span>
                            </div>
                            <div id="alert-margem-neg" class="hidden ml-3 mt-1 space-y-1">
                                @foreach($alertas['produtos_margem_negativa'] as $ap)
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between py-2 px-3 bg-red-50" data-alert-produto="{{ $ap->id }}">
                                    <span class="font-mono text-xs text-black break-words sm:truncate sm:max-w-[140px]">{{ $ap->nome }}</span>
                                    <button onclick="abrirQuickFix({{ $ap->id }}, '{{ addslashes($ap->nome) }}', '{{ $ap->custo_compra }}', {{ $ap->estoque }})"
                                            class="font-mono text-[10px] uppercase tracking-widest px-2 py-1 border border-red-300 text-red-600 hover:bg-red-100 shrink-0">
                                        &#9998; Editar
                                    </button>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if($alertas['margem_zero'] > 0)
                        <div>
                            <div data-expandable="alert-margem-zero" class="flex items-center justify-between py-2 border-l-2 border-red-500 pl-3 cursor-pointer hover:bg-red-50 select-none">
                                <div class="flex items-center gap-2">
                                    <span data-expand-arrow class="font-mono text-[10px] text-red-400">&#9654;</span>
                                    <span class="font-mono text-xs text-black">Margem zero</span>
                                </div>
                                <span class="font-mono text-xs font-bold text-red-600">{{ $alertas['margem_zero'] }}</span>
                            </div>
                            <div id="alert-margem-zero" class="hidden ml-3 mt-1 space-y-1">
                                @foreach($alertas['produtos_margem_zero'] as $ap)
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between py-2 px-3 bg-red-50" data-alert-produto="{{ $ap->id }}">
                                    <span class="font-mono text-xs text-black break-words sm:truncate sm:max-w-[140px]">{{ $ap->nome }}</span>
                                    <button onclick="abrirQuickFix({{ $ap->id }}, '{{ addslashes($ap->nome) }}', '{{ $ap->custo_compra }}', {{ $ap->estoque }})"
                                            class="font-mono text-[10px] uppercase tracking-widest px-2 py-1 border border-red-300 text-red-600 hover:bg-red-100 shrink-0">
                                        &#9998; Editar
                                    </button>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if($temAtencao)
                        <p class="font-mono text-[10px] uppercase tracking-widest text-yellow-600 mt-3 mb-1">Aten&ccedil;&atilde;o</p>
                        @endif

                        @if($alertas['margem_baixa'] > 0)
                        <div>
                            <div data-expandable="alert-margem-baixa" class="flex items-center justify-between py-2 border-l-2 border-yellow-400 pl-3 cursor-pointer hover:bg-yellow-50 select-none">
                                <div class="flex items-center gap-2">
                                    <span data-expand-arrow class="font-mono text-[10px] text-yellow-400">&#9654;</span>
                                    <span class="font-mono text-xs text-black">Margem abaixo de 20%</span>
                                </div>
                                <span class="font-mono text-xs font-bold text-yellow-600">{{ $alertas['margem_baixa'] }}</span>
                            </div>
                            <div id="alert-margem-baixa" class="hidden ml-3 mt-1 space-y-1">
                                @foreach($alertas['produtos_margem_baixa'] as $ap)
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between py-2 px-3 bg-yellow-50" data-alert-produto="{{ $ap->id }}">
                                    <div>
                                        <span class="font-mono text-xs text-black break-words sm:truncate sm:max-w-[120px] block">{{ $ap->nome }}</span>
                                        <span class="font-mono text-[10px] text-yellow-600">{{ number_format($ap->margem_bruta_percentual, 1, ',', '.') }}%</span>
                                    </div>
                                    <button onclick="abrirQuickFix({{ $ap->id }}, '{{ addslashes($ap->nome) }}', '{{ $ap->custo_compra }}', {{ $ap->estoque }})"
                                            class="font-mono text-[10px] uppercase tracking-widest px-2 py-1 border border-yellow-300 text-yellow-700 hover:bg-yellow-100 shrink-0">
                                        &#9998; Editar
                                    </button>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if($alertas['estoque_zerado'] > 0)
                        <div>
                            <div data-expandable="alert-estoque" class="flex items-center justify-between py-2 border-l-2 border-yellow-400 pl-3 cursor-pointer hover:bg-yellow-50 select-none">
                                <div class="flex items-center gap-2">
                                    <span data-expand-arrow class="font-mono text-[10px] text-yellow-400">&#9654;</span>
                                    <span class="font-mono text-xs text-black">Estoque zerado (ativo)</span>
                                </div>
                                <span class="font-mono text-xs font-bold text-yellow-600">{{ $alertas['estoque_zerado'] }}</span>
                            </div>
                            <div id="alert-estoque" class="hidden ml-3 mt-1 space-y-1">
                                @foreach($alertas['produtos_estoque_zerado'] as $ap)
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between py-2 px-3 bg-yellow-50" data-alert-produto="{{ $ap->id }}">
                                    <span class="font-mono text-xs text-black break-words sm:truncate sm:max-w-[140px]">{{ $ap->nome }}</span>
                                    <button onclick="abrirQuickFix({{ $ap->id }}, '{{ addslashes($ap->nome) }}', '{{ $ap->custo_compra }}', {{ $ap->estoque }})"
                                            class="font-mono text-[10px] uppercase tracking-widest px-2 py-1 border border-yellow-300 text-yellow-700 hover:bg-yellow-100 shrink-0">
                                        &#9998; Estoque
                                    </button>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if($temSemCusto)
                        <p class="font-mono text-[10px] uppercase tracking-widest text-blue-600 mt-3 mb-1">Dados Incompletos</p>
                        <div>
                            <div data-expandable="alert-sem-custo" class="flex items-center justify-between py-2 border-l-2 border-blue-400 pl-3 cursor-pointer hover:bg-blue-50 select-none">
                                <div class="flex items-center gap-2">
                                    <span data-expand-arrow class="font-mono text-[10px] text-blue-400">&#9654;</span>
                                    <div>
                                        <span class="font-mono text-xs font-bold text-black">Pre&ccedil;o de compra n&atilde;o cadastrado</span>
                                        <p class="font-mono text-[10px] text-blue-500">Margem indispon&iacute;vel &mdash; cadastre o custo de aquisi&ccedil;&atilde;o</p>
                                    </div>
                                </div>
                                <span class="font-mono text-xs font-bold text-blue-600 shrink-0">{{ $alertas['sem_custo'] }}</span>
                            </div>
                            <div id="alert-sem-custo" class="hidden ml-3 mt-1 space-y-1">
                                @foreach($alertas['produtos_sem_custo'] as $ap)
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between py-2 px-3 bg-blue-50" data-alert-produto="{{ $ap->id }}">
                                    <div>
                                        <span class="font-mono text-xs text-black break-words sm:truncate sm:max-w-[120px] block">{{ $ap->nome }}</span>
                                        <span class="font-mono text-[10px] text-gray-400">R$ {{ number_format($ap->preco_com_desconto, 2, ',', '.') }}</span>
                                    </div>
                                    <button onclick="abrirQuickFix({{ $ap->id }}, '{{ addslashes($ap->nome) }}', '', {{ $ap->estoque }})"
                                            class="font-mono text-[10px] uppercase tracking-widest px-2 py-1 border border-blue-300 text-blue-600 hover:bg-blue-100 shrink-0">
                                        &#9998; Editar custo
                                    </button>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if($temInfo)
                        <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mt-3 mb-1">Informa&ccedil;&atilde;o</p>
                        @if($alertas['sem_imagem'] > 0)
                        <div class="flex items-center justify-between py-1.5 border-l-2 border-gray-300 pl-3">
                            <span class="font-mono text-xs text-black">Sem imagem (ativo)</span>
                            <span class="font-mono text-xs font-bold text-gray-500">{{ $alertas['sem_imagem'] }}</span>
                        </div>
                        @endif
                        @if($alertas['inativos'] > 0)
                        <div class="flex items-center justify-between py-1.5 border-l-2 border-gray-300 pl-3">
                            <span class="font-mono text-xs text-black">Produtos inativos</span>
                            <span class="font-mono text-xs font-bold text-gray-500">{{ $alertas['inativos'] }}</span>
                        </div>
                        @endif
                        @endif
                    </div>
                </div>

                {{-- Status dos pedidos --}}
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-4">Status dos Pedidos</p>
                    <div class="space-y-3">
                        @foreach([
                            ['label'=>'Pendentes',   'val'=>$pedidos_pendentes,   'color'=>'bg-gray-400'],
                            ['label'=>'Processando', 'val'=>$pedidos_processando, 'color'=>'bg-gray-600'],
                            ['label'=>'Enviados',    'val'=>$pedidos_enviados,    'color'=>'bg-gray-800'],
                            ['label'=>'Entregues',   'val'=>$pedidos_entregues,   'color'=>'bg-black'],
                            ['label'=>'Cancelados',  'val'=>$pedidos_cancelados,  'color'=>'bg-gray-300'],
                        ] as $s)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 {{ $s['color'] }} shrink-0"></div>
                                <span class="font-mono text-sm text-black">{{ $s['label'] }}</span>
                            </div>
                            <span class="font-mono text-sm font-bold text-black">{{ $s['val'] }}</span>
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

                {{-- Acoes rapidas --}}
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-4">A&ccedil;&otilde;es R&aacute;pidas</p>
                    <div class="space-y-2 mb-4">
                        <a href="{{ route('admin.produtos') }}" class="flex items-center gap-3 px-4 py-3 border border-[var(--color-lab-border)] hover:border-black hover:bg-gray-50 transition-colors">
                            <span class="font-mono text-xs uppercase tracking-widest text-black">Gerenciar Produtos</span>
                        </a>
                        <a href="{{ route('admin.categorias') }}" class="flex items-center gap-3 px-4 py-3 border border-[var(--color-lab-border)] hover:border-black hover:bg-gray-50 transition-colors">
                            <span class="font-mono text-xs uppercase tracking-widest text-black">Gerenciar Categorias</span>
                        </a>
                        <a href="{{ route('admin.pedidos') }}" class="flex items-center gap-3 px-4 py-3 border border-[var(--color-lab-border)] hover:border-black hover:bg-gray-50 transition-colors">
                            <span class="font-mono text-xs uppercase tracking-widest text-black">Ver Todos os Pedidos</span>
                        </a>
                    </div>
                    <div class="border-t border-[var(--color-lab-border)] pt-4 space-y-2">
                        <div class="flex justify-between">
                            <span class="font-mono text-xs text-[var(--color-lab-muted)]">Custo total</span>
                            <span class="font-mono text-xs font-bold text-black">R$&nbsp;{{ number_format($custo_total, 2, ',', '.') }}</span>
                        </div>
                        @if($itens_sem_custo > 0)
                        <div class="flex justify-between">
                            <span class="font-mono text-xs text-yellow-600">Itens s/ custo (entregues)</span>
                            <span class="font-mono text-xs font-bold text-yellow-600">{{ $itens_sem_custo }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SECTION: PEDIDOS QUE PRECISAM DE ACAO --}}
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
                        <span data-status-badge class="inline-block px-2 py-0.5 font-mono text-[10px] border {{ $pedido->status === 'pendente' ? 'border-gray-400 text-gray-600' : 'border-gray-600 text-gray-700' }}">
                            {{ ucfirst($pedido->status) }}
                        </span>
                    </div>
                    <div class="font-mono text-sm text-black">{{ $pedido->user->name }}</div>
                    <div class="font-mono text-xs text-[var(--color-lab-muted)]">R$&nbsp;{{ number_format($pedido->valor_total, 2, ',', '.') }}</div>
                </div>
                <div class="sm:mt-0">
                    @if($pedido->status === 'pendente')
                    <button onclick="avancarStatusPedido(this, {{ $pedido->id }}, 'processando')"
                            class="w-full sm:w-auto font-mono text-[10px] uppercase tracking-widest px-3 py-2 border border-black text-black hover:bg-black hover:text-white transition-colors">
                        &rarr; Processando
                    </button>
                    @elseif($pedido->status === 'processando')
                    <button onclick="avancarStatusPedido(this, {{ $pedido->id }}, 'enviado')"
                            class="w-full sm:w-auto font-mono text-[10px] uppercase tracking-widest px-3 py-2 border border-black text-black hover:bg-black hover:text-white transition-colors">
                        &rarr; Enviado
                    </button>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- SECTION: PERFORMANCE --}}
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
                        <p class="font-mono text-xs text-black truncate">{{ $item->produto?->nome ?? '&mdash;' }}</p>
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
                <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-4">M&eacute;tricas Gerais</p>
                <div class="space-y-4">
                    <div>
                        <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mb-1">Ticket M&eacute;dio</p>
                        <p class="font-mono text-xl font-bold text-black">R$&nbsp;{{ number_format($ticket_medio, 2, ',', '.') }}</p>
                        <p class="font-mono text-[10px] text-[var(--color-lab-muted)]">por pedido entregue</p>
                    </div>
                    <div>
                        <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mb-1">Unidades Vendidas</p>
                        <p class="font-mono text-xl font-bold text-black">{{ $total_unidades }}</p>
                        <p class="font-mono text-[10px] text-[var(--color-lab-muted)]">pedidos entregues</p>
                    </div>
                    <div>
                        <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mb-1">Produtos Ativos</p>
                        <p class="font-mono text-xl font-bold text-black">{{ $total_ativos }}</p>
                        <p class="font-mono text-[10px] text-[var(--color-lab-muted)]">de {{ $total_produtos }} total</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SECTION: ANALYTICS DE PRODUTOS --}}
    <div data-section="analytics" data-default-open="true" class="border border-[var(--color-lab-border)] bg-white">
        <div data-section-toggle class="flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:justify-between px-4 sm:px-6 py-4 cursor-pointer hover:bg-gray-50 border-b border-[var(--color-lab-border)] select-none">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">
                <span data-section-arrow>&#9660;</span>&nbsp; Analytics de Produtos
            </p>
            <span class="font-mono text-xs text-[var(--color-lab-muted)]">{{ $produtos_analytics->count() }} produtos &middot; clique nos cabe&ccedil;alhos para ordenar</span>
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
                        <th data-col="nome"    class="analytics-th text-left    px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Produto <span class="sort-arrow ml-1"></span></th>
                        <th data-col="marca"   class="analytics-th text-left    px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Marca <span class="sort-arrow ml-1"></span></th>
                        <th data-col="preco"   class="analytics-th text-right   px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Pre&ccedil;o Venda <span class="sort-arrow ml-1"></span></th>
                        <th data-col="custo"   class="analytics-th text-right   px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Custo <span class="sort-arrow ml-1"></span></th>
                        <th data-col="lucro"   class="analytics-th text-right   px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Lucro Unit. <span class="sort-arrow ml-1"></span></th>
                        <th data-col="margem"  class="analytics-th text-right   px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Margem % <span class="sort-arrow ml-1"></span></th>
                        <th data-col="estoque" class="analytics-th text-right   px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Estoque <span class="sort-arrow ml-1"></span></th>
                        <th data-col="status"  class="analytics-th text-center  px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Status <span class="sort-arrow ml-1"></span></th>
                        <th class="px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] whitespace-nowrap">A&ccedil;&otilde;es</th>
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
                                <span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-blue-50 text-blue-500 border border-blue-200" title="Preco de compra nao cadastrado">Sem custo</span>
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
                        <div class="font-mono text-sm text-black break-words sm:truncate">{{ $pedido->user->name }}</div>
                        <div class="font-mono text-xs text-[var(--color-lab-muted)] break-all sm:truncate">{{ $pedido->user->email }}</div>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 mt-2 sm:mt-0">
                        <div class="font-mono text-sm font-bold text-black text-right">R$&nbsp;{{ number_format($pedido->valor_total, 2, ',', '.') }}</div>
                        @php $sc = ['pendente'=>'border-gray-400 text-gray-600','processando'=>'border-gray-600 text-gray-700','enviado'=>'border-gray-800 text-gray-800','entregue'=>'border-black text-black','cancelado'=>'border-gray-300 text-gray-400']; @endphp
                        <span class="inline-block px-3 py-1 font-mono text-[10px] uppercase tracking-widest border {{ $sc[$pedido->status] ?? 'border-gray-300 text-gray-500' }}">
                            {{ ucfirst($pedido->status) }}
                        </span>
                    </div>
                </div>
            </div>
            @empty
            <p class="font-mono text-xs uppercase tracking-widest text-[var(--color-lab-muted)] text-center py-8">Nenhum pedido</p>
            @endforelse
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
                if (cellCusto) cellCusto.textContent = data.custo_compra
                    ? 'R$ ' + fmtMoney(data.custo_compra) : '\u2014';

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
            var url = (window.routes.adminPedidosQuickStatus || '').replace(':id', pedidoId);
            var res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ status: novoStatus }),
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
                if (badge) { badge.textContent = 'Processando'; badge.className = badge.className.replace('border-gray-400 text-gray-600','border-gray-600 text-gray-700'); }
                btn.textContent = '\u2192 Enviado';
                btn.onclick     = function() { window.avancarStatusPedido(btn, pedidoId, 'enviado'); };
                btn.disabled    = false;
            }

            mostrarToast('Status atualizado');
        } catch (err) {
            btn.disabled    = false;
            btn.textContent = original;
            alert('Erro ao atualizar status.');
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

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') fecharQuickFix();
    });
})();
</script>
@endsection
