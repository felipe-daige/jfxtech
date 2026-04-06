<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Detalhes do Pedido #{{ $pedido->id }} - JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
</head>
<body class="min-h-screen flex flex-col bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)] antialiased">
    @php
        $isGuestOrder = $pedido->user_id === null;
    @endphp
    @include('includes.header')

    <main class="flex-grow">
    <!-- Hero Section -->
    <div class="bg-white border-b border-[var(--color-lab-border)] py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex text-xs font-mono text-gray-500 uppercase tracking-widest mb-6">
                <a href="{{ route('site.index') }}" class="hover:text-black transition-colors">HOME</a>
                <svg class="w-4 h-4 mx-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                @if(!$isGuestOrder)
                <a href="{{ route('site.pedidos.index') }}" class="hover:text-black transition-colors">MEUS PEDIDOS</a>
                <svg class="w-4 h-4 mx-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                @endif
                <span class="text-black">PEDIDO #{{ $pedido->id }}</span>
            </nav>
            <h1 class="text-4xl font-bold tracking-tight mb-2">DETALHES DO PEDIDO #{{ $pedido->id }}</h1>
            <p class="text-gray-500 font-mono text-sm">ACOMPANHE TODOS OS DETALHES DO SEU PEDIDO</p>
        </div>
    </div>

    <!-- Detalhes do Pedido -->
    <section class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto">
                @if(session('success'))
                <div class="bg-white border border-black p-4 mb-6 text-sm font-mono uppercase tracking-widest">
                    {{ session('success') }}
                </div>
                @endif

                @if(session('error'))
                <div class="bg-white border border-red-300 p-4 mb-6 text-sm font-mono uppercase tracking-widest text-red-700">
                    {{ session('error') }}
                </div>
                @endif

                <!-- Status do Pedido -->
                <div class="bg-white border {{ $pedido->pago ? 'border-green-400 bg-green-50' : 'border-[var(--color-lab-border)]' }} p-6 mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">Status do Pedido</h2>
                            <p class="text-gray-600">Pedido realizado em {{ $pedido->created_at->format('d/m/Y H:i') }}</p>
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
                </div>

                @if($isGuestOrder && $pedido->status === 'pago')
                <div class="bg-white border border-[var(--color-lab-border)] p-6 mb-6">
                    <div class="max-w-2xl">
                        <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-gray-500 mb-3">Sua compra foi concluída</p>
                        <h2 class="text-2xl font-bold tracking-tight">Crie sua senha para acompanhar pedidos mais rápido</h2>
                        <p class="text-sm text-gray-600 mt-3">Sua compra já está confirmada. Se quiser, defina uma senha agora para transformar este pedido em uma conta e acessar seus próximos pedidos sem preencher tudo novamente.</p>
                    </div>

                    <form method="POST" action="{{ route('site.pedidos.create-account', $pedido) }}" class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        @csrf
                        <input type="hidden" name="guest_token" value="{{ $pedido->guest_token }}">
                        <div class="md:col-span-2">
                            <label class="block text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500 mb-2">E-mail da compra</label>
                            <input type="email" value="{{ $pedido->customer_email }}" readonly class="w-full px-4 py-3 border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] text-sm font-mono">
                        </div>
                        <div>
                            <label class="block text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500 mb-2">Crie sua senha</label>
                            <input type="password" name="password" required class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black transition-colors">
                            @error('password')
                                <p class="mt-2 text-xs text-red-600 font-mono">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500 mb-2">Confirme a senha</label>
                            <input type="password" name="password_confirmation" required class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black transition-colors">
                        </div>
                        <div class="md:col-span-2">
                            <button type="submit" class="inline-flex items-center justify-center bg-black text-white px-5 py-3 text-[10px] font-mono uppercase tracking-widest hover:bg-gray-900 transition-colors">
                                Criar conta com esta compra
                            </button>
                        </div>
                    </form>
                </div>
                @endif

                <!-- Dados do Cliente e Endereço -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Dados do Cliente -->
                    <div class="bg-white border border-[var(--color-lab-border)] p-6">
                        <h3 class="text-sm font-mono font-bold uppercase tracking-widest mb-4">
                            <svg class="w-5 h-5 mr-2 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>DADOS DO CLIENTE
                        </h3>
                        <div class="space-y-2">
                            <p class="text-gray-700"><span class="font-mono text-xs font-bold uppercase tracking-widest text-gray-500">Nome:</span> {{ $pedido->user->name ?? $pedido->customer_name }}</p>
                            <p class="text-gray-700"><span class="font-mono text-xs font-bold uppercase tracking-widest text-gray-500">E-mail:</span> {{ $pedido->user->email ?? $pedido->customer_email }}</p>
                            @if($pedido->user?->phone || $pedido->customer_phone)
                                <p class="text-gray-700"><span class="font-mono text-xs font-bold uppercase tracking-widest text-gray-500">Telefone:</span> {{ $pedido->user->phone ?? $pedido->customer_phone }}</p>
                            @endif
                        </div>
                    </div>

                    <!-- Endereço de Entrega -->
                    <div class="bg-white border border-[var(--color-lab-border)] p-6">
                        <h3 class="text-sm font-mono font-bold uppercase tracking-widest mb-4">
                            <svg class="w-5 h-5 mr-2 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>ENDEREÇO DE ENTREGA
                        </h3>
                        @if($pedido->endereco)
                            <div class="space-y-2">
                                <p class="text-gray-700"><span class="font-mono text-xs font-bold uppercase tracking-widest text-gray-500">CEP:</span> {{ $pedido->endereco->cep }}</p>
                                <p class="text-gray-700"><span class="font-mono text-xs font-bold uppercase tracking-widest text-gray-500">Rua:</span> {{ $pedido->endereco->rua }}</p>
                                <p class="text-gray-700"><span class="font-mono text-xs font-bold uppercase tracking-widest text-gray-500">Número:</span> {{ $pedido->endereco->numero }}</p>
                                @if($pedido->endereco->complemento)
                                    <p class="text-gray-700"><span class="font-mono text-xs font-bold uppercase tracking-widest text-gray-500">Complemento:</span> {{ $pedido->endereco->complemento }}</p>
                                @endif
                                <p class="text-gray-700"><span class="font-mono text-xs font-bold uppercase tracking-widest text-gray-500">Bairro:</span> {{ $pedido->endereco->bairro }}</p>
                                <p class="text-gray-700"><span class="font-mono text-xs font-bold uppercase tracking-widest text-gray-500">Cidade:</span> {{ $pedido->endereco->cidade }}</p>
                                <p class="text-gray-700"><span class="font-mono text-xs font-bold uppercase tracking-widest text-gray-500">Estado:</span> {{ $pedido->endereco->estado }}</p>
                                @if($pedido->endereco->pais)
                                    <p class="text-gray-700"><span class="font-mono text-xs font-bold uppercase tracking-widest text-gray-500">País:</span> {{ $pedido->endereco->pais }}</p>
                                @endif
                            </div>
                        @else
                            <p class="text-gray-500">Endereço não informado</p>
                        @endif
                    </div>
                </div>

                <!-- Itens do Pedido -->
                <div class="bg-white border border-[var(--color-lab-border)] p-6 mb-6">
                    <h3 class="text-sm font-mono font-bold uppercase tracking-widest mb-4">
                        <svg class="w-5 h-5 mr-2 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>ITENS DO PEDIDO
                    </h3>
                    <div class="space-y-4">
                        @foreach($pedido->itens as $item)
                        <div class="flex items-center justify-between p-4 bg-[var(--color-lab-bg)]">
                            <div class="flex items-center space-x-4">
                                <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center flex-shrink-0">
                                    @php
                                        $capa = $item->produto->imagens->firstWhere('capa', true) ?: $item->produto->imagens->first();
                                    @endphp
                                    @if($capa)
                                        <img src="{{ asset('storage/' . $capa->caminho) }}" alt="{{ $item->produto->nome }}" class="w-full h-full object-cover rounded-lg">
                                    @else
                                        <svg class="w-6 h-6 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                                    @endif
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900">{{ $item->produto->nome }}</h4>
                                    <p class="text-sm text-gray-600">Quantidade: {{ $item->quantidade }}</p>
                                    <p class="text-sm text-gray-600">Preço unitário: R$ {{ number_format($item->preco, 2, ',', '.') }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-mono font-bold">R$ {{ number_format($item->preco * $item->quantidade, 2, ',', '.') }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Total do Pedido -->
                    <div class="border-t border-gray-200 mt-6 pt-4">
                        <div class="flex justify-between items-center">
                            <span class="text-2xl font-bold text-gray-900">Total do Pedido:</span>
                            <span class="text-3xl font-mono font-bold">R$ {{ number_format($pedido->valor_total, 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Pagamento -->
                @if($pedido->pagamentos && $pedido->pagamentos->count())
                <div class="bg-white border border-[var(--color-lab-border)] p-6 mb-6">
                    <h3 class="text-sm font-mono font-bold uppercase tracking-widest mb-4">
                        <svg class="w-5 h-5 mr-2 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>INFORMAÇÕES DE PAGAMENTO
                    </h3>
                    <div class="space-y-2">
                        @foreach($pedido->pagamentos as $pagamento)
                        <div class="flex justify-between items-center p-3 bg-[var(--color-lab-bg)]">
                            <span class="font-mono text-xs font-bold uppercase tracking-widest text-gray-500">{{ ['pix' => 'Pix', 'cartao' => 'Cartão', 'boleto' => 'Boleto'][$pagamento->metodo] ?? ucfirst($pagamento->metodo ?? 'Desconhecido') }}</span>
                            <span class="px-2 py-1 text-[10px] font-mono font-bold uppercase tracking-widest
                                @if($pagamento->status == 'aprovado') bg-green-100 text-green-800
                                @elseif($pagamento->status == 'pendente') bg-yellow-100 text-yellow-800
                                @elseif($pagamento->status == 'rejeitado') bg-red-100 text-red-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ ucfirst($pagamento->status ?? 'Pendente') }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Rastreamento -->
                @if($pedido->codigo_rastreio)
                <div class="bg-white border border-[var(--color-lab-border)] p-6 mb-6">
                    <h3 class="text-sm font-mono font-bold uppercase tracking-widest mb-4">
                        <svg class="w-5 h-5 mr-2 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>RASTREAMENTO DO PEDIDO
                    </h3>
                    <div class="space-y-2">
                        <p class="text-gray-700">
                            <span class="font-mono text-xs font-bold uppercase tracking-widest text-gray-500">Código:</span>
                            <span class="font-mono font-bold">{{ $pedido->codigo_rastreio }}</span>
                        </p>
                        <a href="https://rastreamento.correios.com.br/app/index.php?objetos={{ $pedido->codigo_rastreio }}"
                           target="_blank"
                           class="inline-flex items-center bg-black text-white py-2 px-4 font-mono text-[10px] uppercase tracking-widest hover:bg-gray-900 transition-colors">
                            Rastrear nos Correios &#8599;
                        </a>
                    </div>
                </div>
                @endif

                <!-- Botões de Ação -->
                <div class="flex flex-col sm:flex-row gap-4">
                    @if(!$isGuestOrder)
                    <a href="{{ route('site.pedidos.index') }}"
                       class="flex-1 bg-white text-black py-3 px-6 font-bold tracking-widest uppercase text-sm border border-[var(--color-lab-border)] hover:border-black transition-colors text-center">
                        <svg class="w-4 h-4 mr-2 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>Voltar aos Pedidos
                    </a>
                    @endif
                    <a href="{{ route('site.produtos') }}"
                       class="flex-1 bg-black text-white py-3 px-6 font-bold tracking-widest uppercase text-sm hover:bg-gray-900 transition-colors text-center">
                        <svg class="w-4 h-4 mr-2 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>Continuar Comprando
                    </a>
                </div>
            </div>
        </div>
    </section>
    </main>

    @include('includes.footer')
</body>
</html>
