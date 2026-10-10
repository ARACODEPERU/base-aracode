/**
 * Config de Tailwind propia del sitio KiraFact.
 * Autocontenida: no hereda del tailwind.config.js de la raíz.
 */
const path = require('path');

/** Ruta absoluta con separadores normalizados (fast-glob en Windows). */
const abs = (relative) => path.join(__dirname, relative).replace(/\\/g, '/');

module.exports = {
    content: [
        abs('../../views/kirafact/**/*.blade.php'),
        abs('../../../public/themes/kirafact/js/**/*.js'),
    ],
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                kf: {
                    dark: '#061418',
                    panel: '#0C1F25',
                    panel2: '#11292F',
                    line: '#1B3138',
                    teal: '#14B8A6',
                    emerald: '#10B981',
                    sky: '#38BDF8',
                    amber: '#FBBF24',
                    muted: '#94A3B8',
                },
            },
            fontFamily: {
                kf: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
            },
            boxShadow: {
                'kf-glow': '0 0 40px rgba(20, 184, 166, 0.18)',
                'kf-card': '0 18px 40px -20px rgba(2, 12, 14, 0.9)',
            },
        },
    },
    plugins: [],
};
