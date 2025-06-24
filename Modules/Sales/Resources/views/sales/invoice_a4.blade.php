<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style.css" type="text/css" media="all" />

    <style>
        /*
! tailwindcss v3.3.3 | MIT License | https://tailwindcss.com
*/

        /*
1. Prevent padding and border from affecting element width. (https://github.com/mozdevs/cssremedy/issues/4)
2. Allow adding a border to an element by just adding a border-width. (https://github.com/tailwindcss/tailwindcss/pull/116)
*/

        *,
        ::before,
        ::after {
            box-sizing: border-box;
            /* 1 */
            border-width: 0;
            /* 2 */
            border-style: solid;
            /* 2 */
            border-color: #e5e7eb;
            /* 2 */
        }

        ::before,
        ::after {
            --tw-content: '';
        }

        /*
1. Use a consistent sensible line-height in all browsers.
2. Prevent adjustments of font size after orientation changes in iOS.
3. Use a more readable tab size.
4. Use the user's configured `sans` font-family by default.
5. Use the user's configured `sans` font-feature-settings by default.
6. Use the user's configured `sans` font-variation-settings by default.
*/

        html {
            line-height: 1.5;
            /* 1 */
            -webkit-text-size-adjust: 100%;
            /* 2 */
            -moz-tab-size: 4;
            /* 3 */
            tab-size: 4;
            /* 3 */
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
            /* 4 */
            font-feature-settings: normal;
            /* 5 */
            font-variation-settings: normal;
            /* 6 */
        }

        /*
1. Remove the margin in all browsers.
2. Inherit line-height from `html` so users can set them as a class directly on the `html` element.
*/

        body {
            margin: 0;
            /* 1 */
            line-height: inherit;
            /* 2 */
        }

        /*
1. Add the correct height in Firefox.
2. Correct the inheritance of border color in Firefox. (https://bugzilla.mozilla.org/show_bug.cgi?id=190655)
3. Ensure horizontal rules are visible by default.
*/

        hr {
            height: 0;
            /* 1 */
            color: inherit;
            /* 2 */
            border-top-width: 1px;
            /* 3 */
        }

        /*
Add the correct text decoration in Chrome, Edge, and Safari.
*/

        abbr:where([title]) {
            -webkit-text-decoration: underline dotted;
            text-decoration: underline dotted;
        }

        /*
Remove the default font size and weight for headings.
*/

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-size: inherit;
            font-weight: inherit;
        }

        /*
Reset links to optimize for opt-in styling instead of opt-out.
*/

        a {
            color: inherit;
            text-decoration: inherit;
        }

        /*
Add the correct font weight in Edge and Safari.
*/

        b,
        strong {
            font-weight: bolder;
        }

        /*
1. Use the user's configured `mono` font family by default.
2. Correct the odd `em` font sizing in all browsers.
*/

        code,
        kbd,
        samp,
        pre {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            /* 1 */
            font-size: 1em;
            /* 2 */
        }

        /*
Add the correct font size in all browsers.
*/

        small {
            font-size: 80%;
        }

        /*
Prevent `sub` and `sup` elements from affecting the line height in all browsers.
*/

        sub,
        sup {
            font-size: 75%;
            line-height: 0;
            position: relative;
            vertical-align: baseline;
        }

        sub {
            bottom: -0.25em;
        }

        sup {
            top: -0.5em;
        }

        /*
1. Remove text indentation from table contents in Chrome and Safari. (https://bugs.chromium.org/p/chromium/issues/detail?id=999088, https://bugs.webkit.org/show_bug.cgi?id=201297)
2. Correct table border color inheritance in all Chrome and Safari. (https://bugs.chromium.org/p/chromium/issues/detail?id=935729, https://bugs.webkit.org/show_bug.cgi?id=195016)
3. Remove gaps between table borders by default.
*/

        table {
            text-indent: 0;
            /* 1 */
            border-color: inherit;
            /* 2 */
            border-collapse: collapse;
            /* 3 */
        }

        /*
1. Change the font styles in all browsers.
2. Remove the margin in Firefox and Safari.
3. Remove default padding in all browsers.
*/

        button,
        input,
        optgroup,
        select,
        textarea {
            font-family: inherit;
            /* 1 */
            font-feature-settings: inherit;
            /* 1 */
            font-variation-settings: inherit;
            /* 1 */
            font-size: 100%;
            /* 1 */
            font-weight: inherit;
            /* 1 */
            line-height: inherit;
            /* 1 */
            color: inherit;
            /* 1 */
            margin: 0;
            /* 2 */
            padding: 0;
            /* 3 */
        }

        /*
Remove the inheritance of text transform in Edge and Firefox.
*/

        button,
        select {
            text-transform: none;
        }

        /*
1. Correct the inability to style clickable types in iOS and Safari.
2. Remove default button styles.
*/

        button,
        [type='button'],
        [type='reset'],
        [type='submit'] {
            -webkit-appearance: button;
            /* 1 */
            background-color: transparent;
            /* 2 */
            background-image: none;
            /* 2 */
        }

        /*
Use the modern Firefox focus style for all focusable elements.
*/

        :-moz-focusring {
            outline: auto;
        }

        /*
Remove the additional `:invalid` styles in Firefox. (https://github.com/mozilla/gecko-dev/blob/2f9eacd9d3d995c937b4251a5557d95d494c9be1/layout/style/res/forms.css#L728-L737)
*/

        :-moz-ui-invalid {
            box-shadow: none;
        }

        /*
Add the correct vertical alignment in Chrome and Firefox.
*/

        progress {
            vertical-align: baseline;
        }

        /*
Correct the cursor style of increment and decrement buttons in Safari.
*/

        ::-webkit-inner-spin-button,
        ::-webkit-outer-spin-button {
            height: auto;
        }

        /*
1. Correct the odd appearance in Chrome and Safari.
2. Correct the outline style in Safari.
*/

        [type='search'] {
            -webkit-appearance: textfield;
            /* 1 */
            outline-offset: -2px;
            /* 2 */
        }

        /*
Remove the inner padding in Chrome and Safari on macOS.
*/

        ::-webkit-search-decoration {
            -webkit-appearance: none;
        }

        /*
1. Correct the inability to style clickable types in iOS and Safari.
2. Change font properties to `inherit` in Safari.
*/

        ::-webkit-file-upload-button {
            -webkit-appearance: button;
            /* 1 */
            font: inherit;
            /* 2 */
        }

        /*
Add the correct display in Chrome and Safari.
*/

        summary {
            display: list-item;
        }

        /*
Removes the default spacing and border for appropriate elements.
*/

        blockquote,
        dl,
        dd,
        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        hr,
        figure,
        p,
        pre {
            margin: 0;
        }

        fieldset {
            margin: 0;
            padding: 0;
        }

        legend {
            padding: 0;
        }

        ol,
        ul,
        menu {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        /*
Reset default styling for dialogs.
*/

        dialog {
            padding: 0;
        }

        /*
Prevent resizing textareas horizontally by default.
*/

        textarea {
            resize: vertical;
        }

        /*
1. Reset the default placeholder opacity in Firefox. (https://github.com/tailwindlabs/tailwindcss/issues/3300)
2. Set the default placeholder color to the user's configured gray 400 color.
*/

        input::placeholder,
        textarea::placeholder {
            opacity: 1;
            /* 1 */
            color: #9ca3af;
            /* 2 */
        }

        /*
Set the default cursor for buttons.
*/

        button,
        [role="button"] {
            cursor: pointer;
        }

        /*
Make sure disabled buttons don't get the pointer cursor.
*/

        :disabled {
            cursor: default;
        }

        /*
1. Make replaced elements `display: block` by default. (https://github.com/mozdevs/cssremedy/issues/14)
2. Add `vertical-align: middle` to align replaced elements more sensibly by default. (https://github.com/jensimmons/cssremedy/issues/14#issuecomment-634934210)
   This can trigger a poorly considered lint error in some tools but is included by design.
*/

        img,
        svg,
        video,
        canvas,
        audio,
        iframe,
        embed,
        object {
            display: block;
            /* 1 */
            vertical-align: middle;
            /* 2 */
        }

        /*
Constrain images and videos to the parent width and preserve their intrinsic aspect ratio. (https://github.com/mozdevs/cssremedy/issues/14)
*/

        img,
        video {
            max-width: 100%;
            height: auto;
        }

        /* Make elements with the HTML hidden attribute stay hidden by default */

        [hidden] {
            display: none;
        }

        *,
        ::before,
        ::after {
            --tw-border-spacing-x: 0;
            --tw-border-spacing-y: 0;
            --tw-translate-x: 0;
            --tw-translate-y: 0;
            --tw-rotate: 0;
            --tw-skew-x: 0;
            --tw-skew-y: 0;
            --tw-scale-x: 1;
            --tw-scale-y: 1;
            --tw-pan-x: ;
            --tw-pan-y: ;
            --tw-pinch-zoom: ;
            --tw-scroll-snap-strictness: proximity;
            --tw-gradient-from-position: ;
            --tw-gradient-via-position: ;
            --tw-gradient-to-position: ;
            --tw-ordinal: ;
            --tw-slashed-zero: ;
            --tw-numeric-figure: ;
            --tw-numeric-spacing: ;
            --tw-numeric-fraction: ;
            --tw-ring-inset: ;
            --tw-ring-offset-width: 0px;
            --tw-ring-offset-color: #fff;
            --tw-ring-color: rgb(59 130 246 / 0.5);
            --tw-ring-offset-shadow: 0 0 #0000;
            --tw-ring-shadow: 0 0 #0000;
            --tw-shadow: 0 0 #0000;
            --tw-shadow-colored: 0 0 #0000;
            --tw-blur: ;
            --tw-brightness: ;
            --tw-contrast: ;
            --tw-grayscale: ;
            --tw-hue-rotate: ;
            --tw-invert: ;
            --tw-saturate: ;
            --tw-sepia: ;
            --tw-drop-shadow: ;
            --tw-backdrop-blur: ;
            --tw-backdrop-brightness: ;
            --tw-backdrop-contrast: ;
            --tw-backdrop-grayscale: ;
            --tw-backdrop-hue-rotate: ;
            --tw-backdrop-invert: ;
            --tw-backdrop-opacity: ;
            --tw-backdrop-saturate: ;
            --tw-backdrop-sepia: ;
        }

        ::backdrop {
            --tw-border-spacing-x: 0;
            --tw-border-spacing-y: 0;
            --tw-translate-x: 0;
            --tw-translate-y: 0;
            --tw-rotate: 0;
            --tw-skew-x: 0;
            --tw-skew-y: 0;
            --tw-scale-x: 1;
            --tw-scale-y: 1;
            --tw-pan-x: ;
            --tw-pan-y: ;
            --tw-pinch-zoom: ;
            --tw-scroll-snap-strictness: proximity;
            --tw-gradient-from-position: ;
            --tw-gradient-via-position: ;
            --tw-gradient-to-position: ;
            --tw-ordinal: ;
            --tw-slashed-zero: ;
            --tw-numeric-figure: ;
            --tw-numeric-spacing: ;
            --tw-numeric-fraction: ;
            --tw-ring-inset: ;
            --tw-ring-offset-width: 0px;
            --tw-ring-offset-color: #fff;
            --tw-ring-color: rgb(59 130 246 / 0.5);
            --tw-ring-offset-shadow: 0 0 #0000;
            --tw-ring-shadow: 0 0 #0000;
            --tw-shadow: 0 0 #0000;
            --tw-shadow-colored: 0 0 #0000;
            --tw-blur: ;
            --tw-brightness: ;
            --tw-contrast: ;
            --tw-grayscale: ;
            --tw-hue-rotate: ;
            --tw-invert: ;
            --tw-saturate: ;
            --tw-sepia: ;
            --tw-drop-shadow: ;
            --tw-backdrop-blur: ;
            --tw-backdrop-brightness: ;
            --tw-backdrop-contrast: ;
            --tw-backdrop-grayscale: ;
            --tw-backdrop-hue-rotate: ;
            --tw-backdrop-invert: ;
            --tw-backdrop-opacity: ;
            --tw-backdrop-saturate: ;
            --tw-backdrop-sepia: ;
        }

        .fixed {
            position: fixed;
        }

        .bottom-0 {
            bottom: 0px;
        }

        .left-0 {
            left: 0px;
        }

        .table {
            display: table;
        }

        .h-12 {
            height: 3rem;
        }

        .w-1\/2 {
            width: 50%;
        }

        .w-full {
            width: 100%;
        }

        .border-collapse {
            border-collapse: collapse;
        }

        .border-spacing-0 {
            --tw-border-spacing-x: 0px;
            --tw-border-spacing-y: 0px;
            border-spacing: var(--tw-border-spacing-x) var(--tw-border-spacing-y);
        }

        .whitespace-nowrap {
            white-space: nowrap;
        }
        .border {
            border-width: 1px;
        }
        .border-2 {
            border-width: 2px;
        }

        .border-b {
            border-bottom-width: 1px;
        }

        .border-b-2 {
            border-bottom-width: 2px;
        }

        .border-r {
            border-right-width: 1px;
        }

        .border-main {
            border-color: #5c6ac4;
        }

        .bg-main {
            background-color: #5c6ac4;
        }

        .bg-slate-100 {
            background-color: #f1f5f9;
        }

        .pxy {
            padding-left: 1rem;
            padding-right: 1rem;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }

        .p-3 {
            padding: 0.75rem;
        }

        .px-14 {
            padding-left: 3.5rem;
            padding-right: 3.5rem;
        }

        .px-2 {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }

        .py-10 {
            padding-top: 2.5rem;
            padding-bottom: 2.5rem;
        }

        .py-2 {
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }

        .py-3 {
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
        }

        .py-4 {
            padding-top: 1rem;
            padding-bottom: 1rem;
        }

        .py-6 {
            padding-top: 1.5rem;
            padding-bottom: 1.5rem;
        }

        .pb-2 {
            padding-bottom: 0.5rem;
        }

        .pb-3 {
            padding-bottom: 0.75rem;
        }

        .pl-2 {
            padding-left: 0.5rem;
        }

        .pl-3 {
            padding-left: 0.75rem;
        }

        .pl-4 {
            padding-left: 1rem;
        }

        .pr-3 {
            padding-right: 0.75rem;
        }

        .pr-4 {
            padding-right: 1rem;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .align-top {
            vertical-align: top;
        }

        .text-sm {
            font-size: 0.875rem;
            line-height: 1.25rem;
        }

        .text-xs {
            font-size: 0.75rem;
            line-height: 1rem;
        }

        .font-bold {
            font-weight: 700;
        }

        .italic {
            font-style: italic;
        }

        .text-main {
            color: #5c6ac4;
        }

        .text-neutral-600 {
            color: #525252;
        }

        .text-neutral-700 {
            color: #404040;
        }

        .text-slate-300 {
            color: #cbd5e1;
        }

        .text-slate-400 {
            color: #94a3b8;
        }

        .text-white {
            color: #fff;
        }

        @page {
            margin: 0;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
            }
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 150px;
            font-weight: bold;
            color: rgba(255, 0, 0, 0.2);
            /* Rojo con opacidad */
            white-space: nowrap;
        }

    </style>

</head>
@php
    $company = \App\Models\Company::first();
    $logo = '';
    if ($company->logo_document == '/img/logo176x32.png') {
        $logo = public_path($company->logo_document);
    } else {
        $logo = public_path('storage' . DIRECTORY_SEPARATOR . $company->logo_document);
    }

    $bankAccounts = \App\Models\BankAccount::with('bank')->where('invoice_show',true)->get();

@endphp

<body>
    @if ($status == 3)
        <div class="watermark">ANULADO</div>
    @endif
    <div class="relative">
        <div class="py-4">
            <div class="px-14 py-6">
                <table class="w-full border-collapse border-spacing-0">
                    <tbody>
                        <tr>
                            <td class="w-full align-top text-left" style="padding: 0px">
                                <div style="padding: 0px">
                                    <img src="{{ $logo }}" class="h-12" />
                                </div>
                                <p class="whitespace-nowrap text-slate-400 font-bold" style="font-size: 14px ">
                                    {{ $document->getCompany()->getRazonSocial() }}
                                </p>
                                <p class="whitespace-nowrap text-slate-400" style="font-size: 12px ">
                                    {{ $document->getCompany()->getAddress()->getDireccion() }}
                                </p>
                            </td>

                            <td class="align-top">
                                <div class="text-sm">
                                    <table class="border-collapse border-spacing-0">
                                        <tbody>
                                            <tr>
                                                @if ($document->getTipoDoc() == '01')
                                                    <td class="border-2 border-main pxy">
                                                        <div>
                                                            <p
                                                                class="whitespace-nowrap text-slate-400 font-bold text-center">
                                                                {{ $document->getCompany()->getRuc() }}
                                                            </p>
                                                            <p class="whitespace-nowrap text-slate-400 text-center">
                                                                FACTURA
                                                            </p>
                                                            <p class="whitespace-nowrap text-slate-400 text-center"
                                                                style="font-size: 12px">
                                                                E L E C T R Ó N I C A
                                                            </p>
                                                            <p
                                                                class="whitespace-nowrap font-bold text-main text-center">
                                                                {{ $document->getSerie() }}-{{ $document->getCorrelativo() }}
                                                            </p>
                                                        </div>
                                                    </td>
                                                @elseif($document->getTipoDoc() == '03')
                                                    <td class="border-2 border-main pxy">
                                                        <div style="margin: auto">
                                                            <p
                                                                class="whitespace-nowrap text-slate-400 font-bold text-center">
                                                                {{ $document->getCompany()->getRuc() }}
                                                            </p>
                                                            <p class="whitespace-nowrap text-slate-400 text-center">
                                                                BOLETA DE VENTA
                                                            </p>
                                                            <p class="whitespace-nowrap text-slate-400 text-center"
                                                                style="font-size: 12px">
                                                                E L E C T R Ó N I C A
                                                            </p>
                                                            <p
                                                                class="whitespace-nowrap font-bold text-main text-center">
                                                                {{ $document->getSerie() }}-{{ $document->getCorrelativo() }}
                                                            </p>
                                                        </div>
                                                    </td>
                                                @endif
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="bg-slate-100 px-14 py-4 text-sm">
                <table class="w-full border-collapse border-spacing-0">
                    <tbody>
                        <tr>
                            <td class="align-top" colspan="2">
                                <p class="font-bold">Cliente</p>
                            </td>
                        </tr>
                        <tr>
                            <td class="w-1/2 align-top">
                                <div class="text-neutral-600 text-xs">
                                    <p>Razón Social: {{ $document->getClient()->getRznSocial() }}</p>
                                    <p>
                                        @if ($document->getTipoDoc() == '01')
                                            <strong>RUC:</strong>
                                        @elseif($document->getTipoDoc() == '03')
                                            <strong>N/D:</strong>
                                        @endif
                                        {{ $document->getClient()->getNumDoc() }}
                                    </p>
                                    <p>Dirección: {{ $document->getClient()->getAddress() }}</p>
                                </div>
                            </td>
                            <td class="w-1/2 align-top text-right">
                                <div class="text-xs text-neutral-600">
                                    <p>Fecha Emisión: {{ $document->getFechaEmision()->format('d/m/Y') }}</p>
                                    <p></p>
                                    <p></p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="px-14 py-10 text-xs text-neutral-700">
                <table class="w-full border-collapse border-spacing-0">
                    <thead>
                        <tr>
                            <td class="border-b-2 border-main pb-2 pl-3 font-bold text-main">Cant.</td>
                            <td class="border-b-2 border-main pb-2 pl-2 font-bold text-center text-main">Unidad</td>
                            <td class="border-b-2 border-main pb-2 pl-2 font-bold text-center text-main">Código</td>
                            <td class="border-b-2 border-main pb-2 pl-2 text-left font-bold text-main">Descripción</td>
                            <td class="border-b-2 border-main pb-2 pl-2 text-center font-bold text-main">Valor Total
                            </td>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($document->getDetails() as $item)
                            <tr>
                                <td class="border-b py-2 pl-3 text-left">
                                    {{ $item->getCantidad() }}
                                </td>
                                <td class="border-b py-2 pl-2 text-center">
                                    {{ $item->getUnidad() }}
                                </td>
                                <td class="border-b py-2 pl-2 text-center">
                                    {{ $item->getCodProducto() }}
                                </td>
                                <td class="border-b py-2 pl-2 text-left">
                                    {{ $item->getDescripcion() }}
                                </td>
                                <td class="border-b py-2 pl-2 pr-3 text-right">
                                    S/ {{ number_format($item->getMtoPrecioUnitario(), 2, '.', ',') }}
                                </td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="7">
                                <table class="w-full border-collapse border-spacing-0">
                                    <tbody>
                                        <tr>
                                            <td class="w-full"></td>
                                            <td>
                                                <table class="w-full border-collapse border-spacing-0">
                                                    <tbody>
                                                        <tr>
                                                            <td class="border-b py-2 pl-2 pr-3">
                                                                <div class="whitespace-nowrap text-slate-400">
                                                                    Op. Gravadas:
                                                                </div>
                                                            </td>
                                                            <td class="border-b py-2 pl-2 pr-3 text-right">
                                                                <div class="whitespace-nowrap font-bold text-main">
                                                                    S/
                                                                    {{ number_format($document->getMtoOperGravadas(), 2, '.', ',') }}
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="py-2 pl-2 pr-3">
                                                                <div class="whitespace-nowrap text-slate-400">
                                                                    I.G.V.:
                                                                </div>
                                                            </td>
                                                            <td class="py-2 pl-2 pr-3 text-right">
                                                                <div class="whitespace-nowrap font-bold text-main">
                                                                    S/
                                                                    {{ number_format($document->getMtoIGV(), 2, '.', ',') }}
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="bg-main py-2 pl-2 pr-3">
                                                                <div class="whitespace-nowrap font-bold text-white">
                                                                    Precio Venta:
                                                                </div>
                                                            </td>
                                                            <td class="bg-main py-2 pl-2 pr-3 text-right">
                                                                <div class="whitespace-nowrap font-bold text-white">
                                                                    S/
                                                                    {{ number_format($document->getMtoImpVenta(), 2, '.', ',') }}
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="px-14 py-2 text-xs text-neutral-700">
                <table class="w-full border-collapse border-spacing-0">
                    <tbody>

                        <tr>
                            <td class="w-1/2 align-top">
                                <table class="w-full text-xs border-collapse border-spacing-0">
                                    <tbody>
                                        <tr>
                                            <td class="align-top border-b py-2 pl-2 pr-3" colspan="2">
                                                <p>Importe en letras:
                                                    <strong>{{ $document->getLegends()[0]->getValue() }}</strong>
                                                </p>
                                            </td>
                                        </tr>
                                        @foreach ($params['user']['extras'] as $extra)
                                            <tr>
                                                <td width="50%" class="border-b py-2 pl-2 pr-3">
                                                    {{ $extra['name'] }}:
                                                </td>
                                                <td width="50%" class="border-b py-2 pl-2 pr-3">
                                                    {{ $extra['value'] }}
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr>
                                            <td colspan="2">
                                                <blockquote>
                                                    <div>consulte en <a
                                                            href="{{ route('client_search_electronic_invoice') }}">{{ env('APP_URL') }}</a>
                                                    </div>
                                                    {{-- <strong>Resumen:</strong> JhLNj9jIgjYH2VRn5YG28/kdGvU=<br> --}}
                                                    @if ($document->getTipoDoc() == '01')
                                                        <span>Representación Impresa de la FACTURA ELECTRÓNICA.</span>
                                                    @elseif($document->getTipoDoc() == '03')
                                                        <span>Representación Impresa de la BOLETA DE VENTA
                                                            ELECTRÓNICA.</span>
                                                    @endif
                                                </blockquote>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                            <td class="w-1/2 align-top text-right">
                                <img src="{{ $qr_path }}" width="120" height="120" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            @if ($document->getMtoImpVenta() > 0 && $document->getDetraccion())
                @php
                    $moImVe = $document->getMtoImpVenta();
                    $moDeta = $document->getDetraccion()->getMount();
                @endphp
                <div class="px-14 py-2 text-xs text-neutral-700">
                    <table style="width: 100%">
                        <tbody>
                            <tr>
                                <td class="align-top text-left" style="width: 50%">
                                    <p style="font-size: 10px">
                                        Monto neto de pago : S/ {{ number_format($moImVe - $moDeta, 2, '.', ',') }}
                                    </p>
                                </td>
                                <td class="align-top text-right" style="width: 50%">
                                    <p style="font-size: 10px">Operación sujeta al Sistema de Pago de Obligaciones
                                        Tributarias
                                    </p>
                                    <table style="font-size: 10px" class="w-full border-collapse border-spacing-0">
                                        <thead>
                                            <tr>
                                                <th>Concepto de detracción</th>
                                                <th>Valor</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td
                                                    style="border: 1px solid #5c6ac4;border-collapse: collapse; padding: 4px">
                                                    Cta. Banco de la Nación
                                                </td>
                                                <td
                                                    style="border: 1px solid #5c6ac4;border-collapse: collapse; padding: 4px">
                                                    {{ $document->getDetraccion()->getCtaBanco() }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    style="border: 1px solid #5c6ac4;border-collapse: collapse; padding: 4px">
                                                    Código de bien o servicio
                                                </td>
                                                <td
                                                    style="border: 1px solid #5c6ac4;border-collapse: collapse; padding: 4px">
                                                    {{ $document->getDetraccion()->getCodBienDetraccion() }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    style="border: 1px solid #5c6ac4;border-collapse: collapse; padding: 4px">
                                                    Monto
                                                </td>
                                                <td
                                                    style="border: 1px solid #5c6ac4;border-collapse: collapse; padding: 4px">
                                                    {{ number_format($document->getDetraccion()->getMount(), 2, '.', ',') }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td
                                                    style="border: 1px solid #5c6ac4;border-collapse: collapse; padding: 4px">
                                                    Porcentaje
                                                </td>
                                                <td
                                                    style="border: 1px solid #5c6ac4;border-collapse: collapse; padding: 4px">
                                                    {{ $document->getDetraccion()->getPercent() }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endif
            <div class="px-14 py-10 text-sm text-neutral-700">
                @if ($document->getObservacion())
                    <p class="text-main font-bold">Información Adicional</p>
                    <p class="italic">
                        {{ $document->getObservacion() }}
                    </p>
                @endif


            </div>
        </div>
        <div class="relative fixed bottom-0 left-0 w-full">
            <div class="pxy">
                <table style="width: 100%; border-collapse: collapse;">
                    <tbody>
                        <tr>
                            {{-- Inicializa el contador de columnas --}}
                            @php $columnCount = 0; @endphp

                            @foreach ($bankAccounts as $account)
                                <td style="width: 33.33%; padding: 8px;"> {{-- Cada celda ocupa un tercio del ancho --}}
                                    <div style="border: 1px solid #e2e8f0;"> {{-- Equivalente a border border-main --}}
                                        <table style="width: 100%; font-size: 0.75rem; border-collapse: collapse;">
                                            <thead>
                                                <tr>
                                                    <td style="border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; padding-left: 12px; font-weight: bold; color: #4a5568;">
                                                        <table>
                                                            <tr>
                                                                <td style="vertical-align: middle; padding-right: 8px;" rowspan="2">
                                                                    <img src="{{ public_path($account->bank->image) }}" style="width: 45px; display: block;">
                                                                </td>
                                                                <td style="font-size: 0.60rem; vertical-align: top;">
                                                                    {{ $account->description }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td style="vertical-align: top;">
                                                                    {{ $account->bank->full_name }}
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td style="border-bottom: 1px solid #e2e8f0; padding-top: 8px; padding-bottom: 8px; padding-left: 12px; text-align: left;">
                                                        <b>Número de cuenta:</b> {{ $account->number }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding-top: 8px; padding-bottom: 8px; padding-left: 12px; text-align: left;">
                                                        <b>N° de cuenta interbancario (cci):</b> {{ $account->cci }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>

                                {{-- Incrementa el contador de columnas --}}
                                @php $columnCount++; @endphp

                                {{-- Si hemos añadido 3 columnas O es la última cuenta, cerramos la fila y abrimos una nueva (si no es la última cuenta) --}}
                                @if ($columnCount % 3 === 0 && !$loop->last)
                                    </tr><tr>
                                @endif
                            @endforeach

                            {{-- Si la última fila no tiene exactamente 3 columnas, rellenar con celdas vacías para mantener el diseño --}}
                            @while ($columnCount % 3 !== 0)
                                <td style="width: 33.33%;"></td> {{-- Celda vacía --}}
                                @php $columnCount++; @endphp
                            @endwhile
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="bg-slate-100 w-full text-neutral-600 text-center text-xs py-3">
                {{ $document->getCompany()->getNombreComercial() }}
                <span class="text-slate-300 px-2">|</span>
                {{ $document->getCompany()->getEmail() }}
                <span class="text-slate-300 px-2">|</span>
                {{ $document->getCompany()->getTelephone() }}
            </div>
        </div>
    </div>
</body>
</html>
