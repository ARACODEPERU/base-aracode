import assert from 'node:assert/strict';
import test from 'node:test';
import { readFileSync } from 'node:fs';

/**
 * Punto de entrada del Modo Super Editor.
 *
 * El merge 80d7ab9b borró `<SuperEditorToggle />` de la cabecera Vristo y dejó
 * solo el import: el botón dejó de pintarse en silencio y el modo quedó
 * inalcanzable para el rol admin (los engranes solo existen con el modo
 * activo). Este test cubre las dos cabeceras de la app para que un import
 * huérfano vuelva a fallar en CI en lugar de en producción.
 *
 * Ojo: revisa el template y el import por separado, porque un archivo con el
 * import sin uso pasaría el chequeo de «el nombre aparece en el archivo».
 */

const HEADERS = [
    '../../resources/js/Components/vristo/layout/Header.vue',
    '../../resources/js/Components/Header.vue',
];

const readHeader = (relativePath) => readFileSync(new URL(relativePath, import.meta.url), 'utf8');

for (const relativePath of HEADERS) {
    const name = relativePath.replace('../../', '');

    test(`${name} renderiza <SuperEditorToggle /> en su template`, () => {
        const source = readHeader(relativePath);

        assert.match(
            source,
            /<SuperEditorToggle\s*\/>/,
            'El botón «Modo editor» desapareció de la plantilla: el rol admin no podría activar el modo.'
        );
    });

    test(`${name} importa el componente que renderiza`, () => {
        const source = readHeader(relativePath);

        assert.match(
            source,
            /import SuperEditorToggle from 'Modules\/Security\/Resources\/assets\/js\/Components\/SuperEditor\/Toggle\.vue';/,
            'Falta el import de Toggle.vue: la plantilla renderizaría una etiqueta desconocida.'
        );
    });
}
