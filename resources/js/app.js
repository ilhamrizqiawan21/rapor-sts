const sidebar = document.getElementById('appSidebar');
const backdrop = document.getElementById('sidebarBackdrop');
const hamburger = document.getElementById('hamburgerBtn');
const mobileSidebar = window.matchMedia('(max-width: 768px)');

function sidebarIsOpen() {
    return mobileSidebar.matches
        ? sidebar.classList.contains('open')
        : !sidebar.classList.contains('collapsed');
}

function syncSidebar() {
    if (!sidebar || !hamburger) return;

    const isOpen = sidebarIsOpen();
    sidebar.inert = !isOpen;
    hamburger.setAttribute('aria-expanded', String(isOpen));
    hamburger.setAttribute('aria-label', isOpen ? 'Tutup menu' : 'Buka menu');
    backdrop?.classList.toggle('active', mobileSidebar.matches && isOpen);
    document.body.classList.toggle('sidebar-open', mobileSidebar.matches && isOpen);
}

function toggleSidebar() {
    if (!sidebar) return;

    if (mobileSidebar.matches) {
        sidebar.classList.toggle('open');
    } else {
        sidebar.classList.toggle('collapsed');
    }
    syncSidebar();
}

window.toggleSidebar = toggleSidebar;

function initSidebar() {
    if (!sidebar || !hamburger) return;

    syncSidebar();
    hamburger.addEventListener('click', toggleSidebar);
    backdrop?.addEventListener('click', () => {
        sidebar.classList.remove('open');
        syncSidebar();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && mobileSidebar.matches && sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
            syncSidebar();
            hamburger.focus();
        }
    });
    mobileSidebar.addEventListener('change', () => {
        sidebar.classList.remove('open', 'collapsed');
        syncSidebar();
    });

    document.querySelectorAll('.nav-toggle').forEach((toggle) => {
        toggle.addEventListener('click', (event) => {
            event.preventDefault();
            const group = toggle.closest('.nav-group');
            if (!group) return;
            const isOpen = group.classList.toggle('open');
            toggle.setAttribute('aria-expanded', String(isOpen));
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSidebar);
} else {
    initSidebar();
}
