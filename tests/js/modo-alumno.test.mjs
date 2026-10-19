import test from 'node:test';
import assert from 'node:assert/strict';
import { ROLES_PANEL, esModoAlumno, nombresDeRol } from '../../resources/js/utils/modoAlumno.js';

test('el campus del alumno solo aplica cuando Alumno es el único rol de peso', () => {
    assert.equal(esModoAlumno(['Alumno']), true);
    assert.equal(esModoAlumno(['Alumno', 'admin']), false);
    assert.equal(esModoAlumno(['Alumno', 'Administrador']), false);
    assert.equal(esModoAlumno(['admin']), false);
    assert.equal(esModoAlumno([]), false);
});

test('acepta las dos formas en que llegan los roles desde Inertia', () => {
    // auth.roles: cadenas
    assert.equal(esModoAlumno(['Alumno']), true);
    // auth.user.roles: filas de Spatie
    assert.equal(esModoAlumno([{ name: 'Alumno' }]), true);
    assert.equal(esModoAlumno([{ name: 'Alumno' }, { name: 'Docente' }]), false);
});

test('cualquier rol de panel supera al rol Alumno', () => {
    for (const rol of ROLES_PANEL) {
        assert.equal(esModoAlumno(['Alumno', rol]), false, `El rol ${rol} debería abrir el panel.`);
    }
});

test('nombresDeRol ignora entradas vacías', () => {
    assert.deepEqual(nombresDeRol([{ name: 'Alumno' }, null, '', 'admin']), ['Alumno', 'admin']);
    assert.deepEqual(nombresDeRol(), []);
});
