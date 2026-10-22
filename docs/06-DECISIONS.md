# 06 — Decision Log (ADR)

Format: ID · Status · Keputusan · Alasan · Konsekuensi.
Status: **Accepted** (wajib diikuti), **Proposed** (menunggu persetujuan pemilik), **Superseded**.
AI eksekutor tidak boleh mengubah ADR Accepted; usulan perubahan ditulis sebagai ADR baru "Proposed".

---

**ADR-001 · Accepted · Stack Laravel 13 + Blade + MySQL**
Alasan: stack yang dikuasai pemilik (PHP/Laravel/MySQL), mudah dideploy di shared hosting klien,
Laravel 13 versi terbaru dengan dukungan bug fix sampai Sep 2027. Tanpa SPA agar sederhana.
Konsekuensi: interaksi dinamis minimal (vanilla JS); tidak ada build step berat selain Vite untuk CSS/JS.

**ADR-002 · Accepted · Single-tenant: satu instalasi per madrasah**
Alasan: dijual per klien, data siswa terpisah per sekolah, tidak perlu kolom `school_id` di semua tabel.
Konsekuensi: update aplikasi dilakukan per instalasi; identitas & branding disimpan di DB (satu baris).

**ADR-003 · Accepted · Fokus STS; fitur RDM 3.x di luar ruang lingkup v1**
Tidak dibuat: kokurikuler, P5RA, naik kelas, kelulusan, kirim nilai ke pusat, ekstrakurikuler, prestasi.
Alasan: masalah klien adalah ketiadaan rapor tengah semester; fitur lain sudah ada di RDM.
Konsekuensi: ekstrakurikuler & prestasi direncanakan v1.1 (opsi tampilan sudah ada di mockup).

**ADR-004 · Accepted · Meniru alur & tata letak RDM, bukan identitasnya**
Tata letak, urutan menu, istilah, dan bentuk tabel/modal meniru RDM v3.1. Nama "RDM", logo Kemenag,
dan klaim resmi tidak dipakai di UI aplikasi. Login menyebut "Bukan aplikasi resmi Kementerian Agama".
Kontras warna diperbaiki (teks gelap di atas kuning).
Alasan: familiar bagi guru, tetapi tidak menimbulkan kesan aplikasi resmi; melindungi penjual.
Konsekuensi: logo di kop rapor sepenuhnya unggahan klien (boleh logo apa pun yang memang mereka pakai).

**ADR-005 · Accepted · Wali kelas bukan role terpisah**
Wali kelas = guru yang ditetapkan di `classrooms.homeroom_teacher_id`. Menu wali muncul otomatis.
Alasan: sama seperti RDM; satu akun per orang.

**ADR-006 · Accepted · Kepala madrasah = role `kepala`, hanya baca, ditegakkan di server**
Alasan: kepala hanya memantau. Menyembunyikan tombol tidak cukup.
Konsekuensi: middleware `ReadOnlyForHeadmaster` + test 403 untuk setiap route tulis.

**ADR-007 · Accepted · Nilai rapor dihitung, tidak disimpan**
Rerata sumatif, nilai rapor STS, predikat, dan deskripsi capaian dihitung oleh `GradeCalculator` dari
data mentah setiap kali ditampilkan/dicetak. Hanya override deskripsi yang disimpan.
Alasan: satu sumber kebenaran, tidak ada data basi saat nilai LM diubah.
Konsekuensi: perlu batch loading agar leger cepat (03-ERD §4). Snapshot saat kunci kelas = tidak
dibuat di v1 (Proposed untuk v1.1 jika klien butuh arsip yang tidak berubah).

**ADR-008 · Accepted · PDF dengan dompdf**
Alasan: murni PHP, jalan di shared hosting tanpa Chrome/wkhtmltopdf.
Konsekuensi: CSS rapor harus sederhana (tabel, tanpa flex/grid kompleks di template PDF); font Arial/
DejaVu Sans & Times. Bila paket `barryvdh/laravel-dompdf` belum mendukung Laravel 13, pakai
`dompdf/dompdf` langsung dengan wrapper kecil di `PdfRenderer`.

**ADR-009 · Accepted · Tanpa queue wajib**
Cetak PDF satu kelas dilakukan sinkron. Alasan: shared hosting sering tanpa worker.
Konsekuensi: batasi "Semua Rapor" per kelas (bukan per tingkat/sekolah); naikkan `max_execution_time`.

**ADR-010 · Accepted · Backup unduh saja, restore manual**
Alasan: restore dari UI berisiko menimpa data seluruh madrasah. Restore dilakukan teknisi.

**ADR-011 · Accepted · Penghapusan**
Siswa, guru, user: tidak dihapus, hanya nonaktif. LM (lingkup materi): hapus permanen beserta nilainya
setelah konfirmasi, dicatat di activity log. Kelas/mapel: hapus hanya jika belum dipakai.

**ADR-012 · Accepted · Nilai kosong ≠ 0**
`NULL` = belum dinilai. Nilai 0 adalah nilai sah. Rerata hanya menghitung nilai yang terisi; nilai rapor
tidak dihitung bila STS kosong (padahal bobot STS > 0) atau semua LM kosong. Lihat 07-CALCULATION.

**ADR-013 · Accepted · Excel langsung dengan PhpSpreadsheet**
Alasan: kontrol penuh atas template (banyak sheet, kolom terkunci, validasi data) dan mengurangi
dependensi wrapper yang versinya bisa tertinggal dari Laravel 13.

**ADR-014 · Proposed · Interval predikat dibagi rata dari KKTP**
Belum Tercapai < KKTP; sisa rentang (KKTP–100) dibagi tiga sama besar untuk Cukup, Baik, Sangat Baik.
Dengan KKTP 70: Cukup 70–79, Baik 80–89, Sangat Baik 90–100. Perlu dicocokkan dengan perilaku RDM
(isi otomatis kolom Interval di modal Tambah Nilai). Lihat 10-OPEN-QUESTIONS Q1.

**ADR-015 · Proposed · Rumus nilai rapor STS = bobot Sumatif LM : STS**
Lihat 07-CALCULATION §2. Menunggu konfirmasi praktik madrasah klien (Q2).

**ADR-016 · Accepted · Export ke template RDM ditunda sampai ada template asli**
Lihat 08-IMPORT-EXPORT §4. Tidak boleh menebak format kolom RDM.

**ADR-017 · Proposed · Export template RDM v3.1 memakai template asli di `reference/rdm-template/`**
Mengusulkan perubahan ADR-016: template asli RDM v3.1 untuk Sumatif (15 sheet) dan Tes/PAT (1 sheet) kini
tersedia di `reference/rdm-template/`. `RdmExporter` mengisi salinan template tanpa merusak struktur, formula,
proteksi lembar (sandi proteksi adalah bawaan template asli, tidak diubah), maupun token ID siswa kolom B.
Posisi kolom/sel dipusatkan di `config/rdm_export.php`.
Menunggu persetujuan pemilik, termasuk lisensi/izin menyertakan berkas template RDM di repositori.
