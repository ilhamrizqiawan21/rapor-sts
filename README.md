# Rapor STS — Aplikasi Pengolahan Nilai Sumatif Tengah Semester

Aplikasi web pendamping untuk madrasah (MI/MTs/MA) yang memakai **Rapor Digital Madrasah (RDM) Kemenag**.
RDM hanya menampung nilai akhir semester (PAS/PAT/SAS), tidak punya fasilitas rapor tengah semester.
Aplikasi ini mengisi celah itu: guru menginput nilai sumatif lingkup materi + tes STS, wali kelas
melengkapi kehadiran & catatan, lalu rapor STS dicetak dengan alur dan tampilan yang mirip RDM.

Status: **perencanaan selesai, siap dieksekusi**. Eksekutor: Gemini (CLI / Code Assist).
Reviewer arsitektur & diff: Claude.

## Isi folder

```
rapor-sts/
├── README.md                ← file ini (titik masuk)
├── GEMINI.md                ← ATURAN AI. Gemini CLI memuat file ini otomatis.
├── AGENTS.md                ← penunjuk untuk agent lain (Codex, dsb.) → GEMINI.md
├── docs/
│   ├── 01-PRD.md            ← apa & kenapa: masalah, pengguna, ruang lingkup, metrik
│   ├── 02-REQUIREMENTS.md   ← kebutuhan fungsional/non-fungsional + kriteria terima (FR-xx)
│   ├── 03-ERD.md            ← model data, tabel, relasi, constraint (Mermaid)
│   ├── 04-DESIGN.md         ← design tokens, komponen, peta layar → mockup
│   ├── 05-ARCHITECTURE.md   ← stack, struktur folder, role & izin, route, service
│   ├── 06-DECISIONS.md      ← catatan keputusan (ADR). WAJIB dipatuhi.
│   ├── 07-CALCULATION.md    ← rumus nilai, predikat, deskripsi capaian, contoh uji
│   ├── 08-IMPORT-EXPORT.md  ← format template Excel, PDF rapor, export RDM
│   ├── 09-TASKS.md          ← rencana eksekusi bertahap + checklist
│   └── 10-OPEN-QUESTIONS.md ← hal yang belum diputuskan. Jangan ditebak.
├── mockup/
│   ├── index.html           ← buka di browser: daftar semua layar
│   └── *.html               ← 17 layar (HTML statis, tanpa dependensi)
└── reference/
    └── rdm/                 ← screenshot RDM asli sebagai acuan tampilan
```

## Urutan baca

1. Manusia: `docs/01-PRD.md` → `mockup/index.html` → `docs/06-DECISIONS.md`.
2. AI eksekutor: `GEMINI.md` dulu, lalu dokumen yang disebut di sana sesuai tahap di `docs/09-TASKS.md`.

## Cara memakai dengan Gemini CLI

```bash
cd ~/Documents/rapor-sts      # atau folder kode, lihat di bawah
gemini
> /memory show                # pastikan GEMINI.md termuat
> Kerjakan Tahap 0 dari docs/09-TASKS.md. Berhenti setelah tahap selesai dan tampilkan ringkasan diff.
```

Saran: buat proyek Laravel di subfolder `app/` (atau repo terpisah) dan salin `GEMINI.md` + `docs/`
ke root repo, supaya Gemini selalu memuat aturan yang sama. Satu tahap = satu branch = satu review.

## Mockup interaktif

Versi kanvas (bisa diberi komentar) ada di Claude:
https://claude.ai/artifact/KwpgVnGGXiHSbCU7jiCdiK — masih privat, bagikan lewat menu Share bila perlu.
Folder `mockup/` adalah salinan statisnya untuk dipakai offline dan oleh Gemini.
