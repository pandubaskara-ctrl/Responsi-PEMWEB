<?php
require_once __DIR__ . '/../config/database.php';
if (auth()) redirect(home_for(auth()['role']));
$errors = [];
if (is_post()) {
  csrf_check();
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $pw = $_POST['password'] ?? '';
  $pw2 = $_POST['password2'] ?? '';
  if ($name === '') $errors[] = 'Nama wajib diisi.';
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
  if (strlen($pw) < 6) $errors[] = 'Password minimal 6 karakter.';
  if ($pw !== $pw2) $errors[] = 'Konfirmasi password tidak sama.';
  if (!$errors) {
    $c = $pdo->prepare("SELECT 1 FROM users WHERE email=?");
    $c->execute([$email]);
    if ($c->fetchColumn()) $errors[] = 'Email sudah terdaftar.';
  }
  if (!$errors) {
    $pdo->beginTransaction();
    $pdo->prepare("INSERT INTO users(name,email,password,role) VALUES(?,?,?,'user')")->execute([$name, $email, password_hash($pw, PASSWORD_DEFAULT)]);
    $id = (int)$pdo->lastInsertId();
    $st = $pdo->prepare("INSERT INTO storage_locations(user_id,storage_name,description) VALUES(?,?,?)");
    foreach ([['Refrigerator', 'Kulkas'], ['Freezer', 'Pembeku'], ['Dry Storage', 'Rak kering']] as $l) $st->execute([$id, $l[0], $l[1]]);
    $pdo->commit();
    session_regenerate_id(true);
    $_SESSION['user'] = ['id' => $id, 'name' => $name, 'email' => $email, 'role' => 'user'];
    log_activity('Mendaftar akun Pantrio', $id);
    flash('Akun berhasil dibuat. Selamat datang di Pantrio! 🌿');
    redirect('user/dashboard.php');
  }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register · Pantrio</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>

<body>
  <div class="auth">
    <div class="auth-side">
      <div class="brand" style="padding:0">pantrio<span>.</span></div>
      <div>
        <h1>Mulai kelola<br>pantry-mu.</h1>
        <p>Daftar gratis dan mulai kurangi food waste dari dapurmu sendiri.</p>
        <ul>
          <li>✅ Manage — stok &amp; lokasi simpan</li>
          <li>✅ Plan — menu &amp; belanja</li>
          <li>✅ Reduce Waste — catat &amp; evaluasi</li>
        </ul>
      </div>
      <span class="small" style="color:#cfdccb">SDG 12 · Responsible Consumption</span>
    </div>
    <div class="auth-form">
      <div class="auth-box">
        <h2>Buat akun baru</h2>
        <p>Hanya butuh satu menit.</p>
        <?php foreach ($errors as $er): ?><div class="flash danger"><?= e($er) ?></div><?php endforeach; ?>
        <form method="post"><?= csrf_field() ?>
          <div class="field"><label>Nama lengkap</label><input type="text" name="name"
              value="<?= e($_POST['name'] ?? '') ?>" required></div>
          <div class="field"><label>Email</label><input type="email" name="email"
              value="<?= e($_POST['email'] ?? '') ?>" required></div>
          <div class="field"><label>Password</label><input type="password" name="password" required></div>
          <div class="field"><label>Konfirmasi password</label><input type="password" name="password2"
              required></div>
          <button class="btn btn-block">Daftar</button>
        </form>
        <p class="mt small muted">Sudah punya akun? <a href="<?= url('auth/login.php') ?>"><b>Login</b></a></p>
      </div>
    </div>
  </div>
</body>

</html>