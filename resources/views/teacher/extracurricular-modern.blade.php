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

    @if($isLocked)
        <div class="alert" style="background: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; margin-bottom: 16px">
            <b>Perhatian:</b> Kelas {{ $classroom->name }} telah dikunci. Data ekstrakurikuler hanya dapat dilihat sampai kunci dibuka.
        </div>
    @endif

    <div class="card" style="margin-bottom: 20px">
        <div class="cb" style="padding: 20px">
            <!-- Top Header (Title, Subtitle & Action Buttons) -->
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 12px">
                <div>
                    <h2 style="margin: 0 0 4px 0; font-size: 24px; font-weight: 700; color: #2b354f; display: flex; align-items: center; gap: 8px">
                        {{ $selectedActivity->name }}
                        @if($isMandatory)
                            <span class="badge" style="background: #e8f4fd; color: #0d6efd; font-size: 12px; font-weight: 600; padding: 2px 8px; border-radius: 4px">Wajib</span>
                        @else
                            <span class="badge" style="background: #f1f3f5; color: #495057; font-size: 12px; font-weight: 600; padding: 2px 8px; border-radius: 4px">Pilihan</span>
                        @endif
                    </h2>
                    <div style="color: #6c757d; font-size: 14px">Pembina Ekstrakurikuler</div>
                </div>

                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap">
                    @if(! $isLocked && ! $isMandatory)
                        <button type="button" class="btn" onclick="openMemberModal()" style="background: #17a2b8; color: #fff; font-weight: 600; padding: 6px 14px; border-radius: 4px; border: none; cursor: pointer; display: flex; align-items: center; gap: 6px">
                            <svg width="15" height="15" fill="currentColor" viewBox="0 0 16 16"><path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-5.784 6A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/></svg>
                            Kelola Anggota
                        </button>
                    @endif

                    @if(! $isLocked)
                        <button type="submit" form="form-extracurricular" class="btn b-pri" style="font-weight: 600; padding: 6px 16px; border-radius: 4px; display: flex; align-items: center; gap: 6px">
                            <svg width="15" height="15" fill="currentColor" viewBox="0 0 16 16"><path d="M2 1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4.414A1 1 0 0 0 14.707 3.707l-2.414-2.414A1 1 0 0 0 11.586 1H2zm2 1h7v3H4V2zm8 12H4V8h8v6z"/></svg>
                            Simpan
                        </button>
                        <button type="button" class="btn b-suc" onclick="openUploadModal()" style="font-weight: 600; padding: 6px 14px; border-radius: 4px; display: flex; align-items: center; gap: 6px">
                            <svg width="15" height="15" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V11.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708l3-3z"/></svg>
                            Upload
                        </button>
                    @endif

                    <a href="{{ route('guru.ekstrakurikuler.export', ['activity_id' => $selectedActivityId, 'classroom_id' => $classroom->id]) }}" class="btn b-war" style="font-weight: 600; padding: 6px 14px; border-radius: 4px; display: flex; align-items: center; gap: 6px; text-decoration: none">
                        <svg width="15" height="15" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/></svg>
                        Export
                    </a>
                </div>
            </div>

            <!-- Filter Controls (Kegiatan, Tingkat, Kelas) -->
            <form id="filterForm" method="GET" action="{{ route('guru.ekstrakurikuler.index') }}" style="display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end; margin-bottom: 24px; background: #fafbfc; padding: 14px 16px; border-radius: 6px; border: 1px solid #edf0f2">
                @if($activities->count() > 1)
                    <div style="display: flex; flex-direction: column; gap: 4px">
                        <label for="activityFilter" style="font-weight: 600; font-size: 13px; color: #495057">Kegiatan:</label>
                        <select id="activityFilter" name="activity_id" class="sel" onchange="document.getElementById('filterForm').submit()" style="min-width: 180px">
                            @foreach($activities as $act)
                                <option value="{{ $act->id }}" @selected($act->id === $selectedActivityId)>
                                    {{ $act->name }} ({{ $act->typeLabel() }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <input type="hidden" name="activity_id" value="{{ $selectedActivityId }}">
                @endif

                <div style="display: flex; flex-direction: column; gap: 4px; min-width: 130px">
                    <label for="levelFilter" style="font-weight: 600; font-size: 13px; color: #495057">Tingkat</label>
                    <select id="levelFilter" name="level" class="sel" onchange="document.getElementById('filterForm').submit()">
                        @foreach($levels as $lvl)
                            <option value="{{ $lvl }}" @selected((int)$lvl === (int)$selectedLevel)>
                                {{ \App\Models\MadrasahProfile::classLevelRoman((int)$lvl) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 4px; min-width: 180px">
                    <label for="classroomFilter" style="font-weight: 600; font-size: 13px; color: #495057">Kelas</label>
                    <select id="classroomFilter" name="classroom_id" class="sel" onchange="document.getElementById('filterForm').submit()">
                        @foreach($filteredClassrooms as $availableClassroom)
                            <option value="{{ $availableClassroom->id }}" @selected($availableClassroom->id === $classroom->id)>
                                {{ $availableClassroom->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>

            <!-- Table Action Toolbar (Copy, Print, Excel, Search) -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px">
                <div style="display: flex; gap: 4px">
                    <button type="button" class="btn b-sec" onclick="copyTableToClipboard()" style="padding: 4px 10px; font-size: 13px; border: 1px solid #ced4da; background: #fff; color: #495057">Copy</button>
                    <button type="button" class="btn b-sec" onclick="window.print()" style="padding: 4px 10px; font-size: 13px; border: 1px solid #ced4da; background: #fff; color: #495057">Print</button>
                    <a href="{{ route('guru.ekstrakurikuler.export', ['activity_id' => $selectedActivityId, 'classroom_id' => $classroom->id]) }}" class="btn b-sec" style="padding: 4px 10px; font-size: 13px; border: 1px solid #ced4da; background: #fff; color: #495057; text-decoration: none">Excel</a>
                </div>
                <div style="display: flex; align-items: center; gap: 6px">
                    <label for="tableSearch" style="font-weight: 600; font-size: 13px; color: #495057">Search:</label>
                    <input type="text" id="tableSearch" class="in" placeholder="" style="padding: 4px 8px; font-size: 13px; width: 180px">
                </div>
            </div>

            <!-- Scoring Form & Table -->
            <form method="POST" action="{{ route('guru.ekstrakurikuler.update') }}" id="form-extracurricular">
                @csrf
                <input type="hidden" name="activity_id" value="{{ $selectedActivityId }}">
                <input type="hidden" name="classroom_id" value="{{ $classroom->id }}">

                <div class="tb">
                    <table class="t" id="scoresTable">
                        <thead>
                            <tr>
                                <th style="width: 45px; text-align: center">No</th>
                                <th style="width: 120px; text-align: center">NISN</th>
                                <th>Nama</th>
                                <th style="width: 50px; text-align: center">L/P</th>
                                <th style="width: 170px; text-align: center">
                                    <div style="display: flex; flex-direction: column; align-items: center; gap: 4px">
                                        <div>Nilai</div>
                                        @if(! $isLocked)
                                            <select id="bulkGradeSelect" class="sel" style="padding: 2px 6px; font-size: 12px; width: 100%; max-width: 130px; font-weight: normal; background-color: #fff">
                                                <option value="">-Pilih-</option>
                                                <option value="A">A — Sangat Baik</option>
                                                <option value="B">B — Baik</option>
                                                <option value="C">C — Cukup</option>
                                                <option value="D">D — Kurang</option>
                                            </select>
                                        @endif
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="participantRows">
                            @forelse($participants as $index => $student)
                                @php
                                    $rec = $records->get($student->id);
                                    $currentGrade = $rec?->predicate;
                                @endphp
                                <tr class="student-row" data-search="{{ strtolower($student->name.' '.$student->nisn) }}">
                                    <td style="text-align: center" class="row-num">{{ $index + 1 }}</td>
                                    <td style="text-align: center; font-family: monospace">{{ $student->nisn }}</td>
                                    <td>
                                        <input type="hidden" name="participant_ids[]" value="{{ $student->id }}">
                                        <input type="hidden" name="records[{{ $student->id }}][student_id]" value="{{ $student->id }}">
                                        <span style="font-weight: 600">{{ $student->name }}</span>
                                    </td>
                                    <td style="text-align: center">{{ $student->gender ?? '-' }}</td>
                                    <td style="text-align: center">
                                        <select class="sel row-grade-select" name="records[{{ $student->id }}][grade]" style="padding: 4px 8px; font-size: 13px; width: 100%; max-width: 140px" {{ $isLocked ? 'disabled' : '' }}>
                                            <option value="">-Pilih-</option>
                                            <option value="A" @selected($currentGrade === 'A')>A — Sangat Baik</option>
                                            <option value="B" @selected($currentGrade === 'B')>B — Baik</option>
                                            <option value="C" @selected($currentGrade === 'C')>C — Cukup</option>
                                            <option value="D" @selected($currentGrade === 'D')>D — Kurang</option>
                                        </select>
                                    </td>
                                </tr>
                            @empty
                                <tr id="emptyParticipants">
                                    <td colspan="5" style="text-align: center; padding: 28px; color: #6c757d">
                                        Belum ada peserta pada kegiatan ini di kelas {{ $classroom->name }}.
                                        @if(! $isMandatory && ! $isLocked)
                                            <div style="margin-top: 10px">
                                                <button type="button" class="btn" onclick="openMemberModal()" style="background: #17a2b8; color: #fff; font-size: 13px; padding: 5px 14px; border: none; border-radius: 4px; cursor: pointer">
                                                    Pilih Anggota Siswa Sekarang
                                                </button>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Footer Info & Simpan Button -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px; flex-wrap: wrap; gap: 12px">
                    <div style="color: #6c757d; font-size: 13px" id="entriesInfo">
                        Showing 1 to {{ $participants->count() }} of {{ $participants->count() }} entries
                    </div>
                    @if(! $isLocked && $participants->isNotEmpty())
                        <button type="submit" class="btn b-pri" style="font-weight: 600; padding: 6px 18px; border-radius: 4px">
                            Simpan
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Upload Excel Modal -->
    <div id="uploadModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1050; align-items: center; justify-content: center; padding: 16px">
        <div style="background: #fff; border-radius: 8px; max-width: 480px; width: 100%; box-shadow: 0 4px 16px rgba(0,0,0,0.15); overflow: hidden">
            <div style="padding: 16px 20px; border-bottom: 1px solid #dee2e6; display: flex; justify-content: space-between; align-items: center">
                <h3 style="margin: 0; font-size: 17px; font-weight: 600; color: #212529">Upload Nilai Ekstrakurikuler RDM</h3>
                <button type="button" onclick="closeUploadModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #6c757d">&times;</button>
            </div>
            <form method="POST" action="{{ route('guru.ekstrakurikuler.import') }}" enctype="multipart/form-data" style="padding: 20px">
                @csrf
                <input type="hidden" name="activity_id" value="{{ $selectedActivityId }}">
                <input type="hidden" name="classroom_id" value="{{ $classroom->id }}">

                <div style="margin-bottom: 16px; font-size: 13px; color: #495057; line-height: 1.5; background: #e8f4fd; padding: 12px; border-radius: 6px">
                    <b>Petunjuk Pengisian:</b><br>
                    1. Unduh template Excel melalui tombol <b>Export</b> terlebih dahulu.<br>
                    2. Masukkan predikat nilai (A / B / C / D) pada kolom Nilai.<br>
                    3. Unggah kembali file Excel yang telah diisi di bawah ini.
                </div>

                <div style="margin-bottom: 20px">
                    <label for="templateFileInput" style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px">Pilih File Excel (.xlsx / .xls):</label>
                    <input type="file" id="templateFileInput" name="template_file" accept=".xlsx, .xls" required class="in" style="width: 100%">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px">
                    <button type="button" onclick="closeUploadModal()" class="btn b-sec" style="padding: 6px 14px">Batal</button>
                    <button type="submit" class="btn b-suc" style="padding: 6px 16px; font-weight: 600">Unggah Nilai</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Kelola Anggota Modal (Khusus Pilihan / Tidak Wajib) -->
    @if(! $isMandatory)
        <div id="memberModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1050; align-items: center; justify-content: center; padding: 16px">
            <div style="background: #fff; border-radius: 8px; max-width: 620px; width: 100%; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 4px 16px rgba(0,0,0,0.15)">
                <div style="padding: 16px 20px; border-bottom: 1px solid #dee2e6; display: flex; justify-content: space-between; align-items: center">
                    <div>
                        <h3 style="margin: 0; font-size: 17px; font-weight: 600; color: #212529">Kelola Anggota: {{ $selectedActivity->name }}</h3>
                        <div style="font-size: 13px; color: #6c757d">Kelas {{ $classroom->name }}</div>
                    </div>
                    <button type="button" onclick="closeMemberModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #6c757d">&times;</button>
                </div>
                <form method="POST" action="{{ route('guru.ekstrakurikuler.members.update') }}" style="display: flex; flex-direction: column; flex: 1; overflow: hidden">
                    @csrf
                    <input type="hidden" name="activity_id" value="{{ $selectedActivityId }}">
                    <input type="hidden" name="classroom_id" value="{{ $classroom->id }}">

                    <div style="padding: 12px 20px; background: #fafbfc; border-bottom: 1px solid #edf0f2; display: flex; justify-content: space-between; align-items: center">
                        <span style="font-size: 13px; color: #495057">Centang siswa yang mengikuti kegiatan ini:</span>
                        <div style="display: flex; gap: 6px">
                            <button type="button" class="btn b-sec" onclick="checkAllMembers(true)" style="padding: 3px 8px; font-size: 12px">Pilih Semua</button>
                            <button type="button" class="btn b-sec" onclick="checkAllMembers(false)" style="padding: 3px 8px; font-size: 12px">Batal Pilih</button>
                        </div>
                    </div>

                    <div style="padding: 12px 20px; overflow-y: auto; flex: 1">
                        <table class="t" style="margin: 0">
                            <thead>
                                <tr>
                                    <th style="width: 36px; text-align: center">Pilih</th>
                                    <th style="width: 110px">NISN</th>
                                    <th>Nama Siswa</th>
                                    <th style="width: 40px; text-align: center">L/P</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($enrolledStudents as $enrolledStudent)
                                    @php
                                        $isMember = $records->has($enrolledStudent->id);
                                    @endphp
                                    <tr>
                                        <td style="text-align: center">
                                            <input type="checkbox" name="member_ids[]" value="{{ $enrolledStudent->id }}" class="member-checkbox" @checked($isMember) style="cursor: pointer; width: 16px; height: 16px">
                                        </td>
                                        <td style="font-family: monospace">{{ $enrolledStudent->nisn }}</td>
                                        <td><b>{{ $enrolledStudent->name }}</b></td>
                                        <td style="text-align: center">{{ $enrolledStudent->gender ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div style="padding: 14px 20px; border-top: 1px solid #dee2e6; display: flex; justify-content: flex-end; gap: 8px">
                        <button type="button" onclick="closeMemberModal()" class="btn b-sec" style="padding: 6px 14px">Batal</button>
                        <button type="submit" class="btn b-pri" style="padding: 6px 18px; font-weight: 600">Simpan Anggota</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <script>
        // Modal functions
        function openUploadModal() {
            const m = document.getElementById('uploadModal');
            if (m) m.style.display = 'flex';
        }
        function closeUploadModal() {
            const m = document.getElementById('uploadModal');
            if (m) m.style.display = 'none';
        }
        function openMemberModal() {
            const m = document.getElementById('memberModal');
            if (m) m.style.display = 'flex';
        }
        function closeMemberModal() {
            const m = document.getElementById('memberModal');
            if (m) m.style.display = 'none';
        }
        function checkAllMembers(check) {
            document.querySelectorAll('.member-checkbox').forEach(function(cb) {
                cb.checked = check;
            });
        }

        // Bulk grade select in table header
        document.getElementById('bulkGradeSelect')?.addEventListener('change', function() {
            const val = this.value;
            if (!val) return;
            document.querySelectorAll('.row-grade-select').forEach(function(select) {
                if (!select.disabled) {
                    select.value = val;
                }
            });
        });

        // Search filter
        document.getElementById('tableSearch')?.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('.student-row');
            let visibleCount = 0;

            rows.forEach(function(row) {
                const text = row.getAttribute('data-search') || '';
                if (text.includes(query)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Re-number visible rows
            let num = 1;
            rows.forEach(function(row) {
                if (row.style.display !== 'none') {
                    const numCell = row.querySelector('.row-num');
                    if (numCell) numCell.textContent = num++;
                }
            });

            const info = document.getElementById('entriesInfo');
            if (info) {
                info.textContent = 'Showing 1 to ' + visibleCount + ' of ' + rows.length + ' entries' + (query ? ' (filtered)' : '');
            }
        });

        // Copy Table to Clipboard
        function copyTableToClipboard() {
            const rows = document.querySelectorAll('#scoresTable tr');
            let text = '';
            rows.forEach(function(row) {
                if (row.style.display === 'none') return;
                const cols = row.querySelectorAll('th, td');
                const rowData = [];
                cols.forEach(function(col) {
                    const select = col.querySelector('select');
                    if (select) {
                        const opt = select.options[select.selectedIndex];
                        rowData.push(opt ? opt.text.trim() : '');
                    } else {
                        rowData.push(col.innerText.trim());
                    }
                });
                text += rowData.join('\t') + '\n';
            });

            navigator.clipboard.writeText(text).then(function() {
                alert('Data tabel berhasil disalin ke clipboard.');
            }).catch(function() {
                alert('Gagal menyalin ke clipboard.');
            });
        }
    </script>
</x-layout.app>
