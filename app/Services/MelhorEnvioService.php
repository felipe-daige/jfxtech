<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MelhorEnvioService
{
    private string $cepOrigem = '86010000';
    private string $apiUrl = 'https://api.melhorenvio.com.br/v2/me/shipment/calculate';

    // Correios PAC = 1, Correios SEDEX = 2
    private array $servicos = ['1', '2'];

    // Dimensões padrão em cm
    private int $comprimento = 20;
    private int $largura     = 15;
    private int $altura      = 10;

    public function __construct(
        private ?string $token    = null,
        private ?string $appName  = null,
        private int     $timeout  = 5,
    ) {
        $this->token   = $token   ?? config('services.melhor_envio.token');
        $this->appName = $appName ?? config('services.melhor_envio.app_name', 'JFXTech');
        $this->timeout = (int) ($timeout ?? config('services.melhor_envio.timeout', 5));
    }

    /**
     * Retorna cotações PAC e SEDEX para o peso e CEP informados.
     * Usa cache com chave baseada em faixa de peso (não peso exato).
     *
     * @return array{pac: array, sedex: array}|null  null em caso de falha
     */
    public function calcular(float $peso, string $cepDestino): ?array
    {
        if (empty($this->token)) {
            return null;
        }

        $faixaPeso  = $this->determinarFaixaPeso($peso);
        $cacheKey   = "melhorenvio_{$cepDestino}_{$faixaPeso}";

        return cache()->remember($cacheKey, 86400, function () use ($peso, $cepDestino) {
            return $this->consultarApi($peso, $cepDestino);
        });
    }

    private function consultarApi(float $peso, string $cepDestino): ?array
    {
        try {
            $response = Http::withToken($this->token)
                ->withUserAgent("{$this->appName} (contato@jfxtech.com.br)")
                ->timeout($this->timeout)
                ->post($this->apiUrl, [
                    'from'     => ['postal_code' => $this->cepOrigem],
                    'to'       => ['postal_code' => $cepDestino],
                    'package'  => [
                        'weight' => $peso,
                        'length' => $this->comprimento,
                        'width'  => $this->largura,
                        'height' => $this->altura,
                    ],
                    'services' => $this->servicos,
                ]);

            if (!$response->successful()) {
                Log::warning('MelhorEnvio: resposta não-2xx', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();

            if (!is_array($data) || empty($data)) {
                return null;
            }

            return $this->mapearResposta($data);
        } catch (\Throwable $e) {
            Log::warning('MelhorEnvio: falha na chamada à API', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Mapeia a resposta da API para o formato interno { pac: [...], sedex: [...] }.
     * Retorna null se nenhum serviço válido for encontrado.
     */
    private function mapearResposta(array $data): ?array
    {
        $resultado = [];

        foreach ($data as $servico) {
            // Serviços com erro de cotação chegam com campo 'error'
            if (!empty($servico['error'])) {
                continue;
            }

            $id = (string) ($servico['id'] ?? '');

            if ($id === '1') {
                $resultado['pac'] = $this->formatarServico($servico, 'PAC');
            } elseif ($id === '2') {
                $resultado['sedex'] = $this->formatarServico($servico, 'SEDEX');
            }
        }

        if (empty($resultado)) {
            return null;
        }

        return $resultado;
    }

    private function formatarServico(array $servico, string $nome): array
    {
        $prazo = isset($servico['delivery_time'])
            ? $servico['delivery_time'] . ' dias úteis'
            : 'A calcular';

        return [
            'nome'      => $nome,
            'descricao' => "Entrega em {$prazo}",
            'valor'     => (float) ($servico['price'] ?? 0),
            'prazo'     => $prazo,
        ];
    }

    public function determinarFaixaPeso(float $peso): string
    {
        if ($peso <= 0.5) return '0-0.5';
        if ($peso <= 1.0) return '0.5-1';
        if ($peso <= 2.0) return '1-2';
        if ($peso <= 3.0) return '2-3';
        if ($peso <= 5.0) return '3-5';
        return '5-10';
    }
}
