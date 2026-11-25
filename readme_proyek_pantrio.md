# Pantrio — Smart Pantry & Food Waste Management System

**Pantrio** adalah aplikasi berbasis web untuk mengelola inventaris bahan makanan rumah tangga (*smart pantry*), memantau masa kedaluwarsa, merekomendasikan resep berdasarkan stok yang tersedia, melacak catatan makanan terbuang (*food waste*), serta menyajikan analitik dan edukasi penyimpanan makanan.

## 1. Latar Belakang dan Tujuan Proyek

### Latar Belakang

Banyak rumah tangga mengalami pemborosan makanan (*food waste*) akibat kurangnya pencatatan stok bahan di dapur, lupa tanggal kedaluwarsa, serta kebingungan menentukan menu masakan dari bahan yang tersisa. Kondisi ini tidak hanya berdampak pada pemborosan pengeluaran rumah tangga, tetapi juga meningkatkan jumlah limbah organik.

### Tujuan Proyek

* Membantu pengguna mencatat dan memantau ketersediaan serta masa berlaku bahan makanan secara terpusat.

* Mengurangi risiko bahan makanan kedaluwarsa melalui sistem peringatan dini dan rekomendasi resep yang sesuai dengan isi pantry.

* Menyediakan pencatatan dan visualisasi statistik *food waste* sebagai bahan evaluasi pola konsumsi pengguna.

* Memberikan edukasi praktis mengenai cara penyimpanan bahan makanan yang tepat agar lebih tahan lama.

## 2. Daftar Fitur dan Modul yang Diimplementasikan

Aplikasi ini terbagi menjadi dua hak akses utama (**User** dan **Admin**) dengan 9 modul utama yang saling terintegrasi:

1. **Modul 1 — Authentication & Role Management**

   * Registrasi pengguna baru, login, logout, pengelolaan profil pengguna, serta pemisahan hak akses antara `user` dan `admin`.

2. **Modul 2 — Pantry Inventory Management**

   * Pencatatan stok bahan makanan (nama, kategori, jumlah, satuan, lokasi penyimpanan, dan tanggal kedaluwarsa), pencarian, filter status, serta pembaruan stok cepat.

3. **Modul 3 — Expiration Alert & Storage Location**

   * Deteksi otomatis status bahan (*Fresh*, *Expiring Soon*, *Expired*), penghitungan *Pantry Health Score*, serta manajemen lokasi penyimpanan (Kulkas, Freezer, Rak Dapur, dll.).

4. **Modul 4 — Recipe Finder & Ingredient Matching**

   * Pencarian resep masakan beserta kalkulasi otomatis persentase kecocokan bahan yang dimiliki pengguna di dalam pantry.

5. **Modul 5 — Meal Planner**

   * Perencanaan jadwal makan mingguan (Sarapan, Makan Siang, Makan Malam) yang terhubung dengan daftar resep.

6. **Modul 6 — Smart Shopping List**

   * Daftar belanja otomatis dari bahan resep yang belum tersedia di pantry maupun penambahan manual, dilengkapi status centang pembelian.

7. **Modul 7 — Food Waste Tracker**

   * Pencatatan bahan makanan yang terbuang (dari stok pantry maupun manual) berdasarkan kategori alasan (*Expired*, *Spoiled*, *Excessive Purchase*, *Leftovers*, *Other*) yang otomatis mengurangi stok pantry dan terekam di *activity logs*.

8. **Modul 8 — Statistics & Dashboard (User & Admin)**

   * Ringkasan metrik dapur secara *real-time*, riwayat aktivitas terbaru (*activity logs*), serta grafik interaktif dinamis menggunakan **Chart.js** (*Bar Chart* food waste bulanan, *Doughnut Chart* distribusi status bahan, dan *Line Chart* tren food waste).

9. **Modul 9 — Educational Blog & Storage Tips**

   * Artikel edukasi penyimpanan makanan dengan fitur pencarian dan filter kategori untuk pengguna, serta manajemen CRUD artikel, unggah gambar, dan kategori artikel untuk administrator.

## 3. Teknologi yang Digunakan

* **Backend:** PHP 8.x (Native Procedural & Prepared Statements)

* **Database:** MySQL / MariaDB

* **Frontend:** HTML5, CSS3 (Custom Responsive Design), JavaScript (Vanilla JS)

* **Data Visualization:** Chart.js (CDN)

* **Web Server & Environment:** Apache (XAMPP)

*(Catatan: File sistem bawaan macOS seperti `.DS_Store` dan direktori `__MACOSX/` dapat diabaikan atau dihapus saat ekstraksi).*

## 4. Panduan Instalasi dan Konfigurasi

1. **Clone atau Ekstrak Proyek**

   * Salin seluruh folder proyek ke dalam direktori `htdocs` pada instalasi XAMPP Anda, misalnya:

```text
C:\xampp\htdocs\pantrio
```

2. **Buat dan Impor Database**

   * Jalankan layanan **Apache** dan **MySQL** melalui **XAMPP Control Panel**.

   * Buka `http://localhost/phpmyadmin` di browser.

   * Buat database baru dengan nama `pantrio`.

   * Pilih database `pantrio`, masuk ke tab **Import**, lalu pilih file `pantrio.sql` dan klik **Go / Import**.

3. **Konfigurasi Koneksi Database & Base URL**

   * Buka file `config/database.php` dan pastikan pengaturan berikut sesuai dengan lingkungan lokal Anda:

```php
define('BASE_URL', '/pantrio');
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pantrio');
```

## 5. Panduan Menjalankan Aplikasi

1. Pastikan server **Apache** dan **MySQL** dalam keadaan aktif (`Running`) di XAMPP.

2. Buka browser (Chrome / Edge / Firefox) dan akses alamat:

```text
http://localhost/pantrio
```

3. Klik tombol **Login** dan masuk menggunakan akun demo yang telah tersedia di database:

| Peran (Role) | Email | Password |
| :--- | :--- | :--- |
| **User** | `user@pantrio.test` | `password` |
| **Admin** | `admin@pantrio.test` | `password` |

4. Setelah masuk, Anda dapat langsung menguji seluruh fitur pengelolaan stok, pencatatan *food waste*, grafik statistik, maupun manajemen artikel pada sisi admin.