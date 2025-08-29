$(document).ready(function() {
    let rowCounter = 1;

    // Función de validación para los campos requeridos
    function validateRequiredFields() {
        let isValid = true;
        
        // Array de IDs de los campos requeridos
        const requiredFields = ['#empresa', '#mejecutivo', '#nombreCliente', '#mailcliente'];

        requiredFields.forEach(function(field) {
            if ($(field).val().trim() === '') {
                isValid = false;
                $(field).addClass('is-invalid'); // Agrega una clase para estilos de error
            } else {
                $(field).removeClass('is-invalid'); // Remueve la clase si el campo es válido
            }
        });

        // **NUEVA LÍNEA: Validación para que el campo validez no sea menor a 1
        const validez = parseFloat($('#validez').val());
        if (isNaN(validez) || validez < 1) {
            isValid = false;
            $('#validez').addClass('is-invalid');
        } else {
            $('#validez').removeClass('is-invalid');
        }
        
        // También valida la tabla de ítems
        let hasItems = false;
        $('.item-row').each(function() {
            if ($(this).find('.item-name').val().trim() !== '') {
                hasItems = true;
            }
        });
        
        if (!hasItems) {
            isValid = false;
        }

        return isValid;
    }
    
    // Función para calcular el total de una fila
    function calculateRowTotal(row) {
        const qty = parseFloat(row.find('.item-qty').val()) || 0;
        const price = parseFloat(row.find('.item-price').val()) || 0;
        const total = qty * price;
        row.find('.item-total').val(total.toFixed(2));
        calculateGrandTotal();
    }

    // Función para calcular el subtotal y el total final
    function calculateGrandTotal() {
        let subtotal = 0;
        $('.item-row').each(function() {
            const total = parseFloat($(this).find('.item-total').val()) || 0;
            subtotal += total;
        });
        $('#subtotal').val(subtotal.toFixed(2));

        const taxRate = parseFloat($('#tax').val()) || 0;
        const taxAmount = subtotal * (taxRate / 100);
        const finalTotal = subtotal + taxAmount;
        $('#finalTotal').val(finalTotal.toFixed(2));
    }

    // Añadir nueva fila
    $('#add_row').on('click', function() {
        rowCounter++;
        const newRow = `
            <tr class="item-row" data-id="${rowCounter}">
                <td><input type="text" class="form-control item-name" placeholder="Ítem"></td>
                <td><input type="number" class="form-control item-qty" placeholder="Cant." min="0"></td>
                <td><input type="number" class="form-control item-price" placeholder="Precio" min="0"></td>
                <td><input type="number" class="form-control item-total" placeholder="Total" readonly></td>
                <td class="text-center" style="padding: 14px;"><a href="#" class="delete-row"><i class="bi bi-x-circle"></i></a></td>
            </tr>
        `;
        $('#items_table_body').append(newRow);
    });

    // Eliminar fila
    $(document).on('click', '.delete-row', function(e) {
        e.preventDefault();
        $(this).closest('.item-row').remove();
        calculateGrandTotal();
    });

    // Eventos para recalcular totales al cambiar los valores
    $(document).on('input', '.item-qty, .item-price', function() {
        const row = $(this).closest('.item-row');
        calculateRowTotal(row);
    });

    $(document).on('input', '#tax', function() {
        calculateGrandTotal();
    });

    // Manejar el clic en el botón de descarga
    $('#downloadPdf').on('click', function() {
        // Ejecutar la validación antes de generar el PDF
        if (!validateRequiredFields()) {
            alert('Por favor, completa todos los campos obligatorios y asegúrate de que la validez sea de al menos 1 día.');
            return;
        }

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'pt', 'a4');
        const source = document.getElementById('contentToPrint');
        const filename = `Presupuesto_${$('#nombreCliente').val().replace(/ /g, '_') || 'Nuevo'}.pdf`;

        // Oculta el botón de descarga
        const downloadBtn = $(this);
        downloadBtn.hide();

        // Oculta el botón de añadir fila
        const addRowBtn = $('#add_row');
        addRowBtn.hide();

        // Oculta los botones de eliminar fila en cada fila (el icono de la cruz)
        $('.delete-row').closest('td').hide();

        // Elimina los bordes de los campos de texto de los items
        $('.item-row input[type="text"], .item-row input[type="number"]').css('border', 'none');

        // Para ocultar las líneas de los campos fijos
        $('.card input').css('border', 'none');
        $('.card select').css('border', 'none');


        html2canvas(source, {
            scale: 2, // Aumenta la resolución
            useCORS: true,
            logging: false,
            onclone: (document) => {
                const clonedDownloadBtn = document.getElementById('downloadPdf');
                if (clonedDownloadBtn) clonedDownloadBtn.style.display = 'none';

                // Oculta el botón de añadir fila en la copia clonada
                const clonedAddRowBtn = document.getElementById('add_row');
                if (clonedAddRowBtn) clonedAddRowBtn.style.display = 'none';

                // Oculta también los botones de eliminar fila en el clon
                const clonedDeleteButtons = document.querySelectorAll('.delete-row');
                clonedDeleteButtons.forEach(btn => {
                    btn.closest('td').style.display = 'none';
                });
                // Elimina los bordes en la copia clonada
                const clonedInputs = document.querySelectorAll('.item-row input[type="text"], .item-row input[type="number"]');
                clonedInputs.forEach(input => {
                    input.style.border = 'none';
                });
                 // Elimina los bordes de los campos fijos en el clon
                const fixedInputs = document.querySelectorAll('.card input, .card select');
                fixedInputs.forEach(input => {
                    input.style.border = 'none';
                });
            }
        }).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const imgWidth = doc.internal.pageSize.getWidth();
            const pageHeight = doc.internal.pageSize.getHeight();
            const imgHeight = canvas.height * imgWidth / canvas.width;
            let heightLeft = imgHeight;
            let position = 0;
            
            doc.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;
            
            while (heightLeft >= 0) {
                position = heightLeft - imgHeight;
                doc.addPage();
                doc.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
            }
            
            doc.save(filename);

            // Vuelve a mostrar el botón de descarga
            downloadBtn.show();
            // Vuelve a mostrar el botón de añadir fila
            addRowBtn.show();
            // Vuelve a mostrar los botones de eliminar fila
            $('.delete-row').closest('td').show();
            // Restaura los bordes de los campos de texto
            $('.item-row input[type="text"], .item-row input[type="number"]').css('border', '');
            // Restaura los bordes de los campos fijos
            $('.card input').css('border', '');
            $('.card select').css('border', '');
        });
    });
});