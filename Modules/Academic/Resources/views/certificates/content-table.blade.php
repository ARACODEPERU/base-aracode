<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        html, body {
            margin: 0;
            padding: 0;
            background: transparent !important;
        }

        body {
            width: {{ $canvasWidth }}px;
            height: {{ $canvasHeight }}px;
            background-color: transparent;
            overflow: hidden;
            font-family: {{ $fontFamily }}, sans-serif;
            color: {{ $color }};
        }

        .container {
            position: absolute;
            left: {{ $posX }}px;
            top: {{ $posY }}px;
            width: {{ $maxWidth }}px;
            padding: 10px;
            background-color: transparent;
        }

        .course-title {
            font-size: {{ $fontSize + 4 }}px;
            font-weight: bold;
            margin-bottom: 15px;
            text-align: center;
            border-bottom: 2px solid {{ $color }};
            padding-bottom: 8px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background-color: transparent;
        }

        .table-header th {
            font-size: {{ $fontSize - 2 }}px;
            font-weight: bold;
            text-align: left;
            border-bottom: 1px solid {{ $color }};
            padding: 5px 10px;
            background-color: transparent;
        }

        .table-row td {
            font-size: {{ $fontSize - 2 }}px;
            padding: 4px 10px;
            border-bottom: 1px dotted {{ $color }};
            vertical-align: top;
            background-color: transparent;
        }

        .module-cell {
            font-weight: bold;
            width: 40%;
        }

        .themes-cell {
            width: 60%;
            line-height: {{ $lineHeight }}px;
        }

        .theme-list {
            list-style: none;
        }

        .theme-item {
            margin-bottom: 2px;
        }
    </style>
</head>
<body>
    <div class="container">
        @if($courseTitle)
            <div class="course-title">{{ $courseTitle }}</div>
        @endif

        <table class="table">
            <thead>
                <tr class="table-header">
                    <th>Módulo</th>
                    <th>Contenido</th>
                </tr>
            </thead>
            <tbody>
                @if($showModuleContent && $moduleName)
                    {{-- Mostrar solo un módulo con sus temas (certificado por módulo) --}}
                    <tr class="table-row">
                        <td class="module-cell">{{ $moduleName }}</td>
                        <td class="themes-cell">
                            @if(!empty($content) && count($content) > 0)
                                <ul class="theme-list">
                                    @foreach($content[0]['themes'] as $theme)
                                        <li class="theme-item">{{ $theme }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                    </tr>
                @elseif($showCourseContent)
                    {{-- Mostrar todos los módulos con sus temas (certificado por curso) --}}
                    @foreach($content as $module)
                        <tr class="table-row">
                            <td class="module-cell">{{ $module['name'] }}</td>
                            <td class="themes-cell">
                                @if(!empty($module['themes']))
                                    <ul class="theme-list">
                                        @foreach($module['themes'] as $theme)
                                            <li class="theme-item">{{ $theme }}</li>
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
