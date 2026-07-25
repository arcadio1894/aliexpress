let $currentStockItemRow = null;

$(document).ready(function () {

    $(document).on('input', '#dni, #ruc', function () {
        this.value = this.value.replace(/\D/g, '');
    });

    setTimeout(function () {
        $('#product_search').focus();
    }, 300);

    $('#product_search').on('keypress', function (e) {
        if (e.which === 13) { // Enter
            e.preventDefault();
            $('#btn_search').click();
        }
    });

    $('#quantity_total').on('keypress', function (e) {
        if (e.which === 13) { // Enter
            e.preventDefault();
            addProduct();
        }
    });

    $('#modalVuelto').on('hidden.bs.modal', function () {
        $('#btn-pay').prop('disabled', false);
    });

    // Detectar cambio en el select de tipo de vista
    $("#type_id").on("change", function() {
        if ($(this).val() === "f") {
            showDataSearchTable();
        } else {
            showDataSearch();
        }
    });

    $(document).on('click', '[data-item]', showData);

    $(document).on('click', '.pagination-btn', function() {
        var page = $(this).data('page');
        getDataTableNew(page);
    });

    getData(1);

    $("#btn_search").on('click', showDataSearch);

    $(document).on('click', '[data-add_cart]', addProductCart);

    $(document).on('click', '[data-add_cart_special]', addProductCartSpecial);

    $(document).on('input', '#importe_total', function() {
        //console.log("Input event detected!"); // Para depuración
        var $input = $(this);
        var currentValue = parseFloat($input.val());
        var importe = $("#monto_total").val();

        if ( currentValue >= importe)
        {
            $("#vuelto").val(parseFloat(currentValue-importe).toFixed(2));
        } else {
            $("#vuelto").val(parseFloat(0).toFixed(2));
        }

    });

    $(document).on('click', '[data-delete]', deleteItem);

    $("#btn-pay").on('click', payNow);

    $("#btn-save").on('click', guardarVenta);

    $("#btn-notSave").on('click', cerrarVuelto);

    $formCreate = $('#formCreate');

    $modalVuelto = $('#modalVuelto');

    $modalQuantity = $('#modalQuantity');

    $(document).on('input', 'input.quantity', function() {
        console.log("Input event detected!"); // Para depuración
        var $input = $(this);
        var currentValue = parseFloat($input.val());
        var stringDiscount = "";
        //var productId = $input.siblings('button.minus').attr('data-product_id_minus');
        var itemKey = $input.siblings('button.minus').attr('data-item_key_minus');

        if (isNaN(currentValue) || currentValue < 0) {
            currentValue = 0;
            $input.val(currentValue.toFixed(2));
        }

        getDiscountMaterial(itemKey, currentValue.toFixed(2)).then(function(discount) {
            console.log(discount);
            if ( discount != -1 )
            {
                $input.closest('.flex-grow-1').find('h6[data-discount]').html(discount.stringDiscount);
            } else  {
                $input.closest('.flex-grow-1').find('h6[data-discount]').html("");
            }

            updateItems(itemKey, priceTotal, currentValue);
            updateTotalOrder();
        });

        var string = changeStringPrice(itemKey, currentValue.toFixed(2));
        var priceTotal = changePriceTotal(itemKey, currentValue.toFixed(2));

        // Actualizar el string
        $input.closest('.flex-grow-1').find('h6[data-price]').html(string);
        // Actualizar el precio total
        $input.closest('.d-flex').find('p[data-priceTotal]').html(priceTotal);
    });

    $("#btn-pay").show();
    $("#btn-newSale").hide();
    $("#btn-printDocument").hide();

    $("#btn-newSale").on('click', newSale);

    $("#btn-notAddProduct").on('click', notAddProduct);
    $("#btn-add_product").on('click', addProduct);

    $('input[name="invoice_type"]').on('change', function() {
        let tipo = $(this).val();

        $('#datos_boleta input, #datos_factura input').val('');

        if (tipo === 'boleta') {
            $('#datos_boleta').removeClass('d-none');
            $('#datos_factura').addClass('d-none');
        } else if (tipo === 'factura') {
            $('#datos_factura').removeClass('d-none');
            $('#datos_boleta').addClass('d-none');
        } else {
            // Ninguno seleccionado
            $('#datos_boleta').addClass('d-none');
            $('#datos_factura').addClass('d-none');

            // Limpiar inputs
            //$('#datos_boleta input, #datos_factura input').val('');
        }

        bloquearDatosComprobante();
    });

    $('#pv_cash_box_id').on('change', function () {
        const $opt = $(this).find('option:selected');
        const type = $opt.data('type'); // cash|bank
        const usesSub = String($opt.data('uses_subtypes')) === '1';

        if (type === 'bank' && usesSub) {
            $('#wrap_pv_subtype').show();
            $('#pv_cash_box_subtype_id').val('').trigger('change');
        } else {
            $('#wrap_pv_subtype').hide();
            $('#pv_cash_box_subtype_id').val('').trigger('change');
            $('#pv_subtype_hint').hide();
        }
    });

    $('#pv_cash_box_subtype_id').on('change', function () {
        const $opt = $(this).find('option:selected');
        const isDeferred = String($opt.data('is_deferred')) === '1';
        if (isDeferred) $('#pv_subtype_hint').show();
        else $('#pv_subtype_hint').hide();
    });

    $(document).on('change', '#pv_vuelto_cash_box_id', function () {
        refreshVueltoSubtypeUI();
    });

    $('#modalPresentaciones').on('hidden.bs.modal', function () {
        unlockCurrentStockItemQuantity();
    });

    $(document).on('click', '.btn-presentacion-stock-item', function () {
        const materialId = $(this).data('material_id');

        if (!materialId) {
            toastr.error('No se encontró el material.');
            return;
        }

        $currentStockItemRow = $(this).closest('tr');

        $('#presentationsArea').html(`
            <div class="text-center text-muted py-3">
                Cargando presentaciones...
            </div>
        `);

        const availableUnits = parseFloat($currentStockItemRow.data('stock')) || 0;

        console.log("availableUnits "+availableUnits);

        $('#modalPresentaciones').modal('show');

        setTimeout(function () {
            $('#modalPresentaciones').css('z-index', 1060);
            $('.modal-backdrop').last().css('z-index', 1055);
        }, 200);

        fetchPresentations(materialId).then(function (presentations) {
            console.log(presentations);
            console.log("availableUnits "+availableUnits);
            renderPresentationsInModal(presentations, availableUnits);
            if (presentations && presentations.length > 0) {
                $currentStockItemRow
                    .find('.input-cantidad-stock-item')
                    .val(0)
                    .prop('readonly', true)
                    .prop('disabled', true)
                    .attr('title', 'La cantidad se define desde presentaciones');
            }
        }).catch(function () {
            $('#presentationsArea').html(`
            <div class="text-danger">
                Error al cargar las presentaciones.
            </div>
        `);
        });
    });

    $(document).on('click', '#btnAgregarPresentaciones', function () {
        if (!$currentStockItemRow) {
            toastr.error('No se encontró la fila del producto.');
            return;
        }

        const materialId = $currentStockItemRow.data('material-id');
        const stockItemId = $currentStockItemRow.data('stock-item-id');

        let selectedPresentations = [];

        $('#presentationsArea [data-pres-row]').each(function () {
            const row = $(this);

            const materialPresentationId = row.data('pres-id');
            const unitsPerPack = parseFloat(row.data('pres-qty')) || 0;
            const price = parseFloat(row.data('pres-price')) || 0;
            const packs = parseInt(row.find('[data-pres-packs]').val()) || 0;

            if (packs > 0) {
                selectedPresentations.push({
                    material_id: materialId,
                    stock_item_id: stockItemId,
                    material_presentation_id: materialPresentationId,
                    packs: packs,
                    unitsPerPack: unitsPerPack,
                    quantity: packs * unitsPerPack,
                    price: price
                });
            }
        });

        if (selectedPresentations.length === 0) {
            toastr.warning('Debe ingresar al menos una presentación.');
            return;
        }

        renderPresentationSelectionInRow($currentStockItemRow, selectedPresentations);

        $currentStockItemRow
            .find('.input-cantidad-stock-item')
            .val(0)
            .prop('readonly', true)
            .prop('disabled', true)
            .attr('title', 'La cantidad se define desde presentaciones');

        $('#modalPresentaciones').modal('hide');
    });

    $(document).on('click', '.btn-remove-presentations', function () {
        const $row = $(this).closest('tr');

        $row.find('.input-cantidad-stock-item')
            .prop('readonly', false)
            .prop('disabled', false)
            .removeAttr('title');

        const materialId = $row.data('material-id');
        const stockItemId = $row.data('stock-item-id');

        const $actionTd = $(this).closest('td');

        $actionTd.html(`
        <button 
            type="button" 
            class="btn btn-outline-primary btn-sm btn-presentacion-stock-item"
            data-material_id="${materialId}"
            data-stock_item_id="${stockItemId}">
            Presentación
        </button>
    `);
    });

    /*$(document).on('click', '#btn-agregar-todos-stock-items', function () {
        let addedCount = 0;
        let hasError = false;

        $('#tbody-stock-items-venta tr').each(function () {
            const $row = $(this);

            const stockItemId = $row.data('stock-item-id');
            const materialId = $row.data('material-id');

            if (!stockItemId || !materialId) {
                return;
            }

            const productId = stockItemId;
            const productName = $row.data('product-name') || $row.find('td:first').text().trim();
            const productUnit = $row.data('product-unit') || 'UND';
            const productTax = parseFloat($row.data('product-tax')) || 0;
            const productType = parseInt($row.data('product-type')) || 0;
            const stockAvailable = parseFloat($row.data('stock')) || 0;
            const unitPrice = parseFloat($row.data('price')) || 0;

            const $presentationInput = $row.find('.input-presentations-selected');
            const hasPresentationsSelected = $presentationInput.length > 0;

            /!**
             * CASO 1: Tiene presentaciones seleccionadas
             *!/
            if (hasPresentationsSelected) {
                let presentations = [];

                try {
                    presentations = JSON.parse($presentationInput.attr('data-presentations') || '[]');
                } catch (e) {
                    toastr.error('Error leyendo las presentaciones seleccionadas.');
                    hasError = true;
                    return false;
                }

                let totalUnits = presentations.reduce(function (sum, r) {
                    return sum + (parseFloat(r.quantity) || 0);
                }, 0);

                if (totalUnits > stockAvailable) {
                    toastr.error(`La cantidad seleccionada para ${productName} supera el stock disponible.`);
                    hasError = true;
                    return false;
                }

                presentations.forEach(function (r) {
                    const presentationId = r.material_presentation_id;
                    const presentationQty = parseFloat(r.unitsPerPack) || 1;
                    const packs = parseFloat(r.packs) || 0;
                    const price = parseFloat(r.price) || 0;

                    if (packs <= 0) {
                        return;
                    }

                    const itemKey = buildItemKey(stockItemId, presentationId);

                    let existing = $items.find(x => x.itemKey === itemKey);

                    if (existing) {
                        toastr.error(`El producto ${productName} (${presentationQty} unidades) ya está agregado. Use + / - para modificar.`, 'Error', {
                            closeButton: true
                        });
                        hasError = true;
                        return;
                    }

                    const total = parseFloat(packs * price).toFixed(2);

                    $items.push({
                        itemKey: itemKey,
                        productId: productId,
                        stockItemId: stockItemId,
                        materialId: materialId,

                        presentationId: presentationId,
                        presentationQty: presentationQty,
                        presentationLabel: `${presentationQty} unidades`,

                        priceEffective: price,
                        productPrice: price,
                        productName: productName,
                        productUnit: productUnit,
                        productTax: productTax,
                        productType: productType,

                        productTotal: total,
                        productTotalTaxes: parseFloat(total * (1 + (productTax / 100))).toFixed(2),
                        productTaxes: parseFloat(total * (productTax / 100)).toFixed(2),

                        productQuantity: packs,
                        unitsEquivalent: packs * presentationQty,
                        productDiscount: 0
                    });

                    renderDataCartRow(itemKey);
                    addedCount++;
                });

                return;
            }

            /!**
             * CASO 2: Sin presentaciones, venta por unidad
             *!/
            const qtyToUse = parseFloat($row.find('.input-cantidad-stock-item').val()) || 0;

            if (qtyToUse <= 0) {
                return;
            }

            if (qtyToUse > stockAvailable) {
                toastr.error(`La cantidad ingresada para ${productName} supera el stock disponible.`);
                hasError = true;
                return false;
            }

            const itemKey = buildItemKey(stockItemId, null);

            let existing = $items.find(x => x.itemKey === itemKey);

            if (existing) {
                toastr.error(`El producto ${productName} (Unidad) ya está agregado. Use + / - para modificar.`, 'Error', {
                    closeButton: true
                });
                hasError = true;
                return false;
            }

            const total = parseFloat(qtyToUse * unitPrice).toFixed(2);

            $items.push({
                itemKey: itemKey,
                productId: productId,
                stockItemId: stockItemId,
                materialId: materialId,

                presentationId: null,
                presentationQty: 1,
                presentationLabel: 'Unidad',

                priceEffective: unitPrice,
                productPrice: unitPrice,
                productName: productName,
                productUnit: productUnit,
                productTax: productTax,
                productType: productType,

                productTotal: total,
                productTotalTaxes: parseFloat(total * (1 + (productTax / 100))).toFixed(2),
                productTaxes: parseFloat(total * (productTax / 100)).toFixed(2),

                productQuantity: qtyToUse,
                unitsEquivalent: qtyToUse,
                productDiscount: 0
            });

            renderDataCartRow(itemKey);
            addedCount++;
        });

        if (hasError) {
            return;
        }

        if (addedCount === 0) {
            toastr.warning('Debe ingresar una cantidad o seleccionar una presentación.');
            return;
        }

        updateTotalOrder();

        $('#modalStockItemsVenta').modal('hide');

        toastr.success('Productos agregados al carrito.');
    });*/
    $(document).on('click', '#btn-agregar-todos-stock-items', function () {

        let button = $(this);

        let hasZeroPrice = false;

        $('#tbody-stock-items-venta tr').each(function () {
            const $row = $(this);

            const stockItemId = $row.data('stock-item-id');
            const materialId = $row.data('material-id');

            if (!stockItemId || !materialId) {
                return;
            }

            const $presentationInput = $row.find('.input-presentations-selected');
            const hasPresentationsSelected = $presentationInput.length > 0;

            if (hasPresentationsSelected) {
                let presentations = [];

                try {
                    presentations = JSON.parse($presentationInput.attr('data-presentations') || '[]');
                } catch (e) {
                    return;
                }

                presentations.forEach(function (r) {
                    const packs = parseFloat(r.packs) || 0;
                    const price = parseFloat(r.price) || 0;

                    if (packs > 0 && price <= 0) {
                        hasZeroPrice = true;
                    }
                });

                return;
            }

            const qtyToUse = parseFloat($row.find('.input-cantidad-stock-item').val()) || 0;
            const unitPrice = parseFloat($row.data('price')) || 0;

            if (qtyToUse > 0 && unitPrice <= 0) {
                hasZeroPrice = true;
            }
        });

        if (hasZeroPrice) {
            $.confirm({
                icon: 'fas fa-exclamation-triangle',
                theme: 'modern',
                closeIcon: true,
                animation: 'zoom',
                type: 'orange',
                title: 'Precio en cero',
                content: 'Uno o más productos seleccionados tienen precio 0. ¿Procedemos con la venta?',
                buttons: {
                    confirm: {
                        text: 'SÍ, CONTINUAR',
                        btnClass: 'btn-orange',
                        action: function () {
                            continuarAgregarTodosStockItems();
                        }
                    },
                    cancel: {
                        text: 'CANCELAR'
                    }
                }
            });

            return;
        }

        continuarAgregarTodosStockItems();
    });

    $(document).on('keydown', '#dni, #ruc', function (e) {
        if (e.key !== 'Enter') {
            return;
        }

        e.preventDefault();

        let input = $(this);
        let documento = input.val().replace(/\D/g, '');
        let tipo = input.attr('id');

        if (tipo === 'dni' && documento.length !== 8) {
            toastr.error('El DNI debe tener exactamente 8 dígitos.', 'Documento inválido');
            return;
        }

        if (tipo === 'ruc' && documento.length !== 11) {
            toastr.error('El RUC debe tener exactamente 11 dígitos.', 'Documento inválido');
            return;
        }

        consultarClientePorDocumento(documento, tipo);
    });

    $(document).on('click', '.btnConsultarCliente', function (e) {
        let selectorInput = $(this).data('input');
        let input = $(selectorInput);

        e.preventDefault();

        let documento = input.val().replace(/\D/g, '');
        let tipo = input.attr('id');

        if (tipo === 'dni' && documento.length !== 8) {
            toastr.error('El DNI debe tener exactamente 8 dígitos.', 'Documento inválido');
            return;
        }

        if (tipo === 'ruc' && documento.length !== 11) {
            toastr.error('El RUC debe tener exactamente 11 dígitos.', 'Documento inválido');
            return;
        }

        consultarClientePorDocumento(documento, tipo);
    });

    $('#pagos_parciales_venta').on('switchChange.bootstrapSwitch', function (event, state) {
        if (state) {
            limpiarDatosPagoNormal();

            $('#wrap_pago_normal').hide();
            $('#wrap_comprobante').hide();
        } else {
            $('#wrap_pago_normal').show();
            $('#wrap_comprobante').show();
        }
    });

    $(document).on('change', '.itemeable-item-checkbox-v2', function () {
        const requiredCount = parseInt(
            $itemeableV2CurrentDraft
                ? $itemeableV2CurrentDraft.unitsEquivalent
                : 0
        ) || 0;

        updateItemeableItemsCounterV2(requiredCount);
    });

    $(document).on('keydown', '#itemeable-item-search-v2', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            selectItemByScannedCodeV2();
        }
    });

    $(document).on('change', '#itemeable-item-search-v2', function () {
        selectItemByScannedCodeV2();
    });

    $(document).on('click', '#btn-confirm-itemeable-items-v2', function () {
        if (!$itemeableV2CurrentDraft) {
            toastr.error('No se encontró la línea itemeable en proceso.');
            cancelItemeableV2Flow();
            return;
        }

        const requiredCount = parseInt(
            $itemeableV2CurrentDraft.unitsEquivalent || 0
        );

        const selectedItems = [];

        $('#itemeable-items-table-body-v2')
            .find('.itemeable-item-checkbox-v2:checked')
            .each(function () {
                selectedItems.push({
                    id: parseInt($(this).attr('data-item-id')) || 0,
                    code: $(this).attr('data-item-code') || ''
                });
            });

        if (selectedItems.length !== requiredCount) {
            toastr.warning(
                `Debe seleccionar exactamente ${requiredCount} ítem(s). Actualmente tiene ${selectedItems.length}.`
            );

            return;
        }

        const selectedItemIds = selectedItems
            .map(function (item) {
                return item.id;
            })
            .filter(function (id) {
                return id > 0;
            });

        if (selectedItemIds.length !== requiredCount) {
            toastr.error('Se detectó una selección inválida de ítems físicos.');
            return;
        }

        if (selectedItemIds.length !== new Set(selectedItemIds).size) {
            toastr.error('No puede seleccionar el mismo ítem físico más de una vez.');
            return;
        }

        $itemeableV2CurrentDraft.selectedItems = selectedItems;
        $itemeableV2CurrentDraft.selected_item_ids = selectedItemIds;

        $itemeableV2CurrentDraft = null;

        $('#modalSelectItemeableItemsV2')
            .one('hidden.bs.modal', function () {
                processNextItemeableV2Selection();
            })
            .modal('hide');
    });

    $(document).on('click', '#btn-cancel-itemeable-items-v2', function () {
        cancelItemeableV2Flow();
    });

    $(document).on('click', '#btn-close-itemeable-items-v2', function () {
        cancelItemeableV2Flow();
    });
});

let $items = [];
let $subtotal = 0;
let $taxes = 0;
let $total = 0;
let $formCreate;
let $modalVuelto;

let $fin_total_exonerada = 0;
let $fin_total_igv = 0;
let $fin_total_gravada = 0;
let $fin_total_descuentos = 0;
let $fin_total_importe = 0;
let $fin_vuelto = 0;
let $type_vuelto;

let $modeEdit = 1;
let $sale_id = null;
let $modalQuantity;

let $presentationsCache = {}; // materialId -> array presentations

let $itemeableV2SelectionQueue = [];
let $itemeableV2CurrentDraft = null;
let $itemeableV2PendingLines = [];
let $itemeableV2WasCancelled = false;

function limpiarDatosPagoNormal() {
    $('#pv_cash_box_id').val('').trigger('change');
    $('#pv_cash_box_subtype_id').val('').trigger('change');

    $('#wrap_pv_subtype').hide();
    $('#pv_subtype_hint').hide();

    limpiarDatosComprobante();
}

function limpiarDatosComprobante() {
    $('#radio_none').prop('checked', true).trigger('change');

    $('#dni').val('');
    $('#name').val('');
    $('input[name="email_invoice_boleta"]').val('');

    $('#ruc').val('');
    $('#razon_social').val('');
    $('#direccion_fiscal').val('');
    $('input[name="email_invoice_factura"]').val('');

    $('#datos_boleta').addClass('d-none');
    $('#datos_factura').addClass('d-none');

    $('#collapseOneInvoice').collapse('hide');
}

function continuarAgregarTodosStockItems() {
    let hasError = false;
    let pendingLines = [];

    $('#tbody-stock-items-venta tr').each(function () {
        const $row = $(this);

        const stockItemId = parseInt($row.data('stock-item-id')) || 0;
        const materialId = parseInt($row.data('material-id')) || 0;

        if (!stockItemId || !materialId) {
            return;
        }

        const productName =
            $row.data('product-name') ||
            $row.find('td:first').text().trim();

        const productUnit = $row.data('product-unit') || 'UND';
        const productTax = parseFloat($row.data('product-tax')) || 0;
        const productType = parseInt($row.data('product-type')) || 0;
        const stockAvailable = parseFloat($row.data('stock')) || 0;
        const unitPrice = parseFloat($row.data('price')) || 0;

        const isItemeable = productType === 3;

        const $presentationInput = $row.find('.input-presentations-selected');
        const hasPresentationsSelected = $presentationInput.length > 0;

        /*
        |--------------------------------------------------------------------------
        | CASO 1: Presentaciones seleccionadas
        |--------------------------------------------------------------------------
        */
        if (hasPresentationsSelected) {
            let presentations = [];

            try {
                presentations = JSON.parse(
                    $presentationInput.attr('data-presentations') || '[]'
                );
            } catch (e) {
                toastr.error(
                    `Error leyendo las presentaciones de ${productName}.`
                );

                hasError = true;
                return false;
            }

            const totalUnits = presentations.reduce(function (sum, presentation) {
                return sum + (parseFloat(presentation.quantity) || 0);
            }, 0);

            if (totalUnits <= 0) {
                toastr.error(
                    `Debe ingresar al menos una presentación válida para ${productName}.`
                );

                hasError = true;
                return false;
            }

            if (totalUnits > stockAvailable) {
                toastr.error(
                    `La cantidad seleccionada para ${productName} supera el stock disponible.`
                );

                hasError = true;
                return false;
            }

            presentations.forEach(function (presentation) {
                const presentationId = parseInt(
                    presentation.material_presentation_id
                ) || null;

                const unitsPerPack = parseFloat(
                    presentation.unitsPerPack
                ) || 0;

                const packs = parseFloat(presentation.packs) || 0;
                const presentationPrice = parseFloat(presentation.price) || 0;

                if (packs <= 0) {
                    return;
                }

                if (unitsPerPack <= 0) {
                    toastr.error(
                        `La presentación seleccionada para ${productName} no tiene unidades válidas.`
                    );

                    hasError = true;
                    return;
                }

                const unitsEquivalent = packs * unitsPerPack;

                if (isItemeable && unitsEquivalent % 1 !== 0) {
                    toastr.error(
                        `El producto itemeable ${productName} requiere una cantidad entera de Items físicos.`
                    );

                    hasError = true;
                    return;
                }

                const total = parseFloat(packs * presentationPrice).toFixed(2);

                pendingLines.push({
                    productId: stockItemId,
                    stockItemId: stockItemId,
                    materialId: materialId,

                    presentationId: presentationId,
                    presentationQty: unitsPerPack,
                    presentationLabel: `${unitsPerPack} unidades`,

                    priceEffective: presentationPrice,
                    productPrice: presentationPrice,
                    productName: productName,
                    productUnit: productUnit,
                    productTax: productTax,
                    productType: productType,

                    productTotal: total,
                    productTotalTaxes: parseFloat(
                        total * (1 + (productTax / 100))
                    ).toFixed(2),

                    productTaxes: parseFloat(
                        total * (productTax / 100)
                    ).toFixed(2),

                    productQuantity: packs,
                    unitsEquivalent: unitsEquivalent,
                    productDiscount: 0,

                    isItemeable: isItemeable,
                    selectedItems: [],
                    selected_item_ids: []
                });
            });

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | CASO 2: Venta directa por unidad
        |--------------------------------------------------------------------------
        */
        const qtyToUse = parseFloat(
            $row.find('.input-cantidad-stock-item').val()
        ) || 0;

        if (qtyToUse <= 0) {
            return;
        }

        if (qtyToUse > stockAvailable) {
            toastr.error(
                `La cantidad ingresada para ${productName} supera el stock disponible.`
            );

            hasError = true;
            return false;
        }

        if (isItemeable && qtyToUse % 1 !== 0) {
            toastr.error(
                `El producto itemeable ${productName} requiere una cantidad entera de Items físicos.`
            );

            hasError = true;
            return false;
        }

        const total = parseFloat(qtyToUse * unitPrice).toFixed(2);

        pendingLines.push({
            productId: stockItemId,
            stockItemId: stockItemId,
            materialId: materialId,

            presentationId: null,
            presentationQty: 1,
            presentationLabel: 'Unidad',

            priceEffective: unitPrice,
            productPrice: unitPrice,
            productName: productName,
            productUnit: productUnit,
            productTax: productTax,
            productType: productType,

            productTotal: total,
            productTotalTaxes: parseFloat(
                total * (1 + (productTax / 100))
            ).toFixed(2),

            productTaxes: parseFloat(
                total * (productTax / 100)
            ).toFixed(2),

            productQuantity: qtyToUse,
            unitsEquivalent: qtyToUse,
            productDiscount: 0,

            isItemeable: isItemeable,
            selectedItems: [],
            selected_item_ids: []
        });
    });

    if (hasError) {
        return;
    }

    if (pendingLines.length === 0) {
        toastr.warning(
            'Debe ingresar una cantidad o seleccionar una presentación.'
        );

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Preparar flujo final
    |--------------------------------------------------------------------------
    | No se agrega nada todavía al carrito.
    | Así evitamos que queden productos normales agregados si el usuario
    | cancela la selección de un Item físico.
    */
    $itemeableV2PendingLines = pendingLines;

    $itemeableV2SelectionQueue = pendingLines.filter(function (line) {
        return line.isItemeable === true;
    });

    $itemeableV2CurrentDraft = null;
    $itemeableV2WasCancelled = false;

    /*
    |--------------------------------------------------------------------------
    | Sin itemeables
    |--------------------------------------------------------------------------
    | La siguiente función la crearemos en el próximo paso.
    | Será la única responsable de insertar las líneas en $items.
    */
    if ($itemeableV2SelectionQueue.length === 0) {
        finalizePendingLinesV2();
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Con itemeables
    |--------------------------------------------------------------------------
    | Cerramos el modal principal antes de abrir el selector de Items.
    | No dejamos modales amontonados.
    */
    $('#modalStockItemsVenta').modal('hide');

    setTimeout(function () {
        processNextItemeableV2Selection();
    }, 350);
}

function processNextItemeableV2Selection() {
    /*
    |--------------------------------------------------------------------------
    | Si ya no hay itemeables pendientes, recién agregamos todo al carrito
    |--------------------------------------------------------------------------
    */
    if (!$itemeableV2SelectionQueue.length) {
        $itemeableV2CurrentDraft = null;
        finalizePendingLinesV2();
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Tomamos una línea itemeable de la cola
    |--------------------------------------------------------------------------
    */
    $itemeableV2CurrentDraft = $itemeableV2SelectionQueue.shift();

    const requiredCount = parseInt(
        $itemeableV2CurrentDraft.unitsEquivalent || 0
    );

    if (requiredCount <= 0) {
        toastr.error(
            `La cantidad requerida para ${$itemeableV2CurrentDraft.productName} no es válida.`
        );

        cancelItemeableV2Flow();
        return;
    }

    const availableItemsUrl =
        window.APP_POS_V2 &&
        window.APP_POS_V2.URLS &&
        window.APP_POS_V2.URLS.AVAILABLE_ITEMS;

    if (!availableItemsUrl) {
        toastr.error(
            'No se configuró la URL para consultar los ítems disponibles.'
        );

        cancelItemeableV2Flow();
        return;
    }

    const url = availableItemsUrl.replace(
        ':stockItemId',
        $itemeableV2CurrentDraft.stockItemId
    );

    /*
    |--------------------------------------------------------------------------
    | Preparar modal
    |--------------------------------------------------------------------------
    */
    $('#itemeable-product-name-v2').text(
        `${$itemeableV2CurrentDraft.productName} - ${$itemeableV2CurrentDraft.presentationLabel}`
    );

    $('#itemeable-required-count-v2').text(requiredCount);
    $('#itemeable-selected-count-v2').text(0);
    $('#itemeable-selected-required-count-v2').text(requiredCount);

    $('#itemeable-item-search-v2').val('');

    $('#itemeable-items-table-body-v2').html('');

    $('#itemeable-items-loading-v2').show();
    $('#itemeable-items-empty-v2').hide();
    $('#itemeable-items-error-v2').hide();
    $('#itemeable-items-table-container-v2').hide();

    $('#modalSelectItemeableItemsV2').modal({
        backdrop: 'static',
        keyboard: false
    });

    $('#modalSelectItemeableItemsV2').modal('show');

    /*
    |--------------------------------------------------------------------------
    | Consultar Items físicos disponibles
    |--------------------------------------------------------------------------
    */
    $.ajax({
        url: url,
        method: 'GET',
        success: function (response) {
            let items = [];

            /*
             * Soporta respuesta directa array:
             * [...]
             *
             * O respuesta estructurada:
             * { success: true, data: [...] }
             */
            //let items = [];

            if (Array.isArray(response)) {
                items = response;
            } else if (response && Array.isArray(response.items)) {
                items = response.items;
            } else if (response && Array.isArray(response.data)) {
                items = response.data;
            }

            $('#itemeable-items-loading-v2').hide();

            if (!items.length) {
                $('#itemeable-items-empty-v2').show();
                return;
            }

            renderItemeableItemsForV2(items, requiredCount);

            $('#itemeable-items-table-container-v2').show();

            setTimeout(function () {
                $('#itemeable-item-search-v2').focus();
            }, 150);
        },
        error: function () {
            $('#itemeable-items-loading-v2').hide();
            $('#itemeable-items-error-v2').show();
        }
    });
}

function finalizePendingLinesV2() {
    if ($itemeableV2WasCancelled) {
        return;
    }

    let addedCount = 0;
    let hasError = false;

    /*
    |--------------------------------------------------------------------------
    | Validar duplicados antes de insertar
    |--------------------------------------------------------------------------
    | Así evitamos agregar parcialmente las líneas si una ya existe.
    */
    for (let i = 0; i < $itemeableV2PendingLines.length; i++) {
        const line = $itemeableV2PendingLines[i];

        const selectedItemIds = Array.isArray(line.selected_item_ids)
            ? line.selected_item_ids
            : [];

        const itemKey = buildItemKey(
            line.stockItemId,
            line.presentationId,
            selectedItemIds
        );

        const exists = $items.find(function (item) {
            return item.itemKey === itemKey;
        });

        if (exists) {
            const presentationText = line.presentationId
                ? line.presentationLabel
                : 'Unidad';

            toastr.error(
                `El producto ${line.productName} (${presentationText}) ya está agregado.`
            );

            hasError = true;
            break;
        }

        line.itemKey = itemKey;
    }

    if (hasError) {
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Insertar líneas al carrito
    |--------------------------------------------------------------------------
    */
    $itemeableV2PendingLines.forEach(function (line) {
        const selectedItems = Array.isArray(line.selectedItems)
            ? line.selectedItems
            : [];

        const selectedItemIds = selectedItems
            .map(function (item) {
                return parseInt(item.id);
            })
            .filter(function (itemId) {
                return itemId > 0;
            });

        const selectedItemsText = selectedItems
            .map(function (item) {
                return item.code || `Ítem #${item.id}`;
            })
            .join(', ');

        $items.push({
            itemKey: line.itemKey,

            productId: line.productId,
            stockItemId: line.stockItemId,
            materialId: line.materialId,

            presentationId: line.presentationId,
            presentationQty: line.presentationQty,
            presentationLabel: line.presentationLabel,

            priceEffective: line.priceEffective,
            productPrice: line.productPrice,
            productName: line.productName,
            productUnit: line.productUnit,
            productTax: line.productTax,
            productType: line.productType,

            productTotal: line.productTotal,
            productTotalTaxes: line.productTotalTaxes,
            productTaxes: line.productTaxes,

            productQuantity: line.productQuantity,
            unitsEquivalent: line.unitsEquivalent,
            productDiscount: line.productDiscount,

            isItemeable: line.isItemeable === true,
            selected_item_ids: selectedItemIds,
            selected_items: selectedItems,
            selected_items_text: selectedItemsText
        });

        renderDataCartRow(line.itemKey);
        addedCount++;
    });

    updateTotalOrder();

    /*
    |--------------------------------------------------------------------------
    | Limpiar flujo temporal
    |--------------------------------------------------------------------------
    */
    $itemeableV2SelectionQueue = [];
    $itemeableV2CurrentDraft = null;
    $itemeableV2PendingLines = [];
    $itemeableV2WasCancelled = false;

    $('#modalStockItemsVenta').modal('hide');

    if (addedCount > 0) {
        toastr.success('Productos agregados al carrito.');
    }
}

function renderItemeableItemsForV2(items, requiredCount) {
    let html = '';

    items.forEach(function (item) {
        const itemId = parseInt(item.id) || 0;
        const itemCode = item.code || `Ítem #${itemId}`;

        const lotText = item.stock_lot_code
            || item.lot_code
            || item.stock_lot_id
            || '-';

        const locationText = item.warehouse_name
            || item.location
            || '-';

        html += `
            <tr
                data-item-row-v2
                data-item-id="${itemId}"
                data-item-code="${escapeHtml(String(itemCode))}">

                <td class="text-center">
                    <input
                        type="checkbox"
                        class="itemeable-item-checkbox-v2"
                        data-item-id="${itemId}"
                        data-item-code="${escapeHtml(String(itemCode))}">
                </td>

                <td>${escapeHtml(String(itemCode))}</td>
                <td>${escapeHtml(String(lotText))}</td>
                <td>${escapeHtml(String(locationText))}</td>
            </tr>
        `;
    });

    $('#itemeable-items-table-body-v2').html(html);

    updateItemeableItemsCounterV2(requiredCount);
}

function updateItemeableItemsCounterV2(requiredCount) {
    const selectedCount = $('#itemeable-items-table-body-v2')
        .find('.itemeable-item-checkbox-v2:checked')
        .length;

    $('#itemeable-selected-count-v2').text(selectedCount);
    $('#itemeable-selected-required-count-v2').text(requiredCount);

    const reachedLimit = selectedCount >= requiredCount;

    $('#itemeable-items-table-body-v2')
        .find('.itemeable-item-checkbox-v2:not(:checked)')
        .prop('disabled', reachedLimit);
}

function selectItemByScannedCodeV2() {
    const code = String(
        $('#itemeable-item-search-v2').val() || ''
    ).trim();

    if (!code) {
        return;
    }

    const $row = $('#itemeable-items-table-body-v2')
        .find('[data-item-row-v2]')
        .filter(function () {
            const rowCode = String($(this).attr('data-item-code') || '')
                .trim()
                .toLowerCase();

            return rowCode === code.toLowerCase();
        })
        .first();

    if (!$row.length) {
        toastr.warning('No se encontró un ítem disponible con ese código.');
        return;
    }

    const $checkbox = $row.find('.itemeable-item-checkbox-v2');

    if (!$checkbox.is(':checked') && !$checkbox.is(':disabled')) {
        $checkbox.prop('checked', true).trigger('change');

        $('#itemeable-items-table-body-v2').prepend($row);

        $row.addClass('table-success');

        setTimeout(function () {
            $row.removeClass('table-success');
        }, 900);
    }

    $('#itemeable-item-search-v2')
        .val('')
        .focus();
}

function cancelItemeableV2Flow() {
    $itemeableV2WasCancelled = true;

    $itemeableV2SelectionQueue = [];
    $itemeableV2CurrentDraft = null;
    $itemeableV2PendingLines = [];

    $('#modalSelectItemeableItemsV2').modal('hide');

    setTimeout(function () {
        $('#modalStockItemsVenta').modal('show');

        toastr.info(
            'No se agregaron productos al carrito. Puede modificar cantidades o presentaciones.'
        );
    }, 300);
}



function consultarClientePorDocumento(documento, tipo) {
    $.ajax({
        url: `/dashboard/customer/decolecta/${documento}`,
        type: 'GET',
        dataType: 'json',
        beforeSend: function () {
            if (tipo === 'dni') {
                $('#name').val('Consultando...');
            }

            if (tipo === 'ruc') {
                $('#razon_social').val('Consultando...');
                $('#direccion_fiscal').val('Consultando...');
            }
        },
        success: function (response) {
            if (!response.success) {
                toastr.error(response.message || 'No se pudo consultar el documento.', 'Error');
                return;
            }

            let customer = response.customer;

            if (tipo === 'dni') {
                $('#dni').val(customer.RUC);
                $('#name').val(customer.business_name || '');
            }

            if (tipo === 'ruc') {
                $('#ruc').val(customer.RUC);
                $('#razon_social').val(customer.business_name || '');
                $('#direccion_fiscal').val(customer.address || '');
            }

            let mensaje = 'Documento consultado correctamente.';
            toastr.success(mensaje, 'Correcto');
        },
        error: function (xhr) {
            let message = xhr.responseJSON && xhr.responseJSON.message
                ? xhr.responseJSON.message
                : 'No se encontró información. Puede ingresarlo manualmente.';

            toastr.warning(message + ' Puede ingresarlo manualmente.', 'Consulta sin resultado');

            if (tipo === 'dni') {
                $('#name').val('');
                $('#name').prop('readonly', false).focus();
            }

            if (tipo === 'ruc') {
                $('#razon_social').val('');
                $('#direccion_fiscal').val('');

                $('#razon_social').prop('readonly', false).focus();
                $('#direccion_fiscal').prop('readonly', false);
            }
        }
    });
}

function bloquearDatosComprobante() {
    $('input[name="name"]').prop('readonly', true);
    $('input[name="razon_social"]').prop('readonly', true);
    $('input[name="direccion_fiscal"]').prop('readonly', true);
}

function renderPresentationSelectionInRow($row, presentations) {
    const $actionTd = $row.find('td:last');

    const summaryText = buildPresentationSummaryText(presentations);
    const tooltipText = buildPresentationTooltipText(presentations);
    const json = JSON.stringify(presentations);
    const hasMultiple = presentations.length > 1;

    $actionTd.html(`
        <div class="presentation-selected-wrapper">
            
            <div class="custom-tooltip-container ${hasMultiple ? 'has-tooltip' : ''}">
                <input 
                    type="text"
                    class="form-control form-control-sm input-presentations-selected"
                    value="${summaryText}${hasMultiple ? ' ▼' : ''}"
                    readonly
                    data-presentations='${escapeHtml(json)}'
                    data-tooltip="${escapeHtml(tooltipText)}">

                ${
        hasMultiple
            ? `<div class="custom-tooltip-box">${escapeHtml(tooltipText).replace(/\n/g, '<br>')}</div>`
            : ''
        }
            </div>

            <button 
                type="button"
                class="btn btn-danger btn-sm btn-remove-presentations"
                title="Eliminar presentaciones">
                <i class="fa fa-trash"></i>
            </button>

        </div>
    `);
}

function buildPresentationSummaryText(presentations) {
    if (!presentations || presentations.length === 0) {
        return '';
    }

    const first = presentations[0];

    return `${first.packs} paq (${first.unitsPerPack} u) a S/. ${Number(first.price).toFixed(2)}`;
}

function buildPresentationTooltipText(presentations) {
    return presentations.map(function (p) {
        return `${p.packs} paq (${p.unitsPerPack} u) - Cant: ${p.quantity} u - S/. ${Number(p.price).toFixed(2)}`;
    }).join('\n');
}

function buildItemKey(stockItemId, presentationId = null, selectedItemIds = []) {
    const presentationKey = presentationId || 'unidad';

    const selectedItemsKey = Array.isArray(selectedItemIds) && selectedItemIds.length
        ? '_' + selectedItemIds
        .map(function (id) {
            return parseInt(id);
        })
        .filter(function (id) {
            return id > 0;
        })
        .sort(function (a, b) {
            return a - b;
        })
        .join('-')
        : '';

    return `${stockItemId}_${presentationKey}${selectedItemsKey}`;
}

function fetchPresentations(productId) {
    if ($presentationsCache[productId]) {
        return Promise.resolve($presentationsCache[productId]);
    }

    return $.get(`/dashboard/materials-presentations/material/${productId}/presentations`)
        .then(res => {
            const actives = (res.presentations || []).filter(p => p.active === true || p.active === 1 || p.active === "1");
            $presentationsCache[productId] = actives;
            return actives;
        });
}

function renderPresentationsInModalO(presentations) {
    if (!presentations || presentations.length === 0) {
        $('#presentationsArea').html('<div class="text-muted">Este producto no tiene presentaciones configuradas.</div>');
        return;
    }

    let html = `
    <div class="table-responsive">
      <table class="table table-sm table-bordered mb-0">
        <thead>
          <tr>
            <th style="width: 45%;">Presentación</th>
            <th style="width: 25%;">Precio</th>
            <th style="width: 30%;">Paquetes</th>
          </tr>
        </thead>
        <tbody>
  `;

    presentations.forEach(p => {
        // si no tienes label, mostramos por cantidad
        const label = (p.label && p.label.trim()) ? p.label : `${p.quantity} unidades`;

        html += `
      <tr data-pres-row data-pres-id="${p.id}" data-pres-qty="${p.quantity}" data-pres-price="${p.price}">
        <td><strong>${label}</strong><div class="text-muted" style="font-size:12px;">Equivale a ${p.quantity} unidades</div></td>
        <td>S/. ${parseFloat(p.price).toFixed(2)}</td>
        <td>
          <input type="number" min="0" step="1" class="form-control form-control-sm" value="0" data-pres-packs>
        </td>
      </tr>
    `;
    });

    html += `
        </tbody>
      </table>
    </div>
  `;

    $('#presentationsArea').html(html);
}

function renderPresentationsInModal(presentations, availableUnits = 0) {
    console.log("presentations", presentations);
    console.log("availableUnits", availableUnits);

    $('#quantity_stock_show').val(Number(availableUnits).toFixed(0));

    if (!presentations || presentations.length === 0) {
        $('#presentationsArea').html(`
            <div class="text-muted">
                Este producto no tiene presentaciones configuradas.
            </div>
        `);
        return;
    }

    let html = `
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead>
                    <tr>
                        <th style="width: 45%;">Presentación</th>
                        <th style="width: 25%;">Precio</th>
                        <th style="width: 30%;">Paquetes</th>
                    </tr>
                </thead>
                <tbody>
    `;

    presentations.forEach(function (p) {
        const quantity = Number(p.quantity) || 0;
        const price = Number(p.price) || 0;

        const label = p.label && p.label.trim()
            ? p.label
            : `${quantity} unidades`;

        html += `
            <tr 
                data-pres-row
                data-pres-id="${p.id}"
                data-pres-qty="${quantity}"
                data-pres-price="${price}">
                
                <td>
                    <strong>${label}</strong>
                    <div class="text-muted" style="font-size:12px;">
                        Equivale a ${quantity} unidades
                    </div>
                </td>

                <td>S/. ${price.toFixed(2)}</td>

                <td>
                    <input 
                        type="number"
                        min="0"
                        step="1"
                        class="form-control form-control-sm"
                        value="0"
                        data-pres-packs>
                </td>
            </tr>
        `;
    });

    html += `
                </tbody>
            </table>
        </div>
    `;

    $('#presentationsArea').html(html);
}

function escapeHtml(text) {
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

function notAddProduct() {
    $modalQuantity.modal('hide');
}

function addProductCartSpecialO() {
    event.preventDefault(); // Evitar el comportamiento por defecto del enlace

    $modalQuantity.on('shown.bs.modal', function () {
        $('#quantity_total').trigger('focus');
    });

    let productId = $(this).data('product_id');
    let materialId = $(this).data('material_id');
    let productPrice = $(this).data('product_price');
    let productStock = $(this).data('product_stock');
    let productName = $(this).data('product_name');
    let productUnit = $(this).data('product_unit');
    let productTax = $(this).data('product_tax');
    let productType = $(this).data('product_type');

    // Verificar si el producto ya está en el carrito
    /*let existingProduct = $items.find(item => item.productId == productId);*/

    if ( $modeEdit == 0 )
    {
        toastr.error("Lo sentimos ya no puede agregar mas productos, anule o imprima el comprobante.", 'Error', {
            "closeButton": true,
            "debug": false,
            "newestOnTop": false,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "2000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        });
        return;
    }

    /*if (existingProduct) {
        // Si el producto ya está en el carrito, puedes actualizar la cantidad
        toastr.error("El producto "+productName+" ya esta agregado", 'Error',
            {
                "closeButton": true,
                "debug": false,
                "newestOnTop": false,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "2000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            });
    } else {
        showModalQuantity(productId, productPrice, productName, productUnit, productTax, productType, productStock);
    }*/
    showModalQuantity(productId, materialId,productPrice, productName, productUnit, productTax, productType, productStock);


}

function addProductCartSpecial() {
    let materialId = $(this).data('product_id');

    if (!materialId) {
        toastr.error('No se encontró el material.');
        return;
    }

    $('#tbody-stock-items-venta').html(`
        <tr>
            <td colspan="7" class="text-center">Cargando...</td>
        </tr>
    `);

    $('#modalStockItemsVenta').modal('show');

    $.ajax({
        url: `/dashboard/get/material/${materialId}/stock-items`,
        type: 'GET',
        success: function (response) {
            if (!response.success || !response.data || response.data.length === 0) {
                $('#tbody-stock-items-venta').html(`
                    <tr>
                        <td colspan="7" class="text-center">
                            No se encontraron presentaciones.
                        </td>
                    </tr>
                `);
                return;
            }

            renderStockItemsVenta(response.data);
        },
        error: function () {
            $('#tbody-stock-items-venta').html(`
                <tr>
                    <td colspan="7" class="text-center text-danger">
                        Error al cargar las presentaciones.
                    </td>
                </tr>
            `);
        }
    });
}

function renderStockItemsVenta(items) {
    let html = '';

    items.forEach(function (item) {
        let disabledQty = parseFloat(item.stock_available) <= 0 ? 'disabled' : '';

        html += `
            <tr 
                data-stock-item-id="${item.stock_item_id}"
                data-material-id="${item.material_id}"
                data-product-name="${item.display_name || item.variant_text}"
                data-product-unit="${item.unit || 'UND'}"
                data-product-tax="${item.tax || 18}"
                data-product-type="${item.type || 0}"
                data-price="${item.price}"
                data-stock="${item.stock_available}">
                <td>${item.variant_text || 'Presentación única'}</td>
                <td>${item.sku || ''}</td>
                <td>${item.barcode || ''}</td>

                <td>
                    <input 
                        type="text" 
                        class="form-control form-control-sm text-primary font-weight-bold input-disponible"
                        value="${Number(item.stock_available).toFixed(2)}"
                        readonly>
                </td>

                <td>
                    <input 
                        type="text" 
                        class="form-control form-control-sm input-precio-tienda"
                        value="${Number(item.price).toFixed(2)}"
                        readonly>
                </td>

                <td>
                    <input 
                        type="number" 
                        class="form-control form-control-sm input-cantidad-stock-item"
                        value="0"
                        min="0"
                        max="${item.stock_available}"
                        >
                </td>

                <td class="text-center">
                    <button 
                        type="button" 
                        class="btn btn-outline-primary btn-sm btn-presentacion-stock-item"
                        data-material_id="${item.material_id}"
                        data-stock_item_id="${item.stock_item_id}">
                        Presentación
                    </button>
                </td>
            </tr>
        `;
    });

    $('#tbody-stock-items-venta').html(html);
}

function unlockCurrentStockItemQuantity() {
    if (!$currentStockItemRow) return;

    const hasSelectedPresentations =
        $currentStockItemRow.find('.input-presentations-selected').length > 0;

    if (!hasSelectedPresentations) {
        $currentStockItemRow
            .find('.input-cantidad-stock-item')
            .prop('readonly', false)
            .prop('disabled', false)
            .removeAttr('title');
    }
}

/*function showModalQuantity(productId, productPrice, productName, productUnit, productTax, productType, productStock) {

    $("#quantity_productId").val(productId);
    $("#quantity_productPrice").val(productPrice);
    $("#quantity_productStock").val(productStock);
    $("#quantity_productName").val(productName);
    $("#quantity_productUnit").val(productUnit);
    $("#quantity_productTax").val(productTax);
    $("#quantity_productType").val(productType);

    $modalQuantity.modal('show');
}*/
function showModalQuantity(productId, materialId,productPrice, productName, productUnit, productTax, productType, productStock) {

    $("#quantity_productId").val(productId);
    $("#quantity_materialId").val(materialId);
    $("#quantity_productPrice").val(productPrice);
    $("#quantity_productStock").val(productStock);
    $("#quantity_productName").val(productName);
    $("#quantity_productUnit").val(productUnit);
    $("#quantity_productTax").val(productTax);
    $("#quantity_productType").val(productType);

    // reset inputs
    $("#quantity_total").val(0);
    $("#quantity_stock_show").val(productStock);
    $("#presentationsArea").html('<div class="text-muted">Cargando presentaciones...</div>');

    // cargar presentaciones activas
    fetchPresentations(materialId)
        .then(presentations => {
            renderPresentationsInModal(presentations);
            $modalQuantity.modal('show');

            $modalQuantity.on('shown.bs.modal', function () {
                $('#quantity_total').trigger('focus');
            });
        })
        .catch(() => {
            $('#presentationsArea').html('<div class="text-danger">No se pudo cargar presentaciones.</div>');
            $modalQuantity.modal('show');
        });
}

/*function addProduct() {
    event.preventDefault(); // Evitar el comportamiento por defecto del enlace


    let productId =  $("#quantity_productId").val();
    let productPrice = $("#quantity_productPrice").val();
    let productStock = $("#quantity_productStock").val();
    let productName = $("#quantity_productName").val();
    let productUnit = $("#quantity_productUnit").val();
    let productTax = $("#quantity_productTax").val();

    let productType = $("#quantity_productType").val();

    let quantity = $("#quantity_total").val();

    console.log("productStock");
    console.log(productStock);
    console.log("quantity");
    console.log(quantity);

    if (parseFloat(productStock) < parseFloat(quantity)) {
        toastr.error("La cantidad sobrepasa el stock del material.", 'Error', {
            "closeButton": true,
            "debug": false,
            "newestOnTop": false,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "2000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        });
        return;
    }

    if (productType == 2) {
        // Permitir decimales en quantity, no hacemos nada
        let precio = parseFloat(productPrice * quantity).toFixed(2);
        $items.push({
            "productId": productId,
            "productPrice": productPrice,
            "productName": productName,
            "productUnit": productUnit,
            "productTax": productTax,
            "productTotal": precio,
            "productTotalTaxes": parseFloat(precio*(1+(productTax/100))).toFixed(2),
            "productTaxes": parseFloat(precio*(productTax/100)).toFixed(2),
            "productQuantity": quantity,
            "productDiscount": 0
        });
        // Renderizar el producto en el carrito
        renderDataCartQuantity(productId, productPrice, productName, productUnit, quantity);

    } else {
        // No permitir decimales en quantity
        if (quantity % 1 !== 0) {
            toastr.error("Este tipo de producto no acepta decimales", 'Error', {
                "closeButton": true,
                "debug": false,
                "newestOnTop": false,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "2000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            });
            $("#quantity_total").val(Math.floor(quantity)); // Elimina los decimales
            return;
        }

        let precio = parseFloat(productPrice * Math.floor(quantity)).toFixed(2);
        $items.push({
            "productId": productId,
            "productPrice": productPrice,
            "productName": productName,
            "productUnit": productUnit,
            "productTax": productTax,
            "productTotal": precio,
            "productTotalTaxes": parseFloat(precio*(1+(productTax/100))).toFixed(2),
            "productTaxes": parseFloat(precio*(productTax/100)).toFixed(2),
            "productQuantity": quantity,
            "productDiscount": 0
        });
        // Renderizar el producto en el carrito
        renderDataCartQuantity(productId, productPrice, productName, productUnit, quantity);
    }
    //renderDataCart(productId, productPrice, productName, productUnit);
    $('#quantity_total').val('');
    $modalQuantity.modal('hide');
}*/
function addProduct() {
    event.preventDefault();

    let productId = $("#quantity_productId").val();
    let materialId = $("#quantity_materialId").val();
    let unitPrice = parseFloat($("#quantity_productPrice").val());
    let productStock = parseFloat($("#quantity_productStock").val());
    let productName = $("#quantity_productName").val();
    let productUnit = $("#quantity_productUnit").val();
    let productTax = parseFloat($("#quantity_productTax").val());
    let productType = $("#quantity_productType").val();

    // Cantidad unitaria
    let unitQty = parseFloat($("#quantity_total").val()) || 0;

    // Presentaciones seleccionadas
    let rows = [];
    $('#presentationsArea').find('tr[data-pres-row]').each(function () {
        const presId = parseInt($(this).attr('data-pres-id'), 10);
        const presQty = parseInt($(this).attr('data-pres-qty'), 10);
        const presPrice = parseFloat($(this).attr('data-pres-price'));

        const packs = parseInt($(this).find('[data-pres-packs]').val(), 10) || 0;

        if (packs > 0) {
            rows.push({
                presentationId: presId,
                presentationQty: presQty,     // equiv en unidades
                price: presPrice,             // precio por paquete
                packs: packs
            });
        }
    });

    // Nada ingresado
    if (unitQty <= 0 && rows.length === 0) {
        toastr.error("Ingrese cantidad (unidad) o paquetes de alguna presentación.", 'Error', { "closeButton": true });
        return;
    }

    // Validar decimales por tipo SOLO para unidad (presentaciones siempre enteras)
    if (productType != 2 && unitQty % 1 !== 0) {
        toastr.error("Este tipo de producto no acepta decimales en venta por unidad.", 'Error', { "closeButton": true });
        $("#quantity_total").val(Math.floor(unitQty));
        return;
    }

    // Stock equivalente requerido (en unidades)
    let unitsRequired = 0;

    // unidad
    if (unitQty > 0) unitsRequired += unitQty;

    // presentaciones
    rows.forEach(r => {
        unitsRequired += (r.packs * r.presentationQty);
    });

    if (productStock < unitsRequired) {
        toastr.error(`La cantidad sobrepasa el stock del material. Stock: ${productStock} unidades. Requerido: ${unitsRequired} unidades.`, 'Error', { "closeButton": true });
        return;
    }

    // 1) Agregar fila unitaria si aplica
    if (unitQty > 0) {
        const itemKey = buildItemKey(productId, null);

        // bloquear duplicado exacto: misma presentación (unit)
        let existing = $items.find(x => x.itemKey === itemKey);
        if (existing) {
            toastr.error(`El producto ${productName} (Unidad) ya está agregado. Use + / - para modificar.`, 'Error', { "closeButton": true });
        } else {
            const qtyToUse = (productType == 2) ? parseFloat(unitQty) : Math.floor(unitQty);
            const total = (qtyToUse * unitPrice).toFixed(2);

            $items.push({
                itemKey: itemKey,
                productId: productId,
                materialId: materialId,
                presentationId: null,
                presentationQty: 1,
                presentationLabel: 'Unidad',
                priceEffective: unitPrice,     // precio por unidad
                productPrice: unitPrice,
                productName: productName,
                productUnit: productUnit,
                productTax: productTax,
                productTotal: total,
                productTotalTaxes: parseFloat(total * (1 + (productTax / 100))).toFixed(2),
                productTaxes: parseFloat(total * (productTax / 100)).toFixed(2),
                productQuantity: qtyToUse,     // cantidad ingresada (unidades)
                unitsEquivalent: qtyToUse,     // unidades para stock
                productDiscount: 0
            });

            renderDataCartRow(itemKey);
        }
    }

    // 2) Agregar filas por cada presentación
    rows.forEach(r => {
        const itemKey = buildItemKey(productId, r.presentationId);

        let existing = $items.find(x => x.itemKey === itemKey);
        if (existing) {
            toastr.error(`El producto ${productName} (${r.presentationQty} unidades) ya está agregado. Use + / - para modificar.`, 'Error', { "closeButton": true });
            return;
        }

        const total = (r.packs * r.price).toFixed(2);

        $items.push({
            itemKey: itemKey,
            productId: productId,
            materialId: materialId,
            presentationId: r.presentationId,
            presentationQty: r.presentationQty,
            presentationLabel: `${r.presentationQty} unidades`,
            priceEffective: r.price,        // precio por paquete
            productPrice: r.price,
            productName: productName,
            productUnit: productUnit,
            productTax: productTax,
            productTotal: total,
            productTotalTaxes: parseFloat(total * (1 + (productTax / 100))).toFixed(2),
            productTaxes: parseFloat(total * (productTax / 100)).toFixed(2),
            productQuantity: r.packs,       // cantidad ingresada (paquetes)
            unitsEquivalent: (r.packs * r.presentationQty), // unidades para stock
            productDiscount: 0
        });

        renderDataCartRow(itemKey);
    });

    // cerrar
    $('#quantity_total').val(0);
    $modalQuantity.modal('hide');

    updateTotalOrder();
}

function renderDataCartRow(itemKey) {
    const item = $items.find(x => x.itemKey === itemKey);
    if (!item) return;

    var clone = activateTemplate('#item-cart');

    clone.querySelector("[data-delete]").setAttribute("data-delete", itemKey);

    clone.querySelector("[data-name]").innerHTML = item.productName;

    const presLabel = item.presentationId
        ? `Presentación: ${item.presentationLabel}`
        : `Presentación: Unidad`;

    clone.querySelector("[data-presentation_label]").innerHTML = presLabel;

    const selectedItemsElement = clone.querySelector("[data-selected_items]");

    if (selectedItemsElement) {
        if (item.isItemeable && item.selected_items_text) {
            selectedItemsElement.innerHTML =
                `<strong>Ítems:</strong> ${item.selected_items_text}`;

            selectedItemsElement.style.display = 'block';
        } else {
            selectedItemsElement.innerHTML = '';
            selectedItemsElement.style.display = 'none';
        }
    }

    clone.querySelector("[data-price]").innerHTML =
        changeStringPrice(itemKey, item.productQuantity);

    const minusButton = clone.querySelector("[data-item_key_minus]");
    const plusButton = clone.querySelector("[data-item_key_plus]");

    minusButton.setAttribute("data-item_key_minus", itemKey);
    plusButton.setAttribute("data-item_key_plus", itemKey);

    var quantityInput = clone.querySelector("[data-quantity]");

    if (quantityInput) {
        quantityInput.step = item.presentationId
            ? 1
            : (item.productType == 2 ? 0.01 : 1);

        quantityInput.value = item.productQuantity;

        if (item.isItemeable) {
            quantityInput.readOnly = true;
            quantityInput.title =
                'Para cambiar la cantidad, elimine la línea y seleccione nuevamente los ítems físicos.';
        }
    }

    if (item.isItemeable) {
        minusButton.disabled = true;
        plusButton.disabled = true;

        minusButton.classList.add('disabled');
        plusButton.classList.add('disabled');

        minusButton.title =
            'Para cambiar la cantidad, elimine la línea y seleccione nuevamente los ítems físicos.';

        plusButton.title =
            'Para cambiar la cantidad, elimine la línea y seleccione nuevamente los ítems físicos.';
    }

    clone.querySelector("[data-priceTotal]").innerHTML =
        parseFloat(item.productTotal).toFixed(2);

    $("#body-cart").append(clone);

    if (quantityInput) {
        $(quantityInput).trigger('input');
    }
}

function newSale() {
    location.reload();
}

function cerrarVuelto() {
    $modalVuelto.modal('hide');
    $("#btn-pay").attr("disabled", false);
}

function payNow() {
    event.preventDefault();
    $("#btn-pay").attr("disabled", true);

    // 1) Validar items
    if ($items.length === 0) {
        toastr.error("Seleccione productos a la venta.", 'Error', { "closeButton": true });
        $("#btn-pay").attr("disabled", false);
        return;
    }

    const pagosParcialesVenta =
        $('#pagos_parciales_venta').length && $('#pagos_parciales_venta').is(':checked');

    if (pagosParcialesVenta) {
        window.PV_SELECTED_WORKER_ID = null;
        guardarVenta();
        return;
    }

    // 2) Validar CashBox seleccionado
    const cashBoxId = $('#pv_cash_box_id').val();
    if (!cashBoxId) {
        toastr.error("Seleccione una caja (CashBox).", 'Error', { "closeButton": true });
        $("#btn-pay").attr("disabled", false);
        return;
    }

    const $opt = $('#pv_cash_box_id').find('option:selected');
    const boxType = ($opt.data('type') || '').toString();          // 'cash' | 'bank'
    const usesSub = String($opt.data('uses_subtypes')) === '1';     // 1/0

    // 3) Si es bancario con subtypes, validar subtipo
    if (boxType === 'bank' && usesSub) {
        const subtypeId = $('#pv_cash_box_subtype_id').val();
        if (!subtypeId) {
            toastr.error("Seleccione el subtipo bancario (Yape/Plin/POS/Transfer).", 'Error', { "closeButton": true });
            $("#btn-pay").attr("disabled", false);
            return;
        }
    }

    // 4) Definir si requiere vuelto (antes era data-vuelto del radio)
    // Regla recomendada:
    // - cash => puede requerir vuelto (abrimos modal)
    // - bank => no requiere vuelto (guardamos directo)
    const requiresChange = (boxType === 'cash');

    // 5) Trabajador (si aplica)
    if (!window.PV_ASK_WORKER) {
        window.PV_SELECTED_WORKER_ID = null;
        continuarFlujoPago();
        return;
    }

    mostrarPopupTrabajador(continuarFlujoPago);

    function continuarFlujoPago() {
        if (requiresChange) {
            mostrarVuelto();
        } else {
            guardarVenta();
        }
    }
}

function refreshVueltoSubtypeUI() {
    const $opt = $('#pv_vuelto_cash_box_id').find('option:selected');
    const type = ($opt.data('type') || '').toString(); // cash|bank
    const usesSub = String($opt.data('uses_subtypes')) === '1';

    if (type === 'bank' && usesSub) {
        $('#wrap_vuelto_subtype').show();
    } else {
        $('#wrap_vuelto_subtype').hide();
        $('#pv_vuelto_subtype_id').val('').trigger('change');
    }
}

function mostrarPopupTrabajador(doneCallback) {
    // construir options
    let optionsHtml = '<option value="">Seleccione trabajador...</option>';
    (window.PV_WORKERS || []).forEach(function (w) {
        optionsHtml += '<option value="'+w.id+'">'+w.name+'</option>';
    });

    $.confirm({
        title: 'Asignar trabajador',
        content:
            '<form>' +
            '<div class="form-group">' +
            '<label>Seleccione el trabajador que cerrará la venta</label>' +
            '<select id="pv-worker-select" class="form-control">' +
            optionsHtml +
            '</select>' +
            '</div>' +
            '</form>',
        type: 'blue',
        buttons: {
            confirmar: {
                text: 'Aceptar',
                btnClass: 'btn-primary',
                action: function () {
                    var workerId = this.$content.find('#pv-worker-select').val();
                    if (!workerId) {
                        $.alert('Debe seleccionar un trabajador.');
                        return false; // mantiene el popup abierto
                    }

                    window.PV_SELECTED_WORKER_ID = workerId;

                    if (typeof doneCallback === 'function') {
                        doneCallback();
                    }
                }
            },
            cancelar: {
                text: 'Cancelar',
                btnClass: 'btn-secondary',
                action: function () {
                    $("#btn-pay").attr("disabled", false);
                }
            }
        }
    });
}

function mostrarVuelto() {
    $("#monto_total").val(parseFloat($fin_total_importe).toFixed(2));
    refreshVueltoSubtypeUI();
    $modalVuelto.modal('show');
}

function guardarVenta() {
    event.preventDefault();
    $modalVuelto.modal('hide');

    $("#btn-pay").attr("disabled", true);

    $fin_vuelto = $("#vuelto").val();
    $type_vuelto = $("#type_caja").val();

    if ( $items.length == 0 )
    {
        toastr.error("Seleccione productos a la venta.", 'Error', {
            "closeButton": true,
            "debug": false,
            "newestOnTop": false,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "2000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        });
        $("#btn-pay").attr("disabled", false);
        return;
    }

    const pagosParcialesVenta =
        $('#pagos_parciales_venta').length && $('#pagos_parciales_venta').is(':checked');

    if (!pagosParcialesVenta) {
        let tipo = $('input[name="invoice_type"]:checked').val();

        if (tipo === 'boleta') {
            let dni = $('input[name="dni"]').val().trim();
            if (dni === '' || dni.length !== 8 || isNaN(dni)) {
                toastr.error('Debe ingresar un DNI válido de 8 dígitos');
                $("#btn-pay").attr("disabled", false);
                return;
            }
            let name = $('input[id="name"]').val().trim();
            console.log(name);
            if (name === '') {
                toastr.error('Debe ingresar el nombre del cliente.');
                $("#btn-pay").attr("disabled", false);
                return;
            }
        } else if (tipo === 'factura') {
            let ruc = $('input[name="ruc"]').val().trim();
            let razon = $('input[name="razon_social"]').val().trim();
            let direccion = $('input[name="direccion_fiscal"]').val().trim();

            if (ruc === '' || ruc.length !== 11 || isNaN(ruc)) {
                toastr.error('Debe ingresar un RUC válido de 11 dígitos');
                $("#btn-pay").attr("disabled", false);
                return;
            }
            if (razon === '') {
                toastr.error('Debe ingresar la Razón Social');
                $("#btn-pay").attr("disabled", false);
                return;
            }
            if (direccion === '') {
                toastr.error('Debe ingresar la Dirección Fiscal');
                $("#btn-pay").attr("disabled", false);
                return;
            }
        }
    }

    // ==============================
    // Texto del medio de pago (Caja + Subtipo)
    // ==============================
    let paymentText = '';

    if (pagosParcialesVenta) {
        paymentText = 'venta con pagos parciales';
    } else {

        // Caja seleccionada
        const $cashBoxOpt = $('#pv_cash_box_id option:selected');
        const cashBoxName = $cashBoxOpt.text().trim();
        const cashBoxType = $cashBoxOpt.data('type');
        const usesSubtypes = String($cashBoxOpt.data('uses_subtypes')) === '1';

        // Subtipo (si aplica)
        let subtypeName = '';

        if (cashBoxType === 'bank' && usesSubtypes) {
            const $subOpt = $('#pv_cash_box_subtype_id option:selected');
            subtypeName = $subOpt.length ? $subOpt.text().trim() : '';
        }

        // Texto final
        if (subtypeName) {
            paymentText = cashBoxName + ' – ' + subtypeName;
        } else {
            paymentText = cashBoxName;
        }
    }

    // Confirmación con jQuery Confirm
    $.confirm({
        title: 'Confirmar pago',
        content: pagosParcialesVenta
            ? '¿Está seguro de registrar esta venta como <strong>venta con pagos parciales</strong>?'
            : '¿Está seguro de realizar el pago usando <strong>' + paymentText + '</strong>?',
        type: 'blue',
        buttons: {
            confirmar: {
                text: 'Sí, confirmar',
                btnClass: 'btn-primary',
                action: function () {
                    // Proceder con el envío del formulario
                    var createUrl = $formCreate.data('url');
                    var items = JSON.stringify($items);
                    var formulario = $('#formCreate')[0];
                    var form = new FormData(formulario);

                    form.append('items', items);
                    form.append('total_exonerada', $fin_total_exonerada);
                    form.append('total_igv', $fin_total_igv);
                    form.append('total_gravada', $fin_total_gravada);
                    form.append('total_descuentos', $fin_total_descuentos);
                    form.append('total_importe', $fin_total_importe);
                    form.append('total_vuelto', $fin_vuelto);
                    /*form.append('type_vuelto', $type_vuelto);
                    form.append('tipo_pago', tipo_pago);*/
                    // ⬇️ NUEVO: worker elegido (si no hay, va vacío)
                    form.append('worker_id', window.PV_SELECTED_WORKER_ID || '');

                    form.append('cash_box_id', pagosParcialesVenta ? '' : $('#pv_cash_box_id').val());
                    form.append('cash_box_subtype_id', pagosParcialesVenta ? '' : ($('#pv_cash_box_subtype_id').val() || ''));
                    form.append('vuelto_cash_box_id', pagosParcialesVenta ? '' : ($('#pv_vuelto_cash_box_id').val() || ''));
                    form.append('vuelto_cash_box_subtype_id', pagosParcialesVenta ? '' : ($('#pv_vuelto_subtype_id').val() || ''));

                    form.append('pagos_parciales_venta', pagosParcialesVenta ? 's' : 'n');

                    const tipoComprobante = $('input[name="invoice_type"]:checked').val();

                    let data = {};

                    if (tipoComprobante === 'boleta') {
                        data.name = $('#name').val();
                        data.dni = $('#dni').val();
                        data.email = $('#email_invoice_boleta').val();
                    } else if (tipoComprobante === 'factura') {
                        data.ruc = $('#ruc').val();
                        data.razon_social = $('#razon_social').val();
                        data.direccion_fiscal = $('#direccion_fiscal').val();
                        data.email = $('#email_invoice_factura').val();
                    }

                    var processingDialog = $.dialog({
                        title: false,
                        content:
                            '<div class="text-center py-4">' +
                            '<div class="spinner-border text-primary mb-3" role="status">' +
                            '<span class="sr-only">Procesando...</span>' +
                            '</div>' +
                            '<h5 class="mb-2">Estamos procesando la venta</h5>' +
                            '<p class="text-muted mb-0">' +
                            'Por favor, espere mientras registramos el pago y generamos el comprobante.' +
                            '</p>' +
                            '</div>',
                        columnClass: 'col-md-5 col-md-offset-4',
                        containerFluid: true,
                        backgroundDismiss: false,
                        closeIcon: false,
                        escapeKey: false
                    });

                    $.ajax({
                        url: createUrl,
                        method: 'POST',
                        data: form,
                        processData: false,
                        contentType: false,
                        success: function (data) {
                            console.log(data);
                            toastr.success(data.message, 'Éxito', {
                                "closeButton": true,
                                "progressBar": true,
                                "positionClass": "toast-top-right",
                                "timeOut": "2000"
                            });
                            setTimeout(function () {
                                $("#btn-pay").attr("disabled", false);
                                $modeEdit = 0;
                                $sale_id = data.sale_id;

                                $("#btn-pay").hide();
                                $("#btn-newSale").show();
                                $("#btn-printDocument").show();
                                //$("#btn-printDocument").attr("href", data.url_print);
                                $("#btn-printDocument")
                                    .attr("href", data.url_print)
                                    .attr("target", "_blank");

                                if (data.print_type === 'sunat_pdf') {
                                    $("#btn-printDocument").text('Ver comprobante SUNAT');
                                } else {
                                    $("#btn-printDocument").text('Imprimir ticket');
                                }
                            }, 2000);
                        },
                        error: function (data) {
                            if (data.responseJSON.message && !data.responseJSON.errors) {
                                toastr.error(data.responseJSON.message, 'Error');
                            }
                            for (var property in data.responseJSON.errors) {
                                toastr.error(data.responseJSON.errors[property], 'Error');
                            }
                            $("#btn-pay").attr("disabled", false);
                        },
                        complete: function () {
                            if (processingDialog) {
                                processingDialog.close();
                            }
                        }
                    });
                }
            },
            cancelar: {
                text: 'Cancelar',
                btnClass: 'btn-secondary',
                action: function () {
                    $("#btn-pay").attr("disabled", false);
                }
            }
        }
    });
}

function deleteItem() {
    if ($modeEdit == 0) {
        toastr.error("Lo sentimos ya no puede quitar productos, anule o imprima el comprobante.", 'Error', { "closeButton": true });
        return;
    }

    let itemKey = $(this).attr('data-delete');

    $items = $items.filter(item => item.itemKey !== itemKey);

    updateTotalOrder();

    $(this).closest('[data-cart-row]').remove();
}

function updateItems(itemKey, precioTotal, quantity) {
    let result = $items.find(item => item.itemKey === itemKey);
    if (!result) return;

    result.productQuantity = quantity;

    // Recalcular equivalente de stock:
    // unidad => quantity
    // presentación => quantity * presentationQty
    result.unitsEquivalent = result.presentationId ? (quantity * result.presentationQty) : quantity;

    result.productTotal = parseFloat(precioTotal).toFixed(2);
}

function decrementQuantity(button) {
    var $input = $(button).siblings('input[type="number"]');
    var currentValue = parseFloat($input.val());
    var step = parseFloat($input.attr('step')) || 0.01;
    let itemKey = $(button).attr('data-item_key_minus');
    var string = "";
    var priceTotal = 0;
    var stringDiscount = "";

    const currentItem = $items.find(item => item.itemKey === itemKey);

    if (currentItem && currentItem.isItemeable) {
        toastr.warning(
            'No puede modificar directamente la cantidad de un producto itemeable. Elimine la línea y vuelva a seleccionarlo.',
            'Producto itemeable'
        );

        return;
    }

    if (currentValue > 0) {
        $input.val((currentValue - step).toFixed(2)).trigger('change');
        string = changeStringPrice( itemKey, (currentValue - step).toFixed(2) );
        priceTotal = changePriceTotal( itemKey, (currentValue - step).toFixed(2) );
        $(button).closest('.flex-grow-1').find('h6[data-price]').html(string);
        $(button).closest('.d-flex').find('p[data-priceTotal]').html(priceTotal);

        getDiscountMaterial(itemKey, currentValue - step).then(function(discount) {
            console.log(discount);
            if ( discount != -1 )
            {
                $(button).closest('.flex-grow-1').find('h6[data-discount]').html(discount.stringDiscount);
            } else  {
                $(button).closest('.flex-grow-1').find('h6[data-discount]').html("");
            }

            //updateItems($(button).attr('data-product_id_plus', currentValue + step), priceTotal, currentValue + step);
        });

        updateItems(itemKey, priceTotal, currentValue - step);

        updateTotalOrder();
    } else {
        $input.val(0);
        string = changeStringPrice( itemKey, 0 );
        priceTotal = changePriceTotal( itemKey, 0 );
        $(button).closest('.flex-grow-1').find('h6[data-price]').html(string);
        $(button).closest('.d-flex').find('p[data-priceTotal]').html(priceTotal);

        getDiscountMaterial(itemKey, currentValue - step).then(function(discount) {
            console.log(discount);
            //$(button).closest('.flex-grow-1').find('h6[data-discount]').html(discount.stringDiscount);
            if ( discount != -1 )
            {
                $(button).closest('.flex-grow-1').find('h6[data-discount]').html(discount.stringDiscount);
            } else  {
                $(button).closest('.flex-grow-1').find('h6[data-discount]').html("");
            }
            //updateItems($(button).attr('data-product_id_plus', currentValue + step), priceTotal, currentValue + step);
        });

        updateItems(itemKey, priceTotal, 0);

        updateTotalOrder();
    }
    //console.log(string);
}

function changePriceTotal(itemKey, quantity) {
    let result = $items.find(item => item.itemKey === itemKey);
    if (!result) return "0.00";
    return (quantity * result.productPrice).toFixed(2);
}

function changeStringPrice(itemKey, quantity) {
    let result = $items.find(item => item.itemKey === itemKey);
    if (!result) return "";

    if (result.presentationId) {
        // paquetes
        return `<strong>${quantity}</strong> paquetes (${result.presentationQty} u) a S/. ${parseFloat(result.productPrice).toFixed(2)} / paquete`;
    } else {
        // unidad
        return `<strong>${quantity}</strong> ${result.productUnit} a S/. ${parseFloat(result.productPrice).toFixed(2)} / unidad`;
    }
}

function incrementQuantity(button) {
    event.preventDefault();
    var $input = $(button).siblings('input[type="number"]');
    var currentValue = parseFloat($input.val());
    var step = parseFloat($input.attr('step')) || 0.01;
    let itemKey = $(button).attr('data-item_key_plus');

    const currentItem = $items.find(item => item.itemKey === itemKey);

    if (currentItem && currentItem.isItemeable) {
        toastr.warning(
            'No puede modificar directamente la cantidad de un producto itemeable. Elimine la línea y vuelva a seleccionarlo.',
            'Producto itemeable'
        );

        return;
    }

    $input.val((currentValue + step).toFixed(2)).trigger('change');

    var string = "";
    var priceTotal = 0;
    var discount = 0;

    string = changeStringPrice( itemKey, (currentValue + step).toFixed(2) );
    priceTotal = changePriceTotal( itemKey, (currentValue + step).toFixed(2) );
    //console.log(string);

    $(button).closest('.flex-grow-1').find('h6[data-price]').html(string);
    $(button).closest('.d-flex').find('p[data-priceTotal]').text(priceTotal);

    // Maneja la promesa retornada por getDiscountMaterial
    getDiscountMaterial(itemKey, currentValue + step).then(function(discount) {
        console.log(discount);
        //$(button).closest('.flex-grow-1').find('h6[data-discount]').html(discount.stringDiscount);
        if ( discount != -1 )
        {
            $(button).closest('.flex-grow-1').find('h6[data-discount]').html(discount.stringDiscount);
        } else  {
            $(button).closest('.flex-grow-1').find('h6[data-discount]').html("");
        }
        //updateItems($(button).attr('data-product_id_plus', currentValue + step), priceTotal, currentValue + step);
    });

    //$(button).closest('.flex-grow-1').find('h6[data-discount]').html(discount.stringDiscount);

    updateItems(itemKey, priceTotal, currentValue + step);
    
    updateTotalOrder();
}

function updateTotalOrder() {
    /*
    * OP. Exonerada:
- suma de los precios con taxes = 0 o null
OP. Inafecta
OP. Gravada:
- suma ((precios con taxes * cantidad != 0 o != null menos descuentos) dividir entre el 1+porcentaje)
IGV:
- suma ((precios con taxes * cantidad != 0 o != null menos descuentos))  menos OP. Gravada
Descuentos:
- suma de los productos con descuentos
Importe Total:
- OP. Exonerada + OP. Inafecta + OP. Gravada + IGV
    * */
    console.log($items);
    var total_exonerada = 0;
    var total_gravada = 0;
    var total_igv = 0;
    var total_descuentos = 0;
    var total_importe = 0;
    var total_igv_bruto=0;
    for ( let i = 0; i < $items.length; i++ )
    {
        if ( $items[i].productTax == 0 )
        {
            total_exonerada = total_exonerada + parseFloat($items[i].productTotal);
        }

        if ( $items[i].productTax != 0 )
        {
            total_gravada = total_gravada + (($items[i].productTotal-$items[i].productDiscount)/(1+($items[i].productTax/100)));
        }

        if ( $items[i].productTax != 0 )
        {
            total_igv_bruto = total_igv_bruto + ($items[i].productTotal-$items[i].productDiscount);
        }

        total_igv = total_igv_bruto-total_gravada;

        if ( $items[i].productDiscount != 0 )
        {
            total_descuentos = total_descuentos + $items[i].productDiscount;
        }

        console.log("Total exonerada "+total_exonerada);
        console.log("Total gravada "+total_gravada);
        console.log("Total igv "+total_igv);
        console.log("Total descuentos "+total_descuentos);


    }

    //console.log(total_importe);
    total_importe=total_importe+total_exonerada+total_gravada+total_igv;
    // Actualizar los datos

    $fin_total_exonerada = total_exonerada;
    $fin_total_igv = total_igv;
    $fin_total_gravada = total_gravada;
    $fin_total_descuentos = total_descuentos;
    $fin_total_importe = total_importe;

    console.log("Total exonerada "+$fin_total_exonerada);
    console.log("Total gravada "+$fin_total_gravada);
    console.log("Total igv "+$fin_total_igv);
    console.log("Total descuentos "+$fin_total_descuentos);
    console.log("Total importe "+$fin_total_importe);

    $("#op_exonerada").html("S/. "+parseFloat($fin_total_exonerada).toFixed(2));
    //$("#op_inafecta").html("S/. "+parseFloat(op_exonerada).toFixed(2));
    $("#op_gravada").html("S/. "+parseFloat($fin_total_gravada).toFixed(2));
    $("#total_igv").html("S/. "+parseFloat($fin_total_igv).toFixed(2));
    $("#total_descuentos").html("S/. "+parseFloat($fin_total_descuentos).toFixed(2));
    $("#total_importe").html("S/. "+parseFloat($fin_total_importe).toFixed(2));


}

function getDiscountMaterial(product_id, quantity) {
    return $.get('/dashboard/get/discount/product/' + product_id, {
        quantity: quantity
    }).then(function(data) {
        console.log(data.data[0].haveDiscount);
        if (data.data[0].haveDiscount == true) {
            console.log(data);
            var existingProduct = $items.find(item => item.productId == product_id);
            existingProduct.productDiscount = data.data[0].valueDiscount;
            return data.data[0];
        } else {
            return -1;
        }
    }).fail(function(jqXHR, textStatus, errorThrown) {
        console.error(textStatus, errorThrown);
        if (jqXHR.responseJSON.message && !jqXHR.responseJSON.errors) {
            toastr.error(jqXHR.responseJSON.message, 'Error', {
                "closeButton": true,
                "debug": false,
                "newestOnTop": false,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "2000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            });
        }
        for (var property in jqXHR.responseJSON.errors) {
            toastr.error(jqXHR.responseJSON.errors[property], 'Error', {
                "closeButton": true,
                "debug": false,
                "newestOnTop": false,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "2000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            });
        }
    });
}

function addProductCart() {
    event.preventDefault();

    let productId = $(this).data('product_id');
    let materialId = $(this).data('material_id');
    let productPrice = parseFloat($(this).data('product_price'));
    let productStock = parseFloat($(this).data('product_stock'));
    let productName = $(this).data('product_name');
    let productUnit = $(this).data('product_unit');
    let productTax = parseFloat($(this).data('product_tax'));
    let productType = $(this).data('product_type'); // si no existe en normal, puedes dejarlo null

    if ($modeEdit == 0) {
        toastr.error("Lo sentimos ya no puede agregar mas productos, anule o imprima el comprobante.", 'Error', { "closeButton": true });
        return;
    }

    // 👇 clave única para “unidad”
    const itemKey = buildItemKey(productId, null);

    // Si ya existe la fila unidad, no agregues otra: usa +/-
    let existing = $items.find(x => x.itemKey === itemKey);

    if (existing) {
        toastr.error(`El producto ${productName} (Unidad) ya está agregado. Use + / - para modificar.`, 'Error', { "closeButton": true });
        return;
    }

    // validar stock (unidad)
    if (productStock < 1) {
        toastr.error("Stock insuficiente.", 'Error', { "closeButton": true });
        return;
    }

    // agregar item “unit”
    $items.push({
        itemKey: itemKey,
        productId: productId,
        materialId: materialId,
        presentationId: null,
        presentationQty: 1,
        presentationLabel: 'Unidad',
        priceEffective: productPrice,
        productPrice: productPrice,
        productName: productName,
        productUnit: productUnit,
        productTax: productTax,
        productTotal: parseFloat(productPrice * 1).toFixed(2),
        productTotalTaxes: parseFloat((productPrice * 1) * (1 + (productTax / 100))).toFixed(2),
        productTaxes: parseFloat((productPrice * 1) * (productTax / 100)).toFixed(2),
        productQuantity: 1,       // unidades
        unitsEquivalent: 1,       // unidades
        productDiscount: 0,
        productType: productType  // si lo usas en step etc
    });

    // ✅ usa el nuevo render
    renderDataCartRow(itemKey);

    updateTotalOrder();
}

function renderDataCart(productId, productPrice, productName, productUnit) {
    var quantity = 1;
    var clone = activateTemplate('#item-cart');
    clone.querySelector("[data-delete]").setAttribute("data-delete", productId);
    clone.querySelector("[data-stock]").setAttribute("data-stock", productId);
    clone.querySelector("[data-name]").innerHTML = productName;
    clone.querySelector("[data-price]").innerHTML = "<strong>" + quantity + "</strong> "+productUnit+" a " + productPrice + " / Unit";
    clone.querySelector("[data-product_id_minus]").setAttribute("data-product_id_minus", productId);
    clone.querySelector("[data-product_id_plus]").setAttribute("data-product_id_plus", productId);

    var quantityInput = clone.querySelector("[data-quantity]");
    if (quantityInput) {
        quantityInput.value = quantity; // Usa .value en lugar de setAttribute
    }

    var priceTotal;
    priceTotal = quantity * productPrice;

    clone.querySelector("[data-priceTotal]").innerHTML = priceTotal;

    $("#body-cart").append(clone);
}

function renderDataCartQuantity(productId, productPrice, productName, productUnit, productQuantity) {
    var quantity = parseFloat(productQuantity).toFixed(2);
    var clone = activateTemplate('#item-cart');
    clone.querySelector("[data-delete]").setAttribute("data-delete", productId);
    clone.querySelector("[data-name]").innerHTML = productName;
    clone.querySelector("[data-price]").innerHTML = "<strong>" + quantity + "</strong> "+productUnit+" a " + productPrice + " / Unit";
    clone.querySelector("[data-product_id_minus]").setAttribute("data-product_id_minus", productId);
    clone.querySelector("[data-product_id_plus]").setAttribute("data-product_id_plus", productId);
    //clone.querySelector("[data-quantity]").setAttribute("value", quantity);

    // Aquí asegúrate de actualizar el valor del input correctamente
    var quantityInput = clone.querySelector("[data-quantity]");
    if (quantityInput) {
        quantityInput.value = quantity; // Usa .value en lugar de setAttribute
    }

    var priceTotal;
    priceTotal = quantity * productPrice;

    clone.querySelector("[data-priceTotal]").innerHTML = priceTotal;

    $("#body-cart").append(clone);

    updateTotalOrder();

    // Asegúrate de que el input exista antes de disparar el evento
    if (quantityInput) {
        console.log("Vamos a lanzar el evento");
        $(quantityInput).trigger('input');
    }

}

function showData() {
    var numberPage = $(this).attr('data-item');
    console.log(numberPage);
    var type = $("#type_id").val();
    if (type === "f") {
        getDataTableNew(numberPage)
    } else {
        getData(numberPage)
    }
}

function showDataSearch() {
    getData(1)
}

function showDataSearchTable() {
    getDataTableNew(1);
}

function getDataTableNew($numberPage) {
    var category_id = $('#category_id').val();
    var product_search = $("#product_search").val();

    $.get('/dashboard/get/data/products/v2/'+$numberPage, {
        category_id: category_id,
        product_search: product_search
    }, function(data) {
        if (data.data.length == 0) {
            renderDataTableEmptyNew();
        } else {
            renderDataTableNew(data);
        }
    }).fail(function(jqXHR, textStatus, errorThrown) {
        console.error(textStatus, errorThrown);
        toastr.error("Error al obtener los productos", "Error", {
            "closeButton": true,
            "progressBar": true
        });
    });
}

function renderDataTableEmptyNew() {
    $("#table-body").html('');
    $("#pagination").html('');
    $("#textPagination").html('No se encontraron productos.');
}

function renderDataTableNewO(data) {
    var dataAccounting = data.data;
    var pagination = data.pagination;

    // Limpiar contenido anterior
    $("#body-card").html('');
    $("#pagination").html('');
    $("#textPagination").html('Mostrando '+pagination.startRecord+' a '+pagination.endRecord+' de '+pagination.totalFilteredRecords+' productos.');

    var table = `
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Imagen</th>
                        <th>Nombre</th>
                        <th>Precio</th>
                        <th>Unidad</th>
                        <th>Impuesto</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
    `;

    // Agregar las filas de datos
    for (let j = 0; j < dataAccounting.length; j++) {
        table += `
            <tr>
                <td><img src="${document.location.origin}/images/material/${dataAccounting[j].image}" width="50"></td>
                <td>${dataAccounting[j].full_name}</td>
                <td>${dataAccounting[j].price}</td>
                <td>${dataAccounting[j].unit}</td>
                <td>${dataAccounting[j].tax}</td>
                <td>
                    <button class="btn btn-primary btn-sm add-to-cart" 
                        data-add_cart
                        data-product_id="${dataAccounting[j].id}" 
                        data-product_price="${dataAccounting[j].price}" 
                        data-product_name="${dataAccounting[j].full_name}" 
                        data-product_unit="${dataAccounting[j].unit}" 
                        data-product_tax="${dataAccounting[j].tax}">
                        ADD TO CART
                    </button>
                    <button class="btn btn-success btn-sm add-to-cart" 
                        data-add_cart_special
                        data-product_id="${dataAccounting[j].id}" 
                        data-product_price="${dataAccounting[j].price}" 
                        data-product_name="${dataAccounting[j].full_name}" 
                        data-product_unit="${dataAccounting[j].unit}" 
                        data-product_tax="${dataAccounting[j].tax}"
                        data-product_type="${dataAccounting[j].type}">
                        ADD ESPECIAL
                    </button>
                </td>
            </tr>
        `;
    }

    // Cerrar la tabla y el div
    table += `
                </tbody>
            </table>
        </div>
    `;

    // Insertar la tabla en el contenedor
    $("#body-card").append(table);

    renderPaginationNew(pagination);
}

function renderDataTableNew(data) {
    var dataAccounting = data.data;
    var pagination = data.pagination;

    // Limpiar contenido anterior
    $("#body-card").html('');
    $("#pagination").html('');
    $("#textPagination").html('Mostrando '+pagination.startRecord+' a '+pagination.endRecord+' de '+pagination.totalFilteredRecords+' productos.');

    var table = `
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Imagen</th>
                        <th>Nombre</th>
                        <th>Precio</th>
                        <th>Unidad</th>
                        <th>Impuesto</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
    `;

    // Agregar las filas de datos
    for (let j = 0; j < dataAccounting.length; j++) {
        table += `
            <tr>
                <td><img src="${document.location.origin}/images/material/${dataAccounting[j].image}" width="50"></td>
                <td>${dataAccounting[j].full_name}</td>
                <td>${dataAccounting[j].price}</td>
                <td>${dataAccounting[j].unit}</td>
                <td>${dataAccounting[j].tax}</td>
                <td>
                    <button class="btn btn-success btn-sm add-to-cart" 
                        data-add_cart_special
                        data-product_id="${dataAccounting[j].id}" 
                        data-product_price="${dataAccounting[j].price}" 
                        data-product_name="${dataAccounting[j].full_name}" 
                        data-product_unit="${dataAccounting[j].unit}" 
                        data-product_tax="${dataAccounting[j].tax}"
                        data-product_type="${dataAccounting[j].type}">
                        AGREGAR CARRITO
                    </button>
                </td>
            </tr>
        `;
    }

    // Cerrar la tabla y el div
    table += `
                </tbody>
            </table>
        </div>
    `;

    // Insertar la tabla en el contenedor
    $("#body-card").append(table);

    renderPaginationNew(pagination);
}

function renderPaginationNew(pagination) {
    $("#pagination").html('');

    if (pagination.currentPage > 1)
    {
        renderPreviousPage(pagination.currentPage-1);
    }

    if (pagination.totalPages > 1)
    {
        if (pagination.currentPage > 3)
        {
            renderItemPage(1);

            if (pagination.currentPage > 4) {
                renderDisabledPage();
            }
        }

        for (var i = Math.max(1, pagination.currentPage - 2); i <= Math.min(pagination.totalPages, pagination.currentPage + 2); i++)
        {
            renderItemPage(i, pagination.currentPage);
        }

        if (pagination.currentPage < pagination.totalPages - 2)
        {
            if (pagination.currentPage < pagination.totalPages - 3)
            {
                renderDisabledPage();
            }
            renderItemPage(i, pagination.currentPage);
        }

    }

    if (pagination.currentPage < pagination.totalPages)
    {
        renderNextPage(pagination.currentPage+1);
    }
}

function getData($numberPage) {
    var category_id = $('#category_id').val();
    var product_search = $("#product_search").val();
    //console.log(nameCategoryEquipment);
    $.get('/dashboard/get/data/products/v2/'+$numberPage, {
        category_id: category_id,
        product_search: product_search
    }, function(data) {
        if ( data.data.length == 0 )
        {
            renderDataEmpty(data);
        } else {
            renderData(data);
        }


    }).fail(function(jqXHR, textStatus, errorThrown) {
        // Función de error, se ejecuta cuando la solicitud GET falla
        console.error(textStatus, errorThrown);
        if (jqXHR.responseJSON.message && !jqXHR.responseJSON.errors) {
            toastr.error(jqXHR.responseJSON.message, 'Error', {
                "closeButton": true,
                "debug": false,
                "newestOnTop": false,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "2000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            });
        }
        for (var property in jqXHR.responseJSON.errors) {
            toastr.error(jqXHR.responseJSON.errors[property], 'Error', {
                "closeButton": true,
                "debug": false,
                "newestOnTop": false,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "2000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            });
        }
    }, 'json')
        .done(function() {
            // Configuración de encabezados
            var headers = {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            };
            $.ajaxSetup({
                headers: headers
            });
        });
}

function renderDataEmpty(data) {
    var dataAccounting = data.data;
    var pagination = data.pagination;

    $("#body-card").html('');
    $("#pagination").html('');
    $("#textPagination").html('');
    $("#textPagination").html('Mostrando '+pagination.startRecord+' a '+pagination.endRecord+' de '+pagination.totalFilteredRecords+' productos.');
    $('#numberItems').html('');
    $('#numberItems').html(pagination.totalFilteredRecords);

    renderDataCardEmpty();
}

function renderData(data) {
    var dataAccounting = data.data;
    var pagination = data.pagination;

    $("#body-card").html('');
    $("#pagination").html('');
    $("#textPagination").html('');
    $("#textPagination").html('Mostrando '+pagination.startRecord+' a '+pagination.endRecord+' de '+pagination.totalFilteredRecords+' productos.');
    $('#numberItems').html('');
    $('#numberItems').html(pagination.totalFilteredRecords);

    for (let j = 0; j < dataAccounting.length ; j++) {
        renderDataCard(dataAccounting[j]);
    }

    if (pagination.currentPage > 1)
    {
        renderPreviousPage(pagination.currentPage-1);
    }

    if (pagination.totalPages > 1)
    {
        if (pagination.currentPage > 3)
        {
            renderItemPage(1);

            if (pagination.currentPage > 4) {
                renderDisabledPage();
            }
        }

        for (var i = Math.max(1, pagination.currentPage - 2); i <= Math.min(pagination.totalPages, pagination.currentPage + 2); i++)
        {
            renderItemPage(i, pagination.currentPage);
        }

        if (pagination.currentPage < pagination.totalPages - 2)
        {
            if (pagination.currentPage < pagination.totalPages - 3)
            {
                renderDisabledPage();
            }
            renderItemPage(i, pagination.currentPage);
        }

    }

    if (pagination.currentPage < pagination.totalPages)
    {
        renderNextPage(pagination.currentPage+1);
    }
}

function renderDataCardO(data) {
    var clone = activateTemplate('#item-card');
    let url_image = document.location.origin + '/images/material/' + data.image;
    clone.querySelector("[data-image1]").setAttribute("src", url_image);
    /*clone.querySelector("[data-image2]").setAttribute("src", url_image);*/
    clone.querySelector("[data-name]").innerHTML = data.full_name;
    clone.querySelector("[data-price]").innerHTML = data.price;

    clone.querySelector("[data-add_cart]").setAttribute("data-product_id", data.id);
    clone.querySelector("[data-add_cart]").setAttribute("data-product_price", data.price);
    clone.querySelector("[data-add_cart]").setAttribute("data-product_stock", data.stock);
    clone.querySelector("[data-add_cart]").setAttribute("data-product_name", data.full_name);
    clone.querySelector("[data-add_cart]").setAttribute("data-product_unit", data.unit);
    clone.querySelector("[data-add_cart]").setAttribute("data-product_tax", data.tax);

    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_id", data.id);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_price", data.price);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_stock", data.stock);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_name", data.full_name);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_unit", data.unit);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_tax", data.tax);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_type", data.type);

    $("#body-card").append(clone);

    $('[data-toggle="tooltip"]').tooltip();
}

function renderDataCard2(data) {
    var clone = activateTemplate('#item-card');
    let url_image = document.location.origin + '/images/material/' + data.image;

    clone.querySelector("[data-image1]").setAttribute("src", data.image_url);
    /*clone.querySelector("[data-image2]").setAttribute("src", url_image);*/
    clone.querySelector("[data-name]").innerHTML = data.full_name;
    clone.querySelector("[data-price]").innerHTML = data.price;

    // Botón normal
    clone.querySelector("[data-add_cart]").setAttribute("data-product_id", data.id);
    clone.querySelector("[data-add_cart]").setAttribute("data-material_id", data.material_id || '');
    clone.querySelector("[data-add_cart]").setAttribute("data-product_source", data.source || '');
    clone.querySelector("[data-add_cart]").setAttribute("data-product_price", data.price);
    clone.querySelector("[data-add_cart]").setAttribute("data-product_stock", data.stock);
    clone.querySelector("[data-add_cart]").setAttribute("data-product_name", data.full_name);
    clone.querySelector("[data-add_cart]").setAttribute("data-product_unit", data.unit);
    clone.querySelector("[data-add_cart]").setAttribute("data-product_tax", data.tax);
    clone.querySelector("[data-add_cart]").setAttribute("data-product_type", data.type);
    clone.querySelector("[data-add_cart]").setAttribute("data-product_sku", data.sku || '');
    clone.querySelector("[data-add_cart]").setAttribute("data-product_barcode", data.barcode || '');

    // Botón especial
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_id", data.id);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-material_id", data.material_id || '');
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_source", data.source || '');
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_price", data.price);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_stock", data.stock);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_name", data.full_name);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_unit", data.unit);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_tax", data.tax);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_type", data.type);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_sku", data.sku || '');
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_barcode", data.barcode || '');

    $("#body-card").append(clone);

    $('[data-toggle="tooltip"]').tooltip();
}

function renderDataCard(data) {
    var clone = activateTemplate('#item-card');
    let url_image = document.location.origin + '/images/material/' + data.image;

    clone.querySelector("[data-image1]").setAttribute("src", data.image_url);
    /*clone.querySelector("[data-image2]").setAttribute("src", url_image);*/
    clone.querySelector("[data-name]").innerHTML = data.full_name;
    clone.querySelector("[data-price]").innerHTML = data.price;

    // Botón especial
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_id", data.id);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-material_id", data.material_id || '');
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_source", data.source || '');
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_price", data.price);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_stock", data.stock);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_name", data.full_name);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_unit", data.unit);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_tax", data.tax);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_type", data.type);
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_sku", data.sku || '');
    clone.querySelector("[data-add_cart_special]").setAttribute("data-product_barcode", data.barcode || '');

    $("#body-card").append(clone);

    $('[data-toggle="tooltip"]').tooltip();
}

function renderDataCardEmpty() {
    var clone = activateTemplate('#item-card-empty');
    $("#body-card").append(clone);
}

function renderPreviousPage($numberPage) {
    var clone = activateTemplate('#previous-page');
    clone.querySelector("[data-item]").setAttribute('data-item', $numberPage);
    $("#pagination").append(clone);
}

function renderDisabledPage() {
    var clone = activateTemplate('#disabled-page');
    $("#pagination").append(clone);
}

function renderItemPage($numberPage, $currentPage) {
    var clone = activateTemplate('#item-page');
    if ( $numberPage == $currentPage )
    {
        clone.querySelector("[data-item]").setAttribute('data-item', $numberPage);
        clone.querySelector("[data-active]").setAttribute('class', 'page-item active');
        clone.querySelector("[data-item]").innerHTML = $numberPage;
    } else {
        clone.querySelector("[data-item]").setAttribute('data-item', $numberPage);
        clone.querySelector("[data-item]").innerHTML = $numberPage;
    }

    $("#pagination").append(clone);
}

function renderNextPage($numberPage) {
    var clone = activateTemplate('#next-page');
    clone.querySelector("[data-item]").setAttribute('data-item', $numberPage);
    $("#pagination").append(clone);
}

function activateTemplate(id) {
    var t = document.querySelector(id);
    return document.importNode(t.content, true);
}
