# PIX vs. Cartão — Pricing Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Inverter a semântica de preço — `produto->preco` (DB) vira o preço PIX; cartão = PIX × 1.05 — com cobrança real por método no checkout.

**Architecture:** Accessor `preco_cartao` no model Produto calcula o preço de cartão. Views e JS de produto exibem ambos. O checkout recebe `payment_method_category` do frontend e o `preparar` aplica o multiplicador de 5% se for cartão, gravando o `valor_total` correto no pedido antes de inicializar o MP Brick.

**Tech Stack:** Laravel 12 / PHP 8.3, Blade, Vanilla JS, Tailwind CSS 4, Mercado Pago Brick

---

## Files Modified

| Arquivo | O que muda |
|---|---|
| `app/Models/Produto.php` | Novo accessor `getPrecoCartaoAttribute()` |
| `resources/views/components/product-card.blade.php` | PIX = `preco_com_desconto`, parcelas = `preco_cartao / 10` |
| `resources/views/site/produto-detalhes.blade.php` | PIX = `preco_com_desconto`, parcelas = `preco_cartao / 10` |
| `public/js/produto-detalhes.js` | Variantes: PIX = `preco_efetivo`, cartão = `preco_efetivo × 1.05` |
| `resources/views/site/checkout.blade.php` | Toggle PIX/Cartão + JS atualiza total + passa método ao `preparar` |
| `app/Http/Controllers/MercadoPagoCheckoutController.php` | Aceita `payment_method_category` e aplica markup no `prepare()` |
| `tests/Unit/ProdutoPrecoCartaoTest.php` | Novo — unit tests do accessor |
| `tests/Feature/MercadoPagoCheckoutTest.php` | Novos casos para markup de cartão |

---

## Task 1: Accessor `preco_cartao` no Produto model

**Files:**
- Modify: `app/Models/Produto.php` (após `getDescontoPix()`, linha ~189)
- Create: `tests/Unit/ProdutoPrecoCartaoTest.php`

- [ ] **Step 1.1: Escrever o teste unitário**

```php
<?php
// tests/Unit/ProdutoPrecoCartaoTest.php
namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProdutoPrecoCartaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_preco_cartao_adds_five_percent_to_preco_com_desconto(): void
    {
        $produto = Produto::factory()->create([
            'preco' => 100.00,
            'em_promocao' => false,
            'desconto_pix' => 5.0,
        ]);

        $this->assertEquals(105.00, $produto->preco_cartao);
    }

    public function test_preco_cartao_uses_preco_com_desconto_as_base_when_em_promocao(): void
    {
        // preco_com_desconto = preco_original * (1 - desconto_percentual/100) = 100 * 0.80 = 80
        // preco_cartao = 80 * 1.05 = 84.00
        $produto = Produto::factory()->create([
            'preco' => 80.00,
            'preco_original' => 100.00,
            'desconto_percentual' => 20.0,
            'em_promocao' => true,
            'desconto_pix' => 5.0,
        ]);

        $this->assertEquals(84.00, $produto->preco_cartao);
    }

    public function test_preco_cartao_uses_configured_desconto_pix_when_field_is_null(): void
    {
        // desconto_pix = null → getDescontoPix() returns 5.0 (default)
        $produto = Produto::factory()->create([
            'preco' => 200.00,
            'em_promocao' => false,
            'desconto_pix' => null,
        ]);

        $this->assertEquals(210.00, $produto->preco_cartao);
    }
}
```

- [ ] **Step 1.2: Rodar o teste para confirmar falha**

```bash
docker exec laravel-app php artisan test --filter=ProdutoPrecoCartaoTest
```

Esperado: `Error: Call to undefined method ... preco_cartao` (ou similar)

- [ ] **Step 1.3: Adicionar o accessor ao model**

Em `app/Models/Produto.php`, após o método `getDescontoPix()` (linha ~189), adicionar:

```php
public function getPrecoCartaoAttribute(): float
{
    return round($this->preco_com_desconto * (1 + $this->getDescontoPix() / 100), 2);
}
```

- [ ] **Step 1.4: Rodar os testes para confirmar aprovação**

```bash
docker exec laravel-app php artisan test --filter=ProdutoPrecoCartaoTest
```

Esperado: 3 testes PASS

- [ ] **Step 1.5: Garantir que os testes existentes continuam passando**

```bash
docker exec laravel-app php artisan test --filter=ProdutoVarianteTest
```

Esperado: todos PASS

- [ ] **Step 1.6: Commit**

```bash
git add app/Models/Produto.php tests/Unit/ProdutoPrecoCartaoTest.php
git commit -m "feat: accessor preco_cartao no Produto (PIX + 5%)"
```

---

## Task 2: Product card — exibir preço PIX e parcelas de cartão

**Files:**
- Modify: `resources/views/components/product-card.blade.php` (linhas 103-112)

- [ ] **Step 2.1: Substituir o bloco `@php` e os valores exibidos**

Localizar o bloco:
```blade
                @php
                    $fatorPix = 1 - ($produto->getDescontoPix() / 100);
                    $precoPix = $produto->preco_com_desconto * $fatorPix;
                    $valorParcela = $produto->preco_com_desconto / 10;
                @endphp
                <div class="flex items-center gap-1.5 mb-0.5">
                    <span class="font-mono font-bold text-lg whitespace-nowrap">R$ {{ number_format($precoPix, 2, ',', '.') }}</span>
                    <span class="text-[10px] font-mono font-bold bg-black text-white px-1.5 py-0.5 uppercase tracking-widest leading-none">PIX</span>
                </div>
                <span class="text-xs font-mono text-gray-400 whitespace-nowrap">10x R$ {{ number_format($valorParcela, 2, ',', '.') }}</span>
```

Substituir por:
```blade
                @php
                    $precoPix = $produto->preco_com_desconto;
                    $valorParcela = $produto->preco_cartao / 10;
                @endphp
                <div class="flex items-center gap-1.5 mb-0.5">
                    <span class="font-mono font-bold text-lg whitespace-nowrap">R$ {{ number_format($precoPix, 2, ',', '.') }}</span>
                    <span class="text-[10px] font-mono font-bold bg-black text-white px-1.5 py-0.5 uppercase tracking-widest leading-none">PIX</span>
                </div>
                <span class="text-xs font-mono text-gray-400 whitespace-nowrap">10x R$ {{ number_format($valorParcela, 2, ',', '.') }} no cartão</span>
```

- [ ] **Step 2.2: Limpar cache de views e verificar visualmente**

```bash
docker exec laravel-app php artisan view:clear
```

Abrir a home ou listagem de produtos no browser. Verificar:
- Badge PIX mostra o preço do banco diretamente (sem desconto de 5%)
- Parcelas mostram o preço do banco × 1.05 ÷ 10

- [ ] **Step 2.3: Commit**

```bash
git add resources/views/components/product-card.blade.php
git commit -m "feat: product-card exibe PIX=preço DB, parcelas=cartão+5%"
```

---

## Task 3: Página de detalhe do produto — PIX e parcelas de cartão

**Files:**
- Modify: `resources/views/site/produto-detalhes.blade.php` (linhas 119-131)

- [ ] **Step 3.1: Substituir o bloco de preços**

Localizar:
```blade
                        @php
                            $descontoPix = $produto->getDescontoPix();
                            $precoPix = $produto->preco_com_desconto * (1 - $descontoPix / 100);
                            $valorParcela = $produto->preco_com_desconto / 10;
                        @endphp
                        <div class="flex items-baseline gap-2 mb-1">
                            <span class="text-3xl font-mono font-bold" id="price-pix" data-desconto-pix="{{ $descontoPix }}">R$ {{ number_format($precoPix, 2, ',', '.') }}</span>
                            <span class="text-xs font-mono font-bold bg-black text-white px-2 py-0.5 uppercase tracking-widest">PIX</span>
                            @if($produto->em_promocao && $produto->desconto_percentual > 0)
                                <span class="text-sm font-mono text-red-600">(-{{ number_format($produto->desconto_percentual, 0) }}%)</span>
                            @endif
                        </div>
                        <span class="text-sm font-mono text-gray-500" id="price-installment">ou 10x de R$ {{ number_format($valorParcela, 2, ',', '.') }} sem juros</span>
```

Substituir por:
```blade
                        @php
                            $descontoPix = $produto->getDescontoPix();
                            $precoPix = $produto->preco_com_desconto;
                            $valorParcela = $produto->preco_cartao / 10;
                        @endphp
                        <div class="flex items-baseline gap-2 mb-1">
                            <span class="text-3xl font-mono font-bold" id="price-pix" data-desconto-pix="{{ $descontoPix }}">R$ {{ number_format($precoPix, 2, ',', '.') }}</span>
                            <span class="text-xs font-mono font-bold bg-black text-white px-2 py-0.5 uppercase tracking-widest">PIX</span>
                            @if($produto->em_promocao && $produto->desconto_percentual > 0)
                                <span class="text-sm font-mono text-red-600">(-{{ number_format($produto->desconto_percentual, 0) }}%)</span>
                            @endif
                        </div>
                        <span class="text-sm font-mono text-gray-500" id="price-installment">ou 10x de R$ {{ number_format($valorParcela, 2, ',', '.') }} no cartão</span>
```

- [ ] **Step 3.2: Limpar cache e verificar visualmente**

```bash
docker exec laravel-app php artisan view:clear
```

Abrir uma página de produto. Verificar:
- Preço PIX = preço do banco (sem subtrair 5%)
- Parcelas = preço do banco × 1.05 ÷ 10

- [ ] **Step 3.3: Commit**

```bash
git add resources/views/site/produto-detalhes.blade.php
git commit -m "feat: detalhe de produto exibe PIX=preço DB, parcelas=cartão+5%"
```

---

## Task 4: JS de variantes — atualizar preços ao selecionar variante

**Files:**
- Modify: `public/js/produto-detalhes.js` (linhas 674-683)

Atenção: este é um arquivo estático em `public/js/`, não processado pelo Vite. Editar diretamente.

- [ ] **Step 4.1: Substituir o bloco de atualização de preço no handler de variante**

Localizar (linhas ~674-683):
```js
                // Update price display
                priceEl = document.getElementById('price-pix');
                if (priceEl) {
                    var descontoPix = parseFloat(priceEl.dataset.descontoPix) || 5;
                    var pixPreco = varianteEncontrada.preco_efetivo * (1 - descontoPix / 100);
                    priceEl.textContent = 'R$ ' + pixPreco.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
                installmentEl = document.getElementById('price-installment');
                if (installmentEl) {
                    var parcelaValor = varianteEncontrada.preco_efetivo / 10;
                    installmentEl.textContent = 'ou 10x de R$ ' + parcelaValor.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' sem juros';
                }
```

Substituir por:
```js
                // Update price display
                priceEl = document.getElementById('price-pix');
                if (priceEl) {
                    var descontoPix = parseFloat(priceEl.dataset.descontoPix) || 5;
                    var pixPreco = varianteEncontrada.preco_efetivo;
                    priceEl.textContent = 'R$ ' + pixPreco.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
                installmentEl = document.getElementById('price-installment');
                if (installmentEl) {
                    var descontoPix = parseFloat(priceEl ? priceEl.dataset.descontoPix : '5') || 5;
                    var cardPreco = varianteEncontrada.preco_efetivo * (1 + descontoPix / 100);
                    var parcelaValor = cardPreco / 10;
                    installmentEl.textContent = 'ou 10x de R$ ' + parcelaValor.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' no cartão';
                }
```

- [ ] **Step 4.2: Verificar manualmente no browser**

Abrir uma página de produto que tenha variantes. Selecionar cada variante e confirmar:
- Preço PIX atualiza para `preco_efetivo` da variante (sem subtrair 5%)
- Parcelas atualizam para `preco_efetivo × 1.05 ÷ 10`

- [ ] **Step 4.3: Commit**

```bash
git add public/js/produto-detalhes.js
git commit -m "feat: variantes atualizam PIX=preco_efetivo, parcelas=cartão+5%"
```

---

## Task 5: Checkout — toggle PIX/Cartão e total dinâmico

**Files:**
- Modify: `resources/views/site/checkout.blade.php`

São duas sub-partes: HTML (toggle) e JS (lógica).

### 5A — PHP helper e HTML do toggle

- [ ] **Step 5A.1: Adicionar variável PHP `$markupCartao` no início da view**

Localizar a linha (linha ~16):
```blade
    @php($subtotalProdutos = $carrinho->itens->sum(fn ($item) => $item->preco * $item->quantidade))
```

Substituir por:
```blade
    @php
        $subtotalProdutos = $carrinho->itens->sum(fn ($item) => $item->preco * $item->quantidade);
        $markupCartao = (float) \App\Models\Configuracao::get('desconto_pix_global', 5.0) / 100;
    @endphp
```

- [ ] **Step 5A.2: Inserir o toggle de método de pagamento antes do botão "Continuar"**

Localizar (linhas ~312-317):
```blade
                        </div>

                        <!-- Continue Button -->
                        <button id="continue-btn" class="w-full mt-6 bg-black text-white font-bold py-3 px-6 tracking-widest uppercase text-sm hover:bg-gray-900 transition-colors">
                            Continuar para Pagamento
                        </button>
```

Substituir por:
```blade
                        </div>

                        <!-- Método de Pagamento -->
                        <div class="mt-4">
                            <p class="text-xs font-mono text-gray-500 uppercase tracking-widest mb-2">Pagar com:</p>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" id="metodo-pix-btn"
                                    class="metodo-btn border-2 border-black bg-black text-white p-3 font-mono transition-colors text-left"
                                    data-metodo="pix">
                                    <div class="text-xs font-bold uppercase tracking-widest">PIX</div>
                                    <div class="text-[10px] text-white/70 mt-0.5">5% de desconto</div>
                                </button>
                                <button type="button" id="metodo-card-btn"
                                    class="metodo-btn border-2 border-[var(--color-lab-border)] bg-white text-black p-3 font-mono transition-colors text-left hover:border-black"
                                    data-metodo="card">
                                    <div class="text-xs font-bold uppercase tracking-widest">Cartão</div>
                                    <div class="text-[10px] text-gray-500 mt-0.5">Preço cheio</div>
                                </button>
                            </div>
                        </div>

                        <!-- Continue Button -->
                        <button id="continue-btn" class="w-full mt-6 bg-black text-white font-bold py-3 px-6 tracking-widest uppercase text-sm hover:bg-gray-900 transition-colors">
                            Continuar para Pagamento
                        </button>
```

### 5B — JavaScript do checkout

- [ ] **Step 5B.1: Adicionar variável `metodoPagamento` e constante `MARKUP_CARTAO`**

Localizar no bloco `<script>` (linhas ~471-473):
```js
    // --- Cupom de desconto ---
    let descontoAtual = cupomAplicado.desconto || 0;
    let freteAtual = (typeof savedFreteValue === 'number' && savedFreteValue > 0) ? savedFreteValue : 0;
```

Substituir por:
```js
    // --- Cupom de desconto ---
    let descontoAtual = cupomAplicado.desconto || 0;
    let freteAtual = (typeof savedFreteValue === 'number' && savedFreteValue > 0) ? savedFreteValue : 0;
    let metodoPagamento = 'pix';
    const MARKUP_CARTAO = {{ $markupCartao }};
```

- [ ] **Step 5B.2: Atualizar `atualizarTotalComDesconto` para considerar o método**

Localizar (linhas ~487-492):
```js
    function atualizarTotalComDesconto(frete) {
        const fv = (typeof frete === 'number') ? frete : freteAtual;
        const subtotal = parseFloat('{{ $subtotalProdutos }}');
        const total = Math.max(0, subtotal - descontoAtual + fv);
        $('#total-valor').text(formatarBRL(total));
    }
```

Substituir por:
```js
    function atualizarTotalComDesconto(frete) {
        const fv = (typeof frete === 'number') ? frete : freteAtual;
        const subtotal = parseFloat('{{ $subtotalProdutos }}');
        const subtotalLiquido = Math.max(0, subtotal - descontoAtual);
        const markupFactor = metodoPagamento === 'card' ? (1 + MARKUP_CARTAO) : 1.0;
        const total = subtotalLiquido * markupFactor + fv;
        $('#total-valor').text(formatarBRL(total));
    }
```

- [ ] **Step 5B.3: Adicionar handler do toggle de método**

Localizar (linhas ~514-515):
```js
    $(document).ready(function () {
        // Restaurar cupom se já estava aplicado (ex: usuário voltou à página)
```

Inserir após `$(document).ready(function () {`:
```js
        // Toggle PIX / Cartão
        document.querySelectorAll('.metodo-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                metodoPagamento = btn.dataset.metodo;
                document.querySelectorAll('.metodo-btn').forEach(function(b) {
                    b.classList.remove('bg-black', 'text-white', 'border-black');
                    b.classList.add('bg-white', 'text-black', 'border-[var(--color-lab-border)]');
                    b.querySelector('div:last-child').style.color = '';
                });
                btn.classList.remove('bg-white', 'text-black', 'border-[var(--color-lab-border)]');
                btn.classList.add('bg-black', 'text-white', 'border-black');
                atualizarTotalComDesconto();
            });
        });

```

- [ ] **Step 5B.4: Passar `payment_method_category` no payload de `createOrder`**

Localizar (linhas ~653-654):
```js
        dadosEndereco.payer_document = payerDocument;
```

Substituir por:
```js
        dadosEndereco.payer_document = payerDocument;
        dadosEndereco.payment_method_category = metodoPagamento;
```

- [ ] **Step 5B.5: Limpar cache, compilar assets e verificar no browser**

```bash
docker exec laravel-app php artisan view:clear && docker exec laravel-app php artisan cache:clear
cd /var/www/html && npm run build
```

Abrir `/checkout` e verificar:
1. Dois botões PIX / Cartão aparecem abaixo do total
2. PIX selecionado por padrão (fundo preto)
3. Clicar em Cartão → total aumenta ~5%
4. Clicar em PIX → total volta ao valor original

- [ ] **Step 5B.6: Commit**

```bash
git add resources/views/site/checkout.blade.php
git commit -m "feat: checkout com toggle PIX/Cartão e total dinâmico"
```

---

## Task 6: Controller `prepare()` — aplicar markup de cartão no `valor_total`

**Files:**
- Modify: `app/Http/Controllers/MercadoPagoCheckoutController.php`
- Modify: `tests/Feature/MercadoPagoCheckoutTest.php`

### 6A — Escrever os testes primeiro

- [ ] **Step 6A.1: Adicionar testes de markup no `MercadoPagoCheckoutTest.php`**

Abrir `tests/Feature/MercadoPagoCheckoutTest.php` e adicionar dois novos métodos após `test_prepare_accepts_retirada_with_zero_shipping`:

```php
    public function test_prepare_applies_card_markup_when_payment_method_category_is_card(): void
    {
        $user = User::factory()->create(['phone' => '(43) 99999-9999']);
        $pedido = $this->makeCart($user, 100.00, 2); // subtotal = 200

        $response = $this->actingAs($user)->postJson(route('site.checkout.mercadopago.prepare'), [
            'customer_name' => 'Cliente Teste',
            'customer_email' => 'cliente@example.com',
            'customer_phone' => '(43) 99999-9999',
            'cep' => '86010-000',
            'rua' => 'Rua Teste',
            'numero' => '123',
            'bairro' => 'Centro',
            'cidade' => 'Londrina',
            'estado' => 'PR',
            'pais' => 'BR',
            'frete_tipo' => 'pac',
            'payment_method_category' => 'card',
        ]);

        // subtotal = 200, markup 5% = 210, frete PAC = 18 → total = 228
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('checkout.amount', 228.0);

        $pedido->refresh();
        $this->assertEquals(228.00, (float) $pedido->valor_total);
    }

    public function test_prepare_does_not_apply_markup_when_payment_method_category_is_pix(): void
    {
        $user = User::factory()->create(['phone' => '(43) 99999-9999']);
        $pedido = $this->makeCart($user, 100.00, 2); // subtotal = 200

        $response = $this->actingAs($user)->postJson(route('site.checkout.mercadopago.prepare'), [
            'customer_name' => 'Cliente Teste',
            'customer_email' => 'cliente@example.com',
            'customer_phone' => '(43) 99999-9999',
            'cep' => '86010-000',
            'rua' => 'Rua Teste',
            'numero' => '123',
            'bairro' => 'Centro',
            'cidade' => 'Londrina',
            'estado' => 'PR',
            'pais' => 'BR',
            'frete_tipo' => 'pac',
            'payment_method_category' => 'pix',
        ]);

        // subtotal = 200, sem markup, frete PAC = 18 → total = 218
        $response->assertOk()
            ->assertJsonPath('checkout.amount', 218.0);

        $pedido->refresh();
        $this->assertEquals(218.00, (float) $pedido->valor_total);
    }
```

- [ ] **Step 6A.2: Rodar os novos testes para confirmar falha**

```bash
docker exec laravel-app php artisan test --filter="test_prepare_applies_card_markup|test_prepare_does_not_apply_markup"
```

Esperado: FAIL — os dois testes falham porque o controller ainda não aplica o markup.

### 6B — Implementar no controller

- [ ] **Step 6B.1: Adicionar import de `Configuracao`**

Em `app/Http/Controllers/MercadoPagoCheckoutController.php`, localizar o bloco de imports (linhas 5-19) e adicionar após `use App\Models\Pedido;`:

```php
use App\Models\Configuracao;
```

- [ ] **Step 6B.2: Adicionar `payment_method_category` à validação do `prepare()`**

Localizar no método `prepare()` (linha ~50):
```php
            'frete_tipo' => 'required|in:pac,sedex,retirada,gratis',
```

Substituir por:
```php
            'frete_tipo' => 'required|in:pac,sedex,retirada,gratis',
            'payment_method_category' => 'nullable|in:pix,card',
```

- [ ] **Step 6B.3: Substituir o cálculo de `$valorTotal`**

Localizar (linhas ~82-84):
```php
        $subtotal = $pedido->itens->sum(fn ($item) => (float) $item->preco * (int) $item->quantidade);
        $desconto = (float) ($pedido->valor_desconto ?? 0);
        $valorTotal = round(max(0, $subtotal - $desconto) + (float) $frete['valor'], 2);
```

Substituir por:
```php
        $subtotal = $pedido->itens->sum(fn ($item) => (float) $item->preco * (int) $item->quantidade);
        $desconto = (float) ($pedido->valor_desconto ?? 0);
        $isCard = ($validated['payment_method_category'] ?? 'pix') === 'card';
        $descontoPix = (float) Configuracao::get('desconto_pix_global', 5.0);
        $markupFactor = $isCard ? (1 + $descontoPix / 100) : 1.0;
        $subtotalLiquido = max(0, $subtotal - $desconto);
        $valorTotal = round($subtotalLiquido * $markupFactor + (float) $frete['valor'], 2);
```

- [ ] **Step 6B.4: Atualizar `subtotal` no payload de resposta**

Localizar (linha ~112):
```php
                'subtotal' => round($subtotal, 2),
```

Substituir por:
```php
                'subtotal' => round($subtotalLiquido * $markupFactor, 2),
```

- [ ] **Step 6B.5: Rodar os novos testes**

```bash
docker exec laravel-app php artisan test --filter="test_prepare_applies_card_markup|test_prepare_does_not_apply_markup"
```

Esperado: 2 testes PASS

- [ ] **Step 6B.6: Rodar toda a suite para garantir regressão zero**

```bash
docker exec laravel-app php artisan test
```

Esperado: todos os testes PASS (pode haver ajustes se algum teste existente verificava o `checkout.subtotal`)

Se o teste `test_prepare_updates_order_and_returns_checkout_payload` falhar com subtotal errado, é porque ele agora recebe `subtotalLiquido * 1.0` (sem markup, pois `payment_method_category` não foi enviado). O subtotal deve permanecer 200 e o amount 218. Confirme se o teste passa sem alteração.

- [ ] **Step 6B.7: Commit final**

```bash
git add app/Http/Controllers/MercadoPagoCheckoutController.php tests/Feature/MercadoPagoCheckoutTest.php
git commit -m "feat: preparar aplica markup +5% no valor_total quando payment_method_category=card"
```

---

## Verificação Final

- [ ] Rodar suite completa:

```bash
docker exec laravel-app php artisan test
```

Esperado: todos PASS.

- [ ] Limpar todos os caches:

```bash
docker exec laravel-app php artisan view:clear && docker exec laravel-app php artisan cache:clear
```

- [ ] Testar o fluxo completo no browser:
  1. Abrir listagem de produtos → verificar preço PIX e parcelas de cartão
  2. Abrir detalhe de produto → verificar preços; se tiver variante, trocar e confirmar atualização
  3. Adicionar produto ao carrinho → ir ao checkout
  4. Selecionar PIX → ver total
  5. Selecionar Cartão → confirmar total ~5% maior
  6. Clicar Continuar → confirmar que o MP Brick abre com o valor correto
