<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_404_page_renders_correctly(): void
    {
        $view = view('errors.404')->render();

        $this->assertStringContainsString('404', $view);
        $this->assertStringContainsString('PÁGINA NÃO ENCONTRADA', $view);
    }

    public function test_404_route_returns_404_status(): void
    {
        $response = $this->get('/rota-que-nao-existe-xyz-123');

        $response->assertStatus(404);
    }

    public function test_503_page_renders_correctly(): void
    {
        $view = view('errors.503')->render();

        $this->assertStringContainsString('503', $view);
        $this->assertStringContainsString('EM MANUTENÇÃO', $view);
        $this->assertStringContainsString('VOLTAMOS EM BREVE', $view);
    }

    public function test_419_page_renders_recovery_controls(): void
    {
        $view = view('errors.419')->render();

        $this->assertStringContainsString('419', $view);
        $this->assertStringContainsString('Sessão Expirada', $view);
        $this->assertStringContainsString('csrf-return-button', $view);
    }
}
