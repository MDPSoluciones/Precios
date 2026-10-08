<?php
require __DIR__ . '/../config.php';
require_login();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Generador de Etiquetas &middot; MDP Soluciones</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<style>
  :root{
    --carbon:#303030;
    --smoke:#D9DAD4;
    --gold:#BEA167;
    --cream:#F5F1E8;
    --serif:'Cormorant Garamond',serif;
    --sans:'DM Sans',sans-serif;
  }
  *{margin:0;padding:0;box-sizing:border-box}
  body{
    font-family:var(--sans);
    background:var(--carbon);
    color:var(--cream);
    min-height:100vh;
    padding:32px 20px 60px;
  }
  .wrap{max-width:1180px;margin:0 auto}

  header.brand{
    display:flex;align-items:center;gap:18px;
    border-bottom:1px solid rgba(190,161,103,.35);
    padding-bottom:22px;margin-bottom:34px;
  }
  .logo-box{
    height:54px;flex:none;
    background:#fff;border:1px solid var(--gold);
    padding:7px 14px;display:flex;align-items:center;
  }
  .logo-box img{height:100%;width:auto;display:block}
  .brand-text h1{
    font-family:var(--serif);font-weight:700;
    font-size:30px;letter-spacing:.04em;color:var(--cream);
  }
  .brand-text p{
    font-size:12px;letter-spacing:.32em;text-transform:uppercase;
    color:var(--gold);margin-top:3px;
  }

  .layout{display:grid;grid-template-columns:380px 1fr;gap:42px;align-items:start}
  @media(max-width:920px){.layout{grid-template-columns:1fr}}

  .panel h2{
    font-family:var(--serif);font-size:21px;font-weight:600;
    color:var(--gold);margin-bottom:20px;letter-spacing:.03em;
  }
  .field{margin-bottom:18px}
  .field label{
    display:block;font-size:11px;letter-spacing:.18em;
    text-transform:uppercase;color:var(--smoke);margin-bottom:7px;font-weight:600;
  }
  .field input,.field textarea{
    width:100%;background:#3a3a3a;border:1px solid #4a4a4a;
    color:var(--cream);font-family:var(--sans);font-size:14px;
    padding:11px 13px;border-radius:3px;transition:border-color .15s;
  }
  .field input:focus,.field textarea:focus{
    outline:none;border-color:var(--gold);
  }
  .field textarea{resize:vertical;min-height:88px;line-height:1.5}
  .hint{font-size:11px;color:#8a8a8a;margin-top:5px;line-height:1.4}

  /* ===== Grilla de códigos (modo corto o solo códigos + distintos) ===== */
  .codes-grid{
    display:grid;gap:6px;
  }
  .codes-grid.cols-2{grid-template-columns:1fr 1fr}
  .codes-grid.cols-3{grid-template-columns:1fr 1fr 1fr}
  .codes-grid input{
    background:#3a3a3a;border:1px solid #4a4a4a;
    color:var(--cream);font-family:var(--sans);font-size:13px;
    padding:8px 10px;border-radius:3px;text-align:center;min-width:0;
  }
  .codes-grid input:focus{outline:none;border-color:var(--gold)}

  /* ===== Segmented control (3+ opciones mutuamente excluyentes) ===== */
  .seg{
    display:flex;background:#3a3a3a;border:1px solid #4a4a4a;
    border-radius:4px;padding:3px;margin-bottom:14px;gap:2px;
  }
  .seg button{
    flex:1;background:transparent;color:var(--smoke);
    border:none;font-family:var(--sans);font-weight:600;
    font-size:12px;letter-spacing:.06em;text-transform:uppercase;
    padding:9px 6px;border-radius:3px;cursor:pointer;
    transition:background .15s,color .15s;
  }
  .seg button:hover{color:var(--cream)}
  .seg button.active{background:var(--gold);color:var(--carbon)}

  /* ===== TOGGLE TEXTO / LOGO ===== */
  .toggle-row{
    display:flex;align-items:center;gap:14px;margin-bottom:14px;
  }
  .toggle-row .lbl{
    font-size:12px;letter-spacing:.06em;color:var(--smoke);font-weight:600;
  }
  .toggle-row .lbl.active{color:var(--gold)}
  .switch{position:relative;width:52px;height:26px;flex:none}
  .switch input{opacity:0;width:0;height:0}
  .slider{
    position:absolute;inset:0;background:#4a4a4a;
    border-radius:26px;cursor:pointer;transition:background .2s;
  }
  .slider:before{
    content:'';position:absolute;height:20px;width:20px;
    left:3px;top:3px;background:var(--cream);
    border-radius:50%;transition:transform .2s;
  }
  .switch input:checked + .slider{background:var(--gold)}
  .switch input:checked + .slider:before{transform:translateX(26px)}

  .btn{
    width:100%;background:var(--gold);color:var(--carbon);
    border:none;font-family:var(--sans);font-weight:700;
    font-size:13px;letter-spacing:.14em;text-transform:uppercase;
    padding:15px;border-radius:3px;cursor:pointer;
    transition:background .15s,transform .1s;margin-top:6px;
  }
  .btn:hover{background:#cdb079}
  .btn:active{transform:translateY(1px)}
  .btn.secondary{
    background:transparent;color:var(--gold);
    border:1px solid var(--gold);margin-top:12px;
  }
  .btn.secondary:hover{background:rgba(190,161,103,.12)}

  .preview-area h2{
    font-family:var(--serif);font-size:21px;font-weight:600;
    color:var(--gold);margin-bottom:6px;letter-spacing:.03em;
  }
  .preview-area .sub{font-size:12px;color:#8a8a8a;margin-bottom:20px}

  /* ===== HOJA DE ETIQUETA 4x6 ===== */
  .sheet-scale{
    background:var(--smoke);padding:24px;border-radius:4px;
    display:inline-block;
  }
  #sheet{
    width:4in;height:6in;background:#fff;color:#000;
    display:flex;position:relative;
    font-family:var(--sans);
  }
  /* Linea de corte: segmentos solidos negros (imprimen siempre en termica) */
  .cut-line{
    position:absolute;top:0;bottom:0;left:50%;width:0;
    transform:translateX(-1px);
    background-image:linear-gradient(#000 60%, transparent 0);
    background-size:2px 14px;background-repeat:repeat-y;
    border-left:2px solid transparent;
  }
  .cut-mark{
    position:absolute;left:50%;transform:translateX(-50%);
    font-size:11pt;color:#000;background:#fff;
    padding:0 3px;line-height:1;
  }
  .cut-mark.top{top:2px}
  .cut-mark.bot{bottom:2px}
  .half{
    width:2in;height:6in;padding:0.2in 0.16in;
    display:flex;flex-direction:column;align-items:center;
    text-align:center;overflow:hidden;
  }
  .l-company{
    font-family:var(--serif);font-weight:700;
    font-size:16pt;letter-spacing:.02em;line-height:1.05;color:#000;
  }
  .l-company small{
    display:block;font-family:var(--sans);font-weight:700;
    font-size:6pt;letter-spacing:.28em;margin-top:2px;color:#000;
  }
  .l-logo{width:100%;display:flex;justify-content:center;padding:2px 0}
  .l-logo img{width:78%;height:auto;display:block}
  .l-rule{width:82%;border-bottom:1.5px solid #000;margin:8px 0 10px}
  .l-barcode{width:100%;display:flex;justify-content:center}
  .l-barcode svg{max-width:100%;height:auto}
  .l-code{
    font-weight:700;font-size:13pt;letter-spacing:.1em;
    margin-top:2px;margin-bottom:10px;color:#000;
  }
  .l-product{
    font-weight:700;font-size:10pt;letter-spacing:.06em;
    text-transform:uppercase;margin-bottom:6px;color:#000;
  }
  .l-specs{
    font-size:7.5pt;line-height:1.55;letter-spacing:.03em;
    white-space:pre-line;margin-top:auto;padding-top:6px;
    border-top:1px dashed #000;width:100%;color:#000;font-weight:500;
  }

  /* ===== MODO CORTO: 2 columnas x 3 filas = 6 mini-etiquetas de 2x2 ===== */
  #sheet.short{
    display:grid;
    grid-template-columns:2in 2in;
    grid-template-rows:2in 2in 2in;
  }
  .mini{
    width:2in;height:2in;
    padding:0.14in 0.12in;
    display:flex;flex-direction:column;
    align-items:center;justify-content:center;text-align:center;
    overflow:hidden;
  }
  .m-logo{width:100%;display:flex;justify-content:center;margin-bottom:4px}
  .m-logo img{width:70%;height:auto;display:block}
  .m-company{
    font-family:var(--serif);font-weight:700;
    font-size:11pt;letter-spacing:.02em;line-height:1;color:#000;
    margin-bottom:4px;
  }
  .m-company small{
    display:block;font-family:var(--sans);font-weight:700;
    font-size:4.5pt;letter-spacing:.24em;margin-top:1px;color:#000;
  }
  .m-barcode{width:100%;display:flex;justify-content:center;margin-top:4px}
  .m-barcode svg{max-width:100%;height:auto}
  .m-code{
    font-weight:700;font-size:11pt;letter-spacing:.1em;
    margin-top:3px;color:#000;
  }
  /* Lineas de corte del modo corto: 1 vertical al medio + 2 horizontales */
  .cut-v,.cut-h{position:absolute;background-image:linear-gradient(#000 60%, transparent 0);pointer-events:none;z-index:2}
  .cut-v{
    top:0;bottom:0;left:50%;width:0;
    transform:translateX(-1px);
    background-size:2px 14px;background-repeat:repeat-y;
    border-left:2px solid transparent;
  }
  .cut-h{
    left:0;right:0;height:0;
    background-size:14px 2px;background-repeat:repeat-x;
    border-top:2px solid transparent;
  }
  .cut-h.r1{top:2in;transform:translateY(-1px)}
  .cut-h.r2{top:4in;transform:translateY(-1px)}

  /* ===== MODO SOLO CÓDIGOS: 12 / 18 / 24 celdas ===== */
  #sheet.codes-12{display:grid;grid-template-columns:2in 2in;grid-template-rows:repeat(6,1in)}
  #sheet.codes-18{display:grid;grid-template-columns:repeat(3,1fr);grid-template-rows:repeat(6,1in)}
  #sheet.codes-24{display:grid;grid-template-columns:repeat(3,1fr);grid-template-rows:repeat(8,0.75in)}
  .cell{
    display:flex;flex-direction:column;align-items:center;justify-content:center;
    text-align:center;overflow:hidden;padding:3pt 10pt;color:#000;
  }
  .cell .c-bc{width:100%;display:flex;justify-content:center}
  .cell .c-bc svg{max-width:100%;height:auto}
  .cell .c-num{
    font-weight:700;letter-spacing:.05em;margin-top:2px;color:#000;line-height:1;
  }
  #sheet.codes-12 .cell .c-num{font-size:8.5pt}
  #sheet.codes-18 .cell .c-num{font-size:7pt}
  #sheet.codes-24 .cell .c-num{font-size:6pt;margin-top:1px}
  /* Cut lines por posicion arbitraria (top/left en %) */
  .cut-hp{
    position:absolute;left:0;right:0;height:0;pointer-events:none;z-index:2;
    background-image:linear-gradient(90deg,#000 60%, transparent 0);
    background-size:14px 2px;background-repeat:repeat-x;
    border-top:2px solid transparent;transform:translateY(-1px);
  }
  .cut-vp{
    position:absolute;top:0;bottom:0;width:0;pointer-events:none;z-index:2;
    background-image:linear-gradient(#000 60%, transparent 0);
    background-size:2px 14px;background-repeat:repeat-y;
    border-left:2px solid transparent;transform:translateX(-1px);
  }

  @media print{
    @page{size:4in 6in;margin:0}
    body *{visibility:hidden}
    #sheet,#sheet *{visibility:visible}
    body{background:#fff;padding:0}
    #sheet{position:absolute;left:0;top:0}
    .sheet-scale{padding:0;background:none}
    /* Forzar negro pleno en impresion */
    .l-company,.l-company small,.l-code,.l-product,.l-specs,
    .m-company,.m-company small,.m-code,.c-num{
      color:#000 !important;-webkit-print-color-adjust:exact;print-color-adjust:exact;
    }
  }
</style>
</head>
<body>
<div class="wrap">

  <header class="brand">
    <div class="logo-box"><img src="logo-mdp-bn.png" alt="MDP Soluciones"></div>
    <div class="brand-text">
      <h1>Generador de Etiquetas</h1>
      <p>Soporte &amp; Tecnolog&iacute;a</p>
    </div>
  </header>

  <div class="layout">

    <!-- ===== FORMULARIO ===== -->
    <section class="panel">
      <h2>Datos de la etiqueta</h2>

      <div class="seg" id="seg-mode" role="tablist">
        <button type="button" data-mode="long">Larga</button>
        <button type="button" data-mode="short" class="active">Corta</button>
        <button type="button" data-mode="codes">Solo c&oacute;digos</button>
      </div>

      <div class="seg" id="seg-density" style="display:none">
        <button type="button" data-density="12">12</button>
        <button type="button" data-density="18" class="active">18</button>
        <button type="button" data-density="24">24</button>
      </div>

      <div class="toggle-row" id="row-mode">
        <span class="lbl" id="lbl-text">Texto</span>
        <label class="switch">
          <input type="checkbox" id="sw-mode" checked>
          <span class="slider"></span>
        </label>
        <span class="lbl active" id="lbl-logo">Logo</span>
      </div>

      <div class="field" id="field-company">
        <label for="f-company">Nombre de la empresa</label>
        <input id="f-company" type="text" value="MDP Soluciones" placeholder="Ej: MDP Soluciones">
        <div class="hint">Aparece como texto en el encabezado de la etiqueta.</div>
      </div>

      <div class="field" id="field-logohint" style="display:none">
        <div class="hint">Se usar&aacute; el logo MDP Soluciones (procesado en blanco y negro para impresi&oacute;n t&eacute;rmica).</div>
      </div>

      <div class="toggle-row" id="row-diff">
        <span class="lbl active" id="lbl-igual">Igual</span>
        <label class="switch">
          <input type="checkbox" id="sw-diff">
          <span class="slider"></span>
        </label>
        <span class="lbl" id="lbl-distintos">Distintos</span>
      </div>

      <div class="field" id="field-code">
        <label for="f-code">C&oacute;digo (n&uacute;meros)</label>
        <input id="f-code" type="text" inputmode="numeric" value="1393" placeholder="Ej: 1393">
        <div class="hint">Se genera el c&oacute;digo de barras Code 128 a partir de este valor.</div>
      </div>

      <div class="field" id="field-codes-multi" style="display:none">
        <label>C&oacute;digos (uno por posici&oacute;n)</label>
        <div class="codes-grid cols-2" id="codes-grid"></div>
        <div class="hint">El orden es izquierda a derecha, arriba a abajo. Los campos vac&iacute;os generan una etiqueta vac&iacute;a en esa posici&oacute;n.</div>
      </div>

      <div id="fields-long">
        <div class="field">
          <label for="f-product">Nombre del producto</label>
          <input id="f-product" type="text" value="PROBOOK 640 G5" placeholder="Ej: PROBOOK 640 G5">
        </div>

        <div class="field">
          <label for="f-specs">Especificaciones</label>
          <textarea id="f-specs" placeholder="Una l&iacute;nea por caracter&iacute;stica">INTEL I5 8365U
16GB DDR4 2400MHZ
512GB SSD M.2
PANTALLA 14" FHD
UHD GRAPHICS 620</textarea>
          <div class="hint">Cada salto de l&iacute;nea se respeta en la etiqueta.</div>
        </div>
      </div>

      <button class="btn" id="btn-gen">Actualizar vista previa</button>
      <button class="btn secondary" id="btn-print">Imprimir etiqueta 4&times;6"</button>
    </section>

    <!-- ===== VISTA PREVIA ===== -->
    <section class="preview-area">
      <h2>Vista previa</h2>
      <p class="sub" id="preview-sub">Etiqueta f&iacute;sica 4&times;6" &middot; 2 copias de 2" &middot; cortar por la l&iacute;nea central</p>
      <div class="sheet-scale">
        <div id="sheet"></div>
      </div>
    </section>

  </div>
</div>

<script>
var LOGO_SRC = "logo-mdp-bn.png";

function escapeHtml(s){
  return (s||'').replace(/[&<>"\']/g,function(c){
    return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
  });
}

function headerHtml(data){
  return data.useLogo
    ? '<div class="l-logo"><img src="'+LOGO_SRC+'" alt="logo"></div>'
    : '<div class="l-company">'+escapeHtml(data.company)+'<small>SOPORTE &amp; TECNOLOG&Iacute;A</small></div>';
}
function headerHtmlMini(data){
  return data.useLogo
    ? '<div class="m-logo"><img src="'+LOGO_SRC+'" alt="logo"></div>'
    : '<div class="m-company">'+escapeHtml(data.company)+'<small>SOPORTE &amp; TECNOLOG&Iacute;A</small></div>';
}

function buildHalf(data){
  var div = document.createElement('div');
  div.className='half';
  div.innerHTML =
    headerHtml(data) +
    '<div class="l-rule"></div>' +
    '<div class="l-barcode"><svg class="bc"></svg></div>' +
    '<div class="l-code">'+escapeHtml(data.code)+'</div>' +
    '<div class="l-rule"></div>' +
    '<div class="l-product">'+escapeHtml(data.product)+'</div>' +
    '<div class="l-specs">'+escapeHtml(data.specs)+'</div>';
  return div;
}

function buildMini(data, codeOverride){
  var div = document.createElement('div');
  div.className='mini';
  var code = (codeOverride !== undefined) ? codeOverride : data.code;
  if(!code){
    // Posicion vacia: dejar la celda sin barcode ni numero
    div.innerHTML = '';
    return div;
  }
  div.innerHTML =
    headerHtmlMini(data) +
    '<div class="m-barcode"><svg class="bc-mini" data-code="'+escapeHtml(code)+'"></svg></div>' +
    '<div class="m-code">'+escapeHtml(code)+'</div>';
  return div;
}

function renderLong(sheet, data){
  sheet.className = '';
  sheet.innerHTML =
    '<div class="cut-line"></div>' +
    '<div class="cut-mark top">&#9986;</div>' +
    '<div class="cut-mark bot">&#9986;</div>';
  sheet.appendChild(buildHalf(data));
  sheet.appendChild(buildHalf(data));

  document.querySelectorAll('.bc').forEach(function(svg){
    try{
      JsBarcode(svg, data.code, {
        format:'CODE128', width:2, height:52,
        displayValue:false, margin:0, lineColor:'#000'
      });
    }catch(e){
      svg.outerHTML='<div style="font-size:7pt;color:#c00">C&oacute;digo inv&aacute;lido</div>';
    }
  });
}

function renderShort(sheet, data){
  sheet.className = 'short';
  sheet.innerHTML =
    '<div class="cut-v"></div>' +
    '<div class="cut-h r1"></div>' +
    '<div class="cut-h r2"></div>';
  for(var i=0;i<6;i++){
    var codeForCell = data.diff ? (data.codes[i] || '') : data.code;
    sheet.appendChild(buildMini(data, codeForCell));
  }

  document.querySelectorAll('.bc-mini').forEach(function(svg){
    var code = svg.getAttribute('data-code');
    if(!code) return;
    try{
      JsBarcode(svg, code, {
        format:'CODE128', width:1.6, height:38,
        displayValue:false, margin:0, lineColor:'#000'
      });
    }catch(e){
      svg.outerHTML='<div style="font-size:6pt;color:#c00">Cod. inv.</div>';
    }
  });
}

// ===== Modo "Solo códigos" =====
// Configuracion por densidad: columnas, filas, tamaño de barcode
var CODES_LAYOUT = {
  12: {cols:2, rows:6, bcWidth:1.6, bcHeight:38},
  18: {cols:3, rows:6, bcWidth:1.1, bcHeight:36},
  24: {cols:3, rows:8, bcWidth:0.95, bcHeight:24}
};

function buildCell(data, code){
  var div = document.createElement('div');
  div.className = 'cell';
  if(!code){ return div; } // celda vacía
  div.innerHTML =
    '<div class="c-bc"><svg class="bc-code" data-code="'+escapeHtml(code)+'"></svg></div>' +
    '<div class="c-num">'+escapeHtml(code)+'</div>';
  return div;
}

function renderCodes(sheet, data){
  var cfg = CODES_LAYOUT[data.density] || CODES_LAYOUT[18];
  var total = cfg.cols * cfg.rows;
  sheet.className = 'codes-' + data.density;

  // Lineas de corte por porcentaje
  var lines = '';
  for(var c=1;c<cfg.cols;c++){
    lines += '<div class="cut-vp" style="left:'+(100*c/cfg.cols)+'%"></div>';
  }
  for(var r=1;r<cfg.rows;r++){
    lines += '<div class="cut-hp" style="top:'+(100*r/cfg.rows)+'%"></div>';
  }
  sheet.innerHTML = lines;

  for(var i=0;i<total;i++){
    var codeForCell = data.diff ? (data.codes[i] || '') : data.code;
    sheet.appendChild(buildCell(data, codeForCell));
  }

  document.querySelectorAll('.bc-code').forEach(function(svg){
    var code = svg.getAttribute('data-code');
    if(!code) return;
    try{
      JsBarcode(svg, code, {
        format:'CODE128', width:cfg.bcWidth, height:cfg.bcHeight,
        displayValue:false, margin:0, lineColor:'#000'
      });
    }catch(e){
      svg.outerHTML='<div style="font-size:6pt;color:#c00">inv</div>';
    }
  });
}

// ===== Estado global =====
function getMode(){
  var btn = document.querySelector('#seg-mode button.active');
  return btn ? btn.getAttribute('data-mode') : 'short';
}
function getDensity(){
  var btn = document.querySelector('#seg-density button.active');
  return btn ? parseInt(btn.getAttribute('data-density'),10) : 18;
}

// Genera N inputs numéricos en la grilla de códigos múltiples
function ensureMultiInputs(count, cols){
  var grid = document.getElementById('codes-grid');
  grid.className = 'codes-grid cols-' + cols;
  // Preservar valores existentes
  var prev = [];
  grid.querySelectorAll('input').forEach(function(inp){
    prev[parseInt(inp.getAttribute('data-idx'),10)] = inp.value;
  });
  grid.innerHTML = '';
  for(var i=0;i<count;i++){
    var v = prev[i] || '';
    grid.insertAdjacentHTML('beforeend',
      '<input type="text" inputmode="numeric" class="f-code-multi" data-idx="'+i+'" placeholder="'+(i+1)+'" value="'+escapeHtml(v)+'">'
    );
  }
  // Re-attach listeners
  grid.querySelectorAll('input').forEach(function(inp){
    inp.addEventListener('input', render);
  });
}

function render(){
  var mode = getMode();
  var density = getDensity();
  var useLogo = document.getElementById('sw-mode').checked;
  var diff = document.getElementById('sw-diff').checked && (mode === 'short' || mode === 'codes');

  var codes = [];
  document.querySelectorAll('.f-code-multi').forEach(function(inp){
    codes[parseInt(inp.getAttribute('data-idx'),10)] = inp.value.trim();
  });

  var data = {
    mode: mode,
    density: density,
    useLogo: useLogo,
    diff: diff,
    company: document.getElementById('f-company').value.trim() || 'Empresa',
    code: document.getElementById('f-code').value.trim() || '0000',
    codes: codes,
    product: document.getElementById('f-product').value.trim(),
    specs: document.getElementById('f-specs').value
  };

  var sheet = document.getElementById('sheet');
  if(mode === 'long') renderLong(sheet, data);
  else if(mode === 'short') renderShort(sheet, data);
  else renderCodes(sheet, data);
}

function syncMode(){
  var mode = getMode();
  var density = getDensity();
  var useLogo = document.getElementById('sw-mode').checked;

  // Toggle Texto/Logo
  document.getElementById('field-company').style.display = useLogo ? 'none' : 'block';
  document.getElementById('field-logohint').style.display = useLogo ? 'block' : 'none';
  document.getElementById('lbl-text').classList.toggle('active', !useLogo);
  document.getElementById('lbl-logo').classList.toggle('active', useLogo);
  // El toggle Texto/Logo solo tiene sentido en modos con encabezado (long / short)
  document.getElementById('row-mode').style.display = (mode === 'codes') ? 'none' : 'flex';

  // Campos exclusivos de modo largo
  document.getElementById('fields-long').style.display = (mode === 'long') ? 'block' : 'none';

  // Selector de densidad solo en modo codes
  document.getElementById('seg-density').style.display = (mode === 'codes') ? 'flex' : 'none';

  // Toggle Igual/Distintos: aplica en short y codes
  var supportsDiff = (mode === 'short' || mode === 'codes');
  document.getElementById('row-diff').style.display = supportsDiff ? 'flex' : 'none';
  var diff = document.getElementById('sw-diff').checked && supportsDiff;
  document.getElementById('lbl-igual').classList.toggle('active', !diff);
  document.getElementById('lbl-distintos').classList.toggle('active', diff);
  document.getElementById('field-code').style.display = diff ? 'none' : 'block';
  document.getElementById('field-codes-multi').style.display = diff ? 'block' : 'none';

  // Regenerar grilla dinamica de inputs segun modo y densidad
  if(diff){
    var count, cols;
    if(mode === 'short'){ count = 6; cols = 2; }
    else { // codes
      var cfg = CODES_LAYOUT[density] || CODES_LAYOUT[18];
      count = cfg.cols * cfg.rows;
      cols = cfg.cols;
    }
    ensureMultiInputs(count, cols);
  }

  // Subtitulo dinámico de la vista previa
  var sub;
  if(mode === 'long') sub = 'Etiqueta f&iacute;sica 4&times;6" &middot; 2 copias de 2" &middot; cortar por la l&iacute;nea central';
  else if(mode === 'short') sub = 'Etiqueta f&iacute;sica 4&times;6" &middot; 6 copias de 2&times;2" &middot; cortar por las l&iacute;neas punteadas';
  else {
    var cfg2 = CODES_LAYOUT[density] || CODES_LAYOUT[18];
    sub = 'Etiqueta f&iacute;sica 4&times;6" &middot; ' + (cfg2.cols*cfg2.rows) + ' c&oacute;digos (' + cfg2.cols + '&times;' + cfg2.rows + ') &middot; cortar por las l&iacute;neas punteadas';
  }
  document.getElementById('preview-sub').innerHTML = sub;

  render();
}

// Prellena los inputs multi con el codigo base + incrementos (si estan todos vacios)
function seedMultiCodes(){
  var base = parseInt(document.getElementById('f-code').value.trim(),10);
  var inputs = document.querySelectorAll('.f-code-multi');
  if(!inputs.length || isNaN(base)) return;
  var allEmpty = Array.prototype.every.call(inputs, function(i){ return !i.value.trim(); });
  if(!allEmpty) return;
  inputs.forEach(function(inp, idx){ inp.value = String(base + idx); });
}

// ===== Listeners =====
document.getElementById('sw-mode').addEventListener('change', syncMode);
document.getElementById('sw-diff').addEventListener('change', function(){
  // Cuando se activa Distintos, primero regenerar inputs, después prellenar
  syncMode();
  if(this.checked){ seedMultiCodes(); render(); }
});

// Segmented controls: click cambia el active
document.querySelectorAll('#seg-mode button').forEach(function(b){
  b.addEventListener('click', function(){
    document.querySelectorAll('#seg-mode button').forEach(function(x){x.classList.remove('active')});
    this.classList.add('active');
    syncMode();
  });
});
document.querySelectorAll('#seg-density button').forEach(function(b){
  b.addEventListener('click', function(){
    document.querySelectorAll('#seg-density button').forEach(function(x){x.classList.remove('active')});
    this.classList.add('active');
    syncMode();
  });
});

document.getElementById('btn-gen').addEventListener('click', render);
document.getElementById('btn-print').addEventListener('click', function(){
  render(); setTimeout(function(){ window.print(); }, 150);
});
window.addEventListener('load', syncMode);
</script>
</body>
</html>