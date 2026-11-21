<?php
require_once __DIR__ . '/../config/database.php';
$u = require_login('user');
$uid = $u['id'];
if (is_post()) {
  if (($_POST['action'] ?? '') === 'read_all') $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$uid]);
  elseif (($_POST['action'] ?? '') === 'read') $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=? AND notification_id=?")->execute([$uid, (int)$_POST['id']]);
  redirect('user/expiration.php');
}
sync_notifications($uid);
$s = $pdo->prepare("SELECT * FROM food_items WHERE user_id=? AND quantity>0 AND expiry_date<=DATE_ADD(CURDATE(),INTERVAL 3 DAY) ORDER BY expiry_date");
$s->execute([$uid]);
$alerts = $s->fetchAll();
$groups = ['Expired' => [], 'Expires Today' => [], 'Expiring Soon' => []];
foreach ($alerts as $f) $groups[expiry_info($f['expiry_date'])[0]][] = $f;
$n = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY is_read, notification_id DESC LIMIT 20");
$n->execute([$uid]);
$notifs = $n->fetchAll();
$pageTitle = 'Expiration Alerts';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Expiration Alerts</h1>
    <p>Bahan yang perlu segera digunakan, diurutkan berdasarkan prioritas.</p>
  </div>
</div>
<div class="grid g-main">
  <div class="card">
    <h2>Prioritas penggunaan</h2>
    <?php if (!$alerts): ?><div class="empty"><b>✅</b>Semua bahan masih aman. Kerja bagus!</div><?php endif; ?>
    <?php foreach ($groups as $label => $rows): if (!$rows) continue; ?>
      <h3 class="mt mb"><?= $label === 'Expired' ? '🔴' : '🟠' ?> <?= $label ?> (<?= count($rows) ?>)</h3>
      <ul class="list"><?php foreach ($rows as $f): [$l, $cls, $d] = expiry_info($f['expiry_date']); ?>
          <li>
            <div><span class="name"><?= e($f['food_name']) ?></span>
              <div class="muted small"><?= num($f['quantity']) . ' ' . e($f['unit']) ?> ·
                <?= fmt_date($f['expiry_date']) ?> (<?= days_text($d) ?>)</div>
            </div>
            <div class="actions"><?= badge($l, $cls) ?>
              <?php if ($label === 'Expired'): ?><a class="btn btn-ghost btn-sm"
                  href="<?= url('user/food-waste.php?food=' . $f['food_id']) ?>">Catat waste</a>
              <?php else: ?><a class="btn btn-ghost btn-sm" href="<?= url('user/recipes.php') ?>">Cari
                  resep</a><?php endif; ?></div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endforeach; ?>
  </div>
  <div class="card">
    <div class="row between">
      <h2>Notifikasi</h2>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="read_all"><button
          class="btn btn-ghost btn-sm">Tandai semua dibaca</button></form>
    </div>
    <?php if (!$notifs): ?><div class="empty"><b>🔔</b>Belum ada notifikasi.</div><?php else: ?><ul class="list">
        <?php foreach ($notifs as $x): ?>
          <li style="<?= $x['is_read'] ? 'opacity:.55' : '' ?>">
            <div><span class="small"><?= e($x['message']) ?></span>
              <div class="muted small"><?= date('d M, H:i', strtotime($x['created_at'])) ?></div>
            </div>
            <?php if (!$x['is_read']): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action"
                  value="read"><input type="hidden" name="id" value="<?= $x['notification_id'] ?>"><button
                  class="btn btn-ghost btn-sm">✓</button></form><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul><?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>