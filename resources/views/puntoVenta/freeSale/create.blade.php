@extends('layouts.appAdmin2')

@section('openPuntoVenta')
    menu-open
@endsection

@section('activePuntoVenta')
    active
@endsection

@section('activeCreatePuntoVenta')
    active
@endsection

@section('title')
    Venta Libre
@endsection

@section('styles-plugins')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker.standalone.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.standalone.css') }}">

@endsection

@section('styles')
    <style>
        .free-sale-summary {
            top: 15px;
        }

        #freeSaleItemsTable td {
            vertical-align: middle;
        }

        .item-total-display {
            display: block;
            min-width: 100px;
            font-weight: 600;
            text-align: right;
        }

        .btn-remove-free-sale-item {
            width: 38px;
            height: 38px;
        }

        @media (max-width: 1199px) {
            .free-sale-summary {
                position: static !important;
            }
        }
    </style>
@endsection

@section('page-header')
    <h1 class="page-title">Punto de Venta</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Crear nueva venta libre</h5>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="#"><i class="fa fa-archive"></i> Punto de venta</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Nueva venta libre</li>
    </ol>
@endsection

@section('content')
    <form
            id="formFreeSale"
            method="POST"
            action="{{ route('freeSale.store') }}"
            autocomplete="off"
    >
        @csrf

        <div class="row">

            {{-- CONTENIDO PRINCIPAL --}}
            <div class="col-xl-9">

                {{-- DATOS GENERALES --}}
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-file-invoice mr-1"></i>
                            Datos de la venta libre
                        </h3>
                    </div>

                    <div class="card-body">

                        <div class="row">

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="type_document">
                                        Tipo de documento
                                    </label>

                                    <select
                                            id="type_document"
                                            name="type_document"
                                            class="form-control select2"
                                            style="width: 100%;"
                                    >
                                        <option value="ticket" selected>
                                            Ticket de venta
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="date_sale">
                                        Fecha de venta
                                    </label>

                                    <input
                                            type="date"
                                            id="date_sale"
                                            name="date_sale"
                                            class="form-control"
                                            value="{{ old('date_sale', now()->format('Y-m-d')) }}"
                                    >
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="currency">
                                        Moneda
                                    </label>

                                    <select
                                            id="currency"
                                            name="currency"
                                            class="form-control select2"
                                            style="width: 100%;"
                                    >
                                        <option value="PEN" selected>
                                            Soles
                                        </option>
                                        <option value="USD">
                                            Dólares
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="serie">
                                        Serie interna
                                    </label>

                                    <input
                                            type="text"
                                            id="serie"
                                            name="serie"
                                            class="form-control"
                                            maxlength="20"
                                            value="{{ old('serie') }}"
                                            placeholder="Ej. VL01"
                                    >
                                </div>
                            </div>

                        </div>

                        <hr>

                        <div class="row">

                            {{-- TIPO DE CLIENTE --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="client_mode">
                                        Tipo de cliente
                                        <span class="text-danger">(*)</span>
                                    </label>

                                    <select
                                            id="client_mode"
                                            name="client_mode"
                                            class="form-control select2"
                                            style="width: 100%;"
                                    >
                                        <option value="registered">
                                            Cliente registrado
                                        </option>

                                        <option value="manual" selected>
                                            Cliente no registrado
                                        </option>
                                    </select>
                                </div>
                            </div>

                            {{-- SELECT DE CLIENTES REGISTRADOS --}}
                            <div
                                    class="col-md-8"
                                    id="wrap_registered_customer"
                                    style="display: none;"
                            >
                                <div class="form-group">
                                    <label for="customer_id">
                                        Cliente registrado
                                        <span class="text-danger">(*)</span>
                                    </label>

                                    <select
                                            id="customer_id"
                                            name="customer_id"
                                            class="form-control select2"
                                            style="width: 100%;"
                                            disabled
                                    >
                                        <option value="">
                                            Seleccione un cliente...
                                        </option>

                                        @foreach ($customers as $customer)
                                            <option
                                                    value="{{ $customer->id }}"
                                                    data-business-name="{{ $customer->business_name }}"
                                                    data-ruc="{{ $customer->RUC }}"
                                                    data-address="{{ $customer->address }}"
                                                    data-location="{{ $customer->location }}"
                                            >
                                                {{ $customer->business_name }}
                                                @if ($customer->RUC)
                                                    - {{ $customer->RUC }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>

                                    <div
                                            class="invalid-feedback"
                                            id="customer_id_error"
                                    ></div>
                                </div>
                            </div>

                        </div>

                        <div class="row">

                            {{-- NOMBRE O RAZÓN SOCIAL --}}
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="nombre_cliente">
                                        Cliente / Razón social
                                    </label>

                                    <input
                                            type="text"
                                            id="nombre_cliente"
                                            name="nombre_cliente"
                                            class="form-control"
                                            maxlength="200"
                                            value="{{ old('nombre_cliente') }}"
                                            placeholder="Cliente ocasional"
                                    >
                                </div>
                            </div>

                            {{-- TIPO DE DOCUMENTO --}}
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="tipo_documento_cliente">
                                        Tipo de documento
                                    </label>

                                    <select
                                            id="tipo_documento_cliente"
                                            name="tipo_documento_cliente"
                                            class="form-control select2"
                                            style="width: 100%;"
                                    >
                                        <option value="">
                                            Sin documento
                                        </option>

                                        <option value="1">
                                            DNI
                                        </option>

                                        <option value="6">
                                            RUC
                                        </option>

                                        <option value="4">
                                            Carné de extranjería
                                        </option>

                                        <option value="7">
                                            Pasaporte
                                        </option>
                                    </select>
                                </div>
                            </div>

                            {{-- NÚMERO DE DOCUMENTO --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="numero_documento_cliente">
                                        Número de documento
                                    </label>

                                    <input
                                            type="text"
                                            id="numero_documento_cliente"
                                            name="numero_documento_cliente"
                                            class="form-control"
                                            maxlength="20"
                                            value="{{ old('numero_documento_cliente') }}"
                                            placeholder="Opcional"
                                    >
                                </div>
                            </div>

                            {{-- DIRECCIÓN --}}
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="direccion_cliente">
                                        Dirección
                                    </label>

                                    <input
                                            type="text"
                                            id="direccion_cliente"
                                            name="direccion_cliente"
                                            class="form-control"
                                            maxlength="250"
                                            value="{{ old('direccion_cliente') }}"
                                            placeholder="Opcional"
                                    >
                                </div>
                            </div>

                            {{-- CORREO --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="email_cliente">
                                        Correo electrónico
                                    </label>

                                    <input
                                            type="email"
                                            id="email_cliente"
                                            name="email_cliente"
                                            class="form-control"
                                            maxlength="150"
                                            value="{{ old('email_cliente') }}"
                                            placeholder="Opcional"
                                    >
                                </div>
                            </div>

                            {{-- TELÉFONO --}}
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="telefono_cliente">
                                        Teléfono
                                    </label>

                                    <input
                                            type="text"
                                            id="telefono_cliente"
                                            name="telefono_cliente"
                                            class="form-control"
                                            maxlength="20"
                                            value="{{ old('telefono_cliente') }}"
                                            placeholder="Opcional"
                                    >
                                </div>
                            </div>

                        </div>

                    </div>
                </div>

                {{-- DETALLE DE LA VENTA --}}
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list mr-1"></i>
                            Detalle de la venta
                        </h3>

                        <div class="card-tools">
                            <small class="text-muted">
                                Los productos o servicios se ingresan manualmente
                            </small>
                        </div>
                    </div>

                    <div class="card-body p-0">

                        <div class="table-responsive">
                            <table
                                    class="table table-bordered table-striped mb-0"
                                    id="freeSaleItemsTable"
                            >
                                <thead>
                                <tr>
                                    <th style="min-width: 280px;">
                                        Descripción
                                    </th>
                                    <th style="width: 120px;">
                                        Cantidad
                                    </th>
                                    <th style="width: 150px;">
                                        Precio unitario
                                    </th>
                                    <th style="width: 150px;">
                                        Importe
                                    </th>
                                    <th style="width: 60px;"></th>
                                </tr>
                                </thead>

                                <tbody id="freeSaleItemsBody"></tbody>
                            </table>
                        </div>

                    </div>

                    <div class="card-footer">
                        <button
                                type="button"
                                id="btnAddFreeSaleItem"
                                class="btn btn-outline-primary btn-block"
                        >
                            <i class="fas fa-plus mr-1"></i>
                            Agregar producto o servicio
                        </button>
                    </div>
                </div>

                {{-- FORMA DE PAGO --}}
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-wallet mr-1"></i>
                            Forma de pago
                        </h3>
                    </div>

                    <div class="card-body">

                        <div class="row">

                            @if ($pagosParciales === 's')
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="pagos_parciales_venta">
                                            Pagos parciales
                                        </label>

                                        <div>
                                            <input
                                                    type="checkbox"
                                                    id="pagos_parciales_venta"
                                                    name="pagos_parciales_venta"
                                                    value="s"
                                                    data-bootstrap-switch
                                                    data-on-text="SI"
                                                    data-off-text="NO"
                                                    data-on-color="success"
                                                    data-off-color="danger"
                                            >
                                        </div>

                                        <small class="form-text text-muted">
                                            Al activarlo, el pago se registrará mediante abonos.
                                        </small>
                                    </div>
                                </div>
                            @endif

                            <div
                                    id="wrap_pago_normal"
                                    class="{{ $pagosParciales === 's' ? 'col-md-8' : 'col-md-12' }}"
                            >
                                <div class="row">

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="pv_cash_box_id">
                                                Caja (CashBox)
                                                <span class="text-danger">(*)</span>
                                            </label>

                                            <select
                                                    id="pv_cash_box_id"
                                                    name="cash_box_id"
                                                    class="form-control select2"
                                                    style="width: 100%;"
                                            >
                                                <option value="">
                                                    Seleccione caja...
                                                </option>

                                                @foreach ($cashBoxes as $cashBox)
                                                    <option
                                                            value="{{ $cashBox->id }}"
                                                            data-type="{{ $cashBox->type }}"
                                                            data-uses-subtypes="{{ $cashBox->uses_subtypes ? 1 : 0 }}"
                                                    >
                                                        {{ $cashBox->name }}
                                                    </option>
                                                @endforeach
                                            </select>

                                            <div
                                                    class="invalid-feedback"
                                                    id="cash_box_id_error"
                                            ></div>
                                        </div>
                                    </div>

                                    <div
                                            class="col-md-6"
                                            id="wrap_pv_subtype"
                                            style="display: none;"
                                    >
                                        <div class="form-group">
                                            <label for="pv_cash_box_subtype_id">
                                                Subtipo bancario
                                                <span class="text-danger">(*)</span>
                                            </label>

                                            <select
                                                    id="pv_cash_box_subtype_id"
                                                    name="cash_box_subtype_id"
                                                    class="form-control select2"
                                                    style="width: 100%;"
                                            >
                                                <option value="">Seleccione subtipo...</option>

                                            </select>

                                            <small
                                                    class="text-muted"
                                                    id="pv_subtype_hint"
                                                    style="display: none;"
                                            >
                                                Este canal es diferido: quedará pendiente hasta su regularización.
                                            </small>

                                            <div
                                                    class="invalid-feedback"
                                                    id="cash_box_subtype_id_error"
                                            ></div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group mb-0">
                                    <label for="observations">
                                        Notas u observaciones
                                    </label>

                                    <textarea
                                            id="observations"
                                            name="observations"
                                            class="form-control"
                                            rows="3"
                                            maxlength="500"
                                            placeholder="Ingrese alguna referencia u observación opcional..."
                                    >{{ old('observations') }}</textarea>

                                    <small class="form-text text-muted">
                                        Máximo 500 caracteres.
                                    </small>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>

            </div>

            {{-- RESUMEN --}}
            <div class="col-xl-3">

                <div class="card card-primary card-outline sticky-top free-sale-summary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-calculator mr-1"></i>
                            Resumen
                        </h3>
                    </div>

                    <div class="card-body">

                        <div class="d-flex justify-content-between mb-3">
                            <span>Subtotal</span>
                            <strong id="subtotalView">
                                S/ 0.00
                            </strong>
                        </div>

                        <div class="form-group">
                            <label for="discount_percentage">
                                Descuento
                            </label>

                            <div class="input-group">
                                <input
                                        type="number"
                                        id="discount_percentage"
                                        name="discount_percentage"
                                        class="form-control"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        value="0"
                                >

                                <div class="input-group-append">
                                <span class="input-group-text">
                                    %
                                </span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mb-3">
                            <span>Descuento</span>
                            <strong id="discountView">
                                - S/ 0.00
                            </strong>
                        </div>

                        <div class="form-group">
                            <label for="tax_percentage">
                                IGV
                            </label>

                            <div class="input-group">
                                <input
                                        type="number"
                                        id="tax_percentage"
                                        name="tax_percentage"
                                        class="form-control"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        value="18"
                                >

                                <div class="input-group-append">
                                <span class="input-group-text">
                                    %
                                </span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mb-3">
                            <span>IGV</span>
                            <strong id="taxView">
                                S/ 0.00
                            </strong>
                        </div>

                        <hr>

                        <div class="text-center mb-4">
                            <small class="text-muted">
                                TOTAL A COBRAR
                            </small>

                            <h2
                                    id="totalView"
                                    class="font-weight-bold text-primary mb-0"
                            >
                                S/ 0.00
                            </h2>
                        </div>

                        {{-- MONTO RECIBIDO: SOLO PARA CAJA EFECTIVO --}}
                        <div
                                id="wrapAmountReceived"
                                class="form-group"
                                style="display: none;"
                        >
                            <label for="amount_received">
                                Monto recibido
                                <span class="text-danger">(*)</span>
                            </label>

                            <input
                                    type="number"
                                    id="amount_received"
                                    name="amount_received"
                                    class="form-control"
                                    min="0"
                                    step="0.01"
                                    value=""
                                    placeholder="0.00"
                            >

                            <div
                                    class="invalid-feedback"
                                    id="amount_received_error"
                            ></div>
                        </div>

                        {{-- RESUMEN DEL VUELTO --}}
                        <div
                                id="wrapChange"
                                class="d-flex justify-content-between mb-3"
                                style="display: none;"
                        >
                            <span>Vuelto</span>

                            <strong id="changeView">
                                S/ 0.00
                            </strong>
                        </div>

                        {{-- FORMA DE ENTREGA DEL VUELTO --}}
                        <div
                                id="wrapChangePayment"
                                style="display: none;"
                        >
                            <div class="alert alert-light border py-2 px-3 mb-3">
                                <small class="text-muted">
                                    Seleccione la caja desde la que se entregará el vuelto.
                                </small>
                            </div>

                            <div class="form-group">
                                <label for="change_cash_box_id">
                                    Caja del vuelto
                                    <span class="text-danger">(*)</span>
                                </label>

                                <select
                                        id="change_cash_box_id"
                                        name="change_cash_box_id"
                                        class="form-control select2"
                                        style="width: 100%;"
                                >
                                    <option value="">
                                        Seleccione caja...
                                    </option>

                                    @foreach ($cashBoxes as $cashBox)
                                        <option
                                                value="{{ $cashBox->id }}"
                                                data-type="{{ $cashBox->type }}"
                                                data-uses-subtypes="{{ $cashBox->uses_subtypes ? 1 : 0 }}"
                                        >
                                            {{ $cashBox->name }}
                                        </option>
                                    @endforeach
                                </select>

                                <div
                                        class="invalid-feedback"
                                        id="change_cash_box_id_error"
                                ></div>
                            </div>

                            <div
                                    class="form-group"
                                    id="wrap_change_subtype"
                                    style="display: none;"
                            >
                                <label for="change_cash_box_subtype_id">
                                    Subtipo del vuelto
                                    <span class="text-danger">(*)</span>
                                </label>

                                <select
                                        id="change_cash_box_subtype_id"
                                        name="change_cash_box_subtype_id"
                                        class="form-control select2"
                                        style="width: 100%;"
                                >
                                    <option value="">
                                        Seleccione subtipo...
                                    </option>

                                </select>

                                <small
                                        class="text-muted"
                                        id="change_subtype_hint"
                                        style="display: none;"
                                >
                                    Este canal es diferido: el movimiento quedará pendiente hasta su regularización.
                                </small>

                                <div
                                        class="invalid-feedback"
                                        id="change_cash_box_subtype_id_error"
                                ></div>
                            </div>
                        </div>

                        <input
                                type="hidden"
                                id="subtotal"
                                name="subtotal"
                                value="0.00"
                        >

                        <input
                                type="hidden"
                                id="total_discount"
                                name="total_discount"
                                value="0.00"
                        >

                        <input
                                type="hidden"
                                id="tax_amount"
                                name="tax_amount"
                                value="0.00"
                        >

                        <input
                                type="hidden"
                                id="total_amount"
                                name="total_amount"
                                value="0.00"
                        >

                        <input
                                type="hidden"
                                id="change_amount"
                                name="change_amount"
                                value="0.00"
                        >

                        <button
                                type="submit"
                                id="btnSaveFreeSale"
                                class="btn btn-primary btn-block"
                        >
                            <i class="fas fa-save mr-1"></i>
                            Registrar venta libre
                        </button>

                    </div>
                </div>

            </div>

        </div>
    </form>
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
    <script>
        window.FreeSaleConfig = {
            subtypes: {!! json_encode($subtypesConfig) !!}
        };
    </script>

    <script src="{{ asset('js/puntoVenta/freeSale/create.js') }}?v={{ time() }}"></script>
@endsection