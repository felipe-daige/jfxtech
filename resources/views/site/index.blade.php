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
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
</head>
<body class="min-h-screen flex flex-col bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)] antialiased">
    @include('includes.header')

    <main class="flex-grow">
        {{-- ===== HERO CAROUSEL ===== --}}
        <section class="relative h-[70vh] min-h-[500px] w-full bg-black overflow-hidden group">
            {{-- Slide 1 --}}
            <div class="carousel-slide active">
                <div class="absolute inset-0 bg-tech-grid-dark pointer-events-none opacity-30"></div>
                <div class="absolute inset-0 flex flex-col justify-center max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="max-w-2xl">
                        <div class="flex items-center gap-4 mb-4">
                            <span class="text-xs font-mono tracking-widest text-gray-400 uppercase border border-gray-700 px-2 py-1">LANÇAMENTO</span>
                            <div class="h-px bg-gray-700 flex-1 max-w-[100px]"></div>
                        </div>
                        <h1 class="text-5xl md:text-7xl font-bold tracking-tighter text-white mb-2 uppercase">NOVOS PRODUTOS</h1>
                        <p class="text-lg md:text-xl text-gray-400 font-mono mb-8">TECNOLOGIA DE ÚLTIMA GERAÇÃO DISPONÍVEL AGORA</p>
                        <div class="flex gap-6 mb-10">
                            <div class="flex flex-col">
                                <span class="text-[10px] text-gray-500 font-mono uppercase tracking-widest mb-1">ESPEC_01</span>
                                <span class="text-sm font-bold text-white uppercase">ENTREGA 24H</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[10px] text-gray-500 font-mono uppercase tracking-widest mb-1">ESPEC_02</span>
                                <span class="text-sm font-bold text-white uppercase">100% ORIGINAL</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[10px] text-gray-500 font-mono uppercase tracking-widest mb-1">ESPEC_03</span>
                                <span class="text-sm font-bold text-white uppercase">SUPORTE 24/7</span>
                            </div>
                        </div>
                        <a href="{{ route('site.produtos') }}" class="inline-flex items-center gap-2 bg-white text-black px-8 py-4 font-bold tracking-widest text-sm hover:bg-gray-200 transition-colors group/btn">
                            VER CATÁLOGO
                            <svg class="w-4 h-4 group-hover/btn:translate-x-1 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Slide 2 --}}
            <div class="carousel-slide">
                <div class="absolute inset-0 bg-tech-grid-dark pointer-events-none opacity-30"></div>
                <div class="absolute inset-0 flex flex-col justify-center max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="max-w-2xl">
                        <div class="flex items-center gap-4 mb-4">
                            <span class="text-xs font-mono tracking-widest text-gray-400 uppercase border border-gray-700 px-2 py-1">DESTAQUE</span>
                            <div class="h-px bg-gray-700 flex-1 max-w-[100px]"></div>
                        </div>
                        <h1 class="text-5xl md:text-7xl font-bold tracking-tighter text-white mb-2 uppercase whitespace-nowrap">PERFORMANCE EXTREMA</h1>
                        <p class="text-lg md:text-xl text-gray-400 font-mono mb-8">HARDWARE DE PRECISÃO PARA A ELITE COMPETITIVA</p>
                        <div class="flex gap-6 mb-10">
                            <div class="flex flex-col">
                                <span class="text-[10px] text-gray-500 font-mono uppercase tracking-widest mb-1">ESPEC_01</span>
                                <span class="text-sm font-bold text-white uppercase">500+ PRODUTOS</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[10px] text-gray-500 font-mono uppercase tracking-widest mb-1">ESPEC_02</span>
                                <span class="text-sm font-bold text-white uppercase">FRETE GRÁTIS</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[10px] text-gray-500 font-mono uppercase tracking-widest mb-1">ESPEC_03</span>
                                <span class="text-sm font-bold text-white uppercase">5 ANOS GARANTIA</span>
                            </div>
                        </div>
                        <a href="{{ route('site.produtos') }}" class="inline-flex items-center gap-2 bg-white text-black px-8 py-4 font-bold tracking-widest text-sm hover:bg-gray-200 transition-colors group/btn">
                            EXPLORAR HARDWARE
                            <svg class="w-4 h-4 group-hover/btn:translate-x-1 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Slide 3 --}}
            <div class="carousel-slide">
                <div class="absolute inset-0 bg-tech-grid-dark pointer-events-none opacity-30"></div>
                <div class="absolute inset-0 flex flex-col justify-center max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="max-w-2xl">
                        <div class="flex items-center gap-4 mb-4">
                            <span class="text-xs font-mono tracking-widest text-gray-400 uppercase border border-gray-700 px-2 py-1">PROMOÇÃO</span>
                            <div class="h-px bg-gray-700 flex-1 max-w-[100px]"></div>
                        </div>
                        <h1 class="text-5xl md:text-7xl font-bold tracking-tighter text-white mb-2 uppercase">OFERTAS ESPECIAIS</h1>
                        <p class="text-lg md:text-xl text-gray-400 font-mono mb-8">ATÉ 40% OFF EM HARDWARE SELECIONADO</p>
                        <div class="flex gap-6 mb-10">
                            <div class="flex flex-col">
                                <span class="text-[10px] text-gray-500 font-mono uppercase tracking-widest mb-1">ESPEC_01</span>
                                <span class="text-sm font-bold text-white uppercase">ATÉ 40% OFF</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[10px] text-gray-500 font-mono uppercase tracking-widest mb-1">ESPEC_02</span>
                                <span class="text-sm font-bold text-white uppercase">TEMPO LIMITADO</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[10px] text-gray-500 font-mono uppercase tracking-widest mb-1">ESPEC_03</span>
                                <span class="text-sm font-bold text-white uppercase">COMPRE AGORA</span>
                            </div>
                        </div>
                        <a href="{{ route('site.produtos') }}" class="inline-flex items-center gap-2 bg-white text-black px-8 py-4 font-bold tracking-widest text-sm hover:bg-gray-200 transition-colors group/btn">
                            VER OFERTAS
                            <svg class="w-4 h-4 group-hover/btn:translate-x-1 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                        </a>
                    </div>
                </div>
            </div>

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
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-wrap justify-center sm:justify-between items-center gap-6 text-[9px] md:text-xs font-bold tracking-widest uppercase">
                <div class="hidden lg:flex items-center gap-2 flex-shrink-0">
                    <svg class="w-3 h-3 md:w-4 md:h-4 text-yellow-500 fill-yellow-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    <span>3 MILHÕES DE USUÁRIOS</span>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <svg class="w-3 h-3 md:w-4 md:h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
                    <span>5 ANOS DE GARANTIA</span>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <svg class="w-3 h-3 md:w-4 md:h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
                    <span>FRETE GRÁTIS</span>
                </div>
                <div class="hidden sm:flex items-center gap-2 flex-shrink-0">
                    <svg class="w-3 h-3 md:w-4 md:h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
                    <span>DEVOLUÇÃO EM 45 DIAS</span>
                </div>
            </div>
        </div>

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
                        </div>
                    </div>
                </div>

                {{-- Block 2 --}}
                <div class="flex flex-col md:flex-row items-center gap-16">
                    <div class="w-full md:w-1/2 reveal-left">
                        <div class="aspect-square bg-gray-100 border border-[var(--color-lab-border)] overflow-hidden">
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
