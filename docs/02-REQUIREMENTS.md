# 02 — Requirements

Setiap kebutuhan punya ID. Test dan commit sebaiknya menyebut ID terkait (mis. `FR-GUR-04`).
"Harus" = wajib v1. "Sebaiknya" = boleh ditunda bila tahap padat, catat di TASKS.

## A. Kebutuhan fungsional

### A1. Autentikasi & sesi (AUTH)

- **FR-AUTH-01** Login memakai username (NIK/NUPTK/kode bebas) + password + pilihan Tahun Pelajaran
  + Semester. Pilihan default = periode aktif.
  - Terima: kombinasi salah → pesan "Username atau password salah" tanpa menyebut field mana.
  - Terima: periode terpilih disimpan di session (`period_id`) dan tampil di badge sidebar
    "SEMESTER GANJIL 2026/2027".
- **FR-AUTH-02** Rate limit login 5 percobaan/menit per username+IP.
- **FR-AUTH-03** User nonaktif (`is_active = false`) tidak bisa login.
- **FR-AUTH-04** Operator dapat mereset password user lain. User dapat mengganti password sendiri.
- **FR-AUTH-05** Setelah login, user diarahkan ke dashboard sesuai role.
- **FR-AUTH-06** Kepala madrasah memiliki akses hanya baca: semua request non-GET dari role kepala
  ditolak 403 di server (selain logout dan ganti password sendiri).

### A2. Profil & branding (PRF) — operator

- **FR-PRF-01** Isi identitas lembaga: instansi induk (baris 1 kop, boleh kosong), yayasan (opsional),
  nama madrasah, jenjang, NSM, NPSN, alamat, kecamatan, kabupaten/kota, provinsi, telepon/email.
- **FR-PRF-02** Isi nama & NIP kepala madrasah (untuk tanda tangan rapor).
- **FR-PRF-03** Unggah logo kop kiri (wajib untuk cetak) dan logo kop kanan (opsional). PNG/JPG,
  ≤ 1 MB, disimpan di `storage/app/public/branding`.
- **FR-PRF-04** Branding aplikasi: nama aplikasi, subjudul, logo aplikasi, gambar halaman login,
  warna topbar (hex). Berlaku di login, sidebar, dan judul tab.
- **FR-PRF-05** Pratinjau kop tampil di halaman yang sama dan berubah setelah disimpan.

### A3. Lembaga (LMB) — operator

- **FR-LMB-01** Kelola tahun pelajaran (mis. 2026/2027) dan semester (Ganjil/Genap). Tepat satu
  periode aktif.
- **FR-LMB-02** Kelola mata pelajaran: kode, nama, induk (untuk sub-mapel PAI: QH, AA, FIK, SKI),
  kelompok (Umum/Muatan Lokal), kurikulum, urutan cetak, aktif/nonaktif.
- **FR-LMB-03** Seeder menyediakan daftar mapel MTs Kurikulum Merdeka bawaan (lihat ERD §seed) yang
  bisa diubah operator.
- **FR-LMB-04** Kelola kelas per tahun pelajaran: tingkat (VII–IX untuk MTs), nama (VII.A), nama alias,
  kurikulum (Merdeka/K13), fase (D), wali kelas. Fitur "Tambah Sekaligus" membuat beberapa kelas
  paralel (A, B, C…) dalam satu form.

### A4. Guru & mengajar (GRU) — operator

- **FR-GRU-01** Kelola guru: NIK/NUPTK, NIP (opsional), nama + gelar, L/P, username, password awal,
  aktif/nonaktif. Upload massal via template Excel.
- **FR-GRU-02** Data Mengajar: pilih tingkat & kelas → tabel semua mapel kelas itu → pilih guru per mapel
  (dropdown) dan JTM. Simpan per baris.
- **FR-GRU-03** "Salin Mengajar": salin seluruh pembagian mengajar dari periode sebelumnya ke periode
  aktif (hanya untuk kelas & guru yang masih aktif). Tidak menimpa data yang sudah ada.
- **FR-GRU-04** Satu mapel di satu kelas pada satu periode hanya punya satu guru (unique).

### A5. Siswa (SIS) — operator

- **FR-SIS-01** Kelola siswa: NIS, NISN (unique), nama, L/P, tempat & tanggal lahir, alamat, aktif.
- **FR-SIS-02** Upload siswa per kelas via template Excel (lihat 08-IMPORT-EXPORT). Baris gagal
  dilaporkan beserta nomor baris dan alasan; baris valid tetap tersimpan.
- **FR-SIS-03** Penempatan siswa ke kelas per tahun pelajaran. Satu siswa hanya di satu kelas per tahun.
- **FR-SIS-04** Pindah kelas (dalam tahun yang sama) tanpa kehilangan nilai yang sudah masuk: nilai
  tetap terikat ke siswa + teaching assignment lama dan tidak ikut pindah (dicatat di activity log).

### A6. Penilaian guru (NIL)

- **FR-NIL-01** Sidebar guru menampilkan mapel yang diampu pada periode aktif, dikelompokkan per mapel,
  dengan daftar kelas dan ikon status (✕ merah = belum kirim, ✓ hijau = terkirim).
- **FR-NIL-02** Bobot per mapel-kelas: JTM, bobot Sumatif LM, bobot STS. Bobot STS boleh 0. Total bobot
  tidak harus 100 — sistem menormalkan (lihat 07-CALCULATION §2). Default 50:50.
- **FR-NIL-03** Tambah lingkup materi (LM): nomor TP/LM ke-, ringkasan TP/LM (diawali KKO),
  nilai batas ketercapaian (KKTP, default dari pengaturan), lalu tabel siswa untuk isi nilai.
  Kolom "Ketercapaian" terisi otomatis saat nilai diketik (client-side, hanya tampilan) dan dihitung
  ulang di server saat disimpan.
- **FR-NIL-04** Field "Isi semua" di header kolom nilai mengisi semua baris yang kosong.
- **FR-NIL-05** Edit dan hapus LM. Hapus meminta konfirmasi ("Nilai N siswa di LM ini ikut terhapus"),
  menghapus permanen LM beserta nilainya, dan dicatat di activity log (ADR-011). Tidak bisa saat
  mapel-kelas sudah terkirim atau kelas dikunci.
- **FR-NIL-06** Input nilai tes STS per siswa (manual atau upload template Excel).
- **FR-NIL-07** Validasi nilai: angka 0–100, maksimal 2 desimal; kosong = belum dinilai (bukan 0).
- **FR-NIL-08** Halaman Penilaian menampilkan per siswa: rerata sumatif, nilai STS, nilai rapor STS,
  dan capaian kompetensi otomatis. Siswa dengan data belum lengkap ditandai, nilai rapor tampil "–".
- **FR-NIL-09** Capaian kompetensi bisa diedit manual per siswa; hasil edit disimpan sebagai override
  dan diberi penanda. Tombol "Kembalikan ke otomatis".
- **FR-NIL-10** Tombol **Kirim**: hanya aktif jika semua siswa aktif di kelas punya nilai rapor STS.
  Setelah kirim, nilai mapel-kelas terkunci untuk guru. Status = terkirim.
- **FR-NIL-11** Wali kelas atau operator dapat **membatalkan kirim** agar guru bisa memperbaiki.
- **FR-NIL-12** Upload nilai LM via template Excel per mapel-kelas: satu sheet per LM (meniru RDM).
  Template diunduh dari aplikasi dan sudah berisi daftar siswa.

### A7. Wali kelas (WAL)

- **FR-WAL-01** Menu Wali Kelas muncul hanya untuk guru yang menjadi wali kelas pada periode aktif.
- **FR-WAL-02** Kehadiran per siswa: sakit, izin, alpa (bilangan bulat ≥ 0). Upload Excel sebaiknya ada.
- **FR-WAL-03** Catatan wali kelas per siswa (≤ 500 karakter). "Isi catatan massal" mengisi siswa yang
  catatannya masih kosong saja.
- **FR-WAL-04** Status Nilai kelasnya: progres (bar) Sumatif LM & STS, tabel mapel–guru–status.
- **FR-WAL-05** Kunci nilai kelas: hanya bisa jika semua mapel terkirim. Setelah dikunci, tidak ada
  perubahan nilai/kehadiran/catatan di kelas itu kecuali dibuka lagi oleh wali kelas/operator.
- **FR-WAL-06** Cetak rapor STS: per siswa, semua siswa (satu PDF gabungan), dan leger (PDF & Excel).
  Cetak tetap boleh sebelum dikunci, tetapi PDF diberi watermark "DRAFT" bila belum dikunci.

### A8. Operator — rapor (RAP)

- **FR-RAP-01** Pengaturan cetak: judul rapor, tempat cetak, tanggal cetak, label kolom nilai,
  ukuran kertas (A4/F4), watermark teks opsional, bagian yang tampil (capaian kompetensi,
  ketidakhadiran, catatan wali kelas, tanggapan orang tua, baris jumlah, QR verifikasi).
  Ekstrakurikuler & prestasi disembunyikan di v1.
- **FR-RAP-02** Nilai KKM/KKTP default per mapel per tingkat (dipakai sebagai default saat guru
  menambah LM).
- **FR-RAP-03** Operator dapat membuka Status Nilai dan Cetak Rapor untuk semua kelas.

### A9. Kepala madrasah (KPL) — hanya baca

- **FR-KPL-01** Dashboard: % nilai terkirim (mapel-kelas), jumlah guru belum kirim, kelas dikunci,
  rata-rata nilai rapor STS madrasah; filter tingkat.
- **FR-KPL-02** Tabel progres per kelas: wali kelas, mapel terkirim x/y, status kunci, rerata kelas,
  jumlah siswa dengan minimal satu mapel belum tercapai.
- **FR-KPL-03** Daftar guru yang belum mengirim: mapel, kelas, bagian yang belum diisi.
- **FR-KPL-04** Sebaran ketercapaian per tingkat (Sangat Baik/Baik/Cukup/Belum Tercapai).
- **FR-KPL-05** Rekap nilai (leger) per kelas, dengan pilihan tampilan: nilai rapor STS, rerata sumatif,
  nilai tes STS. Sel di bawah KKTP ditandai; "–" untuk belum dikirim.
- **FR-KPL-06** Lihat rapor STS siswa mana pun (PDF), unduh rekap Excel/PDF.
- **FR-KPL-07** Tidak ada tombol tambah/edit/hapus/kirim/kunci pada semua halaman kepala, DAN server
  menolak aksi tersebut (FR-AUTH-06).

### A10. Administrasi (ADM)

- **FR-ADM-01** Activity log: login, kirim/batal kirim, kunci/buka kunci, upload, ubah bobot, hapus LM.
  Tampil di dashboard operator (10 terbaru) dengan pencarian.
- **FR-ADM-02** Backup: operator mengunduh dump database (.sql.gz). Restore dilakukan manual oleh
  teknisi (tidak ada tombol restore di v1 — lihat ADR-010).
- **FR-ADM-03** Kelola akun admin/operator dan akun kepala madrasah.

## B. Kebutuhan non-fungsional

- **NFR-01 Kinerja**: halaman input nilai 40 siswa × 10 LM tampil < 1 detik di server shared hosting
  biasa; simpan satu tabel nilai < 2 detik. Cetak PDF satu kelas (40 rapor) < 60 detik.
- **NFR-02 Keamanan**: password bcrypt; CSRF aktif; semua route di balik `auth`; otorisasi Policy;
  upload divalidasi tipe & ukuran; nama file diacak; tidak ada data siswa di log.
- **NFR-03 Integritas**: simpan massal dalam transaksi; unique constraint di DB sesuai ERD;
  perubahan nilai setelah kirim hanya lewat alur batal kirim.
- **NFR-04 Kompatibilitas**: Chrome/Edge/Firefox terbaru; layar 1366×768 nyaman; layar HP bisa dipakai
  (sidebar menumpuk di atas, tabel bisa digeser horizontal).
- **NFR-05 Bahasa**: seluruh UI bahasa Indonesia; format tanggal "30 Oktober 2026"; desimal koma di
  tampilan (81,4), titik di DB.
- **NFR-06 Aksesibilitas**: label form terhubung, tombol asli, kontras ≥ 4.5:1, target klik ≥ 32px.
- **NFR-07 Deploy**: bisa dijalankan di shared hosting cPanel (PHP 8.3+, MySQL) maupun VPS. Tanpa
  queue worker wajib (cetak PDF besar boleh sinkron dengan batas waktu dinaikkan) — lihat ADR-009.
- **NFR-08 Kustomisasi**: identitas & branding 100% dari database; tidak perlu ubah kode per klien.
