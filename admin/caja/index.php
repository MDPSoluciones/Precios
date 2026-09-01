<?php
require __DIR__ . '/../config.php';
require_login();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Carga de Caja - Panel MDP Soluciones</title>
<link rel="icon" href="https://mdpsoluciones.com.ar/images/logoCircDor.png" type="image/x-icon">
<link rel="stylesheet" href="../assets/style.css?v=3">
<style>
  .caja-modal {
    display: none;
    position: fixed; inset: 0;
    background: rgba(48,48,48,0.45);
    z-index: 200;
    align-items: center;
    justify-content: center;
  }
  .caja-modal.abierto { display: flex; }
  .caja-modal-content {
    background: var(--blanco);
    padding: 26px;
    border-radius: var(--radius);
    width: 90%;
    max-width: 320px;
    box-shadow: var(--sombra-hover);
    position: relative;
  }
  .caja-modal-content h3 { font-size: 19px; margin-bottom: 14px; }
  .caja-modal-content input {
    width: 100%;
    margin: 8px 0;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid var(--humo);
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    box-sizing: border-box;
  }
  .caja-modal-close {
    position: absolute; top: 12px; right: 16px;
    font-size: 20px; font-weight: bold;
    color: #999; cursor: pointer; background: none; border: none;
  }
  .caja-iframe-wrap { margin-top: 22px; }
  .caja-iframe-wrap iframe {
    width: 100%; height: 600px; border: none;
    border-radius: 10px;
  }
  #cajaResultado { margin-top: 10px; font-weight: 700; }
  #cajaClipboard { margin-top: 6px; font-size: 13px; color: #7a776e; }
</style>
</head>
<body>
  <div class="frame-page">
    <div class="contenido-header">
      <h1>Carga de Caja</h1>
      <p>Registrá el movimiento del día y calculá la cuenta recaudadora.</p>
    </div>

    <div class="card-simple">
      <button class="btn btn-oro" onclick="document.getElementById('calculatorModal').classList.add('abierto')">Calcular cuenta recaudadora</button>

      <div class="caja-iframe-wrap">
        <iframe src="https://docs.google.com/forms/d/e/1FAIpQLSfQ-0chI034ZnOfl-1vfMuaDzcoLhc83z8lJRKefEUZHDylww/viewform?embedded=true" height="1984" title="Carga de caja">Cargando…</iframe>
      </div>
    </div>
  </div>

<div id="calculatorModal" class="caja-modal">
  <div class="caja-modal-content">
    <button class="caja-modal-close" onclick="document.getElementById('calculatorModal').classList.remove('abierto')">&times;</button>
    <h3>Cuenta recaudadora</h3>
    <input type="number" id="num1" placeholder="Valor en pesos transferencia">
    <input type="number" id="num2" placeholder="Cotización cuenta recaudadora">
    <button class="btn btn-secundario" onclick="calcularCaja()">Calcular</button>
    <p id="cajaResultado"></p>
    <p id="cajaClipboard"></p>
  </div>
</div>

<script src="caja.js"></script>
</body>
</html>
