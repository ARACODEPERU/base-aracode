/**
 * Config de Tailwind propia del sitio Pichanguero.
 *
 * Es autocontenida a propósito: no hereda del tailwind.config.js de la raíz,
 * así el sitio se puede copiar a su propio dominio (carpeta de vistas + esta
 * config + el CSS compilado) sin arrastrar estilos de ARACODE.
 *
 * Identidad visual (paleta de la publicidad del producto):
 *  - navy   #101D2D  azul noche: identidad, profundidad y contraste
 *  - green  #1B9447  verde cancha: iconos y acentos de marca
 *  - forest #087443  verde intenso: botones sólidos y texto de enlace
 *  - lime   #B8F34A  verde eléctrico: llamadas a la acción y selección
 *  - light  #F2F5F8  gris de fondo: secciones informativas
 *
 * Roles de contraste (verificados con WCAG 2.1): lima sobre navy 12,9:1;
 * blanco sobre verde intenso 5,85:1; verde cancha sobre blanco 3,91:1, así que
 * ese último solo se usa en iconos y titulares grandes, nunca en texto menudo.
 *
 * Los mismos valores se publican como variables CSS en pichanguero.css, para
 * que el diseño se pueda reutilizar fuera de Tailwind.
 *
 * Las rutas se resuelven con __dirname para que funcionen sin importar desde
 * qué carpeta se ejecute el comando de build.
 *
 * El proyecto declara "type": "module" en package.json, así que esta config se
 * carga como ESM: de ahí `import` en lugar de `require`.
 */
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

/** Ruta absoluta con separadores normalizados (fast-glob en Windows). */
const abs = (relative) => path.join(__dirname, relative).replace(/\\/g, '/');

export default {
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
                    navy: '#101D2D',
                    navy2: '#16283B',
                    navy3: '#1E3850',
                    line: '#26425C',

                    /* Marca */
                    green: '#1B9447',
                    forest: '#087443',
                    lime: '#B8F34A',

                    /*
                     * Superficies claras y texto sobre claro.
                     * Las claves van en kebab-case: Tailwind usa el nombre tal
                     * cual para la clase (line-light -> border-pg-line-light).
                     */
                    light: '#F2F5F8',
                    'line-light': '#DCE6EE',
                    ink: '#0B2239',
                    slate: '#5A6B7D',

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
                'pg-card': '0 16px 36px -26px rgba(16, 29, 45, 0.45)',
                'pg-lift': '0 22px 44px -28px rgba(16, 29, 45, 0.5)',
            },
            maxWidth: {
                'pg-copy': '65ch',
            },
        },
    },
    plugins: [],
};
