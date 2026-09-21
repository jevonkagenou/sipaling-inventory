## Panduan Instalasi Lokal (Development)

Panduan ini dirancang langkah demi langkah agar setiap anggota tim dapat menjalankan repositori SIPALING di komputer masing-masing tanpa kendala. Pastikan Anda mengikuti urutan instruksi dengan teliti.

### Persiapan Sistem (Prerequisites)
Sebelum memulai kloning, pastikan komputer Anda telah memasang perangkat lunak berikut:
*   **PHP** (Minimal versi 8.2)
*   **Composer** (Manajer dependensi PHP)
*   **Node.js & npm** (Minimal versi 18.x / direkomendasikan versi LTS terbaru)
*   **Git** (Untuk menarik repositori)
*   **XAMPP / Laragon** (Atau *service* MySQL/MariaDB lokal lainnya)

---

### Langkah-Langkah Instalasi

**1. Kloning Repositori**
Buka terminal (atau Git Bash) di direktori tempat Anda ingin menyimpan proyek, lalu jalankan:
```bash
git clone https://github.com/USERNAME/sipaling-inventory.git
cd sipaling-inventory
```
*(Catatan: Ganti `USERNAME` dengan tautan repositori tim Anda).*

**2. Instalasi Dependensi Backend (PHP)**
Unduh semua pustaka Laravel yang dibutuhkan oleh sistem:
```bash
composer install
```

**3. Instalasi Dependensi Frontend (Vue & Shadcn)**
Unduh mesin Vite, pustaka komponen UI, beserta ikon:
```bash
npm install
```

**4. Konfigurasi Lingkungan Lokal (.env)**
Salin *file* konfigurasi bawaan agar sistem mengenali pengaturan komputer lokal Anda:
```bash
cp .env.example .env
```
Buka file `.env` yang baru saja dibuat menggunakan teks editor (seperti VS Code), lalu cari dan sesuaikan blok konfigurasi *database* ini:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sipaling_db   # Sesuaikan dengan nama database yang akan Anda buat
DB_USERNAME=root          # Umumnya 'root' untuk bawaan XAMPP/Laragon
DB_PASSWORD=              # Kosongkan jika XAMPP Anda tidak dipasangi password
```

**5. Buat Database Kosong di MySQL**
Langkah ini wajib dilakukan **sebelum** melakukan migrasi:
*   Buka aplikasi XAMPP/Laragon dan tekan *Start* pada modul **MySQL**.
*   Buka *browser* dan akses manajer *database* Anda (contoh: `http://localhost/phpmyadmin`).
*   Buat *database* baru dengan nama persis seperti yang tertera di `DB_DATABASE` pada file `.env` Anda (misalnya: `sipaling_db`).

**6. Amankan Sesi & Bangun Struktur Tabel**
Kembali ke terminal, jalankan perintah ini untuk membuat kunci keamanan enkripsi aplikasi, menyusun tabel *database*, dan mengisi data akun uji coba:
```bash
php artisan key:generate
php artisan migrate:fresh --seed
```

**7. Jalankan Mesin Aplikasi (Langkah Terakhir)**
Arsitektur Laravel dengan Inertia + Vue membutuhkan **dua terminal** yang menyala secara bersamaan. Jangan tutup kedua terminal ini selama Anda sedang menguji atau menulis kode.

*   **Buka Terminal 1** (Untuk menjalankan peladen *backend* PHP):
    ```bash
    php artisan serve
    ```
*   **Buka Terminal 2** (Untuk mengaktifkan mesin perender *frontend* Vite):
    ```bash
    npm run dev
    ```

Aplikasi kini sepenuhnya beroperasi! Buka *browser* Anda dan akses: **`http://localhost:8000`**

---

### Solusi Masalah Umum (Troubleshooting)

*   **Layar Blank / *Manifest Not Found*:** Ini terjadi jika Terminal 2 (Vite) mati. Pastikan `npm run dev` selalu berjalan.
*   **Gagal Memuat Ikon (lucide-vue-next):** Jika muncul pesan *Failed to resolve import "lucide-vue-next"*, matikan Terminal Vite (Ctrl+C), jalankan `npm install lucide-vue-next`, lalu jalankan kembali `npm run dev`.
*   **Layar Merah (Error *ts-plugin 1261*):** Sistem operasi yang sensitif terhadap kapitalisasi huruf akan memblokir program jika rute *import* tidak presisi. Pastikan pemanggilan direktori menggunakan huruf besar/kecil yang sama persis dengan penamaan folder di VS Code (contoh yang benar: `import { Button } from '@/Components/ui/button'` dengan huruf **C** kapital).
