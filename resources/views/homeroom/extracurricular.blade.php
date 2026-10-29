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

    @php
        $activityDefinitions = old('activities', $activities->values()->mapWithKeys(fn ($activity) => [
            (string) $activity->id => ['name' => $activity->name, 'supervisor_id' => $activity->supervisor_id],
        ])->all());
        if (blank($activityDefinitions)) {
            $activityDefinitions = ['new-0' => ['name' => '', 'supervisor_id' => '']];
        }
        $activityKeyByName = $activities->mapWithKeys(fn ($activity) => [mb_strtolower(trim($activity->name)) => (string) $activity->id]);
        $submittedStudentRecords = old('student_records');
    @endphp

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
                Buat daftar kegiatan dan tetapkan satu guru pembina untuk setiap kegiatan. Setelah itu pilih kegiatan pada siswa yang mengikutinya.
            </div>

            <form method="POST" action="{{ route('operator.extracurricular.update') }}" id="form-extracurricular">
                @csrf
                <input type="hidden" name="classroom_id" value="{{ $classroom->id }}">

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px">
                    <b>Daftar Kegiatan dan Pembina</b>
                    @if(! $isLocked)
                        <button type="button" class="btn b-sec" id="add-activity" style="min-height: 30px; padding: 0 10px">+ Tambah kegiatan</button>
                    @endif
                </div>
                <div id="activity-definitions" style="margin-bottom: 18px">
                    @foreach($activityDefinitions as $activityKey => $activity)
                        <div class="activity-definition" data-activity-key="{{ $activityKey }}" style="display: grid; grid-template-columns: minmax(180px, 1fr) 280px auto; gap: 8px; margin-bottom: 8px; align-items: start">
                            <input class="in" name="activities[{{ $activityKey }}][name]" value="{{ $activity['name'] ?? '' }}" placeholder="Contoh: Pramuka" maxlength="100" {{ $isLocked ? 'disabled' : '' }}>
                            <select class="in" name="activities[{{ $activityKey }}][supervisor_id]" {{ $isLocked ? 'disabled' : '' }}>
                                <option value="">- Pilih guru pembina -</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" @selected((int) ($activity['supervisor_id'] ?? 0) === $teacher->id)>{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                            @if(! $isLocked)
                                <button type="button" class="btn b-dan remove-activity" style="min-height: 34px; padding: 0 10px" aria-label="Hapus kegiatan">×</button>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="tb">
                    <table class="t">
                        <thead>
                            <tr>
                                <th style="width: 36px">#</th>
                                <th style="width: 230px">NISN / Nama</th>
                                <th>Kegiatan Ekstrakurikuler</th>
                                <th style="width: 150px">Predikat</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $idx => $student)
                                @php
                                    $existing = $records->get($student->id, collect())
                                        ->map(fn ($record) => [
                                            'activity_key' => (string) ($record->extracurricular_activity_id ?: ($activityKeyByName[mb_strtolower(trim($record->name))] ?? '')),
                                            'predicate' => $record->predicate,
                                            'description' => $record->description,
                                        ])->all();
                                    $studentRecords = $submittedStudentRecords !== null
                                        ? ($submittedStudentRecords[$student->id] ?? [['activity_key' => '', 'predicate' => 'Baik', 'description' => '']])
                                        : ($existing ?: [['activity_key' => '', 'predicate' => 'Baik', 'description' => '']]);
                                @endphp
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td>
                                        <b>{{ $student->name }}</b><br>
                                        <span class="cs">{{ $student->nisn }}</span>
                                    </td>
                                    <td colspan="3">
                                        <div class="extra-list" data-student-id="{{ $student->id }}">
                                            @foreach($studentRecords as $recordIndex => $record)
                                                <div class="extra-row" style="display: grid; grid-template-columns: minmax(180px, 1fr) 140px minmax(180px, 1fr) auto; gap: 8px; margin-bottom: 8px; align-items: start">
                                                    <select class="in activity-select" name="student_records[{{ $student->id }}][{{ $recordIndex }}][activity_key]" {{ $isLocked ? 'disabled' : '' }}>
                                                        <option value="">- Pilih kegiatan -</option>
                                                        @foreach($activityDefinitions as $optionKey => $option)
                                                            @if(filled($option['name'] ?? null))
                                                                <option value="{{ $optionKey }}" @selected((string) ($record['activity_key'] ?? '') === (string) $optionKey)>{{ $option['name'] }}</option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                    <select class="in" name="student_records[{{ $student->id }}][{{ $recordIndex }}][predicate]" {{ $isLocked ? 'disabled' : '' }}>
                                                        @foreach(['Sangat Baik', 'Baik', 'Cukup', 'Kurang'] as $predicate)
                                                            <option value="{{ $predicate }}" @selected(($record['predicate'] ?? 'Baik') === $predicate)>{{ $predicate }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input class="in" name="student_records[{{ $student->id }}][{{ $recordIndex }}][description]" value="{{ $record['description'] ?? '' }}" placeholder="Keterangan (opsional)" maxlength="255" {{ $isLocked ? 'disabled' : '' }}>
                                                    @if(! $isLocked)
                                                        <button type="button" class="btn b-dan remove-extra" style="min-height: 34px; padding: 0 10px" aria-label="Hapus kegiatan siswa">×</button>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                        @if(! $isLocked)
                                            <button type="button" class="btn b-sec add-extra" data-student-id="{{ $student->id }}" style="min-height: 30px; padding: 0 10px">+ Tambah kegiatan siswa</button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 24px; color: #6c757d">
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
                const definitions = document.getElementById('activity-definitions');
                const teachers = @json($teachers->map(fn ($teacher) => ['id' => $teacher->id, 'name' => $teacher->name])->values());
                let activityCounter = 1;
                let recordCounter = 1;

                function activityOptions(selected) {
                    let html = '<option value="">- Pilih kegiatan -</option>';
                    definitions.querySelectorAll('.activity-definition').forEach(function (row) {
                        const key = row.dataset.activityKey;
                        const name = row.querySelector('input[name$="[name]"]').value.trim();
                        if (name) {
                            html += '<option value="' + key + '"' + (String(selected) === String(key) ? ' selected' : '') + '>' + name.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</option>';
                        }
                    });
                    return html;
                }

                function syncActivitySelects() {
                    document.querySelectorAll('.activity-select').forEach(function (select) {
                        const selected = select.value;
                        select.innerHTML = activityOptions(selected);
                    });
                }

                document.getElementById('add-activity')?.addEventListener('click', function () {
                    const key = 'new-' + (Date.now() + activityCounter++);
                    const row = document.createElement('div');
                    row.className = 'activity-definition';
                    row.dataset.activityKey = key;
                    row.style.cssText = 'display:grid;grid-template-columns:minmax(180px,1fr) 280px auto;gap:8px;margin-bottom:8px;align-items:start';
                    row.innerHTML = '<input class="in" name="activities[' + key + '][name]" placeholder="Contoh: Pramuka" maxlength="100">' +
                        '<select class="in" name="activities[' + key + '][supervisor_id]"><option value="">- Pilih guru pembina -</option></select>' +
                        '<button type="button" class="btn b-dan remove-activity" style="min-height:34px;padding:0 10px" aria-label="Hapus kegiatan">×</button>';
                    const teacherSelect = row.querySelector('select');
                    teachers.forEach(function (teacher) { teacherSelect.appendChild(new Option(teacher.name, teacher.id)); });
                    definitions.appendChild(row);
                    row.querySelector('input').addEventListener('input', syncActivitySelects);
                    row.querySelector('.remove-activity').addEventListener('click', function () {
                        row.remove();
                        syncActivitySelects();
                    });
                    syncActivitySelects();
                });

                document.querySelectorAll('input[name$="[name]"]').forEach(function (input) {
                    input.addEventListener('input', syncActivitySelects);
                });

                document.querySelectorAll('.add-extra').forEach(function (button) {
                    button.addEventListener('click', function () {
                        const studentId = this.dataset.studentId;
                        const list = document.querySelector('.extra-list[data-student-id="' + studentId + '"]');
                        const index = 'new-' + (Date.now() + recordCounter++);
                        const row = document.createElement('div');
                        row.className = 'extra-row';
                        row.style.cssText = 'display:grid;grid-template-columns:minmax(180px,1fr) 140px minmax(180px,1fr) auto;gap:8px;margin-bottom:8px;align-items:start';
                        row.innerHTML = '<select class="in activity-select" name="student_records[' + studentId + '][' + index + '][activity_key]"></select>' +
                            '<select class="in" name="student_records[' + studentId + '][' + index + '][predicate]"><option>Sangat Baik</option><option selected>Baik</option><option>Cukup</option><option>Kurang</option></select>' +
                            '<input class="in" name="student_records[' + studentId + '][' + index + '][description]" placeholder="Keterangan (opsional)" maxlength="255">' +
                            '<button type="button" class="btn b-dan remove-extra" style="min-height:34px;padding:0 10px" aria-label="Hapus kegiatan siswa">×</button>';
                        list.appendChild(row);
                        row.querySelector('.activity-select').innerHTML = activityOptions('');
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
