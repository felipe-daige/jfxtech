<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Endereço de Entrega - JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
</head>
<body class="min-h-screen flex flex-col bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)] antialiased">
    @include('includes.header')

    <main class="flex-grow">
    <div class="bg-white border-b border-[var(--color-lab-border)] py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex text-xs font-mono text-gray-500 uppercase tracking-widest mb-6">
                <a href="{{ route('site.index') }}" class="hover:text-black transition-colors">HOME</a>
                <svg class="w-4 h-4 mx-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                <span class="text-black">FINALIZAR COMPRA</span>
            </nav>
            <h1 class="text-4xl font-bold tracking-tight mb-2">ENDEREÇO DE ENTREGA</h1>
            <p class="text-gray-500 font-mono text-sm">CONFIRME SEU ENDEREÇO PARA CONTINUAR</p>
        </div>
    </div>

    <section class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Address Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white border border-[var(--color-lab-border)] p-6">
                        <h2 class="text-2xl font-bold tracking-tight mb-6">Endereço de Entrega</h2>

                        @if($enderecos->count() > 0)
                        <!-- Endereço Salvo -->
                        <div class="mb-6">
                            <h4 class="text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500 mb-4">Endereço de Entrega</h4>
                            <div class="bg-[var(--color-lab-bg)] border border-[var(--color-lab-border)] p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-mono text-sm font-bold">{{ $enderecos->first()->rua }}, {{ $enderecos->first()->numero }}</p>
                                        <p class="text-sm text-gray-600">{{ $enderecos->first()->bairro }}, {{ $enderecos->first()->cidade }}/{{ $enderecos->first()->estado }}</p>
                                        <p class="text-sm text-gray-500">CEP: {{ $enderecos->first()->cep_formatado }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-black font-mono text-xs font-bold uppercase tracking-widest">✓ Endereço confirmado</p>
                                        <a href="{{ route('site.perfil') }}" class="text-xs text-blue-600 hover:text-blue-800">
                                            Alterar no perfil
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @else
                        <!-- Formulário de Endereço -->
                        <div class="border-t pt-6">
                            <h4 class="text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500 mb-4">Cadastre seu endereço</h4>

                            <form id="endereco-form" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="md:col-span-1">
                                        <label class="block text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500 mb-2">CEP</label>
                                        <input type="text" id="cep" name="cep" placeholder="00000-000"
                                               class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black transition-colors">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500 mb-2">Rua</label>
                                        <input type="text" id="rua" name="rua" placeholder="Nome da rua"
                                               class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black transition-colors">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500 mb-2">Número</label>
                                        <input type="text" id="numero" name="numero" placeholder="123"
                                               class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black transition-colors">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500 mb-2">Complemento</label>
                                        <input type="text" id="complemento" name="complemento" placeholder="Apto, casa, etc."
                                               class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black transition-colors">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500 mb-2">Bairro</label>
                                        <input type="text" id="bairro" name="bairro" placeholder="Nome do bairro"
                                               class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black transition-colors">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500 mb-2">Cidade</label>
                                        <input type="text" id="cidade" name="cidade" placeholder="Nome da cidade"
                                               class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black transition-colors">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500 mb-2">Estado</label>
                                        <select id="estado" name="estado"
                                                class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black transition-colors">
                                            <option value="">Selecione o estado</option>
                                            <option value="AC">Acre</option>
                                            <option value="AL">Alagoas</option>
                                            <option value="AP">Amapá</option>
                                            <option value="AM">Amazonas</option>
                                            <option value="BA">Bahia</option>
                                            <option value="CE">Ceará</option>
                                            <option value="DF">Distrito Federal</option>
                                            <option value="ES">Espírito Santo</option>
                                            <option value="GO">Goiás</option>
                                            <option value="MA">Maranhão</option>
                                            <option value="MT">Mato Grosso</option>
                                            <option value="MS">Mato Grosso do Sul</option>
                                            <option value="MG">Minas Gerais</option>
                                            <option value="PA">Pará</option>
                                            <option value="PB">Paraíba</option>
                                            <option value="PR">Paraná</option>
                                            <option value="PE">Pernambuco</option>
                                            <option value="PI">Piauí</option>
                                            <option value="RJ">Rio de Janeiro</option>
                                            <option value="RN">Rio Grande do Norte</option>
                                            <option value="RS">Rio Grande do Sul</option>
                                            <option value="RO">Rondônia</option>
                                            <option value="RR">Roraima</option>
                                            <option value="SC">Santa Catarina</option>
                                            <option value="SP">São Paulo</option>
                                            <option value="SE">Sergipe</option>
                                            <option value="TO">Tocantins</option>
                                        </select>
                                    </div>
                                </div>
                            </form>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white border border-[var(--color-lab-border)] p-6 sticky top-6">
                        <h3 class="text-xl font-bold tracking-tight mb-6">Resumo do Pedido</h3>

                        <!-- Order Items -->
                        <div class="space-y-4 mb-6">
                            @foreach($carrinho->itens as $item)
                            <div class="flex items-center space-x-3">
                                <div class="w-16 h-16 bg-gray-100 rounded-xl overflow-hidden">
                                    @if($item->produto->imagens->count() > 0)
                                        <img src="{{ asset('storage/' . $item->produto->imagens->first()->caminho) }}"
                                             alt="{{ $item->produto->nome }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-mono text-sm font-bold">{{ $item->produto->nome }}</h4>
                                    <p class="text-sm text-gray-600">Qtd: {{ $item->quantidade }}</p>
                                    <p class="text-sm font-mono font-bold">R$ {{ number_format($item->preco * $item->quantidade, 2, ',', '.') }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <!-- Order Summary -->
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="font-semibold">R$ {{ number_format($carrinho->valor_total, 2, ',', '.') }}</span>
                            </div>

                            <!-- Opções de Frete -->
                            <div id="opcoes-frete" class="hidden">
                                <h4 class="font-mono text-sm font-bold mb-3">Escolha o frete:</h4>
                                <div class="space-y-3">
                                    <label class="flex items-center p-3 border border-[var(--color-lab-border)] cursor-pointer hover:bg-gray-50 frete-option" data-tipo="pac">
                                        <input type="radio" name="frete" value="pac" class="mr-3 text-black focus:ring-black">
                                        <div class="flex-1">
                                            <div class="font-mono text-sm font-bold">PAC</div>
                                            <div class="text-sm text-gray-600">Entrega em até 7 dias úteis</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="font-mono font-bold" id="pac-valor">-</div>
                                        </div>
                                    </label>
                                    <label class="flex items-center p-3 border border-[var(--color-lab-border)] cursor-pointer hover:bg-gray-50 frete-option" data-tipo="sedex">
                                        <input type="radio" name="frete" value="sedex" class="mr-3 text-black focus:ring-black">
                                        <div class="flex-1">
                                            <div class="font-mono text-sm font-bold">SEDEX</div>
                                            <div class="text-sm text-gray-600">Entrega em até 3 dias úteis</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="font-mono font-bold" id="sedex-valor">-</div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="flex justify-between text-sm" id="frete-resumo">
                                <span class="text-gray-600">Frete:</span>
                                <span class="font-semibold" id="frete-valor">Calculando...</span>
                            </div>
                            <div class="border-t pt-2">
                                <div class="flex justify-between text-lg font-bold">
                                    <span>Total:</span>
                                    <span class="font-mono font-bold" id="total-valor">R$ {{ number_format($carrinho->valor_total, 2, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Continue Button -->
                        <button id="continue-btn" class="w-full mt-6 bg-black text-white font-bold py-3 px-6 tracking-widest uppercase text-sm hover:bg-gray-900 transition-colors">
                            Continuar para Pagamento
                        </button>

                    </div>
                </div>
            </div>
        </div>
    </section>
    </main>

    @include('includes.footer')

    <script>
    $(document).ready(function() {
        // CEP mask
        $('#cep').mask('00000-000');
        $('#numero').mask('0000000000');

        // Buscar CEP
        $('#cep').on('blur', function() {
            const cep = $(this).val().replace(/\D/g, '');
            if (cep.length === 8) {
                buscarCep(cep);
            }
        });

        // Calcular frete inicial
        @if($enderecos->count() > 0)
        const cepSalvo = '{{ $enderecos->first()->cep }}'.replace(/\D/g, '');
        if (cepSalvo.length === 8) {
            calcularFrete(cepSalvo);
        }
        @else
        const cepInicial = $('#cep').val().replace(/\D/g, '');
        if (cepInicial.length === 8) {
            calcularFrete(cepInicial);
        }
        @endif

        // Evento para escolha de frete
        $(document).on('change', 'input[name="frete"]', function() {
            const tipo = $(this).val();
            const valor = parseFloat($('#' + tipo + '-valor').text().replace('R$ ', '').replace(',', '.'));
            atualizarFreteSelecionado(valor);
        });

        // Continue button
        $('#continue-btn').on('click', function() {
            createOrder();
        });

    });

    // Buscar CEP
    function buscarCep(cep) {
        $('#cep').css('background-image', 'url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\'%3E%3Ccircle cx=\'12\' cy=\'12\' r=\'10\' stroke=\'%23000\' stroke-width=\'4\'/%3E%3Cpath d=\'M12 6v6l4 2\' stroke=\'%23000\' stroke-width=\'2\'/%3E%3C/svg%3E")');
        $('#cep').css('background-size', '20px 20px');
        $('#cep').css('background-repeat', 'no-repeat');
        $('#cep').css('background-position', 'right 12px center');

        $.get('/cep/' + cep)
            .done(function(data) {
                if (data.erro) {
                    $('#cep').css('background-image', 'url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\'%3E%3Cpath stroke=\'%23ef4444\' stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M6 18L18 6M6 6l12 12\'/%3E%3C/svg%3E")');
                    $('#cep').css('border-color', '#ef4444');
                } else {
                    $('#cep').css('background-image', 'url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\'%3E%3Cpath stroke=\'%2310b981\' stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M5 13l4 4L19 7\'/%3E%3C/svg%3E")');
                    $('#cep').css('border-color', '#10b981');

                    $('#rua').val(data.logradouro);
                    $('#bairro').val(data.bairro);
                    $('#cidade').val(data.localidade);
                    $('#estado').val(data.uf);

                    // Calcular frete automaticamente após preencher CEP
                    calcularFrete(cep);
                }
            })
            .fail(function() {
                $('#cep').css('background-image', 'url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\'%3E%3Cpath stroke=\'%23ef4444\' stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M6 18L18 6M6 6l12 12\'/%3E%3C/svg%3E")');
                $('#cep').css('border-color', '#ef4444');
            });
    }

    // Calcular frete
    function calcularFrete(cep) {
        $('#frete-valor').text('Calculando...');
        $('#opcoes-frete').addClass('hidden');

        $.ajax({
            url: '/frete/carrinho',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            data: {
                cep_destino: cep
            },
            success: function(data) {
                if (data.success) {
                    // Mostrar opções de frete
                    $('#pac-valor').text('R$ ' + data.opcoes.pac.valor.toFixed(2).replace('.', ','));
                    $('#sedex-valor').text('R$ ' + data.opcoes.sedex.valor.toFixed(2).replace('.', ','));
                    $('#opcoes-frete').removeClass('hidden');

                    // Selecionar PAC por padrão
                    $('input[name="frete"][value="pac"]').prop('checked', true);
                    atualizarFreteSelecionado(data.opcoes.pac.valor);
                } else {
                    $('#frete-valor').text('Erro ao calcular');
                }
            },
            error: function() {
                $('#frete-valor').text('Erro ao calcular');
            }
        });
    }

    // Atualizar frete selecionado
    function atualizarFreteSelecionado(valor) {
        $('#frete-valor').text('R$ ' + valor.toFixed(2).replace('.', ','));

        // Atualizar total
        const subtotal = parseFloat('{{ $carrinho->valor_total }}');
        const total = subtotal + valor;
        $('#total-valor').text('R$ ' + total.toFixed(2).replace('.', ','));
    }

    // Create order
    function createOrder() {
        let dadosEndereco;

        @if($enderecos->count() > 0)
        // Usar endereço salvo
        dadosEndereco = {
            cep: '{{ $enderecos->first()->cep }}',
            rua: '{{ $enderecos->first()->rua }}',
            numero: '{{ $enderecos->first()->numero }}',
            complemento: '{{ $enderecos->first()->complemento }}',
            bairro: '{{ $enderecos->first()->bairro }}',
            cidade: '{{ $enderecos->first()->cidade }}',
            estado: '{{ $enderecos->first()->estado }}',
            pais: 'BR'
        };
        @else
        // Validar formulário se não tiver endereço salvo
        const requiredFields = ['cep', 'rua', 'numero', 'bairro', 'cidade', 'estado'];
        let isValid = true;

        requiredFields.forEach(fieldName => {
            const input = $('#' + fieldName);
            if (!input.val().trim()) {
                input.addClass('border-red-500');
                isValid = false;
            } else {
                input.removeClass('border-red-500');
            }
        });

        if (!isValid) {
            return;
        }

        dadosEndereco = {
            cep: $('#cep').val(),
            rua: $('#rua').val(),
            numero: $('#numero').val(),
            complemento: $('#complemento').val(),
            bairro: $('#bairro').val(),
            cidade: $('#cidade').val(),
            estado: $('#estado').val(),
            pais: 'BR'
        };
        @endif

        // Disable button
        const continueBtn = $('#continue-btn');
        const originalContent = continueBtn.html();
        continueBtn.prop('disabled', true);
        continueBtn.html('PROCESSANDO...');

        $.ajax({
            url: '/pedidos',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            data: dadosEndereco,
            success: function(data) {
                if (data.success) {
                    // Trocar apenas o conteúdo da seção principal
                    const paymentContent = `
                        <div class="max-w-4xl mx-auto">
                            <div class="bg-white border border-[var(--color-lab-border)] p-8">
                                <h2 class="text-2xl font-bold tracking-tight mb-8 text-center">Método de Pagamento</h2>

                                <div class="space-y-4 mb-8">
                                    <label class="flex items-center p-6 border border-[var(--color-lab-border)] cursor-pointer hover:bg-gray-50 payment-option">
                                        <input type="radio" name="payment_method" value="pix" class="mr-4 text-black focus:ring-black">
                                        <div class="flex-1">
                                            <div class="font-mono text-sm font-bold">PIX</div>
                                            <div class="text-sm text-gray-600">Pagamento instantâneo</div>
                                        </div>
                                        <div class="text-right">
                                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                                        </div>
                                    </label>

                                    <label class="flex items-center p-6 border border-[var(--color-lab-border)] cursor-pointer hover:bg-gray-50 payment-option">
                                        <input type="radio" name="payment_method" value="credit" class="mr-4 text-black focus:ring-black">
                                        <div class="flex-1">
                                            <div class="font-mono text-sm font-bold">Cartão de Crédito</div>
                                            <div class="text-sm text-gray-600">Visa, Mastercard, Elo</div>
                                        </div>
                                        <div class="text-right">
                                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                        </div>
                                    </label>

                                    <label class="flex items-center p-6 border border-[var(--color-lab-border)] cursor-pointer hover:bg-gray-50 payment-option">
                                        <input type="radio" name="payment_method" value="debit" class="mr-4 text-black focus:ring-black">
                                        <div class="flex-1">
                                            <div class="font-mono text-sm font-bold">Cartão de Débito</div>
                                            <div class="text-sm text-gray-600">Visa, Mastercard, Elo</div>
                                        </div>
                                        <div class="text-right">
                                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                        </div>
                                    </label>
                                </div>

                                <button id="finalize-payment-btn" class="w-full bg-black text-white font-bold py-3 px-6 tracking-widest uppercase text-sm hover:bg-gray-900 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                    Finalizar Pagamento
                                </button>
                            </div>
                        </div>
                    `;

                    // Trocar apenas o conteúdo da seção
                    $('section.py-12 .max-w-7xl').html(paymentContent);

                    // Atualizar título da página
                    $('h1').text('Finalizar Pagamento');
                    $('p').text('Escolha seu método de pagamento.');

                    // Re-attach payment method selection events
                    $('.payment-option').on('click', function() {
                        $('.payment-option').removeClass('border-black bg-[var(--color-lab-bg)]');
                        $('.payment-option').addClass('border-[var(--color-lab-border)]');
                        $(this).removeClass('border-[var(--color-lab-border)]');
                        $(this).addClass('border-black bg-[var(--color-lab-bg)]');
                        $('#finalize-payment-btn').prop('disabled', false);
                    });

                    // Re-attach finalize payment event
                    $('#finalize-payment-btn').on('click', function() {
                        const selectedMethod = $('input[name="payment_method"]:checked').val();
                        if (!selectedMethod) {
                            alert('Por favor, selecione um método de pagamento.');
                            return;
                        }
                        alert('Pagamento processado com sucesso!');
                        window.location.href = '/pedidos/sucesso';
                    });
                } else {
                    // Show error
                    continueBtn.html('Erro - Tente Novamente');
                    continueBtn.css('background', 'linear-gradient(to right, #ef4444, #dc2626)');

                    setTimeout(function() {
                        continueBtn.prop('disabled', false);
                        continueBtn.html(originalContent);
                        continueBtn.css('background', '#000');
                    }, 3000);
                }
            },
            error: function() {
                // Show error
                continueBtn.html('Erro - Tente Novamente');
                continueBtn.css('background', 'linear-gradient(to right, #ef4444, #dc2626)');

                setTimeout(function() {
                    continueBtn.prop('disabled', false);
                    continueBtn.html(originalContent);
                    continueBtn.css('background', '#000');
                }, 3000);
            }
        });
    }
    </script>
</body>
</html>