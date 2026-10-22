<x-layout.app title="Dashboard Guru" userRole="guru">
    <x-slot:breadcrumb>
        <a href="{{ route('guru.dashboard') }}">Home</a> / <span>Dashboard</span>
    </x-slot:breadcrumb>

    @if(session('success'))
        <x-alert variant="info">{{ session('success') }}</x-alert>
    @endif
    @if(session('error'))
        <x-alert variant="warning">{{ session('error') }}</x-alert>
    @endif

    {{-- Ringkasan Status --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px">
        <div class="card" style="padding: 16px; border-left: 4px solid #1b6ec2">
            <div style="font-size: 12px; color: #6c757d; font-weight: 600; text-transform: uppercase">Total Kelas Diampu</div>
            <div style="font-size: 26px; font-weight: 700; color: #1b6ec2; margin-top: 4px">{{ $totalClasses }}</div>
        </div>
        <div class="card" style="padding: 16px; border-left: 4px solid #1e7e34">
            <div style="font-size: 12px; color: #6c757d; font-weight: 600; text-transform: uppercase">Nilai Terkirim</div>
            <div style="font-size: 26px; font-weight: 700; color: #1e7e34; margin-top: 4px">{{ $submittedClasses }}</div>
        </div>
        <div class="card" style="padding: 16px; border-left: 4px solid #c82333">
            <div style="font-size: 12px; color: #6c757d; font-weight: 600; text-transform: uppercase">Belum Dikirim (Draft)</div>
            <div style="font-size: 26px; font-weight: 700; color: #c82333; margin-top: 4px">{{ $draftClasses }}</div>
        </div>
    </div>

    <x-card title="Daftar Kelas Mengajar">
        <x-slot:subtitle>Periode Aktif: {{ $activePeriod?->badge_text }}</x-slot:subtitle>

        <div class="tb">
            <table class="t">
                <thead>
                    <tr>
                        <th style="width: 40px">#</th>
                        <th>Mata Pelajaran</th>
                        <th>Kelas</th>
                        <th>Tingkat</th>
                        <th>JTM</th>
                        <th>Bobot (S : STS)</th>
                        <th>KKTP</th>
                        <th>Status</th>
                        <th style="width: 180px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assignments as $idx => $a)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td><b>{{ $a->subject->name }}</b> ({{ $a->subject->code }})</td>
                            <td>{{ $a->classroom->name }}</td>
                            <td>Tingkat {{ $a->classroom->level }}</td>
                            <td>{{ $a->hours_per_week }} Jam</td>
                            <td>{{ (float)$a->weight_summative }} : {{ (float)$a->weight_sts }}</td>
                            <td>{{ (float)$a->kktp }}</td>
                            <td>
                                @if($a->isSubmitted())
                                    <span class="pill" style="background: #1e7e34">✓ Terkirim</span>
                                @else
                                    <span class="pill" style="background: #c82333">✕ Draft</span>
                                @endif
                            </td>
                            <td>
                                <div style="display: flex; gap: 4px">
                                    <a href="{{ route('guru.penilaian.index', $a) }}" class="btn b-pri" style="min-height: 28px; padding: 0 8px; font-size: 12px">Penilaian</a>
                                    <a href="{{ route('guru.bobot.edit', $a) }}" class="btn b-sec" style="min-height: 28px; padding: 0 8px; font-size: 12px">Bobot</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="n cs">Anda belum memiliki penugasan mengajar pada periode aktif ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</x-layout.app>
