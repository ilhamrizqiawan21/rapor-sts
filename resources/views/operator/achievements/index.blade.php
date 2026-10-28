<x-layout.app title="Prestasi Siswa" userRole="operator">
    <x-slot:sidebar>
        <x-sidebar.operator active="achievements" />
    </x-slot:sidebar>

    <x-slot:breadcrumb>
        <a href="{{ route('operator.dashboard') }}">Home</a> / <span>Siswa</span> / <span>Prestasi Siswa</span>
    </x-slot:breadcrumb>

    @if(session('success'))
        <x-alert variant="info">{{ session('success') }}</x-alert>
    @endif
    @if($errors->any())
        <x-alert variant="warning">
            @foreach($errors->all() as $err)
                <div>{{ $err }}</div>
            @endforeach
        </x-alert>
    @endif

    <x-card title="Prestasi Siswa">
        <x-slot:actions>
            @if($classroom && ! $isLocked)
                <button type="submit" form="formPrestasi" class="btn b-pri">Simpan Prestasi</button>
            @endif
        </x-slot:actions>

        <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 16px">
            <label for="achievementClassFilter" style="font-weight: 600">Kelas:</label>
            <select id="achievementClassFilter" class="sel" style="min-width: 220px" onchange="location.href = '{{ route('operator.achievements.index') }}?classroom_id=' + this.value">
                @forelse($classrooms as $availableClassroom)
                    <option value="{{ $availableClassroom->id }}" @selected($classroom?->id === $availableClassroom->id)>
                        {{ $availableClassroom->name }} · {{ $availableClassroom->academicYear->name }}
                    </option>
                @empty
                    <option value="">Belum ada kelas</option>
                @endforelse
            </select>
            @if($classroom)
                <span class="cs">Periode {{ $period->academicYear->name }} · {{ $students->count() }} siswa aktif</span>
            @endif
        </div>

        @if($classroom)
                @if($isLocked)
                    <div class="alert" style="background: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; margin-bottom: 14px">
                        <b>Perhatian:</b> Kelas {{ $classroom->name }} telah dikunci. Data prestasi hanya dapat dilihat sampai kunci dibuka.
                    </div>
                @endif
            <div class="alert a-lt" style="margin-bottom: 14px">
                Isi satu atau beberapa prestasi untuk setiap siswa. Data ini dapat ditampilkan di PDF rapor setelah opsi <b>Prestasi Siswa</b> diaktifkan pada Pengaturan Cetak.
            </div>

            <form method="POST" action="{{ route('operator.achievements.update') }}" id="formPrestasi">
                @csrf
                <input type="hidden" name="classroom_id" value="{{ $classroom->id }}">
                <div class="tb">
                    <table class="t">
                        <thead>
                            <tr>
                                <th style="width: 36px">#</th>
                                <th style="width: 210px">Siswa</th>
                                <th>Prestasi</th>
                                <th style="width: 150px">Tingkat</th>
                                <th style="width: 110px">Peringkat</th>
                                <th style="width: 145px">Tanggal</th>
                                <th>Penyelenggara</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $idx => $student)
                                @php
                                    $existing = $records->get($student->id, collect())
                                        ->map(fn ($record) => [
                                            'name' => $record->name,
                                            'level' => $record->level,
                                            'rank' => $record->rank,
                                            'achieved_at' => $record->achieved_at?->format('Y-m-d'),
                                            'organizer' => $record->organizer,
                                            'description' => $record->description,
                                        ])->all();
                                    $studentRecords = old('achievements.'.$student->id, $existing ?: [[
                                        'name' => '',
                                        'level' => 'Sekolah',
                                        'rank' => '',
                                        'achieved_at' => '',
                                        'organizer' => '',
                                        'description' => '',
                                    ]]);
                                @endphp
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td>
                                        <b>{{ $student->name }}</b><br>
                                        <span class="cs">{{ $student->nisn }}</span>
                                    </td>
                                    <td colspan="6">
                                        <div class="achievement-list" data-student-id="{{ $student->id }}">
                                            @foreach($studentRecords as $recordIndex => $record)
                                                <div class="achievement-row" style="display: grid; grid-template-columns: minmax(180px, 1.4fr) 135px 100px 135px minmax(150px, 1fr) minmax(150px, 1fr) auto; gap: 8px; margin-bottom: 8px; align-items: start">
                                                    <input class="in" name="achievements[{{ $student->id }}][{{ $recordIndex }}][name]" value="{{ $record['name'] ?? '' }}" placeholder="Nama prestasi" maxlength="150" {{ $isLocked ? 'disabled' : '' }}>
                                                    <select class="in" name="achievements[{{ $student->id }}][{{ $recordIndex }}][level]" {{ $isLocked ? 'disabled' : '' }}>
                                                        @foreach(['Sekolah', 'Kecamatan', 'Kabupaten/Kota', 'Provinsi', 'Nasional', 'Internasional'] as $level)
                                                            <option value="{{ $level }}" @selected(($record['level'] ?? 'Sekolah') === $level)>{{ $level }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input class="in" name="achievements[{{ $student->id }}][{{ $recordIndex }}][rank]" value="{{ $record['rank'] ?? '' }}" placeholder="Juara 1" maxlength="50" {{ $isLocked ? 'disabled' : '' }}>
                                                    <input class="in" type="date" name="achievements[{{ $student->id }}][{{ $recordIndex }}][achieved_at]" value="{{ $record['achieved_at'] ?? '' }}" {{ $isLocked ? 'disabled' : '' }}>
                                                    <input class="in" name="achievements[{{ $student->id }}][{{ $recordIndex }}][organizer]" value="{{ $record['organizer'] ?? '' }}" placeholder="Penyelenggara" maxlength="150" {{ $isLocked ? 'disabled' : '' }}>
                                                    <input class="in" name="achievements[{{ $student->id }}][{{ $recordIndex }}][description]" value="{{ $record['description'] ?? '' }}" placeholder="Keterangan" maxlength="255" {{ $isLocked ? 'disabled' : '' }}>
                                                    @if(! $isLocked)
                                                        <button type="button" class="btn b-dan remove-achievement" style="min-height: 34px; padding: 0 10px" aria-label="Hapus prestasi">×</button>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                        @if(! $isLocked)
                                            <button type="button" class="btn b-sec add-achievement" data-student-id="{{ $student->id }}" style="min-height: 30px; padding: 0 10px">+ Tambah prestasi</button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 24px; color: #6c757d">
                                        Belum ada siswa aktif terdaftar di kelas {{ $classroom->name }}.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>
        @else
            <div class="alert a-lt">Belum ada kelas pada tahun pelajaran periode aktif.</div>
        @endif
    </x-card>

    @if($classroom && ! $isLocked)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const levels = ['Sekolah', 'Kecamatan', 'Kabupaten/Kota', 'Provinsi', 'Nasional', 'Internasional'];

                document.querySelectorAll('.add-achievement').forEach(function (button) {
                    button.addEventListener('click', function () {
                        const studentId = this.dataset.studentId;
                        const list = document.querySelector('.achievement-list[data-student-id="' + studentId + '"]');
                        const index = list.querySelectorAll('.achievement-row').length;
                        const row = document.createElement('div');
                        row.className = 'achievement-row';
                        row.style.cssText = 'display:grid;grid-template-columns:minmax(180px,1.4fr) 135px 100px 135px minmax(150px,1fr) minmax(150px,1fr) auto;gap:8px;margin-bottom:8px;align-items:start';
                        row.innerHTML = '<input class="in" name="achievements[' + studentId + '][' + index + '][name]" placeholder="Nama prestasi" maxlength="150">' +
                            '<select class="in" name="achievements[' + studentId + '][' + index + '][level]">' + levels.map(function (level) { return '<option>' + level + '</option>'; }).join('') + '</select>' +
                            '<input class="in" name="achievements[' + studentId + '][' + index + '][rank]" placeholder="Juara 1" maxlength="50">' +
                            '<input class="in" type="date" name="achievements[' + studentId + '][' + index + '][achieved_at]">' +
                            '<input class="in" name="achievements[' + studentId + '][' + index + '][organizer]" placeholder="Penyelenggara" maxlength="150">' +
                            '<input class="in" name="achievements[' + studentId + '][' + index + '][description]" placeholder="Keterangan" maxlength="255">' +
                            '<button type="button" class="btn b-dan remove-achievement" style="min-height:34px;padding:0 10px" aria-label="Hapus prestasi">×</button>';
                        list.appendChild(row);
                        row.querySelector('.remove-achievement').addEventListener('click', function () { row.remove(); });
                    });
                });

                document.querySelectorAll('.remove-achievement').forEach(function (button) {
                    button.addEventListener('click', function () { this.closest('.achievement-row').remove(); });
                });
            });
        </script>
    @endif
</x-layout.app>
