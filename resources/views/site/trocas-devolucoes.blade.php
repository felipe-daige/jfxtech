<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Política de trocas e devoluções da JFXTECH para compras online.">
    <title>Trocas e Devoluções - JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
</head>
<body class="min-h-screen flex flex-col bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)] antialiased">
    @include('includes.header')

    <main class="flex-grow">
        <section class="bg-black py-16 border-b border-[var(--color-lab-border)]">
            <div class="container mx-auto px-4 lg:px-8">
                <span class="font-mono text-[10px] uppercase tracking-widest text-gray-400">Suporte</span>
                <h1 class="text-3xl md:text-4xl font-bold text-white mb-3 uppercase tracking-wider">Trocas e Devoluções</h1>
                <p class="max-w-3xl text-gray-400 text-sm leading-relaxed">Confira as condições para solicitar troca, devolução por arrependimento ou atendimento para produto com problema.</p>
            </div>
        </section>

        <section class="py-16">
            <div class="container mx-auto px-4 lg:px-8">
                <div class="grid lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white border border-[var(--color-lab-border)] p-8">
                            <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">Direito de arrependimento</span>
                            <h2 class="text-2xl font-bold uppercase tracking-wider mt-1 mb-4">Devolução em até 7 dias</h2>
                            <p class="text-sm text-[var(--color-lab-muted)] leading-relaxed">Compras online podem ser devolvidas em até 7 dias corridos após o recebimento, desde que o produto esteja completo, sem sinais de uso indevido e com embalagem, manuais, acessórios e nota fiscal quando aplicável.</p>
                        </div>

                        <div class="bg-white border border-[var(--color-lab-border)] p-8">
                            <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">Troca</span>
                            <h2 class="text-2xl font-bold uppercase tracking-wider mt-1 mb-4">Produto errado, avariado ou com defeito</h2>
                            <div class="space-y-4 text-sm text-[var(--color-lab-muted)] leading-relaxed">
                                <p>Se o produto chegou diferente do pedido, sofreu avaria no transporte ou apresentou defeito, entre em contato com nosso suporte informando o número do pedido e enviando fotos da embalagem e do item.</p>
                                <p>A equipe avalia a solicitação e orienta sobre coleta, postagem, troca, reembolso ou encaminhamento para garantia, conforme o caso.</p>
                            </div>
                        </div>

                        <div class="grid md:grid-cols-3 gap-4">
                            <div class="bg-white border border-[var(--color-lab-border)] p-6">
                                <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-2">01</p>
                                <h3 class="font-bold uppercase tracking-wider text-sm mb-2">Solicite suporte</h3>
                                <p class="text-sm text-[var(--color-lab-muted)]">Envie pedido, motivo da troca ou devolução e imagens do produto.</p>
                            </div>
                            <div class="bg-white border border-[var(--color-lab-border)] p-6">
                                <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-2">02</p>
                                <h3 class="font-bold uppercase tracking-wider text-sm mb-2">Aguarde análise</h3>
                                <p class="text-sm text-[var(--color-lab-muted)]">Nossa equipe confirma a elegibilidade e informa o procedimento.</p>
                            </div>
                            <div class="bg-white border border-[var(--color-lab-border)] p-6">
                                <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-2">03</p>
                                <h3 class="font-bold uppercase tracking-wider text-sm mb-2">Envie o item</h3>
                                <p class="text-sm text-[var(--color-lab-muted)]">O produto deve retornar completo e protegido para transporte.</p>
                            </div>
                        </div>

                        <div class="bg-white border border-[var(--color-lab-border)] p-8">
                            <h2 class="text-2xl font-bold uppercase tracking-wider mb-4">Condições importantes</h2>
                            <ul class="space-y-3 text-sm text-[var(--color-lab-muted)]">
                                <li class="flex gap-3"><span class="font-mono text-black">-</span><span>Não envie produtos sem autorização prévia do suporte.</span></li>
                                <li class="flex gap-3"><span class="font-mono text-black">-</span><span>Guarde embalagem, acessórios e comprovantes até a conclusão do atendimento.</span></li>
                                <li class="flex gap-3"><span class="font-mono text-black">-</span><span>Produtos com sinais de mau uso podem ter troca ou devolução recusada após análise.</span></li>
                                <li class="flex gap-3"><span class="font-mono text-black">-</span><span>Reembolsos seguem o mesmo meio de pagamento utilizado na compra sempre que possível.</span></li>
                            </ul>
                        </div>
                    </div>

                    <aside class="space-y-6">
                        <div class="bg-black p-6 text-white">
                            <h2 class="font-mono text-[10px] uppercase tracking-widest text-gray-400 mb-3">Precisa abrir solicitação?</h2>
                            <p class="text-sm text-gray-300 mb-5">Tenha em mãos o número do pedido e explique o motivo da troca ou devolução.</p>
                            <a href="{{ route('site.contato') }}" class="inline-block bg-white text-black font-mono uppercase text-[10px] tracking-widest px-6 py-3 hover:bg-gray-100 transition-colors">Fale Conosco</a>
                        </div>

                        <div class="bg-white border border-[var(--color-lab-border)] p-6">
                            <h2 class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-4">Atalhos</h2>
                            <div class="space-y-3">
                                <a href="{{ route('site.pedidos.index') }}" class="block border border-[var(--color-lab-border)] px-4 py-3 text-sm hover:border-black transition-colors">Meus pedidos</a>
                                <a href="{{ route('site.garantia') }}" class="block border border-[var(--color-lab-border)] px-4 py-3 text-sm hover:border-black transition-colors">Garantia</a>
                                <a href="{{ route('site.rastreamento') }}" class="block border border-[var(--color-lab-border)] px-4 py-3 text-sm hover:border-black transition-colors">Rastreamento</a>
                            </div>
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
