// ===================================================
// CONFIGURACIÓN GLOBAL
// ===================================================
const STORAGE_KEY_VIEW = 'mdp_view_mode'; // 'list' | 'grid'
const SCROLL_SHRINK_THRESHOLD = 80;        // px de scroll para encoger header

// 🚀 Iniciar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    initViewToggle();
    initHeaderShrink();
    initSearch();

    loadGoogleSheetData(); // Cargar datos desde Google Sheets al iniciar

    // 🔄 Actualización automática cada N minutos (5 por defecto)
    const minutos = 5;
    setInterval(loadGoogleSheetData, minutos * 60 * 1000);
});

// ===================================================
// TOGGLE DE VISTA (LISTA / GRID)
// ===================================================
function initViewToggle() {
    const toggle = document.getElementById('viewToggle');
    const labelLista = document.getElementById('labelLista');
    const labelGrid = document.getElementById('labelGrid');

    // Recuperar preferencia guardada
    const savedView = localStorage.getItem(STORAGE_KEY_VIEW) || 'list';
    const isGrid = savedView === 'grid';
    toggle.checked = isGrid;
    applyViewMode(isGrid ? 'grid' : 'list');

    // Listener del toggle
    toggle.addEventListener('change', () => {
        const mode = toggle.checked ? 'grid' : 'list';
        localStorage.setItem(STORAGE_KEY_VIEW, mode);
        applyViewMode(mode);
    });

    function applyViewMode(mode) {
        const pricing = document.getElementById('pricing');
        if (!pricing) return;

        pricing.classList.remove('view-list', 'view-grid');
        pricing.classList.add('view-' + mode);

        // Resaltar label activo
        if (mode === 'grid') {
            labelGrid.classList.add('active');
            labelLista.classList.remove('active');
        } else {
            labelLista.classList.add('active');
            labelGrid.classList.remove('active');
        }
    }
}

// ===================================================
// HEADER SHRINK ON SCROLL
// ===================================================
function initHeaderShrink() {
    const header = document.getElementById('mainHeader');
    const body = document.body;

    let ticking = false;

    function onScroll() {
        if (!ticking) {
            window.requestAnimationFrame(() => {
                if (window.scrollY > SCROLL_SHRINK_THRESHOLD) {
                    header.classList.add('shrunk');
                    body.classList.add('header-shrunk');
                } else {
                    header.classList.remove('shrunk');
                    body.classList.remove('header-shrunk');
                }
                ticking = false;
            });
            ticking = true;
        }
    }

    window.addEventListener('scroll', onScroll, { passive: true });
}

// ===================================================
// BÚSQUEDA
// ===================================================
function initSearch() {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) return;

    searchInput.addEventListener('input', function () {
        const query = this.value.toLowerCase();
        const products = document.querySelectorAll('.pricing-item');

        products.forEach(product => {
            const titleEl = product.querySelector('h2');
            const descEl = product.querySelector('p');
            const title = titleEl ? titleEl.textContent.toLowerCase() : '';
            const desc = descEl ? descEl.textContent.toLowerCase() : '';
            product.style.display = title.includes(query) || desc.includes(query) ? '' : 'none';
        });

        // Ocultar bloques (categoría / condición) que quedan vacíos
        document.querySelectorAll('.products-grid').forEach(grid => {
            const visibleItems = Array.from(grid.querySelectorAll('.pricing-item'))
                .filter(item => item.style.display !== 'none');
            grid.style.display = visibleItems.length > 0 ? '' : 'none';
        });

        document.querySelectorAll('.category-title').forEach(title => {
            const nextGrid = title.nextElementSibling;
            if (nextGrid && nextGrid.classList.contains('products-grid')) {
                title.style.display = nextGrid.style.display === 'none' ? 'none' : '';
            }
        });

        document.querySelectorAll('.condition-title').forEach(title => {
            const items = [];
            let sibling = title.nextElementSibling;
            while (sibling && !sibling.classList.contains('condition-title')) {
                if (sibling.classList.contains('pricing-item') ||
                    (sibling.classList.contains('products-grid') && sibling.style.display !== 'none')) {
                    items.push(sibling);
                }
                sibling = sibling.nextElementSibling;
            }
            const hasVisible = items.some(item => {
                if (item.classList.contains('products-grid')) return item.style.display !== 'none';
                return item.style.display !== 'none';
            });
            title.style.display = hasVisible ? '' : 'none';
        });
    });
}

// ===================================================
// CÁLCULO DE CUOTAS
// ===================================================
// base = precio efectivo
// cuota1  = base * 1.339
// cuota3  = (base * 1.571) / 3
// cuota6  = (base * 1.764) / 6
// cuota9  = (base * 2.004) / 9
// cuota12 = (base * 2.238) / 12
function calcularCuotas(precioEfectivoNum) {
    if (!precioEfectivoNum || isNaN(precioEfectivoNum) || precioEfectivoNum <= 0) {
        return null;
    }
    const base = precioEfectivoNum;
    return {
        c1:  base * 1.339,
        c3:  (base * 1.571) / 3,
        c6:  (base * 1.764) / 6,
        c9:  (base * 2.004) / 9,
        c12: (base * 2.238) / 12
    };
}

function formatARS(num) {
    if (num == null || isNaN(num)) return '0';
    // Redondeo a entero (sin decimales) para que quede más limpio en cuotas
    return Math.round(num).toLocaleString('es-AR');
}

// ===================================================
// CARGA DE DATOS DESDE GOOGLE SHEETS
// ===================================================
async function loadGoogleSheetData() {
    const sheetID = '1W7aJMPe00ORHGjVnRzScIg6KVnjTQvddm63SLHrsAJM';
    const apiKey = 'AIzaSyCdutMi4aKT3vJHaOabTtKUERoYv1-UBmM';
    const sheetRange = 'Form';
    const sheetURL = `https://sheets.googleapis.com/v4/spreadsheets/${sheetID}/values/${sheetRange}?key=${apiKey}`;

    try {
        const response = await fetch(sheetURL);
        const data = await response.json();
        if (!data.values || data.values.length < 2) return;

        const [headers, ...rows] = data.values;
        const pricingContainer = document.getElementById('pricing');
        pricingContainer.innerHTML = '';

        const productsByCondition = {};
        const conditionOrder = [
            "Apple Nuevos", "Apple Usados", "Android Nuevos", "Android Usados",
            "Notebooks Nuevas", "Notebooks Usadas", "PC Escritorio",
            "Tablets Nuevas", "Tablets Usadas", "Accesorios"
        ];

        // Procesar filas
        rows.forEach(row => {
            const status = row[headers.indexOf('Status')] || 'No disponible';
            if (status !== 'Disponible') return;

            const condicion = row[headers.indexOf('Condición del Producto')] || 'Otros';
            const tipo = row[headers.indexOf('Tipo de Producto')] || 'Otros';
            const producto = row[headers.indexOf('Producto')] || 'Sin nombre';
            const descripcion = row[headers.indexOf('Descripción')] || '';
            const precioUSDRaw = row[headers.indexOf('PrecioUSD')] || '0';
            const precioPesosRaw = row[headers.indexOf('PrecioPesos')] || '0';
            const precioTransfRaw = row[headers.indexOf('PrecioTransf')] || '0';
            const imagen = row[headers.indexOf('Imagen2')] || 'images/default.png';

            const precioPesosNum = parseFloat(precioPesosRaw) || 0;

            productsByCondition[condicion] ??= {};
            productsByCondition[condicion][tipo] ??= [];

            productsByCondition[condicion][tipo].push({
                producto,
                descripcion,
                precioUSD: parseFloat(precioUSDRaw).toLocaleString('es-AR'),
                precioPesos: precioPesosNum.toLocaleString('es-AR'),
                precioTransf: parseFloat(precioTransfRaw).toLocaleString('es-AR'),
                precioPesosNum, // valor numérico para cuotas
                imagen
            });
        });

        // Renderizar
        conditionOrder.forEach(condicion => {
            if (!productsByCondition[condicion]) return;

            pricingContainer.innerHTML += `<h1 class="condition-title">${condicion}</h1>`;

            const sortedCategories = Object.keys(productsByCondition[condicion]).sort();
            sortedCategories.forEach(categoria => {
                const productos = productsByCondition[condicion][categoria];

                // Ordenar productos
                productos.sort((a, b) => {
                    const nameComp = a.producto.localeCompare(b.producto);
                    if (nameComp !== 0) return nameComp;

                    const prio = text =>
                        /64\s*gb/i.test(text) ? 0 : /128\s*gb/i.test(text) ? 1 : 2;

                    const prioA = prio(a.descripcion), prioB = prio(b.descripcion);
                    return prioA !== prioB ? prioA - prioB : a.descripcion.localeCompare(b.descripcion);
                });

                // Mostrar título de categoría solo si es Accesorios
                if (condicion === "Accesorios") {
                    pricingContainer.innerHTML += `
                        <h2 class="category-title" style="background-color:rgb(180,180,180);padding:10px;border-radius:5px;">
                            ${categoria}
                        </h2>`;
                }

                // Wrapper grid (en lista se muestra como flex-column gracias al CSS)
                let cardsHTML = '<div class="products-grid">';

                productos.forEach(p => {
                    // Precios principales
                    const mostrarUSD = !((condicion === "Accesorios" || condicion === "Otros") && categoria !== "Gaming");

                    let preciosHTML = '';
                    if (mostrarUSD) {
                        preciosHTML += `<span class="price usd">USD: $${p.precioUSD}</span>`;
                    }
                    preciosHTML += `<span class="price pesos">Efectivo: $${p.precioPesos}</span>`;
                    preciosHTML += `<span class="price transf">Transferencia: $${p.precioTransf}</span>`;

                    // Cuotas (calculadas siempre desde el efectivo)
                    const cuotas = calcularCuotas(p.precioPesosNum);
                    let cuotasHTML = '';
                    if (cuotas) {
                        cuotasHTML = `
                            <div class="cuotas-list">
                                <span class="cuota-item">💳</span>
                                <span class="cuota-item"><span class="cuota-label">1 pago:</span>$${formatARS(cuotas.c1)}</span>
                                <span class="cuota-item"><span class="cuota-label">3 cuotas:</span>$${formatARS(cuotas.c3)}</span>
                                <span class="cuota-item"><span class="cuota-label">6 cuotas:</span>$${formatARS(cuotas.c6)}</span>
                                <span class="cuota-item"><span class="cuota-label">9 cuotas:</span>$${formatARS(cuotas.c9)}</span>
                                <span class="cuota-item"><span class="cuota-label">12 cuotas:</span>$${formatARS(cuotas.c12)}</span>
                            </div>`;
                    }

                    cardsHTML += `
                        <div class="pricing-item">
                            <div class="image-column">
                                <img src="${p.imagen}" alt="${p.producto}" class="product-image" />
                            </div>
                            <div class="product-row">
                                <div class="details-column">
                                    <h2>${p.producto}</h2>
                                    <p>${p.descripcion}</p>
                                </div>
                                <div class="price-column">
                                    <div class="prices">${preciosHTML}</div>
                                </div>
                            </div>
                            ${cuotasHTML}
                        </div>`;
                });

                cardsHTML += '</div>'; // cierra products-grid
                pricingContainer.innerHTML += cardsHTML;
            });
        });

    } catch (error) {
        console.error('Error al cargar los datos de Google Sheets:', error);
    }
}