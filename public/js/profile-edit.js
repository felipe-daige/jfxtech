// profile-edit.js — JFXTECH
document.addEventListener('DOMContentLoaded', function () {

    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    // ─── TABS ─────────────────────────────────────────────────────────────────

    const tabBtns   = document.querySelectorAll('.tab-btn');
    const tabPanels = document.querySelectorAll('.tab-content');
    const validTabs = ['dados', 'enderecos', 'seguranca', 'pedidos'];

    function activateTab(name) {
        tabBtns.forEach(btn => {
            const active = btn.dataset.tab === name;
            btn.classList.toggle('border-black', active);
            btn.classList.toggle('text-black', active);
            btn.classList.toggle('border-transparent', !active);
            btn.classList.toggle('text-gray-500', !active);
        });
        tabPanels.forEach(panel => {
            panel.classList.toggle('hidden', panel.id !== 'tab-' + name);
        });
    }

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            history.replaceState(null, '', '#' + btn.dataset.tab);
            activateTab(btn.dataset.tab);
        });
    });

    const hash = location.hash.replace('#', '');
    activateTab(validTabs.includes(hash) ? hash : 'dados');

    // ─── FORM HELPERS ─────────────────────────────────────────────────────────

    function clearFormState(form) {
        form.querySelectorAll('.inline-alert, .field-error').forEach(el => el.remove());
        form.querySelectorAll('.border-red-500').forEach(el => el.classList.remove('border-red-500'));
    }

    function showAlert(container, type, message) {
        container.querySelectorAll('.inline-alert').forEach(el => el.remove());
        const div = document.createElement('div');
        div.className = 'inline-alert mb-4 px-4 py-3 border text-sm font-mono flex items-center space-x-2 ' +
            (type === 'success' ? 'border-black text-black' : 'border-red-500 text-red-500');
        div.innerHTML = type === 'success'
            ? `<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg><span>${message}</span>`
            : `<span>${message}</span>`;
        container.prepend(div);
        if (type === 'success') setTimeout(() => div.remove(), 3000);
    }

    function showFieldErrors(form, errors) {
        Object.keys(errors).forEach(field => {
            const input = form.querySelector(`[name="${field}"]`);
            if (!input) return;
            input.classList.add('border-red-500');
            const p = document.createElement('p');
            p.className = 'field-error text-red-500 text-xs font-mono mt-1';
            p.textContent = errors[field][0];
            input.parentNode.appendChild(p);
        });
    }

    async function ajaxSubmit(form, btn, originalLabel) {
        btn.disabled = true;
        btn.textContent = 'SALVANDO...';
        clearFormState(form);
        try {
            const res  = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF }
            });
            const data = await res.json();
            return data;
        } catch {
            return { success: false, message: 'Erro de conexão. Tente novamente.' };
        } finally {
            btn.disabled    = false;
            btn.textContent = originalLabel;
        }
    }

    // ─── ABA DADOS PESSOAIS ───────────────────────────────────────────────────

    const formDados = document.getElementById('form-dados-pessoais');
    if (formDados) {
        const phoneInput = formDados.querySelector('[name="phone"]');
        if (phoneInput && typeof $ !== 'undefined' && $.fn.mask) {
            $(phoneInput).mask('(00) 00000-0000');
        }

        const cpfInput = document.getElementById('field-cpf');
        if (cpfInput && typeof $ !== 'undefined' && $.fn.mask) {
            $(cpfInput).mask('000.000.000-00');
        }

        formDados.addEventListener('submit', async e => {
            e.preventDefault();
            const btn  = document.getElementById('btn-salvar-dados');
            const data = await ajaxSubmit(formDados, btn, 'SALVAR ALTERAÇÕES');
            if (data.success) {
                showAlert(formDados, 'success', 'Dados atualizados com sucesso!');
                if (data.user?.name) {
                    const nameDisplay  = document.getElementById('profile-name-display');
                    const avatarInitial = document.getElementById('profile-avatar-initial');
                    if (nameDisplay)   nameDisplay.textContent   = data.user.name;
                    if (avatarInitial) avatarInitial.textContent = data.user.name.charAt(0).toUpperCase();
                }
            } else {
                if (data.errors) showFieldErrors(formDados, data.errors);
                else showAlert(formDados, 'error', data.message || 'Erro ao salvar.');
            }
        });
    }

    // ─── ABA SEGURANÇA ────────────────────────────────────────────────────────

    const formSeguranca = document.getElementById('form-seguranca');
    if (formSeguranca) {
        formSeguranca.addEventListener('submit', async e => {
            e.preventDefault();
            const btn  = document.getElementById('btn-salvar-senha');
            const data = await ajaxSubmit(formSeguranca, btn, 'ALTERAR SENHA');
            if (data.success) {
                showAlert(formSeguranca, 'success', 'Senha alterada com sucesso!');
                formSeguranca.reset();
            } else {
                if (data.errors) showFieldErrors(formSeguranca, data.errors);
                else showAlert(formSeguranca, 'error', data.message || 'Erro ao alterar senha.');
            }
        });
    }

    // ─── MODAL ENDEREÇO ───────────────────────────────────────────────────────

    const modalEndereco = document.getElementById('modal-endereco');
    const formEndereco  = document.getElementById('form-endereco');
    let   editingId     = null;

    function openModal() {
        modalEndereco.classList.remove('opacity-0', 'invisible');
        document.body.classList.add('overflow-hidden');
    }

    function closeModal() {
        modalEndereco.classList.add('opacity-0', 'invisible');
        document.body.classList.remove('overflow-hidden');
        formEndereco?.reset();
        clearModalErrors();
        editingId = null;
        document.getElementById('modal-endereco-title').textContent = 'Novo Endereço';
    }

    function clearModalErrors() {
        formEndereco?.querySelectorAll('.field-error, .inline-alert').forEach(el => el.remove());
        formEndereco?.querySelectorAll('.border-red-500').forEach(el => el.classList.remove('border-red-500'));
    }

    function showModalFieldErrors(errors) {
        clearModalErrors();
        if (!formEndereco) return;
        Object.keys(errors).forEach(field => {
            const input = formEndereco.querySelector(`[name="${field}"]`);
            if (!input) return;
            input.classList.add('border-red-500');
            const p = document.createElement('p');
            p.className = 'field-error text-red-500 text-xs font-mono mt-1';
            p.textContent = errors[field][0];
            input.parentNode.appendChild(p);
        });
    }

    // Open modal buttons (static + dynamically added ones via delegation)
    document.getElementById('tab-enderecos')?.addEventListener('click', e => {
        if (e.target.closest('[data-open-endereco-modal]')) {
            editingId = null;
            document.getElementById('modal-endereco-title').textContent = 'Novo Endereço';
            formEndereco?.reset();
            clearModalErrors();
            openModal();
        }
    });

    document.getElementById('close-endereco-modal')?.addEventListener('click', closeModal);
    document.getElementById('cancel-endereco-modal')?.addEventListener('click', closeModal);
    modalEndereco?.addEventListener('click', e => { if (e.target === modalEndereco) closeModal(); });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && !modalEndereco?.classList.contains('invisible')) closeModal();
    });

    // CEP lookup
    document.getElementById('endereco-cep')?.addEventListener('blur', async function () {
        const cep = this.value.replace(/\D/g, '');
        if (cep.length !== 8) return;
        try {
            const res  = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
            const data = await res.json();
            if (data.erro) return;
            document.getElementById('endereco-rua').value    = data.logradouro || '';
            document.getElementById('endereco-bairro').value = data.bairro     || '';
            document.getElementById('endereco-cidade').value = data.localidade || '';
            document.getElementById('endereco-estado').value = data.uf         || '';
            document.getElementById('endereco-numero')?.focus();
        } catch { /* ignore */ }
    });

    // Populate modal for edit
    function populateModal(e) {
        document.getElementById('endereco-cep').value         = e.cep_formatado || e.cep || '';
        document.getElementById('endereco-rua').value         = e.rua           || '';
        document.getElementById('endereco-numero').value      = e.numero        || '';
        document.getElementById('endereco-complemento').value = e.complemento   || '';
        document.getElementById('endereco-bairro').value      = e.bairro        || '';
        document.getElementById('endereco-cidade').value      = e.cidade        || '';
        document.getElementById('endereco-estado').value      = e.estado        || '';
    }

    // Submit endereço
    formEndereco?.addEventListener('submit', async e => {
        e.preventDefault();
        clearModalErrors();
        const btn = document.getElementById('submit-endereco');
        btn.disabled    = true;
        btn.textContent = 'SALVANDO...';

        const url    = editingId ? `/enderecos/${editingId}` : '/enderecos';
        const body   = new FormData(formEndereco);
        if (editingId) body.append('_method', 'PUT');

        try {
            const res  = await fetch(url, {
                method: 'POST',
                body,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF }
            });
            const data = await res.json();
            if (data.success) {
                closeModal();
                if (editingId) {
                    updateCard(data.endereco);
                } else {
                    appendCard(data.endereco);
                    document.getElementById('enderecos-empty')?.remove();
                }
            } else {
                if (data.errors) showModalFieldErrors(data.errors);
                else showAlert(formEndereco, 'error', data.message || 'Erro ao salvar endereço.');
            }
        } catch {
            showAlert(formEndereco, 'error', 'Erro de conexão. Tente novamente.');
        } finally {
            btn.disabled    = false;
            btn.textContent = 'SALVAR';
        }
    });

    // ─── ADDRESS CARD CRUD ────────────────────────────────────────────────────

    function buildCard(e) {
        const comp = e.complemento ? `, ${e.complemento}` : '';
        const dataJson = JSON.stringify(e).replace(/'/g, '&#39;');
        return `<div class="endereco-card bg-white border border-[var(--color-lab-border)] p-5" data-id="${e.id}" data-endereco='${dataJson}'>
    <p class="text-sm text-black mb-1">${e.rua}, ${e.numero}${comp}</p>
    <p class="text-sm text-black mb-1">${e.bairro}</p>
    <p class="text-sm text-[var(--color-lab-muted)] font-mono mb-4">${e.cidade}/${e.estado} — CEP ${e.cep_formatado}</p>
    <div class="card-actions flex space-x-3">
        <button data-edit-endereco="${e.id}" class="text-[11px] font-mono uppercase tracking-widest text-black border border-[var(--color-lab-border)] px-3 py-1.5 hover:bg-gray-50 transition-colors">Editar</button>
        <button data-confirm-delete="${e.id}" class="text-[11px] font-mono uppercase tracking-widest text-gray-500 hover:text-red-600 transition-colors">Excluir</button>
    </div>
    <div class="confirm-delete hidden mt-3 pt-3 border-t border-[var(--color-lab-border)]">
        <p class="text-[11px] font-mono text-gray-500 mb-2">Confirmar exclusão?</p>
        <div class="flex space-x-2">
            <button data-do-delete="${e.id}" class="text-[11px] font-mono uppercase text-white bg-black px-4 py-1.5">Sim, excluir</button>
            <button data-cancel-delete="${e.id}" class="text-[11px] font-mono uppercase px-4 py-1.5 border border-[var(--color-lab-border)]">Cancelar</button>
        </div>
    </div>
</div>`;
    }

    function appendCard(endereco) {
        const list = document.getElementById('enderecos-list');
        const tmp  = document.createElement('div');
        tmp.innerHTML = buildCard(endereco);
        list.appendChild(tmp.firstElementChild);
    }

    function updateCard(endereco) {
        const existing = document.querySelector(`.endereco-card[data-id="${endereco.id}"]`);
        if (!existing) return;
        const tmp = document.createElement('div');
        tmp.innerHTML = buildCard(endereco);
        existing.replaceWith(tmp.firstElementChild);
    }

    // Delegation for edit / delete buttons
    document.getElementById('enderecos-list')?.addEventListener('click', e => {
        const editBtn   = e.target.closest('[data-edit-endereco]');
        const confirmBtn = e.target.closest('[data-confirm-delete]');
        const cancelBtn  = e.target.closest('[data-cancel-delete]');
        const doDeleteBtn = e.target.closest('[data-do-delete]');

        if (editBtn) {
            const id   = editBtn.dataset.editEndereco;
            const card = document.querySelector(`.endereco-card[data-id="${id}"]`);
            if (!card) return;
            const endData = JSON.parse(card.dataset.endereco);
            editingId = parseInt(id);
            populateModal(endData);
            clearModalErrors();
            document.getElementById('modal-endereco-title').textContent = 'Editar Endereço';
            openModal();
        }

        if (confirmBtn) {
            const card = confirmBtn.closest('.endereco-card');
            card?.querySelector('.confirm-delete')?.classList.remove('hidden');
        }

        if (cancelBtn) {
            const card = cancelBtn.closest('.endereco-card');
            card?.querySelector('.confirm-delete')?.classList.add('hidden');
        }

        if (doDeleteBtn) {
            const id = doDeleteBtn.dataset.doDelete;
            deleteEndereco(id);
        }
    });

    async function deleteEndereco(id) {
        const body = new URLSearchParams({ _method: 'DELETE', _token: CSRF });
        try {
            const res  = await fetch(`/enderecos/${id}`, {
                method: 'POST',
                body,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': CSRF,
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
            });
            const data = await res.json();
            if (data.success) {
                document.querySelector(`.endereco-card[data-id="${id}"]`)?.remove();
                if (!document.querySelector('.endereco-card')) showEmptyState();
            } else {
                alert(data.message || 'Não foi possível excluir o endereço.');
            }
        } catch {
            alert('Erro de conexão. Tente novamente.');
        }
    }

    function showEmptyState() {
        const list = document.getElementById('enderecos-list');
        if (document.getElementById('enderecos-empty') || !list) return;
        list.innerHTML = `<div id="enderecos-empty" class="col-span-2 bg-white border border-[var(--color-lab-border)] p-10 text-center">
    <svg class="w-8 h-8 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
    <p class="text-sm text-gray-500 font-mono mb-4">Nenhum endereço cadastrado.</p>
    <button data-open-endereco-modal class="bg-black text-white px-6 py-2 text-xs font-mono uppercase tracking-widest hover:bg-gray-900 transition-colors">Adicionar Endereço</button>
</div>`;
    }
});
