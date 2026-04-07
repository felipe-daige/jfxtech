<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Meus Pedidos - JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">

    <!-- jQuery e jMask -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
</head>
<body class="min-h-screen flex flex-col bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)] antialiased">

    @include('includes.header')

    <main class="flex-grow">
        <!-- Meus Pedidos Section -->
        <section class="py-16">
            <div class="container mx-auto px-4 lg:px-8">
                <div class="max-w-6xl mx-auto">

                    <!-- Header -->
                    <div class="mb-12">
                        <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">Historico</span>
                        <h1 class="text-3xl font-bold uppercase tracking-wider text-black mt-1">Meus Pedidos</h1>
                        <p class="text-[var(--color-lab-muted)] text-sm mt-2">Acompanhe todos os seus pedidos</p>
                    </div>

                    <!-- Alertas -->
                    @if(session('success'))
                        <div class="border border-black bg-white px-4 py-3 mb-6">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span class="text-sm font-mono">{{ session('success') }}</span>
                            </div>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="border border-red-500 bg-white px-4 py-3 mb-6">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                <span class="text-sm font-mono">{{ session('error') }}</span>
                            </div>
                        </div>
                    @endif

                    <!-- Filtros -->
                    <div class="bg-white border border-[var(--color-lab-border)] p-6 mb-8">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0 md:space-x-6">
                            <div class="flex flex-col md:flex-row md:items-center space-y-4 md:space-y-0 md:space-x-4">
                                <div class="flex items-center space-x-3">
                                    <svg class="w-4 h-4 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                                    <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">Filtrar por:</span>
                                </div>

                                <select id="filtro_status" name="filtro_status" class="border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black transition-colors" aria-label="Filtrar por status">
                                    <option value="">Todos os pedidos</option>
                                    <option value="pendente">Pendente</option>
                                    <option value="processando">Processando</option>
                                    <option value="enviado">Enviado</option>
                                    <option value="entregue">Entregue</option>
                                    <option value="cancelado">Cancelado</option>
                                </select>
                            </div>

                            <div class="flex items-center space-x-3">
                                <span class="text-sm text-[var(--color-lab-muted)] font-mono">Total:</span>
                                <span class="border border-black px-3 py-1 text-[10px] font-mono font-bold uppercase">0</span>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de Pedidos -->
                    <div class="space-y-6">

                        <!-- Pedido Exemplo 1 -->
                        <div class="bg-white border border-[var(--color-lab-border)] overflow-hidden">
                            <div class="p-6">
                                <!-- Header do Pedido -->
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                                    <div class="flex items-center space-x-4 mb-4 md:mb-0">
                                        <div class="w-12 h-12 bg-black flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-mono font-bold text-black uppercase">Pedido #MX001</h3>
                                            <p class="text-xs text-[var(--color-lab-muted)] font-mono">Realizado em 15/01/2024</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center space-x-4">
                                        <span class="inline-flex items-center px-3 py-1 border border-[var(--color-lab-border)] text-[10px] font-mono uppercase tracking-widest text-[var(--color-lab-muted)]">
                                            Processando
                                        </span>
                                        <span class="text-xl font-mono font-bold text-black">R$ 299,90</span>
                                    </div>
                                </div>

                                <!-- Produtos do Pedido -->
                                <div class="border-t border-[var(--color-lab-border)] pt-6">
                                    <h4 class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-4">Produtos:</h4>
                                    <div class="space-y-3">
                                        <div class="flex items-center space-x-4 p-3 bg-[var(--color-lab-bg)]">
                                            <div class="w-12 h-12 bg-[var(--color-lab-border)] flex items-center justify-center">
                                                <svg class="w-5 h-5 text-[var(--color-lab-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                            </div>
                                            <div class="flex-1">
                                                <h5 class="font-bold text-sm text-black">Produto Exemplo 1</h5>
                                                <p class="text-xs text-[var(--color-lab-muted)] font-mono">Quantidade: 1</p>
                                            </div>
                                            <span class="font-mono font-bold text-black text-sm">R$ 199,90</span>
                                        </div>

                                        <div class="flex items-center space-x-4 p-3 bg-[var(--color-lab-bg)]">
                                            <div class="w-12 h-12 bg-[var(--color-lab-border)] flex items-center justify-center">
                                                <svg class="w-5 h-5 text-[var(--color-lab-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                            </div>
                                            <div class="flex-1">
                                                <h5 class="font-bold text-sm text-black">Produto Exemplo 2</h5>
                                                <p class="text-xs text-[var(--color-lab-muted)] font-mono">Quantidade: 1</p>
                                            </div>
                                            <span class="font-mono font-bold text-black text-sm">R$ 99,90</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Acoes do Pedido -->
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between mt-6 pt-6 border-t border-[var(--color-lab-border)]">
                                    <div class="flex items-center space-x-4 mb-4 md:mb-0">
                                        <button class="text-black hover:underline font-mono text-xs uppercase tracking-widest">
                                            <svg class="w-3 h-3 mr-1 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            Ver Detalhes
                                        </button>
                                        <button class="text-black hover:underline font-mono text-xs uppercase tracking-widest">
                                            <svg class="w-3 h-3 mr-1 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                                            Rastrear
                                        </button>
                                    </div>

                                    <div class="flex space-x-3">
                                        <button class="border border-[var(--color-lab-border)] text-black px-4 py-2 text-[10px] font-mono uppercase tracking-widest hover:bg-gray-50 transition-colors">
                                            <svg class="w-3 h-3 mr-1 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            Nota Fiscal
                                        </button>
                                        <button class="bg-black text-white px-4 py-2 text-[10px] font-mono uppercase tracking-widest hover:bg-gray-900 transition-colors">
                                            <svg class="w-3 h-3 mr-1 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
                                            Comprar Novamente
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pedido Exemplo 2 -->
                        <div class="bg-white border border-[var(--color-lab-border)] overflow-hidden">
                            <div class="p-6">
                                <!-- Header do Pedido -->
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                                    <div class="flex items-center space-x-4 mb-4 md:mb-0">
                                        <div class="w-12 h-12 bg-black flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-mono font-bold text-black uppercase">Pedido #MX002</h3>
                                            <p class="text-xs text-[var(--color-lab-muted)] font-mono">Realizado em 10/01/2024</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center space-x-4">
                                        <span class="inline-flex items-center px-3 py-1 border border-black text-[10px] font-mono uppercase tracking-widest text-black">
                                            Entregue
                                        </span>
                                        <span class="text-xl font-mono font-bold text-black">R$ 149,90</span>
                                    </div>
                                </div>

                                <!-- Produtos do Pedido -->
                                <div class="border-t border-[var(--color-lab-border)] pt-6">
                                    <h4 class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-4">Produtos:</h4>
                                    <div class="space-y-3">
                                        <div class="flex items-center space-x-4 p-3 bg-[var(--color-lab-bg)]">
                                            <div class="w-12 h-12 bg-[var(--color-lab-border)] flex items-center justify-center">
                                                <svg class="w-5 h-5 text-[var(--color-lab-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                            </div>
                                            <div class="flex-1">
                                                <h5 class="font-bold text-sm text-black">Produto Exemplo 3</h5>
                                                <p class="text-xs text-[var(--color-lab-muted)] font-mono">Quantidade: 1</p>
                                            </div>
                                            <span class="font-mono font-bold text-black text-sm">R$ 149,90</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Acoes do Pedido -->
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between mt-6 pt-6 border-t border-[var(--color-lab-border)]">
                                    <div class="flex items-center space-x-4 mb-4 md:mb-0">
                                        <button class="text-black hover:underline font-mono text-xs uppercase tracking-widest">
                                            <svg class="w-3 h-3 mr-1 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            Ver Detalhes
                                        </button>
                                        <button class="text-black hover:underline font-mono text-xs uppercase tracking-widest">
                                            <svg class="w-3 h-3 mr-1 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                            Avaliar
                                        </button>
                                    </div>

                                    <div class="flex space-x-3">
                                        <button class="border border-[var(--color-lab-border)] text-black px-4 py-2 text-[10px] font-mono uppercase tracking-widest hover:bg-gray-50 transition-colors">
                                            <svg class="w-3 h-3 mr-1 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            Nota Fiscal
                                        </button>
                                        <button class="bg-black text-white px-4 py-2 text-[10px] font-mono uppercase tracking-widest hover:bg-gray-900 transition-colors">
                                            <svg class="w-3 h-3 mr-1 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
                                            Comprar Novamente
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Estado Vazio (quando nao ha pedidos) -->
                        <div class="bg-white border border-[var(--color-lab-border)] p-12 text-center hidden" id="empty-state">
                            <div class="w-24 h-24 border border-[var(--color-lab-border)] flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-[var(--color-lab-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                            </div>
                            <h3 class="text-xl font-bold text-black mb-4">Nenhum pedido encontrado</h3>
                            <p class="text-[var(--color-lab-muted)] text-sm mb-8">Voce ainda nao fez nenhum pedido.</p>
                            <a href="{{ route('site.produtos') }}" class="inline-block bg-black text-white px-8 py-3 text-sm font-mono uppercase tracking-widest hover:bg-gray-900 transition-colors">
                                Ver Produtos
                            </a>
                        </div>

                    </div>

                    <!-- Paginacao -->
                    <div class="mt-12 flex justify-center">
                        <nav class="flex items-center space-x-1">
                            <button class="px-3 py-2 text-[var(--color-lab-muted)] border border-[var(--color-lab-border)] font-mono text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
                            </button>
                            <button class="px-4 py-2 bg-black text-white font-mono text-sm">1</button>
                            <button class="px-4 py-2 text-black border border-[var(--color-lab-border)] hover:bg-black hover:text-white font-mono text-sm transition-colors">2</button>
                            <button class="px-4 py-2 text-black border border-[var(--color-lab-border)] hover:bg-black hover:text-white font-mono text-sm transition-colors">3</button>
                            <button class="px-3 py-2 text-black border border-[var(--color-lab-border)] hover:bg-black hover:text-white font-mono text-sm transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                            </button>
                        </nav>
                    </div>

                </div>
            </div>
        </section>
    </main>

    @include('includes.footer')

    <!-- JavaScript para interacoes -->
    <script src="{{ asset('js/dropdowns.js') }}"></script>
    <script src="{{ asset('js/meus-pedidos.js') }}"></script>
</body>
</html>
