<x-layout.app title="Cetak Rapor STS" :userRole="$isHomeroom ? 'guru' : 'operator'">
    <x-slot:breadcrumb>
        @if($isHomeroom)
            <a href="{{ route('guru.dashboard') }}">Home</a> / <span>Wali Kelas</span> / <span>Cetak Rapor STS</span>
        @else
            <a href="{{ route('operator.dashboard') }}">Home</a> / <span>Rapor</span> / <span>Cetak Rapor</span>
        @endif
    </x-slot:breadcrumb>

    @if(session('success'))
        <x-alert variant="info">{{ session('success') }}</x-alert>
    @endif
    @if(session('error'))
        <x-alert variant="warning">{{ session('error') }}</x-alert>
    @endif

    @if(! $isLocked)
        <div class="alert a-lt" style="margin-bottom: 16px">
            <b>Informasi:</b> Kelas ini <b>belum dikunci</b>. Rapor tetap dapat dicetak untuk keperluan peninjauan, namun akan memuat watermark <b>"DRAFT"</b> hingga kelas dikunci oleh wali kelas.
        </div>
    @endif

    <div class="card">
        <div class="ch">
            <div>
                <div class="ct">Cetak Rapor STS</div>
                <div class="cs">{{ $classroom ? $classroom->name.' · '.count($students).' siswa' : 'Pilih kelas' }}</div>
            </div>
            @if($classroom)
                <div class="acts grp">
                    <a href="{{ route('rapor.classroom.pdf', $classroom) }}" class="btn b-inf" target="_blank">Semua Rapor</a>
                    <a href="{{ route('rapor.leger.pdf', $classroom) }}" class="btn b-suc" target="_blank">Leger PDF</a>
                    <a href="{{ route('rapor.leger.excel', $classroom) }}" class="btn b-suc" style="background: #17692b">Leger Excel</a>
                </div>
            @endif
        </div>

        <div class="cb" style="display: flex; flex-direction: column; gap: 16px">
            @if(! $isHomeroom && $classrooms->count() > 1)
                {{-- Dropdown Pilih Kelas untuk Operator --}}
                <form method="GET" action="{{ route('operator.rapor.index') }}" id="filterClassForm" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; max-width: 680px">
                    <div class="f">
                        <label for="kl">Pilih Kelas</label>
                        <select id="kl" name="classroom_id" class="in" onchange="document.getElementById('filterClassForm').submit()">
                            @foreach($classrooms as $c)
                                <option value="{{ $c->id }}" {{ $classroom && $classroom->id === $c->id ? 'selected' : '' }}>
                                    Tingkat {{ $c->level }} — {{ $c->name }} (Wali: {{ $c->homeroomTeacher?->name ?? 'Belum ditentukan' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
            @endif

            @if($classroom)
                <div class="tb">
                    <table class="t">
                        <thead>
                            <tr>
                                <th style="width: 40px">#</th>
                                <th>NISN</th>
                                <th>Nama</th>
                                <th style="width: 50px; text-align: center">L/P</th>
                                <th>TTL</th>
                                <th style="width: 130px; text-align: center">Aksi</th>
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
                                        <a href="{{ route('rapor.student.pdf', [$classroom, $student]) }}" class="btn b-dan" style="min-height: 28px; padding: 0 10px; font-size: 12px" target="_blank">
                                            Rapor STS
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 24px; color: #6c757d">
                                        Belum ada siswa aktif terdaftar di kelas {{ $classroom->name }}.
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
