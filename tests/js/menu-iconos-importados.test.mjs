import assert from 'node:assert/strict';
import test from 'node:test';
import { readFileSync, readdirSync, statSync } from 'node:fs';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';

/**
 * Iconos de los menús: cada `icom:` debe venir importado.
 *
 * En producción reventaba con `ReferenceError: faChalkboard is not defined`
 * desde el bundle del Super Editor: Modules/Academic/.../Menu.js usaba
 * `icom: faChalkboard` sin incluirlo en el `import` de
 * `@fortawesome/free-solid-svg-icons`. El navegador corta la ejecución del
 * módulo en la primera referencia a un identificador inexistente, así que la
 * pantalla queda a medias y el error solo se ve en la consola.
 *
 * Los menús se cargan con los iconos sustituidos por cadenas en las pruebas
 * (ver mis-pagos-menu.test.mjs), de modo que un icono sin importar pasaba
 * desapercibido. Esta prueba lee el código fuente y compara lo usado con lo
 * importado, sin necesidad de compilar.
 */

const RAIZ = fileURLToPath(new URL('../..', import.meta.url));

/** Archivos .js/.vue bajo los módulos y resources/js, sin dependencias. */
function archivosFuente(directorio) {
    const encontrados = [];

    for (const entrada of readdirSync(directorio, { withFileTypes: true })) {
        if (['node_modules', 'public', 'storage', 'vendor', '.git'].includes(entrada.name)) {
            continue;
        }

        const ruta = join(directorio, entrada.name);

        if (entrada.isDirectory()) {
            encontrados.push(...archivosFuente(ruta));
        } else if (/\.(js|mjs|vue)$/.test(entrada.name)) {
            encontrados.push(ruta);
        }
    }

    return encontrados;
}

/** Identificadores importados de cualquier paquete @fortawesome. */
function iconosImportados(codigo) {
    const importados = new Set();

    for (const bloque of codigo.matchAll(/import\s*\{([^}]*)\}\s*from\s*['"]@fortawesome[^'"]*['"]/g)) {
        for (const nombre of bloque[1].split(',')) {
            const limpio = nombre.trim().split(/\s+as\s+/).pop();

            if (limpio) {
                importados.add(limpio);
            }
        }
    }

    return importados;
}

/** Iconos usados como valor en objetos de menú (`icom:` / `icon:`). */
function iconosUsados(codigo) {
    return [...codigo.matchAll(/\b(?:icom|icon)\s*:\s*(fa[A-Za-z0-9_]+)/g)].map((coincidencia) => coincidencia[1]);
}

const raizProyecto = RAIZ.replace(/[\\/]$/, '');

const fuentes = [
    ...archivosFuente(join(raizProyecto, 'Modules')),
    ...archivosFuente(join(raizProyecto, 'resources', 'js')),
];

test('todos los iconos de menú vienen importados de @fortawesome', () => {
    const faltantes = [];

    for (const archivo of fuentes) {
        const codigo = readFileSync(archivo, 'utf8');
        const usados = new Set(iconosUsados(codigo));
        const importados = iconosImportados(codigo);

        for (const icono of usados) {
            if (!importados.has(icono)) {
                faltantes.push(`${archivo.replace(raizProyecto, '')}: ${icono}`);
            }
        }
    }

    assert.deepEqual(
        faltantes,
        [],
        'Estos iconos se usan en un menú sin importarlos: el navegador lanzará '
            + '"ReferenceError: <icono> is not defined" al cargar el módulo.',
    );
});

test('el menú escolar importa el icono que usa "Aula Virtual"', () => {
    const archivo = join(raizProyecto, 'Modules', 'Academic', 'Resources', 'assets', 'js', 'Menu.js');
    const codigo = readFileSync(archivo, 'utf8');

    assert.ok(statSync(archivo).isFile(), 'No se encontró el menú del módulo Academic.');

    // Se lee el icono que declara la entrada en lugar de fijar un nombre: así la
    // prueba sigue protegiendo el ReferenceError aunque el diseño cambie el icono.
    const entrada = codigo.match(/text:\s*'Aula Virtual',[\s\S]{0,120}?icom:\s*(\w+),/);

    assert.ok(entrada, 'La entrada "Aula Virtual" desapareció del menú o ya no declara un icono.');
    assert.match(
        codigo,
        new RegExp(`import\\s*\\{[\\s\\S]*?\\b${entrada[1]}\\b[\\s\\S]*?\\}\\s*from\\s*"@fortawesome/free-solid-svg-icons";`),
        `El icono ${entrada[1]} de "Aula Virtual" no está importado: el navegador lanzará ReferenceError.`,
    );
});
