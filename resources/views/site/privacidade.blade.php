<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Política de Privacidade da JFXTECH — como coletamos, usamos e protegemos seus dados.">
    <title>Política de Privacidade - JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
</head>
<body class="min-h-screen flex flex-col bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)] antialiased">
    @include('includes.header')

    <main class="flex-grow">
        <section class="bg-black py-16 border-b border-[var(--color-lab-border)]">
            <div class="container mx-auto px-4 lg:px-8">
                <span class="font-mono text-[10px] uppercase tracking-widest text-gray-400">Legal</span>
                <h1 class="text-3xl md:text-4xl font-bold text-white mb-3 uppercase tracking-wider">Política de Privacidade</h1>
                <p class="max-w-3xl text-gray-400 text-sm leading-relaxed">Explicamos aqui quais dados coletamos, como os utilizamos e quais são seus direitos, em conformidade com a Lei Geral de Proteção de Dados (LGPD — Lei nº 13.709/2018).</p>
            </div>
        </section>

        <section class="py-16">
            <div class="container mx-auto px-4 lg:px-8">
                <div class="grid lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 space-y-6">

                        <div class="bg-white border border-[var(--color-lab-border)] p-8">
                            <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">01</span>
                            <h2 class="text-xl font-bold uppercase tracking-wider mt-1 mb-4">Quais dados coletamos</h2>
                            <div class="space-y-3 text-sm text-[var(--color-lab-muted)] leading-relaxed">
                                <p>Coletamos apenas os dados necessários para a realização de compras, entrega dos produtos e prestação de suporte:</p>
                                <ul class="space-y-2 mt-3">
                                    <li class="flex gap-3"><span class="font-mono text-black">-</span><span><strong class="text-black">Dados de cadastro:</strong> nome, e-mail e senha (armazenada com hash seguro).</span></li>
                                    <li class="flex gap-3"><span class="font-mono text-black">-</span><span><strong class="text-black">Dados do pedido:</strong> CPF, endereço de entrega e histórico de compras.</span></li>
                                    <li class="flex gap-3"><span class="font-mono text-black">-</span><span><strong class="text-black">Dados de navegação:</strong> sessão e cookies técnicos necessários para o funcionamento da loja.</span></li>
                                    <li class="flex gap-3"><span class="font-mono text-black">-</span><span><strong class="text-black">Dados de pagamento:</strong> processados integralmente pelo Mercado Pago. A JFXTECH não tem acesso a dados de cartão.</span></li>
                                </ul>
                            </div>
                        </div>

                        <div class="bg-white border border-[var(--color-lab-border)] p-8">
                            <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">02</span>
                            <h2 class="text-xl font-bold uppercase tracking-wider mt-1 mb-4">Como usamos seus dados</h2>
                            <div class="space-y-3 text-sm text-[var(--color-lab-muted)] leading-relaxed">
                                <p>Utilizamos os dados coletados exclusivamente para:</p>
                                <ul class="space-y-2 mt-3">
                                    <li class="flex gap-3"><span class="font-mono text-black">-</span><span>Processar e entregar seus pedidos.</span></li>
                                    <li class="flex gap-3"><span class="font-mono text-black">-</span><span>Enviar atualizações de status do pedido por e-mail.</span></li>
                                    <li class="flex gap-3"><span class="font-mono text-black">-</span><span>Prestar atendimento de suporte e garantia.</span></li>
                                    <li class="flex gap-3"><span class="font-mono text-black">-</span><span>Cumprir obrigações legais aplicáveis.</span></li>
                                </ul>
                                <p>Não vendemos, alugamos nem compartilhamos seus dados com terceiros para fins de marketing.</p>
                            </div>
                        </div>

                        <div class="bg-white border border-[var(--color-lab-border)] p-8">
                            <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">02a</span>
                            <h2 class="text-xl font-bold uppercase tracking-wider mt-1 mb-4">Base legal do tratamento</h2>
                            <div class="space-y-3 text-sm text-[var(--color-lab-muted)] leading-relaxed">
                                <p>O tratamento de dados pessoais realizado pela JFXTECH se apoia nas seguintes bases legais previstas no Art. 7º da LGPD:</p>
                                <ul class="space-y-2 mt-3">
                                    <li class="flex gap-3"><span class="font-mono text-black">-</span><span><strong class="text-black">Execução de contrato</strong> (Art. 7º, V): dados necessários para processar pedidos, realizar entregas e prestar suporte pós-venda.</span></li>
                                    <li class="flex gap-3"><span class="font-mono text-black">-</span><span><strong class="text-black">Consentimento</strong> (Art. 7º, I): dados fornecidos voluntariamente no cadastro e no formulário de contato.</span></li>
                                    <li class="flex gap-3"><span class="font-mono text-black">-</span><span><strong class="text-black">Obrigação legal</strong> (Art. 7º, II): retenção de registros exigida pela legislação aplicável.</span></li>
                                </ul>
                            </div>
                        </div>

                        <div class="bg-white border border-[var(--color-lab-border)] p-8">
                            <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">02b</span>
                            <h2 class="text-xl font-bold uppercase tracking-wider mt-1 mb-4">Prazo de retenção</h2>
                            <div class="space-y-3 text-sm text-[var(--color-lab-muted)] leading-relaxed">
                                <p>Os dados pessoais associados a pedidos são retidos por até <strong class="text-black">2 anos</strong> após a conclusão da compra, período necessário para atender eventuais solicitações de garantia, trocas ou suporte.</p>
                                <p>Dados de cadastro são mantidos enquanto a conta estiver ativa. Após a exclusão da conta ou solicitação do titular, os dados são removidos, salvo obrigação legal de retenção.</p>
                            </div>
                        </div>

                        <div class="bg-white border border-[var(--color-lab-border)] p-8">
                            <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">03</span>
                            <h2 class="text-xl font-bold uppercase tracking-wider mt-1 mb-4">Compartilhamento de dados</h2>
                            <div class="space-y-3 text-sm text-[var(--color-lab-muted)] leading-relaxed">
                                <p>Seus dados podem ser compartilhados apenas com parceiros operacionais necessários para a execução do serviço:</p>
                                <ul class="space-y-2 mt-3">
                                    <li class="flex gap-3"><span class="font-mono text-black">-</span><span><strong class="text-black">Mercado Pago:</strong> processamento de pagamentos.</span></li>
                                    <li class="flex gap-3"><span class="font-mono text-black">-</span><span><strong class="text-black">Transportadoras (Correios):</strong> informações de entrega necessárias para postagem.</span></li>
                                </ul>
                                <p>Esses parceiros têm suas próprias políticas de privacidade e são responsáveis pelo tratamento dos dados em seus sistemas.</p>
                            </div>
                        </div>

                        <div class="bg-white border border-[var(--color-lab-border)] p-8">
                            <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">04</span>
                            <h2 class="text-xl font-bold uppercase tracking-wider mt-1 mb-4">Cookies</h2>
                            <div class="space-y-3 text-sm text-[var(--color-lab-muted)] leading-relaxed">
                                <p>Utilizamos cookies estritamente necessários para o funcionamento da sessão e da loja (autenticação, carrinho, segurança CSRF). Não utilizamos cookies de rastreamento de terceiros ou publicidade.</p>
                                <p>Ao navegar no site, você concorda com o uso desses cookies técnicos. Desativá-los no navegador pode impedir o funcionamento correto da loja.</p>
                            </div>
                        </div>

                        <div class="bg-white border border-[var(--color-lab-border)] p-8">
                            <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">05</span>
                            <h2 class="text-xl font-bold uppercase tracking-wider mt-1 mb-4">Seus direitos (LGPD)</h2>
                            <div class="space-y-3 text-sm text-[var(--color-lab-muted)] leading-relaxed">
                                <p>De acordo com a LGPD, você tem os seguintes direitos em relação aos seus dados pessoais:</p>
                                <ul class="space-y-2 mt-3">
                                    <li class="flex gap-3"><span class="font-mono text-black">-</span><span><strong class="text-black">Acesso:</strong> solicitar confirmação sobre quais dados temos sobre você.</span></li>
                                    <li class="flex gap-3"><span class="font-mono text-black">-</span><span><strong class="text-black">Correção:</strong> atualizar dados incompletos ou incorretos via perfil ou suporte.</span></li>
                                    <li class="flex gap-3"><span class="font-mono text-black">-</span><span><strong class="text-black">Eliminação:</strong> solicitar a exclusão de dados desnecessários, observadas as obrigações legais de retenção.</span></li>
                                    <li class="flex gap-3"><span class="font-mono text-black">-</span><span><strong class="text-black">Portabilidade:</strong> receber seus dados em formato estruturado mediante solicitação.</span></li>
                                    <li class="flex gap-3"><span class="font-mono text-black">-</span><span><strong class="text-black">Oposição:</strong> se opor a qualquer tratamento de dados baseado em interesse legítimo.</span></li>
                                </ul>
                                <p>Para exercer qualquer um desses direitos, envie uma solicitação para <strong class="text-black">contato@jfxtech.com.br</strong>.</p>
                            </div>
                        </div>

                        <div class="bg-white border border-[var(--color-lab-border)] p-8">
                            <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">06</span>
                            <h2 class="text-xl font-bold uppercase tracking-wider mt-1 mb-4">Segurança</h2>
                            <div class="space-y-3 text-sm text-[var(--color-lab-muted)] leading-relaxed">
                                <p>Adotamos medidas técnicas e organizacionais adequadas para proteger seus dados contra acesso não autorizado, perda ou divulgação indevida, incluindo criptografia de senhas, comunicação via HTTPS e controle de acesso interno.</p>
                            </div>
                        </div>

                        <div class="bg-white border border-[var(--color-lab-border)] p-8">
                            <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">06a</span>
                            <h2 class="text-xl font-bold uppercase tracking-wider mt-1 mb-4">Encarregado pelo tratamento de dados</h2>
                            <div class="space-y-3 text-sm text-[var(--color-lab-muted)] leading-relaxed">
                                <p>Nos termos do Art. 41 da LGPD, o responsável pelo tratamento de dados pessoais da JFXTECH pode ser contatado pelo e-mail:</p>
                                <p><strong class="text-black">contato@jfxtech.com.br</strong></p>
                                <p>Esse é o canal para exercer seus direitos, fazer solicitações ou reportar qualquer questão relacionada à privacidade dos seus dados.</p>
                            </div>
                        </div>

                        <div class="bg-white border border-[var(--color-lab-border)] p-8">
                            <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">07</span>
                            <h2 class="text-xl font-bold uppercase tracking-wider mt-1 mb-4">Alterações nesta política</h2>
                            <div class="space-y-3 text-sm text-[var(--color-lab-muted)] leading-relaxed">
                                <p>Esta política pode ser atualizada a qualquer momento. Mudanças relevantes serão comunicadas por e-mail ou aviso no site. O uso continuado da loja após a publicação implica aceitação das alterações.</p>
                                <p class="text-xs text-gray-400">Última atualização: {{ date('d/m/Y') }}</p>
                            </div>
                        </div>

                    </div>

                    <aside class="space-y-6">
                        <div class="bg-black p-6 text-white">
                            <h2 class="font-mono text-[10px] uppercase tracking-widest text-gray-400 mb-3">Dúvidas ou solicitações</h2>
                            <p class="text-sm text-gray-300 mb-5">Para exercer seus direitos ou esclarecer dúvidas sobre o tratamento dos seus dados, entre em contato.</p>
                            <a href="{{ route('site.contato') }}" class="inline-block bg-white text-black font-mono uppercase text-[10px] tracking-widest px-6 py-3 hover:bg-gray-100 transition-colors">Fale Conosco</a>
                        </div>

                        <div class="bg-white border border-[var(--color-lab-border)] p-6">
                            <h2 class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-4">Documentos relacionados</h2>
                            <div class="space-y-3">
                                <a href="{{ route('site.termos') }}" class="block border border-[var(--color-lab-border)] px-4 py-3 text-sm hover:border-black transition-colors">Termos e Condições</a>
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
