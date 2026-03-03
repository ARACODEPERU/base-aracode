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

        .description-text {
            font-size: {{ $fontSize }}px;
            line-height: {{ $lineHeight }}px;
            text-align: {{ $textAlign }};
        }

        .description-text.text-justify {
            text-align: justify;
        }

        .description-text.text-center {
            text-align: center;
        }

        .description-text.text-left {
            text-align: left;
        }

        .description-text.text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="description-text text-{{ $textAlign }}">
            {!! nl2br(e($text)) !!}
        </div>
    </div>
</body>
</html>
