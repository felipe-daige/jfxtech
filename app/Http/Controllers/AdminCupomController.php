<?php

namespace App\Http\Controllers;

use App\Models\Cupom;
use App\Models\CupomPagamento;
use App\Models\CupomUso;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminCupomController extends Controller
{
    private function checkAdmin(): void
    {
        if (!Auth::check()) {
            abort(401);
        }
    }

    public function index()
    {
        $this->checkAdmin();

        $cupons = Cupom::with([
            'user',
            'usos.pedido',
            'pagamentos',
        ])->orderByDesc('created_at')->get();

        $cupons->each(function (Cupom $cupom) {
            $comissaoPerc = (float) ($cupom->comissao_percentual ?? 100);

            $receitaGerada   = 0.0;
            $descontosDados  = 0.0;
            $comissaoTotal   = 0.0;

            foreach ($cupom->usos as $uso) {
                if (!$uso->pedido) {
                    continue;
                }
                $receitaGerada  += (float) $uso->pedido->valor_total;
                $desconto        = (float) $uso->pedido->valor_desconto;
                $descontosDados += $desconto;
                $comissaoTotal  += round($desconto * $comissaoPerc / 100, 2);
            }

            $comissaoPaga = $cupom->pagamentos->sum(fn ($p) => (float) $p->valor_pago);

            $cupom->receita_gerada     = round($receitaGerada, 2);
            $cupom->descontos_dados    = round($descontosDados, 2);
            $cupom->comissao_total     = round($comissaoTotal, 2);
            $cupom->comissao_paga      = round($comissaoPaga, 2);
            $cupom->comissao_pendente  = round($comissaoTotal - $comissaoPaga, 2);
        });

        return view('admin.cupons', compact('cupons'));
    }

    public function buscarUsuarios(Request $request)
    {
        $this->checkAdmin();

        $q = trim((string) $request->get('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $users = User::query()
            ->where(function ($query) use ($q) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($q) . '%'])
                    ->orWhereRaw('LOWER(email) LIKE ?', ['%' . mb_strtolower($q) . '%']);
            })
            ->select('id', 'name', 'email', 'coupon_portal_enabled')
            ->limit(10)
            ->get();

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $this->checkAdmin();
        $data = $request->validate([
            'codigo'               => 'required|string|max:50|unique:cupons,codigo',
            'user_id'              => 'nullable|integer|exists:users,id',
            'coupon_portal_enabled' => 'boolean',
            'tipo'                 => 'required|in:percentual,fixo',
            'valor'                => 'required|numeric|min:0.01',
            'valor_minimo_pedido'  => 'nullable|numeric|min:0',
            'limite_usos'          => 'nullable|integer|min:1',
            'valido_ate'           => 'nullable|date|after_or_equal:today',
            'ativo'                => 'boolean',
            'comissao_percentual'  => 'nullable|numeric|min:0|max:100',
        ]);

        $data['codigo']              = strtoupper(trim($data['codigo']));
        $data['ativo']               = $request->boolean('ativo', true);
        $data['user_id']             = $request->filled('user_id') ? (int) $request->user_id : null;
        $data['comissao_percentual'] = $request->filled('comissao_percentual')
            ? (float) $request->comissao_percentual
            : 100;

        $cupom = Cupom::create($data);
        $this->syncCouponPortalAccess(
            $data['user_id'],
            $request->boolean('coupon_portal_enabled', false)
        );

        return response()->json(['success' => true, 'cupom' => $cupom->load('user')]);
    }

    public function update(Request $request, int $id)
    {
        $this->checkAdmin();
        $cupom = Cupom::findOrFail($id);

        $data = $request->validate([
            'codigo'               => 'required|string|max:50|unique:cupons,codigo,' . $id,
            'user_id'              => 'nullable|integer|exists:users,id',
            'coupon_portal_enabled' => 'boolean',
            'tipo'                 => 'required|in:percentual,fixo',
            'valor'                => 'required|numeric|min:0.01',
            'valor_minimo_pedido'  => 'nullable|numeric|min:0',
            'limite_usos'          => 'nullable|integer|min:1',
            'valido_ate'           => 'nullable|date',
            'ativo'                => 'boolean',
            'comissao_percentual'  => 'nullable|numeric|min:0|max:100',
        ]);

        $data['codigo']              = strtoupper(trim($data['codigo']));
        $data['ativo']               = $request->boolean('ativo', $cupom->ativo);
        $data['user_id']             = $request->filled('user_id') ? (int) $request->user_id : null;
        $data['comissao_percentual'] = $request->filled('comissao_percentual')
            ? (float) $request->comissao_percentual
            : $cupom->comissao_percentual;

        $previousUserId = $cupom->user_id;
        $cupom->update($data);
        $this->syncCouponPortalAccess(
            $data['user_id'],
            $request->boolean('coupon_portal_enabled', false)
        );

        if ($previousUserId && $previousUserId !== $data['user_id']) {
            $this->disablePortalIfUserHasNoCoupons($previousUserId);
        }

        return response()->json(['success' => true, 'cupom' => $cupom->load('user')]);
    }

    public function destroy(int $id)
    {
        $this->checkAdmin();
        $cupom = Cupom::findOrFail($id);
        $userId = $cupom->user_id;
        $cupom->delete();

        if ($userId) {
            $this->disablePortalIfUserHasNoCoupons($userId);
        }

        return response()->json(['success' => true]);
    }

    public function toggle(int $id)
    {
        $this->checkAdmin();
        $cupom = Cupom::findOrFail($id);
        $cupom->update(['ativo' => !$cupom->ativo]);
        return response()->json(['success' => true, 'ativo' => $cupom->ativo]);
    }

    public function usosPendentes(int $id)
    {
        $this->checkAdmin();
        $cupom = Cupom::findOrFail($id);
        $comissaoPerc = (float) ($cupom->comissao_percentual ?? 100);

        $usos = CupomUso::with('pedido')
            ->where('cupom_id', $id)
            ->whereNull('cupom_pagamento_id')
            ->get()
            ->map(function (CupomUso $uso) use ($comissaoPerc) {
                $desconto = (float) ($uso->pedido?->valor_desconto ?? 0);
                return [
                    'id'              => $uso->id,
                    'pedido_id'       => $uso->pedido_id,
                    'created_at'      => $uso->created_at?->format('d/m/Y'),
                    'valor_desconto'  => number_format($desconto, 2, '.', ''),
                    'comissao_valor'  => number_format(round($desconto * $comissaoPerc / 100, 2), 2, '.', ''),
                ];
            });

        return response()->json($usos);
    }

    public function pagamentos(int $id)
    {
        $this->checkAdmin();
        Cupom::findOrFail($id);

        $pagamentos = CupomPagamento::with('usos.pedido')
            ->where('cupom_id', $id)
            ->orderByDesc('pago_em')
            ->get()
            ->map(function (CupomPagamento $p) {
                return [
                    'id'          => $p->id,
                    'valor_pago'  => number_format((float) $p->valor_pago, 2, '.', ''),
                    'pago_em'     => $p->pago_em->format('d/m/Y'),
                    'observacao'  => $p->observacao,
                    'usos_count'  => $p->usos->count(),
                    'usos'        => $p->usos->map(fn ($u) => [
                        'id'        => $u->id,
                        'pedido_id' => $u->pedido_id,
                        'data'      => $u->created_at?->format('d/m/Y'),
                        'comissao'  => number_format((float) ($u->pedido?->valor_desconto ?? 0), 2, '.', ''),
                    ]),
                ];
            });

        return response()->json($pagamentos);
    }

    public function storePagamento(Request $request, int $id)
    {
        $this->checkAdmin();
        $cupom = Cupom::findOrFail($id);

        $data = $request->validate([
            'valor_pago'  => 'required|numeric|min:0.01',
            'pago_em'     => 'required|date',
            'observacao'  => 'nullable|string|max:1000',
            'uso_ids'     => 'nullable|array',
            'uso_ids.*'   => 'integer',
        ]);

        $pagamento = CupomPagamento::create([
            'cupom_id'   => $cupom->id,
            'valor_pago' => $data['valor_pago'],
            'pago_em'    => $data['pago_em'],
            'observacao' => $data['observacao'] ?? null,
        ]);

        if (!empty($data['uso_ids'])) {
            CupomUso::where('cupom_id', $cupom->id)
                ->whereIn('id', $data['uso_ids'])
                ->whereNull('cupom_pagamento_id')
                ->update(['cupom_pagamento_id' => $pagamento->id]);
        }

        return response()->json(['success' => true, 'pagamento' => $pagamento]);
    }

    public function updatePagamento(Request $request, int $id, int $pid)
    {
        $this->checkAdmin();
        Cupom::findOrFail($id);
        $pagamento = CupomPagamento::where('cupom_id', $id)->findOrFail($pid);

        $data = $request->validate([
            'valor_pago' => 'required|numeric|min:0.01',
            'pago_em'    => 'required|date',
            'observacao' => 'nullable|string|max:1000',
        ]);

        $pagamento->update($data);

        return response()->json(['success' => true, 'pagamento' => $pagamento]);
    }

    public function destroyPagamento(int $id, int $pid)
    {
        $this->checkAdmin();
        Cupom::findOrFail($id);
        $pagamento = CupomPagamento::where('cupom_id', $id)->findOrFail($pid);
        $pagamento->delete();

        return response()->json(['success' => true]);
    }

    private function syncCouponPortalAccess(?int $userId, bool $enabled): void
    {
        if (!$userId) {
            return;
        }

        User::whereKey($userId)->update([
            'coupon_portal_enabled' => $enabled,
        ]);
    }

    private function disablePortalIfUserHasNoCoupons(int $userId): void
    {
        if (!Cupom::where('user_id', $userId)->exists()) {
            User::whereKey($userId)->update([
                'coupon_portal_enabled' => false,
            ]);
        }
    }
}
