/**
 * Config de Tailwind propia del sitio KIRAFACT.
 * Autocontenida: no hereda del tailwind.config.js de la raíz.
 *
 * Los valores de marca viven aquí y en :root de resources/css/kirafact/kirafact.css.
 * Cuando exista el manual de marca definitivo hay que cambiar los dos sitios.
 */
const path = require('path');

/** Ruta absoluta con separadores normalizados (fast-glob en Windows). */
const abs = (relative) => path.join(__dirname, relative).replace(/\\/g, '/');

module.exports = {
    content: [
        abs('../../views/kirafact/**/*.blade.php'),
        abs('../../../public/themes/kirafact/js/**/*.js'),
    ],
    theme: {
        extend: {
            colors: {
                kf: {
                    navy: '#0B1B3A',
                    navy2: '#12274F',
                    navy3: '#17305F',
                    blue: '#168CF0',
                    /* Azul profundo: es el azul que sí cumple contraste AA
                       llevando texto blanco encima o como color de enlace. */
                    'blue-ink': '#0E6FC4',
                    'blue-deep': '#0B5A9E',
                    'blue-soft': '#7CC1FA',
                    mist: '#F3F6FA',
                    line: '#E2E8F0',
                    ink: '#182338',
                    slate: '#5B6B82',
                    muted: '#A9B6C7',
                },
            },
            fontFamily: {
                kf: ['Inter', 'system-ui', '-apple-system', 'Segoe UI', 'sans-serif'],
            },
            boxShadow: {
                'kf-soft': '0 18px 40px -30px rgba(11, 27, 58, 0.32)',
            },
        },
    },
    plugins: [],
};
