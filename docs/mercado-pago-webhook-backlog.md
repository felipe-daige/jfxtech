# Backlog: Webhook Mercado Pago com assinatura invalida

Data do registro: 2026-05-14

## Contexto

Foi investigada a ultima compra paga pelo Mercado Pago porque a notificacao esperada nao apareceu no destino final.

Pedido analisado:
- Pedido: `#868`
- Pagamento Mercado Pago: `158302298737`
- Status do pedido no Laravel: `pago`
- Status do pagamento no Laravel: `pago`
- Detalhe do gateway: `accredited`

## Diagnostico

A comunicacao `Laravel -> n8n` esta integra.

Evidencias:
- O webhook do n8n respondeu `200` em chamada diagnostica feita pelo proprio Laravel.
- O banco do n8n registrou a execucao `1185` do workflow `Webhook`.
- A execucao `1185` iniciou em `2026-05-13 12:01:56.177 UTC`.
- A execucao terminou com status `success`.
- A execucao contem `trigger: pedido_status_changed`.
- A execucao contem `pedido_id: 868`.
- A execucao chegou ate o node `Ntfy`.

Portanto, se a notificacao final nao foi entregue, a falha provavel esta depois da entrada no n8n, no fluxo/node de entrega, nao no envio do Laravel para o n8n.

## Problema encontrado

O erro visivel no Laravel esta na etapa `Mercado Pago -> Laravel`.

O Mercado Pago chamou `https://jfxtech.com.br/webhooks/mercado-pago`, mas o Laravel recusou os callbacks com:

```text
mercado_pago.webhook.invalid_signature
```

Esse erro apareceu para o pagamento `158302298737` em multiplas tentativas do Mercado Pago.

## Hipoteses principais

- `MERCADO_PAGO_WEBHOOK_SECRET` no `.env` nao bate com o segredo configurado no painel do Mercado Pago.
- O webhook no painel do Mercado Pago esta associado a outro app ou ambiente.
- Existe mistura entre credenciais `TEST` e configuracao de webhook de outro ambiente.
- O cache/config do Laravel pode estar carregando um secret antigo.
- Menos provavel: a implementacao local de validacao da assinatura nao esta compativel com o formato real enviado pelo Mercado Pago.

## Proximos passos

1. Conferir no painel do Mercado Pago qual app/ambiente esta apontando para `https://jfxtech.com.br/webhooks/mercado-pago`.
2. Conferir se o segredo de assinatura do webhook no painel e igual ao `MERCADO_PAGO_WEBHOOK_SECRET` configurado no `.env`.
3. Rodar `docker exec laravel-app php artisan config:clear` apos qualquer alteracao de `.env`.
4. Refazer um pagamento sandbox ou reenviar a notificacao pelo painel do Mercado Pago.
5. Confirmar que o log `mercado_pago.webhook.invalid_signature` nao aparece mais.
6. Confirmar que o webhook do Mercado Pago atualiza o pagamento sem depender apenas da resposta imediata do checkout.
7. Se a assinatura continuar falhando, revisar a funcao `webhookSignatureIsValid()` em `app/Http/Controllers/MercadoPagoCheckoutController.php` contra o payload/header real recebido.

## Observacao

O pedido `#868` foi persistido corretamente como pago porque o fluxo de checkout recebeu resposta suficiente do Mercado Pago no momento do pagamento. O problema pendente e a validacao das notificacoes assincronas enviadas posteriormente pelo Mercado Pago para o endpoint Laravel.
