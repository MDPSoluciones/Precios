<?php
require __DIR__ . '/../config.php';
require_login();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presupuesto - AXENTIA</title>
    <link rel="stylesheet" href="presupuesto.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
</head>

<body>

    <header>
        <span id="header_nombre">AXENTIA</span>
        <div class="switch-container">
            <span>AX</span>
            <label class="switch">
                <input type="checkbox" id="empresa_toggle" onchange="cambiarEmpresa()">
                <span class="slider"></span>
            </label>
            <span>MDP</span>
        </div>
        <span style="font-size:13px; opacity:.75; margin-left:12px;">
            Hola, <?= htmlspecialchars(current_user()) ?>
        </span>
    </header>

    <main>
        <!-- Empresa -->
        <section>
            <h2>Datos de la Empresa</h2>
            <label>Nombre</label>
            <input type="text" id="empresa_nombre" value="AXENTIA S.R.L." readonly>
            <label>CUIT</label>
            <input type="text" id="empresa_cuit" value="30-71902891-4" readonly>
            <label>Dirección</label>
            <input type="text" id="empresa_direccion" value="Marinero Sosa 854" readonly>
            <label>Email</label>
            <input type="email" id="empresa_mail" value="adm.axentia@gmail.com" readonly>
            <label>Teléfono</label>
            <input type="text" id="empresa_tel" value="+54 9 3364 24-9663" readonly>
        </section>

        <!-- Cliente -->
        <section>
            <h2>Datos del Cliente</h2>
            <label>Nombre</label>
            <div style="position:relative;">
                <input type="text" id="cliente_nombre" autocomplete="off">
                <div id="sugerencias_clientes"
                    style="position:absolute; top:100%; left:0; right:0; background:#fff; border:1px solid #ccc; display:none; z-index:1000; border-radius:0 0 10px 10px;">
                </div>
            </div>
            <label>CUIT</label>
            <input type="text" id="cliente_cuit">
            <label>Dirección</label>
            <input type="text" id="cliente_direccion">
            <label>Email</label>
            <input type="email" id="cliente_mail">
        </section>

        <!-- Presupuesto -->
        <section>
            <h2>Datos del Presupuesto</h2>
            <label>Moneda</label>
            <select id="moneda" onchange="actualizarTabla(this.value)">
                <option value="ARS">ARS</option>
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
            </select>
            <label>Fecha</label>
            <input type="date" id="fecha">
            <label>Validez (días)</label>
            <input type="number" id="validez" min="1" placeholder="Ej: 30" value="5">
            <label>Forma de pago</label>
            <select id="pago">
                <option>Cuenta Corriente</option>
                <option>Efectivo</option>
                <option>Transferencia</option>
                <option>Cheque</option>
            </select>
            <label>Notas / Observaciones</label>
            <textarea id="notas" rows="3" placeholder="Condiciones especiales, aclaraciones de entrega, etc."></textarea>
        </section>
    </main>

    <!-- Productos -->
    <section class="sec-productos">
        <h2>Productos</h2>

        <div id="form_productos">
            <input type="text"   id="producto_desc"   placeholder="Descripción">
            <input type="number" id="producto_cant"   placeholder="Cantidad" min="1">
            <input type="number" id="producto_precio" placeholder="Precio unitario" step="0.01">
            <input type="number" id="producto_iva"    placeholder="IVA (%)" value="10.5">
            <div class="form-prod-botones">
                <div id="aviso_edicion"></div>
                <button id="btn_agregar_prod" onclick="agregarProducto()">Agregar producto</button>
                <button id="btn_cancelar_edicion" class="btn-secundario" onclick="cancelarEdicion()" style="display:none;">Cancelar edición</button>
            </div>
        </div>

        <div class="tabla-wrap">
            <table id="tabla_productos">
                <thead>
                    <tr>
                        <th>Descripción</th>
                        <th>Cant</th>
                        <th>Precio Base</th>
                        <th>Sub-Tot Base</th>
                        <th>IVA (%)</th>
                        <th>Total IVA</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <div class="totales">
            <h3>Total Neto: <span id="total">0</span> <span class="simbolo_moneda">ARS</span></h3>
            <h3>Total IVA: <span id="iva_total">0</span> <span class="simbolo_moneda">ARS</span></h3>
            <h2>Total Final: <span id="total_final">0</span> <span class="simbolo_moneda">ARS</span></h2>
        </div>
    </section>

    <div class="acciones-presupuesto">
        <button onclick="generarPresupuesto()">✔ Generar Presupuesto</button>
        <button onclick="descargarPDF()" class="btn-secundario">⬇ PDF Presupuesto</button>
        <button onclick="descargarRemito()" class="btn-secundario">⬇ PDF Remito</button>
        <button onclick="nuevoPresupuesto()" class="btn-nuevo">🆕 Nuevo Presupuesto</button>
    </div>

    <div id="resultado"></div>

    <!-- Historial -->
    <section id="historial">
        <div class="historial-encabezado">
            <h2>Historial de Presupuestos</h2>
            <div class="historial-herramientas">
                <button class="btn-tool" onclick="abrirModalClientes()">👥 Clientes</button>
                <a href="backup.php" class="btn-tool btn-link">💾 Backup</a>
            </div>
        </div>
        <input type="text" id="filtro_cliente" placeholder="Buscar por cliente o código..." onkeyup="mostrarHistorial()">
        <ul id="lista_historial"></ul>
    </section>

    <!-- Modal gestión de clientes -->
    <div id="modal-clientes" class="modal-overlay" onclick="cerrarModalSiOverlay(event)">
        <div class="modal-box">
            <div class="modal-header">
                <h2>Gestión de Clientes</h2>
                <button class="btn-cerrar-modal" onclick="cerrarModalClientes()">✕</button>
            </div>
            <div class="modal-body" id="modal-clientes-contenido"></div>
        </div>
    </div>

    <script src="presupuesto.js"></script>

</body>
</html>
