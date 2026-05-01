<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $sorteio->titulo }} - JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
</head>
<body class="min-h-screen flex flex-col bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)] antialiased">
    @include('includes.header')

    @php
        $user = Auth::user();
        $inscricoesAbertas = $sorteio->inscricoesAbertas();
        $resultadoPublicado = $sorteio->resultadoPublicado();
        $ganhador = $sorteio->ganhador;
        $ganhadorInstagram = $ganhador ? '@'.ltrim((string) $ganhador->instagram_username, '@') : null;
        $foiGanhador = $resultadoPublicado && $participacao && $ganhador && (int) $ganhador->id === (int) $participacao->id;
        $produtoSorteio = $sorteio->produto;
        $produtoImagemPrincipal = null;

        if ($produtoSorteio) {
            $produtoImagemPrincipal = $produtoSorteio->id === 120
                ? $produtoSorteio->imagens->firstWhere('caminho', 'produtos/ozDyKocZc4DpwuiI0Sj4xGd6w1KMtocExCVr5Tk6.png')
                : null;
            $produtoImagemPrincipal ??= $produtoSorteio->imagens->firstWhere('capa', true) ?: $produtoSorteio->imagens->first();
        }
    @endphp

    <main class="flex-grow py-10 sm:py-14">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 items-start lg:grid-cols-[0.9fr_1.1fr] gap-6 lg:gap-8">
                <section class="bg-white border border-[var(--color-lab-border)] p-6 sm:p-8 h-fit">
                    <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-3">Sorteio JFXTECH</p>
                    <h1 class="text-3xl sm:text-4xl font-bold tracking-tight mb-4">{{ $sorteio->titulo }}</h1>

                    @if($sorteio->premio)
                        <div class="border border-black px-4 py-3 mb-5">
                            <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500">Prêmio</p>
                            <p class="font-bold text-lg">{{ $sorteio->premio }}</p>
                        </div>
                    @endif

                    @if($sorteio->descricao)
                        <p class="text-sm text-gray-600 leading-6 mb-6">{{ $sorteio->descricao }}</p>
                    @endif

                    @if($produtoSorteio)
                        <div class="border border-black mb-5 bg-white">
                            <div class="bg-[var(--color-lab-bg)] p-4">
                                <div class="h-56 sm:h-64 lg:h-72 flex items-center justify-center">
                                    @if($produtoImagemPrincipal)
                                        <div class="w-full h-full overflow-hidden" tabindex="0" aria-label="Imagem do produto com zoom">
                                            <img src="{{ asset('storage/' . $produtoImagemPrincipal->caminho) }}" alt="{{ $produtoSorteio->nome }}" class="w-full h-full object-contain mix-blend-multiply transition-transform duration-300 hover:scale-125 focus:scale-125">
                                        </div>
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <svg class="w-16 h-16 text-gray-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="p-4">
                                <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-1.5">Produto do sorteio</p>
                                <h2 class="text-lg font-bold tracking-tight mb-1">{{ $produtoSorteio->nome }}</h2>
                                @if($produtoSorteio->descricao_curta)
                                    <p class="text-sm text-gray-500 leading-5 mb-3">{{ $produtoSorteio->descricao_curta }}</p>
                                @endif
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="font-mono text-base font-bold">R$ {{ number_format($produtoSorteio->preco, 2, ',', '.') }}</p>
                                        <p class="font-mono text-[10px] uppercase tracking-widest {{ $produtoSorteio->estoque > 0 ? 'text-green-700' : 'text-red-600' }}">
                                            {{ $produtoSorteio->estoque > 0 ? 'Disponível na loja' : 'Indisponível no momento' }}
                                        </p>
                                    </div>
                                    <div class="flex flex-col gap-2 sm:flex-row">
                                        <a href="{{ route('site.produto.detalhes', $produtoSorteio->slug) }}" class="inline-flex justify-center bg-black text-white px-4 py-2.5 text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition-colors">
                                            Ver produto
                                        </a>
                                        <a href="{{ route('site.produtos') }}" class="inline-flex justify-center border border-black px-4 py-2.5 text-xs font-bold uppercase tracking-widest hover:bg-black hover:text-white transition-colors">
                                            Ver loja
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="space-y-2">
                        <div class="flex items-start gap-3 border border-[var(--color-lab-border)] p-3">
                            <span class="font-mono font-bold text-sm">01</span>
                            <p class="text-sm text-gray-600">Siga a página @jfxtech no Instagram.</p>
                        </div>
                        <div class="flex items-start gap-3 border border-[var(--color-lab-border)] p-3">
                            <span class="font-mono font-bold text-sm">02</span>
                            <p class="text-sm text-gray-600">Curta o post destacado do sorteio.</p>
                        </div>
                        <div class="flex items-start gap-3 border border-[var(--color-lab-border)] p-3">
                            <span class="font-mono font-bold text-sm">03</span>
                            <p class="text-sm text-gray-600">Comente marcando 2 amigos e preencha o formulário nesta página.</p>
                        </div>
                    </div>

                    @if($sorteio->instagram_post_url)
                        <a href="{{ $sorteio->instagram_post_url }}" target="_blank" rel="noopener" class="inline-flex mt-6 border border-black px-5 py-3 text-xs font-bold uppercase tracking-widest hover:bg-black hover:text-white transition-colors">
                            Abrir post oficial
                        </a>
                    @endif

                    <a href="https://www.instagram.com/jfxtech/" target="_blank" rel="noopener" class="inline-flex mt-3 w-full justify-center bg-black text-white px-5 py-3 text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition-colors">
                        Visitar Instagram @jfxtech
                    </a>

                    <div class="mt-6 text-xs text-gray-500 leading-5">
                        A participação fica sujeita a auditoria. Se o número sorteado não tiver cumprido as regras no Instagram, ele poderá ser desclassificado antes da publicação do resultado final.
                        Esta promoção não é patrocinada, endossada, administrada ou associada ao Instagram.
                    </div>
                </section>

                <section class="bg-white border border-[var(--color-lab-border)] p-6 h-fit">
                    <div class="flex items-start justify-between gap-4 mb-5">
                        <div>
                            <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-2">{{ $resultadoPublicado ? 'Resultado' : 'Inscrição' }}</p>
                            <h2 class="text-2xl font-bold tracking-tight">{{ $resultadoPublicado ? 'Resultado publicado' : 'Receba seu número' }}</h2>
                            @if($resultadoPublicado)
                                <p class="mt-2 text-sm text-gray-500 leading-5">O resultado final deste sorteio já está disponível para todos.</p>
                            @else
                                <p class="mt-2 text-sm text-gray-500 leading-5">Preencha todos os campos marcados com * e confirme as regras obrigatórias para liberar o botão.</p>
                            @endif
                        </div>
                        <span class="shrink-0 px-3 py-1 text-[10px] font-mono uppercase tracking-widest border {{ $resultadoPublicado || $inscricoesAbertas ? 'border-black bg-black text-white' : 'border-gray-300 text-gray-400' }}">
                            {{ $resultadoPublicado ? 'Publicado' : ($inscricoesAbertas ? 'Aberto' : 'Fechado') }}
                        </span>
                    </div>

                    @if ($errors->any())
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm mb-5">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 text-sm mb-5">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm mb-5">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if($resultadoPublicado && $ganhador)
                        <div class="border border-black bg-black text-white p-5 sm:p-6">
                            <p class="font-mono text-[10px] uppercase tracking-widest text-white/60 mb-2">Ganhador</p>
                            <p class="font-mono text-5xl font-bold tracking-tight mb-4">{{ $ganhador->numeroFormatado() }}</p>
                            <div class="border-t border-white/20 pt-4">
                                <p class="text-xl font-bold">{{ $ganhador->user?->name }}</p>
                                <p class="font-mono text-sm text-white/70 mt-1 break-words">{{ $ganhadorInstagram }}</p>
                            </div>
                        </div>

                        <div class="mt-4 border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] p-4 text-sm text-gray-600 leading-6">
                            @if($foiGanhador)
                                <p class="font-bold text-black mb-1">Seu número foi sorteado.</p>
                                <p>A equipe JFXTECH fará a conferência final e entrará em contato pelos dados cadastrados.</p>
                            @elseif($participacao)
                                <p>Seu número não foi o sorteado nesta campanha.</p>
                            @else
                                <p>Resultado publicado em {{ $sorteio->resultado_publicado_at->format('d/m/Y H:i') }}.</p>
                            @endif
                        </div>

                        <div class="mt-5 flex flex-col sm:flex-row gap-3">
                            @if($participacao)
                                <a href="{{ route('site.sorteio.acompanhar', $sorteio) }}" class="inline-flex w-full sm:w-auto justify-center bg-black text-white px-5 py-3 text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition-colors">
                                    Minha participação
                                </a>
                            @endif
                            <a href="{{ route('site.sorteio.index') }}" class="inline-flex w-full sm:w-auto justify-center border border-black px-5 py-3 text-xs font-bold uppercase tracking-widest hover:bg-black hover:text-white transition-colors">
                                Ver sorteios
                            </a>
                        </div>
                    @elseif($participacao)
                        <div class="border border-black p-6 text-center">
                            <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-2">Seu número</p>
                            <p class="font-mono text-5xl font-bold tracking-tight">{{ $participacao->numeroFormatado() }}</p>
                            <a href="{{ route('site.sorteio.acompanhar', $sorteio) }}" class="inline-flex mt-6 bg-black text-white px-6 py-3 text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition-colors">
                                Acompanhar sorteio
                            </a>
                        </div>
                    @elseif(! $inscricoesAbertas)
                        <div class="border border-[var(--color-lab-border)] p-6 text-center text-sm text-gray-500">
                            As inscrições deste sorteio não estão abertas.
                        </div>
                    @else
                        <form method="POST" action="{{ route('site.sorteio.participar', $sorteio) }}" class="space-y-4" id="sorteio-form">
                            @csrf

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="sm:col-span-2">
                                    <label class="block text-[10px] font-mono font-bold uppercase tracking-widest mb-2 text-gray-500">Nome completo <span class="text-red-600">*</span></label>
                                    <input type="text" name="name" value="{{ old('name', $user?->name) }}" class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black" required>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-mono font-bold uppercase tracking-widest mb-2 text-gray-500">E-mail <span class="text-red-600">*</span></label>
                                    <input type="email" name="email" value="{{ old('email', $user?->email) }}" class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black" required>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-mono font-bold uppercase tracking-widest mb-2 text-gray-500">WhatsApp <span class="text-red-600">*</span></label>
                                    <input type="tel" name="phone" id="sorteio-phone" value="{{ old('phone', $user?->phone) }}" placeholder="(11) 99999-9999" class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black" required>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-mono font-bold uppercase tracking-widest mb-2 text-gray-500">CPF <span class="text-red-600">*</span></label>
                                    <input type="text" name="cpf" id="sorteio-cpf" value="{{ old('cpf', $user?->cpf) }}" maxlength="14" placeholder="000.000.000-00" class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black" required>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-mono font-bold uppercase tracking-widest mb-2 text-gray-500">Instagram <span class="text-red-600">*</span></label>
                                    <div class="flex border border-[var(--color-lab-border)] bg-white focus-within:border-black">
                                        <span class="flex items-center px-3 font-mono text-sm text-gray-400">@</span>
                                        <input type="text" name="instagram_username" value="{{ old('instagram_username') }}" placeholder="seuusuario" class="w-full px-1 py-3 pr-4 text-sm font-mono focus:outline-none" required>
                                    </div>
                                    <p class="mt-1 text-[10px] font-mono uppercase tracking-widest text-gray-400">Digite apenas o nome, sem @.</p>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-mono font-bold uppercase tracking-widest mb-2 text-gray-500">Amigo 1 marcado <span class="text-red-600">*</span></label>
                                    <div class="flex border border-[var(--color-lab-border)] bg-white focus-within:border-black">
                                        <span class="flex items-center px-3 font-mono text-sm text-gray-400">@</span>
                                        <input type="text" name="instagram_friend_1" value="{{ old('instagram_friend_1') }}" placeholder="amigo1" class="w-full px-1 py-3 pr-4 text-sm font-mono focus:outline-none" required>
                                    </div>
                                    <p class="mt-1 text-[10px] font-mono uppercase tracking-widest text-gray-400">Use um usuário diferente do seu.</p>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-mono font-bold uppercase tracking-widest mb-2 text-gray-500">Amigo 2 marcado <span class="text-red-600">*</span></label>
                                    <div class="flex border border-[var(--color-lab-border)] bg-white focus-within:border-black">
                                        <span class="flex items-center px-3 font-mono text-sm text-gray-400">@</span>
                                        <input type="text" name="instagram_friend_2" value="{{ old('instagram_friend_2') }}" placeholder="amigo2" class="w-full px-1 py-3 pr-4 text-sm font-mono focus:outline-none" required>
                                    </div>
                                    <p class="mt-1 text-[10px] font-mono uppercase tracking-widest text-gray-400">Os 3 usuários precisam ser diferentes.</p>
                                </div>

                                @guest
                                    <div>
                                        <label class="block text-[10px] font-mono font-bold uppercase tracking-widest mb-2 text-gray-500">Senha <span class="text-red-600">*</span></label>
                                        <input type="password" name="password" class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black" required>
                                    </div>

                                    <div>
                                        <label class="block text-[10px] font-mono font-bold uppercase tracking-widest mb-2 text-gray-500">Confirmar senha <span class="text-red-600">*</span></label>
                                        <input type="password" name="password_confirmation" class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black" required>
                                    </div>
                                @endguest
                            </div>

                            <p id="instagram-unique-error" class="hidden text-xs text-red-600">
                                Informe apenas nomes sem @ e use 3 usuários diferentes: seu Instagram, amigo 1 e amigo 2 não podem ser iguais.
                            </p>

                            <div class="space-y-3 border-t border-[var(--color-lab-border)]" style="padding-top: 1.5rem; margin-top: 0.25rem;">
                                <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500">Confirmações obrigatórias <span class="text-red-600">*</span></p>
                                <label class="flex items-start gap-3 text-sm leading-5 text-gray-600">
                                    <input type="checkbox" name="instagram_requirements" value="1" class="tech-checkbox mt-1" required>
                                    <span>Confirmei que segui @jfxtech, curti o post destacado e marquei 2 amigos. <span class="text-red-600">*</span></span>
                                </label>
                                <label class="flex items-start gap-3 text-sm leading-5 text-gray-600">
                                    <input type="checkbox" name="rules" value="1" class="tech-checkbox mt-1" required>
                                    <span>Aceito o regulamento e a auditoria da participação antes da confirmação do ganhador.<span class="whitespace-nowrap"> <span class="text-red-600">*</span></span></span>
                                </label>
                                <label class="flex items-start gap-3 text-sm leading-5 text-gray-600">
                                    <input type="checkbox" name="marketing_opt_in" value="1" class="tech-checkbox mt-1" checked>
                                    <span>Quero receber ofertas, novidades e comunicações da JFXTECH.</span>
                                </label>
                            </div>

                            <button type="submit" id="sorteio-submit" disabled class="w-full bg-black text-white py-4 font-bold tracking-widest uppercase text-sm hover:bg-gray-800 transition-colors disabled:cursor-not-allowed disabled:bg-gray-300 disabled:text-gray-500">
                                Gerar meu número
                            </button>
                        </form>
                    @endif
                </section>
            </div>
        </div>
    </main>

    @include('includes.footer')

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#sorteio-phone').mask('(00) 00000-0000');
            $('#sorteio-cpf').mask('000.000.000-00');

            var sorteioForm = document.getElementById('sorteio-form');
            var sorteioSubmit = document.getElementById('sorteio-submit');

            function updateSorteioSubmitState() {
                if (!sorteioForm || !sorteioSubmit) return;

                var requiredFields = Array.from(sorteioForm.querySelectorAll('input[required]'));
                var isComplete = requiredFields.every(function(field) {
                    if (field.type === 'checkbox') {
                        return field.checked;
                    }

                    return field.value.trim().length > 0;
                });
                var instagramFields = [
                    sorteioForm.querySelector('[name="instagram_username"]'),
                    sorteioForm.querySelector('[name="instagram_friend_1"]'),
                    sorteioForm.querySelector('[name="instagram_friend_2"]')
                ];
                var instagramValues = instagramFields
                    .map(function(field) {
                        return field ? field.value.trim().replace(/^@+/, '').toLowerCase() : '';
                    })
                    .filter(Boolean);
                var hasDuplicatedInstagram = instagramValues.length === 3 && new Set(instagramValues).size !== 3;
                var instagramUniqueError = document.getElementById('instagram-unique-error');

                if (instagramUniqueError) {
                    instagramUniqueError.classList.toggle('hidden', !hasDuplicatedInstagram);
                }

                sorteioSubmit.disabled = !isComplete || hasDuplicatedInstagram;
            }

            if (sorteioForm) {
                var fieldLabels = {
                    name: 'Nome completo',
                    email: 'E-mail',
                    phone: 'WhatsApp',
                    cpf: 'CPF',
                    instagram_username: 'Instagram',
                    instagram_friend_1: 'Amigo 1 marcado',
                    instagram_friend_2: 'Amigo 2 marcado',
                    password: 'Senha',
                    password_confirmation: 'Confirmar senha',
                    instagram_requirements: 'confirmação das regras do Instagram',
                    rules: 'aceite do regulamento'
                };

                function getValidationMessage(field) {
                    var label = fieldLabels[field.name] || 'este campo';

                    if (field.validity.valueMissing) {
                        if (field.type === 'checkbox') {
                            return 'Marque a opção obrigatória: ' + label + '.';
                        }

                        return 'Preencha o campo ' + label + '.';
                    }

                    if (field.validity.typeMismatch && field.type === 'email') {
                        return 'Digite um e-mail válido.';
                    }

                    if (field.validity.tooShort) {
                        return 'Use pelo menos ' + field.minLength + ' caracteres.';
                    }

                    return '';
                }

                function applyPortugueseValidation(field) {
                    field.setCustomValidity('');

                    if (!field.validity.valid) {
                        field.setCustomValidity(getValidationMessage(field));
                    }
                }

                Array.from(sorteioForm.querySelectorAll('input[required]')).forEach(function(field) {
                    field.addEventListener('invalid', function() {
                        applyPortugueseValidation(field);
                    });
                    field.addEventListener('input', function() {
                        field.setCustomValidity('');
                    });
                    field.addEventListener('change', function() {
                        field.setCustomValidity('');
                    });
                });

                sorteioForm.addEventListener('input', updateSorteioSubmitState);
                sorteioForm.addEventListener('change', updateSorteioSubmitState);
                sorteioForm.addEventListener('submit', function(event) {
                    Array.from(sorteioForm.querySelectorAll('input[required]')).forEach(applyPortugueseValidation);

                    if (!sorteioForm.checkValidity()) {
                        event.preventDefault();
                        sorteioForm.reportValidity();
                    }
                });
                updateSorteioSubmitState();
            }
        });
    </script>
</body>
</html>
