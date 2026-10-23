# Audit backend sebelum serah source code

Tanggal: 23 Oktober 2026

## Cakupan dan hasil

- Ditinjau: rute dan middleware, login dan otorisasi peran, validasi input, impor/ekspor Excel, alur nilai dan periode, laporan, migrasi, backup/restore, serta perubahan sidebar.
- `php artisan test`: 159 tes lulus, 683 assertion.
- `php artisan migrate:status`: seluruh migrasi yang ada sudah dijalankan pada instalasi ini.
- `composer validate`, pemeriksaan sintaks PHP/JS, `php artisan view:cache`, `npm run build`, dan `git diff --check`: lulus.
- `.env`, database SQLite, dan berkas backup tidak terlacak oleh Git. Backup database nyata berhasil dibuat dan arsip gzip lolos pemeriksaan integritas.

## Perbaikan dalam audit ini

- Akun yang sudah dinonaktifkan dikeluarkan dari sesi pada permintaan berikutnya.
- Login menolak periode yang belum tersedia; login tidak lagi membuat periode baru.
- Seeder produksi mewajibkan `INITIAL_OPERATOR_PASSWORD` minimal 12 karakter untuk pembuatan akun awal `admin`.
- Form guru tidak lagi mengisi password umum secara otomatis. Impor guru baru dengan kolom password kosong menghasilkan sandi acak 16 karakter; password impor yang terlalu pendek ditolak.
- Impor siswa menolak tempat atau tanggal lahir kosong, sehingga tidak mengarang data kelahiran. Impor kehadiran menolak angka pecahan.
- Wali kelas hanya dapat dipilih dari guru aktif. Pembuatan tahun ajaran dan dua semesternya berada dalam satu transaksi.
- Salin pembagian mengajar hanya dapat menargetkan periode aktif. Untuk tahun ajaran berbeda, kelas dicocokkan dengan nama, tingkat, dan kurikulum di tahun tujuan.
- Nama dan teks yang masuk ke beberapa ekspor Excel ditulis sebagai teks eksplisit agar tidak dibaca sebagai rumus.
- Pembuatan laporan/leger dan pratinjau pengaturan cetak tidak lagi menulis pengaturan baru melalui permintaan GET.
- Sidebar desktop dan seluler sekarang memiliki animasi buka-tutup, status ARIA, penutupan lewat Escape, dan dukungan preferensi gerak minimal.

## Keputusan sebelum serah ke klien

1. **Penghapusan siswa/guru.** `docs/06-DECISIONS.md` ADR-011 menyatakan siswa dan guru hanya boleh dinonaktifkan, tetapi backend saat ini menyediakan hapus permanen. Penghapusan siswa juga menghapus nilai, kehadiran, dan enrollment. Pastikan pilihan produk klien, lalu selaraskan kode dan dokumen.
2. **Password awal guru.** Backend menyimpan password awal guru dalam kolom terenkripsi `password_plain` dan menampilkannya kepada operator. Ini memudahkan distribusi akun, tetapi operator tetap dapat membaca password tersebut selama guru belum mengganti sandinya. Pastikan kebijakan klien; akun lama yang pernah memakai password umum perlu diganti.
3. **Cakupan backup.** Menu hanya mencadangkan database. Logo, foto profil, gambar login, dan berkas unggahan di `storage/app/public` memerlukan salinan terpisah jika klien mengharapkan pemulihan aplikasi secara penuh.

## Verifikasi operasional yang masih diperlukan

- Jalankan `composer audit` pada mesin yang dapat mengakses Packagist. Audit dependensi dari lingkungan ini gagal karena DNS/jaringan tidak tersedia; ini bukan hasil pemeriksaan tanpa kerentanan.
- Uji restore pada staging dengan driver database dan ukuran data yang sama seperti produksi. Tes otomatis yang ada memakai SQLite terisolasi; restore MySQL/MariaDB belum diuji terhadap database nyata.
- Pastikan cron menjalankan `php artisan schedule:run` tiap menit, direktori `storage/app/backups` dapat ditulis/dibaca proses web dan cron, serta batas unggahan PHP cukup untuk berkas restore.
- Periksa gerak sidebar di browser target. Build CSS/JS dan pengujian markup lulus, tetapi tidak ada browser otomatis terpasang di lingkungan audit.
