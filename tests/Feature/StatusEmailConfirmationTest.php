<?php

namespace Tests\Feature;

use App\Enums\PedidoStatus;
use App\Mail\OrderStatusMail;
use App\Models\ItemPedido;
use App\Models\Pagamento;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\User;
use App\Services\MercadoPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StatusEmailConfirmationTest extends TestCase
{
    use RefreshDatabase;

    // ----------------------------------------------------------------
    // atualizarStatusPedido — flag send_email
    // ----------------------------------------------------------------

    public function test_atualizar_status_com_send_email_envia_email(): void
    {
        Mail::fake();
        Http::fake();

        $admin = User::factory()->create(['admin' => true]);
        $pedido = Pedido::factory()->create([
            'status' => PedidoStatus::PAGO,
            'customer_email' => 'cliente@example.com',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.pedidos'))
            ->post(route('admin.pedidos.status', $pedido), [
                'status'     => PedidoStatus::ENVIADO,
                'codigo_rastreio' => 'BR123456789BR',
                'send_email' => '1',
            ])
            ->assertRedirect(route('admin.pedidos'));

        Mail::assertQueued(OrderStatusMail::class, function (OrderStatusMail $mail) use ($pedido) {
            return $mail->pedido->id === $pedido->id
                && $mail->eventType === PedidoStatus::ENVIADO
                && $mail->hasTo('cliente@example.com');
        });
    }

    public function test_atualizar_status_sem_send_email_nao_envia_email(): void
    {
        Mail::fake();
        Http::fake();

        $admin = User::factory()->create(['admin' => true]);
        $pedido = Pedido::factory()->create([
            'status' => PedidoStatus::PAGO,
            'customer_email' => 'cliente@example.com',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.pedidos'))
            ->post(route('admin.pedidos.status', $pedido), [
                'status' => PedidoStatus::ENVIADO,
                'codigo_rastreio' => 'BR123456789BR',
                // send_email ausente — checkbox desmarcado
            ])
            ->assertRedirect(route('admin.pedidos'));

        Mail::assertNothingQueued();
    }

    public function test_atualizar_status_com_send_email_zero_nao_envia_email(): void
    {
        Mail::fake();
        Http::fake();

        $admin = User::factory()->create(['admin' => true]);
        $pedido = Pedido::factory()->create([
            'status'         => PedidoStatus::PROCESSANDO,
            'customer_email' => 'cliente@example.com',
        ]);

        $produto = Produto::factory()->create(['custo_compra' => 50.00]);
        $item = ItemPedido::create([
            'pedido_id'  => $pedido->id,
            'produto_id' => $produto->id,
            'quantidade' => 1,
            'preco'      => 100.00,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.pedidos'))
            ->post(route('admin.pedidos.status', $pedido), [
                'status'     => PedidoStatus::ENVIADO,
                'codigo_rastreio' => 'BR123456789BR',
                'send_email' => '0',
            ])
            ->assertRedirect(route('admin.pedidos'));

        Mail::assertNothingQueued();
    }

    public function test_atualizar_status_para_nao_notificavel_com_send_email_nao_envia_email(): void
    {
        // "pendente" não está em notificationValues — send_email=1 não deve disparar
        Mail::fake();
        Http::fake();

        $admin = User::factory()->create(['admin' => true]);
        $pedido = Pedido::factory()->create([
            'status'         => PedidoStatus::CARRINHO,
            'customer_email' => 'cliente@example.com',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.pedidos'))
            ->post(route('admin.pedidos.status', $pedido), [
                'status'     => PedidoStatus::PENDENTE,
                'send_email' => '1',
            ])
            ->assertRedirect(route('admin.pedidos'));

        Mail::assertNothingQueued();
    }

    public function test_atualizar_status_envia_email_para_todos_status_notificaveis(): void
    {
        Mail::fake();
        Http::fake();

        $admin  = User::factory()->create(['admin' => true]);
        $pedido = Pedido::factory()->create([
            'status'         => PedidoStatus::PAGO,
            'customer_email' => 'cliente@example.com',
        ]);

        $produto = Produto::factory()->create(['custo_compra' => 10.00]);
        $item = ItemPedido::create([
            'pedido_id'  => $pedido->id,
            'produto_id' => $produto->id,
            'quantidade' => 1,
            'preco'      => 50.00,
        ]);

        $sequencia = [
            PedidoStatus::PROCESSANDO,
            PedidoStatus::ENVIADO,
            PedidoStatus::ENTREGUE,
            PedidoStatus::CANCELADO,
        ];

        foreach ($sequencia as $status) {
            $payload = ['status' => $status, 'send_email' => '1'];
            if ($status === PedidoStatus::ENVIADO) {
                $payload['codigo_rastreio'] = 'BR123456789BR';
            }
            if ($status === PedidoStatus::PROCESSANDO) {
                $payload['itens'] = [$item->id => ['custo_unitario_declarado' => '10,00']];
            }

            $this->actingAs($admin)
                ->from(route('admin.pedidos'))
                ->post(route('admin.pedidos.status', $pedido), $payload)
                ->assertRedirect(route('admin.pedidos'));

            $pedido->refresh();
        }

        foreach ($sequencia as $status) {
            Mail::assertQueued(OrderStatusMail::class, fn ($m) => $m->eventType === $status);
        }
    }

    // ----------------------------------------------------------------
    // quickStatusPedido — email sempre enviado
    // ----------------------------------------------------------------

    public function test_quick_status_sempre_envia_email(): void
    {
        Mail::fake();
        Http::fake();

        $admin  = User::factory()->create(['admin' => true]);
        $pedido = Pedido::factory()->create([
            'status'         => PedidoStatus::PAGO,
            'customer_email' => 'cliente@example.com',
        ]);

        $produto = Produto::factory()->create(['custo_compra' => 10.00]);
        ItemPedido::create([
            'pedido_id'  => $pedido->id,
            'produto_id' => $produto->id,
            'quantidade' => 1,
            'preco'      => 50.00,
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.pedidos.quick-status', $pedido), [
                'status'           => PedidoStatus::PROCESSANDO,
                'use_catalog_costs' => true,
            ])
            ->assertJson(['success' => true]);

        Mail::assertQueued(OrderStatusMail::class, function (OrderStatusMail $mail) use ($pedido) {
            return $mail->pedido->id === $pedido->id
                && $mail->eventType === PedidoStatus::PROCESSANDO
                && $mail->hasTo('cliente@example.com');
        });
    }

    public function test_quick_status_envia_email_para_enviado(): void
    {
        Mail::fake();
        Http::fake();

        $admin  = User::factory()->create(['admin' => true]);
        $pedido = Pedido::factory()->create([
            'status'         => PedidoStatus::PROCESSANDO,
            'customer_email' => 'cliente@example.com',
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.pedidos.quick-status', $pedido), [
                'status'          => PedidoStatus::ENVIADO,
                'codigo_rastreio' => 'BR123456789BR',
            ])
            ->assertJson(['success' => true]);

        Mail::assertQueued(OrderStatusMail::class, fn ($m) => $m->eventType === PedidoStatus::ENVIADO);
    }

    // ----------------------------------------------------------------
    // PedidoController — confirmarEntrega e cancelar
    // ----------------------------------------------------------------

    public function test_confirmar_entrega_envia_email_entregue(): void
    {
        Mail::fake();
        Http::fake();

        $user   = User::factory()->create(['email' => 'cliente@example.com']);
        $pedido = Pedido::factory()->create([
            'user_id'         => $user->id,
            'status'          => PedidoStatus::ENVIADO,
            'codigo_rastreio' => 'BR123456789BR',
        ]);

        $this->actingAs($user)
            ->post(route('site.pedidos.confirmar-entrega', $pedido));

        Mail::assertQueued(OrderStatusMail::class, function (OrderStatusMail $mail) use ($pedido) {
            return $mail->pedido->id === $pedido->id
                && $mail->eventType === PedidoStatus::ENTREGUE
                && $mail->hasTo('cliente@example.com');
        });
    }

    public function test_confirmar_entrega_guest_envia_email_para_customer_email(): void
    {
        Mail::fake();
        Http::fake();

        $pedido = Pedido::factory()->create([
            'user_id'         => null,
            'customer_email'  => 'guest@example.com',
            'status'          => PedidoStatus::ENVIADO,
            'guest_token'     => 'tok-abc',
            'codigo_rastreio' => 'BR999',
        ]);

        $this->post(route('site.pedidos.confirmar-entrega', $pedido), [
            'guest_token' => 'tok-abc',
        ]);

        Mail::assertQueued(OrderStatusMail::class, function (OrderStatusMail $mail) use ($pedido) {
            return $mail->pedido->id === $pedido->id
                && $mail->eventType === PedidoStatus::ENTREGUE
                && $mail->hasTo('guest@example.com');
        });
    }

    public function test_cancelar_pedido_envia_email_cancelado(): void
    {
        Mail::fake();
        Http::fake();

        $user   = User::factory()->create(['email' => 'cliente@example.com']);
        $pedido = Pedido::factory()->create([
            'user_id' => $user->id,
            'status'  => PedidoStatus::PENDENTE,
        ]);

        $this->actingAs($user)
            ->post(route('site.pedidos.cancelar', $pedido));

        Mail::assertQueued(OrderStatusMail::class, function (OrderStatusMail $mail) use ($pedido) {
            return $mail->pedido->id === $pedido->id
                && $mail->eventType === PedidoStatus::CANCELADO
                && $mail->hasTo('cliente@example.com');
        });
    }

    public function test_confirmar_entrega_guest_sem_customer_email_nao_falha(): void
    {
        // Guest order sem customer_email: service não encontra destinatário e não envia, sem exceção
        Mail::fake();

        $pedido = Pedido::factory()->create([
            'user_id'         => null,
            'customer_email'  => null,
            'status'          => PedidoStatus::ENVIADO,
            'guest_token'     => 'tok-sem-email',
            'codigo_rastreio' => 'BR123',
        ]);

        $this->post(route('site.pedidos.confirmar-entrega', $pedido), [
            'guest_token' => 'tok-sem-email',
        ])->assertRedirect();

        Mail::assertNothingQueued();
    }

    // ----------------------------------------------------------------
    // MercadoPago webhook — email sempre enviado
    // ----------------------------------------------------------------

    public function test_webhook_pago_envia_email_ao_cliente(): void
    {
        Mail::fake();
        Http::fake();

        config(['services.mercadopago.webhook_secret' => 'test_secret']);

        $pedido = Pedido::factory()->create([
            'status'         => PedidoStatus::PENDENTE,
            'customer_email' => 'comprador@example.com',
        ]);

        Pagamento::create([
            'pedido_id'          => $pedido->id,
            'metodo'             => 'pix',
            'gateway'            => 'mercado_pago',
            'gateway_payment_id' => '77777',
            'status'             => 'pendente',
            'valor'              => 199.90,
        ]);

        $this->mock(MercadoPagoService::class, function ($mock) use ($pedido): void {
            $mock->shouldReceive('getPayment')
                ->once()
                ->with('77777')
                ->andReturn([
                    'id'                 => 77777,
                    'status'             => 'approved',
                    'status_detail'      => 'accredited',
                    'transaction_amount' => 199.90,
                    'payment_method_id'  => 'pix',
                    'payment_type_id'    => 'pix',
                    'external_reference' => (string) $pedido->id,
                ]);
        });

        $timestamp = '1704908010';
        $requestId = 'req-email-test';
        $manifest  = sprintf('id:%s;request-id:%s;ts:%s;', '77777', $requestId, $timestamp);
        $signature = hash_hmac('sha256', $manifest, 'test_secret');

        $this->postJson(
            route('site.checkout.mercadopago.webhook', ['data.id' => '77777', 'type' => 'payment']),
            ['type' => 'payment', 'data' => ['id' => '77777']],
            ['X-Signature' => "ts={$timestamp},v1={$signature}", 'X-Request-Id' => $requestId],
        )->assertOk();

        Mail::assertQueued(OrderStatusMail::class, function (OrderStatusMail $mail) use ($pedido) {
            return $mail->pedido->id === $pedido->id
                && $mail->eventType === PedidoStatus::PAGO
                && $mail->hasTo('comprador@example.com');
        });
    }
}
