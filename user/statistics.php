<?php
require_once __DIR__ . '/../config/database.php';
$u = require_login('user');
$uid = $u['id'];
$c = pantry_counts($uid);
$monthly = monthly_waste($uid);
$reasons = $pdo->prepare("SELECT waste_reason, COUNT(*) c FROM food_waste WHERE user_id=? GROUP BY waste_reason ORDER BY c DESC");
$reasons->execute([$uid]);
$reasons = $reasons->fetchAll(PDO::FETCH_KEY_PAIR);
$top = $pdo->prepare("SELECT food_name, COUNT(*) c FROM food_waste WHERE user_id=? GROUP BY food_name ORDER BY c DESC LIMIT 5");
$top->execute([$uid]);
$top = $top->fetchAll(PDO::FETCH_KEY_PAIR);
$total = $pdo->prepare("SELECT COUNT(*) FROM food_waste WHERE user_id=?");
$total->execute([$uid]);
$total = (int)$total->fetchColumn();
$last = $pdo->prepare("SELECT MAX(waste_date) FROM food_waste WHERE user_id=?");
$last->execute([$uid]);
$last = $last->fetchColumn();
$streak = $last ? (int)(new DateTime($last))->diff(new DateTime('today'))->days : null;
$pageTitle = 'My Statistics';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>My Statistics</h1>
    <p>Gambaran pengelolaan stok dan food waste-mu.</p>
  </div>
</div>
<div class="grid g3 mb">
  <div class="card stat">
    <div class="ico">🥕</div><span class="num"><?= $c['total'] ?></span><span class="lbl">Bahan di pantry</span>
  </div>
  <div class="card stat">
    <div class="ico">🗑️</div><span class="num"><?= $total ?></span><span class="lbl">Total catatan waste</span>
  </div>
  <div class="card stat">
    <div class="ico">🏆</div><span class="num"><?= $streak === null ? '∞' : $streak ?></span><span class="lbl">Hari
      tanpa food waste<?= $streak !== null && $streak >= 7 ? ' 🎉' : '' ?></span>
  </div>
</div>
<div class="grid g2 mb">
  <div class="card">
    <h2>Food waste per bulan</h2><?= bar_chart($monthly) ?>
  </div>
  <div class="card">
    <h2>Distribusi status bahan</h2>
    <?= doughnut([['Fresh', $c['fresh'], '#588157'], ['Expiring Soon', $c['soon'], '#E9A23B'], ['Expired', $c['expired'], '#D96C5F']]) ?>
  </div>
</div>
<div class="grid g-main">
  <div class="card">
    <h2>Tren food waste</h2><?= line_chart($monthly) ?>
  </div>
  <div class="card">
    <h2>Alasan terbanyak</h2><?= hbars($reasons) ?><h2 class="mt">Paling sering terbuang</h2><?= hbars($top) ?>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>