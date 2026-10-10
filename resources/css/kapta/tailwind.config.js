/**
 * Config de Tailwind propia del sitio KAPTA.
 * Autocontenida: no hereda del tailwind.config.js de la raíz.
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
                    dark: '#080D1F',
                    panel: '#0E1530',
                    panel2: '#131C40',
                    line: '#1E2A55',
                    indigo: '#4F46E5',
                    violet: '#7C3AED',
                    cyan: '#22D3EE',
                    amber: '#F59E0B',
                    muted: '#94A3B8',
                },
            },
            fontFamily: {
                ka: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
            },
            boxShadow: {
                'ka-glow': '0 0 40px rgba(99, 102, 241, 0.20)',
                'ka-card': '0 18px 40px -20px rgba(3, 7, 18, 0.9)',
            },
        },
    },
    plugins: [],
};
