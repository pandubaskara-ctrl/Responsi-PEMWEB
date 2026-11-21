<?php
require_once __DIR__ . '/../config/database.php';
$u = require_login('admin');
$monthly = monthly_waste();
$total = (int)$pdo->query("SELECT COUNT(*) FROM food_waste")->fetchColumn();
$users = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM food_waste")->fetchColumn();
$reasons = $pdo->query("SELECT waste_reason, COUNT(*) c FROM food_waste GROUP BY waste_reason ORDER BY c DESC")->fetchAll(PDO::FETCH_KEY_PAIR);
$top = $pdo->query("SELECT food_name, COUNT(*) c FROM food_waste GROUP BY food_name ORDER BY c DESC LIMIT 8")->fetchAll(PDO::FETCH_KEY_PAIR);
$pageTitle = 'Waste Statistics';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <div>
        <h1>Waste Statistics</h1>
        <p>Statistik food waste seluruh pengguna (agregat, tanpa data pribadi).</p>
    </div>
</div>
<div class="grid g3 mb">
    <div class="card stat">
        <div class="ico">🗑️</div><span class="num"><?= $total ?></span><span class="lbl">Total catatan waste</span>
    </div>
    <div class="card stat">
        <div class="ico">👥</div><span class="num"><?= $users ?></span><span class="lbl">Pengguna yang mencatat</span>
    </div>
    <div class="card stat">
        <div class="ico">📈</div><span class="num"><?= $users ? round($total / $users, 1) : 0 ?></span><span
            class="lbl">Rata-rata per pengguna</span>
    </div>
</div>
<div class="grid g2 mb">
    <div class="card">
        <h2>Per bulan</h2><?= bar_chart($monthly) ?>
    </div>
    <div class="card">
        <h2>Tren</h2><?= line_chart($monthly) ?>
    </div>
</div>
<div class="grid g2">
    <div class="card">
        <h2>Alasan terbanyak</h2><?= hbars($reasons) ?>
    </div>
    <div class="card">
        <h2>Bahan paling sering terbuang</h2><?= hbars($top) ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>