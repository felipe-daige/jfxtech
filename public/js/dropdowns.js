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
            
            // Fechar outros dropdowns primeiro
            closeAllDropdowns();
            
            // Toggle do menu mobile (sem delay para ser mais fluido)
            const isVisible = !mobileMenu.classList.contains('opacity-0');
            
            if (isVisible) {
                mobileMenu.classList.add('opacity-0', 'invisible');
            } else {
                mobileMenu.classList.remove('opacity-0', 'invisible');
            }
        });
    }

    // Fechar dropdowns ao clicar fora
    document.addEventListener('click', function(e) {
        // Verificar se o clique foi fora dos dropdowns
        const isInsideDropdown = e.target.closest('.user-dropdown') || 
                                e.target.closest('#mobile-menu-toggle') ||
                                e.target.closest('#mobile-menu');
        
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

    // Prevenir fechamento ao clicar dentro dos dropdowns
    const dropdownMenus = document.querySelectorAll('.user-dropdown-menu, #mobile-menu');
    dropdownMenus.forEach(menu => {
        menu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
});