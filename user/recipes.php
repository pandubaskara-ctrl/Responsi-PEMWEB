<?php
require_once __DIR__ . '/../config/database.php';
$u = require_login('user');
$uid = $u['id'];
$q = trim($_GET['q'] ?? '');
$cat = trim($_GET['cat'] ?? '');
$onlyFav = isset($_GET['fav']);
$minMatch = (int)($_GET['min'] ?? 0);
$f = $pdo->prepare("SELECT recipe_id FROM recipe_favorites WHERE user_id=?");
$f->execute([$uid]);
$favs = array_flip($f->fetchAll(PDO::FETCH_COLUMN));
$recipes = recipe_matches($uid, $q, $cat);
$recipes = array_values(array_filter($recipes, fn($r) => $r['pct'] >= $minMatch && (!$onlyFav || isset($favs[$r['recipe_id']]))));
$cats = $pdo->query("SELECT DISTINCT category FROM recipes ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$pageTitle = 'Recipe Finder';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <div>
        <h1>Recipe Finder</h1>
        <p>Resep diurutkan dari yang paling cocok dengan stok pantry-mu.</p>
    </div>
</div>
<div class="card mb">
    <form class="filters" method="get" style="margin:0">
        <input type="text" name="q" placeholder="Cari resep…" value="<?= e($q) ?>">
        <select name="cat">
            <option value="">Semua kategori</option><?php foreach ($cats as $c): ?><option
                <?= $cat === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
        </select>
        <select name="min">
            <option value="0">Semua kecocokan</option><?php foreach ([50, 75, 100] as $m): ?><option value="<?= $m ?>"
                <?= $minMatch === $m ? 'selected' : '' ?>>≥ <?= $m ?>%</option><?php endforeach; ?>
        </select>
        <label style="display:flex;align-items:center;gap:6px;margin:0;flex:none"><input type="checkbox" name="fav"
                <?= $onlyFav ? 'checked' : '' ?>> ♥ Favorit</label>
        <button class="btn">Cari</button>
    </form>
</div>
<?php if (!$recipes): ?><div class="card empty"><b>🍽️</b>Tidak ada resep yang cocok dengan filter.</div>
<?php else: ?><div class="recipe-grid"><?php foreach ($recipes as $r) echo recipe_card($r, $favs); ?></div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>