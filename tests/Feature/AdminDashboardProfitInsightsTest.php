<?php

namespace Tests\Feature;

use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardProfitInsightsTest extends TestCase
{
    use RefreshDatabase;

    public function test_analytics_page_requires_authentication(): void
    {
        $this->get(route('admin.analytics'))
            ->assertRedirect(route('site.login'));
    }

    public function test_analytics_page_displays_profit_and_exclusivity_insights(): void
    {
        $user = User::factory()->create(['admin' => true]);

        $explicit = Produto::factory()->create([
            'nome' => 'Headset Counter Strike 2',
            'marca' => 'Razer',
            'preco' => 1200.00,
            'custo_compra' => 500.00,
            'estoque' => 10,
            'tags' => ['Exclusivo'],
        ]);

        $premiumBrand = Produto::factory()->create([
            'nome' => 'Teclado 60HE',
            'marca' => 'Wooting',
            'preco' => 900.00,
            'custo_compra' => 300.00,
            'estoque' => 4,
        ]);

        $premiumKeyword = Produto::factory()->create([
            'nome' => 'Monitor Pro 600Hz',
            'marca' => 'Marca X',
            'preco' => 5000.00,
            'custo_compra' => 3500.00,
            'estoque' => 2,
        ]);

        Produto::factory()->create([
            'nome' => 'Produto Sem Custo',
            'custo_compra' => null,
        ]);

        $response = $this->actingAs($user)->get(route('admin.analytics'));

        $response->assertOk()
            ->assertSeeText('Analytics')
            ->assertSeeText('Produtos com Mais Margem e Exclusividade')
            ->assertSeeText('Top 10 por lucro')
            ->assertSeeText('Top 10 menores margens')
            ->assertSeeText($explicit->nome)
            ->assertSeeText($premiumBrand->nome)
            ->assertSeeText($premiumKeyword->nome)
            ->assertSeeText('Estoque baixo')
            ->assertSeeText('Tag Exclusivo')
            ->assertSeeText('Marca premium')
            ->assertSeeText('Linha/edição premium');
    }

    public function test_analytics_page_builds_ranked_profit_insights_with_expected_priority_rules(): void
    {
        $user = User::factory()->create(['admin' => true]);

        $topProfit = Produto::factory()->create([
            'nome' => 'Produto Mais Lucrativo',
            'marca' => 'Marca A',
            'preco' => 1000.00,
            'custo_compra' => 200.00,
            'tags' => [],
        ]);

        $topMargin = Produto::factory()->create([
            'nome' => 'Produto Maior Margem',
            'marca' => 'Artisan',
            'preco' => 800.00,
            'custo_compra' => 80.00,
            'tags' => [],
        ]);

        $explicitExclusive = Produto::factory()->create([
            'nome' => 'Produto Exclusivo',
            'marca' => 'Marca B',
            'preco' => 700.00,
            'custo_compra' => 150.00,
            'tags' => ['Exclusivo'],
        ]);

        $keywordExclusive = Produto::factory()->create([
            'nome' => 'Monitor Arena 540Hz',
            'marca' => 'Marca C',
            'preco' => 3000.00,
            'custo_compra' => 2200.00,
            'tags' => [],
        ]);

        Produto::factory()->create([
            'nome' => 'Produto Ignorado Sem Custo',
            'preco' => 500.00,
            'custo_compra' => null,
        ]);

        $response = $this->actingAs($user)->get(route('admin.analytics'));

        $response->assertOk()
            ->assertViewHas('profit_exclusivity_insights', function (array $insights) use ($topProfit, $topMargin, $explicitExclusive, $keywordExclusive) {
                $topLucro = $insights['top_lucro_unitario'];
                $topMargem = $insights['top_margem_percentual'];
                $topMenorMargem = $insights['top_menor_margem'];
                $topExclusivos = $insights['top_exclusivos_lucrativos'];

                $this->assertSame($topProfit->id, $topLucro->first()['id']);
                $this->assertSame($topMargin->id, $topMargem->first()['id']);
                $this->assertSame($keywordExclusive->id, $topMenorMargem->first()['id']);

                $explicitInsight = $topExclusivos->firstWhere('id', $explicitExclusive->id);
                $brandInsight = $topExclusivos->firstWhere('id', $topMargin->id);
                $keywordInsight = $topExclusivos->firstWhere('id', $keywordExclusive->id);

                $this->assertSame('explicit', $explicitInsight['exclusive_signal']);
                $this->assertSame('Tag Exclusivo', $explicitInsight['exclusive_reason']);
                $this->assertSame('premium_brand', $brandInsight['exclusive_signal']);
                $this->assertSame('Marca premium', $brandInsight['exclusive_reason']);
                $this->assertSame('premium_keyword', $keywordInsight['exclusive_signal']);
                $this->assertSame('Linha/edição premium', $keywordInsight['exclusive_reason']);

                foreach ($topLucro as $item) {
                    $this->assertNotSame('Produto Ignorado Sem Custo', $item['nome']);
                }

                return true;
            });
    }

    public function test_dashboard_no_longer_renders_detailed_analytics_sections(): void
    {
        $user = User::factory()->create(['admin' => true]);
        Produto::factory()->create([
            'nome' => 'Produto Visível Só no Analytics',
            'marca' => 'Wooting',
            'preco' => 900.00,
            'custo_compra' => 300.00,
        ]);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSeeText('Dashboard')
            ->assertSeeText('Analytics')
            ->assertDontSeeText('Produtos com Mais Margem e Exclusividade')
            ->assertDontSeeText('Analytics de Produtos')
            ->assertDontSeeText('Top 10 menores margens');
    }
}
