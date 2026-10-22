# 08 — Import, Export, dan Cetak

Semua template Excel dibuat oleh aplikasi (`TemplateBuilder`, PhpSpreadsheet), bukan file statis,
supaya daftar siswa/kelas selalu terkini. Format `.xlsx`. Baris 1 = judul, baris 2 = keterangan,
baris 3 = header, data mulai baris 4. Kolom identitas (ID, NISN, Nama) dikunci (sheet protection
tanpa password atau dengan password internal) agar guru hanya mengisi kolom nilai.

## 1. Template master data (operator)

**Guru** — sheet `guru`
| Kolom | Wajib | Catatan |
|---|---|---|
| NIK/NUPTK | ✓ | dipakai sebagai username default |
| NIP | | |
| Nama (dengan gelar) | ✓ | |
| L/P | ✓ | L atau P |
| Password awal | | kosong = acak, ditampilkan sekali setelah import |

**Siswa** — sheet `siswa`, diunggah setelah memilih Tingkat & Kelas tujuan
| Kolom | Wajib | Catatan |
|---|---|---|
| NIS | | |
| NISN | ✓ | 10 digit, unique; jika sudah ada → update data & pindahkan enrollment ke kelas ini (konfirmasi) |
| Nama | ✓ | kapital disarankan |
| L/P | ✓ | |
| Tempat Lahir | ✓ | |
| Tanggal Lahir | ✓ | format tanggal Excel atau `DD-MM-YYYY` |
| Alamat | | |

## 2. Template nilai (guru)

**Sumatif LM** — satu file per mapel-kelas, satu sheet per LM (meniru RDM: "PH 2 dan seterusnya
diisi dengan pindah sheet").
- Nama sheet: `LM1`, `LM2`, ... Sel B1 = ringkasan LM, B2 = KKTP LM (boleh diubah di file).
- Header baris 3: `ID` (tersembunyi, student_id) | `NISN` | `Nama` | `L/P` | `Nilai`.
- Unduh template berisi LM yang sudah ada + satu sheet kosong `LM{n+1}` untuk LM baru.
- Upload: sheet yang namanya sudah ada → update nilai; sheet baru dengan ringkasan terisi → buat LM baru.
- Validasi: nilai 0–100, kosong = NULL. Baris dengan ID tidak dikenal ditolak dan dilaporkan.

**STS** — satu sheet `STS`: `ID` | `NISN` | `Nama` | `L/P` | `Nilai STS`.

**Kehadiran & catatan (wali)** — satu sheet: `ID` | `NISN` | `Nama` | `Sakit` | `Izin` | `Alpa` | `Catatan`.

Aturan umum import:
1. Proses dalam satu transaksi per file. Jika > 20% baris gagal, batalkan seluruh file.
2. Tampilkan ringkasan: berhasil N, gagal M (nomor baris + alasan).
3. Tolak bila mapel-kelas sudah terkirim atau kelas dikunci.
4. Ukuran file maks 2 MB; hanya `.xlsx`.

## 3. Export & cetak

- **Rapor STS PDF** per siswa dan "Semua Rapor" per kelas (satu PDF, page break per siswa). Nama file:
  `Rapor_STS_{KELAS}_{NAMA}_{NISN}.pdf` dan `Rapor_STS_{KELAS}_semua.pdf` (spasi → `_`).
  Spesifikasi tata letak: 04-DESIGN §6.
- **Leger PDF** (landscape) dan **Leger Excel** per kelas: No, NISN, Nama, kolom per mapel (kode),
  Jumlah, Rerata, Rank. Baris bawah rerata kelas per mapel. Sel < KKTP diberi warna merah muda di Excel
  dan garis bawah tebal di PDF.
- **Rekap kepala** (Excel/PDF): satu sheet per tingkat berisi progres per kelas dan rerata per mapel.
- **Sampul & identitas siswa**: tidak di v1 (tombol di mockup "Sampul" & "Identitas" = v1.1).

## 4. Export ke template RDM (Tahap 7 — DITUNDA, ADR-016)

Tujuan: nilai sumatif LM yang sudah diinput untuk STS tidak perlu diketik ulang ke RDM saat akhir
semester. Yang diketahui dari tutorial publik: template nilai RDM diunduh per mapel-kelas dari menu
Penilaian, berisi daftar siswa, dan penilaian berikutnya diisi di sheet terpisah.

Yang **belum** diketahui dan tidak boleh ditebak: nama sheet, posisi baris/kolom, kolom kunci
(NISN atau ID internal), sel TP/ringkasan, apakah ada proteksi/validasi, perbedaan Kurikulum Merdeka
vs K13, perbedaan antar-revisi RDM 3.1.

Langkah ketika file sudah tersedia:
1. Pemilik meletakkan contoh template asli (kosong dan terisi) di `reference/rdm-template/`
   beserta versi/revisi RDM-nya.
2. Buat `config/rdm_export.php` berisi pemetaan (sheet, baris awal, kolom NISN, kolom nilai, sel TP)
   per versi RDM, sehingga revisi RDM berikutnya cukup menambah konfigurasi.
3. `RdmExporter` mengisi salinan template asli (bukan membuat file dari nol), lalu unit test
   membandingkan dengan file contoh terisi.
