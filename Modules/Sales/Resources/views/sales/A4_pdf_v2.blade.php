<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 12px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #24324b;
            font-size: 10px;
            margin: 0;
        }

        .clearfix:after {
            content: "";
            display: table;
            clear: both;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .page {
            width: 100%;
            padding-bottom: 28px;
        }

        .muted {
            color: #64748b;
        }

        .header-band {
            background: #5b6df6;
            color: #ffffff;
            border-radius: 22px 22px 0 0;
            overflow: hidden;
        }

        .header-band td {
            vertical-align: top;
        }

        .header-content {
            padding: 14px 18px 10px 18px;
        }

        .header-accent {
            height: 4px;
            background: #d9ddff;
        }

        .logo {
            max-width: 84px;
            max-height: 34px;
        }

        .brand-kicker {
            font-size: 11px;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            font-weight: bold;
            opacity: 0.86;
        }

        .brand-title {
            margin-top: 4px;
            font-size: 24px;
            line-height: 0.95;
            font-weight: bold;
            text-transform: uppercase;
        }

        .brand-web {
            margin-top: 4px;
            font-size: 10px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #9df7b9;
            font-weight: bold;
        }

        .brand-meta {
            display: none;
        }

        .brand-visual {
            margin-left: auto;
            width: 200px;
            height: 86px;
            padding: 0;
        }

        .visual-stage {
            position: relative;
            width: 100%;
            height: 86px;
        }

        .visual-panel {
            position: absolute;
            left: 18px;
            top: 20px;
            width: 78px;
            height: 44px;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.22);
        }

        .visual-desk {
            position: absolute;
            left: 8px;
            right: 16px;
            bottom: 16px;
            height: 8px;
            border-radius: 999px;
            background: #3741b7;
        }

        .visual-avatar {
            position: absolute;
            right: 18px;
            top: 10px;
            width: 54px;
            height: 54px;
            border-radius: 16px;
            background: #86efe2;
        }

        .visual-avatar::before {
            content: '';
            position: absolute;
            top: 7px;
            left: 17px;
            width: 20px;
            height: 20px;
            border-radius: 999px;
            background: #1e293b;
        }

        .visual-avatar::after {
            content: '';
            position: absolute;
            left: 12px;
            bottom: 8px;
            width: 30px;
            height: 18px;
            border-radius: 14px 14px 10px 10px;
            background: #ff7a59;
        }

        .visual-dot {
            position: absolute;
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.55);
        }

        .visual-dot.dot-a {
            left: 122px;
            top: 12px;
        }

        .visual-dot.dot-b {
            left: 132px;
            top: 24px;
        }

        .visual-dot.dot-c {
            left: 144px;
            top: 16px;
        }

        .brand-visual img {
            position: absolute;
            left: 22px;
            top: 24px;
        }

        .brand-chip {
            display: none;
        }

        .doc-strip {
            border: 1px solid #dbe5ff;
            border-top: none;
            border-radius: 0 0 22px 22px;
            background: #ffffff;
            padding: 12px 16px;
        }

        .doc-title {
            font-size: 18px;
            font-weight: bold;
            color: #2d3ea8;
            text-transform: uppercase;
        }

        .doc-number {
            margin-top: 2px;
            font-size: 11px;
            font-weight: bold;
            color: #5b6df6;
        }

        .doc-meta-label {
            font-size: 9px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #7b88a8;
            font-weight: bold;
        }

        .doc-meta-value {
            margin-top: 2px;
            font-size: 11px;
            font-weight: bold;
            color: #24324b;
        }

        .doc-meta-note {
            margin-top: 2px;
            font-size: 9px;
            color: #6b7280;
        }

        .doc-box {
            width: 250px;
            margin-left: auto;
            background: #f8fbff;
            color: #24324b;
            border-radius: 16px;
            padding: 18px;
            text-align: center;
            border: 2px solid #c7d2fe;
        }

        .doc-box .ruc {
            font-size: 12px;
            font-weight: bold;
            color: #5b6b84;
        }

        .doc-box .title {
            margin-top: 8px;
            font-size: 18px;
            font-weight: bold;
            color: #3347c7;
        }

        .doc-box .serie {
            margin-top: 10px;
            font-size: 16px;
            color: #4f46e5;
            font-weight: bold;
            background: #eef2ff;
            border-radius: 12px;
            padding: 8px 10px;
        }

        .section {
            margin-top: 10px;
        }

        .card {
            background: #fcfdff;
            border: 1px solid #dbe5ff;
            border-radius: 0;
            padding: 10px 12px;
        }

        .section-title {
            font-size: 9px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #4f46e5;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .meta-table td {
            vertical-align: top;
            padding: 2px 0;
            font-size: 10px;
        }

        .meta-label {
            width: 100px;
            color: #6b7280;
        }

        .items {
            margin-top: 10px;
        }

        .items thead td {
            background: #ede9fe;
            color: #3730a3;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 7px 7px;
            border-bottom: 1px solid #d8d4fe;
        }

        .items tbody td {
            border-bottom: 1px solid #dbe5ff;
            padding: 7px 7px;
            font-size: 10px;
            vertical-align: top;
        }

        .amount {
            text-align: right;
            white-space: nowrap;
        }

        .code {
            color: #64748b;
            font-size: 11px;
        }

        .summary-box {
            width: 270px;
            margin-left: auto;
            background: #f8fbff;
            color: #24324b;
            border-radius: 0;
            padding: 8px 10px;
            border: 1px solid #c7d2fe;
            border-top: 3px solid #5b6df6;
        }

        .summary-box table td {
            padding: 3px 0;
            font-size: 10px;
        }

        .summary-box .grand-total td {
            border-top: 1px solid #c7d2fe;
            padding-top: 6px;
            font-size: 11px;
            font-weight: bold;
            color: #3347c7;
        }

        .info-grid td {
            vertical-align: top;
        }

        .info-card {
            border: 1px solid #dbe5ff;
            border-radius: 0;
            padding: 10px 12px;
            background: #ffffff;
        }

        .binary-strip {
            margin-top: 8px;
            font-size: 18px;
            letter-spacing: 0.06em;
            color: #eef2ff;
            font-weight: bold;
            line-height: 1;
            word-break: break-all;
        }

        .qr-box {
            text-align: center;
        }

        .qr-box img {
            width: 90px;
            height: 90px;
        }

        .footer-note {
            color: #5b6b84;
            font-size: 9px;
            line-height: 1.6;
        }

        .bank-cell {
            width: 50%;
            vertical-align: top;
            padding: 8px 6px 0 0;
        }

        .bank-card {
            border: 1px solid #dbe5ff;
            border-radius: 0;
            padding: 10px;
            min-height: 72px;
            background: #ffffff;
            border-top: 3px solid #a5b4fc;
        }

        .bank-logo-cell {
            width: 54px;
            vertical-align: top;
        }

        .bank-logo {
            max-width: 40px;
            max-height: 40px;
        }

        .bank-title {
            font-size: 12px;
            font-weight: bold;
        }

        .bank-subtitle,
        .bank-detail {
            font-size: 9px;
            color: #5b6b84;
        }
        .example-header {
            padding: 6px 0;
            margin-bottom: 10px;
            border-bottom: 1px solid #aaaaaa;
        }

        .example-header #logo {
            float: left;
            margin-top: 8px;
        }

        .example-header #logo img {
            height: 52px;
            max-width: 150px;
            object-fit: contain;
        }

        .example-header #company {
            float: right;
            text-align: right;
            font-size: 9px;
            color: #555555;
        }

        .example-header #company .name {
            margin: 0 0 3px 0;
            color: #0087c3;
            font-size: 18px;
            font-weight: normal;
        }

        .example-details {
            margin-bottom: 10px;
        }

        .example-details #client {
            float: left;
            width: 56%;
            padding-left: 2px;
            border-left: 4px solid #0087c3;
        }

        .example-details #client .to {
            color: #777777;
            font-size: 9px;
            margin-bottom: 1px;
        }

        .example-details #client .name {
            font-size: 14px;
            font-weight: normal;
            margin: 0 0 2px 0;
            color: #333333;
        }

        .example-details #client .line {
            font-size: 9px;
            color: #666666;
            line-height: 1.1;
            margin: 0;
        }

        .example-details #invoice {
            float: right;
            width: 38%;
            text-align: right;
        }

        .example-details #invoice h1 {
            color: #0087c3;
            font-size: 24px;
            line-height: 1em;
            font-weight: normal;
            margin: 0 0 2px 0;
        }

        .example-details #invoice .date {
            font-size: 9px;
            color: #777777;
            line-height: 1.1;
            margin: 0;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            margin-bottom: 10px;
        }

        .invoice-table th,
        .invoice-table td {
            padding: 6px 5px;
            background: #eeeeee;
            border-bottom: 1px solid #ffffff;
        }

        .invoice-table thead th {
            white-space: nowrap;
            font-weight: normal;
            font-size: 9px;
            text-align: center;
            color: #555555;
        }

        .invoice-table tbody td {
            text-align: right;
            font-size: 9px;
            color: #555555;
        }

        .invoice-table tbody tr:last-child td {
            border-bottom: none;
        }

        .invoice-table .no {
            width: 28px;
            color: #ffffff;
            font-size: 11px;
            text-align: center;
            background: #57b223;
        }

        .invoice-table .desc {
            text-align: left;
        }

        .invoice-table .unit {
            background: #dddddd;
            text-align: center;
        }

        .invoice-table .qty {
            text-align: center;
        }

        .invoice-table .total {
            background: #57b223;
            color: #ffffff;
        }

        .invoice-table .desc-title {
            color: #57b223;
            font-size: 11px;
            font-weight: normal;
            margin: 0 0 2px 0;
        }

        .invoice-table .desc-sub {
            font-size: 8px;
            color: #666666;
            line-height: 1.2;
        }

        .invoice-table tfoot td {
            padding: 4px 5px;
            background: #ffffff;
            border-bottom: none;
            font-size: 9px;
            white-space: nowrap;
            border-top: 1px solid #aaaaaa;
            text-align: right;
            color: #555555;
        }

        .invoice-table tfoot tr:first-child td {
            border-top: none;
        }

        .invoice-table tfoot tr:last-child td {
            color: #57b223;
            font-size: 11px;
            border-top: 1px solid #57b223;
        }

        .invoice-table tfoot tr td:first-child {
            border: none;
            background: transparent;
        }

        .aux-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }

        .aux-grid td {
            vertical-align: top;
            background: transparent;
            border: none;
            padding: 0;
        }

        #thanks {
            font-size: 18px;
            margin-bottom: 8px;
            color: #333333;
        }

        #notices {
            padding-left: 5px;
            border-left: 4px solid #0087c3;
        }

        #notices .label {
            font-size: 9px;
            color: #777777;
            margin-bottom: 4px;
        }

        #notices .notice {
            font-size: 8px;
            line-height: 1.25;
            color: #555555;
        }

        .document-card {
            border: 1px solid #dddddd;
            padding: 6px;
            text-align: left;
        }

        .document-card .title {
            font-size: 9px;
            color: #777777;
            margin-bottom: 4px;
        }

        .document-card .body {
            font-size: 8px;
            line-height: 1.25;
            color: #555555;
        }

        .document-footer {
            color: #777777;
            width: 100%;
            border-top: 1px solid #aaaaaa;
            padding: 5px 0;
            text-align: center;
            font-size: 9px;
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            background: #f1f5f9;
        }

        .document-footer .sep {
            color: #cbd5e1;
            padding: 0 8px;
        }
    </style>
</head>

<body>
    @php
        $numberletters = new \App\Helpers\NumberLetter();
        $brandImage = $company->isotipo === '/img/isotipo.png'
            ? public_path($company->isotipo)
            : public_path('storage' . DIRECTORY_SEPARATOR . $company->isotipo);
        if (!is_file($brandImage)) {
            $brandImage = $company->logo_document === '/img/logo176x32.png'
                ? public_path($company->logo_document)
                : public_path('storage' . DIRECTORY_SEPARATOR . $company->logo_document);
        }
    @endphp

    <div class="page">
        <header class="example-header clearfix">
            <div id="logo">
                @if (is_file($brandImage))
                    <img src="{{ $brandImage }}">
                @endif
            </div>
            <div id="company">
                <h2 class="name">{{ $company->business_name }}</h2>
                <div>RUC {{ $company->ruc }}</div>
                <div>{{ $local?->address ?? $company->fiscal_address }}</div>
                <div>{{ $local?->description ?? 'Establecimiento principal' }}</div>
                <div>{{ env('APP_URL') }}</div>
            </div>
        </header>

        <main>
            <div id="details" class="example-details clearfix">
                <div id="client">
                    <div class="to">CLIENTE:</div>
                    <h2 class="name">{{ $client?->full_name ?? 'Cliente genérico' }}</h2>
                    <div class="line">Documento: {{ $client?->number ?? '-' }}</div>
                    <div class="line">{{ $client?->address ?? '-' }}</div>
                    <div class="line">Local: {{ $local?->description ?? '-' }}</div>
                </div>
                <div id="invoice">
                    <h1>NOTA DE VENTA</h1>
                    <div class="date">Nro: {{ $document->description }}-{{ $document->number }}</div>
                    <div class="date">Fecha: {{ \Carbon\Carbon::parse($document->created_at)->format('d/m/Y H:i') }}</div>
                    <div class="date">Vendedor: {{ $seller?->name ?? '-' }}</div>
                </div>
            </div>

            <table class="invoice-table">
                <thead>
                    <tr>
                        <th class="no">#</th>
                        <th class="desc">DESCRIPCION</th>
                        <th class="unit">UNIDAD</th>
                        <th class="qty">CANT.</th>
                        <th class="total">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $index => $item)
                        @php
                            $json = json_decode($item->saleProduct, true);
                            $unitType = $json['unit_type'] ?? '-';
                            $size = $json['size'] ?? null;
                            $interne = $json['interne'] ?? null;
                            $productData = json_decode($item->product, true) ?? [];
                        @endphp
                        <tr>
                            <td class="no">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</td>
                            <td class="desc">
                                <div class="desc-title">{{ $productData['description'] ?? 'Producto / servicio' }}</div>
                                <div class="desc-sub">Codigo: {{ $interne ?: '-' }}</div>
                                @if ($size)
                                    <div class="desc-sub">Talla / presentación: {{ $size }}</div>
                                @endif
                            </td>
                            <td class="unit">{{ $unitType }}</td>
                            <td class="qty">{{ (int) $item->quantity }}</td>
                            <td class="total">S/ {{ number_format($item->total, 2, '.', ',') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2"></td>
                        <td colspan="2">TOTAL</td>
                        <td>S/ {{ number_format($sale->total, 2, '.', ',') }}</td>
                    </tr>
                </tfoot>
            </table>

            <table class="aux-grid">
                <tr>
                    <td style="width: 64%; padding-right: 16px;">
                        <div id="thanks">Gracias</div>
                        <div id="notices">
                            <div class="label">INFORMACION ADICIONAL:</div>
                            <div class="notice">
                                <strong>Importe en letras:</strong> {{ $numberletters->convertToLetter($sale->total) }}<br>
                                <strong>Vendedor:</strong> {{ $seller?->name ?? '-' }}<br>
                                <strong>Sitio web:</strong> {{ env('APP_URL') }}<br>
                                Gracias por su compra. Esta representación impresa corresponde a la nota de venta emitida en el sistema.
                            </div>
                        </div>
                    </td>
                    <td style="width: 36%;">
                        <div class="document-card">
                            <div class="title">DOCUMENTO</div>
                            <div class="body">
                                <strong>{{ $document->description }}-{{ $document->number }}</strong><br>
                                Fecha: {{ \Carbon\Carbon::parse($document->created_at)->format('d/m/Y H:i') }}<br>
                                Local: {{ $local?->description ?? '-' }}
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </main>

        <div class="document-footer">
            {{ $company->tradename ?? $company->business_name }}
            <span class="sep">|</span>
            {{ $company->email }}
            <span class="sep">|</span>
            {{ $company->telephone }}
        </div>
    </div>
</body>

</html>
