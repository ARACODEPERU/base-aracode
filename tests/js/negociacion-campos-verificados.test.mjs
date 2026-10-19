import assert from 'node:assert/strict';
import test from 'node:test';
import { readFileSync } from 'node:fs';

/**
 * Formulario publico de la negociacion (/negotiations/registro-de-inscripcion/{token}).
 *
 * El caso que fija esta prueba: los inputs que se llenan con la consulta a RENIEC o
 * SUNAT deben quedar de solo lectura. Antes se bloqueaban hasta validar y se habilitaban
 * despues, de modo que el cliente podia corregir a mano un dato ya verificado. Ahora el
 * bloqueo es por campo: solo se bloquea el input que la consulta llego a llenar, y si la
 * consulta no devuelve datos el campo queda editable para escribirlo a mano.
 */

const BASE = '../../Modules/Commercial/Resources/assets/js/Pages/Negotiations/Public/composables/';

const modulos = new Map();

/**
 * Carga un composable fuera de Vite: se quitan los imports (vue y sweetalert2) y se
 * inyectan los stubs minimos. `axios` y `route` se resuelven como globales, igual que en
 * el navegador.
 */
const cargar = async (archivo) => {
    if (! modulos.has(archivo)) {
        const fuente = readFileSync(new URL(BASE + archivo, import.meta.url), 'utf8');
        const codigo = [
            'const ref = (value) => ({ value });',
            'const watch = () => {};',
            'const Swal2 = { fire: () => Promise.resolve({}) };',
            fuente.replace(/^import .*$/gm, ''),
        ].join('\n');

        modulos.set(archivo, await import('data:text/javascript;base64,' + Buffer.from(codigo).toString('base64')));
    }

    return modulos.get(archivo);
};

/** Deja correr las microtareas de la cadena then/catch/finally de la consulta. */
const flush = () => new Promise((resolve) => setImmediate(resolve));

/**
 * Arma el escenario de una consulta: el formulario, el estado de bloqueo, la respuesta
 * simulada de la API y la instancia del composable.
 */
const escenario = async ({ composable, funcion, campos, respuestas, extra = {} }) => {
    const { useVerifiedIdentityLocks } = await cargar('useVerifiedIdentityLocks.js');
    const modulo = await cargar(composable);

    const form = { ...campos };
    const locks = useVerifiedIdentityLocks();
    const pendientes = [...respuestas];

    globalThis.route = () => '/ruta';
    globalThis.axios = { post: () => Promise.resolve({ data: pendientes.shift() }) };

    return { form, locks, consulta: modulo[funcion]({ form, token: 'token', locks, ...extra }) };
};

// `accepted_privacidad` va en true porque las consultas de identidad solo salen del
// navegador con el consentimiento puesto: los casos que lo omiten a proposito usan
// `escenarioSinConsentimiento`.
const escenarioDni = (respuestas, consentimiento = true) => escenario({
    composable: 'useDniReniecCheck.js',
    funcion: 'useDniReniecCheck',
    campos: { number: '12345678', names: null, father_lastname: null, mother_lastname: null, full_name: null, address: null, accepted_privacidad: consentimiento },
    respuestas,
    extra: { isDni: { value: true }, normalizeNumber: () => {}, numberLength: () => 8 },
});

const escenarioRuc = (respuestas, consentimiento = true) => escenario({
    composable: 'useRucSunatCheck.js',
    funcion: 'useRucSunatCheck',
    campos: { number: '20123456789', full_name: null, address: null, accepted_privacidad: consentimiento },
    respuestas,
});

test('el bloqueo se libera solo en los campos que se liberan', async () => {
    const { useVerifiedIdentityLocks } = await cargar('useVerifiedIdentityLocks.js');
    const locks = useVerifiedIdentityLocks();

    assert.deepEqual(locks.lockFields(['names', 'father_lastname']), ['names', 'father_lastname']);
    assert.equal(locks.isLocked('names'), true);
    assert.equal(locks.isLocked('mother_lastname'), false, 'Un campo que no se bloqueo sigue editable.');

    locks.unlockFields(['names']);

    assert.equal(locks.isLocked('names'), false);
    assert.equal(locks.isLocked('father_lastname'), true, 'Los demas campos siguen bloqueados.');
});

test('RENIEC devuelve los nombres: quedan bloqueados y la consulta habilita el envio', async () => {
    const { form, locks, consulta } = await escenarioDni([
        {
            success: true,
            source: 'reniec',
            person: { names: 'JUAN', father_lastname: 'PEREZ', mother_lastname: 'GOMEZ', full_name: 'PEREZ GOMEZ JUAN' },
        },
    ]);

    consulta.validateDni();
    await flush();

    assert.equal(form.names, 'JUAN');
    assert.equal(form.father_lastname, 'PEREZ');
    assert.equal(form.mother_lastname, 'GOMEZ');
    assert.equal(locks.isLocked('names'), true);
    assert.equal(locks.isLocked('father_lastname'), true);
    assert.equal(locks.isLocked('mother_lastname'), true);
    assert.equal(consulta.dniValidated.value, true);
    assert.equal(consulta.dniSearched.value, true);
});

test('solo se bloquea el input que la consulta lleno', async () => {
    const { locks, consulta } = await escenarioDni([
        { success: true, person: { names: 'JUAN', father_lastname: null, mother_lastname: null, full_name: null } },
    ]);

    consulta.validateDni();
    await flush();

    assert.equal(locks.isLocked('names'), true);
    assert.equal(locks.isLocked('father_lastname'), false, 'Sin dato de la API el campo sigue editable.');
    assert.equal(locks.isLocked('mother_lastname'), false);
});

test('el respaldo que devuelve el nombre completo se reparte y se bloquea', async () => {
    const { form, locks, consulta } = await escenarioDni([
        {
            success: true,
            source: 'migo',
            person: { names: null, father_lastname: null, mother_lastname: null, full_name: 'PEREZ GOMEZ JUAN' },
        },
    ]);

    consulta.validateDni();
    await flush();

    assert.equal(form.father_lastname, 'PEREZ');
    assert.equal(form.mother_lastname, 'GOMEZ');
    assert.equal(form.names, 'JUAN');
    assert.equal(locks.isLocked('names'), true);
    assert.equal(locks.isLocked('father_lastname'), true);
    assert.equal(locks.isLocked('mother_lastname'), true);
});

test('sin datos de RENIEC los nombres se escriben a mano', async () => {
    const { form, locks, consulta } = await escenarioDni([
        { success: false, error: 'No se pudo validar el DNI con RENIEC en este momento. Intenta nuevamente.' },
    ]);

    consulta.validateDni();
    await flush();

    assert.equal(locks.isLocked('names'), false);
    assert.equal(consulta.dniValidated.value, false);
    assert.equal(consulta.dniSearched.value, true, 'La consulta ya se hizo: el envio no queda bloqueado.');

    // El cliente escribe sus nombres a mano.
    form.names = 'MARIA';
    assert.equal(form.names, 'MARIA');
});

test('si RENIEC responde sin nombres el campo queda editable y se avisa', async () => {
    const { locks, consulta } = await escenarioDni([{ success: true, person: {} }]);

    consulta.validateDni();
    await flush();

    assert.equal(locks.isLocked('names'), false);
    assert.equal(consulta.dniValidated.value, false);
    assert.equal(consulta.dniSearched.value, true);
    assert.match(consulta.dniNotice.value, /no devolvio tus datos/);
});

test('al cambiar el numero de documento se sueltan y se vacian los datos de RENIEC', async () => {
    const { form, locks, consulta } = await escenarioDni([
        { success: true, person: { names: 'JUAN', father_lastname: 'PEREZ', mother_lastname: 'GOMEZ' } },
        { success: false, error: 'No se encontro el documento.' },
    ]);

    consulta.validateDni();
    await flush();

    assert.equal(locks.isLocked('names'), true);

    // Otro DNI: la consulta se dispara sola al completar los 8 digitos.
    form.number = '87654321';
    consulta.onNumberInput();
    await flush();

    assert.equal(form.names, null, 'Los datos del documento anterior no se arrastran.');
    assert.equal(form.father_lastname, null);
    assert.equal(locks.isLocked('names'), false);
    assert.equal(consulta.dniSearched.value, true, 'La nueva consulta si se hizo.');
});

test('SUNAT devuelve razon social y direccion: ambas quedan bloqueadas', async () => {
    const { form, locks, consulta } = await escenarioRuc([
        {
            success: true,
            person: {
                razon_social: 'EMPRESA SAC',
                direccion: 'AV. SIEMPRE VIVA 123',
                estado: 'ACTIVO',
                condicion: 'HABIDO',
            },
        },
    ]);

    consulta.validateClientRuc();
    await flush();

    assert.equal(form.full_name, 'EMPRESA SAC');
    assert.equal(form.address, 'AV. SIEMPRE VIVA 123');
    assert.equal(locks.isLocked('full_name'), true);
    assert.equal(locks.isLocked('address'), true);
    assert.equal(consulta.rucClientValidated.value, true);
    assert.equal(consulta.rucClientSearched.value, true);
});

test('SUNAT sin direccion: solo la razon social queda bloqueada', async () => {
    const { locks, consulta } = await escenarioRuc([
        { success: true, person: { razon_social: 'EMPRESA SAC', direccion: '-', estado: 'ACTIVO', condicion: 'HABIDO' } },
    ]);

    consulta.validateClientRuc();
    await flush();

    assert.equal(locks.isLocked('full_name'), true);
    assert.equal(locks.isLocked('address'), false, 'Sin direccion de SUNAT el campo sigue editable.');
});

test('sin datos de SUNAT el RUC se completa a mano', async () => {
    const { locks, consulta } = await escenarioRuc([{ success: false, error: 'No se pudo validar el RUC.' }]);

    consulta.validateClientRuc();
    await flush();

    assert.equal(locks.isLocked('full_name'), false);
    assert.equal(consulta.rucClientValidated.value, false);
    assert.equal(consulta.rucClientSearched.value, true);
});

const escenarioEmail = (respuestas, consentimiento = true) => escenario({
    composable: 'useEmailAccountCheck.js',
    funcion: 'useEmailAccountCheck',
    campos: { email: 'ana@empresa.test', accepted_privacidad: consentimiento },
    respuestas,
    extra: { loadPerson: () => {} },
});

/** Cuenta las consultas que salen a la API despues de crear el escenario. */
const contarPeticiones = () => {
    const estado = { total: 0 };
    const postOriginal = globalThis.axios.post;

    globalThis.axios.post = (...args) => {
        estado.total += 1;

        return postOriginal(...args);
    };

    return estado;
};

test('sin aceptar la politica de privacidad el DNI no sale del navegador', async () => {
    const { consulta } = await escenarioDni([{ success: true, person: { names: 'ANA QUISPE' } }], false);
    const peticiones = contarPeticiones();

    consulta.validateDni();
    await flush();

    assert.equal(peticiones.total, 0, 'RENIEC no se consulta sin consentimiento.');
    assert.equal(consulta.dniSearched.value, false);
    assert.match(consulta.dniHint.value, /Política de Privacidad/);
});

test('sin aceptar la politica de privacidad el RUC no se consulta en SUNAT', async () => {
    const { consulta } = await escenarioRuc([{ success: true, person: { razon_social: 'EMPRESA SAC' } }], false);
    const peticiones = contarPeticiones();

    consulta.validateClientRuc();
    await flush();

    assert.equal(peticiones.total, 0, 'SUNAT no se consulta sin consentimiento.');
    assert.equal(consulta.rucClientSearched.value, false);
    assert.match(consulta.rucClientNotice.value, /Política de Privacidad/);
});

test('sin aceptar la politica de privacidad el correo no se consulta', async () => {
    const { consulta } = await escenarioEmail([{ exists: false }], false);
    const peticiones = contarPeticiones();

    consulta.checkEmailAccount();
    await flush();

    assert.equal(peticiones.total, 0, 'El correo no se consulta sin consentimiento.');
});

test('con la politica aceptada el correo si se consulta', async () => {
    const { consulta } = await escenarioEmail([{ exists: false }]);
    const peticiones = contarPeticiones();

    consulta.checkEmailAccount();
    await flush();

    assert.equal(peticiones.total, 1);
});

test('al cambiar el RUC se sueltan y se vacian los datos de SUNAT', async () => {
    const { form, locks, consulta } = await escenarioRuc([
        { success: true, person: { razon_social: 'EMPRESA SAC', direccion: 'AV. SIEMPRE VIVA 123' } },
    ]);

    consulta.validateClientRuc();
    await flush();

    assert.equal(locks.isLocked('full_name'), true);

    form.number = '20999999999';
    consulta.onRucClientInput();

    assert.equal(form.full_name, null, 'La razon social era del RUC anterior.');
    assert.equal(form.address, null);
    assert.equal(locks.isLocked('full_name'), false);
    assert.equal(locks.isLocked('address'), false);
    assert.equal(consulta.rucClientSearched.value, false, 'Hay que consultar el nuevo RUC.');
});
