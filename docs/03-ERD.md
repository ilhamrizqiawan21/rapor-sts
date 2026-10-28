# 03 — ERD & Skema Database

DB: MySQL 8 / MariaDB 10.6+, `utf8mb4_unicode_ci`. Semua tabel punya `id` BIGINT unsigned auto-increment
dan `created_at`/`updated_at` kecuali disebut lain. FK memakai `foreignId()->constrained()`.

## 1. Diagram

```mermaid
erDiagram
    academic_years ||--o{ periods : "punya 2 semester"
    academic_years ||--o{ classrooms : ""
    academic_years ||--o{ enrollments : ""
    periods ||--o{ teaching_assignments : ""
    periods ||--o{ homeroom_records : ""
    periods ||--o{ classroom_locks : ""
    periods ||--|| print_settings : ""
    users ||--o{ classrooms : "wali kelas"
    users ||--o{ teaching_assignments : "guru"
    subjects ||--o{ subjects : "induk (PAI)"
    subjects ||--o{ teaching_assignments : ""
    subjects ||--o{ subject_kktp_defaults : ""
    classrooms ||--o{ enrollments : ""
    classrooms ||--o{ teaching_assignments : ""
    students ||--o{ enrollments : ""
    teaching_assignments ||--o{ learning_scopes : "LM / TP"
    learning_scopes ||--o{ summative_scores : ""
    teaching_assignments ||--o{ sts_scores : ""
    teaching_assignments ||--o{ competency_overrides : ""
    students ||--o{ summative_scores : ""
    students ||--o{ sts_scores : ""
    students ||--o{ competency_overrides : ""
    students ||--o{ homeroom_records : ""
    users ||--o{ activity_logs : ""

    madrasah_profiles {
        bigint id PK
        string parent_institution "baris 1 kop, nullable"
        string foundation_name "nullable"
        string name
        string level "MI|MTs|MA"
        string nsm
        string npsn
        string address
        string district
        string regency
        string province
        string contact "nullable"
        string headmaster_name
        string headmaster_nip "nullable"
        string logo_left_path
        string logo_right_path "nullable"
    }
    app_settings {
        bigint id PK
        string app_name
        string app_subtitle
        string app_logo_path "nullable"
        string login_image_path "nullable"
        string topbar_color "hex #RRGGBB"
        decimal default_kktp "default 70"
    }
    academic_years {
        bigint id PK
        string name "2026/2027 unique"
    }
    periods {
        bigint id PK
        bigint academic_year_id FK
        tinyint semester "1=Ganjil 2=Genap"
        boolean is_active
    }
    users {
        bigint id PK
        string name "dengan gelar"
        string username UK
        string password
        string role "operator|guru|kepala"
        string nik "nullable"
        string nuptk "nullable"
        string nip "nullable"
        char gender "L|P"
        boolean is_active
    }
    subjects {
        bigint id PK
        bigint parent_id FK "nullable"
        string code
        string name
        string group "umum|mulok"
        string curriculum "merdeka|k13"
        smallint sort_order
        boolean is_active
    }
    subject_kktp_defaults {
        bigint id PK
        bigint academic_year_id FK
        bigint subject_id FK
        tinyint level
        decimal kktp
    }
    classrooms {
        bigint id PK
        bigint academic_year_id FK
        tinyint level "7..9 untuk MTs"
        string name "VII.A"
        string alias "nullable"
        string curriculum "merdeka|k13"
        char phase "D, nullable"
        bigint homeroom_teacher_id FK "users, nullable"
    }
    students {
        bigint id PK
        string nis "nullable"
        string nisn UK
        string name
        char gender
        string birth_place
        date birth_date
        string address "nullable"
        boolean is_active
    }
    enrollments {
        bigint id PK
        bigint academic_year_id FK
        bigint classroom_id FK
        bigint student_id FK
    }
    teaching_assignments {
        bigint id PK
        bigint period_id FK
        bigint classroom_id FK
        bigint subject_id FK
        bigint teacher_id FK "users"
        tinyint hours_per_week
        decimal weight_summative "default 50"
        decimal weight_sts "default 50"
        decimal kktp "KKTP mapel: untuk nilai STS & predikat nilai rapor"
        timestamp submitted_at "nullable = belum kirim"
        bigint submitted_by FK "nullable"
    }
    learning_scopes {
        bigint id PK
        bigint teaching_assignment_id FK
        smallint sequence "TP/LM ke-"
        text summary "ringkasan TP/LM"
        decimal kktp
    }
    summative_scores {
        bigint id PK
        bigint learning_scope_id FK
        bigint student_id FK
        decimal score "5,2 nullable"
    }
    sts_scores {
        bigint id PK
        bigint teaching_assignment_id FK
        bigint student_id FK
        decimal score "5,2 nullable"
    }
    competency_overrides {
        bigint id PK
        bigint teaching_assignment_id FK
        bigint student_id FK
        text description
        bigint updated_by FK
    }
    homeroom_records {
        bigint id PK
        bigint period_id FK
        bigint classroom_id FK
        bigint student_id FK
        smallint sick
        smallint permit
        smallint absent
        text note "nullable, max 500"
    }
    classroom_locks {
        bigint id PK
        bigint period_id FK
        bigint classroom_id FK
        bigint locked_by FK
        timestamp locked_at
    }
    print_settings {
        bigint id PK
        bigint period_id FK
        string report_title
        string print_place
        date print_date
        string score_label "Nilai STS"
        string paper_size "A4|F4"
        string watermark_text "nullable, legacy/unused"
        json sections "flag tampil per bagian"
    }
    activity_logs {
        bigint id PK
        bigint user_id FK "nullable"
        string action
        string subject_type "nullable"
        bigint subject_id "nullable"
        string description
        string ip
        timestamp created_at
    }
```

## 2. Constraint & index wajib

| Tabel | Constraint |
|---|---|
| madrasah_profiles, app_settings | Tepat satu baris (dibuat seeder; aplikasi hanya update) |
| academic_years | `unique(name)` |
| periods | `unique(academic_year_id, semester)`; tepat satu `is_active = true` (dijaga di service, dalam transaksi) |
| users | `unique(username)`; index `(role, is_active)` |
| subjects | `unique(curriculum, code)`; FK `parent_id` → subjects `nullOnDelete` |
| subject_kktp_defaults | `unique(academic_year_id, subject_id, level)` |
| classrooms | `unique(academic_year_id, name)`; FK homeroom `nullOnDelete` |
| students | `unique(nisn)` |
| enrollments | `unique(academic_year_id, student_id)` (satu siswa satu kelas per tahun); index `(classroom_id)` |
| teaching_assignments | `unique(period_id, classroom_id, subject_id)`; index `(teacher_id, period_id)` |
| learning_scopes | `unique(teaching_assignment_id, sequence)`; FK cascade on delete |
| summative_scores | `unique(learning_scope_id, student_id)`; FK learning_scope cascade |
| sts_scores | `unique(teaching_assignment_id, student_id)` |
| competency_overrides | `unique(teaching_assignment_id, student_id)` |
| homeroom_records | `unique(period_id, student_id)` |
| classroom_locks | `unique(period_id, classroom_id)` — buka kunci = hapus baris + log |
| print_settings | `unique(period_id)` |
| activity_logs | index `(created_at)`, `(user_id)`; tanpa `updated_at` |

Catatan:
- `students` dan `users` tidak dihapus dari UI: pakai `is_active = false` (ADR-011).
- Nilai **tidak** di-cascade dari `students`/`users` (restrict) agar tidak hilang tak sengaja.
- `teaching_assignments.submitted_at` adalah satu-satunya penanda status kirim.
- Kolom desimal memakai `decimal(5,2)`.

## 3. Seed bawaan

1. `madrasah_profiles` & `app_settings`: satu baris berisi placeholder `[NAMA MADRASAH]` dll.
2. User operator: `admin` / password acak yang dicetak ke konsol saat `php artisan db:seed`.
3. Mapel MTs Kurikulum Merdeka (urutan cetak sesuai rapor contoh):
   1. Pendidikan Agama Islam (PAI) — induk, tanpa nilai
      - A. Al Qur'an Hadis (QH) · B. Akidah Akhlak (AA) · C. Fikih (FIK) · D. Sejarah Kebudayaan Islam (SKI)
   2. Bahasa Arab (BAR) 3. Pendidikan Pancasila (PP) 4. Bahasa Indonesia (BIN) 5. Matematika (MTK)
   6. Ilmu Pengetahuan Alam (IPA) 7. Ilmu Pengetahuan Sosial (IPS) 8. Bahasa Inggris (BIG)
   9. Pendidikan Jasmani, Olahraga dan Kesehatan (PJOK) 10. Informatika (INF)
   11. Seni Budaya / Prakarya (SBP) · Muatan Lokal (MULOK) — induk, opsional
4. `DemoSeeder` terpisah (tidak dijalankan di produksi): 1 tahun pelajaran, 3 kelas, 10 guru,
   30 siswa fiktif, nilai acak — untuk demo ke calon klien dan untuk test.

## 4. Query penting (untuk menghindari N+1)

- Halaman Penilaian guru: `TeachingAssignment::with(['learningScopes.scores', 'stsScores',
  'competencyOverrides', 'classroom.enrollments.student'])` lalu hitung di `GradeCalculator`.
- Leger kelas: ambil semua teaching_assignments kelas + relasi di atas dalam satu batch, hitung di
  service, jangan query per sel.
- Dashboard kepala: agregat pakai query `GROUP BY classroom_id` untuk hitungan terkirim; rerata
  dihitung di service dari data yang sudah di-batch per tingkat.
