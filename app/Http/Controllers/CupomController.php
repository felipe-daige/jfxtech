<?php

namespace App\Http\Controllers;

use App\Services\CheckoutOrderService;
use App\Services\CouponApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CupomController extends Controller
{
    public function __construct(
        private CheckoutOrderService $checkoutOrderService,
        private CouponApplicationService $couponApplicationService,
    ) {}

    public function aplicar(Request $request): JsonResponse
    {
        $request->validate(['codigo' => 'required|string|max:50']);

        $carrinho = $this->checkoutOrderService->resolveActiveOrder($request, ['itens']);

        if (!$carrinho) {
            return response()->json(['success' => false, 'message' => 'Carrinho não encontrado.'], 422);
        }

        $result = $this->couponApplicationService->applyToOrder(
            $carrinho,
            $request->codigo,
            Auth::id(),
        );

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        return response()->json([
            'success'    => true,
            'codigo'     => $result['codigo'],
            'desconto'   => number_format($result['desconto'], 2, ',', '.'),
            'novo_total' => number_format($result['novo_total'], 2, ',', '.'),
            'mensagem'   => $result['mensagem'],
        ]);
    }

    public function remover(Request $request): JsonResponse
    {
        $carrinho = $this->checkoutOrderService->resolveActiveOrder($request, ['itens']);

        if (!$carrinho) {
            return response()->json(['success' => false, 'message' => 'Carrinho não encontrado.'], 422);
        }

        $carrinho->update(['cupom_codigo' => null, 'valor_desconto' => 0]);

        $subtotal   = $carrinho->itens->sum(fn ($item) => (float) $item->preco * (int) $item->quantidade);
        $freteValor = (float) ($carrinho->frete_valor ?? 0);
        $novoTotal  = round($subtotal + $freteValor, 2);

        return response()->json([
            'success'    => true,
            'novo_total' => number_format($novoTotal, 2, ',', '.'),
        ]);
    }
}
