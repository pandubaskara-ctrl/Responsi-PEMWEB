<?php
require_once __DIR__ . '/../config/database.php';
$u = require_login('user');
$uid = $u['id'];
if (is_post()) {
  $act = $_POST['action'] ?? '';
  $wid = (int)($_POST['waste_id'] ?? 0);
  if ($act === 'save') {
    $name = trim($_POST['food_name'] ?? '');
    $qty = $_POST['quantity'] ?? 0;
    $unit = trim($_POST['unit'] ?? '');
    $reason = $_POST['waste_reason'] ?? '';
    $date = $_POST['waste_date'] ?? '';
    $fid = (int)($_POST['food_id'] ?? 0);
    if ($name === '' || !is_numeric($qty) || $qty <= 0 || $unit === '' || !in_array($reason, WASTE_REASONS) || !strtotime($date)) {
      flash('Lengkapi data: nama, jumlah (> 0), satuan, alasan, dan tanggal.', 'danger');
    } else {
      $pdo->beginTransaction();
      $food = null;
      if ($fid && !$wid) {
        $f = $pdo->prepare("SELECT food_id FROM food_items WHERE food_id=? AND user_id=?");
        $f->execute([$fid, $uid]);
        $food = $f->fetchColumn() ?: null;
      }
      if ($wid) {
        $pdo->prepare("UPDATE food_waste SET food_name=?,quantity=?,unit=?,waste_reason=?,waste_date=? WHERE waste_id=? AND user_id=?")->execute([$name, $qty, $unit, $reason, $date, $wid, $uid]);
        log_activity('Memperbarui catatan food waste: ' . num($qty) . " $unit $name ($reason)");
        flash('Catatan diperbarui.');
      } else {
        $pdo->prepare("INSERT INTO food_waste(user_id,food_id,food_name,quantity,unit,waste_reason,waste_date) VALUES(?,?,?,?,?,?,?)")->execute([$uid, $food, $name, $qty, $unit, $reason, $date]);
        if ($food) $pdo->prepare("UPDATE food_items SET quantity=GREATEST(quantity-?,0) WHERE food_id=? AND user_id=?")->execute([$qty, $food, $uid]);
        log_activity('Mencatat food waste: ' . num($qty) . " $unit $name ($reason)");
        flash('Catatan food waste disimpan' . ($food ? ' dan stok pantry dikurangi.' : '.'));
      }
      $pdo->commit();
    }
  } elseif ($act === 'delete') {
    $pdo->prepare("DELETE FROM food_waste WHERE waste_id=? AND user_id=?")->execute([$wid, $uid]);
    log_activity('Menghapus catatan food waste ID #' . $wid);
    flash('Catatan dihapus.');
  }
  redirect('user/food-waste.php');
}
$edit = null;
if (isset($_GET['edit'])) {
  $s = $pdo->prepare("SELECT * FROM food_waste WHERE waste_id=? AND user_id=?");
  $s->execute([(int)$_GET['edit'], $uid]);
  $edit = $s->fetch();
}
$pre = null;
if (!$edit && isset($_GET['food'])) {
  $s = $pdo->prepare("SELECT * FROM food_items WHERE food_id=? AND user_id=?");
  $s->execute([(int)$_GET['food'], $uid]);
  $pre = $s->fetch();
}
$pantry = $pdo->prepare("SELECT food_id, food_name, unit, quantity FROM food_items WHERE user_id=? AND quantity>0 ORDER BY food_name");
$pantry->execute([$uid]);
$pantry = $pantry->fetchAll();
$hist = $pdo->prepare("SELECT * FROM food_waste WHERE user_id=? ORDER BY waste_date DESC, waste_id DESC");
$hist->execute([$uid]);
$hist = $hist->fetchAll();
$pageTitle = 'Food Waste Tracker';
require __DIR__ . '/../includes/header.php';
$rc = ['Expired' => 'danger', 'Spoiled' => 'danger', 'Excessive Purchase' => 'warning', 'Leftovers' => 'warning', 'Other' => 'muted'];
?>
<div class="page-head">
  <div>
    <h1>Food Waste Tracker</h1>
    <p>Mencatat adalah langkah pertama untuk mengurangi pemborosan.</p>
  </div>
</div>
<div class="grid g-main">
  <div class="card">
    <h2>Riwayat food waste</h2>
    <?php if (!$hist): ?><div class="empty"><b>🌍</b>Belum ada catatan — itu kabar baik!</div><?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Bahan</th>
              <th>Jumlah</th>
              <th>Alasan</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($hist as $h): ?><tr>
                <td><?= fmt_date($h['waste_date']) ?></td>
                <td class="name"><?= e($h['food_name']) ?></td>
                <td><?= num($h['quantity']) . ' ' . e($h['unit']) ?></td>
                <td><?= badge($h['waste_reason'], $rc[$h['waste_reason']] ?? 'muted') ?></td>
                <td>
                  <div class="actions"><a class="btn btn-ghost btn-sm"
                      href="?edit=<?= $h['waste_id'] ?>">Edit</a>
                    <form method="post" data-confirm="Hapus catatan ini?"><?= csrf_field() ?><input
                        type="hidden" name="action" value="delete"><input type="hidden" name="waste_id"
                        value="<?= $h['waste_id'] ?>"><button
                        class="btn btn-danger btn-sm">Hapus</button></form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?></tbody>
        </table>
      </div><?php endif; ?>
  </div>
  <div class="card">
    <h2><?= $edit ? 'Edit catatan' : 'Catat makanan terbuang' ?></h2>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden"
        name="waste_id" value="<?= $edit['waste_id'] ?? 0 ?>">
      <?php if (!$edit): ?><div class="field"><label>Dari pantry (opsional)</label>
          <select name="food_id" id="foodSel">
            <option value="">— Ketik manual —</option>
            <?php foreach ($pantry as $p): ?><option value="<?= $p['food_id'] ?>"
                data-name="<?= e($p['food_name']) ?>" data-unit="<?= e($p['unit']) ?>"
                data-qty="<?= num($p['quantity']) ?>"
                <?= $pre && $pre['food_id'] == $p['food_id'] ? 'selected' : '' ?>><?= e($p['food_name']) ?>
                (<?= num($p['quantity']) . ' ' . e($p['unit']) ?>)</option><?php endforeach; ?>
          </select>
          <p class="muted small" style="margin-top:4px">Jika dipilih, stok pantry otomatis dikurangi.</p>
        </div><?php endif; ?>
      <div class="field"><label>Nama bahan</label><input type="text" name="food_name" id="wName"
          value="<?= e($edit['food_name'] ?? ($pre['food_name'] ?? '')) ?>" required></div>
      <div class="form-grid">
        <div class="field"><label>Jumlah</label><input type="number" step="any" min="0" name="quantity"
            id="wQty" value="<?= e($edit['quantity'] ?? ($pre ? num($pre['quantity']) : '')) ?>" required>
        </div>
        <div class="field"><label>Satuan</label><input type="text" name="unit" id="wUnit"
            value="<?= e($edit['unit'] ?? ($pre['unit'] ?? 'pcs')) ?>" required></div>
      </div>
      <div class="field"><label>Alasan</label><select name="waste_reason"><?php foreach (WASTE_REASONS as $r): ?>
            <option <?= (($edit['waste_reason'] ?? ($pre ? 'Expired' : '')) === $r) ? 'selected' : '' ?>>
              <?= $r ?></option><?php endforeach; ?>
        </select></div>
      <div class="field"><label>Tanggal</label><input type="date" name="waste_date"
          value="<?= e($edit['waste_date'] ?? date('Y-m-d')) ?>" required></div>
      <div class="row"><button class="btn"><?= $edit ? 'Simpan' : 'Catat' ?></button><?php if ($edit): ?><a
            class="btn btn-ghost" href="<?= url('user/food-waste.php') ?>">Batal</a><?php endif; ?></div>
    </form>
  </div>
</div>
<script>
  document.getElementById('foodSel')?.addEventListener('change', function() {
    var o = this.options[this.selectedIndex];
    if (!o.value) return;
    document.getElementById('wName').value = o.dataset.name;
    document.getElementById('wUnit').value = o.dataset.unit;
    document.getElementById('wQty').value = o.dataset.qty;
  });
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>