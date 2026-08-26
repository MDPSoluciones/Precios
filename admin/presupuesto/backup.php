<?php
require __DIR__ . '/../config.php';
require_login();

// Genera y descarga un .zip con clientes.json e historial.json
$archivos = ["clientes.json", "historial.json"];
$zipNombre = "backup_presupuestos_" . date("Ymd_His") . ".zip";
$zipPath   = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipNombre;

$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    echo json_encode(["status" => "error", "mensaje" => "No se pudo crear el ZIP"]);
    exit;
}

foreach ($archivos as $archivo) {
    if (file_exists($archivo)) {
        $zip->addFile($archivo, $archivo);
    } else {
        // Agregar archivo vacío para que el ZIP no quede incompleto
        $zip->addFromString($archivo, json_encode([], JSON_PRETTY_PRINT));
    }
}

$zip->close();

// Forzar descarga
header("Content-Type: application/zip");
header("Content-Disposition: attachment; filename=\"$zipNombre\"");
header("Content-Length: " . filesize($zipPath));
header("Pragma: no-cache");
header("Expires: 0");

readfile($zipPath);
unlink($zipPath); // Limpiar archivo temporal
exit;
?>
