<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>419 - Sessão Expirada | JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
    <script src="{{ asset('js/csrf-recovery.js') }}?v={{ filemtime(public_path('js/csrf-recovery.js')) }}"></script>
</head>
<body class="min-h-screen flex flex-col bg-black text-[var(--color-lab-ink)] antialiased">
    @php
        $returnUrl = session('csrf_return_to') ?? url()->previous() ?? url('/');
        if (! str_starts_with($returnUrl, url('/'))) {
            $returnUrl = url('/');
        }
    @endphp

    @include('includes.header')
    @include('includes.banner-frete-gratis')

    <main class="flex-grow flex items-center justify-center bg-black min-h-[72vh]">
        <section class="relative w-full bg-black py-24 overflow-hidden">
            <div class="bg-tech-grid-dark pointer-events-none absolute inset-0 opacity-30"></div>
            <div class="relative z-10 container mx-auto px-4 text-center">

                <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">Erro HTTP</span>

                <p class="font-mono font-bold text-7xl md:text-8xl text-white mt-2 mb-4 tracking-tighter">
                    419
                </p>

                <h1 class="text-2xl md:text-3xl font-bold text-white uppercase tracking-wider mb-4">
                    Sessão Expirada
                </h1>

                <p class="text-gray-400 text-sm max-w-md mx-auto mb-10 leading-relaxed">
                    Sua sessão expirou por inatividade. Vamos atualizar sua sessão e voltar para a página anterior.
                </p>

                <div class="flex flex-col sm:flex-row justify-center gap-3">
                    <button id="csrf-return-button"
                            class="bg-white text-black px-8 py-4 font-bold text-xs tracking-widest uppercase hover:bg-gray-200 transition-colors cursor-pointer">
                        VOLTAR PARA A PÁGINA
                    </button>
                    <button onclick="window.location.href='{{ url('/') }}'"
                            class="border border-white/30 text-white px-8 py-4 font-bold text-xs tracking-widest uppercase hover:bg-white/10 transition-colors cursor-pointer">
                        IR PARA O INÍCIO
                    </button>
                </div>

            </div>
        </section>
    </main>

    @include('includes.footer')

    <script>
        (function () {
            var fallbackUrl = @json($returnUrl);
            var target = fallbackUrl;

            if (window.JfxCsrfRecovery && typeof window.JfxCsrfRecovery.returnUrl === 'function') {
                target = window.JfxCsrfRecovery.returnUrl(fallbackUrl);
            }

            var button = document.getElementById('csrf-return-button');
            if (button) {
                button.addEventListener('click', function () {
                    window.location.replace(target);
                });
            }

            window.setTimeout(function () {
                window.location.replace(target);
            }, 1200);
        })();
    </script>
</body>
</html>
