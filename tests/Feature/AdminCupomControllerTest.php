<?php

namespace Tests\Feature;

use App\Models\Cupom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCupomControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['admin' => true]);
    }

    public function test_store_can_create_coupon_with_linked_user(): void
    {
        $streamer = User::factory()->create();

        $this->actingAs($this->admin)
            ->postJson(route('admin.cupons.store'), [
                'codigo' => 'STREAM10',
                'user_id' => $streamer->id,
                'coupon_portal_enabled' => true,
                'tipo' => 'percentual',
                'valor' => 10,
                'ativo' => true,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('cupom.user.id', $streamer->id)
            ->assertJsonPath('cupom.user.coupon_portal_enabled', true);

        $this->assertDatabaseHas('cupons', [
            'codigo' => 'STREAM10',
            'user_id' => $streamer->id,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $streamer->id,
            'coupon_portal_enabled' => true,
        ]);
    }

    public function test_update_can_change_linked_user(): void
    {
        $streamerAtual = User::factory()->create();
        $novoStreamer = User::factory()->create();
        $cupom = Cupom::create([
            'codigo' => 'LIVE20',
            'user_id' => $streamerAtual->id,
            'tipo' => 'fixo',
            'valor' => 20,
            'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->putJson(route('admin.cupons.update', $cupom->id), [
                'codigo' => 'LIVE20',
                'user_id' => $novoStreamer->id,
                'coupon_portal_enabled' => true,
                'tipo' => 'fixo',
                'valor' => 20,
                'ativo' => true,
            ])
            ->assertOk()
            ->assertJsonPath('cupom.user.id', $novoStreamer->id)
            ->assertJsonPath('cupom.user.coupon_portal_enabled', true);

        $this->assertDatabaseHas('cupons', [
            'id' => $cupom->id,
            'user_id' => $novoStreamer->id,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $novoStreamer->id,
            'coupon_portal_enabled' => true,
        ]);
    }

    public function test_update_can_remove_linked_user(): void
    {
        $streamer = User::factory()->create(['coupon_portal_enabled' => true]);
        $cupom = Cupom::create([
            'codigo' => 'REMOVE1',
            'user_id' => $streamer->id,
            'tipo' => 'percentual',
            'valor' => 15,
            'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->putJson(route('admin.cupons.update', $cupom->id), [
                'codigo' => 'REMOVE1',
                'user_id' => '',
                'tipo' => 'percentual',
                'valor' => 15,
                'ativo' => true,
            ])
            ->assertOk()
            ->assertJsonPath('cupom.user', null);

        $this->assertDatabaseHas('cupons', [
            'id' => $cupom->id,
            'user_id' => null,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $streamer->id,
            'coupon_portal_enabled' => false,
        ]);
    }

    public function test_store_rejects_invalid_user_id(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.cupons.store'), [
                'codigo' => 'BADUSER',
                'user_id' => 999999,
                'tipo' => 'percentual',
                'valor' => 10,
                'ativo' => true,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('user_id');
    }

    public function test_buscar_usuarios_returns_matching_users(): void
    {
        $streamer = User::factory()->create([
            'name' => 'Streamer Alpha',
            'email' => 'streamer@example.com',
        ]);
        User::factory()->create([
            'name' => 'Pessoa Beta',
            'email' => 'beta@example.com',
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('admin.cupons.buscarUsuarios', ['q' => 'stream']))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $streamer->id,
                'name' => 'Streamer Alpha',
                'email' => 'streamer@example.com',
            ]);
    }

    public function test_deleting_linked_user_sets_coupon_user_id_to_null(): void
    {
        $streamer = User::factory()->create();
        $cupom = Cupom::create([
            'codigo' => 'NULLDEL',
            'user_id' => $streamer->id,
            'tipo' => 'percentual',
            'valor' => 5,
            'ativo' => true,
        ]);

        $streamer->delete();

        $this->assertDatabaseHas('cupons', [
            'id' => $cupom->id,
            'user_id' => null,
        ]);
    }

    public function test_destroy_disables_portal_when_user_has_no_other_coupons(): void
    {
        $streamer = User::factory()->create(['coupon_portal_enabled' => true]);
        $cupom = Cupom::create([
            'codigo' => 'DELETE1',
            'user_id' => $streamer->id,
            'tipo' => 'percentual',
            'valor' => 5,
            'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->deleteJson(route('admin.cupons.destroy', $cupom->id))
            ->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $streamer->id,
            'coupon_portal_enabled' => false,
        ]);
    }

    public function test_usos_pendentes_retorna_somente_usos_sem_pagamento(): void
    {
        $cupom = Cupom::create([
            'codigo' => 'PARCEIRO10',
            'tipo' => 'percentual',
            'valor' => 10,
            'ativo' => true,
            'comissao_percentual' => 100,
        ]);

        $pedido1 = \App\Models\Pedido::factory()->create(['valor_desconto' => 20.00, 'status' => 'entregue']);
        $pedido2 = \App\Models\Pedido::factory()->create(['valor_desconto' => 30.00, 'status' => 'entregue']);

        $uso1 = \App\Models\CupomUso::create(['cupom_id' => $cupom->id, 'pedido_id' => $pedido1->id]);
        $uso2 = \App\Models\CupomUso::create(['cupom_id' => $cupom->id, 'pedido_id' => $pedido2->id]);

        $pagamento = \App\Models\CupomPagamento::create([
            'cupom_id' => $cupom->id,
            'valor_pago' => 30.00,
            'pago_em' => '2026-04-01',
        ]);
        $uso2->update(['cupom_pagamento_id' => $pagamento->id]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.cupons.usosPendentes', $cupom->id))
            ->assertOk();

        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals($uso1->id, $data[0]['id']);
        $this->assertEquals('20.00', $data[0]['valor_desconto']);
        $this->assertEquals('20.00', $data[0]['comissao_valor']); // 100% de 20
    }

    public function test_usos_pendentes_aplica_comissao_percentual_corretamente(): void
    {
        $cupom = Cupom::create([
            'codigo' => 'PARCEIRO50',
            'tipo' => 'percentual',
            'valor' => 10,
            'ativo' => true,
            'comissao_percentual' => 50,
        ]);

        $pedido = \App\Models\Pedido::factory()->create(['valor_desconto' => 40.00, 'status' => 'entregue']);
        \App\Models\CupomUso::create(['cupom_id' => $cupom->id, 'pedido_id' => $pedido->id]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.cupons.usosPendentes', $cupom->id))
            ->assertOk();

        $this->assertEquals('20.00', $response->json('0.comissao_valor')); // 50% de 40
    }

    public function test_pagamentos_retorna_lista_de_pagamentos_do_cupom(): void
    {
        $cupom = Cupom::create([
            'codigo' => 'HIST10',
            'tipo' => 'percentual',
            'valor' => 10,
            'ativo' => true,
            'comissao_percentual' => 100,
        ]);

        $pedido = \App\Models\Pedido::factory()->create(['valor_desconto' => 50.00, 'status' => 'entregue']);
        $pagamento = \App\Models\CupomPagamento::create([
            'cupom_id' => $cupom->id,
            'valor_pago' => 50.00,
            'observacao' => 'Ref. março',
            'pago_em' => '2026-03-31',
        ]);
        $uso = \App\Models\CupomUso::create([
            'cupom_id' => $cupom->id,
            'pedido_id' => $pedido->id,
            'cupom_pagamento_id' => $pagamento->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.cupons.pagamentos', $cupom->id))
            ->assertOk();

        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals('50.00', $data[0]['valor_pago']);
        $this->assertEquals(1, $data[0]['usos_count']);
        $this->assertEquals('Ref. março', $data[0]['observacao']);
        $this->assertEquals($uso->id, $data[0]['usos'][0]['id']);
    }

    public function test_store_pagamento_cria_pagamento_e_vincula_usos(): void
    {
        $cupom = Cupom::create([
            'codigo' => 'PAG10',
            'tipo' => 'percentual',
            'valor' => 10,
            'ativo' => true,
            'comissao_percentual' => 100,
        ]);

        $pedido1 = \App\Models\Pedido::factory()->create(['valor_desconto' => 20.00, 'status' => 'entregue']);
        $pedido2 = \App\Models\Pedido::factory()->create(['valor_desconto' => 30.00, 'status' => 'entregue']);
        $uso1 = \App\Models\CupomUso::create(['cupom_id' => $cupom->id, 'pedido_id' => $pedido1->id]);
        $uso2 = \App\Models\CupomUso::create(['cupom_id' => $cupom->id, 'pedido_id' => $pedido2->id]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.cupons.pagamentos.store', $cupom->id), [
                'valor_pago' => 50.00,
                'pago_em' => '2026-04-30',
                'observacao' => 'Pagamento abril',
                'uso_ids' => [$uso1->id, $uso2->id],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('cupom_pagamentos', [
            'cupom_id' => $cupom->id,
            'valor_pago' => 50.00,
            'observacao' => 'Pagamento abril',
        ]);
        $pagamentoId = \App\Models\CupomPagamento::where('cupom_id', $cupom->id)->value('id');
        $this->assertDatabaseHas('cupom_usos', ['id' => $uso1->id, 'cupom_pagamento_id' => $pagamentoId]);
        $this->assertDatabaseHas('cupom_usos', ['id' => $uso2->id, 'cupom_pagamento_id' => $pagamentoId]);
    }

    public function test_store_pagamento_sem_uso_ids_cria_pagamento_sem_vincular(): void
    {
        $cupom = Cupom::create([
            'codigo' => 'SEMUSO',
            'tipo' => 'percentual',
            'valor' => 10,
            'ativo' => true,
            'comissao_percentual' => 100,
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.cupons.pagamentos.store', $cupom->id), [
                'valor_pago' => 100.00,
                'pago_em' => '2026-04-30',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('cupom_pagamentos', ['cupom_id' => $cupom->id, 'valor_pago' => 100.00]);
    }

    public function test_update_pagamento_atualiza_campos(): void
    {
        $cupom = Cupom::create([
            'codigo' => 'UPAG10',
            'tipo' => 'percentual',
            'valor' => 10,
            'ativo' => true,
            'comissao_percentual' => 100,
        ]);
        $pagamento = \App\Models\CupomPagamento::create([
            'cupom_id' => $cupom->id,
            'valor_pago' => 50.00,
            'pago_em' => '2026-04-01',
        ]);

        $this->actingAs($this->admin)
            ->putJson(route('admin.cupons.pagamentos.update', [$cupom->id, $pagamento->id]), [
                'valor_pago' => 75.00,
                'pago_em' => '2026-04-15',
                'observacao' => 'Ajuste de valor',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('cupom_pagamentos', [
            'id' => $pagamento->id,
            'valor_pago' => 75.00,
            'observacao' => 'Ajuste de valor',
        ]);
    }

    public function test_destroy_pagamento_libera_usos_vinculados(): void
    {
        $cupom = Cupom::create([
            'codigo' => 'DESTPAG',
            'tipo' => 'percentual',
            'valor' => 10,
            'ativo' => true,
            'comissao_percentual' => 100,
        ]);
        $pedido = \App\Models\Pedido::factory()->create(['valor_desconto' => 20.00, 'status' => 'entregue']);
        $pagamento = \App\Models\CupomPagamento::create([
            'cupom_id' => $cupom->id,
            'valor_pago' => 20.00,
            'pago_em' => '2026-04-01',
        ]);
        $uso = \App\Models\CupomUso::create([
            'cupom_id' => $cupom->id,
            'pedido_id' => $pedido->id,
            'cupom_pagamento_id' => $pagamento->id,
        ]);

        $this->actingAs($this->admin)
            ->deleteJson(route('admin.cupons.pagamentos.destroy', [$cupom->id, $pagamento->id]))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('cupom_pagamentos', ['id' => $pagamento->id]);
        $this->assertDatabaseHas('cupom_usos', ['id' => $uso->id, 'cupom_pagamento_id' => null]);
    }

    public function test_index_calcula_metricas_de_comissao_por_cupom(): void
    {
        $parceiro = User::factory()->create();
        $cupom = Cupom::create([
            'codigo' => 'METRIC10',
            'user_id' => $parceiro->id,
            'tipo' => 'percentual',
            'valor' => 10,
            'ativo' => true,
            'comissao_percentual' => 50,
        ]);

        $pedido1 = \App\Models\Pedido::factory()->create(['valor_total' => 100.00, 'valor_desconto' => 10.00, 'status' => 'entregue']);
        $pedido2 = \App\Models\Pedido::factory()->create(['valor_total' => 200.00, 'valor_desconto' => 20.00, 'status' => 'entregue']);
        $uso1 = \App\Models\CupomUso::create(['cupom_id' => $cupom->id, 'pedido_id' => $pedido1->id]);
        \App\Models\CupomUso::create(['cupom_id' => $cupom->id, 'pedido_id' => $pedido2->id]);

        $pagamento = \App\Models\CupomPagamento::create([
            'cupom_id' => $cupom->id, 'valor_pago' => 5.00, 'pago_em' => '2026-04-01',
        ]);
        $uso1->update(['cupom_pagamento_id' => $pagamento->id]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.cupons.index'))
            ->assertOk();

        $cupons = $response->viewData('cupons');
        $c = $cupons->firstWhere('codigo', 'METRIC10');

        $this->assertEquals(300.00, $c->receita_gerada);    // 100 + 200
        $this->assertEquals(30.00, $c->descontos_dados);    // 10 + 20
        $this->assertEquals(15.00, $c->comissao_total);     // (10+20) × 50%
        $this->assertEquals(5.00, $c->comissao_paga);       // pagamento registrado
        $this->assertEquals(10.00, $c->comissao_pendente);  // 15 - 5
    }
}
