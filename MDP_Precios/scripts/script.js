// Ruta al archivo Excel
const filePath = 'data/productos.xlsx';

// Función para cargar y procesar el archivo Excel
async function loadExcelFile() {
    try {
        const response = await fetch(filePath);
        const arrayBuffer = await response.arrayBuffer();
        const data = new Uint8Array(arrayBuffer);
        const workbook = XLSX.read(data, { type: 'array' });
        const sheetName = workbook.SheetNames[0]; // Selecciona la primera hoja
        const sheet = workbook.Sheets[sheetName];
        const jsonData = XLSX.utils.sheet_to_json(sheet); // Convierte la hoja a JSON

        // Generar la lista de precios
        const pricingContainer = document.getElementById('pricing');
        pricingContainer.innerHTML = ''; // Limpia cualquier contenido previo

        jsonData.forEach((row, index) => {
            const item = document.createElement('div');
            item.classList.add('pricing-item');

            // Crear un contenedor para las imágenes
            let imagesHTML = `<div class="image-gallery" id="gallery-${index}">`;
            for (let key in row) {
                if (key.startsWith('Imagen') && row[key]) {
                    imagesHTML += `
                        <img src="${row[key]}" alt="${row.Producto}" class="product-image hidden" />
                    `;
                }
            }
            imagesHTML += `
                <button class="prev-btn" onclick="changeImage(${index}, -1)">&#10094;</button>
                <button class="next-btn" onclick="changeImage(${index}, 1)">&#10095;</button>
            </div>`;

            // Generar el contenido del producto
            item.innerHTML = `
                ${imagesHTML}
                <h2>${row.Producto}</h2>
                <p>${row.Descripción}</p>
                <div class="prices">
                    <span class="price usd">USD: $${parseFloat(row.PrecioUSD).toFixed(2)}</span>
                    <span class="price pesos">Pesos: $${parseFloat(row.PrecioPesos).toFixed(2)}</span>
                </div>
            `;
            pricingContainer.appendChild(item);

            // Mostrar la primera imagen al inicio
            const gallery = document.querySelector(`#gallery-${index}`);
            gallery.querySelector('.product-image').classList.remove('hidden');
        });
    } catch (error) {
        console.error('Error al cargar el archivo Excel:', error);
    }
}

// Función para cambiar la imagen en la galería
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

// Cargar el archivo Excel al iniciar la página
document.addEventListener('DOMContentLoaded', loadExcelFile);
