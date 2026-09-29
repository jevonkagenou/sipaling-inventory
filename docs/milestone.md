# Rencana Kerja dan Milestone Proyek SIPALING
**Sistem Inventaris Prediktif & Audit Log Terintegrasi**  
Dokumen Perencanaan Teknis dan Pembagian Kerja Tim (Minggu 6 s.d. Minggu 16)

---

## 1. Informasi Proyek dan Batasan Ruang Lingkup

### A. Metadata Proyek
| Parameter | Keterangan |
| :--- | :--- |
| Nama Sistem | SIPALING (Sistem Inventaris Prediktif & Audit Log Terintegrasi) |
| Arsitektur Perangkat Lunak | Monolith Modern (Laravel 12, Inertia.js v2, Vue 3 SFC, Tailwind CSS, shadcn-vue) |
| Jumlah Personil Tim | 5 Orang (Felix, Sultan, Desy, Najwa, Meutia) |
| Total Alokasi Waktu | 16 Minggu (Posisi Saat Ini: Minggu ke-5; Sisa Waktu Eksekusi: 11 Minggu) |
| Standar Identitas Entitas | UUID v4 (36 Karakter) untuk seluruh Primary Key dan Foreign Key |

### B. Batasan Sistem (Scope Lock)
| Aspek | Ketentuan Batasan Sistem |
| :--- | :--- |
| Hak Akses Pengguna | 4 Peran Statis berbasis Spatie Permission: Komisaris, Manajer Operasional, Staf Gudang, Auditor Internal |
| Domain Data Inventaris | Murni pencatatan kuantitas fisik barang. Tidak mencakup modul finansial, akuntansi, HPP, jurnal, atau perpajakan |
| Mesin Peramalan | Algoritma Double Exponential Smoothing (DES) Holt's Linear dieksekusi native di backend PHP tanpa service ML eksternal |
| Mekanisme Jejak Audit | Perekaman otomatis melalui Eloquent Model Observers dan Spatie Activitylog dengan sifat append-only (anti-manipulasi) |
| Cakupan Pergudangan | Berfokus pada sistem persediaan distributor tunggal terpusat tanpa kompleksitas multi-rak fisik |

---

## 2. Jadwal dan Rencana Rilis Mingguan (Minggu 6 - 16)

| Minggu Ke- | Target Rilis | Modul Utama | Fokus Deliverable |
| :---: | :---: | :--- | :--- |
| **6** | v0.1.1-alpha | Modul 1: Autentikasi & RBAC | Skema database UUID 13 tabel, instalasi Spatie Permission & Activitylog, konfigurasi 4 peran dan proteksi rute |
| **7** | v0.2.0-alpha | Modul 2: Master Data Inventaris | CRUD Master Kategori dan Produk, seeder dataset retail Kaggle, TanStack Table dengan status ketersediaan stok |
| **8** | v0.2.5-alpha | Modul 3: Transaksi Stok (Backend) | Skema header dan detail mutasi, transaksi atomik DB transaction, row-level locking, validasi stok keluar |
| **9** | v0.3.0-alpha | Modul 3: Transaksi Stok (Frontend) | Antarmuka transaksi Inbound dan Outbound, formulir multi-item dinamis, visualisasi riwayat mutasi stok |
| **10** | v0.3.5-beta | Modul 4: Engine DES (Backend) | Service matematika Holt's Linear, inisialisasi level dan tren, grid search parameter alpha-beta, metrik MAPE dan RMSE |
| **11** | v0.4.0-beta | Modul 4: Analitik DES (Frontend) | Dasbor peramalan Manajer Operasional, grafik tren historis vs estimasi, rekomendasi kuantitas restock otomatis |
| **12** | v0.4.5-beta | Modul 5: Approval Workflow (Logic) | Skema tabel restock_requests, state machine alur persetujuan, policy otorisasi Manajer dan Komisaris |
| **13** | v0.5.0-beta | Modul 5: Approval Workflow (UI) | Dasbor evaluasi Komisaris, dialog tindakan setujui/tolak dengan catatan, notifikasi in-app untuk staf gudang |
| **14** | v0.6.0-rc | Modul 6: Audit Trail & Investigasi | Portal verifikasi Auditor Internal, visual JSON diff viewer (perbandingan data lama vs baru), ekspor laporan PDF/Excel |
| **15** | v0.9.0-rc | Pengujian Sistem (PMPL) | Pengujian fungsional unit testing, integration testing, concurrency race-condition test, dan security audit |
| **16** | v1.0.0-GA | Finalisasi dan Peluncuran | Optimasi performa frontend/backend, penyusunan dokumentasi teknis dan buku panduan pengguna, UAT final |

---

## 3. Rincian Pekerjaan Teknis Berdasarkan Modul dan Lapisan Arsitektur

### Modul 1: Autentikasi, Profil & RBAC (Spatie Permission)
Target Penyelesaian: Minggu ke-6

| ID | Lapisan | Rincian Tugas Teknis | Target Output / Deliverable | PIC | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| M1-BE-01 | Backend / Skema | Mengubah migrasi users agar id bertipe UUID, menambah kolom phone dan is_active | Migrasi `users` dengan primary key UUID | Meutia | [x] |
| M1-BE-02 | Backend / Skema | Menyesuaikan migrasi Spatie Permission agar kolom model_id mendukung tipe UUID | Skema tabel Spatie Permission dengan UUID | Felix | [ ] |
| M1-BE-03 | Backend / Model | Menambahkan trait HasRoles dan HasUuids pada model User.php | Model `User.php` terintegrasi UUID dan Spatie | Felix | [ ] |
| M1-LC-01 | Logika / Seeder | Membuat RoleAndPermissionSeeder.php untuk 4 peran statis dan akun bawaan | Seeder peran dan user default siap pakai | Felix | [ ] |
| M1-LC-02 | Logika / Middleware | Menerapkan middleware pembatasan hak akses berbasis peran pada routes/web.php | Rute terlindungi berdasarkan hak akses | Felix | [ ] |
| M1-LC-03 | Logika / Middleware | Mengonfigurasi HandleInertiaRequests.php untuk membagikan data peran ke Vue | State `auth.roles` tersedia di seluruh halaman Vue | Felix | [ ] |
| M1-FE-01 | Frontend / UI | Memperbarui antarmuka Login.vue dengan pesan validasi akun non-aktif | Form login terintegrasi validasi status akun | Najwa | [ ] |
| M1-FE-02 | Frontend / UI | Membangun navigasi dinamis di AuthenticatedLayout.vue berbasis peran pengguna | Menu sidebar/navbar adaptif sesuai peran aktif | Desy | [ ] |
| M1-FE-03 | Frontend / UI | Menyusun halaman manajemen pengguna untuk pengaturan aktivasi akun | Halaman kelola user dengan badge role | Desy | [ ] |

---

### Modul 2: Master Data Inventaris (Kategori & Produk)
Target Penyelesaian: Minggu ke-7

| ID | Lapisan | Rincian Tugas Teknis | Target Output / Deliverable | PIC | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| M2-BE-01 | Backend / Skema | Membuat migrasi tabel categories dengan kolom id (UUID), name, slug, description | Skema tabel `categories` | Felix | [x] |
| M2-BE-02 | Backend / Skema | Membuat migrasi tabel products dengan category_id (UUID), sku, name, unit, stok | Skema tabel `products` dengan safety stock | Felix | [x] |
| M2-BE-03 | Backend / Model | Membangun relasi Eloquent one-to-many antara Category dan Product | Model `Category.php` dan `Product.php` aktif | Felix | [x] |
| M2-LC-01 | Logika / Validasi | Membuat Form Request StoreProductRequest dan UpdateProductRequest | Validasi keunikan SKU dan kuantitas numerik | Sultan | [ ] |
| M2-LC-02 | Logika / Controller | Membuat ProductController.php dengan proteksi restrict delete jika berelasi transaksi | Controller master barang dengan proteksi integritas | Sultan | [ ] |
| M2-LC-03 | Logika / Seeder | Mengimpor dataset Kaggle retail ke database melalui seeder InventoryCsvSeeder | 5.000 data produk dan kategori terisi di database | Felix | [x] |
| M2-FE-01 | Frontend / UI | Menyempurnakan Inventory/Index.vue dengan TanStack Table dan pagination | Tabel katalog inventaris interaktif dan cepat | Felix | [x] |
| M2-FE-02 | Frontend / UI | Membuat Dialog Modal shadcn-vue untuk form Tambah dan Edit Produk | Form popup modal tambah/edit barang | Najwa | [ ] |
| M2-FE-03 | Frontend / UI | Menerapkan badge visual status ketersediaan stok (Aman, Reorder, Habis) | Indikator visual level persediaan barang | Felix | [x] |

---

### Modul 3: Transaksi Stok Harian (Inbound & Outbound)
Target Penyelesaian: Minggu ke-8 s.d. Minggu ke-9

| ID | Lapisan | Rincian Tugas Teknis | Target Output / Deliverable | PIC | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| M3-BE-01 | Backend / Skema | Membuat migrasi stock_transactions untuk header bukti mutasi masuk/keluar | Skema tabel header `stock_transactions` | Felix | [x] |
| M3-BE-02 | Backend / Skema | Membuat migrasi stock_transaction_details untuk rincian item barang mutasi | Skema tabel detail `stock_transaction_details` | Felix | [x] |
| M3-BE-03 | Backend / Model | Membuat model StockTransaction dan StockTransactionDetail beserta relasinya | Model Eloquent transaksi stok siap pakai | Felix | [x] |
| M3-LC-01 | Logika / Service | Membangun StockTransactionService dengan eksekusi transaksi atomik DB::transaction | Service mutasi stok bergaransi konsistensi | Sultan | [ ] |
| M3-LC-02 | Logika / Locking | Menerapkan lockForUpdate pada baris produk untuk mencegah race condition mutasi | Proteksi konkurensi stok terhindar dari minus | Sultan | [ ] |
| M3-LC-03 | Logika / Nomor | Membuat generator otomatis nomor referensi dokumen mutasi (TRX-IN / TRX-OUT) | Penomoran unik otomatis berformat standar | Sultan | [ ] |
| M3-FE-01 | Frontend / UI | Membangun antarmuka Transaksi Masuk (InboundCreate.vue) dengan input multi-item | Formulir dinamis penerimaan barang gudang | Desy | [ ] |
| M3-FE-02 | Frontend / UI | Membangun antarmuka Transaksi Keluar (OutboundCreate.vue) dengan batas stok riil | Formulir pengeluaran barang dengan validasi stok | Desy | [ ] |
| M3-FE-03 | Frontend / UI | Membuat halaman riwayat mutasi Transactions/Index.vue dengan tab pemisah | Halaman riwayat transaksi dan filter tanggal | Desy | [ ] |
| M3-FE-04 | Frontend / UI | Membuat dialog rincian bukti transaksi mutasi yang dapat dicetak | Tampilan cetak bukti serah terima barang | Najwa | [ ] |

---

### Modul 4: Mesin Analitik & Peramalan DES (Double Exponential Smoothing)
Target Penyelesaian: Minggu ke-10 s.d. Minggu ke-11

| ID | Lapisan | Rincian Tugas Teknis | Target Output / Deliverable | PIC | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| M4-BE-01 | Backend / Skema | Membuat migrasi forecasting_logs untuk menyimpan histori parameter dan hasil DES | Skema tabel `forecasting_logs` | Felix | [ ] |
| M4-BE-02 | Backend / Model | Membuat Model ForecastingLog.php dengan relasi ke model Product | Model Eloquent log peramalan terintegrasi | Felix | [ ] |
| M4-LC-01 | Logika / Algoritma | Menyusun DoubleExponentialSmoothingService dengan rumus Holt's Linear (Level & Tren) | Service murni PHP penghitung proyeksi tren | Felix | [ ] |
| M4-LC-02 | Logika / Akurasi | Mengimplementasikan fungsi kalkulasi metrik error MAPE dan RMSE | Kalkulator tingkat akurasi dan deviasi peramalan | Felix | [ ] |
| M4-LC-03 | Logika / Optimasi | Membangun fungsi Grid Search untuk menemukan pasangan nilai alpha-beta terbaik | Pencarian otomatis parameter error terendah | Felix | [ ] |
| M4-LC-04 | Logika / Kalkulasi | Menghitung rekomendasi kuantitas restock: Max(0, Forecast + Safety Stock - Current Stock) | Formula output saran kuantitas pengadaan | Felix | [ ] |
| M4-FE-01 | Frontend / UI | Membangun Dasbor Analitik Manajer Operasional (Analytics/Index.vue) | Halaman dasbor analitik dan statistik stok | Meutia | [ ] |
| M4-FE-02 | Frontend / UI | Mengintegrasikan visualisasi kurva aktual vs proyeksi peramalan menggunakan Chart | Komponen visual kurva pergerakan kebutuhan barang | Najwa | [ ] |
| M4-FE-03 | Frontend / UI | Menyediakan kartu informasi akurasi (Nilai Alpha, Nilai Beta, Persentase MAPE) | Kartu visual ringkasan performa algoritma | Najwa | [ ] |
| M4-FE-04 | Frontend / UI | Membuat tabel rekomendasi stok menipis dengan tombol aksi cepat ajukan restock | Tabel prioritas pengadaan barang kritis | Meutia | [ ] |

---

### Modul 5: Alur Persetujuan Pengadaan (Restock Approval Workflow)
Target Penyelesaian: Minggu ke-12 s.d. Minggu ke-13

| ID | Lapisan | Rincian Tugas Teknis | Target Output / Deliverable | PIC | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| M5-BE-01 | Backend / Skema | Membuat migrasi restock_requests dengan status enum (pending, approved, rejected) | Skema tabel alur pengajuan `restock_requests` | Sultan | [ ] |
| M5-BE-02 | Backend / Model | Membuat Model RestockRequest.php dengan relasi requester dan reviewer | Model alur persetujuan dengan relasi pengguna | Sultan | [ ] |
| M5-LC-01 | Logika / Policy | Membuat RestockRequestPolicy untuk otorisasi hak ajukan (Manajer) dan setujui (Komisaris) | Kebijakan otorisasi alur kerja approval | Sultan | [ ] |
| M5-LC-02 | Logika / Service | Membangun RestockRequestService untuk mengelola transisi status dokumen pengadaan | Service alur kerja approval anti-bypass | Sultan | [ ] |
| M5-LC-03 | Logika / Event | Mengaitkan persetujuan dokumen dengan opsi pembuatan draf transaksi Inbound | Integrasi otomatis restock ke transaksi masuk | Sultan | [ ] |
| M5-FE-01 | Frontend / UI | Membangun halaman daftar pengajuan Restock/Index.vue dengan tab status | Monitoring pengajuan restock multi-status | Desy | [ ] |
| M5-FE-02 | Frontend / UI | Membuat formulir dialog pengajuan restock terisi otomatis dari rekomendasi DES | Form pengajuan cepat bagi Manajer Operasional | Najwa | [ ] |
| M5-FE-03 | Frontend / UI | Membangun Dasbor Otorisasi Komisaris (Restock/Approval.vue) dengan kartu evaluasi | Halaman tinjauan khusus Komisaris | Meutia | [ ] |
| M5-FE-04 | Frontend / UI | Membuat dialog persetujuan (approve) dan penolakan (reject wajib mengisi alasan) | Modal aksi keputusan Komisaris | Najwa | [ ] |

---

### Modul 6: Audit Trail & Investigasi Forensik Log
Target Penyelesaian: Minggu ke-14

| ID | Lapisan | Rincian Tugas Teknis | Target Output / Deliverable | PIC | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| M6-BE-01 | Backend / Skema | Menyesuaikan migrasi Spatie Activitylog agar kolom causer_id dan subject_id UUID | Skema tabel `activity_log` kompatibel UUID | Felix | [ ] |
| M6-BE-02 | Backend / Model | Menimpa model ActivityLog dengan menonaktifkan delete() dan update() di Eloquent | Jaminan log bersifat append-only permanen | Felix | [ ] |
| M6-LC-01 | Logika / Observer | Mendaftarkan ProductObserver dan StockTransactionObserver untuk merekam histori | Perekaman otomatis data lama dan data baru | Felix | [ ] |
| M6-LC-02 | Logika / Security | Menangkap metadata keamanan (Alamat IP Klien dan User Agent) ke properties log | Jejak audit forensik lengkap dengan IP pengguna | Felix | [ ] |
| M6-LC-03 | Logika / Service | Membangun AuditLogQueryService dengan filter rentang tanggal, modul, dan aktor | Service penelusuran histori berkecepatan tinggi | Felix | [ ] |
| M6-FE-01 | Frontend / UI | Membangun portal investigasi log khusus Auditor Internal (Audit/Index.vue) | Halaman investigasi riwayat sistem | Meutia | [ ] |
| M6-FE-02 | Frontend / UI | Membuat komponen Visual Diff Viewer untuk perbandingan data lama vs data baru | Dialog perbandingan nilai perubahan atribut | Najwa | [ ] |
| M6-FE-03 | Frontend / UI | Menyediakan filter pencarian histori multi-parameter (Tanggal, Pengguna, Modul) | Filter data log interaktif | Meutia | [ ] |
| M6-FE-04 | Frontend / UI | Membuat fitur ekspor log audit ke dalam format berkas CSV dan dokumen PDF | Laporan audit berkas siap unduh | Meutia | [ ] |

---

## 4. Pengujian Terintegrasi, Hardening, dan Peluncuran (Minggu 15 - 16)

### A. Rincian Pengujian Sistem (Minggu ke-15)
| Kategori Pengujian | Fokus Pengujian dan Skenario Validasi | Standar Keberhasilan | PIC | Status |
| :--- | :--- | :--- | :---: | :---: |
| Unit Testing | Akurasi formula DES Holt's Linear dibandingkan dengan perhitungan Excel | Selisih nilai proyeksi < 0.001 | Felix | [ ] |
| Unit Testing | Validasi mutasi stok dengan kuantitas 0 atau bernilai minus | Sistem otomatis menolak input tidak valid | Desy | [ ] |
| Integration Testing | Pengujian alur isolasi peran (Staf Gudang dilarang approve, dsb) | Respon otorisasi HTTP 403 Forbidden | Desy | [ ] |
| Integration Testing | Pengujian integritas atomik (simulasi kegagalan koneksi di tengah transaksi) | Rollback database berhasil tanpa selisih stok | Sultan | [ ] |
| Concurrency Testing | Simulasi 50 transaksi mutasi simultan pada produk yang sama | Terhindar dari race condition dan stok minus | Sultan | [ ] |
| Security Testing | Percobaan eksekusi manipulasi penghapusan paksa tabel activity_log | Eksekusi diblokir oleh model guard | Felix | [ ] |
| Usability Testing | Uji kelancaran navigasi, validasi input form, dan integritas berkas ekspor CSV/PDF | Seluruh form responsif dan berkas terunduh utuh | Meutia | [ ] |

### B. Finalisasi dan Handover (Minggu ke-16)
| Kategori Kegiatan | Rincian Kegiatan Finalisasi | Target Output | PIC | Status |
| :--- | :--- | :--- | :---: | :---: |
| Optimasi Performa | Menghilangkan N+1 query problem via eager loading with() | Waktu respon query di bawah 200ms | Felix | [ ] |
| Optimasi Aset | Code-splitting, penataan chunk size Vite, dan build validasi | Berkas build terkompilasi optimal | Sultan | [ ] |
| Desain Konsistensi | Pengecekan responsivitas mobile dan keselarasan tema gelap/terang | UI rapi tanpa glitch visual | Najwa | [ ] |
| Dokumentasi | Penyusunan Dokumen Teknis Arsitektur dan Panduan Pengguna | Berkas Technical Doc & User Manual | Meutia | [ ] |
| UAT & Handover | Sesi evaluasi sistem terintegrasi bersama pemangku kepentingan | Berita Acara Penerimaan Sistem | Seluruh Tim | [ ] |

---

## 5. Matriks Alokasi Tanggung Jawab Personil Tim (5 Orang)

| Personil | Posisi / Spesialisasi | Cakupan Utama Modul | Tanggung Jawab Deliverable |
| :--- | :--- | :--- | :--- |
| **Felix Alberta Jevon (Felix)** | Project Manager, System Architect & Algorithm Lead | Modul 1 (Auth & RBAC), Modul 2 & 3 (Skema UUID MVP), Modul 4 (Engine DES Holt's Linear), & Modul 6 (Audit Trail) | Memimpin arsitektur sistem, memegang mesin peramalan Double Exponential Smoothing (DES Holt's Linear), metrik akurasi MAPE/RMSE, Grid Search parameter, otentikasi & RBAC Spatie, pengawasan audit trail append-only & observer, serta pengujian keamanan sistem. |
| **Sultan Syarif Ubaidillah A. N. (Sultan)** | Main Developer (Fullstack Backend & Concurrency) | Modul 2 (Validasi & Controller Produk), Modul 3 (Atomic Mutasi & Concurrency), & Modul 5 (Restock Approval Backend) | Mengembangkan logika transaksi atomik (`DB::transaction` & `lockForUpdate`), penomoran mutasi unik, proteksi integritas restrict delete produk, backend alur persetujuan pengadaan (migrasi, model, policy & service approval), simulasi uji konkurensi 50 transaksi, dan optimasi build. |
| **Desy Dwi Puspita (Desy)** | QA & Frontend Developer (Operasional Pergudangan) | Modul 1 (Navigasi Peran & Kelola User), Modul 3 (Form Mutasi Masuk/Keluar & Riwayat), Modul 5 (List Approval), & QA Testing | Membangun antarmuka operasional pergudangan harian: formulir transaksi Inbound & Outbound, riwayat mutasi stok, halaman monitoring pengajuan restock, navigasi peran di AuthenticatedLayout, halaman manajemen pengguna, serta pengujian fungsional mutasi & isolasi peran. |
| **Najwa Kus Syafira (Najwa)** | UI/UX Designer & Frontend Component Specialist | Komponen Modal/Dialog Interaktif (Modul 2, 3, 5, 6), Visualisasi Grafik DES (Modul 4), & Desain Konsistensi | Mengembangkan seluruh komponen interaktif visual (Dialog/Modal form produk, dialog cetak bukti mutasi, dialog persetujuan/penolakan restock, modal visual diff viewer), visualisasi kurva peramalan Chart, kartu metrik akurasi DES, serta konsistensi desain tema gelap/terang & mobile. |
| **Meutia Arafah Hidayat (Meutia)** | Frontend Developer, Dokumentator & QA (Governance & Analitik) | Modul 4 (Dasbor Analitik & Rekomendasi Restock), Modul 5 (Dasbor Otorisasi Komisaris), Modul 6 (Portal Audit Trail & Ekspor), & Dokumentasi | Membangun antarmuka tata kelola & analitik: Dasbor Analitik Manajer Operasional, tabel rekomendasi stok menipis, Dasbor Evaluasi Otorisasi Komisaris, portal investigasi audit log beserta filter multi-parameter, fitur ekspor berkas CSV/PDF log audit, penyusunan Dokumen Teknis Arsitektur, User Manual, logbook PBL, dan pelaksanaan UAT. |

---

## 6. Standar Penyelesaian Tugas (Definition of Done)

| Parameter | Kriteria Standar Kualitas |
| :--- | :--- |
| Integritas Tipe Data | Seluruh data transaksi menggunakan format UUID v4 36-karakter yang valid |
| Validasi Error | Memiliki batas penanganan error pada sisi backend dan validasi format sisi frontend |
| Jejak Audit | Setiap tindakan simpan, ubah, dan hapus menghasilkan rekaman mutlak di activity_log |
| Kerapian Tampilan | Tampilan komponen adaptif pada layar desktop dan perangkat mobile serta selaras pada mode terang dan gelap |
| Standar Kode Sumber | Lolos pengecekan linter tanpa error kompilasi dan ditulis menggunakan standar konvensi Laravel dan Vue 3 |
| Standar Versi Git | Riwayat commit git terstruktur mengikuti format Conventional Commits (feat:, fix:, refactor:, test:) |
