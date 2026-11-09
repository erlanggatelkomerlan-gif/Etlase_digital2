<?php
require __DIR__ . '/includes/config.php';
$me = require_super();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $nama = trim($_POST['nama_situs'] ?? '');
    $tag = trim($_POST['tagline'] ?? '');
    if (mb_strlen($nama) < 3 || mb_strlen($nama) > 40) $errors[] = 'Nama situs harus 3-40 karakter.';
    if (mb_strlen($tag) > 80) $errors[] = 'Tagline maksimal 80 karakter.';

    $baru = null;
    if (!$errors && !empty($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        try { $baru = simpan_gambar($_FILES['logo'], 'logo'); }
        catch (RuntimeException $ex) { $errors[] = $ex->getMessage(); }
    }
    if (!$errors) {
        set_setting('nama_situs', $nama);
        set_setting('tagline', $tag);
        $lama = setting('logo');
        if ($baru) {
            hapus_gambar('logo', $lama);
            set_setting('logo', $baru);
        } elseif (!empty($_POST['hapus_logo'])) {
            hapus_gambar('logo', $lama);
            set_setting('logo', '');
        }
        flash('ok', 'Pengaturan situs disimpan.');
        redirect('pengaturan.php');
    }
}

layout_start('Pengaturan Situs', 'pengaturan');
?>
<div class="page-head">
    <div><h1>Pengaturan Situs</h1><p class="muted">Atur logo, nama, dan tagline yang tampil di seluruh website.</p></div>
</div>

<?php foreach ($errors as $er): ?><div class="flash flash-err" role="alert"><?= e($er) ?></div><?php endforeach; ?>

<form method="post" enctype="multipart/form-data" class="form-split">
    <?= csrf_field() ?>
    <div class="card pad">
        <h2 class="card-title">Logo Website</h2>
        <label class="drop drop-logo" for="logo" data-drop>
            <img class="drop-preview" data-preview alt="Logo" <?= setting('logo') !== '' ? 'src="' . e(img_url('logo', setting('logo'))) . '"' : 'hidden' ?>>
            <span class="drop-hint" data-hint <?= setting('logo') !== '' ? 'hidden' : '' ?>>
                <?= icon('upload', 36) ?>
                <b>Unggah logo</b>
                <small>PNG transparan atau persegi, disarankan 512&times;512</small>
            </span>
        </label>
        <input class="sr" type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/webp,image/gif" data-resize data-max="512">
        <?php if (setting('logo') !== ''): ?><label class="check"><input type="checkbox" name="hapus_logo" value="1"> Hapus logo (kembali ke ikon bawaan)</label><?php endif; ?>

        <div class="logo-preview">
            <span class="muted small">Pratinjau di menu samping</span>
            <div class="brand brand-demo"><?= logo_html(44) ?><span class="brand-text"><?= e(setting('nama_situs', 'Etalase Stella')) ?><small><?= e(setting('tagline', 'Ruang pamer karya siswa')) ?></small></span></div>
        </div>
    </div>

    <div class="card pad">
        <h2 class="card-title">Identitas</h2>
        <div class="field"><label for="nama_situs">Nama Situs</label><input class="input" id="nama_situs" name="nama_situs" maxlength="40" value="<?= e(setting('nama_situs', 'Etalase Stella')) ?>" required></div>
        <div class="field"><label for="tagline">Tagline</label><input class="input" id="tagline" name="tagline" maxlength="80" value="<?= e(setting('tagline', 'Ruang pamer karya siswa')) ?>"></div>
        <div class="actions"><button class="btn btn-red" type="submit">Simpan Pengaturan</button></div>
    </div>
</form>
<?php layout_end();
