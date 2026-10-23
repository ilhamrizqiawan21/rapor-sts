<x-layout.app title="Backup Database" userRole="operator">
    <x-slot:sidebar>
        <x-sidebar.operator active="backup" />
    </x-slot:sidebar>

    <x-slot:breadcrumb>
        <a href="{{ route('operator.dashboard') }}">Home</a> / <span>Backup Database</span>
    </x-slot:breadcrumb>

    @if(session('success'))
        <x-alert variant="info">{{ session('success') }}</x-alert>
    @endif
    @if(session('error'))
        <x-alert variant="warning">{{ session('error') }}</x-alert>
    @endif

    <x-card title="Backup Database">
        <div style="display: flex; flex-direction: column; gap: 12px">
            <p style="margin: 0">
                Backup otomatis:
                <strong>{{ match($schedule) { 'daily' => 'setiap hari pukul '.$time, 'weekly' => 'setiap Minggu pukul '.$time, default => 'nonaktif' } }}</strong>
                · menyimpan {{ $keep }} berkas terbaru.
            </p>
            <span class="hint">Backup otomatis memerlukan cron server: <code>* * * * * php artisan schedule:run</code>. Restore dilakukan manual oleh teknisi.</span>
            <form method="POST" action="{{ route('operator.backup.store') }}">
                @csrf
                <button type="submit" class="btn b-pri">Buat Backup Sekarang</button>
            </form>
        </div>
    </x-card>

    <x-card title="Daftar Backup">
        <table class="t">
            <thead>
                <tr><th>Berkas</th><th>Dibuat</th><th>Ukuran</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($files as $f)
                    <tr>
                        <td>{{ $f['name'] }}</td>
                        <td>{{ $f['created_at']->format('d/m/Y H:i') }}</td>
                        <td>{{ number_format($f['size'] / 1024, 1) }} KB</td>
                        <td><a class="btn" href="{{ route('operator.backup.download', $f['name']) }}">Unduh</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4">Belum ada backup.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-card>
</x-layout.app>
