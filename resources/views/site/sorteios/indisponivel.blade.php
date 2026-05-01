<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sorteio - JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
</head>
<body class="min-h-screen flex flex-col bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)] antialiased">
    @include('includes.header')

    <main class="flex-grow py-16">
        <div class="max-w-3xl mx-auto px-4">
            <div class="bg-white border border-[var(--color-lab-border)] p-8 text-center">
                <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-3">Sorteio JFXTECH</p>
                <h1 class="text-3xl font-bold tracking-tight mb-3">Nenhum sorteio ativo</h1>
                <p class="text-sm text-gray-500 max-w-xl mx-auto">
                    Assim que um novo sorteio estiver liberado, a inscricao aparecera nesta pagina.
                </p>
                <a href="{{ route('site.index') }}" class="inline-flex mt-8 bg-black text-white px-6 py-3 text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition-colors">
                    Voltar ao site
                </a>
            </div>
        </div>
    </main>

    @include('includes.footer')
</body>
</html>
