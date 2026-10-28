@props([
    'active' => 'dashboard',
])

@php
    $activeKey = $active;
    if ($activeKey === 'dashboard' && !request()->routeIs('operator.dashboard')) {
        if (request()->routeIs('operator.profil.*')) $activeKey = 'profil';
        elseif (request()->routeIs('operator.lembaga.*')) $activeKey = 'lembaga';
        elseif (request()->routeIs('operator.subjects.*')) $activeKey = 'subjects';
        elseif (request()->routeIs('operator.classrooms.*')) $activeKey = 'classrooms';
        elseif (request()->routeIs('operator.teachers.*')) $activeKey = 'teachers';
        elseif (request()->routeIs('operator.mengajar.*')) $activeKey = 'mengajar';
        elseif (request()->routeIs('operator.students.*')) $activeKey = 'students';
        elseif (request()->routeIs('operator.achievements.*')) $activeKey = 'achievements';
        elseif (request()->routeIs('operator.extracurricular.*')) $activeKey = 'extracurriculars';
        elseif (request()->routeIs('operator.kktp.*')) $activeKey = 'kktp';
        elseif (request()->routeIs('operator.print-settings.*')) $activeKey = 'print_settings';
        elseif (request()->routeIs('operator.status.*')) $activeKey = 'status_nilai';
        elseif (request()->routeIs('operator.rapor.*')) $activeKey = 'cetak_rapor';
    }

    $lembagaOpen = in_array($activeKey, ['lembaga', 'subjects', 'classrooms']);
    $guruOpen = in_array($activeKey, ['teachers', 'mengajar']);
    $siswaOpen = in_array($activeKey, ['students', 'achievements', 'extracurriculars']);
    $raporOpen = in_array($activeKey, ['kktp', 'print_settings', 'status_nilai', 'cetak_rapor']);
@endphp

<nav class="nav" aria-label="Menu operator">
    <a href="{{ route('operator.dashboard') }}" class="{{ $activeKey === 'dashboard' ? 'on' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 13h8V3H3zM13 21h8V11h-8zM3 21h8v-6H3zM13 3v6h8V3z"/></svg>
        <span>Dashboard</span>
    </a>

    <a href="{{ route('operator.profil.edit') }}" class="{{ $activeKey === 'profil' ? 'on' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 21V5l8-3 8 3v16M9 21v-6h6v6"/></svg>
        <span>Profil Madrasah</span>
    </a>

    <div class="sec">Pengaturan</div>

    {{-- Lembaga --}}
    <div class="nav-group {{ $lembagaOpen ? 'open' : '' }}">
        <button type="button" class="nav-toggle" aria-expanded="{{ $lembagaOpen ? 'true' : 'false' }}">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 0 0-.1-1l2-1.6-2-3.4-2.4 1a7 7 0 0 0-1.7-1L14.5 3h-5l-.3 2.6a7 7 0 0 0-1.7 1l-2.4-1-2 3.4 2 1.6a7 7 0 0 0 0 2l-2 1.6 2 3.4 2.4-1a7 7 0 0 0 1.7 1l.3 2.4h5l.3-2.6a7 7 0 0 0 1.7-1l2.4 1 2-3.4-2-1.6c.1-.3.1-.6.1-1z"/></svg>
            <span>Lembaga</span>
            <svg class="chev" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
        </button>
        <div class="nav-sub">
            <a href="{{ route('operator.lembaga.index') }}" class="sub {{ $activeKey === 'lembaga' ? 'on' : '' }}">
                <span>Pengaturan</span>
            </a>
            <a href="{{ route('operator.subjects.index') }}" class="sub {{ $activeKey === 'subjects' ? 'on' : '' }}">
                <span>Mata Pelajaran</span>
            </a>
            <a href="{{ route('operator.classrooms.index') }}" class="sub {{ $activeKey === 'classrooms' ? 'on' : '' }}">
                <span>Kelas</span>
            </a>
        </div>
    </div>

    {{-- Guru --}}
    <div class="nav-group {{ $guruOpen ? 'open' : '' }}">
        <button type="button" class="nav-toggle" aria-expanded="{{ $guruOpen ? 'true' : 'false' }}">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
            <span>Guru</span>
            <svg class="chev" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
        </button>
        <div class="nav-sub">
            <a href="{{ route('operator.teachers.index') }}" class="sub {{ $activeKey === 'teachers' ? 'on' : '' }}">
                <span>Data Guru</span>
            </a>
            <a href="{{ route('operator.mengajar.index') }}" class="sub {{ $activeKey === 'mengajar' ? 'on' : '' }}">
                <span>Mengajar</span>
            </a>
        </div>
    </div>

    {{-- Siswa --}}
    <div class="nav-group {{ $siswaOpen ? 'open' : '' }}">
        <button type="button" class="nav-toggle" aria-expanded="{{ $siswaOpen ? 'true' : 'false' }}">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="9" cy="8" r="3.5"/><circle cx="17" cy="9" r="2.5"/><path d="M2 20c0-3.5 3-5 7-5s7 1.5 7 5M16 14c3 0 6 1.2 6 4"/></svg>
            <span>Siswa</span>
            <svg class="chev" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
        </button>
        <div class="nav-sub">
            <a href="{{ route('operator.students.index') }}" class="sub {{ $activeKey === 'students' ? 'on' : '' }}">
                <span>Data Siswa</span>
            </a>
            <a href="{{ route('operator.achievements.index') }}" class="sub {{ $activeKey === 'achievements' ? 'on' : '' }}">
                <span>Prestasi Siswa</span>
            </a>
            <a href="{{ route('operator.extracurricular.index') }}" class="sub {{ $activeKey === 'extracurriculars' ? 'on' : '' }}">
                <span>Ekstrakurikuler</span>
            </a>
        </div>
    </div>

    {{-- Rapor --}}
    <div class="nav-group {{ $raporOpen ? 'open' : '' }}">
        <button type="button" class="nav-toggle" aria-expanded="{{ $raporOpen ? 'true' : 'false' }}">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 2h9l5 5v15H6zM14 2v6h6"/></svg>
            <span>Rapor</span>
            <svg class="chev" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
        </button>
        <div class="nav-sub">
            <a href="{{ route('operator.kktp.index') }}" class="sub {{ $activeKey === 'kktp' ? 'on' : '' }}">
                <span>Nilai KKM</span>
            </a>
            <a href="{{ route('operator.print-settings.edit') }}" class="sub {{ $activeKey === 'print_settings' ? 'on' : '' }}">
                <span>Pengaturan Cetak</span>
            </a>
            <a href="{{ route('operator.status.index') }}" class="sub {{ $activeKey === 'status_nilai' ? 'on' : '' }}">
                <span>Status Nilai</span>
            </a>
            <a href="{{ route('operator.rapor.index') }}" class="sub {{ $activeKey === 'cetak_rapor' ? 'on' : '' }}">
                <span>Cetak Rapor</span>
            </a>
        </div>
    </div>

    <div class="sec">Administrasi</div>

    <a href="{{ route('operator.backup.index') }}" class="{{ $activeKey === 'backup' ? 'on' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 4h16v16H4zM8 4v6h8V4"/></svg>
        <span>Backup & Restore</span>
    </a>

    <div class="sec">Akun</div>
    <a href="{{ route('akun.edit') }}" class="{{ $activeKey === 'akun' ? 'on' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
        <span>Profil Saya</span>
    </a>

    <form method="POST" action="{{ route('logout') }}" id="logout-form-operator" style="display: none;">
        @csrf
    </form>
    <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form-operator').submit();">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 3h5v18h-5M10 17l-5-5 5-5M5 12h11"/></svg>
        <span>Logout</span>
    </a>
</nav>
