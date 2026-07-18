let freeSaleItemIndex = 0;

const configuredSubtypes =
    window.FreeSaleConfig &&
    Array.isArray(window.FreeSaleConfig.subtypes)
        ? window.FreeSaleConfig.subtypes
        : [];

const freeSaleSubtypeOptions = configuredSubtypes.map(function (subtype) {
    return {
        value: String(subtype.value),
        text: String(subtype.text),
        isDeferred: String(subtype.isDeferred)
    };
});

const freeSaleChangeSubtypeOptions = configuredSubtypes.map(function (subtype) {
    return {
        value: String(subtype.value),
        text: String(subtype.text),
        isDeferred: String(subtype.isDeferred)
    };
});

$(document).ready(function () {
    $('.select2').select2({
        theme: 'bootstrap4'
    });

    actualizarSubtiposVentaLibre();
    calcularVentaLibre();
    actualizarModoCliente();
    agregarFilaVentaLibre();

    $('[data-bootstrap-switch]').each(function () {
        $(this).bootstrapSwitch();
    });

    $('#btnAddFreeSaleItem').on('click', function () {
        agregarFilaVentaLibre();
    });

    $(document).on('input', '.free-sale-quantity, .free-sale-unit-price', function () {
            calcularVentaLibre();
        }
    );

    $(document).on('click', '.btn-remove-free-sale-item', function () {
            $(this).closest('tr').remove();

            if ($('#freeSaleItemsBody tr').length === 0) {
                agregarFilaVentaLibre();
            }

            calcularVentaLibre();
        }
    );

    $('#client_mode').on('change', function () {
        actualizarModoCliente();
    });

    $('#customer_id').on('change', function () {
        cargarDatosClienteRegistrado();
    });

    $('#discount_percentage, #tax_percentage, #amount_received').on('input',function () {
            calcularVentaLibre();
        }
    );

    $('#currency').on('change', function () {
        calcularVentaLibre();
    });

    $('#pv_cash_box_id').on('change', function () {
        actualizarSubtiposVentaLibre();
        actualizarCamposEfectivo();
        calcularVentaLibre();
    });

    $('#change_cash_box_id').on('change', function () {
        actualizarSubtiposVuelto();
    });

    $('#change_cash_box_subtype_id').on('change', function () {
        actualizarAvisoSubtipoVuelto();
    });

    $('#pv_cash_box_subtype_id').on('change', function () {
        actualizarAvisoSubtipoDiferido();
    });

    $('#pagos_parciales_venta').on('switchChange.bootstrapSwitch',function (event, state) {
            actualizarModoPagoVentaLibre(state);}
    );

    $('#formFreeSale').on('submit', function (event) {
        if (!validarVentaLibre()) {
            event.preventDefault();
            return;
        }

        /*
         * Se habilita para que el valor sea incluido en el POST.
         */
        $('#tipo_documento_cliente').prop('disabled', false);
    });



});

function actualizarCamposEfectivo() {
    const partialPaymentsEnabled =
        $('#pagos_parciales_venta').length > 0 &&
        $('#pagos_parciales_venta').bootstrapSwitch('state');

    if (partialPaymentsEnabled) {
        limpiarDatosVuelto();
        $('#wrapAmountReceived').hide();
        $('#wrapChange').hide();
        $('#wrapChangePayment').hide();
        return;
    }

    const $selectedCashBox =
        $('#pv_cash_box_id option:selected');

    const cashBoxType = String(
        $selectedCashBox.data('type') || ''
    );

    const isCash = cashBoxType === 'cash';

    if (isCash) {
        $('#wrapAmountReceived').show();
        $('#wrapChange').show();
        return;
    }

    limpiarDatosVuelto();

    $('#wrapAmountReceived').hide();
    $('#wrapChange').hide();
    $('#wrapChangePayment').hide();
}

function actualizarModoCliente() {
    const clientMode = $('#client_mode').val();
    const isRegistered = clientMode === 'registered';

    limpiarErrorCliente();

    if (isRegistered) {
        $('#wrap_registered_customer').show();

        $('#customer_id')
            .prop('disabled', false)
            .val('')
            .trigger('change.select2');

        bloquearDatosCliente(true);
        limpiarDatosCliente();

        return;
    }

    $('#wrap_registered_customer').hide();

    $('#customer_id')
        .val('')
        .prop('disabled', true)
        .trigger('change.select2');

    limpiarDatosCliente();
    bloquearDatosCliente(false);
}

function cargarDatosClienteRegistrado() {
    const $selectedCustomer = $('#customer_id option:selected');
    const customerId = $('#customer_id').val();

    limpiarErrorCliente();

    if (!customerId) {
        limpiarDatosCliente();
        bloquearDatosCliente(true);
        return;
    }

    const businessName =
        String($selectedCustomer.data('business-name') || '');

    const ruc =
        String($selectedCustomer.data('ruc') || '');

    const address =
        String($selectedCustomer.data('address') || '');

    const location =
        String($selectedCustomer.data('location') || '');

    let fullAddress = address;

    if (location) {
        fullAddress += fullAddress
            ? ' - ' + location
            : location;
    }

    $('#nombre_cliente').val(businessName);

    $('#tipo_documento_cliente')
        .val(ruc ? '6' : '')
        .trigger('change.select2');

    $('#numero_documento_cliente').val(ruc);
    $('#direccion_cliente').val(fullAddress);

    bloquearDatosCliente(true);
}

function bloquearDatosCliente(blocked) {
    $('#nombre_cliente')
        .prop('readonly', blocked);

    $('#numero_documento_cliente')
        .prop('readonly', blocked);

    $('#direccion_cliente')
        .prop('readonly', blocked);

    /*
     * Select2 no maneja readonly.
     * Lo deshabilitamos visualmente, pero antes de enviar
     * habilitaremos temporalmente el campo.
     */
    $('#tipo_documento_cliente')
        .prop('disabled', blocked)
        .trigger('change.select2');

    /*
     * Customer no posee email ni teléfono en los campos mostrados.
     * Por eso estos dos permanecen editables.
     */
    $('#email_cliente')
        .prop('readonly', false);

    $('#telefono_cliente')
        .prop('readonly', false);
}

function limpiarDatosCliente() {
    $('#nombre_cliente').val('');
    $('#numero_documento_cliente').val('');
    $('#direccion_cliente').val('');
    $('#email_cliente').val('');
    $('#telefono_cliente').val('');

    $('#tipo_documento_cliente')
        .prop('disabled', false)
        .val('')
        .trigger('change.select2');
}

function limpiarErrorCliente() {
    $('#customer_id').removeClass('is-invalid');
    $('#customer_id_error').text('');
}


function agregarFilaVentaLibre() {
    const index = freeSaleItemIndex++;

    const row = `
            <tr>
                <td>
                    <input
                        type="text"
                        name="items[${index}][description]"
                        class="form-control free-sale-description"
                        maxlength="250"
                        placeholder="Descripción del producto o servicio"
                    >
                </td>

                <td>
                    <input
                        type="number"
                        name="items[${index}][quantity]"
                        class="form-control free-sale-quantity"
                        min="0.01"
                        step="0.01"
                        value="1"
                    >
                </td>

                <td>
                    <input
                        type="number"
                        name="items[${index}][unit_price]"
                        class="form-control free-sale-unit-price"
                        min="0"
                        step="0.01"
                        value=""
                        placeholder="0.00"
                    >
                </td>

                <td class="text-right">
                    <span class="item-total-display">
                        S/ 0.00
                    </span>

                    <input
                        type="hidden"
                        name="items[${index}][total]"
                        class="free-sale-item-total"
                        value="0.00"
                    >
                </td>

                <td class="text-center">
                    <button
                        type="button"
                        class="btn btn-danger btn-sm btn-remove-free-sale-item"
                        title="Eliminar"
                    >
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;

    $('#freeSaleItemsBody').append(row);
}

function calcularVentaLibre() {
    let subtotal = 0;

    $('#freeSaleItemsBody tr').each(function () {
        const $row = $(this);

        const quantity = parseFloat(
            $row.find('.free-sale-quantity').val()
        ) || 0;

        const unitPrice = parseFloat(
            $row.find('.free-sale-unit-price').val()
        ) || 0;

        const itemTotal = quantity * unitPrice;

        $row.find('.free-sale-item-total').val(
            itemTotal.toFixed(2)
        );

        $row.find('.item-total-display').text(
            formatoMoneda(itemTotal)
        );

        subtotal += itemTotal;
    });

    let discountPercentage = parseFloat(
        $('#discount_percentage').val()
    ) || 0;

    let taxPercentage = parseFloat(
        $('#tax_percentage').val()
    ) || 0;

    discountPercentage = Math.max(
        0,
        Math.min(discountPercentage, 100)
    );

    taxPercentage = Math.max(
        0,
        Math.min(taxPercentage, 100)
    );

    const discountAmount =
        subtotal * discountPercentage / 100;

    const taxableAmount =
        Math.max(subtotal - discountAmount, 0);

    const taxAmount =
        taxableAmount * taxPercentage / 100;

    const totalAmount =
        taxableAmount + taxAmount;

    const $selectedCashBox =
        $('#pv_cash_box_id option:selected');

    const cashBoxType = String(
        $selectedCashBox.data('type') || ''
    );

    const isCashPayment = cashBoxType === 'cash';

    const amountReceived = isCashPayment
        ? parseFloat($('#amount_received').val()) || 0
        : 0;

    const changeAmount = isCashPayment
        ? Math.max(amountReceived - totalAmount, 0)
        : 0;

    $('#subtotal').val(subtotal.toFixed(2));
    $('#total_discount').val(discountAmount.toFixed(2));
    $('#tax_amount').val(taxAmount.toFixed(2));
    $('#total_amount').val(totalAmount.toFixed(2));
    $('#change_amount').val(changeAmount.toFixed(2));

    $('#subtotalView').text(
        formatoMoneda(subtotal)
    );

    $('#discountView').text(
        '- ' + formatoMoneda(discountAmount)
    );

    $('#taxView').text(
        formatoMoneda(taxAmount)
    );

    $('#totalView').text(
        formatoMoneda(totalAmount)
    );

    $('#changeView').text(
        formatoMoneda(changeAmount)
    );

    actualizarSeccionVuelto(changeAmount, isCashPayment);
}

function actualizarSubtiposVuelto() {
    const $cashBox = $('#change_cash_box_id');

    const $selectedOption =
        $cashBox.find('option:selected');

    const type = String(
        $selectedOption.data('type') || ''
    );

    const usesSubtypes =
        String(
            $selectedOption.data('uses-subtypes')
        ) === '1';

    const $subtype =
        $('#change_cash_box_subtype_id');

    $subtype.empty().append(
        $('<option>', {
            value: '',
            text: 'Seleccione subtipo...'
        })
    );

    $('#change_subtype_hint').hide();

    if (type === 'bank' && usesSubtypes) {
        freeSaleChangeSubtypeOptions.forEach(function (option) {
            const $newOption = $('<option>', {
                value: option.value,
                text: option.text
            });

            $newOption.attr(
                'data-is_deferred',
                option.isDeferred
            );

            $subtype.append($newOption);
        });

        $('#wrap_change_subtype').show();

        $subtype
            .prop('disabled', false)
            .val('')
            .trigger('change.select2');

        return;
    }

    $('#wrap_change_subtype').hide();

    $subtype
        .prop('disabled', true)
        .val('')
        .trigger('change.select2');
}

function actualizarAvisoSubtipoVuelto() {
    const $selectedOption =
        $('#change_cash_box_subtype_id option:selected');

    const isDeferred =
        String(
            $selectedOption.attr('data-is_deferred') || '0'
        ) === '1';

    $('#change_subtype_hint').toggle(isDeferred);
}

function limpiarDatosVuelto() {
    $('#amount_received').val('');

    $('#change_amount').val('0.00');
    $('#changeView').text(formatoMoneda(0));

    limpiarFormaEntregaVuelto();

    $('#amount_received').removeClass('is-invalid');
    $('#amount_received_error').text('');
}

function limpiarFormaEntregaVuelto() {
    $('#change_cash_box_id')
        .val('')
        .trigger('change.select2');

    $('#change_cash_box_subtype_id')
        .empty()
        .append(
            $('<option>', {
                value: '',
                text: 'Seleccione subtipo...'
            })
        )
        .val('')
        .prop('disabled', true)
        .trigger('change.select2');

    $('#wrap_change_subtype').hide();
    $('#change_subtype_hint').hide();

    $('#change_cash_box_id')
        .removeClass('is-invalid');

    $('#change_cash_box_subtype_id')
        .removeClass('is-invalid');

    $('#change_cash_box_id_error').text('');
    $('#change_cash_box_subtype_id_error').text('');
}

function actualizarSeccionVuelto(
    changeAmount,
    isCashPayment
) {
    const hasChange =
        isCashPayment &&
        Number(changeAmount) > 0;

    if (hasChange) {
        $('#wrapChangePayment').show();
        return;
    }

    $('#wrapChangePayment').hide();

    limpiarFormaEntregaVuelto();
}

function formatoMoneda(amount) {
    const currency = $('#currency').val();
    const symbol = currency === 'USD' ? '$' : 'S/';

    return symbol + ' ' + Number(amount || 0).toFixed(2);
}

function actualizarSubtiposVentaLibre() {
    const $cashBox = $('#pv_cash_box_id');
    const $selectedOption = $cashBox.find('option:selected');

    const type = String(
        $selectedOption.data('type') || ''
    );

    const usesSubtypes =
        String(
            $selectedOption.data('uses-subtypes')
        ) === '1';

    const $subtype = $('#pv_cash_box_subtype_id');

    $subtype.empty().append(
        $('<option>', {
            value: '',
            text: 'Seleccione subtipo...'
        })
    );

    $('#pv_subtype_hint').hide();

    if (type === 'bank' && usesSubtypes) {
        freeSaleSubtypeOptions.forEach(function (option) {
            const $newOption = $('<option>', {
                value: option.value,
                text: option.text
            });

            $newOption.attr(
                'data-is_deferred',
                option.isDeferred
            );

            $subtype.append($newOption);
        });

        $('#wrap_pv_subtype').show();

        $subtype
            .prop('disabled', false)
            .val('')
            .trigger('change.select2');

        return;
    }

    $('#wrap_pv_subtype').hide();

    $subtype
        .prop('disabled', true)
        .val('')
        .trigger('change.select2');
}

function actualizarAvisoSubtipoDiferido() {
    const $selectedOption =
        $('#pv_cash_box_subtype_id option:selected');

    const isDeferred =
        String(
            $selectedOption.attr('data-is_deferred') || '0'
        ) === '1';

    $('#pv_subtype_hint').toggle(isDeferred);
}

function actualizarModoPagoVentaLibre(
    pagosParcialesActivo
) {
    if (pagosParcialesActivo) {
        limpiarDatosPagoNormalVentaLibre();
        limpiarDatosVuelto();

        $('#wrap_pago_normal').hide();
        $('#wrapAmountReceived').hide();
        $('#wrapChange').hide();
        $('#wrapChangePayment').hide();

        return;
    }

    $('#wrap_pago_normal').show();

    actualizarCamposEfectivo();
    calcularVentaLibre();
}

function limpiarDatosPagoNormalVentaLibre() {
    $('#pv_cash_box_id')
        .val('')
        .trigger('change.select2');

    $('#pv_cash_box_subtype_id')
        .empty()
        .append(
            $('<option>', {
                value: '',
                text: 'Seleccione subtipo...'
            })
        )
        .val('')
        .prop('disabled', true)
        .trigger('change.select2');

    $('#wrap_pv_subtype').hide();
    $('#pv_subtype_hint').hide();

    $('#pv_cash_box_id').removeClass('is-invalid');
    $('#pv_cash_box_subtype_id').removeClass('is-invalid');

    $('#cash_box_id_error').text('');
    $('#cash_box_subtype_id_error').text('');
}

function validarVentaLibre() {
    limpiarErroresVentaLibre();

    let isValid = true;
    let validItems = 0;

    /*
     * 1. VALIDAR CLIENTE REGISTRADO
     */
    const clientMode = $('#client_mode').val();

    if (
        clientMode === 'registered' &&
        !$('#customer_id').val()
    ) {
        $('#customer_id').addClass('is-invalid');

        $('#customer_id_error').text(
            'Seleccione un cliente registrado.'
        );

        isValid = false;
    }

    /*
     * 2. VALIDAR DETALLE DE LA VENTA
     */
    $('#freeSaleItemsBody tr').each(function () {
        const $row = $(this);

        const description = $.trim(
            $row.find('.free-sale-description').val()
        );

        const quantity = parseFloat(
            $row.find('.free-sale-quantity').val()
        ) || 0;

        const unitPrice = parseFloat(
            $row.find('.free-sale-unit-price').val()
        ) || 0;

        if (
            description !== '' &&
            quantity > 0 &&
            unitPrice >= 0
        ) {
            validItems++;
            return;
        }

        $row.find(
            '.free-sale-description, ' +
            '.free-sale-quantity, ' +
            '.free-sale-unit-price'
        ).addClass('is-invalid');

        isValid = false;
    });

    if (validItems === 0) {
        $.alert({
            title: 'Detalle incompleto',
            content: 'Debe registrar por lo menos un producto o servicio válido.',
            type: 'orange',
            buttons: {
                ok: {
                    text: 'Aceptar',
                    btnClass: 'btn-warning'
                }
            }
        });

        return false;
    }

    /*
     * 3. SABER SI LA VENTA USA PAGOS PARCIALES
     */
    const partialPaymentsEnabled =
        $('#pagos_parciales_venta').length > 0 &&
        $('#pagos_parciales_venta').bootstrapSwitch('state');

    /*
     * Si no usa pagos parciales, se debe validar
     * la forma de pago normal.
     */
    if (!partialPaymentsEnabled) {
        const cashBoxId = $('#pv_cash_box_id').val();

        const $selectedCashBox =
            $('#pv_cash_box_id option:selected');

        const cashBoxType = String(
            $selectedCashBox.data('type') || ''
        );

        const cashBoxUsesSubtypes =
            String(
                $selectedCashBox.data('uses-subtypes')
            ) === '1';

        /*
         * 4. VALIDAR CAJA PRINCIPAL
         */
        if (!cashBoxId) {
            $('#pv_cash_box_id').addClass('is-invalid');

            $('#cash_box_id_error').text(
                'Seleccione una caja.'
            );

            isValid = false;
        }

        /*
         * 5. VALIDAR SUBTIPO DE LA CAJA PRINCIPAL
         */
        if (
            cashBoxId &&
            cashBoxType === 'bank' &&
            cashBoxUsesSubtypes &&
            !$('#pv_cash_box_subtype_id').val()
        ) {
            $('#pv_cash_box_subtype_id')
                .addClass('is-invalid');

            $('#cash_box_subtype_id_error').text(
                'Seleccione un subtipo bancario.'
            );

            isValid = false;
        }

        /*
         * 6. VALIDAR MONTO RECIBIDO
         * Solo cuando la caja principal es efectivo.
         */
        if (
            cashBoxId &&
            cashBoxType === 'cash'
        ) {
            const totalAmount =
                parseFloat($('#total_amount').val()) || 0;

            const amountReceived =
                parseFloat($('#amount_received').val()) || 0;

            const changeAmount =
                parseFloat($('#change_amount').val()) || 0;

            if (
                $('#amount_received').val() === '' ||
                amountReceived < totalAmount
            ) {
                $('#amount_received')
                    .addClass('is-invalid');

                $('#amount_received_error').text(
                    'El monto recibido debe ser igual o mayor al total de la venta.'
                );

                isValid = false;
            }

            /*
             * 7. VALIDAR FORMA DE ENTREGA DEL VUELTO
             * Solo cuando realmente existe vuelto.
             */
            if (changeAmount > 0) {
                const changeCashBoxId =
                    $('#change_cash_box_id').val();

                const $selectedChangeCashBox =
                    $('#change_cash_box_id option:selected');

                const changeCashBoxType = String(
                    $selectedChangeCashBox.data('type') || ''
                );

                const changeCashBoxUsesSubtypes =
                    String(
                        $selectedChangeCashBox.data(
                            'uses-subtypes'
                        )
                    ) === '1';

                /*
                 * Caja desde donde se entregará el vuelto.
                 */
                if (!changeCashBoxId) {
                    $('#change_cash_box_id')
                        .addClass('is-invalid');

                    $('#change_cash_box_id_error').text(
                        'Seleccione la caja desde la que se entregará el vuelto.'
                    );

                    isValid = false;
                }

                /*
                 * Subtipo del vuelto cuando la caja seleccionada
                 * es bancaria y utiliza subtipos.
                 */
                if (
                    changeCashBoxId &&
                    changeCashBoxType === 'bank' &&
                    changeCashBoxUsesSubtypes &&
                    !$('#change_cash_box_subtype_id').val()
                ) {
                    $('#change_cash_box_subtype_id')
                        .addClass('is-invalid');

                    $('#change_cash_box_subtype_id_error').text(
                        'Seleccione el subtipo para la entrega del vuelto.'
                    );

                    isValid = false;
                }
            }
        }
    }

    /*
     * 8. MENSAJE GENERAL
     */
    if (!isValid) {
        $.alert({
            title: 'Datos incompletos',
            content: 'Revise los campos marcados antes de continuar.',
            type: 'orange',
            buttons: {
                ok: {
                    text: 'Aceptar',
                    btnClass: 'btn-warning'
                }
            }
        });
    }

    return isValid;
}

function limpiarErroresVentaLibre() {
    $('.is-invalid').removeClass('is-invalid');

    $('#customer_id_error').text('');
    $('#cash_box_id_error').text('');
    $('#cash_box_subtype_id_error').text('');

    $('#amount_received_error').text('');
    $('#change_cash_box_id_error').text('');
    $('#change_cash_box_subtype_id_error').text('');
}