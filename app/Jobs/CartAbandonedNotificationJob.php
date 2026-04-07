<?php

namespace App\Jobs;

use App\Enums\PedidoStatus;
use App\Models\Pedido;
use App\Services\OrderStatusNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CartAbandonedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $pedidoId) {}

    public function handle(OrderStatusNotificationService $service): void
    {
        $pedido = Pedido::find($this->pedidoId);

        if (!$pedido || $pedido->status !== PedidoStatus::PENDENTE) {
            return;
        }

        $service->sendCartAbandoned($pedido);
    }
}
