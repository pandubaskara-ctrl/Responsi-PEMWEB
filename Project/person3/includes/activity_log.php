<?php
const WASTE_REASONS = ['Expired', 'Spoiled', 'Excessive Purchase', 'Leftovers', 'Other'];
const MEAL_TYPES    = ['Breakfast', 'Lunch', 'Dinner'];

/* ---------- Dasar ---------- */
function e($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function url($p = '')
{
    return BASE_URL . '/' . ltrim($p, '/');
}
function redirect($p)
{
    header('Location: ' . url($p));
    exit;
}
function is_post()
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}
function flash($msg, $type = 'success')
{
    $_SESSION['flash'] = ['m' => $msg, 't' => $type];
}
function fmt_date($d)
{
    return $d ? date('d M Y', strtotime($d)) : '-';
}
function num($n)
{
    return rtrim(rtrim(number_format((float)$n, 2, '.', ''), '0'), '.');
}

/* ---------- Keamanan ---------- */
function csrf_token()
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field()
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}
function csrf_check()
{
    if (is_post() && !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit('Sesi formulir tidak valid. Silakan kembali dan muat ulang halaman.');
    }
}
function auth()
{
    return $_SESSION['user'] ?? null;
}
function home_for($role)
{
    return $role === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php';
}
function require_login($role = null)
{
    $u = auth();
    if (!$u) redirect('auth/login.php');
    if ($role && $u['role'] !== $role) redirect(home_for($u['role']));
    csrf_check();
    return $u;
}
function log_activity($text, $uid = null)
{
    global $pdo;
    $uid = $uid ?? (auth()['id'] ?? null);
    if (!$uid) return;
    $pdo->prepare("INSERT INTO activity_logs(user_id,activity) VALUES(?,?)")->execute([$uid, mb_substr($text, 0, 255)]);
}

/* ---------- Upload gambar ---------- */
function upload_image($field, $sub)
{
    if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
    $f = $_FILES[$field];
    if ($f['size'] > 2 * 1024 * 1024) return null;
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp']) || !@getimagesize($f['tmp_name'])) return null;
    $dir = __DIR__ . '/../uploads/' . $sub;
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    move_uploaded_file($f['tmp_name'], "$dir/$name");
    return "uploads/$sub/$name";
}

/* ---------- Kedaluwarsa ---------- */
function expiry_info($date)
{
    $diff = (int)(new DateTime('today'))->diff(new DateTime($date))->format('%r%a');
    if ($diff < 0)   return ['Expired', 'danger', $diff];
    if ($diff === 0) return ['Expires Today', 'warning', 0];
    if ($diff <= 3)  return ['Expiring Soon', 'warning', $diff];
    return ['Fresh', 'success', $diff];
}
function days_text($d)
{
    if ($d < 0)  return abs($d) . ' hari lalu';
    if ($d == 0) return 'Hari ini';
    if ($d == 1) return 'Besok';
    return $d . ' hari lagi';
}
function badge($label, $cls)
{
    return '<span class="badge ' . $cls . '">' . e($label) . '</span>';
}

function pantry_counts($uid)
{
    global $pdo;
    $s = $pdo->prepare("SELECT expiry_date FROM food_items WHERE user_id=? AND quantity>0");
    $s->execute([$uid]);
    $c = ['total' => 0, 'fresh' => 0, 'soon' => 0, 'expired' => 0];
    foreach ($s as $r) {
        $c['total']++;
        $l = expiry_info($r['expiry_date'])[0];
        if ($l === 'Fresh') $c['fresh']++;
        elseif ($l === 'Expired') $c['expired']++;
        else $c['soon']++;
    }
    return $c;
}

function sync_notifications($uid)
{
    global $pdo;
    $s = $pdo->prepare("SELECT food_id,food_name,expiry_date FROM food_items WHERE user_id=? AND quantity>0 AND expiry_date<=DATE_ADD(CURDATE(),INTERVAL 3 DAY)");
    $s->execute([$uid]);
    $rows = $s->fetchAll();
    $chk = $pdo->prepare("SELECT 1 FROM notifications WHERE user_id=? AND food_id=? AND message=?");
    $ins = $pdo->prepare("INSERT INTO notifications(user_id,food_id,message) VALUES(?,?,?)");
    foreach ($rows as $r) {
        $msg = $r['food_name'] . ' — status: ' . expiry_info($r['expiry_date'])[0];
        $chk->execute([$uid, $r['food_id'], $msg]);
        if (!$chk->fetchColumn()) $ins->execute([$uid, $r['food_id'], $msg]);
    }
}

/* ---------- Master data ---------- */
function get_or_create_ingredient($name, $unit = '')
{
    global $pdo;
    $name = trim($name);
    $s = $pdo->prepare("SELECT ingredient_id FROM ingredients WHERE LOWER(ingredient_name)=LOWER(?)");
    $s->execute([$name]);
    if ($id = $s->fetchColumn()) return (int)$id;
    $pdo->prepare("INSERT INTO ingredients(ingredient_name,default_unit) VALUES(?,?)")->execute([$name, $unit]);
    return (int)$pdo->lastInsertId();
}
function default_list_id($uid)
{
    global $pdo;
    $s = $pdo->prepare("SELECT list_id FROM shopping_lists WHERE user_id=? ORDER BY list_id LIMIT 1");
    $s->execute([$uid]);
    if ($id = $s->fetchColumn()) return (int)$id;
    $pdo->prepare("INSERT INTO shopping_lists(user_id,list_name) VALUES(?,'My Shopping List')")->execute([$uid]);
    return (int)$pdo->lastInsertId();
}

/* ---------- Rekomendasi resep ---------- */
function recipe_matches($uid, $q = '', $cat = '')
{
    global $pdo;
    $sql = "SELECT * FROM recipes WHERE 1=1";
    $p = [];
    if ($q !== '') {
        $sql .= " AND (recipe_name LIKE ? OR description LIKE ?)";
        $p[] = "%$q%";
        $p[] = "%$q%";
    }
    if ($cat !== '') {
        $sql .= " AND category=?";
        $p[] = $cat;
    }
    $st = $pdo->prepare($sql);
    $st->execute($p);
    $recipes = $st->fetchAll();

    $have = $pdo->prepare("SELECT DISTINCT ingredient_id FROM food_items WHERE user_id=? AND quantity>0 AND ingredient_id IS NOT NULL");
    $have->execute([$uid]);
    $owned = array_flip($have->fetchAll(PDO::FETCH_COLUMN));

    $ri = $pdo->query("SELECT ri.recipe_id, ri.ingredient_id, i.ingredient_name FROM recipe_ingredients ri JOIN ingredients i ON i.ingredient_id=ri.ingredient_id")->fetchAll();
    $by = [];
    foreach ($ri as $r) $by[$r['recipe_id']][] = $r;

    foreach ($recipes as &$r) {
        $list = $by[$r['recipe_id']] ?? [];
        $tot = count($list);
        $h = 0;
        $miss = [];
        foreach ($list as $x) {
            if (isset($owned[$x['ingredient_id']])) $h++;
            else $miss[] = $x['ingredient_name'];
        }
        $r['total'] = $tot;
        $r['have'] = $h;
        $r['missing'] = $miss;
        $r['pct'] = $tot ? (int)round($h / $tot * 100) : 0;
    }
    unset($r);
    usort($recipes, fn($a, $b) => ($b['pct'] <=> $a['pct']) ?: strcmp($a['recipe_name'], $b['recipe_name']));
    return $recipes;
}
function recipe_card($r, $favs = [])
{
    $img = !empty($r['image_url']) ? '<img src="' . e(url($r['image_url'])) . '" alt="">' : '<span>🍳</span>';
    $cls = $r['pct'] >= 75 ? 'success' : ($r['pct'] >= 40 ? 'warning' : 'muted');
    $fav = isset($favs[$r['recipe_id']]) ? '<em class="fav">♥</em>' : '';
    return '<a class="recipe-card" href="' . url('user/recipe-detail.php?id=' . $r['recipe_id']) . '"><div class="rc-img">' . $img . $fav . '</div>'
        . '<div class="rc-body"><span class="chip">' . e($r['category']) . '</span><h3>' . e($r['recipe_name']) . '</h3>'
        . '<p class="muted small">⏱ ' . (int)$r['cooking_time'] . ' min · ' . (int)$r['total'] . ' ingredients</p>'
        . '<div class="progress"><i class="' . $cls . '" style="width:' . $r['pct'] . '%"></i></div>'
        . '<p class="match">' . $r['have'] . '/' . $r['total'] . ' tersedia · <b>' . $r['pct'] . '%</b> cocok</p></div></a>';
}

/* ---------- Statistik & grafik (tanpa library) ---------- */
function months_back($n = 6)
{
    $out = [];
    for ($i = $n - 1; $i >= 0; $i--) {
        $d = new DateTime('first day of this month');
        $d->modify("-$i month");
        $out[$d->format('Y-m')] = $d->format('M y');
    }
    return $out;
}
function monthly_waste($uid = null)
{
    global $pdo;
    $m = months_back(6);
    $sql = "SELECT DATE_FORMAT(waste_date,'%Y-%m') ym, COUNT(*) c FROM food_waste WHERE waste_date>=?";
    $p = [array_key_first($m) . '-01'];
    if ($uid) {
        $sql .= " AND user_id=?";
        $p[] = $uid;
    }
    $sql .= " GROUP BY ym";
    $st = $pdo->prepare($sql);
    $st->execute($p);
    $rows = $st->fetchAll(PDO::FETCH_KEY_PAIR);
    $out = [];
    foreach ($m as $k => $lbl) $out[$lbl] = (int)($rows[$k] ?? 0);
    return $out;
}
function bar_chart($data)
{
    $max = max(1, max($data ?: [0]));
    $h = '<div class="bars">';
    foreach ($data as $l => $v) {
        $h .= '<div class="bar-col"><span class="bar-val">' . e($v) . '</span><div class="bar-track"><div class="bar" style="height:' . round($v / $max * 100) . '%"></div></div><span class="bar-lbl">' . e($l) . '</span></div>';
    }
    return $h . '</div>';
}
function line_chart($data)
{
    $w = 520;
    $h = 190;
    $pad = 28;
    $n = count($data);
    $max = max(1, max($data ?: [0]));
    $pts = [];
    $i = 0;
    foreach ($data as $l => $v) {
        $x = $n > 1 ? $pad + $i * ($w - 2 * $pad) / ($n - 1) : $w / 2;
        $y = $h - $pad - ($v / $max) * ($h - 2 * $pad);
        $pts[] = [round($x, 1), round($y, 1), $l, $v];
        $i++;
    }
    $poly = implode(' ', array_map(fn($p) => $p[0] . ',' . $p[1], $pts));
    $s = '<svg viewBox="0 0 ' . $w . ' ' . $h . '" class="line-chart"><line x1="' . $pad . '" y1="' . ($h - $pad) . '" x2="' . ($w - $pad) . '" y2="' . ($h - $pad) . '" stroke="#e7e1d3"/>'
        . '<polyline fill="none" stroke="#588157" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" points="' . $poly . '"/>';
    foreach ($pts as $p) {
        $s .= '<circle cx="' . $p[0] . '" cy="' . $p[1] . '" r="5" fill="#344E41"/>'
            . '<text x="' . $p[0] . '" y="' . ($p[1] - 11) . '" text-anchor="middle" font-size="12" font-weight="600" fill="#344E41">' . e($p[3]) . '</text>'
            . '<text x="' . $p[0] . '" y="' . ($h - 8) . '" text-anchor="middle" font-size="11" fill="#6f7d73">' . e($p[2]) . '</text>';
    }
    return $s . '</svg>';
}
function doughnut($seg)
{
    $tot = array_sum(array_column($seg, 1));
    if (!$tot) return '<div class="empty"><b>🌱</b>Belum ada data.</div>';
    $acc = 0;
    $stops = [];
    foreach ($seg as [$l, $v, $c]) {
        $from = round($acc / $tot * 100, 2);
        $acc += $v;
        $to = round($acc / $tot * 100, 2);
        $stops[] = "$c {$from}% {$to}%";
    }
    $h = '<div class="donut-wrap"><div class="donut" style="background:conic-gradient(' . implode(',', $stops) . ')"><div class="donut-hole"><b>' . $tot . '</b><small>items</small></div></div><ul class="legend">';
    foreach ($seg as [$l, $v, $c]) $h .= '<li><i style="background:' . $c . '"></i>' . e($l) . ' <b>' . $v . '</b></li>';
    return $h . '</ul></div>';
}
function hbars($rows)
{ 
    if (!$rows) return '<div class="empty"><b>🌱</b>Belum ada data.</div>';
    $max = max($rows);
    $h = '';
    foreach ($rows as $l => $v) {
        $h .= '<div class="hbar"><div class="top"><span>' . e($l) . '</span><b>' . e($v) . '</b></div><div class="progress"><i style="width:' . round($v / $max * 100) . '%"></i></div></div>';
    }
    return $h;
}
