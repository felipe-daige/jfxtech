<?php

namespace Tests\Feature;

use App\Models\Cupom;
use App\Models\CupomUso;
use App\Models\ItemPedido;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CupomCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function makeCart(User $user, float $preco = 100.00, int $qtd = 1): Pedido
    {
        $produto = Produto::factory()->create(['preco' => $preco, 'estoque' => 10, 'ativo' => true]);
        $pedido  = Pedido::create([
            'user_id'        => $user->id,
            'status'         => 'carrinho',
            'valor_total'    => $preco * $qtd,
            'valor_desconto' => 0,
        ]);
        ItemPedido::create([
            'pedido_id' => $pedido->id,
            'produto_id' => $produto->id,
            'preco'     => $preco,
            'quantidade' => $qtd,
        ]);
        return $pedido;
    }

    private function makeCupom(array $attrs = []): Cupom
    {
        return Cupom::create(array_merge([
            'codigo'              => 'PROMO10',
            'tipo'                => 'percentual',
            'valor'               => 10,
            'ativo'               => true,
            'valido_ate'          => null,
            'limite_usos'         => null,
            'valor_minimo_pedido' => null,
        ], $attrs));
    }

    public function test_aplicar_cupom_percentual_retorna_desconto_correto(): void
    {
        $user = User::factory()->create();
        $this->makeCart($user, 100.00);

        $this->makeCupom(['tipo' => 'percentual', 'valor' => 10]);

        $this->actingAs($user)
            ->postJson(route('cupom.aplicar'), ['codigo' => 'PROMO10'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('desconto', '10,00')
            ->assertJsonPath('novo_total', '90,00');
    }

    public function test_aplicar_cupom_fixo_retorna_desconto_correto(): void
    {
        $user = User::factory()->create();
        $this->makeCart($user, 100.00);

        $this->makeCupom(['codigo' => 'FIXO20', 'tipo' => 'fixo', 'valor' => 20]);

        $this->actingAs($user)
            ->postJson(route('cupom.aplicar'), ['codigo' => 'FIXO20'])
            ->assertOk()
            ->assertJsonPath('desconto', '20,00')
            ->assertJsonPath('novo_total', '80,00');
    }

    public function test_cupom_invalido_retorna_422(): void
    {
        $user = User::factory()->create();
        $this->makeCart($user);

        $this->actingAs($user)
            ->postJson(route('cupom.aplicar'), ['codigo' => 'INEXISTENTE'])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_cupom_expirado_retorna_422(): void
    {
        $user = User::factory()->create();
        $this->makeCart($user);

        $this->makeCupom(['codigo' => 'VENCIDO', 'valido_ate' => now()->subDay()->toDateString()]);

        $this->actingAs($user)
            ->postJson(route('cupom.aplicar'), ['codigo' => 'VENCIDO'])
            ->assertStatus(422);
    }

    public function test_cupom_com_limite_esgotado_retorna_422(): void
    {
        $user = User::factory()->create();
        $this->makeCart($user);

        $this->makeCupom(['codigo' => 'ESGOTADO', 'limite_usos' => 5, 'usos_realizados' => 5]);

        $this->actingAs($user)
            ->postJson(route('cupom.aplicar'), ['codigo' => 'ESGOTADO'])
            ->assertStatus(422);
    }

    public function test_usuario_nao_pode_reusar_cupom(): void
    {
        $user   = User::factory()->create();
        $pedido = $this->makeCart($user);
        $cupom  = $this->makeCupom();

        CupomUso::create([
            'cupom_id'  => $cupom->id,
            'user_id'   => $user->id,
            'pedido_id' => $pedido->id,
        ]);

        // Novo carrinho para o mesmo usuário
        $this->makeCart($user);

        $this->actingAs($user)
            ->postJson(route('cupom.aplicar'), ['codigo' => 'PROMO10'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Você já utilizou este cupom.');
    }

    public function test_cupom_com_valor_minimo_nao_atingido_retorna_422(): void
    {
        $user = User::factory()->create();
        $this->makeCart($user, 30.00);

        $this->makeCupom(['codigo' => 'MINIMO', 'valor_minimo_pedido' => 50.00]);

        $this->actingAs($user)
            ->postJson(route('cupom.aplicar'), ['codigo' => 'MINIMO'])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Este cupom exige pedido mínimo de R$ 50,00.']);
    }

    public function test_remover_cupom_zera_desconto(): void
    {
        $user   = User::factory()->create();
        $pedido = $this->makeCart($user);
        $pedido->update(['cupom_codigo' => 'PROMO10', 'valor_desconto' => 10]);

        $this->actingAs($user)
            ->postJson(route('cupom.remover'))
            ->assertOk()
            ->assertJsonPath('success', true);

        $pedido->refresh();
        $this->assertNull($pedido->cupom_codigo);
        $this->assertEquals('0.00', $pedido->valor_desconto);
    }
}
