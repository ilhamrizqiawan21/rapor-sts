// Sidebar dropdowns & mobile drawer toggle
function initSidebar() {
    // Dropdown accordion toggles
    document.querySelectorAll('.nav-toggle').forEach(function (toggle) {
        if (toggle.dataset.dropdownBound) return;
        toggle.dataset.dropdownBound = 'true';

        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            const group = toggle.closest('.nav-group');
            if (group) {
                const isOpen = group.classList.toggle('open');
                toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            }
        });
    });

    // Close mobile sidebar when clicking outside
    if (!document.body.dataset.sidebarBackdropBound) {
        document.body.dataset.sidebarBackdropBound = 'true';
        document.addEventListener('click', function (e) {
            const sidebar = document.getElementById('appSidebar');
            const ham = e.target.closest('.ham');
            if (!sidebar || ham) return;
            if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && window.innerWidth <= 768) {
                sidebar.classList.remove('open');
            }
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSidebar);
} else {
    initSidebar();
}
