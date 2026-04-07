<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MercadoPagoService
{
    public function createPayment(array $payload): array
    {
        return $this->request('post', '/v1/payments', $payload, [
            'X-Idempotency-Key' => (string) Str::uuid(),
        ]);
    }

    public function getPayment(string $paymentId): array
    {
        return $this->request('get', '/v1/payments/' . $paymentId);
    }

    public function cancelPayment(string $paymentId): array
    {
        return $this->request('put', '/v1/payments/' . $paymentId, [
            'status' => 'cancelled',
        ]);
    }

    /**
     * @throws RequestException
     */
    protected function request(string $method, string $uri, array $payload = [], array $headers = []): array
    {
        $baseUrl = rtrim((string) config('services.mercadopago.base_url'), '/');
        $accessToken = (string) config('services.mercadopago.access_token');

        abort_if($accessToken === '', 500, 'MERCADO_PAGO_ACCESS_TOKEN não configurado.');

        $response = Http::baseUrl($baseUrl)
            ->acceptJson()
            ->asJson()
            ->withToken($accessToken)
            ->withHeaders($headers)
            ->send($method, $uri, $payload === [] ? [] : ['json' => $payload]);

        $response->throw();

        return $response->json();
    }
}
