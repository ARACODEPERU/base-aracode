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
        }
        .grade-table {
            border-collapse: collapse;
            background-color: transparent;
        }
        .grade-table td {
            vertical-align: middle;
            background-color: transparent;
        }
        .label-cell {
            text-align: center;
            padding-right: 15px;
        }
        .label-text {
            font-size: {{ $fontSize }}px;
            font-weight: bold;
            line-height: 1.2;
        }
        .rect-cell {
            text-align: center;
            vertical-align: middle;
        }
        .grade-rect {
            width: {{ $rectWidth }}px;
            height: {{ $rectHeight }}px;
            border: 2px solid {{ $color }};
            display: flex;
            align-items: center;
            justify-content: center;
        }
        @php
            $digitCount = strlen($gradeValue);
            $fontScale = $digitCount >= 4 ? 0.30 : ($digitCount == 3 ? 0.40 : 0.50);
            $calculatedFontSize = (int)($rectHeight * $fontScale);
        @endphp
        .grade-value {
            font-size: {{ $calculatedFontSize }}px;
            font-weight: bold;
            color: {{ $color }};
        }
    </style>
</head>
<body>
    <div class="container">
        <table class="grade-table">
            <tr>
                <td class="label-cell">
                    <div class="label-text">
                        PROMEDIO<br>FINAL
                    </div>
                </td>
                <td class="rect-cell">
                    <div class="grade-rect">
                        <span class="grade-value">{{ $gradeValue }}</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
