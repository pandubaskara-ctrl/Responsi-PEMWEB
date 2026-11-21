<?php
require_once __DIR__ . '/../config/database.php';
$u = require_login('admin');
$cnt = fn($t, $w = '') => (int)$pdo->query("SELECT COUNT(*) FROM $t $w")->fetchColumn();
$stats = [['👥', $cnt('users', "WHERE role='user'"), 'Total Users'], ['🍳', $cnt('recipes'), 'Total Recipes'], ['🏷️', $cnt('categories'), 'Food Categories'], ['📰', $cnt('articles'), 'Total Articles']];
$monthly = monthly_waste();
$reasons = $pdo->query("SELECT waste_reason, COUNT(*) c FROM food_waste GROUP BY waste_reason ORDER BY c DESC")->fetchAll(PDO::FETCH_KEY_PAIR);
$newUsers = $pdo->query("SELECT name,email,created_at FROM users WHERE role='user' ORDER BY user_id DESC LIMIT 5")->fetchAll();
$pageTitle = 'Admin Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Admin Dashboard</h1>
    <p>Ringkasan aplikasi Pantrio. Data pantry pribadi pengguna tidak ditampilkan.</p>
  </div>
</div>
<div class="grid g4 mb"><?php foreach ($stats as [$i, $n, $l]): ?><div class="card stat">
      <div class="ico"><?= $i ?></div><span class="num"><?= $n ?></span><span class="lbl"><?= $l ?></span>
    </div><?php endforeach; ?></div>
<div class="grid g2 mb">
  <div class="card">
    <h2>Food waste keseluruhan per bulan</h2><?= bar_chart($monthly) ?>
  </div>
  <div class="card">
    <h2>Alasan food waste</h2><?= hbars($reasons) ?>
  </div>
</div>
<div class="card">
  <h2>Pengguna terbaru</h2>
  <?php if (!$newUsers): ?><div class="empty">Belum ada pengguna.</div><?php else: ?><ul class="list">
      <?php foreach ($newUsers as $x): ?><li>
          <div><span class="name"><?= e($x['name']) ?></span>
            <div class="muted small"><?= e($x['email']) ?></div>
          </div><span class="muted small"><?= fmt_date($x['created_at']) ?></span>
        </li><?php endforeach; ?></ul><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>