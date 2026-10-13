import assert from 'node:assert/strict';
import test from 'node:test';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

import { canalesRgb } from '../../resources/js/utils/colorMarca.js';

/**
 * Branding del ERP (Configuraciones → Branding).
 *
 * La marca no se pinta con hex repartidos por los componentes: los tokens
 * `primary` y `secondary` de Tailwind leen variables CSS (`--brand-*`), el
 * layout las inyecta desde los colores guardados en la empresa y la pantalla de
 * Branding los edita. Sin colores guardados el sistema usa la paleta de fábrica
 * de resources/css/app.css.
 *
 * Lo que se verifica aquí: el mecanismo sigue entero (tokens por variable, config
 * en ESM, valores de fábrica), la pantalla y su opción de menú existen, el
 * guardado llega al controlador y el layout aplica la marca a todo el ERP.
 *
 * La comprobación contra la pantalla real (colores computados en Chrome con
 * sesión iniciada) vive en storage/tmp/revisar-branding-empresa.mjs.
 */

const raiz = path.resolve(fileURLToPath(new URL('../..', import.meta.url)));
const leer = (rutaRelativa) => readFileSync(path.join(raiz, rutaRelativa), 'utf8');

const config = leer('tailwind.config.js');
const appCss = leer('resources/css/app.css');
const marca = leer('resources/js/Pages/Company/Branding.vue');
const layout = leer('resources/js/Layouts/Vristo/AppLayout.vue');
const menu = leer('Modules/Security/Resources/assets/js/Menu.js');
const rutas = leer('routes/web.php');
const controlador = leer('app/Http/Controllers/BrandingController.php');

// Sin comentarios: el config documenta el error histórico de `require` y eso no
// puede contar como que el problema volvió.
const configSinComentarios = config
    .replace(/\/\*[\s\S]*?\*\//g, '')
    .replace(/\/\/.*$/gm, '');

test('el config carga como ESM: con `require` el CSS no compila en Vite', () => {
    // El proyecto es `type: module`; Tailwind 3.4 intenta `require` del config y
    // en Node 22 eso lo carga como ESM y revienta con «require is not defined»:
    // el servidor de Vite se queda sin CSS. Con `export default` carga siempre.
    assert.match(configSinComentarios, /export default \{/, 'El config volvió a CommonJS: Vite no podrá compilar el CSS.');
    assert.doesNotMatch(configSinComentarios, /\brequire\(/, 'Hay un `require()` otra vez: el config debe quedarse en ESM.');
});

test('los tokens de marca leen variables CSS en lugar de un hex fijo', () => {
    assert.match(
        config,
        /DEFAULT:\s*'rgb\(var\(--brand-primary\) \/ <alpha-value>\)'/,
        'primary.DEFAULT volvió a un valor fijo: la marca dejaría de pintarse.'
    );
    assert.match(
        config,
        /DEFAULT:\s*'rgb\(var\(--brand-secondary\) \/ <alpha-value>\)'/,
        'secondary.DEFAULT volvió a un valor fijo: la marca dejaría de pintarse.'
    );
    assert.match(config, /light:\s*'rgb\(var\(--brand-primary-light\) \/ <alpha-value>\)'/);
    assert.match(config, /light:\s*'rgb\(var\(--brand-secondary-light\) \/ <alpha-value>\)'/);
    assert.match(config, /'dark-light':\s*'rgb\(var\(--brand-primary\) \/ 0\.15\)'/);
    assert.match(config, /'dark-light':\s*'rgb\(var\(--brand-secondary\) \/ 0\.15\)'/);
});

test('los valores de fábrica existen para que el ERP sin marca no cambie', () => {
    assert.match(appCss, /--brand-primary:\s*67 97 238;/, 'Falta el azul original como valor de fábrica.');
    assert.match(appCss, /--brand-primary-light:\s*234 241 255;/, 'Falta el fondo suave original.');
    assert.match(appCss, /--brand-secondary:\s*128 93 202;/, 'Falta el violeta original como valor de fábrica.');
    assert.match(appCss, /--brand-secondary-light:\s*235 228 247;/, 'Falta el fondo suave original del secundario.');
});

test('la pantalla de Branding edita los dos colores y guarda por su ruta', () => {
    assert.equal(
        (marca.match(/type="color"/g) ?? []).length,
        2,
        'La vista debe tener un selector de color para el primario y otro para el secundario.'
    );
    assert.match(marca, /route\("branding_update"\)/, 'El botón Guardar dejó de enviar a branding_update.');
    assert.match(marca, /variablesVistaPrevia/, 'La vista previa dejó de seguir los colores elegidos.');
    assert.match(marca, /flash\?\.message/, 'El aviso de guardado dejó de leerse del flash de la sesión.');
});

test('la opción Branding vive en el menú de Configuraciones', () => {
    assert.match(menu, /route\("branding_show"\)/);
    assert.match(menu, /text: "Branding"/);
    assert.match(menu, /faPalette/);
    assert.match(
        menu,
        /text: "Branding",\s*permissions: "empresa"/,
        'El ítem de Branding debe compartir el permiso de la configuración de empresa.'
    );
});

test('la ruta y el controlador guardan los colores en la empresa', () => {
    assert.match(rutas, /\[BrandingController::class, 'show'\]/);
    assert.match(rutas, /->name\('branding_show'\)/);
    assert.match(rutas, /\[BrandingController::class, 'update'\]/);
    assert.match(rutas, /->name\('branding_update'\)/);
    assert.match(controlador, /brand_primary/, 'El controlador dejó de leer el color primario.');
    assert.match(controlador, /brand_secondary/, 'El controlador dejó de leer el color secundario.');
    assert.match(controlador, /regex:\/\^#\[0-9a-fA-F\]\{6\}\$\//, 'Faltó la validación del formato #RRGGBB.');
});

test('el layout aplica la marca guardada a todo el ERP', () => {
    assert.match(layout, /variablesDeMarca/, 'El layout dejó de convertir los colores guardados en variables CSS.');
    assert.match(layout, /:style="marcaEstilo"/, 'El layout dejó de inyectar las variables en el contenedor principal.');
});

test('el layout publica la marca en el documento para los portales', () => {
    // Los tooltips, modales y desplegables de ant-design-vue se montan fuera del
    // contenedor de la página: sin las variables en `:root` quedarían con colores
    // fijos y el globito del menú, por ejemplo, no tomaría el color secundario.
    assert.match(
        layout,
        /const VARIABLES_DE_MARCA = \[[\s\S]*?'--brand-secondary',[\s\S]*?\];/,
        'El layout dejó de publicar la lista de variables de marca en el documento.'
    );
    assert.match(layout, /document\.documentElement/, 'El layout dejó de apuntar al documento para publicar la marca.');
    assert.match(
        layout,
        /\.style\.setProperty\(nombre, variables\[nombre\]\)/,
        'El layout dejó de escribir las variables de marca en :root.'
    );
    assert.match(
        layout,
        /\.style\.removeProperty\(nombre\)/,
        'El layout dejó de limpiar la marca al salir: otro layout heredaría los colores de la empresa anterior.'
    );
});

test('los colores de fábrica de la vista coinciden con app.css', () => {
    const canalesDeFabrica = (propiedad) => {
        const encontrado = new RegExp(`--brand-${propiedad}:\\s*([\\d ]+);`).exec(appCss);

        assert.ok(encontrado, `app.css no declara --brand-${propiedad}`);

        return encontrado[1].trim();
    };

    const hexDeFabrica = (nombre) => {
        const encontrado = new RegExp(`${nombre} = "(#[0-9a-f]{6})"`, 'i').exec(marca);

        assert.ok(encontrado, `La vista no declara ${nombre}`);

        return encontrado[1];
    };

    assert.equal(
        canalesRgb(hexDeFabrica('COLOR_PRIMARIO_FABRICA')),
        canalesDeFabrica('primary'),
        'El «Restaurar de fábrica» de la vista y app.css se separaron.'
    );
    assert.equal(
        canalesRgb(hexDeFabrica('COLOR_SECUNDARIO_FABRICA')),
        canalesDeFabrica('secondary'),
        'El «Restaurar de fábrica» de la vista y app.css se separaron.'
    );
});
