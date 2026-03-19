<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>JFXTECH - Hardware Gamer de Alta Performance</title>

    {{-- Open Graph (WhatsApp, Facebook) --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="JFXTECH">
    <meta property="og:title" content="JFXTECH - Hardware Gamer de Alta Performance">
    <meta property="og:description" content="Equipamentos de alta performance para motocross e hardware gamer. Entrega 24h, 100% original, suporte 24/7.">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:image" content="{{ url('storage/images/jfxtech-link-preiew-opt.jpg') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="JFXTECH - Hardware Gamer de Alta Performance">
    <meta name="twitter:description" content="Equipamentos de alta performance para motocross e hardware gamer. Entrega 24h, 100% original, suporte 24/7.">
    <meta name="twitter:image" content="{{ url('storage/images/jfxtech-link-preiew-opt.jpg') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}?v=2">
</head>
<body class="min-h-screen flex flex-col bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)] antialiased">
    @include('includes.header')

    <main class="flex-grow">
        {{-- ===== HERO CAROUSEL ===== --}}
        <section class="relative h-[70vh] min-h-[500px] w-full bg-black overflow-hidden group">
            {{-- Slide 1 --}}
            <div id="carousel-slide-1" class="carousel-slide active" style="background-image:url('{{ asset('carrossel/zowie_600hz_wallpaper_jfxtech_nomockup.png') }}');background-size:cover;background-position:center;">
                <div class="absolute inset-0 bg-tech-grid-dark pointer-events-none opacity-30"></div>
                <div class="absolute inset-0 grid grid-cols-3 items-end sm:items-center pb-16 sm:pb-0 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="col-span-1">
                        <div class="flex items-center gap-4 mb-4">
                            <span class="text-xs font-mono tracking-widest text-gray-400 uppercase border border-gray-700 px-2 py-1">LANÇAMENTO</span>
                            <div class="h-px bg-gray-700 flex-1 max-w-[100px]"></div>
                        </div>
                        <h1 class="text-2xl sm:text-5xl md:text-7xl font-bold tracking-tighter text-white mb-6 md:mb-2 uppercase">ZOWIE 600HZ</h1>
                        <p class="hidden sm:block text-lg md:text-xl text-gray-400 font-mono mb-8">MONITOR GAMING COM A MAIOR TAXA DE ATUALIZAÇÃO DO MERCADO</p>
                        <div class="hidden sm:flex gap-6 mb-10">
                            <div class="flex flex-col">
                                <span class="text-[10px] text-gray-500 font-mono uppercase tracking-widest mb-1">REFRESH</span>
                                <span class="text-sm font-bold text-white uppercase">600HZ</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[10px] text-gray-500 font-mono uppercase tracking-widest mb-1">TEMPO RESP.</span>
                                <span class="text-sm font-bold text-white uppercase">0.5MS GTG</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[10px] text-gray-500 font-mono uppercase tracking-widest mb-1">RESOLUÇÃO</span>
                                <span class="text-sm font-bold text-white uppercase">FULL HD 1080P</span>
                            </div>
                        </div>
                        <a href="{{ route('site.produtos') }}" class="inline-flex items-center gap-2 bg-white text-black px-8 py-4 font-bold tracking-widest text-sm hover:bg-gray-200 transition-colors group/btn">
                            VER MONITORES
                            <svg class="w-4 h-4 group-hover/btn:translate-x-1 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Slide 2 --}}
            <div id="carousel-slide-2" class="carousel-slide" style="background-image:url('{{ asset('carrossel/wooting_60he_wallpaper_jfxtech_nomockup.png') }}');background-size:cover;background-position:center;">
                <div class="absolute inset-0 bg-tech-grid-dark pointer-events-none opacity-30"></div>
                <div class="absolute inset-0 grid grid-cols-3 items-end sm:items-center pb-16 sm:pb-0 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="col-span-1">
                        <div class="flex items-center gap-4 mb-4">
                            <span class="text-xs font-mono tracking-widest text-gray-400 uppercase border border-gray-700 px-2 py-1">EXCLUSIVO</span>
                            <div class="h-px bg-gray-700 flex-1 max-w-[100px]"></div>
                        </div>
                        <h1 class="text-2xl sm:text-5xl md:text-7xl font-bold tracking-tighter text-white mb-6 md:mb-2 uppercase">WOOTING 60HE</h1>
                        <p class="hidden sm:block text-lg md:text-xl text-gray-400 font-mono mb-8">TECLADO HALL EFFECT COM RAPID TRIGGER AJUSTÁVEL</p>
                        <div class="hidden sm:flex gap-6 mb-10">
                            <div class="flex flex-col">
                                <span class="text-[10px] text-gray-500 font-mono uppercase tracking-widest mb-1">TECNOLOGIA</span>
                                <span class="text-sm font-bold text-white uppercase">HALL EFFECT</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[10px] text-gray-500 font-mono uppercase tracking-widest mb-1">RECURSO</span>
                                <span class="text-sm font-bold text-white uppercase">RAPID TRIGGER</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[10px] text-gray-500 font-mono uppercase tracking-widest mb-1">POLLING</span>
                                <span class="text-sm font-bold text-white uppercase">1000HZ</span>
                            </div>
                        </div>
                        <a href="{{ route('site.produtos') }}" class="inline-flex items-center gap-2 bg-white text-black px-8 py-4 font-bold tracking-widest text-sm hover:bg-gray-200 transition-colors group/btn">
                            VER TECLADOS
                            <svg class="w-4 h-4 group-hover/btn:translate-x-1 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Slide 3 --}}
            <div id="carousel-slide-3" class="carousel-slide" style="background-image:url('{{ asset('carrossel/mouses_wallpaper_jfxtech_nomockup.png') }}');background-size:cover;background-position:center;">
                <div class="absolute inset-0 bg-tech-grid-dark pointer-events-none opacity-30"></div>
                <div class="absolute inset-0 grid grid-cols-3 items-end sm:items-center pb-16 sm:pb-0 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="col-span-1">
                        <div class="flex items-center gap-4 mb-4">
                            <span class="text-xs font-mono tracking-widest text-gray-400 uppercase border border-gray-700 px-2 py-1">PROMOÇÃO</span>
                            <div class="h-px bg-gray-700 flex-1 max-w-[100px]"></div>
                        </div>
                        <h1 class="text-2xl sm:text-5xl md:text-7xl font-bold tracking-tighter text-white mb-6 md:mb-2 uppercase">MOUSES GAMING</h1>
                        <p class="hidden sm:block text-lg md:text-xl text-gray-400 font-mono mb-8">PRECISÃO ABSOLUTA COM OS MELHORES SENSORES DO MERCADO</p>
                        <div class="hidden sm:flex gap-6 mb-10">
                            <div class="flex flex-col">
                                <span class="text-[10px] text-gray-500 font-mono uppercase tracking-widest mb-1">DESCONTO</span>
                                <span class="text-sm font-bold text-white uppercase">ATÉ 40% OFF</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[10px] text-gray-500 font-mono uppercase tracking-widest mb-1">ESTOQUE</span>
                                <span class="text-sm font-bold text-white uppercase">LIMITADO</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[10px] text-gray-500 font-mono uppercase tracking-widest mb-1">ENVIO</span>
                                <span class="text-sm font-bold text-white uppercase">FRETE GRÁTIS</span>
                            </div>
                        </div>
                        <a href="{{ route('site.produtos') }}" class="inline-flex items-center gap-2 bg-white text-black px-8 py-4 font-bold tracking-widest text-sm hover:bg-gray-200 transition-colors group/btn">
                            VER MOUSES
                            <svg class="w-4 h-4 group-hover/btn:translate-x-1 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Mobile 4:5 wallpapers --}}
            <style>
                @media (max-width: 639px) {
                    #carousel-slide-1 { background-image: url('{{ asset('carrossel/monitor_ wallpaper_4x5.PNG') }}') !important; }
                    #carousel-slide-2 { background-image: url('{{ asset('carrossel/teclado_wallpaper_4x5.PNG') }}') !important; }
                    #carousel-slide-3 { background-image: url('{{ asset('carrossel/mouses_wallpaper_4x5.PNG') }}') !important; }
}
            </style>

            {{-- Carousel Controls --}}
            <div class="absolute bottom-8 right-8 flex items-center gap-4 z-10">
                <button id="carousel-play-pause" class="w-10 h-10 rounded-full border border-gray-600 flex items-center justify-center text-white hover:bg-white hover:text-black transition-colors">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
                </button>
                <div class="flex gap-2">
                    <button id="carousel-prev" class="w-10 h-10 border border-gray-600 flex items-center justify-center text-white hover:bg-white hover:text-black transition-colors">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                    <button id="carousel-next" class="w-10 h-10 border border-gray-600 flex items-center justify-center text-white hover:bg-white hover:text-black transition-colors">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                </div>
            </div>

            {{-- Progress Indicators --}}
            <div class="absolute bottom-8 left-8 flex gap-2 z-10">
                <button class="carousel-indicator h-1 w-12 bg-white transition-all duration-300"></button>
                <button class="carousel-indicator h-1 w-4 bg-gray-600 hover:bg-gray-400 transition-all duration-300"></button>
                <button class="carousel-indicator h-1 w-4 bg-gray-600 hover:bg-gray-400 transition-all duration-300"></button>
            </div>
        </section>

        {{-- ===== TRUST BAR ===== --}}
        <div class="bg-white border-b border-[var(--color-lab-border)] py-5">

            {{-- Mobile marquee (< sm): 4 itens duplicados para loop seamless --}}
            <div class="sm:hidden overflow-hidden">
                <div class="trust-marquee-track">
                    <span class="trust-marquee-item">
                        <svg class="w-3 h-3 flex-shrink-0 text-yellow-500 fill-yellow-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        ESPECIALISTAS EM GAMER
                    </span>
                    <span class="trust-marquee-item">
                        <svg class="w-3 h-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
                        5 ANOS DE GARANTIA
                    </span>
                    <span class="trust-marquee-item">
                        <svg class="w-3 h-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
                        FRETE GRÁTIS
                    </span>
                    <span class="trust-marquee-item">
                        <svg class="w-3 h-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
                        DEVOLUÇÃO EM 45 DIAS
                    </span>
                    {{-- Duplicatas para loop seamless --}}
                    <span class="trust-marquee-item">
                        <svg class="w-3 h-3 flex-shrink-0 text-yellow-500 fill-yellow-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        ESPECIALISTAS EM GAMER
                    </span>
                    <span class="trust-marquee-item">
                        <svg class="w-3 h-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
                        5 ANOS DE GARANTIA
                    </span>
                    <span class="trust-marquee-item">
                        <svg class="w-3 h-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
                        FRETE GRÁTIS
                    </span>
                    <span class="trust-marquee-item">
                        <svg class="w-3 h-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
                        DEVOLUÇÃO EM 45 DIAS
                    </span>
                </div>
            </div>

            {{-- Desktop (sm+): layout original --}}
            <div class="hidden sm:flex max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex-wrap justify-center sm:justify-between items-center gap-6 text-[9px] md:text-xs font-bold tracking-widest uppercase">
                <div class="hidden lg:flex items-center gap-2 flex-shrink-0">
                    <svg class="w-3 h-3 md:w-4 md:h-4 text-yellow-500 fill-yellow-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    <span>ESPECIALISTAS EM GAMER</span>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <svg class="w-3 h-3 md:w-4 md:h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
                    <span>5 ANOS DE GARANTIA</span>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <svg class="w-3 h-3 md:w-4 md:h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
                    <span>FRETE GRÁTIS</span>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <svg class="w-3 h-3 md:w-4 md:h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
                    <span>DEVOLUÇÃO EM 45 DIAS</span>
                </div>
            </div>

        </div>

        {{-- ===== FEATURED PRODUCTS ===== --}}
        <section class="py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16 reveal">
                    <h2 class="text-3xl md:text-4xl font-black tracking-tight uppercase mb-4">Escolha Sua Arma.</h2>
                    <p class="text-gray-600 font-mono text-sm">HARDWARE PREMIADO PARA TODOS OS TIPOS DE GAMERS.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @forelse($produtos_destaque as $produto)
                        <div class="reveal stagger-{{ $loop->iteration }}">
                            @include('components.product-card', ['produto' => $produto])
                        </div>
                    @empty
                        <div class="col-span-full text-center py-12">
                            <div class="border-2 border-dashed border-[var(--color-lab-border)] p-12">
                                <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                                <h3 class="text-lg font-bold mb-2">Nenhum produto em destaque</h3>
                                <p class="text-gray-500 text-sm font-mono mb-6">Ainda não há produtos em destaque disponíveis.</p>
                                <a href="{{ route('site.produtos') }}" class="inline-block bg-black text-white px-6 py-3 text-xs font-bold tracking-widest uppercase hover:bg-gray-800 transition-colors">
                                    Ver Todos os Produtos
                                </a>
                            </div>
                        </div>
                    @endforelse
                </div>

                <div class="mt-16 text-center reveal">
                    <a href="{{ route('site.produtos') }}" class="bg-black text-white px-10 py-4 font-bold tracking-widest text-sm hover:bg-gray-800 transition-colors uppercase inline-block">
                        Ver Todos os Produtos
                    </a>
                </div>
            </div>
        </section>

        {{-- ===== CATALOG PREVIEW ===== --}}
        <section class="bg-[var(--color-lab-bg)] py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                {{-- Header editorial: esquerda + direita --}}
                <div class="flex items-end justify-between border-b border-[var(--color-lab-border)] pb-8 mb-12 reveal">
                    <div>
                        <p class="text-xs font-mono text-[var(--color-lab-muted)] tracking-widest uppercase mb-2">Categorias</p>
                        <h2 class="text-3xl md:text-4xl font-black tracking-tight uppercase leading-none">Explore o Catálogo.</h2>
                    </div>
                    <a href="{{ route('site.produtos') }}"
                       class="inline-flex items-center gap-2 text-sm font-bold tracking-widest uppercase hover:text-gray-500 transition-colors shrink-0 mb-1">
                        Ver tudo
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </a>
                </div>

                @if($categorias_preview->isNotEmpty())
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 reveal">
                    @foreach($categorias_preview as $categoria)
                    @php
                        $imgs = $categoria->produtos
                            ->map(fn($p) => $p->imagens->first()?->caminho)
                            ->filter()
                            ->values();
                    @endphp
                    <a href="{{ route('site.produtos', ['categorias[]' => $categoria->id]) }}"
                       class="group flex flex-col border border-[var(--color-lab-border)] overflow-hidden hover:border-black transition-colors aspect-square">

                        {{-- Área da imagem (flex-1) --}}
                        <div class="flex-1 relative overflow-hidden bg-gray-100 min-h-0">
                            @if($imgs->count() >= 4)
                                <div class="absolute inset-0 grid grid-cols-2 grid-rows-2 gap-px bg-[var(--color-lab-border)]">
                                    @foreach($imgs->take(4) as $caminho)
                                    <div class="overflow-hidden bg-gray-100">
                                        <img src="{{ asset('storage/' . $caminho) }}"
                                             alt="{{ $categoria->nome }}"
                                             class="w-full h-full object-cover grayscale group-hover:scale-105 transition-transform duration-500">
                                    </div>
                                    @endforeach
                                </div>
                            @elseif($imgs->isNotEmpty())
                                <img src="{{ asset('storage/' . $imgs->first()) }}"
                                     alt="{{ $categoria->nome }}"
                                     class="absolute inset-0 w-full h-full object-cover grayscale group-hover:scale-105 transition-transform duration-500">
                            @endif
                        </div>

                        {{-- Faixa preta sólida — sempre legível --}}
                        <div class="bg-black px-4 py-3 shrink-0">
                            <p class="text-white font-black tracking-widest uppercase text-xs leading-none mb-1">{{ $categoria->nome }}</p>
                            <p class="text-white/50 font-mono text-xs">{{ $categoria->produtos_count }} {{ Str::plural('produto', $categoria->produtos_count) }}</p>
                        </div>
                    </a>
                    @endforeach

                    {{-- CTA card --}}
                    <a href="{{ route('site.produtos') }}"
                       class="group bg-black flex flex-col items-center justify-center gap-4 hover:bg-gray-900 transition-colors aspect-square border border-black">
                        <p class="text-white font-black tracking-widest uppercase text-sm text-center px-6 leading-snug">Ver Todo<br>o Catálogo</p>
                        <svg class="w-5 h-5 text-white/60 group-hover:translate-x-1 group-hover:text-white transition-all duration-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </a>
                </div>
                @endif
            </div>
        </section>

        {{-- ===== CTA SECTION ===== --}}
        <section class="py-24 md:py-32 bg-white text-center reveal">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-4xl md:text-6xl font-black tracking-tight mb-6 uppercase text-gray-900">
                    A Experiência Gamer Definitiva.
                </h2>
                <p class="text-lg md:text-xl text-gray-600 mb-10 max-w-2xl mx-auto">
                    Projetado do zero para fornecer desempenho inigualável. Descubra o hardware em que campeões e profissionais do mundo todo confiam.
                </p>
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <a href="{{ route('site.produtos') }}" class="bg-black text-white px-8 py-4 font-bold tracking-widest text-sm hover:bg-gray-800 transition-colors uppercase">
                        Comprar Todo o Hardware
                    </a>
                </div>
            </div>
        </section>

        {{-- ===== FEATURE BLOCKS (Alternating) ===== --}}
        <section class="bg-[var(--color-lab-bg)] py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                {{-- Block 1 --}}
                <div class="flex flex-col md:flex-row items-center gap-16 mb-32">
                    <div class="w-full md:w-1/2 order-2 md:order-1 reveal-left">
                        <h3 class="text-3xl md:text-4xl font-black tracking-tight mb-4 uppercase">Qualidade Profissional.</h3>
                        <p class="text-gray-600 text-lg mb-8 leading-relaxed">
                            Cada produto é selecionado com rigor técnico. Trabalhamos apenas com marcas que compartilham nossa obsessão por performance e durabilidade. Garantia estendida em todo o catálogo.
                        </p>
                        <a href="{{ route('site.produtos') }}" class="inline-flex items-center gap-2 text-sm font-bold tracking-widest uppercase hover:text-gray-500 transition-colors border-b-2 border-black pb-1">
                            EXPLORAR CATÁLOGO
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </a>
                    </div>
                    <div class="w-full md:w-1/2 order-1 md:order-2 reveal-right">
                        <div class="aspect-square bg-gray-100 border border-[var(--color-lab-border)] overflow-hidden">
                            <img src="{{ asset('qualidade_profissional_cleanup.png') }}" alt="Qualidade Profissional" class="w-full h-full object-cover">
                        </div>
                    </div>
                </div>

                {{-- Block 2 --}}
                <div class="flex flex-col md:flex-row items-center gap-16">
                    <div class="w-full md:w-1/2 reveal-left">
                        <div class="aspect-square bg-gray-100 border border-[var(--color-lab-border)] overflow-hidden">
                            <img src="{{ asset('entrega_zero_latencia_cleanup.png') }}" alt="Entrega com Zero Latência" class="w-full h-full object-cover">
                        </div>
                    </div>
                    <div class="w-full md:w-1/2 reveal-right">
                        <h3 class="text-3xl md:text-4xl font-black tracking-tight mb-4 uppercase">Entrega com Zero Latência.</h3>
                        <p class="text-gray-600 text-lg mb-8 leading-relaxed">
                            Logística otimizada para entregar seu hardware o mais rápido possível. Frete grátis para todo o Brasil em compras acima de R$ 299. Rastreamento em tempo real do seu pedido.
                        </p>
                        <a href="{{ route('site.contato') }}" class="inline-flex items-center gap-2 text-sm font-bold tracking-widest uppercase hover:text-gray-500 transition-colors border-b-2 border-black pb-1">
                            FALE CONOSCO
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===== SUPPORT BLOCK ===== --}}
        <section class="bg-[var(--color-lab-bg)] py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row items-center gap-16">
                    <div class="w-full md:w-1/2 order-2 md:order-1 reveal-left">
                        <h3 class="text-3xl md:text-4xl font-black tracking-tight mb-4 uppercase">Suporte que Acompanha Você.</h3>
                        <p class="text-gray-600 text-lg mb-8 leading-relaxed">
                            Nossa equipe técnica está disponível do pré ao pós-venda. Garantia estendida, troca sem burocracia e suporte real com quem entende de hardware.
                        </p>
                        <a href="{{ route('site.contato') }}" class="inline-flex items-center gap-2 text-sm font-bold tracking-widest uppercase hover:text-gray-500 transition-colors border-b-2 border-black pb-1">
                            FALAR COM SUPORTE
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </a>
                    </div>
                    <div class="w-full md:w-1/2 order-1 md:order-2 reveal-right">
                        <div class="aspect-square bg-gray-100 border border-[var(--color-lab-border)] overflow-hidden">
                            <img src="{{ asset('suporte_cleanup.png') }}" alt="Suporte que Acompanha Você" class="w-full h-full object-cover">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===== ESPORTS / SPECIAL EDITIONS ===== --}}
        <section class="py-24 bg-black text-white relative overflow-hidden">
            <div class="absolute inset-0 bg-tech-grid-dark pointer-events-none opacity-20"></div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
                <h2 class="text-3xl md:text-5xl font-black tracking-tight uppercase mb-6 reveal">JFXTECH Pro Series</h2>
                <p class="text-gray-400 text-lg max-w-2xl mx-auto mb-10 reveal">
                    Hardware de nível profissional para quem não aceita menos que o melhor. Construído com os mesmos padrões rigorosos exigidos pelos campeões mundiais.
                </p>
                <a href="{{ route('site.produtos') }}" class="border-2 border-white text-white px-8 py-4 font-bold tracking-widest text-sm hover:bg-white hover:text-black transition-colors uppercase inline-block reveal">
                    COMPRAR PRO SERIES
                </a>
            </div>
        </section>
    </main>

    @include('includes.footer')

    {{-- Carousel JS --}}
    <script src="{{ asset('js/carousel.js') }}"></script>
</body>
</html>
