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
use App\Enums\PedidoStatus;
use App\Services\CouponPartnerProgressService;
use App\Services\CheckoutOrderService;
use App\Services\CouponApplicationService;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

class SiteController extends Controller
{
    public function __construct(
        protected CheckoutOrderService $checkoutOrderService,
        protected CouponPartnerProgressService $couponPartnerProgressService,
        protected CouponApplicationService $couponApplicationService,
    ) {
    }

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

        if ($produtos_destaque->isEmpty()) {
            $produtos_destaque = Produto::where('ativo', true)
                ->with(['imagens', 'categoria'])
                ->latest()
                ->limit(3)
                ->get();
        }

        $categorias_preview = Categoria::where('ativo', true)
            ->withCount(['produtos as produtos_count' => fn($q) => $q->where('ativo', true)])
            ->with(['produtos' => fn($q) => $q->where('ativo', true)->with('imagens')->limit(4)])
            ->get()
            ->filter(fn($c) => $c->produtos_count > 0)
            ->values();

        return view('site.index', compact('produtos_destaque', 'categorias_preview'));
    }

    public function buscaRapida(Request $request)
    {
        $q = trim($request->get('q', ''));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $produtos = Produto::where('ativo', true)
            ->where('nome', 'ilike', '%' . $q . '%')
            ->with(['imagens' => fn($q) => $q->where('capa', true)->limit(1)])
            ->orderBy('destaque', 'desc')
            ->limit(6)
            ->get()
            ->map(fn($p) => [
                'nome'   => $p->nome,
                'slug'   => $p->slug,
                'preco'  => 'R$ ' . number_format($p->preco_com_desconto, 2, ',', '.'),
                'imagem' => $p->imagens->first()?->caminho,
            ]);

        return response()->json($produtos);
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
            $query->where('nome', 'ilike', '%' . $request->busca . '%');
        }

        // Filtro: apenas em promoção
        if ($request->boolean('em_promocao')) {
            $query->where('em_promocao', true);
        }

        // Filtro: apenas em estoque
        if ($request->boolean('em_estoque')) {
            $query->where('estoque', '>', 0);
        }

        // Filtro: apenas em destaque
        if ($request->boolean('em_destaque')) {
            $query->where('destaque', true);
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
            case 'destaque':
                $query->orderBy('destaque', 'desc')->orderBy('nome', 'asc');
                break;
            case 'novidade':
                $query->orderBy('created_at', 'desc');
                break;
            case 'promocao':
                $query->orderBy('em_promocao', 'desc')->orderBy('preco', 'asc');
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

        $categoriaAtiva = null;
        if ($request->filled('categorias')) {
            $ids = is_array($request->categorias) ? $request->categorias : [$request->categorias];
            $categoriaAtiva = Categoria::whereIn('id', $ids)->pluck('nome')->implode(', ');
        }
        $metaDescription = $categoriaAtiva
            ? "{$categoriaAtiva} — catálogo completo com os melhores produtos gamer. JFXTech."
            : 'Catálogo completo de hardware gamer: monitores, teclados, mouses e mais. Produtos originais com garantia. JFXTech.';

        return view('site.produtos', compact('produtos', 'categorias', 'favoritosIds', 'produtos_no_carrinho', 'metaDescription'));
    }

    /**
     * Página de detalhes do produto
     */
    public function produto_detalhes($slug)
    {
        $produto = Produto::where('slug', $slug)
            ->where('ativo', true)
            ->with(['imagens', 'categoria', 'imagemCapa', 'opcaoGrupos.valores', 'variantesAtivas.produto', 'variantesAtivas.imagens'])
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

        $variantesData = $produto->variantesAtivas->map(fn($v) => [
            'id'                => $v->id,
            'valores'           => $v->valores,
            'preco_efetivo'     => $v->preco_efetivo,
            'estoque_efetivo'   => $v->estoque_efetivo,
            'ativo'             => $v->ativo,
            'imagem_ids'        => $v->imagens->pluck('id')->all(),
            'descricao_efetiva' => $v->descricao_efetiva,   // own or inherited from product
            'specs_efetivos'    => $v->specs_efetivos,       // own or inherited from product
        ])->values()->toArray();

        return view('site.produto-detalhes', compact('produto', 'produtos_relacionados', 'favoritado', 'produto_no_carrinho', 'quantidade_no_carrinho', 'variantesData'));
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
        return response()
            ->view('site.login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
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
        $guestCart = $this->checkoutOrderService->resolveGuestCart($request, ['itens.produto', 'itens.produtoVariante']);

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            $this->checkoutOrderService->mergeGuestCartIntoUser($request, Auth::id(), $guestCart);
            
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
        $this->checkoutOrderService->mergeGuestCartIntoUser($request, $user->id);

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
        $enderecos = Endereco::where('user_id', $usuario->id)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();
        $pedidosRecentes = $usuario->pedidos()
            ->where('status', '!=', 'carrinho')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
        $totalPedidos = $usuario->pedidos()->where('status', '!=', 'carrinho')->count();
        $totalFavoritos = $usuario->favoritos()->count();
        $canAccessCouponPortal = $this->userCanAccessCouponPortal($usuario);

        return view('site.perfil', compact('usuario', 'enderecos', 'pedidosRecentes', 'totalPedidos', 'totalFavoritos', 'canAccessCouponPortal'));
    }

    public function cupom()
    {
        if (!Auth::check()) {
            return redirect()->route('site.login');
        }

        /** @var User $usuario */
        $usuario = Auth::user();

        if (!$this->userCanAccessCouponPortal($usuario)) {
            return redirect()->route('site.perfil')
                ->with('error', 'Seu portal de cupom ainda não está liberado.');
        }

        $cupons = $usuario->cupons()->orderBy('codigo')->get();
        // $codes é derivado dos mesmos cupons já carregados — evita segunda query ao service
        $codes = $cupons->pluck('codigo')->filter()->values();
        $progress = $this->couponPartnerProgressService->progressForUser($usuario);

        $allSales = Pedido::query()
            ->where('status', PedidoStatus::PAGO)
            ->whereIn('cupom_codigo', $codes->all())
            ->orderByDesc('created_at')
            ->get(['id', 'cupom_codigo', 'valor_total', 'valor_desconto', 'created_at']);

        $taxa = $progress['current_rate'] / 100;
        $totalLiquido = $allSales->sum('valor_total');
        $comissaoAcumulada = $totalLiquido * $taxa;
        $mediaPorPedido = $allSales->count() > 0
            ? $totalLiquido / $allSales->count()
            : 0.0;

        // Barra de progressão dentro do tier atual
        $currentTier = collect($progress['tiers'])
            ->first(fn($t) => $t['rate'] === $progress['current_rate'])
            ?? $progress['tiers'][0];
        $tierMin = $currentTier['min'];
        $tierMax = $currentTier['max'];

        if ($tierMax === null) {
            $progressPct = 100;
        } else {
            $range = $tierMax - $tierMin;
            $progressPct = $range > 0
                ? (int) round(($progress['total_sales'] - $tierMin) / $range * 100)
                : 100;
            $progressPct = min(100, max(0, $progressPct));
        }

        return view('site.cupom', compact(
            'usuario', 'cupons', 'progress', 'allSales',
            'taxa', 'totalLiquido', 'comissaoAcumulada', 'mediaPorPedido', 'progressPct'
        ));
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

        // Pré-processamento de CPF: strip de não-dígitos + normalização antes da validação
        if ($request->filled('cpf')) {
            $cpfDigits = preg_replace('/\D/', '', $request->cpf);
            if (strlen($cpfDigits) !== 11) {
                return response()->json([
                    'success' => false,
                    'errors'  => ['cpf' => ['O CPF deve ter exatamente 11 dígitos numéricos.']],
                ], 422);
            }
            $request->merge(['cpf' => $cpfDigits]);
        }

        // Validação com regras específicas
        $request->validate([
            'name' => 'nullable|string|min:2|max:255',
            'phone' => [
                'nullable',
                'string',
                'regex:/^\(\d{2}\)\s\d{4,5}-\d{4}$/',
                'unique:users,phone,' . $user->id
            ],
            'cpf' => ['nullable', 'string', 'size:11', 'unique:users,cpf,' . $user->id],
            'current_password' => 'nullable|string',
            'new_password' => 'nullable|string|min:6|confirmed',
        ], [
            'name.min' => 'O nome deve ter pelo menos 2 caracteres.',
            'phone.regex' => 'O telefone deve estar no formato (XX) XXXXX-XXXX ou (XX) XXXX-XXXX.',
            'phone.unique' => 'Este número de telefone já está sendo usado por outro usuário.',
            'cpf.size' => 'O CPF deve ter exatamente 11 dígitos.',
            'cpf.unique' => 'Este CPF já está cadastrado em outra conta.',
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

        // Atualizar nome
        if ($request->filled('name')) {
            $user->name = $request->name;
        }

        // Atualizar telefone
        if ($request->filled('phone')) {
            $user->phone = $request->phone;
        }

        // Atualizar CPF
        if ($request->filled('cpf')) {
            $user->cpf = $request->cpf; // já normalizado para 11 dígitos na pré-validação
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
                'name'  => $user->name,
                'phone' => $user->phone,
                'cpf'   => $user->cpf,
            ]
        ]);
    }

    public function endereco_store(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado.'
            ], 401);
        }

        $validated = $this->validateEndereco($request);

        $endereco = Endereco::create([
            ...$validated,
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Endereço salvo com sucesso.',
            'endereco' => $this->formatEnderecoResponse($endereco->fresh()),
        ]);
    }

    public function endereco_update(Request $request, Endereco $endereco): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado.'
            ], 401);
        }

        if ((int) $endereco->user_id !== (int) Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Endereço não encontrado.'
            ], 404);
        }

        $validated = $this->validateEndereco($request);
        $endereco->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Endereço atualizado com sucesso.',
            'endereco' => $this->formatEnderecoResponse($endereco->fresh()),
        ]);
    }

    public function endereco_destroy(Endereco $endereco): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado.'
            ], 401);
        }

        if ((int) $endereco->user_id !== (int) Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Endereço não encontrado.'
            ], 404);
        }

        if ($endereco->pedidos()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Este endereço já foi usado em pedido e não pode ser removido.'
            ], 422);
        }

        $enderecoId = $endereco->id;
        $endereco->delete();

        return response()->json([
            'success' => true,
            'message' => 'Endereço removido com sucesso.',
            'deleted_id' => $enderecoId,
        ]);
    }

    /**
     * Exibe a página de finalizar compra
     */
    public function checkout()
    {
        $request = request();
        $carrinho = $this->checkoutOrderService->resolveActiveOrder($request, ['itens.produto.imagens']);

        if (!$carrinho || $carrinho->itens->isEmpty()) {
            return redirect()->route('site.produtos')->with('error', 'Seu carrinho está vazio.');
        }

        $pendingCoupon = $request->session()->get(CouponApplicationService::SESSION_PENDING_COUPON);

        if ($pendingCoupon) {
            $result = $this->couponApplicationService->applyToOrder($carrinho, $pendingCoupon, Auth::id());
            $request->session()->forget(CouponApplicationService::SESSION_PENDING_COUPON);

            if ($result['success']) {
                $carrinho->refresh()->load('itens.produto.imagens');
            }
        }

        $enderecos = Auth::check()
            ? Endereco::where('user_id', Auth::id())
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get()
            : collect();

        return view('site.checkout', compact('carrinho', 'enderecos'));
    }

    private function validateEndereco(Request $request): array
    {
        return $request->validate([
            'cep' => 'required|string|max:9',
            'rua' => 'required|string|max:255',
            'numero' => 'required|string|max:20',
            'complemento' => 'nullable|string|max:255',
            'bairro' => 'required|string|max:100',
            'cidade' => 'required|string|max:100',
            'estado' => 'required|string|size:2',
            'pais' => 'nullable|string|size:2',
        ], [
            'cep.required' => 'Informe o CEP.',
            'rua.required' => 'Informe a rua.',
            'numero.required' => 'Informe o número.',
            'bairro.required' => 'Informe o bairro.',
            'cidade.required' => 'Informe a cidade.',
            'estado.required' => 'Selecione o estado.',
            'estado.size' => 'O estado deve ter 2 caracteres.',
        ]);
    }

    private function formatEnderecoResponse(Endereco $endereco): array
    {
        return [
            'id' => $endereco->id,
            'cep' => $endereco->cep,
            'cep_formatado' => $endereco->cep_formatado,
            'rua' => $endereco->rua,
            'numero' => $endereco->numero,
            'complemento' => $endereco->complemento,
            'bairro' => $endereco->bairro,
            'cidade' => $endereco->cidade,
            'estado' => $endereco->estado,
            'pais' => $endereco->pais,
            'endereco_completo' => $endereco->endereco_completo,
        ];
    }

    private function userCanAccessCouponPortal(User $user): bool
    {
        return $user->coupon_portal_enabled && $user->cupons()->exists();
    }
}
