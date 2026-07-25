$(document).ready(function () {
    $permissions = JSON.parse($('#permissions').val());
    //console.log($permissions);
    $('#sandbox-container .input-daterange').datepicker({
        todayBtn: "linked",
        clearBtn: true,
        language: "es",
        multidate: false,
        autoclose: true
    });

    getDataQuotes(1);

    $("#btnBusquedaAvanzada").click(function(e){
        e.preventDefault();
        $(".busqueda-avanzada").slideToggle();
    });

    $(document).on('click', '[data-item]', showData);
    $("#btn-search").on('click', showDataSearch);

    $('body').tooltip({
        selector: '[data-toggle="tooltip"]'
    });

    $formDelete = $('#formDelete');
    $formDelete.on('submit', destroySubCategory);
    $modalDelete = $('#modalDelete');
    $formDecimals = $('#formDecimals');
    $formDetraction = $('#formDetraction');
    $modalDecimals = $('#modalDecimals');
    $modalDetraction = $('#modalDetraction');
    $(document).on('click', '[data-delete]', cancelQuote);

    $(document).on('click', '[data-finish]', finishQuote);

    $(document).on('click', '[data-confirm]', confirmQuote);

    $(document).on('click', '[data-send]', sendQuote);

    /*$(document).on('click', '[data-renew]', renewQuote);*/

    $('#btn-export').on('click', exportQuotes);

    $(document).on('click', '[data-deselevar]', deselevarQuote);

    $(document).on('click', '[data-decimals]', showModalDecimals);

    $('#btn-changeDecimals').on('click', saveDecimals);

    $(document).on('click', '[data-raise]', raiseQuote);
    $(document).on('click', '[data-raise2]', raise2Quote);

    $(document).on('click', '[data-detraction]', showModalDetraction);

    $('#btn-change').on('click', saveDetraction);

    $(document).on('click', '[data-vb_operations]', vbOpeationsQuote);

    $(document).on('click', '[data-vb_finances]', vbFinancesQuote);

    $(document).on('click', '[data-active_quote]', activeQuote);

    $('#btn-download').on('click', downloadQuotes);

    $(document).on('click', '[data-recotizar]', function () {
        const $button = $(this);

        const quoteId = parseInt(
            $button.attr('data-renew') || 0
        );

        const quoteName = $.trim(
            $button.attr('data-name') || ''
        );

        if (!quoteId) {
            $.alert({
                title: 'Aviso',
                content: 'No se pudo identificar la cotización.',
                type: 'orange',
                buttons: {
                    ok: {
                        text: 'Aceptar',
                        btnClass: 'btn-warning'
                    }
                }
            });

            return;
        }

        const description = quoteName !== ''
            ? '<br><br><strong>' +
            $('<div>').text(quoteName).html() +
            '</strong>'
            : '';

        $.confirm({
            icon: 'fas fa-sync',
            theme: 'modern',
            animation: 'zoom',
            closeAnimation: 'scale',
            closeIcon: true,
            type: 'green',

            title: 'Recotizar cotización',

            content:
                '¿Está seguro de generar una nueva cotización a partir de esta cotización?' +
                description +
                '<br><br>' +
                'Los precios y el stock serán validados nuevamente.',

            buttons: {
                confirm: {
                    text: 'RECOTIZAR',
                    btnClass: 'btn-success',

                    action: function () {
                        const confirmInstance = this;

                        /*
                         * Evitar múltiples clics.
                         */
                        $button.prop('disabled', true);

                        confirmInstance.buttons.confirm.disable();
                        confirmInstance.buttons.cancel.disable();

                        confirmInstance.setContent(
                            '<div class="text-center">' +
                            '<i class="fas fa-spinner fa-spin fa-2x"></i>' +
                            '<br><br>' +
                            'Recotizando, por favor espere...' +
                            '</div>'
                        );

                        $.ajax({
                            url:
                                document.location.origin +
                                '/dashboard/cotizaciones/venta/' +
                                quoteId +
                                '/recotizar',

                            method: 'POST',

                            headers: {
                                'X-CSRF-TOKEN': $(
                                    'meta[name="csrf-token"]'
                                ).attr('content')
                            },

                            success: function (response) {
                                confirmInstance.close();

                                $.alert({
                                    icon: 'fas fa-check-circle',
                                    theme: 'modern',
                                    animation: 'zoom',
                                    type: 'green',

                                    title: 'Cotización recotizada',

                                    content:
                                        response.message ||
                                        'La nueva cotización fue creada correctamente.',

                                    buttons: {
                                        ok: {
                                            text: 'Aceptar',
                                            btnClass: 'btn-success',

                                            action: function () {
                                                window.location.href =
                                                    response.url;
                                            }
                                        }
                                    }
                                });
                            },

                            error: function (xhr) {
                                $button.prop('disabled', false);

                                confirmInstance.close();

                                let message =
                                    'Sucedió un error al recotizar la cotización.';

                                if (
                                    xhr.responseJSON &&
                                    xhr.responseJSON.message
                                ) {
                                    message =
                                        xhr.responseJSON.message;
                                }

                                $.alert({
                                    icon: 'fas fa-exclamation-triangle',
                                    theme: 'modern',
                                    animation: 'zoom',
                                    type: 'orange',

                                    title: 'No se pudo recotizar',

                                    content: $('<div>')
                                        .text(message)
                                        .html(),

                                    buttons: {
                                        ok: {
                                            text: 'Aceptar',
                                            btnClass: 'btn-warning'
                                        }
                                    }
                                });
                            }
                        });

                        /*
                         * Mantiene abierto jquery-confirm mientras
                         * finaliza el AJAX.
                         */
                        return false;
                    }
                },

                cancel: {
                    text: 'CANCELAR'
                }
            }
        });
    });
});

var $formDelete;
var $modalDelete;
var $modalDecimals;
var $formDecimals;

var $permissions;
var $modalDetraction;

function downloadQuotes() {
    var start  = $('#start').val();
    var end  = $('#end').val();
    var stateQuote = $("#stateQuote").val();

    console.log(start);
    console.log(end);

    if ( stateQuote == "" )
    {
        stateQuote = "all";
    }

    console.log(stateQuote);

    if ( start == '' || end == '' )
    {
        console.log('Sin fechas');
        $.confirm({
            icon: 'fas fa-file-excel',
            theme: 'modern',
            closeIcon: true,
            animation: 'zoom',
            type: 'green',
            title: 'No especificó fechas',
            content: 'Si no hay fechas se descargará todas las cotizaciones',
            buttons: {
                confirm: {
                    text: 'DESCARGAR',
                    action: function (e) {
                        //$.alert('Descargado igual');
                        console.log(start);
                        console.log(end);

                        var query = {
                            start: start,
                            end: end,
                            stateQuote: stateQuote
                        };

                        $.alert('Descargando archivo ...');

                        var url = "/dashboard/download/reporte/cotizaciones/v2/?" + $.param(query);

                        window.location = url;

                    },
                },
                cancel: {
                    text: 'CANCELAR',
                    action: function (e) {
                        $.alert("Exportación cancelada.");
                    },
                },
            },
        });
    } else {
        console.log('Con fechas');
        console.log(JSON.stringify(start));
        console.log(JSON.stringify(end));

        var query = {
            start: start,
            end: end,
            stateQuote: stateQuote
        };

        toastr.success('Descargando archivo ...', 'Éxito',
            {
                "closeButton": true,
                "debug": false,
                "newestOnTop": false,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "2000",
                "timeOut": "2000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            });

        var url = "/dashboard/download/reporte/cotizaciones/v2/?" + $.param(query);

        window.location = url;

    }
}

function activeQuote() {
    var quote_id = $(this).data('active');

    $.confirm({
        icon: 'fas fa-lock-open',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'orange',
        columnClass: 'medium',
        title: '¿Está seguro de reactivar esta cotización?',
        content: 'Se va a activar la cotización y se puede seguir trabajando.',
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                btnClass: 'btn-blue',
                action: function () {
                    $.ajax({
                        url: '/dashboard/active/quote/'+quote_id,
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        processData:false,
                        contentType:false,
                        success: function (data) {
                            console.log(data);
                            $.alert(data.message);
                            setTimeout( function () {
                                /*location.reload();*/
                                getDataQuotes(1);
                            }, 2000 )
                        },
                        error: function (data) {
                            $.alert("Sucedió un error en el servidor. Intente nuevamente.");
                        },
                    });
                    //$.alert('Your name is ' + name);
                }
            },
            cancel: {
                text: 'CANCELAR',
                action: function (e) {
                    $.alert("Cotización no activada.");
                },
            },
        }
    });

}

function vbOpeationsQuote() {
    var quote_id = $(this).data('vb_operations');

    $.confirm({
        icon: 'fas fa-check-double',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'green',
        columnClass: 'medium',
        title: '¿Está seguro de dar el visto bueno de operaciones?',
        content: 'Se guardará la fecha de visto bueno y se podrá modificar la lista de materiales.',
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                btnClass: 'btn-blue',
                action: function () {
                    $.ajax({
                        url: '/dashboard/visto/bueno/operations/quote/'+quote_id,
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        processData:false,
                        contentType:false,
                        success: function (data) {
                            console.log(data);
                            $.alert(data.message);
                            setTimeout( function () {
                                /*location.reload();*/
                                getDataQuotes(1);
                            }, 2000 )
                        },
                        error: function (data) {
                            $.alert("Sucedió un error en el servidor. Intente nuevamente.");
                        },
                    });
                    //$.alert('Your name is ' + name);
                }
            },
            cancel: {
                text: 'CANCELAR',
                action: function (e) {
                    $.alert("Se canceló el proceso.");
                },
            },
        }
    });

}

function vbFinancesQuote() {
    var quote_id = $(this).data('vb_finances');

    $.confirm({
        icon: 'fas fa-check-double',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'green',
        columnClass: 'small',
        title: '¿Está seguro de dar el visto bueno de finanzas?',
        content: 'Se guardará la fecha de visto bueno. Por favor verifica si ya descargaste el pdf de cliente.',
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                btnClass: 'btn-blue',
                action: function () {
                    $.ajax({
                        url: '/dashboard/visto/bueno/finances/quote/'+quote_id,
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        processData:false,
                        contentType:false,
                        success: function (data) {
                            console.log(data);
                            $.alert(data.message);
                            setTimeout( function () {
                                /*location.reload();*/
                                getDataQuotes(1);
                            }, 2000 )
                        },
                        error: function (data) {
                            $.alert("Sucedió un error en el servidor. Intente nuevamente.");
                        },
                    });
                    //$.alert('Your name is ' + name);
                }
            },
            cancel: {
                text: 'CANCELAR',
                action: function (e) {
                    $.alert("Se canceló el proceso.");
                },
            },
        }
    });

}

function finishQuote() {
    var quote_id = $(this).data('finish');
    var description = $(this).data('name');

    $.confirm({
        icon: 'fas fa-smile',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'green',
        title: '¿Está seguro de finalizar esta cotización?',
        content: description,
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                action: function (e) {
                    $.ajax({
                        url: '/dashboard/finish/quote/'+quote_id,
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        processData:false,
                        contentType:false,
                        success: function (data) {
                            console.log(data);
                            $.alert(data.message);
                            setTimeout( function () {
                                /*location.href = data.url;*/
                                getDataQuotes(1);
                            }, 2000 )
                        },
                        error: function (data) {
                            $.alert("Sucedió un error en el servidor. Intente nuevamente.");
                        },
                    });
                },
            },
            cancel: {
                text: 'CANCELAR',
                action: function (e) {
                    $.alert("Anulación cancelada.");
                },
            },
        },
    });

}

function saveDetraction() {
    var button = $(this);
    button.attr("disabled", true);
    var form = $formDetraction[0];
    $.confirm({
        icon: 'fas fa-donate',
        theme: 'modern',
        closeIcon: false,
        animation: 'zoom',
        type: 'green',
        columnClass: 'medium',
        title: '¿Está seguro de guardar el tipo de orden?',
        content: 'Este cambio colocará la detracción en el módulo de finanzas.',
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                btnClass: 'btn-blue',
                action: function () {
                    $.ajax({
                        url: '/dashboard/change/detraction/quote',
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        data: new FormData(form),
                        processData:false,
                        contentType:false,
                        success: function (data) {
                            console.log(data);
                            $.alert(data.message);
                            setTimeout( function () {
                                button.attr("disabled", false);
                                $modalDetraction.modal('hide');
                            }, 2000 )
                        },
                        error: function (data) {
                            button.attr("disabled", false);
                            $.alert("Sucedió un error en el servidor. Intente nuevamente.");
                        },
                    });
                }
            },
            cancel: {
                text: 'CANCELAR',
                action: function (e) {
                    button.attr("disabled", false);
                    $.alert("No se guardó ninguún dato.");
                },
            },
        }
    });

}

function showModalDetraction() {
    $('#detraction').val('');
    $('#detraction').trigger('change');
    var quote_id = $(this).data('detraction');
    $.ajax({
        url: "/dashboard/get/detraction/quote/"+quote_id,
        type: 'GET',
        dataType: 'json',
        success: function (data) {
            $formDetraction.find("[id=quote_id]").val(quote_id);
            $('#detraction').val(data.detraction);
            $('#detraction').trigger('change');

            $modalDetraction.modal('show');
        }
    });
}

function showLoader(msg = "Procesando solicitud...") {
    return $.dialog({
        title: false,
        closeIcon: false,
        backgroundDismiss: false,
        theme: 'modern',
        content:
            '<div class="text-center" style="padding:20px;">' +
            '<div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>' +
            '<p class="mt-3">'+ msg +'</p>' +
            '</div>',
    });
}

function raiseQuote() {
    var quote_id = $(this).data('raise');
    var code = ($(this).data('code')===null) ? 'No tiene' : $(this).data('code');

    $.confirm({
        icon: 'fa fa-level-up-alt',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'green',
        columnClass: 'medium',
        title: '¿Está seguro de elevar esta cotización?',
        content: '' +
            '<form action="" class="formName">' +
            '<div class="form-group">' +
            '<strong>Código actual: </strong>' + code +
            '<br><label>Ingrese el código del cliente aquí: </label>' +
            '<input type="text" placeholder="Código" class="name form-control" required />' +
            '</div>' +
            '</form>',
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                btnClass: 'btn-blue',
                action: function () {
                    var name = this.$content.find('.name').val();
                    if(!name || name.trim()===''){
                        $.alert('Ingrese un código válido');
                        return false;
                    }
                    var loader = showLoader("Procesando solicitud, por favor espere...");
                    $.ajax({
                        url: '/dashboard/raise/quote/sale/' + quote_id, // ya SIN /code/...
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        data: {
                            code_customer: name   // <- aquí mandamos el código
                        },
                        success: function (data) {
                            console.log(data);
                            loader.close(); // Cerrar loader
                            $.alert("Cotización elevada.");
                            setTimeout(function () {
                                getDataQuotes(1);
                            }, 2000);
                        },
                        error: function (xhr) {
                            loader.close(); // Cerrar loader también en errores
                            let msg = "Sucedió un error en el servidor. Intente nuevamente.";
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            }
                            $.alert(msg);
                        },
                    });
                    //$.alert('Your name is ' + name);
                }
            },
            cancel: {
                text: 'CANCELAR',
                action: function (e) {
                    $.alert("Cotización no elevada.");
                },
            },
        }
    });

}

function raise2Quote() {
    var quote_id = $(this).data('raise2');
    var code = ($(this).data('code')===null) ? 'No tiene' : $(this).data('code');

    $.confirm({
        icon: 'fa fa-level-up-alt',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'green',
        columnClass: 'medium',
        title: '¿Está seguro de modificar el codigo del cliente?',
        content: '' +
            '<form action="" class="formName">' +
            '<div class="form-group">' +
            '<strong>Código actual: </strong>' + code +
            '<br><label>Ingrese el código del cliente aquí: </label>' +
            '<input type="text" placeholder="Código" class="name form-control" required />' +
            '</div>' +
            '</form>',
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                btnClass: 'btn-blue',
                action: function () {
                    var name = this.$content.find('.name').val();
                    if(!name || name.trim()===''){
                        $.alert('Ingrese un código válido');
                        return false;
                    }
                    $.ajax({
                        url: '/dashboard/raise/quote/'+quote_id+'/code/'+name,
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        processData:false,
                        contentType:false,
                        success: function (data) {
                            console.log(data);
                            $.alert("Código modificado correctamente.");
                            setTimeout( function () {
                                /*location.reload();*/
                                getDataQuotes(1);
                            }, 2000 )
                        },
                        error: function (data) {
                            $.alert("Sucedió un error en el servidor. Intente nuevamente.");
                        },
                    });
                    //$.alert('Your name is ' + name);
                }
            },
            cancel: {
                text: 'CANCELAR',
                action: function (e) {
                    $.alert("Cotización no elevada.");
                },
            },
        }
    });

}

function showModalDecimals() {
    $('#decimals').val('');
    $('#decimals').trigger('change');
    var quote_id = $(this).data('decimals');
    $.ajax({
        url: "/dashboard/get/decimals/quote/"+quote_id,
        type: 'GET',
        dataType: 'json',
        success: function (data) {
            $formDecimals.find("[id=quote_id]").val(quote_id);
            $('#decimals').val(data.decimals);
            $('#decimals').trigger('change');

            $modalDecimals.modal('show');
        }
    });
}

function saveDecimals() {
    var button = $(this);
    button.attr("disabled", true);
    var form = $formDecimals[0];
    $.confirm({
        icon: 'fas fa-toggle-on',
        theme: 'modern',
        closeIcon: false,
        animation: 'zoom',
        type: 'green',
        columnClass: 'medium',
        title: '¿Está seguro de guardar la elección?',
        content: 'Mostrar decimales implica que el PDF va a mostrar los valores con decimales.<br>Ocultar decimales implica que el PDF mostrará valores sin decimales.'  ,
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                btnClass: 'btn-blue',
                action: function () {
                    $.ajax({
                        url: '/dashboard/change/decimals/quote',
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        data: new FormData(form),
                        processData:false,
                        contentType:false,
                        success: function (data) {
                            console.log(data);
                            $.alert(data.message);
                            setTimeout( function () {
                                button.attr("disabled", false);
                                $modalDecimals.modal('hide');
                                getDataQuotes(1);
                            }, 2000 )
                        },
                        error: function (data) {
                            button.attr("disabled", false);
                            $.alert("Sucedió un error en el servidor. Intente nuevamente.");
                        },
                    });
                }
            },
            cancel: {
                text: 'CANCELAR',
                action: function (e) {
                    button.attr("disabled", false);
                    $.alert("No se guardó ninguún dato.");
                },
            },
        }
    });

}

function deselevarQuote() {
    event.preventDefault();
    var quote_id = $(this).data('deselevar');

    $.confirm({
        icon: 'fas fa-level-down',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'green',
        columnClass: 'medium',
        title: '¿Está seguro de regresar a enviado esta cotización?',
        content: 'Se va a regresar el estado enviado y se inhabilitará la orden de ejecución.',
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                btnClass: 'btn-blue',
                action: function () {
                    $.ajax({
                        url: '/dashboard/deselevar/quote/'+quote_id,
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        processData:false,
                        contentType:false,
                        success: function (data) {
                            console.log(data);
                            $.alert(data.message);
                            setTimeout( function () {
                                /*location.reload();*/
                                getDataQuotes(1);

                            }, 2000 )
                        },
                        error: function (data) {
                            $.alert("Sucedió un error en el servidor. Intente nuevamente.");
                        },
                    });
                    //$.alert('Your name is ' + name);
                }
            },
            cancel: {
                text: 'CANCELAR',
                action: function (e) {
                    $.alert("Se canceló el proceso.");
                },
            },
        }
    });

}

function exportQuotes() {
    var start  = $('#start').val();
    var end  = $('#end').val();
    var stateQuote = $("#stateQuote").val();

    console.log(start);
    console.log(end);

    if ( stateQuote == "" )
    {
        stateQuote = "all";
    }

    console.log(stateQuote);

    if ( start == '' || end == '' )
    {
        console.log('Sin fechas');
        $.confirm({
            icon: 'fas fa-file-excel',
            theme: 'modern',
            closeIcon: true,
            animation: 'zoom',
            type: 'green',
            title: 'No especificó fechas',
            content: 'Si no hay fechas se descargará todas las cotizaciones',
            buttons: {
                confirm: {
                    text: 'DESCARGAR',
                    action: function (e) {
                        //$.alert('Descargado igual');
                        console.log(start);
                        console.log(end);

                        var query = {
                            start: start,
                            end: end,
                            stateQuote: stateQuote
                        };

                        $.alert('Descargando archivo ...');

                        var url = "/dashboard/exportar/reporte/cotizaciones/v2/?" + $.param(query);

                        window.location = url;

                    },
                },
                cancel: {
                    text: 'CANCELAR',
                    action: function (e) {
                        $.alert("Exportación cancelada.");
                    },
                },
            },
        });
    } else {
        console.log('Con fechas');
        console.log(JSON.stringify(start));
        console.log(JSON.stringify(end));

        var query = {
            start: start,
            end: end,
            stateQuote: stateQuote
        };

        toastr.success('Descargando archivo ...', 'Éxito',
            {
                "closeButton": true,
                "debug": false,
                "newestOnTop": false,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "2000",
                "timeOut": "2000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            });

        var url = "/dashboard/exportar/reporte/cotizaciones/v2/?" + $.param(query);

        window.location = url;

    }

}

function renewQuote() {
    var quote_id = $(this).data('renew');
    var button = $(this);
    button.attr("disabled", true);
    $.confirm({
        icon: 'fas fa-sync',
        theme: 'modern',
        closeIcon: false,
        animation: 'zoom',
        type: 'green',
        columnClass: 'medium',
        title: '¿Está seguro de renovar esta cotización?',
        content: 'Se va a crear una nueva cotización pero con todos los mismos contenidos.',
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                btnClass: 'btn-blue',
                action: function () {
                    $.ajax({
                        url: '/dashboard/renew/quote/'+quote_id,
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        processData:false,
                        contentType:false,
                        success: function (data) {
                            console.log(data);
                            $.alert(data.message);
                            setTimeout( function () {
                                button.attr("disabled", false);
                                location.href = data.url;
                            }, 2000 )
                        },
                        error: function (data) {
                            button.attr("disabled", false);
                            $.alert("Sucedió un error en el servidor. Intente nuevamente.");
                        },
                    });
                    //$.alert('Your name is ' + name);
                }
            },
            cancel: {
                text: 'CANCELAR',
                action: function (e) {
                    button.attr("disabled", false);
                    $.alert("Cotización no recotizada.");
                },
            },
        }
    });

}

function cancelQuote() {
    var quote_id = $(this).data('delete');
    var description = $(this).data('name');

    $.confirm({
        icon: 'fas fa-frown',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'red',
        title: '¿Está seguro de eliminar esta cotización?',
        content: description,
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                action: function (e) {
                    $.ajax({
                        url: '/dashboard/destroy/quote/sale/'+quote_id,
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        processData:false,
                        contentType:false,
                        success: function (data) {
                            console.log(data);
                            $.alert("Cotización anulada.");
                            setTimeout( function () {
                                /*location.reload();*/
                                getDataQuotes(1);
                            }, 2000 )
                        },
                        error: function (data) {
                            $.alert("Sucedió un error en el servidor. Intente nuevamente.");
                        },
                    });
                },
            },
            cancel: {
                text: 'CANCELAR',
                action: function (e) {
                    $.alert("Anulación cancelada.");
                },
            },
        },
    });

}

function confirmQuote() {
    event.preventDefault();
    var quote_id = $(this).data('confirm');
    var description = $(this).data('name');

    var status_send = $(this).data('status');

    if ( status_send == 0 )
    {
        toastr.error('No puede confirmar sin antes enviar.', 'Error',
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
                "timeOut": "3000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            });
        return;
    }

    $.confirm({
        icon: 'fas fa-smile',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'green',
        title: '¿Está seguro de confirmar esta cotización? ',
        content: description,
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                action: function (e) {
                    $.ajax({
                        url: '/dashboard/confirm/quote/'+quote_id,
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        processData:false,
                        contentType:false,
                        success: function (data) {
                            console.log(data);
                            $.alert("Cotización confirmada con éxito.");
                            setTimeout( function () {
                                /*location.reload();*/
                                getDataQuotes(1);
                            }, 2000 )
                        },
                        error: function (data) {
                            $.alert("Sucedió un error en el servidor. Intente nuevamente.");
                        },
                    });
                },
            },
            cancel: {
                text: 'CANCELAR',
                action: function (e) {
                    $.alert("Confirmación cancelada.");
                },
            },
        },
    });

}

function sendQuote() {
    var quote_id = $(this).data('send');
    var description = $(this).data('name');

    $.confirm({
        icon: 'fas fa-paper-plane',
        theme: 'modern',
        closeIcon: true,
        animation: 'zoom',
        type: 'green',
        title: '¿Está seguro de enviar esta cotización? ',
        content: description,
        buttons: {
            confirm: {
                text: 'CONFIRMAR',
                action: function (e) {
                    $.ajax({
                        url: '/dashboard/send/quote/'+quote_id,
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        processData:false,
                        contentType:false,
                        success: function (data) {
                            console.log(data);
                            $.alert("Cotización enviada con éxito.");
                            setTimeout( function () {
                                /*location.reload();*/
                                getDataQuotes(1);
                            }, 2000 )
                        },
                        error: function (data) {
                            $.alert("Sucedió un error en el servidor. Intente nuevamente.");
                        },
                    });
                },
            },
            cancel: {
                text: 'CANCELAR',
                action: function (e) {
                    $.alert("Envío cancelado.");
                },
            },
        },
    });

}

function destroySubCategory() {
    event.preventDefault();
    // Obtener la URL
    var deleteUrl = $formDelete.data('url');
    $.ajax({
        url: deleteUrl,
        method: 'POST',
        data: new FormData(this),
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
            $modalDelete.modal('hide');
            setTimeout( function () {
                location.reload();
            }, 2000 )
        },
        error: function (data) {
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


        },
    });
}

function exportExcel() {
    var start  = $('#start').val();
    var end  = $('#end').val();
    var startDate   = moment(start, "DD/MM/YYYY");
    var endDate     = moment(end, "DD/MM/YYYY");

    console.log(start);
    console.log(end);
    console.log(startDate);
    console.log(endDate);

    if ( start == '' || end == '' )
    {
        console.log('Sin fechas');
        $.confirm({
            icon: 'fas fa-file-excel',
            theme: 'modern',
            closeIcon: true,
            animation: 'zoom',
            type: 'green',
            title: 'No especificó fechas',
            content: 'Si no hay fechas se descargará todos los ingresos',
            buttons: {
                confirm: {
                    text: 'DESCARGAR',
                    action: function (e) {
                        //$.alert('Descargado igual');
                        console.log(start);
                        console.log(end);

                        var query = {
                            start: start,
                            end: end
                        };

                        $.alert('Descargando archivo ...');

                        var url = "/dashboard/exportar/reporte/egresos/proveedores/?" + $.param(query);

                        window.location = url;

                    },
                },
                cancel: {
                    text: 'CANCELAR',
                    action: function (e) {
                        $.alert("Exportación cancelada.");
                    },
                },
            },
        });
    } else {
        console.log('Con fechas');
        console.log(JSON.stringify(start));
        console.log(JSON.stringify(end));

        var query = {
            start: start,
            end: end
        };

        toastr.success('Descargando archivo ...', 'Éxito',
            {
                "closeButton": true,
                "debug": false,
                "newestOnTop": false,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "2000",
                "timeOut": "2000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            });

        var url = "/dashboard/exportar/reporte/egresos/proveedores/?" + $.param(query);

        window.location = url;

    }

}

function showDataSearch() {
    getDataQuotes(1)
}

function showData() {
    //event.preventDefault();
    var numberPage = $(this).attr('data-item');
    console.log(numberPage);
    getDataQuotes(numberPage)
}

function getDataQuotes($numberPage) {
    $('[data-toggle="tooltip"]').tooltip('dispose').tooltip({
        selector: '[data-toggle="tooltip"]'
    });

    var description_quote = $('#description_quote').val();
    var year = $('#year').val();
    var code = $('#code').val();
    var order = $('#order').val();
    var customer = $('#customer').val();
    var creator = $('#creator').val();
    var stateQuote = $('#stateQuote').val();
    var startDate = $('#start').val();
    var endDate = $('#end').val();

    $.get('/dashboard/get/data/quotes/sale/index/v2/'+$numberPage, {
        description_quote:description_quote,
        year: year,
        code: code,
        order: order,
        customer: customer,
        creator: creator,
        stateQuote: stateQuote,
        startDate: startDate,
        endDate: endDate,
    }, function(data) {
        if ( data.data.length == 0 )
        {
            renderDataQuotesEmpty(data);
        } else {
            renderDataQuotes(data);
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

function renderDataQuotesEmpty(data) {
    var dataAccounting = data.data;
    var pagination = data.pagination;
    console.log(dataAccounting);
    console.log(pagination);

    $("#body-table").html('');
    $("#pagination").html('');
    $("#textPagination").html('');
    $("#textPagination").html('Mostrando '+pagination.startRecord+' a '+pagination.endRecord+' de '+pagination.totalFilteredRecords+' cotizaciones');
    $('#numberItems').html('');
    $('#numberItems').html(pagination.totalFilteredRecords);

    renderDataTableEmpty();
}

function renderDataQuotes(data) {
    var dataQuotes = data.data;
    var pagination = data.pagination;
    console.log(dataQuotes);
    console.log(pagination);

    $("#body-table").html('');
    $("#pagination").html('');
    $("#textPagination").html('');
    $("#textPagination").html('Mostrando '+pagination.startRecord+' a '+pagination.endRecord+' de '+pagination.totalFilteredRecords+' cotizaciones.');
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
    clone.querySelector("[data-id]").innerHTML = data.id;
    clone.querySelector("[data-code]").innerHTML = data.code;
    clone.querySelector("[data-description]").innerHTML = data.description;
    clone.querySelector("[data-date_quote]").innerHTML = data.date_quote;
    //clone.querySelector("[data-date_validate]").innerHTML = data.date_validate;
    clone.querySelector("[data-deadline]").innerHTML = data.deadline;
    clone.querySelector("[data-time_delivery]").innerHTML = data.time_delivery;
    clone.querySelector("[data-customer]").innerHTML = data.customer;
    clone.querySelector("[data-order]").innerHTML = data.order;
    clone.querySelector("[data-total_sunat]").innerHTML = data.total_sunat;
    clone.querySelector("[data-total]").innerHTML = data.total_cliente;
    clone.querySelector("[data-services_add]").innerHTML = data.total_services;
    clone.querySelector("[data-currency]").innerHTML = data.currency;
    clone.querySelector("[data-state]").innerHTML = data.stateText;
    //clone.querySelector("[data-created_at]").innerHTML = data.created_at;
    clone.querySelector("[data-creator]").innerHTML = data.creator;
   /* clone.querySelector("[data-decimals]").innerHTML = data.decimals;*/

    var botones = clone.querySelector("[data-buttons]");

    if ( data.state == "created" )
    {
        var cloneBtnCreated = activateTemplate('#template-btn_created');

        if ( $.inArray('show_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/ver/cotizacion/venta/'+data.id;
            cloneBtnCreated.querySelector("[data-ver_cotizacion]").setAttribute("href", url);
        } else {
            let element = cloneBtnCreated.querySelector("[data-ver_cotizacion]");
            if (element) {
                element.style.display = 'none';
            }
        }

        /*if ( $.inArray('update_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/editar/planos/cotizacion/'+data.id;
            cloneBtnCreated.querySelector("[data-editar_planos]").setAttribute("href", url);
        } else {
            let element = cloneBtnCreated.querySelector("[data-editar_planos]");
            if (element) {
                element.style.display = 'none';
            }
        }*/

        if ( $.inArray('printCustomer_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/imprimir/cotizacion/cliente/'+data.id;
            cloneBtnCreated.querySelector("[data-imprimir_cliente]").setAttribute("href", url);
        } else {
            let element = cloneBtnCreated.querySelector("[data-imprimir_cliente]");
            if (element) {
                element.style.display = 'none';
            }
        }

        /*if ( $.inArray('printInternal_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/imprimir/interno/'+data.id;
            cloneBtnCreated.querySelector("[data-imprimir_interna]").setAttribute("href", url);
        } else {
            let element = cloneBtnCreated.querySelector("[data-imprimir_interna]");
            if (element) {
                element.style.display = 'none';
            }
        }*/

        if ( $.inArray('send_quote', $permissions) !== -1 ) {
            cloneBtnCreated.querySelector("[data-enviar]").setAttribute("data-send", data.id);
            cloneBtnCreated.querySelector("[data-enviar]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnCreated.querySelector("[data-enviar]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('update_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/editar/cotizacion/venta/'+data.id;
            cloneBtnCreated.querySelector("[data-editar]").setAttribute("href", url);
        } else {
            let element = cloneBtnCreated.querySelector("[data-editar]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('confirm_quote', $permissions) !== -1 ) {
            cloneBtnCreated.querySelector("[data-confirmar]").setAttribute("data-status", data.send_state);
            cloneBtnCreated.querySelector("[data-confirmar]").setAttribute("data-confirm", data.id);
            cloneBtnCreated.querySelector("[data-confirmar]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnCreated.querySelector("[data-confirmar]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('destroy_quote', $permissions) !== -1 ) {
            cloneBtnCreated.querySelector("[data-anular]").setAttribute("data-delete", data.id);
            cloneBtnCreated.querySelector("[data-anular]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnCreated.querySelector("[data-anular]");
            if (element) {
                element.style.display = 'none';
            }
        }

        /*if ( $.inArray('renew_quote', $permissions) !== -1 ) {
            cloneBtnCreated.querySelector("[data-recotizar]").setAttribute("data-renew", data.id);
            cloneBtnCreated.querySelector("[data-recotizar]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnCreated.querySelector("[data-recotizar]");
            if (element) {
                element.style.display = 'none';
            }
        }*/

        /*if ( $.inArray('list_quote', $permissions) !== -1 ) {
            cloneBtnCreated.querySelector("[data-decimales]").setAttribute("data-decimals", data.id);
            cloneBtnCreated.querySelector("[data-decimales]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnCreated.querySelector("[data-decimales]");
            if (element) {
                element.style.display = 'none';
            }
        }*/

        botones.append(cloneBtnCreated);
    }

    if ( data.state == "send" )
    {
        var cloneBtnSend = activateTemplate('#template-btn_send');
        if ( $.inArray('show_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/ver/cotizacion/venta/'+data.id;
            cloneBtnSend.querySelector("[data-ver_cotizacion]").setAttribute("href", url);
        } else {
            let element = cloneBtnSend.querySelector("[data-ver_cotizacion]");
            if (element) {
                element.style.display = 'none';
            }
        }

        /*if ( $.inArray('update_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/editar/planos/cotizacion/'+data.id;
            cloneBtnSend.querySelector("[data-editar_planos]").setAttribute("href", url);
        } else {
            let element = cloneBtnSend.querySelector("[data-editar_planos]");
            if (element) {
                element.style.display = 'none';
            }
        }*/

        if ( $.inArray('printCustomer_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/imprimir/cotizacion/cliente/'+data.id;
            cloneBtnSend.querySelector("[data-imprimir_cliente]").setAttribute("href", url);
        } else {
            let element = cloneBtnSend.querySelector("[data-imprimir_cliente]");
            if (element) {
                element.style.display = 'none';
            }
        }

        /*if ( $.inArray('printInternal_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/imprimir/interno/'+data.id;
            cloneBtnSend.querySelector("[data-imprimir_interna]").setAttribute("href", url);
        } else {
            let element = cloneBtnSend.querySelector("[data-imprimir_interna]");
            if (element) {
                element.style.display = 'none';
            }
        }*/

        if ( $.inArray('update_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/editar/cotizacion/venta/'+data.id;
            cloneBtnSend.querySelector("[data-editar]").setAttribute("href", url);
        } else {
            let element = cloneBtnSend.querySelector("[data-editar]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('confirm_quote', $permissions) !== -1 ) {
            cloneBtnSend.querySelector("[data-confirmar]").setAttribute("data-status", data.send_state);
            cloneBtnSend.querySelector("[data-confirmar]").setAttribute("data-confirm", data.id);
            cloneBtnSend.querySelector("[data-confirmar]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnSend.querySelector("[data-confirmar]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('destroy_quote', $permissions) !== -1 ) {
            cloneBtnSend.querySelector("[data-anular]").setAttribute("data-delete", data.id);
            cloneBtnSend.querySelector("[data-anular]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnSend.querySelector("[data-anular]");
            if (element) {
                element.style.display = 'none';
            }
        }

        /*if ( $.inArray('renew_quote', $permissions) !== -1 ) {
            cloneBtnSend.querySelector("[data-recotizar]").setAttribute("data-renew", data.id);
            cloneBtnSend.querySelector("[data-recotizar]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnSend.querySelector("[data-recotizar]");
            if (element) {
                element.style.display = 'none';
            }
        }*/

        /*if ( $.inArray('list_quote', $permissions) !== -1 ) {
            cloneBtnSend.querySelector("[data-decimales]").setAttribute("data-decimals", data.id);
            cloneBtnSend.querySelector("[data-decimales]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnSend.querySelector("[data-decimales]");
            if (element) {
                element.style.display = 'none';
            }
        }*/

        botones.append(cloneBtnSend);
    }

    if ( data.state == "confirm" )
    {
        var cloneBtnConfirm = activateTemplate('#template-btn_confirm');

        if ( $.inArray('show_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/ver/cotizacion/venta/'+data.id;
            cloneBtnConfirm.querySelector("[data-ver_cotizacion]").setAttribute("href", url);
        } else {
            let element = cloneBtnConfirm.querySelector("[data-ver_cotizacion]");
            if (element) {
                element.style.display = 'none';
            }
        }

        /*if ( $.inArray('update_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/editar/planos/cotizacion/'+data.id;
            cloneBtnConfirm.querySelector("[data-editar_planos]").setAttribute("href", url);
        } else {
            let element = cloneBtnConfirm.querySelector("[data-editar_planos]");
            if (element) {
                element.style.display = 'none';
            }
        }*/

        if ( $.inArray('printCustomer_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/imprimir/cotizacion/cliente/'+data.id;
            cloneBtnConfirm.querySelector("[data-imprimir_cliente]").setAttribute("href", url);
        } else {
            let element = cloneBtnConfirm.querySelector("[data-imprimir_cliente]");
            if (element) {
                element.style.display = 'none';
            }
        }

        /*if ( $.inArray('printInternal_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/imprimir/interno/'+data.id;
            cloneBtnConfirm.querySelector("[data-imprimir_interna]").setAttribute("href", url);
        } else {
            let element = cloneBtnConfirm.querySelector("[data-imprimir_interna]");
            if (element) {
                element.style.display = 'none';
            }
        }*/

        if ( $.inArray('raise_quote', $permissions) !== -1 ) {
            cloneBtnConfirm.querySelector("[data-regresar_enviado]").setAttribute("data-deselevar", data.id);
            cloneBtnConfirm.querySelector("[data-regresar_enviado]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnConfirm.querySelector("[data-regresar_enviado]");
            if (element) {
                element.style.display = 'none';
            }
        }

        /*if ( $.inArray('adjust_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/ajustar/cotizacion/'+data.id;
            cloneBtnConfirm.querySelector("[data-ajustar_porcentajes]").setAttribute("href", url);
        } else {
            let element = cloneBtnConfirm.querySelector("[data-ajustar_porcentajes]");
            if (element) {
                element.style.display = 'none';
            }
        }*/

        if ( $.inArray('raise_quote', $permissions) !== -1 ) {
            cloneBtnConfirm.querySelector("[data-elevar]").setAttribute("data-raise", data.id);
            cloneBtnConfirm.querySelector("[data-elevar]").setAttribute("data-code", data.order);
            cloneBtnConfirm.querySelector("[data-elevar]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnConfirm.querySelector("[data-elevar]");
            if (element) {
                element.style.display = 'none';
            }
        }

        /*if ( $.inArray('confirm_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/cotizar/soles/cotizacion/'+data.id;
            cloneBtnConfirm.querySelector("[data-cotizar_soles]").setAttribute("href", url);
        } else {
            let element = cloneBtnConfirm.querySelector("[data-cotizar_soles]");
            if (element) {
                element.style.display = 'none';
            }
        }*/

        if ( $.inArray('destroy_quote', $permissions) !== -1 ) {
            cloneBtnConfirm.querySelector("[data-anular]").setAttribute("data-delete", data.id);
            cloneBtnConfirm.querySelector("[data-anular]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnConfirm.querySelector("[data-anular]");
            if (element) {
                element.style.display = 'none';
            }
        }

        /*if ( $.inArray('renew_quote', $permissions) !== -1 ) {
            cloneBtnConfirm.querySelector("[data-recotizar]").setAttribute("data-renew", data.id);
            cloneBtnConfirm.querySelector("[data-recotizar]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnConfirm.querySelector("[data-recotizar]");
            if (element) {
                element.style.display = 'none';
            }
        }*/

        /*if ( $.inArray('list_quote', $permissions) !== -1 ) {
            cloneBtnConfirm.querySelector("[data-decimales]").setAttribute("data-decimals", data.id);
            cloneBtnConfirm.querySelector("[data-decimales]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnConfirm.querySelector("[data-decimales]");
            if (element) {
                element.style.display = 'none';
            }
        }*/

        botones.append(cloneBtnConfirm);
    }

    if ( data.state == "raise" )
    {
        var cloneBtnRaised = activateTemplate('#template-btn_raised');

        if ( $.inArray('show_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/ver/cotizacion/venta/'+data.id;
            cloneBtnRaised.querySelector("[data-ver_cotizacion]").setAttribute("href", url);
        } else {
            let element = cloneBtnRaised.querySelector("[data-ver_cotizacion]");
            if (element) {
                element.style.display = 'none';
            }
        }

        /*if ( $.inArray('update_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/editar/planos/cotizacion/'+data.id;
            cloneBtnRaised.querySelector("[data-editar_planos]").setAttribute("href", url);
        } else {
            let element = cloneBtnRaised.querySelector("[data-editar_planos]");
            if (element) {
                element.style.display = 'none';
            }
        }*/

        if ( $.inArray('printCustomer_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/imprimir/cotizacion/cliente/'+data.id;
            cloneBtnRaised.querySelector("[data-imprimir_cliente]").setAttribute("href", url);
        } else {
            let element = cloneBtnRaised.querySelector("[data-imprimir_cliente]");
            if (element) {
                element.style.display = 'none';
            }
        }

        /*if ( $.inArray('printInternal_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/imprimir/interno/'+data.id;
            cloneBtnRaised.querySelector("[data-imprimir_interna]").setAttribute("href", url);
        } else {
            let element = cloneBtnRaised.querySelector("[data-imprimir_interna]");
            if (element) {
                element.style.display = 'none';
            }
        }*/

        if ( $.inArray('raise_quote', $permissions) !== -1 ) {
            cloneBtnRaised.querySelector("[data-modificar_codigo]").setAttribute("data-raise2", data.id);
            cloneBtnRaised.querySelector("[data-modificar_codigo]").setAttribute("data-code", data.order);
            cloneBtnRaised.querySelector("[data-modificar_codigo]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnRaised.querySelector("[data-modificar_codigo]");
            if (element) {
                element.style.display = 'none';
            }
        }

        /*if ( $.inArray('raise_quote', $permissions) !== -1 ) {
            cloneBtnRaised.querySelector("[data-seleccionar_detraccion]").setAttribute("data-detraction", data.id);
            cloneBtnRaised.querySelector("[data-seleccionar_detraccion]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnRaised.querySelector("[data-seleccionar_detraccion]");
            if (element) {
                element.style.display = 'none';
            }
        }*/

        if ( $.inArray('finish_quote', $permissions) !== -1 ) {
            cloneBtnRaised.querySelector("[data-finalizar]").setAttribute("data-finish", data.id);
            cloneBtnRaised.querySelector("[data-finalizar]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnRaised.querySelector("[data-finalizar]");
            if (element) {
                element.style.display = 'none';
            }
        }

        /*if ( $.inArray('replacement_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/reemplazar/materiales/cotizacion/'+data.id;
            cloneBtnRaised.querySelector("[data-reemplazar_materiales]").setAttribute("href", url);
        } else {
            let element = cloneBtnRaised.querySelector("[data-reemplazar_materiales]");
            if (element) {
                element.style.display = 'none';
            }
        }*/

        /*if ( $.inArray('finishEquipment_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/finalizar/equipos/cotizacion/'+data.id;
            cloneBtnRaised.querySelector("[data-finalizar_equipos]").setAttribute("href", url);
        } else {
            let element = cloneBtnRaised.querySelector("[data-finalizar_equipos]");
            if (element) {
                element.style.display = 'none';
            }
        }*/

        if ( $.inArray('raise_quote', $permissions) !== -1 ) {
            cloneBtnRaised.querySelector("[data-regresar_enviado]").setAttribute("data-deselevar", data.id);
            cloneBtnRaised.querySelector("[data-regresar_enviado]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnRaised.querySelector("[data-regresar_enviado]");
            if (element) {
                element.style.display = 'none';
            }
        }

        /*if ( $.inArray('confirm_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/cotizar/soles/cotizacion/'+data.id;
            cloneBtnRaised.querySelector("[data-cotizar_soles]").setAttribute("href", url);
        } else {
            let element = cloneBtnRaised.querySelector("[data-cotizar_soles]");
            if (element) {
                element.style.display = 'none';
            }
        }*/

        /*if ( $.inArray('VBFinances_quote', $permissions) !== -1 ) {
            cloneBtnRaised.querySelector("[data-visto_bueno_finanzas]").setAttribute("data-vb_finances", data.id);
            cloneBtnRaised.querySelector("[data-visto_bueno_finanzas]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnRaised.querySelector("[data-visto_bueno_finanzas]");
            if (element) {
                element.style.display = 'none';
            }
        }*/

        /*if ( $.inArray('VBOperations_quote', $permissions) !== -1 ) {
            cloneBtnRaised.querySelector("[data-visto_bueno_operaciones]").setAttribute("data-vb_operations", data.id);
            cloneBtnRaised.querySelector("[data-visto_bueno_operaciones]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnRaised.querySelector("[data-visto_bueno_operaciones]");
            if (element) {
                element.style.display = 'none';
            }
        }*/

        if ( $.inArray('destroy_quote', $permissions) !== -1 ) {
            cloneBtnRaised.querySelector("[data-anular]").setAttribute("data-delete", data.id);
            cloneBtnRaised.querySelector("[data-anular]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnRaised.querySelector("[data-anular]");
            if (element) {
                element.style.display = 'none';
            }
        }

        /*if ( $.inArray('renew_quote', $permissions) !== -1 ) {
            cloneBtnRaised.querySelector("[data-recotizar]").setAttribute("data-renew", data.id);
            cloneBtnRaised.querySelector("[data-recotizar]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnRaised.querySelector("[data-recotizar]");
            if (element) {
                element.style.display = 'none';
            }
        }*/

        /*if ( $.inArray('list_quote', $permissions) !== -1 ) {
            cloneBtnRaised.querySelector("[data-decimales]").setAttribute("data-decimals", data.id);
            cloneBtnRaised.querySelector("[data-decimales]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnRaised.querySelector("[data-decimales]");
            if (element) {
                element.style.display = 'none';
            }
        }*/

        botones.append(cloneBtnRaised);
    }

    /*if ( data.state == "VB_finance" )
    {
        var cloneBtnVB_finance = activateTemplate('#template-btn_VB_finance');

        if ( $.inArray('show_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/ver/cotizacion/'+data.id;
            cloneBtnVB_finance.querySelector("[data-ver_cotizacion]").setAttribute("href", url);
        } else {
            let element = cloneBtnVB_finance.querySelector("[data-ver_cotizacion]");
            if (element) {
                element.style.display = 'none';
            }
        }

        /!*if ( $.inArray('update_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/editar/planos/cotizacion/'+data.id;
            cloneBtnRaised.querySelector("[data-editar_planos]").setAttribute("href", url);
        } else {
            let element = cloneBtnRaised.querySelector("[data-editar_planos]");
            if (element) {
                element.style.display = 'none';
            }
        }*!/

        if ( $.inArray('printCustomer_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/imprimir/cotizacion/cliente/'+data.id;
            cloneBtnVB_finance.querySelector("[data-imprimir_cliente]").setAttribute("href", url);
        } else {
            let element = cloneBtnVB_finance.querySelector("[data-imprimir_cliente]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('printInternal_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/imprimir/interno/'+data.id;
            cloneBtnVB_finance.querySelector("[data-imprimir_interna]").setAttribute("href", url);
        } else {
            let element = cloneBtnVB_finance.querySelector("[data-imprimir_interna]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('raise_quote', $permissions) !== -1 ) {
            cloneBtnVB_finance.querySelector("[data-modificar_codigo]").setAttribute("data-raise2", data.id);
            cloneBtnVB_finance.querySelector("[data-modificar_codigo]").setAttribute("data-code", data.order);
            cloneBtnVB_finance.querySelector("[data-modificar_codigo]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnVB_finance.querySelector("[data-modificar_codigo]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('raise_quote', $permissions) !== -1 ) {
            cloneBtnVB_finance.querySelector("[data-seleccionar_detraccion]").setAttribute("data-detraction", data.id);
            cloneBtnVB_finance.querySelector("[data-seleccionar_detraccion]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnVB_finance.querySelector("[data-seleccionar_detraccion]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('finish_quote', $permissions) !== -1 ) {
            cloneBtnVB_finance.querySelector("[data-finalizar]").setAttribute("data-finish", data.id);
            cloneBtnVB_finance.querySelector("[data-finalizar]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnVB_finance.querySelector("[data-finalizar]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('replacement_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/reemplazar/materiales/cotizacion/'+data.id;
            cloneBtnVB_finance.querySelector("[data-reemplazar_materiales]").setAttribute("href", url);
        } else {
            let element = cloneBtnVB_finance.querySelector("[data-reemplazar_materiales]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('finishEquipment_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/finalizar/equipos/cotizacion/'+data.id;
            cloneBtnVB_finance.querySelector("[data-finalizar_equipos]").setAttribute("href", url);
        } else {
            let element = cloneBtnVB_finance.querySelector("[data-finalizar_equipos]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('raise_quote', $permissions) !== -1 ) {
            cloneBtnVB_finance.querySelector("[data-regresar_enviado]").setAttribute("data-deselevar", data.id);
            cloneBtnVB_finance.querySelector("[data-regresar_enviado]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnVB_finance.querySelector("[data-regresar_enviado]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('VBOperations_quote', $permissions) !== -1 ) {
            cloneBtnVB_finance.querySelector("[data-visto_bueno_operaciones]").setAttribute("data-vb_operations", data.id);
            cloneBtnVB_finance.querySelector("[data-visto_bueno_operaciones]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnVB_finance.querySelector("[data-visto_bueno_operaciones]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('destroy_quote', $permissions) !== -1 ) {
            cloneBtnVB_finance.querySelector("[data-anular]").setAttribute("data-delete", data.id);
            cloneBtnVB_finance.querySelector("[data-anular]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnVB_finance.querySelector("[data-anular]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('renew_quote', $permissions) !== -1 ) {
            cloneBtnVB_finance.querySelector("[data-recotizar]").setAttribute("data-renew", data.id);
            cloneBtnVB_finance.querySelector("[data-recotizar]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnVB_finance.querySelector("[data-recotizar]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('list_quote', $permissions) !== -1 ) {
            cloneBtnVB_finance.querySelector("[data-decimales]").setAttribute("data-decimals", data.id);
            cloneBtnVB_finance.querySelector("[data-decimales]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnVB_finance.querySelector("[data-decimales]");
            if (element) {
                element.style.display = 'none';
            }
        }

        botones.append(cloneBtnVB_finance);
    }

    if ( data.state == "VB_operation" )
    {
        var cloneBtnVB_operation = activateTemplate('#template-btn_VB_operation');

        if ( $.inArray('show_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/ver/cotizacion/'+data.id;
            cloneBtnVB_operation.querySelector("[data-ver_cotizacion]").setAttribute("href", url);
        } else {
            let element = cloneBtnVB_operation.querySelector("[data-ver_cotizacion]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('printCustomer_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/imprimir/cotizacion/cliente/'+data.id;
            cloneBtnVB_operation.querySelector("[data-imprimir_cliente]").setAttribute("href", url);
        } else {
            let element = cloneBtnVB_operation.querySelector("[data-imprimir_cliente]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('printInternal_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/imprimir/interno/'+data.id;
            cloneBtnVB_operation.querySelector("[data-imprimir_interna]").setAttribute("href", url);
        } else {
            let element = cloneBtnVB_operation.querySelector("[data-imprimir_interna]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('raise_quote', $permissions) !== -1 ) {
            cloneBtnVB_operation.querySelector("[data-modificar_codigo]").setAttribute("data-raise2", data.id);
            cloneBtnVB_operation.querySelector("[data-modificar_codigo]").setAttribute("data-code", data.order);
            cloneBtnVB_operation.querySelector("[data-modificar_codigo]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnVB_operation.querySelector("[data-modificar_codigo]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('raise_quote', $permissions) !== -1 ) {
            cloneBtnVB_operation.querySelector("[data-seleccionar_detraccion]").setAttribute("data-detraction", data.id);
            cloneBtnVB_operation.querySelector("[data-seleccionar_detraccion]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnVB_operation.querySelector("[data-seleccionar_detraccion]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('finish_quote', $permissions) !== -1 ) {
            cloneBtnVB_operation.querySelector("[data-finalizar]").setAttribute("data-finish", data.id);
            cloneBtnVB_operation.querySelector("[data-finalizar]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnVB_operation.querySelector("[data-finalizar]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('finishEquipment_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/finalizar/equipos/cotizacion/'+data.id;
            cloneBtnVB_operation.querySelector("[data-finalizar_equipos]").setAttribute("href", url);
        } else {
            let element = cloneBtnVB_operation.querySelector("[data-finalizar_equipos]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('update_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/modificar/lista/materiales/cotizacion/'+data.id;
            cloneBtnVB_operation.querySelector("[data-modificar_lista_materiales]").setAttribute("href", url);
        } else {
            let element = cloneBtnVB_operation.querySelector("[data-modificar_lista_materiales]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('raise_quote', $permissions) !== -1 ) {
            cloneBtnVB_operation.querySelector("[data-regresar_enviado]").setAttribute("data-deselevar", data.id);
            cloneBtnVB_operation.querySelector("[data-regresar_enviado]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnVB_operation.querySelector("[data-regresar_enviado]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('destroy_quote', $permissions) !== -1 ) {
            cloneBtnVB_operation.querySelector("[data-anular]").setAttribute("data-delete", data.id);
            cloneBtnVB_operation.querySelector("[data-anular]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnVB_operation.querySelector("[data-anular]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('renew_quote', $permissions) !== -1 ) {
            cloneBtnVB_operation.querySelector("[data-recotizar]").setAttribute("data-renew", data.id);
            cloneBtnVB_operation.querySelector("[data-recotizar]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnVB_operation.querySelector("[data-recotizar]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('list_quote', $permissions) !== -1 ) {
            cloneBtnVB_operation.querySelector("[data-decimales]").setAttribute("data-decimals", data.id);
            cloneBtnVB_operation.querySelector("[data-decimales]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnVB_operation.querySelector("[data-decimales]");
            if (element) {
                element.style.display = 'none';
            }
        }

        botones.append(cloneBtnVB_operation);
    }*/

    if ( data.state == "close" )
    {
        var cloneBtnClose = activateTemplate('#template-btn_close');

        if ( $.inArray('show_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/ver/cotizacion/venta/'+data.id;
            cloneBtnClose.querySelector("[data-ver_cotizacion]").setAttribute("href", url);
        } else {
            let element = cloneBtnClose.querySelector("[data-ver_cotizacion]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('printCustomer_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/imprimir/cotizacion/cliente/'+data.id;
            cloneBtnClose.querySelector("[data-imprimir_cliente]").setAttribute("href", url);
        } else {
            let element = cloneBtnClose.querySelector("[data-imprimir_cliente]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('raise_quote', $permissions) !== -1 ) {
            cloneBtnClose.querySelector("[data-modificar_codigo]").setAttribute("data-raise2", data.id);
            cloneBtnClose.querySelector("[data-modificar_codigo]").setAttribute("data-code", data.order);
            cloneBtnClose.querySelector("[data-modificar_codigo]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnClose.querySelector("[data-modificar_codigo]");
            if (element) {
                element.style.display = 'none';
            }
        }

        /*if ( $.inArray('renew_quote', $permissions) !== -1 ) {
            cloneBtnClose.querySelector("[data-recotizar]").setAttribute("data-renew", data.id);
            cloneBtnClose.querySelector("[data-recotizar]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnClose.querySelector("[data-recotizar]");
            if (element) {
                element.style.display = 'none';
            }
        }*/

        if ( $.inArray('finish_quote', $permissions) !== -1 ) {
            cloneBtnClose.querySelector("[data-reactivar]").setAttribute("data-active", data.id);
            cloneBtnClose.querySelector("[data-reactivar]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnClose.querySelector("[data-reactivar]");
            if (element) {
                element.style.display = 'none';
            }
        }

        botones.append(cloneBtnClose);
    }

    if ( data.state == "requote" ) {
        var cloneBtnRequote = activateTemplate('#template-btn_requote');

        if ( $.inArray('show_quote', $permissions) !== -1 ) {
            let url = document.location.origin+ '/dashboard/ver/cotizacion/venta/'+data.id;
            cloneBtnRequote.querySelector("[data-ver_cotizacion]").setAttribute("href", url);
        } else {
            let element = cloneBtnRequote.querySelector("[data-ver_cotizacion]");
            if (element) {
                element.style.display = 'none';
            }
        }

        if ( $.inArray('renew_quoteSale', $permissions) !== -1 ) {
            cloneBtnRequote.querySelector("[data-recotizar]").setAttribute("data-renew", data.id);
            cloneBtnRequote.querySelector("[data-recotizar]").setAttribute("data-name", data.description);
        } else {
            let element = cloneBtnRequote.querySelector("[data-recotizar]");
            if (element) {
                element.style.display = 'none';
            }
        }

        botones.append(cloneBtnRequote);
    }

    $("#body-table").append(clone);

    $('[data-toggle="tooltip"]').tooltip();
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

function submitFormEditFacturacion(event) {
    event.preventDefault();
    var button = $(this);

    button.prop('disabled', true);
    var createUrl = $formEditFacturacion.data('url');
    $.ajax({
        url: createUrl, // La URL a la que enviarás la solicitud
        method: 'POST', // El método HTTP que utilizarás (en este caso, POST)
        data: new FormData($('#formEditFacturacion')[0]),
        processData:false,
        contentType:false,
        success: function(data) {
            // Esta función se ejecutará si la solicitud fue exitosa
            // La variable 'response' contendrá los datos devueltos por el servidor (según el tipo de datos especificado en 'dataType')
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
            $modalEditFacturacion.modal('hide');
            setTimeout( function () {
                button.attr("disabled", false);
                showDataSearch();
                //location.reload();
            }, 100 )
        },
        error: function(data) {
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
            button.attr("disabled", false);
            // Esta función se ejecutará si ocurre un error en la solicitud
            // Puedes utilizar las variables 'jqXHR', 'textStatus' y 'errorThrown' para obtener información sobre el error
        }
    });
}

function showModalEditFacturacion() {
    var invoice_id = $(this).attr('data-formEditFacturacion');
    var type = $(this).attr('data-type');

    $formEditFacturacion.find("[id=invoice_id]").val(invoice_id);
    $formEditFacturacion.find("[id=type]").val(type);

    if ( invoice_id == "" )
    {
        invoice_id = "nn";
    }

    $.get("/dashboard/get/info/facturacion/expense/supplier/"+invoice_id+"/"+type, function (data) {
        console.log(data);

        //$formEditFacturacion.find("[id=invoice_id]").val(invoice_id);

        $('#state').val(data.state);
        $('#state').trigger('change');

        $modalEditFacturacion.modal('show');
    }, "json");

}

function activateTemplate(id) {
    var t = document.querySelector(id);
    return document.importNode(t.content, true);
}