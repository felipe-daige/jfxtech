// Admin JavaScript - JFXTECH
// JavaScript consolidado para o painel administrativo

let _pendingRemoverImagemId = null;

$(document).ready(function () {
    // Inicializar máscara de dinheiro para produtos
    $('#preco').maskMoney({
        prefix: 'R$ ',
        thousands: '.',
        decimal: ',',
        allowZero: true,
        allowNegative: false
    });


    // Event listeners para promoção
    $('#em_promocao').change(function () {
        toggleCamposPromocao();
    });

    $('#desconto_percentual').on('input', function () {
        calcularPromocao();
    });

    // Recalcular promoção quando o preço principal for alterado
    $('#preco').on('input', function () {
        if (document.getElementById('em_promocao').checked) {
            calcularPromocao();
        }
    });

    // Fechar modal de produtos com tecla ESC
    $(document).keydown(function (e) {
        if (e.keyCode === 27) { // ESC key
            fecharModalProduto();
        }
    });

    // Fechar modal de produtos ao clicar no overlay
    $('#modalProduto').click(function (e) {
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

    // Limpar preview de novas imagens
    const prevAbrir = document.getElementById('novasImagensPreview');
    if (prevAbrir) { prevAbrir.innerHTML = ''; prevAbrir.classList.add('hidden'); }

    // Limpar tags
    document.querySelectorAll('.tag-checkbox').forEach(cb => cb.checked = false);
    if (typeof atualizarLimiteTags === 'function') atualizarLimiteTags();

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
    modal.addEventListener('click', function (e) {
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

            // Preencher tags (limitado a 2 via frontend)
            const existingTags = Array.isArray(data.tags) ? data.tags : [];
            document.querySelectorAll('.tag-checkbox').forEach(cb => {
                cb.checked = existingTags.includes(cb.value);
                cb.disabled = false;
            });
            if (typeof atualizarLimiteTags === 'function') atualizarLimiteTags();

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
            modal.addEventListener('click', function (e) {
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
    const prev = document.getElementById('novasImagensPreview');
    if (prev) { prev.innerHTML = ''; prev.classList.add('hidden'); }
    limparImagensExistentes();
    document.querySelectorAll('.tag-checkbox').forEach(cb => {
        cb.checked = false;
        cb.disabled = false;
    });
    const warning = document.getElementById('tags_warning');
    if (warning) warning.classList.add('hidden');
}

function mostrarImagensExistentes(imagens) {
    let container = document.getElementById('imagensExistentes');
    if (!container) {
        container = document.createElement('div');
        container.id = 'imagensExistentes';
        container.className = 'mt-5';
        const formContainer = document.getElementById('formFields');
        if (formContainer) {
            formContainer.appendChild(container);
        } else {
            document.getElementById('formProduto').appendChild(container);
        }
    }

    let html = '<p class="font-mono text-[10px] uppercase tracking-widest text-gray-400 mb-2">Imagens Atuais</p>';
    if (imagens && imagens.length > 0) {
        html += '<div id="imagensGrid" class="grid grid-cols-3 gap-2">';
        imagens.forEach((imagem, idx) => {
            const isCapa = imagem.capa;
            html += `
                <div id="img-container-${imagem.id}" class="border ${isCapa ? 'border-black' : 'border-gray-200'} flex flex-col select-none">
                    <div class="img-wrapper relative overflow-hidden">
                        <img src="${imagem.url}" alt="Imagem do produto"
                             class="w-full h-20 object-cover block cursor-zoom-in"
                             onclick="verImagemExpandida('${imagem.url}')">
                        ${isCapa ? '<span class="capa-label absolute top-0 left-0 bg-black text-white text-[8px] font-mono uppercase tracking-widest px-1.5 py-0.5 leading-none">CAPA</span>' : ''}
                        <span class="posicao-label absolute top-0 right-0 bg-black/50 text-white text-[8px] font-mono px-1.5 py-0.5 leading-none">${idx + 1}</span>
                    </div>
                    <div class="flex border-t border-gray-200 divide-x divide-gray-200">
                        <button type="button" onclick="definirCapa(${imagem.id})"
                                class="btn-capa flex-1 py-1.5 text-xs transition-colors ${isCapa ? 'bg-black text-white' : 'text-gray-400 hover:bg-gray-100 hover:text-black'}"
                                data-tooltip="${isCapa ? 'Capa atual' : 'Usar como capa'}">★</button>
                        <button type="button" onclick="downloadImagem(${imagem.id})"
                                class="flex-1 py-1.5 text-xs text-gray-400 hover:bg-gray-100 hover:text-black transition-colors"
                                data-tooltip="Baixar imagem">↓</button>
                        <button type="button" onclick="substituirImagem(${imagem.id})"
                                class="btn-substituir flex-1 py-1.5 text-xs text-gray-400 hover:bg-gray-100 hover:text-black transition-colors"
                                data-tooltip="Alterar imagem">✎</button>
                        <button type="button" onclick="removerImagemExistente(${imagem.id})"
                                class="flex-1 py-1.5 text-xs text-red-400 hover:bg-red-500 hover:text-white transition-colors"
                                data-tooltip="Excluir imagem">✕</button>
                    </div>
                    <div id="substituir-form-${imagem.id}" class="hidden border-t border-gray-200 p-2 bg-gray-50">
                        <input type="file" id="substituir-file-${imagem.id}"
                               accept="image/jpeg,image/png,image/jpg,image/gif"
                               onchange="mostrarPreviewSubstituir(${imagem.id}, this)"
                               class="w-full text-[10px] font-mono mb-1.5
                                      file:mr-2 file:py-0.5 file:px-2 file:border-0
                                      file:text-[10px] file:font-mono file:font-bold
                                      file:uppercase file:tracking-wide
                                      file:bg-black file:text-white file:cursor-pointer">
                        <div class="relative hidden mt-1.5" id="preview-wrapper-${imagem.id}">
                            <img id="preview-substituir-${imagem.id}" src=""
                                 class="w-full h-16 object-cover border border-[var(--color-lab-border)]">
                            <button type="button" onclick="cancelarSubstituir(${imagem.id})"
                                    class="absolute top-0.5 right-0.5 bg-black/60 text-white text-[10px] leading-none w-4 h-4 flex items-center justify-center hover:bg-black transition-colors"
                                    title="Cancelar seleção">&#x2715;</button>
                        </div>
                        <button type="button" onclick="confirmarSubstituir(${imagem.id})"
                                class="w-full bg-black text-white text-[10px] font-mono uppercase tracking-widest py-1.5 hover:bg-gray-800 transition-colors mt-1.5">
                            Confirmar
                        </button>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        html += '<input type="hidden" id="imagemCapaId" name="imagem_capa_id" value="">';
    } else {
        html += '<p class="text-gray-400 text-xs font-mono">Nenhuma imagem cadastrada</p>';
    }

    container.innerHTML = html;

    // Inicializar drag-and-drop
    const grid = document.getElementById('imagensGrid');
    if (grid && typeof Sortable !== 'undefined') {
        const produtoId = document.getElementById('produtoId').value;
        Sortable.create(grid, {
            animation: 150,
            handle: '.img-wrapper',
            ghostClass: 'opacity-40',
            onEnd: function () {
                const ordem = [...grid.children].map((el, idx) => ({
                    id: parseInt(el.id.replace('img-container-', '')),
                    posicao: idx
                }));

                // Atualizar badges de posição visualmente
                [...grid.children].forEach((el, idx) => {
                    const badge = el.querySelector('.posicao-label');
                    if (badge) badge.textContent = idx + 1;
                });

                fetch(`/admin/produtos/${produtoId}/imagens/reordenar`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ ordem })
                })
                .then(r => {
                    if (r.status === 419) {
                        alert('Sessão expirada. A página será recarregada.');
                        window.location.reload();
                    }
                })
                .catch(() => {});
            }
        });
    }
}

function limparImagensExistentes() {
    const container = document.getElementById('imagensExistentes');
    if (container) {
        container.remove();
    }
}

function verImagemExpandida(url) {
    document.getElementById('lightboxImg').src = url;
    document.getElementById('lightboxImagem').classList.remove('hidden');
}

function fecharLightbox() {
    document.getElementById('lightboxImagem').classList.add('hidden');
    document.getElementById('lightboxImg').src = '';
}

function definirCapa(imagemId) {
    // Reset todos os cards
    document.querySelectorAll('#imagensExistentes [id^="img-container-"]').forEach(card => {
        card.classList.remove('border-black');
        card.classList.add('border-gray-200');

        const label = card.querySelector('.capa-label');
        if (label) label.remove();

        const btn = card.querySelector('.btn-capa');
        if (btn) {
            btn.classList.remove('bg-black', 'text-white');
            btn.classList.add('text-gray-400', 'hover:bg-gray-100', 'hover:text-black');
            btn.dataset.tooltip = 'Usar como capa';
        }
    });

    // Marcar card selecionado
    const selected = document.getElementById(`img-container-${imagemId}`);
    if (selected) {
        selected.classList.remove('border-gray-200');
        selected.classList.add('border-black');

        const imgWrapper = selected.querySelector('.img-wrapper');
        if (imgWrapper) {
            const label = document.createElement('span');
            label.className = 'capa-label absolute top-0 left-0 bg-black text-white text-[8px] font-mono uppercase tracking-widest px-1.5 py-0.5 leading-none';
            label.textContent = 'CAPA';
            imgWrapper.appendChild(label);
        }

        const btn = selected.querySelector('.btn-capa');
        if (btn) {
            btn.classList.remove('text-gray-400', 'hover:bg-gray-100', 'hover:text-black');
            btn.classList.add('bg-black', 'text-white');
            btn.dataset.tooltip = 'Capa atual';
        }
    }

    document.getElementById('imagemCapaId').value = imagemId;
}

function removerImagemExistente(imagemId) {
    _pendingRemoverImagemId = imagemId;
    document.getElementById('modalConfirmacaoRemoverImagem').classList.remove('hidden');
}

function fecharModalRemoverImagem() {
    _pendingRemoverImagemId = null;
    document.getElementById('modalConfirmacaoRemoverImagem').classList.add('hidden');
}

function _executarRemocaoImagem(imagemId) {
    fetch(`${window.baseUrl}/admin/produtos/imagens/${imagemId}/excluir`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
        .then(r => {
            if (r.status === 419) {
                alert('Sessão expirada. A página será recarregada.');
                window.location.reload();
                return null;
            }
            return r.json();
        })
        .then(data => {
            if (!data) return;
            if (data.success) {
                const el = document.getElementById(`img-container-${imagemId}`);
                if (el) el.remove();
            } else {
                alert('Erro ao remover imagem.' + (data.message || data.error ? '\n' + (data.message || data.error) : ''));
            }
        })
        .catch(() => alert('Erro ao remover imagem.'));
}

function downloadImagem(imagemId) {
    window.location.href = `${window.baseUrl}/admin/produtos/imagens/${imagemId}/download`;
}

function substituirImagem(imagemId) {
    const form = document.getElementById(`substituir-form-${imagemId}`);
    if (form) {
        form.classList.toggle('hidden');
    }
}

function confirmarSubstituir(imagemId) {
    const fileInput = document.getElementById(`substituir-file-${imagemId}`);
    if (!fileInput || !fileInput.files[0]) {
        alert('Selecione um arquivo de imagem.');
        return;
    }

    const formData = new FormData();
    formData.append('imagem', fileInput.files[0]);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

    fetch(`${window.baseUrl}/admin/produtos/imagens/${imagemId}/substituir`, {
        method: 'POST',
        body: formData
    })
        .then(r => {
            if (r.status === 419) {
                alert('Sessão expirada. A página será recarregada.');
                window.location.reload();
                return null;
            }
            return r.json();
        })
        .then(data => {
            if (!data) return;
            if (data.success) {
                const container = document.getElementById(`img-container-${imagemId}`);
                if (container) {
                    const img = container.querySelector('img');
                    if (img) img.src = data.url + '?t=' + Date.now();
                }
                const form = document.getElementById(`substituir-form-${imagemId}`);
                if (form) form.classList.add('hidden');
            } else {
                alert('Erro ao substituir imagem.');
            }
        })
        .catch(() => alert('Erro ao substituir imagem.'));
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
document.addEventListener('DOMContentLoaded', function () {
    const modalCategoria = document.getElementById('modalCategoria');
    if (modalCategoria) {
        modalCategoria.addEventListener('click', function (e) {
            if (e.target === this) {
                fecharModalCategoria();
            }
        });
    }

    // Fechar modal de categorias com ESC
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modalCategoria && !modalCategoria.classList.contains('hidden')) {
            fecharModalCategoria();
        }
    });

    // Fechar modais de pedidos ao clicar no overlay
    const modalDetalhes = document.getElementById('modalDetalhes');
    const modalStatus = document.getElementById('modalStatus');

    if (modalDetalhes) {
        modalDetalhes.addEventListener('click', function (e) {
            if (e.target === this) {
                fecharModalDetalhes();
            }
        });
    }

    if (modalStatus) {
        modalStatus.addEventListener('click', function (e) {
            if (e.target === this) {
                fecharModalStatus();
            }
        });
    }

    // Fechar modais de pedidos com ESC
    document.addEventListener('keydown', function (e) {
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

// Limitar Tags a 2 Opções
function atualizarLimiteTags() {
    const checkboxes = document.querySelectorAll('.tag-checkbox');
    const checkedCount = document.querySelectorAll('.tag-checkbox:checked').length;
    const warning = document.getElementById('tags_warning');

    checkboxes.forEach(cb => {
        if (cb.checked) {
            cb.disabled = false;
        } else {
            cb.disabled = checkedCount >= 2;
        }
    });

    if (warning) {
        if (checkedCount >= 2) {
            warning.classList.remove('hidden');
        } else {
            warning.classList.add('hidden');
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // Event listener para as tags no modal
    document.querySelectorAll('.tag-checkbox').forEach(cb => {
        cb.addEventListener('change', atualizarLimiteTags);
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
    const modalRemover = document.getElementById('modalConfirmacaoRemoverImagem');
    if (modalRemover) modalRemover.classList.add('hidden');

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
document.addEventListener('DOMContentLoaded', function () {
    // Confirmação de exclusão
    const btnConfirmarExclusao = document.getElementById('confirmarExclusao');
    if (btnConfirmarExclusao) {
        btnConfirmarExclusao.addEventListener('click', function () {
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
        btnConfirmarDestaque.addEventListener('click', function () {
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
        btnConfirmarStatus.addEventListener('click', function () {
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
        modalConfirmacaoExclusao.addEventListener('click', function (e) {
            if (e.target === this) {
                fecharModalConfirmacao();
            }
        });
    }

    const modalConfirmacaoDestaque = document.getElementById('modalConfirmacaoDestaque');
    if (modalConfirmacaoDestaque) {
        modalConfirmacaoDestaque.addEventListener('click', function (e) {
            if (e.target === this) {
                fecharModalConfirmacao();
            }
        });
    }

    const modalConfirmacaoStatus = document.getElementById('modalConfirmacaoStatus');
    if (modalConfirmacaoStatus) {
        modalConfirmacaoStatus.addEventListener('click', function (e) {
            if (e.target === this) {
                fecharModalConfirmacao();
            }
        });
    }

    const modalConfirmacaoRemoverImagem = document.getElementById('modalConfirmacaoRemoverImagem');
    if (modalConfirmacaoRemoverImagem) {
        modalConfirmacaoRemoverImagem.addEventListener('click', function (e) {
            if (e.target === this) {
                fecharModalRemoverImagem();
            }
        });
    }

    const btnConfirmarRemoverImagem = document.getElementById('confirmarRemoverImagem');
    if (btnConfirmarRemoverImagem) {
        btnConfirmarRemoverImagem.addEventListener('click', function () {
            const imagemId = _pendingRemoverImagemId;
            fecharModalRemoverImagem();
            if (imagemId) _executarRemocaoImagem(imagemId);
        });
    }

    const lightbox = document.getElementById('lightboxImagem');
    if (lightbox) {
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !lightbox.classList.contains('hidden')) {
                fecharLightbox();
            }
        });
    }

    // Preview de novas imagens
    const inputImagens = document.getElementById('imagens');
    const previewContainer = document.getElementById('novasImagensPreview');
    if (inputImagens && previewContainer) {
        inputImagens.addEventListener('change', function () {
            previewContainer.innerHTML = '';
            if (!this.files.length) {
                previewContainer.classList.add('hidden');
                return;
            }
            previewContainer.classList.remove('hidden');
            Array.from(this.files).forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = e => {
                    const card = document.createElement('div');
                    card.className = 'relative border border-[var(--color-lab-border)] overflow-hidden';
                    card.innerHTML = `
                        <img src="${e.target.result}" class="w-full h-20 object-cover block">
                        <p class="font-mono text-[9px] text-[var(--color-lab-muted)] px-1 py-0.5 truncate">${file.name}</p>
                        <button type="button" onclick="removerNovaImagem(${index})"
                                class="absolute top-0.5 right-0.5 bg-black/60 text-white text-[10px] leading-none w-4 h-4 flex items-center justify-center hover:bg-black transition-colors"
                                title="Remover">&#x2715;</button>
                    `;
                    previewContainer.appendChild(card);
                };
                reader.readAsDataURL(file);
            });
        });
    }
});

function removerNovaImagem(index) {
    const input = document.getElementById('imagens');
    const dt = new DataTransfer();
    Array.from(input.files).forEach((file, i) => {
        if (i !== index) dt.items.add(file);
    });
    input.files = dt.files;
    input.dispatchEvent(new Event('change'));
}

function cancelarSubstituir(imagemId) {
    const fileInput = document.getElementById(`substituir-file-${imagemId}`);
    if (fileInput) fileInput.value = '';
    const wrapper = document.getElementById(`preview-wrapper-${imagemId}`);
    if (wrapper) {
        wrapper.classList.add('hidden');
        const preview = wrapper.querySelector('img');
        if (preview) preview.src = '';
    }
}

function mostrarPreviewSubstituir(imagemId, input) {
    const wrapper = document.getElementById(`preview-wrapper-${imagemId}`);
    const preview = document.getElementById(`preview-substituir-${imagemId}`);
    if (!wrapper || !preview || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        preview.src = e.target.result;
        wrapper.classList.remove('hidden');
    };
    reader.readAsDataURL(input.files[0]);
}

// Tooltip singleton — bypassa overflow de qualquer ancestral
(function () {
    var tip = document.createElement('div');
    tip.id = 'admin-tooltip';
    document.body.appendChild(tip);

    document.addEventListener('mouseover', function (e) {
        var el = e.target.closest('[data-tooltip]');
        if (!el) return;
        var r = el.getBoundingClientRect();
        tip.textContent = el.dataset.tooltip;
        tip.style.left = (r.left + r.width / 2) + 'px';
        tip.style.top = (r.top - 3) + 'px';
        tip.style.opacity = '1';
    });

    document.addEventListener('mouseout', function (e) {
        var el = e.target.closest('[data-tooltip]');
        if (!el) return;
        if (!el.contains(e.relatedTarget)) tip.style.opacity = '0';
    });
})();
