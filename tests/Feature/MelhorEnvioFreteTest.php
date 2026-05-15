<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MelhorEnvioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MelhorEnvioFreteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Garantir que nenhum teste dispare chamada HTTP real
        Http::preventStrayRequests();

        config([
            'services.melhor_envio.token'    => 'FAKE-TOKEN',
            'services.melhor_envio.app_name' => 'JFXTech-Test',
            'services.melhor_envio.timeout'  => 5,
        ]);
    }

    // -------------------------------------------------------------------------
    // Testes do MelhorEnvioService em isolamento
    // -------------------------------------------------------------------------

    public function test_retorna_null_quando_token_nao_configurado(): void
    {
        config(['services.melhor_envio.token' => null]);

        $service = new MelhorEnvioService(token: null);
        $result  = $service->calcular(0.5, '01310100');

        $this->assertNull($result);
    }

    public function test_retorna_cotacoes_quando_api_responde_com_sucesso(): void
    {
        Http::fake([
            'api.melhorenvio.com.br/*' => Http::response($this->respostaApiValida(), 200),
        ]);

        $service = new MelhorEnvioService(token: 'FAKE-TOKEN');
        $result  = $service->calcular(0.5, '01310100');

        $this->assertNotNull($result);
        $this->assertArrayHasKey('pac', $result);
        $this->assertArrayHasKey('sedex', $result);
        $this->assertEquals(29.90, $result['pac']['valor']);
        $this->assertEquals(45.00, $result['sedex']['valor']);
        $this->assertEquals('PAC', $result['pac']['nome']);
        $this->assertEquals('SEDEX', $result['sedex']['nome']);
    }

    public function test_retorna_null_quando_api_retorna_erro_http(): void
    {
        Http::fake([
            'api.melhorenvio.com.br/*' => Http::response(['error' => 'Unauthorized'], 401),
        ]);

        $service = new MelhorEnvioService(token: 'FAKE-TOKEN');
        $result  = $service->calcular(0.5, '01310100');

        $this->assertNull($result);
    }

    public function test_retorna_null_quando_api_lanca_excecao_de_timeout(): void
    {
        Http::fake([
            'api.melhorenvio.com.br/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
            },
        ]);

        $service = new MelhorEnvioService(token: 'FAKE-TOKEN');
        $result  = $service->calcular(0.5, '01310100');

        $this->assertNull($result);
    }

    public function test_retorna_null_quando_todos_servicos_tem_erro(): void
    {
        $respostaComErros = [
            ['id' => 1, 'name' => 'PAC', 'error' => 'CEP inválido'],
            ['id' => 2, 'name' => 'SEDEX', 'error' => 'CEP inválido'],
        ];

        Http::fake([
            'api.melhorenvio.com.br/*' => Http::response($respostaComErros, 200),
        ]);

        $service = new MelhorEnvioService(token: 'FAKE-TOKEN');
        $result  = $service->calcular(0.5, '99999999');

        $this->assertNull($result);
    }

    public function test_chave_de_cache_usa_faixa_de_peso_nao_valor_exato(): void
    {
        Http::fake([
            'api.melhorenvio.com.br/*' => Http::response($this->respostaApiValida(), 200),
        ]);

        $service = new MelhorEnvioService(token: 'FAKE-TOKEN');

        // Pesos distintos na mesma faixa (0-0.5) devem gerar a mesma chave de cache
        $result1 = $service->calcular(0.3, '01310100');
        $result2 = $service->calcular(0.4, '01310100');

        // A API só deve ter sido chamada uma vez por causa do cache
        Http::assertSentCount(1);

        $this->assertEquals($result1, $result2);
    }

    public function test_determinar_faixa_peso(): void
    {
        $service = new MelhorEnvioService(token: 'FAKE-TOKEN');

        $this->assertEquals('0-0.5', $service->determinarFaixaPeso(0.1));
        $this->assertEquals('0-0.5', $service->determinarFaixaPeso(0.5));
        $this->assertEquals('0.5-1', $service->determinarFaixaPeso(0.6));
        $this->assertEquals('0.5-1', $service->determinarFaixaPeso(1.0));
        $this->assertEquals('1-2',   $service->determinarFaixaPeso(1.5));
        $this->assertEquals('2-3',   $service->determinarFaixaPeso(2.5));
        $this->assertEquals('3-5',   $service->determinarFaixaPeso(4.0));
        $this->assertEquals('5-10',  $service->determinarFaixaPeso(7.0));
    }

    // -------------------------------------------------------------------------
    // Testes de integração: FreteController com MelhorEnvioService
    // -------------------------------------------------------------------------

    public function test_controller_usa_valores_da_api_quando_disponivel(): void
    {
        Http::fake([
            'api.melhorenvio.com.br/*' => Http::response($this->respostaApiValida(), 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('frete.calcular'), [
            'cep_destino' => '01310100',
            'peso_total'  => '0.5',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('source', 'api');

        $data = $response->json();
        $this->assertEquals(29.90, $data['opcoes']['pac']['valor']);
        $this->assertEquals(45.00, $data['opcoes']['sedex']['valor']);
    }

    public function test_controller_usa_tabela_estatica_quando_api_falha(): void
    {
        Http::fake([
            'api.melhorenvio.com.br/*' => Http::response([], 500),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('frete.calcular'), [
            'cep_destino' => '01310100', // SP → faixa 'sp'
            'peso_total'  => '0.5',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('source', 'tabela');

        $data = $response->json();

        // Verificar que usa valores da tabela estática para SP, faixa 0-0.5
        $this->assertEquals(17.00, $data['opcoes']['pac']['valor']);
        $this->assertEquals(25.00, $data['opcoes']['sedex']['valor']);
    }

    public function test_controller_usa_tabela_estatica_quando_token_ausente(): void
    {
        // Sem token → MelhorEnvioService retorna null imediatamente sem chamar Http
        config(['services.melhor_envio.token' => null]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('frete.calcular'), [
            'cep_destino' => '86010100', // Londrina → faixa 'pr_londrina'
            'peso_total'  => '1.0',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('source', 'tabela');

        $data = $response->json();

        // Tabela estática: pr_londrina, faixa 0.5-1
        $this->assertEquals(18.00, $data['opcoes']['pac']['valor']);
        $this->assertEquals(27.00, $data['opcoes']['sedex']['valor']);

        // Nenhuma chamada HTTP deve ter sido feita
        Http::assertNothingSent();
    }

    public function test_controller_inclui_campo_source_na_resposta(): void
    {
        Http::fake([
            'api.melhorenvio.com.br/*' => Http::response($this->respostaApiValida(), 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('frete.calcular'), [
            'cep_destino' => '20040020',
            'peso_total'  => '1.5',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'cep_origem',
                'cep_destino',
                'peso_total',
                'regiao',
                'faixa_peso',
                'source',
                'opcoes' => [
                    'pac'      => ['nome', 'descricao', 'valor', 'prazo'],
                    'sedex'    => ['nome', 'descricao', 'valor', 'prazo'],
                    'retirada' => ['nome', 'descricao', 'valor', 'prazo'],
                ],
            ]);

        $this->assertContains($response->json('source'), ['api', 'tabela']);
    }

    public function test_controller_sempre_inclui_opcao_retirada_com_valor_zero(): void
    {
        Http::fake([
            'api.melhorenvio.com.br/*' => Http::response($this->respostaApiValida(), 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('frete.calcular'), [
            'cep_destino' => '01310100',
            'peso_total'  => '0.5',
        ]);

        $response->assertOk()
            ->assertJsonPath('opcoes.retirada.nome', 'Retirada na Loja');

        $this->assertEquals(0.00, $response->json('opcoes.retirada.valor'));
    }

    public function test_controller_retorna_erro_422_quando_cep_invalido(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('frete.calcular'), [
            'cep_destino' => '123',   // menos de 8 dígitos
            'peso_total'  => '0.5',
        ]);

        $response->assertStatus(422);
    }

    public function test_controller_retorna_erro_422_quando_peso_invalido(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('frete.calcular'), [
            'cep_destino' => '01310100',
            'peso_total'  => '15', // max é 10
        ]);

        $response->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function respostaApiValida(): array
    {
        return [
            [
                'id'            => 1,
                'name'          => 'PAC',
                'price'         => 29.90,
                'delivery_time' => 7,
            ],
            [
                'id'            => 2,
                'name'          => 'SEDEX',
                'price'         => 45.00,
                'delivery_time' => 2,
            ],
        ];
    }
}
