<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Termos e Condições de uso e compra da loja JFXTECH.">
    <title>Termos e Condições - JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
</head>
<body class="min-h-screen flex flex-col bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)] antialiased">
    @include('includes.header')

    <main class="flex-grow">
        <section class="bg-black py-16 border-b border-[var(--color-lab-border)]">
            <div class="container mx-auto px-4 lg:px-8">
                <span class="font-mono text-[10px] uppercase tracking-widest text-gray-400">Legal</span>
                <h1 class="text-3xl md:text-4xl font-bold text-white mb-3 uppercase tracking-wider">Termos e Condições</h1>
                <p class="max-w-3xl text-gray-400 text-sm leading-relaxed">Ao realizar uma compra na JFXTECH, você concorda com os termos descritos nesta página. Leia com atenção antes de finalizar seu pedido.</p>
            </div>
        </section>

        <section class="py-16">
            <div class="container mx-auto px-4 lg:px-8">
                <div class="grid lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 space-y-6">

                        <div class="bg-white border border-[var(--color-lab-border)] p-8">
                            <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">01</span>
                            <h2 class="text-xl font-bold uppercase tracking-wider mt-1 mb-4">Sobre a JFXTECH</h2>
                            <div class="space-y-3 text-sm text-[var(--color-lab-muted)] leading-relaxed">
                                <p>A JFXTECH é uma loja virtual especializada em hardware gamer e periféricos de alta performance. As vendas são realizadas exclusivamente por meio deste site, no endereço <strong class="text-black">jfxtech.com.br</strong>.</p>
                                <p>Ao navegar ou realizar qualquer compra em nossa loja, você declara ter lido, entendido e concordado com estes Termos e Condições.</p>
                            </div>
                        </div>

                        <div class="bg-white border border-[var(--color-lab-border)] p-8">
                            <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">02</span>
                            <h2 class="text-xl font-bold uppercase tracking-wider mt-1 mb-4">Cadastro e Conta</h2>
                            <div class="space-y-3 text-sm text-[var(--color-lab-muted)] leading-relaxed">
                                <p>Para realizar uma compra, é possível fazê-lo como convidado (guest) ou criando uma conta. Você é responsável por manter a confidencialidade dos seus dados de acesso.</p>
                                <p>As informações fornecidas no cadastro devem ser verdadeiras e atualizadas. A JFXTECH não se responsabiliza por dados incorretos informados pelo usuário.</p>
                                <p>Reservamo-nos o direito de cancelar contas com atividades suspeitas ou fraudulentas.</p>
                            </div>
                        </div>

                        <div class="bg-white border border-[var(--color-lab-border)] p-8">
                            <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">03</span>
                            <h2 class="text-xl font-bold uppercase tracking-wider mt-1 mb-4">Preços e Pagamento</h2>
                            <div class="space-y-3 text-sm text-[var(--color-lab-muted)] leading-relaxed">
                                <p>Todos os preços exibidos são em reais (R$). O frete é calculado separadamente conforme o endereço de entrega e o peso do pedido.</p>
                                <p>Os pagamentos são processados de forma segura pelo Mercado Pago. A JFXTECH não armazena dados de cartão de crédito.</p>
                                <p>Em caso de divergência de preço por erro de sistema, entraremos em contato para confirmar ou cancelar o pedido antes do processamento.</p>
                                <p>Cupons de desconto têm validade, condições de uso e não são cumulativos, salvo indicação contrária.</p>
                            </div>
                        </div>

                        <div class="bg-white border border-[var(--color-lab-border)] p-8">
                            <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">04</span>
                            <h2 class="text-xl font-bold uppercase tracking-wider mt-1 mb-4">Entrega e Frete</h2>
                            <div class="space-y-3 text-sm text-[var(--color-lab-muted)] leading-relaxed">
                                <p>Os prazos de entrega são estimados e contam a partir da confirmação do pagamento. Eventuais atrasos por parte da transportadora estão fora do controle da JFXTECH.</p>
                                <p>O código de rastreio é disponibilizado assim que o pedido é postado. É responsabilidade do cliente acompanhar a entrega e estar disponível para recebê-la.</p>
                                <p>Pedidos não entregues por ausência do destinatário podem gerar custos adicionais de reenvio ou resultar em devolução ao remetente.</p>
                            </div>
                        </div>

                        <div class="bg-white border border-[var(--color-lab-border)] p-8">
                            <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">05</span>
                            <h2 class="text-xl font-bold uppercase tracking-wider mt-1 mb-4">Trocas, Devoluções e Garantia</h2>
                            <div class="space-y-3 text-sm text-[var(--color-lab-muted)] leading-relaxed">
                                <p>O cliente tem direito à devolução por arrependimento em até 7 dias corridos após o recebimento do produto, conforme o Código de Defesa do Consumidor (Art. 49, Lei 8.078/90).</p>
                                <p>Produtos com defeito de fabricação são cobertos pela garantia do fabricante. O prazo e as condições variam por marca e categoria.</p>
                                <p>Para abrir qualquer solicitação de troca, devolução ou garantia, entre em contato com o suporte pelo e-mail <strong class="text-black">contato@jfxtech.com.br</strong>. Não envie produtos sem autorização prévia.</p>
                            </div>
                        </div>

                        <div class="bg-white border border-[var(--color-lab-border)] p-8">
                            <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">06</span>
                            <h2 class="text-xl font-bold uppercase tracking-wider mt-1 mb-4">Propriedade Intelectual</h2>
                            <div class="space-y-3 text-sm text-[var(--color-lab-muted)] leading-relaxed">
                                <p>Todo o conteúdo deste site — textos, imagens, logotipos, layout e código — é de propriedade da JFXTECH ou licenciado para uso. É proibida a reprodução, cópia ou distribuição sem autorização expressa.</p>
                                <p>Marcas de fabricantes exibidas nos produtos pertencem a seus respectivos proprietários e são usadas apenas para fins de identificação.</p>
                            </div>
                        </div>

                        <div class="bg-white border border-[var(--color-lab-border)] p-8">
                            <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">07</span>
                            <h2 class="text-xl font-bold uppercase tracking-wider mt-1 mb-4">Alterações nestes Termos</h2>
                            <div class="space-y-3 text-sm text-[var(--color-lab-muted)] leading-relaxed">
                                <p>A JFXTECH se reserva o direito de modificar estes Termos a qualquer momento. Alterações significativas serão comunicadas com antecedência. O uso continuado da loja após as alterações implica aceitação das novas condições.</p>
                                <p class="text-xs text-gray-400">Última atualização: {{ date('d/m/Y') }}</p>
                            </div>
                        </div>

                    </div>

                    <aside class="space-y-6">
                        <div class="bg-black p-6 text-white">
                            <h2 class="font-mono text-[10px] uppercase tracking-widest text-gray-400 mb-3">Dúvidas?</h2>
                            <p class="text-sm text-gray-300 mb-5">Em caso de dúvidas sobre estes termos, entre em contato pelo e-mail ou formulário.</p>
                            <a href="{{ route('site.contato') }}" class="inline-block bg-white text-black font-mono uppercase text-[10px] tracking-widest px-6 py-3 hover:bg-gray-100 transition-colors">Fale Conosco</a>
                        </div>

                        <div class="bg-white border border-[var(--color-lab-border)] p-6">
                            <h2 class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-4">Documentos relacionados</h2>
                            <div class="space-y-3">
                                <a href="{{ route('site.privacidade') }}" class="block border border-[var(--color-lab-border)] px-4 py-3 text-sm hover:border-black transition-colors">Política de Privacidade</a>
                                <a href="{{ route('site.trocas-devolucoes') }}" class="block border border-[var(--color-lab-border)] px-4 py-3 text-sm hover:border-black transition-colors">Trocas e Devoluções</a>
                                <a href="{{ route('site.garantia') }}" class="block border border-[var(--color-lab-border)] px-4 py-3 text-sm hover:border-black transition-colors">Garantia</a>
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
