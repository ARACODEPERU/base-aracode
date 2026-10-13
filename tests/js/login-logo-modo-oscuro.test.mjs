import assert from 'node:assert/strict';
import test from 'node:test';
import { readFileSync } from 'node:fs';

/**
 * Logotipo del /login en modo oscuro.
 *
 * En oscuro el logo correcto es el NEGATIVO (PNG blanco con transparencia): el
 * panel es navy y así se lee. El respaldo anterior era `logo_dark`, que en la
 * práctica se sube como JPG con fondo claro y aparecía como un recuadro blanco
 * con el logotipo casi invisible encima —lo que se veía en producción—.
 *
 * La causa de fondo era un error al escribir la propiedad: `logo_degative` en
 * lugar de `logo_negative`. Como la clave no existía, el negativo de la empresa
 * nunca llegaba a usarse y siempre caía al JPG de respaldo.
 *
 * Se revisa el bloque `companyLogoUrl` del componente, que es donde se decide
 * la imagen, y no el archivo entero: un `logo_negative` suelto en cualquier otra
 * parte no arreglaría el logo.
 */

const RUTA = '../../resources/js/Pages/Auth/Login.vue';

const fuente = readFileSync(new URL(RUTA, import.meta.url), 'utf8');

const bloque = (() => {
    const inicio = fuente.indexOf('const companyLogoUrl');
    const fin = fuente.indexOf('const companyLogoWrapClass');

    assert.ok(inicio > -1 && fin > inicio, 'No se encontró el bloque companyLogoUrl en Login.vue');

    // Sin comentarios: el componente documenta el error ortográfico en un
    // comentario y eso no puede contar como que volvió al código.
    return fuente
        .slice(inicio, fin)
        .replace(/\/\*[\s\S]*?\*\//g, '')
        .replace(/\/\/.*$/gm, '');
})();

test('el modo oscuro prefiere el logo negativo de la empresa', () => {
    assert.match(
        bloque,
        /company\?\.logo_negative/,
        'El modo oscuro dejó de mirar `logo_negative`: volvería el recuadro blanco de `logo_dark`.'
    );
});

test('no queda el nombre mal escrito de la propiedad', () => {
    assert.doesNotMatch(
        bloque,
        /logo_degative/,
        'Volvió `logo_degative` (con g): la clave no existe y el negativo de la empresa se ignora otra vez.'
    );
});

test('el respaldo del modo oscuro es el negativo, no el JPG claro', () => {
    assert.match(
        bloque,
        /defaultCompanyAssets\.logo_negative/,
        'Falta el negativo por defecto: sin logo de empresa, el login oscuro se quedaría sin logotipo legible.'
    );

    assert.doesNotMatch(
        bloque,
        /defaultCompanyAssets\.logo_dark/,
        'El respaldo del modo oscuro es `logo_dark` (JPG con fondo claro): se vería como un recuadro blanco.'
    );
});
