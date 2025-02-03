
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
        const conditionOrder = ["Apple Nuevos", "Apple Usados", "Android Nuevos", "Android Usados", "Accesorios"];


        rows.slice(1).forEach(row => {
            const condicionProducto = row[headers.indexOf('Condición del Producto')] || 'Otros';
            const tipoProducto = row[headers.indexOf('Tipo de Producto')] || 'Otros';
            const producto = row[headers.indexOf('Producto')] || 'Producto no definido';
            const descripcion = row[headers.indexOf('Descripción')] || 'Descripción no disponible';
            const precioUSD = row[headers.indexOf('PrecioUSD')] || '0';
            const precioPesos = row[headers.indexOf('PrecioPesos')] || '0';
            const precioTransf = row[headers.indexOf('PrecioTransf')] || '0';
            const imagen1 = row[headers.indexOf('Imagen1')] || 'images/logoNew.PNG';
            const imagen2 = row[headers.indexOf('Imagen2')] || 'images/logoNew.PNG';
            const imagen3 = row[headers.indexOf('Imagen3')] || 'images/logoNew.PNG';

            if (!productsByCondition[condicionProducto]) {
                productsByCondition[condicionProducto] = {};
            }

            if (!productsByCondition[condicionProducto][tipoProducto]) {
                productsByCondition[condicionProducto][tipoProducto] = [];
            }

            productsByCondition[condicionProducto][tipoProducto].push({
                producto,
                descripcion,
                precioUSD: parseFloat(precioUSD).toFixed(2),
                precioPesos: parseFloat(precioPesos).toFixed(2),
                precioTransf: parseFloat(precioTransf).toFixed(2),
                imagen1,
                imagen2,
                imagen3
            });
        });

        conditionOrder.forEach(condition => {
            if (productsByCondition[condition]) {
                pricingContainer.innerHTML += `<h1 class="condition-title">${condition}</h1>`;
                
                const sortedCategories = Object.keys(productsByCondition[condition]).sort();
                
                sortedCategories.forEach(category => {
                    if (condition === "Accesorios") {
                        pricingContainer.innerHTML += `<h2 class="category-title" style="background-color:rgb(180, 180, 180); padding: 10px; border-radius: 5px;">${category}</h2>`;
                    }

                    productsByCondition[condition][category].forEach(product => {
                        pricingContainer.innerHTML += `
                            <div class="pricing-item">
                                <div class="product-row">
                                    <div class="image-gallery">
                                        <img src="${product.imagen1}" alt="${product.producto} 1" class="product-image" />
                                        <img src="${product.imagen2}" alt="${product.producto} 2" class="product-image" />
                                        <img src="${product.imagen3}" alt="${product.producto} 3" class="product-image" />
                                    </div>
                                    <div class="details-column">
                                        <h2>${product.producto}</h2>
                                        <p>${product.descripcion}</p>                                        
                                    </div>
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
        console.error('Error al cargar los datos de Google Sheets:', error);
    }
}

document.addEventListener('DOMContentLoaded', loadGoogleSheetData);