<?php
require_once __DIR__ . '/../config/database.php';
$u = require_login('admin');
$cnt = fn($t, $w = '') => (int)$pdo->query("SELECT COUNT(*) FROM $t $w")->fetchColumn();
$stats = [
  ['👥', $cnt('users', "WHERE role='user'"), 'Total Pengguna'],
  ['🍳', $cnt('recipes'), 'Total Resep'],
  ['🏷️', $cnt('categories'), 'Total Kategori Bahan'],
  ['📰', $cnt('articles'), 'Total Artikel']
];
$totalWaste = $cnt('food_waste');
$monthly = monthly_waste();

$allItems = $pdo->query("SELECT expiry_date FROM food_items WHERE quantity>0")->fetchAll(PDO::FETCH_COLUMN);
$globalStatus = ['fresh' => 0, 'soon' => 0, 'expired' => 0];
foreach ($allItems as $exp) {
  $lbl = expiry_info($exp)[0];
  if ($lbl === 'Fresh') $globalStatus['fresh']++;
  elseif ($lbl === 'Expired') $globalStatus['expired']++;
  else $globalStatus['soon']++;
}

$reasons = $pdo->query("SELECT waste_reason, COUNT(*) c FROM food_waste GROUP BY waste_reason ORDER BY c DESC")->fetchAll(PDO::FETCH_KEY_PAIR);
$newUsers = $pdo->query("SELECT name,email,created_at FROM users WHERE role='user' ORDER BY user_id DESC LIMIT 5")->fetchAll();
$pageTitle = 'Admin Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Admin Dashboard</h1>
    <p>Ringkasan aplikasi Pantrio & statistik food waste keseluruhan.</p>
  </div>
</div>
<div class="grid g4 mb"><?php foreach ($stats as [$i, $n, $l]): ?><div class="card stat">
      <div class="ico"><?= $i ?></div><span class="num"><?= $n ?></span><span class="lbl"><?= $l ?></span>
    </div><?php endforeach; ?></div>

<div class="grid g3 mb">
  <div class="card stat">
    <div class="ico">🗑️</div><span class="num"><?= $totalWaste ?></span><span class="lbl">Total Food Waste Keseluruhan</span>
  </div>
  <div class="card">
    <h2>Food waste per bulan (Bar Chart)</h2>
    <div style="height:220px;position:relative"><canvas id="adminWasteBar"></canvas></div>
  </div>
  <div class="card">
    <h2>Distribusi status bahan (Doughnut)</h2>
    <div style="height:220px;position:relative"><canvas id="adminPantryDoughnut"></canvas></div>
  </div>
</div>

<div class="grid g2 mb">
  <div class="card">
    <h2>Tren food waste keseluruhan (Line Chart)</h2>
    <div style="height:230px;position:relative"><canvas id="adminWasteLine"></canvas></div>
  </div>
  <div class="card">
    <h2>Alasan food waste terbanyak</h2><?= hbars($reasons) ?>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const adminMonths = <?= json_encode(array_keys($monthly)) ?>;
const adminTotals = <?= json_encode(array_values($monthly)) ?>;

new Chart(document.getElementById('adminWasteBar'), {
  type: 'bar',
  data: {
    labels: adminMonths,
    datasets: [{ label: 'Food Waste', data: adminTotals, backgroundColor: '#588157', borderRadius: 8 }]
  },
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
});

new Chart(document.getElementById('adminPantryDoughnut'), {
  type: 'doughnut',
  data: {
    labels: ['Fresh', 'Expiring Soon', 'Expired'],
    datasets: [{
      data: [<?= $globalStatus['fresh'] ?>, <?= $globalStatus['soon'] ?>, <?= $globalStatus['expired'] ?>],
      backgroundColor: ['#588157', '#E9A23B', '#D96C5F'],
      borderWidth: 2,
      borderColor: '#ffffff'
    }]
  },
  options: { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { legend: { position: 'bottom' } } }
});

new Chart(document.getElementById('adminWasteLine'), {
  type: 'line',
  data: {
    labels: adminMonths,
    datasets: [{
      label: 'Tren Waste',
      data: adminTotals,
      borderColor: '#344E41',
      backgroundColor: 'rgba(88, 129, 87, 0.16)',
      fill: true,
      tension: 0.35,
      pointRadius: 5,
      pointBackgroundColor: '#344E41'
    }]
  },
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
