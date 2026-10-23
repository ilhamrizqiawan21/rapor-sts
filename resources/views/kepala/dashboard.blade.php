<x-layout.app title="Kepala Madrasah · Dashboard Pemantauan" userRole="kepala">
    <x-slot:breadcrumb>
        <a href="{{ route('kepala.dashboard') }}">Home</a> / <span>Kepala Madrasah</span> / <span>Dashboard</span>
    </x-slot:breadcrumb>

    <div style="display: flex; flex-direction: column; gap: 20px">
        {{-- Baris Filter Tingkat dan Tombol Unduh --}}
        <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end">
            <form method="GET" action="{{ route('kepala.dashboard') }}" id="filterLevelForm" style="display: flex; flex-direction: column; gap: 4px">
                <label for="tk" style="font-size: 13px; color: #495057">Filter Tingkat</label>
                <select id="tk" name="tingkat" class="in" style="min-width: 170px" onchange="document.getElementById('filterLevelForm').submit()">
                    <option value="">Semua tingkat</option>
                    @foreach($availableLevels as $lvl)
                        @php
                            $lvlRoman = match($lvl) { 7 => 'VII', 8 => 'VIII', 9 => 'IX', default => (string)$lvl };
                        @endphp
                        <option value="{{ $lvl }}" {{ (string)$filterLevel === (string)$lvl ? 'selected' : '' }}>
                            Tingkat {{ $lvlRoman }}
                        </option>
                    @endforeach
                </select>
            </form>

            <div style="margin-left: auto; display: flex; flex-wrap: wrap; gap: 8px">
                <a href="{{ route('kepala.unduh.excel') }}" class="btn b-suc">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                    Unduh Rekap Excel
                </a>
                <a href="{{ route('kepala.unduh.pdf') }}" class="btn b-inf" target="_blank" rel="noopener">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                    Unduh Rekap PDF
                </a>
            </div>
        </div>

        {{-- 4 Stat Cards --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px">
            <div class="stat" style="background: #1e7e34">
                <span>Nilai terkirim</span>
                <b>{{ $percentSubmitted }}%</b>
                <small>{{ $submittedCount }} dari {{ $totalAssignments }} mapel-kelas</small>
            </div>
            <div class="stat" style="background: #c82333">
                <span>Guru belum kirim</span>
                <b>{{ $unsubmittedTeacherCount }}</b>
                <small>dari {{ $totalTeacherCount }} guru</small>
            </div>
            <div class="stat" style="background: #1b6ec2">
                <span>Kelas dikunci walas</span>
                <b>{{ $lockedClassroomsCount }} / {{ $totalClassrooms }}</b>
                <small>siap cetak rapor</small>
            </div>
            <div class="stat" style="background: #f2b01e; color: #3b2a00">
                <span>Rata-rata nilai STS</span>
                <b>{{ $overallAverage }}</b>
                <small>{{ $filterLevel ? 'Tingkat '.$filterLevel : 'seluruh madrasah' }}</small>
            </div>
        </div>

        {{-- Tabel Progres per Kelas --}}
        <div class="card">
            <div class="ch">
                <div>
                    <div class="ct">Progres per Kelas</div>
                    <div class="cs">Periode aktif: {{ $period->badge_text }}</div>
                </div>
            </div>
            <div class="cb tb">
                <table class="t">
                    <thead>
                        <tr>
                            <th>Kelas</th>
                            <th>Wali Kelas</th>
                            <th style="min-width: 140px">Mapel terkirim</th>
                            <th style="width: 70px; text-align: center">Rasio</th>
                            <th>Status Walas</th>
                            <th class="n">Rerata</th>
                            <th class="n">Belum Tercapai</th>
                            <th style="width: 80px; text-align: center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($classProgress as $cp)
                            <tr>
                                <td><b>{{ $cp['classroom']->name }}</b></td>
                                <td>{{ $cp['homeroom_teacher'] }}</td>
                                <td>
                                    <div class="bar">
                                        <span style="width: {{ $cp['progress_percent'] }}%; {{ $cp['progress_percent'] < 50 ? 'background: #c82333' : '' }}"></span>
                                    </div>
                                </td>
                                <td style="text-align: center">{{ $cp['submitted_count'] }}/{{ $cp['total_count'] }}</td>
                                <td>
                                    @if($cp['is_locked'])
                                        <span class="pill" style="background: #1e7e34">Dikunci</span>
                                    @else
                                        <span class="pill" style="background: #6c757d">Belum dikunci</span>
                                    @endif
                                </td>
                                <td class="n"><b>{{ $cp['class_average'] }}</b></td>
                                <td class="n">
                                    @if($cp['students_below_kktp_count'] > 0)
                                        <span style="color: #b02a37; font-weight: 600">{{ $cp['students_below_kktp_count'] }} siswa</span>
                                    @else
                                        0 siswa
                                    @endif
                                </td>
                                <td style="text-align: center">
                                    <a class="btn b-sec" href="{{ route('kepala.leger.index', ['classroom_id' => $cp['classroom']->id]) }}" style="min-height: 28px; padding: 0 10px; font-size: 12px">
                                        Lihat
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 20px; color: #6c757d">
                                    Tidak ada data kelas untuk tingkat ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Grid 2 Kolom: Guru Belum Kirim & Sebaran Ketercapaian --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: 20px">
            {{-- Guru Belum Mengirim Nilai --}}
            <div class="card">
                <div class="ch">
                    <div class="ct">Guru Belum Mengirim Nilai</div>
                </div>
                <div class="cb tb">
                    <table class="t">
                        <thead>
                            <tr>
                                <th>Guru</th>
                                <th>Mapel</th>
                                <th>Kelas</th>
                                <th>Belum diisi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($unsubmittedTeachers as $ut)
                                <tr>
                                    <td><b>{{ $ut['teacher_name'] }}</b></td>
                                    <td>{{ $ut['subject_name'] }}</td>
                                    <td>{{ $ut['classroom_name'] }}</td>
                                    <td>
                                        <span style="color: #b02a37; font-weight: 600">{{ $ut['missing_label'] }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 18px; color: #1e7e34; font-weight: 600">
                                        ✓ Semua guru telah mengirimkan nilai rapor STS.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Sebaran Ketercapaian per Tingkat --}}
            <div class="card">
                <div class="ch">
                    <div class="ct">Sebaran Ketercapaian per Tingkat</div>
                </div>
                <div class="cb" style="display: flex; flex-direction: column; gap: 14px">
                    <div class="lg">
                        <span><i style="background: #0a58ca"></i>Sangat Baik</span>
                        <span><i style="background: #1e7e34"></i>Baik</span>
                        <span><i style="background: #b8860b"></i>Cukup</span>
                        <span><i style="background: #c82333"></i>Belum Tercapai</span>
                    </div>

                    @forelse($achievementDistribution as $dist)
                        <div style="display: grid; grid-template-columns: 48px 1fr; gap: 8px; align-items: center">
                            <b>{{ $dist['level_roman'] }}</b>
                            @if($dist['total_scores'] > 0)
                                <div class="dist" role="img" aria-label="Tingkat {{ $dist['level_roman'] }}: Sangat Baik {{ $dist['percentages']['sangat_baik'] }}%, Baik {{ $dist['percentages']['baik'] }}%, Cukup {{ $dist['percentages']['cukup'] }}%, Belum Tercapai {{ $dist['percentages']['belum_tercapai'] }}%">
                                    @if($dist['percentages']['sangat_baik'] > 0)
                                        <span style="width: {{ $dist['percentages']['sangat_baik'] }}%; background: #0a58ca">{{ $dist['percentages']['sangat_baik'] }}%</span>
                                    @endif
                                    @if($dist['percentages']['baik'] > 0)
                                        <span style="width: {{ $dist['percentages']['baik'] }}%; background: #1e7e34">{{ $dist['percentages']['baik'] }}%</span>
                                    @endif
                                    @if($dist['percentages']['cukup'] > 0)
                                        <span style="width: {{ $dist['percentages']['cukup'] }}%; background: #b8860b">{{ $dist['percentages']['cukup'] }}%</span>
                                    @endif
                                    @if($dist['percentages']['belum_tercapai'] > 0)
                                        <span style="width: {{ $dist['percentages']['belum_tercapai'] }}%; background: #c82333">{{ $dist['percentages']['belum_tercapai'] }}%</span>
                                    @endif
                                </div>
                            @else
                                <span style="font-size: 12px; color: #6c757d">Belum ada nilai terisi</span>
                            @endif
                        </div>
                    @empty
                        <div style="font-size: 13px; color: #6c757d">Belum ada tingkat yang terdaftar.</div>
                    @endforelse

                    <div class="cs" style="margin-top: 4px">Dihitung dari nilai rapor STS terhadap interval ketercapaian (KKTP) tiap mapel.</div>
                </div>
            </div>
        </div>
    </div>
</x-layout.app>
