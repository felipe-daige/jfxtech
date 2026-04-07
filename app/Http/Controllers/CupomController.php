<?php

namespace App\Http\Controllers;

use App\Models\Cupom;
use App\Models\CupomUso;
use App\Services\CheckoutOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CupomController extends Controller
{
    public function __construct(private CheckoutOrderService $checkoutOrderService) {}

    public function aplicar(Request $request): JsonResponse
    {
        $request->validate(['codigo' => 'required|string|max:50']);

        $carrinho = $this->checkoutOrderService->resolveActiveOrder($request, ['itens']);

        if (!$carrinho) {
            return response()->json(['success' => false, 'message' => 'Carrinho não encontrado.'], 422);
        }

        $subtotal = $carrinho->itens->sum(fn ($item) => (float) $item->preco * (int) $item->quantidade);

        $cupom = Cupom::whereRaw('UPPER(codigo) = ?', [strtoupper(trim($request->codigo))])->first();

        if (!$cupom) {
            return response()->json(['success' => false, 'message' => 'Cupom inválido.'], 422);
        }

        if (!$cupom->ativo) {
            return response()->json(['success' => false, 'message' => 'Este cupom está inativo.'], 422);
        }

        if ($cupom->valido_ate && $cupom->valido_ate->isPast()) {
            return response()->json(['success' => false, 'message' => 'Este cupom está expirado.'], 422);
        }

        if ($cupom->limite_usos !== null && $cupom->usos_realizados >= $cupom->limite_usos) {
            return response()->json(['success' => false, 'message' => 'Este cupom atingiu o limite de usos.'], 422);
        }

        if (Auth::check()) {
            $jaUsou = CupomUso::where('cupom_id', $cupom->id)
                ->where('user_id', Auth::id())
                ->exists();
            if ($jaUsou) {
                return response()->json(['success' => false, 'message' => 'Você já utilizou este cupom.'], 422);
            }
        }

        if ($cupom->valor_minimo_pedido !== null && $subtotal < (float) $cupom->valor_minimo_pedido) {
            $minFormatado = number_format((float) $cupom->valor_minimo_pedido, 2, ',', '.');
            return response()->json([
                'success' => false,
                'message' => "Este cupom exige pedido mínimo de R$ {$minFormatado}.",
            ], 422);
        }

        $desconto = $cupom->calcularDesconto($subtotal);

        $carrinho->update([
            'cupom_codigo'   => $cupom->codigo,
            'valor_desconto' => $desconto,
        ]);

        $freteValor = (float) ($carrinho->frete_valor ?? 0);
        $novoTotal  = max(0, round($subtotal - $desconto + $freteValor, 2));

        $tipoLabel = $cupom->tipo === 'percentual'
            ? number_format((float) $cupom->valor, 0) . '% de desconto'
            : 'R$ ' . number_format((float) $cupom->valor, 2, ',', '.') . ' de desconto';

        return response()->json([
            'success'    => true,
            'codigo'     => $cupom->codigo,
            'desconto'   => number_format($desconto, 2, ',', '.'),
            'novo_total' => number_format($novoTotal, 2, ',', '.'),
            'mensagem'   => "Cupom {$cupom->codigo} aplicado — {$tipoLabel}",
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
