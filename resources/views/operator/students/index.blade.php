<x-layout.app title="Data Siswa" userRole="operator">
    <x-slot:sidebar>
        <x-sidebar.operator active="students" />
    </x-slot:sidebar>

    <x-slot:breadcrumb>
        <a href="{{ route('operator.dashboard') }}">Home</a> / <a href="#">Admin</a> / <span>Data Siswa</span>
    </x-slot:breadcrumb>

    @if(session('success'))
        <x-alert variant="info">{{ session('success') }}</x-alert>
    @endif
    @if($errors->any())
        <x-alert variant="warning">
            @foreach($errors->all() as $err)
                <div>{!! nl2br(e(str_replace("<br>", "\n", $err))) !!}</div>
            @endforeach
        </x-alert>
    @endif

    <x-card title="Data Siswa Madrasah">
        <x-slot:actions>
            <div class="grp">
                @if($selectedClass && $selectedClass->level < 9)
                    <button type="button" class="btn b-inf" onclick="document.getElementById('modalNaikKelas').showModal()">Naikkan Siswa dari {{ $selectedClass->name }}</button>
                @elseif($selectedClass)
                    <span class="n" style="align-self: center">Kelas tingkat akhir</span>
                @endif
                <form method="POST" action="{{ route('operator.students.bulk-destroy') }}" id="formBulkDeleteSiswa" style="display: inline" onsubmit="return confirmBulkDeleteSiswa(event)">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="ids[]" disabled>
                    <button type="submit" id="btnBulkDeleteSiswa" class="btn b-dan" style="display: none; align-items: center; gap: 6px">
                        Hapus Terpilih (<span id="selectedSiswaCount">0</span>)
                    </button>
                </form>
                <button type="button" class="btn b-pri" onclick="document.getElementById('modalTambahSiswa').showModal()">+ Tambah Siswa</button>
                <button type="button" class="btn b-suc" onclick="document.getElementById('modalImportSiswa').showModal()">Upload Excel</button>
                <a href="{{ route('operator.students.template', ['classroom_id' => $selectedClassId]) }}" class="btn b-war">Unduh Template</a>
            </div>
        </x-slot:actions>

        {{-- Filter Tahun & Kelas --}}
        <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; margin-bottom: 16px">
            <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center">
                <label for="yearFilter" style="font-weight: 600">Tahun:</label>
                <select id="yearFilter" class="sel" style="min-width: 140px" onchange="location.href = '{{ route('operator.students.index') }}?academic_year_id=' + this.value">
                    @foreach($academicYears as $y)
                        <option value="{{ $y->id }}" @selected($selectedYearId == $y->id)>{{ $y->name }}</option>
                    @endforeach
                </select>

                <label for="classFilter" style="font-weight: 600">Kelas:</label>
                <select id="classFilter" class="sel" style="min-width: 140px" onchange="location.href = '{{ route('operator.students.index') }}?academic_year_id={{ $selectedYearId }}&classroom_id=' + this.value">
                    <option value="">- Semua Kelas -</option>
                    @foreach($classrooms as $c)
                        <option value="{{ $c->id }}" @selected($selectedClassId == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <form method="GET" action="{{ route('operator.students.index') }}" style="display: flex; gap: 8px">
                <input type="hidden" name="academic_year_id" value="{{ $selectedYearId }}">
                <input type="hidden" name="classroom_id" value="{{ $selectedClassId }}">
                <input name="search" class="in" style="width: 220px; height: 34px" placeholder="Cari nama, NISN..." value="{{ $search }}">
                <button type="submit" class="btn b-sec" style="min-height: 34px">Cari</button>
            </form>
        </div>

        <div class="tb">
            <table class="t">
                <thead>
                    <tr>
                        <th style="width: 36px; text-align: center">
                            <label style="cursor: pointer; display: flex; align-items: center; justify-content: center; margin: 0" title="Pilih semua siswa">
                                <input type="checkbox" id="selectAllSiswa" title="Pilih semua siswa" aria-label="Pilih semua siswa">
                                <span style="display: none">Pilih semua siswa</span>
                            </label>
                        </th>
                        <th style="width: 40px">#</th>
                        <th>NIS</th>
                        <th>NISN</th>
                        <th>Nama Siswa</th>
                        <th>L/P</th>
                        <th>Kelas Aktif</th>
                        <th>Status</th>
                        <th style="width: 220px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $idx => $s)
                        @php
                            $currentEnrollment = $s->enrollments->first();
                            $currentClass = $currentEnrollment?->classroom;
                        @endphp
                        <tr>
                            <td style="text-align: center">
                                <input type="checkbox" name="ids[]" value="{{ $s->id }}" class="student-checkbox" form="formBulkDeleteSiswa" aria-label="Pilih siswa {{ $s->name }}">
                            </td>
                            <td>{{ $students->firstItem() + $idx }}</td>
                            <td>{{ $s->nis ?? '-' }}</td>
                            <td><b>{{ $s->nisn }}</b></td>
                            <td>{{ $s->name }}</td>
                            <td>{{ $s->gender }}</td>
                            <td>
                                @if($currentClass)
                                    <span class="pill" style="background: #1b6ec2">{{ $currentClass->name }}</span>
                                @else
                                    <span class="cs">- Belum Masuk Kelas -</span>
                                @endif
                            </td>
                            <td>
                                @if($s->is_active)
                                    <span class="pill" style="background: #1e7e34">Aktif</span>
                                @else
                                    <span class="pill" style="background: #6c757d">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                <div style="display: flex; gap: 4px; flex-wrap: wrap">
                                    <button type="button" class="btn b-pri" style="min-height: 28px; padding: 0 8px; font-size: 12px" onclick='openEditModal(@json($s), {{ $currentClass?->id ?? "null" }})'>Edit</button>
                                    @if($currentClass)
                                        <button type="button" class="btn b-inf" style="min-height: 28px; padding: 0 8px; font-size: 12px" onclick='openMoveModal({{ $s->id }}, @json($s->name), {{ $currentClass->id }})'>Pindah</button>
                                    @endif
                                    <form method="POST" action="{{ route('operator.students.toggle', $s) }}" style="display: inline">
                                        @csrf
                                        <button type="submit" class="btn {{ $s->is_active ? 'b-war' : 'b-suc' }}" style="min-height: 28px; padding: 0 8px; font-size: 12px" title="{{ $s->is_active ? 'Nonaktifkan Siswa' : 'Aktifkan Siswa' }}">
                                            {{ $s->is_active ? 'Nonaktif' : 'Aktif' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('operator.students.destroy', $s) }}" style="display: inline" onsubmit="return confirm('Hapus siswa {{ addslashes($s->name) }} beserta seluruh nilainya? Tindakan ini tidak dapat dibatalkan.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn b-dan" style="min-height: 28px; padding: 0 8px; font-size: 12px">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="n cs">Belum ada data siswa untuk filter ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($students->hasPages())
            <div class="pg">
                {{ $students->links() }}
            </div>
        @endif
    </x-card>

    @if($selectedClass && $selectedClass->level < 9)
        <x-modal id="modalNaikKelas" title="Naikkan Siswa ke Tahun Ajaran Baru">
            <form method="POST" action="{{ route('operator.students.promote') }}" id="formNaikKelas" onsubmit="return confirm('Naikkan semua siswa aktif ke kelas tujuan? Riwayat tahun ajaran asal tetap tersimpan.')">
                @csrf
                <input type="hidden" name="source_classroom_id" value="{{ $selectedClass->id }}">
                <div class="alert a-lt" style="margin-bottom: 12px">
                    Data siswa tidak dibuat ulang. Sistem menambahkan penempatan pada tahun tujuan dan mempertahankan riwayat kelas sebelumnya. Buat tahun ajaran dan kelas tujuan terlebih dahulu.
                </div>
                <div class="f">
                    <label for="target_academic_year_id">Tahun Ajaran Tujuan</label>
                    <select id="target_academic_year_id" name="target_academic_year_id" class="in" required>
                        <option value="">- Pilih Tahun Ajaran -</option>
                        @foreach($academicYears->where('id', '!=', $selectedYearId) as $year)
                            <option value="{{ $year->id }}">{{ $year->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="f">
                    <label for="target_classroom_id">Kelas Tujuan (tingkat {{ $selectedClass->level + 1 }})</label>
                    <select id="target_classroom_id" name="target_classroom_id" class="in" required disabled>
                        <option value="">- Pilih Tahun Ajaran Dahulu -</option>
                        @foreach($promotionClassrooms as $classroom)
                            <option value="{{ $classroom->id }}" data-year-id="{{ $classroom->academic_year_id }}" data-level="{{ $classroom->level }}" hidden disabled>
                                {{ $classroom->academicYear->name }} · {{ $classroom->name }}
                            </option>
                        @endforeach
                    </select>
                    <small id="promotionClassHint" class="n">Pilih kelas tingkat {{ $selectedClass->level + 1 }} pada tahun ajaran tujuan.</small>
                </div>
            </form>
            <x-slot:footer>
                <button type="button" class="btn b-sec" onclick="document.getElementById('modalNaikKelas').close()">Batal</button>
                <button type="submit" form="formNaikKelas" class="btn b-pri">Naikkan Siswa</button>
            </x-slot:footer>
        </x-modal>
    @endif

    {{-- Modal Tambah Siswa --}}
    <x-modal id="modalTambahSiswa" title="Tambah Data Siswa">
        <form method="POST" action="{{ route('operator.students.store') }}" id="formTambahSiswa">
            @csrf
            <div class="f">
                <label for="nisn">NISN (10 Digit, Wajib Unik)</label>
                <input id="nisn" name="nisn" class="in" required>
            </div>
            <div class="f">
                <label for="nis">NIS (Nomor Induk Siswa)</label>
                <input id="nis" name="nis" class="in">
            </div>
            <div class="f">
                <label for="name">Nama Lengkap Siswa</label>
                <input id="name" name="name" class="in" required>
            </div>
            <div class="f">
                <label for="gender">Jenis Kelamin</label>
                <select id="gender" name="gender" class="in" required>
                    <option value="L">Laki-laki (L)</option>
                    <option value="P">Perempuan (P)</option>
                </select>
            </div>
            <div class="f">
                <label for="birth_place">Tempat Lahir</label>
                <input id="birth_place" name="birth_place" class="in" required>
            </div>
            <div class="f">
                <label for="birth_date">Tanggal Lahir</label>
                <input id="birth_date" name="birth_date" type="date" class="in" required>
            </div>
            <div class="f">
                <label for="classroom_id">Penempatan Kelas</label>
                <select id="classroom_id" name="classroom_id" class="in">
                    <option value="">- Belum Ditentukan -</option>
                    @foreach($classrooms as $c)
                        <option value="{{ $c->id }}" @selected($selectedClassId == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="f">
                <label for="address">Alamat Tempat Tinggal</label>
                <input id="address" name="address" class="in">
            </div>
        </form>

        <x-slot:footer>
            <button type="button" class="btn b-sec" onclick="document.getElementById('modalTambahSiswa').close()">Batal</button>
            <button type="button" class="btn b-pri" onclick="document.getElementById('formTambahSiswa').submit()">Simpan</button>
        </x-slot:footer>
    </x-modal>

    {{-- Modal Upload Excel Siswa --}}
    <x-modal id="modalImportSiswa" title="Upload File Excel Siswa Per Kelas">
        <form method="POST" action="{{ route('operator.students.import') }}" enctype="multipart/form-data" id="formImportSiswa">
            @csrf
            <div class="alert a-lt" style="margin-bottom: 12px">
                Data siswa yang diimpor akan otomatis terdaftar dan ditempatkan pada kelas target yang Anda pilih di bawah.
            </div>
            <div class="f">
                <label for="import_classroom_id">Target Kelas Penempatan</label>
                <select id="import_classroom_id" name="classroom_id" class="in" required>
                    @foreach($classrooms as $c)
                        <option value="{{ $c->id }}" @selected($selectedClassId == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="f">
                <label for="sfile">File Excel (.xlsx)</label>
                <input id="sfile" name="file" type="file" accept=".xlsx" class="in" style="padding: 4px 10px" required>
            </div>
        </form>

        <x-slot:footer>
            <button type="button" class="btn b-sec" onclick="document.getElementById('modalImportSiswa').close()">Batal</button>
            <button type="button" class="btn b-suc" onclick="document.getElementById('formImportSiswa').submit()">Upload & Proses</button>
        </x-slot:footer>
    </x-modal>

    {{-- Modal Edit Siswa --}}
    <x-modal id="modalEditSiswa" title="Edit Data Siswa">
        <form method="POST" id="formEditSiswa" action="">
            @csrf
            @method('PUT')
            <div class="f">
                <label for="edit_nisn">NISN (10 Digit, Wajib Unik)</label>
                <input id="edit_nisn" name="nisn" class="in" required>
            </div>
            <div class="f">
                <label for="edit_nis">NIS (Nomor Induk Siswa)</label>
                <input id="edit_nis" name="nis" class="in">
            </div>
            <div class="f">
                <label for="edit_name">Nama Lengkap Siswa</label>
                <input id="edit_name" name="name" class="in" required>
            </div>
            <div class="f">
                <label for="edit_gender">Jenis Kelamin</label>
                <select id="edit_gender" name="gender" class="in" required>
                    <option value="L">Laki-laki (L)</option>
                    <option value="P">Perempuan (P)</option>
                </select>
            </div>
            <div class="f">
                <label for="edit_birth_place">Tempat Lahir</label>
                <input id="edit_birth_place" name="birth_place" class="in" required>
            </div>
            <div class="f">
                <label for="edit_birth_date">Tanggal Lahir</label>
                <input id="edit_birth_date" name="birth_date" type="date" class="in" required>
            </div>
            <div class="f">
                <label for="edit_classroom_id">Penempatan Kelas</label>
                <select id="edit_classroom_id" name="classroom_id" class="in">
                    <option value="">- Belum Ditentukan -</option>
                    @foreach($classrooms as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="f">
                <label for="edit_address">Alamat Tempat Tinggal</label>
                <input id="edit_address" name="address" class="in">
            </div>
        </form>

        <x-slot:footer>
            <button type="button" class="btn b-sec" onclick="document.getElementById('modalEditSiswa').close()">Batal</button>
            <button type="button" class="btn b-pri" onclick="document.getElementById('formEditSiswa').submit()">Simpan Perubahan</button>
        </x-slot:footer>
    </x-modal>

    @if($selectedYearId)
        <x-modal id="modalPindahKelas" title="Pindah Kelas Siswa">
            <form method="POST" action="" id="formPindahKelas">
                @csrf
                <input type="hidden" name="academic_year_id" value="{{ $selectedYearId }}">
                <input type="hidden" name="source_classroom_id" id="move_source_classroom_id">
                <div class="alert a-lt" style="margin-bottom: 12px">
                    Nilai yang sudah masuk tetap terikat pada kelas dan penugasan asal. Hanya penempatan siswa pada tahun ajaran ini yang berubah.
                </div>
                <p id="move_student_label" style="margin-top: 0"></p>
                <div class="f">
                    <label for="move_target_classroom_id">Kelas Tujuan</label>
                    <select id="move_target_classroom_id" name="target_classroom_id" class="in" required>
                        <option value="">- Pilih Kelas Tujuan -</option>
                        @foreach($classrooms as $classroom)
                            <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
            <x-slot:footer>
                <button type="button" class="btn b-sec" onclick="document.getElementById('modalPindahKelas').close()">Batal</button>
                <button type="submit" form="formPindahKelas" class="btn b-pri">Pindahkan Siswa</button>
            </x-slot:footer>
        </x-modal>
    @endif

    <script>
        @if($selectedClass && $selectedClass->level < 9)
        const targetYearSelect = document.getElementById('target_academic_year_id');
        const targetClassSelect = document.getElementById('target_classroom_id');
        const promotionClassHint = document.getElementById('promotionClassHint');

        if (targetYearSelect && targetClassSelect) {
            targetYearSelect.addEventListener('change', function () {
                const yearId = this.value;
                let matchingClasses = 0;

                Array.from(targetClassSelect.options).forEach(function (option) {
                    if (!option.value) return;

                    const matches = option.dataset.yearId === yearId
                        && Number(option.dataset.level) === {{ $selectedClass->level + 1 }};
                    option.hidden = !matches;
                    option.disabled = !matches;
                    if (matches) matchingClasses++;
                });

                targetClassSelect.value = '';
                targetClassSelect.disabled = !yearId || matchingClasses === 0;
                targetClassSelect.options[0].textContent = matchingClasses
                    ? '- Pilih Kelas Tujuan -'
                    : '- Kelas tingkat berikutnya belum tersedia -';
                if (promotionClassHint) {
                    promotionClassHint.textContent = matchingClasses
                        ? 'Pilih kelas tingkat {{ $selectedClass->level + 1 }} pada tahun ajaran tujuan.'
                        : 'Belum ada kelas tingkat {{ $selectedClass->level + 1 }} pada tahun ajaran ini.';
                }
            });
        }
        @endif

        function openEditModal(student, currentClassId) {
            document.getElementById('formEditSiswa').action = '/operator/siswa/' + student.id;
            document.getElementById('edit_nisn').value = student.nisn || '';
            document.getElementById('edit_nis').value = student.nis || '';
            document.getElementById('edit_name').value = student.name || '';
            document.getElementById('edit_gender').value = student.gender || 'L';
            document.getElementById('edit_birth_place').value = student.birth_place || '';
            document.getElementById('edit_birth_date').value = student.birth_date ? student.birth_date.substring(0, 10) : '';
            document.getElementById('edit_classroom_id').value = currentClassId || '';
            document.getElementById('edit_address').value = student.address || '';
            document.getElementById('modalEditSiswa').showModal();
        }

        function openMoveModal(studentId, studentName, sourceClassroomId) {
            document.getElementById('formPindahKelas').action = '/operator/siswa/' + studentId + '/pindah-kelas';
            document.getElementById('move_source_classroom_id').value = sourceClassroomId;
            document.getElementById('move_target_classroom_id').value = '';
            document.getElementById('move_student_label').textContent = 'Siswa: ' + studentName;
            document.getElementById('modalPindahKelas').showModal();
        }

        document.addEventListener('DOMContentLoaded', function () {
            const selectAllSiswa = document.getElementById('selectAllSiswa');
            const studentCheckboxes = document.querySelectorAll('.student-checkbox');
            const btnBulkDeleteSiswa = document.getElementById('btnBulkDeleteSiswa');
            const selectedSiswaCount = document.getElementById('selectedSiswaCount');

            function updateSiswaBulkState() {
                const checked = document.querySelectorAll('.student-checkbox:checked');
                const count = checked.length;
                if (selectedSiswaCount) selectedSiswaCount.textContent = count;
                if (btnBulkDeleteSiswa) {
                    btnBulkDeleteSiswa.style.display = count > 0 ? 'inline-flex' : 'none';
                }
                if (selectAllSiswa) {
                    selectAllSiswa.checked = studentCheckboxes.length > 0 && count === studentCheckboxes.length;
                    selectAllSiswa.indeterminate = count > 0 && count < studentCheckboxes.length;
                }
            }

            if (selectAllSiswa) {
                selectAllSiswa.addEventListener('change', function () {
                    studentCheckboxes.forEach(cb => { cb.checked = selectAllSiswa.checked; });
                    updateSiswaBulkState();
                });
            }

            studentCheckboxes.forEach(cb => {
                cb.addEventListener('change', updateSiswaBulkState);
            });

            window.confirmBulkDeleteSiswa = function (e) {
                const checked = document.querySelectorAll('.student-checkbox:checked');
                if (checked.length === 0) {
                    alert('Pilih minimal satu siswa untuk dihapus.');
                    e.preventDefault();
                    return false;
                }
                if (!confirm('Hapus ' + checked.length + ' siswa yang dipilih beserta seluruh nilainya? Tindakan ini tidak dapat dibatalkan.')) {
                    e.preventDefault();
                    return false;
                }
                return true;
            };
        });
    </script>
</x-layout.app>

