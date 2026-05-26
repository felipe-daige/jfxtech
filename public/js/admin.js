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
    $('#custo_compra').maskMoney({
        prefix: 'R$ ',
        thousands: '.',
        decimal: ',',
        allowZero: true,
        allowNegative: false
    });
    $('#frete_compra').maskMoney({
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
        calcularPromocao();
    });
    $('#custo_compra').on('input', function () {
        calcularPromocao();
    });
    $('#frete_compra').on('input', function () {
        calcularPromocao();
    });
    $('#descricao').on('input', function () {
        syncDescriptionPreview(this.value);
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
    const descontoPix = document.getElementById('desconto_pix');
    if (descontoPix) descontoPix.value = '';
    toggleCamposPromocao();

    // Reaplicar máscara após reset
    $('#preco').maskMoney({
        prefix: 'R$ ',
        thousands: '.',
        decimal: ',',
        allowZero: true,
        allowNegative: false
    });
    $('#custo_compra').maskMoney({
        prefix: 'R$ ',
        thousands: '.',
        decimal: ',',
        allowZero: true,
        allowNegative: false
    });
    $('#frete_compra').maskMoney({
        prefix: 'R$ ',
        thousands: '.',
        decimal: ',',
        allowZero: true,
        allowNegative: false
    });


    // Limpar campos de specs
    ['sensor','dpi_maximo','switches','peso','conexao','polling_rate','dimensoes','cabo','iluminacao','garantia','layout','superficie','base','drivers','frequencia','microfone','bateria']
        .forEach(key => { const el = document.getElementById('spec_' + key); if (el) el.value = ''; });

    // Clear current product id for variant tab (new product has no id yet)
    window.currentProdutoId = null;

    // Reset tab to first tab when opening modal
    switchProdutoTab('dados');
    calcularPromocao();
    syncDescriptionPreview('');

    document.getElementById('modalProduto').classList.remove('hidden');
}

function editarProduto(id) {
    // Buscar dados do produto
    fetch(`${window.baseUrl}/admin/produtos/${id}`)
        .then(response => response.json())
        .then(data => {
            // Preencher formulário com os dados
            document.getElementById('produtoId').value = data.id;
            document.getElementById('nome').value = data.nome;
            document.getElementById('marca').value = data.marca || '';
            document.getElementById('descricao').value = data.descricao;
            document.getElementById('descricao_curta').value = data.descricao_curta || '';
            syncDescriptionPreview(data.descricao || '');
            document.getElementById('preco').value = 'R$ ' + data.preco;
            document.getElementById('custo_compra').value = data.custo_compra ? 'R$ ' + data.custo_compra : '';
            document.getElementById('frete_compra').value = data.frete_compra ? 'R$ ' + data.frete_compra : '';
            document.getElementById('estoque').value = data.estoque;
            document.getElementById('categoria_id').value = data.categoria_id;
            const pesoEl = document.getElementById('peso'); if (pesoEl) pesoEl.value = data.peso || '';
            const comprEl = document.getElementById('comprimento'); if (comprEl) comprEl.value = data.comprimento || '';
            const largEl = document.getElementById('largura'); if (largEl) largEl.value = data.largura || '';
            const altEl = document.getElementById('altura'); if (altEl) altEl.value = data.altura || '';

            // Preencher campos de promoção
            document.getElementById('em_promocao').checked = data.em_promocao;
            document.getElementById('desconto_percentual').value = data.desconto_percentual || '';
            const descontoPix = document.getElementById('desconto_pix');
            if (descontoPix) {
                descontoPix.value = data.desconto_pix !== null && data.desconto_pix !== undefined ? data.desconto_pix : '';
            }

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
            $('#custo_compra').maskMoney({
                prefix: 'R$ ',
                thousands: '.',
                decimal: ',',
                allowZero: true,
                allowNegative: false
            });
            $('#frete_compra').maskMoney({
                prefix: 'R$ ',
                thousands: '.',
                decimal: ',',
                allowZero: true,
                allowNegative: false
            });

            // Preencher specs
            const specs = data.specs || {};
            ['sensor','dpi_maximo','switches','peso','conexao','polling_rate','dimensoes','cabo','iluminacao','garantia','layout','superficie','base','drivers','frequencia','microfone','bateria']
                .forEach(key => { const el = document.getElementById('spec_' + key); if (el) el.value = specs[key] || ''; });

            // Mostrar imagens existentes com seleção de capa
            mostrarImagensExistentes(data.imagens);

            // Set current product id for variant tab
            window.currentProdutoId = id;

            // Reset tab to first tab when opening modal
            switchProdutoTab('dados');
            calcularPromocao();

            document.getElementById('modalProduto').classList.remove('hidden');
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
            const innerDiv = tabImagens.querySelector('div') || tabImagens;
            innerDiv.appendChild(container);
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
                               accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
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
                        window.JfxCsrfRecovery?.recover();
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
                window.JfxCsrfRecovery?.recover();
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
                window.JfxCsrfRecovery?.recover();
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
        calcularPromocao();
    }
}

function calcularPromocao() {
    const precoAtual = parseCurrencyBRL(document.getElementById('preco').value);
    const custoAtual = parseCurrencyBRL(document.getElementById('custo_compra').value);
    const freteCompra = parseCurrencyBRL(document.getElementById('frete_compra').value);
    const custoTotalCompra = custoAtual > 0 ? custoAtual + freteCompra : 0;
    const descontoPercentual = parseFloat(document.getElementById('desconto_percentual').value) || 0;
    const emPromocao = document.getElementById('em_promocao').checked;
    const valorDesconto = emPromocao && descontoPercentual > 0 ? precoAtual * (descontoPercentual / 100) : 0;
    const precoFinal = emPromocao && descontoPercentual > 0 ? precoAtual - valorDesconto : precoAtual;
    const lucroUnitario = precoFinal - custoTotalCompra;
    const margemBruta = precoFinal > 0 ? (lucroUnitario / precoFinal) * 100 : 0;

    document.getElementById('precoOriginalDisplay').textContent = formatCurrencyBRL(precoAtual);
    document.getElementById('descontoDisplay').textContent = formatCurrencyBRL(valorDesconto);
    document.getElementById('precoFinalDisplay').textContent = formatCurrencyBRL(precoFinal);
    document.getElementById('precoVendaResumoDisplay').textContent = formatCurrencyBRL(precoFinal);
    document.getElementById('custoCompraDisplay').textContent = formatCurrencyBRL(custoAtual);
    document.getElementById('freteCompraDisplay').textContent = formatCurrencyBRL(freteCompra);
    document.getElementById('custoTotalCompraDisplay').textContent = formatCurrencyBRL(custoTotalCompra);
    document.getElementById('lucroUnitarioDisplay').textContent = formatCurrencyBRL(lucroUnitario);
    document.getElementById('margemBrutaDisplay').textContent = formatPercentBRL(margemBruta);

    var lucroEl = document.getElementById('lucroUnitarioDisplay');
    var margemEl = document.getElementById('margemBrutaDisplay');
    if (lucroEl) {
        lucroEl.classList.toggle('text-red-600', lucroUnitario < 0);
        lucroEl.classList.toggle('text-black', lucroUnitario >= 0);
    }
    if (margemEl) {
        margemEl.classList.toggle('text-red-600', margemBruta < 0);
        margemEl.classList.toggle('text-black', margemBruta >= 0);
    }
}

function syncDescriptionPreview(value) {
    var preview = document.getElementById('descricaoPreview');
    var status = document.getElementById('descricaoPreviewStatus');
    if (!preview || !status) return;

    var html = (value || '').trim();

    if (!html) {
        status.textContent = 'Aguardando texto';
        preview.innerHTML = '<div class="product-description-content text-sm text-gray-500"><p>Digite a descrição usando HTML simples para ver a renderização aqui.</p></div>';
        return;
    }

    status.textContent = html.indexOf('<') !== -1 ? 'HTML detectado' : 'Texto simples';
    preview.innerHTML = '<div class="product-description-content">' + sanitizeDescriptionPreviewHtml(html) + '</div>';
}

function sanitizeDescriptionPreviewHtml(html) {
    return String(html)
        .replace(/<(?!\/?(p|ul|ol|li|strong|em|br)\b)[^>]*>/gi, '')
        .replace(/<(\/?)(p|ul|ol|li|strong|em|br)\b[^>]*>/gi, function (_, slash, tag) {
            tag = tag.toLowerCase();
            if (tag === 'br') return '<br>';
            return slash ? '</' + tag + '>' : '<' + tag + '>';
        });
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
    const status = document.getElementById('filtroStatus')?.value || '';
    const data = document.getElementById('filtroData')?.value || '';
    const cupom = (document.getElementById('filtroCupom')?.value || '').trim().toUpperCase();

    let url = new URL(window.location);
    if (status) {
        url.searchParams.set('status', status);
    } else {
        url.searchParams.delete('status');
    }

    if (data) {
        url.searchParams.set('data', data);
    } else {
        url.searchParams.delete('data');
    }

    if (cupom) {
        url.searchParams.set('cupom', cupom);
    } else {
        url.searchParams.delete('cupom');
    }

    window.location.href = url.toString();
}

function resolveAdminRoute(routeTemplate, params = {}) {
    let route = routeTemplate || '';

    Object.entries(params).forEach(([key, value]) => {
        route = route.replace(':' + key, encodeURIComponent(value));
    });

    try {
        const url = new URL(route, window.location.origin);

        if (url.origin !== window.location.origin) {
            return url.pathname + url.search + url.hash;
        }

        return url.toString();
    } catch (error) {
        return route;
    }
}

function verDetalhes(pedidoId) {
    const conteudo = document.getElementById('conteudoDetalhes');
    const modal = document.getElementById('modalDetalhes');
    if (!conteudo || !modal || !window.routes || !window.routes.adminPedidosDetalhes) return;

    // Populate header immediately (skeleton phase)
    modal.dataset.pedidoId = pedidoId;
    modal.dataset.pedidoStatus = '';
    const titleEl = document.getElementById('modalDetalhesTitle');
    const subEl = document.getElementById('modalDetalhesSub');
    const btnStatus = document.getElementById('btnAlterarStatusModal');
    if (titleEl) titleEl.textContent = 'Pedido #' + pedidoId;
    if (subEl) subEl.textContent = 'Detalhes do Pedido';
    if (btnStatus) btnStatus.classList.add('hidden');

    conteudo.innerHTML = `
        <div class="border border-[var(--color-lab-border)] bg-white overflow-hidden animate-pulse">
            <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.3fr)_minmax(320px,0.9fr)]">
                <div class="p-6 border-b xl:border-b-0 xl:border-r border-[var(--color-lab-border)] space-y-4">
                    <div class="h-3 w-28 bg-gray-200"></div>
                    <div class="h-10 w-64 bg-gray-200"></div>
                    <div class="h-4 w-full max-w-2xl bg-gray-100"></div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                        <div class="h-28 border border-[var(--color-lab-border)] bg-gray-50"></div>
                        <div class="h-28 border border-[var(--color-lab-border)] bg-gray-50"></div>
                        <div class="h-28 border border-[var(--color-lab-border)] bg-gray-50"></div>
                    </div>
                </div>
                <div class="p-6 bg-[var(--color-lab-bg)]">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="h-24 border border-[var(--color-lab-border)] bg-white"></div>
                        <div class="h-24 border border-[var(--color-lab-border)] bg-white"></div>
                        <div class="h-24 border border-[var(--color-lab-border)] bg-white"></div>
                        <div class="h-24 border border-[var(--color-lab-border)] bg-white"></div>
                    </div>
                </div>
            </div>
            <div class="p-6 space-y-4">
                <div class="h-32 border border-[var(--color-lab-border)] bg-gray-50"></div>
                <div class="h-32 border border-[var(--color-lab-border)] bg-gray-50"></div>
            </div>
        </div>
    `;
    modal.classList.remove('hidden');

    fetch(resolveAdminRoute(window.routes.adminPedidosDetalhes, { id: pedidoId }), {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
        .then(async response => {
            const contentType = response.headers.get('content-type') || '';
            const payload = contentType.includes('application/json') ? await response.json() : null;

            if (!response.ok) {
                throw new Error(payload?.error || 'Falha ao carregar o pedido.');
            }

            if (!payload || !payload.html) {
                throw new Error('Resposta inválida ao carregar o pedido.');
            }

            return payload;
        })
        .then(data => {
            conteudo.innerHTML = data.html;

            // Read status from rendered content and show action button
            const statusFromContent = conteudo.querySelector('[data-status]')?.dataset?.status ?? '';
            modal.dataset.pedidoStatus = statusFromContent;
            if (btnStatus) btnStatus.classList.remove('hidden');
        })
        .catch(error => {
            console.error('Erro ao carregar detalhes do pedido:', error);
            conteudo.innerHTML = '<div class="border border-red-200 bg-red-50 p-4 text-sm text-red-700">Erro ao carregar os detalhes do pedido. Atualize a página e tente novamente.</div>';
        });
}

function alterarStatus(pedidoId, statusAtual) {
    const form = document.getElementById('formStatus');
    form.action = resolveAdminRoute(window.routes.adminPedidosStatus, { id: pedidoId });
    form.dataset.pedidoId = pedidoId;
    document.getElementById('novoStatus').value = statusAtual;
    alternarCampoCodigoRastreio(statusAtual);
    alternarCampoCustosPreparacao(statusAtual);
    alternarCheckboxEmail(statusAtual);
    document.getElementById('modalStatus').classList.remove('hidden');
}

function fecharModalDetalhes() {
    const modal = document.getElementById('modalDetalhes');
    modal.classList.add('hidden');
    modal.dataset.pedidoId = '';
    modal.dataset.pedidoStatus = '';

    const titleEl = document.getElementById('modalDetalhesTitle');
    const subEl = document.getElementById('modalDetalhesSub');
    const btnStatus = document.getElementById('btnAlterarStatusModal');
    if (titleEl) titleEl.textContent = 'Carregando...';
    if (subEl) subEl.textContent = 'Detalhes do Pedido';
    if (btnStatus) btnStatus.classList.add('hidden');
}

function alterarStatusDoModal() {
    const modal = document.getElementById('modalDetalhes');
    const pedidoId = modal?.dataset?.pedidoId;
    const statusAtual = modal?.dataset?.pedidoStatus ?? '';
    if (!pedidoId) return;
    fecharModalDetalhes();
    alterarStatus(pedidoId, statusAtual);
}

function fecharModalStatus() {
    document.getElementById('modalStatus').classList.add('hidden');
}

function alternarCampoCodigoRastreio(status) {
    const wrapper = document.getElementById('codigoRastreioWrapper');
    const input = document.getElementById('codigoRastreioStatus');

    if (!wrapper || !input) {
        return;
    }

    const requiresTracking = status === 'enviado';

    wrapper.classList.toggle('hidden', !requiresTracking);
    input.required = requiresTracking;

    if (!requiresTracking) {
        input.value = '';
    }
}

var EMAIL_ASSUNTOS = {
    'pago':        'Pagamento aprovado',
    'processando': 'Pedido em processamento',
    'enviado':     'Pedido enviado para entrega',
    'entregue':    'Pedido entregue',
    'cancelado':   'Pedido cancelado'
};

function alternarCheckboxEmail(status) {
    var wrapper  = document.getElementById('emailNotificacaoWrapper');
    var checkbox = document.getElementById('sendEmailCheckbox');
    var texto    = document.getElementById('emailAssuntoTexto');

    if (!wrapper || !checkbox || !texto) return;

    var mostra = Object.prototype.hasOwnProperty.call(EMAIL_ASSUNTOS, status);
    wrapper.classList.toggle('hidden', !mostra);

    if (mostra) {
        checkbox.checked = true;
        texto.textContent = EMAIL_ASSUNTOS[status];
    } else {
        checkbox.checked = false;
    }
}

async function carregarCustosPreparacao(pedidoId) {
    const container = document.getElementById('preparacaoCustosConteudo');
    const notaAtual = document.getElementById('notaFiscalAtual');

    if (!container || !pedidoId || !window.routes.adminPedidosPreparacaoCustos) {
        return;
    }

    container.innerHTML = '<p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Carregando itens...</p>';
    if (notaAtual) {
        notaAtual.classList.add('hidden');
        notaAtual.innerHTML = '';
    }

    try {
        const response = await fetch(resolveAdminRoute(window.routes.adminPedidosPreparacaoCustos, { id: pedidoId }), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const payload = await response.json();

        if (!response.ok) {
            throw new Error(payload?.error || 'Falha ao carregar custos');
        }

        container.innerHTML = '';

        if (notaAtual && payload.nota_fiscal_imagem_url) {
            notaAtual.innerHTML = '<a href="' + payload.nota_fiscal_imagem_url + '" target="_blank" rel="noopener" class="underline hover:text-black">Nota fiscal atual anexada</a>';
            notaAtual.classList.remove('hidden');
        }

        if (!payload.items || payload.items.length === 0) {
            container.innerHTML = '<p class="font-mono text-[10px] uppercase tracking-widest text-red-600">Pedido sem itens.</p>';
            return;
        }

        payload.items.forEach(function (item) {
            const row = document.createElement('div');
            row.className = 'border border-[var(--color-lab-border)] bg-white p-3';

            const header = document.createElement('div');
            header.className = 'flex items-start justify-between gap-3 mb-2';

            const title = document.createElement('div');
            title.className = 'min-w-0';
            title.innerHTML =
                '<p class="font-mono text-xs font-bold text-black break-words"></p>' +
                '<p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-0.5"></p>';
            title.querySelector('p:first-child').textContent = item.produto_nome;
            title.querySelector('p:last-child').textContent = 'Qtd ' + item.quantidade + (item.variant_label ? ' · ' + item.variant_label : '');

            const source = document.createElement('span');
            source.className = 'shrink-0 font-mono text-[9px] uppercase tracking-widest border border-[var(--color-lab-border)] px-2 py-1 text-[var(--color-lab-muted)]';
            source.textContent = (item.source || 'sem_custo').replace('_', ' ');

            header.appendChild(title);
            header.appendChild(source);

            const statusLabel = document.createElement('label');
            statusLabel.className = 'font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-1';
            statusLabel.textContent = 'Confirmação do item';

            const statusSelect = document.createElement('select');
            statusSelect.name = 'itens[' + item.id + '][status_preparacao]';
            statusSelect.className = 'w-full mb-3 px-3 py-2 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black bg-white';
            [
                ['pendente', 'Pendente'],
                ['confirmado', 'Comprado / vai entregar'],
                ['cancelado', 'Cancelado / estorno'],
            ].forEach(function (optionData) {
                const option = document.createElement('option');
                option.value = optionData[0];
                option.textContent = optionData[1];
                option.selected = (item.status_preparacao || 'pendente') === optionData[0];
                statusSelect.appendChild(option);
            });

            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'itens[' + item.id + '][custo_unitario_declarado]';
            input.dataset.costInput = '1';
            input.dataset.suggestedCost = item.suggested_cost !== null && item.suggested_cost !== undefined ? String(item.suggested_cost) : '';
            input.value = item.value !== null && item.value !== undefined ? formatCurrencyInput(item.value) : '';
            input.placeholder = 'ex: 599,90';
            input.className = 'w-full px-3 py-2 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black bg-white';

            const meta = document.createElement('p');
            meta.className = 'mt-2 font-mono text-[10px] text-[var(--color-lab-muted)]';
            meta.textContent = item.suggested_cost !== null && item.suggested_cost !== undefined
                ? 'Sugestão do cadastro: R$ ' + formatCurrencyInput(item.suggested_cost)
                : 'Sem custo cadastrado no produto. Informe o custo real pago.';

            row.appendChild(header);
            row.appendChild(statusLabel);
            row.appendChild(statusSelect);
            row.appendChild(input);
            row.appendChild(meta);
            container.appendChild(row);

            function syncCostRequirement() {
                input.required = statusSelect.value !== 'cancelado';
                input.disabled = statusSelect.value === 'cancelado';
                if (statusSelect.value === 'cancelado') {
                    input.value = '';
                    meta.textContent = 'Este item será tratado como estorno da separação.';
                } else if (!input.value && input.dataset.suggestedCost !== '') {
                    input.value = formatCurrencyInput(input.dataset.suggestedCost);
                    meta.textContent = input.dataset.suggestedCost !== ''
                        ? 'Sugestão do cadastro: R$ ' + formatCurrencyInput(input.dataset.suggestedCost)
                        : 'Sem custo cadastrado no produto. Informe o custo real pago.';
                } else {
                    meta.textContent = input.dataset.suggestedCost !== ''
                        ? 'Sugestão do cadastro: R$ ' + formatCurrencyInput(input.dataset.suggestedCost)
                        : 'Sem custo cadastrado no produto. Informe o custo real pago.';
                }
            }

            statusSelect.addEventListener('change', syncCostRequirement);
            syncCostRequirement();
        });
    } catch (error) {
        container.innerHTML = '<p class="font-mono text-[10px] uppercase tracking-widest text-red-600">Erro ao carregar custos do pedido.</p>';
    }
}

function alternarCampoCustosPreparacao(status) {
    const wrapper = document.getElementById('preparacaoCustosWrapper');
    const form = document.getElementById('formStatus');

    if (!wrapper || !form) {
        return;
    }

    const requiresCosts = status === 'processando';
    wrapper.classList.toggle('hidden', !requiresCosts);
    wrapper.querySelectorAll('[data-cost-input]').forEach(function (input) {
        input.required = requiresCosts;
    });

    if (requiresCosts) {
        carregarCustosPreparacao(form.dataset.pedidoId);
    }
}

function aplicarCustosCatalogoNaPreparacao() {
    document.querySelectorAll('#preparacaoCustosConteudo [data-cost-input]').forEach(function (input) {
        if (!input.disabled && input.dataset.suggestedCost !== '') {
            input.value = formatCurrencyInput(input.dataset.suggestedCost);
        }
    });
}

function formatCurrencyInput(value) {
    const number = parseFloat(String(value).replace(',', '.'));
    if (Number.isNaN(number)) {
        return '';
    }

    return number.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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

    const novoStatus = document.getElementById('novoStatus');
    const formStatus = document.getElementById('formStatus');
    const codigoRastreioStatus = document.getElementById('codigoRastreioStatus');
    const btnUsarCustosCatalogo = document.getElementById('btnUsarCustosCatalogo');

    if (novoStatus) {
        alternarCampoCodigoRastreio(novoStatus.value);
        alternarCampoCustosPreparacao(novoStatus.value);

        novoStatus.addEventListener('change', function () {
            alternarCampoCodigoRastreio(this.value);
            alternarCampoCustosPreparacao(this.value);
            alternarCheckboxEmail(this.value);
        });
    }

    if (btnUsarCustosCatalogo) {
        btnUsarCustosCatalogo.addEventListener('click', aplicarCustosCatalogoNaPreparacao);
    }

    if (formStatus && novoStatus) {
        formStatus.addEventListener('submit', function (e) {
            if (novoStatus.value === 'enviado' && codigoRastreioStatus) {
                codigoRastreioStatus.value = codigoRastreioStatus.value.trim().toUpperCase();

                if (!codigoRastreioStatus.value) {
                    e.preventDefault();
                    alert('O código de rastreio é obrigatório para marcar o pedido como enviado.');
                    codigoRastreioStatus.focus();
                    return;
                }
            }

            if (novoStatus.value === 'processando') {
                const costInputs = Array.from(document.querySelectorAll('#preparacaoCustosConteudo [data-cost-input]:not(:disabled)'));
                const emptyCost = costInputs.find(function (input) {
                    return !input.value.trim();
                });

                if (costInputs.length === 0 || emptyCost) {
                    e.preventDefault();
                    alert('Informe o custo real unitário de todos os itens para marcar como Em preparação.');
                    if (emptyCost) {
                        emptyCost.focus();
                    }
                    return;
                }
            }

            if (novoStatus.value !== 'enviado' && codigoRastreioStatus) {
                codigoRastreioStatus.value = '';
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
    function initTooltip() {
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
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTooltip);
    } else {
        initTooltip();
    }
})();

// ===== ADMIN NOTIFICATION HELPER =====

function adminNotify(msg) {
    if (typeof window.showNotification === 'function') {
        window.showNotification(msg, 'success');
    } else {
        // Simple inline notification
        var bar = document.getElementById('admin-notify-bar');
        if (!bar) {
            bar = document.createElement('div');
            bar.id = 'admin-notify-bar';
            bar.style.cssText = 'position:fixed;top:1rem;right:1rem;z-index:9999;background:#000;color:#fff;padding:0.75rem 1.25rem;font-family:monospace;font-size:0.75rem;letter-spacing:0.05em;';
            document.body.appendChild(bar);
        }
        bar.textContent = msg;
        bar.style.display = 'block';
        setTimeout(function() { bar.style.display = 'none'; }, 3000);
    }
}

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

function switchProdutoTab(targetTab) {
    document.querySelectorAll('.produto-tab-btn').forEach(function(b) {
        var isActive = b.getAttribute('data-tab') === targetTab;
        b.classList.toggle('border-black', isActive);
        b.classList.toggle('text-black', isActive);
        b.classList.toggle('border-transparent', !isActive);
        b.classList.toggle('text-gray-400', !isActive);
    });
    document.querySelectorAll('.produto-tab-panel').forEach(function(p) {
        p.classList.add('hidden');
    });
    var panel = document.getElementById('tab-' + targetTab);
    if (panel) panel.classList.remove('hidden');
    if (targetTab === 'variantes') {
        loadVariantes(window.currentProdutoId);
    }
    if (targetTab === 'fornecedores') {
        loadFornecedoresProduto(window.currentProdutoId);
    }
}

// ===== SUPPLIER OFFER MANAGEMENT =====

window.fornecedoresDisponiveis = [];

function loadFornecedoresProduto(produtoId) {
    var loading = document.getElementById('fornecedores-loading');
    var content = document.getElementById('fornecedores-content');

    if (!produtoId) {
        if (loading) {
            loading.classList.remove('hidden');
            loading.textContent = 'Salve o produto primeiro para gerenciar fornecedores.';
        }
        if (content) content.classList.add('hidden');
        return;
    }

    var requestId = produtoId;
    if (loading) {
        loading.classList.remove('hidden');
        loading.textContent = 'CARREGANDO...';
    }
    if (content) content.classList.add('hidden');

    var url = (window.routes.adminProdutoFornecedores || '/admin/produtos/:id/fornecedores').replace(':id', produtoId);
    fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() } })
        .then(function(r) { if (!r.ok) throw new Error('Erro ao carregar fornecedores: ' + r.status); return r.json(); })
        .then(function(data) {
            if (window.currentProdutoId !== requestId) return;
            window.fornecedoresDisponiveis = data.fornecedores || [];
            renderFornecedorOfertas(data.ofertas || []);
            if (loading) loading.classList.add('hidden');
            if (content) content.classList.remove('hidden');
        })
        .catch(function(err) {
            console.error(err);
            if (loading) loading.textContent = 'Erro ao carregar fornecedores. Tente novamente.';
        });
}

function renderFornecedorOfertas(ofertas) {
    var container = document.getElementById('fornecedores-ofertas-lista');
    if (!container) return;

    if (!ofertas.length) {
        container.innerHTML = '<div class="border border-dashed border-gray-300 p-5 text-center font-mono text-xs text-gray-400 uppercase tracking-widest">Nenhum fornecedor cadastrado para este produto.</div>';
        return;
    }

    container.innerHTML = ofertas.map(function(oferta) {
        return buildFornecedorOfertaHTML(oferta);
    }).join('');
}

function addFornecedorOfertaRow() {
    var container = document.getElementById('fornecedores-ofertas-lista');
    if (!container) return;

    if (container.querySelector('.border-dashed')) {
        container.innerHTML = '';
    }

    container.insertAdjacentHTML('beforeend', buildFornecedorOfertaHTML({ fornecedor: {}, ativo: true }));
}

function removeFornecedorOfertaRow(btn) {
    var row = btn.closest('.fornecedor-oferta-row');
    if (row) row.remove();
    var container = document.getElementById('fornecedores-ofertas-lista');
    if (container && !container.querySelector('.fornecedor-oferta-row')) {
        renderFornecedorOfertas([]);
    }
}

function buildFornecedorOfertaHTML(oferta) {
    var fornecedor = oferta.fornecedor || {};
    var fornecedorId = oferta.fornecedor_id || fornecedor.id || '';

    return '<div class="fornecedor-oferta-row border border-[var(--color-lab-border)] bg-white p-4 space-y-3">' +
        '<div class="flex items-start justify-between gap-3">' +
            '<div class="flex-1 min-w-0">' +
                '<label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Fornecedor existente</label>' +
                '<select class="fornecedor-select w-full border border-[var(--color-lab-border)] px-3 py-2 text-sm font-mono bg-white focus:outline-none focus:border-black" onchange="syncFornecedorRowFromSelect(this)">' +
                    buildFornecedorOptions(fornecedorId) +
                '</select>' +
            '</div>' +
            '<button type="button" onclick="removeFornecedorOfertaRow(this)" class="mt-6 border border-gray-300 px-3 py-2 font-mono text-[10px] uppercase tracking-widest text-gray-500 hover:border-black hover:text-black">Remover</button>' +
        '</div>' +
        '<div class="grid grid-cols-1 md:grid-cols-2 gap-3">' +
            inputFornecedor('nome', 'Nome', fornecedor.nome) +
            inputFornecedor('contato_nome', 'Contato', fornecedor.contato_nome) +
            inputFornecedor('email', 'E-mail', fornecedor.email, 'email') +
            inputFornecedor('telefone', 'Telefone', fornecedor.telefone) +
            inputFornecedor('whatsapp', 'WhatsApp', fornecedor.whatsapp) +
            inputFornecedor('pais', 'País', fornecedor.pais) +
            inputFornecedor('perfil_url', 'URL perfil', fornecedor.perfil_url, 'url') +
            inputFornecedor('site_url', 'Site', fornecedor.site_url, 'url') +
        '</div>' +
        '<div class="grid grid-cols-1 md:grid-cols-3 gap-3">' +
            inputOferta('preco_compra', 'Preço compra', oferta.preco_compra, 'number', '0.01') +
            inputOferta('frete_compra', 'Frete', oferta.frete_compra, 'number', '0.01') +
            inputOferta('moeda', 'Moeda', oferta.moeda || '') +
            inputOferta('quantidade_minima', 'Qtd mínima', oferta.quantidade_minima, 'number', '1') +
            inputOferta('prazo_dias', 'Prazo dias', oferta.prazo_dias, 'number', '1') +
            inputOferta('sku_fornecedor', 'SKU fornecedor', oferta.sku_fornecedor) +
        '</div>' +
        '<div class="grid grid-cols-1 md:grid-cols-2 gap-3">' +
            inputOferta('url_produto', 'URL produto', oferta.url_produto, 'url') +
            inputOferta('cotado_em', 'Cotado em', oferta.cotado_em, 'date') +
        '</div>' +
        '<div class="grid grid-cols-1 md:grid-cols-2 gap-3">' +
            textareaFornecedor('observacoes', 'Obs. fornecedor', fornecedor.observacoes) +
            textareaOferta('observacoes', 'Obs. oferta', oferta.observacoes) +
        '</div>' +
        '<label class="inline-flex items-center gap-2 font-mono text-xs uppercase tracking-widest text-[var(--color-lab-muted)]">' +
            '<input type="checkbox" class="fornecedor-oferta-ativo" ' + (oferta.ativo === false ? '' : 'checked') + '> Ativo' +
        '</label>' +
    '</div>';
}

function buildFornecedorOptions(selectedId) {
    var html = '<option value="">Novo fornecedor</option>';
    (window.fornecedoresDisponiveis || []).forEach(function(fornecedor) {
        var label = fornecedor.nome || fornecedor.email || fornecedor.telefone || ('Fornecedor #' + fornecedor.id);
        html += '<option value="' + fornecedor.id + '"' + (String(selectedId) === String(fornecedor.id) ? ' selected' : '') + '>' + escapeHtml(label) + '</option>';
    });
    return html;
}

function inputFornecedor(field, label, value, type) {
    return inputBase('fornecedor-field', field, label, value, type);
}

function inputOferta(field, label, value, type, step) {
    return inputBase('oferta-field', field, label, value, type, step);
}

function inputBase(className, field, label, value, type, step) {
    return '<div>' +
        '<label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">' + escapeHtml(label) + '</label>' +
        '<input type="' + (type || 'text') + '" data-field="' + field + '" class="' + className + ' w-full border border-[var(--color-lab-border)] px-3 py-2 text-sm font-mono focus:outline-none focus:border-black" value="' + escapeHtml(value != null ? String(value) : '') + '"' + (step ? ' step="' + step + '" min="0"' : '') + '>' +
    '</div>';
}

function textareaFornecedor(field, label, value) {
    return textareaBase('fornecedor-field', field, label, value);
}

function textareaOferta(field, label, value) {
    return textareaBase('oferta-field', field, label, value);
}

function textareaBase(className, field, label, value) {
    return '<div>' +
        '<label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">' + escapeHtml(label) + '</label>' +
        '<textarea data-field="' + field + '" rows="3" class="' + className + ' w-full border border-[var(--color-lab-border)] px-3 py-2 text-sm font-mono focus:outline-none focus:border-black">' + escapeHtml(value != null ? String(value) : '') + '</textarea>' +
    '</div>';
}

function syncFornecedorRowFromSelect(select) {
    var row = select.closest('.fornecedor-oferta-row');
    var id = parseInt(select.value);
    var fornecedor = (window.fornecedoresDisponiveis || []).find(function(item) { return item.id === id; });
    if (!row || !fornecedor) return;

    row.querySelectorAll('.fornecedor-field').forEach(function(input) {
        var key = input.getAttribute('data-field');
        input.value = fornecedor[key] || '';
    });
}

function collectFornecedoresOfertasFromUI() {
    var ofertas = [];
    document.querySelectorAll('.fornecedor-oferta-row').forEach(function(row) {
        var fornecedor = {};
        row.querySelectorAll('.fornecedor-field').forEach(function(input) {
            fornecedor[input.getAttribute('data-field')] = input.value.trim() || null;
        });

        var oferta = {
            fornecedor_id: row.querySelector('.fornecedor-select').value ? parseInt(row.querySelector('.fornecedor-select').value) : null,
            fornecedor: fornecedor,
            ativo: row.querySelector('.fornecedor-oferta-ativo').checked
        };

        row.querySelectorAll('.oferta-field').forEach(function(input) {
            var key = input.getAttribute('data-field');
            var value = input.value.trim();
            if (['preco_compra', 'frete_compra'].indexOf(key) !== -1) {
                oferta[key] = value !== '' ? parseFloat(value) : null;
            } else if (['quantidade_minima', 'prazo_dias'].indexOf(key) !== -1) {
                oferta[key] = value !== '' ? parseInt(value) : null;
            } else {
                oferta[key] = value !== '' ? value : null;
            }
        });

        ofertas.push(oferta);
    });
    return ofertas;
}

function salvarFornecedoresProduto(produtoId) {
    if (!produtoId) {
        alert('Salve o produto primeiro para gerenciar fornecedores.');
        return;
    }

    var url = (window.routes.adminProdutoFornecedoresSalvar || '/admin/produtos/:id/fornecedores').replace(':id', produtoId);
    fetch(url, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken(), 'Accept': 'application/json' },
        body: JSON.stringify({ ofertas: collectFornecedoresOfertasFromUI() })
    })
    .then(function(r) {
        if (!r.ok) {
            return r.json().catch(function() { return {}; }).then(function(data) {
                throw new Error(data.message || 'Erro ao salvar fornecedores: ' + r.status);
            });
        }
        return r.json();
    })
    .then(function() {
        loadFornecedoresProduto(produtoId);
    })
    .catch(function(err) {
        console.error(err);
        alert(err.message);
    });
}

// ===== VARIANT MANAGEMENT FUNCTIONS =====

function loadVariantes(produtoId) {
    if (!produtoId) {
        var loading = document.getElementById('variantes-loading');
        if (loading) loading.textContent = 'Salve o produto primeiro para gerenciar variantes.';
        return;
    }
    var requestId = produtoId; // capture at call time
    var loading = document.getElementById('variantes-loading');
    var content = document.getElementById('variantes-content');
    if (loading) { loading.classList.remove('hidden'); loading.textContent = 'CARREGANDO...'; }
    if (content) content.classList.add('hidden');

    fetch('/admin/produtos/' + produtoId + '/opcoes', {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        // Discard if a different product was opened while this fetch was in flight
        if (window.currentProdutoId !== requestId) return;
        renderGrupos(data.grupos || []);
        renderVariantesTabela(data.variantes || [], data.imagens || []);
        var cb = document.getElementById('estoque-compartilhado');
        if (cb) cb.checked = data.estoque_compartilhado;
        if (loading) loading.classList.add('hidden');
        if (content) content.classList.remove('hidden');
    })
    .catch(function(err) {
        if (window.currentProdutoId !== requestId) return;
        console.error('Erro ao carregar variantes:', err);
        var loading = document.getElementById('variantes-loading');
        if (loading) {
            loading.textContent = 'Erro ao carregar variantes. Tente novamente.';
        }
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
        return '<span class="inline-flex items-center gap-1 bg-gray-100 px-2 py-1 text-xs font-mono" data-valor="' + escapeHtml(v.valor) + '">' +
               escapeHtml(v.valor) +
               '<button type="button" onclick="removeValor(this)" class="remove-valor-btn text-gray-400 hover:text-black" data-valor-id="' + v.id + '">×</button>' +
               '</span>';
    }).join('');

    return '<div class="grupo-item border border-gray-200 p-4" data-grupo-id="' + (grupo.id || '') + '" data-grupo-idx="' + idx + '">' +
        '<div class="flex items-center gap-2 mb-3">' +
        '<input type="text" class="grupo-nome-input flex-1 border border-gray-300 px-3 py-2 text-sm font-mono uppercase" value="' + escapeHtml(grupo.nome || '') + '" placeholder="EX: COR">' +
        '<button type="button" onclick="removeGrupoOpcoes(this)" class="remove-grupo-btn text-gray-400 hover:text-black font-mono text-sm px-2">REMOVER</button>' +
        '</div>' +
        '<div class="flex flex-wrap gap-2 mb-2 valores-container">' + valoresHTML + '</div>' +
        '<div class="flex gap-2">' +
        '<input type="text" class="novo-valor-input flex-1 border border-gray-300 px-3 py-2 text-sm font-mono" placeholder="Novo valor...">' +
        '<button type="button" onclick="addValorToGrupo(this)" class="add-valor-btn bg-gray-100 hover:bg-gray-200 px-3 py-2 text-sm font-mono uppercase transition-colors">+ ADD</button>' +
        '</div>' +
        '</div>';
}

function renderVariantesTabela(variantes, imagens) {
    var container = document.getElementById('variantes-tabela');
    var wrapper = document.getElementById('variantes-tabela-container');
    if (!container) return;
    if (variantes.length === 0) {
        if (wrapper) wrapper.classList.add('hidden');
        return;
    }
    if (wrapper) wrapper.classList.remove('hidden');
    container.innerHTML = variantes.map(function(v) {
        var fotosHtml = '';
        if (imagens && imagens.length > 0) {
            var checks = imagens.map(function(img) {
                var isChecked = (v.imagem_ids || []).indexOf(img.id) !== -1;
                return '<label class="relative cursor-pointer group" title="' + escapeHtml(img.url.split('/').pop()) + '">' +
                    '<input type="checkbox" class="variante-imagem-check sr-only" value="' + img.id + '"' + (isChecked ? ' checked' : '') + '>' +
                    '<img src="' + img.url + '" class="variante-foto-thumb w-12 h-12 object-cover border-2 ' + (isChecked ? 'border-black' : 'border-gray-200') + ' group-hover:border-gray-400 transition-colors">' +
                    '</label>';
            }).join('');
            fotosHtml = '<div class="w-full mt-2 pt-2 border-t border-gray-200">' +
                '<span class="text-[10px] font-mono uppercase tracking-widest text-gray-400 block mb-1">FOTOS DA VARIANTE</span>' +
                '<div class="variante-imagens-checks flex flex-wrap gap-1">' + checks + '</div>' +
                '</div>';
        }
        var conteudoHtml =
            '<div class="w-full mt-3 pt-3 border-t border-gray-200">' +
            '<span class="text-[10px] font-mono uppercase tracking-widest text-gray-400 block mb-1">CONTEÚDO DA VARIANTE <span class="text-gray-300">(opcional — herda do produto se vazio)</span></span>' +
            '<textarea class="variante-descricao w-full border border-gray-200 px-2 py-1 text-xs font-mono resize-y min-h-[60px] mb-2" placeholder="Descrição (deixe vazio para herdar do produto)">' +
                (v.descricao != null ? escapeHtml(v.descricao) : '') +
            '</textarea>' +
            '<div class="grid grid-cols-2 gap-1">' +
            ['sensor','dpi_maximo','switches','peso','conexao','polling_rate','dimensoes','cabo','iluminacao','garantia','layout','superficie','base','drivers','frequencia','microfone','bateria'].map(function(k) {
                var label = {'sensor':'Sensor','dpi_maximo':'DPI Máx','switches':'Switches','peso':'Peso','conexao':'Conexão','polling_rate':'Polling Rate','dimensoes':'Dimensões','cabo':'Cabo','iluminacao':'Iluminação','garantia':'Garantia','layout':'Layout','superficie':'Superfície','base':'Base','drivers':'Drivers','frequencia':'Frequência','microfone':'Microfone','bateria':'Bateria'}[k] || k;
                var val = (v.specs && v.specs[k] != null) ? v.specs[k] : '';
                return '<input type="text" class="variante-spec border border-gray-200 px-2 py-1 text-xs font-mono" ' +
                       'data-spec-key="' + k + '" placeholder="' + escapeHtml(label) + '" value="' + escapeHtml(val) + '">';
            }).join('') +
            '</div>' +
            '</div>';
        return '<div class="variante-row" data-variante-id="' + v.id + '" data-valores=\'' + JSON.stringify(v.valores) + '\'>' +
            '<div class="flex items-center gap-3 border border-gray-100 p-3">' +
            '<span class="flex-1 text-sm font-mono">' + escapeHtml(v.label) + '</span>' +
            '<span class="text-[10px] font-mono uppercase tracking-widest px-2 py-0.5 ' +
                (v.descricao !== null ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-400') +
                ' variante-conteudo-badge">' +
                (v.descricao !== null ? 'PRÓPRIA' : 'HERDA') +
            '</span>' +
            '<button type="button" onclick="toggleVarianteEdit(this)" class="text-xs font-mono uppercase tracking-widest border border-gray-300 px-3 py-1 hover:border-black hover:bg-gray-50 transition-colors">✎ EDITAR</button>' +
            '</div>' +
            '<div class="variante-edit hidden border border-t-0 border-gray-200 bg-gray-50 p-3 flex flex-wrap items-center gap-3">' +
            '<input type="number" class="variante-preco w-28 border border-gray-300 px-2 py-1 text-sm font-mono" placeholder="Preço" value="' + (v.preco !== null && v.preco !== undefined ? v.preco : '') + '" step="0.01" min="0">' +
            '<input type="number" class="variante-custo w-28 border border-gray-300 px-2 py-1 text-sm font-mono" placeholder="Custo" value="' + (v.custo_compra !== null && v.custo_compra !== undefined ? v.custo_compra : '') + '" step="0.01" min="0">' +
            '<input type="number" class="variante-frete w-28 border border-gray-300 px-2 py-1 text-sm font-mono" placeholder="Frete" value="' + (v.frete_compra !== null && v.frete_compra !== undefined ? v.frete_compra : '') + '" step="0.01" min="0">' +
            '<input type="number" class="variante-estoque w-20 border border-gray-300 px-2 py-1 text-sm font-mono estoque-field" placeholder="Estq" value="' + (v.estoque !== null ? v.estoque : '') + '" min="0">' +
            '<label class="flex items-center gap-1 text-xs font-mono"><input type="checkbox" class="variante-ativo" ' + (v.ativo ? 'checked' : '') + '> ATIVO</label>' +
            '<button type="button" onclick="toggleVarianteEdit(this)" class="text-xs font-mono uppercase tracking-widest bg-black text-white px-3 py-1 hover:bg-gray-800 transition-colors ml-auto">✔ OK</button>' +
            '<button type="button" onclick="toggleVarianteEdit(this)" class="text-xs font-mono uppercase tracking-widest border border-gray-300 px-2 py-1 hover:border-black transition-colors">✖</button>' +
            fotosHtml +
            conteudoHtml +
            '</div>' +
            '</div>';
    }).join('');

    // Live badge update when description textarea is edited
    container.querySelectorAll('.variante-descricao').forEach(function(ta) {
        ta.addEventListener('input', function() {
            var badge = ta.closest('.variante-row').querySelector('.variante-conteudo-badge');
            if (!badge) return;
            var temConteudo = ta.value.trim() !== '';
            badge.textContent = temConteudo ? 'PRÓPRIA' : 'HERDA';
            badge.className = badge.className
                .replace(/bg-\S+|text-(?:blue|gray)-\S+/g, '')
                .trim() + ' ' + (temConteudo ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-400');
        });
    });

    // Toggle visual border on photo checkboxes
    container.querySelectorAll('.variante-imagem-check').forEach(function(cb) {
        cb.addEventListener('change', function() {
            var img = this.closest('label').querySelector('.variante-foto-thumb');
            if (img) {
                img.classList.toggle('border-black', this.checked);
                img.classList.toggle('border-gray-200', !this.checked);
            }
        });
    });
}

function toggleVarianteEdit(btn) {
    var row = btn.closest('.variante-row');
    var panel = row.querySelector('.variante-edit');
    panel.classList.toggle('hidden');
}

// ===== VARIANT BUTTON HANDLERS =====

function addGrupoOpcoes() {
    var container = document.getElementById('grupos-container');
    var idx = container ? container.children.length : 0;
    container.insertAdjacentHTML('beforeend', buildGrupoHTML({nome: '', valores: []}, idx));
}

function removeGrupoOpcoes(btn) {
    btn.closest('.grupo-item').remove();
    gerarPreviewLocal();
}

function addValorToGrupo(btn) {
    var grupoItem = btn.closest('.grupo-item');
    var input = grupoItem.querySelector('.novo-valor-input');
    var valor = input.value.trim();
    if (!valor) return;
    var valoresContainer = grupoItem.querySelector('.valores-container');
    valoresContainer.insertAdjacentHTML('beforeend',
        '<span class="inline-flex items-center gap-1 bg-gray-100 px-2 py-1 text-xs font-mono" data-valor="' + escapeHtml(valor) + '">' +
        escapeHtml(valor) +
        '<button type="button" onclick="removeValor(this)" class="remove-valor-btn text-gray-400 hover:text-black" data-valor-id="">×</button>' +
        '</span>');
    input.value = '';
    gerarPreviewLocal();
}

function gerarPreviewLocal() {
    var grupos = [];
    document.querySelectorAll('.grupo-item').forEach(function(item) {
        var nome = item.querySelector('.grupo-nome-input').value.trim();
        if (!nome) return;
        var vals = [];
        item.querySelectorAll('.valores-container span').forEach(function(span) {
            var v = span.getAttribute('data-valor');
            if (v) vals.push(v);
        });
        if (vals.length) grupos.push(vals);
    });
    if (!grupos.length) { renderVariantesTabela([]); return; }

    var combos = [[]];
    grupos.forEach(function(vals) {
        var next = [];
        combos.forEach(function(c) {
            vals.forEach(function(v) { next.push(c.concat([v])); });
        });
        combos = next;
    });

    var saved = {};
    document.querySelectorAll('.variante-row').forEach(function(row) {
        var id = parseInt(row.getAttribute('data-variante-id')) || 0;
        if (!id) return;
        var labelEl = row.querySelector('.flex-1.text-sm.font-mono');
        if (!labelEl) return;
        var savedSpecs = {};
        row.querySelectorAll('.variante-spec').forEach(function(inp) {
            var key = inp.getAttribute('data-spec-key');
            var val = inp.value.trim();
            if (key && val !== '') savedSpecs[key] = val;
        });
        var descricaoTa = row.querySelector('.variante-descricao');
        saved[labelEl.textContent.trim()] = {
            id: id,
            preco: row.querySelector('.variante-preco').value,
            custo_compra: row.querySelector('.variante-custo').value,
            frete_compra: row.querySelector('.variante-frete').value,
            estoque: row.querySelector('.variante-estoque').value,
            ativo: row.querySelector('.variante-ativo').checked,
            descricao: descricaoTa && descricaoTa.value.trim() !== '' ? descricaoTa.value.trim() : null,
            specs: Object.keys(savedSpecs).length > 0 ? savedSpecs : null
        };
    });

    var variantes = combos.map(function(combo) {
        var label = combo.join(' / ');
        var s = saved[label];
        return {
            id: s ? s.id : 0,
            label: label,
            valores: [],
            preco: s ? (s.preco !== '' ? parseFloat(s.preco) : null) : null,
            custo_compra: s ? (s.custo_compra !== '' ? parseFloat(s.custo_compra) : null) : null,
            frete_compra: s ? (s.frete_compra !== '' ? parseFloat(s.frete_compra) : null) : null,
            estoque: s ? (s.estoque !== '' ? parseInt(s.estoque) : null) : null,
            ativo: s ? s.ativo : true,
            descricao: s ? s.descricao : null,
            specs: s ? s.specs : null
        };
    });

    renderVariantesTabela(variantes);
}

function removeValor(btn) {
    var span = btn.closest('span');
    var valorId = parseInt(btn.getAttribute('data-valor-id'));
    span.remove();
    if (valorId) {
        document.querySelectorAll('.variante-row').forEach(function(row) {
            try {
                var valores = JSON.parse(row.getAttribute('data-valores') || '[]');
                if (valores.includes(valorId)) row.remove();
            } catch(e) {}
        });
    }
    gerarPreviewLocal();
}

function salvarTudo(produtoId) {
    var csrfToken = getCsrfToken();
    var grupos = collectGruposFromUI();
    var estoqueCompartilhado = document.getElementById('estoque-compartilhado').checked;
    var variantesData = [];
    document.querySelectorAll('.variante-row').forEach(function(row) {
        var id = parseInt(row.getAttribute('data-variante-id'));
        if (!id) return;
        var preco = row.querySelector('.variante-preco').value;
        var custoCompra = row.querySelector('.variante-custo').value;
        var freteCompra = row.querySelector('.variante-frete').value;
        var estoque = row.querySelector('.variante-estoque').value;
        var ativo = row.querySelector('.variante-ativo').checked;
        var imagem_ids = [];
        row.querySelectorAll('.variante-imagem-check:checked').forEach(function(cb) {
            imagem_ids.push(parseInt(cb.value));
        });
        var descricaoTextarea = row.querySelector('.variante-descricao');
        var descricaoVal = descricaoTextarea ? descricaoTextarea.value.trim() : null;
        var specsVal = {};
        row.querySelectorAll('.variante-spec').forEach(function(inp) {
            var key = inp.getAttribute('data-spec-key');
            var val = inp.value.trim();
            if (key && val !== '') specsVal[key] = val;
        });
        variantesData.push({
            id: id,
            preco: preco !== '' ? parseFloat(preco) : null,
            custo_compra: custoCompra !== '' ? parseFloat(custoCompra) : null,
            frete_compra: freteCompra !== '' ? parseFloat(freteCompra) : null,
            estoque: estoque !== '' ? parseInt(estoque) : null,
            ativo: ativo,
            imagem_ids: imagem_ids,
            descricao: descricaoVal !== '' && descricaoVal !== null ? descricaoVal : null,
            specs: Object.keys(specsVal).length > 0 ? specsVal : null,
        });
    });

    fetch('/admin/produtos/' + produtoId + '/opcao-grupos', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({ grupos: grupos })
    })
    .then(function(r) { if (!r.ok) throw new Error('Erro ao salvar grupos: ' + r.status); return r.json(); })
    .then(function(data) {
        if (!data.success) throw new Error('Erro ao salvar grupos.');
        return fetch('/admin/produtos/' + produtoId + '/variantes/gerar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        });
    })
    .then(function(r) { if (!r.ok) throw new Error('Erro ao gerar variantes: ' + r.status); return r.json(); })
    .then(function() {
        if (variantesData.length === 0) return null;
        return fetch('/admin/produtos/' + produtoId + '/variantes', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ estoque_compartilhado: estoqueCompartilhado, variantes: variantesData })
        });
    })
    .then(function(r) { if (r && !r.ok) throw new Error('Erro ao salvar variantes: ' + r.status); return r ? r.json() : null; })
    .then(function() { loadVariantes(produtoId); })
    .catch(function(err) { console.error(err); alert('Erro: ' + err.message); });
}

function collectGruposFromUI() {
    var grupos = [];
    document.querySelectorAll('.grupo-item').forEach(function(item, idx) {
        var nome = item.querySelector('.grupo-nome-input').value.trim();
        if (!nome) return;
        var valores = [];
        item.querySelectorAll('.valores-container span').forEach(function(span, vIdx) {
            var texto = span.getAttribute('data-valor');
            if (texto) valores.push({ valor: texto, ordem: vIdx });
        });
        grupos.push({ nome: nome, ordem: idx, valores: valores });
    });
    return grupos;
}

window.salvarRastreio = async function(pedidoId) {
    var input         = document.getElementById('rastreio-input-' + pedidoId);
    var freteInput    = document.getElementById('frete-custo-real-input-' + pedidoId);
    var btn           = document.getElementById('rastreio-btn-' + pedidoId);
    var feedback      = document.getElementById('rastreio-feedback-' + pedidoId);
    if (!input || !btn) return;

    var originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = '...';

    var payload = { codigo_rastreio: input.value.trim().toUpperCase() || null };
    if (freteInput) {
        var freteVal = freteInput.value.trim();
        payload.frete_custo_real = freteVal !== '' ? parseFloat(freteVal) : null;
    }

    try {
        var url = resolveAdminRoute(window.routes.adminPedidosRastreio || '', { id: pedidoId });
        var res = await fetch(url, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        });

        var data = await res.json();
        if (!data.success) throw new Error('Falha ao salvar');

        if (feedback) {
            feedback.classList.remove('hidden');
            setTimeout(function() { feedback.classList.add('hidden'); }, 2000);
        }
        input.value = input.value.trim().toUpperCase();
    } catch (err) {
        alert('Erro ao salvar código de rastreio.');
    } finally {
        btn.disabled = false;
        btn.textContent = originalText;
    }
};

function parseCurrencyBRL(value) {
    if (!value) return 0;
    return parseFloat(String(value).replace(/[^\d,]/g, '').replace(',', '.')) || 0;
}

function formatCurrencyBRL(value) {
    return 'R$ ' + (Number(value) || 0).toFixed(2).replace('.', ',');
}

function formatPercentBRL(value) {
    return (Number(value) || 0).toFixed(2).replace('.', ',') + '%';
}
