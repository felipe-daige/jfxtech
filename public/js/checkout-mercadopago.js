(function () {
    let brickController = null;

    function money(value) {
        return Number(value || 0).toFixed(2).replace('.', ',');
    }

    function showFeedback(type, html) {
        const feedback = document.getElementById('paymentBrick_feedback');

        if (!feedback) {
            return;
        }

        feedback.classList.remove('hidden');
        feedback.classList.remove('border-red-300', 'border-green-300', 'border-yellow-300');

        if (type === 'error') {
            feedback.classList.add('border-red-300');
        } else if (type === 'success') {
            feedback.classList.add('border-green-300');
        } else {
            feedback.classList.add('border-yellow-300');
        }

        feedback.innerHTML = html;
    }

    function renderInstructions(payment) {
        const instructions = payment.instructions || {};
        const blocks = [];

        blocks.push('<div class="space-y-3">');
        blocks.push('<h4 class="font-mono text-xs uppercase tracking-widest text-gray-500">Status do pagamento</h4>');
        blocks.push('<p class="text-sm font-semibold text-black">Status: ' + payment.status + '</p>');

        if (payment.status_detail) {
            blocks.push('<p class="text-sm text-gray-600">Detalhe: ' + payment.status_detail + '</p>');
        }

        if (instructions.qr_code_base64) {
            blocks.push('<img alt="QR Code Pix" class="w-48 h-48 border border-[var(--color-lab-border)] p-2 bg-white" src="data:image/png;base64,' + instructions.qr_code_base64 + '">');
        }

        if (instructions.qr_code) {
            blocks.push('<div>');
            blocks.push('<p class="text-xs text-gray-500 mb-2">Copia e cola Pix</p>');
            blocks.push('<textarea readonly class="w-full min-h-28 border border-[var(--color-lab-border)] p-3 text-xs font-mono">' + instructions.qr_code + '</textarea>');
            blocks.push('</div>');
        }

        if (instructions.ticket_url) {
            blocks.push('<a class="inline-flex items-center justify-center border border-black px-4 py-3 text-xs font-mono uppercase tracking-widest hover:bg-black hover:text-white transition-colors" target="_blank" rel="noopener noreferrer" href="' + instructions.ticket_url + '">Abrir boleto</a>');
        }

        if (payment.redirect_url) {
            blocks.push('<a class="inline-flex items-center justify-center border border-[var(--color-lab-border)] px-4 py-3 text-xs font-mono uppercase tracking-widest hover:border-black transition-colors ml-2" href="' + payment.redirect_url + '">Ver pedido</a>');
        }

        blocks.push('</div>');

        return blocks.join('');
    }

    async function pollStatus(statusUrl) {
        if (!statusUrl) {
            return;
        }

        try {
            const response = await fetch(statusUrl, {
                headers: {
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
            });

            const data = await response.json();

            if (!data.success || !data.payment) {
                return;
            }

            if (data.pedido.status === 'pago') {
                showFeedback('success', renderInstructions({
                    ...data.payment,
                    redirect_url: statusUrl.replace('/checkout/mercado-pago/status/', '/pedidos/'),
                }));
                return;
            }

            if (data.pedido.status === 'pendente' || data.pedido.status === 'processando') {
                window.setTimeout(function () {
                    pollStatus(statusUrl);
                }, 5000);
            }
        } catch (error) {
            console.error('Erro ao consultar status do pagamento', error);
        }
    }

    async function mount(checkout) {
        if (!window.MercadoPago) {
            showFeedback('error', '<p class="text-sm text-red-600">SDK do Mercado Pago não carregado.</p>');
            return;
        }

        if (!checkout.public_key) {
            showFeedback('error', '<p class="text-sm text-red-600">Configure `MERCADO_PAGO_PUBLIC_KEY` antes de usar o checkout.</p>');
            return;
        }

        if (brickController && typeof brickController.unmount === 'function') {
            await brickController.unmount();
        }

        const mp = new window.MercadoPago(checkout.public_key, {
            locale: 'pt-BR',
        });

        const bricksBuilder = mp.bricks();

        brickController = await bricksBuilder.create('payment', 'paymentBrick_container', {
            initialization: {
                amount: checkout.amount,
                payer: checkout.payer,
            },
            customization: {
                paymentMethods: {
                    creditCard: 'all',
                    debitCard: 'all',
                    ticket: 'all',
                    bankTransfer: 'all',
                },
            },
            callbacks: {
                onReady: function () {
                    showFeedback('pending', '<p class="text-sm text-gray-600">Brick carregado. Preencha os dados para concluir o pagamento.</p>');
                },
                onSubmit: async function (formData) {
                    const response = await fetch(checkout.urls.pay, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            pedido_id: checkout.pedido_id,
                            transaction_amount: checkout.amount,
                            ...formData,
                        }),
                    });

                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        const message = data.message || 'Não foi possível processar o pagamento.';
                        showFeedback('error', '<p class="text-sm text-red-600">' + message + '</p>');
                        throw new Error(message);
                    }

                    if (data.payment.status === 'approved') {
                        showFeedback('success', '<div class="space-y-3"><p class="text-sm font-semibold text-green-700">Pagamento aprovado.</p><p class="text-sm text-gray-600">Total pago: R$ ' + money(checkout.amount) + '</p><a class="inline-flex items-center justify-center border border-black px-4 py-3 text-xs font-mono uppercase tracking-widest hover:bg-black hover:text-white transition-colors" href="' + data.payment.redirect_url + '">Ver pedido</a></div>');
                        return;
                    }

                    showFeedback('pending', renderInstructions(data.payment));
                    pollStatus(checkout.urls.status);
                },
                onError: function (error) {
                    console.error(error);
                    showFeedback('error', '<p class="text-sm text-red-600">Ocorreu um erro ao carregar ou enviar o Payment Brick.</p>');
                },
            },
        });
    }

    window.checkoutMercadoPago = {
        mount: mount,
    };
})();
