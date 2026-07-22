<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 8pt; }
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 0; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        .line { border-top: 1px dashed #000; margin: 6px 0; }
        .line2 { text-align: center; margin: 6px 0; font-family: monospace; font-weight: bold; letter-spacing: 2px; }
        .line2::after { content: "***********************"; }
        p { margin: 0; padding: 2px 0; }
        img { max-width: 100px; height: auto; display: block; margin: 0 auto; }
        .row { display: flex; justify-content: space-between; align-items: baseline; }
        .h5 { font-size: 14px; }
        .muted { color: #555; font-size: 11px; }
        * { page-break-inside: avoid; page-break-before: auto; page-break-after: auto; }
        /* ancho típico 80mm: 226.8pt. Si tu motor lo respeta, puedes fijarlo: */
        .ticket { width: 210.8pt; margin: 0 auto; }
        /* filas de items */
        .item-row { display: flex; justify-content: space-between; align-items: baseline; gap: 6px; }
        .item-left { flex: 1; }
        .item-right { flex: 0 0 auto; min-width: 70px; text-align: right; }
        .muted { color: #555; font-size: 11px; }
        .sec-title { font-weight: bold; margin-top: 4px; }

        .total {
            font-weight: bold;
            text-align: right;
        }
        .right {
            text-align: right;
        }
        .full-width {
            width: 100%;
        }

        .table-operations {
            width: 100%;
            border-collapse: collapse;
        }

        .table-operations td {
            border: none;
            padding: 2px 0;
        }
    </style>
</head>
<body>
<div class="ticket">
    <div style="text-align:center;">
        <img
                src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/logo/' . $logotipoBNEmpresa))) }}"
                alt="Logo de Tienda"
                style="display:block; margin:0 auto;"
        >
    </div>
    <p class="centered header" style="text-align: center">{{ $nameEmpresa }}<br>
        R.U.C.: {{ $ruc }}<br>
        TICKET DE VENTA</p>
    <p class="centered address bold text-sm" style="text-align: center">{{ $address }}</p>
    <div class="line"></div>

    <p><b>Fecha:</b> {{ now()->format('d/m/Y H:i') }}</p>

    <div class="line"></div>

    @forelse ($sale->details as $detail)
        @php
            $total  = number_format($detail->total, 2, '.', '');
            $nombre = optional($detail->stockItem)->display_name
                        ?? optional($detail->material)->full_name
                        ?? ($detail->description ?: 'Producto');

            $hasPresentation = !empty($detail->material_presentation_id)
                && !empty($detail->packs)
                && !empty($detail->units_per_pack);

            if ($hasPresentation) {
                $presentationText = (int) $detail->units_per_pack . 'und';
                $qtyToShow = (int) $detail->packs;
            } else {
                $presentationText = '1und';
                $qtyToShow = rtrim(rtrim(number_format($detail->quantity,2,'.',''),'0'),'.');
            }

            $line = $nombre . ' (' . $presentationText . ') X ' . $qtyToShow;

            $maxChars = 27;
            $line1 = mb_substr($line, 0, $maxChars);
            $line2 = mb_substr($line, $maxChars);

            /*
             * Snapshot de ítems físicos vendidos.
             * Puede venir como array por cast, o como string JSON si aún no tiene cast.
             */
            $itemSnapshot = $detail->item_snapshot ?? [];

            if (is_string($itemSnapshot)) {
                $decodedSnapshot = json_decode($itemSnapshot, true);
                $itemSnapshot = is_array($decodedSnapshot) ? $decodedSnapshot : [];
            }

            if (!is_array($itemSnapshot)) {
                $itemSnapshot = [];
            }

            $itemCodes = collect($itemSnapshot)
                ->map(function ($item) {
                    if (is_array($item)) {
                        return $item['code'] ?? null;
                    }

                    if (is_object($item)) {
                        return $item->code ?? null;
                    }

                    return null;
                })
                ->filter()
                ->values()
                ->toArray();

            $itemsText = implode(', ', $itemCodes);
        @endphp

        {{-- Línea principal con precio --}}
        <p class="bold" style="font-size:11px; margin-bottom:0;">
            {{ $line1 }}
            <span style="float:right;">S/ {{ $total }}</span>
        </p>

        {{-- Resto del texto si excede --}}
        @if(mb_strlen($line2) > 0)
            <p class="bold" style="font-size:11px; margin-top:0; ">
                {{ $line2 }}
            </p>
        @endif

        {{-- Ítems físicos vendidos --}}
        @if(!empty($itemsText))
            <p style="font-size:9px; margin-top:0; margin-bottom:2px; line-height:1.15;">
                <strong>Ítems:</strong> {{ $itemsText }}
            </p>
        @endif

    @empty
        <p class="muted">— Sin productos registrados —</p>
    @endforelse

    <div class="line"></div>

    <table class="table-operations full-width">
        <tr>
            <td class="total"><b>TOTAL A PAGAR</b></td>
            <td class="total right">S/. {{ number_format($sale->importe_total, 2) }}</td>
        </tr>
    </table>

    <div class="line"></div>

    <p class="text-center" style="font-size: 18px; text-align: center">
        <b>{{ strtoupper($paymentLabel ?? '—') }}</b>
    </p>

    <div class="line"></div>

    <p class="bold right">Pago con: S/. {{ number_format($sale->importe_total+$sale->vuelto, 2) }}</p>
    <p class="bold right">Vuelto: S/. {{ number_format($sale->vuelto, 2) }}</p>

    <div class="line"></div>
    <table style="width:100%; border-collapse:collapse;">
        <colgroup>
            <col style="width:50%;">
            <col style="width:50%;">
        </colgroup>
        <tr>
            <!-- COLUMNA 1 -->
            <td style="vertical-align:top; padding-right:10px;">
                <p style="font-size:12px; font-weight:bold; margin:0 0 4px 0;">
                    {{ $titleCuenta1Empresa }}
                </p>

                <p style="margin:0;">
                    <b>Nro.:</b> {{ $nroCuenta1Empresa }}
                </p>

                @if(!empty($cciCuenta1Empresa))
                    <p style="margin:0;">
                        <b>CCI:</b> {{ $cciCuenta1Empresa }}
                    </p>
                @endif
            </td>

            <!-- COLUMNA 2 -->
            <td style="vertical-align:top; padding-left:10px;">
                <p style="font-size:12px; font-weight:bold; margin:0 0 4px 0;">
                    {{ $titleCuenta2Empresa }}
                </p>

                <p style="margin:0;">
                    <b>Nro.:</b> {{ $nroCuenta2Empresa }}
                </p>

                @if(!empty($cciCuenta2Empresa))
                    <p style="margin:0;">
                        <b>CCI:</b> {{ $cciCuenta2Empresa }}
                    </p>
                @endif
            </td>
        </tr>
    </table>

    <div class="line2"></div>

    <div class="text-center">
        {{--<p><b>Documento no válido como comprobante</b></p>--}}
        <p class="muted"><b>Sistema Punto de Venta desarrollado por www.venti360.com</b></p>
    </div>

    <div class="line2"></div>
</div>
</body>
</html>