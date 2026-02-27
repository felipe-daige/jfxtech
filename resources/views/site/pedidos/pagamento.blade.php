<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pagamento - JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
</head>
<body class="min-h-screen flex flex-col bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)] antialiased">
    @include('includes.header')

    <main class="flex-grow">
    <!-- Hero Section -->
    <div class="bg-white border-b border-[var(--color-lab-border)] py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex text-xs font-mono text-gray-500 uppercase tracking-widest mb-6">
                <a href="{{ route('site.index') }}" class="hover:text-black transition-colors">HOME</a>
                <svg class="w-4 h-4 mx-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                <span class="text-black">PAGAMENTO</span>
            </nav>
            <h1 class="text-4xl font-bold tracking-tight mb-2">FINALIZAR PAGAMENTO</h1>
            <p class="text-gray-500 font-mono text-sm">COMPLETE SEU PEDIDO COM SEGURANÇA</p>
        </div>
    </div>

    <!-- Payment Content -->
    <section class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-6xl mx-auto">
                <!-- Progress Steps -->
                <div class="mb-8">
                    <div class="flex items-center justify-center space-x-4">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-black text-white flex items-center justify-center font-mono text-xs font-bold">&#10003;</div>
                            <span class="ml-2 text-black font-mono text-xs font-bold uppercase tracking-widest">Endereço</span>
                        </div>
                        <div class="w-12 h-px bg-black"></div>
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-black text-white flex items-center justify-center font-mono text-xs font-bold">2</div>
                            <span class="ml-2 text-black font-mono text-xs font-bold uppercase tracking-widest">Pagamento</span>
                        </div>
                        <div class="w-12 h-px bg-black"></div>
                        <div class="flex items-center">
                            <div class="w-8 h-8 border border-[var(--color-lab-border)] text-gray-500 flex items-center justify-center font-mono text-xs">3</div>
                            <span class="ml-2 text-gray-500 font-mono text-xs uppercase tracking-widest">Confirmação</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Payment Steps -->
                    <div class="lg:col-span-2">
                        <!-- Step 1: Confirm Order -->
                        <div id="step-1" class="bg-white border border-[var(--color-lab-border)] p-6 mb-6">
                            <h2 class="text-lg font-mono font-bold uppercase tracking-widest mb-6">1. Confirmar Pedido</h2>

                            <!-- Order Items -->
                            <div class="space-y-4 mb-6">
                                @foreach($pedido->itens as $item)
                                <div class="flex items-center space-x-4 p-4 bg-[var(--color-lab-bg)]">
                                    <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center flex-shrink-0">
                                        @if($item->produto->primeira_imagem)
                                            <img src="/storage/{{ $item->produto->primeira_imagem }}" alt="{{ $item->produto->nome }}" class="w-full h-full object-cover rounded-lg">
                                        @else
                                            <svg class="w-6 h-6 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-bold text-gray-900 text-sm truncate">{{ $item->produto->nome }}</h4>
                                        <p class="text-gray-600 text-xs">Quantidade: {{ $item->quantidade }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-mono font-bold">R$ {{ number_format($item->preco, 2, ',', '.') }}</p>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <!-- Order Summary -->
                            <div class="border-t pt-6">
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Subtotal:</span>
                                        <span class="font-semibold">R$ {{ number_format($pedido->valor_total, 2, ',', '.') }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Frete:</span>
                                        <span class="font-semibold">R$ 15,00</span>
                                    </div>
                                    <div class="border-t pt-3">
                                        <div class="flex justify-between text-lg font-bold">
                                            <span>Total:</span>
                                            <span class="font-mono font-bold">R$ {{ number_format($pedido->valor_total + 15, 2, ',', '.') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Confirm Button -->
                            <div class="mt-6">
                                <button id="confirm-order-btn"
                                        class="w-full bg-black text-white py-3 px-6 font-bold tracking-widest uppercase text-sm hover:bg-gray-900 transition-colors">
                                    <span class="flex items-center justify-center">
                                        <svg class="w-4 h-4 mr-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                                        Continuar para Pagamento
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Step 2: Address (Hidden by default) -->
                        <div id="step-2" class="bg-white border border-[var(--color-lab-border)] p-6 mb-6 hidden">
                            <h2 class="text-lg font-mono font-bold uppercase tracking-widest mb-6">2. Endereço de Entrega</h2>

                            @if($pedido->endereco)
                                <!-- Show existing address -->
                                <div class="bg-[var(--color-lab-bg)] border border-[var(--color-lab-border)] p-4 mb-6">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <h3 class="font-mono text-sm font-bold mb-2">Endereço Selecionado</h3>
                                            <p class="text-gray-700">{{ $pedido->endereco->rua }}, {{ $pedido->endereco->numero }}</p>
                                            @if($pedido->endereco->complemento)
                                                <p class="text-gray-700">{{ $pedido->endereco->complemento }}</p>
                                            @endif
                                            <p class="text-gray-700">{{ $pedido->endereco->bairro }}, {{ $pedido->endereco->cidade }}/{{ $pedido->endereco->estado }}</p>
                                            <p class="text-gray-700">CEP: {{ $pedido->endereco->cep_formatado }}</p>
                                        </div>
                                        <button id="change-address-btn" class="text-black font-mono text-xs font-bold uppercase tracking-widest hover:underline">
                                            Alterar
                                        </button>
                                    </div>
                                </div>
                            @endif

                            <!-- Address Form (Hidden if address exists) -->
                            <div id="address-form" class="{{ $pedido->endereco ? 'hidden' : '' }}">
                                <form id="endereco-form" class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="md:col-span-1">
                                            <label class="block text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500 mb-2">CEP</label>
                                            <input type="text" name="cep" id="cep" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black transition-colors"
                                                   placeholder="00000-000" maxlength="9" required>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500 mb-2">Rua</label>
                                            <input type="text" name="rua" id="rua" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black transition-colors"
                                                   placeholder="Nome da rua" required>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500 mb-2">Número</label>
                                            <input type="text" name="numero" id="numero" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black transition-colors"
                                                   placeholder="123" required>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500 mb-2">Complemento</label>
                                            <input type="text" name="complemento" id="complemento" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black transition-colors"
                                                   placeholder="Apartamento, casa, etc.">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500 mb-2">Bairro</label>
                                            <input type="text" name="bairro" id="bairro" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black transition-colors"
                                                   placeholder="Nome do bairro" required>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500 mb-2">Cidade</label>
                                            <input type="text" name="cidade" id="cidade" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black transition-colors"
                                                   placeholder="Nome da cidade" required>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-mono font-bold uppercase tracking-widest text-gray-500 mb-2">Estado</label>
                                            <select name="estado" id="estado" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black transition-colors" required>
                                                <option value="">Selecione</option>
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

                            <!-- Continue Button -->
                            <div class="mt-6">
                                <button id="continue-address-btn"
                                        class="w-full bg-black text-white py-3 px-6 font-bold tracking-widest uppercase text-sm hover:bg-gray-900 transition-colors">
                                    <span class="flex items-center justify-center">
                                        <svg class="w-4 h-4 mr-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                                        Continuar para Método de Pagamento
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Step 3: Payment Method (Hidden by default) -->
                        <div id="step-3" class="bg-white border border-[var(--color-lab-border)] p-6 mb-6 hidden">
                            <h2 class="text-lg font-mono font-bold uppercase tracking-widest mb-6">3. Método de Pagamento</h2>

                            <div class="space-y-4">
                                <!-- PIX Option -->
                                <label class="flex items-center p-4 border-2 border-[var(--color-lab-border)] cursor-pointer hover:bg-gray-50 payment-option" data-method="pix">
                                    <input type="radio" name="payment_method" value="pix" class="mr-4 text-black focus:ring-black">
                                    <div class="flex-1">
                                        <div class="flex items-center">
                                            <svg class="w-6 h-6 mr-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                                            <div>
                                                <div class="font-bold text-gray-900">PIX</div>
                                                <div class="text-sm text-gray-600">Pagamento instantâneo e seguro</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="selection-indicator text-black font-bold hidden">&#10003;</div>
                                </label>

                                <!-- Credit Card Option -->
                                <label class="flex items-center p-4 border-2 border-[var(--color-lab-border)] cursor-pointer hover:bg-gray-50 payment-option" data-method="credit">
                                    <input type="radio" name="payment_method" value="credit" class="mr-4 text-black focus:ring-black">
                                    <div class="flex-1">
                                        <div class="flex items-center">
                                            <svg class="w-6 h-6 mr-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                            <div>
                                                <div class="font-bold text-gray-900">Cartão de Crédito</div>
                                                <div class="text-sm text-gray-600">Visa, Mastercard, Elo</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="selection-indicator text-gray-400 hidden">&#10003;</div>
                                </label>

                                <!-- Debit Card Option -->
                                <label class="flex items-center p-4 border-2 border-[var(--color-lab-border)] cursor-pointer hover:bg-gray-50 payment-option" data-method="debit">
                                    <input type="radio" name="payment_method" value="debit" class="mr-4 text-black focus:ring-black">
                                    <div class="flex-1">
                                        <div class="flex items-center">
                                            <svg class="w-6 h-6 mr-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                            <div>
                                                <div class="font-bold text-gray-900">Cartão de Débito</div>
                                                <div class="text-sm text-gray-600">Visa, Mastercard</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="selection-indicator text-gray-400 hidden">&#10003;</div>
                                </label>
                            </div>

                            <!-- Finalize Payment Button -->
                            <div class="mt-6">
                                <button id="finalize-payment-btn"
                                        class="w-full bg-black text-white py-3 px-6 font-bold tracking-widest uppercase text-sm hover:bg-gray-900 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                        disabled>
                                    <span class="flex items-center justify-center">
                                        <svg class="w-4 h-4 mr-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                        Finalizar Pagamento
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary Sidebar -->
                    <div class="lg:col-span-1">
                        <div class="bg-white border border-[var(--color-lab-border)] p-6 sticky top-6">
                            <h3 class="text-lg font-mono font-bold uppercase tracking-widest mb-6">Resumo do Pedido</h3>

                            <!-- Order Items -->
                            <div class="space-y-3 mb-6">
                                @foreach($pedido->itens as $item)
                                <div class="flex items-center space-x-3">
                                    <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center flex-shrink-0">
                                        @if($item->produto->primeira_imagem)
                                            <img src="/storage/{{ $item->produto->primeira_imagem }}" alt="{{ $item->produto->nome }}" class="w-full h-full object-cover rounded-lg">
                                        @else
                                            <svg class="w-6 h-6 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-bold text-gray-900 text-xs truncate">{{ $item->produto->nome }}</h4>
                                        <p class="text-gray-600 text-xs">Qtd: {{ $item->quantidade }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-mono font-bold text-sm">R$ {{ number_format($item->preco, 2, ',', '.') }}</p>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <!-- Order Total -->
                            <div class="border-t pt-4">
                                <div class="space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Subtotal:</span>
                                        <span class="font-semibold">R$ {{ number_format($pedido->valor_total, 2, ',', '.') }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Frete:</span>
                                        <span class="font-semibold">R$ 15,00</span>
                                    </div>
                                    <div class="border-t pt-2">
                                        <div class="flex justify-between text-lg font-bold">
                                            <span>Total:</span>
                                            <span class="font-mono font-bold">R$ {{ number_format($pedido->valor_total + 15, 2, ',', '.') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Security Info -->
                            <div class="mt-4 text-center">
                                <div class="flex items-center justify-center text-sm text-gray-600">
                                    <svg class="w-4 h-4 mr-2 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                    Compra 100% segura e protegida
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let currentStep = 1;

        // Step 1: Confirm Order
        const confirmOrderBtn = document.getElementById('confirm-order-btn');
        if (confirmOrderBtn) {
            confirmOrderBtn.addEventListener('click', function() {
                // Se já tem endereço, pular direto para pagamento
                @if($pedido->endereco)
                showStep(3);
                @else
                showStep(2);
                @endif
            });
        }

        // Step 2: Address
        const continueAddressBtn = document.getElementById('continue-address-btn');
        if (continueAddressBtn) {
            continueAddressBtn.addEventListener('click', function() {
                // Só validar se não tem endereço salvo
                @if(!$pedido->endereco)
                if (validateAddressForm()) {
                    showStep(3);
                }
                @else
                showStep(3);
                @endif
            });
        }

        // Change address button
        const changeAddressBtn = document.getElementById('change-address-btn');
        if (changeAddressBtn) {
            changeAddressBtn.addEventListener('click', function() {
                document.getElementById('address-form').classList.remove('hidden');
            });
        }

        // Step 3: Payment Method
        const paymentOptions = document.querySelectorAll('.payment-option');
        const finalizePaymentBtn = document.getElementById('finalize-payment-btn');

        paymentOptions.forEach(option => {
            option.addEventListener('click', function() {
                // Remove selection from all options
                paymentOptions.forEach(opt => {
                    opt.classList.remove('border-black', 'bg-[var(--color-lab-bg)]');
                    opt.classList.add('border-[var(--color-lab-border)]');
                    const indicator = opt.querySelector('.selection-indicator');
                    if (indicator) {
                        indicator.classList.add('hidden');
                        indicator.classList.remove('text-black');
                        indicator.classList.add('text-gray-400');
                    }
                });

                // Select current option
                this.classList.remove('border-[var(--color-lab-border)]');
                this.classList.add('border-black', 'bg-[var(--color-lab-bg)]');
                const indicator = this.querySelector('.selection-indicator');
                if (indicator) {
                    indicator.classList.remove('hidden', 'text-gray-400');
                    indicator.classList.add('text-black');
                }

                // Enable finalize button
                if (finalizePaymentBtn) {
                    finalizePaymentBtn.disabled = false;
                    finalizePaymentBtn.classList.remove('disabled:opacity-50', 'disabled:cursor-not-allowed');
                }
            });
        });

        // Finalize payment
        if (finalizePaymentBtn) {
            finalizePaymentBtn.addEventListener('click', function() {
                finalizePayment();
            });
        }

        // CEP mask
        const cepInput = document.getElementById('cep');
        if (cepInput) {
            cepInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                value = value.replace(/(\d{5})(\d{3})/, '$1-$2');
                e.target.value = value;
            });
        }
    });

    // Show step function
    function showStep(step) {
        // Hide all steps
        document.querySelectorAll('[id^="step-"]').forEach(stepEl => {
            stepEl.classList.add('hidden');
        });

        // Show current step
        const currentStepEl = document.getElementById(`step-${step}`);
        if (currentStepEl) {
            currentStepEl.classList.remove('hidden');
        }
    }

    // Validate address form
    function validateAddressForm() {
        const requiredFields = ['cep', 'rua', 'numero', 'bairro', 'cidade', 'estado'];
        let isValid = true;

        requiredFields.forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (field && !field.value.trim()) {
                field.classList.add('border-red-500');
                isValid = false;
            } else if (field) {
                field.classList.remove('border-red-500');
            }
        });

        if (!isValid) {
            alert('Por favor, preencha todos os campos obrigatórios.');
        }

        return isValid;
    }

    // Finalize payment
    function finalizePayment() {
        const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
        if (!selectedMethod) {
            alert('Por favor, selecione um método de pagamento.');
            return;
        }

        const finalizeBtn = document.getElementById('finalize-payment-btn');
        const originalContent = finalizeBtn.innerHTML;

        // Disable button
        finalizeBtn.disabled = true;
        finalizeBtn.innerHTML = '<span class="flex items-center justify-center">PROCESSANDO...</span>';

        // Here you would integrate with payment gateway
        // For now, just simulate success
        setTimeout(() => {
            alert('Pagamento processado com sucesso!');
            // Redirect to success page
            window.location.href = '/pedidos/sucesso';
        }, 2000);
    }
    </script>

    @include('includes.footer')
</body>
</html>
