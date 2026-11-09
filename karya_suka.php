<?php
require __DIR__ . '/includes/config.php';
$me = require_login();
csrf_verify();

$id = (int) ($_POST['id'] ?? 0);
$ada = db()->prepare('SELECT 1 FROM karya WHERE id = ?');
$ada->execute([$id]);
if (!$ada->fetch()) { http_response_code(404); exit; }

$cek = db()->prepare('SELECT 1 FROM suka WHERE user_id = ? AND karya_id = ?');
$cek->execute([$me['id'], $id]);
if ($cek->fetch()) {
    db()->prepare('DELETE FROM suka WHERE user_id = ? AND karya_id = ?')->execute([$me['id'], $id]);
    $on = false;
} else {
    db()->prepare('INSERT INTO suka (user_id, karya_id) VALUES (?, ?)')->execute([$me['id'], $id]);
    $on = true;
}

$n = db()->prepare('SELECT COUNT(*) FROM suka WHERE karya_id = ?');
$n->execute([$id]);

if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') {
    header('Content-Type: application/json');
    echo json_encode(['on' => $on, 'count' => (int) $n->fetchColumn()]);
    exit;
}
redirect($_SERVER['HTTP_REFERER'] ?? 'dashboard.php');
