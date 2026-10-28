<x-layout.app title="Guru Pembina · Ekstrakurikuler" userRole="guru">
    <x-slot:sidebar>
        <x-sidebar.guru active="extracurricular" />
    </x-slot:sidebar>

    <x-slot:breadcrumb>
        <a href="{{ route('guru.dashboard') }}">Home</a> / <span>Guru Pembina</span> / <span>Ekstrakurikuler</span>
    </x-slot:breadcrumb>

    @if(session('success'))
        <x-alert variant="info">{{ session('success') }}</x-alert>
    @endif
    @if(session('error'))
        <x-alert variant="warning">{{ session('error') }}</x-alert>
    @endif
    @if($errors->any())
        <x-alert variant="danger">
            <ul style="margin: 0; padding-left: 18px">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    @if($isLocked)
        <div class="alert" style="background: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; margin-bottom: 16px">
            <b>Perhatian:</b> Kelas {{ $classroom->name }} telah dikunci. Data hanya dapat dilihat sampai kunci dibuka.
        </div>
    @endif

    <div class="card">
        <div class="ch">
            <div>
                <div class="ct">Penilaian Ekstrakurikuler</div>
                <div class="cs">{{ $classroom->name }} · {{ $period->academicYear->name }}</div>
            </div>
            @if(! $isLocked)
                <button type="submit" form="form-extracurricular" class="btn b-pri">Simpan</button>
            @endif
        </div>

        <div class="cb">
            @if($classrooms->count() > 1)
                <form method="GET" action="{{ route('guru.ekstrakurikuler.index') }}" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 14px">
                    <label for="extracurricularClassFilter" style="font-weight: 600">Kelas:</label>
                    <select id="extracurricularClassFilter" name="classroom_id" class="sel" onchange="this.form.submit()">
                        @foreach($classrooms as $availableClassroom)
                            <option value="{{ $availableClassroom->id }}" @selected($availableClassroom->id === $classroom->id)>
                                {{ $availableClassroom->name }} · {{ $availableClassroom->academicYear->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif

            <div class="alert a-lt" style="margin-bottom: 14px">
                Anda adalah guru pembina untuk kegiatan yang tercantum di bawah. Wali kelas tidak mengisi penilaian ini.
            </div>

            <form method="POST" action="{{ route('guru.ekstrakurikuler.update') }}" id="form-extracurricular">
                @csrf
                <input type="hidden" name="classroom_id" value="{{ $classroom->id }}">
                <div class="tb">
                    <table class="t">
                        <thead>
                            <tr>
                                <th style="width: 36px">#</th>
                                <th style="width: 230px">NISN / Nama</th>
                                <th>Kegiatan Ekstrakurikuler</th>
                                <th style="width: 150px">Predikat</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $studentRecords)
                                @php($student = $studentRecords->first()->student)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <b>{{ $student->name }}</b><br>
                                        <span class="cs">{{ $student->nisn }}</span>
                                    </td>
                                    <td colspan="3">
                                        @foreach($studentRecords as $record)
                                            <div style="display: grid; grid-template-columns: minmax(180px, 1fr) 140px minmax(180px, 1fr); gap: 8px; margin-bottom: 8px; align-items: start">
                                                <input class="in" value="{{ $record->name }}" disabled>
                                                <select class="in" name="records[{{ $record->id }}][predicate]" {{ $isLocked ? 'disabled' : '' }}>
                                                    @foreach(['Sangat Baik', 'Baik', 'Cukup', 'Kurang'] as $predicate)
                                                        <option value="{{ $predicate }}" @selected($record->predicate === $predicate)>{{ $predicate }}</option>
                                                    @endforeach
                                                </select>
                                                <input class="in" name="records[{{ $record->id }}][description]" value="{{ $record->description }}" placeholder="Keterangan (opsional)" maxlength="255" {{ $isLocked ? 'disabled' : '' }}>
                                            </div>
                                        @endforeach
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 24px; color: #6c757d">
                                        Belum ada kegiatan ekstrakurikuler yang ditugaskan kepada Anda di kelas ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>
</x-layout.app>
