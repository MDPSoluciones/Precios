// Catálogo de Productos — pantalla del panel (alta, edición, baja, precios y fotos).
(function () {
    'use strict';

    const CFG = window.CATALOGO;          // { csrf, raiz }
    const API = 'api.php';

    // Google Sheet anterior (solo se usa para el botón "Importar desde Google Sheet").
    const SHEET_ID = '1W7aJMPe00ORHGjVnRzScIg6KVnjTQvddm63SLHrsAJM';
    const SHEET_KEY = 'AIzaSyCdutMi4aKT3vJHaOabTtKUERoYv1-UBmM';
    const SHEET_RANGE = 'Form';

    const TIPOS_SUGERIDOS = ['Celulares', 'Notebooks', 'PC', 'Tablets', 'Auriculares', 'Cargadores',
        'Parlantes', 'Smartwatch', 'Periféricos', 'Gaming', 'Otros'];

    let productos = [];
    let config = { modo: 'auto', cotizacion: 0, ajuste: 20, pct_transf: 0, dolar_venta: null, dolar_consultado: null };
    let cfgEditando = false;    // true mientras el usuario cambia la configuración sin guardar
    let condiciones = [];
    let imagenes = null;        // se carga la primera vez que se abre la galería
    let imagenesFiltradas = [];

    const $ = id => document.getElementById(id);
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c =>
        ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const fmt = n => Math.round(Number(n) || 0).toLocaleString('es-AR');
    const fotoSrc = ruta => CFG.raiz + (ruta || 'images/default.png');

    // ---------------------------------------------------------------
    // Comunicación con api.php
    // ---------------------------------------------------------------
    async function api(accion, cuerpo) {
        const opciones = { credentials: 'same-origin' };
        if (cuerpo !== undefined) {
            opciones.method = 'POST';
            opciones.headers = { 'X-CSRF-Token': CFG.csrf };
            if (cuerpo instanceof FormData) {
                opciones.body = cuerpo;
            } else {
                opciones.headers['Content-Type'] = 'application/json';
                opciones.body = JSON.stringify(cuerpo);
            }
        }
        let res, data;
        try {
            res = await fetch(API + '?accion=' + encodeURIComponent(accion), opciones);
            data = await res.json();
        } catch (e) {
            throw new Error('No se pudo conectar con el servidor.');
        }
        if (res.status === 401) throw new Error('La sesión venció. Volvé a iniciar sesión en el panel.');
        if (!res.ok || data.ok === false) throw new Error(data.error || 'No se pudo completar la operación.');
        return data;
    }

    function aplicar(data) {
        if (data.productos) productos = data.productos;
        if (data.config) config = data.config;
        if (data.condiciones) condiciones = data.condiciones;
        render();
    }

    let toastTimer = null;
    function toast(mensaje, esError) {
        const t = $('toast');
        t.textContent = mensaje;
        t.classList.toggle('error', !!esError);
        t.classList.add('visible');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => t.classList.remove('visible'), esError ? 6000 : 3000);
    }

    // ---------------------------------------------------------------
    // Lista
    // ---------------------------------------------------------------
    function filtrados() {
        const q = $('buscar').value.trim().toLowerCase();
        const cond = $('filtroCondicion').value;
        const est = $('filtroEstado').value;
        return productos
            .filter(p => !cond || p.condicion === cond)
            .filter(p => !est || (est === 'si') === !!p.disponible)
            .filter(p => !q || `${p.producto} ${p.descripcion} ${p.tipo}`.toLowerCase().includes(q))
            .sort((a, b) =>
                condiciones.indexOf(a.condicion) - condiciones.indexOf(b.condicion) ||
                a.producto.localeCompare(b.producto) ||
                a.descripcion.localeCompare(b.descripcion));
    }

    // Muestra el campo que corresponde según el modo elegido en el selector.
    function syncModo() {
        const auto = $('cfgModo').value === 'auto';
        $('campoAjuste').hidden = !auto;
        $('campoCotizacion').hidden = auto;
        $('cfgCotizacion').required = !auto;
        $('cfgAjuste').required = auto;
    }

    function renderConfig() {
        // No pisar lo que el usuario esté cambiando sin haber guardado.
        if (!cfgEditando) {
            $('cfgModo').value = config.modo;
            $('cfgAjuste').value = config.ajuste;
            $('cfgCotizacion').value = config.cotizacion;
            $('cfgPct').value = config.pct_transf;
            syncModo();
        }
        const auto = config.modo === 'auto';
        $('dolarValor').textContent = '$' + fmt(config.cotizacion);
        $('btnDolar').hidden = !auto;
        if (!auto) {
            $('dolarInfo').textContent = '· cargado a mano';
        } else if (config.dolar_venta) {
            const cuando = config.dolar_consultado
                ? new Date(config.dolar_consultado).toLocaleString('es-AR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false })
                : '';
            const signo = config.ajuste < 0 ? '−' : '+';
            $('dolarInfo').textContent = `· blue venta $${fmt(config.dolar_venta)} ${signo} $${fmt(Math.abs(config.ajuste))}` +
                (cuando ? ` · consultado ${cuando}` : '');
        } else {
            $('dolarInfo').textContent = '· todavía no se pudo consultar el dólar blue; se usa este valor mientras tanto';
        }
        $('cfgNota').textContent =
            `Efectivo = USD × ${fmt(config.cotizacion)}. Transferencia = Efectivo + ${config.pct_transf} %. ` +
            (auto ? 'El dólar se vuelve a consultar solo cada 30 minutos (fuente: DolarAPI.com). ' : '') +
            'Los productos con precio fijo no cambian con la cotización.';
    }

    function render() {
        renderConfig();

        // Filtro de categorías y listas del formulario
        const selCond = $('filtroCondicion');
        if (selCond.options.length <= 1) {
            condiciones.forEach(c => selCond.add(new Option(c, c)));
            condiciones.forEach(c => $('fCondicion').add(new Option(c, c)));
        }
        const tipos = [...new Set(TIPOS_SUGERIDOS.concat(productos.map(p => p.tipo)))].sort((a, b) => a.localeCompare(b));
        $('listaTipos').innerHTML = tipos.map(t => `<option value="${esc(t)}">`).join('');

        const disp = productos.filter(p => p.disponible).length;
        $('resumen').textContent = productos.length
            ? `${productos.length} productos: ${disp} disponibles (se ven en el sitio) y ${productos.length - disp} no disponibles.`
            : '';

        const lista = filtrados();
        $('tbody').innerHTML = lista.map(p => `
            <tr class="${p.disponible ? '' : 'no-disponible'}" data-id="${esc(p.id)}">
                <td class="col-foto"><img class="cat-thumb" src="${esc(fotoSrc(p.imagen))}" alt="" loading="lazy"></td>
                <td class="col-nombre">
                    <div class="cat-nombre">${esc(p.producto)}</div>
                    <div class="cat-desc">${esc(p.descripcion)}</div>
                </td>
                <td class="col-cat">
                    <div class="cat-cond">${esc(p.condicion)}</div>
                    <div class="cat-tipo">${esc(p.tipo)}</div>
                </td>
                <td class="num" data-label="USD">${p.precioUSD > 0 ? '$' + fmt(p.precioUSD) : '—'}</td>
                <td class="num" data-label="Efectivo">$${fmt(p.precioPesos)}${p.precioFijo > 0 ? '<span class="cat-fijo">Fijo</span>' : ''}</td>
                <td class="num" data-label="Transferencia">$${fmt(p.precioTransf)}</td>
                <td class="col-estado">
                    <button type="button" class="cat-switch ${p.disponible ? 'on' : ''}" data-accion="estado"
                        title="${p.disponible ? 'Disponible: se muestra en el sitio' : 'No disponible: oculto en el sitio'}"
                        aria-label="Cambiar disponibilidad"></button>
                </td>
                <td class="col-acciones">
                    <button type="button" class="cat-link" data-accion="editar">Editar</button>
                    <button type="button" class="cat-link" data-accion="duplicar">Duplicar</button>
                    <button type="button" class="cat-link peligro" data-accion="eliminar">Eliminar</button>
                </td>
            </tr>`).join('');

        const vacio = $('vacio');
        vacio.hidden = lista.length > 0;
        vacio.textContent = productos.length
            ? 'No hay productos que coincidan con la búsqueda.'
            : 'Todavía no hay productos. Cargá el primero con "+ Nuevo producto" o traé los del formulario anterior con "Importar desde Google Sheet".';
    }

    // Si una foto no existe, mostrar la imagen por defecto (sin onerror en el HTML).
    document.addEventListener('error', e => {
        const img = e.target;
        if (img.tagName === 'IMG' && !img.dataset.fallo) {
            img.dataset.fallo = '1';
            img.src = CFG.raiz + 'images/default.png';
        }
    }, true);

    $('tbody').addEventListener('click', async e => {
        const boton = e.target.closest('[data-accion]');
        if (!boton) return;
        const id = boton.closest('tr').dataset.id;
        const p = productos.find(x => x.id === id);
        if (!p) return;

        try {
            switch (boton.dataset.accion) {
                case 'editar':
                    abrirFormulario(p);
                    break;
                case 'duplicar':
                    abrirFormulario({ ...p, id: '' }, 'Duplicar producto');
                    break;
                case 'estado':
                    boton.disabled = true;
                    aplicar(await api('estado', { id, disponible: !p.disponible }));
                    toast(p.disponible ? 'Marcado como no disponible: ya no se ve en el sitio.' : 'Marcado como disponible: ya se ve en el sitio.');
                    break;
                case 'eliminar':
                    if (!confirm(`¿Eliminar "${p.producto} ${p.descripcion}"?\n\nSe borra del catálogo. Si solo querés ocultarlo del sitio, usá el interruptor "Disponible".`)) return;
                    aplicar(await api('eliminar', { id }));
                    toast('Producto eliminado.');
                    break;
            }
        } catch (err) {
            boton.disabled = false;
            toast(err.message, true);
        }
    });

    ['buscar', 'filtroCondicion', 'filtroEstado'].forEach(id => $(id).addEventListener('input', render));

    // ---------------------------------------------------------------
    // Cotización del dólar (automática o manual)
    // ---------------------------------------------------------------
    // Avisa qué pasó con la consulta del dólar (estado que devuelve api.php).
    function avisoDolar(estado, mensajeOk) {
        if (estado === 'error') {
            toast(`No se pudo consultar el dólar blue. Se sigue usando el último valor ($${fmt(config.cotizacion)}).`, true);
        } else if (estado === 'cambio') {
            toast(`Dólar actualizado a $${fmt(config.cotizacion)}. Los precios del sitio ya cambiaron.`);
        } else if (mensajeOk) {
            toast(mensajeOk);
        }
    }

    $('formConfig').addEventListener('input', () => { cfgEditando = true; });
    $('cfgModo').addEventListener('change', syncModo);

    $('formConfig').addEventListener('submit', async e => {
        e.preventDefault();
        const modo = $('cfgModo').value;
        const datos = {
            modo,
            pct_transf: parseFloat($('cfgPct').value) || 0,
            ajuste: parseFloat($('cfgAjuste').value) || 0,
            cotizacion: parseFloat($('cfgCotizacion').value) || 0
        };
        const automaticos = productos.filter(p => !(p.precioFijo > 0)).length;
        const detalle = modo === 'auto'
            ? `dólar blue ${datos.ajuste < 0 ? '−' : '+'} $${fmt(Math.abs(datos.ajuste))} (automático)`
            : `dólar a $${fmt(datos.cotizacion)} (manual)`;
        if (!confirm(`Se van a recalcular los precios en pesos de ${automaticos} productos con ${detalle} y ${datos.pct_transf} % de transferencia.\n\nEl cambio se ve en el sitio al instante. ¿Continuar?`)) return;
        try {
            const data = await api('config', datos);
            cfgEditando = false;
            aplicar(data);
            avisoDolar(data.dolar === 'cambio' ? 'ok' : data.dolar, 'Guardado. Precios actualizados en el sitio.');
        } catch (err) {
            toast(err.message, true);
        }
    });

    $('btnDolar').addEventListener('click', async () => {
        const boton = $('btnDolar');
        boton.disabled = true;
        try {
            const data = await api('dolar', {});
            aplicar(data);
            avisoDolar(data.dolar, `El dólar no cambió: sigue en $${fmt(config.cotizacion)}.`);
        } catch (err) {
            toast(err.message, true);
        }
        boton.disabled = false;
    });

    // ---------------------------------------------------------------
    // Formulario de producto
    // ---------------------------------------------------------------
    function abrir(id) { $(id).classList.add('abierto'); }
    function cerrar(id) { $(id).classList.remove('abierto'); }

    document.querySelectorAll('.cat-overlay').forEach(ov => {
        ov.addEventListener('click', e => {
            if (e.target === ov || e.target.closest('[data-cerrar]')) ov.classList.remove('abierto');
        });
    });
    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;
        if ($('modalFotos').classList.contains('abierto')) cerrar('modalFotos');
        else cerrar('modalProducto');
    });

    function setFoto(ruta) {
        $('fImagen').value = ruta || '';
        const img = $('fFotoPreview');
        delete img.dataset.fallo;
        img.src = fotoSrc(ruta);
        $('fFotoNota').textContent = ruta || 'Sin foto: se muestra la imagen por defecto.';
        $('btnQuitarFoto').hidden = !ruta;
    }

    function actualizarPreview() {
        const usd = parseFloat($('fUSD').value) || 0;
        const usarFijo = $('fUsarFijo').checked;
        const fijo = usarFijo ? (parseFloat($('fFijo').value) || 0) : 0;
        $('fFijo').disabled = !usarFijo;
        const pesos = fijo > 0 ? fijo : Math.round(usd * config.cotizacion);
        const transf = Math.round(pesos * (1 + config.pct_transf / 100));
        $('fPreview').innerHTML = pesos > 0
            ? `En el sitio: Efectivo <b>$${fmt(pesos)}</b> · Transferencia <b>$${fmt(transf)}</b>` +
              (fijo > 0 ? ' (precio fijo, no cambia con el dólar)' : ` (dólar a $${fmt(config.cotizacion)})`)
            : 'Cargá el precio en USD o un precio fijo en pesos.';
    }

    function abrirFormulario(p, titulo) {
        p = p || {};
        $('modalTitulo').textContent = titulo || (p.id ? 'Editar producto' : 'Nuevo producto');
        $('fId').value = p.id || '';
        $('fProducto').value = p.producto || '';
        $('fDescripcion').value = p.descripcion || '';
        $('fCondicion').value = p.condicion || $('filtroCondicion').value || condiciones[0] || '';
        $('fTipo').value = p.tipo || '';
        $('fUSD').value = p.precioUSD > 0 ? p.precioUSD : '';
        $('fUsarFijo').checked = p.precioFijo > 0;
        $('fFijo').value = p.precioFijo > 0 ? p.precioFijo : '';
        $('fDisponible').checked = p.id === undefined ? true : !!p.disponible;
        $('fError').textContent = '';
        $('btnGuardar').disabled = false;
        setFoto(p.imagen || '');
        actualizarPreview();
        abrir('modalProducto');
        $('fProducto').focus();
    }

    $('btnNuevo').addEventListener('click', () => abrirFormulario());
    ['fUSD', 'fFijo', 'fUsarFijo'].forEach(id => $(id).addEventListener('input', actualizarPreview));
    $('fUsarFijo').addEventListener('change', () => { if ($('fUsarFijo').checked) $('fFijo').focus(); });

    $('formProducto').addEventListener('submit', async e => {
        e.preventDefault();
        const usarFijo = $('fUsarFijo').checked;
        const datos = {
            id: $('fId').value,
            producto: $('fProducto').value,
            descripcion: $('fDescripcion').value,
            condicion: $('fCondicion').value,
            tipo: $('fTipo').value,
            precioUSD: parseFloat($('fUSD').value) || 0,
            precioFijo: usarFijo ? (parseFloat($('fFijo').value) || 0) : 0,
            imagen: $('fImagen').value,
            disponible: $('fDisponible').checked
        };
        $('fError').textContent = '';
        $('btnGuardar').disabled = true;
        try {
            aplicar(await api('guardar', datos));
            cerrar('modalProducto');
            toast(datos.disponible ? 'Guardado. Ya se ve en el sitio.' : 'Guardado como no disponible (no se ve en el sitio).');
        } catch (err) {
            $('fError').textContent = err.message;
            $('btnGuardar').disabled = false;
        }
    });

    // ---------------------------------------------------------------
    // Fotos: subir desde el dispositivo
    // ---------------------------------------------------------------
    // Achica la foto antes de subirla (las del celular pesan varios MB).
    async function achicar(archivo) {
        if (!/^image\/(jpeg|png|webp)$/.test(archivo.type) || !window.createImageBitmap) return archivo;
        let bmp;
        try { bmp = await createImageBitmap(archivo); } catch (e) { return archivo; }
        const MAX = 1000;
        const k = Math.min(1, MAX / Math.max(bmp.width, bmp.height));
        if (k === 1 && archivo.size < 300 * 1024) return archivo;

        const canvas = document.createElement('canvas');
        canvas.width = Math.round(bmp.width * k);
        canvas.height = Math.round(bmp.height * k);
        canvas.getContext('2d').drawImage(bmp, 0, 0, canvas.width, canvas.height);
        // PNG se mantiene en PNG para no perder el fondo transparente.
        const tipo = archivo.type === 'image/png' ? 'image/png' : 'image/jpeg';
        const blob = await new Promise(ok => canvas.toBlob(ok, tipo, 0.85));
        return blob && blob.size < archivo.size ? new File([blob], archivo.name, { type: tipo }) : archivo;
    }

    $('btnSubirFoto').addEventListener('click', () => $('fArchivo').click());
    $('fArchivo').addEventListener('change', async () => {
        const archivo = $('fArchivo').files[0];
        $('fArchivo').value = '';
        if (!archivo) return;
        $('fFotoNota').textContent = 'Subiendo foto...';
        $('btnGuardar').disabled = true;
        try {
            const fd = new FormData();
            fd.append('foto', await achicar(archivo), archivo.name);
            fd.append('nombre', $('fProducto').value.trim() || archivo.name);
            const data = await api('subir', fd);
            setFoto(data.ruta);
            if (imagenes) imagenes.push(data.ruta);
        } catch (err) {
            setFoto($('fImagen').value);
            $('fError').textContent = err.message;
        }
        $('btnGuardar').disabled = false;
    });

    $('btnQuitarFoto').addEventListener('click', () => setFoto(''));

    // ---------------------------------------------------------------
    // Fotos: elegir una que ya está en /images
    // ---------------------------------------------------------------
    function renderGaleria() {
        const q = $('buscarFoto').value.trim().toLowerCase();
        imagenesFiltradas = (imagenes || []).filter(r => !q || r.toLowerCase().includes(q));
        $('galeria').innerHTML = imagenesFiltradas.length
            ? imagenesFiltradas.map((r, i) =>
                `<button type="button" data-idx="${i}" title="${esc(r)}">
                    <img src="${esc(CFG.raiz + r)}" alt="" loading="lazy">
                    <span>${esc(r.replace(/^images\//, ''))}</span>
                </button>`).join('')
            : '<p class="cat-nota">No hay fotos que coincidan.</p>';
    }

    $('btnElegirFoto').addEventListener('click', async () => {
        abrir('modalFotos');
        $('buscarFoto').value = '';
        if (!imagenes) {
            $('galeria').innerHTML = '<p class="cat-nota">Cargando fotos...</p>';
            try {
                imagenes = (await api('imagenes')).imagenes;
            } catch (err) {
                $('galeria').innerHTML = `<p class="cat-error">${esc(err.message)}</p>`;
                return;
            }
        }
        renderGaleria();
        $('buscarFoto').focus();
    });
    $('buscarFoto').addEventListener('input', renderGaleria);
    $('galeria').addEventListener('click', e => {
        const b = e.target.closest('[data-idx]');
        if (!b) return;
        setFoto(imagenesFiltradas[Number(b.dataset.idx)]);
        cerrar('modalFotos');
    });

    // ---------------------------------------------------------------
    // Importar los productos que ya están en el Google Sheet
    // ---------------------------------------------------------------
    $('btnImportar').addEventListener('click', async () => {
        if (!confirm('Se van a traer los productos del Google Sheet del formulario anterior.\n\nSolo se agregan los que todavía no estén cargados acá (no se duplica ni se borra nada). ¿Continuar?')) return;
        const boton = $('btnImportar');
        boton.disabled = true;
        boton.textContent = 'Importando...';
        try {
            let hoja;
            try {
                const res = await fetch(`https://sheets.googleapis.com/v4/spreadsheets/${SHEET_ID}/values/${SHEET_RANGE}?key=${SHEET_KEY}`);
                hoja = await res.json();
            } catch (e) {
                throw new Error('No se pudo leer el Google Sheet.');
            }
            if (!hoja.values || hoja.values.length < 2) throw new Error('El Google Sheet no devolvió productos.');

            const [headers, ...rows] = hoja.values;
            const col = nombre => headers.indexOf(nombre);
            const celda = (r, nombre) => (col(nombre) >= 0 ? (r[col(nombre)] || '') : '').trim();
            const filas = rows.map(r => {
                const usd = parseFloat(celda(r, 'PrecioUSD')) || 0;
                return {
                    producto: celda(r, 'Producto'),
                    descripcion: celda(r, 'Descripción'),
                    condicion: celda(r, 'Condición del Producto'),
                    tipo: celda(r, 'Tipo de Producto'),
                    precioUSD: usd,
                    // Si no tiene USD pero sí pesos, queda como precio fijo.
                    precioFijo: usd > 0 ? 0 : (parseFloat(celda(r, 'PrecioPesos')) || 0),
                    imagen: celda(r, 'Imagen2'),
                    disponible: celda(r, 'Status') === 'Disponible'
                };
            });

            const data = await api('importar', { productos: filas });
            aplicar(data);
            let msj = `Se importaron ${data.agregados} productos.`;
            if (data.repetidos) msj += ` ${data.repetidos} ya estaban cargados.`;
            if (data.invalidos) msj += ` ${data.invalidos} filas se omitieron por datos incompletos (sin nombre, categoría o precio).`;
            toast(msj);
        } catch (err) {
            toast(err.message, true);
        }
        boton.disabled = false;
        boton.textContent = 'Importar desde Google Sheet';
    });

    // ---------------------------------------------------------------
    // Inicio
    // ---------------------------------------------------------------
    api('listar').then(data => {
        aplicar(data);
        if (data.dolar === 'cambio' && data.productos.length) avisoDolar('cambio');
    }).catch(err => {
        $('vacio').hidden = false;
        $('vacio').textContent = err.message;
    });
})();
