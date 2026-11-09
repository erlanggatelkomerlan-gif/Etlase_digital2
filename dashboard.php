<?php
require __DIR__ . '/includes/config.php';
$me = require_login();

$q      = trim($_GET['q'] ?? '');
$kat    = $_GET['kategori'] ?? '';
$urut   = ($_GET['urut'] ?? 'terbaru') === 'populer' ? 'populer' : 'terbaru';
$saya   = isset($_GET['saya']);
$page   = max(1, (int) ($_GET['hal'] ?? 1));
$per    = 12;

$where = ['1=1'];
$p = [];
if ($q !== '') {
    $where[] = '(k.judul LIKE :q OR k.deskripsi LIKE :q OR u.nama LIKE :q)';
    $p[':q'] = '%' . $q . '%';
}
if ($kat !== '' && in_array($kat, KATEGORI, true)) {
    $where[] = 'k.kategori = :kat';
    $p[':kat'] = $kat;
}
if ($saya) {
    $where[] = 'k.user_id = :uid';
    $p[':uid'] = $me['id'];
}
$W = implode(' AND ', $where);

$cnt = db()->prepare("SELECT COUNT(*) FROM karya k JOIN users u ON u.id = k.user_id WHERE $W");
$cnt->execute($p);
$total = (int) $cnt->fetchColumn();

$order = $urut === 'populer' ? 'jml_suka DESC, k.id DESC' : 'k.id DESC';
$st = db()->prepare(karya_select() . " WHERE $W ORDER BY $order LIMIT $per OFFSET " . (($page - 1) * $per));
$st->execute($p + [':me' => $me['id']]);
$karya = $st->fetchAll();

$filtered = ($q !== '' || $kat !== '' || $saya || $page > 1);

$unggulan = [];
if (!$filtered) {
    $f = db()->prepare(karya_select() . ' WHERE k.unggulan = 1 ORDER BY k.id DESC LIMIT 3');
    $f->execute([':me' => $me['id']]);
    $unggulan = $f->fetchAll();
}

$stat = [
    'karya'  => (int) db()->query('SELECT COUNT(*) FROM karya')->fetchColumn(),
    'siswa'  => (int) db()->query("SELECT COUNT(*) FROM users WHERE role='siswa' AND aktif=1")->fetchColumn(),
    'suka'   => (int) db()->query('SELECT COUNT(*) FROM suka')->fetchColumn(),
];
$mine = db()->prepare('SELECT COUNT(*) FROM karya WHERE user_id = ?');
$mine->execute([$me['id']]);
$stat['saya'] = (int) $mine->fetchColumn();

$qs = http_build_query(array_filter(['q' => $q, 'kategori' => $kat, 'urut' => $urut !== 'terbaru' ? $urut : '', 'saya' => $saya ? 1 : '']));

layout_start($saya ? 'Karya Saya' : 'Beranda', $saya ? 'karyasaya' : 'beranda');
?>

<?php if (!$filtered): ?>
<section class="hero">
    <div class="hero-text">
        <span class="eyebrow eyebrow-light"><?= e(ROLE_LABEL[$me['role']]) ?></span>
        <h1>Halo, <?= e(explode(' ', $me['nama'])[0]) ?>!<br>Lihat karya terbaru siswa.</h1>
        <p>Jelajahi karya dari teman-temanmu, beri apresiasi, dan tambahkan karyamu sendiri ke etalase.</p>
        <div class="hero-actions">
            <a href="karya_form.php" class="btn btn-white"><?= icon('plus', 18) ?> Tambah Karya</a>
            <a href="profil.php" class="btn btn-ghost">Lihat Profil</a>
        </div>
    </div>
    <div class="hero-stats">
        <div><b><?= $stat['karya'] ?></b><span>Total Karya</span></div>
        <div><b><?= $stat['siswa'] ?></b><span>Siswa Aktif</span></div>
        <div><b><?= $stat['suka'] ?></b><span>Apresiasi</span></div>
        <div><b><?= $stat['saya'] ?></b><span>Karya Saya</span></div>
    </div>
</section>
<?php endif; ?>

<?php if ($unggulan): ?>
<section class="section">
    <div class="section-head"><h2>Karya Unggulan</h2><span class="muted">Pilihan admin</span></div>
    <div class="grid-karya">
        <?php foreach ($unggulan as $k) echo karya_card($k); ?>
    </div>
</section>
<?php endif; ?>

<section class="section">
    <div class="section-head">
        <h2><?= $saya ? 'Karya Saya' : ($q !== '' ? 'Hasil pencarian' : 'Semua Karya') ?></h2>
        <span class="muted"><?= $total ?> karya</span>
    </div>

    <form class="filters" method="get">
        <?php if ($saya): ?><input type="hidden" name="saya" value="1"><?php endif; ?>
        <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>
        <div class="chips">
            <a class="chip <?= $kat === '' ? 'on' : '' ?>" href="?<?= e(http_build_query(array_filter(['q' => $q, 'urut' => $urut !== 'terbaru' ? $urut : '', 'saya' => $saya ? 1 : '']))) ?>">Semua</a>
            <?php foreach (KATEGORI as $c): ?>
                <a class="chip <?= $kat === $c ? 'on' : '' ?>" href="?<?= e(http_build_query(array_filter(['q' => $q, 'kategori' => $c, 'urut' => $urut !== 'terbaru' ? $urut : '', 'saya' => $saya ? 1 : '']))) ?>"><?= e($c) ?></a>
            <?php endforeach; ?>
        </div>
        <div class="sort">
            <?php if ($kat !== ''): ?><input type="hidden" name="kategori" value="<?= e($kat) ?>"><?php endif; ?>
            <label class="sr" for="urut">Urutkan</label>
            <select id="urut" name="urut" class="input input-sm" data-autosubmit>
                <option value="terbaru" <?= $urut === 'terbaru' ? 'selected' : '' ?>>Terbaru</option>
                <option value="populer" <?= $urut === 'populer' ? 'selected' : '' ?>>Terpopuler</option>
            </select>
        </div>
    </form>

    <?php if (!$karya): ?>
        <div class="empty card">
            <?= icon('image', 40) ?>
            <h2>Belum ada karya</h2>
            <p><?= $saya ? 'Kamu belum menambahkan karya. Mulai pamerkan karyamu sekarang!' : 'Tidak ada karya yang cocok. Jadilah yang pertama menambahkan karya.' ?></p>
            <a href="karya_form.php" class="btn btn-red"><?= icon('plus', 18) ?> Tambah Karya</a>
        </div>
    <?php else: ?>
        <div class="grid-karya">
            <?php foreach ($karya as $k) echo karya_card($k); ?>
        </div>
        <?= pagination($total, $per, $page, $qs) ?>
    <?php endif; ?>
</section>

<?php layout_end();
