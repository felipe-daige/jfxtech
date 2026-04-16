<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_password_reset_link(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'cliente@example.com']);

        $this->post(route('site.password.email'), [
            'email' => 'cliente@example.com',
        ])->assertSessionHas('success');

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_valid_token_resets_password(): void
    {
        $user = User::factory()->create(['email' => 'cliente@example.com']);
        $token = Password::broker()->createToken($user);

        $this->post(route('site.password.update'), [
            'token' => $token,
            'email' => 'cliente@example.com',
            'password' => 'nova-senha-segura',
            'password_confirmation' => 'nova-senha-segura',
        ])->assertRedirect(route('site.login'))
            ->assertSessionHas('success');

        $user->refresh();

        $this->assertTrue(Hash::check('nova-senha-segura', $user->password));
        $this->assertTrue(auth()->attempt([
            'email' => 'cliente@example.com',
            'password' => 'nova-senha-segura',
        ]));
    }

    public function test_invalid_token_does_not_reset_password(): void
    {
        $user = User::factory()->create([
            'email' => 'cliente@example.com',
            'password' => Hash::make('senha-antiga'),
        ]);

        $this->post(route('site.password.update'), [
            'token' => 'token-invalido',
            'email' => 'cliente@example.com',
            'password' => 'nova-senha-segura',
            'password_confirmation' => 'nova-senha-segura',
        ])->assertSessionHasErrors('email');

        $user->refresh();

        $this->assertTrue(Hash::check('senha-antiga', $user->password));
    }
}
