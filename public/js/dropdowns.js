// Dropdowns JavaScript - JFXTECH
document.addEventListener('DOMContentLoaded', function() {
    
    // Função para fechar todos os dropdowns
    function closeAllDropdowns() {
        const allDropdowns = document.querySelectorAll('.user-dropdown-menu, #mobile-menu');
        allDropdowns.forEach(dropdown => {
            dropdown.classList.add('opacity-0', 'invisible');
        });
    }

    // Dropdown do usuário
    const userDropdownTrigger = document.querySelector('.user-dropdown-trigger');
    const userDropdownMenu = document.querySelector('.user-dropdown-menu');
    
    if (userDropdownTrigger && userDropdownMenu) {
        userDropdownTrigger.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Fechar outros dropdowns primeiro
            closeAllDropdowns();
            
            // Toggle do dropdown do usuário (sem delay para ser mais fluido)
            const isVisible = !userDropdownMenu.classList.contains('opacity-0');
            
            if (isVisible) {
                userDropdownMenu.classList.add('opacity-0', 'invisible');
            } else {
                userDropdownMenu.classList.remove('opacity-0', 'invisible');
            }
        });
    }


    // Menu mobile
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuToggle && mobileMenu) {
        mobileMenuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            // Capturar estado antes de fechar (closeAllDropdowns também fecha este menu)
            const isVisible = !mobileMenu.classList.contains('opacity-0');

            // closeAllDropdowns() fecha também este menu; se estava aberto, agora está fechado
            closeAllDropdowns();

            // Toggle do menu mobile (sem delay para ser mais fluido)
            if (!isVisible) {
                mobileMenu.classList.remove('opacity-0', 'invisible');
            }
            // Sem branch de fechar: closeAllDropdowns() acima já fecha o menu quando estava aberto
        });
    }

    // Fechar dropdowns ao clicar fora (ou dentro do menu mobile)
    document.addEventListener('click', function(e) {
        const isInsideDropdown = e.target.closest('.user-dropdown') ||
                                e.target.closest('#mobile-menu-toggle');

        if (!isInsideDropdown) {
            closeAllDropdowns();
        }
    });

    // Fechar dropdowns ao pressionar ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAllDropdowns();
        }
    });

    // Prevenir fechamento ao clicar dentro do dropdown do usuário
    const userDropdownMenuEl = document.querySelector('.user-dropdown-menu');
    if (userDropdownMenuEl) {
        userDropdownMenuEl.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
});
