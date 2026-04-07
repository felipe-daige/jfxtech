<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $article['title'] }} - JFXTECH</title>
    <meta name="description" content="{{ $article['description'] }}">

    <meta property="og:type" content="article">
    <meta property="og:site_name" content="JFXTECH">
    <meta property="og:title" content="{{ $article['title'] }} - JFXTECH">
    <meta property="og:description" content="{{ $article['description'] }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ url($article['image']) }}">

    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "Article",
        "headline": "{{ addslashes($article['title']) }}",
        "description": "{{ addslashes($article['description']) }}",
        "image": "{{ url($article['image']) }}",
        "datePublished": "{{ $article['date'] }}",
        "author": { "@@type": "Organization", "name": "JFXTech" },
        "publisher": {
            "@@type": "Organization",
            "name": "JFXTech",
            "url": "{{ url('/') }}"
        }
    }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
    <style>
        .prose { overflow-wrap: break-word; word-break: break-word; }
        .prose h2 { font-size: 1.5rem; font-weight: 700; margin: 2rem 0 0.75rem; line-height: 1.3; }
        .prose h3 { font-size: 1.25rem; font-weight: 600; margin: 1.5rem 0 0.5rem; line-height: 1.35; }
        .prose p { margin: 1rem 0; line-height: 1.75; }
        .prose ul, .prose ol { margin: 1rem 0 1rem 1.25rem; }
        .prose li { margin: 0.4rem 0; line-height: 1.65; }
        .prose strong { font-weight: 700; }
        @media (max-width: 640px) {
            .prose h2 { font-size: 1.25rem; margin: 1.5rem 0 0.6rem; }
            .prose h3 { font-size: 1.1rem; margin: 1.25rem 0 0.4rem; }
            .prose p, .prose li { font-size: 0.95rem; line-height: 1.7; }
        }
        /* Tabelas comparativas */
        .prose .table-scroll-wrapper { margin: 2rem 0; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.10); }
        .prose .table-badge { display: inline-block; background: #000; color: #fff; font-size: 0.65rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; padding: 0.25rem 0.65rem; border-radius: 4px 4px 0 0; margin-bottom: 0; }
        .prose .table-scroll-inner { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .prose table { width: 100%; border-collapse: collapse; margin: 0; font-size: 0.9rem; }
        .prose th { background: #000; color: #fff; font-weight: 700; font-size: 0.75rem; letter-spacing: 0.06em; text-transform: uppercase; padding: 0.75rem 1rem; text-align: left; white-space: nowrap; }
        .prose td { border-bottom: 1px solid #e5e7eb; padding: 0.65rem 1rem; text-align: left; vertical-align: top; }
        .prose tr:last-child td { border-bottom: none; }
        .prose tr:nth-child(even) td { background: #f9fafb; }
        .prose tr:hover td { background: #f3f4f6; transition: background 0.1s; }
        .prose td:first-child { font-weight: 600; color: #111; white-space: nowrap; }
        @media (max-width: 640px) {
            .prose table { font-size: 0.78rem; }
            .prose th, .prose td { padding: 0.5rem 0.65rem; }
            .prose td:first-child { white-space: normal; }
        }
        .prose code { background: #f3f4f6; padding: 0.1em 0.4em; border-radius: 3px; font-size: 0.875em; }
        .prose pre { background: #1f2937; color: #f9fafb; padding: 1.25rem; border-radius: 6px; overflow-x: auto; margin: 1.5rem 0; }
        .prose pre code { background: none; padding: 0; }
        .prose blockquote { border-left: 4px solid #e5e7eb; padding-left: 1rem; color: #6b7280; margin: 1.5rem 0; }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)] antialiased">
    @include('includes.header')

    <main class="flex-grow">
        <article class="max-w-3xl mx-auto px-4 py-8 md:py-12">
            {{-- Breadcrumb --}}
            <nav class="text-sm text-gray-400 mb-8 flex flex-wrap items-center gap-x-1 gap-y-1 leading-snug">
                <a href="{{ url('/') }}" class="hover:text-black whitespace-nowrap">Home</a>
                <span class="mx-1">/</span>
                <a href="{{ route('site.blog.index') }}" class="hover:text-black whitespace-nowrap">Blog</a>
                <span class="mx-1">/</span>
                <span class="text-black line-clamp-1 min-w-0">{{ $article['title'] }}</span>
            </nav>

            {{-- Tags --}}
            @if(!empty($article['tags']))
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach($article['tags'] as $tag)
                        <span class="text-xs bg-black text-white px-2 py-0.5 rounded">{{ $tag }}</span>
                    @endforeach
                </div>
            @endif

            <h1 class="text-3xl md:text-4xl font-bold leading-tight mb-4">{{ $article['title'] }}</h1>
            <p class="text-gray-400 text-sm mb-8">{{ \Carbon\Carbon::parse($article['date'])->format('d \d\e F \d\e Y') }}</p>

            {{-- Imagem de capa --}}
            <div class="aspect-video rounded-lg overflow-hidden bg-gray-100 mb-10">
                <img
                    src="{{ asset($article['image']) }}"
                    alt="{{ $article['title'] }}"
                    class="w-full h-full object-cover"
                >
            </div>

            {{-- Conteúdo --}}
            <div class="prose max-w-none">
                {!! $article['content'] !!}
            </div>

            {{-- Produto relacionado (opcional) --}}
            @if($featuredProduct)
                <div class="mt-12 border border-gray-200 rounded-lg p-6">
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-3">Produto relacionado</p>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                        @if($featuredProduct->imagemCapa)
                            <img
                                src="{{ asset('storage/' . $featuredProduct->imagemCapa->caminho) }}"
                                alt="{{ $featuredProduct->nome }}"
                                class="w-16 h-16 sm:w-20 sm:h-20 object-cover rounded border border-gray-100 flex-shrink-0"
                            >
                        @endif
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold leading-snug">{{ $featuredProduct->nome }}</h3>
                            <p class="text-sm text-gray-500 mt-0.5">R$ {{ number_format($featuredProduct->preco_com_desconto, 2, ',', '.') }}</p>
                        </div>
                        <a href="{{ route('site.produto.detalhes', $featuredProduct->slug) }}"
                           class="self-start sm:self-auto px-4 py-2 bg-black text-white text-sm rounded hover:bg-gray-800 transition-colors whitespace-nowrap">
                            Ver produto
                        </a>
                    </div>
                </div>
            @endif

            {{-- Voltar ao blog --}}
            <div class="mt-12 pt-8 border-t border-gray-100">
                <a href="{{ route('site.blog.index') }}" class="text-sm hover:underline">← Voltar ao Blog</a>
            </div>
        </article>
    </main>

    @include('includes.footer')

    <script>
    document.querySelectorAll('.prose table').forEach(function(table) {
        var wrapper = document.createElement('div');
        wrapper.className = 'table-scroll-wrapper';

        var badge = document.createElement('span');
        badge.className = 'table-badge';
        badge.textContent = 'Comparativo';

        var inner = document.createElement('div');
        inner.className = 'table-scroll-inner';

        table.parentNode.insertBefore(wrapper, table);
        inner.appendChild(table);
        wrapper.appendChild(badge);
        wrapper.appendChild(inner);
    });
    </script>
</body>
</html>
