<?php

namespace App\Http\Controllers;

use App\Models\Cupom;
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
        $cupons = Cupom::orderByDesc('created_at')->get();
        return view('admin.cupons', compact('cupons'));
    }

    public function store(Request $request)
    {
        $this->checkAdmin();
        $data = $request->validate([
            'codigo'              => 'required|string|max:50|unique:cupons,codigo',
            'tipo'                => 'required|in:percentual,fixo',
            'valor'               => 'required|numeric|min:0.01',
            'valor_minimo_pedido' => 'nullable|numeric|min:0',
            'limite_usos'         => 'nullable|integer|min:1',
            'valido_ate'          => 'nullable|date|after_or_equal:today',
            'ativo'               => 'boolean',
        ]);

        $data['codigo'] = strtoupper(trim($data['codigo']));
        $data['ativo']  = $request->boolean('ativo', true);

        $cupom = Cupom::create($data);

        return response()->json(['success' => true, 'cupom' => $cupom]);
    }

    public function update(Request $request, int $id)
    {
        $this->checkAdmin();
        $cupom = Cupom::findOrFail($id);

        $data = $request->validate([
            'codigo'              => 'required|string|max:50|unique:cupons,codigo,' . $id,
            'tipo'                => 'required|in:percentual,fixo',
            'valor'               => 'required|numeric|min:0.01',
            'valor_minimo_pedido' => 'nullable|numeric|min:0',
            'limite_usos'         => 'nullable|integer|min:1',
            'valido_ate'          => 'nullable|date',
            'ativo'               => 'boolean',
        ]);

        $data['codigo'] = strtoupper(trim($data['codigo']));
        $data['ativo']  = $request->boolean('ativo', $cupom->ativo);

        $cupom->update($data);

        return response()->json(['success' => true, 'cupom' => $cupom]);
    }

    public function destroy(int $id)
    {
        $this->checkAdmin();
        Cupom::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function toggle(int $id)
    {
        $this->checkAdmin();
        $cupom = Cupom::findOrFail($id);
        $cupom->update(['ativo' => !$cupom->ativo]);
        return response()->json(['success' => true, 'ativo' => $cupom->ativo]);
    }
}
