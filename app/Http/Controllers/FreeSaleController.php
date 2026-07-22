<?php

namespace App\Http\Controllers;

use App\CashBox;
use App\CashBoxSubtype;
use App\CashMovement;
use App\CashRegister;
use App\Customer;
use App\DataGeneral;
use App\Http\Requests\StoreFreeSaleRequest;
use App\Sale;
use App\SaleDetail;
use App\Worker;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FreeSaleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        /*$this->middleware('permission:listFreeSale_puntoVenta')
            ->only(['index']);*/

        $this->middleware('permission:createFreeSale_puntoVenta')
            ->only(['create', 'store']);

        $this->middleware('permission:anularFreeSale_puntoVenta')
            ->only(['annul']);
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

    private function resolveFreeSaleCustomer(
        StoreFreeSaleRequest $request
    ) {
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

    private function calculateFreeSaleItems(
        array $items,
        $taxPercentage
    ) {
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

    private function resolveFreeSalePayment(
        StoreFreeSaleRequest $request,
        $totalAmount,
        $partialPayments
    ) {
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

    private function createFreeSaleCashMovements(
        Sale $sale,
        array $paymentData
    ) {
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

    private function roundFreeSaleDecimal(
        $value,
        $precision = 2
    ) {
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

    private function generateFreeSaleSerie(
        $requestedSerie = null
    ) {
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
    public function annul($saleId)
    {
        // Se implementará en una etapa posterior.
    }
}
