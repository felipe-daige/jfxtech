<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Meus Pedidos - JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
</head>
<body class="min-h-screen flex flex-col bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)] antialiased">
    @include('includes.header')

    <main class="flex-grow">
    <!-- Hero Section -->
    <div class="bg-white border-b border-[var(--color-lab-border)] py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex text-xs font-mono text-gray-500 uppercase tracking-widest mb-6">
                <a href="{{ route('site.index') }}" class="hover:text-black transition-colors">HOME</a>
                <svg class="w-4 h-4 mx-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                <span class="text-black">MEUS PEDIDOS</span>
            </nav>
            <h1 class="text-4xl font-bold tracking-tight mb-2">MEUS PEDIDOS</h1>
            <p class="text-gray-500 font-mono text-sm">ACOMPANHE TODOS OS SEUS PEDIDOS E O STATUS DAS ENTREGAS</p>
        </div>
    </div>

    <!-- Pedidos Content -->
    <section class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-6xl mx-auto">
                @if($pedidos->count() > 0)
                    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                        @foreach($pedidos as $pedido)
                        <div class="bg-white border {{ $pedido->pago ? 'border-green-400 bg-green-50' : 'border-[var(--color-lab-border)] hover:border-black' }} transition-colors p-6">
                            <!-- Header do Pedido -->
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="font-mono text-sm font-bold">Pedido #{{ $pedido->id }}</h3>
                                    <p class="text-sm text-gray-600">{{ $pedido->created_at->format('d/m/Y H:i') }}</p>
                                </div>
                                <span class="px-2 py-1 text-[10px] font-mono font-bold uppercase tracking-widest
                                    @if($pedido->status == 'pago') bg-green-100 text-green-800
                                    @elseif($pedido->status == 'pendente') bg-yellow-100 text-yellow-800
                                    @elseif($pedido->status == 'processando') bg-blue-100 text-blue-800
                                    @elseif($pedido->status == 'enviado') bg-purple-100 text-purple-800
                                    @elseif($pedido->status == 'entregue') bg-green-100 text-green-800
                                    @elseif($pedido->status == 'cancelado') bg-red-100 text-red-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ ucfirst($pedido->status) }}
                                </span>
                            </div>

                            <!-- Itens do Pedido -->
                            <div class="mb-4">
                                <h4 class="text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500 mb-2">Itens:</h4>
                                <div class="space-y-2">
                                    @foreach($pedido->itens as $item)
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-gray-200 rounded-lg flex items-center justify-center flex-shrink-0">
                                            @if($item->produto->primeira_imagem)
                                                <img src="/storage/{{ $item->produto->primeira_imagem }}" alt="{{ $item->produto->nome }}" class="w-full h-full object-cover rounded-lg">
                                            @else
                                                <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                                            @endif
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">{{ $item->produto->nome }}</p>
                                            <p class="text-xs text-gray-600">Qtd: {{ $item->quantidade }}</p>
                                        </div>
                                        <p class="text-sm font-mono font-bold">R$ {{ number_format($item->preco, 2, ',', '.') }}</p>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Total do Pedido -->
                            <div class="border-t pt-4">
                                <div class="flex justify-between items-center">
                                    <span class="font-mono text-sm font-bold">Total:</span>
                                    <span class="text-xl font-mono font-bold">R$ {{ number_format($pedido->valor_total, 2, ',', '.') }}</span>
                                </div>
                            </div>

                            <!-- Rastreio -->
                            @if($pedido->codigo_rastreio)
                            <div class="border-t pt-3 mt-3">
                                <p class="text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500 mb-1">Rastreamento</p>
                                <p class="font-mono text-sm text-gray-900">{{ $pedido->codigo_rastreio }}</p>
                                <a href="https://rastreamento.correios.com.br/app/index.php?objetos={{ $pedido->codigo_rastreio }}"
                                   target="_blank"
                                   class="inline-flex items-center font-mono text-xs text-black underline hover:no-underline mt-1">
                                    Rastrear nos Correios &#8599;
                                </a>
                            </div>
                            @endif

                            <!-- Botão Ver Detalhes -->
                            <div class="mt-4">
                                <a href="{{ route('site.pedidos.show', $pedido->id) }}"
                                   class="w-full bg-black text-white py-3 px-6 font-bold tracking-widest uppercase text-sm hover:bg-gray-900 transition-colors text-center block">
                                    <svg class="w-4 h-4 mr-2 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>Ver Detalhes
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <!-- Estado Vazio -->
                    <div class="text-center py-16">
                        <div class="w-24 h-24 bg-[var(--color-lab-bg)] border border-[var(--color-lab-border)] flex items-center justify-center mx-auto mb-6">
                            <svg class="w-8 h-8 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Nenhum pedido encontrado</h3>
                        <p class="text-gray-600 mb-8">Você ainda não fez nenhum pedido. Que tal começar a comprar?</p>
                        <a href="{{ route('site.produtos') }}"
                           class="inline-flex items-center bg-black text-white py-3 px-6 font-bold tracking-widest uppercase text-sm hover:bg-gray-900 transition-colors">
                            <svg class="w-4 h-4 mr-3 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                            Começar a Comprar
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </section>
    </main>

    @include('includes.footer')
</body>
</html>
