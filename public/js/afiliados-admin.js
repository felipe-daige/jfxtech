// public/js/afiliados-admin.js

// SSE real-time metrics
(function () {
    if (!window.routes || !window.routes.adminAfiliadosStream) return;

    const source = new EventSource(window.routes.adminAfiliadosStream);

    source.onmessage = function (e) {
        try {
            const data = JSON.parse(e.data);

            const ativos = document.getElementById('sse-ativos');
            const hoje   = document.getElementById('sse-indicacoes-hoje');
            const pend   = document.getElementById('sse-pendentes');
            const pagas  = document.getElementById('sse-pagas');

            if (ativos) ativos.textContent = data.afiliados_ativos;
            if (hoje)   hoje.textContent   = data.indicacoes_hoje;
            if (pend)   pend.textContent   = 'R$ ' + formatMoney(data.comissoes_pendentes_valor);
            if (pagas)  pagas.textContent  = 'R$ ' + formatMoney(data.comissoes_pagas_valor);
        } catch (err) {
            // ignore parse errors
        }
    };

    source.onerror = function () {
        source.close();
    };

    function formatMoney(value) {
        return Number(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
})();

// Commission modal
function abrirModalComissao(id, type, value) {
    document.getElementById('mc-type').value  = type || 'percent';
    document.getElementById('mc-value').value = value || '';
    document.getElementById('formComissao').action =
        (window.routes.adminAfiliadosComissao || '').replace(':id', id);
    document.getElementById('modalComissao').classList.remove('hidden');
}

function fecharModalComissao() {
    document.getElementById('modalComissao').classList.add('hidden');
}

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') fecharModalComissao();
});
