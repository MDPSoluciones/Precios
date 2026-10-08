// ===================================================
// CONFIGURACIÓN GLOBAL
// ===================================================
const STORAGE_KEY_VIEW = 'mdp_view_mode';      // 'list' | 'grid' (misma clave que el sitio actual)
const CATEGORIA_INICIAL = 'Apple Nuevos';      // categoría que se muestra al abrir
const MINUTOS_ACTUALIZACION = 5;               // recarga automática de datos

// Google Sheet (mismo origen de datos que el sitio productivo)
const SHEET_ID = '1W7aJMPe00ORHGjVnRzScIg6KVnjTQvddm63SLHrsAJM';
const API_KEY = 'AIzaSyCdutMi4aKT3vJHaOabTtKUERoYv1-UBmM';
const SHEET_RANGE = 'Form';

// Ruta base para imágenes relativas del Sheet (ej: "images/x.png").
// Vacío porque el sitio está en la raíz (en una subcarpeta sería '../').
const IMG_BASE = '';

// ===================================================
// MENÚ LATERAL: grupos → categorías
// - Los grupos normales usan la "Condición del Producto" del Sheet.
// - Accesorios usa el "Tipo de Producto" (se arma solo con lo que haya cargado).
// ===================================================
const MENU = [
    {
        name: 'Celulares', icon: 'phone',
        items: ['Apple Nuevos', 'Apple Usados', 'Android Nuevos', 'Android Usados']
    },
    {
        name: 'Computación', icon: 'laptop',
        items: ['Notebooks Nuevas', 'Notebooks Usadas', 'PC Escritorio', 'Tablets Nuevas', 'Tablets Usadas']
    },
    {
        name: 'Accesorios', icon: 'plug',
        condicion: 'Accesorios', byTipo: true, items: [] // se completa con los tipos disponibles
    }
];

const ICONS = {
    phone: '<rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/>',
    laptop: '<rect x="4" y="4" width="16" height="11" rx="1"/><path d="M2 19h20"/>',
    plug: '<path d="M9 2v6M15 2v6M6 8h12v4a6 6 0 0 1-12 0z M12 18v4"/>',
    chev: '<path d="m9 6 6 6-6 6"/>'
};
const svg = (k, s = 16) =>
    `<svg width="${s}" height="${s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${ICONS[k]}</svg>`;

// ===================================================
// ESTADO
// ===================================================
let DATA = [];
const state = {
    group: 'Celulares',
    cat: CATEGORIA_INICIAL,   // '*' = ver todo
    tipo: null,               // filtro rápido (chips)
    q: '',
    view: 'grid',
    openGroups: new Set(['Celulares'])
};

// 🚀 Iniciar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    initViewToggle();
    initSearch();
    initSidebarEvents();
    initDrawer();
    initSpider();

    loadGoogleSheetData();
    setInterval(loadGoogleSheetData, MINUTOS_ACTUALIZACION * 60 * 1000);
});

// ===================================================
// UTILIDADES
// ===================================================
const esc = s => String(s ?? '').replace(/[&<>"']/g, c =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

const fmt = n => (isNaN(n) ? 0 : n).toLocaleString('es-AR');

function imgSrc(path) {
    if (!path) return IMG_BASE + 'images/default.png';
    if (/^(https?:)?\/\//.test(path) || path.startsWith('/') || path.startsWith('data:')) return path;
    return IMG_BASE + path;
}

// ¿El producto pertenece a esta categoría del menú?
function inCat(p, groupName, cat) {
    const g = MENU.find(m => m.name === groupName);
    if (!g) return false;
    return g.byTipo ? (p.condicion === g.condicion && p.tipo === cat) : p.condicion === cat;
}
const countCat = (groupName, cat) => DATA.filter(p => inCat(p, groupName, cat)).length;

// Orden original: por nombre y luego 64GB < 128GB < resto
function sortProducts(list) {
    const prio = t => /64\s*gb/i.test(t) ? 0 : /128\s*gb/i.test(t) ? 1 : 2;
    return list.sort((a, b) => {
        const n = a.producto.localeCompare(b.producto);
        if (n !== 0) return n;
        const pa = prio(a.descripcion), pb = prio(b.descripcion);
        return pa !== pb ? pa - pb : a.descripcion.localeCompare(b.descripcion);
    });
}

// ===================================================
// TOGGLE DE VISTA (GRILLA / LISTA)
// ===================================================
function initViewToggle() {
    const btnGrid = document.getElementById('btnGrid');
    const btnList = document.getElementById('btnList');

    let saved = 'grid';
    try { saved = localStorage.getItem(STORAGE_KEY_VIEW) || 'grid'; } catch (e) { }
    setView(saved === 'list' ? 'list' : 'grid', false);

    btnGrid.addEventListener('click', () => setView('grid'));
    btnList.addEventListener('click', () => setView('list'));

    function setView(mode, rerender = true) {
        state.view = mode;
        btnGrid.classList.toggle('active', mode === 'grid');
        btnList.classList.toggle('active', mode === 'list');
        try { localStorage.setItem(STORAGE_KEY_VIEW, mode); } catch (e) { }
        if (rerender) renderMain();
    }
}

// ===================================================
// BÚSQUEDA (busca en todo el catálogo)
// ===================================================
function initSearch() {
    const input = document.getElementById('searchInput');
    input.addEventListener('input', () => {
        state.q = input.value;
        renderMain();
    });
}

// ===================================================
// MENÚ EN CELULAR (drawer)
// ===================================================
function initDrawer() {
    document.getElementById('menuBtn').addEventListener('click', () => document.body.classList.add('drawer-open'));
    document.getElementById('backdrop').addEventListener('click', () => document.body.classList.remove('drawer-open'));
}

// ===================================================
// SIDEBAR
// ===================================================
function renderSidebar() {
    const side = document.getElementById('sidebar');
    let html = `<div class="side-title">Categorías</div>`;
    html += `<button class="all-btn ${state.cat === '*' ? 'active' : ''}" data-group="*" data-cat="*">
                Ver todo <span class="count">${DATA.length}</span></button>`;

    MENU.forEach(g => {
        const items = g.items.filter(c => countCat(g.name, c) > 0);
        if (!items.length) return;
        const open = state.openGroups.has(g.name);

        html += `<div class="group ${open ? 'open' : ''}">
            <button class="group-head" data-toggle="${esc(g.name)}">
                <span class="ico">${svg(g.icon)}</span>${esc(g.name)}
                <span class="chev">${svg('chev', 14)}</span>
            </button>
            <ul class="sub">${items.map(c => {
                const active = state.cat === c && state.group === g.name;
                return `<li><button class="${active ? 'active' : ''}" data-group="${esc(g.name)}" data-cat="${esc(c)}">
                            ${esc(c)} <span class="count">${countCat(g.name, c)}</span></button></li>`;
            }).join('')}</ul>
        </div>`;
    });
    side.innerHTML = html;
}

function initSidebarEvents() {
    document.getElementById('sidebar').addEventListener('click', e => {
        // Abrir / cerrar grupo
        const t = e.target.closest('[data-toggle]');
        if (t) {
            const name = t.dataset.toggle;
            state.openGroups.has(name) ? state.openGroups.delete(name) : state.openGroups.add(name);
            t.parentElement.classList.toggle('open');
            return;
        }
        // Elegir categoría
        const b = e.target.closest('[data-cat]');
        if (!b) return;
        selectCategory(b.dataset.group, b.dataset.cat);
        document.body.classList.remove('drawer-open');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // Chips de filtro rápido
    document.getElementById('main').addEventListener('click', e => {
        const c = e.target.closest('[data-tipo]');
        if (!c) return;
        state.tipo = c.dataset.tipo || null;
        renderMain();
    });
}

function selectCategory(group, cat) {
    state.group = group;
    state.cat = cat;
    state.tipo = null;
    state.q = '';
    document.getElementById('searchInput').value = '';
    if (group !== '*') state.openGroups.add(group);
    renderSidebar();
    renderMain();
}

// Si la categoría elegida quedó vacía (ej: se vendió todo), pasar a la primera con stock
function ensureValidCategory() {
    if (state.cat === '*' || countCat(state.group, state.cat) > 0) return;
    for (const g of MENU) {
        const c = g.items.find(c => countCat(g.name, c) > 0);
        if (c) { state.group = g.name; state.cat = c; state.openGroups.add(g.name); return; }
    }
    state.cat = '*';
}

// ===================================================
// VISTA PRINCIPAL
// ===================================================
function card(p) {
    // Mismas reglas que el sitio actual: accesorios sin USD (salvo Gaming)
    const mostrarUSD = !((p.condicion === 'Accesorios' || p.condicion === 'Otros') && p.tipo !== 'Gaming');
    const estado = /usad/i.test(p.condicion) ? 'usado' : /nuev/i.test(p.condicion) ? 'nuevo' : '';

    return `
    <div class="card">
        <div class="img"><img src="${esc(imgSrc(p.imagen))}" alt="${esc(p.producto)}" loading="lazy"
             onerror="this.onerror=null;this.src='${IMG_BASE}images/default.png'"></div>
        <div class="info">
            ${estado ? `<span class="badge ${estado}">${estado.toUpperCase()}</span>` : ''}
            <h3>${esc(p.producto)}</h3>
            <div class="desc">${esc(p.descripcion)}</div>
        </div>
        <div class="prices">
            ${mostrarUSD ? `<div class="p"><span>USD</span><b class="usd">$${fmt(p.precioUSD)}</b></div>` : ''}
            <div class="p"><span>Efectivo</span><b class="pesos">$${fmt(p.precioPesos)}</b></div>
            <div class="p"><span>Transferencia</span><b class="transf">$${fmt(p.precioTransf)}</b></div>
            <div class="fin">💳 Consultar por financiación</div>
        </div>
    </div>`;
}

const grid = list => `<div class="products ${state.view === 'list' ? 'list' : ''}">${sortProducts(list).map(card).join('')}</div>`;

function header(crumb, title, n) {
    return `<div class="crumbs">${esc(crumb)}</div>
        <div class="main-head"><h1>${esc(title)}</h1>
        <span class="n">${n} producto${n !== 1 ? 's' : ''}</span></div>`;
}

// Secciones con título, en el orden del menú (para "Ver todo" y búsqueda)
function sections(list) {
    let html = '';
    MENU.forEach(g => g.items.forEach(c => {
        const l = list.filter(p => inCat(p, g.name, c));
        if (l.length) html += `<div class="section-title">${g.byTipo ? 'Accesorios · ' : ''}${esc(c)}</div>${grid(l)}`;
    }));
    return html;
}

function renderMain() {
    const main = document.getElementById('main');
    const q = state.q.trim().toLowerCase();

    // 1) Búsqueda: ignora la categoría y busca en todo
    if (q) {
        const res = DATA.filter(p => `${p.producto} ${p.descripcion} ${p.tipo}`.toLowerCase().includes(q));
        main.innerHTML = header('Búsqueda', `"${state.q.trim()}"`, res.length) +
            (res.length ? sections(res) : '<div class="empty">No encontramos productos con ese nombre.</div>');
        return;
    }

    // 2) Ver todo
    if (state.cat === '*') {
        main.innerHTML = header('Catálogo', 'Todos los productos', DATA.length) + sections(DATA);
        return;
    }

    // 3) Categoría elegida + chips por tipo (si hay más de uno)
    let list = DATA.filter(p => inCat(p, state.group, state.cat));
    const g = MENU.find(m => m.name === state.group);
    const tipos = [...new Set(list.map(p => p.tipo))].sort();
    if (state.tipo && !tipos.includes(state.tipo)) state.tipo = null;

    const chips = (!g.byTipo && tipos.length > 1)
        ? `<div class="chips"><button class="chip ${!state.tipo ? 'active' : ''}" data-tipo="">Todos</button>${tipos.map(t => `<button class="chip ${state.tipo === t ? 'active' : ''}" data-tipo="${esc(t)}">${esc(t)}</button>`).join('')
        }</div>` : '';

    if (state.tipo) list = list.filter(p => p.tipo === state.tipo);
    main.innerHTML = header(state.group, state.cat, list.length) + chips +
        (list.length ? grid(list) : '<div class="empty">No hay productos en esta categoría.</div>');
}

// ===================================================
// CARGA DE DATOS DESDE GOOGLE SHEETS
// ===================================================
async function loadGoogleSheetData() {
    const url = `https://sheets.googleapis.com/v4/spreadsheets/${SHEET_ID}/values/${SHEET_RANGE}?key=${API_KEY}`;

    try {
        const response = await fetch(url);
        const data = await response.json();
        if (!data.values || data.values.length < 2) return;

        const [headers, ...rows] = data.values;
        const col = name => headers.indexOf(name);
        const num = v => parseFloat(v) || 0; // igual que el sitio actual

        DATA = rows
            .filter(r => (r[col('Status')] || 'No disponible') === 'Disponible')
            .map(r => ({
                condicion: r[col('Condición del Producto')] || 'Otros',
                tipo: r[col('Tipo de Producto')] || 'Otros',
                producto: r[col('Producto')] || 'Sin nombre',
                descripcion: r[col('Descripción')] || '',
                precioUSD: num(r[col('PrecioUSD')]),
                precioPesos: num(r[col('PrecioPesos')]),
                precioTransf: num(r[col('PrecioTransf')]),
                imagen: r[col('Imagen2')] || 'images/default.png'
            }));

        // Tipos de accesorios disponibles (orden alfabético, "Otros" al final)
        MENU.filter(m => m.byTipo).forEach(m => {
            m.items = [...new Set(DATA.filter(p => p.condicion === m.condicion).map(p => p.tipo))]
                .sort((a, b) => (a === 'Otros') - (b === 'Otros') || a.localeCompare(b));
        });

        ensureValidCategory();
        renderSidebar();
        renderMain();
    } catch (error) {
        console.error('Error al cargar los datos de Google Sheets:', error);
        if (!DATA.length) {
            document.getElementById('main').innerHTML =
                '<div class="empty">No se pudieron cargar los productos. Intentá de nuevo en unos minutos.</div>';
        }
    }
}

// ===================================================
// 🎃 HALLOWEEN: ARAÑA QUE BAJA CON EL SCROLL
// (solo actúa si el <body> tiene class="halloween")
// ===================================================
function initSpider() {
    const wrap = document.getElementById('spiderWrap');
    if (!wrap || !document.body.classList.contains('halloween')) return;

    const BASE_DROP = 24;   // largo inicial del hilo (px)
    const MAX_EXTRA = 110;  // cuánto puede bajar como máximo (px)
    const FACTOR = 0.2;     // px de hilo por cada px de scroll
    let ticking = false;

    function update() {
        const extra = Math.min(window.scrollY * FACTOR, MAX_EXTRA);
        wrap.style.setProperty('--spider-drop', (BASE_DROP + extra) + 'px');
        ticking = false;
    }

    window.addEventListener('scroll', () => {
        if (!ticking) {
            window.requestAnimationFrame(update);
            ticking = true;
        }
    }, { passive: true });

    update();
}
