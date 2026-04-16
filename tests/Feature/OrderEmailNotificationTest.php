<?php

namespace Tests\Feature;

use App\Enums\PedidoStatus;
use App\Mail\OrderStatusMail;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderEmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_order_sends_email_to_authenticated_customer_and_keeps_webhook(): void
    {
        Mail::fake();
        Http::fake();

        $user = User::factory()->create(['email' => 'cliente@example.com']);
        $pedido = Pedido::factory()->create([
            'user_id' => $user->id,
            'customer_email' => null,
            'status' => PedidoStatus::PENDENTE,
        ]);

        $pedido->update(['status' => PedidoStatus::PAGO]);

        Mail::assertQueued(OrderStatusMail::class, function (OrderStatusMail $mail) use ($pedido) {
            return $mail->pedido->id === $pedido->id
                && $mail->eventType === PedidoStatus::PAGO
                && $mail->hasTo('cliente@example.com');
        });

        Http::assertSent(fn ($request) => $request->data()['pedido']['id'] === $pedido->id);
    }

    public function test_paid_guest_order_sends_email_to_customer_email(): void
    {
        Mail::fake();
        Http::fake();

        $pedido = Pedido::factory()->create([
            'user_id' => null,
            'customer_email' => 'guest@example.com',
            'status' => PedidoStatus::PENDENTE,
            'checkout_mode' => 'guest',
        ]);

        $pedido->update(['status' => PedidoStatus::PAGO]);

        Mail::assertQueued(OrderStatusMail::class, function (OrderStatusMail $mail) use ($pedido) {
            return $mail->pedido->id === $pedido->id
                && $mail->eventType === PedidoStatus::PAGO
                && $mail->hasTo('guest@example.com');
        });
    }

    public function test_processando_enviado_entregue_and_cancelado_send_status_emails(): void
    {
        Mail::fake();
        Http::fake();

        $pedido = Pedido::factory()->create([
            'customer_email' => 'cliente@example.com',
            'status' => PedidoStatus::PAGO,
            'codigo_rastreio' => 'BR123456789BR',
        ]);

        foreach ([PedidoStatus::PROCESSANDO, PedidoStatus::ENVIADO, PedidoStatus::ENTREGUE, PedidoStatus::CANCELADO] as $status) {
            $pedido->update(['status' => $status]);
        }

        Mail::assertQueued(OrderStatusMail::class, fn (OrderStatusMail $mail) => $mail->eventType === PedidoStatus::PROCESSANDO);
        Mail::assertQueued(OrderStatusMail::class, fn (OrderStatusMail $mail) => $mail->eventType === PedidoStatus::ENVIADO && $mail->pedido->codigo_rastreio === 'BR123456789BR');
        Mail::assertQueued(OrderStatusMail::class, fn (OrderStatusMail $mail) => $mail->eventType === PedidoStatus::ENTREGUE);
        Mail::assertQueued(OrderStatusMail::class, fn (OrderStatusMail $mail) => $mail->eventType === PedidoStatus::CANCELADO);
    }

    public function test_shipped_email_renders_tracking_code_correios_link_and_delivery_confirmation_button(): void
    {
        $pedido = Pedido::factory()->create([
            'customer_email' => 'cliente@example.com',
            'status' => PedidoStatus::ENVIADO,
            'guest_token' => 'guest-token-123',
            'codigo_rastreio' => 'BR123456789BR',
        ]);

        $mail = new OrderStatusMail(
            $pedido,
            PedidoStatus::ENVIADO,
            route('site.pedidos.show', $pedido) . '?token=guest-token-123'
        );

        $html = $mail->render();

        $this->assertStringContainsString('BR123456789BR', $html);
        $this->assertStringContainsString('RASTREAR NOS CORREIOS', $html);
        $this->assertStringContainsString('https://rastreamento.correios.com.br/app/index.php?objetos=BR123456789BR', $html);
        $this->assertStringContainsString('CONFIRMAR ENTREGA', $html);
        $this->assertStringContainsString(route('site.pedidos.show', $pedido) . '?token=guest-token-123&amp;confirm_delivery=1#confirmar-entrega', $html);
    }

    public function test_canceled_email_renders_canceled_copy(): void
    {
        $pedido = Pedido::factory()->create([
            'customer_email' => 'cliente@example.com',
            'status' => PedidoStatus::CANCELADO,
        ]);

        $mail = new OrderStatusMail(
            $pedido,
            PedidoStatus::CANCELADO,
            route('site.pedidos.show', $pedido)
        );

        $html = $mail->render();

        $this->assertStringContainsString('Pedido cancelado', $html);
        $this->assertStringContainsString('Seu pedido foi cancelado.', $html);
    }

    public function test_pending_status_does_not_send_regular_order_email(): void
    {
        Mail::fake();
        Http::fake();

        $pedido = Pedido::factory()->carrinho()->create([
            'customer_email' => 'cliente@example.com',
        ]);

        $pedido->update(['status' => PedidoStatus::PENDENTE]);

        Mail::assertNothingQueued();
    }
}
