// Profile Edit JavaScript - JFXTECH
document.addEventListener('DOMContentLoaded', function() {
    
    // Elementos do modal
    const editProfileBtn = document.querySelector('[data-edit-profile]');
    const editModal = document.getElementById('edit-profile-modal');
    const closeModalBtn = document.getElementById('close-edit-modal');
    const cancelModalBtn = document.getElementById('cancel-edit-modal');
    const editForm = document.getElementById('edit-profile-form');
    const submitBtn = document.getElementById('submit-edit-profile');
    
    // Função para abrir modal
    function openEditModal() {
        if (editModal) {
            editModal.classList.remove('opacity-0', 'invisible');
            document.body.classList.add('overflow-hidden');
            
            // Focus no primeiro campo
            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                setTimeout(() => phoneInput.focus(), 100);
            }
        }
    }
    
    // Função para fechar modal
    function closeEditModal() {
        if (editModal) {
            editModal.classList.add('opacity-0', 'invisible');
            document.body.classList.remove('overflow-hidden');
            
            // Limpar formulário
            if (editForm) {
                editForm.reset();
                clearErrors();
            }
        }
    }
    
    // Função para limpar erros
    function clearErrors() {
        const errorElements = document.querySelectorAll('.error-message');
        errorElements.forEach(element => element.remove());
        
        const inputElements = document.querySelectorAll('.border-red-500');
        inputElements.forEach(element => {
            element.classList.remove('border-red-500');
            element.classList.add('border-gray-300');
        });
    }
    
    // Função para mostrar erros
    function showErrors(errors) {
        clearErrors();
        
        Object.keys(errors).forEach(field => {
            const input = document.getElementById(field);
            if (input) {
                input.classList.remove('border-gray-300');
                input.classList.add('border-red-500');
                
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message text-red-500 text-sm mt-1';
                errorDiv.textContent = errors[field][0];
                
                input.parentNode.appendChild(errorDiv);
            }
        });
    }
    
    // Função para mostrar sucesso
    function showSuccess(message) {
        // Remover mensagens anteriores
        const existingAlerts = document.querySelectorAll('.alert-message');
        existingAlerts.forEach(alert => alert.remove());
        
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert-message bg-[var(--color-lab-bg)] border border-[var(--color-lab-border)] text-black px-4 py-3 rounded mb-4';
        alertDiv.innerHTML = `
            <div class="flex items-center">
                <svg class="w-4 h-4 mr-2 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                <span>${message}</span>
            </div>
        `;
        
        // Inserir no topo do modal
        const modalBody = editModal.querySelector('.modal-body');
        if (modalBody) {
            modalBody.insertBefore(alertDiv, modalBody.firstChild);
        }
        
        // Auto-remover após 3 segundos
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 3000);
    }
    
    // Função para validar formulário (simplificada)
    function validateForm() {
        return true; // Deixar o Laravel fazer a validação
    }
    
    // Event listeners
    if (editProfileBtn) {
        editProfileBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openEditModal();
        });
    }
    
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', closeEditModal);
    }
    
    if (cancelModalBtn) {
        cancelModalBtn.addEventListener('click', closeEditModal);
    }
    
    // Fechar modal ao clicar no overlay
    if (editModal) {
        editModal.addEventListener('click', function(e) {
            if (e.target === editModal) {
                closeEditModal();
            }
        });
    }
    
    // Fechar modal com ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && editModal && !editModal.classList.contains('opacity-0')) {
            closeEditModal();
        }
    });
    
    // Aplicar máscara jMask no telefone
    const phoneInput = document.getElementById('phone');
    if (phoneInput && typeof $ !== 'undefined' && $.fn.mask) {
        $(phoneInput).mask('(00) 00000-0000', {
            placeholder: '(00) 00000-0000',
            clearIfNotMatch: true
        });
    }
    
    // Submit do formulário
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Limpar erros anteriores
            clearErrors();
            
            // Desabilitar botão de submit
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'SALVANDO...';
            }
            
            // Coletar dados do formulário
            const formData = new FormData(editForm);
            
            // Enviar requisição
            fetch(editForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    showSuccess(data.message || 'Perfil atualizado com sucesso!');
                    
                    // Atualizar dados na página se necessário
                    if (data.user) {
                        // Atualizar telefone se foi alterado
                        const phoneDisplay = document.querySelector('[data-phone-display]');
                        if (phoneDisplay && data.user.phone) {
                            phoneDisplay.textContent = data.user.phone;
                        }
                    }
                    
                    // Fechar modal após sucesso
                    setTimeout(() => {
                        closeEditModal();
                    }, 1500);
                } else {
                    if (data.errors) {
                        showErrors(data.errors);
                    } else {
                        showErrors({ general: [data.message || 'Erro ao atualizar perfil'] });
                    }
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showErrors({ general: ['Erro de conexão. Tente novamente.'] });
            })
            .finally(() => {
                // Reabilitar botão de submit
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'SALVAR ALTERA\u00c7\u00d5ES';
                }
            });
        });
    }
});
