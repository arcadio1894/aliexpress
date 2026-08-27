<?php

namespace App\Console\Commands;

use App\CashBoxSubtype;
use App\CashMovement;
use App\CashRegister;
use App\CreditNote;
use App\Http\Controllers\Traits\NubefactTrait;
use App\InventoryLevel;
use App\Item;
use App\OutputDetail;
use App\Quote;
use App\QuoteMaterialReservation;
use App\QuoteStockLot;
use App\Sale;
use App\SalePartialPayment;
use App\StockLot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Throwable;

class CheckPendingSunatDocuments extends Command
{
    use NubefactTrait;

    protected $signature = 'sunat:check-pending';

    protected $description = 'Consulta anulaciones y notas de crédito pendientes en Nubefact/SUNAT';

    public function handle()
    {
        $annulmentLogs = [];
        $creditNoteLogs = [];

        $summary = [
            'annulments' => 0,
            'credit_notes' => 0,
            'accepted' => 0,
            'pending' => 0,
            'rejected' => 0,
            'error' => 0,
            'waiting' => 0,
            'skipped' => 0,
            'requires_credit_note' => 0,
        ];

        /*
         * ============================================================
         * ANULACIONES PENDIENTES
         * ============================================================
         */
        $sales = Sale::whereIn('annulment_status', [
            'pending',
            'waiting_sunat_process'
        ])
            ->where('state_annulled', 0)
            ->orderBy('id')
            ->get();

        $summary['annulments'] = $sales->count();

        foreach ($sales as $sale) {
            try {
                $result = $this->processAnnulmentInCommand($sale);

                $status = $result['status'] ?? 'error';

                if (isset($summary[$status])) {
                    $summary[$status]++;
                } else {
                    $summary['error']++;
                }

                $annulmentLogs[] = [
                    'sale_id' => $sale->id,
                    'status' => $status,
                    'message' => $result['message'] ?? 'Sin mensaje.',
                ];

            } catch (Throwable $e) {
                $summary['error']++;

                $annulmentLogs[] = [
                    'sale_id' => $sale->id,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        /*
         * ============================================================
         * NOTAS DE CRÉDITO PENDIENTES
         * ============================================================
         */
        $creditNotes = CreditNote::where('status', 'pending')
            ->orderBy('id')
            ->get();

        $summary['credit_notes'] = $creditNotes->count();

        foreach ($creditNotes as $creditNote) {
            try {
                $result = $this->processCreditNoteInCommand($creditNote);

                $status = $result['status'] ?? 'error';

                if (isset($summary[$status])) {
                    $summary[$status]++;
                } else {
                    $summary['error']++;
                }

                $creditNoteLogs[] = [
                    'credit_note_id' => $creditNote->id,
                    'sale_id' => $creditNote->sale_id,
                    'status' => $status,
                    'message' => $result['message'] ?? 'Sin mensaje.',
                ];

            } catch (Throwable $e) {
                $summary['error']++;

                $creditNoteLogs[] = [
                    'credit_note_id' => $creditNote->id,
                    'sale_id' => $creditNote->sale_id,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        $html = view('email.sunat_pending_report', [
            'date' => now()->format('d/m/Y H:i:s'),
            'annulments' => $annulmentLogs,
            'creditNotes' => $creditNoteLogs,
            'summary' => $summary,
        ])->render();

        Mail::send([], [], function ($message) use ($html) {
            $message->to('joryes1894@gmail.com')
                ->subject('Reporte automático SUNAT/Nubefact - ' . now()->format('d/m/Y'))
                ->setBody($html, 'text/html');
        });

        $this->info('Proceso SUNAT/Nubefact finalizado.');
        $this->info('Anulaciones consultadas: ' . $summary['annulments']);
        $this->info('Notas de crédito consultadas: ' . $summary['credit_notes']);
        $this->info('Aceptadas: ' . $summary['accepted']);
        $this->info('Pendientes: ' . $summary['pending']);
        $this->info('Rechazadas: ' . $summary['rejected']);
        $this->info('Errores: ' . $summary['error']);

        return 0;
    }

    private function processAnnulmentInCommandO(Sale $sale): array
    {
        DB::beginTransaction();

        try {
            $sale = Sale::with(['details'])
                ->lockForUpdate()
                ->find($sale->id);

            if (!$sale) {
                DB::rollBack();

                return [
                    'status' => 'error',
                    'message' => 'Venta no encontrada.',
                ];
            }

            if ((int) $sale->state_annulled === 1) {
                DB::commit();

                return [
                    'status' => 'accepted',
                    'message' => 'La venta ya se encuentra anulada internamente.',
                ];
            }

            if (!in_array($sale->annulment_status, ['pending', 'waiting_sunat_process'], true)) {
                DB::commit();

                return [
                    'status' => 'skipped',
                    'message' => 'La venta no tiene anulación pendiente.',
                ];
            }

            if ($sale->annulment_status === 'waiting_sunat_process') {

                if ($sale->isReceiptFromToday()) {
                    DB::commit();

                    return [
                        'status' => 'waiting',
                        'message' => 'Boleta emitida hoy. Se consultará mañana.',
                    ];
                }

                if (!$sale->isWithinAnnulmentDeadline()) {
                    $sale->annulment_status = 'requires_credit_note';
                    $sale->annulment_type = 'credit_note';
                    $sale->annulment_reason = 'Boleta fuera de plazo para baja. Requiere Nota de Crédito.';
                    $sale->save();

                    DB::commit();

                    return [
                        'status' => 'requires_credit_note',
                        'message' => 'Boleta fuera de plazo. Requiere Nota de Crédito.',
                    ];
                }

                $motivo = $sale->annulment_reason ?: 'Anulación automática desde cron';

                $sale->annulment_status = 'pending';
                $sale->annulment_type = 'nubefact_baja';
                $sale->annulment_requested_at = $sale->annulment_requested_at ?: now();
                $sale->save();

                $result = $this->anularComprobanteNubefact($sale, $motivo);

                $this->persistNubefactAnnulmentResult($sale, $result, $motivo);

                $sale->refresh();

                if ($sale->internal_reversal_status !== 'reversed') {
                    $this->reverseSaleInternallyInCommand(
                        $sale,
                        'Anulación enviada a Nubefact desde cron. Reversión interna aplicada.',
                        false
                    );

                    $sale->refresh();
                }

                if ($sale->annulment_status === 'accepted') {
                    $sale->state_annulled = 1;
                    $sale->annulment_status = 'accepted';
                    $sale->annulment_accepted_at = $sale->annulment_accepted_at ?: now();
                    $sale->save();

                    DB::commit();

                    return [
                        'status' => 'accepted',
                        'message' => 'Anulación enviada y aceptada. Venta marcada como anulada.',
                    ];
                }

                DB::commit();

                return [
                    'status' => 'pending',
                    'message' => 'Anulación enviada a Nubefact. Pendiente SUNAT.',
                ];
            }

            $motivo = $sale->annulment_reason ?: 'Consulta automática de anulación desde cron';

            $result = $this->consultarAnulacionNubefact($sale);

            $this->persistNubefactAnnulmentResult($sale, $result, $motivo);

            $sale->refresh();

            if ($sale->annulment_status === 'accepted') {

                if ($sale->internal_reversal_status !== 'reversed') {
                    $this->reverseSaleInternallyInCommand(
                        $sale,
                        'Anulación aceptada por SUNAT/Nubefact desde cron',
                        true
                    );

                    $sale->refresh();
                }

                $sale->state_annulled = 1;
                $sale->annulment_status = 'accepted';
                $sale->annulment_accepted_at = $sale->annulment_accepted_at ?: now();
                $sale->save();

                DB::commit();

                return [
                    'status' => 'accepted',
                    'message' => 'Anulación aceptada por SUNAT. Venta marcada como anulada.',
                ];
            }

            if ($sale->annulment_status === 'rejected') {
                DB::commit();

                return [
                    'status' => 'rejected',
                    'message' => $sale->annulment_error ?: 'Anulación rechazada por SUNAT.',
                ];
            }

            DB::commit();

            return [
                'status' => 'pending',
                'message' => 'Anulación todavía pendiente de aceptación SUNAT.',
            ];

        } catch (\Throwable $e) {
            DB::rollBack();

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    private function reverseSaleInternallyInCommandO(Sale $sale, ?string $reason = null, bool $markAsAnnulled = true): void
    {
        if ($sale->internal_reversal_status === 'reversed') {
            return;
        }

        $systemUserId = config('services.nubefact.system_user_id');

        $inventoryKeysToSync = [];

        foreach ($sale->details as $detail) {
            $outputDetails = OutputDetail::where('sale_detail_id', $detail->id)
                ->lockForUpdate()
                ->get();

            foreach ($outputDetails as $outputDetail) {
                $qtyToReturn = (float) ($outputDetail->percentage ?? 0);

                if ($qtyToReturn <= 0) {
                    $qtyToReturn = (float) ($detail->quantity ?? 0);
                }

                if (!empty($outputDetail->stock_lot_id)) {
                    $lot = StockLot::where('id', $outputDetail->stock_lot_id)
                        ->lockForUpdate()
                        ->first();

                    if ($lot) {
                        $lot->qty_on_hand = (float) $lot->qty_on_hand + $qtyToReturn;
                        $lot->save();

                        $inventoryKeysToSync[] = [
                            'stock_item_id' => (int) $lot->stock_item_id,
                            'warehouse_id'  => $lot->warehouse_id,
                            'location_id'   => $lot->location_id,
                        ];
                    }
                }

                if (!empty($outputDetail->item_id)) {
                    $item = Item::where('id', $outputDetail->item_id)
                        ->lockForUpdate()
                        ->first();

                    if ($item) {
                        $item->state_item = 'entered';
                        $item->save();
                    }
                }

                $outputDetail->delete();
            }
        }

        $inventoryKeysToSync = collect($inventoryKeysToSync)
            ->unique(function ($row) {
                return implode('|', [
                    $row['stock_item_id'] ?? 'null',
                    $row['warehouse_id'] ?? 'null',
                    $row['location_id'] ?? 'null',
                ]);
            })
            ->values()
            ->all();

        foreach ($inventoryKeysToSync as $key) {
            $this->syncInventoryLevelFromLots(
                (int) $key['stock_item_id'],
                $key['warehouse_id'],
                $key['location_id']
            );
        }

        $movements = CashMovement::where('sale_id', $sale->id)->get();

        foreach ($movements as $movement) {
            $cashBoxSubType = $movement->cash_box_subtype_id
                ? CashBoxSubtype::find($movement->cash_box_subtype_id)
                : null;

            $is_deferred = $cashBoxSubType ? (int) $cashBoxSubType->is_deferred : 0;
            $cash_box_subtype_id_to_use = $cashBoxSubType ? $cashBoxSubType->id : null;

            if ($movement->type === 'sale') {
                if ($is_deferred == 1) {
                    if ((int) $movement->regularize === 0) {
                        $movement->delete();
                    } elseif ((int) $movement->regularize === 1) {
                        $amountToReverse = (float) ($movement->amount_regularize ?? $movement->amount ?? 0);

                        CashMovement::create([
                            'cash_register_id'    => $movement->cash_register_id,
                            'sale_id'             => $sale->id,
                            'type'                => 'expense',
                            'amount'              => $amountToReverse,
                            'description'         => 'Reversión automática de venta (POS regularizado) por anulación de venta #' . $sale->id,
                            'regularize'          => $movement->regularize,
                            'cash_box_subtype_id' => $cash_box_subtype_id_to_use,
                        ]);

                        $cashRegister = CashRegister::find($movement->cash_register_id);

                        if ($cashRegister) {
                            $cashRegister->current_balance -= $amountToReverse;
                            $cashRegister->total_sales     -= $amountToReverse;
                            $cashRegister->total_expenses  += $amountToReverse;
                            $cashRegister->save();
                        }
                    }
                } else {
                    $amountToReverse = (float) $movement->amount;

                    CashMovement::create([
                        'cash_register_id'    => $movement->cash_register_id,
                        'sale_id'             => $sale->id,
                        'type'                => 'expense',
                        'amount'              => $amountToReverse,
                        'description'         => 'Reversión automática de venta por anulación de venta #' . $sale->id,
                        'regularize'          => $movement->regularize,
                        'cash_box_subtype_id' => $cash_box_subtype_id_to_use,
                    ]);

                    $cashRegister = CashRegister::find($movement->cash_register_id);

                    if ($cashRegister) {
                        $cashRegister->current_balance -= $amountToReverse;
                        $cashRegister->total_sales     -= $amountToReverse;
                        $cashRegister->total_expenses  += $amountToReverse;
                        $cashRegister->save();
                    }
                }
            } elseif ($movement->type === 'expense') {
                $amountToReverse = (float) $movement->amount;

                CashMovement::create([
                    'cash_register_id'    => $movement->cash_register_id,
                    'sale_id'             => $sale->id,
                    'type'                => 'income',
                    'amount'              => $amountToReverse,
                    'description'         => 'Reversión automática de gasto (vuelto) por anulación de orden #' . $sale->id,
                    'subtype'             => $movement->subtype,
                    'regularize'          => $movement->regularize,
                    'cash_box_subtype_id' => $cash_box_subtype_id_to_use,
                ]);

                $cashRegister = CashRegister::find($movement->cash_register_id);

                if ($cashRegister) {
                    $cashRegister->current_balance += $amountToReverse;
                    $cashRegister->total_incomes   += $amountToReverse;
                    $cashRegister->total_expenses  -= $amountToReverse;
                    $cashRegister->save();
                }
            }
        }

        if (!empty($sale->quote_id)) {
            QuoteMaterialReservation::where('quote_id', $sale->quote_id)
                ->lockForUpdate()
                ->delete();

            QuoteStockLot::where('quote_id', $sale->quote_id)
                ->lockForUpdate()
                ->delete();
        }

        $partial_payments = SalePartialPayment::where('sale_id', $sale->id)->get();

        foreach ($partial_payments as $partial_payment) {
            $partial_payment->state = 0;
            $partial_payment->save();
        }

        if ($markAsAnnulled) {
            $sale->state_annulled = 1;
        }

        $sale->internal_reversal_status = 'reversed';
        $sale->internal_reversed_at = now();
        $sale->internal_reversed_by = $systemUserId;

        if (empty($sale->annulment_status) || $sale->annulment_status === 'none') {
            $sale->annulment_status = $markAsAnnulled ? 'accepted' : 'pending';
        }

        $sale->annulment_reason = $reason;

        if (empty($sale->annulment_requested_at)) {
            $sale->annulment_requested_at = now();
        }

        if (empty($sale->annulment_requested_by)) {
            $sale->annulment_requested_by = $systemUserId;
        }

        if ($markAsAnnulled) {
            $sale->annulment_accepted_at = $sale->annulment_accepted_at ?: now();
            $sale->annulled_by = $sale->annulled_by ?: $systemUserId;
        }

        $sale->save();
    }

    private function reverseSaleInternallyInCommand(Sale $sale, ?string $reason = null, bool $markAsAnnulled = true): void
    {
        if ($sale->internal_reversal_status === 'reversed') {
            return;
        }

        $systemUserId = config('services.nubefact.system_user_id');

        /*
         * Nos permitirá saber si esta venta contenía
         * al menos un producto itemeable.
         */
        $hasItemizedProducts = false;

        /*
         * ============================================================
         * 1) REVERTIR STOCK DESDE OUTPUT DETAILS
         * ============================================================
         */
        $inventoryKeysToSync = [];

        foreach ($sale->details as $detail) {

            $outputDetails = OutputDetail::where(
                'sale_detail_id',
                $detail->id
            )
                ->lockForUpdate()
                ->get();

            foreach ($outputDetails as $outputDetail) {

                $qtyToReturn = (float) (
                    $outputDetail->percentage ?? 0
                );

                if ($qtyToReturn <= 0) {
                    $qtyToReturn = (float) (
                        $detail->quantity ?? 0
                    );
                }

                /*
                 * ========================================================
                 * DEVOLVER STOCK AL LOTE
                 * ========================================================
                 */
                if (!empty($outputDetail->stock_lot_id)) {

                    $lot = StockLot::where(
                        'id',
                        $outputDetail->stock_lot_id
                    )
                        ->lockForUpdate()
                        ->first();

                    if ($lot) {

                        $lot->qty_on_hand =
                            (float) $lot->qty_on_hand
                            + $qtyToReturn;

                        $lot->save();

                        $inventoryKeysToSync[] = [
                            'stock_item_id' => (int) $lot->stock_item_id,
                            'warehouse_id'  => $lot->warehouse_id,
                            'location_id'   => $lot->location_id,
                        ];
                    }
                }

                /*
                 * ========================================================
                 * PRODUCTO ITEMEABLE
                 * ========================================================
                 */
                if (!empty($outputDetail->item_id)) {

                    /*
                     * La venta sí contenía productos itemeables.
                     */
                    $hasItemizedProducts = true;

                    $item = Item::where(
                        'id',
                        $outputDetail->item_id
                    )
                        ->lockForUpdate()
                        ->first();

                    if ($item) {
                        $item->state_item = 'entered';
                        $item->save();
                    }
                }

                /*
                 * Ya devolvimos el stock/item.
                 * Eliminamos el detalle de salida.
                 */
                $outputDetail->delete();
            }
        }

        /*
         * ============================================================
         * 2) SINCRONIZAR INVENTORY LEVELS
         * ============================================================
         */
        $inventoryKeysToSync = collect($inventoryKeysToSync)
            ->unique(function ($row) {

                return implode('|', [
                    $row['stock_item_id'] ?? 'null',
                    $row['warehouse_id'] ?? 'null',
                    $row['location_id'] ?? 'null',
                ]);
            })
            ->values()
            ->all();

        foreach ($inventoryKeysToSync as $key) {

            $this->syncInventoryLevelFromLots(
                (int) $key['stock_item_id'],
                $key['warehouse_id'],
                $key['location_id']
            );
        }

        /*
         * ============================================================
         * 3) REVERTIR MOVIMIENTOS DE CAJA
         * ============================================================
         */
        $movements = CashMovement::where(
            'sale_id',
            $sale->id
        )
            ->get();

        foreach ($movements as $movement) {

            $cashBoxSubType = $movement->cash_box_subtype_id
                ? CashBoxSubtype::find(
                    $movement->cash_box_subtype_id
                )
                : null;

            $isDeferred = $cashBoxSubType
                ? (int) $cashBoxSubType->is_deferred
                : 0;

            $cashBoxSubtypeIdToUse = $cashBoxSubType
                ? $cashBoxSubType->id
                : null;

            /*
             * ========================================================
             * MOVIMIENTO TIPO SALE
             * ========================================================
             */
            if ($movement->type === 'sale') {

                /*
                 * Caja diferida
                 */
                if ($isDeferred == 1) {

                    /*
                     * No regularizado:
                     * simplemente eliminamos el movimiento.
                     */
                    if ((int) $movement->regularize === 0) {

                        $movement->delete();

                        /*
                         * Regularizado:
                         * debemos generar el egreso correspondiente.
                         */
                    } elseif ((int) $movement->regularize === 1) {

                        $amountToReverse = (float) (
                            $movement->amount_regularize
                            ?? $movement->amount
                            ?? 0
                        );

                        CashMovement::create([
                            'cash_register_id' =>
                                $movement->cash_register_id,

                            'sale_id' =>
                                $sale->id,

                            'type' =>
                                'expense',

                            'amount' =>
                                $amountToReverse,

                            'description' =>
                                'Reversión automática de venta '
                                . '(POS regularizado) por anulación '
                                . 'de venta #'
                                . $sale->id,

                            'regularize' =>
                                $movement->regularize,

                            'cash_box_subtype_id' =>
                                $cashBoxSubtypeIdToUse,
                        ]);

                        $cashRegister = CashRegister::find(
                            $movement->cash_register_id
                        );

                        if ($cashRegister) {

                            $cashRegister->current_balance -=
                                $amountToReverse;

                            $cashRegister->total_sales -=
                                $amountToReverse;

                            $cashRegister->total_expenses +=
                                $amountToReverse;

                            $cashRegister->save();
                        }
                    }

                    /*
                     * Caja normal
                     */
                } else {

                    $amountToReverse =
                        (float) $movement->amount;

                    CashMovement::create([
                        'cash_register_id' =>
                            $movement->cash_register_id,

                        'sale_id' =>
                            $sale->id,

                        'type' =>
                            'expense',

                        'amount' =>
                            $amountToReverse,

                        'description' =>
                            'Reversión automática de venta '
                            . 'por anulación de venta #'
                            . $sale->id,

                        'regularize' =>
                            $movement->regularize,

                        'cash_box_subtype_id' =>
                            $cashBoxSubtypeIdToUse,
                    ]);

                    $cashRegister = CashRegister::find(
                        $movement->cash_register_id
                    );

                    if ($cashRegister) {

                        $cashRegister->current_balance -=
                            $amountToReverse;

                        $cashRegister->total_sales -=
                            $amountToReverse;

                        $cashRegister->total_expenses +=
                            $amountToReverse;

                        $cashRegister->save();
                    }
                }

                /*
                 * ========================================================
                 * MOVIMIENTO TIPO EXPENSE
                 * ========================================================
                 */
            } elseif ($movement->type === 'expense') {

                $amountToReverse =
                    (float) $movement->amount;

                CashMovement::create([
                    'cash_register_id' =>
                        $movement->cash_register_id,

                    'sale_id' =>
                        $sale->id,

                    'type' =>
                        'income',

                    'amount' =>
                        $amountToReverse,

                    'description' =>
                        'Reversión automática de gasto (vuelto) '
                        . 'por anulación de orden #'
                        . $sale->id,

                    'subtype' =>
                        $movement->subtype,

                    'regularize' =>
                        $movement->regularize,

                    'cash_box_subtype_id' =>
                        $cashBoxSubtypeIdToUse,
                ]);

                $cashRegister = CashRegister::find(
                    $movement->cash_register_id
                );

                if ($cashRegister) {

                    $cashRegister->current_balance +=
                        $amountToReverse;

                    $cashRegister->total_incomes +=
                        $amountToReverse;

                    $cashRegister->total_expenses -=
                        $amountToReverse;

                    $cashRegister->save();
                }
            }
        }

        /*
         * ============================================================
         * 4) CANCELAR COTIZACIÓN SI TENÍA PRODUCTOS ITEMEABLES
         * ============================================================
         *
         * QuoteStockLot era una reserva temporal y ya fue consumida
         * al generar la venta.
         *
         * Si posteriormente anulamos esa venta y había items,
         * por ahora hemos decidido cancelar la cotización para evitar
         * que vuelva a facturarse sin poder reconstruir correctamente
         * los items originales.
         */
        if (
            $hasItemizedProducts &&
            !empty($sale->quote_id)
        ) {

            $quote = Quote::where(
                'id',
                $sale->quote_id
            )
                ->lockForUpdate()
                ->first();

            if (
                $quote &&
                $quote->state !== 'canceled'
            ) {
                $quote->state = 'canceled';
                $quote->save();
            }
        }

        /*
         * ============================================================
         * 5) LIMPIAR RESERVAS LEGACY
         * ============================================================
         */
        if (!empty($sale->quote_id)) {

            QuoteMaterialReservation::where(
                'quote_id',
                $sale->quote_id
            )
                ->lockForUpdate()
                ->delete();

            QuoteStockLot::where(
                'quote_id',
                $sale->quote_id
            )
                ->lockForUpdate()
                ->delete();
        }

        /*
         * ============================================================
         * 6) ANULAR PAGOS PARCIALES
         * ============================================================
         */
        $partialPayments = SalePartialPayment::where(
            'sale_id',
            $sale->id
        )
            ->get();

        foreach ($partialPayments as $partialPayment) {

            $partialPayment->state = 0;
            $partialPayment->save();
        }

        /*
         * ============================================================
         * 7) MARCAR REVERSIÓN OPERATIVA
         * ============================================================
         */
        if ($markAsAnnulled) {
            $sale->state_annulled = 1;
        }

        $sale->internal_reversal_status = 'reversed';
        $sale->internal_reversed_at = now();
        $sale->internal_reversed_by = $systemUserId;

        if (
            empty($sale->annulment_status) ||
            $sale->annulment_status === 'none'
        ) {

            $sale->annulment_status =
                $markAsAnnulled
                    ? 'accepted'
                    : 'pending';
        }

        $sale->annulment_reason = $reason;

        if (empty($sale->annulment_requested_at)) {
            $sale->annulment_requested_at = now();
        }

        if (empty($sale->annulment_requested_by)) {
            $sale->annulment_requested_by =
                $systemUserId;
        }

        if ($markAsAnnulled) {

            $sale->annulment_accepted_at =
                $sale->annulment_accepted_at
                    ?: now();

            $sale->annulled_by =
                $sale->annulled_by
                    ?: $systemUserId;
        }

        $sale->save();
    }

    private function processCreditNoteInCommand(CreditNote $creditNote): array
    {
        DB::beginTransaction();

        try {
            $creditNote = CreditNote::with(['sale.details', 'details.saleDetail'])
                ->lockForUpdate()
                ->find($creditNote->id);

            if (!$creditNote) {
                DB::rollBack();

                return [
                    'status' => 'error',
                    'message' => 'Nota de Crédito no encontrada.',
                ];
            }

            if ($creditNote->status !== 'pending') {
                DB::commit();

                return [
                    'status' => 'skipped',
                    'message' => 'La Nota de Crédito ya no está pendiente.',
                ];
            }

            $sale = $creditNote->sale;

            if (!$sale) {
                DB::commit();

                return [
                    'status' => 'error',
                    'message' => 'La Nota de Crédito no tiene venta asociada.',
                ];
            }

            $result = $this->consultarNotaCreditoNubefact($creditNote);

            $this->persistNubefactCreditNoteResult($creditNote, $result);

            $creditNote->refresh();
            $sale->refresh();

            if ($creditNote->status === 'accepted') {

                /*
                 * Si aún no se aplicó reversión operativa,
                 * la aplicamos aquí. Si ya se aplicó, no duplica.
                 */
                $this->applyCreditNoteInternalReversalInCommand($creditNote);

                $creditNote->refresh();
                $sale->refresh();

                if ($creditNote->credit_note_type === 'total') {
                    $sale->state_annulled = 1;
                    $sale->annulment_type = 'credit_note';
                    $sale->annulment_status = 'accepted';
                    $sale->credit_note_status = 'total';
                    $sale->annulment_reason = 'Venta anulada mediante Nota de Crédito';
                    $sale->annulment_requested_at = $sale->annulment_requested_at ?: now();
                    $sale->annulment_requested_by = $sale->annulment_requested_by ?: config('services.nubefact.system_user_id');
                    $sale->annulment_accepted_at = $sale->annulment_accepted_at ?: now();
                    $sale->annulled_by = $sale->annulled_by ?: config('services.nubefact.system_user_id');
                    $sale->save();

                    DB::commit();

                    return [
                        'status' => 'accepted',
                        'message' => 'NC total aceptada. Venta marcada como anulada definitivamente.',
                        'credit_note_type' => 'total',
                        'internal_reversal_status' => $creditNote->internal_reversal_status,
                    ];
                }

                if ($creditNote->credit_note_type === 'partial') {
                    $sale->credit_note_status = 'partial';
                    $sale->save();

                    DB::commit();

                    return [
                        'status' => 'accepted',
                        'message' => 'NC parcial aceptada. Reversión operativa confirmada.',
                        'credit_note_type' => 'partial',
                        'internal_reversal_status' => $creditNote->internal_reversal_status,
                        'cash_refund_status' => $creditNote->cash_refund_status,
                    ];
                }
            }

            if ($creditNote->status === 'rejected') {
                DB::commit();

                return [
                    'status' => 'rejected',
                    'message' => $creditNote->sunat_message ?: 'La Nota de Crédito fue rechazada por SUNAT.',
                    'credit_note_type' => $creditNote->credit_note_type,
                ];
            }

            DB::commit();

            return [
                'status' => 'pending',
                'message' => 'La Nota de Crédito todavía está pendiente de aceptación SUNAT.',
                'credit_note_type' => $creditNote->credit_note_type,
                'internal_reversal_status' => $creditNote->internal_reversal_status,
            ];

        } catch (\Throwable $e) {
            DB::rollBack();

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    private function processAnnulmentInCommand(Sale $sale): array
    {
        DB::beginTransaction();

        try {
            $systemUserId = config('services.nubefact.system_user_id');

            $sale = Sale::with(['details'])
                ->lockForUpdate()
                ->find($sale->id);

            if (!$sale) {
                DB::rollBack();

                return [
                    'status' => 'error',
                    'message' => 'Venta no encontrada.',
                ];
            }

            /*
             * ============================================================
             * VENTA YA ANULADA DEFINITIVAMENTE
             * ============================================================
             */
            if ((int) $sale->state_annulled === 1) {
                DB::commit();

                return [
                    'status' => 'accepted',
                    'message' => 'La venta ya se encuentra anulada internamente.',
                ];
            }

            /*
             * ============================================================
             * VALIDAR QUE TENGA UNA ANULACIÓN PENDIENTE
             * ============================================================
             */
            if (!in_array(
                $sale->annulment_status,
                [
                    'pending',
                    'waiting_sunat_process',
                ],
                true
            )) {
                DB::commit();

                return [
                    'status' => 'skipped',
                    'message' => 'La venta no tiene anulación pendiente.',
                ];
            }

            /*
             * ============================================================
             * BOLETA EN WAITING_SUNAT_PROCESS
             * ============================================================
             */
            if ($sale->annulment_status === 'waiting_sunat_process') {

                /*
                 * La boleta todavía fue emitida hoy.
                 * No intentamos darla de baja todavía.
                 */
                if ($sale->isReceiptFromToday()) {
                    DB::commit();

                    return [
                        'status' => 'waiting',
                        'message' => 'Boleta emitida hoy. Se consultará nuevamente mañana.',
                    ];
                }

                /*
                 * Si ya salió del plazo permitido para baja,
                 * pasamos a requerir Nota de Crédito.
                 */
                if (!$sale->isWithinAnnulmentDeadline()) {

                    $sale->annulment_status = 'requires_credit_note';
                    $sale->annulment_type = 'credit_note';
                    $sale->annulment_reason =
                        'Boleta fuera de plazo para baja. Requiere Nota de Crédito.';

                    if (empty($sale->annulment_requested_by)) {
                        $sale->annulment_requested_by = $systemUserId;
                    }

                    $sale->save();

                    DB::commit();

                    return [
                        'status' => 'requires_credit_note',
                        'message' => 'Boleta fuera de plazo. Requiere Nota de Crédito.',
                    ];
                }

                /*
                 * ========================================================
                 * YA PASÓ EL DÍA DE EMISIÓN
                 * ENVIAR ANULACIÓN A NUBEFACT
                 * ========================================================
                 */
                $motivo = $sale->annulment_reason
                    ?: 'Anulación automática desde cron';

                $sale->annulment_status = 'pending';
                $sale->annulment_type = 'nubefact_baja';

                $sale->annulment_requested_at =
                    $sale->annulment_requested_at ?: now();

                if (empty($sale->annulment_requested_by)) {
                    $sale->annulment_requested_by = $systemUserId;
                }

                $sale->save();

                /*
                 * Enviar solicitud de baja.
                 */
                $result = $this->anularComprobanteNubefact(
                    $sale,
                    $motivo
                );

                /*
                 * Persistir respuesta Nubefact/SUNAT.
                 *
                 * Gracias a los cambios del Trait:
                 * - error SOAP     => pending
                 * - respuesta vacía => pending
                 * - aceptada       => accepted
                 */
                $this->persistNubefactAnnulmentResult(
                    $sale,
                    $result,
                    $motivo
                );

                $sale->refresh();

                /*
                 * ========================================================
                 * REVERSIÓN OPERATIVA INMEDIATA
                 * ========================================================
                 *
                 * Liberamos stock/caja aunque SUNAT todavía esté pendiente.
                 */
                if ($sale->internal_reversal_status !== 'reversed') {

                    $this->reverseSaleInternallyInCommand(
                        $sale,
                        'Anulación enviada a Nubefact desde cron. Reversión interna aplicada.',
                        false
                    );

                    $sale->refresh();
                }

                /*
                 * ========================================================
                 * SUNAT YA ACEPTÓ
                 * ========================================================
                 */
                if ($sale->annulment_status === 'accepted') {

                    $sale->state_annulled = 1;
                    $sale->annulment_status = 'accepted';

                    $sale->annulment_accepted_at =
                        $sale->annulment_accepted_at ?: now();

                    $sale->annulled_by =
                        $sale->annulled_by ?: $systemUserId;

                    $sale->save();

                    DB::commit();

                    return [
                        'status' => 'accepted',
                        'message' => 'Anulación enviada y aceptada por SUNAT. Venta marcada como anulada definitivamente.',
                    ];
                }

                /*
                 * ========================================================
                 * SIGUE PENDIENTE / ERROR TÉCNICO
                 * ========================================================
                 */
                DB::commit();

                return [
                    'status' => 'pending',

                    'message' => $sale->annulment_sunat_message
                        ?: 'Anulación enviada a Nubefact. Pendiente de aceptación SUNAT.',

                    'internal_reversal_status' =>
                        $sale->internal_reversal_status,
                ];
            }

            /*
             * ============================================================
             * ANULACIÓN YA ENVIADA A NUBEFACT
             * annulment_status = pending
             * ============================================================
             */
            $motivo = $sale->annulment_reason
                ?: 'Consulta automática de anulación desde cron';

            /*
             * Consultar estado.
             *
             * Si Nubefact devuelve {}:
             * consultarAnulacionNubefact() lo normaliza.
             *
             * Si devuelve un SOAP error:
             * persistNubefactAnnulmentResult() lo mantiene pending.
             */
            $result = $this->consultarAnulacionNubefact($sale);

            $this->persistNubefactAnnulmentResult(
                $sale,
                $result,
                $motivo
            );

            $sale->refresh();

            /*
             * ============================================================
             * ANULACIÓN ACEPTADA
             * ============================================================
             */
            if ($sale->annulment_status === 'accepted') {

                /*
                 * Contingencia:
                 * si por algún motivo no se había aplicado la reversión,
                 * la aplicamos ahora.
                 */
                if ($sale->internal_reversal_status !== 'reversed') {

                    $this->reverseSaleInternallyInCommand(
                        $sale,
                        'Anulación aceptada por SUNAT/Nubefact desde cron',
                        true
                    );

                    $sale->refresh();
                }

                /*
                 * Aunque la reversión ya hubiera ocurrido anteriormente
                 * con markAsAnnulled=false, ahora que SUNAT confirmó
                 * marcamos definitivamente la venta.
                 */
                $sale->state_annulled = 1;
                $sale->annulment_status = 'accepted';

                $sale->annulment_accepted_at =
                    $sale->annulment_accepted_at ?: now();

                $sale->annulled_by =
                    $sale->annulled_by ?: $systemUserId;

                $sale->save();

                DB::commit();

                return [
                    'status' => 'accepted',
                    'message' => 'Anulación aceptada por SUNAT. Venta marcada como anulada definitivamente.',
                ];
            }

            /*
             * ============================================================
             * RECHAZO DEFINITIVO
             * ============================================================
             *
             * Se conserva para compatibilidad futura.
             * Actualmente un SOAP error o responseCode no se consideran
             * automáticamente como rechazo.
             */
            if ($sale->annulment_status === 'rejected') {

                DB::commit();

                return [
                    'status' => 'rejected',

                    'message' => $sale->annulment_error
                        ?: 'Anulación rechazada por SUNAT.',
                ];
            }

            /*
             * ============================================================
             * PENDIENTE / RESPUESTA INCONCLUSA / ERROR TÉCNICO
             * ============================================================
             *
             * Aquí puede llegar:
             *
             * - SUNAT todavía procesando.
             * - Nubefact con respuesta vacía.
             * - SOAP error temporal.
             * - Respuesta todavía no concluyente.
             *
             * NO se vuelve a revertir stock/caja.
             * Simplemente se intentará nuevamente en otra ejecución.
             */
            DB::commit();

            return [
                'status' => 'pending',

                'message' => $sale->annulment_sunat_message
                    ?: 'La anulación todavía está pendiente de aceptación SUNAT.',

                'internal_reversal_status' =>
                    $sale->internal_reversal_status,
            ];

        } catch (\Throwable $e) {

            DB::rollBack();

            /*
             * Una excepción de comunicación no convierte la venta
             * en rejected.
             *
             * Como la transacción hace rollback, conservará el estado
             * que tenía antes de iniciar este procesamiento.
             */
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    private function applyCreditNoteInternalReversalInCommand(CreditNote $creditNote): void
    {
        if ($creditNote->internal_reversal_status === 'reversed') {
            return;
        }

        $systemUserId = config('services.nubefact.system_user_id');

        $creditNote->loadMissing([
            'sale.details',
            'details.saleDetail',
        ]);

        $sale = $creditNote->sale;

        if (!$sale) {
            throw new \Exception('La Nota de Crédito no tiene venta asociada.');
        }

        if ($creditNote->credit_note_type === 'total') {
            $sale->annulment_type = 'credit_note';
            $sale->credit_note_status = 'total';
            $sale->annulment_reason = 'Venta anulada mediante Nota de Crédito';
            $sale->save();

            $this->reverseSaleInternallyInCommand(
                $sale,
                'Nota de Crédito total aceptada/enviada. Reversión interna aplicada desde cron.',
                false
            );
        }

        if ($creditNote->credit_note_type === 'partial') {
            $this->reverseCreditNoteStockPartiallyInCommand($creditNote);
            $this->registerCreditNoteCashRefundInCommand($creditNote);

            $sale->credit_note_status = 'partial';
            $sale->save();
        }

        $creditNote->internal_reversal_status = 'reversed';
        $creditNote->internal_reversed_at = now();
        $creditNote->internal_reversed_by = $systemUserId;
        $creditNote->save();
    }

    private function reverseCreditNoteStockPartiallyInCommandO(CreditNote $creditNote): void
    {
        if ($creditNote->internal_reversal_status === 'reversed') {
            return;
        }

        $creditNote->loadMissing([
            'details.saleDetail'
        ]);

        $inventoryKeysToSync = [];

        foreach ($creditNote->details as $creditNoteDetail) {
            $saleDetail = $creditNoteDetail->saleDetail;

            if (!$saleDetail) {
                continue;
            }

            $qtyToReturn = (float) $creditNoteDetail->quantity;

            if ($qtyToReturn <= 0) {
                continue;
            }

            $outputDetails = OutputDetail::where('sale_detail_id', $saleDetail->id)
                ->lockForUpdate()
                ->get();

            foreach ($outputDetails as $outputDetail) {
                if ($qtyToReturn <= 0) {
                    break;
                }

                $qtyFromOutput = (float) ($outputDetail->percentage ?? 0);

                if ($qtyFromOutput <= 0) {
                    $qtyFromOutput = (float) $saleDetail->quantity;
                }

                $qtyToApply = min($qtyToReturn, $qtyFromOutput);

                if (!empty($outputDetail->stock_lot_id)) {
                    $lot = StockLot::where('id', $outputDetail->stock_lot_id)
                        ->lockForUpdate()
                        ->first();

                    if ($lot) {
                        $lot->qty_on_hand = (float) $lot->qty_on_hand + $qtyToApply;
                        $lot->save();

                        $inventoryKeysToSync[] = [
                            'stock_item_id' => (int) $lot->stock_item_id,
                            'warehouse_id'  => $lot->warehouse_id,
                            'location_id'   => $lot->location_id,
                        ];
                    }
                }

                if (!empty($outputDetail->item_id) && $qtyToApply >= 1) {
                    $item = Item::where('id', $outputDetail->item_id)
                        ->lockForUpdate()
                        ->first();

                    if ($item) {
                        $item->state_item = 'entered';
                        $item->save();
                    }
                }

                $qtyToReturn -= $qtyToApply;
            }
        }

        $inventoryKeysToSync = collect($inventoryKeysToSync)
            ->unique(function ($row) {
                return implode('|', [
                    $row['stock_item_id'] ?? 'null',
                    $row['warehouse_id'] ?? 'null',
                    $row['location_id'] ?? 'null',
                ]);
            })
            ->values()
            ->all();

        foreach ($inventoryKeysToSync as $key) {
            $this->syncInventoryLevelFromLots(
                (int) $key['stock_item_id'],
                $key['warehouse_id'],
                $key['location_id']
            );
        }
    }

    private function reverseCreditNoteStockPartiallyInCommand(CreditNote $creditNote): void
    {
        if (
            $creditNote->internal_reversal_status === 'reversed'
        ) {
            return;
        }

        $creditNote->loadMissing([
            'details.saleDetail',
            'details.items',
        ]);

        $inventoryKeysToSync = [];

        foreach ($creditNote->details as $creditNoteDetail) {

            $saleDetail = $creditNoteDetail->saleDetail;

            if (!$saleDetail) {
                continue;
            }

            /*
             * ============================================================
             * PRODUCTO ITEMEABLE
             * ============================================================
             *
             * Si existen registros asociados en credit_note_detail_items,
             * devolvemos exactamente los items seleccionados por el usuario.
             */
            if ($creditNoteDetail->items->isNotEmpty()) {

                foreach ($creditNoteDetail->items as $selectedItem) {

                    $outputDetail = OutputDetail::where(
                        'id',
                        $selectedItem->output_detail_id
                    )
                        ->where(
                            'sale_detail_id',
                            $saleDetail->id
                        )
                        ->where(
                            'item_id',
                            $selectedItem->item_id
                        )
                        ->lockForUpdate()
                        ->first();

                    if (!$outputDetail) {
                        throw new \Exception(
                            'No se encontró la salida original del item '
                            . ($selectedItem->item_code
                                ?: '#' . $selectedItem->item_id)
                            . '.'
                        );
                    }

                    $qtyToApply = (float) (
                    $selectedItem->quantity ?: 1
                    );

                    if ($qtyToApply <= 0) {
                        $qtyToApply = 1;
                    }

                    $stockLotId =
                        $selectedItem->stock_lot_id
                            ?: $outputDetail->stock_lot_id;

                    /*
                     * ========================================================
                     * DEVOLVER STOCK AL LOTE ORIGINAL
                     * ========================================================
                     */
                    if (!empty($stockLotId)) {

                        $lot = StockLot::where(
                            'id',
                            $stockLotId
                        )
                            ->lockForUpdate()
                            ->first();

                        if (!$lot) {
                            throw new \Exception(
                                'No se encontró el lote del item '
                                . ($selectedItem->item_code
                                    ?: '#' . $selectedItem->item_id)
                                . '.'
                            );
                        }

                        $lot->qty_on_hand =
                            (float) $lot->qty_on_hand
                            + $qtyToApply;

                        $lot->save();

                        $inventoryKeysToSync[] = [
                            'stock_item_id' =>
                                (int) $lot->stock_item_id,

                            'warehouse_id' =>
                                $lot->warehouse_id,

                            'location_id' =>
                                $lot->location_id,
                        ];
                    }

                    /*
                     * ========================================================
                     * LIBERAR ITEM
                     * ========================================================
                     */
                    $item = Item::where(
                        'id',
                        $selectedItem->item_id
                    )
                        ->lockForUpdate()
                        ->first();

                    if (!$item) {
                        throw new \Exception(
                            'No se encontró el item '
                            . ($selectedItem->item_code
                                ?: '#' . $selectedItem->item_id)
                            . '.'
                        );
                    }

                    $item->state_item = 'entered';
                    $item->save();

                    /*
                     * No eliminamos el OutputDetail.
                     *
                     * credit_note_detail_items conserva la relación
                     * histórica indicando qué item fue devuelto.
                     */
                }

                /*
                 * Ya procesamos exactamente los items de este detalle.
                 * No debemos ejecutar la lógica normal por cantidad.
                 */
                continue;
            }

            /*
             * ============================================================
             * PRODUCTO NORMAL / NO ITEMEABLE
             * ============================================================
             */
            $qtyToReturn =
                (float) $creditNoteDetail->quantity;

            if ($qtyToReturn <= 0) {
                continue;
            }

            $outputDetails = OutputDetail::where(
                'sale_detail_id',
                $saleDetail->id
            )
                ->whereNull('item_id')
                ->lockForUpdate()
                ->get();

            /*
             * Compatibilidad con registros antiguos
             * que puedan tener item_id = 0.
             */
            if ($outputDetails->isEmpty()) {

                $outputDetails = OutputDetail::where(
                    'sale_detail_id',
                    $saleDetail->id
                )
                    ->where(function ($query) {
                        $query->whereNull('item_id')
                            ->orWhere('item_id', 0);
                    })
                    ->lockForUpdate()
                    ->get();
            }

            foreach ($outputDetails as $outputDetail) {

                if ($qtyToReturn <= 0) {
                    break;
                }

                $qtyFromOutput = (float) (
                    $outputDetail->percentage ?? 0
                );

                if ($qtyFromOutput <= 0) {
                    $qtyFromOutput =
                        (float) $saleDetail->quantity;
                }

                $qtyToApply = min(
                    $qtyToReturn,
                    $qtyFromOutput
                );

                if (!empty($outputDetail->stock_lot_id)) {

                    $lot = StockLot::where(
                        'id',
                        $outputDetail->stock_lot_id
                    )
                        ->lockForUpdate()
                        ->first();

                    if ($lot) {

                        $lot->qty_on_hand =
                            (float) $lot->qty_on_hand
                            + $qtyToApply;

                        $lot->save();

                        $inventoryKeysToSync[] = [
                            'stock_item_id' =>
                                (int) $lot->stock_item_id,

                            'warehouse_id' =>
                                $lot->warehouse_id,

                            'location_id' =>
                                $lot->location_id,
                        ];
                    }
                }

                $qtyToReturn -= $qtyToApply;
            }

            /*
             * Si no logramos encontrar suficiente stock original,
             * detenemos la operación para no dejar el inventario
             * parcialmente actualizado.
             */
            if ($qtyToReturn > 0.000001) {
                throw new \Exception(
                    'No se pudo identificar todo el stock que debe '
                    . 'retornar para el detalle #'
                    . $saleDetail->id
                    . '.'
                );
            }
        }

        /*
         * ============================================================
         * SINCRONIZAR INVENTORY LEVELS
         * ============================================================
         */
        $inventoryKeysToSync = collect(
            $inventoryKeysToSync
        )
            ->unique(function ($row) {

                return implode('|', [
                    $row['stock_item_id'] ?? 'null',
                    $row['warehouse_id'] ?? 'null',
                    $row['location_id'] ?? 'null',
                ]);
            })
            ->values()
            ->all();

        foreach ($inventoryKeysToSync as $key) {

            $this->syncInventoryLevelFromLots(
                (int) $key['stock_item_id'],
                $key['warehouse_id'],
                $key['location_id']
            );
        }
    }

    private function registerCreditNoteCashRefundInCommand(CreditNote $creditNote): void
    {
        if ($creditNote->cash_refund_status === 'refunded') {
            return;
        }

        $systemUserId = config('services.nubefact.system_user_id');

        $creditNote->loadMissing([
            'sale',
        ]);

        $sale = $creditNote->sale;

        if (!$sale) {
            throw new \Exception('La Nota de Crédito no tiene venta asociada.');
        }

        $amountToRefund = (float) $creditNote->importe_total;

        if ($amountToRefund <= 0) {
            throw new \Exception('El monto de devolución de la Nota de Crédito debe ser mayor a cero.');
        }

        $saleMovement = CashMovement::where('sale_id', $sale->id)
            ->where('type', 'sale')
            ->orderBy('id', 'desc')
            ->first();

        if (!$saleMovement) {
            throw new \Exception('No se encontró movimiento de caja de la venta para registrar la devolución.');
        }

        $cashMovement = CashMovement::create([
            'cash_register_id'    => $saleMovement->cash_register_id,
            'sale_id'             => $sale->id,
            'type'                => 'expense',
            'amount'              => $amountToRefund,
            'description'         => 'Devolución automática por Nota de Crédito parcial '
                . ($creditNote->serie ? $creditNote->serie . '-' . $creditNote->numero : '')
                . ' de venta #' . $sale->id,
            'regularize'          => $saleMovement->regularize,
            'cash_box_subtype_id' => $saleMovement->cash_box_subtype_id,
        ]);

        $cashRegister = CashRegister::find($saleMovement->cash_register_id);

        if ($cashRegister) {
            $cashRegister->current_balance -= $amountToRefund;
            $cashRegister->total_expenses += $amountToRefund;
            $cashRegister->save();
        }

        $creditNote->cash_refund_status = 'refunded';
        $creditNote->cash_refund_at = now();
        $creditNote->cash_refund_by = $systemUserId;
        $creditNote->cash_movement_id = $cashMovement->id;
        $creditNote->save();
    }

    private function syncInventoryLevelFromLots(
        int $stockItemId,
        $warehouseId,
        $locationId
    ): void
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
                'qty_on_hand'  => 0,
                'qty_reserved' => 0,
                'min_alert'    => 0,
                'max_alert'    => 0,
                'average_cost' => 0,
                'last_cost'    => 0,
            ]
        );

        $inventoryLevel->qty_on_hand  = $qtyOnHand;
        $inventoryLevel->qty_reserved = $qtyReserved;
        $inventoryLevel->save();
    }
}
