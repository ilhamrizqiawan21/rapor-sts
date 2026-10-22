# 05 — Architecture

## 1. Stack (lihat ADR-001)

| Lapisan | Pilihan |
|---|---|
| Bahasa / framework | PHP 8.3+, **Laravel 13** (rilis 17 Mar 2026, bug fix s.d. Sep 2027) |
| Database | MySQL 8 / MariaDB 10.6+ |
| UI | Blade server-rendered + CSS sendiri (port dari `mockup/`) + Bootstrap 5.3 **hanya** grid/utility & JS modal bila perlu. Tanpa SPA. |
| JS | Vanilla JS kecil (`resources/js/app.js`) via Vite: hitung ketercapaian saat mengetik, "isi semua", konfirmasi hapus |
| Excel | `phpoffice/phpspreadsheet` langsung (tanpa wrapper) |
| PDF | `barryvdh/laravel-dompdf` (cek kompatibilitas Laravel 13 saat install; bila belum, pakai `dompdf/dompdf` langsung — ADR-008) |
| Auth | Session auth bawaan Laravel, login custom (username) — tanpa Breeze/Jetstream |
| Test | Pest/PHPUnit bawaan, Laravel Pint |

## 2. Struktur folder

```
app/
├── Enums/                 Role, Semester, Curriculum, Achievement (BelumTercapai, Cukup, Baik, SangatBaik)
├── Http/
│   ├── Controllers/
│   │   ├── Auth/LoginController.php
│   │   ├── Operator/      ProfileController, AcademicYearController, PeriodController, SubjectController,
│   │   │                  ClassroomController, TeacherController, TeachingAssignmentController,
│   │   │                  StudentController, EnrollmentController, KktpDefaultController,
│   │   │                  PrintSettingController, UserController, BackupController, DashboardController
│   │   ├── Teacher/       DashboardController, WeightController, AssessmentController (ringkasan),
│   │   │                  LearningScopeController, StsScoreController, CompetencyOverrideController,
│   │   │                  SubmissionController, ImportController
│   │   ├── Homeroom/      HomeroomRecordController, StatusController, LockController
│   │   ├── Report/        ReportCardController (PDF per siswa/kelas), LedgerController (PDF/Excel)
│   │   └── Headmaster/    DashboardController, LedgerController, TeacherProgressController
│   ├── Middleware/        SetActivePeriod, EnsureRole, ReadOnlyForHeadmaster
│   └── Requests/          satu FormRequest per aksi simpan
├── Models/                sesuai 03-ERD
├── Policies/              TeachingAssignmentPolicy, ClassroomPolicy, StudentPolicy, ...
├── Services/
│   ├── Grading/           GradeCalculator, AchievementResolver, CompetencyDescriber, ClassGradeSheet (DTO)
│   ├── Import/            StudentImporter, TeacherImporter, SummativeScoreImporter, StsScoreImporter,
│   │                      HomeroomImporter, TemplateBuilder
│   ├── Export/            LedgerExcelExporter, RdmExporter (Tahap 7, interface dulu)
│   ├── Report/            ReportCardBuilder (data rapor per siswa), PdfRenderer
│   ├── PeriodService.php  aktifkan periode, salin mengajar
│   └── ActivityLogger.php
resources/views/
├── components/            lihat 04-DESIGN §3
├── layouts/
├── operator/ teacher/ homeroom/ headmaster/ auth/
└── pdf/                   report-card.blade.php, ledger.blade.php (CSS inline, tanpa web font)
```

## 3. Role & izin

Role disimpan di `users.role`. "Wali kelas" adalah turunan: guru yang `classrooms.homeroom_teacher_id`
= dirinya pada tahun pelajaran periode aktif.

| Aksi | Operator | Guru | Wali kelas (kelasnya) | Kepala |
|---|---|---|---|---|
| Kelola master data, profil, pengaturan | ✓ | – | – | – |
| Lihat/ubah bobot & nilai mapel-kelas | lihat | ✓ hanya yang diampu, sebelum kirim & sebelum kunci | lihat | lihat |
| Kirim nilai | – | ✓ | – | – |
| Batal kirim | ✓ | – | ✓ | – |
| Kehadiran & catatan | ✓ | – | ✓ sebelum kunci | lihat |
| Kunci / buka kunci kelas | ✓ | – | ✓ | – |
| Cetak rapor & leger | ✓ semua kelas | – | ✓ | ✓ semua (lihat/unduh) |
| Dashboard pemantauan & rekap | ✓ | – | – | ✓ |
| Backup | ✓ | – | – | – |

Penegakan:
1. Middleware `EnsureRole` per grup route.
2. Policy per model (mis. `TeachingAssignmentPolicy::updateScores` = guru pemilik && belum kirim &&
   kelas belum dikunci && periode aktif).
3. Middleware `ReadOnlyForHeadmaster`: tolak semua method selain GET/HEAD untuk role kepala,
   kecuali `logout` dan `password.update`.
4. Test otorisasi wajib (GEMINI.md §4).

## 4. Route (ringkas)

```
GET  /login                              POST /login            POST /logout
GET  /                                   → redirect dashboard per role

prefix operator, middleware role:operator
  resource profil (edit/update), lembaga/periode, mapel, kelas (+ POST kelas/sekaligus),
  guru (+ POST guru/import, GET guru/template), mengajar (GET ?kelas=, PUT baris, POST salin),
  siswa (+ import/template), enrollments/pindah, kktp, cetak/pengaturan, users, backup (GET unduh)

prefix guru, middleware role:guru
  GET  mengajar/{ta}                      ringkasan penilaian (layar 09)
  GET|PUT mengajar/{ta}/bobot
  GET  mengajar/{ta}/sumatif              (layar 10)
  POST mengajar/{ta}/sumatif              tambah LM + nilai (modal layar 11)
  PUT|DELETE mengajar/{ta}/sumatif/{lm}
  GET  mengajar/{ta}/sumatif/template     POST .../sumatif/import
  GET|PUT mengajar/{ta}/sts               (layar 12)   + template/import
  PUT  mengajar/{ta}/capaian/{student}    DELETE (kembalikan otomatis)
  POST mengajar/{ta}/kirim

prefix wali, middleware role:guru + homeroom
  GET|PUT kehadiran-catatan              POST catatan-massal
  GET status                              POST kunci   DELETE kunci   DELETE kirim/{ta} (batal kirim)

prefix rapor, middleware auth (policy per kelas)
  GET kelas/{classroom}                  daftar siswa (layar 07)
  GET kelas/{classroom}/siswa/{student}.pdf   GET kelas/{classroom}/semua.pdf
  GET kelas/{classroom}/leger.pdf        GET kelas/{classroom}/leger.xlsx

prefix kepala, middleware role:kepala (read-only)
  GET dashboard   GET leger?kelas=   GET kinerja-guru   GET unduh/rekap.xlsx|pdf
```

## 5. Periode aktif

- `SetActivePeriod` membaca `session('period_id')` (dipilih saat login) dan membagikannya ke view &
  container (`app(ActivePeriod::class)`).
- Semua query data bernilai difilter periode ini. Data periode lain bisa dibuka dengan login ulang
  memilih periode tersebut (read/write sesuai izin; nilai periode lampau tetap bisa diubah operator).

## 6. Alur status nilai

```
[draft] --guru Kirim (semua siswa lengkap)--> [terkirim]
[terkirim] --wali/operator Batal kirim--> [draft]
kelas: [terbuka] --wali Kunci (semua mapel terkirim)--> [dikunci]
[dikunci] --wali/operator Buka kunci--> [terbuka]
```
Setiap transisi dicatat di `activity_logs`.

## 7. Deploy

- Satu instalasi per madrasah (ADR-002). `.env` per klien.
- Shared hosting: `public/` sebagai document root, `php artisan storage:link`, cron tidak wajib.
- `memory_limit` ≥ 256M dan `max_execution_time` ≥ 120 untuk cetak PDF satu kelas.
- Backup: tombol operator menghasilkan dump via `mysqldump` bila tersedia; jika tidak tersedia
  (shared hosting), fallback ekspor per tabel dengan query (ADR-010).
