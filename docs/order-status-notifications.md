# Order status notifications

This automation keeps the WhatsApp flows triggered by n8n in sync with the admin order lifecycle. Whenever a pedido transitions into a status that matters for fulfillment, our backend emits a single POST request to the n8n webhook so the automation can format a WhatsApp message and send it to the correct number.

## Trigger rules

- Only the following statuses emit a payload: `pago`, `processando`, `enviado`, `entregue`.
- The webhook fires once per status change and only if the new status matches one of the values above.
- The payload is sent to `ORDER_STATUS_NOTIFICATION_WEBHOOK`, which defaults to `https://webhooks.jfxtech.com.br/webhook/n8n`.

## HTTP contract

- Method: `POST`
- URL: `ORDER_STATUS_NOTIFICATION_WEBHOOK`
- Payload: `application/json`

### Sample payload

```json
{
  "trigger": "pedido_status_changed",
  "pedido": {
    "id": 1234,
    "status": {
      "value": "processando",
      "label": "Em preparação",
      "previous": "pago"
    },
    "valor_total": 259.9,
    "frete_valor": 25,
    "codigo_rastreio": "JFX12345BR",
    "checkout_mode": "authenticated",
    "created_at": "2026-04-07T15:21:00Z",
    "updated_at": "2026-04-07T16:10:00Z"
  },
  "cliente": {
    "id": 42,
    "nome": "Maria Oliveira",
    "email": "maria@example.com",
    "telefone": "67999844366"
  },
  "itens": [
    {
      "produto_id": 77,
      "produto_nome": "Tênis JFX Runner",
      "quantidade": 2,
      "preco_unitario": 99.5,
      "subtotal": 199,
      "opcoes": []
    }
  ]
}
```

### Field reference

- `trigger`: always `pedido_status_changed`.
- `pedido.id`: internal order ID.
- `pedido.status`: new status (`value`), human label (`label`) and previous value for context.
- `pedido.valor_total`, `pedido.frete_valor`: decimal values used in WhatsApp templates.
- `pedido.codigo_rastreio`: tracking code when available.
- `pedido.checkout_mode`: `authenticated` / `guest`.
- `pedido.created_at` / `pedido.updated_at`: ISO timestamps.
- `cliente`: prefers the stored customer fields falling back to the owning user record.
- `itens`: array of line items with price and option metadata. Empty if the pedido has no itens yet.

## Configuration knobs

- `ORDER_STATUS_NOTIFICATION_WEBHOOK`: target URL; keep it pointed to n8n.
- `ORDER_STATUS_NOTIFICATION_TIMEOUT`: HTTP timeout in seconds (default `5`).
- `ORDER_STATUS_NOTIFICATION_ENABLED`: flip to `false` to temporarily disable delivery while keeping the code path intact.

## Deployment notes

- The config file `config/order_status_notifications.php` must be present when `php artisan config:cache` runs; if you add or change the file, run `php artisan config:clear` (or `config:cache` again) in production so the defaults above are available. Without that, the notification service falls back via `config(..., default)` but you still need to make the file discoverable for other deployments.
- The HTTP payload contract is stable even without the config array, because the service uses the default webhook URL, timeout (`5s`), and enabled flag defined in code.
