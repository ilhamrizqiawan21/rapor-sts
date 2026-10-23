# Rapor STS — Aplikasi Pengolahan Nilai Sumatif Tengah Semester

Aplikasi web pendamping untuk madrasah (MI/MTs/MA) yang memakai **Rapor Digital Madrasah (RDM) Kemenag**.
RDM hanya menampung nilai akhir semester (PAS/PAT/SAS), tidak punya fasilitas rapor tengah semester.
Aplikasi ini mengisi celah itu: guru menginput nilai sumatif lingkup materi + tes STS, wali kelas
melengkapi kehadiran & catatan, lalu rapor STS dicetak dengan alur dan tampilan yang mirip RDM.


## Isi folder

```
rapor-sts/
├── README.md                ← file ini (titik masuk)
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
2. Pengembang: dokumen sesuai tahap di `docs/09-TASKS.md`.

## Mockup

Folder `mockup/` berisi tampilan layar sebagai HTML statis; buka `mockup/index.html` di browser.
