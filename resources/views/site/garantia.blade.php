<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Entenda como funciona a garantia dos produtos vendidos pela JFXTECH.">
    <title>Garantia - JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
</head>
<body class="min-h-screen flex flex-col bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)] antialiased">
    @include('includes.header')

    <main class="flex-grow">
        <section class="bg-black py-16 border-b border-[var(--color-lab-border)]">
            <div class="container mx-auto px-4 lg:px-8">
                <span class="font-mono text-[10px] uppercase tracking-widest text-gray-400">Suporte</span>
                <h1 class="text-3xl md:text-4xl font-bold text-white mb-3 uppercase tracking-wider">Garantia</h1>
                <p class="max-w-3xl text-gray-400 text-sm leading-relaxed">Todos os produtos vendidos pela JFXTECH contam com garantia contra defeitos de fabricação. O prazo e as condições podem variar conforme a marca, categoria e política do fabricante.</p>
            </div>
        </section>

        <section class="py-16">
            <div class="container mx-auto px-4 lg:px-8">
                <div class="grid lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white border border-[var(--color-lab-border)] p-8">
                            <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">Como funciona</span>
                            <h2 class="text-2xl font-bold uppercase tracking-wider mt-1 mb-4">Cobertura da garantia</h2>
                            <div class="space-y-4 text-sm text-[var(--color-lab-muted)] leading-relaxed">
                                <p>A garantia cobre defeitos de fabricação identificados em uso normal do produto. Ela não cobre mau uso, danos físicos, oxidação, líquido, queda, modificação, desgaste natural ou instalação inadequada.</p>
                                <p>Quando a garantia for acionada, nossa equipe faz a triagem inicial e orienta o envio das informações necessárias. Em alguns casos, o atendimento pode seguir diretamente pelo fabricante ou assistência autorizada.</p>
                            </div>
                        </div>

                        <div class="grid md:grid-cols-3 gap-4">
                            <div class="bg-white border border-[var(--color-lab-border)] p-6">
                                <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-2">01</p>
                                <h3 class="font-bold uppercase tracking-wider text-sm mb-2">Separe os dados</h3>
                                <p class="text-sm text-[var(--color-lab-muted)]">Tenha o número do pedido, CPF ou e-mail da compra e fotos ou vídeos do problema.</p>
                            </div>
                            <div class="bg-white border border-[var(--color-lab-border)] p-6">
                                <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-2">02</p>
                                <h3 class="font-bold uppercase tracking-wider text-sm mb-2">Fale conosco</h3>
                                <p class="text-sm text-[var(--color-lab-muted)]">Envie a solicitação pelo WhatsApp ou e-mail para receber a análise inicial.</p>
                            </div>
                            <div class="bg-white border border-[var(--color-lab-border)] p-6">
                                <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-2">03</p>
                                <h3 class="font-bold uppercase tracking-wider text-sm mb-2">Acompanhe</h3>
                                <p class="text-sm text-[var(--color-lab-muted)]">A equipe informa os próximos passos, prazos e necessidade de envio do produto.</p>
                            </div>
                        </div>

                        <div class="bg-white border border-[var(--color-lab-border)] p-8">
                            <h2 class="text-2xl font-bold uppercase tracking-wider mb-4">Itens necessários</h2>
                            <ul class="space-y-3 text-sm text-[var(--color-lab-muted)]">
                                <li class="flex gap-3"><span class="font-mono text-black">-</span><span>Número do pedido ou comprovante de compra.</span></li>
                                <li class="flex gap-3"><span class="font-mono text-black">-</span><span>Descrição objetiva do defeito encontrado.</span></li>
                                <li class="flex gap-3"><span class="font-mono text-black">-</span><span>Fotos ou vídeos que ajudem a identificar o problema.</span></li>
                                <li class="flex gap-3"><span class="font-mono text-black">-</span><span>Produto completo, com acessórios, quando o envio for solicitado.</span></li>
                            </ul>
                        </div>
                    </div>

                    <aside class="space-y-6">
                        <div class="bg-black p-6 text-white">
                            <h2 class="font-mono text-[10px] uppercase tracking-widest text-gray-400 mb-3">Atendimento</h2>
                            <p class="text-sm text-gray-300 mb-5">Para acionar a garantia, informe o número do pedido e detalhe o defeito encontrado.</p>
                            <a href="{{ route('site.contato') }}" class="inline-block bg-white text-black font-mono uppercase text-[10px] tracking-widest px-6 py-3 hover:bg-gray-100 transition-colors">Fale Conosco</a>
                        </div>

                        <div class="bg-white border border-[var(--color-lab-border)] p-6">
                            <h2 class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-4">Resumo</h2>
                            <dl class="space-y-4 text-sm">
                                <div>
                                    <dt class="font-bold text-black">Prazo</dt>
                                    <dd class="text-[var(--color-lab-muted)]">Varia por fabricante e produto.</dd>
                                </div>
                                <div>
                                    <dt class="font-bold text-black">Cobertura</dt>
                                    <dd class="text-[var(--color-lab-muted)]">Defeitos de fabricação.</dd>
                                </div>
                                <div>
                                    <dt class="font-bold text-black">Canal</dt>
                                    <dd class="text-[var(--color-lab-muted)]">WhatsApp ou e-mail de suporte.</dd>
                                </div>
                            </dl>
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
