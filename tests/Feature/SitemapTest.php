<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_returns_200(): void
    {
        $response = $this->get('/sitemap.xml');
        $response->assertStatus(200);
    }

    public function test_sitemap_has_xml_content_type(): void
    {
        $response = $this->get('/sitemap.xml');
        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));
    }

    public function test_sitemap_contains_homepage(): void
    {
        $response = $this->get('/sitemap.xml');
        $response->assertSee('<loc>' . url('/') . '</loc>', false);
    }

    public function test_sitemap_contains_blog_index(): void
    {
        $response = $this->get('/sitemap.xml');
        $response->assertSee('<loc>' . url('/blog') . '</loc>', false);
    }

    public function test_sitemap_contains_blog_article_fixture(): void
    {
        $response = $this->get('/sitemap.xml');
        $response->assertSee('/blog/como-escolher-monitor-gamer', false);
    }

    public function test_sitemap_is_valid_xml(): void
    {
        $response = $this->get('/sitemap.xml');
        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);
    }
}
