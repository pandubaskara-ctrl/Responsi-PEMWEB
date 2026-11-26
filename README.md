# 🌿 Pantrio: Smart Pantry & Meal Planner

> **"Use What You Have, Waste Less."**

**Pantrio** adalah aplikasi web manajemen inventaris dapur, pemantauan kadaluwarsa, rekomendasi resep otomatis, dan pelacakan *food waste* yang mendukung **SDG 12: Responsible Consumption and Production**.

Proyek ini merupakan hasil **Responsi Praktikum Pemrograman Web ** Jurusan Informatika, Fakultas Teknik, Universitas Jenderal Soedirman.

---

## 📖 Tentang Proyek

Food waste merupakan masalah global yang signifikan. Di Indonesia, masalah ini diperparah dengan kurangnya alat praktis bagi rumah tangga untuk mengelola inventaris dapur dan memantau tanggal kedaluwarsa. **Pantrio** hadir sebagai solusi terintegrasi dengan tiga pilar utama:

- **Manage:** Catat stok & pantau tanggal kadaluwarsa.
- **Plan:** Rencanakan menu & daftar belanja cerdas.
- **Reduce Waste:** Evaluasi & tekan limbah makanan harian.

Aplikasi ini dibangun menggunakan **PHP** dan **MySQL** dengan antarmuka yang responsif, serta menerapkan *Role-Based Access Control* (Admin & User).

---

## ✨ Fitur Utama

1. **Authentication:** Register, Login, Role Management, dan Profil.
2. **Inventory Bahan (My Pantry):** CRUD stok bahan makanan (pakai/tambah stok) dengan pengelompokan lokasi simpan.
3. **Expiry Reminder:** Notifikasi otomatis dan status segar hingga kadaluarsa untuk meminimalkan pemborosan (Prinsip FIFO).
4. **Rekomendasi Resep:** Mencocokkan resep dari database dengan stok yang tersedia di pantry (Match Rate).
5. **Meal Planner & Shopping List:** Penjadwalan menu mingguan dan daftar belanja otomatis untuk bahan yang kurang.
6. **Food Waste Tracker:** Pencatatan limbah makanan dan visualisasi grafik statistik.
7. **Statistik Dashboard:** Pantry Health Score dan grafik konsumsi pribadi.
8. **Blog Edukasi:** Artikel tips menyimpan makanan dan gaya hidup berkelanjutan.
9. **Admin Panel:** Manajemen pengguna, master kategori/bahan, katalog resep, dan statistik agregat food waste.

---

## 🛠️ Teknologi yang Digunakan

- **Backend:** PHP
- **Database:** MySQL
- **Frontend:** HTML5, CSS3, JavaScript (Responsive UI)
- **Tools:** Git, GitHub, XAMPP/Laragon (Local Server)

---

## 🚀 Panduan Instalasi (Clone & Run)

Ikuti langkah-langkah di bawah ini untuk menjalankan proyek Pantrio di komputer lokalmu.

### 1. Prasyarat

Pastikan kamu sudah menginstall:

- Web Server (XAMPP / Laragon / MAMP)
- Git (untuk cloning)
- Browser modern (Chrome/Firefox/Edge)

### 2. Clone Repository

Buka terminal atau Command Prompt, lalu jalankan perintah:

```bash
git clone https://github.com/pandubaskara-ctrl/Responsi-PEMWEB.git
```

### 3. Unduh Database

Kami telah menyediakan file `.sql` yang siap diimpor. Silakan unduh database melalui link Google Drive berikut:

> 📥 **[DOWNLOAD DATABASE PANTRIO (Google Drive)](https://drive.google.com/drive/folders/1PNeWWqmlaWkFvNy1ipK74438lkcuAHkw?usp=sharing)**
>

### 4. Setup Database & Konfigurasi

1. Pindahkan folder `Responsi-PEMWEB` hasil clone ke dalam direktori `htdocs` (XAMPP) atau `www` (Laragon).
2. Buka **phpMyAdmin** (`http://localhost/phpmyadmin`).
3. Buat database baru dengan nama `pantrio_db`.
4. Pilih database tersebut, lalu klik tab **Import** dan unggah file `.sql` yang sudah kamu download dari Google Drive tadi.
5. Buka file `config/database.php` (atau sesuaikan dengan struktur project) dan pastikan konfigurasi berikut sesuai dengan environment-mu:

```php
$host = "localhost";
$user = "root";
$pass = ""; // Kosongkan jika menggunakan XAMPP default
$db   = "pantrio_db";
```

### 5. Jalankan Aplikasi

Buka browser dan akses:

```
http://localhost/Responsi-PEMWEB
```

### 6. Akun Demo

Untuk mencoba aplikasi, gunakan akun demo berikut (SUDAH TERTERA JUGA DI HALAMAN LOGIN):

| Role  | Email                | Password   |
| :---- | :------------------- | :--------- |
| User  | `user@pantrio.test`  | `password` |
| Admin | `admin@pantrio.test` | `password` |

---

## 👥 Tim Pengembang (Kelompok 2)
```

Nama                        | NIM       | Peran
:-------------------------- | :-------- | :----------------------------------------
Al Triaza Muhammad          | H1D025138 | Food Management & Recommendation Feature
Pandu Hendra Baskara        | H1D025139 | Tracking, Analytic & Admin Management
Fildza Nazhifa              | H1D025148 | Authentication, Inventory & Database

```

---

## 🎯 Kontribusi terhadap SDG 12
```

Pantrio mendukung agenda global SDG 12: Responsible Consumption and Production
pada tingkat rumah tangga melalui:

- Target 12.3: Menekan jumlah makanan terbuang di tingkat konsumen melalui
  fitur notifikasi kadaluarsa dan rekomendasi olahan resep dari bahan sisa.
- Target 12.5: Mendorong perilaku belanja terencana melalui Shopping List
  dan Meal Planner mingguan.
- Target 12.8: Menyediakan informasi edukatif pada Educational Blog serta
  analitik food waste.

```


*Dibuat dengan ❤️ untuk mengurangi food waste di Indonesia.*
