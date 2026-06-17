# Confirmação de E-mail ao Alterar Status do Pedido

**Data:** 2026-05-26  
**Status:** Aprovado

---

## Objetivo

Quando o admin altera manualmente o status de um pedido (via modal completo), exibir um checkbox "Enviar e-mail ao cliente" (marcado por padrão) para os status que disparam notificação. O admin pode desmarcar para alterar o status sem enviar e-mail.

Fluxos automáticos (pagamento via MP, cliente confirma entrega, cliente cancela) continuam enviando e-mail sempre, sem confirmação.

---

## Arquitetura

### Mudança central: e-mail sai do Observer

Atualmente `PedidoObserver::updated()` chama `OrderEmailNotificationService::send()` para toda mudança de status — independente de quem a fez. O observer precisa ser agnóstico à origem.

**Novo fluxo:** o e-mail é chamado explicitamente em cada ponto que atualiza status:

| Ponto | Comportamento |
|---|---|
| `MercadoPagoCheckoutController::processPaymentUpdate` | Sempre envia |
| `PedidoController::confirmarEntrega` | Sempre envia (ENTREGUE) |
| `PedidoController::cancelar` | Sempre envia (CANCELADO) |
| `AdminController::atualizarStatusPedido` | Envia só se `send_email=1` no request |
| `AdminController::quickStatusPedido` | Sempre envia (ação rápida no dashboard) |

O observer mantém: gravação de histórico (`PedidoHistorico`), webhook n8n, job de carrinho abandonado, conversão de guest.

### Status que disparam e-mail

Definido por `PedidoStatus::notificationValues()`: `pago`, `processando`, `enviado`, `entregue`, `cancelado`.

---

## UI — Checkbox no Modal de Status

No `#modalStatus` em `resources/views/admin/pedidos.blade.php`, adicionar entre o campo de status e o rodapé:

```
┌──────────────────────────────────────────┐
│ NOVO STATUS                              │
│ [Em preparação              ▼]           │
│                                          │
│  NOTIFICAÇÃO AO CLIENTE                  │
│  [✓] Enviar e-mail ao cliente            │
│      "Pedido em processamento"           │
│                                          │
│    [Cancelar]    [Atualizar Status]      │
└──────────────────────────────────────────┘
```

**Comportamento JS:**
- Ao mudar o `select#novoStatus`: se valor está em `['pago','processando','enviado','entregue','cancelado']` → mostrar bloco, marcar checkbox, atualizar texto do assunto
- Caso contrário → esconder bloco (checkbox desmarcado implicitamente)
- O texto abaixo do checkbox mostra o assunto do e-mail que será enviado

**Assuntos por status:**

| Status | Texto exibido |
|---|---|
| `pago` | "Pagamento aprovado" |
| `processando` | "Pedido em processamento" |
| `enviado` | "Pedido enviado para entrega" |
| `entregue` | "Pedido entregue" |
| `cancelado` | "Pedido cancelado" |

---

## Backend

### `atualizarStatusPedido`

```php
$validated = $request->validate([
    'status'     => [...],
    'send_email' => ['nullable', 'boolean'],
    // campos existentes...
]);

// após $pedido->update($updates):
if ($request->boolean('send_email') && in_array($validated['status'], PedidoStatus::notificationValues(), true)) {
    app(OrderEmailNotificationService::class)->send($pedido->fresh());
}
```

### `quickStatusPedido`

Após `$pedido->update($updates)`:
```php
app(OrderEmailNotificationService::class)->send($pedido->fresh());
```

### `MercadoPagoCheckoutController`

Injetar `OrderEmailNotificationService` no construtor. Após `$pedido->update(['status' => $newStatus])`:
```php
$this->orderEmailService->send($pedido->fresh());
```

### `PedidoController`

Injetar `OrderEmailNotificationService`. Após cada `$pedido->update([...])` em `confirmarEntrega` e `cancelar`:
```php
$this->orderEmailService->send($pedido->fresh());
```

---

## Testes Afetados

- `MercadoPagoCheckoutTest` — e-mail agora vem do controller, não do observer; mock do Mail::fake() continua funcionando
- `OrderEmailNotificationTest` — testa o serviço diretamente, não afetado
- `OrderStatusNotificationTest` — testa webhook n8n pelo observer, não afetado

---

## Fora do Escopo

- Confirmação de e-mail para `quickStatusPedido` — ação rápida no dashboard, não interromper
- Personalizar o conteúdo do e-mail neste fluxo
- Histórico de e-mails (coberto pelo plano `2026-05-26-pedido-historico-status-emails.md`)
