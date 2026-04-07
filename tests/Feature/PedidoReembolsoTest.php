<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PedidoReembolsoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Pedido $pedido;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->pedido = Pedido::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'pago',
        ]);
    }

    public function test_authenticated_user_can_access_reembolso_view(): void
    {
        $this->actingAs($this->user)
            ->get(route('site.pedidos.reembolso', $this->pedido))
            ->assertOk()
            ->assertViewIs('site.pedidos.reembolso');
    }

    public function test_other_user_cannot_access_reembolso_view(): void
    {
        $other = User::factory()->create();
        $this->actingAs($other)
            ->get(route('site.pedidos.reembolso', $this->pedido))
            ->assertRedirect(route('site.pedidos.index'));
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get(route('site.pedidos.reembolso', $this->pedido))
            ->assertRedirect(route('site.login'));
    }

    public function test_pago_pedido_shows_reembolso_mode(): void
    {
        $this->actingAs($this->user)
            ->get(route('site.pedidos.reembolso', $this->pedido))
            ->assertOk()
            ->assertSee('Solicitar Reembolso');
    }

    public function test_entregue_within_7_days_shows_reembolso_mode(): void
    {
        $this->pedido->update([
            'status'     => 'entregue',
            'updated_at' => now()->subDays(3),
        ]);

        $this->actingAs($this->user)
            ->get(route('site.pedidos.reembolso', $this->pedido))
            ->assertOk()
            ->assertSee('Solicitar Reembolso');
    }

    public function test_entregue_after_7_days_shows_garantia_mode(): void
    {
        $this->pedido->update([
            'status'     => 'entregue',
            'updated_at' => now()->subDays(10),
        ]);

        $this->actingAs($this->user)
            ->get(route('site.pedidos.reembolso', $this->pedido))
            ->assertOk()
            ->assertSee('Garantia de Fábrica');
    }

    public function test_cancelado_pedido_redirects_to_show(): void
    {
        $this->pedido->update(['status' => 'cancelado']);

        $this->actingAs($this->user)
            ->get(route('site.pedidos.reembolso', $this->pedido))
            ->assertRedirect(route('site.pedidos.show', $this->pedido));
    }
}
