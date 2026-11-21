<?php
require_once __DIR__ . '/../config/database.php';
$u = require_login('user');
$uid = $u['id'];
$id = (int)($_GET['id'] ?? 0);
$food = null;
if ($id) {
    $s = $pdo->prepare("SELECT * FROM food_items WHERE food_id=? AND user_id=?");
    $s->execute([$id, $uid]);
    $food = $s->fetch();
    if (!$food) {
        flash('Data tidak ditemukan.', 'danger');
        redirect('user/pantry.php');
    }
}
$cats = $pdo->query("SELECT * FROM categories ORDER BY category_name")->fetchAll();
$locs = $pdo->prepare("SELECT * FROM storage_locations WHERE user_id=? ORDER BY storage_name");
$locs->execute([$uid]);
$locs = $locs->fetchAll();
$v = [
    'food_name' => $_GET['name'] ?? '',
    'quantity' => $_GET['qty'] ?? '',
    'unit' => $_GET['unit'] ?? 'pcs',
    'category_id' => '',
    'storage_id' => '',
    'purchase_date' => date('Y-m-d'),
    'expiry_date' => '',
    'notes' => ''
];
if ($food) $v = array_merge($v, $food);
$errors = [];
if (is_post()) {
    $v = array_merge($v, $_POST);
    $name = trim($v['food_name']);
    $qty = $v['quantity'];
    $unit = trim($v['unit']);
    if ($name === '') $errors[] = 'Nama bahan wajib diisi.';
    if (!is_numeric($qty) || $qty < 0) $errors[] = 'Jumlah harus angka ≥ 0.';
    if ($unit === '') $errors[] = 'Satuan wajib diisi.';
    if (!$v['expiry_date'] || !strtotime($v['expiry_date'])) $errors[] = 'Tanggal kedaluwarsa wajib diisi.';
    if (!$errors) {
        $cat = (int)$v['category_id'] ?: null;
        $stor = null;
        if ((int)$v['storage_id']) { // pastikan lokasi milik user ini
            $c = $pdo->prepare("SELECT storage_id FROM storage_locations WHERE storage_id=? AND user_id=?");
            $c->execute([(int)$v['storage_id'], $uid]);
            $stor = $c->fetchColumn() ?: null;
        }
        $ing = get_or_create_ingredient($name, $unit);
        $pur = $v['purchase_date'] ?: null;
        if ($food) {
            $pdo->prepare("UPDATE food_items SET category_id=?,storage_id=?,ingredient_id=?,food_name=?,quantity=?,unit=?,purchase_date=?,expiry_date=?,notes=? WHERE food_id=? AND user_id=?")
                ->execute([$cat, $stor, $ing, $name, $qty, $unit, $pur, $v['expiry_date'], trim($v['notes']), $id, $uid]);
            log_activity("Mengedit $name");
            flash('Bahan diperbarui.');
        } else {
            $pdo->prepare("INSERT INTO food_items(user_id,category_id,storage_id,ingredient_id,food_name,quantity,unit,purchase_date,expiry_date,notes) VALUES(?,?,?,?,?,?,?,?,?,?)")
                ->execute([$uid, $cat, $stor, $ing, $name, $qty, $unit, $pur, $v['expiry_date'], trim($v['notes'])]);
            log_activity('Menambahkan ' . num($qty) . " $unit $name");
            flash('Bahan ditambahkan ke pantry.');
        }
        redirect('user/pantry.php');
    }
}
$pageTitle = $food ? 'Edit Food' : 'Add Food';
$active = 'pantry.php';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <div>
        <h1><?= $food ? 'Edit Food' : 'Add Food' ?></h1>
        <p>Catat detail bahan makanan di pantry-mu.</p>
    </div><a class="btn btn-ghost" href="<?= url('user/pantry.php') ?>">← Kembali</a>
</div>
<div class="card" style="max-width:820px">
    <?php foreach ($errors as $er): ?><div class="flash danger"><?= e($er) ?></div><?php endforeach; ?>
    <form method="post"><?= csrf_field() ?>
        <div class="form-grid">
            <div class="field span2"><label>Nama bahan</label><input type="text" name="food_name"
                    value="<?= e($v['food_name']) ?>" required placeholder="mis. Telur"></div>
            <div class="field"><label>Kategori</label><select name="category_id">
                    <option value="">— Pilih —</option><?php foreach ($cats as $c): ?><option
                            value="<?= $c['category_id'] ?>"
                            <?= $v['category_id'] == $c['category_id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?>
                        </option><?php endforeach; ?>
                </select></div>
            <div class="field"><label>Lokasi penyimpanan</label><select name="storage_id">
                    <option value="">— Pilih —</option><?php foreach ($locs as $l): ?><option
                            value="<?= $l['storage_id'] ?>" <?= $v['storage_id'] == $l['storage_id'] ? 'selected' : '' ?>>
                            <?= e($l['storage_name']) ?></option><?php endforeach; ?>
                </select></div>
            <div class="field"><label>Jumlah</label><input type="number" name="quantity" step="any" min="0"
                    value="<?= e($v['quantity']) ?>" required></div>
            <div class="field"><label>Satuan</label><input type="text" name="unit" list="units"
                    value="<?= e($v['unit']) ?>" required>
                <datalist
                    id="units"><?php foreach (['pcs', 'gram', 'kg', 'ml', 'liter', 'siung', 'cup', 'sdm', 'sdt', 'lembar', 'bungkus'] as $x) echo "<option value=\"$x\">"; ?></datalist>
            </div>
            <div class="field"><label>Tanggal pembelian</label><input type="date" name="purchase_date"
                    value="<?= e($v['purchase_date']) ?>"></div>
            <div class="field"><label>Tanggal kedaluwarsa</label><input type="date" name="expiry_date"
                    value="<?= e($v['expiry_date']) ?>" required></div>
            <div class="field span2"><label>Catatan</label><textarea name="notes"
                    placeholder="Opsional"><?= e($v['notes']) ?></textarea></div>
        </div>
        <button class="btn"><?= $food ? 'Simpan perubahan' : 'Tambah ke pantry' ?></button>
    </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>