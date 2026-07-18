<?php

namespace App\Http\Controllers;

use App\CashBox;
use App\CashBoxSubtype;
use App\Customer;
use App\DataGeneral;
use Illuminate\Http\Request;

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
    public function store(Request $request)
    {
        // Se implementará después de definir todos los campos de la vista.
    }

    /**
     * Anular una venta libre.
     */
    public function annul($saleId)
    {
        // Se implementará en una etapa posterior.
    }
}
