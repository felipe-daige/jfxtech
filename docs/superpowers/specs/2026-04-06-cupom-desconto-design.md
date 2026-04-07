# Sistema de Cupons de Desconto — Design Spec

**Data:** 2026-04-06  
**Status:** Aprovado

## Contexto

O checkout (`/finalizar-compra`) não possui campo para inserção de cupons de desconto. O sistema precisa de:
- Campo de cupom no checkout com validação em tempo real (AJAX)
- Painel admin para criar e gerenciar cupons
- Desconto aplicado antes do pagamento via MercadoPago

## Escopo

- Tipos de cupom: percentual (%) e valor fixo (R$)
- Limite de 1 uso por usuário autenticado
- Painel admin com CRUD completo
- Sem suporte a frete grátis por ora

---

## Banco de Dados

### Nova tabela `cupons`

| Campo | Tipo | Descrição |
|---|---|---|
| `id` | bigint PK auto-increment | |
| `codigo` | varchar(50) unique | Código digitado pelo usuário (ex: `PROMO10`) — armazenado em maiúsculas |
| `tipo` | enum `percentual\|fixo` | Tipo de desconto |
| `valor` | decimal(10,2) | % ou R$ conforme tipo |
| `valor_minimo_pedido` | decimal(10,2) nullable | Valor mínimo do subtotal para ativar o cupom |
| `limite_usos` | int unsigned nullable | Null = usos ilimitados |
| `usos_realizados` | int unsigned default 0 | Incrementado apenas após pagamento confirmado |
| `valido_ate` | date nullable | Null = sem expiração |
| `ativo` | boolean default true | |
| `created_at`, `updated_at` | timestamps | |

### Nova tabela `cupom_usos`

Rastreia qual usuário usou qual cupom em qual pedido (evita uso duplicado).

| Campo | Tipo |
|---|---|
| `id` | bigint PK |
| `cupom_id` | FK → cupons (cascade delete) |
| `user_id` | int unsigned nullable FK → users |
| `pedido_id` | FK → pedidos |
| `created_at` | timestamp |

### Alterações na tabela `pedidos`

Novos campos (migration `alter_pedidos_add_cupom_fields`):

| Campo | Tipo | Descrição |
|---|---|---|
| `cupom_codigo` | varchar(50) nullable | Snapshot do código aplicado (não FK — evita problema se cupom for deletado depois) |
| `valor_desconto` | decimal(10,2) default 0 | Valor descontado; nunca ultrapassa o subtotal |

---

## Backend

### Model `Cupom` (`app/Models/Cupom.php`)

- Fillable: todos os campos acima
- Cast: `ativo` → boolean, `valido_ate` → date
- Mutator: `setCodigo` → sempre `strtoupper()`
- Scope: `ativo()` — filtra `ativo = true` e `valido_ate >= today OR valido_ate IS NULL`
- Método `calcularDesconto(float $subtotal): float`:
  - `percentual`: `round($subtotal * $this->valor / 100, 2)`
  - `fixo`: `min($this->valor, $subtotal)`

### Controller `CupomController` (`app/Http/Controllers/CupomController.php`)

**`aplicar(Request $request)`**
1. Valida: `codigo` obrigatório string max 50
2. Resolve carrinho ativo via `CheckoutOrderService::resolveActiveOrder()`
3. Busca cupom: `Cupom::whereRaw('UPPER(codigo) = ?', [strtoupper($codigo)])->first()` — 422 se não encontrado
4. Verifica `ativo` e data de expiração — 422 com mensagem específica
5. Verifica `usos_realizados < limite_usos` — 422 se esgotado
6. Se usuário autenticado: verifica `CupomUso::where(['cupom_id' => $cupom->id, 'user_id' => auth()->id()])->exists()` — 422 se já usou
7. Verifica `valor_minimo_pedido`: subtotal do carrinho deve ser `>=` — 422 com mensagem informando o mínimo
8. Calcula desconto via `$cupom->calcularDesconto($subtotal)`
9. Atualiza pedido: `$carrinho->update(['cupom_codigo' => $cupom->codigo, 'valor_desconto' => $desconto])`
10. Retorna JSON: `{ success: true, desconto: "12,00", novo_total: "126,00", mensagem: "Cupom PROMO10 aplicado — 10% de desconto" }`

**`remover(Request $request)`**
1. Resolve carrinho ativo
2. `$carrinho->update(['cupom_codigo' => null, 'valor_desconto' => 0])`
3. Retorna JSON com novo total (subtotal + frete)

### Rotas públicas (`routes/web.php`)

```php
Route::post('/cupom/aplicar', [CupomController::class, 'aplicar'])->name('cupom.aplicar');
Route::post('/cupom/remover', [CupomController::class, 'remover'])->name('cupom.remover');
```

Não exige autenticação (suporte a guest checkout). Validação de "já usou" só se aplica a usuários autenticados.

### Atualização em `MercadoPagoCheckoutController::prepare()`

```php
$subtotal = $pedido->itens->sum(fn ($item) => (float) $item->preco * (int) $item->quantidade);
$valorTotal = round($subtotal - (float) $pedido->valor_desconto + (float) $frete['valor'], 2);
```

Após pagamento confirmado: dentro de `MercadoPagoCheckoutController::webhook()`, quando o status do pagamento mudar para `approved`/`pago`, verificar se `pedido->cupom_codigo` está preenchido e então:
- `Cupom::where('codigo', $pedido->cupom_codigo)->increment('usos_realizados')`
- `CupomUso::create(['cupom_id' => $cupom->id, 'user_id' => $pedido->user_id, 'pedido_id' => $pedido->id])`

### `CarrinhoController::recalcularValorTotal()`

Atualizar para incluir o desconto:
```php
$subtotal = $carrinho->itens()->sum(DB::raw('quantidade * preco'));
$carrinho->update(['valor_total' => max(0, $subtotal - $carrinho->valor_desconto)]);
```

---

## Frontend — `finalizar-compra.blade.php`

### Posição do campo de cupom

No resumo do pedido (sidebar), entre os itens e o subtotal:

```
[ Código do cupom ............. ] [ APLICAR ]
```

Após aplicar com sucesso:
```
✓ PROMO10 — 10% de desconto   [remover]
```

Linha de desconto aparece no resumo:
```
Subtotal           R$ 120,00
Desconto          -R$  12,00
Frete              R$  18,00
─────────────────────────────
Total             R$ 126,00
```

### Comportamento JS (`public/js/checkout-mercadopago.js` ou inline)

- `aplicarCupom()`: POST `/cupom/aplicar` com `{ codigo }`, atualiza DOM com desconto e novo total, mostra badge com código + botão remover
- `removerCupom()`: POST `/cupom/remover`, remove linha de desconto, atualiza total
- Feedback de erro inline abaixo do input (mensagem do servidor)
- Ao trocar frete: recalcular total considerando `valor_desconto` já aplicado

---

## Admin — Painel de Cupons

### Rotas admin (`routes/web.php`, grupo `/admin`)

```php
Route::get('/admin/cupons',              [AdminCupomController::class, 'index'])->name('admin.cupons.index');
Route::post('/admin/cupons',             [AdminCupomController::class, 'store'])->name('admin.cupons.store');
Route::put('/admin/cupons/{id}',         [AdminCupomController::class, 'update'])->name('admin.cupons.update');
Route::delete('/admin/cupons/{id}',      [AdminCupomController::class, 'destroy'])->name('admin.cupons.destroy');
Route::post('/admin/cupons/{id}/toggle', [AdminCupomController::class, 'toggle'])->name('admin.cupons.toggle');
```

### Controller `AdminCupomController`

CRUD simples retornando JSON para chamadas AJAX (mesmo padrão dos outros controllers admin).

### View `resources/views/admin/cupons.blade.php`

Tabela com colunas: Código | Tipo | Valor | Mínimo | Usos | Validade | Status | Ações

Ações: editar (modal) | ativar/desativar (toggle) | deletar (modal de confirmação)

Link de navegação adicionado ao menu lateral do admin.

---

## Verificação / Testes

1. Criar cupom via admin (percentual 10%, mínimo R$50)
2. Adicionar produto > R$50 ao carrinho
3. Ir para `/finalizar-compra`, digitar código, clicar Aplicar
4. Verificar linha de desconto aparece, total atualiza corretamente
5. Tentar usar o mesmo cupom novamente → erro "você já usou este cupom"
6. Testar cupom expirado → erro com mensagem
7. Testar cupom com `limite_usos` atingido → erro
8. Remover cupom → linha desaparece, total volta ao original
9. Completar pagamento → `usos_realizados` incrementa, `cupom_usos` tem registro
10. Testar valor mínimo não atingido → erro com valor mínimo informado
