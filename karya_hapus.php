<?php
require __DIR__ . '/includes/config.php';
$me = require_login();
csrf_verify();

$id = (int) ($_POST['id'] ?? 0);
$st = db()->prepare('SELECT * FROM karya WHERE id = ?');
$st->execute([$id]);
$k = $st->fetch();

if ($k) {
    if ((int) $k['user_id'] !== (int) $me['id'] && !is_staff($me)) forbidden();
    db()->prepare('DELETE FROM karya WHERE id = ?')->execute([$id]);
    hapus_gambar('karya', $k['gambar']);
    flash('ok', 'Karya telah dihapus.');
}
redirect(is_staff($me) && ($_POST['kembali'] ?? '') === 'admin' ? 'admin.php' : 'dashboard.php');
