<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] p-4 sm:p-5">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-3">Dados do Cliente</p>
            <p class="font-mono text-sm text-black break-words"><span class="text-[var(--color-lab-muted)]">Nome:</span> {{ $pedido->user?->name ?? $pedido->customer_name ?? 'Guest' }}</p>
            <p class="font-mono text-sm text-black break-all"><span class="text-[var(--color-lab-muted)]">E-mail:</span> {{ $pedido->user?->email ?? $pedido->customer_email ?? 'E-mail não informado' }}</p>
        </div>
        <div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] p-4 sm:p-5">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-3">Endereco de Entrega</p>
            @if($pedido->endereco)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1 font-mono text-sm">
                    <p class="text-black break-words"><span class="text-[var(--color-lab-muted)]">CEP:</span> {{ $pedido->endereco->cep }}</p>
                    <p class="text-black break-words"><span class="text-[var(--color-lab-muted)]">Rua:</span> {{ $pedido->endereco->rua }}</p>
                    <p class="text-black break-words"><span class="text-[var(--color-lab-muted)]">Numero:</span> {{ $pedido->endereco->numero }}</p>
                    <p class="text-black break-words"><span class="text-[var(--color-lab-muted)]">Complemento:</span> {{ $pedido->endereco->complemento ?: '-' }}</p>
                    <p class="text-black break-words"><span class="text-[var(--color-lab-muted)]">Bairro:</span> {{ $pedido->endereco->bairro }}</p>
                    <p class="text-black break-words"><span class="text-[var(--color-lab-muted)]">Cidade:</span> {{ $pedido->endereco->cidade }}</p>
                    <p class="text-black break-words"><span class="text-[var(--color-lab-muted)]">Estado:</span> {{ $pedido->endereco->estado }}</p>
                    <p class="text-black break-words"><span class="text-[var(--color-lab-muted)]">Pais:</span> {{ $pedido->endereco->pais ?? 'Brasil' }}</p>
                </div>
            @else
                <p class="font-mono text-xs text-[var(--color-lab-muted)]">Endereco nao informado</p>
            @endif
        </div>
    </div>

    <div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] p-4 sm:p-5">
        <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-4">Itens do Pedido</p>
        <div class="space-y-3">
            @foreach($pedido->itens as $item)
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center space-x-3 min-w-0">
                        @php
                            $capa = $item->produto->imagens->firstWhere('capa', true) ?: $item->produto->imagens->first();
                        @endphp
                        @if($capa)
                            <img src="{{ asset('storage/' . $capa->caminho) }}" alt="{{ $item->produto->nome }}" class="w-12 h-12 object-cover border border-[var(--color-lab-border)]">
                        @else
                            <div class="w-12 h-12 bg-white border border-[var(--color-lab-border)]"></div>
                        @endif
                        <div class="min-w-0">
                            <p class="font-mono text-sm font-bold text-black break-words">{{ $item->produto->nome }}</p>
                            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Qtd: {{ $item->quantidade }}</p>
                        </div>
                    </div>
                    <div class="text-left sm:text-right">
                        <p class="font-mono text-sm font-bold text-black">R$ {{ number_format($item->preco, 2, ',', '.') }}</p>
                        <p class="font-mono text-[10px] text-[var(--color-lab-muted)]">Subtotal: R$ {{ number_format($item->preco * $item->quantidade, 2, ',', '.') }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="border-t border-[var(--color-lab-border)] mt-4 pt-4 flex justify-start sm:justify-end">
            <p class="font-mono text-base font-bold text-black break-words">Total: R$ {{ number_format($pedido->valor_total, 2, ',', '.') }}</p>
        </div>
    </div>

    @if($pedido->pagamentos && $pedido->pagamentos->count())
    <div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] p-4 sm:p-5">
        <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-4">Pagamento</p>
        @foreach($pedido->pagamentos as $pg)
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between font-mono text-sm py-1">
                <span class="text-black break-words">{{ ucfirst($pg->metodo ?? 'desconhecido') }}</span>
                <span class="text-[var(--color-lab-muted)] break-words">{{ ucfirst($pg->status ?? 'pendente') }}</span>
            </div>
        @endforeach
    </div>
    @endif

    <div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] p-4 sm:p-5">
        <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-3">C&oacute;digo de Rastreio (Correios)</p>
        <div class="flex gap-2 items-center">
            <input type="text"
                   id="rastreio-input-{{ $pedido->id }}"
                   value="{{ $pedido->codigo_rastreio ?? '' }}"
                   placeholder="Ex: BR123456789BR"
                   maxlength="50"
                   class="flex-1 font-mono text-sm px-3 py-2 border border-[var(--color-lab-border)] bg-white focus:outline-none focus:border-black transition-colors uppercase">
            <button onclick="salvarRastreio({{ $pedido->id }})"
                    id="rastreio-btn-{{ $pedido->id }}"
                    class="font-mono text-[10px] uppercase tracking-widest px-3 py-2 border border-black text-black hover:bg-black hover:text-white transition-colors whitespace-nowrap">
                Salvar
            </button>
        </div>
        <p id="rastreio-feedback-{{ $pedido->id }}" class="font-mono text-[10px] text-green-700 mt-1 hidden">Salvo!</p>
        @if($pedido->codigo_rastreio)
        <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-1">
            <a href="https://rastreamento.correios.com.br/app/index.php?objetos={{ $pedido->codigo_rastreio }}"
               target="_blank"
               class="underline hover:text-black">Rastrear no site dos Correios &#8599;</a>
        </p>
        @endif
    </div>
</div>
