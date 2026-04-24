<?php

namespace Tests\Feature;

use App\Enums\PedidoStatus;
use App\Models\ItemPedido;
use App\Models\Pagamento;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PedidoPendenteSemPagamentoTest extends TestCase
{
    use RefreshDatabase;

    // ─── ensureCart() ────────────────────────────────────────────────────────

    public function test_adding_to_cart_reverts_unpaid_pending_order_to_cart_instead_of_creating_duplicate(): void
    {
        $user = User::factory()->create();
        $pendente = $this->makePendingOrder($user);

        $produto = Produto::factory()->create(['preco' => 50.00, 'estoque' => 5]);

        $this->actingAs($user)->postJson(route('site.carrinho.adicionar'), [
            'produto_id' => $produto->id,
            'quantidade' => 1,
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('pedidos', [
            'id' => $pendente->id,
            'status' => PedidoStatus::CARRINHO,
        ]);

        $this->assertDatabaseCount('pedidos', 1);
    }

    public function test_adding_to_cart_does_not_revert_pending_order_that_has_a_payment(): void
    {
        $user = User::factory()->create();
        $pendente = $this->makePendingOrder($user);

        Pagamento::create([
            'pedido_id' => $pendente->id,
            'metodo' => 'pix',
            'status' => 'pendente',
            'valor' => 120.00,
        ]);

        $produto = Produto::factory()->create(['preco' => 50.00, 'estoque' => 5]);

        $this->actingAs($user)->postJson(route('site.carrinho.adicionar'), [
            'produto_id' => $produto->id,
            'quantidade' => 1,
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('pedidos', [
            'id' => $pendente->id,
            'status' => PedidoStatus::PENDENTE,
        ]);

        $this->assertDatabaseCount('pedidos', 2);
    }

    // ─── cancelar() ──────────────────────────────────────────────────────────

    public function test_user_can_cancel_own_pending_order_without_payment(): void
    {
        $user = User::factory()->create();
        $pedido = $this->makePendingOrder($user);

        $this->actingAs($user)
            ->post(route('site.pedidos.cancelar', $pedido))
            ->assertRedirect(route('site.pedidos.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pedidos', [
            'id' => $pedido->id,
            'status' => PedidoStatus::CANCELADO,
        ]);
    }

    public function test_cannot_cancel_pending_order_that_already_has_payment(): void
    {
        $user = User::factory()->create();
        $pedido = $this->makePendingOrder($user);

        Pagamento::create([
            'pedido_id' => $pedido->id,
            'metodo' => 'pix',
            'status' => 'pendente',
            'valor' => 120.00,
        ]);

        $this->actingAs($user)
            ->post(route('site.pedidos.cancelar', $pedido))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('pedidos', [
            'id' => $pedido->id,
            'status' => PedidoStatus::PENDENTE,
        ]);
    }

    public function test_cannot_cancel_another_users_order(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $pedido = $this->makePendingOrder($owner);

        $this->actingAs($other)
            ->post(route('site.pedidos.cancelar', $pedido))
            ->assertRedirect();

        $this->assertDatabaseHas('pedidos', [
            'id' => $pedido->id,
            'status' => PedidoStatus::PENDENTE,
        ]);
    }

    public function test_cannot_cancel_order_in_processando_status(): void
    {
        $user = User::factory()->create();
        $pedido = Pedido::create([
            'user_id' => $user->id,
            'status' => PedidoStatus::PROCESSANDO,
            'valor_total' => 120.00,
        ]);

        $this->actingAs($user)
            ->post(route('site.pedidos.cancelar', $pedido))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('pedidos', [
            'id' => $pedido->id,
            'status' => PedidoStatus::PROCESSANDO,
        ]);
    }

    // ─── show view ───────────────────────────────────────────────────────────

    public function test_show_view_displays_cancel_button_for_pending_order_without_payment(): void
    {
        $user = User::factory()->create();
        $pedido = $this->makePendingOrder($user);

        $this->actingAs($user)
            ->get(route('site.pedidos.show', $pedido))
            ->assertOk()
            ->assertSee(route('site.pedidos.cancelar', $pedido));
    }

    public function test_show_view_does_not_display_cancel_button_when_order_has_payment(): void
    {
        $user = User::factory()->create();
        $pedido = $this->makePendingOrder($user);

        Pagamento::create([
            'pedido_id' => $pedido->id,
            'metodo' => 'pix',
            'status' => 'pendente',
            'valor' => 120.00,
        ]);

        $this->actingAs($user)
            ->get(route('site.pedidos.show', $pedido))
            ->assertOk()
            ->assertDontSee(route('site.pedidos.cancelar', $pedido));
    }

    // ─── helpers ─────────────────────────────────────────────────────────────

    private function makePendingOrder(User $user): Pedido
    {
        $produto = Produto::factory()->create(['preco' => 120.00, 'peso' => 0.5]);

        $pedido = Pedido::create([
            'user_id' => $user->id,
            'status' => PedidoStatus::PENDENTE,
            'valor_total' => 120.00,
        ]);

        ItemPedido::create([
            'pedido_id' => $pedido->id,
            'produto_id' => $produto->id,
            'quantidade' => 1,
            'preco' => 120.00,
        ]);

        return $pedido;
    }
}
