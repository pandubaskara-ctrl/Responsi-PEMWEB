<?php
require_once __DIR__ . '/../config/database.php';
$u = require_login('user');
$uid = $u['id'];
$list = default_list_id($uid);
if (is_post()) {
  $act = $_POST['action'] ?? '';
  $iid = (int)($_POST['item_id'] ?? 0);
  if ($act === 'save') {
    $name = trim($_POST['item_name'] ?? '');
    $qty = $_POST['quantity'] ?? 1;
    $unit = trim($_POST['unit'] ?? '') ?: 'pcs';
    if ($name === '' || !is_numeric($qty) || $qty <= 0) flash('Nama dan jumlah (> 0) wajib diisi.', 'danger');
    elseif ($iid) {
      $pdo->prepare("UPDATE shopping_list_items SET item_name=?,quantity=?,unit=? WHERE item_id=? AND list_id=?")->execute([$name, $qty, $unit, $iid, $list]);
      flash('Barang diperbarui.');
    } else {
      $pdo->prepare("INSERT INTO shopping_list_items(list_id,item_name,quantity,unit) VALUES(?,?,?,?)")->execute([$list, $name, $qty, $unit]);
      flash('Barang ditambahkan.');
    }
  } elseif ($act === 'toggle') {
    $pdo->prepare("UPDATE shopping_list_items SET is_purchased=1-is_purchased WHERE item_id=? AND list_id=?")->execute([$iid, $list]);
  } elseif ($act === 'delete') {
    $pdo->prepare("DELETE FROM shopping_list_items WHERE item_id=? AND list_id=?")->execute([$iid, $list]);
    flash('Barang dihapus.');
  } elseif ($act === 'clear') {
    $pdo->prepare("DELETE FROM shopping_list_items WHERE list_id=? AND is_purchased=1")->execute([$list]);
    flash('Barang yang sudah dibeli dibersihkan.');
  }
  redirect('user/shopping-list.php');
}
$edit = null;
if (isset($_GET['edit'])) {
  $s = $pdo->prepare("SELECT * FROM shopping_list_items WHERE item_id=? AND list_id=?");
  $s->execute([(int)$_GET['edit'], $list]);
  $edit = $s->fetch();
}
$items = $pdo->prepare("SELECT * FROM shopping_list_items WHERE list_id=? ORDER BY is_purchased, item_id DESC");
$items->execute([$list]);
$items = $items->fetchAll();
$bought = count(array_filter($items, fn($i) => $i['is_purchased']));
$pageTitle = 'Shopping List';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Shopping List</h1>
    <p><?= $bought ?> dari <?= count($items) ?> barang sudah dibeli.</p>
  </div>
  <?php if ($bought): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action"
        value="clear"><button class="btn btn-ghost">Bersihkan yang sudah dibeli</button></form><?php endif; ?>
</div>
<div class="grid g-main">
  <div class="card">
    <h2>Daftar belanja</h2>
    <?php if (!$items): ?><div class="empty"><b>🛒</b>Daftar kosong. Tambahkan barang atau ambil dari resep.</div>
    <?php endif; ?>
    <?php foreach ($items as $i): ?>
      <div class="shop-item <?= $i['is_purchased'] ? 'done' : '' ?>">
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input
            type="hidden" name="item_id" value="<?= $i['item_id'] ?>"><button class="check"
            title="Tandai"><?= $i['is_purchased'] ? '✓' : '' ?></button></form>
        <div class="grow">
          <div class="t name"><?= e($i['item_name']) ?></div>
          <div class="muted small"><?= num($i['quantity']) . ' ' . e($i['unit']) ?></div>
        </div>
        <div class="actions">
          <?php if ($i['is_purchased']): ?><a class="btn btn-ghost btn-sm"
              href="<?= url('user/food-form.php?' . http_build_query(['name' => $i['item_name'], 'qty' => $i['quantity'], 'unit' => $i['unit']])) ?>">→
              Pantry</a><?php endif; ?>
          <a class="btn btn-ghost btn-sm" href="?edit=<?= $i['item_id'] ?>">Edit</a>
          <form method="post" data-confirm="Hapus barang ini?"><?= csrf_field() ?><input type="hidden"
              name="action" value="delete"><input type="hidden" name="item_id"
              value="<?= $i['item_id'] ?>"><button class="btn btn-danger btn-sm">×</button></form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="card">
    <h2><?= $edit ? 'Edit barang' : 'Tambah barang' ?></h2>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden"
        name="item_id" value="<?= $edit['item_id'] ?? 0 ?>">
      <div class="field"><label>Nama barang</label><input type="text" name="item_name"
          value="<?= e($edit['item_name'] ?? '') ?>" required></div>
      <div class="form-grid">
        <div class="field"><label>Jumlah</label><input type="number" step="any" min="0" name="quantity"
            value="<?= e($edit['quantity'] ?? 1) ?>" required></div>
        <div class="field"><label>Satuan</label><input type="text" name="unit"
            value="<?= e($edit['unit'] ?? 'pcs') ?>"></div>
      </div>
      <div class="row"><button class="btn"><?= $edit ? 'Simpan' : 'Tambah' ?></button><?php if ($edit): ?><a
            class="btn btn-ghost" href="<?= url('user/shopping-list.php') ?>">Batal</a><?php endif; ?></div>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>