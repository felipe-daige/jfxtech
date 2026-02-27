<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Produto;
use App\Models\Categoria;
use App\Models\Endereco;
use App\Models\Pedido;
use App\Models\ItemPedido;
use Illuminate\Validation\ValidationException;

class SiteController extends Controller
{
    /**
     * Página inicial
     */
    public function index()
    {
        // Buscar produtos em destaque (ativos e marcados como destaque)
        $produtos_destaque = Produto::where('ativo', true)
            ->where('destaque', true)
            ->with(['imagens', 'categoria'])
            ->limit(3)
            ->get();

        return view('site.index', compact('produtos_destaque'));
    }

    /**
     * Página de produtos
     */
    public function produtos(Request $request)
    {
        // Buscar todos os produtos ativos
        $query = Produto::where('ativo', true)
            ->with(['imagens', 'categoria']);

        // Filtro por múltiplas categorias
        if ($request->filled('categorias')) {
            $categorias_ids = is_array($request->categorias) ? $request->categorias : [$request->categorias];
            $query->whereIn('categoria_id', $categorias_ids);
        }

        // Filtro por preço
        if ($request->filled('preco_min')) {
            $query->where('preco', '>=', $request->preco_min);
        }
        if ($request->filled('preco_max')) {
            $query->where('preco', '<=', $request->preco_max);
        }

        // Busca por nome
        if ($request->filled('busca')) {
            $query->where('nome', 'like', '%' . $request->busca . '%');
        }

        // Ordenação
        $ordenacao = $request->get('ordenacao', 'nome');
        switch ($ordenacao) {
            case 'preco_asc':
                $query->orderBy('preco', 'asc');
                break;
            case 'preco_desc':
                $query->orderBy('preco', 'desc');
                break;
            case 'nome':
            default:
                $query->orderBy('nome', 'asc');
                break;
        }

        $produtos = $query->paginate(12);
        $categorias = Categoria::where('ativo', true)->get();

        // Verificar favoritos se usuário estiver logado
        $favoritosIds = [];
        if (Auth::check()) {
            $favoritosIds = Auth::user()->favoritos()->pluck('produto_id')->toArray();
        }

        // Verificar produtos no carrinho se usuário estiver logado
        $produtos_no_carrinho = [];
        if (Auth::check()) {
            $carrinho = Pedido::where('user_id', Auth::id())
                ->where('status', 'carrinho')
                ->first();
            
            if ($carrinho) {
                $produtos_no_carrinho = $carrinho->itens()
                    ->pluck('quantidade', 'produto_id')
                    ->toArray();
            }
        }

        return view('site.produtos', compact('produtos', 'categorias', 'favoritosIds', 'produtos_no_carrinho'));
    }

    /**
     * Página de detalhes do produto
     */
    public function produto_detalhes($slug)
    {
        $produto = Produto::where('slug', $slug)
            ->where('ativo', true)
            ->with(['imagens', 'categoria'])
            ->firstOrFail();

        // Buscar produtos relacionados (mesma categoria, excluindo o atual)
        $produtos_relacionados = Produto::where('ativo', true)
            ->where('categoria_id', $produto->categoria_id)
            ->where('id', '!=', $produto->id)
            ->with(['imagens', 'categoria'])
            ->limit(4)
            ->get();

        // Verificar se o produto está nos favoritos do usuário
        $favoritado = false;
        if (Auth::check()) {
            $favoritado = Auth::user()->favoritos()->where('produto_id', $produto->id)->exists();
        }

        // Verificar se o produto está no carrinho do usuário
        $produto_no_carrinho = false;
        $quantidade_no_carrinho = 0;
        if (Auth::check()) {
            $carrinho = Pedido::where('user_id', Auth::id())
                ->where('status', 'carrinho')
                ->first();
            
            if ($carrinho) {
                $item = ItemPedido::where('pedido_id', $carrinho->id)
                    ->where('produto_id', $produto->id)
                    ->first();
                
                if ($item) {
                    $produto_no_carrinho = true;
                    $quantidade_no_carrinho = $item->quantidade;
                }
            }
        }

        return view('site.produto-detalhes', compact('produto', 'produtos_relacionados', 'favoritado', 'produto_no_carrinho', 'quantidade_no_carrinho'));
    }

    /**
     * Página de contato
     */
    public function contato()
    {
        return view('site.contato');
    }

    /**
     * Exibir formulário de login
     */
    public function login_view(){
        return view('site.login');
    }

    /**
     * Exibir formulário de registro
     */
    public function register_view(){
        return view('site.register');
    }

    /**
     * Processar login do usuário
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8',
        ], [
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'Digite um email válido.',
            'password.required' => 'A senha é obrigatória.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->has('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            
            return redirect()->intended(route('site.index'))
                ->with('success', 'Login realizado com sucesso!');
        }

        return back()->withErrors([
            'email' => 'As credenciais fornecidas não conferem com nossos registros.',
        ])->withInput($request->except('password'));
    }

    /**
     * Processar registro do usuário
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'terms' => 'required|accepted',
        ], [
            'name.required' => 'O nome é obrigatório.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'Digite um email válido.',
            'email.unique' => 'Este email já está cadastrado.',
            'phone.required' => 'O telefone é obrigatório.',
            'password.required' => 'A senha é obrigatória.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed' => 'A confirmação da senha não confere.',
            'terms.required' => 'Você deve aceitar os termos de uso.',
            'terms.accepted' => 'Você deve aceitar os termos de uso.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);

        return redirect()->route('site.index')
            ->with('success', 'Conta criada com sucesso! Bem-vindo à MX Racing!');
    }

    /**
     * Logout do usuário
     */
    public function logout(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('site.index')
            ->with('success', 'Logout realizado com sucesso!');
    }

    /**
     * Página do perfil do usuário
     */
    public function perfil()
    {
        // Verificar se o usuário está logado
        if (!Auth::check()) {
            return redirect()->route('site.login')
                ->with('error', 'Você precisa estar logado para acessar seu perfil.');
        }

        $usuario = Auth::user();
        return view('site.perfil', compact('usuario'));
    }

    /**
     * Atualizar perfil do usuário
     */
    public function perfil_update(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado.'
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Validação adicional para telefone
        if ($request->filled('phone')) {
            $phoneDigits = preg_replace('/\D/', '', $request->phone);
            if (strlen($phoneDigits) < 10) {
                return response()->json([
                    'success' => false,
                    'errors' => [
                        'phone' => ['O telefone deve ter pelo menos 10 dígitos numéricos.']
                    ]
                ], 422);
            }
        }

        // Validação com regras específicas
        $request->validate([
            'phone' => [
                'nullable',
                'string',
                'regex:/^\(\d{2}\)\s\d{4,5}-\d{4}$/',
                'unique:users,phone,' . $user->id
            ],
            'current_password' => 'nullable|string',
            'new_password' => 'nullable|string|min:6|confirmed',
        ], [
            'phone.regex' => 'O telefone deve estar no formato (XX) XXXXX-XXXX ou (XX) XXXX-XXXX.',
            'phone.unique' => 'Este número de telefone já está sendo usado por outro usuário.',
            'new_password.min' => 'A nova senha deve ter pelo menos 6 caracteres.',
            'new_password.confirmed' => 'A confirmação da senha não confere.',
        ]);

        // Verificar se quer alterar senha
        if ($request->filled('new_password')) {
            if (!$request->filled('current_password')) {
                return response()->json([
                    'success' => false,
                    'errors' => [
                        'current_password' => ['Senha atual é obrigatória para alterar a senha']
                    ]
                ], 422);
            }

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'errors' => [
                        'current_password' => ['Senha atual incorreta']
                    ]
                ], 422);
            }
        }

        // Atualizar telefone
        if ($request->filled('phone')) {
            $user->phone = $request->phone;
        }

        // Atualizar senha
        if ($request->filled('new_password')) {
            $user->password = Hash::make($request->new_password);
        }

        // Salvar alterações
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Perfil atualizado com sucesso!',
            'user' => [
                'phone' => $user->phone
            ]
        ]);
    }

    /**
     * Exibe a página de finalizar compra
     */
    public function finalizar_compra()
    {
        // Verificar se usuário está logado
        if (!Auth::check()) {
            return redirect()->route('site.login')->with('error', 'Você precisa estar logado para finalizar a compra.');
        }

        // Verificar se há itens no carrinho
        $carrinho = Pedido::where('user_id', Auth::id())
            ->where('status', 'carrinho')
            ->with(['itens.produto.imagens'])
            ->first();

        if (!$carrinho || $carrinho->itens->isEmpty()) {
            return redirect()->route('site.produtos')->with('error', 'Seu carrinho está vazio.');
        }

        // Buscar endereços do usuário
        $enderecos = Endereco::where('user_id', Auth::id())->get();

        return view('site.finalizar-compra', compact('carrinho', 'enderecos'));
    }
}