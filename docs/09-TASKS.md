# 09 — Rencana Eksekusi

Satu tahap = satu sesi Gemini = satu branch = satu review. Jangan memulai tahap berikutnya sebelum
tahap sebelumnya direview dan di-merge. Centang `[x]` saat selesai. "Selesai" berarti test lulus.

## Tahap 0 — Fondasi
- [x] `laravel new` (Laravel 13), PHP 8.3+, MySQL. Hapus scaffolding yang tidak dipakai.
- [x] Konfigurasi locale `id`, timezone `Asia/Jakarta`, `Model::preventLazyLoading()` di non-produksi.
- [x] Pasang dependency ADR saja: phpspreadsheet, dompdf (cek kompatibilitas — ADR-008).
- [x] Port CSS dari `mockup/` ke `resources/css/app.css` (tokens 04-DESIGN §1), Vite build.
- [x] Blade components layout & sidebar per role (04-DESIGN §3) dengan data dummy.
- [x] Enum: Role, Semester, Curriculum, Achievement.
- Selesai bila: halaman kosong ber-layout persis mockup 02 tampil; `php artisan test` & Pint lulus.

## Tahap 1 — Skema, seed, auth
- [x] Migrasi semua tabel 03-ERD + constraint §2. Model + relasi + factory.
- [x] Seeder: profil & app_settings placeholder, operator `admin`, mapel MTs bawaan; `DemoSeeder`.
- [x] Login custom (FR-AUTH-01..05), rate limit, middleware `SetActivePeriod`, `EnsureRole`,
      `ReadOnlyForHeadmaster`.
- [x] Test: login sukses/gagal/nonaktif, redirect per role, kepala → 403 untuk POST apa pun.
- Selesai bila: mockup 01 berfungsi dengan data seed; badge semester tampil.

## Tahap 2 — Master data operator
- [x] Profil & branding + upload logo + pratinjau kop (FR-PRF-*).
- [x] Tahun pelajaran & periode aktif (FR-LMB-01).
- [x] Mata pelajaran (FR-LMB-02/03), kelas + "Tambah Sekaligus" (FR-LMB-04).
- [x] Guru + upload template (FR-GRU-01), siswa + upload per kelas (FR-SIS-*), pindah kelas.
- [x] Data mengajar + salin mengajar (FR-GRU-02..04). KKTP default (FR-RAP-02).
- [x] Dashboard operator + activity log (FR-ADM-01).
- [x] Test: unique constraints, import valid/invalid, salin mengajar tidak menimpa.

## Tahap 3 — Grading engine (tanpa UI)
- [x] `GradeCalculator`, `AchievementResolver`, `CompetencyDescriber`, `ClassGradeSheet`
      sesuai 07-CALCULATION.
- [x] Unit test E1–E11, predikat KKTP 70 & 75, deskripsi D1–D2, presisi BCMath.
- Selesai bila: semua contoh 07 §6 lulus.

## Tahap 4 — Penilaian guru
- [x] Sidebar guru + status (FR-NIL-01). Bobot (FR-NIL-02).
- [x] Sumatif: daftar LM (mockup 10), modal tambah/edit (mockup 11), hapus (FR-NIL-03..05),
      JS ketercapaian & "isi semua".
- [x] STS (FR-NIL-06). Ringkasan penilaian + override capaian (FR-NIL-08/09).
- [x] Template & upload LM / STS (FR-NIL-12, 08 §2).
- [x] Kirim (FR-NIL-10) dengan peringatan sel kosong.
- [x] Test policy: guru lain 403, setelah kirim 403, kelas dikunci 403.

## Tahap 5 — Wali kelas & cetak
- [x] Kehadiran & catatan + massal + upload (FR-WAL-02/03).
- [x] Status nilai, kunci/buka, batal kirim (FR-WAL-04/05, FR-NIL-11).
- [x] Pengaturan cetak (FR-RAP-01).
- [x] PDF rapor per siswa & semua (04-DESIGN §6), watermark DRAFT.
- [x] Leger PDF & Excel: format resmi madrasah (PAI group QH/AA/FIK/SKI, Mulok BSUN, Pengetahuan & Keterampilan, identitas JK, NIS, Nisn, kop resmi & ttd titimangsa).
- [x] Test: data rapor (ReportCardBuilder) sesuai contoh; kunci hanya jika semua terkirim.
- Selesai bila: PDF contoh dari DemoSeeder dibandingkan berdampingan dengan mockup 14–15 oleh pemilik.

## Tahap 6 — Kepala madrasah
- [x] Dashboard (FR-KPL-01..04), leger (FR-KPL-05), lihat rapor & unduh rekap (FR-KPL-06).
- [x] Kinerja input guru (daftar guru: mapel-kelas, % terkirim, terakhir aktif).
- [x] Test: semua route tulis 403 untuk kepala; dashboard tanpa N+1 (assert jumlah query).

## Tahap 7 — Export template RDM (Selesai — Template Asli RDM 3.1 Terpasang)
- [x] Analisis template RDM asli v3.1 (Sumatif 15 sheet & PAT 1 sheet) di `reference/rdm-template/`.
- [x] Konfigurasi pemetaan `config/rdm_export.php` per versi RDM.
- [x] `RdmExporter` mengisi nilai LM & STS, mempertahankan token ID siswa kolom B, proteksi lembar, dan password hash `8619`.
- [x] UI & Modal: download langsung dan pengisian template upload di view penilaian guru (Sumatif & STS).
- [x] Feature test `RdmExportTest`: verifikasi pengisian sel, token kolom B utuh, proteksi lembar, dan otorisasi guru.

## Tahap 8 — Rilis
- [ ] Backup (FR-ADM-02), kelola akun (FR-ADM-03), ganti password.
- [ ] Halaman error 403/404/419/500 berbahasa Indonesia dengan layout aplikasi.
- [ ] `docs/DEPLOY.md`: shared hosting & VPS, `.env` contoh, storage:link, batas PHP.
- [ ] Uji terima bersama pemilik pada data demo; perbaiki temuan.
