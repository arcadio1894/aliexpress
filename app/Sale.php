<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'date_sale',
        'serie',
        'worker_id',
        'caja',
        'currency',
        'op_exonerada',
        'op_inafecta',
        'op_gravada',
        'igv',
        'total_descuentos',
        'importe_total',
        'vuelto',
        'tipo_pago_id',
        'state_annulled',
        'dispatch_status',

        'annulment_status',
        'annulment_type',
        'annulment_reason',
        'annulment_requested_at',
        'annulment_accepted_at',
        'annulment_error',
        'annulment_ticket',
        'annulment_response',
        'annulment_key',
        'annulment_sunat_status',
        'annulment_sunat_message',
        'annulment_sunat_responsecode',
        'annulment_pdf_path',
        'annulment_xml_path',
        'annulment_cdr_path',
        'annulment_pdf_url',
        'annulment_xml_url',
        'annulment_cdr_url',

        'annulment_requested_by',
        'annulled_by',

        'credit_note_status',

        'internal_reversal_status',
        'internal_reversed_at',
        'internal_reversed_by',

        'quote_id',

        'free_sale',
        'customer_id',
        'observation',

        'numero',
        'xml_path',
        'cdr_path',
        'pdf_path',
        'type_document',
        'pagos_parciales_venta',
        'sunat_ticket',
        'sunat_status',
        'sunat_message',
        'fecha_emision',
        'nombre_cliente',
        'tipo_documento_cliente', // '1' = DNI, '6' = RUC
        'numero_documento_cliente',
        'direccion_cliente',
        'email_cliente',
        'serie_sunat'
    ];

    protected $dates = ['date_sale'];

    protected $casts = [
        'free_sale' => 'boolean',
    ];

    public function worker()
    {
        return $this->belongsTo('App\Worker');
    }

    public function quote()
    {
        return $this->belongsTo('App\Quote');
    }

    public function tipoPago()
    {
        return $this->belongsTo('App\TipoPago');
    }

    public function details()
    {
        return $this->hasMany('App\SaleDetail');
    }

    public function getFormattedSaleDateAttribute()
    {
        return Carbon::parse($this->date_sale)->isoFormat('DD/MM/YYYY [a las] h:mm A');
    }

    public function cashMovements()
    {
        return $this->hasMany(CashMovement::class, 'sale_id');
    }

    public function partialPayments()
    {
        return $this->hasMany(SalePartialPayment::class);
    }

    public function creditNotes()
    {
        return $this->hasMany(CreditNote::class);
    }

    public function getDataTotalsAttribute()
    {
        $sale = Sale::find($this->id);

        $items = $sale->details->map(function ($item) {
            $valor_unitario = round($item->price / 1.18, 6);
            $subtotal = $valor_unitario * $item->quantity; // 20.25
            $igv = ($item->price * $item->quantity) - $subtotal; // 23.90 - 20.25 = 3.65

            return [
                "unidad_de_medida" => "NIU",
                "codigo" => $item->material_id,
                "descripcion" => $item->material->full_name,
                "cantidad" => $item->quantity,
                "valor_unitario" => round($valor_unitario, 6),
                "precio_unitario" => round($item->price, 6),
                "subtotal" => round($subtotal, 6), // 34.66
                "tipo_de_igv" => "1", // gravado
                "igv" => round($igv, 2), // 6.24
                "total" => round($item->price*$item->quantity, 6) // 40.90
            ];
        })->toArray();


        $total_gravada = array_sum(array_column($items, 'subtotal'));

        return [
                "total_gravada" => number_format($total_gravada - $this->total_descuentos, 2, '.', ''),
                "total_igv" => number_format(($total_gravada - $this->total_descuentos) * 0.18, 2, '.', ''),
                "total" => number_format(($total_gravada - $this->total_descuentos) * 1.18, 2, '.', ''),
                "total_a_pagar" => number_format(($total_gravada - $this->total_descuentos) * 1.18, 2, '.', ''),
            ] + ($this->total_descuentos > 0 ? [
                "descuento_global" => number_format($this->total_descuentos, 2, '.', ''),
                "total_descuento" => number_format($this->total_descuentos, 2, '.', '')
            ] : []) + [
                "items" => $items,
            ];

    }

    public function hasElectronicDocument(): bool
    {
        return in_array($this->type_document, ['01', '03'], true)
            && !empty($this->serie_sunat)
            && !empty($this->numero)
            && $this->sunat_status !== 'Error';
    }

    public function hasSunatError(): bool
    {
        return in_array($this->type_document, ['01', '03'], true)
            && $this->sunat_status === 'Error';
    }

    public function hasNoElectronicDocument(): bool
    {
        return empty($this->type_document);
    }

    public function isInvoice(): bool
    {
        return $this->type_document === '01';
    }

    public function isReceipt(): bool
    {
        return $this->type_document === '03';
    }

    public function isReceiptFromToday(): bool
    {
        if (!$this->isReceipt()) {
            return false;
        }

        $emissionDate = $this->fecha_emision ?: $this->date_sale;

        if (!$emissionDate) {
            return false;
        }

        return Carbon::parse($emissionDate)->isToday();
    }

    public function canAttemptNubefactAnnulment(): bool
    {
        if (!$this->hasElectronicDocument()) {
            return false;
        }

        if ($this->isReceiptFromToday()) {
            return false;
        }

        return true;
    }

    public function isWithinAnnulmentDeadline(int $days = 7): bool
    {
        $emissionDate = $this->fecha_emision ?: $this->date_sale;

        if (!$emissionDate) {
            return false;
        }

        return Carbon::parse($emissionDate)
                ->startOfDay()
                ->diffInDays(now()->startOfDay()) <= $days;
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
