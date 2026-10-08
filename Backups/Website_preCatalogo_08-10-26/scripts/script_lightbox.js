// Espera a que el DOM esté completamente cargado para iniciar la función principal
// Esto asegura que el elemento #pricing ya exista
document.addEventListener('DOMContentLoaded', loadGoogleSheetData);

// ID y clave de la API de Google Sheets (reemplazar con los valores reales)
const sheetID = 'xxxxx';
const apiKey = 'xxxx';
const sheetRange = 'Form'; // Nombre de la hoja dentro del archivo

// URL de la API de Google Sheets construida con los datos anteriores
const sheetURL = `https://sheets.googleapis.com/v4/spreadsheets/${sheetID}/values/${sheetRange}?key=${apiKey}`;

// Función asincrónica para cargar los datos desde Google Sheets
async function loadGoogleSheetData() {
    try {
        // Fetch a la API de Google Sheets
        const response = await fetch(sheetURL);
        const data = await response.json();

        // Validación mínima de datos
        if (!data.values || data.values.length < 2) {
            console.error('Datos insuficientes o mal estructurados en la hoja.');
            return;
        }

        // Separar encabezados y filas de datos
        const rows = data.values;
        const headers = rows[0];

        // Contenedor principal donde se insertará el HTML dinámico
        const pricingContainer = document.getElementById('pricing');
        pricingContainer.innerHTML = '';

        // Estructura para agrupar productos por condición y tipo
        const productsByCondition = {};

        // Orden específico de condiciones para agrupar en la UI
        const conditionOrder = [
            "Apple Nuevos",
            "Apple Usados",
            "Android Nuevos",
            "Android Usados",
            "Accesorios"
        ];

        // Procesar cada fila (exceptuando encabezados)
        rows.slice(1).forEach(row => {
            const status = row[headers.indexOf('Status')] || 'No disponible';
            if (status !== 'Disponible') return; // Filtrar solo productos disponibles

            // Extraer cada campo según los encabezados
            const condicionProducto = row[headers.indexOf('Condición del Producto')] || 'Otros';
            const tipoProducto = row[headers.indexOf('Tipo de Producto')] || 'Otros';
            const producto = row[headers.indexOf('Producto')] || 'Producto no definido';
            const descripcion = row[headers.indexOf('Descripción')] || 'Descripción no disponible';
            const precioUSD = row[headers.indexOf('PrecioUSD')] || '0';
            const precioPesos = row[headers.indexOf('PrecioPesos')] || '0';
            const precioTransf = row[headers.indexOf('PrecioTransf')] || '0';
            const imagen = row[headers.indexOf('Imagen2')] || 'images/default.png';

            // Agrupación por condición y tipo de producto
            if (!productsByCondition[condicionProducto]) {
                productsByCondition[condicionProducto] = {};
            }
            if (!productsByCondition[condicionProducto][tipoProducto]) {
                productsByCondition[condicionProducto][tipoProducto] = [];
            }

            // Agregar producto al grupo correspondiente
            productsByCondition[condicionProducto][tipoProducto].push({
                producto,
                descripcion,
                precioUSD: parseFloat(precioUSD).toLocaleString('es-AR', { minimumFractionDigits: 2 }),
                precioPesos: parseFloat(precioPesos).toLocaleString('es-AR', { minimumFractionDigits: 2 }),
                precioTransf: parseFloat(precioTransf).toLocaleString('es-AR', { minimumFractionDigits: 2 }),
                imagen
            });
        });

        // Construir el HTML de manera ordenada según las condiciones definidas
        conditionOrder.forEach(condition => {
            if (productsByCondition[condition]) {
                // Título principal por condición
                pricingContainer.innerHTML += `<h1 class="condition-title">${condition}</h1>`;

                // Ordenar alfabéticamente los tipos de productos
                const sortedCategories = Object.keys(productsByCondition[condition]).sort();

                sortedCategories.forEach(category => {
                    // Para "Accesorios" se muestra un subtítulo de categoría
                    if (condition === "Accesorios") {
                        pricingContainer.innerHTML += `
                            <h2 class="category-title" style="background-color:rgb(180, 180, 180); padding: 10px; border-radius: 5px;">
                                ${category}
                            </h2>`;
                    }

                    // Ordenar productos alfabéticamente por nombre
                    productsByCondition[condition][category].sort((a, b) => a.producto.localeCompare(b.producto));

                    // Generar HTML para cada producto
                    productsByCondition[condition][category].forEach(product => {
                        pricingContainer.innerHTML += `
                            <div class="pricing-item">
                                <div class="product-row">
                                    <!-- Imagen con Lightbox -->
                                    <div class="image-column">
                                        <a href="${product.imagen1}" data-lightbox="${product.producto}" data-title="${product.producto}">
                                            <img src="${product.imagen1}" alt="${product.producto}" class="product-image" />
                                        </a>
                                    </div>
                                    <!-- Detalles del producto -->
                                    <div class="details-column">
                                        <h2>${product.producto}</h2>
                                        <p>${product.descripcion}</p>
                                    </div>
                                    <!-- Precios -->
                                    <div class="price-column">
                                        <div class="prices">
                                            ${condition === "Accesorios" || condition === "Otros" ? `
                                                <span class="price pesos">Efectivo: $${product.precioPesos}</span>
                                                <span class="price transf">Transferencia: $${product.precioTransf}</span>` 
                                            : `
                                                <span class="price usd">USD: $${product.precioUSD}</span>
                                                <span class="price pesos">Efectivo: $${product.precioPesos}</span>
                                                <span class="price transf">Transferencia: $${product.precioTransf}</span>`}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                });
            }
        });

    } catch (error) {
        // Manejo de errores
        console.error('Error al cargar los datos de Google Sheets:', error);
    }
}

// Ejecutar nuevamente por si se cargó tarde el DOM
document.addEventListener('DOMContentLoaded', loadGoogleSheetData);
