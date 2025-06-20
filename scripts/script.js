
document.addEventListener('DOMContentLoaded', loadGoogleSheetData);

// ID de la hoja de Google Sheets y clave de API
const sheetID = '1W7aJMPe00ORHGjVnRzScIg6KVnjTQvddm63SLHrsAJM'; // Reemplaza con tu ID de hoja
const apiKey = 'AIzaSyCdutMi4aKT3vJHaOabTtKUERoYv1-UBmM'; // Reemplaza con tu clave de API
const sheetRange = 'Form'; // Nombre de la hoja o rango específico

// URL de la API de Google Sheets
const sheetURL = `https://sheets.googleapis.com/v4/spreadsheets/${sheetID}/values/${sheetRange}?key=${apiKey}`;

async function loadGoogleSheetData() {
    try {
        const response = await fetch(sheetURL);
        const data = await response.json();

        if (!data.values || data.values.length < 2) {
            console.error('Datos insuficientes o mal estructurados en la hoja.');
            return;
        }

        const rows = data.values;
        const headers = rows[0];
        const pricingContainer = document.getElementById('pricing');
        pricingContainer.innerHTML = '';

        const productsByCondition = {};
        const conditionOrder = ["Apple Nuevos", "Apple Usados", "Android Nuevos", "Android Usados", "Notebooks Nuevas","Notebooks Usadas","Tablets Nuevas","Tablets Usadas","Accesorios"];

        rows.slice(1).forEach(row => {
            const status = row[headers.indexOf('Status')] || 'No disponible';
            if (status !== 'Disponible') return; // Filtrar solo productos disponibles

            const condicionProducto = row[headers.indexOf('Condición del Producto')] || 'Otros';
            const tipoProducto = row[headers.indexOf('Tipo de Producto')] || 'Otros';
            const producto = row[headers.indexOf('Producto')] || 'Producto no definido';
            const descripcion = row[headers.indexOf('Descripción')] || 'Descripción no disponible';
            const precioUSD = row[headers.indexOf('PrecioUSD')] || '0';
            const precioPesos = row[headers.indexOf('PrecioPesos')] || '0';
            const precioTransf = row[headers.indexOf('PrecioTransf')] || '0';
            const imagen = row[headers.indexOf('Imagen2')] || 'images/default.png';

            if (!productsByCondition[condicionProducto]) {
                productsByCondition[condicionProducto] = {};
            }

            if (!productsByCondition[condicionProducto][tipoProducto]) {
                productsByCondition[condicionProducto][tipoProducto] = [];
            }

            productsByCondition[condicionProducto][tipoProducto].push({
                producto,
                descripcion,
                precioUSD: parseFloat(precioUSD).toLocaleString('es-AR', { minimumFractionDigits: 0 }),
                precioPesos: parseFloat(precioPesos).toLocaleString('es-AR', { minimumFractionDigits: 0 }),
                precioTransf: parseFloat(precioTransf).toLocaleString('es-AR', { minimumFractionDigits: 0 }),
                imagen
            });
        });

        // Ordenar condiciones según el orden predefinido
        conditionOrder.forEach(condition => {
            if (productsByCondition[condition]) {
                pricingContainer.innerHTML += `<h1 class="condition-title">${condition}</h1>`;

                const sortedCategories = Object.keys(productsByCondition[condition]).sort();

                sortedCategories.forEach(category => {
                    // Ordenar productos por nombre, y en caso de empate, priorizar 64gb sobre 128gb en la descripción
                    productsByCondition[condition][category].sort((a, b) => {
                        const nameComp = a.producto.localeCompare(b.producto);
                        if (nameComp !== 0) return nameComp;

                        const getPriority = (text) => {
                            if (/64\s*gb/i.test(text)) return 0;
                            if (/128\s*gb/i.test(text)) return 1;
                            return 2;
                        };

                        const prioA = getPriority(a.descripcion);
                        const prioB = getPriority(b.descripcion);
                        if (prioA !== prioB) return prioA - prioB;

                        return a.descripcion.localeCompare(b.descripcion);
                    });

                    // Mostrar la categoría solo si la condición es "Accesorios"
                    if (condition === "Accesorios") {
                        pricingContainer.innerHTML += `<h2 class="category-title" style="background-color:rgb(180, 180, 180); padding: 10px; border-radius: 5px;">${category}</h2>`;
                    }

                    // Mostrar productos de la categoría
                    productsByCondition[condition][category].forEach(product => {
                        pricingContainer.innerHTML += `
                            <div class="pricing-item">
                                <div class="product-row">
                                    <!-- Imagen del producto -->
                                    <div class="image-column">
                                        <img src="${product.imagen}" alt="${product.producto}" class="product-image" />
                                    </div>
                                    <!-- Detalles del producto -->
                                    <div class="details-column">
                                        <h2>${product.producto}</h2>
                                        <p>${product.descripcion}</p>
                                    </div>
                                    <!-- Precios -->
                                    <div class="price-column">
                                        <div class="prices">
                                            ${(condition === "Accesorios" || condition === "Otros") && category !== "Gaming" ? `
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
        console.error('Error al cargar los datos de Google Sheets:', error);
    }
}


document.addEventListener('DOMContentLoaded', loadGoogleSheetData);