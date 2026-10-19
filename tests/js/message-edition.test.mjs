import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { useMessageEdition } from '../../Modules/CRM/Resources/assets/js/useMessageEdition.js';

const propio = 7;
const ajeno = 9;

function crearApi(requestUpdate = async (message, text) => ({
    success: true,
    message: { text, updated_at: new Date().toISOString() },
})) {
    const avisos = [];

    const api = useMessageEdition({
        isOwnMessage: (message) => String(message.person_id) === String(propio),
        getMessageText: (message) => message.text ?? '',
        setMessageText: (message, text) => {
            message.text = text;
        },
        requestUpdate,
        notify: (msg, type) => avisos.push({ msg, type }),
    });

    return { api, avisos };
}

function mensaje(overrides = {}) {
    return {
        id: 1,
        type: 'text',
        person_id: propio,
        text: 'Hola',
        created_at: new Date().toISOString(),
        ...overrides,
    };
}

test('solo permite editar mensajes propios de texto dentro de los 90 segundos', () => {
    const { api } = crearApi();

    assert.equal(api.canEditMessage(mensaje()), true);
    assert.equal(
        api.canEditMessage(mensaje({ created_at: new Date(Date.now() - 91_000).toISOString() })),
        false
    );
    assert.equal(api.canEditMessage(mensaje({ person_id: ajeno })), false);
    assert.equal(api.canEditMessage(mensaje({ type: 'audio' })), false);
});

test('no abre la edición de un mensaje vencido y avisa por qué', () => {
    const { api, avisos } = crearApi();
    const vencido = mensaje({ created_at: new Date(Date.now() - 91_000).toISOString() });

    api.startEditMessage(vencido);

    assert.equal(api.editingText.value, '');
    assert.equal(avisos.at(-1)?.type, 'warning');
    assert.match(avisos.at(-1)?.msg ?? '', /90 segundos/);
});

test('guarda el nuevo texto y marca el mensaje como editado', async () => {
    const { api, avisos } = crearApi();
    const original = mensaje();

    api.startEditMessage(original);
    assert.equal(api.editingText.value, 'Hola');

    api.editingText.value = 'Hola corregido';
    await api.saveEditedMessage(original);

    assert.equal(original.text, 'Hola corregido');
    assert.equal(original.edited, true);
    assert.equal(avisos.at(-1)?.msg, 'Mensaje actualizado.');
});

test('el chat del alumno muestra el aviso de éxito con SweetAlert', () => {
    const fuente = readFileSync(
        new URL('../../Modules/CRM/Resources/assets/js/Pages/Chat/studentDashboard.vue', import.meta.url),
        'utf8'
    );

    assert.match(fuente, /import Swal from 'sweetalert2'/);
    assert.match(fuente, /notify: showMessage/);
    assert.match(fuente, /toast\.fire\(/);
});

test('los tres chats guardan la edición al presionar Enter', () => {
    const archivos = [
        '../../Modules/CRM/Resources/assets/js/Pages/Chat/Dashboard.vue',
        '../../Modules/CRM/Resources/assets/js/Pages/Chat/studentDashboard.vue',
        '../../Modules/CRM/Resources/assets/js/Components/ChatBox.vue',
    ];

    for (const archivo of archivos) {
        const fuente = readFileSync(new URL(archivo, import.meta.url), 'utf8');
        assert.match(
            fuente,
            /@keydown\.enter\.exact\.prevent="saveEditedMessage\(message\)"/,
            `${archivo} no guarda con Enter`
        );
    }
});

test('no envía una edición vacía', async () => {
    let llamadas = 0;
    const { api, avisos } = crearApi(async () => {
        llamadas += 1;
        return { success: true };
    });
    const original = mensaje();

    api.startEditMessage(original);
    api.editingText.value = '   ';
    await api.saveEditedMessage(original);

    assert.equal(llamadas, 0);
    assert.equal(avisos.at(-1)?.type, 'warning');
});
