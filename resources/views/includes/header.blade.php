{{-- JFXTECH - Top Bar --}}
<div class="bg-black text-white text-[10px] font-mono py-1 px-4 flex justify-between items-center tracking-widest uppercase opacity-90">
    <span class="flex items-center gap-1">STATUS: ONLINE <span class="relative flex h-2 w-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span></span></span>
    <div class="flex gap-4 items-center">
        <span id="jfx-date">---</span>
        <span class="hidden sm:inline" id="jfx-tz">---</span>
        <span class="hidden sm:inline" id="jfx-clock">--:--:--</span>
    </div>
</div>

{{-- JFXTECH - Main Navigation --}}
<header class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-[var(--color-lab-border)]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            {{-- Logo --}}
            <a href="{{ route('site.index') }}" class="flex items-center gap-2 group">
                <img src="{{ asset('storage/images/jfxtech-logo-500x500-removebg-preview.png') }}" alt="JFXTECH" class="h-8 w-8 object-contain">
                <span class="font-bold text-lg tracking-tight">JFXTECH</span>
            </a>

            {{-- Desktop Nav --}}
            <nav class="hidden md:flex space-x-8 items-center h-full">
                <a href="{{ route('site.index') }}" class="text-sm font-medium transition-colors {{ request()->routeIs('site.index') ? 'text-black' : 'text-gray-500 hover:text-black' }}">INÍCIO</a>
                
                {{-- Dropdown Produtos --}}
                <div class="relative group h-full flex items-center">
                    <a href="{{ route('site.produtos') }}" class="text-sm font-medium transition-colors flex items-center gap-1 {{ request()->routeIs('site.produtos') ? 'text-black' : 'text-gray-500 hover:text-black' }}">
                        PRODUTOS
                        <svg class="w-3 h-3 transition-transform group-hover:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </a>
                    
                    {{-- Wrapper de Categorias (Megamenu / Dropdown) --}}
                    <div class="absolute left-0 top-full w-56 bg-white border border-[var(--color-lab-border)] shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 transform origin-top-left -translate-y-2 group-hover:translate-y-0">
                        <div class="p-2">
                            <a href="{{ route('site.produtos') }}" class="block px-4 py-2 text-xs font-bold font-mono tracking-widest uppercase text-black hover:bg-gray-50 transition-colors border-b border-[var(--color-lab-border)] mb-2">
                                TODOS OS PRODUTOS
                            </a>
                            <div class="space-y-1">
                                @php
                                    $headerCategorias = \App\Models\Categoria::orderBy('nome')->get();
                                @endphp
                                @foreach($headerCategorias as $categoria)
                                    <a href="{{ route('site.produtos', ['categorias[]' => $categoria->id]) }}" class="block px-4 py-2 text-xs font-mono tracking-wider uppercase text-gray-500 hover:text-black hover:bg-gray-50 transition-colors rounded-sm">
                                        {{ $categoria->nome }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <a href="{{ route('site.contato') }}" class="text-sm font-medium transition-colors {{ request()->routeIs('site.contato') ? 'text-black' : 'text-gray-500 hover:text-black' }}">CONTATO</a>
                <a href="{{ route('site.blog.index') }}" class="text-sm font-medium transition-colors {{ request()->routeIs('site.blog.*') ? 'text-black' : 'text-gray-500 hover:text-black' }}">BLOG</a>
                @auth
                    <a href="{{ route('site.pedidos.index') }}" class="text-sm font-medium transition-colors {{ request()->routeIs('site.pedidos.index') ? 'text-black' : 'text-gray-500 hover:text-black' }}">PEDIDOS</a>
                @endauth
            </nav>

            {{-- Actions --}}
            <div class="flex items-center space-x-3">
                {{-- Cart Button --}}
                <div class="cart-dropdown relative">
                    <button class="cart-dropdown-trigger p-2 text-gray-500 hover:text-black transition-colors relative" aria-label="Carrinho de compras">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                        <span class="absolute -top-0.5 -right-0.5 bg-black text-white text-[9px] font-mono font-bold w-4 h-4 flex items-center justify-center" aria-label="0 itens no carrinho">0</span>
                    </button>
                </div>

                @auth
                    {{-- User Dropdown --}}
                    <div class="user-dropdown relative">
                        <button class="user-dropdown-trigger flex items-center space-x-2 text-gray-500 hover:text-black transition-colors">
                            <div class="relative">
                                @if(Auth::user()->admin)
                                    <div class="w-8 h-8 bg-black text-white flex items-center justify-center text-xs font-mono font-bold">
                                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                    </div>
                                @else
                                    <div class="w-8 h-8 border-2 border-gray-300 flex items-center justify-center text-xs font-mono font-bold text-gray-700">
                                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            <span class="hidden lg:inline text-sm font-medium">{{ Auth::user()->name }}</span>
                            <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                        </button>

                        {{-- Dropdown Menu --}}
                        <div class="user-dropdown-menu absolute right-0 mt-2 w-52 bg-white border border-[var(--color-lab-border)] shadow-lg opacity-0 invisible transition-all duration-200 z-50">
                            {{-- User name for mobile --}}
                            <div class="px-4 py-3 border-b border-[var(--color-lab-border)] lg:hidden">
                                <p class="text-xs font-mono font-bold uppercase tracking-widest text-gray-500">CONTA</p>
                                <p class="text-sm font-bold mt-1">{{ Auth::user()->name }}</p>
                            </div>

                            <div class="py-1">
                                <a href="{{ route('site.perfil') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-black transition-colors">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    Meu Perfil
                                </a>
                                @if(Auth::user()->coupon_portal_enabled && Auth::user()->cupons()->exists())
                                    <a href="{{ route('site.cupom') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-black transition-colors">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H2v7l6.29 6.29c.94.94 2.48.94 3.42 0l3.58-3.58c.94-.94.94-2.48 0-3.42L9 5Z"/><path d="M6 9.01V9"/><path d="m15 5 6.3 6.3a2.4 2.4 0 0 1 0 3.4L17 19"/></svg>
                                        Meu Cupom
                                    </a>
                                @endif
                                <a href="{{ route('site.pedidos.index') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-black transition-colors">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                                    Meus Pedidos
                                </a>
                                <a href="{{ route('site.favoritos') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-black transition-colors">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                                    Favoritos
                                </a>

                                @if(Auth::user()->admin)
                                    <div class="border-t border-[var(--color-lab-border)] my-1"></div>
                                    <div class="px-4 py-2">
                                        <p class="text-[10px] font-mono font-bold uppercase tracking-widest text-gray-400">ADMIN</p>
                                    </div>
                                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-black transition-colors">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                                        Dashboard
                                    </a>
                                    <a href="{{ route('admin.produtos') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-black transition-colors">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                                        Produtos
                                    </a>
                                    <a href="{{ route('admin.pedidos') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-black transition-colors">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/><rect width="20" height="14" x="2" y="6" rx="2"/></svg>
                                        Pedidos
                                    </a>
                                    <a href="{{ route('admin.categorias') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-black transition-colors">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"/><path d="M7 7h.01"/></svg>
                                        Categorias
                                    </a>
                                @endif

                                <div class="border-t border-[var(--color-lab-border)] my-1"></div>
                                <form method="POST" action="{{ route('site.logout') }}">
                                    @csrf
                                    <button type="submit" class="flex items-center gap-2 w-full text-left px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                                        Sair
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    <a href="{{ route('site.login') }}" class="bg-black text-white px-5 py-2 text-xs font-bold tracking-widest hover:bg-gray-800 transition-colors uppercase">
                        LOGIN
                    </a>
                @endauth

                {{-- Mobile Menu Toggle --}}
                <button id="mobile-menu-toggle" class="md:hidden p-2 text-gray-500 hover:text-black transition-colors" aria-label="Menu mobile">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Mobile Menu Dropdown --}}
    <div id="mobile-menu" class="md:hidden absolute top-full left-0 right-0 bg-white border-b border-[var(--color-lab-border)] shadow-lg opacity-0 invisible transition-all duration-200 z-40">
        <div class="max-w-7xl mx-auto px-4 py-4 space-y-1">
            <a href="{{ route('site.index') }}" class="block px-4 py-3 text-sm font-medium tracking-wider uppercase transition-colors {{ request()->routeIs('site.index') ? 'text-black bg-gray-50' : 'text-gray-500 hover:text-black hover:bg-gray-50' }}">
                Início
            </a>
            <div class="border-y border-[var(--color-lab-border)] my-2 py-2">
                <a href="{{ route('site.produtos') }}" class="flex justify-between items-center px-4 py-2 text-sm font-medium tracking-wider uppercase transition-colors {{ request()->routeIs('site.produtos') ? 'text-black' : 'text-gray-500 hover:text-black' }}">
                    <span>Produtos</span>
                    <span class="text-[10px] font-mono tracking-widest bg-black text-white px-2 py-1 uppercase">Ver Todos</span>
                </a>
                <div class="px-4 mt-2 space-y-1 border-l-2 border-[var(--color-lab-border)] ml-4">
                    @php
                        $mobileCategorias = \App\Models\Categoria::orderBy('nome')->get();
                    @endphp
                    @foreach($mobileCategorias as $categoria)
                        <a href="{{ route('site.produtos', ['categorias[]' => $categoria->id]) }}" class="block py-2 text-xs font-mono tracking-wider uppercase text-gray-500 hover:text-black transition-colors">
                            {{ $categoria->nome }}
                        </a>
                    @endforeach
                </div>
            </div>
            <a href="{{ route('site.contato') }}" class="block px-4 py-3 text-sm font-medium tracking-wider uppercase transition-colors {{ request()->routeIs('site.contato') ? 'text-black bg-gray-50' : 'text-gray-500 hover:text-black hover:bg-gray-50' }}">
                Contato
            </a>
            <a href="{{ route('site.blog.index') }}" class="block px-4 py-3 text-sm font-medium tracking-wider uppercase transition-colors {{ request()->routeIs('site.blog.*') ? 'text-black bg-gray-50' : 'text-gray-500 hover:text-black hover:bg-gray-50' }}">
                Blog
            </a>
            @auth
                <a href="{{ route('site.pedidos.index') }}" class="block px-4 py-3 text-sm font-medium tracking-wider uppercase transition-colors {{ request()->routeIs('site.pedidos.index') ? 'text-black bg-gray-50' : 'text-gray-500 hover:text-black hover:bg-gray-50' }}">
                    Meus Pedidos
                </a>
                @if(Auth::user()->coupon_portal_enabled && Auth::user()->cupons()->exists())
                    <a href="{{ route('site.cupom') }}" class="block px-4 py-3 text-sm font-medium tracking-wider uppercase transition-colors {{ request()->routeIs('site.cupom') ? 'text-black bg-gray-50' : 'text-gray-500 hover:text-black hover:bg-gray-50' }}">
                        Meu Cupom
                    </a>
                @endif
            @endauth
        </div>
    </div>
</header>

{{-- Cart Sidebar --}}
<div id="cart-sidebar" class="fixed top-0 right-0 h-full w-full sm:w-96 bg-white shadow-2xl transform translate-x-full transition-transform duration-300 ease-in-out z-50 border-l border-[var(--color-lab-border)] flex flex-col">
    {{-- Cart Header --}}
    <div class="bg-black text-white p-5 flex-shrink-0">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center space-x-3">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                <div>
                    <h3 class="text-sm font-bold tracking-widest uppercase">MEU CARRINHO</h3>
                    <p class="text-white/60 text-[10px] font-mono cart-item-count">0 ITENS</p>
                </div>
            </div>
            <button id="close-cart" class="w-8 h-8 border border-white/20 hover:bg-white/10 flex items-center justify-center transition-colors">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        {{-- Progress bar --}}
        <div class="w-full bg-white/10 h-0.5">
            <div class="bg-white h-0.5 transition-all duration-500" style="width: 0%"></div>
        </div>
    </div>

    {{-- Cart Content --}}
    <div class="flex-1 overflow-y-auto bg-[var(--color-lab-bg)] cart-scroll cart-content">
        <div class="p-5">
            {{-- Empty Cart --}}
            <div class="text-center py-16">
                <div class="w-20 h-20 border-2 border-dashed border-gray-300 flex items-center justify-center mx-auto mb-6 cart-empty-icon">
                    <svg class="w-8 h-8 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                </div>
                <h4 class="font-bold text-lg mb-2">Carrinho Vazio</h4>
                <p class="text-gray-500 text-sm mb-8 max-w-xs mx-auto font-mono">
                    Nenhum item adicionado ainda.
                </p>
                <div class="space-y-3 max-w-xs mx-auto">
                    <a href="{{ route('site.produtos') }}" class="block w-full bg-black text-white py-3 px-6 text-xs font-bold tracking-widest uppercase hover:bg-gray-800 transition-colors text-center cart-button">
                        EXPLORAR PRODUTOS
                    </a>
                    @auth
                    <a href="{{ route('site.favoritos') }}" class="block w-full border-2 border-black text-black py-3 px-6 text-xs font-bold tracking-widest uppercase hover:bg-black hover:text-white transition-colors text-center cart-button">
                        VER FAVORITOS
                    </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>

    {{-- Cart Footer --}}
    <div class="border-t border-[var(--color-lab-border)] p-5 bg-white flex-shrink-0">
        <div class="space-y-2 mb-4">
            <div class="flex justify-between items-center text-sm text-gray-500 font-mono">
                <span>SUBTOTAL</span>
                <span class="cart-subtotal">R$ 0,00</span>
            </div>
            <div class="flex justify-between items-center text-sm text-gray-500 font-mono">
                <span>FRETE</span>
                <span class="font-bold text-black">GRÁTIS</span>
            </div>
            <div class="border-t border-[var(--color-lab-border)] pt-2">
                <div class="flex justify-between items-center font-bold">
                    <span class="text-sm font-mono uppercase">TOTAL</span>
                    <span class="text-lg cart-total">R$ 0,00</span>
                </div>
            </div>
        </div>
        <a href="{{ route('site.checkout') }}" class="block w-full bg-black text-white py-3.5 px-6 text-xs font-bold tracking-widest uppercase hover:bg-gray-800 transition-colors text-center" id="checkout-btn">
            FINALIZAR COMPRA
        </a>
        <div class="mt-3 flex justify-center gap-4 text-[10px] text-gray-400 font-mono uppercase tracking-wider">
            <span>COMPRA SEGURA</span>
            <span>FRETE GRÁTIS</span>
        </div>
    </div>
</div>

{{-- Cart Overlay --}}
<div id="cart-overlay" class="fixed inset-0 bg-black/50 z-40 opacity-0 invisible transition-all duration-300"></div>

{{-- Scripts --}}
<script src="{{ asset('js/dropdowns.js') }}?v={{ filemtime(public_path('js/dropdowns.js')) }}"></script>
<script src="{{ asset('js/cart.js') }}?v={{ filemtime(public_path('js/cart.js')) }}"></script>
<script src="{{ asset('js/topbar.js') }}?v=2"></script>
