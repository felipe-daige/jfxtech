<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_users_page_requires_authentication(): void
    {
        $this->get(route('admin.usuarios.index'))
            ->assertRedirect(route('site.login'));
    }

    public function test_non_admin_cannot_access_admin_users_page(): void
    {
        $user = User::factory()->create(['admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.usuarios.index'))
            ->assertForbidden();
    }

    public function test_admin_can_list_users_with_order_summary(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $cliente = User::factory()->create([
            'name' => 'Victor Medeiros',
            'email' => 'victor.gremio83@gmail.com',
            'phone' => '(51) 99746-3222',
            'cpf' => '03312669030',
        ]);

        Pedido::factory()->create([
            'user_id' => $cliente->id,
            'status' => 'pago',
            'valor_total' => 199.90,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.usuarios.index', ['q' => 'victor']))
            ->assertOk()
            ->assertSee('Victor Medeiros')
            ->assertSee('victor.gremio83@gmail.com')
            ->assertSee('(51) 99746-3222')
            ->assertSee('03312669030');
    }

    public function test_admin_can_update_basic_user_fields(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $user = User::factory()->create([
            'name' => 'Cliente Inicial',
            'phone' => '(11) 99999-1111',
            'cpf' => null,
            'admin' => false,
            'coupon_portal_enabled' => false,
            'must_change_password' => false,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.usuarios.index'))
            ->put(route('admin.usuarios.update', $user->id), [
                'name' => 'Victor Medeiros',
                'phone' => '(51) 99746-3222',
                'cpf' => '033.126.690-30',
                'admin' => '1',
                'coupon_portal_enabled' => '1',
                'must_change_password' => '1',
            ])
            ->assertRedirect(route('admin.usuarios.index'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Victor Medeiros',
            'phone' => '(51) 99746-3222',
            'cpf' => '03312669030',
            'admin' => true,
            'coupon_portal_enabled' => true,
            'must_change_password' => true,
        ]);
    }
}
