# 09 — Rencana Eksekusi

Satu tahap = satu sesi Gemini = satu branch = satu review. Jangan memulai tahap berikutnya sebelum
tahap sebelumnya direview dan di-merge. Centang `[x]` saat selesai. "Selesai" berarti test lulus.

## Tahap 0 — Fondasi
- [ ] `laravel new` (Laravel 13), PHP 8.3+, MySQL. Hapus scaffolding yang tidak dipakai.
- [ ] Konfigurasi locale `id`, timezone `Asia/Jakarta`, `Model::preventLazyLoading()` di non-produksi.
- [ ] Pasang dependency ADR saja: phpspreadsheet, dompdf (cek kompatibilitas — ADR-008).
- [ ] Port CSS dari `mockup/` ke `resources/css/app.css` (tokens 04-DESIGN §1), Vite build.
- [ ] Blade components layout & sidebar per role (04-DESIGN §3) dengan data dummy.
- [ ] Enum: Role, Semester, Curriculum, Achievement.
- Selesai bila: halaman kosong ber-layout persis mockup 02 tampil; `php artisan test` & Pint lulus.

## Tahap 1 — Skema, seed, auth
- [ ] Migrasi semua tabel 03-ERD + constraint §2. Model + relasi + factory.
- [ ] Seeder: profil & app_settings placeholder, operator `admin`, mapel MTs bawaan; `DemoSeeder`.
- [ ] Login custom (FR-AUTH-01..05), rate limit, middleware `SetActivePeriod`, `EnsureRole`,
      `ReadOnlyForHeadmaster`.
- [ ] Test: login sukses/gagal/nonaktif, redirect per role, kepala → 403 untuk POST apa pun.
- Selesai bila: mockup 01 berfungsi dengan data seed; badge semester tampil.

## Tahap 2 — Master data operator
- [ ] Profil & branding + upload logo + pratinjau kop (FR-PRF-*).
- [ ] Tahun pelajaran & periode aktif (FR-LMB-01).
- [ ] Mata pelajaran (FR-LMB-02/03), kelas + "Tambah Sekaligus" (FR-LMB-04).
- [ ] Guru + upload template (FR-GRU-01), siswa + upload per kelas (FR-SIS-*), pindah kelas.
- [ ] Data mengajar + salin mengajar (FR-GRU-02..04). KKTP default (FR-RAP-02).
- [ ] Dashboard operator + activity log (FR-ADM-01).
- [ ] Test: unique constraints, import valid/invalid, salin mengajar tidak menimpa.

## Tahap 3 — Grading engine (tanpa UI)
- [ ] `GradeCalculator`, `AchievementResolver`, `CompetencyDescriber`, `ClassGradeSheet`
      sesuai 07-CALCULATION.
- [ ] Unit test E1–E11, predikat KKTP 70 & 75, deskripsi D1–D2, presisi BCMath.
- Selesai bila: semua contoh 07 §6 lulus.

## Tahap 4 — Penilaian guru
- [ ] Sidebar guru + status (FR-NIL-01). Bobot (FR-NIL-02).
- [ ] Sumatif: daftar LM (mockup 10), modal tambah/edit (mockup 11), hapus (FR-NIL-03..05),
      JS ketercapaian & "isi semua".
- [ ] STS (FR-NIL-06). Ringkasan penilaian + override capaian (FR-NIL-08/09).
- [ ] Template & upload LM / STS (FR-NIL-12, 08 §2).
- [ ] Kirim (FR-NIL-10) dengan peringatan sel kosong.
- [ ] Test policy: guru lain 403, setelah kirim 403, kelas dikunci 403.

## Tahap 5 — Wali kelas & cetak
- [ ] Kehadiran & catatan + massal + upload (FR-WAL-02/03).
- [ ] Status nilai, kunci/buka, batal kirim (FR-WAL-04/05, FR-NIL-11).
- [ ] Pengaturan cetak (FR-RAP-01).
- [ ] PDF rapor per siswa & semua (04-DESIGN §6), watermark DRAFT.
- [ ] Leger PDF & Excel.
- [ ] Test: data rapor (ReportCardBuilder) sesuai contoh; kunci hanya jika semua terkirim.
- Selesai bila: PDF contoh dari DemoSeeder dibandingkan berdampingan dengan mockup 14–15 oleh pemilik.

## Tahap 6 — Kepala madrasah
- [ ] Dashboard (FR-KPL-01..04), leger (FR-KPL-05), lihat rapor & unduh rekap (FR-KPL-06).
- [ ] Kinerja input guru (daftar guru: mapel-kelas, % terkirim, terakhir aktif).
- [ ] Test: semua route tulis 403 untuk kepala; dashboard tanpa N+1 (assert jumlah query).

## Tahap 7 — Export template RDM (DIBLOKIR sampai template asli tersedia)
- [ ] Lihat 08-IMPORT-EXPORT §4.

## Tahap 8 — Rilis
- [ ] Backup (FR-ADM-02), kelola akun (FR-ADM-03), ganti password.
- [ ] Halaman error 403/404/419/500 berbahasa Indonesia dengan layout aplikasi.
- [ ] `docs/DEPLOY.md`: shared hosting & VPS, `.env` contoh, storage:link, batas PHP.
- [ ] Uji terima bersama pemilik pada data demo; perbaiki temuan.
