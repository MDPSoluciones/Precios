// 🚀 Iniciar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    loadGoogleSheetData(); // Cargar datos desde Google Sheets al iniciar

    // 🔄 Actualización automática cada N minutos (5 por defecto)
    const minutos = 5;
    setInterval(loadGoogleSheetData, minutos * 60 * 1000); // Ejecutar la carga cada N minutos

    // 🔍 Búsqueda de productos por texto en el campo de búsqueda
    document.getElementById('searchInput').addEventListener('input', function () {
        const query = this.value.toLowerCase(); // Convertir input a minúsculas
        const products = document.querySelectorAll('.pricing-item'); // Seleccionar todos los productos

        // Mostrar u ocultar productos según coincidencia con título o descripción
        products.forEach(product => {
            const title = product.querySelector('h2').textContent.toLowerCase();
            const desc = product.querySelector('p').textContent.toLowerCase();
            product.style.display = title.includes(query) || desc.includes(query) ? '' : 'none';
        });

        // 🧼 Ocultar títulos de condición si no hay productos visibles debajo
        document.querySelectorAll('.condition-title').forEach(title => {
            const items = [];
            let sibling = title.nextElementSibling;
            while (sibling && !sibling.classList.contains('condition-title')) {
                if (sibling.classList.contains('pricing-item')) items.push(sibling);
                sibling = sibling.nextElementSibling;
            }
            title.style.display = items.some(item => item.style.display !== 'none') ? '' : 'none';
        });

        // 🧼 Ocultar títulos de categoría si están vacíos después del filtrado
        document.querySelectorAll('.category-title').forEach(title => {
            const items = [];
            let sibling = title.nextElementSibling;
            while (
                sibling &&
                !sibling.classList.contains('category-title') &&
                !sibling.classList.contains('condition-title')
            ) {
                if (sibling.classList.contains('pricing-item')) items.push(sibling);
                sibling = sibling.nextElementSibling;
            }
            title.style.display = items.some(item => item.style.display !== 'none') ? '' : 'none';
        });
    });
});

// 📦 Función principal que carga y muestra los datos desde Google Sheets
async function loadGoogleSheetData() {
    // 🎯 Configuración de la hoja y API
    const sheetID = '1W7aJMPe00ORHGjVnRzScIg6KVnjTQvddm63SLHrsAJM'; // ID de la hoja de cálculo
    const apiKey = 'AIzaSyCdutMi4aKT3vJHaOabTtKUERoYv1-UBmM'; // Tu clave de API
    const sheetRange = 'Form'; // Nombre de la hoja/rango
    const sheetURL = `https://sheets.googleapis.com/v4/spreadsheets/${sheetID}/values/${sheetRange}?key=${apiKey}`;

    try {
        const response = await fetch(sheetURL); // Obtener datos desde la API
        const data = await response.json(); // Parsear respuesta como JSON
        if (!data.values || data.values.length < 2) return; // Validación mínima

        const [headers, ...rows] = data.values; // Separar cabecera y filas
        const pricingContainer = document.getElementById('pricing');
        pricingContainer.innerHTML = ''; // Limpiar contenido actual

        const productsByCondition = {}; // 🗂️ Almacenar productos organizados por condición
        const conditionOrder = [ // 🎯 Orden fijo para mostrar las condiciones
            "Apple Nuevos", "Apple Usados", "Android Nuevos", "Android Usados",
            "Notebooks Nuevas", "Notebooks Usadas", "PC Escritorio",
            "Tablets Nuevas", "Tablets Usadas", "Accesorios"
        ];

        // 🔁 Procesar cada fila (producto) del sheet
        rows.forEach(row => {
            const status = row[headers.indexOf('Status')] || 'No disponible';
            if (status !== 'Disponible') return; // Solo mostrar disponibles

            // 🧩 Extraer atributos del producto
            const condicion = row[headers.indexOf('Condición del Producto')] || 'Otros';
            const tipo = row[headers.indexOf('Tipo de Producto')] || 'Otros';
            const producto = row[headers.indexOf('Producto')] || 'Sin nombre';
            const descripcion = row[headers.indexOf('Descripción')] || '';
            const precioUSD = row[headers.indexOf('PrecioUSD')] || '0';
            const precioPesos = row[headers.indexOf('PrecioPesos')] || '0';
            const precioTransf = row[headers.indexOf('PrecioTransf')] || '0';
            const imagen = row[headers.indexOf('Imagen2')] || 'images/default.png';

            // 📁 Inicializar estructuras si no existen
            productsByCondition[condicion] ??= {};
            productsByCondition[condicion][tipo] ??= [];

            // 🛒 Añadir producto al grupo correspondiente
            productsByCondition[condicion][tipo].push({
                producto,
                descripcion,
                precioUSD: parseFloat(precioUSD).toLocaleString('es-AR'),
                precioPesos: parseFloat(precioPesos).toLocaleString('es-AR'),
                precioTransf: parseFloat(precioTransf).toLocaleString('es-AR'),
                imagen
            });
        });

        // 🎨 Renderizar los productos por condición y categoría
        conditionOrder.forEach(condicion => {
            if (productsByCondition[condicion]) {
                pricingContainer.innerHTML += `<h1 class="condition-title">${condicion}</h1>`;

                const sortedCategories = Object.keys(productsByCondition[condicion]).sort();
                sortedCategories.forEach(categoria => {
                    const productos = productsByCondition[condicion][categoria];

                    // 🔢 Ordenar productos alfabéticamente y por prioridad (GB)
                    productos.sort((a, b) => {
                        /* const nameComp = a.producto.localeCompare(b.producto);
                        if (nameComp !== 0) return nameComp; */

                        if (condicion === "Apple Nuevos" || condicion === "Apple Usados") {
                            // Orden descendente Z → A
                            const nameComp = b.producto.localeCompare(a.producto);
                            if (nameComp !== 0) return nameComp;
                        } else {
                            // Orden ascendente A → Z
                            const nameComp = a.producto.localeCompare(b.producto);
                            if (nameComp !== 0) return nameComp;
                        }

                        const prio = text =>
                            /64\s*gb/i.test(text) ? 0 : /128\s*gb/i.test(text) ? 1 : 2;

                        const prioA = prio(a.descripcion), prioB = prio(b.descripcion);
                        return prioA !== prioB ? prioA - prioB : a.descripcion.localeCompare(b.descripcion);
                    });

                    // 📌 Mostrar categoría solo si es "Accesorios"
                    if (condicion === "Accesorios") {
                        pricingContainer.innerHTML += `
                            <h2 class="category-title" style="background-color:rgb(180,180,180);padding:10px;border-radius:5px;">
                                ${categoria}
                            </h2>`;
                    }

                    // 🖼️ Insertar cada producto en el DOM
                    productos.forEach(p => {
                        const precios = (condicion === "Accesorios" || condicion === "Otros") && categoria !== "Gaming"
                            ? `<span class="price pesos">Efectivo: $${p.precioPesos}</span>
                               <span class="price transf">Transferencia: $${p.precioTransf}</span>`
                            : `<span class="price usd">USD: $${p.precioUSD}</span>
                               <span class="price pesos">Efectivo: $${p.precioPesos}</span>
                               <span class="price transf">Transferencia: $${p.precioTransf}</span>`;

                        pricingContainer.innerHTML += `
                            <div class="pricing-item">
                                <div class="product-row">
                                    <div class="image-column">
                                        <img src="${p.imagen}" alt="${p.producto}" class="product-image" />
                                    </div>
                                    <div class="details-column">
                                        <h2>${p.producto}</h2>
                                        <p>${p.descripcion}</p>
                                    </div>
                                    <div class="price-column">
                                        <div class="prices">${precios}</div>
                                    </div>
                                </div>
                            </div>`;
                    });
                });
            }
        });

    } catch (error) {
        console.error('Error al cargar los datos de Google Sheets:', error); // Manejo de error
    }
}