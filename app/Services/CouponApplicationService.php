<?php

namespace App\Services;

use App\Models\Cupom;
use App\Models\CupomUso;
use App\Models\Pedido;

class CouponApplicationService
{
    public const SESSION_PENDING_COUPON = 'pending_coupon_code';

    public function normalizeCode(mixed $codigo): ?string
    {
        if (!is_string($codigo) && !is_numeric($codigo)) {
            return null;
        }

        $codigo = strtoupper(trim((string) $codigo));

        if ($codigo === '' || strlen($codigo) > 50) {
            return null;
        }

        return $codigo;
    }

    public function applyToOrder(Pedido $carrinho, string $codigo, ?int $userId = null): array
    {
        $codigo = $this->normalizeCode($codigo);

        if (!$codigo) {
            return $this->failure('Cupom inválido.');
        }

        $carrinho->loadMissing('itens');

        $subtotal = $carrinho->itens->sum(fn ($item) => (float) $item->preco * (int) $item->quantidade);
        $cupom = Cupom::whereRaw('UPPER(codigo) = ?', [$codigo])->first();

        if (!$cupom) {
            return $this->failure('Cupom inválido.');
        }

        if (!$cupom->ativo) {
            return $this->failure('Este cupom está inativo.');
        }

        if ($cupom->valido_ate && $cupom->valido_ate->isPast()) {
            return $this->failure('Este cupom está expirado.');
        }

        if ($cupom->limite_usos !== null && $cupom->usos_realizados >= $cupom->limite_usos) {
            return $this->failure('Este cupom atingiu o limite de usos.');
        }

        if ($userId !== null) {
            $jaUsou = CupomUso::where('cupom_id', $cupom->id)
                ->where('user_id', $userId)
                ->exists();

            if ($jaUsou) {
                return $this->failure('Você já utilizou este cupom.');
            }
        }

        if ($cupom->valor_minimo_pedido !== null && $subtotal < (float) $cupom->valor_minimo_pedido) {
            $minFormatado = number_format((float) $cupom->valor_minimo_pedido, 2, ',', '.');

            return $this->failure("Este cupom exige pedido mínimo de R$ {$minFormatado}.");
        }

        $desconto = $cupom->calcularDesconto($subtotal);

        $carrinho->update([
            'cupom_codigo' => $cupom->codigo,
            'valor_desconto' => $desconto,
        ]);

        $freteValor = (float) ($carrinho->frete_valor ?? 0);
        $novoTotal = max(0, round($subtotal - $desconto + $freteValor, 2));

        $tipoLabel = $cupom->tipo === 'percentual'
            ? number_format((float) $cupom->valor, 0) . '% de desconto'
            : 'R$ ' . number_format((float) $cupom->valor, 2, ',', '.') . ' de desconto';

        return [
            'success' => true,
            'codigo' => $cupom->codigo,
            'desconto' => $desconto,
            'novo_total' => $novoTotal,
            'mensagem' => "Cupom {$cupom->codigo} aplicado — {$tipoLabel}",
        ];
    }

    private function failure(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
        ];
    }
}
