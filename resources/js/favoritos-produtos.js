// Sistema de favoritos para produtos
document.addEventListener('DOMContentLoaded', function() {
    console.log('MX - Sistema de favoritos carregado! ❤️');
    
    // Event listeners para botões de favorito
    document.addEventListener('click', function(e) {
        if (e.target.closest('.favorito-btn')) {
            e.preventDefault();
            const button = e.target.closest('.favorito-btn');
            const produtoId = button.getAttribute('data-produto-id');
            const icon = button.querySelector('i');
            
            // Verificar se está nos favoritos
            const isFavorito = icon.classList.contains('fas');
            
            if (isFavorito) {
                // Remover dos favoritos
                removerFavorito(produtoId, button, icon);
            } else {
                // Adicionar aos favoritos
                adicionarFavorito(produtoId, button, icon);
            }
        }
    });
    
    // Função para adicionar aos favoritos
    function adicionarFavorito(produtoId, button, icon) {
        fetch('/favoritos/adicionar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                produto_id: produtoId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Atualizar visual
                icon.classList.remove('far');
                icon.classList.add('fas');
                button.classList.add('bg-red-500', 'text-white');
                button.classList.remove('bg-white/90', 'text-gray-700');
                
                // Mostrar notificação
                mostrarNotificacao(data.message, 'success');
            } else {
                mostrarNotificacao(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erro ao adicionar favorito:', error);
            mostrarNotificacao('Erro ao adicionar aos favoritos. Tente novamente.', 'error');
        });
    }
    
    // Função para remover dos favoritos
    function removerFavorito(produtoId, button, icon) {
        fetch('/favoritos/remover', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                produto_id: produtoId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Atualizar visual
                icon.classList.remove('fas');
                icon.classList.add('far');
                button.classList.remove('bg-red-500', 'text-white');
                button.classList.add('bg-white/90', 'text-gray-700');
                
                // Mostrar notificação
                mostrarNotificacao(data.message, 'info');
            } else {
                mostrarNotificacao(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erro ao remover favorito:', error);
            mostrarNotificacao('Erro ao remover dos favoritos. Tente novamente.', 'error');
        });
    }
    
    // Função para mostrar notificações
    function mostrarNotificacao(mensagem, tipo) {
        // Remover notificação anterior se existir
        const notificacaoAnterior = document.querySelector('.favorito-notification');
        if (notificacaoAnterior) {
            notificacaoAnterior.remove();
        }
        
        // Criar nova notificação
        const notificacao = document.createElement('div');
        notificacao.className = `favorito-notification fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white font-semibold transition-all duration-300 transform translate-x-full`;
        
        // Definir cor baseada no tipo
        switch(tipo) {
            case 'success':
                notificacao.classList.add('bg-green-500');
                break;
            case 'error':
                notificacao.classList.add('bg-red-500');
                break;
            case 'info':
                notificacao.classList.add('bg-blue-500');
                break;
            default:
                notificacao.classList.add('bg-gray-500');
        }
        
        notificacao.textContent = mensagem;
        document.body.appendChild(notificacao);
        
        // Animar entrada
        setTimeout(() => {
            notificacao.classList.remove('translate-x-full');
        }, 100);
        
        // Remover após 3 segundos
        setTimeout(() => {
            notificacao.classList.add('translate-x-full');
            setTimeout(() => {
                if (notificacao.parentNode) {
                    notificacao.remove();
                }
            }, 300);
        }, 3000);
    }
});
