import assert from 'node:assert/strict';
import test from 'node:test';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

/**
 * Menú lateral del ERP (sidebar) y color de marca.
 *
 * El ítem activo del menú se pinta con los tokens `primary`, que leen las
 * variables `--brand-*` que publica Configuraciones → Branding. Aquí no se
 * comprueba el color final (eso lo mide Chrome en
 * storage/tmp/revisar-branding-empresa.mjs) sino el mecanismo del componente:
 * qué clases lleva cada estado y que el fondo base de una fila no pueda pisar el
 * fondo de su estado activo.
 *
 * Esa última parte cubre un fallo real: la fila llevaba `bg-white/70` (y
 * `dark:bg-slate-800/80`) en su clase base junto al `bg-primary-light` del estado
 * activo. Con dos utilidades de fondo en la misma lista gana la que quede después
 * en el CSS compilado, así que el estado activo se quedaba sin el color de la
 * marca. Ahora el fondo base se decide en una sola rama, la inactiva.
 */

const raiz = path.resolve(fileURLToPath(new URL('../..', import.meta.url)));
const archivo = 'resources/js/Components/vristo/layout/Sidebar-Admin.vue';
const completo = readFileSync(path.join(raiz, archivo), 'utf8');

// Sin comentarios: el archivo documenta el diseño anterior y conserva bloques
// comentados (el botón de Settings) que no son código vivo.
const fuente = completo
    .replace(/<!--[\s\S]*?-->/g, '')
    .replace(/\/\*[\s\S]*?\*\//g, '')
    .replace(/^\s*\/\/.*$/gm, '');

/** Clases base de las dos filas de opción del panel: la expandible y la simple. */
const clasesBaseDeFila = [...fuente.matchAll(/class="group[^"]*rounded-xl border [^"]*"/g)].map(
    (coincidencia) => coincidencia[0]
);

// El badge «nuevo» conserva su azul a propósito: es una etiqueta semántica, no un
// estado del menú. Se retira del texto revisado para poder exigir «sin azules
// fijos» en el resto del componente.
const codigoDelMenu = fuente.replace(/const badgeClasses = [\s\S]*?\n    \};/, '');

test('las dos filas del panel de opciones existen para poder revisarlas', () => {
    assert.equal(clasesBaseDeFila.length, 2, 'Cambió la estructura de las filas del panel de opciones.');
});

test('el fondo base de una fila no compite con el fondo de su estado activo', () => {
    for (const clase of clasesBaseDeFila) {
        assert.doesNotMatch(
            clase,
            /bg-white\/70/,
            'La fila volvió a declarar su fondo base junto al fondo del estado activo: el color de la marca podría quedar anulado.'
        );
        assert.doesNotMatch(
            clase,
            /dark:bg-slate-800\/80/,
            'La fila volvió a declarar su fondo base oscuro junto al fondo del estado activo.'
        );
    }

    assert.match(
        fuente,
        /return 'border-transparent bg-white\/70 dark:bg-slate-800\/80';/,
        'El fondo y el borde base de las filas deben decidirse en la rama inactiva de optionLinkClasses.'
    );
});

test('el borde base de una fila no compite con el borde de sus estados destacados', () => {
    for (const clase of clasesBaseDeFila) {
        assert.doesNotMatch(
            clase,
            /border-transparent/,
            'La fila volvió a fijar su borde en la clase base: el borde de marca del estado activo o abierto podría quedar anulado.'
        );
    }

    assert.match(
        fuente,
        /return 'border-secondary\/40 bg-secondary-light text-secondary/,
        'La opción abierta del menú dejó de llevar el borde del color secundario.'
    );
});

test('el ítem activo de la barra de módulos usa el color primario', () => {
    assert.match(
        fuente,
        /activeModule === menu\.text \? 'bg-primary text-white/,
        'El módulo activo del menú dejó de usar el color primario de la marca.'
    );
    assert.doesNotMatch(
        codigoDelMenu,
        /bg-blue-600|bg-blue-500|bg-sky-600|bg-sky-500/,
        'El menú volvió a un azul fijo de Tailwind en lugar de los tokens primary.'
    );
});

test('las filas activas del panel y sus iconos se pintan con la marca', () => {
    assert.match(
        fuente,
        /isOptionActive\(option\)\) \{\s*return 'border-primary\/40 bg-primary-light text-primary/,
        'La fila activa del panel dejó de usar el tinte del color primario.'
    );
    assert.match(
        fuente,
        /dark:bg-primary\/20 dark:text-white/,
        'La fila activa del panel perdió su fondo de marca en modo oscuro.'
    );
    assert.match(fuente, /bg-primary-light text-primary shadow-sm/, 'El recuadro del icono activo dejó de usar la marca.');
});

test('el hover y la opción abierta del menú usan el color secundario', () => {
    assert.match(
        fuente,
        /hover:border-secondary\/40 hover:bg-secondary-light hover:shadow-secondary\/15/,
        'El hover de las filas del menú dejó de usar el color secundario.'
    );
    assert.match(
        fuente,
        /group-hover:bg-secondary-light group-hover:text-secondary/,
        'El recuadro del icono dejó de seguir el color secundario al pasar el mouse.'
    );
    assert.match(
        fuente,
        /return 'border-secondary\/40 bg-secondary-light text-secondary/,
        'La opción abierta del menú dejó de usar el color secundario.'
    );
    // El naranja era el color del hover en el diseño original: si vuelve, el menú
    // deja de seguir la identidad del cliente.
    assert.doesNotMatch(codigoDelMenu, /orange-/, 'El menú volvió al naranja fijo del tema original.');
});

test('el tooltip de los módulos usa el color secundario de la marca', () => {
    assert.match(
        fuente,
        /const colorTooltip = 'rgb\(var\(--brand-secondary\)\)';/,
        'El tooltip de los módulos dejó de seguir el color secundario de la marca.'
    );
    assert.match(
        fuente,
        /:color="colorTooltip"/,
        'Los tooltips de los módulos dejaron de recibir el color de la marca.'
    );
    assert.doesNotMatch(
        fuente,
        /['"]purple['"]/,
        'El tooltip volvió al morado fijo de ant-design-vue en lugar del secundario de la marca.'
    );
});

test('las superficies del menú (barra y recuadro del logo) toman la marca', () => {
    assert.match(
        fuente,
        /items-center bg-primary-light dark:bg-slate-700 border-r/,
        'La barra de módulos dejó de usar el tinte del color primario.'
    );
    assert.match(
        fuente,
        /rounded-xl bg-primary-light text-white shadow-sm dark:bg-primary\/30/,
        'El recuadro del logo dejó de usar la marca.'
    );

    // El azul suave del menú no puede volver: con una marca de otro color, el
    // fondo del sidebar se veía ajeno a la identidad del cliente.
    assert.doesNotMatch(codigoDelMenu, /bg-blue-50|dark:bg-blue-700/, 'El menú volvió al azul fijo de fondo.');
});
