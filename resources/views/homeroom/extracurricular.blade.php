<x-layout.app title="Operator · Ekstrakurikuler" userRole="operator">
    <x-slot:sidebar>
        <x-sidebar.operator active="extracurriculars" />
    </x-slot:sidebar>

    <x-slot:breadcrumb>
        <a href="{{ route('operator.dashboard') }}">Home</a> / <span>Siswa</span> / <span>Ekstrakurikuler</span>
    </x-slot:breadcrumb>

    @if(session('success'))
        <x-alert variant="info">{{ session('success') }}</x-alert>
    @endif
    @if(session('error'))
        <x-alert variant="warning">{{ session('error') }}</x-alert>
    @endif
    @if($errors->any())
        <x-alert variant="danger">
            <ul style="margin: 0; padding-left: 18px">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    @if($isLocked)
        <div class="alert" style="background: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; margin-bottom: 16px">
            <b>Perhatian:</b> Kelas {{ $classroom->name }} telah dikunci. Data ekstrakurikuler hanya dapat dilihat sampai kunci dibuka.
        </div>
    @endif

    <div class="card">
        <div class="ch">
            <div>
                <div class="ct">Ekstrakurikuler Siswa</div>
                <div class="cs">{{ $classroom->name }} · {{ $students->count() }} siswa · {{ $period->academicYear->name }}</div>
            </div>
            @if(! $isLocked)
                <button type="submit" form="form-extracurricular" class="btn b-pri">Simpan</button>
            @endif
        </div>

        <div class="cb">
            @if($isOperator)
                <form method="GET" action="{{ route('operator.extracurricular.index') }}" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 14px">
                    <label for="extracurricularClassFilter" style="font-weight: 600">Kelas:</label>
                    <select id="extracurricularClassFilter" name="classroom_id" class="sel" style="min-width: 220px" onchange="this.form.submit()">
                        @foreach($classrooms as $availableClassroom)
                            <option value="{{ $availableClassroom->id }}" @selected($availableClassroom->id === $classroom->id)>
                                {{ $availableClassroom->name }} · {{ $availableClassroom->academicYear->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif

            <div class="alert a-lt" style="margin-bottom: 14px">
                Tambahkan satu atau beberapa kegiatan per siswa. Predikat dan keterangan ini akan tampil di rapor jika bagian Ekstrakurikuler diaktifkan pada Pengaturan Cetak.
            </div>

            <form method="POST" action="{{ route('operator.extracurricular.update') }}" id="form-extracurricular">
                @csrf
                <input type="hidden" name="classroom_id" value="{{ $classroom->id }}">
                <div class="tb">
                    <table class="t">
                        <thead>
                            <tr>
                                <th style="width: 36px">#</th>
                                <th style="width: 230px">NISN / Nama</th>
                                <th>Kegiatan Ekstrakurikuler</th>
                                <th style="width: 220px">Guru Pembina</th>
                                <th style="width: 150px">Predikat</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $idx => $student)
                                @php
                                    $existing = $records->get($student->id, collect())
                                        ->map(fn ($record) => [
                                            'name' => $record->name,
                                            'supervisor_id' => $record->supervisor_id,
                                            'predicate' => $record->predicate,
                                            'description' => $record->description,
                                        ])->all();
                                    $studentRecords = old('extracurriculars.'.$student->id, $existing ?: [['name' => '', 'predicate' => 'Baik', 'description' => '']]);
                                @endphp
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td>
                                        <b>{{ $student->name }}</b><br>
                                        <span class="cs">{{ $student->nisn }}</span>
                                    </td>
                                    <td colspan="4">
                                        <div class="extra-list" data-student-id="{{ $student->id }}">
                                            @foreach($studentRecords as $recordIndex => $record)
                                                <div class="extra-row" style="display: grid; grid-template-columns: minmax(180px, 1fr) 220px 140px minmax(180px, 1fr) auto; gap: 8px; margin-bottom: 8px; align-items: start">
                                                    <input class="in" name="extracurriculars[{{ $student->id }}][{{ $recordIndex }}][name]" value="{{ $record['name'] ?? '' }}" placeholder="Contoh: Pramuka" maxlength="100" {{ $isLocked ? 'disabled' : '' }}>
                                                    <select class="in" name="extracurriculars[{{ $student->id }}][{{ $recordIndex }}][supervisor_id]" {{ $isLocked ? 'disabled' : '' }}>
                                                        <option value="">- Pilih guru pembina -</option>
                                                        @foreach($teachers as $teacher)
                                                            <option value="{{ $teacher->id }}" @selected((int) ($record['supervisor_id'] ?? 0) === $teacher->id)>{{ $teacher->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <select class="in" name="extracurriculars[{{ $student->id }}][{{ $recordIndex }}][predicate]" {{ $isLocked ? 'disabled' : '' }}>
                                                        @foreach(['Sangat Baik', 'Baik', 'Cukup', 'Kurang'] as $predicate)
                                                            <option value="{{ $predicate }}" @selected(($record['predicate'] ?? 'Baik') === $predicate)>{{ $predicate }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input class="in" name="extracurriculars[{{ $student->id }}][{{ $recordIndex }}][description]" value="{{ $record['description'] ?? '' }}" placeholder="Keterangan (opsional)" maxlength="255" {{ $isLocked ? 'disabled' : '' }}>
                                                    @if(! $isLocked)
                                                        <button type="button" class="btn b-dan remove-extra" style="min-height: 34px; padding: 0 10px" aria-label="Hapus kegiatan">×</button>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                        @if(! $isLocked)
                                            <button type="button" class="btn b-sec add-extra" data-student-id="{{ $student->id }}" style="min-height: 30px; padding: 0 10px">+ Tambah kegiatan</button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 24px; color: #6c757d">
                                        Belum ada siswa aktif terdaftar di kelas {{ $classroom->name }}.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>

    @if(! $isLocked)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.add-extra').forEach(function (button) {
                    button.addEventListener('click', function () {
                        const studentId = this.dataset.studentId;
                        const list = document.querySelector('.extra-list[data-student-id="' + studentId + '"]');
                        const index = list.querySelectorAll('.extra-row').length;
                        const row = document.createElement('div');
                        row.className = 'extra-row';
                        row.style.cssText = 'display:grid;grid-template-columns:minmax(180px,1fr) 220px 140px minmax(180px,1fr) auto;gap:8px;margin-bottom:8px;align-items:start';
                        row.innerHTML = '<input class="in" name="extracurriculars[' + studentId + '][' + index + '][name]" placeholder="Contoh: Pramuka" maxlength="100">' +
                            '<select class="in supervisor-select" name="extracurriculars[' + studentId + '][' + index + '][supervisor_id]"></select>' +
                            '<select class="in" name="extracurriculars[' + studentId + '][' + index + '][predicate]"><option>Sangat Baik</option><option selected>Baik</option><option>Cukup</option><option>Kurang</option></select>' +
                            '<input class="in" name="extracurriculars[' + studentId + '][' + index + '][description]" placeholder="Keterangan (opsional)" maxlength="255">' +
                            '<button type="button" class="btn b-dan remove-extra" style="min-height:34px;padding:0 10px" aria-label="Hapus kegiatan">×</button>';
                        const supervisorSelect = row.querySelector('.supervisor-select');
                        supervisorSelect.appendChild(new Option('- Pilih guru pembina -', ''));
                        @json($teachers->map(fn ($teacher) => ['id' => $teacher->id, 'name' => $teacher->name])->values())
                            .forEach(function (teacher) { supervisorSelect.appendChild(new Option(teacher.name, teacher.id)); });
                        list.appendChild(row);
                        row.querySelector('.remove-extra').addEventListener('click', function () { row.remove(); });
                    });
                });

                document.querySelectorAll('.remove-extra').forEach(function (button) {
                    button.addEventListener('click', function () { this.closest('.extra-row').remove(); });
                });
            });
        </script>
    @endif
</x-layout.app>
