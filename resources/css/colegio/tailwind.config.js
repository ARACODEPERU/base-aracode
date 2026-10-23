/**
 * Config de Tailwind propia del sitio del colegio.
 *
 * Es autocontenida a propósito: no hereda del tailwind.config.js de la raíz,
 * así el sitio puede copiarse a su propio dominio sin arrastrar estilos de
 * ARACODE. Las rutas se resuelven con __dirname para que el build funcione
 * desde cualquier carpeta.
 *
 * Identidad visual:
 *  - forest   #173F35  verde profundo: textos de marca y pie de página
 *  - emerald  #2F7D63  verde principal: enlaces, botones sólidos y detalles
 *  - mint     #E8F6EC  verde muy claro: fondos de secciones suaves
 *  - cream    #FFF9F0  crema: fondo general del sitio
 *  - sand     #F4E4CE  arena: bordes y separadores cálidos
 *  - amber    #F7C45C  amarillo: llamadas a la acción del aula infantil
 *  - sky      #6CC4F5  celeste: acciones secundarias y ambiente del Inicial
 *  - blush    #FFE1D6  melocotón: acentos y mejillas de los personajes
 */
const path = require('path');

/** Ruta absoluta con separadores normalizados (fast-glob en Windows). */
const abs = (relative) => path.join(__dirname, relative).replace(/\\/g, '/');

module.exports = {
    content: [
        abs('../../views/colegio/**/*.blade.php'),
        abs('../../../public/themes/colegio/js/**/*.js'),
    ],
    theme: {
        extend: {
            colors: {
                cole: {
                    ink: '#22362F',
                    forest: '#173F35',
                    emerald: '#2F7D63',
                    mint: '#E8F6EC',
                    cream: '#FFF9F0',
                    sand: '#F4E4CE',
                    amber: '#F7C45C',
                    amberdeep: '#7C5316',
                    sky: '#6CC4F5',
                    skydeep: '#176B94',
                    blush: '#FFE1D6',
                },
            },
            fontFamily: {
                display: ['Fredoka', 'Nunito', 'system-ui', 'sans-serif'],
                body: ['Nunito', 'system-ui', 'sans-serif'],
            },
            boxShadow: {
                cole: '0 30px 60px -28px rgba(23, 63, 53, 0.45)',
                'cole-soft': '0 18px 40px -24px rgba(23, 63, 53, 0.35)',
            },
            borderRadius: {
                '4xl': '32px',
            },
        },
    },
    plugins: [],
};
