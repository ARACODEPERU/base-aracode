<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Registro de Asistencia mensual</title>
    <style>
        @page { margin: 18px 20px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1f2937; }
        h1 { font-size: 14px; margin: 0 0 2px; }
        .subtitle { font-size: 10px; color: #4b5563; margin-bottom: 8px; }
        table.grid { width: 100%; border-collapse: collapse; }
        table.grid th, table.grid td { border: 1px solid #9ca3af; padding: 2px 3px; }
        table.grid thead th { background: #e5e7eb; text-align: center; font-size: 8px; }
        table.grid td.day { text-align: center; font-weight: bold; }
        table.grid td.names { text-align: left; }
        .weekend { background: #f3f4f6; }
        .legend { margin-top: 8px; font-size: 9px; color: #374151; }
        .footer { margin-top: 14px; font-size: 8px; color: #6b7280; text-align: right; }
    </style>
</head>
<body>
    <h1>{{ $school?->name ?? 'Colegio' }} — Registro de Asistencia mensual</h1>
    <div class="subtitle">
        {{ $section->grade?->level?->name }} · {{ $section->grade?->name }} · Sección {{ $section->name }} — {{ $monthLabel }}
    </div>

    <table class="grid">
        <thead>
            <tr>
                <th rowspan="2" style="width: 22px;">#</th>
                <th rowspan="2">APELLIDOS Y NOMBRES</th>
                @foreach ($days as $day)
                    <th class="{{ $day['weekend'] ? 'weekend' : '' }}">{{ $day['day'] }}</th>
                @endforeach
            </tr>
            <tr>
                @foreach ($days as $day)
                    <th class="{{ $day['weekend'] ? 'weekend' : '' }}">{{ $day['letter'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($students as $student)
                <tr>
                    <td class="day">{{ $student['n'] }}</td>
                    <td class="names">{{ mb_strtoupper($student['full_name']) }}</td>
                    @foreach ($days as $day)
                        <td class="{{ $day['weekend'] ? 'weekend' : 'day' }}">{{ $student['attendance'][$day['day']] ?? '' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ $days->count() + 2 }}" class="names">Sin alumnos matriculados</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="legend">
        <strong>Leyenda:</strong>
        @foreach ($statuses as $code => $label)
            {{ $code }} = {{ $label }}{{ ! ($code === array_key_last($statuses)) ? ' · ' : '' }}
        @endforeach
    </div>

    <div class="footer">Generado el {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
