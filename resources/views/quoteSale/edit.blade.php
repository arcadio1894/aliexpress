@extends('layouts.appAdmin2')

@section('openQuote')
    menu-open
@endsection

@section('activeQuote')
    active
@endsection

@section('activeListQuote')
    active
@endsection

@section('title')
    Cotizaciones
@endsection

@section('styles-plugins')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker.standalone.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/bootstrap-datepicker/css/bootstrap-datepicker3.standalone.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/typehead/typeahead.css') }}">
    <!-- summernote -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/summernote/summernote-bs4.css') }}">

@endsection

@section('styles')
    <style>
        .select2-search__field{
            width: 100% !important;
        }

        .input-group .select2-container {
            flex: 1 1 auto;       /* que ocupe el espacio disponible */
            width: 1% !important; /* que no se expanda a 100% */
        }
        .input-group .select2-selection {
            height: 100% !important; /* que se ajuste a la altura del input-group */
        }
    </style>
@endsection

@section('page-header')
    <h1 class="page-title">Cotizaciones</h1>
@endsection

@section('page-title')
    <h5 class="card-title">Modificar cotización</h5>
@endsection

@section('page-breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item">
            <a href="{{ route('dashboard.principal') }}"><i class="fa fa-home"></i> Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('quoteSale.index') }}"><i class="fa fa-key"></i> Cotizaciones</a>
        </li>
        <li class="breadcrumb-item"><i class="fa fa-plus-circle"></i> Editar</li>
    </ol>
@endsection

@section('content')
    <input type="hidden" id="permissions" value="{{ json_encode($permissions) }}">

    <div class="col-md-12">
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Importante!</strong> Código de colores en los productos. <br>
            El color gris indica que el producto no ha sufrido modificaciones. <br>
            El color <strong style="color: blue;">AZUL</strong> indica que el producto ha sido actualizado el precio. <br>
            El color <strong style="color: red;">ROJO</strong> indica que no hay stock en el almacén. <br>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    </div>

    <form id="formEdit" class="form-horizontal" data-url="{{ route('quoteSale.update') }}" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-md-12">
                <div class="card card-success datos_generales">
                    <div class="card-header">
                        <h3 class="card-title">Datos generales</h3>
                        <input type="hidden" id="customer_quote_id" value="{{ $quote->customer_id }}">
                        <input type="hidden" id="contact_quote_id" value="{{ $quote->contact_id }}">
                        <div class="card-tools">
                            {{--<a class="btn btn-primary btn-sm" data-quote="{{ $quote->id }}" data-toggle="tooltip" title="Guardar cambios" id="btn-guardar_datos_generales">
                                <i class="fas fa-check-square"></i> Guardar cambios generales
                            </a>--}}

                            <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fas fa-minus"></i></button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-group row">
                            <div class="col-md-12">
                                <label for="descriptionQuote">Descripción general de cotización </label>
                                <input type="text" id="descriptionQuote" onkeyup="mayus(this);" name="code_description" class="form-control form-control-sm" value="{{ $quote->description_quote }}">
                                <input type="hidden" id="quote_id" name="quote_id" value="{{ $quote->id }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label for="description">Código de cotización </label>
                                <input type="text" id="codeQuote" onkeyup="mayus(this);" name="code_quote" class="form-control form-control-sm" value="{{ $quote->code }}" readonly>
                            </div>

                            <div class="col-md-4" id="sandbox-container">
                                <label for="date_quote">Fecha de cotización </label>
                                <div class="input-daterange" id="datepicker">
                                    <input type="text" class="form-control form-control-sm date-range-filter" id="date_quote" name="date_quote" value="{{ date('d/m/Y', strtotime($quote->date_quote)) }}">
                                </div>
                            </div>
                            <div class="col-md-4" id="sandbox-container">
                                <label for="date_end">Válido hasta </label>
                                <div class="input-daterange" id="datepicker2">
                                    <input type="text" class="form-control form-control-sm date-range-filter" id="date_validate" name="date_validate" value="{{ date('d/m/Y', strtotime($quote->date_validate)) }}">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label for="description">Forma de pago </label>
                                {{--<input type="text" id="paymentQuote" onkeyup="mayus(this);" name="way_to_pay" class="form-control form-control-sm" value="{{ $quote->way_to_pay }}">--}}
                                <select id="paymentQuote" name="payment_deadline" class="form-control form-control-sm select2" style="width: 100%;">
                                    <option></option>
                                    @foreach( $paymentDeadlines as $paymentDeadline )
                                        <option value="{{ $paymentDeadline->id }}" {{ ($paymentDeadline->id == $quote->payment_deadline_id) ? 'selected':'' }} >{{ $paymentDeadline->description }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="timeQuote">Tiempo de entrega </label>
                                <div class="input-group input-group-sm mb-3">
                                    <input type="number" id="timeQuote" step="1" min="0" name="delivery_time" class="form-control form-control-sm" value="{{ $quote->time_delivery }}">
                                    <div class="input-group-append">
                                        <span class="input-group-text" id="basic-addon2"> DIAS</span>
                                    </div>
                                </div>
                                {{--<input type="text" id="timeQuote" onkeyup="mayus(this);" name="delivery_time" class="form-control form-control-sm" value="{{ $quote->delivery_time }}">--}}
                            </div>
                            {{--@hasanyrole('logistic|admin')--}}
                            <div class="col-md-4">
                                <label for="customer_id">Cliente</label>
                                <div class="input-group input-group-sm">
                                    <select id="customer_id" name="customer_id" class="form-control select2bs4">
                                        <option></option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" {{ ($customer->id == $quote->customer_id) ? 'selected':'' }}>
                                                {{ $customer->business_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-primary" id="btn-add-customer">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="contact_id">Contacto </label>
                                <select id="contact_id" name="contact_id" class="form-control form-control-sm select2" style="width: 100%;">
                                    <option></option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label for="observations">Observaciones </label>
                                <textarea class="textarea_observations" id="observations" name="observations" placeholder="Place some text here"
                                          style="width: 100%; height: 200px; font-size: 14px; line-height: 18px; border: 1px solid #dddddd; padding: 10px;">{{ $quote->observations }}</textarea>
                            </div>
                            {{--@endhasanyrole--}}
                        </div>

                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
        </div>
        {{--<div class="row">
            <div class="col-md-4 offset-md-4 col-sm-4 offset-sm-4">
                <button type="button" id="btn-addEquipment" class="btn btn-block bg-gradient-primary">Nuevo equipo <i class="fas fa-plus-circle"></i></button>
                <br>
            </div>
        </div>--}}

        <div class="row" id="element_loader">
            @foreach( $quote->equipments as $equipment )
            <div class="col-md-12">
                <div class="card card-success" data-equip>
                    <div class="card-header">
                        <h3 class="card-title">COTIZACIÓN</h3>

                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-group row">
                            <input type="hidden" data-utilityequipment="" value="{{ $equipment->utility }}">
                            <input type="hidden" data-rentequipment="" value="{{ $equipment->rent }}">
                            <input type="hidden" data-letterequipment="" value="{{ $equipment->letter }}">
                            <input type="hidden" name="" id="igv" value="{{ $igv }}">
                            <div class="col-md-12" style="display: none">
                                <label for="description">Detalles de cotización <span class="right badge badge-danger">(*)</span></label>
                                <textarea class="textarea_edit" data-detailequipment placeholder="Place some text here"
                                          style="width: 100%; height: 200px; font-size: 14px; line-height: 18px; border: 1px solid #dddddd; padding: 10px;">{{ $equipment->detail }}</textarea>
                            </div>
                        </div>

                        <div class="card card-warning">
                            <div class="card-header">
                                <h3 class="card-title">PRODUCTOS</h3>

                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-10">
                                        <div class="form-group">
                                            <label>Seleccionar producto <span class="right badge badge-danger">(*)</span></label>
                                            <select class="form-control consumable_search" data-consumable style="width:100%" name="consumable_search"></select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="btn-add"> &nbsp; </label>
                                        <button type="button" data-addConsumable class="btn btn-block btn-outline-primary">Agregar <i class="fas fa-arrow-circle-right"></i></button>
                                    </div>
                                </div>
                                <hr>
                                <div data-bodyConsumable>
                                    <div class="row">
                                        <div class="col-md-4"><strong>Descripción</strong></div>
                                        <div class="col-md-1"><strong>Present.</strong></div>
                                        <div class="col-md-1"><strong>Unidad</strong></div>
                                        <div class="col-md-1"><strong>Cantidad</strong></div>
                                        <div class="col-md-1"><strong>V/U</strong></div>
                                        <div class="col-md-1"><strong>P/U</strong></div>
                                        <div class="col-md-2"><strong>Importe</strong></div>
                                        <div class="col-md-1"><strong>Acción</strong></div>
                                    </div>

                                    @foreach($equipment->consumables as $consumable)
                                        @php
                                            $presText = $consumable->presentation
                                                ? ($consumable->presentation->label ?? ($consumable->presentation->quantity . ' und'))
                                                : 'Unidad';

                                            // Cantidad visible: packs si hay presentación, caso contrario cantidad real.
                                            $qtyView = round($consumable->packs ?? $consumable->quantity, 2);

                                            // Valores reales usados por JavaScript.
                                            $valorReal = $consumable->valor_unitario;
                                            $priceReal = $consumable->price;
                                            $importeReal = $consumable->total;

                                            /*
                                             * El tipo de venta se determina desde el material asociado
                                             * al StockItem; no desde ningún dato enviado por frontend.
                                             */
                                            $isItemeable = (int) (
                                                $consumable->stockItem->material->tipo_venta_id ?? 0
                                            ) === 3;

                                            /*
                                             * Un EquipmentConsumable puede tener varios QuoteStockLot
                                             * porque los items pueden venir de distintos lotes.
                                             */
                                            $selectedItemIds = $consumable->quoteStockLots
                                                ? $consumable->quoteStockLots
                                                    ->flatMap(function ($quoteStockLot) {
                                                        return $quoteStockLot->item_ids ?? [];
                                                    })
                                                    ->map(function ($itemId) {
                                                        return (int) $itemId;
                                                    })
                                                    ->filter(function ($itemId) {
                                                        return $itemId > 0;
                                                    })
                                                    ->unique()
                                                    ->values()
                                                    ->toArray()
                                                : [];

                                            $selectedItemCodes = [];

                                            if (!empty($selectedItemIds)) {
                                                $itemsById = \App\Item::whereIn('id', $selectedItemIds)
                                                    ->get(['id', 'code'])
                                                    ->keyBy('id');

                                                $selectedItemCodes = collect($selectedItemIds)
                                                    ->map(function ($itemId) use ($itemsById) {
                                                        $item = $itemsById->get($itemId);

                                                        if (!$item) {
                                                            return 'Ítem #' . $itemId;
                                                        }

                                                        return $item->code ?: ('Ítem #' . $item->id);
                                                    })
                                                    ->values()
                                                    ->toArray();
                                            }
                                            /*
                                             * Por ahora mostramos los IDs en tooltip.
                                             * Luego, al revisar el controlador de edición, lo mejoraremos
                                             * para mostrar los códigos reales de cada Item sin N+1 queries.
                                             */
                                            $selectedItemsText = !empty($selectedItemCodes)
                                            ? implode(', ', $selectedItemCodes)
                                            : '';

                                            $itemTooltip = $isItemeable && $selectedItemsText !== ''
                                            ? 'Ítems seleccionados: ' . $selectedItemsText
                                            : '';
                                        @endphp

                                        <div class="row" data-consumableRow>

                                            {{-- Descripción --}}
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <input type="text"
                                                           class="form-control form-control-sm"
                                                           value="{{ $consumable->material->code." ".$consumable->stockItem->display_name }}"
                                                           data-consumableDescription
                                                           title="{{ $itemTooltip }}"
                                                           {{ $consumable->stockItem->ui_color ? 'style=color:' . $consumable->stockItem->ui_color : '' }}
                                                           readonly>

                                                    {{--
                                                        IMPORTANTE:
                                                        data-consumableid debe ser stock_item_id.
                                                        En creación también se usa el ID del StockItem.
                                                    --}}
                                                    <input type="hidden"
                                                           data-consumableid="{{ $consumable->stock_item_id }}"
                                                           data-is_itemeable="{{ $isItemeable ? 1 : 0 }}"
                                                           data-selected_item_ids='@json($selectedItemIds)'
                                                           data-selected_items_text="{{ $selectedItemsText }}">

                                                    <input type="hidden"
                                                           data-stock_item_id="{{ $consumable->stock_item_id }}">

                                                    <input type="hidden"
                                                           data-descuento="{{ $consumable->discount }}">

                                                    <input type="hidden"
                                                           data-type_promotion="{{ $consumable->type_promo }}">

                                                    {{-- Presentación --}}
                                                    <input type="hidden"
                                                           data-presentation_id="{{ $consumable->material_presentation_id }}">

                                                    <input type="hidden"
                                                           data-units_per_pack="{{ $consumable->units_per_pack }}">

                                                    <input type="hidden"
                                                           data-units_equivalent="{{ $consumable->quantity }}">

                                                    <input type="hidden"
                                                           data-packs="{{ $consumable->packs }}">
                                                </div>
                                            </div>

                                            {{-- Presentación --}}
                                            <div class="col-md-1">
                                                <div class="form-group">
                                                    <input type="text"
                                                           class="form-control form-control-sm"
                                                           value="{{ $presText }}"
                                                           data-presentation_text
                                                           readonly>
                                                </div>
                                            </div>

                                            {{-- Unidad --}}
                                            <div class="col-md-1">
                                                <div class="form-group">
                                                    <input type="text"
                                                           class="form-control form-control-sm"
                                                           value="{{ $consumable->material->unitMeasure->description }}"
                                                           data-consumableUnit
                                                           readonly>
                                                </div>
                                            </div>

                                            {{-- Cantidad --}}
                                            <div class="col-md-1">
                                                <div class="form-group">
                                                    <input type="number"
                                                           class="form-control form-control-sm"
                                                           min="0"
                                                           step="{{ $consumable->material_presentation_id ? '1' : '0.01' }}"
                                                           data-consumableQuantity
                                                           value="{{ $qtyView }}"
                                                           oninput="calculateTotalC(this);">
                                                </div>
                                            </div>

                                            {{-- Valor unitario --}}
                                            <div class="col-md-1" @cannot('showPrices_quote') style="display:none" @endcannot>
                                                <div class="form-group">
                                                    <input type="number"
                                                           class="form-control form-control-sm"
                                                           data-consumableValor
                                                           data-consumable_valor_real="{{ $valorReal }}"
                                                           value="{{ round($valorReal, 2) }}"
                                                           readonly>
                                                </div>
                                            </div>

                                            {{-- Precio unitario --}}
                                            <div class="col-md-1" @cannot('showPrices_quote') style="display:none" @endcannot>
                                                <div class="form-group">
                                                    <input type="number"
                                                           class="form-control form-control-sm"
                                                           data-consumablePrice
                                                           data-consumable_price_real="{{ $priceReal }}"
                                                           value="{{ round($priceReal, 2) }}"
                                                           readonly>
                                                </div>
                                            </div>

                                            {{-- Importe --}}
                                            <div class="col-md-2" @cannot('showPrices_quote') style="display:none" @endcannot>
                                                <div class="form-group">
                                                    <input type="number"
                                                           class="form-control form-control-sm"
                                                           data-consumableImporte
                                                           value="{{ round($importeReal, 2) }}"
                                                           readonly>
                                                </div>
                                            </div>

                                            {{-- Eliminar --}}
                                            <div class="col-md-1">
                                                <button type="button"
                                                        data-deleteConsumable
                                                        class="btn btn-block btn-outline-danger btn-sm">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>

                                        </div>
                                    @endforeach

                                </div>
                            </div>
                        </div>

                        <div class="card card-cyan ">
                            <div class="card-header">
                                <h3 class="card-title">SERVICIOS ADICIONALES</h3>

                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="material_search">Descripción <span class="right badge badge-danger">(*)</span></label>
                                            <input type="text" id="material_search" onkeyup="mayus(this);" class="form-control">

                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label >Unidad <span class="right badge badge-danger">(*)</span></label>
                                            <select class="form-control select2 unitMeasure" style="width: 100%;">
                                                <option></option>
                                                @foreach( $unitMeasures as $unitMeasure )
                                                    <option value="{{ $unitMeasure->id }}">{{ $unitMeasure->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="quantity">Cantidad <span class="right badge badge-danger">(*)</span></label>
                                            <input type="number" id="quantity" class="form-control" placeholder="0.00" min="0" value="0" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" onblur="
                                                this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                                                ">
                                        </div>
                                    </div>
                                    @can('showPrices_quote')
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="price">Precio C/IGV <span class="right badge badge-danger">(*)</span></label>
                                                <input type="number" id="price" class="form-control" placeholder="0.00" min="0" value="0" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" onblur="
                                                this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                                                ">
                                            </div>
                                        </div>
                                    @endcan
                                    <div class="col-md-2">
                                        <label for="btn-add"> &nbsp; </label>
                                        <button type="button" data-addService class="btn btn-block btn-outline-primary">Agregar <i class="fas fa-arrow-circle-right"></i></button>
                                    </div>

                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <strong>Descripción</strong>
                                        </div>
                                    </div>
                                    <div class="col-md-1">
                                        <div class="form-group">
                                            <strong>Unidad</strong>
                                        </div>
                                    </div>
                                    <div class="col-md-1">
                                        <div class="form-group">
                                            <strong>Cantidad</strong>
                                        </div>
                                    </div>
                                    <div class="col-md-1">
                                        <div class="form-group">
                                            <strong>V/U</strong>
                                        </div>
                                    </div>
                                    <div class="col-md-1">
                                        <div class="form-group">
                                            <strong>P/U</strong>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <strong>Importe</strong>
                                        </div>
                                    </div>

                                    <div class="col-md-1">
                                        <div class="form-group">
                                            <strong>Facturar</strong>
                                        </div>
                                    </div>

                                    <div class="col-md-1">
                                        <div class="form-group">
                                            <strong>Acción</strong>
                                        </div>
                                    </div>
                                </div>
                                <div data-bodyService>

                                    @foreach($equipment->workforces as $workforce)
                                        @php
                                            $qty = (float) $workforce->quantity;
                                            $pu  = (float) $workforce->price; // P/U con IGV
                                            $vu  = $pu / (1 + ($igv/100));     // V/U sin IGV
                                            $imp = (float) $workforce->total;

                                            $qtyFmt = rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.');
                                        @endphp

                                        <div class="row" data-serviceRow>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <input type="text" onkeyup="mayus(this);" class="form-control form-control-sm"
                                                           value="{{ $workforce->description }}" data-serviceDescription>
                                                    <input type="hidden" data-serviceId value="{{ $workforce->id }}">
                                                </div>
                                            </div>

                                            <div class="col-md-1">
                                                <div class="form-group">
                                                    <input type="text" class="form-control form-control-sm"
                                                           value="{{ $workforce->unit }}" data-serviceUnit readonly>
                                                </div>
                                            </div>

                                            <div class="col-md-1">
                                                <div class="form-group">
                                                    <input type="number" class="form-control form-control-sm"
                                                           data-serviceQuantity min="0" step="0.01"
                                                           value="{{ $qtyFmt }}">
                                                </div>
                                            </div>

                                            <div class="col-md-1">
                                                <div class="form-group">
                                                    <input type="number" class="form-control form-control-sm"
                                                           data-serviceVU value="{{ round($vu,2) }}" readonly
                                                           @cannot('showPrices_quote') style="display:none" @endcannot>
                                                </div>
                                            </div>

                                            <div class="col-md-1">
                                                <div class="form-group">
                                                    <input type="number" class="form-control form-control-sm"
                                                           data-servicePU value="{{ round($pu,2) }}" min="0" step="0.01"
                                                           @cannot('showPrices_quote') style="display:none" @endcannot>
                                                </div>
                                            </div>

                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <input type="number" class="form-control form-control-sm"
                                                           data-serviceImporte value="{{ round($imp,2) }}" readonly
                                                           @cannot('showPrices_quote') style="display:none" @endcannot>
                                                </div>
                                            </div>

                                            <div class="col-md-1">
                                                <div class="form-group text-center">
                                                    <div class="icheck-primary d-inline">
                                                        <input type="checkbox"
                                                               id="billable_{{ $equipment->id }}_{{ $workforce->id }}"
                                                               data-serviceBillable
                                                                {{ ($workforce->billable ?? true) ? 'checked' : '' }}>
                                                        <label for="billable_{{ $equipment->id }}_{{ $workforce->id }}"></label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-1">
                                                <button type="button" data-deleteService class="btn btn-block btn-outline-danger btn-sm">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <!-- /.card -->

                        <div class="card card-purple">
                            <div class="card-header">
                                <h3 class="card-title">DESCUENTO GLOBAL</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="card-body" id="discountSection"
                                 data-discount_type="{{ $quote->discount_type ?? 'amount' }}"
                                 data-discount_input_mode="{{ $quote->discount_input_mode ?? 'without_igv' }}"
                                 data-discount_value="{{ $quote->discount_input_value ?? 0 }}">

                                <div class="row">
                                    <!-- Tipo -->
                                    <div class="col-md-3">
                                        <label>Tipo</label>
                                        <div class="form-group clearfix">
                                            <div class="icheck-primary d-inline">
                                                <input type="radio" name="discount_type"
                                                       id="discount_type_amount"
                                                       value="amount"
                                                        {{ ($quote->discount_type ?? 'amount') === 'amount' ? 'checked' : '' }}>
                                                <label for="discount_type_amount">Monto (S/)</label>
                                            </div>
                                            <div class="icheck-primary d-inline ml-3">
                                                <input type="radio" name="discount_type"
                                                       id="discount_type_percent"
                                                       value="percent"
                                                        {{ ($quote->discount_type ?? '') === 'percent' ? 'checked' : '' }}>
                                                <label for="discount_type_percent">Porcentaje (%)</label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Valor -->
                                    <div class="col-md-3">
                                        <label>Valor</label>
                                        <input type="number"
                                               class="form-control"
                                               id="discount_value"
                                               min="0"
                                               step="0.01"
                                               value="{{ $quote->discount_input_value ?? 0 }}">
                                        <small class="text-muted" id="discount_value_hint">Ingrese monto en soles.</small>
                                    </div>

                                    <!-- Modo -->
                                    <div class="col-md-4">
                                        <label>Modo de ingreso</label>
                                        <div class="form-group clearfix">
                                            <div class="icheck-primary d-inline">
                                                <input type="radio" name="discount_input_mode"
                                                       id="discount_mode_without"
                                                       value="without_igv"
                                                        {{ ($quote->discount_input_mode ?? 'without_igv') === 'without_igv' ? 'checked' : '' }}>
                                                <label for="discount_mode_without">SIN IGV (base)</label>
                                            </div>
                                            <div class="icheck-primary d-inline ml-3">
                                                <input type="radio" name="discount_input_mode"
                                                       id="discount_mode_with"
                                                       value="with_igv"
                                                        {{ ($quote->discount_input_mode ?? '') === 'with_igv' ? 'checked' : '' }}>
                                                <label for="discount_mode_with">CON IGV (del total)</label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Acciones -->
                                    <div class="col-md-2">
                                        <label>&nbsp;</label>
                                        <button type="button" class="btn btn-outline-secondary btn-block" id="btn-clear-discount">
                                            Limpiar
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-9">
                                <div class="alert alert-warning mt-2 mb-0" id="alert_edit" style="display: none">
                                    Se han detectado cambios, no olvide guardar los productos.
                                </div>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-primary btn-block" id="btn-saveProducts" data-quote="{{ $quote->id }}" data-idEquipment="{{ $equipment->id }}">Guardar cambios en la cotización</button>
                            </div>
                        </div>
                    </div>
                    <!-- /.card-body -->

                </div>
            </div>
            @endforeach
        </div>

        @can('showPrices_quote')
            <div class="row">
                <!-- accepted payments column -->
                <div class="col-sm-7">

                </div>
                <!-- /.col -->
                <div class="col-sm-5">
                    <p class="lead">Resumen de Cotización</p>

                    <div class="table-responsive">
                        <table class="table">
                            <tr>
                                <th style="width:50%">DESCUENTO (-): </th>
                                <td>{{ ($currency == 'pen') ?'PEN' : 'USD' }} <span id="descuento" data-descuento_real="{{ $quote->descuento }}" class="align-right">{{ round($quote->descuento, 2) }}</span></td>
                            </tr>
                            <tr>
                                <th style="width:50%">GRAVADA: </th>
                                <td>{{ ($currency == 'pen') ?'PEN' : 'USD' }} <span id="gravada" data-gravada_real="{{ $quote->gravada }}" class="align-right">{{ round($quote->gravada, 2) }}</span></td>
                            </tr>
                            <tr>
                                <th style="width:50%">IGV {{ $igv }}%: </th>
                                <td>{{ ($currency == 'pen') ?'PEN' : 'USD' }} <span id="igv_total" data-igv_total_real="{{ $quote->igv_total }}" class="align-right">{{ round($quote->igv_total, 2) }}</span></td>
                            </tr>
                            <tr>
                                <th style="width:50%">TOTAL: </th>
                                <td>{{ ($currency == 'pen') ?'PEN' : 'USD' }} <span id="total_importe" data-total_importe_real="{{ $quote->total_importe }}" class="align-right">{{ round($quote->total_importe, 2) }}</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <!-- /.col -->
            </div>
        @endcan

    </form>

    <template id="template-consumable">
        <div class="row" data-consumableRow>

            {{-- Descripción --}}
            <div class="col-md-4">
                <div class="form-group">
                    <input type="text" class="form-control form-control-sm" data-consumableDescription readonly>

                    {{-- se leen por attr() --}}
                    <input type="hidden" data-consumableid>
                    <input type="hidden" data-stock_item_id>
                    <input type="hidden" data-descuento>
                    <input type="hidden" data-type_promotion>

                    {{-- presentación --}}
                    <input type="hidden" data-presentation_id>
                    <input type="hidden" data-units_per_pack>
                    <input type="hidden" data-units_equivalent>
                </div>
            </div>

            {{-- Presentación --}}
            <div class="col-md-1">
                <div class="form-group">
                    <input type="text" class="form-control form-control-sm" data-presentation_text readonly>
                </div>
            </div>

            {{-- Unidad --}}
            <div class="col-md-1">
                <div class="form-group">
                    <input type="text" class="form-control form-control-sm" data-consumableUnit readonly>
                </div>
            </div>

            {{-- Cantidad (packs o unidades) --}}
            <div class="col-md-1">
                <div class="form-group">
                    <input type="number" class="form-control form-control-sm"
                           min="0" step="0.01" data-consumableQuantity
                           oninput="calculateTotalC(this);">
                </div>
            </div>

            {{-- V/U (sin IGV) --}}
            <div class="col-md-1">
                <div class="form-group">
                    <input type="number" class="form-control form-control-sm" data-consumableValor data-consumable_valor_real readonly>
                </div>
            </div>

            {{-- P/U (con IGV) --}}
            <div class="col-md-1">
                <div class="form-group">
                    <input type="number" class="form-control form-control-sm" data-consumablePrice data-consumable_price_real readonly>
                </div>
            </div>

            {{-- Importe --}}
            <div class="col-md-2">
                <div class="form-group">
                    <input type="number" class="form-control form-control-sm" data-consumableImporte readonly>
                </div>
            </div>

            <div class="col-md-1">
                <button type="button" data-deleteConsumable class="btn btn-block btn-outline-danger btn-sm">
                    <i class="fas fa-trash"></i>
                </button>
            </div>

        </div>
    </template>

    <template id="template-service">
        <div class="row" data-serviceRow>
            <div class="col-md-4">
                <div class="form-group">
                    <input type="text" onkeyup="mayus(this);" class="form-control form-control-sm" data-serviceDescription>
                    <input type="hidden" data-serviceId>
                </div>
            </div>

            <div class="col-md-1">
                <div class="form-group">
                    <input type="text" class="form-control form-control-sm" data-serviceUnit readonly>
                </div>
            </div>

            <div class="col-md-1">
                <div class="form-group">
                    <input type="number" class="form-control form-control-sm"
                           placeholder="0.00" min="0" step="0.01" data-serviceQuantity>
                </div>
            </div>

            <div class="col-md-1">
                <div class="form-group">
                    <input type="number" class="form-control form-control-sm"
                           placeholder="0.00" min="0" data-serviceVU readonly
                           @cannot('showPrices_quote') style="display:none" @endcannot>
                </div>
            </div>

            <div class="col-md-1">
                <div class="form-group">
                    <input type="number" class="form-control form-control-sm"
                           placeholder="0.00" min="0" step="0.01" data-servicePU
                           @cannot('showPrices_quote') style="display:none" @endcannot>
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    <input type="number" class="form-control form-control-sm"
                           placeholder="0.00" min="0" data-serviceImporte readonly
                           @cannot('showPrices_quote') style="display:none" @endcannot>
                </div>
            </div>

            <div class="col-md-1">
                <div class="form-group text-center">
                    <div class="icheck-primary d-inline">
                        <input type="checkbox" data-serviceBillable data-billable-id>
                        <label data-billable-label></label>
                    </div>
                </div>
            </div>

            <div class="col-md-1">
                <button type="button" data-deleteService class="btn btn-block btn-outline-danger btn-sm">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    </template>

    <div id="modalQuantityConsumable" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h4 class="modal-title">Ingrese cantidad / presentaciones</h4>
                </div>

                <input type="hidden" id="c_quantity_productId">
                <input type="hidden" id="c_quantity_is_itemeable" value="0">
                <div class="modal-body">

                    <div class="form-group row">
                        <div class="col-md-6">
                            <label for="c_quantity_total">Cantidad (Unidad)</label>
                            <input type="number" min="0" step="0.01" class="form-control" id="c_quantity_total" value="0">
                            <small class="text-muted">Esto agrega en unidad usando el precio base del producto.</small>
                        </div>
                        <div class="col-md-6">
                            <label>Stock disponible (unidades)</label>
                            <input type="text" class="form-control" id="c_quantity_stock_show" readonly>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-2">
                        <strong>Presentaciones</strong>
                        <div class="text-muted" style="font-size: 12px;">
                            Ingresa cuántos “paquetes” quieres agregar.
                        </div>
                    </div>

                    <div id="c_presentationsArea">
                        <div class="text-muted">Cargando presentaciones...</div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="btn-notAddConsumable">Cancelar</button>
                    <button type="button" id="btn-add_consumable_modal" class="btn btn-success">Agregar</button>
                </div>

            </div>
        </div>
    </div>

    <div id="modalSelectItemeableItems" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <div class="modal-header">
                    <h4 class="modal-title">Seleccione los ítems</h4>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <p class="mb-1">
                            <strong>Producto:</strong>
                            <span id="itemeable-product-name"></span>
                        </p>

                        <p class="mb-1">
                            <strong>Ítems requeridos:</strong>
                            <span id="itemeable-required-count">0</span>
                        </p>

                        <p class="mb-0">
                            <strong>Seleccionados:</strong>
                            <span id="itemeable-selected-count">0</span>
                            de
                            <span id="itemeable-selected-required-count">0</span>
                        </p>
                    </div>

                    <div id="itemeable-items-loading" class="text-center py-3">
                        <i class="fa fa-spinner fa-spin"></i>
                        Cargando ítems disponibles...
                    </div>

                    <div id="itemeable-items-empty" class="alert alert-warning" style="display: none;">
                        No existen ítems disponibles para este producto.
                    </div>

                    <div class="form-group mb-3">
                        <label for="itemeable-item-search">
                            Buscar o escanear código de ítem
                        </label>

                        <input
                                type="text"
                                id="itemeable-item-search"
                                class="form-control"
                                autocomplete="off"
                                placeholder="Escanee o escriba el código del ítem"
                        >

                        <small class="text-muted">
                            Al encontrar una coincidencia exacta, el ítem se seleccionará automáticamente.
                        </small>
                    </div>

                    <div id="itemeable-items-table-container" style="display: none;">
                        <div class="itemeable-items-scroll">
                            <table class="table table-bordered table-hover mb-0">
                                <thead>
                                <tr>
                                    <th style="width: 70px;" class="text-center">
                                        Elegir
                                    </th>
                                    <th>Código del ítem</th>
                                    <th style="width: 120px;" class="text-center">
                                        Estado
                                    </th>
                                </tr>
                                </thead>
                                <tbody id="itemeable-items-table-body"></tbody>
                            </table>
                        </div>
                    </div>

                    <div id="itemeable-items-error" class="alert alert-danger" style="display: none;">
                        No se pudieron cargar los ítems disponibles.
                    </div>

                </div>

                <div class="modal-footer">
                    <button
                            type="button"
                            class="btn btn-secondary"
                            id="btn-cancel-itemeable-items"
                    >
                        Cancelar
                    </button>

                    <button
                            type="button"
                            class="btn btn-success"
                            id="btn-confirm-itemeable-items"
                            disabled
                    >
                        Confirmar ítems
                    </button>
                </div>

            </div>
        </div>
    </div>

    <div id="modalChangePercentages" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Cambiar los porcentages de ganancia</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="quote_percentage" id="quote_percentage">
                    <input type="hidden" name="equipment_percentage" id="equipment_percentage">

                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                <strong>Importante!</strong> Se recargará automaticamente la página para que hagan efecto los cambios.
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <label class="col-sm-12 control-label" for="percentage_utility"> Utilidad </label>

                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control form-control-sm" name="percentage_utility" id="percentage_utility" placeholder="0.00" min="0" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" onblur="
                                        this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                                        ">
                                <div class="input-group-append">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="col-sm-12 control-label" for="percentage_letter"> Letra </label>

                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control form-control-sm" name="percentage_letter" id="percentage_letter" placeholder="0.00" min="0" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" onblur="
                                        this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                                        ">
                                <div class="input-group-append">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4" >
                            <label class="col-sm-12 control-label" for="percentage_rent"> Renta </label>

                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control form-control-sm" name="percentage_rent" id="percentage_rent" placeholder="0.00" min="0" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" onblur="
                                        this.style.borderColor=/^\d+(?:\.\d{1,2})?$/.test(this.value)?'':'red'
                                        ">
                                <div class="input-group-append">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Cancelar</button>
                    <button type="button" id="btn-changePercentage" class="btn btn-outline-primary">Guardar porcentajes</button>
                </div>

            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="promotionModal" tabindex="-1" role="dialog" aria-labelledby="promotionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Promociones disponibles</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="promotion-content">
                    <!-- Aquí se cargan dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Cliente -->
    <div class="modal fade" id="modalCustomer" tabindex="-1" role="dialog" aria-labelledby="modalCustomerLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalCustomerLabel">Nuevo Cliente</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <form id="formCreateCustomer" class="form-horizontal" data-url="{{ route('customer.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-12 col-form-label">RUC <span class="right badge badge-danger">(*)</span></label>
                                <input type="text" class="form-control" name="ruc" placeholder="Ejm: 1234678901">
                            </div>

                            <div class="col-md-2">
                                <label class="col-12 col-form-label">Extranjero <span class="right badge badge-danger">(*)</span></label>
                                <input id="btn-grouped" type="checkbox" name="special" data-bootstrap-switch data-off-color="danger" data-on-text="SI" data-off-text="NO" data-on-color="success">
                            </div>

                            <div class="col-md-6">
                                <label class="col-12 col-form-label">Razon Social <span class="right badge badge-danger">(*)</span></label>
                                <input type="text" class="form-control" onkeyup="mayus(this);" name="business_name" placeholder="Ejm: Edesce EIRL">
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-6">
                                <label class="col-12 col-form-label">Direccion</label>
                                <input type="text" class="form-control" onkeyup="mayus(this);" name="address" placeholder="Ejm: Jr Union">
                            </div>

                            <div class="col-md-6">
                                <label class="col-12 col-form-label">Ubicacion</label>
                                <input type="text" class="form-control" onkeyup="mayus(this);" name="location" placeholder="Ejm: Moche">
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" id="btn-submit-customer" class="btn btn-outline-success">Guardar</button>
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
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
    <script src="{{asset('admin/plugins/typehead/typeahead.bundle.js')}}"></script>
@endsection

@section('scripts')
    <script src="{{asset('admin/plugins/summernote/summernote-bs4.min.js')}}"></script>
    <script src="{{asset('admin/plugins/summernote/lang/summernote-es-ES.js')}}"></script>
    <script src="{{ asset('admin/plugins/jquery_loading/loadingoverlay.min.js')}}"></script>

    <script>
        $(function () {
            //Initialize Select2 Elements
            $('.textarea_observations').summernote({
                lang: 'es-ES',
                placeholder: 'Ingrese los detalles',
                tabsize: 2,
                height: 120,
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['fontname', ['fontname']],
                    ['para', ['ul', 'ol']],
                    ['insert', ['link']],
                    ['view', ['codeview', 'help']]
                ]
            });
            $('.textarea_edit').summernote({
                lang: 'es-ES',
                placeholder: 'Ingrese los detalles',
                tabsize: 2,
                height: 120,
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['fontname', ['fontname']],
                    ['para', ['ul', 'ol']],
                    ['insert', ['link']],
                    ['view', ['codeview', 'help']]
                ]
            });

            $('#customer_id').select2({
                placeholder: "Selecione cliente",
                theme: 'bootstrap4',
                width: 'resolve'
            });
            $('#contact_id').select2({
                placeholder: "Selecione contacto",
            });

            $('#paymentQuote').select2({
                placeholder: "Selecione forma de pago",
            });

            $('.unitMeasure').select2({
                placeholder: "Seleccione unidad",
            });

            $('.material_search').select2({
                placeholder: 'Selecciona un material',
                ajax: {
                    url: '/dashboard/select/materials',
                    dataType: 'json',
                    type: 'GET',
                    processResults(data) {
                        //console.log(data);
                        return {
                            results: $.map(data, function (item) {
                                //console.log(item.full_description);
                                return {
                                    text: item.full_description,
                                    id: item.id,
                                }
                            })
                        }
                    }
                }
            });

            $('.consumable_search').select2({
                placeholder: 'Selecciona un consumible',
                ajax: {
                    url: '/dashboard/select/consumables',
                    dataType: 'json',
                    type: 'GET',
                    processResults(data) {
                        //console.log(data);
                        return {
                            results: $.map(data, function (item) {
                                //console.log(item.full_description);
                                return {
                                    text: item.full_description,
                                    id: item.id,
                                }
                            })
                        }
                    }
                }
            });


            $('#sandbox-container .input-daterange').datepicker({
                todayBtn: "linked",
                clearBtn: true,
                language: "es",
                multidate: false,
                autoclose: true
            });
            $("input[data-bootstrap-switch]").each(function(){
                $(this).bootstrapSwitch();
            });
        })
    </script>

    <script>
        window.APP_QUOTE = window.APP_QUOTE || {};
        window.APP_QUOTE.URLS = window.APP_QUOTE.URLS || {};

        window.APP_QUOTE.URLS.AVAILABLE_ITEMS =
            "{{ route('quotes.stock-items.available-items', ':stockItemId') }}";
    </script>

    <script src="{{ asset('js/quoteSale/edit.js') }}?v={{ time() }}"></script>
@endsection
