<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Consumo de IA</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 9px; color: #1f2937; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        p { margin: 0 0 8px; color: #6b7280; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 4px 5px; text-align: left; }
        th { background: #f3f4f6; font-size: 8px; text-transform: uppercase; color: #374151; }
        .num { text-align: right; }
        .totales { margin: 0 0 10px; }
        .totales td { border: 0; padding: 2px 10px 2px 0; font-size: 9px; }
        .totales strong { font-size: 11px; }
        .error { color: #b91c1c; }
    </style>
</head>
<body>
    <h1>Consumo de IA</h1>
    <p>
        Generado el {{ $generado }}
        @if (! empty($filtros['desde']) || ! empty($filtros['hasta']))
            · Periodo: {{ $filtros['desde'] ?: 'inicio' }} a {{ $filtros['hasta'] ?: 'hoy' }}
        @endif
        @if (! empty($filtros['provider']))
            · Proveedor: {{ $filtros['provider'] }}
        @endif
    </p>

    <table class="totales">
        <tr>
            <td>Consultas: <strong>{{ number_format($totales['requests'], 0, ',', '.') }}</strong></td>
            <td>Tokens: <strong>{{ number_format($totales['tokens'], 0, ',', '.') }}</strong></td>
            <td>Costo estimado: <strong>US$ {{ number_format($totales['cost'], 6, '.', ',') }}</strong></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Usuario</th>
                <th>Proveedor / modelo</th>
                <th>Pantalla</th>
                <th class="num">Entrada</th>
                <th class="num">Salida</th>
                <th class="num">Total</th>
                <th class="num">Costo USD</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($filas as $fila)
                <tr>
                    <td>{{ $fila['fecha'] }}</td>
                    <td>{{ $fila['usuario'] }}</td>
                    <td>{{ $fila['proveedor'] }} / {{ $fila['modelo'] }}</td>
                    <td>{{ $fila['pantalla'] ?: '—' }}</td>
                    <td class="num">{{ number_format($fila['prompt_tokens'], 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($fila['completion_tokens'], 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($fila['total_tokens'], 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($fila['costo'], 6, '.', ',') }}</td>
                    <td class="{{ $fila['estado'] === 'error' ? 'error' : '' }}">
                        {{ $fila['estado'] === 'error' ? 'Error' : 'Correcta' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">No hay consultas con esos filtros.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
