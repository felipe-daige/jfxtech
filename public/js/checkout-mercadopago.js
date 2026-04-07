(function () {
    let brickController = null;
    let activeAttemptId = 0;
    let activeStatusPollTimeout = null;
    let activeCheckout = null;
    const runtimeVersion = 'checkout-mp-v4';

    console.info('[MercadoPago]', runtimeVersion, 'script loaded');

    function money(value) {
        return Number(value || 0).toFixed(2).replace('.', ',');
    }

    function firstFilled(values) {
        for (let index = 0; index < values.length; index += 1) {
            const value = values[index];

            if (value !== undefined && value !== null && value !== '') {
                return value;
            }
        }

        return null;
    }

    function readPath(source, path) {
        if (!source || !path) {
            return null;
        }

        const segments = path.split('.');
        let current = source;

        for (let index = 0; index < segments.length; index += 1) {
            if (current === undefined || current === null) {
                return null;
            }

            current = current[segments[index]];
        }

        return current === undefined ? null : current;
    }

    function readValue(source, keys) {
        if (!source) {
            return null;
        }

        const keyList = Array.isArray(keys) ? keys : [keys];

        for (let index = 0; index < keyList.length; index += 1) {
            const key = keyList[index];

            if (typeof FormData !== 'undefined' && source instanceof FormData) {
                const direct = source.get(key);
                if (direct !== null && direct !== '') {
                    return direct;
                }
            }

            const directValue = readPath(source, key);
            if (directValue !== null && directValue !== '') {
                return directValue;
            }
        }

        return null;
    }

    function resolveSelectedPaymentMethod(selectedPaymentMethod) {
        if (!selectedPaymentMethod) {
            return null;
        }

        if (typeof selectedPaymentMethod === 'string') {
            return selectedPaymentMethod;
        }

        return firstFilled([
            selectedPaymentMethod.id,
            selectedPaymentMethod.payment_method_id,
            selectedPaymentMethod.paymentMethodId,
            selectedPaymentMethod.type,
            selectedPaymentMethod.paymentType,
        ]);
    }

    function normalizePaymentMethodId(rawValue) {
        if (!rawValue) {
            return null;
        }

        const normalized = String(rawValue).trim();

        if (normalized === 'bank_transfer' || normalized === 'bankTransfer') {
            return 'pix';
        }

        if (normalized === 'ticket') {
            return 'bolbradesco';
        }

        return normalized;
    }

    function normalizePayer(rawFormData, checkout) {
        const nestedFormData = rawFormData.formData || null;
        const payer = {
            ...(readValue(nestedFormData, 'payer') || {}),
            ...(rawFormData.payer || {}),
        };

        const email = firstFilled([
            payer.email,
            readValue(rawFormData, ['payer.email', 'payerEmail', 'email']),
            readValue(nestedFormData, ['payer.email', 'payerEmail', 'email']),
            checkout.payer && checkout.payer.email,
        ]);

        if (email) {
            payer.email = email;
        }

        const firstName = firstFilled([
            payer.first_name,
            payer.firstName,
            checkout.payer && checkout.payer.first_name,
        ]);
        if (firstName) {
            payer.first_name = firstName;
        }

        const lastName = firstFilled([
            payer.last_name,
            payer.lastName,
            checkout.payer && checkout.payer.last_name,
        ]);
        if (lastName) {
            payer.last_name = lastName;
        }

        payer.entityType = firstFilled([
            payer.entityType,
            checkout.payer && checkout.payer.entityType,
            'individual',
        ]);

        delete payer.firstName;
        delete payer.lastName;

        const identification = {
            ...(checkout.payer && checkout.payer.identification ? checkout.payer.identification : {}),
            ...(payer.identification || {}),
        };

        if (identification.number) {
            identification.number = String(identification.number).replace(/\D/g, '');
        }

        if (identification.type && identification.number) {
            payer.identification = identification;
        }

        return payer;
    }

    function normalizePaymentPayload(rawFormData, checkout) {
        const nestedFormData = rawFormData.formData || null;
        const paymentMethodId = normalizePaymentMethodId(firstFilled([
            readValue(rawFormData, ['payment_method_id', 'paymentMethodId']),
            readValue(nestedFormData, ['payment_method_id', 'paymentMethodId']),
            resolveSelectedPaymentMethod(rawFormData.selectedPaymentMethod),
        ]));

        const payload = {
            pedido_id: checkout.pedido_id,
            transaction_amount: checkout.amount,
            payment_method_id: paymentMethodId,
            payer: normalizePayer(rawFormData, checkout),
        };

        const installments = firstFilled([
            readValue(rawFormData, 'installments'),
            readValue(nestedFormData, 'installments'),
        ]);
        if (installments) {
            payload.installments = installments;
        }

        const token = firstFilled([
            readValue(rawFormData, 'token'),
            readValue(nestedFormData, 'token'),
        ]);
        if (token) {
            payload.token = token;
        }

        const issuerId = firstFilled([
            readValue(rawFormData, ['issuer_id', 'issuerId']),
            readValue(nestedFormData, ['issuer_id', 'issuerId']),
        ]);
        if (issuerId) {
            payload.issuer_id = issuerId;
        }

        return payload;
    }

    function formatErrors(errors) {
        const items = Object.values(errors || {}).flat().filter(Boolean);

        if (items.length === 0) {
            return '';
        }

        return '<ul class="mt-2 space-y-1 text-sm text-red-600">' + items.map(function (item) {
            return '<li>' + item + '</li>';
        }).join('') + '</ul>';
    }

    function formatGatewayDetails(details) {
        if (!details) {
            return '';
        }

        const items = [];

        if (details.message) {
            items.push(details.message);
        }

        if (Array.isArray(details.cause)) {
            details.cause.forEach(function (cause) {
                if (cause && cause.description) {
                    items.push(cause.description);
                } else if (cause && cause.code) {
                    items.push(cause.code);
                }
            });
        }

        if (items.length === 0) {
            return '';
        }

        return '<ul class="mt-2 space-y-1 text-sm text-red-600">' + items.map(function (item) {
            return '<li>' + item + '</li>';
        }).join('') + '</ul>';
    }

    function translatePaymentStatus(status) {
        const labels = {
            approved: 'Aprovado',
            pending: 'Pendente',
            in_process: 'Em processamento',
            rejected: 'Recusado',
            cancelled: 'Cancelado',
        };

        return labels[status] || 'Em análise';
    }

    function translatePaymentStatusDetail(statusDetail) {
        const labels = {
            accredited: 'Pagamento confirmado',
            pending_waiting_transfer: 'Aguardando pagamento',
            pending_waiting_payment: 'Aguardando pagamento',
            in_process: 'Pagamento em análise',
            rejected_other_reason: 'Pagamento não aprovado',
            cc_rejected_other_reason: 'Pagamento não aprovado',
        };

        return labels[statusDetail] || 'Aguardando atualização do pagamento';
    }

    function showFeedback(type, html) {
        const feedback = document.getElementById('paymentBrick_feedback');

        if (!feedback) {
            return;
        }

        feedback.classList.remove('hidden');
        feedback.classList.remove('border-red-300', 'border-green-300', 'border-yellow-300', 'bg-[var(--color-lab-bg)]', 'bg-white');

        if (type === 'error') {
            feedback.classList.add('border-red-300');
            feedback.classList.add('bg-white');
        } else if (type === 'success') {
            feedback.classList.add('border-green-300');
            feedback.classList.add('bg-white');
        } else {
            feedback.classList.add('border-[var(--color-lab-border)]', 'bg-[var(--color-lab-bg)]');
        }

        feedback.innerHTML = html;
    }

    function clearStatusPoll() {
        if (activeStatusPollTimeout !== null) {
            window.clearTimeout(activeStatusPollTimeout);
            activeStatusPollTimeout = null;
        }
    }

    function getBrickContainer() {
        return document.getElementById('paymentBrick_container');
    }

    function setBrickVisibility(visible) {
        const container = getBrickContainer();

        if (!container) {
            return;
        }

        container.classList.toggle('hidden', !visible);
    }

    function beginPaymentAttempt(messageHtml) {
        clearStatusPoll();
        activeAttemptId += 1;

        if (messageHtml) {
            showFeedback('info', messageHtml);
        }

        return activeAttemptId;
    }

    function isActiveAttempt(attemptId) {
        return attemptId === activeAttemptId;
    }

    async function resetPaymentBrick(checkout, feedbackHtml) {
        clearStatusPoll();
        activeAttemptId += 1;
        setBrickVisibility(true);

        if (feedbackHtml) {
            showFeedback('info', feedbackHtml);
        }

        if (brickController && typeof brickController.unmount === 'function') {
            await brickController.unmount();
            brickController = null;
        }

        if (!checkout) {
            return;
        }

        await mount(checkout);
    }

    async function enterActivePaymentState(payment, checkout, attemptId) {
        if (!isActiveAttempt(attemptId)) {
            return;
        }

        clearStatusPoll();

        if (brickController && typeof brickController.unmount === 'function') {
            await brickController.unmount();
            brickController = null;
        }

        setBrickVisibility(false);
        showFeedback('info', renderInstructions(payment));

        document.querySelectorAll('[data-payment-reset="true"]').forEach(function (resetButton) {
            resetButton.addEventListener('click', function () {
                resetPaymentBrick(checkout, '<div class="space-y-3"><p class="font-mono text-[10px] uppercase tracking-[0.2em] text-gray-500">Alterando forma de pagamento</p><p class="text-sm text-gray-700">Estamos recarregando o checkout em uma nova instância segura. A tentativa pendente anterior será cancelada ao enviar o novo pagamento.</p></div>');
            }, { once: true });
        });
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderInstructions(payment) {
        const instructions = payment.instructions || {};
        const blocks = [];
        const translatedStatus = translatePaymentStatus(payment.status);
        const translatedDetail = payment.status_detail ? translatePaymentStatusDetail(payment.status_detail) : null;
        const hasPixInstructions = Boolean(instructions.qr_code_base64 || instructions.qr_code);
        const hasTicketInstructions = Boolean(instructions.ticket_url) && !hasPixInstructions;
        const hasOfflineInstructions = hasPixInstructions || hasTicketInstructions;
        const isTicket = Boolean(instructions.ticket_url) && !hasPixInstructions;
        const headerLabel = hasPixInstructions ? 'Pix gerado' : (isTicket ? 'Boleto gerado' : 'Pagamento iniciado');
        const introText = hasPixInstructions
            ? 'Use o QR Code ou o código Pix abaixo para concluir o pagamento no aplicativo do seu banco.'
            : (isTicket ? 'Abra o boleto abaixo para visualizar, imprimir ou copiar o código de barras e concluir o pagamento.' : 'Acompanhe a cobrança abaixo para concluir o pagamento.');
        const nextStepText = hasPixInstructions
            ? 'Se preferir outra forma de pagamento, escolha a opção abaixo para voltar ao checkout com uma nova instância segura.'
            : 'Se quiser trocar a forma de pagamento, use o botão principal abaixo para reabrir o checkout com segurança antes de gerar uma nova tentativa.';
        const resetButtonHtml = '<button type="button" data-payment-reset="true" class="inline-flex w-full items-center justify-center bg-black px-5 py-4 text-sm font-mono uppercase tracking-[0.2em] text-white transition-colors hover:bg-neutral-800">Trocar forma de pagamento</button>';

        blocks.push('<div class="space-y-5">');
        blocks.push('<div class="border border-[var(--color-lab-border)] bg-white p-5">');
        blocks.push('<p class="font-mono text-[10px] uppercase tracking-[0.2em] text-gray-500 mb-2">' + headerLabel + '</p>');
        blocks.push('<p class="text-2xl font-bold tracking-tight text-black">' + translatedStatus + '</p>');

        if (translatedDetail) {
            blocks.push('<p class="text-sm text-gray-600 mt-2">Situação do pagamento: ' + translatedDetail + '</p>');
        }
        blocks.push('<p class="text-sm text-gray-600 mt-3">' + introText + '</p>');
        blocks.push('</div>');

        blocks.push('<div class="border border-black bg-[var(--color-lab-bg)] p-4">');
        blocks.push('<p class="font-mono text-[10px] uppercase tracking-[0.2em] text-gray-500 mb-2">Quer tentar outra forma?</p>');
        blocks.push('<p class="text-sm text-gray-700 mb-4">Reabra as opções de pagamento em uma nova instância segura. A cobrança atual só será cancelada quando a próxima tentativa for enviada.</p>');
        blocks.push(resetButtonHtml);
        blocks.push('</div>');

        if (hasPixInstructions) {
            blocks.push('<div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] p-4">');
            blocks.push('<p class="font-mono text-[10px] uppercase tracking-[0.2em] text-gray-500 mb-2">Antes de confirmar</p>');
            blocks.push('<p class="text-sm text-gray-700">No aplicativo do seu banco, confirme que o pagamento Pix será enviado para a conta Mercado Pago de Felipe Devecchi Daige antes de concluir a transação.</p>');
            blocks.push('</div>');
        }

        if (instructions.qr_code_base64) {
            blocks.push('<div class="border border-[var(--color-lab-border)] bg-white p-6">');
            blocks.push('<div class="flex flex-col items-center text-center">');
            blocks.push('<p class="font-mono text-[10px] uppercase tracking-[0.2em] text-gray-500 mb-4">Escaneie com seu banco</p>');
            blocks.push('<img alt="QR Code Pix" class="w-52 h-52 border border-[var(--color-lab-border)] p-3 bg-white" src="data:image/png;base64,' + instructions.qr_code_base64 + '">');
            blocks.push('<p class="text-sm text-gray-600 mt-4 max-w-sm">Abra o aplicativo do seu banco, escaneie o QR Code e confirme o pagamento para liberar seu pedido.</p>');
            blocks.push('</div>');
            blocks.push('</div>');
        }

        if (instructions.qr_code) {
            blocks.push('<div class="border border-[var(--color-lab-border)] bg-white p-5">');
            blocks.push('<p class="font-mono text-[10px] uppercase tracking-[0.2em] text-gray-500 mb-3">Ou copie o código Pix</p>');
            blocks.push('<textarea readonly class="w-full min-h-28 border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] p-3 text-xs font-mono leading-relaxed">' + escapeHtml(instructions.qr_code) + '</textarea>');
            blocks.push('<p class="text-sm text-gray-600 mt-3">Se preferir, copie o código acima e finalize o Pix diretamente no aplicativo do seu banco.</p>');
            blocks.push('</div>');
        }

        if (instructions.ticket_url && !hasPixInstructions) {
            blocks.push('<a class="inline-flex items-center justify-center border border-black px-4 py-3 text-xs font-mono uppercase tracking-widest hover:bg-black hover:text-white transition-colors" target="_blank" rel="noopener noreferrer" href="' + instructions.ticket_url + '">Abrir boleto</a>');
        }

        if (payment.redirect_url && !hasPixInstructions) {
            blocks.push('<a class="inline-flex items-center justify-center border border-[var(--color-lab-border)] px-4 py-3 text-xs font-mono uppercase tracking-widest hover:border-black transition-colors ml-2" href="' + payment.redirect_url + '">Acompanhar pedido</a>');
        }

        blocks.push('<div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] p-4">');
        blocks.push('<p class="font-mono text-[10px] uppercase tracking-[0.2em] text-gray-500 mb-2">Próxima etapa</p>');
        blocks.push('<p class="text-sm text-gray-700">Assim que o pagamento for identificado, a confirmação aparece automaticamente nesta tela e o pedido segue para processamento. ' + nextStepText + '</p>');
        blocks.push('</div>');
        if (hasOfflineInstructions) {
            blocks.push('<div>');
            blocks.push('<p class="text-xs text-gray-500 mb-3">Se você trocar a forma de pagamento e enviar uma nova tentativa, esta cobrança pendente será cancelada automaticamente.</p>');
            blocks.push(resetButtonHtml);
            blocks.push('</div>');
        }
        blocks.push('</div>');

        return blocks.join('');
    }

    async function pollStatus(statusUrl, attemptId) {
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

            if (!isActiveAttempt(attemptId)) {
                return;
            }

            if (!data.success || !data.payment) {
                return;
            }

            if (data.pedido.status === 'pago') {
                clearStatusPoll();
                showFeedback('success', renderInstructions({
                    ...data.payment,
                    redirect_url: data.redirect_url || (activeCheckout && activeCheckout.urls ? activeCheckout.urls.orders : null),
                }));
                return;
            }

            if (data.pedido.status === 'processando' && data.redirect_url) {
                clearStatusPoll();
                window.location.href = data.redirect_url;
                return;
            }

            if (data.pedido.status === 'pendente' || data.pedido.status === 'processando') {
                activeStatusPollTimeout = window.setTimeout(function () {
                    pollStatus(statusUrl, attemptId);
                }, 5000);
            }
        } catch (error) {
            console.error('Erro ao consultar status do pagamento', error);
        }
    }

    async function mount(checkout) {
        console.info('[MercadoPago]', runtimeVersion, 'mount checkout', {
            pedidoId: checkout && checkout.pedido_id,
        });

        activeCheckout = checkout;
        setBrickVisibility(true);

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
                payer: {
                    ...checkout.payer,
                    entityType: (checkout.payer && checkout.payer.entityType) || 'individual',
                },
            },
            customization: {
                paymentMethods: {
                    creditCard: 'all',
                    debitCard: 'all',
                    ticket: 'all',
                    bankTransfer: 'all',
                },
                visual: {
                    style: {
                        customVariables: {
                            borderRadiusSmall: '0px',
                            borderRadiusMedium: '0px',
                            borderRadiusLarge: '0px',
                            baseFontSize: '14px',
                            formPadding: '8px',
                        },
                    },
                },
            },
            callbacks: {
                onReady: function () {
                    console.info('[MercadoPago]', runtimeVersion, 'brick ready');
                    showFeedback('info', '<div class="border border-[var(--color-lab-border)] bg-white p-5"><p class="font-mono text-[10px] uppercase tracking-[0.2em] text-gray-500 mb-2">Pagamento pronto</p><p class="text-sm text-gray-600">Escolha a forma de pagamento, preencha os dados e conclua a compra em ambiente seguro.</p></div>');
                },
                onSubmit: async function ({ selectedPaymentMethod, formData } = {}, additionalData) {
                    console.info('[MercadoPago]', runtimeVersion, 'onSubmit fired', {
                        selectedPaymentMethod: selectedPaymentMethod,
                        formData: formData,
                        additionalData: additionalData,
                    });

                    const payload = normalizePaymentPayload({
                        selectedPaymentMethod: selectedPaymentMethod,
                        formData: formData,
                    }, checkout);

                    console.info('[MercadoPago]', runtimeVersion, 'normalized payload', payload);

                    const attemptId = beginPaymentAttempt(
                        '<div class="space-y-3">'
                        + '<p class="font-mono text-[10px] uppercase tracking-[0.2em] text-gray-500">Processando nova tentativa</p>'
                        + '<p class="text-sm text-gray-700">Estamos iniciando a forma de pagamento selecionada. Qualquer tentativa pendente anterior será cancelada automaticamente.</p>'
                        + '</div>'
                    );

                    if (!payload.payment_method_id) {
                        console.error('Mercado Pago Brick submit sem payment_method_id', {
                            selectedPaymentMethod: selectedPaymentMethod,
                            formData: formData,
                            additionalData: additionalData,
                        });
                        showFeedback(
                            'error',
                            '<p class="text-sm text-red-600">O Brick não retornou o método de pagamento. Recarregue a página e tente novamente.</p>'
                        );
                        throw new Error('Payment method id ausente no submit do Brick.');
                    }

                    const response = await fetch(checkout.urls.pay, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json();
                    console.info('[MercadoPago]', runtimeVersion, 'pay response', {
                        status: response.status,
                        ok: response.ok,
                        data: data,
                    });

                    if (!isActiveAttempt(attemptId)) {
                        return;
                    }

                    if (!response.ok || !data.success) {
                        const message = data.message || 'Não foi possível processar o pagamento.';
                        showFeedback(
                            'error',
                            '<div class="space-y-3"><p class="font-mono text-[10px] uppercase tracking-[0.2em] text-red-500">Não foi possível concluir</p><p class="text-sm text-red-600">' + message + '</p><p class="text-sm text-gray-600">Revise os dados informados ou tente outra forma de pagamento.</p></div>'
                            + formatErrors(data.errors)
                            + formatGatewayDetails(data.details)
                        );
                        throw new Error(message);
                    }

                    if (data.payment.status === 'approved') {
                        clearStatusPoll();
                        showFeedback('success', '<div class="space-y-3"><p class="font-mono text-[10px] uppercase tracking-[0.2em] text-green-700">Pagamento confirmado</p><p class="text-xl font-bold tracking-tight text-black">Compra aprovada</p><p class="text-sm text-gray-600">Total pago: R$ ' + money(checkout.amount) + '</p><a class="inline-flex items-center justify-center border border-black px-4 py-3 text-xs font-mono uppercase tracking-widest hover:bg-black hover:text-white transition-colors" href="' + data.payment.redirect_url + '">Acompanhar pedido</a></div>');
                        return;
                    }

                    if (data.payment.status === 'in_process') {
                        showFeedback('success', '<div class="space-y-3"><p class="font-mono text-[10px] uppercase tracking-[0.2em] text-green-700">Pedido recebido</p><p class="text-xl font-bold tracking-tight text-black">Pagamento em análise</p><p class="text-sm text-gray-600">Estamos levando você para a página do pedido para acompanhar a confirmação.</p></div>');
                        window.setTimeout(function () {
                            window.location.href = data.payment.redirect_url || checkout.urls.orders;
                        }, 800);
                        return;
                    }

                    await enterActivePaymentState(data.payment, checkout, attemptId);

                    pollStatus(checkout.urls.status, attemptId);
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
