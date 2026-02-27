// Filtros de produtos em tempo real
document.addEventListener('DOMContentLoaded', function() {
    console.log('MX - Filtros de produtos carregados! 🏍️');
    
    // Elementos do DOM
    const buscaInput = document.getElementById('busca-produto');
    const categoriaCheckboxes = document.querySelectorAll('.categoria-checkbox');
    const precoMinInput = document.getElementById('preco-min');
    const precoMaxInput = document.getElementById('preco-max');
    const ordenacaoSelect = document.getElementById('ordenacao');
    
    // Função para aplicar filtros via AJAX
    function aplicarFiltros() {
        // Mostrar indicador de carregamento
        const produtosGrid = document.querySelector('.grid.sm\\:grid-cols-2.lg\\:grid-cols-3.xl\\:grid-cols-4');
        if (produtosGrid) {
            produtosGrid.innerHTML = `
                <div class="col-span-full flex justify-center items-center py-12">
                    <div class="text-center">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600 mx-auto mb-4"></div>
                        <p class="text-gray-600">Carregando produtos...</p>
                    </div>
                </div>
            `;
        }
        
        const params = new URLSearchParams();
        
        // Busca por nome
        if (buscaInput.value.trim()) {
            params.set('busca', buscaInput.value.trim());
        }
        
        // Categorias selecionadas
        const categoriasSelecionadas = Array.from(categoriaCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);
        
        if (categoriasSelecionadas.length > 0) {
            categoriasSelecionadas.forEach(categoria => {
                params.append('categorias[]', categoria);
            });
        }
        
        // Faixa de preço
        if (precoMinInput.value) {
            params.set('preco_min', precoMinInput.value);
        }
        if (precoMaxInput.value) {
            params.set('preco_max', precoMaxInput.value);
        }
        
        // Ordenação
        if (ordenacaoSelect.value) {
            params.set('ordenacao', ordenacaoSelect.value);
        }
        
        // Fazer requisição AJAX
        fetch(window.location.pathname + '?' + params.toString(), {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(response => response.text())
        .then(html => {
            // Extrair apenas a área dos produtos do HTML retornado
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const produtosGrid = doc.querySelector('.grid.sm\\:grid-cols-2.lg\\:grid-cols-3.xl\\:grid-cols-4');
            const pagination = doc.querySelector('.bg-white.rounded-xl.border.border-gray-200.p-4');
            
            // Atualizar a área dos produtos
            const currentProdutosGrid = document.querySelector('.grid.sm\\:grid-cols-2.lg\\:grid-cols-3.xl\\:grid-cols-4');
            if (currentProdutosGrid && produtosGrid) {
                currentProdutosGrid.innerHTML = produtosGrid.innerHTML;
            }
            
            // Atualizar paginação se existir
            const currentPagination = document.querySelector('.bg-white.rounded-xl.border.border-gray-200.p-4');
            if (pagination) {
                if (currentPagination) {
                    currentPagination.outerHTML = pagination.outerHTML;
                } else {
                    // Adicionar paginação se não existir
                    const container = document.querySelector('.flex-1');
                    if (container) {
                        container.appendChild(pagination);
                    }
                }
            } else if (currentPagination) {
                // Remover paginação se não existir mais
                currentPagination.remove();
            }
            
            // Atualizar URL sem recarregar a página
            const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            window.history.pushState({}, '', newUrl);
        })
        .catch(error => {
            console.error('Erro ao aplicar filtros:', error);
        });
    }
    
    // Event listeners
    if (buscaInput) {
        let timeoutId;
        buscaInput.addEventListener('input', function() {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                aplicarFiltros();
            }, 500); // Delay de 500ms para evitar muitas requisições
        });
    }
    
    if (categoriaCheckboxes.length > 0) {
        categoriaCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', aplicarFiltros);
        });
    }
    
    if (precoMinInput) {
        precoMinInput.addEventListener('change', aplicarFiltros);
    }
    
    if (precoMaxInput) {
        precoMaxInput.addEventListener('change', aplicarFiltros);
    }
    
    if (ordenacaoSelect) {
        ordenacaoSelect.addEventListener('change', aplicarFiltros);
    }
    
    // Botão limpar filtros
    const limparBtn = document.getElementById('limpar-filtros');
    if (limparBtn) {
        limparBtn.addEventListener('click', function() {
            // Limpar todos os campos
            if (buscaInput) buscaInput.value = '';
            categoriaCheckboxes.forEach(cb => cb.checked = false);
            if (precoMinInput) precoMinInput.value = '';
            if (precoMaxInput) precoMaxInput.value = '';
            if (ordenacaoSelect) ordenacaoSelect.value = 'nome';
            
            // Redirecionar sem parâmetros
            window.location.href = window.location.pathname;
        });
    }
    
    // Atualizar contador de filtros ativos
    function atualizarContadorFiltros() {
        const filterCount = document.getElementById('filterCount');
        if (filterCount) {
            let count = 0;
            
            if (buscaInput && buscaInput.value.trim()) count++;
            if (Array.from(categoriaCheckboxes).some(cb => cb.checked)) count++;
            if (precoMinInput && precoMinInput.value) count++;
            if (precoMaxInput && precoMaxInput.value) count++;
            
            filterCount.textContent = count;
        }
    }
    
    // Atualizar contador inicial
    atualizarContadorFiltros();
    
    // Atualizar contador quando os filtros mudarem
    [buscaInput, precoMinInput, precoMaxInput].forEach(input => {
        if (input) {
            input.addEventListener('input', atualizarContadorFiltros);
        }
    });
    
    categoriaCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', atualizarContadorFiltros);
    });
});
