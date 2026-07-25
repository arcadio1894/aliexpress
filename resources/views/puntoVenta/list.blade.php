@extends('layouts.appAdmin2')

@section('openPuntoVenta')
    menu-open
@endsection

@section('activePuntoVenta')
    active
@endsection

@section('activeListPuntoVenta')
    active
@endsection

@section('title')
    Ventas
@endsection

@section('styles-plugins')
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker.standalone.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.standalone.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">

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

        .bg-pago-parcial-rojo {
            background-color: #f8d7da !important;
            color: #000 !important;
        }

        .bg-pago-parcial-naranja {
            background-color: #ffe5b4 !important;
            color: #000 !important;
        }

        .bg-pago-parcial-verde {
            background-color: #d4edda !important;
            color: #000 !important;
        }

        .pp-progress-container {
            padding-right: 60px;
        }

        .pp-progress-wrap {
            position: relative;
            height: 26px;
            background-color: #e9ecef;
            border-radius: 4px;
            overflow: visible;
        }

        .pp-progress-fill {
            height: 100%;
            width: 0%;
            background-color: #b7e4c7;
        }

        .pp-progress-over {
            position: absolute;
            top: 0;
            left: 100%;
            height: 100%;
            width: 0%;
            background-color: #ffd6a5;
        }

        .pp-progress-mark-100 {
            position: absolute;
            top: 0;
            left: 100%;
            height: 100%;
            width: 2px;
            background-color: #198754;
        }

        .pp-progress-text {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 26px;
            line-height: 26px;
            text-align: center;
            font-weight: bold;
            color: #000;
        }

        #globalProcessLoader {
            position: fixed;
            z-index: 99999;
            inset: 0;
        }

        .global-loader-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
        }

        .global-loader-box {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 340px;
            max-width: calc(100% - 30px);
            transform: translate(-50%, -50%);
            background: #fff;
            border-radius: 8px;
            padding: 25px 20px;
            text-align: center;
            box-shadow: 0 8px 30px rgba(0,0,0,0.25);
        }

        .global-loader-message {
            font-weight: 700;
            font-size: 16px;
            color: #222;
            margin-bottom: 6px;
        }

        .global-loader-submessage {
            font-size: 12px;
            color: #666;
        }

        .modal-nc-items-content {
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }

        .modal-nc-items-body {
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .nc-partial-items-scroll {
            flex: 1;
            max-height: 430px;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 6px;
        }

        .modal-nc-items-footer {
            flex-shrink: 0;
            background: #ffffff;
            border-top: 1px solid #dee2e6;
        }

        .nc-partial-items-scroll::-webkit-scrollbar {
            width: 8px;
        }

        .nc-partial-items-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .nc-partial-items-scroll::-webkit-scrollbar-thumb {
            background: #b8b8b8;
            border-radius: 10px;
        }

        .nc-partial-items-scroll::-webkit-scrollbar-thumb:hover {
            background: #999999;
        }

        /*
         * Encabezado de cada grupo fijo mientras se recorren sus items
         */
        [data-itemable-group] .card-header {
            position: sticky;
            top: 0;
            z-index: 5;
            background: #ffffff;
            border-bottom: 1px solid #dee2e6;
        }

        /*
         * Separación visual entre grupos
         */
        [data-itemable-group] {
            overflow: visible;
        }

        /*
         * Item seleccionado
         */
        .nc-partial-item-row.border-success {
            background: #f1fbf4 !important;
        }
    </style>
@endsection

@section('page-header')
    <h1 class="page-title">Pedidos de Clientes</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Listado de pedidos</h5>

    {{--@can('create_referralGuide')
        <a href="{{ route('referral.guide.create') }}" class="btn btn-outline-success btn-sm float-right" > <i class="fa fa-plus font-20"></i> Nueva Guía de remisión </a>
    @endcan
    @can('download_referralGuide')
        <button type="button" id="btn-download" class="btn btn-outline-success btn-sm float-right mr-2" > <i class="fas fa-download"></i> Exportar guías</button>
    @endcan--}}
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('puntoVenta.list') }}"><i class="fa fa-archive"></i> Ventas</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Listado</li>
    </ol>
@endsection

@section('content')
    {{--<input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">--}}
    <!--begin::Form-->
    <form action="#">
        <!--begin::Card-->
        <!--begin::Input group-->
        <div class="row">
            <div class="col-md-12">
                <!-- Barra de búsqueda -->
                <div class="input-group">
                    <input type="text" id="code" class="form-control" placeholder="Código del pedido..." autocomplete="off">
                    <div class="input-group-append ">
                        <button class="btn btn-primary" type="button" id="btn-search">Buscar</button>
                        <a href="#" id="btnBusquedaAvanzada" class="vertical-center ml-3 mt-2">Búsqueda Avanzada</a>
                    </div>
                </div>

                <!-- Sección de búsqueda avanzada (inicialmente oculta) -->
                <div class="mt-3 busqueda-avanzada">
                    <!-- Aquí coloca más campos de búsqueda avanzada -->
                    <div class="row">
                        <div class="col-md-2">
                            <label for="year">Año de registro:</label>
                            <select id="year" class="form-control form-control-sm select2" style="width: 100%;">
                                <option value="">TODOS</option>
                                @for ($i=0; $i<count($arrayYears); $i++)
                                    <option value="{{ $arrayYears[$i] }}">{{ $arrayYears[$i] }}</option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="campoExtra">Fechas de venta:</label>
                            <div class="col-md-12" id="sandbox-container">
                                <div class="input-daterange input-group" id="datepicker">
                                    <input type="text" class="form-control form-control-sm date-range-filter" id="start" name="start" autocomplete="off">
                                    <span class="input-group-addon">&nbsp;&nbsp;&nbsp; al &nbsp;&nbsp;&nbsp; </span>
                                    <input type="text" class="form-control form-control-sm date-range-filter" id="end" name="end" autocomplete="off">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label for="filter_customer_id">Cliente:</label>
                            <select id="filter_customer_id" class="form-control form-control-sm select2" style="width: 100%;">
                                <option value="">TODOS</option>
                                <option value="venta_directa">VENTA DIRECTA</option>
                                <option value="cotizacion_sin_cliente">COTIZACIÓN SIN CLIENTE</option>

                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">
                                        {{ $customer->business_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="filter_payment_status">Cumplimiento:</label>
                            <select id="filter_payment_status" class="form-control form-control-sm select2" style="width: 100%;">
                                <option value="">TODOS</option>
                                <option value="rojo">Menos del 50%</option>
                                <option value="naranja">50% a 99.99%</option>
                                <option value="verde">100% o más</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="filter_dispatch_status">Despacho:</label>
                            <select id="filter_dispatch_status" class="form-control form-control-sm select2" style="width: 100%;">
                                <option value="">TODOS</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="despachado">Despachado</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mt-2">

                        <div class="col-md-3">
                            <label for="filter_cash_box_id">Método de pago / Caja:</label>
                            <select id="filter_cash_box_id" class="form-control form-control-sm select2" style="width: 100%;">
                                <option value="">TODOS</option>
                                <option value="pago_parcial">PAGO PARCIAL</option>

                                @foreach($cashBoxes as $cashBox)
                                    <option value="{{ $cashBox->id }}"
                                            data-type="{{ $cashBox->type }}"
                                            data-uses_subtypes="{{ $cashBox->uses_subtypes ? 1 : 0 }}">
                                        {{ $cashBox->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3" id="filter_cash_box_subtype_wrap" style="display:none;">
                            <label for="filter_cash_box_subtype_id">Canal / Subtipo:</label>
                            <select id="filter_cash_box_subtype_id" class="form-control form-control-sm select2" style="width: 100%;">
                                <option value="">TODOS</option>

                                @foreach($subtypes as $subtype)
                                    <option value="{{ $subtype->id }}">
                                        {{ $subtype->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="filter_invoice_status">Comprobante:</label>
                            <select id="filter_invoice_status" class="form-control form-control-sm select2" style="width: 100%;">
                                <option value="">TODOS</option>
                                <option value="con_comprobante">Con comprobante</option>
                                <option value="sin_comprobante">Sin comprobante</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="filter_sale_status">Estado Venta</label>
                            <select id="filter_sale_status" class="form-control form-control-sm select2" style="width: 100%;">
                                <option value="active" selected>Activas</option>
                                <option value="annulled">Anuladas</option>
                            </select>
                        </div>
                    </div>

                    <!-- Añade más campos según lo necesario -->
                </div>
            </div>
        </div>
        <!--end::Input group-->

    </form>
    <!--end::Form-->

    <!--begin::Toolbar-->
    <div class="d-flex flex-wrap flex-stack pb-7">
        <!--begin::Title-->
        <div class="d-flex flex-wrap align-items-center my-1">
            <h3 class="fw-bolder me-5 my-1"><span id="numberItems"></span> Pedidos encontrados
                <span class="text-gray-400 fs-6">por fecha de creación ↓ </span>
            </h3>
        </div>
        <!--end::Title-->
    </div>
    <!--end::Toolbar-->

    <!--begin::Tab Content-->
    <div class="tab-content">
        <!--begin::Tab pane-->
        <hr>
        <div class="table-responsive">
            <table class="table table-bordered letraTabla table-hover table-sm mb-5">
                <thead>
                <tr class="normal-title">
                    {{--<th>ID</th>--}}
                    <th>Código</th>
                    <th>Fecha Venta</th>
                    <th>Cliente</th>
                    <th>Moneda</th>
                    <th>Total</th>
                    <th>Metodo de Pago</th>
                    <th id="th-dispatch-or-annulled">Estado Despacho</th>
                    <th>Estado Comprobante</th>
                    <th>Acciones</th>
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
            {{--<td data-id></td>--}}
            <td data-code></td>
            <td data-date></td>
            <td data-customer></td>
            <td data-currency></td>
            <td data-total></td>
            <td data-tipo_pago></td>
            <td data-dispatch_status></td>
            <td data-estado_comprobante></td>
            <td data-buttons></td>
        </tr>
    </template>

    <template id="item-table-empty">
        <tr>
            <td colspan="6" align="center">No se ha encontrado ningún dato</td>
        </tr>
    </template>

    <template id="template-active">
        <a href="" target="_blank" data-print_recibo data-id="" class="btn btn-outline-dark btn-sm" data-toggle="tooltip" data-placement="top" title="Imprimir boleta"><i class="fas fa-print"></i></a>

        <button type="button"
                data-recuperar_archivos_nubefact
                data-sale_id=""
                class="btn btn-outline-primary btn-sm"
                data-toggle="tooltip"
                data-placement="top"
                title="Recuperar archivos del comprobante">
            <i class="fas fa-sync-alt"></i>
        </button>

        <button data-ver_detalles data-id="" class="btn btn-outline-secondary btn-sm" data-toggle="tooltip" data-placement="top" title="Ver Detalles"><i class="fas fa-list-ol"></i></button>
        <button data-anular data-id="" data-type_document="" class="btn btn-outline-danger btn-sm" data-toggle="tooltip" data-placement="top" title="Anular Orden"><i class="fas fa-trash-alt"></i></button>

        <button data-consultar_anulacion data-sale_id="" class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Consultar Anulación">
            <img src="{{ asset('images/sale/consultar_anulacion2.png') }}" alt="Generar" style="width: 18px; height: 18px;">
        </button>

        <button href="" data-generar_comprobante data-sale_id="" class="btn btn-warning btn-sm" data-toggle="tooltip" data-placement="top" title="Generar comprobante">
            <img src="{{ asset('images/sale/facturacion_electronica.png') }}" alt="Generar" style="width: 16px; height: 16px;">
        </button>

        <button data-pagos_parciales data-sale_id="" class="btn btn-outline-info btn-sm" data-toggle="tooltip" data-placement="top" title="Pagos Parciales">
            <img src="{{ asset('images/sale/dinero.png') }}" alt="Generar" style="width: 18px; height: 18px;">
        </button>

        <button data-generar_nota_credito data-sale_id="" class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Generar Nota de Crédito">
            <i class="fas fa-file-invoice-dollar"></i>
        </button>

        <button data-consultar_nota_credito data-sale_id="" class="btn btn-outline-success btn-sm" data-toggle="tooltip" data-placement="top" title="Consultar Nota de Crédito">
            <img src="{{ asset('images/sale/nota_credito.png') }}" alt="Generar" style="width: 18px; height: 18px;">
        </button>

        <button data-generar_nota_credito_parcial data-sale_id="" class="btn btn-outline-warning btn-sm" data-toggle="tooltip" data-placement="top" title="Generar Nota de Crédito Parcial">
            <i class="fas fa-file-invoice"></i>
        </button>

        <a href="" target="_blank" data-ver_nc_parcial_pdf class="btn btn-outline-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Ver PDF Nota de Crédito Parcial">
            <i class="fas fa-file-pdf"></i>
        </a>
    </template>

    <template id="template-annulled">
        <a href="" target="_blank" data-print_recibo data-id="" class="btn btn-outline-dark btn-sm" data-toggle="tooltip" data-placement="top" title="Ver comprobante original">
            <i class="fas fa-print"></i>
        </a>

        <button data-ver_detalles data-id="" class="btn btn-outline-secondary btn-sm" data-toggle="tooltip" data-placement="top" title="Ver Detalles">
            <i class="fas fa-list-ol"></i>
        </button>

        <a href="" target="_blank" data-print_anulacion_pdf class="btn btn-outline-danger btn-sm" data-toggle="tooltip" data-placement="top" title="Ver PDF Anulación">
            <i class="fas fa-file-pdf"></i>
        </a>

        <a href="" target="_blank" data-print_anulacion_xml class="btn btn-outline-info btn-sm" data-toggle="tooltip" data-placement="top" title="Ver XML Anulación">
            <i class="fas fa-file-code"></i>
        </a>

        <a href="" target="_blank" data-print_anulacion_cdr class="btn btn-outline-success btn-sm" data-toggle="tooltip" data-placement="top" title="Ver CDR Anulación">
            <i class="fas fa-file-archive"></i>
        </a>
    </template>

    <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderDetailsModalLabel">Detalles del Pedido</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <!-- Aquí se cargarán los detalles dinámicamente -->
                    <div id="order-details-content"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="mapModal" tabindex="-1" aria-labelledby="mapModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mapModalLabel">Ruta Generada</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div id="mapContainer" style="width: 100%; height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalFacturador" tabindex="-1" role="dialog" aria-labelledby="modalFacturadorLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="modalFacturadorLabel">Datos del comprobante</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Aquí va el cuerpo dinámico -->
                    <form id="formFacturador">
                        <input type="hidden" name="order_id" id="order_id">

                        <div class="form-group">
                            <label>Tipo de comprobante:</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="invoice_type" id="radio_boleta" value="boleta">
                                <label class="form-check-label" for="radio_boleta">Boleta</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="invoice_type" id="radio_factura" value="factura">
                                <label class="form-check-label" for="radio_factura">Factura</label>
                            </div>
                        </div>

                        <!-- Datos boleta -->
                        <div id="datos_boleta" class="d-none">
                            <div class="form-group">
                                <label for="dni">Nombre <span style="color:red;">*</span></label>
                                <input type="text" name="name" id="name" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="dni">DNI <span style="color:red;">*</span></label>
                                <input type="text" name="dni" id="dni" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="email_invoice_boleta">Email (Opcional)</label>
                                <input type="text" name="email_invoice_boleta" id="email_invoice_boleta" class="form-control">
                            </div>
                        </div>

                        <!-- Datos factura -->
                        <div id="datos_factura" class="d-none">
                            <div class="form-group">
                                <label for="ruc">RUC <span style="color:red;">*</span></label>
                                <input type="text" name="ruc" id="ruc" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="razon_social">Razón Social <span style="color:red;">*</span></label>
                                <input type="text" name="razon_social" id="razon_social" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="direccion_fiscal">Dirección Fiscal <span style="color:red;">*</span></label>
                                <input type="text" name="direccion_fiscal" id="direccion_fiscal" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="email_invoice_factura">Email (Opcional)</label>
                                <input type="text" name="email_invoice_factura" id="email_invoice_factura" class="form-control">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button id="btnGuardarDatos" type="button" class="btn btn-secondary">Guardar datos</button>
                    <button id="btnGenerarComprobante" type="button" class="btn btn-warning">Generar comprobante</button>

                </div>
                <div id="downloadSection" class="mt-3 text-center d-none mb-5">
                    <a id="btnDescargarPDF" href="#" target="_blank" class="btn btn-success">
                        Descargar PDF del Comprobante
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalPagosParciales" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">

                <div class="modal-header bg-info">
                    <h5 class="modal-title">Pagos Parciales</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">

                    <input type="hidden" id="pp_sale_id">

                    <!-- 1. Progreso -->
                    <div class="mb-3">
                        <label><b>Avance de pago</b></label>

                        <div class="pp-progress-container">
                            <div class="pp-progress-wrap">
                                <div id="pp_progress_bar" class="pp-progress-fill"></div>
                                <div id="pp_progress_over" class="pp-progress-over"></div>
                                <div class="pp-progress-mark-100"></div>
                                <div id="pp_progress_text" class="pp-progress-text">0%</div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Totales -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Total venta</label>
                            <input type="text" id="pp_total_venta" class="form-control form-control-sm" readonly>
                        </div>
                        <div class="col-md-6">
                            <label>Total abonado</label>
                            <input type="text" id="pp_total_abonado" class="form-control form-control-sm" readonly>
                        </div>
                    </div>

                    <hr>

                    <!-- 3. Formulario nuevo pago -->
                    <h6><b>Datos del pago nuevo</b></h6>

                    <div class="row">
                        <div class="col-md-3">
                            <label>Fecha <span class="right badge badge-danger">(*)</span></label>
                            <input type="date" id="pp_fecha_pago" class="form-control form-control-sm">
                        </div>

                        <div class="col-md-3">
                            <label>Monto <span class="right badge badge-danger">(*)</span></label>
                            <input type="number" step="0.01" min="0" id="pp_monto" class="form-control form-control-sm">
                        </div>

                        <div class="col-md-3">
                            <label>Caja <span class="right badge badge-danger">(*)</span></label>
                            <select id="pp_cash_box_id" class="form-control form-control-sm select2" style="width:100%;">
                                <option value=""></option>
                                @foreach($cashBoxes as $b)
                                    <option value="{{ $b['id'] }}"
                                            data-type="{{ $b['type'] }}"
                                            data-uses_subtypes="{{ $b['uses_subtypes'] ? 1 : 0 }}">
                                        {{ $b['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3" id="pp_cash_box_subtype_wrap" style="display:none;">
                            <label>Canal / Subtipo <span class="right badge badge-danger">(*)</span></label>
                            <select id="pp_cash_box_subtype_id" class="form-control form-control-sm select2" style="width:100%;">
                                <option value=""></option>
                                @foreach($subtypes as $st)
                                    <option value="{{ $st['id'] }}"
                                            data-is_deferred="{{ $st['is_deferred'] ?? 0 }}">
                                        {{ $st['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="text-right mt-3">
                        <button type="button" id="btnGuardarPagoParcial" class="btn btn-success btn-sm">
                            <i class="fas fa-plus"></i> Agregar pago
                        </button>
                    </div>

                    <hr>

                    <!-- 4. Listado pagos -->
                    <h6><b>Pagos realizados</b></h6>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Monto</th>
                                <th>Caja</th>
                                <th width="50">Acción</th>
                            </tr>
                            </thead>
                            <tbody id="pp_body_pagos">
                            </tbody>
                        </table>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                        Cerrar
                    </button>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="modalGenerarComprobante" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">

                <div class="modal-header bg-warning">
                    <h5 class="modal-title">Generar comprobante</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">

                    <input type="hidden" id="gc_sale_id">
                    <input type="hidden" id="gc_free_sale" value="0">

                    <ul class="nav nav-tabs" id="gc_tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="gc_boleta_tab" data-toggle="tab" href="#gc_boleta" role="tab">
                                Boleta
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="gc_factura_tab" data-toggle="tab" href="#gc_factura" role="tab">
                                Factura
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content pt-3">

                        <div class="tab-pane fade show active" id="gc_boleta" role="tabpanel">
                            <div class="form-group">
                                <label>DNI <span class="text-danger">*</span></label>
                                {{--<input type="text" maxlength="8" id="gc_dni" class="form-control form-control-sm">--}}
                                <div class="input-group input-group-sm">
                                    <input
                                            type="text"
                                            maxlength="8"
                                            id="gc_dni"
                                            class="form-control"
                                            inputmode="numeric"
                                            autocomplete="off"
                                    >

                                    <div class="input-group-append">
                                        <button
                                                type="button"
                                                id="btnBuscarGcDni"
                                                class="btn btn-outline-primary"
                                                title="Buscar DNI"
                                        >
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Nombre <span class="text-danger">*</span></label>
                                <input type="text" id="gc_name" class="form-control form-control-sm" readonly>
                            </div>

                            <div class="form-group">
                                <label>Email (Opcional)</label>
                                <input type="text" id="gc_email_boleta" class="form-control form-control-sm">
                            </div>
                        </div>

                        <div class="tab-pane fade" id="gc_factura" role="tabpanel">
                            <div class="form-group">
                                <label>RUC <span class="text-danger">*</span></label>
                                {{--<input type="text" maxlength="11" id="gc_ruc" class="form-control form-control-sm">--}}
                                <div class="input-group input-group-sm">
                                    <input
                                            type="text"
                                            maxlength="11"
                                            id="gc_ruc"
                                            class="form-control"
                                            inputmode="numeric"
                                            autocomplete="off"
                                    >

                                    <div class="input-group-append">
                                        <button
                                                type="button"
                                                id="btnBuscarGcRuc"
                                                class="btn btn-outline-primary"
                                                title="Buscar RUC"
                                        >
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Razón Social <span class="text-danger">*</span></label>
                                <input type="text" id="gc_razon_social" class="form-control form-control-sm" readonly>
                            </div>

                            <div class="form-group">
                                <label>Dirección Fiscal <span class="text-danger">*</span></label>
                                <input type="text" id="gc_direccion_fiscal" class="form-control form-control-sm" readonly>
                            </div>

                            <div class="form-group">
                                <label>Email (Opcional)</label>
                                <input type="text" id="gc_email_factura" class="form-control form-control-sm">
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" id="btnConfirmarGenerarComprobante" class="btn btn-warning btn-sm">
                        Generar comprobante
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                        Cerrar
                    </button>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="modalNotaCreditoParcial" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Nota de Crédito Parcial</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="nc_partial_sale_id">

                    <div class="mb-2">
                        <strong>Venta:</strong> <span id="nc_partial_sale_code"></span><br>
                        <strong>Cliente:</strong> <span id="nc_partial_customer"></span><br>
                        <strong>Comprobante:</strong> <span id="nc_partial_document"></span>
                    </div>

                    <table class="table table-bordered table-sm">
                        <thead>
                        <tr>
                            <th>Producto / Servicio</th>
                            <th>Vendido</th>
                            <th>Ya Devuelto</th>
                            <th>Disponible</th>
                            <th>Devolver</th>
                            <th>Total NC</th>
                        </tr>
                        </thead>
                        <tbody id="body-nc-partial-items"></tbody>
                    </table>

                    <div class="text-right">
                        <strong>Total Nota de Crédito: S/ <span id="nc_partial_total">0.00</span></strong>
                    </div>
                </div>

                <div class="modal-footer">
                    <button id="btn-submit-nc-partial" class="btn btn-warning">
                        Generar Nota de Crédito Parcial
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Cerrar
                    </button>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="modalNcPartialItems" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content modal-nc-items-content">

                <div class="modal-header">
                    <h5 class="modal-title">
                        Seleccionar productos a devolver
                    </h5>

                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body modal-nc-items-body">

                    <div class="alert alert-info">
                        Seleccione exactamente los productos que serán devueltos.
                    </div>

                    <div class="form-group mb-3">
                        <label for="nc_partial_item_search">
                            Buscar el código del item
                        </label>

                        <div class="input-group">
                            <input type="text"
                                   id="nc_partial_item_search"
                                   class="form-control"
                                   autocomplete="off"
                                   placeholder="Pistolee o escriba el código y presione Enter">

                            <div class="input-group-append">
                                <button type="button"
                                        id="btn-search-nc-partial-item"
                                        class="btn btn-outline-primary">
                                    <i class="fas fa-search"></i>
                                    Buscar
                                </button>
                            </div>
                        </div>

                        <small class="form-text text-muted">
                            Al encontrar el código, el item será seleccionado automáticamente.
                        </small>
                    </div>

                    <div id="nc-partial-item-search-message"></div>

                    <div class="nc-partial-items-scroll">
                        <div id="body-nc-partial-item-selection"></div>
                    </div>

                </div>

                <div class="modal-footer modal-nc-items-footer">
                    <button type="button"
                            id="btn-confirm-nc-partial-items"
                            class="btn btn-warning">
                        Confirmar selección
                    </button>

                    <button type="button"
                            class="btn btn-secondary"
                            data-dismiss="modal">
                        Volver
                    </button>
                </div>

            </div>
        </div>
    </div>

    <div id="globalProcessLoader" style="display:none;">
        <div class="global-loader-backdrop"></div>

        <div class="global-loader-box">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="sr-only">Cargando...</span>
            </div>

            <div id="globalProcessLoaderMessage" class="global-loader-message">
                Procesando...
            </div>

            <div class="global-loader-submessage">
                Por favor, no cierre esta ventana ni presione otros botones.
            </div>
        </div>
    </div>
@endsection

@section('plugins')
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>

    <script src="{{ asset('admin/plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-switch/js/bootstrap-switch.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-datepicker/locales/bootstrap-datepicker.es.min.js') }}"></script>
@endsection

@section('scripts')
    <script>
        $(function () {
            //Initialize Select2 Elements
            $('#year').select2({
                placeholder: "Seleccione año",
                allowClear: true
            });

            $('#filter_customer_id').select2({
                placeholder: "Seleccione cliente",
                allowClear: true
            });

            $('#filter_payment_status').select2({
                placeholder: "Seleccione cumplimiento",
                allowClear: true
            });

            $('#filter_dispatch_status').select2({
                placeholder: "Seleccione despacho",
                allowClear: true
            });

            $('#filter_cash_box_id').select2({
                placeholder: "Seleccione caja",
                allowClear: true
            });

            $('#filter_cash_box_subtype_id').select2({
                placeholder: "Seleccione canal",
                allowClear: true
            });

            $('#filter_invoice_status').select2({
                placeholder: "Seleccione comprobante",
                allowClear: true
            });

            $('#filter_sale_status').select2({
                placeholder: "Seleccione estado",
                allowClear: true
            });

        })
    </script>
    <script src="{{ asset('js/puntoVenta/list.js') }}?v={{ time() }}"></script>

@endsection