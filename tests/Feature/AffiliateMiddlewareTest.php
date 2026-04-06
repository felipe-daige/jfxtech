<?php
namespace Tests\Feature;

use App\Models\Affiliate;
use App\Models\AffiliateSetting;
use App\Services\AffiliateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliateMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_middleware_sets_cookie_when_ref_is_valid_active_affiliate(): void
    {
        AffiliateSetting::create(['key' => 'cookie_days', 'value' => '30']);
        Affiliate::factory()->create(['status' => 'ativo', 'codigo' => 'TESTCODE']);

        $response = $this->get('/?ref=TESTCODE');

        $response->assertCookie(AffiliateService::COOKIE_NAME, 'TESTCODE');
    }

    public function test_middleware_ignores_invalid_code(): void
    {
        $response = $this->get('/?ref=BADCODE1');

        $response->assertCookieMissing(AffiliateService::COOKIE_NAME);
    }

    public function test_middleware_ignores_inactive_affiliate(): void
    {
        Affiliate::factory()->create(['status' => 'inativo', 'codigo' => 'INACTIVE']);

        $response = $this->get('/?ref=INACTIVE');

        $response->assertCookieMissing(AffiliateService::COOKIE_NAME);
    }

    public function test_middleware_ignores_request_without_ref(): void
    {
        $response = $this->get('/');

        $response->assertCookieMissing(AffiliateService::COOKIE_NAME);
    }
}
