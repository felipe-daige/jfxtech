<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produto;
use App\Models\Categoria;
use App\Models\Pedido;
use App\Models\ItemPedido;
use App\Models\ProdutoImagem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

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
            $query->where('nome', 'like', "%{$pesquisa}%");
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
        
        $produtos = $query->orderBy('created_at', 'desc')->paginate(10);
        $categorias = Categoria::all();
        
        return view('admin.produtos', compact('produtos', 'categorias'));
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
            $query->where('nome', 'like', "%{$pesquisa}%");
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
        
        $produtos = $query->orderBy('created_at', 'desc')->paginate(10);
        
        // Retornar HTML renderizado dos produtos
        $html = view('admin.includes.produtos-lista', compact('produtos'))->render();
        
        return response()->json([
            'html' => $html,
            'total' => $produtos->total(),
            'current_page' => $produtos->currentPage(),
            'last_page' => $produtos->lastPage()
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
            'estoque' => $produto->estoque,
            'categoria_id' => $produto->categoria_id,
            'ativo' => $produto->ativo,
            'imagens' => $produto->imagens->map(function($imagem) {
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
            'estoque' => $request->estoque,
            'categoria_id' => $request->categoria_id
        ]);

        // Upload de novas imagens
        if ($request->hasFile('imagens')) {
            foreach ($request->file('imagens') as $imagem) {
                $caminho = $imagem->store('produtos', 'public');
                ProdutoImagem::create([
                    'produto_id' => $produto->id,
                    'caminho' => $caminho
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
}
