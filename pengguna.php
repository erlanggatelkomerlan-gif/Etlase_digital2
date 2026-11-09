<?php
require __DIR__ . '/includes/config.php';
$me = require_staff();
$super = is_super($me);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $aksi = $_POST['aksi'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    $t = null;
    if ($id) {
        $s = db()->prepare('SELECT * FROM users WHERE id = ?');
        $s->execute([$id]);
        $t = $s->fetch();
    }

    // Admin hanya boleh mengelola siswa; tidak ada yang boleh mengubah dirinya lewat sini
    $boleh = $t && (int) $t['id'] !== (int) $me['id'] && ($super || $t['role'] === 'siswa');

    if ($aksi === 'tambah' && $super) {
        $nama = trim($_POST['nama'] ?? '');
        $un = trim($_POST['username'] ?? '');
        $pw = (string) ($_POST['password'] ?? '');
        $role = $_POST['role'] ?? 'siswa';
        if (!isset(ROLE_LABEL[$role])) $role = 'siswa';
        $cek = db()->prepare('SELECT 1 FROM users WHERE username = ?');
        $cek->execute([$un]);
        if (mb_strlen($nama) < 3 || !preg_match('/^[A-Za-z0-9_.]{4,20}$/', $un) || strlen($pw) < 6) {
            flash('err', 'Data tidak valid: nama min. 3 karakter, username 4-20 karakter (huruf/angka/._), password min. 6 karakter.');
        } elseif ($cek->fetch()) {
            flash('err', 'Username sudah dipakai.');
        } else {
            db()->prepare('INSERT INTO users (username, nama, password, role) VALUES (?,?,?,?)')
                ->execute([$un, $nama, password_hash($pw, PASSWORD_DEFAULT), $role]);
            flash('ok', "Pengguna $un berhasil ditambahkan.");
        }
    } elseif ($aksi === 'role' && $super && $boleh) {
        $role = $_POST['role'] ?? '';
        if (isset(ROLE_LABEL[$role])) {
            db()->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $id]);
            flash('ok', 'Peran pengguna diperbarui.');
        }
    } elseif ($aksi === 'aktif' && $boleh) {
        db()->prepare('UPDATE users SET aktif = 1 - aktif WHERE id = ?')->execute([$id]);
        flash('ok', 'Status akun diperbarui.');
    } elseif ($aksi === 'reset' && $boleh) {
        $tmp = substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(9))), 0, 8);
        db()->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash($tmp, PASSWORD_DEFAULT), $id]);
        flash('ok', 'Password sementara untuk ' . $t['username'] . ': ' . $tmp . ' (catat dan berikan kepada pengguna).');
    } elseif ($aksi === 'hapus' && $super && $boleh) {
        $g = db()->prepare('SELECT gambar FROM karya WHERE user_id = ?');
        $g->execute([$id]);
        foreach ($g->fetchAll() as $r) hapus_gambar('karya', $r['gambar']);
        hapus_gambar('avatar', $t['avatar']);
        db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        flash('ok', 'Pengguna dan seluruh karyanya telah dihapus.');
    } else {
        flash('err', 'Aksi tidak diizinkan.');
    }
    redirect('pengguna.php' . (!empty($_POST['q']) ? '?q=' . urlencode($_POST['q']) : ''));
}

$q = trim($_GET['q'] ?? '');
$rf = $_GET['role'] ?? '';
$w = ['1=1'];
$p = [];
if ($q !== '') { $w[] = '(u.nama LIKE :q OR u.username LIKE :q OR u.kelas LIKE :q)'; $p[':q'] = "%$q%"; }
if (isset(ROLE_LABEL[$rf])) { $w[] = 'u.role = :r'; $p[':r'] = $rf; }
$st = db()->prepare('SELECT u.*, (SELECT COUNT(*) FROM karya k WHERE k.user_id = u.id) AS n_karya FROM users u WHERE ' . implode(' AND ', $w) . " ORDER BY CASE u.role WHEN 'super_admin' THEN 0 WHEN 'admin' THEN 1 ELSE 2 END, u.nama");
$st->execute($p);
$users = $st->fetchAll();

layout_start('Pengguna', 'pengguna');
?>
<div class="page-head">
    <div><h1>Manajemen Pengguna</h1><p class="muted"><?= $super ? 'Atur akun, peran, dan akses seluruh pengguna.' : 'Kelola akun siswa (aktifkan, nonaktifkan, reset password).' ?></p></div>
</div>

<?php if ($super): ?>
<details class="card pad add-user">
    <summary class="btn btn-red btn-sm"><?= icon('plus', 16) ?> Tambah Pengguna</summary>
    <form method="post" class="row-4">
        <?= csrf_field() ?><input type="hidden" name="aksi" value="tambah">
        <div class="field"><label for="tn">Nama</label><input class="input" id="tn" name="nama" required></div>
        <div class="field"><label for="tu">Username</label><input class="input" id="tu" name="username" required></div>
        <div class="field"><label for="tp">Password</label><input class="input" id="tp" type="text" name="password" required minlength="6"></div>
        <div class="field"><label for="tr">Peran</label>
            <select class="input" id="tr" name="role">
                <?php foreach (ROLE_LABEL as $k => $l): ?><option value="<?= e($k) ?>" <?= $k === 'siswa' ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="field field-btn"><button class="btn btn-red" type="submit">Simpan</button></div>
    </form>
</details>
<?php endif; ?>

<section class="card">
    <div class="card-head">
        <h2><?= count($users) ?> Pengguna</h2>
        <form class="inline-search" method="get">
            <label class="sr" for="uq">Cari</label>
            <input id="uq" class="input input-sm" type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nama atau username">
            <label class="sr" for="ur">Peran</label>
            <select id="ur" class="input input-sm" name="role" data-autosubmit>
                <option value="">Semua peran</option>
                <?php foreach (ROLE_LABEL as $k => $l): ?><option value="<?= e($k) ?>" <?= $rf === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Pengguna</th><th>Peran</th><th>Karya</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u):
                $self = (int) $u['id'] === (int) $me['id'];
                $kelola = !$self && ($super || $u['role'] === 'siswa'); ?>
                <tr>
                    <td><a class="tcell" href="profil.php?id=<?= (int) $u['id'] ?>"><?= avatar_html($u, 40) ?><span><b><?= e($u['nama']) ?><?= $self ? ' (Anda)' : '' ?></b><small>@<?= e($u['username']) ?><?= $u['kelas'] ? ' · ' . e($u['kelas']) : '' ?></small></span></a></td>
                    <td>
                        <?php if ($super && $kelola): ?>
                            <form method="post" class="inline-form">
                                <?= csrf_field() ?><input type="hidden" name="aksi" value="role"><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><input type="hidden" name="q" value="<?= e($q) ?>">
                                <label class="sr" for="r<?= (int) $u['id'] ?>">Peran</label>
                                <select id="r<?= (int) $u['id'] ?>" name="role" class="input input-sm" data-autosubmit>
                                    <?php foreach (ROLE_LABEL as $k => $l): ?><option value="<?= e($k) ?>" <?= $u['role'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
                                </select>
                            </form>
                        <?php else: echo role_badge($u['role']); endif; ?>
                    </td>
                    <td><?= (int) $u['n_karya'] ?></td>
                    <td><?= $u['aktif'] ? '<span class="badge b-ok">Aktif</span>' : '<span class="badge b-soft">Nonaktif</span>' ?></td>
                    <td>
                        <?php if ($kelola): ?>
                        <div class="row-actions">
                            <form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="aktif"><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><input type="hidden" name="q" value="<?= e($q) ?>"><button class="btn btn-line btn-sm" type="submit"><?= $u['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?></button></form>
                            <form method="post" data-confirm="Reset password pengguna ini?"><?= csrf_field() ?><input type="hidden" name="aksi" value="reset"><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><input type="hidden" name="q" value="<?= e($q) ?>"><button class="btn btn-line btn-sm" type="submit">Reset PW</button></form>
                            <?php if ($super): ?>
                            <form method="post" data-confirm="Hapus pengguna beserta semua karyanya? Tindakan ini permanen."><?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><input type="hidden" name="q" value="<?= e($q) ?>"><button class="btn btn-danger btn-sm" type="submit">Hapus</button></form>
                            <?php endif; ?>
                        </div>
                        <?php else: ?><span class="muted small">—</span><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php layout_end();
