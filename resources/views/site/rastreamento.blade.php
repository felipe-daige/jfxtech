<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Rastreie seu pedido JFXTECH usando o código de rastreamento enviado após a postagem.">
    <title>Rastreamento - JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
</head>
<body class="min-h-screen flex flex-col bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)] antialiased">
    @include('includes.header')

    <main class="flex-grow">
        <section class="bg-black py-16 border-b border-[var(--color-lab-border)]">
            <div class="container mx-auto px-4 lg:px-8">
                <span class="font-mono text-[10px] uppercase tracking-widest text-gray-400">Suporte</span>
                <h1 class="text-3xl md:text-4xl font-bold text-white mb-3 uppercase tracking-wider">Rastreamento</h1>
                <p class="max-w-3xl text-gray-400 text-sm leading-relaxed">Use o código de rastreio informado no seu pedido para acompanhar a entrega. O status pode levar algumas horas para aparecer após a postagem.</p>
            </div>
        </section>

        <section class="py-16">
            <div class="container mx-auto px-4 lg:px-8">
                <div class="grid lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white border border-[var(--color-lab-border)] p-8">
                            <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">Correios</span>
                            <h2 class="text-2xl font-bold uppercase tracking-wider mt-1 mb-4">Consultar entrega</h2>
                            <p class="text-sm text-[var(--color-lab-muted)] mb-6">Digite o código recebido por e-mail ou disponível em Meus Pedidos. A consulta abre o rastreador dos Correios em uma nova aba.</p>

                            <form action="https://rastreamento.correios.com.br/app/index.php" method="get" target="_blank" rel="noopener" class="grid sm:grid-cols-[1fr_auto] gap-3">
                                <label class="sr-only" for="objetos">Código de rastreio</label>
                                <input
                                    id="objetos"
                                    name="objetos"
                                    type="text"
                                    maxlength="50"
                                    autocomplete="off"
                                    placeholder="EX: BR123456789BR"
                                    class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono uppercase tracking-wider focus:outline-none focus:border-black transition-colors"
                                    required
                                >
                                <button type="submit" class="bg-black text-white px-6 py-3 text-[10px] font-mono uppercase tracking-widest hover:bg-gray-900 transition-colors">
                                    Rastrear
                                </button>
                            </form>
                        </div>

                        <div class="grid md:grid-cols-3 gap-4">
                            <div class="bg-white border border-[var(--color-lab-border)] p-6">
                                <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-2">01</p>
                                <h3 class="font-bold uppercase tracking-wider text-sm mb-2">Pedido aprovado</h3>
                                <p class="text-sm text-[var(--color-lab-muted)]">Após a confirmação do pagamento, o pedido entra em separação.</p>
                            </div>
                            <div class="bg-white border border-[var(--color-lab-border)] p-6">
                                <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-2">02</p>
                                <h3 class="font-bold uppercase tracking-wider text-sm mb-2">Postagem</h3>
                                <p class="text-sm text-[var(--color-lab-muted)]">O código de rastreio é liberado quando a transportadora recebe a encomenda.</p>
                            </div>
                            <div class="bg-white border border-[var(--color-lab-border)] p-6">
                                <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-2">03</p>
                                <h3 class="font-bold uppercase tracking-wider text-sm mb-2">Entrega</h3>
                                <p class="text-sm text-[var(--color-lab-muted)]">Acompanhe movimentações e previsão diretamente no rastreador.</p>
                            </div>
                        </div>

                        <div class="bg-white border border-[var(--color-lab-border)] p-8">
                            <h2 class="text-2xl font-bold uppercase tracking-wider mb-4">Onde encontrar o código</h2>
                            <div class="space-y-4 text-sm text-[var(--color-lab-muted)] leading-relaxed">
                                <p>O código de rastreio aparece no detalhe do pedido quando ele é marcado como enviado. Ele também pode ser enviado por e-mail junto da atualização de status.</p>
                                <p>Se o pedido já foi enviado e o código ainda não aparece, fale com o suporte informando o número do pedido.</p>
                            </div>
                        </div>
                    </div>

                    <aside class="space-y-6">
                        <div class="bg-black p-6 text-white">
                            <h2 class="font-mono text-[10px] uppercase tracking-widest text-gray-400 mb-3">Meus pedidos</h2>
                            <p class="text-sm text-gray-300 mb-5">Acesse sua conta para ver status, detalhes e código de rastreamento dos pedidos feitos na loja.</p>
                            <a href="{{ route('site.pedidos.index') }}" class="inline-block bg-white text-black font-mono uppercase text-[10px] tracking-widest px-6 py-3 hover:bg-gray-100 transition-colors">Ver Pedidos</a>
                        </div>

                        <div class="bg-white border border-[var(--color-lab-border)] p-6">
                            <h2 class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-4">Observações</h2>
                            <ul class="space-y-3 text-sm text-[var(--color-lab-muted)]">
                                <li class="flex gap-3"><span class="font-mono text-black">-</span><span>O código pode levar algumas horas para atualizar no sistema da transportadora.</span></li>
                                <li class="flex gap-3"><span class="font-mono text-black">-</span><span>Confira se não há espaços antes ou depois do código digitado.</span></li>
                                <li class="flex gap-3"><span class="font-mono text-black">-</span><span>Pedidos ainda em separação não possuem código de rastreio.</span></li>
                            </ul>
                        </div>
                    </aside>
                </div>
            </div>
        </section>
    </main>

    @include('includes.footer')
    <script src="{{ asset('js/dropdowns.js') }}"></script>
</body>
</html>
