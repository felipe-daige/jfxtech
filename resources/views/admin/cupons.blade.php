@extends('includes.header-admin')

@section('title', 'Cupons')

@section('content')
<div class="flex items-center justify-between mb-8">
    <h1 class="font-mono text-2xl font-bold uppercase tracking-widest">Cupons</h1>
    <button id="btn-novo-cupom" class="bg-black text-white px-5 py-2 font-mono text-xs uppercase tracking-widest hover:bg-gray-900 transition-colors">
        + Novo Cupom
    </button>
</div>

{{-- Cards grid --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
@forelse ($cupons as $cupom)
<div class="border border-[var(--color-lab-border)] bg-white font-mono">

    {{-- Header --}}
    <div class="flex items-start justify-between px-4 py-3 border-b border-[var(--color-lab-border)]">
        <div class="flex items-center gap-3 min-w-0">
            <span class="text-base font-bold uppercase tracking-widest truncate">{{ $cupom->codigo }}</span>
            <span class="shrink-0 px-2 py-0.5 text-[10px] uppercase tracking-widest border {{ $cupom->ativo ? 'bg-black text-white border-black' : 'border-gray-300 text-gray-400' }}">
                {{ $cupom->ativo ? '● Ativo' : '○ Inativo' }}
            </span>
        </div>
        {{-- Menu ··· --}}
        @php
            $cupomJson = array_merge(
                $cupom->only(['id','codigo','user_id','tipo','valor','valor_minimo_pedido','limite_usos','valido_ate','ativo','comissao_percentual']),
                ['user' => $cupom->user ? $cupom->user->only(['id','name','email','coupon_portal_enabled']) : null]
            );
        @endphp
        <div class="relative shrink-0 ml-2" data-cupom-menu>
            <button class="btn-menu text-gray-500 hover:text-black px-2 py-1 text-sm leading-none" data-id="{{ $cupom->id }}">···</button>
            <div class="menu-dropdown hidden absolute right-0 top-7 z-20 bg-white border border-[var(--color-lab-border)] min-w-[180px] shadow-sm">
                <button class="btn-editar block w-full text-left px-4 py-2 text-xs hover:bg-gray-50"
                    data-cupom='@json($cupomJson)'>
                    Editar
                </button>
                <button class="toggle-status block w-full text-left px-4 py-2 text-xs hover:bg-gray-50"
                    data-id="{{ $cupom->id }}">
                    {{ $cupom->ativo ? 'Desativar' : 'Ativar' }}
                </button>
                @if($cupom->user_id)
                <button class="btn-historico block w-full text-left px-4 py-2 text-xs hover:bg-gray-50"
                    data-id="{{ $cupom->id }}" data-codigo="{{ $cupom->codigo }}" data-pendente="{{ $cupom->comissao_pendente }}">
                    Registrar pagamento
                </button>
                @endif
                <button class="btn-deletar block w-full text-left px-4 py-2 text-xs text-red-600 hover:bg-gray-50 border-t border-[var(--color-lab-border)]"
                    data-id="{{ $cupom->id }}">
                    Excluir
                </button>
            </div>
        </div>
    </div>

    {{-- Parceiro --}}
    <div class="px-4 py-2 border-b border-[var(--color-lab-border)] text-xs">
        @if($cupom->user)
            <span class="font-semibold">{{ $cupom->user->name }}</span>
            <span class="text-gray-400 ml-1">· {{ $cupom->user->email }}</span>
        @else
            <span class="text-gray-400">Sem parceiro vinculado</span>
        @endif
    </div>

    {{-- Info do cupom --}}
    <div class="px-4 py-2 border-b border-[var(--color-lab-border)] text-xs text-gray-600 space-y-0.5">
        <div>
            {{ $cupom->tipo === 'percentual' ? number_format($cupom->valor, 0) . '% de desconto' : 'R$ ' . number_format($cupom->valor, 2, ',', '.') . ' fixo' }}
            · Comissão: {{ number_format($cupom->comissao_percentual ?? 100, 0) }}%
        </div>
        <div>
            {{ $cupom->usos_realizados }} usos
            @if($cupom->valor_minimo_pedido) · Mín. R$ {{ number_format($cupom->valor_minimo_pedido, 2, ',', '.') }} @endif
            @if($cupom->valido_ate) · Até {{ $cupom->valido_ate->format('d/m/Y') }} @endif
            @if($cupom->user && $cupom->user->coupon_portal_enabled)
                · <span class="text-black font-semibold">Portal ativo</span>
            @endif
        </div>
    </div>

    {{-- Métricas (apenas com parceiro) --}}
    @if($cupom->user_id)
    <div class="px-4 py-3 border-b border-[var(--color-lab-border)] text-xs space-y-1">
        <div class="flex justify-between">
            <span class="text-gray-500 uppercase tracking-wider">Receita gerada</span>
            <span class="font-semibold">R$ {{ number_format($cupom->receita_gerada, 2, ',', '.') }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-500 uppercase tracking-wider">Descontos dados</span>
            <span>R$ {{ number_format($cupom->descontos_dados, 2, ',', '.') }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-500 uppercase tracking-wider">Comissão devida</span>
            <span class="{{ $cupom->comissao_pendente > 0 ? 'font-bold text-black' : 'text-gray-400' }}">
                R$ {{ number_format($cupom->comissao_pendente, 2, ',', '.') }}
            </span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-500 uppercase tracking-wider">Já pago</span>
            <span>R$ {{ number_format($cupom->comissao_paga, 2, ',', '.') }}</span>
        </div>
    </div>
    <div class="px-4 py-2">
        <button class="btn-historico w-full text-left text-xs underline hover:text-gray-600"
            data-id="{{ $cupom->id }}" data-codigo="{{ $cupom->codigo }}" data-pendente="{{ $cupom->comissao_pendente }}">
            Ver histórico de pagamentos
        </button>
    </div>
    @else
    <div class="px-4 py-3 text-xs text-gray-400 italic">
        Vincule um parceiro para rastrear comissões.
    </div>
    @endif

</div>
@empty
<div class="lg:col-span-2 border border-[var(--color-lab-border)] px-4 py-12 text-center text-gray-500 font-mono text-sm">
    Nenhum cupom cadastrado.
</div>
@endforelse
</div>

{{-- ==================== MODAL CRIAR/EDITAR CUPOM ==================== --}}
<div id="modal-cupom" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white border border-black w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--color-lab-border)] sticky top-0 bg-white">
            <h2 class="font-mono text-sm font-bold uppercase tracking-widest" id="modal-titulo">Novo Cupom</h2>
            <button id="modal-fechar" class="text-gray-400 hover:text-black text-xl leading-none">&times;</button>
        </div>
        <form id="form-cupom" class="px-6 py-5 space-y-4">
            <input type="hidden" id="cupom-id" value="">
            <input type="hidden" id="f-user-id" name="user_id" value="">

            <div>
                <label class="block text-xs font-mono uppercase tracking-widest mb-1">Código *</label>
                <input type="text" id="f-codigo" name="codigo" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono uppercase focus:outline-none focus:border-black" maxlength="50" required>
            </div>

            <div class="relative">
                <label class="block text-xs font-mono uppercase tracking-widest mb-1">Parceiro</label>
                <input type="text" id="f-user-search" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black" placeholder="Buscar por nome ou e-mail" autocomplete="off">
                <div id="f-user-results" class="hidden absolute z-10 mt-1 max-h-40 w-full overflow-y-auto border border-[var(--color-lab-border)] bg-white"></div>
                <div class="mt-1 flex items-center justify-between gap-3">
                    <p id="f-user-selected" class="text-xs text-gray-500"></p>
                    <button type="button" id="f-user-clear" class="hidden text-xs underline text-gray-500 hover:text-black">remover vínculo</button>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" id="f-coupon-portal-enabled" name="coupon_portal_enabled" value="1" class="w-4 h-4" disabled>
                <label for="f-coupon-portal-enabled" class="text-xs font-mono uppercase tracking-widest">Liberar acesso ao portal /cupom</label>
            </div>
            <p id="f-coupon-portal-help" class="text-[10px] text-gray-500 -mt-2">Selecione um usuário para controlar o acesso ao portal.</p>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-mono uppercase tracking-widest mb-1">Tipo *</label>
                    <select id="f-tipo" name="tipo" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black" required>
                        <option value="percentual">Percentual (%)</option>
                        <option value="fixo">Valor Fixo (R$)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-mono uppercase tracking-widest mb-1">Valor *</label>
                    <input type="number" id="f-valor" name="valor" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black" step="0.01" min="0.01" required>
                </div>
            </div>

            <div>
                <label class="block text-xs font-mono uppercase tracking-widest mb-1">Comissão ao parceiro (%)</label>
                <input type="number" id="f-comissao" name="comissao_percentual" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black" step="0.01" min="0" max="100" value="100">
                <p class="text-[10px] text-gray-500 mt-1">% do desconto gerado que vai para o parceiro. 100 = parceiro recebe tudo.</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-mono uppercase tracking-widest mb-1">Pedido Mínimo (R$)</label>
                    <input type="number" id="f-minimo" name="valor_minimo_pedido" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black" step="0.01" min="0">
                </div>
                <div>
                    <label class="block text-xs font-mono uppercase tracking-widest mb-1">Limite de Usos</label>
                    <input type="number" id="f-limite" name="limite_usos" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black" min="1">
                </div>
            </div>

            <div>
                <label class="block text-xs font-mono uppercase tracking-widest mb-1">Válido até</label>
                <input type="date" id="f-validade" name="valido_ate" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black">
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" id="f-ativo" name="ativo" value="1" checked class="w-4 h-4">
                <label for="f-ativo" class="text-xs font-mono uppercase tracking-widest">Ativo</label>
            </div>

            <p id="form-erro" class="text-red-600 text-xs hidden"></p>

            <button type="submit" class="w-full bg-black text-white py-3 font-mono text-xs uppercase tracking-widest hover:bg-gray-900 transition-colors">
                Salvar
            </button>
        </form>
    </div>
</div>

{{-- ==================== MODAL EXCLUIR ==================== --}}
<div id="modal-deletar" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white border border-black p-8 w-full max-w-sm text-center">
        <p class="font-mono text-sm mb-6">Tem certeza que deseja excluir este cupom?</p>
        <input type="hidden" id="deletar-id">
        <div class="flex gap-4 justify-center">
            <button id="confirmar-deletar" class="bg-black text-white px-6 py-2 font-mono text-xs uppercase tracking-widest hover:bg-gray-900">Excluir</button>
            <button id="cancelar-deletar" class="border border-black px-6 py-2 font-mono text-xs uppercase tracking-widest hover:bg-gray-100">Cancelar</button>
        </div>
    </div>
</div>

{{-- ==================== MODAL HISTÓRICO DE PAGAMENTOS ==================== --}}
<div id="modal-pagamentos" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white border border-black w-full max-w-lg max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--color-lab-border)]">
            <h2 class="font-mono text-sm font-bold uppercase tracking-widest">
                Pagamentos · <span id="pag-codigo"></span>
            </h2>
            <button id="pag-fechar" class="text-gray-400 hover:text-black text-xl leading-none">&times;</button>
        </div>

        <div class="flex items-center justify-between px-6 py-3 border-b border-[var(--color-lab-border)] bg-gray-50">
            <div class="font-mono text-xs">
                <span class="text-gray-500 uppercase tracking-wider">Pendente:</span>
                <span id="pag-pendente" class="font-bold text-black ml-1">R$ —</span>
            </div>
            <button id="btn-registrar-pagamento" class="bg-black text-white px-4 py-1.5 font-mono text-xs uppercase tracking-widest hover:bg-gray-900">
                + Registrar
            </button>
        </div>

        <div id="pag-lista" class="overflow-y-auto divide-y divide-[var(--color-lab-border)] flex-1">
            <div class="px-6 py-8 text-center text-gray-400 font-mono text-xs">Carregando...</div>
        </div>
    </div>
</div>

{{-- ==================== MODAL REGISTRAR / EDITAR PAGAMENTO ==================== --}}
<div id="modal-reg-pagamento" class="fixed inset-0 bg-black/50 z-60 hidden items-center justify-center p-4">
    <div class="bg-white border border-black w-full max-w-md max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--color-lab-border)]">
            <h2 id="reg-titulo" class="font-mono text-sm font-bold uppercase tracking-widest">Registrar Pagamento</h2>
            <button id="reg-fechar" class="text-gray-400 hover:text-black text-xl leading-none">&times;</button>
        </div>

        <div class="overflow-y-auto flex-1">
            {{-- Usos pendentes (só no modo criar) --}}
            <div id="reg-usos-section" class="px-6 py-4 border-b border-[var(--color-lab-border)]">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-mono text-xs uppercase tracking-widest text-gray-500">Usos Pendentes</span>
                    <button type="button" id="reg-selecionar-todos" class="text-xs underline font-mono hover:text-gray-700">Selecionar todos</button>
                </div>
                <div id="reg-usos-lista" class="space-y-1 max-h-48 overflow-y-auto">
                    <div class="text-xs text-gray-400 font-mono py-2">Carregando...</div>
                </div>
                <div class="mt-2 font-mono text-xs text-right">
                    Total selecionado: <span id="reg-total-selecionado" class="font-bold">R$ 0,00</span>
                </div>
            </div>

            <form id="form-reg-pagamento" class="px-6 py-4 space-y-4">
                <input type="hidden" id="reg-pagamento-id" value="">
                <div>
                    <label class="block text-xs font-mono uppercase tracking-widest mb-1">Valor pago *</label>
                    <input type="number" id="reg-valor-pago" name="valor_pago" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black" step="0.01" min="0.01" required>
                </div>
                <div>
                    <label class="block text-xs font-mono uppercase tracking-widest mb-1">Data do pagamento *</label>
                    <input type="date" id="reg-pago-em" name="pago_em" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black" required>
                </div>
                <div>
                    <label class="block text-xs font-mono uppercase tracking-widest mb-1">Observação</label>
                    <input type="text" id="reg-observacao" name="observacao" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black" maxlength="1000">
                </div>
                <p id="reg-erro" class="text-red-600 text-xs hidden"></p>
                <div class="flex gap-3">
                    <button type="button" id="reg-cancelar" class="flex-1 border border-black py-2 font-mono text-xs uppercase tracking-widest hover:bg-gray-100">Cancelar</button>
                    <button type="submit" class="flex-1 bg-black text-white py-2 font-mono text-xs uppercase tracking-widest hover:bg-gray-900">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ==================== MODAL VER USOS COBERTOS ==================== --}}
<div id="modal-usos-cobertos" class="fixed inset-0 bg-black/50 z-70 hidden items-center justify-center p-4">
    <div class="bg-white border border-black w-full max-w-sm max-h-[80vh] flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--color-lab-border)]">
            <h2 class="font-mono text-sm font-bold uppercase tracking-widest">Usos cobertos</h2>
            <button id="usos-fechar" class="text-gray-400 hover:text-black text-xl leading-none">&times;</button>
        </div>
        <div id="usos-lista" class="overflow-y-auto divide-y divide-[var(--color-lab-border)]">
            <div class="px-6 py-6 text-center text-gray-400 font-mono text-xs">Nenhum uso.</div>
        </div>
    </div>
</div>

<script>
(function ($) {
    'use strict';

    // ── URL helpers ──────────────────────────────────────────────────────────
    const baseUrl = '/admin/cupons';
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    function url(id)                  { return baseUrl + '/' + id; }
    function urlToggle(id)            { return baseUrl + '/' + id + '/toggle'; }
    function urlPagamentos(id)        { return baseUrl + '/' + id + '/pagamentos'; }
    function urlPagamento(id, pid)    { return baseUrl + '/' + id + '/pagamentos/' + pid; }
    function urlUsosPendentes(id)     { return baseUrl + '/' + id + '/usos-pendentes'; }
    function urlBuscarUsuarios()      { return '{{ route("admin.cupons.buscarUsuarios") }}'; }

    // ── State ────────────────────────────────────────────────────────────────
    let currentCupomId   = null;
    let currentCupomCodigo = null;
    let usosPendentes    = [];
    let editandoPagamentoId = null;
    let couponUserSearchRequest = null;

    // ═══════════════════════════════════════════════════════════════════════
    // MENU ···
    // ═══════════════════════════════════════════════════════════════════════
    $(document).on('click', '.btn-menu', function (e) {
        e.stopPropagation();
        const $menu = $(this).siblings('.menu-dropdown');
        $('.menu-dropdown').not($menu).addClass('hidden');
        $menu.toggleClass('hidden');
    });

    $(document).on('click', function () {
        $('.menu-dropdown').addClass('hidden');
    });

    // ═══════════════════════════════════════════════════════════════════════
    // MODAL CUPOM (criar/editar)
    // ═══════════════════════════════════════════════════════════════════════
    function renderUserSelection(user) {
        if (user && user.id) {
            $('#f-user-id').val(user.id);
            $('#f-user-search').val(user.name || '');
            $('#f-user-selected').text((user.name || '') + ' (' + (user.email || '') + ')');
            $('#f-user-clear').removeClass('hidden');
            $('#f-coupon-portal-enabled').prop('disabled', false).prop('checked', !!user.coupon_portal_enabled);
            $('#f-coupon-portal-help').text('Este usuário poderá acessar o portal /cupom se a liberação estiver marcada.');
            return;
        }
        $('#f-user-id').val('');
        $('#f-user-search').val('');
        $('#f-user-selected').text('');
        $('#f-user-clear').addClass('hidden');
        $('#f-coupon-portal-enabled').prop('disabled', true).prop('checked', false);
        $('#f-coupon-portal-help').text('Selecione um usuário para controlar o acesso ao portal.');
    }

    function hideUserResults() { $('#f-user-results').addClass('hidden').empty(); }

    function showUserResults(users) {
        const $r = $('#f-user-results').empty();
        if (!users.length) {
            $r.append('<div class="px-3 py-2 text-xs text-gray-500">Nenhum usuário encontrado.</div>');
        } else {
            users.forEach(function (u) {
                const $btn = $('<button type="button" class="block w-full border-b border-[var(--color-lab-border)] px-3 py-2 text-left text-xs hover:bg-gray-50"></button>');
                $btn.append($('<div class="font-semibold"></div>').text(u.name));
                $btn.append($('<div class="text-gray-500"></div>').text(u.email));
                $btn.on('click', function () { renderUserSelection(u); hideUserResults(); });
                $r.append($btn);
            });
        }
        $r.removeClass('hidden');
    }

    function abrirModalCupom(titulo, cupom) {
        $('#modal-titulo').text(titulo);
        $('#cupom-id').val(cupom ? cupom.id : '');
        $('#f-codigo').val(cupom ? cupom.codigo : '');
        renderUserSelection(cupom ? cupom.user : null);
        $('#f-tipo').val(cupom ? cupom.tipo : 'percentual');
        $('#f-valor').val(cupom ? cupom.valor : '');
        $('#f-comissao').val(cupom ? (cupom.comissao_percentual !== null ? parseFloat(cupom.comissao_percentual) : 100) : 100);
        $('#f-minimo').val(cupom ? (cupom.valor_minimo_pedido || '') : '');
        $('#f-limite').val(cupom ? (cupom.limite_usos || '') : '');
        $('#f-validade').val(cupom && cupom.valido_ate ? cupom.valido_ate.substring(0, 10) : '');
        $('#f-ativo').prop('checked', cupom ? !!cupom.ativo : true);
        $('#form-erro').addClass('hidden').text('');
        hideUserResults();
        $('#modal-cupom').removeClass('hidden').addClass('flex');
    }

    function fecharModalCupom() {
        hideUserResults();
        $('#modal-cupom').addClass('hidden').removeClass('flex');
    }

    $('#btn-novo-cupom').on('click', function () { abrirModalCupom('Novo Cupom', null); });
    $('#modal-fechar').on('click', fecharModalCupom);
    $('#modal-cupom').on('click', function (e) { if ($(e.target).is('#modal-cupom')) fecharModalCupom(); });

    $('#f-user-search').on('input', function () {
        const q = $(this).val().trim();
        if (q.length < 2) { hideUserResults(); return; }
        if (couponUserSearchRequest) couponUserSearchRequest.abort();
        couponUserSearchRequest = $.get(urlBuscarUsuarios(), { q })
            .done(showUserResults)
            .always(function () { couponUserSearchRequest = null; });
    });

    $('#f-user-clear').on('click', function () { renderUserSelection(null); hideUserResults(); });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('#f-user-search, #f-user-results').length) hideUserResults();
    });

    $(document).on('click', '.btn-editar', function () {
        const cupom = $(this).data('cupom');
        abrirModalCupom('Editar Cupom', cupom);
    });

    $('#form-cupom').on('submit', function (e) {
        e.preventDefault();
        const id   = $('#cupom-id').val();
        const url_ = id ? url(id) : '{{ route("admin.cupons.store") }}';
        const meth = id ? 'PUT' : 'POST';

        $.ajax({
            url: url_, method: meth,
            data: {
                _token:               csrfToken,
                codigo:               $('#f-codigo').val(),
                user_id:              $('#f-user-id').val() || null,
                coupon_portal_enabled: $('#f-coupon-portal-enabled').is(':checked') ? 1 : 0,
                tipo:                 $('#f-tipo').val(),
                valor:                $('#f-valor').val(),
                comissao_percentual:  $('#f-comissao').val(),
                valor_minimo_pedido:  $('#f-minimo').val() || null,
                limite_usos:          $('#f-limite').val() || null,
                valido_ate:           $('#f-validade').val() || null,
                ativo:                $('#f-ativo').is(':checked') ? 1 : 0,
            },
            success: function () { window.location.reload(); },
            error: function (xhr) {
                const errors = xhr.responseJSON && xhr.responseJSON.errors;
                const msg = errors
                    ? Object.values(errors).flat().join(' ')
                    : ((xhr.responseJSON && xhr.responseJSON.message) || 'Erro ao salvar.');
                $('#form-erro').removeClass('hidden').text(msg);
            },
        });
    });

    // ═══════════════════════════════════════════════════════════════════════
    // TOGGLE STATUS
    // ═══════════════════════════════════════════════════════════════════════
    $(document).on('click', '.toggle-status', function () {
        const id = $(this).data('id');
        $.post(urlToggle(id), { _token: csrfToken }, function () { window.location.reload(); });
    });

    // ═══════════════════════════════════════════════════════════════════════
    // MODAL EXCLUIR
    // ═══════════════════════════════════════════════════════════════════════
    $(document).on('click', '.btn-deletar', function () {
        $('#deletar-id').val($(this).data('id'));
        $('#modal-deletar').removeClass('hidden').addClass('flex');
    });

    $('#cancelar-deletar').on('click', function () {
        $('#modal-deletar').addClass('hidden').removeClass('flex');
    });

    $('#confirmar-deletar').on('click', function () {
        $.ajax({
            url: url($('#deletar-id').val()), method: 'DELETE',
            data: { _token: csrfToken },
            success: function () { window.location.reload(); },
        });
    });

    // ═══════════════════════════════════════════════════════════════════════
    // MODAL HISTÓRICO DE PAGAMENTOS
    // ═══════════════════════════════════════════════════════════════════════
    function carregarPagamentos() {
        $('#pag-lista').html('<div class="px-6 py-8 text-center text-gray-400 font-mono text-xs">Carregando...</div>');

        $.get(urlPagamentos(currentCupomId))
            .done(function (pagamentos) {
                renderListaPagamentos(pagamentos);
            })
            .fail(function () {
                $('#pag-lista').html('<div class="px-6 py-8 text-center text-red-500 font-mono text-xs">Erro ao carregar pagamentos.</div>');
            });
    }

    function renderListaPagamentos(pagamentos) {
        const $lista = $('#pag-lista').empty();

        if (!pagamentos.length) {
            $lista.html('<div class="px-6 py-8 text-center text-gray-400 font-mono text-xs">Nenhum pagamento registrado.</div>');
            return;
        }

        pagamentos.forEach(function (p) {
            const obs    = p.observacao ? '<span class="text-gray-400">"' + escapeHtml(p.observacao) + '"</span>' : '';
            const $item  = $('<div class="px-6 py-4 font-mono text-xs"></div>');
            const $header = $('<div class="flex items-start justify-between gap-2"></div>');

            const $info = $('<div></div>');
            $info.html(
                '<div class="font-bold">' + escapeHtml(p.pago_em) + ' &nbsp; R$ ' + formatBr(p.valor_pago) + '</div>' +
                (obs ? '<div class="mt-0.5 text-[11px]">' + obs + '</div>' : '') +
                '<div class="mt-1 text-gray-500">' + p.usos_count + ' uso(s) coberto(s)</div>'
            );

            const $menu = $('<div class="relative shrink-0"></div>');
            const $menuBtn = $('<button class="text-gray-400 hover:text-black px-1">···</button>');
            const $dropdown = $(
                '<div class="pag-dropdown hidden absolute right-0 top-6 z-20 bg-white border border-[var(--color-lab-border)] min-w-[160px] shadow-sm">' +
                    '<button class="btn-ver-usos block w-full text-left px-4 py-2 text-xs hover:bg-gray-50">Ver usos cobertos</button>' +
                    '<button class="btn-editar-pagamento block w-full text-left px-4 py-2 text-xs hover:bg-gray-50">Editar</button>' +
                    '<button class="btn-excluir-pagamento block w-full text-left px-4 py-2 text-xs text-red-600 hover:bg-gray-50 border-t border-[var(--color-lab-border)]">Excluir</button>' +
                '</div>'
            );
            $menuBtn.on('click', function (e) {
                e.stopPropagation();
                $('.pag-dropdown').not($dropdown).addClass('hidden');
                $dropdown.toggleClass('hidden');
            });

            $dropdown.find('.btn-ver-usos').on('click', function () {
                $dropdown.addClass('hidden');
                abrirUsosCobertos(p.usos);
            });

            $dropdown.find('.btn-editar-pagamento').on('click', function () {
                $dropdown.addClass('hidden');
                abrirRegistrarPagamento(p);
            });

            $dropdown.find('.btn-excluir-pagamento').on('click', function () {
                $dropdown.addClass('hidden');
                if (!confirm('Excluir este pagamento? Os usos cobertos voltarão para pendente.')) return;
                $.ajax({
                    url: urlPagamento(currentCupomId, p.id), method: 'DELETE',
                    data: { _token: csrfToken },
                    success: function () { carregarPagamentos(); },
                    error: function () { alert('Erro ao excluir pagamento.'); },
                });
            });

            $menu.append($menuBtn).append($dropdown);
            $header.append($info).append($menu);
            $item.append($header);
            $lista.append($item);
        });

        $(document).on('click.pagDropdown', function () { $('.pag-dropdown').addClass('hidden'); });
    }

    function abrirModalPagamentos(id, codigo, pendente) {
        currentCupomId    = id;
        currentCupomCodigo = codigo;
        $('#pag-codigo').text(codigo);
        $('#pag-pendente').text(pendente !== undefined ? 'R$ ' + formatBr(pendente) : 'R$ —');
        carregarPagamentos();
        $('#modal-pagamentos').removeClass('hidden').addClass('flex');
    }

    $('#pag-fechar').on('click', function () {
        $(document).off('click.pagDropdown');
        $('#modal-pagamentos').addClass('hidden').removeClass('flex');
    });

    $(document).on('click', '.btn-historico', function () {
        const id      = $(this).data('id');
        const codigo  = $(this).data('codigo');
        const pendente = $(this).data('pendente');
        abrirModalPagamentos(id, codigo, pendente);
    });

    $('#btn-registrar-pagamento').on('click', function () {
        abrirRegistrarPagamento(null);
    });

    // ═══════════════════════════════════════════════════════════════════════
    // MODAL REGISTRAR / EDITAR PAGAMENTO
    // ═══════════════════════════════════════════════════════════════════════
    function calcularTotalSelecionado() {
        let total = 0;
        $('#reg-usos-lista input[type="checkbox"]:checked').each(function () {
            total += parseFloat($(this).data('comissao')) || 0;
        });
        $('#reg-total-selecionado').text('R$ ' + formatBr(total.toFixed(2)));
        $('#reg-valor-pago').val(total > 0 ? total.toFixed(2) : $('#reg-valor-pago').val());
    }

    function renderUsosPendentes(usos) {
        const $lista = $('#reg-usos-lista').empty();
        usosPendentes = usos;

        if (!usos.length) {
            $lista.html('<div class="text-xs text-gray-400 font-mono py-2">Nenhum uso pendente.</div>');
            return;
        }

        usos.forEach(function (u) {
            const $row = $('<label class="flex items-start gap-2 cursor-pointer py-1"></label>');
            const $cb  = $('<input type="checkbox" class="mt-0.5 w-4 h-4">');
            $cb.val(u.id).data('comissao', u.comissao_valor);
            $cb.on('change', calcularTotalSelecionado);
            $row.append($cb);
            $row.append($('<div class="font-mono text-xs"></div>').html(
                '<span>Pedido #' + u.pedido_id + '</span> ' +
                '<span class="text-gray-400">· ' + (u.created_at || '') + '</span> ' +
                '<span class="font-semibold">· R$ ' + formatBr(u.comissao_valor) + '</span>'
            ));
            $lista.append($row);
        });
    }

    function abrirRegistrarPagamento(pagamento) {
        editandoPagamentoId = pagamento ? pagamento.id : null;
        const editando = !!pagamento;

        $('#reg-titulo').text(editando ? 'Editar Pagamento' : 'Registrar Pagamento · ' + currentCupomCodigo);
        $('#reg-pagamento-id').val(editando ? pagamento.id : '');
        $('#reg-valor-pago').val(editando ? parseFloat(pagamento.valor_pago).toFixed(2) : '');
        $('#reg-pago-em').val(editando && pagamento.pago_em
            ? (function (d) {
                const parts = d.split('/');
                return parts.length === 3 ? parts[2] + '-' + parts[1] + '-' + parts[0] : '';
            })(pagamento.pago_em)
            : '');
        $('#reg-observacao').val(editando ? (pagamento.observacao || '') : '');
        $('#reg-erro').addClass('hidden').text('');

        if (editando) {
            $('#reg-usos-section').addClass('hidden');
        } else {
            $('#reg-usos-section').removeClass('hidden');
            $('#reg-usos-lista').html('<div class="text-xs text-gray-400 font-mono py-2">Carregando...</div>');
            $('#reg-total-selecionado').text('R$ 0,00');
            $('#reg-valor-pago').val('');

            $.get(urlUsosPendentes(currentCupomId))
                .done(function (usos) { renderUsosPendentes(usos); })
                .fail(function () { $('#reg-usos-lista').html('<div class="text-xs text-red-500 font-mono py-2">Erro ao carregar usos.</div>'); });
        }

        $('#modal-reg-pagamento').removeClass('hidden').addClass('flex');
    }

    function fecharModalRegPagamento() {
        $('#modal-reg-pagamento').addClass('hidden').removeClass('flex');
        editandoPagamentoId = null;
        usosPendentes = [];
    }

    $('#reg-fechar, #reg-cancelar').on('click', fecharModalRegPagamento);

    $('#reg-selecionar-todos').on('click', function () {
        const checkboxes = $('#reg-usos-lista input[type="checkbox"]');
        const allChecked = checkboxes.length === checkboxes.filter(':checked').length;
        checkboxes.prop('checked', !allChecked).trigger('change');
        calcularTotalSelecionado();
    });

    $('#form-reg-pagamento').on('submit', function (e) {
        e.preventDefault();
        const editando = !!editandoPagamentoId;
        const reqUrl   = editando ? urlPagamento(currentCupomId, editandoPagamentoId) : urlPagamentos(currentCupomId);
        const method   = editando ? 'PUT' : 'POST';

        const payload = {
            _token:     csrfToken,
            valor_pago: $('#reg-valor-pago').val(),
            pago_em:    $('#reg-pago-em').val(),
            observacao: $('#reg-observacao').val() || null,
        };

        if (!editando) {
            payload['uso_ids[]'] = [];
            $('#reg-usos-lista input[type="checkbox"]:checked').each(function () {
                payload['uso_ids[]'].push($(this).val());
            });
        }

        $.ajax({
            url: reqUrl, method: method,
            data: payload,
            success: function () {
                fecharModalRegPagamento();
                carregarPagamentos();
                window.location.reload();
            },
            error: function (xhr) {
                const errors = xhr.responseJSON && xhr.responseJSON.errors;
                const msg = errors
                    ? Object.values(errors).flat().join(' ')
                    : ((xhr.responseJSON && xhr.responseJSON.message) || 'Erro ao salvar.');
                $('#reg-erro').removeClass('hidden').text(msg);
            },
        });
    });

    // ═══════════════════════════════════════════════════════════════════════
    // MODAL USOS COBERTOS
    // ═══════════════════════════════════════════════════════════════════════
    function abrirUsosCobertos(usos) {
        const $lista = $('#usos-lista').empty();

        if (!usos || !usos.length) {
            $lista.html('<div class="px-6 py-6 text-center text-gray-400 font-mono text-xs">Nenhum uso coberto.</div>');
        } else {
            usos.forEach(function (u) {
                $lista.append(
                    $('<div class="px-6 py-3 font-mono text-xs"></div>').html(
                        '<span>Pedido #' + u.pedido_id + '</span> ' +
                        '<span class="text-gray-400">· ' + (u.data || '') + '</span> ' +
                        '<span class="font-semibold">· R$ ' + formatBr(u.comissao) + '</span>'
                    )
                );
            });
        }

        $('#modal-usos-cobertos').removeClass('hidden').addClass('flex');
    }

    $('#usos-fechar').on('click', function () {
        $('#modal-usos-cobertos').addClass('hidden').removeClass('flex');
    });

    // ═══════════════════════════════════════════════════════════════════════
    // Utils
    // ═══════════════════════════════════════════════════════════════════════
    function formatBr(val) {
        return parseFloat(val).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function escapeHtml(str) {
        return $('<div>').text(str).html();
    }

})(jQuery);
</script>
@endsection
