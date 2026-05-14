<?php

namespace App\Services;

use App\Enums\PedidoStatus;
use App\Models\Pedido;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderStatusNotificationService
{
    public function send(Pedido $pedido): void
    {
        $enabled = (bool) config('order_status_notifications.enabled', true);
        if (!$enabled) {
            return;
        }

        if (!$this->shouldNotify($pedido)) {
            return;
        }

        $webhook = config('order_status_notifications.webhook_url', env('ORDER_STATUS_NOTIFICATION_WEBHOOK', 'https://webhooks.jfxtech.com.br/webhook/n8n'));
        if (empty($webhook)) {
            return;
        }

        $timeout = (float) config('order_status_notifications.timeout', 5);

        try {
            $http = Http::timeout($timeout);

            $authUser = config('order_status_notifications.auth_user', '');
            $authPass = config('order_status_notifications.auth_pass', '');
            if (!empty($authUser) && !empty($authPass)) {
                $http = $http->withBasicAuth($authUser, $authPass);
            }

            $http->post($webhook, $this->buildPayload($pedido));
        } catch (Throwable $exception) {
            Log::error('Order status notification failed', [
                'pedido_id' => $pedido->id,
                'status' => $pedido->status,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function sendCartAbandoned(Pedido $pedido): void
    {
        $enabled = (bool) config('order_status_notifications.enabled', true);
        if (!$enabled) {
            return;
        }

        $webhook = config('order_status_notifications.webhook_url', env('ORDER_STATUS_NOTIFICATION_WEBHOOK', 'https://webhooks.jfxtech.com.br/webhook/n8n'));
        if (empty($webhook)) {
            return;
        }

        $timeout = (float) config('order_status_notifications.timeout', 5);

        try {
            $http = Http::timeout($timeout);

            $authUser = config('order_status_notifications.auth_user', '');
            $authPass = config('order_status_notifications.auth_pass', '');
            if (!empty($authUser) && !empty($authPass)) {
                $http = $http->withBasicAuth($authUser, $authPass);
            }

            $payload = $this->buildPayload($pedido);
            $payload['trigger'] = 'cart_abandoned';

            $http->post($webhook, $payload);
        } catch (Throwable $exception) {
            Log::error('Cart abandoned notification failed', [
                'pedido_id' => $pedido->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function shouldNotify(Pedido $pedido): bool
    {
        return in_array($pedido->status, PedidoStatus::webhookNotificationValues(), true);
    }

    protected function buildPayload(Pedido $pedido): array
    {
        $pedido->loadMissing(['user', 'itens', 'itens.produto']);

        $user = $pedido->user;
        $items = $pedido->itens->map(function ($item) {
            return [
                'produto_id' => $item->produto_id,
                'produto_nome' => $item->produto->nome ?? null,
                'quantidade' => $item->quantidade,
                'preco_unitario' => (float) $item->preco,
                'subtotal' => (float) $item->subtotal,
                'opcoes' => $item->opcoes_snapshot ?? [],
            ];
        })->values()->all();

        return [
            'trigger' => 'pedido_status_changed',
            'pedido' => [
                'id' => $pedido->id,
                'status' => [
                    'value' => $pedido->status,
                    'label' => PedidoStatus::label($pedido->status),
                    'previous' => $pedido->getOriginal('status'),
                ],
                'valor_total' => (float) $pedido->valor_total,
                'frete_valor' => (float) $pedido->frete_valor,
                'codigo_rastreio' => $pedido->codigo_rastreio,
                'checkout_mode' => $pedido->checkout_mode,
                'created_at' => optional($pedido->created_at)->toIso8601String(),
                'updated_at' => optional($pedido->updated_at)->toIso8601String(),
            ],
            'cliente' => [
                'id' => $pedido->user_id,
                'nome' => $pedido->customer_name ?? $user->name ?? null,
                'email' => $pedido->customer_email ?? $user->email ?? null,
                'telefone' => $pedido->customer_phone ?? ($user->phone ?? null),
            ],
            'itens' => $items,
        ];
    }
}
