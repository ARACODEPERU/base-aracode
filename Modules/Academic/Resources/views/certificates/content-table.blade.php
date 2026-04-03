<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: transparent !important; }
        body { width: {{ $canvasWidth }}px; height: {{ $canvasHeight }}px; background: transparent; overflow: hidden; font-family: Arial, sans-serif; color: {{ $color }}; }
        .container { position: absolute; left: {{ $posX }}px; top: {{ $posY }}px; width: {{ $maxWidth }}px; max-width: {{ $maxWidth }}px; padding: 10px; background: transparent; overflow: hidden; }
        .course-title { font-size: {{ $fontSize + 4 }}px; font-weight: bold; margin-bottom: 15px; text-align: center; border-bottom: 2px solid {{ $color }}; padding-bottom: 8px; }
        .content-table { width: 100%; max-width: 100%; border-collapse: collapse; table-layout: fixed; }
        .content-table th { font-size: {{ $fontSize - 2 }}px; font-weight: bold; text-align: left; border-bottom: 1px solid {{ $color }}; padding: 5px; overflow: hidden; }
        .content-table td { font-size: {{ $fontSize - 2 }}px; padding: 4px; border-bottom: 1px dotted {{ $color }}; vertical-align: top; overflow: hidden; }
        .module-name { font-weight: bold; word-wrap: break-word; }
        .exam-grade { font-weight: bold; color: {{ $examColor }}; font-size: {{ $examFontSize }}px; }
    </style>
</head>
<body width="{{ $canvasWidth }}">
    <div class="container">
        @if($courseTitle)
            <div class="course-title">{{ $courseTitle }}</div>
        @endif

        <table class="content-table">
            <thead>
                <tr>
                    <th>MÓDULO</th>
                    @if($showExamGrade)
                    <th width="20%">CALIFICACIÓN OBTENIDA</th>
                    @endif
                    @if($showThemes)
                    <th  width="40%">CONTENIDO</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @if($showModuleContent && $moduleName)
                    <tr>
                        <td class="module-name">{{ $moduleName }}</td>
                        @if($showExamGrade)
                        <td class="exam-grade">{{ $examGrade[0] ?? '-' }}</td>
                        @endif
                        <td>
                            @if($showThemes && !empty($content) && count($content) > 0)
                                <ul style="list-style: none; padding: 0; margin: 0;">
                                    @foreach($content[0]['themes'] as $theme)
                                        <li style="font-size: {{ $fontSize - 4 }}px;">° {{ $theme }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                    </tr>
                @elseif($showCourseContent)
                    @foreach($content as $index => $module)
                        <tr>
                            <td class="module-name">{{ $module['name'] }}</td>
                            @if($showExamGrade)
                            <td class="exam-grade">{{ $examGrade[$index] ?? '-' }}</td>
                            @endif
                            <td>
                                @if($showThemes && !empty($module['themes']))
                                    <ul style="list-style: none; padding: 0; margin: 0;">
                                        @foreach($module['themes'] as $theme)
                                            <li style="font-size: {{ $fontSize - 4 }}px;">° {{ $theme }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</body>
</html>
