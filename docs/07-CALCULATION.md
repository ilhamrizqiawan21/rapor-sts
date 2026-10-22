# 07 — Perhitungan Nilai, Predikat, dan Deskripsi Capaian

Semua perhitungan hanya ada di `app/Services/Grading/`. Satu fungsi = satu aturan di bawah, masing-masing
punya unit test dengan contoh di §6. Status: rumus §2 dan interval §4 masih **Proposed** (ADR-014/015) —
kode dibuat dengan konstanta/konfigurasi yang mudah diganti.

## 1. Istilah

- **LM** = lingkup materi / TP (tujuan pembelajaran) yang ditambahkan guru di layar Sumatif.
- **KKTP LM** = `learning_scopes.kktp` (nilai batas ketercapaian LM itu).
- **KKTP mapel** = `teaching_assignments.kktp` (default dari `subject_kktp_defaults`, lalu
  `app_settings.default_kktp`). Dipakai untuk nilai STS dan predikat nilai rapor.
- **Bobot** = `weight_summative` (wS) dan `weight_sts` (wT), angka ≥ 0, tidak harus berjumlah 100.

## 2. Nilai rapor STS (ADR-015, Proposed)

```
nilai_lm  = daftar summative_scores.score siswa untuk semua LM mapel-kelas, abaikan NULL
rerata    = rata-rata(nilai_lm)                       -- NULL jika tidak ada satu pun nilai
sts       = sts_scores.score                          -- boleh NULL

jika rerata = NULL                      → nilai_rapor = NULL
jika wT = 0                             → nilai_rapor = rerata
jika wS = 0                             → nilai_rapor = sts            (NULL bila sts NULL)
jika sts = NULL                         → nilai_rapor = NULL
selain itu                              → nilai_rapor = (rerata × wS + sts × wT) / (wS + wT)
jika wS = 0 dan wT = 0                  → tolak di validasi bobot (minimal satu > 0)
```

Presisi: hitung dengan **BCMath** (`bcadd/bcmul/bcdiv`, scale 4) atau integer per seratus. Jangan pakai
float biasa (contoh: 79 dan 79,98 → rerata float 79,49000000000001).

## 3. Pembulatan & tampilan

- Nilai rapor yang tampil di layar, leger, dan PDF: **bulat ke integer, half-up** (79,5 → 80; 79,49 → 79).
- Rerata sumatif di layar Penilaian: bulat integer half-up (sama).
- Rerata kelas / rata-rata madrasah (dashboard kepala, baris bawah leger): 1 desimal, koma (81,4).
- `NULL` ditampilkan "–". Siswa dengan nilai rapor NULL diberi label "Nilai STS belum diisi" atau
  "Belum ada nilai sumatif" sesuai penyebabnya.
- Predikat & deskripsi dihitung dari nilai rapor yang **sudah dibulatkan**.

## 4. Predikat / ketercapaian (ADR-014, Proposed)

Untuk nilai `n` (integer hasil pembulatan) dan KKTP `k`:

```
pita = (100 − k) / 3
n < k                 → Belum Tercapai
k ≤ n < k + pita      → Cukup
k + pita ≤ n < k + 2·pita → Baik
n ≥ k + 2·pita        → Sangat Baik
```

Tampilan interval di modal Tambah Nilai memakai batas integer:
Cukup `k … ceil(k+pita)−1`, Baik `ceil(k+pita) … ceil(k+2·pita)−1`, Sangat Baik `ceil(k+2·pita) … 100`.
Contoh k=70 → Belum < 70, Cukup 70–79, Baik 80–89, Sangat Baik 90–100.
Contoh k=75 → Belum < 75, Cukup 75–83, Baik 84–91, Sangat Baik 92–100.

Kolom "Ketercapaian" per LM memakai KKTP LM; predikat nilai rapor memakai KKTP mapel.

## 5. Deskripsi capaian kompetensi (otomatis)

Meniru gaya kalimat rapor RDM ("Menunjukkan penguasaan yang baik dalam ...").

```
kata = { SangatBaik: "sangat baik", Baik: "baik", Cukup: "cukup baik", BelumTercapai: "kurang" }
lm_terbaik = LM dengan nilai tertinggi siswa itu (seri → sequence terkecil)
kalimat = "Menunjukkan penguasaan yang {kata[predikat nilai rapor]} dalam {ringkasan lm_terbaik}"

lm_lemah = LM dengan nilai terendah yang < KKTP LM-nya, dan ≠ lm_terbaik (seri → sequence terkecil)
jika lm_lemah ada: kalimat += ". Perlu bimbingan dalam {ringkasan lm_lemah}"
kalimat += "."
```

- Ringkasan dipakai apa adanya, spasi di ujung dibuang, titik di akhir ringkasan dibuang.
- Jika nilai rapor NULL → tidak ada deskripsi.
- Jika ada `competency_overrides` untuk siswa+mapel → pakai teks override (tampilkan ikon "diedit").
- Panjang maksimal dicetak 300 karakter; lebih dari itu ditolak di validasi override.

## 6. Contoh uji (wajib jadi unit test)

Bobot 50:50, KKTP mapel 70, kecuali disebut lain.

| # | Nilai LM | STS | Bobot S:T | Rerata (mentah) | Nilai rapor (mentah → tampil) | Predikat |
|---|---|---|---|---|---|---|
| E1 | 85, 82, 85 | 80 | 50:50 | 84 | 82 → **82** | Baik |
| E2 | 92, 90, 94 | 90 | 50:50 | 92 | 91 → **91** | Sangat Baik |
| E3 | 78, 70, 74 | 70 | 50:50 | 74 | 72 → **72** | Cukup |
| E4 | 80, 85 | 77 | 60:40 | 82,5 | 80,3 → **80** | Baik |
| E5 | 80, 85 | NULL | 50:50 | 82,5 | NULL → **–** | – |
| E6 | 83, 86 | NULL | 50:0 | 84,5 | 84,5 → **85** | Baik |
| E7 | 80, NULL, 90 | 85 | 50:50 | 85 | 85 → **85** | Baik |
| E8 | 79, 80 | – | 50:0 | 79,5 | 79,5 → **80** (half-up) | Baik |
| E9 | 79, 79,98 | – | 50:0 | 79,49 | 79,49 → **79** | Cukup |
| E10 | (tidak ada) | 90 | 50:50 | NULL | NULL → **–** | – |
| E11 | 70, 80 | 60 | 0:100 | 75 | 60 → **60** | Belum Tercapai |

Predikat dengan KKTP 75: 74 → Belum, 75 → Cukup, 83 → Cukup, 84 → Baik, 91 → Baik, 92 → Sangat Baik.

Deskripsi:
- D1: LM1 "menganalisis tata cara bersuci dari hadas dan najis" = 85, LM2 "mempraktikkan wudu" = 82,
  nilai rapor 82 (Baik) → "Menunjukkan penguasaan yang baik dalam menganalisis tata cara bersuci dari
  hadas dan najis."
- D2: LM1 "mengidentifikasi macam-macam najis" = 78, LM2 "menyucikan najis" = 66 (KKTP 70), nilai rapor
  72 (Cukup) → "Menunjukkan penguasaan yang cukup baik dalam mengidentifikasi macam-macam najis. Perlu
  bimbingan dalam menyucikan najis."

## 7. Syarat kirim & kunci

- **Kirim** (guru) diizinkan bila setiap siswa aktif di kelas itu punya nilai rapor ≠ NULL.
  Sel LM yang kosong tidak memblokir, tetapi ditampilkan sebagai peringatan sebelum konfirmasi kirim.
- **Kunci** (wali) diizinkan bila semua teaching_assignments kelas pada periode aktif sudah terkirim.
- Progres "Sumatif LM" di Status Nilai = persen mapel-kelas yang punya ≥ 1 LM dan semua siswa punya
  ≥ 1 nilai LM. Progres "Nilai STS" = persen mapel-kelas (dengan wT > 0) yang semua siswanya punya STS.
