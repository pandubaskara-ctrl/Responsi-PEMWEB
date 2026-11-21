<?php
require_once __DIR__ . '/../config/database.php';
$u = require_login('admin');
if (is_post()) {
  $id = (int)($_POST['user_id'] ?? 0);
  if ($id === $u['id']) flash('Kamu tidak bisa mengubah atau menghapus akunmu sendiri di sini.', 'danger');
  elseif (($_POST['action'] ?? '') === 'role' && in_array($_POST['role'] ?? '', ['admin', 'user'])) {
    $pdo->prepare("UPDATE users SET role=? WHERE user_id=?")->execute([$_POST['role'], $id]);
    flash('Role diperbarui.');
  } elseif (($_POST['action'] ?? '') === 'delete') {
    $pdo->prepare("DELETE FROM users WHERE user_id=?")->execute([$id]);
    flash('Akun dihapus beserta seluruh datanya.');
  }
  redirect('admin/users.php');
}
$q = trim($_GET['q'] ?? '');
$st = $pdo->prepare("SELECT * FROM users WHERE name LIKE ? OR email LIKE ? ORDER BY role, user_id DESC");
$st->execute(["%$q%", "%$q%"]);
$users = $st->fetchAll();
$pageTitle = 'User Management';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>User Management</h1>
    <p><?= count($users) ?> akun terdaftar.</p>
  </div>
</div>
<div class="card">
  <form class="filters" method="get"><input type="text" name="q" placeholder="Cari nama atau email…"
      value="<?= e($q) ?>"><button class="btn">Cari</button></form>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Nama</th>
          <th>Email</th>
          <th>Role</th>
          <th>Bergabung</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $x): $me = $x['user_id'] == $u['id']; ?>
          <tr>
            <td class="name"><?= e($x['name']) ?><?= $me ? ' ' . badge('Kamu', 'muted') : '' ?></td>
            <td><?= e($x['email']) ?></td>
            <td><?= badge(ucfirst($x['role']), $x['role'] === 'admin' ? 'warning' : 'success') ?></td>
            <td><?= fmt_date($x['created_at']) ?></td>
            <td><?php if (!$me): ?><div class="actions">
                  <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden"
                      name="action" value="role"><input type="hidden" name="user_id"
                      value="<?= $x['user_id'] ?>">
                    <select name="role">
                      <option <?= $x['role'] === 'user' ? 'selected' : '' ?>>user</option>
                      <option <?= $x['role'] === 'admin' ? 'selected' : '' ?>>admin</option>
                    </select><button class="btn btn-ghost btn-sm">Ubah</button>
                  </form>
                  <form method="post" data-confirm="Hapus akun <?= e($x['name']) ?> beserta seluruh datanya?">
                    <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input
                      type="hidden" name="user_id" value="<?= $x['user_id'] ?>"><button
                      class="btn btn-danger btn-sm">Hapus</button></form>
                </div><?php endif; ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>