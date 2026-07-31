<?php

namespace App\Http\Controllers;

use App\CashBox;
use App\CashBoxSubtype;
use App\CashMovement;
use App\CashRegister;
use App\Customer;
use App\DataGeneral;
use App\Http\Controllers\Traits\FreeSaleFinancialReversalTrait;
use App\Http\Controllers\Traits\NubefactTrait;
use App\Http\Requests\StoreFreeSaleRequest;
use App\Sale;
use App\SaleDetail;
use App\Worker;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\SalePartialPayment;

class FreeSaleController extends Controller
{

    use NubefactTrait;
    use FreeSaleFinancialReversalTrait;

    public function __construct()
    {
        $this->middleware('auth');

        /*$this->middleware('permission:listFreeSale_puntoVenta')
            ->only(['index']);*/

        $this->middleware('permission:createFreeSale_puntoVenta')
            ->only(['create', 'store', 'generateInvoice']);

        $this->middleware(
            'permission:anularFreeSale_puntoVenta'
        )->only([
            'annul',
            'consultAnnulment',
        ]);
    }

    /**
     * Lista de ventas libres.
     */
    public function index()
    {
        return view('puntoVenta.freeSale.index');
    }

    /**
     * Formulario para crear una venta libre.
     */
    public function create()
    {
        $cashBoxes = CashBox::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        $subtypes = CashBoxSubtype::query()
            ->where('is_active', true)
            ->whereNull('cash_box_id')
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        $subtypesConfig = $subtypes->map(function ($subtype) {
            return [
                'value' => (string) $subtype->id,
                'text' => $subtype->name,
                'isDeferred' => $subtype->is_deferred ? '1' : '0',
            ];
        })->values();

        $customers = Customer::query()
            ->select([
                'id',
                'business_name',
                'RUC',
                'address',
                'location',
            ])
            ->orderBy('business_name')
            ->get();

        $dataPagosParciales = DataGeneral::query()
            ->where('name', 'pagos_parciales')
            ->first();

        $pagosParciales = $dataPagosParciales
            ? strtolower(trim($dataPagosParciales->valueText))
            : 'n';

        return view('puntoVenta.freeSale.create', compact(
            'cashBoxes',
            'subtypes',
            'subtypesConfig',
            'customers',
            'pagosParciales'
        ));
    }

    /**
     * Guardar una venta libre.
     */
    public function store(StoreFreeSaleRequest $request)
    {
        /*
         * El backend decide las reglas reales.
         *
         * No confiamos en:
         * subtotal
         * taxable_amount
         * tax_amount
         * total_amount
         * change_amount
         * discount_percentage
        */
        $taxPercentage = '18.0000000000';
        $discountAmount = '0.0000000000';

        $partialPayments =
            $request->input('pagos_parciales_venta') === 's';

        try {
            $result = DB::transaction(function () use (
                $request,
                $taxPercentage,
                $discountAmount,
                $partialPayments
            ) {
                /*
                 * 1. Obtener trabajador del usuario actual.
                 */
                $worker = Worker::query()
                    ->where('user_id', Auth::id())
                    ->firstOrFail();

                /*
                 * 2. Resolver los datos históricos del cliente.
                 */
                $customerData = $this->resolveFreeSaleCustomer(
                    $request
                );

                /*
                 * 3. Recalcular todos los conceptos en backend.
                 */
                $calculation = $this->calculateFreeSaleItems(
                    $request->input('items', []),
                    $taxPercentage
                );

                /*
                 * Primera versión sin descuento.
                 */
                $totalAmount = $calculation['total_with_tax'];
                $taxableAmount = $calculation['taxable_amount'];
                $taxAmount = $calculation['tax_amount'];

                /*
                 * 4. Resolver y validar el pago.
                 */
                $paymentData = $this->resolveFreeSalePayment(
                    $request,
                    $totalAmount,
                    $partialPayments
                );

                /*
                 * 5. Crear Sale.
                 */
                $sale = Sale::create([
                    'date_sale' => Carbon::parse(
                        $request->input('date_sale')
                        . ' '
                        . Carbon::now()->format('H:i:s')
                    ),

                    'serie' => $this->generateFreeSaleSerie(
                        $request->input('serie')
                    ),

                    'worker_id' => $worker->id,
                    'caja' => $worker->id,

                    'currency' => $request->input(
                        'currency',
                        'PEN'
                    ),

                    /*
                     * Primera versión:
                     * todos los conceptos son gravados con 18%.
                     */
                    'op_exonerada' => 0,
                    'op_inafecta' => 0,
                    'op_gravada' => $taxableAmount,
                    'igv' => $taxAmount,

                    'total_descuentos' => $discountAmount,
                    'importe_total' => $totalAmount,

                    'vuelto' => $paymentData['change_amount'],

                    'tipo_pago_id' => null,

                    'state_annulled' => 0,

                    'dispatch_status' => $partialPayments
                        ? 'pendiente'
                        : 'despachado',

                    'pagos_parciales_venta' => $partialPayments
                        ? 's'
                        : 'n',

                    /*
                     * Identificador principal.
                     */
                    'free_sale' => true,
                    'observation' => $request->input('observations'),
                    /*
                     * Relación y snapshot del cliente.
                     */
                    'customer_id' =>
                        $customerData['customer_id'],

                    'nombre_cliente' =>
                        $customerData['name'],

                    'tipo_documento_cliente' =>
                        $customerData['document_type'],

                    'numero_documento_cliente' =>
                        $customerData['document_number'],

                    'direccion_cliente' =>
                        $customerData['address'],

                    'email_cliente' =>
                        $customerData['email'],

                    /*
                     * Todavía no se factura.
                     */
                    'type_document' => null,
                    'serie_sunat' => null,
                    'numero' => null,
                    'fecha_emision' => null,

                    'sunat_ticket' => null,
                    'sunat_status' => null,
                    'sunat_message' => null,

                    'xml_path' => null,
                    'cdr_path' => null,
                    'pdf_path' => null,
                ]);

                /*
                 * Si tu tabla sales ya tiene la columna observation,
                 * puedes agregarla directamente al Sale::create:
                 *
                 * 'observation' => $request->input('observations'),
                 */

                /*
                 * 6. Crear los SaleDetail.
                 */
                foreach ($calculation['items'] as $item) {
                    SaleDetail::create([
                        'sale_id' => $sale->id,

                        /*
                         * Venta libre: no existe producto de inventario.
                         */
                        'material_id' => null,
                        'stock_item_id' => null,
                        'material_presentation_id' => null,

                        'description' => $item['description'],

                        /*
                         * Precio unitario sin IGV.
                         */
                        'valor_unitario' =>
                            $item['unit_value_without_tax'],

                        /*
                         * Precio unitario final con IGV.
                         */
                        'price' => $item['unit_price_with_tax'],

                        'quantity' => $item['quantity'],

                        'packs' => null,
                        'units_per_pack' => null,

                        'percentage_tax' => $taxPercentage,

                        /*
                         * Cantidad × precio con IGV.
                         */
                        'total' => $item['total_with_tax'],

                        /*
                         * Primera versión sin descuento.
                         */
                        'discount' => 0,

                        'unit_cost' => null,
                        'total_cost' => null,
                        'item_snapshot' => null,

                        /*
                         * Inclúyelo si ya agregaste tax_amount
                         * a sale_details y a su $fillable.
                         */
                        'tax_amount' => $item['tax_amount'],
                    ]);
                }

                /*
                 * 7. Crear movimientos de caja.
                 *
                 * En pagos parciales no se crea un pago inicial.
                 */
                if (!$partialPayments) {
                    $this->createFreeSaleCashMovements(
                        $sale,
                        $paymentData
                    );
                }

                return [
                    'sale' => $sale,
                    'payment' => $paymentData,
                ];
            }, 3);

            /** @var Sale $sale */
            $sale = $result['sale'];

            /*
             * No generamos Nubefact en esta etapa.
             */
            return response()->json([
                'message' =>
                    'Venta libre registrada correctamente.',

                'sale_id' => $sale->id,

                'url_print' => route(
                    'puntoVenta.print',
                    $sale->id
                ),

                'print_type' => 'ticket',
            ], 201);

        } catch (ValidationException $e) {
            throw $e;

        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' =>
                    'No se pudo registrar la venta libre.',

                /*
                 * Puedes retirar "error" en producción.
                 */
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    private function resolveFreeSaleCustomer(StoreFreeSaleRequest $request) {
        $clientMode = $request->input('client_mode');

        if ($clientMode === 'registered') {
            $customer = Customer::query()
                ->findOrFail(
                    $request->input('customer_id')
                );

            $ruc = trim((string) $customer->RUC);

            if (
                $ruc !== '' &&
                !preg_match('/^\d{11}$/', $ruc)
            ) {
                throw ValidationException::withMessages([
                    'customer_id' =>
                        'El cliente registrado tiene un RUC inválido.',
                ]);
            }

            $addressParts = array_filter([
                trim((string) $customer->address),
                trim((string) $customer->location),
            ]);

            return [
                'customer_id' => $customer->id,
                'name' => trim(
                    (string) $customer->business_name
                ),

                'document_type' =>
                    $ruc !== '' ? '6' : null,

                'document_number' =>
                    $ruc !== '' ? $ruc : null,

                'address' =>
                    implode(' - ', $addressParts),

                /*
                 * Customer no tiene email en los campos mostrados.
                 * Permitimos escribirlo en la venta.
                 */
                'email' => $request->input(
                    'email_cliente'
                ),
            ];
        }

        /*
         * Cliente no registrado.
         */
        $documentType = $request->input(
            'tipo_documento_cliente'
        );

        $documentNumber = trim(
            (string) $request->input(
                'numero_documento_cliente'
            )
        );

        if (
            $documentType === '1' &&
            !preg_match('/^\d{8}$/', $documentNumber)
        ) {
            throw ValidationException::withMessages([
                'numero_documento_cliente' =>
                    'El DNI debe contener exactamente 8 dígitos.',
            ]);
        }

        if (
            $documentType === '6' &&
            !preg_match('/^\d{11}$/', $documentNumber)
        ) {
            throw ValidationException::withMessages([
                'numero_documento_cliente' =>
                    'El RUC debe contener exactamente 11 dígitos.',
            ]);
        }

        return [
            'customer_id' => null,

            'name' => trim(
                (string) $request->input(
                    'nombre_cliente'
                )
            ),

            'document_type' =>
                $documentType ?: null,

            'document_number' =>
                $documentNumber !== ''
                    ? $documentNumber
                    : null,

            'address' => trim(
                (string) $request->input(
                    'direccion_cliente'
                )
            ),

            'email' => $request->input(
                'email_cliente'
            ),
        ];
    }

    private function calculateFreeSaleItems(array $items,$taxPercentage) {
        $scale = 10;

        $taxRate = bcdiv(
            $taxPercentage,
            '100',
            $scale
        );

        $taxFactor = bcadd(
            '1',
            $taxRate,
            $scale
        );

        $totalWithTax = '0.0000000000';
        $totalTaxable = '0.0000000000';
        $totalTax = '0.0000000000';

        $calculatedItems = [];

        foreach ($items as $index => $item) {
            $description = trim(
                (string) ($item['description'] ?? '')
            );

            if ($description === '') {
                throw ValidationException::withMessages([
                    "items.{$index}.description" =>
                        'Ingrese la descripción del concepto.',
                ]);
            }

            $quantity = $this->normalizeFreeSaleDecimal(
                $item['quantity'] ?? 0
            );

            $unitPriceWithTax =
                $this->normalizeFreeSaleDecimal(
                    $item['unit_price'] ?? 0
                );

            if (bccomp($quantity, '0', $scale) !== 1) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" =>
                        'La cantidad debe ser mayor que cero.',
                ]);
            }

            if (
                bccomp(
                    $unitPriceWithTax,
                    '0',
                    $scale
                ) === -1
            ) {
                throw ValidationException::withMessages([
                    "items.{$index}.unit_price" =>
                        'El precio no puede ser negativo.',
                ]);
            }

            /*
             * Precio unitario sin IGV.
             */
            $unitValueWithoutTax = bcdiv(
                $unitPriceWithTax,
                $taxFactor,
                $scale
            );

            /*
             * Total final de la línea.
             */
            $lineTotalWithTax = bcmul(
                $quantity,
                $unitPriceWithTax,
                $scale
            );

            /*
             * Base imponible de la línea.
             */
            $lineTaxableAmount = bcdiv(
                $lineTotalWithTax,
                $taxFactor,
                $scale
            );

            /*
             * IGV incluido en el total.
             */
            $lineTaxAmount = bcsub(
                $lineTotalWithTax,
                $lineTaxableAmount,
                $scale
            );

            $totalWithTax = bcadd(
                $totalWithTax,
                $lineTotalWithTax,
                $scale
            );

            $totalTaxable = bcadd(
                $totalTaxable,
                $lineTaxableAmount,
                $scale
            );

            $totalTax = bcadd(
                $totalTax,
                $lineTaxAmount,
                $scale
            );

            $calculatedItems[] = [
                'description' => $description,
                'quantity' => $quantity,

                'unit_price_with_tax' =>
                    $unitPriceWithTax,

                'unit_value_without_tax' =>
                    $unitValueWithoutTax,

                'taxable_amount' =>
                    $lineTaxableAmount,

                'tax_amount' =>
                    $lineTaxAmount,

                'total_with_tax' =>
                    $lineTotalWithTax,
            ];
        }

        if (count($calculatedItems) === 0) {
            throw ValidationException::withMessages([
                'items' =>
                    'Debe registrar por lo menos un concepto.',
            ]);
        }

        return [
            'items' => $calculatedItems,

            'taxable_amount' =>
                $this->roundFreeSaleDecimal(
                    $totalTaxable,
                    2
                ),

            'tax_amount' =>
                $this->roundFreeSaleDecimal(
                    $totalTax,
                    2
                ),

            'total_with_tax' =>
                $this->roundFreeSaleDecimal(
                    $totalWithTax,
                    2
                ),
        ];
    }

    private function resolveFreeSalePayment(StoreFreeSaleRequest $request,$totalAmount,$partialPayments) {
        if ($partialPayments) {
            return [
                'partial_payments' => true,

                'cash_register' => null,
                'cash_box' => null,
                'cash_box_subtype_id' => null,
                'regularize' => 1,

                'amount_received' => '0.00',
                'sale_movement_amount' => '0.00',
                'change_amount' => '0.00',

                'change_register' => null,
                'change_cash_box' => null,
                'change_cash_box_subtype_id' => null,
            ];
        }

        $cashBoxId = $request->input('cash_box_id');

        if (!$cashBoxId) {
            throw ValidationException::withMessages([
                'cash_box_id' =>
                    'Seleccione una caja.',
            ]);
        }

        /*
         * Bloqueamos la sesión porque modificaremos sus saldos.
         */
        $cashRegister = CashRegister::query()
            ->with('cashBox')
            ->where('cash_box_id', $cashBoxId)
            ->where('user_id', Auth::id())
            ->where('status', 1)
            ->lockForUpdate()
            ->latest('id')
            ->first();

        if (!$cashRegister) {
            throw ValidationException::withMessages([
                'cash_box_id' =>
                    'No existe una sesión abierta para la caja seleccionada.',
            ]);
        }

        $cashBox = $cashRegister->cashBox;

        if (
            !$cashBox ||
            !$cashBox->is_active
        ) {
            throw ValidationException::withMessages([
                'cash_box_id' =>
                    'La caja seleccionada no está disponible.',
            ]);
        }

        $subtypeId = null;
        $regularize = 1;

        if (
            $cashBox->type === 'bank' &&
            $cashBox->uses_subtypes
        ) {
            $subtypeId = $request->input(
                'cash_box_subtype_id'
            );

            if (!$subtypeId) {
                throw ValidationException::withMessages([
                    'cash_box_subtype_id' =>
                        'Seleccione un subtipo bancario.',
                ]);
            }

            $subtype = CashBoxSubtype::query()
                ->where('id', $subtypeId)
                ->where('is_active', true)
                ->first();

            if (!$subtype) {
                throw ValidationException::withMessages([
                    'cash_box_subtype_id' =>
                        'El subtipo bancario seleccionado no es válido.',
                ]);
            }

            $regularize = $subtype->is_deferred
                ? 0
                : 1;
        }

        /*
         * Para banco se registra exactamente el total.
         */
        $amountReceived = $totalAmount;
        $changeAmount = '0.00';
        $saleMovementAmount = $totalAmount;

        $changeRegister = null;
        $changeCashBox = null;
        $changeSubtypeId = null;

        /*
         * El monto recibido y el vuelto solo aplican
         * cuando el pago principal es efectivo.
         */
        if ($cashBox->type === 'cash') {
            $amountReceived =
                $this->normalizeFreeSaleDecimal(
                    $request->input(
                        'amount_received',
                        0
                    )
                );

            if (
                bccomp(
                    $amountReceived,
                    $totalAmount,
                    2
                ) === -1
            ) {
                throw ValidationException::withMessages([
                    'amount_received' =>
                        'El monto recibido debe ser igual o mayor al total de la venta.',
                ]);
            }

            $changeAmount = $this->roundFreeSaleDecimal(
                bcsub(
                    $amountReceived,
                    $totalAmount,
                    10
                ),
                2
            );

            /*
             * Se registra todo el dinero recibido como ingreso.
             * Después se registra el vuelto como egreso.
             */
            $saleMovementAmount = $amountReceived;

            if (
                bccomp(
                    $changeAmount,
                    '0',
                    2
                ) === 1
            ) {
                $changeCashBoxId = $request->input(
                    'change_cash_box_id'
                );

                if (!$changeCashBoxId) {
                    throw ValidationException::withMessages([
                        'change_cash_box_id' =>
                            'Seleccione la caja desde donde se entregará el vuelto.',
                    ]);
                }

                $changeRegister = CashRegister::query()
                    ->with('cashBox')
                    ->where(
                        'cash_box_id',
                        $changeCashBoxId
                    )
                    ->where('user_id', Auth::id())
                    ->where('status', 1)
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();

                if (!$changeRegister) {
                    throw ValidationException::withMessages([
                        'change_cash_box_id' =>
                            'No existe una sesión abierta para la caja del vuelto.',
                    ]);
                }

                $changeCashBox =
                    $changeRegister->cashBox;

                if (
                    !$changeCashBox ||
                    !$changeCashBox->is_active
                ) {
                    throw ValidationException::withMessages([
                        'change_cash_box_id' =>
                            'La caja del vuelto no está disponible.',
                    ]);
                }

                if (
                    $changeCashBox->type === 'bank' &&
                    $changeCashBox->uses_subtypes
                ) {
                    $changeSubtypeId = $request->input(
                        'change_cash_box_subtype_id'
                    );

                    if (!$changeSubtypeId) {
                        throw ValidationException::withMessages([
                            'change_cash_box_subtype_id' =>
                                'Seleccione el subtipo bancario del vuelto.',
                        ]);
                    }

                    $changeSubtype =
                        CashBoxSubtype::query()
                            ->where(
                                'id',
                                $changeSubtypeId
                            )
                            ->where('is_active', true)
                            ->first();

                    if (!$changeSubtype) {
                        throw ValidationException::withMessages([
                            'change_cash_box_subtype_id' =>
                                'El subtipo seleccionado para el vuelto no es válido.',
                        ]);
                    }
                }
            }
        }

        return [
            'partial_payments' => false,

            'cash_register' => $cashRegister,
            'cash_box' => $cashBox,

            'cash_box_subtype_id' => $subtypeId,
            'regularize' => $regularize,

            'amount_received' => $amountReceived,
            'sale_movement_amount' =>
                $saleMovementAmount,

            'change_amount' => $changeAmount,

            'change_register' => $changeRegister,
            'change_cash_box' => $changeCashBox,

            'change_cash_box_subtype_id' =>
                $changeSubtypeId,
        ];
    }

    private function createFreeSaleCashMovements(Sale $sale,array $paymentData) {
        /** @var CashRegister $cashRegister */
        $cashRegister =
            $paymentData['cash_register'];

        /** @var CashBox $cashBox */
        $cashBox =
            $paymentData['cash_box'];

        $saleMovementAmount =
            $paymentData['sale_movement_amount'];

        $regularize =
            $paymentData['regularize'];

        /*
         * Ingreso principal.
         */
        CashMovement::create([
            'cash_register_id' =>
                $cashRegister->id,

            'type' => 'sale',

            'amount' =>
                $saleMovementAmount,

            'description' =>
                'Venta libre registrada',

            'observation' =>
                $cashBox->type === 'bank'
                    ? 'Pago bancario de venta libre'
                    : 'Pago en efectivo de venta libre',

            'regularize' => $regularize,

            'cash_box_subtype_id' =>
                $paymentData[
                'cash_box_subtype_id'
                ],

            'sale_id' => $sale->id,
        ]);

        if ($regularize === 1) {
            $cashRegister->current_balance =
                bcadd(
                    (string) $cashRegister->current_balance,
                    $saleMovementAmount,
                    2
                );

            $cashRegister->total_sales =
                bcadd(
                    (string) $cashRegister->total_sales,
                    $saleMovementAmount,
                    2
                );

            $cashRegister->save();
        }

        $changeAmount =
            $paymentData['change_amount'];

        if (
            bccomp(
                $changeAmount,
                '0',
                2
            ) !== 1
        ) {
            return;
        }

        /** @var CashRegister $changeRegister */
        $changeRegister =
            $paymentData['change_register'];

        /*
         * Egreso por vuelto.
         */
        CashMovement::create([
            'cash_register_id' =>
                $changeRegister->id,

            'type' => 'expense',

            'amount' => $changeAmount,

            'description' =>
                'Vuelto entregado en venta libre',

            'observation' =>
                'Vuelto de la venta libre #'
                . $sale->id,

            'regularize' => 1,

            'cash_box_subtype_id' =>
                $paymentData[
                'change_cash_box_subtype_id'
                ],

            'sale_id' => $sale->id,
        ]);

        $changeRegister->current_balance =
            bcsub(
                (string) $changeRegister->current_balance,
                $changeAmount,
                2
            );

        $changeRegister->total_expenses =
            bcadd(
                (string) $changeRegister->total_expenses,
                $changeAmount,
                2
            );

        $changeRegister->save();
    }

    private function normalizeFreeSaleDecimal($value)
    {
        $value = trim((string) $value);

        /*
         * Soportar coma decimal enviada accidentalmente.
         */
        $value = str_replace(',', '.', $value);

        if (
            $value === '' ||
            !is_numeric($value)
        ) {
            return '0.0000000000';
        }

        return number_format(
            (float) $value,
            10,
            '.',
            ''
        );
    }

    private function roundFreeSaleDecimal($value,$precision = 2) {
        $increment = '0.'
            . str_repeat('0', $precision)
            . '5';

        if (bccomp($value, '0', 10) >= 0) {
            return bcadd(
                $value,
                $increment,
                $precision
            );
        }

        return bcsub(
            $value,
            $increment,
            $precision
        );
    }

    private function generateFreeSaleSerie($requestedSerie = null) {
        $requestedSerie = trim(
            (string) $requestedSerie
        );

        if ($requestedSerie !== '') {
            return $requestedSerie;
        }

        do {
            $serie = 'VL'
                . Carbon::now()->format('ymdHis')
                . random_int(10, 99);

            $exists = Sale::query()
                ->where('serie', $serie)
                ->exists();

        } while ($exists);

        return $serie;
    }

    /**
     * Anular una venta libre.
     */
    public function annul($id)
    {
        DB::beginTransaction();

        try {
            $sale = Sale::query()
                ->with([
                    'cashMovements.cashRegister.cashBox',
                    'partialPayments.cashMovement.cashRegister.cashBox',
                ])
                ->where('free_sale', true)
                ->lockForUpdate()
                ->find($id);

            if (!$sale) {
                return response()->json([
                    'message' => 'La venta libre no fue encontrada.',
                ], 422);
            }

            if ((int) $sale->state_annulled === 1) {
                return response()->json([
                    'message' => 'La venta libre ya fue anulada previamente.',
                ], 422);
            }

            if (
            in_array(
                $sale->annulment_status,
                ['pending', 'waiting_sunat_process'],
                true
            )
            ) {
                return response()->json([
                    'message' =>
                        'Esta venta libre ya tiene una anulación pendiente. Consulte su estado antes de intentar nuevamente.',

                    'annulment_status' =>
                        $sale->annulment_status,

                    'pending_annulment' => true,
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | 1. VENTA LIBRE SIN COMPROBANTE ELECTRÓNICO
            |--------------------------------------------------------------------------
            */
            if ($sale->hasNoElectronicDocument()) {
                $this->setFreeSaleAnnulmentRequestData(
                    $sale,
                    'Anulación interna de venta libre sin comprobante electrónico',
                    'internal'
                );

                $this->reverseFreeSaleFinancially(
                    $sale,
                    'Anulación interna de venta libre sin comprobante electrónico'
                );

                $this->markFreeSaleAnnulmentAccepted(
                    $sale,
                    'internal'
                );

                DB::commit();

                return response()->json([
                    'message' =>
                        'La venta libre fue anulada correctamente.',

                    'annulment_status' => 'accepted',
                    'pending_annulment' => false,

                    'internal_reversal_status' =>
                        'reversed',
                ], 200);
            }

            /*
            |--------------------------------------------------------------------------
            | 2. COMPROBANTE CON ERROR SUNAT
            |--------------------------------------------------------------------------
            */
            if ($sale->hasSunatError()) {
                $this->setFreeSaleAnnulmentRequestData(
                    $sale,
                    'Anulación interna de venta libre con comprobante en Error SUNAT',
                    'internal'
                );

                $this->reverseFreeSaleFinancially(
                    $sale,
                    'Anulación de venta libre con comprobante en Error SUNAT'
                );

                $this->markFreeSaleAnnulmentAccepted(
                    $sale,
                    'internal'
                );

                DB::commit();

                return response()->json([
                    'message' =>
                        'La venta libre fue anulada internamente. El comprobante tenía estado Error SUNAT.',

                    'annulment_status' => 'accepted',
                    'pending_annulment' => false,

                    'internal_reversal_status' =>
                        'reversed',
                ], 200);
            }

            /*
            |--------------------------------------------------------------------------
            | 3. BOLETA EMITIDA HOY
            |--------------------------------------------------------------------------
            */
            if ($sale->isReceiptFromToday()) {
                $sale->annulment_status =
                    'waiting_sunat_process';

                $sale->annulment_type =
                    'nubefact_baja';

                $sale->annulment_reason =
                    'Boleta emitida hoy. Esperando procesamiento de Nubefact/SUNAT.';

                $sale->annulment_requested_at = now();
                $sale->annulment_requested_by = Auth::id();
                $sale->save();

                /*
                 * Mantenemos el mismo criterio de la venta normal:
                 * reversión financiera inmediata, aunque SUNAT siga pendiente.
                 */
                $this->reverseFreeSaleFinancially(
                    $sale,
                    'Boleta emitida hoy. Reversión financiera anticipada.'
                );

                DB::commit();

                return response()->json([
                    'message' =>
                        'La boleta fue emitida hoy. La reversión financiera fue aplicada, pero Nubefact podrá procesar la baja posteriormente.',

                    'annulment_status' =>
                        'waiting_sunat_process',

                    'waiting_sunat_process' => true,
                    'pending_annulment' => true,

                    'internal_reversal_status' =>
                        'reversed',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | 4. COMPROBANTE FUERA DEL PLAZO DE BAJA
            |--------------------------------------------------------------------------
            |
            | Todavía no revertimos financieramente porque la nota de crédito
            | aún no ha sido generada ni aceptada.
            */
            if (!$sale->isWithinAnnulmentDeadline()) {
                $sale->annulment_status =
                    'requires_credit_note';

                $sale->annulment_type =
                    'credit_note';

                $sale->annulment_reason =
                    'Comprobante fuera de plazo para baja. Requiere Nota de Crédito.';

                $sale->annulment_requested_at = now();
                $sale->annulment_requested_by = Auth::id();
                $sale->save();

                DB::commit();

                return response()->json([
                    'message' =>
                        'El comprobante ya no puede anularse mediante una baja. Debe generar una Nota de Crédito.',

                    'annulment_status' =>
                        'requires_credit_note',

                    'requires_credit_note' => true,
                    'pending_annulment' => false,

                    'internal_reversal_status' =>
                        $sale->internal_reversal_status,
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | 5. COMPROBANTE VÁLIDO DENTRO DEL PLAZO
            |--------------------------------------------------------------------------
            */
            $reason =
                'Anulación de comprobante de venta libre solicitada por el usuario';

            $sale->annulment_status = 'pending';
            $sale->annulment_type = 'nubefact_baja';
            $sale->annulment_reason = $reason;
            $sale->annulment_requested_at = now();
            $sale->annulment_requested_by = Auth::id();
            $sale->save();

            $result = $this->anularComprobanteNubefact(
                $sale,
                $reason
            );

            $this->persistNubefactAnnulmentResult(
                $sale,
                $result,
                $reason
            );

            $sale->refresh();

            /*
             * Aplicamos la reversión financiera una sola vez,
             * aunque SUNAT quede pendiente.
             */
            if (
                $sale->internal_reversal_status !==
                'reversed'
            ) {
                $this->reverseFreeSaleFinancially(
                    $sale,
                    'Anulación de venta libre enviada a Nubefact'
                );

                $sale->refresh();
            }

            /*
             * El Trait establece accepted, pending o rejected.
             */
            if ($sale->annulment_status === 'accepted') {
                $sale->state_annulled = 1;
                $sale->annulled_by = Auth::id();

                $sale->annulment_accepted_at =
                    $sale->annulment_accepted_at ?: now();

                $sale->save();
            }

            DB::commit();

            return response()->json([
                'message' =>
                    $sale->annulment_status === 'accepted'
                        ? 'La venta libre y su comprobante fueron anulados correctamente.'
                        : 'La anulación fue enviada a Nubefact y la reversión financiera fue aplicada. La confirmación de SUNAT puede quedar pendiente.',

                'annulment_status' =>
                    $sale->annulment_status,

                'pending_annulment' => in_array(
                    $sale->annulment_status,
                    ['pending', 'waiting_sunat_process'],
                    true
                ),

                'internal_reversal_status' =>
                    $sale->internal_reversal_status,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo anular la venta libre: ' .
                    $e->getMessage(),
            ], 422);
        }
    }

    private function reverseFreeSaleFinanciallyO(Sale $sale,string $reason) {
        /*
         * Idempotencia a nivel de Sale.
         */
        if (
            $sale->internal_reversal_status ===
            'reversed'
        ) {
            return;
        }

        /*
         * Obtenemos todos los movimientos directamente relacionados
         * con la venta:
         *
         * - pago normal
         * - vuelto
         * - pagos parciales
         */
        $movements = CashMovement::query()
            ->with([
                'cashRegister.cashBox',
                'regularizedChildren.cashRegister.cashBox',
            ])
            ->where('sale_id', $sale->id)
            ->lockForUpdate()
            ->get();

        /*
         * En caso de que algún pago parcial conserve un movimiento
         * cuyo sale_id no estuviera poblado, también lo incorporamos
         * mediante cash_movement_id.
         */
        $partialPayments = SalePartialPayment::query()
            ->with([
                'cashMovement.cashRegister.cashBox',
            ])
            ->where('sale_id', $sale->id)
            ->where('state', 1)
            ->lockForUpdate()
            ->get();

        foreach ($partialPayments as $partialPayment) {
            if (
                $partialPayment->cashMovement &&
                !$movements->contains(
                    'id',
                    $partialPayment->cashMovement->id
                )
            ) {
                $movements->push(
                    $partialPayment->cashMovement
                );
            }
        }

        /*
         * Incluimos posibles movimientos hijos generados al
         * regularizar un movimiento bancario diferido.
         */
        $additionalMovements = collect();

        foreach ($movements as $movement) {
            foreach (
                $movement->regularizedChildren
                as $regularizedChild
            ) {
                if (
                !$movements->contains(
                    'id',
                    $regularizedChild->id
                )
                ) {
                    $additionalMovements->push(
                        $regularizedChild
                    );
                }
            }
        }

        $movements = $movements
            ->concat($additionalMovements)
            ->unique('id')
            ->sortBy('id')
            ->values();

        foreach ($movements as $movement) {
            $this->reverseFreeSaleCashMovement(
                $movement,
                $sale,
                $reason
            );
        }

        /*
         * Los pagos parciales no se eliminan.
         * Solo dejan de considerarse activos.
         */
        foreach ($partialPayments as $partialPayment) {
            $partialPayment->state = 0;
            $partialPayment->save();
        }

        $sale->internal_reversal_status = 'reversed';
        $sale->internal_reversed_at = now();
        $sale->internal_reversed_by = Auth::id();
        $sale->save();
    }

    private function reverseFreeSaleCashMovementO(CashMovement $originalMovement,Sale $sale,string $reason) {
        /*
         * No revertimos movimientos que ya son, a su vez,
         * movimientos compensatorios.
         */
        if (!empty($originalMovement->cash_movement_origin_id)) {
            return;
        }

        /*
         * Idempotencia por movimiento.
         */
        $existingReversal = CashMovement::query()
            ->where(
                'cash_movement_origin_id',
                $originalMovement->id
            )
            ->first();

        if ($existingReversal) {
            return;
        }

        if (!$originalMovement->cash_register_id) {
            throw new \Exception(
                "El movimiento {$originalMovement->id} no tiene una sesión de caja asociada."
            );
        }

        $cashRegister = CashRegister::query()
            ->where(
                'id',
                $originalMovement->cash_register_id
            )
            ->lockForUpdate()
            ->first();

        if (!$cashRegister) {
            throw new \Exception(
                "No se encontró la sesión del movimiento {$originalMovement->id}."
            );
        }

        /*
         * sale e income incrementan el balance.
         * expense lo reduce.
         */
        if (
        in_array(
            $originalMovement->type,
            ['sale', 'income'],
            true
        )
        ) {
            $reversalType = 'expense';
        } elseif (
            $originalMovement->type === 'expense'
        ) {
            $reversalType = 'income';
        } else {
            throw new \Exception(
                "El movimiento {$originalMovement->id} tiene un tipo no soportado."
            );
        }

        /*
         * impactAmount() devuelve cero si regularize = 0.
         *
         * El movimiento compensatorio conserva el estado de
         * regularización del movimiento original.
         */
        $impactAmount = number_format(
            (float) $originalMovement->impactAmount(),
            2,
            '.',
            ''
        );

        $movementAmount = number_format(
            (float) $originalMovement->amount,
            2,
            '.',
            ''
        );

        CashMovement::create([
            'cash_register_id' =>
                $cashRegister->id,

            'type' => $reversalType,

            /*
             * Conservamos el monto nominal original.
             */
            'amount' => $movementAmount,

            'description' =>
                'Reversión de movimiento por anulación de venta libre #' .
                $sale->id,

            'observation' =>
                $reason .
                '. Movimiento original #' .
                $originalMovement->id,

            /*
             * Si el original estaba diferido, su reversión
             * también queda sin impacto en el balance.
             */
            'regularize' =>
                (bool) $originalMovement->regularize,

            'amount_regularize' =>
                $originalMovement->amount_regularize,

            'commission' =>
                $originalMovement->commission,

            'cash_box_subtype_id' =>
                $originalMovement->cash_box_subtype_id,

            'sale_id' => $sale->id,

            'cash_movement_origin_id' =>
                $originalMovement->id,

            'cash_movement_regularize_id' => null,

            'arqueo' => false,
        ]);

        /*
         * Si el movimiento original no afectó el balance,
         * tampoco alteramos la sesión.
         */
        if ((float) $impactAmount <= 0) {
            return;
        }

        if ($reversalType === 'expense') {
            $cashRegister->current_balance =
                bcsub(
                    (string) $cashRegister->current_balance,
                    $impactAmount,
                    2
                );

            $cashRegister->total_expenses =
                bcadd(
                    (string) $cashRegister->total_expenses,
                    $impactAmount,
                    2
                );
        } else {
            $cashRegister->current_balance =
                bcadd(
                    (string) $cashRegister->current_balance,
                    $impactAmount,
                    2
                );

            /*
             * Un ingreso por reversión del vuelto no es una venta.
             * Por ello no aumentamos total_sales.
             *
             * Si CashRegister cuenta con total_income,
             * podrías incrementarlo aquí.
             */
        }

        $cashRegister->save();
    }

    private function setFreeSaleAnnulmentRequestData(Sale $sale,string $reason,string $type) {
        $sale->annulment_status = 'pending';
        $sale->annulment_type = $type;
        $sale->annulment_reason = $reason;
        $sale->annulment_requested_at = now();
        $sale->annulment_requested_by = Auth::id();
        $sale->save();
    }

    private function markFreeSaleAnnulmentAccepted(Sale $sale,string $type) {
        $sale->state_annulled = 1;
        $sale->annulment_status = 'accepted';
        $sale->annulment_type = $type;
        $sale->annulment_accepted_at = now();
        $sale->annulled_by = Auth::id();

        $sale->annulment_sunat_status =
            $type === 'internal'
                ? null
                : $sale->annulment_sunat_status;

        $sale->save();
    }

    public function consultAnnulment($id)
    {
        DB::beginTransaction();

        try {
            $sale = Sale::query()
                ->where('free_sale', true)
                ->lockForUpdate()
                ->find($id);

            if (!$sale) {
                return response()->json([
                    'message' =>
                        'La venta libre no fue encontrada.',
                ], 422);
            }

            if (
            !in_array(
                $sale->annulment_status,
                ['pending', 'waiting_sunat_process'],
                true
            )
            ) {
                return response()->json([
                    'message' =>
                        'La venta libre no tiene una anulación pendiente para consultar.',
                ], 422);
            }

            $result =
                $this->consultarAnulacionNubefact($sale);

            $reason =
                $sale->annulment_reason
                    ?: 'Consulta de anulación de venta libre';

            $this->persistNubefactAnnulmentResult(
                $sale,
                $result,
                $reason
            );

            $sale->refresh();

            /*
             * Normalmente la reversión ya se aplicó cuando se
             * solicitó la baja. Esta condición garantiza idempotencia.
             */
            if (
                $sale->internal_reversal_status !==
                'reversed'
            ) {
                $this->reverseFreeSaleFinancially(
                    $sale,
                    'Reversión financiera luego de consultar la anulación'
                );

                $sale->refresh();
            }

            if ($sale->annulment_status === 'accepted') {
                $sale->state_annulled = 1;
                $sale->annulled_by =
                    $sale->annulled_by ?: Auth::id();

                $sale->annulment_accepted_at =
                    $sale->annulment_accepted_at ?: now();

                $sale->save();
            }

            DB::commit();

            return response()->json([
                'message' =>
                    $sale->annulment_status === 'accepted'
                        ? 'SUNAT aceptó la anulación de la venta libre.'
                        : (
                    $sale->annulment_status === 'rejected'
                        ? 'SUNAT rechazó la anulación de la venta libre.'
                        : 'La anulación continúa pendiente en SUNAT.'
                    ),

                'annulment_status' =>
                    $sale->annulment_status,

                'pending_annulment' => in_array(
                    $sale->annulment_status,
                    ['pending', 'waiting_sunat_process'],
                    true
                ),

                'internal_reversal_status' =>
                    $sale->internal_reversal_status,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo consultar la anulación: ' .
                    $e->getMessage(),
            ], 422);
        }
    }

    public function generateInvoice(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | VALIDACIÓN INICIAL
        |--------------------------------------------------------------------------
        */
        $request->validate([
            'sale_id' => [
                'required',
                'integer',
                'exists:sales,id',
            ],

            'tipo_comprobante' => [
                'required',
                'in:boleta,factura',
            ],

            'dni' => [
                'nullable',
                'string',
                'max:8',
            ],

            'name' => [
                'nullable',
                'string',
                'max:200',
            ],

            'email_boleta' => [
                'nullable',
                'email',
                'max:150',
            ],

            'ruc' => [
                'nullable',
                'string',
                'max:11',
            ],

            'razon_social' => [
                'nullable',
                'string',
                'max:200',
            ],

            'direccion_fiscal' => [
                'nullable',
                'string',
                'max:250',
            ],

            'email_factura' => [
                'nullable',
                'email',
                'max:150',
            ],
        ]);

        $sale = null;

        try {
            /*
            |--------------------------------------------------------------------------
            | 1. VALIDAR Y PREPARAR LA VENTA
            |--------------------------------------------------------------------------
            |
            | Esta transacción solo bloquea la venta mientras validamos
            | y actualizamos los datos fiscales.
            |
            | La llamada HTTP a Nubefact se hará fuera de la transacción.
            */
            $sale = DB::transaction(function () use ($request) {
                $sale = Sale::query()
                    ->with([
                        'details.material',
                        'details.stockItem',
                    ])
                    ->where('free_sale', true)
                    ->lockForUpdate()
                    ->find($request->input('sale_id'));

                if (!$sale) {
                    throw ValidationException::withMessages([
                        'sale_id' =>
                            'La venta libre no fue encontrada.',
                    ]);
                }

                /*
                 * No se puede generar comprobante para una venta anulada.
                 */
                if ((int) $sale->state_annulled === 1) {
                    throw ValidationException::withMessages([
                        'sale_id' =>
                            'No puede generar un comprobante para una venta libre anulada.',
                    ]);
                }

                /*
                 * Tampoco debe tener una anulación activa o una solicitud
                 * que requiera nota de crédito.
                 */
                if (
                in_array(
                    $sale->annulment_status,
                    [
                        'pending',
                        'waiting_sunat_process',
                        'accepted',
                        'requires_credit_note',
                    ],
                    true
                )
                ) {
                    throw ValidationException::withMessages([
                        'sale_id' =>
                            'La venta libre tiene un proceso de anulación y no puede generar un comprobante.',
                    ]);
                }

                /*
                 * Si ya tiene factura o boleta válida, no permitimos generar otra.
                 *
                 * Cuando sunat_status es Error sí permitimos reintentar.
                 */
                if (
                    in_array(
                        $sale->type_document,
                        ['01', '03'],
                        true
                    ) &&
                    $sale->sunat_status !== 'Error'
                ) {
                    throw ValidationException::withMessages([
                        'sale_id' =>
                            'Esta venta libre ya tiene un comprobante generado.',
                    ]);
                }

                /*
                 * La venta debe tener conceptos.
                 */
                if ($sale->details->isEmpty()) {
                    throw ValidationException::withMessages([
                        'sale_id' =>
                            'La venta libre no contiene conceptos para facturar.',
                    ]);
                }

                /*
                 * Validamos la consistencia de todos los detalles.
                 */
                foreach ($sale->details as $detail) {
                    $description = trim(
                        (string) $detail->description
                    );

                    if (
                        empty($detail->material_id) &&
                        $description === ''
                    ) {
                        throw ValidationException::withMessages([
                            'sale_id' =>
                                'Uno de los conceptos de la venta libre no tiene descripción.',
                        ]);
                    }

                    if ((float) $detail->quantity <= 0) {
                        throw ValidationException::withMessages([
                            'sale_id' =>
                                'Uno de los conceptos tiene una cantidad inválida.',
                        ]);
                    }

                    if ((float) $detail->price < 0) {
                        throw ValidationException::withMessages([
                            'sale_id' =>
                                'Uno de los conceptos tiene un precio inválido.',
                        ]);
                    }

                    if ((float) $detail->total < 0) {
                        throw ValidationException::withMessages([
                            'sale_id' =>
                                'Uno de los conceptos tiene un total inválido.',
                        ]);
                    }

                    if (
                        is_null($detail->valor_unitario) ||
                        (float) $detail->valor_unitario < 0
                    ) {
                        throw ValidationException::withMessages([
                            'sale_id' =>
                                'Uno de los conceptos no tiene un valor unitario válido.',
                        ]);
                    }
                }

                /*
                 * Validación de totales principales.
                 */
                if ((float) $sale->importe_total <= 0) {
                    throw ValidationException::withMessages([
                        'sale_id' =>
                            'La venta libre no tiene un importe total válido.',
                    ]);
                }

                if ((float) $sale->op_gravada < 0) {
                    throw ValidationException::withMessages([
                        'sale_id' =>
                            'La operación gravada de la venta no es válida.',
                    ]);
                }

                if ((float) $sale->igv < 0) {
                    throw ValidationException::withMessages([
                        'sale_id' =>
                            'El IGV de la venta no es válido.',
                    ]);
                }

                $tipoComprobante = $request->input(
                    'tipo_comprobante'
                );

                /*
                |--------------------------------------------------------------------------
                | BOLETA
                |--------------------------------------------------------------------------
                */
                if ($tipoComprobante === 'boleta') {
                    $documentNumber = preg_replace(
                        '/\D/',
                        '',
                        (string) $request->input('dni')
                    );

                    $customerName = trim(
                        (string) $request->input('name')
                    );

                    $email = trim(
                        (string) $request->input(
                            'email_boleta'
                        )
                    );

                    if (
                    !preg_match(
                        '/^\d{8}$/',
                        $documentNumber
                    )
                    ) {
                        throw ValidationException::withMessages([
                            'dni' =>
                                'Ingrese un DNI válido de 8 dígitos.',
                        ]);
                    }

                    if ($customerName === '') {
                        throw ValidationException::withMessages([
                            'name' =>
                                'Ingrese o consulte el nombre del cliente.',
                        ]);
                    }

                    $sale->type_document = '03';

                    $sale->tipo_documento_cliente = '1';

                    $sale->numero_documento_cliente =
                        $documentNumber;

                    $sale->nombre_cliente =
                        $customerName;

                    /*
                     * Conservamos la dirección histórica de la venta.
                     * Para boleta no es obligatorio eliminarla.
                     */
                    $sale->email_cliente =
                        $email !== '' ? $email : null;
                }

                /*
                |--------------------------------------------------------------------------
                | FACTURA
                |--------------------------------------------------------------------------
                */
                if ($tipoComprobante === 'factura') {
                    $documentNumber = preg_replace(
                        '/\D/',
                        '',
                        (string) $request->input('ruc')
                    );

                    $businessName = trim(
                        (string) $request->input(
                            'razon_social'
                        )
                    );

                    $fiscalAddress = trim(
                        (string) $request->input(
                            'direccion_fiscal'
                        )
                    );

                    $email = trim(
                        (string) $request->input(
                            'email_factura'
                        )
                    );

                    if (
                    !preg_match(
                        '/^\d{11}$/',
                        $documentNumber
                    )
                    ) {
                        throw ValidationException::withMessages([
                            'ruc' =>
                                'Ingrese un RUC válido de 11 dígitos.',
                        ]);
                    }

                    if ($businessName === '') {
                        throw ValidationException::withMessages([
                            'razon_social' =>
                                'Ingrese o consulte la razón social.',
                        ]);
                    }

                    if ($fiscalAddress === '') {
                        throw ValidationException::withMessages([
                            'direccion_fiscal' =>
                                'Ingrese o consulte la dirección fiscal.',
                        ]);
                    }

                    $sale->type_document = '01';

                    $sale->tipo_documento_cliente = '6';

                    $sale->numero_documento_cliente =
                        $documentNumber;

                    $sale->nombre_cliente =
                        $businessName;

                    $sale->direccion_cliente =
                        $fiscalAddress;

                    $sale->email_cliente =
                        $email !== '' ? $email : null;
                }

                /*
                 * Limpiamos el error anterior para permitir un nuevo intento.
                 *
                 * No eliminamos serie_sunat ni numero aquí porque normalmente
                 * estarán vacíos cuando hubo un error de generación.
                 */
                $sale->sunat_status = null;
                $sale->sunat_message = null;

                $sale->save();

                return $sale;
            }, 3);

            /*
            |--------------------------------------------------------------------------
            | 2. GENERAR COMPROBANTE EN NUBEFACT
            |--------------------------------------------------------------------------
            |
            | Esta llamada se ejecuta fuera de la transacción para no mantener
            | bloqueada la fila mientras esperamos la respuesta HTTP.
            */
            try {
                $sale->loadMissing([
                    'details.material',
                    'details.stockItem',
                ]);

                $nubefactResult =
                    $this->generarComprobanteNubefactParaVenta(
                        $sale
                    );

                /*
                 * Este método:
                 *
                 * 1. Guarda serie, número y estado SUNAT.
                 * 2. Intenta descargar PDF, XML y CDR.
                 * 3. Devuelve advertencias cuando algún archivo falla.
                 */
                $fileResult =
                    $this->persistNubefactFilesAndUpdateSale(
                        $sale,
                        $nubefactResult
                    );

            } catch (\Throwable $e) {
                Log::error(
                    'Error generando comprobante de venta libre',
                    [
                        'sale_id' => $sale->id,
                        'error' => $e->getMessage(),
                    ]
                );

                /*
                 * Si ya existe serie y número, significa que el comprobante
                 * pudo haber sido registrado antes de que fallara otra operación.
                 * Evitamos convertirlo incorrectamente en Error.
                 */
                $sale->refresh();

                if (
                    empty($sale->serie_sunat) ||
                    empty($sale->numero)
                ) {
                    $sale->update([
                        'sunat_status' => 'Error',
                        'sunat_message' => $e->getMessage(),
                    ]);
                }

                return response()->json([
                    'message' =>
                        'No se pudo generar el comprobante de la venta libre: ' .
                        $e->getMessage(),
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | 3. PREPARAR RESPUESTA
            |--------------------------------------------------------------------------
            */
            $sale->refresh();

            $urlPrint = route(
                'puntoVenta.print',
                $sale->id
            );

            $printType = 'ticket';
            $pdfAvailable = false;

            /*
             * Prioridad 1: PDF local validado.
             */
            if (!empty($sale->pdf_path)) {
                $localPath = public_path(
                    'comprobantes/pdfs/' .
                    $sale->pdf_path
                );

                if (file_exists($localPath)) {
                    $urlPrint = asset(
                        'comprobantes/pdfs/' .
                        $sale->pdf_path
                    );

                    $printType = 'sunat_pdf';
                    $pdfAvailable = true;
                }
            }

            /*
             * Prioridad 2: enlace remoto devuelto por Nubefact.
             *
             * Solo lo usamos si no tenemos un PDF local.
             */
            if (
                !$pdfAvailable &&
                !empty(
                $nubefactResult['enlace_del_pdf']
                )
            ) {
                $urlPrint =
                    $nubefactResult['enlace_del_pdf'];

                $printType = 'sunat_pdf_remote';
                $pdfAvailable = true;
            }

            $message =
                'Comprobante de la venta libre generado correctamente.';

            if (
                !empty($fileResult['errores']) &&
                is_array($fileResult['errores'])
            ) {
                $message .=
                    ' El comprobante fue emitido, pero algunos archivos no pudieron descargarse localmente.';
            }

            return response()->json([
                'message' => $message,

                'sale_id' => $sale->id,

                'url_print' => $urlPrint,
                'print_type' => $printType,
                'pdf_available' => $pdfAvailable,

                'type_document' =>
                    $sale->type_document,

                'serie_sunat' =>
                    $sale->serie_sunat,

                'numero' =>
                    $sale->numero,

                'sunat_status' =>
                    $sale->sunat_status,

                'sunat_message' =>
                    $sale->sunat_message,

                'files' => [
                    'pdf' => !empty(
                    $fileResult['pdf_descargado']
                    ),

                    'xml' => !empty(
                    $fileResult['xml_descargado']
                    ),

                    'cdr' => !empty(
                    $fileResult['cdr_descargado']
                    ),
                ],

                'file_warnings' =>
                    isset($fileResult['errores'])
                        ? $fileResult['errores']
                        : [],
            ], 200);

        } catch (ValidationException $e) {
            throw $e;

        } catch (\Throwable $e) {
            Log::error(
                'Error inesperado generando comprobante de venta libre',
                [
                    'sale_id' => $request->input('sale_id'),
                    'error' => $e->getMessage(),
                ]
            );

            return response()->json([
                'message' =>
                    'No se pudo generar el comprobante de la venta libre: ' .
                    $e->getMessage(),
            ], 422);
        }
    }
}
