<?php
require_once __DIR__ . '/../config/database.php';
$u = require_login('user');
$uid = $u['id'];
if (is_post() && ($_POST['action'] ?? '') === 'read') {
  $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=? AND notification_id=?")->execute([$uid, (int)$_POST['id']]);
  redirect('user/dashboard.php');
}
sync_notifications($uid);
$c = pantry_counts($uid);
$health = $c['total'] ? (int)round($c['fresh'] / $c['total'] * 100) : 0;
$wasteCount = $pdo->prepare("SELECT COUNT(*) FROM food_waste WHERE user_id=?");
$wasteCount->execute([$uid]);
$wasteCount = (int)$wasteCount->fetchColumn();
$matches = recipe_matches($uid);
$ready = count(array_filter($matches, fn($r) => $r['pct'] >= 50));
$favs = [];
$soon = $pdo->prepare("SELECT * FROM food_items WHERE user_id=? AND quantity>0 ORDER BY expiry_date LIMIT 5");
$soon->execute([$uid]);
$soon = $soon->fetchAll();
$notifs = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? AND is_read=0 ORDER BY notification_id DESC LIMIT 5");
$notifs->execute([$uid]);
$notifs = $notifs->fetchAll();
$logs = $pdo->prepare("SELECT * FROM activity_logs WHERE user_id=? ORDER BY log_id DESC LIMIT 6");
$logs->execute([$uid]);
$logs = $logs->fetchAll();
$h = (int)date('G');
$greet = $h < 11 ? 'Good morning' : ($h < 15 ? 'Good afternoon' : ($h < 19 ? 'Good evening' : 'Good night'));
$ringColor = $health >= 70 ? '#588157' : ($health >= 40 ? '#E9A23B' : '#D96C5F');
$pageTitle = 'Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h1><?= $greet ?>, <?= e(explode(' ', $u['name'])[0]) ?>! 🌿</h1>
    <p>Let’s make the most of your pantry today.</p>
  </div>
  <a class="btn" href="<?= url('user/food-form.php') ?>">+ Add Food</a>
</div>

<div class="grid g4 mb">
  <div class="card stat">
    <div class="ico">🥕</div><span class="num"><?= $c['total'] ?></span><span class="lbl">Total Food Items</span>
  </div>
  <div class="card stat">
    <div class="ico">⏳</div><span class="num"><?= $c['soon'] ?></span><span class="lbl">Expiring Soon</span>
  </div>
  <div class="card stat">
    <div class="ico">🍳</div><span class="num"><?= $ready ?></span><span class="lbl">Available Recipes</span>
  </div>
  <div class="card stat">
    <div class="ico">🗑️</div><span class="num"><?= $wasteCount ?></span><span class="lbl">Waste Records</span>
  </div>
</div>

<div class="grid g-main mb">
  <div class="card">
    <h2>Expiration Reminder</h2>
    <?php if (!$soon): ?><div class="empty"><b>🌱</b>Pantry masih kosong. Tambahkan bahan pertamamu!</div>
    <?php else: ?>
      <ul class="list"><?php foreach ($soon as $f): [$l, $cls, $d] = expiry_info($f['expiry_date']); ?>
          <li>
            <div><span class="name"><?= e($f['food_name']) ?></span>
              <div class="muted small"><?= num($f['quantity']) . ' ' . e($f['unit']) ?> · <?= days_text($d) ?>
              </div>
            </div><?= badge($l, $cls) ?>
          </li>
        <?php endforeach; ?>
      </ul><?php endif; ?>
    <a class="small" href="<?= url('user/expiration.php') ?>">Lihat semua pengingat →</a>
  </div>
  <div class="card">
    <h2>Pantry Health Score</h2>
    <div class="score">
      <div class="ring" style="background:conic-gradient(<?= $ringColor ?> <?= $health ?>%,#ebe7dc 0)">
        <span><?= $health ?></span>
      </div>
      <p class="muted small"><?= $c['fresh'] ?> dari <?= $c['total'] ?> bahan masih fresh.
        <?= $c['expired'] ? '<b>' . $c['expired'] . ' sudah expired</b> — segera dibuang atau dicatat.' : 'Pertahankan!' ?>
      </p>
    </div>
  </div>
</div>

<div class="grid g-main mb">
  <div class="card">
    <h2>Recommended for You</h2>
    <?php $top = array_slice(array_filter($matches, fn($r) => $r['pct'] > 0), 0, 3);
    if (!$top): ?><div class="empty"><b>🍳</b>Belum ada resep yang cocok.</div><?php else: ?>
      <div class="recipe-grid"><?php foreach ($top as $r) echo recipe_card($r, $favs); ?></div><?php endif; ?>
  </div>
  <div class="card">
    <h2>Notifikasi</h2>
    <?php if (!$notifs): ?><div class="empty"><b>🔔</b>Tidak ada notifikasi baru.</div><?php else: ?>
      <ul class="list"><?php foreach ($notifs as $n): ?>
          <li><span class="small"><?= e($n['message']) ?></span>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="read"><input
                type="hidden" name="id" value="<?= $n['notification_id'] ?>"><button
                class="btn btn-ghost btn-sm">✓</button></form>
          </li>
        <?php endforeach; ?>
      </ul><?php endif; ?>
  </div>
</div>

<div class="card">
  <h2>Riwayat Aktivitas</h2>
  <?php if (!$logs): ?><div class="empty">Belum ada aktivitas.</div><?php else: ?>
    <ul class="list"><?php foreach ($logs as $l): ?><li><span><?= e($l['activity']) ?></span><span
            class="muted small"><?= date('d M, H:i', strtotime($l['created_at'])) ?></span></li><?php endforeach; ?>
    </ul><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>