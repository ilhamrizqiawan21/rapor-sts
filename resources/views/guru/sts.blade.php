<x-layout.app title="Nilai STS — {{ $teachingAssignment->subject->name }} {{ $teachingAssignment->classroom->name }}" userRole="guru">
    <x-slot:topbarLeft>
        <a class="tl" href="{{ route('guru.dashboard') }}">Beranda</a>
        <a class="tl" href="{{ route('guru.bobot.edit', $teachingAssignment) }}">Bobot</a>
        <a class="tl on" href="{{ route('guru.penilaian.index', $teachingAssignment) }}">Penilaian</a>
    </x-slot:topbarLeft>

    <x-slot:breadcrumb>
        <a href="{{ route('guru.dashboard') }}">Home</a> / <a href="{{ route('guru.penilaian.index', $teachingAssignment) }}">Penilaian</a> / <span>STS</span>
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
                <div>{!! $err !!}</div>
            @endforeach
        </x-alert>
    @endif

    @php
        $isLocked = $teachingAssignment->isSubmitted() || $teachingAssignment->classroom->isLockedInPeriod($teachingAssignment->period_id);
        $kktpVal = (float) $teachingAssignment->kktp;
    @endphp

    <div class="card">
        <div class="ch">
            <div>
                <div class="ct">Nilai Sumatif Tengah Semester (STS)</div>
                <div class="cs">{{ $teachingAssignment->subject->name }} · {{ $teachingAssignment->classroom->name }}</div>
            </div>
            <div class="acts grp">
                <a class="btn b-dan" href="{{ route('guru.penilaian.index', $teachingAssignment) }}">← Kembali</a>
                <a class="btn b-war" href="{{ route('guru.sts.template', $teachingAssignment) }}">Template Excel</a>
                @if(! $isLocked)
                    <button type="button" class="btn b-suc" onclick="document.getElementById('modalUploadSts').showModal()">Upload</button>
                    <button type="button" class="btn b-pri" onclick="document.getElementById('formSts').submit()">Simpan</button>
                @endif
            </div>
        </div>

        <div class="cb" style="display: flex; flex-direction: column; gap: 14px">
            <div class="alert a-lav" style="margin: 0">
                Isi nilai tes STS langsung di tabel, atau unduh <b>Template Excel</b>, isi, lalu <b>Upload</b>. Nilai Batas Ketercapaian STS: <b>{{ $kktpVal }}</b>. Kosongkan baris jika siswa belum mengikuti tes STS.
            </div>

            <form method="POST" action="{{ route('guru.sts.update', $teachingAssignment) }}" id="formSts">
                @csrf
                @method('PUT')

                <div class="tb">
                    <table class="t">
                        <thead>
                            <tr>
                                <th style="width: 36px">#</th>
                                <th>NISN</th>
                                <th>Nama Siswa</th>
                                <th>L/P</th>
                                <th style="width: 200px">Nilai STS (0–100)</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $idx => $student)
                                @php
                                    $scoreModel = $scores->get($student->id);
                                    $scoreVal = $scoreModel?->score !== null ? (float) $scoreModel->score : null;
                                @endphp
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td>{{ $student->nisn }}</td>
                                    <td><b>{{ $student->name }}</b></td>
                                    <td>{{ $student->gender }}</td>
                                    <td>
                                        <input type="number" step="0.01" min="0" max="100" name="scores[{{ $student->id }}]" id="sts_{{ $student->id }}"
                                            class="in sts-score-in" style="height: 32px; width: 110px"
                                            value="{{ old("scores.{$student->id}", $scoreVal) }}"
                                            oninput="updateStsKet({{ $student->id }})"
                                            @disabled($isLocked)>
                                    </td>
                                    <td>
                                        <span id="ket_{{ $student->id }}">
                                            @if($scoreVal !== null)
                                                @if($scoreVal >= $kktpVal)
                                                    <span style="color: #1e7e34; font-weight: 600">Tercapai</span>
                                                @else
                                                    <span style="color: #c82333; font-weight: 600">Belum Tercapai</span>
                                                @endif
                                            @else
                                                <span style="color: #c82333; font-weight: 600">Belum diisi</span>
                                            @endif
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="n cs">Belum ada siswa aktif di kelas ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(! $isLocked && $students->isNotEmpty())
                    <div style="display: flex; justify-content: flex-end; margin-top: 16px">
                        <button type="submit" class="btn b-pri">Simpan Nilai STS</button>
                    </div>
                @endif
            </form>
        </div>
    </div>

    {{-- Modal Upload Excel STS --}}
    <x-modal id="modalUploadSts" title="Upload Nilai STS Excel">
        <form method="POST" action="{{ route('guru.sts.import', $teachingAssignment) }}" enctype="multipart/form-data" id="formUploadSts">
            @csrf
            <div class="alert a-lt" style="margin-bottom: 12px">
                Unggah file Excel hasil pengisian template nilai STS. Nilai siswa akan otomatis tersimpan.
            </div>
            <div class="f">
                <label for="stsFile">Pilih File (.xlsx)</label>
                <input id="stsFile" name="file" type="file" accept=".xlsx" class="in" style="padding: 4px 8px" required>
            </div>
        </form>

        <x-slot:footer>
            <button type="button" class="btn b-dan" onclick="document.getElementById('modalUploadSts').close()">Batal</button>
            <button type="button" class="btn b-suc" onclick="document.getElementById('formUploadSts').submit()">Upload & Proses</button>
        </x-slot:footer>
    </x-modal>

    <script>
        function updateStsKet(sid) {
            const inpt = document.getElementById('sts_' + sid);
            const label = document.getElementById('ket_' + sid);
            const valStr = inpt.value;
            const k = {{ $kktpVal }};

            if (valStr === '' || isNaN(valStr)) {
                label.innerHTML = '<span style="color: #c82333; font-weight: 600">Belum diisi</span>';
                return;
            }

            const val = parseFloat(valStr);
            if (val >= k) {
                label.innerHTML = '<span style="color: #1e7e34; font-weight: 600">Tercapai</span>';
            } else {
                label.innerHTML = '<span style="color: #c82333; font-weight: 600">Belum Tercapai</span>';
            }
        }
    </script>
</x-layout.app>
