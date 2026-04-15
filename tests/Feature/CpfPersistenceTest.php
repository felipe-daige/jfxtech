<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CpfPersistenceTest extends TestCase
{
    use RefreshDatabase;

    // ─── perfil_update() ───────────────────────────────────────────────

    public function test_perfil_update_salva_cpf_quando_usuario_nao_tem_cpf(): void
    {
        $user = User::factory()->create(['cpf' => null]);

        $this->actingAs($user)
            ->putJson(route('site.perfil.update'), ['cpf' => '123.456.789-09'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'cpf' => '12345678909']);
    }

    public function test_perfil_update_atualiza_proprio_cpf_sem_conflito_de_unique(): void
    {
        $user = User::factory()->create(['cpf' => '12345678909']);

        $this->actingAs($user)
            ->putJson(route('site.perfil.update'), ['cpf' => '98765432100'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'cpf' => '98765432100']);
    }

    public function test_perfil_update_rejeita_cpf_de_outro_usuario(): void
    {
        User::factory()->create(['cpf' => '11122233344']);
        $user = User::factory()->create(['cpf' => null]);

        $this->actingAs($user)
            ->putJson(route('site.perfil.update'), ['cpf' => '111.222.333-44'])
            ->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'cpf' => null]);
    }

    public function test_perfil_update_rejeita_cpf_com_menos_de_11_digitos(): void
    {
        $user = User::factory()->create(['cpf' => null]);

        $this->actingAs($user)
            ->putJson(route('site.perfil.update'), ['cpf' => '1234567'])
            ->assertStatus(422);
    }

    public function test_perfil_update_ignora_cpf_quando_campo_nao_enviado(): void
    {
        $user = User::factory()->create(['cpf' => '12345678909']);

        $this->actingAs($user)
            ->putJson(route('site.perfil.update'), ['name' => 'Novo Nome'])
            ->assertOk();

        // CPF não deve ser apagado
        $this->assertDatabaseHas('users', ['id' => $user->id, 'cpf' => '12345678909']);
    }

    // ─── pay() CPF capture ─────────────────────────────────────────────

    private function makePedidoPendente(User $user): \App\Models\Pedido
    {
        $produto = \App\Models\Produto::factory()->create(['preco' => 100.00, 'peso' => 0.5, 'estoque' => 5, 'ativo' => true]);
        $pedido  = \App\Models\Pedido::create([
            'user_id'        => $user->id,
            'status'         => 'pendente',
            'valor_total'    => 100.00,
            'frete_tipo'     => 'pac',
            'frete_valor'    => 0.00,
            'customer_email' => $user->email,
            'checkout_mode'  => 'authenticated',
        ]);
        \App\Models\ItemPedido::create([
            'pedido_id'  => $pedido->id,
            'produto_id' => $produto->id,
            'quantidade' => 1,
            'preco'      => 100.00,
        ]);
        return $pedido;
    }

    private function mockMercadoPagoApproved(): void
    {
        $this->mock(\App\Services\MercadoPagoService::class, function ($mock) {
            $mock->shouldReceive('createPayment')->andReturn([
                'id'                 => 'pay_123',
                'status'             => 'approved',
                'status_detail'      => 'accredited',
                'payment_type_id'    => 'credit_card',
                'transaction_amount' => 100.00,
                'external_reference' => '',
            ]);
            $mock->shouldReceive('cancelPayment')->andReturn([]);
        });
    }

    public function test_pay_salva_cpf_quando_usuario_logado_nao_tem_cpf(): void
    {
        $user   = User::factory()->create(['cpf' => null]);
        $pedido = $this->makePedidoPendente($user);
        $this->mockMercadoPagoApproved();

        $this->actingAs($user)
            ->postJson(route('site.checkout.mercadopago.pay'), [
                'pedido_id'          => $pedido->id,
                'payment_method_id'  => 'visa',
                'transaction_amount' => 100.00,
                'installments'       => 1,
                'token'              => 'tok_test',
                'payer'              => [
                    'email'          => $user->email,
                    'identification' => ['type' => 'CPF', 'number' => '12345678909'],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'cpf' => '12345678909']);
    }

    public function test_pay_nao_sobrescreve_cpf_existente(): void
    {
        $user   = User::factory()->create(['cpf' => '99988877766']);
        $pedido = $this->makePedidoPendente($user);
        $this->mockMercadoPagoApproved();

        $this->actingAs($user)
            ->postJson(route('site.checkout.mercadopago.pay'), [
                'pedido_id'          => $pedido->id,
                'payment_method_id'  => 'visa',
                'transaction_amount' => 100.00,
                'installments'       => 1,
                'token'              => 'tok_test',
                'payer'              => [
                    'email'          => $user->email,
                    'identification' => ['type' => 'CPF', 'number' => '12345678909'],
                ],
            ])
            ->assertOk();

        // CPF original deve permanecer intocado
        $this->assertDatabaseHas('users', ['id' => $user->id, 'cpf' => '99988877766']);
    }

    public function test_pay_nao_salva_cpf_para_guest(): void
    {
        $guestToken = 'guest-abc-123';
        $pedido = \App\Models\Pedido::create([
            'status'         => 'pendente',
            'valor_total'    => 100.00,
            'frete_tipo'     => 'pac',
            'frete_valor'    => 0.00,
            'customer_email' => 'guest@example.com',
            'guest_token'    => $guestToken,
            'checkout_mode'  => 'guest',
        ]);
        $produto = \App\Models\Produto::factory()->create(['preco' => 100.00, 'peso' => 0.5, 'estoque' => 5, 'ativo' => true]);
        \App\Models\ItemPedido::create(['pedido_id' => $pedido->id, 'produto_id' => $produto->id, 'quantidade' => 1, 'preco' => 100.00]);

        $this->mockMercadoPagoApproved();

        $this->withSession([
                'checkout_order_id'    => $pedido->id,
                'checkout_order_token' => $guestToken,
            ])
            ->postJson(route('site.checkout.mercadopago.pay'), [
                'pedido_id'          => $pedido->id,
                'payment_method_id'  => 'visa',
                'transaction_amount' => 100.00,
                'installments'       => 1,
                'token'              => 'tok_test',
                'payer'              => [
                    'email'          => 'guest@example.com',
                    'identification' => ['type' => 'CPF', 'number' => '12345678909'],
                ],
            ])
            ->assertOk();

        // Nenhum usuário deve ter esse CPF (guest não tem User)
        $this->assertDatabaseMissing('users', ['cpf' => '12345678909']);
    }

    public function test_pay_nao_falha_quando_cpf_pertence_a_outro_usuario(): void
    {
        User::factory()->create(['cpf' => '12345678909']);
        $user   = User::factory()->create(['cpf' => null]);
        $pedido = $this->makePedidoPendente($user);
        $this->mockMercadoPagoApproved();

        // Deve completar o pagamento normalmente, ignorar silenciosamente o CPF conflitante
        $this->actingAs($user)
            ->postJson(route('site.checkout.mercadopago.pay'), [
                'pedido_id'          => $pedido->id,
                'payment_method_id'  => 'visa',
                'transaction_amount' => 100.00,
                'installments'       => 1,
                'token'              => 'tok_test',
                'payer'              => [
                    'email'          => $user->email,
                    'identification' => ['type' => 'CPF', 'number' => '12345678909'],
                ],
            ])
            ->assertOk();

        // O user atual não deve ter adquirido o CPF de outro
        $this->assertDatabaseHas('users', ['id' => $user->id, 'cpf' => null]);
    }
}
