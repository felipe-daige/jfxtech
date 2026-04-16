<?php

namespace App\Services;

use App\Enums\PedidoStatus;
use App\Mail\OrderStatusMail;
use App\Models\Pedido;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class OrderEmailNotificationService
{
    public function __construct(private CheckoutOrderService $checkoutOrderService) {}

    public function send(Pedido $pedido): void
    {
        if (!in_array($pedido->status, PedidoStatus::notificationValues(), true)) {
            return;
        }

        $this->sendEvent($pedido, $pedido->status);
    }

    public function sendOrderReceived(Pedido $pedido): void
    {
        $this->sendEvent($pedido, 'received');
    }

    private function sendEvent(Pedido $pedido, string $eventType): void
    {
        $pedido->loadMissing(['user', 'itens.produto']);

        $recipient = $this->recipientEmail($pedido);
        if (!$recipient) {
            return;
        }

        try {
            Mail::to($recipient)->queue(new OrderStatusMail(
                $pedido,
                $eventType,
                $this->checkoutOrderService->orderUrl($pedido),
            ));
        } catch (Throwable $exception) {
            Log::error('Order email notification failed', [
                'pedido_id' => $pedido->id,
                'status' => $pedido->status,
                'event_type' => $eventType,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function recipientEmail(Pedido $pedido): ?string
    {
        return $pedido->customer_email ?: $pedido->user?->email;
    }
}
