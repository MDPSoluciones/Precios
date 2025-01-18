// ID de la hoja de Google Sheets
const sheetID = '1F8TibB9l16OybjfHvVJI3rbCQc6PXI8rwHvlqpT1c38'; // Reemplaza con tu propio ID
const sheetURL = `https://spreadsheets.google.com/feeds/list/${sheetID}/1/public/values?alt=json`;

// Función para cargar y procesar los datos desde Google Sheets
async function loadGoogleSheetData() {
    try {
        const response = await fetch(sheetURL);
        const data = await response.json();

        // Procesar los datos
        const entries = data.feed.entry; // Datos de la hoja
        const pricingContainer = document.getElementById('pricing');
        pricingContainer.innerHTML = ''; // Limpiar contenido previo

        entries.forEach((entry, index) => {
            const producto = entry['gsx$producto']['$t'];
            const descripcion = entry['gsx$descripción']['$t'];
            const precioUSD = entry['gsx$preciousd']['$t'];
            const precioPesos = entry['gsx$preciopesos']['$t'];

            // Crear un contenedor para las imágenes
            let imagesHTML = `<div class="image-gallery" id="gallery-${index}">`;
            for (let i = 1; i <= 3; i++) {
                const imagen = entry[`gsx$imagen${i}`]?.['$t'];
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

// Cargar los datos de Google Sheets al cargar la página
document.addEventListener('DOMContentLoaded', loadGoogleSheetData);
