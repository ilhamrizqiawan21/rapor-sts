<x-layout.app title="Status Nilai Rapor STS" userRole="operator">
    <x-slot:breadcrumb>
        <a href="{{ route('operator.dashboard') }}">Home</a> / <span>Rapor</span> / <span>Status Nilai</span>
    </x-slot:breadcrumb>

    @if(session('success'))
        <x-alert variant="info">{{ session('success') }}</x-alert>
    @endif
    @if(session('error'))
        <x-alert variant="warning">{{ session('error') }}</x-alert>
    @endif
    @if(session('info'))
        <x-alert variant="info">{{ session('info') }}</x-alert>
    @endif

    <div class="card">
        <div class="ch">
            <div>
                <div class="ct">Status Nilai Rapor STS</div>
                <div class="cs">Pantau progres pengisian dan pengiriman nilai guru per kelas</div>
            </div>
            @if($selectedClassroom)
                <div class="acts">
                    @if($isLocked)
                        <form method="POST" action="{{ route('operator.status.unlock', $selectedClassroom) }}" onsubmit="return confirm('Buka kunci nilai kelas {{ $selectedClassroom->name }}?')">
                            @csrf
                            <button type="submit" class="btn b-dan">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>
                                Terkunci (Buka Kunci)
                            </button>
                        </form>
                    @elseif($allSubmitted)
                        <form method="POST" action="{{ route('operator.status.lock', $selectedClassroom) }}" onsubmit="return confirm('Kunci nilai rapor kelas {{ $selectedClassroom->name }}?')">
                            @csrf
                            <button type="submit" class="btn b-suc">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                Kunci Nilai Kelas
                            </button>
                        </form>
                    @else
                        <button type="button" class="btn b-inf" style="opacity: 0.85; cursor: not-allowed" title="Semua mapel harus terkirim sebelum kelas dapat dikunci" disabled>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="1"/><path d="M8 11V7a4 4 0 0 1 8 0"/></svg>
                            Belum Dikunci Walas (Belum Lengkap)
                        </button>
                    @endif
                </div>
            @endif
        </div>

        <div class="cb" style="display: flex; flex-direction: column; gap: 16px">
            {{-- Filter Kelas --}}
            <form method="GET" action="{{ route('operator.status.index') }}" id="filterForm" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; max-width: 680px">
                <div class="f">
                    <label for="kl">Pilih Kelas</label>
                    <select id="kl" name="classroom_id" class="in" onchange="document.getElementById('filterForm').submit()">
                        @foreach($classrooms as $c)
                            <option value="{{ $c->id }}" {{ $selectedClassroom && $selectedClassroom->id === $c->id ? 'selected' : '' }}>
                                Tingkat {{ $c->level }} — {{ $c->name }} (Wali: {{ $c->homeroomTeacher?->name ?? 'Belum ditentukan' }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>

            @if($selectedClassroom)
                {{-- Progres Bar --}}
                <div style="border: 1px solid #dee2e6; border-radius: 4px">
                    <div style="padding: 10px 14px; border-bottom: 1px solid #dee2e6; font-size: 13px; font-weight: 600">Progres Nilai — {{ $selectedClassroom->name }}</div>
                    <div style="padding: 14px; display: flex; flex-direction: column; gap: 12px">
                        <div style="display: flex; flex-direction: column; gap: 6px">
                            <div style="display: flex; justify-content: space-between; font-size: 13px">
                                <span>Sumatif Lingkup Materi</span>
                                <b>{{ $progressLm }}%</b>
                            </div>
                            <div class="bar">
                                <span style="width: {{ $progressLm }}%">{{ $progressLm }}%</span>
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 6px">
                            <div style="display: flex; justify-content: space-between; font-size: 13px">
                                <span>Nilai STS</span>
                                <b>{{ $progressSts }}%</b>
                            </div>
                            <div class="bar">
                                <span style="width: {{ $progressSts }}%">{{ $progressSts }}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tabel Status Nilai Guru --}}
                <div class="tb">
                    <table class="t">
                        <thead>
                            <tr>
                                <th style="width: 40px">#</th>
                                <th>Mapel</th>
                                <th>Guru</th>
                                <th>Sumatif LM</th>
                                <th>STS</th>
                                <th>Status</th>
                                <th style="width: 120px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tableRows as $row)
                                @if($row['is_header'])
                                    <tr style="background: #f8f9fa">
                                        <td><b>{{ $row['no'] }}</b></td>
                                        <td colspan="6"><b>{{ $row['subject_name'] }}</b></td>
                                    </tr>
                                @else
                                    <tr>
                                        <td>{{ $row['no'] }}</td>
                                        <td style="{{ $row['letter'] ? 'padding-left: 24px; font-style: italic' : '' }}">
                                            {{ $row['letter'] }} {{ $row['subject_name'] }}
                                        </td>
                                        <td>{{ $row['teacher_name'] }}</td>
                                        <td>{{ $row['lm_text'] }}</td>
                                        <td>{{ $row['sts_text'] }}</td>
                                        <td>
                                            @if($row['is_submitted'])
                                                <span class="pill" style="background: #1e7e34">✓ Terkirim</span>
                                            @else
                                                <span class="pill" style="background: #c82333">✕ Belum Kirim</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($row['is_submitted'] && ! $isLocked)
                                                <form method="POST" action="{{ route('operator.status.unsubmit', $row['assignment']) }}" onsubmit="return confirm('Batalkan pengiriman nilai mata pelajaran ini agar guru dapat mengedit?')">
                                                    @csrf
                                                    <button type="submit" class="btn b-dan" style="padding: 2px 8px; font-size: 11px; min-height: 26px">
                                                        Batal Kirim
                                                    </button>
                                                </form>
                                            @elseif($row['is_submitted'] && $isLocked)
                                                <span style="font-size: 11px; color: #6c757d">Terkunci</span>
                                            @else
                                                –
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 20px; color: #6c757d">
                                        Belum ada penugasan mengajar terdaftar pada kelas ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert a-lt">Belum ada kelas yang dibuat untuk tahun pelajaran aktif.</div>
            @endif
        </div>
    </div>
</x-layout.app>
