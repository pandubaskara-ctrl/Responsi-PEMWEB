<?php
// Variabel yang diharapkan: $pageTitle (string), $active (opsional, nama file menu aktif)
$u = auth();
$isAdmin = $u['role'] === 'admin';
$active = $active ?? basename($_SERVER['SCRIPT_NAME']);
if ($isAdmin) {
  $menu = [
    ['admin/dashboard.php', 'Dashboard', '🏠'],
    ['admin/users.php', 'User Management', '👥'],
    ['admin/categories.php', 'Category Management', '🏷️'],
    ['admin/recipes.php', 'Recipe Management', '🍳'],
    ['admin/articles.php', 'Article Management', '📰'],
    ['admin/waste.php', 'Waste Statistics', '📊'],
    ['auth/profile.php', 'Settings', '⚙️'],
  ];
} else {
  $menu = [
    ['user/dashboard.php', 'Dashboard', '🏠'],
    ['user/pantry.php', 'My Pantry', '🥕'],
    ['user/expiration.php', 'Expiration Alerts', '⏰'],
    ['user/recipes.php', 'Recipe Finder', '🍳'],
    ['user/meal-planner.php', 'Meal Planner', '🗓️'],
    ['user/shopping-list.php', 'Shopping List', '🛒'],
    ['user/food-waste.php', 'Food Waste Tracker', '🗑️'],
    ['user/statistics.php', 'My Statistics', '📊'],
    ['user/storage.php', 'Storage Locations', '🗄️'],
    ['user/articles.php', 'Educational Blog', '📖'],
    ['auth/profile.php', 'My Profile', '👤'],
  ];
  $unread = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
  $unread->execute([$u['id']]);
  $unread = (int)$unread->fetchColumn();
}
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle ?? 'Pantrio') ?> · Pantrio</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>

<body>
  <aside class="sidebar" id="sidebar">
    <div class="brand">pantrio<span>.</span><small><?= $isAdmin ? 'PANTRIO ADMIN' : 'SMART PANTRY' ?></small></div>
    <nav class="nav">
      <?php foreach ($menu as [$file, $label, $ico]): ?>
        <a href="<?= url($file) ?>" class="<?= basename($file) === $active ? 'active' : '' ?>"><span
            class="ico"><?= $ico ?></span><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="side-user">
      <div class="avatar"><?= e(strtoupper(mb_substr($u['name'], 0, 1))) ?></div>
      <div><b><?= e($u['name']) ?></b><a href="<?= url('auth/logout.php') ?>">Logout →</a></div>
    </div>
  </aside>
  <div class="main">
    <header class="topbar">
      <button class="menu-btn" id="menuBtn" aria-label="Menu">☰</button>
      <span class="date"><?= strtoupper(date('l, j F Y')) ?></span>
      <?php if (!$isAdmin): ?>
        <a class="bell"
          href="<?= url('user/expiration.php') ?>">🔔<?php if ($unread): ?><b><?= $unread ?></b><?php endif; ?></a>
      <?php else: ?><span class="chip">Admin</span><?php endif; ?>
    </header>
    <main class="content">
      <?php if ($flash): ?><div class="flash <?= e($flash['t']) ?>"><?= e($flash['m']) ?></div><?php endif; ?>