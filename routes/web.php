<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\PedidosController;
use App\Http\Controllers\FavoritosController;
use App\Http\Controllers\CarrinhoController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MercadoPagoCheckoutController;

Route::get('/', [SiteController::class, 'index'])->name('site.index');
Route::get('/produtos', [SiteController::class, 'produtos'])->name('site.produtos');
Route::get('/produto/{slug}', [SiteController::class, 'produto_detalhes'])->name('site.produto.detalhes');
Route::get('/contato', [SiteController::class, 'contato'])->name('site.contato');
Route::get('/busca-rapida', [SiteController::class, 'buscaRapida'])->name('site.busca_rapida');

// Rotas de autenticação
Route::get('/login', [SiteController::class, 'login_view'])->name('site.login');
Route::post('/login', [SiteController::class, 'login'])->name('site.login.post');
Route::get('/register', [SiteController::class, 'register_view'])->name('site.register');
Route::post('/register', [SiteController::class, 'register'])->name('site.register.post');
Route::post('/logout', [SiteController::class, 'logout'])->name('site.logout');

// Rota do perfil (apenas para usuários logados)
Route::get('/perfil', [SiteController::class, 'perfil'])->name('site.perfil');
Route::put('/perfil', [SiteController::class, 'perfil_update'])->name('site.perfil.update');

// Rota dos pedidos (apenas para usuários logados)
Route::get('/meus-pedidos', [PedidosController::class, 'meus_pedidos'])->name('site.meus-pedidos');

// Rotas dos favoritos (apenas para usuários logados)
Route::get('/favoritos', [FavoritosController::class, 'favoritos'])->name('site.favoritos');
Route::post('/favoritos/adicionar', [FavoritosController::class, 'adicionar'])->name('site.favoritos.adicionar');
Route::delete('/favoritos/remover', [FavoritosController::class, 'remover'])->name('site.favoritos.remover');
Route::delete('/favoritos/limpar', [FavoritosController::class, 'limpar_todos'])->name('site.favoritos.limpar');

// Rotas do carrinho (apenas para usuários logados)
Route::get('/carrinho', [CarrinhoController::class, 'carrinho'])->name('site.carrinho');
Route::post('/carrinho/adicionar', [CarrinhoController::class, 'adicionar'])->name('site.carrinho.adicionar');
Route::post('/carrinho/remover', [CarrinhoController::class, 'remover'])->name('site.carrinho.remover');
Route::post('/carrinho/atualizar', [CarrinhoController::class, 'atualizar_quantidade'])->name('site.carrinho.atualizar');
Route::get('/carrinho/contador', [CarrinhoController::class, 'contador'])->name('site.carrinho.contador');
Route::get('/carrinho/itens', [CarrinhoController::class, 'itens'])->name('site.carrinho.itens');
Route::post('/carrinho/verificar', [CarrinhoController::class, 'verificar_produto'])->name('site.carrinho.verificar');

// Rota do checkout
Route::get('/finalizar-compra', [SiteController::class, 'finalizar_compra'])->name('site.finalizar-compra');

// Rotas de pedidos
Route::post('/pedidos', [App\Http\Controllers\PedidoController::class, 'store'])->name('site.pedidos.store');
// Rota removida - usando view unificada em finalizar-compra
Route::get('/meus-pedidos', [App\Http\Controllers\PedidoController::class, 'index'])->name('site.pedidos.index');
Route::get('/pedidos/{id}', [App\Http\Controllers\PedidoController::class, 'show'])->name('site.pedidos.show');

// Rotas administrativas (apenas para admins)
Route::prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    
    // Produtos
    Route::get('/produtos', [AdminController::class, 'produtos'])->name('produtos');
    Route::get('/produtos/search', [AdminController::class, 'pesquisarProdutos'])->name('produtos.search');
    Route::post('/produtos/bulk', [AdminController::class, 'bulkActionProdutos'])->name('produtos.bulk');
    Route::post('/produtos/exportar', [AdminController::class, 'exportarProdutos'])->name('produtos.exportar');
    Route::post('/produtos/imagens/{id}/excluir', [AdminController::class, 'excluirImagem'])->name('produtos.imagens.excluir');
    Route::get('/produtos/imagens/{id}/download', [AdminController::class, 'downloadImagem'])->name('produtos.imagens.download');
    Route::post('/produtos/imagens/{id}/substituir', [AdminController::class, 'substituirImagem'])->name('produtos.imagens.substituir');
    Route::post('/produtos/{id}/imagens/reordenar', [AdminController::class, 'reordenarImagens'])->name('produtos.imagens.reordenar');
    Route::get('/produtos/{id}/opcoes', [AdminController::class, 'buscarOpcoesProduto'])->name('produtos.opcoes');
    Route::post('/produtos/{id}/opcao-grupos', [AdminController::class, 'salvarOpcaoGrupos'])->name('produtos.opcao-grupos');
    Route::post('/produtos/{id}/variantes/gerar', [AdminController::class, 'gerarVariantes'])->name('produtos.variantes.gerar');
    Route::put('/produtos/{id}/variantes', [AdminController::class, 'salvarVariantes'])->name('produtos.variantes.salvar');
    Route::get('/produtos/{id}', [AdminController::class, 'buscarProduto'])->name('produtos.buscar');
    Route::post('/produtos', [AdminController::class, 'criarProduto'])->name('produtos.criar');
    Route::post('/produtos/{id}/editar', [AdminController::class, 'editarProduto'])->name('produtos.editar');
    Route::post('/produtos/{id}/status', [AdminController::class, 'alterarStatusProduto'])->name('produtos.alterar-status');
    Route::post('/produtos/{id}/destaque', [AdminController::class, 'alterarDestaqueProduto'])->name('produtos.alterar-destaque');
    Route::post('/produtos/{id}/excluir', [AdminController::class, 'excluirProduto'])->name('produtos.excluir');
    
    // Pedidos
    Route::get('/pedidos', [AdminController::class, 'pedidos'])->name('pedidos');
    Route::get('/pedidos/{id}', [AdminController::class, 'detalhesPedido'])->name('pedidos.detalhes');
    Route::post('/pedidos/{id}/status', [AdminController::class, 'atualizarStatusPedido'])->name('pedidos.status');
    
    // Categorias
    Route::get('/categorias', [AdminController::class, 'categorias'])->name('categorias');
    Route::post('/categorias', [AdminController::class, 'criarCategoria'])->name('categorias.criar');
    Route::post('/categorias/{id}/editar', [AdminController::class, 'editarCategoria'])->name('categorias.editar');
    Route::post('/categorias/{id}/excluir', [AdminController::class, 'excluirCategoria'])->name('categorias.excluir');
});

// Rota para buscar CEP (pública)
Route::get('/cep/{cep}', [App\Http\Controllers\CepController::class, 'buscarCep'])->name('cep.buscar');

// Rotas de frete
Route::post('/frete/calcular', [App\Http\Controllers\FreteController::class, 'calcularFrete'])->name('frete.calcular');
Route::post('/frete/carrinho', [App\Http\Controllers\FreteController::class, 'calcularFreteCarrinho'])->name('frete.carrinho');

// Checkout Mercado Pago
Route::post('/checkout/mercado-pago/preparar', [MercadoPagoCheckoutController::class, 'prepare'])->name('site.checkout.mercadopago.prepare');
Route::post('/checkout/mercado-pago/pagar', [MercadoPagoCheckoutController::class, 'pay'])->name('site.checkout.mercadopago.pay');
Route::get('/checkout/mercado-pago/status/{pedido}', [MercadoPagoCheckoutController::class, 'status'])->name('site.checkout.mercadopago.status');
Route::post('/webhooks/mercado-pago', [MercadoPagoCheckoutController::class, 'webhook'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('site.checkout.mercadopago.webhook');
