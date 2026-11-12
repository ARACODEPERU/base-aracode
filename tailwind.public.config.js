// Build de Tailwind SOLO para el sitio público (marketing).
// ---------------------------------------------------------------------------
// `website.css` se genera con tailwind.config.js, que escanea todo el ERP:
// vistas de administración, módulos y Vue. Eso produce 7,6 MB de CSS del que
// la web pública usa una fracción.
//
// Este config reutiliza el tema y los plugins del config principal, pero
// limita el contenido a las vistas de `aracode` (las que usa
// `layouts/webpage.blade.php`). El resultado se sirve como
// `public/css/website-public.css` y deja intacto el CSS del ERP.
//
// Uso: npx tailwindcss -c tailwind.public.config.js -i resources/css/app.css \
//        -o public/css/website-public.css --minify

import base from './tailwind.config.js';

export default {
    ...base,
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './resources/views/aracode/**/*.blade.php',
        './resources/views/components/**/*.blade.php',
    ],
    // Solo lo que no se puede leer en el HTML: valores que llegan por variable
    // desde PHP y las escalas de color de los avatares del equipo.
    theme: {
        ...base.theme,
        extend: {
            ...(base.theme && base.theme.extend),
            // Las vistas antiguas de «about» usan z-2/z-3/z-4, que el build
            // grande generaba y aquí hay que declarar para no perderlos.
            zIndex: { 2: '2', 3: '3', 4: '4' },
        },
    },
    safelist: [
        'bg-white',
        'bg-ara-slate-50',
        'bg-ara-navy',
        {
            pattern: /(from|to)-(cyan|amber|rose|emerald|violet|blue)-(400|500|600)/,
        },
    ],
};
