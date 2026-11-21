<?php
require_once __DIR__ . '/config/database.php';
if (auth()) redirect(home_for(auth()['role']));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pantrio — Smart Pantry & Meal Planner</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>

<body>
    <nav class="land-nav">
        <div class="brand">pantrio<span>.</span></div>
        <div class="row"><a class="btn btn-ghost" href="<?= url('auth/login.php') ?>">Login</a><a class="btn"
                href="<?= url('auth/register.php') ?>">Get Started</a></div>
    </nav>

    <section class="hero">
        <div>
            <span class="pill">SDG 12 · RESPONSIBLE CONSUMPTION</span>
            <h1>Use What You Have, <em>Waste Less.</em></h1>
            <p class="lead">Pantrio membantu kamu mengelola stok bahan makanan, memantau tanggal kedaluwarsa, menemukan
                resep dari bahan yang ada, dan mencatat makanan yang terbuang.</p>
            <div class="row"><a class="btn" href="<?= url('auth/register.php') ?>">Mulai Gratis</a><a
                    class="btn btn-ghost" href="#fitur">Lihat Fitur</a></div>
        </div>
        <div class="mock">
            <p class="muted small" style="letter-spacing:1.5px;font-weight:600"><?= strtoupper(date('l, j F Y')) ?></p>
            <h3>Good morning! 🌿</h3>
            <div class="mini">
                <div><b>24</b><span class="muted small">Total Food Items</span></div>
                <div><b>3</b><span class="muted small">Expiring Soon</span></div>
            </div>
            <ul class="list">
                <li><span class="name">Fresh milk</span><?= badge('Tomorrow', 'warning') ?></li>
                <li><span class="name">Bread</span><?= badge('Today', 'danger') ?></li>
                <li><span class="name">Vegetable Omelette</span><?= badge('100% match', 'success') ?></li>
            </ul>
        </div>
    </section>

    <section class="section alt" id="fitur">
        <h2 class="title">Manage · Plan · Reduce Waste</h2>
        <p class="sub">Tiga fokus utama Pantrio dalam satu platform yang hangat dan sederhana.</p>
        <div class="grid g3">
            <?php foreach ([
      ['🥕','Food Inventory','Catat stok, jumlah, lokasi simpan, dan tanggal kedaluwarsa.'],
      ['⏰','Expiration Reminder','Peringatan otomatis untuk bahan yang segera atau sudah kedaluwarsa.'],
      ['🍳','Recipe Recommendation','Resep dicocokkan dengan bahan di pantry-mu, urut dari yang paling cocok.'],
      ['🗓️','Meal Planner','Susun menu harian dan mingguan dari resep favoritmu.'],
      ['🛒','Shopping List','Bahan yang kurang langsung masuk daftar belanja.'],
      ['📊','Waste Statistics','Pantau tren makanan terbuang dan temukan kebiasaan yang bisa diperbaiki.'],
    ] as [$i, $t, $d]): ?>
            <div class="card feature"><span class="ico"><?= $i ?></span>
                <h3><?= $t ?></h3>
                <p><?= $d ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="section">
        <h2 class="title">Kenapa Pantrio?</h2>
        <p class="sub">Selaras dengan SDG 12 — Responsible Consumption and Production (target 12.3, 12.5, 12.8).</p>
        <div class="grid g3">
            <div class="card feature">
                <h3>Kurangi food waste</h3>
                <p>Ingat bahan yang hampir kedaluwarsa sebelum terlambat.</p>
            </div>
            <div class="card feature">
                <h3>Belanja lebih cerdas</h3>
                <p>Tidak lagi membeli bahan yang sebenarnya masih ada.</p>
            </div>
            <div class="card feature">
                <h3>Sadar konsumsi</h3>
                <p>Artikel edukasi dan statistik pribadi membangun kebiasaan baru.</p>
            </div>
        </div>
    </section>
    <footer class="footer"><b>pantrio.</b> · Use What You Have, Waste Less.<br><span class="small">Proyek Responsi
            Praktikum Pemrograman Web I · SDG 12</span></footer>
</body>

</html>