<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Painel Administrativo') - JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-maskmoney/3.0.2/jquery.maskMoney.min.js"></script>
    <script src="{{ asset('js/dropdowns.js') }}"></script>
    <script src="{{ asset('js/admin.js') }}?v{{time()}}"></script>
    <style>
        body {
            overflow-x: hidden;
        }

        .admin-shell-main {
            width: 100%;
            min-width: 0;
        }

        .admin-mobile-scroll {
            scrollbar-width: thin;
            -webkit-overflow-scrolling: touch;
        }

        @media (max-width: 380px) {
            .admin-compact-text {
                letter-spacing: 0.18em;
                font-size: 9px;
            }
        }

        @media (min-width: 1024px) and (max-width: 1439px) {
            .admin-shell-main {
                padding: 1.5rem;
            }
        }
    </style>
    <script>
        // Variáveis globais para o admin.js
        window.baseUrl = '{{ url("/") }}';
        window.routes = {
            adminProdutosCriar: '{{ route("admin.produtos.criar") }}',
            adminProdutosEditar: '{{ route("admin.produtos.editar", ":id") }}',
            adminProdutosQuickEdit: '{{ route("admin.produtos.quick-edit", ":id") }}',
            adminCategoriasCriar: '{{ route("admin.categorias.criar") }}',
            adminCategoriasEditar: '{{ route("admin.categorias.editar", ":id") }}',
            adminPedidosStatus: '{{ route("admin.pedidos.status", ":id") }}',
            adminPedidosDetalhes: '{{ route("admin.pedidos.detalhes", ":id") }}',
            adminPedidosQuickStatus: '{{ route("admin.pedidos.quick-status", ":id") }}',
        };
    </script>
</head>
<body class="bg-[var(--color-lab-bg)]">
    <!-- Header -->
    <header class="sticky top-0 z-30 bg-white/95 backdrop-blur border-b border-[var(--color-lab-border)]">
        <div class="container mx-auto px-4 py-3 sm:py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3 sm:space-x-4 min-w-0">
                    <!-- Menu hamburguer para mobile -->
                    <button id="menuToggle" class="lg:hidden text-black hover:text-gray-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                    </button>

                    <div class="flex items-center space-x-3 min-w-0">
                        <img src="{{ asset('storage/images/jfxtech-logo-500x500-removebg-preview.png') }}" alt="JFXTECH" class="h-7 w-7 object-contain">
                        <div class="min-w-0">
                            <h1 class="admin-compact-text text-xs sm:text-sm font-mono font-bold uppercase tracking-widest text-black truncate">Admin Panel</h1>
                            <p class="admin-compact-text text-[10px] font-mono uppercase tracking-widest text-[var(--color-lab-muted)] truncate">JFXTECH System</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center space-x-3 sm:space-x-4 lg:space-x-6 shrink-0">
                    <a href="{{ route('site.index') }}" class="flex items-center space-x-2 text-[var(--color-lab-muted)] hover:text-black transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        <span class="hidden sm:inline font-mono text-xs uppercase tracking-widest">Site</span>
                    </a>
                    <div class="flex items-center space-x-2">
                        <div class="w-6 h-6 lg:w-7 lg:h-7 bg-black flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <span class="font-mono text-xs uppercase tracking-widest text-black hidden sm:inline">{{ Auth::user() ? Auth::user()->name : 'Usuario' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="flex">
        <!-- Overlay para mobile -->
        <div id="sidebarOverlay" class="fixed inset-0 bg-black/30 backdrop-blur-sm z-40 lg:hidden hidden"></div>

        <!-- Sidebar -->
        <aside id="sidebar" class="fixed lg:sticky lg:top-[73px] inset-y-0 left-0 z-50 w-[82vw] max-w-60 lg:w-56 xl:w-60 bg-white border-r border-[var(--color-lab-border)] min-h-screen lg:h-[calc(100vh-73px)] transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out lg:block overflow-y-auto admin-mobile-scroll">
            <div class="flex items-center justify-between p-4 border-b border-[var(--color-lab-border)] lg:hidden">
                <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Navigation</span>
                <button id="closeSidebar" class="text-black hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            <nav class="p-4">
                <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-4 px-3">Menu</p>
                <ul class="space-y-1">
                    <li>
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-3 px-3 py-2.5 font-mono text-xs uppercase tracking-widest transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-black text-white' : 'text-[var(--color-lab-muted)] hover:bg-gray-100 hover:text-black' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.produtos') }}" class="flex items-center space-x-3 px-3 py-2.5 font-mono text-xs uppercase tracking-widest transition-colors {{ request()->routeIs('admin.produtos*') ? 'bg-black text-white' : 'text-[var(--color-lab-muted)] hover:bg-gray-100 hover:text-black' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                            <span>Produtos</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.pedidos') }}" class="flex items-center space-x-3 px-3 py-2.5 font-mono text-xs uppercase tracking-widest transition-colors {{ request()->routeIs('admin.pedidos*') ? 'bg-black text-white' : 'text-[var(--color-lab-muted)] hover:bg-gray-100 hover:text-black' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            <span>Pedidos</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.categorias') }}" class="flex items-center space-x-3 px-3 py-2.5 font-mono text-xs uppercase tracking-widest transition-colors {{ request()->routeIs('admin.categorias*') ? 'bg-black text-white' : 'text-[var(--color-lab-muted)] hover:bg-gray-100 hover:text-black' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"/><path d="M7 7h.01"/></svg>
                            <span>Categorias</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.afiliados.index') }}" class="flex items-center space-x-3 px-3 py-2.5 font-mono text-xs uppercase tracking-widest transition-colors {{ request()->routeIs('admin.afiliados*') ? 'bg-black text-white' : 'text-[var(--color-lab-muted)] hover:bg-gray-100 hover:text-black' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            <span>Afiliados</span>
                        </a>
                    </li>
                </ul>

                <div class="border-t border-[var(--color-lab-border)] mt-6 pt-4">
                    <a href="{{ route('site.index') }}" class="flex items-center space-x-3 px-3 py-2.5 font-mono text-xs uppercase tracking-widest text-[var(--color-lab-muted)] hover:bg-gray-100 hover:text-black transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" x2="3" y1="12" y2="12"/></svg>
                        <span>Voltar ao Site</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-shell-main flex-1 p-4 sm:p-6 lg:p-8">
            @if(session('success'))
                <div class="border border-black bg-white p-4 mb-6 flex items-start space-x-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 flex-shrink-0"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span class="font-mono text-sm text-black">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="border border-black bg-white p-4 mb-6 flex items-start space-x-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 flex-shrink-0"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                    <span class="font-mono text-sm text-black">{{ session('error') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="border border-black bg-white p-4 mb-6">
                    <ul class="space-y-1">
                        @foreach($errors->all() as $error)
                            <li class="flex items-start space-x-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 flex-shrink-0"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                                <span class="font-mono text-sm text-black">{{ $error }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script>
        // Controle do menu mobile
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const closeSidebar = document.getElementById('closeSidebar');

            // Funcao para abrir sidebar
            function openSidebar() {
                sidebar.classList.remove('-translate-x-full');
                sidebarOverlay.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }

            // Funcao para fechar sidebar
            function closeSidebarMenu() {
                sidebar.classList.add('-translate-x-full');
                sidebarOverlay.classList.add('hidden');
                document.body.style.overflow = '';
            }

            // Abrir sidebar
            if (menuToggle) {
                menuToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    openSidebar();
                });
            }

            // Fechar sidebar
            if (closeSidebar) {
                closeSidebar.addEventListener('click', function(e) {
                    e.preventDefault();
                    closeSidebarMenu();
                });
            }

            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function(e) {
                    e.preventDefault();
                    closeSidebarMenu();
                });
            }

            // Fechar sidebar ao clicar em um link (mobile)
            const sidebarLinks = sidebar.querySelectorAll('a');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth < 1024) {
                        setTimeout(() => {
                            closeSidebarMenu();
                        }, 100);
                    }
                });
            });

            // Fechar sidebar ao redimensionar para desktop
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 1024) {
                    sidebar.classList.remove('-translate-x-full');
                    sidebarOverlay.classList.add('hidden');
                    document.body.style.overflow = '';
                } else {
                    sidebar.classList.add('-translate-x-full');
                    sidebarOverlay.classList.add('hidden');
                    document.body.style.overflow = '';
                }
            });

            // Fechar sidebar com tecla ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && !sidebar.classList.contains('-translate-x-full')) {
                    closeSidebarMenu();
                }
            });
        });
    </script>
</body>
</html>
