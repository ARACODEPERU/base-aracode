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
        .module-item { margin-bottom: 15px; overflow: hidden; }
        .module-name { font-size: {{ $fontSize }}px; font-weight: bold; margin-bottom: 5px; display: flex; align-items: center; flex-wrap: wrap; }
        .module-number { margin-right: 8px; }
        .exam-grade { font-weight: bold; color: {{ $examColor }}; font-size: {{ $fontSize }}px; margin-left: 15px; }
        .themes-list { padding-left: 20px; margin-top: 5px; }
        .theme-item { font-size: {{ $fontSize - 2 }}px; margin-bottom: 2px; }
    </style>
</head>
<body>
    <div class="container">
        @if($courseTitle)
            <div class="course-title">{{ $courseTitle }}</div>
        @endif

        @if($showModuleContent && $moduleName)
            <div class="module-item">
                <div class="module-name">
                    <span class="module-number">1.</span>
                    <span>{{ $moduleName }}</span>
                    @if($showExamGrade)
                    <span class="exam-grade"> CALIFICACIÓN OBTENIDA: {{ $examGrade[0] ?? '-' }}</span>
                    @endif
                </div>
                @if($showThemes && !empty($content) && count($content) > 0)
                    <div class="themes-list">
                        @foreach($content[0]['themes'] as $theme)
                            <div class="theme-item">° {{ $theme }}</div>
                        @endforeach
                    </div>
                @endif
            </div>
        @elseif($showCourseContent)
            @foreach($content as $index => $module)
                <div class="module-item">
                    <div class="module-name">
                        <span class="module-number">{{ $index + 1 }}.</span>
                        <span>{{ $module['name'] }}</span>
                        @if($showExamGrade)
                        <span class="exam-grade"> CALIFICACIÓN OBTENIDA: {{ $examGrade[$index] ?? '-' }}</span>
                        @endif
                    </div>
                    @if($showThemes && !empty($module['themes']))
                        <div class="themes-list">
                            @foreach($module['themes'] as $theme)
                                <div class="theme-item">° {{ $theme }}</div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        @endif
    </div>
</body>
</html>
