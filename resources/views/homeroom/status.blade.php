<x-layout.app title="Status Nilai Rapor STS" userRole="guru">
    <x-slot:breadcrumb>
        <a href="{{ route('guru.dashboard') }}">Home</a> / <span>Wali Kelas</span> / <span>Status Nilai</span>
    </x-slot:breadcrumb>

    @if(session('success'))
        <x-alert variant="info">{{ session('success') }}</x-alert>
    @endif
    @if(session('error'))
        <x-alert variant="warning">{{ session('error') }}</x-alert>
    @endif
    @if(session('info'))
        <x-alert variant="info">{{ session('info') }}</x-alert>
    @endif

    <div class="card">
        <div class="ch">
            <div>
                <div class="ct">Status Nilai Rapor STS — {{ $classroom->name }}</div>
                <div class="cs">Status pengiriman nilai oleh guru mata pelajaran</div>
            </div>
            <div class="acts">
                @if($isLocked)
                    <form method="POST" action="{{ route('wali.status.unlock', $classroom) }}" onsubmit="return confirm('Apakah Anda yakin ingin membuka kunci nilai kelas ini?')">
                        @csrf
                        <button type="submit" class="btn b-dan">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>
                            Terkunci (Buka Kunci)
                        </button>
                    </form>
                @elseif($allSubmitted)
                    <form method="POST" action="{{ route('wali.status.lock', $classroom) }}" onsubmit="return confirm('Semua nilai sudah terkirim. Kunci nilai rapor kelas ini?')">
                        @csrf
                        <button type="submit" class="btn b-suc">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            Kunci Nilai Kelas
                        </button>
                    </form>
                @else
                    <button type="button" class="btn b-inf" style="opacity: 0.85; cursor: not-allowed" title="Semua mapel harus terkirim sebelum kelas dapat dikunci" disabled>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="1"/><path d="M8 11V7a4 4 0 0 1 8 0"/></svg>
                        Belum Dikunci Walas (Belum Lengkap)
                    </button>
                @endif
            </div>
        </div>

        <div class="cb" style="display: flex; flex-direction: column; gap: 16px">
            {{-- Progres Bar --}}
            <div style="border: 1px solid #dee2e6; border-radius: 4px">
                <div style="padding: 10px 14px; border-bottom: 1px solid #dee2e6; font-size: 13px; font-weight: 600">Progres Nilai</div>
                <div style="padding: 14px; display: flex; flex-direction: column; gap: 12px">
                    <div style="display: flex; flex-direction: column; gap: 6px">
                        <div style="display: flex; justify-content: space-between; font-size: 13px">
                            <span>Sumatif Lingkup Materi</span>
                            <b>{{ $progressLm }}%</b>
                        </div>
                        <div class="bar">
                            <span style="width: {{ $progressLm }}%">{{ $progressLm }}%</span>
                        </div>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 6px">
                        <div style="display: flex; justify-content: space-between; font-size: 13px">
                            <span>Nilai STS</span>
                            <b>{{ $progressSts }}%</b>
                        </div>
                        <div class="bar">
                            <span style="width: {{ $progressSts }}%">{{ $progressSts }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabel Status Nilai Guru --}}
            <div class="tb">
                <table class="t">
                    <thead>
                        <tr>
                            <th style="width: 40px">#</th>
                            <th>Mata Pelajaran</th>
                            <th>Guru</th>
                            <th>Sumatif LM</th>
                            <th>STS</th>
                            <th>Status</th>
                            <th style="width: 120px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tableRows as $row)
                            @if($row['is_header'])
                                <tr style="background: #f8f9fa">
                                    <td><b>{{ $row['no'] }}</b></td>
                                    <td colspan="6"><b>{{ $row['subject_name'] }}</b></td>
                                </tr>
                            @else
                                <tr>
                                    <td>{{ $row['no'] }}</td>
                                    <td style="{{ $row['letter'] ? 'padding-left: 24px; font-style: italic' : '' }}">
                                        {{ $row['letter'] }} {{ $row['subject_name'] }}
                                    </td>
                                    <td>{{ $row['teacher_name'] }}</td>
                                    <td>{{ $row['lm_text'] }}</td>
                                    <td>{{ $row['sts_text'] }}</td>
                                    <td>
                                        @if($row['is_submitted'])
                                            <span class="pill" style="background: #1e7e34">✓ Terkirim</span>
                                        @else
                                            <span class="pill" style="background: #c82333">✕ Belum Kirim</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($row['is_submitted'] && ! $isLocked)
                                            <form method="POST" action="{{ route('wali.status.unsubmit', $row['assignment']) }}" onsubmit="return confirm('Batalkan pengiriman nilai mata pelajaran ini agar guru dapat mengedit?')">
                                                @csrf
                                                <button type="submit" class="btn b-dan" style="padding: 2px 8px; font-size: 11px; min-height: 26px">
                                                    Batal Kirim
                                                </button>
                                            </form>
                                        @elseif($row['is_submitted'] && $isLocked)
                                            <span style="font-size: 11px; color: #6c757d">Terkunci</span>
                                        @else
                                            –
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 20px; color: #6c757d">
                                    Belum ada penugasan mengajar terdaftar pada kelas ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layout.app>
