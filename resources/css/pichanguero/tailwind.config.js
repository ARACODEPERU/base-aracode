/**
 * Config de Tailwind propia del sitio Pichanguero.
 *
 * Es autocontenida a propósito: no hereda del tailwind.config.js de la raíz,
 * así el sitio se puede copiar a su propio dominio (carpeta de vistas + esta
 * config + el CSS compilado) sin arrastrar estilos de ARACODE.
 *
 * Identidad visual:
 *  - navy   #071F36  azul marino profundo: identidad, profundidad y contraste
 *  - green  #36B82E  verde corporativo: conecta los componentes con el logotipo
 *  - lime   #B5FF27  verde lima: llamadas a la acción y elementos seleccionados
 *  - forest #00834B  verde complementario: botones sólidos y acentos en claro
 *  - light  #F3F7FA  gris azulado claro: fondo de las secciones informativas
 *
 * Los mismos valores se publican como variables CSS en pichanguero.css, para
 * que el diseño se pueda reutilizar fuera de Tailwind.
 *
 * Las rutas se resuelven con __dirname para que funcionen sin importar desde
 * qué carpeta se ejecute el comando de build.
 */
const path = require('path');

/** Ruta absoluta con separadores normalizados (fast-glob en Windows). */
const abs = (relative) => path.join(__dirname, relative).replace(/\\/g, '/');

module.exports = {
    content: [
        abs('../../views/pichanguero/**/*.blade.php'),
        abs('../../../public/themes/pichanguero/js/**/*.js'),
    ],
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                pg: {
                    /* Superficies oscuras */
                    navy: '#071F36',
                    navy2: '#0A2A47',
                    navy3: '#0E3557',
                    line: '#1B4067',

                    /* Marca */
                    green: '#36B82E',
                    forest: '#00834B',
                    lime: '#B5FF27',

                    /*
                     * Superficies claras y texto sobre claro.
                     * Las claves van en kebab-case: Tailwind usa el nombre tal
                     * cual para la clase (line-light -> border-pg-line-light).
                     */
                    light: '#F3F7FA',
                    'line-light': '#DCE6EE',
                    ink: '#0B2239',
                    slate: '#55697D',

                    /* Texto secundario sobre oscuro */
                    muted: '#9AB0C4',
                },
            },
            fontFamily: {
                pg: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
                display: ['Montserrat', 'Inter', 'system-ui', 'sans-serif'],
            },
            boxShadow: {
                /* Sombras moderadas: sin brillos artificiales */
                'pg-card': '0 16px 36px -26px rgba(7, 31, 54, 0.45)',
                'pg-lift': '0 22px 44px -28px rgba(7, 31, 54, 0.5)',
            },
            maxWidth: {
                'pg-copy': '65ch',
            },
        },
    },
    plugins: [],
};
