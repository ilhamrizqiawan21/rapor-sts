# 04 — Design

Prinsip: **meniru alur dan tata letak RDM v3.1** supaya guru langsung familiar, tetapi dengan identitas
aplikasi sendiri (ADR-004). Semua layar ada di `mockup/` dan acuan aslinya di `reference/rdm/`.

## 1. Design tokens

Taruh sebagai CSS custom properties di `resources/css/app.css`. Warna topbar diambil dari
`app_settings.topbar_color` dan ditimpa inline di `<html style="--topbar: ...">`.

| Token | Nilai | Pemakaian |
|---|---|---|
| `--bg` | `#f4f6f9` | latar halaman |
| `--sidebar` | `#37404d` | sidebar |
| `--sidebar-sub` | `#2e3641` | item submenu |
| `--sidebar-text` | `#c9ced6` | teks sidebar |
| `--sidebar-section` | `#aab2bf` | label seksi (PENGATURAN, AKUN) |
| `--topbar` | `#628b0d` | topbar (bisa diganti klien) |
| `--primary` | `#2d24d4` | item aktif, tombol utama (Tambah, Simpan, Sumatif) |
| `--success` | `#1e7e34` | Kirim, Upload, status terkirim |
| `--danger` | `#c82333` | Kembali, Batal, Hapus, belum kirim |
| `--info` | `#1b6ec2` | STS, Semua Rapor, info |
| `--warning` | `#f2b01e` + teks `#3b2a00` | Export, kotak info kuning |
| `--semester-badge` | `#ffd43b` + garis putus-putus hitam | badge semester di sidebar |
| `--text` | `#212529` | teks utama |
| `--muted` | `#6c757d` | teks bantu |
| `--border` | `#dee2e6` / `#e3e6ea` | garis kartu & tabel |

Catatan kontras: RDM memakai teks putih di atas kuning; aplikasi ini memakai teks gelap `#3b2a00`
agar terbaca (ADR-004). Hijau/biru/merah tombol sudah digelapkan agar teks putih lulus 4.5:1.

Tipografi: `Source Sans 3` (Google Fonts, fallback Arial) untuk aplikasi, 14px dasar. Rapor cetak:
Arial/Helvetica untuk isi, Times New Roman untuk nama madrasah & alamat di kop.

Radius 3–4px, bayangan kartu `0 1px 3px rgba(0,0,0,.12)`. Tidak ada gradien, tidak ada emoji.

## 2. Layout aplikasi

```
┌──────────────┬──────────────────────────────────────────────┐
│ brand (putih)│ topbar (--topbar) ☰  [menu atas guru]  Nama ◯│
│ badge semstr ├──────────────────────────────────────────────┤
│ sidebar      │ breadcrumb: Home / Admin / Halaman           │
│  menu        ├──────────────────────────────────────────────┤
│  seksi       │ .body (padding 24)                           │
│              │   .card ─ .ch (judul + .acts tombol grup)    │
│              │          └ .cb (filter, tools tabel, tabel)  │
│              ├──────────────────────────────────────────────┤
│              │ footer: "Nama Aplikasi Versi x © tahun"      │
└──────────────┴──────────────────────────────────────────────┘
```

- Sidebar 240px, `flex-wrap` sehingga di HP sidebar pindah ke atas.
- Tombol aksi kartu berupa **grup tombol menempel** (`.grp`) di kanan atas kartu, seperti RDM.
- Tabel bergaya DataTables: baris alat (Copy/Print/Excel, Show entries, Search), header abu-abu,
  baris belang, paginasi "Showing 1 to 10 of N entries · First Previous 1 Next Last".
  Implementasi boleh server-side sederhana (search + paginate Laravel); tidak wajib plugin DataTables.
- Modal (Tambah Kelas, Tambah Nilai): judul + tombol × di kanan, isi, footer tombol "Batal" (merah)
  dan "Save changes" (biru).

## 3. Komponen (Blade components)

Buat di `resources/views/components/`:

| Komponen | Kelas CSS mockup | Isi |
|---|---|---|
| `<x-layout.app>` | `.app .side .main .top .crumb .body .foot` | layout utama, slot sidebar per role |
| `<x-sidebar.operator>` / `.guru` / `.kepala` | `.nav a .on .sub .sec` | menu per role |
| `<x-card title subtitle>` | `.card .ch .ct .cs .acts .cb` | kartu dengan slot `actions` |
| `<x-btn variant>` | `.btn .b-pri .b-suc .b-dan .b-war .b-inf .b-sec` | tombol/link |
| `<x-table-tools>` | `.tools .tbtn` | Copy/Print/Excel + search |
| `<x-status-pill status>` | `.pill` | Terkirim / Belum Kirim / Dikunci |
| `<x-progress value>` | `.bar` | bar progres |
| `<x-modal id title>` | — | modal dengan `<dialog>` HTML native |
| `<x-alert variant>` | `.alert .a-war .a-inf .a-lt .a-lav` | kotak info |
| `<x-kop>` | — | kop rapor (dipakai di pratinjau profil & PDF) |

## 4. Peta layar → mockup → requirement

| # | Layar | File mockup | Role | FR |
|---|---|---|---|---|
| 01 | Login | `01-login.html` | semua | AUTH-01 |
| 02 | Dashboard operator | `02-operator-dashboard.html` | operator | ADM-01 |
| 03 | Profil madrasah & branding | `03-operator-profil.html` | operator | PRF-* |
| 04 | Data mengajar guru | `04-operator-mengajar.html` | operator | GRU-02/03 |
| 05 | Pengaturan cetak | `05-operator-pengaturan-cetak.html` | operator | RAP-01 |
| 06 | Status nilai | `06-status-nilai.html` | operator, wali | WAL-04/05 |
| 07 | Cetak rapor | `07-cetak-rapor.html` | operator, wali, kepala* | WAL-06, KPL-06 |
| 08 | Bobot | `08-guru-bobot.html` | guru | NIL-02 |
| 09 | Penilaian STS (ringkasan) | `09-guru-penilaian.html` | guru | NIL-08/09/10 |
| 10 | Nilai sumatif LM | `10-guru-sumatif.html` | guru | NIL-03/05/12 |
| 11 | Modal tambah nilai LM | `11-guru-tambah-nilai.html` | guru | NIL-03/04 |
| 12 | Nilai STS | `12-guru-sts.html` | guru | NIL-06 |
| 13 | Kehadiran & catatan | `13-wali-kehadiran-catatan.html` | wali | WAL-02/03 |
| 14–15 | Rapor STS halaman 1–2 | `14-rapor-hal1.html`, `15-rapor-hal2.html` | cetak | WAL-06 |
| 16 | Dashboard kepala | `16-kepala-dashboard.html` | kepala | KPL-01..04 |
| 17 | Rekap nilai (leger) | `17-kepala-leger.html` | kepala | KPL-05 |

\* versi kepala tanpa tombol aksi; label "HANYA LIHAT" di topbar.

Layar yang belum dibuat mockup-nya, ikuti pola layar sejenis: Data Kelas & modal Tambah Kelas
(lihat `reference/rdm/160151.jpg`, `160204.jpg`), Data Guru (`160318.jpg`), Data Siswa (`160353.jpg`),
Mata Pelajaran (`160125.jpg`), Pengaturan Lembaga (`160108.jpg`), Nilai KKM, Admin Aplikasi,
Backup, Kinerja Input Guru (kepala).

Di layar 09, siswa yang nilai STS-nya belum diisi menampilkan nilai rapor **"–"** (bukan dihitung
dengan STS = 0). Lihat 07-CALCULATION §2–3.

## 5. Menu per role

- **Operator**: Dashboard · Profil Madrasah · PENGATURAN: Lembaga (Pengaturan, Mata Pelajaran, Kelas),
  Guru (Data Guru, Mengajar), Siswa (Data Siswa, Pindah Kelas) · Rapor (Nilai KKM, Pengaturan Cetak,
  Status Nilai, Cetak Rapor) · ADMINISTRASI: Admin Aplikasi, Backup · AKUN: Logout.
- **Guru**: Dashboard · [KODE - Mapel] → daftar kelas + ikon status · TUGAS TAMBAHAN: Wali Kelas X
  (Kehadiran & Catatan, Status Nilai, Cetak Rapor STS) — hanya jika wali kelas · AKUN: Logout.
  Topbar: Beranda · Bobot · Penilaian (konteks mapel-kelas terpilih).
- **Kepala**: Dashboard · PEMANTAUAN: Progres Nilai, Rekap Nilai (Leger), Kinerja Input Guru ·
  LAPORAN: Lihat Rapor STS, Unduh Laporan · AKUN: Logout.

## 6. Spesifikasi rapor STS (PDF)

Acuan: rapor akhir semester hasil cetak RDM (2 halaman A4). Mockup `14-rapor-hal1.html` & `15-rapor-hal2.html`.

Halaman 1
1. **Kop**: logo kiri 78×78px; tengah: `parent_institution` (Arial bold 12.5px, kapital),
   nama madrasah (Times bold 18px, kapital), alamat (Times italic 12.5px), "Kecamatan X, Kabupaten Y -
   Provinsi" (Times italic). Logo kanan opsional. Bila tidak ada logo kanan, beri ruang kosong
   seukuran logo agar teks tetap di tengah.
2. **Identitas** di antara dua garis 2px: kiri NAMA, NIS/NISN ("NIS / NISN"), Madrasah, Alamat
   (alamat madrasah); kanan Kelas, Fase, Semester, Tahun Pelajaran.
3. **Judul**: `print_settings.report_title` (default "LAPORAN HASIL SUMATIF TENGAH SEMESTER"),
   bold 14.5px, tengah.
4. **Tabel**: No | Mata Pelajaran | `score_label` (default "Nilai STS") | Capaian Kompetensi.
   Mapel induk (PAI) tampil sebagai baris judul tanpa nilai, sub-mapel diberi huruf A., B., C., D.
   Capaian 9.5px rata kiri-kanan. Baris "Jumlah" hanya jika diaktifkan.
5. **Footer**: kiri "KELAS _ NAMA _ NISN" 9.5px, kanan "Halaman 1".

Halaman 2
1. (v1.1) Ekstrakurikuler, Prestasi — disembunyikan di v1.
2. Ketidakhadiran: Sakit / Izin / Alpa | angka | "Hari".
3. Catatan Wali Kelas (kotak).
4. Tanggapan Orang Tua/Wali (kotak kosong) — jika diaktifkan.
5. Tanda tangan: kiri "Orang Tua/Wali" + garis; kanan "{tempat}, {tanggal}" / "Wali Kelas" / nama
   (bold) / "NIP." + NIP wali; tengah bawah "Mengetahui / Kepala Madrasah" / nama (bold) / "NIP. ...".
6. QR verifikasi opsional di kiri bawah (v1.1 — isi URL verifikasi; di v1 cukup tidak tampil).
7. Footer sama seperti halaman 1, "Halaman 2".

Ukuran kertas dari pengaturan (A4 210×297 mm, F4 215×330 mm), margin 12 mm.
