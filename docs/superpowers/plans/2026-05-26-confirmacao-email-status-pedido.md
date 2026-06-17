# Confirmação de E-mail ao Alterar Status do Pedido — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Adicionar checkbox "Enviar e-mail ao cliente" no modal de alterar status do admin; mover email para fora do Observer e chamá-lo explicitamente em cada ponto que atualiza status.

**Architecture:** `PedidoObserver` para de chamar `OrderEmailNotificationService`. O e-mail é chamado explicitamente em `MercadoPagoCheckoutController`, `PedidoController` (confirmar entrega + cancelar) e `AdminController::quickStatusPedido` (sempre), e em `AdminController::atualizarStatusPedido` somente se `send_email=1`. UI: checkbox com toggle JS no `#modalStatus`.

**Tech Stack:** Laravel 12, PHP 8.3, Blade, Vanilla JS, PHPUnit

---

## File Map

| Ação | Arquivo |
|---|---|
| Modificar | `app/Observers/PedidoObserver.php` — remover chamada de email |
| Modificar | `app/Http/Controllers/MercadoPagoCheckoutController.php` — email explícito |
| Modificar | `app/Http/Controllers/PedidoController.php` — email explícito |
| Modificar | `app/Http/Controllers/AdminController.php` — flag `send_email` + quick-status |
| Modificar | `resources/views/admin/pedidos.blade.php` — checkbox HTML |
| Modificar | `public/js/admin.js` — toggle JS do checkbox |
| Modificar | `tests/Feature/MercadoPagoCheckoutTest.php` — verificar que testes passam |

---

### Task 1: Remover e-mail do PedidoObserver

**Files:**
- Modify: `app/Observers/PedidoObserver.php`

- [ ] **Step 1: Verificar o estado atual do observer**

```bash
cat -n /var/www/html/app/Observers/PedidoObserver.php
```

- [ ] **Step 2: Remover a linha de email e o import desnecessário**

Substituir o conteúdo de `app/Observers/PedidoObserver.php` por:

```php
<?php

namespace App\Observers;

use App\Enums\PedidoStatus;
use App\Jobs\CartAbandonedNotificationJob;
use App\Models\Pedido;
use App\Services\OrderStatusNotificationService;

class PedidoObserver
{
    public function updated(Pedido $pedido): void
    {
        if (!$pedido->wasChanged('status')) {
            return;
        }

        if ($pedido->status === PedidoStatus::PENDENTE) {
            CartAbandonedNotificationJob::dispatch($pedido->id)
                ->delay(now()->addMinutes(30));
            return;
        }

        if (!in_array($pedido->status, PedidoStatus::notificationValues(), true)) {
            return;
        }

        if (in_array($pedido->status, [PedidoStatus::PAGO, PedidoStatus::PROCESSANDO, PedidoStatus::ENVIADO, PedidoStatus::ENTREGUE], true)) {
            app(\App\Services\GuestToUserConversionService::class)->convert($pedido);
        }

        app(OrderStatusNotificationService::class)->send($pedido);
    }
}
```

- [ ] **Step 3: Rodar os testes para ver o que quebrou**

```bash
docker exec laravel-app php artisan test 2>&1 | tail -30
```

Esperado: alguns testes de email devem falhar — isso é intencional, vamos corrigir nas próximas tasks.

- [ ] **Step 4: Commit**

```bash
git add app/Observers/PedidoObserver.php
git commit -m "refactor: remover email do PedidoObserver — será chamado explicitamente"
```

---

### Task 2: Adicionar email ao MercadoPagoCheckoutController

**Files:**
- Modify: `app/Http/Controllers/MercadoPagoCheckoutController.php`

- [ ] **Step 1: Adicionar injeção de OrderEmailNotificationService no construtor**

Localizar (linhas 25-28):
```php
public function __construct(
    protected MercadoPagoService $mercadoPagoService,
    protected CheckoutOrderService $checkoutOrderService,
) {}
```

Substituir por:
```php
public function __construct(
    protected MercadoPagoService $mercadoPagoService,
    protected CheckoutOrderService $checkoutOrderService,
    protected \App\Services\OrderEmailNotificationService $orderEmailService,
) {}
```

- [ ] **Step 2: Adicionar chamada de email após atualização de status**

Localizar (linha ~434):
```php
$newStatus = $this->mapOrderStatus($gatewayResponse['status'] ?? null);
$pedido->update(['status' => $newStatus]);

if ($newStatus === PedidoStatus::PAGO) {
    $this->recordCouponUse($pedido);
}
```

Substituir por:
```php
$newStatus = $this->mapOrderStatus($gatewayResponse['status'] ?? null);
$pedido->update(['status' => $newStatus]);

if ($newStatus === PedidoStatus::PAGO) {
    $this->recordCouponUse($pedido);
}

$this->orderEmailService->send($pedido);
```

- [ ] **Step 3: Rodar os testes de MercadoPago**

```bash
docker exec laravel-app php artisan test --filter=MercadoPagoCheckoutTest
```

Esperado: PASS

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/MercadoPagoCheckoutController.php
git commit -m "feat: email explícito no MercadoPagoCheckoutController após status update"
```

---

### Task 3: Adicionar email ao PedidoController

**Files:**
- Modify: `app/Http/Controllers/PedidoController.php`

- [ ] **Step 1: Adicionar injeção de OrderEmailNotificationService no construtor**

Localizar (linhas 18-21):
```php
public function __construct(
    protected CheckoutOrderService $checkoutOrderService
) {
}
```

Substituir por:
```php
public function __construct(
    protected CheckoutOrderService $checkoutOrderService,
    protected \App\Services\OrderEmailNotificationService $orderEmailService,
) {
}
```

- [ ] **Step 2: Adicionar email em confirmarEntrega**

Localizar (linha ~231):
```php
$pedido->update([
    'status' => PedidoStatus::ENTREGUE,
]);

return redirect($this->checkoutOrderService->orderUrl($pedido))
    ->with('success', 'Entrega confirmada com sucesso.');
```

Substituir por:
```php
$pedido->update([
    'status' => PedidoStatus::ENTREGUE,
]);

$this->orderEmailService->send($pedido);

return redirect($this->checkoutOrderService->orderUrl($pedido))
    ->with('success', 'Entrega confirmada com sucesso.');
```

- [ ] **Step 3: Adicionar email em cancelar**

Localizar (linha ~258):
```php
$pedido->update(['status' => PedidoStatus::CANCELADO]);

return redirect()->route('site.pedidos.index')
    ->with('success', 'Pedido #' . $pedido->id . ' cancelado com sucesso.');
```

Substituir por:
```php
$pedido->update(['status' => PedidoStatus::CANCELADO]);

$this->orderEmailService->send($pedido);

return redirect()->route('site.pedidos.index')
    ->with('success', 'Pedido #' . $pedido->id . ' cancelado com sucesso.');
```

- [ ] **Step 4: Rodar testes**

```bash
docker exec laravel-app php artisan test --filter=PedidoEntregaConfirmationTest
```

Esperado: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/PedidoController.php
git commit -m "feat: email explícito no PedidoController para entrega e cancelamento"
```

---

### Task 4: Adicionar flag send_email ao atualizarStatusPedido e email ao quickStatusPedido

**Files:**
- Modify: `app/Http/Controllers/AdminController.php`

- [ ] **Step 1: Atualizar atualizarStatusPedido — adicionar flag send_email**

Localizar (linha ~988):
```php
public function atualizarStatusPedido(Request $request, $id)
{
    $pedido = Pedido::with(['itens.produto', 'itens.produtoVariante'])->findOrFail($id);

    $validated = $request->validate([
        'status' => ['required', Rule::in(PedidoStatus::adminValues())],
        'codigo_rastreio' => ['nullable', 'string', 'max:50', Rule::requiredIf(fn () => $request->input('status') === PedidoStatus::ENVIADO)],
        'nota_fiscal_imagem' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
    ]);
```

Substituir por:
```php
public function atualizarStatusPedido(Request $request, $id)
{
    $pedido = Pedido::with(['itens.produto', 'itens.produtoVariante'])->findOrFail($id);

    $validated = $request->validate([
        'status' => ['required', Rule::in(PedidoStatus::adminValues())],
        'codigo_rastreio' => ['nullable', 'string', 'max:50', Rule::requiredIf(fn () => $request->input('status') === PedidoStatus::ENVIADO)],
        'nota_fiscal_imagem' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        'send_email' => ['nullable', 'boolean'],
    ]);
```

- [ ] **Step 2: Adicionar chamada de email após o update em atualizarStatusPedido**

Localizar (linha ~1023):
```php
        $pedido->update($updates);
        });

        return redirect()->route('admin.pedidos')->with('success', 'Status do pedido atualizado com sucesso!');
    }
```

Substituir por:
```php
        $pedido->update($updates);
        });

        if ($request->boolean('send_email') && in_array($validated['status'], PedidoStatus::notificationValues(), true)) {
            app(\App\Services\OrderEmailNotificationService::class)->send($pedido);
        }

        return redirect()->route('admin.pedidos')->with('success', 'Status do pedido atualizado com sucesso!');
    }
```

- [ ] **Step 3: Adicionar email ao quickStatusPedido**

Localizar (linha ~3156):
```php
        DB::transaction(function () use ($pedido, $updates, $preparationData) {
            if ($preparationData !== []) {
                $this->persistPreparationItemData($pedido, $preparationData);
            }

            $pedido->update($updates);
        });

        return response()->json([
            'success' => true,
```

Substituir por:
```php
        DB::transaction(function () use ($pedido, $updates, $preparationData) {
            if ($preparationData !== []) {
                $this->persistPreparationItemData($pedido, $preparationData);
            }

            $pedido->update($updates);
        });

        app(\App\Services\OrderEmailNotificationService::class)->send($pedido);

        return response()->json([
            'success' => true,
```

- [ ] **Step 4: Rodar todos os testes**

```bash
docker exec laravel-app php artisan test
```

Esperado: todos passando

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/AdminController.php
git commit -m "feat: flag send_email em atualizarStatusPedido e email explícito em quickStatusPedido"
```

---

### Task 5: UI — Checkbox no #modalStatus (HTML)

**Files:**
- Modify: `resources/views/admin/pedidos.blade.php`

- [ ] **Step 1: Adicionar o bloco do checkbox antes do fechamento do `<div class="p-6">`**

Localizar (linha ~325):
```blade
                    </div>
                </div>

                <div class="p-6 border-t border-[var(--color-lab-border)] flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
```

Substituir por:
```blade
                    </div>

                    <div id="emailNotificacaoWrapper" class="mt-4 hidden">
                        <div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] px-4 py-3">
                            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Notificação ao cliente</p>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="send_email" id="sendEmailCheckbox" value="1" checked
                                    class="w-4 h-4 accent-black cursor-pointer">
                                <span class="font-mono text-xs text-black">Enviar e-mail ao cliente</span>
                            </label>
                            <p id="emailAssuntoTexto" class="mt-1 ml-6 font-mono text-[10px] text-[var(--color-lab-muted)]"></p>
                        </div>
                    </div>
                </div>

                <div class="p-6 border-t border-[var(--color-lab-border)] flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
```

- [ ] **Step 2: Limpar cache e testar visualmente**

```bash
docker exec laravel-app php artisan view:clear
```

Abrir `/admin/pedidos`, clicar em "Alterar Status" de um pedido. O bloco de e-mail não deve aparecer ainda (o JS será adicionado na próxima task).

- [ ] **Step 3: Commit**

```bash
git add resources/views/admin/pedidos.blade.php
git commit -m "feat: HTML do checkbox de notificação email no modal de status"
```

---

### Task 6: UI — Toggle JS do checkbox

**Files:**
- Modify: `public/js/admin.js`

- [ ] **Step 1: Adicionar a função `alternarCheckboxEmail` após `alternarCampoCodigoRastreio`**

Localizar (linha ~793, após o fechamento de `alternarCampoCodigoRastreio`):
```js
    if (!requiresTracking) {
        input.value = '';
    }
}
```

Adicionar depois:
```js
    if (!requiresTracking) {
        input.value = '';
    }
}

var EMAIL_ASSUNTOS = {
    'pago':        'Pagamento aprovado',
    'processando': 'Pedido em processamento',
    'enviado':     'Pedido enviado para entrega',
    'entregue':    'Pedido entregue',
    'cancelado':   'Pedido cancelado'
};

function alternarCheckboxEmail(status) {
    var wrapper  = document.getElementById('emailNotificacaoWrapper');
    var checkbox = document.getElementById('sendEmailCheckbox');
    var texto    = document.getElementById('emailAssuntoTexto');

    if (!wrapper || !checkbox || !texto) return;

    var mostra = EMAIL_ASSUNTOS.hasOwnProperty(status);
    wrapper.classList.toggle('hidden', !mostra);

    if (mostra) {
        checkbox.checked = true;
        texto.textContent = EMAIL_ASSUNTOS[status];
    } else {
        checkbox.checked = false;
    }
}
```

- [ ] **Step 2: Chamar `alternarCheckboxEmail` no inicializador de `alterarStatus`**

Localizar (linha ~740):
```js
function alterarStatus(pedidoId, statusAtual) {
    const form = document.getElementById('formStatus');
    form.action = resolveAdminRoute(window.routes.adminPedidosStatus, { id: pedidoId });
    form.dataset.pedidoId = pedidoId;
    document.getElementById('novoStatus').value = statusAtual;
    alternarCampoCodigoRastreio(statusAtual);
    alternarCampoCustosPreparacao(statusAtual);
    document.getElementById('modalStatus').classList.remove('hidden');
}
```

Substituir por:
```js
function alterarStatus(pedidoId, statusAtual) {
    const form = document.getElementById('formStatus');
    form.action = resolveAdminRoute(window.routes.adminPedidosStatus, { id: pedidoId });
    form.dataset.pedidoId = pedidoId;
    document.getElementById('novoStatus').value = statusAtual;
    alternarCampoCodigoRastreio(statusAtual);
    alternarCampoCustosPreparacao(statusAtual);
    alternarCheckboxEmail(statusAtual);
    document.getElementById('modalStatus').classList.remove('hidden');
}
```

- [ ] **Step 3: Chamar `alternarCheckboxEmail` no listener de mudança do select**

Localizar (linha ~1005):
```js
        novoStatus.addEventListener('change', function () {
            alternarCampoCodigoRastreio(this.value);
            alternarCampoCustosPreparacao(this.value);
        });
```

Substituir por:
```js
        novoStatus.addEventListener('change', function () {
            alternarCampoCodigoRastreio(this.value);
            alternarCampoCustosPreparacao(this.value);
            alternarCheckboxEmail(this.value);
        });
```

- [ ] **Step 4: Compilar assets**

```bash
cd /var/www/html && npm run build
```

- [ ] **Step 5: Testar visualmente**

Abrir `/admin/pedidos`. Clicar em "Alterar Status":
- Selecionar status `pago` → bloco de e-mail aparece com "Pagamento aprovado", checkbox marcado
- Selecionar status `pendente` → bloco some
- Desmarcar checkbox → submit com `send_email` ausente → status muda, e-mail NÃO é enviado
- Manter checkbox marcado → status muda, e-mail É enviado

- [ ] **Step 6: Commit**

```bash
git add public/js/admin.js
git commit -m "feat: toggle JS do checkbox de e-mail no modal de status do pedido"
```

---

### Task 7: Verificar e corrigir testes

**Files:**
- Modify: `tests/Feature/MercadoPagoCheckoutTest.php` (se necessário)

- [ ] **Step 1: Rodar todos os testes**

```bash
docker exec laravel-app php artisan test
```

- [ ] **Step 2: Se algum teste falhar por e-mail não ser enviado, adicionar `send_email=1` ao request**

Se testes de `AdminController` que chamam `atualizarStatusPedido` e assertam `Mail::assertQueued` falharem, adicionar `'send_email' => '1'` ao array do request nesses testes.

Exemplo de correção:
```php
$this->post(route('admin.pedidos.status', $pedido->id), [
    'status' => PedidoStatus::ENVIADO,
    'codigo_rastreio' => 'BR123456789BR',
    'send_email' => '1',  // adicionar esta linha
]);
```

- [ ] **Step 3: Confirmar que todos os testes passam**

```bash
docker exec laravel-app php artisan test
```

Esperado: verde

- [ ] **Step 4: Commit (se houve alterações)**

```bash
git add tests/
git commit -m "fix: ajustar testes para send_email explícito em atualizarStatusPedido"
```

---

## Self-Review

**Spec coverage:**
- ✅ Observer não envia mais e-mail — Task 1
- ✅ MP webhook sempre envia — Task 2
- ✅ confirmarEntrega e cancelar sempre enviam — Task 3
- ✅ atualizarStatusPedido respeita flag send_email — Task 4
- ✅ quickStatusPedido sempre envia — Task 4
- ✅ Checkbox aparece só para status que disparam e-mail — Tasks 5+6
- ✅ Checkbox marcado por padrão — Task 5 (checked no HTML) + Task 6 (JS reseta para checked)
- ✅ Texto do assunto exibido — Task 6 (EMAIL_ASSUNTOS map)

**Placeholder scan:** nenhum TBD encontrado

**Type consistency:**
- `alternarCheckboxEmail(status)` definida em Task 6 Step 1, chamada em Steps 2 e 3
- `app(\App\Services\OrderEmailNotificationService::class)->send($pedido)` — padrão consistente com AdminController existente
- `$request->boolean('send_email')` — correto para checkbox HTML que envia `value="1"` quando marcado e omite o campo quando desmarcado
