// JavaScript para página de Meus Pedidos
document.addEventListener('DOMContentLoaded', function() {
    
    // Filtro de status dos pedidos
    const statusFilter = document.querySelector('select');
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            const selectedStatus = this.value;
            filterPedidos(selectedStatus);
        });
    }

    // Função para filtrar pedidos
    function filterPedidos(status) {
        const pedidos = document.querySelectorAll('.bg-white.border');
        
        pedidos.forEach(pedido => {
            if (status === '') {
                pedido.style.display = 'block';
            } else {
                const statusElement = pedido.querySelector('.inline-flex.items-center.px-3.py-1.rounded-full');
                if (statusElement) {
                    const pedidoStatus = statusElement.textContent.toLowerCase();
                    if (pedidoStatus.includes(status)) {
                        pedido.style.display = 'block';
                    } else {
                        pedido.style.display = 'none';
                    }
                }
            }
        });
    }

    // Botões de ação dos pedidos
    document.addEventListener('click', function(e) {
        // Ver detalhes do pedido
        if (e.target.closest('button') && e.target.textContent.includes('Ver Detalhes')) {
            e.preventDefault();
            showPedidoDetalhes(e.target.closest('.bg-white.border'));
        }

        // Rastrear pedido
        if (e.target.closest('button') && e.target.textContent.includes('Rastrear')) {
            e.preventDefault();
            showRastreamento(e.target.closest('.bg-white.border'));
        }

        // Avaliar pedido
        if (e.target.closest('button') && e.target.textContent.includes('Avaliar')) {
            e.preventDefault();
            showAvaliacao(e.target.closest('.bg-white.border'));
        }

        // Comprar novamente
        if (e.target.closest('button') && e.target.textContent.includes('Comprar Novamente')) {
            e.preventDefault();
            comprarNovamente(e.target.closest('.bg-white.border'));
        }

        // Baixar nota fiscal
        if (e.target.closest('button') && e.target.textContent.includes('Nota Fiscal')) {
            e.preventDefault();
            baixarNotaFiscal(e.target.closest('.bg-white.border'));
        }
    });

    // Função para mostrar detalhes do pedido
    function showPedidoDetalhes(pedidoElement) {
        const pedidoId = pedidoElement.querySelector('h3').textContent;
        
        // Criar modal de detalhes
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black/30 backdrop-blur-sm z-50 flex items-center justify-center p-4';
        modal.innerHTML = `
            <div class="bg-white border border-gray-200 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 class="text-xl font-black text-gray-800">Detalhes do Pedido</h3>
                    <button class="text-gray-400 hover:text-gray-600 transition-colors close-modal">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-[var(--color-lab-bg)] flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                        </div>
                        <h4 class="text-2xl font-black text-gray-800 mb-2">${pedidoId}</h4>
                        <p class="text-gray-600 mb-6">Aqui você pode ver todos os detalhes do seu pedido</p>

                        <div class="bg-gray-50 p-4">
                            <p class="text-sm text-gray-600">Esta funcionalidade será implementada quando o sistema de pedidos estiver completo.</p>
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

    // Função para mostrar rastreamento
    function showRastreamento(pedidoElement) {
        const pedidoId = pedidoElement.querySelector('h3').textContent;
        
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black/30 backdrop-blur-sm z-50 flex items-center justify-center p-4';
        modal.innerHTML = `
            <div class="bg-white border border-gray-200 w-full max-w-md">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 class="text-xl font-black text-gray-800">Rastrear Pedido</h3>
                    <button class="text-gray-400 hover:text-gray-600 transition-colors close-modal">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-[var(--color-lab-bg)] flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                        </div>
                        <h4 class="text-lg font-bold text-gray-800 mb-2">${pedidoId}</h4>
                        <p class="text-gray-600 mb-6">Acompanhe o status do seu pedido</p>

                        <div class="bg-gray-50 p-4">
                            <p class="text-sm text-gray-600">Sistema de rastreamento será implementado com a integração de transportadoras.</p>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        modal.querySelector('.close-modal').addEventListener('click', () => {
            document.body.removeChild(modal);
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                document.body.removeChild(modal);
            }
        });
    }

    // Função para mostrar avaliação
    function showAvaliacao(pedidoElement) {
        const pedidoId = pedidoElement.querySelector('h3').textContent;
        
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black/30 backdrop-blur-sm z-50 flex items-center justify-center p-4';
        modal.innerHTML = `
            <div class="bg-white border border-gray-200 w-full max-w-md">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 class="text-xl font-black text-gray-800">Avaliar Pedido</h3>
                    <button class="text-gray-400 hover:text-gray-600 transition-colors close-modal">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-[var(--color-lab-bg)] flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        </div>
                        <h4 class="text-lg font-bold text-gray-800 mb-2">${pedidoId}</h4>
                        <p class="text-gray-600 mb-6">Como foi sua experiência?</p>

                        <div class="bg-gray-50 p-4">
                            <p class="text-sm text-gray-600">Sistema de avaliações será implementado em breve.</p>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        modal.querySelector('.close-modal').addEventListener('click', () => {
            document.body.removeChild(modal);
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                document.body.removeChild(modal);
            }
        });
    }

    // Função para comprar novamente
    function comprarNovamente(pedidoElement) {
        const pedidoId = pedidoElement.querySelector('h3').textContent;
        
        // Mostrar confirmação
        if (confirm(`Deseja adicionar os produtos do ${pedidoId} ao carrinho novamente?`)) {
            // TODO: Implementar lógica para adicionar produtos ao carrinho
            alert('Funcionalidade será implementada quando o sistema de carrinho estiver completo.');
        }
    }

    // Função para baixar nota fiscal
    function baixarNotaFiscal(pedidoElement) {
        const pedidoId = pedidoElement.querySelector('h3').textContent;
        
        // TODO: Implementar download da nota fiscal
        alert(`Download da nota fiscal do ${pedidoId} será implementado com o sistema de pagamentos.`);
    }

    // Verificar se há pedidos para mostrar estado vazio
    function checkEmptyState() {
        const pedidos = document.querySelectorAll('.bg-white.border');
        const emptyState = document.getElementById('empty-state');
        
        if (pedidos.length === 0 && emptyState) {
            emptyState.classList.remove('hidden');
        } else if (emptyState) {
            emptyState.classList.add('hidden');
        }
    }

    // Verificar estado vazio ao carregar
    checkEmptyState();
});
