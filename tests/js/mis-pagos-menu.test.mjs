import assert from 'node:assert/strict';
import test from 'node:test';
import { readFileSync } from 'node:fs';

import { puedeVerMisPagos } from '../../Modules/Socialevents/Resources/assets/js/utils/organizationPaymentsAccess.js';

/**
 * Entrada "Mis pagos" del menú de Gestión Deportiva.
 *
 * El caso que fija esta prueba: el organizador quiere pagar por adelantado, pero
 * el aviso de pago solo aparecía al crear un partido con el acceso vencido o sin
 * saldo, así que no tenía forma de llegar a la vista de pagos desde el sidebar.
 * Esa fila es del organizador con cuenta propia: el superadministrador vive en la
 * organización histórica y administra desde "Cuentas de organizaciones", y en el
 * modo clásico de Eventos la vista responde 404 porque no hay organización.
 */

const MENU_PATH = '../../Modules/Socialevents/Resources/assets/js/Menu.js';

/**
 * Carga Menu.js con los stubs mínimos (`route` de Ziggy). Se transforma el
 * import de iconos porque fuera de Vite solo interesa la forma del menú.
 */
const loadMenu = async () => {
    const source = readFileSync(new URL(MENU_PATH, import.meta.url), 'utf8');
    const importRe = /import\s*\{([\s\S]*?)\}\s*from\s*"@fortawesome\/free-solid-svg-icons";/;
    const names = source.match(importRe)[1].split(',').map((name) => name.trim()).filter(Boolean);
    const code = `${source.replace(importRe, names.map((name) => `const ${name} = 'icon';`).join('\n'))}`;
    const url = 'data:text/javascript;base64,' + Buffer.from(code).toString('base64');

    globalThis.route = (name) => '/r/' + name;

    return import(url);
};

const entradaMisPagos = (menu) => (menu.items || []).find((item) => item.text === 'Mis pagos');

test('el menú deportivo ofrece "Mis pagos" apuntando a la vista de pagos', async () => {
    const menu = (await loadMenu()).default;
    const item = entradaMisPagos(menu);

    assert.ok(item, 'La entrada "Mis pagos" desapareció del menú de Socialevents.');
    assert.equal(item.route, '/r/even_organizations.topup.index', 'Debe abrir la misma vista de pagos que usa el aviso al crear un partido.');
    assert.equal(item.organizationOnly, true, 'La fila debe quedar restringida al organizador con cuenta propia.');
    assert.equal(item.permissions, 'even_dashboard', 'El organizador entra al módulo con even_dashboard.');
});

test('el modo deportivo conserva la entrada "Mis pagos"', async () => {
    const menu = (await loadMenu()).configureSocialeventsMenu(true);

    assert.equal(menu.text, 'GESTIÓN DEPORTIVA');
    assert.ok(entradaMisPagos(menu), 'El filtro del modo deportivo no debe quitar la fila.');
});

test('solo el organizador con cuenta propia en modo deportivo ve "Mis pagos"', () => {
    assert.equal(puedeVerMisPagos({ mode: true, organization_id: 7 }, ['sports_organizer']), true);
    assert.equal(puedeVerMisPagos({ mode: true, organization_id: '7' }, []), true);

    assert.equal(puedeVerMisPagos({ mode: false, organization_id: 7 }, []), false, 'En el modo clásico de Eventos la vista no corresponde.');
    assert.equal(puedeVerMisPagos({ mode: true, organization_id: null }, []), false, 'Sin organización la vista responde 404.');
    assert.equal(puedeVerMisPagos({ mode: true, organization_id: 7 }, ['admin']), false, 'El superadministrador administra desde Cuentas de organizaciones.');
    assert.equal(puedeVerMisPagos({ mode: true, organization_id: 7 }, ['Administrador']), false);
    assert.equal(puedeVerMisPagos(), false, 'Sin datos compartidos la fila no se muestra.');
});

test('Sidebar-Admin.vue aplica la restricción a opciones y subopciones', () => {
    const source = readFileSync(new URL('../../resources/js/Components/vristo/layout/Sidebar-Admin.vue', import.meta.url), 'utf8');

    assert.match(source, /import \{ puedeVerMisPagos \} from 'Modules\/Socialevents\/Resources\/assets\/js\/utils\/organizationPaymentsAccess\.js';/);
    assert.match(source, /return ! opcion\.organizationOnly \|\| puedeVerMisPagos\(sportsOrganization\.value, rolesSesion\.value\);/);
    assert.match(source, /restriccionOrganizacion\(option\)/, 'La opción del menú debe pasar por la restricción.');
    assert.match(source, /restriccionOrganizacion\(subOption\)/, 'Las subopciones también.');
});

test('Sidebar.vue aplica la restricción al armar el menú', () => {
    const source = readFileSync(new URL('../../resources/js/Components/Sidebar.vue', import.meta.url), 'utf8');

    assert.match(source, /import \{ puedeVerMisPagos \} from '\.\.\/\.\.\/\.\.\/Modules\/Socialevents\/Resources\/assets\/js\/utils\/organizationPaymentsAccess\.js';/);
    assert.match(source, /!item\.organizationOnly \|\| puedeVerMisPagos\(sportsOrganization, rolesSesion\)/);
});
