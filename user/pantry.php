<?php
require_once __DIR__ . '/../config/database.php';
$u = require_login('user');
$uid = $u['id'];
if (is_post()) {
    $id = (int)($_POST['food_id'] ?? 0);
    $f = $pdo->prepare("SELECT * FROM food_items WHERE food_id=? AND user_id=?");
    $f->execute([$id, $uid]);
    $food = $f->fetch();
    if ($food) {
        $act = $_POST['action'] ?? '';
        if ($act === 'stock') {
            $amt = (float)($_POST['amount'] ?? 0);
            $op = $_POST['op'] ?? 'use';
            if ($amt <= 0) flash('Jumlah harus lebih dari 0.', 'danger');
            elseif ($op === 'use') {
                $pdo->prepare("UPDATE food_items SET quantity=GREATEST(quantity-?,0) WHERE food_id=? AND user_id=?")->execute([$amt, $id, $uid]);
                log_activity('Menggunakan ' . num($amt) . ' ' . $food['unit'] . ' ' . $food['food_name']);
                flash('Stok ' . $food['food_name'] . ' dikurangi.');
            } else {
                $pdo->prepare("UPDATE food_items SET quantity=quantity+? WHERE food_id=? AND user_id=?")->execute([$amt, $id, $uid]);
                log_activity('Menambah ' . num($amt) . ' ' . $food['unit'] . ' ' . $food['food_name']);
                flash('Stok ' . $food['food_name'] . ' ditambah.');
            }
        } elseif ($act === 'delete') {
            $pdo->prepare("DELETE FROM food_items WHERE food_id=? AND user_id=?")->execute([$id, $uid]);
            log_activity('Menghapus ' . $food['food_name'] . ' dari pantry');
            flash('Bahan dihapus.');
        }
    }
    redirect('user/pantry.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
}
$q = trim($_GET['q'] ?? '');
$cat = (int)($_GET['cat'] ?? 0);
$loc = (int)($_GET['loc'] ?? 0);
$status = $_GET['status'] ?? '';
$sql = "SELECT f.*, c.category_name, s.storage_name FROM food_items f
        LEFT JOIN categories c ON c.category_id=f.category_id
        LEFT JOIN storage_locations s ON s.storage_id=f.storage_id WHERE f.user_id=?";
$p = [$uid];
if ($q !== '') {
    $sql .= " AND f.food_name LIKE ?";
    $p[] = "%$q%";
}
if ($cat) {
    $sql .= " AND f.category_id=?";
    $p[] = $cat;
}
if ($loc) {
    $sql .= " AND f.storage_id=?";
    $p[] = $loc;
}
$sql .= " ORDER BY f.expiry_date, f.food_name";
$st = $pdo->prepare($sql);
$st->execute($p);
$items = $st->fetchAll();
if ($status !== '') {
    $items = array_values(array_filter($items, function ($f) use ($status) {
        if ($status === 'empty') return $f['quantity'] <= 0;
        if ($f['quantity'] <= 0) return false;
        $l = expiry_info($f['expiry_date'])[0];
        return ($status === 'fresh' && $l === 'Fresh') || ($status === 'soon' && in_array($l, ['Expiring Soon', 'Expires Today'])) || ($status === 'expired' && $l === 'Expired');
    }));
}
$cats = $pdo->query("SELECT * FROM categories ORDER BY category_name")->fetchAll();
$locs = $pdo->prepare("SELECT * FROM storage_locations WHERE user_id=? ORDER BY storage_name");
$locs->execute([$uid]);
$locs = $locs->fetchAll();
$pageTitle = 'My Pantry';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <div>
        <h1>My Pantry</h1>
        <p>Semua bahan makananmu dalam satu tempat.</p>
    </div><a class="btn" href="<?= url('user/food-form.php') ?>">+ Add Food</a>
</div>
<div class="card">
    <form class="filters" method="get">
        <input type="text" name="q" placeholder="Cari bahan…" value="<?= e($q) ?>">
        <select name="cat">
            <option value="0">Semua kategori</option><?php foreach ($cats as $c): ?><option
                    value="<?= $c['category_id'] ?>" <?= $cat == $c['category_id'] ? 'selected' : '' ?>>
                    <?= e($c['category_name']) ?></option><?php endforeach; ?>
        </select>
        <select name="loc">
            <option value="0">Semua lokasi</option><?php foreach ($locs as $l): ?><option
                    value="<?= $l['storage_id'] ?>" <?= $loc == $l['storage_id'] ? 'selected' : '' ?>>
                    <?= e($l['storage_name']) ?></option><?php endforeach; ?>
        </select>
        <select name="status">
            <option value="">Semua status</option>
            <?php foreach (['fresh' => 'Fresh', 'soon' => 'Expiring Soon', 'expired' => 'Expired', 'empty' => 'Habis'] as $k => $v): ?>
                <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?>
        </select>
        <button class="btn">Filter</button>
    </form>
    <?php if (!$items): ?><div class="empty"><b>🧺</b>Tidak ada bahan yang cocok. <a
                href="<?= url('user/food-form.php') ?>">Tambah bahan</a></div><?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Bahan</th>
                        <th>Kategori</th>
                        <th>Lokasi</th>
                        <th>Stok</th>
                        <th>Kedaluwarsa</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $f): [$l, $cls, $d] = expiry_info($f['expiry_date']);
                                                                                    $empty = $f['quantity'] <= 0; ?>
                        <tr>
                            <td>
                                <div class="name"><?= e($f['food_name']) ?></div><?php if ($f['notes']): ?><div
                                        class="muted small"><?= e($f['notes']) ?></div><?php endif; ?>
                            </td>
                            <td><?= e($f['category_name'] ?? '-') ?></td>
                            <td><?= e($f['storage_name'] ?? '-') ?></td>
                            <td><b><?= num($f['quantity']) ?></b>
                                <?= e($f['unit']) ?><?php if ($empty) echo ' ' . badge('Habis', 'muted'); ?>
                                <form method="post" class="stock-form mt" style="margin-top:6px"><?= csrf_field() ?><input
                                        type="hidden" name="action" value="stock"><input type="hidden" name="food_id"
                                        value="<?= $f['food_id'] ?>">
                                    <input type="number" name="amount" step="any" min="0" placeholder="qty">
                                    <button class="btn btn-ghost btn-sm" name="op" value="use" title="Gunakan">−</button><button
                                        class="btn btn-ghost btn-sm" name="op" value="add" title="Tambah">+</button>
                                </form>
                            </td>
                            <td><?= fmt_date($f['expiry_date']) ?><div class="muted small"><?= days_text($d) ?></div>
                                <?= $empty ? '' : badge($l, $cls) ?></td>
                            <td>
                                <div class="actions"><a class="btn btn-ghost btn-sm"
                                        href="<?= url('user/food-form.php?id=' . $f['food_id']) ?>">Edit</a>
                                    <form method="post" data-confirm="Hapus <?= e($f['food_name']) ?>?">
                                        <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input
                                            type="hidden" name="food_id" value="<?= $f['food_id'] ?>"><button
                                            class="btn btn-danger btn-sm">Hapus</button></form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>