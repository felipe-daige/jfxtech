<?php

namespace Tests\Feature;

use App\Models\Cupom;
use App\Models\Pedido;
use App\Models\User;
use App\Services\CouponPartnerProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_with_coupon_can_access_portal(): void
    {
        $user = User::factory()->create(['coupon_portal_enabled' => true]);
        Cupom::create([
            'codigo' => 'PARTNER10',
            'user_id' => $user->id,
            'tipo' => 'percentual',
            'valor' => 10,
            'ativo' => true,
        ]);

        $this->actingAs($user)
            ->get(route('site.cupom'))
            ->assertOk()
            ->assertSee('Meus Cupons')
            ->assertSee('PARTNER10')
            ->assertSee('5%')
            ->assertSee(url('/?cupom=PARTNER10'), false);
    }

    public function test_user_without_portal_access_is_redirected_to_profile(): void
    {
        $user = User::factory()->create(['coupon_portal_enabled' => false]);
        Cupom::create([
            'codigo' => 'BLOCKED10',
            'user_id' => $user->id,
            'tipo' => 'percentual',
            'valor' => 10,
            'ativo' => true,
        ]);

        $this->actingAs($user)
            ->get(route('site.cupom'))
            ->assertRedirect(route('site.perfil'));
    }

    public function test_user_with_access_but_without_coupon_is_redirected_to_profile(): void
    {
        $user = User::factory()->create(['coupon_portal_enabled' => true]);

        $this->actingAs($user)
            ->get(route('site.cupom'))
            ->assertRedirect(route('site.perfil'));
    }

    public function test_portal_tracks_paid_progress_per_coupon_without_mixing_codes(): void
    {
        $user = User::factory()->create(['coupon_portal_enabled' => true]);

        Cupom::create([
            'codigo' => 'ALPHA10',
            'user_id' => $user->id,
            'tipo' => 'percentual',
            'valor' => 10,
            'ativo' => true,
        ]);
        Cupom::create([
            'codigo' => 'BETA10',
            'user_id' => $user->id,
            'tipo' => 'percentual',
            'valor' => 10,
            'ativo' => true,
        ]);

        $this->seedPaidOrdersForCoupon('ALPHA10', 3);
        $this->seedPaidOrdersForCoupon('BETA10', 20, 1000);
        Pedido::factory()->create(['status' => 'pendente', 'cupom_codigo' => 'ALPHA10']);
        Pedido::factory()->create(['status' => 'cancelado', 'cupom_codigo' => 'BETA10']);

        $this->actingAs($user)
            ->get(route('site.cupom'))
            ->assertOk()
            ->assertViewHas('couponProgress', function ($couponProgress) {
                return $couponProgress->get('ALPHA10')['total_sales'] === 3
                    && $couponProgress->get('ALPHA10')['current_rate'] === 5
                    && $couponProgress->get('ALPHA10')['next_threshold'] === 15
                    && $couponProgress->get('ALPHA10')['sales_to_next'] === 12
                    && $couponProgress->get('BETA10')['total_sales'] === 20
                    && $couponProgress->get('BETA10')['current_rate'] === 6
                    && $couponProgress->get('BETA10')['next_threshold'] === 30
                    && $couponProgress->get('BETA10')['sales_to_next'] === 10;
            })
            ->assertViewHas('progress', function ($progress) {
                return $progress['coupon_code'] === 'ALPHA10'
                    && $progress['total_sales'] === 3
                    && $progress['current_rate'] === 5
                    && $progress['next_threshold'] === 15
                    && $progress['sales_to_next'] === 12;
            })
            ->assertViewHas('progressAggregate', function ($progress) {
                return $progress['total_sales'] === 23
                    && $progress['current_rate'] === 6;
            })
            ->assertSee('ALPHA10')
            ->assertSee('BETA10')
            ->assertSee('3 / 15 vendas')
            ->assertSee('faltam 12');

        $this->actingAs($user)
            ->get(route('site.cupom', ['cupom' => 'BETA10']))
            ->assertOk()
            ->assertViewHas('progress', function ($progress) {
                return $progress['coupon_code'] === 'BETA10'
                    && $progress['total_sales'] === 20
                    && $progress['current_rate'] === 6
                    && $progress['next_threshold'] === 30
                    && $progress['sales_to_next'] === 10;
            })
            ->assertSee('20 / 30 vendas')
            ->assertSee('faltam 10');
    }

    public function test_progress_service_returns_expected_rates_and_percentages_for_thresholds(): void
    {
        $service = app(CouponPartnerProgressService::class);

        Pedido::factory()->create(['status' => 'pendente', 'cupom_codigo' => 'ZERO10']);
        Pedido::factory()->create(['status' => 'cancelado', 'cupom_codigo' => 'ZERO10']);

        $cases = [
            ['ZERO10', 0, 5, 0],
            ['FOURTEEN10', 14, 5, 100],
            ['FIFTEEN10', 15, 6, 0],
            ['TWENTY10', 20, 6, 36],
            ['SIXTY10', 60, 8, 100],
        ];

        foreach ($cases as $index => [$code, $paidOrders, $expectedRate, $expectedPct]) {
            $this->seedPaidOrdersForCoupon($code, $paidOrders, ($index + 1) * 1000);
            $progress = $service->progressForCouponCode($code);

            $this->assertSame($paidOrders, $progress['total_sales']);
            $this->assertSame($expectedRate, $progress['current_rate']);
            $this->assertSame($expectedPct, $progress['progress_pct']);
        }
    }

    public function test_progress_counts_paid_lifecycle_statuses_without_counting_pending_or_cancelled(): void
    {
        $service = app(CouponPartnerProgressService::class);

        foreach (['pago', 'processando', 'enviado', 'entregue'] as $index => $status) {
            Pedido::factory()->create([
                'status' => $status,
                'cupom_codigo' => 'LIFECYCLE10',
                'guest_token' => 'coupon-lifecycle-' . $index,
            ]);
        }

        Pedido::factory()->create(['status' => 'pendente', 'cupom_codigo' => 'LIFECYCLE10']);
        Pedido::factory()->create(['status' => 'cancelado', 'cupom_codigo' => 'LIFECYCLE10']);

        $progress = $service->progressForCouponCode('LIFECYCLE10');

        $this->assertSame(4, $progress['total_sales']);
        $this->assertSame(29, $progress['progress_pct']);
        $this->assertSame(11, $progress['sales_to_next']);
    }

    private function seedPaidOrdersForCoupon(string $codigo, int $count, int $offset = 0): void
    {
        for ($i = 0; $i < $count; $i++) {
            Pedido::factory()->create([
                'status' => 'pago',
                'cupom_codigo' => $codigo,
                'guest_token' => 'coupon-portal-' . ($offset + $i),
            ]);
        }
    }
}
