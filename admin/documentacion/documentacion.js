// Documentación — panel MDP Soluciones
// Todos leen y confirman lectura; solo el admin sube, edita, reemplaza y elimina.

const TIPO_TXT = { pdf: 'PDF', img: 'IMG', doc: 'DOC', xls: 'XLS', vid: 'VIDEO', ppt: 'PPT' };
const COLORES = ['#2b6cb0', '#b05a8a', '#2f7d4f', '#b7791f', '#6b4fa0', '#c05621', '#2c7a7b', '#303030', '#9b2c2c'];

let docs = [], categorias = [], empleados = [], YO = '', ADMIN = false, MAX_MB = 25, TIPOS = [], DIAS_NUEVO = 7;
let cat = '', soloPend = false, abierto = null;

const $ = s => document.querySelector(s);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const fecha = f => f ? f.slice(0, 10).split('-').reverse().join('/') : '';
const fechaCorta = f => f ? fecha(f).slice(0, 5) : '';
const tamano = b => b > 1048576 ? (b / 1048576).toFixed(1).replace('.', ',') + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB';
const reciente = f => f && (Date.now() - new Date(f.replace(' ', 'T')).getTime()) < DIAS_NUEVO * 86400000;
const pendiente = d => d.confirmar && !d.mi_lectura && !ADMIN;
const catDe = id => categorias.find(c => c.id === id) || { id, nombre: 'Sin categoría', icono: '📁' };
const lecturas = d => d.lecturas && !Array.isArray(d.lecturas) ? d.lecturas : {};
const leyo = (d, n) => Object.keys(lecturas(d)).find(u => u.toLowerCase() === n.toLowerCase());

function color(n) { let h = 0; for (const ch of String(n).toLowerCase()) h = (h * 31 + ch.charCodeAt(0)) >>> 0; return COLORES[h % COLORES.length]; }
const av = n => `<span class="av" style="background:${color(n)}">${esc(String(n).slice(0, 2).toUpperCase())}</span>`;

function aviso(msg, error) {
    const el = $('#toast');
    el.textContent = msg;
    el.className = 'toast on' + (error ? ' error' : '');
    clearTimeout(aviso.t);
    aviso.t = setTimeout(() => el.className = 'toast', error ? 4500 : 2200);
}

// ── API ──────────────────────────────────────────────────────────────────────
async function api(accion, datos) {
    const opts = datos ? { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ...datos, csrf: DOC_CFG.csrf }) } : {};
    const res = await fetch(`api.php?accion=${accion}`, opts);
    if (res.status === 401) { location.href = '../login.php'; throw new Error('Sesión vencida'); }
    let r; try { r = await res.json(); } catch { throw new Error('Respuesta inválida del servidor'); }
    if (r.status !== 'ok') throw new Error(r.mensaje || 'Error');
    return r;
}

// Subida con barra de progreso (XMLHttpRequest permite medir el avance)
function subirArchivo(accion, form, barra) {
    return new Promise((ok, mal) => {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', `api.php?accion=${accion}`);
        xhr.upload.onprogress = e => { if (e.lengthComputable && barra) barra.style.width = (100 * e.loaded / e.total) + '%'; };
        xhr.onload = () => {
            let r; try { r = JSON.parse(xhr.responseText); } catch { return mal(new Error(xhr.status === 413 ? 'El archivo es demasiado grande para el servidor' : 'Respuesta inválida del servidor')); }
            r.status === 'ok' ? ok(r) : mal(new Error(r.mensaje || 'Error'));
        };
        xhr.onerror = () => mal(new Error('Se cortó la conexión'));
        form.append('csrf', DOC_CFG.csrf);
        xhr.send(form);
    });
}

async function cargar() {
    try {
        const r = await api('listar');
        docs = r.docs; categorias = r.categorias; empleados = r.empleados; YO = r.yo; ADMIN = r.admin;
        MAX_MB = r.max_mb; TIPOS = r.tipos; DIAS_NUEVO = r.dias_nuevo;
        if (cat && !categorias.some(c => c.id === cat)) cat = '';
        render();
        actualizarBadgeMenu();
        if (abierto) docs.some(d => d.id === abierto) ? abrir(abierto) : cerrar();
    } catch (e) {
        $('#sub').textContent = 'No se pudo cargar la documentación: ' + e.message;
    }
}

// Contador rojo en el menú lateral del panel (vive en la página que contiene este iframe)
function actualizarBadgeMenu() {
    try {
        const link = window.parent.document.querySelector('[data-tool="documentacion"]');
        if (!link) return;
        let b = link.querySelector('.nav-badge');
        const n = docs.filter(pendiente).length;
        if (!n) { b?.remove(); return; }
        if (!b) { b = window.parent.document.createElement('span'); b.className = 'nav-badge'; b.style.cssText = 'background:#a33;color:#fff;border-radius:99px;font-size:10px;font-weight:700;padding:1px 7px;margin-left:auto'; link.appendChild(b); }
        b.textContent = n;
    } catch { /* fuera del panel */ }
}

// ── Listado ──────────────────────────────────────────────────────────────────
function renderCats() {
    const n = id => docs.filter(d => !id || d.categoria === id).length;
    const item = (id, icono, nombre, editable) => `
        <div class="cat-item ${cat === id ? 'on' : ''}">
          <button class="sel" data-cat="${esc(id)}">${icono} ${esc(nombre)} <span class="n">${n(id)}</span></button>
          ${editable && ADMIN ? `<span class="acc"><button data-edit-cat="${esc(id)}" title="Renombrar">✏</button><button data-del-cat="${esc(id)}" title="Eliminar">🗑</button></span>` : ''}
        </div>`;
    $('#cats').innerHTML = item('', '📚', 'Todos', false) + '<hr>' +
        categorias.map(c => item(c.id, esc(c.icono), c.nombre, true)).join('') +
        (ADMIN ? '<button class="btn-nueva-cat" data-nueva-cat>+ Nueva categoría</button>' : '');
}

function tarjeta(d) {
    const leidos = empleados.filter(n => leyo(d, n)).length;
    let tags = '';
    if (reciente(d.reemplazado)) tags += '<span class="tag t-act">ACTUALIZADO</span>';
    else if (reciente(d.creado)) tags += '<span class="tag t-nuevo">NUEVO</span>';
    if (!ADMIN && d.confirmar) tags += d.mi_lectura ? '<span class="tag t-leido">✓ Leído</span>' : '<span class="tag t-leer">Pendiente de leer</span>';
    return `<div class="doc" data-abrir="${d.id}" role="button" tabindex="0">
        <div class="icono i-${d.archivo.tipo}">${TIPO_TXT[d.archivo.tipo] || esc(d.archivo.ext)}</div>
        <div style="flex:1;min-width:0">
          <h4>${esc(d.titulo)}</h4>
          ${d.descripcion ? `<p>${esc(d.descripcion)}</p>` : ''}
          <div class="meta">${tags}<span>${fecha(d.actualizado || d.creado)}</span><span>· ${tamano(d.archivo.tamano)}</span></div>
          ${ADMIN ? (d.confirmar
            ? `<div class="lect"><span>Leído ${leidos}/${empleados.length}</span><span class="barra"><i style="width:${empleados.length ? 100 * leidos / empleados.length : 0}%"></i></span></div>`
            : '<div class="lect">Sin confirmación de lectura</div>') : ''}
        </div>
      </div>`;
}

function render() {
    $('#btnSubir').classList.toggle('oculto', !ADMIN);
    $('#fPend').classList.toggle('oculto', ADMIN);
    $('#sub').textContent = ADMIN
        ? 'Subí manuales (PDF, imágenes, Word, Excel, PowerPoint o video) y seguí quién los leyó.'
        : 'Manuales e instructivos del equipo. Los marcados en rojo hay que leerlos y confirmar.';

    const p = docs.filter(pendiente);
    $('#pendientes').innerHTML = p.length
        ? `<div class="pendientes"><b>📌 Tenés ${p.length} documento${p.length > 1 ? 's' : ''} para leer y confirmar</b><div class="lista">${p.map(d => `<button data-abrir="${d.id}">${esc(d.titulo)}</button>`).join('')}</div></div>`
        : '';

    renderCats();
    const q = $('#buscar').value.toLowerCase().trim();
    const lista = docs
        .filter(d => (!cat || d.categoria === cat) &&
            (!q || `${d.titulo} ${d.descripcion} ${d.archivo.original} ${catDe(d.categoria).nombre}`.toLowerCase().includes(q)) &&
            (!soloPend || pendiente(d)))
        .sort((a, b) => $('#orden').value === 'titulo'
            ? a.titulo.localeCompare(b.titulo)
            : (b.actualizado || b.creado).localeCompare(a.actualizado || a.creado));

    if (!docs.length) {
        $('#lista').innerHTML = `<div class="vacio">${ADMIN ? 'Todavía no hay documentos. Tocá <b>“⬆ Subir documento”</b> para cargar el primero.' : 'Todavía no hay documentos cargados.'}</div>`;
        return;
    }
    if (!lista.length) { $('#lista').innerHTML = '<div class="vacio">No hay documentos con ese filtro.</div>'; return; }
    const grupos = cat ? [catDe(cat)] : [...categorias, ...[...new Set(lista.map(d => d.categoria))].filter(id => !categorias.some(c => c.id === id)).map(catDe)];
    $('#lista').innerHTML = grupos.map(c => {
        const ds = lista.filter(d => d.categoria === c.id);
        return ds.length ? `<div class="grupo"><h3>${esc(c.icono)} ${esc(c.nombre)}</h3><div class="docs">${ds.map(tarjeta).join('')}</div></div>` : '';
    }).join('');
}

// ── Visor ────────────────────────────────────────────────────────────────────
function vista(d) {
    const url = `ver.php?id=${d.id}&t=${encodeURIComponent(d.actualizado || '')}`;
    if (d.archivo.tipo === 'pdf') return `<iframe src="${url}#view=FitH" title="${esc(d.titulo)}"></iframe>`;
    if (d.archivo.tipo === 'img') return `<img src="${url}" alt="${esc(d.titulo)}">`;
    if (d.archivo.tipo === 'vid') return `<video src="${url}" controls preload="metadata"></video>`;
    return `<div class="sin-visor"><div class="icono i-${d.archivo.tipo}" style="width:70px;height:86px;font-size:13px">${TIPO_TXT[d.archivo.tipo] || ''}</div>
        <b>Este tipo de archivo no se puede ver en el navegador</b><span class="meta">Descargalo para abrirlo con ${d.archivo.tipo === 'xls' ? 'Excel' : d.archivo.tipo === 'ppt' ? 'PowerPoint' : 'Word'}.</span>
        <a class="btn btn-oro" href="ver.php?id=${d.id}&descargar=1">⬇ Descargar</a></div>`;
}

function abrir(id) {
    const d = docs.find(x => x.id === id);
    if (!d) return;
    const mismo = abierto === id && $('#overlay').classList.contains('on');
    abierto = id;
    const c = catDe(d.categoria);
    const leidos = empleados.filter(n => leyo(d, n)), faltan = empleados.filter(n => !leyo(d, n));
    const info = `
        ${!ADMIN && d.confirmar ? (d.mi_lectura
            ? `<div class="leido-ok">✓ Confirmaste la lectura el ${fecha(d.mi_lectura)}</div>`
            : `<div class="caja" style="border:2px solid var(--mal)"><h5>Confirmación de lectura</h5><p>Leé el documento completo y confirmá.</p>
                 <button class="btn btn-verde" style="width:100%;margin-top:6px" data-confirmar>✓ Lo leí y lo entendí</button></div>`) : ''}
        <div class="caja"><h5>Detalle</h5>
          <p>${esc(c.icono)} ${esc(c.nombre)}</p>
          <p>${TIPO_TXT[d.archivo.tipo] || ''} · ${tamano(d.archivo.tamano)}</p>
          <p>Subido el ${fecha(d.creado)} por ${esc(d.subido_por)}</p>
          ${d.reemplazado ? `<p>Archivo actualizado el ${fecha(d.reemplazado)}</p>` : ''}
          ${d.descripcion ? `<p style="color:var(--gris);font-size:12.5px">${esc(d.descripcion)}</p>` : ''}
        </div>
        ${ADMIN && d.confirmar ? `<div class="caja"><h5>Lectura · ${leidos.length} de ${empleados.length}</h5>
          ${leidos.map(n => `<div class="persona">${av(n)} ${esc(n)}<small>✓ ${fechaCorta(lecturas(d)[leyo(d, n)])}</small></div>`).join('')}
          ${faltan.map(n => `<div class="persona" style="opacity:.75">${av(n)} ${esc(n)}<small style="color:var(--mal)">Sin leer</small></div>`).join('')}
          ${!empleados.length ? '<p class="meta">No hay empleados cargados.</p>' : ''}
          ${faltan.length ? `<button class="btn btn-secundario mini" style="margin-top:8px" data-recordatorio>📋 Copiar recordatorio para WhatsApp</button>` : ''}
        </div>` : ''}
        ${ADMIN ? `<div class="caja"><h5>Administrar</h5><div class="acciones-admin">
          <button class="btn btn-secundario mini" data-editar>✏ Editar datos</button>
          <button class="btn btn-secundario mini" data-reemplazar>⬆ Reemplazar archivo</button>
          <button class="btn btn-peligro mini" data-eliminar>🗑 Eliminar</button></div>
          ${d.cant_versiones ? `<p class="meta" style="margin-top:8px">${d.cant_versiones} versión(es) anterior(es) guardada(s) en el servidor.</p>` : ''}
        </div>` : ''}`;
    if (mismo) { $('#infoDoc').innerHTML = info; return; }   // refresco sin recargar el visor
    $('#modal').className = 'modal';
    $('#modal').innerHTML = `
      <div class="modal-h"><h3>${esc(d.titulo)}</h3>
        <a href="ver.php?id=${d.id}&descargar=1">⬇ Descargar</a>
        ${d.archivo.ver_en_navegador ? `<a href="ver.php?id=${d.id}" target="_blank" rel="noopener">↗ Pantalla completa</a>` : ''}
        <button data-cerrar>✕</button></div>
      <div class="modal-b"><div class="visor"><div class="hoja">${vista(d)}</div><div class="info" id="infoDoc">${info}</div></div></div>`;
    $('#overlay').classList.add('on');
}

function cerrar() {
    abierto = null;
    $('#overlay').classList.remove('on');
    $('#modal').innerHTML = '';   // corta videos en reproducción
}

async function confirmarLectura() {
    const d = docs.find(x => x.id === abierto);
    try {
        const r = await api('confirmar', { id: d.id });
        d.mi_lectura = r.fecha;
        render(); actualizarBadgeMenu(); abrir(d.id);
        aviso('✓ Lectura confirmada');
    } catch (e) { aviso(e.message, true); }
}

async function copiarRecordatorio() {
    const d = docs.find(x => x.id === abierto);
    const faltan = empleados.filter(n => !leyo(d, n));
    const txt = `📌 *Recordatorio*\nTienen pendiente leer y confirmar en el panel → *Documentación*:\n\n📄 *${d.titulo}*\n\nFalta: ${faltan.join(', ')}\n\nCuando lo lean, toquen *“Lo leí y lo entendí”* ✅`;
    try { await navigator.clipboard.writeText(txt); aviso('✓ Recordatorio copiado. Pegalo en WhatsApp'); }
    catch { prompt('Copiá el recordatorio:', txt); }
}

// ── Formularios (admin) ──────────────────────────────────────────────────────
const opcionesCat = sel => categorias.map(c => `<option value="${esc(c.id)}" ${c.id === sel ? 'selected' : ''}>${esc(c.icono)} ${esc(c.nombre)}</option>`).join('');

function formSubida(d) {
    // d = null → documento nuevo · d = documento → reemplazar archivo
    const nuevo = !d;
    $('#modal').className = 'modal chico';
    $('#modal').innerHTML = `
      <div class="modal-h"><h3>${nuevo ? 'Subir documento' : 'Reemplazar archivo · ' + esc(d.titulo)}</h3><button data-cerrar>✕</button></div>
      <div class="modal-b">
        <label class="drop" id="drop">
          <input type="file" id="fArchivo" hidden accept="${TIPOS.map(t => '.' + t).join(',')}">
          <b>Arrastrá el archivo acá o hacé clic para elegirlo</b>
          PDF, imágenes, Word, Excel, PowerPoint o video MP4 · máximo ${MAX_MB} MB
        </label>
        ${nuevo ? `
        <div class="campo" style="margin-top:12px"><label>Título *</label><input id="fTitulo" maxlength="150" placeholder="Ej: Cómo cargar un producto usado"></div>
        <div class="campo"><label>Categoría *</label><select id="fCat">${opcionesCat(cat || categorias[0]?.id)}</select></div>
        <div class="campo"><label>Descripción corta</label><textarea id="fDesc" rows="2" maxlength="400" placeholder="Qué explica este documento"></textarea></div>
        <label class="check"><input type="checkbox" id="fConfirmar" checked><span>Pedir confirmación de lectura<small>Los empleados lo ven en rojo hasta que tocan “Lo leí”. Vos ves quién lo leyó.</small></span></label>`
        : `<label class="check" style="margin-top:12px"><input type="checkbox" id="fReconfirmar" ${d.confirmar ? 'checked' : ''}><span>Pedir que todos lo vuelvan a confirmar<small>Se borran las confirmaciones anteriores. La versión vieja del archivo queda guardada.</small></span></label>`}
        <div class="progreso oculto" id="prog"><i id="progBarra"></i></div>
        <div class="pie-modal"><button class="btn btn-secundario" data-cerrar>Cancelar</button><button class="btn btn-oro" id="fEnviar">${nuevo ? 'Subir' : 'Reemplazar'}</button></div>
      </div>`;
    $('#overlay').classList.add('on');

    let archivo = null;
    const drop = $('#drop'), input = $('#fArchivo');
    const elegir = f => {
        if (!f) return;
        const ext = f.name.split('.').pop().toLowerCase();
        if (!TIPOS.includes(ext)) { aviso('Tipo de archivo no permitido', true); return; }
        if (f.size > MAX_MB * 1048576) { aviso(`El archivo pesa más de ${MAX_MB} MB`, true); return; }
        archivo = f;
        drop.classList.add('cargado');
        drop.innerHTML = `<div class="icono i-${tipoDeExt(ext)}">${esc(ext)}</div><div><b style="margin:0">${esc(f.name)}</b><span class="meta">${tamano(f.size)} · listo para subir · <u>cambiar</u></span></div>`;
        drop.appendChild(input);
        if (nuevo && !$('#fTitulo').value) $('#fTitulo').value = f.name.replace(/\.[^.]+$/, '').replace(/[_-]+/g, ' ');
    };
    input.onchange = () => elegir(input.files[0]);
    drop.ondragover = e => { e.preventDefault(); drop.classList.add('encima'); };
    drop.ondragleave = () => drop.classList.remove('encima');
    drop.ondrop = e => { e.preventDefault(); drop.classList.remove('encima'); elegir(e.dataTransfer.files[0]); };

    $('#fEnviar').onclick = async () => {
        if (!archivo) { aviso('Elegí un archivo', true); return; }
        const form = new FormData();
        form.append('archivo', archivo);
        if (nuevo) {
            const titulo = $('#fTitulo').value.trim();
            if (!titulo) { aviso('Poné un título', true); $('#fTitulo').focus(); return; }
            form.append('titulo', titulo);
            form.append('categoria', $('#fCat').value);
            form.append('descripcion', $('#fDesc').value);
            form.append('confirmar', $('#fConfirmar').checked ? '1' : '');
        } else {
            form.append('id', d.id);
            form.append('reconfirmar', $('#fReconfirmar').checked ? '1' : '');
        }
        $('#fEnviar').disabled = true; $('#prog').classList.remove('oculto');
        try {
            await subirArchivo(nuevo ? 'subir' : 'reemplazar', form, $('#progBarra'));
            aviso(nuevo ? '✓ Documento subido' : '✓ Archivo reemplazado');
            cerrar(); await cargar();
        } catch (e) {
            aviso(e.message, true);
            $('#fEnviar').disabled = false; $('#prog').classList.add('oculto');
        }
    };
}
const tipoDeExt = ext => ({ pdf: 'pdf', jpg: 'img', jpeg: 'img', png: 'img', webp: 'img', gif: 'img', mp4: 'vid', doc: 'doc', docx: 'doc', xls: 'xls', xlsx: 'xls', ppt: 'ppt', pptx: 'ppt' }[ext] || 'doc');

function formEditar(d) {
    $('#modal').className = 'modal chico';
    $('#modal').innerHTML = `
      <div class="modal-h"><h3>Editar documento</h3><button data-cerrar>✕</button></div>
      <div class="modal-b">
        <div class="campo"><label>Título *</label><input id="eTitulo" maxlength="150" value="${esc(d.titulo)}"></div>
        <div class="campo"><label>Categoría</label><select id="eCat">${opcionesCat(d.categoria)}</select></div>
        <div class="campo"><label>Descripción corta</label><textarea id="eDesc" rows="2" maxlength="400">${esc(d.descripcion)}</textarea></div>
        <label class="check"><input type="checkbox" id="eConfirmar" ${d.confirmar ? 'checked' : ''}><span>Pedir confirmación de lectura</span></label>
        <div class="pie-modal"><button class="btn btn-secundario" data-volver>Cancelar</button><button class="btn btn-oro" id="eGuardar">Guardar</button></div>
      </div>`;
    $('#overlay').classList.add('on');
    $('#eGuardar').onclick = async () => {
        try {
            await api('editar', { id: d.id, titulo: $('#eTitulo').value, categoria: $('#eCat').value, descripcion: $('#eDesc').value, confirmar: $('#eConfirmar').checked });
            aviso('✓ Cambios guardados');
            const id = d.id; abierto = null; await cargar(); abrir(id);
        } catch (e) { aviso(e.message, true); }
    };
}

function formCategoria(c) {
    abierto = null;
    $('#modal').className = 'modal chico';
    $('#modal').innerHTML = `
      <div class="modal-h"><h3>${c ? 'Renombrar categoría' : 'Nueva categoría'}</h3><button data-cerrar>✕</button></div>
      <div class="modal-b">
        <div class="grid-icono">
          <div class="campo"><label>Ícono</label><input id="cIcono" maxlength="4" value="${esc(c ? c.icono : '📁')}" style="text-align:center;font-size:18px"></div>
          <div class="campo"><label>Nombre *</label><input id="cNombre" maxlength="40" value="${esc(c ? c.nombre : '')}" placeholder="Ej: Servicio técnico"></div>
        </div>
        <p class="meta">Podés pegar un emoji como ícono (por ejemplo 🔧 🏪 🏷️ 📦).</p>
        <div class="pie-modal"><button class="btn btn-secundario" data-cerrar>Cancelar</button><button class="btn btn-oro" id="cGuardar">Guardar</button></div>
      </div>`;
    $('#overlay').classList.add('on');
    $('#cNombre').focus();
    $('#cGuardar').onclick = async () => {
        try {
            const r = await api('categoria_guardar', { id: c ? c.id : '', nombre: $('#cNombre').value, icono: $('#cIcono').value });
            aviso('✓ Categoría guardada');
            cerrar(); cat = r.categoria.id; await cargar();
        } catch (e) { aviso(e.message, true); }
    };
}

// ── Eventos ──────────────────────────────────────────────────────────────────
document.addEventListener('click', async e => {
    const t = e.target;
    if (t.closest('[data-abrir]')) { abrir(+t.closest('[data-abrir]').dataset.abrir); return; }
    if (t.closest('[data-cat]')) { cat = t.closest('[data-cat]').dataset.cat; render(); return; }
    if (t.closest('[data-nueva-cat]')) { formCategoria(null); return; }
    if (t.closest('[data-edit-cat]')) { formCategoria(catDe(t.closest('[data-edit-cat]').dataset.editCat)); return; }
    if (t.closest('[data-del-cat]')) {
        const c = catDe(t.closest('[data-del-cat]').dataset.delCat);
        if (!confirm(`¿Eliminar la categoría "${c.nombre}"?`)) return;
        try { await api('categoria_eliminar', { id: c.id }); if (cat === c.id) cat = ''; aviso('Categoría eliminada'); cargar(); }
        catch (err) { aviso(err.message, true); }
        return;
    }
    if (t === $('#overlay') || t.closest('[data-cerrar]')) { cerrar(); return; }
    if (t.closest('[data-volver]')) { const id = abierto; abierto = null; abrir(id); return; }
    if (t.closest('[data-confirmar]')) { confirmarLectura(); return; }
    if (t.closest('[data-recordatorio]')) { copiarRecordatorio(); return; }
    const d = docs.find(x => x.id === abierto);
    if (!d) return;
    if (t.closest('[data-editar]')) formEditar(d);
    else if (t.closest('[data-reemplazar]')) formSubida(d);
    else if (t.closest('[data-eliminar]')) {
        if (!confirm(`¿Eliminar "${d.titulo}"? El archivo se mueve a una carpeta de eliminados en el servidor.`)) return;
        try { await api('eliminar', { id: d.id }); aviso('Documento eliminado'); cerrar(); cargar(); }
        catch (err) { aviso(err.message, true); }
    }
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && $('#overlay').classList.contains('on')) cerrar();
    if (e.key === 'Enter' && e.target.matches('.doc[data-abrir]')) abrir(+e.target.dataset.abrir);
});
$('#btnSubir').onclick = () => { abierto = null; formSubida(null); };
$('#buscar').oninput = render;
$('#orden').onchange = render;
$('#fPend').onclick = () => { soloPend = !soloPend; $('#fPend').classList.toggle('on', soloPend); render(); };

// Refresco cada 2 minutos (si no hay nada abierto)
setInterval(() => { if (!document.hidden && !$('#overlay').classList.contains('on')) cargar(); }, 120000);
cargar();
