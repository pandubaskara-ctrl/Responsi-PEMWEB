<?php
require_once __DIR__ . '/../config/database.php';
$u = require_login();
$uid = $u['id'];
if (is_post()) {
  if (($_POST['action'] ?? '') === 'profile') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $dup = $pdo->prepare("SELECT 1 FROM users WHERE email=? AND user_id<>?");
    $dup->execute([$email, $uid]);
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) flash('Nama dan email yang valid wajib diisi.', 'danger');
    elseif ($dup->fetchColumn()) flash('Email sudah dipakai akun lain.', 'danger');
    else {
      $pdo->prepare("UPDATE users SET name=?,email=? WHERE user_id=?")->execute([$name, $email, $uid]);
      $_SESSION['user']['name'] = $name;
      $_SESSION['user']['email'] = $email;
      log_activity('Memperbarui profil');
      flash('Profil diperbarui.');
    }
  } elseif (($_POST['action'] ?? '') === 'password') {
    $row = $pdo->prepare("SELECT password FROM users WHERE user_id=?");
    $row->execute([$uid]);
    $new = $_POST['new_password'] ?? '';
    if (!password_verify($_POST['old_password'] ?? '', $row->fetchColumn())) flash('Password lama salah.', 'danger');
    elseif (strlen($new) < 6) flash('Password baru minimal 6 karakter.', 'danger');
    elseif ($new !== ($_POST['new_password2'] ?? '')) flash('Konfirmasi password baru tidak sama.', 'danger');
    else {
      $pdo->prepare("UPDATE users SET password=? WHERE user_id=?")->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
      log_activity('Mengganti password');
      flash('Password berhasil diganti.');
    }
  }
  redirect('auth/profile.php');
}
$me = $pdo->prepare("SELECT * FROM users WHERE user_id=?");
$me->execute([$uid]);
$me = $me->fetch();
$pageTitle = 'Profile';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h1><?= $u['role'] === 'admin' ? 'Settings' : 'My Profile' ?></h1>
    <p>Kelola informasi akun dan keamanan.</p>
  </div>
</div>
<div class="grid g2">
  <div class="card">
    <h2>Informasi akun</h2>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="profile">
      <div class="field"><label>Nama</label><input type="text" name="name" value="<?= e($me['name']) ?>" required>
      </div>
      <div class="field"><label>Email</label><input type="email" name="email" value="<?= e($me['email']) ?>"
          required></div>
      <p class="small muted mb">Role: <b><?= e(ucfirst($me['role'])) ?></b> · Bergabung
        <?= fmt_date($me['created_at']) ?></p>
      <button class="btn">Simpan perubahan</button>
    </form>
  </div>
  <div class="card">
    <h2>Ganti password</h2>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="password">
      <div class="field"><label>Password lama</label><input type="password" name="old_password" required></div>
      <div class="field"><label>Password baru</label><input type="password" name="new_password" required></div>
      <div class="field"><label>Konfirmasi password baru</label><input type="password" name="new_password2"
          required></div>
      <button class="btn btn-secondary">Ganti password</button>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>