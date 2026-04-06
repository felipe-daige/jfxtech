<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\PedidosController;
use App\Http\Controllers\FavoritosController;
use App\Http\Controllers\CarrinhoController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MercadoPagoCheckoutController;
use App\Http\Controllers\AffiliadoController;
use App\Http\Controllers\AdminAfiliadoController;

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
Route::post('/enderecos', [SiteController::class, 'endereco_store'])->name('site.enderecos.store');
Route::put('/enderecos/{endereco}', [SiteController::class, 'endereco_update'])->name('site.enderecos.update');
Route::delete('/enderecos/{endereco}', [SiteController::class, 'endereco_destroy'])->name('site.enderecos.destroy');

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
Route::post('/pedidos/{pedido}/criar-conta', [App\Http\Controllers\PedidoController::class, 'createAccount'])->name('site.pedidos.create-account');
Route::get('/pedidos/{pedido}', [App\Http\Controllers\PedidoController::class, 'show'])->name('site.pedidos.show');

// Rotas administrativas (apenas para admins)
Route::prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    
    // Produtos
    Route::get('/produtos', [AdminController::class, 'produtos'])->name('produtos');
    Route::get('/produtos/search', [AdminController::class, 'pesquisarProdutos'])->name('produtos.search');
    Route::post('/produtos/bulk', [AdminController::class, 'bulkActionProdutos'])->name('produtos.bulk');
    Route::post('/produtos/exportar', [AdminController::class, 'exportarProdutos'])->name('produtos.exportar');
    Route::get('/dashboard/exportar/csv', [AdminController::class, 'exportarAnalyticsCsv'])->name('dashboard.exportar.csv');
    Route::get('/dashboard/exportar/pdf', [AdminController::class, 'exportarAnalyticsPdf'])->name('dashboard.exportar.pdf');
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
    Route::post('/produtos/{id}/quick-edit', [AdminController::class, 'quickEditProduto'])->name('produtos.quick-edit');

    // Pedidos
    Route::get('/pedidos', [AdminController::class, 'pedidos'])->name('pedidos');
    Route::get('/pedidos/{id}', [AdminController::class, 'detalhesPedido'])->name('pedidos.detalhes');
    Route::post('/pedidos/{id}/status', [AdminController::class, 'atualizarStatusPedido'])->name('pedidos.status');
    Route::post('/pedidos/{id}/quick-status', [AdminController::class, 'quickStatusPedido'])->name('pedidos.quick-status');
    
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

// ─── Painel do Afiliado (requires auth) ───────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/afiliados', [AffiliadoController::class, 'painel'])->name('afiliados.painel');
    Route::get('/afiliados/solicitar', [AffiliadoController::class, 'solicitar'])->name('afiliados.solicitar');
    Route::post('/afiliados/solicitar', [AffiliadoController::class, 'registrar'])->name('afiliados.registrar');
    Route::get('/afiliados/indicacoes', [AffiliadoController::class, 'indicacoes'])->name('afiliados.indicacoes');
    Route::get('/afiliados/comissoes', [AffiliadoController::class, 'comissoes'])->name('afiliados.comissoes');
});

// ─── Admin: Afiliados ─────────────────────────────────────────────────────────
Route::prefix('admin/afiliados')->name('admin.afiliados.')->group(function () {
    Route::get('/',                [AdminAfiliadoController::class, 'index'])->name('index');
    Route::get('/stream',          [AdminAfiliadoController::class, 'stream'])->name('stream');
    Route::get('/comissoes',       [AdminAfiliadoController::class, 'comissoes'])->name('comissoes');
    Route::post('/comissoes/bulk', [AdminAfiliadoController::class, 'bulkComissoes'])->name('comissoes.bulk');
    Route::get('/configuracoes',   [AdminAfiliadoController::class, 'configuracoes'])->name('configuracoes');
    Route::post('/configuracoes',  [AdminAfiliadoController::class, 'salvarConfiguracoes'])->name('configuracoes.salvar');
    Route::get('/buscar-usuarios',  [AdminAfiliadoController::class, 'buscarUsuarios'])->name('buscarUsuarios');
    Route::post('/',                [AdminAfiliadoController::class, 'store'])->name('store');
    Route::get('/{id}',            [AdminAfiliadoController::class, 'show'])->name('show');
    Route::post('/{id}/aprovar',   [AdminAfiliadoController::class, 'aprovar'])->name('aprovar');
    Route::post('/{id}/suspender', [AdminAfiliadoController::class, 'suspender'])->name('suspender');
    Route::post('/{id}/comissao',  [AdminAfiliadoController::class, 'editarComissao'])->name('comissao');
});
