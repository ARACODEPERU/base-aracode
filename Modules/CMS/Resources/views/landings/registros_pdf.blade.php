<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Registros del landing</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #1f2937;
            margin: 0;
        }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .subtitulo { font-size: 11px; color: #4b5563; margin: 0 0 2px; }
        .meta { font-size: 9px; color: #6b7280; margin: 0 0 12px; }
        table { width: 100%; border-collapse: collapse; }
        thead th {
            background: #eef2f7;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .03em;
            color: #374151;
            padding: 6px 5px;
            border-bottom: 1px solid #cbd5e1;
        }
        tbody td {
            padding: 5px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        tbody tr:nth-child(even) td { background: #f9fafb; }
        .vacio { padding: 18px; text-align: center; color: #6b7280; }
        .pie { margin-top: 10px; font-size: 8px; color: #9ca3af; text-align: right; }
    </style>
</head>
<body>
    <h1>Registros del landing</h1>
    <p class="subtitulo">{{ $landing->title ?? 'Landing de curso gratis' }}</p>
    <p class="meta">
        Campaña: {{ $campaign }}
        @if (!empty($desde) && !empty($hasta))
            &nbsp;|&nbsp; Fechas: {{ $desde }} a {{ $hasta }}
        @endif
        @if (!empty($search))
            &nbsp;|&nbsp; Búsqueda: {{ $search }}
        @endif
        &nbsp;|&nbsp; Total: {{ $registros->count() }} registro(s)
    </p>

    @if ($registros->isEmpty())
        <p class="vacio">Todavía no hay registros para este landing con los filtros aplicados.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width: 12%">Fecha</th>
                    <th style="width: 18%">Nombre</th>
                    <th style="width: 20%">Email</th>
                    <th style="width: 12%">Teléfono</th>
                    <th style="width: 12%">Origen</th>
                    <th style="width: 18%">UTM</th>
                    <th style="width: 8%">Curso(s) gratis</th>
                    <th style="width: 16%">Temas de interés</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($registros as $registro)
                    <tr>
                        <td>{{ optional($registro->created_at)->format('d/m/Y H:i') }}</td>
                        <td>{{ $registro->full_name }}</td>
                        <td>{{ $registro->email }}</td>
                        <td>{{ $registro->phone }}</td>
                        <td>{{ $registro->traffic_source ?: '—' }}</td>
                        <td>
                            {{ $registro->utm_source ?: '—' }}
                            @if ($registro->utm_medium) / {{ $registro->utm_medium }} @endif
                            @if ($registro->utm_campaign) <br>{{ $registro->utm_campaign }} @endif
                        </td>
                        <td>{{ $registro->subject ?: '—' }}</td>
                        <td>{{ implode(', ', \Modules\CMS\Support\FreeCourseTopics::etiquetas($registro->interests ?? [])) ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p class="pie">Generado el {{ now()->format('d/m/Y H:i') }}</p>
</body>
</html>
