<?php
// El logout ahora es único y vive en la raíz del portal (cierra la sesión de todas las apps).
require __DIR__ . '/../config.php';
header('Location: ' . PORTAL_BASE . '/logout.php');
exit;
