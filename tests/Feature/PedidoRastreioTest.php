<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PedidoRastreioTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Pedido $pedido;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['admin' => true]);
        $this->pedido = Pedido::factory()->create(['status' => 'enviado', 'codigo_rastreio' => null]);
    }

    public function test_admin_can_save_tracking_code(): void
    {
        $this->actingAs($this->admin)
            ->patchJson(route('admin.pedidos.rastreio', $this->pedido), [
                'codigo_rastreio' => 'BR123456789BR',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('pedidos', [
            'id' => $this->pedido->id,
            'codigo_rastreio' => 'BR123456789BR',
        ]);
    }

    public function test_admin_can_clear_tracking_code(): void
    {
        $this->pedido->update(['codigo_rastreio' => 'BR111111111BR']);

        $this->actingAs($this->admin)
            ->patchJson(route('admin.pedidos.rastreio', $this->pedido), [
                'codigo_rastreio' => null,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('pedidos', [
            'id' => $this->pedido->id,
            'codigo_rastreio' => null,
        ]);
    }

    public function test_tracking_code_max_length_is_50(): void
    {
        $this->actingAs($this->admin)
            ->patchJson(route('admin.pedidos.rastreio', $this->pedido), [
                'codigo_rastreio' => str_repeat('A', 51),
            ])
            ->assertUnprocessable();
    }

    public function test_unauthenticated_cannot_save_tracking_code(): void
    {
        $this->patchJson(route('admin.pedidos.rastreio', $this->pedido), [
            'codigo_rastreio' => 'BR123456789BR',
        ])
            ->assertUnauthorized();
    }
}
