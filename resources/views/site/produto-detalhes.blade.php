<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $produto->nome }} - JFXTECH</title>

    {{-- Open Graph (WhatsApp, Facebook) --}}
    <meta property="og:type" content="product">
    <meta property="og:site_name" content="JFXTECH">
    <meta property="og:title" content="{{ $produto->nome }} - JFXTECH">
    <meta property="og:description" content="{{ $produto->descricao_curta ?? Str::limit(strip_tags($produto->descricao), 160) }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $produto->imagemCapa ? url('storage/' . $produto->imagemCapa->caminho) : url('storage/images/jfxtech-link-preiew-opt.jpg') }}">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $produto->nome }} - JFXTECH">
    <meta name="twitter:description" content="{{ $produto->descricao_curta ?? Str::limit(strip_tags($produto->descricao), 160) }}">
    <meta name="twitter:image" content="{{ $produto->imagemCapa ? url('storage/' . $produto->imagemCapa->caminho) : url('storage/images/jfxtech-link-preiew-opt.jpg') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
</head>
<body class="min-h-screen flex flex-col bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)] antialiased">
    @include('includes.header')

    <main class="flex-grow">
        {{-- Breadcrumbs --}}
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <nav class="flex text-xs font-mono text-gray-500 uppercase tracking-widest">
                <a href="{{ route('site.index') }}" class="hover:text-black transition-colors">HOME</a>
                <svg class="w-4 h-4 mx-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                <a href="{{ route('site.produtos') }}" class="hover:text-black transition-colors">PRODUTOS</a>
                @if($produto->categoria)
                <svg class="w-4 h-4 mx-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                <a href="{{ route('site.produtos', ['categorias' => [$produto->categoria->id]]) }}" class="hover:text-black transition-colors">{{ strtoupper($produto->categoria->nome) }}</a>
                @endif
                <svg class="w-4 h-4 mx-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                <span class="text-black">{{ strtoupper($produto->nome) }}</span>
            </nav>
        </div>

        {{-- Product Hero --}}
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
                {{-- Image Gallery --}}
                <div class="space-y-4">
                    <div class="aspect-square bg-white border border-[var(--color-lab-border)] overflow-hidden flex items-center justify-center p-8 relative">
                        @if($produto->primeira_imagem)
                            <img id="main-image" src="{{ asset('storage/' . $produto->primeira_imagem) }}" alt="{{ $produto->nome }}" class="w-full h-full object-contain mix-blend-multiply">
                        @else
                            <div class="w-full h-full flex items-center justify-center">
                                <svg class="w-20 h-20 text-gray-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                            </div>
                        @endif

                        @if($produto->em_promocao && $produto->desconto_percentual > 0)
                            <div class="absolute top-4 right-4 bg-red-600 text-white text-[10px] font-mono px-2 py-1 uppercase tracking-widest">
                                -{{ number_format($produto->desconto_percentual, 0) }}% OFF
                            </div>
                        @endif
                    </div>

                    @if($produto->imagens->count() > 1)
                    <div class="grid grid-cols-4 gap-3">
                        @foreach($produto->imagens as $index => $imagem)
                        <button class="thumbnail-btn aspect-square bg-white border overflow-hidden p-2 transition-colors {{ $index === 0 ? 'border-black' : 'border-[var(--color-lab-border)] hover:border-black' }}"
                                data-image="{{ asset('storage/' . $imagem->caminho) }}">
                            <img src="{{ asset('storage/' . $imagem->caminho) }}" alt="{{ $produto->nome }} - {{ $index + 1 }}" class="w-full h-full object-contain mix-blend-multiply">
                        </button>
                        @endforeach
                    </div>
                    @endif
                </div>

                {{-- Product Info --}}
                <div class="flex flex-col justify-center">
                    @if($produto->categoria)
                    <div class="mb-2 inline-block bg-black text-white text-[10px] font-mono px-2 py-1 uppercase tracking-widest w-fit">
                        {{ $produto->categoria->nome }}
                    </div>
                    @endif

                    @if($produto->marca)
                        <p class="text-xs font-mono text-gray-500 uppercase tracking-widest mb-2">{{ $produto->marca }}</p>
                    @endif

                    <h1 class="text-4xl md:text-5xl font-bold tracking-tight mb-4">{{ $produto->nome }}</h1>

                    {{-- Price --}}
                    <div class="mb-6">
                        @if($produto->em_promocao && $produto->preco_original && $produto->preco_original > $produto->preco)
                            <span class="text-gray-400 text-lg line-through font-mono block mb-1">R$ {{ number_format($produto->preco_original, 2, ',', '.') }}</span>
                        @endif
                        <span class="text-3xl font-mono font-bold">R$ {{ number_format($produto->preco, 2, ',', '.') }}</span>
                        @if($produto->em_promocao && $produto->desconto_percentual > 0)
                            <span class="ml-2 text-sm font-mono text-red-600">(-{{ number_format($produto->desconto_percentual, 0) }}%)</span>
                        @endif
                    </div>

                    <p class="text-gray-600 mb-8 leading-relaxed">{{ $produto->descricao_curta ?? Str::limit(strip_tags($produto->descricao), 200) }}</p>

                    {{-- Variant Selector --}}
                    @if($produto->tem_variantes && $produto->variantesAtivas->count() > 0)
                    <div class="mb-8" id="variante-selector">
                        @foreach($produto->opcaoGrupos as $grupo)
                        <div class="mb-4">
                            <span class="text-xs font-mono font-bold uppercase tracking-widest text-gray-500">{{ $grupo->nome }}</span>
                            <div class="flex flex-wrap gap-2 mt-2">
                                @foreach($grupo->valores as $valor)
                                <button type="button"
                                        class="opcao-btn border border-[var(--color-lab-border)] px-4 py-2 text-sm font-mono hover:border-black transition-colors"
                                        data-grupo="{{ $grupo->id }}"
                                        data-valor="{{ $valor->id }}">
                                    {{ $valor->valor }}
                                </button>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Pass variant data as JSON for JS --}}
                    <script id="variantes-data" type="application/json">
                    @json($variantesData)
                    </script>
                    @endif

                    {{-- Stock Status --}}
                    <div class="flex items-center gap-2 mb-6 font-mono text-sm">
                        @if($produto->em_estoque)
                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                            <span class="text-green-700">EM ESTOQUE</span>
                            <span id="stock-units" class="text-gray-400">({{ $produto->estoque }} un.)</span>
                        @else
                            <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                            <span class="text-red-600">ESGOTADO</span>
                        @endif
                    </div>

                    {{-- Specs Grid --}}
                    <div class="grid grid-cols-2 gap-3 mb-8">
                        <div class="border border-[var(--color-lab-border)] bg-white p-4">
                            <p class="text-[10px] font-mono text-gray-500 mb-1">FRETE</p>
                            <p class="font-bold text-sm">GRÁTIS BRASIL</p>
                        </div>
                        <div class="border border-[var(--color-lab-border)] bg-white p-4">
                            <p class="text-[10px] font-mono text-gray-500 mb-1">GARANTIA</p>
                            <p class="font-bold text-sm">5 ANOS</p>
                        </div>
                        <div class="border border-[var(--color-lab-border)] bg-white p-4">
                            <p class="text-[10px] font-mono text-gray-500 mb-1">ATENDIMENTO</p>
                            <p class="font-bold text-sm">VIA WHATSAPP · 7 DIAS</p>
                        </div>
                        <div class="border border-[var(--color-lab-border)] bg-white p-4">
                            <p class="text-[10px] font-mono text-gray-500 mb-1">DEVOLUÇÃO</p>
                            <p class="font-bold text-sm">30 DIAS</p>
                        </div>
                    </div>

                    {{-- Quantity --}}
                    <div class="flex items-center gap-4 mb-6">
                        <span class="text-xs font-mono font-bold uppercase tracking-widest text-gray-500">QTD:</span>
                        <div class="flex items-center border border-[var(--color-lab-border)]">
                            <button type="button" id="decrease-qty" class="w-10 h-10 flex items-center justify-center hover:bg-gray-100 transition-colors text-gray-600">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/></svg>
                            </button>
                            <input type="number" id="quantity" value="1" min="1" max="{{ $produto->estoque }}" class="w-14 text-center border-x border-[var(--color-lab-border)] h-10 font-mono font-bold text-sm focus:outline-none">
                            <button type="button" id="increase-qty" class="w-10 h-10 flex items-center justify-center hover:bg-gray-100 transition-colors text-gray-600">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex gap-4">
                        @if($produto_no_carrinho)
                            <button class="flex-1 bg-gray-400 text-white py-4 px-8 font-bold tracking-widest uppercase text-sm cursor-not-allowed" disabled>
                                JÁ NO CARRINHO
                            </button>
                        @else
                            <button type="button"
                                    class="add-to-cart-btn flex-1 bg-black text-white py-4 px-8 font-bold tracking-widest hover:bg-gray-800 transition-colors uppercase text-sm
                                        {{ !$produto->em_estoque || ($produto->tem_variantes && $produto->variantesAtivas->count() > 0) ? 'opacity-50 cursor-not-allowed' : '' }}"
                                    data-produto-id="{{ $produto->id }}"
                                    data-variante-id=""
                                    {{ !$produto->em_estoque || ($produto->tem_variantes && $produto->variantesAtivas->count() > 0) ? 'disabled' : '' }}>
                                {{ ($produto->tem_variantes && $produto->variantesAtivas->count() > 0) ? 'SELECIONE AS OPÇÕES' : 'ADICIONAR AO CARRINHO' }}
                            </button>
                        @endif
                        <button id="toggle-favorite"
                                class="w-14 h-14 flex items-center justify-center border transition-colors {{ $favoritado ? 'bg-black text-white border-black' : 'border-black hover:bg-gray-50' }}"
                                data-produto-id="{{ $produto->id }}">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="{{ $favoritado ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        {{-- Description Section --}}
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="bg-white border border-[var(--color-lab-border)] p-8">
                <h3 class="font-mono text-xs font-bold uppercase tracking-widest mb-6 text-gray-500">DESCRIÇÃO DO PRODUTO</h3>
                <div class="text-gray-700 leading-relaxed text-lg prose max-w-none">{!! $produto->descricao !!}</div>
            </div>
        </section>

        {{-- Specs / Anatomy Section --}}
        <section class="bg-black text-white py-24 relative overflow-hidden">
            <div class="absolute inset-0 bg-tech-grid-dark pointer-events-none opacity-20"></div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold tracking-tight mb-4">ESPECIFICAÇÕES TÉCNICAS</h2>
                    <p class="text-gray-400 font-mono text-sm max-w-2xl mx-auto">CADA DETALHE PROJETADO PARA MÁXIMA PERFORMANCE. SEM COMPROMISSOS.</p>
                </div>

                @php
                    $specs_raw     = $produto->specs ?? [];
                    $specs_validas = array_filter($specs_raw, fn($v) => $v !== null && $v !== '');
                    $chaves        = array_keys($specs_validas);
                    $mid           = (int) ceil(count($chaves) / 2);
                    $esquerda      = array_slice($chaves, 0, $mid);
                    $direita       = array_slice($chaves, $mid);
                    $labels = [
                        'sensor'       => 'Sensor',      'dpi_maximo'   => 'DPI Máximo',
                        'switches'     => 'Switches',    'peso'         => 'Peso',
                        'conexao'      => 'Conexão',     'polling_rate' => 'Polling Rate',
                        'dimensoes'    => 'Dimensões',   'cabo'         => 'Cabo',
                        'iluminacao'   => 'Iluminação',  'garantia'     => 'Garantia',
                    ];
                @endphp

                @if(count($specs_validas) > 0)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-12 items-center">
                    {{-- Esquerda --}}
                    <div class="space-y-6">
                        @foreach($esquerda as $chave)
                        <div class="flex flex-col items-end text-right">
                            <p class="text-[10px] font-mono text-gray-500 uppercase tracking-widest mb-1">
                                {{ $labels[$chave] ?? str_replace('_', ' ', $chave) }}
                            </p>
                            <h3 class="font-bold text-base">{{ $specs_validas[$chave] }}</h3>
                        </div>
                        @endforeach
                    </div>
                    {{-- Centro — imagem --}}
                    <div class="relative aspect-square flex items-center justify-center">
                        <div class="absolute inset-0 bg-gradient-to-b from-transparent via-gray-900/50 to-transparent rounded-full blur-3xl"></div>
                        @if($produto->primeira_imagem)
                            <img src="{{ asset('storage/' . $produto->primeira_imagem) }}"
                                 alt="{{ $produto->nome }}"
                                 class="relative z-10 w-3/4 object-contain opacity-80 mix-blend-screen" loading="lazy">
                        @endif
                    </div>
                    {{-- Direita --}}
                    <div class="space-y-6">
                        @foreach($direita as $chave)
                        <div class="flex flex-col items-start text-left">
                            <p class="text-[10px] font-mono text-gray-500 uppercase tracking-widest mb-1">
                                {{ $labels[$chave] ?? str_replace('_', ' ', $chave) }}
                            </p>
                            <h3 class="font-bold text-base">{{ $specs_validas[$chave] }}</h3>
                        </div>
                        @endforeach
                    </div>
                </div>
                @else
                {{-- fallback: 4 bullets genéricos originais --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-12 items-center">
                    <div class="space-y-12">
                        <div class="flex flex-col items-end text-right">
                            <h3 class="font-bold text-lg mb-2">QUALIDADE PREMIUM</h3>
                            <p class="text-sm text-gray-400">Materiais de primeira linha selecionados para durabilidade máxima.</p>
                        </div>
                        <div class="flex flex-col items-end text-right">
                            <h3 class="font-bold text-lg mb-2">PRECISÃO ABSOLUTA</h3>
                            <p class="text-sm text-gray-400">Cada componente calibrado para desempenho de alto nível.</p>
                        </div>
                    </div>
                    <div class="relative aspect-square flex items-center justify-center">
                        <div class="absolute inset-0 bg-gradient-to-b from-transparent via-gray-900/50 to-transparent rounded-full blur-3xl"></div>
                        @if($produto->primeira_imagem)
                            <img src="{{ asset('storage/' . $produto->primeira_imagem) }}" alt="{{ $produto->nome }}" class="relative z-10 w-3/4 object-contain opacity-80 mix-blend-screen" loading="lazy">
                        @endif
                    </div>
                    <div class="space-y-12">
                        <div class="flex flex-col items-start text-left">
                            <h3 class="font-bold text-lg mb-2">ALTA PERFORMANCE</h3>
                            <p class="text-sm text-gray-400">Projetado para entregar resultados superiores em todas as condições.</p>
                        </div>
                        <div class="flex flex-col items-start text-left">
                            <h3 class="font-bold text-lg mb-2">GARANTIA ESTENDIDA</h3>
                            <p class="text-sm text-gray-400">5 anos de garantia completa para sua total tranquilidade.</p>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </section>

        {{-- Related Products --}}
        @if($produtos_relacionados->count() > 0)
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold tracking-tight mb-4 uppercase">PRODUTOS RELACIONADOS</h2>
                <p class="text-gray-500 font-mono text-sm">HARDWARE QUE COMPLEMENTA SUA ESCOLHA.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($produtos_relacionados as $produto_rel)
                    @include('components.product-card', ['produto' => $produto_rel])
                @endforeach
            </div>
        </section>
        @endif
    </main>

    @include('includes.footer')

    <script src="{{ asset('js/produto-detalhes.js') }}"></script>
</body>
</html>
