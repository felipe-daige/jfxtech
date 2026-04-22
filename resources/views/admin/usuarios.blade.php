@extends('includes.header-admin')

@section('title', 'Usuários')

@section('content')
<div class="space-y-6 lg:space-y-8">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-1">Administração</p>
            <h1 class="text-2xl sm:text-3xl font-bold text-black tracking-tight">Usuários</h1>
            <p class="mt-2 max-w-2xl text-sm text-[var(--color-lab-muted)]">
                Gerencie cadastro, privilégios administrativos e acesso comercial sem sair do painel.
            </p>
        </div>
        <div class="inline-flex w-fit flex-col border border-[var(--color-lab-border)] bg-white px-4 py-3">
            <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-[var(--color-lab-muted)]">Total listado</span>
            <span class="mt-1 font-mono text-sm font-bold text-black">{{ $users->total() }} usuário(s)</span>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="border border-[var(--color-lab-border)] bg-white p-5">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Base total</p>
            <p class="font-mono text-2xl font-bold text-black">{{ $summary['total'] }}</p>
        </div>
        <div class="border border-[var(--color-lab-border)] bg-white p-5">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Admins</p>
            <p class="font-mono text-2xl font-bold text-black">{{ $summary['admins'] }}</p>
        </div>
        <div class="border border-[var(--color-lab-border)] bg-white p-5">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Portal de cupom</p>
            <p class="font-mono text-2xl font-bold text-black">{{ $summary['portal_enabled'] }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.usuarios.index') }}" class="border border-[var(--color-lab-border)] bg-white p-4 sm:p-6">
        <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-4">Filtros</p>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-[minmax(0,1.4fr)_repeat(3,minmax(0,0.75fr))_auto_auto] gap-4">
            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                placeholder="Buscar por nome, e-mail, telefone ou CPF"
                class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black"
            >

            <select name="admin_filter" class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black bg-white">
                <option value="">Todos os perfis</option>
                <option value="1" @selected(request('admin_filter') === '1')>Admins</option>
                <option value="0" @selected(request('admin_filter') === '0')>Clientes</option>
            </select>

            <select name="portal_filter" class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black bg-white">
                <option value="">Portal de cupom</option>
                <option value="1" @selected(request('portal_filter') === '1')>Liberado</option>
                <option value="0" @selected(request('portal_filter') === '0')>Bloqueado</option>
            </select>

            <select name="orders_filter" class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black bg-white">
                <option value="">Pedidos</option>
                <option value="with_orders" @selected(request('orders_filter') === 'with_orders')>Com pedidos</option>
                <option value="without_orders" @selected(request('orders_filter') === 'without_orders')>Sem pedidos</option>
            </select>

            <button type="submit" class="bg-black text-white px-5 py-3 text-xs font-bold tracking-widest uppercase hover:bg-gray-800 transition-colors">
                Filtrar
            </button>

            <a href="{{ route('admin.usuarios.index') }}" class="border border-[var(--color-lab-border)] px-5 py-3 text-xs font-bold tracking-widest uppercase text-black hover:bg-gray-50 transition-colors text-center">
                Limpar
            </a>
        </div>
    </form>

    <div class="border border-[var(--color-lab-border)] bg-white overflow-hidden">
        <div class="hidden lg:block overflow-x-auto admin-mobile-scroll">
            <table class="w-full min-w-[1120px] text-sm">
                <thead>
                    <tr class="border-b border-[var(--color-lab-border)]">
                        <th class="px-4 py-3 text-left font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Usuário</th>
                        <th class="px-4 py-3 text-left font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Contato</th>
                        <th class="px-4 py-3 text-left font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">CPF</th>
                        <th class="px-4 py-3 text-left font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Permissões</th>
                        <th class="px-4 py-3 text-left font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Pedidos</th>
                        <th class="px-4 py-3 text-left font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Criado em</th>
                        <th class="px-4 py-3 text-left font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Último pedido</th>
                        <th class="px-4 py-3 text-right font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        @php
                            $userPayload = [
                                'id' => $user->id,
                                'name' => $user->name,
                                'email' => $user->email,
                                'phone' => $user->phone,
                                'cpf' => $user->cpf,
                                'admin' => (bool) $user->admin,
                                'coupon_portal_enabled' => (bool) $user->coupon_portal_enabled,
                                'must_change_password' => (bool) $user->must_change_password,
                            ];
                        @endphp
                        <tr class="border-b border-[var(--color-lab-border)] align-top hover:bg-gray-50">
                            <td class="px-4 py-4">
                                <p class="font-mono text-xs font-bold text-black">{{ $user->name }}</p>
                                <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)]">{{ $user->email }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-mono text-xs text-black">{{ $user->phone ?: '—' }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-mono text-xs text-black">{{ $user->cpf ?: '—' }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <span class="inline-block px-2 py-0.5 font-mono text-[10px] border {{ $user->admin ? 'border-black bg-black text-white' : 'border-[var(--color-lab-border)] text-[var(--color-lab-muted)]' }}">
                                        {{ $user->admin ? 'Admin' : 'Cliente' }}
                                    </span>
                                    <span class="inline-block px-2 py-0.5 font-mono text-[10px] border {{ $user->coupon_portal_enabled ? 'border-black text-black' : 'border-[var(--color-lab-border)] text-[var(--color-lab-muted)]' }}">
                                        {{ $user->coupon_portal_enabled ? 'Portal cupom' : 'Sem portal' }}
                                    </span>
                                    <span class="inline-block px-2 py-0.5 font-mono text-[10px] border {{ $user->must_change_password ? 'border-yellow-300 bg-yellow-50 text-yellow-800' : 'border-[var(--color-lab-border)] text-[var(--color-lab-muted)]' }}">
                                        {{ $user->must_change_password ? 'Troca senha' : 'Senha ok' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-mono text-xs text-black">{{ $user->pedidos_count }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-mono text-xs text-black">{{ $user->created_at->format('d/m/Y H:i') }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-mono text-xs text-black">
                                    {{ $user->pedidos_max_created_at ? \Illuminate\Support\Carbon::parse($user->pedidos_max_created_at)->format('d/m/Y H:i') : '—' }}
                                </p>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center border border-[var(--color-lab-border)] px-3 py-2 font-mono text-[10px] uppercase tracking-widest text-black hover:border-black transition-colors"
                                    data-user='@json($userPayload)'
                                    data-open-user-modal
                                >
                                    Editar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center">
                                <p class="font-mono text-sm font-bold uppercase tracking-widest text-black">Nenhum usuário encontrado</p>
                                <p class="mt-2 font-mono text-xs text-[var(--color-lab-muted)]">Ajuste os filtros ou tente uma busca diferente.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="grid grid-cols-1 gap-4 p-4 lg:hidden">
            @forelse($users as $user)
                @php
                    $userPayload = [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'cpf' => $user->cpf,
                        'admin' => (bool) $user->admin,
                        'coupon_portal_enabled' => (bool) $user->coupon_portal_enabled,
                        'must_change_password' => (bool) $user->must_change_password,
                    ];
                @endphp
                <div class="border border-[var(--color-lab-border)] bg-white p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-mono text-sm font-bold text-black break-words">{{ $user->name }}</p>
                            <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)] break-all">{{ $user->email }}</p>
                        </div>
                        <button
                            type="button"
                            class="shrink-0 border border-[var(--color-lab-border)] px-3 py-2 font-mono text-[10px] uppercase tracking-widest text-black hover:border-black transition-colors"
                            data-user='@json($userPayload)'
                            data-open-user-modal
                        >
                            Editar
                        </button>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div>
                            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Telefone</p>
                            <p class="mt-1 font-mono text-xs text-black">{{ $user->phone ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">CPF</p>
                            <p class="mt-1 font-mono text-xs text-black">{{ $user->cpf ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Pedidos</p>
                            <p class="mt-1 font-mono text-xs text-black">{{ $user->pedidos_count }}</p>
                        </div>
                        <div>
                            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Último pedido</p>
                            <p class="mt-1 font-mono text-xs text-black">
                                {{ $user->pedidos_max_created_at ? \Illuminate\Support\Carbon::parse($user->pedidos_max_created_at)->format('d/m/Y H:i') : '—' }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <span class="inline-block px-2 py-0.5 font-mono text-[10px] border {{ $user->admin ? 'border-black bg-black text-white' : 'border-[var(--color-lab-border)] text-[var(--color-lab-muted)]' }}">
                            {{ $user->admin ? 'Admin' : 'Cliente' }}
                        </span>
                        <span class="inline-block px-2 py-0.5 font-mono text-[10px] border {{ $user->coupon_portal_enabled ? 'border-black text-black' : 'border-[var(--color-lab-border)] text-[var(--color-lab-muted)]' }}">
                            {{ $user->coupon_portal_enabled ? 'Portal cupom' : 'Sem portal' }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="border border-[var(--color-lab-border)] bg-white p-12 text-center">
                    <p class="font-mono text-sm font-bold uppercase tracking-widest text-black">Nenhum usuário encontrado</p>
                    <p class="mt-2 font-mono text-xs text-[var(--color-lab-muted)]">Ajuste os filtros ou tente uma busca diferente.</p>
                </div>
            @endforelse
        </div>
    </div>

    @if($users->hasPages())
        <div>
            {{ $users->links() }}
        </div>
    @endif
</div>

<div id="user-modal" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden z-50" onclick="closeUserModal()">
    <div class="flex items-center justify-center min-h-screen p-2 sm:p-4">
        <div class="bg-white border border-[var(--color-lab-border)] w-full max-w-xl max-h-[95vh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="p-6 border-b border-[var(--color-lab-border)] flex items-center justify-between">
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Gestão de usuário</p>
                    <h2 id="user-modal-title" class="mt-1 font-mono text-sm font-bold uppercase tracking-widest text-black">Editar usuário</h2>
                </div>
                <button type="button" onclick="closeUserModal()" class="text-[var(--color-lab-muted)] hover:text-black p-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

            <form id="user-edit-form" method="POST" class="p-6 space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Nome</label>
                        <input type="text" name="name" id="user-name" class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black" required>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">E-mail</label>
                        <input type="email" id="user-email" class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono bg-gray-50 text-gray-400 cursor-not-allowed" readonly>
                    </div>

                    <div>
                        <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Telefone</label>
                        <input type="text" name="phone" id="user-phone" placeholder="(11) 99999-9999" class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black">
                    </div>

                    <div>
                        <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">CPF</label>
                        <input type="text" name="cpf" id="user-cpf" placeholder="Somente números" class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black">
                    </div>
                </div>

                <div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] p-4 space-y-3">
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Permissões e estado</p>
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="admin" id="user-admin" value="1" class="h-4 w-4 border-[var(--color-lab-border)] text-black focus:ring-black">
                        <span class="font-mono text-xs text-black">Administrador</span>
                    </label>
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="coupon_portal_enabled" id="user-coupon-portal" value="1" class="h-4 w-4 border-[var(--color-lab-border)] text-black focus:ring-black">
                        <span class="font-mono text-xs text-black">Liberar portal /cupom</span>
                    </label>
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="must_change_password" id="user-force-password" value="1" class="h-4 w-4 border-[var(--color-lab-border)] text-black focus:ring-black">
                        <span class="font-mono text-xs text-black">Exigir troca de senha no próximo acesso</span>
                    </label>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 sm:justify-end">
                    <button type="button" onclick="closeUserModal()" class="w-full sm:w-auto border border-[var(--color-lab-border)] px-5 py-3 text-xs font-bold tracking-widest uppercase text-black hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="w-full sm:w-auto bg-black text-white px-5 py-3 text-xs font-bold tracking-widest uppercase hover:bg-gray-800 transition-colors">
                        Salvar alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@php
    $reopenUserModalData = session('user_modal_open') ? [
        'id' => session('user_modal_open'),
        'name' => old('name'),
        'email' => old('email'),
        'phone' => old('phone'),
        'cpf' => old('cpf'),
        'admin' => old('admin') ? true : false,
        'coupon_portal_enabled' => old('coupon_portal_enabled') ? true : false,
        'must_change_password' => old('must_change_password') ? true : false,
    ] : null;
@endphp

<script>
(function () {
    var modal = document.getElementById('user-modal');
    var form = document.getElementById('user-edit-form');
    var updateUrlTemplate = @json(url('/admin/usuarios/__ID__'));
    var modalUserId = @json(session('user_modal_open'));
    var oldUserData = @json($reopenUserModalData);

    function fillUserForm(user) {
        if (!user) return;

        form.action = updateUrlTemplate.replace('__ID__', user.id);
        document.getElementById('user-modal-title').textContent = 'Editar ' + (user.name || 'usuário');
        document.getElementById('user-name').value = user.name || '';
        document.getElementById('user-email').value = user.email || '';
        document.getElementById('user-phone').value = user.phone || '';
        document.getElementById('user-cpf').value = user.cpf || '';
        document.getElementById('user-admin').checked = !!user.admin;
        document.getElementById('user-coupon-portal').checked = !!user.coupon_portal_enabled;
        document.getElementById('user-force-password').checked = !!user.must_change_password;
    }

    window.openUserModal = function (user) {
        fillUserForm(user);
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    };

    window.closeUserModal = function () {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    };

    document.querySelectorAll('[data-open-user-modal]').forEach(function (button) {
        button.addEventListener('click', function () {
            var payload = this.getAttribute('data-user');
            if (!payload) return;

            openUserModal(JSON.parse(payload));
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeUserModal();
        }
    });

    if (modalUserId) {
        var trigger = document.querySelector('[data-open-user-modal][data-user*="\\"id\\":' + modalUserId + '"]');
        var currentUser = trigger ? JSON.parse(trigger.getAttribute('data-user')) : null;
        openUserModal(Object.assign({}, currentUser || {}, oldUserData || {}));
    }
})();
</script>
@endsection
