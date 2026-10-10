/**
 * Config de Tailwind propia del sitio KAPTA LMS.
 *
 * Es autocontenida a propósito: no hereda del tailwind.config.js de la raíz,
 * así el sitio se puede copiar a su propio dominio (carpeta de vistas + esta
 * config + el CSS compilado) sin arrastrar estilos de ARACODE.
 *
 * Identidad visual (la del logotipo oficial):
 *  - navy     #0B1740  azul marino: hero, bandas destacadas y pie
 *  - blue     #0188EE  azul principal: botones, enlaces y elementos interactivos
 *  - blue-ink #0173CE  azul principal un punto más profundo
 *  - cyan     #42C5F5  celeste: acentos, indicadores y degradados discretos
 *  - mist     #F3F6FA  gris claro: fondo de secciones informativas
 *  - ink      #26344F  azul grisáceo: textos sobre fondo claro
 *
 * Sobre el contraste: el azul de marca (#0188EE) es correcto como relleno y
 * como acento, pero con texto blanco encima se queda en 3.6:1 y no cumple AA.
 * Por eso las superficies que llevan texto usan #0173CE (4.8:1 con blanco) y
 * el azul de marca queda para acentos, bordes, degradados y estados hover.
 * El celeste nunca se usa como color de texto sobre blanco (1.99:1).
 *
 * Las rutas se resuelven con __dirname para que funcionen sin importar desde
 * qué carpeta se ejecute el comando de build.
 */
const path = require('path');

/** Ruta absoluta con separadores normalizados (fast-glob en Windows). */
const abs = (relative) => path.join(__dirname, relative).replace(/\\/g, '/');

module.exports = {
    content: [
        abs('../../views/kapta/**/*.blade.php'),
        abs('../../../public/themes/kapta/js/**/*.js'),
    ],
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                ka: {
                    /* Superficies oscuras */
                    navy: '#0B1740',
                    navy2: '#122459',
                    navy3: '#162D6B',
                    'line-dark': '#243A75',

                    /* Superficies claras */
                    mist: '#F3F6FA',
                    sky: '#EAF3FE',
                    line: '#E1E8F2',

                    /* Marca */
                    blue: '#0188EE',
                    'blue-ink': '#0173CE',
                    'blue-deep': '#01519C',
                    cyan: '#42C5F5',

                    /* Texto */
                    ink: '#26344F',
                    slate: '#516480',
                    muted: '#A7BAD6',
                },
            },
            fontFamily: {
                ka: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
                display: ['"Plus Jakarta Sans"', 'Inter', 'system-ui', 'sans-serif'],
            },
            boxShadow: {
                'ka-card': '0 18px 40px -28px rgba(11, 23, 64, 0.35)',
                'ka-lift': '0 26px 52px -30px rgba(11, 23, 64, 0.42)',
                'ka-frame': '0 40px 80px -40px rgba(11, 23, 64, 0.55)',
            },
            borderRadius: {
                'ka': '1.25rem',
            },
            maxWidth: {
                'ka-copy': '62ch',
            },
        },
    },
    plugins: [],
};
