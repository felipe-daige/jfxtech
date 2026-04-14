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
            ->assertSee('Meu Cupom')
            ->assertSee('PARTNER10')
            ->assertSee('5%');
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

    public function test_portal_counts_only_paid_orders_and_aggregates_multiple_coupons(): void
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

        Pedido::factory()->create(['status' => 'pago', 'cupom_codigo' => 'ALPHA10']);
        Pedido::factory()->create(['status' => 'pago', 'cupom_codigo' => 'BETA10']);
        Pedido::factory()->create(['status' => 'pendente', 'cupom_codigo' => 'ALPHA10']);
        Pedido::factory()->create(['status' => 'cancelado', 'cupom_codigo' => 'BETA10']);

        $this->actingAs($user)
            ->get(route('site.cupom'))
            ->assertOk()
            ->assertSee('ALPHA10')
            ->assertSee('BETA10')
            ->assertSee('2');
    }

    public function test_progress_service_returns_expected_rates_for_thresholds(): void
    {
        $service = app(CouponPartnerProgressService::class);
        $user = User::factory()->create(['coupon_portal_enabled' => true]);

        Cupom::create([
            'codigo' => 'TIER10',
            'user_id' => $user->id,
            'tipo' => 'percentual',
            'valor' => 10,
            'ativo' => true,
        ]);

        $this->seedPaidOrdersForCoupon('TIER10', 14);
        $this->assertSame(5, $service->progressForUser($user)['current_rate']);

        $this->seedPaidOrdersForCoupon('TIER10', 1, 1000);
        $this->assertSame(6, $service->progressForUser($user)['current_rate']);

        $this->seedPaidOrdersForCoupon('TIER10', 15, 2000);
        $this->assertSame(7, $service->progressForUser($user)['current_rate']);

        $this->seedPaidOrdersForCoupon('TIER10', 30, 3000);
        $this->assertSame(8, $service->progressForUser($user)['current_rate']);
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
