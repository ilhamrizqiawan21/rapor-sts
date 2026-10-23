@props([
    'active' => 'dashboard',
])

@php
    $activeKey = $active;
    if ($activeKey === 'dashboard' && !request()->routeIs('kepala.dashboard')) {
        if (request()->routeIs('kepala.leger.*')) $activeKey = 'leger';
        elseif (request()->routeIs('kepala.kinerja.*')) $activeKey = 'kinerja';
        elseif (request()->routeIs('kepala.rapor.*')) $activeKey = 'rapor';
        elseif (request()->routeIs('kepala.unduh.*')) $activeKey = 'unduh';
    }
@endphp

<nav class="nav" aria-label="Menu kepala madrasah">
    <a href="{{ route('kepala.dashboard') }}" class="{{ $activeKey === 'dashboard' ? 'on' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 13h8V3H3zM13 21h8V11h-8zM3 21h8v-6H3zM13 3v6h8V3z"/></svg>
        <span>Dashboard</span>
    </a>

    <div class="sec">Pemantauan</div>

    <a href="{{ route('kepala.dashboard') }}#progres" class="{{ $activeKey === 'progres' ? 'on' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg>
        <span>Progres Nilai</span>
    </a>

    <a href="{{ route('kepala.leger.index') }}" class="{{ $activeKey === 'leger' ? 'on' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 4h18v16H3zM3 10h18M9 4v16"/></svg>
        <span>Rekap Nilai (Leger)</span>
    </a>

    <a href="{{ route('kepala.kinerja.index') }}" class="{{ $activeKey === 'kinerja' ? 'on' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
        <span>Kinerja Input Guru</span>
    </a>

    <div class="sec">Laporan</div>

    <a href="{{ route('kepala.rapor.index') }}" class="{{ $activeKey === 'rapor' ? 'on' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 2h9l5 5v15H6zM14 2v6h6"/></svg>
        <span>Lihat Rapor STS</span>
    </a>

    <a href="{{ route('kepala.unduh.excel') }}" class="{{ $activeKey === 'unduh' ? 'on' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5M4 21h16"/></svg>
        <span>Unduh Rekap Excel</span>
    </a>

    <div class="sec">Akun</div>
    <a href="{{ route('akun.edit') }}" class="{{ $activeKey === 'akun' ? 'on' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
        <span>Profil Saya</span>
    </a>

    <form method="POST" action="{{ route('logout') }}" id="logout-form-kepala" style="display: none;">
        @csrf
    </form>
    <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form-kepala').submit();">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 3h5v18h-5M10 17l-5-5 5-5M5 12h11"/></svg>
        <span>Logout</span>
    </a>
</nav>
