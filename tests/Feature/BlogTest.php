<?php

namespace Tests\Feature;

use Tests\TestCase;

class BlogTest extends TestCase
{
    public function test_blog_index_returns_200(): void
    {
        $response = $this->get('/blog');
        $response->assertStatus(200);
    }

    public function test_blog_index_lists_articles(): void
    {
        $response = $this->get('/blog');
        $response->assertSee('Como Escolher o Monitor Gamer Ideal');
    }

    public function test_blog_show_returns_200_for_valid_slug(): void
    {
        $response = $this->get('/blog/como-escolher-monitor-gamer');
        $response->assertStatus(200);
        $response->assertSee('Como Escolher o Monitor Gamer Ideal');
    }

    public function test_blog_show_renders_markdown_content(): void
    {
        $response = $this->get('/blog/como-escolher-monitor-gamer');
        $response->assertSee('<h2>', false);
        $response->assertSee('144Hz', false);
    }

    public function test_blog_show_returns_404_for_invalid_slug(): void
    {
        $response = $this->get('/blog/artigo-que-nao-existe-xyz-abc');
        $response->assertStatus(404);
    }
}
