<?php
namespace Tests\Feature;

use App\Models\Affiliate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliadoControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_painel_redirects_unauthenticated_to_login(): void
    {
        $this->get(route('afiliados.painel'))
             ->assertRedirect(route('site.login'));
    }

    public function test_painel_redirects_to_solicitar_when_no_affiliate(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
             ->get(route('afiliados.painel'))
             ->assertRedirect(route('afiliados.solicitar'));
    }

    public function test_painel_shows_pending_message_when_status_pendente(): void
    {
        $user = User::factory()->create();
        Affiliate::factory()->create(['user_id' => $user->id, 'status' => 'pendente']);

        $this->actingAs($user)
             ->get(route('afiliados.painel'))
             ->assertOk()
             ->assertSee('análise');
    }

    public function test_painel_shows_dashboard_when_ativo(): void
    {
        $user = User::factory()->create();
        Affiliate::factory()->create(['user_id' => $user->id, 'status' => 'ativo']);

        $this->actingAs($user)
             ->get(route('afiliados.painel'))
             ->assertOk()
             ->assertSee('Link de Indica');
    }

    public function test_solicitar_form_shows_for_non_affiliate(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
             ->get(route('afiliados.solicitar'))
             ->assertOk();
    }

    public function test_registrar_creates_affiliate_and_redirects(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->post(route('afiliados.registrar'), ['pix_key' => 'meu@pix.com'])
             ->assertRedirect(route('afiliados.painel'));

        $this->assertDatabaseHas('affiliates', [
            'user_id' => $user->id,
            'status'  => 'pendente',
        ]);
    }

    public function test_registrar_prevents_duplicate_affiliate(): void
    {
        $user = User::factory()->create();
        Affiliate::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
             ->post(route('afiliados.registrar'), [])
             ->assertRedirect(route('afiliados.painel'));

        $this->assertDatabaseCount('affiliates', 1);
    }

    public function test_indicacoes_returns_ok_for_active_affiliate(): void
    {
        $user = User::factory()->create();
        Affiliate::factory()->create(['user_id' => $user->id, 'status' => 'ativo']);

        $this->actingAs($user)
             ->get(route('afiliados.indicacoes'))
             ->assertOk();
    }

    public function test_comissoes_returns_ok_for_active_affiliate(): void
    {
        $user = User::factory()->create();
        Affiliate::factory()->create(['user_id' => $user->id, 'status' => 'ativo']);

        $this->actingAs($user)
             ->get(route('afiliados.comissoes'))
             ->assertOk();
    }
}
