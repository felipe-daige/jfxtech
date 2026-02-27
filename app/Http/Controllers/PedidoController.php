<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Endereco;
use App\Models\ItemPedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PedidoController extends Controller
{
    /**
     * Exibe a página de criação de pedido
     */
    public function create()
    {
        // Verificar se usuário está logado
        if (!Auth::check()) {
            return redirect()->route('site.login')->with('error', 'Você precisa estar logado para criar um pedido.');
        }

        // Verificar se há itens no carrinho
        $carrinho = Pedido::where('user_id', Auth::id())
            ->where('status', 'carrinho')
            ->with(['itens.produto.imagens'])
            ->first();

        if (!$carrinho || $carrinho->itens->isEmpty()) {
            return redirect()->route('site.carrinho')->with('error', 'Seu carrinho está vazio.');
        }

        // Buscar endereços do usuário
        $enderecos = Endereco::where('user_id', Auth::id())->get();

        return view('site.pedidos.create', compact('carrinho', 'enderecos'));
    }

    /**
     * Salva o endereço e cria o pedido
     */
    public function store(Request $request)
    {
        // Verificar se usuário está logado
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Você precisa estar logado para criar um pedido.'
            ], 401);
        }

        // Validar dados do endereço
        $request->validate([
            'cep' => 'required|string|max:9',
            'rua' => 'required|string|max:255',
            'numero' => 'required|string|max:10',
            'complemento' => 'nullable|string|max:255',
            'bairro' => 'required|string|max:100',
            'cidade' => 'required|string|max:100',
            'estado' => 'required|string|size:2',
            'pais' => 'nullable|string|size:2'
        ]);

        try {
            DB::beginTransaction();

            // Buscar carrinho atual
            $carrinho = Pedido::where('user_id', Auth::id())
                ->where('status', 'carrinho')
                ->first();

            if (!$carrinho || $carrinho->itens->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seu carrinho está vazio.'
                ], 400);
            }

            // Buscar ou criar endereço (evita duplicação)
            $endereco = Endereco::firstOrCreate(
                [
                    'user_id' => Auth::id(),
                    'cep' => $request->cep,
                    'rua' => $request->rua,
                    'numero' => $request->numero,
                    'bairro' => $request->bairro,
                    'cidade' => $request->cidade,
                    'estado' => $request->estado
                ],
                [
                    'complemento' => $request->complemento,
                    'pais' => $request->pais ?? 'BR'
                ]
            );

            // Atualizar pedido com endereço
            $carrinho->update([
                'endereco_id' => $endereco->id,
                'status' => 'pendente'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pedido criado com sucesso!',
                'pedido_id' => $carrinho->id,
                'redirect_url' => route('site.finalizar-compra')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibe a página de pagamento
     */
    public function pagamento($id)
    {
        // Verificar se usuário está logado
        if (!Auth::check()) {
            return redirect()->route('site.login')->with('error', 'Você precisa estar logado para acessar o pagamento.');
        }

        // Buscar pedido do usuário atual
        $pedido = Pedido::where('id', $id)
            ->where('user_id', Auth::id())
            ->where('status', 'pendente')
            ->with(['itens.produto.imagens', 'endereco'])
            ->first();

        if (!$pedido) {
            return redirect()->route('site.produtos')->with('error', 'Pedido não encontrado ou não pertence a você.');
        }

        // Redirecionar para a view unificada de finalizar compra
        return redirect()->route('site.finalizar-compra');
    }

    /**
     * Lista os pedidos do usuário
     */
    public function index()
    {
        // Verificar se usuário está logado
        if (!Auth::check()) {
            return redirect()->route('site.login')->with('error', 'Você precisa estar logado para ver seus pedidos.');
        }

        // Buscar pedidos do usuário
        $pedidos = Pedido::where('user_id', Auth::id())
            ->where('status', '!=', 'carrinho')
            ->with(['itens.produto.imagens', 'endereco'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('site.pedidos.index', compact('pedidos'));
    }

    /**
     * Exibe detalhes de um pedido
     */
    public function show($id)
    {
        // Verificar se usuário está logado
        if (!Auth::check()) {
            return redirect()->route('site.login')->with('error', 'Você precisa estar logado para ver seus pedidos.');
        }

        // Buscar pedido
        $pedido = Pedido::where('id', $id)
            ->where('user_id', Auth::id())
            ->with(['itens.produto.imagens', 'endereco'])
            ->first();

        if (!$pedido) {
            return redirect()->route('site.pedidos.index')->with('error', 'Pedido não encontrado.');
        }

        return view('site.pedidos.show', compact('pedido'));
    }
}
