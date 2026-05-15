<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Services\CheckoutOrderService;
use App\Services\FreteEstimativaService;
use App\Services\MelhorEnvioService;
use Illuminate\Http\Request;

class FreteController extends Controller
{
    private string $cepOrigem = '86010000';

    public function __construct(
        protected CheckoutOrderService $checkoutOrderService,
        protected FreteEstimativaService $freteEstimativaService,
        protected MelhorEnvioService $melhorEnvioService,
    ) {
    }

    /**
     * Calcular frete baseado no CEP de destino e peso
     */
    public function calcularFrete(Request $request)
    {
        $request->validate([
            'cep_destino' => 'required|string|size:8',
            'peso_total' => 'required|numeric|min:0.1|max:10'
        ]);

        $cepDestino = $request->cep_destino;
        $pesoTotal  = (float) $request->peso_total;

        // Tentar cotação via API Melhor Envio
        $apiResultado = $this->melhorEnvioService->calcular($pesoTotal, $cepDestino);
        $source       = $apiResultado !== null ? 'api' : 'tabela';

        // Fallback para tabela estática quando a API não está disponível
        $regiao = $this->freteEstimativaService->determinarRegiao($cepDestino);

        $valores = [
            'pac' => $apiResultado['pac'] ?? [
                'nome'      => 'PAC',
                'descricao' => 'Entrega em 4 a 7 dias úteis',
                'valor'     => $this->freteEstimativaService->calcularPorRegiao($pesoTotal, $regiao, 'pac'),
                'prazo'     => '4-7 dias úteis',
            ],
            'sedex' => $apiResultado['sedex'] ?? [
                'nome'      => 'SEDEX',
                'descricao' => 'Entrega em 1 a 3 dias úteis',
                'valor'     => $this->freteEstimativaService->calcularPorRegiao($pesoTotal, $regiao, 'sedex'),
                'prazo'     => '1-3 dias úteis',
            ],
            'retirada' => [
                'nome'      => 'Retirada na Loja',
                'descricao' => 'Retire seu pedido em nossa loja em Londrina, PR',
                'valor'     => 0.00,
                'prazo'     => 'Combinado',
            ],
        ];

        return response()->json([
            'success'     => true,
            'cep_origem'  => $this->cepOrigem,
            'cep_destino' => $cepDestino,
            'peso_total'  => $pesoTotal,
            'regiao'      => $regiao,
            'faixa_peso'  => $this->freteEstimativaService->determinarFaixaPeso($pesoTotal),
            'source'      => $source,
            'opcoes'      => $valores,
        ]);
    }

    /**
     * Calcular frete do carrinho
     */
    public function calcularFreteCarrinho(Request $request)
    {
        $request->validate([
            'cep_destino' => 'required|string|size:8'
        ]);

        $carrinho = $this->resolvePedidoAtivo();

        if (!$carrinho || $carrinho->itens->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Carrinho vazio'
            ], 400);
        }

        // Calcular peso total
        $pesoTotal = 0;
        foreach ($carrinho->itens as $item) {
            $pesoProduto = $item->produto->peso ?? 0.5; // Peso padrão se não informado
            $pesoTotal += $pesoProduto * $item->quantidade;
        }

        // Calcular frete
        $resultado = $this->calcularFrete(new Request([
            'cep_destino' => $request->cep_destino,
            'peso_total' => $pesoTotal
        ]));

        // Frete grátis promocional (tempo limitado)
        if (config('services.frete_gratis_ativo', false)) {
            $minimoFrete = (float) config('services.frete_gratis_minimo', 0);
            $subtotal = $carrinho->itens->sum(fn($item) => $item->preco * $item->quantidade);

            if ($subtotal >= $minimoFrete) {
                $data = $resultado->getData(true);
                $data['opcoes']['gratis'] = [
                    'nome' => 'Frete Grátis',
                    'descricao' => 'Promoção por tempo limitado!',
                    'valor' => 0.00,
                    'prazo' => '7-12 dias úteis',
                    'tempo_limitado' => true,
                ];
                return response()->json($data);
            }
        }

        return $resultado;
    }

    private function resolvePedidoAtivo(): ?Pedido
    {
        return $this->checkoutOrderService->resolveActiveOrder(request(), ['itens.produto']);
    }
}
