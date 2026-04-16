<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>403 - Acesso Negado | JFXTECH</title>
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
                    403
                </p>

                <h1 class="text-2xl md:text-3xl font-bold text-white uppercase tracking-wider mb-4">
                    Acesso Negado
                </h1>

                <p class="text-gray-400 text-sm max-w-md mx-auto mb-10 leading-relaxed">
                    Você não tem permissão para acessar esta página. Se acredita que isso é um erro, entre em contato conosco.
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="{{ route('site.index') }}"
                       class="bg-white text-black px-8 py-4 font-bold text-xs tracking-widest uppercase hover:bg-gray-200 transition-colors">
                        VOLTAR AO INÍCIO
                    </a>
                    <a href="{{ route('site.contato') }}"
                       class="border border-white/30 text-white px-8 py-4 font-bold text-xs tracking-widest uppercase hover:bg-white/10 transition-colors">
                        FALAR COM SUPORTE
                    </a>
                </div>

            </div>
        </section>
    </main>

    @include('includes.footer')

</body>
</html>
