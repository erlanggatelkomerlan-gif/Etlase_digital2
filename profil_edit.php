<?php
require __DIR__ . '/includes/config.php';
$me = require_login();

$errors = [];
$pwErrors = [];
$v = ['nama' => $me['nama'], 'kelas' => $me['kelas'], 'bio' => $me['bio']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $aksi = $_POST['aksi'] ?? 'profil';

    if ($aksi === 'profil') {
        $v['nama'] = trim($_POST['nama'] ?? '');
        $v['kelas'] = trim($_POST['kelas'] ?? '');
        $v['bio'] = trim($_POST['bio'] ?? '');
        if (mb_strlen($v['nama']) < 3 || mb_strlen($v['nama']) > 60) $errors[] = 'Nama harus 3-60 karakter.';
        if (mb_strlen($v['kelas']) > 20) $errors[] = 'Kelas maksimal 20 karakter.';
        if (mb_strlen($v['bio']) > 300) $errors[] = 'Bio maksimal 300 karakter.';

        $avatar = $me['avatar'];
        $baru = null;
        if (!$errors && !empty($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
            try { $baru = simpan_gambar($_FILES['avatar'], 'avatar'); }
            catch (RuntimeException $ex) { $errors[] = $ex->getMessage(); }
        }
        if (!$errors) {
            if ($baru) { hapus_gambar('avatar', $avatar); $avatar = $baru; }
            elseif (!empty($_POST['hapus_avatar'])) { hapus_gambar('avatar', $avatar); $avatar = ''; }
            db()->prepare('UPDATE users SET nama=?, kelas=?, bio=?, avatar=? WHERE id=?')
                ->execute([$v['nama'], $v['kelas'], $v['bio'], $avatar, $me['id']]);
            flash('ok', 'Profil berhasil diperbarui.');
            redirect('profil.php');
        }
    } elseif ($aksi === 'password') {
        $lama = (string) ($_POST['lama'] ?? '');
        $baru = (string) ($_POST['baru'] ?? '');
        $ulang = (string) ($_POST['ulang'] ?? '');
        if (!password_verify($lama, $me['password'])) $pwErrors[] = 'Password lama salah.';
        if (strlen($baru) < 6) $pwErrors[] = 'Password baru minimal 6 karakter.';
        if ($baru !== $ulang) $pwErrors[] = 'Konfirmasi password baru tidak sama.';
        if (!$pwErrors) {
            db()->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($baru, PASSWORD_DEFAULT), $me['id']]);
            flash('ok', 'Password berhasil diganti.');
            redirect('profil.php');
        }
    }
}

layout_start('Edit Profil', 'profil');
?>
<div class="page-head">
    <div><h1>Edit Profil</h1><p class="muted">Perbarui foto, identitas, dan keamanan akun Anda.</p></div>
    <a href="profil.php" class="btn btn-line">Kembali</a>
</div>

<div class="form-split form-split-profile">
    <form method="post" enctype="multipart/form-data" class="card pad">
        <?= csrf_field() ?><input type="hidden" name="aksi" value="profil">
        <h2 class="card-title">Informasi Profil</h2>
        <?php foreach ($errors as $er): ?><div class="flash flash-err" role="alert"><?= e($er) ?></div><?php endforeach; ?>

        <div class="avatar-edit">
            <span data-avatar-preview><?= avatar_html($me, 96, 'avatar-xl') ?></span>
            <div>
                <label class="btn btn-line btn-sm" for="avatar"><?= icon('upload', 16) ?> Ganti Foto</label>
                <input class="sr" type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif" data-resize data-avatar-input>
                <?php if ($me['avatar']): ?>
                    <label class="check"><input type="checkbox" name="hapus_avatar" value="1"> Hapus foto</label>
                <?php endif; ?>
                <p class="muted small">JPG, PNG, atau WEBP.</p>
            </div>
        </div>

        <div class="field"><label>Username</label><input class="input" type="text" value="<?= e($me['username']) ?>" disabled></div>
        <div class="field"><label for="nama">Nama Lengkap</label><input class="input" type="text" id="nama" name="nama" value="<?= e($v['nama']) ?>" required></div>
        <div class="field"><label for="kelas">Kelas</label><input class="input" type="text" id="kelas" name="kelas" value="<?= e($v['kelas']) ?>" placeholder="XI IPA 2"></div>
        <div class="field"><label for="bio">Bio</label><textarea class="input" id="bio" name="bio" rows="4" maxlength="300" placeholder="Ceritakan sedikit tentang dirimu..."><?= e($v['bio']) ?></textarea></div>
        <div class="actions"><button class="btn btn-red" type="submit">Simpan Profil</button></div>
    </form>

    <form method="post" class="card pad">
        <?= csrf_field() ?><input type="hidden" name="aksi" value="password">
        <h2 class="card-title"><?= icon('key', 20) ?> Ganti Password</h2>
        <?php foreach ($pwErrors as $er): ?><div class="flash flash-err" role="alert"><?= e($er) ?></div><?php endforeach; ?>
        <div class="field"><label for="lama">Password Lama</label><input class="input" type="password" id="lama" name="lama" autocomplete="current-password" required></div>
        <div class="field"><label for="baru">Password Baru</label><input class="input" type="password" id="baru" name="baru" autocomplete="new-password" required></div>
        <div class="field"><label for="ulang">Ulangi Password Baru</label><input class="input" type="password" id="ulang" name="ulang" autocomplete="new-password" required></div>
        <div class="actions"><button class="btn btn-dark" type="submit">Ganti Password</button></div>
    </form>
</div>
<?php layout_end();
