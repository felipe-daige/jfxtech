<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Meus Sorteios - JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
</head>
<body class="min-h-screen flex flex-col bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)] antialiased">
    @include('includes.header')

    <main class="flex-grow py-10 sm:py-14">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8">
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-2">Conta JFXTECH</p>
                    <h1 class="text-3xl font-bold tracking-tight">Meus sorteios</h1>
                </div>
                <a href="{{ route('site.sorteio.index') }}" class="inline-flex justify-center bg-black text-white px-5 py-3 text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition-colors">
                    Ver sorteio ativo
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @forelse($participacoes as $participacao)
                    @php
                        $sorteio = $participacao->sorteio;
                        $ganhador = $sorteio?->ganhador;
                        $resultadoPublicado = $sorteio?->resultadoPublicado();
                        $foiGanhador = $resultadoPublicado && $ganhador && (int) $ganhador->id === (int) $participacao->id;
                    @endphp
                    <article class="bg-white border border-[var(--color-lab-border)] p-5">
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div>
                                <h2 class="font-bold text-lg">{{ $sorteio?->titulo }}</h2>
                                @if($sorteio?->produto)
                                    <p class="text-sm text-gray-500 mt-1">{{ $sorteio->produto->nome }}</p>
                                @endif
                                <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mt-1">
                                    {{ $participacao->created_at->format('d/m/Y H:i') }}
                                </p>
                            </div>
                            <span class="px-2 py-1 text-[10px] font-mono uppercase tracking-widest border {{ $resultadoPublicado ? 'border-black text-black' : 'border-gray-300 text-gray-400' }}">
                                {{ $resultadoPublicado ? 'Resultado' : 'Aberto' }}
                            </span>
                        </div>

                        <div class="border border-black p-4 text-center mb-4">
                            <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500">Número</p>
                            <p class="font-mono text-4xl font-bold">{{ $participacao->numeroFormatado() }}</p>
                        </div>

                        @if($resultadoPublicado)
                            <p class="text-sm {{ $foiGanhador ? 'font-bold text-black' : 'text-gray-500' }}">
                                {{ $foiGanhador ? 'Seu número foi sorteado.' : 'Ganhador: número '.$ganhador?->numeroFormatado().' - @'.$ganhador?->instagram_username }}
                            </p>
                        @else
                            <p class="text-sm text-gray-500">Resultado ainda não publicado.</p>
                        @endif

                        @if($sorteio)
                            <a href="{{ route('site.sorteio.acompanhar', $sorteio) }}" class="inline-flex mt-5 border border-black px-4 py-2 text-xs font-bold uppercase tracking-widest hover:bg-black hover:text-white transition-colors">
                                Acompanhar
                            </a>
                        @endif
                    </article>
                @empty
                    <div class="md:col-span-2 xl:col-span-3 bg-white border border-[var(--color-lab-border)] p-10 text-center">
                        <p class="text-sm text-gray-500 mb-5">Você ainda não está participando de nenhum sorteio.</p>
                        <a href="{{ route('site.sorteio.index') }}" class="inline-flex bg-black text-white px-5 py-3 text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition-colors">
                            Participar agora
                        </a>
                    </div>
                @endforelse
            </div>

            <div class="mt-6">
                {{ $participacoes->links() }}
            </div>
        </div>
    </main>

    @include('includes.footer')
</body>
</html>
