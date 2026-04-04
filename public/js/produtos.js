/**
 * Products Page JavaScript - JFXTECH
 */
document.addEventListener('DOMContentLoaded', function() {

    // Load cart counter
    if (window.updateCartCounter) window.updateCartCounter();

    // Notification system (local fallback if not available from cart.js)
    if (!window.showNotification) {
        window.showNotification = function(message, type) {
            type = type || 'info';
            document.querySelectorAll('.notification').forEach(function(n) { n.remove(); });
            var notification = document.createElement('div');
            notification.className = 'notification fixed top-4 right-4 z-[60] px-6 py-3 shadow-lg text-white text-sm font-mono font-bold uppercase tracking-wider transition-all duration-300 transform translate-x-full';
            var colors = { success: 'bg-black', error: 'bg-red-600', info: 'bg-gray-800', warning: 'bg-yellow-600' };
            notification.classList.add(colors[type] || colors.info);
            notification.textContent = message;
            document.body.appendChild(notification);
            setTimeout(function() { notification.classList.remove('translate-x-full'); }, 100);
            setTimeout(function() {
                notification.classList.add('translate-x-full');
                setTimeout(function() { if (notification.parentNode) notification.remove(); }, 300);
            }, 3000);
        };
    }
});
