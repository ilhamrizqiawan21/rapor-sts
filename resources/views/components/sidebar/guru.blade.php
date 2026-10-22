@props([
    'active' => 'kelas',
    'teachingClasses' => [],
    'homeroomClass' => null,
])

<nav class="nav" aria-label="Menu guru">
    <a href="#" class="{{ $active === 'dashboard' ? 'on' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 13h8V3H3zM13 21h8V11h-8zM3 21h8v-6H3zM13 3v6h8V3z"/></svg>
        <span>Dashboard</span>
    </a>

    {{-- Mapel yang diampu (default dummy bila belum ada data dinamis) --}}
    @if(empty($teachingClasses))
        <a href="#">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 19V5a2 2 0 0 1 2-2h14v16H6a2 2 0 0 0-2 2zM8 7h8"/></svg>
            <span>FIK - Fikih</span>
            <svg class="chev" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
        </a>
        <a href="#" class="sub on">
            <span>VII.A</span>
            <span class="st" style="background: #c82333" aria-label="Belum kirim">✕</span>
        </a>
        <a href="#" class="sub">
            <span>VII.B</span>
            <span class="st" style="background: #1e7e34" aria-label="Terkirim">✓</span>
        </a>
        <a href="#" class="sub">
            <span>VIII.A</span>
            <span class="st" style="background: #c82333" aria-label="Belum kirim">✕</span>
        </a>
    @else
        @foreach($teachingClasses as $subject)
            <a href="#">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 19V5a2 2 0 0 1 2-2h14v16H6a2 2 0 0 0-2 2zM8 7h8"/></svg>
                <span>{{ $subject['code'] }} - {{ $subject['name'] }}</span>
                <svg class="chev" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
            </a>
            @foreach($subject['classes'] as $cls)
                <a href="{{ $cls['url'] ?? '#' }}" class="sub {{ ($cls['is_active'] ?? false) ? 'on' : '' }}">
                    <span>{{ $cls['name'] }}</span>
                    @if($cls['submitted'] ?? false)
                        <span class="st" style="background: #1e7e34" aria-label="Terkirim">✓</span>
                    @else
                        <span class="st" style="background: #c82333" aria-label="Belum kirim">✕</span>
                    @endif
                </a>
            @endforeach
        @endforeach
    @endif

    {{-- Tugas tambahan bila wali kelas --}}
    <div class="sec">Tugas Tambahan</div>
    <a href="#" class="{{ $active === 'wali' ? 'on' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-5 7-5s7 1.5 7 5"/></svg>
        <span>Wali Kelas {{ $homeroomClass['name'] ?? 'VII.B' }}</span>
    </a>

    <div class="sec">Akun</div>
    <a href="#">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 3h5v18h-5M10 17l-5-5 5-5M5 12h11"/></svg>
        <span>Logout</span>
    </a>
</nav>
