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
            margin-bottom: 20px;
            text-align: center;
            border-bottom: 2px solid {{ $color }};
            padding-bottom: 10px;
        }

        .module {
            margin-bottom: 15px;
        }

        .module-title {
            font-size: {{ $fontSize }}px;
            font-weight: bold;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }

        .module-number {
            margin-right: 8px;
        }

        .themes-list {
            list-style: none;
            padding-left: 20px;
        }

        .theme-item {
            font-size: {{ $fontSize - 2 }}px;
            line-height: {{ $lineHeight }}px;
            margin-bottom: 4px;
            display: flex;
            align-items: flex-start;
        }

        .bullet {
            margin-right: 8px;
            flex-shrink: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        @if($courseTitle)
            <div class="course-title">{{ $courseTitle }}</div>
        @endif

        @if($showModuleContent && $moduleName)
            {{-- Mostrar solo un módulo con sus temas (certificado por módulo) --}}
            <div class="module">
                <div class="module-title">
                    <span class="module-number">1.</span>
                    <span>{{ $moduleName }}</span>
                </div>
                @if(!empty($content) && count($content) > 0)
                    <ul class="themes-list">
                        @foreach($content[0]['themes'] as $theme)
                            <li class="theme-item">
                                <span class="bullet">°</span>
                                <span>{{ $theme }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @elseif($showCourseContent)
            {{-- Mostrar todos los módulos con sus temas (certificado por curso) --}}
            @foreach($content as $index => $module)
                <div class="module">
                    <div class="module-title">
                        <span class="module-number">{{ $index + 1 }}.</span>
                        <span>{{ $module['name'] }}</span>
                    </div>
                    @if(!empty($module['themes']))
                        <ul class="themes-list">
                            @foreach($module['themes'] as $theme)
                                <li class="theme-item">
                                    <span class="bullet">°</span>
                                    <span>{{ $theme }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        @endif
    </div>
</body>
</html>
