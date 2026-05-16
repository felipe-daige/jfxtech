# Design: Sistema de Preços PIX vs. Cartão

**Data:** 2026-05-16  
**Status:** Aprovado

---

## Problema

O sistema atual trata `produto->preco` (DB) como preço de cartão e exibe o PIX com 5% de desconto sobre ele. O dono da loja quer inverter: o preço salvo no banco **é** o preço PIX, e o cartão cobra 5% a mais.

---

## Decisões

- **Sem migração de banco.** `produtos.preco` passa a ser semanticamente o preço PIX. Nenhum dado é alterado.
- **Fórmula do cartão:** `preco_com_desconto × (1 + descontoPix/100)` — atualmente 5%, configurável por produto ou globalmente via `Configuracao::get('desconto_pix_global')`.
- **Cobrança real:** o checkout cobra o preço do método selecionado. Cartão paga 5% a mais que PIX de verdade.
- **Frete não sofre markup** — o +5% incide só no subtotal de produtos.
- **Desconto de cupom** aplica-se ao subtotal base (preço PIX), depois o markup de cartão incide sobre o resultado líquido.

---

## Arquitetura

### Model (`app/Models/Produto.php`)

Novo accessor:

```php
public function getPrecoCartaoAttribute(): float
{
    return round($this->preco_com_desconto * (1 + $this->getDescontoPix() / 100), 2);
}
```

`preco_com_desconto` já existe e aplica desconto promocional se `em_promocao = true`. O accessor `preco_cartao` constrói sobre ele.

### Product card (`resources/views/components/product-card.blade.php`)

| Antes | Depois |
|---|---|
| `$precoPix = preco_com_desconto × 0.95` | `$precoPix = preco_com_desconto` |
| `$valorParcela = preco_com_desconto / 10` | `$valorParcela = preco_cartao / 10` |

O badge PIX permanece; a linha de parcelas passa a refletir o preço de cartão.

### Página de produto (`resources/views/site/produto-detalhes.blade.php`)

- Preço PIX: `$produto->preco_com_desconto` (sem fator)
- Preço cartão: `$produto->preco_cartao` (exibido junto, com label "Cartão/Parcelado")
- Atributo `data-desconto-pix` mantido para o JS de variantes

### JS de variantes (`public/js/produto-detalhes.js`)

Quando o usuário seleciona uma variante:

```js
const pixPreco = varianteEncontrada.preco_efetivo;
const cardPreco = pixPreco * (1 + descontoPix / 100);
```

Atualiza ambos os elementos de preço na página.

### Página de checkout (`resources/views/site/checkout.blade.php`)

Adiciona toggle **PIX / Cartão** antes do botão "Continuar":

- PIX selecionado: total = `subtotal − desconto + frete`
- Cartão selecionado: total = `(subtotal − desconto) × 1.05 + frete`
- `atualizarTotalComDesconto()` recebe o método atual
- `createOrder()` inclui `payment_method_category: 'pix' | 'card'` no payload de `preparar`

### Controller (`app/Http/Controllers/MercadoPagoCheckoutController@preparar`)

```php
$isCard = in_array($validated['payment_method_category'] ?? 'pix', ['card', 'credit', 'debit']);
$markupFactor = $isCard ? (1 + $descontoPix / 100) : 1.0;
$subtotalComMarkup = round(($subtotal - $desconto) * $markupFactor, 2);
$valorTotal = round($subtotalComMarkup + $frete['valor'], 2);
```

O `valor_total` gravado no pedido reflete o método escolhido. O MP Brick é inicializado com esse valor.

---

## Escopo fora deste spec

- Restricão do Brick ao método pré-selecionado (futuro)
- Exibição do método escolhido no detalhe do pedido admin (futuro)
- Emails transacionais com destaque para o método (futuro)
