<x-layout.app title="Penilaian STS — {{ $teachingAssignment->subject->name }} {{ $teachingAssignment->classroom->name }}" userRole="guru">
    <x-slot:topbarLeft>
        <a class="tl" href="{{ route('guru.dashboard') }}">Beranda</a>
        <a class="tl" href="{{ route('guru.bobot.edit', $teachingAssignment) }}">Bobot</a>
        <a class="tl on" href="{{ route('guru.penilaian.index', $teachingAssignment) }}">Penilaian</a>
    </x-slot:topbarLeft>

    <x-slot:breadcrumb>
        <a href="{{ route('guru.dashboard') }}">Home</a> / <a href="#">Guru</a> / <span>Penilaian</span>
    </x-slot:breadcrumb>

    @if(session('success'))
        <x-alert variant="info">{{ session('success') }}</x-alert>
    @endif
    @if(session('error'))
        <x-alert variant="warning">{{ session('error') }}</x-alert>
    @endif

    @php
        $isSubmitted = $teachingAssignment->isSubmitted();
        $isLocked = $isSubmitted || $teachingAssignment->classroom->isLockedInPeriod($teachingAssignment->period_id);
    @endphp

    <div class="card">
        <div class="ch">
            <div>
                <div class="ct">Penilaian Sumatif Tengah Semester</div>
                <div class="cs">
                    {{ $teachingAssignment->subject->name }} · {{ $teachingAssignment->classroom->name }} · Kurikulum {{ ucfirst($teachingAssignment->classroom->curriculum->value) }} · Bobot Sumatif {{ (float)$teachingAssignment->weight_summative }}% : STS {{ (float)$teachingAssignment->weight_sts }}%
                </div>
            </div>

            <div class="acts grp">
                <a class="btn b-pri" href="{{ route('guru.sumatif.index', $teachingAssignment) }}">Sumatif</a>
                <a class="btn b-inf" href="{{ route('guru.sts.index', $teachingAssignment) }}">STS</a>

                @if($isSubmitted)
                    <span class="btn b-suc" style="cursor: default" title="Terkirim pada {{ $teachingAssignment->submitted_at->format('d/m/Y H:i') }}">✓ Terkirim</span>
                @else
                    <form method="POST" action="{{ route('guru.penilaian.submit', $teachingAssignment) }}" style="display: inline"
                        onsubmit="return confirmSubmission({{ $sheet->hasEmptySummativeCells ? 'true' : 'false' }})">
                        @csrf
                        <button type="submit" class="btn b-suc" @disabled(! $sheet->isAllComplete)
                            title="{{ ! $sheet->isAllComplete ? 'Lengkapi nilai seluruh siswa aktif terlebih dahulu untuk mengirim' : 'Kirim nilai ke wali kelas' }}">
                            Kirim Nilai
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="cb" style="display: flex; flex-direction: column; gap: 14px">
            @if($isSubmitted)
                <div class="alert a-lt">
                    <b>Nilai Terkirim:</b> Nilai mapel ini telah dikirim ke wali kelas pada {{ $teachingAssignment->submitted_at?->format('d F Y, H:i') }}. Perubahan nilai terkunci dan hanya dapat dibuka kembali oleh wali kelas atau operator.
                </div>
            @elseif(! $sheet->isAllComplete)
                <div class="alert a-lt">
                    <b>Perhatian:</b> Masih terdapat <b>{{ $sheet->incompleteCount }}</b> siswa yang belum memiliki nilai rapor STS lengkap. Tombol <b>Kirim Nilai</b> akan aktif setelah semua siswa memiliki nilai lengkap.
                </div>
            @else
                <div class="alert a-lt">
                    <b>Semua nilai telah lengkap!</b> Silakan periksa kembali capaian kompetensi siswa, lalu klik <b>Kirim Nilai</b> untuk menyerahkan nilai kepada wali kelas.
                </div>
            @endif

            {{-- Toolbar Filter Search --}}
            <div class="tools">
                <label style="margin-left: auto; display: flex; align-items: center; gap: 6px">
                    Cari Siswa: <input class="in" style="width: 200px" type="search" id="searchTable" onkeyup="filterTable()" placeholder="Ketik nama / NISN...">
                </label>
            </div>

            <div class="tb">
                <table class="t" id="scoreTable">
                    <thead>
                        <tr>
                            <th style="width: 36px">#</th>
                            <th>NISN</th>
                            <th>Nama Siswa</th>
                            <th class="n">Rerata<br>Sumatif</th>
                            <th class="n">STS</th>
                            <th class="n">Nilai<br>Rapor STS</th>
                            <th style="min-width: 320px">Capaian Kompetensi</th>
                            <th style="width: 100px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sheet->rows as $idx => $row)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td>{{ $row->student->nisn }}</td>
                                <td><b>{{ $row->student->name }}</b></td>
                                <td class="n">{{ $row->getFormattedSummativeAverage() }}</td>
                                <td class="n">{{ $row->getFormattedStsScore() }}</td>
                                <td class="n">
                                    @if($row->finalScore !== null)
                                        <b>{{ $row->finalScore }}</b>
                                    @else
                                        <b style="color: #c82333">–</b>
                                    @endif
                                </td>
                                <td>
                                    @if($row->description)
                                        <span>{{ $row->description }}</span>
                                        @if($row->isDescriptionOverridden)
                                            <span class="pill" style="background: #e0a10e; color: #212529; font-size: 10px; margin-left: 4px">Diedit</span>
                                        @endif
                                    @else
                                        <span style="color: #6c757d; font-style: italic">{{ $row->statusLabel }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if(! $isLocked)
                                        <div style="display: flex; gap: 4px; flex-wrap: wrap">
                                            <button type="button" class="btn b-pri" style="min-height: 26px; padding: 0 6px; font-size: 11px"
                                                onclick='openOverrideModal({{ $row->student->id }}, {{ json_encode($row->student->name) }}, {{ json_encode($row->description ?? "") }})'>
                                                Edit
                                            </button>
                                            @if($row->isDescriptionOverridden)
                                                <form method="POST" action="{{ route('guru.capaian.destroy', [$teachingAssignment, $row->student]) }}" style="display: inline"
                                                    onsubmit="return confirm('Kembalikan deskripsi siswa {{ addslashes($row->student->name) }} ke kalimat otomatis?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn b-war" style="min-height: 26px; padding: 0 6px; font-size: 11px" title="Reset ke otomatis">
                                                        Reset
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @else
                                        <span class="cs">- Terkunci -</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="n cs">Belum ada data siswa terdaftar di kelas ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal Edit Override Capaian (FR-NIL-09) --}}
    <x-modal id="modalOverride" title="Edit Manual Capaian Kompetensi">
        <form method="POST" id="formOverride" action="">
            @csrf
            @method('PUT')
            <p id="overrideStudentName" style="margin: 0 0 10px; font-weight: 600"></p>
            <div class="f">
                <label for="overrideDescription">Deskripsi Capaian (Maksimal 300 Karakter)</label>
                <textarea id="overrideDescription" name="description" class="in" style="height: 100px; padding: 8px 10px; resize: vertical" maxlength="300" required></textarea>
                <span class="cs" id="charCounter" style="display: block; text-align: right; margin-top: 4px">0 / 300</span>
            </div>
        </form>

        <x-slot:footer>
            <button type="button" class="btn b-dan" onclick="document.getElementById('modalOverride').close()">Batal</button>
            <button type="button" class="btn b-pri" onclick="document.getElementById('formOverride').submit()">Simpan Capaian</button>
        </x-slot:footer>
    </x-modal>

    <script>
        function confirmSubmission(hasEmptySummative) {
            if (hasEmptySummative) {
                return confirm('PERINGATAN: Terdapat nilai sumatif siswa yang masih kosong.\nApakah Anda yakin ingin tetap mengirim nilai rapor ini ke wali kelas? Setelah dikirim nilai akan terkunci.');
            }
            return confirm('Apakah Anda yakin ingin mengirim nilai rapor ini ke wali kelas?\nSetelah dikirim, nilai akan terkunci untuk guru.');
        }

        function filterTable() {
            const query = document.getElementById('searchTable').value.toLowerCase();
            const rows = document.querySelectorAll('#scoreTable tbody tr');
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        }

        function openOverrideModal(studentId, studentName, currentDesc) {
            document.getElementById('formOverride').action = '/guru/mengajar/{{ $teachingAssignment->id }}/capaian/' + studentId;
            document.getElementById('overrideStudentName').innerText = 'Siswa: ' + studentName;
            document.getElementById('overrideDescription').value = currentDesc;
            updateCharCount();
            document.getElementById('modalOverride').showModal();
        }

        const descInput = document.getElementById('overrideDescription');
        if (descInput) {
            descInput.addEventListener('input', updateCharCount);
        }

        function updateCharCount() {
            const len = document.getElementById('overrideDescription').value.length;
            document.getElementById('charCounter').innerText = len + ' / 300';
        }
    </script>
</x-layout.app>

