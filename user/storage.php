<?php
require_once __DIR__ . '/../config/database.php';
$u = require_login('user');
$uid = $u['id'];
if (is_post()) {
  $act = $_POST['action'] ?? '';
  $sid = (int)($_POST['storage_id'] ?? 0);
  $name = trim($_POST['storage_name'] ?? '');
  $desc = trim($_POST['description'] ?? '');
  if ($act === 'save') {
    if ($name === '') flash('Nama lokasi wajib diisi.', 'danger');
    elseif ($sid) {
      $pdo->prepare("UPDATE storage_locations SET storage_name=?,description=? WHERE storage_id=? AND user_id=?")->execute([$name, $desc, $sid, $uid]);
      flash('Lokasi diperbarui.');
    } else {
      $pdo->prepare("INSERT INTO storage_locations(user_id,storage_name,description) VALUES(?,?,?)")->execute([$uid, $name, $desc]);
      flash('Lokasi ditambahkan.');
    }
  } elseif ($act === 'delete') {
    $used = $pdo->prepare("SELECT COUNT(*) FROM food_items WHERE storage_id=? AND user_id=?");
    $used->execute([$sid, $uid]);
    if ($used->fetchColumn() > 0) flash('Lokasi masih dipakai bahan di pantry, tidak bisa dihapus.', 'danger');
    else {
      $pdo->prepare("DELETE FROM storage_locations WHERE storage_id=? AND user_id=?")->execute([$sid, $uid]);
      flash('Lokasi dihapus.');
    }
  }
  redirect('user/storage.php');
}
$edit = null;
if (isset($_GET['edit'])) {
  $s = $pdo->prepare("SELECT * FROM storage_locations WHERE storage_id=? AND user_id=?");
  $s->execute([(int)$_GET['edit'], $uid]);
  $edit = $s->fetch();
}
$locs = $pdo->prepare("SELECT s.*, (SELECT COUNT(*) FROM food_items f WHERE f.storage_id=s.storage_id) used FROM storage_locations s WHERE user_id=? ORDER BY storage_name");
$locs->execute([$uid]);
$locs = $locs->fetchAll();
$cats = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM food_items f WHERE f.category_id=c.category_id AND f.user_id=" . (int)$uid . ") used FROM categories c ORDER BY category_name")->fetchAll();
$pageTitle = 'Storage Locations';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Storage Locations</h1>
    <p>Atur tempat penyimpanan bahan dan lihat kategori yang tersedia.</p>
  </div>
</div>
<div class="grid g2">
  <div>
    <div class="card mb">
      <h2><?= $edit ? 'Edit lokasi' : 'Tambah lokasi' ?></h2>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden"
          name="storage_id" value="<?= $edit['storage_id'] ?? 0 ?>">
        <div class="field"><label>Nama lokasi</label><input type="text" name="storage_name"
            value="<?= e($edit['storage_name'] ?? '') ?>" required placeholder="mis. Lemari Dapur"></div>
        <div class="field"><label>Keterangan</label><input type="text" name="description"
            value="<?= e($edit['description'] ?? '') ?>"></div>
        <div class="row"><button class="btn"><?= $edit ? 'Simpan' : 'Tambah' ?></button><?php if ($edit): ?><a
              class="btn btn-ghost" href="<?= url('user/storage.php') ?>">Batal</a><?php endif; ?></div>
      </form>
    </div>
    <div class="card">
      <h2>Lokasi saya</h2>
      <?php if (!$locs): ?><div class="empty">Belum ada lokasi.</div><?php else: ?><ul class="list">
          <?php foreach ($locs as $l): ?>
            <li>
              <div><span class="name"><?= e($l['storage_name']) ?></span>
                <div class="muted small"><?= e($l['description']) ?> · <?= $l['used'] ?> bahan</div>
              </div>
              <div class="actions"><a class="btn btn-ghost btn-sm"
                  href="<?= url('user/pantry.php?loc=' . $l['storage_id']) ?>">Lihat</a><a
                  class="btn btn-ghost btn-sm" href="?edit=<?= $l['storage_id'] ?>">Edit</a>
                <form method="post" data-confirm="Hapus lokasi ini?"><?= csrf_field() ?><input type="hidden"
                    name="action" value="delete"><input type="hidden" name="storage_id"
                    value="<?= $l['storage_id'] ?>"><button class="btn btn-danger btn-sm">Hapus</button>
                </form>
              </div>
            </li>
          <?php endforeach; ?>
        </ul><?php endif; ?>
    </div>
  </div>
  <div class="card">
    <h2>Kategori bahan</h2>
    <p class="muted small mb">Kategori dikelola oleh Admin. Klik untuk memfilter pantry-mu.</p>
    <ul class="list"><?php foreach ($cats as $c): ?><li>
          <div><span class="name"><?= e($c['category_name']) ?></span>
            <div class="muted small"><?= e($c['description']) ?></div>
          </div>
          <a
            href="<?= url('user/pantry.php?cat=' . $c['category_id']) ?>"><?= badge($c['used'] . ' bahan', 'muted') ?></a>
        </li><?php endforeach; ?></ul>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>