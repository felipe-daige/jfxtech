<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_footer_support_links_point_to_existing_pages(): void
    {
        $response = $this->get('/');

        $response
            ->assertStatus(200)
            ->assertSee(route('site.garantia'), false)
            ->assertSee(route('site.trocas-devolucoes'), false)
            ->assertSee(route('site.rastreamento'), false);
    }

    public function test_support_pages_are_available(): void
    {
        foreach ([
            route('site.garantia') => 'Garantia',
            route('site.trocas-devolucoes') => 'Trocas e Devoluções',
            route('site.rastreamento') => 'Rastreamento',
        ] as $url => $title) {
            $this->get($url)
                ->assertStatus(200)
                ->assertSee($title);
        }
    }
}
