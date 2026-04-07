<?php

namespace Tests\Feature;

use App\Enums\PedidoStatus;
use App\Models\Pedido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
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
                && $data['pedido']['status']['value'] === PedidoStatus::PAGO;
        });
    }

    public function test_notification_not_sent_for_irrelevant_status()
    {
        Http::fake();

        $pedido = Pedido::factory()->create(['status' => PedidoStatus::PENDENTE]);
        $pedido->update(['status' => PedidoStatus::CANCELADO]);

        Http::assertNothingSent();
    }

    public function test_defaults_fire_when_config_keys_missing()
    {
        Http::fake();
        Config::set('order_status_notifications', null);

        $pedido = Pedido::factory()->create(['status' => PedidoStatus::PENDENTE]);
        $pedido->update(['status' => PedidoStatus::PAGO]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://webhooks.jfxtech.com.br/webhook/n8n';
        });
    }
}
