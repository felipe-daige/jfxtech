<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TemporaryPasswordLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_log_in_multiple_times_with_temporary_password_without_forced_redirect(): void
    {
        $temporaryPassword = 'TempPass8';
        $user = User::factory()->create([
            'email' => '012.11864@alunos.unigran.br',
            'password' => Hash::make($temporaryPassword),
            'must_change_password' => true,
        ]);

        $this->post(route('site.login.post'), [
            'email' => $user->email,
            'password' => $temporaryPassword,
        ])->assertRedirect(route('site.index'));

        $this->actingAs($user)
            ->get(route('site.pedidos.index'))
            ->assertOk();

        $this->post(route('site.logout'))
            ->assertRedirect(route('site.index'));

        $this->post(route('site.login.post'), [
            'email' => $user->email,
            'password' => $temporaryPassword,
        ])->assertRedirect(route('site.index'));

        $this->actingAs($user)
            ->get(route('site.perfil'))
            ->assertOk();

        $user->refresh();

        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check($temporaryPassword, $user->password));
    }
}
