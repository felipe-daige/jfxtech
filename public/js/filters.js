/**
 * JFXTECH - Collapsible Filters & Mobile Filter Sidebar
 */
document.addEventListener('DOMContentLoaded', function () {
    // Collapsible filter sections
    document.querySelectorAll('[data-filter-toggle]').forEach(function (header) {
        header.addEventListener('click', function () {
            var content = this.nextElementSibling;
            var chevron = this.querySelector('.filter-chevron');
            if (content.classList.contains('open')) {
                content.classList.remove('open');
                if (chevron) chevron.classList.remove('rotated');
            } else {
                content.classList.add('open');
                if (chevron) chevron.classList.add('rotated');
            }
        });
    });

    // Mobile filter toggle
    var mobileToggle  = document.getElementById('mobileFilterToggle');
    var mobileClose   = document.getElementById('mobileFilterClose');
    var filterSidebar = document.querySelector('.filter-sidebar');
    var filterOverlay = document.getElementById('filter-overlay');

    function openFilterDrawer() {
        filterSidebar.classList.add('open');
        if (filterOverlay) filterOverlay.classList.add('active');
    }

    function closeFilterDrawer() {
        filterSidebar.classList.remove('open');
        if (filterOverlay) filterOverlay.classList.remove('active');
    }

    if (mobileToggle && filterSidebar) mobileToggle.addEventListener('click', openFilterDrawer);
    if (mobileClose  && filterSidebar) mobileClose.addEventListener('click', closeFilterDrawer);
    if (filterOverlay)                 filterOverlay.addEventListener('click', closeFilterDrawer);

    // Auto-submit filters on change
    var form = document.getElementById('filtros-form');
    if (form) {
        form.querySelectorAll('input[type="checkbox"], select').forEach(function (el) {
            el.addEventListener('change', function () {
                form.submit();
            });
        });

        // Debounced submit for text/number inputs
        var debounceTimer;
        form.querySelectorAll('input[type="text"], input[type="number"]').forEach(function (el) {
            el.addEventListener('keyup', function (e) {
                if (e.key === 'Enter') {
                    form.submit();
                    return;
                }
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () {
                    form.submit();
                }, 800);
            });
        });
    }
});
