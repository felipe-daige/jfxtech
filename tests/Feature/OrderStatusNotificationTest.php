<?php

namespace Tests\Feature;

use App\Enums\PedidoStatus;
use App\Jobs\CartAbandonedNotificationJob;
use App\Models\Pedido;
use App\Services\OrderStatusNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderStatusNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_sent_when_status_changes_to_relevant_value()
    {
        Http::fake();

        $pedido = Pedido::factory()->create([
            'status' => PedidoStatus::PENDENTE,
            'valor_total' => 149.9,
            'frete_valor' => 18.5,
        ]);

        $pedido->update(['status' => PedidoStatus::PAGO]);

        Http::assertSent(function ($request) use ($pedido) {
            $data = $request->data();

            return $request->url() === config('order_status_notifications.webhook_url')
                && $data['pedido']['id'] === $pedido->id
                && $data['pedido']['status']['value'] === PedidoStatus::PAGO
                && $data['trigger'] === 'pedido_status_changed';
        });
    }

    public function test_notification_not_sent_for_irrelevant_status()
    {
        Http::fake();
        Queue::fake();

        $pedido = Pedido::factory()->create(['status' => PedidoStatus::PAGO]);
        $pedido->update(['status' => PedidoStatus::CANCELADO]);

        Http::assertNothingSent();
    }

    public function test_defaults_fire_when_config_keys_missing()
    {
        Http::fake();

        $pedido = Pedido::factory()->create(['status' => PedidoStatus::PENDENTE]);
        $pedido->update(['status' => PedidoStatus::PAGO]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://webhooks.jfxtech.com.br/webhook/n8n';
        });
    }

    public function test_cart_abandoned_job_dispatched_with_delay_when_status_changes_to_pendente()
    {
        Queue::fake();

        $pedido = Pedido::factory()->carrinho()->create();
        $pedido->update(['status' => PedidoStatus::PENDENTE]);

        Queue::assertPushed(CartAbandonedNotificationJob::class, function ($job) use ($pedido) {
            return $job->pedidoId === $pedido->id;
        });
    }

    public function test_cart_abandoned_job_does_not_send_if_pedido_already_paid()
    {
        Http::fake();

        $pedido = Pedido::factory()->pago()->create();

        app(CartAbandonedNotificationJob::class, ['pedidoId' => $pedido->id])
            ->handle(app(OrderStatusNotificationService::class));

        Http::assertNothingSent();
    }

    public function test_cart_abandoned_job_sends_notification_if_still_pendente()
    {
        Http::fake();

        $pedido = Pedido::factory()->create([
            'status' => PedidoStatus::PENDENTE,
            'customer_name' => 'João Silva',
            'customer_email' => 'joao@email.com',
            'customer_phone' => '43999999999',
        ]);

        app(CartAbandonedNotificationJob::class, ['pedidoId' => $pedido->id])
            ->handle(app(OrderStatusNotificationService::class));

        Http::assertSent(function ($request) use ($pedido) {
            $data = $request->data();

            return $data['trigger'] === 'cart_abandoned'
                && $data['pedido']['id'] === $pedido->id
                && $data['cliente']['email'] === 'joao@email.com';
        });
    }

    public function test_cart_abandoned_job_sends_notification_for_guest()
    {
        Http::fake();

        $pedido = Pedido::factory()->create([
            'user_id' => null,
            'status' => PedidoStatus::PENDENTE,
            'customer_name' => 'Maria Guest',
            'customer_email' => 'maria@email.com',
            'customer_phone' => '11988888888',
            'checkout_mode' => 'guest',
        ]);

        app(CartAbandonedNotificationJob::class, ['pedidoId' => $pedido->id])
            ->handle(app(OrderStatusNotificationService::class));

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $data['trigger'] === 'cart_abandoned'
                && $data['cliente']['id'] === null
                && $data['cliente']['email'] === 'maria@email.com'
                && $data['pedido']['checkout_mode'] === 'guest';
        });
    }
}
