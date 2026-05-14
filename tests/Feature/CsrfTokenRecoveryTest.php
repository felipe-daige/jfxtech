<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CsrfTokenRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_mismatch_redirects_browser_back_to_previous_page(): void
    {
        Route::middleware('web')->post('/_test/csrf-expired', function () {
            throw new TokenMismatchException;
        });

        $this->withHeader('Referer', url('/perfil'))
            ->from(url('/perfil'))
            ->post('/_test/csrf-expired')
            ->assertRedirect(url('/perfil'))
            ->assertSessionHas('csrf_token_refreshed', true)
            ->assertSessionHas('csrf_return_to', url('/perfil'));
    }

    public function test_token_mismatch_returns_reload_payload_for_ajax_requests(): void
    {
        Route::middleware('web')->post('/_test/csrf-expired-json', function () {
            throw new TokenMismatchException;
        });

        $response = $this
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->withHeader('Referer', url('/checkout'))
            ->postJson('/_test/csrf-expired-json');

        $response
            ->assertStatus(419)
            ->assertHeader('X-CSRF-Expired', '1')
            ->assertHeader('X-CSRF-Redirect', url('/checkout'))
            ->assertJson([
                'redirect_url' => url('/checkout'),
                'reload' => true,
            ]);
    }
}
