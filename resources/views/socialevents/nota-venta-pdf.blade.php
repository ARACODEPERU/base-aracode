<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Nota de venta {{ $document->invoice_serie }}-{{ $document->invoice_correlative }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #1f2937; padding-bottom: 12px; }
        .brand { font-size: 20px; font-weight: 800; }
        .muted { color: #6b7280; }
        .box { border: 1px solid #d1d5db; border-radius: 6px; padding: 10px; margin-top: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        th { background: #f3f4f6; font-size: 11px; text-transform: uppercase; }
        .right { text-align: right; }
        .total { font-size: 16px; font-weight: 800; }
        .footer { margin-top: 24px; font-size: 10px; color: #6b7280; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="brand">{{ $company->business_name ?? $company->name ?? 'ARACODE' }}</div>
            <div class="muted">{{ $company->ruc ?? '' }}</div>
            <div class="muted">{{ $company->fiscal_address ?? '' }}</div>
        </div>
        <div class="right">
            <div style="font-size: 14px; font-weight: 800;">NOTA DE VENTA</div>
            <div>{{ $document->invoice_serie }}-{{ str_pad((string) $document->invoice_correlative, 9, '0', STR_PAD_LEFT) }}</div>
            <div class="muted">{{ \Carbon\Carbon::parse($document->invoice_broadcast_date)->format('d/m/Y') }}</div>
        </div>
    </div>

    <div class="box">
        <strong>Cliente</strong><br>
        {{ $document->client_rzn_social }}<br>
        <span class="muted">{{ $document->client_type_doc }}-{{ $document->client_number }}</span>
        @if ($document->client_address)
            <br><span class="muted">{{ $document->client_address }}</span>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Descripción</th>
                <th class="right">Cantidad</th>
                <th class="right">Precio</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td>{{ $item->decription_product }}</td>
                    <td class="right">{{ number_format((float) $item->quantity, 2) }}</td>
                    <td class="right">S/ {{ number_format((float) $item->mto_price_unit, 2) }}</td>
                    <td class="right">S/ {{ number_format((float) $item->mto_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="right" style="margin-top: 16px;">
        <div class="total">Total: S/ {{ number_format((float) $document->overall_total, 2) }}</div>
    </div>

    <div class="footer">
        Documento interno de venta. No constituye factura ni boleta electrónica.
    </div>
</body>
</html>
