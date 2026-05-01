<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Acompanhar Sorteio - JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
</head>
<body class="min-h-screen flex flex-col bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)] antialiased">
    @include('includes.header')

    @php
        $resultadoPublicado = $sorteio->resultadoPublicado();
        $ganhador = $sorteio->ganhador;
        $foiGanhador = $resultadoPublicado && $ganhador && (int) $ganhador->id === (int) $participacao->id;
        $produtoSorteio = $sorteio->produto;
        $produtoImagemPrincipal = null;

        if ($produtoSorteio) {
            $produtoImagemPrincipal = $produtoSorteio->id === 120
                ? $produtoSorteio->imagens->firstWhere('caminho', 'produtos/ozDyKocZc4DpwuiI0Sj4xGd6w1KMtocExCVr5Tk6.png')
                : null;
            $produtoImagemPrincipal ??= $produtoSorteio->imagens->firstWhere('capa', true) ?: $produtoSorteio->imagens->first();
        }
        $instagramUsername = '@'.ltrim((string) $participacao->instagram_username, '@');
        $instagramFriend1 = '@'.ltrim((string) $participacao->instagram_friend_1, '@');
        $instagramFriend2 = '@'.ltrim((string) $participacao->instagram_friend_2, '@');
        $ganhadorInstagram = $ganhador ? '@'.ltrim((string) $ganhador->instagram_username, '@') : null;
    @endphp

    <main class="flex-grow py-6 sm:py-14">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 text-sm mb-5">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white border border-[var(--color-lab-border)] overflow-hidden">
                <div class="grid grid-cols-1 lg:grid-cols-[0.85fr_1.15fr]">
                    <section class="p-4 sm:p-8 border-b lg:border-b-0 lg:border-r border-[var(--color-lab-border)]">
                        <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-2">Minha participação</p>
                        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight leading-tight mb-5 sm:mb-6">{{ $sorteio->titulo }}</h1>

                        <div class="border border-black bg-[var(--color-lab-bg)] p-5 sm:p-6 text-center mb-5 sm:mb-6">
                            <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-2">Seu número</p>
                            <p class="font-mono text-5xl sm:text-6xl font-bold tracking-tight">{{ $participacao->numeroFormatado() }}</p>
                        </div>

                        <div class="divide-y divide-[var(--color-lab-border)] border-y border-[var(--color-lab-border)] text-sm">
                            <div class="grid grid-cols-1 sm:grid-cols-[140px_1fr] gap-1 sm:gap-4 py-3">
                                <span class="text-gray-500">Status</span>
                                <span class="font-bold sm:text-right">{{ $participacao->statusLabel() }}</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-[140px_1fr] gap-2 sm:gap-4 py-3">
                                <span class="text-gray-500">Instagram</span>
                                <div class="sm:text-right min-w-0">
                                    <span class="inline-block max-w-full border border-[var(--color-lab-border)] bg-white px-3 py-2 font-mono text-xs sm:text-sm leading-none break-all">
                                        {{ $instagramUsername }}
                                    </span>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-[140px_1fr] gap-2 sm:gap-4 py-3">
                                <span class="text-gray-500">Amigos marcados</span>
                                <div class="flex flex-wrap gap-2 sm:justify-end min-w-0">
                                    <span class="inline-block max-w-full border border-[var(--color-lab-border)] bg-white px-3 py-2 font-mono text-xs sm:text-sm leading-none break-all">
                                        {{ $instagramFriend1 }}
                                    </span>
                                    <span class="inline-block max-w-full border border-[var(--color-lab-border)] bg-white px-3 py-2 font-mono text-xs sm:text-sm leading-none break-all">
                                        {{ $instagramFriend2 }}
                                    </span>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-[140px_1fr] gap-1 sm:gap-4 py-3">
                                <span class="text-gray-500">Inscrição</span>
                                <span class="font-mono sm:text-right">{{ $participacao->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>

                        @if($produtoSorteio)
                            <div class="mt-5 sm:mt-6 border border-[var(--color-lab-border)] p-4">
                                <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-3">Produto do sorteio</p>
                                <div class="flex gap-3 sm:gap-4">
                                    <div class="w-16 h-16 sm:w-20 sm:h-20 bg-[var(--color-lab-bg)] border border-[var(--color-lab-border)] p-2 flex items-center justify-center shrink-0">
                                        @if($produtoImagemPrincipal)
                                            <img src="{{ asset('storage/' . $produtoImagemPrincipal->caminho) }}" alt="{{ $produtoSorteio->nome }}" class="w-full h-full object-contain mix-blend-multiply">
                                        @else
                                            <svg class="w-8 h-8 text-gray-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-bold text-sm leading-5">{{ $produtoSorteio->nome }}</p>
                                        <p class="font-mono text-xs text-gray-500 mt-1">R$ {{ number_format($produtoSorteio->preco, 2, ',', '.') }}</p>
                                        <a href="{{ route('site.produto.detalhes', $produtoSorteio->slug) }}" class="inline-flex mt-3 text-xs font-bold uppercase tracking-widest text-black hover:text-gray-500 transition-colors">
                                            Ver produto
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="mt-6 flex flex-col sm:flex-row gap-3">
                            <a href="{{ route('site.sorteios.minhas') }}" class="inline-flex w-full sm:w-auto justify-center border border-black px-5 py-3 text-xs font-bold uppercase tracking-widest hover:bg-black hover:text-white transition-colors">
                                Meus sorteios
                            </a>
                            @if($sorteio->instagram_post_url)
                                <a href="{{ $sorteio->instagram_post_url }}" target="_blank" rel="noopener" class="inline-flex w-full sm:w-auto justify-center border border-[var(--color-lab-border)] px-5 py-3 text-xs font-bold uppercase tracking-widest hover:border-black transition-colors">
                                    Post oficial
                                </a>
                            @endif
                        </div>
                    </section>

                    <section class="p-4 sm:p-8">
                        <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-2">Resultado</p>

                        @if(! $resultadoPublicado)
                            <div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] p-5 sm:p-6">
                                <h2 class="text-xl sm:text-2xl font-bold mb-3">Resultado ainda não publicado</h2>
                                <p class="text-sm text-gray-600 leading-6">
                                    Quando o ganhador for confirmado pela auditoria, o resultado final aparecerá aqui.
                                </p>
                                <div class="mt-5 grid grid-cols-[auto_1fr] gap-3 text-sm">
                                    <span class="mt-1 h-2.5 w-2.5 bg-black"></span>
                                    <div>
                                        <p class="font-bold">Participação registrada</p>
                                        <p class="text-gray-500 leading-5">Seu número já está reservado para este sorteio.</p>
                                    </div>
                                    <span class="mt-1 h-2.5 w-2.5 border border-gray-400"></span>
                                    <div>
                                        <p class="font-bold text-gray-500">Aguardando apuração</p>
                                        <p class="text-gray-500 leading-5">Volte nesta página para acompanhar a publicação.</p>
                                    </div>
                                </div>
                            </div>
                        @elseif($foiGanhador)
                            <div class="border border-black bg-black text-white p-5 sm:p-6">
                                <p class="font-mono text-[10px] uppercase tracking-widest text-white/60 mb-2">Resultado final</p>
                                <h2 class="text-2xl sm:text-3xl font-bold mb-3">Seu número foi sorteado</h2>
                                <p class="text-sm text-white/70">Número vencedor: {{ $participacao->numeroFormatado() }}</p>
                            </div>
                        @else
                            <div class="border border-[var(--color-lab-border)] p-5 sm:p-6">
                                <h2 class="text-xl sm:text-2xl font-bold mb-3">Resultado publicado</h2>
                                <p class="text-sm text-gray-600 mb-6">Seu número não foi o sorteado nesta campanha.</p>
                                @if($ganhador)
                                    <div class="border border-black p-5">
                                        <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-2">Ganhador</p>
                                        <p class="text-xl font-bold">{{ $ganhador->user?->name }}</p>
                                        <p class="font-mono text-sm text-gray-500 mt-1 break-words">
                                            Número {{ $ganhador->numeroFormatado() }} · {{ $ganhadorInstagram }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if($resultadoPublicado && $ganhador && ! $foiGanhador)
                            <div class="mt-5 text-xs text-gray-500 leading-5">
                                Resultado publicado em {{ $sorteio->resultado_publicado_at->format('d/m/Y H:i') }}.
                            </div>
                        @endif
                    </section>
                </div>
            </div>
        </div>
    </main>

    @include('includes.footer')
</body>
</html>
