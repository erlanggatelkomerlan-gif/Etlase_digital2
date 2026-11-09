<?php
require __DIR__ . '/includes/config.php';
require_staff();
csrf_verify();

$id = (int) ($_POST['id'] ?? 0);
db()->prepare('UPDATE karya SET unggulan = 1 - unggulan WHERE id = ?')->execute([$id]);
flash('ok', 'Status unggulan diperbarui.');
redirect($_SERVER['HTTP_REFERER'] ?? 'admin.php');
