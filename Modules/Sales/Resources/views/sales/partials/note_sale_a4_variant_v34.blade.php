<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    @php
        $templateVariant = $variant ?? '3';
        $dimensionBg = str_replace(DIRECTORY_SEPARATOR, '/', public_path('themes/invoice/example4/dimension.png'));
        $numberletters = new \App\Helpers\NumberLetter();
        $logo = $company->logo_document === '/img/logo176x32.png'
            ? public_path($company->logo_document)
            : public_path('storage' . DIRECTORY_SEPARATOR . $company->logo_document);
    @endphp
    <style>
        @page { margin: 12px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #001028;
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
            border-spacing: 0;
        }
        .page {
            position: relative;
            padding-bottom: 28px;
        }
        .invoice-table {
            margin: 10px 0 12px 0;
        }
        .invoice-table th,
        .invoice-table td {
            padding: 6px 7px;
        }
        .invoice-table th {
            font-weight: normal;
            font-size: 9px;
            color: #5d6975;
            border-bottom: 1px solid #c1ced9;
            text-align: center;
            white-space: nowrap;
        }
        .invoice-table td {
            font-size: 9px;
            color: #444;
            text-align: right;
            vertical-align: top;
        }
        .invoice-table .service,
        .invoice-table .desc {
            text-align: left;
        }
        .invoice-table .desc-title {
            font-size: 10px;
            margin-bottom: 2px;
        }
        .invoice-table .desc-sub {
            font-size: 8px;
            line-height: 1.25;
            color: #666;
        }
        .invoice-table tfoot td {
            padding: 4px 7px;
            font-size: 9px;
            text-align: right;
            border-top: 1px solid #c1ced9;
        }
        .invoice-table tfoot tr:first-child td {
            border-top: none;
        }
        .invoice-table tfoot tr:last-child td {
            font-size: 11px;
            font-weight: bold;
        }
        .support-grid {
            margin-top: 10px;
        }
        .support-grid td {
            vertical-align: top;
        }
        .support-box {
            border: 1px solid #d7dde4;
            padding: 8px;
        }
        .support-title {
            font-size: 9px;
            color: #5d6975;
            margin-bottom: 5px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .support-text {
            font-size: 8px;
            line-height: 1.3;
            color: #555;
        }
        .footer-line {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            border-top: 1px solid #c1ced9;
            padding: 5px 0;
            text-align: center;
            font-size: 9px;
            color: #5d6975;
            background: #f8fafc;
        }
        .footer-line .sep {
            color: #cbd5e1;
            padding: 0 8px;
        }

        @if ($templateVariant === '3')
        .doc-header {
            width: 100%;
            margin-bottom: 14px;
            padding: 8px 0;
        }
        .doc-logo {
            width: 56%;
            vertical-align: middle;
        }
        .doc-logo img {
            width: 150px;
            max-height: 58px;
            object-fit: contain;
        }
        .doc-meta {
            width: 44%;
            vertical-align: top;
            text-align: right;
        }
        .doc-title {
            color: #5d6975;
            font-size: 24px;
            line-height: 1.1;
            margin-bottom: 6px;
        }
        .doc-info {
            width: auto;
            margin-left: auto;
        }
        .doc-info td {
            padding: 1px 0 1px 10px;
            font-size: 10px;
            line-height: 1.25;
        }
        .doc-info .label {
            font-weight: bold;
            color: #5d6975;
            text-align: right;
        }
        .doc-info .value {
            color: #444;
        }
        .invoice-table tr:nth-child(2n-1) td {
            background: #eeeeee;
        }
        .invoice-table tr:last-child td {
            background: #dddddd;
        }
        .details-wrap {
            margin-top: 10px;
        }
        .details-wrap #project {
            float: left;
        }
        .details-wrap #company {
            float: right;
        }
        .arrow {
            margin-bottom: 4px;
        }
        .arrow.back {
            text-align: right;
        }
        .inner-arrow {
            padding: 0 10px;
            min-height: 24px;
            display: inline-block;
            background-color: rgb(233, 125, 49);
            line-height: 24px;
            vertical-align: middle;
            font-size: 8px;
            color: #fff;
        }
        .arrow.back .inner-arrow {
            background-color: rgb(233, 217, 49);
            color: #001028;
        }
        .arrow:before,
        .arrow:after {
            content:'';
            display: inline-block;
            width: 0;
            height: 0;
            border: 12px solid transparent;
            vertical-align: middle;
        }
        .arrow:before {
            border-top-color: rgb(233, 125, 49);
            border-bottom-color: rgb(233, 125, 49);
            border-right-color: rgb(233, 125, 49);
        }
        .arrow.back:before {
            border-right-color: rgb(233, 217, 49);
        }
        .arrow:after {
            border-left-color: rgb(233, 125, 49);
        }
        .arrow.back:after {
            border-left-color: rgb(233, 217, 49);
            border-top-color: rgb(233, 217, 49);
            border-bottom-color: rgb(233, 217, 49);
            border-right-color: transparent;
        }
        .arrow span {
            display: inline-block;
            width: 68px;
            margin-right: 10px;
            text-align: right;
            font-size: 8px;
            font-weight: bold;
        }
        .arrow.back span {
            margin-right: 0;
            margin-left: 10px;
            text-align: left;
        }
        @else
        .header {
            padding: 6px 0 10px 0;
            margin-bottom: 10px;
        }
        #logo {
            text-align: center;
            margin-bottom: 6px;
        }
        #logo img {
            width: 104px;
            max-height: 72px;
            object-fit: contain;
        }
        h1.invoice-title {
            border-top: 1px solid #5d6975;
            border-bottom: 1px solid #5d6975;
            color: #5d6975;
            font-size: 24px;
            line-height: 1.35;
            font-weight: normal;
            text-align: center;
            margin: 0 0 12px 0;
            background: url('{{ $dimensionBg }}') center/cover no-repeat;
        }
        #project {
            float: left;
            font-size: 9px;
        }
        #project span {
            color: #5d6975;
            text-align: right;
            width: 68px;
            margin-right: 8px;
            display: inline-block;
            font-size: 8px;
            font-weight: bold;
        }
        #company {
            float: right;
            text-align: right;
            font-size: 9px;
            color: #555;
        }
        #project div,
        #company div {
            white-space: nowrap;
            line-height: 1.3;
        }
        .invoice-table tr:nth-child(2n-1) td {
            background: #f5f5f5;
        }
        @endif
    </style>
</head>
<body>
    <div class="page">
        @if ($templateVariant === '3')
            <main>
                <table class="doc-header">
                    <tr>
                        <td class="doc-logo">
                            @if (is_file($logo))
                                <img src="{{ $logo }}">
                            @else
                                <div class="doc-title">{{ $company->business_name }}</div>
                            @endif
                        </td>
                        <td class="doc-meta">
                            <div class="doc-title">NOTA DE VENTA {{ $document->description }}-{{ $document->number }}</div>
                            <table class="doc-info">
                                <tr>
                                    <td class="label">FECHA EMISION</td>
                                    <td class="value">{{ \Carbon\Carbon::parse($document->created_at)->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td class="label">LOCAL</td>
                                    <td class="value">{{ $local?->description ?? '-' }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <div class="details-wrap clearfix">
                    <div id="project">
                        <div class="arrow"><div class="inner-arrow"><span>CLIENTE</span>{{ $client?->full_name ?? 'Cliente genérico' }}</div></div>
                        <div class="arrow"><div class="inner-arrow"><span>DOC.</span>{{ $client?->number ?? '-' }}</div></div>
                        <div class="arrow"><div class="inner-arrow"><span>DIRECCION</span>{{ $client?->address ?? '-' }}</div></div>
                        <div class="arrow"><div class="inner-arrow"><span>VENDEDOR</span>{{ $seller?->name ?? '-' }}</div></div>
                    </div>
                    <div id="company">
                        <div class="arrow back"><div class="inner-arrow">{{ $company->business_name }} <span>EMPRESA</span></div></div>
                        <div class="arrow back"><div class="inner-arrow">RUC {{ $company->ruc }} <span>RUC</span></div></div>
                        <div class="arrow back"><div class="inner-arrow">{{ $local?->address ?? $company->fiscal_address }} <span>DIRECCION</span></div></div>
                        <div class="arrow back"><div class="inner-arrow">{{ env('APP_URL') }} <span>WEB</span></div></div>
                    </div>
                </div>

                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th class="service">SERVICIO</th>
                            <th class="desc">DESCRIPCION</th>
                            <th>UNIDAD</th>
                            <th>QTY</th>
                            <th>TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $item)
                            @php
                                $json = json_decode($item->saleProduct, true);
                                $unitType = $json['unit_type'] ?? '-';
                                $size = $json['size'] ?? null;
                                $interne = $json['interne'] ?? null;
                                $productData = json_decode($item->product, true) ?? [];
                            @endphp
                            <tr>
                                <td class="service">{{ $interne ?: 'SERVICIO' }}</td>
                                <td class="desc">
                                    <div class="desc-title">{{ $productData['description'] ?? 'Producto / servicio' }}</div>
                                    @if ($size)
                                        <div class="desc-sub">Talla / presentación: {{ $size }}</div>
                                    @endif
                                </td>
                                <td class="unit">{{ $unitType }}</td>
                                <td class="qty">{{ (int) $item->quantity }}</td>
                                <td class="total">S/ {{ number_format($item->total, 2, '.', ',') }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="4" class="grand total">TOTAL</td>
                            <td class="grand total">S/ {{ number_format($sale->total, 2, '.', ',') }}</td>
                        </tr>
                    </tbody>
                </table>
            </main>
        @else
            <header class="header clearfix">
                <div id="logo">
                    @if (is_file($logo))
                        <img src="{{ $logo }}">
                    @endif
                </div>
                <h1 class="invoice-title">NOTA DE VENTA {{ $document->description }}-{{ $document->number }}</h1>
                <div id="company">
                    <div>{{ $company->business_name }}</div>
                    <div>RUC {{ $company->ruc }}</div>
                    <div>{{ $local?->address ?? $company->fiscal_address }}</div>
                    <div>{{ env('APP_URL') }}</div>
                </div>
                <div id="project">
                    <div><span>CLIENTE</span>{{ $client?->full_name ?? 'Cliente genérico' }}</div>
                    <div><span>DOC.</span>{{ $client?->number ?? '-' }}</div>
                    <div><span>FECHA</span>{{ \Carbon\Carbon::parse($document->created_at)->format('d/m/Y H:i') }}</div>
                    <div><span>LOCAL</span>{{ $local?->description ?? '-' }}</div>
                    <div><span>VENDEDOR</span>{{ $seller?->name ?? '-' }}</div>
                </div>
            </header>

            <main>
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th class="service">SERVICIO</th>
                            <th class="desc">DESCRIPCION</th>
                            <th>UNIDAD</th>
                            <th>QTY</th>
                            <th>TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $item)
                            @php
                                $json = json_decode($item->saleProduct, true);
                                $unitType = $json['unit_type'] ?? '-';
                                $size = $json['size'] ?? null;
                                $interne = $json['interne'] ?? null;
                                $productData = json_decode($item->product, true) ?? [];
                            @endphp
                            <tr>
                                <td class="service">{{ $interne ?: 'SERVICIO' }}</td>
                                <td class="desc">
                                    <div class="desc-title">{{ $productData['description'] ?? 'Producto / servicio' }}</div>
                                    @if ($size)
                                        <div class="desc-sub">Talla / presentación: {{ $size }}</div>
                                    @endif
                                </td>
                                <td class="unit">{{ $unitType }}</td>
                                <td class="qty">{{ (int) $item->quantity }}</td>
                                <td class="total">S/ {{ number_format($item->total, 2, '.', ',') }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="4" class="grand total">TOTAL</td>
                            <td class="grand total">S/ {{ number_format($sale->total, 2, '.', ',') }}</td>
                        </tr>
                    </tbody>
                </table>
            </main>
        @endif

        <table class="support-grid">
            <tr>
                <td style="width: 68%; padding-right: 10px;">
                    <div class="support-box">
                        <div class="support-title">Información adicional</div>
                        <div class="support-text">
                            <strong>Importe en letras:</strong> {{ $numberletters->convertToLetter($sale->total) }}<br>
                            <strong>Vendedor:</strong> {{ $seller?->name ?? '-' }}<br>
                            <strong>Sitio web:</strong> {{ env('APP_URL') }}<br>
                            Gracias por su compra. Esta representación impresa corresponde a la nota de venta emitida en el sistema.
                        </div>
                    </div>
                </td>
                <td style="width: 32%;">
                    <div class="support-box">
                        <div class="support-title">Documento</div>
                        <div class="support-text">
                            {{ $document->description }}-{{ $document->number }}<br>
                            Fecha: {{ \Carbon\Carbon::parse($document->created_at)->format('d/m/Y H:i') }}<br>
                            Local: {{ $local?->description ?? '-' }}
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="footer-line">
            {{ $company->tradename ?? $company->business_name }}
            <span class="sep">|</span>
            {{ $company->email }}
            <span class="sep">|</span>
            {{ $company->telephone }}
        </div>
    </div>
</body>
</html>
