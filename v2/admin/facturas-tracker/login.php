<?php
// El login ahora es único y vive en la raíz del portal.
require __DIR__ . '/../config.php';
header('Location: ' . PORTAL_BASE . '/login.php');
exit;
