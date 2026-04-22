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

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        <div class="border border-[var(--color-lab-border)] bg-white p-5 sm:p-6">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Receita do Produto</p>
            <p class="text-lg sm:text-2xl font-bold text-black font-mono">R$ {{ number_format($receitaTotal, 2, ',', '.') }}</p>
            <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-1">{{ $pedidosCount }} pedido(s) no recorte</p>
        </div>
        <div class="border border-[var(--color-lab-border)] bg-white p-5 sm:p-6">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Lucro Bruto</p>
            <p class="text-lg sm:text-2xl font-bold font-mono {{ $lucroBrutoTotal >= 0 ? 'text-black' : 'text-red-600' }}">R$ {{ number_format($lucroBrutoTotal, 2, ',', '.') }}</p>
            <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-1">custo total: R$ {{ number_format($product_metrics['custo_total'], 2, ',', '.') }}</p>
        </div>
        <div class="border border-[var(--color-lab-border)] bg-white p-5 sm:p-6">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Margem Bruta</p>
            <p class="text-2xl sm:text-3xl font-bold font-mono {{ $margemBrutaPercentual >= 20 ? 'text-black' : ($margemBrutaPercentual >= 0 ? 'text-yellow-600' : 'text-red-600') }}">{{ number_format($margemBrutaPercentual, 1, ',', '.') }}%</p>
            <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-1">sobre a receita do produto</p>
        </div>
        <div class="border border-[var(--color-lab-border)] bg-white p-5 sm:p-6">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Unidades Vendidas</p>
            <p class="text-2xl sm:text-3xl font-bold text-black font-mono">{{ $unidadesVendidas }}</p>
            <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-1">itens do produto vendidos</p>
        </div>
        <div class="border border-[var(--color-lab-border)] bg-white p-5 sm:p-6">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Ticket Médio do Produto</p>
            <p class="text-lg sm:text-2xl font-bold text-black font-mono">R$ {{ number_format($ticketMedioProduto, 2, ',', '.') }}</p>
            <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-1">receita do produto por pedido</p>
        </div>
        <div class="border border-[var(--color-lab-border)] bg-white p-5 sm:p-6">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Itens Sem Custo</p>
            <p class="text-2xl sm:text-3xl font-bold text-black font-mono">{{ $itensSemCusto }}</p>
            <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-1">{{ $ultimoPedidoEm ? 'último pedido em ' . \Illuminate\Support\Carbon::parse($ultimoPedidoEm)->format('d/m/Y H:i') : 'sem pedidos neste recorte' }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)] gap-4">
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
                <div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] p-4 sm:col-span-2">
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Leitura rápida</p>
                    <p class="mt-2 text-sm text-black">
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

        <div class="border border-[var(--color-lab-border)] bg-white p-5 sm:p-6">
            <div class="flex items-center justify-between gap-3 mb-4">
                <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Dados de Catálogo</p>
                <span class="font-mono text-xs text-[var(--color-lab-muted)]">estado atual</span>
            </div>
            <div class="space-y-3">
                <div class="flex items-center justify-between gap-4 border-b border-[var(--color-lab-border)] pb-3">
                    <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Preço venda</span>
                    <span class="font-mono text-sm font-bold text-black">R$ {{ number_format($produto->preco_com_desconto, 2, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between gap-4 border-b border-[var(--color-lab-border)] pb-3">
                    <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Custo</span>
                    <span class="font-mono text-sm font-bold {{ $produto->custo_compra !== null ? 'text-black' : 'text-gray-400' }}">
                        {{ $produto->custo_compra !== null ? 'R$ ' . number_format($produto->custo_compra, 2, ',', '.') : 'Não cadastrado' }}
                    </span>
                </div>
                <div class="flex items-center justify-between gap-4 border-b border-[var(--color-lab-border)] pb-3">
                    <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Lucro unitário</span>
                    <span class="font-mono text-sm font-bold {{ $produto->lucro_bruto_unitario !== null && $produto->lucro_bruto_unitario < 0 ? 'text-red-600' : 'text-black' }}">
                        {{ $produto->lucro_bruto_unitario !== null ? 'R$ ' . number_format($produto->lucro_bruto_unitario, 2, ',', '.') : '—' }}
                    </span>
                </div>
                <div class="flex items-center justify-between gap-4 border-b border-[var(--color-lab-border)] pb-3">
                    <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Margem unitária</span>
                    <span class="font-mono text-sm font-bold {{ $produto->margem_bruta_percentual !== null && $produto->margem_bruta_percentual < 20 ? 'text-yellow-700' : 'text-black' }}">
                        {{ $produto->margem_bruta_percentual !== null ? number_format($produto->margem_bruta_percentual, 1, ',', '.') . '%' : '—' }}
                    </span>
                </div>
                <div class="flex items-center justify-between gap-4 border-b border-[var(--color-lab-border)] pb-3">
                    <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Estoque</span>
                    <span class="font-mono text-sm font-bold {{ $produto->ativo && $produto->estoque == 0 ? 'text-yellow-700' : 'text-black' }}">{{ $produto->estoque }}</span>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Status</span>
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
