<x-layout.app title="Operator · Kegiatan Ekstrakurikuler" userRole="operator">
    <x-slot:sidebar>
        <x-sidebar.operator active="extracurriculars" />
    </x-slot:sidebar>

    <x-slot:breadcrumb>
        <a href="{{ route('operator.dashboard') }}">Home</a> / <span>Ekstrakurikuler</span>
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

    @php
        $activityRows = old('activities', $activities->mapWithKeys(fn ($activity) => [
            (string) $activity->id => [
                'id' => $activity->id,
                'name' => $activity->name,
                'is_mandatory' => $activity->is_mandatory,
                'supervisor_id' => $activity->supervisor_id,
            ],
        ])->all());
        if (blank($activityRows)) {
            $activityRows = ['new-0' => ['name' => '', 'is_mandatory' => false, 'supervisor_id' => '']];
        }
    @endphp

    <div class="card">
        <div class="ch">
            <div>
                <div class="ct">Kegiatan Ekstrakurikuler</div>
                <div class="cs">Periode {{ $period->semester->label() }} · {{ $period->academicYear->name }}</div>
            </div>
            <button type="submit" form="form-extracurricular-definitions" class="btn b-pri">Simpan</button>
        </div>

        <div class="cb">
            <div class="alert a-lt" style="margin-bottom: 14px">
                Setiap kegiatan memiliki satu guru pembina untuk periode ini. Guru pembina akan memilih peserta dan mengisi nilai dari menu guru.
            </div>

            <form method="POST" action="{{ route('operator.extracurricular.activities.update') }}" id="form-extracurricular-definitions">
                @csrf
                <div id="activity-definitions">
                    @foreach($activityRows as $activityKey => $activity)
                        <div class="activity-row" style="display: grid; grid-template-columns: minmax(200px, 1fr) 150px minmax(220px, 1fr) auto; gap: 8px; margin-bottom: 8px; align-items: start">
                            @if(! empty($activity['id']))
                                <input type="hidden" name="activities[{{ $activityKey }}][id]" value="{{ $activity['id'] }}">
                            @endif
                            <input class="in" name="activities[{{ $activityKey }}][name]" value="{{ $activity['name'] ?? '' }}" placeholder="Contoh: Pramuka" maxlength="100" required>
                            <select class="in" name="activities[{{ $activityKey }}][is_mandatory]">
                                <option value="0" @selected(!($activity['is_mandatory'] ?? false))>Pilihan</option>
                                <option value="1" @selected(!empty($activity['is_mandatory']))>Wajib</option>
                            </select>
                            <select class="in" name="activities[{{ $activityKey }}][supervisor_id]" required>
                                <option value="">- Pilih guru pembina -</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" @selected((int) ($activity['supervisor_id'] ?? 0) === $teacher->id)>{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                            @if(! empty($activity['id']))
                                <button type="button" class="btn b-dan" onclick="document.getElementById('delete-activity-{{ $activity['id'] }}').submit()" style="min-height: 34px; padding: 0 10px" aria-label="Hapus kegiatan">×</button>
                            @else
                                <button type="button" class="btn b-dan remove-activity" style="min-height: 34px; padding: 0 10px" aria-label="Hapus kegiatan">×</button>
                            @endif
                        </div>
                    @endforeach
                </div>

                <button type="button" class="btn b-sec" id="add-activity" style="min-height: 32px; padding: 0 10px">+ Tambah kegiatan</button>
            </form>

            @foreach($activities as $activity)
                <form method="POST" action="{{ route('operator.extracurricular.activities.destroy', $activity->id) }}" id="delete-activity-{{ $activity->id }}" onsubmit="return confirm('Hapus kegiatan ini?')" style="display: none">
                    @csrf
                    @method('DELETE')
                </form>
            @endforeach

            <div class="tb" style="margin-top: 20px">
                <table class="t">
                    <thead>
                        <tr>
                            <th style="width: 42px">#</th>
                            <th>Kegiatan</th>
                            <th style="width: 110px">Sifat</th>
                            <th>Guru Pembina</th>
                            <th style="width: 150px">Peserta</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activities as $index => $activity)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td><b>{{ $activity->name }}</b></td>
                                <td>
                                    @if($activity->isMandatory())
                                        <span class="badge" style="background: #e8f4fd; color: #0d6efd; font-weight: 600; padding: 2px 8px; border-radius: 4px; font-size: 12px">Wajib</span>
                                    @else
                                        <span class="badge" style="background: #f1f3f5; color: #495057; font-weight: 600; padding: 2px 8px; border-radius: 4px; font-size: 12px">Pilihan</span>
                                    @endif
                                </td>
                                <td>{{ $activity->supervisor?->name ?? '-' }}</td>
                                <td>{{ $activity->records_count ?? $activity->records()->count() }} siswa</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 24px; color: #6c757d">Belum ada kegiatan ekstrakurikuler.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const definitions = document.getElementById('activity-definitions');
            const teachers = @json($teachers->map(fn ($teacher) => ['id' => $teacher->id, 'name' => $teacher->name])->values());
            let counter = 1;

            document.getElementById('add-activity')?.addEventListener('click', function () {
                const key = 'new-' + (Date.now() + counter++);
                const row = document.createElement('div');
                row.className = 'activity-row';
                row.style.cssText = 'display:grid;grid-template-columns:minmax(200px,1fr) 150px minmax(220px,1fr) auto;gap:8px;margin-bottom:8px;align-items:start';
                row.innerHTML = '<input class="in" name="activities[' + key + '][name]" placeholder="Contoh: Pramuka" maxlength="100" required>' +
                    '<select class="in" name="activities[' + key + '][is_mandatory]"><option value="0">Pilihan</option><option value="1">Wajib</option></select>' +
                    '<select class="in" name="activities[' + key + '][supervisor_id]" required><option value="">- Pilih guru pembina -</option></select>' +
                    '<button type="button" class="btn b-dan remove-activity" style="min-height:34px;padding:0 10px" aria-label="Hapus kegiatan">×</button>';
                const select = row.querySelectorAll('select')[1];
                teachers.forEach(function (teacher) { select.appendChild(new Option(teacher.name, teacher.id)); });
                definitions.appendChild(row);
                row.querySelector('.remove-activity').addEventListener('click', function () { row.remove(); });
            });

            document.querySelectorAll('.remove-activity').forEach(function (button) {
                button.addEventListener('click', function () { this.closest('.activity-row').remove(); });
            });
        });
    </script>
</x-layout.app>
