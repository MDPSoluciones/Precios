// Tareas — panel MDP Soluciones
// Admin: 3 vistas (por estado, por empleado, lista) y todos los empleados.
// Empleado: sus tareas + la bolsa general (tareas sin asignar) para tomarlas.

const ESTADOS = [
    { k: 'pendiente',  t: 'Pendiente',  c: 'var(--pend)' },
    { k: 'proceso',    t: 'En proceso', c: 'var(--proc)' },
    { k: 'revision',   t: 'Revisión',   c: 'var(--rev)' },
    { k: 'finalizado', t: 'Finalizado', c: 'var(--fin)' },
    { k: 'cancelado',  t: 'Cancelado',  c: 'var(--canc)' }
];
const ACTIVOS = ['pendiente', 'proceso', 'revision'];
const COLORES = ['#2b6cb0', '#b05a8a', '#2f7d4f', '#b7791f', '#6b4fa0', '#c05621', '#2c7a7b', '#303030', '#9b2c2c'];

let tareas = [], usuarios = [], YO = '', ADMIN = false;
let vista = leerPref('tareas_vista', 'a');
let filtroEstado = '', soloMias = false, orden = 'vence', abierta = null;

const $ = s => document.querySelector(s);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const igual = (a, b) => String(a).toLowerCase() === String(b).toLowerCase();
const tiene = (t, n) => t.asignados.some(a => igual(a, n));
const hoy = () => new Date().toLocaleDateString('sv-SE'); // AAAA-MM-DD local
const fechaCorta = f => f ? f.slice(0, 10).split('-').reverse().slice(0, 2).join('/') : '';
const fechaHora = f => f ? `${fechaCorta(f)}${f.length > 10 ? ' ' + f.slice(11, 16) : ''}` : '';
const estadoInfo = k => ESTADOS.find(e => e.k === k) || ESTADOS[0];
const pct = t => t.estado === 'finalizado' ? 100 : (t.pasos.length ? Math.round(100 * t.pasos.filter(p => p.hecho).length / t.pasos.length) : 0);
const tarde = t => t.vence && t.vence < hoy() && ACTIVOS.includes(t.estado);
const puedeEditar = t => ADMIN || tiene(t, YO) || igual(t.creado_por, YO);
const puedeCerrar = t => ADMIN || igual(t.creado_por, YO);

function leerPref(k, def) { try { return localStorage.getItem(k) || def; } catch { return def; } }
function guardarPref(k, v) { try { localStorage.setItem(k, v); } catch { /* sin almacenamiento */ } }

function color(n) {
    let h = 0;
    for (const ch of String(n).toLowerCase()) h = (h * 31 + ch.charCodeAt(0)) >>> 0;
    return COLORES[h % COLORES.length];
}
const avatar = n => `<span class="av" style="background:${color(n)}" title="${esc(n)}">${esc(String(n).slice(0, 2).toUpperCase())}</span>`;
const avatares = t => `<span class="avs">${t.asignados.map(avatar).join('')}</span>`;
const venceTxt = t => t.vence ? `<span class="vence ${tarde(t) ? 'tarde' : ''}">${tarde(t) ? '⚠ Vencida ' : '📅 '}${fechaCorta(t.vence)}</span>` : '';

function aviso(msg, error) {
    const el = $('#toast');
    el.textContent = msg;
    el.className = 'toast on' + (error ? ' error' : '');
    clearTimeout(aviso.t);
    aviso.t = setTimeout(() => el.className = 'toast', error ? 4000 : 2200);
}

// ── API ──────────────────────────────────────────────────────────────────────
async function api(accion, datos) {
    const opts = datos
        ? { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ...datos, csrf: TAREAS_CFG.csrf }) }
        : {};
    const res = await fetch(`api.php?accion=${accion}`, opts);
    let r;
    try { r = await res.json(); } catch { throw new Error('Respuesta inválida del servidor'); }
    if (res.status === 401) { location.href = '../login.php'; throw new Error('Sesión vencida'); }
    if (r.status !== 'ok') throw new Error(r.mensaje || 'Error');
    return r;
}

async function cargar() {
    try {
        const r = await api('listar');
        tareas = r.tareas; usuarios = r.usuarios; YO = r.yo; ADMIN = r.admin;
        prepararFiltros();
        render();
        if (abierta) abrir(abierta, true);
    } catch (e) {
        $('#subtitulo').textContent = 'No se pudieron cargar las tareas: ' + e.message;
    }
}

// Aplica la tarea que devolvió el servidor y redibuja
function aplicar(t) {
    const i = tareas.findIndex(x => x.id === t.id);
    if (i >= 0) tareas[i] = t; else tareas.push(t);
    render();
    if (abierta === t.id) abrir(t.id, true);
}
async function accion(nombre, datos, okMsg) {
    try {
        const r = await api(nombre, datos);
        if (r.tarea) aplicar(r.tarea);
        if (r.eliminada) { tareas = tareas.filter(t => t.id !== r.eliminada); cerrar(); render(); }
        if (okMsg) aviso(okMsg);
        return r;
    } catch (e) {
        aviso(e.message, true);
        render();
        if (abierta) abrir(abierta, true);
    }
}
const cambiarEstado = (id, estado) => accion('estado', { id, estado });
const tomar = id => accion('tomar', { id }, '✓ Tarea asignada a vos');

// ── Filtros ──────────────────────────────────────────────────────────────────
function prepararFiltros() {
    const fe = $('#fEmpleado'), fc = $('#fCategoria');
    const selE = fe.value, selC = fc.value;
    fe.innerHTML = `<option value="">Todos los empleados</option><option value="__libre">Sin asignar (bolsa)</option>` +
        usuarios.map(u => `<option value="${esc(u.usuario)}">${esc(u.usuario)}</option>`).join('');
    fe.value = selE;
    const cats = [...new Set(tareas.map(t => t.categoria).filter(Boolean))].sort();
    fc.innerHTML = `<option value="">Todas las categorías</option>` + cats.map(c => `<option>${esc(c)}</option>`).join('');
    fc.value = cats.includes(selC) ? selC : '';
    $('#listaCategorias').innerHTML = cats.map(c => `<option value="${esc(c)}">`).join('');

    fe.classList.toggle('oculto', !ADMIN);
    $('#fMias').classList.toggle('oculto', !ADMIN);
    $('#selVistas').classList.toggle('oculto', !ADMIN);
}
['#fBuscar', '#fEmpleado', '#fPrioridad', '#fCategoria'].forEach(s => $(s).addEventListener('input', render));
$('#fMias').onclick = () => { soloMias = !soloMias; $('#fMias').classList.toggle('on', soloMias); render(); };

function filtradas(ignorarEstado) {
    const q = $('#fBuscar').value.toLowerCase().trim();
    const e = $('#fEmpleado').value, p = $('#fPrioridad').value, c = $('#fCategoria').value;
    return tareas.filter(t =>
        (!q || `${t.titulo} ${t.descripcion} ${t.categoria} ${t.asignados.join(' ')} #${t.id}`.toLowerCase().includes(q)) &&
        (!ADMIN || !e || (e === '__libre' ? !t.asignados.length : tiene(t, e))) &&
        (!p || t.prioridad === p) && (!c || t.categoria === c) &&
        (!ADMIN || !soloMias || tiene(t, YO)) &&
        (ignorarEstado || !filtroEstado || t.estado === filtroEstado));
}

function renderChipsEstado() {
    const base = filtradas(true);
    $('#fEstados').innerHTML = `<button class="chip ${!filtroEstado ? 'on' : ''}" data-e="">Todas <span>${base.length}</span></button>` +
        ESTADOS.map(e => `<button class="chip ${filtroEstado === e.k ? 'on' : ''}" data-e="${e.k}">${e.t} <span>${base.filter(t => t.estado === e.k).length}</span></button>`).join('');
    $('#fEstados').querySelectorAll('.chip').forEach(b => b.onclick = () => { filtroEstado = b.dataset.e; render(); });
}

// ── Piezas de UI ─────────────────────────────────────────────────────────────
function selEstado(t) {
    if (!puedeEditar(t)) return `<span class="estado-sel" style="background:${estadoInfo(t.estado).c};padding:3px 8px;border-radius:6px;color:#fff;font-size:11px;font-weight:700">${estadoInfo(t.estado).t}</span>`;
    return `<select class="estado-sel" style="background:${estadoInfo(t.estado).c}" data-estado-id="${t.id}">${opcionesEstado(t)}</select>`;
}
// Finalizar, cancelar o reabrir: solo admin o quien creó la tarea
function opcionesEstado(t) {
    return ESTADOS.map(e => {
        const cierra = ['finalizado', 'cancelado'].includes(e.k) || ['finalizado', 'cancelado'].includes(t.estado);
        const bloq = e.k !== t.estado && cierra && !puedeCerrar(t);
        return `<option value="${e.k}" ${e.k === t.estado ? 'selected' : ''} ${bloq ? 'disabled' : ''}>${e.t}${bloq ? ' (admin o creador)' : ''}</option>`;
    }).join('');
}

function tarjeta(t, opts = {}) {
    const asig = t.asignados.length
        ? `${avatares(t)}${opts.sinNombre ? '' : `<span>${esc(t.asignados.join(', '))}</span>`}`
        : `<span class="sinasig">Sin asignar</span>${ACTIVOS.includes(t.estado) ? `<button class="btn btn-oro mini" data-tomar="${t.id}">✋ Tomar</button>` : ''}`;
    const hechos = t.pasos.filter(p => p.hecho).length;
    return `<div class="card" draggable="${opts.drag && puedeEditar(t) ? 'true' : 'false'}" data-id="${t.id}">
        <div class="meta"><span class="prio ${t.prioridad}">${t.prioridad}</span>${t.categoria ? `<span class="cat">${esc(t.categoria)}</span>` : ''}${t.asignados.length > 1 ? '<span class="compartida">👥 Compartida</span>' : ''}${opts.conEstado ? selEstado(t) : ''}</div>
        <h4>${esc(t.titulo)}</h4>
        <div class="meta">${venceTxt(t)}${t.pasos.length ? `<span>☑ ${hechos}/${t.pasos.length}</span>` : ''}${t.comentarios.length ? `<span>💬 ${t.comentarios.length}</span>` : ''}</div>
        <div class="barra"><i style="width:${pct(t)}%"></i></div>
        <div class="pie"><span class="meta">${asig}</span><span class="meta">${pct(t)}%</span></div>
    </div>`;
}

// Eventos comunes de tarjetas/filas (delegación)
document.addEventListener('click', e => {
    const tomarBtn = e.target.closest('[data-tomar]');
    if (tomarBtn) { e.stopPropagation(); tomar(+tomarBtn.dataset.tomar); return; }
    if (e.target.closest('select')) return;
    const card = e.target.closest('.card[data-id], tr.fila[data-id]');
    if (card) abrir(+card.dataset.id);
});
document.addEventListener('change', e => {
    const s = e.target.closest('select[data-estado-id]');
    if (s) cambiarEstado(+s.dataset.estadoId, s.value);
});

// ── Vista A: tablero por estado ──────────────────────────────────────────────
function renderA() {
    const lista = filtradas(true);
    $('#vista-a').innerHTML = `<div class="kanban">${ESTADOS.map(e => {
        const ts = lista.filter(t => t.estado === e.k);
        return `<div class="col ${e.k} ${filtroEstado && filtroEstado !== e.k ? 'oculto' : ''}" data-estado="${e.k}">
            <div class="col-h"><span class="dot" style="background:${e.c}"></span>${e.t}<span class="n">${ts.length}</span></div>
            ${ts.map(t => tarjeta(t, { drag: true })).join('') || '<div class="vacio">Sin tareas</div>'}
        </div>`;
    }).join('')}</div>`;
    document.querySelectorAll('#vista-a .card[draggable="true"]').forEach(c => c.ondragstart = ev => ev.dataTransfer.setData('text/plain', c.dataset.id));
    document.querySelectorAll('#vista-a .col').forEach(col => {
        col.ondragover = ev => { ev.preventDefault(); col.classList.add('drop'); };
        col.ondragleave = () => col.classList.remove('drop');
        col.ondrop = ev => {
            ev.preventDefault(); col.classList.remove('drop');
            const id = +ev.dataTransfer.getData('text/plain');
            const t = tareas.find(x => x.id === id);
            if (t && t.estado !== col.dataset.estado) cambiarEstado(id, col.dataset.estado);
        };
    });
}

// ── Vista B: columnas por empleado + bolsa ───────────────────────────────────
function columnaBolsa(libres) {
    return `<div class="persona bolsa">
        <div class="persona-h"><span class="av" style="background:var(--oro);color:var(--carbon)">✋</span><div><b>Bolsa de tareas</b><small>Sin asignar · cualquiera la puede tomar</small></div><span class="carga">${libres.length}</span></div>
        ${libres.map(t => tarjeta(t)).join('') || '<div class="vacio">No hay tareas libres 🎉</div>'}
    </div>`;
}
function renderB() {
    const lista = filtradas().filter(t => filtroEstado || ACTIVOS.includes(t.estado));
    const fe = $('#fEmpleado').value;
    const cols = [];
    if (!fe || fe === '__libre') cols.push(columnaBolsa(lista.filter(t => !t.asignados.length)));
    usuarios.forEach(u => {
        if (fe && !igual(fe, u.usuario)) return;
        if (soloMias && !igual(u.usuario, YO)) return;
        const ts = lista.filter(t => tiene(t, u.usuario));
        const activas = tareas.filter(t => tiene(t, u.usuario) && ACTIVOS.includes(t.estado)).length;
        cols.push(`<div class="persona">
            <div class="persona-h">${avatar(u.usuario)}<div><b>${esc(u.usuario)}</b><small>${u.rol === 'admin' ? 'Admin' : 'Empleado'}</small></div>
              <span class="carga">${activas} activas<div class="mini-barra"><i style="width:${Math.min(100, activas * 20)}%"></i></div></span></div>
            ${ts.map(t => tarjeta(t, { conEstado: true, sinNombre: true })).join('') || '<div class="vacio">Sin tareas</div>'}
        </div>`);
    });
    $('#vista-b').innerHTML = `<p class="meta" style="margin:0 0 10px">${filtroEstado ? '' : 'Se muestran las tareas activas (pendientes, en proceso y en revisión). Usá los filtros de estado para ver finalizadas o canceladas.'}</p><div class="equipo">${cols.join('')}</div>`;
}

// ── Vista C: lista ───────────────────────────────────────────────────────────
function renderC() {
    const base = filtradas(true);
    const P = ['alta', 'media', 'baja'];
    const lista = filtradas().sort((a, b) =>
        orden === 'vence' ? (a.vence || '9999').localeCompare(b.vence || '9999')
        : orden === 'prio' ? P.indexOf(a.prioridad) - P.indexOf(b.prioridad)
        : orden === 'avance' ? pct(b) - pct(a)
        : orden === 'id' ? b.id - a.id
        : (a.asignados[0] || 'zz').localeCompare(b.asignados[0] || 'zz'));
    $('#vista-c').innerHTML = `
        <div class="kpis">${ESTADOS.map(e => `<div class="kpi ${filtroEstado === e.k ? 'on' : ''}" style="border-left-color:${e.c}" data-e="${e.k}"><b>${base.filter(t => t.estado === e.k).length}</b><span>${e.t}</span></div>`).join('')}</div>
        <div class="tabla-wrap"><table>
          <tr><th data-o="id">N° ↕</th><th>Tarea</th><th data-o="asig">Asignada a ↕</th><th>Estado</th><th data-o="prio">Prioridad ↕</th><th data-o="avance">Avance ↕</th><th data-o="vence">Vence ↕</th><th></th></tr>
          ${lista.map(t => `<tr class="fila" data-id="${t.id}">
            <td class="meta">#${t.id}</td>
            <td><div class="t">${esc(t.titulo)}</div>${t.categoria ? `<span class="cat">${esc(t.categoria)}</span>` : ''}</td>
            <td>${t.asignados.length ? `<span class="meta">${avatares(t)} ${esc(t.asignados.join(', '))}</span>` : '<span class="sinasig">Sin asignar</span>'}</td>
            <td>${selEstado(t)}</td>
            <td><span class="prio ${t.prioridad}">${t.prioridad}</span></td>
            <td><div class="barra"><i style="width:${pct(t)}%"></i></div><span class="pct">${pct(t)}%</span></td>
            <td>${venceTxt(t)}</td>
            <td>${!t.asignados.length && ACTIVOS.includes(t.estado) ? `<button class="btn btn-oro mini" data-tomar="${t.id}">✋ Tomar</button>` : ''}</td>
          </tr>`).join('') || '<tr><td colspan="8" class="vacio">No hay tareas con esos filtros</td></tr>'}
        </table></div>`;
    document.querySelectorAll('#vista-c .kpi').forEach(k => k.onclick = () => { filtroEstado = filtroEstado === k.dataset.e ? '' : k.dataset.e; render(); });
    document.querySelectorAll('#vista-c th[data-o]').forEach(th => th.onclick = () => { orden = th.dataset.o; render(); });
}

// ── Vista empleado: mis tareas + bolsa (+ las que creé para otros) ───────────
function renderEmp() {
    const lista = filtradas();
    const visible = t => filtroEstado || ACTIVOS.includes(t.estado);
    const mias = lista.filter(t => tiene(t, YO) && visible(t));
    const libres = lista.filter(t => !t.asignados.length && visible(t));
    const creadas = lista.filter(t => igual(t.creado_por, YO) && t.asignados.length && !tiene(t, YO) && visible(t));
    const activas = tareas.filter(t => tiene(t, YO) && ACTIVOS.includes(t.estado));
    const aRevisar = tareas.filter(t => igual(t.creado_por, YO) && t.estado === 'revision').length;
    $('#vista-emp').innerHTML = `
        <div class="resumen-emp">
          <div><b>${activas.length}</b>Mis tareas activas</div>
          <div><b>${activas.filter(tarde).length}</b>Vencidas</div>
          <div><b>${tareas.filter(t => !t.asignados.length && t.estado === 'pendiente').length}</b>Libres en la bolsa</div>
          ${aRevisar ? `<div><b>${aRevisar}</b>Para revisar (creadas por mí)</div>` : ''}
        </div>
        <div class="emp-layout">
          <div class="persona">
            <div class="persona-h">${avatar(YO)}<div><b>Mis tareas</b><small>Cambiá el estado a medida que avanzás</small></div><span class="carga">${mias.length}</span></div>
            ${mias.map(t => tarjeta(t, { conEstado: true })).join('') || '<div class="vacio">No tenés tareas asignadas</div>'}
          </div>
          ${columnaBolsa(libres)}
          ${creadas.length ? `<div class="persona">
            <div class="persona-h"><span class="av" style="background:#6b4fa0">✎</span><div><b>Creadas por mí</b><small>Asignadas a otros · las finalizás vos</small></div><span class="carga">${creadas.length}</span></div>
            ${creadas.map(t => tarjeta(t, { conEstado: true })).join('')}
          </div>` : ''}
        </div>
        ${!filtroEstado ? '<p class="meta" style="margin-top:10px">Se muestran las tareas activas. Usá los filtros de estado para ver finalizadas o canceladas.</p>' : ''}`;
}

const SUB = {
    a: 'Arrastrá las tarjetas entre columnas para cambiar el estado.',
    b: 'Una columna por empleado, más la bolsa de tareas libres.',
    c: 'Tocá los totales para filtrar y los títulos para ordenar.',
    emp: 'Tus tareas y la bolsa general: tocá “Tomar” para asignarte una.'
};
function render() {
    renderChipsEstado();
    const v = ADMIN ? vista : 'emp';
    ['a', 'b', 'c', 'emp'].forEach(x => { const el = $('#vista-' + x); el.classList.toggle('oculto', x !== v); if (x !== v) el.innerHTML = ''; });
    ({ a: renderA, b: renderB, c: renderC, emp: renderEmp })[v]();
    $('#subtitulo').textContent = SUB[v];
    document.querySelectorAll('#selVistas button').forEach(b => b.classList.toggle('on', b.dataset.vista === vista));
}
document.querySelectorAll('#selVistas button').forEach(b => b.onclick = () => { vista = b.dataset.vista; guardarPref('tareas_vista', vista); render(); });

// ── Detalle de tarea ─────────────────────────────────────────────────────────
function abrir(id, refrescar) {
    const t = tareas.find(x => x.id === id);
    if (!t) { cerrar(); return; }
    // Al refrescar no se pisa lo que el usuario está escribiendo
    const borrador = refrescar ? { c: $('#mComentario')?.value || '', p: $('#mPaso')?.value || '' } : { c: '', p: '' };
    abierta = id;
    const ed = puedeEditar(t), dis = ed ? '' : 'disabled';
    const opcEstado = opcionesEstado(t);
    const hist = [...t.historial].reverse();
    $('#modal').innerHTML = `
      <div class="modal-h">
        <span class="meta" style="color:#bbb">#${t.id}</span>
        <input id="mTitulo" value="${esc(t.titulo)}" ${dis} maxlength="140">
        <button class="cerrar" data-cerrar>✕</button>
      </div>
      <div class="modal-b">
        <div class="grid2">
          <div class="campo"><label>Estado</label><select id="mEstado" ${dis}>${opcEstado}</select></div>
          <div class="campo"><label>Prioridad</label><select id="mPrioridad" ${dis}>
            ${['alta', 'media', 'baja'].map(p => `<option value="${p}" ${t.prioridad === p ? 'selected' : ''}>${p[0].toUpperCase() + p.slice(1)}</option>`).join('')}</select></div>
          <div class="campo"><label>Vence</label><input type="date" id="mVence" value="${esc(t.vence)}" ${dis}></div>
          <div class="campo"><label>Categoría</label><input id="mCategoria" list="listaCategorias" value="${esc(t.categoria)}" maxlength="40" ${dis}></div>
        </div>
        <div class="campo"><span class="lbl">Asignada a ${t.asignados.length > 1 ? '· compartida' : ''}</span>
          ${ed
            ? `<div class="asig-lista">${usuarios.map(u => `<label><input type="checkbox" data-asig value="${esc(u.usuario)}" ${tiene(t, u.usuario) ? 'checked' : ''}>${avatar(u.usuario)} ${esc(u.usuario)}</label>`).join('')}</div>`
            : `<div class="asig-lista">${t.asignados.map(n => `<span class="meta">${avatar(n)} ${esc(n)}</span>`).join('') || '<span class="sinasig">Sin asignar</span>'}</div>`}
        </div>
        <div class="campo"><label>Descripción</label><textarea id="mDesc" rows="2" ${dis} placeholder="Detalle, datos del cliente, etc.">${esc(t.descripcion)}</textarea></div>
        <div class="campo"><span class="lbl">Avance · ${pct(t)}%</span>
          <div class="barra" style="margin:0 0 6px"><i style="width:${pct(t)}%"></i></div>
          ${t.pasos.map((p, i) => `<label class="paso ${p.hecho ? 'ok' : ''}"><input type="checkbox" data-paso="${i}" ${p.hecho ? 'checked' : ''} ${dis}><span>${esc(p.texto)}</span>${ed ? `<button class="quitar" data-quitar-paso="${i}" title="Quitar paso">✕</button>` : ''}</label>`).join('')
            || '<div class="vacio">Sin pasos. Agregá pasos para medir el avance.</div>'}
          ${ed ? `<div class="fila-add"><input id="mPaso" placeholder="Nuevo paso…" maxlength="120" value="${esc(borrador.p)}"><button class="btn btn-secundario mini" data-agregar-paso>+ Agregar</button></div>` : ''}
        </div>
        <div class="campo"><span class="lbl">Comentarios</span>
          ${t.comentarios.map(c => `<div class="coment"><small><b>${esc(c.usuario)}</b> · ${fechaHora(c.fecha)}</small>${esc(c.texto).replace(/\n/g, '<br>')}</div>`).join('')}
          <div class="fila-add"><textarea id="mComentario" rows="2" placeholder="Escribí un comentario…" style="flex:1">${esc(borrador.c)}</textarea><button class="btn btn-secundario mini" data-comentar>Enviar</button></div>
        </div>
        <div class="campo"><span class="lbl">Historial</span>
          <div class="hist">${hist.map(h => `<div><b>${esc(h.usuario)}</b> ${esc(h.accion)} <small>· ${fechaHora(h.fecha)}</small></div>`).join('')}</div>
        </div>
        <div class="acciones-modal">
          <div>
            ${ACTIVOS.includes(t.estado) && !tiene(t, YO) ? `<button class="btn btn-oro mini" data-tomar="${t.id}">✋ ${t.asignados.length ? 'Sumarme' : 'Tomar'}</button>` : ''}
            ${tiene(t, YO) && ACTIVOS.includes(t.estado) ? `<button class="btn btn-secundario mini" data-soltar>Dejar la tarea</button>` : ''}
          </div>
          <div>
            <span class="meta">Creada por ${esc(t.creado_por)} · ${fechaHora(t.creado)}</span>
            ${ADMIN ? `<button class="btn btn-peligro mini" data-eliminar>🗑 Eliminar</button>` : ''}
          </div>
        </div>
        ${!ed ? '<div class="nota">Para editar o marcar avances, tomá la tarea primero.</div>' : ''}
      </div>`;
    $('#overlay').classList.add('on');
    if (!ed) return;

    const editar = (campo, valor) => accion('editar', { id: t.id, [campo]: valor });
    $('#mTitulo').onchange = e => e.target.value.trim() ? editar('titulo', e.target.value) : abrir(t.id, true);
    $('#mPrioridad').onchange = e => editar('prioridad', e.target.value);
    $('#mVence').onchange = e => editar('vence', e.target.value);
    $('#mCategoria').onchange = e => editar('categoria', e.target.value);
    $('#mDesc').onchange = e => editar('descripcion', e.target.value);
    $('#mEstado').onchange = e => cambiarEstado(t.id, e.target.value);
    document.querySelectorAll('#modal [data-asig]').forEach(ch => ch.onchange = () => {
        const sel = [...document.querySelectorAll('#modal [data-asig]:checked')].map(x => x.value);
        accion('asignar', { id: t.id, asignados: sel });
    });
    document.querySelectorAll('#modal [data-paso]').forEach(ch => ch.onchange = () => accion('paso', { id: t.id, op: 'tildar', indice: +ch.dataset.paso }));
}

// Botones del modal (delegación)
$('#overlay').addEventListener('click', e => {
    if (e.target === $('#overlay') || e.target.closest('[data-cerrar]')) { cerrar(); return; }
    const id = abierta;
    if (e.target.closest('[data-quitar-paso]')) {
        e.preventDefault();
        const i = +e.target.closest('[data-quitar-paso]').dataset.quitarPaso;
        if (confirm('¿Quitar este paso?')) accion('paso', { id, op: 'quitar', indice: i });
    } else if (e.target.closest('[data-agregar-paso]')) {
        const txt = $('#mPaso').value.trim();
        if (txt) { $('#mPaso').value = ''; accion('paso', { id, op: 'agregar', texto: txt }); }
    } else if (e.target.closest('[data-comentar]')) {
        const txt = $('#mComentario').value.trim();
        if (txt) { $('#mComentario').value = ''; accion('comentar', { id, texto: txt }, '✓ Comentario agregado'); }
    } else if (e.target.closest('[data-soltar]')) {
        if (confirm('¿Dejar esta tarea? Si nadie más la tiene, vuelve a la bolsa.')) accion('soltar', { id }, 'Dejaste la tarea');
    } else if (e.target.closest('[data-eliminar]')) {
        if (confirm('¿Eliminar la tarea definitivamente? (Queda una copia en el registro de eliminadas.) Si solo no se va a hacer, conviene Cancelarla.'))
            accion('eliminar', { id }, 'Tarea eliminada');
    }
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && $('#overlay').classList.contains('on')) cerrar();
    if (e.key === 'Enter' && e.target.id === 'mPaso') { e.preventDefault(); document.querySelector('[data-agregar-paso]')?.click(); }
});
function cerrar() { abierta = null; $('#overlay').classList.remove('on'); }

// ── Nueva tarea ──────────────────────────────────────────────────────────────
$('#btnNueva').onclick = () => {
    abierta = null;
    $('#modal').innerHTML = `
      <div class="modal-h"><h3>Nueva tarea</h3><button class="cerrar" data-cerrar>✕</button></div>
      <div class="modal-b">
        <div class="campo"><label>Título *</label><input id="nTitulo" maxlength="140" placeholder="Ej: Cambiar módulo Samsung A54"></div>
        <div class="campo"><label>Descripción</label><textarea id="nDesc" rows="2" placeholder="Detalle, datos del cliente, etc."></textarea></div>
        <div class="grid2">
          <div class="campo"><label>Categoría</label><input id="nCategoria" list="listaCategorias" maxlength="40" placeholder="Ej: Servicio técnico"></div>
          <div class="campo"><label>Prioridad</label><select id="nPrioridad"><option value="media">Media</option><option value="alta">Alta</option><option value="baja">Baja</option></select></div>
          <div class="campo"><label>Vence</label><input type="date" id="nVence"></div>
        </div>
        <div class="campo"><span class="lbl">Asignar a (sin marcar = queda en la bolsa)</span>
          <div class="asig-lista">${usuarios.map(u => `<label><input type="checkbox" data-nasig value="${esc(u.usuario)}">${avatar(u.usuario)} ${esc(u.usuario)}</label>`).join('')}</div>
        </div>
        <div class="campo"><label>Pasos (opcional, uno por línea · sirven para medir el avance)</label><textarea id="nPasos" rows="3" placeholder="Diagnóstico&#10;Pedir repuesto&#10;Reparar"></textarea></div>
        <div style="text-align:right"><button class="btn btn-secundario" data-cerrar>Cancelar</button> <button class="btn btn-oro" id="nCrear">Crear tarea</button></div>
      </div>`;
    $('#overlay').classList.add('on');
    $('#nTitulo').focus();
    $('#nCrear').onclick = async () => {
        const titulo = $('#nTitulo').value.trim();
        if (!titulo) { aviso('Poné un título', true); $('#nTitulo').focus(); return; }
        $('#nCrear').disabled = true;
        const r = await accion('crear', {
            titulo,
            descripcion: $('#nDesc').value,
            categoria: $('#nCategoria').value,
            prioridad: $('#nPrioridad').value,
            vence: $('#nVence').value,
            asignados: [...document.querySelectorAll('[data-nasig]:checked')].map(x => x.value),
            pasos: $('#nPasos').value.split('\n')
        }, '✓ Tarea creada');
        if (r) { cerrar(); prepararFiltros(); render(); } else { $('#nCrear').disabled = false; }
    };
};

// Refresco automático cada minuto (para ver lo que cargan los demás)
setInterval(() => { if (!document.hidden && !$('#overlay').classList.contains('on')) cargar(); }, 60000);
cargar();
