<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produto;
use App\Models\Categoria;
use App\Models\Pedido;
use App\Models\ItemPedido;
use App\Models\ProdutoImagem;
use App\Models\ProdutoVariante;
use App\Models\ProdutoOpcaoGrupo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProdutosExport;
use App\Support\ProdutoDescricaoFormatter;

class AdminController extends Controller
{
    // Dashboard administrativo
    public function dashboard()
    {
        if (!Auth::check()) {
            return redirect()->route('site.login');
        }

        // Métricas de pedidos
        $total_produtos      = Produto::count();
        $total_pedidos       = Pedido::count();
        $pedidos_pendentes   = Pedido::where('status', 'pendente')->count();
        $pedidos_processando = Pedido::where('status', 'processando')->count();
        $pedidos_enviados    = Pedido::where('status', 'enviado')->count();
        $pedidos_entregues   = Pedido::where('status', 'entregue')->count();
        $pedidos_cancelados  = Pedido::where('status', 'cancelado')->count();
        $receita_total       = Pedido::where('status', 'entregue')->sum('valor_total');

        // Custo e lucro (apenas pedidos entregues)
        $itensEntregues = ItemPedido::with([
            'produto:id,custo_compra',
            'produtoVariante:id,produto_id,custo_compra',
        ])->whereHas('pedido', function ($query) {
            $query->where('status', 'entregue');
        })->get();

        $custo_total     = 0;
        $itens_sem_custo = 0;

        foreach ($itensEntregues as $item) {
            $custoUnitario = $this->resolveItemCost($item);

            if ($custoUnitario === null) {
                $itens_sem_custo += $item->quantidade;
                $custoUnitario = 0;
            }

            $custo_total += $item->quantidade * $custoUnitario;
        }

        $lucro_bruto_total       = $receita_total - $custo_total;
        $margem_bruta_percentual = $receita_total > 0
            ? round(($lucro_bruto_total / $receita_total) * 100, 2)
            : 0;

        // Produtos para analytics e alertas
        $produtos_analytics = Produto::select(
            'id', 'nome', 'marca', 'preco', 'preco_original', 'custo_compra',
            'desconto_percentual', 'em_promocao', 'estoque', 'ativo'
        )->with('imagemCapa:id,produto_id')->orderBy('nome')->get();

        // Collections para alertas expansíveis
        $margemMinima          = 20;
        $produtosSemCusto      = $produtos_analytics->whereNull('custo_compra');
        $produtosEstoqueZerado = $produtos_analytics->where('ativo', true)->where('estoque', 0);
        $produtosMargemNeg     = $produtos_analytics->filter(
            fn($p) => $p->custo_compra !== null && $p->margem_bruta_percentual !== null && $p->margem_bruta_percentual < 0
        );
        $produtosMargemZero    = $produtos_analytics->filter(
            fn($p) => $p->custo_compra !== null && $p->margem_bruta_percentual !== null && $p->margem_bruta_percentual == 0
        );
        $produtosMargemBaixa   = $produtos_analytics->filter(
            fn($p) => $p->custo_compra !== null && $p->margem_bruta_percentual !== null
                && $p->margem_bruta_percentual > 0 && $p->margem_bruta_percentual < $margemMinima
        );

        $alertas = [
            'margem_negativa'          => $produtosMargemNeg->count(),
            'margem_zero'              => $produtosMargemZero->count(),
            'margem_baixa'             => $produtosMargemBaixa->count(),
            'estoque_zerado'           => $produtosEstoqueZerado->count(),
            'sem_custo'                => $produtosSemCusto->count(),
            'sem_imagem'               => $produtos_analytics->where('ativo', true)
                                             ->filter(fn($p) => $p->imagemCapa === null)->count(),
            'inativos'                 => $produtos_analytics->where('ativo', false)->count(),
            'produtos_sem_custo'       => $produtosSemCusto->values(),
            'produtos_estoque_zerado'  => $produtosEstoqueZerado->values(),
            'produtos_margem_negativa' => $produtosMargemNeg->values(),
            'produtos_margem_zero'     => $produtosMargemZero->values(),
            'produtos_margem_baixa'    => $produtosMargemBaixa->values(),
        ];

        // Performance
        $top_produtos = ItemPedido::select(
            'produto_id',
            \DB::raw('SUM(quantidade) as total_vendido'),
            \DB::raw('SUM(preco * quantidade) as receita_gerada')
        )->whereHas('pedido', fn($q) => $q->where('status', 'entregue'))
            ->groupBy('produto_id')
            ->orderByDesc('total_vendido')
            ->limit(5)
            ->with('produto:id,nome,marca')
            ->get();

        $receita_categoria = \DB::table('itens_pedido')
            ->join('pedidos', 'pedidos.id', '=', 'itens_pedido.pedido_id')
            ->join('produtos', 'produtos.id', '=', 'itens_pedido.produto_id')
            ->join('categorias', 'categorias.id', '=', 'produtos.categoria_id')
            ->where('pedidos.status', 'entregue')
            ->select('categorias.nome', \DB::raw('SUM(itens_pedido.preco * itens_pedido.quantidade) as receita'))
            ->groupBy('categorias.id', 'categorias.nome')
            ->orderByDesc('receita')
            ->get();

        $ticket_medio   = $pedidos_entregues > 0 ? round($receita_total / $pedidos_entregues, 2) : 0;
        $total_unidades = ItemPedido::whereHas('pedido', fn($q) => $q->where('status', 'entregue'))->sum('quantidade');
        $total_ativos   = Produto::where('ativo', true)->count();

        // Pedidos que precisam de ação
        $pedidos_acao = Pedido::whereIn('status', ['pendente', 'processando'])
            ->with('user:id,name')
            ->orderBy('created_at')
            ->get();

        $pedidos_recentes = Pedido::with('user')->orderBy('created_at', 'desc')->limit(5)->get();

        return view('admin.dashboard', compact(
            'total_produtos', 'total_pedidos', 'pedidos_pendentes',
            'pedidos_processando', 'pedidos_enviados', 'pedidos_entregues', 'pedidos_cancelados',
            'receita_total', 'custo_total', 'lucro_bruto_total', 'margem_bruta_percentual',
            'itens_sem_custo', 'pedidos_recentes',
            'produtos_analytics', 'alertas',
            'top_produtos', 'receita_categoria',
            'ticket_medio', 'total_unidades', 'total_ativos',
            'pedidos_acao'
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
            'descricao' => $produto->descricao,
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
            'descricao' => 'required|string',
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
            'imagens.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
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
            'descricao' => $descricao,
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
            'descricao' => 'required|string',
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
            'imagens.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
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
            'descricao' => $descricao,
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
            'imagem' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
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
    public function pedidos()
    {
        if (!Auth::check()) {
            return redirect()->route('site.login');
        }
        
        $pedidos = Pedido::with('user', 'itens.produto')->orderBy('created_at', 'desc')->paginate(10);
        
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
            'pagamentos'
        ])->findOrFail($id);

        $html = view('admin.includes.pedido-detalhes', compact('pedido'))->render();
        return response()->json(['html' => $html]);
    }

    // Atualizar status do pedido
    public function atualizarStatusPedido(Request $request, $id)
    {
        $pedido = Pedido::findOrFail($id);
        
        $request->validate([
            'status' => 'required|in:pendente,processando,enviado,entregue,cancelado'
        ]);

        $pedido->update(['status' => $request->status]);
        
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
                    'id'         => $v->id,
                    'valores'    => $v->valores,
                    'preco'      => $v->preco,
                    'custo_compra' => $v->custo_compra,
                    'estoque'    => $v->estoque,
                    'ativo'      => $v->ativo,
                    'label'      => $label,
                    'imagem_ids' => $v->imagens->pluck('id')->all(),
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

        $request->validate([
            'status' => 'required|in:pendente,processando,enviado,entregue,cancelado',
        ]);

        $pedido->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'status'  => $pedido->status,
        ]);
    }
}
