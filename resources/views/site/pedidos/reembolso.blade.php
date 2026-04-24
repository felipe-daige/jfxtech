@php
    $isGuestOrder = $pedido->user_id === null;
    $whatsapp = '556721800568';

    if ($pedido->status === 'entregue' && $pedido->updated_at->diffInDays(now()) > 7) {
        $modo = 'garantia';
        $titulo = 'Garantia de Fábrica';
        $waMessage = "Olá, tenho um produto com problema no pedido #{$pedido->id}. Gostaria de acionar a garantia de fábrica.";
    } elseif ($pedido->status === 'entregue') {
        $modo = 'reembolso';
        $titulo = 'Solicitar Reembolso';
        $dataEntrega = $pedido->updated_at->format('d/m/Y');
        $waMessage = "Olá, gostaria de solicitar o reembolso do pedido #{$pedido->id}, recebido em {$dataEntrega}.";
    } else {
        $modo = 'reembolso';
        $titulo = 'Solicitar Reembolso';
        $waMessage = "Olá, gostaria de solicitar o reembolso do pedido #{$pedido->id} (ainda não recebi o produto).";
    }
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $titulo }} — Pedido #{{ $pedido->id }} - JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
</head>
<body class="min-h-screen flex flex-col bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)] antialiased">

@include('includes.header')

<main class="flex-grow">

    {{-- Hero / Breadcrumb --}}
    <div class="bg-white border-b border-[var(--color-lab-border)] py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex flex-wrap text-xs font-mono text-gray-500 uppercase tracking-widest mb-6 gap-1 items-center">
                <a href="{{ route('site.index') }}" class="hover:text-black transition-colors">HOME</a>
                <svg class="w-4 h-4 mx-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                @if(!$isGuestOrder)
                    <a href="{{ route('site.pedidos.index') }}" class="hover:text-black transition-colors">MEUS PEDIDOS</a>
                    <svg class="w-4 h-4 mx-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                @endif
                <a href="{{ route('site.pedidos.show', $pedido) }}" class="hover:text-black transition-colors">PEDIDO #{{ $pedido->id }}</a>
                <svg class="w-4 h-4 mx-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                <span class="text-black">{{ strtoupper($titulo) }}</span>
            </nav>
            <h1 class="text-4xl font-bold tracking-tight mb-2">{{ strtoupper($titulo) }}</h1>
            <p class="text-gray-500 font-mono text-sm">
                @if($modo === 'reembolso')
                    PEDIDO #{{ $pedido->id }} — SOLICITE SEU REEMBOLSO PELO WHATSAPP
                @else
                    PEDIDO #{{ $pedido->id }} — ACIONAMENTO DE GARANTIA DE FÁBRICA
                @endif
            </p>
        </div>
    </div>

    <section class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto space-y-6">

                {{-- Seção 1: Status do Prazo --}}
                @if($modo === 'reembolso' && $pedido->status === 'entregue')
                    @php
                        $diasRestantes = 7 - (int) $pedido->updated_at->diffInDays(now());
                        $prazoFinal = $pedido->updated_at->copy()->addDays(7)->format('d/m/Y');
                    @endphp
                    <div class="bg-green-50 border border-green-400 p-6">
                        <div class="flex items-start gap-4">
                            <div class="mt-0.5 shrink-0">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div class="flex-1">
                                <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-green-700 mb-1">Dentro do prazo legal</p>
                                <h2 class="text-lg font-bold text-green-900 mb-2">Você está dentro do prazo de reembolso</h2>
                                <p class="text-sm text-green-800 mb-4">Compras online têm direito a reembolso integral em até <strong>7 dias após o recebimento</strong> (Art. 49, Código de Defesa do Consumidor).</p>
                                <div class="flex items-center gap-2 text-xs font-mono mt-4">
                                    <div class="text-center">
                                        <div class="w-3 h-3 rounded-full bg-green-600 mx-auto mb-1"></div>
                                        <span class="text-green-700 uppercase tracking-wider">Entregue<br>{{ $pedido->updated_at->format('d/m') }}</span>
                                    </div>
                                    <div class="flex-1 h-px bg-green-400"></div>
                                    <div class="text-center">
                                        <div class="w-4 h-4 rounded-full bg-green-700 border-2 border-green-900 mx-auto mb-1 flex items-center justify-center">
                                            <div class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                        </div>
                                        <span class="text-green-900 font-bold uppercase tracking-wider">Hoje</span>
                                    </div>
                                    <div class="flex-1 h-px bg-gray-300"></div>
                                    <div class="text-center">
                                        <div class="w-3 h-3 rounded-full bg-gray-300 mx-auto mb-1"></div>
                                        <span class="text-gray-500 uppercase tracking-wider">Prazo<br>{{ $prazoFinal }}</span>
                                    </div>
                                </div>
                                <p class="mt-3 text-sm font-mono font-bold text-green-800">
                                    {{ $diasRestantes }} {{ $diasRestantes === 1 ? 'dia restante' : 'dias restantes' }} para solicitar reembolso.
                                </p>
                            </div>
                        </div>
                    </div>

                @elseif($modo === 'reembolso' && in_array($pedido->status, ['pago', 'processando', 'enviado']))
                    <div class="bg-green-50 border border-green-400 p-6">
                        <div class="flex items-start gap-4">
                            <div class="mt-0.5 shrink-0">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div class="flex-1">
                                <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-green-700 mb-1">Produto ainda não entregue</p>
                                <h2 class="text-lg font-bold text-green-900 mb-2">Você tem direito a reembolso integral</h2>
                                <p class="text-sm text-green-800">Seu pedido ainda não foi entregue. O prazo de 7 dias para arrependimento (Art. 49, CDC) começa a contar a partir do <strong>recebimento do produto</strong>. Entre em contato para cancelar e receber o reembolso.</p>
                            </div>
                        </div>
                    </div>

                @else
                    @php $dataFimPrazo = $pedido->updated_at->copy()->addDays(7)->format('d/m/Y'); @endphp
                    <div class="bg-gray-50 border border-gray-300 p-6">
                        <div class="flex items-start gap-4">
                            <div class="mt-0.5 shrink-0">
                                <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div class="flex-1">
                                <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-gray-500 mb-1">Prazo de reembolso encerrado</p>
                                <h2 class="text-lg font-bold text-gray-800 mb-2">O prazo de 7 dias para arrependimento encerrou</h2>
                                <p class="text-sm text-gray-600 mb-4">O prazo legal encerrou em {{ $dataFimPrazo }}. Caso seu produto apresente <strong>defeito ou problema de fabricação</strong>, você pode acionar a Garantia de Fábrica.</p>
                                <div class="flex items-center gap-2 text-xs font-mono mt-4">
                                    <div class="text-center">
                                        <div class="w-3 h-3 rounded-full bg-gray-500 mx-auto mb-1"></div>
                                        <span class="text-gray-600 uppercase tracking-wider">Entregue<br>{{ $pedido->updated_at->format('d/m') }}</span>
                                    </div>
                                    <div class="flex-1 h-px bg-gray-400"></div>
                                    <div class="text-center">
                                        <div class="w-3 h-3 rounded-full bg-gray-400 mx-auto mb-1"></div>
                                        <span class="text-gray-500 uppercase tracking-wider">Prazo<br>{{ $dataFimPrazo }}</span>
                                    </div>
                                    <div class="flex-1 h-px bg-gray-400"></div>
                                    <div class="text-center">
                                        <div class="w-4 h-4 rounded-full bg-gray-700 border-2 border-gray-900 mx-auto mb-1 flex items-center justify-center">
                                            <div class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                        </div>
                                        <span class="text-gray-900 font-bold uppercase tracking-wider">Hoje</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Seção 2: Resumo do Pedido --}}
                <div class="bg-white border border-[var(--color-lab-border)] p-6">
                    <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-gray-500 mb-4">Resumo do Pedido #{{ $pedido->id }}</p>
                    <div class="space-y-4 mb-6">
                        @foreach($pedido->itens as $item)
                        <div class="flex items-center gap-4">
                            @if($item->produto && $item->produto->imagens->isNotEmpty())
                                <img src="{{ asset('storage/' . $item->produto->imagens->first()->caminho) }}"
                                     alt="{{ $item->produto->nome }}"
                                     class="w-16 h-16 object-cover border border-[var(--color-lab-border)] shrink-0">
                            @else
                                <div class="w-16 h-16 bg-gray-100 border border-[var(--color-lab-border)] shrink-0 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-sm truncate">{{ $item->produto->nome ?? 'Produto' }}</p>
                                <p class="text-xs text-gray-500 font-mono">Qtd: {{ $item->quantidade }}</p>
                            </div>
                            <p class="font-mono text-sm font-bold shrink-0">R$ {{ number_format($item->preco * $item->quantidade, 2, ',', '.') }}</p>
                        </div>
                        @endforeach
                    </div>
                    <div class="border-t border-[var(--color-lab-border)] pt-4 space-y-1">
                        @if($pedido->frete_valor > 0)
                        <div class="flex justify-between text-sm text-gray-600">
                            <span class="font-mono">Frete ({{ strtoupper($pedido->frete_tipo ?? '') }})</span>
                            <span class="font-mono">R$ {{ number_format($pedido->frete_valor, 2, ',', '.') }}</span>
                        </div>
                        @endif
                        @if($pedido->valor_desconto > 0)
                        <div class="flex justify-between text-sm text-green-700">
                            <span class="font-mono">Desconto</span>
                            <span class="font-mono">− R$ {{ number_format($pedido->valor_desconto, 2, ',', '.') }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between font-bold text-base border-t border-[var(--color-lab-border)] pt-2 mt-2">
                            <span class="font-mono uppercase tracking-widest text-sm">Total Pago</span>
                            <span class="font-mono">R$ {{ number_format($pedido->valor_total, 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Seção 3: Como Funciona --}}
                <div class="bg-white border border-[var(--color-lab-border)] p-6">
                    <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-gray-500 mb-6">Como Funciona</p>
                    <div class="space-y-6">
                        <div class="flex gap-4">
                            <div class="shrink-0 w-8 h-8 border-2 border-black flex items-center justify-center font-mono font-bold text-sm">1</div>
                            <div>
                                <p class="font-bold text-sm mb-1">Entre em contato pelo WhatsApp</p>
                                <p class="text-sm text-gray-600">Clique no botão abaixo. Uma mensagem já estará pronta com o número do seu pedido — basta enviar.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="shrink-0 w-8 h-8 border-2 border-black flex items-center justify-center font-mono font-bold text-sm">2</div>
                            <div>
                                @if($modo === 'reembolso')
                                    <p class="font-bold text-sm mb-1">Análise da solicitação</p>
                                    <p class="text-sm text-gray-600">Nossa equipe analisa sua solicitação e entra em contato em até <strong>5 a 10 dias úteis</strong>.</p>
                                @else
                                    <p class="font-bold text-sm mb-1">Avaliação do defeito</p>
                                    <p class="text-sm text-gray-600">Nossa equipe orienta sobre o procedimento de envio ou retirada do produto para análise técnica.</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="shrink-0 w-8 h-8 border-2 border-black flex items-center justify-center font-mono font-bold text-sm">3</div>
                            <div>
                                @if($modo === 'reembolso')
                                    <p class="font-bold text-sm mb-1">Reembolso processado</p>
                                    <p class="text-sm text-gray-600">O valor é devolvido pelo mesmo método de pagamento utilizado na compra.</p>
                                @else
                                    <p class="font-bold text-sm mb-1">Resolução</p>
                                    <p class="text-sm text-gray-600">Dependendo da análise técnica: reparo, substituição do produto ou reembolso conforme disponibilidade.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Seção 4: CTA WhatsApp --}}
                <div class="bg-white border border-[var(--color-lab-border)] p-8 text-center">
                    <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-gray-500 mb-3">Pronto para continuar?</p>
                    <h2 class="text-2xl font-bold tracking-tight mb-2">Fale com a gente pelo WhatsApp</h2>
                    <p class="text-sm text-gray-600 mb-8">A mensagem com o número do seu pedido já está pronta. É só clicar e enviar.</p>
                    <a href="https://wa.me/{{ $whatsapp }}?text={{ urlencode($waMessage) }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="inline-flex items-center gap-3 px-8 py-4 bg-black text-white text-sm font-mono font-bold uppercase tracking-widest hover:bg-gray-800 transition-colors">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        {{ $titulo }}
                    </a>
                    <p class="mt-4 text-xs text-gray-400 font-mono">Atendimento via WhatsApp — (67) 2180-0568</p>
                </div>

                {{-- Voltar --}}
                <div class="flex justify-start">
                    <a href="{{ route('site.pedidos.show', $pedido) }}"
                       class="text-xs font-mono uppercase tracking-widest text-gray-500 hover:text-black transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        Voltar ao pedido
                    </a>
                </div>

            </div>
        </div>
    </section>
</main>

@include('includes.footer')
</body>
</html>
