import assert from 'node:assert/strict';
import test from 'node:test';
import { readFileSync } from 'node:fs';

/**
 * Menú del módulo Académico: qué se ve según el rol.
 *
 * El caso que fija esta prueba: el rol Portero solo tiene
 * 'aca_school_porteria_escaner'. Con el módulo Académico gated únicamente por
 * 'aca_dashboard' y el grupo Colegio por permisos administrativos, ese rol no
 * veía nada en el panel y no había forma de llegar a la pantalla de escaneo,
 * aunque el backend sí lo autorizaba (permission:aca_school_porteria_escaner).
 *
 * El checker replica `hasPermission` de
 * resources/js/Components/vristo/layout/Sidebar-Admin.vue, que es quien filtra
 * el menú: un permiso suelto, o una lista con semántica OR.
 */

const MENU_PATH = '../../Modules/Academic/Resources/assets/js/Menu.js';

const checker = (granted) => (permission) => {
    if (!permission) {
        return true;
    }

    if (Array.isArray(permission)) {
        return permission.some((item) => granted.includes(item));
    }

    return granted.includes(permission);
};

/**
 * Carga Menu.js con los stubs mínimos (`route` de Ziggy y el `businessType` que
 * app.blade.php expone en window). Se transforma el import de iconos porque
 * fuera de Vite solo interesa la forma del menú, no los iconos.
 */
let cache = null;

const loadMenu = async (businessType) => {
    if (! cache) {
        const source = readFileSync(new URL(MENU_PATH, import.meta.url), 'utf8');
        const importRe = /import\s*\{([\s\S]*?)\}\s*from\s*"@fortawesome\/free-solid-svg-icons";/;
        const names = source.match(importRe)[1].split(',').map((name) => name.trim()).filter(Boolean);

        cache = source.replace(importRe, names.map((name) => `const ${name} = 'icon';`).join('\n'));
    }

    // `route` y `window` se resuelven al evaluar el módulo, así que van primero.
    const prefix = "globalThis.route = (name) => '/r/' + name;\n";
    const code = `${prefix}${cache}\n//businessType=${businessType}\n`;
    const url = 'data:text/javascript;base64,' + Buffer.from(code).toString('base64');

    globalThis.window = { businessType };

    return (await import(url)).default;
};

/** Estado de la entrada Portería para un conjunto de permisos concedidos. */
const porteriaState = (menu, granted) => {
    const has = checker(granted);
    const group = (menu.items || []).find((item) => item.text === 'Colegio');

    if (! group) {
        return { module: has(menu.permissions), group: null, porteria: null };
    }

    const item = (group.items || []).find((entry) => entry.text === 'Portería');

    return {
        module: has(menu.permissions),
        group: has(group.permissions),
        porteria: item ? has(item.permissions) : null,
    };
};

test('el rol Portero ve el módulo, el grupo Colegio y la entrada Portería', async () => {
    const menu = await loadMenu('5');
    const state = porteriaState(menu, ['aca_school_porteria_escaner']);

    assert.deepEqual(state, { module: true, group: true, porteria: true });
});

test('el rol Portero también ve Portería cuando el negocio es colegio y capacitaciones (6)', async () => {
    const menu = await loadMenu('6');

    assert.deepEqual(porteriaState(menu, ['aca_school_porteria_escaner']), {
        module: true,
        group: true,
        porteria: true,
    });
});

test('quien no tiene el permiso de escanear no ve la entrada Portería', async () => {
    const menu = await loadMenu('5');
    const state = porteriaState(menu, ['aca_dashboard', 'aca_school_docente_notas']);

    assert.equal(state.module, true, 'El módulo sí se ve: tiene aca_dashboard.');
    assert.equal(state.group, true, 'El grupo Colegio se ve por sus permisos administrativos.');
    assert.equal(state.porteria, false, 'Portería es solo para quien puede escanear.');
});

test('sin ningún permiso escolar el módulo Académico no aparece', async () => {
    const menu = await loadMenu('5');

    assert.deepEqual(porteriaState(menu, ['aca_miscursos']), {
        module: false,
        group: false,
        porteria: false,
    });
});

test('el Administrador ve Portería y los parámetros SMS', async () => {
    const menu = await loadMenu('5');
    const granted = [
        'aca_school_porteria_escaner',
        'aca_school_porteria_reporte',
        'aca_school_sms_configuracion',
        'aca_smsgate_configuracion',
    ];
    const has = checker(granted);
    const group = menu.items.find((item) => item.text === 'Colegio');

    assert.equal(has(menu.permissions), true);
    assert.equal(has(group.permissions), true);
    assert.equal(
        has(group.items.find((item) => item.text === 'Parámetros SMS').permissions),
        true
    );
});

test('fuera del modo colegio (tipo de negocio distinto de 5 y 6) no existe el grupo Colegio', async () => {
    const menu = await loadMenu('1');

    assert.equal(
        (menu.items || []).some((item) => item.text === 'Colegio'),
        false,
        'Con otro tipo de negocio el grupo no se arma: la entrada Portería no puede mostrarse.'
    );
});
