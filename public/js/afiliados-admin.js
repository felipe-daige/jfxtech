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

// Create affiliate modal
let criarBuscarTimeout = null;

function abrirModalCriar() {
    document.getElementById('modalCriarAfiliado').classList.remove('hidden');
    document.getElementById('criar-user-search').focus();
}

function fecharModalCriar() {
    document.getElementById('modalCriarAfiliado').classList.add('hidden');
    document.getElementById('criar-user-id').value = '';
    document.getElementById('criar-user-search').value = '';
    document.getElementById('criar-user-selected').textContent = '';
    document.getElementById('criar-user-results').classList.add('hidden');
    document.getElementById('criar-user-results').innerHTML = '';
    document.getElementById('criar-codigo').value = '';
}

function gerarCodigoAuto() {
    const nome = document.getElementById('criar-user-search').value;
    const sugestao = nome.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 8);
    document.getElementById('criar-codigo').value = sugestao || '';
    document.getElementById('criar-codigo').focus();
}

function selecionarUsuarioCriar(id, name, email) {
    document.getElementById('criar-user-id').value = id;
    document.getElementById('criar-user-search').value = name;
    document.getElementById('criar-user-selected').textContent = email;
    document.getElementById('criar-user-results').classList.add('hidden');
    // Auto-suggest code from name
    const sugestao = name.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 8);
    if (!document.getElementById('criar-codigo').value) {
        document.getElementById('criar-codigo').value = sugestao;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('criar-user-search');
    if (!searchInput) return;

    searchInput.addEventListener('input', function () {
        clearTimeout(criarBuscarTimeout);
        const q = this.value.trim();
        if (q.length < 2) {
            document.getElementById('criar-user-results').classList.add('hidden');
            return;
        }
        criarBuscarTimeout = setTimeout(() => {
            const url = (window.routes.adminAfiliadosBuscarUsuarios || '') + '?q=' + encodeURIComponent(q);
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(users => {
                    const box = document.getElementById('criar-user-results');
                    if (!users.length) {
                        box.innerHTML = '<p class="px-3 py-2 text-[10px] text-gray-400">Nenhum usuário encontrado</p>';
                    } else {
                        box.innerHTML = users.map(u =>
                            `<button type="button" onclick="selecionarUsuarioCriar(${u.id}, ${JSON.stringify(u.name)}, ${JSON.stringify(u.email)})"
                                class="w-full text-left px-3 py-2 text-xs hover:bg-gray-100 border-b border-gray-100 last:border-0">
                                <span class="font-bold">${u.name}</span>
                                <span class="text-gray-400 ml-2">${u.email}</span>
                            </button>`
                        ).join('');
                    }
                    box.classList.remove('hidden');
                })
                .catch(() => {});
        }, 300);
    });

    // Close search results on outside click
    document.addEventListener('click', function (e) {
        if (!e.target.closest('#criar-user-search') && !e.target.closest('#criar-user-results')) {
            document.getElementById('criar-user-results')?.classList.add('hidden');
        }
    });
});

// Add Escape key to close create modal
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') fecharModalCriar();
});
