<?php

namespace App\Http\Controllers;

use App\Audit;
use App\CashBox;
use App\CashBoxSubtype;
use App\CashMovement;
use App\CashRegister;
use App\Customer;
use App\DataGeneral;
use App\Equipment;
use App\EquipmentConsumable;
use App\EquipmentElectric;
use App\EquipmentMaterial;
use App\EquipmentTurnstile;
use App\EquipmentWorkday;
use App\EquipmentWorkforce;
use App\Http\Controllers\Traits\NubefactTrait;
use App\Http\Requests\StoreQuoteRequest;
use App\Http\Requests\UpdateQuoteRequest;
use App\ImagesQuote;
use App\InventoryLevel;
use App\Item;
use App\Mail\StockLowNotificationMail;
use App\Material;
use App\MaterialPresentation;
use App\Notification;
use App\NotificationUser;
use App\Output;
use App\OutputDetail;
use App\PaymentDeadline;
use App\PorcentageQuote;
use App\PriceListItem;
use App\PromotionLimit;
use App\PromotionUsage;
use App\Quote;
use App\QuoteMaterialReservation;
use App\QuoteStockLot;
use App\QuoteUser;
use App\ResumenEquipment;
use App\ResumenQuote;
use App\Sale;
use App\SaleDetail;
use App\Services\InventoryCostService;
use App\StockItem;
use App\StockLot;
use App\TipoPago;
use App\UnitMeasure;
use App\User;
use App\Worker;
use App\Workforce;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;
use Intervention\Image\Facades\Image;
use App\Services\QuoteStockReservationService;

class QuoteSaleController extends Controller
{
    use NubefactTrait;

    /** @var InventoryCostService */
    private $inventoryCostService;

    public function __construct(InventoryCostService $inventoryCostService)
    {
        $this->inventoryCostService = $inventoryCostService;
    }

    public function index()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $registros = Quote::all();

        $arrayYears = $registros->pluck('date_quote')->map(function ($date) {
            return Carbon::parse($date)->format('Y');
        })->unique()->toArray();

        $arrayYears = array_values($arrayYears);

        $arrayCustomers = Customer::select('id', 'business_name')->get()->toArray();
        // created, send, confirm, raised, VB_finance, VB_operation, close, canceled
        $arrayStates = [
            ["value" => "created", "display" => "CREADAS"],
            ["value" => "send", "display" => "ENVIADAS"],
            ["value" => "confirm", "display" => "CONFIRMADAS"],
            ["value" => "raised", "display" => "ELEVADAS"],
            ["value" => "requote", "display" => "PARA RECOTIZAR"],
            ["value" => "close", "display" => "FINALIZADOS"],
            ["value" => "canceled", "display" => "ANULADAS"]
        ];

        $arrayUsers = User::select('id', 'name')->get()->toArray();

        return view('quoteSale.general', compact( 'permissions', 'arrayYears', 'arrayCustomers', 'arrayStates', 'arrayUsers'));

    }

    public function indexFacturadas()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $registros = Quote::all();

        $arrayYears = $registros->pluck('date_quote')->map(function ($date) {
            return Carbon::parse($date)->format('Y');
        })->unique()->toArray();

        $arrayYears = array_values($arrayYears);

        $arrayCustomers = Customer::select('id', 'business_name')->get()->toArray();
        // created, send, confirm, raised, VB_finance, VB_operation, close, canceled
        $arrayStates = [
            ["value" => "created", "display" => "CREADAS"],
            ["value" => "send", "display" => "ENVIADAS"],
            ["value" => "confirm", "display" => "CONFIRMADAS"],
            ["value" => "raised", "display" => "ELEVADAS"],
            /*["value" => "VB_operation", "display" => "VB OPERACIONES"],*/
            ["value" => "close", "display" => "FINALIZADOS"],
            ["value" => "canceled", "display" => "CANCELADAS"]
        ];

        $arrayUsers = User::select('id', 'name')->get()->toArray();

        return view('quoteSale.facturadas', compact( 'permissions', 'arrayYears', 'arrayCustomers', 'arrayStates', 'arrayUsers'));

    }

    public function getMaterialsO(Request $request)
    {
        $materials = [];

        if ($request->has('q')) {
            $search = $request->get('q');

            $materials = Material::where('enable_status', 1) // 👈 aquí filtras en la BD
            ->get()
                ->filter(function ($item) use ($search) {
                    return stripos($item->full_name, $search) !== false;
                });
        }

        return json_encode($materials);
    }

    public function getMaterials(Request $request)
    {
        $results = collect();

        if ($request->has('q')) {
            $search = trim($request->get('q'));

            $materials = Material::with([
                'unitMeasure',
                'stockItems' => function ($query) {
                    $query->where('is_active', true)
                        ->with([
                            'variant:id,material_id,attribute_summary,color_id',
                            'variant.talla:id,name,short_name',
                            'variant.color:id,name,short_name',
                            'unitMeasure:id,name',
                        ]);
                }
            ])
                ->where('enable_status', 1)
                ->get()
                ->filter(function ($material) use ($search) {
                    return stripos($material->full_name, $search) !== false;
                });

            foreach ($materials as $material) {
                // Si no tiene stock items, devolver opción legacy temporal
                if ($material->stockItems->isEmpty()) {
                    $results->push([
                        'id' => 'material_' . $material->id,
                        'type' => 'legacy_material',
                        'material_id' => $material->id,
                        'stock_item_id' => null,
                        'text' => $material->full_name,
                        'full_name' => $material->full_name,
                        'unit' => optional($material->unitMeasure)->name,
                        'code' => $material->code,
                        'stock_current' => $material->stock_current,
                    ]);
                    continue;
                }

                foreach ($material->stockItems as $stockItem) {
                    $variantText = $this->getVariantText($stockItem);

                    $text = $stockItem->display_name ?: $material->full_name;
                    if ($variantText) {
                        $text .= ' - ' . $variantText;
                    }

                    if (!empty($stockItem->sku)) {
                        $text .= ' | SKU: ' . $stockItem->sku;
                    }

                    $results->push([
                        'id' => $stockItem->id,
                        'type' => 'stock_item',
                        'material_id' => $material->id,
                        'stock_item_id' => $stockItem->id,
                        'text' => $text,
                        'full_name' => $material->full_name,
                        'display_name' => $stockItem->display_name,
                        'variant_text' => $variantText,
                        'unit' => optional($stockItem->unitMeasure)->name ?: optional($material->unitMeasure)->name,
                        'code' => $material->code,
                        'sku' => $stockItem->sku,
                        'barcode' => $stockItem->barcode,
                    ]);
                }
            }
        }

        return response()->json($results->values());
    }

    public function getMaterialTotalsO()
    {
        $materials = Material::with('unitMeasure','typeScrap')
            ->where('enable_status', 1)->get();

        $array = [];
        foreach ( $materials as $material )
        {
            array_push($array, [
                'id'=> $material->id,
                'full_description' => $material->full_name,
                'unit' => ($material->unitMeasure == null) ? "":$material->unitMeasure->name,
                'code' => $material->code,
                'type_scrap' => $material->typeScrap,
                'unit_measure' => $material->unitMeasure,
                'list_price' => $material->list_price,
                'enable_status' => $material->enable_status,
                'stock_current' => $material->stock_current,
                'state_update_price' => $material->state_update_price
            ]);
        }

        return $array;
    }

    public function getMaterialTotals()
    {
        $materials = Material::with([
            'unitMeasure',
            'typeScrap',
            'stockItems' => function ($query) {
                $query->where('is_active', true)
                    ->with([
                        'variant:id,material_id,attribute_summary,quality_id,color_id',
                        'variant.talla:id,name,short_name',
                        'variant.color:id,name,short_name',
                        'unitMeasure:id,name',
                        'inventoryLevels:id,stock_item_id,qty_on_hand,qty_reserved',
                        'priceListItems.priceList:id,is_default,is_active',
                    ]);
            }
        ])->where('enable_status', 1)->get();

        $array = [];

        foreach ($materials as $material) {

            // Legacy temporal: material sin stock items
            if ($material->stockItems->isEmpty()) {
                $array[] = [
                    'id' => 'material_' . $material->id,
                    'type' => 'legacy_material',
                    'material_id' => $material->id,
                    'stock_item_id' => null,
                    'full_description' => $material->full_name,
                    'display_name' => $material->full_name,
                    'variant_text' => '',
                    'unit' => optional($material->unitMeasure)->name ?: '',
                    'code' => $material->code,
                    'sku' => null,
                    'barcode' => null,
                    'type_scrap' => $material->typeScrap,
                    'tipo_venta_id' => $material->tipo_venta_id,
                    'unit_measure' => $material->unitMeasure,
                    'list_price' => $material->list_price,
                    'enable_status' => $material->enable_status,
                    'stock_current' => (float) ($material->stock_current ?? 0),
                    'stock_reserved' => 0,
                    'stock_available' => (float) ($material->stock_current ?? 0),
                    'state_update_price' => $material->state_update_price,
                ];
                continue;
            }

            foreach ($material->stockItems as $stockItem) {
                $variantText = '';

                if ($stockItem->variant) {
                    if (!empty($stockItem->variant->attribute_summary)) {
                        $variantText = $stockItem->variant->attribute_summary;
                    } else {
                        $quality = optional($stockItem->variant->quality)->short_name
                            ?: optional($stockItem->variant->quality)->name;

                        $color = optional($stockItem->variant->color)->name;

                        $variantText = collect([$quality, $color])
                            ->filter()
                            ->implode(' / ');
                    }
                }

                $fullDescription = $stockItem->display_name ?: $material->full_name;
                if ($variantText) {
                    $fullDescription .= ' - ' . $variantText;
                }

                $stockCurrent = (float) $stockItem->inventoryLevels->sum(function ($level) {
                    return (float) ($level->qty_on_hand ?? 0);
                });

                $stockReserved = (float) $stockItem->inventoryLevels->sum(function ($level) {
                    return (float) ($level->qty_reserved ?? 0);
                });

                $stockAvailable = $stockCurrent - $stockReserved;

                $priceListItem = $stockItem->priceListItems
                    ->first(function ($item) {
                        return $item->priceList
                            && $item->priceList->is_default
                            && $item->priceList->is_active;
                    });

                $listPrice = $priceListItem ? (float) $priceListItem->price : 0;

                $array[] = [
                    'id' => $stockItem->id,
                    'type' => 'stock_item',
                    'material_id' => $material->id,
                    'stock_item_id' => $stockItem->id,
                    'full_description' => $fullDescription,
                    'display_name' => $stockItem->display_name,
                    'variant_text' => $variantText,
                    'unit' => optional($stockItem->unitMeasure)->name ?: optional($material->unitMeasure)->name ?: '',
                    'code' => $material->code,
                    'sku' => $stockItem->sku,
                    'barcode' => $stockItem->barcode,
                    'type_scrap' => $material->typeScrap,
                    'tipo_venta_id' => $material->tipo_venta_id,
                    'unit_measure' => $stockItem->unitMeasure ?: $material->unitMeasure,
                    'list_price' => $listPrice,
                    'enable_status' => $material->enable_status,
                    'stock_current' => $stockCurrent,
                    'stock_reserved' => $stockReserved,
                    'stock_available' => $stockAvailable,
                    'state_update_price' => $material->state_update_price,
                ];
            }
        }

        return $array;
    }

    private function getVariantText($stockItem)
    {
        $variantText = '';

        if ($stockItem->variant) {
            if (!empty($stockItem->variant->attribute_summary)) {
                $variantText = $stockItem->variant->attribute_summary;
            } else {
                $talla = optional($stockItem->variant->quality)->short_name
                    ?: optional($stockItem->variant->quality)->name;

                $color = optional($stockItem->variant->color)->name;

                $variantText = collect([$talla, $color])
                    ->filter()
                    ->implode(' / ');
            }
        }

        return $variantText;
    }

    public function getAvailableItemsByStockItem($stockItemId)
    {
        $items = Item::query()
            ->with([
                'stockLot:id,lot_code',
                'warehouse:id,name',
            ])
            ->where('stock_item_id', $stockItemId)
            ->where('percentage', 1)
            ->where('state_item', 'entered')
            ->orderBy('code', 'asc')
            ->get([
                'id',
                'stock_item_id',
                'material_id',
                'stock_lot_id',
                'code',
                'warehouse_id',
                'location_id',
                'state_item',
            ]);

        return response()->json([
            'success' => true,
            'items' => $items->map(function ($item) {
                $lotCode = optional($item->stockLot)->lot_code;

                if (empty(trim((string) $lotCode))) {
                    $lotCode = 'Sin Lote';
                }

                $warehouseName = optional($item->warehouse)->name;

                if (empty(trim((string) $warehouseName))) {
                    $warehouseName = 'Sin almacén';
                }

                return [
                    'id' => $item->id,
                    'code' => $item->code ?: ('Ítem #' . $item->id),

                    'stock_item_id' => $item->stock_item_id,
                    'material_id' => $item->material_id,

                    'stock_lot_id' => $item->stock_lot_id,
                    'stock_lot_code' => $lotCode,
                    'lot_code' => $lotCode,

                    'warehouse_id' => $item->warehouse_id,
                    'warehouse_name' => $warehouseName,

                    'location_id' => $item->location_id,
                    'state_item' => $item->state_item,
                ];
            })->values(),
        ]);
    }

    public function create()
    {
        $begin = microtime(true);
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();
        $unitMeasures = UnitMeasure::all();
        $customers = Customer::all();
        $defaultConsumable = '(*)';
        $defaultElectric = '(e)';
        $consumables = Material::with('unitMeasure')->orderBy('full_name', 'asc')->get();
        $electrics = Material::with('unitMeasure')->where('category_id', 2)->whereElectric('description',$defaultElectric)->orderBy('full_name', 'asc')->get();
        $workforces = Workforce::with('unitMeasure')->get();
        $maxId = Quote::max('id')+1;
        $length = 5;
        $codeQuote = 'COT-'.str_pad($maxId,$length,"0", STR_PAD_LEFT);
        $paymentDeadlines = PaymentDeadline::where('type', 'quotes')->get();
        $utility = PorcentageQuote::where('name', 'utility')->first();
        $rent = PorcentageQuote::where('name', 'rent')->first();
        $letter = PorcentageQuote::where('name', 'letter')->first();

        //dd($array);

        $array = [];

        $dataCurrency = DataGeneral::where('name', 'type_current')->first();
        $currency = $dataCurrency->valueText;

        $dataIgv = PorcentageQuote::where('name', 'igv')->first();
        $igv = $dataIgv->value;

        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Crear cotizacion VISTA',
            'time' => $end
        ]);


        return view('quoteSale.create', compact('currency', 'customers', 'unitMeasures', 'consumables', 'electrics', 'workforces', 'codeQuote', 'permissions', 'paymentDeadlines', 'utility', 'rent', 'letter', 'array', 'igv'));
    }

    /*public function store(StoreQuoteRequest $request)
    {
        $begin = microtime(true);
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $maxCode = Quote::max('id');
            $maxId = $maxCode + 1;
            $length = 5;

            $quote = Quote::create([
                'code' => '',
                'description_quote' => $request->get('code_description'),
                'observations' => $request->get('observations'),
                'date_quote' => ($request->has('date_quote')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_quote')) : Carbon::now(),
                'date_validate' => ($request->has('date_validate')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_validate')) : Carbon::now()->addDays(5),
                'way_to_pay' => ($request->has('way_to_pay')) ? $request->get('way_to_pay') : '',
                'delivery_time' => ($request->has('delivery_time')) ? $request->get('delivery_time') : '',
                'customer_id' => ($request->has('customer_id')) ? $request->get('customer_id') : null,
                'contact_id' => ($request->has('contact_id')) ? $request->get('contact_id') : null,
                'payment_deadline_id' => ($request->has('payment_deadline')) ? $request->get('payment_deadline') : null,
                'state' => 'created',
                // Guardamos los totales
                'descuento' => ($request->has('descuento')) ? $request->get('descuento') : null,
                'gravada' => ($request->has('gravada')) ? $request->get('gravada') : null,
                'igv_total' => ($request->has('igv_total')) ? $request->get('igv_total') : null,
                'total_importe' => ($request->has('total_importe')) ? $request->get('total_importe') : null
            ]);

            $codeQuote = '';
            if ( $maxId < $quote->id ){
                $codeQuote = 'COT-'.str_pad($quote->id,$length,"0", STR_PAD_LEFT);
                $quote->code = $codeQuote;
                $quote->save();
            } else {
                $codeQuote = 'COT-'.str_pad($maxId,$length,"0", STR_PAD_LEFT);
                $quote->code = $codeQuote;
                $quote->save();
            }

            QuoteUser::create([
                'quote_id' => $quote->id,
                'user_id' => Auth::user()->id,
            ]);

            $equipments = json_decode($request->get('equipments'));

            $totalQuote = 0;

            for ( $i=0; $i<sizeof($equipments); $i++ )
            {
                $equipment = Equipment::create([
                    'quote_id' => $quote->id,
                    'description' =>($equipments[$i]->description == "" || $equipments[$i]->description == null) ? '':$equipments[$i]->description,
                    'detail' => ($equipments[$i]->detail == "" || $equipments[$i]->detail == null) ? '':$equipments[$i]->detail,
                    'quantity' => $equipments[$i]->quantity,
                    'utility' => $equipments[$i]->utility,
                    'rent' => $equipments[$i]->rent,
                    'letter' => $equipments[$i]->letter,
                    'total' => $equipments[$i]->total
                ]);

                $totalMaterial = 0;

                $totalConsumable = 0;

                $totalElectric = 0;

                $totalWorkforces = 0;

                $totalTornos = 0;

                $totalDias = 0;

                $materials = $equipments[$i]->materials;

                $consumables = $equipments[$i]->consumables;

                $electrics = $equipments[$i]->electrics;

                $workforces = $equipments[$i]->workforces;

                $tornos = $equipments[$i]->tornos;

                $dias = $equipments[$i]->dias;

                for ( $j=0; $j<sizeof($materials); $j++ )
                {
                    $equipmentMaterial = EquipmentMaterial::create([
                        'equipment_id' => $equipment->id,
                        'material_id' => $materials[$j]->material->id,
                        'quantity' => (float) $materials[$j]->quantity,
                        'price' => (float) $materials[$j]->material->unit_price,
                        'length' => (float) ($materials[$j]->length == '') ? 0: $materials[$j]->length,
                        'width' => (float) ($materials[$j]->width == '') ? 0: $materials[$j]->width,
                        'percentage' => (float) $materials[$j]->quantity,
                        'state' => ($materials[$j]->quantity > $materials[$j]->material->stock_current) ? 'Falta comprar':'En compra',
                        'availability' => ($materials[$j]->quantity > $materials[$j]->material->stock_current) ? 'Agotado':'Completo',
                        'total' => (float) $materials[$j]->quantity*(float) $materials[$j]->material->unit_price,
                    ]);

                    $totalMaterial += $equipmentMaterial->total;
                }

                for ($k = 0; $k < sizeof($consumables); $k++) {
                    $material = Material::find($consumables[$k]->id);

                    if (!$material) {
                        throw new \Exception("El material con ID {$consumables[$k]->id} no existe.");
                    }

                    $requestedQty = (float) $consumables[$k]->quantity;

                    // Stock disponible = lo que hay en almacén - lo que ya está reservado en otras cotizaciones
                    $available = (float) $material->stock_current - (float) $material->stock_reserved;

                    if ($requestedQty > $available) {
                        // No hay suficiente stock disponible para esta cotización
                        throw new \Exception("El material {$material->full_name} no cuenta con stock suficiente para la cantidad solicitada ({$requestedQty}). Stock disponible: {$available}.");
                    }

                    // 🟢 REGISTRAR EQUIPMENT CONSUMABLE
                    $equipmentConsumable = EquipmentConsumable::create([
                        'availability' => ((float) $consumables[$k]->quantity > $material->stock_current) ? 'Agotado' : 'Completo',
                        'state' => ((float) $consumables[$k]->quantity > $material->stock_current) ? 'Falta comprar' : 'En compra',
                        'equipment_id' => $equipment->id,
                        'material_id' => $consumables[$k]->id,
                        'quantity' => (float) $consumables[$k]->quantity,
                        'price' => (float) $consumables[$k]->price,
                        'valor_unitario' => (float) $consumables[$k]->valor,
                        'discount' => (float) $consumables[$k]->discount,
                        'total' => (float) $consumables[$k]->importe,
                        'type_promo' => $consumables[$k]->type_promo,
                    ]);

                    $totalConsumable += $equipmentConsumable->total;

                    // 🟢 REGISTRAR/ACTUALIZAR RESERVA POR COTIZACIÓN
                    // si ya había reserva de este material en esta misma cotización, la acumulamos
                    $reservation = QuoteMaterialReservation::where('quote_id', $quote->id)
                        ->where('material_id', $material->id)
                        ->lockForUpdate()
                        ->first();

                    if ($reservation) {
                        $reservation->quantity += $requestedQty;
                        $reservation->save();
                    } else {
                        $reservation = QuoteMaterialReservation::create([
                            'quote_id'   => $quote->id,
                            'material_id'=> $material->id,
                            'quantity'   => $requestedQty,
                        ]);
                    }

                    // 🟢 ACTUALIZAR EL STOCK RESERVADO DEL MATERIAL
                    $material->stock_reserved += $requestedQty;
                    $material->save();

                    // 🟢 VALIDAR PROMOCIÓN SI type_promo = limit
                    if ($consumables[$k]->type_promo == "limit") {
                        $promotion = PromotionLimit::where('material_id', $consumables[$k]->id)
                            ->whereDate('start_date', '<=', now())
                            ->whereDate('end_date', '>=', now())
                            ->first();

                        if ($promotion) {
                            // buscar uso
                            $query = PromotionUsage::where('promotion_limit_id', $promotion->id);

                            if ($promotion->applies_to == 'worker') {
                                $query->where('user_id', auth()->id());
                            }

                            $usage = $query->first();

                            if (!$usage) {
                                $usage = PromotionUsage::create([
                                    'promotion_limit_id' => $promotion->id,
                                    'user_id' => $promotion->applies_to == 'worker' ? auth()->id() : null,
                                    'used_quantity'        => (float) $consumables[$k]->quantity,
                                    'equipment_id'         => $equipment->id,
                                    'equipment_consumable_id' => $equipmentConsumable->id,
                                    'quote_id'             => $equipment->quote_id,
                                ]);
                            }

                            $requestedQty = (float) $consumables[$k]->quantity;
                            $remaining = $promotion->limit_quantity - $usage->used_quantity;

                            if ($remaining < $requestedQty) {
                                throw new \Exception("La promoción para {$material->full_name} ya no tiene suficiente cantidad disponible");
                            }

                            // actualizar consumo
                            //$usage->increment('used_quantity', $requestedQty);
                        }
                    }


                }

                for ( $k=0; $k<sizeof($electrics); $k++ )
                {
                    $equipmentElectric = EquipmentElectric::create([
                        'equipment_id' => $equipment->id,
                        'material_id' => $electrics[$k]->id,
                        'quantity' => (float) $electrics[$k]->quantity,
                        'price' => (float) $electrics[$k]->price,
                        'total' => (float) $electrics[$k]->total,
                    ]);

                    $totalElectric += $equipmentElectric->total;
                }

                for ( $w=0; $w<sizeof($workforces); $w++ )
                {
                    $equipmentWorkforce = EquipmentWorkforce::create([
                        'equipment_id' => $equipment->id,
                        'description' => $workforces[$w]->description,
                        'price' => (float) $workforces[$w]->price,
                        'quantity' => (float) $workforces[$w]->quantity,
                        'total' => (float) $workforces[$w]->total,
                        'unit' => $workforces[$w]->unit,
                    ]);

                    $totalWorkforces += $equipmentWorkforce->total;
                }

                for ( $r=0; $r<sizeof($tornos); $r++ )
                {
                    $equipmenttornos = EquipmentTurnstile::create([
                        'equipment_id' => $equipment->id,
                        'description' => $tornos[$r]->description,
                        'price' => (float) $tornos[$r]->price,
                        'quantity' => (float) $tornos[$r]->quantity,
                        'total' => (float) $tornos[$r]->total
                    ]);

                    $totalTornos += $equipmenttornos->total;
                }

                for ( $d=0; $d<sizeof($dias); $d++ )
                {
                    $equipmentdias = EquipmentWorkday::create([
                        'equipment_id' => $equipment->id,
                        'description' => $dias[$d]->description,
                        'quantityPerson' => (float) $dias[$d]->quantity,
                        'hoursPerPerson' => (float) $dias[$d]->hours,
                        'pricePerHour' => (float) $dias[$d]->price,
                        'total' => (float) $dias[$d]->total
                    ]);

                    $totalDias += $equipmentdias->total;
                }

                // TODO: Cambio el 16/01/2024
                $totalEquipo = (($totalMaterial + $totalConsumable + $totalElectric + $totalWorkforces + $totalTornos + $totalDias) * (float)$equipment->quantity);
                $totalEquipmentU = $totalEquipo*(($equipment->utility/100)+1);
                $totalEquipmentL = $totalEquipmentU*(($equipment->letter/100)+1);
                $totalEquipmentR = $totalEquipmentL*(($equipment->rent/100)+1);

                $totalQuote += $totalEquipmentR;

                $equipment->total = $totalEquipo;

                $equipment->save();
            }

            $quote->save();

            // Crear notificacion
            $notification = Notification::create([
                'content' => $quote->code.' creada por '.Auth::user()->name,
                'reason_for_creation' => 'create_quote',
                'user_id' => Auth::user()->id,
                'url_go' => route('quote.edit', $quote->id)
            ]);

            // Roles adecuados para recibir esta notificación admin, logistica
            $users = User::role(['admin', 'principal' , 'logistic'])->get();
            foreach ( $users as $user )
            {
                if ( $user->id != Auth::user()->id )
                {
                    foreach ( $user->roles as $role )
                    {
                        NotificationUser::create([
                            'notification_id' => $notification->id,
                            'role_id' => $role->id,
                            'user_id' => $user->id,
                            'read' => false,
                            'date_read' => null,
                            'date_delete' => null
                        ]);
                    }
                }
            }

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Guardar cotizacion POST',
                'time' => $end
            ]);
            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Cotización '.$codeQuote.' guardada con éxito.'], 200);

    }*/

    /**
     * Store Quote
     *
     * IMPORTANTE (según cambios recientes):
     * - Consumables pueden venir como:
     *   - Unitario: quantity = unidades, presentation_id = null, units_equivalent = quantity (opcional)
     *   - Presentación: quantity = packs, presentation_id != null, units_per_pack viene del front,
     *                   pero el backend recalcula units_per_pack desde BD y units_equivalent = packs * units_per_pack
     *
     * - Para mantener coherencia con reservas/stock:
     *   - Reservamos y descontamos stock_reserved en UNIDADES EQUIVALENTES (units_equivalent).
     *
     * - Workforces (servicios adicionales):
     *   - Puede venir billable (1/0). Si billable = 0, igual lo guardamos, pero en facturación se excluirá.
     */
    public function storeO(StoreQuoteRequest $request)
    {
        $begin = microtime(true);
        $validated = $request->validated();

        $dataCurrency = DataGeneral::where('name', 'type_current')->first();
        $currency = $dataCurrency->valueText;

        DB::beginTransaction();
        try {

            $maxCode = Quote::max('id');
            $maxId = $maxCode + 1;
            $length = 5;

            $quote = Quote::create([
                'code' => '',
                'description_quote' => $request->get('code_description'),
                'observations' => $request->get('observations'),

                'date_quote' => ($request->filled('date_quote'))
                    ? Carbon::createFromFormat('d/m/Y', $request->get('date_quote'))
                    : Carbon::now(),

                'date_validate' => ($request->filled('date_validate'))
                    ? Carbon::createFromFormat('d/m/Y', $request->get('date_validate'))
                    : Carbon::now()->addDays(5),

                'way_to_pay' => $request->get('way_to_pay', ''),
                'delivery_time' => $request->get('delivery_time', ''),

                'customer_id' => $request->get('customer_id'),
                'contact_id' => $request->get('contact_id'),
                'payment_deadline_id' => $request->get('payment_deadline'),

                'state' => 'created',
                'currency_invoice' => $currency,

                // Totales (front)
                //'descuento' => $request->get('descuento'),
                'descuento' => $request->get('descuentoReal'),
                'discount_type' => $request->get('discount_type'),                 // amount|percent|null
                'discount_input_mode' => $request->get('discount_input_mode'),     // with_igv|without_igv|null
                'discount_input_value' => $request->get('discount_input_value'),   // decimal|null

                /*'gravada' => $request->get('gravada'),
                'igv_total' => $request->get('igv_total'),
                'total_importe' => $request->get('total_importe'),*/
                'gravada' => $request->get('gravadaReal'),
                'igv_total' => $request->get('igvReal'),
                'total_importe' => $request->get('totalReal'),
            ]);

            // Código
            $codeQuote = ($maxId < $quote->id)
                ? 'COT-' . str_pad($quote->id, $length, "0", STR_PAD_LEFT)
                : 'COT-' . str_pad($maxId, $length, "0", STR_PAD_LEFT);

            $quote->code = $codeQuote;
            $quote->save();

            QuoteUser::create([
                'quote_id' => $quote->id,
                'user_id' => Auth::user()->id,
            ]);

            $equipments = json_decode($request->get('equipments')) ?? [];

            // IGV: si tienes una variable/config global, reemplaza
            $igvPct = 18.0;
            $igvFactor = 1 + ($igvPct / 100);

            for ($i = 0; $i < sizeof($equipments); $i++) {

                $equipment = Equipment::create([
                    'quote_id' => $quote->id,
                    'description' => ($equipments[$i]->description ?? '') ?: '',
                    'detail' => ($equipments[$i]->detail ?? '') ?: '',
                    'quantity' => $equipments[$i]->quantity ?? 1,
                    'utility' => $equipments[$i]->utility ?? 0,
                    'rent' => $equipments[$i]->rent ?? 0,
                    'letter' => $equipments[$i]->letter ?? 0,
                    'total' => $equipments[$i]->total ?? 0,
                ]);

                $totalMaterial = 0;
                $totalConsumable = 0;
                $totalElectric = 0;
                $totalWorkforces = 0;
                $totalTornos = 0;
                $totalDias = 0;

                $materials = $equipments[$i]->materials ?? [];
                $consumables = $equipments[$i]->consumables ?? [];
                $electrics = $equipments[$i]->electrics ?? [];
                $workforces = $equipments[$i]->workforces ?? [];
                $tornos = $equipments[$i]->tornos ?? [];
                $dias = $equipments[$i]->dias ?? [];

                // MATERIALS (sin cambios)
                for ($j = 0; $j < sizeof($materials); $j++) {
                    $equipmentMaterial = EquipmentMaterial::create([
                        'equipment_id' => $equipment->id,
                        'material_id' => $materials[$j]->material->id,
                        'quantity' => (float)$materials[$j]->quantity,
                        'price' => (float)$materials[$j]->material->unit_price,
                        'length' => (float)(($materials[$j]->length ?? '') == '' ? 0 : $materials[$j]->length),
                        'width' => (float)(($materials[$j]->width ?? '') == '' ? 0 : $materials[$j]->width),
                        'percentage' => (float)$materials[$j]->quantity,
                        'state' => ($materials[$j]->quantity > $materials[$j]->material->stock_current) ? 'Falta comprar' : 'En compra',
                        'availability' => ($materials[$j]->quantity > $materials[$j]->material->stock_current) ? 'Agotado' : 'Completo',
                        'total' => (float)$materials[$j]->quantity * (float)$materials[$j]->material->unit_price,
                    ]);

                    $totalMaterial += $equipmentMaterial->total;
                }

                // CONSUMABLES (con presentaciones)
                // Recomendado para precisión decimal (si tienes bcmath)
                @bcscale(10);

                $totalConsumable = '0';

                for ($k = 0; $k < sizeof($consumables); $k++) {

                    $c = $consumables[$k];

                    $materialId = (int)($c->id ?? 0);
                    $material = Material::find($materialId);

                    if (!$material) {
                        throw new \Exception("El material con ID {$materialId} no existe.");
                    }

                    // ============================
                    // 1) Inputs del front (reales)
                    // ============================
                    // quantity: packs si hay presentación, unidades si no hay presentación
                    $frontQty = (float)($c->quantity ?? 0);
                    if ($frontQty <= 0) {
                        throw new \Exception("Cantidad inválida para material {$material->full_name}.");
                    }

                    $presentationId = $c->presentation_id ?? null;

                    // ✅ Estos 3 son los que mandas y NO debemos recalcular:
                    $valorUnitarioReal = (string)($c->valorReal ?? $c->valor ?? '0'); // sin IGV (pack o unidad)
                    $priceReal         = (string)($c->priceReal ?? $c->price ?? '0'); // con IGV (pack o unidad)
                    $importeReal       = (string)($c->importe ?? '0');                // total con IGV (ya calculado)

                    if ((float)$valorUnitarioReal < 0 || (float)$priceReal < 0 || (float)$importeReal < 0) {
                        throw new \Exception("Valores inválidos (precio/valor/total) para {$material->full_name}.");
                    }

                    // ============================
                    // 2) Presentación SOLO para stock (unidades equivalentes)
                    // ============================
                    $packs = null;
                    $unitsPerPack = null;

                    if (!empty($presentationId)) {

                        $packs = (int)$frontQty;
                        if ($packs < 1) {
                            throw new \Exception("Cantidad de paquetes inválida para material {$material->full_name}.");
                        }

                        $presentation = MaterialPresentation::where('id', $presentationId)
                            ->where('material_id', $materialId)
                            ->where('active', 1)
                            ->first();

                        if (!$presentation) {
                            throw new \Exception("La presentación seleccionada no es válida o no pertenece al material {$material->full_name}.");
                        }

                        $unitsPerPack = (int)$presentation->quantity;
                        if ($unitsPerPack < 1) {
                            throw new \Exception("La presentación tiene cantidad inválida para {$material->full_name}.");
                        }

                        $requestedUnits = (float)($packs * $unitsPerPack);

                    } else {
                        // Unitario: units_equivalent debe ser unidades (si no viene, usa frontQty)
                        $requestedUnits = (float)($c->units_equivalent ?? $frontQty);

                        if ($requestedUnits <= 0) {
                            throw new \Exception("Cantidad inválida para material {$material->full_name}.");
                        }
                    }

                    // ============================
                    // 3) Stock disponible
                    // ============================
                    $available = (float)$material->stock_current - (float)$material->stock_reserved;

                    if ($requestedUnits > $available) {
                        throw new \Exception(
                            "El material {$material->full_name} no cuenta con stock suficiente. " .
                            "Requerido: {$requestedUnits}. Disponible: {$available}."
                        );
                    }

                    $availability = ($requestedUnits > (float)$material->stock_current) ? 'Agotado' : 'Completo';
                    $state        = ($requestedUnits > (float)$material->stock_current) ? 'Falta comprar' : 'En compra';

                    // ============================
                    // 4) Guardar consumable (decimales reales)
                    // ============================
                    $equipmentConsumable = EquipmentConsumable::create([
                        'availability' => $availability,
                        'state' => $state,
                        'equipment_id' => $equipment->id,
                        'material_id' => $materialId,

                        // ✅ SIEMPRE stock en unidades equivalentes
                        'quantity' => $requestedUnits,

                        // ✅ Guardar EXACTO lo real que viene del front (decimal 20,10)
                        'price' => $priceReal,                    // con IGV (pack o unidad)
                        'valor_unitario' => $valorUnitarioReal,   // sin IGV (pack o unidad)
                        'total' => $importeReal,                  // total con IGV (ya calculado)

                        'discount' => (string)($c->discount ?? '0'),
                        'type_promo' => $c->type_promo ?? null,

                        'material_presentation_id' => $presentationId,
                        'packs' => $packs,
                        'units_per_pack' => $unitsPerPack,
                    ]);

                    // ============================
                    // 5) Total consumables con precisión
                    // ============================
                    // Ideal: sumar el mismo string que guardas (importeReal)
                    $totalConsumable = bcadd($totalConsumable, $importeReal, 10);

                    // ============================
                    // 6) Reservas (unidades equivalentes)
                    // ============================
                    $reservation = QuoteMaterialReservation::where('quote_id', $quote->id)
                        ->where('material_id', $materialId)
                        ->lockForUpdate()
                        ->first();

                    if ($reservation) {
                        $reservation->quantity += $requestedUnits;
                        $reservation->save();
                    } else {
                        QuoteMaterialReservation::create([
                            'quote_id' => $quote->id,
                            'material_id' => $materialId,
                            'quantity' => $requestedUnits,
                        ]);
                    }

                    $material->stock_reserved += $requestedUnits;
                    $material->save();

                    // ============================
                    // 7) Promo limit (valida y registra uso) - usa unidades equivalentes
                    // ============================
                    if (($c->type_promo ?? null) === "limit") {

                        $promotion = PromotionLimit::where('material_id', $materialId)
                            ->whereDate('start_date', '<=', now())
                            ->whereDate('end_date', '>=', now())
                            ->first();

                        if ($promotion) {
                            $query = PromotionUsage::where('promotion_limit_id', $promotion->id);

                            if ($promotion->applies_to === 'worker') {
                                $query->where('user_id', auth()->id());
                            }

                            $usage = $query->first();

                            // ✅ Validar antes de sumar
                            $alreadyUsed = $usage ? (float)$usage->used_quantity : 0.0;
                            $remaining = (float)$promotion->limit_quantity - $alreadyUsed;

                            if ($remaining < $requestedUnits) {
                                throw new \Exception("La promoción para {$material->full_name} ya no tiene suficiente cantidad disponible");
                            }

                            if (!$usage) {
                                PromotionUsage::create([
                                    'promotion_limit_id' => $promotion->id,
                                    'user_id' => $promotion->applies_to === 'worker' ? auth()->id() : null,
                                    'used_quantity' => $requestedUnits,
                                    'equipment_id' => $equipment->id,
                                    'equipment_consumable_id' => $equipmentConsumable->id,
                                    'quote_id' => $equipment->quote_id,
                                ]);
                            } else {
                                $usage->used_quantity += $requestedUnits;
                                $usage->equipment_id = $equipment->id;
                                $usage->equipment_consumable_id = $equipmentConsumable->id;
                                $usage->quote_id = $equipment->quote_id;
                                $usage->save();
                            }
                        }
                    }
                }

                // WORKFORCES (servicios adicionales)
                // - Guarda billable
                // - SIEMPRE suma al total del equipo/cotización
                for ($w = 0; $w < sizeof($workforces); $w++) {

                    $billable = isset($workforces[$w]->billable)
                        ? (int)$workforces[$w]->billable
                        : 1;

                    $equipmentWorkforce = EquipmentWorkforce::create([
                        'equipment_id' => $equipment->id,
                        'description' => $workforces[$w]->description ?? '',
                        'price' => (float)($workforces[$w]->price ?? 0),
                        'quantity' => (float)($workforces[$w]->quantity ?? 0),
                        'total' => (float)($workforces[$w]->importe ?? ($workforces[$w]->total ?? 0)),
                        'unit' => $workforces[$w]->unit ?? '',
                        'billable' => $billable,
                    ]);

                    $totalWorkforces += $equipmentWorkforce->total;
                }

                // Totales del equipo (sin cambios)
                $totalEquipo = (($totalMaterial + $totalConsumable + $totalElectric + $totalWorkforces + $totalTornos + $totalDias) * (float)$equipment->quantity);
                $totalEquipmentU = $totalEquipo * ((($equipment->utility / 100) + 1));
                $totalEquipmentL = $totalEquipmentU * ((($equipment->letter / 100) + 1));
                $totalEquipmentR = $totalEquipmentL * ((($equipment->rent / 100) + 1));

                //$totalQuote += $totalEquipmentR;

                $equipment->total = $totalEquipo;
                $equipment->save();
            }

            $quote->save();

            $notification = Notification::create([
                'content' => $quote->code . ' creada por ' . Auth::user()->name,
                'reason_for_creation' => 'create_quote',
                'user_id' => Auth::user()->id,
                'url_go' => route('quote.edit', $quote->id)
            ]);

            $users = User::role(['admin', 'principal', 'logistic'])->get();
            foreach ($users as $user) {
                if ($user->id != Auth::user()->id) {
                    foreach ($user->roles as $role) {
                        NotificationUser::create([
                            'notification_id' => $notification->id,
                            'role_id' => $role->id,
                            'user_id' => $user->id,
                            'read' => false,
                            'date_read' => null,
                            'date_delete' => null
                        ]);
                    }
                }
            }

            $end = microtime(true) - $begin;
            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Guardar cotizacion POST',
                'time' => $end
            ]);

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 422);
        }

        return response()->json(['message' => 'Cotización ' . $codeQuote . ' guardada con éxito.'], 200);
    }

    public function store(StoreQuoteRequest $request)
    {
        $begin = microtime(true);
        $validated = $request->validated();

        $dataCurrency = DataGeneral::where('name', 'type_current')->first();
        $currency = $dataCurrency->valueText;

        DB::beginTransaction();

        try {
            $maxCode = Quote::max('id');
            $maxId = $maxCode + 1;
            $length = 5;

            $quote = Quote::create([
                'code' => '',
                'description_quote' => $request->get('code_description'),
                'observations' => $request->get('observations'),

                'date_quote' => ($request->filled('date_quote'))
                    ? Carbon::createFromFormat('d/m/Y', $request->get('date_quote'))
                    : Carbon::now(),

                'date_validate' => ($request->filled('date_validate'))
                    ? Carbon::createFromFormat('d/m/Y', $request->get('date_validate'))
                    : Carbon::now()->addDays(5),

                'way_to_pay' => $request->get('way_to_pay', ''),
                'delivery_time' => $request->get('delivery_time', ''),

                'customer_id' => $request->get('customer_id'),
                'contact_id' => $request->get('contact_id'),
                'payment_deadline_id' => $request->get('payment_deadline'),

                'state' => 'created',
                'currency_invoice' => $currency,

                'descuento' => $request->get('descuentoReal'),
                'discount_type' => $request->get('discount_type'),
                'discount_input_mode' => $request->get('discount_input_mode'),
                'discount_input_value' => $request->get('discount_input_value'),

                'gravada' => $request->get('gravadaReal'),
                'igv_total' => $request->get('igvReal'),
                'total_importe' => $request->get('totalReal'),
            ]);

            // Código
            $codeQuote = ($maxId < $quote->id)
                ? 'COT-' . str_pad($quote->id, $length, "0", STR_PAD_LEFT)
                : 'COT-' . str_pad($maxId, $length, "0", STR_PAD_LEFT);

            $quote->code = $codeQuote;
            $quote->save();

            QuoteUser::create([
                'quote_id' => $quote->id,
                'user_id' => Auth::user()->id,
            ]);

            $equipments = json_decode($request->get('equipments')) ?? [];

            // IGV: si tienes una variable/config global, reemplaza
            $igvPct = 18.0;
            $igvFactor = 1 + ($igvPct / 100);

            for ($i = 0; $i < sizeof($equipments); $i++) {
                $equipment = Equipment::create([
                    'quote_id' => $quote->id,
                    'description' => ($equipments[$i]->description ?? '') ?: '',
                    'detail' => ($equipments[$i]->detail ?? '') ?: '',
                    'quantity' => $equipments[$i]->quantity ?? 1,
                    'utility' => $equipments[$i]->utility ?? 0,
                    'rent' => $equipments[$i]->rent ?? 0,
                    'letter' => $equipments[$i]->letter ?? 0,
                    'total' => $equipments[$i]->total ?? 0,
                ]);

                $totalMaterial = 0;
                $totalConsumable = 0;
                $totalElectric = 0;
                $totalWorkforces = 0;
                $totalTornos = 0;
                $totalDias = 0;

                $materials = $equipments[$i]->materials ?? [];
                $consumables = $equipments[$i]->consumables ?? [];
                $electrics = $equipments[$i]->electrics ?? [];
                $workforces = $equipments[$i]->workforces ?? [];
                $tornos = $equipments[$i]->tornos ?? [];
                $dias = $equipments[$i]->dias ?? [];

                // =====================================================
                // MATERIALS
                // =====================================================
                for ($j = 0; $j < sizeof($materials); $j++) {
                    $equipmentMaterial = EquipmentMaterial::create([
                        'equipment_id' => $equipment->id,
                        'material_id' => $materials[$j]->material->id,
                        'quantity' => (float) $materials[$j]->quantity,
                        'price' => (float) $materials[$j]->material->unit_price,
                        'length' => (float) (($materials[$j]->length ?? '') == '' ? 0 : $materials[$j]->length),
                        'width' => (float) (($materials[$j]->width ?? '') == '' ? 0 : $materials[$j]->width),
                        'percentage' => (float) $materials[$j]->quantity,
                        'state' => ($materials[$j]->quantity > $materials[$j]->material->stock_current)
                            ? 'Falta comprar'
                            : 'En compra',
                        'availability' => ($materials[$j]->quantity > $materials[$j]->material->stock_current)
                            ? 'Agotado'
                            : 'Completo',
                        'total' => (float) $materials[$j]->quantity *
                            (float) $materials[$j]->material->unit_price,
                    ]);

                    $totalMaterial += $equipmentMaterial->total;
                }

                // =====================================================
                // CONSUMABLES
                // =====================================================
                @bcscale(10);

                $totalConsumable = '0';

                /** @var QuoteStockReservationService $reservationService */
                $reservationService = app(QuoteStockReservationService::class);

                for ($k = 0; $k < sizeof($consumables); $k++) {
                    $c = $consumables[$k];

                    $stockItemId = (int) ($c->id ?? 0);

                    $stockItem = StockItem::with(['material'])->find($stockItemId);

                    if (!$stockItem) {
                        throw new \Exception("El producto con ID {$stockItemId} no existe.");
                    }

                    if (!$stockItem->material) {
                        throw new \Exception("El producto con ID {$stockItemId} no tiene material asociado.");
                    }

                    /*
                     * La validación importante siempre se toma del backend.
                     * No se confía en is_itemeable enviado desde JavaScript.
                     */
                    $isItemeable = (int) ($stockItem->material->tipo_venta_id ?? 0) === 3;

                    // ============================
                    // 1) Inputs del front
                    // ============================
                    $frontQty = (float) ($c->quantity ?? 0);

                    if ($frontQty <= 0) {
                        throw new \Exception(
                            "Cantidad inválida para material {$stockItem->display_name}."
                        );
                    }

                    $presentationId = $c->presentation_id ?? null;

                    // Valores reales enviados desde frontend
                    $valorUnitarioReal = (string) ($c->valorReal ?? $c->valor ?? '0');
                    $priceReal = (string) ($c->priceReal ?? $c->price ?? '0');
                    $importeReal = (string) ($c->importe ?? '0');

                    if (
                        (float) $valorUnitarioReal < 0 ||
                        (float) $priceReal < 0 ||
                        (float) $importeReal < 0
                    ) {
                        throw new \Exception(
                            "Valores inválidos (precio/valor/total) para {$stockItem->display_name}."
                        );
                    }

                    // ============================
                    // 2) Presentación / unidades reales
                    // ============================
                    $packs = null;
                    $unitsPerPack = null;

                    if (!empty($presentationId)) {
                        $packs = (int) $frontQty;

                        if ($packs < 1) {
                            throw new \Exception(
                                "Cantidad de paquetes inválida para material {$stockItem->display_name}."
                            );
                        }

                        $presentation = MaterialPresentation::where('id', $presentationId)
                            ->where('material_id', $stockItem->material->id)
                            ->where('active', 1)
                            ->first();

                        if (!$presentation) {
                            throw new \Exception(
                                "La presentación seleccionada no es válida o no pertenece al material {$stockItem->display_name}."
                            );
                        }

                        $unitsPerPack = (int) $presentation->quantity;

                        if ($unitsPerPack < 1) {
                            throw new \Exception(
                                "La presentación tiene cantidad inválida para {$stockItem->display_name}."
                            );
                        }

                        $requestedUnits = (float) ($packs * $unitsPerPack);
                    } else {
                        $requestedUnits = (float) ($c->units_equivalent ?? $frontQty);

                        if ($requestedUnits <= 0) {
                            throw new \Exception(
                                "Cantidad inválida para material {$stockItem->display_name}."
                            );
                        }
                    }

                    // ============================
                    // 3) Datos de ítems itemeables
                    // ============================
                    $selectedItemIds = [];

                    if ($isItemeable) {
                        $rawSelectedItemIds = $c->selected_item_ids ?? [];

                        if (is_string($rawSelectedItemIds)) {
                            $decodedItemIds = json_decode($rawSelectedItemIds, true);
                            $rawSelectedItemIds = is_array($decodedItemIds)
                                ? $decodedItemIds
                                : [];
                        }

                        if (!is_array($rawSelectedItemIds)) {
                            $rawSelectedItemIds = [];
                        }

                        $selectedItemIds = collect($rawSelectedItemIds)
                            ->map(function ($itemId) {
                                return (int) $itemId;
                            })
                            ->filter(function ($itemId) {
                                return $itemId > 0;
                            })
                            ->values()
                            ->toArray();

                        if (empty($selectedItemIds)) {
                            throw new \Exception(
                                "Debe seleccionar los ítems físicos para {$stockItem->material->full_name}."
                            );
                        }

                        if (count($selectedItemIds) !== count(array_unique($selectedItemIds))) {
                            throw new \Exception(
                                "Se detectaron ítems repetidos en {$stockItem->material->full_name}."
                            );
                        }

                        /*
                         * Como los Items representan unidades completas,
                         * la cantidad reservada debe ser exactamente igual
                         * a los IDs seleccionados.
                         */
                        if ((int) $requestedUnits !== count($selectedItemIds)) {
                            throw new \Exception(
                                "La cantidad de ítems seleccionados no coincide con la cantidad requerida para {$stockItem->material->full_name}. " .
                                "Requerido: {$requestedUnits}. Seleccionados: " . count($selectedItemIds) . '.'
                            );
                        }
                    }

                    // ============================
                    // 4) Stock disponible por lote
                    // ============================
                    $available = $reservationService->getAvailableStockByStockItem($stockItem->id);

                    if ($requestedUnits > $available) {
                        throw new \Exception(
                            "El material {$stockItem->material->full_name} no cuenta con stock suficiente. " .
                            "Requerido: {$requestedUnits}. Disponible: {$available}."
                        );
                    }

                    $availability = ($requestedUnits > $available) ? 'Agotado' : 'Completo';
                    $state = ($requestedUnits > $available) ? 'Falta comprar' : 'En compra';

                    // ============================
                    // 5) Guardar EquipmentConsumable
                    // ============================
                    $equipmentConsumable = EquipmentConsumable::create([
                        'availability' => $availability,
                        'state' => $state,
                        'equipment_id' => $equipment->id,
                        'material_id' => $stockItem->material->id,
                        'stock_item_id' => $stockItem->id,

                        // Stock siempre en unidades equivalentes
                        'quantity' => $requestedUnits,

                        // Valores comerciales del frontend
                        'price' => $priceReal,
                        'valor_unitario' => $valorUnitarioReal,
                        'total' => $importeReal,

                        'discount' => (string) ($c->discount ?? '0'),
                        'type_promo' => $c->type_promo ?? null,

                        'material_presentation_id' => $presentationId,
                        'packs' => $packs,
                        'units_per_pack' => $unitsPerPack,
                    ]);

                    // ============================
                    // 6) Total consumables
                    // ============================
                    $totalConsumable = bcadd(
                        $totalConsumable,
                        $importeReal,
                        10
                    );

                    // ============================
                    // 7) Reservar stock
                    // ============================
                    if ($isItemeable) {
                        /*
                         * quote_detail_id será el ID del EquipmentConsumable.
                         * Este método:
                         * - valida los IDs
                         * - bloquea Items y StockLots
                         * - cambia Items de entered a reserved
                         * - crea QuoteStockLot con item_ids
                         */
                        $reservationService->reserveItemeableForQuoteDetail(
                            (int) $quote->id,
                            (int) $equipmentConsumable->id,
                            (int) $stockItem->id,
                            $selectedItemIds
                        );
                    } else {
                        /*
                         * Flujo existente para productos normales:
                         * el sistema toma stock por lotes según disponibilidad.
                         */
                        $reservationService->reserveForQuoteDetail(
                            (int) $quote->id,
                            (int) $equipmentConsumable->id,
                            (int) $stockItem->id,
                            (float) $requestedUnits
                        );
                    }

                    // ============================
                    // 8) Promo limit
                    // ============================
                    if (($c->type_promo ?? null) === "limit") {
                        $promotion = PromotionLimit::where('material_id', $stockItem->material->id)
                            ->whereDate('start_date', '<=', now())
                            ->whereDate('end_date', '>=', now())
                            ->first();

                        if ($promotion) {
                            $query = PromotionUsage::where(
                                'promotion_limit_id',
                                $promotion->id
                            );

                            if ($promotion->applies_to === 'worker') {
                                $query->where('user_id', auth()->id());
                            }

                            $usage = $query->first();

                            $alreadyUsed = $usage
                                ? (float) $usage->used_quantity
                                : 0.0;

                            $remaining = (float) $promotion->limit_quantity - $alreadyUsed;

                            if ($remaining < $requestedUnits) {
                                throw new \Exception(
                                    "La promoción para {$stockItem->material->full_name} ya no tiene suficiente cantidad disponible"
                                );
                            }

                            if (!$usage) {
                                PromotionUsage::create([
                                    'promotion_limit_id' => $promotion->id,
                                    'user_id' => $promotion->applies_to === 'worker'
                                        ? auth()->id()
                                        : null,
                                    'used_quantity' => $requestedUnits,
                                    'equipment_id' => $equipment->id,
                                    'equipment_consumable_id' => $equipmentConsumable->id,
                                    'quote_id' => $equipment->quote_id,
                                ]);
                            } else {
                                $usage->used_quantity += $requestedUnits;
                                $usage->equipment_id = $equipment->id;
                                $usage->equipment_consumable_id = $equipmentConsumable->id;
                                $usage->quote_id = $equipment->quote_id;
                                $usage->save();
                            }
                        }
                    }
                }

                // =====================================================
                // WORKFORCES
                // =====================================================
                for ($w = 0; $w < sizeof($workforces); $w++) {
                    $billable = isset($workforces[$w]->billable)
                        ? (int) $workforces[$w]->billable
                        : 1;

                    $equipmentWorkforce = EquipmentWorkforce::create([
                        'equipment_id' => $equipment->id,
                        'description' => $workforces[$w]->description ?? '',
                        'price' => (float) ($workforces[$w]->price ?? 0),
                        'quantity' => (float) ($workforces[$w]->quantity ?? 0),
                        'total' => (float) (
                            $workforces[$w]->importe ??
                            ($workforces[$w]->total ?? 0)
                        ),
                        'unit' => $workforces[$w]->unit ?? '',
                        'billable' => $billable,
                    ]);

                    $totalWorkforces += $equipmentWorkforce->total;
                }

                // =====================================================
                // Totales del equipo
                // =====================================================
                $totalEquipo = (
                    (
                        $totalMaterial +
                        $totalConsumable +
                        $totalElectric +
                        $totalWorkforces +
                        $totalTornos +
                        $totalDias
                    ) * (float) $equipment->quantity
                );

                $totalEquipmentU = $totalEquipo * ((($equipment->utility / 100) + 1));
                $totalEquipmentL = $totalEquipmentU * ((($equipment->letter / 100) + 1));
                $totalEquipmentR = $totalEquipmentL * ((($equipment->rent / 100) + 1));

                $equipment->total = $totalEquipo;
                $equipment->save();
            }

            $quote->save();

            $notification = Notification::create([
                'content' => $quote->code . ' creada por ' . Auth::user()->name,
                'reason_for_creation' => 'create_quote',
                'user_id' => Auth::user()->id,
                'url_go' => route('quote.edit', $quote->id)
            ]);

            $users = User::role(['admin', 'principal', 'logistic'])->get();

            foreach ($users as $user) {
                if ($user->id != Auth::user()->id) {
                    foreach ($user->roles as $role) {
                        NotificationUser::create([
                            'notification_id' => $notification->id,
                            'role_id' => $role->id,
                            'user_id' => $user->id,
                            'read' => false,
                            'date_read' => null,
                            'date_delete' => null
                        ]);
                    }
                }
            }

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Guardar cotizacion POST',
                'time' => $end
            ]);

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 422);
        }

        return response()->json([
            'message' => 'Cotización ' . $codeQuote . ' guardada con éxito.'
        ], 200);
    }

    public function renewQuote(Quote $quote)
    {
        try {
            $renewQuote = DB::transaction(function () use ($quote) {
                $begin = microtime(true);
                /*
                 * ==========================================================
                 * 1. BLOQUEAR Y OBTENER LA COTIZACIÓN ORIGINAL
                 * ==========================================================
                 */
                $originalQuote = Quote::query()
                    ->where('id', $quote->id)
                    ->with([
                        'equipments.consumables.material',
                        'equipments.consumables.stockItem',
                        'equipments.workforces',
                    ])
                    ->lockForUpdate()
                    ->firstOrFail();

                /*
                 * Solamente una cotización marcada para recotizar
                 * puede utilizar esta operación.
                 */
                if ($originalQuote->state !== 'requote') {
                    throw new \Exception(
                        'La cotización seleccionada no está disponible para recotización.'
                    );
                }

                /*
                 * Una cotización original solamente puede generar
                 * una nueva recotización.
                 */
                $alreadyRenewed = Quote::query()
                    ->where('renewed_from_quote_id', $originalQuote->id)
                    ->exists();

                if ($alreadyRenewed) {
                    throw new \Exception(
                        'Esta cotización ya fue recotizada anteriormente.'
                    );
                }

                /*
                 * ==========================================================
                 * 2. VALIDAR QUE SOLAMENTE EXISTA UN EQUIPO
                 * ==========================================================
                 */
                if ($originalQuote->equipments->isEmpty()) {
                    throw new \Exception(
                        'La cotización no contiene productos para recotizar.'
                    );
                }

                if ($originalQuote->equipments->count() > 1) {
                    throw new \Exception(
                        'La cotización contiene más de un grupo de productos y no puede recotizarse automáticamente.'
                    );
                }

                /*
                 * ==========================================================
                 * 3. VALIDAR QUE NO EXISTAN PRODUCTOS ITEMEABLES
                 * ==========================================================
                 */
                foreach ($originalQuote->equipments as $equipment) {
                    foreach ($equipment->consumables as $consumable) {

                        if (!$consumable->material) {
                            throw new \Exception(
                                'Uno de los productos no tiene un material asociado.'
                            );
                        }

                        if ((int) $consumable->material->tipo_venta_id === 3) {
                            throw new \Exception(
                                'La cotización contiene productos con ítems físicos y no puede recotizarse automáticamente.'
                            );
                        }

                        if (empty($consumable->stock_item_id)) {
                            throw new \Exception(
                                'El producto "' .
                                $consumable->material->full_name .
                                '" no tiene un StockItem asociado y no puede recotizarse automáticamente.'
                            );
                        }
                    }
                }

                /*
                 * ==========================================================
                 * 4. CONFIGURACIÓN DE IGV
                 * ==========================================================
                 */
                $dataIgv = DataGeneral::where('name', 'igv_venta')->first();

                if (!$dataIgv) {
                    throw new \Exception(
                        'No se encontró la configuración del IGV de venta.'
                    );
                }

                $igvPct = (float) $dataIgv->valueNumber;
                $factor = 1 + ($igvPct / 100);

                /*
                 * Redondeo comercial equivalente a moneyRound().
                 */
                $moneyRound = function ($value) {
                    return round((float) $value, 2);
                };

                /*
                 * Redondeo utilizado para valores reales.
                 */
                $round10 = function ($value) {
                    return round((float) $value, 10);
                };

                /*
                 * ==========================================================
                 * 5. CREAR LA NUEVA COTIZACIÓN
                 * ==========================================================
                 */
                $renewQuote = Quote::create([
                    /*
                     * Código temporal para evitar conflictos con el índice unique.
                     */
                    'code' => 'TMP-' . Str::uuid(),

                    'renewed_from_quote_id' => $originalQuote->id,

                    'description_quote' => $originalQuote->description_quote,
                    'observations' => $originalQuote->observations,

                    'date_quote' => Carbon::now(),
                    'date_validate' => Carbon::now()->addDays(5),

                    'way_to_pay' => $originalQuote->way_to_pay,
                    'delivery_time' => $originalQuote->delivery_time,

                    'customer_id' => $originalQuote->customer_id,
                    'contact_id' => $originalQuote->contact_id,
                    'payment_deadline_id' => $originalQuote->payment_deadline_id,

                    'state' => 'created',
                    'currency_invoice' => $originalQuote->currency_invoice,

                    /*
                     * Se conserva la configuración del descuento.
                     * Los importes serán recalculados posteriormente.
                     */
                    'discount_type' => $originalQuote->discount_type,
                    'discount_input_mode' => $originalQuote->discount_input_mode,
                    'discount_input_value' => $originalQuote->discount_input_value,

                    'descuento' => 0,
                    'gravada' => 0,
                    'igv_total' => 0,
                    'total_importe' => 0,
                ]);

                /*
                 * Código definitivo.
                 */
                $renewQuote->code = 'COT-' . str_pad(
                        $renewQuote->id,
                        5,
                        '0',
                        STR_PAD_LEFT
                    );

                $renewQuote->save();

                QuoteUser::create([
                    'quote_id' => $renewQuote->id,
                    'user_id' => Auth::id(),
                ]);

                /** @var QuoteStockReservationService $reservationService */
                $reservationService = app(QuoteStockReservationService::class);

                /*
                 * Totales que reproducen el comportamiento del frontend.
                 */
                $subtotalConsumablesWithIgvReal = 0;
                $discountPromotions = 0;
                $servicesSumBillable = 0;

                /*
                 * ==========================================================
                 * 6. COPIAR EQUIPOS
                 * ==========================================================
                 */
                foreach ($originalQuote->equipments as $originalEquipment) {

                    $renewEquipment = Equipment::create([
                        'quote_id' => $renewQuote->id,
                        'description' => $originalEquipment->description ?: '',
                        'detail' => $originalEquipment->detail ?: '',
                        'quantity' => $originalEquipment->quantity ?: 1,
                        'utility' => $originalEquipment->utility ?: 0,
                        'rent' => $originalEquipment->rent ?: 0,
                        'letter' => $originalEquipment->letter ?: 0,
                        'total' => 0,
                    ]);

                    $totalConsumablesForEquipment = 0;
                    $totalWorkforcesForEquipment = 0;

                    /*
                     * ======================================================
                     * 6.1. CONSUMIBLES
                     * ======================================================
                     */
                    foreach ($originalEquipment->consumables as $originalConsumable) {

                        $stockItem = StockItem::query()
                            ->with([
                                'material',
                            ])
                            ->where('id', $originalConsumable->stock_item_id)
                            ->where('is_active', true)
                            ->first();

                        if (!$stockItem) {
                            throw new \Exception(
                                'Uno de los productos ya no se encuentra activo o disponible.'
                            );
                        }

                        if (!$stockItem->material) {
                            throw new \Exception(
                                'El producto con ID ' .
                                $originalConsumable->stock_item_id .
                                ' no tiene material asociado.'
                            );
                        }

                        if ((int) $stockItem->material->tipo_venta_id === 3) {
                            throw new \Exception(
                                'El producto "' .
                                $stockItem->material->full_name .
                                '" requiere selección de ítems físicos y no puede recotizarse.'
                            );
                        }

                        /*
                         * No modificamos la cantidad solicitada originalmente.
                         */
                        $requestedUnits = (float) $originalConsumable->quantity;

                        if ($requestedUnits <= 0) {
                            throw new \Exception(
                                'El producto "' .
                                $stockItem->material->full_name .
                                '" tiene una cantidad inválida.'
                            );
                        }

                        $presentationId = $originalConsumable
                            ->material_presentation_id;

                        $packs = $originalConsumable->packs;
                        $unitsPerPack = $originalConsumable->units_per_pack;

                        $currentPrice = 0;
                        $lineQuantityForPrice = $requestedUnits;

                        /*
                         * ==================================================
                         * PRODUCTO CON PRESENTACIÓN
                         * ==================================================
                         */
                        if (!empty($presentationId)) {

                            $presentation = MaterialPresentation::query()
                                ->where('id', $presentationId)
                                ->where(
                                    'material_id',
                                    $stockItem->material_id
                                )
                                ->where('active', 1)
                                ->first();

                            if (!$presentation) {
                                throw new \Exception(
                                    'La presentación del producto "' .
                                    $stockItem->material->full_name .
                                    '" ya no se encuentra activa.'
                                );
                            }

                            if (
                                empty($packs) ||
                                (int) $packs <= 0 ||
                                empty($unitsPerPack) ||
                                (int) $unitsPerPack <= 0
                            ) {
                                throw new \Exception(
                                    'La presentación del producto "' .
                                    $stockItem->material->full_name .
                                    '" contiene información inválida.'
                                );
                            }

                            /*
                             * Si la presentación cambió su cantidad,
                             * no alteramos silenciosamente lo solicitado.
                             */
                            if (
                                (int) $presentation->quantity !==
                                (int) $unitsPerPack
                            ) {
                                throw new \Exception(
                                    'La presentación del producto "' .
                                    $stockItem->material->full_name .
                                    '" cambió su cantidad de unidades. Revise el producto manualmente.'
                                );
                            }

                            $currentPrice = (float) $presentation->price;
                            $lineQuantityForPrice = (float) $packs;

                        } else {

                            /*
                             * ==================================================
                             * PRODUCTO POR UNIDAD
                             * ==================================================
                             */
                            $priceListItem = PriceListItem::query()
                                ->where(
                                    'stock_item_id',
                                    $stockItem->id
                                )
                                ->whereHas('priceList', function ($query) {
                                    $query->where('is_default', true)
                                        ->where('is_active', true);
                                })
                                ->first();

                            if (!$priceListItem) {
                                throw new \Exception(
                                    'El producto "' .
                                    $stockItem->display_name .
                                    '" no tiene un precio configurado en la lista predeterminada.'
                                );
                            }

                            $currentPrice = (float) $priceListItem->price;
                        }

                        if ($currentPrice <= 0) {
                            throw new \Exception(
                                'El producto "' .
                                $stockItem->display_name .
                                '" tiene un precio inválido o igual a cero.'
                            );
                        }

                        /*
                         * Precio con IGV y valor unitario sin IGV.
                         */
                        $priceReal = $round10($currentPrice);
                        $valorUnitarioReal = $round10(
                            $priceReal / $factor
                        );

                        $importeReal = $round10(
                            $lineQuantityForPrice * $priceReal
                        );

                        /*
                         * ==================================================
                         * VALIDAR STOCK ACTUAL
                         * ==================================================
                         */
                        $available = $reservationService
                            ->getAvailableStockByStockItem(
                                (int) $stockItem->id
                            );

                        if ($requestedUnits > $available) {
                            throw new \Exception(
                                'El producto "' .
                                $stockItem->material->full_name .
                                '" no cuenta con stock suficiente. ' .
                                'Requerido: ' .
                                $requestedUnits .
                                '. Disponible: ' .
                                $available .
                                '.'
                            );
                        }

                        /*
                         * ==================================================
                         * CREAR EL NUEVO CONSUMIBLE
                         * ==================================================
                         */
                        $renewConsumable = EquipmentConsumable::create([
                            'equipment_id' => $renewEquipment->id,
                            'material_id' => $stockItem->material_id,
                            'stock_item_id' => $stockItem->id,

                            /*
                             * Se conserva la cantidad real solicitada.
                             */
                            'quantity' => $requestedUnits,

                            'price' => $priceReal,
                            'valor_unitario' => $valorUnitarioReal,
                            'total' => $importeReal,

                            /*
                             * Se conserva el descuento específico anterior.
                             */
                            'discount' => $originalConsumable->discount ?: 0,
                            'type_promo' => $originalConsumable->type_promo,

                            'material_presentation_id' => $presentationId,
                            'packs' => $packs,
                            'units_per_pack' => $unitsPerPack,

                            'state' => 'En compra',
                            'availability' => 'Completo',
                        ]);

                        /*
                         * Crear las reservas de la nueva cotización.
                         */
                        $reservationService->reserveForQuoteDetail(
                            (int) $renewQuote->id,
                            (int) $renewConsumable->id,
                            (int) $stockItem->id,
                            $requestedUnits
                        );

                        /*
                         * Totales del equipo, siguiendo store().
                         */
                        $totalConsumablesForEquipment = $round10(
                            $totalConsumablesForEquipment +
                            $importeReal
                        );

                        /*
                         * Totales generales, siguiendo confirmEquipment().
                         */
                        $subtotalConsumablesWithIgvReal = $round10(
                            $subtotalConsumablesWithIgvReal +
                            $importeReal
                        );

                        $discountPromotions = $round10(
                            $discountPromotions +
                            (float) ($originalConsumable->discount ?: 0)
                        );
                    }

                    /*
                     * ======================================================
                     * 6.2. SERVICIOS ADICIONALES
                     * ======================================================
                     */
                    foreach ($originalEquipment->workforces as $originalWorkforce) {

                        $billable = isset($originalWorkforce->billable)
                            ? (int) $originalWorkforce->billable
                            : 1;

                        $renewWorkforce = EquipmentWorkforce::create([
                            'equipment_id' => $renewEquipment->id,
                            'description' => $originalWorkforce->description ?: '',
                            'price' => (float) $originalWorkforce->price,
                            'quantity' => (float) $originalWorkforce->quantity,
                            'total' => (float) $originalWorkforce->total,
                            'unit' => $originalWorkforce->unit ?: '',
                            'billable' => $billable,
                        ]);

                        /*
                         * Store suma todos los servicios para equipment.total.
                         */
                        $totalWorkforcesForEquipment = $round10(
                            $totalWorkforcesForEquipment +
                            (float) $renewWorkforce->total
                        );

                        /*
                         * Para total_importe solamente se consideran
                         * los servicios facturables.
                         */
                        if ($billable === 1) {
                            $servicesSumBillable = $round10(
                                $servicesSumBillable +
                                (float) $renewWorkforce->total
                            );
                        }
                    }

                    /*
                     * ======================================================
                     * 6.3. TOTAL DEL EQUIPO SEGÚN STORE ACTUAL
                     * ======================================================
                     */
                    $totalEquipment = $round10(
                        (
                            $totalConsumablesForEquipment +
                            $totalWorkforcesForEquipment
                        ) * (float) $renewEquipment->quantity
                    );

                    $renewEquipment->total = $totalEquipment;
                    $renewEquipment->save();
                }

                /*
                 * ==========================================================
                 * 7. RECALCULAR TOTALES DE LA COTIZACIÓN
                 * ==========================================================
                 */

                /*
                 * Descuentos de promociones guardados en las líneas.
                 */
                $subtotalConsumablesWithIgvReal = $round10(
                    $subtotalConsumablesWithIgvReal -
                    $discountPromotions
                );

                if ($subtotalConsumablesWithIgvReal < 0) {
                    $subtotalConsumablesWithIgvReal = 0;
                }

                $subtotalWithIgvReal = $round10(
                    $subtotalConsumablesWithIgvReal +
                    $servicesSumBillable
                );

                /*
                 * Reproducir computeDiscountWithIgv().
                 */
                $discountType = $originalQuote->discount_type ?: 'amount';

                $discountInputMode = $originalQuote
                    ->discount_input_mode ?: 'without_igv';

                $discountInputValue = (float) (
                $originalQuote->discount_input_value ?: 0
                );

                $discountWithIgv = 0;

                if ($discountInputValue > 0) {

                    if ($discountType === 'amount') {

                        $discountWithIgv =
                            $discountInputMode === 'with_igv'
                                ? $discountInputValue
                                : $moneyRound(
                                $discountInputValue * $factor
                            );

                    } else {

                        $percentage = $discountInputValue / 100;

                        if ($discountInputMode === 'with_igv') {
                            $discountWithIgv = $moneyRound(
                                $subtotalWithIgvReal *
                                $percentage
                            );
                        } else {
                            $discountWithIgv = $moneyRound(
                                (
                                    $subtotalWithIgvReal /
                                    $factor
                                ) *
                                $percentage *
                                $factor
                            );
                        }
                    }

                    if ($discountWithIgv > $subtotalWithIgvReal) {
                        $discountWithIgv = $subtotalWithIgvReal;
                    }
                }

                $discountWithIgv = $moneyRound(
                    $discountWithIgv
                );

                $totalFinalWithIgvReal = $round10(
                    $subtotalWithIgvReal -
                    $discountWithIgv
                );

                if ($totalFinalWithIgvReal < 0) {
                    $totalFinalWithIgvReal = 0;
                }

                $baseFinalReal = $round10(
                    $totalFinalWithIgvReal /
                    $factor
                );

                if ($baseFinalReal < 0) {
                    $baseFinalReal = 0;
                }

                $igvFinalReal = $round10(
                    $totalFinalWithIgvReal -
                    $baseFinalReal
                );

                $discountBaseReal = $round10(
                    $discountWithIgv /
                    $factor
                );

                /*
                 * Guardar los totales reales.
                 */
                $renewQuote->descuento = $discountBaseReal;
                $renewQuote->gravada = $baseFinalReal;
                $renewQuote->igv_total = $igvFinalReal;
                $renewQuote->total_importe = $totalFinalWithIgvReal;
                $renewQuote->save();

                /*
                 * ==========================================================
                 * 8. NOTIFICACIÓN
                 * ==========================================================
                 */
                $notification = Notification::create([
                    'content' =>
                        $renewQuote->code .
                        ' recotizada desde ' .
                        $originalQuote->code .
                        ' por ' .
                        Auth::user()->name,

                    'reason_for_creation' => 'renew_quote',

                    'user_id' => Auth::id(),

                    'url_go' => route(
                        'quote.edit',
                        $renewQuote->id
                    ),
                ]);

                $users = User::role([
                    'admin',
                    'principal',
                    'logistic'
                ])->get();

                foreach ($users as $user) {
                    if ($user->id == Auth::id()) {
                        continue;
                    }

                    foreach ($user->roles as $role) {
                        NotificationUser::create([
                            'notification_id' => $notification->id,
                            'role_id' => $role->id,
                            'user_id' => $user->id,
                            'read' => false,
                            'date_read' => null,
                            'date_delete' => null,
                        ]);
                    }
                }

                /*
                 * ==========================================================
                 * 9. AUDITORÍA
                 * ==========================================================
                 */
                $end = microtime(true) - $begin;

                Audit::create([
                    'user_id' => Auth::id(),
                    'action' =>
                        'Recotizar cotización de venta ' .
                        $originalQuote->code .
                        ' a ' .
                        $renewQuote->code,
                    'time' => $end,
                ]);

                return $renewQuote;
            });

            return response()->json([
                'ok' => true,
                'message' =>
                    'La cotización ' .
                    $renewQuote->code .
                    ' fue creada correctamente.',
                'url' => route('quoteSale.index'),
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 422);
        }
    }

    public function getAvailableStockFromLots($stockItemId)
    {
        return (float) StockLot::where('stock_item_id', $stockItemId)
            ->get()
            ->sum(function ($lot) {
                return (float) $lot->qty_on_hand - (float) $lot->qty_reserved;
            });
    }

    public function updateDatosGeneral(Request $request)
    {
        $quote = Quote::findOrFail($request->get('quote_id'));

        try {
            $dateQuote = $request->get('date_quote')
                ? Carbon::createFromFormat('d/m/Y', $request->get('date_quote'))
                : null;

            $dateValidate = $request->get('date_validate')
                ? Carbon::createFromFormat('d/m/Y', $request->get('date_validate'))
                : null;

            $quote->update([
                'description_quote'   => $request->get('descriptionQuote'),
                'code'                => $request->get('codeQuote'),
                'date_quote'          => $dateQuote,
                'date_validate'       => $dateValidate,
                'way_to_pay'          => $request->get('way_to_pay') ?? '',
                'delivery_time'       => $request->get('delivery_time') ?? '',
                'customer_id'         => $request->get('customer_id') ?? null,
                'contact_id'          => $request->get('contact_id') ?? null,
                'payment_deadline_id' => $request->get('payment_deadline') ?? null,
                'observations'        => $request->get('observations') ?? '',
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Datos generales actualizados correctamente.'
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function getDataQuotesIndex(Request $request, $pageNumber = 1)
    {
        $perPage = 10;

        $description_quote = $request->input('description_quote');
        $year = $request->input('year');
        $code = $request->input('code');
        $order = $request->input('order');
        $customer = $request->input('customer');
        $creator = $request->input('creator');
        $stateQuote = $request->input('stateQuote');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        /*
         * ============================================================
         * CONSULTA BASE
         * ============================================================
         */
        $query = Quote::with([
            'customer',
            'deadline',
            'users',
            'equipments.workforces',
        ])
            ->whereNotIn('state', [
                'canceled',
                'expired',
                'close',
            ])
            ->where('state_active', 'open')

            /*
             * No mostrar cotizaciones que todavía tengan
             * una venta activa asociada.
             */
            ->whereDoesntHave('sales', function ($q) {
                $q->where('state_annulled', 0);
            })

            /*
             * ========================================================
             * OCULTAR COTIZACIONES REQUOTE YA RECOTIZADAS
             * ========================================================
             *
             * Se muestran:
             * - Todas las cotizaciones cuyo estado no sea requote.
             * - Las cotizaciones requote que todavía no tengan
             *   una nueva cotización asociada.
             *
             * No se muestran:
             * - Las cotizaciones requote que ya fueron utilizadas
             *   como origen de otra cotización.
             */
            ->where(function ($q) {
                $q->where('state', '<>', 'requote')
                    ->orWhere(function ($requoteQuery) {
                        $requoteQuery
                            ->where('state', 'requote')
                            ->whereDoesntHave('renewedQuote');
                    });
            });

        /*
         * ============================================================
         * FILTRO POR FECHAS
         * ============================================================
         */
        if ($startDate != "" && $endDate != "") {
            $fechaInicio = Carbon::createFromFormat(
                'd/m/Y',
                $startDate
            );

            $fechaFinal = Carbon::createFromFormat(
                'd/m/Y',
                $endDate
            );

            $query->whereDate(
                'date_quote',
                '>=',
                $fechaInicio
            );

            $query->whereDate(
                'date_quote',
                '<=',
                $fechaFinal
            );
        }

        /*
         * ============================================================
         * FILTROS GENERALES
         * ============================================================
         */
        if ($description_quote) {
            $query->where(
                'description_quote',
                'LIKE',
                '%' . $description_quote . '%'
            );
        }

        if ($year) {
            $query->whereYear(
                'date_quote',
                $year
            );
        }

        if ($code) {
            $query->where(
                'code',
                'LIKE',
                '%' . $code . '%'
            );
        }

        if ($order) {
            $query->where(
                'code_customer',
                'LIKE',
                '%' . $order . '%'
            );
        }

        if ($customer) {
            $query->whereHas(
                'customer',
                function ($query2) use ($customer) {
                    $query2->where(
                        'customer_id',
                        $customer
                    );
                }
            );
        }

        if ($creator != "") {
            $query->whereHas(
                'users',
                function ($query2) use ($creator) {
                    $query2->where(
                        'user_id',
                        $creator
                    );
                }
            );
        }

        /*
         * ============================================================
         * FILTRO POR ESTADO
         * ============================================================
         */
        if ($stateQuote) {
            $query->where(function ($subquery) use ($stateQuote) {
                switch ($stateQuote) {
                    case 'created':
                        $subquery
                            ->where('state', 'created')
                            ->where(function ($q2) {
                                $q2->where('send_state', 0)
                                    ->orWhere('send_state', false);
                            });
                        break;

                    case 'send':
                        $subquery
                            ->where('state', 'created')
                            ->where(function ($q2) {
                                $q2->where('send_state', 1)
                                    ->orWhere('send_state', true);
                            });
                        break;

                    case 'close':
                        $subquery->where(
                            'state_active',
                            'close'
                        );
                        break;

                    case 'raised':
                        $subquery
                            ->where('state', 'confirmed')
                            ->where('raise_status', 1)
                            ->where('state', '<>', 'canceled')
                            ->where('state_active', '<>', 'close');
                        break;

                    case 'confirm':
                        $subquery
                            ->where('state', 'confirmed')
                            ->where('raise_status', 0);
                        break;

                    case 'canceled':
                        $subquery->where(
                            'state',
                            'canceled'
                        );
                        break;

                    case 'requote':
                        /*
                         * Solamente devolverá las cotizaciones para
                         * recotizar que todavía no fueron recotizadas.
                         *
                         * La condición whereDoesntHave('renewedQuote')
                         * también está aplicada en la consulta base.
                         */
                        $subquery->where(
                            'state',
                            'requote'
                        );
                        break;

                    default:
                        break;
                }
            });
        }

        /*
         * ============================================================
         * ORDENAMIENTO Y PAGINACIÓN
         * ============================================================
         */
        $query->orderBy(
            'created_at',
            'DESC'
        );

        $totalFilteredRecords = $query->count();

        $totalPages = (int) ceil(
            $totalFilteredRecords / $perPage
        );

        $startRecord = $totalFilteredRecords > 0
            ? (($pageNumber - 1) * $perPage) + 1
            : 0;

        $endRecord = min(
            $totalFilteredRecords,
            $pageNumber * $perPage
        );

        $quotes = $query
            ->skip(($pageNumber - 1) * $perPage)
            ->take($perPage)
            ->get();

        /*
         * ============================================================
         * FORMATEAR RESPUESTA
         * ============================================================
         */
        $array = [];

        foreach ($quotes as $quote) {
            $state = "";
            $stateText = "";

            if ($quote->state === 'created') {
                if (
                    $quote->send_state == 1 ||
                    $quote->send_state == true
                ) {
                    $state = 'send';

                    $stateText =
                        '<span class="badge bg-warning">' .
                        'Enviado' .
                        '</span>';
                } else {
                    $state = 'created';

                    $stateText =
                        '<span class="badge bg-primary">' .
                        'Creada' .
                        '</span>';
                }
            }

            if ($quote->state_active === 'close') {
                $state = 'close';

                $stateText =
                    '<span class="badge bg-danger">' .
                    'Finalizada' .
                    '</span>';
            } else {
                if (
                    $quote->state === 'confirmed' &&
                    $quote->raise_status === 1
                ) {
                    $state = 'raise';

                    $stateText =
                        '<span class="badge bg-success">' .
                        'Elevada' .
                        '</span>';
                }

                if (
                    $quote->state === 'confirmed' &&
                    $quote->raise_status === 0
                ) {
                    $state = 'confirm';

                    $stateText =
                        '<span class="badge bg-success">' .
                        'Confirmada' .
                        '</span>';
                }

                if ($quote->state === 'canceled') {
                    $state = 'canceled';

                    $stateText =
                        '<span class="badge bg-danger">' .
                        'Cancelada' .
                        '</span>';
                }

                if ($quote->state === 'requote') {
                    $state = 'requote';

                    $stateText =
                        '<span class="badge bg-secondary">' .
                        'Para Recotizar' .
                        '</span>';
                }
            }

            /*
             * Estado de decimales.
             */
            if ($quote->state_decimals == 1) {
                $stateDecimals =
                    '<span class="badge bg-success">' .
                    'Mostrar' .
                    '</span>';
            } else {
                $stateDecimals =
                    '<span class="badge bg-danger">' .
                    'Ocultar' .
                    '</span>';
            }

            /*
             * Servicios no facturables.
             */
            $total_workforce = 0;

            foreach ($quote->equipments as $equipment) {
                foreach ($equipment->workforces as $workforce) {
                    if ($workforce->billable == false) {
                        $total_workforce +=
                            (float) $workforce->total;
                    }
                }
            }

            $array[] = [
                'id' => $quote->id,

                'year' => (
                    $quote->date_quote == null ||
                    $quote->date_quote == ""
                )
                    ? ''
                    : $quote->date_quote->year,

                'code' => (
                    $quote->code == null ||
                    $quote->code == ""
                )
                    ? ''
                    : $quote->code,

                'description' => (
                    $quote->description_quote == null ||
                    $quote->description_quote == ""
                )
                    ? ''
                    : $quote->description_quote,

                'date_quote' => (
                    $quote->date_quote == null ||
                    $quote->date_quote == ""
                )
                    ? ''
                    : $quote->date_quote->format('d/m/Y'),

                'order' => (
                    $quote->code_customer == null ||
                    $quote->code_customer == ""
                )
                    ? ''
                    : $quote->code_customer,

                'date_validate' => (
                    $quote->date_validate == null ||
                    $quote->date_validate == ""
                )
                    ? ''
                    : $quote->date_validate->format('d/m/Y'),

                'deadline' => (
                    $quote->payment_deadline_id == null ||
                    $quote->payment_deadline_id == ""
                )
                    ? ''
                    : optional($quote->deadline)->description,

                'time_delivery' =>
                    $quote->time_delivery . ' DÍAS',

                'customer' => empty($quote->customer_id)
                    ? ''
                    : optional($quote->customer)->business_name,

                'total_sunat' => number_format(
                    $quote->total_importe,
                    2
                ),

                'total_cliente' => number_format(
                    $quote->total_importe + $total_workforce,
                    2
                ),

                'total_services' => number_format(
                    $total_workforce,
                    2
                ),

                'currency' => (
                    $quote->currency_invoice == null ||
                    $quote->currency_invoice == ""
                )
                    ? ''
                    : $quote->currency_invoice,

                'state' => $state,
                'stateText' => $stateText,

                'created_at' => $quote->created_at
                    ->format('d/m/Y'),

                'creator' => (
                    !isset($quote->users[0]) ||
                    !$quote->users[0]->user
                )
                    ? ''
                    : $quote->users[0]->user->name,

                'decimals' => $stateDecimals,
                'send_state' => $quote->send_state,
            ];
        }

        /*
         * ============================================================
         * INFORMACIÓN DE PAGINACIÓN
         * ============================================================
         */
        $pagination = [
            'currentPage' => (int) $pageNumber,
            'totalPages' => $totalPages,
            'startRecord' => $startRecord,
            'endRecord' => $endRecord,
            'totalRecords' => $totalFilteredRecords,
            'totalFilteredRecords' => $totalFilteredRecords,
        ];

        return [
            'data' => $array,
            'pagination' => $pagination,
        ];
    }

    public function getDataQuotesSalesIndex(Request $request, $pageNumber = 1)
    {
        $perPage = 10;
        $description_quote = $request->input('description_quote');
        $year = $request->input('year');
        $code = $request->input('code');
        $order = $request->input('order');
        $customer = $request->input('customer');
        $creator = $request->input('creator');
        $stateQuote = $request->input('stateQuote');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        if ($startDate == "" || $endDate == "")
        {
            $query = Quote::with('customer', 'deadline', 'users')
                ->whereNotIn('state', ['canceled', 'expired'])
                ->where('state_active', 'open')
                ->whereHas('sales', function ($q) {
                    $q->where('state_annulled', 0);
                })
                ->orderBy('created_at', 'DESC');

        } else {

            $fechaInicio = Carbon::createFromFormat('d/m/Y', $startDate);
            $fechaFinal  = Carbon::createFromFormat('d/m/Y', $endDate);

            $query = Quote::with('customer', 'deadline', 'users')
                ->whereNotIn('state', ['canceled', 'expired'])
                ->where('state_active', 'open')
                ->whereDate('date_quote', '>=', $fechaInicio)
                ->whereDate('date_quote', '<=', $fechaFinal)
                ->whereHas('sales', function ($q) {
                    $q->where('state_annulled', 0);
                })
                ->orderBy('created_at', 'DESC');
        }

        // Aplicar filtros si se proporcionan
        if ($description_quote) {
            $query->where('description_quote', 'LIKE', '%'.$description_quote.'%');
        }

        if ($year) {
            $query->whereYear('date_quote', $year);
        }

        if ($code) {
            $query->where('code', 'LIKE', '%'.$code.'%');

        }

        if ($order) {
            $query->where('code_customer', 'LIKE', '%'.$order.'%');

        }

        if ($customer) {
            $query->whereHas('customer', function ($query2) use ($customer) {
                $query2->where('customer_id', $customer);
            });

        }

        if ($creator != "")
        {
            $query->whereHas('users', function ($query2) use ($creator) {
                $query2->where('user_id', $creator);
            });
        }

        if ($stateQuote) {
            // Creada, Enviada, confirmada, elevada, VB Finanzas, VB Operaciones, Finalizadas, Anuladas
            // created, send, confirm, raised, VB_finance, VB_operation, close, canceled
            $query->where(function ($subquery) use ($stateQuote) {
                $subquery->where(function ($q) use ($stateQuote) {
                    switch ($stateQuote) {
                        case 'created':
                            $q->where('state', 'created')
                                ->where(function ($q2) {
                                    $q2->where('send_state', 0)
                                        ->orWhere('send_state', false);
                                });
                            break;
                        case 'send':
                            $q->where('state', 'created')
                                ->where(function ($q2) {
                                    $q2->where('send_state', 1)
                                        ->orWhere('send_state', true);
                                });
                            break;
                        case 'close':
                            $q->where('state_active', 'close');
                            break;
                        case 'raised':
                            $q->where('state', 'confirmed')
                                ->where('raise_status', 1)
                                ->where('state', '<>','canceled')
                                ->where('state_active', '<>','close');
                            break;
                        case 'confirm':
                            $q->where('state', 'confirmed')
                                ->where('raise_status', 0);
                            break;
                        case 'canceled':
                            $q->where('state', 'canceled');
                            break;
                        default:
                            // Lógica por defecto o manejo de errores si es necesario
                            break;
                    }
                });
            });
        }

        //$query = FinanceWork::with('quote', 'bank');

        $totalFilteredRecords = $query->count();
        $totalPages = ceil($totalFilteredRecords / $perPage);

        $startRecord = ($pageNumber - 1) * $perPage + 1;
        $endRecord = min($totalFilteredRecords, $pageNumber * $perPage);

        $quotes = $query->skip(($pageNumber - 1) * $perPage)
            ->take($perPage)
            ->get();

        //dd($proformas);

        $array = [];

        foreach ( $quotes as $quote )
        {
            $state = "";
            $stateText = "";
            if ( $quote->state === 'created' ) {
                if ( $quote->send_state == 1 || $quote->send_state == true )
                {
                    $state = 'send';
                    $stateText = '<span class="badge bg-warning">Enviado</span>';
                } else {
                    $state = 'created';
                    $stateText = '<span class="badge bg-primary">Creada</span>';
                }
            }
            if ($quote->state_active === 'close'){
                $state = 'close';
                $stateText = '<span class="badge bg-danger">Finalizada</span>';
            } else {
                if ($quote->state === 'confirmed' && $quote->raise_status === 1){

                    $state = 'raise';
                    $stateText = '<span class="badge bg-success">Elevada</span>';
                }
                if ($quote->state === 'confirmed' && $quote->raise_status === 0){
                    $state = 'confirm';
                    $stateText =  '<span class="badge bg-success">Confirmada</span>';
                }
                if ($quote->state === 'canceled'){
                    $state = 'canceled';
                    $stateText = '<span class="badge bg-danger">Cancelada</span>';
                }
            }

            $stateDecimals = '';
            if ( $quote->state_decimals == 1 )
            {
                $stateDecimals = '<span class="badge bg-success">Mostrar</span>';
            } else {
                $stateDecimals = '<span class="badge bg-danger">Ocultar</span>';
            }

            $total_workforce  = 0;
            foreach($quote->equipments as $equipment)
            {
                foreach($equipment->workforces as $workforce)
                {
                    if ( $workforce->billable == false )
                    {
                        $total_workforce = $total_workforce + $workforce->total;
                    }

                }
            }

            array_push($array, [
                "id" => $quote->id,
                "year" => ( $quote->date_quote == null || $quote->date_quote == "") ? '':$quote->date_quote->year,
                "code" => ($quote->code == null || $quote->code == "") ? '': $quote->code,
                "description" => ($quote->description_quote == null || $quote->description_quote == "") ? '': $quote->description_quote,
                "date_quote" => ($quote->date_quote == null || $quote->date_quote == "") ? '': $quote->date_quote->format('d/m/Y'),
                "order" => ($quote->code_customer == null || $quote->code_customer == "") ? "": $quote->code_customer,
                "date_validate" => ($quote->date_validate == null || $quote->date_validate == "") ? '': $quote->date_validate->format('d/m/Y'),
                "deadline" => ($quote->payment_deadline_id == null || $quote->payment_deadline_id == "") ? "":$quote->deadline->description,
                "time_delivery" => $quote->time_delivery.' DÍAS',
                "customer" => ($quote->customer_id == "" || $quote->customer_id == null) ? "" : $quote->customer->business_name,
                "total_igv" => number_format($quote->total_importe/1.18, 2),
                "total" => number_format($quote->total_importe, 2),
                "total_sunat" => number_format($quote->total_importe, 2),
                "total_cliente" => number_format($quote->total_importe+$total_workforce, 2),
                "total_services" => number_format($total_workforce, 2),
                "currency" => ($quote->currency_invoice == null || $quote->currency_invoice == "") ? '': $quote->currency_invoice,
                "state" => $state,
                "stateText" => $stateText,
                "created_at" => $quote->created_at->format('d/m/Y'),
                "creator" => ($quote->users[0] == null) ? "": $quote->users[0]->user->name,
                "decimals" => $stateDecimals,
                "send_state" => $quote->send_state
            ]);
        }

        $pagination = [
            'currentPage' => (int)$pageNumber,
            'totalPages' => (int)$totalPages,
            'startRecord' => $startRecord,
            'endRecord' => $endRecord,
            'totalRecords' => $totalFilteredRecords,
            'totalFilteredRecords' => $totalFilteredRecords
        ];

        return ['data' => $array, 'pagination' => $pagination];
    }

    public function edit($id)
    {
        $begin = microtime(true);

        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $unitMeasures = UnitMeasure::all();
        $customers = Customer::all();

        $defaultConsumable = '(*)';
        $defaultElectric = '(e)';

        $consumables = Material::with('unitMeasure')
            ->where('category_id', 2)
            ->where('description', 'LIKE', '%' . $defaultConsumable . '%')
            ->orderBy('full_name', 'asc')
            ->get();

        $electrics = Material::with('unitMeasure')
            ->where('category_id', 2)
            ->whereElectric('description', $defaultElectric)
            ->orderBy('full_name', 'asc')
            ->get();

        $workforces = Workforce::with('unitMeasure')->get();

        $paymentDeadlines = PaymentDeadline::where('type', 'quotes')->get();

        $utility = PorcentageQuote::where('name', 'utility')->first();
        $rent = PorcentageQuote::where('name', 'rent')->first();
        $letter = PorcentageQuote::where('name', 'letter')->first();

        $quote = Quote::where('id', $id)
            ->with('customer')
            ->with('deadline')
            ->with([
                'equipments' => function ($query) {
                    $query->with([
                        'materials',

                        'consumables' => function ($consumableQuery) {
                            $consumableQuery->with([
                                'presentation',
                                'stockItem.material',
                                'stockItem.inventoryLevels',
                                'stockItem.priceListItems.priceList',
                                'quoteStockLots',
                            ]);
                        },

                        'electrics',
                        'workforces',
                        'turnstiles',
                        'workdays',
                    ]);
                }
            ])
            ->first();

        $images = [];

        $dataCurrency = DataGeneral::where('name', 'type_current')->first();
        $currency = $dataCurrency->valueText;

        $dataIgv = PorcentageQuote::where('name', 'igv')->first();
        $igv = $dataIgv->value;

        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Editar cotizacion VISTA',
            'time' => $end
        ]);

        return view('quoteSale.edit', compact(
            'quote',
            'unitMeasures',
            'customers',
            'consumables',
            'electrics',
            'workforces',
            'permissions',
            'paymentDeadlines',
            'utility',
            'rent',
            'letter',
            'images',
            'currency',
            'igv'
        ));
    }

    public function update(UpdateQuoteRequest $request)
    {
        $begin = microtime(true);
        $validated = $request->validated();
        $reservationService = app(QuoteStockReservationService::class);
        DB::beginTransaction();
        try {
            $quote = Quote::find($request->get('quote_id'));

            $quote->code = $request->get('code_quote');
            $quote->description_quote = $request->get('code_description');
            $quote->observations = $request->get('observations');
            $quote->date_quote = ($request->has('date_quote')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_quote')) : Carbon::now();
            $quote->date_validate = ($request->has('date_validate')) ? Carbon::createFromFormat('d/m/Y', $request->get('date_validate')) : Carbon::now()->addDays(5);
            $quote->way_to_pay = ($request->has('way_to_pay')) ? $request->get('way_to_pay') : '';
            $quote->payment_deadline_id = ($request->has('payment_deadline')) ? $request->get('payment_deadline') : null;
            $quote->delivery_time = ($request->has('delivery_time')) ? $request->get('delivery_time') : '';
            $quote->customer_id = ($request->has('customer_id')) ? $request->get('customer_id') : null;
            $quote->contact_id = ($request->has('contact_id')) ? $request->get('contact_id') : null;
            //$quote->utility = ($request->has('utility')) ? $request->get('utility'): 0;
            //$quote->letter = ($request->has('letter')) ? $request->get('letter'): 0;
            //$quote->rent = ($request->has('taxes')) ? $request->get('taxes'): 0;
            $quote->currency_invoice = 'USD';
            $quote->currency_compra = null;
            $quote->currency_venta = null;
            $quote->total_soles = 0;
            $quote->save();

            $equipments = json_decode($request->get('equipments'));

            $totalQuote = 0;

            for ( $i=0; $i<sizeof($equipments); $i++ )
            {
                if ($equipments[$i]->quote === '' )
                {
                    $equipment = Equipment::create([
                        'quote_id' => $quote->id,
                        'description' => ($equipments[$i]->description == "" || $equipments[$i]->description == null) ? '':$equipments[$i]->description,
                        'detail' => ($equipments[$i]->detail == "" || $equipments[$i]->detail == null) ? '':$equipments[$i]->detail,
                        'quantity' => $equipments[$i]->quantity,
                        'utility' => $equipments[$i]->utility,
                        'rent' => $equipments[$i]->rent,
                        'letter' => $equipments[$i]->letter,
                        'total' => $equipments[$i]->total
                    ]);

                    $totalMaterial = 0;

                    $totalConsumable = 0;

                    $totalElectric = 0;

                    $totalWorkforces = 0;

                    $totalTornos = 0;

                    $totalDias = 0;

                    $materials = $equipments[$i]->materials;

                    $consumables = $equipments[$i]->consumables;

                    $electrics = $equipments[$i]->electrics;

                    $workforces = $equipments[$i]->workforces;

                    $tornos = $equipments[$i]->tornos;

                    $dias = $equipments[$i]->dias;

                    for ( $j=0; $j<sizeof($materials); $j++ )
                    {
                        $equipmentMaterial = EquipmentMaterial::create([
                            'equipment_id' => $equipment->id,
                            'material_id' => $materials[$j]->material->id,
                            'quantity' => (float) $materials[$j]->quantity,
                            'price' => (float) $materials[$j]->material->unit_price,
                            'length' => (float) ($materials[$j]->length == '') ? 0: $materials[$j]->length,
                            'width' => (float) ($materials[$j]->width == '') ? 0: $materials[$j]->width,
                            'percentage' => (float) $materials[$j]->quantity,
                            'state' => ($materials[$j]->quantity > $materials[$j]->material->stock_current) ? 'Falta comprar':'En compra',
                            'availability' => ($materials[$j]->quantity > $materials[$j]->material->stock_current) ? 'Agotado':'Completo',
                            'total' => (float) $materials[$j]->material->unit_price*(float) $materials[$j]->quantity,
                        ]);

                        $totalMaterial += $equipmentMaterial->total;
                    }

                    // CONSUMABLES
                    @bcscale(10);

                    for ($k = 0; $k < sizeof($consumables); $k++) {
                        $c = $consumables[$k];

                        $materialId = (int) ($c->id ?? 0);
                        $stockItemId = (int) ($c->stockItemId ?? 0);

                        $material = Material::find($materialId);
                        if (!$material) {
                            throw new \Exception("El material con ID {$materialId} no existe.");
                        }

                        $stockItem = StockItem::with(['material'])
                            ->where('id', $stockItemId)
                            ->where('material_id', $materialId)
                            ->first();

                        if (!$stockItem) {
                            throw new \Exception("El stock item con ID {$stockItemId} no existe o no pertenece al material {$material->full_name}.");
                        }

                        // 1) Inputs del front
                        $frontQty = (float) ($c->quantity ?? 0);
                        if ($frontQty <= 0) {
                            throw new \Exception("Cantidad inválida para material {$stockItem->display_name}.");
                        }

                        $presentationId = $c->presentation_id ?? null;

                        $valorUnitarioReal = (string) ($c->valorReal ?? $c->valor ?? '0');
                        $priceReal         = (string) ($c->priceReal ?? $c->price ?? '0');
                        $importeReal       = (string) ($c->importe ?? '0');

                        if ((float) $valorUnitarioReal < 0 || (float) $priceReal < 0 || (float) $importeReal < 0) {
                            throw new \Exception("Valores inválidos (precio/valor/total) para {$stockItem->display_name}.");
                        }

                        // 2) Presentación / unidades equivalentes
                        $packs = null;
                        $unitsPerPack = null;

                        if (!empty($presentationId)) {
                            $packs = (int) $frontQty;
                            if ($packs < 1) {
                                throw new \Exception("Cantidad de paquetes inválida para material {$stockItem->display_name}.");
                            }

                            $presentation = MaterialPresentation::where('id', $presentationId)
                                ->where('material_id', $materialId)
                                ->where('active', 1)
                                ->first();

                            if (!$presentation) {
                                throw new \Exception("La presentación seleccionada no es válida o no pertenece al material {$stockItem->display_name}.");
                            }

                            $unitsPerPack = (int) $presentation->quantity;
                            if ($unitsPerPack < 1) {
                                throw new \Exception("La presentación tiene cantidad inválida para {$stockItem->display_name}.");
                            }

                            $requestedUnits = (float) ($packs * $unitsPerPack);
                        } else {
                            $requestedUnits = (float) ($c->units_equivalent ?? $frontQty);

                            if ($requestedUnits <= 0) {
                                throw new \Exception("Cantidad inválida para material {$stockItem->display_name}.");
                            }
                        }

                        // 3) Validar stock disponible real por lotes
                        $available = $reservationService->getAvailableStockByStockItem($stockItem->id);

                        if ($requestedUnits > $available) {
                            throw new \Exception(
                                "El material {$stockItem->material->full_name} no cuenta con stock suficiente. " .
                                "Requerido: {$requestedUnits}. Disponible: {$available}."
                            );
                        }

                        $availability = ($requestedUnits > $available) ? 'Agotado' : 'Completo';
                        $state        = ($requestedUnits > $available) ? 'Falta comprar' : 'En compra';

                        // 4) Promo limit
                        if (($c->type_promo ?? null) === "limit") {
                            $promotion = PromotionLimit::where('material_id', $materialId)
                                ->whereDate('start_date', '<=', now())
                                ->whereDate('end_date', '>=', now())
                                ->first();

                            if ($promotion) {
                                $query = PromotionUsage::where('promotion_limit_id', $promotion->id);

                                if ($promotion->applies_to == 'worker') {
                                    $query->where('user_id', auth()->id());
                                }

                                $usage = $query->first();

                                if (!$usage) {
                                    $usage = PromotionUsage::create([
                                        'promotion_limit_id' => $promotion->id,
                                        'user_id' => $promotion->applies_to == 'worker' ? auth()->id() : null,
                                        'used_quantity' => 0,
                                    ]);
                                }

                                $remaining = (float) $promotion->limit_quantity - (float) $usage->used_quantity;

                                if ($remaining < $requestedUnits) {
                                    throw new \Exception("La promoción para {$material->full_name} ya no tiene suficiente cantidad disponible");
                                }

                                $usage->increment('used_quantity', $requestedUnits);
                            }
                        }

                        // 5) Guardar consumable
                        $equipmentConsumable = EquipmentConsumable::create([
                            'availability' => $availability,
                            'state' => $state,
                            'equipment_id' => $equipment->id,
                            'material_id' => $materialId,
                            'stock_item_id' => $stockItemId,
                            'quantity' => $requestedUnits,
                            'price' => $priceReal,
                            'valor_unitario' => $valorUnitarioReal,
                            'discount' => (string) ($c->discount ?? '0'),
                            'total' => $importeReal,
                            'type_promo' => $c->type_promo ?? null,
                            'material_presentation_id' => $presentationId,
                            'packs' => $packs,
                            'units_per_pack' => $unitsPerPack,
                        ]);

                        $totalConsumable = bcadd((string) $totalConsumable, (string) $importeReal, 10);

                        // 6) Reservar lotes para cotización
                        $reservationService->reserveForQuoteDetail(
                            (int) $quote->id,
                            (int) $equipmentConsumable->id,
                            (int) $stockItemId,
                            (float) $requestedUnits
                        );

                        // 7) Vincular promo con el detalle
                        if (($c->type_promo ?? null) === "limit") {
                            $promotion = PromotionLimit::where('material_id', $materialId)
                                ->whereDate('start_date', '<=', now())
                                ->whereDate('end_date', '>=', now())
                                ->first();

                            if ($promotion) {
                                $query = PromotionUsage::where('promotion_limit_id', $promotion->id);

                                if ($promotion->applies_to == 'worker') {
                                    $query->where('user_id', auth()->id());
                                }

                                $usage = $query->first();

                                if ($usage) {
                                    $usage->equipment_id = $equipment->id;
                                    $usage->equipment_consumable_id = $equipmentConsumable->id;
                                    $usage->quote_id = $equipment->quote_id;
                                    $usage->save();
                                }
                            }
                        }
                    }

                    for ( $k=0; $k<sizeof($electrics); $k++ )
                    {
                        $equipmentElectric = EquipmentElectric::create([
                            'equipment_id' => $equipment->id,
                            'material_id' => $electrics[$k]->id,
                            'quantity' => (float) $electrics[$k]->quantity,
                            'price' => (float) $electrics[$k]->price,
                            'total' => (float) $electrics[$k]->quantity*(float) $electrics[$k]->price,
                        ]);

                        $totalElectric += $equipmentElectric->total;
                    }

                    for ( $w=0; $w<sizeof($workforces); $w++ )
                    {
                        $equipmentWorkforce = EquipmentWorkforce::create([
                            'equipment_id' => $equipment->id,
                            'description' => $workforces[$w]->description,
                            'price' => (float) $workforces[$w]->price,
                            'quantity' => (float) $workforces[$w]->quantity,
                            'total' => (float) $workforces[$w]->price*(float) $workforces[$w]->quantity,
                            'unit' => $workforces[$w]->unit,
                        ]);

                        $totalWorkforces += $equipmentWorkforce->total;
                    }

                    for ( $r=0; $r<sizeof($tornos); $r++ )
                    {
                        $equipmenttornos = EquipmentTurnstile::create([
                            'equipment_id' => $equipment->id,
                            'description' => $tornos[$r]->description,
                            'price' => (float) $tornos[$r]->price,
                            'quantity' => (float) $tornos[$r]->quantity,
                            'total' => (float) $tornos[$r]->price*(float) $tornos[$r]->quantity
                        ]);

                        $totalTornos += $equipmenttornos->total;
                    }

                    for ( $d=0; $d<sizeof($dias); $d++ )
                    {
                        $equipmentdias = EquipmentWorkday::create([
                            'equipment_id' => $equipment->id,
                            'description' => $dias[$d]->description,
                            'quantityPerson' => (float) $dias[$d]->quantity,
                            'hoursPerPerson' => (float) $dias[$d]->hours,
                            'pricePerHour' => (float) $dias[$d]->price,
                            'total' => (float) $dias[$d]->quantity*(float) $dias[$d]->hours*(float) $dias[$d]->price
                        ]);

                        $totalDias += $equipmentdias->total;
                    }

                    //$totalQuote += ($totalMaterial + $totalConsumable + $totalWorkforces + $totalTornos + $totalDias) * (float)$equipment->quantity;

                    //$equipment->total = ($totalMaterial + $totalConsumable + $totalWorkforces + $totalTornos + $totalDias)* (float)$equipment->quantity;

                    // Cambio el 16/01/2024
                    //$totalEquipo = (($totalMaterial + $totalConsumable + $totalWorkforces + $totalTornos) * (float)$equipment->quantity) + $totalDias;
                    $totalEquipo = (($totalMaterial + $totalConsumable + $totalElectric + $totalWorkforces + $totalTornos + $totalDias) * (float)$equipment->quantity);
                    $totalEquipmentU = $totalEquipo*(($equipment->utility/100)+1);
                    $totalEquipmentL = $totalEquipmentU*(($equipment->letter/100)+1);
                    $totalEquipmentR = $totalEquipmentL*(($equipment->rent/100)+1);

                    $totalQuote += $totalEquipmentR;

                    $equipment->total = $totalEquipo;

                    $equipment->save();
                }

            }

            $quote->total += $totalQuote;

            $quote->save();

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Editar cotizaciones POST',
                'time' => $end
            ]);

            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Nuevos equipos guardados con éxito.'], 200);

    }

    public function destroyO(Quote $quote)
    {
        DB::transaction(function () use ($quote) {

            /*
             * 1) LIBERAR RESERVAS DE MATERIALES (QuoteMaterialReservation)
             *    y AJUSTAR stock_reserved EN Material
             */
            $reservations = QuoteMaterialReservation::where('quote_id', $quote->id)
                ->lockForUpdate()
                ->get();

            foreach ($reservations as $reservation) {
                $material = Material::lockForUpdate()->find($reservation->material_id);

                if ($material) {
                    // Restar la cantidad reservada y evitar negativos
                    $material->stock_reserved = max(
                        0,
                        (float) $material->stock_reserved - (float) $reservation->quantity
                    );
                    $material->save();
                }

                // Borramos la reserva de esta cotización
                $reservation->delete();
            }

            /*
             * 2) LIMPIAR EQUIPOS Y CONSUMIBLES LIGADOS A LA COTIZACIÓN
             *    - Eliminar PromotionUsage por consumible
             *    - Eliminar consumibles
             *    - Eliminar equipos
             */
            $equipments = Equipment::where('quote_id', $quote->id)
                ->with('consumables')   // relación: equipment -> consumables
                ->get();

            foreach ($equipments as $equipment) {
                foreach ($equipment->consumables as $consumable) {

                    // Borrar usos de promociones ligados a este consumible
                    PromotionUsage::where('equipment_consumable_id', $consumable->id)->delete();

                    // Borrar el consumible
                    //$consumable->delete();
                }

                // Borrar el equipo
                //$equipment->delete();
            }

            /*
             * 3) MARCAR LA COTIZACIÓN COMO ANULADA
             */
            $quote->state = 'canceled';
            $quote->save();
        });

        return response()->json(['ok' => true]);
    }

    public function destroy(Quote $quote)
    {
        try {
            DB::transaction(function () use ($quote) {

                $quote = Quote::where('id', $quote->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($quote->state === 'canceled') {
                    throw new \Exception('La cotización ya fue anulada.');
                }

                /** @var QuoteStockReservationService $reservationService */
                $reservationService = app(QuoteStockReservationService::class);

                /*
                 * 0) VALIDAR SI SE PUEDE ANULAR
                 */
                $activeSale = Sale::where('quote_id', $quote->id)
                    ->where('state_annulled', 0)
                    ->lockForUpdate()
                    ->first();

                if ($quote->state === 'confirmed' && $activeSale) {
                    throw new \Exception(
                        'No se puede anular esta cotización porque tiene una venta activa asociada. Primero debe anular la venta.'
                    );
                }

                /*
                 * 1) LIBERAR RESERVAS DE LOTES DE LA COTIZACIÓN
                 */
                $reservationService->releaseReservationsByQuote((int) $quote->id);

                /*
                 * 2) LIMPIAR EQUIPOS Y CONSUMIBLES LIGADOS A LA COTIZACIÓN
                 *    - Eliminar PromotionUsage por consumible
                 *    - Eliminar consumibles
                 *    - Eliminar relaciones hijas
                 *    - Eliminar equipos
                 */
                $equipments = Equipment::where('quote_id', $quote->id)
                    ->with([
                        'consumables',
                        'materials',
                        'electrics',
                        'workforces',
                        'turnstiles',
                        'workdays',
                    ])
                    ->get();

                foreach ($equipments as $equipment) {

                    foreach ($equipment->consumables as $consumable) {
                        PromotionUsage::where('equipment_consumable_id', $consumable->id)->delete();
                        //$consumable->delete();
                    }

                    /*foreach ($equipment->workforces as $workforce) {
                        $workforce->delete();
                    }*/

                    //$equipment->delete();
                }

                /*
                 * 3) MARCAR LA COTIZACIÓN COMO ANULADA
                 */
                $quote->state = 'canceled';
                $quote->save();
            });

            return response()->json(['ok' => true]);

        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function updateEquipmentOfQuoteOriginal(Request $request, $id_equipment, $id_quote)
    {
        //dump($request);
        //dd();
        $begin = microtime(true);
        $user = Auth::user();
        $quote = Quote::find($id_quote);
        $quote_user = QuoteUser::where('quote_id', $id_quote)
            ->where('user_id', $user->id)->first();

        $equipmentSent = null;

        DB::beginTransaction();
        try {
            $equipment_quote = Equipment::where('id', $id_equipment)
                ->where('quote_id',$quote->id)->first();

            $output_details= OutputDetail::where('equipment_id', $equipment_quote->id)->get();

            if ( count($output_details) == 0 )
            {
                // TODO: Si no hay outputs details que proceda como estaba planificado
                //$totalDeleted = 0;
                foreach( $equipment_quote->materials as $material ) {
                    //$totalDeleted = $totalDeleted + (float) $material->total;
                    $material->delete();
                }
                foreach( $equipment_quote->consumables as $consumable ) {
                    //$totalDeleted = $totalDeleted + (float) $consumable->total;
                    $consumable->delete();
                }
                foreach( $equipment_quote->electrics as $electric ) {
                    //$totalDeleted = $totalDeleted + (float) $consumable->total;
                    $electric->delete();
                }
                foreach( $equipment_quote->workforces as $workforce ) {
                    //$totalDeleted = $totalDeleted + (float) $workforce->total;
                    $workforce->delete();
                }
                foreach( $equipment_quote->turnstiles as $turnstile ) {
                    //$totalDeleted = $totalDeleted + (float) $turnstile->total;
                    $turnstile->delete();
                }
                foreach( $equipment_quote->workdays as $workday ) {
                    //$totalDeleted = $totalDeleted + (float) $workday->total;
                    $workday->delete();
                }

                $equipment_quote->delete();

                $equipments = $request->input('equipment');

                $totalQuote = 0;

                foreach ( $equipments as $equip )
                {
                    $equipment = Equipment::create([
                        'quote_id' => $quote->id,
                        'description' => ($equip['description'] == "" || $equip['description'] == null) ? '':$equip['description'],
                        'detail' => ($equip['detail'] == "" || $equip['detail'] == null) ? '':$equip['detail'],
                        'quantity' => $equip['quantity'],
                        'utility' => $equip['utility'],
                        'rent' => $equip['rent'],
                        'letter' => $equip['letter'],
                        'total' => $equip['total']
                    ]);

                    $totalMaterial = 0;

                    $totalConsumable = 0;

                    $totalElectric = 0;

                    $totalWorkforces = 0;

                    $totalTornos = 0;

                    $totalDias = 0;

                    $materials = $equip['materials'];

                    $consumables = $equip['consumables'];

                    $electrics = $equip['electrics'];

                    $workforces = $equip['workforces'];

                    $tornos = $equip['tornos'];

                    $dias = $equip['dias'];
                    //dump($materials);
                    foreach ( $materials as $material )
                    {
                        $equipmentMaterial = EquipmentMaterial::create([
                            'equipment_id' => $equipment->id,
                            'material_id' => (int)$material['material']['id'],
                            'quantity' => (float) $material['quantity'],
                            'price' => (float) $material['material']['unit_price'],
                            'length' => (float) ($material['length'] == '') ? 0: $material['length'],
                            'width' => (float) ($material['width'] == '') ? 0: $material['width'],
                            'percentage' => (float) $material['quantity'],
                            'state' => ($material['quantity'] > $material['material']['stock_current']) ? 'Falta comprar':'En compra',
                            'availability' => ($material['quantity'] > $material['material']['stock_current']) ? 'Agotado':'Completo',
                            'total' => (float) $material['quantity']*(float) $material['material']['unit_price']
                        ]);

                        //$totalMaterial += $equipmentMaterial->total;
                    }

                    /*for ($k = 0; $k < sizeof($consumables); $k++) {
                        $material = Material::find($consumables[$k]->id);

                        // 🟢 VALIDAR PROMOCIÓN SI type_promo = limit
                        if ($consumables[$k]->type_promo == "limit") {
                            $promotion = PromotionLimit::where('material_id', $consumables[$k]->id)
                                ->whereDate('start_date', '<=', now())
                                ->whereDate('end_date', '>=', now())
                                ->first();

                            if ($promotion) {
                                // buscar uso
                                $query = PromotionUsage::where('promotion_limit_id', $promotion->id);

                                if ($promotion->applies_to == 'worker') {
                                    $query->where('user_id', auth()->id());
                                }

                                $usage = $query->first();

                                if (!$usage) {
                                    $usage = PromotionUsage::create([
                                        'promotion_limit_id' => $promotion->id,
                                        'user_id' => $promotion->applies_to == 'worker' ? auth()->id() : null,
                                        'used_quantity' => 0,
                                    ]);
                                }

                                $requestedQty = (float) $consumables[$k]->quantity;
                                $remaining = $promotion->limit_quantity - $usage->used_quantity;

                                if ($remaining < $requestedQty) {
                                    throw new \Exception("La promoción para {$material->full_name} ya no tiene suficiente cantidad disponible");
                                }

                                // actualizar consumo
                                $usage->increment('used_quantity', $requestedQty);
                            }
                        }

                        // 🟢 REGISTRAR EQUIPMENT CONSUMABLE
                        $equipmentConsumable = EquipmentConsumable::create([
                            'availability' => ((float) $consumables[$k]->quantity > $material->stock_current) ? 'Agotado' : 'Completo',
                            'state' => ((float) $consumables[$k]->quantity > $material->stock_current) ? 'Falta comprar' : 'En compra',
                            'equipment_id' => $equipment->id,
                            'material_id' => $consumables[$k]->id,
                            'quantity' => (float) $consumables[$k]->quantity,
                            'price' => (float) $consumables[$k]->price,
                            'valor_unitario' => (float) $consumables[$k]->valor,
                            'discount' => (float) $consumables[$k]->discount,
                            'total' => (float) $consumables[$k]->importe,
                            'type_promo' => $consumables[$k]->type_promo,
                        ]);

                        $totalConsumable += $equipmentConsumable->total;
                    }*/

                    foreach ( $consumables as $consumable )
                    {
                        $material = Material::find((int)$consumable['id']);

                        // 🟢 VALIDAR PROMOCIÓN SI type_promo = limit
                        if ($consumable["type_promo"] == "limit") {
                            $promotion = PromotionLimit::where('material_id', $consumable["id"])
                                ->whereDate('start_date', '<=', now())
                                ->whereDate('end_date', '>=', now())
                                ->first();

                            if ($promotion) {
                                // buscar uso
                                $query = PromotionUsage::where('promotion_limit_id', $promotion->id);

                                if ($promotion->applies_to == 'worker') {
                                    $query->where('user_id', auth()->id());
                                }

                                $usage = $query->first();

                                if (!$usage) {
                                    $usage = PromotionUsage::create([
                                        'promotion_limit_id' => $promotion->id,
                                        'user_id' => $promotion->applies_to == 'worker' ? auth()->id() : null,
                                        'used_quantity' => 0,
                                    ]);
                                }

                                $requestedQty = (float) $consumable["quantity"];
                                $remaining = $promotion->limit_quantity - $usage->used_quantity;

                                if ($remaining < $requestedQty) {
                                    throw new \Exception("La promoción para {$material->full_name} ya no tiene suficiente cantidad disponible");
                                }

                                // actualizar consumo
                                $usage->increment('used_quantity', $requestedQty);
                            }
                        }

                        $equipmentConsumable = EquipmentConsumable::create([
                            'availability' => ((float) $consumable['quantity'] > $material->stock_current) ? 'Agotado':'Completo',
                            'state' => ((float) $consumable['quantity'] > $material->stock_current) ? 'Falta comprar':'En compra',
                            'equipment_id' => $equipment->id,
                            'material_id' => $consumable['id'],
                            'quantity' => (float) $consumable['quantity'],
                            'price' => (float) $consumable['price'],
                            'valor_unitario' => (float) $consumable['valor'],
                            'discount' => (float) $consumable['discount'],
                            'total' => (float) $consumable['importe'],
                            'type_promo' => $consumables['type_promo'],
                        ]);
                    }

                    foreach ( $electrics as $electricd )
                    {
                        $equipmentElectric = EquipmentElectric::create([
                            'equipment_id' => $equipment->id,
                            'material_id' => $electricd['id'],
                            'quantity' => (float) $electricd['quantity'],
                            'price' => (float) $electricd['price'],
                            'total' => (float) $electricd['quantity']*(float) $electricd['price'],
                        ]);

                        //$totalConsumable += $equipmentConsumable->total;
                    }

                    foreach ( $workforces as $workforce )
                    {
                        $equipmentWorkforce = EquipmentWorkforce::create([
                            'equipment_id' => $equipment->id,
                            'description' => $workforce['description'],
                            'price' => (float) $workforce['price'],
                            'quantity' => (float) $workforce['quantity'],
                            'total' => (float) $workforce['price']*(float) $workforce['quantity'],
                            'unit' => $workforce['unit'],
                        ]);

                        //$totalWorkforces += $equipmentWorkforce->total;
                    }

                    foreach ( $tornos as $torno )
                    {
                        $equipmenttornos = EquipmentTurnstile::create([
                            'equipment_id' => $equipment->id,
                            'description' => $torno['description'],
                            'price' => (float) $torno['price'],
                            'quantity' => (float) $torno['quantity'],
                            'total' => (float) $torno['price']*(float) $torno['quantity']
                        ]);

                        //$totalTornos += $equipmenttornos->total;
                    }

                    foreach ( $dias as $dia )
                    {
                        $equipmentdias = EquipmentWorkday::create([
                            'equipment_id' => $equipment->id,
                            'description' => $dia['description'],
                            'quantityPerson' => (float) $dia['quantity'],
                            'hoursPerPerson' => (float) $dia['hours'],
                            'pricePerHour' => (float) $dia['price'],
                            'total' => (float) $dia['quantity']*(float) $dia['hours']*(float) $dia['price']
                        ]);

                        //$totalDias += $equipmentdias->total;
                    }

                    $totalEquipo2 = (float)$equip['total'];
                    $totalEquipmentU2 = $totalEquipo2*(($equip['utility']/100)+1);
                    $totalEquipmentL2 = $totalEquipmentU2*(($equip['letter']/100)+1);
                    $totalEquipmentR2 = $totalEquipmentL2*(($equip['rent']/100)+1);

                    $totalQuote = $totalQuote + $totalEquipmentR2;

                    $equipment->total = $totalEquipo2;

                    $equipment->save();

                    $equipmentSent = $equipment;
                }

                // Guardamos los totales
                $quote->descuento = ($request->has('descuento')) ? $request->get('descuento') : null;
                $quote->gravada = ($request->has('gravada')) ? $request->get('gravada') : null;
                $quote->igv_total = ($request->has('igv_total')) ? $request->get('igv_total') : null;
                $quote->total_importe = ($request->has('total_importe')) ? $request->get('total_importe') : null;

                $quote->save();
            } else {
                // TODO: Ya no eliminamos el equipo solo lo modificamos
                //$totalDeleted = 0;
                foreach( $equipment_quote->materials as $material ) {
                    //$totalDeleted = $totalDeleted + (float) $material->total;
                    $material->delete();
                }
                foreach( $equipment_quote->consumables as $consumable ) {
                    //$totalDeleted = $totalDeleted + (float) $consumable->total;
                    $consumable->delete();
                }
                foreach( $equipment_quote->electrics as $electric ) {
                    //$totalDeleted = $totalDeleted + (float) $consumable->total;
                    $electric->delete();
                }
                foreach( $equipment_quote->workforces as $workforce ) {
                    //$totalDeleted = $totalDeleted + (float) $workforce->total;
                    $workforce->delete();
                }
                foreach( $equipment_quote->turnstiles as $turnstile ) {
                    //$totalDeleted = $totalDeleted + (float) $turnstile->total;
                    $turnstile->delete();
                }
                foreach( $equipment_quote->workdays as $workday ) {
                    //$totalDeleted = $totalDeleted + (float) $workday->total;
                    $workday->delete();
                }

                $quote->save();

                //$equipment_quote->delete();

                $equipments = $request->input('equipment');

                $totalQuote = 0;

                foreach ( $equipments as $equip )
                {
                    $equipment_quote->quote_id = $quote->id;
                    $equipment_quote->description = ($equip['description'] == "" || $equip['description'] == null) ? '':$equip['description'];
                    $equipment_quote->detail = ($equip['detail'] == "" || $equip['detail'] == null) ? '':$equip['detail'];
                    $equipment_quote->quantity = $equip['quantity'];
                    $equipment_quote->utility = $equip['utility'];
                    $equipment_quote->rent = $equip['rent'];
                    $equipment_quote->letter = $equip['letter'];
                    $equipment_quote->total = $equip['total'];
                    $equipment_quote->save();

                    $totalMaterial = 0;

                    $totalConsumable = 0;

                    $totalElectric = 0;

                    $totalWorkforces = 0;

                    $totalTornos = 0;

                    $totalDias = 0;

                    $materials = $equip['materials'];

                    $consumables = $equip['consumables'];

                    $electrics = $equip['electrics'];

                    $workforces = $equip['workforces'];

                    $tornos = $equip['tornos'];

                    $dias = $equip['dias'];
                    //dump($materials);
                    foreach ( $materials as $material )
                    {
                        $equipmentMaterial = EquipmentMaterial::create([
                            'equipment_id' => $equipment_quote->id,
                            'material_id' => (int)$material['material']['id'],
                            'quantity' => (float) $material['quantity'],
                            'price' => (float) $material['material']['unit_price'],
                            'length' => (float) ($material['length'] == '') ? 0: $material['length'],
                            'width' => (float) ($material['width'] == '') ? 0: $material['width'],
                            'percentage' => (float) $material['quantity'],
                            'state' => ($material['quantity'] > $material['material']['stock_current']) ? 'Falta comprar':'En compra',
                            'availability' => ($material['quantity'] > $material['material']['stock_current']) ? 'Agotado':'Completo',
                            'total' => (float) $material['quantity']*(float) $material['material']['unit_price']
                        ]);

                        //$totalMaterial += $equipmentMaterial->total;
                    }

                    foreach ( $consumables as $consumable )
                    {
                        $material = Material::find((int)$consumable['id']);

                        // 🟢 VALIDAR PROMOCIÓN SI type_promo = limit
                        if ($consumable["type_promo"] == "limit") {
                            $promotion = PromotionLimit::where('material_id', $consumable["id"])
                                ->whereDate('start_date', '<=', now())
                                ->whereDate('end_date', '>=', now())
                                ->first();

                            if ($promotion) {
                                // buscar uso
                                $query = PromotionUsage::where('promotion_limit_id', $promotion->id);

                                if ($promotion->applies_to == 'worker') {
                                    $query->where('user_id', auth()->id());
                                }

                                $usage = $query->first();

                                if (!$usage) {
                                    $usage = PromotionUsage::create([
                                        'promotion_limit_id' => $promotion->id,
                                        'user_id' => $promotion->applies_to == 'worker' ? auth()->id() : null,
                                        'used_quantity' => 0,
                                    ]);
                                }

                                $requestedQty = (float) $consumable["quantity"];
                                $remaining = $promotion->limit_quantity - $usage->used_quantity;

                                if ($remaining < $requestedQty) {
                                    throw new \Exception("La promoción para {$material->full_name} ya no tiene suficiente cantidad disponible");
                                }

                                // actualizar consumo
                                $usage->increment('used_quantity', $requestedQty);
                            }
                        }

                        $equipmentConsumable = EquipmentConsumable::create([
                            'availability' => ((float) $consumable['quantity'] > $material->stock_current) ? 'Agotado':'Completo',
                            'state' => ((float) $consumable['quantity'] > $material->stock_current) ? 'Falta comprar':'En compra',
                            'equipment_id' => $equipment_quote->id,
                            'material_id' => $consumable['id'],
                            'quantity' => (float) $consumable['quantity'],
                            'price' => (float) $consumable['price'],
                            'valor_unitario' => (float) $consumable['valor'],
                            'discount' => (float) $consumable['discount'],
                            'total' => (float) $consumable['importe'],
                            'type_promo' => $consumables['type_promo'],
                        ]);
                    }

                    foreach ( $electrics as $electric )
                    {
                        $equipmentElectric = EquipmentElectric::create([
                            'equipment_id' => $equipment_quote->id,
                            'material_id' => $electric['id'],
                            'quantity' => (float) $electric['quantity'],
                            'price' => (float) $electric['price'],
                            'total' => (float) $electric['quantity']*(float) $electric['price'],
                        ]);

                        //$totalConsumable += $equipmentConsumable->total;
                    }

                    foreach ( $workforces as $workforce )
                    {
                        $equipmentWorkforce = EquipmentWorkforce::create([
                            'equipment_id' => $equipment_quote->id,
                            'description' => $workforce['description'],
                            'price' => (float) $workforce['price'],
                            'quantity' => (float) $workforce['quantity'],
                            'total' => (float) $workforce['price']*(float) $workforce['quantity'],
                            'unit' => $workforce['unit'],
                        ]);

                        //$totalWorkforces += $equipmentWorkforce->total;
                    }

                    foreach ( $tornos as $torno )
                    {
                        $equipmenttornos = EquipmentTurnstile::create([
                            'equipment_id' => $equipment_quote->id,
                            'description' => $torno['description'],
                            'price' => (float) $torno['price'],
                            'quantity' => (float) $torno['quantity'],
                            'total' => (float) $torno['price']*(float) $torno['quantity']
                        ]);

                        //$totalTornos += $equipmenttornos->total;
                    }

                    foreach ( $dias as $dia )
                    {
                        $equipmentdias = EquipmentWorkday::create([
                            'equipment_id' => $equipment_quote->id,
                            'description' => $dia['description'],
                            'quantityPerson' => (float) $dia['quantity'],
                            'hoursPerPerson' => (float) $dia['hours'],
                            'pricePerHour' => (float) $dia['price'],
                            'total' => (float) $dia['quantity']*(float) $dia['hours']*(float) $dia['price']
                        ]);

                        //$totalDias += $equipmentdias->total;
                    }
                }

                $quote->descuento = ($request->has('descuento')) ? $request->get('descuento') : null;
                $quote->gravada = ($request->has('gravada')) ? $request->get('gravada') : null;
                $quote->igv_total = ($request->has('igv_total')) ? $request->get('igv_total') : null;
                $quote->total_importe = ($request->has('total_importe')) ? $request->get('total_importe') : null;

                $quote->save();
            }

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Modificar equipo de cotizacion',
                'time' => $end
            ]);
            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage().' '.$e->getLine()], 422);
        }
        return response()->json(['message' => 'Equipo guardado con éxito.', 'equipment'=>$equipmentSent, 'quote'=>$quote], 200);

    }

    public function updateEquipmentOfQuote2(Request $request, $id_equipment, $id_quote)
    {
        $begin = microtime(true);
        $user = Auth::user();
        $quote = Quote::find($id_quote);
        $quote_user = QuoteUser::where('quote_id', $id_quote)
            ->where('user_id', $user->id)->first();

        $equipmentSent = null;

        DB::beginTransaction();
        try {
            $equipment_quote = Equipment::where('id', $id_equipment)
                ->where('quote_id',$quote->id)->first();

            $output_details= OutputDetail::where('equipment_id', $equipment_quote->id)->get();

            if ( count($output_details) == 0 )
            {
                // TODO: Si no hay outputs details que proceda como estaba planificado
                foreach( $equipment_quote->materials as $material ) {
                    $material->delete();
                }
                foreach( $equipment_quote->consumables as $consumable ) {
                    // 🧮 devolver reserva de este consumible
                    $material = Material::lockForUpdate()->find($consumable->material_id);

                    if ($material) {
                        // buscar la reserva total de este material para esta cotización
                        $reservation = QuoteMaterialReservation::where('quote_id', $quote->id)
                            ->where('material_id', $consumable->material_id)
                            ->lockForUpdate()
                            ->first();

                        if ($reservation) {
                            $reservation->quantity -= (float) $consumable->quantity;

                            if ($reservation->quantity <= 0) {
                                $reservation->delete();
                            } else {
                                $reservation->save();
                            }
                        }

                        // restar del stock_reserved del material
                        $material->stock_reserved -= (float) $consumable->quantity;
                        if ($material->stock_reserved < 0) {
                            $material->stock_reserved = 0; // por seguridad
                        }
                        $material->save();
                    }
                    // 🟢 eliminar usages ligados
                    PromotionUsage::where('equipment_consumable_id', $consumable->id)->delete();
                    $consumable->delete();
                }
                foreach( $equipment_quote->electrics as $electric ) {
                    $electric->delete();
                }
                foreach( $equipment_quote->workforces as $workforce ) {
                    $workforce->delete();
                }
                foreach( $equipment_quote->turnstiles as $turnstile ) {
                    $turnstile->delete();
                }
                foreach( $equipment_quote->workdays as $workday ) {
                    $workday->delete();
                }

                $equipment_quote->delete();

                $equipments = $request->input('equipment');

                $totalQuote = 0;

                foreach ( $equipments as $equip )
                {
                    $equipment = Equipment::create([
                        'quote_id' => $quote->id,
                        'description' => ($equip['description'] == "" || $equip['description'] == null) ? '':$equip['description'],
                        'detail' => ($equip['detail'] == "" || $equip['detail'] == null) ? '':$equip['detail'],
                        'quantity' => $equip['quantity'],
                        'utility' => $equip['utility'],
                        'rent' => $equip['rent'],
                        'letter' => $equip['letter'],
                        'total' => $equip['total']
                    ]);

                    $materials = $equip['materials'];
                    $consumables = $equip['consumables'];
                    $electrics = $equip['electrics'];
                    $workforces = $equip['workforces'];
                    $tornos = $equip['tornos'];
                    $dias = $equip['dias'];

                    foreach ( $materials as $material )
                    {
                        EquipmentMaterial::create([
                            'equipment_id' => $equipment->id,
                            'material_id' => (int)$material['material']['id'],
                            'quantity' => (float) $material['quantity'],
                            'price' => (float) $material['material']['unit_price'],
                            'length' => (float) ($material['length'] == '') ? 0: $material['length'],
                            'width' => (float) ($material['width'] == '') ? 0: $material['width'],
                            'percentage' => (float) $material['quantity'],
                            'state' => ($material['quantity'] > $material['material']['stock_current']) ? 'Falta comprar':'En compra',
                            'availability' => ($material['quantity'] > $material['material']['stock_current']) ? 'Agotado':'Completo',
                            'total' => (float) $material['quantity']*(float) $material['material']['unit_price']
                        ]);
                    }

                    foreach ( $consumables as $consumable )
                    {
                        // 🔒 bloquear material para evitar race conditions
                        $material = Material::lockForUpdate()->find((int)$consumable['id']);

                        if (!$material) {
                            throw new \Exception("El material con ID {$consumable['id']} no existe.");
                        }

                        $requestedQty = (float) $consumable['quantity'];
                        $available = (float) $material->stock_current - (float) $material->stock_reserved;

                        if ($requestedQty > $available) {
                            throw new \Exception("El material {$material->full_name} no cuenta con stock suficiente para la cantidad solicitada ({$requestedQty}). Stock disponible: {$available}.");
                        }

                        // 🟢 PRIMERO crear consumible
                        $equipmentConsumable = EquipmentConsumable::create([
                            'availability' => ((float) $consumable['quantity'] > $material->stock_current) ? 'Agotado':'Completo',
                            'state' => ((float) $consumable['quantity'] > $material->stock_current) ? 'Falta comprar':'En compra',
                            'equipment_id' => $equipment->id,
                            'material_id' => $consumable['id'],
                            'quantity' => (float) $consumable['quantity'],
                            'price' => (float) $consumable['price'],
                            'valor_unitario' => (float) $consumable['valor'],
                            'discount' => (float) $consumable['discount'],
                            'total' => (float) $consumable['importe'],
                            'type_promo' => $consumable['type_promo'],
                        ]);

                        // 🟢 REGISTRAR/ACTUALIZAR RESERVA POR COTIZACIÓN
                        $reservation = QuoteMaterialReservation::where('quote_id', $quote->id)
                            ->where('material_id', $material->id)
                            ->lockForUpdate()
                            ->first();

                        if ($reservation) {
                            $reservation->quantity += $requestedQty;
                            $reservation->save();
                        } else {
                            $reservation = QuoteMaterialReservation::create([
                                'quote_id'    => $quote->id,
                                'material_id' => $material->id,
                                'quantity'    => $requestedQty,
                            ]);
                        }

                        // 🟢 ACTUALIZAR EL STOCK RESERVADO DEL MATERIAL
                        $material->stock_reserved += $requestedQty;
                        $material->save();

                        // 🟢 Luego validar promoción
                        if ($consumable["type_promo"] == "limit") {
                            $promotion = PromotionLimit::where('material_id', $consumable["id"])
                                ->whereDate('start_date', '<=', now())
                                ->whereDate('end_date', '>=', now())
                                ->first();

                            if ($promotion) {
                                $query = PromotionUsage::where('promotion_limit_id', $promotion->id)
                                    ->where('quote_id', $quote->id)
                                    ->where('equipment_id', $equipment->id)
                                    ->where('equipment_consumable_id', $equipmentConsumable->id);

                                if ($promotion->applies_to == 'worker') {
                                    $query->where('user_id', auth()->id());
                                }

                                $usage = $query->first();

                                if (!$usage) {
                                    $usage = PromotionUsage::create([
                                        'promotion_limit_id' => $promotion->id,
                                        'quote_id' => $quote->id,
                                        'equipment_id' => $equipment->id,
                                        'equipment_consumable_id' => $equipmentConsumable->id,
                                        'user_id' => $promotion->applies_to == 'worker' ? auth()->id() : null,
                                        'used_quantity' => 0,
                                    ]);
                                }

                                $requestedQty = (float) $consumable["quantity"];
                                $remaining = $promotion->limit_quantity - $usage->used_quantity;

                                if ($remaining < $requestedQty) {
                                    throw new \Exception("La promoción para {$material->full_name} ya no tiene suficiente cantidad disponible");
                                }

                                $usage->increment('used_quantity', $requestedQty);
                            }
                        }
                    }

                    foreach ( $electrics as $electricd )
                    {
                        EquipmentElectric::create([
                            'equipment_id' => $equipment->id,
                            'material_id' => $electricd['id'],
                            'quantity' => (float) $electricd['quantity'],
                            'price' => (float) $electricd['price'],
                            'total' => (float) $electricd['quantity']*(float) $electricd['price'],
                        ]);
                    }

                    foreach ( $workforces as $workforce )
                    {
                        EquipmentWorkforce::create([
                            'equipment_id' => $equipment->id,
                            'description' => $workforce['description'],
                            'price' => (float) $workforce['price'],
                            'quantity' => (float) $workforce['quantity'],
                            'total' => (float) $workforce['price']*(float) $workforce['quantity'],
                            'unit' => $workforce['unit'],
                        ]);
                    }

                    foreach ( $tornos as $torno )
                    {
                        EquipmentTurnstile::create([
                            'equipment_id' => $equipment->id,
                            'description' => $torno['description'],
                            'price' => (float) $torno['price'],
                            'quantity' => (float) $torno['quantity'],
                            'total' => (float) $torno['price']*(float) $torno['quantity']
                        ]);
                    }

                    foreach ( $dias as $dia )
                    {
                        EquipmentWorkday::create([
                            'equipment_id' => $equipment->id,
                            'description' => $dia['description'],
                            'quantityPerson' => (float) $dia['quantity'],
                            'hoursPerPerson' => (float) $dia['hours'],
                            'pricePerHour' => (float) $dia['price'],
                            'total' => (float) $dia['quantity']*(float) $dia['hours']*(float) $dia['price']
                        ]);
                    }

                    $totalEquipo2 = (float)$equip['total'];
                    $totalEquipmentU2 = $totalEquipo2*(($equip['utility']/100)+1);
                    $totalEquipmentL2 = $totalEquipmentU2*(($equip['letter']/100)+1);
                    $totalEquipmentR2 = $totalEquipmentL2*(($equip['rent']/100)+1);

                    $totalQuote = $totalQuote + $totalEquipmentR2;

                    $equipment->total = $totalEquipo2;
                    $equipment->save();

                    $equipmentSent = $equipment;
                }

                // Guardamos los totales
                $quote->descuento = ($request->has('descuento')) ? $request->get('descuento') : null;
                $quote->gravada = ($request->has('gravada')) ? $request->get('gravada') : null;
                $quote->igv_total = ($request->has('igv_total')) ? $request->get('igv_total') : null;
                $quote->total_importe = ($request->has('total_importe')) ? $request->get('total_importe') : null;
                $quote->save();

            } else {
                // 🟢 Ya no eliminamos el equipo, solo lo modificamos
                foreach( $equipment_quote->materials as $material ) {
                    $material->delete();
                }
                foreach( $equipment_quote->consumables as $consumable ) {
                    // 🧮 devolver reserva de este consumible
                    $material = Material::lockForUpdate()->find($consumable->material_id);

                    if ($material) {
                        // buscar la reserva total de este material para esta cotización
                        $reservation = QuoteMaterialReservation::where('quote_id', $quote->id)
                            ->where('material_id', $consumable->material_id)
                            ->lockForUpdate()
                            ->first();

                        if ($reservation) {
                            $reservation->quantity -= (float) $consumable->quantity;

                            if ($reservation->quantity <= 0) {
                                $reservation->delete();
                            } else {
                                $reservation->save();
                            }
                        }

                        // restar del stock_reserved del material
                        $material->stock_reserved -= (float) $consumable->quantity;
                        if ($material->stock_reserved < 0) {
                            $material->stock_reserved = 0; // por seguridad
                        }
                        $material->save();
                    }
                    PromotionUsage::where('equipment_consumable_id', $consumable->id)->delete();
                    $consumable->delete();
                }
                foreach( $equipment_quote->electrics as $electric ) {
                    $electric->delete();
                }
                foreach( $equipment_quote->workforces as $workforce ) {
                    $workforce->delete();
                }
                foreach( $equipment_quote->turnstiles as $turnstile ) {
                    $turnstile->delete();
                }
                foreach( $equipment_quote->workdays as $workday ) {
                    $workday->delete();
                }

                $quote->save();

                $equipments = $request->input('equipment');
                $totalQuote = 0;

                foreach ( $equipments as $equip )
                {
                    $equipment_quote->quote_id = $quote->id;
                    $equipment_quote->description = ($equip['description'] == "" || $equip['description'] == null) ? '':$equip['description'];
                    $equipment_quote->detail = ($equip['detail'] == "" || $equip['detail'] == null) ? '':$equip['detail'];
                    $equipment_quote->quantity = $equip['quantity'];
                    $equipment_quote->utility = $equip['utility'];
                    $equipment_quote->rent = $equip['rent'];
                    $equipment_quote->letter = $equip['letter'];
                    $equipment_quote->total = $equip['total'];
                    $equipment_quote->save();

                    $materials = $equip['materials'];
                    $consumables = $equip['consumables'];
                    $electrics = $equip['electrics'];
                    $workforces = $equip['workforces'];
                    $tornos = $equip['tornos'];
                    $dias = $equip['dias'];

                    foreach ( $materials as $material )
                    {
                        EquipmentMaterial::create([
                            'equipment_id' => $equipment_quote->id,
                            'material_id' => (int)$material['material']['id'],
                            'quantity' => (float) $material['quantity'],
                            'price' => (float) $material['material']['unit_price'],
                            'length' => (float) ($material['length'] == '') ? 0: $material['length'],
                            'width' => (float) ($material['width'] == '') ? 0: $material['width'],
                            'percentage' => (float) $material['quantity'],
                            'state' => ($material['quantity'] > $material['material']['stock_current']) ? 'Falta comprar':'En compra',
                            'availability' => ($material['quantity'] > $material['material']['stock_current']) ? 'Agotado':'Completo',
                            'total' => (float) $material['quantity']*(float) $material['material']['unit_price']
                        ]);
                    }

                    foreach ( $consumables as $consumable )
                    {
                        // 🔒 bloquear material para evitar race conditions
                        $material = Material::lockForUpdate()->find((int)$consumable['id']);

                        if (!$material) {
                            throw new \Exception("El material con ID {$consumable['id']} no existe.");
                        }

                        $requestedQty = (float) $consumable['quantity'];
                        $available = (float) $material->stock_current - (float) $material->stock_reserved;

                        if ($requestedQty > $available) {
                            throw new \Exception("El material {$material->full_name} no cuenta con stock suficiente para la cantidad solicitada ({$requestedQty}). Stock disponible: {$available}.");
                        }

                        $equipmentConsumable = EquipmentConsumable::create([
                            'availability' => ((float) $consumable['quantity'] > $material->stock_current) ? 'Agotado':'Completo',
                            'state' => ((float) $consumable['quantity'] > $material->stock_current) ? 'Falta comprar':'En compra',
                            'equipment_id' => $equipment_quote->id,
                            'material_id' => $consumable['id'],
                            'quantity' => (float) $consumable['quantity'],
                            'price' => (float) $consumable['price'],
                            'valor_unitario' => (float) $consumable['valor'],
                            'discount' => (float) $consumable['discount'],
                            'total' => (float) $consumable['importe'],
                            'type_promo' => $consumable['type_promo'],
                        ]);

                        // 🟢 REGISTRAR/ACTUALIZAR RESERVA POR COTIZACIÓN
                        $reservation = QuoteMaterialReservation::where('quote_id', $quote->id)
                            ->where('material_id', $material->id)
                            ->lockForUpdate()
                            ->first();

                        if ($reservation) {
                            $reservation->quantity += $requestedQty;
                            $reservation->save();
                        } else {
                            $reservation = QuoteMaterialReservation::create([
                                'quote_id'    => $quote->id,
                                'material_id' => $material->id,
                                'quantity'    => $requestedQty,
                            ]);
                        }

                        // 🟢 ACTUALIZAR EL STOCK RESERVADO DEL MATERIAL
                        $material->stock_reserved += $requestedQty;
                        $material->save();

                        if ($consumable["type_promo"] == "limit") {
                            $promotion = PromotionLimit::where('material_id', $consumable["id"])
                                ->whereDate('start_date', '<=', now())
                                ->whereDate('end_date', '>=', now())
                                ->first();

                            if ($promotion) {
                                $query = PromotionUsage::where('promotion_limit_id', $promotion->id)
                                    ->where('quote_id', $quote->id)
                                    ->where('equipment_id', $equipment_quote->id)
                                    ->where('equipment_consumable_id', $equipmentConsumable->id);

                                if ($promotion->applies_to == 'worker') {
                                    $query->where('user_id', auth()->id());
                                }

                                $usage = $query->first();

                                if (!$usage) {
                                    $usage = PromotionUsage::create([
                                        'promotion_limit_id' => $promotion->id,
                                        'quote_id' => $quote->id,
                                        'equipment_id' => $equipment_quote->id,
                                        'equipment_consumable_id' => $equipmentConsumable->id,
                                        'user_id' => $promotion->applies_to == 'worker' ? auth()->id() : null,
                                        'used_quantity' => 0,
                                    ]);
                                }

                                $requestedQty = (float) $consumable["quantity"];
                                $remaining = $promotion->limit_quantity - $usage->used_quantity;

                                if ($remaining < $requestedQty) {
                                    throw new \Exception("La promoción para {$material->full_name} ya no tiene suficiente cantidad disponible");
                                }

                                $usage->increment('used_quantity', $requestedQty);
                            }
                        }
                    }

                    foreach ( $electrics as $electric )
                    {
                        EquipmentElectric::create([
                            'equipment_id' => $equipment_quote->id,
                            'material_id' => $electric['id'],
                            'quantity' => (float) $electric['quantity'],
                            'price' => (float) $electric['price'],
                            'total' => (float) $electric['quantity']*(float) $electric['price'],
                        ]);
                    }

                    foreach ( $workforces as $workforce )
                    {
                        EquipmentWorkforce::create([
                            'equipment_id' => $equipment_quote->id,
                            'description' => $workforce['description'],
                            'price' => (float) $workforce['price'],
                            'quantity' => (float) $workforce['quantity'],
                            'total' => (float) $workforce['price']*(float) $workforce['quantity'],
                            'unit' => $workforce['unit'],
                        ]);
                    }

                    foreach ( $tornos as $torno )
                    {
                        EquipmentTurnstile::create([
                            'equipment_id' => $equipment_quote->id,
                            'description' => $torno['description'],
                            'price' => (float) $torno['price'],
                            'quantity' => (float) $torno['quantity'],
                            'total' => (float) $torno['price']*(float) $torno['quantity']
                        ]);
                    }

                    foreach ( $dias as $dia )
                    {
                        EquipmentWorkday::create([
                            'equipment_id' => $equipment_quote->id,
                            'description' => $dia['description'],
                            'quantityPerson' => (float) $dia['quantity'],
                            'hoursPerPerson' => (float) $dia['hours'],
                            'pricePerHour' => (float) $dia['price'],
                            'total' => (float) $dia['quantity']*(float) $dia['hours']*(float) $dia['price']
                        ]);
                    }
                }

                $quote->descuento = ($request->has('descuento')) ? $request->get('descuento') : null;
                $quote->gravada = ($request->has('gravada')) ? $request->get('gravada') : null;
                $quote->igv_total = ($request->has('igv_total')) ? $request->get('igv_total') : null;
                $quote->total_importe = ($request->has('total_importe')) ? $request->get('total_importe') : null;
                $quote->save();
            }

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Modificar equipo de cotizacion',
                'time' => $end
            ]);
            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage().' '.$e->getLine()], 422);
        }
        return response()->json(['message' => 'Equipo guardado con éxito.', 'equipment'=>$equipmentSent, 'quote'=>$quote], 200);

    }

    public function updateEquipmentOfQuote3(Request $request, $id_equipment, $id_quote)
    {
        //dd($request);
        $begin = microtime(true);

        $quote = Quote::findOrFail($id_quote);

        DB::beginTransaction();
        try {

            $dateQuote = $request->get('date_quote')
                ? Carbon::createFromFormat('d/m/Y', $request->get('date_quote'))
                : null;

            $dateValidate = $request->get('date_validate')
                ? Carbon::createFromFormat('d/m/Y', $request->get('date_validate'))
                : null;

            $quote->update([
                'description_quote'   => $request->get('descriptionQuote'),
                'code'                => $request->get('codeQuote'),
                'date_quote'          => $dateQuote,
                'date_validate'       => $dateValidate,
                'way_to_pay'          => $request->get('way_to_pay') ?? '',
                'delivery_time'       => $request->get('delivery_time') ?? '',
                'customer_id'         => $request->get('customer_id') ?? null,
                'contact_id'          => $request->get('contact_id') ?? null,
                'payment_deadline_id' => $request->get('payment_deadline') ?? null,
                'observations'        => $request->get('observations') ?? '',
            ]);


            $equipment_quote = Equipment::where('id', $id_equipment)
                ->where('quote_id', $quote->id)
                ->firstOrFail();

            /**
             * 1) Guardar totales de Quote + metadata del descuento
             * AHORA vienen del front como:
             * - descuento, gravada, igv_total, total_importe (reales 10 dec)
             */
            $quote->descuento      = (string)($request->input('descuento', '0'));
            $quote->gravada        = (string)($request->input('gravada', '0'));
            $quote->igv_total      = (string)($request->input('igv_total', '0'));
            $quote->total_importe  = (string)($request->input('total_importe', '0'));

            $quote->discount_type       = $request->input('discount_type');        // amount|percent
            $quote->discount_input_mode = $request->input('discount_input_mode');  // with_igv|without_igv
            $quote->discount_input_value= $request->input('discount_input_value'); // numeric
            $quote->save();

            /**
             * 2) Eliminar consumables antiguos y devolver reservas
             * quantity en BD = unidades equivalentes (stock)
             */
            foreach ($equipment_quote->consumables as $consumable) {

                $material = Material::lockForUpdate()->find($consumable->material_id);

                if ($material) {
                    $reservation = QuoteMaterialReservation::where('quote_id', $quote->id)
                        ->where('material_id', $consumable->material_id)
                        ->lockForUpdate()
                        ->first();

                    if ($reservation) {
                        $reservation->quantity -= (float)$consumable->quantity;
                        if ($reservation->quantity <= 0) $reservation->delete();
                        else $reservation->save();
                    }

                    $material->stock_reserved -= (float)$consumable->quantity;
                    if ($material->stock_reserved < 0) $material->stock_reserved = 0;
                    $material->save();
                }

                $consumable->delete();
            }

            // Limpiar relaciones restantes (siempre recreamos)
            foreach ($equipment_quote->workforces as $workforce) $workforce->delete();
            foreach ($equipment_quote->materials as $m) $m->delete();
            foreach ($equipment_quote->electrics as $e) $e->delete();
            foreach ($equipment_quote->turnstiles as $t) $t->delete();
            foreach ($equipment_quote->workdays as $w) $w->delete();

            /**
             * 3) Payload (array con 1 equipo)
             */
            $equipments = $request->input('equipment');
            if (!is_array($equipments) || count($equipments) === 0) {
                throw new \Exception('No se recibieron equipos.');
            }
            $equip = $equipments[0];

            /**
             * 4) Actualizar equipo principal
             */
            $equipment_quote->description = $equip['description'] ?? '';
            $equipment_quote->detail      = $equip['detail'] ?? '';
            $equipment_quote->quantity    = $equip['quantity'] ?? 1;
            $equipment_quote->utility     = $equip['utility'] ?? 0;
            $equipment_quote->rent        = $equip['rent'] ?? 0;
            $equipment_quote->letter      = $equip['letter'] ?? 0;

            // total del equipo con IGV (real 10 dec)
            $equipment_quote->total       = (string)($equip['total'] ?? '0');
            $equipment_quote->save();

            /**
             * 5) Re-crear consumables
             * - En BD: quantity SIEMPRE unidades equivalentes (stock)
             * - price / valor_unitario / total: VIENEN DEL FRONT EN REAL (10 dec)
             */
            $consumables = $equip['consumables'] ?? [];

            // Para sumar con precisión si lo necesitas
            @bcscale(10);

            foreach ($consumables as $c) {

                $materialId = (int)($c['id'] ?? 0);
                $material = Material::lockForUpdate()->find($materialId);

                if (!$material) {
                    throw new \Exception("El material con ID {$materialId} no existe.");
                }

                // --- presentación solo para stock/reserva ---
                $presentationId = $c['presentation_id'] ?? null;
                $packs = null;
                $unitsPerPack = null;

                // units equivalentes a reservar
                $requestedUnits = 0;

                if (!empty($presentationId)) {

                    // En UI quantity = packs
                    $packs = (int)($c['quantity'] ?? 0);
                    if ($packs < 1) {
                        throw new \Exception("Packs inválidos para {$material->full_name}.");
                    }

                    // Validar presentación real
                    $presentation = MaterialPresentation::where('id', $presentationId)
                        ->where('material_id', $materialId)
                        ->where('active', 1)
                        ->first();

                    if (!$presentation) {
                        throw new \Exception("Presentación inválida para {$material->full_name}.");
                    }

                    $unitsPerPack = (int)$presentation->quantity;
                    if ($unitsPerPack < 1) {
                        throw new \Exception("Units_per_pack inválido para {$material->full_name}.");
                    }

                    $requestedUnits = (float)($packs * $unitsPerPack);

                } else {

                    // Unitario: units_equivalent debe ser unidades (si no viene, usa quantity)
                    $requestedUnits = (float)($c['units_equivalent'] ?? ($c['quantity'] ?? 0));
                    if ($requestedUnits <= 0) {
                        throw new \Exception("Cantidad inválida para {$material->full_name}.");
                    }
                }

                // --- Dinero (NO recalcular): guardar lo real del front ---
                $valorUnitarioReal = (string)($c['valorReal'] ?? $c['valor'] ?? '0'); // sin IGV
                $priceReal         = (string)($c['priceReal'] ?? $c['price'] ?? '0'); // con IGV
                $importeReal       = (string)($c['importe'] ?? '0');                  // total con IGV

                if ((float)$valorUnitarioReal < 0 || (float)$priceReal < 0 || (float)$importeReal < 0) {
                    throw new \Exception("Valores inválidos (precio/valor/total) para {$material->full_name}.");
                }

                // validar stock disponible (considera reservas)
                $available = (float)$material->stock_current - (float)$material->stock_reserved;
                if ($requestedUnits > $available) {
                    throw new \Exception("Stock insuficiente para {$material->full_name}. Requerido: {$requestedUnits}. Disponible: {$available}.");
                }

                $availability = ($requestedUnits > (float)$material->stock_current) ? 'Agotado' : 'Completo';
                $state        = ($requestedUnits > (float)$material->stock_current) ? 'Falta comprar' : 'En compra';

                // crear consumable (BD guarda unidades equivalentes)
                $equipmentConsumable = EquipmentConsumable::create([
                    'equipment_id' => $equipment_quote->id,
                    'material_id' => $materialId,

                    'material_presentation_id' => $presentationId,
                    'packs' => $packs,
                    'units_per_pack' => $unitsPerPack,

                    // ✅ stock (unidades equivalentes)
                    'quantity' => (float)$requestedUnits,

                    // ✅ dinero real (decimal 20,10)
                    'price' => $priceReal,
                    'valor_unitario' => $valorUnitarioReal,
                    'total' => $importeReal,

                    'discount' => (string)($c['discount'] ?? '0'),
                    'type_promo' => $c['type_promo'] ?? null,
                    'availability' => $availability,
                    'state' => $state,
                ]);

                // crear/actualizar reserva
                $reservation = QuoteMaterialReservation::where('quote_id', $quote->id)
                    ->where('material_id', $materialId)
                    ->lockForUpdate()
                    ->first();

                if ($reservation) {
                    $reservation->quantity += $requestedUnits;
                    $reservation->save();
                } else {
                    QuoteMaterialReservation::create([
                        'quote_id' => $quote->id,
                        'material_id' => $materialId,
                        'quantity' => $requestedUnits,
                    ]);
                }

                // actualizar stock_reserved
                $material->stock_reserved += $requestedUnits;
                $material->save();
            }

            /**
             * 6) Re-crear workforces
             * (si tus workforces también tienen reales, aplícalo igual)
             */
            $workforces = $equip['workforces'] ?? [];

            foreach ($workforces as $w) {
                EquipmentWorkforce::create([
                    'equipment_id' => $equipment_quote->id,
                    'description' => $w['description'] ?? '',
                    'price' => (string)($w['price'] ?? '0'),        // con IGV
                    'quantity' => (float)($w['quantity'] ?? 0),
                    'total' => (string)($w['importe'] ?? '0'),      // con IGV
                    'unit' => $w['unit'] ?? '',
                    'billable' => isset($w['billable']) ? (int)$w['billable'] : 1,
                ]);
            }

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Modificar equipo de cotizacion (EDIT)',
                'time' => microtime(true) - $begin
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Equipo guardado con éxito.',
                'equipment' => $equipment_quote,
                'quote' => $quote
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 422);
        }
    }

    public function updateEquipmentOfQuote1(Request $request, $id_equipment, $id_quote)
    {
        $begin = microtime(true);

        $quote = Quote::findOrFail($id_quote);

        DB::beginTransaction();

        try {
            /** @var QuoteStockReservationService $reservationService */
            $reservationService = app(QuoteStockReservationService::class);

            $dateQuote = $request->get('date_quote')
                ? Carbon::createFromFormat('d/m/Y', $request->get('date_quote'))
                : null;

            $dateValidate = $request->get('date_validate')
                ? Carbon::createFromFormat('d/m/Y', $request->get('date_validate'))
                : null;

            $quote->update([
                'description_quote' => $request->get('descriptionQuote'),
                'code' => $request->get('codeQuote'),
                'date_quote' => $dateQuote,
                'date_validate' => $dateValidate,
                'way_to_pay' => $request->get('way_to_pay') ?? '',
                'delivery_time' => $request->get('delivery_time') ?? '',
                'customer_id' => $request->get('customer_id') ?? null,
                'contact_id' => $request->get('contact_id') ?? null,
                'payment_deadline_id' => $request->get('payment_deadline') ?? null,
                'observations' => $request->get('observations') ?? '',
            ]);

            $equipment_quote = Equipment::where('id', $id_equipment)
                ->where('quote_id', $quote->id)
                ->firstOrFail();

            /*
             * =========================================================
             * 1) Totales generales de cotización
             * =========================================================
             */
            $quote->descuento = (string) $request->input('descuento', '0');
            $quote->gravada = (string) $request->input('gravada', '0');
            $quote->igv_total = (string) $request->input('igv_total', '0');
            $quote->total_importe = (string) $request->input('total_importe', '0');

            $quote->discount_type = $request->input('discount_type');
            $quote->discount_input_mode = $request->input('discount_input_mode');
            $quote->discount_input_value = $request->input('discount_input_value');
            $quote->save();

            /*
             * =========================================================
             * 2) Liberar reservas y eliminar consumables anteriores
             *
             * releaseReservationsByQuoteDetail:
             * - devuelve qty_reserved al lote
             * - si QuoteStockLot tiene item_ids, cambia Items:
             *   reserved -> entered
             * - elimina QuoteStockLot
             * =========================================================
             */
            foreach ($equipment_quote->consumables as $consumable) {
                $reservationService->releaseReservationsByQuoteDetail(
                    (int) $quote->id,
                    (int) $consumable->id
                );

                $consumable->delete();
            }

            /*
             * Limpiar relaciones restantes.
             */
            foreach ($equipment_quote->workforces as $workforce) {
                $workforce->delete();
            }

            foreach ($equipment_quote->materials as $material) {
                $material->delete();
            }

            foreach ($equipment_quote->electrics as $electric) {
                $electric->delete();
            }

            foreach ($equipment_quote->turnstiles as $turnstile) {
                $turnstile->delete();
            }

            foreach ($equipment_quote->workdays as $workday) {
                $workday->delete();
            }

            /*
             * =========================================================
             * 3) Obtener payload
             * =========================================================
             */
            $equipments = $request->input('equipment');

            if (!is_array($equipments) || count($equipments) === 0) {
                throw new \Exception('No se recibieron equipos.');
            }

            $equip = $equipments[0];

            /*
             * =========================================================
             * 4) Actualizar equipo principal
             * =========================================================
             */
            $equipment_quote->description = $equip['description'] ?? '';
            $equipment_quote->detail = $equip['detail'] ?? '';
            $equipment_quote->quantity = $equip['quantity'] ?? 1;
            $equipment_quote->utility = $equip['utility'] ?? 0;
            $equipment_quote->rent = $equip['rent'] ?? 0;
            $equipment_quote->letter = $equip['letter'] ?? 0;
            $equipment_quote->total = (string) ($equip['total'] ?? '0');
            $equipment_quote->save();

            /*
             * =========================================================
             * 5) Re-crear consumables y sus reservas
             * =========================================================
             */
            $consumables = $equip['consumables'] ?? [];

            @bcscale(10);

            foreach ($consumables as $c) {
                /*
                 * En la nueva lógica, id representa StockItem.
                 * Se deja fallback para payloads antiguos que manden
                 * stock_item_id por separado.
                 */
                $stockItemId = (int) (
                    $c['stock_item_id']
                    ?? $c['stockItemId']
                    ?? $c['id']
                    ?? 0
                );

                if ($stockItemId <= 0) {
                    throw new \Exception('No se pudo identificar el stock item del producto.');
                }

                $stockItem = StockItem::with(['material'])
                    ->where('id', $stockItemId)
                    ->first();

                if (!$stockItem) {
                    throw new \Exception(
                        "El stock item con ID {$stockItemId} no existe."
                    );
                }

                if (!$stockItem->material) {
                    throw new \Exception(
                        "El stock item con ID {$stockItemId} no tiene material asociado."
                    );
                }

                $material = $stockItem->material;
                $materialId = (int) $material->id;

                /*
                 * La validación itemeable se realiza en backend.
                 * No confiamos en is_itemeable enviado por JavaScript.
                 */
                $isItemeable = (int) ($material->tipo_venta_id ?? 0) === 3;

                /*
                 * ---------------------------------------------------------
                 * Presentación y unidades equivalentes
                 * ---------------------------------------------------------
                 */
                $presentationId = $c['presentation_id'] ?? null;
                $packs = null;
                $unitsPerPack = null;
                $requestedUnits = 0;

                if (!empty($presentationId)) {
                    $packs = (int) ($c['quantity'] ?? 0);

                    if ($packs < 1) {
                        throw new \Exception(
                            "Packs inválidos para {$stockItem->display_name}."
                        );
                    }

                    $presentation = MaterialPresentation::where('id', $presentationId)
                        ->where('material_id', $materialId)
                        ->where('active', 1)
                        ->first();

                    if (!$presentation) {
                        throw new \Exception(
                            "Presentación inválida para {$stockItem->display_name}."
                        );
                    }

                    $unitsPerPack = (int) $presentation->quantity;

                    if ($unitsPerPack < 1) {
                        throw new \Exception(
                            "Units per pack inválido para {$stockItem->display_name}."
                        );
                    }

                    $requestedUnits = (float) ($packs * $unitsPerPack);
                } else {
                    $requestedUnits = (float) (
                        $c['units_equivalent']
                        ?? $c['quantity']
                        ?? 0
                    );

                    if ($requestedUnits <= 0) {
                        throw new \Exception(
                            "Cantidad inválida para {$stockItem->display_name}."
                        );
                    }
                }

                /*
                 * ---------------------------------------------------------
                 * Valores monetarios enviados desde frontend
                 * ---------------------------------------------------------
                 */
                $valorUnitarioReal = (string) (
                    $c['valorReal']
                    ?? $c['valor']
                    ?? '0'
                );

                $priceReal = (string) (
                    $c['priceReal']
                    ?? $c['price']
                    ?? '0'
                );

                $importeReal = (string) (
                    $c['importe']
                    ?? '0'
                );

                if (
                    (float) $valorUnitarioReal < 0 ||
                    (float) $priceReal < 0 ||
                    (float) $importeReal < 0
                ) {
                    throw new \Exception(
                        "Valores inválidos (precio/valor/total) para {$stockItem->display_name}."
                    );
                }

                /*
                 * ---------------------------------------------------------
                 * Validar itemeables
                 * ---------------------------------------------------------
                 */
                $selectedItemIds = [];

                if ($isItemeable) {
                    $rawSelectedItemIds = $c['selected_item_ids'] ?? [];

                    /*
                     * Por seguridad, soportamos que llegue como JSON string
                     * o como array.
                     */
                    if (is_string($rawSelectedItemIds)) {
                        $decodedIds = json_decode($rawSelectedItemIds, true);

                        $rawSelectedItemIds = is_array($decodedIds)
                            ? $decodedIds
                            : [];
                    }

                    if (!is_array($rawSelectedItemIds)) {
                        $rawSelectedItemIds = [];
                    }

                    $selectedItemIds = collect($rawSelectedItemIds)
                        ->map(function ($itemId) {
                            return (int) $itemId;
                        })
                        ->filter(function ($itemId) {
                            return $itemId > 0;
                        })
                        ->values()
                        ->toArray();

                    if (empty($selectedItemIds)) {
                        throw new \Exception(
                            "Debe seleccionar los ítems físicos para {$material->full_name}."
                        );
                    }

                    if (count($selectedItemIds) !== count(array_unique($selectedItemIds))) {
                        throw new \Exception(
                            "Se detectaron ítems repetidos para {$material->full_name}."
                        );
                    }

                    /*
                     * Cada Item representa una unidad completa.
                     */
                    if ((int) $requestedUnits !== count($selectedItemIds)) {
                        throw new \Exception(
                            "La cantidad de ítems seleccionados no coincide con la cantidad requerida para {$material->full_name}. " .
                            "Requerido: {$requestedUnits}. Seleccionados: " . count($selectedItemIds) . '.'
                        );
                    }
                }

                /*
                 * ---------------------------------------------------------
                 * Stock disponible por lotes
                 * ---------------------------------------------------------
                 */
                $available = $reservationService->getAvailableStockByStockItem(
                    $stockItemId
                );

                if ($requestedUnits > $available) {
                    throw new \Exception(
                        "Stock insuficiente para {$material->full_name}. " .
                        "Requerido: {$requestedUnits}. Disponible: {$available}."
                    );
                }

                $availability = 'Completo';
                $state = 'En compra';

                /*
                 * ---------------------------------------------------------
                 * Crear nuevo detalle consumable
                 * ---------------------------------------------------------
                 */
                $equipmentConsumable = EquipmentConsumable::create([
                    'equipment_id' => $equipment_quote->id,
                    'material_id' => $materialId,
                    'stock_item_id' => $stockItemId,

                    'material_presentation_id' => $presentationId,
                    'packs' => $packs,
                    'units_per_pack' => $unitsPerPack,

                    'quantity' => (float) $requestedUnits,

                    'price' => $priceReal,
                    'valor_unitario' => $valorUnitarioReal,
                    'total' => $importeReal,

                    'discount' => (string) ($c['discount'] ?? '0'),
                    'type_promo' => $c['type_promo'] ?? null,

                    'availability' => $availability,
                    'state' => $state,
                ]);

                /*
                 * ---------------------------------------------------------
                 * Reservar stock
                 * ---------------------------------------------------------
                 */
                if ($isItemeable) {
                    $reservationService->reserveItemeableForQuoteDetail(
                        (int) $quote->id,
                        (int) $equipmentConsumable->id,
                        (int) $stockItemId,
                        $selectedItemIds
                    );
                } else {
                    $reservationService->reserveForQuoteDetail(
                        (int) $quote->id,
                        (int) $equipmentConsumable->id,
                        (int) $stockItemId,
                        (float) $requestedUnits
                    );
                }
            }

            /*
             * =========================================================
             * 6) Re-crear servicios adicionales
             * =========================================================
             */
            $workforces = $equip['workforces'] ?? [];

            foreach ($workforces as $workforce) {
                EquipmentWorkforce::create([
                    'equipment_id' => $equipment_quote->id,
                    'description' => $workforce['description'] ?? '',
                    'price' => (string) ($workforce['price'] ?? '0'),
                    'quantity' => (float) ($workforce['quantity'] ?? 0),
                    'total' => (string) ($workforce['importe'] ?? '0'),
                    'unit' => $workforce['unit'] ?? '',
                    'billable' => isset($workforce['billable'])
                        ? (int) $workforce['billable']
                        : 1,
                ]);
            }

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Modificar equipo de cotizacion (EDIT)',
                'time' => microtime(true) - $begin
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Equipo guardado con éxito.',
                'equipment' => $equipment_quote,
                'quote' => $quote
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 422);
        }
    }

    public function updateEquipmentOfQuote(
        Request $request,
        $id_equipment,
        $id_quote
    ) {
        $begin = microtime(true);

        DB::beginTransaction();

        try {
            @bcscale(10);

            $quote = Quote::findOrFail($id_quote);

            $equipmentQuote = Equipment::where(
                'id',
                $id_equipment
            )
                ->where('quote_id', $quote->id)
                ->firstOrFail();

            $equipments = $request->input('equipment');

            if (
                !is_array($equipments) ||
                count($equipments) === 0
            ) {
                throw new \Exception(
                    'No se recibieron equipos.'
                );
            }

            $equip = $equipments[0];

            if (!is_array($equip)) {
                throw new \Exception(
                    'El equipo recibido tiene un formato inválido.'
                );
            }

            /*
             * La validación monetaria ocurre antes de actualizar, eliminar
             * detalles o liberar reservas.
             */
            $calculated = $this
                ->calculateAndValidateQuoteEquipmentTotals(
                    $equip,
                    $request
                );

            $equip['consumables'] =
                $calculated['consumables'];

            $equip['workforces'] =
                $calculated['workforces'];

            $equip['total'] =
                $calculated['total_with_igv'];

            /** @var QuoteStockReservationService $reservationService */
            $reservationService = app(
                QuoteStockReservationService::class
            );

            $dateQuote = $request->get('date_quote')
                ? Carbon::createFromFormat(
                    'd/m/Y',
                    $request->get('date_quote')
                )
                : null;

            $dateValidate = $request->get('date_validate')
                ? Carbon::createFromFormat(
                    'd/m/Y',
                    $request->get('date_validate')
                )
                : null;

            $quote->description_quote =
                $request->get('descriptionQuote');

            $quote->code =
                $request->get('codeQuote');

            $quote->date_quote =
                $dateQuote;

            $quote->date_validate =
                $dateValidate;

            $quote->way_to_pay =
                $request->get('way_to_pay') ?? '';

            $quote->delivery_time =
                $request->get('delivery_time') ?? '';

            $quote->customer_id =
                $request->get('customer_id') ?: null;

            $quote->contact_id =
                $request->get('contact_id') ?: null;

            $quote->payment_deadline_id =
                $request->get('payment_deadline') ?: null;

            $quote->observations =
                $request->get('observations') ?? '';

            $quote->descuento =
                $calculated['discount_base'];

            $quote->gravada =
                $calculated['taxable_base'];

            $quote->igv_total =
                $calculated['igv'];

            $quote->total_importe =
                $calculated['total_with_igv'];

            $quote->discount_type =
                $calculated['discount_type'];

            $quote->discount_input_mode =
                $calculated['discount_input_mode'];

            $quote->discount_input_value =
                $calculated['discount_input_value'];

            $quote->save();

            foreach ($equipmentQuote->consumables as $consumable) {
                $reservationService
                    ->releaseReservationsByQuoteDetail(
                        (int) $quote->id,
                        (int) $consumable->id
                    );

                $consumable->delete();
            }

            foreach ($equipmentQuote->workforces as $workforce) {
                $workforce->delete();
            }

            foreach ($equipmentQuote->materials as $material) {
                $material->delete();
            }

            foreach ($equipmentQuote->electrics as $electric) {
                $electric->delete();
            }

            foreach ($equipmentQuote->turnstiles as $turnstile) {
                $turnstile->delete();
            }

            foreach ($equipmentQuote->workdays as $workday) {
                $workday->delete();
            }

            $equipmentQuote->description =
                $equip['description'] ?? '';

            $equipmentQuote->detail =
                $equip['detail'] ?? '';

            $equipmentQuote->quantity =
                $equip['quantity'] ?? 1;

            $equipmentQuote->utility =
                $equip['utility'] ?? 0;

            $equipmentQuote->rent =
                $equip['rent'] ?? 0;

            $equipmentQuote->letter =
                $equip['letter'] ?? 0;

            $equipmentQuote->total =
                $calculated['total_with_igv'];

            $equipmentQuote->save();

            $consumables = $equip['consumables'] ?? [];

            foreach ($consumables as $index => $c) {
                $position = $index + 1;

                $stockItemId = (int) (
                    $c['stock_item_id']
                    ?? $c['stockItemId']
                    ?? $c['id']
                    ?? 0
                );

                if ($stockItemId <= 0) {
                    throw new \Exception(
                        "No se pudo identificar el stock item del producto {$position}."
                    );
                }

                $stockItem = StockItem::with(['material'])
                    ->where('id', $stockItemId)
                    ->first();

                if (!$stockItem) {
                    throw new \Exception(
                        "El stock item con ID {$stockItemId} no existe."
                    );
                }

                if (!$stockItem->material) {
                    throw new \Exception(
                        "El stock item con ID {$stockItemId} no tiene material asociado."
                    );
                }

                $material = $stockItem->material;
                $materialId = (int) $material->id;

                $isItemeable =
                    (int) ($material->tipo_venta_id ?? 0) === 3;

                $presentationId =
                    $c['presentation_id'] ?? null;

                $packs = null;
                $unitsPerPack = null;
                $requestedUnits = '0.0000000000';

                if (!empty($presentationId)) {
                    $packsDecimal = $this->normalizeQuoteDecimal(
                        $c['quantity'] ?? 0,
                        "packs de {$stockItem->display_name}"
                    );

                    if (
                        bccomp(
                            $packsDecimal,
                            '1.0000000000',
                            10
                        ) < 0
                    ) {
                        throw new \Exception(
                            "Packs inválidos para {$stockItem->display_name}."
                        );
                    }

                    if (
                        bccomp(
                            $packsDecimal,
                            number_format(
                                floor((float) $packsDecimal),
                                10,
                                '.',
                                ''
                            ),
                            10
                        ) !== 0
                    ) {
                        throw new \Exception(
                            "La cantidad de packs debe ser entera para {$stockItem->display_name}."
                        );
                    }

                    $packs = (int) $packsDecimal;

                    $presentation = MaterialPresentation::where(
                        'id',
                        $presentationId
                    )
                        ->where(
                            'material_id',
                            $materialId
                        )
                        ->where('active', 1)
                        ->first();

                    if (!$presentation) {
                        throw new \Exception(
                            "Presentación inválida para {$stockItem->display_name}."
                        );
                    }

                    $unitsPerPack =
                        (int) $presentation->quantity;

                    if ($unitsPerPack < 1) {
                        throw new \Exception(
                            "Units per pack inválido para {$stockItem->display_name}."
                        );
                    }

                    $requestedUnits = bcmul(
                        (string) $packs,
                        (string) $unitsPerPack,
                        10
                    );
                } else {
                    $requestedUnits = $this->normalizeQuoteDecimal(
                        $c['units_equivalent']
                        ?? $c['quantity']
                        ?? 0,
                        "cantidad de {$stockItem->display_name}"
                    );

                    if (
                        bccomp(
                            $requestedUnits,
                            '0.0000000000',
                            10
                        ) <= 0
                    ) {
                        throw new \Exception(
                            "Cantidad inválida para {$stockItem->display_name}."
                        );
                    }
                }

                $priceReal = $this->normalizeQuoteDecimal(
                    $c['priceReal']
                    ?? $c['price']
                    ?? 0,
                    "precio de {$stockItem->display_name}"
                );

                $valorUnitarioReal = bcdiv(
                    $priceReal,
                    '1.1800000000',
                    10
                );

                $quantityForPrice = !empty($presentationId)
                    ? number_format(
                        (float) $packs,
                        10,
                        '.',
                        ''
                    )
                    : $requestedUnits;

                $importeReal = bcmul(
                    $quantityForPrice,
                    $priceReal,
                    10
                );

                $selectedItemIds = [];

                if ($isItemeable) {
                    $rawSelectedItemIds =
                        $c['selected_item_ids'] ?? [];

                    if (is_string($rawSelectedItemIds)) {
                        $decodedIds = json_decode(
                            $rawSelectedItemIds,
                            true
                        );

                        $rawSelectedItemIds =
                            is_array($decodedIds)
                                ? $decodedIds
                                : [];
                    }

                    if (!is_array($rawSelectedItemIds)) {
                        $rawSelectedItemIds = [];
                    }

                    $selectedItemIds = collect(
                        $rawSelectedItemIds
                    )
                        ->map(function ($itemId) {
                            return (int) $itemId;
                        })
                        ->filter(function ($itemId) {
                            return $itemId > 0;
                        })
                        ->values()
                        ->toArray();

                    if (empty($selectedItemIds)) {
                        throw new \Exception(
                            "Debe seleccionar los ítems físicos para {$material->full_name}."
                        );
                    }

                    if (
                        count($selectedItemIds) !==
                        count(array_unique($selectedItemIds))
                    ) {
                        throw new \Exception(
                            "Se detectaron ítems repetidos para {$material->full_name}."
                        );
                    }

                    if (
                        (int) $requestedUnits !==
                        count($selectedItemIds)
                    ) {
                        throw new \Exception(
                            "La cantidad de ítems seleccionados no coincide con la cantidad requerida para {$material->full_name}. " .
                            "Requerido: {$requestedUnits}. " .
                            'Seleccionados: ' .
                            count($selectedItemIds) .
                            '.'
                        );
                    }
                }

                $available = $reservationService
                    ->getAvailableStockByStockItem(
                        $stockItemId
                    );

                if (
                    (float) $requestedUnits >
                    (float) $available
                ) {
                    throw new \Exception(
                        "Stock insuficiente para {$material->full_name}. " .
                        "Requerido: {$requestedUnits}. " .
                        "Disponible: {$available}."
                    );
                }

                $equipmentConsumable =
                    EquipmentConsumable::create([
                        'equipment_id' =>
                            $equipmentQuote->id,

                        'material_id' =>
                            $materialId,

                        'stock_item_id' =>
                            $stockItemId,

                        'material_presentation_id' =>
                            $presentationId,

                        'packs' =>
                            $packs,

                        'units_per_pack' =>
                            $unitsPerPack,

                        'quantity' =>
                            (float) $requestedUnits,

                        'price' =>
                            $priceReal,

                        'valor_unitario' =>
                            $valorUnitarioReal,

                        'total' =>
                            $importeReal,

                        'discount' =>
                            '0.0000000000',

                        'type_promo' =>
                            null,

                        'availability' =>
                            'Completo',

                        'state' =>
                            'En compra',
                    ]);

                if ($isItemeable) {
                    $reservationService
                        ->reserveItemeableForQuoteDetail(
                            (int) $quote->id,
                            (int) $equipmentConsumable->id,
                            (int) $stockItemId,
                            $selectedItemIds
                        );
                } else {
                    $reservationService
                        ->reserveForQuoteDetail(
                            (int) $quote->id,
                            (int) $equipmentConsumable->id,
                            (int) $stockItemId,
                            (float) $requestedUnits
                        );
                }
            }

            $workforces = $equip['workforces'] ?? [];

            foreach ($workforces as $index => $workforce) {
                $position = $index + 1;

                $price = $this->normalizeQuoteDecimal(
                    $workforce['price'] ?? 0,
                    "precio del servicio {$position}"
                );

                $quantity = $this->normalizeQuoteDecimal(
                    $workforce['quantity'] ?? 0,
                    "cantidad del servicio {$position}"
                );

                $total = bcmul(
                    $price,
                    $quantity,
                    10
                );

                $billable = isset($workforce['billable'])
                    ? (int) $workforce['billable']
                    : 1;

                $billable = $billable === 1 ? 1 : 0;

                EquipmentWorkforce::create([
                    'equipment_id' =>
                        $equipmentQuote->id,

                    'description' =>
                        $workforce['description'] ?? '',

                    'price' =>
                        $price,

                    'quantity' =>
                        (float) $quantity,

                    'total' =>
                        $total,

                    'unit' =>
                        $workforce['unit'] ?? '',

                    'billable' =>
                        $billable,
                ]);
            }

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' =>
                    'Modificar equipo de cotizacion (EDIT)',
                'time' =>
                    microtime(true) - $begin,
            ]);

            DB::commit();

            return response()->json([
                'message' =>
                    'Equipo guardado con éxito.',

                'equipment' =>
                    $equipmentQuote->fresh(),

                'quote' =>
                    $quote->fresh(),

                'calculated_totals' => [
                    'subtotal_consumables' =>
                        (float) $calculated[
                        'subtotal_consumables'
                        ],

                    'subtotal_workforces' =>
                        (float) $calculated[
                        'subtotal_workforces'
                        ],

                    'subtotal_with_igv' =>
                        (float) $calculated[
                        'subtotal_with_igv'
                        ],

                    'discount_base' =>
                        (float) $calculated[
                        'discount_base'
                        ],

                    'discount_with_igv' =>
                        (float) $calculated[
                        'discount_with_igv'
                        ],

                    'gravada' =>
                        (float) $calculated[
                        'taxable_base'
                        ],

                    'igv' =>
                        (float) $calculated[
                        'igv'
                        ],

                    'total' =>
                        (float) $calculated[
                        'total_with_igv'
                        ],
                ],
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 422);
        }
    }

    /**
     * Convierte y valida un valor decimal recibido.
     */
    private function normalizeQuoteDecimal($value,string $field,bool $allowNegative = false): string {
        if ($value === null || $value === '') {
            $value = '0';
        }

        $value = str_replace(',', '.', trim((string) $value));

        if (!is_numeric($value)) {
            throw new \Exception(
                "El campo {$field} contiene un valor numérico inválido."
            );
        }

        $normalized = number_format(
            (float) $value,
            10,
            '.',
            ''
        );

        if (
            !$allowNegative &&
            bccomp($normalized, '0.0000000000', 10) < 0
        ) {
            throw new \Exception(
                "El campo {$field} no puede ser negativo."
            );
        }

        return $normalized;
    }

    /**
     * Obtiene el valor absoluto de un decimal BCMath.
     */
    private function bcAbs(string $value, int $scale = 10): string
    {
        return bccomp($value, '0', $scale) < 0
            ? bcmul($value, '-1', $scale)
            : $value;
    }

    /**
     * Valida que dos importes sean iguales dentro de una tolerancia.
     */
    private function assertAmountsMatch(string $expected,string $received,string $message,string $tolerance = '0.0100000000'): void {
        $difference = bcsub($expected, $received, 10);
        $difference = $this->bcAbs($difference, 10);

        if (bccomp($difference, $tolerance, 10) > 0) {
            throw new \Exception(
                $message .
                ' Esperado: S/ ' . number_format((float) $expected, 2) .
                '. Recibido: S/ ' . number_format((float) $received, 2) . '.'
            );
        }
    }

    /**
     * Recalcula y valida todos los importes del equipo.
     *
     * No confía en los totales enviados por JavaScript.calculateAndValidateQuoteEquipmentTotals
     */
    private function calculateAndValidateQuoteEquipmentTotals(array $equip,Request $request): array {
        @bcscale(10);

        $subtotalConsumables = '0.0000000000';
        $subtotalWorkforces = '0.0000000000';

        $normalizedConsumables = [];
        $normalizedWorkforces = [];

        /*
        |--------------------------------------------------------------------------
        | 1. CONSUMIBLES
        |--------------------------------------------------------------------------
        */
        $consumables = $equip['consumables'] ?? [];

        if (!is_array($consumables)) {
            throw new \Exception(
                'El detalle de consumibles tiene un formato inválido.'
            );
        }

        foreach ($consumables as $index => $consumable) {
            $position = $index + 1;

            $presentationId =
                $consumable['presentation_id'] ?? null;

            /*
             * Cuando existe presentación, quantity representa packs.
             * Cuando no existe presentación, representa unidades.
             */
            $quantityForPrice = $this->normalizeQuoteDecimal(
                $consumable['quantity'] ?? 0,
                "cantidad del producto {$position}"
            );

            if (
                bccomp(
                    $quantityForPrice,
                    '0.0000000000',
                    10
                ) <= 0
            ) {
                throw new \Exception(
                    "La cantidad del producto {$position} debe ser mayor que cero."
                );
            }

            $price = $this->normalizeQuoteDecimal(
                $consumable['priceReal']
                ?? $consumable['price']
                ?? 0,
                "precio del producto {$position}"
            );

            /*
             * Total calculado por el backend.
             */
            $calculatedTotal = bcmul(
                $quantityForPrice,
                $price,
                10
            );

            /*
             * Como el descuento es global, no debe aplicarse un
             * descuento independiente dentro de cada producto.
             */
            $lineDiscount = $this->normalizeQuoteDecimal(
                $consumable['discount'] ?? 0,
                "descuento del producto {$position}"
            );

            if (
                bccomp(
                    $lineDiscount,
                    '0.0000000000',
                    10
                ) !== 0
            ) {
                throw new \Exception(
                    "El producto {$position} contiene un descuento por ítem. " .
                    'Los descuentos deben aplicarse únicamente de forma global.'
                );
            }

            /*
             * Validar el importe que envió JavaScript.
             */
            $receivedTotal = $this->normalizeQuoteDecimal(
                $consumable['importe'] ?? 0,
                "importe del producto {$position}"
            );

            $this->assertAmountsMatch(
                $calculatedTotal,
                $receivedTotal,
                "El importe del producto {$position} no coincide con cantidad por precio."
            );

            $subtotalConsumables = bcadd(
                $subtotalConsumables,
                $calculatedTotal,
                10
            );

            /*
             * Se conserva la información original, pero el importe
             * queda reemplazado por el cálculo confiable del backend.
             */
            $consumable['importe'] = $calculatedTotal;
            $consumable['priceReal'] = $price;
            $consumable['discount'] = '0.0000000000';

            $normalizedConsumables[] = $consumable;
        }

        /*
        |--------------------------------------------------------------------------
        | 2. SERVICIOS ADICIONALES
        |--------------------------------------------------------------------------
        */
        $workforces = $equip['workforces'] ?? [];

        if (!is_array($workforces)) {
            throw new \Exception(
                'El detalle de servicios tiene un formato inválido.'
            );
        }

        foreach ($workforces as $index => $workforce) {
            $position = $index + 1;

            $quantity = $this->normalizeQuoteDecimal(
                $workforce['quantity'] ?? 0,
                "cantidad del servicio {$position}"
            );

            if (
                bccomp(
                    $quantity,
                    '0.0000000000',
                    10
                ) <= 0
            ) {
                throw new \Exception(
                    "La cantidad del servicio {$position} debe ser mayor que cero."
                );
            }

            $price = $this->normalizeQuoteDecimal(
                $workforce['price'] ?? 0,
                "precio del servicio {$position}"
            );

            $calculatedTotal = bcmul(
                $quantity,
                $price,
                10
            );

            $receivedTotal = $this->normalizeQuoteDecimal(
                $workforce['importe'] ?? 0,
                "importe del servicio {$position}"
            );

            $this->assertAmountsMatch(
                $calculatedTotal,
                $receivedTotal,
                "El importe del servicio {$position} no coincide con cantidad por precio."
            );

            $billable = isset($workforce['billable'])
                ? (int) $workforce['billable']
                : 1;

            /*
             * Solo los servicios facturables forman parte del total.
             */
            if ($billable === 1) {
                $subtotalWorkforces = bcadd(
                    $subtotalWorkforces,
                    $calculatedTotal,
                    10
                );
            }

            $workforce['price'] = $price;
            $workforce['importe'] = $calculatedTotal;
            $workforce['billable'] = $billable;

            $normalizedWorkforces[] = $workforce;
        }

        /*
        |--------------------------------------------------------------------------
        | 3. SUBTOTAL CON IGV ANTES DEL DESCUENTO
        |--------------------------------------------------------------------------
        */
        $subtotalWithIgv = bcadd(
            $subtotalConsumables,
            $subtotalWorkforces,
            10
        );

        /*
        |--------------------------------------------------------------------------
        | 4. CONFIGURACIÓN DEL DESCUENTO GLOBAL
        |--------------------------------------------------------------------------
        */
        $igvPercentage = '18.0000000000';
        $factor = '1.1800000000';

        $discountType = strtolower(
            trim((string) $request->input(
                'discount_type',
                'amount'
            ))
        );

        $discountInputMode = strtolower(
            trim((string) $request->input(
                'discount_input_mode',
                'without_igv'
            ))
        );

        $discountInputValue = $this->normalizeQuoteDecimal(
            $request->input('discount_input_value', 0),
            'valor del descuento global'
        );

        if (
        !in_array(
            $discountType,
            ['amount', 'percentage'],
            true
        )
        ) {
            throw new \Exception(
                'El tipo de descuento global no es válido.'
            );
        }

        if (
        !in_array(
            $discountInputMode,
            ['with_igv', 'without_igv'],
            true
        )
        ) {
            throw new \Exception(
                'La modalidad del descuento global no es válida.'
            );
        }

        $discountWithIgv = '0.0000000000';
        $discountBase = '0.0000000000';

        /*
        |--------------------------------------------------------------------------
        | 5. CALCULAR DESCUENTO
        |--------------------------------------------------------------------------
        */
        if ($discountType === 'percentage') {
            if (
                bccomp(
                    $discountInputValue,
                    '100.0000000000',
                    10
                ) > 0
            ) {
                throw new \Exception(
                    'El porcentaje de descuento no puede ser mayor a 100%.'
                );
            }

            /*
             * El porcentaje se aplica sobre el subtotal completo.
             * El modo con/sin IGV no modifica matemáticamente el porcentaje.
             */
            $percentageFactor = bcdiv(
                $discountInputValue,
                '100.0000000000',
                10
            );

            $discountWithIgv = bcmul(
                $subtotalWithIgv,
                $percentageFactor,
                10
            );

            $discountBase = bcdiv(
                $discountWithIgv,
                $factor,
                10
            );
        } else {
            /*
             * Descuento por monto exacto.
             */
            if ($discountInputMode === 'with_igv') {
                $discountWithIgv = $discountInputValue;

                $discountBase = bcdiv(
                    $discountWithIgv,
                    $factor,
                    10
                );
            } else {
                $discountBase = $discountInputValue;

                $discountWithIgv = bcmul(
                    $discountBase,
                    $factor,
                    10
                );
            }
        }

        if (
            bccomp(
                $discountWithIgv,
                $subtotalWithIgv,
                10
            ) > 0
        ) {
            throw new \Exception(
                'El descuento global no puede ser mayor que el subtotal.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 6. TOTAL FINAL
        |--------------------------------------------------------------------------
        */
        $totalWithIgv = bcsub(
            $subtotalWithIgv,
            $discountWithIgv,
            10
        );

        $taxableBase = bcdiv(
            $totalWithIgv,
            $factor,
            10
        );

        $igv = bcsub(
            $totalWithIgv,
            $taxableBase,
            10
        );

        /*
        |--------------------------------------------------------------------------
        | 7. VALIDAR TOTALES ENVIADOS POR JAVASCRIPT
        |--------------------------------------------------------------------------
        */
        $receivedDiscount = $this->normalizeQuoteDecimal(
            $request->input('descuento', 0),
            'descuento enviado'
        );

        $receivedTaxableBase = $this->normalizeQuoteDecimal(
            $request->input('gravada', 0),
            'operación gravada enviada'
        );

        $receivedIgv = $this->normalizeQuoteDecimal(
            $request->input('igv_total', 0),
            'IGV enviado'
        );

        $receivedTotal = $this->normalizeQuoteDecimal(
            $request->input('total_importe', 0),
            'importe total enviado'
        );

        $equipmentReceivedTotal = $this->normalizeQuoteDecimal(
            $equip['total'] ?? 0,
            'total del equipo'
        );

        $this->assertAmountsMatch(
            $discountBase,
            $receivedDiscount,
            'El descuento global enviado no coincide con el cálculo del servidor.'
        );

        $this->assertAmountsMatch(
            $taxableBase,
            $receivedTaxableBase,
            'La operación gravada enviada no coincide con el cálculo del servidor.'
        );

        $this->assertAmountsMatch(
            $igv,
            $receivedIgv,
            'El IGV enviado no coincide con el cálculo del servidor.'
        );

        $this->assertAmountsMatch(
            $totalWithIgv,
            $receivedTotal,
            'El importe total enviado no coincide con la suma de los detalles.'
        );

        $this->assertAmountsMatch(
            $totalWithIgv,
            $equipmentReceivedTotal,
            'El total del equipo no coincide con la suma de sus detalles.'
        );

        return [
            'consumables' => $normalizedConsumables,
            'workforces' => $normalizedWorkforces,

            'subtotal_consumables' => $subtotalConsumables,
            'subtotal_workforces' => $subtotalWorkforces,
            'subtotal_with_igv' => $subtotalWithIgv,

            'discount_base' => $discountBase,
            'discount_with_igv' => $discountWithIgv,

            'taxable_base' => $taxableBase,
            'igv' => $igv,
            'total_with_igv' => $totalWithIgv,

            'discount_type' => $discountType,
            'discount_input_mode' => $discountInputMode,
            'discount_input_value' => $discountInputValue,
        ];
    }

    public function showRegistrarComprobante($typeComprobante)
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $dataCurrency = DataGeneral::where('name', 'type_current')->first();
        $currency = $dataCurrency->valueText;

        $dataIgv = PorcentageQuote::where('name', 'igv')->first();
        $igv = $dataIgv->value;

        // ✅ NUEVO: Cajas + subtipos (igual que PV)
        $cashBoxes = CashBox::where('is_active', 1)
            ->orderBy('position')
            ->get()
            ->map(function ($b) {
                return [
                    'id'            => (int) $b->id,
                    'name'          => (string) $b->name,
                    'type'          => (string) $b->type,
                    'uses_subtypes' => (bool) $b->uses_subtypes,
                ];
            })
            ->values();

        $subtypes = CashBoxSubtype::whereNull('cash_box_id')
            ->where('is_active', 1)
            ->orderBy('position')
            ->get()
            ->map(function ($s) {
                return [
                    'id'   => (int) $s->id,
                    'name' => (string) $s->name,
                ];
            })
            ->values();
        /*dump($cashBoxes);
        dd();*/

        $data_pagos_parciales = DataGeneral::where('name', 'pagos_parciales')->first();
        $pagos_parciales = $data_pagos_parciales->valueText;

        return view('quoteSale.registrarComprobante', compact(
            'typeComprobante',
            'permissions',
            'currency',
            'igv',
            'cashBoxes','subtypes', 'pagos_parciales'
        ));
    }

    public function buscarO(Request $request)
    {
        $query = Quote::query();

        if ($request->has('code') && !empty($request->code)) {
            $query->where('code', 'LIKE', '%' . $request->code . '%');
        }

        if ($request->has('name') && !empty($request->name)) {
            $query->where('description_quote', 'LIKE', '%' . $request->name . '%');
        }

        $quotes = $query->where('state', 'confirmed')
            ->whereDoesntHave('sales', function ($q) {
                $q->where('state_annulled', 0);
            })
            ->limit(20)
            ->get()
            ->map(function ($quote) {
                $quote->customer_name = $quote->customer_id
                    ? optional($quote->customer)->business_name
                    : "";

                $quote->date_quote_format = $quote->date_quote
                    ? $quote->date_quote->format('d/m/Y')
                    : "";

                return $quote;
            });

        return response()->json($quotes);
    }

    public function buscarChat(Request $request)
    {
        if (!$request->filled('code') && !$request->filled('name')) {
            return response()->json([]);
        }

        $query = Quote::with(['customer:id,business_name'])
            ->where('state', 'confirmed')
            ->whereDoesntHave('sales', function ($q) {
                $q->where('state_annulled', 0);
            });

        if ($request->filled('code')) {
            $query->where('code', 'LIKE', '%' . trim($request->code) . '%');
        }

        if ($request->filled('name')) {
            $query->where('description_quote', 'LIKE', '%' . trim($request->name) . '%');
        }

        $quotes = $query->orderBy('id', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($quote) {
                $quote->customer_name = optional($quote->customer)->business_name ?? '';
                $quote->date_quote_format = $quote->date_quote
                    ? $quote->date_quote->format('d/m/Y')
                    : '';

                return $quote;
            });

        return response()->json($quotes);
    }

    public function buscar(Request $request)
    {
        if (!$request->filled('code') && !$request->filled('name')) {
            return response()->json([]);
        }

        $query = Quote::with(['customer:id,business_name'])
            ->select('id', 'code', 'description_quote', 'customer_id', 'date_quote', 'state')
            ->where('state', 'confirmed')
            ->whereDoesntHave('sales', function ($q) {
                $q->where('state_annulled', 0);
            });

        if ($request->filled('code')) {
            $query->where('code', 'LIKE', '%' . trim($request->code) . '%');
        }

        if ($request->filled('name')) {
            $query->where('description_quote', 'LIKE', '%' . trim($request->name) . '%');
        }

        $quotes = $query->orderBy('id', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($quote) {
                return [
                    'id' => $quote->id,
                    'code' => $quote->code,
                    'description_quote' => $quote->description_quote,
                    'customer_name' => optional($quote->customer)->business_name ?? '',
                    'date_quote_format' => $quote->date_quote
                        ? $quote->date_quote->format('d/m/Y')
                        : '',
                ];
            })
            ->values();

        return response()->json($quotes);
    }

    public function getDataIndividualO($id)
    {
        $quote = Quote::with('customer')
            ->with('deadline')
            ->with(['equipments' => function ($query) {
                $query->with([
                    'consumables.material',
                    'workforces' // ✅ nuevo
                ]);
            }])->findOrFail($id);

        // Si quieres formatear la fecha:
        $quote->date_quote_format = $quote->date_quote ? $quote->date_quote->format('d/m/Y') : "";
        $quote->date_validate_format = $quote->date_validate ? $quote->date_validate->format('d/m/Y') : "";
        $quote->deadline_format = $quote->deadline ? $quote->deadline->description : "";
        $quote->customer_format = $quote->customer_id ? $quote->customer->business_name : "";
        $quote->contact_format = $quote->contact_id ? $quote->contact->name : "";

        return response()->json($quote);
    }

    public function getDataIndividual($id)
    {
        $quote = Quote::with([
            'customer:id,business_name,RUC,address',
            'contact:id,name',
            'deadline:id,description',
            'equipments' => function ($query) {
                $query->select('id', 'quote_id', 'detail')
                    ->with([
                        'consumables' => function ($q) {
                            $q->select(
                                'id',
                                'equipment_id',
                                'material_id',
                                'stock_item_id',
                                'material_presentation_id',
                                'discount',
                                'type_promo',
                                'units_per_pack',
                                'quantity',
                                'packs',
                                'valor_unitario',
                                'price',
                                'total'
                            )->with([
                                'material' => function ($q) {
                                    $q->select('id', 'full_name', 'unit_measure_id')
                                        ->with('unitMeasure:id,name');
                                },
                                'stockItem:id,display_name,sku',
                                'quoteStockLots:id,quote_detail_id,item_ids'
                            ]);
                        },
                        'workforces' => function ($q) {
                            $q->select(
                                'id',
                                'equipment_id',
                                'description',
                                'unit',
                                'quantity',
                                'price',
                                'total',
                                'billable'
                            );
                        }
                    ]);
            }
        ])->findOrFail($id);

        $response = [
            'id' => $quote->id,
            'code' => $quote->code,
            'description_quote' => $quote->description_quote,
            'date_quote_format' => $quote->date_quote ? $quote->date_quote->format('d/m/Y') : '',
            'date_validate_format' => $quote->date_validate ? $quote->date_validate->format('d/m/Y') : '',
            'deadline_format' => optional($quote->deadline)->description ?? '',
            'customer_format' => optional($quote->customer)->business_name ?? '',
            'contact_format' => optional($quote->contact)->name ?? '',
            'delivery_time' => $quote->delivery_time,
            'observations' => $quote->observations,
            'descuento' => $quote->descuento ?? 0,
            'gravada' => $quote->gravada ?? 0,
            'igv_total' => $quote->igv_total ?? 0,
            'total_importe' => $quote->total_importe ?? 0,
            'discount_type' => $quote->discount_type,
            'discount_input_mode' => $quote->discount_input_mode,
            'discount_input_value' => $quote->discount_input_value,

            'customer' => [
                'RUC' => optional($quote->customer)->RUC ?? '',
                'address' => optional($quote->customer)->address ?? '',
            ],

            'equipments' => $quote->equipments->map(function ($equipment) {
                return [
                    'id' => $equipment->id,
                    'detail' => $equipment->detail,
                    'consumables' => $equipment->consumables->map(function ($consumable) {
                        $itemIds = [];

                        foreach ($consumable->quoteStockLots ?? [] as $quoteStockLot) {
                            $rawItemIds = $quoteStockLot->item_ids ?? [];

                            if (is_string($rawItemIds)) {
                                $decodedItemIds = json_decode($rawItemIds, true);
                                $rawItemIds = is_array($decodedItemIds) ? $decodedItemIds : [];
                            }

                            if (!is_array($rawItemIds)) {
                                $rawItemIds = [];
                            }

                            foreach ($rawItemIds as $itemId) {
                                $itemId = (int) $itemId;

                                if ($itemId > 0) {
                                    $itemIds[] = $itemId;
                                }
                            }
                        }

                        $itemIds = array_values(array_unique($itemIds));

                        $itemCodes = [];

                        if (!empty($itemIds)) {
                            $itemCodes = Item::whereIn('id', $itemIds)
                                ->whereNotNull('code')
                                ->where('code', '<>', '')
                                ->orderBy('code', 'asc')
                                ->pluck('code')
                                ->values()
                                ->toArray();
                        }
                        return [
                            'id' => $consumable->id,
                            'discount' => $consumable->discount,
                            'type_promo' => $consumable->type_promo,
                            'material_presentation_id' => $consumable->material_presentation_id,
                            'units_per_pack' => $consumable->units_per_pack,
                            'quantity' => $consumable->quantity,
                            'packs' => $consumable->packs,
                            'valor_unitario' => $consumable->valor_unitario,
                            'price' => $consumable->price,
                            'total' => $consumable->total,
                            'material' => [
                                'full_description' => optional($consumable->stockItem)->display_name
                                    ?? optional($consumable->material)->full_name
                                    ?? '',
                                'name_unit' => optional(optional($consumable->material)->unitMeasure)->name ?? '',
                                'sku' => optional($consumable->stockItem)->sku ?? '',
                            ],
                            'item_codes' => $itemCodes,
                        ];
                    })->values(),

                    'workforces' => $equipment->workforces->map(function ($wf) {
                        return [
                            'id' => $wf->id,
                            'description' => $wf->description,
                            'unit' => $wf->unit,
                            'quantity' => $wf->quantity,
                            'price' => $wf->price,
                            'total' => $wf->total,
                            'billable' => $wf->billable,
                        ];
                    })->values(),
                ];
            })->values(),
        ];

        return response()->json($response);
    }

    public function storeFromQuoteO(Request $request)
    {
        $begin = microtime(true);
        $worker = Worker::where('user_id', Auth::user()->id)->first();
        $dataCurrency = DataGeneral::where('name', 'type_current')->first();
        $currency = $dataCurrency->valueText;

        DB::beginTransaction();
        try {
            $quote = Quote::with(['equipments.consumables.material'])->findOrFail($request->quote_id);

            // Validaciones mínimas
            if (!$quote || $quote->state !== 'confirmed') {
                throw new \Exception("La cotización no es válida o no está confirmada.");
            }

            // Construcción de la venta
            $sale = Sale::create([
                // Si viene fechaDocumento la usamos, si no usamos la actual
                'date_sale' => $request->filled('fechaDocumento')
                    ? Carbon::createFromFormat('Y-m-d', $request->fechaDocumento)
                    : Carbon::now(),

                'serie' => $this->generateRandomString(), // método tuyo que genera serie
                'worker_id' => $worker->id,
                'caja' => $worker->id,
                'currency' => ($currency == 'usd') ? 'USD':'PEN',

                // Totales (tomados de la cotización)
                'op_exonerada' => 0,
                'op_inafecta' => 0,
                'op_gravada' => $quote->gravada,
                'igv' => $quote->igv_total,
                'total_descuentos' => $quote->descuento,
                'importe_total' => $quote->total_importe,
                'vuelto' => 0,

                'quote_id' => $quote->id,

                // Aquí usamos lo que envías
                'tipo_pago_id' => $request->tipoPago ?? 4,  // si no llega, por defecto efectivo

                // Datos de cliente
                'type_document' => $request->type_document,  // "01" factura, "03" boleta/ticket
                'numero_documento_cliente' => $request->numero_documento_cliente,
                'tipo_documento_cliente' => $request->tipo_documento_cliente, // "1" DNI, "6" RUC
                'nombre_cliente' => $request->nombre_cliente,
                'direccion_cliente' => $request->direccion_cliente,
                'email_cliente' => $request->email_cliente,

                // Campos SUNAT se llenarán luego
                'serie_sunat' => null,
                'numero' => null,
                'sunat_ticket' => null,
                'sunat_status' => null,
                'sunat_message' => null,
                'xml_path' => null,
                'cdr_path' => null,
                'fecha_emision' => null,
            ]);

            // ✅ 1) Crear Output (salida) asociada a la ejecución/orden de la cotización
            $output = Output::create([
                'execution_order'   => ($quote->order_execution == "") ? 'VENTA POR COTIZACION':$quote->order_execution, // o $quote->execution_order si tu campo fuera otro
                'request_date'      => $sale->date_sale,        // o Carbon::now()
                'requesting_user'   => Auth::id(),
                'responsible_user'  => Auth::id(),              // si luego lo asignas a almacenero, lo puedes cambiar
                'state'             => 'confirmed',
                'indicator'         => 'or',
            ]);

            // Ahora los detalles
            foreach ($quote->equipments as $equipment) {
                foreach ($equipment->consumables as $consumable) {
                    SaleDetail::create([
                        'sale_id' => $sale->id,
                        'material_id' => $consumable->material_id,
                        'price' => $consumable->price,
                        'quantity' => $consumable->quantity,
                        'percentage_tax' => 18, // Asumiendo IGV fijo, ajusta si es dinámico
                        'total' => $consumable->total,
                        'discount' => $consumable->discount,
                    ]);

                    // Descontar stock del material directamente
                    $material = $consumable->material;
                    if ($material) {
                        $material->stock_current = max(0, $material->stock_current - $consumable->quantity);
                        $material->stock_current = max(0, $material->stock_reserved - $consumable->quantity);
                        $material->save();
                    }

                    /*$this->manageNotifications($material);*/

                    $materialId = $consumable->material_id;
                    $qtyNeeded  = (float) $consumable->quantity;

                    // ⚠️ por ahora solo soportamos cantidades enteras (material normal sin retazos)
                    if (floor($qtyNeeded) != $qtyNeeded) {
                        throw new \Exception("La cotización requiere cantidad decimal para el material {$materialId}. Aún no está implementado para retazos.");
                    }

                    $qtyNeeded = (int) $qtyNeeded;

                    if ( $material->tipo_venta_id == 3 )
                    {
                        // ✅ 2.1) Obtener items disponibles con lock (evita carreras)
                        $items = Item::where('material_id', $materialId)
                            ->whereIn('state_item', ['entered', 'scrapped'])
                            // si usas usage para filtrar disponibles:
                            // ->where('usage', '<>', 'finished')
                            ->orderBy('id', 'asc') // FIFO simple
                            ->lockForUpdate()
                            ->take($qtyNeeded)
                            ->get();

                        // ✅ 2.2) Validar stock suficiente
                        if ($items->count() < $qtyNeeded) {
                            throw new \Exception("Stock insuficiente del material ID {$materialId}. Se requieren {$qtyNeeded} y solo hay {$items->count()} disponibles.");
                        }

                        // ✅ 2.3) Crear OutputDetails + marcar items como exited
                        foreach ($items as $item) {
                            OutputDetail::create([
                                'output_id'    => $output->id,
                                'item_id'      => $item->id,
                                'material_id'  => $materialId,
                                'quote_id'     => $quote->id,
                                'custom'       => 0,
                                'percentage'   => 1,           // material normal => 1
                                'price'        => $item->price,        // opcional: puedes guardar el costo o precio de salida si quieres
                                'length'       => $item->length,
                                'width'        => $item->width,
                                'equipment_id' => $equipment->id ?? null,
                                'activo'       => null,        // no usar para lógica de salida (según me dijiste)
                            ]);

                            $item->state_item = 'exited';
                            // opcional si manejas usage:
                            // $item->usage = 'finished';
                            $item->save();
                        }
                    }
                }
            }
            $paymentType = $request->tipoPago ?? 4;
            // Mapear tipo de pago a los nombres de las cajas
            $paymentTypeMap = [
                1 => 'yape',
                2 => 'plin',
                3 => 'bancario',
                4 => 'efectivo'
            ];

            // Obtener la caja del tipo de pago
            $cashRegister = CashRegister::where('type', $paymentTypeMap[$paymentType])
                ->where('user_id', Auth::user()->id)
                ->where('status', 1) // Caja abierta
                ->latest()
                ->first();

            if (!isset($cashRegister)) {
                return response()->json(['message' => 'No hay caja abierta para este tipo de pago.'], 422);
            }
            if ( $paymentType != 3 ) {
                // Crear el movimiento de ingreso (venta)
                CashMovement::create([
                    'cash_register_id' => $cashRegister->id,
                    'type' => 'sale', // Tipo de movimiento: venta
                    'amount' => (float)$request->get('total_importe')+(float)$request->get('total_vuelto'),
                    'description' => 'Venta registrada con tipo de pago: ' . $paymentTypeMap[$paymentType],
                    'sale_id' => $sale->id
                ]);

                // Actualizar el saldo actual y el total de ventas en la caja
                $cashRegister->current_balance += (float)$request->get('total_importe')+(float)$request->get('total_vuelto');
                $cashRegister->total_sales += (float)$request->get('total_importe')+(float)$request->get('total_vuelto');
                $cashRegister->save();
            } else {
                // Crear el movimiento de ingreso (venta)
                CashMovement::create([
                    'cash_register_id' => $cashRegister->id,
                    'type' => 'sale', // Tipo de movimiento: venta
                    'amount' => (float)$request->get('total_importe')+(float)$request->get('total_vuelto'),
                    'description' => 'Venta registrada con tipo de pago: ' . $paymentTypeMap[$paymentType],
                    'regularize' => 0,
                    'sale_id' => $sale->id
                ]);
            }


            // Registrar el vuelto como egreso si el tipo de pago es efectivo y hay vuelto

            $typeVuelto = $paymentTypeMap[$paymentType];
            $vuelto = 0;

            if ($paymentType == 4) {
                // Mapear el type_vuelto (la caja desde donde se dará el vuelto)
                $typeVueltoMap = [
                    'efectivo' => 'efectivo',
                    'yape' => 'yape',
                    'plin' => 'plin',
                    'bancario' => 'bancario'
                ];

                // Obtener la caja para el vuelto
                $vueltoCashRegister = CashRegister::where('type', $typeVueltoMap[$typeVuelto])
                    ->where('user_id', Auth::user()->id)
                    ->where('status', 1) // Caja abierta
                    ->latest()
                    ->first();

                if (!isset($vueltoCashRegister)) {
                    return response()->json(['message' => 'No hay caja abierta para dar el vuelto.'], 422);
                }

                // Crear el movimiento de egreso (vuelto)
                CashMovement::create([
                    'cash_register_id' => $vueltoCashRegister->id,
                    'type' => 'expense', // Tipo de movimiento: egreso
                    'amount' => $vuelto,
                    'description' => 'Vuelto entregado de la venta',
                    'sale_id' => $sale->id
                ]);

                // Actualizar el saldo de la caja del vuelto
                $vueltoCashRegister->current_balance -= $vuelto;
                $vueltoCashRegister->total_expenses += $vuelto;
                $vueltoCashRegister->save();
            }

            // Crear notificación
            $notification = Notification::create([
                'content' => 'Venta creada desde cotización por '.Auth::user()->name,
                'reason_for_creation' => 'create_sale_from_quote',
                'user_id' => Auth::user()->id,
                'url_go' => route('puntoVenta.index')
            ]);

            $users = User::role(['admin', 'principal', 'logistic'])->get();
            foreach ($users as $user) {
                if ($user->id != Auth::user()->id) {
                    foreach ($user->roles as $role) {
                        NotificationUser::create([
                            'notification_id' => $notification->id,
                            'role_id' => $role->id,
                            'user_id' => $user->id,
                            'read' => false
                        ]);
                    }
                }
            }

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Guardar venta desde cotización',
                'time' => $end
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Venta creada con éxito desde cotización',
                'sale_id' => $sale->id,
                'url_print' => route('puntoVenta.print', $sale->id)
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function storeFromQuoteO2(Request $request)
    {
        $begin = microtime(true);

        $worker = Worker::where('user_id', Auth::user()->id)->first();
        $dataCurrency = DataGeneral::where('name', 'type_current')->first();
        $currency = $dataCurrency->valueText;

        DB::beginTransaction();
        try {
            $quote = Quote::with(['equipments.consumables.material'])->findOrFail($request->quote_id);

            if (!$quote || $quote->state !== 'confirmed') {
                throw new \Exception("La cotización no es válida o no está confirmada.");
            }

            // 1) Crear venta
            $sale = Sale::create([
                'date_sale' => $request->filled('fechaDocumento')
                    ? Carbon::createFromFormat('Y-m-d', $request->fechaDocumento)
                    : Carbon::now(),

                'serie' => $this->generateRandomString(),
                'worker_id' => $worker->id,
                'caja' => $worker->id,
                'currency' => ($currency == 'usd') ? 'USD' : 'PEN',

                'op_exonerada' => 0,
                'op_inafecta' => 0,
                'op_gravada' => $quote->gravada,
                'igv' => $quote->igv_total,
                'total_descuentos' => $quote->descuento,
                'importe_total' => $quote->total_importe,
                'vuelto' => 0,

                'quote_id' => $quote->id,

                'tipo_pago_id' => $request->tipoPago ?? 4,

                'type_document' => $request->type_document,
                'numero_documento_cliente' => $request->numero_documento_cliente,
                'tipo_documento_cliente' => $request->tipo_documento_cliente,
                'nombre_cliente' => $request->nombre_cliente,
                'direccion_cliente' => $request->direccion_cliente,
                'email_cliente' => $request->email_cliente,

                'serie_sunat' => null,
                'numero' => null,
                'sunat_ticket' => null,
                'sunat_status' => null,
                'sunat_message' => null,
                'xml_path' => null,
                'cdr_path' => null,
                'pdf_path' => null,
                'fecha_emision' => null,
            ]);

            $saleDate = $sale->date_sale instanceof Carbon
                ? $sale->date_sale
                : Carbon::parse($sale->date_sale);

            // recolectar materialIds
            $materialIds = [];
            foreach ($quote->equipments as $equipment) {
                foreach ($equipment->consumables as $consumable) {
                    $materialIds[] = (int)$consumable->material_id;
                }
            }
            $materialIds = array_values(array_unique($materialIds));

            // costo promedio vigente por material hasta la fecha de emisión
            $avgCosts = $this->inventoryCostService->getAverageCostsUpToDate($materialIds, $saleDate);

            // 2) Crear Output
            $output = Output::create([
                'execution_order'   => ($quote->order_execution == "") ? 'VENTA POR COTIZACION' : $quote->order_execution,
                'request_date'      => $sale->date_sale,
                'requesting_user'   => Auth::id(),
                'responsible_user'  => Auth::id(),
                'state'             => 'confirmed',
                'indicator'         => 'or',
            ]);

            // 3) Detalles + stock + output details
            foreach ($quote->equipments as $equipment) {
                foreach ($equipment->consumables as $consumable) {

                    $materialId = (int)$consumable->material_id;
                    $qty = (float)$consumable->quantity; // OJO: confirmaste que quantity siempre es real a descontar

                    $unitCost = (float)($avgCosts[$materialId] ?? 0.0);
                    $totalCost = $qty * $unitCost;

                    $saleDetail = SaleDetail::create([
                        'sale_id'                   => $sale->id,
                        'material_id'               => $consumable->material_id,
                        'material_presentation_id'  => $consumable->material_presentation_id,
                        'valor_unitario'            => $consumable->valor_unitario,
                        'price'                     => $consumable->price,
                        'quantity'                  => $consumable->quantity,
                        'packs'                     => $consumable->packs,           // ✅ si hay presentación
                        'units_per_pack'            => $consumable->units_per_pack,    // ✅ si hay presentación
                        'percentage_tax'            => 18,
                        'total'                     => $consumable->total,
                        'discount'                  => $consumable->discount,

                        // ✅ snapshot de costos para utilidad histórica
                        'unit_cost'                 => $unitCost,
                        'total_cost'                => $totalCost,
                    ]);

                    $material = $consumable->material;

                    if ($material) {
                        $material->unit_price = $unitCost;
                        $material->stock_current  = max(0, (float)$material->stock_current  - (float)$consumable->quantity);
                        $material->stock_reserved = max(0, (float)$material->stock_reserved - (float)$consumable->quantity);
                        $material->save();
                    }

                    $materialId = $consumable->material_id;
                    $qtyNeeded  = (float) $consumable->quantity;

                    if (floor($qtyNeeded) != $qtyNeeded) {
                        throw new \Exception("La cotización requiere cantidad decimal para el material {$materialId}. Aún no está implementado para retazos.");
                    }

                    $qtyNeeded = (int) $qtyNeeded;

                    if ($material && (int)$material->tipo_venta_id === 3) {
                        $items = Item::where('material_id', $materialId)
                            ->whereIn('state_item', ['entered', 'scrapped'])
                            ->orderBy('id', 'asc')
                            ->lockForUpdate()
                            ->take($qtyNeeded)
                            ->get();

                        if ($items->count() < $qtyNeeded) {
                            throw new \Exception("Stock insuficiente del material ID {$materialId}. Se requieren {$qtyNeeded} y solo hay {$items->count()} disponibles.");
                        }

                        foreach ($items as $item) {
                            OutputDetail::create([
                                'output_id'      => $output->id,
                                'sale_detail_id' => $saleDetail->id, // ✅ enlace a la línea de venta
                                'item_id'        => $item->id,
                                'material_id'    => $materialId,
                                'quote_id'       => $quote->id,
                                'custom'         => 0,
                                'percentage'     => 1,
                                'price'          => $item->price,
                                'length'         => $item->length,
                                'width'          => $item->width,
                                'equipment_id'   => $equipment->id ?? null,
                                'activo'         => null,
                                // ✅ costo promedio aplicado (misma base)
                                'unit_cost'      => $unitCost,
                                'total_cost'     => $unitCost,
                            ]);

                            $item->state_item = 'exited';
                            $item->save();
                        }
                    }

                    if ($material && (int)$material->tipo_venta_id !== 3) {
                        // ✅ NO ITEMEABLE: 1 OutputDetail por línea, con item_id NULL
                        OutputDetail::create([
                            'output_id'      => $output->id,
                            'sale_detail_id' => $saleDetail->id,
                            'item_id'        => null,
                            'material_id'    => $materialId,
                            'quote_id'       => $quote->id,
                            'custom'         => 0,

                            // tu VIEW usa percentage como "cantidad real"
                            'percentage'     => $qty,

                            // opcional, si tu tabla los necesita
                            'price'          => $consumable->price,
                            'length'         => null,
                            'width'          => null,
                            'equipment_id'   => $equipment->id ?? null,
                            'activo'         => null,

                            // (Opcional pero recomendado para auditoría)
                            'unit_cost'      => $unitCost,
                            'total_cost'     => $totalCost,
                        ]);
                    }
                }
            }

            // 4) Caja + notificaciones + auditoría (tu misma lógica tal cual)
            // ... (aquí pega tu bloque tal como lo tienes)
            $paymentType = $request->tipoPago ?? 4;
            // Mapear tipo de pago a los nombres de las cajas
            $paymentTypeMap = [
                1 => 'yape',
                2 => 'plin',
                3 => 'bancario',
                4 => 'efectivo'
            ];

            // Obtener la caja del tipo de pago
            $cashRegister = CashRegister::where('type', $paymentTypeMap[$paymentType])
                ->where('user_id', Auth::user()->id)
                ->where('status', 1) // Caja abierta
                ->latest()
                ->first();

            if (!isset($cashRegister)) {
                return response()->json(['message' => 'No hay caja abierta para este tipo de pago.'], 422);
            }
            if ( $paymentType != 3 ) {
                // Crear el movimiento de ingreso (venta)
                CashMovement::create([
                    'cash_register_id' => $cashRegister->id,
                    'type' => 'sale', // Tipo de movimiento: venta
                    'amount' => $quote->total_importe,
                    'description' => 'Venta registrada con tipo de pago: ' . $paymentTypeMap[$paymentType],
                    'sale_id' => $sale->id
                ]);

                // Actualizar el saldo actual y el total de ventas en la caja
                $cashRegister->current_balance += $quote->total_importe;
                $cashRegister->total_sales += $quote->total_importe;
                $cashRegister->save();
            } else {
                // Crear el movimiento de ingreso (venta)
                CashMovement::create([
                    'cash_register_id' => $cashRegister->id,
                    'type' => 'sale', // Tipo de movimiento: venta
                    'amount' => $quote->total_importe,
                    'description' => 'Venta registrada con tipo de pago: ' . $paymentTypeMap[$paymentType],
                    'regularize' => 0,
                    'sale_id' => $sale->id
                ]);
            }


            // Registrar el vuelto como egreso si el tipo de pago es efectivo y hay vuelto

            $typeVuelto = $paymentTypeMap[$paymentType];
            $vuelto = 0;

            if ($paymentType == 4) {
                // Mapear el type_vuelto (la caja desde donde se dará el vuelto)
                $typeVueltoMap = [
                    'efectivo' => 'efectivo',
                    'yape' => 'yape',
                    'plin' => 'plin',
                    'bancario' => 'bancario'
                ];

                // Obtener la caja para el vuelto
                $vueltoCashRegister = CashRegister::where('type', $typeVueltoMap[$typeVuelto])
                    ->where('user_id', Auth::user()->id)
                    ->where('status', 1) // Caja abierta
                    ->latest()
                    ->first();

                if (!isset($vueltoCashRegister)) {
                    return response()->json(['message' => 'No hay caja abierta para dar el vuelto.'], 422);
                }

                // Crear el movimiento de egreso (vuelto)
                if ( $vuelto > 0)
                {
                    CashMovement::create([
                        'cash_register_id' => $vueltoCashRegister->id,
                        'type' => 'expense', // Tipo de movimiento: egreso
                        'amount' => $vuelto,
                        'description' => 'Vuelto entregado de la venta',
                        'sale_id' => $sale->id
                    ]);

                    // Actualizar el saldo de la caja del vuelto
                    $vueltoCashRegister->current_balance -= $vuelto;
                    $vueltoCashRegister->total_expenses += $vuelto;
                    $vueltoCashRegister->save();
                }

            }

            // Crear notificación
            $notification = Notification::create([
                'content' => 'Venta creada desde cotización por '.Auth::user()->name,
                'reason_for_creation' => 'create_sale_from_quote',
                'user_id' => Auth::user()->id,
                'url_go' => route('puntoVenta.index')
            ]);

            $users = User::role(['admin', 'principal', 'logistic'])->get();
            foreach ($users as $user) {
                if ($user->id != Auth::user()->id) {
                    foreach ($user->roles as $role) {
                        NotificationUser::create([
                            'notification_id' => $notification->id,
                            'role_id' => $role->id,
                            'user_id' => $user->id,
                            'read' => false
                        ]);
                    }
                }
            }

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action'  => 'Guardar venta desde cotización',
                'time'    => $end
            ]);

            DB::commit();

            // ✅ 5) Generar comprobante Nubefact (FUERA de la transacción)
            $nubefactResult = null;
            if (in_array($sale->type_document, ['01', '03'])) {
                try {
                    $sale->loadMissing(['details.material']);
                    $nubefactResult = $this->generarComprobanteNubefactParaVenta($sale);

                    // Guardar archivos y actualizar campos (reusamos lógica similar a tu NubefactController)
                    $filename = 'ORD' . $sale->id;
                    $pdfFilename = $filename . '.pdf';
                    $xmlFilename = $filename . '.xml';
                    $cdrFilename = $filename . '.zip';

                    foreach (['pdfs', 'xmls', 'cdrs'] as $folder) {
                        if (!file_exists(public_path("comprobantes/$folder"))) {
                            mkdir(public_path("comprobantes/$folder"), 0777, true);
                        }
                    }

                    if (!empty($nubefactResult['enlace_del_pdf'])) {
                        $pdfContent = Http::get($nubefactResult['enlace_del_pdf'])->body();
                        file_put_contents(public_path('comprobantes/pdfs/' . $pdfFilename), $pdfContent);
                    }
                    if (!empty($nubefactResult['enlace_del_xml'])) {
                        $xmlContent = Http::get($nubefactResult['enlace_del_xml'])->body();
                        file_put_contents(public_path('comprobantes/xmls/' . $xmlFilename), $xmlContent);
                    }
                    if (!empty($nubefactResult['enlace_del_cdr'])) {
                        $cdrContent = Http::get($nubefactResult['enlace_del_cdr'])->body();
                        file_put_contents(public_path('comprobantes/cdrs/' . $cdrFilename), $cdrContent);
                    }

                    $sale->update([
                        'serie_sunat' => $nubefactResult['serie'] ?? null,
                        'numero' => $nubefactResult['numero'] ?? null,
                        'sunat_ticket' => $nubefactResult['sunat_ticket'] ?? null,
                        'sunat_status' => $nubefactResult['sunat_description'] ?? 'Enviado',
                        'sunat_message' => $nubefactResult['sunat_note'] ?? '',
                        'xml_path' => file_exists(public_path('comprobantes/xmls/' . $xmlFilename)) ? $xmlFilename : null,
                        'cdr_path' => file_exists(public_path('comprobantes/cdrs/' . $cdrFilename)) ? $cdrFilename : null,
                        'pdf_path' => file_exists(public_path('comprobantes/pdfs/' . $pdfFilename)) ? $pdfFilename : null,
                        'fecha_emision' => now()->toDateString(),
                    ]);

                } catch (\Throwable $e) {
                    // Si Nubefact falla, NO tumbamos la venta: devolvemos warning
                    $sale->update([
                        'sunat_status' => 'Error',
                        'sunat_message' => $e->getMessage(),
                    ]);
                }
            }

            // Por defecto: ticket interno
            $urlPrint = route('puntoVenta.print', $sale->id);
            $printType = 'ticket';

            // Si es boleta/factura, preferimos PDF SUNAT (si existe)
            if (in_array($sale->type_document, ['01', '03'])) {

                // Caso A: tenemos link directo de nubefact en la respuesta
                if (!empty($nubefactResult['enlace_del_pdf'])) {
                    $urlPrint = $nubefactResult['enlace_del_pdf'];
                }

                // Caso B (recomendado): usar el PDF guardado localmente (si existe)
                if (!empty($sale->pdf_path)) {
                    // Prioridad 1: PDF guardado localmente
                    if (!empty($sale->pdf_path)) {
                        $localPath = public_path('comprobantes/pdfs/' . $sale->pdf_path);
                        if (file_exists($localPath)) {
                            $urlPrint  = asset('comprobantes/pdfs/' . $sale->pdf_path);
                            $printType = 'sunat_pdf';
                        }
                    }
                    // Prioridad 2: enlace directo de Nubefact
                    elseif (!empty($nubefactResult['enlace_del_pdf'])) {
                        $urlPrint  = $nubefactResult['enlace_del_pdf'];
                        $printType = 'sunat_pdf';
                    }
                }
            }
            return response()->json([
                'message' => 'Venta creada con éxito desde cotización' . ($nubefactResult ? ' y comprobante generado.' : ' (sin comprobante).'),
                'sale_id' => $sale->id,
                'nubefact' => $nubefactResult,
                'url_print' => $urlPrint,
                'print_type' => $printType,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function storeFromQuoteO3(Request $request)
    {
        $begin = microtime(true);

        $worker = Worker::where('user_id', Auth::id())->firstOrFail();

        $dataCurrency = DataGeneral::where('name', 'type_current')->first();
        $currency = $dataCurrency ? $dataCurrency->valueText : 'pen';

        // ===========================
        // ✅ Validación CashBox/Subtype
        // ===========================
        $cashBoxId        = $request->input('pv_cash_box_id');
        $cashBoxSubtypeId = $request->input('pv_cash_box_subtype_id');

        if (!$cashBoxId) {
            return response()->json(['message' => 'Seleccione una caja (CashBox).'], 422);
        }

        $cashBox = CashBox::find($cashBoxId);

        if (!$cashBox || !(bool)$cashBox->is_active) {
            return response()->json(['message' => 'La caja seleccionada no es válida o está inactiva.'], 422);
        }

        $needSubtype = ($cashBox->type === 'bank' && (bool)$cashBox->uses_subtypes);

        if ($needSubtype) {
            if (!$cashBoxSubtypeId) {
                return response()->json(['message' => 'Seleccione el canal/subtipo (Yape/Plin/POS/Transfer).'], 422);
            }

            // ✅ Subtypes globales (no dependen de cash_box_id)
            $validSubtype = CashBoxSubtype::whereNull('cash_box_id')
                ->where('is_active', 1)
                ->where('id', (int)$cashBoxSubtypeId)
                ->first();

            if (!$validSubtype) {
                return response()->json(['message' => 'El subtipo seleccionado no es válido o está inactivo.'], 422);
            }
        } else {
            $cashBoxSubtypeId = null;
        }

        DB::beginTransaction();
        try {
            $quote = Quote::with(['equipments.consumables.material'])->findOrFail($request->quote_id);

            if (!$quote || $quote->state !== 'confirmed') {
                throw new \Exception("La cotización no es válida o no está confirmada.");
            }

            // ===========================
            // 1) Crear venta (Sale)
            // ===========================
            $sale = Sale::create([
                'date_sale' => $request->filled('fechaDocumento')
                    ? Carbon::createFromFormat('Y-m-d', $request->fechaDocumento)
                    : Carbon::now(),

                'serie'     => $this->generateRandomString(),
                'worker_id' => $worker->id,
                'caja'      => $worker->id,
                'currency'  => ($currency === 'usd') ? 'USD' : 'PEN',

                'op_exonerada'     => 0,
                'op_inafecta'      => 0,
                'op_gravada'       => $quote->gravada,
                'igv'              => $quote->igv_total,
                'total_descuentos' => $quote->descuento,
                'importe_total'    => $quote->total_importe,
                'vuelto'           => 0,

                'quote_id' => $quote->id,

                // ❌ Ya NO usamos tipo_pago_id
                // 'tipo_pago_id' => ...

                'type_document'            => $request->type_document,
                'numero_documento_cliente' => $request->numero_documento_cliente,
                'tipo_documento_cliente'   => $request->tipo_documento_cliente,
                'nombre_cliente'           => $request->nombre_cliente,
                'direccion_cliente'        => $request->direccion_cliente,
                'email_cliente'            => $request->email_cliente,

                'serie_sunat'   => null,
                'numero'        => null,
                'sunat_ticket'  => null,
                'sunat_status'  => null,
                'sunat_message' => null,
                'xml_path'      => null,
                'cdr_path'      => null,
                'pdf_path'      => null,
                'fecha_emision' => null,
            ]);

            $saleDate = $sale->date_sale instanceof Carbon
                ? $sale->date_sale
                : Carbon::parse($sale->date_sale);

            // recolectar materialIds
            $materialIds = [];
            foreach ($quote->equipments as $equipment) {
                foreach ($equipment->consumables as $consumable) {
                    $materialIds[] = (int)$consumable->material_id;
                }
            }
            $materialIds = array_values(array_unique($materialIds));

            // costo promedio vigente por material hasta la fecha de emisión
            $avgCosts = $this->inventoryCostService->getAverageCostsUpToDate($materialIds, $saleDate);

            // ===========================
            // 2) Crear Output
            // ===========================
            $output = Output::create([
                'execution_order'   => ($quote->order_execution == "") ? 'VENTA POR COTIZACION' : $quote->order_execution,
                'request_date'      => $sale->date_sale,
                'requesting_user'   => Auth::id(),
                'responsible_user'  => Auth::id(),
                'state'             => 'confirmed',
                'indicator'         => 'or',
            ]);

            $igv = (float) PorcentageQuote::where('name', 'igv')->value('value'); // 18.00
            $factor = bcadd('1', bcdiv((string)$igv, '100', 10), 10); // 1.1800000000

            // ===========================
            // 3) Detalles + stock + output details
            // ===========================
            foreach ($quote->equipments as $equipment) {
                // 1) Consumables (tal cual lo tienes)
                foreach ($equipment->consumables as $consumable) {

                    $materialId = (int)$consumable->material_id;
                    $qty        = (float)$consumable->quantity;

                    $unitCost  = (float)($avgCosts[$materialId] ?? 0.0);
                    $totalCost = $qty * $unitCost;

                    $saleDetail = SaleDetail::create([
                        'sale_id'                  => $sale->id,
                        'material_id'              => $consumable->material_id,
                        'material_presentation_id' => $consumable->material_presentation_id,
                        'valor_unitario'           => $consumable->valor_unitario,
                        'price'                    => $consumable->price,
                        'quantity'                 => $consumable->quantity,
                        'packs'                    => $consumable->packs,
                        'units_per_pack'           => $consumable->units_per_pack,
                        'percentage_tax'           => 18,
                        'total'                    => $consumable->total,
                        'discount'                 => $consumable->discount,

                        // snapshot costos
                        'unit_cost'                => $unitCost,
                        'total_cost'               => $totalCost,
                    ]);

                    $material = $consumable->material;

                    if ($material) {
                        //$material->unit_price     = $unitCost;
                        $material->stock_current  = max(0, (float)$material->stock_current  - (float)$consumable->quantity);
                        $material->stock_reserved = max(0, (float)$material->stock_reserved - (float)$consumable->quantity);
                        $material->save();
                    }

                    $qtyNeeded = (float)$consumable->quantity;

                    if (floor($qtyNeeded) != $qtyNeeded) {
                        throw new \Exception("La cotización requiere cantidad decimal para el material {$materialId}. Aún no está implementado para retazos.");
                    }

                    $qtyNeeded = (int)$qtyNeeded;

                    if ($material && (int)$material->tipo_venta_id === 3) {

                        $items = Item::where('material_id', $materialId)
                            ->whereIn('state_item', ['entered', 'scrapped'])
                            ->orderBy('id', 'asc')
                            ->lockForUpdate()
                            ->take($qtyNeeded)
                            ->get();

                        if ($items->count() < $qtyNeeded) {
                            throw new \Exception("Stock insuficiente del material ID {$materialId}. Se requieren {$qtyNeeded} y solo hay {$items->count()} disponibles.");
                        }

                        foreach ($items as $item) {

                            OutputDetail::create([
                                'output_id'      => $output->id,
                                'sale_detail_id' => $saleDetail->id,
                                'item_id'        => $item->id,
                                'material_id'    => $materialId,
                                'quote_id'       => $quote->id,
                                'custom'         => 0,
                                'percentage'     => 1,
                                'price'          => $item->price,
                                'length'         => $item->length,
                                'width'          => $item->width,
                                'equipment_id'   => $equipment->id ?? null,
                                'activo'         => null,
                                'unit_cost'      => $unitCost,
                                'total_cost'     => $unitCost,
                            ]);

                            $item->state_item = 'exited';
                            $item->save();
                        }
                    }

                    if ($material && (int)$material->tipo_venta_id !== 3) {
                        OutputDetail::create([
                            'output_id'      => $output->id,
                            'sale_detail_id' => $saleDetail->id,
                            'item_id'        => null,
                            'material_id'    => $materialId,
                            'quote_id'       => $quote->id,
                            'custom'         => 0,
                            'percentage'     => $qty,
                            'price'          => $consumable->price,
                            'length'         => null,
                            'width'          => null,
                            'equipment_id'   => $equipment->id ?? null,
                            'activo'         => null,
                            'unit_cost'      => $unitCost,
                            'total_cost'     => $totalCost,
                        ]);
                    }
                }

                // 2) Workforces (solo billable = 1)
                foreach ($equipment->workforces->where('billable', 1) as $wf) {

                    $qty = (string) $wf->quantity;
                    $priceWithIgv = (string) $wf->price; // precio unitario con IGV

                    // 🔥 valor_unitario SIN IGV (20,10)
                    $valorUnitario = bcdiv($priceWithIgv, $factor, 10);

                    // 🔥 total sin IGV
                    $totalSinIgv = bcmul($valorUnitario, $qty, 10);

                    // 🔥 IGV del item
                    $igvItem = bcsub((string)$wf->total, $totalSinIgv, 10);

                    $saleDetail = SaleDetail::create([
                        'sale_id'                  => $sale->id,
                        'material_id'              => null,
                        'material_presentation_id' => null,
                        'description'              => $wf->description,

                        'valor_unitario'           => $valorUnitario, // 🔥 SIN IGV
                        'price'                    => $priceWithIgv,  // CON IGV
                        'quantity'                 => $qty,
                        'packs'                    => null,
                        'units_per_pack'           => null,
                        'percentage_tax'           => $igv,

                        'total'                    => $wf->total,     // total CON IGV
                        'discount'                 => 0,

                        'unit_cost'                => '0.0000000000',
                        'total_cost'               => '0.0000000000',
                    ]);
                }

            }

            // ===========================
            // 4) Caja: movimiento (SIN VUELTO)
            // ===========================
            $cashRegisterQuery = CashRegister::where('cash_box_id', $cashBox->id)
                ->where('user_id', Auth::id())
                ->where('status', 1)
                ->latest();

            // Si tu tabla cash_registers tiene cash_box_subtype_id, filtramos
            if ($needSubtype && $cashBoxSubtypeId && Schema::hasColumn('cash_registers', 'cash_box_subtype_id')) {
                $cashRegisterQuery->where('cash_box_subtype_id', (int)$cashBoxSubtypeId);
            }

            $cashRegister = $cashRegisterQuery->first();

            if (!$cashRegister) {
                return response()->json(['message' => 'No hay caja abierta para la caja/canal seleccionado.'], 422);
            }

            $amount = (float)$quote->total_importe;

            $description = 'Venta desde cotización - ' . ($cashBox->name ?? 'Caja');
            if ($needSubtype && $cashBoxSubtypeId) {
                $sub = CashBoxSubtype::find((int)$cashBoxSubtypeId);
                if ($sub) $description .= ' (' . $sub->name . ')';
            }

            // ===========================
            // Determinar regularize según subtipo (MISMA LÓGICA QUE POS)
            // ===========================
            $regularize = 1;

            if ($cashBox->type === 'bank' && (int) $cashBox->uses_subtypes === 1) {

                if (!$cashBoxSubtypeId) {
                    return response()->json([
                        'message' => 'Debe seleccionar el subtipo bancario (Yape / Plin / POS / Transfer).'
                    ], 422);
                }

                $subtype = CashBoxSubtype::findOrFail($cashBoxSubtypeId);

                // 👉 si es diferido, NO se regulariza aún
                $regularize = $subtype->is_deferred ? 0 : 1;
            } else {
                // por seguridad, si no aplica, lo anulamos
                $cashBoxSubtypeId = null;
            }

            // ===========================
            // Construir movimiento
            // ===========================
            $movement = [
                'cash_register_id'      => $cashRegister->id,
                'type'                  => 'sale',
                'amount'                => $amount,
                'description'           => $description,
                'sale_id'               => $sale->id,
                // ✅ correcto
                'regularize'            => $regularize,
            ];

            // Si hay subtipo, lo seteamos (tu modelo SÍ lo tiene)
            if (!is_null($cashBoxSubtypeId)) {
                $movement['cash_box_subtype_id'] = (int) $cashBoxSubtypeId;
            }

            CashMovement::create($movement);

            // Si NO es bank, actualizamos saldos (si aplica en tu modelo)
            if ($regularize == 1) {
                $cashRegister->current_balance += $amount;
                $cashRegister->total_sales     += $amount;
                $cashRegister->save();
            }

            // ===========================
            // Notificación + Auditoría
            // ===========================
            $notification = Notification::create([
                'content' => 'Venta creada desde cotización por ' . Auth::user()->name,
                'reason_for_creation' => 'create_sale_from_quote',
                'user_id' => Auth::id(),
                'url_go' => route('puntoVenta.index')
            ]);

            $users = User::role(['admin', 'principal', 'logistic'])->get();
            foreach ($users as $u) {
                if ($u->id != Auth::id()) {
                    foreach ($u->roles as $role) {
                        NotificationUser::create([
                            'notification_id' => $notification->id,
                            'role_id' => $role->id,
                            'user_id' => $u->id,
                            'read' => false
                        ]);
                    }
                }
            }

            $elapsed = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::id(),
                'action'  => 'Guardar venta desde cotización',
                'time'    => $elapsed
            ]);

            DB::commit();

            // ===========================
            // 5) Nubefact (igual que tu bloque)
            // ===========================
            $nubefactResult = null;

            if (in_array($sale->type_document, ['01', '03'])) {
                try {
                    $sale->loadMissing(['details.material']);
                    $nubefactResult = $this->generarComprobanteNubefactParaVenta($sale);

                    $filename = 'ORD' . $sale->id;
                    $pdfFilename = $filename . '.pdf';
                    $xmlFilename = $filename . '.xml';
                    $cdrFilename = $filename . '.zip';

                    foreach (['pdfs', 'xmls', 'cdrs'] as $folder) {
                        if (!file_exists(public_path("comprobantes/$folder"))) {
                            mkdir(public_path("comprobantes/$folder"), 0777, true);
                        }
                    }

                    if (!empty($nubefactResult['enlace_del_pdf'])) {
                        $pdfContent = Http::get($nubefactResult['enlace_del_pdf'])->body();
                        file_put_contents(public_path('comprobantes/pdfs/' . $pdfFilename), $pdfContent);
                    }
                    if (!empty($nubefactResult['enlace_del_xml'])) {
                        $xmlContent = Http::get($nubefactResult['enlace_del_xml'])->body();
                        file_put_contents(public_path('comprobantes/xmls/' . $xmlFilename), $xmlContent);
                    }
                    if (!empty($nubefactResult['enlace_del_cdr'])) {
                        $cdrContent = Http::get($nubefactResult['enlace_del_cdr'])->body();
                        file_put_contents(public_path('comprobantes/cdrs/' . $cdrFilename), $cdrContent);
                    }

                    $sale->update([
                        'serie_sunat'   => $nubefactResult['serie'] ?? null,
                        'numero'        => $nubefactResult['numero'] ?? null,
                        'sunat_ticket'  => $nubefactResult['sunat_ticket'] ?? null,
                        'sunat_status'  => $nubefactResult['sunat_description'] ?? 'Enviado',
                        'sunat_message' => $nubefactResult['sunat_note'] ?? '',
                        'xml_path'      => file_exists(public_path('comprobantes/xmls/' . $xmlFilename)) ? $xmlFilename : null,
                        'cdr_path'      => file_exists(public_path('comprobantes/cdrs/' . $cdrFilename)) ? $cdrFilename : null,
                        'pdf_path'      => file_exists(public_path('comprobantes/pdfs/' . $pdfFilename)) ? $pdfFilename : null,
                        'fecha_emision' => now()->toDateString(),
                    ]);

                } catch (\Throwable $e) {
                    $sale->update([
                        'sunat_status'  => 'Error',
                        'sunat_message' => $e->getMessage(),
                    ]);
                }
            }

            // ===========================
            // URL impresión
            // ===========================
            $urlPrint  = route('puntoVenta.print', $sale->id);
            $printType = 'ticket';

            if (in_array($sale->type_document, ['01', '03'])) {
                if (!empty($nubefactResult['enlace_del_pdf'])) {
                    $urlPrint = $nubefactResult['enlace_del_pdf'];
                }
                if (!empty($sale->pdf_path)) {
                    $localPath = public_path('comprobantes/pdfs/' . $sale->pdf_path);
                    if (file_exists($localPath)) {
                        $urlPrint  = asset('comprobantes/pdfs/' . $sale->pdf_path);
                        $printType = 'sunat_pdf';
                    } elseif (!empty($nubefactResult['enlace_del_pdf'])) {
                        $urlPrint  = $nubefactResult['enlace_del_pdf'];
                        $printType = 'sunat_pdf';
                    }
                }
            }

            return response()->json([
                'message'    => 'Venta creada con éxito desde cotización' . ($nubefactResult ? ' y comprobante generado.' : ' (sin comprobante).'),
                'sale_id'    => $sale->id,
                'nubefact'   => $nubefactResult,
                'url_print'  => $urlPrint,
                'print_type' => $printType,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function storeFromQuote(Request $request)
    {
        $begin = microtime(true);

        $worker = Worker::where('user_id', Auth::id())->firstOrFail();

        $dataCurrency = DataGeneral::where('name', 'type_current')->first();
        $currency = $dataCurrency ? $dataCurrency->valueText : 'pen';

        $negocioAceptaPagosParciales = $request->input('negocio_acepta_pagos_parciales', 'n');
        $pagosParcialesVenta = $request->input('pagos_parciales_venta', 'n');

        if (!in_array($pagosParcialesVenta, ['s', 'n'])) {
            throw new \Exception("Valor inválido para pagos parciales.");
        }

        if ($pagosParcialesVenta === 's' && $negocioAceptaPagosParciales !== 's') {
            throw new \Exception("Este negocio no tiene habilitados los pagos parciales.");
        }

        // ===========================
        // ✅ Validación CashBox/Subtype
        // Solo aplica si NO es pago parcial
        // ===========================
        $cashBoxId = null;
        $cashBoxSubtypeId = null;
        $cashBox = null;
        $needSubtype = false;

        if ($pagosParcialesVenta === 'n') {

            $cashBoxId = $request->input('pv_cash_box_id');
            $cashBoxSubtypeId = $request->input('pv_cash_box_subtype_id');

            if (!$cashBoxId) {
                return response()->json(['message' => 'Seleccione una caja (CashBox).'], 422);
            }

            $cashBox = CashBox::find($cashBoxId);

            if (!$cashBox || !(bool)$cashBox->is_active) {
                return response()->json(['message' => 'La caja seleccionada no es válida o está inactiva.'], 422);
            }

            $needSubtype = ($cashBox->type === 'bank' && (bool)$cashBox->uses_subtypes);

            if ($needSubtype) {
                if (!$cashBoxSubtypeId) {
                    return response()->json(['message' => 'Seleccione el canal/subtipo (Yape/Plin/POS/Transfer).'], 422);
                }

                $validSubtype = CashBoxSubtype::whereNull('cash_box_id')
                    ->where('is_active', 1)
                    ->where('id', (int)$cashBoxSubtypeId)
                    ->first();

                if (!$validSubtype) {
                    return response()->json(['message' => 'El subtipo seleccionado no es válido o está inactivo.'], 422);
                }
            } else {
                $cashBoxSubtypeId = null;
            }
        }

        DB::beginTransaction();
        try {
            $quote = Quote::with([
                'equipments.consumables.material',
                'equipments.consumables.stockItem',
                'equipments.workforces',
            ])
                ->where('id', $request->quote_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$quote || $quote->state !== 'confirmed') {
                throw new \Exception("La cotización no es válida o no está confirmada.");
            }

            $activeSale = Sale::where('quote_id', $quote->id)
                ->where('state_annulled', 0)
                ->lockForUpdate()
                ->first();

            if ($activeSale) {
                throw new \Exception("La cotización ya fue convertida en una venta activa.");
            }

            /* $tipoComprobante = $request->tipo_documento_cliente; // 01 factura, 03 boleta
            $typeDocument = $request->type_document; // 1 DNI, 6 RUC
            $numeroDocumento = preg_replace('/\D/', '', $request->numero_documento_cliente);*/

            $tipoComprobante = $request->type_document; // 01 factura, 03 boleta
            $typeDocument = $request->tipo_documento_cliente; // 1 DNI, 6 RUC
            $numeroDocumento = preg_replace('/\D/', '', $request->numero_documento_cliente ?? '');

            if ($pagosParcialesVenta === 'n') {

                // Boleta = DNI
                if ($tipoComprobante === '03') {

                    if ((string)$typeDocument !== '1') {
                        throw new \Exception("Para emitir una boleta, el tipo de documento debe ser DNI.");
                    }

                    if (!preg_match('/^\d{8}$/', $numeroDocumento)) {
                        throw new \Exception("El DNI debe contener exactamente 8 dígitos.");
                    }
                }

                // Factura = RUC
                if ($tipoComprobante === '01') {

                    if ((string)$typeDocument !== '6') {
                        throw new \Exception("Para emitir una factura, el tipo de documento debe ser RUC.");
                    }

                    if (!preg_match('/^\d{11}$/', $numeroDocumento)) {
                        throw new \Exception("El RUC debe contener exactamente 11 dígitos.");
                    }
                }
            }

            // ===========================
            // 1) Crear venta (Sale)
            // ===========================
            $sale = Sale::create([
                'date_sale' => $request->filled('fechaDocumento')
                    ? Carbon::createFromFormat('Y-m-d', $request->fechaDocumento)
                    : Carbon::now(),

                'serie'     => $this->generateRandomString(),
                'worker_id' => $worker->id,
                'caja'      => $worker->id,
                'currency'  => ($currency === 'usd') ? 'USD' : 'PEN',

                'op_exonerada'     => 0,
                'op_inafecta'      => 0,
                'op_gravada'       => $quote->gravada,
                'igv'              => $quote->igv_total,
                'total_descuentos' => $quote->descuento,
                'importe_total'    => $quote->total_importe,
                'vuelto'           => 0,

                'quote_id' => $quote->id,
                'dispatch_status' => ($pagosParcialesVenta === 's') ? 'pendiente': 'despachado',

                'type_document' => $pagosParcialesVenta === 's'
                    ? null
                    : $request->type_document,

                'numero_documento_cliente' => $pagosParcialesVenta === 's'
                    ? null
                    : $numeroDocumento,

                'tipo_documento_cliente' => $pagosParcialesVenta === 's'
                    ? null
                    : $request->tipo_documento_cliente,

                'nombre_cliente' => $pagosParcialesVenta === 's'
                    ? null
                    : $request->nombre_cliente,

                'direccion_cliente' => $pagosParcialesVenta === 's'
                    ? null
                    : $request->direccion_cliente,

                'email_cliente' => $pagosParcialesVenta === 's'
                    ? null
                    : $request->email_cliente,

                'pagos_parciales_venta' => $pagosParcialesVenta,

                'serie_sunat'   => null,
                'numero'        => null,
                'sunat_ticket'  => null,
                'sunat_status'  => null,
                'sunat_message' => null,
                'xml_path'      => null,
                'cdr_path'      => null,
                'pdf_path'      => null,
                'fecha_emision' => null,
            ]);

            // ===========================
            // 2) Crear Output
            // ===========================
            $output = Output::create([
                'execution_order'   => ($quote->order_execution == "") ? 'VENTA POR COTIZACION' : $quote->order_execution,
                'request_date'      => $sale->date_sale,
                'requesting_user'   => Auth::id(),
                'responsible_user'  => Auth::id(),
                'state'             => 'confirmed',
                'indicator'         => 'or',
            ]);

            $igv = (float) PorcentageQuote::where('name', 'igv')->value('value');
            $factor = bcadd('1', bcdiv((string)$igv, '100', 10), 10);

            // ===========================
            // 3) Detalles + consumo real de reservas/lotes
            // ===========================
            foreach ($quote->equipments as $equipment) {

                foreach ($equipment->consumables as $consumable) {
                    $materialId  = (int) $consumable->material_id;
                    $stockItemId = (int) $consumable->stock_item_id;
                    $qty         = (float) $consumable->quantity;

                    if (!$stockItemId) {
                        throw new \Exception("El consumible {$consumable->id} no tiene stock_item_id.");
                    }

                    $material = $consumable->material;

                    $reservedLots = QuoteStockLot::where('quote_id', $quote->id)
                        ->where('quote_detail_id', $consumable->id)
                        ->where('stock_item_id', $stockItemId)
                        ->orderBy('id', 'asc')
                        ->lockForUpdate()
                        ->get();

                    if ($reservedLots->isEmpty()) {
                        throw new \Exception("No se encontraron reservas para el consumible {$consumable->id}.");
                    }

                    $reservedQty = (float) $reservedLots->sum('quantity');
                    if (bccomp((string) $reservedQty, (string) $qty, 10) < 0) {
                        throw new \Exception(
                            "Las reservas del consumible {$consumable->id} son insuficientes. " .
                            "Reservado: {$reservedQty}. Requerido: {$qty}."
                        );
                    }

                    $totalCost = (float) $reservedLots->sum(function ($row) {
                        return (float) $row->quantity * (float) $row->unit_cost;
                    });
                    $unitCost = $qty > 0 ? ($totalCost / $qty) : 0;

                    $saleDetailData = [
                        'sale_id'                  => $sale->id,
                        'material_id'              => $materialId,
                        'stock_item_id'            => $stockItemId,
                        'material_presentation_id' => $consumable->material_presentation_id,
                        'valor_unitario'           => $consumable->valor_unitario,
                        'price'                    => $consumable->price,
                        'quantity'                 => $consumable->quantity,
                        'packs'                    => $consumable->packs,
                        'units_per_pack'           => $consumable->units_per_pack,
                        'percentage_tax'           => 18,
                        'total'                    => $consumable->total,
                        'discount'                 => $consumable->discount,
                        'unit_cost'                => $unitCost,
                        'total_cost'               => $totalCost,
                        'item_snapshot'            => null,
                    ];

                    if (Schema::hasColumn('sale_details', 'stock_item_id')) {
                        $saleDetailData['stock_item_id'] = $stockItemId;
                    }

                    $saleDetail = SaleDetail::create($saleDetailData);

                    if ($material && (int) $material->tipo_venta_id === 3) {

                        $itemSnapshot = [];

                        if (floor($qty) != $qty) {
                            throw new \Exception(
                                "La cotización requiere cantidad decimal para el material {$materialId}. " .
                                "Aún no está implementado para retazos."
                            );
                        }

                        foreach ($reservedLots as $reservedLot) {
                            $consumeQty = (float) $reservedLot->quantity;

                            if ($consumeQty <= 0) {
                                continue;
                            }

                            if (floor($consumeQty) != $consumeQty) {
                                throw new \Exception(
                                    "La reserva del lote {$reservedLot->stock_lot_id} tiene cantidad decimal y no está implementado para items unitarios."
                                );
                            }

                            $consumeQtyInt = (int) $consumeQty;

                            /*
                             * Los IDs no se buscan nuevamente por FIFO.
                             * Deben ser exactamente los ítems físicos que se reservaron
                             * al guardar la cotización.
                             */
                            $reservedItemIds = $reservedLot->item_ids ?? [];

                            if (is_string($reservedItemIds)) {
                                $decodedItemIds = json_decode($reservedItemIds, true);
                                $reservedItemIds = is_array($decodedItemIds)
                                    ? $decodedItemIds
                                    : [];
                            }

                            if (!is_array($reservedItemIds)) {
                                $reservedItemIds = [];
                            }

                            $reservedItemIds = collect($reservedItemIds)
                                ->map(function ($itemId) {
                                    return (int) $itemId;
                                })
                                ->filter(function ($itemId) {
                                    return $itemId > 0;
                                })
                                ->unique()
                                ->values()
                                ->toArray();

                            if (count($reservedItemIds) !== $consumeQtyInt) {
                                throw new \Exception(
                                    "Los ítems reservados no coinciden con la cantidad del lote {$reservedLot->stock_lot_id}. " .
                                    "Esperados: {$consumeQtyInt}. Registrados: " . count($reservedItemIds) . '.'
                                );
                            }

                            /*
                             * Se bloquean únicamente los Items seleccionados en cotización.
                             * Deben seguir reservados y pertenecer al mismo StockItem/lote.
                             */
                            $reservedItems = Item::whereIn('id', $reservedItemIds)
                                ->where('stock_item_id', $stockItemId)
                                ->where('stock_lot_id', $reservedLot->stock_lot_id)
                                ->where('percentage', 1)
                                ->where('state_item', 'reserved')
                                ->lockForUpdate()
                                ->get();

                            if ($reservedItems->count() !== $consumeQtyInt) {
                                throw new \Exception(
                                    "Uno o más ítems reservados ya no están disponibles para completar la venta del lote {$reservedLot->stock_lot_id}."
                                );
                            }

                            $lot = StockLot::where('id', $reservedLot->stock_lot_id)
                                ->lockForUpdate()
                                ->first();

                            if (!$lot) {
                                throw new \Exception(
                                    "No se encontró el lote reservado {$reservedLot->stock_lot_id}."
                                );
                            }

                            if (
                                (float) $lot->qty_reserved < $consumeQty ||
                                (float) $lot->qty_on_hand < $consumeQty
                            ) {
                                throw new \Exception(
                                    "El lote {$lot->id} no tiene stock/reserva suficiente para consumirse."
                                );
                            }

                            foreach ($reservedItems as $item) {
                                $itemSnapshot[] = [
                                    'id' => (int) $item->id,
                                    'code' => (string) $item->code,
                                ];

                                OutputDetail::create([
                                    'output_id' => $output->id,
                                    'sale_detail_id' => $saleDetail->id,

                                    'item_id' => $item->id,
                                    'material_id' => $materialId,
                                    'stock_item_id' => $item->stock_item_id,
                                    'stock_lot_id' => $item->stock_lot_id,
                                    'warehouse_id' => $item->warehouse_id,
                                    'location_id' => $item->location_id,

                                    'quote_id' => $quote->id,
                                    'custom' => 0,
                                    'percentage' => 1,

                                    'price' => $item->price,
                                    'length' => $item->length,
                                    'width' => $item->width,

                                    'equipment_id' => $equipment->id ?? null,
                                    'activo' => null,

                                    'unit_cost' => (float) (
                                        $item->unit_cost ?? $reservedLot->unit_cost
                                    ),

                                    'total_cost' => (float) (
                                        $item->unit_cost ?? $reservedLot->unit_cost
                                    ),
                                ]);

                                $item->state_item = 'exited';
                                $item->save();
                            }

                            /*
                             * La reserva ahora se convierte en salida real.
                             */
                            $lot->qty_on_hand = (float) $lot->qty_on_hand - $consumeQty;
                            $lot->qty_reserved = (float) $lot->qty_reserved - $consumeQty;
                            $lot->save();

                            $this->syncInventoryLevelFromLots(
                                $stockItemId,
                                $lot->warehouse_id,
                                $lot->location_id
                            );

                            /*
                             * Ya no existe reserva pendiente para este lote.
                             */
                            $reservedLot->delete();
                        }

                        $saleDetail->item_snapshot = $itemSnapshot;
                        $saleDetail->save();

                    } else {

                        foreach ($reservedLots as $reservedLot) {
                            $consumeQty = (float) $reservedLot->quantity;

                            if ($consumeQty <= 0) {
                                continue;
                            }

                            $lot = StockLot::where('id', $reservedLot->stock_lot_id)
                                ->lockForUpdate()
                                ->first();

                            if (!$lot) {
                                throw new \Exception("No se encontró el lote reservado {$reservedLot->stock_lot_id}.");
                            }

                            if ((float) $lot->qty_reserved < $consumeQty) {
                                throw new \Exception(
                                    "El lote {$lot->id} no tiene reserva suficiente. " .
                                    "Reservado en lote: {$lot->qty_reserved}. Requerido: {$consumeQty}."
                                );
                            }

                            if ((float) $lot->qty_on_hand < $consumeQty) {
                                throw new \Exception(
                                    "El lote {$lot->id} no tiene stock suficiente para consumirse. " .
                                    "Stock: {$lot->qty_on_hand}. Requerido: {$consumeQty}."
                                );
                            }

                            $lot->qty_on_hand  = (float) $lot->qty_on_hand - $consumeQty;
                            $lot->qty_reserved = (float) $lot->qty_reserved - $consumeQty;
                            $lot->save();

                            $this->syncInventoryLevelFromLots(
                                $stockItemId,
                                $lot->warehouse_id,
                                $lot->location_id
                            );

                            OutputDetail::create([
                                'output_id'      => $output->id,
                                'sale_detail_id' => $saleDetail->id,
                                'item_id'        => null,
                                'material_id'    => $materialId,
                                'stock_item_id'  => $stockItemId,
                                'stock_lot_id'   => $lot->id,
                                'warehouse_id'   => $lot->warehouse_id,
                                'location_id'    => $lot->location_id,
                                'quote_id'       => $quote->id,
                                'custom'         => 0,
                                'percentage'     => $consumeQty,
                                'price'          => $consumable->price,
                                'length'         => null,
                                'width'          => null,
                                'equipment_id'   => $equipment->id ?? null,
                                'activo'         => null,
                                'unit_cost'      => (float) $reservedLot->unit_cost,
                                'total_cost'     => (float) $reservedLot->unit_cost * $consumeQty,
                            ]);

                            $reservedLot->delete();
                        }
                    }
                }

                foreach ($equipment->workforces->where('billable', 1) as $wf) {
                    $qty = (string) $wf->quantity;
                    $priceWithIgv = (string) $wf->price;

                    $valorUnitario = bcdiv($priceWithIgv, $factor, 10);

                    SaleDetail::create([
                        'sale_id'                  => $sale->id,
                        'material_id'              => null,
                        'stock_item_id'            => null,
                        'material_presentation_id' => null,
                        'description'              => $wf->description,
                        'valor_unitario'           => $valorUnitario,
                        'price'                    => $priceWithIgv,
                        'quantity'                 => $qty,
                        'packs'                    => null,
                        'units_per_pack'           => null,
                        'percentage_tax'           => $igv,
                        'total'                    => $wf->total,
                        'discount'                 => 0,
                        'unit_cost'                => '0.0000000000',
                        'total_cost'               => '0.0000000000',
                    ]);
                }
            }

            // ===========================
            // 4) Caja: movimiento (SIN VUELTO)
            // Solo aplica si NO es pago parcial
            // ===========================
            if ($pagosParcialesVenta === 'n') {
                $cashRegisterQuery = CashRegister::where('cash_box_id', $cashBox->id)
                    ->where('user_id', Auth::id())
                    ->where('status', 1)
                    ->latest();

                if ($needSubtype && $cashBoxSubtypeId && Schema::hasColumn('cash_registers', 'cash_box_subtype_id')) {
                    $cashRegisterQuery->where('cash_box_subtype_id', (int)$cashBoxSubtypeId);
                }

                $cashRegister = $cashRegisterQuery->first();

                if (!$cashRegister) {
                    return response()->json(['message' => 'No hay caja abierta para la caja/canal seleccionado.'], 422);
                }

                $amount = (float)$quote->total_importe;

                $description = 'Venta desde cotización - ' . ($cashBox->name ?? 'Caja');
                if ($needSubtype && $cashBoxSubtypeId) {
                    $sub = CashBoxSubtype::find((int)$cashBoxSubtypeId);
                    if ($sub) $description .= ' (' . $sub->name . ')';
                }

                $regularize = 1;

                if ($cashBox->type === 'bank' && (int)$cashBox->uses_subtypes === 1) {
                    if (!$cashBoxSubtypeId) {
                        return response()->json([
                            'message' => 'Debe seleccionar el subtipo bancario (Yape / Plin / POS / Transfer).'
                        ], 422);
                    }

                    $subtype = CashBoxSubtype::findOrFail($cashBoxSubtypeId);
                    $regularize = $subtype->is_deferred ? 0 : 1;
                } else {
                    $cashBoxSubtypeId = null;
                }

                $movement = [
                    'cash_register_id' => $cashRegister->id,
                    'type' => 'sale',
                    'amount' => $amount,
                    'description' => $description,
                    'sale_id' => $sale->id,
                    'regularize' => $regularize,
                ];

                if (!is_null($cashBoxSubtypeId)) {
                    $movement['cash_box_subtype_id'] = (int)$cashBoxSubtypeId;
                }

                CashMovement::create($movement);

                if ($regularize == 1) {
                    $cashRegister->current_balance += $amount;
                    $cashRegister->total_sales += $amount;
                    $cashRegister->save();
                }
            }

            // ===========================
            // Notificación + Auditoría
            // ===========================
            $notification = Notification::create([
                'content' => 'Venta creada desde cotización por ' . Auth::user()->name,
                'reason_for_creation' => 'create_sale_from_quote',
                'user_id' => Auth::id(),
                'url_go' => route('puntoVenta.index')
            ]);

            $users = User::role(['admin', 'principal', 'logistic'])->get();
            foreach ($users as $u) {
                if ($u->id != Auth::id()) {
                    foreach ($u->roles as $role) {
                        NotificationUser::create([
                            'notification_id' => $notification->id,
                            'role_id' => $role->id,
                            'user_id' => $u->id,
                            'read' => false
                        ]);
                    }
                }
            }

            $elapsed = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::id(),
                'action'  => 'Guardar venta desde cotización',
                'time'    => $elapsed
            ]);

            DB::commit();

            // ===========================
            // 5) Nubefact
            // ===========================
            $nubefactResult = null;

            if ($pagosParcialesVenta === 'n' && in_array($sale->type_document, ['01', '03'])) {
                /*
                 * PRIMER PROCESO:
                 * Emitir el comprobante en Nubefact.
                 */
                try {
                    $sale->loadMissing(['details.material']);

                    $nubefactResult = $this
                        ->generarComprobanteNubefactParaVenta($sale);

                    /*
                     * Guardamos inmediatamente los datos tributarios.
                     *
                     * Desde este momento sabemos que Nubefact procesó
                     * correctamente el comprobante, aunque después pudiera
                     * fallar la descarga de uno de los archivos.
                     */
                    $sale->update([
                        'serie_sunat' => $nubefactResult['serie'] ?? null,
                        'numero' => $nubefactResult['numero'] ?? null,
                        'sunat_ticket' => $nubefactResult['sunat_ticket'] ?? null,
                        'sunat_status' => $nubefactResult['sunat_description']
                            ?? 'Enviado',
                        'sunat_message' => $nubefactResult['sunat_note'] ?? '',
                        'fecha_emision' => now()->toDateString(),
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Error al generar comprobante en Nubefact', [
                        'sale_id' => $sale->id,
                        'error' => $e->getMessage(),
                    ]);

                    $sale->update([
                        'sunat_status' => 'Error',
                        'sunat_message' => $e->getMessage(),
                    ]);
                }

                /*
                 * SEGUNDO PROCESO:
                 * Descargar PDF, XML y CDR.
                 *
                 * Solamente se ejecuta si Nubefact devolvió un resultado.
                 */
                if (is_array($nubefactResult)) {
                    $filename = 'ORD' . $sale->id;

                    $pdfFilename = $filename . '.pdf';
                    $xmlFilename = $filename . '.xml';
                    $cdrFilename = $filename . '.zip';

                    $pdfPath = public_path(
                        'comprobantes/pdfs/' . $pdfFilename
                    );

                    $xmlPath = public_path(
                        'comprobantes/xmls/' . $xmlFilename
                    );

                    $cdrPath = public_path(
                        'comprobantes/cdrs/' . $cdrFilename
                    );

                    /*
                     * Creamos las carpetas con permisos estándar.
                     * Ya no usamos 0777.
                     */
                    foreach (['pdfs', 'xmls', 'cdrs'] as $folder) {
                        $directory = public_path(
                            'comprobantes/' . $folder
                        );

                        if (!is_dir($directory)) {
                            if (
                                !mkdir($directory, 0755, true) &&
                                !is_dir($directory)
                            ) {
                                Log::error(
                                    'No se pudo crear directorio de comprobantes',
                                    [
                                        'sale_id' => $sale->id,
                                        'directory' => $directory,
                                    ]
                                );
                            }
                        }
                    }

                    /*
                     * Las tres descargas son independientes.
                     *
                     * Si falla el PDF, todavía intentamos descargar XML y CDR.
                     * Si falla el XML, todavía intentamos descargar el CDR.
                     */
                    $pdfDescargado = false;
                    $xmlDescargado = false;
                    $cdrDescargado = false;

                    $erroresDescarga = [];

                    if (!empty($nubefactResult['enlace_del_pdf'])) {
                        try {
                            $this->descargarArchivoNubefactSeguro(
                                $nubefactResult['enlace_del_pdf'],
                                $pdfPath,
                                'pdf'
                            );

                            $pdfDescargado = true;
                        } catch (\Throwable $e) {
                            $erroresDescarga[] = 'PDF: ' . $e->getMessage();

                            Log::error(
                                'El comprobante fue emitido, pero falló el PDF',
                                [
                                    'sale_id' => $sale->id,
                                    'error' => $e->getMessage(),
                                    'url' => $nubefactResult['enlace_del_pdf'],
                                ]
                            );
                        }
                    }

                    if (!empty($nubefactResult['enlace_del_xml'])) {
                        try {
                            $this->descargarArchivoNubefactSeguro(
                                $nubefactResult['enlace_del_xml'],
                                $xmlPath,
                                'xml'
                            );

                            $xmlDescargado = true;
                        } catch (\Throwable $e) {
                            $erroresDescarga[] = 'XML: ' . $e->getMessage();

                            Log::error(
                                'El comprobante fue emitido, pero falló el XML',
                                [
                                    'sale_id' => $sale->id,
                                    'error' => $e->getMessage(),
                                    'url' => $nubefactResult['enlace_del_xml'],
                                ]
                            );
                        }
                    }

                    if (!empty($nubefactResult['enlace_del_cdr'])) {
                        try {
                            $this->descargarArchivoNubefactSeguro(
                                $nubefactResult['enlace_del_cdr'],
                                $cdrPath,
                                'zip'
                            );

                            $cdrDescargado = true;
                        } catch (\Throwable $e) {
                            $erroresDescarga[] = 'CDR: ' . $e->getMessage();

                            Log::error(
                                'El comprobante fue emitido, pero falló el CDR',
                                [
                                    'sale_id' => $sale->id,
                                    'error' => $e->getMessage(),
                                    'url' => $nubefactResult['enlace_del_cdr'],
                                ]
                            );
                        }
                    }

                    /*
                     * Solo guardamos las rutas cuando la descarga y validación
                     * fueron correctas.
                     *
                     * Ya no usamos únicamente file_exists(), porque un HTML
                     * también podría existir con extensión .pdf.
                     */
                    $datosArchivos = [];

                    if ($pdfDescargado) {
                        $datosArchivos['pdf_path'] = $pdfFilename;
                    }

                    if ($xmlDescargado) {
                        $datosArchivos['xml_path'] = $xmlFilename;
                    }

                    if ($cdrDescargado) {
                        $datosArchivos['cdr_path'] = $cdrFilename;
                    }

                    if (!empty($datosArchivos)) {
                        $sale->update($datosArchivos);
                    }

                    /*
                     * No cambiamos sunat_status a Error, porque el comprobante
                     * sí fue emitido.
                     *
                     * El detalle técnico queda en laravel.log.
                     */
                    if (!empty($erroresDescarga)) {
                        Log::warning(
                            'Comprobante emitido con archivos pendientes',
                            [
                                'sale_id' => $sale->id,
                                'errores' => $erroresDescarga,
                            ]
                        );
                    }

                    /*
                     * Recargamos el modelo porque acabamos de actualizar
                     * pdf_path, xml_path y cdr_path.
                     */
                    $sale->refresh();
                }
            }

            $urlPrint  = route('puntoVenta.print', $sale->id);
            $printType = 'ticket';

            if ($pagosParcialesVenta === 'n' && in_array($sale->type_document, ['01', '03'])) {
                if (!empty($sale->pdf_path)) {
                    $localPath = public_path(
                        'comprobantes/pdfs/' . $sale->pdf_path
                    );

                    if (file_exists($localPath)) {
                        $urlPrint = asset(
                            'comprobantes/pdfs/' . $sale->pdf_path
                        );

                        $printType = 'sunat_pdf';
                    }
                }

                /*
                 * Si el archivo local no se pudo descargar,
                 * utilizamos directamente el enlace de Nubefact.
                 */
                if (
                    $printType !== 'sunat_pdf' &&
                    !empty($nubefactResult['enlace_del_pdf'])
                ) {
                    $urlPrint = $nubefactResult['enlace_del_pdf'];
                    $printType = 'sunat_pdf';
                }
            }

            return response()->json([
                'message'    => 'Venta creada con éxito desde cotización' . ($nubefactResult ? ' y comprobante generado.' : ' (sin comprobante).'),
                'sale_id'    => $sale->id,
                'nubefact'   => $nubefactResult,
                'url_print'  => $urlPrint,
                'print_type' => $printType,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Sincroniza el resumen por inventory_level desde los stock_lots.
     */
    protected function syncInventoryLevelFromLots(int $stockItemId, $warehouseId, $locationId): void
    {
        $qtyOnHand = (float) StockLot::where('stock_item_id', $stockItemId)
            ->where('warehouse_id', $warehouseId)
            ->where('location_id', $locationId)
            ->sum('qty_on_hand');

        $qtyReserved = (float) StockLot::where('stock_item_id', $stockItemId)
            ->where('warehouse_id', $warehouseId)
            ->where('location_id', $locationId)
            ->sum('qty_reserved');

        $inventoryLevel = InventoryLevel::lockForUpdate()->firstOrCreate(
            [
                'stock_item_id' => $stockItemId,
                'warehouse_id'  => $warehouseId,
                'location_id'   => $locationId,
            ],
            [
                'qty_on_hand'   => 0,
                'qty_reserved'  => 0,
                'min_alert'     => 0,
                'max_alert'     => 0,
                'average_cost'  => 0,
                'last_cost'     => 0,
            ]
        );

        $inventoryLevel->qty_on_hand  = $qtyOnHand;
        $inventoryLevel->qty_reserved = $qtyReserved;
        $inventoryLevel->save();
    }

    public function generateRandomString($length = 25) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function manageNotifications(Material $material)
    {
        $dataGeneralTypeNotificationPopUp = DataGeneral::where('name', 'send_notification_store_pop_up')->first();
        $dataGeneralTypeNotificationCampana = DataGeneral::where('name', 'send_notification_store_campana')->first();
        $dataGeneralTypeNotificationTelegram = DataGeneral::where('name', 'send_notification_store_email')->first();
        $dataGeneralTypeNotificationEmail = DataGeneral::where('name', 'send_notification_store_telegram')->first();

        // Texto base
        $content = 'El producto '.$material->full_name.' está por agotarse.';

        $nameMaterial = $material->full_name;

        // Obtener usuarios con roles específicos (excepto el actual)
        $users = User::role(['admin', 'principal', 'logistic'])->where('id', '!=', Auth::id())->get();

        if ($dataGeneralTypeNotificationCampana && $dataGeneralTypeNotificationCampana->valueText === 's')
        {
            $notification = Notification::create([
                'content' => $content,
                'reason_for_creation' => 'check_stock',
                'user_id' => Auth::id(),
                'url_go' => route('material.index.store')
            ]);

            foreach ($users as $user) {
                foreach ($user->roles as $role) {
                    NotificationUser::create([
                        'notification_id' => $notification->id,
                        'role_id' => $role->id,
                        'user_id' => $user->id,
                        'read' => false,
                        'date_read' => null,
                        'date_delete' => null
                    ]);
                }
            }
        }

        if ($dataGeneralTypeNotificationPopUp && $dataGeneralTypeNotificationPopUp->valueText === 's')
        {
            $notification = Notification::create([
                'content' => $content,
                'reason_for_creation' => 'check_stock_pop_up',
                'user_id' => Auth::id(),
                'url_go' => route('material.index.store')
            ]);

            foreach ($users as $user) {
                foreach ($user->roles as $role) {
                    NotificationUser::create([
                        'notification_id' => $notification->id,
                        'role_id' => $role->id,
                        'user_id' => $user->id,
                        'read' => false,
                        'date_read' => null,
                        'date_delete' => null
                    ]);
                }
            }
        }

        if ($dataGeneralTypeNotificationEmail && $dataGeneralTypeNotificationEmail->valueText === 's')
        {
            foreach ($users as $user) {
                Mail::to($user->email)->queue(new StockLowNotificationMail($nameMaterial));
            }
        }

        // Si deseas dejar el código preparado para Telegram:
        if ($dataGeneralTypeNotificationTelegram && $dataGeneralTypeNotificationTelegram->valueText === 's')
        {
            $telegram = new TelegramController();

            // Enviar al canal de procesos
            $telegram->sendNotification('📦 El producto XYZ está por agotarse.', 'process');
        }


    }

    public function raiseQuote(Request $request, $quote_id)
    {
        $begin = microtime(true);
        $quote = Quote::find($quote_id);
        // Leer el código enviado desde el popup
        $code = trim($request->input('code_customer', ''));

        if ($code === '') {
            return response()->json(['message' => 'El código de cliente es obligatorio.'], 422);
        }

        DB::beginTransaction();
        try {
            if ( !isset( $quote->order_execution ) )
            {
                $all_quotes = Quote::whereNotNull('order_execution')->get();
                $quantity = count($all_quotes) + 1;
                $length = 5;
                $codeOrderExecution = 'OE-'.str_pad($quantity,$length,"0", STR_PAD_LEFT);
                $quote->order_execution = $codeOrderExecution;
                $quote->save();
            }

            $quote->code_customer = $code;
            $quote->raise_status = true;
            $quote->save();

            // TODO: Guardar el pdf interna en el sistema
            $quote = Quote::where('id', $quote->id)
                ->with('customer')
                ->with('deadline')
                ->with(['equipments' => function ($query) {
                    $query->with(['materials', 'consumables', 'electrics', 'workforces', 'turnstiles']);
                }])->first();

            $images = ImagesQuote::where('quote_id', $quote->id)
                ->where('type', 'img')
                ->orderBy('order', 'ASC')->get();

            $view = view('exports.quoteInternal', compact('quote', 'images'));

            $pdf = PDF::loadHTML($view);

            $description = str_replace(array('"', "'", "/"),'',$quote->description_quote);

            $name = $quote->code . ' '. ltrim(rtrim($description)) . '.pdf';

            $image_path = public_path().'/pdfs/quotes/'.$name;
            if (file_exists($image_path)) {
                unlink($image_path);
            }

            $output = $pdf->output();
            file_put_contents(public_path().'/pdfs/quotes/'.$name, $output);

            $pdfPrincipal = public_path().'/pdfs/quotes/'.$name;

            $oMerger = PDFMerger::init();

            $oMerger->addPDF($pdfPrincipal, 'all');

            $pdfs = ImagesQuote::where('quote_id', $quote->id)
                ->where('type', 'pdf')->get();

            foreach ( $pdfs as $pdf )
            {
                $namePdf = public_path().'/images/planos/'.$pdf->image;
                $oMerger->addPDF($namePdf, 'all');
            }

            $oMerger->merge();
            $oMerger->setFileName($name);
            // Guarda el archivo fusionado en la misma carpeta
            $output_path = public_path('pdfs/quotes/' . $name);
            $oMerger->save($output_path);

            // TODO: Guardar los resumenes
            $resumen = ResumenQuote::create([
                'quote_id' => $quote->id,
                'code' => $quote->code,
                'description_quote' => $quote->description_quote,
                'date_quote' => $quote->date_quote,
                'customer_id' => ($quote->customer_id == null) ? null : $quote->customer_id,
                'customer' => ($quote->customer_id == null) ? "" : $quote->customer->business_name,
                'contact_id' => ($quote->contact_id == null) ? null : $quote->contact_id,
                'contact' => ($quote->contact_id == null) ? "" : $quote->contact->name,
                'total_sin_igv' => round(($quote->total_equipments)/1.18, 2),
                'total_con_igv' => round($quote->total_equipments, 2),
                'total_utilidad_sin_igv' => round(($quote->total_quote)/1.18, 2),
                'total_utilidad_con_igv' => round($quote->total_quote, 2),
                'path_pdf' => $name
            ]);

            foreach ( $quote->equipments as $equipment )
            {
                $resumenEquipment = ResumenEquipment::create([
                    'resumen_quote_id' => $resumen->id,
                    'equipment_id' => $equipment->id,
                    'description' => $equipment->description,
                    'total_materials' => $equipment->total_materials,
                    'total_consumables' => $equipment->total_consumables,
                    'total_electrics' => $equipment->total_electrics,
                    'total_workforces' => $equipment->total_workforces,
                    'total_turnstiles' => $equipment->total_turnstiles,
                    'total_workdays' => $equipment->total_workdays,
                    'quantity' => $equipment->quantity,
                    'total' => round($equipment->subtotal_percentage/1.18, 2),
                    'utility' => $equipment->utility,
                    'letter' => $equipment->letter,
                    'rent' => $equipment->rent
                ]);
            }

            // TODO: Actualizar los stocks
            $equipment_materials = $quote->equipments;
            foreach ( $equipment_materials as $equipment )
            {
                $quote_materials = $equipment->consumables;
                foreach ( $quote_materials as $consumable )
                {
                    $mat = Material::find($consumable->material_id);
                    $quantity = $consumable->quantity;
                    $mat->stock_current = $mat->stock_current - $quantity;
                    $mat->save();
                }
            }


            // Crear notificacion
            $notification = Notification::create([
                'content' => $quote->code.' elevada por '.Auth::user()->name,
                'reason_for_creation' => 'raise_quote',
                'user_id' => Auth::user()->id,
                'url_go' => route('quote.raise', $quote->id)
            ]);

            // Roles adecuados para recibir esta notificación admin, logistica
            $users = User::role(['admin', 'principal' , 'logistic' , 'finance'])->get();
            foreach ( $users as $user )
            {
                if ( $user->id != Auth::user()->id )
                {
                    foreach ( $user->roles as $role )
                    {
                        NotificationUser::create([
                            'notification_id' => $notification->id,
                            'role_id' => $role->id,
                            'user_id' => $user->id,
                            'read' => false,
                            'date_read' => null,
                            'date_delete' => null
                        ]);
                    }
                }
            }

            $end = microtime(true) - $begin;

            Audit::create([
                'user_id' => Auth::user()->id,
                'action' => 'Elevar cotizacion',
                'time' => $end
            ]);

            DB::commit();
        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Cotización elevada.'], 200);
    }

    public function printQuoteToCustomer($id)
    {
        // Eliminamos elos archivos
        $files = glob(public_path().'/pdfs/*');
        foreach($files as $file){
            if(is_file($file))
                unlink($file);
        }

        $quote = Quote::where('id', $id)
            ->with('customer')
            ->with('deadline')
            ->with('users')
            ->with(['equipments' => function ($query) {
                $query->with([
                    'materials',
                    'consumables.material',
                    'consumables.stockItem',
                    'consumables.quoteStockLots',
                    'workforces',
                    'turnstiles'
                ]);
            }])->first();

        $quoteItemIdsByConsumable = [];
        $allItemIds = [];

        foreach ($quote->equipments as $equipment) {
            foreach ($equipment->consumables as $consumable) {
                $itemIds = [];

                foreach ($consumable->quoteStockLots as $quoteStockLot) {
                    $rawItemIds = $quoteStockLot->item_ids ?? [];

                    if (is_string($rawItemIds)) {
                        $decodedItemIds = json_decode($rawItemIds, true);
                        $rawItemIds = is_array($decodedItemIds) ? $decodedItemIds : [];
                    }

                    if (!is_array($rawItemIds)) {
                        $rawItemIds = [];
                    }

                    foreach ($rawItemIds as $itemId) {
                        $itemId = (int) $itemId;

                        if ($itemId > 0) {
                            $itemIds[] = $itemId;
                            $allItemIds[] = $itemId;
                        }
                    }
                }

                $quoteItemIdsByConsumable[$consumable->id] = array_values(array_unique($itemIds));
            }
        }

        $itemsById = Item::whereIn('id', array_values(array_unique($allItemIds)))
            ->get(['id', 'code'])
            ->keyBy('id');

        $quoteItemCodesByConsumable = [];

        foreach ($quoteItemIdsByConsumable as $consumableId => $itemIds) {
            $codes = [];

            foreach ($itemIds as $itemId) {
                $item = $itemsById->get($itemId);

                if ($item) {
                    $codes[] = $item->code ?: ('Ítem #' . $item->id);
                }
            }

            $quoteItemCodesByConsumable[$consumableId] = $codes;
        }

        /*dump($quoteItemCodesByConsumable);
        dd();*/


        $images = ImagesQuote::where('quote_id', $quote->id)
            ->where('type', 'img')
            ->orderBy('order', 'ASC')->get();

        $dataIgv = PorcentageQuote::where('name', 'igv')->first();
        $igv = $dataIgv->value;

        $monedaTexto = $quote->currency_invoice === 'USD'
            ? 'DÓLARES'
            : 'SOLES';

        $total_workforce  = 0;
        foreach($quote->equipments as $equipment)
        {
            foreach($equipment->workforces as $workforce)
            {
                if ( $workforce->billable == false )
                {
                    $total_workforce = $total_workforce + $workforce->total;
                }

            }
        }

        $montoEnLetras = numeroALetras($quote->total_importe+$total_workforce, $monedaTexto);

        $dataNombreEmpresa = DataGeneral::where('name', 'empresa')->first();
        $nombreEmpresa = $dataNombreEmpresa->valueText;
        $dataDireccionEmpresa = DataGeneral::where('name', 'address')->first();
        $direccionEmpresa = $dataDireccionEmpresa->valueText;
        $dataTelefonoEmpresa = DataGeneral::where('name', 'telefono')->first();
        $telefonoEmpresa = $dataTelefonoEmpresa->valueText;
        $dataEmailEmpresa = DataGeneral::where('name', 'email')->first();
        $emailEmpresa = $dataEmailEmpresa->valueText;
        $dataWebEmpresa = DataGeneral::where('name', 'web')->first();
        $webEmpresa = $dataWebEmpresa->valueText;
        $dataRucEmpresa = DataGeneral::where('name', 'ruc')->first();
        $rucEmpresa = $dataRucEmpresa->valueText;
        $dataLogotipoEmpresa = DataGeneral::where('name', 'logotipo')->first();
        $logotipoEmpresa = $dataLogotipoEmpresa->valueText;

        $dataVersiculoEmpresa = DataGeneral::where('name', 'versiculo')->first();
        $versiculoEmpresa = $dataVersiculoEmpresa->valueText;
        $dataCitaBiblicaEmpresa = DataGeneral::where('name', 'cita_biblica')->first();
        $citaBiblicaEmpresa = $dataCitaBiblicaEmpresa->valueText;

        // Cuenta1
        $dataTitleCuenta1Empresa = DataGeneral::where('name', 'title_cuenta_1')->first();
        $titleCuenta1Empresa = $dataTitleCuenta1Empresa->valueText;
        $dataNroCuenta1Empresa = DataGeneral::where('name', 'nro_cuenta_1')->first();
        $nroCuenta1Empresa = $dataNroCuenta1Empresa->valueText;
        $dataCciCuenta1Empresa = DataGeneral::where('name', 'cci_cuenta_1')->first();
        $cciCuenta1Empresa = $dataCciCuenta1Empresa->valueText;
        $dataImgCuenta1Empresa = DataGeneral::where('name', 'img_cuenta_1')->first();
        $imgCuenta1Empresa = $dataImgCuenta1Empresa->valueText;
        $dataOwnerCuenta1Empresa = DataGeneral::where('name', 'owner_cuenta_1')->first();
        $ownerCuenta1Empresa = $dataOwnerCuenta1Empresa->valueText;

        // Cuenta2
        $dataTitleCuenta2Empresa = DataGeneral::where('name', 'title_cuenta_2')->first();
        $titleCuenta2Empresa = $dataTitleCuenta2Empresa->valueText;
        $dataNroCuenta2Empresa = DataGeneral::where('name', 'nro_cuenta_2')->first();
        $nroCuenta2Empresa = $dataNroCuenta2Empresa->valueText;
        $dataCciCuenta2Empresa = DataGeneral::where('name', 'cci_cuenta_2')->first();
        $cciCuenta2Empresa = $dataCciCuenta2Empresa->valueText;
        $dataImgCuenta2Empresa = DataGeneral::where('name', 'img_cuenta_2')->first();
        $imgCuenta2Empresa = $dataImgCuenta2Empresa->valueText;
        $dataOwnerCuenta2Empresa = DataGeneral::where('name', 'owner_cuenta_2')->first();
        $ownerCuenta2Empresa = $dataOwnerCuenta2Empresa->valueText;

        $tieneCuentas = false;
        if ( $nroCuenta2Empresa != "" || $nroCuenta1Empresa != "" )
        {
            $tieneCuentas = true;
        }

        $view = view('exports.quoteSaleCustomerV2', compact(
            'logotipoEmpresa',
            'versiculoEmpresa',
            'citaBiblicaEmpresa',
            'titleCuenta1Empresa',
            'nroCuenta1Empresa',
            'cciCuenta1Empresa',
            'imgCuenta1Empresa',
            'ownerCuenta1Empresa',
            'titleCuenta2Empresa',
            'nroCuenta2Empresa',
            'cciCuenta2Empresa',
            'imgCuenta2Empresa',
            'ownerCuenta2Empresa',
            'tieneCuentas',
            'rucEmpresa','webEmpresa','emailEmpresa','telefonoEmpresa','direccionEmpresa','nombreEmpresa', 'quote', 'images', 'igv', 'montoEnLetras', 'quoteItemCodesByConsumable'));

        $pdf = PDF::loadHTML($view);

        $description = str_replace(array('"', "'", "/"),'',$quote->description_quote);

        $name = $quote->code . ' '. ltrim(rtrim($description)) . '.pdf';

        $image_path = public_path().'/pdfs/'.$name;
        //$image_path = 'C:/wamp64/www/construction/public/pdfs/'.$name;
        if (file_exists($image_path)) {
            unlink($image_path);
        }

        // = $pdf->output();

        //file_put_contents(public_path().'/pdfs/'.$name, $output);
        //file_put_contents('C:/wamp64/www/construction/public/pdfs/'.$name, $output);
        $pdfPrincipal = public_path().'/pdfs/'.$name;
        //$pdfPrincipal = 'C:/wamp64/www/construction/public/pdfs/'.$name;
        //$oMerger = PDFMerger::init();

        //$oMerger->addPDF($pdfPrincipal, 'all');

        /*$pdfs = ImagesQuote::where('quote_id', $quote->id)
            ->where('type', 'pdf')->get();

        foreach ( $pdfs as $pdf )
        {
            $namePdf = public_path().'/images/planos/'.$pdf->image;
            //$namePdf ='C:/wamp64/www/construction/public/images/planos/'.$pdf->image;
            $oMerger->addPDF($namePdf, 'all');
        }*/

        //$oMerger->merge();
        //$oMerger->setFileName($name);
        //$oMerger->stream();

        return $pdf->stream($name);
    }

    public function show($id)
    {
        $begin = microtime(true);
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();
        $unitMeasures = UnitMeasure::all();
        $customers = Customer::all();
        $defaultConsumable = '(*)';
        $defaultElectric = '(e)';
        $consumables = Material::with('unitMeasure')->where('category_id', 2)->where('description','LIKE',"%".$defaultConsumable."%")->orderBy('full_name', 'asc')->get();
        $electrics = Material::with('unitMeasure')->where('category_id', 2)->whereElectric('description',$defaultElectric)->orderBy('full_name', 'asc')->get();
        $workforces = Workforce::with('unitMeasure')->get();
        $paymentDeadlines = PaymentDeadline::where('type', 'quotes')->get();
        $utility = PorcentageQuote::where('name', 'utility')->first();
        $rent = PorcentageQuote::where('name', 'rent')->first();
        $letter = PorcentageQuote::where('name', 'letter')->first();

        $quote = Quote::where('id', $id)
            ->with('customer')
            ->with('deadline')
            ->with(['equipments' => function ($query) {
                $query->with([
                    'materials',
                    'consumables.material',
                    'consumables.stockItem',
                    'consumables.quoteStockLots',
                    'electrics',
                    'workforces',
                    'turnstiles',
                    'workdays'
                ]);
            }])->first();

        $quoteItemIdsByConsumable = [];
        $allItemIds = [];

        foreach ($quote->equipments as $equipment) {
            foreach ($equipment->consumables as $consumable) {
                $itemIds = [];

                foreach ($consumable->quoteStockLots as $quoteStockLot) {
                    $rawItemIds = $quoteStockLot->item_ids ?? [];

                    if (is_string($rawItemIds)) {
                        $decodedItemIds = json_decode($rawItemIds, true);
                        $rawItemIds = is_array($decodedItemIds) ? $decodedItemIds : [];
                    }

                    if (!is_array($rawItemIds)) {
                        $rawItemIds = [];
                    }

                    foreach ($rawItemIds as $itemId) {
                        $itemId = (int) $itemId;

                        if ($itemId > 0) {
                            $itemIds[] = $itemId;
                            $allItemIds[] = $itemId;
                        }
                    }
                }

                $quoteItemIdsByConsumable[$consumable->id] = array_values(array_unique($itemIds));
            }
        }

        $itemsById = \App\Item::whereIn('id', array_values(array_unique($allItemIds)))
            ->get(['id', 'code'])
            ->keyBy('id');

        $quoteItemCodesByConsumable = [];

        foreach ($quoteItemIdsByConsumable as $consumableId => $itemIds) {
            $codes = [];

            foreach ($itemIds as $itemId) {
                $item = $itemsById->get($itemId);

                if ($item && !empty($item->code)) {
                    $codes[] = $item->code;
                }
            }

            $quoteItemCodesByConsumable[$consumableId] = $codes;
        }

        $images = [];

        $dataCurrency = DataGeneral::where('name', 'type_current')->first();
        $currency = $dataCurrency->valueText;

        $dataIgv = PorcentageQuote::where('name', 'igv')->first();
        $igv = $dataIgv->value;

        $end = microtime(true) - $begin;

        Audit::create([
            'user_id' => Auth::user()->id,
            'action' => 'Ver cotizacion venta VISTA',
            'time' => $end
        ]);

        return view('quoteSale.show', compact(
            'quote',
            'unitMeasures',
            'customers',
            'consumables',
            'electrics',
            'workforces',
            'permissions',
            'paymentDeadlines',
            'utility',
            'rent',
            'letter',
            'images',
            'currency',
            'igv',
            'quoteItemCodesByConsumable'
        ));

    }
}
