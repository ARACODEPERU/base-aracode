import assert from 'node:assert/strict';
import test from 'node:test';
import { readFileSync } from 'node:fs';

import { debeAvisarAccesoVencido } from '../../Modules/Socialevents/Resources/assets/js/utils/organizationAccessAlert.js';

/**
 * Aviso de acceso vencido del organizador.
 *
 * El caso que fija esta prueba: al vencer la prueba la organización queda en solo
 * lectura y el organizador debe enterarse y tener a mano el botón para pagar. El
 * aviso no puede dispararse para el superadministrador (el vencimiento de una
 * cuenta no lo afecta) ni en la propia vista de pagos, donde el formulario ya está.
 */

const vencido = { mode: true, can_read: true, can_write: false };
const read = (relativePath) => readFileSync(new URL(relativePath, import.meta.url), 'utf8');

test('avisa al organizador con el acceso vencido', () => {
    assert.equal(debeAvisarAccesoVencido(vencido, ['sports_organizer'], '/socialevents/editions/3/fixtures'), true);
});

test('no avisa mientras todavía puede escribir', () => {
    assert.equal(debeAvisarAccesoVencido({ ...vencido, can_write: true }, [], '/socialevents/dashboard'), false);
});

test('no avisa al superadministrador ni fuera del modo deportivo', () => {
    assert.equal(debeAvisarAccesoVencido(vencido, ['admin'], '/socialevents/dashboard'), false);
    assert.equal(debeAvisarAccesoVencido(vencido, ['Administrador'], '/socialevents/dashboard'), false);
    assert.equal(debeAvisarAccesoVencido({ ...vencido, mode: false }, ['sports_organizer'], '/socialevents/dashboard'), false);
});

test('la cuenta bloqueada recibe aviso para pagar sin recuperar acceso al módulo', () => {
    const bloqueada = { mode: true, status: 'suspended', can_read: false, can_write: false };

    assert.equal(debeAvisarAccesoVencido(bloqueada, [], '/socialevents/dashboard'), true);
    assert.equal(debeAvisarAccesoVencido(bloqueada, [], '/socialevents/organizations/topup'), false);
});

test('no avisa en la vista de pagos, que ya explica el estado', () => {
    assert.equal(debeAvisarAccesoVencido(vencido, [], '/socialevents/organizations/topup'), false);
    assert.equal(debeAvisarAccesoVencido(vencido, [], '/socialevents/organizations/topup?estado=1'), false);
    assert.equal(debeAvisarAccesoVencido(vencido, [], '/socialevents/organizations'), true);
});

test('sin datos compartidos no avisa', () => {
    assert.equal(debeAvisarAccesoVencido(), false);
});

test('el layout monta el aviso en cualquier pantalla', () => {
    const source = read('../../resources/js/Layouts/Vristo/AppLayout.vue');

    assert.match(
        source,
        /import OrganizationAccessAlert from 'Modules\/Socialevents\/Resources\/assets\/js\/Components\/OrganizationAccessAlert\.vue';/,
        'El layout debe importar el aviso: si no, el organizador vencido no se entera.'
    );
    assert.match(source, /<OrganizationAccessAlert \/>/, 'El import sin uso no pinta nada.');
});

test('la pantalla de partidos ofrece pagar cuando el acceso venció', () => {
    const source = read('../../Modules/Socialevents/Resources/assets/js/Pages/Editions/Fixtures.vue');

    assert.match(source, /confirmButtonText: data\.can_write \? 'Recargar saldo' : 'Ir a pagar'/);
    assert.match(
        source,
        /if \(result\.isConfirmed\) \{\s*router\.visit\(route\('even_organizations\.topup\.index'\)\);/,
        'Con el acceso vencido el aviso debe llevar a la vista de pagos, no solo cerrarse.'
    );
});

test('el aviso diferencia cuenta bloqueada de acceso vencido', () => {
    const source = read('../../Modules/Socialevents/Resources/assets/js/Components/OrganizationAccessAlert.vue');

    assert.match(source, /sportsOrganization\?\.status === 'suspended' \? 'Cuenta bloqueada' : 'Acceso vencido'/);
    assert.match(source, /route\('even_organizations\.topup\.index'\)/);
});

test('la pantalla de cuentas permite vencer la prueba con una fecha', () => {
    const source = read('../../Modules/Socialevents/Resources/assets/js/Pages/Organizations/Accounts.vue');

    assert.match(source, /trial_ends_at: organization\.trial_ends_at \? String\(organization\.trial_ends_at\)\.slice\(0, 10\) : ''/);
    assert.match(source, /Fin de prueba/, 'Sin el campo el administrador no puede vencer la prueba hoy mismo.');
});
