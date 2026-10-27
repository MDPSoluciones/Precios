<?php
require __DIR__ . '/config.php';
require_login();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inicio - Panel MDP</title>
<link rel="stylesheet" href="assets/style.css?v=3">
</head>
<body>
  <div class="frame-page">
    <div class="contenido-header">
      <h1>Hola, <?= htmlspecialchars(current_user()) ?></h1>
      <p>Elegí una herramienta del menú de la izquierda para empezar.</p>
    </div>

    <div class="tools-grid">
      <a class="tool-card" href="tareas/">
        <div class="tool-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="m8 12.5 2.5 2.5L16 9.5"/><path d="M8 2v4M16 2v4"/></svg></div>
        <h2>Tareas</h2>
        <p>Asignación y seguimiento de tareas del equipo.</p>
      </a>

      <a class="tool-card" href="documentacion/">
        <div class="tool-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4.5A1.5 1.5 0 0 1 5.5 3H11v17H5.5A1.5 1.5 0 0 1 4 18.5Z"/><path d="M20 4.5A1.5 1.5 0 0 0 18.5 3H13v17h5.5a1.5 1.5 0 0 0 1.5-1.5Z"/><path d="M7 7h2M15 7h2"/></svg></div>
        <h2>Documentación</h2>
        <p>Manuales e instructivos para el equipo.</p>
      </a>

      <a class="tool-card" href="presupuesto/">
        <div class="tool-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h6"/></svg></div>
        <h2>Presupuesto</h2>
        <p>Generar presupuestos y remitos para clientes.</p>
      </a>

      <a class="tool-card" href="facturas-tracker/">
        <div class="tool-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"/><path d="M9 4V3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1"/><path d="m9 13 2 2 4-4"/></svg></div>
        <h2>Control de Facturas</h2>
        <p>Seguimiento de compras y facturas A.</p>
      </a>

      <a class="tool-card" href="etiquetas/">
        <div class="tool-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M11 3H4v7l10 10 7-7L11 3Z"/><circle cx="8" cy="8" r="1.4"/></svg></div>
        <h2>Etiquetas</h2>
        <p>Generador de códigos de barra para impresión térmica.</p>
      </a>

      <a class="tool-card" href="cuotas/">
        <div class="tool-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="7" cy="7" r="2.3"/><circle cx="17" cy="17" r="2.3"/><path d="M18 6 6 18"/></svg></div>
        <h2>Simulador de Cuotas</h2>
        <p>Calculadora de ventas con tarjeta y recargos.</p>
      </a>

      <a class="tool-card" href="caja/">
        <div class="tool-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7a2 2 0 0 1 2-2h13a1 1 0 0 1 1 1v3"/><path d="M3 7v11a2 2 0 0 0 2 2h14a1 1 0 0 0 1-1v-4"/><path d="M15 13h4v4h-4a2 2 0 0 1 0-4Z"/></svg></div>
        <h2>Carga de Caja</h2>
        <p>Registro diario de caja y cuenta recaudadora.</p>
      </a>

      <a class="tool-card" href="catalogo/">
        <div class="tool-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13M8 12h13M8 18h13"/><path d="M3.5 6h.01M3.5 12h.01M3.5 18h.01"/></svg></div>
        <h2>Carga de Productos</h2>
        <p>Alta, edición y precios de los productos del sitio.</p>
      </a>

      <a class="tool-card" href="inventario/">
        <div class="tool-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 4l9 5.5V19a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1Z"/></svg></div>
        <h2>Inventario</h2>
        <p>Stock sincronizado con Google Sheets.</p>
      </a>
    </div>
  </div>
</body>
</html>
