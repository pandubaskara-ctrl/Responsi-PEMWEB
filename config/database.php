<?php
// Konfigurasi Pantrio — sesuaikan dengan XAMPP kamu
define('BASE_URL', '/pantrio');   // nama folder project di htdocs (ubah jika berbeda)
define('DB_HOST', 'localhost');
define('DB_NAME', 'pantrio');
define('DB_USER', 'root');
define('DB_PASS', '');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $ex) {
    exit('Koneksi database gagal. Pastikan MySQL aktif dan database.sql sudah di-import. (' . htmlspecialchars($ex->getMessage()) . ')');
}

require_once __DIR__ . '/../includes/functions.php';
