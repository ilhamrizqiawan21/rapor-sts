<x-layout.app title="Guru Pembina · Ekstrakurikuler" userRole="guru">
    <x-slot:sidebar>
        <x-sidebar.guru active="extracurricular" />
    </x-slot:sidebar>

    <x-slot:breadcrumb>
        <a href="{{ route('guru.dashboard') }}">Home</a> / <span>Guru Pembina</span> / <span>Ekstrakurikuler</span>
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
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    @php
        $gradeLabels = [
            'A' => 'Sangat Baik',
            'B' => 'Baik',
            'C' => 'Cukup',
            'D' => 'Kurang',
        ];
        $gradeFor = fn (?string $grade): string => match ($grade) {
            'Sangat Baik' => 'A',
            'Baik' => 'B',
            'Cukup' => 'C',
            'Kurang' => 'D',
            default => $grade ?: 'B',
        };
    @endphp

    @if($isLocked)
        <div class="alert" style="background: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; margin-bottom: 16px">
            <b>Perhatian:</b> Kelas {{ $classroom->name }} telah dikunci. Data ekstrakurikuler hanya dapat dilihat sampai kunci dibuka.
        </div>
    @endif

    <div class="card">
        <div class="ch">
            <div>
                <div class="ct">Penilaian Ekstrakurikuler</div>
                <div class="cs">{{ $classroom->name }} · {{ $period->academicYear->name }}</div>
            </div>
            @if(! $isLocked)
                <button type="submit" form="form-extracurricular" class="btn b-pri">Simpan</button>
            @endif
        </div>

        <div class="cb">
            <form method="GET" action="{{ route('guru.ekstrakurikuler.index') }}" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 14px">
                <label for="activityFilter" style="font-weight: 600">Kegiatan:</label>
                <select id="activityFilter" name="activity_id" class="sel" onchange="this.form.submit()">
                    @foreach($activities as $activity)
                        <option value="{{ $activity->id }}" @selected($activity->id === $selectedActivityId)>{{ $activity->name }}</option>
                    @endforeach
                </select>
                <label for="classroomFilter" style="font-weight: 600">Kelas:</label>
                <select id="classroomFilter" name="classroom_id" class="sel" onchange="this.form.submit()">
                    @foreach($classrooms as $availableClassroom)
                        <option value="{{ $availableClassroom->id }}" @selected($availableClassroom->id === $classroom->id)>
                            {{ $availableClassroom->name }}
                        </option>
                    @endforeach
                </select>
            </form>

            <div class="alert a-lt" style="margin-bottom: 14px">
                Pilih siswa yang mengikuti kegiatan ini di kelas {{ $classroom->name }}. Hanya siswa yang sudah dipilih yang muncul pada tabel penilaian.
            </div>

            <form method="POST" action="{{ route('guru.ekstrakurikuler.update') }}" id="form-extracurricular">
                @csrf
                <input type="hidden" name="activity_id" value="{{ $selectedActivityId }}">
                <input type="hidden" name="classroom_id" value="{{ $classroom->id }}">

                @if(! $isLocked)
                    <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 14px">
                        <label for="studentPicker" style="font-weight: 600">Tambah peserta:</label>
                        <select id="studentPicker" class="sel" style="min-width: 260px">
                            <option value="">- Pilih siswa -</option>
                            @foreach($availableStudents as $student)
                                <option value="{{ $student->id }}" data-name="{{ $student->name }}" data-nisn="{{ $student->nisn }}">{{ $student->name }} · {{ $student->nisn }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn b-sec" id="add-student">Tambah</button>
                    </div>
                @endif

                <div class="tb">
                    <table class="t">
                        <thead>
                            <tr>
                                <th style="width: 36px">#</th>
                                <th>Nama Siswa</th>
                                <th style="width: 170px">Nilai</th>
                                <th>Keterangan</th>
                                @if(! $isLocked)<th style="width: 70px">Aksi</th>@endif
                            </tr>
                        </thead>
                        <tbody id="participantRows">
                            @forelse($records as $studentId => $record)
                                <tr class="participant-row" data-student-id="{{ $studentId }}">
                                    <td class="row-number">{{ $loop->iteration }}</td>
                                    <td>
                                        <input type="hidden" name="participant_ids[]" value="{{ $studentId }}">
                                        <input type="hidden" name="records[{{ $studentId }}][student_id]" value="{{ $studentId }}">
                                        <b>{{ $record->student->name }}</b><br>
                                        <span class="cs">{{ $record->student->nisn }}</span>
                                    </td>
                                    <td>
                                        <select class="in" name="records[{{ $studentId }}][grade]" {{ $isLocked ? 'disabled' : '' }}>
                                            @foreach($gradeLabels as $grade => $label)
                                                <option value="{{ $grade }}" @selected($gradeFor($record->predicate) === $grade)>{{ $grade }} — {{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input class="in" name="records[{{ $studentId }}][description]" value="{{ $record->description }}" placeholder="Keterangan (opsional)" maxlength="255" {{ $isLocked ? 'disabled' : '' }}></td>
                                    @if(! $isLocked)
                                        <td><button type="button" class="btn b-dan remove-student" style="min-height: 32px; padding: 0 9px">Hapus</button></td>
                                    @endif
                                </tr>
                            @empty
                                <tr id="emptyParticipants">
                                    <td colspan="{{ $isLocked ? 4 : 5 }}" style="text-align: center; padding: 24px; color: #6c757d">Belum ada peserta pada kegiatan ini di kelas {{ $classroom->name }}.</td>
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
                const picker = document.getElementById('studentPicker');
                const rows = document.getElementById('participantRows');
                const empty = document.getElementById('emptyParticipants');
                const gradeLabels = { A: 'Sangat Baik', B: 'Baik', C: 'Cukup', D: 'Kurang' };

                function refreshRows() {
                    const participantRows = rows.querySelectorAll('.participant-row');
                    participantRows.forEach(function (row, index) {
                        row.querySelector('.row-number').textContent = index + 1;
                    });
                    if (empty) empty.style.display = participantRows.length ? 'none' : '';
                    if (picker) {
                        picker.querySelectorAll('option[data-name]').forEach(function (option) {
                            option.hidden = !!rows.querySelector('.participant-row[data-student-id=\"' + option.value + '\"]');
                        });
                    }
                }

                function addStudent(studentId, name, nisn) {
                    if (!studentId || rows.querySelector('.participant-row[data-student-id=\"' + studentId + '\"]')) return;
                    const row = document.createElement('tr');
                    row.className = 'participant-row';
                    row.dataset.studentId = studentId;
                    row.innerHTML = '<td class=\"row-number\"></td>' +
                        '<td><input type=\"hidden\" name=\"participant_ids[]\" value=\"' + studentId + '\">' +
                        '<input type=\"hidden\" name=\"records[' + studentId + '][student_id]\" value=\"' + studentId + '\">' +
                        '<b>' + name.replace(/&/g, '&amp;').replace(/</g, '&lt;') + '</b><br><span class=\"cs\">' + nisn + '</span></td>' +
                        '<td><select class=\"in\" name=\"records[' + studentId + '][grade]\"><option value=\"A\">A — ' + gradeLabels.A + '</option><option value=\"B\" selected>B — ' + gradeLabels.B + '</option><option value=\"C\">C — ' + gradeLabels.C + '</option><option value=\"D\">D — ' + gradeLabels.D + '</option></select></td>' +
                        '<td><input class=\"in\" name=\"records[' + studentId + '][description]\" placeholder=\"Keterangan (opsional)\" maxlength=\"255\"></td>' +
                        '<td><button type=\"button\" class=\"btn b-dan remove-student\" style=\"min-height:32px;padding:0 9px\">Hapus</button></td>';
                    rows.appendChild(row);
                    row.querySelector('.remove-student').addEventListener('click', function () {
                        row.remove();
                        refreshRows();
                    });
                    refreshRows();
                }

                document.getElementById('add-student')?.addEventListener('click', function () {
                    const option = picker.options[picker.selectedIndex];
                    if (!option?.value) return;
                    addStudent(option.value, option.dataset.name, option.dataset.nisn);
                    picker.value = '';
                });

                document.querySelectorAll('.remove-student').forEach(function (button) {
                    button.addEventListener('click', function () {
                        this.closest('.participant-row').remove();
                        refreshRows();
                    });
                });
                refreshRows();
            });
        </script>
    @endif
</x-layout.app>
