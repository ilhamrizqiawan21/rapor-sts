// Sidebar dropdowns & mobile drawer toggle
function toggleSidebar() {
    const sidebar = document.getElementById('appSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (!sidebar) return;

    if (window.innerWidth <= 768) {
        const isOpen = sidebar.classList.toggle('open');
        if (backdrop) {
            backdrop.classList.toggle('active', isOpen);
        }
    } else {
        const isCollapsed = sidebar.classList.toggle('collapsed');
        try {
            localStorage.setItem('sidebar_collapsed', isCollapsed ? '1' : '0');
        } catch (e) {}
    }
}

window.toggleSidebar = toggleSidebar;

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

    // Close mobile sidebar when clicking backdrop or outside
    if (!document.body.dataset.sidebarBackdropBound) {
        document.body.dataset.sidebarBackdropBound = 'true';
        document.addEventListener('click', function (e) {
            const sidebar = document.getElementById('appSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            const ham = e.target.closest('.ham');
            if (!sidebar || ham) return;

            if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && window.innerWidth <= 768) {
                sidebar.classList.remove('open');
                if (backdrop) backdrop.classList.remove('active');
            }
        });
    }

    // Restore desktop collapsed state
    if (window.innerWidth > 768) {
        try {
            if (localStorage.getItem('sidebar_collapsed') === '1') {
                document.getElementById('appSidebar')?.classList.add('collapsed');
            }
        } catch (e) {}
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSidebar);
} else {
    initSidebar();
}
