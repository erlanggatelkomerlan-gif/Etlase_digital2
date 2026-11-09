<?php
require __DIR__ . '/includes/config.php';
$me = require_login();

$id = (int) ($_GET['id'] ?? $me['id']);
$st = db()->prepare('SELECT * FROM users WHERE id = ?');
$st->execute([$id]);
$u = $st->fetch();
if (!$u) {
    http_response_code(404);
    layout_start('Tidak ditemukan');
    echo '<div class="empty card"><h2>Pengguna tidak ditemukan</h2><a class="btn btn-red" href="dashboard.php">Kembali</a></div>';
    layout_end();
    exit;
}
$own = (int) $u['id'] === (int) $me['id'];

$s = db()->prepare('SELECT COUNT(*) AS n, COALESCE(SUM(dilihat),0) AS v FROM karya WHERE user_id = ?');
$s->execute([$id]);
$agg = $s->fetch();
$sk = db()->prepare('SELECT COUNT(*) FROM suka s JOIN karya k ON k.id = s.karya_id WHERE k.user_id = ?');
$sk->execute([$id]);
$suka = (int) $sk->fetchColumn();

$ks = db()->prepare(karya_select() . ' WHERE k.user_id = :uid ORDER BY k.id DESC');
$ks->execute([':me' => $me['id'], ':uid' => $id]);
$karya = $ks->fetchAll();

layout_start($own ? 'Profil Saya' : $u['nama'], $own ? 'profil' : '');
?>
<section class="profile card">
    <div class="profile-banner">
        <span class="orb o1"></span><span class="orb o2"></span>
    </div>
    <div class="profile-main">
        <?= avatar_html($u, 120, 'avatar-xl') ?>
        <div class="profile-info">
            <div class="profile-name">
                <h1><?= e($u['nama']) ?></h1>
                <?= role_badge($u['role']) ?>
                <?php if (!$u['aktif']): ?><span class="badge b-soft">Nonaktif</span><?php endif; ?>
            </div>
            <p class="muted">@<?= e($u['username']) ?><?= $u['kelas'] ? ' &middot; ' . e($u['kelas']) : '' ?> &middot; Bergabung <?= e(tanggal_id($u['dibuat'])) ?></p>
            <p class="profile-bio"><?= $u['bio'] !== '' ? nl2br(e($u['bio'])) : '<span class="muted">Belum ada bio.</span>' ?></p>
        </div>
        <?php if ($own): ?>
            <a href="profil_edit.php" class="btn btn-red"><?= icon('edit', 18) ?> Edit Profil</a>
        <?php endif; ?>
    </div>
    <div class="profile-stats">
        <div><b><?= (int) $agg['n'] ?></b><span>Karya</span></div>
        <div><b><?= $suka ?></b><span>Suka Diterima</span></div>
        <div><b><?= (int) $agg['v'] ?></b><span>Total Dilihat</span></div>
    </div>
</section>

<section class="section">
    <div class="section-head"><h2><?= $own ? 'Karya Saya' : 'Karya ' . e(explode(' ', $u['nama'])[0]) ?></h2><span class="muted"><?= count($karya) ?> karya</span></div>
    <?php if (!$karya): ?>
        <div class="empty card">
            <?= icon('image', 40) ?>
            <h2>Belum ada karya</h2>
            <?php if ($own): ?><p>Mulai pamerkan karya pertamamu.</p><a class="btn btn-red" href="karya_form.php"><?= icon('plus', 18) ?> Tambah Karya</a><?php endif; ?>
        </div>
    <?php else: ?>
        <div class="grid-karya"><?php foreach ($karya as $k) echo karya_card($k); ?></div>
    <?php endif; ?>
</section>
<?php layout_end();
