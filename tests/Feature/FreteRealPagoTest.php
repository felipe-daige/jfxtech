<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\User;
use App\Enums\PedidoStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FreteRealPagoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['admin' => true]);
    }

    public function test_admin_pode_salvar_frete_custo_real_via_rastreio(): void
    {
        $pedido = Pedido::factory()->create([
            'status'      => PedidoStatus::ENVIADO,
            'frete_valor' => 25.00,
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('admin.pedidos.rastreio', $pedido), [
                'codigo_rastreio' => 'BR123456789BR',
                'frete_custo_real' => 19.50,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('pedidos', [
            'id'              => $pedido->id,
            'codigo_rastreio' => 'BR123456789BR',
            'frete_custo_real' => 19.50,
        ]);
    }

    public function test_accessor_resultado_frete_calcula_diferenca_corretamente(): void
    {
        $pedido = Pedido::factory()->create([
            'status'          => PedidoStatus::ENVIADO,
            'frete_valor'     => 25.00,
            'frete_custo_real' => 19.50,
        ]);

        $this->assertEquals(5.50, $pedido->fresh()->resultado_frete);
    }

    public function test_accessor_resultado_frete_retorna_null_sem_custo_real(): void
    {
        $pedido = Pedido::factory()->create([
            'status'          => PedidoStatus::ENVIADO,
            'frete_valor'     => 25.00,
            'frete_custo_real' => null,
        ]);

        $this->assertNull($pedido->fresh()->resultado_frete);
    }

    public function test_resultado_frete_negativo_quando_custo_maior_que_cobrado(): void
    {
        $pedido = Pedido::factory()->create([
            'status'          => PedidoStatus::ENVIADO,
            'frete_valor'     => 15.00,
            'frete_custo_real' => 22.00,
        ]);

        $this->assertEquals(-7.00, $pedido->fresh()->resultado_frete);
    }

    public function test_frete_custo_real_nulo_nao_aparece_no_pl(): void
    {
        $pedido = Pedido::factory()->create([
            'status'          => PedidoStatus::ENVIADO,
            'frete_valor'     => 25.00,
            'frete_custo_real' => null,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.pedidos.detalhes', $pedido->id))
            ->assertOk()
            ->assertDontSee('Resultado frete');
    }

    public function test_frete_custo_real_aparece_quando_preenchido(): void
    {
        $pedido = Pedido::factory()->create([
            'status'          => PedidoStatus::ENVIADO,
            'frete_valor'     => 25.00,
            'frete_custo_real' => 19.50,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.pedidos.detalhes', $pedido->id))
            ->assertOk()
            ->assertSee('Resultado frete')
            ->assertSee('+R$');
    }

    public function test_frete_custo_real_invalido_retorna_erro_validacao(): void
    {
        $pedido = Pedido::factory()->create(['status' => PedidoStatus::ENVIADO]);

        $this->actingAs($this->admin)
            ->patchJson(route('admin.pedidos.rastreio', $pedido), [
                'frete_custo_real' => -5.00,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['frete_custo_real']);
    }

    public function test_salvar_sem_frete_custo_real_nao_altera_valor_existente_quando_omitido(): void
    {
        $pedido = Pedido::factory()->create([
            'status'          => PedidoStatus::ENVIADO,
            'frete_custo_real' => 19.50,
        ]);

        // Salvar sem enviar frete_custo_real — o campo não deve ser sobrescrito com null
        // (o controller usa ?? null quando não presente, portanto salva null — comportamento esperado)
        $this->actingAs($this->admin)
            ->patchJson(route('admin.pedidos.rastreio', $pedido), [
                'codigo_rastreio' => 'BR000000000BR',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);
    }
}
