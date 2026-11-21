<?php
require_once __DIR__ . '/../config/database.php';
$u = require_login('admin');
if (is_post()) {
  $act = $_POST['action'] ?? '';
  $id = (int)($_POST['article_id'] ?? 0);
  if ($act === 'save') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $cat = (int)($_POST['article_category_id'] ?? 0) ?: null;
    if ($title === '' || $content === '') {
      flash('Judul dan isi artikel wajib diisi.', 'danger');
      redirect('admin/articles.php' . ($id ? "?edit=$id" : '?new=1'));
    }
    $img = upload_image('image', 'articles');
    if ($id) {
      $pdo->prepare("UPDATE articles SET article_category_id=?,title=?,content=?,image_url=COALESCE(?,image_url) WHERE article_id=?")->execute([$cat, $title, $content, $img, $id]);
      flash('Artikel diperbarui.');
    } else {
      $pdo->prepare("INSERT INTO articles(article_category_id,admin_id,title,content,image_url) VALUES(?,?,?,?,?)")->execute([$cat, $u['id'], $title, $content, $img]);
      flash('Artikel dipublikasikan.');
    }
  } elseif ($act === 'delete') {
    $pdo->prepare("DELETE FROM articles WHERE article_id=?")->execute([$id]);
    flash('Artikel dihapus.');
  }
  redirect('admin/articles.php');
}
$editing = isset($_GET['edit']) || isset($_GET['new']);
$a = null;
if (isset($_GET['edit'])) {
  $s = $pdo->prepare("SELECT * FROM articles WHERE article_id=?");
  $s->execute([(int)$_GET['edit']]);
  $a = $s->fetch();
  if (!$a) redirect('admin/articles.php');
}
$cats = $pdo->query("SELECT * FROM article_categories ORDER BY category_name")->fetchAll();
$all = $pdo->query("SELECT a.*, c.category_name FROM articles a LEFT JOIN article_categories c ON c.article_category_id=a.article_category_id ORDER BY a.created_at DESC")->fetchAll();
$pageTitle = 'Article Management';
require __DIR__ . '/../includes/header.php';
?>
<?php if ($editing): ?>
  <div class="page-head">
    <div>
      <h1><?= $a ? 'Edit Artikel' : 'Artikel Baru' ?></h1>
    </div><a class="btn btn-ghost" href="<?= url('admin/articles.php') ?>">← Kembali</a>
  </div>
  <div class="card" style="max-width:860px">
    <form method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="action"
        value="save"><input type="hidden" name="article_id" value="<?= $a['article_id'] ?? 0 ?>">
      <div class="field"><label>Judul</label><input type="text" name="title" value="<?= e($a['title'] ?? '') ?>"
          required></div>
      <div class="field"><label>Kategori</label><select name="article_category_id">
          <option value="">— Umum —</option><?php foreach ($cats as $c): ?><option
              value="<?= $c['article_category_id'] ?>"
              <?= ($a['article_category_id'] ?? 0) == $c['article_category_id'] ? 'selected' : '' ?>>
              <?= e($c['category_name']) ?></option><?php endforeach; ?>
        </select></div>
      <div class="field"><label>Isi artikel</label><textarea name="content" style="min-height:240px"
          required><?= e($a['content'] ?? '') ?></textarea></div>
      <div class="field"><label>Gambar (jpg/png/webp, maks 2 MB)</label><input type="file" name="image"
          accept="image/*"><?php if (!empty($a['image_url'])): ?><p class="muted small mt">Gambar saat ini:
            <?= e($a['image_url']) ?></p><?php endif; ?></div>
      <button class="btn">Simpan artikel</button>
    </form>
  </div>
<?php else: ?>
  <div class="page-head">
    <div>
      <h1>Article Management</h1>
      <p><?= count($all) ?> artikel.</p>
    </div>
    <div class="row"><a class="btn btn-ghost" href="<?= url('admin/categories.php') ?>">Kelola kategori</a><a
        class="btn" href="?new=1">+ Artikel baru</a></div>
  </div>
  <div class="card"><?php if (!$all): ?><div class="empty"><b>📰</b>Belum ada artikel.</div><?php else: ?><div
        class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Judul</th>
              <th>Kategori</th>
              <th>Tanggal</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($all as $x): ?><tr>
                <td class="name"><?= e($x['title']) ?></td>
                <td><span class="chip"><?= e($x['category_name'] ?? 'Umum') ?></span></td>
                <td><?= fmt_date($x['created_at']) ?></td>
                <td>
                  <div class="actions"><a class="btn btn-ghost btn-sm"
                      href="?edit=<?= $x['article_id'] ?>">Edit</a>
                    <form method="post" data-confirm="Hapus artikel ini?"><?= csrf_field() ?><input
                        type="hidden" name="action" value="delete"><input type="hidden" name="article_id"
                        value="<?= $x['article_id'] ?>"><button class="btn btn-danger btn-sm">Hapus</button>
                    </form>
                  </div>
                </td>
              </tr><?php endforeach; ?>
          </tbody>
        </table>
      </div><?php endif; ?></div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>