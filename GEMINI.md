# GEMINI.md — Aturan Kerja AI untuk Proyek Rapor STS

Kamu adalah **eksekutor implementasi**. Arsitektur dan keputusan sudah ditetapkan di `docs/`.
Tugasmu menulis kode yang sesuai dokumen, bukan merancang ulang. Diff-mu akan direview.

Konteks yang harus selalu dimuat:
@./docs/06-DECISIONS.md
@./docs/05-ARCHITECTURE.md

Baca sesuai kebutuhan tahap: `docs/02-REQUIREMENTS.md`, `docs/03-ERD.md`, `docs/04-DESIGN.md`,
`docs/07-CALCULATION.md`, `docs/08-IMPORT-EXPORT.md`, `docs/09-TASKS.md`, `docs/10-OPEN-QUESTIONS.md`.

## 1. Cara bekerja

1. Kerjakan **satu tahap** dari `docs/09-TASKS.md` per sesi. Jangan melompat ke tahap berikutnya.
2. Sebelum menulis kode, tulis rencana singkat: file yang dibuat/diubah, migrasi, test. Lalu kerjakan.
3. Setelah selesai: jalankan `php artisan test` dan `./vendor/bin/pint --test`. Tampilkan hasilnya.
4. Akhiri dengan ringkasan: apa yang dikerjakan, file yang berubah, test yang ditambahkan,
   hal yang belum selesai, dan pertanyaan terbuka. Centang item di `docs/09-TASKS.md`.
5. Kalau menemukan hal yang tidak diatur dokumen atau bertentangan: **berhenti dan tanya**.
   Jangan menebak. Catat di `docs/10-OPEN-QUESTIONS.md` bila sesi tidak interaktif.

## 2. Larangan keras

- Jangan menambah dependency (composer/npm) selain yang ada di ADR. Usulkan dulu sebagai ADR "Proposed".
- Jangan mengubah keputusan di `docs/06-DECISIONS.md`. Kalau perlu berubah, tulis ADR baru berstatus
  "Proposed" dan minta persetujuan.
- Jangan mengarang format template Excel RDM. Export RDM baru dikerjakan setelah file template asli
  ada di `reference/rdm-template/` (lihat `docs/08-IMPORT-EXPORT.md`).
- Jangan meng-hardcode identitas madrasah (nama, logo, alamat, kepala madrasah, warna). Semua dari
  tabel `madrasah_profiles` / `app_settings`.
- Jangan memakai nama "RDM", logo Kemenag, atau klaim resmi Kemenag di UI aplikasi. Logo kop rapor
  hanya berasal dari unggahan klien.
- Jangan membuat SPA, Livewire, Inertia, Vue, atau React. UI = Blade server-rendered.
- Jangan menyimpan nilai rapor hasil hitung sebagai sumber kebenaran; hitung dari data mentah
  (lihat ADR-007), kecuali snapshot saat kunci wali kelas bila ADR mengizinkan.
- Jangan menghapus data permanen dari UI. Pakai `is_active` / soft delete sesuai ERD.
- Jangan commit file `.env`, kredensial, atau data siswa asli.

## 3. Standar kode

- Laravel 13, PHP 8.3+, MySQL 8 / MariaDB 10.6+. Ikuti konvensi Laravel; kode & identifier bahasa
  Inggris, teks UI bahasa Indonesia (via `lang/id/*.php` bila berulang).
- Controller tipis. Validasi di **FormRequest**. Otorisasi di **Policy** / Gate, dicek di controller
  (`$this->authorize`) — menyembunyikan tombol di Blade TIDAK cukup.
- Logika nilai hanya di `app/Services/Grading/*` (lihat `docs/07-CALCULATION.md`). Blade dan
  controller tidak boleh menghitung nilai sendiri.
- Simpan massal (nilai satu kelas, upload Excel) di dalam `DB::transaction()` dan pakai upsert.
- Cegah N+1: eager load (`with()`), cek dengan `Model::preventLazyLoading()` di local.
- Semua query nilai difilter `period_id` aktif dari session.
- Angka nilai: `decimal(5,2)` di DB, tampil dibulatkan sesuai `docs/07-CALCULATION.md`.
- Format kode dengan Laravel Pint (preset laravel).

## 4. Test

- Pest atau PHPUnit (pilih yang terpasang bawaan Laravel 13; jangan menambah keduanya).
- Wajib ada feature test untuk: otorisasi per role (terutama kepala = hanya baca, guru hanya kelas
  yang diampu), perhitungan nilai (semua contoh di `07-CALCULATION.md`), kirim/kunci nilai,
  import Excel (file valid & invalid).
- Test tidak boleh bergantung pada data produksi. Pakai factory + seeder demo.

## 5. UI

- Ikuti `docs/04-DESIGN.md` dan file di `mockup/`. Nama kelas CSS dari mockup (`.side`, `.card`,
  `.btn b-pri`, `.t`, dst.) dipertahankan agar mudah dibandingkan.
- Satu file CSS aplikasi: `resources/css/app.css` (tanpa framework CSS tambahan selain yang di ADR).
- Teks tombol & label persis seperti mockup kecuali dokumen menyatakan lain.
- Semua form memakai `<label>` yang terhubung, tombol `<button>` asli, kontras teks ≥ 4.5:1.

## 6. Git

- Satu tahap = satu branch: `tahap-<n>-<ringkas>`, mis. `tahap-2-master-data`.
- Pesan commit deskriptif, bahasa Indonesia atau Inggris, format:
  `<area>: <apa yang berubah>` — contoh: `grading: hitung nilai rapor STS dari bobot per mapel`.
  Dilarang pesan samar seperti "fix", "update", "wip".
- Jangan push/merge ke `main` sendiri. Berhenti di branch dan minta review.
