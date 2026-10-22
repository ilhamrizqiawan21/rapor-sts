@props([
    'active' => 'dashboard',
])

<nav class="nav" aria-label="Menu operator">
    <a href="#" class="{{ $active === 'dashboard' ? 'on' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 13h8V3H3zM13 21h8V11h-8zM3 21h8v-6H3zM13 3v6h8V3z"/></svg>
        <span>Dashboard</span>
    </a>

    <a href="#" class="{{ $active === 'profil' ? 'on' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 21V5l8-3 8 3v16M9 21v-6h6v6"/></svg>
        <span>Profil Madrasah</span>
    </a>

    <div class="sec">Pengaturan</div>

    <a href="#" class="{{ $active === 'lembaga' ? 'on' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 0 0-.1-1l2-1.6-2-3.4-2.4 1a7 7 0 0 0-1.7-1L14.5 3h-5l-.3 2.6a7 7 0 0 0-1.7 1l-2.4-1-2 3.4 2 1.6a7 7 0 0 0 0 2l-2 1.6 2 3.4 2.4-1a7 7 0 0 0 1.7 1l.3 2.4h5l.3-2.6a7 7 0 0 0 1.7-1l2.4 1 2-3.4-2-1.6c.1-.3.1-.6.1-1z"/></svg>
        <span>Lembaga</span>
        <svg class="chev" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
    </a>

    <a href="#" class="{{ $active === 'guru' ? 'on' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
        <span>Guru</span>
        <svg class="chev" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
    </a>

    <a href="#" class="{{ $active === 'siswa' ? 'on' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="9" cy="8" r="3.5"/><circle cx="17" cy="9" r="2.5"/><path d="M2 20c0-3.5 3-5 7-5s7 1.5 7 5M16 14c3 0 6 1.2 6 4"/></svg>
        <span>Siswa</span>
        <svg class="chev" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
    </a>

    <a href="#" class="{{ $active === 'rapor' ? 'on' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 2h9l5 5v15H6zM14 2v6h6"/></svg>
        <span>Rapor</span>
        <svg class="chev" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
    </a>

    <div class="sec">Administrasi</div>

    <a href="#" class="{{ $active === 'admin' ? 'on' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M2 21c0-4 3-6 7-6M16 11l2 2 4-4"/></svg>
        <span>Admin Aplikasi</span>
    </a>

    <a href="#" class="{{ $active === 'backup' ? 'on' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 4h16v16H4zM8 4v6h8V4"/></svg>
        <span>Backup Restore</span>
    </a>

    <div class="sec">Akun</div>

    <a href="#">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 3h5v18h-5M10 17l-5-5 5-5M5 12h11"/></svg>
        <span>Logout</span>
    </a>
</nav>
