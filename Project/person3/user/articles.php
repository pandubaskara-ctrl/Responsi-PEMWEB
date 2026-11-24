<?php
require_once __DIR__ . '/../config/database.php';
$u = require_login('user');
$pageTitle = 'Educational Blog';
if (isset($_GET['id'])) {
  $s = $pdo->prepare("SELECT a.*, c.category_name, u.name author FROM articles a LEFT JOIN article_categories c ON c.article_category_id=a.article_category_id LEFT JOIN users u ON u.user_id=a.admin_id WHERE a.article_id=?");
  $s->execute([(int)$_GET['id']]);
  $a = $s->fetch();
  if (!$a) {
    flash('Artikel tidak ditemukan.', 'danger');
    redirect('user/articles.php');
  }
  $pageTitle = $a['title'];
  require __DIR__ . '/../includes/header.php'; ?>
  <div class="page-head">
    <div><span class="chip"><?= e($a['category_name'] ?? 'Umum') ?></span>
      <h1 style="margin-top:6px"><?= e($a['title']) ?></h1>
      <p><?= fmt_date($a['created_at']) ?> · <?= e($a['author'] ?? 'Admin') ?></p>
    </div><a class="btn btn-ghost" href="<?= url('user/articles.php') ?>">← Kembali</a>
  </div>
  <?php if ($a['image_url']): ?><div class="hero-img"><img src="<?= e(url($a['image_url'])) ?>" alt=""></div>
  <?php endif; ?>
  <div class="card" style="max-width:820px;font-size:1.02rem;line-height:1.8"><?= nl2br(e($a['content'])) ?></div>
<?php require __DIR__ . '/../includes/footer.php';
  exit;
}
$q = trim($_GET['q'] ?? '');
$cat = (int)($_GET['cat'] ?? 0);
$sql = "SELECT a.*, c.category_name FROM articles a LEFT JOIN article_categories c ON c.article_category_id=a.article_category_id WHERE 1=1";
$p = [];
if ($q !== '') {
  $sql .= " AND (a.title LIKE ? OR a.content LIKE ?)";
  $p[] = "%$q%";
  $p[] = "%$q%";
}
if ($cat) {
  $sql .= " AND a.article_category_id=?";
  $p[] = $cat;
}
$st = $pdo->prepare($sql . " ORDER BY a.created_at DESC");
$st->execute($p);
$arts = $st->fetchAll();
$cats = $pdo->query("SELECT * FROM article_categories ORDER BY category_name")->fetchAll();
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Educational Blog</h1>
    <p>Tips menyimpan makanan dan mengurangi food waste.</p>
  </div>
</div>
<div class="card mb">
  <form class="filters" method="get" style="margin:0"><input type="text" name="q" placeholder="Cari artikel…"
      value="<?= e($q) ?>">
    <select name="cat">
      <option value="0">Semua kategori</option><?php foreach ($cats as $c): ?><option
          value="<?= $c['article_category_id'] ?>" <?= $cat == $c['article_category_id'] ? 'selected' : '' ?>>
          <?= e($c['category_name']) ?></option><?php endforeach; ?>
    </select><button class="btn">Cari</button>
  </form>
</div>
<?php if (!$arts): ?><div class="card empty"><b>📖</b>Belum ada artikel.</div><?php else: ?>
  <div class="recipe-grid"><?php foreach ($arts as $a): ?>
      <a class="recipe-card" href="?id=<?= $a['article_id'] ?>">
        <div class="rc-img"><?= $a['image_url'] ? '<img src="' . e(url($a['image_url'])) . '" alt="">' : '📖' ?></div>
        <div class="rc-body"><span class="chip"><?= e($a['category_name'] ?? 'Umum') ?></span>
          <h3><?= e($a['title']) ?></h3>
          <p class="muted small"><?= e(mb_strimwidth(strip_tags($a['content']), 0, 110, '…')) ?></p>
        </div>
      </a>
    <?php endforeach; ?>
  </div><?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>