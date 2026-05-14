<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderCouponFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_orders_by_coupon_code(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $matching = Pedido::factory()->create(['cupom_codigo' => 'ALPHA10']);
        $other = Pedido::factory()->create(['cupom_codigo' => 'BETA10']);
        $withoutCoupon = Pedido::factory()->create(['cupom_codigo' => null]);

        $response = $this->actingAs($admin)
            ->get(route('admin.pedidos', ['cupom' => 'alpha10']));

        $response->assertOk()
            ->assertViewHas('couponFilter', 'ALPHA10')
            ->assertViewHas('pedidos', function ($pedidos) use ($matching, $other, $withoutCoupon) {
                $ids = $pedidos->pluck('id');

                return $ids->contains($matching->id)
                    && ! $ids->contains($other->id)
                    && ! $ids->contains($withoutCoupon->id);
            });
    }
}
