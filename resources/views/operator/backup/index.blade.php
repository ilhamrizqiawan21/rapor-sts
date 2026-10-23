<x-layout.app title="Backup & Restore" userRole="operator">
    <x-slot:sidebar>
        <x-sidebar.operator active="backup" />
    </x-slot:sidebar>

    <x-slot:breadcrumb>
        <a href="{{ route('operator.dashboard') }}">Home</a> / <span>Backup & Restore</span>
    </x-slot:breadcrumb>

    @php
        $latest = $files[0] ?? null;
        $scheduleLabel = match ($schedule) {
            'daily' => 'Setiap hari, '.$time,
            'weekly' => 'Setiap Minggu, '.$time,
            default => 'Tidak aktif',
        };
    @endphp

    <div class="backup-page">
        <div class="backup-heading">
            <div>
                <span class="backup-eyebrow">Administrasi data</span>
                <h1>Backup & Restore</h1>
                <p>Buat salinan database, unduh untuk disimpan di tempat lain, atau pulihkan data dari backup.</p>
            </div>
            <span class="backup-scope">Database saja</span>
        </div>

        @if(session('success'))
            <x-alert variant="info">{{ session('success') }}</x-alert>
        @endif
        @if(session('error'))
            <x-alert variant="warning">{{ session('error') }}</x-alert>
        @endif
        @if($errors->any())
            <x-alert variant="warning">{{ $errors->first() }}</x-alert>
        @endif

        <div class="backup-overview" aria-label="Ringkasan backup">
            <div class="backup-metric">
                <span>Backup terakhir</span>
                <strong>{{ $latest ? $latest['created_at']->format('d M Y, H:i') : 'Belum ada' }}</strong>
            </div>
            <div class="backup-metric">
                <span>Backup di server</span>
                <strong>{{ count($files) }} <small>berkas</small></strong>
            </div>
            <div class="backup-metric">
                <span>Jadwal otomatis</span>
                <strong>{{ $scheduleLabel }}</strong>
                <small>Menyimpan {{ $keep }} backup terbaru</small>
            </div>
        </div>

        <div class="backup-actions">
            <section class="backup-action-card" aria-labelledby="backup-create-title">
                <div class="backup-action-icon backup-action-icon-blue" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h13l3 3v13H4zM8 4v6h8V4M8 20v-7h8v7"/></svg>
                </div>
                <div class="backup-action-copy">
                    <span class="backup-step">01 · Buat salinan</span>
                    <h2 id="backup-create-title">Backup database</h2>
                    <p>Simpan kondisi data saat ini sebagai berkas backup baru di server.</p>
                </div>
                <form method="POST" action="{{ route('operator.backup.store') }}">
                    @csrf
                    <button type="submit" class="btn b-pri">Buat Backup Sekarang</button>
                </form>
            </section>

            <section class="backup-action-card" aria-labelledby="backup-download-title">
                <div class="backup-action-icon backup-action-icon-green" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v3h16v-3"/></svg>
                </div>
                <div class="backup-action-copy">
                    <span class="backup-step">02 · Simpan salinan</span>
                    <h2 id="backup-download-title">Download backup</h2>
                    <p>Unduh backup terbaru ke komputer atau pilih berkas lain dari daftar di bawah.</p>
                </div>
                @if($latest)
                    <a class="btn b-suc" href="{{ route('operator.backup.download', $latest['name']) }}">Download Terbaru</a>
                @else
                    <span class="backup-muted-action">Buat backup terlebih dahulu</span>
                @endif
            </section>
        </div>

        <x-card title="Daftar Backup" subtitle="Berkas yang tersimpan di server dan siap diunduh atau dipulihkan.">
            <div class="tb">
                <table class="t backup-table">
                    <thead>
                        <tr><th>Berkas</th><th>Tanggal</th><th>Ukuran</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse($files as $f)
                            <tr>
                                <td><span class="backup-file-icon" aria-hidden="true">SQL</span><span class="backup-filename">{{ $f['name'] }}</span></td>
                                <td>{{ $f['created_at']->format('d M Y, H:i') }}</td>
                                <td>{{ number_format($f['size'] / 1024, 1) }} KB</td>
                                <td><a class="btn b-sec" href="{{ route('operator.backup.download', $f['name']) }}" aria-label="Download {{ $f['name'] }}">Download</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="backup-empty">Belum ada backup. Klik <strong>Buat Backup Sekarang</strong> untuk memulai.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <section class="backup-restore" aria-labelledby="backup-restore-title">
            <div class="backup-restore-heading">
                <div class="backup-action-icon backup-action-icon-amber" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 11a8 8 0 1 1 2 6M4 4v7h7M12 7v5l3 2"/></svg>
                </div>
                <div>
                    <span class="backup-step">03 · Pulihkan data</span>
                    <h2 id="backup-restore-title">Restore database</h2>
                    <p>Pilih backup di server atau unggah berkas backup dari komputer.</p>
                </div>
            </div>

            <div class="backup-warning"><strong>Perhatian:</strong> Restore mengganti seluruh data database saat ini. Sistem membuat backup pengaman lebih dulu. Jalankan saat pengguna lain tidak sedang mengubah data.</div>

            <div class="backup-restore-options">
                <form method="POST" action="{{ route('operator.backup.restore') }}" class="backup-restore-form">
                    @csrf
                    <input type="hidden" name="source" value="stored">
                    <h3>Dari backup di server</h3>
                    <p>Pilih salah satu berkas yang tersedia pada daftar backup.</p>
                    <div class="f">
                        <label for="stored-backup">Berkas backup</label>
                        <select class="in" id="stored-backup" name="file" required @disabled(empty($files))>
                            <option value="">Pilih berkas backup</option>
                            @foreach($files as $f)
                                <option value="{{ $f['name'] }}">{{ $f['name'] }} · {{ $f['created_at']->format('d/m/Y H:i') }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="f">
                        <label for="stored-confirmation">Ketik RESTORE untuk konfirmasi</label>
                        <input class="in" id="stored-confirmation" name="confirmation" autocomplete="off" required pattern="RESTORE" placeholder="RESTORE">
                    </div>
                    <button class="btn b-dan" type="submit" @disabled(empty($files)) onclick="return confirm('Ganti seluruh data database dengan backup yang dipilih?')">Restore dari server</button>
                </form>

                <form method="POST" action="{{ route('operator.backup.restore') }}" enctype="multipart/form-data" class="backup-restore-form">
                    @csrf
                    <input type="hidden" name="source" value="upload">
                    <h3>Dari komputer</h3>
                    <p>Gunakan berkas backup yang sebelumnya sudah diunduh.</p>
                    <div class="f">
                        <label for="uploaded-backup">Berkas .sql.gz (maks. 50 MB)</label>
                        <input class="in backup-file-input" id="uploaded-backup" type="file" name="archive" accept=".gz,.sql.gz" required>
                    </div>
                    <div class="f">
                        <label for="upload-confirmation">Ketik RESTORE untuk konfirmasi</label>
                        <input class="in" id="upload-confirmation" name="confirmation" autocomplete="off" required pattern="RESTORE" placeholder="RESTORE">
                    </div>
                    <button class="btn b-dan" type="submit" onclick="return confirm('Ganti seluruh data database dengan berkas yang diunggah?')">Upload & Restore</button>
                </form>
            </div>
        </section>
    </div>
</x-layout.app>
