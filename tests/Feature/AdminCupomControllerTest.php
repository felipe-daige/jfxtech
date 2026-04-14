<?php

namespace Tests\Feature;

use App\Models\Cupom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCupomControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['admin' => true]);
    }

    public function test_store_can_create_coupon_with_linked_user(): void
    {
        $streamer = User::factory()->create();

        $this->actingAs($this->admin)
            ->postJson(route('admin.cupons.store'), [
                'codigo' => 'STREAM10',
                'user_id' => $streamer->id,
                'coupon_portal_enabled' => true,
                'tipo' => 'percentual',
                'valor' => 10,
                'ativo' => true,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('cupom.user.id', $streamer->id)
            ->assertJsonPath('cupom.user.coupon_portal_enabled', true);

        $this->assertDatabaseHas('cupons', [
            'codigo' => 'STREAM10',
            'user_id' => $streamer->id,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $streamer->id,
            'coupon_portal_enabled' => true,
        ]);
    }

    public function test_update_can_change_linked_user(): void
    {
        $streamerAtual = User::factory()->create();
        $novoStreamer = User::factory()->create();
        $cupom = Cupom::create([
            'codigo' => 'LIVE20',
            'user_id' => $streamerAtual->id,
            'tipo' => 'fixo',
            'valor' => 20,
            'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->putJson(route('admin.cupons.update', $cupom->id), [
                'codigo' => 'LIVE20',
                'user_id' => $novoStreamer->id,
                'coupon_portal_enabled' => true,
                'tipo' => 'fixo',
                'valor' => 20,
                'ativo' => true,
            ])
            ->assertOk()
            ->assertJsonPath('cupom.user.id', $novoStreamer->id)
            ->assertJsonPath('cupom.user.coupon_portal_enabled', true);

        $this->assertDatabaseHas('cupons', [
            'id' => $cupom->id,
            'user_id' => $novoStreamer->id,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $novoStreamer->id,
            'coupon_portal_enabled' => true,
        ]);
    }

    public function test_update_can_remove_linked_user(): void
    {
        $streamer = User::factory()->create(['coupon_portal_enabled' => true]);
        $cupom = Cupom::create([
            'codigo' => 'REMOVE1',
            'user_id' => $streamer->id,
            'tipo' => 'percentual',
            'valor' => 15,
            'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->putJson(route('admin.cupons.update', $cupom->id), [
                'codigo' => 'REMOVE1',
                'user_id' => '',
                'tipo' => 'percentual',
                'valor' => 15,
                'ativo' => true,
            ])
            ->assertOk()
            ->assertJsonPath('cupom.user', null);

        $this->assertDatabaseHas('cupons', [
            'id' => $cupom->id,
            'user_id' => null,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $streamer->id,
            'coupon_portal_enabled' => false,
        ]);
    }

    public function test_store_rejects_invalid_user_id(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.cupons.store'), [
                'codigo' => 'BADUSER',
                'user_id' => 999999,
                'tipo' => 'percentual',
                'valor' => 10,
                'ativo' => true,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('user_id');
    }

    public function test_buscar_usuarios_returns_matching_users(): void
    {
        $streamer = User::factory()->create([
            'name' => 'Streamer Alpha',
            'email' => 'streamer@example.com',
        ]);
        User::factory()->create([
            'name' => 'Pessoa Beta',
            'email' => 'beta@example.com',
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('admin.cupons.buscarUsuarios', ['q' => 'stream']))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $streamer->id,
                'name' => 'Streamer Alpha',
                'email' => 'streamer@example.com',
            ]);
    }

    public function test_deleting_linked_user_sets_coupon_user_id_to_null(): void
    {
        $streamer = User::factory()->create();
        $cupom = Cupom::create([
            'codigo' => 'NULLDEL',
            'user_id' => $streamer->id,
            'tipo' => 'percentual',
            'valor' => 5,
            'ativo' => true,
        ]);

        $streamer->delete();

        $this->assertDatabaseHas('cupons', [
            'id' => $cupom->id,
            'user_id' => null,
        ]);
    }

    public function test_destroy_disables_portal_when_user_has_no_other_coupons(): void
    {
        $streamer = User::factory()->create(['coupon_portal_enabled' => true]);
        $cupom = Cupom::create([
            'codigo' => 'DELETE1',
            'user_id' => $streamer->id,
            'tipo' => 'percentual',
            'valor' => 5,
            'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->deleteJson(route('admin.cupons.destroy', $cupom->id))
            ->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $streamer->id,
            'coupon_portal_enabled' => false,
        ]);
    }
}
