// ID de la hoja de Google Sheets y clave de API
const sheetID = '1F8TibB9l16OybjfHvVJI3rbCQc6PXI8rwHvlqpT1c38'; // Reemplaza con tu ID de hoja
const apiKey = 'AIzaSyAtl_Qk6w93Dk-7SeUfMGEW9DsEAmyDzpk'; // Reemplaza con tu clave de API
const sheetRange = 'Hoja1'; // Nombre de la hoja o rango específico

// URL de la API de Google Sheets
const sheetURL = `https://sheets.googleapis.com/v4/spreadsheets/${sheetID}/values/${sheetRange}?key=${apiKey}`;

// Función para cargar los datos desde Google Sheets
async function loadGoogleSheetData() {
    try {
        const response = await fetch(sheetURL);
        const data = await response.json();
        console.log(data); // Ver los datos devueltos por la API

        if (!data.values || data.values.length < 2) {
            console.error('Datos insuficientes o mal estructurados en la hoja.');
            return;
        }

        const rows = data.values; // Filas de la hoja
        const headers = rows[0]; // Encabezados
        const pricingContainer = document.getElementById('pricing');
        pricingContainer.innerHTML = ''; // Limpia el contenido previo

        rows.slice(1).forEach((row, index) => {
            // Validar si las columnas necesarias existen
            const producto = row[headers.indexOf('Producto')] || 'Producto no definido';
            const descripcion = row[headers.indexOf('Descripción')] || 'Descripción no disponible';
            const precioUSD = row[headers.indexOf('PrecioUSD')] || '0';
            const precioPesos = row[headers.indexOf('PrecioPesos')] || '0';

            // Crear un contenedor para las imágenes
            let imagesHTML = `<div class="image-gallery" id="gallery-${index}">`;
            for (let i = 1; i <= 3; i++) {
                const imagenIndex = headers.indexOf(`Imagen${i}`);
                const imagen = imagenIndex !== -1 ? row[imagenIndex] : null;
                if (imagen) {
                    imagesHTML += `
                        <img src="${imagen}" alt="${producto}" class="product-image hidden" />
                    `;
                }
            }
            imagesHTML += `
                <button class="prev-btn" onclick="changeImage(${index}, -1)">&#10094;</button>
                <button class="next-btn" onclick="changeImage(${index}, 1)">&#10095;</button>
            </div>`;

            // Crear el contenido del producto
            const item = document.createElement('div');
            item.classList.add('pricing-item');
            item.innerHTML = `
                ${imagesHTML}
                <h2>${producto}</h2>
                <p>${descripcion}</p>
                <div class="prices">
                    <span class="price usd">USD: $${parseFloat(precioUSD).toFixed(2)}</span>
                    <span class="price pesos">Pesos: $${parseFloat(precioPesos).toFixed(2)}</span>
                </div>
            `;
            pricingContainer.appendChild(item);

            // Mostrar la primera imagen
            const gallery = document.querySelector(`#gallery-${index}`);
            if (gallery) {
                gallery.querySelector('.product-image').classList.remove('hidden');
            }
        });
    } catch (error) {
        console.error('Error al cargar los datos de Google Sheets:', error);
    }
}

// Función para cambiar las imágenes
function changeImage(galleryIndex, direction) {
    const gallery = document.querySelector(`#gallery-${galleryIndex}`);
    const images = gallery.querySelectorAll('.product-image');
    let currentIndex = Array.from(images).findIndex(img => !img.classList.contains('hidden'));

    // Ocultar la imagen actual
    images[currentIndex].classList.add('hidden');

    // Calcular el índice de la siguiente imagen
    currentIndex = (currentIndex + direction + images.length) % images.length;

    // Mostrar la nueva imagen
    images[currentIndex].classList.remove('hidden');
}

// Cargar los datos al iniciar la página
document.addEventListener('DOMContentLoaded', loadGoogleSheetData);
