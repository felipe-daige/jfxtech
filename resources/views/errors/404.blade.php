<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>404 - Página Não Encontrada | JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
</head>
<body class="min-h-screen flex flex-col bg-black text-[var(--color-lab-ink)] antialiased">

    @include('includes.header')
    @include('includes.banner-frete-gratis')

    <main class="flex-grow flex items-center justify-center bg-black min-h-[72vh]">
        <section class="relative w-full bg-black py-24 overflow-hidden">
            <div class="bg-tech-grid-dark pointer-events-none absolute inset-0 opacity-30"></div>
            <div class="relative z-10 container mx-auto px-4 text-center">

                <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">Erro HTTP</span>

                <p class="font-mono font-bold text-7xl md:text-8xl text-white mt-2 mb-4 tracking-tighter">
                    404
                </p>

                <h1 class="text-2xl md:text-3xl font-bold text-white tracking-wider mb-4">
                    PÁGINA NÃO ENCONTRADA
                </h1>

                <p class="text-gray-400 text-sm max-w-md mx-auto mb-10 leading-relaxed">
                    A página que você está procurando não existe ou foi movida. Verifique o endereço ou navegue pelo site.
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="{{ route('site.index') }}"
                       class="bg-white text-black px-8 py-4 font-bold text-xs tracking-widest uppercase hover:bg-gray-200 transition-colors">
                        VOLTAR AO INÍCIO
                    </a>
                    <a href="{{ route('site.produtos') }}"
                       class="border border-white/30 text-white px-8 py-4 font-bold text-xs tracking-widest uppercase hover:bg-white/10 transition-colors">
                        VER PRODUTOS
                    </a>
                </div>

            </div>
        </section>
    </main>

    @include('includes.footer')

</body>
</html>
