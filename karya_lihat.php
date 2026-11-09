<?php
require __DIR__ . '/includes/config.php';
$me = require_login();

$id = (int) ($_GET['id'] ?? 0);
$st = db()->prepare(karya_select() . ' WHERE k.id = :id');
$st->execute([':me' => $me['id'], ':id' => $id]);
$k = $st->fetch();
if (!$k) {
    http_response_code(404);
    layout_start('Tidak ditemukan');
    echo '<div class="empty card"><h2>Karya tidak ditemukan</h2><a class="btn btn-red" href="dashboard.php">Kembali</a></div>';
    layout_end();
    exit;
}

// hitung tayangan sekali per sesi
if (empty($_SESSION['dilihat'][$id])) {
    $_SESSION['dilihat'][$id] = 1;
    db()->prepare('UPDATE karya SET dilihat = dilihat + 1 WHERE id = ?')->execute([$id]);
    $k['dilihat']++;
}

$bisaKelola = (int) $k['user_id'] === (int) $me['id'] || is_staff($me);

$lain = db()->prepare(karya_select() . ' WHERE k.user_id = :uid AND k.id != :id ORDER BY k.id DESC LIMIT 3');
$lain->execute([':me' => $me['id'], ':uid' => $k['user_id'], ':id' => $id]);
$lain = $lain->fetchAll();

layout_start($k['judul']);
?>
<a href="dashboard.php" class="back">&larr; Kembali ke Beranda</a>

<section class="detail">
    <div class="detail-img card">
        <img src="<?= e(img_url('karya', $k['gambar'])) ?>" alt="<?= e($k['judul']) ?>">
    </div>

    <aside class="detail-side">
        <div class="card pad">
            <div class="tags">
                <span class="badge b-soft"><?= e($k['kategori']) ?></span>
                <?php if ($k['unggulan']): ?><span class="badge b-red"><?= icon('star', 12) ?> Unggulan</span><?php endif; ?>
            </div>
            <h1 class="detail-title"><?= e($k['judul']) ?></h1>

            <a class="author-box" href="profil.php?id=<?= (int) $k['user_id'] ?>">
                <?= avatar_html($k, 48) ?>
                <span><b><?= e($k['nama']) ?></b><small><?= e($k['kelas'] ?: 'Siswa') ?> &middot; <?= e(tanggal_id($k['dibuat'])) ?></small></span>
            </a>

            <?php if (trim($k['deskripsi']) !== ''): ?>
                <p class="detail-desc"><?= nl2br(e($k['deskripsi'])) ?></p>
            <?php endif; ?>

            <div class="detail-stats">
                <span><?= icon('eye', 18) ?> <?= (int) $k['dilihat'] ?> dilihat</span>
                <form method="post" action="karya_suka.php" class="like-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <button type="submit" class="like-btn like-lg <?= $k['saya_suka'] ? 'on' : '' ?>" aria-pressed="<?= $k['saya_suka'] ? 'true' : 'false' ?>">
                        <?= icon('heart', 18) ?><span class="like-count"><?= (int) $k['jml_suka'] ?></span> suka
                    </button>
                </form>
            </div>

            <?php if ($bisaKelola): ?>
                <div class="manage">
                    <a href="karya_form.php?id=<?= $id ?>" class="btn btn-line btn-sm"><?= icon('edit', 16) ?> Edit</a>
                    <?php if (is_staff($me)): ?>
                        <form method="post" action="karya_unggulan.php">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
                            <button class="btn btn-dark btn-sm" type="submit"><?= icon('star', 16) ?> <?= $k['unggulan'] ? 'Batal Unggulan' : 'Jadikan Unggulan' ?></button>
                        </form>
                    <?php endif; ?>
                    <form method="post" action="karya_hapus.php" data-confirm="Hapus karya ini secara permanen?">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
                        <button class="btn btn-danger btn-sm" type="submit"><?= icon('trash', 16) ?> Hapus</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </aside>
</section>

<?php if ($lain): ?>
<section class="section">
    <div class="section-head"><h2>Karya lain dari <?= e(explode(' ', $k['nama'])[0]) ?></h2></div>
    <div class="grid-karya">
        <?php foreach ($lain as $l) echo karya_card($l); ?>
    </div>
</section>
<?php endif; ?>
<?php layout_end();
