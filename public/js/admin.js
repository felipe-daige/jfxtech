// Admin JavaScript - JFXTECH
// JavaScript consolidado para o painel administrativo

$(document).ready(function() {
    // Inicializar máscara de dinheiro para produtos
    $('#preco').maskMoney({
        prefix: 'R$ ',
        thousands: '.',
        decimal: ',',
        allowZero: true,
        allowNegative: false
    });
    
    
    // Event listeners para promoção
    $('#em_promocao').change(function() {
        toggleCamposPromocao();
    });
    
    $('#desconto_percentual').on('input', function() {
        calcularPromocao();
    });
    
    // Recalcular promoção quando o preço principal for alterado
    $('#preco').on('input', function() {
        if (document.getElementById('em_promocao').checked) {
            calcularPromocao();
        }
    });
    
    // Fechar modal de produtos com tecla ESC
    $(document).keydown(function(e) {
        if (e.keyCode === 27) { // ESC key
            fecharModalProduto();
        }
    });
    
    // Fechar modal de produtos ao clicar no overlay
    $('#modalProduto').click(function(e) {
        // Verificar se o clique foi no overlay (modal principal) ou em elementos filhos do overlay
        if (e.target === this || e.target.classList.contains('flex') && e.target.classList.contains('items-center')) {
            fecharModalProduto();
        }
    });
});

// ===== FUNÇÕES DE PRODUTOS =====

function abrirModalProduto() {
    document.getElementById('modalTitulo').textContent = 'Novo Produto';
    document.getElementById('formProduto').action = window.routes.adminProdutosCriar;
    document.getElementById('formProduto').method = 'POST';
    document.getElementById('formMethod').innerHTML = '';
    document.getElementById('produtoId').value = '';
    document.getElementById('formProduto').reset();
    
    // Limpar imagens existentes
    limparImagensExistentes();
    
    // Limpar campos de promoção
    document.getElementById('em_promocao').checked = false;
    document.getElementById('desconto_percentual').value = '';
    toggleCamposPromocao();
    
    // Reaplicar máscara após reset
    $('#preco').maskMoney({
        prefix: 'R$ ',
        thousands: '.',
        decimal: ',',
        allowZero: true,
        allowNegative: false
    });
    
    
    document.getElementById('modalProduto').classList.remove('hidden');
    
    // Adicionar evento de clique no overlay
    const modal = document.getElementById('modalProduto');
    modal.addEventListener('click', function(e) {
        // Verificar se o clique foi no overlay (modal principal) ou em elementos filhos do overlay
        if (e.target === modal || e.target.classList.contains('flex') && e.target.classList.contains('items-center')) {
            fecharModalProduto();
        }
    });
}

function editarProduto(id) {
    // Buscar dados do produto
    fetch(`${window.baseUrl}/admin/produtos/${id}`)
        .then(response => response.json())
        .then(data => {
             // Preencher formulário com os dados
             document.getElementById('produtoId').value = data.id;
             document.getElementById('nome').value = data.nome;
             document.getElementById('descricao').value = data.descricao;
             document.getElementById('preco').value = 'R$ ' + data.preco;
             document.getElementById('estoque').value = data.estoque;
             document.getElementById('categoria_id').value = data.categoria_id;
             
             // Preencher campos de promoção
             document.getElementById('em_promocao').checked = data.em_promocao;
             document.getElementById('desconto_percentual').value = data.desconto_percentual || '';
             
             // Preencher campo destaque
             document.getElementById('destaque').checked = data.destaque;
             
             // Mostrar/ocultar campos de promoção
             toggleCamposPromocao();
            
             // Configurar formulário para edição
             document.getElementById('modalTitulo').textContent = 'Editar Produto';
             document.getElementById('formProduto').action = window.routes.adminProdutosEditar.replace(':id', id);
             document.getElementById('formProduto').method = 'POST';
             document.getElementById('formMethod').innerHTML = '';
            
            // Reaplicar máscara
            $('#preco').maskMoney({
                prefix: 'R$ ',
                thousands: '.',
                decimal: ',',
                allowZero: true,
                allowNegative: false
            });
            
            // Mostrar imagens existentes com seleção de capa
            mostrarImagensExistentes(data.imagens);
            
            document.getElementById('modalProduto').classList.remove('hidden');
            
            // Adicionar evento de clique no overlay
            const modal = document.getElementById('modalProduto');
            modal.addEventListener('click', function(e) {
                // Verificar se o clique foi no overlay (modal principal) ou em elementos filhos do overlay
                if (e.target === modal || e.target.classList.contains('flex') && e.target.classList.contains('items-center')) {
                    fecharModalProduto();
                }
            });
        })
        .catch(error => {
            console.error('Erro ao carregar produto:', error);
            alert('Erro ao carregar dados do produto');
        });
}

function fecharModalProduto() {
    document.getElementById('modalProduto').classList.add('hidden');
}

function mostrarImagensExistentes(imagens) {
    const container = document.getElementById('imagensExistentes');
    if (!container) {
        // Criar container se não existir
        const div = document.createElement('div');
        div.id = 'imagensExistentes';
        div.className = 'mt-4';
        div.innerHTML = '<h4 class="text-sm font-medium text-gray-700 mb-2">Imagens Atuais:</h4>';
        // Procurar pela div com padding que contém os campos do formulário
        const formContainer = document.getElementById('formProduto').querySelector('.p-4, .lg\\:p-6');
        if (formContainer) {
            formContainer.appendChild(div);
        } else {
            // Fallback: adicionar ao final do formulário
            document.getElementById('formProduto').appendChild(div);
        }
    }
    
    let html = '<h4 class="text-sm font-medium text-gray-700 mb-2">Imagens Atuais:</h4>';
    if (imagens && imagens.length > 0) {
        html += '<div class="grid grid-cols-2 gap-2">';
        imagens.forEach((imagem, index) => {
            const isCapa = imagem.capa; // Usar informação do banco de dados
            html += `
                <div class="relative">
                    <img src="${imagem.url}" alt="Imagem do produto" class="w-full h-20 object-cover rounded-lg border ${isCapa ? 'ring-2 ring-red-500' : ''}">
                    <div class="absolute top-1 left-1">
                        <button type="button" onclick="definirCapa(${imagem.id})" class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-blue-600 ${isCapa ? 'bg-green-500 hover:bg-green-600' : ''}" title="${isCapa ? 'Imagem de capa' : 'Definir como capa'}">
                            ${isCapa ? '✓' : '★'}
                        </button>
                    </div>
                    <button type="button" onclick="removerImagemExistente(${imagem.id})" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600">
                        ×
                    </button>
                </div>
            `;
        });
        html += '</div>';
        html += '<input type="hidden" id="imagemCapaId" name="imagem_capa_id" value="">';
    } else {
        html += '<p class="text-gray-500 text-sm">Nenhuma imagem cadastrada</p>';
    }
    
    document.getElementById('imagensExistentes').innerHTML = html;
}

function limparImagensExistentes() {
    const container = document.getElementById('imagensExistentes');
    if (container) {
        container.remove();
    }
}

function definirCapa(imagemId) {
    // Remover destaque de todas as imagens
    const imagens = document.querySelectorAll('#imagensExistentes img');
    imagens.forEach(img => {
        img.classList.remove('ring-2', 'ring-red-500');
    });
    
    // Destacar a imagem selecionada
    const imagemSelecionada = document.querySelector(`button[onclick="definirCapa(${imagemId})"]`).closest('.relative').querySelector('img');
    imagemSelecionada.classList.add('ring-2', 'ring-red-500');
    
    // Atualizar botões de capa
    const botoesCapa = document.querySelectorAll('#imagensExistentes button[onclick^="definirCapa"]');
    botoesCapa.forEach(botao => {
        const isSelected = botao.getAttribute('onclick').includes(imagemId);
        if (isSelected) {
            botao.className = botao.className.replace('bg-blue-500 hover:bg-blue-600', 'bg-green-500 hover:bg-green-600');
            botao.innerHTML = '✓';
            botao.title = 'Imagem de capa';
        } else {
            botao.className = botao.className.replace('bg-green-500 hover:bg-green-600', 'bg-blue-500 hover:bg-blue-600');
            botao.innerHTML = '★';
            botao.title = 'Definir como capa';
        }
    });
    
    // Definir a imagem de capa no campo hidden
    document.getElementById('imagemCapaId').value = imagemId;
}

function removerImagemExistente(imagemId) {
    if (confirm('Tem certeza que deseja remover esta imagem?')) {
        // Aqui você implementaria a remoção da imagem
        // Por enquanto, apenas remove do DOM
        const container = document.getElementById('imagensExistentes');
        if (container) {
            const imgElement = container.querySelector(`button[onclick="removerImagemExistente(${imagemId})"]`).closest('.relative');
            imgElement.remove();
        }
    }
}

// ===== FUNÇÕES DE PROMOÇÃO =====

function toggleCamposPromocao() {
    const checkbox = document.getElementById('em_promocao');
    const campos = document.getElementById('camposPromocao');
    const resumo = document.getElementById('resumoPromocao');
    
    if (checkbox.checked) {
        campos.classList.remove('hidden');
        resumo.classList.remove('hidden');
        calcularPromocao();
    } else {
        campos.classList.add('hidden');
        resumo.classList.add('hidden');
        // Limpar campos
        document.getElementById('desconto_percentual').value = '';
    }
}

function calcularPromocao() {
    const precoAtual = parseFloat(document.getElementById('preco').value.replace(/[^\d,]/g, '').replace(',', '.')) || 0;
    const descontoPercentual = parseFloat(document.getElementById('desconto_percentual').value) || 0;
    
    if (precoAtual > 0 && descontoPercentual > 0) {
        const valorDesconto = precoAtual * (descontoPercentual / 100);
        const precoFinal = precoAtual - valorDesconto;
        
        // Atualizar resumo
        document.getElementById('precoOriginalDisplay').textContent = 'R$ ' + precoAtual.toFixed(2).replace('.', ',');
        document.getElementById('descontoDisplay').textContent = 'R$ ' + valorDesconto.toFixed(2).replace('.', ',');
        document.getElementById('precoFinalDisplay').textContent = 'R$ ' + precoFinal.toFixed(2).replace('.', ',');
    }
}

// ===== FUNÇÕES DE CATEGORIAS =====

function abrirModalCategoria() {
    document.getElementById('modalTitulo').textContent = 'Nova Categoria';
    document.getElementById('formCategoria').action = window.routes.adminCategoriasCriar;
    document.getElementById('formCategoria').method = 'POST';
    document.getElementById('formMethod').innerHTML = '';
    document.getElementById('formCategoria').reset();
    document.getElementById('modalCategoria').classList.remove('hidden');
}

function editarCategoria(id, nome, descricao) {
    document.getElementById('modalTitulo').textContent = 'Editar Categoria';
    document.getElementById('formCategoria').action = window.routes.adminCategoriasEditar.replace(':id', id);
    document.getElementById('formCategoria').method = 'POST';
    document.getElementById('formMethod').innerHTML = '';
    document.getElementById('nome').value = nome;
    document.getElementById('descricao').value = descricao;
    document.getElementById('modalCategoria').classList.remove('hidden');
}

function fecharModalCategoria() {
    document.getElementById('modalCategoria').classList.add('hidden');
}

// ===== FUNÇÕES DE PEDIDOS =====

function aplicarFiltros() {
    const status = document.getElementById('filtroStatus').value;
    const data = document.getElementById('filtroData').value;
    
    let url = new URL(window.location);
    if (status) url.searchParams.set('status', status);
    if (data) url.searchParams.set('data', data);
    
    window.location.href = url.toString();
}

function verDetalhes(pedidoId) {
    const conteudo = document.getElementById('conteudoDetalhes');
    const modal = document.getElementById('modalDetalhes');
    if (!conteudo || !modal || !window.routes || !window.routes.adminPedidosDetalhes) return;

    conteudo.innerHTML = `
        <div class="text-center py-8">
            <div class="text-sm font-mono font-bold uppercase tracking-widest text-gray-400 mb-4">CARREGANDO...</div>
            <p class="text-gray-500">Carregando detalhes do pedido...</p>
        </div>
    `;
    modal.classList.remove('hidden');

    fetch(window.routes.adminPedidosDetalhes.replace(':id', pedidoId))
        .then(r => r.json())
        .then(data => {
            if (data && data.html) {
                conteudo.innerHTML = data.html;
            } else {
                conteudo.innerHTML = '<p class="text-red-600">Não foi possível carregar os detalhes.</p>';
            }
        })
        .catch(() => {
            conteudo.innerHTML = '<p class="text-red-600">Erro ao carregar os detalhes.</p>';
        });
}

function alterarStatus(pedidoId, statusAtual) {
    document.getElementById('formStatus').action = window.routes.adminPedidosStatus.replace(':id', pedidoId);
    document.getElementById('novoStatus').value = statusAtual;
    document.getElementById('modalStatus').classList.remove('hidden');
}

function fecharModalDetalhes() {
    document.getElementById('modalDetalhes').classList.add('hidden');
}

function fecharModalStatus() {
    document.getElementById('modalStatus').classList.add('hidden');
}

// ===== EVENT LISTENERS GLOBAIS =====

// Fechar modal de categorias ao clicar no overlay
document.addEventListener('DOMContentLoaded', function() {
    const modalCategoria = document.getElementById('modalCategoria');
    if (modalCategoria) {
        modalCategoria.addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalCategoria();
            }
        });
    }
    
    // Fechar modal de categorias com ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modalCategoria && !modalCategoria.classList.contains('hidden')) {
            fecharModalCategoria();
        }
    });
    
    // Fechar modais de pedidos ao clicar no overlay
    const modalDetalhes = document.getElementById('modalDetalhes');
    const modalStatus = document.getElementById('modalStatus');
    
    if (modalDetalhes) {
        modalDetalhes.addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalDetalhes();
            }
        });
    }
    
    if (modalStatus) {
        modalStatus.addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalStatus();
            }
        });
    }
    
    // Fechar modais de pedidos com ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (modalDetalhes && !modalDetalhes.classList.contains('hidden')) {
                fecharModalDetalhes();
            }
            if (modalStatus && !modalStatus.classList.contains('hidden')) {
                fecharModalStatus();
            }
        }
    });
});

// Variáveis globais para armazenar dados dos modais
let produtoIdExclusao = null;
let produtoIdDestaque = null;
let produtoIdStatus = null;
let valorDestaque = null;
let valorStatus = null;
let acaoDestaque = null;
let acaoStatus = null;

// Função para confirmar exclusão de produto
function confirmarExclusao(produtoId) {
    produtoIdExclusao = produtoId;
    const modal = document.getElementById('modalConfirmacaoExclusao');
    modal.classList.remove('hidden');
}

// Função para confirmar alteração de destaque
function confirmarAlteracaoDestaque(produtoId, valor, acao) {
    produtoIdDestaque = produtoId;
    valorDestaque = valor;
    acaoDestaque = acao;
    
    const modal = document.getElementById('modalConfirmacaoDestaque');
    const titulo = document.getElementById('tituloDestaque');
    const mensagem = document.getElementById('mensagemDestaque');
    
    titulo.textContent = `Confirmar ${acao.charAt(0).toUpperCase() + acao.slice(1)}`;
    mensagem.textContent = `Tem certeza que deseja ${acao} este produto?`;
    
    modal.classList.remove('hidden');
}

// Função para confirmar alteração de status
function confirmarAlteracaoStatus(produtoId, valor, acao) {
    produtoIdStatus = produtoId;
    valorStatus = valor;
    acaoStatus = acao;
    
    const modal = document.getElementById('modalConfirmacaoStatus');
    const titulo = document.getElementById('tituloStatus');
    const mensagem = document.getElementById('mensagemStatus');
    
    titulo.textContent = `Confirmar ${acao.charAt(0).toUpperCase() + acao.slice(1)}`;
    mensagem.textContent = `Tem certeza que deseja ${acao} este produto?`;
    
    modal.classList.remove('hidden');
}

// Função para fechar todos os modais de confirmação
function fecharModalConfirmacao() {
    document.getElementById('modalConfirmacaoExclusao').classList.add('hidden');
    document.getElementById('modalConfirmacaoDestaque').classList.add('hidden');
    document.getElementById('modalConfirmacaoStatus').classList.add('hidden');
    
    // Limpar variáveis
    produtoIdExclusao = null;
    produtoIdDestaque = null;
    produtoIdStatus = null;
    valorDestaque = null;
    valorStatus = null;
    acaoDestaque = null;
    acaoStatus = null;
}

// Event listeners para os botões de confirmação
document.addEventListener('DOMContentLoaded', function() {
    // Confirmação de exclusão
    const btnConfirmarExclusao = document.getElementById('confirmarExclusao');
    if (btnConfirmarExclusao) {
    btnConfirmarExclusao.addEventListener('click', function() {
        if (produtoIdExclusao) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/admin/produtos/${produtoIdExclusao}/excluir`;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            csrfToken.value = csrfMeta ? csrfMeta.getAttribute('content') : '';
            
            form.appendChild(csrfToken);
            document.body.appendChild(form);
            form.submit();
        }
    });
    }
    
    // Confirmação de destaque
    const btnConfirmarDestaque = document.getElementById('confirmarDestaque');
    if (btnConfirmarDestaque) {
    btnConfirmarDestaque.addEventListener('click', function() {
        if (produtoIdDestaque && valorDestaque !== null) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/admin/produtos/${produtoIdDestaque}/destaque`;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            csrfToken.value = csrfMeta ? csrfMeta.getAttribute('content') : '';
            
            const destaqueInput = document.createElement('input');
            destaqueInput.type = 'hidden';
            destaqueInput.name = 'destaque';
            destaqueInput.value = valorDestaque;
            
            form.appendChild(csrfToken);
            form.appendChild(destaqueInput);
            document.body.appendChild(form);
            form.submit();
        }
    });
    }
    
    // Confirmação de status
    const btnConfirmarStatus = document.getElementById('confirmarStatus');
    if (btnConfirmarStatus) {
    btnConfirmarStatus.addEventListener('click', function() {
        if (produtoIdStatus && valorStatus !== null) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/admin/produtos/${produtoIdStatus}/status`;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            csrfToken.value = csrfMeta ? csrfMeta.getAttribute('content') : '';
            
            const ativoInput = document.createElement('input');
            ativoInput.type = 'hidden';
            ativoInput.name = 'ativo';
            ativoInput.value = valorStatus;
            
            form.appendChild(csrfToken);
            form.appendChild(ativoInput);
            document.body.appendChild(form);
            form.submit();
        }
    });
    }
    
    // Fechar modais ao clicar no backdrop
    const modalConfirmacaoExclusao = document.getElementById('modalConfirmacaoExclusao');
    if (modalConfirmacaoExclusao) {
        modalConfirmacaoExclusao.addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalConfirmacao();
            }
        });
    }
    
    const modalConfirmacaoDestaque = document.getElementById('modalConfirmacaoDestaque');
    if (modalConfirmacaoDestaque) {
        modalConfirmacaoDestaque.addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalConfirmacao();
            }
        });
    }
    
    const modalConfirmacaoStatus = document.getElementById('modalConfirmacaoStatus');
    if (modalConfirmacaoStatus) {
        modalConfirmacaoStatus.addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalConfirmacao();
            }
        });
    }
});
