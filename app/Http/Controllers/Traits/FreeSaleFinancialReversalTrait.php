<?php

namespace App\Http\Controllers\Traits;

use App\CashMovement;
use App\CashRegister;
use App\Sale;
use App\SalePartialPayment;
use Illuminate\Support\Facades\Auth;

trait FreeSaleFinancialReversalTrait
{
    private function reverseFreeSaleFinancially(Sale $sale,string $reason) {
        if (
            $sale->internal_reversal_status ===
            'reversed'
        ) {
            return;
        }

        $movements = CashMovement::query()
            ->with([
                'cashRegister.cashBox',
                'regularizedChildren.cashRegister.cashBox',
            ])
            ->where('sale_id', $sale->id)
            ->lockForUpdate()
            ->get();

        $partialPayments = SalePartialPayment::query()
            ->with([
                'cashMovement.cashRegister.cashBox',
            ])
            ->where('sale_id', $sale->id)
            ->where('state', 1)
            ->lockForUpdate()
            ->get();

        /*
         * Incorporar movimientos de pagos parciales que,
         * por algún dato antiguo, no tengan sale_id.
         */
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
         * Incorporar movimientos hijos de regularizaciones
         * bancarias diferidas.
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
         * Los pagos parciales mantienen su historial,
         * pero dejan de estar activos.
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

    private function reverseFreeSaleCashMovement(CashMovement $originalMovement,Sale $sale,string $reason) {
        /*
         * No revertir movimientos que ya son compensaciones.
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
                'El movimiento #' .
                $originalMovement->id .
                ' no tiene una sesión de caja asociada.'
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
                'No se encontró la sesión de caja del movimiento #' .
                $originalMovement->id . '.'
            );
        }

        if (
        in_array(
            $originalMovement->type,
            ['sale', 'income'],
            true
        )
        ) {
            $reversalType = 'expense';

        } elseif ($originalMovement->type === 'expense') {
            $reversalType = 'income';

        } else {
            throw new \Exception(
                'El movimiento #' .
                $originalMovement->id .
                ' tiene un tipo no soportado.'
            );
        }

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
            'cash_register_id' => $cashRegister->id,
            'type' => $reversalType,
            'amount' => $movementAmount,

            'description' =>
                'Reversión por Nota de Crédito total de venta libre #' .
                $sale->id,

            'observation' =>
                $reason .
                '. Movimiento original #' .
                $originalMovement->id,

            'regularize' =>
                (bool) $originalMovement->regularize,

            'amount_regularize' =>
                $originalMovement->amount_regularize,

            'commission' =>
                $originalMovement->commission,

            'sale_id' => $sale->id,

            'cash_box_subtype_id' =>
                $originalMovement->cash_box_subtype_id,

            'cash_movement_origin_id' =>
                $originalMovement->id,

            'cash_movement_regularize_id' => null,
            'arqueo' => false,
        ]);

        /*
         * Un movimiento diferido regularize=0 no afectó saldo,
         * por lo que su reversión tampoco debe modificarlo.
         */
        if ((float) $impactAmount <= 0) {
            return;
        }

        if ($reversalType === 'expense') {
            $cashRegister->current_balance = bcsub(
                (string) $cashRegister->current_balance,
                $impactAmount,
                2
            );

            $cashRegister->total_expenses = bcadd(
                (string) $cashRegister->total_expenses,
                $impactAmount,
                2
            );
        } else {
            $cashRegister->current_balance = bcadd(
                (string) $cashRegister->current_balance,
                $impactAmount,
                2
            );
        }

        $cashRegister->save();
    }
}