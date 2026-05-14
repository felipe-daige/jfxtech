<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCatalogPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_manager_can_access_product_and_category_admin(): void
    {
        $manager = User::factory()->create([
            'admin' => false,
            'admin_permissions' => [User::ADMIN_PERMISSION_CATALOG],
        ]);

        $this->actingAs($manager)
            ->get(route('admin.produtos'))
            ->assertOk()
            ->assertViewIs('admin.produtos');

        $this->actingAs($manager)
            ->get(route('admin.categorias'))
            ->assertOk()
            ->assertViewIs('admin.categorias');
    }

    public function test_catalog_manager_cannot_access_super_admin_areas(): void
    {
        $manager = User::factory()->create([
            'admin' => false,
            'admin_permissions' => [User::ADMIN_PERMISSION_CATALOG],
        ]);

        $this->actingAs($manager)
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->actingAs($manager)
            ->get(route('admin.usuarios.index'))
            ->assertForbidden();

        $this->actingAs($manager)
            ->get(route('admin.pedidos'))
            ->assertForbidden();

        $this->actingAs($manager)
            ->get(route('admin.cupons.index'))
            ->assertForbidden();
    }

    public function test_regular_user_cannot_access_catalog_admin(): void
    {
        $user = User::factory()->create([
            'admin' => false,
            'admin_permissions' => null,
        ]);

        $this->actingAs($user)
            ->get(route('admin.produtos'))
            ->assertForbidden();
    }

    public function test_super_admin_can_grant_catalog_permission_to_user(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $target = User::factory()->create([
            'admin' => false,
            'name' => 'Julio',
            'phone' => null,
            'cpf' => null,
            'admin_permissions' => null,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.usuarios.update', $target), [
                'name' => $target->name,
                'phone' => null,
                'cpf' => null,
                'catalog_manage' => '1',
            ])
            ->assertRedirect();

        $target->refresh();

        $this->assertFalse($target->admin);
        $this->assertSame([User::ADMIN_PERMISSION_CATALOG], $target->admin_permissions);
        $this->assertTrue($target->hasAdminPermission(User::ADMIN_PERMISSION_CATALOG));
    }
}
