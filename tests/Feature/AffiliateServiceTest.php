<?php
namespace Tests\Feature;

use App\Models\Affiliate;
use App\Models\AffiliateReferral;
use App\Models\AffiliateSetting;
use App\Services\AffiliateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliateServiceTest extends TestCase
{
    use RefreshDatabase;

    private AffiliateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AffiliateService();
    }

    public function test_get_setting_returns_default_when_missing(): void
    {
        $value = $this->service->getSetting('commission_percent_default', '5.00');
        $this->assertEquals('5.00', $value);
    }

    public function test_get_setting_returns_db_value(): void
    {
        AffiliateSetting::create(['key' => 'cookie_days', 'value' => '45']);
        $value = $this->service->getSetting('cookie_days', '30');
        $this->assertEquals('45', $value);
    }

    public function test_generate_unique_code_is_8_uppercase_alphanumeric(): void
    {
        $code = $this->service->generateUniqueCode();
        $this->assertEquals(8, strlen($code));
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{8}$/', $code);
    }

    public function test_generate_unique_code_does_not_collide_with_existing(): void
    {
        Affiliate::factory()->create(['codigo' => 'AAAAAAAA']);
        for ($i = 0; $i < 5; $i++) {
            $code = $this->service->generateUniqueCode();
            $this->assertNotEquals('AAAAAAAA', $code);
        }
    }

    public function test_calculate_commission_percent_type(): void
    {
        AffiliateSetting::create(['key' => 'commission_percent_default', 'value' => '5.00']);
        $user = \App\Models\User::factory()->create();
        $affiliate = Affiliate::factory()->create([
            'user_id'          => $user->id,
            'commission_type'  => 'percent',
            'commission_value' => 10.00,
        ]);
        $pedido = \App\Models\Pedido::create([
            'user_id' => $user->id, 'status' => 'pago',
            'valor_total' => 200.00, 'frete_tipo' => 'pac', 'frete_valor' => 0,
        ]);

        $valor = $this->service->calculateCommission($affiliate, $pedido);

        $this->assertEquals(20.00, $valor); // 10% of 200
    }

    public function test_calculate_commission_fixed_type(): void
    {
        $user = \App\Models\User::factory()->create();
        $affiliate = Affiliate::factory()->create([
            'user_id'          => $user->id,
            'commission_type'  => 'fixed',
            'commission_value' => 15.00,
        ]);
        $pedido = \App\Models\Pedido::create([
            'user_id' => $user->id, 'status' => 'pago',
            'valor_total' => 200.00, 'frete_tipo' => 'pac', 'frete_valor' => 0,
        ]);

        $valor = $this->service->calculateCommission($affiliate, $pedido);

        $this->assertEquals(15.00, $valor); // flat R$ 15
    }

    public function test_calculate_commission_uses_global_when_value_null(): void
    {
        AffiliateSetting::create(['key' => 'commission_percent_default', 'value' => '5.00']);
        $user = \App\Models\User::factory()->create();
        $affiliate = Affiliate::factory()->create([
            'user_id'          => $user->id,
            'commission_type'  => 'percent',
            'commission_value' => null,
        ]);
        $pedido = \App\Models\Pedido::create([
            'user_id' => $user->id, 'status' => 'pago',
            'valor_total' => 100.00, 'frete_tipo' => 'pac', 'frete_valor' => 0,
        ]);

        $valor = $this->service->calculateCommission($affiliate, $pedido);

        $this->assertEquals(5.00, $valor); // 5% of 100
    }

    public function test_record_referral_creates_pending_referral(): void
    {
        $affiliateUser = \App\Models\User::factory()->create();
        $affiliate = Affiliate::factory()->create(['user_id' => $affiliateUser->id, 'status' => 'ativo']);
        $newUser = \App\Models\User::factory()->create();

        $request = \Illuminate\Http\Request::create('/');
        $request->cookies->set(AffiliateService::COOKIE_NAME, $affiliate->codigo);
        app()->instance('request', $request);

        $this->service->recordReferralOnRegister($newUser);

        $this->assertDatabaseHas('affiliate_referrals', [
            'affiliate_id'     => $affiliate->id,
            'referred_user_id' => $newUser->id,
            'status'           => 'pendente',
        ]);
    }

    public function test_record_referral_ignores_empty_cookie(): void
    {
        $newUser = \App\Models\User::factory()->create();
        $request = \Illuminate\Http\Request::create('/');
        app()->instance('request', $request);

        $this->service->recordReferralOnRegister($newUser);

        $this->assertDatabaseCount('affiliate_referrals', 0);
    }

    public function test_record_referral_ignores_self_referral(): void
    {
        $user = \App\Models\User::factory()->create();
        $affiliate = Affiliate::factory()->create(['user_id' => $user->id, 'status' => 'ativo']);

        $request = \Illuminate\Http\Request::create('/');
        $request->cookies->set(AffiliateService::COOKIE_NAME, $affiliate->codigo);
        app()->instance('request', $request);

        $this->service->recordReferralOnRegister($user);

        $this->assertDatabaseCount('affiliate_referrals', 0);
    }

    public function test_record_referral_ignores_invalid_code(): void
    {
        $newUser = \App\Models\User::factory()->create();
        $request = \Illuminate\Http\Request::create('/');
        $request->cookies->set(AffiliateService::COOKIE_NAME, 'INVALID1');
        app()->instance('request', $request);

        $this->service->recordReferralOnRegister($newUser);

        $this->assertDatabaseCount('affiliate_referrals', 0);
    }
}
