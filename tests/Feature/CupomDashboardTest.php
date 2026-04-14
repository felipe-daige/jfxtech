<?php

namespace Tests\Feature;

use App\Models\Cupom;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CupomDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::factory()->create(['coupon_portal_enabled' => true]);
    }

    private function makeCupom(User $user, string $codigo = 'PARCEIRO10'): Cupom
    {
        return Cupom::create([
            'user_id' => $user->id,
            'codigo'  => $codigo,
            'tipo'    => 'percentual',
            'valor'   => 10,
            'ativo'   => true,
        ]);
    }

    private function makePaidOrder(string $codigo, float $total, float $desconto): Pedido
    {
        return Pedido::create([
            'user_id'        => User::factory()->create()->id,
            'status'         => 'pago',
            'cupom_codigo'   => $codigo,
            'valor_total'    => $total,
            'valor_desconto' => $desconto,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function dashboard_passes_correct_metrics_to_view(): void
    {
        $user = $this->makeUser();
        $cupom = $this->makeCupom($user, 'PARCEIRO10');

        // 3 pedidos pagos: valor_total já é o líquido cobrado
        $this->makePaidOrder('PARCEIRO10', 100.00, 10.00);
        $this->makePaidOrder('PARCEIRO10', 200.00, 20.00);
        $this->makePaidOrder('PARCEIRO10', 150.00, 15.00);

        // totalLiquido = sum(valor_total) = 100+200+150 = 450
        // taxa tier = 5% (3 vendas → tier 0-14)
        // comissão = 450 * 0.05 = 22.5
        // média = 450 / 3 = 150

        $response = $this->actingAs($user)->get('/cupom');

        $response->assertStatus(200);
        $response->assertViewHas('totalLiquido', 450.00);
        $response->assertViewHas('comissaoAcumulada', 22.5);
        $response->assertViewHas('mediaPorPedido', 150.00);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function dashboard_returns_all_sales_not_limited_to_ten(): void
    {
        $user = $this->makeUser();
        $this->makeCupom($user, 'BIG20');

        // criar 15 pedidos pagos
        foreach (range(1, 15) as $_) {
            $this->makePaidOrder('BIG20', 50.00, 5.00);
        }

        $response = $this->actingAs($user)->get('/cupom');

        $response->assertStatus(200);
        $response->assertViewHas('allSales', fn($s) => $s->count() === 15);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function dashboard_computes_progress_bar_percentage(): void
    {
        $user = $this->makeUser();
        $this->makeCupom($user, 'TIER2');

        // criar 20 vendas → tier 15-29 → progressPct = round((20-15)/(29-15)*100) = 36
        foreach (range(1, 20) as $_) {
            $this->makePaidOrder('TIER2', 100.00, 0.00);
        }

        $response = $this->actingAs($user)->get('/cupom');

        $response->assertStatus(200);
        $response->assertViewHas('progressPct', 36);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function dashboard_redirects_if_portal_not_enabled(): void
    {
        $user = User::factory()->create(['coupon_portal_enabled' => false]);

        $response = $this->actingAs($user)->get('/cupom');

        $response->assertRedirect(route('site.perfil'));
    }
}
