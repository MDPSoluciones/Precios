$(document).ready(function() {
    let rowCounter = 1;

    // Función de validación para los campos requeridos
    function validateRequiredFields() {
        let isValid = true;
        
        // Array de IDs de los campos requeridos
        const requiredFields = ['#empresa', '#mejecutivo', '#nombreCliente']; //, '#mailcliente'];

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

        // Llamar a la ventana de impresión del navegador
        window.print();
    });
});