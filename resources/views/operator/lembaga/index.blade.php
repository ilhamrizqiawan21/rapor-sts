<x-layout.app title="Pengaturan Lembaga & Periode" userRole="operator">
    <x-slot:sidebar>
        <x-sidebar.operator active="lembaga" />
    </x-slot:sidebar>

    <x-slot:breadcrumb>
        <a href="{{ route('operator.dashboard') }}">Home</a> / <a href="#">Admin</a> / <span>Pengaturan Lembaga</span>
    </x-slot:breadcrumb>

    @if(session('success'))
        <x-alert variant="info">{{ session('success') }}</x-alert>
    @endif
    @if(session('error'))
        <x-alert variant="warning">{{ session('error') }}</x-alert>
    @endif

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; align-items: start">
        {{-- Kartu Tambah Tahun Pelajaran --}}
        <x-card title="Tambah Tahun Pelajaran">
            <form method="POST" action="{{ route('operator.lembaga.tahun.store') }}" style="display: flex; flex-direction: column; gap: 14px">
                @csrf
                <div class="f">
                    <label for="name">Tahun Pelajaran (contoh: 2026/2027)</label>
                    <input id="name" name="name" class="in" placeholder="YYYY/YYYY" required>
                    <span class="hint">Tahun baru otomatis dilengkapi Semester Ganjil dan Genap.</span>
                </div>
                <button type="submit" class="btn b-pri" style="align-self: flex-start">+ Simpan Tahun</button>
            </form>
        </x-card>

        {{-- Info Periode Aktif Saat Ini --}}
        <x-card title="Periode Aktif Berjalan">
            <div style="display: flex; flex-direction: column; gap: 12px">
                <p style="margin: 0">Aplikasi sedang memproses dan menampilkan data pada periode:</p>
                <div style="padding: 12px 16px; background: #e8f5e9; border: 1px solid #c8e6c9; border-radius: 4px; font-weight: 700; color: #1e7e34; font-size: 16px">
                    {{ $activePeriod ? $activePeriod->badge_text : 'Belum Ada Periode Aktif' }}
                </div>
                <span class="hint">Untuk mengubah periode aktif yang berlaku bagi seluruh guru dan kepala madrasah, klik tombol "Aktifkan" pada tabel di bawah.</span>
            </div>
        </x-card>
    </div>

    {{-- Tabel Tahun & Periode --}}
    <x-card title="Daftar Tahun Pelajaran & Semester">
        <div class="tb">
            <table class="t">
                <thead>
                    <tr>
                        <th style="width: 50px">#</th>
                        <th>Tahun Pelajaran</th>
                        <th>Semester Ganjil</th>
                        <th>Semester Genap</th>
                        <th style="width: 100px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($academicYears as $idx => $year)
                        @php
                            $ganjil = $year->periods->firstWhere('semester.value', 1);
                            $genap = $year->periods->firstWhere('semester.value', 2);
                        @endphp
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td><b>{{ $year->name }}</b></td>
                            <td>
                                @if($ganjil)
                                    @if($ganjil->is_active)
                                        <span class="pill" style="background: #1e7e34">✓ AKTIF</span>
                                    @else
                                        <form method="POST" action="{{ route('operator.lembaga.periode.activate', $ganjil) }}" style="display: inline">
                                            @csrf
                                            <button type="submit" class="btn b-sec" style="min-height: 28px; padding: 0 8px; font-size: 12px">Aktifkan</button>
                                        </form>
                                    @endif
                                @endif
                            </td>
                            <td>
                                @if($genap)
                                    @if($genap->is_active)
                                        <span class="pill" style="background: #1e7e34">✓ AKTIF</span>
                                    @else
                                        <form method="POST" action="{{ route('operator.lembaga.periode.activate', $genap) }}" style="display: inline">
                                            @csrf
                                            <button type="submit" class="btn b-sec" style="min-height: 28px; padding: 0 8px; font-size: 12px">Aktifkan</button>
                                        </form>
                                    @endif
                                @endif
                            </td>
                            <td>
                                <form method="POST" action="{{ route('operator.lembaga.tahun.destroy', $year) }}" onsubmit="return confirm('Hapus tahun pelajaran {{ $year->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn b-dan" style="min-height: 28px; padding: 0 8px; font-size: 12px">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="n cs">Belum ada tahun pelajaran.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</x-layout.app>
