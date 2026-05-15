<?php

namespace App\Http\Controllers;

use App\Models\Configuracao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminConfiguracaoController extends Controller
{
    public function update(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'desconto_pix_global' => 'required|numeric|min:0|max:100',
        ]);

        Configuracao::set('desconto_pix_global', number_format((float) $validated['desconto_pix_global'], 2, '.', ''));

        return response()->json([
            'success' => true,
            'desconto_pix_global' => (float) Configuracao::get('desconto_pix_global'),
        ], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }
}
