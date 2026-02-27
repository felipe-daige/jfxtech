<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Produto;
use App\Models\Pedido;
use App\Models\ItemPedido;

class CarrinhoController extends Controller
{
    /**
     * Adicionar produto ao carrinho
     */
    public function adicionar(Request $request)
    {
        $request->validate([
            'produto_id' => 'required|exists:produtos,id',
            'quantidade' => 'required|integer|min:1|max:10'
        ], [
            'produto_id.required' => 'ID do produto é obrigatório.',
            'produto_id.exists' => 'Produto não encontrado.',
            'quantidade.required' => 'Quantidade é obrigatória.',
            'quantidade.integer' => 'Quantidade deve ser um número inteiro.',
            'quantidade.min' => 'Quantidade deve ser pelo menos 1.',
            'quantidade.max' => 'Quantidade máxima é 10.'
        ]);

        $produto = Produto::where('id', $request->produto_id)
            ->where('ativo', true)
            ->first();

        if (!$produto) {
            return response()->json([
                'success' => false,
                'message' => 'Produto não encontrado ou inativo.'
            ], 404);
        }

        if ($produto->estoque < $request->quantidade) {
            return response()->json([
                'success' => false,
                'message' => 'Quantidade solicitada não disponível em estoque.'
            ], 400);
        }

        // Verificar se o usuário está logado
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Você precisa estar logado para adicionar produtos ao carrinho.',
                'redirect' => route('site.login')
            ], 401);
        }

        // Buscar carrinho ativo do usuário ou criar um novo
        $carrinho = Pedido::where('user_id', Auth::id())
            ->where('status', 'carrinho')
            ->first();

        if (!$carrinho) {
            $carrinho = Pedido::create([
                'user_id' => Auth::id(),
                'status' => 'carrinho',
                'valor_total' => 0
            ]);
        }

        // Verificar se o produto já está no carrinho
        $itemExistente = ItemPedido::where('pedido_id', $carrinho->id)
            ->where('produto_id', $produto->id)
            ->first();

        if ($itemExistente) {
            // Atualizar quantidade
            $novaQuantidade = $itemExistente->quantidade + $request->quantidade;
            
            if ($novaQuantidade > $produto->estoque) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quantidade total excede o estoque disponível.'
                ], 400);
            }

            $itemExistente->quantidade = $novaQuantidade;
            $itemExistente->preco = $produto->preco;
            $itemExistente->save();
        } else {
            // Adicionar novo item
            ItemPedido::create([
                'pedido_id' => $carrinho->id,
                'produto_id' => $produto->id,
                'quantidade' => $request->quantidade,
                'preco' => $produto->preco
            ]);
        }

        // Recalcular valor total do carrinho
        $this->recalcularValorTotal($carrinho);

        return response()->json([
            'success' => true,
            'message' => 'Produto adicionado ao carrinho com sucesso!',
            'carrinho' => [
                'total_itens' => $carrinho->itens()->sum('quantidade'),
                'valor_total' => $carrinho->valor_total
            ],
            'produto' => [
                'id' => $produto->id,
                'nome' => $produto->nome,
                'quantidade' => $itemExistente ? $itemExistente->quantidade : $request->quantidade
            ]
        ]);
    }

    /**
     * Remover produto do carrinho
     */
    public function remover(Request $request)
    {
        $request->validate([
            'produto_id' => 'required|exists:produtos,id'
        ]);

        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado.'
            ], 401);
        }

        $carrinho = Pedido::where('user_id', Auth::id())
            ->where('status', 'carrinho')
            ->first();

        if (!$carrinho) {
            return response()->json([
                'success' => false,
                'message' => 'Carrinho não encontrado.'
            ], 404);
        }

        $item = ItemPedido::where('pedido_id', $carrinho->id)
            ->where('produto_id', $request->produto_id)
            ->first();

        if ($item) {
            $item->delete();
            $this->recalcularValorTotal($carrinho);
        }

        return response()->json([
            'success' => true,
            'message' => 'Produto removido do carrinho.'
        ]);
    }

    /**
     * Atualizar quantidade de um item no carrinho
     */
    public function atualizar_quantidade(Request $request)
    {
        $request->validate([
            'produto_id' => 'required|exists:produtos,id',
            'quantidade' => 'required|integer|min:1|max:10'
        ]);

        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado.'
            ], 401);
        }

        $produto = Produto::find($request->produto_id);
        
        if ($produto->estoque < $request->quantidade) {
            return response()->json([
                'success' => false,
                'message' => 'Quantidade solicitada não disponível em estoque.'
            ], 400);
        }

        $carrinho = Pedido::where('user_id', Auth::id())
            ->where('status', 'carrinho')
            ->first();

        if (!$carrinho) {
            return response()->json([
                'success' => false,
                'message' => 'Carrinho não encontrado.'
            ], 404);
        }

        $item = ItemPedido::where('pedido_id', $carrinho->id)
            ->where('produto_id', $request->produto_id)
            ->first();

        if ($item) {
            $item->quantidade = $request->quantidade;
            $item->save();
            $this->recalcularValorTotal($carrinho);
        }

        return response()->json([
            'success' => true,
            'message' => 'Quantidade atualizada com sucesso.'
        ]);
    }

    /**
     * Exibir carrinho
     */
    public function carrinho()
    {
        if (!Auth::check()) {
            return redirect()->route('site.login')
                ->with('error', 'Você precisa estar logado para acessar o carrinho.');
        }

        $carrinho = Pedido::where('user_id', Auth::id())
            ->where('status', 'carrinho')
            ->with(['itens.produto.imagens'])
            ->first();

        return view('site.carrinho', compact('carrinho'));
    }

    /**
     * Obter contador do carrinho
     */
    public function contador()
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'total_itens' => 0
            ]);
        }

        $carrinho = Pedido::where('user_id', Auth::id())
            ->where('status', 'carrinho')
            ->first();

        $totalItens = $carrinho ? $carrinho->itens()->sum('quantidade') : 0;

        return response()->json([
            'success' => true,
            'total_itens' => $totalItens
        ]);
    }

    /**
     * Obter itens do carrinho
     */
    public function itens()
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado.'
            ], 401);
        }

        $carrinho = Pedido::where('user_id', Auth::id())
            ->where('status', 'carrinho')
            ->with(['itens.produto.imagens'])
            ->first();

        if (!$carrinho) {
            return response()->json([
                'success' => true,
                'carrinho' => null
            ]);
        }

        return response()->json([
            'success' => true,
            'carrinho' => [
                'id' => $carrinho->id,
                'valor_total' => $carrinho->valor_total,
                'itens' => $carrinho->itens->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'quantidade' => $item->quantidade,
                        'preco' => $item->preco,
                        'produto' => [
                            'id' => $item->produto->id,
                            'nome' => $item->produto->nome,
                            'primeira_imagem' => $item->produto->primeira_imagem
                        ]
                    ];
                })
            ]
        ]);
    }

    /**
     * Verificar se produto está no carrinho
     */
    public function verificar_produto(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'no_carrinho' => false
            ]);
        }

        $request->validate([
            'produto_id' => 'required|exists:produtos,id'
        ]);

        $carrinho = Pedido::where('user_id', Auth::id())
            ->where('status', 'carrinho')
            ->first();

        if (!$carrinho) {
            return response()->json([
                'success' => true,
                'no_carrinho' => false
            ]);
        }

        $item = ItemPedido::where('pedido_id', $carrinho->id)
            ->where('produto_id', $request->produto_id)
            ->first();

        return response()->json([
            'success' => true,
            'no_carrinho' => $item ? true : false,
            'quantidade' => $item ? $item->quantidade : 0
        ]);
    }

    /**
     * Recalcular valor total do carrinho
     */
    private function recalcularValorTotal($carrinho)
    {
        $valorTotal = $carrinho->itens()->get()->sum(function ($item) {
            return $item->quantidade * $item->preco;
        });
        
        $carrinho->update(['valor_total' => $valorTotal]);
    }
}
