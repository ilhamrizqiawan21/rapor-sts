<x-layout.app title="Data Guru" userRole="operator">
    <x-slot:sidebar>
        <x-sidebar.operator active="teachers" />
    </x-slot:sidebar>

    <x-slot:breadcrumb>
        <a href="{{ route('operator.dashboard') }}">Home</a> / <a href="#">Admin</a> / <span>Data Guru</span>
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

    <x-card title="Data Guru & Tenaga Pendidik">
        <x-slot:actions>
            <div class="grp">
                <button type="button" class="btn b-pri" onclick="document.getElementById('modalTambahGuru').showModal()">+ Tambah Guru</button>
                <button type="button" class="btn b-suc" onclick="document.getElementById('modalImportGuru').showModal()">Upload Excel</button>
                <a href="{{ route('operator.teachers.template') }}" class="btn b-war">Unduh Template</a>
            </div>
        </x-slot:actions>

        <form method="GET" action="{{ route('operator.teachers.index') }}" style="display: flex; gap: 8px; margin-bottom: 16px; justify-content: flex-end">
            <input name="search" class="in" style="max-width: 260px; height: 34px" placeholder="Cari nama, NIK, NIP..." value="{{ $search }}">
            <button type="submit" class="btn b-sec" style="min-height: 34px">Cari</button>
        </form>

        <div class="tb">
            <table class="t">
                <thead>
                    <tr>
                        <th style="width: 40px">#</th>
                        <th>Nama Guru</th>
                        <th>Username (NIK/NUPTK)</th>
                        <th>Password</th>
                        <th>NIP</th>
                        <th>L/P</th>
                        <th>Status</th>
                        <th style="width: 200px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($teachers as $idx => $t)
                        <tr>
                            <td>{{ $teachers->firstItem() + $idx }}</td>
                            <td><b>{{ $t->name }}</b></td>
                            <td>{{ $t->username }}</td>
                            <td>@if($t->password_plain)<code>{{ $t->password_plain }}</code>@else<span class="n" title="Password diubah di luar aplikasi / belum tercatat">-</span>@endif</td>
                            <td>{{ $t->nip ?? '-' }}</td>
                            <td>{{ $t->gender }}</td>
                            <td>
                                @if($t->is_active)
                                    <span class="pill" style="background: #1e7e34">Aktif</span>
                                @else
                                    <span class="pill" style="background: #6c757d">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                <form method="POST" action="{{ route('operator.teachers.toggle', $t) }}" style="display: inline">
                                    @csrf
                                    <button type="submit" class="btn {{ $t->is_active ? 'b-war' : 'b-suc' }}" style="min-height: 28px; padding: 0 8px; font-size: 12px">
                                        {{ $t->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('operator.teachers.destroy', $t) }}" style="display: inline" onsubmit="return confirm('Hapus guru {{ addslashes($t->name) }}? Tindakan ini tidak dapat dibatalkan.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn b-dan" style="min-height: 28px; padding: 0 8px; font-size: 12px">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="n cs">Belum ada data guru.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($teachers->hasPages())
            <div class="pg">
                {{ $teachers->links() }}
            </div>
        @endif
    </x-card>

    {{-- Modal Tambah Guru --}}
    <x-modal id="modalTambahGuru" title="Tambah Data Guru">
        <form method="POST" action="{{ route('operator.teachers.store') }}" id="formTambahGuru">
            @csrf
            <div class="f">
                <label for="name">Nama Guru (dengan gelar)</label>
                <input id="name" name="name" class="in" required>
            </div>
            <div class="f">
                <label for="username">Username (NIK / NUPTK)</label>
                <input id="username" name="username" class="in" required>
            </div>
            <div class="f">
                <label for="password">Password Awal</label>
                <input id="password" name="password" type="password" class="in" minlength="6" autocomplete="new-password" required>
            </div>
            <div class="f">
                <label for="nip">NIP (opsional)</label>
                <input id="nip" name="nip" class="in">
            </div>
            <div class="f">
                <label for="gender">Jenis Kelamin</label>
                <select id="gender" name="gender" class="in" required>
                    <option value="L">Laki-laki (L)</option>
                    <option value="P">Perempuan (P)</option>
                </select>
            </div>
        </form>

        <x-slot:footer>
            <button type="button" class="btn b-sec" onclick="document.getElementById('modalTambahGuru').close()">Batal</button>
            <button type="button" class="btn b-pri" onclick="document.getElementById('formTambahGuru').submit()">Simpan</button>
        </x-slot:footer>
    </x-modal>

    {{-- Modal Upload Excel Guru --}}
    <x-modal id="modalImportGuru" title="Upload File Excel Data Guru">
        <form method="POST" action="{{ route('operator.teachers.import') }}" enctype="multipart/form-data" id="formImportGuru">
            @csrf
            <div class="alert a-lt" style="margin-bottom: 12px">
                Gunakan template resmi dari tombol <b>Unduh Template</b>. Pastikan format kolom tidak diubah (format .xlsx, maks 2MB).
            </div>
            <div class="f">
                <label for="file">Pilih File Excel (.xlsx)</label>
                <input id="file" name="file" type="file" accept=".xlsx" class="in" style="padding: 4px 10px" required>
            </div>
        </form>

        <x-slot:footer>
            <button type="button" class="btn b-sec" onclick="document.getElementById('modalImportGuru').close()">Batal</button>
            <button type="button" class="btn b-suc" onclick="document.getElementById('formImportGuru').submit()">Upload & Proses</button>
        </x-slot:footer>
    </x-modal>
</x-layout.app>

