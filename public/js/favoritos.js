// JavaScript para página de Favoritos
document.addEventListener('DOMContentLoaded', function() {
    
    // Filtro de categorias
    const categoryFilter = document.querySelector('select');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            const selectedCategory = this.value;
            filterFavoritos(selectedCategory);
        });
    }

    // Função para filtrar favoritos por categoria
    function filterFavoritos(category) {
        const produtos = document.querySelectorAll('.product-card');

        produtos.forEach(produto => {
            if (category === '') {
                produto.style.display = 'block';
            } else {
                const categoriaElement = produto.querySelector('.text-xs.text-gray-500.uppercase');
                if (categoriaElement) {
                    const produtoCategoria = categoriaElement.textContent.toLowerCase();
                    if (produtoCategoria.includes(category)) {
                        produto.style.display = 'block';
                    } else {
                        produto.style.display = 'none';
                    }
                }
            }
        });
    }

    // Botões de ação dos produtos
    document.addEventListener('click', function(e) {
        // Adicionar ao carrinho
        if (e.target.closest('button') && e.target.textContent.includes('Adicionar')) {
            e.preventDefault();
            adicionarAoCarrinho(e.target.closest('.product-card'));
        }

        // Ver detalhes do produto
        if (e.target.closest('[data-action="view"]') || e.target.closest('button') && e.target.textContent.includes('Ver')) {
            e.preventDefault();
            verDetalhes(e.target.closest('.product-card'));
        }

        // Remover dos favoritos
        if (e.target.closest('[data-action="remove"]') || e.target.closest('button') && e.target.textContent.includes('Remover')) {
            e.preventDefault();
            removerDosFavoritos(e.target.closest('.product-card'));
        }

        // Limpar todos os favoritos
        if (e.target.closest('button') && e.target.textContent.includes('Limpar Todos')) {
            e.preventDefault();
            limparTodosFavoritos();
        }

        // Toggle favorito (coração)
        if (e.target.closest('[data-action="toggle-fav"]') || e.target.closest('.favorito-btn')) {
            e.preventDefault();
            toggleFavorito(e.target.closest('[data-action="toggle-fav"]') || e.target.closest('.favorito-btn'));
        }
    });

    // Função para adicionar produto ao carrinho
    function adicionarAoCarrinho(produtoElement) {
        const nomeProduto = produtoElement.querySelector('h3').textContent;
        
        // Mostrar confirmação
        if (confirm(`Deseja adicionar "${nomeProduto}" ao carrinho?`)) {
            // TODO: Implementar lógica para adicionar ao carrinho
            showNotification('Produto adicionado ao carrinho!', 'success');
        }
    }

    // Função para ver detalhes do produto
    function verDetalhes(produtoElement) {
        const nomeProduto = produtoElement.querySelector('h3').textContent;
        const categoria = produtoElement.querySelector('.text-xs.text-gray-500.uppercase').textContent;
        const preco = produtoElement.querySelector('.font-mono.font-bold').textContent;
        
        // Criar modal de detalhes
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black/30 backdrop-blur-sm z-50 flex items-center justify-center p-4';
        modal.innerHTML = `
            <div class="bg-white border border-gray-200 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 class="text-xl font-black text-gray-800">Detalhes do Produto</h3>
                    <button class="text-gray-400 hover:text-gray-600 transition-colors close-modal">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6">
                    <div class="text-center">
                        <div class="w-24 h-24 bg-gray-100 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                        </div>
                        <h4 class="text-2xl font-black text-gray-800 mb-2">${nomeProduto}</h4>
                        <p class="text-gray-600 mb-4">${categoria}</p>
                        <p class="text-2xl font-mono font-bold mb-6">${preco}</p>

                        <div class="bg-gray-50 p-4">
                            <p class="text-sm text-gray-600">Esta funcionalidade será implementada quando o sistema de produtos estiver completo.</p>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Fechar modal
        modal.querySelector('.close-modal').addEventListener('click', () => {
            document.body.removeChild(modal);
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                document.body.removeChild(modal);
            }
        });
    }

    // Função para remover dos favoritos
    function removerDosFavoritos(produtoElement) {
        const nomeProduto = produtoElement.querySelector('h3').textContent;
        
        // Mostrar confirmação
        if (confirm(`Deseja remover "${nomeProduto}" dos favoritos?`)) {
            // Animação de remoção
            produtoElement.style.transition = 'all 0.3s ease';
            produtoElement.style.transform = 'scale(0.8)';
            produtoElement.style.opacity = '0';
            
            setTimeout(() => {
                produtoElement.remove();
                checkEmptyState();
                showNotification('Produto removido dos favoritos!', 'success');
            }, 300);
        }
    }

    // Função para limpar todos os favoritos
    function limparTodosFavoritos() {
        if (confirm('Deseja remover todos os produtos dos favoritos?')) {
            const produtos = document.querySelectorAll('.product-card');

            produtos.forEach((produto, index) => {
                setTimeout(() => {
                    produto.style.transition = 'all 0.3s ease';
                    produto.style.transform = 'scale(0.8)';
                    produto.style.opacity = '0';
                    
                    setTimeout(() => {
                        produto.remove();
                        if (index === produtos.length - 1) {
                            checkEmptyState();
                            showNotification('Todos os favoritos foram removidos!', 'success');
                        }
                    }, 300);
                }, index * 100);
            });
        }
    }

    // Função para toggle do favorito
    function toggleFavorito(button) {
        const isFavorited = button.dataset.favorited === 'true';

        if (isFavorited) {
            // Remover dos favoritos
            button.dataset.favorited = 'false';
            button.classList.remove('bg-black', 'text-white');
            button.classList.add('bg-white', 'text-black');
            showNotification('Removido dos favoritos!', 'info');
        } else {
            // Adicionar aos favoritos
            button.dataset.favorited = 'true';
            button.classList.remove('bg-white', 'text-black');
            button.classList.add('bg-black', 'text-white');
            showNotification('Adicionado aos favoritos!', 'success');
        }
    }

    // Função para verificar estado vazio
    function checkEmptyState() {
        const produtos = document.querySelectorAll('.product-card');
        const emptyState = document.getElementById('empty-state');
        
        if (produtos.length === 0 && emptyState) {
            emptyState.classList.remove('hidden');
        } else if (emptyState) {
            emptyState.classList.add('hidden');
        }
    }

    // Função para mostrar notificações
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `site-toast fixed z-50 pointer-events-none px-6 py-3 shadow-lg transition-all duration-300 transform translate-x-full`;

        // Cores baseadas no tipo
        const colors = {
            success: 'bg-black text-white',
            error: 'bg-red-500 text-white',
            info: 'bg-black text-white',
            warning: 'bg-yellow-500 text-white'
        };

        notification.className += ` ${colors[type] || colors.info}`;
        const icons = { success: '\u2713', error: '\u2715', info: '!', warning: '!' };
        notification.innerHTML = `
            <div class="flex items-center space-x-2">
                <span>${icons[type] || icons.info}</span>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Animar entrada
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);
        
        // Remover após 3 segundos
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }

    // Verificar estado vazio ao carregar
    checkEmptyState();

});
