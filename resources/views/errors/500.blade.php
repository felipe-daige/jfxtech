<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>500 - Erro Interno | JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
</head>
<body class="min-h-screen flex flex-col bg-black text-[var(--color-lab-ink)] antialiased">

    {{-- Cabeçalho mínimo sem queries ao banco --}}
    <div class="bg-black text-white text-[10px] font-mono py-1 px-4 flex justify-between items-center tracking-widest uppercase opacity-90">
        <span class="flex items-center gap-1">STATUS: ONLINE</span>
    </div>
    <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-[var(--color-lab-border)]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="/" class="flex items-center gap-2">
                    <img src="{{ asset('storage/images/jfxtech-logo-500x500-removebg-preview.png') }}" alt="JFXTECH" class="h-8 w-8 object-contain">
                    <span class="font-bold text-lg tracking-tight">JFXTECH</span>
                </a>
                <a href="/" class="text-sm font-medium text-gray-500 hover:text-black transition-colors uppercase tracking-wider">
                    Início
                </a>
            </div>
        </div>
    </header>

    <main class="flex-grow flex items-center justify-center bg-black min-h-[72vh]">
        <section class="relative w-full bg-black py-24 overflow-hidden">
            <div class="bg-tech-grid-dark pointer-events-none absolute inset-0 opacity-30"></div>
            <div class="relative z-10 container mx-auto px-4 text-center">

                <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">Erro HTTP</span>

                <p class="font-mono font-bold text-7xl md:text-8xl text-white mt-2 mb-4 tracking-tighter">
                    500
                </p>

                <h1 class="text-2xl md:text-3xl font-bold text-white uppercase tracking-wider mb-4">
                    Erro Interno do Servidor
                </h1>

                <p class="text-gray-400 text-sm max-w-md mx-auto mb-10 leading-relaxed">
                    Algo deu errado no servidor. Nossa equipe foi notificada. Tente novamente em alguns instantes.
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <button onclick="window.location.reload()"
                            class="bg-white text-black px-8 py-4 font-bold text-xs tracking-widest uppercase hover:bg-gray-200 transition-colors cursor-pointer">
                        TENTAR NOVAMENTE
                    </button>
                    <a href="/"
                       class="border border-white/30 text-white px-8 py-4 font-bold text-xs tracking-widest uppercase hover:bg-white/10 transition-colors">
                        VOLTAR AO INÍCIO
                    </a>
                </div>

            </div>
        </section>
    </main>

    <footer class="bg-white border-t border-[var(--color-lab-border)] py-8">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="font-mono text-[10px] uppercase tracking-widest text-gray-400">
                &copy; {{ date('Y') }} JFXTECH — Todos os direitos reservados
            </p>
        </div>
    </footer>

</body>
</html>
