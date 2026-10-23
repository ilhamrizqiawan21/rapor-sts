<x-layout.app title="Nilai Sumatif LM — {{ $teachingAssignment->subject->name }} {{ $teachingAssignment->classroom->name }}" userRole="guru">
    <x-slot:topbarLeft>
        <a class="tl" href="{{ route('guru.dashboard') }}">Beranda</a>
        <a class="tl" href="{{ route('guru.bobot.edit', $teachingAssignment) }}">Bobot</a>
        <a class="tl on" href="{{ route('guru.penilaian.index', $teachingAssignment) }}">Penilaian</a>
    </x-slot:topbarLeft>

    <x-slot:breadcrumb>
        <a href="{{ route('guru.dashboard') }}">Home</a> / <a href="{{ route('guru.penilaian.index', $teachingAssignment) }}">Penilaian</a> / <span>Sumatif</span>
    </x-slot:breadcrumb>

    @if(session('success'))
        <x-alert variant="info">{{ session('success') }}</x-alert>
    @endif
    @if(session('error'))
        <x-alert variant="warning">{{ session('error') }}</x-alert>
    @endif
    @if($errors->any())
        <x-alert variant="warning">
            @foreach($errors->all() as $err)
                <div>{!! nl2br(e(str_replace("<br>", "\n", $err))) !!}</div>
            @endforeach
        </x-alert>
    @endif

    @php
        $isLocked = $teachingAssignment->isSubmitted() || $teachingAssignment->classroom->isLockedInPeriod($teachingAssignment->period_id);
    @endphp

    <div class="card">
        <div class="ch">
            <div>
                <div class="ct">Nilai Sumatif Lingkup Materi</div>
                <div class="cs">{{ $teachingAssignment->subject->name }} · {{ $teachingAssignment->classroom->name }} · sampai tengah semester</div>
            </div>
            <div class="acts grp">
                <a class="btn b-sec" href="{{ route('guru.penilaian.index', $teachingAssignment) }}">← Kembali</a>
                @if(! $isLocked)
                    <button type="button" class="btn b-pri" onclick="openAddModal()">+ Tambah LM</button>
                    <button type="button" class="btn b-suc" onclick="document.getElementById('modalUploadSumatif').showModal()">Upload</button>
                @endif
                <a class="btn b-war" href="{{ route('guru.sumatif.template', $teachingAssignment) }}">Export Template</a>
                <button type="button" class="btn b-inf" onclick="document.getElementById('modalExportRdmSumatif').showModal()">Export RDM</button>
            </div>
        </div>

        <div class="cb" style="display: flex; flex-direction: column; gap: 14px">
            <div class="tools">
                <label style="margin-left: auto; display: flex; align-items: center; gap: 6px">
                    Cari: <input class="in" style="width: 180px" type="search" id="searchSumatif" onkeyup="filterSumatif()" placeholder="Ketik nama / NISN...">
                </label>
            </div>

            <div class="tb">
                <table class="t" id="tableSumatif">
                    <thead>
                        <tr>
                            <th rowspan="2" style="width: 40px">No</th>
                            <th rowspan="2">NISN</th>
                            <th rowspan="2" style="text-align: left">Nama Siswa</th>
                            <th rowspan="2">L/P</th>
                            @if($scopes->isNotEmpty())
                                <th colspan="{{ $scopes->count() }}">Nilai per TP / Lingkup Materi</th>
                            @else
                                <th>Lingkup Materi</th>
                            @endif
                            <th rowspan="2">Rerata</th>
                        </tr>
                        <tr>
                            @forelse($scopes as $scope)
                                <th>
                                    <div class="lm" style="display: flex; align-items: center; justify-content: center; gap: 4px">
                                        <span>LM {{ $scope->sequence }}</span>
                                        @if(! $isLocked)
                                            <button type="button" class="ib" title="Edit LM {{ $scope->sequence }}" style="color: #1e7e34; padding: 2px; cursor: pointer"
                                                onclick='openEditLmModal(@json($scope), @json($scope->scores->pluck("score", "student_id")))'>
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20h4L19 9l-4-4L4 16zM14 6l4 4"/></svg>
                                            </button>
                                            <form method="POST" action="{{ route('guru.sumatif.destroy', [$teachingAssignment, $scope]) }}" style="display: inline"
                                                onsubmit="return confirm('Hapus LM {{ $scope->sequence }}? Nilai seluruh siswa pada LM ini akan ikut terhapus.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="ib" title="Hapus LM {{ $scope->sequence }}" style="color: #c82333; padding: 2px; cursor: pointer">
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6L6 18"/></svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                    <div class="cs" style="font-weight: normal; font-size: 11px" title="{{ $scope->summary }}">
                                        KKTP: {{ (float) $scope->kktp }}
                                    </div>
                                </th>
                            @empty
                                <th class="cs" style="font-weight: normal">- Belum ada LM -</th>
                            @endforelse
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $idx => $student)
                            <tr>
                                <td class="n">{{ $idx + 1 }}</td>
                                <td>{{ $student->nisn }}</td>
                                <td>{{ $student->name }}</td>
                                <td class="n">{{ $student->gender }}</td>

                                @forelse($scopes as $scope)
                                    @php
                                        $val = $matrix[$student->id][$scope->id] ?? null;
                                    @endphp
                                    <td class="n">
                                        @if($val !== null)
                                            <span style="{{ $val < $scope->kktp ? 'color: #c82333; font-weight: 600' : '' }}">{{ (float)$val }}</span>
                                        @else
                                            <span style="color: #6c757d">–</span>
                                        @endif
                                    </td>
                                @empty
                                    <td class="n cs">–</td>
                                @endforelse

                                <td class="n">
                                    <b>{{ $averages[$student->id] ?? '–' }}</b>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 5 + max(1, $scopes->count()) }}" class="n cs">Belum ada siswa aktif di kelas ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal Tambah / Edit LM (Mockup 11) --}}
    <x-modal id="modalLmForm" title="Tambah / Edit Nilai Sumatif">
        <form method="POST" id="formLm" action="">
            @csrf
            <div id="methodContainer"></div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 16px">
                <div style="display: flex; flex-direction: column; gap: 12px">
                    <div style="display: grid; grid-template-columns: 120px 1fr; gap: 10px; align-items: center">
                        <label for="inputSeq" style="color: #495057; font-weight: 600">TP/LM Ke-</label>
                        <input id="inputSeq" name="sequence" class="in" type="number" min="1" style="max-width: 100px" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 120px 1fr; gap: 10px; align-items: start">
                        <label for="inputSummary" style="color: #495057; font-weight: 600; padding-top: 6px">Ringkasan Materi</label>
                        <textarea id="inputSummary" name="summary" class="in" style="height: 80px; padding: 6px 10px; resize: vertical" placeholder="Diawali KKO (contoh: menganalisis tata cara bersuci...)" required></textarea>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 12px">
                    <div style="display: grid; grid-template-columns: 140px 1fr; gap: 10px; align-items: center">
                        <label for="inputKktp" style="color: #495057; font-weight: 600">KKTP LM</label>
                        <input id="inputKktp" name="kktp" class="in" type="number" step="0.01" min="0" max="100" style="max-width: 100px" oninput="updateIntervalDisplay()" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 140px 1fr; gap: 10px; align-items: start">
                        <span style="color: #495057; font-weight: 600">Interval</span>
                        <div id="intervalDisplay" style="display: grid; grid-template-columns: auto 1fr; gap: 2px 10px; font-size: 13px">
                            <!-- Diupdate otomatis via JS -->
                        </div>
                    </div>
                </div>
            </div>

            <div style="overflow-x: auto; max-height: 400px">
                <table class="t">
                    <thead>
                        <tr>
                            <th style="width: 36px">#</th>
                            <th>NISN</th>
                            <th>Nama Siswa</th>
                            <th>L/P</th>
                            <th style="min-width: 220px">
                                <div style="display: flex; align-items: center; gap: 8px">
                                    <span>Nilai (0–100)</span>
                                    <input type="number" step="0.01" min="0" max="100" id="fillAllInput" placeholder="Isi semua" class="in" style="height: 28px; width: 90px; padding: 2px 6px; font-size: 12px" oninput="fillAllScores(this.value)">
                                </div>
                            </th>
                            <th style="width: 120px">Ketercapaian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $idx => $st)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td>{{ $st->nisn }}</td>
                                <td><b>{{ $st->name }}</b></td>
                                <td>{{ $st->gender }}</td>
                                <td>
                                    <input type="number" step="0.01" min="0" max="100" name="scores[{{ $st->id }}]" id="score_{{ $st->id }}" class="in student-score-in" style="height: 30px; width: 90px" oninput="updateRowKetercapaian({{ $st->id }})">
                                </td>
                                <td>
                                    <span id="label_kc_{{ $st->id }}" class="kc cs">–</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </form>

        <x-slot:footer>
            <button type="button" class="btn b-sec" onclick="document.getElementById('modalLmForm').close()">Batal</button>
            <button type="button" class="btn b-pri" onclick="document.getElementById('formLm').submit()">Simpan LM & Nilai</button>
        </x-slot:footer>
    </x-modal>

    {{-- Modal Upload Excel Sumatif --}}
    <x-modal id="modalUploadSumatif" title="Upload Nilai Sumatif LM Excel">
        <form method="POST" action="{{ route('guru.sumatif.import', $teachingAssignment) }}" enctype="multipart/form-data" id="formUploadSumatif">
            @csrf
            <div class="alert a-lt" style="margin-bottom: 12px">
                Unggah file Excel hasil pengisian template. Satu file dapat memuat beberapa sheet (<code>LM1</code>, <code>LM2</code>, dst).
            </div>
            <div class="f">
                <label for="sumatifFile">Pilih File (.xlsx)</label>
                <input id="sumatifFile" name="file" type="file" accept=".xlsx" class="in" style="padding: 4px 8px" required>
            </div>
        </form>

        <x-slot:footer>
            <button type="button" class="btn b-sec" onclick="document.getElementById('modalUploadSumatif').close()">Batal</button>
            <button type="button" class="btn b-suc" onclick="document.getElementById('formUploadSumatif').submit()">Upload & Proses</button>
        </x-slot:footer>
    </x-modal>

    {{-- Modal Export RDM Sumatif --}}
    <x-modal id="modalExportRdmSumatif" title="Export Nilai Sumatif ke Template RDM">
        <div style="display: flex; flex-direction: column; gap: 16px">
            <div class="alert a-lt" style="margin: 0">
                Nilai Lingkup Materi (LM) dapat diisi langsung ke template Excel asli yang diunduh dari RDM madrasah Anda (agar token ID siswa RDM tetap cocok), atau diunduh langsung menggunakan format RDM v3.1.
            </div>

            <div style="background: var(--c-surface, #f8f9fa); border: 1px solid var(--c-border, #e9ecef); border-radius: 6px; padding: 12px">
                <h4 style="margin: 0 0 8px 0; font-size: 14px">Opsi 1: Isi Template Asli RDM (Direkomendasikan)</h4>
                <p class="cs" style="margin: 0 0 10px 0; font-size: 12px">
                    Unggah berkas template kosong yang Anda unduh dari menu Penilaian RDM resmi madrasah Anda. Sistem akan mengisi kolom Nilai (sheet SUM 1, SUM 2, dst) dan ringkasan materi secara otomatis.
                </p>
                <form method="POST" action="{{ route('guru.rdm.sumatif.fill', $teachingAssignment) }}" enctype="multipart/form-data" id="formFillRdmSumatif">
                    @csrf
                    <div class="f" style="margin-bottom: 10px">
                        <label for="rdmSumatifTemplateFile" style="font-size: 12px; font-weight: bold">Pilih File Template dari RDM (.xlsx)</label>
                        <input id="rdmSumatifTemplateFile" name="template_file" type="file" accept=".xlsx" class="in" style="padding: 4px 8px" required>
                    </div>
                    <button type="submit" class="btn b-pri" style="width: 100%">Isi & Unduh Template RDM</button>
                </form>
            </div>

            <div style="background: var(--c-surface, #f8f9fa); border: 1px solid var(--c-border, #e9ecef); border-radius: 6px; padding: 12px">
                <h4 style="margin: 0 0 8px 0; font-size: 14px">Opsi 2: Unduh Format RDM Terisi Langsung</h4>
                <p class="cs" style="margin: 0 0 10px 0; font-size: 12px">
                    Unduh berkas template RDM v3.1 yang telah terisi data siswa kelas ini dan seluruh nilai Lingkup Materi yang ada saat ini.
                </p>
                <a href="{{ route('guru.rdm.sumatif.export', $teachingAssignment) }}" class="btn b-war" style="display: block; text-align: center; text-decoration: none">
                    Unduh Langsung Format RDM
                </a>
            </div>
        </div>

        <x-slot:footer>
            <button type="button" class="btn b-sec" onclick="document.getElementById('modalExportRdmSumatif').close()">Tutup</button>
        </x-slot:footer>
    </x-modal>

    <script>
        function filterSumatif() {
            const query = document.getElementById('searchSumatif').value.toLowerCase();
            const rows = document.querySelectorAll('#tableSumatif tbody tr');
            rows.forEach(r => {
                r.style.display = r.innerText.toLowerCase().includes(query) ? '' : 'none';
            });
        }

        function openAddModal() {
            document.getElementById('formLm').action = '{{ route("guru.sumatif.store", $teachingAssignment) }}';
            document.getElementById('methodContainer').innerHTML = '';
            document.getElementById('inputSeq').value = '{{ $nextSequence }}';
            document.getElementById('inputSummary').value = '';
            document.getElementById('inputKktp').value = '{{ $defaultKktp }}';
            document.getElementById('fillAllInput').value = '';

            document.querySelectorAll('.student-score-in').forEach(inpt => {
                inpt.value = '';
            });
            document.querySelectorAll('[id^="label_kc_"]').forEach(el => {
                el.innerText = '–';
                el.style.background = '';
                el.style.color = '#6c757d';
            });

            updateIntervalDisplay();
            document.getElementById('modalLmForm').showModal();
        }

        function openEditLmModal(scope, scoresMap) {
            document.getElementById('formLm').action = '/guru/mengajar/{{ $teachingAssignment->id }}/sumatif/' + scope.id;
            document.getElementById('methodContainer').innerHTML = '<input type="hidden" name="_method" value="PUT">';
            document.getElementById('inputSeq').value = scope.sequence;
            document.getElementById('inputSummary').value = scope.summary;
            document.getElementById('inputKktp').value = parseFloat(scope.kktp);
            document.getElementById('fillAllInput').value = '';

            document.querySelectorAll('.student-score-in').forEach(inpt => {
                const sid = inpt.id.replace('score_', '');
                const val = scoresMap[sid] !== undefined && scoresMap[sid] !== null ? scoresMap[sid] : '';
                inpt.value = val;
                updateRowKetercapaian(sid);
            });

            updateIntervalDisplay();
            document.getElementById('modalLmForm').showModal();
        }

        function updateIntervalDisplay() {
            const k = parseFloat(document.getElementById('inputKktp').value) || 70;
            const pita = (100 - k) / 3;
            const kInt = Math.ceil(k);
            const b1 = Math.ceil(k + pita);
            const b2 = Math.ceil(k + (2 * pita));

            const html = `
                <b>Belum</b><span>: &lt; ${kInt}</span>
                <b>Cukup</b><span>: ${kInt} – ${b1 - 1}</span>
                <b>Baik</b><span>: ${b1} – ${b2 - 1}</span>
                <b>Sangat Baik</b><span>: ${b2} – 100</span>
            `;
            document.getElementById('intervalDisplay').innerHTML = html;

            document.querySelectorAll('.student-score-in').forEach(inpt => {
                const sid = inpt.id.replace('score_', '');
                updateRowKetercapaian(sid);
            });
        }

        function updateRowKetercapaian(sid) {
            const inpt = document.getElementById('score_' + sid);
            const label = document.getElementById('label_kc_' + sid);
            const valStr = inpt.value;
            const k = parseFloat(document.getElementById('inputKktp').value) || 70;
            const pita = (100 - k) / 3;

            if (valStr === '' || isNaN(valStr)) {
                label.innerText = '–';
                label.style.background = '';
                label.style.color = '#6c757d';
                return;
            }

            const val = parseFloat(valStr);
            if (val < k) {
                label.innerText = 'Belum Tercapai';
                label.style.background = '#f8d7da';
                label.style.color = '#842029';
            } else if (val < (k + pita)) {
                label.innerText = 'Cukup';
                label.style.background = '#fff3cd';
                label.style.color = '#664d03';
            } else if (val < (k + (2 * pita))) {
                label.innerText = 'Baik';
                label.style.background = '#d4edda';
                label.style.color = '#155724';
            } else {
                label.innerText = 'Sangat Baik';
                label.style.background = '#cfe2ff';
                label.style.color = '#0a3d8f';
            }
        }

        function fillAllScores(val) {
            document.querySelectorAll('.student-score-in').forEach(inpt => {
                if (inpt.value === '') {
                    inpt.value = val;
                    const sid = inpt.id.replace('score_', '');
                    updateRowKetercapaian(sid);
                }
            });
        }
    </script>
</x-layout.app>

