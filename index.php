<?php
require __DIR__ . '/includes/config.php';
redirect(user() ? 'dashboard.php' : 'login.php');
