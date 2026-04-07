<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cupons — Admin JFX Tech</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)] antialiased">
@include('includes.header-admin')

<div class="flex min-h-[calc(100vh-4rem)]">
    <main class="flex-1 p-8">
        <div class="flex items-center justify-between mb-8">
            <h1 class="font-mono text-2xl font-bold uppercase tracking-widest">Cupons</h1>
            <button id="btn-novo-cupom" class="bg-black text-white px-5 py-2 font-mono text-xs uppercase tracking-widest hover:bg-gray-900 transition-colors">
                + Novo Cupom
            </button>
        </div>

        <div class="border border-[var(--color-lab-border)]">
            <table class="w-full text-sm font-mono" id="tabela-cupons">
                <thead class="bg-black text-white">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs uppercase tracking-widest">Código</th>
                        <th class="px-4 py-3 text-left text-xs uppercase tracking-widest">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs uppercase tracking-widest">Valor</th>
                        <th class="px-4 py-3 text-left text-xs uppercase tracking-widest">Mínimo</th>
                        <th class="px-4 py-3 text-left text-xs uppercase tracking-widest">Usos</th>
                        <th class="px-4 py-3 text-left text-xs uppercase tracking-widest">Validade</th>
                        <th class="px-4 py-3 text-left text-xs uppercase tracking-widest">Status</th>
                        <th class="px-4 py-3 text-left text-xs uppercase tracking-widest">Ações</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($cupons as $cupom)
                    <tr class="border-t border-[var(--color-lab-border)] hover:bg-gray-50">
                        <td class="px-4 py-3 font-bold">{{ $cupom->codigo }}</td>
                        <td class="px-4 py-3">{{ $cupom->tipo === 'percentual' ? 'Percentual' : 'Fixo' }}</td>
                        <td class="px-4 py-3">
                            {{ $cupom->tipo === 'percentual' ? number_format($cupom->valor, 0) . '%' : 'R$ ' . number_format($cupom->valor, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3">
                            {{ $cupom->valor_minimo_pedido ? 'R$ ' . number_format($cupom->valor_minimo_pedido, 2, ',', '.') : '—' }}
                        </td>
                        <td class="px-4 py-3">
                            {{ $cupom->usos_realizados }}{{ $cupom->limite_usos ? '/' . $cupom->limite_usos : '' }}
                        </td>
                        <td class="px-4 py-3">{{ $cupom->valido_ate ? $cupom->valido_ate->format('d/m/Y') : '—' }}</td>
                        <td class="px-4 py-3">
                            <button
                                class="toggle-status px-2 py-1 text-xs uppercase tracking-widest border {{ $cupom->ativo ? 'bg-black text-white border-black' : 'bg-white text-gray-500 border-gray-300' }}"
                                data-id="{{ $cupom->id }}"
                            >
                                {{ $cupom->ativo ? 'Ativo' : 'Inativo' }}
                            </button>
                        </td>
                        <td class="px-4 py-3 space-x-2">
                            <button class="btn-editar text-xs underline hover:text-black" data-cupom='@json($cupom)'>Editar</button>
                            <button class="btn-deletar text-xs underline text-red-600 hover:text-red-800" data-id="{{ $cupom->id }}">Excluir</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">Nenhum cupom cadastrado.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </main>
</div>

{{-- Modal criar/editar --}}
<div id="modal-cupom" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center">
    <div class="bg-white border border-black p-8 w-full max-w-lg mx-4">
        <div class="flex items-center justify-between mb-6">
            <h2 class="font-mono text-lg font-bold uppercase tracking-widest" id="modal-titulo">Novo Cupom</h2>
            <button id="modal-fechar" class="text-gray-400 hover:text-black text-xl">&times;</button>
        </div>
        <form id="form-cupom" class="space-y-4">
            <input type="hidden" id="cupom-id" value="">
            <div>
                <label class="block text-xs font-mono uppercase tracking-widest mb-1">Código *</label>
                <input type="text" id="f-codigo" name="codigo" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono uppercase focus:outline-none focus:border-black" maxlength="50" required>
            </div>
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

{{-- Modal confirmar exclusão --}}
<div id="modal-deletar" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center">
    <div class="bg-white border border-black p-8 w-full max-w-sm mx-4 text-center">
        <p class="font-mono text-sm mb-6">Tem certeza que deseja excluir este cupom?</p>
        <input type="hidden" id="deletar-id">
        <div class="flex gap-4 justify-center">
            <button id="confirmar-deletar" class="bg-black text-white px-6 py-2 font-mono text-xs uppercase tracking-widest hover:bg-gray-900">Excluir</button>
            <button id="cancelar-deletar" class="border border-black px-6 py-2 font-mono text-xs uppercase tracking-widest hover:bg-gray-100">Cancelar</button>
        </div>
    </div>
</div>

<script>
function cuponsUpdateUrl(id)  { return '/admin/cupons/' + id; }
function cuponsToggleUrl(id)  { return '/admin/cupons/' + id + '/toggle'; }
function cuponsDeleteUrl(id)  { return '/admin/cupons/' + id; }

function abrirModal(titulo, cupom) {
    $('#modal-titulo').text(titulo);
    $('#cupom-id').val(cupom ? cupom.id : '');
    $('#f-codigo').val(cupom ? cupom.codigo : '');
    $('#f-tipo').val(cupom ? cupom.tipo : 'percentual');
    $('#f-valor').val(cupom ? cupom.valor : '');
    $('#f-minimo').val(cupom ? (cupom.valor_minimo_pedido || '') : '');
    $('#f-limite').val(cupom ? (cupom.limite_usos || '') : '');
    $('#f-validade').val(cupom && cupom.valido_ate ? cupom.valido_ate.substring(0, 10) : '');
    $('#f-ativo').prop('checked', cupom ? !!cupom.ativo : true);
    $('#form-erro').addClass('hidden').text('');
    $('#modal-cupom').removeClass('hidden').addClass('flex');
}

function fecharModal() {
    $('#modal-cupom').addClass('hidden').removeClass('flex');
}

$(document).ready(function () {

    $('#btn-novo-cupom').on('click', function () {
        abrirModal('Novo Cupom', null);
    });

    $('#modal-fechar').on('click', fecharModal);
    $('#modal-cupom').on('click', function (e) {
        if ($(e.target).is('#modal-cupom')) fecharModal();
    });

    $(document).on('click', '.btn-editar', function () {
        const cupom = $(this).data('cupom');
        abrirModal('Editar Cupom', cupom);
    });

    $('#form-cupom').on('submit', function (e) {
        e.preventDefault();
        const id   = $('#cupom-id').val();
        const url  = id ? cuponsUpdateUrl(id) : '{{ route("admin.cupons.store") }}';
        const meth = id ? 'PUT' : 'POST';

        $.ajax({
            url: url, method: meth,
            data: {
                _token:               $('meta[name="csrf-token"]').attr('content'),
                codigo:               $('#f-codigo').val(),
                tipo:                 $('#f-tipo').val(),
                valor:                $('#f-valor').val(),
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

    $(document).on('click', '.toggle-status', function () {
        const id = $(this).data('id');
        $.post(cuponsToggleUrl(id), { _token: $('meta[name="csrf-token"]').attr('content') }, function () {
            window.location.reload();
        });
    });

    $(document).on('click', '.btn-deletar', function () {
        $('#deletar-id').val($(this).data('id'));
        $('#modal-deletar').removeClass('hidden').addClass('flex');
    });

    $('#cancelar-deletar').on('click', function () {
        $('#modal-deletar').addClass('hidden').removeClass('flex');
    });

    $('#confirmar-deletar').on('click', function () {
        const id = $('#deletar-id').val();
        $.ajax({
            url: cuponsDeleteUrl(id),
            method: 'DELETE',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function () { window.location.reload(); },
        });
    });

});
</script>
</body>
</html>
