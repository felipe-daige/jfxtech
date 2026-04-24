<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Enums\PedidoStatus;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use App\Models\Produto;
use App\Models\Categoria;
use App\Models\Pedido;
use App\Models\ItemPedido;
use App\Models\ProdutoImagem;
use App\Models\ProdutoVariante;
use App\Models\ProdutoOpcaoGrupo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProdutosExport;
use App\Support\ProdutoDescricaoFormatter;
use App\Services\PromotionSimulationService;

class AdminController extends Controller
{
    // Dashboard administrativo
    public function dashboard()
    {
        if (!Auth::check()) {
            return redirect()->route('site.login');
        }

        return view('admin.dashboard', $this->getDashboardAnalyticsData());
    }

    public function analytics()
    {
        if (!Auth::check()) {
            return redirect()->route('site.login');
        }

        return view('admin.analytics', $this->getDashboardAnalyticsData());
    }

    public function analyticsProductSearch(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $term = trim((string) $request->query('q', ''));
        if ($term === '') {
            return response()->json(['products' => []]);
        }

        $prefix   = $term . '%';
        $contains = '%' . $term . '%';

        $products = Produto::query()
            ->select(['id', 'nome', 'marca', 'slug', 'ativo'])
            ->where(function ($query) use ($contains) {
                $query
                    ->where('nome',  'ilike', $contains)
                    ->orWhere('marca', 'ilike', $contains)
                    ->orWhere('slug',  'ilike', $contains);
            })
            ->orderByRaw(
                "CASE
                    WHEN nome ILIKE ? THEN 0
                    WHEN COALESCE(marca, '') ILIKE ? THEN 1
                    WHEN slug ILIKE ? THEN 2
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
        if (!Auth::check()) {
            return redirect()->route('site.login');
        }

        $period = $this->resolveAnalyticsPeriod((string) $request->query('period', '30'));

        return view('admin.analytics-produto', array_merge(
            $this->getProductAnalyticsData($produto, $period),
            ['time_series' => $this->getProductTimeSeries($produto, $period)]
        ));
    }

    public function simulatePromotion(Request $request, PromotionSimulationService $simulationService)
    {
        if (!Auth::check()) {
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
        if (!Auth::check()) {
            return redirect()->route('site.login');
        }

        $produtos_analytics = Produto::select(
            'id', 'nome', 'marca', 'preco', 'preco_original', 'custo_compra',
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
        if (!Auth::check()) {
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
        
        $perPage  = min((int) $request->get('per_page', 24), 96);
        $viewMode = in_array($request->get('view_mode'), ['cards', 'lista']) ? $request->get('view_mode') : 'cards';
        $produtos = $query->orderBy('created_at', 'desc')->paginate($perPage);
        $categorias = Categoria::all();

        return view('admin.produtos', compact('produtos', 'categorias', 'perPage', 'viewMode'));
    }

    // Pesquisar produtos via AJAX
    public function pesquisarProdutos(Request $request)
    {
        if (!Auth::check()) {
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
        
        $perPage  = min((int) $request->get('per_page', 24), 96);
        $viewMode = in_array($request->get('view_mode'), ['cards', 'lista']) ? $request->get('view_mode') : 'cards';
        $produtos = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $partial = $viewMode === 'lista' ? 'admin.includes.produtos-lista-linhas' : 'admin.includes.produtos-lista';
        $html = view($partial, compact('produtos'))->render();

        $categorias = \App\Models\Categoria::all();
        $paginacaoHtml = view('admin.includes.paginacao', [
            'produtos'    => $produtos,
            'perPage'     => $perPage,
            'pesquisa'    => $request->get('pesquisa'),
            'categoriaId' => $request->get('categoria_id'),
            'categorias'  => $categorias,
        ])->render();

        return response()->json([
            'html'           => $html,
            'paginacao_html' => $paginacaoHtml,
            'total'          => $produtos->total(),
            'current_page'   => $produtos->currentPage(),
            'last_page'      => $produtos->lastPage(),
            'view_mode'      => $viewMode,
        ]);
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
            'peso' => $produto->peso ? number_format($produto->peso, 3, ',', '.') : '',
            'desconto_percentual' => $produto->desconto_percentual,
            'em_promocao' => $produto->em_promocao,
            'destaque' => $produto->destaque,
            'tags' => $produto->tags ?? [],
            'estoque' => $produto->estoque,
            'categoria_id' => $produto->categoria_id,
            'ativo' => $produto->ativo,
            'specs' => $produto->specs ?? [],
            'imagens' => $produto->imagens()->orderBy('ordem')->orderBy('id')->get()->map(function($imagem) {
                return [
                    'id' => $imagem->id,
                    'caminho' => $imagem->caminho,
                    'url' => asset('storage/' . $imagem->caminho),
                    'capa' => $imagem->capa
                ];
            })
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
            'peso' => 'nullable|numeric|min:0',
            'estoque' => 'required|integer|min:0',
            'categoria_id' => 'required|exists:categorias,id',
            'em_promocao' => 'nullable|boolean',
            'desconto_percentual' => 'nullable|numeric|min:0|max:100',
            'destaque' => 'nullable|boolean',
            'tags' => 'nullable|array|max:2',
            'tags.*' => 'string|max:50',
            'imagens.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048'
        ]);

        // Converter preço do formato brasileiro para decimal
        $preco = $this->parseMoneyInput($request->preco);
        
        // Validar se o preço convertido é um número válido
        if (!is_numeric($preco) || $preco < 0) {
            return redirect()->back()->withErrors(['preco' => 'Preço deve ser um valor válido maior ou igual a zero.'])->withInput();
        }

        $custoCompra = $this->parseMoneyInput($request->custo_compra);
        if ($request->filled('custo_compra') && (!is_numeric($custoCompra) || $custoCompra < 0)) {
            return redirect()->back()->withErrors(['custo_compra' => 'Custo de compra deve ser um valor válido maior ou igual a zero.'])->withInput();
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

        $specs = array_filter($request->input('specs', []), fn($v) => $v !== null && $v !== '');
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
            'peso' => $request->peso, // Peso em KG
            'preco_original' => $emPromocao ? $preco : null, // Preço original (sem desconto)
            'desconto_percentual' => $descontoPercentual,
            'em_promocao' => $emPromocao,
            'destaque' => $destaque,
            'tags' => $request->input('tags', []),
            'estoque' => $request->estoque,
            'categoria_id' => $request->categoria_id,
            'specs' => !empty($specs) ? $specs : null,
            'ativo' => true // Produtos são ativos por padrão
        ]);

        // Upload de imagens
        if ($request->hasFile('imagens')) {
            $primeiraImagem = true;
            foreach ($request->file('imagens') as $imagem) {
                $caminho = $imagem->store('produtos', 'public');
                ProdutoImagem::create([
                    'produto_id' => $produto->id,
                    'caminho' => $caminho,
                    'capa' => $primeiraImagem
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
            'peso' => 'nullable|numeric|min:0',
            'estoque' => 'required|integer|min:0',
            'categoria_id' => 'required|exists:categorias,id',
            'em_promocao' => 'nullable|boolean',
            'desconto_percentual' => 'nullable|numeric|min:0|max:100',
            'destaque' => 'nullable|boolean',
            'tags' => 'nullable|array|max:2',
            'tags.*' => 'string|max:50',
            'imagens.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'imagem_capa_id' => 'nullable|exists:produto_imagens,id'
        ]);

        // Converter preço do formato brasileiro para decimal
        $preco = $this->parseMoneyInput($request->preco);
        
        // Validar se o preço convertido é um número válido
        if (!is_numeric($preco) || $preco < 0) {
            return redirect()->back()->withErrors(['preco' => 'Preço deve ser um valor válido maior ou igual a zero.'])->withInput();
        }

        $custoCompra = $this->parseMoneyInput($request->custo_compra);
        if ($request->filled('custo_compra') && (!is_numeric($custoCompra) || $custoCompra < 0)) {
            return redirect()->back()->withErrors(['custo_compra' => 'Custo de compra deve ser um valor válido maior ou igual a zero.'])->withInput();
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

        $specs = array_filter($request->input('specs', []), fn($v) => $v !== null && $v !== '');
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
            'peso' => $request->peso, // Peso em KG
            'preco_original' => $emPromocao ? $preco : null, // Preço original (sem desconto)
            'desconto_percentual' => $descontoPercentual,
            'em_promocao' => $emPromocao,
            'destaque' => $destaque,
            'tags' => $request->input('tags', []),
            'estoque' => $request->estoque,
            'categoria_id' => $request->categoria_id,
            'specs' => !empty($specs) ? $specs : null,
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
            'ativo' => 'required|boolean'
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
            'destaque' => 'required|boolean'
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
        if (!Auth::check()) {
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
        if (!Auth::check()) {
            return redirect()->route('site.login');
        }

        $imagem = ProdutoImagem::findOrFail($id);

        if (!Storage::disk('public')->exists($imagem->caminho)) {
            abort(404, 'Arquivo não encontrado');
        }

        return Storage::disk('public')->download($imagem->caminho);
    }

    // Substituir imagem individual de produto
    public function substituirImagem(Request $request, $id)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $request->validate([
            'imagem' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
        ]);

        $imagem = ProdutoImagem::findOrFail($id);
        $eraCapa = $imagem->capa;

        Storage::disk('public')->delete($imagem->caminho);

        $novoCaminho = $request->file('imagem')->store('produtos', 'public');
        $imagem->update(['caminho' => $novoCaminho]);

        return response()->json([
            'success' => true,
            'url' => asset('storage/' . $novoCaminho),
            'capa' => $eraCapa
        ]);
    }

    public function reordenarImagens(Request $request, $produtoId)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $request->validate([
            'ordem'           => 'required|array',
            'ordem.*.id'      => 'required|integer|exists:produto_imagens,id',
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
        if (!Auth::check()) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $action = $request->input('action'); // 'delete' | 'ativar' | 'desativar' | 'set_exclusivo' | 'remove_exclusivo' | 'set_em_breve' | 'remove_em_breve'
        $ids    = $request->input('ids', []);

        if (empty($ids) || !in_array($action, ['delete', 'ativar', 'desativar', 'set_exclusivo', 'remove_exclusivo', 'set_em_breve', 'remove_em_breve'])) {
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
                    if (!in_array('Exclusivo', $tags)) {
                        $tags[] = 'Exclusivo';
                        // Limitar a apenas 2 tags (preservando a mais recente)
                        if (count($tags) > 2) $tags = array_slice($tags, -2);
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
                    if (!in_array('Em Breve', $tags)) {
                        $tags[] = 'Em Breve';
                        if (count($tags) > 2) $tags = array_slice($tags, -2);
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
        if (!Auth::check()) {
            return redirect()->route('site.login');
        }

        $query = Pedido::with('user', 'itens.produto')->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('data')) {
            $query->whereDate('created_at', $request->input('data'));
        }

        $pedidos = $query->paginate(10)->withQueryString();

        return view('admin.pedidos', compact('pedidos'));
    }

    // Retorna HTML dos detalhes do pedido para o modal
    public function detalhesPedido($id)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $pedido = Pedido::with([
            'user',
            'endereco',
            'itens.produto.imagens',
            'itens.produtoVariante',
            'pagamentos'
        ])->findOrFail($id);

        $analytics = $this->buildOrderDetailAnalytics($pedido);

        $html = view('admin.includes.pedido-detalhes', compact('pedido', 'analytics'))->render();
        return response()->json(['html' => $html]);
    }

    // Atualizar status do pedido
    public function atualizarStatusPedido(Request $request, $id)
    {
        $pedido = Pedido::findOrFail($id);
        
        $validated = $request->validate([
            'status' => ['required', Rule::in(PedidoStatus::adminValues())],
            'codigo_rastreio' => ['nullable', 'string', 'max:50', Rule::requiredIf(fn () => $request->input('status') === PedidoStatus::ENVIADO)],
        ]);

        $updates = ['status' => $validated['status']];

        if ($validated['status'] === PedidoStatus::ENVIADO) {
            $updates['codigo_rastreio'] = strtoupper(trim((string) $validated['codigo_rastreio']));
        }

        $pedido->update($updates);
        
        return redirect()->route('admin.pedidos')->with('success', 'Status do pedido atualizado com sucesso!');
    }

    // Gerenciar categorias
    public function categorias()
    {
        if (!Auth::check()) {
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
            'descricao' => 'nullable|string'
        ]);

        Categoria::create([
            'nome' => $request->nome,
            'descricao' => $request->descricao
        ]);

        return redirect()->route('admin.categorias')->with('success', 'Categoria criada com sucesso!');
    }

    // Editar categoria
    public function editarCategoria(Request $request, $id)
    {
        $categoria = Categoria::findOrFail($id);
        
        $request->validate([
            'nome' => 'required|string|max:255|unique:categorias,nome,' . $id,
            'descricao' => 'nullable|string'
        ]);

        $categoria->update([
            'nome' => $request->nome,
            'descricao' => $request->descricao
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
        if (!Auth::check()) abort(403);

        $ids = $request->input('ids', []);
        return Excel::download(new ProdutosExport($ids), 'produtos-jfx-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function exportarAnalyticsCsv()
    {
        if (!Auth::check()) {
            return redirect()->route('site.login');
        }

        $analytics = $this->getDashboardAnalyticsData();
        $filename = 'analytics-dashboard-jfx-' . now()->format('Y-m-d-His') . '.csv';

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
            fputcsv($handle, ['Produtos Analytics'], ';');
            fputcsv($handle, ['Nome', 'Marca', 'Preco Venda', 'Custo', 'Lucro Unit.', 'Margem %', 'Estoque', 'Status'], ';');

            foreach ($analytics['produtos_analytics'] as $produto) {
                fputcsv($handle, [
                    $produto->nome,
                    $produto->marca ?? '—',
                    $this->formatCurrency($produto->preco_com_desconto),
                    $produto->custo_compra !== null ? $this->formatCurrency((float) $produto->custo_compra) : '—',
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
        if (!Auth::check()) {
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

        return $pdf->download('analytics-dashboard-jfx-' . now()->format('Y-m-d-His') . '.pdf');
    }

    public function buscarOpcoesProduto($id)
    {
        if (!Auth::check()) {
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
                    if (!isset($valorMap[$vid])) return false;
                }
                return true;
            })
            ->values()
            ->map(function ($v) use ($valorMap) {
                $label = collect($v->valores)->map(fn($vid) => $valorMap[$vid]['valor'] ?? '?')->join(' / ');
                return [
                    'id'          => $v->id,
                    'valores'     => $v->valores,
                    'preco'       => $v->preco,
                    'custo_compra'=> $v->custo_compra,
                    'estoque'     => $v->estoque,
                    'ativo'       => $v->ativo,
                    'descricao'   => $v->descricao,   // null if inheriting from product
                    'specs'       => $v->specs,       // null if inheriting from product
                    'label'       => $label,
                    'imagem_ids'  => $v->imagens->pluck('id')->all(),
                ];
            });

        return response()->json([
            'grupos'   => $produto->opcaoGrupos->map(fn($g) => [
                'id'      => $g->id,
                'nome'    => $g->nome,
                'ordem'   => $g->ordem,
                'valores' => $g->valores->map(fn($v) => [
                    'id'    => $v->id,
                    'valor' => $v->valor,
                    'ordem' => $v->ordem,
                ]),
            ]),
            'variantes'             => $variantes,
            'estoque_compartilhado' => $produto->estoque_compartilhado,
            'imagens'               => $produto->imagens->map(fn($img) => [
                'id'  => $img->id,
                'url' => asset('storage/' . $img->caminho),
            ]),
        ]);
    }

    public function salvarOpcaoGrupos(Request $request, $id)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $produto = Produto::findOrFail($id);

        $request->validate([
            'grupos'                    => 'array',
            'grupos.*.nome'             => 'required|string|max:50',
            'grupos.*.ordem'            => 'nullable|integer',
            'grupos.*.valores'          => 'nullable|array',
            'grupos.*.valores.*.valor'  => 'required|string|max:100',
            'grupos.*.valores.*.ordem'  => 'nullable|integer',
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
                ->flatMap(fn($g) => $g->valores->pluck('id'))
                ->all();

            // Collect IDs that will survive (values whose groups/names are in the request)
            $nomesNovos = collect($request->grupos)->pluck('nome')->all();
            $valoresNoRequest = collect($request->grupos)->flatMap(fn($g) => collect($g['valores'] ?? [])->pluck('valor'))->all();

            // Get IDs of values being kept (groups in request + values in those groups)
            $idsAManter = $produto->opcaoGrupos()
                ->with('valores')
                ->whereIn('nome', $nomesNovos)
                ->get()
                ->flatMap(fn($g) => $g->valores->whereIn('valor', $valoresNoRequest)->pluck('id'))
                ->all();

            // Delete (or deactivate if referenced by orders) variants that contain any valor ID being removed
            $idsRemovidos = array_diff($currentValorIds, $idsAManter);
            if (!empty($idsRemovidos)) {
                foreach ($produto->variantes()->get() as $variante) {
                    $variantValorIds = $variante->valores ?? [];
                    if (!empty(array_intersect($variantValorIds, $idsRemovidos))) {
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

                if (!empty($grupoData['valores'])) {
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
        if (!Auth::check()) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $produto = Produto::with('opcaoGrupos.valores')->findOrFail($id);
        $grupos = $produto->opcaoGrupos;

        if ($grupos->isEmpty()) {
            return response()->json(['success' => true, 'geradas' => 0]);
        }

        // Build cartesian product of valor_id arrays
        $sets = $grupos->map(fn($g) => $g->valores->pluck('id')->all())->all();
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
                        ->whereRaw("valores::text = ?", [$valoresJson])
                        ->exists();
                } else {
                    // SQLite (tests): cast to text via JSON function
                    $existe = $produto->variantes()
                        ->whereRaw("CAST(valores AS TEXT) = ?", [$valoresJson])
                        ->exists();
                }

                if (!$existe) {
                    ProdutoVariante::create([
                        'produto_id' => $produto->id,
                        'valores'    => $combo,
                        'preco'      => null,
                        'custo_compra' => null,
                        'estoque'    => null,
                        'ativo'      => true,
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
        if (!Auth::check()) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $produto = Produto::findOrFail($id);

        $request->validate([
            'estoque_compartilhado'      => 'nullable|boolean',
            'variantes'                  => 'nullable|array',
            'variantes.*.id'             => 'required|integer',
            'variantes.*.preco'          => 'nullable|numeric|min:0',
            'variantes.*.custo_compra'   => 'nullable|numeric|min:0',
            'variantes.*.estoque'        => 'nullable|integer|min:0',
            'variantes.*.ativo'          => 'nullable|boolean',
            'variantes.*.imagem_ids'     => 'nullable|array',
            'variantes.*.imagem_ids.*'   => 'integer',
            'variantes.*.descricao'      => 'nullable|string',
            'variantes.*.specs'          => 'nullable|array',
            'variantes.*.specs.*'        => 'nullable|string',
        ]);

        \DB::transaction(function () use ($request, $produto) {
            if ($request->has('estoque_compartilhado')) {
                $produto->update(['estoque_compartilhado' => $request->boolean('estoque_compartilhado')]);
            }

            foreach ($request->input('variantes', []) as $varData) {
                $variante = ProdutoVariante::where('id', $varData['id'])
                    ->where('produto_id', $produto->id)
                    ->first();
                if (!$variante) continue; // skip gracefully — variant deleted/replaced by rename

                $update = [];
                if (array_key_exists('preco', $varData))   $update['preco']   = $varData['preco'];
                if (array_key_exists('custo_compra', $varData)) $update['custo_compra'] = $varData['custo_compra'];
                if (array_key_exists('estoque', $varData)) $update['estoque'] = $varData['estoque'];
                if (array_key_exists('ativo', $varData))   $update['ativo']   = $varData['ativo'];

                if (array_key_exists('descricao', $varData)) {
                    $raw = $varData['descricao'];
                    if ($raw === null || $raw === '' || ProdutoDescricaoFormatter::toPlainText((string)$raw) === '') {
                        $update['descricao'] = null;
                    } else {
                        $update['descricao'] = ProdutoDescricaoFormatter::sanitize($raw);
                    }
                }

                if (array_key_exists('specs', $varData)) {
                    $filtered = array_filter((array)($varData['specs'] ?? []), fn($v) => $v !== null && $v !== '');
                    $update['specs'] = !empty($filtered) ? $filtered : null;
                }

                if (!empty($update)) $variante->update($update);

                if (array_key_exists('imagem_ids', $varData)) {
                    $imagem_ids = array_filter((array)($varData['imagem_ids'] ?? []), 'is_numeric');
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

        $normalized = str_replace(['R$ ', '.'], '', $normalized);
        $normalized = str_replace(',', '.', $normalized);

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function resolveItemCost(ItemPedido $item): ?float
    {
        if ($item->produtoVariante && $item->produtoVariante->custo_compra !== null) {
            return (float) $item->produtoVariante->custo_compra;
        }

        if ($item->produto && $item->produto->custo_compra !== null) {
            return (float) $item->produto->custo_compra;
        }

        return null;
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

    private function getProductAnalyticsData(Produto $produto, string $period): array
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
                'pedido:id,status,created_at,valor_total,frete_valor,valor_desconto',
                'produto:id,custo_compra',
                'produtoVariante:id,produto_id,custo_compra',
            ])
            ->where('produto_id', $produto->id)
            ->whereHas('pedido', function ($query) use ($performanceStatuses, $periodStart) {
                $query->whereIn('status', $performanceStatuses);

                if ($periodStart) {
                    $query->where('created_at', '>=', $periodStart);
                }
            });

        $itens = $itensQuery->get();
        $receitaTotal = 0.0;
        $custoTotal = 0.0;
        $unidadesVendidas = 0;
        $itensSemCusto = 0;

        foreach ($itens as $item) {
            $valorBruto = (float) $item->preco * (int) $item->quantidade;

            $pedidoValorTotal = (float) ($item->pedido->valor_total ?? 0);
            $pedidoFrete      = (float) ($item->pedido->frete_valor ?? 0);
            $pedidoDesconto   = (float) ($item->pedido->valor_desconto ?? 0);
            $pedidoSubtotal   = $pedidoValorTotal - $pedidoFrete + $pedidoDesconto;
            $fatorLiquido     = $pedidoSubtotal > 0
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

    private function getProductTimeSeries(Produto $produto, string $period): array
    {
        $performanceStatuses = PedidoStatus::performanceValues();
        $periodStart = match ($period) {
            '30'  => now()->subDays(30),
            '90'  => now()->subDays(90),
            '365' => now()->subDays(365),
            default => null,
        };

        $itens = ItemPedido::query()
            ->with([
                'pedido:id,status,created_at,valor_total,frete_valor,valor_desconto',
                'produto:id,custo_compra',
                'produtoVariante:id,produto_id,custo_compra',
            ])
            ->where('produto_id', $produto->id)
            ->whereHas('pedido', function ($query) use ($performanceStatuses, $periodStart) {
                $query->whereIn('status', $performanceStatuses);
                if ($periodStart) {
                    $query->where('created_at', '>=', $periodStart);
                }
            })
            ->get();

        $groupBy = match ($period) {
            '30'    => fn ($item) => Carbon::parse($item->pedido->created_at)->format('Y-m-d'),
            '90'    => fn ($item) => Carbon::parse($item->pedido->created_at)->startOfWeek()->format('Y-m-d'),
            '365'   => fn ($item) => Carbon::parse($item->pedido->created_at)->startOfWeek()->format('Y-m-d'),
            default => fn ($item) => Carbon::parse($item->pedido->created_at)->format('Y-m'),
        };

        $groups = [];
        foreach ($itens as $item) {
            $key = $groupBy($item);
            if (!isset($groups[$key])) {
                $groups[$key] = ['receita' => 0.0, 'custo' => 0.0, 'unidades' => 0];
            }

            $valorBruto  = (float) $item->preco * (int) $item->quantidade;
            $pedidoTotal = (float) ($item->pedido->valor_total ?? 0);
            $pedidoFrete = (float) ($item->pedido->frete_valor ?? 0);
            $pedidoDesc  = (float) ($item->pedido->valor_desconto ?? 0);
            $subtotal    = $pedidoTotal - $pedidoFrete + $pedidoDesc;
            $fator       = $subtotal > 0 ? ($pedidoTotal - $pedidoFrete) / $subtotal : 1.0;

            $groups[$key]['receita']  += $valorBruto * $fator;
            $groups[$key]['unidades'] += (int) $item->quantidade;

            $custo = $this->resolveItemCost($item) ?? 0.0;
            $groups[$key]['custo'] += $custo * (int) $item->quantidade;
        }

        ksort($groups);

        $result = [];
        foreach ($groups as $date => $data) {
            $receita  = round($data['receita'], 2);
            $lucro    = $receita - round($data['custo'], 2);
            $margem   = $receita > 0 ? round(($lucro / $receita) * 100, 2) : 0.0;
            $result[] = [
                'date'     => $date,
                'receita'  => $receita,
                'unidades' => $data['unidades'],
                'margem'   => $margem,
            ];
        }

        return $result;
    }

    private function getDashboardAnalyticsData(): array
    {
        $performanceStatuses = PedidoStatus::performanceValues();
        $total_produtos      = Produto::count();
        $total_pedidos       = Pedido::count();
        $pedidos_nao_finalizados = Pedido::whereIn('status', [PedidoStatus::CARRINHO, PedidoStatus::PENDENTE])->count();
        $pedidos_pagos       = Pedido::where('status', PedidoStatus::PAGO)->count();
        $pedidos_pendentes   = Pedido::where('status', PedidoStatus::PENDENTE)->count();
        $pedidos_processando = Pedido::where('status', PedidoStatus::PROCESSANDO)->count();
        $pedidos_enviados    = Pedido::where('status', PedidoStatus::ENVIADO)->count();
        $pedidos_entregues   = Pedido::where('status', PedidoStatus::ENTREGUE)->count();
        $pedidos_cancelados  = Pedido::where('status', PedidoStatus::CANCELADO)->count();
        $pedidos_performance = Pedido::whereIn('status', $performanceStatuses);
        $receita_total       = (float) (clone $pedidos_performance)
            ->selectRaw('COALESCE(SUM(valor_total - COALESCE(frete_valor, 0)), 0) as receita')
            ->value('receita');

        $itensPerformance = ItemPedido::with([
            'produto:id,custo_compra',
            'produtoVariante:id,produto_id,custo_compra',
        ])->whereHas('pedido', function ($query) {
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
        $margem_bruta_percentual = $receita_total > 0
            ? round(($lucro_bruto_total / $receita_total) * 100, 2)
            : 0;

        $produtos_analytics = Produto::select(
            'id',
            'nome',
            'marca',
            'preco',
            'preco_original',
            'custo_compra',
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
            fn($p) => $p->custo_compra !== null && $p->margem_bruta_percentual !== null && $p->margem_bruta_percentual < 0
        );
        $produtosMargemZero = $produtos_analytics->filter(
            fn($p) => $p->custo_compra !== null && $p->margem_bruta_percentual !== null && $p->margem_bruta_percentual == 0
        );
        $produtosMargemBaixa = $produtos_analytics->filter(
            fn($p) => $p->custo_compra !== null && $p->margem_bruta_percentual !== null
                && $p->margem_bruta_percentual > 0 && $p->margem_bruta_percentual < $margemMinima
        );

        $alertas = [
            'margem_negativa'          => $produtosMargemNeg->count(),
            'margem_zero'              => $produtosMargemZero->count(),
            'margem_baixa'             => $produtosMargemBaixa->count(),
            'estoque_zerado'           => $produtosEstoqueZerado->count(),
            'sem_custo'                => $produtosSemCusto->count(),
            'inativos'                 => $produtos_analytics->where('ativo', false)->count(),
            'produtos_sem_custo'       => $produtosSemCusto->values(),
            'produtos_estoque_zerado'  => $produtosEstoqueZerado->values(),
            'produtos_margem_negativa' => $produtosMargemNeg->values(),
            'produtos_margem_zero'     => $produtosMargemZero->values(),
            'produtos_margem_baixa'    => $produtosMargemBaixa->values(),
        ];

        $top_produtos = ItemPedido::select(
            'produto_id',
            \DB::raw('SUM(quantidade) as total_vendido'),
            \DB::raw('SUM(preco * quantidade) as receita_gerada')
        )->whereHas('pedido', fn($q) => $q->whereIn('status', $performanceStatuses))
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
            ->select('categorias.nome', \DB::raw('SUM(itens_pedido.preco * itens_pedido.quantidade) as receita'))
            ->groupBy('categorias.id', 'categorias.nome')
            ->orderByDesc('receita')
            ->get();

        $total_pedidos_performance = (clone $pedidos_performance)->count();
        $ticket_medio   = $total_pedidos_performance > 0 ? round($receita_total / $total_pedidos_performance, 2) : 0;
        $total_unidades = ItemPedido::whereHas('pedido', fn($q) => $q->whereIn('status', $performanceStatuses))->sum('quantidade');
        $total_ativos   = Produto::where('ativo', true)->count();

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
            'custo_total',
            'lucro_bruto_total',
            'margem_bruta_percentual',
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
            'sla_enviado_sem_entregar'
        );
    }

    private function analyticsSummaryRows(array $analytics): array
    {
        return [
            ['Receita Total', $this->formatCurrency($analytics['receita_total'])],
            ['Lucro Bruto', $this->formatCurrency($analytics['lucro_bruto_total'])],
            ['Margem Bruta', $this->formatPercent($analytics['margem_bruta_percentual'])],
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
            ->filter(fn($produto) => $produto->ativo
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
                    'custo_compra' => (float) $produto->custo_compra,
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
                ->filter(fn(array $produto) => $produto['exclusive_signal'] !== 'none')
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
            ->filter(fn($tag) => is_string($tag))
            ->map(fn(string $tag) => Str::lower(trim($tag)));

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
        $items = $pedido->itens->map(function (ItemPedido $item) {
            $receita = round((float) $item->preco * (int) $item->quantidade, 2);
            $custoUnitario = $this->resolveItemCost($item);
            $custoTotal = $custoUnitario !== null ? round($custoUnitario * (int) $item->quantidade, 2) : null;
            $lucroUnitario = $custoUnitario !== null ? round((float) $item->preco - $custoUnitario, 2) : null;
            $lucroTotal = $custoTotal !== null ? round($receita - $custoTotal, 2) : null;
            $margemPercentual = ($custoUnitario !== null && $receita > 0)
                ? round((($receita - $custoTotal) / $receita) * 100, 2)
                : null;
            $opcoes = collect($item->opcoes_snapshot ?? [])
                ->filter(fn ($value, $key) => filled($key) && filled($value))
                ->map(fn ($value, $key) => $key . ': ' . (is_array($value) ? implode(', ', $value) : $value))
                ->values();

            $health = 'healthy';
            if ($custoUnitario === null) {
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
                'custo_unitario' => $custoUnitario,
                'custo_total' => $custoTotal,
                'lucro_unitario' => $lucroUnitario,
                'lucro_total' => $lucroTotal,
                'margem_percentual' => $margemPercentual,
                'cost_source' => $item->produtoVariante?->custo_compra !== null ? 'variante' : ($item->produto?->custo_compra !== null ? 'produto' : 'sem_custo'),
                'variant_label' => $opcoes->isNotEmpty() ? $opcoes->implode(' · ') : null,
                'variant_lines' => $opcoes,
                'image_path' => optional($item->produto?->imagens?->firstWhere('capa', true) ?: $item->produto?->imagens?->first())->caminho,
                'health' => $health,
            ];
        })->values();

        $receitaItens = round((float) $items->sum('receita'), 2);
        $custoConhecido = round((float) $items->sum(fn (array $item) => $item['custo_total'] ?? 0), 2);
        $lucroConhecido = round((float) $items->sum(fn (array $item) => $item['lucro_total'] ?? 0), 2);
        $totalUnidades = (int) $items->sum('quantidade');
        $itensSemCusto = (int) $items->where('cost_source', 'sem_custo')->count();

        $rankByProfit = $items
            ->sortByDesc(fn (array $item) => $item['lucro_total'] ?? PHP_FLOAT_MIN)
            ->pluck('id')
            ->values();

        $rankByRevenue = $items
            ->sortByDesc('receita')
            ->pluck('id')
            ->values();

        $rankByMargin = $items
            ->sortBy(fn (array $item) => $item['margem_percentual'] ?? PHP_FLOAT_MAX)
            ->pluck('id')
            ->values();

        $topProfitItemId = $rankByProfit->first();
        $worstMarginItemId = $rankByMargin->first();

        $items = $items->map(function (array $item) use ($receitaItens, $lucroConhecido, $rankByRevenue, $rankByMargin, $topProfitItemId, $worstMarginItemId) {
            $receitaShare = $receitaItens > 0 ? round(($item['receita'] / $receitaItens) * 100, 2) : 0;
            $lucroShare = ($lucroConhecido != 0 && $item['lucro_total'] !== null)
                ? round(($item['lucro_total'] / $lucroConhecido) * 100, 2)
                : null;

            $item['receita_share_percentual'] = $receitaShare;
            $item['lucro_share_percentual'] = $lucroShare;
            $item['rank_receita'] = $rankByRevenue->search($item['id']) + 1;
            $item['rank_margem'] = $rankByMargin->search($item['id']) + 1;
            $item['is_top_profit'] = $item['id'] === $topProfitItemId;
            $item['is_worst_margin'] = $item['id'] === $worstMarginItemId;
            $item['margin_bar_percent'] = $item['margem_percentual'] === null
                ? 0
                : max(6, min(100, round(abs($item['margem_percentual']))));

            return $item;
        })->values();

        $frete = round((float) ($pedido->frete_valor ?? 0), 2);
        $desconto = round((float) ($pedido->valor_desconto ?? 0), 2);
        $receitaLiquida = max(0, round($receitaItens - $desconto, 2));
        $lucroLiquido = round($receitaLiquida - $custoConhecido, 2);
        $margemPedido = ($receitaLiquida > 0 && $itensSemCusto === 0)
            ? round(($lucroLiquido / $receitaLiquida) * 100, 2)
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

        return [
            'summary' => [
                'receita_itens' => $receitaItens,
                'receita_liquida' => $receitaLiquida,
                'valor_total_pedido' => round((float) $pedido->valor_total, 2),
                'frete' => $frete,
                'desconto' => $desconto,
                'custo_total_estimado' => $custoConhecido,
                'lucro_total_estimado' => $lucroLiquido,
                'margem_percentual_estimada' => $margemPedido,
                'unidades' => $totalUnidades,
                'linhas' => $items->count(),
                'itens_sem_custo' => $itensSemCusto,
            ],
            'items' => $items,
            'highlights' => [
                'top_profit_item_id' => $topProfitItemId,
                'worst_margin_item_id' => $worstMarginItemId,
            ],
            'payment_mix' => $paymentMix,
        ];
    }

    private function formatCurrency(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }

    private function formatPercent(float $value): string
    {
        return number_format($value, 1, ',', '.') . '%';
    }

    public function quickEditProduto(Request $request, $id)
    {
        if (!Auth::check()) {
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

        if (!empty($data)) {
            $produto->update($data);
            $produto->refresh();
        }

        return response()->json([
            'success'      => true,
            'custo_compra' => $produto->custo_compra,
            'estoque'      => $produto->estoque,
            'margem'       => $produto->margem_bruta_percentual,
            'lucro'        => $produto->lucro_bruto_unitario,
        ]);
    }

    public function quickStatusPedido(Request $request, $id)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $pedido = Pedido::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', Rule::in(PedidoStatus::adminValues())],
            'codigo_rastreio' => ['nullable', 'string', 'max:50', Rule::requiredIf(fn () => $request->input('status') === PedidoStatus::ENVIADO)],
        ]);

        $updates = ['status' => $validated['status']];

        if ($validated['status'] === PedidoStatus::ENVIADO) {
            $updates['codigo_rastreio'] = strtoupper(trim((string) $validated['codigo_rastreio']));
        }

        $pedido->update($updates);

        return response()->json([
            'success' => true,
            'status'  => $pedido->status,
            'codigo_rastreio' => $pedido->codigo_rastreio,
        ]);
    }

    public function atualizarRastreio(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        if (!Auth::check()) {
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
