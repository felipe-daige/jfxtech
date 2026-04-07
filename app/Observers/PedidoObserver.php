<?php

namespace App\Observers;

use App\Enums\PedidoStatus;
use App\Models\Pedido;
use App\Services\OrderStatusNotificationService;

class PedidoObserver
{
    public function updated(Pedido $pedido): void
    {
        if (!$pedido->wasChanged('status')) {
            return;
        }

        if (!in_array($pedido->status, PedidoStatus::notificationValues(), true)) {
            return;
        }

        app(OrderStatusNotificationService::class)->send($pedido);
    }
}
