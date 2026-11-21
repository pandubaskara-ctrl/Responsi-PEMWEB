<?php
require_once __DIR__ . '/../config/database.php';
$u = require_login('admin');
if (is_post()) {
    $type = ($_POST['type'] ?? '') === 'article' ? 'article' : 'food';
    $act = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    if ($type === 'food') {
        $tbl = 'categories';
        $pk = 'category_id';
    } else {
        $tbl = 'article_categories';
        $pk = 'article_category_id';
    }
    if ($act === 'save') {
        if ($name === '') flash('Nama kategori wajib diisi.', 'danger');
        elseif ($type === 'food') {
            if ($id) $pdo->prepare("UPDATE categories SET category_name=?,description=? WHERE category_id=?")->execute([$name, $desc, $id]);
            else $pdo->prepare("INSERT INTO categories(category_name,description) VALUES(?,?)")->execute([$name, $desc]);
            flash('Kategori disimpan.');
        } else {
            if ($id) $pdo->prepare("UPDATE article_categories SET category_name=? WHERE article_category_id=?")->execute([$name, $id]);
            else $pdo->prepare("INSERT INTO article_categories(category_name) VALUES(?)")->execute([$name]);
            flash('Kategori artikel disimpan.');
        }
    } elseif ($act === 'delete') {
        $used = $type === 'food' ? "SELECT COUNT(*) FROM food_items WHERE category_id=?" : "SELECT COUNT(*) FROM articles WHERE article_category_id=?";
        $c = $pdo->prepare($used);
        $c->execute([$id]);
        if ($c->fetchColumn() > 0) flash('Kategori masih dipakai, tidak bisa dihapus.', 'danger');
        else {
            $pdo->prepare("DELETE FROM $tbl WHERE $pk=?")->execute([$id]);
            flash('Kategori dihapus.');
        }
    }
    redirect('admin/categories.php');
}
$editType = $_GET['type'] ?? 'food';
$edit = null;
if (isset($_GET['edit'])) {
    $s = $editType === 'article' ? $pdo->prepare("SELECT article_category_id id, category_name name, '' description FROM article_categories WHERE article_category_id=?")
        : $pdo->prepare("SELECT category_id id, category_name name, description FROM categories WHERE category_id=?");
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch();
}
$cats = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM food_items f WHERE f.category_id=c.category_id) used FROM categories c ORDER BY category_name")->fetchAll();
$acats = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM articles a WHERE a.article_category_id=c.article_category_id) used FROM article_categories c ORDER BY category_name")->fetchAll();
$pageTitle = 'Category Management';
require __DIR__ . '/../includes/header.php';

function cat_form($type, $edit, $editType, $withDesc)
{
    $e = ($edit && $editType === $type) ? $edit : null; ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="type"
            value="<?= $type ?>"><input type="hidden" name="id" value="<?= $e['id'] ?? 0 ?>">
        <div class="field"><label>Nama kategori</label><input type="text" name="name" value="<?= e($e['name'] ?? '') ?>"
                required></div>
        <?php if ($withDesc): ?><div class="field"><label>Deskripsi</label><input type="text" name="description"
                    value="<?= e($e['description'] ?? '') ?>"></div><?php endif; ?>
        <div class="row"><button class="btn"><?= $e ? 'Simpan' : 'Tambah' ?></button><?php if ($e): ?><a
                    class="btn btn-ghost" href="<?= url('admin/categories.php') ?>">Batal</a><?php endif; ?></div>
    </form>
<?php }
function cat_rows($rows, $type)
{ ?>
    <ul class="list"><?php foreach ($rows as $r): ?><li>
                <div><span class="name"><?= e($r['category_name']) ?></span>
                    <div class="muted small"><?= e($r['description'] ?? '') ?> <?= $r['used'] ?> dipakai</div>
                </div>
                <div class="actions"><a class="btn btn-ghost btn-sm"
                        href="?type=<?= $type ?>&edit=<?= $r['category_id'] ?? $r['article_category_id'] ?>">Edit</a>
                    <form method="post" data-confirm="Hapus kategori ini?"><?= csrf_field() ?><input type="hidden" name="action"
                            value="delete"><input type="hidden" name="type" value="<?= $type ?>"><input type="hidden" name="id"
                            value="<?= $r['category_id'] ?? $r['article_category_id'] ?>"><button
                            class="btn btn-danger btn-sm">Hapus</button></form>
                </div>
            </li><?php endforeach; ?></ul>
<?php } ?>
<div class="page-head">
    <div>
        <h1>Category Management</h1>
        <p>Kelola kategori bahan makanan dan kategori artikel.</p>
    </div>
</div>
<div class="grid g2">
    <div>
        <div class="card mb">
            <h2>Kategori bahan makanan</h2><?php cat_form('food', $edit, $editType, true); ?>
        </div>
        <div class="card"><?php cat_rows($cats, 'food'); ?></div>
    </div>
    <div>
        <div class="card mb">
            <h2>Kategori artikel</h2><?php cat_form('article', $edit, $editType, false); ?>
        </div>
        <div class="card"><?php cat_rows($acats, 'article'); ?></div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>