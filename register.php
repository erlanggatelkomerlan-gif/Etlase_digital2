<?php
require __DIR__ . '/includes/config.php';

if (user()) redirect('dashboard.php');

$errors = [];
$v = ['nama' => '', 'username' => '', 'kelas' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $v['nama'] = trim($_POST['nama'] ?? '');
    $v['username'] = trim($_POST['username'] ?? '');
    $v['kelas'] = trim($_POST['kelas'] ?? '');
    $pw = (string) ($_POST['password'] ?? '');
    $pw2 = (string) ($_POST['password2'] ?? '');

    if (mb_strlen($v['nama']) < 3) $errors[] = 'Nama lengkap minimal 3 karakter.';
    if (!preg_match('/^[A-Za-z0-9_.]{4,20}$/', $v['username'])) $errors[] = 'Username 4-20 karakter: huruf, angka, titik, atau garis bawah.';
    if (mb_strlen($v['kelas']) > 20) $errors[] = 'Kelas maksimal 20 karakter.';
    if (strlen($pw) < 6) $errors[] = 'Password minimal 6 karakter.';
    if ($pw !== $pw2) $errors[] = 'Konfirmasi password tidak sama.';

    if (!$errors) {
        $cek = db()->prepare('SELECT 1 FROM users WHERE username = ?');
        $cek->execute([$v['username']]);
        if ($cek->fetch()) $errors[] = 'Username sudah dipakai, pilih yang lain.';
    }

    if (!$errors) {
        $st = db()->prepare("INSERT INTO users (username, nama, password, role, kelas) VALUES (?,?,?,'siswa',?)");
        $st->execute([$v['username'], $v['nama'], password_hash($pw, PASSWORD_DEFAULT), $v['kelas']]);
        session_regenerate_id(true);
        $_SESSION['uid'] = (int) db()->lastInsertId();
        flash('ok', 'Akun berhasil dibuat. Selamat datang di Etalase!');
        redirect('dashboard.php');
    }
}

auth_start('Daftar');
?>
<span class="eyebrow">Akun Siswa</span>
<h2>Buat akun baru</h2>
<p class="sub">Daftar gratis untuk mulai memamerkan karyamu.</p>

<?php foreach ($errors as $er): ?><div class="flash flash-err" role="alert"><?= e($er) ?></div><?php endforeach; ?>

<form method="post">
    <?= csrf_field() ?>
    <div class="field">
        <label for="nama">Nama Lengkap</label>
        <input class="input" type="text" id="nama" name="nama" value="<?= e($v['nama']) ?>" placeholder="Contoh: Siti Nurhaliza" required autofocus>
    </div>
    <div class="row-2">
        <div class="field">
            <label for="username">Username</label>
            <input class="input" type="text" id="username" name="username" value="<?= e($v['username']) ?>" placeholder="siti.nur" autocomplete="username" required>
        </div>
        <div class="field">
            <label for="kelas">Kelas</label>
            <input class="input" type="text" id="kelas" name="kelas" value="<?= e($v['kelas']) ?>" placeholder="XI IPA 2">
        </div>
    </div>
    <div class="row-2">
        <div class="field">
            <label for="password">Password</label>
            <input class="input" type="password" id="password" name="password" placeholder="Min. 6 karakter" autocomplete="new-password" required>
        </div>
        <div class="field">
            <label for="password2">Ulangi Password</label>
            <input class="input" type="password" id="password2" name="password2" placeholder="Ketik ulang" autocomplete="new-password" required>
        </div>
    </div>
    <button type="submit" class="btn btn-red btn-block">Daftar &amp; Masuk</button>
</form>

<p class="switch">Sudah punya akun? <a href="login.php">Masuk di sini</a></p>
<?php auth_end();
