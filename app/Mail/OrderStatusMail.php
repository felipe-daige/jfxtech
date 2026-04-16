<?php

namespace App\Mail;

use App\Enums\PedidoStatus;
use App\Models\Pedido;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStatusMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Pedido $pedido,
        public string $eventType,
        public ?string $orderUrl = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectForEvent(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->viewForEvent(),
            with: [
                'pedido' => $this->pedido,
                'eventType' => $this->eventType,
                'orderUrl' => $this->orderUrl,
                'deliveryConfirmationUrl' => $this->deliveryConfirmationUrl(),
                'trackingUrl' => $this->trackingUrl(),
                'title' => $this->titleForEvent(),
                'bodyText' => $this->messageForEvent(),
                'iconUrl' => $this->iconUrlForEvent(),
                'iconAlt' => $this->iconAltForEvent(),
            ],
        );
    }

    private function viewForEvent(): string
    {
        return match ($this->eventType) {
            'received' => 'emails.orders.received',
            PedidoStatus::PAGO => 'emails.orders.paid',
            PedidoStatus::PROCESSANDO => 'emails.orders.processing',
            PedidoStatus::ENVIADO => 'emails.orders.shipped',
            PedidoStatus::ENTREGUE => 'emails.orders.delivered',
            PedidoStatus::CANCELADO => 'emails.orders.status',
            default => 'emails.orders.status',
        };
    }

    private function subjectForEvent(): string
    {
        return match ($this->eventType) {
            'received' => "Recebemos seu pedido #{$this->pedido->id}",
            PedidoStatus::PAGO => "Pagamento aprovado - Pedido #{$this->pedido->id}",
            PedidoStatus::PROCESSANDO => "Pedido #{$this->pedido->id} em processamento",
            PedidoStatus::ENVIADO => "Pedido #{$this->pedido->id} enviado",
            PedidoStatus::ENTREGUE => "Pedido #{$this->pedido->id} entregue",
            PedidoStatus::CANCELADO => "Pedido #{$this->pedido->id} cancelado",
            default => "Atualização do pedido #{$this->pedido->id}",
        };
    }

    private function titleForEvent(): string
    {
        return match ($this->eventType) {
            'received' => 'Recebemos seu pedido',
            PedidoStatus::PAGO => 'Pagamento aprovado',
            PedidoStatus::PROCESSANDO => 'Pedido em processamento',
            PedidoStatus::ENVIADO => 'Pedido enviado',
            PedidoStatus::ENTREGUE => 'Pedido entregue',
            PedidoStatus::CANCELADO => 'Pedido cancelado',
            default => 'Atualização do pedido',
        };
    }

    private function messageForEvent(): string
    {
        return match ($this->eventType) {
            'received' => 'Seu pedido foi recebido e estamos aguardando a confirmação do pagamento.',
            PedidoStatus::PAGO => 'Seu pagamento foi aprovado. Vamos iniciar a preparação do pedido.',
            PedidoStatus::PROCESSANDO => 'Seu pedido está em preparação.',
            PedidoStatus::ENVIADO => 'Seu pedido saiu para entrega.',
            PedidoStatus::ENTREGUE => 'Seu pedido foi marcado como entregue.',
            PedidoStatus::CANCELADO => 'Seu pedido foi cancelado. Se precisar de ajuda, entre em contato com nosso suporte.',
            default => 'O status do seu pedido foi atualizado.',
        };
    }

    private function iconUrlForEvent(): string
    {
        return match ($this->eventType) {
            'received' => 'https://jfxtech.com.br/storage/images/emails/received.png',
            PedidoStatus::PAGO => 'https://jfxtech.com.br/storage/images/emails/approved.png',
            PedidoStatus::PROCESSANDO => 'https://jfxtech.com.br/storage/images/emails/processing.png',
            PedidoStatus::ENVIADO => 'https://jfxtech.com.br/storage/images/emails/shipped.png',
            PedidoStatus::ENTREGUE => 'https://jfxtech.com.br/storage/images/emails/delivered.png',
            PedidoStatus::CANCELADO => 'https://jfxtech.com.br/storage/images/emails/received.png',
            default => 'https://jfxtech.com.br/storage/images/emails/received.png',
        };
    }

    private function iconAltForEvent(): string
    {
        return match ($this->eventType) {
            'received' => 'Pedido recebido',
            PedidoStatus::PAGO => 'Pagamento aprovado',
            PedidoStatus::PROCESSANDO => 'Pedido em preparação',
            PedidoStatus::ENVIADO => 'Pedido enviado',
            PedidoStatus::ENTREGUE => 'Pedido entregue',
            PedidoStatus::CANCELADO => 'Pedido cancelado',
            default => 'Atualização do pedido',
        };
    }

    private function deliveryConfirmationUrl(): ?string
    {
        if ($this->eventType !== PedidoStatus::ENVIADO || !$this->orderUrl) {
            return null;
        }

        return $this->orderUrl . (str_contains($this->orderUrl, '?') ? '&' : '?') . 'confirm_delivery=1#confirmar-entrega';
    }

    private function trackingUrl(): ?string
    {
        if ($this->eventType !== PedidoStatus::ENVIADO || empty($this->pedido->codigo_rastreio)) {
            return null;
        }

        return 'https://rastreamento.correios.com.br/app/index.php?objetos=' . urlencode((string) $this->pedido->codigo_rastreio);
    }
}
