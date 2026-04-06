<?php
// tests/Feature/AdminAfiliadoControllerTest.php
namespace Tests\Feature;

use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\AffiliateReferral;
use App\Models\AffiliateSetting;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAfiliadoControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['admin' => true]);
    }

    public function test_index_requires_auth(): void
    {
        $this->get(route('admin.afiliados.index'))
             ->assertRedirect(route('site.login'));
    }

    public function test_index_returns_ok_for_authenticated_user(): void
    {
        $this->actingAs($this->admin)
             ->get(route('admin.afiliados.index'))
             ->assertOk();
    }

    public function test_show_returns_json_for_affiliate(): void
    {
        $affiliate = Affiliate::factory()->create();

        $this->actingAs($this->admin)
             ->getJson(route('admin.afiliados.show', $affiliate->id))
             ->assertOk()
             ->assertJsonPath('id', $affiliate->id);
    }

    public function test_aprovar_sets_status_to_ativo(): void
    {
        $affiliate = Affiliate::factory()->create(['status' => 'pendente', 'approved_at' => null]);

        $this->actingAs($this->admin)
             ->post(route('admin.afiliados.aprovar', $affiliate->id))
             ->assertRedirect();

        $this->assertDatabaseHas('affiliates', [
            'id'     => $affiliate->id,
            'status' => 'ativo',
        ]);
        $this->assertNotNull($affiliate->fresh()->approved_at);
    }

    public function test_suspender_sets_status_to_inativo(): void
    {
        $affiliate = Affiliate::factory()->create(['status' => 'ativo']);

        $this->actingAs($this->admin)
             ->post(route('admin.afiliados.suspender', $affiliate->id))
             ->assertRedirect();

        $this->assertDatabaseHas('affiliates', [
            'id'     => $affiliate->id,
            'status' => 'inativo',
        ]);
    }

    public function test_editar_comissao_updates_commission(): void
    {
        $affiliate = Affiliate::factory()->create();

        $this->actingAs($this->admin)
             ->post(route('admin.afiliados.comissao', $affiliate->id), [
                 'commission_type'  => 'percent',
                 'commission_value' => '8.50',
             ])
             ->assertRedirect();

        $this->assertDatabaseHas('affiliates', [
            'id'               => $affiliate->id,
            'commission_type'  => 'percent',
            'commission_value' => 8.50,
        ]);
    }

    public function test_comissoes_index_returns_ok(): void
    {
        $this->actingAs($this->admin)
             ->get(route('admin.afiliados.comissoes'))
             ->assertOk();
    }

    public function test_bulk_comissoes_aprovar_changes_status(): void
    {
        $affiliateUser = User::factory()->create();
        $affiliate = Affiliate::factory()->create(['user_id' => $affiliateUser->id]);
        $buyer = User::factory()->create();
        $referral = AffiliateReferral::create([
            'affiliate_id' => $affiliate->id, 'referred_user_id' => $buyer->id, 'status' => 'convertido',
        ]);
        $pedido = Pedido::create([
            'user_id' => $buyer->id, 'status' => 'pago', 'valor_total' => 100.00,
            'frete_tipo' => 'pac', 'frete_valor' => 0,
        ]);
        $commission = AffiliateCommission::create([
            'affiliate_id' => $affiliate->id,
            'referral_id'  => $referral->id,
            'pedido_id'    => $pedido->id,
            'valor'        => 5.00,
            'status'       => 'pendente',
            'eligible_at'  => now()->subDay(),
        ]);

        $this->actingAs($this->admin)
             ->post(route('admin.afiliados.comissoes.bulk'), [
                 'ids'    => [$commission->id],
                 'action' => 'aprovar',
             ])
             ->assertRedirect();

        $this->assertDatabaseHas('affiliate_commissions', [
            'id'     => $commission->id,
            'status' => 'aprovado',
        ]);
    }

    public function test_bulk_comissoes_pago_changes_status(): void
    {
        $affiliateUser = User::factory()->create();
        $affiliate = Affiliate::factory()->create(['user_id' => $affiliateUser->id]);
        $buyer = User::factory()->create();
        $referral = AffiliateReferral::create([
            'affiliate_id' => $affiliate->id, 'referred_user_id' => $buyer->id, 'status' => 'convertido',
        ]);
        $pedido = Pedido::create([
            'user_id' => $buyer->id, 'status' => 'pago', 'valor_total' => 100.00,
            'frete_tipo' => 'pac', 'frete_valor' => 0,
        ]);
        $commission = AffiliateCommission::create([
            'affiliate_id' => $affiliate->id,
            'referral_id'  => $referral->id,
            'pedido_id'    => $pedido->id,
            'valor'        => 5.00,
            'status'       => 'aprovado',
            'eligible_at'  => now()->subDay(),
        ]);

        $this->actingAs($this->admin)
             ->post(route('admin.afiliados.comissoes.bulk'), [
                 'ids'    => [$commission->id],
                 'action' => 'marcar_pago',
             ])
             ->assertRedirect();

        $this->assertDatabaseHas('affiliate_commissions', [
            'id'     => $commission->id,
            'status' => 'pago',
        ]);
        $this->assertNotNull($commission->fresh()->paid_at);
    }

    public function test_configuracoes_returns_ok(): void
    {
        AffiliateSetting::create(['key' => 'commission_percent_default', 'value' => '5.00']);
        AffiliateSetting::create(['key' => 'cookie_days', 'value' => '30']);
        AffiliateSetting::create(['key' => 'grace_period_days', 'value' => '30']);

        $this->actingAs($this->admin)
             ->get(route('admin.afiliados.configuracoes'))
             ->assertOk();
    }

    public function test_salvar_configuracoes_updates_settings(): void
    {
        AffiliateSetting::create(['key' => 'commission_percent_default', 'value' => '5.00']);
        AffiliateSetting::create(['key' => 'cookie_days', 'value' => '30']);
        AffiliateSetting::create(['key' => 'grace_period_days', 'value' => '30']);

        $this->actingAs($this->admin)
             ->post(route('admin.afiliados.configuracoes.salvar'), [
                 'commission_percent_default' => '8.00',
                 'cookie_days'               => '60',
                 'grace_period_days'         => '15',
             ])
             ->assertRedirect();

        $this->assertDatabaseHas('affiliate_settings', ['key' => 'commission_percent_default', 'value' => '8.00']);
        $this->assertDatabaseHas('affiliate_settings', ['key' => 'cookie_days', 'value' => '60']);
        $this->assertDatabaseHas('affiliate_settings', ['key' => 'grace_period_days', 'value' => '15']);
    }
}
