<?php

namespace App\Observers;

use App\Enums\PedidoStatus;
use App\Jobs\CartAbandonedNotificationJob;
use App\Models\Pedido;
use App\Services\OrderEmailNotificationService;
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
        app(OrderEmailNotificationService::class)->send($pedido);
    }
}
