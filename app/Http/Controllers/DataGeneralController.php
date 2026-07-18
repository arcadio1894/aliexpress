<?php

namespace App\Http\Controllers;

use App\DataGeneral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DataGeneralController extends Controller
{
    private $defaultVariables = [
        'typeBoleta',
        'daysOfWeek',
        'empresa',
        'ruc',
        'horasXDia',
        'diasMes',
        'horasSemanales',
        'tipoDomumento',
        'daysToExpireMin',
        'start_rotation_baja',
        'end_rotation_baja',
        'start_rotation_media',
        'end_rotation_media',
        'start_rotation_alta',
        'type_current',
        'address',
        'idWarehouseTienda',
        'store_material_min',
        'send_notification_store_pop_up',
        'send_notification_store_campana',
        'send_notification_store_email',
        'send_notification_store_telegram',
        'telefono',
        'email',
        'web',
        'logotipo',
        'logotipo_bn',
        'versiculo',
        'cita_biblica',
        'title_cuenta_1',
        'nro_cuenta_1',
        'cci_cuenta_1',
        'img_cuenta_1',
        'owner_cuenta_1',
        'title_cuenta_2',
        'nro_cuenta_2',
        'cci_cuenta_2',
        'img_cuenta_2',
        'owner_cuenta_2',
        'show_alert_stock_minimos',
        'pagos_parciales',
        'whatsapp',
        'show_prices_catalog',
        'show_presentations',
        'description_footer',

        'facebook',
        'twitter',
        'youtube',
        'instagram',
        'pinterest',
        'tiktok',

        'password_reset',
        'igv_venta'
    ];

    public function index()
    {
        $user = Auth::user();
        $permissions = $user->getPermissionsViaRoles()->pluck('name')->toArray();
        return view('dataGeneral.index', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:data_generals,name',
            'valueText' => 'nullable|required_without:valueNumber',
            'valueNumber' => 'nullable|required_without:valueText|numeric',
        ]);

        DB::beginTransaction();

        try {
            $data = DataGeneral::create([
                'name' => $request->name,
                'valueText' => $request->valueText,
                'valueNumber' => $request->valueNumber,
                'module' => null,
                'description' => $request->description,
            ]);

            DB::commit();

            return response()->json(['success' => true, 'data' => $data]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el dato.',
                'error' => $e->getMessage()
            ], 422);
        }
    }


    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $data = DataGeneral::findOrFail($id);

            $request->validate([
                'name' => 'required|string|unique:data_generals,name,' . $data->id,
                'valueText' => 'nullable|required_without:valueNumber',
                'valueNumber' => 'nullable|required_without:valueText|numeric',
            ]);

            $updateData = [
                'name' => $request->name,
                'valueText' => $request->valueText,
                'valueNumber' => $request->valueNumber,
                'module' => null,
                'description' => $request->description,
            ];

            // Limpieza del campo no usado
            if ($request->filled('valueText')) {
                $updateData['valueNumber'] = null;
            } elseif ($request->filled('valueNumber')) {
                $updateData['valueText'] = null;
            }

            $data->update($updateData);

            DB::commit();

            return response()->json(['success' => true, 'data' => $data]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el dato.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    public function getDataGeneral(Request $request, $pageNumber = 1)
    {
        $perPage = 10;
        $name = $request->input('name');
        $query = DataGeneral::query()->orderBy('name');

        // Puedes agregar filtros opcionales más adelante
        if ($name != "") {
            $query->where('name', 'LIKE', '%'.$name.'%');
        }

        $totalFilteredRecords = $query->count();
        $totalPages = ceil($totalFilteredRecords / $perPage);
        $startRecord = ($pageNumber - 1) * $perPage + 1;
        $endRecord = min($totalFilteredRecords, $pageNumber * $perPage);

        $dataGenerals = $query->skip(($pageNumber - 1) * $perPage)
            ->take($perPage)
            ->get();

        // ➕ Verificar si hay nuevas variables no existentes en BD
        $existingNames = DataGeneral::pluck('name')->toArray();
        $newCreated = [];

        foreach ($this->defaultVariables as $varName) {
            if (!in_array($varName, $existingNames)) {
                DataGeneral::create([
                    'name' => $varName,
                    'valueText' => null,
                    'valueNumber' => null,
                    'module' => null,
                    'description' => null
                ]);
                $newCreated[] = $varName;
            }
        }

        // Si se creó alguna, las recargamos para incluirlas
        if (count($newCreated) > 0) {
            $dataGenerals = DataGeneral::orderBy('name')
                ->skip(($pageNumber - 1) * $perPage)
                ->take($perPage)
                ->get();
        }

        $array = [];

        foreach ( $dataGenerals as $dataGeneral )
        {
            array_push($array, [
                "id" => $dataGeneral->id,
                "name" => $dataGeneral->name,
                "valueText" => $dataGeneral->valueText,
                "valueNumber" => $dataGeneral->valueNumber,
                "description" => $dataGeneral->description,
            ]);
        }

        $pagination = [
            'currentPage' => (int) $pageNumber,
            'totalPages' => (int) $totalPages,
            'startRecord' => $startRecord,
            'endRecord' => $endRecord,
            'totalRecords' => $totalFilteredRecords,
            'totalFilteredRecords' => $totalFilteredRecords,
        ];

        return [
            'data' => $array,
            'pagination' => $pagination,
            'newlyCreated' => $newCreated, // Esto servirá para el jquery-confirm
        ];
    }
}
