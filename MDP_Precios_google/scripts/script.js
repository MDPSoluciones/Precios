
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
        console.log(data);

        if (!data.values || data.values.length < 2) {
            console.error('Datos insuficientes o mal estructurados en la hoja.');
            return;
        }

        const rows = data.values;
        const headers = rows[0];
        const pricingContainer = document.getElementById('pricing');
        pricingContainer.innerHTML = '';

        rows.slice(1).forEach((row, index) => {
            const producto = row[headers.indexOf('Producto')] || 'Producto no definido';
            const descripcion = row[headers.indexOf('Descripción')] || 'Descripción no disponible';
            const precioUSD = row[headers.indexOf('PrecioUSD')] || '0';
            const precioPesos = row[headers.indexOf('PrecioPesos')] || '0';

            let imagesHTML = '';
            for (let i = 1; i <= 3; i++) {
                const imagenIndex = headers.indexOf(`Imagen${i}`);
                const imagen = imagenIndex !== -1 ? row[imagenIndex] : null;
                if (imagen) {
                    imagesHTML += `<img src="${imagen}" alt="${producto}" class="product-image" />`;
                    break; // Solo usamos la primera imagen para esta disposición
                }
            }

            const item = document.createElement('div');
            item.classList.add('pricing-item');
            item.innerHTML = `
                <div class="product-row">
                    <div class="image-column">
                        ${imagesHTML}
                    </div>
                    <div class="details-column">
                        <h2>${producto}</h2>
                        <p>${descripcion}</p>
                    </div>
                    <div class="price-column">
                        <div class="prices">
                            <span class="price usd">USD: $${parseFloat(precioUSD).toFixed(2)}</span>
                            <span class="price pesos">Pesos: $${parseFloat(precioPesos).toFixed(2)}</span>
                        </div>
                    </div>
                </div>
            `;
            pricingContainer.appendChild(item);
        });
    } catch (error) {
        console.error('Error al cargar los datos de Google Sheets:', error);
    }
}