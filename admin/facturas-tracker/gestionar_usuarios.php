<?php
// La gestión de usuarios ahora es única y vive en la raíz del portal
// (un mismo usuario sirve para Presupuesto y para Control de Facturas).
require __DIR__ . '/../config.php';
header('Location: ' . PORTAL_BASE . '/gestionar_usuarios.php');
exit;
