<x-layout.app title="Kepala Madrasah · Kinerja Input Guru" userRole="kepala">
    <x-slot:breadcrumb>
        <a href="{{ route('kepala.dashboard') }}">Home</a> / <span>Kepala Madrasah</span> / <span>Kinerja Input Guru</span>
    </x-slot:breadcrumb>

    <div class="card">
        <div class="ch">
            <div>
                <div class="ct">Kinerja Input Nilai Guru</div>
                <div class="cs">Pantau keaktifan dan persentase pengiriman nilai guru pada periode aktif</div>
            </div>
        </div>

        <div class="cb tb">
            <table class="t">
                <thead>
                    <tr>
                        <th style="width: 40px">#</th>
                        <th>Nama Guru</th>
                        <th>NIP</th>
                        <th>Mapel yang Diampu</th>
                        <th>Daftar Kelas</th>
                        <th style="min-width: 150px">Progres Nilai Terkirim</th>
                        <th style="width: 140px">Terakhir Aktif</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($teacherRows as $idx => $row)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td><b>{{ $row['teacher']->name }}</b></td>
                            <td>{{ $row['teacher']->nip ?? '–' }}</td>
                            <td>{{ $row['subjects'] ?: '–' }}</td>
                            <td>{{ $row['classes'] ?: '–' }}</td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 4px">
                                    <div style="display: flex; justify-content: space-between; font-size: 11.5px">
                                        <span>{{ $row['submitted'] }}/{{ $row['total'] }} mapel-kelas</span>
                                        <b>{{ $row['percent'] }}%</b>
                                    </div>
                                    <div class="bar">
                                        <span style="width: {{ $row['percent'] }}%; {{ $row['percent'] === 100 ? 'background: #1e7e34' : ($row['percent'] < 50 ? 'background: #c82333' : '#f2b01e') }}"></span>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $row['last_active'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 24px; color: #6c757d">
                                Belum ada data guru pengampu pada periode aktif.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layout.app>
