<?php
require_once __DIR__ . '/../config/database.php';
if (auth()) redirect(home_for(auth()['role']));
$err = '';
if (is_post()) {
  csrf_check();
  $email = trim($_POST['email'] ?? '');
  $s = $pdo->prepare("SELECT * FROM users WHERE email=?");
  $s->execute([$email]);
  $u = $s->fetch();
  if ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
    session_regenerate_id(true);
    $_SESSION['user'] = ['id' => (int)$u['user_id'], 'name' => $u['name'], 'email' => $u['email'], 'role' => $u['role']];
    log_activity('Login ke Pantrio', $u['user_id']);
    redirect(home_for($u['role']));
  }
  $err = 'Email atau password salah.';
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login · Pantrio</title>
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
        <h1>Use What You Have,<br>Waste Less.</h1>
        <p>Kelola pantry, rencanakan menu, dan kurangi makanan terbuang bersama Pantrio.</p>
        <ul>
          <li>🥕 Pantau stok &amp; kedaluwarsa</li>
          <li>🍳 Resep dari bahan yang ada</li>
          <li>📊 Statistik food waste pribadi</li>
        </ul>
      </div>
      <span class="small" style="color:#cfdccb">SDG 12 · Responsible Consumption</span>
    </div>
    <div class="auth-form">
      <div class="auth-box">
        <h2>Selamat datang kembali 👋</h2>
        <p>Masuk untuk melanjutkan ke pantry-mu.</p>
        <?php if ($err): ?><div class="flash danger"><?= e($err) ?></div><?php endif; ?>
        <form method="post"><?= csrf_field() ?>
          <div class="field"><label>Email</label><input type="email" name="email"
              value="<?= e($_POST['email'] ?? '') ?>" required autofocus></div>
          <div class="field"><label>Password</label><input type="password" name="password" required></div>
          <button class="btn btn-block">Login</button>
        </form>
        <p class="mt small muted">Belum punya akun? <a href="<?= url('auth/register.php') ?>"><b>Daftar
              sekarang</b></a></p>
        <p class="mt small muted">Demo — Admin: admin@pantrio.test · User: user@pantrio.test · Password:
          <code>password</code>
        </p>
      </div>
    </div>
  </div>
</body>

</html>