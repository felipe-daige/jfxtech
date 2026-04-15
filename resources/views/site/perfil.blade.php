<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Meu Perfil - JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
</head>
<body class="min-h-screen flex flex-col bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)] antialiased">

    @include('includes.header')

    <main class="flex-grow">
        <section class="py-16">
            <div class="container mx-auto px-4 lg:px-8">
                <div class="max-w-4xl mx-auto">

                    <!-- Page title -->
                    <div class="mb-10">
                        <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">Conta</span>
                        <h1 class="text-3xl font-bold uppercase tracking-wider text-black mt-1">Meu Perfil</h1>
                    </div>

                    @if(session('success'))
                        <div class="bg-white border border-black p-4 mb-6">
                            <p class="font-mono text-sm text-black">{{ session('success') }}</p>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="bg-white border border-black p-4 mb-6">
                            <p class="font-mono text-sm text-black">{{ session('error') }}</p>
                        </div>
                    @endif

                    <!-- Profile card -->
                    <div class="bg-white border border-[var(--color-lab-border)] p-8 mb-8">
                        <div class="flex flex-col md:flex-row items-center md:items-start space-y-6 md:space-y-0 md:space-x-8">
                            <div class="w-20 h-20 bg-black flex items-center justify-center flex-shrink-0">
                                <span id="profile-avatar-initial" class="text-white text-2xl font-bold font-mono">{{ strtoupper(substr($usuario->name, 0, 1)) }}</span>
                            </div>
                            <div class="flex-1 text-center md:text-left">
                                <h2 id="profile-name-display" class="text-2xl font-bold text-black mb-1">{{ $usuario->name }}</h2>
                                <p class="text-[var(--color-lab-muted)] text-sm font-mono mb-4">{{ $usuario->email }}</p>
                                <div class="flex flex-wrap justify-center md:justify-start gap-3">
                                    <span class="inline-flex items-center px-3 py-1 border border-[var(--color-lab-border)] text-[10px] font-mono uppercase tracking-widest text-gray-500">
                                        <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Conta Ativa
                                    </span>
                                    <span class="inline-flex items-center px-3 py-1 border border-[var(--color-lab-border)] text-[10px] font-mono uppercase tracking-widest text-gray-500">
                                        <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                        Membro desde {{ $usuario->created_at->format('m/Y') }}
                                    </span>
                                    @if($canAccessCouponPortal)
                                        <a href="{{ route('site.cupom') }}" class="inline-flex items-center px-3 py-1 bg-black text-white text-[10px] font-mono uppercase tracking-widest hover:bg-gray-900 transition-colors">
                                            Meu Cupom
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab navigation -->
                    <div class="border-b border-[var(--color-lab-border)] mb-8">
                        <nav class="flex overflow-x-auto">
                            <button data-tab="dados"      class="tab-btn px-5 py-3 font-mono text-[11px] uppercase tracking-widest border-b-2 whitespace-nowrap transition-colors">Dados Pessoais</button>
                            <button data-tab="enderecos"  class="tab-btn px-5 py-3 font-mono text-[11px] uppercase tracking-widest border-b-2 whitespace-nowrap transition-colors">Endereços</button>
                            <button data-tab="seguranca"  class="tab-btn px-5 py-3 font-mono text-[11px] uppercase tracking-widest border-b-2 whitespace-nowrap transition-colors">Segurança</button>
                            <button data-tab="pedidos"    class="tab-btn px-5 py-3 font-mono text-[11px] uppercase tracking-widest border-b-2 whitespace-nowrap transition-colors">Pedidos</button>
                        </nav>
                    </div>

                    <!-- ===== ABA: DADOS PESSOAIS ===== -->
                    <div id="tab-dados" class="tab-content hidden">
                        <div class="bg-white border border-[var(--color-lab-border)] p-6 max-w-lg">
                            <form id="form-dados-pessoais" action="{{ route('site.perfil.update') }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="_section" value="dados">

                                <div class="mb-5">
                                    <label class="font-mono text-[10px] uppercase tracking-widest text-gray-500 block mb-2">Nome Completo</label>
                                    <input type="text" name="name" id="field-name"
                                        value="{{ $usuario->name }}"
                                        class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black transition-colors">
                                </div>

                                <div class="mb-5">
                                    <label class="font-mono text-[10px] uppercase tracking-widest text-gray-500 block mb-2">E-mail</label>
                                    <input type="email" value="{{ $usuario->email }}" readonly
                                        class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono bg-gray-50 text-gray-400 cursor-not-allowed">
                                    <p class="text-[10px] text-[var(--color-lab-muted)] font-mono mt-1">O e-mail não pode ser alterado.</p>
                                </div>

                                <div class="mb-6">
                                    <label class="font-mono text-[10px] uppercase tracking-widest text-gray-500 block mb-2">Telefone</label>
                                    <input type="text" name="phone" id="field-phone"
                                        value="{{ $usuario->phone ?? '' }}"
                                        placeholder="(11) 99999-9999"
                                        class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black transition-colors">
                                </div>

                                <div class="mb-6">
                                    <label class="font-mono text-[10px] uppercase tracking-widest text-gray-500 block mb-2">CPF</label>
                                    <input type="text" name="cpf" id="field-cpf"
                                        value="{{ $usuario->cpf ?? '' }}"
                                        placeholder="000.000.000-00"
                                        class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black transition-colors">
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit" id="btn-salvar-dados"
                                        class="bg-black text-white px-8 py-3 text-sm font-mono uppercase tracking-widest hover:bg-gray-900 transition-colors">
                                        Salvar Alterações
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- ===== ABA: ENDEREÇOS ===== -->
                    <div id="tab-enderecos" class="tab-content hidden">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-sm font-mono uppercase tracking-widest text-black font-bold">Meus Endereços</h3>
                            <button data-open-endereco-modal
                                class="bg-black text-white px-5 py-2 text-xs font-mono uppercase tracking-widest hover:bg-gray-900 transition-colors">
                                + Adicionar
                            </button>
                        </div>

                        <div id="enderecos-list" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @forelse($enderecos as $endereco)
                                <div class="endereco-card bg-white border border-[var(--color-lab-border)] p-5"
                                    data-id="{{ $endereco->id }}"
                                    data-endereco="{{ json_encode(['id'=>$endereco->id,'cep'=>$endereco->cep,'cep_formatado'=>$endereco->cep_formatado,'rua'=>$endereco->rua,'numero'=>$endereco->numero,'complemento'=>$endereco->complemento,'bairro'=>$endereco->bairro,'cidade'=>$endereco->cidade,'estado'=>$endereco->estado]) }}">
                                    <p class="text-sm text-black mb-1">{{ $endereco->rua }}, {{ $endereco->numero }}{{ $endereco->complemento ? ', '.$endereco->complemento : '' }}</p>
                                    <p class="text-sm text-black mb-1">{{ $endereco->bairro }}</p>
                                    <p class="text-sm text-[var(--color-lab-muted)] font-mono mb-4">{{ $endereco->cidade }}/{{ $endereco->estado }} — CEP {{ $endereco->cep_formatado }}</p>
                                    <div class="card-actions flex space-x-3">
                                        <button data-edit-endereco="{{ $endereco->id }}"
                                            class="text-[11px] font-mono uppercase tracking-widest text-black border border-[var(--color-lab-border)] px-3 py-1.5 hover:bg-gray-50 transition-colors">
                                            Editar
                                        </button>
                                        <button data-confirm-delete="{{ $endereco->id }}"
                                            class="text-[11px] font-mono uppercase tracking-widest text-gray-500 hover:text-red-600 transition-colors">
                                            Excluir
                                        </button>
                                    </div>
                                    <div class="confirm-delete hidden mt-3 pt-3 border-t border-[var(--color-lab-border)]">
                                        <p class="text-[11px] font-mono text-gray-500 mb-2">Confirmar exclusão?</p>
                                        <div class="flex space-x-2">
                                            <button data-do-delete="{{ $endereco->id }}"
                                                class="text-[11px] font-mono uppercase text-white bg-black px-4 py-1.5">Sim, excluir</button>
                                            <button data-cancel-delete="{{ $endereco->id }}"
                                                class="text-[11px] font-mono uppercase px-4 py-1.5 border border-[var(--color-lab-border)]">Cancelar</button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div id="enderecos-empty" class="col-span-2 bg-white border border-[var(--color-lab-border)] p-10 text-center">
                                    <svg class="w-8 h-8 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <p class="text-sm text-[var(--color-lab-muted)] font-mono mb-4">Nenhum endereço cadastrado.</p>
                                    <button data-open-endereco-modal
                                        class="bg-black text-white px-6 py-2 text-xs font-mono uppercase tracking-widest hover:bg-gray-900 transition-colors">
                                        Adicionar Endereço
                                    </button>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- ===== ABA: SEGURANÇA ===== -->
                    <div id="tab-seguranca" class="tab-content hidden">
                        <div class="bg-white border border-[var(--color-lab-border)] p-6 max-w-lg">
                            <form id="form-seguranca" action="{{ route('site.perfil.update') }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="_section" value="seguranca">

                                <div class="mb-5">
                                    <label class="font-mono text-[10px] uppercase tracking-widest text-gray-500 block mb-2">Senha Atual</label>
                                    <input type="password" name="current_password" id="field-current-password"
                                        placeholder="Digite sua senha atual"
                                        class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black transition-colors">
                                </div>

                                <div class="mb-5">
                                    <label class="font-mono text-[10px] uppercase tracking-widest text-gray-500 block mb-2">Nova Senha</label>
                                    <input type="password" name="new_password" id="field-new-password"
                                        placeholder="Mínimo 6 caracteres"
                                        class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black transition-colors">
                                </div>

                                <div class="mb-6">
                                    <label class="font-mono text-[10px] uppercase tracking-widest text-gray-500 block mb-2">Confirmar Nova Senha</label>
                                    <input type="password" name="new_password_confirmation" id="field-confirm-password"
                                        placeholder="Repita a nova senha"
                                        class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black transition-colors">
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit" id="btn-salvar-senha"
                                        class="bg-black text-white px-8 py-3 text-sm font-mono uppercase tracking-widest hover:bg-gray-900 transition-colors">
                                        Alterar Senha
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- ===== ABA: PEDIDOS ===== -->
                    <div id="tab-pedidos" class="tab-content hidden">

                        <!-- Stats -->
                        <div class="grid grid-cols-2 gap-4 mb-8">
                            <div class="bg-white border border-[var(--color-lab-border)] p-6 text-center">
                                <span class="font-mono text-3xl font-bold text-black block">{{ $totalPedidos }}</span>
                                <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mt-1">Pedidos</p>
                            </div>
                            <div class="bg-white border border-[var(--color-lab-border)] p-6 text-center">
                                <span class="font-mono text-3xl font-bold text-black block">{{ $totalFavoritos }}</span>
                                <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mt-1">Favoritos</p>
                            </div>
                        </div>

                        @if($pedidosRecentes->isEmpty())
                            <div class="bg-white border border-[var(--color-lab-border)] p-10 text-center">
                                <svg class="w-8 h-8 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                <p class="text-sm text-[var(--color-lab-muted)] font-mono mb-4">Nenhum pedido realizado ainda.</p>
                                <a href="{{ route('site.produtos') }}"
                                    class="bg-black text-white px-6 py-2 text-xs font-mono uppercase tracking-widest hover:bg-gray-900 transition-colors inline-block">
                                    Ver Produtos
                                </a>
                            </div>
                        @else
                            <div class="bg-white border border-[var(--color-lab-border)]">
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead>
                                            <tr class="border-b border-[var(--color-lab-border)]">
                                                <th class="text-left px-6 py-3 font-mono text-[10px] uppercase tracking-widest text-gray-500">Pedido</th>
                                                <th class="text-left px-6 py-3 font-mono text-[10px] uppercase tracking-widest text-gray-500">Data</th>
                                                <th class="text-left px-6 py-3 font-mono text-[10px] uppercase tracking-widest text-gray-500">Valor</th>
                                                <th class="text-left px-6 py-3 font-mono text-[10px] uppercase tracking-widest text-gray-500">Status</th>
                                                <th class="px-6 py-3"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($pedidosRecentes as $pedido)
                                                <tr class="border-b border-[var(--color-lab-border)] last:border-0">
                                                    <td class="px-6 py-4 font-mono text-sm text-black">#{{ $pedido->id }}</td>
                                                    <td class="px-6 py-4 text-sm text-[var(--color-lab-muted)] font-mono">{{ $pedido->created_at->format('d/m/Y') }}</td>
                                                    <td class="px-6 py-4 font-mono text-sm text-black">R$ {{ number_format($pedido->valor_total, 2, ',', '.') }}</td>
                                                    <td class="px-6 py-4">
                                                        @php
                                                            $badgeClass = match($pedido->status) {
                                                                'pendente'    => 'border border-gray-300 text-gray-500',
                                                                'processando' => 'border border-black text-black',
                                                                'enviado'     => 'bg-black text-white',
                                                                'entregue'    => 'bg-black text-white',
                                                                'cancelado'   => 'border border-red-500 text-red-500',
                                                                default       => 'border border-gray-300 text-gray-500',
                                                            };
                                                        @endphp
                                                        <span class="inline-block px-2 py-1 font-mono text-[10px] uppercase tracking-widest {{ $badgeClass }}">
                                                            {{ $pedido->status }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 text-right">
                                                        <a href="{{ route('site.pedidos.show', $pedido->id) }}"
                                                            class="font-mono text-[11px] uppercase tracking-widest text-black border border-[var(--color-lab-border)] px-3 py-1.5 hover:bg-gray-50 transition-colors">
                                                            Ver
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="p-4 border-t border-[var(--color-lab-border)] text-center">
                                    <a href="{{ route('site.pedidos.index') }}"
                                        class="font-mono text-[11px] uppercase tracking-widest text-black hover:underline">
                                        Ver todos os pedidos →
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </section>
    </main>

    <!-- ===== MODAL: ENDEREÇO ===== -->
    <div id="modal-endereco" class="fixed inset-0 bg-black/30 backdrop-blur-sm z-50 opacity-0 invisible transition-all duration-300">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white border border-[var(--color-lab-border)] w-full max-w-md">
                <div class="flex items-center justify-between p-6 border-b border-[var(--color-lab-border)]">
                    <h3 id="modal-endereco-title" class="text-sm font-mono uppercase tracking-widest text-black font-bold">Novo Endereço</h3>
                    <button id="close-endereco-modal" class="text-[var(--color-lab-muted)] hover:text-black transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6">
                    <form id="form-endereco">
                        <div class="grid grid-cols-3 gap-4 mb-4">
                            <div class="col-span-2">
                                <label class="font-mono text-[10px] uppercase tracking-widest text-gray-500 block mb-2">CEP</label>
                                <input type="text" name="cep" id="endereco-cep" placeholder="00000-000"
                                    class="w-full border border-[var(--color-lab-border)] px-3 py-2.5 text-sm font-mono focus:outline-none focus:border-black transition-colors">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="font-mono text-[10px] uppercase tracking-widest text-gray-500 block mb-2">Rua / Logradouro</label>
                            <input type="text" name="rua" id="endereco-rua"
                                class="w-full border border-[var(--color-lab-border)] px-3 py-2.5 text-sm font-mono focus:outline-none focus:border-black transition-colors">
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="font-mono text-[10px] uppercase tracking-widest text-gray-500 block mb-2">Número</label>
                                <input type="text" name="numero" id="endereco-numero"
                                    class="w-full border border-[var(--color-lab-border)] px-3 py-2.5 text-sm font-mono focus:outline-none focus:border-black transition-colors">
                            </div>
                            <div>
                                <label class="font-mono text-[10px] uppercase tracking-widest text-gray-500 block mb-2">Complemento</label>
                                <input type="text" name="complemento" id="endereco-complemento" placeholder="Opcional"
                                    class="w-full border border-[var(--color-lab-border)] px-3 py-2.5 text-sm font-mono focus:outline-none focus:border-black transition-colors">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="font-mono text-[10px] uppercase tracking-widest text-gray-500 block mb-2">Bairro</label>
                            <input type="text" name="bairro" id="endereco-bairro"
                                class="w-full border border-[var(--color-lab-border)] px-3 py-2.5 text-sm font-mono focus:outline-none focus:border-black transition-colors">
                        </div>

                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <div class="col-span-2">
                                <label class="font-mono text-[10px] uppercase tracking-widest text-gray-500 block mb-2">Cidade</label>
                                <input type="text" name="cidade" id="endereco-cidade"
                                    class="w-full border border-[var(--color-lab-border)] px-3 py-2.5 text-sm font-mono focus:outline-none focus:border-black transition-colors">
                            </div>
                            <div>
                                <label class="font-mono text-[10px] uppercase tracking-widest text-gray-500 block mb-2">Estado</label>
                                <input type="text" name="estado" id="endereco-estado" placeholder="SP" maxlength="2"
                                    class="w-full border border-[var(--color-lab-border)] px-3 py-2.5 text-sm font-mono focus:outline-none focus:border-black transition-colors uppercase">
                            </div>
                        </div>

                        <div class="flex space-x-3">
                            <button type="button" id="cancel-endereco-modal"
                                class="flex-1 border border-[var(--color-lab-border)] text-black py-3 text-sm font-mono uppercase tracking-widest hover:bg-gray-50 transition-colors">
                                Cancelar
                            </button>
                            <button type="submit" id="submit-endereco"
                                class="flex-1 bg-black text-white py-3 text-sm font-mono uppercase tracking-widest hover:bg-gray-900 transition-colors">
                                Salvar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @include('includes.footer')

    <script src="{{ asset('js/dropdowns.js') }}"></script>
    <script src="{{ asset('js/profile-edit.js') }}"></script>
</body>
</html>
