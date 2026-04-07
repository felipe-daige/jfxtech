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
    @php($subtotalProdutos = $carrinho->itens->sum(fn ($item) => $item->preco * $item->quantidade))
    @include('includes.header')

    <main class="flex-grow">
    <div class="bg-white border-b border-[var(--color-lab-border)] py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex text-xs font-mono text-gray-500 uppercase tracking-widest mb-6">
                <a href="{{ route('site.index') }}" class="hover:text-black transition-colors">HOME</a>
                <svg class="w-4 h-4 mx-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                <span class="text-black">FINALIZAR COMPRA</span>
            </nav>
            <h1 id="checkout-title" class="text-4xl font-bold tracking-tight mb-2">ENDEREÇO DE ENTREGA</h1>
            <p id="checkout-subtitle" class="text-gray-500 font-mono text-sm">CONFIRME SEU ENDEREÇO PARA CONTINUAR</p>
        </div>
    </div>

    @if(config('services.frete_gratis_ativo'))
    <div class="bg-black text-white py-3 px-4">
        <div class="max-w-7xl mx-auto flex items-center justify-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <p class="font-mono text-xs font-bold uppercase tracking-widest">Frete Grátis por Tempo Limitado &mdash; Aproveite antes que acabe!</p>
        </div>
    </div>
    @endif

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
                            <div class="mb-4">
                                <label for="payer-document" class="block text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500 mb-2">CPF do pagador</label>
                                <input
                                    type="text"
                                    id="payer-document"
                                    name="payer_document"
                                    placeholder="000.000.000-00"
                                    aria-describedby="payer-document-error"
                                    aria-invalid="false"
                                    class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black transition-colors"
                                >
                                <div
                                    id="payer-document-error"
                                    class="hidden mt-3 border border-red-200 bg-white px-4 py-3"
                                    role="alert"
                                >
                                    <div class="flex items-start gap-3">
                                        <svg class="w-4 h-4 mt-0.5 text-red-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <circle cx="12" cy="12" r="9"></circle>
                                            <path d="M12 8v5"></path>
                                            <path d="M12 16h.01"></path>
                                        </svg>
                                        <div>
                                            <p class="text-[10px] font-mono font-bold uppercase tracking-widest text-red-500">CPF obrigatório</p>
                                            <p class="mt-1 text-sm text-gray-700">Informe um CPF válido do pagador para continuar com o checkout.</p>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">Usado para concluir o pagamento com Pix no Mercado Pago.</p>
                            </div>

                            <!-- Cupom de desconto -->
                            <div class="border border-[var(--color-lab-border)] p-3 mb-1">
                                <div id="cupom-form">
                                    <div class="flex gap-2">
                                        <input
                                            type="text"
                                            id="cupom-input"
                                            placeholder="Código do cupom"
                                            class="flex-1 border border-[var(--color-lab-border)] px-3 py-2 text-sm font-mono uppercase focus:outline-none focus:border-black"
                                            maxlength="50"
                                        >
                                        <button
                                            type="button"
                                            id="cupom-btn"
                                            class="bg-black text-white px-4 py-2 text-xs font-mono uppercase tracking-widest hover:bg-gray-900 transition-colors whitespace-nowrap"
                                        >
                                            Aplicar
                                        </button>
                                    </div>
                                    <p id="cupom-erro" class="text-red-600 text-xs mt-1 hidden"></p>
                                </div>
                                <div id="cupom-aplicado" class="hidden">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-mono text-green-700" id="cupom-label"></span>
                                        <button type="button" id="cupom-remover-btn" class="text-xs text-gray-500 underline hover:text-black ml-2">remover</button>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="font-semibold">R$ {{ number_format($subtotalProdutos, 2, ',', '.') }}</span>
                            </div>

                            <div class="flex justify-between text-sm" id="desconto-resumo" style="display:none">
                                <span class="text-gray-600">Desconto:</span>
                                <span class="font-semibold text-green-700" id="desconto-valor">- R$ 0,00</span>
                            </div>

                            <!-- Opções de Frete -->
                            <div id="opcoes-frete" class="hidden">
                                <h4 class="font-mono text-sm font-bold mb-3">Escolha o frete:</h4>
                                <div class="space-y-3">
                                    @if(config('services.frete_gratis_ativo'))
                                    <label id="frete-gratis-option" class="hidden relative flex items-center p-3 border-2 border-green-500 cursor-pointer hover:bg-green-50 frete-option" data-tipo="gratis">
                                        <span class="absolute -top-2.5 right-2 bg-black text-white text-[9px] font-mono font-bold uppercase tracking-widest px-2 py-0.5">Tempo Limitado</span>
                                        <input type="radio" name="frete" value="gratis" class="mr-3 text-black focus:ring-black">
                                        <div class="flex-1">
                                            <div class="font-mono text-sm font-bold text-green-700">Frete Grátis</div>
                                            <div class="text-sm text-gray-600">Promoção por tempo limitado &middot; 5&ndash;7 dias úteis</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="font-mono font-bold text-green-700" id="gratis-valor">R$ 0,00</div>
                                        </div>
                                    </label>
                                    @endif
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
                                    <span class="font-mono font-bold" id="total-valor">R$ {{ number_format($subtotalProdutos, 2, ',', '.') }}</span>
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
    const savedFreteType = @json($carrinho->frete_tipo);
    const savedFreteValue = {{ $carrinho->frete_valor !== null ? (float) $carrinho->frete_valor : 'null' }};
    const cupomAplicado = {
        codigo: @json($carrinho->cupom_codigo),
        desconto: parseFloat('{{ $carrinho->valor_desconto ?? 0 }}'),
    };
    let checkoutPayerDocument = '';

    $(document).ready(function() {
        // CEP mask
        $('#cep').mask('00000-000');
        $('#numero').mask('0000000000');
        $('#payer-document').mask('000.000.000-00');

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

        $('#payer-document').on('input blur', function() {
            const isValid = ($(this).val() || '').replace(/\D/g, '').length === 11;

            if (isValid) {
                setPayerDocumentError(false);
            }
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

                    // Frete grátis por tempo limitado
                    if (data.opcoes.gratis) {
                        $('#frete-gratis-option').removeClass('hidden');
                    } else {
                        $('#frete-gratis-option').addClass('hidden');
                    }

                    $('#opcoes-frete').removeClass('hidden');

                    const freteTipoInicial = savedFreteType && data.opcoes[savedFreteType]
                        ? savedFreteType
                        : (data.opcoes.gratis ? 'gratis' : 'pac');

                    $('input[name="frete"][value="' + freteTipoInicial + '"]').prop('checked', true);
                    atualizarFreteSelecionado(data.opcoes[freteTipoInicial].valor);
                } else {
                    $('#opcoes-frete').removeClass('hidden');
                    $('#frete-valor').text(savedFreteValue !== null ? 'R$ ' + savedFreteValue.toFixed(2).replace('.', ',') : 'Erro ao calcular');
                }
            },
            error: function() {
                $('#opcoes-frete').removeClass('hidden');
                $('#frete-valor').text(savedFreteValue !== null ? 'R$ ' + savedFreteValue.toFixed(2).replace('.', ',') : 'Erro ao calcular');
            }
        });
    }

    // --- Cupom de desconto ---
    let descontoAtual = cupomAplicado.desconto || 0;
    let freteAtual = (typeof savedFreteValue === 'number' && savedFreteValue > 0) ? savedFreteValue : 0;

    function formatarBRL(valor) {
        return 'R$ ' + valor.toFixed(2).replace('.', ',');
    }

    function atualizarTotalComDesconto(frete) {
        const fv = (typeof frete === 'number') ? frete : freteAtual;
        const subtotal = parseFloat('{{ $subtotalProdutos }}');
        const total = Math.max(0, subtotal - descontoAtual + fv);
        $('#total-valor').text(formatarBRL(total));
    }

    function mostrarCupomAplicado(codigo, desconto, mensagem) {
        descontoAtual = desconto;
        $('#cupom-form').addClass('hidden');
        $('#cupom-aplicado').removeClass('hidden');
        $('#cupom-label').text('✓ ' + mensagem);
        $('#desconto-valor').text('- ' + formatarBRL(desconto));
        $('#desconto-resumo').show();
        atualizarTotalComDesconto();
    }

    function restaurarFormCupom() {
        descontoAtual = 0;
        $('#cupom-form').removeClass('hidden');
        $('#cupom-aplicado').addClass('hidden');
        $('#cupom-input').val('');
        $('#cupom-erro').addClass('hidden').text('');
        $('#desconto-resumo').hide();
        atualizarTotalComDesconto();
    }

    $(document).ready(function () {
        // Restaurar cupom se já estava aplicado (ex: usuário voltou à página)
        if (cupomAplicado.codigo && cupomAplicado.desconto > 0) {
            mostrarCupomAplicado(
                cupomAplicado.codigo,
                cupomAplicado.desconto,
                cupomAplicado.codigo + ' aplicado'
            );
        }

        $('#cupom-btn').on('click', function () {
            const codigo = $('#cupom-input').val().trim();
            if (!codigo) return;

            $('#cupom-btn').prop('disabled', true).text('...');
            $('#cupom-erro').addClass('hidden').text('');

            $.ajax({
                url: '{{ route("cupom.aplicar") }}',
                method: 'POST',
                data: { codigo: codigo, _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    mostrarCupomAplicado(
                        res.codigo,
                        parseFloat(res.desconto.replace(',', '.')),
                        res.mensagem
                    );
                },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Cupom inválido.';
                    $('#cupom-erro').removeClass('hidden').text(msg);
                },
                complete: function () {
                    $('#cupom-btn').prop('disabled', false).text('Aplicar');
                },
            });
        });

        $('#cupom-remover-btn').on('click', function () {
            $.ajax({
                url: '{{ route("cupom.remover") }}',
                method: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function () {
                    restaurarFormCupom();
                },
            });
        });
    });

    // Atualizar frete selecionado
    function atualizarFreteSelecionado(valor) {
        freteAtual = valor;
        $('#frete-valor').text(formatarBRL(valor));
        atualizarTotalComDesconto(valor);
    }

    function setPayerDocumentError(show) {
        const payerDocumentInput = $('#payer-document');
        const payerDocumentError = $('#payer-document-error');

        payerDocumentInput.toggleClass('border-red-500', show);
        payerDocumentInput.attr('aria-invalid', show ? 'true' : 'false');
        payerDocumentError.toggleClass('hidden', !show);

        if (show) {
            payerDocumentInput.trigger('focus');

            const inputElement = payerDocumentInput.get(0);
            if (inputElement && typeof inputElement.scrollIntoView === 'function') {
                inputElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    }

    // Create order
    function createOrder() {
        let dadosEndereco;
        const freteTipo = $('input[name="frete"]:checked').val();
        const payerDocument = ($('#payer-document').val() || '').trim();

        if (!freteTipo) {
            alert('Selecione uma opção de frete antes de continuar.');
            return;
        }

        if (payerDocument.replace(/\D/g, '').length !== 11) {
            setPayerDocumentError(true);
            return;
        }

        setPayerDocumentError(false);
        checkoutPayerDocument = payerDocument;

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
            pais: 'BR',
            frete_tipo: freteTipo
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
            pais: 'BR',
            frete_tipo: freteTipo
        };
        @endif

        // Disable button
        const continueBtn = $('#continue-btn');
        const originalContent = continueBtn.html();
        continueBtn.prop('disabled', true);
        continueBtn.html('PROCESSANDO...');

        $.ajax({
            url: '{{ route('site.checkout.mercadopago.prepare') }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            data: dadosEndereco,
            success: function(data) {
                if (data.success) {
                    renderMercadoPagoCheckout(data.checkout);
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

    function renderMercadoPagoCheckout(checkout) {
        checkout.payer = checkout.payer || {};
        checkout.payer.identification = {
            type: 'CPF',
            number: checkoutPayerDocument.replace(/\D/g, '')
        };

        const paymentContent = `
            <div class="max-w-5xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_320px] gap-8">
                    <div class="bg-white border border-[var(--color-lab-border)] p-8">
                        <div class="flex items-start justify-between gap-6 mb-8">
                            <div>
                                <h2 class="text-2xl font-bold tracking-tight">Pagamento</h2>
                                <p class="text-sm text-gray-500 mt-2">Conclua o pagamento com segurança pelo Mercado Pago.</p>
                            </div>
                            <div class="text-right">
                                <div class="text-[10px] font-mono uppercase tracking-[0.2em] text-gray-500">Total</div>
                                <div class="text-2xl font-bold">R$ ${checkout.amount.toFixed(2).replace('.', ',')}</div>
                            </div>
                        </div>

                        <div id="paymentBrick_container"></div>
                        <div id="paymentBrick_feedback" class="hidden mt-6 border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] p-5"></div>
                    </div>

                    <aside class="bg-white border border-[var(--color-lab-border)] p-6 h-fit">
                        <h3 class="font-mono text-[10px] uppercase tracking-[0.2em] text-gray-500 mb-4">Resumo</h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span>Subtotal</span>
                                <span>R$ ${checkout.subtotal.toFixed(2).replace('.', ',')}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Frete ${checkout.frete.label}</span>
                                <span>R$ ${checkout.frete.valor.toFixed(2).replace('.', ',')}</span>
                            </div>
                            <div class="flex justify-between border-t pt-3 font-bold">
                                <span>Total</span>
                                <span>R$ ${checkout.amount.toFixed(2).replace('.', ',')}</span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-4">O pedido só será confirmado após retorno do gateway/webhook.</p>
                    </aside>
                </div>
            </div>
        `;

        $('section.py-12 .max-w-7xl').html(paymentContent);
        $('#checkout-title').text('FINALIZAR PAGAMENTO');
        $('#checkout-subtitle').text('PAGUE COM O PAYMENT BRICK DO MERCADO PAGO');

        if (window.checkoutMercadoPago) {
            window.checkoutMercadoPago.mount(checkout);
        }
    }
    </script>
    <script src="https://sdk.mercadopago.com/js/v2"></script>
    <script src="{{ asset('js/checkout-mercadopago.js') }}?v={{ filemtime(public_path('js/checkout-mercadopago.js')) }}"></script>
</body>
</html>
