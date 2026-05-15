@extends('includes.header-admin')

@section('title', $produto->nome . ' — Admin')

@section('content')

@php
    $precoVenda = $produto->em_promocao && $produto->desconto_percentual > 0
        ? round($produto->preco * (1 - $produto->desconto_percentual / 100), 2)
        : (float) $produto->preco;

    $custoAquisicao    = (float) ($produto->custo_compra ?? 0) + (float) ($produto->frete_compra ?? 0);
    $freteEntrega      = $produto->peso ? (float) $produto->frete_estimado_entrega : null;
    $custoTotal        = $custoAquisicao + ($freteEntrega ?? 0);
    $lucro             = $custoAquisicao > 0 ? $precoVenda - $custoTotal : null;
    $margem            = ($lucro !== null && $precoVenda > 0) ? ($lucro / $precoVenda * 100) : null;

    $imagens = $produto->imagens->sortBy('ordem');
    $capa    = $imagens->firstWhere('capa', true) ?? $imagens->first();
@endphp

{{-- Breadcrumb / voltar --}}
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3 min-w-0">
        <a href="{{ route('admin.produtos') }}" class="font-mono text-xs uppercase tracking-widest text-gray-400 hover:text-black shrink-0">← Produtos</a>
        <span class="text-gray-300 shrink-0">/</span>
        <span class="font-mono text-xs uppercase tracking-widest text-black truncate">{{ $produto->nome }}</span>
    </div>
    <div class="flex items-center gap-2 shrink-0 ml-4">
        @if(Auth::user()?->isSuperAdmin())
            <a href="{{ route('admin.analytics.products.show', $produto->id) }}"
               class="border border-[var(--color-lab-border)] px-4 py-2 font-mono text-xs uppercase tracking-widest hover:bg-gray-100 transition-colors">
                Analytics
            </a>
        @endif
        <button onclick="editarProduto({{ $produto->id }})"
                class="bg-black text-white px-4 py-2 font-mono text-xs uppercase tracking-widest hover:bg-gray-900 transition-colors">
            Editar
        </button>
    </div>
</div>

{{-- Header do produto --}}
<div class="border border-[var(--color-lab-border)] bg-white px-5 py-4 mb-4 flex items-start justify-between gap-4 flex-wrap">
    <div class="min-w-0">
        <h2 class="font-mono text-xl font-bold uppercase tracking-widest text-black">{{ $produto->nome }}</h2>
        <div class="mt-1 flex flex-wrap items-center gap-2 font-mono text-xs text-gray-500">
            @if($produto->marca)
                <span>{{ $produto->marca }}</span>
                <span class="text-gray-300">·</span>
            @endif
            <span>{{ $produto->categoria->nome ?? '—' }}</span>
            <span class="text-gray-300">·</span>
            <span>SKU base #{{ $produto->id }}</span>
        </div>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
        @if($produto->ativo)
            <span class="px-2 py-0.5 text-[10px] font-mono uppercase tracking-widest bg-black text-white">● Ativo</span>
        @else
            <span class="px-2 py-0.5 text-[10px] font-mono uppercase tracking-widest border border-gray-300 text-gray-400">○ Inativo</span>
        @endif
        @if($produto->destaque)
            <span class="px-2 py-0.5 text-[10px] font-mono uppercase tracking-widest border border-[var(--color-lab-border)]">★ Destaque</span>
        @endif
        @if($produto->em_promocao && $produto->desconto_percentual > 0)
            <span class="px-2 py-0.5 text-[10px] font-mono uppercase tracking-widest bg-gray-100 border border-gray-200">
                -{{ number_format($produto->desconto_percentual, 0) }}% OFF
            </span>
        @endif
    </div>
</div>

{{-- Grid principal --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">

    {{-- Galeria de imagens --}}
    <div class="lg:col-span-2 border border-[var(--color-lab-border)] bg-white">
        @if($imagens->isEmpty())
            <div class="flex items-center justify-center h-56 text-gray-400 font-mono text-xs">Sem imagens</div>
        @else
            <div class="p-4">
                {{-- Imagem principal --}}
                <div class="mb-3 bg-gray-50 border border-[var(--color-lab-border)] flex items-center justify-center" style="height: 280px;">
                    <img id="img-principal"
                         src="{{ asset('storage/' . $capa->caminho) }}"
                         alt="{{ $produto->nome }}"
                         class="max-h-full max-w-full object-contain p-4">
                </div>
                {{-- Thumbnails --}}
                @if($imagens->count() > 1)
                <div class="flex gap-2 overflow-x-auto pb-1">
                    @foreach($imagens as $imagem)
                    <button onclick="document.getElementById('img-principal').src='{{ asset('storage/' . $imagem->caminho) }}'"
                            class="shrink-0 border-2 border-[var(--color-lab-border)] hover:border-black transition-colors {{ $imagem->id === ($capa?->id) ? 'border-black' : '' }}"
                            style="width:64px;height:64px;">
                        <img src="{{ asset('storage/' . $imagem->caminho) }}" class="w-full h-full object-cover">
                    </button>
                    @endforeach
                </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Preços e estoque --}}
    <div class="space-y-4">
        <div class="border border-[var(--color-lab-border)] bg-white p-4 font-mono">
            <p class="text-[10px] uppercase tracking-widest text-gray-400 mb-3">Preço</p>
            @if($produto->em_promocao && $produto->desconto_percentual > 0)
                <p class="text-2xl font-bold text-black">R$ {{ number_format($precoVenda, 2, ',', '.') }}</p>
                <p class="text-sm text-gray-400 line-through mt-0.5">R$ {{ number_format($produto->preco, 2, ',', '.') }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ number_format($produto->desconto_percentual, 0) }}% de desconto</p>
            @else
                <p class="text-2xl font-bold text-black">R$ {{ number_format($precoVenda, 2, ',', '.') }}</p>
            @endif
        </div>

        <div class="border border-[var(--color-lab-border)] bg-white p-4 font-mono">
            <p class="text-[10px] uppercase tracking-widest text-gray-400 mb-3">Estoque</p>
            <p class="text-2xl font-bold {{ $produto->estoque > 0 ? 'text-black' : 'text-red-600' }}">
                {{ $produto->estoque }}
            </p>
            <p class="text-xs text-gray-400 mt-0.5">{{ $produto->estoque > 0 ? 'em estoque' : 'esgotado' }}</p>
            @if($produto->peso)
                <p class="text-xs text-gray-400 mt-2">Peso: {{ number_format($produto->peso, 3, ',', '.') }} kg</p>
                @if($produto->peso_dimensional)
                <p class="text-xs text-gray-400 mt-1">
                    Dimensional: {{ number_format($produto->peso_dimensional, 3, ',', '.') }} kg
                    @if($produto->peso_cobranca > $produto->peso)
                        <span class="text-orange-600">(cobra por {{ number_format($produto->peso_cobranca, 3, ',', '.') }} kg)</span>
                    @endif
                </p>
                @endif
            @endif
        </div>

        @if($custoAquisicao > 0 || $freteEntrega !== null)
        <div class="border border-[var(--color-lab-border)] bg-white p-4 font-mono text-xs space-y-1.5">
            <p class="text-[10px] uppercase tracking-widest text-gray-400 mb-3">Financeiro</p>
            <div class="flex justify-between">
                <span class="text-gray-500">Custo compra</span>
                <span>R$ {{ number_format($produto->custo_compra ?? 0, 2, ',', '.') }}</span>
            </div>
            @if($produto->frete_compra)
            <div class="flex justify-between">
                <span class="text-gray-500">Frete entrada</span>
                <span>R$ {{ number_format($produto->frete_compra, 2, ',', '.') }}</span>
            </div>
            @endif
            @if($freteEntrega !== null)
            <div class="flex justify-between">
                <span class="text-gray-500">Frete saída <span class="text-[9px] text-gray-400">(est.)</span></span>
                <span>R$ {{ number_format($freteEntrega, 2, ',', '.') }}</span>
            </div>
            @endif
            <div class="flex justify-between border-t border-[var(--color-lab-border)] pt-1.5 mt-1">
                <span class="text-gray-500">Custo total</span>
                <span>R$ {{ number_format($custoTotal, 2, ',', '.') }}</span>
            </div>
            @if($lucro !== null)
            <div class="flex justify-between">
                <span class="text-gray-500">Lucro unit.</span>
                <span class="{{ $lucro >= 0 ? 'text-black' : 'text-red-600' }}">R$ {{ number_format($lucro, 2, ',', '.') }}</span>
            </div>
            <div class="flex justify-between font-bold border-t border-[var(--color-lab-border)] pt-1.5 mt-1">
                <span>Margem bruta</span>
                <span class="{{ $margem >= 20 ? 'text-black' : ($margem >= 0 ? 'text-gray-600' : 'text-red-600') }}">
                    {{ number_format($margem, 1, ',', '.') }}%
                </span>
            </div>
            @endif
            @if($freteEntrega !== null && $custoAquisicao === 0.0)
            <p class="text-[9px] text-gray-400 mt-2">Cadastre o custo de compra para ver margem.</p>
            @endif
        </div>
        @endif
    </div>
</div>

{{-- Descrição --}}
@if($produto->descricao_curta || $produto->descricao)
<div class="border border-[var(--color-lab-border)] bg-white p-5 mb-4">
    @if($produto->descricao_curta)
        <p class="font-mono text-[10px] uppercase tracking-widest text-gray-400 mb-2">Descrição curta</p>
        <p class="font-mono text-sm text-gray-700 mb-4">{{ $produto->descricao_curta }}</p>
    @endif
    @if($produto->descricao)
        <p class="font-mono text-[10px] uppercase tracking-widest text-gray-400 mb-3">Descrição completa</p>
        <div class="product-description-content text-sm text-gray-700 leading-relaxed">
            {!! $produto->descricao !!}
        </div>
    @endif
</div>
@endif

{{-- Specs e Tags --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
    @if(!empty($produto->specs))
    <div class="border border-[var(--color-lab-border)] bg-white p-5">
        <p class="font-mono text-[10px] uppercase tracking-widest text-gray-400 mb-3">Especificações</p>
        <table class="w-full font-mono text-xs">
            @foreach($produto->specs as $chave => $valor)
            <tr class="border-b border-[var(--color-lab-border)] last:border-0">
                <td class="py-2 pr-4 text-gray-500 w-1/2">{{ $chave }}</td>
                <td class="py-2 text-black">{{ $valor }}</td>
            </tr>
            @endforeach
        </table>
    </div>
    @endif

    @if(!empty($produto->tags))
    <div class="border border-[var(--color-lab-border)] bg-white p-5">
        <p class="font-mono text-[10px] uppercase tracking-widest text-gray-400 mb-3">Tags</p>
        <div class="flex flex-wrap gap-2">
            @foreach($produto->tags as $tag)
            <span class="px-3 py-1 border border-[var(--color-lab-border)] font-mono text-xs text-black">{{ $tag }}</span>
            @endforeach
        </div>
    </div>
    @endif
</div>

{{-- Variantes --}}
@if($produto->variantes->isNotEmpty())
<div class="border border-[var(--color-lab-border)] bg-white mb-4">
    <div class="px-5 py-3 border-b border-[var(--color-lab-border)] flex items-center justify-between">
        <p class="font-mono text-[10px] uppercase tracking-widest text-gray-400">Variantes ({{ $produto->variantes->count() }})</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full font-mono text-xs">
            <thead>
                <tr class="border-b border-[var(--color-lab-border)] bg-gray-50">
                    <th class="px-5 py-2 text-left text-[10px] uppercase tracking-widest text-gray-400 font-normal">SKU</th>
                    <th class="px-5 py-2 text-left text-[10px] uppercase tracking-widest text-gray-400 font-normal">Descrição</th>
                    <th class="px-5 py-2 text-right text-[10px] uppercase tracking-widest text-gray-400 font-normal">Preço</th>
                    <th class="px-5 py-2 text-right text-[10px] uppercase tracking-widest text-gray-400 font-normal">Estoque</th>
                    <th class="px-5 py-2 text-center text-[10px] uppercase tracking-widest text-gray-400 font-normal">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[var(--color-lab-border)]">
                @foreach($produto->variantes as $variante)
                <tr class="{{ $variante->ativo ? '' : 'opacity-50' }}">
                    <td class="px-5 py-3 text-black">{{ $variante->sku }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $variante->descricao ?: '—' }}</td>
                    <td class="px-5 py-3 text-right">
                        @if($variante->preco)
                            R$ {{ number_format($variante->preco, 2, ',', '.') }}
                        @else
                            <span class="text-gray-400">Padrão</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right {{ $variante->estoque <= 0 ? 'text-red-600' : 'text-black' }}">
                        {{ $variante->estoque }}
                    </td>
                    <td class="px-5 py-3 text-center">
                        @if($variante->ativo)
                            <span class="text-[10px] uppercase tracking-widest text-black">Ativo</span>
                        @else
                            <span class="text-[10px] uppercase tracking-widest text-gray-400">Inativo</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Opcao Grupos --}}
@if($produto->opcaoGrupos->isNotEmpty())
<div class="border border-[var(--color-lab-border)] bg-white mb-4 p-5">
    <p class="font-mono text-[10px] uppercase tracking-widest text-gray-400 mb-3">Opções</p>
    <div class="flex flex-wrap gap-6">
        @foreach($produto->opcaoGrupos as $grupo)
        <div>
            <p class="font-mono text-xs font-bold text-black mb-1">{{ $grupo->nome }}</p>
            <div class="flex flex-wrap gap-1">
                @foreach($grupo->opcaoValores as $valor)
                <span class="border border-[var(--color-lab-border)] px-2 py-0.5 font-mono text-xs">{{ $valor->valor }}</span>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Modal de edição (reutiliza o mesmo do admin) --}}
@include('admin.includes.modal-produto')

<style>
    .product-description-content p { margin-bottom: 0.75rem; }
    .product-description-content ul { list-style: disc; padding-left: 1.25rem; margin-bottom: 0.75rem; }
    .product-description-content strong { font-weight: 600; }
</style>

<script>
    // Abrir modal de edição ao carregar se vier parâmetro ?editar=1
    document.addEventListener('DOMContentLoaded', function () {
        const params = new URLSearchParams(window.location.search);
        if (params.get('editar') === '1') {
            editarProduto({{ $produto->id }});
        }
    });
</script>

@endsection
