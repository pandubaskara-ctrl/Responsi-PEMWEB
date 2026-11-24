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
    <div class="ico">🥕</div><span class="num"><?= $c['total'] ?></span><span class="lbl">Total bahan di pantry</span>
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
    <h2>Food waste per bulan (Bar Chart)</h2>
    <div style="height:260px;position:relative"><canvas id="wasteBarChart"></canvas></div>
  </div>
  <div class="card">
    <h2>Distribusi status bahan (Doughnut Chart)</h2>
    <div style="height:260px;position:relative"><canvas id="pantryDoughnutChart"></canvas></div>
  </div>
</div>
<div class="grid g-main">
  <div class="card">
    <h2>Tren food waste (Line Chart)</h2>
    <div style="height:260px;position:relative"><canvas id="wasteLineChart"></canvas></div>
  </div>
  <div class="card">
    <h2>Alasan terbanyak</h2><?= hbars($reasons) ?><h2 class="mt">Paling sering terbuang</h2><?= hbars($top) ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const monthLabels = <?= json_encode(array_keys($monthly)) ?>;
const monthValues = <?= json_encode(array_values($monthly)) ?>;

new Chart(document.getElementById('wasteBarChart'), {
  type: 'bar',
  data: {
    labels: monthLabels,
    datasets: [{
      label: 'Jumlah Catatan Waste',
      data: monthValues,
      backgroundColor: '#588157',
      borderRadius: 8
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
  }
});

new Chart(document.getElementById('pantryDoughnutChart'), {
  type: 'doughnut',
  data: {
    labels: ['Fresh', 'Expiring Soon', 'Expired'],
    datasets: [{
      data: [<?= (int)$c['fresh'] ?>, <?= (int)$c['soon'] ?>, <?= (int)$c['expired'] ?>],
      backgroundColor: ['#588157', '#E9A23B', '#D96C5F'],
      borderWidth: 2,
      borderColor: '#ffffff'
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '62%',
    plugins: { legend: { position: 'bottom' } }
  }
});

new Chart(document.getElementById('wasteLineChart'), {
  type: 'line',
  data: {
    labels: monthLabels,
    datasets: [{
      label: 'Tren Food Waste',
      data: monthValues,
      borderColor: '#344E41',
      backgroundColor: 'rgba(88, 129, 87, 0.16)',
      fill: true,
      tension: 0.35,
      pointRadius: 5,
      pointBackgroundColor: '#344E41'
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
  }
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
