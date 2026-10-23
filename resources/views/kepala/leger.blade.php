<x-layout.app title="Kepala Madrasah · Rekap Nilai Leger" userRole="kepala">
    <x-slot:breadcrumb>
        <a href="{{ route('kepala.dashboard') }}">Home</a> / <span>Kepala Madrasah</span> / <span>Rekap Nilai (Leger)</span>
    </x-slot:breadcrumb>

    <div class="card">
        <div class="ch">
            <div>
                <div class="ct">Rekap Nilai Rapor STS (Leger)</div>
                <div class="cs">
                    @if($selectedClassroom)
                        {{ $selectedClassroom->name }} · Wali Kelas {{ $selectedClassroom->homeroomTeacher?->name ?? '–' }} · {{ $submittedCount }} dari {{ $totalAssignments }} mapel terkirim
                    @else
                        Pilih kelas
                    @endif
                </div>
            </div>
            @if($selectedClassroom)
                <div class="acts">
                    <a href="{{ route('rapor.leger.excel', $selectedClassroom) }}" class="btn b-suc">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                        Unduh Excel
                    </a>
                    <a href="{{ route('rapor.leger.pdf', $selectedClassroom) }}" class="btn b-dan" target="_blank" rel="noopener">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                        Unduh PDF
                    </a>
                </div>
            @endif
        </div>

        <div class="cb" style="display: flex; flex-direction: column; gap: 16px">
            {{-- Form Filter --}}
            <form method="GET" action="{{ route('kepala.leger.index') }}" id="filterLegerForm" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; max-width: 760px">
                <div class="f">
                    <label for="tk">Tingkat</label>
                    <select id="tk" name="tingkat" class="in" onchange="document.getElementById('filterLegerForm').submit()">
                        <option value="">Semua Tingkat</option>
                        @foreach($levels as $lvl)
                            <option value="{{ $lvl }}" {{ (string)$selectedLevel === (string)$lvl ? 'selected' : '' }}>
                                Tingkat {{ $lvl }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="f">
                    <label for="kl">Kelas</label>
                    <select id="kl" name="classroom_id" class="in" onchange="document.getElementById('filterLegerForm').submit()">
                        @foreach($classrooms as $c)
                            <option value="{{ $c->id }}" {{ $selectedClassroom && $selectedClassroom->id === $c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="f">
                    <label for="tp">Tampilkan</label>
                    <select id="tp" name="tampilkan" class="in" onchange="document.getElementById('filterLegerForm').submit()">
                        <option value="rapor" {{ $viewType === 'rapor' ? 'selected' : '' }}>Nilai Rapor STS</option>
                        <option value="sumatif" {{ $viewType === 'sumatif' ? 'selected' : '' }}>Rerata Sumatif LM</option>
                        <option value="sts" {{ $viewType === 'sts' ? 'selected' : '' }}>Nilai Tes STS</option>
                    </select>
                </div>
            </form>

            @if($selectedClassroom)
                <div class="tb">
                    <table class="t">
                        <thead>
                            <tr>
                                <th style="width: 35px">No</th>
                                <th style="text-align: left">Nama</th>
                                @foreach($subjectColumns as $col)
                                    <th title="{{ $col['name'] }} (KKTP: {{ $col['kktp'] }})">
                                        {{ $col['code'] }}
                                    </th>
                                @endforeach
                                <th style="width: 50px">Jumlah</th>
                                <th style="width: 50px">Rerata</th>
                                <th style="width: 45px">Rank</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $idx => $r)
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td class="nm"><b>{{ $r['student']->name }}</b></td>
                                    @foreach($subjectColumns as $col)
                                        @php
                                            $sc = $r['scores'][$col['id']] ?? null;
                                        @endphp
                                        <td>
                                            @if($sc && $sc['is_below'])
                                                <span class="lo">{{ $sc['formatted'] }}</span>
                                            @else
                                                {{ $sc ? $sc['formatted'] : '–' }}
                                            @endif
                                        </td>
                                    @endforeach
                                    <td style="font-weight: bold">{{ $r['total'] }}</td>
                                    <td style="font-weight: bold">{{ $r['formatted_average'] }}</td>
                                    <td style="font-weight: bold">{{ $r['rank'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 5 + count($subjectColumns) }}" style="text-align: center; padding: 20px; color: #6c757d">
                                        Belum ada siswa aktif terdaftar di kelas ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($rows->isNotEmpty())
                            <tfoot>
                                <tr>
                                    <td></td>
                                    <td style="text-align: left; font-weight: bold">Rerata kelas</td>
                                    @foreach($subjectColumns as $col)
                                        <td style="font-weight: bold">{{ $classAverages[$col['id']] ?? '–' }}</td>
                                    @endforeach
                                    <td></td>
                                    <td style="font-weight: bold">{{ $overallAverage }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 18px; font-size: 12.5px; color: #495057">
                    <span><span class="lo">66</span> = di bawah nilai batas ketercapaian mapel</span>
                    <span>– = nilai belum dikirim guru</span>
                    <span>Rank hanya untuk informasi internal pemantauan, tidak dicetak di rapor</span>
                </div>
            @else
                <div class="alert a-lt">Belum ada kelas yang dibuat pada periode aktif.</div>
            @endif
        </div>
    </div>
</x-layout.app>
