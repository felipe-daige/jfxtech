<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoritosController extends Controller
{
    /**
     * Exibir página de favoritos
     */
    public function favoritos()
    {
        // Verificar se o usuário está logado
        if (!Auth::check()) {
            return redirect()->route('site.login')
                ->with('error', 'Você precisa estar logado para acessar seus favoritos.');
        }

        $usuario = Auth::user();
        
        // Buscar favoritos do usuário do banco de dados
        $favoritos = $usuario->produtosFavoritos()
            ->with(['imagens', 'categoria'])
            ->paginate(12);
        
        return view('site.favoritos', compact('usuario', 'favoritos'));
    }

    /**
     * Adicionar produto aos favoritos
     */
    public function adicionar(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado.'
            ], 401);
        }

        $request->validate([
            'produto_id' => 'required|exists:produtos,id'
        ]);

        $usuario = Auth::user();
        $produtoId = $request->produto_id;

        // Verificar se já está nos favoritos
        $favoritoExistente = $usuario->favoritos()->where('produto_id', $produtoId)->first();

        if ($favoritoExistente) {
            return response()->json([
                'success' => false,
                'message' => 'Produto já está nos favoritos!'
            ], 400);
        }

        // Adicionar aos favoritos
        $usuario->favoritos()->create([
            'produto_id' => $produtoId
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Produto adicionado aos favoritos!'
        ]);
    }

    /**
     * Remover produto dos favoritos
     */
    public function remover(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado.'
            ], 401);
        }

        $request->validate([
            'produto_id' => 'required|exists:produtos,id'
        ]);

        $usuario = Auth::user();
        $produtoId = $request->produto_id;

        // Remover dos favoritos
        $removido = $usuario->favoritos()->where('produto_id', $produtoId)->delete();

        if ($removido) {
            return response()->json([
                'success' => true,
                'message' => 'Produto removido dos favoritos!'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Produto não estava nos favoritos!'
            ], 400);
        }
    }

    /**
     * Limpar todos os favoritos
     */
    public function limpar_todos(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado.'
            ], 401);
        }

        $usuario = Auth::user();
        
        // Remover todos os favoritos do usuário
        $removidos = $usuario->favoritos()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Todos os favoritos foram removidos!',
            'removidos' => $removidos
        ]);
    }
}
