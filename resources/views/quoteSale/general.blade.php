@extends('layouts.appAdmin2')

@section('openQuote')
    menu-open
@endsection

@section('activeQuote')
    active
@endsection

@section('activeGeneralQuote')
    active
@endsection

@section('title')
    Cotizaciones
@endsection

@section('styles-plugins')
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker.standalone.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.standalone.css') }}">

@endsection

@section('styles')
    <style>
        .select2-search__field{
            width: 100% !important;
        }
        .letraTabla {
            font-family: "Calibri", Arial, sans-serif; /* Utiliza Calibri si está instalado, de lo contrario, usa Arial o una fuente sans-serif similar */
            font-size: 15px; /* Tamaño de fuente 11 */
        }
        .normal-title {
            background-color: #203764; /* Color deseado para el fondo */
            color: #fff; /* Color deseado para el texto */
            text-align: center;
        }
        .cliente-title {
            background-color: #FFC000; /* Color deseado para el fondo */
            color: #000; /* Color deseado para el texto */
            text-align: center;
        }
        .trabajo-title {
            background-color: #00B050; /* Color deseado para el fondo */
            color: #000; /* Color deseado para el texto */
            text-align: center;
        }
        .documentacion-title {
            background-color: #FFC000; /* Color deseado para el fondo */
            color: #000; /* Color deseado para el texto */
            text-align: center;
        }
        .importe-title {
            background-color: #00B050; /* Color deseado para el fondo */
            color: #000; /* Color deseado para el texto */
            text-align: center;
        }
        .facturacion-title {
            background-color: #FFC000; /* Color deseado para el fondo */
            color: #000; /* Color deseado para el texto */
            text-align: center;
        }
        .abono-title {
            background-color: #00B050; /* Color deseado para el fondo */
            color: #000; /* Color deseado para el texto */
            text-align: center;
        }
        .busqueda-avanzada {
            display: none;
        }

        #btnBusquedaAvanzada {
            display: inline-block;
            text-decoration: none;
            color: #007bff;
            border-bottom: 1px solid transparent;
            transition: border-bottom 0.3s ease;
        }
        #btnBusquedaAvanzada:hover {
            border-bottom: 2px solid #007bff;
        }
        .vertical-center {
            display: flex;
            align-items: center;
        }
        .datepicker-orient-top {
            top: 100px !important;
        }
    </style>
@endsection

@section('page-header')
    <h1 class="page-title">Listado General de Cotizaciones</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Listado de cotizaciones</h5>
    @can('create_quoteSale')
        <a href="{{ route('quoteSale.create') }}" class="btn btn-outline-success btn-sm float-right" > <i class="fa fa-plus font-20"></i> Nueva cotización </a>
    @endcan
    {{--@hasanyrole('admin|principal')
    <button type="button" id="btn-export" class="btn btn-outline-primary btn-sm float-right mr-2" > <i class="far fa-file-excel"></i> Descargar Excel </button>
    @endhasanyrole--}}
    {{--@can('create_quoteSale')
        <button type="button" id="btn-download" class="btn btn-outline-success btn-sm float-right mr-2" > <i class="fas fa-download"></i> Exportar cotizaciones </button>
    @endcan--}}
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('quoteSale.index') }}"><i class="fa fa-archive"></i> Cotizaciones</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Listado</li>
    </ol>
@endsection

@section('content')
    <input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">
    <!--begin::Form-->
    <form action="#">
        <!--begin::Card-->
        <!--begin::Input group-->
        <div class="row">
            <div class="col-md-12">
                <!-- Barra de búsqueda -->
                <div class="input-group">
                    <input type="text" id="description_quote" class="form-control" placeholder="Descripción de la cotización..." autocomplete="off">
                    <div class="input-group-append ">
                        <button class="btn btn-primary" type="button" id="btn-search">Buscar</button>
                        <a href="#" id="btnBusquedaAvanzada" class="vertical-center ml-3 mt-2">Búsqueda Avanzada</a>
                    </div>
                </div>

                <!-- Sección de búsqueda avanzada (inicialmente oculta) -->
                <div class="mt-3 busqueda-avanzada">
                    <!-- Aquí coloca más campos de búsqueda avanzada -->
                    <div class="row">

                        <div class="col-md-3">
                            <label for="year">Año del Trabajo:</label>
                            <select id="year" class="form-control form-control-sm select2" style="width: 100%;">
                                <option value="">TODOS</option>
                                @for ($i=0; $i<count($arrayYears); $i++)
                                    <option value="{{ $arrayYears[$i] }}">{{ $arrayYears[$i] }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="code">N° de cotización:</label>
                            <input type="text" id="code" class="form-control form-control-sm" placeholder="412" autocomplete="off">

                        </div>
                        <div class="col-md-3">
                            <label for="order">Orden de Compra/Servicio:</label>
                            <input type="text" id="order" class="form-control form-control-sm" placeholder="42000" autocomplete="off">

                        </div>
                        <div class="col-md-3">
                            <label for="customer">Cliente:</label>
                            <select id="customer" name="customer" class="form-control form-control-sm select2" style="width: 100%;">
                                <option value="">TODOS</option>
                                @for ($i=0; $i<count($arrayCustomers); $i++)
                                    <option value="{{ $arrayCustomers[$i]['id'] }}">{{ $arrayCustomers[$i]['business_name'] }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <br>

                    <div class="row">
                        <div class="col-md-3">
                            <label for="stateQuote">Estado Cotización:</label>
                            <select id="stateQuote" name="stateQuote" class="form-control form-control-sm select2" style="width: 100%;">
                                <option value="">TODOS</option>
                                @foreach ($arrayStates as $state)
                                    <option value="{{ $state['value'] }}">{{ $state['display'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="campoExtra">Fechas de Cotización:</label>
                            <div class="col-md-12" id="sandbox-container">
                                <div class="input-daterange input-group" id="datepicker">
                                    <input type="text" class="form-control form-control-sm date-range-filter" id="start" name="start" autocomplete="off">
                                    <span class="input-group-addon">&nbsp;&nbsp;&nbsp; al &nbsp;&nbsp;&nbsp; </span>
                                    <input type="text" class="form-control form-control-sm date-range-filter" id="end" name="end" autocomplete="off">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="creator">Creador:</label>
                            <select id="creator" name="creator" class="form-control form-control-sm select2" style="width: 100%;">
                                <option value="">TODOS</option>
                                @for ($i=0; $i<count($arrayUsers); $i++)
                                    <option value="{{ $arrayUsers[$i]['id'] }}">{{ $arrayUsers[$i]['name'] }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <br>

                    <div class="row">

                    </div>

                    <!-- Añade más campos según lo necesario -->
                </div>
            </div>
        </div>
        <!--end::Input group-->
        <!--begin:Action-->
        {{--<div class="col-md-1">
            <label for="btn-search">&nbsp;</label><br>
            <button type="button" id="btn-search" class="btn btn-primary me-5">Buscar</button>
        </div>--}}

    </form>
    <!--end::Form-->

    <!--begin::Toolbar-->
    <div class="d-flex flex-wrap flex-stack pb-7">
        <!--begin::Title-->
        <div class="d-flex flex-wrap align-items-center my-1">
            <h3 class="fw-bolder me-5 my-1"><span id="numberItems"></span> Cotizaciones encontradas
                <span class="text-gray-400 fs-6">por fecha de creación ↓ </span>
            </h3>
        </div>
        <!--end::Title-->
    </div>
    <!--end::Toolbar-->

    <!--begin::Tab Content-->
    <div class="tab-content">
        <!--begin::Tab pane-->
        @can('generarComprobante_quoteSale')
        <hr>
        <div class="row">
            <div class="container-fluid">
                <div class="row text-center">
                    @can('generarBoleta_quoteSale')
                    <div class="col">
                        <a href="{{ route('show.register.comprobante', 'Boleta') }}" type="button" class="btn btn-primary btn-block">Boleta</a>
                    </div>
                    @endcan
                    @can('generarFactura_quoteSale')
                    <div class="col">
                        <a href="{{ route('show.register.comprobante', 'Factura') }}" type="button" class="btn btn-success btn-block">Factura</a>
                    </div>
                    @endcan
                    <div class="col">
                        <a href="{{ route('show.register.comprobante', 'Ticket') }}" type="button" class="btn btn-info btn-block">Ticket de venta</a>
                    </div>
                </div>
            </div>
        </div>
        @endcan
        <hr>
        <div class="table-responsive">
            <table class="table table-bordered letraTabla table-hover table-sm">
                <thead>
                <tr class="normal-title">
                    <th>ID</th>
                    <th>Código</th>
                    <th>Descripción</th>
                    <th>Fecha Cotización</th>
                    {{--<th>Fecha Válida</th>--}}
                    <th>Forma Pago</th>
                    <th>Tiempo Entrega</th>
                    <th>Cliente</th>
                    <th>Orden Servicio</th>
                    <th>Total SUNAT</th>
                    <th>Sercicios Ad.</th>
                    <th>Total CLIENTE</th>
                    <th>Moneda</th>
                    <th>Estado</th>
                    {{--<th>Fecha Creación</th>--}}
                    <th>Creador</th>
                    {{--<th>Decimales</th>--}}
                    <th></th>
                </tr>
                </thead>
                <tbody id="body-table">

                </tbody>
            </table>
        </div>
        <!--end::Tab pane-->
        <!--begin::Pagination-->
        <div class="d-flex flex-stack flex-wrap pt-1">
            <div class="fs-6 fw-bold text-gray-700" id="textPagination"></div>
            <!--begin::Pages-->
            <ul class="pagination" style="margin-left: auto;" id="pagination">

            </ul>
            <!--end::Pages-->
        </div>
        <!--end::Pagination-->
    </div>
    <!--end::Tab Content-->

    <template id="previous-page">
        <li class="page-item previous">
            <a href="#" class="page-link" data-item>
                <!--<i class="previous"></i>-->
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
    </template>

    <template id="item-page">
        <li class="page-item" data-active>
            <a href="#" class="page-link" data-item="">5</a>
        </li>
    </template>

    <template id="next-page">
        <li class="page-item next">
            <a href="#" class="page-link" data-item>
                <!--<i class="next"></i>-->
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    </template>

    <template id="disabled-page">
        <li class="page-item disabled">
            <span class="page-link">...</span>
        </li>
    </template>

    <template id="item-table">
        <tr>
            <td data-id></td>
            <td data-code></td>
            <td data-description></td>
            <td data-date_quote></td>
           {{-- <td data-date_validate></td>--}}
            <td data-deadline></td>

            <td data-time_delivery></td>
            <td data-customer></td>

            <td data-order></td>
            <td data-total_sunat></td>
            <td data-services_add></td>
            <td data-total></td>
            <td data-currency></td>
            <td data-state></td>
            {{--<td data-created_at></td>--}}
            <td data-creator></td>
            {{--<td data-decimals></td>--}}
            <td data-buttons></td>
        </tr>
    </template>

    <template id="item-table-empty">
        <tr>
            <td colspan="15" align="center">No se ha encontrado ningún dato</td>
        </tr>
    </template>

    <template id="template-btn_created">
        <a data-ver_cotizacion href="{{--'+document.location.origin+ '/dashboard/ver/cotizacion/'+item.id+'--}}" class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Ver Detalles"><i class="fa fa-eye"></i></a>
        {{--<a data-editar_planos href="--}}{{--'+document.location.origin+ '/dashboard/editar/planos/cotizacion/'+item.id+'--}}{{--" class="btn bg-lime color-palette btn-sm" data-toggle="tooltip" data-placement="top" title="Editar planos"><i class="fas fa-images"></i></a>
        --}}
        <a data-imprimir_cliente target="_blank" href="{{--' + document.location.origin + '/dashboard/imprimir/cliente/' + item.id +'--}}" class="btn btn-outline-info btn-sm" data-toggle="tooltip" data-placement="top" title="Imprimir para cliente"><i class="fa fa-print"></i></a>
        {{--<a data-imprimir_interna target="_blank" href="--}}{{--' + document.location.origin + '/dashboard/imprimir/interno/' + item.id +'--}}{{--" class="btn btn-outline-dark btn-sm" data-toggle="tooltip" data-placement="top" title="Imprimir interna"><i class="fa fa-print"></i></a>
        --}}
        <button data-enviar data-send="{{--' + item.id + '--}}" data-name="{{--' + item.description_quote + '--}}" class="btn btn-outline-info btn-sm" data-toggle="tooltip" data-placement="top" title="Enviar"><i class="fas fa-file-import"></i></button>
        <a data-editar href="{{--'+document.location.origin+ '/dashboard/editar/cotizacion/'+item.id+'--}}" class="btn btn-outline-warning btn-sm" data-toggle="tooltip" data-placement="top" title="Editar"><i class="fa fa-pen"></i></a>
        <button data-confirmar data-status="{{--'+ item.send_state +'--}}" data-confirm="{{--'+item.id+'--}}" data-name="{{--'+item.description_quote+'--}}" class="btn btn-outline-success btn-sm" data-toggle="tooltip" data-placement="top" title="Confirmar"><i class="fa fa-check"></i></button>
        <button data-anular data-delete="{{--'+item.id+'--}}" data-name="{{--'+item.description_quote+'--}}" class="btn btn-outline-danger btn-sm" data-toggle="tooltip" data-placement="top" title="Anular"><i class="fa fa-trash"></i></button>
        {{--<button data-recotizar data-renew="--}}{{--'+item.id+'--}}{{--" data-name="--}}{{--'+item.description_quote+'--}}{{--" class="btn btn-outline-success btn-sm" data-toggle="tooltip" data-placement="top" title="Recotizar"><i class="fas fa-sync"></i></button>
        <button data-decimales data-decimals="--}}{{--'+item.id+'--}}{{--" data-name="--}}{{--'+item.description_quote+'--}}{{--" class="btn btn-outline-info btn-sm" data-toggle="tooltip" data-placement="top" title="Mostrar decimales"><i class="fas fa-toggle-on"></i></button>--}}
    </template>

    <template id="template-btn_send">
        <a data-ver_cotizacion href="{{--'+document.location.origin+ '/dashboard/ver/cotizacion/'+item.id+'--}}" class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Ver Detalles"><i class="fa fa-eye"></i></a>
        {{--<a data-editar_planos href="--}}{{--'+document.location.origin+ '/dashboard/editar/planos/cotizacion/'+item.id+'--}}{{--" class="btn bg-lime color-palette btn-sm" data-toggle="tooltip" data-placement="top" title="Editar planos"><i class="fas fa-images"></i></a>
        --}}
        <a data-imprimir_cliente target="_blank" href="{{--' + document.location.origin + '/dashboard/imprimir/cliente/' + item.id +'--}}" class="btn btn-outline-info btn-sm" data-toggle="tooltip" data-placement="top" title="Imprimir para cliente"><i class="fa fa-print"></i></a>
        {{--<a data-imprimir_interna target="_blank" href="--}}{{--' + document.location.origin + '/dashboard/imprimir/interno/' + item.id +'--}}{{--" class="btn btn-outline-dark btn-sm" data-toggle="tooltip" data-placement="top" title="Imprimir interna"><i class="fa fa-print"></i></a>
        --}}
        <a data-editar href="{{--'+document.location.origin+ '/dashboard/editar/cotizacion/'+item.id+'--}}" class="btn btn-outline-warning btn-sm" data-toggle="tooltip" data-placement="top" title="Editar"><i class="fa fa-pen"></i></a>
        <button data-confirmar data-status="{{--'+ item.send_state +'--}}" data-confirm="{{--'+item.id+'--}}" data-name="{{--'+item.description_quote+'--}}" class="btn btn-outline-success btn-sm" data-toggle="tooltip" data-placement="top" title="Confirmar"><i class="fa fa-check"></i></button>
        <button data-anular data-delete="{{--'+item.id+'--}}" data-name="{{--'+item.description_quote+'--}}" class="btn btn-outline-danger btn-sm" data-toggle="tooltip" data-placement="top" title="Anular"><i class="fa fa-trash"></i></button>
        {{--<button data-recotizar data-renew="--}}{{--'+item.id+'--}}{{--" data-name="--}}{{--'+item.description_quote+'--}}{{--" class="btn btn-outline-success btn-sm" data-toggle="tooltip" data-placement="top" title="Recotizar"><i class="fas fa-sync"></i></button>
        <button data-decimales data-decimals="--}}{{--'+item.id+'--}}{{--" data-name="--}}{{--'+item.description_quote+'--}}{{--" class="btn btn-outline-info btn-sm" data-toggle="tooltip" data-placement="top" title="Mostrar decimales"><i class="fas fa-toggle-on"></i></button>--}}
    </template>

    <template id="template-btn_confirm">
        <a data-ver_cotizacion href="{{--'+document.location.origin+ '/dashboard/ver/cotizacion/'+item.id+'--}}" class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Ver Detalles"><i class="fa fa-eye"></i></a>
        {{--<a data-editar_planos href="--}}{{--'+document.location.origin+ '/dashboard/editar/planos/cotizacion/'+item.id+'--}}{{--" class="btn bg-lime color-palette btn-sm" data-toggle="tooltip" data-placement="top" title="Editar planos"><i class="fas fa-images"></i></a>
        --}}
        <a data-imprimir_cliente target="_blank" href="{{--' + document.location.origin + '/dashboard/imprimir/cliente/' + item.id +'--}}" class="btn btn-outline-info btn-sm" data-toggle="tooltip" data-placement="top" title="Imprimir para cliente"><i class="fa fa-print"></i></a>
        {{--<a data-imprimir_interna target="_blank" href="--}}{{--' + document.location.origin + '/dashboard/imprimir/interno/' + item.id +'--}}{{--" class="btn btn-outline-dark btn-sm" data-toggle="tooltip" data-placement="top" title="Imprimir interna"><i class="fa fa-print"></i></a>
        --}}
        <button data-regresar_enviado data-deselevar="{{--'+item.id+'--}}" data-name="{{--'+item.description_quote+'--}}" class="btn btn-outline-secondary btn-sm" data-toggle="tooltip" data-placement="top" title="Regresar a enviado"><i class="fas fa-level-down-alt"></i></button>
        {{--<a data-ajustar_porcentajes href="--}}{{--'+document.location.origin+ '/dashboard/ajustar/cotizacion/'+item.id+'--}}{{--" class="btn btn-outline-dark btn-sm" data-toggle="tooltip" data-placement="top" title="Ajustar porcentajes"><i class="fas fa-percentage"></i></a>
        --}}
        <button data-elevar data-raise="{{--' + item.id + '--}}" data-code="{{--' + item.code_customer + '--}}" data-name="{{--' + item.description_quote + '--}}" class="btn btn-outline-success btn-sm" data-toggle="tooltip" data-placement="top" title="Elevar"><i class="fa fa-level-up-alt"></i></button>
        {{--<a data-cotizar_soles href="--}}{{--'+document.location.origin+ '/dashboard/cotizar/soles/cotizacion/'+item.id+'--}}{{--" class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Cotizar en soles"><i class="fa fa-dollar-sign"></i></a>
        --}}
        <button data-anular data-delete="{{--'+item.id+'--}}" data-name="{{--'+item.description_quote+'--}}" class="btn btn-outline-danger btn-sm" data-toggle="tooltip" data-placement="top" title="Anular"><i class="fa fa-trash"></i></button>
        {{--<button data-recotizar data-renew="--}}{{--'+item.id+'--}}{{--" data-name="--}}{{--'+item.description_quote+'--}}{{--" class="btn btn-outline-success btn-sm" data-toggle="tooltip" data-placement="top" title="Recotizar"><i class="fas fa-sync"></i></button>
        <button data-decimales data-decimals="--}}{{--'+item.id+'--}}{{--" data-name="--}}{{--'+item.description_quote+'--}}{{--" class="btn btn-outline-info btn-sm" data-toggle="tooltip" data-placement="top" title="Mostrar decimales"><i class="fas fa-toggle-on"></i></button>--}}
    </template>

    <template id="template-btn_raised">
        <a data-ver_cotizacion href="{{--'+document.location.origin+ '/dashboard/ver/cotizacion/'+item.id+'--}}" class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Ver Detalles"><i class="fa fa-eye"></i></a>
        {{--<a data-editar_planos href="--}}{{--'+document.location.origin+ '/dashboard/editar/planos/cotizacion/'+item.id+'--}}{{--" class="btn bg-lime color-palette btn-sm" data-toggle="tooltip" data-placement="top" title="Editar planos"><i class="fas fa-images"></i></a>
        --}}
        <a data-imprimir_cliente target="_blank" href="{{--' + document.location.origin + '/dashboard/imprimir/cliente/' + item.id +'--}}" class="btn btn-outline-info btn-sm" data-toggle="tooltip" data-placement="top" title="Imprimir para cliente"><i class="fa fa-print"></i></a>
        {{--<a data-imprimir_interna target="_blank" href="--}}{{--' + document.location.origin + '/dashboard/imprimir/interno/' + item.id +'--}}{{--" class="btn btn-outline-dark btn-sm" data-toggle="tooltip" data-placement="top" title="Imprimir interna"><i class="fa fa-print"></i></a>
        --}}
        <button data-modificar_codigo data-raise2="{{--'+item.id+'--}}" data-code="{{--'+item.code_customer+'--}}" data-name="{{--'+item.description_quote+'--}}" class="btn btn-outline-success btn-sm" data-toggle="tooltip" data-placement="top" title="Modificar código"><i class="fa fa-chart-line"></i></button>
        {{--<button data-seleccionar_detraccion data-detraction="--}}{{--'+item.id+'--}}{{--" data-name="--}}{{--'+item.description_quote+'--}}{{--" class="btn btn-outline-success btn-sm" data-toggle="tooltip" data-placement="top" title="Seleccionar tipo de orden"><i class="fas fa-donate"></i></button>
        --}}
        <button data-finalizar data-finish="{{--'+item.id+'--}}" data-name="{{--'+item.description_quote+'--}}" class="btn btn-outline-danger btn-sm" data-toggle="tooltip" data-placement="top" title="Finalizar"><i class="fas fa-window-close"></i></button>
        {{--<a data-reemplazar_materiales href="--}}{{--'+document.location.origin+ '/dashboard/reemplazar/materiales/cotizacion/'+item.id+'--}}{{--" class="btn btn-outline-warning btn-sm" data-toggle="tooltip" data-placement="top" title="Reemplazar materiales"><i class="fas fa-recycle"></i></a>
        <a data-finalizar_equipos href="--}}{{--'+document.location.origin+ '/dashboard/finalizar/equipos/cotizacion/'+item.id+'--}}{{--" class="btn btn-outline-info btn-sm" data-toggle="tooltip" data-placement="top" title="Finalizar equipos"><i class="fas fa-times-circle"></i></a>
        --}}
        <button data-regresar_enviado data-deselevar="{{--'+item.id+'--}}" data-name="{{--'+item.description_quote+'--}}" class="btn btn-outline-secondary btn-sm" data-toggle="tooltip" data-placement="top" title="Regresar a enviado"><i class="fas fa-level-down-alt"></i></button>
        {{--<a data-cotizar_soles href="--}}{{--'+document.location.origin+ '/dashboard/cotizar/soles/cotizacion/'+item.id+'--}}{{--" class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Cotizar en soles"><i class="fa fa-dollar-sign"></i></a>
        --}}{{--<button data-visto_bueno_finanzas data-vb_finances="--}}{{--'+item.id+'--}}{{--" data-name="--}}{{--'+item.description_quote+'--}}{{--" class="btn btn-outline-success btn-sm" data-toggle="tooltip" data-placement="top" title="Visto bueno de finanzas"><i class="fas fa-check-double"></i></button>
        --}}
        {{--<button data-visto_bueno_operaciones data-vb_operations="--}}{{--'+item.id+'--}}{{--" data-name="--}}{{--'+item.description_quote+'--}}{{--" class="btn btn-outline-success btn-sm" data-toggle="tooltip" data-placement="top" title="Visto bueno de operaciones"><i class="fas fa-check-double"></i></button>
        --}}
        <button data-anular data-delete="{{--'+item.id+'--}}" data-name="{{--'+item.description_quote+'--}}" class="btn btn-outline-danger btn-sm" data-toggle="tooltip" data-placement="top" title="Anular"><i class="fa fa-trash"></i></button>
        {{--<button data-recotizar data-renew="--}}{{--'+item.id+'--}}{{--" data-name="--}}{{--'+item.description_quote+'--}}{{--" class="btn btn-outline-success btn-sm" data-toggle="tooltip" data-placement="top" title="Recotizar"><i class="fas fa-sync"></i></button>
        <button data-decimales data-decimals="--}}{{--'+item.id+'--}}{{--" data-name="--}}{{--'+item.description_quote+'--}}{{--" class="btn btn-outline-info btn-sm" data-toggle="tooltip" data-placement="top" title="Mostrar decimales"><i class="fas fa-toggle-on"></i></button>--}}
    </template>

    <template id="template-btn_close">
        <a data-ver_cotizacion href="{{--'+document.location.origin+ '/dashboard/ver/cotizacion/'+item.id+'--}}" class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Ver Detalles"><i class="fa fa-eye"></i></a>
        <a data-imprimir_cliente target="_blank" href="{{--' + document.location.origin + '/dashboard/imprimir/cliente/' + item.id +'--}}" class="btn btn-outline-info btn-sm" data-toggle="tooltip" data-placement="top" title="Imprimir para cliente"><i class="fa fa-print"></i></a>
        <button data-modificar_codigo data-raise2="{{--'+item.id+'--}}" data-code="{{--'+item.code_customer+'--}}" data-name="{{--'+item.description_quote+'--}}" class="btn btn-outline-success btn-sm" data-toggle="tooltip" data-placement="top" title="Modificar código"><i class="fa fa-chart-line"></i></button>
        {{--<button data-recotizar data-renew="--}}{{--'+item.id+'--}}{{--" data-name="--}}{{--'+item.description_quote+'--}}{{--" class="btn btn-outline-success btn-sm" data-toggle="tooltip" data-placement="top" title="Recotizar"><i class="fas fa-sync"></i></button>
        --}}
        <button data-reactivar data-active_quote="{{--'+item.id+'--}}" data-name="{{--'+item.description_quote+'--}}" class="btn btn-outline-warning btn-sm" data-toggle="tooltip" data-placement="top" title="Reactivar"><i class="fas fa-lock-open"></i></button>
    </template>

    <template id="template-btn_requote">
        <a data-ver_cotizacion href="" class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Ver Detalles"><i class="fa fa-eye"></i></a>
        <button data-recotizar data-renew="" data-name="" class="btn btn-outline-success btn-sm" data-toggle="tooltip" data-placement="top" title="Recotizar"><i class="fas fa-sync"></i></button>
    </template>

    {{--<template id="template-btn_VB_finance">
        <a data-ver_cotizacion href="--}}{{--'+document.location.origin+ '/dashboard/ver/cotizacion/'+item.id+'--}}{{--" class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Ver Detalles"><i class="fa fa-eye"></i></a>
        <a data-imprimir_cliente target="_blank" href="--}}{{--' + document.location.origin + '/dashboard/imprimir/cliente/' + item.id +'--}}{{--" class="btn btn-outline-info btn-sm" data-toggle="tooltip" data-placement="top" title="Imprimir para cliente"><i class="fa fa-print"></i></a>
        <a data-imprimir_interna target="_blank" href="--}}{{--' + document.location.origin + '/dashboard/imprimir/interno/' + item.id +'--}}{{--" class="btn btn-outline-dark btn-sm" data-toggle="tooltip" data-placement="top" title="Imprimir interna"><i class="fa fa-print"></i></a>
        <button data-modificar_codigo data-raise2="--}}{{--'+item.id+'--}}{{--" data-code="--}}{{--'+item.code_customer+'--}}{{--" data-name="--}}{{--'+item.description_quote+'--}}{{--" class="btn btn-outline-success btn-sm" data-toggle="tooltip" data-placement="top" title="Modificar código"><i class="fa fa-chart-line"></i></button>
        <button data-seleccionar_detraccion data-detraction="--}}{{--'+item.id+'--}}{{--" data-name="--}}{{--'+item.description_quote+'--}}{{--" class="btn btn-outline-success btn-sm" data-toggle="tooltip" data-placement="top" title="Seleccionar tipo de orden"><i class="fas fa-donate"></i></button>
        <button data-finalizar data-finish="--}}{{--'+item.id+'--}}{{--" data-name="--}}{{--'+item.description_quote+'--}}{{--" class="btn btn-outline-danger btn-sm" data-toggle="tooltip" data-placement="top" title="Finalizar"><i class="fas fa-window-close"></i></button>
        <a data-reemplazar_materiales href="--}}{{--'+document.location.origin+ '/dashboard/reemplazar/materiales/cotizacion/'+item.id+'--}}{{--" class="btn btn-outline-warning btn-sm" data-toggle="tooltip" data-placement="top" title="Reemplazar materiales"><i class="fas fa-recycle"></i></a>
        <a data-finalizar_equipos href="--}}{{--'+document.location.origin+ '/dashboard/finalizar/equipos/cotizacion/'+item.id+'--}}{{--" class="btn btn-outline-info btn-sm" data-toggle="tooltip" data-placement="top" title="Finalizar equipos"><i class="fas fa-times-circle"></i></a>
        <button data-regresar_enviado data-deselevar="--}}{{--'+item.id+'--}}{{--" data-name="--}}{{--'+item.description_quote+'--}}{{--" class="btn btn-outline-secondary btn-sm" data-toggle="tooltip" data-placement="top" title="Regresar a enviado"><i class="fas fa-level-down-alt"></i></button>
        <button data-visto_bueno_operaciones data-vb_operations="--}}{{--'+item.id+'--}}{{--" data-name="--}}{{--'+item.description_quote+'--}}{{--" class="btn btn-outline-success btn-sm" data-toggle="tooltip" data-placement="top" title="Visto bueno de operaciones"><i class="fas fa-check-double"></i></button>
        <button data-anular data-delete="--}}{{--'+item.id+'--}}{{--" data-name="--}}{{--'+item.description_quote+'--}}{{--" class="btn btn-outline-danger btn-sm" data-toggle="tooltip" data-placement="top" title="Anular"><i class="fa fa-trash"></i></button>
        <button data-recotizar data-renew="--}}{{--'+item.id+'--}}{{--" data-name="--}}{{--'+item.description_quote+'--}}{{--" class="btn btn-outline-success btn-sm" data-toggle="tooltip" data-placement="top" title="Recotizar"><i class="fas fa-sync"></i></button>
        <button data-decimales data-decimals="--}}{{--'+item.id+'--}}{{--" data-name="--}}{{--'+item.description_quote+'--}}{{--" class="btn btn-outline-info btn-sm" data-toggle="tooltip" data-placement="top" title="Mostrar decimales"><i class="fas fa-toggle-on"></i></button>
    </template>--}}

    {{--<template id="template-btn_VB_operation">
        <a data-ver_cotizacion href="--}}{{--'+document.location.origin+ '/dashboard/ver/cotizacion/'+item.id+'--}}{{--" class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Ver Detalles"><i class="fa fa-eye"></i></a>
        <a data-imprimir_cliente target="_blank" href="--}}{{--' + document.location.origin + '/dashboard/imprimir/cliente/' + item.id +'--}}{{--" class="btn btn-outline-info btn-sm" data-toggle="tooltip" data-placement="top" title="Imprimir para cliente"><i class="fa fa-print"></i></a>
        <a data-imprimir_interna target="_blank" href="--}}{{--' + document.location.origin + '/dashboard/imprimir/interno/' + item.id +'--}}{{--" class="btn btn-outline-dark btn-sm" data-toggle="tooltip" data-placement="top" title="Imprimir interna"><i class="fa fa-print"></i></a>
        <button data-modificar_codigo data-raise2="--}}{{--'+item.id+'--}}{{--" data-code="--}}{{--'+item.code_customer+'--}}{{--" data-name="--}}{{--'+item.description_quote+'--}}{{--" class="btn btn-outline-success btn-sm" data-toggle="tooltip" data-placement="top" title="Modificar código"><i class="fa fa-chart-line"></i></button>
        <button data-seleccionar_detraccion data-detraction="--}}{{--'+item.id+'--}}{{--" data-name="--}}{{--'+item.description_quote+'--}}{{--" class="btn btn-outline-success btn-sm" data-toggle="tooltip" data-placement="top" title="Seleccionar tipo de orden"><i class="fas fa-donate"></i></button>
        <button data-finalizar data-finish="--}}{{--'+item.id+'--}}{{--" data-name="--}}{{--'+item.description_quote+'--}}{{--" class="btn btn-outline-danger btn-sm" data-toggle="tooltip" data-placement="top" title="Finalizar"><i class="fas fa-window-close"></i></button>
        <a data-finalizar_equipos href="--}}{{--'+document.location.origin+ '/dashboard/finalizar/equipos/cotizacion/'+item.id+'--}}{{--" class="btn btn-outline-info btn-sm" data-toggle="tooltip" data-placement="top" title="Finalizar equipos"><i class="fas fa-times-circle"></i></a>
        <a data-modificar_lista_materiales href="--}}{{--'+document.location.origin+ '/dashboard/modificar/lista/materiales/cotizacion/'+item.id+'--}}{{--" class="btn btn-outline-warning btn-sm" data-toggle="tooltip" data-placement="top" title="Editar lista materiales"><i class="fas fa-edit"></i></a>
        <button data-regresar_enviado data-deselevar="--}}{{--'+item.id+'--}}{{--" data-name="--}}{{--'+item.description_quote+'--}}{{--" class="btn btn-outline-secondary btn-sm" data-toggle="tooltip" data-placement="top" title="Regresar a enviado"><i class="fas fa-level-down-alt"></i></button>
        <button data-anular data-delete="--}}{{--'+item.id+'--}}{{--" data-name="--}}{{--'+item.description_quote+'--}}{{--" class="btn btn-outline-danger btn-sm" data-toggle="tooltip" data-placement="top" title="Anular"><i class="fa fa-trash"></i></button>
        <button data-recotizar data-renew="--}}{{--'+item.id+'--}}{{--" data-name="--}}{{--'+item.description_quote+'--}}{{--" class="btn btn-outline-success btn-sm" data-toggle="tooltip" data-placement="top" title="Recotizar"><i class="fas fa-sync"></i></button>
        <button data-decimales data-decimals="--}}{{--'+item.id+'--}}{{--" data-name="--}}{{--'+item.description_quote+'--}}{{--" class="btn btn-outline-info btn-sm" data-toggle="tooltip" data-placement="top" title="Mostrar decimales"><i class="fas fa-toggle-on"></i></button>
    </template>--}}

    <div id="modalDetraction" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Seleccionar tipo de orden</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <form id="formDetraction" data-url="{{ route('detraction.change') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="quote_id" name="quote_id">
                        <strong>Cambie o Seleccione el tipo de orden</strong>
                        <select id="detraction" name="detraction" class="form-control select2" style="width: 100%;">
                            <option value=""></option>
                            <option value="nn">Ninguno</option>
                            <option value="oc">Orden de Compra</option>
                            <option value="os">Orden de Servicio</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" id="btn-change" class="btn btn-success">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @can('destroy_quote')
        <div id="modalDelete" class="modal fade" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Confirmar eliminación</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <form id="formDelete" data-url="{{ route('subcategory.destroy') }}">
                        @csrf
                        <div class="modal-body">
                            <input type="hidden" id="subcategory_id" name="subcategory_id">
                            <strong>¿Está seguro de eliminar esta subcategoría?</strong>
                            <p id="name"></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger">Eliminar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    <div id="modalDecimals" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Seleccionar visualizar decimales</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <form id="formDecimals" data-url="{{ route('decimals.change') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="quote_id" name="quote_id">
                        <strong>Cambie o Seleccione la visualización de decimales</strong>
                        <select id="decimals" name="decimals" class="form-control select2" style="width: 100%;">
                            <option value=""></option>
                            <option value="1">Mostrar decimales</option>
                            <option value="0">Ocultar decimales</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" id="btn-changeDecimals" class="btn btn-success">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('plugins')
    <!-- Datatables -->
    <script src="{{ asset('admin/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>

    <script src="{{ asset('admin/plugins/moment/moment.min.js') }}"></script>

    <script src="{{ asset('admin/plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-datepicker/locales/bootstrap-datepicker.es.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/inputmask/min/jquery.inputmask.bundle.min.js') }}"></script>

    <script>
        $(function () {

            $('#detraction').select2({
                placeholder: "Seleccione"
            });
            $('#decimals').select2({
                placeholder: "Seleccione"
            });


        })
    </script>
@endsection

@section('scripts')
    <script>
        $(function () {
            //Initialize Select2 Elements
            $('#year').select2({
                placeholder: "Selecione año",
                allowClear: true
            });

            $('#customer').select2({
                placeholder: "Selecione",
                allowClear: true
            });

            $('#creator').select2({
                placeholder: "Selecione",
                allowClear: true
            });

            $('#stateQuote').select2({
                placeholder: "Selecione",
                allowClear: true
            });

            $('#decimals').select2({
                placeholder: "Seleccione"
            });

        })
    </script>
    <script src="{{ asset('js/quoteSale/general.js') }}"></script>

@endsection