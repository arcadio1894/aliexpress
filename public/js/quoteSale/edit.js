/* =========================================================
   quoteSale/edit.js (REESTRUCTURADO)
   - 1 solo equipo (data-equip)
   - Recalcula totales en vivo (descuento/gravada/igv/total)
   - Muestra alerta de cambios (#alert_edit)
   - Marca card como "dirty" (card-gray-dark) hasta guardar
   - FIX DECIMALES: total-first + base trunc + igv residual
   ========================================================= */

let $consumables = [];
let $equipments = [];
let $permissions = [];
let $igv = $("#igv").val();

var $modalConsumableQty = $('#modalQuantityConsumable');
var $currentConsumableRender = null;
var $currentConsumable = null;

var $modalSelectItemeableItems = $('#modalSelectItemeableItems');
var $currentItemeableDraft = null;

/* =========================
   Helpers dinero (FIX 0.01)
   ========================= */

function mayus(e) {
    e.value = e.value.toUpperCase();
}


function moneyRound(n) {
    return Math.round((parseFloat(n || 0) + Number.EPSILON) * 100) / 100;
}
function moneyTrunc(n) {
    n = parseFloat(n || 0);
    return (n >= 0) ? Math.floor(n * 100) / 100 : Math.ceil(n * 100) / 100;
}
function divTrunc2(a, b) {
    b = parseFloat(b || 1);
    if (!b) return 0;
    return moneyTrunc((parseFloat(a || 0)) / b);
}

// Mantengo tu round2 para compatibilidad (pero ya no lo usamos para base/igv)
function round2(num) {
    return Math.round((parseFloat(num || 0) + Number.EPSILON) * 100) / 100;
}

/* =======================================
   Descuento global: utilidades (FIX 0.01)
   - Calcula descuento "con IGV" y "en base"
   - según type (amount|percent) y mode (with_igv|without_igv)
   ======================================= */
function computeDiscountWithIgv(subtotalWithIgv, igvPct) {
    const $d = $('#discountSection');
    if ($d.length === 0) return 0;

    const type = ($d.attr('data-discount_type') || 'amount');           // amount | percent
    const mode = ($d.attr('data-discount_input_mode') || 'without_igv'); // with_igv | without_igv
    const value = parseFloat($d.attr('data-discount_value') || 0);

    if (!value || value <= 0) return 0;

    const factor = 1 + (igvPct / 100);

    let discWithIgv = 0;

    if (type === 'amount') {
        // monto ingresado puede ser con o sin IGV
        discWithIgv = (mode === 'with_igv') ? value : moneyRound(value * factor);
    } else {
        // porcentaje sobre total con IGV o sobre base sin IGV
        const pct = value / 100;
        if (mode === 'with_igv') {
            discWithIgv = moneyRound(subtotalWithIgv * pct);
        } else {
            const baseSubtotal = subtotalWithIgv / factor;
            discWithIgv = moneyRound((baseSubtotal * pct) * factor);
        }
    }

    // no permitir superar el subtotal
    if (discWithIgv > subtotalWithIgv) discWithIgv = subtotalWithIgv;

    return moneyRound(discWithIgv);
}

function computeDiscountBaseFromWithIgv(discountWithIgv, igvPct) {
    const factor = 1 + (igvPct / 100);
    // descuento base TRUNC (consistente con base truncada)
    return divTrunc2(discountWithIgv, factor);
}

/* ======================================================
   Reglas finales de totales (FIX 0.01):
   1) totalFinal = round(subtotalWithIgv - discountWithIgv)
   2) baseFinal = TRUNC(totalFinal / 1.18)
   3) igvFinal  = round(totalFinal - baseFinal)
   ====================================================== */
function computeTotals(subtotalWithIgv) {
    const igvPct = parseFloat($igv || 18);
    const factor = 1 + (igvPct / 100);

    const discountWithIgv = computeDiscountWithIgv(subtotalWithIgv, igvPct);
    const totalFinal = moneyRound(subtotalWithIgv - discountWithIgv);

    const baseFinal = divTrunc2(totalFinal, factor);
    const igvFinal = moneyRound(totalFinal - baseFinal);

    const discountBase = computeDiscountBaseFromWithIgv(discountWithIgv, igvPct);

    return {
        discountWithIgv,
        discountBase,
        baseFinal,
        igvFinal,
        totalFinal
    };
}

/* =========================
   Presentations / Modal
   ========================= */
function fetchPresentations(materialId) {
    return $.ajax({
        url: `/dashboard/materials-presentations/material/${materialId}/presentations`,
        method: 'GET',
        dataType: 'json'
    }).then(function (res) {
        console.log(res);
        return res.presentations || [];
    });
}

function addConsumable() {
    console.log("Vamos a agregar consumable");

    let button = $(this);

    var consumableID = button.parent().parent().find('[data-consumable]').val();

    if (!consumableID) {
        toastr.error('Debe seleccionar un producto', 'Error');
        return;
    }

    // contenedor donde se agregan filas
    var render = button.parent().parent().next().next();

    var consumable = $consumables.find(mat => mat.id === parseInt(consumableID));

    if (!consumable) {
        toastr.error('Producto no encontrado', 'Error');
        return;
    }

    let consumablePrice = parseFloat(consumable.list_price) || 0;

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
                        button.parent().parent().find('[data-cantidad]').val(0);
                        $(".consumable_search").empty().trigger('change');

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

function showModalQuantityConsumable(render, consumable) {
    $currentConsumableRender = render;
    $currentConsumable = consumable;

    $('#c_quantity_productId').val(consumable.id);
    $('#c_quantity_total').val(0);
    $('#c_quantity_stock_show').val(consumable.stock_available);
    $('#c_presentationsArea').html('<div class="text-muted">Cargando presentaciones...</div>');

    fetchPresentations(consumable.material_id)
        .then(function (presentations) {
            let actives = presentations.filter(x => x.active === true || x.active === 1 || x.active === "1");
            renderPresentationsInModalConsumable(actives);
            $modalConsumableQty.modal('show');
        })
        .catch(function () {
            $('#c_presentationsArea').html('<div class="text-danger">No se pudo cargar presentaciones.</div>');
            $modalConsumableQty.modal('show');
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
            <th style="width:45%;">Presentación</th>
            <th style="width:25%;">Precio</th>
            <th style="width:30%;">Paquetes</th>
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

/* =========================
   Render consumable row
   ========================= */
function renderTemplateConsumable(
    render,
    consumable,
    qtyVisible,
    pricePU,
    discount,
    isPrice,
    pres,
    selectedItems = []
) {
    var clone = activateTemplate('#template-consumable');

    const isItemeable = parseInt(consumable.tipo_venta_id || 0) === 3;

    const selectedItemIds = Array.isArray(selectedItems)
        ? selectedItems
            .map(function (item) {
                return parseInt(item.id);
            })
            .filter(function (itemId) {
                return itemId > 0;
            })
        : [];

    const selectedItemsText = Array.isArray(selectedItems)
        ? selectedItems
            .map(function (item) {
                return item.code || ('Ítem #' + item.id);
            })
            .join(', ')
        : '';

    // visibles
    clone.querySelector("[data-consumableDescription]").value = consumable.code+" "+consumable.full_description;
    clone.querySelector("[data-consumableUnit]").value = consumable.unit_measure.name;
    clone.querySelector("[data-consumableQuantity]").value = parseFloat(qtyVisible).toFixed(2);
    clone.querySelector("[data-consumablePrice]").value = parseFloat(pricePU).toFixed(2);

    $(clone)
        .find('[data-consumablePrice]')
        .attr('data-consumable_price_real', parseFloat(pricePU).toFixed(10));

    const igvPct = parseFloat($igv || 18);
    const igvFactor = 1 + (igvPct / 100);
    const valorUnitario = parseFloat(pricePU) / igvFactor;

    clone.querySelector("[data-consumableValor]").value = valorUnitario.toFixed(2);

    $(clone)
        .find('[data-consumableValor]')
        .attr('data-consumable_valor_real', valorUnitario.toFixed(10));

    clone.querySelector("[data-consumableImporte]").value =
        (parseFloat(qtyVisible) * parseFloat(pricePU)).toFixed(2);

    /*
     * En edit.js el consumable obtenido desde AJAX trabaja con:
     * consumable.id = stock_item_id
     * consumable.material_id = material padre
     */
    const $consumableId = $(clone).find('[data-consumableId]');

    $consumableId.attr('data-consumableid', consumable.id);
    $consumableId.attr('data-is_itemeable', isItemeable ? '1' : '0');
    $consumableId.attr('data-selected_item_ids', JSON.stringify(selectedItemIds));
    $consumableId.attr('data-selected_items_text', selectedItemsText);

    $(clone)
        .find('[data-stock_item_id]')
        .attr('data-stock_item_id', consumable.id);

    $(clone)
        .find('[data-descuento]')
        .attr('data-descuento', parseFloat(discount || 0).toFixed(2));

    $(clone)
        .find('[data-type_promotion]')
        .attr('data-type_promotion', 'ninguno');

    /*
     * Tooltip nativo al pasar sobre la descripción.
     */
    if (isItemeable && selectedItemsText !== '') {
        $(clone)
            .find('[data-consumableDescription]')
            .attr('title', 'Ítems seleccionados: ' + selectedItemsText);
    }

    // presentación meta
    if (pres) {
        clone.querySelector("[data-presentation_text]").value = pres.text;

        $(clone).find('[data-presentation_id]').attr('data-presentation_id', pres.id);
        $(clone).find('[data-units_per_pack]').attr('data-units_per_pack', pres.unitsPerPack);
        $(clone).find('[data-units_equivalent]').attr('data-units_equivalent', pres.unitsEquivalent);
        $(clone).find('[data-packs]').attr('data-packs', pres.packs);

        clone.querySelector("[data-consumableQuantity]").setAttribute('step', '1');
    } else {
        clone.querySelector("[data-presentation_text]").value = "Unidad";

        $(clone).find('[data-presentation_id]').attr('data-presentation_id', '');
        $(clone).find('[data-units_per_pack]').attr('data-units_per_pack', '');
        $(clone).find('[data-units_equivalent]').attr(
            'data-units_equivalent',
            parseFloat(qtyVisible)
        );
        $(clone).find('[data-packs]').attr('data-packs', '');
    }

    render.append(clone);

    onQuoteChanged();
}

/* =========================
   Dirty state + alerta
   ========================= */
function getMainEquipCard() {
    return $('[data-equip]').first();
}

function setDirty() {
    $('#alert_edit').show();
    const $card = getMainEquipCard();
    $card.attr('data-dirty', '1').removeClass('card-success').addClass('card-gray-dark');
}

function setClean() {
    $('#alert_edit').hide();
    const $card = getMainEquipCard();
    $card.attr('data-dirty', '0').removeClass('card-gray-dark').addClass('card-success');
}

// Llamada única por cualquier cambio
function onQuoteChanged() {
    setDirty();
    recalcQuoteTotalsFromDom();
}

/* =========================
   Lectura DOM: Consumables
   ========================= */
function readConsumablesFromDom($card) {
    const rows = $card.find('[data-consumableDescription]').closest('.row');

    const arr = [];
    let sumImporte = 0;
    let sumPromoDiscount = 0;

    rows.each(function () {
        const $row = $(this);

        const description = ($row.find('[data-consumableDescription]').val() || '').trim();

        if (!description) {
            return;
        }

        const $consumableId = $row.find('[data-consumableid], [data-consumableId]').first();

        /*
         * En edición, el backend acepta stock_item_id.
         * El Blade existente ya mantiene ambos atributos.
         */
        const stockItemId =
            $row.find('[data-stock_item_id]').attr('data-stock_item_id') ||
            $consumableId.attr('data-consumableid') ||
            null;

        const unit = ($row.find('[data-consumableUnit]').val() || '').trim();

        const quantity = parseFloat(
            $row.find('[data-consumableQuantity]').val() || 0
        );

        const valor = parseFloat(
            $row.find('[data-consumableValor]').val() || 0
        );

        const price = parseFloat(
            $row.find('[data-consumablePrice]').val() || 0
        );

        const importe = parseFloat(
            $row.find('[data-consumableImporte]').val() || 0
        );

        const discount = parseFloat(
            $row.find('[data-descuento]').attr('data-descuento') || 0
        );

        const typePromo = $row.find('[data-type_promotion]')
            .attr('data-type_promotion') || null;

        const presentationId = $row.find('[data-presentation_id]')
            .attr('data-presentation_id') || null;

        const unitsPerPack = parseFloat(
            $row.find('[data-units_per_pack]')
                .attr('data-units_per_pack') || 0
        );

        const unitsEquivalent = parseFloat(
            $row.find('[data-units_equivalent]')
                .attr('data-units_equivalent') || 0
        );

        const isItemeable = parseInt(
            $consumableId.attr('data-is_itemeable') || 0
        ) === 1;

        let selectedItemIds = [];

        try {
            const rawItemIds = $consumableId.attr('data-selected_item_ids') || '[]';

            selectedItemIds = JSON.parse(rawItemIds);

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

        if (isItemeable) {
            const requiredUnits = unitsEquivalent || quantity;

            if (selectedItemIds.length !== requiredUnits) {
                throw new Error(
                    'La cantidad de ítems seleccionados no coincide con "' +
                    description +
                    '".'
                );
            }
        }

        arr.push({
            /*
             * Para edición, el backend toma stock_item_id como prioritario.
             * id queda igual para compatibilidad.
             */
            id: stockItemId,
            stock_item_id: stockItemId,
            description: description,
            unit: unit,
            quantity: quantity,
            units_equivalent: unitsEquivalent || quantity,

            valor: valor,
            valorReal: $row.find('[data-consumableValor]')
                .attr('data-consumable_valor_real') || valor,

            price: price,
            priceReal: $row.find('[data-consumablePrice]')
                .attr('data-consumable_price_real') || price,

            importe: importe,

            discount: discount,
            type_promo: typePromo,

            presentation_id: presentationId,
            units_per_pack: unitsPerPack || null,

            is_itemeable: isItemeable,
            selected_item_ids: selectedItemIds
        });

        sumImporte += importe;
        sumPromoDiscount += discount;
    });

    return {
        array: arr,
        sum_importe: moneyRound(sumImporte),
        sum_promos: moneyRound(sumPromoDiscount)
    };
}

/* =========================
   Lectura DOM: Servicios
   ========================= */
function readServicesFromDom($container) {
    const services = $container.find('[data-serviceRow]');

    const arr = [];
    let sumAll = 0;
    let sumBillable = 0;

    services.each(function () {
        const $row = $(this);

        const desc = ($row.find('[data-serviceDescription]').val() || '').trim();
        if (!desc) return;

        const unit = ($row.find('[data-serviceUnit]').val() || '').trim();
        const qty = parseFloat($row.find('[data-serviceQuantity]').val() || 0);
        const vu = parseFloat($row.find('[data-serviceVU]').val() || 0);
        const pu = parseFloat($row.find('[data-servicePU]').val() || 0);
        const imp = parseFloat($row.find('[data-serviceImporte]').val() || 0);

        const billable = $row.find('[data-serviceBillable]').is(':checked') ? 1 : 0;

        arr.push({ description: desc, unit, quantity: qty, valor: vu, price: pu, importe: imp, billable });

        sumAll += (parseFloat(imp) || 0);
        if (billable === 1) sumBillable += (parseFloat(imp) || 0);
    });

    return {
        array: arr,
        sum_all: moneyRound(sumAll),
        sum_billable: moneyRound(sumBillable)
    };
}

/* =========================
   Recalcular totales en vista
   ========================= */
function recalcQuoteTotalsFromDom() {
    const $card = getMainEquipCard();
    if ($card.length === 0) return;

    const cRead = readConsumablesFromDom($card);
    const servicesRead = readServicesFromDom($card.find('[data-bodyService]'));

    const subtotalConsumables = moneyRound((cRead.sum_importe || 0) - (cRead.sum_promos || 0));
    const subtotalWithIgv = moneyRound(subtotalConsumables + (servicesRead.sum_billable || 0));

    const totals = computeTotals(subtotalWithIgv);

    // UI muestra descuento BASE (como venías guardando en Quote)
    $('#descuento').html(totals.discountBase.toFixed(2));
    $('#gravada').html(totals.baseFinal.toFixed(2));
    $('#igv_total').html(totals.igvFinal.toFixed(2));
    $('#total_importe').html(totals.totalFinal.toFixed(2));

    $('#descuento').attr('data-descuento_real', totals.discountBase);
    $('#gravada').attr('data-gravada_real', totals.baseFinal);
    $('#igv_total').attr('data-igv_total_real', totals.igvFinal);
    $('#total_importe').attr('data-total_importe_real', totals.totalFinal);

}

/* =========================
   Servicios: recalcular fila
   ========================= */
function calculateServiceRow(row) {
    const $row = $(row);

    let qty = parseFloat($row.find('[data-serviceQuantity]').val() || 0);
    if (isNaN(qty) || qty < 0) qty = 0;

    let pu = parseFloat($row.find('[data-servicePU]').val() || 0);
    if (isNaN(pu) || pu < 0) pu = 0;

    const igvPct = parseFloat($igv || 18);
    const igvFactor = 1 + (igvPct / 100);

    const vu = (igvFactor > 0) ? (pu / igvFactor) : 0;
    const importe = qty * pu;

    $row.find('[data-serviceVU]').val(vu.toFixed(2));
    $row.find('[data-serviceImporte]').val(importe.toFixed(2));
}

/* =========================
   Consumables: recalcular fila
   ========================= */
function calculateTotalC(input) {
    const row = input.closest('.row');
    if (!row) return;

    let qty = parseFloat(input.value || 0);
    if (isNaN(qty) || qty < 0) qty = 0;

    const igvPct = parseFloat($igv || 18);
    const igvFactor = 1 + (igvPct / 100);

    const elPrice = row.querySelector('[data-consumablePrice]');
    const elValor = row.querySelector('[data-consumableValor]');
    const elImporte = row.querySelector('[data-consumableImporte]');

    if (!elPrice || !elValor || !elImporte) return;

    const pricePU = parseFloat(elPrice.value || 0);
    const importe = qty * pricePU;

    elImporte.value = importe.toFixed(2);
    elValor.value = (pricePU / igvFactor).toFixed(2);

    const unitsPerPackEl = row.querySelector('[data-units_per_pack]');
    const unitsEqEl = row.querySelector('[data-units_equivalent]');

    const unitsPerPack = unitsPerPackEl ? parseFloat(unitsPerPackEl.getAttribute('data-units_per_pack') || 0) : 0;

    if (unitsPerPack > 0 && unitsEqEl) {
        qty = Math.floor(qty);
        input.value = qty;

        const unitsEquivalent = qty * unitsPerPack;
        unitsEqEl.setAttribute('data-units_equivalent', unitsEquivalent);
    } else if (unitsEqEl) {
        unitsEqEl.setAttribute('data-units_equivalent', qty);
    }

    onQuoteChanged();
}

/* =========================
   Descuento: sync data attrs
   ========================= */
function syncDiscountSectionFromUI() {
    const $d = $('#discountSection');
    const type = $('input[name="discount_type"]:checked').val() || 'amount';
    const mode = $('input[name="discount_input_mode"]:checked').val() || 'without_igv';
    let val = parseFloat($('#discount_value').val() || 0);
    if (isNaN(val) || val < 0) val = 0;

    $d.attr('data-discount_type', type);
    $d.attr('data-discount_input_mode', mode);
    $d.attr('data-discount_value', val.toFixed(2));

    onQuoteChanged();
}

/* =========================
   Fill initial equipments
   ========================= */
function fillEquipments() {
    $equipments = [];

    const $card = getMainEquipCard();
    if ($card.length === 0) return;

    const quote_id = $('#quote_id').val();
    const idEquipment = $('#btn-saveProducts').attr('data-idEquipment') || null;

    const cRead = readConsumablesFromDom($card);
    const servicesRead = readServicesFromDom($card.find('[data-bodyService]'));

    const subtotalConsumables = moneyRound((cRead.sum_importe || 0) - (cRead.sum_promos || 0));
    const subtotalWithIgv = moneyRound(subtotalConsumables + (servicesRead.sum_billable || 0));

    $equipments.push({
        id: 0,
        quote: quote_id,
        equipment: idEquipment,
        quantity: 1,
        utility: $card.find('[data-utilityequipment]').val() || 0,
        rent: $card.find('[data-rentequipment]').val() || 0,
        letter: $card.find('[data-letterequipment]').val() || 0,
        total: subtotalWithIgv,
        description: "",
        detail: $card.find('[data-detailequipment]').val() || "",
        materials: [],
        consumables: cRead.array,
        electrics: [],
        workforces: servicesRead.array,
        tornos: [],
        dias: []
    });

    //recalcQuoteTotalsFromDom();
    setClean();
    $("#element_loader").LoadingOverlay("hide", true);
}

/* =========================
   Delete handlers
   ========================= */
function deleteConsumable() {
    $(this).closest('.row').remove();
    onQuoteChanged();
}

function deleteServiceRow() {
    $(this).closest('[data-serviceRow]').remove();
    onQuoteChanged();
}

/* =========================
   Add service
   ========================= */
function addService() {
    const $cardBody = $(this).closest('.card-body');

    const desc = $cardBody.find('#material_search').val().trim();
    const unitId = $cardBody.find('.unitMeasure').val();
    const unitText = $cardBody.find('.unitMeasure option:selected').text().trim();
    const qty = parseFloat($cardBody.find('#quantity').val() || 0);

    if (!desc) return toastr.error('Debe ingresar una descripción', 'Error');
    if (!unitId) return toastr.error('Debe seleccionar una unidad', 'Error');
    if (!qty || qty <= 0) return toastr.error('Debe ingresar una cantidad', 'Error');

    let pu = 0;
    const $priceInput = $cardBody.find('#price');
    if ($priceInput.length) {
        pu = parseFloat($priceInput.val() || 0);
        if (!pu || pu <= 0) return toastr.error('Debe ingresar un precio válido', 'Error');
    }

    const $render = $cardBody.find('[data-bodyService]');
    const clone = activateTemplate('#template-service');

    clone.querySelector('[data-serviceDescription]').value = desc;
    clone.querySelector('[data-serviceId]').value = '';
    clone.querySelector('[data-serviceUnit]').value = unitText;
    clone.querySelector('[data-serviceQuantity]').value = qty.toFixed(2);
    clone.querySelector('[data-servicePU]').value = pu.toFixed(2);

    $render.append(clone);

    const $lastRow = $render.find('[data-serviceRow]').last();

    const uid = 'billable_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
    $lastRow.find('[data-billable-id]').attr('id', uid);
    $lastRow.find('[data-billable-label]').attr('for', uid);
    $lastRow.find('[data-serviceBillable]').prop('checked', true);

    calculateServiceRow($lastRow);

    $cardBody.find('#material_search').val('');
    $cardBody.find('#quantity').val(0);
    if ($priceInput.length) $priceInput.val(0);
    $cardBody.find('.unitMeasure').val(null).trigger('change');

    onQuoteChanged();
}

/* =========================
   Guardar cambios
   ========================= */
function triggerSaveEquipment() {
    const $btn = $('[data-saveEquipment]').first();
    if ($btn.length) $btn.click();
}

$('#btn-add_consumable_modal').on('click', function () {
    if (!$currentConsumable || !$currentConsumableRender) {
        toastr.error('No hay consumible seleccionado', 'Error');
        return;
    }

    let linesToAdd = [];
    let hasPresentation = false;

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
            total_units_required: unitsEquivalent,
            presentation: {
                id: presId,
                text: presLabel,
                packs: packs,
                unitsPerPack: unitsPerPack,
                unitsEquivalent: unitsEquivalent,
                pricePack: presPrice
            }
        });
    });

    if (!hasPresentation) {
        let qty = parseFloat($('#c_quantity_total').val() || 0);

        if (qty <= 0) {
            toastr.error('Ingresa cantidad o selecciona una presentación', 'Error');
            return;
        }

        linesToAdd.push({
            quantity_to_show: qty,
            price: parseFloat($currentConsumable.list_price) || 0,
            total_units_required: qty,
            presentation: null
        });
    }

    const isItemeable = parseInt($currentConsumable.tipo_venta_id || 0) === 3;

    if (isItemeable) {
        const totalUnitsRequired = linesToAdd.reduce(function (total, line) {
            return total + Number(line.total_units_required || 0);
        }, 0);

        if (!Number.isInteger(totalUnitsRequired) || totalUnitsRequired <= 0) {
            toastr.error(
                'Los productos itemeables solo pueden venderse en unidades enteras.',
                'Cantidad inválida'
            );
            return;
        }

        $currentItemeableDraft = {
            consumable: $currentConsumable,
            render: $currentConsumableRender,
            lines: linesToAdd,
            total_units_required: totalUnitsRequired
        };

        $modalConsumableQty.modal('hide');

        openItemeableItemsSelector($currentItemeableDraft);

        return;
    }

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

function openItemeableItemsSelector(draft) {
    if (!draft || !draft.consumable) {
        toastr.error('No se pudo preparar la selección de ítems.', 'Error');
        return;
    }

    const consumable = draft.consumable;
    const stockItemId = consumable.id;
    const requiredCount = parseInt(draft.total_units_required || 0);

    if (!stockItemId || requiredCount <= 0) {
        toastr.error('No se pudo identificar el producto itemeable.', 'Error');
        return;
    }

    $('#itemeable-product-name').text(
        consumable.full_description || consumable.display_name || ''
    );

    $('#itemeable-required-count').text(requiredCount);
    $('#itemeable-selected-count').text(0);
    $('#itemeable-selected-required-count').text(requiredCount);

    $('#itemeable-item-search').val('');

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

    const url = window.APP_QUOTE.URLS.AVAILABLE_ITEMS
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

            const items = response.items || [];

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

function renderItemeableItems(items, requiredCount) {
    let html = '';

    items.forEach(function (item) {
        const itemCode = item.code || ('Ítem #' + item.id);

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
                <td class="text-center">
                    <span class="badge badge-success">Disponible</span>
                </td>
            </tr>
        `;
    });

    $('#itemeable-items-table-body').html(html);

    updateItemeableItemsCounter(requiredCount);
}

function updateItemeableItemsCounter(requiredCount) {
    const selectedCount = $('.itemeable-item-checkbox:checked').length;

    $('#itemeable-selected-count').text(selectedCount);

    const $checkboxes = $('.itemeable-item-checkbox');

    if (selectedCount >= requiredCount) {
        $checkboxes.not(':checked').prop('disabled', true);
    } else {
        $checkboxes.prop('disabled', false);
    }

    $('#btn-confirm-itemeable-items')
        .prop('disabled', selectedCount !== requiredCount);
}

function selectItemByScannedCode() {
    const code = ($('#itemeable-item-search').val() || '').trim();

    if (!code) {
        return;
    }

    const normalizedCode = code.toLowerCase();

    const $row = $('[data-item-row]').filter(function () {
        const itemCode = String($(this).attr('data-item-code') || '')
            .trim()
            .toLowerCase();

        return itemCode === normalizedCode;
    }).first();

    if (!$row.length) {
        toastr.warning(
            'No se encontró un ítem disponible con ese código.',
            'Ítem no encontrado'
        );
        return;
    }

    const $checkbox = $row.find('.itemeable-item-checkbox');

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

    $('#itemeable-items-table-body').prepend($row);

    $row.addClass('table-success');

    setTimeout(function () {
        $row.removeClass('table-success');
    }, 1200);

    $('#itemeable-item-search')
        .val('')
        .focus();
}

/* =========================
   init
   ========================= */
$(document).ready(function () {
    $permissions = JSON.parse($('#permissions').val() || '[]');
    $igv = parseFloat($('#igv').val() || 18);

    $("#element_loader").LoadingOverlay("show", { background: "rgba(61, 215, 239, 0.4)" });

    $selectContact = $('#contact_id');
    getContacts();

    fillEquipments();

    // Carga consumables
    $.ajax({
        url: "/dashboard/get/quote/sale/materials/totals",
        type: 'GET',
        dataType: 'json',
        success: function (json) {
            $consumables = [];
            for (var i = 0; i < json.length; i++) $consumables.push(json[i]);
        }
    });

    $('.consumable_search').select2({
        placeholder: 'Selecciona un consumible',
        ajax: {
            url: '/dashboard/get/quote/sale/materials',
            dataType: 'json',
            type: 'GET',
            processResults(data) {
                return {
                    results: $.map(data, function (item) {
                        return {
                            text: item.display_name,
                            id: item.id,
                        }
                    })
                }
            }
        }
    });

    // Eventos: productos
    $(document).on('click', '[data-deleteConsumable]', deleteConsumable);

    // Eventos: servicios
    $(document).on('click', '[data-addService]', addService);
    $(document).on('click', '[data-deleteService]', deleteServiceRow);

    $(document).on('input', '[data-serviceQuantity],[data-servicePU]', function () {
        const row = $(this).closest('[data-serviceRow]');
        calculateServiceRow(row);
        onQuoteChanged();
    });

    $(document).on('change', '[data-serviceBillable]', function () {
        onQuoteChanged();
    });

    // Descuento
    $(document).on('change input', 'input[name="discount_type"], input[name="discount_input_mode"], #discount_value', syncDiscountSectionFromUI);

    $('#btn-clear-discount').on('click', function () {
        $('#discount_type_amount').prop('checked', true);
        $('#discount_mode_without').prop('checked', true);
        $('#discount_value').val(0);
        syncDiscountSectionFromUI();
    });

    // Guardar cambios
    $('#btn-saveProducts').on('click', function (e) {
        e.preventDefault();
        saveEquipmentEdit();
    });

    // Modal focus fix
    $('#modalQuantityConsumable').on('hidden.bs.modal', function () {
        document.activeElement && document.activeElement.blur && document.activeElement.blur();
        $('#material_search').focus();
    });

    $(document).on('click', '[data-addConsumable]', addConsumable);

    $(document).on("click", "#btn-guardar_datos_generales", function (e) {
        e.preventDefault();

        let data = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            quote_id: $("#quote_id").val(),
            descriptionQuote: $("#descriptionQuote").val(),
            codeQuote: $("#codeQuote").val(),
            date_quote: $("#date_quote").val(),
            date_validate: $("#date_validate").val(),
            way_to_pay: $("#paymentQuote").val(), // si lo usas
            delivery_time: $("#timeQuote").val(),
            customer_id: $("#customer_id").val(),
            contact_id: $("#contact_id").val(),
            payment_deadline: $("#paymentQuote").val(),
            observations: $("#observations").val(),
        };

        $.ajax({
            url: "/dashboard/quotes/update-general",
            type: "POST",
            data: data,
            success: function (response) {
                toastr.success(response.message);

                // Cambiar card de vuelta a verde
                $(".card.datos_generales")
                    .removeClass("card-dark")
                    .addClass("card-success");
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || "Error al guardar cambios.");
            }
        });
    });

    $selectCustomer = $('#customer_id');

    $selectCustomer.change(function () {
        $selectContact.empty();
        var customer = $selectCustomer.val();
        $.get("/dashboard/get/contact/" + customer, function (data) {
            $selectContact.append($("<option>", {
                value: '',
                text: 'Seleccione contacto'
            }));
            var contact_quote_id = $('#contact_quote_id').val();
            for (var i = 0; i < data.length; i++) {
                if (data[i].id === parseInt(contact_quote_id)) {
                    var newOption = new Option(data[i].contact, data[i].id, false, true);
                    // Append it to the select
                    $selectContact.append(newOption).trigger('change');

                } else {
                    var newOption2 = new Option(data[i].contact, data[i].id, false, false);
                    // Append it to the select
                    $selectContact.append(newOption2);
                }
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

    $(document).on('change', '.itemeable-item-checkbox', function () {
        const requiredCount = parseInt(
            $('#btn-confirm-itemeable-items').data('required-count') || 0
        );

        updateItemeableItemsCounter(requiredCount);
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

    $('#btn-cancel-itemeable-items').on('click', function () {
        $currentItemeableDraft = null;

        $modalSelectItemeableItems.modal('hide');

        $modalConsumableQty.modal('show');
    });

    $('#btn-confirm-itemeable-items').on('click', function () {
        const draft = $currentItemeableDraft;

        if (!draft) {
            toastr.error('No se encontró el producto temporal.', 'Error');
            return;
        }

        const requiredCount = parseInt(draft.total_units_required || 0);

        const selectedItems = [];

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
            const unitsForLine = parseInt(line.total_units_required || 0);

            const selectedItemsForLine = selectedItems.slice(
                currentPosition,
                currentPosition + unitsForLine
            );

            currentPosition += unitsForLine;

            renderTemplateConsumable(
                draft.render,
                draft.consumable,
                line.quantity_to_show,
                line.price,
                0,
                true,
                line.presentation,
                selectedItemsForLine
            );
        });

        $modalSelectItemeableItems.modal('hide');

        $currentItemeableDraft = null;

        toastr.success(
            'Se agregaron ' + selectedItems.length + ' ítems al equipo.',
            'Producto agregado'
        );
    });
});

var $selectCustomer;
var $selectContact;

function getContacts() {
    var customer =  $('#customer_quote_id').val();
    $.get( "/dashboard/get/contact/"+customer, function( data ) {
        $selectContact.append($("<option>", {
            value: '',
            text: ''
        }));
        for ( var i=0; i<data.length; i++ )
        {
            if (data[i].id === parseInt($('#contact_quote_id').val())) {
                var newOption = new Option(data[i].contact, data[i].id, false, true);
                // Append it to the select
                $selectContact.append(newOption).trigger('change');

            } else {
                var newOption2 = new Option(data[i].contact, data[i].id, false, false);
                // Append it to the select
                $selectContact.append(newOption2);
            }

        }
    });
}

/* =========================
   Save (Edit) - FIX 0.01
   ========================= */
const getFactor = (igvPct) => 1 + ((Number(igvPct) || 0) / 100);
const round10 = (n) => Math.round((Number(n) || 0) * 1e10) / 1e10;

function saveEquipmentEdit() {

    const $btn = $('#btn-saveProducts');

    const idEquipment2 = $btn.attr('data-idEquipment');
    const quoteId2     = $btn.attr('data-quote');

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

                    $("#element_loader").LoadingOverlay("show", { background: "rgba(61, 215, 239, 0.4)" });

                    // ===========================
                    // 0) Card principal (1 equipo)
                    // ===========================
                    const $card = $('[data-equip]').first();

                    // ===========================
                    // 1) Datos generales del equipo
                    // ===========================
                    const utility = $card.find('[data-utilityequipment]').val() || 0;
                    const rent    = $card.find('[data-rentequipment]').val() || 0;
                    const letter  = $card.find('[data-letterequipment]').val() || 0;
                    const detail  = $card.find('[data-detailequipment]').val() || "";

                    // ===========================
                    // 2) CONSUMABLES (productos) - igual que create.js
                    // ===========================
                    // Ajusta este selector si en edit cambia la estructura:
                    const consumables = $card.find('[data-bodyConsumables], [data-bodyConsumable], [data-consumablesContainer]').first().length
                        ? $card.find('[data-bodyConsumables], [data-bodyConsumable], [data-consumablesContainer]').first()
                        : $card; // fallback: busca en todo el card

                    const consumablesRead = readConsumablesFromDom($card);

                    const consumablesArray = consumablesRead.array;
                    const descuentoPromos = consumablesRead.sum_promos;

                    console.log('Consumables enviados:', consumablesArray);

                    // ===========================
                    // 3) SERVICIOS ADICIONALES
                    // ===========================
                    const servicesContainer = $card.find('[data-bodyService]');
                    let servicesRead = { array: [], sum_all: 0, sum_billable: 0 };
                    if (servicesContainer.length > 0) {
                        servicesRead = readServicesFromDom(servicesContainer);
                    }

                    const servicesArray = servicesRead.array;
                    const servicesSumAll = servicesRead.sum_all; // con IGV
                    const servicesSumBillable = servicesRead.sum_billable;
                    // ===========================
                    // 4) Totales con 10 dec (reales)
                    // ===========================
                    const igvPct = parseFloat($igv) || 18;
                    const factor = getFactor(igvPct);

                    // productos con IGV: quantity * priceReal (priceReal es pack/unidad según corresponda)
                    let subtotalConsumablesWithIgvReal = 0;
                    for (let i = 0; i < consumablesArray.length; i++) {
                        const qty = Number(consumablesArray[i].quantity) || 0;
                        const priceReal = Number(consumablesArray[i].priceReal ?? consumablesArray[i].price) || 0;
                        subtotalConsumablesWithIgvReal = round10(subtotalConsumablesWithIgvReal + round10(qty * priceReal));
                    }

                    console.log('DEBUG prices', consumablesArray.map(x => ({
                        desc: x.description,
                        qty: x.quantity,
                        price: x.price,
                        priceReal: x.priceReal
                    })));

                    // promos por ítem (si ya no usan, será 0)
                    subtotalConsumablesWithIgvReal = round10(subtotalConsumablesWithIgvReal - (Number(descuentoPromos) || 0));
                    if (subtotalConsumablesWithIgvReal < 0) subtotalConsumablesWithIgvReal = 0;

                    const servicesWithIgvReal = round10(Number(servicesSumBillable) || 0);
                    const subtotalWithIgvReal = round10(subtotalConsumablesWithIgvReal + servicesWithIgvReal);

                    // descuento global con IGV (tu función)
                    const discountWithIgvReal = round10(computeDiscountWithIgv(subtotalWithIgvReal, igvPct));

                    // total final con IGV
                    let totalFinalWithIgvReal = round10(subtotalWithIgvReal - discountWithIgvReal);
                    if (totalFinalWithIgvReal < 0) totalFinalWithIgvReal = 0;

                    // base + igv
                    let baseFinalReal = round10(totalFinalWithIgvReal / factor);
                    if (baseFinalReal < 0) baseFinalReal = 0;

                    const igvFinalReal = round10(totalFinalWithIgvReal - baseFinalReal);

                    // descuento en base (sin igv) para guardar y UI
                    const discountBaseReal = round10(discountWithIgvReal / factor);

                    // ===========================
                    // 5) UI (2 dec) + data reales
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
                    // 6) Meta descuento (igual que tenías)
                    // ===========================
                    const $d = $('#discountSection');
                    const discount_type = $d.attr('data-discount_type') || $('input[name="discount_type"]:checked').val() || 'amount';
                    const discount_input_mode = $d.attr('data-discount_input_mode') || $('input[name="discount_input_mode"]:checked').val() || 'without_igv';
                    const discount_input_value = $d.attr('data-discount_value') || $('#discount_value').val() || '0';

                    const discountGlobalMeta = {
                        subtotal_with_igv: subtotalWithIgvReal,
                        discount_with_igv: discountWithIgvReal,
                        discount_base: discountBaseReal,
                        igv_pct: igvPct,
                        factor: factor
                    };

                    // ===========================
                    // 7) Payload equipo (1 item) - con reales
                    // ===========================
                    const equipmentPayload = [{
                        id: 0,
                        quote: quoteId2,
                        equipment: idEquipment2,
                        quantity: 1,
                        utility: utility,
                        rent: rent,
                        letter: letter,

                        // ✅ real con IGV
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
                    }];

                    // ===========================
                    // 8) Enviar al backend (reales a 10 dec)
                    // ===========================
                    $.ajax({
                        url: '/dashboard/update/equipment/' + idEquipment2 + '/quote/sale/' + quoteId2,
                        method: 'POST',
                        data: JSON.stringify({

                            quote_id: $("#quote_id").val(),
                            descriptionQuote: $("#descriptionQuote").val(),
                            codeQuote: $("#codeQuote").val(),
                            date_quote: $("#date_quote").val(),
                            date_validate: $("#date_validate").val(),
                            way_to_pay: $("#paymentQuote").val(), // si lo usas
                            delivery_time: $("#timeQuote").val(),
                            customer_id: $("#customer_id").val(),
                            contact_id: $("#contact_id").val(),
                            payment_deadline: $("#paymentQuote").val(),
                            observations: $("#observations").val(),

                            equipment: equipmentPayload,

                            // ✅ Totales quote en REAL (10 dec)
                            descuento: discountBaseReal,
                            gravada: baseFinalReal,
                            igv_total: igvFinalReal,
                            total_importe: totalFinalWithIgvReal,

                            // meta descuento (por si lo rehidratas en edit)
                            discount_type: discount_type,
                            discount_input_mode: discount_input_mode,
                            discount_input_value: discount_input_value
                        }),
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        processData: false,
                        contentType: 'application/json; charset=utf-8',
                        success: function (data) {
                            // si tienes funciones de "clean state" para edit
                            if (typeof setClean === 'function') setClean();
                            $.alert(data.message || 'Equipo guardado con éxito.');
                        },
                        error: function (xhr) {
                            const msg = xhr.responseJSON?.message || 'Error al guardar.';
                            toastr.error(msg, 'Error');
                        },
                        complete: function () {
                            $("#element_loader").LoadingOverlay("hide", true);
                        }
                    });
                }
            },
            cancel: { text: 'CANCELAR' }
        }
    });
}

function activateTemplate(id) {
    var t = document.querySelector(id);
    return document.importNode(t.content, true);
}
