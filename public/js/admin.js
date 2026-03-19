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


    // Clear current product id for variant tab (new product has no id yet)
    window.currentProdutoId = null;

    // Reset tab to first tab when opening modal
    document.querySelectorAll('.produto-tab-btn').forEach(function(b, i) {
        if (i === 0) { b.classList.add('border-black', 'text-black'); b.classList.remove('border-transparent', 'text-gray-400'); }
        else { b.classList.remove('border-black', 'text-black'); b.classList.add('border-transparent', 'text-gray-400'); }
    });
    document.querySelectorAll('.produto-tab-panel').forEach(function(p, i) {
        if (i === 0) p.classList.remove('hidden'); else p.classList.add('hidden');
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

            // Set current product id for variant tab
            window.currentProdutoId = id;

            // Reset tab to first tab when opening modal
            document.querySelectorAll('.produto-tab-btn').forEach(function(b, i) {
                if (i === 0) { b.classList.add('border-black', 'text-black'); b.classList.remove('border-transparent', 'text-gray-400'); }
                else { b.classList.remove('border-black', 'text-black'); b.classList.add('border-transparent', 'text-gray-400'); }
            });
            document.querySelectorAll('.produto-tab-panel').forEach(function(p, i) {
                if (i === 0) p.classList.remove('hidden'); else p.classList.add('hidden');
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
        const tabImagens = document.getElementById('tab-imagens');
        if (tabImagens) {
            tabImagens.querySelector('.px-6').appendChild(container);
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

// ===== HELPER FUNCTIONS =====

function getCsrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

function escapeHtml(str) {
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(str));
    return d.innerHTML;
}

// ===== TAB SYSTEM FOR PRODUCT MODAL =====

function initProdutoModalTabs() {
    var tabBtns = document.querySelectorAll('.produto-tab-btn');
    var tabPanels = document.querySelectorAll('.produto-tab-panel');

    tabBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetTab = this.getAttribute('data-tab');

            tabBtns.forEach(function(b) {
                b.classList.remove('border-black', 'text-black');
                b.classList.add('border-transparent', 'text-gray-400');
            });
            this.classList.remove('border-transparent', 'text-gray-400');
            this.classList.add('border-black', 'text-black');

            tabPanels.forEach(function(panel) {
                panel.classList.add('hidden');
            });
            var panel = document.getElementById('tab-' + targetTab);
            if (panel) panel.classList.remove('hidden');

            if (targetTab === 'variantes') {
                loadVariantes(window.currentProdutoId);
            }
        });
    });
}

// ===== VARIANT MANAGEMENT FUNCTIONS =====

function loadVariantes(produtoId) {
    if (!produtoId) return;
    var loading = document.getElementById('variantes-loading');
    var content = document.getElementById('variantes-content');
    if (loading) loading.classList.remove('hidden');
    if (content) content.classList.add('hidden');

    fetch('/admin/produtos/' + produtoId + '/opcoes', {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        renderGrupos(data.grupos || []);
        renderVariantesTabela(data.variantes || []);
        var cb = document.getElementById('estoque-compartilhado');
        if (cb) cb.checked = data.estoque_compartilhado;
        if (loading) loading.classList.add('hidden');
        if (content) content.classList.remove('hidden');
    });
}

function renderGrupos(grupos) {
    var container = document.getElementById('grupos-container');
    if (!container) return;
    container.innerHTML = '';
    grupos.forEach(function(grupo, idx) {
        container.insertAdjacentHTML('beforeend', buildGrupoHTML(grupo, idx));
    });
}

function buildGrupoHTML(grupo, idx) {
    var valoresHTML = (grupo.valores || []).map(function(v) {
        return '<span class="inline-flex items-center gap-1 bg-gray-100 px-2 py-1 text-xs font-mono">' +
               escapeHtml(v.valor) +
               '<button type="button" class="remove-valor-btn text-gray-400 hover:text-black" data-valor-id="' + v.id + '">×</button>' +
               '</span>';
    }).join('');

    return '<div class="grupo-item border border-gray-200 p-4" data-grupo-id="' + (grupo.id || '') + '" data-grupo-idx="' + idx + '">' +
        '<div class="flex items-center gap-2 mb-3">' +
        '<input type="text" class="grupo-nome-input flex-1 border border-gray-300 px-3 py-2 text-sm font-mono uppercase" value="' + escapeHtml(grupo.nome || '') + '" placeholder="EX: COR">' +
        '<button type="button" class="remove-grupo-btn text-gray-400 hover:text-black font-mono text-sm px-2">REMOVER</button>' +
        '</div>' +
        '<div class="flex flex-wrap gap-2 mb-2 valores-container">' + valoresHTML + '</div>' +
        '<div class="flex gap-2">' +
        '<input type="text" class="novo-valor-input flex-1 border border-gray-300 px-3 py-2 text-sm font-mono" placeholder="Novo valor...">' +
        '<button type="button" class="add-valor-btn bg-gray-100 hover:bg-gray-200 px-3 py-2 text-sm font-mono uppercase transition-colors">+ ADD</button>' +
        '</div>' +
        '</div>';
}

function renderVariantesTabela(variantes) {
    var container = document.getElementById('variantes-tabela');
    var wrapper = document.getElementById('variantes-tabela-container');
    if (!container) return;
    if (variantes.length === 0) {
        if (wrapper) wrapper.classList.add('hidden');
        return;
    }
    if (wrapper) wrapper.classList.remove('hidden');
    container.innerHTML = variantes.map(function(v) {
        return '<div class="variante-row flex items-center gap-3 border border-gray-100 p-3" data-variante-id="' + v.id + '">' +
            '<span class="flex-1 text-sm font-mono">' + escapeHtml(v.label) + '</span>' +
            '<input type="number" class="variante-preco w-28 border border-gray-300 px-2 py-1 text-sm font-mono" placeholder="Preço" value="' + (v.preco || '') + '" step="0.01" min="0">' +
            '<input type="number" class="variante-estoque w-20 border border-gray-300 px-2 py-1 text-sm font-mono estoque-field" placeholder="Estq" value="' + (v.estoque !== null ? v.estoque : '') + '" min="0">' +
            '<label class="flex items-center gap-1 text-xs font-mono"><input type="checkbox" class="variante-ativo" ' + (v.ativo ? 'checked' : '') + '> ATIVO</label>' +
            '</div>';
    }).join('');
}

// ===== VARIANT TAB EVENT DELEGATION =====

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tab system
    initProdutoModalTabs();

    // Delegate events for variant tab
    document.addEventListener('click', function(e) {
        // Add grupo
        if (e.target && e.target.id === 'add-grupo-btn') {
            var container = document.getElementById('grupos-container');
            var idx = container ? container.children.length : 0;
            container.insertAdjacentHTML('beforeend', buildGrupoHTML({nome: '', valores: []}, idx));
        }

        // Remove grupo
        if (e.target && e.target.classList.contains('remove-grupo-btn')) {
            e.target.closest('.grupo-item').remove();
        }

        // Add valor
        if (e.target && e.target.classList.contains('add-valor-btn')) {
            var grupoItem = e.target.closest('.grupo-item');
            var input = grupoItem.querySelector('.novo-valor-input');
            var valor = input.value.trim();
            if (!valor) return;
            var valoresContainer = grupoItem.querySelector('.valores-container');
            valoresContainer.insertAdjacentHTML('beforeend',
                '<span class="inline-flex items-center gap-1 bg-gray-100 px-2 py-1 text-xs font-mono">' +
                escapeHtml(valor) +
                '<button type="button" class="remove-valor-btn text-gray-400 hover:text-black" data-valor-id="">×</button>' +
                '</span>');
            input.value = '';
        }

        // Remove valor
        if (e.target && e.target.classList.contains('remove-valor-btn')) {
            e.target.closest('span').remove();
        }

        // Gerar variantes
        if (e.target && e.target.id === 'gerar-variantes-btn') {
            salvarGruposEGerar(window.currentProdutoId);
        }

        // Salvar variantes
        if (e.target && e.target.id === 'salvar-variantes-btn') {
            salvarVariantes(window.currentProdutoId);
        }
    });
});

function salvarGruposEGerar(produtoId) {
    var grupos = collectGruposFromUI();
    var csrfToken = getCsrfToken();

    fetch('/admin/produtos/' + produtoId + '/opcao-grupos', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({ grupos: grupos })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (!data.success) { alert('Erro ao salvar grupos.'); return; }
        return fetch('/admin/produtos/' + produtoId + '/variantes/gerar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        });
    })
    .then(function(r) { return r ? r.json() : null; })
    .then(function(data) {
        if (data) loadVariantes(produtoId);
    });
}

function collectGruposFromUI() {
    var grupos = [];
    document.querySelectorAll('.grupo-item').forEach(function(item, idx) {
        var nome = item.querySelector('.grupo-nome-input').value.trim();
        if (!nome) return;
        var valores = [];
        item.querySelectorAll('.valores-container span').forEach(function(span, vIdx) {
            var texto = span.childNodes[0].textContent.trim();
            if (texto) valores.push({ valor: texto, ordem: vIdx });
        });
        grupos.push({ nome: nome, ordem: idx, valores: valores });
    });
    return grupos;
}

function salvarVariantes(produtoId) {
    var variantes = [];
    document.querySelectorAll('.variante-row').forEach(function(row) {
        var id = parseInt(row.getAttribute('data-variante-id'));
        var preco = row.querySelector('.variante-preco').value;
        var estoque = row.querySelector('.variante-estoque').value;
        var ativo = row.querySelector('.variante-ativo').checked;
        variantes.push({
            id: id,
            preco: preco !== '' ? parseFloat(preco) : null,
            estoque: estoque !== '' ? parseInt(estoque) : null,
            ativo: ativo,
        });
    });

    var estoqueCompartilhado = document.getElementById('estoque-compartilhado').checked;

    fetch('/admin/produtos/' + produtoId + '/variantes', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken(), 'Accept': 'application/json' },
        body: JSON.stringify({ estoque_compartilhado: estoqueCompartilhado, variantes: variantes })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success && window.showNotification) window.showNotification('Variantes salvas!', 'success');
    });
}
