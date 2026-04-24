<?php

namespace App\Http\Controllers;

use App\Enums\PedidoStatus;
use App\Models\Pedido;
use App\Models\Endereco;
use App\Models\ItemPedido;
use App\Models\User;
use App\Services\CheckoutOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PedidoController extends Controller
{
    public function __construct(
        protected CheckoutOrderService $checkoutOrderService
    ) {
    }

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
            ->where('status', PedidoStatus::CARRINHO)
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
            ->where('status', PedidoStatus::CARRINHO)
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
                'status' => PedidoStatus::PENDENTE
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pedido criado com sucesso!',
                'pedido_id' => $carrinho->id,
                'redirect_url' => route('site.checkout')
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
            ->where('status', PedidoStatus::PENDENTE)
            ->with(['itens.produto.imagens', 'endereco'])
            ->first();

        if (!$pedido) {
            return redirect()->route('site.produtos')->with('error', 'Pedido não encontrado ou não pertence a você.');
        }

        // Redirecionar para a view unificada de finalizar compra
        return redirect()->route('site.checkout');
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
            ->where('status', '!=', PedidoStatus::CARRINHO)
            ->with(['itens.produto.imagens', 'endereco'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('site.pedidos.index', compact('pedidos'));
    }

    /**
     * Exibe detalhes de um pedido
     */
    public function show(Request $request, Pedido $pedido)
    {
        $pedido->load(['itens.produto.imagens', 'endereco', 'pagamentos', 'user']);

        if (!$this->checkoutOrderService->canAccessOrder($request, $pedido)) {
            if (Auth::check()) {
                return redirect()->route('site.pedidos.index')->with('error', 'Pedido não encontrado.');
            }

            return redirect()->route('site.login')->with('error', 'Pedido não encontrado.');
        }

        return view('site.pedidos.show', compact('pedido'));
    }

    /**
     * Exibe a página dedicada de reembolso / garantia de fábrica
     */
    public function reembolso(Request $request, Pedido $pedido)
    {
        $pedido->load(['itens.produto.imagens', 'endereco', 'pagamentos']);

        if (!$this->checkoutOrderService->canAccessOrder($request, $pedido)) {
            if (Auth::check()) {
                return redirect()->route('site.pedidos.index')->with('error', 'Pedido não encontrado.');
            }
            return redirect()->route('site.login')->with('error', 'Pedido não encontrado.');
        }

        $statusesNaoAplicaveis = [PedidoStatus::CANCELADO, PedidoStatus::PENDENTE, PedidoStatus::CARRINHO];
        if (in_array($pedido->status, $statusesNaoAplicaveis, strict: true)) {
            return redirect()->route('site.pedidos.show', $pedido);
        }

        return view('site.pedidos.reembolso', compact('pedido'));
    }

    public function confirmarEntrega(Request $request, Pedido $pedido)
    {
        if (!$this->checkoutOrderService->canAccessOrder($request, $pedido)) {
            if (Auth::check()) {
                return redirect()->route('site.pedidos.index')->with('error', 'Pedido não encontrado.');
            }

            return redirect()->route('site.login')->with('error', 'Pedido não encontrado.');
        }

        if ($pedido->status !== PedidoStatus::ENVIADO) {
            return redirect($this->checkoutOrderService->orderUrl($pedido))
                ->with('error', 'A entrega só pode ser confirmada quando o pedido estiver enviado.');
        }

        $pedido->update([
            'status' => PedidoStatus::ENTREGUE,
        ]);

        return redirect($this->checkoutOrderService->orderUrl($pedido))
            ->with('success', 'Entrega confirmada com sucesso.');
    }

    public function cancelar(Request $request, Pedido $pedido)
    {
        if (!$this->checkoutOrderService->canAccessOrder($request, $pedido)) {
            if (Auth::check()) {
                return redirect()->route('site.pedidos.index')->with('error', 'Pedido não encontrado.');
            }
            return redirect()->route('site.login')->with('error', 'Pedido não encontrado.');
        }

        if ($pedido->status !== PedidoStatus::PENDENTE) {
            return redirect($this->checkoutOrderService->orderUrl($pedido))
                ->with('error', 'Apenas pedidos pendentes sem pagamento podem ser cancelados.');
        }

        if ($pedido->pagamentos()->exists()) {
            return redirect($this->checkoutOrderService->orderUrl($pedido))
                ->with('error', 'Não é possível cancelar um pedido com pagamento em andamento. Entre em contato pelo WhatsApp.');
        }

        $pedido->update(['status' => PedidoStatus::CANCELADO]);

        return redirect()->route('site.pedidos.index')
            ->with('success', 'Pedido #' . $pedido->id . ' cancelado com sucesso.');
    }

    public function createAccount(Request $request, Pedido $pedido)
    {
        if (!$this->checkoutOrderService->canAccessOrder($request, $pedido)) {
            abort(403);
        }

        if ($pedido->user_id !== null) {
            return redirect($this->checkoutOrderService->orderUrl($pedido));
        }

        if ($pedido->status !== PedidoStatus::PAGO) {
            return back()->with('error', 'A conta pode ser criada depois da confirmação do pagamento.');
        }

        $validated = $request->validate([
            'guest_token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed' => 'A confirmação da senha não confere.',
        ]);

        if (!$pedido->guest_token || !hash_equals($pedido->guest_token, $validated['guest_token'])) {
            return back()->with('error', 'Não foi possível validar esta compra para criação da conta.');
        }

        $existingUser = User::where('email', $pedido->customer_email)->first();
        if ($existingUser) {
            return back()->with('error', 'Já existe uma conta com este e-mail. Faça login para acompanhar seus pedidos.');
        }

        $user = DB::transaction(function () use ($pedido, $validated) {
            $user = User::create([
                'name' => $pedido->customer_name,
                'email' => $pedido->customer_email,
                'phone' => $pedido->customer_phone,
                'password' => Hash::make($validated['password']),
            ]);

            $pedido->update([
                'user_id' => $user->id,
                'checkout_mode' => 'authenticated',
            ]);

            if ($pedido->endereco) {
                $pedido->endereco->update([
                    'user_id' => $user->id,
                ]);
            }

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();
        $this->checkoutOrderService->clearGuestOrder($request);

        return redirect()->route('site.pedidos.show', $pedido)->with('success', 'Conta criada com sucesso. Seu pedido agora está vinculado ao seu perfil.');
    }
}
