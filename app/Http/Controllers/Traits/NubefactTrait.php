<?php

namespace App\Http\Controllers\Traits;

use App\CreditNote;
use App\Sale;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait NubefactTrait
{
    private function buildNubefactDataO(Sale $order): array
    {
        $order->loadMissing([
            'details.material',
            'details.stockItem']);

        $isFactura = $order->type_document === '01';
        $serie = $isFactura ? 'FFF1' : 'BBB1';
        /*$serie = $isFactura
            ? config('services.nubefact.serie_factura', 'FFF1')
            : config('services.nubefact.serie_boleta', 'BBB1');*/
        $tipoCliente = $order->tipo_documento_cliente ?: ($isFactura ? '6' : '1');

        $items = $order->details->map(function ($item) {

            // 1) Determinar si es servicio (workforce) o producto
            $isService = empty($item->material_id); // null => servicio

            $qty = (string) ((float)$item->quantity);
            if ((float)$qty <= 0) {
                throw new \Exception("Cantidad inválida en detalle {$item->id}");
            }

            $qty = (string) ( ($item->material_presentation_id == null) ? (float)$item->quantity: (float)$item->packs);

            // ✅ TOTAL DE LÍNEA (con IGV) desde tu BD, no recalcular con price*qty
            //$totalLine = number_format((float)$item->total, 2, '.', ''); // "115.00"
            $totalLine = $item->total;

            // ✅ precio_unitario = total / qty (6 decimales)
            //$precioUnitario = bcdiv($totalLine, $qty, 10); // "38.333333"
            $precioUnitario = $item->price;

            // ✅ valor_unitario = precio_unitario / 1.18
            //$valorUnitario  = bcdiv($precioUnitario, '1.18', 10);
            $valorUnitario = $item->valor_unitario;

            // ✅ subtotal = valor_unitario * qty (2 decimales para dinero)
            $subtotal = bcmul($valorUnitario, $qty, 10); // "97.46" (ej)

            // ✅ igv = total - subtotal (2 decimales)
            $igv = bcsub($totalLine, $subtotal, 10);

            //$present = (string) ( ($item->material_presentation_id == null) ? round($item->quantity): $item->units_per_pack.'und');

            if ($isService) {
                $descripcion = strtoupper($item->description) ?: 'Servicio';
                $present = 'srv';
            } else {
                $present = (string) (
                $item->material_presentation_id == null
                    ? round($item->quantity)
                    : $item->units_per_pack . 'und'
                );

                $descripcion = "(".$present.") ";

                if (!empty($item->stock_item_id) && $item->stockItem) {
                    $descripcion .= $item->stockItem->display_name;
                } elseif ($item->material) {
                    $descripcion .= $item->material->full_name;
                } else {
                    $descripcion .= 'Material ' . $item->material_id;
                }

                /*
 * Agregar códigos de ítems físicos vendidos.
 * Se usa item_snapshot para no depender de OutputDetail.
 */
                $itemSnapshot = $item->item_snapshot ?? [];

                if (is_string($itemSnapshot)) {
                    $decodedSnapshot = json_decode($itemSnapshot, true);
                    $itemSnapshot = is_array($decodedSnapshot) ? $decodedSnapshot : [];
                }

                if (!is_array($itemSnapshot)) {
                    $itemSnapshot = [];
                }

                $itemCodes = collect($itemSnapshot)
                    ->map(function ($snapshotItem) {
                        if (is_array($snapshotItem)) {
                            return $snapshotItem['code'] ?? null;
                        }

                        if (is_object($snapshotItem)) {
                            return $snapshotItem->code ?? null;
                        }

                        return null;
                    })
                    ->filter()
                    ->values()
                    ->toArray();

                if (!empty($itemCodes)) {
                    $descripcion .= ' | Items: ' . implode(', ', $itemCodes);
                }
            }

            return [
                "unidad_de_medida" => "NIU",
                "codigo" => "",
                "descripcion" => $descripcion,
                "cantidad" => (float) $qty,

                // Nubefact usa estos para recalcular/mostrar:
                "valor_unitario" => (float) $valorUnitario,
                "precio_unitario" => (float) $precioUnitario,

                // Totales de línea consistentes:
                "subtotal" => (float) $subtotal,
                "tipo_de_igv" => "1",
                "igv" => (float) $igv,
                "total" => (float) $totalLine,
            ];
        })->toArray();

        // Total gravada = suma de subtotales (base imponible)
        // Suma con precisión (usa "subtotal" con 3+ decimales si lo tienes, o calcula raw)
        //$totalGravadaRaw = '0.000';

        /*foreach ($items as $it) {
            $subRaw = number_format((float)$it['valor_unitario'] * (float)$it['cantidad'], 10, '.', ''); // 3 decimales
            $totalGravadaRaw = bcadd($totalGravadaRaw, $subRaw, 10);
        }*/

        // base = total_gravada - descuento (ambos a 2 decimales)
        $discount = $order->total_descuentos;
        /*dump("discount");
        dump($discount);*/
        $total_gravada = $order->op_gravada;
        /*dump("total_gravada");
        dump($total_gravada);*/

        //$base = bcsub($total_gravada, 0, 10);
        $base = $total_gravada;
        /*dump("base");
        dump($base);*/

        $total_igv = bcmul($base, '0.18', 10);
        /*dump("total_igv");
        dump($total_igv);*/

        $total = bcadd($base, $total_igv, 10);

        /*dump("total");
        dump($total);*/

        return [
                "operacion" => "generar_comprobante",
                "tipo_de_comprobante" => $isFactura ? "1" : "2",
                "serie" => $serie,
                "numero" => "",
                "codigo_unico" => (string) Str::uuid(),
                "sunat_transaction" => "1",
                "cliente_tipo_de_documento" => $tipoCliente,
                "cliente_numero_de_documento" => $order->numero_documento_cliente,
                "cliente_denominacion" => $order->nombre_cliente,
                "cliente_direccion" => $order->direccion_cliente ?: "",
                "cliente_email" => $order->email_cliente ?: "",
                "fecha_de_emision" => now()->format('d-m-Y'),
                "moneda" => "1",
                "porcentaje_de_igv" => 18.00,
                "total_gravada" => $base,
                "total_igv" => $total_igv,
                "total" => $total,
                "total_a_pagar" => $total,
            ]
            + ($discount > 0 ? [
                "descuento_global" => number_format($discount, 10, '.', ''),
                "total_descuento" => number_format($discount, 10, '.', ''),
            ] : [])
            + [
                "items" => $items,
            ];
    }

    private function buildNubefactData(Sale $order): array
    {
        $order->loadMissing([
            'details.material',
            'details.stockItem',
        ]);

        /*
        |--------------------------------------------------------------------------
        | IDENTIFICAR EL TIPO DE VENTA
        |--------------------------------------------------------------------------
        |
        | Las ventas normales y provenientes de cotizaciones conservan
        | exactamente el comportamiento que ya tenían.
        |
        | La lógica especial solo se aplica cuando free_sale = 1.
        */
        $isFreeSale = (bool) $order->free_sale;

        $isFactura = $order->type_document === '01';

        /*
         * Se conservan las series utilizadas actualmente.
         */
        $serie = $isFactura
            ? 'FFF1'
            : 'BBB1';

        $tipoCliente = $order->tipo_documento_cliente
            ?: ($isFactura ? '6' : '1');

        /*
        |--------------------------------------------------------------------------
        | CONSTRUIR DETALLES
        |--------------------------------------------------------------------------
        */
        $items = $order->details
            ->map(function ($item) use ($isFreeSale) {

                /*
                 * En ventas normales, un material_id vacío continúa
                 * identificándose como servicio, igual que antes.
                 */
                $isService = empty($item->material_id);

                /*
                |--------------------------------------------------------------------------
                | CANTIDAD
                |--------------------------------------------------------------------------
                |
                | Se conserva la lógica existente:
                |
                | - Sin presentación: quantity.
                | - Con presentación: packs.
                */
                $qty = (string) (
                $item->material_presentation_id == null
                    ? (float) $item->quantity
                    : (float) $item->packs
                );

                if ((float) $qty <= 0) {
                    throw new \Exception(
                        "Cantidad inválida en detalle {$item->id}"
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | PRECIOS Y TOTAL DE LA LÍNEA
                |--------------------------------------------------------------------------
                |
                | Estos valores provienen directamente de SaleDetail.
                |
                | total:
                | Cantidad por precio unitario con IGV.
                |
                | price:
                | Precio unitario con IGV.
                |
                | valor_unitario:
                | Precio unitario sin IGV.
                */
                $totalLine = (string) $item->total;

                $precioUnitario = (string) $item->price;

                $valorUnitario = (string) $item->valor_unitario;

                if ((float) $totalLine < 0) {
                    throw new \Exception(
                        "Total inválido en detalle {$item->id}"
                    );
                }

                if ((float) $precioUnitario < 0) {
                    throw new \Exception(
                        "Precio unitario inválido en detalle {$item->id}"
                    );
                }

                if ((float) $valorUnitario < 0) {
                    throw new \Exception(
                        "Valor unitario inválido en detalle {$item->id}"
                    );
                }

                /*
                 * Base imponible de la línea.
                 */
                $subtotal = bcmul(
                    $valorUnitario,
                    $qty,
                    10
                );

                /*
                |--------------------------------------------------------------------------
                | IGV DEL DETALLE
                |--------------------------------------------------------------------------
                |
                | Venta libre:
                | Usa tax_amount porque fue calculado explícitamente
                | cuando se registró la venta.
                |
                | Venta normal o cotización:
                | Conserva exactamente el cálculo histórico:
                | total - subtotal.
                */
                if (
                    $isFreeSale &&
                    !is_null($item->tax_amount)
                ) {
                    $igv = number_format(
                        (float) $item->tax_amount,
                        10,
                        '.',
                        ''
                    );
                } else {
                    $igv = bcsub(
                        $totalLine,
                        $subtotal,
                        10
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | DESCRIPCIÓN
                |--------------------------------------------------------------------------
                */
                if ($isFreeSale) {
                    /*
                     * Venta libre:
                     * La descripción fue escrita manualmente.
                     */
                    $description = trim(
                        (string) $item->description
                    );

                    $descripcion = $description !== ''
                        ? mb_strtoupper(
                            $description,
                            'UTF-8'
                        )
                        : 'SERVICIO';

                } elseif ($isService) {
                    /*
                     * Servicio de una venta normal:
                     * Se conserva el comportamiento anterior.
                     */
                    $description = trim(
                        (string) $item->description
                    );

                    $descripcion = $description !== ''
                        ? mb_strtoupper(
                            $description,
                            'UTF-8'
                        )
                        : 'SERVICIO';

                } else {
                    /*
                     * Producto de inventario:
                     * Se conserva la lógica anterior.
                     */
                    $present = (string) (
                    $item->material_presentation_id == null
                        ? round(
                        (float) $item->quantity
                    )
                        : $item->units_per_pack . 'und'
                    );

                    $descripcion = '(' . $present . ') ';

                    if (
                        !empty($item->stock_item_id) &&
                        $item->stockItem
                    ) {
                        $descripcion .=
                            $item->stockItem->display_name;

                    } elseif ($item->material) {
                        $descripcion .=
                            $item->material->full_name;

                    } elseif (
                        trim((string) $item->description) !== ''
                    ) {
                        /*
                         * Fallback adicional sin alterar el flujo normal.
                         */
                        $descripcion .= trim(
                            (string) $item->description
                        );

                    } else {
                        $descripcion .=
                            'Material ' . $item->material_id;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | CÓDIGOS DE ÍTEMS FÍSICOS
                    |--------------------------------------------------------------------------
                    |
                    | Se conserva el comportamiento anterior para productos
                    | itemeables.
                    */
                    $itemSnapshot = $item->item_snapshot ?? [];

                    if (is_string($itemSnapshot)) {
                        $decodedSnapshot = json_decode(
                            $itemSnapshot,
                            true
                        );

                        $itemSnapshot = is_array($decodedSnapshot)
                            ? $decodedSnapshot
                            : [];
                    }

                    if (!is_array($itemSnapshot)) {
                        $itemSnapshot = [];
                    }

                    $itemCodes = collect($itemSnapshot)
                        ->map(function ($snapshotItem) {
                            if (is_array($snapshotItem)) {
                                return $snapshotItem['code']
                                    ?? null;
                            }

                            if (is_object($snapshotItem)) {
                                return $snapshotItem->code
                                    ?? null;
                            }

                            return null;
                        })
                        ->filter()
                        ->values()
                        ->toArray();

                    if (!empty($itemCodes)) {
                        $descripcion .=
                            ' | Items: ' .
                            implode(', ', $itemCodes);
                    }
                }

                return [
                    'unidad_de_medida' => 'NIU',
                    'codigo' => '',
                    'descripcion' => $descripcion,
                    'cantidad' => (float) $qty,

                    /*
                     * Precio unitario sin IGV.
                     */
                    'valor_unitario' =>
                        (float) $valorUnitario,

                    /*
                     * Precio unitario con IGV.
                     */
                    'precio_unitario' =>
                        (float) $precioUnitario,

                    /*
                     * Base imponible del detalle.
                     */
                    'subtotal' =>
                        (float) $subtotal,

                    'tipo_de_igv' => '1',

                    /*
                     * IGV de toda la línea.
                     */
                    'igv' => (float) $igv,

                    /*
                     * Total de la línea con IGV.
                     */
                    'total' => (float) $totalLine,
                ];
            })
            ->values()
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | TOTALES GENERALES
        |--------------------------------------------------------------------------
        */
        $discount = $order->total_descuentos ?? 0;

        $totalGravada = $order->op_gravada ?? 0;

        if ($isFreeSale) {
            /*
             * Venta libre:
             *
             * Los valores fueron calculados en backend al crear la venta.
             * No volvemos a calcularlos para evitar diferencias de redondeo.
             */
            $base = number_format(
                (float) ($order->op_gravada ?? 0),
                10,
                '.',
                ''
            );

            $totalIgv = number_format(
                (float) ($order->igv ?? 0),
                10,
                '.',
                ''
            );

            $total = number_format(
                (float) ($order->importe_total ?? 0),
                10,
                '.',
                ''
            );

        } else {
            /*
             * Ventas normales y cotizaciones:
             *
             * Se conserva exactamente el comportamiento anterior.
             */
            $base = $totalGravada;

            $totalIgv = bcmul(
                (string) $base,
                '0.18',
                10
            );

            $total = bcadd(
                (string) $base,
                (string) $totalIgv,
                10
            );
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDACIONES MÍNIMAS
        |--------------------------------------------------------------------------
        */
        if ((float) $base < 0) {
            throw new \Exception(
                'La operación gravada no puede ser negativa.'
            );
        }

        if ((float) $totalIgv < 0) {
            throw new \Exception(
                'El IGV no puede ser negativo.'
            );
        }

        if ((float) $total <= 0) {
            throw new \Exception(
                'El importe total debe ser mayor que cero.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | PAYLOAD PRINCIPAL
        |--------------------------------------------------------------------------
        */
        $payload = [
            'operacion' => 'generar_comprobante',

            'tipo_de_comprobante' =>
                $isFactura ? '1' : '2',

            'serie' => $serie,

            'numero' => '',

            'codigo_unico' =>
                (string) Str::uuid(),

            'sunat_transaction' => '1',

            'cliente_tipo_de_documento' =>
                $tipoCliente,

            'cliente_numero_de_documento' =>
                $order->numero_documento_cliente,

            'cliente_denominacion' =>
                $order->nombre_cliente,

            'cliente_direccion' =>
                $order->direccion_cliente ?: '',

            'cliente_email' =>
                $order->email_cliente ?: '',

            'fecha_de_emision' =>
                now()->format('d-m-Y'),

            /*
             * 1 corresponde a soles.
             * Se conserva el comportamiento actual.
             */
            'moneda' => '1',

            'porcentaje_de_igv' => 18.00,

            'total_gravada' =>
                (float) $base,

            'total_igv' =>
                (float) $totalIgv,

            'total' =>
                (float) $total,

            'total_a_pagar' =>
                (float) $total,
        ];

        /*
        |--------------------------------------------------------------------------
        | DESCUENTO GLOBAL
        |--------------------------------------------------------------------------
        |
        | Se conserva exactamente el comportamiento que ya utilizabas.
        |
        | En Venta Libre actualmente será cero porque el descuento está
        | temporalmente deshabilitado.
        */
        if ((float) $discount > 0) {
            $payload['descuento_global'] =
                number_format(
                    (float) $discount,
                    10,
                    '.',
                    ''
                );

            $payload['total_descuento'] =
                number_format(
                    (float) $discount,
                    10,
                    '.',
                    ''
                );
        }

        $payload['items'] = $items;

        return $payload;
    }

    private function trunc2($value): string
    {
        // asegura string con decimales
        $s = (string) $value;

        if (strpos($s, '.') === false) {
            return $s . '.00';
        }

        [$int, $dec] = explode('.', $s, 2);
        $dec = substr($dec . '00', 0, 2); // completa y corta a 2
        return $int . '.' . $dec;
    }

    private function round2($value): string
    {
        return number_format(round((float)$value, 10, PHP_ROUND_HALF_UP), 10, '.', '');
    }

    private function generarComprobanteNubefactParaVentaO(Sale $order): array
    {
        if (!$order->type_document) {
            throw new \Exception('El tipo de comprobante no está definido.');
        }

        $data = $this->buildNubefactData($order);
        /*dump("Nubefact data");
        dump($data);
        dd();*/

        /*$token = env('NUBEFACT_TOKEN');
        $url   = env('NUBEFACT_API_URL');*/
        $token = config('services.nubefact.token');
        $url   = config('services.nubefact.url');

        if (!$token || !$url) {
            throw new \Exception('Faltan credenciales Nubefact en .env (NUBEFACT_TOKEN / NUBEFACT_API_URL).');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Token token=' . $token,
            'Content-Type'  => 'application/json',
        ])->post($url, $data);

        $result = $response->json();

        if (!$response->ok()) {
            $msg = is_array($result) ? json_encode($result) : $response->body();
            throw new \Exception('Nubefact respondió error HTTP: ' . $msg);
        }

        if (isset($result['errors'])) {
            throw new \Exception('Error desde Nubefact: ' . $result['errors']);
        }

        return $result;
    }

    private function generarComprobanteNubefactParaVenta(Sale $order): array
    {
        if (!$order->type_document) {
            throw new \RuntimeException(
                'El tipo de comprobante no está definido.'
            );
        }

        $data = $this->buildNubefactData($order);

        $token = config('services.nubefact.token');
        $url = config('services.nubefact.url');

        if (empty($token) || empty($url)) {
            throw new \RuntimeException(
                'Faltan las credenciales de Nubefact.'
            );
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Token token=' . $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
                ->timeout(60)
                ->post($url, $data);
        } catch (\Throwable $e) {
            Log::error('No se pudo conectar con Nubefact', [
                'sale_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException(
                'No se pudo establecer conexión con Nubefact: '
                . $e->getMessage()
            );
        }

        $result = $response->json();

        if (!$response->successful()) {
            $mensaje = is_array($result)
                ? json_encode($result, JSON_UNESCAPED_UNICODE)
                : substr($response->body(), 0, 1000);

            Log::error('Nubefact respondió con error HTTP', [
                'sale_id' => $order->id,
                'http_status' => $response->status(),
                'content_type' => $response->header('Content-Type'),
                'response' => $mensaje,
            ]);

            throw new \RuntimeException(
                'Nubefact respondió con HTTP '
                . $response->status()
                . ': '
                . $mensaje
            );
        }

        if (!is_array($result)) {
            Log::error('Nubefact no devolvió un JSON válido', [
                'sale_id' => $order->id,
                'http_status' => $response->status(),
                'content_type' => $response->header('Content-Type'),
                'response' => substr($response->body(), 0, 1000),
            ]);

            throw new \RuntimeException(
                'Nubefact devolvió una respuesta inválida.'
            );
        }

        if (!empty($result['errors'])) {
            $errores = is_array($result['errors'])
                ? json_encode(
                    $result['errors'],
                    JSON_UNESCAPED_UNICODE
                )
                : (string) $result['errors'];

            throw new \RuntimeException(
                'Error desde Nubefact: ' . $errores
            );
        }

        return $result;
    }

    private function persistNubefactFilesAndUpdateSaleO(Sale $order, array $result): void
    {
        $filename = 'ORD' . $order->id;

        $pdfFilename = $filename . '.pdf';
        $xmlFilename = $filename . '.xml';
        $cdrFilename = $filename . '.zip';

        // Crear carpetas si no existen
        foreach (['pdfs', 'xmls', 'cdrs'] as $folder) {
            if (!file_exists(public_path("comprobantes/$folder"))) {
                mkdir(public_path("comprobantes/$folder"), 0777, true);
            }
        }

        // Descargar archivos desde Nubefact
        if (!empty($result['enlace_del_pdf'])) {
            $pdfContent = Http::get($result['enlace_del_pdf'])->body();
            file_put_contents(public_path('comprobantes/pdfs/' . $pdfFilename), $pdfContent);
        }

        if (!empty($result['enlace_del_xml'])) {
            $xmlContent = Http::get($result['enlace_del_xml'])->body();
            file_put_contents(public_path('comprobantes/xmls/' . $xmlFilename), $xmlContent);
        }

        if (!empty($result['enlace_del_cdr'])) {
            $cdrContent = Http::get($result['enlace_del_cdr'])->body();
            file_put_contents(public_path('comprobantes/cdrs/' . $cdrFilename), $cdrContent);
        }

        // Actualizar la venta con los nombres de archivo y estado SUNAT
        $order->update([
            'serie_sunat'   => $result['serie'] ?? null,
            'numero'        => $result['numero'] ?? null,
            'sunat_ticket'  => $result['sunat_ticket'] ?? null,
            'sunat_status'  => $result['sunat_description'] ?? 'Enviado',
            'sunat_message' => $result['sunat_note'] ?? '',
            'xml_path'      => file_exists(public_path('comprobantes/xmls/' . $xmlFilename)) ? $xmlFilename : null,
            'cdr_path'      => file_exists(public_path('comprobantes/cdrs/' . $cdrFilename)) ? $cdrFilename : null,
            'pdf_path'      => file_exists(public_path('comprobantes/pdfs/' . $pdfFilename)) ? $pdfFilename : null,
            'fecha_emision' => now()->toDateString(),
        ]);
    }

    private function persistNubefactFilesAndUpdateSaleO2(Sale $order, array $result): array {
        $filename = 'ORD' . $order->id;

        $pdfFilename = $filename . '.pdf';
        $xmlFilename = $filename . '.xml';
        $cdrFilename = $filename . '.zip';

        $pdfDirectory = public_path('comprobantes/pdfs');
        $xmlDirectory = public_path('comprobantes/xmls');
        $cdrDirectory = public_path('comprobantes/cdrs');

        /*
         * Crear las carpetas necesarias.
         * Se utiliza 0755 en lugar de 0777.
         */
        foreach (
            [
                $pdfDirectory,
                $xmlDirectory,
                $cdrDirectory,
            ] as $directory
        ) {
            if (!is_dir($directory)) {
                if (
                    !mkdir($directory, 0755, true) &&
                    !is_dir($directory)
                ) {
                    throw new \RuntimeException(
                        'No se pudo crear el directorio: ' . $directory
                    );
                }
            }
        }

        $pdfPath = $pdfDirectory
            . DIRECTORY_SEPARATOR
            . $pdfFilename;

        $xmlPath = $xmlDirectory
            . DIRECTORY_SEPARATOR
            . $xmlFilename;

        $cdrPath = $cdrDirectory
            . DIRECTORY_SEPARATOR
            . $cdrFilename;

        $resultado = [
            'pdf_descargado' => false,
            'xml_descargado' => false,
            'cdr_descargado' => false,
            'errores' => [],
        ];

        /*
         * Descargar PDF.
         */
        if (!empty($result['enlace_del_pdf'])) {
            try {
                $this->descargarArchivoNubefactSeguro(
                    $result['enlace_del_pdf'],
                    $pdfPath,
                    'pdf'
                );

                $resultado['pdf_descargado'] = true;
            } catch (\Throwable $e) {
                $resultado['errores'][] =
                    'PDF: ' . $e->getMessage();

                Log::error(
                    'Comprobante emitido, pero falló la descarga del PDF',
                    [
                        'sale_id' => $order->id,
                        'url' => $result['enlace_del_pdf'],
                        'error' => $e->getMessage(),
                    ]
                );
            }
        } else {
            $resultado['errores'][] =
                'Nubefact no devolvió enlace del PDF.';

            Log::warning(
                'Nubefact no devolvió enlace del PDF',
                [
                    'sale_id' => $order->id,
                ]
            );
        }

        /*
         * Descargar XML.
         */
        if (!empty($result['enlace_del_xml'])) {
            try {
                $this->descargarArchivoNubefactSeguro(
                    $result['enlace_del_xml'],
                    $xmlPath,
                    'xml'
                );

                $resultado['xml_descargado'] = true;
            } catch (\Throwable $e) {
                $resultado['errores'][] =
                    'XML: ' . $e->getMessage();

                Log::error(
                    'Comprobante emitido, pero falló la descarga del XML',
                    [
                        'sale_id' => $order->id,
                        'url' => $result['enlace_del_xml'],
                        'error' => $e->getMessage(),
                    ]
                );
            }
        }

        /*
         * Descargar CDR.
         */
        if (!empty($result['enlace_del_cdr'])) {
            try {
                $this->descargarArchivoNubefactSeguro(
                    $result['enlace_del_cdr'],
                    $cdrPath,
                    'zip'
                );

                $resultado['cdr_descargado'] = true;
            } catch (\Throwable $e) {
                $resultado['errores'][] =
                    'CDR: ' . $e->getMessage();

                Log::error(
                    'Comprobante emitido, pero falló la descarga del CDR',
                    [
                        'sale_id' => $order->id,
                        'url' => $result['enlace_del_cdr'],
                        'error' => $e->getMessage(),
                    ]
                );
            }
        }

        /*
         * Registrar únicamente los archivos que fueron descargados
         * y validados correctamente.
         */
        $archivosActualizar = [];

        if ($resultado['pdf_descargado']) {
            $archivosActualizar['pdf_path'] = $pdfFilename;
        }

        if ($resultado['xml_descargado']) {
            $archivosActualizar['xml_path'] = $xmlFilename;
        }

        if ($resultado['cdr_descargado']) {
            $archivosActualizar['cdr_path'] = $cdrFilename;
        }

        if (!empty($archivosActualizar)) {
            $order->update($archivosActualizar);
        }

        return $resultado;
    }

    private function persistNubefactFilesAndUpdateSale(Sale $order, array $result): array {
        /*
        |--------------------------------------------------------------------------
        | 1. VALIDAR RESPUESTA MÍNIMA DEL COMPROBANTE
        |--------------------------------------------------------------------------
        */
        $serie = trim(
            (string) ($result['serie'] ?? '')
        );

        $numero = trim(
            (string) ($result['numero'] ?? '')
        );

        if ($serie === '' || $numero === '') {
            throw new \RuntimeException(
                'No se puede persistir el comprobante porque Nubefact no devolvió serie o número.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 2. GUARDAR PRIMERO LA INFORMACIÓN SUNAT
        |--------------------------------------------------------------------------
        |
        | La emisión no depende de que posteriormente se pueda descargar
        | el PDF, XML o CDR.
        */
        $sunatStatus = $result['sunat_description']
            ?? $result['aceptada_por_sunat']
            ?? 'Enviado';

        if (is_bool($sunatStatus)) {
            $sunatStatus = $sunatStatus
                ? 'Aceptado'
                : 'Enviado';
        }

        $sunatMessage = $result['sunat_note']
            ?? $result['sunat_soap_error']
            ?? '';

        $order->update([
            'serie_sunat' => $serie,
            'numero' => $numero,

            'sunat_ticket' =>
                $result['sunat_ticket']
                ?? $result['sunat_ticket_numero']
                ?? null,

            'sunat_status' =>
                (string) $sunatStatus,

            'sunat_message' =>
                is_array($sunatMessage)
                    ? json_encode(
                    $sunatMessage,
                    JSON_UNESCAPED_UNICODE
                )
                    : (string) $sunatMessage,

            'fecha_emision' => now()->toDateString(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | 3. PREPARAR NOMBRES Y DIRECTORIOS
        |--------------------------------------------------------------------------
        */
        $filename = 'ORD' . $order->id;

        $pdfFilename = $filename . '.pdf';
        $xmlFilename = $filename . '.xml';
        $cdrFilename = $filename . '.zip';

        $pdfDirectory =
            public_path('comprobantes/pdfs');

        $xmlDirectory =
            public_path('comprobantes/xmls');

        $cdrDirectory =
            public_path('comprobantes/cdrs');

        foreach (
            [
                $pdfDirectory,
                $xmlDirectory,
                $cdrDirectory,
            ] as $directory
        ) {
            if (!is_dir($directory)) {
                if (
                    !mkdir($directory, 0755, true) &&
                    !is_dir($directory)
                ) {
                    throw new \RuntimeException(
                        'No se pudo crear el directorio: ' .
                        $directory
                    );
                }
            }
        }

        $pdfPath =
            $pdfDirectory .
            DIRECTORY_SEPARATOR .
            $pdfFilename;

        $xmlPath =
            $xmlDirectory .
            DIRECTORY_SEPARATOR .
            $xmlFilename;

        $cdrPath =
            $cdrDirectory .
            DIRECTORY_SEPARATOR .
            $cdrFilename;

        $resultado = [
            'comprobante_registrado' => true,

            'pdf_descargado' => false,
            'xml_descargado' => false,
            'cdr_descargado' => false,

            'errores' => [],
        ];

        /*
        |--------------------------------------------------------------------------
        | 4. DESCARGAR PDF
        |--------------------------------------------------------------------------
        */
        if (!empty($result['enlace_del_pdf'])) {
            try {
                $this->descargarArchivoNubefactSeguro(
                    $result['enlace_del_pdf'],
                    $pdfPath,
                    'pdf'
                );

                $resultado['pdf_descargado'] = true;

            } catch (\Throwable $e) {
                $resultado['errores'][] =
                    'PDF: ' . $e->getMessage();

                Log::error(
                    'Comprobante emitido, pero falló la descarga del PDF',
                    [
                        'sale_id' => $order->id,
                        'url' => $result['enlace_del_pdf'],
                        'error' => $e->getMessage(),
                    ]
                );
            }
        } else {
            $resultado['errores'][] =
                'Nubefact no devolvió enlace del PDF.';

            Log::warning(
                'Nubefact no devolvió enlace del PDF',
                [
                    'sale_id' => $order->id,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 5. DESCARGAR XML
        |--------------------------------------------------------------------------
        */
        if (!empty($result['enlace_del_xml'])) {
            try {
                $this->descargarArchivoNubefactSeguro(
                    $result['enlace_del_xml'],
                    $xmlPath,
                    'xml'
                );

                $resultado['xml_descargado'] = true;

            } catch (\Throwable $e) {
                $resultado['errores'][] =
                    'XML: ' . $e->getMessage();

                Log::error(
                    'Comprobante emitido, pero falló la descarga del XML',
                    [
                        'sale_id' => $order->id,
                        'url' => $result['enlace_del_xml'],
                        'error' => $e->getMessage(),
                    ]
                );
            }
        } else {
            $resultado['errores'][] =
                'Nubefact no devolvió enlace del XML.';
        }

        /*
        |--------------------------------------------------------------------------
        | 6. DESCARGAR CDR
        |--------------------------------------------------------------------------
        */
        if (!empty($result['enlace_del_cdr'])) {
            try {
                $this->descargarArchivoNubefactSeguro(
                    $result['enlace_del_cdr'],
                    $cdrPath,
                    'zip'
                );

                $resultado['cdr_descargado'] = true;

            } catch (\Throwable $e) {
                $resultado['errores'][] =
                    'CDR: ' . $e->getMessage();

                Log::error(
                    'Comprobante emitido, pero falló la descarga del CDR',
                    [
                        'sale_id' => $order->id,
                        'url' => $result['enlace_del_cdr'],
                        'error' => $e->getMessage(),
                    ]
                );
            }
        } else {
            $resultado['errores'][] =
                'Nubefact no devolvió enlace del CDR.';
        }

        /*
        |--------------------------------------------------------------------------
        | 7. GUARDAR ÚNICAMENTE ARCHIVOS VÁLIDOS
        |--------------------------------------------------------------------------
        */
        $archivosActualizar = [];

        if ($resultado['pdf_descargado']) {
            $archivosActualizar['pdf_path'] =
                $pdfFilename;
        }

        if ($resultado['xml_descargado']) {
            $archivosActualizar['xml_path'] =
                $xmlFilename;
        }

        if ($resultado['cdr_descargado']) {
            $archivosActualizar['cdr_path'] =
                $cdrFilename;
        }

        if (!empty($archivosActualizar)) {
            $order->update(
                $archivosActualizar
            );
        }

        $order->refresh();

        return $resultado;
    }

    private function buildNubefactVoidData(Sale $sale, string $motivo): array
    {
        if (!$sale->type_document || !in_array($sale->type_document, ['01', '03'], true)) {
            throw new \Exception('La venta no tiene factura o boleta electrónica para anular.');
        }

        if (empty($sale->serie_sunat) || empty($sale->numero)) {
            throw new \Exception('La venta no tiene serie o número SUNAT para anular.');
        }

        return [
            "operacion" => "generar_anulacion",
            "tipo_de_comprobante" => $sale->type_document === '01' ? "1" : "2",
            "serie" => $sale->serie_sunat,
            "numero" => (string) $sale->numero,
            "motivo" => $motivo ?: "Anulación de comprobante",
            "codigo_unico" => (string) Str::uuid(),
        ];
    }

    private function anularComprobanteNubefact(Sale $sale, string $motivo): array
    {
        $data = $this->buildNubefactVoidData($sale, $motivo);

        $token = config('services.nubefact.token');
        $url   = config('services.nubefact.url');

        if (!$token || !$url) {
            throw new \Exception('Faltan credenciales Nubefact en .env.');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Token token=' . $token,
            'Content-Type'  => 'application/json',
        ])->post($url, $data);

        $result = $response->json();

        if (!$response->ok()) {
            $msg = is_array($result) ? json_encode($result) : $response->body();
            throw new \Exception('Nubefact respondió error HTTP al anular: ' . $msg);
        }

        if (isset($result['errors'])) {
            throw new \Exception('Error desde Nubefact al anular: ' . $result['errors']);
        }

        return $result;
    }

    private function persistNubefactAnnulmentResult(Sale $sale, array $result, string $motivo): void
    {
        $accepted = (bool) ($result['aceptada_por_sunat'] ?? false);

        $description = $result['sunat_description'] ?? null;
        $note = $result['sunat_note'] ?? null;
        $soapError = $result['sunat_soap_error'] ?? null;
        $responseCode = $result['sunat_responsecode'] ?? null;

        $ticket = $result['sunat_ticket_numero']
            ?? $result['sunat_ticket']
            ?? null;

        $key = $result['key'] ?? null;

        $pdfUrl = $result['enlace_del_pdf'] ?? null;
        $xmlUrl = $result['enlace_del_xml'] ?? null;
        $cdrUrl = $result['enlace_del_cdr'] ?? null;

        $filename = 'ANULACION_ORD' . $sale->id;

        $pdfFilename = $filename . '.pdf';
        $xmlFilename = $filename . '.xml';
        $cdrFilename = $filename . '.cdr';

        foreach ([
                     'anulaciones/pdfs',
                     'anulaciones/xmls',
                     'anulaciones/cdrs',
                 ] as $folder) {
            if (!file_exists(public_path("comprobantes/$folder"))) {
                mkdir(public_path("comprobantes/$folder"), 0777, true);
            }
        }

        if (!empty($pdfUrl)) {
            $pdfContent = Http::get($pdfUrl)->body();
            file_put_contents(public_path('comprobantes/anulaciones/pdfs/' . $pdfFilename), $pdfContent);
        }

        if (!empty($xmlUrl)) {
            $xmlContent = Http::get($xmlUrl)->body();
            file_put_contents(public_path('comprobantes/anulaciones/xmls/' . $xmlFilename), $xmlContent);
        }

        if (!empty($cdrUrl)) {
            $cdrContent = Http::get($cdrUrl)->body();
            file_put_contents(public_path('comprobantes/anulaciones/cdrs/' . $cdrFilename), $cdrContent);
        }

        $finalMessage = $soapError
            ?: ($note ?: ($description ?: null));

        $sale->annulment_response = json_encode($result, JSON_UNESCAPED_UNICODE);

        if (!empty($ticket)) {
            $sale->annulment_ticket = $ticket;
        }

        if (!empty($key)) {
            $sale->annulment_key = $key;
        }

        $sale->annulment_reason = $motivo;
        $sale->annulment_requested_at = $sale->annulment_requested_at ?: now();

        if (!empty($pdfUrl)) {
            $sale->annulment_pdf_url = $pdfUrl;
        }

        if (!empty($xmlUrl)) {
            $sale->annulment_xml_url = $xmlUrl;
        }

        if (!empty($cdrUrl)) {
            $sale->annulment_cdr_url = $cdrUrl;
        }

        if (file_exists(public_path('comprobantes/anulaciones/pdfs/' . $pdfFilename))) {
            $sale->annulment_pdf_path = $pdfFilename;
        }

        if (file_exists(public_path('comprobantes/anulaciones/xmls/' . $xmlFilename))) {
            $sale->annulment_xml_path = $xmlFilename;
        }

        if (file_exists(public_path('comprobantes/anulaciones/cdrs/' . $cdrFilename))) {
            $sale->annulment_cdr_path = $cdrFilename;
        }

        if (!empty($responseCode)) {
            $sale->annulment_sunat_responsecode = $responseCode;
        }

        if ($accepted) {

            /*
             * ============================================================
             * ACEPTACIÓN CONFIRMADA POR SUNAT
             * ============================================================
             */
            $sale->annulment_status = 'accepted';
            $sale->annulment_accepted_at = now();
            $sale->annulment_sunat_status = 'Aceptado';

            $sale->annulment_sunat_message =
                $finalMessage ?: 'Anulación aceptada por SUNAT.';

            $sale->annulment_error = null;

        } elseif (!empty($soapError)) {

            /*
             * ============================================================
             * ERROR TÉCNICO / SOAP
             * ============================================================
             *
             * Un error SOAP no significa que SUNAT haya rechazado
             * la anulación.
             *
             * La solicitud puede seguir procesándose y debe volver
             * a consultarse posteriormente.
             */
            $sale->annulment_status = 'pending';
            $sale->annulment_sunat_status = 'Pendiente';

            $sale->annulment_sunat_message =
                $soapError;

            $sale->annulment_error =
                $soapError;

        } elseif (!empty($responseCode)) {

            /*
             * ============================================================
             * RESPUESTA SUNAT CON CÓDIGO
             * ============================================================
             *
             * Por ahora NO asumimos que cualquier código significa rechazo.
             * Mantendremos pendiente hasta determinar cuáles códigos
             * representan realmente un rechazo definitivo.
             */
            $sale->annulment_status = 'pending';
            $sale->annulment_sunat_status = 'Pendiente';

            $sale->annulment_sunat_message =
                $finalMessage
                    ?: 'SUNAT devolvió el código ' . $responseCode . '. La anulación continuará pendiente de verificación.';

            $sale->annulment_error =
                $finalMessage;

        } else {

            /*
             * ============================================================
             * SIN RESPUESTA DEFINITIVA
             * ============================================================
             */
            $sale->annulment_status = 'pending';
            $sale->annulment_sunat_status = 'Pendiente';

            $sale->annulment_sunat_message =
                $finalMessage
                    ?: 'Anulación enviada a Nubefact. Pendiente de aceptación SUNAT.';

            $sale->annulment_error = null;
        }

        $sale->save();
    }

    private function buildNubefactConsultAnnulmentData(Sale $sale): array
    {
        if (!$sale->type_document || !in_array($sale->type_document, ['01', '03'], true)) {
            throw new \Exception('La venta no tiene factura o boleta electrónica para consultar anulación.');
        }

        if (empty($sale->serie_sunat) || empty($sale->numero)) {
            throw new \Exception('La venta no tiene serie o número SUNAT.');
        }

        return [
            "operacion" => "consultar_anulacion",
            "tipo_de_comprobante" => $sale->type_document === '01' ? 1 : 2,
            "serie" => $sale->serie_sunat,
            "numero" => (int) $sale->numero,
        ];
    }

    private function consultarAnulacionNubefact(Sale $sale): array
    {
        $data = $this->buildNubefactConsultAnnulmentData($sale);

        $token = config('services.nubefact.token');
        $url   = config('services.nubefact.url');

        if (!$token || !$url) {
            throw new \Exception('Faltan credenciales Nubefact.');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Token token=' . $token,
            'Content-Type'  => 'application/json',
        ])->post($url, $data);

        $result = $response->json();

        if (!$response->ok()) {
            $msg = is_array($result) ? json_encode($result) : $response->body();
            throw new \Exception('Error HTTP consultando anulación en Nubefact: ' . $msg);
        }

        if (isset($result['errors'])) {
            throw new \Exception(
                is_array($result['errors'])
                    ? json_encode($result['errors'], JSON_UNESCAPED_UNICODE)
                    : $result['errors']
            );
        }

        /*
         * ============================================================
         * RESPUESTA VACÍA / INCONCLUSA
         * ============================================================
         *
         * HTTP 200 no significa necesariamente que SUNAT haya
         * respondido de forma definitiva.
         *
         * Nubefact puede devolver un arreglo vacío temporalmente.
         * En ese caso devolvemos una respuesta normalizada para que
         * persistNubefactAnnulmentResult() la mantenga como pending.
        */
        if (!is_array($result) || empty($result)) {
            return [
                'aceptada_por_sunat' => false,
                'sunat_description' => null,
                'sunat_note' => null,
                'sunat_responsecode' => null,
                'sunat_soap_error' =>
                    'Nubefact devolvió una respuesta vacía o no concluyente al consultar la anulación. Se realizará una nueva consulta posteriormente.',
                '_consulta_inconclusa' => true,
            ];
        }


        return $result;
    }

    private function buildNubefactCreditNoteTotalData(Sale $sale, CreditNote $creditNote): array
    {
        $sale->loadMissing([
            'details.material',
            'details.stockItem'
        ]);

        if (empty($creditNote->generation_key)) {
            $creditNote->generation_key =
                (string) Str::uuid();

            $creditNote->save();
        }

        $isFactura = $sale->type_document === '01';

        $serieNotaCredito = $isFactura
            ? config('services.nubefact.serie_nc_factura', 'FFF1')
            : config('services.nubefact.serie_nc_boleta', 'BBB1');

        $items = $sale->details->map(function ($item) {

            $qty = (float) $item->quantity;
            $subtotal = (float) $item->valor_unitario * $qty;
            $igv = (float) $item->total - $subtotal;

            if ($qty <= 0) {
                throw new \Exception("Cantidad inválida en detalle {$item->id}");
            }

            $descripcion = '';

            if (empty($item->material_id)) {
                $descripcion = strtoupper($item->description ?: 'Servicio');
            } else {
                if (!empty($item->stock_item_id) && $item->stockItem) {
                    $descripcion = $item->stockItem->display_name;
                } elseif ($item->material) {
                    $descripcion = $item->material->full_name;
                } else {
                    $descripcion = 'Material ' . $item->material_id;
                }
            }

            return [
                "unidad_de_medida" => "NIU",
                "codigo" => "",
                "descripcion" => $descripcion,
                "cantidad" => $qty,
                "valor_unitario" => (float) $item->valor_unitario,
                "precio_unitario" => (float) $item->price,
                "subtotal" => round($subtotal, 2),
                "tipo_de_igv" => "1",
                "igv" => round($igv, 2),
                "total" => round((float) $item->total, 2),
            ];
        })->toArray();

        return [
            "operacion" => "generar_comprobante",
            "tipo_de_comprobante" => "3", // Nota de crédito
            "serie" => $serieNotaCredito,
            "numero" => "",
            /*"codigo_unico" => 'NC-' . $sale->id . '-' . now()->timestamp . '-' . Str::random(8),*/
            "codigo_unico" => $creditNote->generation_key,
            "sunat_transaction" => "1",

            "cliente_tipo_de_documento" => $sale->tipo_documento_cliente,
            "cliente_numero_de_documento" => $sale->numero_documento_cliente,
            "cliente_denominacion" => $sale->nombre_cliente,
            "cliente_direccion" => $sale->direccion_cliente ?: "",
            "cliente_email" => $sale->email_cliente ?: "",

            "fecha_de_emision" => now()->format('d-m-Y'),
            "moneda" => "1",
            "porcentaje_de_igv" => 18.00,

            "tipo_de_nota_de_credito" => $creditNote->reason_code,
            "motivo_o_sustento_de_nota_de_credito" => $creditNote->reason_description,

            "documento_que_se_modifica_tipo" => $sale->type_document === '01' ? "1" : "2",
            "documento_que_se_modifica_serie" => $sale->serie_sunat,
            "documento_que_se_modifica_numero" => $sale->numero,

            "total_gravada" => (float) $sale->op_gravada,
            "total_igv" => (float) $sale->igv,
            "total" => (float) $sale->importe_total,
            "total_a_pagar" => (float) $sale->importe_total,

            "items" => $items,
        ];
    }

    private function generarNotaCreditoNubefact(Sale $sale, CreditNote $creditNote): array
    {
        $data = $this->buildNubefactCreditNoteTotalData($sale, $creditNote);

        Log::info(
            'Payload Nota de Crédito Nubefact',
            [
                'sale_id' => $sale->id,
                'credit_note_id' =>
                    $creditNote->id,

                'original_document' => [
                    'type_document' =>
                        $sale->type_document,

                    'serie' =>
                        $sale->serie_sunat,

                    'numero' =>
                        $sale->numero,
                ],

                'generation_key' =>
                    $creditNote->generation_key,

                'payload' => $data,
            ]
        );
        //dd($data);

        $token = config('services.nubefact.token');
        $url = config('services.nubefact.url');

        if (!$token || !$url) {
            throw new \Exception('Faltan credenciales Nubefact.');
        }

        try {
            $response = Http::withHeaders([
                'Authorization' =>
                    'Token token=' . $token,

                'Accept' =>
                    'application/json',

                'Content-Type' =>
                    'application/json',
            ])
                ->timeout(60)
                ->post($url, $data);

        } catch (\Throwable $e) {
            Log::error(
                'No se pudo conectar con Nubefact al generar Nota de Crédito',
                [
                    'sale_id' => $sale->id,
                    'credit_note_id' =>
                        $creditNote->id,

                    'error' => $e->getMessage(),
                ]
            );

            throw new \RuntimeException(
                'No se pudo establecer conexión con Nubefact: ' .
                $e->getMessage()
            );
        }

        $result = $response->json();

        if (!$response->successful()) {
            $message = is_array($result)
                ? json_encode(
                    $result,
                    JSON_UNESCAPED_UNICODE
                )
                : substr(
                    $response->body(),
                    0,
                    1000
                );

            Log::error(
                'Nubefact respondió error HTTP al generar Nota de Crédito',
                [
                    'sale_id' => $sale->id,
                    'credit_note_id' =>
                        $creditNote->id,

                    'http_status' =>
                        $response->status(),

                    'response' => $message,
                ]
            );

            throw new \RuntimeException(
                'Nubefact respondió con HTTP ' .
                $response->status() .
                ': ' .
                $message
            );
        }

        if (!is_array($result)) {
            throw new \RuntimeException(
                'Nubefact devolvió una respuesta inválida para la Nota de Crédito.'
            );
        }

        if (!empty($result['errors'])) {
            $errors = is_array(
                $result['errors']
            )
                ? json_encode(
                    $result['errors'],
                    JSON_UNESCAPED_UNICODE
                )
                : (string) $result['errors'];

            throw new \RuntimeException(
                'Error desde Nubefact: ' .
                $errors
            );
        }

        Log::info(
            'Respuesta Nota de Crédito Nubefact',
            [
                'sale_id' => $sale->id,
                'credit_note_id' =>
                    $creditNote->id,

                'result' => $result,
            ]
        );

        return $result;
    }

    private function persistNubefactCreditNoteResult(CreditNote $creditNote,array $result): void {
        /*
        |--------------------------------------------------------------------------
        | 1. INTERPRETAR RESPUESTA DE NUBEFACT / SUNAT
        |--------------------------------------------------------------------------
        */

        if (
            isset($result['invoice']) &&
            is_array($result['invoice'])
        ) {
            $result = array_merge(
                $result,
                $result['invoice']
            );
        }

        if (
            isset($result['data']) &&
            is_array($result['data'])
        ) {
            $result = array_merge(
                $result,
                $result['data']
            );
        }

        $accepted = filter_var(
            $result['aceptada_por_sunat'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        $description =
            $result['sunat_description'] ?? null;

        $note =
            $result['sunat_note'] ?? null;

        $soapError =
            $result['sunat_soap_error'] ?? null;

        $responseCode =
            $result['sunat_responsecode'] ?? null;

        $pdfUrl =
            $result['enlace_del_pdf'] ?? null;

        $xmlUrl =
            $result['enlace_del_xml'] ?? null;

        $cdrUrl =
            $result['enlace_del_cdr'] ?? null;

        /*
         * Convertir posibles arreglos en texto.
         */
        if (is_array($description)) {
            $description = json_encode(
                $description,
                JSON_UNESCAPED_UNICODE
            );
        }

        if (is_array($note)) {
            $note = json_encode(
                $note,
                JSON_UNESCAPED_UNICODE
            );
        }

        if (is_array($soapError)) {
            $soapError = json_encode(
                $soapError,
                JSON_UNESCAPED_UNICODE
            );
        }

        $finalMessage =
            $soapError
                ?: ($note ?: ($description ?: null));

        /*
        |--------------------------------------------------------------------------
        | 2. GUARDAR INFORMACIÓN PRINCIPAL DE LA RESPUESTA
        |--------------------------------------------------------------------------
        |
        | Guardamos esto antes de descargar los archivos.
        | La respuesta fiscal no depende de que el PDF pueda descargarse.
        */
        $creditNote->serie =
            $result['serie']
            ?? $creditNote->serie;

        $creditNote->numero =
            $result['numero']
            ?? $creditNote->numero;

        $creditNote->sunat_ticket =
            $result['sunat_ticket']
            ?? $result['sunat_ticket_numero']
            ?? null;

        $creditNote->nubefact_key =
            $result['key']
            ?? $creditNote->nubefact_key;

        $creditNote->nubefact_response =
            json_encode(
                $result,
                JSON_UNESCAPED_UNICODE
            );

        $creditNote->sunat_responsecode =
            is_null($responseCode)
                ? null
                : (string) $responseCode;

        $creditNote->pdf_url = $pdfUrl;
        $creditNote->xml_url = $xmlUrl;
        $creditNote->cdr_url = $cdrUrl;

        /*
        |--------------------------------------------------------------------------
        | 3. DETERMINAR EL ESTADO REAL
        |--------------------------------------------------------------------------
        */
        if ($accepted) {
            $creditNote->status = 'accepted';

            $creditNote->accepted_at =
                $creditNote->accepted_at ?: now();

            $creditNote->sunat_status =
                'Aceptado';

            $creditNote->sunat_message =
                $finalMessage
                    ?: 'Nota de Crédito aceptada por SUNAT.';

        } elseif (
            !empty($soapError) ||
            (
                !empty($responseCode) &&
                (string) $responseCode !== '0'
            )
        ) {
            $creditNote->status = 'rejected';

            /*
             * Si antes estuvo aceptada, no conservamos esa fecha.
             */
            $creditNote->accepted_at = null;

            $creditNote->sunat_status =
                'Rechazado';

            $creditNote->sunat_message =
                $finalMessage
                    ?: 'SUNAT rechazó la Nota de Crédito.';

        } else {
            $creditNote->status = 'pending';

            $creditNote->accepted_at = null;

            $creditNote->sunat_status =
                'Pendiente';

            $creditNote->sunat_message =
                $finalMessage
                    ?: 'Nota de Crédito enviada a Nubefact. Pendiente de aceptación SUNAT.';
        }

        /*
         * Persistimos primero la respuesta y el estado.
         */
        $creditNote->save();

        /*
        |--------------------------------------------------------------------------
        | 4. PREPARAR DIRECTORIOS Y ARCHIVOS
        |--------------------------------------------------------------------------
        */
        $filename =
            'NC_' . $creditNote->id;

        $pdfFilename =
            $filename . '.pdf';

        $xmlFilename =
            $filename . '.xml';

        $cdrFilename =
            $filename . '.zip';

        $pdfDirectory =
            public_path(
                'comprobantes/notas_credito/pdfs'
            );

        $xmlDirectory =
            public_path(
                'comprobantes/notas_credito/xmls'
            );

        $cdrDirectory =
            public_path(
                'comprobantes/notas_credito/cdrs'
            );

        foreach (
            [
                $pdfDirectory,
                $xmlDirectory,
                $cdrDirectory,
            ] as $directory
        ) {
            if (!is_dir($directory)) {
                if (
                    !mkdir($directory, 0755, true) &&
                    !is_dir($directory)
                ) {
                    Log::error(
                        'No se pudo crear directorio para Nota de Crédito',
                        [
                            'credit_note_id' =>
                                $creditNote->id,

                            'directory' =>
                                $directory,
                        ]
                    );

                    /*
                     * No lanzamos excepción porque la respuesta
                     * fiscal ya fue registrada.
                     */
                }
            }
        }

        $pdfPath =
            $pdfDirectory .
            DIRECTORY_SEPARATOR .
            $pdfFilename;

        $xmlPath =
            $xmlDirectory .
            DIRECTORY_SEPARATOR .
            $xmlFilename;

        $cdrPath =
            $cdrDirectory .
            DIRECTORY_SEPARATOR .
            $cdrFilename;

        $filesToUpdate = [];

        /*
        |--------------------------------------------------------------------------
        | 5. DESCARGAR PDF
        |--------------------------------------------------------------------------
        |
        | Puede existir PDF incluso cuando SUNAT rechazó la nota.
        | Se conserva únicamente como evidencia/auditoría.
        */
        if (
            !empty($pdfUrl) &&
            is_dir($pdfDirectory)
        ) {
            try {
                $this->descargarArchivoNubefactSeguro(
                    $pdfUrl,
                    $pdfPath,
                    'pdf'
                );

                $filesToUpdate['pdf_path'] =
                    $pdfFilename;

            } catch (\Throwable $e) {
                Log::error(
                    'Falló la descarga del PDF de Nota de Crédito',
                    [
                        'credit_note_id' =>
                            $creditNote->id,

                        'status' =>
                            $creditNote->status,

                        'url' =>
                            $pdfUrl,

                        'error' =>
                            $e->getMessage(),
                    ]
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 6. DESCARGAR XML
        |--------------------------------------------------------------------------
        */
        if (
            !empty($xmlUrl) &&
            is_dir($xmlDirectory)
        ) {
            try {
                $this->descargarArchivoNubefactSeguro(
                    $xmlUrl,
                    $xmlPath,
                    'xml'
                );

                $filesToUpdate['xml_path'] =
                    $xmlFilename;

            } catch (\Throwable $e) {
                Log::error(
                    'Falló la descarga del XML de Nota de Crédito',
                    [
                        'credit_note_id' =>
                            $creditNote->id,

                        'status' =>
                            $creditNote->status,

                        'url' =>
                            $xmlUrl,

                        'error' =>
                            $e->getMessage(),
                    ]
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 7. DESCARGAR CDR
        |--------------------------------------------------------------------------
        |
        | En una respuesta rechazada, el CDR puede ser importante
        | porque contiene el código y mensaje de SUNAT.
        */
        if (
            !empty($cdrUrl) &&
            is_dir($cdrDirectory)
        ) {
            try {
                $this->descargarArchivoNubefactSeguro(
                    $cdrUrl,
                    $cdrPath,
                    'zip'
                );

                $filesToUpdate['cdr_path'] =
                    $cdrFilename;

            } catch (\Throwable $e) {
                Log::error(
                    'Falló la descarga del CDR de Nota de Crédito',
                    [
                        'credit_note_id' =>
                            $creditNote->id,

                        'status' =>
                            $creditNote->status,

                        'url' =>
                            $cdrUrl,

                        'error' =>
                            $e->getMessage(),
                    ]
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 8. GUARDAR SOLO LOS ARCHIVOS VALIDADOS
        |--------------------------------------------------------------------------
        */
        if (!empty($filesToUpdate)) {
            $creditNote->update(
                $filesToUpdate
            );
        }

        Log::info(
            'Resultado de Nota de Crédito persistido',
            [
                'credit_note_id' =>
                    $creditNote->id,

                'sale_id' =>
                    $creditNote->sale_id,

                'status' =>
                    $creditNote->status,

                'sunat_responsecode' =>
                    $creditNote->sunat_responsecode,

                'serie' =>
                    $creditNote->serie,

                'numero' =>
                    $creditNote->numero,

                'pdf_downloaded' =>
                    isset($filesToUpdate['pdf_path']),

                'xml_downloaded' =>
                    isset($filesToUpdate['xml_path']),

                'cdr_downloaded' =>
                    isset($filesToUpdate['cdr_path']),
            ]
        );
    }

    private function buildNubefactConsultCreditNoteData(CreditNote $creditNote): array
    {
        if (empty($creditNote->serie) || empty($creditNote->numero)) {
            throw new \Exception('La Nota de Crédito no tiene serie o número para consultar.');
        }

        return [
            "operacion" => "consultar_comprobante",
            "tipo_de_comprobante" => 3,
            "serie" => $creditNote->serie,
            "numero" => (int) $creditNote->numero,
        ];
    }

    private function consultarNotaCreditoNubefactO(CreditNote $creditNote): array
    {
        $data = $this->buildNubefactConsultCreditNoteData($creditNote);

        //dd($data);

        $token = config('services.nubefact.token');
        $url   = config('services.nubefact.url');

        if (!$token || !$url) {
            throw new \Exception('Faltan credenciales Nubefact.');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Token token=' . $token,
            'Content-Type'  => 'application/json',
        ])->post($url, $data);

        $result = $response->json();

        if (!$response->ok()) {
            $msg = is_array($result) ? json_encode($result) : $response->body();
            throw new \Exception('Error HTTP consultando Nota de Crédito en Nubefact: ' . $msg);
        }

        if (isset($result['errors'])) {
            throw new \Exception(
                is_array($result['errors'])
                    ? json_encode($result['errors'], JSON_UNESCAPED_UNICODE)
                    : $result['errors']
            );
        }

        return $result;
    }

    private function consultarNotaCreditoNubefact(
        CreditNote $creditNote
    ): array {
        $data = $this->buildNubefactConsultCreditNoteData(
            $creditNote
        );

        $token = config('services.nubefact.token');
        $url = config('services.nubefact.url');

        if (!$token || !$url) {
            throw new \RuntimeException(
                'Faltan credenciales Nubefact.'
            );
        }

        Log::info(
            'Consultando Nota de Crédito en Nubefact',
            [
                'credit_note_id' => $creditNote->id,
                'sale_id' => $creditNote->sale_id,
                'payload' => $data,
            ]
        );

        try {
            $response = Http::withHeaders([
                'Authorization' =>
                    'Token token=' . $token,

                'Accept' =>
                    'application/json',

                'Content-Type' =>
                    'application/json',
            ])
                ->timeout(60)
                ->post($url, $data);

        } catch (\Throwable $e) {
            Log::error(
                'Error de conexión consultando Nota de Crédito',
                [
                    'credit_note_id' => $creditNote->id,
                    'sale_id' => $creditNote->sale_id,
                    'error' => $e->getMessage(),
                ]
            );

            throw new \RuntimeException(
                'No se pudo establecer conexión con Nubefact: ' .
                $e->getMessage()
            );
        }

        $result = $response->json();

        if (!$response->successful()) {
            $message = is_array($result)
                ? json_encode(
                    $result,
                    JSON_UNESCAPED_UNICODE
                )
                : substr(
                    $response->body(),
                    0,
                    1000
                );

            Log::error(
                'Nubefact respondió error al consultar Nota de Crédito',
                [
                    'credit_note_id' => $creditNote->id,
                    'sale_id' => $creditNote->sale_id,
                    'http_status' => $response->status(),
                    'response' => $message,
                ]
            );

            throw new \RuntimeException(
                'Nubefact respondió con HTTP ' .
                $response->status() .
                ': ' .
                $message
            );
        }

        if (!is_array($result)) {
            Log::error(
                'Nubefact devolvió una respuesta inválida al consultar Nota de Crédito',
                [
                    'credit_note_id' => $creditNote->id,
                    'sale_id' => $creditNote->sale_id,
                    'response' => substr(
                        $response->body(),
                        0,
                        1000
                    ),
                ]
            );

            throw new \RuntimeException(
                'Nubefact devolvió una respuesta inválida al consultar la Nota de Crédito.'
            );
        }

        if (!empty($result['errors'])) {
            $errors = is_array($result['errors'])
                ? json_encode(
                    $result['errors'],
                    JSON_UNESCAPED_UNICODE
                )
                : (string) $result['errors'];

            throw new \RuntimeException(
                'Error desde Nubefact: ' . $errors
            );
        }

        Log::info(
            'Respuesta consulta Nota de Crédito Nubefact',
            [
                'credit_note_id' => $creditNote->id,
                'sale_id' => $creditNote->sale_id,
                'result' => $result,
            ]
        );

        /*
         * Algunas respuestas de Nubefact pueden traer la información
         * real del comprobante dentro de "invoice".
         */
        if (
            isset($result['invoice']) &&
            is_array($result['invoice'])
        ) {
            $result = array_merge(
                $result,
                $result['invoice']
            );
        }

        /*
         * Compatibilidad defensiva por si la respuesta viene dentro de "data".
         */
        if (
            isset($result['data']) &&
            is_array($result['data'])
        ) {
            $result = array_merge(
                $result,
                $result['data']
            );
        }

        return $result;
    }

    private function generarNotaCreditoParcialNubefact(Sale $sale, CreditNote $creditNote): array
    {
        $data = $this->buildNubefactCreditNotePartialData($sale, $creditNote);

        $token = config('services.nubefact.token');
        $url   = config('services.nubefact.url');

        if (!$token || !$url) {
            throw new \Exception('Faltan credenciales Nubefact.');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Token token=' . $token,
            'Content-Type'  => 'application/json',
        ])->post($url, $data);

        $result = $response->json();

        if (!$response->ok()) {
            $msg = is_array($result)
                ? json_encode($result, JSON_UNESCAPED_UNICODE)
                : $response->body();

            throw new \Exception('Nubefact respondió error HTTP al generar Nota de Crédito parcial: ' . $msg);
        }

        if (isset($result['errors'])) {
            throw new \Exception(
                is_array($result['errors'])
                    ? json_encode($result['errors'], JSON_UNESCAPED_UNICODE)
                    : $result['errors']
            );
        }

        return $result;
    }

    private function buildNubefactCreditNotePartialData(Sale $sale, CreditNote $creditNote): array
    {
        $creditNote->loadMissing(['details']);

        if ($creditNote->details->isEmpty()) {
            throw new \Exception('La Nota de Crédito parcial no tiene detalles.');
        }

        $isFactura = $sale->type_document === '01';

        $serieNotaCredito = $isFactura
            ? config('services.nubefact.serie_nc_factura', 'FFF1')
            : config('services.nubefact.serie_nc_boleta', 'BBB1');

        $items = $creditNote->details->map(function ($detail) {
            return [
                "unidad_de_medida" => "NIU",
                "codigo" => "",
                "descripcion" => $detail->description,
                "cantidad" => (float) $detail->quantity,
                "valor_unitario" => (float) $detail->valor_unitario,
                "precio_unitario" => (float) $detail->price,
                "subtotal" => (float) $detail->subtotal,
                "tipo_de_igv" => "1",
                "igv" => (float) $detail->igv,
                "total" => (float) $detail->total,
            ];
        })->toArray();

        return [
            "operacion" => "generar_comprobante",
            "tipo_de_comprobante" => "3",
            "serie" => $serieNotaCredito,
            "numero" => "",
            "codigo_unico" => 'NC-PARCIAL-' . $sale->id . '-' . now()->timestamp . '-' . Str::random(8),

            "sunat_transaction" => "1",

            "cliente_tipo_de_documento" => $sale->tipo_documento_cliente,
            "cliente_numero_de_documento" => $sale->numero_documento_cliente,
            "cliente_denominacion" => $sale->nombre_cliente,
            "cliente_direccion" => $sale->direccion_cliente ?: "",
            "cliente_email" => $sale->email_cliente ?: "",

            "fecha_de_emision" => now()->format('d-m-Y'),
            "moneda" => "1",
            "porcentaje_de_igv" => 18.00,

            "tipo_de_nota_de_credito" => $creditNote->reason_code ?: "07",
            "motivo_o_sustento_de_nota_de_credito" => $creditNote->reason_description ?: "Devolución parcial",

            "documento_que_se_modifica_tipo" => $sale->type_document === '01' ? "1" : "2",
            "documento_que_se_modifica_serie" => $sale->serie_sunat,
            "documento_que_se_modifica_numero" => $sale->numero,

            "total_gravada" => (float) $creditNote->op_gravada,
            "total_igv" => (float) $creditNote->igv,
            "total" => (float) $creditNote->importe_total,
            "total_a_pagar" => (float) $creditNote->importe_total,

            "items" => $items,
        ];
    }

    private function descargarArchivoNubefactSeguro(string $url,string $rutaDestino,string $tipoArchivo,int $maxIntentos = 3): void {
        $ultimoError = 'Nubefact devolvió una respuesta inválida.';

        for ($intento = 1; $intento <= $maxIntentos; $intento++) {
            try {
                $response = Http::timeout(60)
                    ->get($url);

                $contenido = $response->body();

                if (
                    $response->successful() &&
                    $this->contenidoNubefactEsValido(
                        $contenido,
                        $tipoArchivo,
                        $response->header('Content-Type')
                    )
                ) {
                    $directorio = dirname($rutaDestino);

                    if (!is_dir($directorio)) {
                        if (!mkdir($directorio, 0755, true) && !is_dir($directorio)) {
                            throw new \RuntimeException(
                                'No se pudo crear el directorio: ' . $directorio
                            );
                        }
                    }

                    /*
                     * Primero se escribe en un archivo temporal.
                     * De esta forma nunca sobrescribimos un archivo válido
                     * con una respuesta incompleta o incorrecta.
                     */
                    $rutaTemporal = $rutaDestino . '.tmp';

                    if (file_exists($rutaTemporal)) {
                        @unlink($rutaTemporal);
                    }

                    $bytesEscritos = file_put_contents(
                        $rutaTemporal,
                        $contenido,
                        LOCK_EX
                    );

                    if ($bytesEscritos === false || $bytesEscritos === 0) {
                        @unlink($rutaTemporal);

                        throw new \RuntimeException(
                            'No se pudo escribir el archivo temporal.'
                        );
                    }

                    if (!rename($rutaTemporal, $rutaDestino)) {
                        @unlink($rutaTemporal);

                        throw new \RuntimeException(
                            'No se pudo mover el archivo temporal a su ubicación final.'
                        );
                    }

                    @chmod($rutaDestino, 0664);

                    Log::info('Archivo de Nubefact descargado correctamente', [
                        'tipo' => $tipoArchivo,
                        'ruta' => $rutaDestino,
                        'bytes' => $bytesEscritos,
                        'intento' => $intento,
                    ]);

                    return;
                }

                $ultimoError = sprintf(
                    'HTTP %s, Content-Type: %s, respuesta: %s',
                    $response->status(),
                    $response->header('Content-Type') ?: 'no informado',
                    substr(
                        trim(strip_tags($contenido)),
                        0,
                        300
                    )
                );

                Log::warning('Nubefact devolvió un archivo inválido', [
                    'tipo' => $tipoArchivo,
                    'url' => $url,
                    'intento' => $intento,
                    'http_status' => $response->status(),
                    'content_type' => $response->header('Content-Type'),
                    'body_preview' => substr($contenido, 0, 500),
                ]);
            } catch (\Throwable $e) {
                $ultimoError = $e->getMessage();

                Log::warning('Error al descargar archivo de Nubefact', [
                    'tipo' => $tipoArchivo,
                    'url' => $url,
                    'intento' => $intento,
                    'error' => $e->getMessage(),
                ]);
            }

            if ($intento < $maxIntentos) {
                /*
                 * Esperas progresivas:
                 * intento 1: espera 2 segundos
                 * intento 2: espera 4 segundos
                 */
                sleep($intento * 2);
            }
        }

        throw new \RuntimeException(
            'No se pudo descargar un archivo '
            . strtoupper($tipoArchivo)
            . ' válido desde Nubefact. '
            . $ultimoError
        );
    }

    private function contenidoNubefactEsValido(string $contenido,string $tipoArchivo,?string $contentType = null): bool {
        if ($contenido === '') {
            return false;
        }

        $tipoArchivo = strtolower($tipoArchivo);
        $contentType = strtolower((string) $contentType);

        /*
         * Evitamos aceptar páginas HTML de error como:
         * 502 Bad Gateway
         * 503 Service Unavailable
         * 504 Gateway Timeout
         */
        if (
            strpos($contentType, 'text/html') !== false ||
            stripos(ltrim($contenido), '<!doctype html') === 0 ||
            stripos(ltrim($contenido), '<html') === 0
        ) {
            return false;
        }

        if ($tipoArchivo === 'pdf') {
            return substr($contenido, 0, 5) === '%PDF-';
        }

        if ($tipoArchivo === 'zip') {
            /*
             * Los archivos ZIP comienzan normalmente con PK.
             * El CDR de Nubefact se descarga como ZIP.
             */
            return substr($contenido, 0, 2) === 'PK';
        }

        if ($tipoArchivo === 'xml') {
            $contenidoLimpio = ltrim($contenido);

            libxml_use_internal_errors(true);

            $xml = simplexml_load_string($contenidoLimpio);

            libxml_clear_errors();

            return $xml !== false;
        }

        return false;
    }

    private function consultarComprobanteNubefact(Sale $sale): array
    {
        if (!in_array($sale->type_document, ['01', '03'])) {
            throw new \RuntimeException(
                'La venta no corresponde a una boleta o factura electrónica.'
            );
        }

        if (empty($sale->serie_sunat) || empty($sale->numero)) {
            throw new \RuntimeException(
                'La venta no tiene serie o número SUNAT para consultar el comprobante.'
            );
        }

        $token = config('services.nubefact.token');
        $url = config('services.nubefact.url');

        if (empty($token) || empty($url)) {
            throw new \RuntimeException(
                'Faltan las credenciales de Nubefact.'
            );
        }

        /*
         * Nubefact utiliza:
         * 1 = Factura
         * 2 = Boleta
         *
         * En nuestra base:
         * 01 = Factura
         * 03 = Boleta
         */
        $tipoComprobanteNubefact =
            $sale->type_document === '01' ? 1 : 2;

        $data = [
            'operacion' => 'consultar_comprobante',
            'tipo_de_comprobante' => $tipoComprobanteNubefact,
            'serie' => $sale->serie_sunat,
            'numero' => (int) $sale->numero,
        ];

        try {
            /*
             * Compatible con Laravel 7:
             * no usamos connectTimeout().
             */
            $response = Http::withHeaders([
                'Authorization' => 'Token token=' . $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
                ->timeout(60)
                ->post($url, $data);

        } catch (\Throwable $e) {
            Log::error(
                'No se pudo conectar con Nubefact al consultar comprobante',
                [
                    'sale_id' => $sale->id,
                    'serie' => $sale->serie_sunat,
                    'numero' => $sale->numero,
                    'error' => $e->getMessage(),
                ]
            );

            throw new \RuntimeException(
                'No se pudo establecer conexión con Nubefact: '
                . $e->getMessage()
            );
        }

        $result = $response->json();

        if (!$response->successful()) {
            $mensaje = is_array($result)
                ? json_encode(
                    $result,
                    JSON_UNESCAPED_UNICODE
                )
                : substr($response->body(), 0, 1000);

            Log::error(
                'Nubefact respondió error al consultar comprobante',
                [
                    'sale_id' => $sale->id,
                    'http_status' => $response->status(),
                    'response' => $mensaje,
                ]
            );

            throw new \RuntimeException(
                'Nubefact respondió con HTTP '
                . $response->status()
                . ': '
                . $mensaje
            );
        }

        if (!is_array($result)) {
            Log::error(
                'Nubefact devolvió una respuesta inválida al consultar comprobante',
                [
                    'sale_id' => $sale->id,
                    'http_status' => $response->status(),
                    'response' => substr(
                        $response->body(),
                        0,
                        1000
                    ),
                ]
            );

            throw new \RuntimeException(
                'Nubefact devolvió una respuesta inválida.'
            );
        }

        if (!empty($result['errors'])) {
            $errores = is_array($result['errors'])
                ? json_encode(
                    $result['errors'],
                    JSON_UNESCAPED_UNICODE
                )
                : (string) $result['errors'];

            throw new \RuntimeException(
                'Error desde Nubefact: ' . $errores
            );
        }

        /*
         * Para este proceso necesitamos al menos uno de los enlaces.
         */
        if (
            empty($result['enlace_del_pdf']) &&
            empty($result['enlace_del_xml']) &&
            empty($result['enlace_del_cdr'])
        ) {
            Log::warning(
                'La consulta del comprobante no devolvió enlaces',
                [
                    'sale_id' => $sale->id,
                    'result' => $result,
                ]
            );

            throw new \RuntimeException(
                'Nubefact encontró el comprobante, pero todavía no devolvió enlaces de descarga.'
            );
        }

        return $result;
    }
}
