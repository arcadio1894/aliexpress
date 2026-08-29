let $materials=[];
let $materialsTypeahead=[];
let $consumables=[];
let $electrics=[];
let $items=[];
let $equipments=[];
let $equipmentStatus=false;
let $total=0;
let $totalUtility=0;
let $subtotal=0;
let $subtotal2=0;
let $subtotal3=0;
var $permissions;
var $igv;

$(document).ready(function () {
    $permissions = JSON.parse($('#permissions').val());
    $igv = $('#igv').val();
    $.ajax({
        url: "/dashboard/get/quote/sale/materials/totals",
        type: 'GET',
        dataType: 'json',
        success: function (json) {
            for (var i=0; i<json.length; i++)
            {
                $consumables.push(json[i]);
            }
        }
    });

    $('.materialTypeahead').typeahead({
            hint: true,
            highlight: true, /* Enable substring highlighting */
            minLength: 1 /* Specify minimum characters required for showing suggestions */
        },
        {
            limit: 12,
            source: substringMatcher($materialsTypeahead)
        });

    $(document).on('click', '[data-confirm]', confirmEquipment);

    $(document).on('click', '[data-addConsumable]', addConsumable);

    $formCreate = $('#formCreate');
    $("#btn-submit").on("click", storeQuote);

    $('.consumable_search').select2({
        placeholder: 'Selecciona un producto',
        ajax: {
            url: '/dashboard/get/quote/sale/materials',
            dataType: 'json',
            type: 'GET',
            processResults(data) {
                //console.log(data);
                return {
                    results: $.map(data, function (item) {
                        //console.log(item.full_description);
                        return {
                            text: item.display_name,
                            id: item.id,
                        }
                    })
                }
            }
        }
    });

    $(document).on('click', '[data-deleteConsumable]', deleteConsumable);

    $(document).on('click', '[data-saveEquipment]', saveEquipment);

    $(document).on('input', '[data-consumableQuantity]', function() {
        var card = $(this).parent().parent().parent().parent().parent().parent().parent().parent();
        card.removeClass('card-success');
        card.addClass('card-gray-dark');
    });

    $(document).on('input', '[data-detailequipment]', function() {
        var card = $(this).parent().parent().parent().parent();
        card.removeClass('card-success');
        card.addClass('card-gray-dark');
    });

    $(document).on("summernote.change", ".textarea_edit",function (e) {   // callback as jquery custom event
        var card = $(this).parent().parent().parent().parent();
        card.removeClass('card-success');
        card.addClass('card-gray-dark');
    });

    $selectCustomer = $('#customer_id');
    $selectContact = $('#contact_id');

    $selectCustomer.change(function () {
        $selectContact.empty();
        var customer =  $selectCustomer.val();
        $.get( "/dashboard/get/contact/"+customer, function( data ) {
            $selectContact.append($("<option>", {
                value: '',
                text: 'Seleccione contacto'
            }));
            for ( var i=0; i<data.length; i++ )
            {
                $selectContact.append($("<option>", {
                    value: data[i].id,
                    text: data[i].contact
                }));
            }
        });

    });

    // Abrir modal al dar click en +
    $("#btn-add-customer").on("click", function() {
        $("#formCreateCustomer")[0].reset(); // limpiar formulario
        $("#modalCustomer").modal("show");
    });

    // Enviar formulario por AJAX
    $("#btn-submit-customer").on("click", function(e) {
        e.preventDefault();

        let form = $("#formCreateCustomer");
        let url = form.data("url");
        let formData = form.serialize();

        $.ajax({
            type: "POST",
            url: url,
            data: formData,
            success: function(response) {
                toastr.success(response.message);

                // Cerrar modal
                $("#modalCustomer").modal("hide");

                // Obtener el cliente nuevo
                let customer = response.customer;

                // Crear nueva opción
                let newOption = new Option(customer.business_name, customer.id, true, true);

                // Agregar al select2 y seleccionarlo
                $('#customer_id').append(newOption).trigger('change');

                // Limpiar el formulario
                $("#formCreateCustomer")[0].reset();

            },
            error: function(xhr) {
                let errors = xhr.responseJSON?.message || "Error al guardar";
                toastr.error(errors);
            }
        });
    });

    $('#btn-notAddConsumable').on('click', function () {
        $modalConsumableQty.modal('hide');
    });

    /*$('#btn-add_consumable_modal').on('click', function () {

        if (!$currentConsumable || !$currentConsumableRender) {
            toastr.error('No hay consumible seleccionado', 'Error');
            return;
        }

        let added = false;

        // 1) Presentaciones con packs > 0 => 1 fila por presentación
        $('[data-pres-row]').each(function () {
            let packs = parseInt($(this).find('[data-pres-packs]').val() || 0);
            if (packs > 0) {
                added = true;

                let presId = parseInt($(this).attr('data-pres-id'));
                let unitsPerPack = parseInt($(this).attr('data-pres-qty'));
                let presPrice = parseFloat($(this).attr('data-pres-price')); // precio del pack
                let presLabel = $(this).attr('data-pres-label');

                let unitsEquivalent = packs * unitsPerPack;

                // En UI queremos packs y precio pack
                let qtyToShow = packs;        // ✅ cantidad visible
                let pricePack = presPrice;    // ✅ precio por pack

                renderTemplateConsumable(
                    $currentConsumableRender,
                    $currentConsumable,
                    qtyToShow,     // 👈 cantidad visible = packs
                    pricePack,     // 👈 P/U visible = precio pack
                    0,
                    true,
                    {
                        id: presId,
                        text: presLabel,
                        packs: packs,
                        unitsPerPack: unitsPerPack,
                        unitsEquivalent: unitsEquivalent,
                        pricePack: pricePack
                    }
                );
            }
        });

        // 2) Si no hay presentación seleccionada => usar unidad
        if (!added) {
            let qty = parseFloat($('#c_quantity_total').val() || 0);
            if (qty <= 0) {
                toastr.error('Ingresa cantidad o selecciona una presentación', 'Error');
                return;
            }

            let unitPrice = parseFloat($currentConsumable.list_price);

            renderTemplateConsumable(
                $currentConsumableRender,
                $currentConsumable,
                qty,
                unitPrice,
                0,
                true,
                null
            );
        }

        $modalConsumableQty.modal('hide');
    });*/
    $('#btn-add_consumable_modal').on('click', function () {

        if (!$currentConsumable || !$currentConsumableRender) {
            toastr.error('No hay consumible seleccionado', 'Error');
            return;
        }

        let linesToAdd = [];
        let hasPresentation = false;

        /*
         * ==========================================================
         * 1. Revisar presentaciones seleccionadas.
         *    Cada presentación genera una futura fila independiente.
         * ==========================================================
         */
        $('[data-pres-row]').each(function () {
            let packs = parseInt($(this).find('[data-pres-packs]').val() || 0);

            if (packs <= 0) {
                return;
            }

            hasPresentation = true;

            let presId = parseInt($(this).attr('data-pres-id'));
            let unitsPerPack = parseInt($(this).attr('data-pres-qty'));
            let presPrice = parseFloat($(this).attr('data-pres-price'));
            let presLabel = $(this).attr('data-pres-label');

            let unitsEquivalent = packs * unitsPerPack;

            linesToAdd.push({
                quantity_to_show: packs,
                price: presPrice,
                presentation: {
                    id: presId,
                    text: presLabel,
                    packs: packs,
                    unitsPerPack: unitsPerPack,
                    unitsEquivalent: unitsEquivalent,
                    pricePack: presPrice
                },

                /*
                 * Para itemeables esta será la cantidad real de
                 * ítems físicos que deberá seleccionar el usuario.
                 */
                total_units_required: unitsEquivalent
            });
        });

        /*
         * ==========================================================
         * 2. Si no se eligieron presentaciones, trabajar por unidad.
         * ==========================================================
         */
        if (!hasPresentation) {
            let qty = parseFloat($('#c_quantity_total').val() || 0);

            if (qty <= 0) {
                toastr.error('Ingresa cantidad o selecciona una presentación', 'Error');
                return;
            }

            let unitPrice = parseFloat($currentConsumable.list_price) || 0;

            linesToAdd.push({
                quantity_to_show: qty,
                price: unitPrice,
                presentation: null,
                total_units_required: qty
            });
        }

        /*
         * ==========================================================
         * 3. Validar si el producto es itemeable.
         *    tipo_venta_id = 3
         * ==========================================================
         */
        let isItemeable = parseInt($currentConsumable.tipo_venta_id || 0) === 3;

        /*
         * ==========================================================
         * 4. Flujo especial para productos itemeables.
         *
         * Aún NO renderizamos las filas.
         * Guardamos toda la selección temporalmente y luego
         * abriremos el selector de ítems físicos.
         * ==========================================================
         */
        if (isItemeable) {
            let totalUnitsRequired = linesToAdd.reduce(function (total, line) {
                return total + parseFloat(line.total_units_required || 0);
            }, 0);

            /*
             * Un item físico no puede venderse parcialmente.
             * Por eso no permitimos 1.5, 2.25, etc.
             */
            if (!Number.isInteger(totalUnitsRequired)) {
                toastr.error(
                    'Los productos itemeables solo pueden venderse en unidades enteras.',
                    'Cantidad inválida'
                );
                return;
            }

            if (totalUnitsRequired <= 0) {
                toastr.error('Debe seleccionar al menos un ítem.', 'Error');
                return;
            }

            /*
             * Estas variables globales las utilizaremos en el siguiente paso
             * cuando creemos el modal selector de ítems.
             */
            window.$currentItemeableDraft = {
                consumable: $currentConsumable,
                render: $currentConsumableRender,
                lines: linesToAdd,
                total_units_required: totalUnitsRequired
            };

            /*
             * Ocultamos el modal de cantidad antes de abrir
             * el modal de selección de ítems.
             *
             * Esta función la construiremos en el siguiente paso.
             */
            $modalConsumableQty.modal('hide');

            openItemeableItemsSelector(window.$currentItemeableDraft);

            return;
        }

        /*
         * ==========================================================
         * 5. Flujo normal:
         *    Renderizar inmediatamente cada fila.
         * ==========================================================
         */
        linesToAdd.forEach(function (line) {
            renderTemplateConsumable(
                $currentConsumableRender,
                $currentConsumable,
                line.quantity_to_show,
                line.price,
                0,
                true,
                line.presentation
            );
        });

        $modalConsumableQty.modal('hide');
    });

    // Cuando cambia cantidad
    $(document).on('input', '[data-serviceQuantity]', function () {
        const row = $(this).closest('[data-serviceRow]');
        calculateServiceRow(row);
        markEquipDirty(this);
    });

    // Cuando cambia P/U
    $(document).on('input', '[data-servicePU]', function () {
        const row = $(this).closest('[data-serviceRow]');
        calculateServiceRow(row);
        markEquipDirty(this);
    });

    $(document).on('click', '[data-addService]', addService);

    $(document).on('click', '[data-deleteService]', function () {
        markEquipDirty(this);
        $(this).closest('[data-serviceRow]').remove();
    });

    // Cuando cambia Tipo (monto/percent)
    $(document).on('change', 'input[name="discount_type"]', function () {
        const type = $(this).val(); // amount | percent
        $('#discountSection').attr('data-discount_type', type);

        if (type === 'percent') {
            $('#discount_value').attr('step', '0.01');
            $('#discount_value_hint').text('Ingrese porcentaje (0 a 100).');
        } else {
            $('#discount_value').attr('step', '0.01');
            $('#discount_value_hint').text('Ingrese monto en soles.');
        }
    });

    // Cuando cambia Modo (con/sin igv)
    $(document).on('change', 'input[name="discount_input_mode"]', function () {
        const mode = $(this).val(); // with_igv | without_igv
        $('#discountSection').attr('data-discount_input_mode', mode);
    });

    // Cuando cambia valor
    $(document).on('input', '#discount_value', function () {
        let val = parseFloat($(this).val() || 0);
        if (isNaN(val) || val < 0) val = 0;
        $('#discountSection').attr('data-discount_value', val.toFixed(2));
    });

    // Limpiar
    $('#btn-clear-discount').on('click', function () {
        $('#discount_type_amount').prop('checked', true).trigger('change');
        $('#discount_mode_without').prop('checked', true).trigger('change');
        $('#discount_value').val(0).trigger('input');
    });

    $(document).on('change', '[data-serviceBillable]', function () {
        markEquipDirty(this);
    });

    $(document).on('change input', '#discountSection input, #discount_value', function () {
        // Si tu descuento no está dentro del card, busca un card “activo”
        // O marca todos los cards si aplica globalmente:
        $('[data-equip]').each(function(){ markEquipDirty($(this)); });
    });

    $('#modalQuantityConsumable').on('hidden.bs.modal', function () {
        // ✅ asegurar que nada dentro conserve foco
        document.activeElement && document.activeElement.blur && document.activeElement.blur();

        // poner foco en algo fuera
        $('#material_search').focus();
    });

    /*
    |--------------------------------------------------------------------------
    | Limita la cantidad de ítems seleccionables
    |--------------------------------------------------------------------------
    */
    $(document).on('change', '.itemeable-item-checkbox', function () {
        let requiredCount = parseInt(
            $('#btn-confirm-itemeable-items').data('required-count') || 0
        );

        updateItemeableItemsCounter(requiredCount);
    });

    $(document).on('click', '#btn-confirm-itemeable-items', function () {
        let draft = window.$currentItemeableDraft;

        if (!draft) {
            toastr.error('No se encontró la información temporal del producto.', 'Error');
            return;
        }

        let requiredCount = parseInt(draft.total_units_required || 0);

        let selectedItems = [];

        $('.itemeable-item-checkbox:checked').each(function () {
            selectedItems.push({
                id: parseInt($(this).data('item-id')),
                code: $(this).data('item-code')
            });
        });

        if (selectedItems.length !== requiredCount) {
            toastr.error(
                'Debe seleccionar exactamente ' + requiredCount + ' ítems.',
                'Selección incompleta'
            );
            return;
        }

        let currentPosition = 0;

        draft.lines.forEach(function (line) {
            let lineUnitsRequired = parseInt(line.total_units_required || 0);

            let itemsForThisLine = selectedItems.slice(
                currentPosition,
                currentPosition + lineUnitsRequired
            );

            currentPosition += lineUnitsRequired;

            renderTemplateConsumable(
                draft.render,
                draft.consumable,
                line.quantity_to_show,
                line.price,
                0,
                true,
                line.presentation,
                itemsForThisLine
            );
        });

        $modalSelectItemeableItems.modal('hide');

        markEquipDirty(draft.render);

        toastr.success(
            'Se agregaron ' + selectedItems.length + ' ítems a la cotización.',
            'Producto agregado'
        );

        window.$currentItemeableDraft = null;
    });

    $(document).on('click', '#btn-cancel-itemeable-items', function () {
        window.$currentItemeableDraft = null;

        $modalSelectItemeableItems.modal('hide');

        /*
         * Volvemos al modal anterior para que pueda corregir
         * cantidad o presentación sin tener que seleccionar el producto otra vez.
         */
        $modalConsumableQty.modal('show');
    });

    $(document).on('keydown', '#itemeable-item-search', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            selectItemByScannedCode();
        }
    });

    $(document).on('change', '#itemeable-item-search', function () {
        selectItemByScannedCode();
    });
});

var $formCreate;
var $modalAddMaterial;
var $material;
var $renderMaterial;
var $selectCustomer;
var $selectContact;
var $descuento = 0;

var $modalConsumableQty = $('#modalQuantityConsumable');
var $currentConsumableRender = null; // contenedor DOM donde se append la fila
var $currentConsumable = null;       // objeto del material seleccionado ($consumables)

let $modalSelectItemeableItems = $('#modalSelectItemeableItems');

var substringMatcher = function(strs) {
    return function findMatches(q, cb) {
        var matches, substringRegex;

        // an array that will be populated with substring matches
        matches = [];

        // regex used to determine if a string contains the substring `q`
        substrRegex = new RegExp(q, 'i');

        // iterate through the pool of strings and for any string that
        // contains the substring `q`, add it to the `matches` array
        $.each(strs, function(i, str) {
            if (substrRegex.test(str)) {
                matches.push(str);
            }
        });

        cb(matches);
    };
};

/*
|--------------------------------------------------------------------------
| Abre el selector de ítems físicos para un producto itemeable
|--------------------------------------------------------------------------
*/
function openItemeableItemsSelector(draft) {
    if (!draft || !draft.consumable) {
        toastr.error('No se pudo preparar la selección de ítems.', 'Error');
        return;
    }

    let consumable = draft.consumable;

    console.log(consumable);
    /*
     * En tu flujo actual, consumable.id corresponde al StockItem seleccionado.
     * Si en tu estructura el ID del stock item tiene otro nombre,
     * aquí sería donde se debería cambiar.
     */
    let stockItemId = consumable.id;

    if (!stockItemId) {
        toastr.error('No se pudo identificar el stock item del producto.', 'Error');
        return;
    }

    let requiredCount = parseInt(draft.total_units_required || 0);

    if (requiredCount <= 0) {
        toastr.error('La cantidad de ítems requeridos no es válida.', 'Error');
        return;
    }

    $('#itemeable-product-name').text(consumable.display_name || consumable.description || '');
    $('#itemeable-required-count').text(requiredCount);
    $('#itemeable-selected-count').text(0);
    $('#itemeable-selected-required-count').text(requiredCount);

    $('#itemeable-items-loading').show();
    $('#itemeable-items-empty').hide();
    $('#itemeable-items-error').hide();
    $('#itemeable-items-table-container').hide();
    $('#itemeable-items-table-body').empty();

    $('#btn-confirm-itemeable-items')
        .prop('disabled', true)
        .data('required-count', requiredCount);

    $modalSelectItemeableItems.modal({
        backdrop: 'static',
        keyboard: false
    });

    $modalSelectItemeableItems.modal('show');

    let url = window.APP_QUOTE.URLS.AVAILABLE_ITEMS
        .replace(':stockItemId', stockItemId);

    $.ajax({
        url: url,
        method: 'GET',
        success: function (response) {
            $('#itemeable-items-loading').hide();

            if (!response.success) {
                $('#itemeable-items-error').show();
                return;
            }

            let items = response.items || [];

            if (!items.length) {
                $('#itemeable-items-empty').show();
                return;
            }

            renderItemeableItems(items, requiredCount);

            $('#itemeable-items-table-container').show();

            $('#itemeable-item-search')
                .val('')
                .focus();
        },
        error: function () {
            $('#itemeable-items-loading').hide();
            $('#itemeable-items-error').show();
        }
    });
}

/*
|--------------------------------------------------------------------------
| Renderiza los checkbox de los ítems disponibles
|--------------------------------------------------------------------------
*/
function renderItemeableItems(items, requiredCount) {
    let html = '';

    items.forEach(function (item) {
        let itemCode = item.code || ('Ítem #' + item.id);

        const lotText = item.stock_lot_code
            || item.lot_code
            || item.stock_lot_id
            || '-';

        const locationText = item.warehouse_name
            || item.location
            || '-';

        html += `
            <tr data-item-row data-item-id="${item.id}" data-item-code="${itemCode}">
                <td class="text-center">
                    <input
                        type="checkbox"
                        class="itemeable-item-checkbox"
                        value="${item.id}"
                        data-item-id="${item.id}"
                        data-item-code="${itemCode}"
                            >
                </td>
    
                <td>${itemCode}</td>
    
                <td>${lotText}</td>
                <td>${locationText}</td>
            </tr>
                `;
    });

    $('#itemeable-items-table-body').html(html);
    updateItemeableItemsCounter(requiredCount);
}

function selectItemByScannedCode() {
    let code = ($('#itemeable-item-search').val() || '').trim();

    if (!code) {
        return;
    }

    let normalizedCode = code.toLowerCase();

    let $row = $('[data-item-row]').filter(function () {
        let itemCode = String($(this).attr('data-item-code') || '')
            .trim()
            .toLowerCase();

        return itemCode === normalizedCode;
    }).first();

    if (!$row.length) {
        toastr.warning('No se encontró un ítem disponible con ese código.', 'Ítem no encontrado');
        return;
    }

    let $checkbox = $row.find('.itemeable-item-checkbox');

    if ($checkbox.prop('disabled') && !$checkbox.is(':checked')) {
        toastr.warning(
            'Ya alcanzó la cantidad máxima de ítems permitidos.',
            'Límite alcanzado'
        );
        return;
    }

    if (!$checkbox.is(':checked')) {
        $checkbox.prop('checked', true).trigger('change');
    }

    /*
     * Mover al inicio para que los ítems seleccionados
     * queden visibles aunque existan cientos de registros.
     */
    $('#itemeable-items-table-body').prepend($row);

    /*
     * Resalta brevemente la fila encontrada.
     */
    $row.addClass('table-success');

    setTimeout(function () {
        $row.removeClass('table-success');
    }, 1200);

    $('#itemeable-item-search')
        .val('')
        .focus();
}

/*
|--------------------------------------------------------------------------
| Actualiza contador y bloquea selección adicional
|--------------------------------------------------------------------------
*/
function updateItemeableItemsCounter(requiredCount) {
    let selectedCount = $('.itemeable-item-checkbox:checked').length;

    $('#itemeable-selected-count').text(selectedCount);

    let $checkboxes = $('.itemeable-item-checkbox');

    /*
     * Si ya llegó al máximo, bloqueamos los demás checkbox.
     */
    if (selectedCount >= requiredCount) {
        $checkboxes.not(':checked').prop('disabled', true);
    } else {
        $checkboxes.prop('disabled', false);
    }

    $('#btn-confirm-itemeable-items')
        .prop('disabled', selectedCount !== requiredCount);
}

function fetchPresentations(materialId) {
    return $.ajax({
        url: `/dashboard/materials-presentations/material/${materialId}/presentations`,
        method: 'GET',
        dataType: 'json'
    }).then(function(res) {
        // devolvemos SOLO el arreglo
        return res.presentations || [];
    });
}

function renderPresentationsInModalConsumable(presentations) {
    if (!presentations || presentations.length === 0) {
        $('#c_presentationsArea').html('<div class="text-muted">Este producto no tiene presentaciones configuradas.</div>');
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
        const label = (p.label && p.label.trim()) ? p.label : `${p.quantity} und`;

        html += `
      <tr data-pres-row
          data-pres-id="${p.id}"
          data-pres-qty="${p.quantity}"
          data-pres-price="${p.price}"
          data-pres-label="${label}">
        <td>
          <strong>${label}</strong>
          <div class="text-muted" style="font-size:12px;">Equivale a ${p.quantity} unidades</div>
        </td>
        <td>S/. ${parseFloat(p.price).toFixed(2)}</td>
        <td>
          <input type="number" min="0" step="1"
                 class="form-control form-control-sm"
                 value="0" data-pres-packs>
        </td>
      </tr>
    `;
    });

    html += `</tbody></table></div>`;
    $('#c_presentationsArea').html(html);
}

function showModalQuantityConsumable(render, consumable) {
    $currentConsumableRender = render;
    $currentConsumable = consumable;

    $('#c_quantity_is_itemeable').val(
        parseInt(consumable.tipo_venta_id) === 3 ? 1 : 0
    );

    $('#c_quantity_productId').val(consumable.id);
    $('#c_quantity_total').val(0);
    $('#c_quantity_stock_show').val(consumable.stock_available);
    $('#c_presentationsArea').html('<div class="text-muted">Cargando presentaciones...</div>');

    fetchPresentations(consumable.material_id)
        .then(function(presentations) {
            // solo activas (por si acaso)
            let actives = presentations.filter(x => x.active === true || x.active === 1 || x.active === "1");

            renderPresentationsInModalConsumable(actives);
            $modalConsumableQty.modal('show');
        })
        .catch(function() {
            $('#c_presentationsArea').html('<div class="text-danger">No se pudo cargar presentaciones.</div>');
            $modalConsumableQty.modal('show');
        });
}

function calculateServiceRow(row) {
    const $row = $(row);

    let qty = parseFloat($row.find('[data-serviceQuantity]').val() || 0);
    if (isNaN(qty) || qty < 0) qty = 0;

    let pu = parseFloat($row.find('[data-servicePU]').val() || 0); // con IGV
    if (isNaN(pu) || pu < 0) pu = 0;

    const igvPct = (typeof $igv !== 'undefined' && $igv !== null) ? parseFloat($igv) : 18;
    const igvFactor = 1 + (igvPct / 100);

    // V/U (sin IGV)
    const vu = (igvFactor > 0) ? (pu / igvFactor) : 0;

    // Importe (con IGV)
    const importe = qty * pu;

    $row.find('[data-serviceVU]').val(vu.toFixed(2));
    $row.find('[data-serviceImporte]').val(importe.toFixed(2));
}

function addService() {
    const $card = $(this).closest('.card-body');
    const desc = $card.find('#material_search').val().trim();
    const unitId = $card.find('.unitMeasure').val();
    const unitText = $card.find('.unitMeasure option:selected').text().trim();
    const qty = parseFloat($card.find('#quantity').val() || 0);

    if (!desc) {
        toastr.error('Debe ingresar una descripción', 'Error');
        return;
    }
    if (!unitId) {
        toastr.error('Debe seleccionar una unidad', 'Error');
        return;
    }
    if (!qty || qty <= 0) {
        toastr.error('Debe ingresar una cantidad', 'Error');
        return;
    }

    // Precio (con IGV)
    let pu = 0;
    const $priceInput = $card.find('#price');
    if ($priceInput.length) {
        pu = parseFloat($priceInput.val() || 0);
        if (!pu || pu <= 0) {
            toastr.error('Debe ingresar un precio válido', 'Error');
            return;
        }
    } else {
        // si no puede ver precios, igual lo dejamos en 0, y se ocultan campos por @cannot
        pu = 0;
    }

    // render
    const $render = $card.find('[data-bodyService]');
    const clone = activateTemplate('#template-service');

    // Set values
    clone.querySelector('[data-serviceDescription]').value = desc;
    clone.querySelector('[data-serviceId]').value = ''; // nuevo, aún no existe en BD
    clone.querySelector('[data-serviceUnit]').value = unitText;
    clone.querySelector('[data-serviceQuantity]').value = qty.toFixed(2);
    clone.querySelector('[data-servicePU]').value = pu.toFixed(2);

    $render.append(clone);

    // recalcular para setear V/U e Importe
    const $lastRow = $render.find('[data-serviceRow]').last();

    const uid = 'billable_' + Date.now() + '_' + Math.floor(Math.random()*1000);
    $lastRow.find('[data-billable-id]').attr('id', uid);
    $lastRow.find('[data-billable-label]').attr('for', uid);
    $lastRow.find('[data-serviceBillable]').prop('checked', true);
    calculateServiceRow($lastRow);

    // limpiar inputs superiores
    $card.find('#material_search').val('');
    $card.find('#quantity').val(0);
    if ($priceInput.length) $priceInput.val(0);
    $card.find('.unitMeasure').val(null).trigger('change');
}

/*function saveEquipment() {
    var button = $(this);

    $.confirm({
        icon: 'fas fa-smile',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'orange',
        title: 'Guardar cambios',
        content: '¿Está seguro de guardar los cambios en los productos?',
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                action: function () {

                    // ===========================
                    // 0) Identificar equipo existente
                    // ===========================
                    var equipmentId = parseInt(button.data('saveequipment'));

                    // Eliminamos el registro previo del mismo equipo
                    $equipments = $equipments.filter(equipment => equipment.id !== equipmentId);

                    var quantity = 1;

                    // ===========================
                    // 1) Datos generales del equipo
                    // ===========================
                    var utility = button.parent().parent().next().children().children().val();
                    var rent    = button.parent().parent().next().children().children().next().val();
                    var letter  = button.parent().parent().next().children().children().next().next().val();
                    var detail  = button.parent().parent().next().children().children().next().next().next().children().next().val();

                    // ===========================
                    // 2) CONSUMABLES (productos)
                    // ===========================
                    var consumables = button.parent().parent().next().children().next().children().next().children().next().next();

                    var consumablesDescription = [];
                    var consumablesIds = [];
                    var consumablesUnit = [];

                    var consumablesQuantity = []; // visible
                    var consumablesValor = [];
                    var consumablesValorReal = [];
                    var consumablesPrice = [];
                    var consumablesPriceReal = [];
                    var consumablesImporte = [];

                    var consumablesDiscount = []; // compatibilidad
                    var consumablesTypePromos = [];

                    var consumablesPresentationId = [];
                    var consumablesUnitsPerPack = [];
                    var consumablesUnitsEquivalent = [];

                    var descuentoPromos = 0;

                    consumables.each(function(){
                        $(this).find('[data-consumableDescription]').each(function(){
                            consumablesDescription.push($(this).val());
                        });

                        $(this).find('[data-consumableId]').each(function(){
                            consumablesIds.push($(this).attr('data-consumableid'));
                        });

                        $(this).find('[data-descuento]').each(function(){
                            const d = parseFloat($(this).attr('data-descuento') || 0);
                            consumablesDiscount.push(d);
                            descuentoPromos += d;
                        });

                        $(this).find('[data-type_promotion]').each(function(){
                            consumablesTypePromos.push($(this).attr('data-type_promotion'));
                        });

                        $(this).find('[data-consumableUnit]').each(function(){
                            consumablesUnit.push($(this).val());
                        });

                        $(this).find('[data-consumableQuantity]').each(function(){
                            consumablesQuantity.push($(this).val());
                        });

                        $(this).find('[data-consumableValor]').each(function(){
                            consumablesValor.push($(this).val());
                        });

                        $(this).find('[data-consumable_valor_real]').each(function(){
                            consumablesValorReal.push($(this).attr('data-consumable_valor_real'));
                        });

                        $(this).find('[data-consumablePrice]').each(function(){
                            consumablesPrice.push($(this).val());
                        });

                        $(this).find('[data-consumable_price_real]').each(function(){
                            consumablesPriceReal.push($(this).attr('data-consumable_price_real'));
                        });

                        $(this).find('[data-consumableImporte]').each(function(){
                            consumablesImporte.push($(this).val());
                        });

                        $(this).find('[data-presentation_id]').each(function(){
                            consumablesPresentationId.push($(this).attr('data-presentation_id') || null);
                        });

                        $(this).find('[data-units_per_pack]').each(function(){
                            consumablesUnitsPerPack.push($(this).attr('data-units_per_pack') || null);
                        });

                        $(this).find('[data-units_equivalent]').each(function(){
                            consumablesUnitsEquivalent.push($(this).attr('data-units_equivalent') || null);
                        });
                    });

                    // Armamos array final de consumables (incluye reales)
                    var consumablesArray = [];
                    for (let i = 0; i < consumablesDescription.length; i++) {
                        consumablesArray.push({
                            id: consumablesIds[i],
                            description: consumablesDescription[i],
                            unit: consumablesUnit[i],

                            // ✅ quantity = packs si hay presentación, unidades si no hay presentación
                            quantity: consumablesQuantity[i],

                            // solo para inventario
                            units_equivalent: consumablesUnitsEquivalent[i] || consumablesQuantity[i],

                            valor: consumablesValor[i],
                            valorReal: consumablesValorReal[i],
                            price: consumablesPrice[i],
                            priceReal: consumablesPriceReal[i],
                            importe: consumablesImporte[i],

                            discount: consumablesDiscount[i],
                            type_promo: consumablesTypePromos[i],
                            presentation_id: consumablesPresentationId[i] || null,
                            units_per_pack: consumablesUnitsPerPack[i] || null
                        });
                    }

                    // ===========================
                    // 3) SERVICIOS ADICIONALES
                    // ===========================
                    var card = button.closest('.card');
                    var servicesContainer = card.find('[data-bodyService]');

                    var servicesRead = { array: [], sum_all: 0, sum_billable: 0 };
                    if (servicesContainer.length > 0) {
                        servicesRead = readServicesFromDom(servicesContainer);
                    }

                    var servicesArray = servicesRead.array;
                    var servicesSumAll = servicesRead.sum_all; // ✅ con IGV
                    var servicesSumBillable = servicesRead.sum_billable;
                    // ===========================
                    // 4) Totales (misma lógica que confirmEquipment)
                    // ===========================
                    const igvPct = parseFloat($igv) || 18;
                    const factor = getFactor(igvPct);

                    let subtotalConsumablesWithIgvReal = 0;

                    for (let i = 0; i < consumablesArray.length; i++) {
                        const qty = Number(consumablesArray[i].quantity) || 0;
                        const priceReal = Number(consumablesArray[i].priceReal ?? consumablesArray[i].price) || 0;

                        const lineWithIgvReal = round10(qty * priceReal);
                        subtotalConsumablesWithIgvReal = round10(subtotalConsumablesWithIgvReal + lineWithIgvReal);
                    }

                    subtotalConsumablesWithIgvReal = round10(subtotalConsumablesWithIgvReal - (Number(descuentoPromos) || 0));
                    if (subtotalConsumablesWithIgvReal < 0) subtotalConsumablesWithIgvReal = 0;

                    const servicesWithIgvReal = round10(Number(servicesSumBillable) || 0);
                    const subtotalWithIgvReal = round10(subtotalConsumablesWithIgvReal + servicesWithIgvReal);

                    const discountWithIgvReal = round10(computeDiscountWithIgv(subtotalWithIgvReal, igvPct));

                    let totalFinalWithIgvReal = round10(subtotalWithIgvReal - discountWithIgvReal);
                    if (totalFinalWithIgvReal < 0) totalFinalWithIgvReal = 0;

                    let baseFinalReal = round10(totalFinalWithIgvReal / factor);
                    if (baseFinalReal < 0) baseFinalReal = 0;

                    const igvFinalReal = round10(totalFinalWithIgvReal - baseFinalReal);
                    const discountBaseReal = round10(discountWithIgvReal / factor);

                    // ===========================
                    // 5) UI (2 decimales) + data reales
                    // ===========================
                    $('#descuento').html(moneyRound(discountBaseReal).toFixed(2));
                    $('#gravada').html(moneyRound(baseFinalReal).toFixed(2));
                    $('#igv_total').html(moneyRound(igvFinalReal).toFixed(2));
                    $('#total_importe').html(moneyRound(totalFinalWithIgvReal).toFixed(2));

                    $('#descuento').attr('data-descuento_real', discountBaseReal);
                    $('#gravada').attr('data-gravada_real', baseFinalReal);
                    $('#igv_total').attr('data-igv_total_real', igvFinalReal);
                    $('#total_importe').attr('data-total_importe_real', totalFinalWithIgvReal);

                    // ===========================
                    // 6) Guardar en memoria ($equipments)
                    // ===========================
                    button.attr('data-saveEquipment', equipmentId);
                    button.next().attr('data-deleteEquipment', equipmentId);

                    const discountGlobalMeta = {
                        subtotal_with_igv: subtotalWithIgvReal,
                        discount_with_igv: discountWithIgvReal,
                        discount_base: discountBaseReal,
                        igv_pct: igvPct,
                        factor: factor
                    };

                    $equipments.push({
                        id: equipmentId,
                        quantity: quantity,
                        utility: utility,
                        rent: rent,
                        letter: letter,

                        total: totalFinalWithIgvReal, // ✅ real con IGV

                        description: "",
                        detail: detail,

                        materials: [],
                        consumables: consumablesArray,
                        electrics: [],
                        workforces: servicesArray,

                        discount_global: {
                            base: discountBaseReal,
                            meta: discountGlobalMeta
                        },

                        tornos: [],
                        dias: []
                    });

                    // UI
                    markEquipClean(card);
                    $items = [];
                    $.alert("Productos guardados!");
                }
            },
            cancel: {
                text: 'CANCELAR',
                action: function () {
                    $.alert("Modificación cancelada.");
                }
            }
        }
    });
}*/
function saveEquipment() {
    var $btn = $(this);

    $.confirm({
        icon: 'fas fa-smile',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'orange',
        title: 'Guardar cambios',
        content: '¿Está seguro de guardar los cambios en los productos?',
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                action: function () {

                    // ===========================
                    // 0) Anclar al CARD del equipo
                    // ===========================
                    var $card = $btn.closest('[data-equip]');

                    // ===========================
                    // 1) Identificar equipo existente
                    // ===========================
                    var equipmentId = parseInt($btn.data('saveequipment'));

                    // Eliminamos el registro previo del mismo equipo
                    $equipments = $equipments.filter(function (equipment) {
                        return equipment.id !== equipmentId;
                    });

                    var quantity = 1;

                    // ===========================
                    // 2) Datos generales del equipo
                    // ===========================
                    var utility = $card.find('[data-utilityEquipment]').val() || 0;
                    var rent = $card.find('[data-rentEquipment]').val() || 0;
                    var letter = $card.find('[data-letterEquipment]').first().val() || 0;
                    var detail = $card.find('[data-detailequipment]').val() || "";

                    const igvPct = parseFloat(
                        $card.find('#igv').val() ||
                        (typeof $igv !== 'undefined' ? $igv : 18)
                    ) || 18;

                    const factor = getFactor(igvPct);

                    // ===========================
                    // 3) CONSUMABLES
                    // ===========================
                    var $consContainer = $card.find('[data-bodyConsumable]').first();

                    var consumablesArray = [];
                    var descuentoPromos = 0;
                    var itemeableValidationError = false;

                    $consContainer.find('[data-consumableRow]').each(function () {
                        var $row = $(this);

                        var discount = parseFloat(
                            $row.find('[data-descuento]').attr('data-descuento') || 0
                        );

                        descuentoPromos += discount;

                        var qty = $row.find('[data-consumableQuantity]').val();

                        var unitsEq = $row.find('[data-units_equivalent]')
                            .attr('data-units_equivalent') || qty;

                        /*
                         * Datos adicionales de productos itemeables
                         */
                        var $consumableIdElement = $row.find('[data-consumableid]').first();

                        var isItemeable = parseInt(
                            $consumableIdElement.attr('data-is_itemeable') || 0
                        ) === 1;

                        var selectedItemIdsRaw = $consumableIdElement.attr(
                            'data-selected_item_ids'
                        ) || '[]';

                        var selectedItemIds = [];

                        try {
                            selectedItemIds = JSON.parse(selectedItemIdsRaw);

                            if (!Array.isArray(selectedItemIds)) {
                                selectedItemIds = [];
                            }

                            selectedItemIds = selectedItemIds
                                .map(function (itemId) {
                                    return parseInt(itemId);
                                })
                                .filter(function (itemId) {
                                    return itemId > 0;
                                });
                        } catch (error) {
                            selectedItemIds = [];
                        }

                        /*
                         * Para itemeables, los IDs seleccionados deben
                         * coincidir con las unidades reales de inventario.
                         */
                        if (isItemeable) {
                            var unitsEquivalent = parseFloat(unitsEq || 0);

                            if (
                                selectedItemIds.length === 0 ||
                                selectedItemIds.length !== unitsEquivalent
                            ) {
                                toastr.error(
                                    'La cantidad de ítems seleccionados no coincide con el producto: ' +
                                    ($row.find('[data-consumableDescription]').val() || ''),
                                    'Validación de ítems'
                                );

                                itemeableValidationError = true;
                                return false;
                            }
                        }

                        consumablesArray.push({
                            id: $consumableIdElement.attr('data-consumableid'),

                            description: $row.find('[data-consumableDescription]').val() || '',
                            unit: $row.find('[data-consumableUnit]').val() || '',

                            // Cantidad visible: packs o unidades.
                            quantity: qty,

                            // Cantidad real consumida desde inventario.
                            units_equivalent: unitsEq,

                            valor: $row.find('[data-consumableValor]').val() || 0,

                            valorReal: $row.find('[data-consumableValor]')
                                    .attr('data-consumable_valor_real')
                                ?? $row.find('[data-consumableValor]').val()
                                ?? 0,

                            price: $row.find('[data-consumablePrice]').val() || 0,

                            priceReal: $row.find('[data-consumablePrice]')
                                    .attr('data-consumable_price_real')
                                ?? $row.find('[data-consumablePrice]').val()
                                ?? 0,

                            importe: $row.find('[data-consumableImporte]').val() || 0,

                            discount: discount,

                            type_promo: $row.find('[data-type_promotion]')
                                .attr('data-type_promotion') || null,

                            presentation_id: $row.find('[data-presentation_id]')
                                .attr('data-presentation_id') || null,

                            units_per_pack: $row.find('[data-units_per_pack]')
                                .attr('data-units_per_pack') || null,

                            // Nuevos datos itemeables
                            is_itemeable: isItemeable,
                            selected_item_ids: selectedItemIds
                        });
                    });

                    if (itemeableValidationError) {
                        return false;
                    }

                    // ===========================
                    // 4) SERVICIOS ADICIONALES
                    // ===========================
                    var servicesContainer = $card.find('[data-bodyService]').first();

                    var servicesRead = {
                        array: [],
                        sum_all: 0,
                        sum_billable: 0
                    };

                    if (servicesContainer.length > 0) {
                        servicesRead = readServicesFromDom(servicesContainer);
                    }

                    var servicesArray = servicesRead.array;
                    var servicesSumBillable = servicesRead.sum_billable;

                    // ===========================
                    // 5) Totales
                    // ===========================
                    let subtotalConsumablesWithIgvReal = 0;

                    for (let i = 0; i < consumablesArray.length; i++) {
                        const qty = Number(consumablesArray[i].quantity) || 0;

                        const priceReal = Number(
                            consumablesArray[i].priceReal ??
                            consumablesArray[i].price
                        ) || 0;

                        const lineWithIgvReal = round10(qty * priceReal);

                        subtotalConsumablesWithIgvReal = round10(
                            subtotalConsumablesWithIgvReal + lineWithIgvReal
                        );
                    }

                    subtotalConsumablesWithIgvReal = round10(
                        subtotalConsumablesWithIgvReal -
                        (Number(descuentoPromos) || 0)
                    );

                    if (subtotalConsumablesWithIgvReal < 0) {
                        subtotalConsumablesWithIgvReal = 0;
                    }

                    const servicesWithIgvReal = round10(
                        Number(servicesSumBillable) || 0
                    );

                    const subtotalWithIgvReal = round10(
                        subtotalConsumablesWithIgvReal + servicesWithIgvReal
                    );

                    const discountWithIgvReal = round10(
                        computeDiscountWithIgv(subtotalWithIgvReal, igvPct)
                    );

                    let totalFinalWithIgvReal = round10(
                        subtotalWithIgvReal - discountWithIgvReal
                    );

                    if (totalFinalWithIgvReal < 0) {
                        totalFinalWithIgvReal = 0;
                    }

                    let baseFinalReal = round10(totalFinalWithIgvReal / factor);

                    if (baseFinalReal < 0) {
                        baseFinalReal = 0;
                    }

                    const igvFinalReal = round10(
                        totalFinalWithIgvReal - baseFinalReal
                    );

                    const discountBaseReal = round10(
                        discountWithIgvReal / factor
                    );

                    // ===========================
                    // 6) UI
                    // ===========================
                    $('#descuento').html(moneyRound(discountBaseReal).toFixed(2));
                    $('#gravada').html(moneyRound(baseFinalReal).toFixed(2));
                    $('#igv_total').html(moneyRound(igvFinalReal).toFixed(2));
                    $('#total_importe').html(moneyRound(totalFinalWithIgvReal).toFixed(2));

                    $('#descuento').attr('data-descuento_real', discountBaseReal);
                    $('#gravada').attr('data-gravada_real', baseFinalReal);
                    $('#igv_total').attr('data-igv_total_real', igvFinalReal);
                    $('#total_importe').attr('data-total_importe_real', totalFinalWithIgvReal);

                    // ===========================
                    // 7) Guardar en $equipments
                    // ===========================
                    $btn.attr('data-saveEquipment', equipmentId);

                    $card.find('[data-deleteEquipment]')
                        .attr('data-deleteEquipment', equipmentId);

                    const discountGlobalMeta = {
                        subtotal_with_igv: subtotalWithIgvReal,
                        discount_with_igv: discountWithIgvReal,
                        discount_base: discountBaseReal,
                        igv_pct: igvPct,
                        factor: factor
                    };

                    $equipments.push({
                        id: equipmentId,
                        quantity: quantity,
                        utility: utility,
                        rent: rent,
                        letter: letter,
                        total: totalFinalWithIgvReal,
                        description: "",
                        detail: detail,
                        materials: [],
                        consumables: consumablesArray,
                        electrics: [],
                        workforces: servicesArray,
                        discount_global: {
                            base: discountBaseReal,
                            meta: discountGlobalMeta
                        },
                        tornos: [],
                        dias: []
                    });

                    markEquipClean($card);

                    $items = [];

                    console.log('Equipments actualizados:', $equipments);

                    $.alert("Productos guardados!");
                }
            },
            cancel: {
                text: 'CANCELAR',
                action: function () {
                    $.alert("Modificación cancelada.");
                }
            }
        }
    });
}

function computeDiscountWithIgv(subtotalWithIgv, igvPct) {
    const $d = $('#discountSection');
    const type = ($d.attr('data-discount_type') || 'amount'); // amount | percent
    const mode = ($d.attr('data-discount_input_mode') || 'without_igv'); // with_igv | without_igv
    const value = parseFloat($d.attr('data-discount_value') || 0);

    if (!value || value <= 0) return 0;

    const factor = 1 + (igvPct / 100);

    let discountWithIgv = 0;

    if (type === 'amount') {
        discountWithIgv = (mode === 'with_igv') ? value : moneyRound(value * factor);
    } else {
        const pct = value / 100;
        discountWithIgv = (mode === 'with_igv')
            ? moneyRound(subtotalWithIgv * pct)
            : moneyRound((subtotalWithIgv / factor) * pct * factor);
    }

    // No pasar el subtotal
    if (discountWithIgv > subtotalWithIgv) discountWithIgv = subtotalWithIgv;

    return moneyRound(discountWithIgv);
}

function deleteConsumable() {
    //console.log($(this).parent().parent().parent());
    /*var card = $(this).parent().parent().parent().parent().parent().parent().parent();
    card.removeClass('card-success');
    card.addClass('card-gray-dark');
    $(this).parent().parent().remove();*/
    markEquipDirty(this);
    $(this).closest('[data-consumableRow]').remove();
}

function addConsumable() {

    let button = $(this);

    var consumableID = button.parent().parent().find('[data-consumable]').val();

    if (!consumableID) {
        toastr.error('Debe seleccionar un producto', 'Error');
        return;
    }

    // tu render original
    var render = button.parent().parent().next().next();

    var consumable = $consumables.find(mat => mat.id === parseInt(consumableID));

    if (!consumable) {
        toastr.error('Producto no encontrado', 'Error');
        return;
    }

    console.log(consumable);
    let consumablePrice = parseFloat(consumable.list_price) || 0;

    // 👇 VALIDACIÓN NUEVA
    if (consumablePrice <= 0) {

        $.confirm({
            icon: 'fas fa-exclamation-triangle',
            theme: 'modern',
            closeIcon: true,
            animation: 'zoom',
            type: 'orange',
            title: 'Precio en cero',
            content: 'El precio de este producto es 0. ¿Procedemos con la venta?',
            buttons: {
                confirm: {
                    text: 'SÍ, CONTINUAR',
                    btnClass: 'btn-orange',
                    action: function () {

                        // limpiar UI
                        button.parent().parent().find('[data-cantidad]').val(0);
                        $(".consumable_search").empty().trigger('change');

                        // abrir modal
                        showModalQuantityConsumable(render, consumable);
                    }
                },
                cancel: {
                    text: 'CANCELAR'
                }
            }
        });

        return;
    }

    // limpiar UI
    button.parent().parent().find('[data-cantidad]').val(0);
    $(".consumable_search").empty().trigger('change');

    // abrir modal
    showModalQuantityConsumable(render, consumable);
}

function checkMaterialPromotions(materialId, cantidad, consumable, cantidadOriginal, render) {
    $.ajax({
        url: '/dashboard/check-promotions',
        method: 'POST',
        data: {
            material_id: materialId,
            quantity: cantidad,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            if (response.success && response.promotions.length > 0) {
                showPromotionModal(response.promotions, consumable, cantidadOriginal, render);
            } else {
                toastr.info("No hay promociones aplicables.");
                renderTemplateConsumable(render, consumable, cantidadOriginal, 0, "ninguno")
            }
        },
        error: function () {
            toastr.error("Error al verificar promociones.");
        }
    });
}

function showPromotionModal(promotions, consumable, cantidad, render) {
    let content = '';

    promotions.forEach((promo, index) => {
        let btn = `<button class="btn btn-primary btn-sm select-promo" 
                        data-index="${index}" 
                        data-type="${promo.type}">
                        Seleccionar
                   </button>`;

        if (promo.type === 'seasonal') {
            content += `<div class="mb-2 border p-2 rounded">
                            <strong>Descuento por Categoría:</strong> ${promo.discount}% hasta el ${promo.valid_until}
                            <br>${btn}
                        </div>`;
        }
        else if (promo.type === 'quantity_discount') {
            content += `<div class="mb-2 border p-2 rounded">
                            <strong>Descuento por Cantidad:</strong> ${promo.percentage}%
                            <br>${btn}
                        </div>`;
        }
        else if (promo.type === 'limit') {
            content += `<div class="mb-2 border p-2 rounded">
                            <strong>Promoción Límite:</strong> ${promo.price_type === 'fixed' ? 'Precio fijo' : 'Descuento'} 
                            ${promo.percentage || promo.promo_price}
                            <br>${btn}
                        </div>`;
        }


    });

    // ➕ Agregar botón de "sin promoción"
    content += `<div class="mb-2 border p-2 rounded text-center">
                <button class="btn btn-secondary btn-sm select-promo" 
                        data-index="-1" 
                        data-type="none">
                        No aplicar promoción
                </button>
            </div>`;

    $("#promotion-content").html(content);
    $("#promotionModal").modal('show');

    // Evento de selección de promoción
    $(".select-promo").off().on("click", function () {
        let index = $(this).data("index");
        let type = $(this).data("type");
        let promo = promotions[index];

        if (type === 'none') {
            // 👉 El usuario eligió no aplicar ninguna promoción
            let precioNormal = parseFloat(consumable.list_price);
            renderTemplateConsumableWithFixedPrice(render, consumable, cantidad, precioNormal, 'ninguno');

            $("#promotionModal").modal('hide');
            return; // cortar aquí
        }

        if (type === 'quantity_discount') {
            getDiscountMaterial(consumable.id, parseFloat(cantidad).toFixed(2)).then(function(discount) {
                let valueDiscount = discount != -1 ? discount.valueDiscount : 0;
                $descuento += valueDiscount;
                renderTemplateConsumable(render, consumable, cantidad, valueDiscount, "quantity_discount");
            });
        }
        else if (type === 'seasonal') {
            let precioBase = parseFloat(consumable.list_price);
            let descuento = promo.discount;
            let precioFinal = precioBase - (precioBase * (descuento / 100));
            renderTemplateConsumable(render, consumable, cantidad, precioFinal, "seasonal", true);
        }
        else if (type === 'limit') {
            let limite = promo.remaining_quantity;
            let precioNormal = consumable.list_price;

            if (promo.price_type === 'fixed') {
                if (cantidad > limite) {
                    // Parte con precio promo
                    renderTemplateConsumableWithFixedPrice(render, consumable, limite, promo.promo_price, "limit");
                    // Parte sin promo
                    renderTemplateConsumableWithFixedPrice(render, consumable, cantidad - limite, precioNormal, 'ninguno');
                } else {
                    renderTemplateConsumableWithFixedPrice(render, consumable, cantidad, promo.promo_price, "limit");
                }
            }
            else if (promo.price_type === 'percentage') {
                let precioConDescuento = precioNormal - (precioNormal * promo.percentage / 100);

                if (cantidad > limite) {
                    renderTemplateConsumableWithFixedPrice(render, consumable, limite, precioConDescuento, "limit");
                    renderTemplateConsumableWithFixedPrice(render, consumable, cantidad - limite, precioNormal, 'ninguno');
                } else {
                    renderTemplateConsumableWithFixedPrice(render, consumable, cantidad, precioConDescuento, "limit");
                }
            }
        }

        $("#promotionModal").modal('hide');
    });
}

function renderTemplateConsumableWithFixedPrice(render, consumable, quantity, fixedPrice, type_promo) {
    var card = render.closest('[data-equip]');
    card.removeClass('card-success').addClass('card-gray-dark');

    let precioBase = parseFloat(fixedPrice);
    let valorUnitario = precioBase / ((100 + parseFloat($igv)) / 100);
    let importeTotal = precioBase * parseFloat(quantity);

    var clone = activateTemplate('#template-consumable');
    clone.querySelector("[data-consumableDescription]").setAttribute('value', consumable.full_description);
    clone.querySelector("[data-consumableId]").setAttribute('data-consumableId', consumable.id);
    clone.querySelector("[data-descuento]").setAttribute('data-descuento', "0.00");
    clone.querySelector("[data-type_promotion]").setAttribute('data-type_promotion', type_promo);
    clone.querySelector("[data-consumableUnit]").setAttribute('value', consumable.unit_measure.description);
    clone.querySelector("[data-consumableQuantity]").setAttribute('value', (parseFloat(quantity)).toFixed(2));

    clone.querySelector("[data-consumableValor]").setAttribute('value', (parseFloat(valorUnitario).toFixed(2)));
    clone.querySelector("[data-consumablePrice]").setAttribute('value', (parseFloat(precioBase).toFixed(2)));
    clone.querySelector("[data-consumableImporte]").setAttribute('value', (parseFloat(importeTotal).toFixed(2)));

    render.append(clone);
}

function renderTemplateConsumableOrigin(render, consumable, quantity, discountOrPrice, type_promo, isPrice = false, pres = null) {

    console.log(consumable);
    var clone = activateTemplate('#template-consumable');

    let qtyVisible = parseFloat(quantity);

    // Caso presentación: P/U es precio del pack y cantidad son packs
    if (pres) {
        const pricePack = parseFloat(pres.pricePack);
        const packs = parseInt(pres.packs);
        const unitsEquivalent = parseInt(pres.unitsEquivalent);

        // visibles
        clone.querySelector("[data-consumableDescription]").value = consumable.full_description;
        clone.querySelector("[data-consumableUnit]").value = consumable.unit_measure.name;
        clone.querySelector("[data-consumableQuantity]").value = packs.toFixed(2); // ✅ muestra packs
        clone.querySelector("[data-consumablePrice]").value = pricePack.toFixed(2); // ✅ precio pack
        $(clone).find('[data-consumablePrice]').attr('data-consumable_price_real', pricePack.toFixed(10));

        // V/U (valor unitario) si lo quieres mostrar como valor unitario del pack sin IGV:
        let valorUnitario = pricePack / ((100 + parseFloat($igv)) / 100);
        clone.querySelector("[data-consumableValor]").value = valorUnitario.toFixed(2);
        $(clone).find('[data-consumableValor]').attr('data-consumable_valor_real', valorUnitario.toFixed(10));

        // importe = packs * pricePack
        let importeTotal = pricePack * packs;
        clone.querySelector("[data-consumableImporte]").value = importeTotal.toFixed(2);

        // data attrs para confirm/save
        $(clone).find('[data-consumableId]').attr('data-consumableid', consumable.id);
        $(clone).find('[data-descuento]').attr('data-descuento', "0.00");
        $(clone).find('[data-type_promotion]').attr('data-type_promotion', type_promo);

        // presentación (vis + hidden)
        clone.querySelector("[data-presentation_text]").value = pres.text;

        $(clone).find('[data-presentation_id]').attr('data-presentation_id', pres.id);
        $(clone).find('[data-packs]').attr('data-packs', packs);
        $(clone).find('[data-units_per_pack]').attr('data-units_per_pack', pres.unitsPerPack);
        $(clone).find('[data-units_equivalent]').attr('data-units_equivalent', unitsEquivalent);
        markEquipDirty(render);
        render.append(clone);
        return;
    }

    // Caso unidad normal (sin presentación)
    let precioUnitario = isPrice ? parseFloat(discountOrPrice) : parseFloat(consumable.list_price);
    let valorUnitario = precioUnitario / ((100 + parseFloat($igv)) / 100);
    let importeTotal  = precioUnitario * qtyVisible;

    clone.querySelector("[data-consumableDescription]").value = consumable.full_description;
    clone.querySelector("[data-consumableUnit]").value = consumable.unit_measure.name;
    clone.querySelector("[data-consumableQuantity]").value = qtyVisible.toFixed(2);
    clone.querySelector("[data-consumableValor]").value = valorUnitario.toFixed(2);
    $(clone).find('[data-consumableValor]').attr('data-consumable_valor_real', valorUnitario.toFixed(10));

    clone.querySelector("[data-consumablePrice]").value = precioUnitario.toFixed(2);
    $(clone).find('[data-consumablePrice]').attr('data-consumable_price_real', precioUnitario.toFixed(10));

    clone.querySelector("[data-consumableImporte]").value = importeTotal.toFixed(2);

    $(clone).find('[data-consumableId]').attr('data-consumableid', consumable.id);
    $(clone).find('[data-descuento]').attr('data-descuento', isPrice ? "0.00" : parseFloat(discountOrPrice).toFixed(2));
    $(clone).find('[data-type_promotion]').attr('data-type_promotion', type_promo);

    clone.querySelector("[data-presentation_text]").value = "Unidad";
    $(clone).find('[data-presentation_id]').attr('data-presentation_id', "");
    $(clone).find('[data-packs]').attr('data-packs', "");
    $(clone).find('[data-units_per_pack]').attr('data-units_per_pack', "");
    $(clone).find('[data-units_equivalent]').attr('data-units_equivalent', qtyVisible);

    console.log(render);
    markEquipDirty(render);
    render.append(clone);
}

function renderTemplateConsumable(
    render,
    consumable,
    quantity,
    discountOrPrice,
    type_promo,
    isPrice = false,
    pres = null,
    selectedItems = []
) {
    console.log(consumable);

    var clone = activateTemplate('#template-consumable');

    let qtyVisible = parseFloat(quantity);
    let isItemeable = parseInt(consumable.tipo_venta_id || 0) === 3;

    /*
     |--------------------------------------------------------------------------
     | Información de ítems seleccionados
     |--------------------------------------------------------------------------
     | Se guarda dentro de la fila temporal para luego incluirla cuando
     | se arme el array equipments al guardar la cotización.
     */
    let selectedItemIds = Array.isArray(selectedItems)
        ? selectedItems.map(function (item) {
            return parseInt(item.id);
        }).filter(Boolean)
        : [];

    let selectedItemsText = Array.isArray(selectedItems)
        ? selectedItems.map(function (item) {
            return item.code || ('Ítem #' + item.id);
        }).join(', ')
        : '';

    /*
     |--------------------------------------------------------------------------
     | Función interna para guardar información itemeable en la fila
     |--------------------------------------------------------------------------
     */
    function applyItemeableData() {
        let $consumableIdElement = $(clone).find('[data-consumableId]');
        let $descriptionElement = $(clone).find('[data-consumableDescription]');

        $consumableIdElement.attr(
            'data-is_itemeable',
            isItemeable ? '1' : '0'
        );

        $consumableIdElement.attr(
            'data-selected_item_ids',
            JSON.stringify(selectedItemIds)
        );

        $consumableIdElement.attr(
            'data-selected_items_text',
            selectedItemsText
        );

        /*
         * Tooltip nativo al pasar el mouse sobre la descripción.
         * Funciona aunque el campo sea input readonly.
         */
        if (isItemeable && selectedItemsText !== '') {
            $descriptionElement.attr(
                'title',
                'Ítems seleccionados: ' + selectedItemsText
            );

            $descriptionElement.attr(
                'data-toggle',
                'tooltip'
            );

            $descriptionElement.attr(
                'data-placement',
                'top'
            );
        } else {
            $descriptionElement.removeAttr('title');
            $descriptionElement.removeAttr('data-toggle');
            $descriptionElement.removeAttr('data-placement');
        }
    }

    /*
     |--------------------------------------------------------------------------
     | Caso presentación
     |--------------------------------------------------------------------------
     */
    if (pres) {
        const pricePack = parseFloat(pres.pricePack);
        const packs = parseInt(pres.packs);
        const unitsEquivalent = parseInt(pres.unitsEquivalent);

        clone.querySelector("[data-consumableDescription]").value = consumable.code+" "+consumable.full_description;
        clone.querySelector("[data-consumableUnit]").value = consumable.unit_measure.name;
        clone.querySelector("[data-consumableQuantity]").value = packs.toFixed(2);
        clone.querySelector("[data-consumablePrice]").value = pricePack.toFixed(2);

        $(clone)
            .find('[data-consumablePrice]')
            .attr('data-consumable_price_real', pricePack.toFixed(10));

        let valorUnitario = pricePack / ((100 + parseFloat($igv)) / 100);

        clone.querySelector("[data-consumableValor]").value = valorUnitario.toFixed(2);

        $(clone)
            .find('[data-consumableValor]')
            .attr('data-consumable_valor_real', valorUnitario.toFixed(10));

        let importeTotal = pricePack * packs;

        clone.querySelector("[data-consumableImporte]").value = importeTotal.toFixed(2);

        $(clone)
            .find('[data-consumableId]')
            .attr('data-consumableid', consumable.id);

        $(clone)
            .find('[data-descuento]')
            .attr('data-descuento', '0.00');

        $(clone)
            .find('[data-type_promotion]')
            .attr('data-type_promotion', type_promo);

        clone.querySelector("[data-presentation_text]").value = pres.text;

        $(clone)
            .find('[data-presentation_id]')
            .attr('data-presentation_id', pres.id);

        $(clone)
            .find('[data-packs]')
            .attr('data-packs', packs);

        $(clone)
            .find('[data-units_per_pack]')
            .attr('data-units_per_pack', pres.unitsPerPack);

        $(clone)
            .find('[data-units_equivalent]')
            .attr('data-units_equivalent', unitsEquivalent);

        applyItemeableData();

        markEquipDirty(render);
        render.append(clone);

        return;
    }

    /*
     |--------------------------------------------------------------------------
     | Caso unidad normal
     |--------------------------------------------------------------------------
     */
    let precioUnitario = isPrice
        ? parseFloat(discountOrPrice)
        : parseFloat(consumable.list_price);

    let valorUnitario = precioUnitario / ((100 + parseFloat($igv)) / 100);
    let importeTotal = precioUnitario * qtyVisible;

    clone.querySelector("[data-consumableDescription]").value = consumable.code+" "+consumable.full_description;
    clone.querySelector("[data-consumableUnit]").value = consumable.unit_measure.name;
    clone.querySelector("[data-consumableQuantity]").value = qtyVisible.toFixed(2);
    clone.querySelector("[data-consumableValor]").value = valorUnitario.toFixed(2);
    clone.querySelector("[data-consumablePrice]").value = precioUnitario.toFixed(2);
    clone.querySelector("[data-consumableImporte]").value = importeTotal.toFixed(2);

    $(clone)
        .find('[data-consumableValor]')
        .attr('data-consumable_valor_real', valorUnitario.toFixed(10));

    $(clone)
        .find('[data-consumablePrice]')
        .attr('data-consumable_price_real', precioUnitario.toFixed(10));

    $(clone)
        .find('[data-consumableId]')
        .attr('data-consumableid', consumable.id);

    $(clone)
        .find('[data-descuento]')
        .attr(
            'data-descuento',
            isPrice ? '0.00' : parseFloat(discountOrPrice).toFixed(2)
        );

    $(clone)
        .find('[data-type_promotion]')
        .attr('data-type_promotion', type_promo);

    clone.querySelector("[data-presentation_text]").value = 'Unidad';

    $(clone)
        .find('[data-presentation_id]')
        .attr('data-presentation_id', '');

    $(clone)
        .find('[data-packs]')
        .attr('data-packs', '');

    $(clone)
        .find('[data-units_per_pack]')
        .attr('data-units_per_pack', '');

    $(clone)
        .find('[data-units_equivalent]')
        .attr('data-units_equivalent', qtyVisible);

    applyItemeableData();

    console.log(render);

    markEquipDirty(render);
    render.append(clone);
}

function getDiscountMaterial(product_id, quantity) {
    return $.get('/dashboard/get/discount/product/' + product_id, {
        quantity: quantity
    }).then(function(data) {
        console.log(data.data[0].haveDiscount);
        if (data.data[0].haveDiscount == true) {
            console.log(data);
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

//Función auxiliar 1: calcular descuento global (SIN IGV)
function computeGlobalDiscountBase(subtotalWithIgv, igvPct) {
    const $d = $('#discountSection');
    if ($d.length === 0) {
        return { base: 0, debug: 'no_section' };
    }

    const type = ($d.attr('data-discount_type') || 'amount');          // amount | percent
    const mode = ($d.attr('data-discount_input_mode') || 'without_igv'); // with_igv | without_igv
    const value = parseFloat($d.attr('data-discount_value') || 0);

    if (!value || value <= 0) return { base: 0, debug: 'value_zero' };

    const factor = 1 + (igvPct / 100);

    // Base sin IGV del subtotal (si todo es gravado)
    //const baseSubtotal = subtotalWithIgv / factor;
    const baseSubtotal = divTrunc2(subtotalWithIgv, factor);

    let discountBase = 0;

    if (type === 'amount') {
        // Monto: puede venir sin IGV o con IGV
        //discountBase = (mode === 'with_igv') ? (value / factor) : value;
        discountBase = (mode === 'with_igv') ? divTrunc2(value, factor) : value;
    } else {
        // Porcentaje: puede venir calculado sobre base o sobre total
        const pct = value / 100;
        if (mode === 'with_igv') {
            //discountBase = (subtotalWithIgv * pct) / factor;
            discountBase = divTrunc2(subtotalWithIgv * pct, factor);
        } else {
            discountBase = baseSubtotal * pct;
        }
    }

    // No permitir descuento mayor a la base
    if (discountBase > baseSubtotal) discountBase = baseSubtotal;

    //return { base: round2(discountBase), debug: { type, mode, value } };
    return { base: moneyTrunc(discountBase), debug: { type, mode, value } };
}

//Función auxiliar 2: obtener servicios adicionales del DOM
function readServicesFromDom(container) {
    const services = container.find('[data-serviceRow]');

    const arr = [];
    let sumAll = 0;
    let sumBillable = 0;

    services.each(function() {
        const $row = $(this);

        const desc = ($row.find('[data-serviceDescription]').val() || '').trim();
        if (!desc) return;

        const unit = ($row.find('[data-serviceUnit]').val() || '').trim();
        const qty  = parseFloat($row.find('[data-serviceQuantity]').val() || 0);
        const vu   = parseFloat($row.find('[data-serviceVU]').val() || 0); // sin IGV
        const pu   = parseFloat($row.find('[data-servicePU]').val() || 0); // con IGV
        const imp  = parseFloat($row.find('[data-serviceImporte]').val() || 0);

        const billable = $row.find('[data-serviceBillable]').is(':checked') ? 1 : 0;

        arr.push({
            description: desc,
            unit: unit,
            quantity: qty,
            valor: vu,
            price: pu,
            importe: imp,
            billable: billable
        });

        // ✅ Cotización: suma todo
        sumAll += round2(imp);

        // ✅ Facturación (más adelante): suma solo facturables
        if (billable === 1) {
            sumBillable += round2(imp);
        }
    });

    return {
        array: arr,
        sum_all: round2(sumAll),
        sum_billable: round2(sumBillable)
    };
}

const round10 = (n) => Math.round((Number(n) || 0) * 1e10) / 1e10;
const moneyRound = (n) => Math.round((Number(n) || 0) * 100) / 100;

// factor IGV: 1.18 (o el que toque)
const getFactor = (igvPct) => 1 + ((Number(igvPct) || 0) / 100);

/*function confirmEquipment() {
    var button = $(this);

    $.confirm({
        icon: 'fas fa-smile',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'green',
        title: 'Confirmar Productos',
        content: 'Debe confirmar para almacenar los productos en memoria',
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                action: function () {

                    // ===========================
                    // UI: bloquear botón confirmar
                    // ===========================
                    button.hide();
                    button.next().show();        // botón guardar
                    button.next().next().show(); // botón eliminar

                    var quantity = 1;

                    // ===========================
                    // 0) Datos generales del equipo
                    // ===========================
                    var utility = button.parent().parent().next().children().children().val();
                    var rent    = button.parent().parent().next().children().children().next().val();
                    var letter  = button.parent().parent().next().children().children().next().next().val();
                    var detail  = button.parent().parent().next().children().children().next().next().next().children().next().val();

                    // ===========================
                    // 1) CONSUMABLES (productos)
                    // ===========================
                    var consumables = button.parent().parent().next().children().next().children().next().children().next().next();

                    var consumablesDescription = [];
                    var consumablesIds = [];
                    var consumablesUnit = [];

                    var consumablesQuantity = []; // visible: packs o unidades
                    var consumablesValor = [];
                    var consumablesValorReal = [];
                    var consumablesPrice = [];    // P/U con IGV (pack o unitario)
                    var consumablesPriceReal = [];
                    var consumablesImporte = [];

                    var consumablesDiscount = []; // compatibilidad (promos por item)
                    var consumablesTypePromos = [];

                    var consumablesPresentationId = [];
                    var consumablesUnitsPerPack = [];
                    var consumablesUnitsEquivalent = [];

                    var descuentoPromos = 0;

                    consumables.each(function(){
                        $(this).find('[data-consumableDescription]').each(function(){
                            consumablesDescription.push($(this).val());
                        });

                        $(this).find('[data-consumableId]').each(function(){
                            consumablesIds.push($(this).attr('data-consumableid'));
                        });

                        $(this).find('[data-descuento]').each(function(){
                            const d = parseFloat($(this).attr('data-descuento') || 0);
                            consumablesDiscount.push(d);
                            descuentoPromos += d;
                        });

                        $(this).find('[data-type_promotion]').each(function(){
                            consumablesTypePromos.push($(this).attr('data-type_promotion'));
                        });

                        $(this).find('[data-consumableUnit]').each(function(){
                            consumablesUnit.push($(this).val());
                        });

                        $(this).find('[data-consumableQuantity]').each(function(){
                            consumablesQuantity.push($(this).val());
                        });

                        $(this).find('[data-consumableValor]').each(function(){
                            consumablesValor.push($(this).val());
                        });

                        $(this).find('[data-consumable_valor_real]').each(function(){
                            consumablesValorReal.push($(this).attr('data-consumable_valor_real'));
                        });

                        $(this).find('[data-consumablePrice]').each(function(){
                            consumablesPrice.push($(this).val());
                        });

                        $(this).find('[data-consumable_price_real]').each(function(){
                            consumablesPriceReal.push($(this).attr('data-consumable_price_real'));
                        });

                        $(this).find('[data-consumableImporte]').each(function(){
                            consumablesImporte.push($(this).val());
                        });

                        $(this).find('[data-presentation_id]').each(function(){
                            consumablesPresentationId.push($(this).attr('data-presentation_id') || null);
                        });

                        $(this).find('[data-units_per_pack]').each(function(){
                            consumablesUnitsPerPack.push($(this).attr('data-units_per_pack') || null);
                        });

                        $(this).find('[data-units_equivalent]').each(function(){
                            consumablesUnitsEquivalent.push($(this).attr('data-units_equivalent') || null);
                        });
                    });

                    var consumablesArray = [];
                    for (let i = 0; i < consumablesDescription.length; i++) {
                        consumablesArray.push({
                            id: consumablesIds[i],
                            description: consumablesDescription[i],
                            unit: consumablesUnit[i],

                            // ✅ quantity = packs si hay presentación, unidades si no hay presentación
                            quantity: consumablesQuantity[i],

                            // solo para inventario (NO para SUNAT)
                            units_equivalent: consumablesUnitsEquivalent[i] || consumablesQuantity[i],

                            valor: consumablesValor[i],
                            valorReal: consumablesValorReal[i], // ✅ por pack o por unidad según corresponda
                            price: consumablesPrice[i],
                            priceReal: consumablesPriceReal[i], // ✅ por pack o por unidad según corresponda
                            importe: consumablesImporte[i],

                            discount: consumablesDiscount[i],
                            type_promo: consumablesTypePromos[i],
                            presentation_id: consumablesPresentationId[i] || null,
                            units_per_pack: consumablesUnitsPerPack[i] || null
                        });
                    }

                    // ===========================
                    // 2) SERVICIOS ADICIONALES
                    // ===========================
                    var card = button.closest('.card');
                    var servicesContainer = card.find('[data-bodyService]');

                    var servicesRead = { array: [], sum_all: 0, sum_billable: 0 };
                    if (servicesContainer.length > 0) {
                        servicesRead = readServicesFromDom(servicesContainer);
                    }

                    var servicesArray = servicesRead.array;
                    var servicesSumAll = servicesRead.sum_all;
                    var servicesSumBillable = servicesRead.sum_billable;

                    const igvPct = parseFloat($igv) || 18;
                    const factor = getFactor(igvPct);

                    // ===========================
                    // 3) SUBTOTAL con IGV (antes de descuento global)
                    // ===========================
                    // ✅ Para SUNAT: usar quantity (packs/unidades) * priceReal (pack/unidad)
                    let subtotalConsumablesWithIgvReal = 0;

                    for (let i = 0; i < consumablesArray.length; i++) {
                        const qty = Number(consumablesArray[i].quantity) || 0;
                        const priceReal = Number(consumablesArray[i].priceReal ?? consumablesArray[i].price) || 0;

                        const lineWithIgvReal = round10(qty * priceReal);
                        subtotalConsumablesWithIgvReal = round10(subtotalConsumablesWithIgvReal + lineWithIgvReal);
                    }

                    // promos (si existen)
                    subtotalConsumablesWithIgvReal = round10(subtotalConsumablesWithIgvReal - (Number(descuentoPromos) || 0));
                    if (subtotalConsumablesWithIgvReal < 0) subtotalConsumablesWithIgvReal = 0;

                    const servicesWithIgvReal = round10(Number(servicesSumBillable) || 0);
                    const subtotalWithIgvReal = round10(subtotalConsumablesWithIgvReal + servicesWithIgvReal);

                    // ===========================
                    // 4) DESCUENTO GLOBAL (tu función devuelve con IGV)
                    // ===========================
                    const discountWithIgvReal = round10(computeDiscountWithIgv(subtotalWithIgvReal, igvPct));

                    let totalFinalWithIgvReal = round10(subtotalWithIgvReal - discountWithIgvReal);
                    if (totalFinalWithIgvReal < 0) totalFinalWithIgvReal = 0;

                    // ===========================
                    // 5) BASE, IGV (SIN TRUNCAR)
                    // ===========================
                    let baseFinalReal = round10(totalFinalWithIgvReal / factor);
                    if (baseFinalReal < 0) baseFinalReal = 0;

                    const igvFinalReal = round10(totalFinalWithIgvReal - baseFinalReal);
                    const discountBaseReal = round10(discountWithIgvReal / factor);

                    // ===========================
                    // 6) UI (2 decimales) + data reales
                    // ===========================
                    $('#descuento').html(moneyRound(discountBaseReal).toFixed(2));
                    $('#gravada').html(moneyRound(baseFinalReal).toFixed(2));
                    $('#igv_total').html(moneyRound(igvFinalReal).toFixed(2));
                    $('#total_importe').html(moneyRound(totalFinalWithIgvReal).toFixed(2));

                    $('#descuento').attr('data-descuento_real', discountBaseReal);
                    $('#gravada').attr('data-gravada_real', baseFinalReal);
                    $('#igv_total').attr('data-igv_total_real', igvFinalReal);
                    $('#total_importe').attr('data-total_importe_real', totalFinalWithIgvReal);

                    // ===========================
                    // 7) Guardar en memoria ($equipments)
                    // ===========================
                    button.next().attr('data-saveEquipment', $equipments.length);

                    const discountGlobalMeta = {
                        subtotal_with_igv: subtotalWithIgvReal,
                        discount_with_igv: discountWithIgvReal,
                        discount_base: discountBaseReal,
                        igv_pct: igvPct,
                        factor: factor
                    };

                    $equipments.push({
                        id: $equipments.length,
                        quantity: quantity,
                        utility: utility,
                        rent: rent,
                        letter: letter,
                        total: totalFinalWithIgvReal,
                        description: "",
                        detail: detail,
                        materials: [],
                        consumables: consumablesArray,
                        electrics: [],
                        workforces: servicesArray,
                        discount_global: {
                            base: discountBaseReal,
                            meta: discountGlobalMeta
                        },
                        tornos: [],
                        dias: []
                    });

                    // UI
                    card.removeClass('card-gray-dark').addClass('card-success');

                    $items = [];
                    $.alert("Productos confirmados!");
                }
            },
            cancel: {
                text: 'CANCELAR',
                action: function () {
                    $equipmentStatus = false;
                    $.alert("Confirmación cancelada.");
                }
            }
        }
    });
}*/
function confirmEquipment() {
    var $btn = $(this);

    $.confirm({
        icon: 'fas fa-smile',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'green',
        title: 'Confirmar Productos',
        content: 'Debe confirmar para almacenar los productos en memoria',
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                action: function () {

                    // ===========================
                    // 0) Anclar al CARD del equipo
                    // ===========================
                    var $card = $btn.closest('[data-equip]');

                    // ===========================
                    // UI: bloquear/mostrar botones
                    // ===========================
                    $card.find('[data-confirm]').hide();
                    $card.find('[data-saveEquipment]').show();
                    $card.find('[data-deleteEquipment]').show();

                    var quantity = 1;

                    // ===========================
                    // 1) Datos generales del equipo
                    // ===========================
                    var utility = $card.find('[data-utilityEquipment]').val() || 0;
                    var rent = $card.find('[data-rentEquipment]').val() || 0;
                    var letter = $card.find('[data-letterEquipment]').first().val() || 0;
                    var detail = $card.find('[data-detailequipment]').val() || "";

                    var igvPct = parseFloat(
                        $card.find('#igv').val() ||
                        (typeof $igv !== 'undefined' ? $igv : 18)
                    ) || 18;

                    var factor = getFactor(igvPct);

                    // ===========================
                    // 2) CONSUMABLES
                    // ===========================
                    var $consContainer = $card.find('[data-bodyConsumable]').first();

                    var consumablesArray = [];
                    var descuentoPromos = 0;

                    $consContainer.find('[data-consumableRow]').each(function () {
                        var $row = $(this);

                        var discount = parseFloat(
                            $row.find('[data-descuento]').attr('data-descuento') || 0
                        );

                        descuentoPromos += discount;

                        var qty = $row.find('[data-consumableQuantity]').val();

                        var unitsEq = $row.find('[data-units_equivalent]')
                            .attr('data-units_equivalent') || qty;

                        /*
                         * =====================================================
                         * DATOS DE ÍTEMS ITEMEABLES
                         * =====================================================
                         */
                        var $consumableIdElement = $row.find('[data-consumableid]').first();

                        var isItemeable = parseInt(
                            $consumableIdElement.attr('data-is_itemeable') || 0
                        ) === 1;

                        var selectedItemIdsRaw = $consumableIdElement.attr(
                            'data-selected_item_ids'
                        ) || '[]';

                        var selectedItemIds = [];

                        try {
                            selectedItemIds = JSON.parse(selectedItemIdsRaw);

                            if (!Array.isArray(selectedItemIds)) {
                                selectedItemIds = [];
                            }

                            selectedItemIds = selectedItemIds
                                .map(function (itemId) {
                                    return parseInt(itemId);
                                })
                                .filter(function (itemId) {
                                    return itemId > 0;
                                });
                        } catch (error) {
                            selectedItemIds = [];
                        }

                        /*
                         * Seguridad adicional:
                         * si el producto es itemeable, debe tener IDs de ítems.
                         */
                        if (isItemeable && selectedItemIds.length === 0) {
                            toastr.error(
                                'El producto "' +
                                ($row.find('[data-consumableDescription]').val() || '') +
                                '" requiere ítems seleccionados.',
                                'Ítems pendientes'
                            );

                            throw new Error('Producto itemeable sin ítems seleccionados.');
                        }

                        consumablesArray.push({
                            id: $consumableIdElement.attr('data-consumableid'),

                            description: $row.find('[data-consumableDescription]').val() || '',
                            unit: $row.find('[data-consumableUnit]').val() || '',

                            // Cantidad visible: unidades o packs
                            quantity: qty,

                            // Cantidad real de unidades para inventario
                            units_equivalent: unitsEq,

                            valor: $row.find('[data-consumableValor]').val() || 0,

                            valorReal: $row.find('[data-consumableValor]')
                                    .attr('data-consumable_valor_real')
                                ?? $row.find('[data-consumableValor]').val()
                                ?? 0,

                            price: $row.find('[data-consumablePrice]').val() || 0,

                            priceReal: $row.find('[data-consumablePrice]')
                                    .attr('data-consumable_price_real')
                                ?? $row.find('[data-consumablePrice]').val()
                                ?? 0,

                            importe: $row.find('[data-consumableImporte]').val() || 0,

                            discount: discount,

                            type_promo: $row.find('[data-type_promotion]')
                                .attr('data-type_promotion') || null,

                            presentation_id: $row.find('[data-presentation_id]')
                                .attr('data-presentation_id') || null,

                            units_per_pack: $row.find('[data-units_per_pack]')
                                .attr('data-units_per_pack') || null,

                            /*
                             * Nuevos datos para productos itemeables
                             */
                            is_itemeable: isItemeable,
                            selected_item_ids: selectedItemIds
                        });
                    });

                    /*
                     * Validación final:
                     * para itemeables, unidades equivalentes debe ser igual
                     * a la cantidad de ítems físicos seleccionados.
                     */
                    for (let i = 0; i < consumablesArray.length; i++) {
                        let consumable = consumablesArray[i];

                        if (!consumable.is_itemeable) {
                            continue;
                        }

                        let unitsEquivalent = parseFloat(
                            consumable.units_equivalent || 0
                        );

                        if (unitsEquivalent !== consumable.selected_item_ids.length) {
                            toastr.error(
                                'La cantidad de ítems seleccionados no coincide con la cantidad del producto: ' +
                                consumable.description,
                                'Validación de ítems'
                            );

                            return false;
                        }
                    }

                    // ===========================
                    // 3) SERVICIOS ADICIONALES
                    // ===========================
                    var servicesContainer = $card.find('[data-bodyService]').first();

                    var servicesRead = {
                        array: [],
                        sum_all: 0,
                        sum_billable: 0
                    };

                    if (servicesContainer.length > 0) {
                        servicesRead = readServicesFromDom(servicesContainer);
                    }

                    var servicesArray = servicesRead.array;
                    var servicesSumBillable = servicesRead.sum_billable;

                    // ===========================
                    // 4) SUBTOTAL CON IGV
                    // ===========================
                    let subtotalConsumablesWithIgvReal = 0;

                    for (let i = 0; i < consumablesArray.length; i++) {
                        const qty = Number(consumablesArray[i].quantity) || 0;

                        const priceReal = Number(
                            consumablesArray[i].priceReal ??
                            consumablesArray[i].price
                        ) || 0;

                        const lineWithIgvReal = round10(qty * priceReal);

                        subtotalConsumablesWithIgvReal = round10(
                            subtotalConsumablesWithIgvReal + lineWithIgvReal
                        );
                    }

                    subtotalConsumablesWithIgvReal = round10(
                        subtotalConsumablesWithIgvReal - (Number(descuentoPromos) || 0)
                    );

                    if (subtotalConsumablesWithIgvReal < 0) {
                        subtotalConsumablesWithIgvReal = 0;
                    }

                    const servicesWithIgvReal = round10(
                        Number(servicesSumBillable) || 0
                    );

                    const subtotalWithIgvReal = round10(
                        subtotalConsumablesWithIgvReal + servicesWithIgvReal
                    );

                    // ===========================
                    // 5) DESCUENTO GLOBAL
                    // ===========================
                    const discountWithIgvReal = round10(
                        computeDiscountWithIgv(subtotalWithIgvReal, igvPct)
                    );

                    let totalFinalWithIgvReal = round10(
                        subtotalWithIgvReal - discountWithIgvReal
                    );

                    if (totalFinalWithIgvReal < 0) {
                        totalFinalWithIgvReal = 0;
                    }

                    // ===========================
                    // 6) BASE E IGV
                    // ===========================
                    let baseFinalReal = round10(totalFinalWithIgvReal / factor);

                    if (baseFinalReal < 0) {
                        baseFinalReal = 0;
                    }

                    const igvFinalReal = round10(
                        totalFinalWithIgvReal - baseFinalReal
                    );

                    const discountBaseReal = round10(
                        discountWithIgvReal / factor
                    );

                    // ===========================
                    // 7) Actualizar totales visuales
                    // ===========================
                    $('#descuento').html(moneyRound(discountBaseReal).toFixed(2));
                    $('#gravada').html(moneyRound(baseFinalReal).toFixed(2));
                    $('#igv_total').html(moneyRound(igvFinalReal).toFixed(2));
                    $('#total_importe').html(moneyRound(totalFinalWithIgvReal).toFixed(2));

                    $('#descuento').attr('data-descuento_real', discountBaseReal);
                    $('#gravada').attr('data-gravada_real', baseFinalReal);
                    $('#igv_total').attr('data-igv_total_real', igvFinalReal);
                    $('#total_importe').attr('data-total_importe_real', totalFinalWithIgvReal);

                    // ===========================
                    // 8) Guardar en $equipments
                    // ===========================
                    $card.find('[data-saveEquipment]')
                        .attr('data-saveEquipment', $equipments.length);

                    const discountGlobalMeta = {
                        subtotal_with_igv: subtotalWithIgvReal,
                        discount_with_igv: discountWithIgvReal,
                        discount_base: discountBaseReal,
                        igv_pct: igvPct,
                        factor: factor
                    };

                    $equipments.push({
                        id: $equipments.length,
                        quantity: quantity,
                        utility: utility,
                        rent: rent,
                        letter: letter,
                        total: totalFinalWithIgvReal,
                        description: "",
                        detail: detail,
                        materials: [],
                        consumables: consumablesArray,
                        electrics: [],
                        workforces: servicesArray,
                        discount_global: {
                            base: discountBaseReal,
                            meta: discountGlobalMeta
                        },
                        tornos: [],
                        dias: []
                    });

                    $card.removeClass('card-gray-dark').addClass('card-success');

                    $items = [];

                    console.log('Equipments actualizados:', $equipments);

                    $.alert("Productos confirmados!");
                }
            },
            cancel: {
                text: 'CANCELAR',
                action: function () {
                    $equipmentStatus = false;
                    $.alert("Confirmación cancelada.");
                }
            }
        }
    });
}

// Redondeo clásico a 2 decimales (solo para mostrar o cierre final)
/*function moneyRound(n) {
    return Math.round((n + Number.EPSILON) * 100) / 100;
}*/

// ✅ TRUNCAR a 2 decimales (lo que te evita el +0.01)
function moneyTrunc(n) {
    n = Number(n) || 0;
    return (n >= 0)
        ? Math.floor(n * 100) / 100
        : Math.ceil(n * 100) / 100; // por si hubiera negativos
}

// Dividir y truncar a 2 decimales (para convertir con IGV -> sin IGV)
function divTrunc2(a, b) {
    if (!b) return 0;
    return moneyTrunc((Number(a) || 0) / (Number(b) || 1));
}

// Función para redondear a 2 decimales
function round2(num) {
    return Math.round((num + Number.EPSILON) * 100) / 100;
}

function mayus(e) {
    e.value = e.value.toUpperCase();
}

function getEquipCardFromElement(el) {
    // Busca el card más cercano que representa el equipo
    return $(el).closest('[data-equip]');
}

function markEquipDirty(elOrCard) {
    const $card = (elOrCard instanceof jQuery) ? elOrCard : getEquipCardFromElement(elOrCard);

    if ($card.length === 0) return;

    // Si ya está "dirty" no hace nada
    if ($card.attr('data-dirty') === '1') return;

    $card.attr('data-dirty', '1');

    // Cambia color: success -> dark
    $card.removeClass('card-success').addClass('card-gray-dark');

    // (Opcional) si quieres mostrar un badge "Pendiente"
    // $card.find('[data-pending-badge]').removeClass('d-none');
}

function markEquipClean(elOrCard) {
    const $card = (elOrCard instanceof jQuery) ? elOrCard : getEquipCardFromElement(elOrCard);

    if ($card.length === 0) return;

    $card.attr('data-dirty', '0');

    // dark -> success
    $card.removeClass('card-gray-dark').addClass('card-success');

    // (Opcional) ocultar badge
    // $card.find('[data-pending-badge]').addClass('d-none');
}

function calculateMargen(e) {
    var margen = e.value;

    var letter = $('#letter').val() ;
    var rent = $('#taxes').val() ;

    $subtotal = ($total * ((parseFloat(margen)/100)+1)).toFixed(2);
    $subtotal2 = ($subtotal * ((parseFloat(letter)/100)+1)).toFixed(2);
    $subtotal3 = ($subtotal2 * ((parseFloat(rent)/100)+1)).toFixed(0);

    $('#subtotal2').html('USD '+$subtotal);
    $('#subtotal3').html('USD '+$subtotal2);
    $('#total').html('USD '+$subtotal3);

}

function calculateLetter(e) {
    var letter = e.value;

    var margen = $('#utility').val() ;
    var rent = $('#taxes').val() ;

    $subtotal = ($total * ((parseFloat(margen)/100)+1)).toFixed(2);
    $subtotal2 = ($subtotal * ((parseFloat(letter)/100)+1)).toFixed(2);
    $subtotal3 = ($subtotal2 * ((parseFloat(rent)/100)+1)).toFixed(0);
    $('#subtotal3').html('USD '+$subtotal2);
    $('#total').html('USD '+$subtotal3);

}

function calculateRent(e) {
    var rent = e.value;

    var margen = $('#utility').val();
    var letter = $('#letter').val() ;

    $subtotal = ($total * ((parseFloat(margen)/100)+1)).toFixed(2);
    $subtotal2 = ($subtotal * ((parseFloat(letter)/100)+1)).toFixed(2);
    $subtotal3 = ($subtotal2 * ((parseFloat(rent)/100)+1)).toFixed(0);

    $('#total').html('USD '+$subtotal3);

}

function calculateMargen2(margen) {
    var letter = $('#letter').val() ;
    var rent = $('#taxes').val() ;

    $subtotal = ($total * ((parseFloat(margen)/100)+1)).toFixed(2);
    $subtotal2 = ($subtotal * ((parseFloat(letter)/100)+1)).toFixed(2);
    $subtotal3 = ($subtotal2 * ((parseFloat(rent)/100)+1)).toFixed(0);

    $('#subtotal2').html('USD '+$subtotal);
    $('#subtotal3').html('USD '+$subtotal2);
    $('#total').html('USD '+$subtotal3);

}

function calculateLetter2(letter) {
    var margen = $('#utility').val() ;
    var rent = $('#taxes').val() ;

    $subtotal = ($total * ((parseFloat(margen)/100)+1)).toFixed(2);
    $subtotal2 = ($subtotal * ((parseFloat(letter)/100)+1)).toFixed(2);
    $subtotal3 = ($subtotal2 * ((parseFloat(rent)/100)+1)).toFixed(0);
    $('#subtotal3').html('USD '+$subtotal2);
    $('#total').html('USD '+$subtotal3);

}

function calculateRent2(rent) {
    var margen = $('#utility').val();
    var letter = $('#letter').val() ;

    $subtotal = ($total * ((parseFloat(margen)/100)+1)).toFixed(2);
    $subtotal2 = ($subtotal * ((parseFloat(letter)/100)+1)).toFixed(2);
    $subtotal3 = ($subtotal2 * ((parseFloat(rent)/100)+1)).toFixed(0);

    $('#total').html('USD '+$subtotal3);
}

function calculateTotalC(input) {
    const row = input.closest('.row');
    if (!row) return;

    // cantidad visible (packs o unidades)
    let qty = parseFloat(input.value || 0);
    if (isNaN(qty) || qty < 0) qty = 0;

    // IGV dinámico (usa tu $igv si ya existe)
    const igvPct = (typeof $igv !== 'undefined' && $igv !== null) ? parseFloat($igv) : 18;
    const igvFactor = 1 + (igvPct / 100);

    const elPrice = row.querySelector('[data-consumablePrice]');   // P/U con IGV
    const elValor = row.querySelector('[data-consumableValor]');   // V/U sin IGV
    const elImporte = row.querySelector('[data-consumableImporte]');

    if (!elPrice || !elValor || !elImporte) return;

    const pricePU = parseFloat(elPrice.value || 0); // P/U con IGV
    const importe = qty * pricePU;

    elImporte.value = importe.toFixed(2);
    elValor.value = (pricePU / igvFactor).toFixed(2);

    // ✅ Si es presentación: actualiza units_equivalent (packs * units_per_pack)
    const unitsPerPackAttrEl = row.querySelector('[data-units_per_pack]');
    const unitsEqAttrEl = row.querySelector('[data-units_equivalent]');

    const unitsPerPack = unitsPerPackAttrEl ? parseFloat(unitsPerPackAttrEl.getAttribute('data-units_per_pack') || 0) : 0;

    // Si existe units_per_pack > 0, lo tratamos como presentación
    if (unitsPerPack > 0 && unitsEqAttrEl) {
        const unitsEquivalent = qty * unitsPerPack;
        unitsEqAttrEl.setAttribute('data-units_equivalent', unitsEquivalent);
        qty = Math.floor(qty);
        input.value = qty;
    }

    // (opcional) si tu total general se recalcula aquí, llama tu función:
    // recalcTotalsQuote();
    markEquipDirty(input);
}

function calculateTotalE(e) {
    var cantidad = e.value;
    var precio = e.parentElement.parentElement.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value;
    // CON IGV
    e.parentElement.parentElement.nextElementSibling.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = (parseFloat(cantidad)*parseFloat(precio)).toFixed(2);
    // SIN IGV
    e.parentElement.parentElement.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = ((parseFloat(cantidad)*parseFloat(precio))/1.18).toFixed(2);

}

function calculateTotal(e) {
    var cantidad = e.value;
    var precio = e.parentElement.parentElement.nextElementSibling.firstElementChild.firstElementChild.value;
    e.parentElement.parentElement.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = (parseFloat(cantidad)*parseFloat(precio)).toFixed(2);

}

function calculateTotal2(e) {
    var precio = e.value;
    var cantidad = e.parentElement.parentElement.previousElementSibling.firstElementChild.firstElementChild.value;
    e.parentElement.parentElement.nextElementSibling.firstElementChild.firstElementChild.value = (parseFloat(cantidad)*parseFloat(precio)).toFixed(2);

}

function calculateTotalQuatity(e) {
    var cantidad = e.value;
    var hour = e.parentElement.parentElement.nextElementSibling.firstElementChild.firstElementChild.value;
    var price = e.parentElement.parentElement.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value;

    e.parentElement.parentElement.nextElementSibling.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = (parseFloat(cantidad)*parseFloat(hour)*parseFloat(price)).toFixed(2);

}

function calculateTotalHour(e) {
    var cantidad = e.parentElement.parentElement.previousElementSibling.firstElementChild.firstElementChild.value;
    var hour = e.value;
    var price = e.parentElement.parentElement.nextElementSibling.firstElementChild.firstElementChild.value;
    e.parentElement.parentElement.nextElementSibling.nextElementSibling.firstElementChild.firstElementChild.value = (parseFloat(cantidad)*parseFloat(hour)*parseFloat(price)).toFixed(2);

}

function calculateTotalPrice(e) {
    var cantidad = e.parentElement.parentElement.previousElementSibling.previousElementSibling.firstElementChild.firstElementChild.value;
    var hour = e.parentElement.parentElement.previousElementSibling.firstElementChild.firstElementChild.value;
    var price = e.value;
    console.log(cantidad);
    console.log(hour);
    console.log(price);
    e.parentElement.parentElement.nextElementSibling.firstElementChild.firstElementChild.value = (parseFloat(cantidad)*parseFloat(hour)*parseFloat(price)).toFixed(2);
    console.log(e.parentElement.parentElement.nextElementSibling.firstElementChild.firstElementChild.value);
}

function deleteItem() {
    //console.log($(this).parent().parent().parent());
    var card = $(this).parent().parent().parent().parent().parent().parent().parent();
    card.removeClass('card-success');
    card.addClass('card-gray-dark');

    $(this).parent().parent().remove();
    var itemId = $(this).data('delete');
    //$items = $items.filter(item => item.id !== itemId);
}

function editedActive() {
    var flag = false;
    $(document).find('[data-equip]').each(function(){
        console.log($(this));
        if ($(this).hasClass('card-gray-dark'))
        {
            flag = true;
        }
    });

    return flag;
}

function storeQuote() {
    event.preventDefault();
    $("#btn-submit").attr("disabled", true);

    if ( editedActive() )
    {
        toastr.error('No se puede guardar porque hay productos no confirmados.', 'Error',
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
        $("#btn-submit").attr("disabled", false);
        return;
    }
    if( $equipments.length === 0 )
    {
        toastr.error('No se puede crear una cotización sin productos.', 'Error',
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
        $("#btn-submit").attr("disabled", false);
        return;
    }
    // Obtener la URL
    var createUrl = $formCreate.data('url');
    var equipos = JSON.stringify($equipments);
    var formulario = $('#formCreate')[0];
    var form = new FormData(formulario);
    form.append('equipments', equipos);

    // Datos totales
    let descuento = $("#descuento").html();
    let gravada = $("#gravada").html();
    let igv_total = $("#igv_total").html();
    let total_importe = $("#total_importe").html();

    let descuentoReal = $('#descuento').attr('data-descuento_real');
    let gravadaReal = $('#gravada').attr('data-gravada_real');
    let igvReal = $('#igv_total').attr('data-igv_total_real');
    let totalReal = $('#total_importe').attr('data-total_importe_real');

    form.append('descuento', descuento);
    form.append('gravada', gravada);
    form.append('igv_total', igv_total);
    form.append('total_importe', total_importe);

    form.append('descuentoReal', descuentoReal);
    form.append('gravadaReal', gravadaReal);
    form.append('igvReal', igvReal);
    form.append('totalReal', totalReal);

    const $d = $('#discountSection');

    form.append('discount_input_value', $d.attr('data-discount_value') || '0');

    $.ajax({
        url: createUrl,
        method: 'POST',
        data: form,
        processData:false,
        contentType:false,
        success: function (data) {
            console.log(data);
            toastr.success(data.message, 'Éxito',
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
            setTimeout( function () {
                $("#btn-submit").attr("disabled", false);
                location.reload();
            }, 2000 )
        },
        error: function (data) {
            if( data.responseJSON.message && !data.responseJSON.errors )
            {
                toastr.error(data.responseJSON.message, 'Error',
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
            }
            for ( var property in data.responseJSON.errors ) {
                toastr.error(data.responseJSON.errors[property], 'Error',
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
            }
            $("#btn-submit").attr("disabled", false);

        },
    });
}

function activateTemplate(id) {
    var t = document.querySelector(id);
    return document.importNode(t.content, true);
}