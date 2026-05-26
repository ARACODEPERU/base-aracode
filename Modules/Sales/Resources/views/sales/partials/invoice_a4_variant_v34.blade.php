<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    @php
        $templateVariant = $variant ?? '3';
        $dimensionBg = str_replace(DIRECTORY_SEPARATOR, '/', public_path('themes/invoice/example4/dimension.png'));
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
        .watermark {
            position: fixed;
            top: 40%;
            left: 12%;
            font-size: 90px;
            color: rgba(239, 68, 68, 0.12);
            transform: rotate(-24deg);
            font-weight: bold;
            z-index: 1;
        }
        .content {
            position: relative;
            z-index: 2;
        }
        a {
            color: #001028;
            text-decoration: none;
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
            font-size: 9px;
            padding: 4px 7px;
            text-align: right;
            border-top: 1px solid #c1ced9;
            background: #fff;
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
            margin-top: 8px;
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
        .support-table {
            width: 100%;
            margin-top: 4px;
        }
        .support-table td,
        .support-table th {
            border: 1px solid #d7dde4;
            padding: 4px;
            font-size: 8px;
            text-align: left;
            background: #fff;
            color: #555;
        }
        .support-table th {
            background: #f5f7fa;
            font-weight: normal;
        }
        .qr-box {
            text-align: center;
        }
        .qr-box img {
            width: 74px;
            height: 74px;
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
        .doc-title-text,
        .doc-title-number {
            display: block;
        }
        .doc-title-text {
            font-size: 21px;
            line-height: 1.05;
            white-space: normal;
        }
        .doc-title-number {
            font-size: 18px;
            line-height: 1.1;
            margin-top: 2px;
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
        .invoice-table td.unit,
        .invoice-table td.qty,
        .invoice-table td.total {
            font-size: 10px;
        }
        .invoice-table td.sub {
            border-top: 1px solid #c1ced9;
        }
        .invoice-table td.grand {
            border-top: 1px solid #5d6975;
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
            font-size: 20px;
            line-height: 1.2;
            font-weight: normal;
            text-align: center;
            margin: 0 0 12px 0;
            padding: 4px 0 5px;
            background: url('{{ $dimensionBg }}') center/cover no-repeat;
        }
        .invoice-title-text,
        .invoice-title-number {
            display: block;
        }
        .invoice-title-text {
            white-space: normal;
        }
        .invoice-title-number {
            margin-top: 2px;
            font-size: 17px;
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
        .invoice-table td.unit,
        .invoice-table td.qty,
        .invoice-table td.total {
            font-size: 10px;
        }
        .invoice-table td.grand {
            border-top: 1px solid #5d6975;
        }
        @endif
    </style>
</head>
@php
    $company = \App\Models\Company::first();
    $logo = $company->logo_document === '/img/logo176x32.png'
        ? public_path($company->logo_document)
        : public_path('storage' . DIRECTORY_SEPARATOR . $company->logo_document);
    $bankAccounts = \App\Models\BankAccount::with('bank')->where('invoice_show', true)->get();
    $documentCredito = \App\Models\Sale::whereHas('document', function ($query) use ($document) {
            $query->where('invoice_serie', $document->getSerie())
                ->where('invoice_correlative', $document->getCorrelativo())
                ->where('forma_pago', 'Credito');
        })
        ->with('document.quotas.payments')
        ->first();
    $paymentMethods = \App\Models\PaymentMethod::get();
    $clientAddress = $document->getClient()->getAddress();
    $clientAddressText = is_object($clientAddress) && method_exists($clientAddress, 'getDireccion')
        ? $clientAddress->getDireccion()
        : ($clientAddress ?? '');
    $clientLocationText = is_object($clientAddress) && method_exists($clientAddress, 'getDepartamento')
        ? trim(($clientAddress->getDepartamento() ?? '') . '-' . ($clientAddress->getProvincia() ?? '') . '-' . ($clientAddress->getDistrito() ?? ''), '-')
        : '';
    $companyAddress = $document->getCompany()->getAddress();
    $documentDiscountTotal = 0;
    foreach ($document->getDetails() as $detail) {
        $documentDiscountTotal += method_exists($detail, 'getDescuento') ? (float) ($detail->getDescuento() ?? 0) : 0;
    }
    $hasAnyPaymentsToShow = false;
    if ($documentCredito) {
        if ($documentCredito->document->single_payment) {
            foreach ($documentCredito->document->quotas as $quota) {
                if (count($quota->payments) > 0) {
                    $hasAnyPaymentsToShow = true;
                }
            }
            if ($documentCredito->payments) {
                $hasAnyPaymentsToShow = true;
            }
        } else {
            foreach ($documentCredito->document->quotas as $quota) {
                if (count($quota->payments) > 0) {
                    $hasAnyPaymentsToShow = true;
                }
            }
        }
    }
    function getPaymentMethodVariant($id, $paymentMethods)
    {
        foreach ($paymentMethods as $method) {
            if (isset($method->id) && $method->id == $id) {
                return $method->description;
            }
        }
        return '-';
    }
@endphp
<body>
    @if ($status == 3)
        <div class="watermark">ANULADO</div>
    @endif

    <div class="page">
        <div class="content">
            @if ($templateVariant === '3')
                <main>
                    <table class="doc-header">
                        <tr>
                            <td class="doc-logo">
                                @if (is_file($logo))
                                    <img src="{{ $logo }}">
                                @else
                                    <div class="doc-title">{{ $document->getCompany()->getRazonSocial() }}</div>
                                @endif
                            </td>
                            <td class="doc-meta">
                                <div class="doc-title">
                                    <span class="doc-title-text">{{ $document->getTipoDoc() == '01' ? 'FACTURA ELECTRÓNICA' : 'BOLETA ELECTRÓNICA' }}</span>
                                    <span class="doc-title-number">{{ $document->getSerie() }}-{{ $document->getCorrelativo() }}</span>
                                </div>
                                <table class="doc-info">
                                    <tr>
                                        <td class="label">FECHA EMISION</td>
                                        <td class="value">{{ $document->getFechaEmision()->format('d/m/Y') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="label">FECHA DE VENCIMIENTO</td>
                                        <td class="value">{{ $document->getFecVencimiento() ? $document->getFecVencimiento()->format('d/m/Y') : $document->getFechaEmision()->format('d/m/Y') }}</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>

                    <div class="details-wrap clearfix">
                        <div id="project">
                            <div class="arrow"><div class="inner-arrow"><span>CLIENTE</span>{{ $document->getClient()->getRznSocial() }}</div></div>
                            <div class="arrow"><div class="inner-arrow"><span>DOC.</span>{{ $document->getClient()->getNumDoc() }}</div></div>
                            <div class="arrow"><div class="inner-arrow"><span>DIRECCION</span>{{ $clientAddressText ?: '-' }}</div></div>
                            <div class="arrow"><div class="inner-arrow"><span>UBICACION</span>{{ $clientLocationText ?: '-' }}</div></div>
                        </div>
                        <div id="company">
                            <div class="arrow back"><div class="inner-arrow">{{ $document->getCompany()->getRazonSocial() }} <span>EMPRESA</span></div></div>
                            <div class="arrow back"><div class="inner-arrow">RUC {{ $document->getCompany()->getRuc() }} <span>RUC</span></div></div>
                            <div class="arrow back"><div class="inner-arrow">{{ $companyAddress->getDireccion() }} <span>DIRECCION</span></div></div>
                            <div class="arrow back"><div class="inner-arrow">{{ env('APP_URL') }} <span>WEB</span></div></div>
                        </div>
                    </div>

                    <table class="invoice-table">
                        <thead>
                            <tr>
                                <th class="service">SERVICIO</th>
                                <th class="desc">DESCRIPCION</th>
                                <th>PRECIO</th>
                                <th>QTY</th>
                                <th>TOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($document->getDetails() as $item)
                                @php
                                    $itemDiscount = method_exists($item, 'getDescuento')
                                        ? (float) ($item->getDescuento() ?? 0)
                                        : 0;
                                @endphp
                                <tr>
                                    <td class="service">{{ $item->getCodProducto() ?: 'SERVICIO' }}</td>
                                    <td class="desc">
                                        <div class="desc-title">{{ $item->getDescripcion() }}</div>
                                        @if ($itemDiscount > 0)
                                            <div class="desc-sub">Descuento SUNAT: S/ {{ number_format($itemDiscount, 2, '.', ',') }}</div>
                                        @endif
                                    </td>
                                    <td class="unit">S/ {{ number_format($item->getMtoPrecioUnitario(), 2, '.', ',') }}</td>
                                    <td class="qty">{{ $item->getCantidad() }}</td>
                                    <td class="total">S/ {{ number_format($item->getMtoPrecioUnitario(), 2, '.', ',') }}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <td colspan="4" class="sub">OP. GRAVADAS</td>
                                <td class="sub total">S/ {{ number_format($document->getMtoOperGravadas(), 2, '.', ',') }}</td>
                            </tr>
                            <tr>
                                <td colspan="4">I.G.V.</td>
                                <td class="total">S/ {{ number_format($document->getMtoIGV(), 2, '.', ',') }}</td>
                            </tr>
                            @if ($documentDiscountTotal > 0)
                                <tr>
                                    <td colspan="4">DESCUENTO</td>
                                    <td class="total">S/ {{ number_format($documentDiscountTotal, 2, '.', ',') }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td colspan="4" class="grand total">TOTAL</td>
                                <td class="grand total">S/ {{ number_format($document->getMtoImpVenta(), 2, '.', ',') }}</td>
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
                    <h1 class="invoice-title">
                        <span class="invoice-title-text">{{ $document->getTipoDoc() == '01' ? 'FACTURA ELECTRÓNICA' : 'BOLETA ELECTRÓNICA' }}</span>
                        <span class="invoice-title-number">{{ $document->getSerie() }}-{{ $document->getCorrelativo() }}</span>
                    </h1>
                    <div id="company">
                        <div>{{ $document->getCompany()->getRazonSocial() }}</div>
                        <div>RUC {{ $document->getCompany()->getRuc() }}</div>
                        <div>{{ $companyAddress->getDireccion() }}</div>
                        <div>{{ env('APP_URL') }}</div>
                    </div>
                    <div id="project">
                        <div><span>CLIENTE</span>{{ $document->getClient()->getRznSocial() }}</div>
                        <div><span>DOC.</span>{{ $document->getClient()->getNumDoc() }}</div>
                        <div><span>FECHA</span>{{ $document->getFechaEmision()->format('d/m/Y') }}</div>
                        <div><span>VENCE</span>{{ $document->getFecVencimiento() ? $document->getFecVencimiento()->format('d/m/Y') : '-' }}</div>
                        <div><span>DIR.</span>{{ $clientAddressText ?: '-' }}</div>
                        <div><span>MONEDA</span>PEN</div>
                    </div>
                </header>

                <main>
                    <table class="invoice-table">
                        <thead>
                            <tr>
                                <th class="service">SERVICIO</th>
                                <th class="desc">DESCRIPCION</th>
                                <th>PRECIO</th>
                                <th>QTY</th>
                                <th>TOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($document->getDetails() as $item)
                                @php
                                    $itemDiscount = method_exists($item, 'getDescuento')
                                        ? (float) ($item->getDescuento() ?? 0)
                                        : 0;
                                @endphp
                                <tr>
                                    <td class="service">{{ $item->getCodProducto() ?: 'SERVICIO' }}</td>
                                    <td class="desc">
                                        <div class="desc-title">{{ $item->getDescripcion() }}</div>
                                        @if ($itemDiscount > 0)
                                            <div class="desc-sub">Descuento SUNAT: S/ {{ number_format($itemDiscount, 2, '.', ',') }}</div>
                                        @endif
                                    </td>
                                    <td class="unit">S/ {{ number_format($item->getMtoPrecioUnitario(), 2, '.', ',') }}</td>
                                    <td class="qty">{{ $item->getCantidad() }}</td>
                                    <td class="total">S/ {{ number_format($item->getMtoPrecioUnitario(), 2, '.', ',') }}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <td colspan="4">OP. GRAVADAS</td>
                                <td class="total">S/ {{ number_format($document->getMtoOperGravadas(), 2, '.', ',') }}</td>
                            </tr>
                            <tr>
                                <td colspan="4">I.G.V.</td>
                                <td class="total">S/ {{ number_format($document->getMtoIGV(), 2, '.', ',') }}</td>
                            </tr>
                            @if ($documentDiscountTotal > 0)
                                <tr>
                                    <td colspan="4">DESCUENTO</td>
                                    <td class="total">S/ {{ number_format($documentDiscountTotal, 2, '.', ',') }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td colspan="4" class="grand total">TOTAL</td>
                                <td class="grand total">S/ {{ number_format($document->getMtoImpVenta(), 2, '.', ',') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </main>
            @endif

            <table class="support-grid">
                <tr>
                    <td style="width: 66%; padding-right: 10px;">
                        <div class="support-box">
                            <div class="support-title">Información adicional</div>
                            <div class="support-text">
                                <strong>Importe en letras:</strong> {{ $document->getLegends()[0]->getValue() }}<br>
                                @foreach ($params['user']['extras'] as $extra)
                                    <strong>{{ $extra['name'] }}:</strong> {{ $extra['value'] }}<br>
                                @endforeach
                                Consulte su comprobante en <strong>{{ env('APP_URL') }}</strong>.<br>
                                Representación impresa de la {{ $document->getTipoDoc() == '01' ? 'factura' : 'boleta' }} electrónica.
                            </div>
                        </div>

                        @if ($hasAnyPaymentsToShow)
                            <div class="support-box">
                                <div class="support-title">Pagos</div>
                                <table class="support-table">
                                    <thead>
                                        <tr>
                                            <th>Descripción</th>
                                            <th>Fecha</th>
                                            <th>Método</th>
                                            <th>Referencia</th>
                                            <th>Monto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if ($documentCredito->document->single_payment)
                                            @foreach ($documentCredito->document->quotas as $quota)
                                                @foreach ($quota->payments as $pay)
                                                    @if ($pay->estado)
                                                        <tr>
                                                            <td>Pago cuota {{ $quota->quota_number }} {{ $pay->description }}</td>
                                                            <td>{{ $pay->payment_date }}</td>
                                                            <td>{{ getPaymentMethodVariant($pay->payment_method_id, $paymentMethods) }}</td>
                                                            <td>{{ $pay->reference }}</td>
                                                            <td>{{ number_format($pay->amount_applied, 2, '.', '') }}</td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            @endforeach
                                            @foreach ($documentCredito->payments as $pay)
                                                <tr>
                                                    <td>{{ $pay['description'] }}</td>
                                                    <td>{{ $pay['payment_date'] }}</td>
                                                    <td>{{ getPaymentMethodVariant($pay['type'], $paymentMethods) }}</td>
                                                    <td>{{ $pay['reference'] }}</td>
                                                    <td>{{ number_format($pay['amount'], 2, '.', '') }}</td>
                                                </tr>
                                            @endforeach
                                        @else
                                            @foreach ($documentCredito->document->quotas as $quota)
                                                @foreach ($quota->payments as $pay)
                                                    <tr>
                                                        <td>Pago cuota {{ $quota->quota_number }} {{ $pay->description }}</td>
                                                        <td>{{ $pay->payment_date }}</td>
                                                        <td>{{ getPaymentMethodVariant($pay->payment_method_id, $paymentMethods) }}</td>
                                                        <td>{{ $pay->reference }}</td>
                                                        <td>{{ number_format($pay->amount_applied, 2, '.', '') }}</td>
                                                    </tr>
                                                @endforeach
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        @if ($document->getMtoImpVenta() > 0 && $document->getDetraccion())
                            @php
                                $netPayment = $document->getMtoImpVenta() - $document->getDetraccion()->getMount();
                            @endphp
                            <div class="support-box">
                                <div class="support-title">Detracción</div>
                                <div class="support-text">
                                    Monto neto de pago: <strong>S/ {{ number_format($netPayment, 2, '.', ',') }}</strong>
                                </div>
                                <table class="support-table">
                                    <tbody>
                                        <tr>
                                            <td>Cta. Banco de la Nación</td>
                                            <td>{{ $document->getDetraccion()->getCtaBanco() }}</td>
                                        </tr>
                                        <tr>
                                            <td>Código de bien o servicio</td>
                                            <td>{{ $document->getDetraccion()->getCodBienDetraccion() }}</td>
                                        </tr>
                                        <tr>
                                            <td>Monto</td>
                                            <td>{{ number_format($document->getDetraccion()->getMount(), 2, '.', ',') }}</td>
                                        </tr>
                                        <tr>
                                            <td>Porcentaje</td>
                                            <td>{{ $document->getDetraccion()->getPercent() }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        @if ($document->getObservacion())
                            <div class="support-box">
                                <div class="support-title">Observaciones</div>
                                <div class="support-text">{{ $document->getObservacion() }}</div>
                            </div>
                        @endif

                        @if (count($bankAccounts) > 0)
                            <div class="support-box">
                                <div class="support-title">Cuentas bancarias</div>
                                <table class="support-table">
                                    <thead>
                                        <tr>
                                            <th>Cuenta</th>
                                            <th>Banco</th>
                                            <th>Número</th>
                                            <th>CCI</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($bankAccounts as $account)
                                            <tr>
                                                <td>{{ $account->description }}</td>
                                                <td>{{ $account->bank->full_name }}</td>
                                                <td>{{ $account->number }}</td>
                                                <td>{{ $account->cci }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </td>
                    <td style="width: 34%;">
                        <div class="support-box qr-box">
                            <div class="support-title">Código QR</div>
                            <img src="{{ $qr_path }}">
                            <div class="support-text" style="margin-top: 5px;">
                                {{ $document->getSerie() }}-{{ $document->getCorrelativo() }}<br>
                                {{ env('APP_URL') }}
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="footer-line">
            {{ $document->getCompany()->getNombreComercial() }}
            <span class="sep">|</span>
            {{ $document->getCompany()->getEmail() }}
            <span class="sep">|</span>
            {{ $document->getCompany()->getTelephone() }}
        </div>
    </div>
</body>
</html>
