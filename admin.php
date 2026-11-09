<?php
require __DIR__ . '/includes/config.php';
$me = require_staff();

$q = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['hal'] ?? 1));
$per = 15;

$p = [];
$w = '1=1';
if ($q !== '') { $w = '(k.judul LIKE :q OR u.nama LIKE :q OR k.kategori LIKE :q)'; $p[':q'] = "%$q%"; }

$c = db()->prepare("SELECT COUNT(*) FROM karya k JOIN users u ON u.id=k.user_id WHERE $w");
$c->execute($p);
$total = (int) $c->fetchColumn();

$st = db()->prepare("SELECT k.*, u.nama, (SELECT COUNT(*) FROM suka s WHERE s.karya_id=k.id) AS jml_suka
    FROM karya k JOIN users u ON u.id=k.user_id WHERE $w ORDER BY k.id DESC LIMIT $per OFFSET " . (($page - 1) * $per));
$st->execute($p);
$rows = $st->fetchAll();

$stat = [
    ['Total Karya', (int) db()->query('SELECT COUNT(*) FROM karya')->fetchColumn()],
    ['Siswa Terdaftar', (int) db()->query("SELECT COUNT(*) FROM users WHERE role='siswa'")->fetchColumn()],
    ['Karya Unggulan', (int) db()->query('SELECT COUNT(*) FROM karya WHERE unggulan=1')->fetchColumn()],
    ['Karya Hari Ini', (int) db()->query("SELECT COUNT(*) FROM karya WHERE date(dibuat)=date('now','localtime')")->fetchColumn()],
];

layout_start('Panel Admin', 'admin');
?>
<section class="hero hero-dark">
    <div class="hero-text">
        <span class="eyebrow eyebrow-light"><?= e(ROLE_LABEL[$me['role']]) ?></span>
        <h1>Panel Admin</h1>
        <p>Moderasi karya siswa, pilih karya unggulan, dan jaga etalase tetap rapi.</p>
    </div>
    <div class="hero-actions">
        <a href="pengguna.php" class="btn btn-white"><?= icon('users', 18) ?> Kelola Pengguna</a>
        <?php if (is_super($me)): ?><a href="pengaturan.php" class="btn btn-ghost">Pengaturan Situs</a><?php endif; ?>
    </div>
</section>

<section class="stats">
    <?php foreach ($stat as $i => $s): ?>
        <div class="stat <?= $i === 0 ? 'stat-red' : '' ?>"><span><?= e($s[0]) ?></span><b><?= $s[1] ?></b></div>
    <?php endforeach; ?>
</section>

<section class="card">
    <div class="card-head">
        <h2>Moderasi Karya</h2>
        <form class="inline-search" method="get">
            <label class="sr" for="aq">Cari</label>
            <input id="aq" class="input input-sm" type="search" name="q" value="<?= e($q) ?>" placeholder="Cari judul atau siswa">
        </form>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Karya</th><th>Siswa</th><th>Kategori</th><th>Suka</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if (!$rows): ?><tr><td colspan="6" class="empty-cell">Tidak ada karya.</td></tr><?php endif; ?>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><a class="tcell" href="karya_lihat.php?id=<?= (int) $r['id'] ?>"><img src="<?= e(img_url('karya', $r['gambar'])) ?>" alt=""><span><b><?= e($r['judul']) ?></b><small><?= e(tanggal_id($r['dibuat'])) ?></small></span></a></td>
                    <td><a href="profil.php?id=<?= (int) $r['user_id'] ?>"><?= e($r['nama']) ?></a></td>
                    <td><?= e($r['kategori']) ?></td>
                    <td><?= (int) $r['jml_suka'] ?></td>
                    <td><?= $r['unggulan'] ? '<span class="badge b-red">Unggulan</span>' : '<span class="badge b-gray">Biasa</span>' ?></td>
                    <td>
                        <div class="row-actions">
                            <form method="post" action="karya_unggulan.php"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="btn btn-line btn-sm" type="submit"><?= $r['unggulan'] ? 'Batalkan' : 'Unggulkan' ?></button></form>
                            <form method="post" action="karya_hapus.php" data-confirm="Hapus karya ini secara permanen?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="kembali" value="admin"><button class="btn btn-danger btn-sm" type="submit">Hapus</button></form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-foot"><?= pagination($total, $per, $page, $q !== '' ? http_build_query(['q' => $q]) : '') ?></div>
</section>
<?php layout_end();
