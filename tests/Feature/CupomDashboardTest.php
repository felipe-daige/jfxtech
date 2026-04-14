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

    /** @test */
    public function dashboard_passes_correct_metrics_to_view(): void
    {
        $user = $this->makeUser();
        $cupom = $this->makeCupom($user, 'PARCEIRO10');

        // 3 pedidos pagos: total=100 desconto=10 → líquido=90 cada
        $this->makePaidOrder('PARCEIRO10', 100.00, 10.00);
        $this->makePaidOrder('PARCEIRO10', 200.00, 20.00);
        $this->makePaidOrder('PARCEIRO10', 150.00, 15.00);

        // líquido total = 90+180+135 = 405
        // taxa tier = 5% (3 vendas → tier 0-14)
        // comissão = 405 * 0.05 = 20.25
        // média = 405 / 3 = 135

        $response = $this->actingAs($user)->get('/cupom');

        $response->assertStatus(200);
        $response->assertViewHas('totalLiquido', 405.00);
        $response->assertViewHas('comissaoAcumulada', 20.25);
        $response->assertViewHas('mediaPorPedido', 135.00);
    }

    /** @test */
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

    /** @test */
    public function dashboard_computes_progress_bar_percentage(): void
    {
        $user = $this->makeUser();
        $this->makeCupom($user, 'TIER2');

        // criar 20 vendas → tier 15-29 → progressPct = (20-15)/(29-15)*100 ≈ 36
        foreach (range(1, 20) as $_) {
            $this->makePaidOrder('TIER2', 100.00, 0.00);
        }

        $response = $this->actingAs($user)->get('/cupom');

        $response->assertStatus(200);
        // dentro do range 0–100
        $response->assertViewHas('progressPct', fn($v) => $v >= 0 && $v <= 100);
    }

    /** @test */
    public function dashboard_redirects_if_portal_not_enabled(): void
    {
        $user = User::factory()->create(['coupon_portal_enabled' => false]);

        $response = $this->actingAs($user)->get('/cupom');

        $response->assertRedirect(route('site.perfil'));
    }
}
