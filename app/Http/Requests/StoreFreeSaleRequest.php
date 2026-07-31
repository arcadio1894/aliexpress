<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFreeSaleRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()
            && $this->user()->can('createFreeSale_puntoVenta');
    }

    public function rules()
    {
        return [
            'date_sale' => ['required', 'date'],
            'currency' => ['required', 'in:PEN,USD'],

            'client_mode' => [
                'required',
                'in:registered,manual',
            ],

            'customer_id' => [
                'nullable',
                'required_if:client_mode,registered',
                'integer',
                'exists:customers,id',
            ],

            'nombre_cliente' => [
                'nullable',
                'string',
                'max:200',
            ],

            'tipo_documento_cliente' => [
                'nullable',
                'in:1,4,6,7',
            ],

            'numero_documento_cliente' => [
                'nullable',
                'string',
                'max:20',
            ],

            'direccion_cliente' => [
                'nullable',
                'string',
                'max:250',
            ],

            'email_cliente' => [
                'nullable',
                'email',
                'max:150',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.description' => [
                'required',
                'string',
                'max:250',
            ],

            'items.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
            ],

            /*
             * Precio final, con IGV incluido.
             */
            'items.*.unit_price' => [
                'required',
                'numeric',
                'gte:0',
            ],

            'tax_percentage' => [
                'required',
                'numeric',
                'in:0,18',
            ],

            'pagos_parciales_venta' => [
                'nullable',
                'in:s',
            ],

            'cash_box_id' => [
                'nullable',
                'integer',
                'exists:cash_boxes,id',
            ],

            'cash_box_subtype_id' => [
                'nullable',
                'integer',
                'exists:cash_box_subtypes,id',
            ],

            'amount_received' => [
                'nullable',
                'numeric',
                'gte:0',
            ],

            'change_cash_box_id' => [
                'nullable',
                'integer',
                'exists:cash_boxes,id',
            ],

            'change_cash_box_subtype_id' => [
                'nullable',
                'integer',
                'exists:cash_box_subtypes,id',
            ],

            'observations' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages()
    {
        return [
            'customer_id.required_if' =>
                'Seleccione un cliente registrado.',

            'items.required' =>
                'Debe registrar por lo menos un concepto.',

            'items.min' =>
                'Debe registrar por lo menos un concepto.',

            'items.*.description.required' =>
                'Todos los conceptos deben tener una descripción.',

            'items.*.quantity.gt' =>
                'La cantidad debe ser mayor que cero.',

            'items.*.unit_price.gte' =>
                'El precio no puede ser negativo.',
        ];
    }
}
