<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PedidosController extends Controller
{
    /**
     * Exibir página de meus pedidos
     */
    public function meus_pedidos()
    {
        // Verificar se o usuário está logado
        if (!Auth::check()) {
            return redirect()->route('site.login')
                ->with('error', 'Você precisa estar logado para acessar seus pedidos.');
        }

        $usuario = Auth::user();
        
        // TODO: Buscar pedidos do usuário do banco de dados
        $pedidos = collect([]); // Por enquanto, lista vazia
        
        return view('site.meus-pedidos', compact('usuario', 'pedidos'));
    }
}
