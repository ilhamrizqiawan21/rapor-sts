<x-layout.app title="Data Kelas" userRole="operator">
    <x-slot:sidebar>
        <x-sidebar.operator active="classrooms" />
    </x-slot:sidebar>

    <x-slot:breadcrumb>
        <a href="{{ route('operator.dashboard') }}">Home</a> / <a href="#">Admin</a> / <span>Data Kelas</span>
    </x-slot:breadcrumb>

    @php($firstClassLevel = (int) array_key_first($gradeLevels))

    @if(session('success'))
        <x-alert variant="info">{{ session('success') }}</x-alert>
    @endif
    @if(session('error'))
        <x-alert variant="warning">{{ session('error') }}</x-alert>
    @endif
    @if($errors->any())
        <x-alert variant="warning">{{ $errors->first() }}</x-alert>
    @endif

    <x-card title="Data Kelas Madrasah">
        <x-slot:actions>
            <div class="grp">
                <button type="button" class="btn b-pri" onclick="document.getElementById('modalTambahKelas').showModal()">+ Tambah Kelas</button>
                <button type="button" class="btn b-inf" onclick="document.getElementById('modalTambahSekaligus').showModal()">Tambah Sekaligus</button>
            </div>
        </x-slot:actions>

        <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 16px">
            <label for="yearFilter" style="font-weight: 600">Tahun Pelajaran:</label>
            <select id="yearFilter" class="sel" onchange="location.href = '{{ route('operator.classrooms.index') }}?academic_year_id=' + this.value">
                @foreach($academicYears as $y)
                    <option value="{{ $y->id }}" @selected($selectedYearId == $y->id)>{{ $y->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="tb">
            <table class="t">
                <thead>
                    <tr>
                        <th style="width: 40px">#</th>
                        <th>Tingkat</th>
                        <th>Nama Kelas</th>
                        <th>Kurikulum</th>
                        <th>Fase</th>
                        <th>Wali Kelas</th>
                        <th>Jumlah Siswa</th>
                        <th style="width: 100px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($classrooms as $idx => $c)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td>Tingkat {{ $c->level }}</td>
                            <td><b>{{ $c->name }}</b></td>
                            <td>{{ $c->curriculum->label() }}</td>
                            <td>{{ $c->phase ?? '-' }}</td>
                            <td>
                                <form method="POST" action="{{ route('operator.classrooms.homeroom.update', $c) }}">
                                    @csrf
                                    @method('PATCH')
                                    <select name="homeroom_teacher_id" class="sel" aria-label="Wali kelas {{ $c->name }}" onchange="this.form.requestSubmit()">
                                        @if($c->homeroomTeacher && !$teachers->contains('id', $c->homeroom_teacher_id))
                                            <option value="{{ $c->homeroom_teacher_id }}" selected disabled>{{ $c->homeroomTeacher->name }} (tidak aktif)</option>
                                        @endif
                                        <option value="" @selected(!$c->homeroom_teacher_id)>Belum ditetapkan</option>
                                        @foreach($teachers as $t)
                                            <option value="{{ $t->id }}" @selected($c->homeroom_teacher_id === $t->id)>{{ $t->name }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td>{{ $c->enrollments_count }} Siswa</td>
                            <td>
                                <form method="POST" action="{{ route('operator.classrooms.destroy', $c) }}" onsubmit="return confirm('Hapus kelas {{ $c->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn b-dan" style="min-height: 28px; padding: 0 8px; font-size: 12px">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="n cs">Belum ada kelas pada tahun pelajaran ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    {{-- Modal Tambah Satu Kelas --}}
    <x-modal id="modalTambahKelas" title="Tambah Data Kelas">
        <form method="POST" action="{{ route('operator.classrooms.store') }}" id="formTambahKelas">
            @csrf
            <input type="hidden" name="academic_year_id" value="{{ $selectedYearId }}">
            <div class="f">
                <label for="level">Tingkat</label>
                <select id="level" name="level" class="in" required>
                    @foreach($gradeLevels as $level => $label)
                        <option value="{{ $level }}" @selected((int) $level === $firstClassLevel)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="f">
                <label for="cname">Nama Kelas (contoh: <span id="classNameExample">{{ $classLevelConfig[$firstClassLevel]['prefix'] }}A</span>)</label>
                <input id="cname" name="name" class="in" required>
            </div>
            <div class="f">
                <label for="curriculum">Kurikulum</label>
                <select id="curriculum" name="curriculum" class="in">
                    <option value="merdeka">Kurikulum Merdeka</option>
                    <option value="k13">Kurikulum 2013</option>
                </select>
            </div>
            <div class="f">
                <label for="phase">Fase</label>
                <input id="phase" name="phase" class="in" value="{{ $classLevelConfig[$firstClassLevel]['phase'] }}">
            </div>
            <div class="f">
                <label for="homeroom_teacher_id">Wali Kelas</label>
                <select id="homeroom_teacher_id" name="homeroom_teacher_id" class="in">
                    <option value="">- Pilih Wali Kelas -</option>
                    @foreach($teachers as $t)
                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        <x-slot:footer>
            <button type="button" class="btn b-sec" onclick="document.getElementById('modalTambahKelas').close()">Batal</button>
            <button type="button" class="btn b-pri" onclick="document.getElementById('formTambahKelas').submit()">Simpan</button>
        </x-slot:footer>
    </x-modal>

    {{-- Modal Tambah Sekaligus --}}
    <x-modal id="modalTambahSekaligus" title="Tambah Kelas Sekaligus">
        <form method="POST" action="{{ route('operator.classrooms.batch') }}" id="formTambahBatch">
            @csrf
            <input type="hidden" name="academic_year_id" value="{{ $selectedYearId }}">
            <div class="f">
                <label for="blevel">Tingkat</label>
                    <select id="blevel" name="level" class="in" required>
                        @foreach($gradeLevels as $level => $label)
                            <option value="{{ $level }}" @selected((int) $level === $firstClassLevel)>{{ $label }}</option>
                        @endforeach
                    </select>
            </div>
            <div class="f">
                <label for="bprefix">Awalan / Format Nama Kelas</label>
                <input id="bprefix" name="prefix" class="in" value="{{ $classLevelConfig[$firstClassLevel]['prefix'] }}" required>
            </div>
            <div class="f">
                <label for="bparallels">Daftar Paralel (dipisahkan koma)</label>
                <input id="bparallels" name="parallels" class="in" value="A, B, C, D" required>
                <span class="hint">Contoh: "A, B, C, D" akan menghasilkan kelas dengan awalan tingkat yang dipilih.</span>
            </div>
            <div class="f">
                <label for="bcurriculum">Kurikulum</label>
                <select id="bcurriculum" name="curriculum" class="in">
                    <option value="merdeka">Kurikulum Merdeka</option>
                    <option value="k13">Kurikulum 2013</option>
                </select>
            </div>
            <div class="f">
                <label for="bphase">Fase</label>
                <input id="bphase" name="phase" class="in" value="{{ $classLevelConfig[$firstClassLevel]['phase'] }}">
            </div>
        </form>

        <x-slot:footer>
            <button type="button" class="btn b-sec" onclick="document.getElementById('modalTambahSekaligus').close()">Batal</button>
            <button type="button" class="btn b-pri" onclick="document.getElementById('formTambahBatch').submit()">Buat Semua Kelas</button>
        </x-slot:footer>
    </x-modal>

    <script>
        const classLevelConfig = @json($classLevelConfig);

        function syncClassLevelFields(level) {
            const config = classLevelConfig[level];
            if (!config) return;

            const classNameExample = document.getElementById('classNameExample');
            const singlePhase = document.getElementById('phase');
            const batchPrefix = document.getElementById('bprefix');
            const batchPhase = document.getElementById('bphase');

            if (classNameExample) classNameExample.textContent = config.prefix + 'A';
            if (singlePhase) singlePhase.value = config.phase;
            if (batchPrefix) batchPrefix.value = config.prefix;
            if (batchPhase) batchPhase.value = config.phase;
        }

        document.getElementById('level')?.addEventListener('change', function () {
            syncClassLevelFields(this.value);
        });
        document.getElementById('blevel')?.addEventListener('change', function () {
            syncClassLevelFields(this.value);
        });
    </script>
</x-layout.app>

