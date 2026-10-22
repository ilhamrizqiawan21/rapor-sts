<x-layout.app title="Mata Pelajaran" userRole="operator">
    <x-slot:sidebar>
        <x-sidebar.operator active="subjects" />
    </x-slot:sidebar>

    <x-slot:breadcrumb>
        <a href="{{ route('operator.dashboard') }}">Home</a> / <a href="#">Admin</a> / <span>Mata Pelajaran</span>
    </x-slot:breadcrumb>

    @if(session('success'))
        <x-alert variant="info">{{ session('success') }}</x-alert>
    @endif
    @if(session('error'))
        <x-alert variant="warning">{{ session('error') }}</x-alert>
    @endif

    <x-card title="Daftar Mata Pelajaran">
        <x-slot:actions>
            <button type="button" class="btn b-pri" onclick="document.getElementById('modalTambahMapel').showModal()">+ Tambah Mapel</button>
        </x-slot:actions>

        <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 16px">
            <label for="curriculumFilter" style="font-weight: 600">Kurikulum:</label>
            <select id="curriculumFilter" class="sel" onchange="location.href = '{{ route('operator.subjects.index') }}?curriculum=' + this.value">
                <option value="merdeka" @selected($curriculum === 'merdeka')>Kurikulum Merdeka</option>
                <option value="k13" @selected($curriculum === 'k13')>Kurikulum 2013</option>
            </select>
        </div>

        <div class="tb">
            <table class="t">
                <thead>
                    <tr>
                        <th style="width: 40px">#</th>
                        <th>Kode</th>
                        <th>Nama Mata Pelajaran</th>
                        <th>Induk</th>
                        <th>Kelompok</th>
                        <th>Urutan</th>
                        <th>Status</th>
                        <th style="width: 120px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subjects as $idx => $s)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td><b>{{ $s->code }}</b></td>
                            <td @if($s->parent_id) style="padding-left: 28px; font-style: italic" @endif>
                                {{ $s->name }}
                            </td>
                            <td>{{ $s->parent ? $s->parent->code : '-' }}</td>
                            <td>{{ ucfirst($s->group) }}</td>
                            <td>{{ $s->sort_order }}</td>
                            <td>
                                @if($s->is_active)
                                    <span class="pill" style="background: #1e7e34">Aktif</span>
                                @else
                                    <span class="pill" style="background: #6c757d">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                <form method="POST" action="{{ route('operator.subjects.destroy', $s) }}" onsubmit="return confirm('Hapus mata pelajaran {{ $s->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn b-dan" style="min-height: 28px; padding: 0 8px; font-size: 12px">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="n cs">Belum ada data mata pelajaran untuk kurikulum ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    {{-- Modal Tambah Mapel --}}
    <x-modal id="modalTambahMapel" title="Tambah Mata Pelajaran">
        <form method="POST" action="{{ route('operator.subjects.store') }}" id="formTambahMapel">
            @csrf
            <input type="hidden" name="curriculum" value="{{ $curriculum }}">
            <div class="f">
                <label for="code">Kode Mapel (contoh: FIK, BAR)</label>
                <input id="code" name="code" class="in" required>
            </div>
            <div class="f">
                <label for="name">Nama Mata Pelajaran</label>
                <input id="name" name="name" class="in" required>
            </div>
            <div class="f">
                <label for="parent_id">Mapel Induk (opsional, contoh: PAI)</label>
                <select id="parent_id" name="parent_id" class="in">
                    <option value="">- Tidak Ada (Mapel Mandiri / Induk) -</option>
                    @foreach($parentSubjects as $p)
                        <option value="{{ $p->id }}">{{ $p->code }} - {{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="f">
                <label for="group">Kelompok</label>
                <select id="group" name="group" class="in">
                    <option value="umum">Umum</option>
                    <option value="mulok">Muatan Lokal</option>
                </select>
            </div>
            <div class="f">
                <label for="sort_order">Urutan Cetak</label>
                <input id="sort_order" name="sort_order" type="number" class="in" value="{{ $subjects->count() + 1 }}">
            </div>
        </form>

        <x-slot:footer>
            <button type="button" class="btn b-dan" onclick="document.getElementById('modalTambahMapel').close()">Batal</button>
            <button type="button" class="btn b-pri" onclick="document.getElementById('formTambahMapel').submit()">Simpan</button>
        </x-slot:footer>
    </x-modal>
</x-layout.app>

