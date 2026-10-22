<x-layout.app title="Dashboard Operator" userRole="operator">
    <x-slot:sidebar>
        <x-sidebar.operator active="dashboard" />
    </x-slot:sidebar>

    <x-slot:breadcrumb>
        <a href="#">Home</a> / <a href="#">Admin</a> / <span>Dashboard</span>
    </x-slot:breadcrumb>

    @if(session('success'))
        <x-alert variant="info">{{ session('success') }}</x-alert>
    @endif

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px">
        <div class="stat" style="background: #1b6ec2">
            <div><b>{{ $teacherCount ?? 0 }}</b><span>Guru</span></div>
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
        </div>
        <div class="stat" style="background: #1e7e34">
            <div><b>{{ $studentCount ?? 0 }}</b><span>Siswa</span></div>
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="9" cy="8" r="3.5"/><circle cx="17" cy="9" r="2.5"/><path d="M2 20c0-3.5 3-5 7-5s7 1.5 7 5M16 14c3 0 6 1.2 6 4"/></svg>
        </div>
        <div class="stat" style="background: #c82333">
            <div><b>{{ $classCount ?? 0 }}</b><span>Kelas</span></div>
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 10l9-6 9 6M5 10v9M19 10v9M9 10v9M15 10v9M3 21h18"/></svg>
        </div>
        <div class="stat" style="background: #f2b01e; color: #3b2a00">
            <div><b>{{ $submittedPercent ?? 0 }}%</b><span>Nilai STS terkirim</span></div>
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4z"/></svg>
        </div>
    </div>

    <div class="card" style="overflow: hidden">
        <div style="background: #e0a10e; color: #3b2a00; padding: 10px 16px; font-weight: 700; font-size: 13px">Info Aplikasi</div>
        <x-alert variant="warning" style="border-radius: 0; padding: 16px">
            Selamat datang di aplikasi pengolahan nilai Sumatif Tengah Semester (STS). Nilai sumatif lingkup materi yang diinput guru di sini dipakai untuk rapor STS, dan dapat diekspor ke template Excel RDM saat akhir semester.<br><br>
            <b>Periode Aktif:</b> {{ $activePeriod ? $activePeriod->badge_text : 'Belum ada periode aktif yang dipilih.' }}
        </x-alert>
    </div>

    <x-card title="Aktivitas Terbaru">
        <div class="tb">
            <table class="t">
                <thead>
                    <tr>
                        <th style="width: 48px"></th>
                        <th>User</th>
                        <th>Aktivitas</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activities as $act)
                        <tr>
                            <td>
                                <div class="ava" style="background: #1b6ec2; color: #fff; width: 30px; height: 30px">
                                    {{ strtoupper(substr($act->user->name ?? 'A', 0, 1)) }}
                                </div>
                            </td>
                            <td>
                                <b>{{ $act->user->name ?? 'Sistem' }}</b>
                                <div class="cs">{{ $act->user->role->label() ?? '-' }}</div>
                            </td>
                            <td>{{ $act->description }}</td>
                            <td class="cs">{{ $act->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="n cs" style="padding: 24px">Belum ada riwayat aktivitas yang tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($activities, 'hasPages') && $activities->hasPages())
            <div class="pg">
                {{ $activities->links() }}
            </div>
        @endif
    </x-card>
</x-layout.app>
