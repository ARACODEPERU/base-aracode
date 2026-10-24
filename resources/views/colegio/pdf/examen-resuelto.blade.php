<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $activity->title }} · Examen resuelto</title>
    <style>
        @page { margin: 32px 38px; }
        body { font-family: DejaVu Sans, sans-serif; color: #24233A; font-size: 12px; }
        .head { border-bottom: 3px solid #7938D5; padding-bottom: 14px; margin-bottom: 20px; }
        .school { font-size: 11px; letter-spacing: 1.2px; text-transform: uppercase; color: #7938D5; font-weight: bold; }
        h1 { font-size: 22px; margin: 6px 0 4px; }
        .meta { color: #5c5a75; font-size: 11px; }
        .student { margin: 14px 0 22px; padding: 12px 14px; background: #F3F0FF; border-radius: 10px; }
        .student td { padding: 2px 8px 2px 0; font-size: 11px; }
        .student strong { color: #24233A; }
        .grade { float: right; text-align: right; }
        .grade strong { display: block; font-size: 26px; color: #7938D5; }
        .grade span { font-size: 10px; color: #5c5a75; text-transform: uppercase; letter-spacing: .8px; }
        .question { margin-bottom: 16px; page-break-inside: avoid; }
        .question__title { font-weight: bold; font-size: 12px; margin-bottom: 5px; }
        .question__answer { background: #F7F8FC; border-left: 4px solid #12C6C1; padding: 9px 11px; border-radius: 6px; }
        .question__answer span { display: block; font-size: 9px; letter-spacing: .8px; text-transform: uppercase; color: #5c5a75; margin-bottom: 3px; }
        .empty { color: #8b89a0; font-style: italic; }
        .foot { margin-top: 26px; padding-top: 12px; border-top: 1px solid #e6e3f2; font-size: 9px; color: #77758c; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="head">
        <div class="school">{{ $portal['school']['name'] }} · Aula Virtual</div>
        <h1>{{ $activity->title }}</h1>
        <div class="meta">
            {{ $portal['grade']['level'] }} · {{ $portal['grade']['label'] }}
            @if ($finishedAt)
                · {{ $finishedAt->format('d/m/Y H:i') }}
            @endif
        </div>
    </div>

    <table class="student" width="100%">
        <tr>
            <td width="65%">
                <div><strong>{{ $portal['student']['full_name'] }}</strong></div>
                <div>Código: {{ $portal['student']['code'] }}</div>
                <div>Curso: {{ $activity->course->name ?: $activity->course->area?->name }}</div>
            </td>
            <td class="grade">
                <strong>{{ $gradeLabel }}</strong>
                <span>Nota obtenida</span>
            </td>
        </tr>
    </table>

    @foreach ($rows as $row)
        <div class="question">
            <div class="question__title">{{ $loop->iteration }}. {{ $row['question'] }}</div>
            <div class="question__answer">
                <span>Tu respuesta</span>
                @if ($row['answer'])
                    {{ $row['answer'] }}
                @else
                    <em class="empty">Sin respuesta</em>
                @endif
            </div>
        </div>
    @endforeach

    <div class="foot">
        Este documento contiene las respuestas que registraste y la nota de tu último intento.
        No incluye las respuestas correctas de la evaluación.
    </div>
</body>
</html>
