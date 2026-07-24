$(document).ready(function () {
    //$permissions = JSON.parse($('#permissions').val());
    //console.log($permissions);
    $('#sandbox-container .input-daterange').datepicker({
        todayBtn: "linked",
        clearBtn: true,
        language: "es",
        multidate: false,
        autoclose: true
    });

    getDataOrders(1);

    $("#btnBusquedaAvanzada").click(function(e){
        e.preventDefault();
        $(".busqueda-avanzada").slideToggle();
    });

    $(document).on('click', '[data-item]', showData);
    $("#btn-search").on('click', showDataSearch);

    $('body').tooltip({
        selector: '[data-toggle="tooltip"]'
    });

    $(document).on('click', '[data-ver_detalles]', showDetails);

    $(document).on('click', '[data-anular]', anularOrder);

    /*$(document).on('click', '[data-generar_comprobante]', function () {
        const orderId = $(this).data('order-id');

        $.confirm({
            title: 'Confirmación',
            content: '¿Deseas generar el comprobante?',
            buttons: {
                confirmar: {
                    text: 'Sí, generar',
                    action: function () {
                        $.ajax({
                            url: `/factura/generar/${orderId}`,
                            type: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function (response) {
                                if (response.error) {
                                    $.alert('Error: ' + response.error);
                                } else {
                                    $.alert('Comprobante generado con éxito. Ticket: ' + response.ticket);
                                }
                            },
                            error: function (xhr) {
                                $.alert('Error al procesar la solicitud. Intenta nuevamente.');
                            }
                        });
                    }
                },
                cancelar: {
                    text: 'Cancelar',
                    action: function () {}
                }
            }
        });
    });

    $(document).on('click', '[data-imprimir_comprobante]', function () {
        const orderId = $(this).data('order-id');
        window.open(`/factura/imprimir/${orderId}`, '_blank');
    });*/

    $(document).on('click', '[data-facturador]', function () {
        const btn = $(this);
        if (!$('#downloadSection').hasClass('d-none')) {
            $('#downloadSection').addClass('d-none');
        }
        // Asignar ID
        $('#order_id').val(btn.data('id'));

        const tipo = btn.data('tipo-comprobante');
        const docTipo = btn.data('tipo-documento-cliente');
        const numero = btn.data('numero-documento-cliente');
        const direccion = btn.data('direccion-cliente');
        const email = btn.data('email-cliente');
        const nombre = btn.data('nombre-cliente');

        // Limpiar campos anteriores
        $('#formFacturador')[0].reset();
        $('#datos_boleta, #datos_factura').addClass('d-none');

        if (tipo === 'boleta') {
            $('#radio_boleta').prop('checked', true);
            $('#datos_boleta').removeClass('d-none');

            $('#dni').val(numero);
            $('#email_invoice_boleta').val(email);
            $('#name').val(nombre);
        } else if (tipo === 'factura') {
            $('#radio_factura').prop('checked', true);
            $('#datos_factura').removeClass('d-none');

            $('#ruc').val(numero);
            $('#razon_social').val(nombre);
            $('#direccion_fiscal').val(direccion);
            $('#email_invoice_factura').val(email);
        }

        $('#modalFacturador').modal('show');
    });

    $('input[name="invoice_type"]').on('change', function () {
        const selected = $(this).val();
        if (selected === 'boleta') {
            $('#datos_boleta').removeClass('d-none');
            $('#datos_factura').addClass('d-none');
        } else {
            $('#datos_factura').removeClass('d-none');
            $('#datos_boleta').addClass('d-none');
        }
    });

    $('#btnGuardarDatos').on('click', function () {
        const tipoComprobante = $('input[name="invoice_type"]:checked').val();
        const orderId = $('#order_id').val();

        let data = {
            order_id: orderId,
            type_document: tipoComprobante,
        };

        if (tipoComprobante === 'boleta') {
            data.dni = $('#dni').val();
            data.email = $('#email_invoice_boleta').val();
            data.name = $('#name').val();
        } else if (tipoComprobante === 'factura') {
            data.ruc = $('#ruc').val();
            data.razon_social = $('#razon_social').val();
            data.direccion_fiscal = $('#direccion_fiscal').val();
            data.email = $('#email_invoice_factura').val();
        }

        $.ajax({
            url: '/dashboard/sales/update-invoice-data',
            type: 'POST',
            data: data,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                $.alert({
                    title: 'Éxito',
                    content: response.message,
                    type: 'green'
                });
                //$('#modalFacturador').modal('hide');
            },
            error: function (xhr) {
                $.alert({
                    title: 'Error',
                    content: xhr.responseJSON.message || 'Ocurrió un error',
                    type: 'red'
                });
            }
        });
    });

    $('#btnGenerarComprobante').on('click', function () {
        const orderId = $('#order_id').val();

        $.ajax({
            url: '/dashboard/facturador/generar', // Puedes cambiar el nombre si quieres
            type: 'POST',
            data: {
                order_id: orderId
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function () {
                $('#btnGenerarComprobante').prop('disabled', true).text('Generando...');
            },
            success: function (response) {
                $.alert({
                    title: 'Comprobante generado',
                    content: response.message,
                    type: 'green'
                });
                if (response.pdf_url) {
                    $('#btnDescargarPDF').attr('href', response.pdf_url);
                    $('#downloadSection').removeClass('d-none');
                }
                //$('#modalFacturador').modal('hide');
            },
            error: function (xhr) {
                $.alert({
                    title: 'Error',
                    content: xhr.responseJSON.message || 'No se pudo generar el comprobante',
                    type: 'red'
                });
            },
            complete: function () {
                $('#btnGenerarComprobante').prop('disabled', false).text('Generar comprobante');
            }
        });
    });

    $(document).on('click', '[data-pagos_parciales]', function () {
        let saleId = $(this).data('sale_id');

        $('#pp_sale_id').val(saleId);
        $('#pp_fecha_pago').val(new Date().toISOString().slice(0, 10));
        $('#pp_monto').val('');
        let efectivoOption = $('#pp_cash_box_id option').filter(function () {
            return $(this).text().trim().toLowerCase() === 'efectivo';
        }).first();

        if (efectivoOption.length) {
            $('#pp_cash_box_id').val(efectivoOption.val()).trigger('change');
        } else {
            $('#pp_cash_box_id').val('').trigger('change');
        }
        $('#pp_cash_box_subtype_id').val('').trigger('change');
        $('#pp_cash_box_subtype_wrap').hide();

        cargarPagosParciales(saleId);

        $('#modalPagosParciales').modal('show');
    });

    $('#pp_cash_box_id').on('change', function () {
        let $opt = $(this).find('option:selected');
        let boxType = ($opt.data('type') || '').toString();
        let usesSub = String($opt.data('uses_subtypes')) === '1';

        $('#pp_cash_box_subtype_id').val('').trigger('change');

        if (boxType === 'bank' && usesSub) {
            $('#pp_cash_box_subtype_wrap').show();
        } else {
            $('#pp_cash_box_subtype_wrap').hide();
        }
    });

    $('#btnGuardarPagoParcial').on('click', function () {
        let saleId = $('#pp_sale_id').val();
        let fecha = $('#pp_fecha_pago').val();
        let monto = $('#pp_monto').val();
        let cashBoxId = $('#pp_cash_box_id').val();

        const $opt = $('#pp_cash_box_id').find('option:selected');
        const boxType = ($opt.data('type') || '').toString();
        const usesSub = String($opt.data('uses_subtypes') || '0') === '1';

        let cashBoxSubtypeId = null;

        if (boxType === 'bank' && usesSub) {
            cashBoxSubtypeId = $('#pp_cash_box_subtype_id').val();
        }

        if (!saleId || !fecha || !monto || parseFloat(monto) <= 0 || !cashBoxId) {
            $.alert({
                title: 'Campos incompletos',
                content: 'Complete fecha, monto y caja.',
                type: 'red',
                buttons: { ok: { text: 'OK', btnClass: 'btn-danger' } }
            });
            return;
        }

        if (boxType === 'bank' && usesSub && !cashBoxSubtypeId) {
            $.alert({
                title: 'Campos incompletos',
                content: 'Seleccione el canal/subtipo.',
                type: 'red',
                buttons: { ok: { text: 'OK', btnClass: 'btn-danger' } }
            });
            return;
        }

        let cashBoxText = $opt.text().trim();
        let subtypeText = '';

        if (cashBoxSubtypeId) {
            subtypeText = $('#pp_cash_box_subtype_id option:selected').text().trim();
        }

        let metodoPago = subtypeText ? cashBoxText + ' - ' + subtypeText : cashBoxText;

        $.confirm({
            title: 'Confirmar pago parcial',
            content: `
            <div>
                <p>¿Está seguro de registrar este pago?</p>
                <ul>
                    <li><b>Fecha:</b> ${fecha}</li>
                    <li><b>Monto:</b> S/ ${parseFloat(monto).toFixed(2)}</li>
                    <li><b>Caja:</b> ${metodoPago}</li>
                </ul>
            </div>
        `,
            type: 'blue',
            buttons: {
                confirmar: {
                    text: 'Sí, registrar',
                    btnClass: 'btn-primary',
                    action: function () {
                        const jc = this;

                        jc.buttons.confirmar.disable();
                        jc.buttons.cancelar.disable();

                        $.ajax({
                            url: '/dashboard/sale/partial-payment/store',
                            method: 'POST',
                            data: {
                                sale_id: saleId,
                                payment_date: fecha,
                                amount: monto,
                                cash_box_id: cashBoxId,
                                cash_box_subtype_id: cashBoxSubtypeId
                            },
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function (res) {
                                jc.close();

                                $.alert({
                                    title: 'Éxito',
                                    content: res.message || 'Pago registrado con éxito.',
                                    type: 'green',
                                    buttons: {
                                        ok: {
                                            text: 'OK',
                                            btnClass: 'btn-success',
                                            action: function () {
                                                resetFormularioPagoParcial();
                                                cargarPagosParciales(saleId);
                                                reloadCurrentPageOrders();
                                            }
                                        }
                                    }
                                });
                            },
                            error: function (err) {
                                jc.buttons.confirmar.enable();
                                jc.buttons.cancelar.enable();

                                $.alert({
                                    title: 'Error',
                                    content: err.responseJSON?.message || 'No se pudo registrar el pago.',
                                    type: 'red',
                                    buttons: { ok: { text: 'OK', btnClass: 'btn-danger' } }
                                });
                            }
                        });

                        return false;
                    }
                },
                cancelar: {
                    text: 'Cancelar',
                    btnClass: 'btn-secondary'
                }
            }
        });
    });

    $(document).on('click', '[data-eliminar_pago_parcial]', function () {
        let paymentId = $(this).data('id');
        let saleId = $('#pp_sale_id').val();

        $.confirm({
            title: 'Eliminar pago parcial',
            content: '¿Está seguro de eliminar este pago? Se generará la reversión del movimiento de caja.',
            type: 'red',
            buttons: {
                confirmar: {
                    text: 'Sí, eliminar',
                    btnClass: 'btn-danger',
                    action: function () {
                        const jc = this;

                        jc.buttons.confirmar.disable();
                        jc.buttons.cancelar.disable();

                        $.ajax({
                            url: '/dashboard/sale/partial-payment/' + paymentId,
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function (res) {
                                jc.close();

                                $.alert({
                                    title: 'Eliminado',
                                    content: res.message || 'Pago parcial eliminado correctamente.',
                                    type: 'green',
                                    buttons: {
                                        ok: {
                                            text: 'OK',
                                            btnClass: 'btn-success',
                                            action: function () {
                                                resetFormularioPagoParcial();
                                                cargarPagosParciales(saleId);
                                            }
                                        }
                                    }
                                });
                            },
                            error: function (err) {
                                jc.buttons.confirmar.enable();
                                jc.buttons.cancelar.enable();

                                $.alert({
                                    title: 'Error',
                                    content: err.responseJSON?.message || 'No se pudo eliminar el pago.',
                                    type: 'red',
                                    buttons: {
                                        ok: {
                                            text: 'OK',
                                            btnClass: 'btn-danger'
                                        }
                                    }
                                });
                            }
                        });

                        return false;
                    }
                },
                cancelar: {
                    text: 'Cancelar',
                    btnClass: 'btn-secondary'
                }
            }
        });
    });

    /*$(document).on('click', '[data-generar_comprobante]', function () {
        let saleId = $(this).data('sale_id');

        limpiarModalGenerarComprobante();

        $('#gc_sale_id').val(saleId);
        $('#modalGenerarComprobante').modal('show');
    });*/

    $(document).on('click', '[data-generar_comprobante]', function (event) {
        event.preventDefault();

        const saleId = $(this).data('sale_id');

        const isFreeSale = String( $(this).data('free_sale') ) === '1';

        limpiarModalGenerarComprobante();

        $('#gc_sale_id').val(saleId);

        $('#gc_free_sale').val( isFreeSale ? '1' : '0');

        $('#modalGenerarComprobante').modal('show');
    });

    $('a[data-toggle="tab"][href="#gc_boleta"], a[data-toggle="tab"][href="#gc_factura"]').on('shown.bs.tab', function () {
        $('#gc_dni').val('');
        $('#gc_name').val('').prop('readonly', true);
        $('#gc_email_boleta').val('');

        $('#gc_ruc').val('');
        $('#gc_razon_social').val('').prop('readonly', true);
        $('#gc_direccion_fiscal').val('').prop('readonly', true);
        $('#gc_email_factura').val('');
    });

    /*$('#gc_dni').on('keydown', function (e) {
        if (e.key !== 'Enter') return;

        e.preventDefault();

        let dni = $(this).val().trim();

        if (!/^\d{8}$/.test(dni)) {
            toastr.warning('Ingrese un DNI válido de 8 dígitos.');
            return;
        }

        consultarClienteComprobante(dni, 'dni');
    });

    $('#gc_ruc').on('keydown', function (e) {
        if (e.key !== 'Enter') return;

        e.preventDefault();

        let ruc = $(this).val().trim();

        if (!/^\d{11}$/.test(ruc)) {
            toastr.warning('Ingrese un RUC válido de 11 dígitos.');
            return;
        }

        consultarClienteComprobante(ruc, 'ruc');
    });*/
    $('#gc_dni').on(
        'keydown',
        function (event) {
            if (event.key !== 'Enter') {
                return;
            }

            event.preventDefault();

            buscarDniGenerarComprobante();
        }
    );

    $('#btnBuscarGcDni').on(
        'click',
        function () {
            buscarDniGenerarComprobante();
        }
    );

    $('#gc_ruc').on(
        'keydown',
        function (event) {
            if (event.key !== 'Enter') {
                return;
            }

            event.preventDefault();

            buscarRucGenerarComprobante();
        }
    );

    $('#btnBuscarGcRuc').on(
        'click',
        function () {
            buscarRucGenerarComprobante();
        }
    );

    /*$('#btnConfirmarGenerarComprobante').on('click', function () {
        let saleId = $('#gc_sale_id').val();

        let activeTab = $('#gc_tabs .nav-link.active').attr('href');
        let tipoComprobante = activeTab === '#gc_factura' ? 'factura' : 'boleta';

        let payload = {
            sale_id: saleId,
            tipo_comprobante: tipoComprobante,
            dni: $('#gc_dni').val(),
            name: $('#gc_name').val(),
            email_boleta: $('#gc_email_boleta').val(),

            ruc: $('#gc_ruc').val(),
            razon_social: $('#gc_razon_social').val(),
            direccion_fiscal: $('#gc_direccion_fiscal').val(),
            email_factura: $('#gc_email_factura').val()
        };

        $.confirm({
            title: 'Confirmar comprobante',
            content: '¿Está seguro de generar el comprobante electrónico?',
            type: 'orange',
            buttons: {
                confirmar: {
                    text: 'Sí, generar',
                    btnClass: 'btn-warning',
                    action: function () {
                        const jc = this;

                        jc.buttons.confirmar.disable();
                        jc.buttons.cancelar.disable();

                        jc.setContent(`
                        <div style="display:flex;align-items:center;gap:10px">
                            <i class="fa fa-spinner fa-spin"></i>
                            <span>Generando comprobante electrónico...</span>
                        </div>
                    `);

                        $.ajax({
                            url: '/dashboard/sale/generate-invoice',
                            method: 'POST',
                            data: payload,
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function (res) {
                                jc.close();

                                $.alert({
                                    title: 'Éxito',
                                    content: res.message || 'Comprobante generado correctamente.',
                                    type: 'green',
                                    buttons: {
                                        ver: {
                                            text: 'Ver PDF',
                                            btnClass: 'btn-primary',
                                            action: function () {
                                                /!*if (res.url_print) {
                                                    window.open(res.url_print, '_blank');
                                                }

                                                $('#modalGenerarComprobante').modal('hide');

                                                if (typeof getData === 'function') {
                                                    getData();
                                                } else {
                                                    location.reload();
                                                }*!/
                                                finalizarGeneracionComprobante(res, true);
                                            }
                                        },
                                        cerrar: {
                                            text: 'Cerrar',
                                            btnClass: 'btn-secondary',
                                            action: function () {
                                                /!*$('#modalGenerarComprobante').modal('hide');

                                                if (typeof getData === 'function') {
                                                    getData();
                                                } else {
                                                    location.reload();
                                                }*!/
                                                finalizarGeneracionComprobante(res, false);
                                            }
                                        }
                                    }
                                });
                            },
                            error: function (err) {
                                jc.buttons.confirmar.enable();
                                jc.buttons.cancelar.enable();

                                $.alert({
                                    title: 'Error al generar comprobante',
                                    content: err.responseJSON?.message || 'No se pudo generar el comprobante.',
                                    type: 'red',
                                    buttons: {
                                        ok: {
                                            text: 'OK',
                                            btnClass: 'btn-danger'
                                        }
                                    }
                                });
                            }
                        });

                        return false;
                    }
                },
                cancelar: {
                    text: 'Cancelar',
                    btnClass: 'btn-secondary'
                }
            }
        });
    });*/

    $('#btnConfirmarGenerarComprobante').on(
        'click',
        function () {
            const saleId =
                $('#gc_sale_id').val();

            const isFreeSale =
                $('#gc_free_sale').val() === '1';

            const activeTab =
                $('#gc_tabs .nav-link.active')
                    .attr('href');

            const tipoComprobante =
                activeTab === '#gc_factura'
                    ? 'factura'
                    : 'boleta';

            const payload = {
                sale_id: saleId,
                tipo_comprobante:
                tipoComprobante,

                dni: $('#gc_dni').val(),
                name: $('#gc_name').val(),
                email_boleta:
                    $('#gc_email_boleta').val(),

                ruc: $('#gc_ruc').val(),
                razon_social:
                    $('#gc_razon_social').val(),
                direccion_fiscal:
                    $('#gc_direccion_fiscal').val(),
                email_factura:
                    $('#gc_email_factura').val()
            };

            if (
                !validarDatosGenerarComprobante(
                    tipoComprobante
                )
            ) {
                return;
            }

            const url = isFreeSale
                ? '/dashboard/free-sale/generate-invoice'
                : '/dashboard/sale/generate-invoice';

            const tipoVentaText = isFreeSale
                ? 'venta libre'
                : 'venta';

            $.confirm({
                title: 'Confirmar comprobante',

                content: `
                ¿Está seguro de generar el comprobante
                electrónico para esta
                <strong>${tipoVentaText}</strong>?
                <br><br>
                <strong>Venta #${saleId}</strong>
            `,

                type: 'orange',

                buttons: {
                    confirmar: {
                        text: 'Sí, generar',
                        btnClass: 'btn-warning',

                        action: function () {
                            const dialog = this;

                            dialog.buttons
                                .confirmar
                                .disable();

                            dialog.buttons
                                .cancelar
                                .disable();

                            dialog.setContent(`
                            <div style="
                                display:flex;
                                align-items:center;
                                gap:10px;
                            ">
                                <i class="
                                    fa fa-spinner
                                    fa-spin
                                "></i>

                                <span>
                                    Generando comprobante
                                    electrónico...
                                </span>
                            </div>
                        `);

                            $.ajax({
                                url: url,
                                method: 'POST',
                                data: payload,

                                headers: {
                                    'Accept':
                                        'application/json',

                                    'X-CSRF-TOKEN':
                                        $('meta[name="csrf-token"]')
                                            .attr('content')
                                },

                                success: function (response) {
                                    dialog.close();

                                    mostrarExitoGeneracionComprobante(
                                        response
                                    );
                                },

                                error: function (xhr) {
                                    dialog.buttons
                                        .confirmar
                                        .enable();

                                    dialog.buttons
                                        .cancelar
                                        .enable();

                                    mostrarErrorGeneracionComprobante(
                                        xhr
                                    );
                                }
                            });

                            return false;
                        }
                    },

                    cancelar: {
                        text: 'Cancelar',
                        btnClass: 'btn-secondary'
                    }
                }
            });
        }
    );

    $(document).on('switchChange.bootstrapSwitch', '.switch-dispatch-status', function (event, state) {
        let $switch = $(this);
        let saleId = $switch.data('sale_id');
        let nuevoEstado = state ? 'despachado' : 'pendiente';

        $.confirm({
            title: 'Confirmar cambio',
            content: '¿Desea cambiar el estado de despacho a <strong>' + nuevoEstado.toUpperCase() + '</strong>?',
            type: state ? 'green' : 'orange',
            buttons: {
                confirmar: {
                    text: 'Sí, cambiar',
                    btnClass: state ? 'btn-success' : 'btn-warning',
                    action: function () {
                        $.ajax({
                            url: '/dashboard/sale/update-dispatch-status',
                            method: 'POST',
                            data: {
                                sale_id: saleId,
                                dispatch_status: nuevoEstado
                            },
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function (res) {
                                toastr.success(res.message || 'Estado actualizado.');
                            },
                            error: function (err) {
                                toastr.error(err.responseJSON?.message || 'No se pudo actualizar.');

                                $switch.bootstrapSwitch('state', !state, true);
                            }
                        });
                    }
                },
                cancelar: {
                    text: 'Cancelar',
                    btnClass: 'btn-secondary',
                    action: function () {
                        $switch.bootstrapSwitch('state', !state, true);
                    }
                }
            }
        });
    });

    $('#filter_cash_box_id').on('change', function () {
        const $opt = $(this).find('option:selected');
        const boxType = ($opt.data('type') || '').toString();
        const usesSubtypes = String($opt.data('uses_subtypes')) === '1';

        $('#filter_cash_box_subtype_id').val('').trigger('change');

        if (boxType === 'bank' && usesSubtypes) {
            $('#filter_cash_box_subtype_wrap').show();
        } else {
            $('#filter_cash_box_subtype_wrap').hide();
        }
    });

    /*$(document).on('click', '[data-consultar_anulacion]', function () {
        let saleId = $(this).data('sale_id');

        showProcessLoader('Estamos consultando el comprobante a SUNAT...');

        $.ajax({
            url: '/dashboard/consultar/anulacion/' + saleId,
            method: 'POST',
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            processData: false,
            contentType: false,
            success: function (data) {
                $.alert({
                    title: 'Consulta realizada',
                    content: data.message,
                    type: 'green',
                    buttons: {
                        ok: {
                            text: 'Aceptar',
                            btnClass: 'btn-success'
                        }
                    }
                });

                reloadCurrentPageOrders();
            },
            error: function (xhr) {
                let message = "Sucedió un error al consultar la anulación.";

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }

                $.alert({
                    title: 'Aviso',
                    content: message,
                    type: 'orange',
                    buttons: {
                        ok: {
                            text: 'Entendido',
                            btnClass: 'btn-warning'
                        }
                    }
                });

                reloadCurrentPageOrders();
            },
            complete: function () {
                hideProcessLoader();
            }
        });
    });*/
    $(document).on('click', '[data-consultar_anulacion]', function () {
            const saleId =
                $(this).data('sale_id');

            const isFreeSale =
                String(
                    $(this).data('free_sale')
                ) === '1';

            const url = isFreeSale
                ? '/dashboard/ventas-libres/' +
                saleId +
                '/consultar-anulacion'
                : '/dashboard/consultar/anulacion/' +
                saleId;

            $.ajax({
                url: url,
                method: 'POST',

                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN':
                        $('meta[name="csrf-token"]')
                            .attr('content')
                },

                processData: false,
                contentType: false,

                success: function (data) {
                    $.alert({
                        title:
                            'Consulta realizada',

                        content:
                        data.message,

                        type:
                            data.pending_annulment
                                ? 'orange'
                                : 'green',

                        buttons: {
                            ok: {
                                text: 'Aceptar',
                                btnClass:
                                    data.pending_annulment
                                        ? 'btn-warning'
                                        : 'btn-success',

                                action: function () {
                                    reloadCurrentPageOrders();
                                }
                            }
                        }
                    });
                },

                error: function (xhr) {
                    let message =
                        'Sucedió un error al consultar la anulación.';

                    if (
                        xhr.responseJSON &&
                        xhr.responseJSON.message
                    ) {
                        message =
                            xhr.responseJSON.message;
                    }

                    $.alert({
                        title: 'Aviso',
                        content: message,
                        type: 'orange',

                        buttons: {
                            ok: {
                                text: 'Entendido',
                                btnClass: 'btn-warning',

                                action: function () {
                                    reloadCurrentPageOrders();
                                }
                            }
                        }
                    });
                }
            });
        }
    );

    $(document).on('click', '[data-generar_nota_credito]', function () {
        let saleId = $(this).data('sale_id');

        $.confirm({
            title: 'Generar Nota de Crédito',
            content: 'Se generará una Nota de Crédito total por anulación de la operación. ¿Desea continuar?',
            type: 'orange',
            buttons: {
                confirmar: {
                    text: 'Sí, generar',
                    btnClass: 'btn-warning',
                    action: function () {
                        $.ajax({
                            url: '/dashboard/generar/nota-credito/total/' + saleId,
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            processData: false,
                            contentType: false,
                            success: function (data) {
                                $.alert({
                                    title: 'Correcto',
                                    content: data.message,
                                    type: 'green',
                                    buttons: {
                                        ok: {
                                            text: 'Aceptar',
                                            btnClass: 'btn-success'
                                        }
                                    }
                                });

                                reloadCurrentPageOrders();
                            },
                            error: function (xhr) {
                                let message = 'No se pudo generar la Nota de Crédito.';

                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    message = xhr.responseJSON.message;
                                }

                                $.alert({
                                    title: 'Aviso',
                                    content: message,
                                    type: 'orange',
                                    buttons: {
                                        ok: {
                                            text: 'Entendido',
                                            btnClass: 'btn-warning'
                                        }
                                    }
                                });

                                reloadCurrentPageOrders();
                            }
                        });
                    }
                },
                cancelar: {
                    text: 'Cancelar'
                }
            }
        });
    });

    $(document).on('click', '[data-consultar_nota_credito]', function () {
        let saleId = $(this).data('sale_id');

        showProcessLoader('Estamos consultando la anulación del comprobante...');

        $.ajax({
            url: '/dashboard/consultar/nota-credito/' + saleId,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            processData: false,
            contentType: false,
            success: function (data) {
                $.alert({
                    title: 'Consulta realizada',
                    content: data.message,
                    type: 'green',
                    buttons: {
                        ok: {
                            text: 'Aceptar',
                            btnClass: 'btn-success'
                        }
                    }
                });

                reloadCurrentPageOrders();
            },
            error: function (xhr) {
                let message = 'No se pudo consultar la Nota de Crédito.';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }

                $.alert({
                    title: 'Aviso',
                    content: message,
                    type: 'orange',
                    buttons: {
                        ok: {
                            text: 'Entendido',
                            btnClass: 'btn-warning'
                        }
                    }
                });

                reloadCurrentPageOrders();
            },
            complete: function () {
                hideProcessLoader();
            }
        });
    });

    $(document).on('click', '[data-generar_nota_credito_parcial]', function () {
        let saleId = $(this).data('sale_id');

        $.get('/dashboard/nota-credito/parcial/data/' + saleId, function (data) {

            /*
             * Guardamos toda la información de la venta,
             * incluidos los items disponibles.
             */
            ncPartialData = data;
            ncPartialSelectedItems = {};

            $('#nc_partial_sale_id').val(data.sale_id);
            $('#nc_partial_sale_code').text(data.code);
            $('#nc_partial_customer').text(data.cliente);

            $('#nc_partial_document').text(
                (data.type_document === '01' ? 'Factura' : 'Boleta')
                + ' '
                + data.serie_sunat
                + '-'
                + data.numero
            );

            $('#body-nc-partial-items').empty();

            data.items.forEach(function (item) {

                let itemableBadge = '';

                if (item.is_itemable) {
                    itemableBadge = `
                    <br>
                    <span class="badge badge-info mt-1">
                        PRODUCTO ITEMEABLE
                    </span>
                `;
                }

                $('#body-nc-partial-items').append(`
                <tr
                    data-nc-partial-row
                    data-sale_detail_id="${item.sale_detail_id}"
                    data-is_itemable="${item.is_itemable ? 1 : 0}"
                >
                    <td>
                        ${escapeHtml(item.description)}
                        ${itemableBadge}
                    </td>

                    <td>${item.sold_quantity}</td>

                    <td>${item.credited_quantity}</td>

                    <td>${item.available_quantity}</td>

                    <td>
                        <input type="number"
                               class="form-control form-control-sm nc-partial-qty"
                               data-sale_detail_id="${item.sale_detail_id}"
                               data-price="${item.price}"
                               data-max="${item.available_quantity}"
                               data-is_itemable="${item.is_itemable ? 1 : 0}"
                               min="0"
                               max="${item.available_quantity}"
                               step="${item.is_itemable ? 1 : 0.01}"
                               value="0">
                    </td>

                    <td>
                        S/ <span class="nc-partial-line-total">0.00</span>
                    </td>
                </tr>
            `);
            });

            $('#nc_partial_total').text('0.00');
            $('#modalNotaCreditoParcial').modal('show');

        }).fail(function (xhr) {
            let message = 'No se pudo obtener la información de la venta.';

            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }

            $.alert({
                title: 'Aviso',
                content: message,
                type: 'orange',
            });
        });
    });

    $(document).on('input', '.nc-partial-qty', function () {
        let input = $(this);
        let qty = parseFloat(input.val()) || 0;
        let max = parseFloat(input.data('max')) || 0;
        let price = parseFloat(input.data('price')) || 0;

        if (qty > max) {
            qty = max;
            input.val(max);
        }

        if (qty < 0) {
            qty = 0;
            input.val(0);
        }

        let totalLine = qty * price;

        input.closest('tr').find('.nc-partial-line-total').text(totalLine.toFixed(2));

        let total = 0;

        $('.nc-partial-qty').each(function () {
            let q = parseFloat($(this).val()) || 0;
            let p = parseFloat($(this).data('price')) || 0;
            total += q * p;
        });

        $('#nc_partial_total').text(total.toFixed(2));
    });

    $(document).on('click', '#btn-submit-nc-partial', function () {

        if (!ncPartialData) {
            $.alert({
                title: 'Aviso',
                content: 'No se ha cargado la información de la venta.',
                type: 'orange'
            });

            return;
        }

        let selectedLines = [];
        let itemableLines = [];
        let hasErrors = false;

        $('.nc-partial-qty').each(function () {

            const input = $(this);

            const saleDetailId = parseInt(
                input.data('sale_detail_id')
            );

            const qty = parseFloat(input.val()) || 0;
            const max = parseFloat(input.data('max')) || 0;
            const price = parseFloat(input.data('price')) || 0;
            const isItemable = parseInt(input.data('is_itemable')) === 1;

            if (qty < 0 || qty > max) {
                hasErrors = true;

                $.alert({
                    title: 'Cantidad inválida',
                    content: 'La cantidad a devolver no puede superar la cantidad disponible.',
                    type: 'orange'
                });

                return false;
            }

            /*
             * Los productos itemeables solo admiten cantidades enteras.
             */
            if (
                isItemable &&
                qty > 0 &&
                !Number.isInteger(qty)
            ) {
                hasErrors = true;

                $.alert({
                    title: 'Cantidad inválida',
                    content: 'Los productos itemeables deben devolverse en cantidades enteras.',
                    type: 'orange'
                });

                return false;
            }

            if (qty <= 0) {
                return;
            }

            const itemData = ncPartialData.items.find(function (row) {
                return parseInt(row.sale_detail_id) === saleDetailId;
            });

            if (!itemData) {
                hasErrors = true;
                return false;
            }

            const line = {
                sale_detail_id: saleDetailId,
                quantity: qty,
                price: price,
                is_itemable: isItemable,
                description: itemData.description,
                available_items: itemData.available_items || []
            };

            selectedLines.push(line);

            if (isItemable) {
                itemableLines.push(line);
            }
        });

        if (hasErrors) {
            return;
        }

        if (selectedLines.length === 0) {
            $.alert({
                title: 'Aviso',
                content: 'Debe seleccionar al menos un producto para devolver.',
                type: 'orange'
            });

            return;
        }

        /*
         * Si no existen productos itemeables,
         * posteriormente enviaremos directamente al backend.
         */
        if (itemableLines.length === 0) {
            prepararEnvioNotaCreditoParcial(selectedLines);
            return;
        }

        /*
         * Si existen productos itemeables,
         * abrimos el segundo modal.
         */
        renderNcPartialItemSelection(itemableLines);

        $('#modalNotaCreditoParcial').modal('hide');

        $('#modalNcPartialItems').modal('show');
    });

    $(document).on('change', '.nc-partial-item-checkbox', function () {

        const checkbox = $(this);

        const saleDetailId = parseInt(
            checkbox.data('sale_detail_id')
        );

        const requiredQty = parseInt(
            checkbox.data('required_qty')
        );

        const group = $(
            '[data-itemable-group][data-sale_detail_id="' +
            saleDetailId +
            '"]'
        );

        const checkedItems = group.find(
            '.nc-partial-item-checkbox:checked'
        );

        if (checkedItems.length > requiredQty) {

            checkbox.prop('checked', false);

            $.alert({
                title: 'Selección inválida',
                content:
                    'Solo debe seleccionar ' +
                    requiredQty +
                    ' item(s) para este producto.',
                type: 'orange'
            });
        }

        updateNcPartialItemCounters();
        reorderNcPartialSelectedItems();
    });

    $(document).on('click', '#btn-confirm-nc-partial-items', function () {

        let valid = true;
        let selections = {};

        $('[data-itemable-group]').each(function () {

            const group = $(this);

            const saleDetailId = parseInt(
                group.data('sale_detail_id')
            );

            const requiredQty = parseInt(
                group.data('required_qty')
            );

            const selectedItemIds = [];

            group.find('.nc-partial-item-checkbox:checked')
                .each(function () {
                    selectedItemIds.push(
                        parseInt($(this).data('item_id'))
                    );
                });

            if (selectedItemIds.length !== requiredQty) {
                valid = false;

                $.alert({
                    title: 'Selección incompleta',
                    content:
                        'Debe seleccionar exactamente '
                        + requiredQty
                        + ' item(s) para cada producto.',
                    type: 'orange'
                });

                return false;
            }

            selections[saleDetailId] = selectedItemIds;
        });

        if (!valid) {
            return;
        }

        ncPartialSelectedItems = selections;

        $('#modalNcPartialItems').modal('hide');

        /*
         * En el siguiente paso aquí prepararemos el payload
         * y enviaremos la Nota de Crédito al backend.
         */
        prepararEnvioNotaCreditoParcial();
    });

    $(document).on('click', '[data-ver_solucion_sunat_error]', function () {
        const message = $(this).data('message') || 'Sin motivo registrado.';
        const date = $(this).data('date') || '';

        $.alert({
            title: 'Solución del comprobante',
            content: `
            <strong>Fecha:</strong> ${escapeHtml(date)}<br>
            <strong>Detalle:</strong><br>
            ${escapeHtml(message)}
        `,
            type: 'green',
            columnClass: 'medium',
            buttons: {
                ok: {
                    text: 'Cerrar',
                    btnClass: 'btn-success'
                }
            }
        });
    });

    $(document).on('keydown','#nc_partial_item_search',function (event) {
        if (event.key === 'Enter' || event.keyCode === 13) {
            event.preventDefault();
            searchAndSelectNcPartialItem();
        }
    });

    $(document).on('click','#btn-search-nc-partial-item',function () {
            searchAndSelectNcPartialItem();
        }
    );

    $('#modalNcPartialItems').on('shown.bs.modal', function () {
        $('#nc_partial_item_search').val('').focus();
    });
});

var $formDelete;
var $modalDelete;

var $permissions;

let ncPartialData = null;
let ncPartialSelectedItems = {};

function validarDatosGenerarComprobante(
    tipoComprobante
) {
    if (tipoComprobante === 'boleta') {
        const dni =
            $('#gc_dni').val().trim();

        const name =
            $('#gc_name').val().trim();

        if (!/^\d{8}$/.test(dni)) {
            toastr.warning(
                'Ingrese un DNI válido de 8 dígitos.'
            );

            $('#gc_dni').focus();
            return false;
        }

        if (!name) {
            toastr.warning(
                'Busque el DNI para obtener el nombre.'
            );

            $('#gc_dni').focus();
            return false;
        }

        return true;
    }

    const ruc =
        $('#gc_ruc').val().trim();

    const businessName =
        $('#gc_razon_social').val().trim();

    const fiscalAddress =
        $('#gc_direccion_fiscal').val().trim();

    if (!/^\d{11}$/.test(ruc)) {
        toastr.warning(
            'Ingrese un RUC válido de 11 dígitos.'
        );

        $('#gc_ruc').focus();
        return false;
    }

    if (!businessName || !fiscalAddress) {
        toastr.warning(
            'Busque el RUC para obtener la razón social y dirección fiscal.'
        );

        $('#gc_ruc').focus();
        return false;
    }

    return true;
}

function mostrarExitoGeneracionComprobante(
    response
) {
    $.alert({
        title: 'Comprobante generado',

        content:
            response.message ||
            'Comprobante generado correctamente.',

        type: 'green',

        buttons: {
            ver: {
                text: 'Ver PDF',
                btnClass: 'btn-primary',

                action: function () {
                    finalizarGeneracionComprobante(
                        response,
                        true
                    );
                }
            },

            cerrar: {
                text: 'Cerrar',
                btnClass: 'btn-secondary',

                action: function () {
                    finalizarGeneracionComprobante(
                        response,
                        false
                    );
                }
            }
        }
    });
}

function mostrarErrorGeneracionComprobante(xhr) {
    let messages = [];

    if (
        xhr.responseJSON &&
        xhr.responseJSON.errors
    ) {
        $.each(
            xhr.responseJSON.errors,
            function (field, fieldMessages) {
                $.each(
                    fieldMessages,
                    function (index, message) {
                        messages.push(message);
                    }
                );
            }
        );
    }

    if (
        messages.length === 0 &&
        xhr.responseJSON &&
        xhr.responseJSON.message
    ) {
        messages.push(
            xhr.responseJSON.message
        );
    }

    if (messages.length === 0) {
        messages.push(
            'No se pudo generar el comprobante.'
        );
    }

    const html = messages
        .map(function (message) {
            return `
                <li class="mb-1">
                    ${escapeHtml(message)}
                </li>
            `;
        })
        .join('');

    $.alert({
        title:
            'Error al generar comprobante',

        content: `
            <p>
                Revise la siguiente información:
            </p>

            <div class="
                alert alert-danger mb-0
            ">
                <ul class="mb-0 pl-3">
                    ${html}
                </ul>
            </div>
        `,

        type: 'red',

        buttons: {
            ok: {
                text: 'Aceptar',
                btnClass: 'btn-danger'
            }
        }
    });
}

function buscarDniGenerarComprobante() {
    const dni =
        $('#gc_dni').val().trim();

    if (!/^\d{8}$/.test(dni)) {
        toastr.warning(
            'Ingrese un DNI válido de 8 dígitos.'
        );

        $('#gc_dni').focus();
        return;
    }

    consultarClienteComprobante(
        dni,
        'dni'
    );
}

function buscarRucGenerarComprobante() {
    const ruc =
        $('#gc_ruc').val().trim();

    if (!/^\d{11}$/.test(ruc)) {
        toastr.warning(
            'Ingrese un RUC válido de 11 dígitos.'
        );

        $('#gc_ruc').focus();
        return;
    }

    consultarClienteComprobante(
        ruc,
        'ruc'
    );
}

function prepararEnvioNotaCreditoParcial() {
    let saleId = $('#nc_partial_sale_id').val();
    let items = [];

    $('.nc-partial-qty').each(function () {
        const qty = parseFloat($(this).val()) || 0;

        if (qty <= 0) {
            return;
        }

        const saleDetailId = parseInt(
            $(this).data('sale_detail_id')
        );

        items.push({
            sale_detail_id: saleDetailId,
            quantity: qty,
            selected_item_ids:
                ncPartialSelectedItems[saleDetailId] || []
        });
    });

    if (items.length === 0) {
        toastr.error(
            'Debe ingresar al menos una cantidad a devolver.',
            'Error'
        );

        return;
    }

    $.confirm({
        title: 'Confirmar Nota de Crédito Parcial',
        content: `
            Se generará una Nota de Crédito parcial por los productos
            seleccionados y se devolverán los items específicos indicados.
            <br><br>
            ¿Desea continuar?
        `,
        type: 'orange',
        buttons: {
            confirmar: {
                text: 'Sí, generar',
                btnClass: 'btn-warning',
                action: function () {

                    showProcessLoader('Estamos enviando el comprobante a SUNAT...');

                    $.ajax({
                        url: '/dashboard/generar/nota-credito/parcial/' + saleId,
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: JSON.stringify({
                            reason_code: '07',
                            reason_description: 'Devolución parcial',
                            items: items
                        }),
                        contentType: 'application/json',
                        processData: false,
                        success: function (data) {
                            hideProcessLoader();

                            $('#modalNotaCreditoParcial').modal('hide');
                            $('#modalNcPartialItems').modal('hide');

                            ncPartialData = null;
                            ncPartialSelectedItems = {};

                            $.alert({
                                title: 'Correcto',
                                content: data.message,
                                type: 'green',
                                buttons: {
                                    ok: {
                                        text: 'Aceptar',
                                        btnClass: 'btn-success'
                                    }
                                }
                            });

                            reloadCurrentPageOrders();
                        },
                        error: function (xhr) {
                            let message =
                                'No se pudo generar la Nota de Crédito parcial.';

                            if (
                                xhr.responseJSON &&
                                xhr.responseJSON.message
                            ) {
                                message = xhr.responseJSON.message;
                            }

                            $.alert({
                                title: 'Aviso',
                                content: message,
                                type: 'orange'
                            });
                        },
                    });
                }
            },
            cancelar: {
                text: 'Cancelar'
            }
        }
    });
}

function updateNcPartialItemCounters() {

    $('[data-itemable-group]').each(function () {

        const group = $(this);
        const requiredQty = parseInt(group.data('required_qty')) || 0;

        const selectedQty = group.find(
            '.nc-partial-item-checkbox:checked'
        ).length;

        const counter = group.find('[data-selected-counter]');

        counter.text(selectedQty + ' / ' + requiredQty);

        counter.removeClass(
            'badge-secondary badge-success badge-danger'
        );

        if (selectedQty === requiredQty) {
            counter.addClass('badge-success');
        } else {
            counter.addClass('badge-danger');
        }
    });
}

function renderNcPartialItemSelection(itemableLines) {
    const container = $('#body-nc-partial-item-selection');

    container.empty();

    itemableLines.forEach(function (line) {

        let itemsHtml = '';

        line.available_items.forEach(function (item) {

            const itemId = parseInt(item.item_id);

            const isChecked =
                Array.isArray(ncPartialSelectedItems[line.sale_detail_id]) &&
                ncPartialSelectedItems[line.sale_detail_id].includes(itemId);

            const itemCode = item.code || '';

            itemsHtml += `
                <div class="nc-partial-item-row border rounded p-2 mb-2 ${isChecked ? 'border-success bg-light' : ''}"
                     data-item-row
                     data-item_id="${itemId}"
                     data-item-code="${escapeHtml(itemCode)}"
                     data-sale_detail_id="${line.sale_detail_id}">

                    <div class="custom-control custom-checkbox">

                        <input type="checkbox"
                               class="custom-control-input nc-partial-item-checkbox"
                               id="nc_item_${line.sale_detail_id}_${itemId}"
                               data-sale_detail_id="${line.sale_detail_id}"
                               data-item_id="${itemId}"
                               data-item_code="${escapeHtml(itemCode)}"
                               data-required_qty="${line.quantity}"
                               ${isChecked ? 'checked' : ''}>

                        <label class="custom-control-label"
                               for="nc_item_${line.sale_detail_id}_${itemId}">

                            <strong>
                                ${escapeHtml(itemCode || 'SIN CÓDIGO')}
                            </strong>

                            <br>

                            <small class="text-muted">
                                ${escapeHtml(item.description)}
                            </small>

                        </label>

                    </div>

                </div>
            `;
        });

        container.append(`
            <div class="card mb-3"
                 data-itemable-group
                 data-sale_detail_id="${line.sale_detail_id}"
                 data-required_qty="${line.quantity}">

                <div class="card-header">

                    <strong>
                        ${escapeHtml(line.description)}
                    </strong>

                    <br>

                    <small>
                        Debe seleccionar:
                        <strong>${line.quantity}</strong>
                    </small>

                    <span class="badge badge-secondary float-right"
                          data-selected-counter>
                        0 / ${line.quantity}
                    </span>

                </div>

                <div class="card-body"
                     data-item-list>

                    ${itemsHtml || `
                        <div class="alert alert-warning mb-0">
                            No existen items disponibles para seleccionar.
                        </div>
                    `}

                </div>

            </div>
        `);
    });

    updateNcPartialItemCounters();
    reorderNcPartialSelectedItems();

    $('#nc_partial_item_search').val('');
    $('#nc-partial-item-search-message').empty();
}

function normalizeNcPartialItemCode(value) {
    return String(value || '')
        .trim()
        .toUpperCase();
}

function searchAndSelectNcPartialItem() {

    const searchInput = $('#nc_partial_item_search');
    const searchCode = normalizeNcPartialItemCode(
        searchInput.val()
    );

    const messageContainer = $('#nc-partial-item-search-message');

    messageContainer.empty();

    if (searchCode === '') {
        messageContainer.html(`
            <div class="alert alert-warning py-2">
                Ingrese o pistolee un código.
            </div>
        `);

        searchInput.focus();
        return;
    }

    let foundRow = null;

    $('[data-item-row]').each(function () {

        const row = $(this);

        const itemCode = normalizeNcPartialItemCode(
            row.attr('data-item-code')
        );

        if (itemCode === searchCode) {
            foundRow = row;
            return false;
        }
    });

    if (!foundRow) {
        messageContainer.html(`
            <div class="alert alert-danger py-2">
                No se encontró un item disponible con el código
                <strong>${escapeHtml(searchCode)}</strong>.
            </div>
        `);

        searchInput.select();
        return;
    }

    const checkbox = foundRow.find(
        '.nc-partial-item-checkbox'
    );

    if (checkbox.is(':checked')) {
        messageContainer.html(`
            <div class="alert alert-info py-2">
                El item
                <strong>${escapeHtml(searchCode)}</strong>
                ya se encuentra seleccionado.
            </div>
        `);

        foundRow[0].scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });

        searchInput.val('').focus();
        return;
    }

    const saleDetailId = parseInt(
        checkbox.data('sale_detail_id')
    );

    const requiredQty = parseInt(
        checkbox.data('required_qty')
    );

    const group = $(
        '[data-itemable-group][data-sale_detail_id="' +
        saleDetailId +
        '"]'
    );

    const selectedQty = group.find(
        '.nc-partial-item-checkbox:checked'
    ).length;

    if (selectedQty >= requiredQty) {
        messageContainer.html(`
            <div class="alert alert-warning py-2">
                Ya se seleccionó la cantidad requerida para este producto.
                Desmarque un item antes de seleccionar otro.
            </div>
        `);

        searchInput.select();
        return;
    }

    checkbox.prop('checked', true).trigger('change');

    messageContainer.html(`
        <div class="alert alert-success py-2">
            Item
            <strong>${escapeHtml(searchCode)}</strong>
            seleccionado correctamente.
        </div>
    `);

    /*
     * Luego de reordenar, volvemos a encontrar la fila,
     * ya que cambió de posición en el DOM.
     */
    setTimeout(function () {

        let selectedRow = null;

        $('[data-item-row]').each(function () {

            const row = $(this);

            if (
                normalizeNcPartialItemCode(
                    row.attr('data-item-code')
                ) === searchCode
            ) {
                selectedRow = row;
                return false;
            }
        });

        if (selectedRow && selectedRow.length) {
            selectedRow[0].scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }

    }, 100);

    searchInput.val('').focus();
}

function escapeSelectorValue(value) {
    if (window.CSS && CSS.escape) {
        return CSS.escape(String(value));
    }

    return String(value).replace(
        /([ !"#$%&'()*+,./:;<=>?@[\\\]^`{|}~])/g,
        '\\$1'
    );
}

function reorderNcPartialSelectedItems() {

    $('[data-itemable-group]').each(function () {

        const group = $(this);
        const itemList = group.find('[data-item-list]');

        const selectedRows = itemList
            .find('[data-item-row]')
            .filter(function () {
                return $(this)
                    .find('.nc-partial-item-checkbox')
                    .is(':checked');
            });

        const unselectedRows = itemList
            .find('[data-item-row]')
            .filter(function () {
                return !$(this)
                    .find('.nc-partial-item-checkbox')
                    .is(':checked');
            });

        selectedRows.each(function () {
            $(this)
                .addClass('border-success bg-light')
                .removeClass('border-secondary');

            itemList.append(this);
        });

        unselectedRows.each(function () {
            $(this)
                .removeClass('border-success bg-light');

            itemList.append(this);
        });
    });
}

function showProcessLoader(message) {
    const finalMessage = message || 'Procesando solicitud...';

    $('#globalProcessLoaderMessage').text(finalMessage);
    $('#globalProcessLoader').fadeIn(120);

    $('body').css('overflow', 'hidden');
}

function hideProcessLoader() {
    $('#globalProcessLoader').fadeOut(120, function () {
        $('body').css('overflow', '');
    });
}

function consultarClienteComprobante(documento, tipo) {
    $.ajax({
        url: `/dashboard/customer/decolecta/${documento}`,
        type: 'GET',
        dataType: 'json',
        beforeSend: function () {
            if (tipo === 'dni') {
                $('#gc_name').val('Consultando...');
            }

            if (tipo === 'ruc') {
                $('#gc_razon_social').val('Consultando...');
                $('#gc_direccion_fiscal').val('Consultando...');
            }
        },
        success: function (response) {
            if (!response.success) {
                toastr.error(response.message || 'No se pudo consultar el documento.', 'Error');
                return;
            }

            let customer = response.customer;

            if (tipo === 'dni') {
                $('#gc_dni').val(customer.RUC);
                $('#gc_name').val(customer.business_name || '');
            }

            if (tipo === 'ruc') {
                $('#gc_ruc').val(customer.RUC);
                $('#gc_razon_social').val(customer.business_name || '');
                $('#gc_direccion_fiscal').val(customer.address || '');
            }

            toastr.success('Documento consultado correctamente.', 'Correcto');
        },
        error: function (xhr) {
            let message = xhr.responseJSON?.message || 'No se encontró información.';

            toastr.warning(message + ' Puede ingresarlo manualmente.', 'Consulta sin resultado');

            if (tipo === 'dni') {
                $('#gc_name').val('').prop('readonly', false).focus();
            }

            if (tipo === 'ruc') {
                $('#gc_razon_social').val('').prop('readonly', false).focus();
                $('#gc_direccion_fiscal').val('').prop('readonly', false);
            }
        }
    });
}

function limpiarModalGenerarComprobante() {
    $('#gc_sale_id').val('');

    $('#gc_dni').val('');
    $('#gc_name').val('').prop('readonly', true);
    $('#gc_email_boleta').val('');

    $('#gc_ruc').val('');
    $('#gc_razon_social').val('').prop('readonly', true);
    $('#gc_direccion_fiscal').val('').prop('readonly', true);
    $('#gc_email_factura').val('');

    $('#gc_boleta_tab').tab('show');
}

function resetFormularioPagoParcial() {
    $('#pp_fecha_pago').val(new Date().toISOString().slice(0, 10));
    $('#pp_monto').val('');

    let efectivoOption = $('#pp_cash_box_id option').filter(function () {
        return $(this).text().trim().toLowerCase() === 'efectivo';
    }).first();

    if (efectivoOption.length) {
        $('#pp_cash_box_id').val(efectivoOption.val()).trigger('change');
    } else {
        $('#pp_cash_box_id').val('').trigger('change');
    }

    $('#pp_cash_box_subtype_id').val('').trigger('change');
    $('#pp_cash_box_subtype_wrap').hide();
}

function pintarResumenPagos(totalVenta, totalAbonado) {
    totalVenta = parseFloat(totalVenta || 0);
    totalAbonado = parseFloat(totalAbonado || 0);

    let porcentaje = totalVenta > 0 ? (totalAbonado / totalVenta) * 100 : 0;

    let normalWidth = Math.min(porcentaje, 100);
    let overWidth = porcentaje > 100 ? Math.min(porcentaje - 100, 20) : 0;

    $('#pp_total_venta').val(totalVenta.toFixed(2));
    $('#pp_total_abonado').val(totalAbonado.toFixed(2));

    $('#pp_progress_bar').css('width', normalWidth + '%');

    $('#pp_progress_over').css({
        'left': '100%',
        'width': overWidth + '%'
    });

    $('#pp_progress_text').text(porcentaje.toFixed(2) + '%');
}

function renderPagosParciales(pagos) {
    let html = '';

    if (!pagos || pagos.length === 0) {
        html = `
            <tr>
                <td colspan="4" class="text-center text-muted">
                    No se encontraron pagos registrados
                </td>
            </tr>
        `;

        $('#pp_body_pagos').html(html);
        return;
    }

    pagos.forEach(function (pago) {
        html += `
            <tr>
                <td>${pago.payment_date}</td>
                <td>${parseFloat(pago.amount).toFixed(2)}</td>
                <td>${pago.cash_box_label || '-'}</td>
                <td class="text-center">
                    <button class="btn btn-danger btn-sm" data-eliminar_pago_parcial data-id="${pago.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    $('#pp_body_pagos').html(html);
}

function cargarPagosParciales(saleId) {
    $.get('/dashboard/sale/partial-payment/' + saleId, function (res) {
        pintarResumenPagos(res.total_venta, res.total_abonado);
        renderPagosParciales(res.pagos);
    });
}

function anularOrderO() {
    var order_id = $(this).data('id');
    var typeDocument = $(this).data('type_document');

    var title = "";
    var content = "";

    if (typeDocument == '01' || typeDocument == '03') {
        title = '¿Está seguro de dar de baja esta <strong>boleta/factura</strong>?';

        content = `
        Esta acción afectará el comprobante registrado y realizará la comunicación correspondiente con SUNAT.
        <br>
        Verifique la información antes de continuar, ya que el proceso podría no ser reversible. <br>ORDEN `+order_id;

    } else {
        title = "¿Está seguro de anular esta orden?";
        content = "Verifique la información antes de continuar. ORDEN "+order_id;
    }

    $.confirm({
        icon: 'fas fa-trash-alt',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'red',
        title: title,
        content: content,
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                action: function (e) {
                    $.ajax({
                        url: '/dashboard/anular/order/'+order_id,
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        processData:false,
                        contentType:false,
                        success: function (data) {
                            console.log(data);
                            $.alert(data.message);
                            setTimeout( function () {
                                reloadCurrentPageOrders()
                            }, 2000 )
                        },
                        error: function (xhr) {
                            console.log(xhr);

                            let message = "Sucedió un error en el servidor. Intente nuevamente.";

                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                message = xhr.responseJSON.message;
                            } else if (xhr.responseText) {
                                message = xhr.responseText;
                            }

                            $.alert({
                                title: 'Aviso',
                                content: message,
                                type: 'orange',
                                buttons: {
                                    ok: {
                                        text: 'Entendido',
                                        btnClass: 'btn-warning'
                                    }
                                }
                            });

                            reloadCurrentPageOrders()
                        }
                    });
                },
            },
            cancel: {
                text: 'CANCELAR',
                action: function (e) {
                    $.alert("Cambio de estado cancelado.");
                },
            },
        },
    });

}

function anularOrder() {
    const saleId = $(this).data('id');

    const typeDocument = String(
        $(this).data('type_document') || ''
    );

    const isFreeSale =
        String($(this).data('free_sale')) === '1';

    const hasElectronicDocument =
        typeDocument === '01' ||
        typeDocument === '03';

    const url = isFreeSale
        ? '/dashboard/ventas-libres/' +
        saleId +
        '/anular'
        : '/dashboard/anular/order/' +
        saleId;

    let title;
    let content;

    if (isFreeSale) {
        if (hasElectronicDocument) {
            title =
                '¿Está seguro de anular esta ' +
                '<strong>venta libre facturada</strong>?';

            content = `
                Esta venta posee una boleta o factura electrónica.
                Se aplicará el procedimiento correspondiente con
                Nubefact/SUNAT y se revertirán sus movimientos
                financieros.
                <br><br>
                Los pagos parciales existentes serán inactivados,
                pero se conservará su historial.
                <br><br>
                <strong>VENTA ${saleId}</strong>
            `;
        } else {
            title =
                '¿Está seguro de anular esta ' +
                '<strong>venta libre</strong>?';

            content = `
                Se revertirán todos los movimientos financieros
                asociados a esta venta.
                <br><br>
                Los pagos parciales quedarán inactivos, pero
                conservarán su historial.
                <br><br>
                Esta operación no afectará inventario.
                <br><br>
                <strong>VENTA ${saleId}</strong>
            `;
        }
    } else if (hasElectronicDocument) {
        title =
            '¿Está seguro de dar de baja esta ' +
            '<strong>boleta/factura</strong>?';

        content = `
            Esta acción afectará el comprobante registrado
            y realizará la comunicación correspondiente
            con SUNAT.
            <br><br>
            Verifique la información antes de continuar,
            ya que el proceso podría no ser reversible.
            <br><br>
            <strong>ORDEN ${saleId}</strong>
        `;
    } else {
        title =
            '¿Está seguro de anular esta orden?';

        content = `
            Verifique la información antes de continuar.
            <br><br>
            <strong>ORDEN ${saleId}</strong>
        `;
    }

    $.confirm({
        icon: 'fas fa-trash-alt',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'red',
        title: title,
        content: content,

        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                btnClass: 'btn-danger',

                action: function () {
                    ejecutarAnulacionVenta(
                        url
                    );
                }
            },

            cancel: {
                text: 'CANCELAR',
                btnClass: 'btn-default'
            }
        }
    });
}

function ejecutarAnulacionVenta(url) {
    $.ajax({
        url: url,
        method: 'POST',

        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN':
                $('meta[name="csrf-token"]')
                    .attr('content')
        },

        processData: false,
        contentType: false,

        success: function (data) {
            const isPending =
                data.pending_annulment === true;

            $.alert({
                title: isPending
                    ? 'Anulación pendiente'
                    : 'Venta anulada',

                content: data.message,

                type: isPending
                    ? 'orange'
                    : 'green',

                buttons: {
                    ok: {
                        text: 'Aceptar',

                        btnClass: isPending
                            ? 'btn-warning'
                            : 'btn-success',

                        action: function () {
                            reloadCurrentPageOrders();
                        }
                    }
                }
            });
        },

        error: function (xhr) {
            let message =
                'Sucedió un error al anular la venta.';

            if (
                xhr.responseJSON &&
                xhr.responseJSON.message
            ) {
                message =
                    xhr.responseJSON.message;
            }

            $.alert({
                title: 'No se pudo completar la anulación',
                content: message,
                type: 'orange',

                buttons: {
                    ok: {
                        text: 'Entendido',
                        btnClass: 'btn-warning',

                        action: function () {
                            reloadCurrentPageOrders();
                        }
                    }
                }
            });
        }
    });
}

function printOrder() {
    const orderId = $(this).data('id');

    // Realizar una solicitud AJAX para obtener los detalles del pedido
    $.ajax({
        url: `/print/order/${orderId}`,
        method: 'POST',
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        processData:false,
        contentType:false,
        success: function (response) {
            if (response.error) {
                // Generar dinámicamente el contenido del modal
                toastr.success(response.message, 'Éxito', {
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
                toastr.error(response.message, 'Error', {
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
        },
        error: function (xhr) {
            console.error('Error:', xhr.responseText);
            toastr.error(xhr.responseText, 'Error', {
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

function showDetails() {
    const orderId = $(this).data('id');

    // Realizar una solicitud AJAX para obtener los detalles del pedido
    $.ajax({
        url: `/dashboard/sales/${orderId}/details`,
        method: 'GET',
        success: function (response) {
            if (response.details) {
                // Generar dinámicamente el contenido del modal
                let content = '';
                response.details.forEach((detail, index) => {
                    content += `
                        <div class="mb-4">
                            <h6><strong>Producto:</strong> ${detail.code} | ${detail.producto}</h6>
                            <p><strong>Cantidad:</strong> ${detail.quantity}</p>
                            <p><strong>Precio:</strong> ${detail.price}</p>
                            <p><strong>Total:</strong> ${detail.total}</p>
                    `;

                    content += `</div><hr />`;
                });

                // Insertar el contenido generado en el modal
                $('#order-details-content').html(content);

                // Mostrar el modal
                $('#orderDetailsModal').modal('show');
            } else {
                toastr.error('No se encontraron detalles para este pedido.', 'Error', {
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
        },
        error: function (xhr) {
            console.error('Error:', xhr.responseText);
            toastr.error('Ocurrió un error al obtener los detalles del pedido.', 'Error', {
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

function finalizarGeneracionComprobante(res, abrirPdf) {
    if (abrirPdf && res.url_print) {
        window.open(res.url_print, '_blank');
    }

    limpiarModalGenerarComprobante();
    $('#modalGenerarComprobante').modal('hide');

    reloadCurrentPageOrders();
}

function getCurrentPageOrders() {
    let currentPage = $('#pagination li.active a.page-link').attr('data-item');

    return currentPage ? parseInt(currentPage) : 1;
}

function reloadCurrentPageOrders() {
    let currentPage = getCurrentPageOrders();
    getDataOrders(currentPage);
}

function showDataSearch() {
    getDataOrders(1)
}

function showData() {
    //event.preventDefault();
    var numberPage = $(this).attr('data-item');
    console.log(numberPage);
    getDataOrders(numberPage)
}

function getDataOrders($numberPage) {
    $('[data-toggle="tooltip"]').tooltip('dispose').tooltip({
        selector: '[data-toggle="tooltip"]'
    });

    var code = $('#code').val();
    var year = $('#year').val();
    var startDate = $('#start').val();
    var endDate = $('#end').val();

    var customerId = $('#filter_customer_id').val();
    var paymentStatus = $('#filter_payment_status').val();
    var dispatchStatus = $('#filter_dispatch_status').val();
    var cashBoxId = $('#filter_cash_box_id').val();
    var cashBoxSubtypeId = $('#filter_cash_box_subtype_id').val();
    var invoiceStatus = $('#filter_invoice_status').val();
    var saleStatus = $('#filter_sale_status').val();

    if (saleStatus === 'annulled') {
        $('#th-dispatch-or-annulled').text('Fecha Anulación');
    } else {
        $('#th-dispatch-or-annulled').text('Estado Despacho');
    }

    $.get('/dashboard/get/data/sales/' + $numberPage, {
        code: code,
        year: year,
        startDate: startDate,
        endDate: endDate,

        customer_id: customerId,
        payment_status: paymentStatus,
        dispatch_status: dispatchStatus,
        cash_box_id: cashBoxId,
        cash_box_subtype_id: cashBoxSubtypeId,
        invoice_status: invoiceStatus,
        sale_status: saleStatus
    }, function(data) {
        if (data.data.length == 0) {
            renderDataOrdersEmpty(data);
        } else {
            renderDataOrders(data);
        }
    }).fail(function(jqXHR, textStatus, errorThrown) {
        console.error(textStatus, errorThrown);

        if (jqXHR.responseJSON.message && !jqXHR.responseJSON.errors) {
            toastr.error(jqXHR.responseJSON.message, 'Error');
        }

        for (var property in jqXHR.responseJSON.errors) {
            toastr.error(jqXHR.responseJSON.errors[property], 'Error');
        }
    }, 'json')
        .done(function() {
            var headers = {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            };

            $.ajaxSetup({
                headers: headers
            });
        });
}

function renderDataOrdersEmpty(data) {
    var dataAccounting = data.data;
    var pagination = data.pagination;
    console.log(dataAccounting);
    console.log(pagination);

    $("#body-table").html('');
    $("#pagination").html('');
    $("#textPagination").html('');
    $("#textPagination").html('Mostrando '+pagination.startRecord+' a '+pagination.endRecord+' de '+pagination.totalFilteredRecords+' pedidos de clientes.');
    $('#numberItems').html('');
    $('#numberItems').html(pagination.totalFilteredRecords);

    renderDataTableEmpty();
}

function renderDataOrders(data) {
    var dataQuotes = data.data;
    var pagination = data.pagination;

    $("#body-table").html('');
    $("#pagination").html('');
    $("#textPagination").html('');
    $("#textPagination").html('Mostrando '+pagination.startRecord+' a '+pagination.endRecord+' de '+pagination.totalFilteredRecords+' pedidos de clientes.');
    $('#numberItems').html('');
    $('#numberItems').html(pagination.totalFilteredRecords);

    for (let j = 0; j < dataQuotes.length ; j++) {
        renderDataTable(dataQuotes[j]);
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

function renderDataTableEmpty() {
    var clone = activateTemplate('#item-table-empty');
    $("#body-table").append(clone);
}

function renderDataTable(data) {
    var clone = activateTemplate('#item-table');

    clone.querySelector("[data-code]").innerHTML = data.code;
    clone.querySelector("[data-date]").innerHTML = data.date;
    clone.querySelector("[data-customer]").innerHTML = data.nombre_cliente;
    clone.querySelector("[data-currency]").innerHTML = data.currency;

    const tdTotal = clone.querySelector("[data-total]");
    tdTotal.innerHTML = data.total;

    tdTotal.classList.remove(
        'bg-pago-parcial-rojo',
        'bg-pago-parcial-naranja',
        'bg-pago-parcial-verde'
    );

    if (data.estado_pago_parcial === 'rojo') {
        tdTotal.classList.add('bg-pago-parcial-rojo');
    } else if (data.estado_pago_parcial === 'naranja') {
        tdTotal.classList.add('bg-pago-parcial-naranja');
    } else if (data.estado_pago_parcial === 'verde') {
        tdTotal.classList.add('bg-pago-parcial-verde');
    }

    clone.querySelector("[data-tipo_pago]").innerHTML = data.tipo_pago;

    if (parseInt(data.is_annulled) === 1) {
        clone.querySelector("[data-dispatch_status]").innerHTML =
            data.annulment_accepted_at || 'ANULADA';
    } else {
        let dispatchChecked = data.dispatch_status === 'despachado';

        clone.querySelector("[data-dispatch_status]").innerHTML = `
            <input type="checkbox"
                   class="switch-dispatch-status"
                   data-sale_id="${data.id}"
                   ${dispatchChecked ? 'checked' : ''}
                   data-bootstrap-switch
                   data-on-text="Desp."
                   data-off-text="Pend."
                   data-on-color="success"
                   data-off-color="warning">`;
    }

    let estadoComprobante = data.estado_comprobante || 'SIN COMPROBANTE';
    let badgeClass = 'badge-secondary';

    if (data.sunat_status === 'Error') {
        badgeClass = 'badge-danger';
    } else if (data.annulment_status === 'pending') {
        badgeClass = 'badge-info';
    } else if (data.annulment_status === 'waiting_sunat_process') {
        badgeClass = 'badge-warning';
    } else if (data.has_pending_credit_note) {
        badgeClass = 'badge-info';
    } else if (data.has_rejected_credit_note) {
        badgeClass = 'badge-danger';
    } else if (data.annulment_status === 'requires_credit_note') {
        badgeClass = 'badge-warning';
    } else if (data.annulment_status === 'accepted') {
        badgeClass = 'badge-dark';
    } else if (data.type_document === '01' || data.type_document === '03') {
        badgeClass = 'badge-success';
    }

    /*clone.querySelector("[data-estado_comprobante]").innerHTML =
        `<span class="badge ${badgeClass}">${estadoComprobante}</span>`;*/
    let htmlEstado = `<span class="badge ${badgeClass}">${estadoComprobante}</span>`;

    if (data.sunat_error_is_discarded) {
        const reason = data.sunat_error_discard_reason || 'Sin motivo registrado';

        htmlEstado += `
        <br>
        <span class="badge badge-success mt-1">SOLUCIONADO</span>
        <button type="button"
                class="btn btn-link btn-sm p-0 ml-1"
                data-ver_solucion_sunat_error
                data-message="${escapeHtml(reason)}"
                data-date="${escapeHtml(data.sunat_error_discarded_at || '')}"
                data-toggle="tooltip"
                title="Ver solución">
            <i class="fas fa-info-circle text-success"></i>
        </button>
    `;
    }



    if (data.credit_note_status === 'partial') {
        htmlEstado += `<br><span class="badge badge-warning mt-1">NC PARCIAL</span>`;
    }

    if (
        data.annulment_status === 'accepted' &&
        data.annulment_type === 'credit_note'
    ) {
        htmlEstado += `<br><span class="badge badge-secondary mt-1">ANULADA POR NOTA DE CRÉDITO</span>`;
    }

    if (
        data.annulment_status === 'accepted' &&
        data.annulment_type === 'nubefact_baja'
    ) {
        htmlEstado += `<br><span class="badge badge-secondary mt-1">ANULADA POR BAJA</span>`;
    }

    clone.querySelector("[data-estado_comprobante]").innerHTML = htmlEstado;

    var botones = clone.querySelector("[data-buttons]");

    /*
     * ============================================================
     * VENTAS ANULADAS
     * ============================================================
     */
    if (parseInt(data.is_annulled) === 1) {
        var cloneBtnAnnulled = activateTemplate('#template-annulled');

        cloneBtnAnnulled.querySelector("[data-ver_detalles]").setAttribute("data-id", data.id);

        const printOriginal = cloneBtnAnnulled.querySelector("[data-print_recibo]");
        printOriginal.setAttribute("data-id", data.id);
        printOriginal.setAttribute("href", data.print_url || "#");
        printOriginal.setAttribute("title", data.print_label || "Ver comprobante original");

        if (!data.print_url) {
            printOriginal.classList.add('disabled');
            printOriginal.removeAttribute('target');
            printOriginal.addEventListener('click', function (e) {
                e.preventDefault();
            });
        } else {
            printOriginal.setAttribute('target', '_blank');
        }

        const pdfAnulacion = cloneBtnAnnulled.querySelector("[data-print_anulacion_pdf]");
        const xmlAnulacion = cloneBtnAnnulled.querySelector("[data-print_anulacion_xml]");
        const cdrAnulacion = cloneBtnAnnulled.querySelector("[data-print_anulacion_cdr]");

        let pdfUrl = data.annulment_pdf_url_local;
        let xmlUrl = data.annulment_xml_url_local;
        let cdrUrl = data.annulment_cdr_url_local;

        if (data.is_credit_note_annulment) {
            pdfUrl = data.credit_note_pdf_url_local;
            xmlUrl = data.credit_note_xml_url_local;
            cdrUrl = data.credit_note_cdr_url_local;
        }

        /*if (data.annulment_xml_url_local) {
            xmlAnulacion.setAttribute("href", data.annulment_xml_url_local);
            xmlAnulacion.setAttribute("target", "_blank");
        } else {
            xmlAnulacion.remove();
        }

        if (data.annulment_cdr_url_local) {
            cdrAnulacion.setAttribute("href", data.annulment_cdr_url_local);
            cdrAnulacion.setAttribute("target", "_blank");
        } else {
            cdrAnulacion.remove();
        }*/
        if (pdfUrl) {
            pdfAnulacion.setAttribute("href", pdfUrl);
            pdfAnulacion.setAttribute("target", "_blank");
        } else {
            pdfAnulacion.remove();
        }

        if (xmlUrl) {
            xmlAnulacion.setAttribute("href", xmlUrl);
            xmlAnulacion.setAttribute("target", "_blank");
        } else {
            xmlAnulacion.remove();
        }

        if (cdrUrl) {
            cdrAnulacion.setAttribute("href", cdrUrl);
            cdrAnulacion.setAttribute("target", "_blank");
        } else {
            cdrAnulacion.remove();
        }

        if (data.is_credit_note_annulment) {
            pdfAnulacion.setAttribute("title", "Ver PDF Nota de Crédito");
            xmlAnulacion.setAttribute("title", "Ver XML Nota de Crédito");
            cdrAnulacion.setAttribute("title", "Ver CDR Nota de Crédito");
        }

        botones.append(cloneBtnAnnulled);
        $("#body-table").append(clone);

        $('[data-toggle="tooltip"]').tooltip();
        return;
    }

    /*
     * ============================================================
     * VENTAS ACTIVAS
     * ============================================================
     */
    var cloneBtnActive = activateTemplate('#template-active');

    cloneBtnActive.querySelector("[data-ver_detalles]").setAttribute("data-id", data.id);

    const printBtn = cloneBtnActive.querySelector("[data-print_recibo]");
    printBtn.setAttribute("data-id", data.id);
    printBtn.setAttribute("href", data.print_url || "#");
    printBtn.setAttribute("title", data.print_label || "Ver comprobante");

    if (!data.print_url) {
        printBtn.classList.add('disabled');
        printBtn.removeAttribute('target');
        printBtn.addEventListener('click', function (e) {
            e.preventDefault();
        });
    } else {
        printBtn.setAttribute('target', '_blank');
    }

    /*const btnAnular = cloneBtnActive.querySelector("[data-anular]");

    if (
        data.annulment_status === 'pending' ||
        data.annulment_status === 'waiting_sunat_process' ||
        data.annulment_status === 'accepted'
    ) {
        btnAnular.remove();
    } else {
        btnAnular.setAttribute("data-id", data.id);
        btnAnular.setAttribute("data-type_document", data.type_document);
    }*/
    const btnAnular =
        cloneBtnActive.querySelector("[data-anular]");

    if (
        data.annulment_status === 'pending' ||
        data.annulment_status === 'waiting_sunat_process' ||
        data.annulment_status === 'accepted'
    ) {
        btnAnular.remove();
    } else {
        btnAnular.setAttribute('data-id',data.id);

        btnAnular.setAttribute('data-type_document',data.type_document || '');

        btnAnular.setAttribute('data-free_sale',data.free_sale ? '1' : '0');
    }

    /*const btnConsultarAnulacion = cloneBtnActive.querySelector("[data-consultar_anulacion]");

    if (
        data.annulment_status === 'pending' ||
        data.annulment_status === 'waiting_sunat_process'
    ) {
        btnConsultarAnulacion.setAttribute("data-sale_id", data.id);
    } else {
        btnConsultarAnulacion.remove();
    }*/
    const btnConsultarAnulacion =
        cloneBtnActive.querySelector("[data-consultar_anulacion]");

    if (data.annulment_status === 'pending' || data.annulment_status === 'waiting_sunat_process') {
        btnConsultarAnulacion.setAttribute('data-sale_id',data.id);

        btnConsultarAnulacion.setAttribute('data-free_sale',data.free_sale ? '1' : '0');
    } else {
        btnConsultarAnulacion.remove();
    }

    const btnGenerarComprobante = cloneBtnActive.querySelector("[data-generar_comprobante]");
    const btnPagosParciales = cloneBtnActive.querySelector("[data-pagos_parciales]");

    const btnNotaCredito = cloneBtnActive.querySelector("[data-generar_nota_credito]");
    const btnConsultarNotaCredito = cloneBtnActive.querySelector("[data-consultar_nota_credito]");

    const btnVerNcParcialPdf = cloneBtnActive.querySelector("[data-ver_nc_parcial_pdf]");

    if (data.has_partial_credit_note && data.partial_credit_note_pdf_url_local) {
        btnVerNcParcialPdf.setAttribute("href", data.partial_credit_note_pdf_url_local);
        btnVerNcParcialPdf.setAttribute("target", "_blank");
    } else {
        btnVerNcParcialPdf.remove();
    }

    /*
     * ============================================================
     * NOTA DE CRÉDITO PENDIENTE
     * ============================================================
     */
    if (data.has_pending_credit_note) {
        btnConsultarNotaCredito.setAttribute("data-sale_id", data.id);
        btnNotaCredito.remove();

    } else if (data.has_rejected_credit_note) {
        btnNotaCredito.remove();
        btnConsultarNotaCredito.remove();

    }
    /*
     * ============================================================
     * PUEDE GENERAR NOTA DE CRÉDITO
     * ============================================================
     */
    else if (data.can_generate_credit_note) {

        btnNotaCredito.setAttribute(
            "data-sale_id",
            data.id
        );

        btnConsultarNotaCredito.remove();
    }
    /*
     * ============================================================
     * NO APLICA
     * ============================================================
     */
    else {

        btnNotaCredito.remove();
        btnConsultarNotaCredito.remove();
    }

    /*const tieneComprobanteValido =
        (data.type_document === '01' || data.type_document === '03') &&
        data.sunat_status !== 'Error';

    if (tieneComprobanteValido) {
        btnGenerarComprobante.remove();
    } else {
        btnGenerarComprobante.setAttribute("data-sale_id", data.id);
        btnGenerarComprobante.setAttribute("href", "#");
    }*/
    const tieneComprobanteValido =
        (
            data.type_document === '01' ||
            data.type_document === '03'
        ) &&
        data.sunat_status !== 'Error';

    if (tieneComprobanteValido) {
        btnGenerarComprobante.remove();
    } else {
        btnGenerarComprobante.setAttribute('data-sale_id',data.id);

        btnGenerarComprobante.setAttribute('data-free_sale',data.free_sale ? '1' : '0');

        btnGenerarComprobante.setAttribute('href','#');
    }

    if (data.pagos_parciales_venta === 's') {
        btnPagosParciales.setAttribute("data-sale_id", data.id);
    } else {
        btnPagosParciales.remove();
    }

    /*
     * ============================================================
     * Boton de generar nota de credito parcial
     * ============================================================
     */

    const btnNotaCreditoParcial = cloneBtnActive.querySelector("[data-generar_nota_credito_parcial]");

    const puedeGenerarNotaParcial =
        (data.type_document === '01' || data.type_document === '03') &&
        data.sunat_status !== 'Error' &&
        parseInt(data.is_annulled) !== 1 &&
        !data.has_pending_credit_note &&
        !data.has_rejected_credit_note;

    if (puedeGenerarNotaParcial) {
        btnNotaCreditoParcial.setAttribute("data-sale_id", data.id);
    } else {
        btnNotaCreditoParcial.remove();
    }

    botones.append(cloneBtnActive);
    $("#body-table").append(clone);

    $('#body-table input.switch-dispatch-status').bootstrapSwitch();
    $('[data-toggle="tooltip"]').tooltip();
}

function escapeHtml(value) {
    if (value === null || value === undefined) {
        return '';
    }

    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
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