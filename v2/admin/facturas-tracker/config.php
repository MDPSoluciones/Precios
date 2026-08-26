<?php
// config.php de esta app — ahora delega sesión/login/usuarios en el config.php
// compartido de la raíz del portal. Solo define lo que es propio de esta app.

require __DIR__ . '/../config.php';

define('DATA_DIR', __DIR__ . '/data');
define('COMPRAS_FILE', DATA_DIR . '/compras.json');
