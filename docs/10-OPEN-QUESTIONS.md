# 10 — Pertanyaan Terbuka

Jangan diimplementasikan dengan tebakan. Pakai default sementara yang tertulis, buat mudah diganti,
dan tandai di kode dengan komentar `// OPEN-Qn`.

| ID | Pertanyaan | Default sementara | Memblokir |
|---|---|---|---|
| Q1 | Bagaimana RDM mengisi kolom Interval (Cukup/Baik/Sangat Baik) dari Nilai Batas Ketercapaian? | Dibagi rata (ADR-014) | Tidak |
| Q2 | Apakah madrasah klien menghitung nilai rapor STS = rerata sumatif LM × bobot + tes STS × bobot? Atau hanya nilai tes STS? | Bobot 50:50, bisa diubah per mapel (ADR-015) | Tidak |
| Q3 | Kalimat deskripsi capaian di RDM: pola persisnya, kata predikat, dan apakah memakai LM tertinggi/terendah? | 07-CALCULATION §5 | Tidak |
| Q4 | Apakah ekstrakurikuler & prestasi perlu tampil di rapor STS klien? | Tidak (v1.1) | Tidak |
| Q5 | Format template Excel nilai RDM 3.1 (Kurikulum Merdeka & K13)? | Terpasang template asli di reference/rdm-template/ & config/rdm_export.php (ADR-017, Proposed) | Tidak (Selesai Tahap 7) |
| Q6 | Madrasah klien memakai K13 untuk kelas IX: apakah rapor STS kelas IX memakai format pengetahuan/keterampilan? | Dikonfirmasi klien: Leger memuat Bagian Pengetahuan & Keterampilan (Excel multi-sheet & PDF 2 bagian) | Tidak (Selesai) |
| Q7 | Perlu snapshot nilai saat kelas dikunci (arsip tidak berubah)? | Tidak (ADR-007) | Tidak |
| Q8 | Rentang tanggal kehadiran STS (awal semester s.d. tanggal STS) — perlu dicatat di rapor? | Tidak dicetak | Tidak |
| Q9 | Hosting: shared hosting klien atau VPS pemilik? | Mendukung keduanya | Tahap 8 |
