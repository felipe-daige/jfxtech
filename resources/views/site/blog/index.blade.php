<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Blog - JFXTECH</title>
    <meta name="description" content="Guias, reviews e dicas de hardware gamer. Aprenda a montar seu PC, comparar produtos e escolher o melhor setup. JFXTech.">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="JFXTECH">
    <meta property="og:title" content="Blog - JFXTECH">
    <meta property="og:description" content="Guias, reviews e dicas de hardware gamer.">
    <meta property="og:url" content="{{ url('/blog') }}">
    <meta property="og:image" content="{{ url('storage/images/jfxtech-link-preiew-opt.jpg') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
</head>
<body class="min-h-screen flex flex-col bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)] antialiased">
    @include('includes.header')

    <main class="flex-grow">
        <div class="max-w-6xl mx-auto px-4 py-12">
            <h1 class="text-3xl font-bold mb-2">Blog</h1>
            <p class="text-gray-500 mb-10">Guias, reviews e dicas de hardware gamer</p>

            @if($articles->isEmpty())
                <p class="text-gray-400">Nenhum artigo publicado ainda.</p>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach($articles as $article)
                        <a href="{{ route('site.blog.show', $article['slug']) }}" class="group block border border-gray-200 rounded-lg overflow-hidden hover:border-black transition-colors">
                            <div class="aspect-video overflow-hidden bg-gray-100">
                                <img
                                    src="{{ asset($article['image']) }}"
                                    alt="{{ $article['title'] }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                    loading="lazy"
                                >
                            </div>
                            <div class="p-5">
                                @if(!empty($article['tags']))
                                    <div class="flex flex-wrap gap-1 mb-3">
                                        @foreach(array_slice($article['tags'], 0, 3) as $tag)
                                            <span class="text-xs bg-black text-white px-2 py-0.5 rounded">{{ $tag }}</span>
                                        @endforeach
                                    </div>
                                @endif
                                <h2 class="font-bold text-lg leading-snug mb-2 group-hover:underline">{{ $article['title'] }}</h2>
                                <p class="text-sm text-gray-500 line-clamp-2">{{ $article['description'] }}</p>
                                <p class="text-xs text-gray-400 mt-3">{{ \Carbon\Carbon::parse($article['date'])->format('d/m/Y') }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>

                {{-- Paginação simples --}}
                @if($total > $perPage)
                    <div class="flex justify-center gap-4 mt-12">
                        @if($page > 1)
                            <a href="{{ route('site.blog.index', ['page' => $page - 1]) }}" class="px-4 py-2 border border-black text-sm hover:bg-black hover:text-white transition-colors">← Anterior</a>
                        @endif
                        @if($page * $perPage < $total)
                            <a href="{{ route('site.blog.index', ['page' => $page + 1]) }}" class="px-4 py-2 border border-black text-sm hover:bg-black hover:text-white transition-colors">Próximo →</a>
                        @endif
                    </div>
                @endif
            @endif
        </div>
    </main>

    @include('includes.footer')
</body>
</html>
