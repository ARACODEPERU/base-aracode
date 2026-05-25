<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page {
            margin: 20px 25px;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            color: #333;
            line-height: 1.5;
        }
        .header {
            border-bottom: 2px solid {{ $primaryColor ?? '#4361ee' }};
            padding-bottom: 12px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .header-logo {
            width: 80px;
            height: auto;
        }
        .header-info {
            flex: 1;
        }
        .header-info h1 {
            font-size: 16pt;
            margin: 0 0 4px 0;
            color: {{ $primaryColor ?? '#4361ee' }};
        }
        .header-info p {
            margin: 1px 0;
            font-size: 8pt;
            color: #666;
        }
        .title-section {
            text-align: center;
            margin-bottom: 20px;
        }
        .title-section h2 {
            font-size: 14pt;
            margin: 0;
            color: #222;
        }
        .title-section p {
            font-size: 8pt;
            color: #888;
            margin: 4px 0 0;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .info-table td {
            padding: 4px 8px;
            border: 1px solid #ddd;
            font-size: 9pt;
        }
        .info-table .label {
            background-color: #f5f5f5;
            font-weight: bold;
            width: 30%;
        }
        .section-title {
            font-size: 11pt;
            font-weight: bold;
            color: {{ $primaryColor ?? '#4361ee' }};
            border-bottom: 1px solid #ddd;
            padding-bottom: 4px;
            margin-bottom: 8px;
            margin-top: 16px;
        }
        .content-text {
            font-size: 9pt;
            line-height: 1.6;
            white-space: pre-wrap;
            padding: 8px;
            background-color: #fafafa;
            border-radius: 4px;
        }
        .page-break {
            page-break-before: always;
        }
        .prescription-title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 12px;
            padding: 5px 0;
            border-bottom: 1px solid #ddd;
        }
        .prescription-grid {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }
        .prescription-grid td {
            vertical-align: top;
            width: 50%;
        }
        .prescription-grid .left-col {
            padding-right: 12px;
        }
        .prescription-grid .right-col {
            padding-left: 12px;
        }
        .prescription-item {
            padding: 5px 0;
            border-bottom: 1px dotted #ddd;
            font-size: 9pt;
        }
        .prescription-item:last-child {
            border-bottom: none;
        }
        .prescription-drug {
            font-weight: bold;
        }
        .prescription-muted {
            color: #666;
            font-size: 8pt;
        }
        .prescription-empty {
            color: #999;
            font-style: italic;
            font-size: 8.5pt;
        }
        .prescription-signature {
            margin-top: 35px;
            text-align: right;
        }
        .prescription-signature-line {
            display: inline-block;
            width: 210px;
            border-top: 1px solid #333;
            padding-top: 5px;
            text-align: center;
            font-size: 9pt;
            font-weight: bold;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 7pt;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 6px;
        }
        .signature-area {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            text-align: center;
            width: 45%;
        }
        .signature-line {
            border-top: 1px solid #333;
            padding-top: 6px;
            margin-top: 50px;
            font-size: 9pt;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
        }
        .badge-signed { background-color: #d4edda; color: #155724; }
        .badge-pending { background-color: #fff3cd; color: #856404; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        @if($logoUrl)
            <img class="header-logo" src="{{ $logoUrl }}" alt="Logo" />
        @endif
        <div class="header-info">
            <h1>{{ $establishmentName }}</h1>
            @if($ruc)<p>RUC: {{ $ruc }}</p>@endif
            @if($address)<p>{{ $address }}</p>@endif
            @if($phone || $email)
                <p>{{ $phone ? "Tel: $phone" : '' }}{{ $phone && $email ? ' | ' : '' }}{{ $email ? "Email: $email" : '' }}</p>
            @endif
        </div>
    </div>

    <!-- Title -->
    <div class="title-section">
        <h2>REPORTE DE ATENCIÓN MÉDICA</h2>
        <p>Documento generado el {{ $generatedAt }}</p>
    </div>

    <!-- Patient & Doctor Info -->
    <table class="info-table">
        <tr>
            <td class="label">Paciente</td>
            <td>{{ $patientName }}</td>
            <td class="label">Documento</td>
            <td>{{ $patientDoc ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Médico</td>
            <td>{{ $doctorName }}</td>
            <td class="label">Especialidad</td>
            <td>{{ $doctorSpecialty ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Fecha de Atención</td>
            <td>{{ $attentionDate }}</td>
            <td class="label">Servicio</td>
            <td>{{ $serviceType }}</td>
        </tr>
        @if($signedAt)
        <tr>
            <td class="label">Firmado el</td>
            <td colspan="3">
                <span class="badge badge-signed">{{ $signedAt }}</span>
            </td>
        </tr>
        @endif
    </table>

    <!-- Vital Signs -->
    @if($vitalSigns)
    <div class="section-title">Signos Vitales</div>
    <table class="info-table">
        <tr>
            <td class="label">Presión Arterial</td>
            <td>{{ $vitalSigns['blood_pressure'] ?? '-' }}</td>
            <td class="label">Frec. Cardíaca</td>
            <td>{{ $vitalSigns['heart_rate'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Temperatura</td>
            <td>{{ $vitalSigns['temperature'] ?? '-' }}</td>
            <td class="label">Saturación O₂</td>
            <td>{{ $vitalSigns['oxygen_saturation'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Peso</td>
            <td>{{ $vitalSigns['weight'] ?? '-' }}</td>
            <td class="label">Talla</td>
            <td>{{ $vitalSigns['height'] ?? '-' }}</td>
        </tr>
    </table>
    @endif

    <!-- Diagnosis -->
    @if($diagnosis)
    <div class="section-title">Diagnóstico</div>
    <div class="content-text">{{ $diagnosis }}</div>
    @endif

    <!-- Treatment -->
    @if($treatment)
    <div class="section-title">Tratamiento</div>
    <div class="content-text">{{ $treatment }}</div>
    @endif

    <!-- Observations -->
    @if($observations)
    <div class="section-title">Observaciones</div>
    <div class="content-text">{{ $observations }}</div>
    @endif

    <!-- Prescription -->
    @if($prescription)
    <div class="section-title">Receta Médica</div>
    <div class="content-text">{{ $prescription }}</div>
    @endif

    <!-- Signature -->
    <div class="signature-area">
        <div class="signature-box">
            <div class="signature-line">{{ $doctorName }}</div>
            <div style="font-size:8pt;color:#666;">Médico</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">{{ $patientName }}</div>
            <div style="font-size:8pt;color:#666;">Paciente</div>
        </div>
    </div>

    @if(!empty($prescriptionData))
    <div class="page-break"></div>

    <div class="header">
        @if($prescriptionData['logoUrl'])
            <img class="header-logo" src="{{ $prescriptionData['logoUrl'] }}" alt="Logo" />
        @endif
        <div class="header-info">
            <h1>{{ $prescriptionData['establishmentName'] }}</h1>
            @if($prescriptionData['ruc'])<p>RUC: {{ $prescriptionData['ruc'] }}</p>@endif
            @if($prescriptionData['address'] || $prescriptionData['phone'] || $prescriptionData['email'])
                <p>
                    {{ $prescriptionData['address'] ?: '' }}
                    {{ $prescriptionData['address'] && ($prescriptionData['phone'] || $prescriptionData['email']) ? ' | ' : '' }}
                    {{ $prescriptionData['phone'] ? 'Tel: ' . $prescriptionData['phone'] : '' }}
                    {{ $prescriptionData['phone'] && $prescriptionData['email'] ? ' | ' : '' }}
                    {{ $prescriptionData['email'] ? 'Email: ' . $prescriptionData['email'] : '' }}
                </p>
            @endif
        </div>
    </div>

    <div class="prescription-title">RECETA MEDICA</div>

    <table class="info-table">
        <tr>
            <td class="label">Paciente</td>
            <td>{{ $prescriptionData['patientName'] }}</td>
            <td class="label">Edad</td>
            <td>{{ $prescriptionData['patientAge'] }}</td>
        </tr>
        <tr>
            <td class="label">Medico</td>
            <td>{{ $prescriptionData['doctorName'] }}</td>
            <td class="label">Fecha</td>
            <td>{{ $prescriptionData['attentionDate'] }}</td>
        </tr>
        <tr>
            <td class="label">Especialidad</td>
            <td colspan="3">{{ $prescriptionData['doctorSpecialty'] ?? '-' }}</td>
        </tr>
    </table>

    <table class="prescription-grid">
        <tr>
            <td class="left-col">
                <div class="section-title">Terapia Farmacologica</div>
                @if(count($prescriptionData['pharmacologicalTreatments']) > 0)
                    @foreach($prescriptionData['pharmacologicalTreatments'] as $med)
                        <div class="prescription-item">
                            <div class="prescription-drug">
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
                                <div class="prescription-muted">{{ $med['frequency'] }}</div>
                            @endif
                        </div>
                    @endforeach
                @else
                    <div class="prescription-empty">No se indicaron medicamentos.</div>
                @endif
            </td>
            <td class="right-col">
                <div class="section-title">Indicaciones</div>
                @php($hasAdministrationIndications = false)
                @foreach($prescriptionData['pharmacologicalTreatments'] as $med)
                    @if($med['administration_indications'])
                        @php($hasAdministrationIndications = true)
                        <div class="prescription-item">
                            @if($med['active_ingredient'] || $med['brand'])
                                <div class="prescription-drug">{{ $med['active_ingredient'] ?: $med['brand'] }}:</div>
                            @endif
                            <div>{{ $med['administration_indications'] }}</div>
                        </div>
                    @endif
                @endforeach

                @if($prescriptionData['nonPharmacologicalIndications'])
                    <div class="content-text">{{ $prescriptionData['nonPharmacologicalIndications'] }}</div>
                @elseif(!$hasAdministrationIndications)
                    <div class="prescription-empty">Sin indicaciones registradas.</div>
                @endif
            </td>
        </tr>
    </table>

    <div class="prescription-signature">
        <div class="prescription-signature-line">{{ $prescriptionData['doctorName'] }}</div>
        <div class="prescription-muted">
            {{ $prescriptionData['doctorSpecialty'] && $prescriptionData['doctorSpecialty'] !== '-' ? $prescriptionData['doctorSpecialty'] : 'Medico' }}
        </div>
        @if($prescriptionData['colegiatura'])
            <div class="prescription-muted">{{ $prescriptionData['colegiatura'] }}</div>
        @endif
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        {{ $printFooter }}<br>
        {{ $establishmentName }} - Documento generado electrónicamente.
    </div>
</body>
</html>
