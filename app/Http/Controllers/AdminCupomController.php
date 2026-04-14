<?php

namespace App\Http\Controllers;

use App\Models\Cupom;
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
        $cupons = Cupom::with('user')->orderByDesc('created_at')->get();
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
            'codigo'              => 'required|string|max:50|unique:cupons,codigo',
            'user_id'             => 'nullable|integer|exists:users,id',
            'coupon_portal_enabled' => 'boolean',
            'tipo'                => 'required|in:percentual,fixo',
            'valor'               => 'required|numeric|min:0.01',
            'valor_minimo_pedido' => 'nullable|numeric|min:0',
            'limite_usos'         => 'nullable|integer|min:1',
            'valido_ate'          => 'nullable|date|after_or_equal:today',
            'ativo'               => 'boolean',
        ]);

        $data['codigo'] = strtoupper(trim($data['codigo']));
        $data['ativo'] = $request->boolean('ativo', true);
        $data['user_id'] = $request->filled('user_id') ? (int) $request->user_id : null;

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
            'codigo'              => 'required|string|max:50|unique:cupons,codigo,' . $id,
            'user_id'             => 'nullable|integer|exists:users,id',
            'coupon_portal_enabled' => 'boolean',
            'tipo'                => 'required|in:percentual,fixo',
            'valor'               => 'required|numeric|min:0.01',
            'valor_minimo_pedido' => 'nullable|numeric|min:0',
            'limite_usos'         => 'nullable|integer|min:1',
            'valido_ate'          => 'nullable|date',
            'ativo'               => 'boolean',
        ]);

        $data['codigo'] = strtoupper(trim($data['codigo']));
        $data['ativo'] = $request->boolean('ativo', $cupom->ativo);
        $data['user_id'] = $request->filled('user_id') ? (int) $request->user_id : null;

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
