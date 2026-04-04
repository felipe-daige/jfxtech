<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Catálogo - JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
</head>
<body class="min-h-screen flex flex-col bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)] antialiased">
    @include('includes.header')

    <main class="flex-grow">
        {{-- Page Header --}}
        <div class="bg-white border-b border-[var(--color-lab-border)] py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <nav class="flex text-xs font-mono text-gray-500 uppercase tracking-widest mb-6">
                    <a href="{{ route('site.index') }}" class="hover:text-black transition-colors">HOME</a>
                    <svg class="w-4 h-4 mx-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                    <span class="text-black">PRODUTOS</span>
                </nav>
                <h1 class="text-4xl font-bold tracking-tight mb-2">CATÁLOGO DE HARDWARE</h1>
                <p class="text-gray-500 font-mono text-sm">NAVEGUE PELO NOSSO ARSENAL COMPLETO DE EQUIPAMENTOS DE ALTA PERFORMANCE</p>
            </div>
        </div>

        {{-- Main Content --}}
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="flex flex-col lg:flex-row gap-12">

                {{-- Sidebar Filters --}}
                <aside class="w-full lg:w-64 flex-shrink-0 filter-sidebar">
                    <div class="sticky top-24">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="font-mono text-xs font-bold uppercase tracking-widest">FILTROS</h3>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('site.produtos') }}" class="text-xs font-mono text-gray-500 hover:text-black transition-colors uppercase tracking-wider" id="limpar-filtros">LIMPAR</a>
                                <button id="mobileFilterClose" class="lg:hidden p-1 text-gray-500 hover:text-black transition-colors" aria-label="Fechar filtros">
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                </button>
                            </div>
                        </div>

                        <form id="filtros-form" method="GET">
                            {{-- Search --}}
                            <div class="filter-section">
                                <div class="filter-section-header" data-filter-toggle>
                                    BUSCA
                                    <svg class="filter-chevron w-4 h-4 rotated" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                </div>
                                <div class="filter-section-content open">
                                    <div class="relative">
                                        <input type="text" name="busca" value="{{ request('busca') }}" placeholder="Buscar produto..."
                                               class="w-full px-3 py-2 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black transition-colors bg-white">
                                        <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                                    </div>
                                </div>
                            </div>

                            {{-- Categories --}}
                            <div class="filter-section">
                                <div class="filter-section-header" data-filter-toggle>
                                    CATEGORIA
                                    <svg class="filter-chevron w-4 h-4 rotated" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                </div>
                                <div class="filter-section-content open">
                                    <div class="space-y-2">
                                        @foreach($categorias as $categoria)
                                        <label class="flex items-center gap-3 cursor-pointer group text-sm">
                                            <input type="checkbox" name="categorias[]" value="{{ $categoria->id }}"
                                                   class="tech-checkbox categoria-checkbox"
                                                   {{ in_array($categoria->id, (array) request('categorias', [])) ? 'checked' : '' }}>
                                            <span class="text-gray-600 group-hover:text-black transition-colors">{{ $categoria->nome }}</span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            {{-- Price Range --}}
                            <div class="filter-section">
                                <div class="filter-section-header" data-filter-toggle>
                                    PREÇO
                                    <svg class="filter-chevron w-4 h-4 rotated" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                </div>
                                <div class="filter-section-content open">
                                    <div class="flex items-center gap-2">
                                        <input type="number" name="preco_min" value="{{ request('preco_min') }}" placeholder="Min"
                                               class="w-full px-3 py-2 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black transition-colors bg-white">
                                        <span class="text-gray-400 font-mono text-xs">—</span>
                                        <input type="number" name="preco_max" value="{{ request('preco_max') }}" placeholder="Max"
                                               class="w-full px-3 py-2 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black transition-colors bg-white">
                                    </div>
                                </div>
                            </div>

                            {{-- Filtros Rápidos --}}
                            <div class="filter-section">
                                <div class="filter-section-header" data-filter-toggle>
                                    FILTROS RÁPIDOS
                                    <svg class="filter-chevron w-4 h-4 rotated" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                </div>
                                <div class="filter-section-content open">
                                    <div class="space-y-2">
                                        <label class="flex items-center gap-3 cursor-pointer group text-sm">
                                            <input type="checkbox" name="em_promocao" value="1"
                                                   class="tech-checkbox"
                                                   {{ request('em_promocao') ? 'checked' : '' }}>
                                            <span class="text-gray-600 group-hover:text-black transition-colors">EM PROMOÇÃO</span>
                                        </label>
                                        <label class="flex items-center gap-3 cursor-pointer group text-sm">
                                            <input type="checkbox" name="em_estoque" value="1"
                                                   class="tech-checkbox"
                                                   {{ request('em_estoque') ? 'checked' : '' }}>
                                            <span class="text-gray-600 group-hover:text-black transition-colors">EM ESTOQUE</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- Sort --}}
                            <div class="filter-section">
                                <div class="filter-section-header" data-filter-toggle>
                                    ORDENAR
                                    <svg class="filter-chevron w-4 h-4 rotated" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                </div>
                                <div class="filter-section-content open">
                                    <select name="ordenacao" class="w-full px-3 py-2 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black transition-colors bg-white appearance-none cursor-pointer">
                                        <option value="nome" {{ request('ordenacao') == 'nome' ? 'selected' : '' }}>NOME A-Z</option>
                                        <option value="preco_asc" {{ request('ordenacao') == 'preco_asc' ? 'selected' : '' }}>PREÇO (MENOR-MAIOR)</option>
                                        <option value="preco_desc" {{ request('ordenacao') == 'preco_desc' ? 'selected' : '' }}>PREÇO (MAIOR-MENOR)</option>
                                        <option value="destaque" {{ request('ordenacao') == 'destaque' ? 'selected' : '' }}>EM DESTAQUE</option>
                                        <option value="novidade" {{ request('ordenacao') == 'novidade' ? 'selected' : '' }}>MAIS NOVOS</option>
                                        <option value="promocao" {{ request('ordenacao') == 'promocao' ? 'selected' : '' }}>EM PROMOÇÃO</option>
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>
                </aside>

                <div id="filter-overlay" class="fixed inset-0 bg-black/50 z-40 invisible opacity-0 transition-all duration-300 lg:hidden"></div>

                {{-- Product Grid Area --}}
                <div class="flex-1">
                    {{-- Mobile Filter Toggle --}}
                    <div class="lg:hidden mb-6">
                        <button id="mobileFilterToggle" class="flex items-center gap-2 px-4 py-2 border border-[var(--color-lab-border)] text-sm font-mono font-bold uppercase tracking-wider hover:bg-black hover:text-white transition-colors">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="21" x2="14" y1="4" y2="4"/><line x1="10" x2="3" y1="4" y2="4"/><line x1="21" x2="12" y1="12" y2="12"/><line x1="8" x2="3" y1="12" y2="12"/><line x1="21" x2="16" y1="20" y2="20"/><line x1="12" x2="3" y1="20" y2="20"/><line x1="14" x2="14" y1="2" y2="6"/><line x1="8" x2="8" y1="10" y2="14"/><line x1="16" x2="16" y1="18" y2="22"/></svg>
                            FILTROS
                        </button>
                    </div>

                    {{-- Results Header --}}
                    <div class="flex justify-between items-end border-b border-[var(--color-lab-border)] pb-4 mb-8">
                        <div>
                            <h2 class="text-xl font-bold tracking-tight">TODOS OS PRODUTOS</h2>
                            <p class="text-sm text-gray-500 font-mono mt-1">MOSTRANDO {{ $produtos->total() }} RESULTADO{{ $produtos->total() != 1 ? 'S' : '' }}</p>
                        </div>
                    </div>

                    {{-- Product Grid --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                        @forelse($produtos as $produto)
                            @include('components.product-card', ['produto' => $produto])
                        @empty
                            <div class="col-span-full text-center py-16">
                                <div class="border-2 border-dashed border-[var(--color-lab-border)] p-12">
                                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                                    <h3 class="text-lg font-bold mb-2">NENHUM PRODUTO ENCONTRADO</h3>
                                    <p class="text-gray-500 text-sm font-mono mb-6">Tente ajustar os filtros ou buscar por outros termos.</p>
                                    <a href="{{ route('site.produtos') }}" class="inline-block bg-black text-white px-6 py-3 text-xs font-bold tracking-widest uppercase hover:bg-gray-800 transition-colors">
                                        LIMPAR FILTROS
                                    </a>
                                </div>
                            </div>
                        @endforelse
                    </div>

                    {{-- Pagination --}}
                    @if($produtos->hasPages())
                    <div class="border-t border-[var(--color-lab-border)] pt-6">
                        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                            <p class="text-xs text-gray-500 font-mono uppercase tracking-wider">
                                {{ $produtos->firstItem() }}-{{ $produtos->lastItem() }} DE {{ $produtos->total() }} PRODUTOS
                            </p>
                            <div class="flex items-center gap-1">
                                @if($produtos->onFirstPage())
                                    <span class="w-10 h-10 flex items-center justify-center border border-[var(--color-lab-border)] text-gray-300 cursor-not-allowed">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                                    </span>
                                @else
                                    <a href="{{ $produtos->previousPageUrl() }}" class="w-10 h-10 flex items-center justify-center border border-[var(--color-lab-border)] hover:bg-black hover:text-white hover:border-black transition-colors">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                                    </a>
                                @endif

                                @foreach($produtos->getUrlRange(1, $produtos->lastPage()) as $page => $url)
                                    @if($page == $produtos->currentPage())
                                        <span class="w-10 h-10 flex items-center justify-center bg-black text-white text-sm font-mono font-bold">{{ $page }}</span>
                                    @else
                                        <a href="{{ $url }}" class="w-10 h-10 flex items-center justify-center border border-[var(--color-lab-border)] text-sm font-mono hover:bg-black hover:text-white hover:border-black transition-colors">{{ $page }}</a>
                                    @endif
                                @endforeach

                                @if($produtos->hasMorePages())
                                    <a href="{{ $produtos->nextPageUrl() }}" class="w-10 h-10 flex items-center justify-center border border-[var(--color-lab-border)] hover:bg-black hover:text-white hover:border-black transition-colors">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                    </a>
                                @else
                                    <span class="w-10 h-10 flex items-center justify-center border border-[var(--color-lab-border)] text-gray-300 cursor-not-allowed">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </section>
    </main>

    @include('includes.footer')

    <script src="{{ asset('js/produtos.js') }}?v={{ filemtime(public_path('js/produtos.js')) }}"></script>
    <script src="{{ asset('js/favoritos-produtos.js') }}"></script>
    <script src="{{ asset('js/filters.js') }}"></script>
</body>
</html>
