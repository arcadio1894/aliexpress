<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    protected $appends = ['time_delivery','have_details', 'subtotal_utility', 'subtotal_letter', 'subtotal_rent', 'subtotal_rent_pdf', 'subtotal_utility_edit', 'subtotal_letter_edit', 'subtotal_rent_edit', 'total_quote', 'total_equipments'/*, 'total_services'*/];

    protected $fillable = [
        'code',
        'description_quote',
        'description',
        'date_quote',
        'way_to_pay',
        'delivery_time',
        'customer_id',
        'date_validate',
        'total',
        'state',
        'utility',
        'letter',
        'rent',
        'code_customer',
        'raise_status',
        'currency_invoice',
        'currency_compra',
        'currency_venta',
        'total_soles',
        'order_execution',
        'state_active',
        'contact_id',
        'payment_deadline_id',
        'reason_separate',
        'vb_finances',
        'date_vb_finances',
        'vb_operations',
        'date_vb_operations',
        'proforma_id',
        'observations',
        'state_decimals',
        'descuento',
        'discount_type',
        'discount_input_mode',
        'discount_input_value',
        'gravada',
        'igv_total',
        'total_importe',
        'renewed_from_quote_id',
    ];

    protected $dates = ['date_quote', 'created_at', 'date_vb_finances', 'date_vb_operations', 'date_validate'];
   /* public function getEstadoAttribute()
    {
        if ( $this->state === 'created' ) {
            if ( $this->send_state == 1 || $this->send_state == true )
            {
                return 'send';
            } else {
                return 'created';
            }
        }
        if ($this->state_active === 'close'){
            return 'close';
        } else {
            if ($this->state === 'confirmed' && $this->raise_status === 1){
                if ( $this->vb_finances == 1 && $this->vb_operations == null )
                {
                    return 'VB_finance';
                } else {
                    if ( $this->vb_finances == 1 && $this->vb_operations == 1 )
                    {
                        return 'VB_operation';
                    } else {
                        if ( $this->vb_finances == null && $this->vb_operations == null )
                        {
                            return 'raise';
                        }
                    }
                }
            }
            if ($this->state === 'confirmed' && $this->raise_status === 0){
                return 'confirm';
            }
            if ($this->state === 'canceled'){
                return 'canceled';
            }
        }

        return "";
    }*/

    public function getTotalServicesAttribute()
    {
        $totalService = 0;

        foreach ( $this->equipments as $equipment )
        {
            foreach ($equipment->workforces as $workforce)
            {
                $totalService = $totalService + $workforce->total;
            }
        }

        return $totalService;
    }

    public function getTimeDeliveryAttribute()
    {
        if ($this->delivery_time === null) {
            return ""; // Devuelve null si el campo es null
        }

        $pattern = '/^(\d+(?:\.\d+)?)\s+(d[ií]́?as)$/iu';

        // Verificar si el campo sigue el formato esperado número + espacio + días (independientemente de las mayúsculas/minúsculas y acentos)
        if (preg_match($pattern, $this->delivery_time, $matches)) {
            return (float)$matches[1]; // Devuelve solo el número
        } else {
            return $this->delivery_time; // Si no sigue el formato esperado, devuelve el dato completo
        }
    }

    public function getHaveImagesAttribute()
    {
        $have_images = true;
        $images = ImagesQuote::where('quote_id', $this->id)->get();

        if ( isset($images) )
        {
            $have_images = false;
        }


        return $have_images;
    }

    public function getHaveDetailsAttribute()
    {
        $have_details = false;
        $equipos = Equipment::where('quote_id', $this->id)->get();
        foreach ( $equipos as $equipment )
        {
            if ( $equipment->detail != "" || $equipment->detail != null )
            {
                $have_details = true;
            }
        }

        return $have_details;
    }

    public function getTotalEquipmentsAttribute()
    {
        $totalFinal2 = 0;
        $equipos = Equipment::where('quote_id', $this->id)->get();
        foreach ( $equipos as $equipment )
        {
            $totalFinal2 = $totalFinal2 + $equipment->total;
        }

        return $totalFinal2;
    }
    
    public function getTotalQuoteAttribute()
    {
        $nuevo = true;
        $equipos = Equipment::where('quote_id', $this->id)->get();
        foreach ( $equipos as $equipment )
        {
            if ( $equipment->utility == 0 && $equipment->letter == 0 && $equipment->rent == 0 )
            {
                $nuevo = false;
            } else {
                $nuevo = true;
            }
        }

        $totalFinal = 0;
        if ( !$nuevo )
        {
            if ( $this->total_soles != 0 )
            {
                $subtotal1 = $this->total_soles * (($this->utility/100)+1);
                $subtotal2 = $subtotal1 * (($this->letter/100)+1);
                $subtotal3 = $subtotal2 * (($this->rent/100)+1);
                $totalFinal = $subtotal3;
            } else {
                $subtotal1 = $this->total * (($this->utility/100)+1);
                $subtotal2 = $subtotal1 * (($this->letter/100)+1);
                $subtotal3 = $subtotal2 * (($this->rent/100)+1);
                $totalFinal =  $subtotal3;
            }
        } else {
            if ( $this->total_soles != 0 )
            {
                $totalFinal = $this->total_soles;
            } else {
                $totalFinal = $this->total;
            }
        }

        return $totalFinal;
    }

    public function getSubtotalUtilityAttribute()
    {
        if ( $this->total_soles != 0 )
        {
            $subtotal1 = $this->total_soles * (($this->utility/100)+1);
            return number_format($subtotal1, 2);
        } else {
            $subtotal1 = $this->total * (($this->utility/100)+1);
            return number_format($subtotal1, 2);
        }

    }

    public function getSubtotalUtilityEditAttribute()
    {
        $subtotal1 = $this->total * (($this->utility/100)+1);
        return number_format($subtotal1, 2);

    }

    public function getSubtotalLetterAttribute()
    {
        if ( $this->total_soles != 0 )
        {
            $subtotal1 = $this->total_soles * (($this->utility/100)+1);
            $subtotal2 = $subtotal1 * (($this->letter/100)+1);
            return number_format($subtotal2, 2);
        } else {
            $subtotal1 = $this->total * (($this->utility/100)+1);
            $subtotal2 = $subtotal1 * (($this->letter/100)+1);
            return number_format($subtotal2, 2);
        }

    }

    public function getSubtotalLetterEditAttribute()
    {
        $subtotal1 = $this->total * (($this->utility/100)+1);
        $subtotal2 = $subtotal1 * (($this->letter/100)+1);
        return number_format($subtotal2, 2);
    }


    public function getSubtotalRentAttribute()
    {
        if ( $this->total_soles != 0 )
        {
            $subtotal1 = $this->total_soles * (($this->utility/100)+1);
            $subtotal2 = $subtotal1 * (($this->letter/100)+1);
            $subtotal3 = $subtotal2 * (($this->rent/100)+1);
            return number_format($subtotal3, 0);
        } else {
            $subtotal1 = $this->total * (($this->utility/100)+1);
            $subtotal2 = $subtotal1 * (($this->letter/100)+1);
            $subtotal3 = $subtotal2 * (($this->rent/100)+1);
            return number_format($subtotal3, 0);
        }

    }

    public function getSubtotalRentPdfAttribute()
    {
        if ( $this->total_soles != 0 )
        {
            $subtotal1 = $this->total_soles * (($this->utility/100)+1);
            $subtotal2 = $subtotal1 * (($this->letter/100)+1);
            $subtotal3 = $subtotal2 * (($this->rent/100)+1);
            return $subtotal3;
        } else {
            $subtotal1 = $this->total * (($this->utility/100)+1);
            $subtotal2 = $subtotal1 * (($this->letter/100)+1);
            $subtotal3 = $subtotal2 * (($this->rent/100)+1);
            return $subtotal3;
        }

    }

    public function getSubtotalRentEditAttribute()
    {
        $subtotal1 = $this->total * (($this->utility/100)+1);
        $subtotal2 = $subtotal1 * (($this->letter/100)+1);
        $subtotal3 = $subtotal2 * (($this->rent/100)+1);
        return number_format($subtotal3, 0);
    }

    public function customer()
    {
        return $this->belongsTo('App\Customer');
    }

    public function contact()
    {
        return $this->belongsTo('App\ContactName', 'contact_id');
    }

    public function equipments()
    {
        return $this->hasMany('App\Equipment')->orderBy('description','asc');
    }

    public function users()
    {
        return $this->hasMany('App\QuoteUser');
    }

    public function deadline()
    {
        return $this->belongsTo('App\PaymentDeadline', 'payment_deadline_id');
    }

    public function outputs()
    {
        return $this->hasMany('App\Output', 'execution_order', 'order_execution');
    }

    /*public function sales()
    {
        return $this->hasMany('App\Sale');
    }*/
    public function sales()
    {
        return $this->hasMany(Sale::class, 'quote_id');
    }

    public function stockLotReservations()
    {
        return $this->hasMany(QuoteStockLot::class, 'quote_id');
    }

    public function originalQuote()
    {
        return $this->belongsTo(
            Quote::class,
            'renewed_from_quote_id'
        );
    }

    public function renewedQuote()
    {
        return $this->hasOne(
            Quote::class,
            'renewed_from_quote_id'
        );
    }
}
