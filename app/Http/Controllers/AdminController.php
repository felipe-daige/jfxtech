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

class AdminController extends Controller
{
    // Dashboard administrativo
    public function dashboard()
    {
        if (!Auth::check()) {
            return redirect()->route('site.login');
        }
        
        $total_produtos = Produto::count();
        $total_pedidos = Pedido::count();
        $pedidos_pendentes = Pedido::where('status', 'pendente')->count();
        $pedidos_processando = Pedido::where('status', 'processando')->count();
        $pedidos_enviados = Pedido::where('status', 'enviado')->count();
        $pedidos_entregues = Pedido::where('status', 'entregue')->count();
        $pedidos_cancelados = Pedido::where('status', 'cancelado')->count();
        $receita_total = Pedido::where('status', 'entregue')->sum('valor_total');
        
        $pedidos_recentes = Pedido::with('user')->orderBy('created_at', 'desc')->limit(5)->get();
        
        return view('admin.dashboard', compact(
            'total_produtos', 
            'total_pedidos', 
            'pedidos_pendentes',
            'pedidos_processando',
            'pedidos_enviados',
            'pedidos_entregues',
            'pedidos_cancelados',
            'receita_total',
            'pedidos_recentes'
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

        return response()->json([
            'html'         => $html,
            'total'        => $produtos->total(),
            'current_page' => $produtos->currentPage(),
            'last_page'    => $produtos->lastPage(),
            'view_mode'    => $viewMode,
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
            'peso' => $produto->peso ? number_format($produto->peso, 3, ',', '.') : '',
            'desconto_percentual' => $produto->desconto_percentual,
            'em_promocao' => $produto->em_promocao,
            'destaque' => $produto->destaque,
            'tags' => $produto->tags ?? [],
            'estoque' => $produto->estoque,
            'categoria_id' => $produto->categoria_id,
            'ativo' => $produto->ativo,
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
        $preco = str_replace(['R$ ', '.'], '', $request->preco);
        $preco = str_replace(',', '.', $preco);
        
        // Validar se o preço convertido é um número válido
        if (!is_numeric($preco) || $preco < 0) {
            return redirect()->back()->withErrors(['preco' => 'Preço deve ser um valor válido maior ou igual a zero.'])->withInput();
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

        $produto = Produto::create([
            'nome' => $request->nome,
            'descricao' => $request->descricao,
            'preco' => $precoFinal, // Preço final (com desconto se aplicável)
            'peso' => $request->peso, // Peso em KG
            'preco_original' => $emPromocao ? $preco : null, // Preço original (sem desconto)
            'desconto_percentual' => $descontoPercentual,
            'em_promocao' => $emPromocao,
            'destaque' => $destaque,
            'tags' => $request->input('tags', []),
            'estoque' => $request->estoque,
            'categoria_id' => $request->categoria_id,
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
        $preco = str_replace(['R$ ', '.'], '', $request->preco);
        $preco = str_replace(',', '.', $preco);
        
        // Validar se o preço convertido é um número válido
        if (!is_numeric($preco) || $preco < 0) {
            return redirect()->back()->withErrors(['preco' => 'Preço deve ser um valor válido maior ou igual a zero.'])->withInput();
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

        $produto->update([
            'nome' => $request->nome,
            'descricao' => $request->descricao,
            'preco' => $precoFinal, // Preço final (com desconto se aplicável)
            'peso' => $request->peso, // Peso em KG
            'preco_original' => $emPromocao ? $preco : null, // Preço original (sem desconto)
            'desconto_percentual' => $descontoPercentual,
            'em_promocao' => $emPromocao,
            'destaque' => $destaque,
            'tags' => $request->input('tags', []),
            'estoque' => $request->estoque,
            'categoria_id' => $request->categoria_id
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

        $produto = Produto::with(['opcaoGrupos.valores', 'variantes'])->findOrFail($id);

        // Build a valor map for label resolution
        $valorMap = [];
        foreach ($produto->opcaoGrupos as $grupo) {
            foreach ($grupo->valores as $valor) {
                $valorMap[$valor->id] = ['grupo' => $grupo->nome, 'valor' => $valor->valor];
            }
        }

        $variantes = $produto->variantes->map(function ($v) use ($valorMap) {
            $label = collect($v->valores)->map(fn($vid) => $valorMap[$vid]['valor'] ?? '?')->join(' / ');
            return [
                'id'      => $v->id,
                'valores' => $v->valores,
                'preco'   => $v->preco,
                'estoque' => $v->estoque,
                'ativo'   => $v->ativo,
                'label'   => $label,
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
        ]);
    }

    public function salvarOpcaoGrupos(Request $request, $id)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $produto = Produto::findOrFail($id);

        $request->validate([
            'grupos'                    => 'required|array',
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
            // Delete groups not in this request (by name)
            $nomesNovos = collect($request->grupos)->pluck('nome')->all();
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
            'estoque_compartilhado'    => 'nullable|boolean',
            'variantes'                => 'nullable|array',
            'variantes.*.id'           => 'required|exists:produto_variantes,id',
            'variantes.*.preco'        => 'nullable|numeric|min:0',
            'variantes.*.estoque'      => 'nullable|integer|min:0',
            'variantes.*.ativo'        => 'nullable|boolean',
        ]);

        \DB::transaction(function () use ($request, $produto) {
            if ($request->has('estoque_compartilhado')) {
                $produto->update(['estoque_compartilhado' => $request->boolean('estoque_compartilhado')]);
            }

            foreach ($request->input('variantes', []) as $varData) {
                $variante = ProdutoVariante::where('id', $varData['id'])
                    ->where('produto_id', $produto->id)
                    ->firstOrFail();

                $update = [];
                if (array_key_exists('preco', $varData))   $update['preco']   = $varData['preco'];
                if (array_key_exists('estoque', $varData)) $update['estoque'] = $varData['estoque'];
                if (array_key_exists('ativo', $varData))   $update['ativo']   = $varData['ativo'];

                if (!empty($update)) $variante->update($update);
            }
        });

        return response()->json(['success' => true]);
    }
}
