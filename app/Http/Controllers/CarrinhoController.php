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
            'produto_id'          => 'required|exists:produtos,id',
            'quantidade'          => 'required|integer|min:1|max:10',
            'produto_variante_id' => 'nullable|exists:produto_variantes,id',
        ], [
            'produto_id.required' => 'ID do produto é obrigatório.',
            'produto_id.exists'   => 'Produto não encontrado.',
            'quantidade.required' => 'Quantidade é obrigatória.',
            'quantidade.integer'  => 'Quantidade deve ser um número inteiro.',
            'quantidade.min'      => 'Quantidade deve ser pelo menos 1.',
            'quantidade.max'      => 'Quantidade máxima é 10.',
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

        // Validate and load variant (cross-FK check: variante must belong to this produto)
        $variante = null;
        if ($request->produto_variante_id) {
            $variante = \App\Models\ProdutoVariante::with('produto')
                ->where('id', $request->produto_variante_id)
                ->where('produto_id', $produto->id)
                ->where('ativo', true)
                ->first();

            if (!$variante) {
                return response()->json([
                    'success' => false,
                    'message' => 'Variante inválida ou inativa.'
                ], 422);
            }
            // Ensure produto relation is set for accessor use
            $variante->setRelation('produto', $produto);
        }

        $estoqueEfetivo = $variante ? $variante->estoque_efetivo : $produto->estoque;

        if ($estoqueEfetivo < $request->quantidade) {
            return response()->json([
                'success' => false,
                'message' => 'Quantidade solicitada não disponível em estoque.'
            ], 400);
        }

        // Verificar se o usuário está logado
        if (!Auth::check()) {
            return response()->json([
                'success'  => false,
                'message'  => 'Você precisa estar logado para adicionar produtos ao carrinho.',
                'redirect' => route('site.login')
            ], 401);
        }

        $carrinho = Pedido::where('user_id', Auth::id())
            ->where('status', 'carrinho')
            ->first();

        if (!$carrinho) {
            $carrinho = Pedido::create([
                'user_id'     => Auth::id(),
                'status'      => 'carrinho',
                'valor_total' => 0
            ]);
        }

        // Composite key lookup: (produto_id, produto_variante_id) — null is a valid key value
        $itemExistente = ItemPedido::where('pedido_id', $carrinho->id)
            ->where('produto_id', $produto->id)
            ->where('produto_variante_id', $request->produto_variante_id)
            ->first();

        if ($itemExistente) {
            $novaQuantidade = $itemExistente->quantidade + $request->quantidade;

            if ($novaQuantidade > $estoqueEfetivo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quantidade total excede o estoque disponível.'
                ], 400);
            }

            // Only increment quantity — preco is NEVER refreshed (preserves price at time of add)
            $itemExistente->quantidade = $novaQuantidade;
            $itemExistente->save();
        } else {
            // Price: use variant's preco_efetivo if variant present; product price otherwise
            $precoGravado = $variante ? $variante->preco_efetivo : $produto->preco;

            // Build opcoes_snapshot server-side from valor IDs — never from frontend
            $snapshot = null;
            if ($variante) {
                $snapshot = [];
                $valorModels = \App\Models\ProdutoOpcaoValor::with('grupo')
                    ->whereIn('id', $variante->valores)
                    ->get()
                    ->keyBy('id');

                foreach ($variante->valores as $valorId) {
                    $valorModel = $valorModels->get($valorId);
                    if ($valorModel) {
                        $snapshot[$valorModel->grupo->nome] = $valorModel->valor;
                    }
                }
            }

            ItemPedido::create([
                'pedido_id'           => $carrinho->id,
                'produto_id'          => $produto->id,
                'produto_variante_id' => $variante?->id,
                'quantidade'          => $request->quantidade,
                'preco'               => $precoGravado,
                'opcoes_snapshot'     => $snapshot,
            ]);
        }

        $this->recalcularValorTotal($carrinho);
        $carrinho->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Produto adicionado ao carrinho com sucesso!',
            'carrinho' => [
                'total_itens' => $carrinho->itens()->sum('quantidade'),
                'valor_total' => $carrinho->valor_total,
            ],
            'produto' => [
                'id'         => $produto->id,
                'nome'       => $produto->nome,
                'quantidade' => $itemExistente ? $itemExistente->quantidade : $request->quantidade,
            ],
        ]);
    }

    /**
     * Remover produto do carrinho
     */
    public function remover(Request $request)
    {
        $request->validate([
            'produto_id'          => 'required|exists:produtos,id',
            'produto_variante_id' => 'nullable|exists:produto_variantes,id',
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
            ->where('produto_variante_id', $request->produto_variante_id)
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
            'produto_id'          => 'required|exists:produtos,id',
            'quantidade'          => 'required|integer|min:1|max:10',
            'produto_variante_id' => 'nullable|exists:produto_variantes,id',
        ]);

        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado.'
            ], 401);
        }

        $produto = Produto::find($request->produto_id);

        // Use estoque_efetivo when variant present; cross-FK: variante must belong to this produto
        $variante = null;
        if ($request->produto_variante_id) {
            $variante = \App\Models\ProdutoVariante::with('produto')
                ->where('id', $request->produto_variante_id)
                ->where('produto_id', $request->produto_id)
                ->first();

            if (!$variante) {
                return response()->json(['success' => false, 'message' => 'Variante inválida.'], 422);
            }

            $variante->setRelation('produto', $produto);
        }
        $estoqueEfetivo = $variante ? $variante->estoque_efetivo : $produto->estoque;

        if ($estoqueEfetivo < $request->quantidade) {
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
            ->where('produto_variante_id', $request->produto_variante_id)
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
            ->with(['itens.produto.imagens', 'itens.produtoVariante'])
            ->first();

        if (!$carrinho) {
            return response()->json([
                'success'  => true,
                'carrinho' => null
            ]);
        }

        return response()->json([
            'success' => true,
            'carrinho' => [
                'id'          => $carrinho->id,
                'valor_total' => $carrinho->valor_total,
                'itens'       => $carrinho->itens->map(function ($item) {
                    return [
                        'id'                  => $item->id,
                        'quantidade'          => $item->quantidade,
                        'preco'               => $item->preco,
                        'produto_variante_id' => $item->produto_variante_id,
                        'opcoes_snapshot'     => $item->opcoes_snapshot,
                        'produto' => [
                            'id'              => $item->produto->id,
                            'nome'            => $item->produto->nome,
                            'primeira_imagem' => $item->produto->primeira_imagem,
                        ],
                    ];
                }),
            ],
        ]);
    }

    /**
     * Verificar se produto está no carrinho
     */
    public function verificar_produto(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success'     => false,
                'no_carrinho' => false
            ]);
        }

        $request->validate([
            'produto_id'          => 'required|exists:produtos,id',
            'produto_variante_id' => 'nullable|exists:produto_variantes,id',
        ]);

        $carrinho = Pedido::where('user_id', Auth::id())
            ->where('status', 'carrinho')
            ->first();

        if (!$carrinho) {
            return response()->json([
                'success'     => true,
                'no_carrinho' => false
            ]);
        }

        $item = ItemPedido::where('pedido_id', $carrinho->id)
            ->where('produto_id', $request->produto_id)
            ->where('produto_variante_id', $request->produto_variante_id)
            ->first();

        return response()->json([
            'success'             => true,
            'no_carrinho'         => (bool) $item,
            'quantidade'          => $item ? $item->quantidade : 0,
            'produto_variante_id' => $item ? $item->produto_variante_id : null,
        ]);
    }

    /**
     * Recalcular valor total do carrinho
     */
    private function recalcularValorTotal($carrinho)
    {
        $subtotal = $carrinho->itens()->get()->sum(fn ($item) => $item->quantidade * $item->preco);
        $desconto = (float) ($carrinho->valor_desconto ?? 0);
        $carrinho->update(['valor_total' => max(0, $subtotal - $desconto)]);
    }
}
