<?php
require_once __DIR__ . '/../config/database.php';
$u = require_login('user');
$uid = $u['id'];
$id = (int)($_GET['id'] ?? 0);
$r = $pdo->prepare("SELECT * FROM recipes WHERE recipe_id=?");
$r->execute([$id]);
$recipe = $r->fetch();
if (!$recipe) {
  flash('Resep tidak ditemukan.', 'danger');
  redirect('user/recipes.php');
}
$ing = $pdo->prepare("SELECT ri.*, i.ingredient_name FROM recipe_ingredients ri JOIN ingredients i ON i.ingredient_id=ri.ingredient_id WHERE ri.recipe_id=? ORDER BY i.ingredient_name");
$ing->execute([$id]);
$ings = $ing->fetchAll();
$o = $pdo->prepare("SELECT ingredient_id, SUM(quantity) FROM food_items WHERE user_id=? AND quantity>0 AND ingredient_id IS NOT NULL GROUP BY ingredient_id");
$o->execute([$uid]);
$owned = $o->fetchAll(PDO::FETCH_KEY_PAIR);

if (is_post()) {
  $act = $_POST['action'] ?? '';
  if ($act === 'fav') {
    $c = $pdo->prepare("SELECT favorite_id FROM recipe_favorites WHERE user_id=? AND recipe_id=?");
    $c->execute([$uid, $id]);
    if ($c->fetchColumn()) {
      $pdo->prepare("DELETE FROM recipe_favorites WHERE user_id=? AND recipe_id=?")->execute([$uid, $id]);
      flash('Dihapus dari favorit.');
    } else {
      $pdo->prepare("INSERT INTO recipe_favorites(user_id,recipe_id) VALUES(?,?)")->execute([$uid, $id]);
      flash('Disimpan ke favorit ♥');
    }
  } elseif ($act === 'shop') {
    $list = default_list_id($uid);
    $added = 0;
    $chk = $pdo->prepare("SELECT 1 FROM shopping_list_items WHERE list_id=? AND LOWER(item_name)=LOWER(?) AND is_purchased=0");
    $ins = $pdo->prepare("INSERT INTO shopping_list_items(list_id,item_name,quantity,unit) VALUES(?,?,?,?)");
    foreach ($ings as $x) {
      if (isset($owned[$x['ingredient_id']])) continue;
      $chk->execute([$list, $x['ingredient_name']]);
      if (!$chk->fetchColumn()) {
        $ins->execute([$list, $x['ingredient_name'], $x['quantity_needed'], $x['unit']]);
        $added++;
      }
    }
    log_activity("Menambahkan bahan resep {$recipe['recipe_name']} ke shopping list");
    flash($added ? "$added bahan ditambahkan ke Shopping List." : 'Tidak ada bahan baru yang perlu ditambahkan.');
  } elseif ($act === 'plan') {
    $date = $_POST['plan_date'] ?? '';
    $meal = $_POST['meal_type'] ?? '';
    if (!strtotime($date) || !in_array($meal, MEAL_TYPES)) flash('Tanggal dan waktu makan tidak valid.', 'danger');
    else {
      $pdo->prepare("INSERT INTO meal_plans(user_id,recipe_id,plan_date,meal_type) VALUES(?,?,?,?)")->execute([$uid, $id, $date, $meal]);
      log_activity("Menjadwalkan {$recipe['recipe_name']} ($meal, " . fmt_date($date) . ')');
      flash('Resep dijadwalkan di Meal Planner.');
    }
  } elseif ($act === 'cook') {
    // Kurangi stok dari bahan yang paling dekat kedaluwarsa (asumsi satuan sama)
    $pdo->beginTransaction();
    $sel = $pdo->prepare("SELECT food_id, quantity FROM food_items WHERE user_id=? AND ingredient_id=? AND quantity>0 ORDER BY expiry_date");
    $upd = $pdo->prepare("UPDATE food_items SET quantity=? WHERE food_id=? AND user_id=?");
    foreach ($ings as $x) {
      $need = (float)$x['quantity_needed'];
      $sel->execute([$uid, $x['ingredient_id']]);
      foreach ($sel->fetchAll() as $row) {
        if ($need <= 0) break;
        $take = min($need, (float)$row['quantity']);
        $upd->execute([$row['quantity'] - $take, $row['food_id'], $uid]);
        $need -= $take;
      }
    }
    $pdo->commit();
    log_activity("Memasak {$recipe['recipe_name']} (stok dikurangi)");
    flash('Selamat memasak! Stok pantry sudah dikurangi sesuai kebutuhan resep. 🍳');
  }
  redirect('user/recipe-detail.php?id=' . $id);
}
$isFav = $pdo->prepare("SELECT 1 FROM recipe_favorites WHERE user_id=? AND recipe_id=?");
$isFav->execute([$uid, $id]);
$isFav = (bool)$isFav->fetchColumn();
$have = count(array_filter($ings, fn($x) => isset($owned[$x['ingredient_id']])));
$total = count($ings);
$pct = $total ? (int)round($have / $total * 100) : 0;
$steps = array_filter(array_map('trim', preg_split('/\R/', (string)$recipe['instructions'])));
$pageTitle = $recipe['recipe_name'];
$active = 'recipes.php';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div><span class="chip"><?= e($recipe['category']) ?></span>
    <h1 style="margin-top:6px"><?= e($recipe['recipe_name']) ?></h1>
    <p>⏱ <?= (int)$recipe['cooking_time'] ?> menit · <?= $have ?>/<?= $total ?> bahan tersedia (<b><?= $pct ?>%</b>)
    </p>
  </div>
  <div class="row"><a class="btn btn-ghost" href="<?= url('user/recipes.php') ?>">← Kembali</a>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="fav"><button
        class="btn btn-ghost"><?= $isFav ? '♥ Favorit' : '♡ Simpan favorit' ?></button></form>
  </div>
</div>
<div class="hero-img"><?= $recipe['image_url'] ? '<img src="' . e(url($recipe['image_url'])) . '" alt="">' : '🍳' ?>
</div>
<div class="grid g-main">
  <div>
    <div class="card mb">
      <h2>Deskripsi</h2>
      <p><?= nl2br(e($recipe['description'])) ?></p>
    </div>
    <div class="card">
      <h2>Langkah memasak</h2><?php if (!$steps): ?><p class="muted">Belum ada langkah.</p><?php else: ?><ol
          class="steps"><?php foreach ($steps as $s) echo '<li>' . e($s) . '</li>'; ?></ol><?php endif; ?>
    </div>
  </div>
  <div>
    <div class="card mb">
      <h2>Bahan-bahan</h2>
      <div class="progress"><i style="width:<?= $pct ?>%"></i></div>
      <ul class="list ing-list"><?php foreach ($ings as $x): $ok = isset($owned[$x['ingredient_id']]); ?>
          <li><span><?= $ok ? '<span class="ok">✓</span>' : '<span class="no">✗</span>' ?>
              <?= e($x['ingredient_name']) ?></span><span
              class="muted small"><?= num($x['quantity_needed']) . ' ' . e($x['unit']) ?></span></li>
        <?php endforeach; ?>
      </ul>
      <div class="row mt">
        <?php if ($have < $total): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action"
              value="shop"><button class="btn btn-secondary btn-sm">🛒 Tambah bahan kurang ke belanja</button>
          </form><?php endif; ?>
        <?php if ($have > 0): ?><form method="post" data-confirm="Kurangi stok pantry sesuai resep ini?">
            <?= csrf_field() ?><input type="hidden" name="action" value="cook"><button class="btn btn-sm">🍳
              Masak sekarang</button></form><?php endif; ?>
      </div>
    </div>
    <div class="card">
      <h2>Jadwalkan menu</h2>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="plan">
        <div class="field"><label>Tanggal</label><input type="date" name="plan_date"
            value="<?= date('Y-m-d') ?>" required></div>
        <div class="field"><label>Waktu makan</label><select
            name="meal_type"><?php foreach (MEAL_TYPES as $m) echo "<option>$m</option>"; ?></select></div>
        <button class="btn btn-block">Tambah ke Meal Planner</button>
      </form>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>