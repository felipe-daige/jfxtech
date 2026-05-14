<?php

namespace App\Http\Controllers;

use App\Enums\PedidoStatus;
use App\Exports\ProdutosExport;
use App\Models\Categoria;
use App\Models\Cupom;
use App\Models\CustoOperacional;
use App\Models\Fornecedor;
use App\Models\ItemPedido;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\ProdutoFornecedorOferta;
use App\Models\ProdutoImagem;
use App\Models\ProdutoVariante;
use App\Services\CouponPartnerProgressService;
use App\Services\PromotionSimulationService;
use App\Support\ProdutoDescricaoFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class AdminController extends Controller
{
    // Dashboard administrativo
    public function dashboard()
    {
        if (! Auth::check()) {
            return redirect()->route('site.login');
        }

        return view('admin.dashboard', $this->getDashboardAnalyticsData());
    }

    public function analytics()
    {
        if (! Auth::check()) {
            return redirect()->route('site.login');
        }

        return view('admin.analytics', $this->getDashboardAnalyticsData());
    }

    public function analyticsProductSearch(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $term = trim((string) $request->query('q', ''));
        if ($term === '') {
            return response()->json(['products' => []]);
        }

        $prefix = $term.'%';
        $contains = '%'.$term.'%';
        $likeOperator = \DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $likeKeyword = \DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        $products = Produto::query()
            ->select(['id', 'nome', 'marca', 'slug', 'ativo'])
            ->where(function ($query) use ($contains, $likeOperator) {
                $query
                    ->where('nome', $likeOperator, $contains)
                    ->orWhere('marca', $likeOperator, $contains)
                    ->orWhere('slug', $likeOperator, $contains);
            })
            ->orderByRaw(
                "CASE
                    WHEN nome {$likeKeyword} ? THEN 0
                    WHEN COALESCE(marca, '') {$likeKeyword} ? THEN 1
                    WHEN slug {$likeKeyword} ? THEN 2
                    ELSE 3
                END",
                [$prefix, $prefix, $prefix]
            )
            ->orderBy('nome')
            ->limit(8)
            ->get()
            ->map(fn (Produto $produto) => [
                'id' => $produto->id,
                'name' => $produto->nome,
                'brand' => $produto->marca,
                'slug' => $produto->slug,
                'active' => (bool) $produto->ativo,
                'url' => route('admin.analytics.products.show', $produto),
            ])
            ->values();

        return response()->json(['products' => $products]);
    }

    public function analyticsProductShow(Request $request, Produto $produto)
    {
        if (! Auth::check()) {
            return redirect()->route('site.login');
        }

        $period = $this->resolveAnalyticsPeriod((string) $request->query('period', '30'));
        $couponFilter = $this->normalizeCouponFilter($request->query('cupom'));

        return view('admin.analytics-produto', array_merge(
            $this->getProductAnalyticsData($produto, $period, $couponFilter),
            ['time_series' => $this->getProductTimeSeries($produto, $period, $couponFilter)]
        ));
    }

    public function storeCustoOperacional(Request $request)
    {
        if (! Auth::check()) {
            return redirect()->route('site.login');
        }

        $validated = $request->validate([
            'tipo' => ['required', Rule::in(array_keys($this->operationalCostTypes()))],
            'nome' => ['required', 'string', 'max:120'],
            'valor' => ['required', 'string'],
            'data_referencia' => ['nullable', 'date'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
        ]);

        $valor = $this->parseMoneyInput($validated['valor']);
        if ($valor === null || $valor <= 0) {
            return redirect()->back()->withErrors(['valor' => 'Informe um valor maior que zero.'])->withInput();
        }

        CustoOperacional::create([
            'created_by' => Auth::id(),
            'tipo' => $validated['tipo'],
            'nome' => trim((string) $validated['nome']),
            'valor' => round($valor, 2),
            'data_referencia' => $validated['data_referencia'] ?? now()->toDateString(),
            'observacoes' => $this->nullableString($validated['observacoes'] ?? null),
        ]);

        return redirect()->route('admin.analytics')->with('success', 'Custo operacional registrado com sucesso.');
    }

    public function destroyCustoOperacional(int $id)
    {
        if (! Auth::check()) {
            return redirect()->route('site.login');
        }

        CustoOperacional::findOrFail($id)->delete();

        return redirect()->route('admin.analytics')->with('success', 'Custo operacional removido.');
    }

    public function simulatePromotion(Request $request, PromotionSimulationService $simulationService)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'exists:produtos,id'],
            'period_days' => ['required', Rule::in([30, 90, 365])],
            'discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'extra_unit_cost' => ['nullable', 'numeric', 'min:0'],
            'extra_order_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $simulation = $simulationService->simulate(
            $validated['product_ids'],
            (int) $validated['period_days'],
            (float) $validated['discount_percent'],
            (float) ($validated['extra_unit_cost'] ?? 0),
            (float) ($validated['extra_order_cost'] ?? 0),
        );

        return response()->json($simulation);
    }

    public function simuladorPromocao()
    {
        if (! Auth::check()) {
            return redirect()->route('site.login');
        }

        $produtos_analytics = Produto::select(
            'id', 'nome', 'marca', 'preco', 'preco_original', 'custo_compra', 'frete_compra',
            'desconto_percentual', 'em_promocao', 'ativo'
        )->orderBy('nome')->get();

        $promotion_simulator_periods = [30, 90, 365];
        $promotion_simulator_default_period = 30;

        return view('admin.simulador-promocao', compact(
            'produtos_analytics',
            'promotion_simulator_periods',
            'promotion_simulator_default_period'
        ));
    }

    // Gerenciar produtos
    public function produtos(Request $request)
    {
        if (! Auth::check()) {
            return redirect()->route('site.login');
        }

        $query = Produto::with('categoria', 'imagens');

        // Aplicar filtro de pesquisa se fornecido
        if ($request->filled('pesquisa')) {
            $pesquisa = $request->pesquisa;
            $query->where('nome', 'ilike', "%{$pesquisa}%");
        }

        // Aplicar filtro de status (ativo/inativo)
        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'ativo') {
                $query->where('ativo', true);
            } elseif ($status === 'inativo') {
                $query->where('ativo', false);
            }
        }

        // Aplicar filtro de destaque
        if ($request->filled('destaque')) {
            $destaque = $request->destaque;
            if ($destaque === 'sim') {
                $query->where('destaque', true);
            } elseif ($destaque === 'nao') {
                $query->where('destaque', false);
            }
        }

        // Aplicar filtro de categoria
        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        $perPage = min((int) $request->get('per_page', 24), 96);
        $viewMode = in_array($request->get('view_mode'), ['cards', 'lista']) ? $request->get('view_mode') : 'cards';
        $produtos = $query->orderBy('created_at', 'desc')->paginate($perPage);
        $categorias = Categoria::all();

        return view('admin.produtos', compact('produtos', 'categorias', 'perPage', 'viewMode'));
    }

    // Pesquisar produtos via AJAX
    public function pesquisarProdutos(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $query = Produto::with('categoria', 'imagens');

        // Aplicar filtro de pesquisa se fornecido
        if ($request->filled('pesquisa')) {
            $pesquisa = $request->pesquisa;
            $query->where('nome', 'ilike', "%{$pesquisa}%");
        }

        // Aplicar filtro de status (ativo/inativo)
        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'ativo') {
                $query->where('ativo', true);
            } elseif ($status === 'inativo') {
                $query->where('ativo', false);
            }
        }

        // Aplicar filtro de destaque
        if ($request->filled('destaque')) {
            $destaque = $request->destaque;
            if ($destaque === 'sim') {
                $query->where('destaque', true);
            } elseif ($destaque === 'nao') {
                $query->where('destaque', false);
            }
        }

        // Aplicar filtro de categoria
        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        $perPage = min((int) $request->get('per_page', 24), 96);
        $viewMode = in_array($request->get('view_mode'), ['cards', 'lista']) ? $request->get('view_mode') : 'cards';
        $produtos = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $partial = $viewMode === 'lista' ? 'admin.includes.produtos-lista-linhas' : 'admin.includes.produtos-lista';
        $html = view($partial, compact('produtos'))->render();

        $categorias = \App\Models\Categoria::all();
        $paginacaoHtml = view('admin.includes.paginacao', [
            'produtos' => $produtos,
            'perPage' => $perPage,
            'pesquisa' => $request->get('pesquisa'),
            'categoriaId' => $request->get('categoria_id'),
            'categorias' => $categorias,
        ])->render();

        return response()->json([
            'html' => $html,
            'paginacao_html' => $paginacaoHtml,
            'total' => $produtos->total(),
            'current_page' => $produtos->currentPage(),
            'last_page' => $produtos->lastPage(),
            'view_mode' => $viewMode,
        ]);
    }

    // Página de detalhes completos do produto
    public function verProduto(int $id)
    {
        if (! Auth::check()) {
            return redirect()->route('site.login');
        }

        $produto = Produto::with([
            'categoria',
            'imagens' => fn ($q) => $q->orderBy('ordem')->orderBy('id'),
            'variantes',
            'opcaoGrupos.opcaoValores',
        ])->findOrFail($id);

        $categorias = \App\Models\Categoria::orderBy('nome')->get();

        return view('admin.produto-ver', compact('produto', 'categorias'));
    }

    // Buscar dados do produto para edição
    public function buscarProduto($id)
    {
        $produto = Produto::with('categoria', 'imagens')->findOrFail($id);

        return response()->json([
            'id' => $produto->id,
            'nome' => $produto->nome,
            'marca' => $produto->marca,
            'descricao' => $produto->descricao,
            'descricao_curta' => $produto->descricao_curta,
            'preco' => $produto->em_promocao && $produto->preco_original ?
                       number_format($produto->preco_original, 2, ',', '.') :
                       number_format($produto->preco, 2, ',', '.'),
            'custo_compra' => $produto->custo_compra !== null ? number_format($produto->custo_compra, 2, ',', '.') : '',
            'frete_compra' => $produto->frete_compra !== null ? number_format($produto->frete_compra, 2, ',', '.') : '',
            'peso' => $produto->peso ? number_format($produto->peso, 3, ',', '.') : '',
            'desconto_percentual' => $produto->desconto_percentual,
            'em_promocao' => $produto->em_promocao,
            'destaque' => $produto->destaque,
            'tags' => $produto->tags ?? [],
            'estoque' => $produto->estoque,
            'categoria_id' => $produto->categoria_id,
            'ativo' => $produto->ativo,
            'specs' => $produto->specs ?? [],
            'imagens' => $produto->imagens()->orderBy('ordem')->orderBy('id')->get()->map(function ($imagem) {
                return [
                    'id' => $imagem->id,
                    'caminho' => $imagem->caminho,
                    'url' => asset('storage/'.$imagem->caminho),
                    'capa' => $imagem->capa,
                ];
            }),
        ]);
    }

    // Criar produto
    public function criarProduto(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'marca' => 'nullable|string|max:100',
            'descricao' => 'required|string',
            'descricao_curta' => 'nullable|string',
            'preco' => 'required|string',
            'custo_compra' => 'nullable|string',
            'frete_compra' => 'nullable|string',
            'peso' => 'nullable|numeric|min:0',
            'estoque' => 'required|integer|min:0',
            'categoria_id' => 'required|exists:categorias,id',
            'em_promocao' => 'nullable|boolean',
            'desconto_percentual' => 'nullable|numeric|min:0|max:100',
            'destaque' => 'nullable|boolean',
            'tags' => 'nullable|array|max:2',
            'tags.*' => 'string|max:50',
            'imagens.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        // Converter preço do formato brasileiro para decimal
        $preco = $this->parseMoneyInput($request->preco);

        // Validar se o preço convertido é um número válido
        if (! is_numeric($preco) || $preco < 0) {
            return redirect()->back()->withErrors(['preco' => 'Preço deve ser um valor válido maior ou igual a zero.'])->withInput();
        }

        $custoCompra = $this->parseMoneyInput($request->custo_compra);
        if ($request->filled('custo_compra') && (! is_numeric($custoCompra) || $custoCompra < 0)) {
            return redirect()->back()->withErrors(['custo_compra' => 'Custo de compra deve ser um valor válido maior ou igual a zero.'])->withInput();
        }

        $freteCompra = $this->parseMoneyInput($request->frete_compra);
        if ($request->filled('frete_compra') && (! is_numeric($freteCompra) || $freteCompra < 0)) {
            return redirect()->back()->withErrors(['frete_compra' => 'Frete de compra deve ser um valor válido maior ou igual a zero.'])->withInput();
        }

        // Processar campos de promoção
        $emPromocao = $request->has('em_promocao');
        $descontoPercentual = $request->desconto_percentual ?? null;

        // Calcular preço final se estiver em promoção
        $precoFinal = $preco;
        if ($emPromocao && $descontoPercentual > 0) {
            $precoFinal = $preco * (1 - $descontoPercentual / 100);
        }

        // Processar campo destaque
        $destaque = $request->boolean('destaque');

        // Validar limite de produtos em destaque (máximo 3)
        if ($destaque) {
            $produtosDestaque = Produto::where('destaque', true)->count();
            if ($produtosDestaque >= 3) {
                return redirect()->back()->withErrors(['destaque' => 'Limite máximo de 3 produtos em destaque atingido.'])->withInput();
            }
        }

        $specs = array_filter($request->input('specs', []), fn ($v) => $v !== null && $v !== '');
        $descricao = ProdutoDescricaoFormatter::sanitize($request->descricao);

        if (ProdutoDescricaoFormatter::toPlainText($descricao) === '') {
            return redirect()->back()->withErrors(['descricao' => 'Descrição deve conter conteúdo visível.'])->withInput();
        }

        $produto = Produto::create([
            'nome' => $request->nome,
            'marca' => $request->filled('marca') ? trim((string) $request->marca) : null,
            'descricao' => $descricao,
            'descricao_curta' => $request->filled('descricao_curta') ? trim((string) $request->descricao_curta) : null,
            'preco' => $precoFinal, // Preço final (com desconto se aplicável)
            'custo_compra' => $request->filled('custo_compra') ? $custoCompra : null,
            'frete_compra' => $request->filled('frete_compra') ? $freteCompra : null,
            'peso' => $request->peso, // Peso em KG
            'preco_original' => $emPromocao ? $preco : null, // Preço original (sem desconto)
            'desconto_percentual' => $descontoPercentual,
            'em_promocao' => $emPromocao,
            'destaque' => $destaque,
            'tags' => $request->input('tags', []),
            'estoque' => $request->estoque,
            'categoria_id' => $request->categoria_id,
            'specs' => ! empty($specs) ? $specs : null,
            'ativo' => true, // Produtos são ativos por padrão
        ]);

        // Upload de imagens
        if ($request->hasFile('imagens')) {
            $primeiraImagem = true;
            foreach ($request->file('imagens') as $imagem) {
                $caminho = $imagem->store('produtos', 'public');
                ProdutoImagem::create([
                    'produto_id' => $produto->id,
                    'caminho' => $caminho,
                    'capa' => $primeiraImagem,
                ]);
                $primeiraImagem = false; // Apenas a primeira imagem será capa
            }
        }

        return redirect()->route('admin.produtos')->with('success', 'Produto criado com sucesso!');
    }

    // Editar produto
    public function editarProduto(Request $request, $id)
    {
        $produto = Produto::findOrFail($id);

        $request->validate([
            'nome' => 'required|string|max:255',
            'marca' => 'nullable|string|max:100',
            'descricao' => 'required|string',
            'descricao_curta' => 'nullable|string',
            'preco' => 'required|string',
            'custo_compra' => 'nullable|string',
            'frete_compra' => 'nullable|string',
            'peso' => 'nullable|numeric|min:0',
            'estoque' => 'required|integer|min:0',
            'categoria_id' => 'required|exists:categorias,id',
            'em_promocao' => 'nullable|boolean',
            'desconto_percentual' => 'nullable|numeric|min:0|max:100',
            'destaque' => 'nullable|boolean',
            'tags' => 'nullable|array|max:2',
            'tags.*' => 'string|max:50',
            'imagens.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'imagem_capa_id' => 'nullable|exists:produto_imagens,id',
        ]);

        // Converter preço do formato brasileiro para decimal
        $preco = $this->parseMoneyInput($request->preco);

        // Validar se o preço convertido é um número válido
        if (! is_numeric($preco) || $preco < 0) {
            return redirect()->back()->withErrors(['preco' => 'Preço deve ser um valor válido maior ou igual a zero.'])->withInput();
        }

        $custoCompra = $this->parseMoneyInput($request->custo_compra);
        if ($request->filled('custo_compra') && (! is_numeric($custoCompra) || $custoCompra < 0)) {
            return redirect()->back()->withErrors(['custo_compra' => 'Custo de compra deve ser um valor válido maior ou igual a zero.'])->withInput();
        }

        $freteCompra = $this->parseMoneyInput($request->frete_compra);
        if ($request->filled('frete_compra') && (! is_numeric($freteCompra) || $freteCompra < 0)) {
            return redirect()->back()->withErrors(['frete_compra' => 'Frete de compra deve ser um valor válido maior ou igual a zero.'])->withInput();
        }

        // Processar campos de promoção
        $emPromocao = $request->has('em_promocao');
        $descontoPercentual = $request->desconto_percentual ?? null;

        // Calcular preço final se estiver em promoção
        $precoFinal = $preco;
        if ($emPromocao && $descontoPercentual > 0) {
            $precoFinal = $preco * (1 - $descontoPercentual / 100);
        }

        // Processar campo destaque
        $destaque = $request->boolean('destaque');

        // Validar limite de produtos em destaque (máximo 3)
        if ($destaque) {
            $produtosDestaque = Produto::where('destaque', true)->where('id', '!=', $id)->count();
            if ($produtosDestaque >= 3) {
                return redirect()->back()->withErrors(['destaque' => 'Limite máximo de 3 produtos em destaque atingido.'])->withInput();
            }
        }

        $specs = array_filter($request->input('specs', []), fn ($v) => $v !== null && $v !== '');
        $descricao = ProdutoDescricaoFormatter::sanitize($request->descricao);

        if (ProdutoDescricaoFormatter::toPlainText($descricao) === '') {
            return redirect()->back()->withErrors(['descricao' => 'Descrição deve conter conteúdo visível.'])->withInput();
        }

        $produto->update([
            'nome' => $request->nome,
            'marca' => $request->filled('marca') ? trim((string) $request->marca) : null,
            'descricao' => $descricao,
            'descricao_curta' => $request->filled('descricao_curta') ? trim((string) $request->descricao_curta) : null,
            'preco' => $precoFinal, // Preço final (com desconto se aplicável)
            'custo_compra' => $request->filled('custo_compra') ? $custoCompra : null,
            'frete_compra' => $request->filled('frete_compra') ? $freteCompra : null,
            'peso' => $request->peso, // Peso em KG
            'preco_original' => $emPromocao ? $preco : null, // Preço original (sem desconto)
            'desconto_percentual' => $descontoPercentual,
            'em_promocao' => $emPromocao,
            'destaque' => $destaque,
            'tags' => $request->input('tags', []),
            'estoque' => $request->estoque,
            'categoria_id' => $request->categoria_id,
            'specs' => ! empty($specs) ? $specs : null,
        ]);

        // Upload de novas imagens
        if ($request->hasFile('imagens')) {
            $maxOrdem = $produto->imagens()->max('ordem') ?? 0;
            foreach ($request->file('imagens') as $index => $imagem) {
                $caminho = $imagem->store('produtos', 'public');
                ProdutoImagem::create([
                    'produto_id' => $produto->id,
                    'caminho' => $caminho,
                    'ordem' => $maxOrdem + $index + 1,
                ]);
            }
        }

        // Definir imagem de capa se especificada
        if ($request->imagem_capa_id) {
            // Remover capa anterior se existir
            $produto->imagens()->update(['capa' => false]);

            // Definir nova capa
            $imagemCapa = ProdutoImagem::where('id', $request->imagem_capa_id)
                ->where('produto_id', $produto->id)
                ->first();
            if ($imagemCapa) {
                $imagemCapa->update(['capa' => true]);
            }
        }

        return redirect()->route('admin.produtos')->with('success', 'Produto atualizado com sucesso!');
    }

    // Alterar status do produto (ativo/inativo)
    public function alterarStatusProduto(Request $request, $id)
    {
        $produto = Produto::findOrFail($id);

        $request->validate([
            'ativo' => 'required|boolean',
        ]);

        $produto->update(['ativo' => $request->ativo]);

        $status = $request->ativo ? 'ativado' : 'desativado';

        return redirect()->route('admin.produtos')->with('success', "Produto {$status} com sucesso!");
    }

    // Alterar destaque do produto
    public function alterarDestaqueProduto(Request $request, $id)
    {
        $produto = Produto::findOrFail($id);

        $request->validate([
            'destaque' => 'required|boolean',
        ]);

        // Validar limite de produtos em destaque (máximo 3)
        if ($request->destaque) {
            $produtosDestaque = Produto::where('destaque', true)->where('id', '!=', $id)->count();
            if ($produtosDestaque >= 3) {
                return redirect()->back()->withErrors(['destaque' => 'Limite máximo de 3 produtos em destaque atingido.']);
            }
        }

        $produto->update(['destaque' => $request->destaque]);

        $status = $request->destaque ? 'adicionado ao destaque' : 'removido do destaque';

        return redirect()->route('admin.produtos')->with('success', "Produto {$status} com sucesso!");
    }

    // Excluir produto
    public function excluirProduto($id)
    {
        $produto = Produto::findOrFail($id);

        // Excluir imagens do storage
        foreach ($produto->imagens as $imagem) {
            Storage::disk('public')->delete($imagem->caminho);
        }

        $produto->delete();

        return redirect()->route('admin.produtos')->with('success', 'Produto excluído com sucesso!');
    }

    // Excluir imagem individual de produto
    public function excluirImagem($id)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        try {
            $imagem = ProdutoImagem::findOrFail($id);
            $eraCapa = $imagem->capa;
            $produtoId = $imagem->produto_id;

            Storage::disk('public')->delete($imagem->caminho);
            $imagem->delete();

            // Se era capa, promover a imagem mais antiga restante
            if ($eraCapa) {
                $outra = ProdutoImagem::where('produto_id', $produtoId)->oldest()->first();
                if ($outra) {
                    $outra->update(['capa' => true]);
                }
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Download de imagem individual de produto
    public function downloadImagem($id)
    {
        if (! Auth::check()) {
            return redirect()->route('site.login');
        }

        $imagem = ProdutoImagem::findOrFail($id);

        if (! Storage::disk('public')->exists($imagem->caminho)) {
            abort(404, 'Arquivo não encontrado');
        }

        return Storage::disk('public')->download($imagem->caminho);
    }

    // Substituir imagem individual de produto
    public function substituirImagem(Request $request, $id)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $request->validate([
            'imagem' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $imagem = ProdutoImagem::findOrFail($id);
        $eraCapa = $imagem->capa;

        Storage::disk('public')->delete($imagem->caminho);

        $novoCaminho = $request->file('imagem')->store('produtos', 'public');
        $imagem->update(['caminho' => $novoCaminho]);

        return response()->json([
            'success' => true,
            'url' => asset('storage/'.$novoCaminho),
            'capa' => $eraCapa,
        ]);
    }

    public function reordenarImagens(Request $request, $produtoId)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $request->validate([
            'ordem' => 'required|array',
            'ordem.*.id' => 'required|integer|exists:produto_imagens,id',
            'ordem.*.posicao' => 'required|integer|min:0',
        ]);

        foreach ($request->ordem as $item) {
            ProdutoImagem::where('id', $item['id'])
                ->where('produto_id', $produtoId)
                ->update(['ordem' => $item['posicao']]);
        }

        return response()->json(['success' => true]);
    }

    public function bulkActionProdutos(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $action = $request->input('action'); // 'delete' | 'ativar' | 'desativar' | 'set_exclusivo' | 'remove_exclusivo' | 'set_em_breve' | 'remove_em_breve'
        $ids = $request->input('ids', []);

        if (empty($ids) || ! in_array($action, ['delete', 'ativar', 'desativar', 'set_exclusivo', 'remove_exclusivo', 'set_em_breve', 'remove_em_breve'])) {
            return response()->json(['error' => 'Requisição inválida'], 422);
        }

        $count = count($ids);

        switch ($action) {
            case 'delete':
                $produtos = Produto::with('imagens')->whereIn('id', $ids)->get();
                foreach ($produtos as $produto) {
                    foreach ($produto->imagens as $imagem) {
                        Storage::disk('public')->delete($imagem->caminho);
                        $imagem->delete();
                    }
                    $produto->delete();
                }
                break;
            case 'ativar':
                Produto::whereIn('id', $ids)->update(['ativo' => 1]);
                break;
            case 'desativar':
                Produto::whereIn('id', $ids)->update(['ativo' => 0]);
                break;
            case 'set_exclusivo':
                $produtos = Produto::whereIn('id', $ids)->get();
                foreach ($produtos as $produto) {
                    $tags = $produto->tags ?? [];
                    if (! in_array('Exclusivo', $tags)) {
                        $tags[] = 'Exclusivo';
                        // Limitar a apenas 2 tags (preservando a mais recente)
                        if (count($tags) > 2) {
                            $tags = array_slice($tags, -2);
                        }
                        $produto->update(['tags' => $tags]);
                    }
                }
                break;
            case 'remove_exclusivo':
                $produtos = Produto::whereIn('id', $ids)->get();
                foreach ($produtos as $produto) {
                    $tags = $produto->tags ?? [];
                    if (($key = array_search('Exclusivo', $tags)) !== false) {
                        unset($tags[$key]);
                        $produto->update(['tags' => array_values($tags)]);
                    }
                }
                break;
            case 'set_em_breve':
                $produtos = Produto::whereIn('id', $ids)->get();
                foreach ($produtos as $produto) {
                    $tags = $produto->tags ?? [];
                    if (! in_array('Em Breve', $tags)) {
                        $tags[] = 'Em Breve';
                        if (count($tags) > 2) {
                            $tags = array_slice($tags, -2);
                        }
                        $produto->update(['tags' => $tags]);
                    }
                }
                break;
            case 'remove_em_breve':
                $produtos = Produto::whereIn('id', $ids)->get();
                foreach ($produtos as $produto) {
                    $tags = $produto->tags ?? [];
                    if (($key = array_search('Em Breve', $tags)) !== false) {
                        unset($tags[$key]);
                        $produto->update(['tags' => array_values($tags)]);
                    }
                }
                break;
        }

        return response()->json(['success' => true, 'count' => $count]);
    }

    // Gerenciar pedidos
    public function pedidos(Request $request)
    {
        if (! Auth::check()) {
            return redirect()->route('site.login');
        }

        $query = Pedido::with('user', 'itens.produto')->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('data')) {
            $query->whereDate('created_at', $request->input('data'));
        }

        $couponFilter = $this->normalizeCouponFilter($request->query('cupom'));
        $this->applyCouponFilterToPedidoQuery($query, $couponFilter);

        $pedidos = $query->paginate(10)->withQueryString();
        $cost_comparison = $this->buildCostComparisonAnalytics(null, null, $couponFilter);
        $cupomOptions = $this->couponFilterOptions();

        return view('admin.pedidos', compact('pedidos', 'cost_comparison', 'cupomOptions', 'couponFilter'));
    }

    // Retorna HTML dos detalhes do pedido para o modal
    public function detalhesPedido(Request $request, $id)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $pedido = Pedido::with([
            'user',
            'endereco',
            'itens.produto.imagens',
            'itens.produtoVariante',
            'pagamentos',
        ])->findOrFail($id);

        $analytics = $this->buildOrderDetailAnalytics($pedido);

        if (! $request->expectsJson() && ! $request->ajax()) {
            return view('admin.pedido-detalhes', compact('pedido', 'analytics'));
        }

        $html = view('admin.includes.pedido-detalhes', array_merge(
            compact('pedido', 'analytics'),
            ['isFullOrderPage' => false]
        ))->render();

        return response()->json(['html' => $html]);
    }

    public function preparacaoCustosPedido($id)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $pedido = Pedido::with([
            'itens.produto:id,nome,custo_compra,frete_compra',
            'itens.produtoVariante:id,produto_id,custo_compra,frete_compra',
        ])->findOrFail($id);

        return response()->json([
            'pedido_id' => $pedido->id,
            'nota_fiscal_imagem_url' => $pedido->nota_fiscal_imagem_path
                ? Storage::disk('public')->url($pedido->nota_fiscal_imagem_path)
                : null,
            'items' => $pedido->itens->map(function (ItemPedido $item) {
                $suggestedCost = $this->resolveCatalogItemCost($item);
                $declaredCost = $item->custo_unitario_declarado !== null
                    ? round((float) $item->custo_unitario_declarado, 2)
                    : null;

                return [
                    'id' => $item->id,
                    'produto_nome' => $item->produto?->nome ?? 'Produto removido',
                    'variant_label' => $this->buildVariantLabel($item),
                    'quantidade' => (int) $item->quantidade,
                    'suggested_cost' => $suggestedCost,
                    'declared_cost' => $declaredCost,
                    'value' => $declaredCost ?? $suggestedCost,
                    'status_preparacao' => $item->status_preparacao_efetivo,
                    'status_preparacao_label' => ItemPedido::preparationStatusLabel($item->status_preparacao_efetivo),
                    'source' => $declaredCost !== null ? 'declarado' : $this->resolveCatalogItemCostSource($item),
                ];
            })->values(),
        ]);
    }

    public function atualizarPreparacaoPedido(Request $request, $id)
    {
        if (! Auth::check()) {
            return redirect()->route('site.login');
        }

        $pedido = Pedido::with(['itens.produto', 'itens.produtoVariante'])->findOrFail($id);

        $request->validate([
            'nota_fiscal_imagem' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ]);

        $preparationData = $this->collectPreparationItemData($pedido, $request);
        $updates = [];

        if ($request->hasFile('nota_fiscal_imagem')) {
            if ($pedido->nota_fiscal_imagem_path) {
                Storage::disk('public')->delete($pedido->nota_fiscal_imagem_path);
            }

            $updates['nota_fiscal_imagem_path'] = $request->file('nota_fiscal_imagem')->store('notas-fiscais', 'public');
        }

        DB::transaction(function () use ($pedido, $updates, $preparationData) {
            $this->persistPreparationItemData($pedido, $preparationData);

            if ($updates !== []) {
                $pedido->update($updates);
            }
        });

        return redirect()
            ->route('admin.pedidos.detalhes', $pedido)
            ->with('success', 'Dados de preparação atualizados com sucesso.');
    }

    // Atualizar status do pedido
    public function atualizarStatusPedido(Request $request, $id)
    {
        $pedido = Pedido::with(['itens.produto', 'itens.produtoVariante'])->findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', Rule::in(PedidoStatus::adminValues())],
            'codigo_rastreio' => ['nullable', 'string', 'max:50', Rule::requiredIf(fn () => $request->input('status') === PedidoStatus::ENVIADO)],
            'nota_fiscal_imagem' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ]);

        $updates = ['status' => $validated['status']];

        if ($validated['status'] === PedidoStatus::ENVIADO) {
            $updates['codigo_rastreio'] = strtoupper(trim((string) $validated['codigo_rastreio']));
        }

        $preparationData = [];
        if ($validated['status'] === PedidoStatus::PROCESSANDO) {
            $preparationData = $this->collectPreparationItemData($pedido, $request);

            if ($request->hasFile('nota_fiscal_imagem')) {
                if ($pedido->nota_fiscal_imagem_path) {
                    Storage::disk('public')->delete($pedido->nota_fiscal_imagem_path);
                }

                $updates['nota_fiscal_imagem_path'] = $request->file('nota_fiscal_imagem')->store('notas-fiscais', 'public');
            }
        }

        DB::transaction(function () use ($pedido, $updates, $preparationData) {
            if ($preparationData !== []) {
                $this->persistPreparationItemData($pedido, $preparationData);
            }

            $pedido->update($updates);
        });

        return redirect()->route('admin.pedidos')->with('success', 'Status do pedido atualizado com sucesso!');
    }

    // Gerenciar categorias
    public function categorias()
    {
        if (! Auth::check()) {
            return redirect()->route('site.login');
        }

        $categorias = Categoria::withCount('produtos')->orderBy('nome')->get();

        return view('admin.categorias', compact('categorias'));
    }

    // Criar categoria
    public function criarCategoria(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255|unique:categorias,nome',
            'descricao' => 'nullable|string',
        ]);

        Categoria::create([
            'nome' => $request->nome,
            'descricao' => $request->descricao,
        ]);

        return redirect()->route('admin.categorias')->with('success', 'Categoria criada com sucesso!');
    }

    // Editar categoria
    public function editarCategoria(Request $request, $id)
    {
        $categoria = Categoria::findOrFail($id);

        $request->validate([
            'nome' => 'required|string|max:255|unique:categorias,nome,'.$id,
            'descricao' => 'nullable|string',
        ]);

        $categoria->update([
            'nome' => $request->nome,
            'descricao' => $request->descricao,
        ]);

        return redirect()->route('admin.categorias')->with('success', 'Categoria atualizada com sucesso!');
    }

    // Excluir categoria
    public function excluirCategoria($id)
    {
        $categoria = Categoria::findOrFail($id);

        // Verificar se há produtos nesta categoria
        if ($categoria->produtos()->count() > 0) {
            return redirect()->route('admin.categorias')->with('error', 'Não é possível excluir categoria que possui produtos!');
        }

        $categoria->delete();

        return redirect()->route('admin.categorias')->with('success', 'Categoria excluída com sucesso!');
    }

    public function exportarProdutos(Request $request)
    {
        if (! Auth::check()) {
            abort(403);
        }

        $ids = $request->input('ids', []);

        return Excel::download(new ProdutosExport($ids), 'produtos-jfx-'.now()->format('Y-m-d').'.xlsx');
    }

    public function exportarAnalyticsCsv()
    {
        if (! Auth::check()) {
            return redirect()->route('site.login');
        }

        $analytics = $this->getDashboardAnalyticsData();
        $filename = 'analytics-dashboard-jfx-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($analytics) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Analytics Dashboard', 'Valor'], ';');
            fputcsv($handle, ['Exportado em', now()->format('d/m/Y H:i')], ';');
            fputcsv($handle, [], ';');

            fputcsv($handle, ['Resumo', 'Valor'], ';');
            foreach ($this->analyticsSummaryRows($analytics) as $row) {
                fputcsv($handle, $row, ';');
            }

            fputcsv($handle, [], ';');
            fputcsv($handle, ['Status dos Pedidos', 'Quantidade'], ';');
            foreach ($this->analyticsOrderStatusRows($analytics) as $row) {
                fputcsv($handle, $row, ';');
            }

            fputcsv($handle, [], ';');
            fputcsv($handle, ['Alertas', 'Quantidade'], ';');
            foreach ($this->analyticsAlertRows($analytics) as $row) {
                fputcsv($handle, $row, ';');
            }

            fputcsv($handle, [], ';');
            fputcsv($handle, ['Custos Operacionais Recentes'], ';');
            fputcsv($handle, ['Data', 'Tipo', 'Nome', 'Valor'], ';');
            foreach ($analytics['custos_operacionais'] as $custo) {
                fputcsv($handle, [
                    $custo->data_referencia->format('d/m/Y'),
                    $analytics['custo_operacional_tipos'][$custo->tipo] ?? ucfirst($custo->tipo),
                    $custo->nome,
                    $this->formatCurrency((float) $custo->valor),
                ], ';');
            }

            fputcsv($handle, [], ';');
            fputcsv($handle, ['Produtos Analytics'], ';');
            fputcsv($handle, ['Nome', 'Marca', 'Preco Venda', 'Custo', 'Lucro Unit.', 'Margem %', 'Estoque', 'Status'], ';');

            foreach ($analytics['produtos_analytics'] as $produto) {
                fputcsv($handle, [
                    $produto->nome,
                    $produto->marca ?? '—',
                    $this->formatCurrency($produto->preco_com_desconto),
                    $produto->custo_efetivo !== null ? $this->formatCurrency($produto->custo_efetivo) : '—',
                    $produto->lucro_bruto_unitario !== null ? $this->formatCurrency($produto->lucro_bruto_unitario) : '—',
                    $produto->margem_bruta_percentual !== null ? $this->formatPercent($produto->margem_bruta_percentual) : 'Sem custo',
                    (string) $produto->estoque,
                    $produto->ativo ? 'Ativo' : 'Inativo',
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportarAnalyticsPdf()
    {
        if (! Auth::check()) {
            return redirect()->route('site.login');
        }

        $analytics = $this->getDashboardAnalyticsData();
        $analytics['exportedAt'] = now();

        $pdf = Pdf::loadView('admin.exports.dashboard-analytics-pdf', [
            'analytics' => $analytics,
            'summaryRows' => $this->analyticsSummaryRows($analytics),
            'orderStatusRows' => $this->analyticsOrderStatusRows($analytics),
            'alertRows' => $this->analyticsAlertRows($analytics),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('analytics-dashboard-jfx-'.now()->format('Y-m-d-His').'.pdf');
    }

    public function buscarOpcoesProduto($id)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $produto = Produto::with(['opcaoGrupos.valores', 'variantes.imagens', 'imagens'])->findOrFail($id);

        // Build a valor map for label resolution
        $valorMap = [];
        foreach ($produto->opcaoGrupos as $grupo) {
            foreach ($grupo->valores as $valor) {
                $valorMap[$valor->id] = ['grupo' => $grupo->nome, 'valor' => $valor->valor];
            }
        }

        $variantes = $produto->variantes
            ->filter(function ($v) use ($valorMap) {
                // Exclude variants with broken valor references (ghost variants)
                foreach ($v->valores ?? [] as $vid) {
                    if (! isset($valorMap[$vid])) {
                        return false;
                    }
                }

                return true;
            })
            ->values()
            ->map(function ($v) use ($valorMap) {
                $label = collect($v->valores)->map(fn ($vid) => $valorMap[$vid]['valor'] ?? '?')->join(' / ');

                return [
                    'id' => $v->id,
                    'valores' => $v->valores,
                    'preco' => $v->preco,
                    'custo_compra' => $v->custo_compra,
                    'frete_compra' => $v->frete_compra,
                    'estoque' => $v->estoque,
                    'ativo' => $v->ativo,
                    'descricao' => $v->descricao,   // null if inheriting from product
                    'specs' => $v->specs,       // null if inheriting from product
                    'label' => $label,
                    'imagem_ids' => $v->imagens->pluck('id')->all(),
                ];
            });

        return response()->json([
            'grupos' => $produto->opcaoGrupos->map(fn ($g) => [
                'id' => $g->id,
                'nome' => $g->nome,
                'ordem' => $g->ordem,
                'valores' => $g->valores->map(fn ($v) => [
                    'id' => $v->id,
                    'valor' => $v->valor,
                    'ordem' => $v->ordem,
                ]),
            ]),
            'variantes' => $variantes,
            'estoque_compartilhado' => $produto->estoque_compartilhado,
            'imagens' => $produto->imagens->map(fn ($img) => [
                'id' => $img->id,
                'url' => asset('storage/'.$img->caminho),
            ]),
        ]);
    }

    public function listarFornecedores(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $query = Fornecedor::query()->orderBy('nome')->orderBy('id');

        $term = trim((string) $request->query('q', ''));
        if ($term !== '') {
            $operator = \DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($term, $operator) {
                $q->where('nome', $operator, "%{$term}%")
                    ->orWhere('email', $operator, "%{$term}%")
                    ->orWhere('telefone', $operator, "%{$term}%")
                    ->orWhere('whatsapp', $operator, "%{$term}%");
            });
        }

        return response()->json([
            'fornecedores' => $query->limit(100)->get()->map(fn (Fornecedor $fornecedor) => $this->formatFornecedorPayload($fornecedor)),
        ]);
    }

    public function buscarProdutoFornecedores($id)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $produto = Produto::with(['fornecedorOfertas.fornecedor'])->findOrFail($id);

        return response()->json([
            'fornecedores' => Fornecedor::query()
                ->orderBy('nome')
                ->orderBy('id')
                ->limit(100)
                ->get()
                ->map(fn (Fornecedor $fornecedor) => $this->formatFornecedorPayload($fornecedor)),
            'ofertas' => $produto->fornecedorOfertas
                ->sortBy(fn (ProdutoFornecedorOferta $oferta) => $oferta->fornecedor?->nome ?? '')
                ->values()
                ->map(fn (ProdutoFornecedorOferta $oferta) => $this->formatFornecedorOfertaPayload($oferta)),
        ]);
    }

    public function salvarProdutoFornecedores(Request $request, $id)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $produto = Produto::findOrFail($id);

        $validated = $request->validate([
            'ofertas' => ['nullable', 'array'],
            'ofertas.*.fornecedor_id' => ['nullable', 'integer', 'exists:fornecedores,id'],
            'ofertas.*.fornecedor' => ['nullable', 'array'],
            'ofertas.*.fornecedor.nome' => ['nullable', 'string', 'max:255'],
            'ofertas.*.fornecedor.perfil_url' => ['nullable', 'url', 'max:2048'],
            'ofertas.*.fornecedor.site_url' => ['nullable', 'url', 'max:2048'],
            'ofertas.*.fornecedor.email' => ['nullable', 'email', 'max:255'],
            'ofertas.*.fornecedor.telefone' => ['nullable', 'string', 'max:50'],
            'ofertas.*.fornecedor.whatsapp' => ['nullable', 'string', 'max:50'],
            'ofertas.*.fornecedor.contato_nome' => ['nullable', 'string', 'max:255'],
            'ofertas.*.fornecedor.pais' => ['nullable', 'string', 'max:80'],
            'ofertas.*.fornecedor.observacoes' => ['nullable', 'string'],
            'ofertas.*.preco_compra' => ['nullable', 'numeric', 'min:0'],
            'ofertas.*.frete_compra' => ['nullable', 'numeric', 'min:0'],
            'ofertas.*.moeda' => ['nullable', 'string', 'max:10'],
            'ofertas.*.quantidade_minima' => ['nullable', 'integer', 'min:1'],
            'ofertas.*.prazo_dias' => ['nullable', 'integer', 'min:0'],
            'ofertas.*.url_produto' => ['nullable', 'url', 'max:2048'],
            'ofertas.*.sku_fornecedor' => ['nullable', 'string', 'max:255'],
            'ofertas.*.observacoes' => ['nullable', 'string'],
            'ofertas.*.cotado_em' => ['nullable', 'date'],
            'ofertas.*.ativo' => ['nullable', 'boolean'],
        ]);

        $ofertas = $validated['ofertas'] ?? [];
        $idsManter = [];

        \DB::transaction(function () use ($ofertas, $produto, &$idsManter) {
            foreach ($ofertas as $ofertaData) {
                $fornecedorData = $this->normalizeFornecedorData($ofertaData['fornecedor'] ?? []);
                $ofertaPayload = $this->normalizeFornecedorOfertaData($ofertaData);
                $fornecedorId = $ofertaData['fornecedor_id'] ?? null;

                $hasFornecedorData = collect($fornecedorData)->filter(fn ($value) => filled($value))->isNotEmpty();
                $hasOfertaData = collect($ofertaPayload)
                    ->except('ativo')
                    ->filter(fn ($value) => filled($value) || $value === 0 || $value === 0.0)
                    ->isNotEmpty();

                if (! $fornecedorId && ! $hasFornecedorData && ! $hasOfertaData) {
                    continue;
                }

                if (! $fornecedorId && ! $hasFornecedorData) {
                    abort(response()->json(['message' => 'Informe um fornecedor para salvar a oferta.'], 422));
                }

                $fornecedor = $fornecedorId
                    ? Fornecedor::findOrFail($fornecedorId)
                    : Fornecedor::create($fornecedorData + ['ativo' => true]);

                if ($fornecedorId && $hasFornecedorData) {
                    $fornecedor->update($fornecedorData);
                }

                $oferta = ProdutoFornecedorOferta::updateOrCreate(
                    [
                        'produto_id' => $produto->id,
                        'fornecedor_id' => $fornecedor->id,
                    ],
                    $ofertaPayload
                );

                $idsManter[] = $oferta->id;
            }

            $query = $produto->fornecedorOfertas();
            if (! empty($idsManter)) {
                $query->whereNotIn('id', $idsManter)->delete();
            } else {
                $query->delete();
            }
        });

        $produto->load(['fornecedorOfertas.fornecedor']);

        return response()->json([
            'success' => true,
            'ofertas' => $produto->fornecedorOfertas
                ->sortBy(fn (ProdutoFornecedorOferta $oferta) => $oferta->fornecedor?->nome ?? '')
                ->values()
                ->map(fn (ProdutoFornecedorOferta $oferta) => $this->formatFornecedorOfertaPayload($oferta)),
        ]);
    }

    public function salvarOpcaoGrupos(Request $request, $id)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $produto = Produto::findOrFail($id);

        $request->validate([
            'grupos' => 'array',
            'grupos.*.nome' => 'required|string|max:50',
            'grupos.*.ordem' => 'nullable|integer',
            'grupos.*.valores' => 'nullable|array',
            'grupos.*.valores.*.valor' => 'required|string|max:100',
            'grupos.*.valores.*.ordem' => 'nullable|integer',
        ]);

        // Validate unique names per produto
        $nomes = collect($request->grupos)->pluck('nome');
        if ($nomes->unique()->count() !== $nomes->count()) {
            return response()->json(['error' => 'Nomes de grupo devem ser únicos por produto.'], 422);
        }

        \DB::transaction(function () use ($request, $produto) {
            // Collect IDs of all current values (before deletion)
            $currentValorIds = $produto->opcaoGrupos()
                ->with('valores')
                ->get()
                ->flatMap(fn ($g) => $g->valores->pluck('id'))
                ->all();

            // Collect IDs that will survive (values whose groups/names are in the request)
            $nomesNovos = collect($request->grupos)->pluck('nome')->all();
            $valoresNoRequest = collect($request->grupos)->flatMap(fn ($g) => collect($g['valores'] ?? [])->pluck('valor'))->all();

            // Get IDs of values being kept (groups in request + values in those groups)
            $idsAManter = $produto->opcaoGrupos()
                ->with('valores')
                ->whereIn('nome', $nomesNovos)
                ->get()
                ->flatMap(fn ($g) => $g->valores->whereIn('valor', $valoresNoRequest)->pluck('id'))
                ->all();

            // Delete (or deactivate if referenced by orders) variants that contain any valor ID being removed
            $idsRemovidos = array_diff($currentValorIds, $idsAManter);
            if (! empty($idsRemovidos)) {
                foreach ($produto->variantes()->get() as $variante) {
                    $variantValorIds = $variante->valores ?? [];
                    if (! empty(array_intersect($variantValorIds, $idsRemovidos))) {
                        $hasOrders = \App\Models\ItemPedido::where('produto_variante_id', $variante->id)->exists();
                        if ($hasOrders) {
                            $variante->update(['ativo' => false]); // preserve FK for order history
                        } else {
                            $variante->delete(); // hard-delete if no orders reference it
                        }
                    }
                }
            }

            // Delete groups not in this request (by name)
            $produto->opcaoGrupos()->whereNotIn('nome', $nomesNovos)->delete();

            foreach ($request->grupos as $idx => $grupoData) {
                $grupo = $produto->opcaoGrupos()->updateOrCreate(
                    ['nome' => $grupoData['nome']],
                    ['ordem' => $grupoData['ordem'] ?? $idx]
                );

                if (! empty($grupoData['valores'])) {
                    $valoresNovos = collect($grupoData['valores'])->pluck('valor')->all();
                    $grupo->valores()->whereNotIn('valor', $valoresNovos)->delete();

                    foreach ($grupoData['valores'] as $vIdx => $valorData) {
                        $grupo->valores()->updateOrCreate(
                            ['valor' => $valorData['valor']],
                            ['ordem' => $valorData['ordem'] ?? $vIdx]
                        );
                    }
                }
            }
        });

        return response()->json(['success' => true]);
    }

    public function gerarVariantes($id)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $produto = Produto::with('opcaoGrupos.valores')->findOrFail($id);
        $grupos = $produto->opcaoGrupos;

        if ($grupos->isEmpty()) {
            return response()->json(['success' => true, 'geradas' => 0]);
        }

        // Build cartesian product of valor_id arrays
        $sets = $grupos->map(fn ($g) => $g->valores->pluck('id')->all())->all();
        $combinacoes = $this->cartesianProduct($sets);

        $criadas = 0;
        \DB::transaction(function () use ($produto, $combinacoes, &$criadas) {
            foreach ($combinacoes as $combo) {
                sort($combo); // always sorted for consistent comparison
                $valoresJson = json_encode($combo);

                // Check if variant already exists (compare sorted JSON)
                $driver = \DB::getDriverName();
                if ($driver === 'pgsql') {
                    $existe = $produto->variantes()
                        ->whereRaw('valores::text = ?', [$valoresJson])
                        ->exists();
                } else {
                    // SQLite (tests): cast to text via JSON function
                    $existe = $produto->variantes()
                        ->whereRaw('CAST(valores AS TEXT) = ?', [$valoresJson])
                        ->exists();
                }

                if (! $existe) {
                    ProdutoVariante::create([
                        'produto_id' => $produto->id,
                        'valores' => $combo,
                        'preco' => null,
                        'custo_compra' => null,
                        'frete_compra' => null,
                        'estoque' => null,
                        'ativo' => true,
                    ]);
                    $criadas++;
                }
            }
        });

        return response()->json(['success' => true, 'geradas' => $criadas]);
    }

    private function cartesianProduct(array $sets): array
    {
        $result = [[]];
        foreach ($sets as $set) {
            $newResult = [];
            foreach ($result as $existing) {
                foreach ($set as $item) {
                    $newResult[] = array_merge($existing, [$item]);
                }
            }
            $result = $newResult;
        }

        return $result;
    }

    public function salvarVariantes(Request $request, $id)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $produto = Produto::findOrFail($id);

        $request->validate([
            'estoque_compartilhado' => 'nullable|boolean',
            'variantes' => 'nullable|array',
            'variantes.*.id' => 'required|integer',
            'variantes.*.preco' => 'nullable|numeric|min:0',
            'variantes.*.custo_compra' => 'nullable|numeric|min:0',
            'variantes.*.frete_compra' => 'nullable|numeric|min:0',
            'variantes.*.estoque' => 'nullable|integer|min:0',
            'variantes.*.ativo' => 'nullable|boolean',
            'variantes.*.imagem_ids' => 'nullable|array',
            'variantes.*.imagem_ids.*' => 'integer',
            'variantes.*.descricao' => 'nullable|string',
            'variantes.*.specs' => 'nullable|array',
            'variantes.*.specs.*' => 'nullable|string',
        ]);

        \DB::transaction(function () use ($request, $produto) {
            if ($request->has('estoque_compartilhado')) {
                $produto->update(['estoque_compartilhado' => $request->boolean('estoque_compartilhado')]);
            }

            foreach ($request->input('variantes', []) as $varData) {
                $variante = ProdutoVariante::where('id', $varData['id'])
                    ->where('produto_id', $produto->id)
                    ->first();
                if (! $variante) {
                    continue;
                } // skip gracefully — variant deleted/replaced by rename

                $update = [];
                if (array_key_exists('preco', $varData)) {
                    $update['preco'] = $varData['preco'];
                }
                if (array_key_exists('custo_compra', $varData)) {
                    $update['custo_compra'] = $varData['custo_compra'];
                }
                if (array_key_exists('frete_compra', $varData)) {
                    $update['frete_compra'] = $varData['frete_compra'];
                }
                if (array_key_exists('estoque', $varData)) {
                    $update['estoque'] = $varData['estoque'];
                }
                if (array_key_exists('ativo', $varData)) {
                    $update['ativo'] = $varData['ativo'];
                }

                if (array_key_exists('descricao', $varData)) {
                    $raw = $varData['descricao'];
                    if ($raw === null || $raw === '' || ProdutoDescricaoFormatter::toPlainText((string) $raw) === '') {
                        $update['descricao'] = null;
                    } else {
                        $update['descricao'] = ProdutoDescricaoFormatter::sanitize($raw);
                    }
                }

                if (array_key_exists('specs', $varData)) {
                    $filtered = array_filter((array) ($varData['specs'] ?? []), fn ($v) => $v !== null && $v !== '');
                    $update['specs'] = ! empty($filtered) ? $filtered : null;
                }

                if (! empty($update)) {
                    $variante->update($update);
                }

                if (array_key_exists('imagem_ids', $varData)) {
                    $imagem_ids = array_filter((array) ($varData['imagem_ids'] ?? []), 'is_numeric');
                    $variante->imagens()->sync($imagem_ids);
                }
            }
        });

        return response()->json(['success' => true]);
    }

    private function parseMoneyInput(?string $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);
        if ($normalized === '') {
            return null;
        }

        $normalized = str_replace(['R$', ' '], '', $normalized);
        if (str_contains($normalized, ',')) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $normalized)) {
            $normalized = str_replace('.', '', $normalized);
        }

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function collectPreparationItemData(Pedido $pedido, Request $request, bool $useCatalogCosts = false): array
    {
        $pedido->loadMissing(['itens.produto', 'itens.produtoVariante']);

        $errors = [];
        $items = [];

        foreach ($pedido->itens as $item) {
            $rawItem = data_get($request->input('itens', []), (string) $item->id, []);
            $status = $rawItem['status_preparacao']
                ?? ($useCatalogCosts ? ItemPedido::STATUS_PREPARACAO_CONFIRMADO : $item->status_preparacao_efetivo);
            $status = in_array($status, ItemPedido::preparationStatuses(), true)
                ? $status
                : ItemPedido::STATUS_PREPARACAO_PENDENTE;
            $rawValue = $rawItem['custo_unitario_declarado'] ?? null;
            $cost = null;

            if ($status === ItemPedido::STATUS_PREPARACAO_CANCELADO) {
                $items[$item->id] = [
                    'status_preparacao' => $status,
                    'custo_unitario_declarado' => null,
                ];

                continue;
            }

            if (($rawValue === null || trim((string) $rawValue) === '') && $useCatalogCosts) {
                $cost = $this->resolveCatalogItemCost($item);
            } else {
                $cost = $this->parseMoneyInput($rawValue !== null ? (string) $rawValue : null);
            }

            if ($cost === null || ! is_numeric($cost) || $cost < 0) {
                $errors['itens.'.$item->id.'.custo_unitario_declarado'] = 'Informe o custo real unitário deste item.';

                continue;
            }

            $items[$item->id] = [
                'status_preparacao' => $status,
                'custo_unitario_declarado' => round((float) $cost, 2),
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $items;
    }

    private function persistPreparationItemData(Pedido $pedido, array $items): void
    {
        $pedido->loadMissing('itens');

        foreach ($pedido->itens as $item) {
            if (! array_key_exists($item->id, $items)) {
                continue;
            }

            $data = $items[$item->id];
            $status = $data['status_preparacao'];

            $item->update([
                'custo_unitario_declarado' => $data['custo_unitario_declarado'],
                'custo_declarado_em' => $data['custo_unitario_declarado'] !== null ? now() : null,
                'status_preparacao' => $status,
                'status_preparacao_em' => $status !== $item->status_preparacao_efetivo ? now() : $item->status_preparacao_em,
            ]);
        }
    }

    private function buildVariantLabel(ItemPedido $item): ?string
    {
        $opcoes = collect($item->opcoes_snapshot ?? [])
            ->filter(fn ($value, $key) => filled($key) && filled($value))
            ->map(fn ($value, $key) => $key.': '.(is_array($value) ? implode(', ', $value) : $value))
            ->values();

        return $opcoes->isNotEmpty() ? $opcoes->implode(' · ') : null;
    }

    private function formatFornecedorPayload(Fornecedor $fornecedor): array
    {
        return [
            'id' => $fornecedor->id,
            'nome' => $fornecedor->nome,
            'perfil_url' => $fornecedor->perfil_url,
            'site_url' => $fornecedor->site_url,
            'email' => $fornecedor->email,
            'telefone' => $fornecedor->telefone,
            'whatsapp' => $fornecedor->whatsapp,
            'contato_nome' => $fornecedor->contato_nome,
            'pais' => $fornecedor->pais,
            'observacoes' => $fornecedor->observacoes,
            'ativo' => $fornecedor->ativo,
        ];
    }

    private function formatFornecedorOfertaPayload(ProdutoFornecedorOferta $oferta): array
    {
        return [
            'id' => $oferta->id,
            'produto_id' => $oferta->produto_id,
            'fornecedor_id' => $oferta->fornecedor_id,
            'fornecedor' => $oferta->fornecedor ? $this->formatFornecedorPayload($oferta->fornecedor) : null,
            'preco_compra' => $oferta->preco_compra !== null ? (float) $oferta->preco_compra : null,
            'frete_compra' => $oferta->frete_compra !== null ? (float) $oferta->frete_compra : null,
            'moeda' => $oferta->moeda,
            'quantidade_minima' => $oferta->quantidade_minima,
            'prazo_dias' => $oferta->prazo_dias,
            'url_produto' => $oferta->url_produto,
            'sku_fornecedor' => $oferta->sku_fornecedor,
            'observacoes' => $oferta->observacoes,
            'cotado_em' => $oferta->cotado_em?->toDateString(),
            'ativo' => $oferta->ativo,
        ];
    }

    private function normalizeFornecedorData(array $data): array
    {
        return [
            'nome' => $this->nullableString($data['nome'] ?? null),
            'perfil_url' => $this->nullableString($data['perfil_url'] ?? null),
            'site_url' => $this->nullableString($data['site_url'] ?? null),
            'email' => $this->nullableString($data['email'] ?? null),
            'telefone' => $this->nullableString($data['telefone'] ?? null),
            'whatsapp' => $this->nullableString($data['whatsapp'] ?? null),
            'contato_nome' => $this->nullableString($data['contato_nome'] ?? null),
            'pais' => $this->nullableString($data['pais'] ?? null),
            'observacoes' => $this->nullableString($data['observacoes'] ?? null),
        ];
    }

    private function normalizeFornecedorOfertaData(array $data): array
    {
        return [
            'preco_compra' => array_key_exists('preco_compra', $data) ? $data['preco_compra'] : null,
            'frete_compra' => array_key_exists('frete_compra', $data) ? $data['frete_compra'] : null,
            'moeda' => $this->nullableString($data['moeda'] ?? null),
            'quantidade_minima' => array_key_exists('quantidade_minima', $data) ? $data['quantidade_minima'] : null,
            'prazo_dias' => array_key_exists('prazo_dias', $data) ? $data['prazo_dias'] : null,
            'url_produto' => $this->nullableString($data['url_produto'] ?? null),
            'sku_fornecedor' => $this->nullableString($data['sku_fornecedor'] ?? null),
            'observacoes' => $this->nullableString($data['observacoes'] ?? null),
            'cotado_em' => $this->nullableString($data['cotado_em'] ?? null),
            'ativo' => array_key_exists('ativo', $data) ? $data['ativo'] : true,
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function resolveItemCost(ItemPedido $item): ?float
    {
        if ($this->itemIsCanceled($item)) {
            return null;
        }

        if ($item->custo_unitario_declarado !== null) {
            return round((float) $item->custo_unitario_declarado, 2);
        }

        return $this->resolveCatalogItemCost($item);
    }

    private function resolveCatalogItemCost(ItemPedido $item): ?float
    {
        if ($item->produtoVariante) {
            $custoCompra = $item->produtoVariante->custo_compra !== null
                ? (float) $item->produtoVariante->custo_compra
                : ($item->produto?->custo_compra !== null ? (float) $item->produto->custo_compra : null);

            if ($custoCompra === null) {
                return null;
            }

            $freteCompra = $item->produtoVariante->frete_compra !== null
                ? (float) $item->produtoVariante->frete_compra
                : (float) ($item->produto?->frete_compra ?? 0);

            return round($custoCompra + $freteCompra, 2);
        }

        return $item->produto?->custo_efetivo;
    }

    private function resolveItemCostSource(ItemPedido $item): string
    {
        if ($this->itemIsCanceled($item)) {
            return 'cancelado';
        }

        if ($item->custo_unitario_declarado !== null) {
            return 'declarado';
        }

        return $this->resolveCatalogItemCostSource($item);
    }

    private function resolveCatalogItemCostSource(ItemPedido $item): string
    {
        if ($item->produtoVariante?->custo_compra !== null) {
            return 'variante';
        }

        if ($item->produto?->custo_compra !== null) {
            return 'produto';
        }

        return 'sem_custo';
    }

    private function itemIsCanceled(ItemPedido $item): bool
    {
        return $item->status_preparacao_efetivo === ItemPedido::STATUS_PREPARACAO_CANCELADO;
    }

    private function applyActiveItemFilter($query)
    {
        return $query->where(function ($query) {
            $query
                ->whereNull('status_preparacao')
                ->orWhere('status_preparacao', '<>', ItemPedido::STATUS_PREPARACAO_CANCELADO);
        });
    }

    private function buildOrderRevenueContext(Pedido $pedido): array
    {
        $pedido->loadMissing('itens');

        $items = $pedido->itens->values();
        $grossTotal = round((float) $items->sum(fn (ItemPedido $item) => (float) $item->preco * (int) $item->quantidade), 2);
        $netProductsTotal = max(0.0, round((float) $pedido->valor_total - (float) ($pedido->frete_valor ?? 0), 2));

        if ($grossTotal <= 0 || $items->isEmpty()) {
            return [
                'gross_total' => $grossTotal,
                'net_products_total' => 0.0,
                'active_revenue' => 0.0,
                'estorno_total' => 0.0,
                'item_gross_revenues' => [],
                'item_net_revenues' => [],
                'item_discounts' => [],
                'item_refunds' => [],
            ];
        }

        $factor = $netProductsTotal / $grossTotal;
        $remainingNet = $netProductsTotal;
        $itemGrossRevenues = [];
        $itemNetRevenues = [];
        $itemDiscounts = [];
        $itemRefunds = [];
        $activeRevenue = 0.0;
        $refundTotal = 0.0;
        $lastIndex = $items->count() - 1;

        foreach ($items as $index => $item) {
            $gross = round((float) $item->preco * (int) $item->quantidade, 2);
            $net = $index === $lastIndex
                ? round(max(0.0, $remainingNet), 2)
                : round(max(0.0, $gross * $factor), 2);
            $remainingNet = round($remainingNet - $net, 2);
            $discount = max(0.0, round($gross - $net, 2));
            $refund = $this->itemIsCanceled($item) ? $net : 0.0;

            $itemGrossRevenues[$item->id] = $gross;
            $itemNetRevenues[$item->id] = $net;
            $itemDiscounts[$item->id] = $discount;
            $itemRefunds[$item->id] = $refund;

            if ($this->itemIsCanceled($item)) {
                $refundTotal = round($refundTotal + $refund, 2);
            } else {
                $activeRevenue = round($activeRevenue + $net, 2);
            }
        }

        return [
            'gross_total' => $grossTotal,
            'net_products_total' => $netProductsTotal,
            'active_revenue' => $activeRevenue,
            'estorno_total' => $refundTotal,
            'item_gross_revenues' => $itemGrossRevenues,
            'item_net_revenues' => $itemNetRevenues,
            'item_discounts' => $itemDiscounts,
            'item_refunds' => $itemRefunds,
        ];
    }

    private function buildPerformanceRevenueSummary(): array
    {
        $pedidos = Pedido::with('itens')
            ->whereIn('status', PedidoStatus::performanceValues())
            ->get();

        $receitaTotal = 0.0;
        $estornoTotal = 0.0;

        foreach ($pedidos as $pedido) {
            $context = $this->buildOrderRevenueContext($pedido);
            $receitaTotal = round($receitaTotal + $context['active_revenue'], 2);
            $estornoTotal = round($estornoTotal + $context['estorno_total'], 2);
        }

        return [
            'receita_total' => $receitaTotal,
            'estornos_total' => $estornoTotal,
        ];
    }

    private function normalizeCouponFilter(mixed $value): ?string
    {
        $code = Str::upper(trim((string) $value));

        return $code === '' ? null : $code;
    }

    private function applyCouponFilterToPedidoQuery($query, ?string $couponCode)
    {
        if ($couponCode !== null) {
            $query->whereRaw('UPPER(cupom_codigo) = ?', [$couponCode]);
        }

        return $query;
    }

    private function couponFilterOptions(?Produto $produto = null)
    {
        $pedidoCodes = Pedido::query()
            ->whereNotNull('cupom_codigo')
            ->where('cupom_codigo', '<>', '')
            ->when($produto, function ($query) use ($produto) {
                $query->whereHas('itens', fn ($itemQuery) => $itemQuery->where('produto_id', $produto->id));
            })
            ->distinct()
            ->orderBy('cupom_codigo')
            ->pluck('cupom_codigo');

        return Cupom::query()
            ->orderBy('codigo')
            ->pluck('codigo')
            ->merge($pedidoCodes)
            ->map(fn ($code) => $this->normalizeCouponFilter($code))
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    private function buildCostComparisonAnalytics(?Produto $produto = null, ?string $period = null, ?string $couponCode = null): array
    {
        $periodStart = match ($period) {
            '30' => now()->subDays(30),
            '90' => now()->subDays(90),
            '365' => now()->subDays(365),
            default => null,
        };

        $items = ItemPedido::query()
            ->with([
                'pedido:id,status,created_at,updated_at',
                'produto:id,nome,slug,custo_compra,frete_compra',
                'produtoVariante:id,produto_id,valores,custo_compra,frete_compra',
            ])
            ->whereNotNull('custo_unitario_declarado')
            ->where(fn ($query) => $this->applyActiveItemFilter($query))
            ->when($produto, fn ($query) => $query->where('produto_id', $produto->id))
            ->whereHas('pedido', function ($query) use ($periodStart, $couponCode) {
                $query->whereIn('status', PedidoStatus::performanceValues());

                if ($periodStart) {
                    $query->where('created_at', '>=', $periodStart);
                }

                $this->applyCouponFilterToPedidoQuery($query, $couponCode);
            })
            ->get();

        return $this->buildCostComparisonFromItems($items);
    }

    private function buildCostComparisonFromItems($items): array
    {
        $rows = collect($items)
            ->map(fn (ItemPedido $item) => $this->buildItemCostComparison($item))
            ->filter()
            ->values();

        $knownRows = $rows->filter(fn (array $row) => $row['catalog_unit_cost'] !== null);
        $overruns = $knownRows->filter(fn (array $row) => $row['delta_total'] > 0);
        $savings = $knownRows->filter(fn (array $row) => $row['delta_total'] < 0);
        $percentRows = $knownRows->filter(fn (array $row) => $row['delta_percent'] !== null);
        $avgDeltaPercent = $percentRows->count() > 0
            ? round((float) $percentRows->avg('delta_percent'), 2)
            : null;

        return [
            'summary' => [
                'items_count' => $rows->count(),
                'units_count' => (int) $rows->sum('quantidade'),
                'real_total' => round((float) $rows->sum('real_total'), 2),
                'catalog_total' => round((float) $knownRows->sum('catalog_total'), 2),
                'delta_total' => round((float) $knownRows->sum('delta_total'), 2),
                'overrun_total' => round((float) $overruns->sum('delta_total'), 2),
                'overrun_count' => $overruns->count(),
                'saving_total' => round(abs((float) $savings->sum('delta_total')), 2),
                'saving_count' => $savings->count(),
                'missing_catalog_count' => $rows->count() - $knownRows->count(),
                'avg_delta_percent' => $avgDeltaPercent,
            ],
            'rows' => $rows,
            'top_overruns' => $overruns->sortByDesc('delta_total')->take(8)->values(),
            'top_savings' => $savings->sortBy('delta_total')->take(8)->values(),
            'recent' => $rows
                ->sortByDesc(fn (array $row) => $row['confirmed_at']?->timestamp ?? 0)
                ->take(12)
                ->values(),
        ];
    }

    private function buildItemCostComparison(ItemPedido $item): ?array
    {
        if ($this->itemIsCanceled($item) || $item->custo_unitario_declarado === null) {
            return null;
        }

        $pedido = $item->pedido;
        $catalogCost = $this->resolveCatalogItemCost($item);
        $realCost = round((float) $item->custo_unitario_declarado, 2);
        $quantity = (int) $item->quantidade;
        $deltaUnit = $catalogCost !== null ? round($realCost - $catalogCost, 2) : null;
        $deltaTotal = $deltaUnit !== null ? round($deltaUnit * $quantity, 2) : null;
        $deltaPercent = ($catalogCost !== null && $catalogCost > 0)
            ? round(($deltaUnit / $catalogCost) * 100, 2)
            : null;

        return [
            'item_id' => $item->id,
            'pedido_id' => $item->pedido_id,
            'pedido_status' => $pedido?->status,
            'pedido_status_label' => $pedido?->status ? PedidoStatus::label($pedido->status) : null,
            'pedido_created_at' => $pedido?->created_at,
            'pedido_url' => $item->pedido_id ? route('admin.pedidos.detalhes', $item->pedido_id) : null,
            'produto_id' => $item->produto_id,
            'produto_nome' => $item->produto?->nome ?? 'Produto removido',
            'produto_url' => $item->produto_id ? route('admin.analytics.products.show', $item->produto_id) : null,
            'variant_label' => $this->buildVariantLabel($item),
            'quantidade' => $quantity,
            'real_unit_cost' => $realCost,
            'catalog_unit_cost' => $catalogCost,
            'real_total' => round($realCost * $quantity, 2),
            'catalog_total' => $catalogCost !== null ? round($catalogCost * $quantity, 2) : null,
            'delta_unit' => $deltaUnit,
            'delta_total' => $deltaTotal,
            'delta_percent' => $deltaPercent,
            'direction' => $deltaTotal === null ? 'missing_catalog' : ($deltaTotal > 0 ? 'overrun' : ($deltaTotal < 0 ? 'saving' : 'same')),
            'catalog_source' => $this->resolveCatalogItemCostSource($item),
            'confirmed_at' => $item->custo_declarado_em ?? $item->status_preparacao_em ?? $pedido?->updated_at,
            'status_preparacao_label' => ItemPedido::preparationStatusLabel($item->status_preparacao_efetivo),
        ];
    }

    private function resolvePartnerCommissionContext(Pedido $pedido): array
    {
        $couponCode = trim((string) ($pedido->cupom_codigo ?? ''));

        if ($couponCode === '') {
            return [
                'coupon_code' => null,
                'partner_name' => null,
                'rate' => null,
            ];
        }

        $coupon = Cupom::with('user')
            ->whereRaw('UPPER(codigo) = ?', [Str::upper($couponCode)])
            ->whereNotNull('user_id')
            ->first();

        if (! $coupon || ! $coupon->user) {
            return [
                'coupon_code' => Str::upper($couponCode),
                'partner_name' => null,
                'rate' => null,
            ];
        }

        $progress = app(CouponPartnerProgressService::class)->progressForUser($coupon->user);

        return [
            'coupon_code' => $coupon->codigo,
            'partner_name' => $coupon->user->name,
            'rate' => (float) $progress['current_rate'],
        ];
    }

    private function resolveAnalyticsPeriod(string $period): string
    {
        return in_array($period, ['30', '90', '365', 'total'], true) ? $period : '30';
    }

    private function analyticsPeriodOptions(): array
    {
        return [
            ['value' => '30', 'label' => '30 dias'],
            ['value' => '90', 'label' => '90 dias'],
            ['value' => '365', 'label' => '365 dias'],
            ['value' => 'total', 'label' => 'Total'],
        ];
    }

    private function operationalCostTypes(): array
    {
        return [
            'transporte' => 'Transporte',
            'embalagem' => 'Embalagem',
            'taxa' => 'Taxas',
            'imposto' => 'Impostos',
            'marketing' => 'Marketing',
            'fornecedor' => 'Fornecedor',
            'outros' => 'Outros',
        ];
    }

    private function getProductAnalyticsData(Produto $produto, string $period, ?string $couponCode = null): array
    {
        $performanceStatuses = PedidoStatus::performanceValues();
        $periodStart = match ($period) {
            '30' => now()->subDays(30),
            '90' => now()->subDays(90),
            '365' => now()->subDays(365),
            default => null,
        };

        $itensQuery = ItemPedido::query()
            ->with([
                'pedido:id,status,created_at,valor_total,frete_valor,valor_desconto,cupom_codigo',
                'produto:id,custo_compra,frete_compra',
                'produtoVariante:id,produto_id,custo_compra,frete_compra',
            ])
            ->where('produto_id', $produto->id)
            ->where(fn ($query) => $this->applyActiveItemFilter($query))
            ->whereHas('pedido', function ($query) use ($performanceStatuses, $periodStart, $couponCode) {
                $query->whereIn('status', $performanceStatuses);

                if ($periodStart) {
                    $query->where('created_at', '>=', $periodStart);
                }

                $this->applyCouponFilterToPedidoQuery($query, $couponCode);
            });

        $itens = $itensQuery->get();
        $receitaTotal = 0.0;
        $custoTotal = 0.0;
        $unidadesVendidas = 0;
        $itensSemCusto = 0;

        foreach ($itens as $item) {
            $valorBruto = (float) $item->preco * (int) $item->quantidade;

            $pedidoValorTotal = (float) ($item->pedido->valor_total ?? 0);
            $pedidoFrete = (float) ($item->pedido->frete_valor ?? 0);
            $pedidoDesconto = (float) ($item->pedido->valor_desconto ?? 0);
            $pedidoSubtotal = $pedidoValorTotal - $pedidoFrete + $pedidoDesconto;
            $fatorLiquido = $pedidoSubtotal > 0
                ? ($pedidoValorTotal - $pedidoFrete) / $pedidoSubtotal
                : 1.0;

            $receitaTotal += $valorBruto * $fatorLiquido;
            $unidadesVendidas += (int) $item->quantidade;

            $custoUnitario = $this->resolveItemCost($item);
            if ($custoUnitario === null) {
                $itensSemCusto += (int) $item->quantidade;
                $custoUnitario = 0.0;
            }

            $custoTotal += $custoUnitario * (int) $item->quantidade;
        }

        $pedidosCount = $itens->pluck('pedido_id')->unique()->count();
        $lucroBrutoTotal = round($receitaTotal - $custoTotal, 2);
        $margemBrutaPercentual = $receitaTotal > 0
            ? round(($lucroBrutoTotal / $receitaTotal) * 100, 2)
            : 0.0;
        $ticketMedioProduto = $pedidosCount > 0
            ? round($receitaTotal / $pedidosCount, 2)
            : 0.0;
        $ultimoPedidoEm = $itens
            ->pluck('pedido.created_at')
            ->filter()
            ->max();

        return [
            'produto' => $produto->loadMissing(['categoria:id,nome', 'imagemCapa:id,produto_id,caminho']),
            'categorias' => Categoria::orderBy('nome')->get(),
            'selected_period' => $period,
            'period_options' => $this->analyticsPeriodOptions(),
            'period_label' => collect($this->analyticsPeriodOptions())->firstWhere('value', $period)['label'] ?? '30 dias',
            'period_start' => $periodStart,
            'selected_coupon' => $couponCode,
            'coupon_options' => $this->couponFilterOptions($produto),
            'cost_comparison' => $this->buildCostComparisonAnalytics($produto, $period, $couponCode),
            'product_metrics' => [
                'receita_total' => round($receitaTotal, 2),
                'custo_total' => round($custoTotal, 2),
                'lucro_bruto_total' => $lucroBrutoTotal,
                'margem_bruta_percentual' => $margemBrutaPercentual,
                'unidades_vendidas' => $unidadesVendidas,
                'pedidos_count' => $pedidosCount,
                'ticket_medio_produto' => $ticketMedioProduto,
                'itens_sem_custo' => $itensSemCusto,
                'ultimo_pedido_em' => $ultimoPedidoEm,
            ],
        ];
    }

    private function getProductTimeSeries(Produto $produto, string $period, ?string $couponCode = null): array
    {
        $performanceStatuses = PedidoStatus::performanceValues();
        $periodStart = match ($period) {
            '30' => now()->subDays(30),
            '90' => now()->subDays(90),
            '365' => now()->subDays(365),
            default => null,
        };

        $itens = ItemPedido::query()
            ->with([
                'pedido:id,status,created_at,valor_total,frete_valor,valor_desconto,cupom_codigo',
                'produto:id,custo_compra,frete_compra',
                'produtoVariante:id,produto_id,custo_compra,frete_compra',
            ])
            ->where('produto_id', $produto->id)
            ->where(fn ($query) => $this->applyActiveItemFilter($query))
            ->whereHas('pedido', function ($query) use ($performanceStatuses, $periodStart, $couponCode) {
                $query->whereIn('status', $performanceStatuses);
                if ($periodStart) {
                    $query->where('created_at', '>=', $periodStart);
                }

                $this->applyCouponFilterToPedidoQuery($query, $couponCode);
            })
            ->get();

        $groupBy = match ($period) {
            '30' => fn ($item) => Carbon::parse($item->pedido->created_at)->format('Y-m-d'),
            '90' => fn ($item) => Carbon::parse($item->pedido->created_at)->startOfWeek()->format('Y-m-d'),
            '365' => fn ($item) => Carbon::parse($item->pedido->created_at)->startOfWeek()->format('Y-m-d'),
            default => fn ($item) => Carbon::parse($item->pedido->created_at)->format('Y-m'),
        };

        $groups = [];
        foreach ($itens as $item) {
            $key = $groupBy($item);
            if (! isset($groups[$key])) {
                $groups[$key] = ['receita' => 0.0, 'custo' => 0.0, 'unidades' => 0];
            }

            $valorBruto = (float) $item->preco * (int) $item->quantidade;
            $pedidoTotal = (float) ($item->pedido->valor_total ?? 0);
            $pedidoFrete = (float) ($item->pedido->frete_valor ?? 0);
            $pedidoDesc = (float) ($item->pedido->valor_desconto ?? 0);
            $subtotal = $pedidoTotal - $pedidoFrete + $pedidoDesc;
            $fator = $subtotal > 0 ? ($pedidoTotal - $pedidoFrete) / $subtotal : 1.0;

            $groups[$key]['receita'] += $valorBruto * $fator;
            $groups[$key]['unidades'] += (int) $item->quantidade;

            $custo = $this->resolveItemCost($item) ?? 0.0;
            $groups[$key]['custo'] += $custo * (int) $item->quantidade;
        }

        ksort($groups);

        $result = [];
        foreach ($groups as $date => $data) {
            $receita = round($data['receita'], 2);
            $lucro = $receita - round($data['custo'], 2);
            $margem = $receita > 0 ? round(($lucro / $receita) * 100, 2) : 0.0;
            $result[] = [
                'date' => $date,
                'receita' => $receita,
                'unidades' => $data['unidades'],
                'margem' => $margem,
            ];
        }

        return $result;
    }

    private function getDashboardAnalyticsData(): array
    {
        $performanceStatuses = PedidoStatus::performanceValues();
        $total_produtos = Produto::count();
        $total_pedidos = Pedido::count();
        $pedidos_nao_finalizados = Pedido::whereIn('status', [PedidoStatus::CARRINHO, PedidoStatus::PENDENTE])->count();
        $pedidos_pagos = Pedido::where('status', PedidoStatus::PAGO)->count();
        $pedidos_pendentes = Pedido::where('status', PedidoStatus::PENDENTE)->count();
        $pedidos_processando = Pedido::where('status', PedidoStatus::PROCESSANDO)->count();
        $pedidos_enviados = Pedido::where('status', PedidoStatus::ENVIADO)->count();
        $pedidos_entregues = Pedido::where('status', PedidoStatus::ENTREGUE)->count();
        $pedidos_cancelados = Pedido::where('status', PedidoStatus::CANCELADO)->count();
        $pedidos_performance = Pedido::whereIn('status', $performanceStatuses);
        $performanceRevenueSummary = $this->buildPerformanceRevenueSummary();
        $receita_total = $performanceRevenueSummary['receita_total'];
        $estornos_total = $performanceRevenueSummary['estornos_total'];

        $itensPerformance = ItemPedido::with([
            'produto:id,custo_compra,frete_compra',
            'produtoVariante:id,produto_id,custo_compra,frete_compra',
        ])
            ->where(fn ($query) => $this->applyActiveItemFilter($query))
            ->whereHas('pedido', function ($query) {
                $query->whereIn('status', PedidoStatus::performanceValues());
            })->get();

        $custo_total = 0;
        $itens_sem_custo = 0;

        foreach ($itensPerformance as $item) {
            $custoUnitario = $this->resolveItemCost($item);

            if ($custoUnitario === null) {
                $itens_sem_custo += $item->quantidade;
                $custoUnitario = 0;
            }

            $custo_total += $item->quantidade * $custoUnitario;
        }

        $lucro_bruto_total = $receita_total - $custo_total;
        $custos_operacionais_total = (float) CustoOperacional::sum('valor');
        $lucro_liquido_total = round($lucro_bruto_total - $custos_operacionais_total, 2);
        $margem_bruta_percentual = $receita_total > 0
            ? round(($lucro_bruto_total / $receita_total) * 100, 2)
            : 0;
        $margem_liquida_percentual = $receita_total > 0
            ? round(($lucro_liquido_total / $receita_total) * 100, 2)
            : 0;
        $custos_operacionais = CustoOperacional::query()
            ->orderByDesc('data_referencia')
            ->orderByDesc('id')
            ->limit(8)
            ->get();
        $custo_operacional_tipos = $this->operationalCostTypes();

        $produtos_analytics = Produto::select(
            'id',
            'nome',
            'marca',
            'preco',
            'preco_original',
            'custo_compra',
            'frete_compra',
            'desconto_percentual',
            'em_promocao',
            'destaque',
            'estoque',
            'ativo',
            'categoria_id',
            'tags'
        )->with([
            'imagemCapa:id,produto_id',
            'categoria:id,nome',
        ])->orderBy('nome')->get();

        $margemMinima = 20;
        $produtosSemCusto = $produtos_analytics->whereNull('custo_compra');
        $produtosEstoqueZerado = $produtos_analytics->where('ativo', true)->where('estoque', 0);
        $produtosMargemNeg = $produtos_analytics->filter(
            fn ($p) => $p->custo_compra !== null && $p->margem_bruta_percentual !== null && $p->margem_bruta_percentual < 0
        );
        $produtosMargemZero = $produtos_analytics->filter(
            fn ($p) => $p->custo_compra !== null && $p->margem_bruta_percentual !== null && $p->margem_bruta_percentual == 0
        );
        $produtosMargemBaixa = $produtos_analytics->filter(
            fn ($p) => $p->custo_compra !== null && $p->margem_bruta_percentual !== null
                && $p->margem_bruta_percentual > 0 && $p->margem_bruta_percentual < $margemMinima
        );

        $alertas = [
            'margem_negativa' => $produtosMargemNeg->count(),
            'margem_zero' => $produtosMargemZero->count(),
            'margem_baixa' => $produtosMargemBaixa->count(),
            'estoque_zerado' => $produtosEstoqueZerado->count(),
            'sem_custo' => $produtosSemCusto->count(),
            'inativos' => $produtos_analytics->where('ativo', false)->count(),
            'produtos_sem_custo' => $produtosSemCusto->values(),
            'produtos_estoque_zerado' => $produtosEstoqueZerado->values(),
            'produtos_margem_negativa' => $produtosMargemNeg->values(),
            'produtos_margem_zero' => $produtosMargemZero->values(),
            'produtos_margem_baixa' => $produtosMargemBaixa->values(),
        ];

        $top_produtos = ItemPedido::select(
            'produto_id',
            \DB::raw('SUM(quantidade) as total_vendido'),
            \DB::raw('SUM(preco * quantidade) as receita_gerada')
        )->where(fn ($query) => $this->applyActiveItemFilter($query))
            ->whereHas('pedido', fn ($q) => $q->whereIn('status', $performanceStatuses))
            ->groupBy('produto_id')
            ->orderByDesc('total_vendido')
            ->limit(5)
            ->with('produto:id,nome,marca')
            ->get();

        $receita_categoria = \DB::table('itens_pedido')
            ->join('pedidos', 'pedidos.id', '=', 'itens_pedido.pedido_id')
            ->join('produtos', 'produtos.id', '=', 'itens_pedido.produto_id')
            ->join('categorias', 'categorias.id', '=', 'produtos.categoria_id')
            ->whereIn('pedidos.status', $performanceStatuses)
            ->where(function ($query) {
                $query
                    ->whereNull('itens_pedido.status_preparacao')
                    ->orWhere('itens_pedido.status_preparacao', '<>', ItemPedido::STATUS_PREPARACAO_CANCELADO);
            })
            ->select('categorias.nome', \DB::raw('SUM(itens_pedido.preco * itens_pedido.quantidade) as receita'))
            ->groupBy('categorias.id', 'categorias.nome')
            ->orderByDesc('receita')
            ->get();

        $total_pedidos_performance = (clone $pedidos_performance)->count();
        $ticket_medio = $total_pedidos_performance > 0 ? round($receita_total / $total_pedidos_performance, 2) : 0;
        $total_unidades = ItemPedido::where(fn ($query) => $this->applyActiveItemFilter($query))
            ->whereHas('pedido', fn ($q) => $q->whereIn('status', $performanceStatuses))
            ->sum('quantidade');
        $total_ativos = Produto::where('ativo', true)->count();

        $pedidos_acao = Pedido::whereIn('status', PedidoStatus::actionableValues())
            ->with('user:id,name')
            ->orderBy('created_at')
            ->get();

        $sla_pago_sem_processar = Pedido::where('status', PedidoStatus::PAGO)
            ->where('updated_at', '<', now()->subHours(24))
            ->count();

        $sla_processando_sem_enviar = Pedido::where('status', PedidoStatus::PROCESSANDO)
            ->where('updated_at', '<', now()->subDays(3))
            ->count();

        $sla_enviado_sem_entregar = Pedido::where('status', PedidoStatus::ENVIADO)
            ->where('updated_at', '<', now()->subDays(15))
            ->count();

        $pedidos_recentes = Pedido::with('user')->orderBy('created_at', 'desc')->limit(5)->get();
        $categorias = Categoria::orderBy('nome')->get();
        $statusCounts = [
            PedidoStatus::PAGO => $pedidos_pagos,
            PedidoStatus::PENDENTE => $pedidos_pendentes,
            PedidoStatus::PROCESSANDO => $pedidos_processando,
            PedidoStatus::ENVIADO => $pedidos_enviados,
            PedidoStatus::ENTREGUE => $pedidos_entregues,
            PedidoStatus::CANCELADO => $pedidos_cancelados,
        ];
        $statusPalette = [
            PedidoStatus::PAGO => 'bg-green-500',
            PedidoStatus::PENDENTE => 'bg-gray-400',
            PedidoStatus::PROCESSANDO => 'bg-gray-600',
            PedidoStatus::ENVIADO => 'bg-gray-800',
            PedidoStatus::ENTREGUE => 'bg-black',
            PedidoStatus::CANCELADO => 'bg-gray-300',
        ];
        $pedidos_por_status = array_merge([
            [
                'key' => 'nao_finalizados',
                'label' => 'Não finalizados',
                'count' => $pedidos_nao_finalizados,
                'color' => 'bg-yellow-300',
            ],
        ], array_map(fn (string $status) => [
            'key' => $status,
            'label' => PedidoStatus::label($status),
            'count' => $statusCounts[$status] ?? 0,
            'color' => $statusPalette[$status] ?? 'bg-gray-300',
        ], PedidoStatus::adminValues()));

        $promotion_simulator_periods = [30, 90, 365];
        $promotion_simulator_default_period = 30;
        $profit_exclusivity_insights = $this->buildProfitExclusivityInsights($produtos_analytics);
        $cost_comparison = $this->buildCostComparisonAnalytics();

        return compact(
            'total_produtos',
            'total_pedidos',
            'pedidos_nao_finalizados',
            'pedidos_pagos',
            'pedidos_pendentes',
            'pedidos_processando',
            'pedidos_enviados',
            'pedidos_entregues',
            'pedidos_cancelados',
            'receita_total',
            'estornos_total',
            'custo_total',
            'lucro_bruto_total',
            'custos_operacionais_total',
            'lucro_liquido_total',
            'margem_bruta_percentual',
            'margem_liquida_percentual',
            'itens_sem_custo',
            'pedidos_recentes',
            'produtos_analytics',
            'alertas',
            'top_produtos',
            'receita_categoria',
            'ticket_medio',
            'total_unidades',
            'total_ativos',
            'pedidos_acao',
            'categorias',
            'pedidos_por_status',
            'profit_exclusivity_insights',
            'promotion_simulator_periods',
            'promotion_simulator_default_period',
            'sla_pago_sem_processar',
            'sla_processando_sem_enviar',
            'sla_enviado_sem_entregar',
            'custos_operacionais',
            'custo_operacional_tipos',
            'cost_comparison'
        );
    }

    private function analyticsSummaryRows(array $analytics): array
    {
        return [
            ['Receita Total', $this->formatCurrency($analytics['receita_total'])],
            ['Estornos de Produtos', $this->formatCurrency($analytics['estornos_total'] ?? 0.0)],
            ['Lucro Bruto', $this->formatCurrency($analytics['lucro_bruto_total'])],
            ['Custos Operacionais', $this->formatCurrency($analytics['custos_operacionais_total'])],
            ['Lucro Líquido', $this->formatCurrency($analytics['lucro_liquido_total'])],
            ['Diferenca Custo Real x Sistema', $this->formatCurrency($analytics['cost_comparison']['summary']['delta_total'] ?? 0.0)],
            ['Margem Bruta', $this->formatPercent($analytics['margem_bruta_percentual'])],
            ['Margem Líquida', $this->formatPercent($analytics['margem_liquida_percentual'])],
            ['Pedidos pagos', (string) $analytics['pedidos_pagos']],
            ['Nao finalizados', (string) $analytics['pedidos_nao_finalizados']],
            ['Pendentes', (string) $analytics['pedidos_pendentes']],
            ['Total de Produtos', (string) $analytics['total_produtos']],
            ['Total de Pedidos', (string) $analytics['total_pedidos']],
            ['Ticket Medio', $this->formatCurrency($analytics['ticket_medio'])],
            ['Total de Unidades', (string) $analytics['total_unidades']],
            ['Total de Ativos', (string) $analytics['total_ativos']],
            ['Itens Sem Custo', (string) $analytics['itens_sem_custo']],
        ];
    }

    private function buildProfitExclusivityInsights($produtosAnalytics): array
    {
        $eligible = $produtosAnalytics
            ->filter(fn ($produto) => $produto->ativo
                && $produto->custo_compra !== null
                && $produto->lucro_bruto_unitario !== null
                && $produto->margem_bruta_percentual !== null)
            ->map(function ($produto) {
                $exclusive = $this->resolveExclusiveSignal($produto);

                return [
                    'id' => $produto->id,
                    'nome' => $produto->nome,
                    'marca' => $produto->marca,
                    'categoria' => $produto->categoria?->nome,
                    'preco_com_desconto' => (float) $produto->preco_com_desconto,
                    'custo_compra' => $produto->custo_efetivo,
                    'lucro_bruto_unitario' => (float) $produto->lucro_bruto_unitario,
                    'margem_bruta_percentual' => (float) $produto->margem_bruta_percentual,
                    'estoque' => (int) $produto->estoque,
                    'destaque' => (bool) $produto->destaque,
                    'tags' => $produto->tags ?? [],
                    'exclusive_signal' => $exclusive['signal'],
                    'exclusive_reason' => $exclusive['reason'],
                ];
            })
            ->values();

        return [
            'top_lucro_unitario' => $eligible
                ->sort(function (array $a, array $b) {
                    return [$b['lucro_bruto_unitario'], $b['margem_bruta_percentual'], $a['nome']]
                        <=> [$a['lucro_bruto_unitario'], $a['margem_bruta_percentual'], $b['nome']];
                })
                ->take(10)
                ->values(),
            'top_margem_percentual' => $eligible
                ->sort(function (array $a, array $b) {
                    return [$b['margem_bruta_percentual'], $b['lucro_bruto_unitario'], $a['nome']]
                        <=> [$a['margem_bruta_percentual'], $a['lucro_bruto_unitario'], $b['nome']];
                })
                ->take(10)
                ->values(),
            'top_menor_margem' => $eligible
                ->sort(function (array $a, array $b) {
                    return [$a['margem_bruta_percentual'], $a['lucro_bruto_unitario'], $a['nome']]
                        <=> [$b['margem_bruta_percentual'], $b['lucro_bruto_unitario'], $b['nome']];
                })
                ->take(10)
                ->values(),
            'top_exclusivos_lucrativos' => $eligible
                ->filter(fn (array $produto) => $produto['exclusive_signal'] !== 'none')
                ->sort(function (array $a, array $b) {
                    return [$b['lucro_bruto_unitario'], $b['margem_bruta_percentual'], $a['nome']]
                        <=> [$a['lucro_bruto_unitario'], $a['margem_bruta_percentual'], $b['nome']];
                })
                ->take(10)
                ->values(),
        ];
    }

    private function resolveExclusiveSignal(Produto $produto): array
    {
        $tags = collect($produto->tags ?? [])
            ->filter(fn ($tag) => is_string($tag))
            ->map(fn (string $tag) => Str::lower(trim($tag)));

        if ($tags->contains('exclusivo')) {
            return [
                'signal' => 'explicit',
                'reason' => 'Tag Exclusivo',
            ];
        }

        $brand = Str::lower(trim((string) ($produto->marca ?? '')));
        if (in_array($brand, ['artisan', 'wooting', 'wlmouse', 'benq zowie'], true)) {
            return [
                'signal' => 'premium_brand',
                'reason' => 'Marca premium',
            ];
        }

        $name = Str::lower($produto->nome);
        foreach (['counter strike 2 edition', '600hz', '540hz', 'qd-oled'] as $keyword) {
            if (Str::contains($name, $keyword)) {
                return [
                    'signal' => 'premium_keyword',
                    'reason' => 'Linha/edição premium',
                ];
            }
        }

        return [
            'signal' => 'none',
            'reason' => null,
        ];
    }

    private function analyticsOrderStatusRows(array $analytics): array
    {
        return [
            ['Nao finalizados', (string) $analytics['pedidos_nao_finalizados']],
            ['Pagos', (string) $analytics['pedidos_pagos']],
            ['Pendentes', (string) $analytics['pedidos_pendentes']],
            ['Processando', (string) $analytics['pedidos_processando']],
            ['Enviados', (string) $analytics['pedidos_enviados']],
            ['Entregues', (string) $analytics['pedidos_entregues']],
            ['Cancelados', (string) $analytics['pedidos_cancelados']],
        ];
    }

    private function analyticsAlertRows(array $analytics): array
    {
        return [
            ['Margem negativa', (string) $analytics['alertas']['margem_negativa']],
            ['Margem zero', (string) $analytics['alertas']['margem_zero']],
            ['Margem baixa', (string) $analytics['alertas']['margem_baixa']],
            ['Estoque zerado', (string) $analytics['alertas']['estoque_zerado']],
            ['Sem custo', (string) $analytics['alertas']['sem_custo']],
            ['Inativos', (string) $analytics['alertas']['inativos']],
        ];
    }

    private function buildOrderDetailAnalytics(Pedido $pedido): array
    {
        $partnerCommission = $this->resolvePartnerCommissionContext($pedido);
        $revenueContext = $this->buildOrderRevenueContext($pedido);

        $items = $pedido->itens->map(function (ItemPedido $item) use ($revenueContext) {
            $isCanceled = $this->itemIsCanceled($item);
            $receitaOriginal = round((float) $item->preco * (int) $item->quantidade, 2);
            $receitaLiquidaOriginal = $revenueContext['item_net_revenues'][$item->id] ?? $receitaOriginal;
            $descontoRateado = $revenueContext['item_discounts'][$item->id] ?? 0.0;
            $estorno = $revenueContext['item_refunds'][$item->id] ?? 0.0;
            $receita = $isCanceled ? 0.0 : $receitaLiquidaOriginal;
            $custoUnitario = $isCanceled ? null : $this->resolveItemCost($item);
            $custoTotal = $custoUnitario !== null ? round($custoUnitario * (int) $item->quantidade, 2) : null;
            $lucroUnitario = $custoUnitario !== null ? round((float) $item->preco - $custoUnitario, 2) : null;
            $lucroTotal = $custoTotal !== null ? round($receita - $custoTotal, 2) : null;
            $margemPercentual = ($custoUnitario !== null && $receita > 0)
                ? round((($receita - $custoTotal) / $receita) * 100, 2)
                : null;
            $opcoes = collect($item->opcoes_snapshot ?? [])
                ->filter(fn ($value, $key) => filled($key) && filled($value))
                ->map(fn ($value, $key) => $key.': '.(is_array($value) ? implode(', ', $value) : $value))
                ->values();

            $health = 'healthy';
            if ($isCanceled) {
                $health = 'canceled';
            } elseif ($custoUnitario === null) {
                $health = 'missing_cost';
            } elseif ($margemPercentual < 0) {
                $health = 'negative';
            } elseif ($margemPercentual < 20) {
                $health = 'low';
            }

            return [
                'id' => $item->id,
                'produto_id' => $item->produto_id,
                'produto_nome' => $item->produto?->nome ?? 'Produto removido',
                'quantidade' => (int) $item->quantidade,
                'preco_unitario' => round((float) $item->preco, 2),
                'receita' => $receita,
                'receita_original' => $receitaOriginal,
                'receita_liquida_original' => $receitaLiquidaOriginal,
                'desconto_rateado' => $descontoRateado,
                'estorno' => $estorno,
                'custo_unitario' => $custoUnitario,
                'custo_total' => $custoTotal,
                'lucro_unitario' => $lucroUnitario,
                'lucro_total' => $lucroTotal,
                'margem_percentual' => $margemPercentual,
                'cost_source' => $this->resolveItemCostSource($item),
                'status_preparacao' => $item->status_preparacao_efetivo,
                'status_preparacao_label' => ItemPedido::preparationStatusLabel($item->status_preparacao_efetivo),
                'is_canceled' => $isCanceled,
                'variant_label' => $opcoes->isNotEmpty() ? $opcoes->implode(' · ') : null,
                'variant_lines' => $opcoes,
                'image_path' => optional($item->produto?->imagens?->firstWhere('capa', true) ?: $item->produto?->imagens?->first())->caminho,
                'health' => $health,
            ];
        })->values();

        $receitaItens = round((float) $items->sum('receita'), 2);
        $desconto = round((float) ($pedido->valor_desconto ?? 0), 2);
        $taxaParceiro = $partnerCommission['rate'];

        $items = $items->map(function (array $item) use ($taxaParceiro) {
            $receitaLiquida = max(0, round($item['receita'], 2));
            $precoUnitarioLiquido = $item['quantidade'] > 0
                ? round($receitaLiquida / $item['quantidade'], 2)
                : 0.0;
            $comissaoParceiro = $taxaParceiro !== null
                ? round($receitaLiquida * ($taxaParceiro / 100), 2)
                : null;
            $lucroLiquidoItem = $item['custo_total'] !== null
                ? round($receitaLiquida - $item['custo_total'], 2)
                : null;

            $item['receita_liquida'] = $receitaLiquida;
            $item['preco_unitario_liquido'] = $precoUnitarioLiquido;
            $item['comissao_parceiro'] = $comissaoParceiro;
            $item['lucro_liquido_item'] = $lucroLiquidoItem;
            $item['lucro_apos_parceiro'] = ($lucroLiquidoItem !== null && $comissaoParceiro !== null)
                ? round($lucroLiquidoItem - $comissaoParceiro, 2)
                : null;

            return $item;
        })->values();

        $activeItems = $items->reject(fn (array $item) => $item['is_canceled'])->values();
        $custoConhecido = round((float) $activeItems->sum(fn (array $item) => $item['custo_total'] ?? 0), 2);
        $lucroConhecido = round((float) $activeItems->sum(fn (array $item) => $item['lucro_total'] ?? 0), 2);
        $totalUnidades = (int) $activeItems->sum('quantidade');
        $itensSemCusto = (int) $activeItems->where('cost_source', 'sem_custo')->count();
        $itensComCustoDeclarado = (int) $activeItems->where('cost_source', 'declarado')->count();
        $itensCancelados = (int) $items->where('is_canceled', true)->count();

        $priceRankValues = $activeItems
            ->pluck('preco_unitario_liquido')
            ->unique()
            ->sortDesc()
            ->values();

        $knownMarginItems = $activeItems
            ->filter(fn (array $item) => $item['margem_percentual'] !== null)
            ->values();

        $marginRankValues = $knownMarginItems
            ->pluck('margem_percentual')
            ->unique()
            ->sort()
            ->values();

        $topPriceValue = $activeItems->count() > 1 ? $priceRankValues->first() : null;
        $worstMarginValue = $knownMarginItems->count() > 1 ? $marginRankValues->first() : null;

        $items = $items->map(function (array $item) use ($receitaItens, $lucroConhecido, $priceRankValues, $marginRankValues, $topPriceValue, $worstMarginValue) {
            $receitaShare = $receitaItens > 0 ? round(($item['receita'] / $receitaItens) * 100, 2) : 0;
            $lucroShare = ($lucroConhecido != 0 && $item['lucro_total'] !== null)
                ? round(($item['lucro_total'] / $lucroConhecido) * 100, 2)
                : null;
            $rankPreco = ! $item['is_canceled']
                ? $priceRankValues->search($item['preco_unitario_liquido'])
                : false;
            $rankMargem = (! $item['is_canceled'] && $item['margem_percentual'] !== null)
                ? $marginRankValues->search($item['margem_percentual'])
                : false;

            $item['receita_share_percentual'] = $receitaShare;
            $item['lucro_share_percentual'] = $lucroShare;
            $item['rank_preco'] = $rankPreco === false ? null : $rankPreco + 1;
            $item['rank_margem'] = $rankMargem === false ? null : $rankMargem + 1;
            $item['is_top_price'] = $topPriceValue !== null
                && ! $item['is_canceled']
                && $item['preco_unitario_liquido'] === $topPriceValue;
            $item['is_worst_margin'] = $worstMarginValue !== null
                && ! $item['is_canceled']
                && $item['margem_percentual'] === $worstMarginValue;
            $item['margin_bar_percent'] = $item['margem_percentual'] === null
                ? 0
                : max(6, min(100, round(abs($item['margem_percentual']))));

            return $item;
        })->values();

        $frete = round((float) ($pedido->frete_valor ?? 0), 2);
        $ticketOriginal = round((float) $pedido->valor_total, 2);
        $estornoTotal = round((float) $revenueContext['estorno_total'], 2);
        $ticket = max(0.0, round($ticketOriginal - $estornoTotal, 2));
        $receitaLiquidaProdutos = $receitaItens;
        $lucroProdutos = round($receitaLiquidaProdutos - $custoConhecido, 2);
        $comissaoParceiroTotal = $taxaParceiro !== null
            ? round((float) $items->sum(fn (array $item) => $item['comissao_parceiro'] ?? 0), 2)
            : null;
        $lucroTicketAntesComissao = round($ticket - $custoConhecido, 2);
        $lucroTicket = round($lucroTicketAntesComissao - ($comissaoParceiroTotal ?? 0), 2);
        $margemProdutos = ($receitaLiquidaProdutos > 0 && $itensSemCusto === 0)
            ? round(($lucroProdutos / $receitaLiquidaProdutos) * 100, 2)
            : null;
        $margemTicket = ($ticket > 0 && $itensSemCusto === 0)
            ? round(($lucroTicket / $ticket) * 100, 2)
            : null;
        $paymentMix = $pedido->pagamentos
            ->groupBy(fn ($pagamento) => $pagamento->metodo ?? 'desconhecido')
            ->map(fn ($group, $method) => [
                'label' => match ($method) {
                    'pix' => 'Pix',
                    'cartao' => 'Cartão',
                    'boleto' => 'Boleto',
                    default => ucfirst((string) $method),
                },
                'count' => $group->count(),
                'value' => round((float) $group->sum('valor'), 2),
            ])
            ->values();
        $costComparison = $this->buildCostComparisonFromItems($pedido->itens);

        return [
            'summary' => [
                'receita_itens' => $receitaItens,
                'receita_liquida' => $receitaLiquidaProdutos,
                'receita_produtos_liquida' => $receitaLiquidaProdutos,
                'valor_total_original' => $ticketOriginal,
                'valor_total_pedido' => $ticket,
                'estorno_total' => $estornoTotal,
                'frete' => $frete,
                'desconto' => $desconto,
                'custo_total_estimado' => $custoConhecido,
                'comissao_parceiro_total' => $comissaoParceiroTotal,
                'parceiro_taxa_percentual' => $taxaParceiro,
                'parceiro_nome' => $partnerCommission['partner_name'],
                'parceiro_cupom_codigo' => $partnerCommission['coupon_code'],
                'lucro_ticket_antes_comissao' => $lucroTicketAntesComissao,
                'lucro_total_estimado' => $lucroTicket,
                'lucro_produtos_estimado' => $lucroProdutos,
                'lucro_ticket_estimado' => $lucroTicket,
                'margem_percentual_estimada' => $margemTicket,
                'margem_produtos_percentual' => $margemProdutos,
                'margem_ticket_percentual' => $margemTicket,
                'unidades' => $totalUnidades,
                'linhas' => $items->count(),
                'itens_sem_custo' => $itensSemCusto,
                'itens_com_custo_declarado' => $itensComCustoDeclarado,
                'itens_cancelados' => $itensCancelados,
            ],
            'items' => $items,
            'highlights' => [
                'top_price_item_ids' => $items->where('is_top_price', true)->pluck('id')->values()->all(),
                'worst_margin_item_ids' => $items->where('is_worst_margin', true)->pluck('id')->values()->all(),
            ],
            'payment_mix' => $paymentMix,
            'cost_comparison' => $costComparison,
        ];
    }

    private function formatCurrency(float $value): string
    {
        return 'R$ '.number_format($value, 2, ',', '.');
    }

    private function formatPercent(float $value): string
    {
        return number_format($value, 1, ',', '.').'%';
    }

    public function quickEditProduto(Request $request, $id)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $produto = Produto::findOrFail($id);
        $data = [];

        if ($request->filled('custo_compra')) {
            $data['custo_compra'] = $this->parseMoneyInput($request->custo_compra);
        } elseif ($request->has('custo_compra') && $request->custo_compra === '') {
            $data['custo_compra'] = null;
        }

        if ($request->filled('estoque')) {
            $data['estoque'] = max(0, (int) $request->estoque);
        }

        if (! empty($data)) {
            $produto->update($data);
            $produto->refresh();
        }

        return response()->json([
            'success' => true,
            'custo_compra' => $produto->custo_compra,
            'custo_efetivo' => $produto->custo_efetivo,
            'estoque' => $produto->estoque,
            'margem' => $produto->margem_bruta_percentual,
            'lucro' => $produto->lucro_bruto_unitario,
        ]);
    }

    public function quickStatusPedido(Request $request, $id)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $pedido = Pedido::with(['itens.produto', 'itens.produtoVariante'])->findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', Rule::in(PedidoStatus::adminValues())],
            'codigo_rastreio' => ['nullable', 'string', 'max:50', Rule::requiredIf(fn () => $request->input('status') === PedidoStatus::ENVIADO)],
            'use_catalog_costs' => ['nullable', 'boolean'],
        ]);

        $updates = ['status' => $validated['status']];

        if ($validated['status'] === PedidoStatus::ENVIADO) {
            $updates['codigo_rastreio'] = strtoupper(trim((string) $validated['codigo_rastreio']));
        }

        $preparationData = [];
        if ($validated['status'] === PedidoStatus::PROCESSANDO) {
            $preparationData = $this->collectPreparationItemData($pedido, $request, $request->boolean('use_catalog_costs'));
        }

        DB::transaction(function () use ($pedido, $updates, $preparationData) {
            if ($preparationData !== []) {
                $this->persistPreparationItemData($pedido, $preparationData);
            }

            $pedido->update($updates);
        });

        return response()->json([
            'success' => true,
            'status' => $pedido->status,
            'codigo_rastreio' => $pedido->codigo_rastreio,
        ]);
    }

    public function atualizarRastreio(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $pedido = Pedido::findOrFail($id);

        $validated = $request->validate([
            'codigo_rastreio' => 'nullable|string|max:50',
        ]);

        $pedido->update(['codigo_rastreio' => $validated['codigo_rastreio']]);

        return response()->json(['success' => true]);
    }
}
