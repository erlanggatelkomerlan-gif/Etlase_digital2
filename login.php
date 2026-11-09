<?php
require __DIR__ . '/includes/config.php';

if (user()) redirect('dashboard.php');

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    $st = db()->prepare('SELECT * FROM users WHERE username = ?');
    $st->execute([$username]);
    $u = $st->fetch();

    if ($u && password_verify($password, $u['password'])) {
        if (!(int) $u['aktif']) {
            $error = 'Akun Anda dinonaktifkan. Hubungi admin.';
        } else {
            session_regenerate_id(true);
            $_SESSION['uid'] = (int) $u['id'];
            flash('ok', 'Selamat datang kembali, ' . $u['nama'] . '!');
            redirect('dashboard.php');
        }
    } else {
        $error = 'Username atau password salah.';
    }
}

auth_start('Masuk');
?>
<span class="eyebrow">Selamat Datang</span>
<h2>Masuk ke akun Anda</h2>
<p class="sub">Gunakan username dan password untuk melanjutkan.</p>

<?php if ($error): ?><div class="flash flash-err" role="alert"><?= e($error) ?></div><?php endif; ?>

<form method="post" autocomplete="on">
    <?= csrf_field() ?>
    <div class="field">
        <label for="username">Username</label>
        <input class="input" type="text" id="username" name="username" value="<?= e($username) ?>" placeholder="Masukkan username" autocomplete="username" required autofocus>
    </div>
    <div class="field">
        <label for="password">Password</label>
        <div class="pw">
            <input class="input" type="password" id="password" name="password" placeholder="Masukkan password" autocomplete="current-password" required>
            <button type="button" class="pw-toggle" data-toggle-pw aria-label="Tampilkan password"><?= icon('eye', 18) ?></button>
        </div>
    </div>
    <button type="submit" class="btn btn-red btn-block">Masuk</button>
</form>

<p class="switch">Belum punya akun? <a href="register.php">Daftar sebagai siswa</a></p>
<?php auth_end();
