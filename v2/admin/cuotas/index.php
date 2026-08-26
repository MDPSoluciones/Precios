<?php
require __DIR__ . '/../config.php';
require_login();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <title>Calculadora Ventas con Tarjeta</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />

    <!-- Ícono del sitio -->
    <link rel="icon" href="https://mdpsoluciones.com.ar/images/card.ico" type="image/x-icon">

    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #D9DAD4;
            color: #222;
            padding: 24px;
        }

        header {
            background: #303030;
            color: #F5F1E8;
            padding: 20px;
            text-align: center;
            font-size: 2rem;
            font-weight: bold;
            position: relative;
        }

        .panel {
            max-width: 100%;
            margin: 0 auto;
            background: #F5F1E8;
            border-radius: 10px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
            padding: 20px;
        }

        h1 {
            margin: 0 0 12px 0;
            font-size: 20px;
        }

        .controls {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 16px;
            align-items: end;
        }

        .controls label {
            display: block;
            font-size: 13px;
            margin-bottom: 6px;
            color: #444;
        }

        .control {
            display: flex;
            flex-direction: column;
        }

        input[type="number"],
        input[type="text"],
        select {
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid #d0d6dc;
            width: 180px;
            box-sizing: border-box;
            font-size: 14px;
        }

        button {
            background: #BEA167;
            color: #fff;
            border: none;
            padding: 10px 14px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
        }

        button.secondary {
            background: #303030;
        }

        button.link {
            background: none;
            color: #666;
            padding: 4px 6px;
            font-size: 12px;
            text-decoration: underline;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            font-size: 14px;
        }

        th,
        td {
            padding: 10px 8px;
            border-bottom: 1px solid #e6e9ed;
            text-align: right;
            vertical-align: middle;
        }

        th:first-child,
        td:first-child {
            text-align: left;
            width: 160px;
        }

        th {
            background: #fafbfc;
            font-weight: 600;
            color: #333;
            font-size: 13px;
        }

        .small {
            font-size: 12px;
            color: #666;
        }

        /* read-only styling */
        input[readonly] {
            background: transparent;
            border: none;
            color: inherit;
        }

        /* responsive */
        @media (max-width: 880px) {
            .controls {
                flex-direction: column;
            }

            input[type="number"],
            input[type="text"] {
                width: 100%;
            }
        }

        /* --- Tabs --- */
        .tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 18px;
            border-bottom: 1px solid #e6e9ed;
        }

        .tab-btn {
            background: none;
            color: #666;
            border: none;
            padding: 10px 16px;
            cursor: pointer;
            font-size: 14px;
            border-bottom: 3px solid transparent;
            border-radius: 0;
        }

        .tab-btn.active {
            color: #222;
            font-weight: 700;
            border-bottom: 3px solid #BEA167;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* --- Simulador de cobro --- */
        .sim-layout {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            align-items: flex-start;
        }

        .sim-form {
            flex: 1 1 420px;
        }

        .sim-result {
            flex: 1 1 320px;
            background: #fafbfc;
            border-radius: 10px;
            padding: 16px 18px;
            border: 1px solid #e6e9ed;
        }

        .result-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #e6e9ed;
        }

        .result-row .label {
            display: block;
            color: #333;
            font-size: 14px;
        }

        .result-row .label .row-tag {
            font-size: 10px;
            font-weight: 400;
            color: #8a8a8a;
        }

        .result-row .sub {
            display: block;
            font-size: 12px;
            color: #777;
            margin-top: 2px;
        }

        .result-row .value {
            white-space: nowrap;
            font-size: 14px;
        }

        .result-row.total {
            border-bottom: none;
            padding-top: 14px;
            font-weight: 700;
        }

        .result-row.total .label,
        .result-row.total .value {
            font-size: 17px;
        }

        .whatsapp-box {
            margin-top: 20px;
            background: #fafbfc;
            border-radius: 10px;
            padding: 16px 18px;
            border: 1px solid #e6e9ed;
        }

        .whatsapp-box h3 {
            margin: 0 0 10px 0;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #666;
        }

        .whatsapp-text {
            white-space: pre-wrap;
            font-family: inherit;
            font-size: 14px;
            line-height: 1.5;
            margin: 0 0 14px 0;
            background: #fff;
            border: 1px solid #e6e9ed;
            border-radius: 8px;
            padding: 12px 14px;
        }

        .whatsapp-actions {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .copied-msg {
            display: none;
        }

        .dolar-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #fff;
            border: 1px solid #e6e9ed;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 14px;
            color: #333;
        }

        .dolar-refresh-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 15px;
            padding: 2px 4px;
            line-height: 1;
            color: #666;
        }

        .dolar-refresh-btn:hover {
            color: #222;
        }

        .toast {
            position: fixed;
            left: 50%;
            bottom: 32px;
            transform: translateX(-50%) translateY(12px);
            background: #303030;
            color: #F5F1E8;
            padding: 12px 22px;
            border-radius: 8px;
            font-size: 14px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease, transform 0.25s ease;
            z-index: 2000;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        .platform-panel {
            display: none;
        }

        .platform-panel.active {
            display: block;
        }

        .hint {
            background: #eef1f4;
            border-left: 4px solid #8aa2b8;
            padding: 10px 12px;
            font-size: 12px;
            color: #444;
            border-radius: 4px;
            margin-bottom: 16px;
        }

        /* --- Modal de configuración --- */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }

        .modal-overlay.open {
            display: flex;
        }

        .modal-box {
            background: #fff;
            border-radius: 10px;
            width: 100%;
            max-width: 760px;
            max-height: 88vh;
            overflow-y: auto;
            padding: 22px 24px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.25);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 19px;
        }

        .modal-close {
            background: none;
            color: #666;
            font-size: 22px;
            line-height: 1;
            padding: 2px 6px;
            border-radius: 6px;
        }

        .modal-close:hover {
            background: #f0f0f0;
        }

        .modal-body h3 {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #666;
            margin: 20px 0 8px 0;
            border-top: 1px solid #e6e9ed;
            padding-top: 16px;
        }

        .modal-body h3:first-of-type {
            margin-top: 12px;
            border-top: none;
            padding-top: 0;
        }

        .modal-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-top: 18px;
            padding-top: 14px;
            border-top: 1px solid #e6e9ed;
            flex-wrap: wrap;
        }

        .modal-footer .right-actions {
            display: flex;
            gap: 8px;
        }
    </style>
</head>

<body>
    <header>
        <span id="header_nombre">MDP SOLUCIONES</span>
    </header>
    <div class="panel">

        <div class="tabs">
            <button class="tab-btn active" id="tabbtn-simulador" onclick="showTab('simulador')">Simulador de
                cobro</button>
            <button class="tab-btn" id="tabbtn-cuotas" onclick="showTab('cuotas')">Calculadora USD / Cuotas</button>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
            <div class="dolar-badge" id="dolar-badge" title="Fuente: DolarAPI.com">
                💵 Dólar de referencia: <strong id="dolar-badge-valor">-</strong>
                <span class="small" id="dolar-badge-info"></span>
                <button class="dolar-refresh-btn" onclick="actualizarDolar(true)" title="Actualizar cotización">🔄</button>
            </div>
            <button onclick="openConfigModal()">⚙ Configurar</button>
        </div>

        <!-- ===================== TAB 1: Calculadora USD / Cuotas ===================== -->
        <div id="tab-cuotas" class="tab-content">
            <h1>Calculadora Ventas con Tarjeta</h1>

            <div class="controls">
                <div class="control">
                    <label>Producto</label>
                    <input id="producto" type="text" value="" />
                </div>

                <div class="control">
                    <label>Valor en USD</label>
                    <input id="usd" type="number" step="1" value="500" />
                </div>

                <div style="display:flex; align-items:center; gap:8px;">
                    <button onclick="generarTabla()">Generar tabla</button>
                </div>
            </div>

            <!-- Tabla read-only -->
            <table id="tabla-resultados" aria-label="Resultados">
                <thead>
                    <tr>
                        <th>PRODUCTO</th>
                        <th>PRECIO<br><span class="small">EFECTIVO (USD)</span></th>
                        <th>PRECIO PESOS<br><span class="small">(A DÓLAR BLUE VENTA)</span></th>
                        <th>PRECIO TRENSFERENCIA</th>
                        <th>IVA (10.5%)</th>
                        <th>IIBB (3,5%)</th>
                        <th>PRECIO FACTURADO<br><span class="small">(EFT/TRF)</span></th>
                        <th id="th-en1">EN 1 CUOTA</th>
                        <th id="th-en3">EN 3 CUOTAS</th>
                        <th id="th-en6">EN 6 CUOTAS</th>
                        <th id="th-en12">EN 12 CUOTAS</th>
                    </tr>
                </thead>
                <tbody id="tbody-results">
                    <!-- se completa dinámicamente -->
                </tbody>
            </table>

            <p class="small" id="notas" style="margin-top:12px;">
                Notas: <strong>P. transferencia</strong> = pesos + transf %. <strong>P. Facturado</strong> = pesos +
                IVA +
                IIBB. Las cuotas aplican
                recargos: EN 1 = +10%, EN 3 = +25%, EN 6 = +35%, EN 12 = +45%.
                Estos porcentajes se editan desde "⚙ Configurar".
            </p>
        </div>

        <!-- ===================== TAB 2: Simulador de cobro ===================== -->
        <div id="tab-simulador" class="tab-content active">
            <h1>Simulador de cobro</h1>
            <div class="hint">
                Ingresá cuánto querés <strong>recibir</strong> en tu cuenta y el simulador calcula cuánto le
                tenés que <strong>cobrar</strong> al cliente para cubrir el costo de la plataforma, las cuotas
                sin interés y las retenciones. Los porcentajes de cada plataforma se editan desde
                "⚙ Configurar". Si vendés a través de un revendedor, cargá su margen (%): se calcula sobre
                lo que querés recibir, y se le descuenta el impuesto extra que genera ese margen, así vos
                terminás recibiendo exactamente tu monto.
            </div>

            <div class="sim-layout">
                <div class="sim-form">
                    <div class="controls">
                        <div class="control">
                            <label>Plataforma</label>
                            <select id="sim_plataforma" onchange="showPlatform()">
                                <option value="mercadopago">Mercado Pago (Point)</option>
                                <option value="transferencia">Transferencia / Efectivo</option>
                            </select>
                        </div>

                        <div class="control">
                            <label>¿Cuánto querés recibir?</label>
                            <div style="display:flex; gap:6px;">
                                <input id="sim_recibir" type="number" step="1" placeholder="Ej: 100000"
                                    style="flex:1; min-width:0;" oninput="actualizarConversionRecibir()" />
                                <select id="sim_recibir_moneda" style="width:92px;"
                                    onchange="actualizarConversionRecibir()">
                                    <option value="ARS">$ ARS</option>
                                    <option value="USD">USD</option>
                                </select>
                            </div>
                            <span class="small" id="sim_recibir_conversion"></span>
                        </div>

                        <div class="control">
                            <label>Margen revendedor (%)<br><span class="small">opcional</span></label>
                            <input id="sim_revendedorPct" type="number" step="0.1" value="0" />
                        </div>

                        <div class="control" id="mp_cuotasSelect_wrap">
                            <label>¿En cuántas cuotas ofrecés?</label>
                            <select id="mp_cuotasSelect">
                                <option value="1">Pago único</option>
                                <option value="2">2 cuotas</option>
                                <option value="3">3 cuotas</option>
                                <option value="6">6 cuotas</option>
                                <option value="9">9 cuotas</option>
                                <option value="12">12 cuotas</option>
                                <option value="18">18 cuotas</option>
                            </select>
                        </div>

                        <div style="display:flex; align-items:center; gap:8px;">
                            <button onclick="calcularSimulador()">Calcular</button>
                        </div>
                    </div>
                </div>

                <div class="sim-result">
                    <div id="sim-result-body">
                        <p class="small">Completá los datos y presioná "Calcular" para ver cuánto deberías
                            cobrar.</p>
                    </div>

                    <div class="whatsapp-box" id="whatsapp-box" style="display:none;">
                        <h3>Mensaje para enviar (WhatsApp / Instagram)</h3>
                        <pre class="whatsapp-text" id="whatsapp-text"></pre>
                        <div class="whatsapp-actions">
                            <button onclick="copiarMensajeWhatsApp()">📋 Copiar mensaje WhatsApp</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- ===================== MODAL: Configuración ===================== -->
    <div class="modal-overlay" id="configModal">
        <div class="modal-box">
            <div class="modal-header">
                <h2>Configuración</h2>
                <button class="modal-close" onclick="closeConfigModal()">&times;</button>
            </div>
            <p class="small">Estos son los porcentajes y tasas que usan las calculadoras. Se guardan solo al
                presionar "Guardar".</p>

            <div class="modal-body">
                <h3>Calculadora USD / Cuotas</h3>
                <div class="controls">
                    <div class="control">
                        <label>Dólar de referencia</label>
                        <input id="dolar" type="number" step="1" value="1450" />
                    </div>
                    <div class="control">
                        <label>Transferencia (%)</label>
                        <input id="transferPct" type="number" step="0.1" value="3" />
                    </div>
                    <div class="control">
                        <label>IVA (%)</label>
                        <input id="ivaPct" type="number" step="0.1" value="10.5" />
                    </div>
                    <div class="control">
                        <label>IIBB (%)</label>
                        <input id="iibbPct" type="number" step="0.1" value="3.5" />
                    </div>
                </div>
                <div class="controls">
                    <div class="control">
                        <label>Recargo 1 cuota (%)</label>
                        <input id="pct1" type="number" step="0.1" value="10" />
                    </div>
                    <div class="control">
                        <label>Recargo 3 cuotas (%)</label>
                        <input id="pct3" type="number" step="0.1" value="25" />
                    </div>
                    <div class="control">
                        <label>Recargo 6 cuotas (%)</label>
                        <input id="pct6" type="number" step="0.1" value="35" />
                    </div>
                    <div class="control">
                        <label>Recargo 12 cuotas (%)</label>
                        <input id="pct12" type="number" step="0.1" value="45" />
                    </div>
                </div>

                <h3>Mercado Pago (Point)</h3>
                <div class="controls">
                    <div class="control">
                        <label>Costo por cobro (%)<br><span class="small">en el momento</span></label>
                        <input id="mp_costoCobro" type="number" step="0.01" value="4.98" />
                    </div>
                    <div class="control">
                        <label>IVA sobre comisiones (%)</label>
                        <input id="mp_ivaComision" type="number" step="0.1" value="21" />
                    </div>
                </div>
                <label class="small" style="display:block; margin-bottom:6px;">Costo por ofrecer cuotas sin
                    interés (%), según cantidad de cuotas:</label>
                <div class="controls">
                    <div class="control">
                        <label>2 cuotas (%)</label>
                        <input id="mp_pct2" type="number" step="0.01" value="3.30" />
                    </div>
                    <div class="control">
                        <label>3 cuotas (%)</label>
                        <input id="mp_pct3" type="number" step="0.01" value="4.50" />
                    </div>
                    <div class="control">
                        <label>6 cuotas (%)</label>
                        <input id="mp_pct6" type="number" step="0.01" value="10.30" />
                    </div>
                    <div class="control">
                        <label>9 cuotas (%)</label>
                        <input id="mp_pct9" type="number" step="0.01" value="15.30" />
                    </div>
                    <div class="control">
                        <label>12 cuotas (%)</label>
                        <input id="mp_pct12" type="number" step="0.01" value="19.50" />
                    </div>
                    <div class="control">
                        <label>18 cuotas (%)</label>
                        <input id="mp_pct18" type="number" step="0.01" value="26.10" />
                    </div>
                </div>
                <label class="small" style="display:block; margin-bottom:6px;">Retenciones:</label>
                <div class="controls">
                    <div class="control">
                        <label>Imp. Créditos y Débitos (%)</label>
                        <input id="mp_retCD" type="number" step="0.01" value="0.6" />
                    </div>
                    <div class="control">
                        <label>IIBB Régimen SIRTAC (%)</label>
                        <input id="mp_retIIBB" type="number" step="0.01" value="0.4" />
                    </div>
                </div>

                <h3>Transferencia / Efectivo</h3>
                <div class="controls">
                    <div class="control">
                        <label>Costo por transferencia (%)</label>
                        <input id="tr_costo" type="number" step="0.01" value="0" />
                    </div>
                    <div class="control">
                        <label>IVA (%)</label>
                        <input id="tr_iva" type="number" step="0.1" value="0" />
                    </div>
                    <div class="control">
                        <label>IIBB (%)</label>
                        <input id="tr_iibb" type="number" step="0.1" value="0" />
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <div class="left-actions">
                    <button class="link" onclick="resetConfigDefaults()">Restablecer valores por defecto</button>
                    <button class="link" onclick="exportConfig()">Exportar .json</button>
                    <button class="link" onclick="document.getElementById('importFile').click()">Importar
                        .json</button>
                    <input type="file" id="importFile" accept="application/json" style="display:none"
                        onchange="importConfig(event)">
                </div>
                <div class="right-actions">
                    <button class="secondary" onclick="closeConfigModal()">Cancelar</button>
                    <button onclick="guardarConfiguracion()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== TOAST: notificación flotante ===================== -->
    <div class="toast" id="toast"></div>

    <script>
        // Formato de números (máx 2 decimales, no mostrar .00 innecesario)
        function fmt(n) {
            if (typeof n !== "number" || !isFinite(n)) return "-";
            const opts = { minimumFractionDigits: 0, maximumFractionDigits: 2 };
            return n.toLocaleString('es-AR', opts);
        }

        function generarTabla() {
            const producto = document.getElementById('producto').value || '';
            const usd = parseFloat(document.getElementById('usd').value) || 0;
            const dolar = parseFloat(document.getElementById('dolar').value) || 1;
            const transferPct = (parseFloat(document.getElementById('transferPct').value) || 0) / 100;
            const ivaPct = (parseFloat(document.getElementById('ivaPct').value) || 0) / 100;
            const iibbPct = (parseFloat(document.getElementById('iibbPct').value) || 0) / 100;

            const pct1 = parseFloat(document.getElementById('pct1').value) || 0;
            const pct3 = parseFloat(document.getElementById('pct3').value) || 0;
            const pct6 = parseFloat(document.getElementById('pct6').value) || 0;
            const pct12 = parseFloat(document.getElementById('pct12').value) || 0;

            const precioPesos = usd * dolar;
            const precioTransferido = precioPesos * (1 + transferPct);

            const iva = precioPesos * ivaPct;
            const iibb = precioPesos * iibbPct;

            const precioFacturado = precioPesos + iva + iibb;

            const mult1 = 1 + pct1 / 100;
            const mult3 = 1 + pct3 / 100;
            const mult6 = 1 + pct6 / 100;
            const mult12 = 1 + pct12 / 100;

            const en1 = precioFacturado * mult1;
            const en3 = precioFacturado * mult3;
            const en6 = precioFacturado * mult6;
            const en12 = precioFacturado * mult12;

            const cuota3 = en3 / 3;
            const cuota6 = en6 / 6;
            const cuota12 = en12 / 12;

            document.getElementById('th-en1').innerHTML = `EN 1 CUOTA<br><span class="small">(+${fmtPct(pct1)}%)</span>`;
            document.getElementById('th-en3').innerHTML = `EN 3 CUOTAS<br><span class="small">(+${fmtPct(pct3)}%)</span>`;
            document.getElementById('th-en6').innerHTML = `EN 6 CUOTAS<br><span class="small">(+${fmtPct(pct6)}%)</span>`;
            document.getElementById('th-en12').innerHTML = `EN 12 CUOTAS<br><span class="small">(+${fmtPct(pct12)}%)</span>`;
            document.getElementById('notas').innerHTML =
                `Notas: <strong>P. transferencia</strong> = pesos + transf %. <strong>P. Facturado</strong> = pesos + IVA + IIBB. Las cuotas aplican recargos: EN 1 = +${fmtPct(pct1)}%, EN 3 = +${fmtPct(pct3)}%, EN 6 = +${fmtPct(pct6)}%, EN 12 = +${fmtPct(pct12)}%. Estos porcentajes se editan desde "⚙ Configurar".`;

            const tbody = document.getElementById('tbody-results');
            tbody.innerHTML = '';

            const tr1 = document.createElement('tr');
            function td(text) {
                const c = document.createElement('td');
                c.textContent = text;
                return c;
            }

            tr1.appendChild(td(producto));
            tr1.appendChild(td(fmt(usd, 'USD')));
            tr1.appendChild(td(fmt(precioPesos, 'ARS')));
            tr1.appendChild(td(fmt(precioTransferido, 'ARS')));
            tr1.appendChild(td(fmt(iva, 'ARS')));
            tr1.appendChild(td(fmt(iibb, 'ARS')));
            tr1.appendChild(td(fmt(precioFacturado, 'ARS')));
            tr1.appendChild(td(fmt(en1, 'ARS')));
            tr1.appendChild(td(fmt(en3, 'ARS')));
            tr1.appendChild(td(fmt(en6, 'ARS')));
            tr1.appendChild(td(fmt(en12, 'ARS')));

            const tr2 = document.createElement('tr');
            const tdVacio = () => {
                const td = document.createElement('td');
                td.textContent = '';
                return td;
            };

            tr2.appendChild(tdVacio());
            tr2.appendChild(tdVacio());
            tr2.appendChild(tdVacio());
            tr2.appendChild(tdVacio());
            tr2.appendChild(tdVacio());
            tr2.appendChild(tdVacio());
            tr2.appendChild(tdVacio());
            const tdLabel = document.createElement('td');
            tdLabel.textContent = "Valor por cuota";
            tdLabel.style.fontWeight = "bold";
            tdLabel.style.textAlign = "right";
            tr2.appendChild(tdLabel);
            tr2.appendChild(td(fmt(cuota3, 'ARS')));
            tr2.appendChild(td(fmt(cuota6, 'ARS')));
            tr2.appendChild(td(fmt(cuota12, 'ARS')));

            tbody.appendChild(tr1);
            tbody.appendChild(tr2);
        }

        function fmtPct(n) {
            if (typeof n !== "number" || !isFinite(n)) return "-";
            return n.toLocaleString('es-AR', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        }

        function fmt(n, moneda) {
            if (typeof n !== "number" || !isFinite(n)) return "-";
            if (!moneda) {
                const opts = { minimumFractionDigits: 0, maximumFractionDigits: 2 };
                return n.toLocaleString('es-AR', opts);
            }
            return n.toLocaleString('es-AR', {
                style: 'currency',
                currency: moneda,
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            });
        }

        // ===================== TABS =====================
        function showTab(name) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById('tab-' + name).classList.add('active');
            document.getElementById('tabbtn-' + name).classList.add('active');
        }

        // ===================== SIMULADOR DE COBRO =====================
        function showPlatform() {
            const plataforma = document.getElementById('sim_plataforma').value;
            const cuotasWrap = document.getElementById('mp_cuotasSelect_wrap');
            cuotasWrap.style.display = plataforma === 'mercadopago' ? '' : 'none';
            // Los valores mostrados corresponden a un cálculo anterior; se ocultan
            // hasta volver a presionar "Calcular" para la plataforma nueva.
            document.getElementById('whatsapp-box').style.display = 'none';
        }

        // Convierte lo que haya en "sim_recibir" a pesos según la moneda elegida.
        // ARS: se usa tal cual. USD: se multiplica por el dólar blue vigente (campo "dolar").
        function obtenerRecibirEnPesos() {
            const valor = parseFloat(document.getElementById('sim_recibir').value) || 0;
            const moneda = document.getElementById('sim_recibir_moneda').value;
            if (moneda === 'USD') {
                const dolar = parseFloat(document.getElementById('dolar').value) || 0;
                return valor * dolar;
            }
            return valor;
        }

        // Muestra en vivo, debajo del campo, a cuántos pesos equivale lo ingresado en USD.
        function actualizarConversionRecibir() {
            const valor = parseFloat(document.getElementById('sim_recibir').value) || 0;
            const moneda = document.getElementById('sim_recibir_moneda').value;
            const infoEl = document.getElementById('sim_recibir_conversion');
            if (moneda === 'USD' && valor > 0) {
                const dolar = parseFloat(document.getElementById('dolar').value) || 0;
                infoEl.textContent = `≈ ${fmt(valor * dolar, 'ARS')} (dólar referencia ${fmt(dolar, 'ARS')})`;
            } else {
                infoEl.textContent = '';
            }
        }

        function renderRow(container, label, sub, valueText, isTotal, tag) {
            const row = document.createElement('div');
            row.className = 'result-row' + (isTotal ? ' total' : '');

            const left = document.createElement('div');
            const lbl = document.createElement('span');
            lbl.className = 'label';
            lbl.textContent = label;
            if (tag) {
                const tagEl = document.createElement('sup');
                tagEl.className = 'row-tag';
                tagEl.textContent = ' ' + tag;
                lbl.appendChild(tagEl);
            }
            left.appendChild(lbl);
            if (sub) {
                const subEl = document.createElement('span');
                subEl.className = 'sub';
                subEl.textContent = sub;
                left.appendChild(subEl);
            }

            const right = document.createElement('div');
            right.className = 'value';
            right.textContent = valueText;

            row.appendChild(left);
            row.appendChild(right);
            container.appendChild(row);
        }

        function calcularSimulador() {
            const monedaRecibir = document.getElementById('sim_recibir_moneda').value;
            const valorIngresado = parseFloat(document.getElementById('sim_recibir').value) || 0;
            const dolarActual = parseFloat(document.getElementById('dolar').value) || 0;
            const recibir = obtenerRecibirEnPesos();
            const plataforma = document.getElementById('sim_plataforma').value;
            const revendedorPct = parseFloat(document.getElementById('sim_revendedorPct').value) || 0;
            const body = document.getElementById('sim-result-body');
            body.innerHTML = '';

            if (recibir <= 0) {
                body.innerHTML = '<p class="small">Ingresá un monto a recibir mayor a 0.</p>';
                return;
            }

            let rateTotal = 0;
            let costoBase = 0;
            let cuotas = 1;

            renderRow(body, 'Para recibir',
                monedaRecibir === 'USD' ? `USD ${fmt(valorIngresado)} · dólar referencia ${fmt(dolarActual, 'ARS')}` : null,
                fmt(recibir, 'ARS'));

            if (plataforma === 'mercadopago') {
                const costoCobroPct = parseFloat(document.getElementById('mp_costoCobro').value) || 0;
                const ivaComisionPct = parseFloat(document.getElementById('mp_ivaComision').value) || 0;
                cuotas = parseInt(document.getElementById('mp_cuotasSelect').value, 10) || 1;

                const cuotaPctById = { 2: 'mp_pct2', 3: 'mp_pct3', 6: 'mp_pct6', 9: 'mp_pct9', 12: 'mp_pct12', 18: 'mp_pct18' };
                let cuotaPct = 0;
                if (cuotaPctById[cuotas]) {
                    cuotaPct = parseFloat(document.getElementById(cuotaPctById[cuotas]).value) || 0;
                }

                const retCDPct = parseFloat(document.getElementById('mp_retCD').value) || 0;
                const retIIBBPct = parseFloat(document.getElementById('mp_retIIBB').value) || 0;

                const rateCosto = (costoCobroPct / 100) * (1 + ivaComisionPct / 100);
                const rateCuotas = cuotas > 1 ? (cuotaPct / 100) * (1 + ivaComisionPct / 100) : 0;
                const rateRetCD = retCDPct / 100;
                const rateRetIIBB = retIIBBPct / 100;

                rateTotal = rateCosto + rateCuotas + rateRetCD + rateRetIIBB;

                if (rateTotal >= 1) {
                    body.innerHTML = '<p class="small" style="color:#b00020;">La suma de costos y retenciones supera o iguala el 100%. Revisá los porcentajes en "⚙ Configurar".</p>';
                    return;
                }

                costoBase = recibir / (1 - rateTotal);
                const montoCosto = costoBase * rateCosto;
                const montoCuotas = costoBase * rateCuotas;
                const montoRetCD = costoBase * rateRetCD;
                const montoRetIIBB = costoBase * rateRetIIBB;

                renderRow(body, 'Costo por cobro', `En el momento ${fmtPct(costoCobroPct)}% + IVA`, '+ ' + fmt(montoCosto, 'ARS'), false, '[Mercado Pago]');
                if (cuotas > 1) {
                    renderRow(body, 'Costo por ofrecer cuotas sin interés', `En ${cuotas} cuotas ${fmtPct(cuotaPct)}% + IVA`, '+ ' + fmt(montoCuotas, 'ARS'), false, '[Mercado Pago]');
                }
                renderRow(body, 'Impuesto sobre los Créditos y Débitos', `${fmtPct(retCDPct)}%`, '+ ' + fmt(montoRetCD, 'ARS'), false, '[Impuesto]');
                renderRow(body, 'Impuesto Ingresos Brutos Régimen SIRTAC', `${fmtPct(retIIBBPct)}%`, '+ ' + fmt(montoRetIIBB, 'ARS'), false, '[Impuesto]');

            } else {
                const costoPct = parseFloat(document.getElementById('tr_costo').value) || 0;
                const ivaPct = parseFloat(document.getElementById('tr_iva').value) || 0;
                const iibbPct = parseFloat(document.getElementById('tr_iibb').value) || 0;

                rateTotal = (costoPct + ivaPct + iibbPct) / 100;

                if (rateTotal >= 1) {
                    body.innerHTML = '<p class="small" style="color:#b00020;">La suma de costos y retenciones supera o iguala el 100%. Revisá los porcentajes en "⚙ Configurar".</p>';
                    return;
                }

                costoBase = recibir / (1 - rateTotal);
                const montoCosto = costoBase * (costoPct / 100);
                const montoIva = costoBase * (ivaPct / 100);
                const montoIibb = costoBase * (iibbPct / 100);

                renderRow(body, 'Costo por transferencia', `${fmtPct(costoPct)}%`, '+ ' + fmt(montoCosto, 'ARS'), false, '[Banco]');
                renderRow(body, 'IVA', `${fmtPct(ivaPct)}%`, '+ ' + fmt(montoIva, 'ARS'), false, '[Impuesto]');
                renderRow(body, 'IIBB', `${fmtPct(iibbPct)}%`, '+ ' + fmt(montoIibb, 'ARS'), false, '[Impuesto]');
            }

            let totalFinal = costoBase;

            if (revendedorPct > 0) {
                // El margen se calcula sobre "recibir" (no sobre costoBase), y NO se vuelve a
                // "engordar" con la fórmula inversa: se suma como monto fijo al costoBase.
                // Como el cobro real al cliente final sí paga comisión/retenciones sobre ese
                // monto extra, ese impuesto adicional se descuenta de la comisión del revendedor,
                // así el negocio termina recibiendo exactamente "recibir".
                const margenNominal = recibir * (revendedorPct / 100);
                const precioConMargen = recibir + margenNominal;
                const impuestoExtra = margenNominal * rateTotal;
                const comisionNeta = margenNominal - impuestoExtra;
                totalFinal = costoBase + margenNominal;

                renderRow(body, 'Deberías cobrar (sin revendedor)', null, fmt(costoBase, 'ARS'));
                renderRow(body, 'Margen revendedor', `${fmtPct(revendedorPct)}% sobre ${fmt(recibir, 'ARS')} → precio de reventa ${fmt(precioConMargen, 'ARS')}`, '+ ' + fmt(margenNominal, 'ARS'));
                renderRow(body, 'Impuesto extra por el margen', 'se descuenta de la comisión del revendedor', '− ' + fmt(impuestoExtra, 'ARS'), false, '[Mercado Pago / Impuestos]');
                renderRow(body, 'Le corresponde al revendedor', null, fmt(comisionNeta, 'ARS'), false, '[Revendedor]');
                renderRow(body, 'Deberías cobrarle al cliente final', null, fmt(totalFinal, 'ARS'), true);
            } else {
                renderRow(body, 'Deberías cobrar', null, fmt(totalFinal, 'ARS'), true);
            }

            if (plataforma === 'mercadopago' && cuotas > 1) {
                renderRow(body, 'Valor de cada cuota', `${cuotas} cuotas`, fmt(totalFinal / cuotas, 'ARS'));
            }

            generarMensajeWhatsApp(plataforma, recibir, revendedorPct);
        }

        // ===================== MENSAJE PARA WHATSAPP / INSTAGRAM =====================
        // Calcula, para una cantidad de cuotas puntual, cuánto hay que cobrarle al
        // cliente final (mismo criterio que calcularSimulador, pero sin tocar el DOM
        // del panel de detalle, para poder recorrer varias cuotas de una).
        function calcularTotalCobro(plataforma, recibir, cuotas, revendedorPct) {
            let rateTotal = 0;

            if (plataforma === 'mercadopago') {
                const costoCobroPct = parseFloat(document.getElementById('mp_costoCobro').value) || 0;
                const ivaComisionPct = parseFloat(document.getElementById('mp_ivaComision').value) || 0;

                const cuotaPctById = { 2: 'mp_pct2', 3: 'mp_pct3', 6: 'mp_pct6', 9: 'mp_pct9', 12: 'mp_pct12', 18: 'mp_pct18' };
                let cuotaPct = 0;
                if (cuotaPctById[cuotas]) {
                    cuotaPct = parseFloat(document.getElementById(cuotaPctById[cuotas]).value) || 0;
                }

                const retCDPct = parseFloat(document.getElementById('mp_retCD').value) || 0;
                const retIIBBPct = parseFloat(document.getElementById('mp_retIIBB').value) || 0;

                const rateCosto = (costoCobroPct / 100) * (1 + ivaComisionPct / 100);
                const rateCuotas = cuotas > 1 ? (cuotaPct / 100) * (1 + ivaComisionPct / 100) : 0;
                rateTotal = rateCosto + rateCuotas + (retCDPct / 100) + (retIIBBPct / 100);
            } else {
                const costoPct = parseFloat(document.getElementById('tr_costo').value) || 0;
                const ivaPct = parseFloat(document.getElementById('tr_iva').value) || 0;
                const iibbPct = parseFloat(document.getElementById('tr_iibb').value) || 0;
                rateTotal = (costoPct + ivaPct + iibbPct) / 100;
            }

            if (rateTotal >= 1) return null;

            const costoBase = recibir / (1 - rateTotal);
            const margenNominal = revendedorPct > 0 ? recibir * (revendedorPct / 100) : 0;

            return costoBase + margenNominal;
        }

        function generarMensajeWhatsApp(plataforma, recibir, revendedorPct) {
            const box = document.getElementById('whatsapp-box');
            const textEl = document.getElementById('whatsapp-text');

            if (recibir <= 0) {
                box.style.display = 'none';
                return;
            }

            let mensaje = '';

            if (plataforma === 'mercadopago') {
                const cuotasDisponibles = [1, 2, 3, 6, 9, 12, 18];
                const lineas = [];
                for (const cuotas of cuotasDisponibles) {
                    const total = calcularTotalCobro('mercadopago', recibir, cuotas, revendedorPct);
                    if (total === null) { box.style.display = 'none'; return; }
                    const label = cuotas === 1 ? '1 pago' : `${cuotas} cuotas`;
                    const valor = cuotas === 1 ? total : total / cuotas;
                    lineas.push(`${label} de: ${fmt(valor, 'ARS')}`);
                }
                mensaje = 'Te compartimos nuestras opciones de pago 👇\n\n💳 Tarjeta de crédito:\n' + lineas.join('\n');
            } else {
                const total = calcularTotalCobro('transferencia', recibir, 1, revendedorPct);
                if (total === null) { box.style.display = 'none'; return; }
                mensaje = 'Te compartimos nuestra opción de pago 👇\n\n🏦 Transferencia / Efectivo: ' + fmt(total, 'ARS');
            }

            textEl.textContent = mensaje;
            box.style.display = '';
        }

        let toastTimer = null;
        function mostrarToast(mensaje) {
            const el = document.getElementById('toast');
            el.textContent = mensaje;
            el.classList.add('show');
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => el.classList.remove('show'), 2000);
        }

        function copiarMensajeWhatsApp() {
            const text = document.getElementById('whatsapp-text').textContent;
            if (!text) return;

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text)
                    .then(() => mostrarToast('✓ Mensaje copiado al portapapeles'))
                    .catch(() => mostrarToast('No se pudo copiar. Seleccioná el texto manualmente.'));
            } else {
                mostrarToast('No se pudo copiar. Seleccioná el texto manualmente.');
            }
        }

        // ===================== MODAL DE CONFIGURACIÓN =====================
        function openConfigModal() {
            document.getElementById('configModal').classList.add('open');
        }

        function closeConfigModal() {
            document.getElementById('configModal').classList.remove('open');
        }

        function guardarConfiguracion() {
            saveConfig();
            generarTabla();
            actualizarBadgeDolar(parseFloat(document.getElementById('dolar').value) || 0, '· valor cargado manualmente');
            if ((parseFloat(document.getElementById('sim_recibir').value) || 0) > 0) {
                calcularSimulador();
            }
            closeConfigModal();
        }

        function resetConfigDefaults() {
            document.getElementById('dolar').value = 1450;
            document.getElementById('transferPct').value = 3;
            document.getElementById('ivaPct').value = 10.5;
            document.getElementById('iibbPct').value = 3.5;
            document.getElementById('pct1').value = 10;
            document.getElementById('pct3').value = 25;
            document.getElementById('pct6').value = 35;
            document.getElementById('pct12').value = 45;

            document.getElementById('mp_costoCobro').value = 4.98;
            document.getElementById('mp_ivaComision').value = 21;
            document.getElementById('mp_pct2').value = 3.30;
            document.getElementById('mp_pct3').value = 4.50;
            document.getElementById('mp_pct6').value = 10.30;
            document.getElementById('mp_pct9').value = 15.30;
            document.getElementById('mp_pct12').value = 19.50;
            document.getElementById('mp_pct18').value = 26.10;
            document.getElementById('mp_retCD').value = 0.6;
            document.getElementById('mp_retIIBB').value = 0.4;

            document.getElementById('tr_costo').value = 0;
            document.getElementById('tr_iva').value = 0;
            document.getElementById('tr_iibb').value = 0;
            // No guarda solo: hay que presionar "Guardar" para confirmar.
        }

        // ===================== DÓLAR BLUE: carga automática =====================
        // Fuente: DolarAPI.com (pública, sin necesidad de API key).
        // Devuelve { compra, venta, casa, nombre, moneda, fechaActualizacion }.
        const DOLAR_BLUE_API = 'https://dolarapi.com/v1/dolares/blue';
        // Ajuste manual pedido por el negocio: se suma al valor "venta" informado por la API.
        const DOLAR_AJUSTE = 20;

        async function fetchDolarBlueVenta() {
            try {
                const res = await fetch(DOLAR_BLUE_API, { cache: 'no-store' });
                if (!res.ok) return null;
                const data = await res.json();
                const venta = parseFloat(data.venta);
                return isFinite(venta) && venta > 0 ? venta : null;
            } catch (e) {
                console.warn('No se pudo obtener el dólar de referencia desde la API.', e);
                return null;
            }
        }

        function actualizarBadgeDolar(valor, info) {
            document.getElementById('dolar-badge-valor').textContent = fmt(valor, 'ARS');
            document.getElementById('dolar-badge-info').textContent = info || '';
        }

        // Trae el valor actual del dólar (API + ajuste) y lo carga en el campo "dolar"
        // (editable luego desde "⚙ Configurar"). Si la API no responde (sin conexión,
        // CORS, etc.), se mantiene el último valor guardado (config.json o localStorage).
        async function actualizarDolar(manual) {
            actualizarBadgeDolar(parseFloat(document.getElementById('dolar').value) || 0, '· actualizando...');

            const ventaApi = await fetchDolarBlueVenta();
            if (ventaApi) {
                const valor = ventaApi + DOLAR_AJUSTE;
                document.getElementById('dolar').value = valor;
                saveConfig();
                const hora = new Date().toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
                actualizarBadgeDolar(valor, `· actualizado ${hora}`);
                if (manual) mostrarToast('✓ Dólar de referencia actualizado');
            } else {
                const actual = parseFloat(document.getElementById('dolar').value) || 0;
                actualizarBadgeDolar(actual, '· sin conexión, valor guardado');
                if (manual) mostrarToast('No se pudo actualizar. Se mantiene el valor guardado.');
            }
            generarTabla();
            actualizarConversionRecibir();
        }

        // ===================== PERSISTENCIA DE CONFIGURACIÓN =====================
        const CONFIG_FIELDS = [
            'dolar', 'transferPct', 'ivaPct', 'iibbPct', 'pct1', 'pct3', 'pct6', 'pct12',
            'sim_plataforma', 'sim_recibir_moneda', 'mp_costoCobro', 'mp_ivaComision', 'mp_cuotasSelect',
            'mp_pct2', 'mp_pct3', 'mp_pct6', 'mp_pct9', 'mp_pct12', 'mp_pct18',
            'mp_retCD', 'mp_retIIBB',
            'tr_costo', 'tr_iva', 'tr_iibb'
        ];
        const CONFIG_STORAGE_KEY = 'calculadora_cuotas_config_v1';

        function getConfigFromFields() {
            const config = {};
            CONFIG_FIELDS.forEach(id => {
                const el = document.getElementById(id);
                if (el) config[id] = el.value;
            });
            return config;
        }

        function applyConfigToFields(config) {
            if (!config) return;
            CONFIG_FIELDS.forEach(id => {
                const el = document.getElementById(id);
                if (el && config[id] !== undefined && config[id] !== null) el.value = config[id];
            });
        }

        function saveConfig() {
            try {
                localStorage.setItem(CONFIG_STORAGE_KEY, JSON.stringify(getConfigFromFields()));
            } catch (e) {
                console.warn('No se pudo guardar la configuración en este navegador.', e);
            }
        }

        function loadConfigFromStorage() {
            try {
                const raw = localStorage.getItem(CONFIG_STORAGE_KEY);
                if (raw) applyConfigToFields(JSON.parse(raw));
            } catch (e) {
                console.warn('No se pudo leer la configuración guardada.', e);
            }
        }

        // Intenta leer config.json (misma carpeta que este HTML). Devuelve el objeto
        // si lo encuentra y es válido, o null si no existe / no se puede leer
        // (por ejemplo si el HTML se abrió como archivo local sin servidor: fetch()
        // de otro archivo local suele estar bloqueado por CORS en Chrome).
        async function loadConfigFromFile() {
            try {
                const res = await fetch('config.json', { cache: 'no-store' });
                if (!res.ok) return null;
                return await res.json();
            } catch (e) {
                console.warn('No se encontró config.json o no se pudo leer (¿se abrió el HTML sin servidor?).', e);
                return null;
            }
        }

        function exportConfig() {
            const blob = new Blob([JSON.stringify(getConfigFromFields(), null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'config.json';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        function importConfig(event) {
            const file = event.target.files && event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (e) => {
                try {
                    const config = JSON.parse(e.target.result);
                    applyConfigToFields(config);
                } catch (err) {
                    alert('El archivo no tiene un formato JSON válido.');
                }
            };
            reader.readAsText(file);
            event.target.value = '';
        }

        // Inicializar: primero intenta cargar config.json (misma carpeta). Si no existe
        // o no se puede leer, usa lo último guardado en este navegador (localStorage).
        window.addEventListener('DOMContentLoaded', async () => {
            const fileConfig = await loadConfigFromFile();
            if (fileConfig) {
                applyConfigToFields(fileConfig);
                saveConfig(); // la dejamos también cacheada en este navegador
            } else {
                loadConfigFromStorage();
            }
            showPlatform();
            showTab('simulador');
            await actualizarDolar(); // trae el dólar blue del día; generarTabla() se llama adentro
        });
    </script>
</body>

</html>
