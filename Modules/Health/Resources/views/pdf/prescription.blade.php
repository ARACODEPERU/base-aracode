<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page {
            margin: 15px 20px;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            color: #333;
            line-height: 1.4;
        }
        .header {
            border-bottom: 2px solid {{ $primaryColor ?? '#4361ee' }};
            padding-bottom: 8px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .header-logo {
            width: 65px;
            height: auto;
        }
        .header-info {
            flex: 1;
        }
        .header-info h1 {
            font-size: 14pt;
            margin: 0 0 2px 0;
            color: {{ $primaryColor ?? '#4361ee' }};
        }
        .header-info p {
            margin: 1px 0;
            font-size: 7.5pt;
            color: #666;
        }
        .title {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 10px;
            padding: 4px 0;
            border-bottom: 1px solid #ddd;
        }
        .patient-info {
            margin-bottom: 12px;
            padding: 6px 8px;
            background: #f8f9fa;
            border-radius: 3px;
            font-size: 8.5pt;
        }
        .patient-info table {
            width: 100%;
        }
        .patient-info td {
            padding: 1px 4px;
        }
        .patient-info .label {
            font-weight: bold;
            color: #555;
            width: 18%;
        }
        .two-col {
            width: 100%;
            margin-bottom: 10px;
        }
        .two-col td {
            vertical-align: top;
            padding: 0;
        }
        .two-col .left-col {
            width: 50%;
            padding-right: 10px;
        }
        .two-col .right-col {
            width: 50%;
            padding-left: 10px;
        }
        .col-title {
            font-size: 9pt;
            font-weight: bold;
            color: {{ $primaryColor ?? '#4361ee' }};
            border-bottom: 1px solid #ddd;
            padding-bottom: 3px;
            margin-bottom: 6px;
        }
        .treatment-item {
            padding: 3px 0;
            border-bottom: 1px dotted #eee;
            font-size: 8.5pt;
        }
        .treatment-item:last-child {
            border-bottom: none;
        }
        .treatment-drug {
            font-weight: bold;
            font-size: 9pt;
        }
        .treatment-frequency {
            color: #666;
            font-size: 8pt;
        }
        .indication-item {
            padding: 3px 0;
            font-size: 8.5pt;
            border-bottom: 1px dotted #eee;
        }
        .indication-item:last-child {
            border-bottom: none;
        }
        .indication-label {
            font-weight: bold;
            color: #555;
        }
        .non-pharma {
            margin-top: 8px;
            padding-top: 6px;
            border-top: 1px solid #ddd;
        }
        .non-pharma-title {
            font-weight: bold;
            color: {{ $primaryColor ?? '#4361ee' }};
            font-size: 8.5pt;
            margin-bottom: 3px;
        }
        .non-pharma-text {
            font-size: 8pt;
            color: #555;
            white-space: pre-wrap;
        }
        .no-items {
            font-size: 8pt;
            color: #999;
            font-style: italic;
        }
        .footer-area {
            margin-top: 20px;
            text-align: right;
        }
        .signature-line {
            display: inline-block;
            width: 200px;
            border-top: 1px solid #333;
            padding-top: 4px;
            font-size: 9pt;
            font-weight: bold;
        }
        .signature-sub {
            font-size: 7.5pt;
            color: #888;
            margin-top: 2px;
        }
        .stamp-area {
            display: inline-block;
            width: 120px;
            height: 80px;
            border: 2px dashed #ccc;
            border-radius: 4px;
            margin-right: 10px;
            vertical-align: middle;
            text-align: center;
            font-size: 7pt;
            color: #aaa;
            line-height: 80px;
        }
        .footer-note {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 6.5pt;
            color: #bbb;
            border-top: 1px solid #eee;
            padding-top: 3px;
        }
    </style>
</head>
<body>
    <!-- Header: Establishment -->
    <div class="header">
        @if($logoUrl)
            <img class="header-logo" src="{{ $logoUrl }}" alt="Logo" />
        @endif
        <div class="header-info">
            <h1>{{ $establishmentName }}</h1>
            @if($ruc)<p>RUC: {{ $ruc }}</p>@endif
            @if($address || $phone || $email)
                <p>
                    {{ $address ? "{$address}" : '' }}
                    {{ $address && ($phone || $email) ? ' | ' : '' }}
                    {{ $phone ? "Tel: {$phone}" : '' }}
                    {{ $phone && $email ? ' | ' : '' }}
                    {{ $email ? "Email: {$email}" : '' }}
                </p>
            @endif
        </div>
    </div>

    <!-- Title -->
    <div class="title">RECETA MÉDICA</div>

    <!-- Patient + Doctor Info -->
    <div class="patient-info">
        <table>
            <tr>
                <td class="label">Paciente:</td>
                <td><strong>{{ $patientName }}</strong></td>
                <td class="label">Edad:</td>
                <td>{{ $patientAge }}</td>
            </tr>
            <tr>
                <td class="label">Médico:</td>
                <td><strong>{{ $doctorName }}</strong></td>
                <td class="label">Fecha:</td>
                <td>{{ $attentionDate }}</td>
            </tr>
            @if($doctorSpecialty && $doctorSpecialty !== '—')
            <tr>
                <td class="label">Especialidad:</td>
                <td colspan="3">{{ $doctorSpecialty }}</td>
            </tr>
            @endif
            @if($doctorTelephone)
            <tr>
                <td class="label">Teléfono:</td>
                <td colspan="3">{{ $doctorTelephone }}</td>
            </tr>
            @endif
            @if($doctorAddress)
            <tr>
                <td class="label">Dirección:</td>
                <td colspan="3">{{ $doctorAddress }}</td>
            </tr>
            @endif
        </table>
    </div>

    <!-- Two-column content -->
    <table class="two-col">
        <tr>
            <td class="left-col">
                <div class="col-title">Terapia Farmacológica</div>
                @if(count($pharmacologicalTreatments) > 0)
                    @foreach($pharmacologicalTreatments as $med)
                        <div class="treatment-item">
                            <div class="treatment-drug">
                                @if($med['active_ingredient'] && $med['brand'])
                                    {{ $med['active_ingredient'] }} ({{ $med['brand'] }}) {{ $med['dosage'] }}
                                @elseif($med['brand'])
                                    {{ $med['brand'] }} {{ $med['dosage'] }}
                                @elseif($med['active_ingredient'])
                                    {{ $med['active_ingredient'] }} {{ $med['dosage'] }}
                                @else
                                    {{ $med['dosage'] }}
                                @endif
                            </div>
                            @if($med['frequency'])
                                <div class="treatment-frequency">{{ $med['frequency'] }}</div>
                            @endif
                        </div>
                    @endforeach
                @else
                    <div class="no-items">No se indicaron medicamentos.</div>
                @endif
            </td>
            <td class="right-col">
                <div class="col-title">Indicaciones</div>
                @if(count($pharmacologicalTreatments) > 0)
                    @foreach($pharmacologicalTreatments as $med)
                        @if($med['administration_indications'])
                            <div class="indication-item">
                                @if($med['active_ingredient'] || $med['brand'])
                                    <div class="indication-label">
                                        {{ $med['active_ingredient'] ?: $med['brand'] }}:
                                    </div>
                                @endif
                                <div>{{ $med['administration_indications'] }}</div>
                            </div>
                        @endif
                    @endforeach
                @endif

                @if($nonPharmacologicalIndications)
                    <div class="non-pharma">
                        <div class="non-pharma-title">Indicaciones No Farmacológicas</div>
                        <div class="non-pharma-text">{{ $nonPharmacologicalIndications }}</div>
                    </div>
                @else
                    @if(count($pharmacologicalTreatments) === 0 || !collect($pharmacologicalTreatments)->pluck('administration_indications')->filter()->count())
                        <div class="no-items">Sin indicaciones registradas.</div>
                    @endif
                @endif
            </td>
        </tr>
    </table>

    <!-- Signature Area (bottom-right) -->
    <div class="footer-area">
        <div class="stamp-area">Sello</div>
        <div style="display:inline-block;vertical-align:middle;text-align:center;">
            <div class="signature-line">{{ $doctorName }}</div>
            <div class="signature-sub">
                {{ $doctorSpecialty && $doctorSpecialty !== '—' ? $doctorSpecialty : 'Médico' }}
            </div>
            @if($colegiatura)
                <div class="signature-sub">{{ $colegiatura }}</div>
            @endif
        </div>
    </div>

    <!-- Footer -->
    <div class="footer-note">
        {{ $establishmentName }} — Documento generado electrónicamente el {{ $generatedAt }}
        @if($printFooter && $printFooter !== 'Documento generado por el sistema de gestión de salud.')
            | {{ $printFooter }}
        @endif
    </div>
</body>
</html>
