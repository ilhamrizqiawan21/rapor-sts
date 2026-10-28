<x-layout.app title="Kepala Madrasah · Lihat Rapor STS" userRole="kepala">
    <x-slot:breadcrumb>
        <a href="{{ route('kepala.dashboard') }}">Home</a> / <span>Kepala Madrasah</span> / <span>Lihat Rapor STS</span>
    </x-slot:breadcrumb>

    @if(! $isLocked && $selectedClassroom)
        <div class="alert a-lt" style="margin-bottom: 16px">
            <b>Informasi:</b> Kelas {{ $selectedClassroom->name }} <b>belum dikunci</b> oleh wali kelas. Rapor tetap dapat dicetak untuk keperluan peninjauan.
        </div>
    @endif

    <div class="card">
        <div class="ch">
            <div>
                <div class="ct">Lihat Rapor STS Siswa</div>
                <div class="cs">{{ $selectedClassroom ? $selectedClassroom->name.' · '.count($students).' siswa' : 'Pilih kelas' }}</div>
            </div>
            @if($selectedClassroom)
                <div class="acts">
                    <a href="{{ route('rapor.classroom.pdf', $selectedClassroom) }}" class="btn b-inf" target="_blank">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 2h9l5 5v15H6zM14 2v6h6"/></svg>
                        Semua Rapor (1 Kelas)
                    </a>
                </div>
            @endif
        </div>

        <div class="cb" style="display: flex; flex-direction: column; gap: 16px">
            {{-- Filter Kelas --}}
            <form method="GET" action="{{ route('kepala.rapor.index') }}" id="filterClassForm" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; max-width: 680px">
                <div class="f">
                    <label for="kl">Pilih Kelas</label>
                    <select id="kl" name="classroom_id" class="in" onchange="document.getElementById('filterClassForm').submit()">
                        @foreach($classrooms as $c)
                            <option value="{{ $c->id }}" {{ $selectedClassroom && $selectedClassroom->id === $c->id ? 'selected' : '' }}>
                                Tingkat {{ $c->level }} — {{ $c->name }} (Wali: {{ $c->homeroomTeacher?->name ?? 'Belum ditentukan' }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>

            @if($selectedClassroom)
                <div class="tb">
                    <table class="t">
                        <thead>
                            <tr>
                                <th style="width: 40px">#</th>
                                <th>NISN</th>
                                <th>Nama Siswa</th>
                                <th style="width: 50px; text-align: center">L/P</th>
                                <th>TTL</th>
                                <th style="width: 120px; text-align: center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $idx => $student)
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td>{{ $student->nisn }}</td>
                                    <td><b>{{ $student->name }}</b></td>
                                    <td style="text-align: center">{{ $student->gender }}</td>
                                    <td>
                                        @if($student->birth_place || $student->birth_date)
                                            {{ $student->birth_place ? $student->birth_place.', ' : '' }}{{ $student->birth_date?->format('d-m-Y') }}
                                        @else
                                            –
                                        @endif
                                    </td>
                                    <td style="text-align: center">
                                        <a href="{{ route('rapor.student.pdf', [$selectedClassroom, $student]) }}" class="btn b-inf" style="min-height: 28px; padding: 0 10px; font-size: 12px" target="_blank" rel="noopener">
                                            Rapor STS
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 24px; color: #6c757d">
                                        Belum ada siswa aktif terdaftar di kelas {{ $selectedClassroom->name }}.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert a-lt">Belum ada kelas yang terdaftar pada tahun pelajaran aktif.</div>
            @endif
        </div>
    </div>
</x-layout.app>
