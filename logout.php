<?php
require __DIR__ . '/includes/config.php';
$_SESSION = [];
session_destroy();
session_start();
flash('ok', 'Anda telah keluar.');
redirect('login.php');
