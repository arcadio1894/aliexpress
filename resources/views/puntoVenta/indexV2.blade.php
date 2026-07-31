@extends('layouts.appAdmin2')

@section('openPuntoVenta')
    menu-open
@endsection

@section('activePuntoVenta')
    active
@endsection

@section('activeCreatePuntoVentaV2')
    active
@endsection

@section('title')
    Punto de Venta
@endsection

@section('styles-plugins')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/typehead/typeahead.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
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
        .section-products {
            padding: 80px 0 54px;
        }

        .product-grid{
            background-color: #fff;
            font-family: 'Work Sans', sans-serif;
            text-align: center;
            transition: all 0.3s ease 0s;
        }
        .product-grid:hover{
            box-shadow:  0 0 20px -10px rgba(237,29,36,0.3);
        }
        .product-grid .product-image{
            overflow: hidden;
            position: relative;
            transition: all 0.3s ease 0s;
        }
        .product-grid:hover .product-image{ border-radius: 0 0 30px 30px; }
        .product-grid .product-image a.image{ display: block; }
        .product-grid .product-image img{
            width: 100%;
            height: auto;
        }
        .product-image .pic-1{
            backface-visibility: hidden;
            transition: all 0.5s ease 0s;
        }
        .product-grid:hover .product-image .pic-1{ opacity: 0; }
        .product-image .pic-2{
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            opacity: 0;
            position: absolute;
            top: 0;
            left: 0;
            transition: all 0.5s ease 0s;
        }
        .product-grid:hover .product-image .pic-2{ opacity: 1; }
        .product-grid .product-links{
            padding: 0;
            margin: 0;
            list-style: none;
            opacity: 0;
            position: absolute;
            bottom: 0;
            right: 10px;
            transition: all 0.3s ease 0s;
        }
        .product-grid:hover .product-links{ opacity: 1; }
        .product-grid .product-links li{
            margin: 0 0 10px 0;
            transform: rotate(360deg) scale(0);
            transition: all 0.3s ease 0s;
        }
        .product-grid:hover .product-links li{ transform: rotate(0) scale(1); }
        .product-grid:hover .product-links li:nth-child(3){ transition-delay: 0.1s; }
        .product-grid:hover .product-links li:nth-child(2){ transition-delay: 0.2s; }
        .product-grid:hover .product-links li:nth-child(1){ transition-delay: 0.3s; }
        .product-grid .product-links li a{
            color: #666;
            background-color: #fff;
            font-size: 18px;
            line-height: 42px;
            width: 40px;
            height: 40px;
            border-radius: 50px;
            display: block;
            transition: all 0.3s ease 0s;
        }
        .product-grid .product-links li a:hover{
            color: #fff;
            background-color: #ed1d24;
        }
        .product-grid .product-content{
            text-align: left;
            padding: 15px 10px;
        }
        .product-grid .rating{
            padding: 0;
            margin: 0 0 7px;
            list-style: none;
        }
        .product-grid .rating li{
            color: #f7bc3d;
            font-size: 13px;
        }
        .product-grid .rating li.far{ color: #777; }
        .product-grid .title{
            font-size: 16px;
            font-weight: 600;
            text-transform: capitalize;
            margin: 0 0 6px;
        }
        .product-grid .title a{
            color: #555;
            transition: all 0.3s ease 0s;
        }
        .product-grid .title a:hover{ color: #ed1d24; }
        .product-grid .price{
            color: #ed1d24;
            font-size: 18px;
            font-weight: 700;
        }
        @media screen and (max-width:990px){
            .product-grid{ margin: 0 0 30px; }
        }

        @media (min-width: 1025px) {
            .h-custom {
                height: 100vh !important;
            }
        }

        .number-input input[type="number"] {
            -webkit-appearance: textfield;
            -moz-appearance: textfield;
            appearance: textfield;
        }

        .number-input input[type=number]::-webkit-inner-spin-button,
        .number-input input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
        }

        .number-input button {
            -webkit-appearance: none;
            background-color: transparent;
            border: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            margin: 0;
            position: relative;
        }

        .number-input button:before,
        .number-input button:after {
            display: inline-block;
            position: absolute;
            content: '';
            height: 2px;
            transform: translate(-50%, -50%);
        }

        .number-input button.plus:after {
            transform: translate(-50%, -50%) rotate(90deg);
        }

        .number-input input[type=number] {
            text-align: center;
        }

        .number-input.number-input {
            border: 1px solid #ced4da;
            width: 10rem;
            border-radius: .25rem;
        }

        .number-input.number-input button {
            width: 2.6rem;
            height: .7rem;
        }

        .number-input.number-input button.minus {
            padding-left: 10px;
        }

        .number-input.number-input button:before,
        .number-input.number-input button:after {
            width: .7rem;
            background-color: #495057;
        }

        .number-input.number-input input[type=number] {
            max-width: 4rem;
            padding: .5rem;
            border: 1px solid #ced4da;
            border-width: 0 1px;
            font-size: 1rem;
            height: 2rem;
            color: #495057;
        }

        @media not all and (min-resolution:.001dpcm) {
            @supports (-webkit-appearance: none) and (stroke-color:transparent) {

                .number-input.def-number-input.safari_only button:before,
                .number-input.def-number-input.safari_only button:after {
                    margin-top: -.3rem;
                }
            }
        }

        .shopping-cart .def-number-input.number-input {
            border: none;
        }

        .shopping-cart .def-number-input.number-input input[type=number] {
            max-width: 8rem;
            border: none;
        }

        .shopping-cart .def-number-input.number-input input[type=number].black-text,
        .shopping-cart .def-number-input.number-input input.btn.btn-link[type=number],
        .shopping-cart .def-number-input.number-input input.md-toast-close-button[type=number]:hover,
        .shopping-cart .def-number-input.number-input input.md-toast-close-button[type=number]:focus {
            color: #212529 !important;
        }

        .shopping-cart .def-number-input.number-input button {
            width: 1rem;
        }

        .shopping-cart .def-number-input.number-input button:before,
        .shopping-cart .def-number-input.number-input button:after {
            width: .5rem;
        }

        .shopping-cart .def-number-input.number-input button.minus:before,
        .shopping-cart .def-number-input.number-input button.minus:after {
            background-color: #9e9e9e;
        }

        .shopping-cart .def-number-input.number-input button.plus:before,
        .shopping-cart .def-number-input.number-input button.plus:after {
            background-color: #4285f4;
        }

        .large-input {
            width: 100px; /* Ajusta el valor según sea necesario */
            text-align: center; /* Centra el texto en el input */
        }

        .details {
            border: 1.5px solid grey;
            color: #212121;
            width: 100%;
            height: auto;
            box-shadow: 0px 0px 10px #212121;
        }

        .cart {
            background-color: #212121;
            color: white;
            margin-top: 10px;
            font-size: 12px;
            font-weight: 900;
            width: 100%;
            height: 39px;
            padding-top: 9px;
            box-shadow: 0px 5px 10px  #212121;
        }

        .card2 {
            width: fit-content;
        }

        .card-body2 {
            width: fit-content;
        }

        .btn2 {
            border-radius: 0;
        }

        .img-thumbnail2 {
            border: none;
        }

        .card2 {
            box-shadow: 0 20px 40px rgba(0, 0, 0, .2);
            border-radius: 5px;
            padding-bottom: 10px;
        }

        .product-card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
        }

        .product-image {
            max-height: 150px; /* Ajusta según sea necesario */
            object-fit: contain;
            margin-bottom: 15px;
        }

        .cvp {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .product-title {
            height: 80px; /* Altura fija para el título */
            overflow: hidden; /* Ocultar el texto que sobrepasa el límite */
            text-overflow: ellipsis; /* Añadir puntos suspensivos si el texto es demasiado largo */
            display: -webkit-box;
            -webkit-line-clamp: 2; /* Mostrar un máximo de 2 líneas */
            -webkit-box-orient: vertical;
        }

        .product-price {
            margin-bottom: 15px;
        }

        .btn.cart:hover {
            color: #007bff; /* Cambia este valor al color que prefieras */
        }

        .presentation-selected-wrapper {
            display: flex;
            align-items: center;
            gap: 4px;
            width: 100%;
            min-width: 220px;
        }

        .custom-tooltip-container {
            position: relative;
            flex: 1;
            min-width: 0;
        }

        .input-presentations-selected {
            width: 100%;
            min-width: 0;
            cursor: default;
        }

        .btn-remove-presentations {
            flex: 0 0 auto;
        }

        /* Tooltip hacia abajo */
        .custom-tooltip-box {
            position: absolute;
            bottom: calc(100% + 10px);
            top: auto;
            left: 50%;
            transform: translateX(-50%) translateY(6px);

            min-width: 230px;
            max-width: 340px;

            background: #2563eb;
            color: #fff;
            padding: 8px 12px;
            border-radius: 10px;

            font-size: 12px;
            line-height: 1.5;
            white-space: normal;

            box-shadow: 0 8px 25px rgba(0,0,0,.18);

            opacity: 0;
            visibility: hidden;
            transition: all .18s ease;

            z-index: 999999;
            pointer-events: none;
            text-align: left;
        }

        /* Flechita arriba del tooltip */
        .custom-tooltip-box::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);

            border-width: 7px;
            border-style: solid;
            border-color: #2563eb transparent transparent transparent;
        }

        .custom-tooltip-container:hover .custom-tooltip-box {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(0);
        }

        /*#modalStockItemsVenta .modal-body,
        #modalStockItemsVenta .table-responsive,
        #modalStockItemsVenta table,
        #modalStockItemsVenta td {
            overflow: visible !important;
        }*/

        #modalStockItemsVenta .modal-body {
            overflow: hidden;
        }

        #modalStockItemsVenta .table-responsive {
            max-height: calc(100vh - 220px);
            overflow-y: auto !important;
            overflow-x: auto;
        }
    </style>
@endsection

@section('page-header')
    <h1 class="page-title">Punto de Venta</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Crear nueva venta</h5>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('puntoVenta.list') }}"><i class="fa fa-archive"></i> Punto de venta</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Nueva venta</li>
    </ol>
@endsection

@section('content')
    <form id="formCreate" class="form-horizontal" data-url="{{ route('puntoVenta.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-md-4">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">Productos Seleccionados</h3>

                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fas fa-minus"></i></button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="shopping-cart">
                            <div class="row">
                                <div class="col-lg-12 px-1 py-1">
                                    <div id="body-cart">

                                    </div>

                                    <hr class="mb-4" style="height: 2px; background-color: #1266f1; opacity: 1;">

                                    <div class="d-flex justify-content-between px-x">
                                        <p class="fw-bold">OP. EXONERADA:</p>
                                        <p class="fw-bold" id="op_exonerada">0.00</p>
                                    </div>
                                    <div class="d-flex justify-content-between px-x">
                                        <p class="fw-bold">OP. INAFECTA</p>
                                        <p class="fw-bold" id="op_inafecta">0.00</p>
                                    </div>
                                    <div class="d-flex justify-content-between px-x">
                                        <p class="fw-bold">OP. GRAVADA:</p>
                                        <p class="fw-bold" id="op_gravada">0.00</p>
                                    </div>
                                    <div class="d-flex justify-content-between px-x">
                                        <p class="fw-bold">I.G.V.:</p>
                                        <p class="fw-bold" id="total_igv">0.00</p>
                                    </div>
                                    <div class="d-flex justify-content-between px-x">
                                        <p class="fw-bold">TOTAL DESCUENTOS:</p>
                                        <p class="fw-bold" id="total_descuentos">0.00</p>
                                    </div>
                                    <div class="d-flex justify-content-between p-2 mb-2 bg-primary">
                                        <h5 class="fw-bold mb-0">IMPORTE TOTAL:</h5>
                                        <h5 class="fw-bold mb-0" id="total_importe">0.00</h5>
                                    </div>

                                </div>
                                <div class="col-md-12 px-1 py-1">

                                    <input type="hidden"
                                           name="negocio_acepta_pagos_parciales"
                                           id="negocio_acepta_pagos_parciales"
                                           value="{{ $pagos_parciales }}">

                                    <div data-mdb-input-init class="form-outline mb-1">
                                        {{--@foreach( $tipoPagos as $tipoPago )
                                            <div class="form-group clearfix">
                                                <div class="icheck-primary d-inline">
                                                    <input type="radio" data-vuelto="{{$tipoPago->vuelto}}" name="tipo_pago" value="{{$tipoPago->id}}" id="checkboxPrimary{{$tipoPago->id}}">
                                                    <label for="checkboxPrimary{{$tipoPago->id}}">
                                                        {{$tipoPago->description}}
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach--}}

                                        @if ($pagos_parciales == 's')
                                            <div class="form-group">
                                                <label>Pagos parciales</label>

                                                <input type="checkbox"
                                                       name="pagos_parciales_venta"
                                                       id="pagos_parciales_venta"
                                                       value="s"
                                                       data-bootstrap-switch
                                                       data-on-text="SI"
                                                       data-off-text="NO"
                                                       data-on-color="success"
                                                       data-off-color="danger">
                                            </div>
                                        @endif
                                        <div id="wrap_pago_normal">
                                            <div class="form-group">
                                                <label><b>Caja (CashBox)</b></label>
                                                <select id="pv_cash_box_id" class="form-control select2" style="width:100%;">
                                                    <option value="">Seleccione caja...</option>
                                                    @foreach($cashBoxes as $b)
                                                        <option value="{{ $b->id }}"
                                                                data-type="{{ $b->type }}"
                                                                data-uses_subtypes="{{ $b->uses_subtypes ? 1 : 0 }}">
                                                            {{ $b->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="form-group" id="wrap_pv_subtype" style="display:none;">
                                                <label><b>Subtipo bancario</b></label>
                                                <select id="pv_cash_box_subtype_id" class="form-control select2" style="width:100%;">
                                                    <option value="">Seleccione subtipo...</option>
                                                    @foreach($subtypes as $st)
                                                        <option value="{{ $st->id }}"
                                                                data-is_deferred="{{ $st->is_deferred ? 1 : 0 }}">
                                                            {{ $st->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <small class="text-muted" id="pv_subtype_hint" style="display:none;">
                                                    Este canal es diferido: quedará pendiente hasta regularización.
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-12 px-1 py-1" id="wrap_comprobante">
                                    <div class="accordion" id="accordionInvoice">
                                        <!-- Sección del acordeón -->
                                        <div class="card">
                                            <div class="card-header p-2" id="headingOneInvoice">
                                                <p class="mb-0 d-flex justify-content-between">
                                                    <!-- Título del acordeón -->
                                                    <span class="d-flex align-items-center">
                                                        <img src="{{ asset('/images/sale/invoice.png') }}" alt="Cupon" style="width: 30px; height: 30px; margin-right: 10px;">
                                                        ¿Necesitas Boleta o Factura?
                                                    </span>
                                                    <!-- Link de agregar -->
                                                    <a href="#" class="btn btn-link p-0" data-toggle="collapse" data-target="#collapseOneInvoice" aria-expanded="false" aria-controls="collapseOneInvoice">
                                                        Solicitar
                                                    </a>
                                                </p>
                                            </div>

                                            <!-- Contenido del acordeón -->
                                            <div id="collapseOneInvoice" class="collapse" aria-labelledby="headingOne" data-parent="#accordionInvoice">
                                                <div class="card-body">
                                                    <!-- Botones radio -->
                                                    <div class="form-group">
                                                        <label>Tipo de comprobante:</label><br>

                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="invoice_type" id="radio_none" value="ninguno" checked>
                                                            <label class="form-check-label" for="radio_none">Ticket</label>
                                                        </div>
                                                        @can('generarBoleta_puntoVenta')
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="invoice_type" id="radio_boleta" value="boleta">
                                                            <label class="form-check-label" for="radio_boleta">Boleta</label>
                                                        </div>
                                                        @endcan
                                                        @can('generarFactura_puntoVenta')
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="invoice_type" id="radio_factura" value="factura">
                                                            <label class="form-check-label" for="radio_factura">Factura</label>
                                                        </div>
                                                        @endcan
                                                    </div>

                                                    <!-- Campos para Boleta -->
                                                    <div id="datos_boleta" class="d-none">
                                                        <div class="form-group">
                                                            <label for="dni">DNI <span style="color:red;">*</span></label>
                                                            <div class="input-group input-group-sm">
                                                                <input
                                                                        type="text"
                                                                        maxlength="8"
                                                                        id="dni"
                                                                        name="dni"
                                                                        class="form-control"
                                                                        inputmode="numeric"
                                                                        enterkeyhint="search"
                                                                >
                                                                <div class="input-group-append">
                                                                    <button
                                                                            type="button"
                                                                            class="btn btn-primary btnConsultarCliente"
                                                                            data-input="#dni"
                                                                            title="Consultar DNI"
                                                                    >
                                                                        <i class="fas fa-search"></i>
                                                                    </button>
                                                                </div>
                                                                {{--<input type="text" name="dni" id="dni" class="form-control" >--}}
                                                            </div>
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="dni">Nombre <span style="color:red;">*</span></label>
                                                            <input type="text" name="name" id="name" class="form-control">
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="email_invoice_boleta">Email (Opcional)</label>
                                                            <input type="text" name="email_invoice_boleta" class="form-control">
                                                        </div>
                                                    </div>

                                                    <!-- Campos para Factura -->
                                                    <div id="datos_factura" class="d-none">
                                                        <div class="form-group">
                                                            <label for="ruc">RUC <span style="color:red;">*</span></label>

                                                            <div class="input-group input-group-sm">
                                                                <input
                                                                        type="text"
                                                                        maxlength="11"
                                                                        id="ruc"
                                                                        name="ruc"
                                                                        value=""
                                                                        class="form-control"
                                                                        inputmode="numeric"
                                                                        enterkeyhint="search"
                                                                >
                                                                {{--<input type="text" name="ruc" id="ruc" class="form-control" >--}}
                                                                <div class="input-group-append">
                                                                    <button
                                                                            type="button"
                                                                            class="btn btn-primary btnConsultarCliente"
                                                                            data-input="#ruc"
                                                                            title="Consultar RUC"
                                                                    >
                                                                        <i class="fas fa-search"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="razon_social">Razón Social <span style="color:red;">*</span></label>
                                                            <input type="text" name="razon_social" id="razon_social" class="form-control" >
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="direccion_fiscal">Dirección Fiscal <span style="color:red;">*</span></label>
                                                            <input type="text" name="direccion_fiscal" id="direccion_fiscal" class="form-control" >
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="email_invoice_factura">Email (Opcional)</label>
                                                            <input type="text" name="email_invoice_factura" class="form-control">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-12 px-1 py-1">
                                    <button  type="button" id="btn-pay" class="btn btn-success btn-block btn-lg">PAGAR</button>

                                    <a href="#" target="_blank" id="btn-printDocument" class="btn btn-primary btn-block btn-lg">IMPRIMIR COMPROBANTE</a>

                                    <button  type="button" id="btn-newSale" class="btn btn-warning btn-block btn-lg">NUEVA VENTA</button>

                                </div>
                            </div>
                        </div>

                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            <div class="col-md-8">
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">Listado de Productos</h3>

                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fas fa-minus"></i></button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <select id="category_id" class="form-control select2" name="category_id" data-states style="width: 100%;">
                                    <option></option>
                                    @foreach( $categories as $category )
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 offset-sm-2">
                                <select id="type_id" class="form-control select2" name="type_id" data-states style="width: 100%;">
                                    <option></option>
                                    <option value="c" selected>Celdas</option>
                                    <option value="f">Filas</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="product_search" placeholder="Buscar productos">
                                    <div class="input-group-append">
                                        <button type="button" id="btn_search" class="btn btn-primary">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="d-flex flex-wrap align-items-center mb-4">
                                <h5 class="fw-bold me-5 my-1"><span id="numberItems"></span> Productos encontrados</h5>
                            </div>
                        </div>

                        <div class="row" id="body-card">
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <div class="fs-6 fw-bold text-gray-700" id="textPagination"></div>
                            <ul class="pagination" id="pagination"></ul>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </form>

    <div id="modalVuelto" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Ingrese el monto</h4>
                </div>

                <div class="modal-body">
                    <div class="form-group row">
                        <div class="col-md-6">
                            <label for="monto_total">Monto Total</label>
                            <input type="number" min="0" class="form-control" id="monto_total" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="importe_total">Importe</label>
                            <input type="number" min="0" class="form-control" id="importe_total">
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-6 ">
                            <label for="vuelto">Vuelto</label>
                            <input type="number" min="0" class="form-control" id="vuelto" readonly>
                        </div>
                        <div class="col-md-6 ">
                            {{--<label for="vuelto">Caja</label>
                            <select id="type_caja" class="form-control select2" name="type_caja" data-states style="width: 100%;">
                                <option></option>
                                <option value="efectivo" selected>Efectivo</option>
                                <option value="yape">Yape</option>
                                <option value="plin">Plin</option>
                                <option value="bancario">Bancario</option>
                            </select>--}}
                            <label for="pv_vuelto_cash_box_id">Caja para el vuelto</label>
                            <select id="pv_vuelto_cash_box_id" class="form-control select2" style="width: 100%;">
                                <option value="">Seleccione caja...</option>
                                @foreach($cashBoxes as $b)
                                    <option value="{{ $b->id }}"
                                            data-type="{{ $b->type }}"
                                            data-uses_subtypes="{{ $b->uses_subtypes ? 1 : 0 }}"
                                            {{ $b->type === 'cash' ? 'selected' : '' }}>
                                        {{ $b->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Por defecto el vuelto sale de efectivo, pero puedes cambiarlo.</small>

                            <div id="wrap_vuelto_subtype" style="display:none; margin-top:10px;">
                                <label for="pv_vuelto_subtype_id">Subtipo bancario (para vuelto)</label>
                                <select id="pv_vuelto_subtype_id" class="form-control select2" style="width: 100%;">
                                    <option value="">Seleccione subtipo...</option>
                                    @foreach($subtypes as $st)
                                        <option value="{{ $st->id }}">{{ $st->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Si el vuelto sale de bancario, seleccione el canal (Yape/Plin/Transfer/POS).</small>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="btn-notSave">Cancelar</button>
                    <button type="button" id="btn-save" class="btn btn-success">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    {{--<div id="modalQuantity" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Ingrese cantidad</h4>
                </div>

                <input type="hidden" id="quantity_productId">
                <input type="hidden" id="quantity_productPrice">
                <input type="hidden" id="quantity_productStock">
                <input type="hidden" id="quantity_productName">
                <input type="hidden" id="quantity_productUnit">
                <input type="hidden" id="quantity_productTax">
                <input type="hidden" id="quantity_productType">

                <div class="modal-body">
                    <div class="form-group row">
                        <div class="col-md-6">
                            <label for="quantity_total">Cantidad</label>
                            <input type="number" min="0" class="form-control" id="quantity_total">
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="btn-notAddProduct">Cancelar</button>
                    <button type="button" id="btn-add_product" class="btn btn-success">Agregar</button>
                </div>
            </div>
        </div>
    </div>--}}
    {{--<div id="modalQuantity" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h4 class="modal-title">Ingrese cantidad / presentaciones</h4>
                </div>

                <input type="hidden" id="quantity_productId">
                <input type="hidden" id="quantity_materialId">
                <input type="hidden" id="quantity_productPrice">
                <input type="hidden" id="quantity_productStock">
                <input type="hidden" id="quantity_productName">
                <input type="hidden" id="quantity_productUnit">
                <input type="hidden" id="quantity_productTax">
                <input type="hidden" id="quantity_productType">

                <div class="modal-body">

                    <!-- Cantidad unitaria (igual que antes) -->
                    <div class="form-group row">
                        <div class="col-md-6">
                            <label for="quantity_total">Cantidad (Unidad)</label>
                            <input type="number" min="0" step="1" class="form-control" id="quantity_total" value="0">
                            <small class="text-muted">Esto vende en unidad usando el precio base del producto.</small>
                        </div>
                        <div class="col-md-6">
                            <label>Stock disponible (unidades)</label>
                            <input type="text" class="form-control" id="quantity_stock_show" readonly>
                        </div>
                    </div>

                    <hr>

                    <!-- Presentaciones -->
                    <div class="mb-2">
                        <strong>Presentaciones</strong>
                        <div class="text-muted" style="font-size: 12px;">
                            Ingresa cuántos “paquetes” (docenas, cuartos, etc.) quieres vender.
                        </div>
                    </div>

                    <div id="presentationsArea">
                        <div class="text-muted">Cargando presentaciones...</div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="btn-notAddProduct">Cancelar</button>
                    <button type="button" id="btn-add_product" class="btn btn-success">Agregar</button>
                </div>

            </div>
        </div>
    </div>--}}

    <div id="modalPresentaciones" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header py-2">
                    <h5 class="modal-title">Presentaciones</h5>

                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">

                    <input type="hidden" id="quantity_materialId">
                    <input type="hidden" id="quantity_stockItemId">

                    <div class="form-group mb-2">
                        <label class="mb-1 font-weight-bold">
                            Unidades disponibles
                        </label>
                        <input
                                type="text"
                                class="form-control form-control-sm"
                                id="quantity_stock_show"
                                readonly
                                style="max-width: 140px;">
                    </div>

                    <div class="mb-2">
                        <strong>Presentaciones</strong>
                        <div class="text-muted" style="font-size: 12px;">
                            Ingresa cuántos “paquetes” quieres vender.
                        </div>
                    </div>

                    <div id="presentationsArea">
                        <div class="text-muted">Cargando presentaciones...</div>
                    </div>

                </div>

                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="button" id="btnAgregarPresentaciones" class="btn btn-success btn-sm">
                        Agregar
                    </button>
                </div>

            </div>
        </div>
    </div>
    {{--<template id="item-cart">
        <div class="d-flex align-items-center mb-3 w-100">
            <div class="flex-grow-1 ms-3">
                <a href="#!" class="float-right" data-delete><i class="fas fa-times"></i></a>
                <h5 class="text-primary" data-name>Samsung Galaxy M11 64GB</h5>
                <h6 style="color: #9e9e9e;" data-price data-stock></h6>
                <div class="d-flex align-items-center justify-content-between w-100">

                    <div class="def-number-input number-input safari_only">
                        <button type="button" data-mdb-button-init onclick="decrementQuantity(this)" data-product_id_minus class="minus"></button>
                        <input class="quantity fw-bold bg-body-tertiary text-body large-input" data-quantity min="0" name="quantity" type="number" step="0.01">
                        <button type="button" data-mdb-button-init onclick="incrementQuantity(this)" data-product_id_plus class="plus"></button>
                    </div>
                    <p class="fw-bold mb-0 me-5 pe-3" data-priceTotal>799$</p>
                </div>
                <div class="align-items-center justify-content-between">
                    <h6 class="text-success" data-discount></h6>
                </div>

                <hr class="mb-4" style="height: 0.8px; background-color: #9e9e9e; opacity: 1;">
            </div>
        </div>
    </template>--}}

    <template id="item-cart">
        <div class="d-flex align-items-center mb-3 w-100" data-cart-row>
            <div class="flex-grow-1 ms-3">

                <a href="#!" class="float-right" data-delete><i class="fas fa-times"></i></a>

                <h5 class="text-primary" data-name>Producto</h5>

                <!-- NUEVO: etiqueta de presentación -->
                <div class="text-muted" style="font-size: 12px;" data-presentation_label></div>

                <small
                        data-selected_items
                        class="text-muted d-block"
                        style="display:none; font-size:11px;">
                </small>

                <h6 style="color: #9e9e9e;" data-price data-stock></h6>

                <div class="d-flex align-items-center justify-content-between w-100">
                    <div class="def-number-input number-input safari_only">
                        <button type="button" onclick="decrementQuantity(this)" data-item_key_minus class="minus"></button>

                        <input class="quantity fw-bold bg-body-tertiary text-body large-input"
                               data-quantity
                               min="0"
                               name="quantity"
                               type="number"
                               step="1">

                        <button type="button" onclick="incrementQuantity(this)" data-item_key_plus class="plus"></button>
                    </div>

                    <p class="fw-bold mb-0 me-5 pe-3" data-priceTotal>0.00</p>
                </div>

                <div class="align-items-center justify-content-between">
                    <h6 class="text-success" data-discount></h6>
                </div>

                <hr class="mb-4" style="height: 0.8px; background-color: #9e9e9e; opacity: 1;">
            </div>
        </div>
    </template>

    <template id="item-card">
        <div class="card mx-auto col-md-3 col-10 mt-1 product-card">
            <img class='mx-auto img-thumbnail product-image'
                 data-image1
                 width="auto" height="auto"/>
            <div class="card-body2 text-center mx-auto">
                <div class='cvp'>
                    <h5 class="card-title font-weight-bold product-title" data-name>Yail wrist watch</h5>
                    <p class="card-text product-price" data-price>$299</p>
                    <a href="#" class="btn details px-auto" data-add_cart_special data-product_id data-product_price data-product_stock data-product_name data-product_unit data-product_tax data-product_type>AGREGAR CARRITO</a>
                </div>
            </div>
        </div>
    </template>

    <template id="item-card-empty">
        <div class="col-md-12 col-sm-12">
            No se ha encontrado ningún producto
        </div>
    </template>

    <template id="previous-page">
        <li class="page-item previous">
            <a href="#" class="page-link" data-item><
                <i class="previous"></i>
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
            <a href="#" class="page-link" data-item>>
                <i class="next"></i>
            </a>
        </li>
    </template>

    <template id="disabled-page">
        <li class="page-item disabled">
            <span class="page-link">...</span>
        </li>
    </template>

    <template id="item-table">
        <table class="table table-striped">
            <thead>
            <tr>
                <th>Imagen</th>
                <th>Nombre</th>
                <th>Precio</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody id="table-body">
            <tr data-row>
                <td>
                    <img class="img-thumbnail product-image" data-image1 width="50" height="50"/>
                </td>
                <td class="product-title" data-name>Yail wrist watch</td>
                <td class="product-price" data-price>$299</td>
                <td>
                    <a href="#" class="btn details btn-sm"
                       data-add_cart_special data-product_id data-product_price
                       data-product_name data-product_unit data-product_tax data-product_type>
                        ADD SPECIAL
                    </a>
                    <a href="#" class="btn cart btn-sm btn-primary"
                       data-add_cart data-product_id data-product_price
                       data-product_name data-product_unit data-product_tax>
                        ADD TO CART
                    </a>
                </td>
            </tr>
            </tbody>
        </table>
    </template>

    <div class="modal fade" id="modalStockItemsVenta" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">

                <div class="modal-header py-2">
                    <h5 class="modal-title">
                        Seleccionar presentación / variante
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body p-2">

                    <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                        <table class="table table-sm table-bordered table-hover mb-0">
                            <thead class="thead-light">
                            <tr>
                                <th style="width: 170px;">Variante</th>
                                <th style="width: 160px;">SKU</th>
                                <th style="width: 130px;">Código barras</th>
                                <th style="width: 100px;">Disponible</th>
                                <th style="width: 120px;">Precio tienda</th>
                                <th style="width: 110px;">Cantidad</th>
                                <th style="width: 130px;">Acción</th>
                            </tr>
                            </thead>
                            <tbody id="tbody-stock-items-venta">
                            <tr>
                                <td colspan="7" class="text-center">
                                    Cargando...
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                </div>

                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                        Cerrar
                    </button>

                    <button type="button" class="btn btn-success btn-sm" id="btn-agregar-todos-stock-items">
                        Agregar al carrito
                    </button>
                </div>

            </div>
        </div>
    </div>

    <div id="modalSelectItemeableItemsV2" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">

                {{--<div class="modal-header py-2">
                    <h5 class="modal-title">
                        Seleccionar ítems físicos
                    </h5>

                    <button type="button" class="close" id="btn-close-itemeable-items-v2">
                        <span>&times;</span>
                    </button>
                </div>--}}

                <div class="modal-body">

                    <div class="mb-2">
                        <strong id="itemeable-product-name-v2"></strong>
                    </div>

                    <div class="alert alert-info py-2 mb-2">
                        Debe seleccionar exactamente
                        <strong id="itemeable-required-count-v2">0</strong>
                        ítem(s).

                        Seleccionados:
                        <strong id="itemeable-selected-count-v2">0</strong>
                        /
                        <strong id="itemeable-selected-required-count-v2">0</strong>
                    </div>

                    <div class="form-group mb-2">
                        <label for="itemeable-item-search">
                            Escanear o ingresar código de ítem
                        </label>
                        <input
                                type="text"
                                id="itemeable-item-search-v2"
                                class="form-control form-control-sm"
                                autocomplete="off"
                                placeholder="Escanee o escriba el código del ítem">
                    </div>

                    <div id="itemeable-items-loading-v2" class="text-center text-muted py-3">
                        Cargando ítems disponibles...
                    </div>

                    <div id="itemeable-items-empty-v2" class="text-center text-muted py-3" style="display:none;">
                        No se encontraron ítems disponibles.
                    </div>

                    <div id="itemeable-items-error-v2" class="alert alert-danger" style="display:none;">
                        Ocurrió un error al consultar los ítems disponibles.
                    </div>

                    <div
                            id="itemeable-items-table-container-v2"
                            class="table-responsive"
                            style="display:none; max-height:330px; overflow-y:auto;">

                        <table class="table table-sm table-bordered table-hover mb-0">
                            <thead class="thead-light">
                            <tr>
                                <th style="width:55px;">Elegir</th>
                                <th>Código</th>
                                <th>Lote</th>
                                <th>Ubicación</th>
                            </tr>
                            </thead>

                            <tbody id="itemeable-items-table-body-v2"></tbody>
                        </table>
                    </div>

                </div>

                <div class="modal-footer py-2">
                    <button
                            type="button"
                            class="btn btn-secondary btn-sm"
                            id="btn-cancel-itemeable-items-v2">
                        Cancelar
                    </button>

                    <button
                            type="button"
                            class="btn btn-success btn-sm"
                            id="btn-confirm-itemeable-items-v2">
                        Confirmar selección
                    </button>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('plugins')
    <!-- Select2 -->
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-switch/js/bootstrap-switch.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap-datepicker/locales/bootstrap-datepicker.es.min.js') }}"></script>

@endsection

@section('scripts')
    <script src="{{asset('admin/plugins/typehead/typeahead.bundle.js')}}"></script>
    <script>
        $("input[data-bootstrap-switch]").each(function(){
            $(this).bootstrapSwitch();
        });
        $('#category_id').select2({
            placeholder: "Categorías",
            allowClear: true
        });
        $('#type_id').select2({
            placeholder: "Seleccione",
            allowClear: true
        });
        $('#type_caja').select2({
            placeholder: "Seleccione",
            allowClear: true
        });


    </script>
    <script>
        // flag: ¿pedir trabajador en el pago?
        window.PV_ASK_WORKER = @json($askWorker);
        window.APP_POS_V2 = window.APP_POS_V2 || {};
        window.APP_POS_V2.URLS = window.APP_POS_V2.URLS || {};

        window.APP_POS_V2.URLS.AVAILABLE_ITEMS =
            "{{ route('quotes.stock-items.available-items', ':stockItemId') }}";
        // lista simple de trabajadores para el popup
        window.PV_WORKERS = @json(
            $workers->map(function($w){
                return [
                    'id'   => $w->id,
                    'name' => $w->first_name.' '.$w->last_name,
                ];
            })
        );

        // aquí guardaremos el id elegido
        window.PV_SELECTED_WORKER_ID = null;
    </script>

    <script src="{{ asset('js/puntoVenta/indexV2.js') }}?v={{ time() }}"></script>

@endsection
