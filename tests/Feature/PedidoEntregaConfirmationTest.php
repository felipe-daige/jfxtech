<?php

namespace Tests\Feature;

use App\Enums\PedidoStatus;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PedidoEntregaConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_customer_can_confirm_delivery(): void
    {
        $user = User::factory()->create();
        $pedido = Pedido::factory()->create([
            'user_id' => $user->id,
            'status' => PedidoStatus::ENVIADO,
            'codigo_rastreio' => 'BR123456789BR',
        ]);

        $this->actingAs($user)
            ->post(route('site.pedidos.confirmar-entrega', $pedido))
            ->assertRedirect(route('site.pedidos.show', $pedido));

        $this->assertDatabaseHas('pedidos', [
            'id' => $pedido->id,
            'status' => PedidoStatus::ENTREGUE,
        ]);
    }

    public function test_guest_customer_can_confirm_delivery_with_token(): void
    {
        $pedido = Pedido::factory()->create([
            'user_id' => null,
            'status' => PedidoStatus::ENVIADO,
            'guest_token' => 'guest-token-123',
            'codigo_rastreio' => 'BR123456789BR',
        ]);

        $this->post(route('site.pedidos.confirmar-entrega', $pedido), [
            'guest_token' => 'guest-token-123',
        ])->assertRedirect(route('site.pedidos.show', $pedido) . '?token=guest-token-123');

        $this->assertDatabaseHas('pedidos', [
            'id' => $pedido->id,
            'status' => PedidoStatus::ENTREGUE,
        ]);
    }

    public function test_customer_cannot_confirm_delivery_when_order_is_not_shipped(): void
    {
        $user = User::factory()->create();
        $pedido = Pedido::factory()->create([
            'user_id' => $user->id,
            'status' => PedidoStatus::PROCESSANDO,
        ]);

        $this->actingAs($user)
            ->post(route('site.pedidos.confirmar-entrega', $pedido))
            ->assertRedirect(route('site.pedidos.show', $pedido));

        $this->assertDatabaseHas('pedidos', [
            'id' => $pedido->id,
            'status' => PedidoStatus::PROCESSANDO,
        ]);
    }
}
