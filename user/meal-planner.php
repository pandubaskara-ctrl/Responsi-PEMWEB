<?php
require_once __DIR__ . '/../config/database.php';
$u = require_login('user');
$uid = $u['id'];
$week = $_GET['week'] ?? date('Y-m-d');
$start = new DateTime(strtotime($week) ? $week : 'today');
$start->modify('monday this week');
$weekParam = $start->format('Y-m-d');
if (is_post()) {
  $act = $_POST['action'] ?? '';
  if ($act === 'add') {
    $rid = (int)$_POST['recipe_id'];
    $date = $_POST['plan_date'] ?? '';
    $meal = $_POST['meal_type'] ?? '';
    $ex = $pdo->prepare("SELECT 1 FROM recipes WHERE recipe_id=?");
    $ex->execute([$rid]);
    if (!$ex->fetchColumn() || !strtotime($date) || !in_array($meal, MEAL_TYPES)) flash('Data jadwal tidak valid.', 'danger');
    else {
      $pdo->prepare("INSERT INTO meal_plans(user_id,recipe_id,plan_date,meal_type) VALUES(?,?,?,?)")->execute([$uid, $rid, $date, $meal]);
      log_activity("Menjadwalkan menu $meal " . fmt_date($date));
      flash('Menu dijadwalkan.');
    }
  } elseif ($act === 'delete') {
    $pdo->prepare("DELETE FROM meal_plans WHERE plan_id=? AND user_id=?")->execute([(int)$_POST['plan_id'], $uid]);
    flash('Jadwal dihapus.');
  }
  redirect('user/meal-planner.php?week=' . $weekParam);
}
$end = (clone $start)->modify('+6 day');
$s = $pdo->prepare("SELECT m.*, r.recipe_name FROM meal_plans m JOIN recipes r ON r.recipe_id=m.recipe_id WHERE m.user_id=? AND m.plan_date BETWEEN ? AND ? ORDER BY m.plan_date");
$s->execute([$uid, $start->format('Y-m-d'), $end->format('Y-m-d')]);
$plans = [];
foreach ($s as $p) $plans[$p['plan_date']][$p['meal_type']][] = $p;
$recipes = $pdo->query("SELECT recipe_id, recipe_name FROM recipes ORDER BY recipe_name")->fetchAll();
$prev = (clone $start)->modify('-7 day')->format('Y-m-d');
$next = (clone $start)->modify('+7 day')->format('Y-m-d');
$dayNames = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
$pageTitle = 'Meal Planner';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Meal Planner</h1>
    <p><?= $start->format('d M') ?> – <?= $end->format('d M Y') ?></p>
  </div>
  <div class="row"><a class="btn btn-ghost btn-sm" href="?week=<?= $prev ?>">← Minggu lalu</a><a
      class="btn btn-ghost btn-sm" href="?week=<?= date('Y-m-d') ?>">Minggu ini</a><a class="btn btn-ghost btn-sm"
      href="?week=<?= $next ?>">Minggu depan →</a></div>
</div>
<div class="card mb">
  <h2>Tambah menu</h2>
  <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="add">
    <select name="recipe_id" required>
      <option value="">Pilih resep…</option>
      <?php foreach ($recipes as $r) echo '<option value="' . $r['recipe_id'] . '">' . e($r['recipe_name']) . '</option>'; ?>
    </select>
    <input type="date" name="plan_date" value="<?= date('Y-m-d') ?>" required>
    <select name="meal_type"><?php foreach (MEAL_TYPES as $m) echo "<option>$m</option>"; ?></select>
    <button class="btn">+ Jadwalkan</button>
  </form>
</div>
<div class="week-grid">
  <?php for ($i = 0; $i < 7; $i++): $d = (clone $start)->modify("+$i day");
    $key = $d->format('Y-m-d'); ?>
    <div class="day <?= $key === date('Y-m-d') ? 'today' : '' ?>">
      <h4><?= $dayNames[$i] ?><small><?= $d->format('d M') ?></small></h4>
      <?php foreach (MEAL_TYPES as $m): ?><div class="slot"><?= $m ?></div>
        <?php foreach ($plans[$key][$m] ?? [] as $p): ?>
          <div class="plan"><a
              href="<?= url('user/recipe-detail.php?id=' . $p['recipe_id']) ?>"><?= e($p['recipe_name']) ?></a>
            <form method="post" data-confirm="Hapus jadwal ini?"><?= csrf_field() ?><input type="hidden" name="action"
                value="delete"><input type="hidden" name="plan_id" value="<?= $p['plan_id'] ?>"><button
                title="Hapus">×</button></form>
          </div>
      <?php endforeach;
      endforeach; ?>
    </div>
  <?php endfor; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>