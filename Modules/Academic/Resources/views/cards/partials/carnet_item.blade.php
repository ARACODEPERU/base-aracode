    <div class="carnet">
        <div class="carnet-header">
            <div class="escudo">
                @if ($logoDataUri)
                    <img src="{!! $logoDataUri !!}" alt="Escudo">
                @else
                    {{ mb_substr($school?->name ?? 'A', 0, 1) }}
                @endif
            </div>
            <div class="nombre-colegio">{{ $school?->name ?? 'Institución Educativa' }}</div>
        </div>

        <div class="carnet-body">
            <span class="etiqueta">CARNÉ ESCOLAR {{ $enrollment->year?->year ?? '' }}</span>

            <div class="alumno-nombre">{{ $student->person?->full_name }}</div>
            <div class="alumno-dato">
                DNI <span class="dato-clave">{{ $student->person?->number }}</span>
                &nbsp;·&nbsp;
                Cód. <span class="dato-clave">{{ $student->student_code }}</span>
            </div>

            <div class="separador"></div>

            <table class="info-matricula">
                <tr>
                    <td class="etiqueta-col">Nivel</td>
                    <td class="valor-col">{{ $enrollment->section->grade?->level?->name ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="etiqueta-col">Grado</td>
                    <td class="valor-col">{{ $enrollment->section->grade?->name ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="etiqueta-col">Sección</td>
                    <td class="valor-col">{{ $enrollment->section->name ?? '—' }}</td>
                </tr>
            </table>

            <img class="qr" src="{!! $qrDataUri !!}" alt="QR de asistencia">
            <div class="qr-caption">Código de asistencia: {{ $student->student_code }}</div>
        </div>

        <div class="carnet-footer">
            {{ $school?->address }}@if($school?->address && $school?->phone) &nbsp;·&nbsp; @endif{{ $school?->phone }}
        </div>
    </div>
