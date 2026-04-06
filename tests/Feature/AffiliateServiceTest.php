<?php
namespace Tests\Feature;

use App\Models\Affiliate;
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
}
