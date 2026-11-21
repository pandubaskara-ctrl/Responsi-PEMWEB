<?php
require_once __DIR__ . '/../config/database.php';
$u = require_login('admin');
if (is_post()) {
    $act = $_POST['action'] ?? '';
    $rid = (int)($_POST['recipe_id'] ?? 0);
    if ($act === 'save') {
        $name = trim($_POST['recipe_name'] ?? '');
        $cat = trim($_POST['category'] ?? '');
        $time = max(1, (int)($_POST['cooking_time'] ?? 15));
        if ($name === '' || $cat === '') {
            flash('Nama dan kategori resep wajib diisi.', 'danger');
            redirect('admin/recipes.php' . ($rid ? "?edit=$rid" : '?new=1'));
        }
        $img = upload_image('image', 'recipes');
        if ($rid) {
            $pdo->prepare("UPDATE recipes SET recipe_name=?,category=?,description=?,instructions=?,cooking_time=?,image_url=COALESCE(?,image_url) WHERE recipe_id=?")
                ->execute([$name, $cat, trim($_POST['description']), trim($_POST['instructions']), $time, $img, $rid]);
            flash('Resep diperbarui.');
        } else {
            $pdo->prepare("INSERT INTO recipes(recipe_name,category,description,instructions,cooking_time,image_url) VALUES(?,?,?,?,?,?)")
                ->execute([$name, $cat, trim($_POST['description']), trim($_POST['instructions']), $time, $img]);
            $rid = (int)$pdo->lastInsertId();
            flash('Resep dibuat. Sekarang tambahkan bahan-bahannya.');
        }
        redirect("admin/recipes.php?edit=$rid");
    } elseif ($act === 'delete') {
        $pdo->prepare("DELETE FROM recipes WHERE recipe_id=?")->execute([$rid]);
        flash('Resep dihapus.');
        redirect('admin/recipes.php');
    } elseif ($act === 'add_ing') {
        $n = trim($_POST['ing_name'] ?? '');
        $q = $_POST['qty'] ?? 0;
        $un = trim($_POST['unit'] ?? '');
        if ($n === '' || !is_numeric($q) || $q <= 0 || $un === '') flash('Nama, jumlah (> 0), dan satuan bahan wajib diisi.', 'danger');
        else {
            $iid = get_or_create_ingredient($n, $un);
            $pdo->prepare("INSERT INTO recipe_ingredients(recipe_id,ingredient_id,quantity_needed,unit) VALUES(?,?,?,?)")->execute([$rid, $iid, $q, $un]);
            flash('Bahan ditambahkan.');
        }
        redirect("admin/recipes.php?edit=$rid");
    } elseif ($act === 'del_ing') {
        $pdo->prepare("DELETE FROM recipe_ingredients WHERE recipe_ingredient_id=? AND recipe_id=?")->execute([(int)$_POST['ri_id'], $rid]);
        flash('Bahan dihapus.');
        redirect("admin/recipes.php?edit=$rid");
    }
}
$pageTitle = 'Recipe Management';
$editing = isset($_GET['edit']) || isset($_GET['new']);
$r = null;
$ings = [];
if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM recipes WHERE recipe_id=?");
    $s->execute([(int)$_GET['edit']]);
    $r = $s->fetch();
    if (!$r) redirect('admin/recipes.php');
    $s = $pdo->prepare("SELECT ri.*, i.ingredient_name FROM recipe_ingredients ri JOIN ingredients i ON i.ingredient_id=ri.ingredient_id WHERE ri.recipe_id=?");
    $s->execute([$r['recipe_id']]);
    $ings = $s->fetchAll();
}
$all = $pdo->query("SELECT r.*, (SELECT COUNT(*) FROM recipe_ingredients ri WHERE ri.recipe_id=r.recipe_id) ing FROM recipes r ORDER BY recipe_name")->fetchAll();
require __DIR__ . '/../includes/header.php';
?>
<?php if ($editing): ?>
    <div class="page-head">
        <div>
            <h1><?= $r ? 'Edit Resep' : 'Resep Baru' ?></h1>
        </div><a class="btn btn-ghost" href="<?= url('admin/recipes.php') ?>">← Kembali</a>
    </div>
    <div class="grid g-main">
        <div class="card">
            <form method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="action"
                    value="save"><input type="hidden" name="recipe_id" value="<?= $r['recipe_id'] ?? 0 ?>">
                <div class="form-grid">
                    <div class="field span2"><label>Nama resep</label><input type="text" name="recipe_name"
                            value="<?= e($r['recipe_name'] ?? '') ?>" required></div>
                    <div class="field"><label>Kategori masakan</label><input type="text" name="category" list="rc"
                            value="<?= e($r['category'] ?? '') ?>" required><datalist id="rc">
                            <option>Breakfast</option>
                            <option>Main Course</option>
                            <option>Soup</option>
                            <option>Side Dish</option>
                            <option>Snack</option>
                            <option>Dessert</option>
                        </datalist></div>
                    <div class="field"><label>Waktu memasak (menit)</label><input type="number" name="cooking_time" min="1"
                            value="<?= e($r['cooking_time'] ?? 15) ?>"></div>
                    <div class="field span2"><label>Deskripsi</label><textarea
                            name="description"><?= e($r['description'] ?? '') ?></textarea></div>
                    <div class="field span2"><label>Langkah memasak (satu langkah per baris)</label><textarea
                            name="instructions" style="min-height:160px"><?= e($r['instructions'] ?? '') ?></textarea></div>
                    <div class="field span2"><label>Gambar (jpg/png/webp, maks 2 MB)</label><input type="file" name="image"
                            accept="image/*"><?php if (!empty($r['image_url'])): ?><p class="muted small mt">Gambar saat
                                ini: <?= e($r['image_url']) ?></p><?php endif; ?></div>
                </div><button class="btn">Simpan resep</button>
            </form>
        </div>
        <div class="card">
            <h2>Bahan resep</h2>
            <?php if (!$r): ?><p class="muted">Simpan resep terlebih dahulu untuk menambah bahan.</p><?php else: ?>
                <ul class="list"><?php foreach ($ings as $x): ?><li><span><span
                                    class="name"><?= e($x['ingredient_name']) ?></span> <span
                                    class="muted small"><?= num($x['quantity_needed']) . ' ' . e($x['unit']) ?></span></span>
                            <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="del_ing"><input
                                    type="hidden" name="recipe_id" value="<?= $r['recipe_id'] ?>"><input type="hidden" name="ri_id"
                                    value="<?= $x['recipe_ingredient_id'] ?>"><button class="btn btn-danger btn-sm">×</button>
                            </form>
                        </li><?php endforeach; ?></ul>
                <form method="post" class="mt"><?= csrf_field() ?><input type="hidden" name="action" value="add_ing"><input
                        type="hidden" name="recipe_id" value="<?= $r['recipe_id'] ?>">
                    <div class="field"><label>Nama bahan</label><input type="text" name="ing_name" required
                            placeholder="mis. Telur"></div>
                    <div class="form-grid">
                        <div class="field"><label>Jumlah</label><input type="number" step="any" min="0" name="qty" required>
                        </div>
                        <div class="field"><label>Satuan</label><input type="text" name="unit" required></div>
                    </div>
                    <button class="btn btn-secondary">+ Tambah bahan</button>
                </form><?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="page-head">
        <div>
            <h1>Recipe Management</h1>
            <p><?= count($all) ?> resep tersedia.</p>
        </div><a class="btn" href="?new=1">+ Resep baru</a>
    </div>
    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Resep</th>
                        <th>Kategori</th>
                        <th>Waktu</th>
                        <th>Bahan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all as $x): ?><tr>
                            <td class="name"><?= e($x['recipe_name']) ?></td>
                            <td><span class="chip"><?= e($x['category']) ?></span></td>
                            <td><?= (int)$x['cooking_time'] ?> min</td>
                            <td><?= $x['ing'] ?></td>
                            <td>
                                <div class="actions"><a class="btn btn-ghost btn-sm"
                                        href="?edit=<?= $x['recipe_id'] ?>">Edit</a>
                                    <form method="post" data-confirm="Hapus resep ini?"><?= csrf_field() ?><input type="hidden"
                                            name="action" value="delete"><input type="hidden" name="recipe_id"
                                            value="<?= $x['recipe_id'] ?>"><button class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr><?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>