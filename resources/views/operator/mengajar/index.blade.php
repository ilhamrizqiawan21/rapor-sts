<x-layout.app title="Data Mengajar Guru" userRole="operator">
    <x-slot:sidebar>
        <x-sidebar.operator active="mengajar" />
    </x-slot:sidebar>

    <x-slot:breadcrumb>
        <a href="{{ route('operator.dashboard') }}">Home</a> / <a href="#">Admin</a> / <span>Mengajar</span>
    </x-slot:breadcrumb>

    @if(session('success'))
        <x-alert variant="info">{{ session('success') }}</x-alert>
    @endif
    @if(session('error'))
        <x-alert variant="warning">{{ session('error') }}</x-alert>
    @endif

    <x-card title="Data Mengajar Guru" subtitle="Master Data">
        <x-slot:actions>
            <div class="grp">
                <button type="button" class="btn b-dan" onclick="document.getElementById('modalSalinMengajar').showModal()">Salin Mengajar</button>
                <a href="{{ route('operator.teachers.index') }}" class="btn b-war">Daftar Guru</a>
            </div>
        </x-slot:actions>

        <div style="display: flex; flex-direction: column; gap: 16px">
            <x-alert variant="warning">
                Untuk mengatur guru pengampu mata pelajaran berdasarkan kelas, silakan pilih kelas di bawah.<br>
                Pilih guru dan Jam Tatap Muka (JTM), lalu klik <b>Simpan</b> pada baris mata pelajaran terkait.
            </x-alert>

            <div style="display: flex; gap: 12px; align-items: center; max-width: 480px">
                <label for="classroomSelect" style="font-weight: 600">Pilih Kelas:</label>
                <select id="classroomSelect" class="sel" style="flex: 1" onchange="location.href = '{{ route('operator.mengajar.index') }}?classroom_id=' + this.value">
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" @selected($selectedClass?->id == $c->id)>{{ $c->name }} (Tingkat {{ $c->level }})</option>
                    @endforeach
                </select>
            </div>

            @if($selectedClass && $activePeriod)
                <div class="tb">
                    <table class="t">
                        <thead>
                            <tr>
                                <th style="width: 40px">#</th>
                                <th>Mata Pelajaran</th>
                                <th>Induk</th>
                                <th>Kelompok</th>
                                <th style="width: 80px">JTM</th>
                                <th>Guru Pengampu</th>
                                <th style="width: 140px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($subjects as $idx => $sbj)
                                @php
                                    $isParent = $sbj->isParent();
                                    $assignment = $existingAssignments[$sbj->id] ?? null;
                                @endphp
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td @if($sbj->parent_id) style="padding-left: 28px; font-style: italic" @endif>
                                        @if($isParent)
                                            <b>{{ $sbj->name }}</b>
                                        @else
                                            {{ $sbj->name }} ({{ $sbj->code }})
                                        @endif
                                    </td>
                                    <td>{{ $sbj->parent ? $sbj->parent->code : '-' }}</td>
                                    <td>{{ ucfirst($sbj->group) }}</td>

                                    @if($isParent)
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    @else
                                        <form method="POST" action="{{ route('operator.mengajar.store') }}" id="formAssignment{{ $sbj->id }}">
                                            @csrf
                                            <input type="hidden" name="period_id" value="{{ $activePeriod->id }}">
                                            <input type="hidden" name="classroom_id" value="{{ $selectedClass->id }}">
                                            <input type="hidden" name="subject_id" value="{{ $sbj->id }}">
                                            <input type="hidden" name="kktp" value="{{ $assignment?->kktp ?? $defaultKktp }}">

                                            <td>
                                                <input type="number" name="hours_per_week" class="in" style="width: 60px; height: 32px" value="{{ $assignment?->hours_per_week ?? 2 }}" min="0">
                                            </td>
                                            <td>
                                                <select name="teacher_id" class="sel" style="min-width: 220px; height: 34px" required>
                                                    <option value="">- Pilih Guru -</option>
                                                    @foreach($teachers as $t)
                                                        <option value="{{ $t->id }}" @selected($assignment?->teacher_id == $t->id)>{{ $t->name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <div style="display: flex; gap: 4px">
                                                    <button type="submit" class="btn b-pri" style="min-height: 30px; padding: 0 10px; font-size: 12px">Simpan</button>
                                        </form>
                                                    @if($assignment)
                                                        <form method="POST" action="{{ route('operator.mengajar.destroy', $assignment) }}" onsubmit="return confirm('Hapus guru pengampu mapel ini?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn b-dan" style="min-height: 30px; padding: 0 8px; font-size: 12px">Hapus</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-alert variant="warning">Belum ada periode aktif atau kelas yang dipilih.</x-alert>
            @endif
        </div>
    </x-card>

    {{-- Modal Salin Mengajar --}}
    <x-modal id="modalSalinMengajar" title="Salin Pembagian Mengajar">
        <form method="POST" action="{{ route('operator.mengajar.copy') }}" id="formSalinMengajar">
            @csrf
            <input type="hidden" name="target_period_id" value="{{ $activePeriod?->id }}">

            <div class="alert a-lt" style="margin-bottom: 12px">
                Menyalin seluruh data mengajar guru dari periode lampau ke periode aktif (<b>{{ $activePeriod?->badge_text }}</b>). Data yang sudah ada di periode aktif tidak akan ditimpa.
            </div>

            <div class="f">
                <label for="source_period_id">Salin Dari Periode Sumber</label>
                <select id="source_period_id" name="source_period_id" class="in" required>
                    <option value="">- Pilih Periode Sumber -</option>
                    @foreach($otherPeriods as $p)
                        <option value="{{ $p->id }}">{{ $p->badge_text }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        <x-slot:footer>
            <button type="button" class="btn b-dan" onclick="document.getElementById('modalSalinMengajar').close()">Batal</button>
            <button type="button" class="btn b-pri" onclick="document.getElementById('formSalinMengajar').submit()">Salin Sekarang</button>
        </x-slot:footer>
    </x-modal>
</x-layout.app>
