// Sistema de favoritos para produtos
document.addEventListener('DOMContentLoaded', function() {
    
    // Verificar e preservar estado inicial dos favoritos
    const favoritoButtons = document.querySelectorAll('.favorito-btn');
    favoritoButtons.forEach(button => {
        const icon = button.querySelector('i');
        const produtoId = button.getAttribute('data-produto-id');
        const isFavorito = icon.classList.contains('fas');
        
        
        // Garantir que o estado visual está correto
        if (isFavorito) {
            button.classList.add('bg-black', 'text-white');
            button.classList.remove('border-black');
        } else {
            button.classList.remove('bg-black', 'text-white');
            button.classList.add('border-black');
        }
    });
    
    // Event listeners para botões de favorito
    document.addEventListener('click', function(e) {
        if (e.target.closest('.favorito-btn')) {
            e.preventDefault();
            const button = e.target.closest('.favorito-btn');
            const produtoId = button.getAttribute('data-produto-id');
            const icon = button.querySelector('i');
            
            // Verificar se está nos favoritos - verificar tanto o ícone quanto o botão
            const isFavorito = icon.classList.contains('fas') || button.classList.contains('bg-black');
            
            
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
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                produto_id: produtoId
            })
        })
        .then(response => {
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Atualizar visual
                icon.classList.remove('far');
                icon.classList.add('fas');
                button.classList.add('bg-black', 'text-white');
                button.classList.remove('border-black');
                
                // Mostrar notificação
                mostrarNotificacao(data.message, 'success');
            } else {
                // Se não está logado, redirecionar para login
                if (data.message && (data.message.includes('logado') || data.message.includes('autenticado'))) {
                    window.location.href = '/login';
                } else {
                    mostrarNotificacao(data.message, 'error');
                }
            }
        })
        .catch(error => {
            // Se erro de autenticação, redirecionar para login
            if (error.message && error.message.includes('autenticado')) {
                window.location.href = '/login';
            } else {
                mostrarNotificacao('Erro ao adicionar aos favoritos. Tente novamente.', 'error');
            }
        });
    }
    
    // Função para remover dos favoritos
    function removerFavorito(produtoId, button, icon) {
        fetch('/favoritos/remover', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                produto_id: produtoId
            })
        })
        .then(response => {
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Atualizar visual
                icon.classList.remove('fas');
                icon.classList.add('far');
                button.classList.remove('bg-black', 'text-white');
                button.classList.add('border-black');
                
                // Mostrar notificação
                mostrarNotificacao(data.message, 'info');
            } else {
                // Se não está logado, redirecionar para login
                if (data.message && (data.message.includes('logado') || data.message.includes('autenticado'))) {
                    window.location.href = '/login';
                } else {
                    mostrarNotificacao(data.message, 'error');
                }
            }
        })
        .catch(error => {
            // Se erro de autenticação, redirecionar para login
            if (error.message && error.message.includes('autenticado')) {
                window.location.href = '/login';
            } else {
                mostrarNotificacao('Erro ao remover dos favoritos. Tente novamente.', 'error');
            }
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
        notificacao.className = `favorito-notification site-toast fixed z-50 pointer-events-none px-6 py-3 shadow-lg text-white font-semibold transition-all duration-300 transform translate-x-full`;
        
        // Definir cor baseada no tipo
        switch(tipo) {
            case 'success':
                notificacao.classList.add('bg-black');
                break;
            case 'error':
                notificacao.classList.add('bg-red-500');
                break;
            case 'info':
                notificacao.classList.add('bg-black');
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
