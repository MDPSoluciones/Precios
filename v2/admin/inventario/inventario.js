/**
 * INVENTARIO — inventario.js
 *
 * Arquitectura:
 *  ┌─────────────────────────────────────────────────────┐
 *  │  Google Sheets  ←─ fuente de verdad (en la nube)   │
 *  │       ↕  sincronización automática                  │
 *  │  localStorage   ←─ caché offline (en el navegador) │
 *  └─────────────────────────────────────────────────────┘
 *
 *  • Al abrir la app: si hay URL configurada, trae los datos del Sheet.
 *  • Al guardar/editar/eliminar: guarda en localStorage Y sube al Sheet.
 *  • Sin conexión o sin URL: trabaja solo con localStorage.
 *  • Sin datos en absoluto: muestra pantalla de bienvenida con instrucciones.
 */

'use strict';

/* ═══════════════════════════════════════════════════════════════════════════
   CONSTANTES
═══════════════════════════════════════════════════════════════════════════ */
const LS_KEY        = 'inventario_data';
const LS_KEY_OLD    = 'inventario_data_v2'; // clave temporal de versión anterior
const LS_META_KEY   = 'inventario_meta';
const LS_SHEETS_KEY = 'inventario_sheets_url';
const LS_ZONES_KEY  = 'inventario_empty_zones';
const PAGE_SIZE     = 50;

const XLSX_COLS = [
  { key: 'id',            header: 'ID' },
  { key: 'nombre',        header: 'Nombre' },
  { key: 'categoria',     header: 'Categoría' },
  { key: 'marca',         header: 'Marca' },
  { key: 'modelo',        header: 'Modelo' },
  { key: 'clasificacion', header: 'Clasificación' },
  { key: 'cant',          header: 'Cantidad' },
  { key: 'codigo',        header: 'Código' },
  { key: 'costo',         header: 'Costo (USD)' },
  { key: 'venta',         header: 'Precio Venta (USD)' },
  { key: 'reventa',       header: 'Precio Reventa (USD)' },
  { key: 'tipo',          header: 'Tipo' },
  { key: 'ubicacion',     header: 'Ubicación' },
  { key: 'notas',         header: 'Notas' },
];


/* ═══════════════════════════════════════════════════════════════════════════
   STORE — única fuente de verdad local
═══════════════════════════════════════════════════════════════════════════ */
const Store = (() => {
  let _data = [];

  // Carga desde localStorage, con migración de claves anteriores
  function load() {
    try {
      // 1. Intentar clave actual
      let raw = localStorage.getItem(LS_KEY);

      // 2. Si no hay nada, buscar en claves anteriores (migración)
      if (!raw || raw === '[]') {
        const candidates = [LS_KEY_OLD, 'inventario_data_v2', 'inventario_data'];
        for (const key of candidates) {
          const old = localStorage.getItem(key);
          if (old && old !== '[]') {
            raw = old;
            // Migrar: guardar en clave actual y limpiar la vieja
            localStorage.setItem(LS_KEY, old);
            if (key !== LS_KEY) localStorage.removeItem(key);
            console.info(`[Inventario] Migrado desde clave "${key}" → "${LS_KEY}"`);
            break;
          }
        }
      }

      _data = raw ? JSON.parse(raw) : [];
    } catch {
      _data = [];
    }
  }

  // Guarda en localStorage Y dispara sync al Sheet
  function save(skipSheetSync = false) {
    Status.set('saving');
    try {
      localStorage.setItem(LS_KEY, JSON.stringify(_data));
      localStorage.setItem(LS_META_KEY, JSON.stringify({
        updated: new Date().toISOString(),
        count: _data.length,
      }));
    } catch {
      Status.set('error', 'LocalStorage lleno');
      UI.toast('⚠ No se pudo guardar localmente.');
      return;
    }

    if (!skipSheetSync) {
      Sheets.autoPush(); // asíncrono, no bloquea
    } else {
      Status.set('ok');
    }
  }

  function getAll()    { return _data; }
  function isEmpty()   { return _data.length === 0; }

  function setAll(d, skipSheetSync = false) {
    _data = d;
    save(skipSheetSync);
  }

  function nextId() {
    return _data.length ? Math.max(..._data.map(d => d.id)) + 1 : 1;
  }

  function upsert(item) {
    const idx = _data.findIndex(d => d.id === item.id);
    if (idx === -1) _data.push(item);
    else _data[idx] = item;
    save();
  }

  function remove(id) {
    _data = _data.filter(d => d.id !== id);
    save();
  }

  return { load, save, getAll, isEmpty, setAll, nextId, upsert, remove };
})();


/* ═══════════════════════════════════════════════════════════════════════════
   STATUS — indicador en el topbar
═══════════════════════════════════════════════════════════════════════════ */
const Status = {
  _timer: null,

  set(state, detail = '') {
    const dot = document.getElementById('saveDot');
    const lbl = document.getElementById('saveLabel');
    if (!dot) return;

    const cfg = {
      ok:       { cls: 'save-dot--ok',      label: Sheets.getUrl() ? '☁ Sincronizado' : '💾 Guardado localmente' },
      saving:   { cls: 'save-dot--saving',  label: '⟳ Guardando…' },
      syncing:  { cls: 'save-dot--saving',  label: '☁ Sincronizando…' },
      error:    { cls: 'save-dot--error',   label: '⚠ ' + (detail || 'Error') },
      offline:  { cls: 'save-dot--error',   label: '⚡ Sin conexión — guardado local' },
      nodata:   { cls: 'save-dot--error',   label: 'Sin datos — configurá Google Sheets' },
    };

    const c = cfg[state] || cfg.ok;
    dot.className  = `save-dot ${c.cls}`;
    lbl.textContent = c.label;
  },
};


/* ═══════════════════════════════════════════════════════════════════════════
   SHEETS — integración con Google Apps Script
═══════════════════════════════════════════════════════════════════════════ */
const Sheets = {

  getUrl() {
    return localStorage.getItem(LS_SHEETS_KEY) || '';
  },

  saveUrl(url) {
    localStorage.setItem(LS_SHEETS_KEY, url);
  },

  isConfigured() {
    return !!this.getUrl();
  },

  // ── Auto-pull al arrancar la app ────────────────────────────────────────
  async bootPull() {
    if (!this.isConfigured()) return false;

    Status.set('syncing');
    setSyncStatus('loading', 'Conectando con Google Sheets…', '');

    try {
      const rows = await this._fetch();
      if (rows === null) return false; // error ya logueado

      if (rows.length === 0) {
        // Sheet existe pero está vacío — no pisamos datos locales
        Status.set('ok');
        setSyncStatus('ok', 'Sheet conectado (vacío)', 'Los datos locales se conservaron');
        return false;
      }

      const valid = rows.map((r, i) => normalizeImportedRow(r, i)).filter(r => r.nombre);
      // Guardamos pero sin volver a disparar un push (ya venimos del sheet)
      Store.setAll(valid, true);
      Status.set('ok');
      setSyncStatus('ok', `${valid.length} ítems`, `Última sincronización: ${timeNow()}`);
      return true;

    } catch (err) {
      Status.set('offline');
      setSyncStatus('error', 'Sin conexión al Sheet', err.message);
      return false;
    }
  },

  // ── Auto-push después de cada cambio (silencioso) ───────────────────────
  _pushTimer: null,

  autoPush() {
    if (!this.isConfigured()) {
      Status.set('ok');
      return;
    }
    // Debounce: si hay varios cambios seguidos, espera 1.5s antes de subir
    clearTimeout(this._pushTimer);
    Status.set('syncing');
    this._pushTimer = setTimeout(() => this._doPush(true), 1500);
  },

  // ── Push manual (desde el modal) ─────────────────────────────────────────
  async pushManual() {
    if (!this.isConfigured()) {
      UI.toast('⚠ Configurá primero la URL del Web App');
      return;
    }
    setSyncStatus('loading', 'Enviando al Sheet…', '');
    await this._doPush(false);
  },

  // ── Pull manual (desde el modal) ─────────────────────────────────────────
  async pullManual() {
    if (!this.isConfigured()) {
      UI.toast('⚠ Configurá primero la URL del Web App');
      return;
    }

    const rows = await this._fetch();
    if (rows === null) return;
    if (rows.length === 0) {
      setSyncStatus('error', 'El Sheet está vacío', 'Enviá los datos primero');
      return;
    }

    const valid = rows.map((r, i) => normalizeImportedRow(r, i)).filter(r => r.nombre);

    UI.confirm(
      'Traer datos del Sheet',
      `Se encontraron ${valid.length} ítems en el Google Sheet. Esto reemplazará el inventario local. ¿Continuar?`,
      () => {
        Store.setAll(valid, true); // sin re-push
        populateFilters();
        applyFilters();
        renderStats();
        checkEmpty();
        Status.set('ok');
        setSyncStatus('ok', `${valid.length} ítems`, `Última sincronización: ${timeNow()}`);
        UI.toast(`✅ ${valid.length} ítems importados desde Google Sheets`);
      }
    );
  },

  // ── Internos ─────────────────────────────────────────────────────────────

  // GET via JSONP — bypasea CORS desde cualquier dominio
  _fetch() {
    return new Promise((resolve, reject) => {
      const cbName = '_gsCallback_' + Date.now();
      const script  = document.createElement('script');
      const timeout = setTimeout(() => {
        cleanup();
        reject(new Error('Timeout — el servidor tardó demasiado. Verificá la URL del Web App.'));
      }, 12000);

      function cleanup() {
        clearTimeout(timeout);
        delete window[cbName];
        if (script.parentNode) script.remove();
      }

      window[cbName] = (data) => {
        cleanup();
        if (data && data.error) reject(new Error(data.error));
        else resolve(data);
      };

      script.onerror = () => {
        cleanup();
        reject(new Error('No se pudo conectar. Verificá que el script esté desplegado y que "Quién tiene acceso" sea "Cualquier persona".'));
      };

      script.src = `${this.getUrl()}?action=get&callback=${cbName}`;
      document.head.appendChild(script);
    });
  },

  // POST via no-cors — bypasea el problema de redirección CORS de Apps Script
  async _doPush(silent = false) {
    const data = Store.getAll();
    const payload = data.map(d => {
      const obj = {};
      XLSX_COLS.forEach(c => { obj[c.header] = d[c.key] ?? ''; });
      return obj;
    });

    try {
      // mode: 'no-cors' — el request llega al servidor pero la respuesta es opaca.
      // No podemos leerla, pero para escritura alcanza con que los datos lleguen.
      await fetch(this.getUrl(), {
        method: 'POST',
        mode:   'no-cors',
        body:   new URLSearchParams({
          action: 'set',
          data:   JSON.stringify(payload),
        }),
      });

      Status.set('ok');
      setSyncStatus('ok', `${data.length} ítems`, `Última sincronización: ${timeNow()}`);
      if (!silent) UI.toast(`✅ ${data.length} ítems enviados al Sheet`);

    } catch (err) {
      Status.set('offline');
      setSyncStatus('error', 'Error al enviar', err.message);
      if (!silent) UI.toast('❌ No se pudo enviar al Sheet');
    }
  },
};


/* ═══════════════════════════════════════════════════════════════════════════
   STATE — filtros, ordenamiento, paginación, vista
═══════════════════════════════════════════════════════════════════════════ */
const State = {
  filtered:  [],
  view:      'table',
  sortCol:   'id',
  sortDir:   1,
  page:      1,
  editingId: null,
};


/* ═══════════════════════════════════════════════════════════════════════════
   FILTER / SORT
═══════════════════════════════════════════════════════════════════════════ */
function applyFilters() {
  const q     = document.getElementById('searchInput').value.toLowerCase().trim();
  const cat   = document.getElementById('filterCat').value;
  const ubi   = document.getElementById('filterUbi').value;
  const tipo  = document.getElementById('filterTipo').value;
  const stock = document.getElementById('filterStock').value;

  document.getElementById('searchClear').classList.toggle('visible', q.length > 0);

  State.filtered = Store.getAll().filter(d => {
    if (q && !`${d.nombre} ${d.marca} ${d.modelo} ${d.codigo} ${d.notas}`.toLowerCase().includes(q)) return false;
    if (cat   && d.categoria !== cat)   return false;
    if (ubi   && d.ubicacion !== ubi)   return false;
    if (tipo  && d.tipo      !== tipo)  return false;
    if (stock === 'zero' && d.cant !== 0) return false;
    if (stock === 'low'  && !(typeof d.cant === 'number' && d.cant > 0 && d.cant <= 3)) return false;
    if (stock === 'ok'   && !(typeof d.cant === 'number' && d.cant > 3)) return false;
    return true;
  });

  State.filtered.sort((a, b) => {
    let av = a[State.sortCol] ?? '', bv = b[State.sortCol] ?? '';
    if (typeof av === 'number' && typeof bv === 'number') return (av - bv) * State.sortDir;
    return String(av).localeCompare(String(bv), 'es') * State.sortDir;
  });

  State.page = 1;
  render();
}

function setSort(col) {
  if (State.sortCol === col) State.sortDir *= -1;
  else { State.sortCol = col; State.sortDir = 1; }
  applyFilters();
}

function clearSearch() {
  document.getElementById('searchInput').value = '';
  applyFilters();
}

function resetFilters() {
  document.getElementById('searchInput').value = '';
  ['filterCat','filterUbi','filterTipo','filterStock'].forEach(id => {
    document.getElementById(id).value = '';
  });
  applyFilters();
}


/* ═══════════════════════════════════════════════════════════════════════════
   RENDER
═══════════════════════════════════════════════════════════════════════════ */
function render() {
  if (Store.isEmpty()) { renderWelcome(); return; }

  document.getElementById('tableWrap').style.display = 'block';
  document.getElementById('welcomeScreen')?.remove();

  const start    = (State.page - 1) * PAGE_SIZE;
  const pageData = State.filtered.slice(start, start + PAGE_SIZE);
  const total    = State.filtered.length;

  document.getElementById('resultInfo').innerHTML =
    `<span><strong>${total}</strong> ítem${total !== 1 ? 's' : ''} de ${Store.getAll().length} totales</span>
     <span>Página ${State.page} / ${Math.ceil(total / PAGE_SIZE) || 1}</span>`;

  if (State.view === 'table') renderTable(pageData);
  else                        renderGrid(pageData);

  renderPagination();
}

/* ── WELCOME SCREEN (sin datos) ── */
function renderWelcome() {
  document.getElementById('tableWrap').style.display = 'none';

  if (document.getElementById('welcomeScreen')) return;

  const el = document.createElement('div');
  el.id = 'welcomeScreen';
  el.className = 'welcome-screen';
  el.innerHTML = `
    <div class="welcome-inner">
      <div class="welcome-icon">📦</div>
      <h2>El inventario está vacío</h2>
      <p>Para empezar elegí una de estas opciones:</p>
      <div class="welcome-options">
        <div class="welcome-option" onclick="UI.openSync(); UI.switchSyncTab('sheets')">
          <div class="welcome-opt-icon">📊</div>
          <h3>Conectar Google Sheets</h3>
          <p>Si ya tenés un Sheet con datos, configurá la URL y se importan automáticamente.</p>
        </div>
        <div class="welcome-option" onclick="UI.openSync(); UI.switchSyncTab('excel')">
          <div class="welcome-opt-icon">📁</div>
          <h3>Importar Excel / CSV</h3>
          <p>Cargá el archivo <code>Inventario_Normalizado.xlsx</code> que generamos antes.</p>
        </div>
        <div class="welcome-option" onclick="UI.openModal(null)">
          <div class="welcome-opt-icon">✏</div>
          <h3>Agregar manualmente</h3>
          <p>Empezá cargando ítems uno por uno desde cero.</p>
        </div>
      </div>
    </div>
  `;
  document.querySelector('.main').appendChild(el);
}

function checkEmpty() {
  if (Store.isEmpty()) renderWelcome();
  else document.getElementById('welcomeScreen')?.remove();
}

/* ── TABLE ── */
const COLUMNS = [
  { k: 'id',        label: '#',         w: '42px' },
  { k: 'nombre',    label: 'Nombre' },
  { k: 'categoria', label: 'Categoría', w: '130px' },
  { k: 'tipo',      label: 'Tipo',      w: '104px' },
  { k: 'cant',      label: 'Cant.',     w: '58px'  },
  { k: 'codigo',    label: 'Código',    w: '72px'  },
  { k: 'venta',     label: 'Venta',     w: '72px'  },
  { k: 'ubicacion', label: 'Ubicación', w: '200px' },
  { k: 'notas',     label: 'Notas',     w: '150px' },
  { k: '_',         label: '',          w: '48px'  },
];

function renderTable(rows) {
  if (!rows.length) {
    document.getElementById('tableView').innerHTML = emptyHTML();
    return;
  }
  let h = `<table><thead><tr>`;
  COLUMNS.forEach(c => {
    if (c.k === '_') { h += `<th style="width:${c.w}"></th>`; return; }
    const sorted = State.sortCol === c.k;
    const arrow  = sorted ? (State.sortDir === 1 ? '▲' : '▼') : '⇅';
    h += `<th onclick="setSort('${c.k}')" ${c.w ? `style="width:${c.w}"` : ''} class="${sorted ? 'sorted' : ''}">
            ${c.label} <span class="sort-arrow">${arrow}</span></th>`;
  });
  h += `</tr></thead><tbody>`;
  rows.forEach(d => {
    const q  = d.cant === '' || d.cant === null ? '—' : d.cant;
    const qc = typeof d.cant === 'number' ? qtyClass(d.cant) : '';
    h += `<tr>
      <td class="td-id">${d.id}</td>
      <td class="td-nombre">${esc(d.nombre)}
        ${d.marca ? `<small>${esc(d.marca)}${d.modelo ? ' · ' + esc(d.modelo) : ''}</small>` : ''}
      </td>
      <td><span class="pill pill-cat">${esc(d.categoria || '—')}</span></td>
      <td>${tipoPill(d.tipo)}</td>
      <td class="td-qty ${qc}">${q}</td>
      <td style="font-family:monospace;font-size:.75rem;color:#aaa">${d.codigo || '—'}</td>
      <td class="td-price">${fmtPrice(d.venta)}</td>
      <td><span class="ubi-tag" title="${esc(d.ubicacion)}">${esc(shortStr(d.ubicacion, 30))}</span></td>
      <td class="td-notas" title="${esc(d.notas)}">${esc(d.notas) || ''}</td>
      <td><button class="action-btn" onclick="UI.openModal(${d.id})">✏</button></td>
    </tr>`;
  });
  h += `</tbody></table>`;
  document.getElementById('tableView').innerHTML = h;
}

/* ── GRID ── */
function renderGrid(rows) {
  if (!rows.length) {
    document.getElementById('gridView').innerHTML = emptyHTML();
    return;
  }
  let h = `<div class="grid">`;
  rows.forEach(d => {
    const q  = d.cant === '' || d.cant === null ? '?' : d.cant;
    const qc = typeof d.cant === 'number' ? qtyClass(d.cant) : '';
    h += `<div class="card">
      <div class="card-top">
        <span class="card-cat">${esc(d.categoria || '—')}</span>
        <span class="card-qty ${qc}">${q}</span>
      </div>
      <div class="card-name">${esc(d.nombre)}</div>
      <div class="card-marca">${d.marca ? esc(d.marca) + (d.modelo ? ' · ' + esc(d.modelo) : '') : '&nbsp;'}</div>
      <div class="card-footer">
        <div>
          <div class="card-ubi">${esc(shortStr(d.ubicacion, 28))}</div>
          ${d.venta ? `<div class="card-price">Venta: ${fmtPrice(d.venta)}</div>` : ''}
        </div>
        <button class="action-btn" onclick="UI.openModal(${d.id})">✏</button>
      </div>
      ${d.codigo ? `<div class="card-code">#${d.codigo}</div>` : ''}
    </div>`;
  });
  h += `</div>`;
  document.getElementById('gridView').innerHTML = h;
}

/* ── PAGINATION ── */
function renderPagination() {
  const total = Math.ceil(State.filtered.length / PAGE_SIZE);
  if (total <= 1) { document.getElementById('pagination').innerHTML = ''; return; }
  const p = State.page;
  let h = `<span>${State.filtered.length} ítems</span><div class="page-btns">`;
  h += `<button class="page-btn" onclick="goPage(${p-1})" ${p<=1?'disabled':''}>‹</button>`;
  for (let i = Math.max(1,p-2); i <= Math.min(total,p+2); i++) {
    h += `<button class="page-btn ${i===p?'active':''}" onclick="goPage(${i})">${i}</button>`;
  }
  h += `<button class="page-btn" onclick="goPage(${p+1})" ${p>=total?'disabled':''}>›</button>`;
  h += `</div><span>Pág. ${p}/${total}</span>`;
  document.getElementById('pagination').innerHTML = h;
}

function goPage(p) { State.page = p; render(); }

/* ── STATS ── */
function renderStats() {
  const all     = Store.getAll();
  const units   = all.reduce((a, d) => a + (typeof d.cant === 'number' ? d.cant : 0), 0);
  const locs    = new Set(all.map(d => d.ubicacion)).size;
  const pending = all.filter(d => d.tipo === 'Pendiente').length;
  const noStock = all.filter(d => d.cant === 0).length;
  const withPx  = all.filter(d => d.venta).length;

  document.getElementById('statsBar').innerHTML = `
    <div class="stat"><div>
      <div class="stat-val">${all.length}</div>
      <div class="stat-label">Referencias</div>
    </div></div>
    <div class="stat-divider"></div>
    <div class="stat"><div>
      <div class="stat-val">${units.toLocaleString('es')}</div>
      <div class="stat-label">Unidades</div>
    </div></div>
    <div class="stat-divider"></div>
    <div class="stat"><div>
      <div class="stat-val">${locs}</div>
      <div class="stat-label">Ubicaciones</div>
    </div></div>
    <div class="stat-divider"></div>
    <div class="stat"><div>
      <div class="stat-val stat-val--alert">${pending}</div>
      <div class="stat-label">Pendientes</div>
    </div></div>
    <div class="stat-divider"></div>
    <div class="stat"><div>
      <div class="stat-val stat-val--warn">${noStock}</div>
      <div class="stat-label">Sin stock</div>
    </div></div>
    <div class="stat-divider"></div>
    <div class="stat"><div>
      <div class="stat-val">${withPx}</div>
      <div class="stat-label">Con precio</div>
    </div></div>`;
}

/* ── FILTER DROPDOWNS ── */
function populateFilters() {
  const all   = Store.getAll();
  const cats  = [...new Set(all.map(d => d.categoria))].sort();
  const tipos = [...new Set(all.map(d => d.tipo))].sort();

  fillSelect('filterCat',  'Categorías',  cats);
  fillSelect('filterTipo', 'Tipos',       tipos);
  fillSelect('filterUbi',  'Ubicaciones', Zones.getAllZones());

  fillSelectForm('fCategoria', cats,                 '+ Nueva categoría…');
  fillSelectForm('fUbicacion', Zones.getAllZones(),   '+ Nueva ubicación…');
}

function fillSelect(id, placeholder, values) {
  const sel = document.getElementById(id);
  const cur = sel.value;
  sel.innerHTML = `<option value="">${placeholder}</option>`;
  values.forEach(v => {
    const o = document.createElement('option');
    o.value = v; o.text = v;
    if (v === cur) o.selected = true;
    sel.appendChild(o);
  });
}

function fillSelectForm(id, values, newLabel) {
  const sel = document.getElementById(id);
  sel.innerHTML = '';
  values.forEach(v => {
    const o = document.createElement('option');
    o.value = v; o.text = v;
    sel.appendChild(o);
  });
  const onew = document.createElement('option');
  onew.value = '__new'; onew.text = newLabel;
  sel.appendChild(onew);
}


/* ═══════════════════════════════════════════════════════════════════════════
   APP — acciones de datos
═══════════════════════════════════════════════════════════════════════════ */
const App = {

  saveItem() {
    const nombre = document.getElementById('fNombre').value.trim();
    if (!nombre) { UI.toast('⚠ El nombre es requerido'); return; }

    let cat = document.getElementById('fCategoria').value;
    if (cat === '__new') { cat = prompt('Nueva categoría:')?.trim() || ''; if (!cat) return; }
    let ubi = document.getElementById('fUbicacion').value;
    if (ubi === '__new') { ubi = prompt('Nueva ubicación:')?.trim() || ''; if (!ubi) return; }

    const readNum = id => { const v = document.getElementById(id).value; return v === '' ? '' : Number(v); };

    const item = {
      id:            State.editingId ?? Store.nextId(),
      nombre,
      categoria:     cat,
      marca:         document.getElementById('fMarca').value.trim(),
      modelo:        document.getElementById('fModelo').value.trim(),
      clasificacion: document.getElementById('fClasificacion').value,
      cant:          readNum('fCant'),
      codigo:        document.getElementById('fCodigo').value.trim(),
      costo:         readNum('fCosto'),
      venta:         readNum('fVenta'),
      reventa:       readNum('fReventa'),
      tipo:          document.getElementById('fTipo').value,
      ubicacion:     ubi,
      notas:         document.getElementById('fNotas').value.trim(),
    };

    Store.upsert(item);
    UI.closeModal('itemModalBg');
    populateFilters();
    applyFilters();
    renderStats();
    checkEmpty();
    UI.toast(State.editingId ? '✅ Ítem actualizado' : '✅ Ítem agregado');
  },

  confirmDelete() {
    const item = Store.getAll().find(d => d.id === State.editingId);
    UI.confirm('Eliminar ítem', `¿Eliminar "${item?.nombre}"?`, () => {
      Store.remove(State.editingId);
      UI.closeModal('itemModalBg');
      populateFilters();
      applyFilters();
      renderStats();
      checkEmpty();
      UI.toast('🗑 Ítem eliminado');
    });
  },

  exportXLSX() {
    if (typeof XLSX === 'undefined') { UI.toast('⚠ SheetJS no disponible'); return; }
    const data    = State.filtered.length < Store.getAll().length ? State.filtered : Store.getAll();
    const headers = XLSX_COLS.map(c => c.header);
    const rows    = data.map(d => XLSX_COLS.map(c => d[c.key] ?? ''));
    const ws      = XLSX.utils.aoa_to_sheet([headers, ...rows]);
    ws['!cols']   = [5,42,22,18,22,12,10,10,12,14,16,12,32,30].map(w => ({ wch: w }));

    const mkSummary = (groupKey) => {
      const map = {};
      data.forEach(d => {
        const k = d[groupKey] || '—';
        if (!map[k]) map[k] = { refs:0, units:0 };
        map[k].refs++;
        map[k].units += typeof d.cant === 'number' ? d.cant : 0;
      });
      return XLSX.utils.aoa_to_sheet([
        [groupKey === 'categoria' ? 'Categoría' : 'Ubicación', 'Referencias', 'Unidades'],
        ...Object.entries(map).sort().map(([k,v]) => [k, v.refs, v.units]),
      ]);
    };

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws,                   'Inventario');
    XLSX.utils.book_append_sheet(wb, mkSummary('categoria'), 'Por Categoría');
    XLSX.utils.book_append_sheet(wb, mkSummary('ubicacion'), 'Por Ubicación');

    XLSX.writeFile(wb, `inventario_${dateStamp()}.xlsx`);
    UI.toast('✅ Excel descargado');
  },

  exportCSV() {
    const data = State.filtered.length < Store.getAll().length ? State.filtered : Store.getAll();
    let csv = XLSX_COLS.map(c => c.header).join(',') + '\n';
    data.forEach(d => {
      csv += XLSX_COLS.map(c => `"${String(d[c.key] ?? '').replace(/"/g,'""')}"`).join(',') + '\n';
    });
    downloadText(`inventario_${dateStamp()}.csv`, '\uFEFF' + csv, 'text/csv;charset=utf-8');
    UI.toast('✅ CSV descargado');
  },

  handleFileImport(e) {
    const file = e.target.files?.[0];
    if (file) this._processFile(file);
    e.target.value = '';
  },
  dragOver(e)  { e.preventDefault(); document.getElementById('importZoneInner').classList.add('drag-over'); },
  dragLeave()  { document.getElementById('importZoneInner').classList.remove('drag-over'); },
  dropFile(e)  { e.preventDefault(); document.getElementById('importZoneInner').classList.remove('drag-over'); const f = e.dataTransfer.files?.[0]; if (f) this._processFile(f); },

  _processFile(file) {
    if (typeof XLSX === 'undefined') { UI.toast('⚠ SheetJS no disponible'); return; }
    const reader = new FileReader();
    reader.onload = (e) => {
      try {
        const wb   = XLSX.read(e.target.result, { type: 'array' });
        const ws   = wb.Sheets[wb.SheetNames[0]];
        const rows = XLSX.utils.sheet_to_json(ws, { defval: '' });
        if (!rows.length) { App._setSyncInfo('El archivo está vacío.', 'error'); return; }

        const valid = rows.map((r, i) => normalizeImportedRow(r, i)).filter(r => r.nombre);
        if (!valid.length) { App._setSyncInfo('No se reconocieron las columnas. Usá el formato exportado por el sistema.', 'error'); return; }

        const mode = document.querySelector('input[name="importMode"]:checked')?.value || 'replace';
        UI.confirm(
          'Confirmar importación',
          `${valid.length} registros válidos · Modo: "${mode === 'replace' ? 'Reemplazar todo' : 'Fusionar'}". ¿Continuar?`,
          () => {
            if (mode === 'replace') {
              Store.setAll(valid);
            } else {
              const byId = Object.fromEntries(Store.getAll().map(d => [d.id, d]));
              valid.forEach(item => { byId[item.id] = item; });
              Store.setAll(Object.values(byId).sort((a,b) => a.id - b.id));
            }
            populateFilters(); applyFilters(); renderStats(); checkEmpty();
            App._setSyncInfo(`✅ ${valid.length} ítems importados.`, 'success');
            UI.toast(`✅ ${valid.length} ítems importados`);
          }
        );
      } catch (err) {
        App._setSyncInfo(`Error: ${err.message}`, 'error');
      }
    };
    reader.readAsArrayBuffer(file);
  },

  _setSyncInfo(msg, type) {
    const el = document.getElementById('syncInfo');
    if (!el) return;
    el.textContent = msg;
    el.className   = msg ? `sync-info visible ${type||''}` : 'sync-info';
  },
};


/* ═══════════════════════════════════════════════════════════════════════════
   UI — modal, vista, toast, confirm
═══════════════════════════════════════════════════════════════════════════ */
const UI = {

  openModal(id) {
    State.editingId = id;
    const isNew = id === null;
    document.getElementById('modalTitle').textContent    = isNew ? 'Agregar ítem' : `Editar ítem #${id}`;
    document.getElementById('modalSub').textContent      = isNew ? 'Complete los datos del nuevo producto' : (Store.getAll().find(d => d.id === id)?.nombre || '');
    document.getElementById('btnDelete').style.display   = isNew ? 'none' : 'inline-flex';
    if (isNew) clearForm(); else fillForm(Store.getAll().find(d => d.id === id));
    document.getElementById('itemModalBg').classList.add('open');
  },

  closeModal(bgId) {
    document.getElementById(bgId).classList.remove('open');
    if (bgId === 'syncModalBg') {
      document.getElementById('syncInfo') && (document.getElementById('syncInfo').className = 'sync-info');
      document.getElementById('sheetsInfo') && (document.getElementById('sheetsInfo').className = 'sync-info');
    }
  },

  closeBgClick(e, bgId) { if (e.target.id === bgId) this.closeModal(bgId); },

  openSync() {
    // Cargar URL guardada en el input
    document.getElementById('sheetsUrl').value = Sheets.getUrl();
    updateSheetsStatusBanner();
    document.getElementById('syncModalBg').classList.add('open');
  },

  openZones() { Zones.open(); },

  setView(v) {
    State.view = v;
    document.getElementById('tableView').style.display   = v === 'table' ? 'block' : 'none';
    document.getElementById('gridView').style.display    = v === 'grid'  ? 'block' : 'none';
    document.getElementById('viewBtnTable').classList.toggle('active', v === 'table');
    document.getElementById('viewBtnGrid').classList.toggle('active',  v === 'grid');
    render();
  },

  switchSyncTab(tab) {
    document.getElementById('syncPanelSheets').style.display = tab === 'sheets' ? 'block' : 'none';
    document.getElementById('syncPanelExcel').style.display  = tab === 'excel'  ? 'block' : 'none';
    document.getElementById('tabSheets').classList.toggle('active', tab === 'sheets');
    document.getElementById('tabExcel').classList.toggle('active',  tab === 'excel');
  },

  toast(msg) {
    const el = document.getElementById('toast');
    el.textContent = msg;
    el.classList.add('show');
    clearTimeout(UI._toastTimer);
    UI._toastTimer = setTimeout(() => el.classList.remove('show'), 2800);
  },

  confirm(title, msg, onOk) {
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmMsg').textContent   = msg;
    const okBtn = document.getElementById('confirmOk');
    okBtn.onclick = () => { this.closeModal('confirmModalBg'); onOk(); };
    document.getElementById('confirmModalBg').classList.add('open');
  },
};


/* ── Sheets URL save desde el modal ── */
function saveSheetUrl() {
  const val = document.getElementById('sheetsUrl').value.trim();
  if (val && !val.startsWith('https://script.google.com')) {
    UI.toast('⚠ La URL debe ser de script.google.com');
    return;
  }
  Sheets.saveUrl(val);
  updateSheetsStatusBanner();
  UI.toast(val ? '✅ URL guardada' : '✅ URL eliminada');
  if (val) Status.set('ok');
}

function updateSheetsStatusBanner() {
  const url = Sheets.getUrl();
  if (url) {
    setSyncStatus('ok', 'Configurado', url.slice(0, 52) + (url.length > 52 ? '…' : ''));
  } else {
    setSyncStatus('unconfigured', 'Sin configurar', 'Ingresá la URL del Web App');
  }
}

function setSyncStatus(state, label, detail) {
  const dot = document.getElementById('sheetsStatusDot');
  const lbl = document.getElementById('sheetsStatusLabel');
  const det = document.getElementById('sheetsStatusDetail');
  if (!dot || !lbl || !det) return; // modal no abierto todavía
  dot.className   = `sheets-status-dot sheets-status-dot--${state}`;
  lbl.textContent = label;
  det.textContent = detail;
}


/* ═══════════════════════════════════════════════════════════════════════════
   FORM HELPERS
═══════════════════════════════════════════════════════════════════════════ */
function clearForm() {
  ['fNombre','fMarca','fModelo','fCodigo','fNotas'].forEach(id => document.getElementById(id).value = '');
  ['fCant','fCosto','fVenta','fReventa'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('fTipo').value = 'Accesorio';
  document.getElementById('fClasificacion').value = '';
}

function fillForm(d) {
  if (!d) return;
  const set = (id, val) => { document.getElementById(id).value = val ?? ''; };
  set('fNombre', d.nombre); set('fMarca', d.marca); set('fModelo', d.modelo);
  set('fCodigo', d.codigo); set('fNotas', d.notas);
  set('fCant',   d.cant === '' || d.cant == null ? '' : d.cant);
  set('fCosto',  d.costo); set('fVenta', d.venta); set('fReventa', d.reventa);
  set('fTipo', d.tipo); set('fClasificacion', d.clasificacion);
  setSelectVal('fCategoria', d.categoria);
  setSelectVal('fUbicacion', d.ubicacion);
}

function setSelectVal(id, val) {
  const sel = document.getElementById(id);
  for (const o of sel.options) { if (o.value === val) { o.selected = true; return; } }
}


/* ═══════════════════════════════════════════════════════════════════════════
   IMPORT NORMALIZER
═══════════════════════════════════════════════════════════════════════════ */
function normalizeImportedRow(r, idx) {
  const MAP = {
    'id':'id',
    'nombre':'nombre','name':'nombre','descripcion':'nombre','descripción':'nombre',
    'categoría':'categoria','categoria':'categoria','category':'categoria',
    'marca':'marca','brand':'marca',
    'modelo':'modelo','model':'modelo',
    'clasificación':'clasificacion','clasificacion':'clasificacion',
    'cantidad':'cant','cant':'cant','qty':'cant','stock':'cant',
    'código':'codigo','codigo':'codigo','code':'codigo',
    'costo (usd)':'costo','costo':'costo','cost':'costo',
    'precio venta (usd)':'venta','precio venta':'venta','venta':'venta','price':'venta',
    'precio reventa (usd)':'reventa','precio reventa':'reventa','reventa':'reventa',
    'tipo':'tipo','type':'tipo',
    'ubicación':'ubicacion','ubicacion':'ubicacion','location':'ubicacion',
    'notas':'notas','notes':'notas','observaciones':'notas',
  };

  const out = { id:'', nombre:'', categoria:'', marca:'', modelo:'', clasificacion:'',
                cant:'', codigo:'', costo:'', venta:'', reventa:'', tipo:'Accesorio',
                ubicacion:'', notas:'' };

  Object.entries(r).forEach(([k, v]) => {
    const key = MAP[k.toLowerCase().trim()];
    if (key) out[key] = v;
  });

  out.id      = out.id     ? Number(out.id)      : (idx + 1);
  out.cant    = out.cant   !== '' ? Number(out.cant)    : '';
  out.costo   = out.costo  !== '' ? Number(out.costo)   : '';
  out.venta   = out.venta  !== '' ? Number(out.venta)   : '';
  out.reventa = out.reventa!== '' ? Number(out.reventa) : '';
  ['id','cant','costo','venta','reventa'].forEach(k => { if (isNaN(out[k])) out[k] = ''; });
  return out;
}


/* ═══════════════════════════════════════════════════════════════════════════
   ZONES
═══════════════════════════════════════════════════════════════════════════ */
const Zones = {
  _getMap() {
    const map = {};
    Store.getAll().forEach(d => { if (d.ubicacion) map[d.ubicacion] = (map[d.ubicacion] || 0) + 1; });
    return map;
  },
  _loadEmpty()       { try { return JSON.parse(localStorage.getItem(LS_ZONES_KEY)) || []; } catch { return []; } },
  _saveEmpty(zones)  { localStorage.setItem(LS_ZONES_KEY, JSON.stringify(zones)); },
  _addEmpty(name)    { const z = this._loadEmpty(); if (!z.includes(name)) z.push(name); this._saveEmpty(z); },
  _removeEmpty(name) { this._saveEmpty(this._loadEmpty().filter(z => z !== name)); },

  getAllZones() {
    const all = [...new Set([...Object.keys(this._getMap()), ...this._loadEmpty()])];
    return all.sort((a, b) => a.localeCompare(b, 'es'));
  },

  open() {
    this.render();
    document.getElementById('newZoneName').value = '';
    document.getElementById('zonesModalBg').classList.add('open');
  },

  render() {
    const map    = this._getMap();
    const sorted = this.getAllZones();
    this._rendered = sorted; // guardar para lookup por índice
    const list   = document.getElementById('zoneList');
    if (!sorted.length) { list.innerHTML = `<div class="zone-empty">No hay zonas registradas.</div>`; return; }
    list.innerHTML = sorted.map((name, i) => {
      const count    = map[name] || 0;
      const badgeCls = count === 0 ? 'zone-count-badge--zero' : '';
      return `<div class="zone-row">
        <div class="zone-row-name">${esc(name)}</div>
        <div class="zone-col-count"><span class="zone-count-badge ${badgeCls}">${count}</span></div>
        <div class="zone-col-actions"><div class="zone-actions">
          <button class="zone-btn" onclick="Zones.openRename(${i})">✏ Renombrar</button>
          <button class="zone-btn zone-btn--del" onclick="Zones.openDelete(${i})">🗑</button>
        </div></div>
      </div>`;
    }).join('');
  },

  add() {
    const name = document.getElementById('newZoneName').value.trim();
    if (!name) { UI.toast('⚠ Escribí un nombre'); return; }
    if (this.getAllZones().includes(name)) { UI.toast('⚠ Esa zona ya existe'); return; }
    this._addEmpty(name);
    document.getElementById('newZoneName').value = '';
    this.render();
    populateFilters();
    UI.toast(`✅ Zona "${name}" creada`);
  },

  _renamingZone: null,
  openRename(idx) {
    const name = this._rendered[idx];
    if (!name) return;
    this._renamingZone = name;
    document.getElementById('renameZoneOldName').textContent = `Zona actual: "${name}"`;
    document.getElementById('renameZoneInput').value = name;
    document.getElementById('renameZoneModalBg').classList.add('open');
    setTimeout(() => { const i = document.getElementById('renameZoneInput'); i.focus(); i.select(); }, 100);
  },
  confirmRename() {
    const oldName = this._renamingZone;
    const newName = document.getElementById('renameZoneInput').value.trim();
    if (!newName)          { UI.toast('⚠ El nombre no puede estar vacío'); return; }
    if (newName === oldName) { UI.closeModal('renameZoneModalBg'); return; }
    if (this.getAllZones().includes(newName)) { UI.toast('⚠ Ya existe esa zona'); return; }
    const all = Store.getAll();
    let changed = 0;
    all.forEach(d => { if (d.ubicacion === oldName) { d.ubicacion = newName; changed++; } });
    Store.setAll(all);
    const empty = this._loadEmpty().map(z => z === oldName ? newName : z);
    this._saveEmpty(empty);
    UI.closeModal('renameZoneModalBg');
    this.render(); populateFilters(); applyFilters();
    UI.toast(`✅ "${oldName}" → "${newName}" (${changed} ítems)`);
  },

  _deletingZone: null,
  openDelete(idx) {
    const name = this._rendered[idx];
    if (!name) return;
    this._deletingZone = name;
    const count    = this._getMap()[name] || 0;
    const others   = this.getAllZones().filter(z => z !== name);
    document.getElementById('deleteZoneName').textContent = `Zona: "${name}"`;
    let body = '';
    if (count === 0) {
      body = `<div class="delete-zone-info">Esta zona no tiene ítems. Se eliminará sin afectar el inventario.</div>`;
    } else {
      const opts = others.map(z => `<option value="${esc(z)}">${esc(z)}</option>`).join('');
      body = `<div class="delete-zone-info">Tiene <strong>${count} ítem${count!==1?'s':''}</strong>. ¿Qué hacemos con ellos?</div>
      <div class="delete-zone-reassign">
        <label class="radio-label"><input type="radio" name="deleteZoneMode" value="reassign" checked> Mover a otra zona:</label>
        <select id="reassignZoneSelect" style="margin-left:22px">${opts||'<option value="">— Sin zonas —</option>'}</select>
        <label class="radio-label" style="margin-top:6px"><input type="radio" name="deleteZoneMode" value="clear"> Dejar sin zona</label>
      </div>`;
    }
    document.getElementById('deleteZoneBody').innerHTML = body;
    document.getElementById('deleteZoneOk').onclick = () => this.confirmDelete(count > 0);
    document.getElementById('deleteZoneModalBg').classList.add('open');
  },
  confirmDelete(hasItems) {
    const name = this._deletingZone;
    if (hasItems) {
      const mode = document.querySelector('input[name="deleteZoneMode"]:checked')?.value;
      const all  = Store.getAll();
      if (mode === 'reassign') {
        const target = document.getElementById('reassignZoneSelect')?.value;
        if (!target) { UI.toast('⚠ Seleccioná una zona destino'); return; }
        all.forEach(d => { if (d.ubicacion === name) d.ubicacion = target; });
      } else {
        all.forEach(d => { if (d.ubicacion === name) d.ubicacion = ''; });
      }
      Store.setAll(all);
    }
    this._removeEmpty(name);
    UI.closeModal('deleteZoneModalBg');
    this.render(); populateFilters(); applyFilters(); renderStats();
    UI.toast(`✅ Zona "${name}" eliminada`);
  },
};


/* ═══════════════════════════════════════════════════════════════════════════
   UTILS
═══════════════════════════════════════════════════════════════════════════ */
function esc(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function fmtPrice(v)   { return v ? `$${Number(v).toLocaleString('es',{minimumFractionDigits:0})}` : '—'; }
function qtyClass(q)   { if (q === 0) return 'qty-zero'; if (q > 0 && q <= 3) return 'qty-low'; return ''; }
function tipoPill(t)   { return `<span class="pill pill-${esc((t||'').replace(/\s/g,'\\ '))}">${esc(t||'—')}</span>`; }
function shortStr(s,n) { return s ? (s.length>n ? s.slice(0,n-1)+'…' : s) : '—'; }
function dateStamp()   { const d=new Date(); return `${d.getFullYear()}${pad(d.getMonth()+1)}${pad(d.getDate())}`; }
function timeNow()     { return new Date().toLocaleTimeString('es',{hour:'2-digit',minute:'2-digit'}); }
function pad(n)        { return String(n).padStart(2,'0'); }
function downloadText(fname, text, mime) { const a=document.createElement('a'); a.href=`data:${mime},`+encodeURIComponent(text); a.download=fname; a.click(); }
function emptyHTML() {
  return `<div class="empty"><div class="empty-icon">🔍</div><h3>Sin resultados</h3>
    <p>Intentá con otros filtros o <button class="btn btn-outline btn-sm" style="margin-left:6px" onclick="resetFilters()">limpiar filtros</button></p></div>`;
}


/* ═══════════════════════════════════════════════════════════════════════════
   EVENT LISTENERS
═══════════════════════════════════════════════════════════════════════════ */
document.getElementById('searchInput').addEventListener('input', applyFilters);
['filterCat','filterUbi','filterTipo','filterStock'].forEach(id =>
  document.getElementById(id).addEventListener('change', applyFilters));

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    ['itemModalBg','syncModalBg','confirmModalBg','zonesModalBg','renameZoneModalBg','deleteZoneModalBg']
      .forEach(id => document.getElementById(id)?.classList.remove('open'));
  }
  if ((e.ctrlKey || e.metaKey) && e.key === 'n') { e.preventDefault(); UI.openModal(null); }
});


/* ═══════════════════════════════════════════════════════════════════════════
   BOOT — arranque de la aplicación
═══════════════════════════════════════════════════════════════════════════ */
async function boot() {
  // 1. Cargar datos del localStorage (caché offline)
  Store.load();

  // 2. Si hay URL de Sheets configurada → pull automático (actualiza desde la nube)
  if (Sheets.isConfigured()) {
    const pulled = await Sheets.bootPull();
    if (!pulled && Store.isEmpty()) {
      // Sheets configurado pero vacío, y sin caché local
      Status.set('nodata');
    }
  } else if (Store.isEmpty()) {
    Status.set('nodata');
  } else {
    Status.set('ok');
  }

  // 3. Renderizar
  populateFilters();
  applyFilters();
  renderStats();
  checkEmpty();
}

boot();
