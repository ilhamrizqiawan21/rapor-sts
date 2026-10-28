<x-layout.app title="Nilai KKTP / KKM Default" userRole="operator">
    <x-slot:sidebar>
        <x-sidebar.operator active="kktp" />
    </x-slot:sidebar>

    <x-slot:breadcrumb>
        <a href="{{ route('operator.dashboard') }}">Home</a> / <a href="#">Admin</a> / <span>Nilai KKM</span>
    </x-slot:breadcrumb>

    @if(session('success'))
        <x-alert variant="info">{{ session('success') }}</x-alert>
    @endif

    <x-card title="Kriteria Ketercapaian Tujuan Pembelajaran (KKTP) Default">
        <div style="display: flex; flex-direction: column; gap: 16px">
            <x-alert variant="warning">
                KKTP default mapel digunakan sebagai nilai batas ketuntasan awal saat guru membuat penilaian STS. Jika belum diatur, sistem menggunakan KKTP bawaan madrasah (<b>{{ $globalKktp }}</b>).
            </x-alert>

            <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center">
                <label for="yearFilter" style="font-weight: 600">Tahun:</label>
                <select id="yearFilter" class="sel" style="min-width: 140px" onchange="location.href = '{{ route('operator.kktp.index') }}?academic_year_id=' + this.value + '&level={{ $selectedLevel }}'">
                    @foreach($academicYears as $y)
                        <option value="{{ $y->id }}" @selected($selectedYearId == $y->id)>{{ $y->name }}</option>
                    @endforeach
                </select>

                <label for="levelFilter" style="font-weight: 600">Tingkat:</label>
                <select id="levelFilter" class="sel" style="min-width: 140px" onchange="location.href = '{{ route('operator.kktp.index') }}?academic_year_id={{ $selectedYearId }}&level=' + this.value">
                    @foreach($levels as $level)
                        <option value="{{ $level }}" @selected($selectedLevel === $level)>Tingkat {{ \App\Models\MadrasahProfile::classLevelLabel($level) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="tb">
                <table class="t">
                    <thead>
                        <tr>
                            <th style="width: 40px">#</th>
                            <th>Kode</th>
                            <th>Mata Pelajaran</th>
                            <th style="width: 140px">KKTP Default</th>
                            <th style="width: 100px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subjects as $idx => $sbj)
                            @php
                                $val = $kktpDefaults[$sbj->id]?->kktp ?? $globalKktp;
                            @endphp
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td><b>{{ $sbj->code }}</b></td>
                                <td>{{ $sbj->name }}</td>
                                <form method="POST" action="{{ route('operator.kktp.store') }}">
                                    @csrf
                                    <input type="hidden" name="academic_year_id" value="{{ $selectedYearId }}">
                                    <input type="hidden" name="subject_id" value="{{ $sbj->id }}">
                                    <input type="hidden" name="level" value="{{ $selectedLevel }}">
                                    <td>
                                        <input type="number" step="0.01" name="kktp" class="in" style="width: 100px; height: 32px" value="{{ $val }}" required min="0" max="100">
                                    </td>
                                    <td>
                                        <button type="submit" class="btn b-pri" style="min-height: 30px; padding: 0 10px; font-size: 12px">Simpan</button>
                                    </td>
                                </form>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </x-card>
</x-layout.app>

