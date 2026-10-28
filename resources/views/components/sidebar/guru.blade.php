@props([
    'active' => 'dashboard',
    'activeAssignmentId' => null,
    'teachingClasses' => [],
    'homeroomClass' => null,
])

@php
    $user = auth()->user();
    $activePeriod = (app()->bound('activePeriod') && app('activePeriod'))
        ? app('activePeriod')
        : \App\Models\Period::with('academicYear')->where('is_active', true)->first();

    if (empty($teachingClasses) && $user) {
        $assignments = \App\Models\TeachingAssignment::with(['subject', 'classroom'])
            ->where('period_id', $activePeriod?->id)
            ->where('teacher_id', $user->id)
            ->get();

        $grouped = [];
        foreach ($assignments->groupBy('subject_id') as $subjectId => $group) {
            $subj = $group->first()->subject;
            $classList = [];
            foreach ($group as $a) {
                $classList[] = [
                    'id' => $a->id,
                    'name' => $a->classroom->name,
                    'url' => route('guru.penilaian.index', $a),
                    'submitted' => $a->isSubmitted(),
                    'is_active' => (string) $activeAssignmentId === (string) $a->id || request()->route('teachingAssignment')?->id == $a->id,
                ];
            }
            $grouped[] = [
                'id' => $subj->id,
                'code' => $subj->code,
                'name' => $subj->name,
                'classes' => $classList,
            ];
        }
        $teachingClasses = $grouped;
    }

    if ($homeroomClass === null && $user && $activePeriod) {
        $homeroomClass = \App\Models\Classroom::where('academic_year_id', $activePeriod->academic_year_id)
            ->where('homeroom_teacher_id', $user->id)
            ->first();
    }

    $hasExtracurricularAssignment = $user && $activePeriod
        ? \App\Models\ExtracurricularRecord::where('period_id', $activePeriod->id)
            ->where('supervisor_id', $user->id)
            ->exists()
        : false;

    $currentRoute = request()->route()?->getName();
    $activeKey = $active;
    if ($activeKey === 'dashboard' && !request()->routeIs('guru.dashboard')) {
        if (request()->routeIs('wali.kehadiran.*')) $activeKey = 'wali_kehadiran';
        elseif (request()->routeIs('guru.ekstrakurikuler.*')) $activeKey = 'extracurricular';
        elseif (request()->routeIs('wali.status.*')) $activeKey = 'wali_status';
        elseif (request()->routeIs('wali.rapor.*')) $activeKey = 'wali_rapor';
    }

    $waliOpen = in_array($activeKey, ['wali', 'wali_kehadiran', 'wali_status', 'wali_rapor']);
@endphp

<nav class="nav" aria-label="Menu guru">
    <a href="{{ route('guru.dashboard') }}" class="{{ $activeKey === 'dashboard' ? 'on' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 13h8V3H3zM13 21h8V11h-8zM3 21h8v-6H3zM13 3v6h8V3z"/></svg>
        <span>Dashboard</span>
    </a>

    {{-- Mapel yang diampu --}}
    @foreach($teachingClasses as $subject)
        @php
            $isSubjectOpen = collect($subject['classes'])->contains(fn($c) => !empty($c['is_active']));
        @endphp
        <div class="nav-group {{ $isSubjectOpen ? 'open' : '' }}">
            <button type="button" class="nav-toggle" aria-expanded="{{ $isSubjectOpen ? 'true' : 'false' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 19V5a2 2 0 0 1 2-2h14v16H6a2 2 0 0 0-2 2zM8 7h8"/></svg>
                <span>{{ $subject['code'] }} - {{ $subject['name'] }}</span>
                <svg class="chev" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
            </button>
            <div class="nav-sub">
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
            </div>
        </div>
    @endforeach

    {{-- Tugas tambahan bila wali kelas (FR-WAL-01) --}}
    @if($homeroomClass)
        <div class="sec">Tugas Tambahan</div>
        <div class="nav-group {{ $waliOpen ? 'open' : '' }}">
            <button type="button" class="nav-toggle" aria-expanded="{{ $waliOpen ? 'true' : 'false' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-5 7-5s7 1.5 7 5"/></svg>
                <span>Wali Kelas {{ $homeroomClass->name }}</span>
                <svg class="chev" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
            </button>
            <div class="nav-sub">
                <a href="{{ route('wali.kehadiran.index') }}" class="sub {{ $activeKey === 'wali_kehadiran' ? 'on' : '' }}">
                    <span>Kehadiran &amp; Catatan</span>
                </a>
                <a href="{{ route('wali.status.index') }}" class="sub {{ $activeKey === 'wali_status' ? 'on' : '' }}">
                    <span>Status Nilai</span>
                </a>
                <a href="{{ route('wali.rapor.index') }}" class="sub {{ $activeKey === 'wali_rapor' ? 'on' : '' }}">
                    <span>Cetak Rapor STS</span>
                </a>
            </div>
        </div>
    @endif

    @if($hasExtracurricularAssignment)
        <div class="sec">Tugas Penilaian</div>
        <a href="{{ route('guru.ekstrakurikuler.index') }}" class="{{ $activeKey === 'extracurricular' ? 'on' : '' }}">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 4h16v16H4zM8 8h8M8 12h8M8 16h5"/></svg>
            <span>Penilaian Ekstrakurikuler</span>
        </a>
    @endif

    <div class="sec">Akun</div>
    <a href="{{ route('akun.edit') }}" class="{{ $active === 'akun' ? 'on' : '' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
        <span>Profil Saya</span>
    </a>
    <form method="POST" action="{{ route('logout') }}" id="logout-form-guru" style="display: none;">
        @csrf
    </form>
    <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form-guru').submit();">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 3h5v18h-5M10 17l-5-5 5-5M5 12h11"/></svg>
        <span>Logout</span>
    </a>
</nav>
