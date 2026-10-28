# 01 — Product Requirements Document (PRD)

Versi: 1.0 · Tanggal: 22 Oktober 2026 · Pemilik produk: Ilham Rizqiawan (EduTech)

## 1. Latar belakang

Sejak semester ganjil 2025/2026 seluruh madrasah wajib memakai **Rapor Digital Madrasah (RDM)**
Kemenag. RDM mengolah nilai harian/sumatif dan nilai akhir semester (PAS/PAT/SAS), lalu mencetak
rapor akhir semester. **RDM tidak menyediakan rapor tengah semester (STS/PTS).** Akibatnya madrasah
membuat rapor STS manual di Excel/Word: rekap lambat, format tiap guru berbeda, rawan salah hitung,
dan nilai sumatif yang sudah diketik untuk STS harus diketik ulang ke RDM di akhir semester.

## 2. Masalah yang diselesaikan

1. Tidak ada alat resmi untuk mengolah dan mencetak rapor STS.
2. Rekap nilai STS lintas guru dan kelas dikerjakan manual oleh wali kelas.
3. Kepala madrasah tidak punya pantauan real-time progres pengisian nilai STS.
4. Input ganda: nilai sumatif lingkup materi diketik untuk STS lalu diketik lagi ke RDM.

## 3. Tujuan produk

- Guru, wali kelas, dan operator bisa memakai aplikasi tanpa pelatihan panjang karena alur dan
  tampilannya meniru RDM yang sudah mereka kenal.
- Rapor STS tercetak rapi, seragam, dengan identitas madrasah klien.
- Kepala madrasah bisa memantau progres dan hasil tanpa bisa mengubah data.
- (Tahap lanjut) Nilai sumatif bisa diekspor ke format template RDM agar tidak diketik ulang.

## 4. Pengguna (role)

| Role | Siapa | Kebutuhan utama |
|---|---|---|
| Operator | Operator/proktor madrasah | Menyiapkan data: profil, tahun pelajaran, mapel, kelas, guru, siswa, pembagian mengajar, pengaturan cetak |
| Guru | Guru mata pelajaran | Atur bobot, input nilai sumatif per TP/lingkup materi dan nilai tes STS, kirim nilai |
| Wali kelas | Guru yang ditugasi sebagai wali kelas (bukan akun terpisah) | Isi kehadiran & catatan, pantau status, kunci nilai, cetak rapor & leger kelasnya |
| Kepala madrasah | Kepala madrasah | Pantau progres, rekap nilai, lihat rapor, unduh laporan. **Hanya baca.** |

## 5. Ruang lingkup v1 (MVP)

Masuk:
- Login dengan pilihan tahun pelajaran & semester (seperti RDM).
- Master data: profil madrasah + branding, tahun pelajaran/semester, mata pelajaran, kelas, guru,
  siswa (input manual & upload Excel), pembagian mengajar (+ salin dari semester sebelumnya).
- Penilaian guru: bobot Sumatif LM : STS, KKTP per lingkup materi, input nilai per LM (manual/upload),
  input nilai tes STS, deskripsi capaian kompetensi otomatis (bisa diedit), kirim nilai.
- Wali kelas: kehadiran (S/I/A), catatan wali kelas (per siswa & massal), status nilai, kunci/buka,
  cetak rapor STS (per siswa & semua), leger PDF/Excel.
- Kepala madrasah: dashboard pemantauan, rekap nilai (leger), lihat rapor, unduh rekap.
- Pengaturan cetak: judul rapor, tempat & tanggal cetak, label kolom nilai, ukuran kertas,
  bagian yang tampil.
- Backup database (unduh .sql) oleh operator.

Tidak masuk v1 (lihat ADR-003):
- Kokurikuler, P5RA, naik kelas, kelulusan, kirim nilai ke server pusat Kemenag.
- Ekstrakurikuler & prestasi di rapor STS (rencana v1.1; tampilan sudah ada di mockup sebagai opsi).
- Akses siswa/orang tua, aplikasi mobile.
- Sinkronisasi langsung dengan RDM/EMIS (tidak ada API publik).
- Export ke template RDM — menunggu file template asli (Tahap 7, lihat 10-OPEN-QUESTIONS).

## 6. Alur utama

1. Operator: isi profil & branding → tahun pelajaran aktif → mapel → kelas + wali kelas → guru →
   siswa → pembagian mengajar → pengaturan cetak.
2. Guru: pilih mapel & kelas di sidebar → Bobot → Sumatif (tambah LM, isi nilai) → STS (isi nilai)
   → cek tabel Penilaian (nilai rapor & capaian) → Kirim.
3. Wali kelas: isi kehadiran & catatan → cek Status Nilai sampai semua mapel terkirim → Kunci →
   Cetak Rapor STS & Leger.
4. Kepala madrasah: buka dashboard kapan saja untuk melihat progres dan hasil.

## 7. Metrik keberhasilan

- Wali kelas mencetak rapor STS satu kelas (≤ 40 siswa) dalam < 10 menit setelah semua nilai masuk.
- 0 selisih antara nilai rapor tercetak dan perhitungan manual pada contoh uji `07-CALCULATION.md`.
- ≥ 90% guru di madrasah percontohan mengisi nilai tanpa bantuan setelah 1 kali demo.
- Kepala madrasah dapat melihat persentase nilai terkirim tanpa bertanya ke operator.

## 8. Batasan & asumsi

- Satu instalasi = satu madrasah (ADR-002). Dijual per klien, identitas dikustomisasi.
- Hosting/deployment bukan bagian dari harga aplikasi kecuali disepakati terpisah.
- Jumlah data tipikal: ≤ 1.000 siswa, ≤ 40 kelas, ≤ 80 guru per madrasah.
- Data siswa adalah data pribadi anak: akses wajib login, tidak ada halaman publik.

## 9. Referensi

- Screenshot RDM v3.1: `reference/rdm/`
- Mockup: `mockup/index.html`
- Contoh rapor akhir semester RDM (format acuan rapor STS): dijelaskan di `04-DESIGN.md §6`.
