<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CreditNote extends Model
{
    protected $fillable = [
        'sale_id',
        'type_document',
        'serie',
        'numero',
        'reason_code',
        'reason_description',
        'op_gravada',
        'op_exonerada',
        'op_inafecta',
        'igv',
        'total_descuentos',
        'importe_total',
        'credit_note_type',
        'status',
        'sunat_status',
        'sunat_message',
        'sunat_ticket',
        'nubefact_key',
        'generation_key',
        'sunat_responsecode',
        'nubefact_response',
        'pdf_path',
        'xml_path',
        'cdr_path',
        'pdf_url',
        'xml_url',
        'cdr_url',
        'created_by',
        'accepted_at',

        'internal_reversal_status',
        'internal_reversed_at',
        'internal_reversed_by',
        'cash_refund_status',
        'cash_refund_at',
        'cash_refund_by',
        'cash_movement_id',
    ];

    protected $dates = [
        'accepted_at',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function details()
    {
        return $this->hasMany(CreditNoteDetail::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function internalReversedBy()
    {
        return $this->belongsTo(User::class, 'internal_reversed_by');
    }

    public function cashRefundBy()
    {
        return $this->belongsTo(User::class, 'cash_refund_by');
    }

    public function cashMovement()
    {
        return $this->belongsTo(CashMovement::class, 'cash_movement_id');
    }
}
